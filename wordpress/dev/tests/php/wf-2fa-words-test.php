<?php
/**
 * The Two-Factor plugin's words are reworded by their exact source text
 * (two-factor.php, spokares_hard_2fa_word_map()). The live site auto-updates
 * the plugin (it ran 0.17.0 when this was written), so if an update changes a
 * source string the rewording silently stops. This test reads the installed
 * plugin's files and checks every source string is still there.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'every Two-Factor string the site rewords is still in the installed plugin (0.17 or later)',
	function () {
		if ( ! class_exists( 'Two_Factor_Core' ) ) {
			skip( 'the Two-Factor plugin is not active' );
		}
		$dir = WP_PLUGIN_DIR . '/two-factor';
		assert_true( is_dir( $dir ), 'the plugin folder' );
		if ( defined( 'TWO_FACTOR_VERSION' ) ) {
			assert_true( version_compare( TWO_FACTOR_VERSION, '0.17.0', '>=' ), 'Two-Factor ' . TWO_FACTOR_VERSION . ' is older than the live site\'s 0.17.0' );
		}
		$source = '';
		$files  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			if ( 'php' === $file->getExtension() && ! str_contains( $file->getPathname(), '/vendor/' ) ) {
				$source .= (string) file_get_contents( $file->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin files.
			}
		}
		$map = \spokares_hard_2fa_word_map();
		assert_count( 8, $map, 'the reworded strings' );
		foreach ( array_keys( $map ) as $text ) {
			assert_contains( "'" . $text . "'", $source, 'the plugin no longer has this string: ' . $text );
		}
	}
);
