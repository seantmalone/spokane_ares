<?php
/**
 * Render spokares/asof (§6.4). The markup is built from escaped values in
 * inc/render.php and echoed through the block allowlist.
 *
 * @package spokares-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

spokares_block_echo( spokares_render_asof() );
