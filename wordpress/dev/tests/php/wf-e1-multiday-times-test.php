<?php
/**
 * Regression tests for workflow review E1 (ux/workflows/content.py; PLAN
 * §3.4 events, §4.4 "end time after start time"): the event form compared
 * the start and end times alone, so a weekend event with times (a Field Day
 * or a SET weekend, Fri 6:00 PM to Sun noon) was refused with "The end time
 * must be after the start time." Start and end are a date plus a time: when
 * the Last day is later than the First day, any end time is after the start.
 * On one day an end time at or before the start is still refused.
 *
 * The public "when" of such an event printed "Oct 16–18, 6:00 PM–noon",
 * which reads as every day from 6:00 PM to noon. It now prints each time
 * with its day ("Fri, Oct 16, 6:00 PM–Sun, Oct 18, noon"; on the hub
 * "Fri 6:00 PM–Sun noon"). An end time after the start (Sat–Sun, 9:00 AM–
 * 5:00 PM) still prints as before.
 *
 * The saves use the helpers of ux-events-meetings-test.php (uxevt_click(),
 * uxevt_form(), uxevt_event()): post.php's "editpost" with what the drawn
 * form sends.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The first day with this weekday ('5' = Friday) at least $min days after
 * the site's today.
 *
 * @param string $dow Weekday, 0 (Sunday) to 6.
 * @param int    $min Days ahead.
 */
function wfe1_next( string $dow, int $min ): string {
	$day = \spokares_add_days( \spokares_today(), $min );
	while ( \spokares_date_obj( $day )->format( 'w' ) !== $dow ) {
		$day = \spokares_add_days( $day, 1 );
	}
	return $day;
}

/**
 * Add an exercise through the form with these dates and times, and click
 * Publish. Returns [event ID, the click's result].
 *
 * @param string $first   First day.
 * @param string $last    Last day ('' = none).
 * @param string $t_start Start time.
 * @param string $t_end   End time.
 */
function wfe1_add( string $first, string $last, string $t_start, string $t_end ): array {
	$id  = get_default_post_to_edit( 'spk_event', true )->ID;
	$res = uxevt_click(
		$id,
		array(
			'post_title'     => 'WF-E1 Field Day weekend',
			'spk_kind'       => 'exercise',
			'spk_date_mode'  => 'date',
			'spk_start'      => $first,
			'spk_end'        => $last,
			'spk_all_day'    => null,
			'spk_time_start' => $t_start,
			'spk_time_end'   => $t_end,
		),
		'publish'
	);
	return array( $id, $res );
}

test(
	'a weekend event from Fri 6:00 PM to Sun noon publishes with both times saved',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$fri       = wfe1_next( '5', 20 );
			$sun       = \spokares_add_days( $fri, 2 );
			[ $id, $res ] = wfe1_add( $fri, $sun, '18:00', '12:00' );
			assert_same( 'publish', get_post_status( $id ), $role . ': refused with ' . export( wp_list_pluck( $res['notices'], 'text' ) ) );
			assert_same( '18:00', (string) get_post_meta( $id, 'spk_time_start', true ), $role . ': start time' );
			assert_same( '12:00', (string) get_post_meta( $id, 'spk_time_end', true ), $role . ': end time' );
			assert_same( $sun, (string) get_post_meta( $id, 'spk_end', true ), $role . ': last day' );
			assert_not_contains( 'must be after the start time', uxevt_form( $id ), $role . ': no sentence on the form' );
		}
	}
);

test(
	'on one day an end time at or before the start is still refused (control), and so is a Last day equal to the First day',
	function () {
		as_role( 'ares-editor' );
		$day = wfe1_next( '6', 20 );
		foreach ( array( array( $day, '', '19:00', '18:00' ), array( $day, '', '18:00', '18:00' ), array( $day, $day, '19:00', '09:00' ) ) as $case ) {
			[ $id ] = wfe1_add( ...$case );
			assert_same( 'draft', get_post_status( $id ), 'refused: ' . implode( ' ', $case ) );
			assert_contains( 'The end time must be after the start time.', uxevt_form( $id ), 'the sentence: ' . implode( ' ', $case ) );
		}
	}
);

test(
	'the site prints each time with its day for Fri 6:00 PM to Sun noon; Sat–Sun 9:00 AM–5:00 PM prints as before',
	function () {
		$fri      = wfe1_next( '5', 20 );
		$sun      = \spokares_add_days( $fri, 2 );
		$id       = uxevt_event(
			'WF-E1 overnight',
			array(
				'spk_kind'       => 'exercise',
				'spk_start'      => $fri,
				'spk_end'        => $sun,
				'spk_time_start' => '18:00',
				'spk_time_end'   => '12:00',
			)
		);
		$plain    = static fn( string $s ): string => str_replace( "\u{00A0}", ' ', $s );
		$expected = \spokares_fmt_date( $fri, 'short' ) . ', 6:00 PM–' . \spokares_fmt_date( $sun, 'short' ) . ', noon';
		assert_same( $expected, $plain( \spokares_fmt_when( $id, 'card' ) ), 'card' );
		assert_same( $expected, $plain( \spokares_fmt_when( $id, 'row' ) ), 'row' );
		assert_same( 'Fri 6:00 PM–Sun noon', $plain( \spokares_fmt_when( $id, 'meta' ) ), 'hub line' );

		$sat   = wfe1_next( '6', 20 );
		$daily = uxevt_event(
			'WF-E1 daily hours',
			array(
				'spk_kind'       => 'exercise',
				'spk_start'      => $sat,
				'spk_end'        => \spokares_add_days( $sat, 1 ),
				'spk_time_start' => '09:00',
				'spk_time_end'   => '17:00',
			)
		);
		assert_same( \spokares_fmt_date_range( $sat, \spokares_add_days( $sat, 1 ) ) . ', 9:00 AM–5:00 PM', $plain( \spokares_fmt_when( $daily, 'card' ) ), 'daily hours card (control)' );
		assert_same( '9:00 AM–5:00 PM', $plain( \spokares_fmt_when( $daily, 'meta' ) ), 'daily hours hub line (control)' );
	}
);
