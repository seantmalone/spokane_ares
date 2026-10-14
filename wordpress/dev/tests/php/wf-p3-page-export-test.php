<?php
/**
 * Regression tests for workflow review P3: the webmaster's layout edits in
 * the block editor had to be pasted into theme/spokares/patterns/*.php by
 * hand. `wp spokares page export <home|how|about>` now prints the page as a
 * whole-page pattern file, and `--sections` says which sections differ from
 * the theme's pattern. These tests call the functions behind the command
 * (WP-CLI isn't loaded in the test request).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'page export: the theme file\'s header, then the page\'s markup; it registers and parses as a pattern',
	function () {
		foreach ( array(
			'home'  => array( 'home', 'spokares/page-home' ),
			'how'   => array( 'how-it-works', 'spokares/page-how-it-works' ),
			'about' => array( 'about', 'spokares/page-about' ),
		) as $which => $want ) {
			$file = \spokares_page_export_file( $which );
			expect_not_wp_error( $file, $which );
			assert_matches( '#^<\?php\n/\*\*\n \* Title: .*\n \* Slug: ' . preg_quote( $want[1], '#' ) . '\n#', $file, $which . ': the header' );
			assert_contains( "defined( 'ABSPATH' ) || exit;\n?>\n<!-- wp:", $file, $which . ': the markup follows' );
			assert_not_contains( 'require __DIR__', $file, $which . ': no require lines' );
			assert_contains( trim( get_post( page_id( $want[0] ) )->post_content ), $file, $which . ': the live markup' );
		}
		expect_wp_error( \spokares_page_export_file( 'members' ), 'spokares_export_page' );
	}
);

test(
	'page export --sections: a fresh site matches the theme; a words edit and a layout edit are named by section',
	function () {
		$rows = \spokares_page_export_sections( 'about' );
		expect_not_wp_error( $rows );
		assert_true( count( $rows ) > 1, 'About has sections' );
		assert_same( array( 'same' ), array_values( array_unique( array_column( $rows, 'state' ) ) ), 'a fresh site matches its patterns' );

		$about   = page_id( 'about' );
		$content = get_post( $about )->post_content;
		$at      = strpos( $content, "</p>\n<!-- /wp:paragraph -->" );
		$content = substr( $content, 0, $at ) . ' More words.' . substr( $content, $at ) . "\n\n<!-- wp:paragraph -->\n<p>New part.</p>\n<!-- /wp:paragraph -->";
		as_role( 'admin' );
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $about,
					'post_content' => $content,
				)
			)
		);
		clean_post_cache( $about );
		$rows = \spokares_page_export_sections( 'about' );
		$words = array_values( array_filter( $rows, static fn( $r ) => 'words' === $r['state'] ) );
		assert_count( 1, $words, 'one section\'s words changed' );
		assert_contains( 'about-head', $words[0]['classes'], 'the page head (about-head.php), where the first paragraph is' );
		assert_same( array(), array_values( array_filter( $rows, static fn( $r ) => 'layout' === $r['state'] ) ), 'no layout change in the old sections' );
		assert_same( 'added', end( $rows )['state'], 'a new section at the end' );
		assert_same( 'core/paragraph', end( $rows )['name'], 'its block' );
	}
);
