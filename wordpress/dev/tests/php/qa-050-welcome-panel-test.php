<?php
/**
 * Regression tests for QA-050 (PLAN §3.4 Dashboard: "Welcome, At a Glance,
 * Activity, Quick Draft and News removed"; admin-trim.php: "Welcome, Quick
 * Draft and News off for all"; §5.7: no push to the Site Editor).
 *
 * The plugin calls remove_action( 'welcome_panel', 'wp_welcome_panel' ) when
 * admin-trim.php is loaded. Plugins load before wp-admin/includes/admin.php,
 * and core only adds that hook in wp-admin/includes/admin-filters.php, so the
 * removal runs first and does nothing. An administrator opening /wp-admin/
 * gets core's "Welcome to WordPress!" panel with its "Open site editor" and
 * "Edit styles" links.
 *
 * Correct behaviour: by the time wp-admin/index.php decides whether to print
 * the panel (has_action( 'welcome_panel' ) && edit_theme_options), core's
 * wp_welcome_panel is no longer hooked, and firing welcome_panel prints no
 * Site Editor links.
 *
 * The tests run in admin-post.php, after the admin includes and admin_init,
 * the same point wp-admin/index.php has reached when it loads. They then play
 * the rest of a Dashboard request up to the panel: the dashboard screen,
 * load-index.php and wp_dashboard_setup(). A removal on any of those hooks
 * counts. Outbound HTTP (the browser and PHP version checks in
 * wp_dashboard_setup()) is answered with an error so the tests stay offline.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Plays a Dashboard request from the point wp-admin/index.php starts (admin
 * includes loaded, admin_init fired) up to where it prints the Welcome panel,
 * for the current user, then puts the screen globals back.
 */
function qa_050_open_dashboard(): void {
	global $pagenow, $hook_suffix, $current_screen;
	$saved = array( $pagenow, $hook_suffix, $current_screen );
	$block = static function () {
		return new \WP_Error( 'qa_050_offline', 'Outbound HTTP is off in this test.' );
	};
	add_filter( 'pre_http_request', $block, 1 );
	try {
		$pagenow     = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated screen, restored below.
		$hook_suffix = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated screen, restored below.
		set_current_screen( 'dashboard' );
		do_action( 'load-index.php' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's screen hook, fired as admin.php does for index.php.
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		wp_dashboard_setup();
	} finally {
		remove_filter( 'pre_http_request', $block, 1 );
		$pagenow        = $saved[0]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		$hook_suffix    = $saved[1]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		$current_screen = $saved[2]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
	}
}

test(
	'the Dashboard shows an administrator no core Welcome panel',
	function () {
		as_role( 'admin' );
		qa_050_open_dashboard();
		assert_true( current_user_can( 'edit_theme_options' ), 'administrators pass the panel\'s capability gate, so only the hook keeps it off' );
		assert_false(
			has_action( 'welcome_panel', 'wp_welcome_panel' ),
			'core\'s wp_welcome_panel is still hooked on welcome_panel when the Dashboard prints it (the file-load remove_action runs before admin-filters.php adds it)'
		);
	}
);

test(
	'the Dashboard welcome_panel hook prints no Site Editor links for an administrator',
	function () {
		as_role( 'admin' );
		qa_050_open_dashboard();
		if ( ! has_action( 'welcome_panel' ) ) {
			assert_false( has_action( 'welcome_panel' ), 'nothing is hooked on welcome_panel' );
			return;
		}
		ob_start();
		do_action( 'welcome_panel' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's hook, fired as wp-admin/index.php does.
		$out = (string) ob_get_clean();
		assert_not_contains( 'Welcome to WordPress', $out, 'the Dashboard prints core\'s "Welcome to WordPress!" panel' );
		assert_not_contains( 'site-editor.php', $out, 'the Dashboard Welcome panel links to the Site Editor ("Open site editor" / "Edit styles")' );
	}
);
