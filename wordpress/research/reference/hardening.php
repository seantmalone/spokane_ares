<?php
/**
 * Hardening defaults for spokares.org (reference for spokares-core/includes/hardening.php).
 *
 * Server-independent: everything here is PHP, so it holds on Apache, LiteSpeed,
 * OpenLiteSpeed or Nginx alike.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

// 1. XML-RPC: nothing on this site uses it. Refuse the whole endpoint, not just
// authenticated methods (the xmlrpc_enabled filter leaves pingbacks and
// system.multicall reachable).
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
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

// 2. Application passwords: no integration needs REST Basic Auth.
add_filter( 'wp_is_application_passwords_available', '__return_false' );

// 3. No comments or pings anywhere.
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );

// 4. No user enumeration for anonymous visitors.
add_filter(
	'rest_endpoints',
	static function ( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}
		return $endpoints;
	}
);
add_filter(
	'wp_sitemaps_add_provider',
	static function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);
add_action(
	'template_redirect',
	static function () {
		if ( is_author() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	},
	0
);
add_filter(
	'login_errors',
	static function () {
		return esc_html__( 'Sign-in failed. Check your details and try again.', 'spokares-core' );
	}
);

// 5. Uploads: PDFs and web images only. No SVG, HTML, Office macros or archives.
add_filter(
	'upload_mimes',
	static function () {
		return array(
			'pdf'          => 'application/pdf',
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		);
	}
);

// 6. Security headers, sent from PHP because .htaccess Header rules are ignored
// on OpenLiteSpeed and Nginx.
function spokares_security_headers() {
	$headers = array(
		'X-Content-Type-Options'  => 'nosniff',
		'Referrer-Policy'         => 'strict-origin-when-cross-origin',
		'X-Frame-Options'         => 'SAMEORIGIN',
		'Permissions-Policy'      => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
		// Directives that cannot break inline block styles or scripts.
		'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'",
	);
	if ( is_ssl() ) {
		// Start low; raise to 31536000 after a month without HTTPS problems.
		$headers['Strict-Transport-Security'] = 'max-age=86400';
	}
	return $headers;
}
add_action(
	'send_headers',
	static function () {
		foreach ( spokares_security_headers() as $name => $value ) {
			header( $name . ': ' . $value );
		}
	}
);

// 7. Login throttle: 10 failures per IP in 15 minutes locks that IP for 15 minutes.
// REMOTE_ADDR only; never trust X-Forwarded-For (no proxy sits in front).
function spokares_login_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'none';
	return 'spokares_login_' . md5( $ip . wp_salt( 'auth' ) );
}
add_action(
	'wp_login_failed',
	static function () {
		$key = spokares_login_key();
		set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
	}
);
add_filter(
	'authenticate',
	static function ( $user ) {
		if ( (int) get_transient( spokares_login_key() ) >= 10 ) {
			return new WP_Error( 'spokares_locked', __( 'Too many failed sign-ins. Wait 15 minutes.', 'spokares-core' ) );
		}
		return $user;
	},
	99
);

// 8. Two-factor is mandatory for anyone who can edit content (needs the
// Two-Factor plugin). Until it is set up, every admin screen except the
// profile redirects to the profile's Two-Factor section.
add_action(
	'admin_init',
	static function () {
		if ( ! class_exists( 'Two_Factor_Core' ) || wp_doing_ajax() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		if ( Two_Factor_Core::is_user_using_two_factor( get_current_user_id() ) ) {
			return;
		}
		global $pagenow;
		if ( 'profile.php' !== $pagenow ) {
			wp_safe_redirect( admin_url( 'profile.php#two-factor-options' ) );
			exit;
		}
	}
);

// 9. Do not advertise the WordPress version in page source.
remove_action( 'wp_head', 'wp_generator' );
