<?php
/**
 * Regression tests for QA-104: on phones the members hub drew "Tuesday net"
 * above "This week", but only with CSS (`.hub-net { order: -1 }` under
 * 900px, and reading-flow, which only Chrome follows). The markup kept This
 * week first, so Firefox, Safari and screen readers read This week, then the
 * net, and Tab went from the search down to "What to bring" and then back up
 * to "Copy radio settings".
 *
 * The owner's decision (2026-09-27): put the Tuesday net first in the markup
 * at every width, with no CSS reordering, and keep the desktop hub as close
 * to the current look as practical. The hub is the theme pattern
 * spokares/members-hub, which templates/page-members.html draws.
 *
 * These tests read the markup the server sends; e2e/qa-104-hub-order.test.mjs
 * checks in a browser that what is drawn follows it at each width.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The h1-h3 headings of some markup, in order, as "<level>:<text>".
 *
 * @param string $html Rendered markup.
 */
function qa104_outline( string $html ): array {
	preg_match_all( '#<h([1-3])\b[^>]*>(.*?)</h\1>#s', $html, $found, PREG_SET_ORDER );
	$out = array();
	foreach ( $found as $one ) {
		$out[] = $one[1] . ':' . trim( html_entity_decode( wp_strip_all_tags( $one[2] ), ENT_QUOTES, 'UTF-8' ) );
	}
	return $out;
}

/**
 * The classes of the hub grid's sections, in markup order.
 *
 * @param string $html Rendered markup.
 */
function qa104_sections( string $html ): array {
	preg_match_all( '#<section class="[^"]*\b(hub-(?:net|week|most))\b#', $html, $found );
	return $found[1];
}

test(
	'QA-104: the members hub pattern puts the Tuesday net before This week in the markup',
	function () {
		$html = do_blocks( '<!-- wp:pattern {"slug":"spokares/members-hub"} /-->' );
		assert_same( array( 'hub-net', 'hub-week', 'hub-most' ), qa104_sections( $html ), 'the hub sections in markup order' );
		assert_same(
			array( '1:For members', '2:Tuesday net', '2:This week', '3:Exercises', '2:Most used' ),
			qa104_outline( $html ),
			'the headings a screen reader lists, in order'
		);
		assert_true( strpos( $html, 'id="rota"' ) < strpos( $html, 'id="this-week"' ), 'the #rota anchor comes before #this-week' );
	}
);

test(
	'QA-104: the For members page template draws the hub in that order',
	function () {
		$template = get_block_template( get_stylesheet() . '//page-members' );
		assert_true( $template instanceof \WP_Block_Template, 'page-members exists' );
		$html = do_blocks( $template->content );
		$main = strpos( $html, '<main' );
		assert_true( false !== $main, 'the template has a <main>' );
		$html = substr( $html, $main, strpos( $html, '</main>', $main ) - $main );
		assert_same( array( 'hub-net', 'hub-week', 'hub-most' ), qa104_sections( $html ), 'the hub sections in the page as served' );
		assert_same( '2:Tuesday net', qa104_outline( $html )[1] ?? '', 'the first heading after "For members"' );
	}
);
