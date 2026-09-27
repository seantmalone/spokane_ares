<?php
/**
 * No user enumeration (§5.3): REST users routes and the users sitemap gone
 * for visitors, author archives and ?author= 404, oEmbed without the author,
 * feeds off, anonymous /wp/v2/* refused, and a lost-password form that never
 * says whether an account exists (and is throttled, to protect the mail cap).
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

// REST users routes: 404 for anonymous visitors (the block editor, logged in, still works).
add_filter(
	'rest_endpoints',
	static function ( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( str_starts_with( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}
);

/**
 * Anonymous requests to /wp/v2/* get 401 (the front end uses no REST).
 * oEmbed and the Two-Factor routes stay available.
 *
 * @param WP_Error|null|true $result Earlier result.
 */
function spokares_hard_rest_anon( $result ) {
	if ( null !== $result || is_user_logged_in() ) {
		return $result;
	}
	$route = '';
	if ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		$route = (string) $GLOBALS['wp']->query_vars['rest_route'];
	} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$path  = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$pos   = strpos( $path, '/' . rest_get_url_prefix() . '/' );
		$route = false !== $pos ? substr( $path, $pos + strlen( rest_get_url_prefix() ) + 1 ) : '';
	}
	$route = '/' . ltrim( $route, '/' );
	// The users routes are removed for visitors above, so they answer 404.
	if ( str_starts_with( $route, '/wp/v2' ) && ! str_starts_with( $route, '/wp/v2/users' ) ) {
		return new WP_Error( 'rest_login_required', __( 'Sign in to use this.', 'spokares-hardening' ), array( 'status' => 401 ) );
	}
	return $result;
}
add_filter( 'rest_authentication_errors', 'spokares_hard_rest_anon', 50 );

// No users sitemap.
add_filter(
	'wp_sitemaps_add_provider',
	static function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

// Author archives and ?author=N: 404, before canonical redirects can reveal a name.
add_action(
	'template_redirect',
	static function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing.
		if ( is_author() || isset( $_GET['author'] ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	},
	0
);
add_filter(
	'redirect_canonical',
	static function ( $redirect ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing.
		return ( is_author() || isset( $_GET['author'] ) ) ? false : $redirect;
	}
);

// oEmbed responses without the author.
add_filter(
	'oembed_response_data',
	static function ( $data ) {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}
);

// Feeds off: 404, and no feed links in the page head.
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
add_action(
	'template_redirect',
	static function () {
		if ( is_feed() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	},
	0
);
foreach ( array( 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom', 'do_feed_rss2_comments', 'do_feed_atom_comments' ) as $spokares_hard_feed ) {
	remove_all_actions( $spokares_hard_feed );
	add_action(
		$spokares_hard_feed,
		static function () {
			status_header( 404 );
			nocache_headers();
			exit;
		},
		1
	);
}
unset( $spokares_hard_feed );

/**
 * The page that says "check your e-mail", whatever happened.
 */
function spokares_hard_lostpassword_done(): void {
	wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', wp_login_url() ) );
	exit;
}

// Lost password: throttle 5 per IP per hour and 20 per hour site-wide.
add_action(
	'lostpassword_post',
	static function () {
		$ip   = spokares_hard_ip_key( 'reset' );
		$site = 'spk_reset_site';
		$n_ip = (int) get_transient( $ip );
		$n_st = (int) get_transient( $site );
		if ( $n_ip >= 5 || $n_st >= 20 ) {
			spokares_hard_lostpassword_done();
		}
		set_transient( $ip, $n_ip + 1, HOUR_IN_SECONDS );
		set_transient( $site, $n_st + 1, HOUR_IN_SECONDS );
	},
	1
);

// Unknown account: the same "check your e-mail" page as a real one.
add_filter(
	'lostpassword_errors',
	static function ( $errors, $user_data = false ) {
		if ( ! $errors instanceof WP_Error || $errors->get_error_message( 'empty_username' ) ) {
			return $errors;
		}
		if ( ! $user_data || $errors->get_error_message( 'invalid_email' ) || $errors->get_error_message( 'invalidcombo' ) ) {
			spokares_hard_lostpassword_done();
		}
		return $errors;
	},
	10,
	2
);
