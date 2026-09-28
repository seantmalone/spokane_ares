<?php
/**
 * Plugin Name:       Spokane ARES core
 * Description:       Events, the Net Control Schedule, meetings, documents and the dynamic blocks for spokares.org. Deactivating it hides the lists; the data stays.
 * Version:           0.2.0
 * Requires at least: 7.1
 * Requires PHP:      8.3
 * Tested up to:      7.1
 * Author:            Spokane County ARES-ACS
 * License:           GPL-2.0-or-later
 * Text Domain:       spokares-core
 * Update URI:        false
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

define( 'SPOKARES_CORE_VERSION', '0.2.0' );
define( 'SPOKARES_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPOKARES_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'SPOKARES_CORE_FILE', __FILE__ );

// Order matters: helpers first, then the data model, then everything that reads it.
require SPOKARES_CORE_DIR . 'inc/helpers.php';
require SPOKARES_CORE_DIR . 'inc/format.php';
require SPOKARES_CORE_DIR . 'inc/guards.php';
require SPOKARES_CORE_DIR . 'inc/data.php';
require SPOKARES_CORE_DIR . 'inc/schedule.php';
require SPOKARES_CORE_DIR . 'inc/roles.php';
require SPOKARES_CORE_DIR . 'inc/blocks.php';
require SPOKARES_CORE_DIR . 'inc/render.php';
require SPOKARES_CORE_DIR . 'inc/routes.php';
require SPOKARES_CORE_DIR . 'inc/files.php';
require SPOKARES_CORE_DIR . 'inc/governance.php';

if ( is_admin() ) {
	require SPOKARES_CORE_DIR . 'inc/admin-common.php';
	require SPOKARES_CORE_DIR . 'inc/admin-trim.php';
	require SPOKARES_CORE_DIR . 'inc/admin-rota.php';
	require SPOKARES_CORE_DIR . 'inc/admin-net.php';
	require SPOKARES_CORE_DIR . 'inc/admin-meetings.php';
	require SPOKARES_CORE_DIR . 'inc/admin-tiles.php';
	require SPOKARES_CORE_DIR . 'inc/admin-site.php';
	require SPOKARES_CORE_DIR . 'inc/admin-events.php';
	require SPOKARES_CORE_DIR . 'inc/admin-documents.php';
	require SPOKARES_CORE_DIR . 'inc/admin-pages.php';
	require SPOKARES_CORE_DIR . 'inc/dashboard.php';
} elseif ( 'wp-login.php' === ( $GLOBALS['pagenow'] ?? '' ) ) {
	// The sign-in screen's seal and home link live with the other admin trims.
	require SPOKARES_CORE_DIR . 'inc/admin-trim.php';
}

// The admin bar menu and the front-end edit links need these on the front end too.
require SPOKARES_CORE_DIR . 'inc/admin-bar.php';

register_activation_hook( __FILE__, 'spokares_activate' );

/**
 * Activation: role and caps, default options (only when absent), post types
 * and the /docs/ route registered directly, then a rewrite flush.
 * Activation runs after `init`, so the registrations are repeated here.
 */
function spokares_activate(): void {
	spokares_register_data_model();
	spokares_add_rewrite_rules();
	spokares_sync_roles();
	spokares_add_default_options();
	// Autoloaded: it is read on every request (spokares_maybe_upgrade).
	update_option( 'spk_core_version', SPOKARES_CORE_VERSION, true );
	flush_rewrite_rules( false );
}

/**
 * Re-sync the role and caps when the plugin version changes (a deploy by rsync
 * never runs the activation hook), and rebuild the rewrite rules, so a deploy
 * without --flush-rewrites can't leave /docs/<slug>/ without its rule. The
 * rules are registered on init at priority 10, before this runs.
 */
function spokares_maybe_upgrade(): void {
	if ( get_option( 'spk_core_version' ) === SPOKARES_CORE_VERSION ) {
		return;
	}
	spokares_sync_roles();
	spokares_add_default_options();
	update_option( 'spk_core_version', SPOKARES_CORE_VERSION, true );
	if ( function_exists( 'wp_set_option_autoload' ) ) {
		wp_set_option_autoload( 'spk_core_version', true );
	}
	flush_rewrite_rules( false );
}
add_action( 'init', 'spokares_maybe_upgrade', 20 );
