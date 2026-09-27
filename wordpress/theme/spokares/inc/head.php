<?php
/**
 * <head> additions: font preloads, favicon fallback, meta description.
 * PLAN.md §6.7 filters 5 and 7.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preload the two roman woff2 files: the Home H1 (Crimson Pro) and body text
 * (Schibsted Grotesk) use them on first paint. The @font-face rules come from
 * theme.json; the URLs here must match those files exactly.
 */
function spokares_theme_preload_fonts(): void {
	foreach ( array( 'crimson-pro-roman.woff2', 'schibsted-grotesk-roman.woff2' ) as $file ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/' . $file ) )
		);
	}
}
add_action( 'wp_head', 'spokares_theme_preload_fonts', 1 );

/**
 * Favicon fallback: the seal, until an administrator sets a Site Icon.
 */
function spokares_theme_favicon(): void {
	if ( has_site_icon() ) {
		return;
	}
	printf(
		'<link rel="icon" href="%s" type="image/png">' . "\n",
		esc_url( get_theme_file_uri( 'assets/img/seal-ares-acs-138.png' ) )
	);
}
add_action( 'wp_head', 'spokares_theme_favicon', 5 );

/**
 * <meta name="description"> from the page's excerpt, when one is set
 * (the copy files' "Meta description" lines are imported as excerpts).
 */
function spokares_theme_meta_description(): void {
	if ( ! is_singular( 'page' ) && ! is_front_page() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post || '' === trim( $post->post_excerpt ) ) {
		return;
	}
	$description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $post->post_excerpt ) ) );
	if ( '' === $description ) {
		return;
	}
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
}
add_action( 'wp_head', 'spokares_theme_meta_description', 2 );
