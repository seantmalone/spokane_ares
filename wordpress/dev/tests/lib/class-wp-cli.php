<?php
/**
 * DEV ONLY: a stand-in for WP-CLI's output API, so the PHP tests can call the
 * `wp spokares …` command functions (inc/cli.php) inside the test request,
 * where WP-CLI isn't loaded. Each message is recorded; error() throws (the
 * real one exits), with the code below so a test can tell it from others.
 * Required by the tests that use it, never when the real WP-CLI is loaded;
 * wp-cli-utils.php stands in for WP_CLI\Utils\format_items().
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-cli-utils.php';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- stands in for WP-CLI's own class.
if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * WP_CLI's static output methods, recording what they're given.
	 */
	class WP_CLI {

		/**
		 * The exception code of error().
		 */
		const EXIT_CODE = 7311;

		/**
		 * What was printed: [kind, text] (kind line, log, success, warning,
		 * error; table rows as [table, rows]).
		 *
		 * @var array
		 */
		public static $out = array();

		/**
		 * Print a line.
		 *
		 * @param string $text Text.
		 */
		public static function line( $text = '' ): void {
			self::$out[] = array( 'line', (string) $text );
		}

		/**
		 * Print a message.
		 *
		 * @param string $text Text.
		 */
		public static function log( $text ): void {
			self::$out[] = array( 'log', (string) $text );
		}

		/**
		 * Print "Success: …".
		 *
		 * @param string $text Text.
		 */
		public static function success( $text ): void {
			self::$out[] = array( 'success', (string) $text );
		}

		/**
		 * Print "Warning: …".
		 *
		 * @param string $text Text.
		 */
		public static function warning( $text ): void {
			self::$out[] = array( 'warning', (string) $text );
		}

		/**
		 * Print "Error: …" and stop (here: throw).
		 *
		 * @param string $text Text.
		 * @throws \RuntimeException Always.
		 */
		public static function error( $text ): void {
			self::$out[] = array( 'error', (string) $text );
			throw new \RuntimeException( (string) $text, self::EXIT_CODE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- dev test output, never HTML.
		}

		/**
		 * Commands are registered only by the real WP-CLI.
		 */
		public static function add_command(): void {
		}
	}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
