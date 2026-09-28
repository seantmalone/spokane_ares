<?php
/**
 * Spokane County ARES-ACS block theme (Option B, "Carry the Message").
 *
 * The theme is the look only. Data, admin screens and the dynamic blocks
 * (spokares/*) live in the spokares-core plugin; security lives in the
 * spokares-hardening must-use plugin. See wordpress/PLAN.md §6.7.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;

define( 'SPOKARES_THEME_VERSION', '0.1.1' );

require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/head.php';
require_once __DIR__ . '/inc/render-filters.php';
