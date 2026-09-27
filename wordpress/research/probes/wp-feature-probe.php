<?php
/**
 * Server-side probes behind wordpress/research/architecture.md (no port, no PHP install).
 * Run:
 *   npx -y @wp-playground/cli@3.1.55 php \
 *     --mount=/Users/sean/Projects/spokane_ares/wordpress/research/probes:/test -- /test/wp-feature-probe.php
 * Each line prints PASS/INFO so a maintainer can re-check after a WordPress major update.
 */
require '/wordpress/wp-load.php';
$reg = WP_Block_Type_Registry::get_instance();
function out( $label, $value ) { echo str_pad( $label, 58 ), ' ', $value, "\n"; }

out( 'WordPress / PHP', get_bloginfo( 'version' ) . ' / ' . PHP_VERSION );

// 1. PHP-only block (7.0 autoRegister) from block.json + render.php: no JS, no build.
$toc = register_block_type( '/test/blk/toc' );
out( '1 block.json autoRegister block registered', ( $toc && ! empty( $toc->supports['autoRegister'] ) && is_callable( $toc->render_callback ) ) ? 'PASS' : 'FAIL' );

// 2. Server-rendered TOC from the page's own H2 anchors (nested in a group).
$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'About', 'post_content' =>
	'<!-- wp:heading {"anchor":"ares-acs"} --><h2 class="wp-block-heading" id="ares-acs">ARES and ACS</h2><!-- /wp:heading -->'
	. '<!-- wp:group --><div class="wp-block-group"><!-- wp:heading {"anchor":"legal-basis"} --><h2 class="wp-block-heading" id="legal-basis">The legal basis</h2><!-- /wp:heading --></div><!-- /wp:group -->' ) );
$blk  = new WP_Block( array( 'blockName' => 'spk/toc', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ), array( 'postId' => $id ) );
$html = $blk->render();
out( '2 TOC block lists both H2 anchors', ( strpos( $html, '#ares-acs' ) && strpos( $html, '#legal-basis' ) ) ? 'PASS' : 'FAIL' );

// 3. Custom Block Bindings source renders on the front end.
register_block_bindings_source( 'spokares/fact', array(
	'label'              => 'Site facts',
	'get_value_callback' => function ( $args ) { $f = array( 'repeater' => 'W7GBU 147.300 MHz, +600 kHz, 100 Hz' ); return $f[ $args['key'] ] ?? null; },
) );
$bound = do_blocks( '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"spokares/fact","args":{"key":"repeater"}}}}} --><p>fallback</p><!-- /wp:paragraph -->' );
out( '3 custom bindings source replaces paragraph content', strpos( $bound, '147.300' ) ? 'PASS' : 'FAIL' );
out( '3b bindable attrs: list-item / table', wp_json_encode( get_block_bindings_supported_attributes( 'core/list-item' ) ) . ' / ' . wp_json_encode( get_block_bindings_supported_attributes( 'core/table' ) ) );

// 4. Template hierarchy filter auto-assigns a block template (members pages).
$parent = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'For members', 'post_name' => 'members' ) );
add_filter( 'page_template_hierarchy', function ( $t ) { array_unshift( $t, 'page-no-title.php' ); return $t; } );
$tpl = resolve_block_template( 'page', apply_filters( 'page_template_hierarchy', array( 'page.php' ) ), '' );
out( '4 hierarchy filter picks block template (TT5 stand-in)', ( $tpl && 'page-no-title' === $tpl->slug ) ? 'PASS' : 'FAIL' );

// 5. core/table "stack" style: data-label injected in PHP (no JS).
add_filter( 'render_block_core/table', function ( $html, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'is-style-stack' ) || ! preg_match( '#<thead>(.*?)</thead>#s', $html, $m ) ) { return $html; }
	preg_match_all( '#<th[^>]*>(.*?)</th>#s', $m[1], $th );
	$labels = array_map( function ( $x ) { return trim( wp_strip_all_tags( $x ) ); }, $th[1] );
	$p = new WP_HTML_Tag_Processor( $html ); $i = 0;
	while ( $p->next_tag() ) {
		if ( $p->is_tag_closer() ) { continue; }
		if ( 'TR' === $p->get_tag() ) { $i = 0; }
		if ( 'TD' === $p->get_tag() ) { if ( isset( $labels[ $i ] ) ) { $p->set_attribute( 'data-label', $labels[ $i ] ); } $i++; }
	}
	return $p->get_updated_html();
}, 10, 2 );
$t = do_blocks( '<!-- wp:table {"className":"is-style-stack"} --><figure class="wp-block-table is-style-stack"><table><thead><tr><th>Role</th><th>Call sign</th></tr></thead><tbody><tr><td>Net Manager</td><td>AG7QP</td></tr></tbody></table></figure><!-- /wp:table -->' );
out( '5 table stack data-label injected server-side', strpos( $t, 'data-label="Call sign"' ) ? 'PASS' : 'FAIL' );

// 6. Which core blocks stay editable under contentOnly ("role":"content" attributes).
foreach ( array( 'paragraph', 'heading', 'list-item', 'button', 'image', 'cover', 'table', 'details', 'quote' ) as $b ) {
	$j = json_decode( file_get_contents( ABSPATH . WPINC . "/blocks/$b/block.json" ), true ); $r = array();
	foreach ( ( $j['attributes'] ?? array() ) as $k => $a ) { if ( 'content' === ( $a['role'] ?? '' ) ) { $r[] = $k; } }
	out( "6 content-role attrs core/$b", implode( ',', $r ) ?: '-' );
}

// 7. Registry and role facts the architecture relies on.
out( '7 core/table-of-contents in core', $reg->is_registered( 'core/table-of-contents' ) ? 'yes' : 'no (experimental, Gutenberg only)' );
out( '7 core/breadcrumbs, core/details, core/accordion', implode( ',', array_map( function ( $b ) use ( $reg ) { return $reg->is_registered( "core/$b" ) ? 'yes' : 'no'; }, array( 'breadcrumbs', 'details', 'accordion' ) ) ) );
$ed = get_role( 'editor' );
foreach ( array( 'edit_theme_options', 'edit_css', 'unfiltered_html', 'manage_options' ) as $c ) { out( "7 Editor role has $c", $ed->has_cap( $c ) ? 'yes' : 'no' ); }
$nav = json_decode( file_get_contents( ABSPATH . WPINC . '/blocks/navigation/block.json' ), true );
out( '7 navigation overlayMenu default', $nav['attributes']['overlayMenu']['default'] );
preg_match_all( '/@media \(min-width: (\d+)px\)/', file_get_contents( ABSPATH . WPINC . '/blocks/navigation/style.css' ), $mq );
out( '7 navigation style.css min-width breakpoints', implode( ',', array_unique( $mq[1] ) ) . 'px' );
