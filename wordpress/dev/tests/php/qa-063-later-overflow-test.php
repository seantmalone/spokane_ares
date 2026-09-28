<?php
/**
 * Regression tests for QA-063 (PLAN §2.3 #14: "An event must never vanish
 * from Exercises because of where its date falls"; §3.5 `events` › `later`
 * "Every published upcoming event of `types` except the next-up cards").
 *
 * The members Exercises template draws "Later this season" with the block
 * attribute `limit` 12, and spokares_events() cuts the list there. With 12
 * earlier exercise, training or on-air events, every later one drops off the
 * page: the seeded State COMMEX, Washington W1AW/7, SKYWARN Recognition Day
 * and Winter Field Day have no row and no `#<slug>` anchor, so the Events
 * list's "View on site" and the save notice's "See it on Exercises & events"
 * go to an anchor that isn't there, and the save notice says "Published, but
 * no list shows it right now (it has ended, or the lists are full)".
 *
 * The page is drawn from the real template pattern (spokares/members-exercises),
 * so the block attributes are the ones the site uses. The number of added
 * events is the block's own limit plus two (14 with limit 12).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The "Later this season" block of the members Exercises pattern, parsed.
 */
function qa063_later_block(): array {
	$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( 'spokares/members-exercises' );
	if ( ! $pattern || empty( $pattern['content'] ) ) {
		fail( 'set-up: the pattern spokares/members-exercises is not registered' );
	}
	$stack = parse_blocks( $pattern['content'] );
	while ( $stack ) {
		$block = array_shift( $stack );
		if ( 'spokares/events' === ( $block['blockName'] ?? '' ) && 'later' === ( $block['attrs']['view'] ?? '' ) ) {
			return $block;
		}
		foreach ( $block['innerBlocks'] ?? array() as $inner ) {
			$stack[] = $inner;
		}
	}
	fail( 'set-up: the members Exercises pattern has no spokares/events "later" block' );
	return array();
}

/**
 * The list arguments of that block with no limit: every event the view is
 * meant to show (§3.5).
 */
function qa063_later_args_unlimited(): array {
	$attrs = qa063_later_block()['attrs'];
	return array(
		'types'     => (string) ( $attrs['types'] ?? 'exercise,training' ),
		'limit'     => 0,
		'cardTypes' => (string) ( $attrs['cardTypes'] ?? 'exercise' ),
		'cardLimit' => (int) ( $attrs['cardLimit'] ?? 2 ),
	);
}

/**
 * The /members/exercises/ page body as the site draws it (the template's
 * pattern), for the current user.
 */
function qa063_exercises_page(): string {
	return do_blocks( '<!-- wp:pattern {"slug":"spokares/members-exercises"} /-->' );
}

/**
 * Publish $count training and on-air events dated from tomorrow on, one a
 * day, all before the seeded State COMMEX (Oct 24). Returns slug => ID.
 *
 * @param int $count How many.
 */
function qa063_add_events( int $count ): array {
	$out   = array();
	$today = \spokares_today();
	for ( $i = 1; $i <= $count; $i++ ) {
		$slug = sprintf( 'qa-063-later-%02d', $i );
		$id   = create_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'publish',
				'post_title'  => sprintf( 'QA-063 later event %02d', $i ),
				'post_name'   => $slug,
			)
		);
		foreach (
			array(
				'spk_kind'      => 0 === $i % 3 ? 'on-air' : 'training',
				'spk_date_mode' => 'date',
				'spk_start'     => \spokares_add_days( $today, $i ),
				'spk_end'       => '',
				'spk_summary'   => 'filler for QA-063',
			) as $key => $value
		) {
			update_post_meta( $id, $key, $value );
		}
		clean_post_cache( $id );
		$out[ get_post_field( 'post_name', $id ) ] = $id;
	}
	return $out;
}

/**
 * The seeded events that vanished in the report, slug => title words.
 */
function qa063_seeded_later(): array {
	return array(
		'commex-2026' => 'State COMMEX',
		'w1aw7-2026'  => 'W1AW/7',
		'srd-2026'    => 'SKYWARN Recognition Day',
		'wfd-2027'    => 'Winter Field Day',
	);
}

/**
 * Does the page have an element with this id?
 *
 * @param string $html Page.
 * @param string $slug Anchor.
 */
function qa063_has_anchor( string $html, string $slug ): bool {
	return (bool) preg_match( '/\sid="' . preg_quote( esc_attr( $slug ), '/' ) . '"/', $html );
}

test(
	'Exercises (control): with the seed data every "Later this season" event has its row anchor',
	function () {
		as_anonymous();
		$limit = (int) ( qa063_later_block()['attrs']['limit'] ?? 0 );
		assert_true( $limit > 0, 'set-up: the template\'s "later" block has a limit attribute (§3.5 says 12)' );
		foreach ( array_keys( qa063_seeded_later() ) as $slug ) {
			assert_true( post_id( 'spk_event', $slug ) > 0, 'set-up: the seed has the event ' . $slug );
		}
		$html = qa063_exercises_page();
		$all  = \spokares_events( 'later', qa063_later_args_unlimited() );
		if ( count( $all ) > $limit ) {
			skip( 'the seed alone has more later events (' . count( $all ) . ') than the limit (' . $limit . '); the other tests cover that' );
		}
		foreach ( $all as $ev ) {
			assert_true( qa063_has_anchor( $html, $ev['slug'] ), 'seed only: "' . $ev['title'] . '" has the anchor #' . $ev['slug'] );
		}
	}
);

test(
	'Exercises: with more upcoming events than the "later" block limit, every one keeps a row anchor on the page',
	function () {
		$limit = (int) ( qa063_later_block()['attrs']['limit'] ?? 12 );
		$added = qa063_add_events( max( 14, $limit + 2 ) );
		$all   = \spokares_events( 'later', qa063_later_args_unlimited() );
		$want  = wp_list_pluck( $all, 'slug' );
		foreach ( array_keys( $added ) as $slug ) {
			assert_contains( $slug, $want, 'set-up: the added event ' . $slug . ' belongs in "Later this season" (not a Next up card)' );
		}
		assert_true( count( $all ) > $limit, 'set-up: more later events (' . count( $all ) . ') than the block limit (' . $limit . ')' );

		foreach ( array( 'anonymous', 'ares-editor' ) as $role ) {
			as_role( $role );
			$html    = qa063_exercises_page();
			$missing = array();
			foreach ( $all as $ev ) {
				if ( ! qa063_has_anchor( $html, $ev['slug'] ) ) {
					$missing[] = $ev['title'] . ' (#' . $ev['slug'] . ', ' . $ev['start'] . ')';
				}
			}
			assert_same( array(), $missing, $role . ': upcoming events that vanished from /members/exercises/ (PLAN §2.3 #14) with ' . count( $all ) . ' later events and limit ' . $limit );
		}
	}
);

test(
	'Exercises: the seeded State COMMEX, W1AW/7, SKYWARN Recognition Day and Winter Field Day stay reachable after 14 earlier events are added',
	function () {
		qa063_add_events( 14 );
		as_role( 'ares-editor' );
		$html = qa063_exercises_page();
		foreach ( qa063_seeded_later() as $slug => $words ) {
			$view = \spokares_site_url( '/members/exercises/', $slug );
			assert_true( qa063_has_anchor( $html, $slug ), $words . ': the Events list\'s "View on site" (' . $view . ') needs the anchor #' . $slug . ' on the page' );
		}
	}
);

test(
	'Events save notice: an upcoming event past the 12th later row is not reported as "no list shows it"',
	function () {
		qa063_add_events( 14 );
		as_role( 'ares-editor' );
		foreach ( qa063_seeded_later() as $slug => $words ) {
			$sentence = \spokares_event_places_sentence( post_id( 'spk_event', $slug ) );
			assert_not_contains( 'no list shows it', $sentence, $words . ': the notice after a save' );
			assert_contains( 'Later this season', $sentence, $words . ': the notice after a save names where it shows' );
		}
	}
);
