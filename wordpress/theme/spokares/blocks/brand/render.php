<?php
/**
 * Render: spokares-theme/brand (the seal lockup in the header, the seal link in the footer).
 *
 * Header: <a class="brand">seal + "Spokane County / ARES-ACS"</a> (DESIGN.md §6.1).
 * Footer: <a class="footer__seal">seal</a> (DESIGN.md §6.21).
 * The block wrapper attributes sit on the link itself so B's flex rules
 * (.brand { margin-right: auto }) apply without an extra wrapper.
 *
 * @package spokares
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$spokares_variant = ( isset( $attributes['variant'] ) && 'footer' === $attributes['variant'] ) ? 'footer' : 'header';

$spokares_seal_92  = get_theme_file_uri( 'assets/img/seal-ares-acs-92.png' );
$spokares_seal_138 = get_theme_file_uri( 'assets/img/seal-ares-acs-138.png' );
$spokares_srcset   = $spokares_seal_92 . ' 92w, ' . $spokares_seal_138 . ' 138w';

if ( 'footer' === $spokares_variant ) {
	$spokares_wrapper = get_block_wrapper_attributes( array( 'class' => 'footer__seal' ) );
	printf(
		'<a %1$s href="%2$s"><img src="%3$s" srcset="%4$s" sizes="44px" alt="%5$s" width="44" height="44" loading="lazy" decoding="async"></a>',
		$spokares_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes().
		esc_url( home_url( '/' ) ),
		esc_url( $spokares_seal_92 ),
		esc_attr( $spokares_srcset ),
		esc_attr__( 'Spokane County ARES-ACS home', 'spokares' )
	);
	return;
}

$spokares_wrapper = get_block_wrapper_attributes( array( 'class' => 'brand' ) );
printf(
	'<a %1$s href="%2$s"><img src="%3$s" srcset="%4$s" sizes="46px" alt="" width="46" height="46" decoding="async"><span class="brand__name"><span>%5$s</span> <strong>%6$s</strong></span></a>',
	$spokares_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes().
	esc_url( home_url( '/' ) ),
	esc_url( $spokares_seal_92 ),
	esc_attr( $spokares_srcset ),
	esc_html__( 'Spokane County', 'spokares' ),
	esc_html__( 'ARES-ACS', 'spokares' )
);
