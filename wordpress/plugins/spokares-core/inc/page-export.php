<?php
/**
 * `wp spokares page export <home|how|about>` (workflow review P3): the
 * webmaster's layout edits to Home, How it works or About, made in the block
 * editor on the live site, as a theme pattern file, so they can be copied
 * into git for the next release and a fresh install matches (PLAN §5.7).
 *
 * The functions are plain PHP (the dev tests call them); the command is
 * registered only under WP-CLI, which only someone with shell access runs.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The three text pages: command name => page path and whole-page pattern.
 *
 * @return array<string,array{path:string,pattern:string}>
 */
function spokares_export_pages(): array {
	return array(
		'home'  => array(
			'path'    => 'home',
			'pattern' => 'page-home',
		),
		'how'   => array(
			'path'    => 'how-it-works',
			'pattern' => 'page-how-it-works',
		),
		'about' => array(
			'path'    => 'about',
			'pattern' => 'page-about',
		),
	);
}

/**
 * The page's current block markup as a whole-page pattern file: the theme
 * file's own header (Title, Slug, Categories …) with the page's markup in
 * place of its require lines. WP_Error when the page or the theme file is
 * missing.
 *
 * @param string $which 'home', 'how' or 'about'.
 * @return string|WP_Error
 */
function spokares_page_export_file( string $which ) {
	$pages = spokares_export_pages();
	if ( ! isset( $pages[ $which ] ) ) {
		/* translators: %s: what was typed. */
		return new WP_Error( 'spokares_export_page', sprintf( __( 'No page called "%s". Use home, how or about.', 'spokares-core' ), $which ) );
	}
	$page = get_page_by_path( $pages[ $which ]['path'], OBJECT, 'page' );
	if ( ! $page ) {
		/* translators: %s: page path. */
		return new WP_Error( 'spokares_export_page', sprintf( __( 'The page /%s/ is not on this site.', 'spokares-core' ), $pages[ $which ]['path'] ) );
	}
	$file = get_theme_file_path( 'patterns/' . $pages[ $which ]['pattern'] . '.php' );
	$head = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local theme file.
	$cut  = strpos( $head, "defined( 'ABSPATH' ) || exit;" );
	if ( false === $cut ) {
		/* translators: %s: file path. */
		return new WP_Error( 'spokares_export_pattern', sprintf( __( 'The theme file %s is missing or has no header.', 'spokares-core' ), $file ) );
	}
	$head = substr( $head, 0, $cut ) . "defined( 'ABSPATH' ) || exit;\n?>\n";
	return $head . trim( (string) $page->post_content ) . "\n";
}

/**
 * Section by section, how the live page differs from the theme's
 * whole-page pattern: for each top-level block its name, its classes (which
 * name the pattern file it comes from, e.g. "about-head"), and 'same',
 * 'words' (only the words differ) or 'layout'. Sections only on one side
 * are 'added' or 'removed'.
 *
 * @param string $which 'home', 'how' or 'about'.
 * @return array<int,array{n:int,name:string,classes:string,state:string}>|WP_Error
 */
function spokares_page_export_sections( string $which ) {
	$pages = spokares_export_pages();
	$page  = isset( $pages[ $which ] ) ? get_page_by_path( $pages[ $which ]['path'], OBJECT, 'page' ) : null;
	if ( ! $page ) {
		return spokares_page_export_file( $which ); // The same error.
	}
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'spokares/' . $pages[ $which ]['pattern'] );
	if ( ! $pattern || empty( $pattern['content'] ) ) {
		return new WP_Error( 'spokares_export_pattern', __( 'The theme’s whole-page pattern is not registered.', 'spokares-core' ) );
	}
	$top  = static fn( string $content ): array => array_values( array_filter( parse_blocks( $content ), static fn( $b ) => null !== $b['blockName'] || '' !== trim( (string) $b['innerHTML'] ) ) );
	$live = $top( (string) $page->post_content );
	$ours = $top( (string) $pattern['content'] );
	$out  = array();
	$max  = max( count( $live ), count( $ours ) );
	for ( $i = 0; $i < $max; $i++ ) {
		$b = $live[ $i ] ?? $ours[ $i ];
		if ( ! isset( $live[ $i ] ) ) {
			$state = 'removed';
		} elseif ( ! isset( $ours[ $i ] ) ) {
			$state = 'added';
		} elseif ( ! spokares_skeleton_equal( spokares_skeleton_nodes( array( $ours[ $i ] ) ), spokares_skeleton_nodes( array( $live[ $i ] ) ) ) ) {
			$state = 'layout';
		} else {
			$state = trim( serialize_block( $ours[ $i ] ) ) === trim( serialize_block( $live[ $i ] ) ) ? 'same' : 'words';
		}
		$out[] = array(
			'n'       => $i + 1,
			'name'    => (string) ( $b['blockName'] ?? 'core/freeform' ),
			'classes' => (string) ( $b['attrs']['className'] ?? '' ),
			'state'   => $state,
		);
	}
	return $out;
}

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
	/**
	 * Export the current block markup of Home, How it works or About as a
	 * theme pattern file.
	 *
	 * ## OPTIONS
	 *
	 * <page>
	 * : home, how or about.
	 *
	 * [--file=<path>]
	 * : Write the pattern file here instead of printing it (for example
	 * theme/spokares/patterns/page-about.php in a git checkout).
	 *
	 * [--sections]
	 * : Instead, list the page's sections and whether each differs from the
	 * theme's pattern (same, words, layout, added, removed).
	 *
	 * ## EXAMPLES
	 *
	 *     wp spokares page export about --sections
	 *     wp spokares page export about > page-about.php
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Options.
	 */
	$spokares_page_export = static function ( array $args, array $assoc_args ): void {
		$which = (string) ( $args[0] ?? '' );
		if ( ! empty( $assoc_args['sections'] ) ) {
			$rows = spokares_page_export_sections( $which );
			if ( is_wp_error( $rows ) ) {
				WP_CLI::error( $rows->get_error_message() );
			}
			WP_CLI\Utils\format_items( 'table', $rows, array( 'n', 'name', 'classes', 'state' ) );
			return;
		}
		$file = spokares_page_export_file( $which );
		if ( is_wp_error( $file ) ) {
			WP_CLI::error( $file->get_error_message() );
		}
		if ( str_contains( $file, '/wp-content/uploads/' ) ) {
			WP_CLI::warning( 'The markup links photos in this site\'s Media Library (/wp-content/uploads/). A fresh install doesn\'t have them: point those at a theme image (get_theme_file_uri()) before committing.' );
		}
		if ( ! empty( $assoc_args['file'] ) ) {
			if ( false === file_put_contents( (string) $assoc_args['file'], $file ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP-CLI writes the file the webmaster named.
				WP_CLI::error( 'Could not write ' . $assoc_args['file'] );
			}
			WP_CLI::success( 'Wrote ' . $assoc_args['file'] . '. It replaces the section require lines with the whole page; split it back into the section files if you prefer, then diff and commit.' );
			return;
		}
		WP_CLI::line( rtrim( $file, "\n" ) );
	};
	WP_CLI::add_command( 'spokares page export', $spokares_page_export );
	unset( $spokares_page_export );
}
