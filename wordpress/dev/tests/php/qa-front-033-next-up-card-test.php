<?php
/**
 * Tests for QA-033 (PLAN §6.4 `events` › `next-up`, §2 #14 "Later this
 * season" rows): the Next up card printed the event's list fragment as it
 * was typed for Later this season ("send a DYFI report by Winlink, marked as
 * an exercise": lower case, no full stop) and then one line per more link,
 * so the Great ShakeOut card (a card from Sep 28) read "Great ShakeOut ↗"
 * under the title "Great ShakeOut", and "DYFI ↗" on a line of its own.
 *
 * A card prints the short line as a sentence (first word capitalised when it
 * is all lower case, a full stop when it ends without a mark) and makes a
 * more link inside it where its words appear (as Later this season does). A
 * more link named like the event keeps its line, named by its site
 * ("shakeout.org"), so no line repeats the title; the title stays plain, as
 * on B's cards. Other more links keep their own lines.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Visible text: the hidden "(opens …)" dropped, tags stripped, entities
 * decoded, spaces collapsed.
 *
 * @param string $html Markup.
 */
function qafront033_visible( string $html ): string {
	$html = (string) preg_replace( '/<span class="vh">.*?<\/span>/s', '', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * The card of an event, as the Next up view prints it.
 *
 * @param int  $id    Event.
 * @param bool $first The first (red) card.
 */
function qafront033_card( int $id, bool $first = false ): string {
	return \spokares_render_event_card( \spokares_event_data( $id ), $first );
}

/**
 * The inner HTML of every element of a class in a card.
 *
 * @param string $html Card.
 * @param string $tag  Tag name.
 * @param string $cls  Class.
 * @return string[]
 */
function qafront033_parts( string $html, string $tag, string $cls = '' ): array {
	$attr = '' !== $cls ? '[^>]*class="' . preg_quote( $cls, '#' ) . '"' : '[^>]*';
	preg_match_all( '#<' . $tag . $attr . '>(.*?)</' . $tag . '>#s', $html, $m );
	return $m[1];
}

/**
 * Publish an exercise with a short line and more links (removed after the test).
 *
 * @param string $title   Title.
 * @param string $summary Short line.
 * @param array  $links   More links: [ [label, url], … ].
 */
function qafront033_event( string $title, string $summary, array $links ): int {
	$id = create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'publish',
			'post_title'  => $title,
		)
	);
	update_post_meta( $id, 'spk_kind', 'exercise' );
	update_post_meta( $id, 'spk_date_mode', 'date' );
	update_post_meta( $id, 'spk_start', \spokares_add_days( \spokares_today(), 200 ) );
	update_post_meta( $id, 'spk_summary', $summary );
	update_post_meta(
		$id,
		'spk_links',
		array_map(
			static fn( $l ) => array(
				'label' => $l[0],
				'url'   => $l[1],
			),
			$links
		)
	);
	clean_post_cache( $id );
	return $id;
}

test(
	'Great ShakeOut card: the short description is a sentence with DYFI linked inside it, and no line repeats the title',
	function () {
		foreach ( array( 'anonymous', 'ares-editor', 'admin' ) as $role ) {
			as_role( $role );
			$html = qafront033_card( post_id( 'spk_event', 'shakeout-2026' ) );

			$summary = qafront033_parts( $html, 'p', 'ex-summary' );
			assert_count( 1, $summary, $role . ': one short description' );
			assert_same( 'Send a DYFI report by Winlink, marked as an exercise.', qafront033_visible( $summary[0] ), $role . ': the short description reads as a sentence' );
			assert_matches( '#<a class="ext" href="https://earthquake\.usgs\.gov/data/dyfi/">DYFI<span class="vh">#', $summary[0], $role . ': DYFI is linked inside the short description' );

			$links = qafront033_parts( $html, 'p', 'ex-link' );
			assert_same( array( 'shakeout.org' ), array_map( __NAMESPACE__ . '\qafront033_visible', $links ), $role . ': one more-link line, naming the ShakeOut site (no "Great ShakeOut" line, no "DYFI" line)' );
			assert_contains( 'href="https://www.shakeout.org/"', $links[0], $role . ': the ShakeOut link is kept' );

			$h3 = qafront033_parts( $html, 'h3' );
			assert_count( 1, $h3, $role . ': one title' );
			assert_same( 'Great ShakeOut', $h3[0], $role . ': the title, plain as on B\'s cards' );
			assert_same( 1, substr_count( qafront033_visible( $html ), 'Great ShakeOut' ), $role . ': the title is printed once' );
		}
	}
);

test(
	'SET card (control): a more link that is not in the short description and not the title keeps its own line',
	function () {
		$html  = qafront033_card( post_id( 'spk_event', 'set-2026' ), true );
		$lines = array_map( __NAMESPACE__ . '\qafront033_visible', qafront033_parts( $html, 'p', 'ex-link' ) );
		assert_same( array( 'ARRL SET forms' ), $lines, 'the ARRL SET forms line' );
		assert_same( array( 'Simulated Emergency Test' ), array_map( __NAMESPACE__ . '\qafront033_visible', qafront033_parts( $html, 'h3' ) ), 'the title' );
		assert_not_contains( '<a', qafront033_parts( $html, 'h3' )[0], 'the title is not a link' );
	}
);

test(
	'card short description: a closing mark is kept, mixed-case first words are left alone, links keep their order',
	function () {
		$id   = qafront033_event(
			'QA front drill',
			'eQSL cards for everyone. The EC asks you to:',
			array(
				array( 'Forms', 'https://example.org/forms' ),
				array( 'eQSL', 'https://example.org/eqsl' ),
				array( 'Sign-up sheet', 'https://example.org/sign-up' ),
			)
		);
		$html = qafront033_card( $id );
		$sum  = qafront033_parts( $html, 'p', 'ex-summary' );
		assert_same( 'eQSL cards for everyone. The EC asks you to:', qafront033_visible( $sum[0] ), 'no capital forced on "eQSL", no full stop after the colon' );
		assert_contains( 'href="https://example.org/eqsl"', $sum[0], 'eQSL is linked inside the line' );
		$lines = array_map( __NAMESPACE__ . '\qafront033_visible', qafront033_parts( $html, 'p', 'ex-link' ) );
		assert_same( array( 'Forms', 'Sign-up sheet' ), $lines, 'the other links keep their lines, in order' );
	}
);

test(
	'card short description: a lower-case fragment with no mark gets a capital and a full stop; one already ending in "!" is kept',
	function () {
		$a = qafront033_event( 'QA front A', 'bring a charged radio', array() );
		$b = qafront033_event( 'QA front B', 'Bring a charged radio!', array() );
		assert_same( array( 'Bring a charged radio.' ), array_map( __NAMESPACE__ . '\qafront033_visible', qafront033_parts( qafront033_card( $a ), 'p', 'ex-summary' ) ), 'fragment' );
		assert_same( array( 'Bring a charged radio!' ), array_map( __NAMESPACE__ . '\qafront033_visible', qafront033_parts( qafront033_card( $b ), 'p', 'ex-summary' ) ), 'sentence' );
	}
);

test(
	'card: a title-named more link to a mapped site names the site; an internal one reads "Details"',
	function () {
		$id    = qafront033_event(
			'QA front skywarn',
			'',
			array(
				array( 'QA front SKYWARN', 'https://www.weather.gov/crh/skywarnrecognition' ),
				array( 'QA front skywarn', set_url_scheme( home_url( '/members/documents/' ), 'https' ) ),
			)
		);
		$lines = array_map( __NAMESPACE__ . '\qafront033_visible', qafront033_parts( qafront033_card( $id ), 'p', 'ex-link' ) );
		assert_same( array( 'The National Weather Service', 'Details' ), $lines, 'the two more-link lines' );
	}
);

test(
	'Later this season (control): the ShakeOut row still reads "Great ShakeOut: send a DYFI report …" with the title linked',
	function () {
		$ev   = \spokares_event_data( post_id( 'spk_event', 'shakeout-2026' ) );
		$rest = \spokares_later_rest( $ev, \spokares_event_title_target( $ev ) );
		assert_same( ': send a DYFI report by Winlink, marked as an exercise', qafront033_visible( $rest ), 'the row keeps the list fragment after the title' );
		assert_same( 'https://www.shakeout.org/', \spokares_event_title_target( $ev ), 'the row title links to the ShakeOut site' );
	}
);
