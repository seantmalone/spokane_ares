<?php
/**
 * Stable document links: /docs/<slug>/ (§5.5, §6.5).
 *
 * A 302 to the file or the linked page only when the document is published,
 * privacy-checked and has a file (upload) or an https link. Anything else,
 * drafts and "Soon" items included, is a 404 through the theme's 404 template.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The rewrite rule.
 */
function spokares_add_rewrite_rules(): void {
	add_rewrite_rule( '^docs/([a-z0-9-]+)/?$', 'index.php?spk_doc=$matches[1]', 'top' );
}
add_action( 'init', 'spokares_add_rewrite_rules' );

/**
 * Register the query var.
 *
 * @param string[] $vars Query vars.
 */
function spokares_query_vars( $vars ) {
	$vars[] = 'spk_doc';
	return $vars;
}
add_filter( 'query_vars', 'spokares_query_vars' );

/**
 * Where /docs/<slug>/ may send a visitor, or '' (404).
 *
 * @param string $slug Document slug.
 */
function spokares_doc_target( string $slug ): string {
	$slug = sanitize_title( $slug );
	if ( '' === $slug ) {
		return '';
	}
	$posts = get_posts(
		array(
			'post_type'        => 'spk_document',
			'post_status'      => 'publish',
			'name'             => $slug,
			'numberposts'      => 1,
			'suppress_filters' => false,
		)
	);
	$doc   = $posts ? spokares_public_document( (int) $posts[0]->ID ) : null;
	if ( ! $doc ) {
		return '';
	}
	$source = (string) get_post_meta( $doc->ID, 'spk_source', true );
	if ( 'upload' === $source ) {
		$file = absint( get_post_meta( $doc->ID, 'spk_file', true ) );
		$url  = $file ? (string) wp_get_attachment_url( $file ) : '';
		// Our own uploads: the URL is built by WordPress, never taken from the request.
		$base = wp_upload_dir( null, false )['baseurl'] ?? '';
		return ( '' !== $url && '' !== $base && str_starts_with( set_url_scheme( $url ), set_url_scheme( $base ) ) ) ? $url : '';
	}
	if ( 'link' === $source ) {
		$url = spokares_clean_url( (string) get_post_meta( $doc->ID, 'spk_url', true ) );
		return str_starts_with( $url, 'https://' ) ? $url : '';
	}
	return '';
}

/**
 * The slug of a /docs/<slug>/ request typed with capitals (/docs/ICS-213/),
 * which the lowercase rewrite rule doesn't match, or ''. Slugs are lowercase,
 * so the link works however it is typed. Read from the request WordPress
 * already parsed (and 404ed), so it needs no rewrite flush.
 */
function spokares_doc_slug_any_case(): string {
	global $wp;
	if ( ! is_404() || ! ( $wp instanceof WP ) ) {
		return '';
	}
	if ( ! preg_match( '#^docs/([A-Za-z0-9-]+)/?$#', (string) $wp->request, $m ) || ! preg_match( '/[A-Z]/', $m[1] ) ) {
		return '';
	}
	return strtolower( $m[1] );
}

/**
 * Handle /docs/<slug>/.
 */
function spokares_doc_redirect(): void {
	$slug = get_query_var( 'spk_doc' );
	if ( ! is_string( $slug ) || '' === $slug ) {
		$slug = spokares_doc_slug_any_case();
	}
	if ( '' === $slug ) {
		return;
	}
	$target = spokares_doc_target( $slug );
	if ( '' !== $target ) {
		nocache_headers();
		if ( spokares_is_external( $target ) ) {
			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- stored https link of a published, privacy-checked document; never from the request.
			wp_redirect( $target, 302, 'spokares' );
		} else {
			wp_safe_redirect( $target, 302, 'spokares' );
		}
		exit;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'spokares_doc_redirect', 1 );
