<?php
/**
 * Regression tests for QA-013 (PLAN §4.1): the site has only Administrator
 * and ARES Editor accounts. Before the fix, Users › Add User offered ARES
 * Editor, Subscriber (preselected), Contributor, Author, Editor and
 * Administrator, and Settings › General › New User Default Role was
 * Subscriber. WordPress's core Editor is the natural pick for a volunteer
 * and carries powers an ARES Editor deliberately lacks (deleting pages,
 * the Media Library, patterns, categories).
 *
 * Correct behaviour: the role lists an administrator gets (Add User, a
 * user's profile, the Users list's "Change role to…") offer only
 * Administrator and ARES Editor, plus, on an existing account's own edit
 * screen, the role that account already has (so re-saving a legacy
 * core-role account neither fails nor silently changes its role); the new
 * user default role is ARES Editor; and a crafted Add User or profile save
 * cannot give a core role.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The roles an administrator may give, sorted.
 */
function qa013_editable_role_keys(): array {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	$keys = array_keys( get_editable_roles() );
	sort( $keys );
	return $keys;
}

/**
 * The option values and the selected value of a rendered role <select>.
 *
 * @param string $html The <option> list from wp_dropdown_roles().
 */
function qa013_parse_options( string $html ): array {
	preg_match_all( '/<option\b([^>]*)>/', $html, $m );
	$values   = array();
	$selected = null;
	foreach ( $m[1] as $attrs ) {
		if ( ! preg_match( '/value=[\'"]([^\'"]*)[\'"]/', $attrs, $v ) ) {
			continue;
		}
		$values[] = $v[1];
		if ( false !== strpos( $attrs, 'selected' ) ) {
			$selected = $v[1];
		}
	}
	sort( $values );
	return array(
		'values'   => $values,
		'selected' => $selected,
	);
}

/**
 * Run $callback as if it were the request for user-edit.php?user_id=$user_id
 * (the screen an administrator uses to edit another account).
 *
 * @param int      $user_id  Account being edited.
 * @param array    $post     Body fields (unslashed); empty for the GET.
 * @param callable $callback The code to run.
 */
function qa013_on_user_edit( int $user_id, array $post, callable $callback ): array {
	global $pagenow;
	$saved   = $pagenow;
	$pagenow = 'user-edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated screen, restored below.
	try {
		if ( $post ) {
			$post = array_merge(
				array(
					'action'  => 'update',
					'user_id' => (string) $user_id,
				),
				$post
			);
		}
		return call_request( $post ? 'POST' : 'GET', array( 'user_id' => (string) $user_id ), $post, $callback );
	} finally {
		$pagenow = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
	}
}

/**
 * The profile form fields for an existing account, with a chosen role.
 *
 * @param int    $user_id Account being edited.
 * @param string $role    Role to post.
 */
function qa013_profile_fields( int $user_id, string $role ): array {
	$user = get_userdata( $user_id );
	return array(
		'_wpnonce'     => wp_create_nonce( 'update-user_' . $user_id ),
		'role'         => $role,
		'email'        => $user->user_email,
		'nickname'     => $user->nickname,
		'display_name' => $user->display_name,
		'first_name'   => $user->first_name,
		'last_name'    => $user->last_name,
	);
}

/**
 * Submit Users › Add User (user-new.php's createuser action) as the
 * current user. Returns call_request()'s result.
 *
 * @param string $login New username.
 * @param string $role  Role picked in the form.
 */
function qa013_add_user( string $login, string $role ): array {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
	add_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
	try {
		return call_request(
			'POST',
			array(),
			array(
				'action'               => 'createuser',
				'_wpnonce_create-user' => wp_create_nonce( 'create-user' ),
				'user_login'           => $login,
				'email'                => $login . '@example.invalid',
				'pass1'                => 'qa013-Long-Passphrase-9',
				'pass2'                => 'qa013-Long-Passphrase-9',
				'role'                 => $role,
			),
			static function () {
				return edit_user();
			}
		);
	} finally {
		remove_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
		remove_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
	}
}

test(
	'Add User offers only Administrator and ARES Editor',
	function () {
		as_role( 'admin' );
		assert_same( array( 'administrator', 'ares_editor' ), qa013_editable_role_keys(), 'roles an administrator is offered' );
	}
);

test(
	'the Add User role list preselects ARES Editor and lists no core role',
	function () {
		as_role( 'admin' );
		require_once ABSPATH . 'wp-admin/includes/template.php';
		// user-new.php: wp_dropdown_roles( get_option( 'default_role' ) ).
		ob_start();
		wp_dropdown_roles( get_option( 'default_role' ) );
		$select = qa013_parse_options( (string) ob_get_clean() );
		assert_same( array( 'administrator', 'ares_editor' ), $select['values'], 'Add User role options' );
		assert_same( 'ares_editor', $select['selected'], 'preselected role' );
	}
);

test(
	'the new user default role is ARES Editor',
	function () {
		assert_same( 'ares_editor', get_option( 'default_role' ), 'Settings › General › New User Default Role' );
	}
);

test(
	'Add User refuses to create an account with a core role',
	function () {
		foreach ( array( 'subscriber', 'contributor', 'author', 'editor' ) as $role ) {
			as_role( 'admin' );
			$login = 'qa013-new-' . $role;
			$res   = qa013_add_user( $login, $role );
			assert_same( 403, $res['status'], 'Add User with role ' . $role . ' should be refused (wp_die 403)' );
			assert_false( (bool) username_exists( $login ), 'a ' . $role . ' account was created' );
		}
	}
);

test(
	'an administrator cannot change an ARES Editor to core Editor from the profile screen',
	function () {
		as_role( 'admin' );
		$id  = user_id( 'ares-editor' );
		$res = qa013_on_user_edit(
			$id,
			qa013_profile_fields( $id, 'editor' ),
			static function () use ( $id ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				return edit_user( $id );
			}
		);
		assert_same( 403, $res['status'], 'changing an ARES Editor to core Editor should be refused (wp_die 403)' );
		clean_user_cache( $id );
		assert_same( array( 'ares_editor' ), array_values( get_userdata( $id )->roles ), 'role after the refused change' );
	}
);

test(
	'control: Add User still creates an ARES Editor',
	function () {
		as_role( 'admin' );
		$res = qa013_add_user( 'qa013-new-ares', 'ares_editor' );
		assert_same( null, $res['die'], 'Add User refused an ARES Editor' );
		$id = expect_not_wp_error( $res['returned'], 'edit_user' );
		assert_true( is_int( $id ) && $id > 0, 'new user ID' );
		assert_same( array( 'ares_editor' ), array_values( get_userdata( $id )->roles ), 'new account role' );
	}
);

test(
	'control: an existing core-role account keeps its role on its own edit screen and can be re-saved',
	function () {
		as_role( 'admin' );
		$id = user_id( 'core-editor' );

		// The screen: the account's own role stays offered, so it is the
		// selected option (not "No role for this site").
		$get = qa013_on_user_edit(
			$id,
			array(),
			static function () {
				return qa013_editable_role_keys();
			}
		);
		assert_contains( 'editor', $get['returned'], 'core Editor profile: its own role must stay in the list' );

		// Re-saving the profile without touching the Role list.
		$res = qa013_on_user_edit(
			$id,
			qa013_profile_fields( $id, 'editor' ),
			static function () use ( $id ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				return edit_user( $id );
			}
		);
		assert_same( null, $res['die'], 're-saving a core Editor profile was refused' );
		expect_not_wp_error( $res['returned'], 'edit_user' );
		clean_user_cache( $id );
		assert_same( array( 'editor' ), array_values( get_userdata( $id )->roles ), 'role after re-saving' );
	}
);
