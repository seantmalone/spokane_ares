<?php
/**
 * Regression tests for QA-055 (PLAN §3.4 Net rota: "13 Tuesdays from this
 * week (link: "Show 13 more")"; EDITING-GUIDE: "Need a Tuesday further out?
 * Click Show 13 more under the list.").
 *
 * The Net rota screen clamps ?weeks= to 13..52. Each "Show 13 more" click
 * adds 13 Tuesdays, up to the 52 cap. At the cap the screen still prints
 * "Showing 52 Tuesdays from this week. Show 13 more" as a link, and the link
 * goes to weeks=min( 52, 52 + 13 ) = 52: the same page. Clicking it reloads
 * the screen with nothing more to show (a dead link that promises 13 more).
 *
 * Correct behaviour: at 52 Tuesdays there is no "Show 13 more" link and no
 * link back to the same 52-week screen. Below the cap the link is still
 * there and adds 13 Tuesdays.
 *
 * The screen is rendered as admin.php?page=spokares-rota&weeks=N would print
 * it, for an ARES Editor and for an ARES Editor with the Net details grant.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The Net rota screen's HTML for ?weeks=$weeks, as the current user.
 *
 * @param string|null $weeks The weeks query arg (null leaves it out).
 */
function qa055_rota_html( ?string $weeks ): string {
	$get = array( 'page' => 'spokares-rota' );
	if ( null !== $weeks ) {
		$get['weeks'] = $weeks;
	}
	$res = call_request(
		'GET',
		$get,
		array(),
		static function () {
			\spokares_rota_page();
		}
	);
	assert_same( null, $res['die'], 'the rota screen did not wp_die()' );
	assert_same( null, $res['redirect'], 'the rota screen did not redirect' );
	return (string) $res['output'];
}

/**
 * Every link on the screen: href (entities decoded) and visible text.
 *
 * @param string $html Screen HTML.
 */
function qa055_links( string $html ): array {
	$links = array();
	preg_match_all( '#<a\b([^>]*)>(.*?)</a>#is', $html, $m, PREG_SET_ORDER );
	foreach ( $m as $a ) {
		$href = '';
		if ( preg_match( '#\bhref\s*=\s*"([^"]*)"#i', $a[1], $h ) ) {
			$href = html_entity_decode( $h[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
		$links[] = array(
			'href' => $href,
			'text' => trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $a[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ),
		);
	}
	return $links;
}

/**
 * The rota screen links (page=spokares-rota) and their weeks= value.
 *
 * @param string $html Screen HTML.
 */
function qa055_rota_links( string $html ): array {
	$out = array();
	foreach ( qa055_links( $html ) as $link ) {
		$query = (string) wp_parse_url( $link['href'], PHP_URL_QUERY );
		parse_str( $query, $args );
		if ( isset( $args['page'] ) && 'spokares-rota' === $args['page'] ) {
			$link['weeks'] = isset( $args['weeks'] ) ? (int) $args['weeks'] : null;
			$out[]         = $link;
		}
	}
	return $out;
}

/**
 * How many Tuesdays the screen shows: every row is drawn (so "Show 13 more"
 * can show the next ones in place, keeping what was typed), and the rows
 * past ?weeks= are hidden.
 *
 * @param string $html Screen HTML.
 * @return array{drawn:int,shown:int}
 */
function qa055_rows( string $html ): array {
	preg_match_all( '/<tr class="spk-rota-row[^"]*"[^>]*>/', $html, $m );
	$shown = array_filter( $m[0], static fn( $tag ) => ! preg_match( '/\shidden[\s>]/', $tag ) );
	return array(
		'drawn' => count( $m[0] ),
		'shown' => count( $shown ),
	);
}

/**
 * The "Show 13 more" links on the screen.
 *
 * @param string $html Screen HTML.
 */
function qa055_more_links( string $html ): array {
	return array_values(
		array_filter(
			qa055_links( $html ),
			static fn( $l ) => false !== stripos( $l['text'], 'Show 13 more' )
		)
	);
}

test(
	'at 52 Tuesdays the Net rota shows no "Show 13 more" link',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$html = qa055_rota_html( '52' );
			assert_same( 52, qa055_rows( $html )['shown'], $role . ': set-up: the screen lists 52 Tuesdays' );
			assert_contains( 'That’s as far ahead as you can post.', $html, $role . ': at the cap the screen says so' );
			$more = qa055_more_links( $html );
			assert_count(
				0,
				$more,
				$role . ': at the 52-week cap a "Show 13 more" link is still printed (' . wp_json_encode( $more ) . ')'
			);
		}
	}
);

test(
	'at 52 Tuesdays no rota link reloads the same 52-week screen',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$html = qa055_rota_html( '52' );
			$same = array_values( array_filter( qa055_rota_links( $html ), static fn( $l ) => null !== $l['weeks'] && $l['weeks'] >= 52 ) );
			assert_count(
				0,
				$same,
				$role . ': a link on the 52-week screen goes to weeks=52 again, the same page (' . wp_json_encode( $same ) . ')'
			);
		}
	}
);

test(
	'weeks above the cap clamp to 52 Tuesdays with no "Show 13 more" link',
	function () {
		as_role( 'ares-editor' );
		$html = qa055_rota_html( '60' );
		assert_same( 52, qa055_rows( $html )['shown'], 'set-up: weeks=60 is clamped to 52 Tuesdays' );
		assert_count( 0, qa055_more_links( $html ), 'weeks=60 (clamped to 52) still prints a "Show 13 more" link' );
	}
);

test(
	'control: below the cap "Show 13 more" adds 13 Tuesdays (13 to 26, 26 to 39, 39 to 52)',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			foreach ( array( array( null, 26 ), array( '13', 26 ), array( '26', 39 ), array( '39', 52 ) ) as $step ) {
				$weeks = $step[0];
				$to    = $step[1];
				$html  = qa055_rota_html( $weeks );
				$shown = null === $weeks ? 13 : (int) $weeks;
				assert_same( $shown, qa055_rows( $html )['shown'], $role . ': set-up: weeks=' . ( $weeks ?? '(none)' ) . ' lists ' . $shown . ' Tuesdays' );
				assert_same( 52, qa055_rows( $html )['drawn'], $role . ': weeks=' . ( $weeks ?? '(none)' ) . ': all 52 Tuesdays are on the page, the rest hidden, so "Show 13 more" shows them in place' );
				$more = qa055_more_links( $html );
				assert_count( 1, $more, $role . ': weeks=' . ( $weeks ?? '(none)' ) . ': one "Show 13 more" link' );
				$query = (string) wp_parse_url( $more[0]['href'], PHP_URL_QUERY );
				parse_str( $query, $args );
				assert_same( 'spokares-rota', $args['page'] ?? '', $role . ': the link stays on the Net rota screen' );
				assert_same( (string) $to, (string) ( $args['weeks'] ?? '' ), $role . ': weeks=' . ( $weeks ?? '(none)' ) . ': "Show 13 more" goes to weeks=' . $to );
			}
		}
	}
);
