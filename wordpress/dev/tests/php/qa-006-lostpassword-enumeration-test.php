<?php
/**
 * Regression tests for QA-006 (PLAN §5.3, no user enumeration): the
 * lost-password form must send every visitor to
 * wp-login.php?checkemail=confirm, whether or not the account exists. The
 * hardening plugin (enumeration.php) sends unknown names and addresses there,
 * but when a real account's reset e-mail can't be sent (an SMTP outage or the
 * ~100/hour mail cap; always on the dev site) core returns an error and
 * wp-login.php shows the form again with "Sign-in failed…" and HTTP 200. An
 * unknown name gets a 302, so the difference tells a visitor which accounts
 * exist. The same happens for any other error core returns for a real
 * account, such as no_password_reset.
 *
 * Each request below runs what wp-login.php (WordPress 7.1) runs for a POST to
 * ?action=lostpassword before it prints anything: login_init,
 * login_form_lostpassword, retrieve_password(), the redirect to
 * checkemail=confirm when that succeeds, and the lost_password action before
 * the form would be shown again. wp_mail() is stubbed with pre_wp_mail, and the
 * lost-password throttle's counters are cleared first so the throttle's own
 * redirect can't hide the result.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * POST the lost-password form as a visitor. Returns call_request()'s result
 * plus 'mailed' (the recipients wp_mail() was asked to send to) and 'errors'
 * (the error codes core would show on the form; empty after a redirect).
 *
 * @param string $user_login What the visitor typed (a username or an e-mail address).
 * @param bool   $mail_works Whether wp_mail() succeeds.
 */
function qa_006_lost_password( string $user_login, bool $mail_works ): array {
	$mailed = array();
	$mailer = static function ( $short_circuit, $atts ) use ( &$mailed, $mail_works ) {
		unset( $short_circuit );
		$mailed[] = is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : (string) $atts['to'];
		return $mail_works;
	};

	// The throttle (5 per IP and 20 site-wide per hour) redirects on its own.
	delete_transient( 'spk_reset_site' );
	if ( function_exists( 'spokares_hard_ip_key' ) ) {
		delete_transient( \spokares_hard_ip_key( 'reset' ) );
	}

	as_anonymous();
	add_filter( 'pre_wp_mail', $mailer, 10, 2 );
	try {
		$res = call_request(
			'POST',
			array( 'action' => 'lostpassword' ),
			array(
				'user_login'  => $user_login,
				'redirect_to' => '',
				'wp-submit'   => 'Get New Password',
			),
			static function () {
				do_action( 'login_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's wp-login.php hook, fired as wp-login.php does.
				do_action( 'login_form_lostpassword' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's wp-login.php hook, fired as wp-login.php does.
				$errors = retrieve_password();
				if ( ! is_wp_error( $errors ) ) {
					wp_safe_redirect( 'wp-login.php?checkemail=confirm' );
					return null;
				}
				do_action( 'lost_password', $errors ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's wp-login.php hook, fired as wp-login.php does.
				// wp-login.php would now print the form again with these errors (HTTP 200).
				return $errors;
			}
		);
	} finally {
		remove_filter( 'pre_wp_mail', $mailer, 10 );
	}
	$res['mailed'] = $mailed;
	$res['errors'] = is_wp_error( $res['returned'] ) ? $res['returned']->get_error_codes() : array();
	return $res;
}

/**
 * Where the lost-password form sends a visitor, or a description of the form
 * shown again instead.
 *
 * @param array $res qa_006_lost_password()'s result.
 */
function qa_006_outcome( array $res ): string {
	if ( null !== $res['redirect'] ) {
		return 'redirect ' . (string) wp_parse_url( $res['redirect'], PHP_URL_QUERY );
	}
	if ( null !== $res['die'] ) {
		return 'wp_die(): ' . $res['die'];
	}
	return 'the form again, with errors: ' . implode( ', ', $res['errors'] );
}

test(
	'control: with mail working, a real account and an unknown name both go to "check your e-mail"',
	function () {
		$unknown = qa_006_lost_password( 'nosuchuser1', true );
		assert_same( 'redirect checkemail=confirm', qa_006_outcome( $unknown ), 'unknown name' );
		assert_same( array(), $unknown['mailed'], 'no mail for an unknown name' );

		$known = qa_006_lost_password( 'editor', true );
		assert_same( 'redirect checkemail=confirm', qa_006_outcome( $known ), 'real account' );
		assert_same( array( get_userdata( user_id( 'ares-editor' ) )->user_email ), $known['mailed'], 'the reset e-mail goes to the account' );
	}
);

test(
	'a real username whose reset e-mail cannot be sent gets the same redirect as an unknown name',
	function () {
		$unknown = qa_006_lost_password( 'nosuchuser1', false );
		assert_same( 'redirect checkemail=confirm', qa_006_outcome( $unknown ), 'unknown name' );

		foreach ( array( 'ares-editor', 'admin' ) as $key ) {
			$user  = get_userdata( user_id( $key ) );
			$known = qa_006_lost_password( $user->user_login, false );
			assert_same( array( $user->user_email ), $known['mailed'], $key . ': the reset e-mail was attempted' );
			assert_same( qa_006_outcome( $unknown ), qa_006_outcome( $known ), $key . ' (' . $user->user_login . '): same answer as an unknown name' );
			assert_same( $unknown['redirect'], $known['redirect'], $key . ': same redirect location' );
		}
	}
);

test(
	'a real e-mail address whose reset e-mail cannot be sent gets the same redirect as an unknown address',
	function () {
		$unknown = qa_006_lost_password( 'nobody-qa006@example.invalid', false );
		assert_same( 'redirect checkemail=confirm', qa_006_outcome( $unknown ), 'unknown address' );

		$user  = get_userdata( user_id( 'ares-editor' ) );
		$known = qa_006_lost_password( $user->user_email, false );
		assert_same( array( $user->user_email ), $known['mailed'], 'the reset e-mail was attempted' );
		assert_same( qa_006_outcome( $unknown ), qa_006_outcome( $known ), 'real address: same answer as an unknown one' );
		assert_same( $unknown['redirect'], $known['redirect'], 'same redirect location' );
	}
);

test(
	'a real account that may not reset its password gets the same redirect as an unknown name',
	function () {
		add_filter( 'allow_password_reset', '__return_false' );
		try {
			$unknown = qa_006_lost_password( 'nosuchuser1', true );
			$known   = qa_006_lost_password( 'editor', true );
		} finally {
			remove_filter( 'allow_password_reset', '__return_false' );
		}
		assert_same( 'redirect checkemail=confirm', qa_006_outcome( $unknown ), 'unknown name' );
		assert_same( qa_006_outcome( $unknown ), qa_006_outcome( $known ), 'real account (no_password_reset): same answer as an unknown name' );
	}
);
