<?php
/**
 * Content surface (§4.1, §5.3): posts are not used. Single posts and post,
 * category, tag, date and author archives are 404; nobody but an
 * administrator can create a post or a page (the six pages are fixed).
 * Attachment pages are 404 too, and never redirect to their file: a file
 * whose document was deleted would otherwise be found by its number
 * (/?attachment_id=N, /?p=N), which undoes the random name (§5.5).
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

/**
 * Only administrators may create posts or pages.
 *
 * @param array  $args      Post type args.
 * @param string $post_type Post type.
 */
function spokares_hard_create_caps( $args, $post_type ) {
	if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
		$args['capabilities']                 = isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array();
		$args['capabilities']['create_posts'] = 'manage_options';
	}
	return $args;
}
add_filter( 'register_post_type_args', 'spokares_hard_create_caps', 10, 2 );

/**
 * 404 for the blog surface.
 */
function spokares_hard_no_blog(): void {
	if ( is_admin() ) {
		return;
	}
	$blog       = is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author() || is_post_type_archive( 'post' ) || ( is_home() && ! is_front_page() );
	$attachment = spokares_hard_is_attachment_request();
	if ( $blog || $attachment ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		if ( $attachment ) {
			// On a 404 WordPress would still send ?p=N or ?attachment_id=N on
			// to the attachment's address, and from there to its file.
			remove_action( 'template_redirect', 'redirect_canonical' );
		}
	}
}
add_action( 'template_redirect', 'spokares_hard_no_blog', 0 );

/**
 * Is this front-end request for an attachment page (by ?attachment_id=,
 * ?attachment=, a ?p= or ?page_id= that names an attachment, or a pretty
 * attachment URL)?
 */
function spokares_hard_is_attachment_request(): bool {
	if ( is_attachment() ) {
		return true;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
	if ( isset( $_GET['attachment_id'] ) || isset( $_GET['attachment'] ) || '' !== (string) get_query_var( 'attachment' ) ) {
		return true;
	}
	foreach ( array( get_queried_object_id(), (int) get_query_var( 'p' ), (int) get_query_var( 'page_id' ), (int) get_query_var( 'attachment_id' ) ) as $id ) {
		if ( $id > 0 && 'attachment' === get_post_type( $id ) ) {
			return true;
		}
	}
	return false;
}

/**
 * No canonical redirect for an attachment: with attachment pages off,
 * WordPress would send /?attachment_id=N (and /?p=N) on to the file itself.
 *
 * @param string|false $redirect_url  Where WordPress would redirect.
 * @param string       $requested_url The requested URL.
 */
function spokares_hard_no_attachment_redirect( $redirect_url, $requested_url ) {
	unset( $requested_url );
	if ( is_admin() || ! is_string( $redirect_url ) || '' === $redirect_url ) {
		return $redirect_url;
	}
	if ( spokares_hard_is_attachment_request() ) {
		return false;
	}
	// A guessed redirect (for a 404) that lands on an attachment or a file.
	$uploads = (string) ( wp_get_upload_dir()['baseurl'] ?? '' );
	if ( '' !== $uploads && str_starts_with( set_url_scheme( $redirect_url ), set_url_scheme( $uploads ) ) ) {
		return false;
	}
	$target = url_to_postid( $redirect_url );
	return $target && 'attachment' === get_post_type( $target ) ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'spokares_hard_no_attachment_redirect', 99, 2 );

/**
 * No REST discovery links (the Link header and <link rel="https://api.w.org/">)
 * for visitors: they advertise /wp-json/wp/v2/pages/N, which visitors can't
 * read anyway (401). Signed-in editors keep them.
 */
function spokares_hard_no_rest_links(): void {
	if ( is_user_logged_in() ) {
		return;
	}
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
}
add_action( 'init', 'spokares_hard_no_rest_links' );
