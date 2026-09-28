<?php
/**
 * Content surface (§4.1, §5.3): posts are not used. Single posts and post,
 * category, tag, date and author archives are 404; nobody but an
 * administrator can create a post or a page (the six pages are fixed), a
 * synced pattern, or a category, tag or pattern category.
 * Attachment pages are 404 too, and never redirect to their file: a file
 * whose document was deleted would otherwise be found by its number
 * (/?attachment_id=N, /?p=N), which undoes the random name (§5.5). Links to
 * an attachment page point at the file instead.
 * An unknown address is a 404 however deep it is, and so is "page 2" of a
 * single page (§5.6: old and spam URLs never land on a real page).
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

/**
 * Only administrators may create posts or pages. Synced patterns (wp_block)
 * are administrators' only altogether: core lets anyone who can publish
 * posts create and publish one, lets its author (or anyone with
 * edit_others_posts) change it later, and opens the Patterns list to anyone
 * with edit_posts. A pattern an administrator inserts into a page would then
 * change round the layout lock (§4.3 layer 6). "read" stays as core has it,
 * so the block editor can still show a pattern already on a page.
 *
 * @param array  $args      Post type args.
 * @param string $post_type Post type.
 */
function spokares_hard_create_caps( $args, $post_type ) {
	if ( ! is_array( $args ) ) {
		return $args;
	}
	$caps = isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array();
	if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
		$caps['create_posts'] = 'manage_options';
	} elseif ( 'wp_block' === $post_type ) {
		foreach ( array( 'create_posts', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts', 'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts', 'read_private_posts' ) as $cap ) {
			$caps[ $cap ] = 'manage_options';
		}
	} else {
		return $args;
	}
	$args['capabilities'] = $caps;
	return $args;
}
add_filter( 'register_post_type_args', 'spokares_hard_create_caps', 10, 2 );

/**
 * Categories, tags and pattern categories are administrators' only (§4.1).
 * Core lets anyone with edit_posts create a tag or a pattern category over
 * REST (assign_terms is enough for a flat taxonomy), and WordPress's own
 * Editor role manages categories and tags. Nothing on the site uses them.
 * Assigning a category stays as core has it (edit_posts): creating one
 * needs edit_terms, and the block editor reads the taxonomy list in the edit
 * context only while some taxonomy is assignable (else a 403 on every page
 * it opens).
 *
 * @param array  $args     Taxonomy args.
 * @param string $taxonomy Taxonomy.
 */
function spokares_hard_term_caps( $args, $taxonomy ) {
	if ( ! is_array( $args ) || ! in_array( $taxonomy, array( 'category', 'post_tag', 'wp_pattern_category' ), true ) ) {
		return $args;
	}
	$caps = array(
		'manage_terms' => 'manage_options',
		'edit_terms'   => 'manage_options',
		'delete_terms' => 'manage_options',
	);
	if ( 'category' !== $taxonomy ) {
		$caps['assign_terms'] = 'manage_options';
	}
	$args['capabilities'] = array_merge( isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array(), $caps );
	return $args;
}
add_filter( 'register_taxonomy_args', 'spokares_hard_term_caps', 10, 2 );

/**
 * The block editor asks for the synced patterns (GET /wp/v2/blocks in the
 * edit context) to fill its inserter. Non-administrators may no longer list
 * them, so they get an empty list instead of a 403 in the console.
 *
 * @param mixed           $result  Response so far (null).
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 */
function spokares_hard_no_pattern_list( $result, $server, $request ) {
	unset( $server );
	if ( null !== $result || ! $request instanceof WP_REST_Request || 'GET' !== $request->get_method() || '/wp/v2/blocks' !== $request->get_route() ) {
		return $result;
	}
	$type = get_post_type_object( 'wp_block' );
	if ( ! is_user_logged_in() || ! $type || current_user_can( $type->cap->edit_posts ) ) {
		return $result;
	}
	$response = new WP_REST_Response( array(), 200 );
	$response->header( 'X-WP-Total', '0' );
	$response->header( 'X-WP-TotalPages', '0' );
	return $response;
}
add_filter( 'rest_pre_dispatch', 'spokares_hard_no_pattern_list', 10, 3 );

/**
 * An address no rewrite rule matches is a 404, however many segments it
 * has. WordPress sets the 404 itself, but then drops it when the request
 * looks like a request "for itself" (PHP_SELF equal to the path, as some
 * front controllers, Playground among them, set it): the empty query became
 * the front page and redirect_canonical() sent a permanent 301 to Home.
 *
 * @param array $query_vars Parsed query variables.
 */
function spokares_hard_unmatched_is_404( $query_vars ) {
	global $wp;
	if ( ! is_array( $query_vars ) || is_admin() || isset( $query_vars['error'] ) || ! $wp instanceof WP ) {
		return $query_vars;
	}
	if ( '' === (string) $wp->request || ! empty( $wp->matched_rule ) || ! get_option( 'permalink_structure' ) ) {
		return $query_vars;
	}
	$query_vars['error'] = '404';
	return $query_vars;
}
add_filter( 'request', 'spokares_hard_unmatched_is_404' );

/**
 * 404 for the blog surface.
 */
function spokares_hard_no_blog(): void {
	if ( is_admin() ) {
		return;
	}
	$blog       = is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author() || is_post_type_archive( 'post' ) || ( is_home() && ! is_front_page() );
	$attachment = spokares_hard_is_attachment_request();
	$paged      = spokares_hard_is_paged_single();
	if ( $blog || $attachment || $paged ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		if ( $attachment || $paged ) {
			// On a 404 WordPress would still send ?p=N or ?attachment_id=N on
			// to the attachment's address, and from there to its file, and
			// /?paged=2 on to /page/2/.
			remove_action( 'template_redirect', 'redirect_canonical' );
		}
	}
}
add_action( 'template_redirect', 'spokares_hard_no_blog', 0 );

/**
 * Is this "page N" (N > 1) of a single page or of the front page? None of
 * the pages is split with <!--nextpage-->, so /about/page/2/, /page/2/ and
 * /?page_id=N&paged=2 would be 200 copies of the page (WordPress only checks
 * <!--nextpage--> splits), and the front page's copy had a canonical link to
 * /2/, which is a 404.
 */
function spokares_hard_is_paged_single(): bool {
	if ( ! is_singular() ) {
		return false;
	}
	if ( (int) get_query_var( 'paged' ) > 1 ) {
		return true;
	}
	// For a static front page WordPress moves "paged" into "page".
	$post = get_queried_object();
	return (int) get_query_var( 'page' ) > 1 && $post instanceof WP_Post && ! str_contains( $post->post_content, '<!--nextpage-->' );
}

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

/**
 * Attachment pages are 404 (above), so a link to one (the Media Library's
 * View, the Edit Media screen's permalink, "Link to attachment page") goes
 * to the file itself.
 *
 * @param string $link    Attachment page URL.
 * @param int    $post_id Attachment.
 */
function spokares_hard_attachment_link( $link, $post_id ) {
	$file = wp_get_attachment_url( (int) $post_id );
	return $file ? $file : $link;
}
add_filter( 'attachment_link', 'spokares_hard_attachment_link', 10, 2 );
