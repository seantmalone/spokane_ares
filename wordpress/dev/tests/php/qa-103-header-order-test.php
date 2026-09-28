<?php
/**
 * Regression tests for QA-103: the header's reading and focus order below
 * 1020px did not match what is drawn (PLAN §6 parts/header.html, owner
 * decision 2026-09-27).
 *
 * Under 1020px the bar shows the brand, "Join the team", then the Menu
 * button. The markup was brand, Navigation (its Menu button first), then the
 * Join button, and site.css put Join before the nav with flex `order`
 * (reading-flow made only Chrome follow it). Firefox and Safari, and every
 * screen reader's reading order, follow the markup: brand, Menu, Join.
 *
 * The owner's decision: fix it in the markup for every browser. The DOM
 * order must be the visual order at every width, with no CSS reordering; a
 * second "Join the team" inside the phone menu is allowed, and the bar keeps
 * its own. Desktop keeps brand, the four links, then Join.
 *
 * These tests read the header as the server renders it: a Join link must
 * come between the brand and the Menu button (the phone bar), and a Join link
 * must follow the last of the four links (the desktop bar). Which of the two
 * is drawn at a width, and that nothing is reordered by CSS, is checked in a
 * browser by e2e/qa-103-header-order.test.mjs.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The focusable stops in the rendered header part, in markup order. Each is
 * 'brand', 'join', 'menu', 'close' or 'link:<href>', plus whether it sits
 * inside the primary <nav>.
 *
 * @return array<int, array{stop: string, in_nav: bool}>
 */
function qa103_header_stops(): array {
	$html  = do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->' );
	$tags  = new \WP_HTML_Tag_Processor( $html );
	$stops = array();
	$depth = 0;
	while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		$name = $tags->get_tag();
		if ( 'NAV' === $name ) {
			$depth += $tags->is_tag_closer() ? -1 : 1;
			continue;
		}
		if ( $tags->is_tag_closer() || ! in_array( $name, array( 'A', 'BUTTON' ), true ) ) {
			continue;
		}
		$href = (string) $tags->get_attribute( 'href' );
		if ( 'A' === $name && $tags->has_class( 'brand' ) ) {
			$stop = 'brand';
		} elseif ( 'A' === $name && str_ends_with( $href, '#join' ) ) {
			$stop = 'join';
		} elseif ( 'BUTTON' === $name && $tags->has_class( 'wp-block-navigation__responsive-container-open' ) ) {
			$stop = 'menu';
		} elseif ( 'BUTTON' === $name && $tags->has_class( 'wp-block-navigation__responsive-container-close' ) ) {
			$stop = 'close';
		} elseif ( 'A' === $name && $tags->has_class( 'wp-block-navigation-item__content' ) ) {
			$stop = 'link:' . $href;
		} else {
			$stop = strtolower( $name ) . ':' . $href;
		}
		$stops[] = array(
			'stop'   => $stop,
			'in_nav' => $depth > 0,
		);
	}
	return $stops;
}

test(
	'QA-103: the header markup reads brand, Join the team, then Menu (the phone bar)',
	function () {
		$stops = array_column( qa103_header_stops(), 'stop' );
		$seen  = implode( ', ', $stops );
		assert_same( 'brand', $stops[0] ?? '', 'the brand is the first stop in the header: ' . $seen );
		$menu = array_search( 'menu', $stops, true );
		assert_true( false !== $menu, 'the header has the Menu button: ' . $seen );
		assert_same( array( 'brand', 'join' ), array_slice( $stops, 0, (int) $menu ), 'between the brand and the Menu button the markup has exactly one "Join the team": ' . $seen );
	}
);

test(
	'QA-103: the header markup reads brand, the four links, then Join the team (the desktop bar)',
	function () {
		$stops = array_column( qa103_header_stops(), 'stop' );
		$seen  = implode( ', ', $stops );
		$links = array();
		foreach ( $stops as $stop ) {
			if ( str_starts_with( $stop, 'link:' ) ) {
				$links[] = $stop;
			}
		}
		assert_same( array( 'link:/', 'link:/how-it-works/', 'link:/about/', 'link:/members/' ), $links, 'the four primary links, in order: ' . $seen );
		$last_link = max( array_keys( $stops, 'link:/members/', true ) );
		$after     = array_slice( $stops, $last_link + 1 );
		assert_same( array( 'join' ), $after, 'after the last primary link the markup has exactly one "Join the team", and it ends the header: ' . $seen );
	}
);

test(
	'QA-103: both Join buttons go to the Join section and are real links (no script needed)',
	function () {
		$html = do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->' );
		assert_same( 2, preg_match_all( '#<a\b[^>]*href="/\#join"[^>]*>Join the team</a>#', $html ), 'two "Join the team" links to /#join' );
		$in_nav = 0;
		foreach ( qa103_header_stops() as $stop ) {
			if ( 'join' === $stop['stop'] && $stop['in_nav'] ) {
				++$in_nav;
			}
		}
		assert_same( 1, $in_nav, 'one of them is inside the primary navigation (the phone menu), as the owner decided' );
	}
);
