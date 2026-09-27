<?php
/**
 * Hardening (§5.3; from the tested research/reference/hardening.php):
 * XML-RPC refused, application passwords off, comments and pings closed,
 * generic login errors, a login throttle, no generator tag.
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

// 1. XML-RPC: nothing on this site uses it. Refuse the whole endpoint (the
// xmlrpc_enabled filter alone leaves pingbacks and system.multicall open).
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo 'XML-RPC is off.';
	exit;
}
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter(
	'wp_headers',
	static function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
remove_action( 'wp_head', 'rsd_link' );

// 2. Application passwords: no integration needs REST Basic Auth.
add_filter( 'wp_is_application_passwords_available', '__return_false' );

// 3. No comments or pings anywhere.
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );
add_filter( 'comments_array', '__return_empty_array' );

// 4. Generic login errors.
add_filter(
	'login_errors',
	static function () {
		return esc_html__( 'Sign-in failed. Check your details and try again.', 'spokares-hardening' );
	}
);

/**
 * The throttle key for this visitor (REMOTE_ADDR only; no proxy sits in front,
 * so X-Forwarded-For is never trusted).
 *
 * @param string $what Which counter.
 */
function spokares_hard_ip_key( string $what ): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'none';
	return 'spk_' . $what . '_' . md5( $ip . wp_salt( 'auth' ) );
}

// 5. Login throttle: 10 failures per IP in 15 minutes lock that IP for 15 minutes.
add_action(
	'wp_login_failed',
	static function () {
		$key = spokares_hard_ip_key( 'login' );
		set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
	}
);
add_filter(
	'authenticate',
	static function ( $user ) {
		if ( (int) get_transient( spokares_hard_ip_key( 'login' ) ) >= 10 ) {
			return new WP_Error( 'spokares_locked', __( 'Too many failed sign-ins. Wait 15 minutes.', 'spokares-hardening' ) );
		}
		return $user;
	},
	99
);

// 6. Do not advertise the WordPress version.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
