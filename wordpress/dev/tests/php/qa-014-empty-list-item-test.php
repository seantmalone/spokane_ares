<?php
/**
 * Regression tests for QA-014 (PLAN §4.3, §6.5): a non-administrator could
 * save an empty list item into a lead-in list (Home's "What we do" stops,
 * About's years, How it works' message hops). Pressing Enter at the start or
 * end of an item in the block editor leaves an empty item; saving it went
 * through ("Page updated."), stored <li></li>, and the public page showed an
 * extra stop on the line with no words. The layout check lets a bare list
 * item through so editors can add items, and the editor guard's lead-in
 * warning skips empty items, so nothing stopped it.
 *
 * The correct behaviour: for a user held to the layout lock, the server
 * either refuses the save or stores the page without the empty item, on the
 * REST path (the block editor) and on every other save path. Adding a real
 * item (with bold first words) still saves.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Some page content with one more list item added after the first item of
 * the list that has this class.
 *
 * @param string $content Page content.
 * @param string $css     Class of the list (stops, years, hops).
 * @param string $li      The new item's HTML.
 */
function qa014_add_item( string $content, string $css, string $li ): string {
	if ( ! preg_match( '/<(ul|ol) class="wp-block-list[^"]*\b' . preg_quote( $css, '/' ) . '\b[^"]*">/', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		fail( 'no list with class "' . $css . '" in the page' );
	}
	$close = strpos( $content, '<!-- /wp:list-item -->', (int) $m[0][1] );
	if ( false === $close ) {
		fail( 'the "' . $css . '" list has no items' );
	}
	$at = $close + strlen( '<!-- /wp:list-item -->' );
	return substr( $content, 0, $at ) . "\n\n<!-- wp:list-item -->\n" . $li . "\n<!-- /wp:list-item -->" . substr( $content, $at );
}

/**
 * The words of each item of the list with this class, in order ('' for an
 * item with no words).
 *
 * @param string $content Page content.
 * @param string $css     Class of the list.
 * @return string[]|null Null when there is no such list.
 */
function qa014_items( string $content, string $css ): ?array {
	$find = static function ( array $blocks ) use ( &$find, $css ): ?array {
		foreach ( $blocks as $b ) {
			if ( 'core/list' === ( $b['blockName'] ?? '' ) ) {
				$classes = preg_split( '/\s+/', (string) ( $b['attrs']['className'] ?? '' ) );
				if ( in_array( $css, $classes, true ) ) {
					$out = array();
					foreach ( $b['innerBlocks'] as $item ) {
						$out[] = trim( html_entity_decode( wp_strip_all_tags( (string) ( $item['innerHTML'] ?? '' ) ), ENT_QUOTES, 'UTF-8' ) );
					}
					return $out;
				}
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$hit = $find( $b['innerBlocks'] );
				if ( null !== $hit ) {
					return $hit;
				}
			}
		}
		return null;
	};
	return $find( parse_blocks( $content ) );
}

/**
 * The page's stored content, read fresh from the database.
 *
 * @param int $id Page ID.
 */
function qa014_stored( int $id ): string {
	clean_post_cache( $id );
	return (string) get_post( $id )->post_content;
}

/**
 * As this role, save the page over REST (as the block editor does) with an
 * empty item added to the list with this class, then check that the stored
 * page, and the page as it prints, has no empty item in that list.
 *
 * @param string $role Role key.
 * @param string $path Page path.
 * @param string $css  Class of the list.
 */
function qa014_rest_empty_item( string $role, string $path, string $css ): void {
	$id     = page_id( $path );
	$before = qa014_items( qa014_stored( $id ), $css );
	assert_true( is_array( $before ) && count( $before ) > 0, $path . ': the "' . $css . '" list is on the page' );
	assert_not_contains( '', $before, $path . ': no empty item before the test' );

	as_role( $role );
	$new = qa014_add_item( qa014_stored( $id ), $css, '<li></li>' );
	assert_contains( '', (array) qa014_items( $new, $css ), 'the new content holds an empty item' );
	$res = rest( 'POST', '/wp/v2/pages/' . $id, array( 'content' => $new ) );

	$after = qa014_items( qa014_stored( $id ), $css );
	assert_not_contains(
		'',
		(array) $after,
		$role . ' saved ' . $path . ' over REST (HTTP ' . $res->get_status() . ') and the stored "' . $css . '" list now has an empty item: ' . wp_json_encode( $after )
	);
	assert_same( count( $before ), count( (array) $after ), $path . ': the "' . $css . '" list keeps its items' );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter, run as the front end runs it.
	$html = apply_filters( 'the_content', qa014_stored( $id ) );
	assert_not_contains( '<li></li>', $html, $path . ': the public page prints an empty list item' );
}

test(
	'ARES Editor: an empty stop in Home\'s What we do list is not saved over REST',
	function () {
		qa014_rest_empty_item( 'ares-editor', 'home', 'stops' );
	}
);

test(
	'ARES Editor with the net grant: an empty stop in Home\'s What we do list is not saved over REST',
	function () {
		qa014_rest_empty_item( 'ares-net', 'home', 'stops' );
	}
);

test(
	'core Editor: an empty stop in Home\'s What we do list is not saved over REST',
	function () {
		qa014_rest_empty_item( 'core-editor', 'home', 'stops' );
	}
);

test(
	'ARES Editor: an empty year in About\'s history list is not saved over REST',
	function () {
		qa014_rest_empty_item( 'ares-editor', 'about', 'years' );
	}
);

test(
	'ARES Editor: an empty hop in How it works\' message path is not saved over REST',
	function () {
		qa014_rest_empty_item( 'ares-editor', 'how-it-works', 'hops' );
	}
);

test(
	'ARES Editor: an empty stop is not saved through wp_update_post() either',
	function () {
		$id     = page_id( 'home' );
		$before = qa014_items( qa014_stored( $id ), 'stops' );
		as_role( 'ares-editor' );
		$new = qa014_add_item( qa014_stored( $id ), 'stops', '<li></li>' );
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_content' => $new,
				)
			)
		);
		$after = qa014_items( qa014_stored( $id ), 'stops' );
		assert_not_contains( '', (array) $after, 'the stored stops list has an empty item: ' . wp_json_encode( $after ) );
		assert_same( count( $before ), count( (array) $after ), 'the stops list keeps its items' );
	}
);

test(
	'ARES Editor: a new stop with bold first words still saves (control)',
	function () {
		$id     = page_id( 'home' );
		$before = qa014_items( qa014_stored( $id ), 'stops' );
		as_role( 'ares-editor' );
		$new = qa014_add_item( qa014_stored( $id ), 'stops', '<li><strong>Search and rescue</strong> Radio support for the county’s SAR teams.</li>' );
		$res = rest( 'POST', '/wp/v2/pages/' . $id, array( 'content' => $new ) );
		expect_not_wp_error( $res, 'adding a real stop is not a layout change' );
		$after = qa014_items( qa014_stored( $id ), 'stops' );
		assert_count( count( $before ) + 1, (array) $after, 'the new stop is stored' );
		assert_contains( 'Search and rescue Radio support for the county’s SAR teams.', (array) $after, 'the new stop is stored' );
	}
);
