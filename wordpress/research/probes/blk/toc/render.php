<?php
$post = get_post( $block->context['postId'] ?? get_the_ID() );
$items = array();
$walk = function( $blocks ) use ( &$walk, &$items ) {
  foreach ( $blocks as $b ) {
    if ( 'core/heading' === $b['blockName'] && ( $b['attrs']['level'] ?? 2 ) === 2 ) {
      if ( preg_match( '/id="([^"]+)"/', $b['innerHTML'], $m ) ) $items[] = array( $m[1], trim( wp_strip_all_tags( $b['innerHTML'] ) ) );
    }
    if ( ! empty( $b['innerBlocks'] ) ) $walk( $b['innerBlocks'] );
  }
};
$walk( parse_blocks( $post ? $post->post_content : '' ) );
echo '<nav ' . get_block_wrapper_attributes( array( 'class' => 'toc' ) ) . ' aria-label="' . esc_attr( $attributes['title'] ) . '"><ol>';
foreach ( $items as $i ) printf( '<li><a href="#%s">%s</a></li>', esc_attr( $i[0] ), esc_html( $i[1] ) );
echo '</ol></nav>';
