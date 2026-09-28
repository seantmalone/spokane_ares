<?php
/**
 * Regression tests for QA-041 (PLAN §2.2 #11, no third-party requests; §4.2,
 * profile pictures hidden): every page a signed-in user opens sends a SHA-256
 * hash of that user's e-mail address to Gravatar. WordPress 7.1 prints the
 * toolbar's "Howdy" picture (28px, and 64px in the account menu, each with a
 * 2x srcset) as secure.gravatar.com/avatar/<sha256 of the e-mail> URLs, the
 * Profile and Users screens do the same, and the REST user objects the block
 * editor loads carry avatar_urls pointing there. show_avatars is on and
 * nothing in the must-use plugin filters avatar data.
 *
 * Correct behaviour: for every signed-in user level, nothing the site prints
 * or returns points at gravatar.com. The toolbar shows no Gravatar picture,
 * get_avatar() and get_avatar_url() (which every avatar in core goes through,
 * including the ones printed with force_display) give no Gravatar URL, and
 * the REST user objects hold none.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The signed-in role keys (every role key except anonymous).
 */
function qa041_signed_in_roles(): array {
	return array_values( array_diff( role_keys(), array( 'anonymous' ) ) );
}

/**
 * The first Gravatar URL in a piece of output, or '' when there is none.
 *
 * @param mixed $output HTML, a URL, or data to be JSON-encoded.
 */
function qa041_gravatar_in( $output ): string {
	if ( ! is_string( $output ) ) {
		$output = (string) wp_json_encode( $output, JSON_UNESCAPED_SLASHES );
	}
	return preg_match( '#(?:https?:)?//[a-z0-9.-]*gravatar\.com/[^\s"\'<>]*#i', $output, $m ) ? $m[0] : '';
}

/**
 * The toolbar's account item and account menu (the two places WordPress
 * prints the signed-in user's picture: "Howdy, name" and the menu under it)
 * for the current user, as their titles and meta.
 */
function qa041_toolbar_account_output(): string {
	require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
	$bar = new \WP_Admin_Bar();
	wp_admin_bar_my_account_item( $bar );
	wp_admin_bar_my_account_menu( $bar );
	$out = '';
	foreach ( (array) $bar->get_nodes() as $id => $node ) {
		$out .= $id . ': ' . $node->title . ' ' . wp_json_encode( $node->meta, JSON_UNESCAPED_SLASHES ) . "\n";
	}
	return $out;
}

test(
	'the toolbar shows no Gravatar picture for any signed-in user level',
	function () {
		$leaks = array();
		foreach ( qa041_signed_in_roles() as $role ) {
			as_role( $role );
			$out = qa041_toolbar_account_output();
			assert_contains( 'my-account: ', $out, $role . ': the toolbar account item was not built' );
			$url = qa041_gravatar_in( $out );
			if ( '' !== $url ) {
				$leaks[ $role ] = $url;
			}
		}
		assert_same( array(), $leaks, 'toolbar pictures sent to Gravatar (role => first URL)' );
	}
);

test(
	'get_avatar() and get_avatar_url() give no Gravatar URL for any site user',
	function () {
		$leaks = array();
		foreach ( qa041_signed_in_roles() as $role ) {
			as_role( $role );
			$user = wp_get_current_user();
			$seen = array(
				'get_avatar_url( id )'     => get_avatar_url( $user->ID ),
				'get_avatar_url( e-mail )' => get_avatar_url( $user->user_email ),
				'get_avatar( id, 32 )'     => get_avatar( $user->ID, 32 ),
			);
			foreach ( $seen as $call => $output ) {
				$url = qa041_gravatar_in( (string) $output );
				if ( '' !== $url ) {
					$leaks[ $role . ' ' . $call ] = $url;
				}
			}
		}
		assert_same( array(), $leaks, 'avatar URLs pointing at Gravatar (role call => URL)' );
	}
);

test(
	'REST user objects carry no Gravatar URLs for any signed-in user level',
	function () {
		$leaks = array();
		foreach ( qa041_signed_in_roles() as $role ) {
			as_role( $role );
			$res = expect_not_wp_error( rest( 'GET', '/wp/v2/users/me' ), $role . ': GET /wp/v2/users/me' );
			assert_same( user_id( $role ), (int) ( $res->get_data()['id'] ?? 0 ), $role . ': /users/me answered for the wrong user' );
			$url = qa041_gravatar_in( $res->get_data() );
			if ( '' !== $url ) {
				$leaks[ $role . ' /users/me' ] = $url;
			}
		}

		// An administrator's user list holds every account's avatar_urls.
		as_role( 'admin' );
		$res = expect_not_wp_error(
			rest(
				'GET',
				'/wp/v2/users',
				array(
					'context'  => 'edit',
					'per_page' => 100,
				)
			),
			'admin: GET /wp/v2/users'
		);
		assert_true( count( (array) $res->get_data() ) >= count( qa041_signed_in_roles() ), 'admin: the user list is missing the dev accounts' );
		$url = qa041_gravatar_in( $res->get_data() );
		if ( '' !== $url ) {
			$leaks['admin /users?context=edit'] = $url;
		}

		assert_same( array(), $leaks, 'REST user objects pointing at Gravatar (role route => first URL)' );
	}
);
