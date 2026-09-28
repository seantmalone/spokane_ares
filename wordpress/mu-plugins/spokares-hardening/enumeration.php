<?php
/**
 * No user enumeration (§5.3): REST users routes and the users sitemap gone
 * for visitors, and signed-in users without list_users see only their own
 * account there; author archives and ?author= 404, oEmbed without the
 * author, feeds off, anonymous /wp/v2/* refused, and a lost-password form
 * that never says whether an account exists (and is throttled, to protect
 * the mail cap). The remote Pattern Directory route is gone too (§4.3).
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST routes that are gone: the users routes for anonymous visitors (the
 * block editor, signed in, still works), and for everyone the remote Pattern
 * Directory (§4.3 layer 5: every pattern on this site is the theme's own).
 * The theme's should_load_remote_block_patterns only keeps WordPress.org
 * patterns out of the editor; the route itself made the server fetch them
 * from api.wordpress.org for any account with edit_posts.
 *
 * @param array $endpoints Route => handlers.
 */
function spokares_hard_rest_endpoints( $endpoints ) {
	if ( ! is_array( $endpoints ) ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( str_starts_with( $route, '/wp/v2/pattern-directory' ) || ( ! is_user_logged_in() && str_starts_with( $route, '/wp/v2/users' ) ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'spokares_hard_rest_endpoints' );

/**
 * Signed in without list_users (everyone but administrators): the users
 * collection lists at most your own account, whatever the query (search,
 * slug, include, who=authors). Core lists every account with a published
 * post, the administrator's login slug included.
 *
 * @param array $args WP_User_Query arguments.
 */
function spokares_hard_rest_user_query( $args ) {
	if ( ! is_array( $args ) || current_user_can( 'list_users' ) ) {
		return $args;
	}
	$me              = get_current_user_id();
	$asked           = isset( $args['include'] ) ? wp_parse_id_list( $args['include'] ) : array();
	$args['include'] = ( ! $asked || in_array( $me, $asked, true ) ) && $me ? array( $me ) : array( 0 );
	return $args;
}
add_filter( 'rest_user_query', 'spokares_hard_rest_user_query' );

/**
 * ...and another account can't be read by its ID (GET /wp/v2/users/N),
 * which also keeps a page's embedded author empty. /wp/v2/users/me stays.
 *
 * One narrow exception: the block editor, as it opens a page, asks for the
 * page author's display name only (_fields=id,name, view context). Someone
 * who edits other people's pages already sees that name in the Pages list,
 * so that exact request is answered for the author of published pages;
 * the slug, links and everything else stay out of reach.
 *
 * @param mixed           $response Response so far (null to go on).
 * @param array           $handler  Route handler.
 * @param WP_REST_Request $request  Request.
 */
function spokares_hard_rest_other_user( $response, $handler, $request ) {
	unset( $handler );
	if ( null !== $response || ! $request instanceof WP_REST_Request || ! in_array( $request->get_method(), array( 'GET', 'HEAD' ), true ) ) {
		return $response;
	}
	if ( ! preg_match( '#^/wp/v2/users/(\\d+)$#', (string) $request->get_route(), $m ) ) {
		return $response;
	}
	$user_id = (int) $m[1];
	if ( get_current_user_id() === $user_id || current_user_can( 'list_users' ) ) {
		return $response;
	}
	$fields = wp_parse_list( (string) $request->get_param( '_fields' ) );
	$view   = 'view' === ( $request->get_param( 'context' ) ?? 'view' );
	if ( $fields && $view && ! array_diff( $fields, array( 'id', 'name' ) ) && current_user_can( 'edit_others_pages' ) && count_user_posts( $user_id, 'page', true ) > 0 ) {
		return $response;
	}
	return new WP_Error( 'rest_user_cannot_view', __( 'Sorry, you are not allowed to see this account.', 'spokares-hardening' ), array( 'status' => rest_authorization_required_code() ) );
}
add_filter( 'rest_request_before_callbacks', 'spokares_hard_rest_other_user', 10, 3 );

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

/**
 * A real account whose reset can't go out (the e-mail failed during an SMTP
 * outage or at the hourly mail cap, or the account may not reset its
 * password) also gets "check your e-mail". Otherwise wp-login.php showed the
 * form again (HTTP 200) where an unknown name got the redirect, which told a
 * visitor the account exists. Only a POST of the form: the form's own GET
 * errors (an expired or invalid reset link) are shown as usual, and so is
 * an empty box.
 *
 * @param WP_Error $errors Errors the form would show.
 */
function spokares_hard_lostpassword_failed( $errors ): void {
	if ( ! $errors instanceof WP_Error || ! $errors->has_errors() || $errors->get_error_message( 'empty_username' ) ) {
		return;
	}
	if ( 'POST' !== strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
		return;
	}
	spokares_hard_lostpassword_done();
}
add_action( 'lost_password', 'spokares_hard_lostpassword_failed' );
