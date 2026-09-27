<?php
/**
 * DEV ONLY: last blueprint step (PLAN.md §6.10 step 8). Runs in its own
 * request after the theme and plugin are active and `init` has run with them:
 *
 *   1. the "editor" test user (role ares_editor, or editor if the plugin is off)
 *   2. seed/import.php in dev mode
 *   3. setup/pages.php (six pages, hero photo, front page)
 *   4. delete the Sample Page, the Privacy Policy draft and "Hello world!"
 *   5. flush rewrite rules
 *
 * Every step logs a line to /spokares-log/setup.log (start.sh mounts it at
 * /tmp/pg-<PORT>-logs/) and a failing step is logged and skipped, so the boot
 * never fails on an incomplete theme or plugin.
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SPOKARES_DEV_IMPORT_LIBRARY' ) ) {
	define( 'SPOKARES_DEV_IMPORT_LIBRARY', true );
}

/**
 * Append lines to the setup log.
 *
 * @param string[] $lines Lines.
 */
function spokares_dev_setup_log( array $lines ): void {
	if ( is_dir( '/spokares-log' ) ) {
		file_put_contents( '/spokares-log/setup.log', implode( "\n", $lines ) . "\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- dev setup log.
	}
}

/**
 * Run one setup step; a thrown error is logged, not fatal.
 *
 * @param string   $name Step name.
 * @param callable $step Returns log lines.
 */
function spokares_dev_setup_step( string $name, callable $step ): void {
	try {
		spokares_dev_setup_log( (array) $step() );
	} catch ( Throwable $e ) {
		spokares_dev_setup_log( array( 'setup: ' . $name . ' FAILED: ' . $e->getMessage() . ' (' . wp_basename( $e->getFile() ) . ':' . $e->getLine() . ')' ) );
	}
}

spokares_dev_setup_log(
	array(
		'setup: WordPress ' . get_bloginfo( 'version' ) . ', PHP ' . PHP_VERSION . ', theme ' . get_stylesheet() . ', spokares-core ' . ( defined( 'SPOKARES_CORE_VERSION' ) ? SPOKARES_CORE_VERSION : 'NOT ACTIVE' ) . ', hardening ' . ( defined( 'SPOKARES_HARDENING_VERSION' ) ? SPOKARES_HARDENING_VERSION : 'NOT LOADED' ) . ', two-factor ' . ( class_exists( 'Two_Factor_Core' ) ? 'active' : 'NOT ACTIVE' ),
	)
);

// Act as the administrator (media sideload, kses as an admin without unfiltered_html).
$spokares_dev_admin = get_user_by( 'login', 'admin' );
if ( $spokares_dev_admin ) {
	wp_set_current_user( $spokares_dev_admin->ID );
}

// 1. The test editor.
spokares_dev_setup_step(
	'editor user',
	static function (): array {
		if ( get_user_by( 'login', 'editor' ) ) {
			return array( 'setup: user editor exists' );
		}
		$role = get_role( 'ares_editor' ) ? 'ares_editor' : 'editor';
		$uid  = wp_insert_user(
			array(
				'user_login'   => 'editor',
				'user_pass'    => 'password',
				'display_name' => 'Test Editor',
				'nickname'     => 'Test Editor',
				'first_name'   => '',
				'user_email'   => 'editor@example.invalid',
				'role'         => $role,
			)
		);
		if ( is_wp_error( $uid ) ) {
			return array( 'setup: user editor FAILED: ' . $uid->get_error_message() );
		}
		return array( 'setup: user editor created (ID ' . $uid . ', role ' . $role . ')' );
	}
);

// 2. The seed, in dev mode.
spokares_dev_setup_step(
	'import',
	static function (): array {
		require_once dirname( __DIR__ ) . '/seed/import.php';
		$result = spokares_dev_import( 'dev' );
		return $result['log'];
	}
);

// 3. The six pages.
spokares_dev_setup_step(
	'pages',
	static function (): array {
		require_once __DIR__ . '/pages.php';
		$result = spokares_dev_setup_pages();
		return $result['log'];
	}
);

// 4. WordPress's sample content (Sample Page, "Hello world!", the Privacy Policy draft).
spokares_dev_setup_step(
	'defaults',
	static function (): array {
		require_once __DIR__ . '/pages.php';
		return spokares_dev_remove_sample_content();
	}
);

// 5. Pretty links and /docs/<slug>/.
spokares_dev_setup_step(
	'rewrite',
	static function (): array {
		flush_rewrite_rules( false );
		return array( 'setup: rewrite rules flushed (' . get_option( 'permalink_structure' ) . ')', 'setup: done' );
	}
);
