<?php
/**
 * Regression tests for QA-048 (PLAN §4.2, the profile screen for
 * non-admins shows only name, e-mail, password and Two-Factor options;
 * colour scheme, keyboard shortcuts, toolbar, website, biography and
 * profile picture are hidden).
 *
 * Before the fix the trim was CSS only, keyed to `.spk-editor.profile-php`.
 * Every non-admin level can also open its own profile at
 * wp-admin/user-edit.php?user_id=<own ID> (core allows edit_user on
 * yourself), where the body class is `user-edit-php`, so none of the rules
 * apply: Toolbar, Keyboard Shortcuts, Website, "About Yourself" /
 * Biographical Info and Profile Picture all show, and the toolbar gains a
 * "View User" link to /author/<login>/ (a 404). And because the fields were
 * only hidden, a crafted Update Profile POST (to profile.php or to that
 * user-edit.php URL) still stored user_url and description.
 *
 * Correct behaviour, for every signed-in level except administrator:
 * - opening your own profile at user-edit.php?user_id=<self> sends you to
 *   profile.php (the one trimmed screen);
 * - an Update Profile POST that carries a Website or Biography, through
 *   either URL, stores neither (the save itself still works);
 * and, as controls, an administrator keeps the full profile (Website and
 * Biography saved) and still opens other accounts at user-edit.php.
 *
 * The requests are simulated the way wp-admin runs them: $pagenow set, the
 * site's own admin_init handlers (spokares-core and spokares-hardening; core's
 * admin_init can't be fired again inside the test request), the screen set,
 * load-{$pagenow}, then for an update user-edit.php's own steps:
 * check_admin_referer( 'update-user_<ID>' ), edit_user capability,
 * personal_options_update (own profile) or edit_user_profile_update, and
 * edit_user(). IS_PROFILE_PAGE can't be defined here (it is a constant).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The signed-in levels that get the trimmed profile (all but anonymous and
 * administrator).
 */
function qa048_trimmed_roles(): array {
	return array_values( array_diff( role_keys(), array( 'anonymous', 'admin' ) ) );
}

/**
 * The admin_init handlers the site's own code adds (spokares-core and the
 * spokares-hardening must-use plugin), in priority order. Core's handlers and
 * the dev test endpoint are left out: admin_init already ran for this request.
 */
function qa048_site_admin_init_handlers(): array {
	global $wp_filter;
	if ( empty( $wp_filter['admin_init'] ) ) {
		return array();
	}
	$callbacks = $wp_filter['admin_init']->callbacks;
	ksort( $callbacks );
	$handlers = array();
	foreach ( $callbacks as $list ) {
		foreach ( $list as $cb ) {
			$fn = $cb['function'];
			try {
				$ref = is_array( $fn ) ? new \ReflectionMethod( $fn[0], $fn[1] ) : new \ReflectionFunction( $fn );
			} catch ( \ReflectionException $e ) {
				continue;
			}
			$file = wp_normalize_path( (string) $ref->getFileName() );
			if ( str_contains( $file, '/spokares-core/' ) || str_contains( $file, '/spokares-hardening/' ) ) {
				$handlers[] = $fn;
			}
		}
	}
	return $handlers;
}

/**
 * Run a request for profile.php or user-edit.php as the current user, the
 * way wp-admin does (see the file comment). Returns call_request()'s result;
 * for an update, `returned` is edit_user()'s result.
 *
 * @param string $screen  'profile.php' or 'user-edit.php'.
 * @param int    $user_id Account asked for (user-edit.php's user_id).
 * @param array  $post    Body fields (unslashed); empty for the GET.
 */
function qa048_profile_request( string $screen, int $user_id, array $post = array() ): array {
	global $pagenow;
	$saved   = array( $pagenow, $GLOBALS['current_screen'] ?? null, $GLOBALS['typenow'] ?? '', $GLOBALS['taxnow'] ?? '' );
	$pagenow = $screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated screen, restored below.
	$get     = 'user-edit.php' === $screen ? array( 'user_id' => (string) $user_id ) : array();
	try {
		return call_request(
			$post ? 'POST' : 'GET',
			$get,
			$post,
			static function () use ( $screen, $user_id, $post ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				foreach ( qa048_site_admin_init_handlers() as $handler ) {
					call_user_func( $handler );
				}
				set_current_screen( 'user-edit.php' === $screen ? 'user-edit' : 'profile' );
				do_action( 'load-' . $screen ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's screen hook, fired as admin.php does.
				if ( ! $post ) {
					return null;
				}
				// user-edit.php, case 'update'.
				check_admin_referer( 'update-user_' . $user_id );
				if ( ! current_user_can( 'edit_user', $user_id ) ) {
					wp_die( 'Sorry, you are not allowed to edit this user.' );
				}
				if ( get_current_user_id() === $user_id ) {
					do_action( 'personal_options_update', $user_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's hook, fired as user-edit.php does.
				} else {
					do_action( 'edit_user_profile_update', $user_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's hook, fired as user-edit.php does.
				}
				return edit_user( $user_id );
			}
		);
	} finally {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the simulated screen.
		$pagenow                   = $saved[0];
		$GLOBALS['current_screen'] = $saved[1];
		$GLOBALS['typenow']        = $saved[2];
		$GLOBALS['taxnow']         = $saved[3];
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}

/**
 * The Update Profile form as the profile screen posts it for an account
 * (the hidden Website and Biography rows are still in the form, empty),
 * with $extra on top.
 *
 * @param int    $user_id Account being saved.
 * @param string $screen  'profile.php' or 'user-edit.php'.
 * @param array  $extra   Fields to add or replace.
 */
function qa048_profile_fields( int $user_id, string $screen, array $extra = array() ): array {
	$user = get_userdata( $user_id );
	return array_merge(
		array(
			'_wpnonce'         => wp_create_nonce( 'update-user_' . $user_id ),
			'_wp_http_referer' => '/wp-admin/' . $screen,
			'from'             => 'profile',
			'checkuser_id'     => (string) get_current_user_id(),
			'action'           => 'update',
			'user_id'          => (string) $user_id,
			'email'            => $user->user_email,
			'nickname'         => $user->nickname,
			'display_name'     => $user->display_name,
			'first_name'       => $user->first_name,
			'last_name'        => $user->last_name,
			'url'              => '',
			'description'      => '',
		),
		$extra
	);
}

/**
 * Empty an account's Website and Biography, so each test starts from the
 * state the dev accounts are created in.
 *
 * @param int $user_id Account.
 */
function qa048_clear_extras( int $user_id ): void {
	$res = wp_update_user(
		array(
			'ID'          => $user_id,
			'user_url'    => '',
			'description' => '',
		)
	);
	expect_not_wp_error( $res, 'clearing Website and Biography' );
	clean_user_cache( $user_id );
}

/**
 * The stored Website and Biography of an account.
 *
 * @param int $user_id Account.
 */
function qa048_extras( int $user_id ): array {
	clean_user_cache( $user_id );
	$user = get_userdata( $user_id );
	return array(
		'user_url'    => (string) $user->user_url,
		'description' => (string) get_user_meta( $user_id, 'description', true ),
	);
}

/**
 * Post a crafted Update Profile (Website and Biography filled in) to your
 * own profile through $screen, for every trimmed level. Returns, per role,
 * what was stored when anything was.
 *
 * @param string $screen 'profile.php' or 'user-edit.php'.
 */
function qa048_crafted_saves( string $screen ): array {
	$stored = array();
	foreach ( qa048_trimmed_roles() as $role ) {
		as_role( $role );
		$id = user_id( $role );
		qa048_clear_extras( $id );
		$res = qa048_profile_request(
			$screen,
			$id,
			qa048_profile_fields(
				$id,
				$screen,
				array(
					'url'         => 'https://qa048.example.invalid/' . $role,
					'description' => 'QA-048 biography for ' . $role,
				)
			)
		);
		$now = qa048_extras( $id );
		if ( '' !== $now['user_url'] || '' !== $now['description'] ) {
			$stored[ $role ] = $now + array(
				'redirect' => $res['redirect'],
				'die'      => $res['die'],
			);
		}
	}
	return $stored;
}

test(
	'opening your own profile at user-edit.php?user_id=<self> sends every non-admin level to profile.php',
	function () {
		$wrong = array();
		foreach ( qa048_trimmed_roles() as $role ) {
			as_role( $role );
			$res = qa048_profile_request( 'user-edit.php', user_id( $role ) );
			if ( null === $res['redirect'] || ! str_contains( (string) wp_parse_url( $res['redirect'], PHP_URL_PATH ), '/wp-admin/profile.php' ) ) {
				$wrong[ $role ] = null !== $res['redirect'] ? 'redirect to ' . $res['redirect'] : ( null !== $res['die'] ? 'wp_die: ' . $res['die'] : 'the untrimmed user-edit.php screen opened (Toolbar, Website, Biographical Info, Profile Picture, "View User")' );
			}
		}
		assert_same( array(), $wrong, 'own profile via user-edit.php should redirect to profile.php (role => what happened)' );
	}
);

test(
	'a profile.php save stores no Website or Biography for any non-admin level',
	function () {
		assert_same( array(), qa048_crafted_saves( 'profile.php' ), 'hidden fields stored from a crafted profile.php POST (role => stored values)' );
	}
);

test(
	'a save through user-edit.php?user_id=<self> stores no Website or Biography for any non-admin level',
	function () {
		assert_same( array(), qa048_crafted_saves( 'user-edit.php' ), 'hidden fields stored from a crafted user-edit.php POST (role => stored values)' );
	}
);

test(
	'control: the normal profile.php save still works for every non-admin level',
	function () {
		foreach ( qa048_trimmed_roles() as $role ) {
			as_role( $role );
			$id  = user_id( $role );
			$res = qa048_profile_request( 'profile.php', $id, qa048_profile_fields( $id, 'profile.php', array( 'first_name' => 'Renamed ' . $role ) ) );
			assert_same( null, $res['redirect'], $role . ': the profile save was redirected' );
			assert_same( null, $res['die'], $role . ': the profile save was refused' );
			expect_not_wp_error( $res['returned'], $role . ': edit_user' );
			clean_user_cache( $id );
			assert_same( 'Renamed ' . $role, get_userdata( $id )->first_name, $role . ': First Name after saving' );
		}
	}
);

test(
	'control: an administrator keeps the full profile (Website and Biography are saved)',
	function () {
		as_role( 'admin' );
		$id = user_id( 'admin' );
		qa048_clear_extras( $id );
		$res = qa048_profile_request(
			'profile.php',
			$id,
			qa048_profile_fields(
				$id,
				'profile.php',
				array(
					'url'         => 'https://qa048.example.invalid/admin',
					'description' => 'QA-048 administrator biography',
				)
			)
		);
		assert_same( null, $res['die'], 'the administrator profile save was refused' );
		expect_not_wp_error( $res['returned'], 'edit_user' );
		assert_same(
			array(
				'user_url'    => 'https://qa048.example.invalid/admin',
				'description' => 'QA-048 administrator biography',
			),
			qa048_extras( $id ),
			'administrator Website and Biography after saving'
		);
	}
);

test(
	'control: an administrator still opens another account at user-edit.php',
	function () {
		as_role( 'admin' );
		$res = qa048_profile_request( 'user-edit.php', user_id( 'ares-editor' ) );
		assert_same( null, $res['redirect'], 'administrator sent away from Edit User' );
		assert_same( null, $res['die'], 'administrator refused Edit User' );
	}
);
