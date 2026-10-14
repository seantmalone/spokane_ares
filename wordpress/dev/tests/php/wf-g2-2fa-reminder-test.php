<?php
/**
 * Regression tests for workflow review G2: every new account starts with
 * e-mailed codes as its only second step, so the enforcement floor never
 * fires for it, and moving to the phone app was never asked for. If mail
 * fails, that account can't sign in. Now the Dashboard reminds an account
 * the enforcement covers whose only method is e-mailed codes: a line under
 * the tasks for its first 14 days, then an amber Needs attention line. No
 * one is locked out (mail isn't verified yet; PLAN §5.4).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Give a dev account these two-factor methods.
 *
 * @param int      $id        User.
 * @param string[] $providers Provider classes.
 */
function wfg2_methods( int $id, array $providers ): void {
	update_user_meta( $id, '_two_factor_enabled_providers', $providers );
	update_user_meta( $id, '_two_factor_provider', $providers[0] ?? '' );
	if ( in_array( 'Two_Factor_Totp', $providers, true ) ) {
		update_user_meta( $id, '_two_factor_totp_key', 'ABCDEFGHIJKLMNOP' );
	}
	if ( in_array( 'Two_Factor_Backup_Codes', $providers, true ) ) {
		update_user_meta( $id, '_two_factor_backup_codes', array( wp_hash_password( '12345678' ) ) );
	}
}

/**
 * The Dashboard widget's HTML for the current user.
 */
function wfg2_dashboard(): string {
	ob_start();
	\spokares_dashboard_widget();
	return (string) ob_get_clean();
}

test(
	'e-mailed codes only: a reminder under the tasks for 14 days, then in Needs attention; never a lock-out',
	function () {
		if ( ! class_exists( 'Two_Factor_Core' ) ) {
			skip( 'the Two-Factor plugin is not active' );
		}
		$text = 'Your sign-in codes come only by e-mail, so if e-mail is slow or down you can’t sign in. Set up the phone app, or print backup codes, on your profile.';
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			$id = user_id( $role );
			wfg2_methods( $id, array( 'Two_Factor_Email' ) );
			delete_user_meta( $id, 'spk_first_sign_in' );
			as_role( $role );
			assert_same( 'soon', \spokares_hard_2fa_reminder( $id ), $role . ': just seen' );
			assert_true( (int) get_user_meta( $id, 'spk_first_sign_in', true ) > 0, $role . ': first seen recorded' );
			$html = wfg2_dashboard();
			assert_contains( '<p class="spk-2fa-reminder">' . esc_html( $text ) . ' <a href="' . esc_url( admin_url( 'profile.php#two-factor-options' ) ) . '">Set it up on your profile</a></p>', $html, $role . ': the line under the tasks' );
			assert_not_contains( '<li>' . esc_html( $text ), $html, $role . ': not in Needs attention yet' );

			update_user_meta( $id, 'spk_first_sign_in', time() - 14 * DAY_IN_SECONDS - 60 );
			assert_same( 'due', \spokares_hard_2fa_reminder( $id ), $role . ': after 14 days' );
			$html = wfg2_dashboard();
			assert_matches( '#<div class="spk-attention">.*<li>' . preg_quote( esc_html( $text ), '#' ) . ' <a href="[^"]*profile\.php\#two-factor-options">Set it up on your profile</a></li>#s', $html, $role . ': in Needs attention' );
			assert_not_contains( '<p class="spk-2fa-reminder">', $html, $role . ': once only' );
			assert_false( \spokares_hard_2fa_missing(), $role . ': not sent to the profile (no lock-out)' );
		}
	}
);

test(
	'no reminder with the phone app or backup codes, with no method (the enforcement\'s job), or for an account that can\'t change the site',
	function () {
		if ( ! class_exists( 'Two_Factor_Core' ) ) {
			skip( 'the Two-Factor plugin is not active' );
		}
		$id = user_id( 'ares-editor' );
		update_user_meta( $id, 'spk_first_sign_in', time() - 30 * DAY_IN_SECONDS );
		foreach ( array(
			'phone app'    => array( 'Two_Factor_Email', 'Two_Factor_Totp' ),
			'backup codes' => array( 'Two_Factor_Email', 'Two_Factor_Backup_Codes' ),
			'no method'    => array(),
		) as $what => $providers ) {
			delete_user_meta( $id, '_two_factor_totp_key' );
			delete_user_meta( $id, '_two_factor_backup_codes' );
			wfg2_methods( $id, $providers );
			assert_same( '', \spokares_hard_2fa_reminder( $id ), $what );
		}
		$sub = user_id( 'subscriber' );
		wfg2_methods( $sub, array( 'Two_Factor_Email' ) );
		assert_same( '', \spokares_hard_2fa_reminder( $sub ), 'a Subscriber' );
	}
);

test(
	'the first sign-in is recorded once',
	function () {
		$user = get_userdata( user_id( 'ares-editor' ) );
		delete_user_meta( $user->ID, 'spk_first_sign_in' );
		do_action( 'wp_login', $user->user_login, $user );
		$first = (int) get_user_meta( $user->ID, 'spk_first_sign_in', true );
		assert_true( $first > 0, 'recorded' );
		update_user_meta( $user->ID, 'spk_first_sign_in', 1700000000 );
		do_action( 'wp_login', $user->user_login, $user );
		assert_same( 1700000000, (int) get_user_meta( $user->ID, 'spk_first_sign_in', true ), 'kept' );
	}
);

test(
	'an account with no site tasks that the enforcement still covers (core Author) gets the reminder in its one box, amber after 14 days',
	function () {
		if ( ! class_exists( 'Two_Factor_Core' ) ) {
			skip( 'the Two-Factor plugin is not active' );
		}
		$id = user_id( 'author' );
		wfg2_methods( $id, array( 'Two_Factor_Email' ) );
		update_user_meta( $id, 'spk_first_sign_in', time() );
		as_role( 'author' );
		assert_false( \spokares_has_site_tasks(), 'no site tasks (control)' );
		ob_start();
		\spokares_dashboard_no_tasks();
		assert_contains( '<p class="spk-2fa-reminder">Your sign-in codes come only by e-mail', (string) ob_get_clean(), 'first 14 days' );
		update_user_meta( $id, 'spk_first_sign_in', time() - 15 * DAY_IN_SECONDS );
		ob_start();
		\spokares_dashboard_no_tasks();
		assert_contains( '<p class="spk-2fa-reminder spk-attention">Your sign-in codes come only by e-mail', (string) ob_get_clean(), 'after 14 days' );
	}
);

test(
	'G5: spokares_hard_2fa_user_can_change_site() (the enforcement and the weekly audit) covers edit_posts, the rota and Net details',
	function () {
		$sub = get_userdata( user_id( 'subscriber' ) );
		assert_false( \spokares_hard_2fa_user_can_change_site( $sub->ID ), 'a Subscriber (control)' );
		$sub->add_cap( 'spokares_edit_rota' );
		clean_user_cache( $sub->ID );
		assert_true( \spokares_hard_2fa_user_can_change_site( $sub->ID ), 'with the rota capability' );
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
			$uid  = (int) $uid;
			$want = user_can( $uid, 'edit_posts' ) || user_can( $uid, 'spokares_edit_rota' ) || user_can( $uid, 'spokares_edit_net_details' );
			assert_same( $want, \spokares_hard_2fa_user_can_change_site( $uid ), 'user ' . $uid );
		}
		as_role( 'ares-net' );
		assert_same( \spokares_hard_2fa_user_can_change_site( get_current_user_id() ), \spokares_hard_2fa_can_change_site(), 'the current user\'s question is the same' );
	}
);
