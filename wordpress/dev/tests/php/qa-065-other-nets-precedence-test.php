<?php
/**
 * Regression tests for QA-065 (PLAN §3.4): a week ticked in two net groups
 * on Net details is advertised under both in How it works › Other nets,
 * while the rota shows only one.
 *
 * The rota (spokares_net_on()) gives a week ticked twice to one group: simplex
 * first, then Winlink, then GMRS, and the Net details screen says so ("A week
 * ticked twice counts as simplex first, then Winlink, then GMRS."). The
 * other-nets view of spokares/net printed every group's ticked weeks as they
 * were stored, so:
 *
 * - Winlink 1st–4th with GMRS 1st and 3rd printed "ACS GMRS net: 1st and 3rd
 *   Tuesdays …" while the rota calls those Tuesdays "Winlink night".
 * - Winlink 5th with Simplex 5th printed the fifth Tuesday in both bullets.
 *
 * Each week must be listed once, under the group the rota gives it.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Store net weeks over the seeded ones (the framework restores the options).
 *
 * @param array $nets spk_nets keys to change.
 */
function qa065_set( array $nets ): void {
	update_option( 'spk_nets', array_merge( \spokares_opt( 'spk_nets' ), $nets ) );
}

/**
 * Plain text of some HTML: tags dropped, entities decoded, white space
 * collapsed.
 *
 * @param string $html HTML.
 */
function qa065_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( "\u{00A0}", ' ', $text );
	return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * The bullets of How it works › Other nets, as a visitor gets them.
 *
 * @return string[] Plain text of each bullet.
 */
function qa065_bullets(): array {
	as_anonymous();
	$html = do_blocks( '<!-- wp:spokares/net {"view":"other-nets"} /-->' );
	preg_match_all( '#<li\b[^>]*>(.*?)</li>#s', $html, $m );
	return array_map( __NAMESPACE__ . '\qa065_plain', $m[1] );
}

/**
 * Which weeks each Other nets bullet advertises.
 *
 * A bullet is Winlink ("Winlink nights: …"), GMRS ("ACS GMRS net: …") or
 * simplex ("… Tuesdays: the net starts on simplex …"). Its weeks are the
 * ordinals before the first "Tuesday".
 *
 * @return array{winlink:int[],simplex:int[],gmrs:int[],bullets:string[]}
 */
function qa065_claims(): array {
	$out   = array(
		'winlink' => array(),
		'simplex' => array(),
		'gmrs'    => array(),
		'bullets' => array(),
	);
	$words = array(
		'1st'   => 1,
		'2nd'   => 2,
		'3rd'   => 3,
		'4th'   => 4,
		'fifth' => 5,
		'5th'   => 5,
	);
	foreach ( qa065_bullets() as $text ) {
		$out['bullets'][] = $text;
		if ( str_starts_with( $text, 'Winlink nights:' ) ) {
			$kind = 'winlink';
		} elseif ( str_starts_with( $text, 'ACS GMRS net:' ) ) {
			$kind = 'gmrs';
		} elseif ( false !== stripos( $text, 'simplex' ) ) {
			$kind = 'simplex';
		} else {
			fail( 'Other nets printed a bullet the test cannot place: "' . $text . '"' );
		}
		$head = strstr( $text, 'Tuesday', true );
		$head = false === $head ? $text : $head;
		preg_match_all( '/\b(1st|2nd|3rd|4th|5th|fifth)\b/i', $head, $m );
		$weeks = array();
		foreach ( $m[1] as $word ) {
			$weeks[] = $words[ strtolower( $word ) ];
		}
		assert_true( (bool) $weeks, 'Other nets bullet names no week: "' . $text . '"' );
		$out[ $kind ] = array_merge( $out[ $kind ], $weeks );
	}
	foreach ( array( 'winlink', 'simplex', 'gmrs' ) as $kind ) {
		$out[ $kind ] = array_values( array_unique( $out[ $kind ] ) );
		sort( $out[ $kind ] );
	}
	return $out;
}

/**
 * Which weeks the rota gives each group, from spokares_net_on() on the five
 * Tuesdays of September 2026 (1, 8, 15, 22 and 29).
 *
 * @return array{winlink:int[],simplex:int[],gmrs:int[]}
 */
function qa065_rota_weeks(): array {
	$out = array(
		'winlink' => array(),
		'simplex' => array(),
		'gmrs'    => array(),
	);
	for ( $n = 1; $n <= 5; $n++ ) {
		$row = \spokares_net_on( \spokares_add_days( '2026-09-01', 7 * ( $n - 1 ) ) );
		assert_same( $n, $row['nth'], 'test calendar: Tuesday ' . $n . ' of September 2026' );
		if ( isset( $out[ $row['kind'] ] ) ) {
			$out[ $row['kind'] ][] = $n;
		}
	}
	return $out;
}

/**
 * Other nets must list each week once, under the group the rota gives it.
 *
 * @param string $label Case label for the failure text.
 */
function qa065_assert_agrees( string $label ): void {
	$claims = qa065_claims();
	$rota   = qa065_rota_weeks();
	$said   = implode( ' / ', $claims['bullets'] );
	$seen   = array_merge( $claims['winlink'], $claims['simplex'], $claims['gmrs'] );
	assert_same(
		count( array_unique( $seen ) ),
		count( $seen ),
		$label . ': a week is advertised under two groups in Other nets ("' . $said . '")'
	);
	foreach ( array( 'winlink', 'simplex', 'gmrs' ) as $kind ) {
		assert_same(
			$rota[ $kind ],
			$claims[ $kind ],
			$label . ': Other nets ' . $kind . ' weeks differ from the rota ("' . $said . '")'
		);
	}
}

test(
	'with no overlap, Other nets lists the weeks the rota gives each group',
	function () {
		qa065_set(
			array(
				'winlink_nth' => array( 2, 4 ),
				'simplex_nth' => array( 5 ),
				'gmrs_nth'    => array( 3 ),
				'gmrs_time'   => '19:30',
			)
		);
		// Sanity check of the parser: the seeded, non-overlapping weeks.
		$claims = qa065_claims();
		assert_same( array( 2, 4 ), $claims['winlink'], 'Winlink weeks' );
		assert_same( array( 5 ), $claims['simplex'], 'simplex weeks' );
		assert_same( array( 3 ), $claims['gmrs'], 'GMRS weeks' );
		qa065_assert_agrees( 'no overlap' );
	}
);

test(
	'Winlink 1st to 4th with GMRS 1st and 3rd: Other nets does not advertise GMRS on Winlink nights',
	function () {
		qa065_set(
			array(
				'winlink_nth' => array( 1, 2, 3, 4 ),
				'simplex_nth' => array(),
				'gmrs_nth'    => array( 1, 3 ),
				'gmrs_time'   => '19:30',
			)
		);
		$rota = qa065_rota_weeks();
		assert_same( array( 1, 2, 3, 4 ), $rota['winlink'], 'rota (sanity check): Winlink wins weeks 1 to 4' );
		assert_same( array(), $rota['gmrs'], 'rota (sanity check): GMRS wins no week' );
		foreach ( qa065_bullets() as $text ) {
			assert_false( str_starts_with( $text, 'ACS GMRS net:' ), 'Other nets says "' . $text . '" but the rota calls the 1st and 3rd Tuesdays "Winlink night"' );
		}
		qa065_assert_agrees( 'Winlink 1-4, GMRS 1+3' );
	}
);

test(
	'Winlink and Simplex both on the 5th: the fifth Tuesday is listed once, under simplex',
	function () {
		qa065_set(
			array(
				'winlink_nth' => array( 2, 4, 5 ),
				'simplex_nth' => array( 5 ),
				'gmrs_nth'    => array( 3 ),
				'gmrs_time'   => '19:30',
			)
		);
		$claims = qa065_claims();
		assert_same( array( 5 ), $claims['simplex'], 'simplex weeks in Other nets' );
		assert_not_contains( 5, $claims['winlink'], 'Other nets lists the fifth Tuesday under Winlink too ("' . implode( ' / ', $claims['bullets'] ) . '")' );
		qa065_assert_agrees( 'Winlink 2+4+5, simplex 5' );
	}
);

test(
	'a week ticked in all three groups is listed once, under simplex',
	function () {
		qa065_set(
			array(
				'winlink_nth' => array( 2, 3 ),
				'simplex_nth' => array( 3 ),
				'gmrs_nth'    => array( 3, 4 ),
				'gmrs_time'   => '19:30',
			)
		);
		qa065_assert_agrees( 'week 3 in all three groups' );
		$claims = qa065_claims();
		assert_same( array( 2 ), $claims['winlink'], 'Winlink weeks' );
		assert_same( array( 3 ), $claims['simplex'], 'simplex weeks' );
		assert_same( array( 4 ), $claims['gmrs'], 'GMRS weeks' );
	}
);

test(
	'after saving overlapping weeks on Net details, Other nets agrees with the rota',
	function () {
		as_role( 'ares-net' );
		$radio = \spokares_opt( 'spk_radio' );
		$nets  = \spokares_opt( 'spk_nets' );
		$res   = post_form(
			'spokares_save_net_details',
			array(
				'radio' => array(
					'primary'   => array(
						'call'   => $radio['primary']['call'],
						'freq'   => $radio['primary']['freq'],
						'offset' => $radio['primary']['offset'],
						'tone'   => $radio['primary']['tone'],
					),
					'alternate' => array(
						'freq'   => $radio['alternate']['freq'],
						'offset' => $radio['alternate']['offset'],
						'tone'   => $radio['alternate']['tone'],
						'show'   => $radio['alternate']['show'] ? '1' : '',
					),
				),
				'nets'  => array(
					'net_time'       => $nets['net_time'],
					'gmrs_time'      => '19:30',
					'winlink_nth'    => array( '1', '2', '3', '4' ),
					'simplex_nth'    => array(),
					'gmrs_nth'       => array( '1', '3' ),
					'winlink_howto'  => $nets['winlink_howto'],
					'open_slot_line' => $nets['open_slot_line'],
				),
			)
		);
		assert_same( null, $res['die'], 'save refused outright' );
		qa065_assert_agrees( 'saved Winlink 1-4, GMRS 1+3' );
	}
);
