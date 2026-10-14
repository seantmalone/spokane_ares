<?php
/**
 * Regression tests for workflow review E7 and E3 (ux/workflows/content.py;
 * UX spec §3.1 Dashboard, PLAN §3.4 events).
 *
 * E7: the commonest edit started with "Change or cancel an event", which
 * opened the events list: a click, a scan, and a click on the name. The
 * Site tasks box now lists the next five events (after the ones happening
 * now), name and date, each name linking straight to its form, with "All
 * events" beside Add an event. Cancelled ones are listed, tagged
 * "Cancelled" (they may need taking back).
 *
 * E3: a Postponed or Date not posted yet event stays "upcoming" for ever
 * and nobody was reminded. Needs attention now has a line for published
 * ones not saved for more than 30 days, each linking to its form ("2
 * events have had no date for over 30 days: …"). As requested (a public
 * service's standing state) is not counted.
 *
 * Uses uxnav_dashboard() and uxnav_plain() from
 * ux-navigation-dashboard-public-test.php, and uxevt_event() and
 * uxevt_day() from ux-events-meetings-test.php.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Make an event look last saved this many days ago.
 *
 * @param int $id   Event.
 * @param int $days Days ago.
 */
function wfe7_age( int $id, int $days ): void {
	global $wpdb;
	$when = time() - $days * DAY_IN_SECONDS;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- test set-up; restored after the test.
	$wpdb->update(
		$wpdb->posts,
		array(
			'post_modified'     => wp_date( 'Y-m-d H:i:s', $when ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', $when ),
		),
		array( 'ID' => $id )
	);
	clean_post_cache( $id );
}

/**
 * The Exercises & Events section of the dashboard.
 */
function wfe7_events_section(): string {
	$html = uxnav_dashboard();
	if ( ! preg_match( '#<section class="spk-task">\s*<h3>Exercises &amp; Events</h3>.*?</section>#s', $html, $m ) ) {
		fail( 'no Exercises & Events section: ' . $html );
	}
	return $m[0];
}

test(
	'the Dashboard lists the next five events, each linking to its form, with All events; no Change or cancel an event button',
	function () {
		as_role( 'ares-editor' );
		$soon = uxevt_event(
			'WF-E7 soonest drill',
			array(
				'spk_kind'  => 'training',
				'spk_start' => \spokares_today(),
			)
		);
		$off  = uxevt_event(
			'WF-E7 called off',
			array(
				'spk_kind'      => 'training',
				'spk_start'     => \spokares_today(),
				'spk_cancelled' => '1',
			)
		);
		as_role( 'ares-editor' );
		$section = wfe7_events_section();
		preg_match_all( '#<li>\s*(.*?)\s*</li>#s', $section, $items );
		assert_count( 5, $items[1], 'five events' );
		$expected = array_slice(
			array_values( array_filter( \spokares_events( 'all-upcoming' ), static fn( $e ) => ! ( empty( $e['cancelled'] ) && \spokares_event_is_now( $e ) ) ) ),
			0,
			5
		);
		foreach ( $expected as $i => $ev ) {
			assert_matches( '#^<a href="[^"]*post\.php\?post=' . (int) $ev['id'] . '&(amp;|\#038;)?action=edit">#', $items[1][ $i ], 'item ' . $i . ' links to the form of ' . $ev['title'] );
			assert_contains( esc_html( str_replace( "\u{00A0}", ' ', \spokares_fmt_when( $ev, 'row' ) ) ), $items[1][ $i ], 'item ' . $i . ' has its date' );
		}
		$plain = uxnav_plain( $section );
		assert_contains( 'WF-E7 soonest drill, ' . \spokares_fmt_date( \spokares_today(), 'short' ), $plain, 'name and date' );
		assert_contains( 'WF-E7 called off, ' . \spokares_fmt_date( \spokares_today(), 'short' ) . ' · Cancelled', $plain, 'a cancelled one is tagged' );
		assert_contains( '>All events</a>', $section, 'All events' );
		assert_contains( 'edit.php?post_type=spk_event', $section, 'All events opens the list' );
		assert_not_contains( 'Change or cancel an event', $section, 'the list button is gone' );
		assert_not_contains( 'Next:', $section, 'the one-line Next is replaced by the list' );
		unset( $soon, $off );
	}
);

test(
	'an event happening now is named above the list, linked, and not repeated in it',
	function () {
		$now = uxevt_event(
			'WF-E7 running exercise',
			array(
				'spk_kind'  => 'exercise',
				'spk_start' => uxevt_day( -1 ),
				'spk_end'   => uxevt_day( 1 ),
			)
		);
		as_role( 'ares-editor' );
		$section = wfe7_events_section();
		assert_matches( '#Happening now: <a href="[^"]*post=' . $now . '&(amp;|\#038;)?action=edit">WF-E7 running exercise</a>#', $section, 'linked' );
		preg_match( '#<ul class="spk-event-list">.*?</ul>#s', $section, $list );
		assert_not_contains( 'WF-E7 running exercise', $list[0] ?? '', 'not in the list' );
	}
);

test(
	'Needs attention names published events Postponed or Date not posted yet and not saved for over 30 days, each linked',
	function () {
		$old_postponed = uxevt_event(
			'WF-E3 postponed drill',
			array(
				'spk_kind'      => 'training',
				'spk_date_mode' => 'postponed',
			)
		);
		$old_undated   = uxevt_event(
			'WF-E3 undated parade',
			array(
				'spk_kind'      => 'public-service',
				'spk_date_mode' => 'not-posted',
			)
		);
		$fresh         = uxevt_event(
			'WF-E3 fresh postponed',
			array(
				'spk_kind'      => 'training',
				'spk_date_mode' => 'postponed',
			)
		);
		$requested     = uxevt_event(
			'WF-E3 as requested',
			array(
				'spk_kind'      => 'public-service',
				'spk_date_mode' => 'as-requested',
			)
		);
		$draft         = uxevt_event(
			'WF-E3 draft postponed',
			array(
				'spk_kind'      => 'training',
				'spk_date_mode' => 'postponed',
			),
			'draft'
		);
		foreach ( array( $old_postponed, $old_undated, $requested, $draft ) as $id ) {
			wfe7_age( $id, 45 );
		}
		wfe7_age( $fresh, 10 );

		as_role( 'ares-editor' );
		$html = uxnav_dashboard();
		if ( ! preg_match( '#<li>(\d+ events? ha(?:s|ve) had no date for over 30 days:.*?)</li>#s', $html, $m ) ) {
			fail( 'no Needs attention line for undated events: ' . $html );
		}
		$line = $m[1];
		assert_matches( '#<a href="[^"]*post=' . $old_postponed . '&(amp;|\#038;)?action=edit">WF-E3 postponed drill</a> \(Postponed\)#', $line, 'the postponed one, linked' );
		assert_matches( '#<a href="[^"]*post=' . $old_undated . '&(amp;|\#038;)?action=edit">WF-E3 undated parade</a> \(Date not posted yet\)#', $line, 'the undated one, linked' );
		foreach ( array( 'WF-E3 fresh postponed', 'WF-E3 as requested', 'WF-E3 draft postponed' ) as $name ) {
			assert_not_contains( $name, $line, 'not listed: ' . $name );
		}
		assert_true( strpos( $html, 'spk-attention' ) < strpos( $html, '<section class="spk-task">' ), 'in Needs attention, above the sections' );

		// Saving it (a new date, or just a save) takes it off the line.
		wp_update_post( array( 'ID' => $old_postponed ) );
		assert_not_contains( 'WF-E3 postponed drill', uxnav_dashboard(), 'saved: off the line' );
	}
);

test(
	'the undated-events line is for editors who can change events; none when nothing is old (control)',
	function () {
		as_role( 'ares-editor' );
		assert_not_contains( 'had no date for over 30 days', uxnav_dashboard(), 'the seed\'s undated events are fresh' );
	}
);
