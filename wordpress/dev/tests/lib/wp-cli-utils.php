<?php
/**
 * DEV ONLY: a stand-in for WP_CLI\Utils\format_items(), recording a table's
 * rows in WP_CLI::$out (class-wp-cli.php requires it).
 *
 * @package spokares-dev
 */

namespace WP_CLI\Utils; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- stands in for WP-CLI's own namespace.

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( __NAMESPACE__ . '\format_items' ) ) {
	/**
	 * Record a table's rows.
	 *
	 * @param string $format Format.
	 * @param array  $items  Rows.
	 * @param array  $fields Columns.
	 */
	function format_items( $format, $items, $fields ): void {
		unset( $format, $fields );
		\WP_CLI::$out[] = array( 'table', $items );
	}
}
