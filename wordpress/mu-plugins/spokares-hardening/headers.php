<?php
/**
 * Security headers from PHP (§5.3), on the front end, the sign-in page and
 * wp-admin (.htaccess Header rules are ignored on OpenLiteSpeed and Nginx).
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

/**
 * The headers. HSTS starts at one day; set SPOKARES_HSTS_MAX_AGE in
 * wp-config.php to 31536000 after a clean month.
 */
function spokares_hard_security_headers(): array {
	$headers = array(
		'X-Content-Type-Options'  => 'nosniff',
		'Referrer-Policy'         => 'strict-origin-when-cross-origin',
		'X-Frame-Options'         => 'SAMEORIGIN',
		'Permissions-Policy'      => 'camera=(), microphone=(), geolocation=()',
		// Directives that can't break inline block styles or scripts.
		'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'",
	);
	if ( is_ssl() ) {
		$age                                  = defined( 'SPOKARES_HSTS_MAX_AGE' ) ? absint( SPOKARES_HSTS_MAX_AGE ) : DAY_IN_SECONDS;
		$headers['Strict-Transport-Security'] = 'max-age=' . $age;
	}
	return $headers;
}

/**
 * Send them once per request.
 */
function spokares_hard_send_headers(): void {
	static $sent = false;
	if ( $sent || headers_sent() ) {
		return;
	}
	$sent = true;
	// PHP's own "X-Powered-By: PHP/8.3.x" (expose_php) names the version.
	if ( function_exists( 'header_remove' ) ) {
		header_remove( 'X-Powered-By' );
	}
	foreach ( spokares_hard_security_headers() as $name => $value ) {
		header( $name . ': ' . $value );
	}
}
add_action( 'send_headers', 'spokares_hard_send_headers' );
add_action( 'login_init', 'spokares_hard_send_headers' );
add_action( 'admin_init', 'spokares_hard_send_headers' );
// wp-admin sends visitors who aren't signed in away before admin_init runs.
add_action(
	'init',
	static function () {
		if ( is_admin() ) {
			spokares_hard_send_headers();
		}
	},
	0
);
add_filter(
	'rest_pre_serve_request',
	static function ( $served ) {
		spokares_hard_send_headers();
		return $served;
	}
);
