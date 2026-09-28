<?php
/**
 * Regression tests for QA-038 (PLAN §3.2, §3.4 Settings › ARES site).
 *
 * Settings › ARES site offered two groups.io addresses, "Main group" and
 * "Member files" (spk_site groupsio_main and groupsio_files), but nothing on
 * the site read them: the groups.io links live in the theme's parts and
 * patterns and in page text. An administrator who changed or cleared them
 * saw "Saved." and nothing changed. The owner decided (2026-09-27) to remove
 * the two fields: the form, the save handler, the option's sanitiser and
 * defaults, the dev seed and the help text. The groups.io links in page text
 * stay as they are.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The Settings › ARES site screen as the current user sees it.
 */
function qa038_screen(): string {
	ob_start();
	\spokares_site_page();
	return (string) ob_get_clean();
}

/**
 * The Settings › ARES site form fields as the screen posts them, from the
 * stored option, plus any extra fields.
 *
 * @param array $extra Extra site[] fields.
 */
function qa038_fields( array $extra = array() ): array {
	$place = \spokares_opt( 'spk_site' )['place'];
	return array(
		'site' => array_merge(
			array(
				'place' => array(
					'name'        => $place['name'],
					'street'      => $place['street'],
					'city'        => $place['city'],
					'state'       => $place['state'],
					'zip'         => $place['zip'],
					'needs_check' => $place['needs_check'] ? '1' : '',
				),
			),
			$extra
		),
	);
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa038_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Every key of an array, nested keys too.
 *
 * @param array $value Array.
 */
function qa038_keys( array $value ): array {
	$keys = array();
	foreach ( $value as $k => $v ) {
		$keys[] = (string) $k;
		if ( is_array( $v ) ) {
			$keys = array_merge( $keys, qa038_keys( $v ) );
		}
	}
	return $keys;
}

/**
 * Fail when any key of an spk_site value names groups.io.
 *
 * @param array  $site    spk_site value.
 * @param string $message Where it came from.
 */
function qa038_assert_no_groupsio( array $site, string $message ): void {
	$found = array_values( array_filter( qa038_keys( $site ), static fn( $k ) => false !== stripos( $k, 'groupsio' ) ) );
	assert_same( array(), $found, $message . ' still has groups.io keys: ' . wp_json_encode( $site ) );
}

test(
	'Settings › ARES site has no groups.io address fields',
	function () {
		as_role( 'admin' );
		$html = qa038_screen();
		assert_contains( 'name="site[place][name]"', $html, 'the meeting place fields are still on the screen (control)' );
		assert_not_contains( 'groupsio', $html, 'the screen still has a groups.io field' );
		assert_not_contains( 'Main group', $html, 'the screen still has the "Main group" field' );
		assert_not_contains( 'Member files', $html, 'the screen still has the "Member files" field' );
		assert_not_contains( '>groups.io<', $html, 'the screen still has the "groups.io" section heading' );
	}
);

test(
	'the spk_site defaults and the option as the plugin reads it have no groups.io addresses',
	function () {
		// The stored row itself depends on when the dev site was seeded (a
		// site started before this change still holds the old keys until the
		// next save); the seed is checked by the source scan below.
		qa038_assert_no_groupsio( \spokares_option_defaults()['spk_site'], 'the spk_site defaults' );
		\spokares_opt_flush();
		qa038_assert_no_groupsio( \spokares_opt( 'spk_site' ), 'spokares_opt( spk_site )' );
		assert_same( 'Spokane County Emergency Management', \spokares_opt( 'spk_site' )['place']['name'], 'the seeded meeting place is kept (control)' );
	}
);

test(
	'the spk_site sanitiser drops groups.io addresses',
	function () {
		$clean = \spokares_sanitize_opt_site(
			array(
				'groupsio_main'  => 'https://spokaneares-acs.groups.io/g/main',
				'groupsio_files' => 'https://spokaneares-acs.groups.io/g/main/files',
				'place'          => array( 'name' => 'Somewhere' ),
			)
		);
		qa038_assert_no_groupsio( $clean, 'spokares_sanitize_opt_site()' );
		assert_same( 'Somewhere', $clean['place']['name'], 'the sanitiser keeps the meeting place (control)' );

		// Any write of the option goes through the registered sanitiser.
		update_option(
			'spk_site',
			array_merge(
				(array) get_option( 'spk_site' ),
				array(
					'groupsio_main'  => 'https://example.org/g/main',
					'groupsio_files' => 'https://example.org/g/main/files',
				)
			)
		);
		qa038_assert_no_groupsio( (array) get_option( 'spk_site' ), 'spk_site after update_option()' );
	}
);

test(
	'an older stored spk_site with groups.io addresses reads without them',
	function () {
		$legacy = static fn() => array(
			'groupsio_main'  => 'https://spokaneares-acs.groups.io/g/main',
			'groupsio_files' => 'https://spokaneares-acs.groups.io/g/main/files',
			'place'          => array(
				'name'        => 'Old place',
				'street'      => '1 Main St',
				'city'        => 'Spokane',
				'state'       => 'WA',
				'zip'         => '99201',
				'needs_check' => false,
			),
		);
		add_filter( 'pre_option_spk_site', $legacy );
		\spokares_opt_flush();
		try {
			$site = \spokares_opt( 'spk_site' );
		} finally {
			remove_filter( 'pre_option_spk_site', $legacy );
			\spokares_opt_flush();
		}
		qa038_assert_no_groupsio( $site, 'spokares_opt( spk_site ) over an older stored value' );
		assert_same( 'Old place', $site['place']['name'], 'the stored meeting place is read (control)' );
	}
);

test(
	'saving ARES site with groups.io fields posted stores none and says "Saved."',
	function () {
		as_role( 'admin' );
		qa038_notices();
		$res = post_form(
			'spokares_save_site',
			qa038_fields(
				array(
					'groupsio_main'  => 'not an address',
					'groupsio_files' => 'https://example.org/g/main/files',
				)
			)
		);
		assert_same( null, $res['die'], 'the save was refused outright' );
		assert_contains( 'page=spokares-site', (string) $res['redirect'], 'the save redirects back to its screen' );
		$said = qa038_notices();
		assert_same( array( 'success' ), array_values( array_unique( wp_list_pluck( $said, 'type' ) ) ), 'the save complained about a field that is gone: ' . wp_json_encode( $said ) );
		\spokares_opt_flush();
		qa038_assert_no_groupsio( (array) get_option( 'spk_site' ), 'spk_site after the save' );
	}
);

test(
	'saving ARES site still saves the meeting place (control)',
	function () {
		as_role( 'admin' );
		$fields                          = qa038_fields();
		$fields['site']['place']['name'] = 'Spokane County EOC';
		$res                             = post_form( 'spokares_save_site', $fields );
		assert_same( null, $res['die'], 'the save was refused outright' );
		\spokares_opt_flush();
		assert_same( 'Spokane County EOC', \spokares_opt( 'spk_site' )['place']['name'], 'the meeting place name' );
	}
);

test(
	'the ARES site help text no longer says the screen holds the groups.io addresses',
	function () {
		as_role( 'admin' );
		$lines = implode( ' ', \spokares_help_lines()['spokares-site'] ?? array() );
		assert_contains( 'meeting place', $lines, 'the help still explains the meeting place (control)' );
		assert_not_contains( 'groups.io addresses', $lines, 'the ARES site help text' );
	}
);

test(
	'no plugin code or dev seed refers to the groups.io settings',
	function () {
		$files = array_merge(
			glob( SPOKARES_CORE_DIR . 'inc/*.php' ),
			array( SPOKARES_CORE_DIR . 'spokares-core.php' ),
			is_dir( '/spokares-dev/seed' ) ? glob( '/spokares-dev/seed/*.php' ) : array(),
			is_dir( '/spokares-dev/setup' ) ? glob( '/spokares-dev/setup/*.php' ) : array()
		);
		assert_true( count( $files ) > 10, 'found the plugin files to check (control)' );
		$hits = array();
		foreach ( $files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local plugin or dev file.
			$src = (string) file_get_contents( $file );
			if ( false !== stripos( $src, 'groupsio_' ) ) {
				$hits[] = wp_basename( $file );
			}
		}
		assert_same( array(), $hits, 'files that still read or write groupsio_main / groupsio_files' );
	}
);
