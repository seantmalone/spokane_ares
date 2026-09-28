<?php
/**
 * Regression tests for QA-002 (PLAN §4.1, §5.4, §5.8): the per-user "Net
 * details and Meeting rules" grant (spokares_edit_net_details) is for ARES
 * Editors. Changing an ARES Editor to Subscriber, or to "No role for this
 * site", must end it: WP_User::set_role() keeps per-user caps, so before
 * the fix the demoted account could still open and save Net details and
 * Meeting rules (changing the public repeater frequency) while sitting
 * below the two-factor floor and the weekly audit, which only look at
 * users who can edit_posts. The grant checkbox must also be offered, and
 * honoured, only on ARES Editor profiles, not on Subscriber, Contributor,
 * Author or core Editor profiles.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Change a dev account's role the way wp-admin does (set_role on a fresh
 * WP_User), then sign that account in. Returns the reloaded user.
 *
 * @param string $key  Role key from qa-users.json.
 * @param string $role New role ('' for "No role for this site").
 */
function qa002_demote( string $key, string $role ): \WP_User {
	$user = new \WP_User( user_id( $key ) );
	$user->set_role( $role );
	clean_user_cache( $user->ID );
	return as_role( $key );
}

/**
 * The Net details form fields with a new primary frequency, as the screen
 * posts them.
 *
 * @param string $freq Frequency to save.
 */
function qa002_net_fields( string $freq ): array {
	$radio = \spokares_opt( 'spk_radio' );
	$nets  = \spokares_opt( 'spk_nets' );
	return array(
		'radio' => array(
			'primary'   => array(
				'call'   => $radio['primary']['call'],
				'freq'   => $freq,
				'offset' => $radio['primary']['offset'],
				'tone'   => $radio['primary']['tone'],
			),
			'alternate' => array(
				'freq'   => $radio['alternate']['freq'],
				'offset' => $radio['alternate']['offset'],
				'tone'   => $radio['alternate']['tone'],
				'show'   => $radio['alternate']['show'] ? '1' : '',
			),
		),
		'nets'  => array(
			'net_time'       => $nets['net_time'],
			'gmrs_time'      => $nets['gmrs_time'],
			'winlink_nth'    => $nets['winlink_nth'],
			'simplex_nth'    => $nets['simplex_nth'],
			'gmrs_nth'       => $nets['gmrs_nth'],
			'winlink_howto'  => $nets['winlink_howto'],
			'open_slot_line' => $nets['open_slot_line'],
		),
	);
}

/**
 * The grant field as an administrator sees it on another user's profile.
 *
 * @param string $key Role key of the profile being edited.
 */
function qa002_profile_field( string $key ): string {
	as_role( 'admin' );
	ob_start();
	\spokares_profile_grant_field( get_userdata( user_id( $key ) ) );
	return (string) ob_get_clean();
}

test(
	'control: the granted ARES Editor can save Net details',
	function () {
		as_role( 'ares-net' );
		assert_true( current_user_can( 'spokares_edit_net_details' ), 'granted ARES Editor' );
		$res = post_form( 'spokares_save_net_details', qa002_net_fields( '146.520' ) );
		assert_same( null, $res['die'], 'save refused' );
		assert_contains( 'page=spokares-net-details', (string) $res['redirect'], 'redirect back to the screen' );
		$radio = \spokares_opt( 'spk_radio' );
		assert_same( '146.520', $radio['primary']['freq'], 'frequency saved' );
	}
);

test(
	'changing the granted ARES Editor to Subscriber ends the Net details grant',
	function () {
		qa002_demote( 'ares-net', 'subscriber' );
		assert_false( user_can( user_id( 'ares-net' ), 'spokares_edit_net_details' ), 'user_can after demotion' );
		assert_false( current_user_can( 'spokares_edit_net_details' ), 'current_user_can after demotion' );
	}
);

test(
	'removing the granted ARES Editor\'s role ends the Net details grant',
	function () {
		qa002_demote( 'ares-net', '' );
		assert_same( array(), array_values( wp_get_current_user()->roles ), 'no role' );
		assert_false( current_user_can( 'spokares_edit_net_details' ), 'no role but still granted' );

		$user = new \WP_User( user_id( 'ares-net' ) );
		$user->set_role( 'ares_editor' );
		$user->remove_role( 'ares_editor' );
		clean_user_cache( $user->ID );
		assert_false( user_can( user_id( 'ares-net' ), 'spokares_edit_net_details' ), 'after remove_role( ares_editor )' );
	}
);

test(
	'a demoted ex-editor cannot save Net details (public frequency unchanged)',
	function () {
		$before = \spokares_opt( 'spk_radio' );
		qa002_demote( 'ares-net', 'subscriber' );
		$res   = post_form( 'spokares_save_net_details', qa002_net_fields( '146.520' ) );
		$after = \spokares_opt( 'spk_radio' );
		assert_same( $before['primary']['freq'], $after['primary']['freq'], 'the demoted Subscriber changed the repeater frequency' );
		assert_same( 403, $res['status'], 'save handler should refuse with 403' );
		assert_not_same( null, $res['die'], 'save handler should wp_die()' );
	}
);

test(
	'a role-less ex-editor cannot save Net details',
	function () {
		$before = \spokares_opt( 'spk_radio' );
		qa002_demote( 'ares-net', '' );
		$res   = post_form( 'spokares_save_net_details', qa002_net_fields( '146.520' ) );
		$after = \spokares_opt( 'spk_radio' );
		assert_same( $before['primary']['freq'], $after['primary']['freq'], 'the role-less account changed the repeater frequency' );
		assert_same( 403, $res['status'], 'save handler should refuse with 403' );
	}
);

test(
	'a demoted ex-editor cannot save Meeting rules',
	function () {
		$before = get_option( 'spk_meetings' );
		qa002_demote( 'ares-net', 'subscriber' );
		$res = post_form( 'spokares_save_meeting_rules', array( 'rules' => array() ) );
		assert_same( 403, $res['status'], 'meeting rules save handler should refuse with 403' );
		assert_equals( $before, get_option( 'spk_meetings' ), 'meeting rules changed' );
	}
);

test(
	'demoting through the profile form with the grant box still ticked ends the grant',
	function () {
		// user-edit.php runs edit_user_profile_update (the grant save) and
		// then edit_user() (the role change); an administrator who only
		// changes the Role list leaves the box ticked.
		require_once ABSPATH . 'wp-admin/includes/user.php';
		as_role( 'admin' );
		$id   = user_id( 'ares-net' );
		$user = get_userdata( $id );
		$res  = call_request(
			'POST',
			array(),
			array(
				'spokares_grant_nonce' => wp_create_nonce( 'spokares_grant_' . $id ),
				'spokares_net_details' => '1',
				'role'                 => 'subscriber',
				'email'                => $user->user_email,
				'nickname'             => $user->nickname,
				'display_name'         => $user->display_name,
				'first_name'           => $user->first_name,
				'last_name'            => $user->last_name,
			),
			static function () use ( $id ) {
				\spokares_profile_grant_save( $id );
				return edit_user( $id );
			}
		);
		expect_not_wp_error( $res['returned'], 'edit_user' );
		clean_user_cache( $id );
		assert_contains( 'subscriber', get_userdata( $id )->roles, 'role changed' );
		assert_false( user_can( $id, 'spokares_edit_net_details' ), 'Subscriber kept the grant' );
	}
);

test(
	'whoever can edit Net details is under the two-factor floor and the weekly audit',
	function () {
		// spokares_hard_2fa_missing() and ops/weekly-check.sh only cover
		// users who can edit_posts (§5.4, §5.8), so a grant holder without
		// edit_posts would be a password-only account.
		qa002_demote( 'ares-net', 'subscriber' );
		$audited = array_map(
			'intval',
			get_users(
				array(
					'capability' => 'edit_posts',
					'fields'     => 'ID',
				)
			)
		);
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
			$uid = (int) $uid;
			if ( ! user_can( $uid, 'spokares_edit_net_details' ) ) {
				continue;
			}
			$login = get_userdata( $uid )->user_login;
			assert_true( user_can( $uid, 'edit_posts' ), $login . ' can edit Net details but is below the two-factor floor' );
			assert_contains( $uid, $audited, $login . ' can edit Net details but is missing from the weekly two-factor audit' );
		}
	}
);

test(
	'the grant checkbox is shown on an ARES Editor profile',
	function () {
		assert_contains( 'name="spokares_net_details"', qa002_profile_field( 'ares-editor' ), 'ARES Editor profile' );
	}
);

test(
	'the grant checkbox is not offered on core-role profiles',
	function () {
		foreach ( array( 'subscriber', 'contributor', 'author', 'core-editor' ) as $key ) {
			assert_not_contains( 'name="spokares_net_details"', qa002_profile_field( $key ), $key . ' profile' );
		}
	}
);

test(
	'a crafted grant save on a core-role profile does not give Net details',
	function () {
		foreach ( array( 'subscriber', 'contributor', 'author', 'core-editor' ) as $key ) {
			as_role( 'admin' );
			$id = user_id( $key );
			call_request(
				'POST',
				array(),
				array(
					'spokares_grant_nonce' => wp_create_nonce( 'spokares_grant_' . $id ),
					'spokares_net_details' => '1',
				),
				static function () use ( $id ) {
					\spokares_profile_grant_save( $id );
				}
			);
			clean_user_cache( $id );
			assert_false( user_can( $id, 'spokares_edit_net_details' ), $key . ' was granted Net details' );
		}
	}
);
