<?php
/**
 * Regression tests for QA-061 (PLAN §6.0 rule 2: B's pages and data.js are
 * the wording reference; §6 spk_meetings: Home line and Hub line).
 *
 * A meeting with no start time carries "time words" for Home, where the line
 * describes the recurring meeting: "Third Thursday training meeting,
 * evenings". The members hub's "Next meeting:" line names one dated
 * meeting, but spokares_meeting_hub_line() reuses the same recurring words,
 * so the hub reads "Next meeting: Thu, Oct 15, evenings, Third Thursday
 * training meeting" (e.g. /members/?today=2026-10-11 or 2027-03-15). The
 * reference prints a single dated Third Thursday as "Evening" (data.js
 * displayTime 'Evening'; site.js eventRow), so a single date reads
 * "Thu, Oct 15, evening, Third Thursday training meeting".
 *
 * Home keeps the recurring "evenings", and a meeting with a start time keeps
 * its time on the hub.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A meeting rule by id (fails the test when the seed has none).
 *
 * @param string $id Meeting id.
 */
function qa061_meeting( string $id ): array {
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}
	fail( 'no meeting ' . $id . ' in spk_meetings' );
	return array();
}

/**
 * The next real dates of a meeting from today (cancellations and moves applied).
 *
 * @param string $id Meeting id.
 * @param int    $n  How many dates.
 */
function qa061_dates( string $id, int $n ): array {
	foreach ( \spokares_next_meetings( $n ) as $item ) {
		if ( $item['meeting']['id'] === $id ) {
			return wp_list_pluck( $item['dates'], 'date' );
		}
	}
	fail( 'no upcoming dates for ' . $id );
	return array();
}

/**
 * Visible text: tags stripped, entities decoded, no-break spaces as spaces.
 *
 * @param string $html HTML or text.
 */
function qa061_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * The seeded Third Thursday meeting, checked to be a time-words meeting.
 */
function qa061_third_thursday(): array {
	$m = qa061_meeting( 'third-thursday' );
	assert_same( '', $m['start'], 'precondition: the Third Thursday meeting has no start time' );
	assert_same( 'evenings', $m['time_text'], 'precondition: the Third Thursday meeting uses the seeded time words' );
	return $m;
}

test(
	'hub line: a time-words meeting on one date reads "evening", not the recurring "evenings"',
	function () {
		$m     = qa061_third_thursday();
		$dates = array_merge( array( '2026-10-15', '2027-03-18' ), qa061_dates( 'third-thursday', 3 ) );
		foreach ( array_unique( $dates ) as $ymd ) {
			$date = qa061_plain( \spokares_fmt_date( $ymd, 'short' ) );
			$line = qa061_plain( \spokares_meeting_hub_line( $m, $ymd ) );
			assert_not_contains( 'evenings', $line, $ymd . ': the hub line names one date, so it does not use Home\'s recurring "evenings"' );
			assert_matches(
				'/^' . preg_quote( $date, '/' ) . ', evening, Third Thursday training meeting$/i',
				$line,
				$ymd . ': the hub line reads "' . $date . ', evening, Third Thursday training meeting" (got "' . $line . '")'
			);
		}
	}
);

test(
	'members hub block: "Next meeting:" for the Third Thursday reads "evening"',
	function () {
		qa061_third_thursday();
		$next = qa061_dates( 'third-thursday', 1 )[0] ?? '';
		$date = qa061_plain( \spokares_fmt_date( $next, 'short' ) );
		$html = \spokares_render_meetings(
			array(
				'view'    => 'next',
				'meeting' => 'third-thursday',
			)
		);
		assert_matches( '/<p class="m-line hub-meeting">.*?<\/p>/s', $html, 'the next-meeting view prints the hub line' );
		preg_match( '/<p class="m-line hub-meeting">(.*?)<\/p>/s', $html, $hit );
		$line = qa061_plain( $hit[1] ?? '' );
		assert_not_contains( 'evenings', $line, 'the hub\'s "Next meeting:" line does not use the recurring "evenings"' );
		assert_matches(
			'/^Next meeting: ' . preg_quote( $date, '/' ) . ', evening, Third Thursday training meeting$/i',
			$line,
			'the hub reads "Next meeting: ' . $date . ', evening, Third Thursday training meeting" (got "' . $line . '")'
		);
	}
);

test(
	'Home line: the Third Thursday keeps the recurring "evenings"',
	function () {
		$m = qa061_third_thursday();
		assert_same( 'Third Thursday training meeting, evenings', qa061_plain( \spokares_meeting_home_line( $m ) ), 'Home describes the recurring meeting' );
	}
);

test(
	'hub line: a meeting with a start time keeps its time',
	function () {
		$m    = qa061_meeting( 'workshop' );
		$date = qa061_plain( \spokares_fmt_date( '2026-10-10', 'short' ) );
		assert_same( $date . ', 9:00 AM, Second Saturday Workshop', qa061_plain( \spokares_meeting_hub_line( $m, '2026-10-10' ) ), 'the workshop hub line (PLAN §6 example)' );
	}
);
