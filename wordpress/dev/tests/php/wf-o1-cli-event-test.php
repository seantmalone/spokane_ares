<?php
/**
 * Regression tests for workflow finding O1 (ux/workflows/content.py), the
 * events part: an event's dates were changed over SSH with update_post_meta,
 * around the event form's checks. `wp spokares event set-date` runs the
 * form's own checker (spokares_event_problems()) on the event with the new
 * dates and writes only the When fields; `event cancel` ticks Cancelled;
 * `event list` lists them. The command functions are called with a
 * stand-in WP_CLI (wfo1r_cli(), in wf-o1-cli-rota-test.php).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published training event on a date, with a time.
 *
 * @param string $start First day.
 * @param array  $meta  More meta.
 */
function wfo1e_event( string $start, array $meta = array() ): int {
	as_role( 'admin' );
	$id = create_post(
		array(
			'post_type'  => 'spk_event',
			'post_title' => 'WF-O1 training night',
		)
	);
	foreach ( array_merge(
		array(
			'spk_kind'       => 'training',
			'spk_date_mode'  => 'date',
			'spk_start'      => $start,
			'spk_time_start' => '18:00',
			'spk_time_end'   => '20:00',
		),
		$meta
	) as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	clean_post_cache( $id );
	return $id;
}

test(
	'event set-date: the new dates go through the form\'s checks; saved with the sort keys and a cache purge',
	function () {
		$start = \spokares_add_days( \spokares_today(), 20 );
		$id    = wfo1e_event( $start );
		$first = \spokares_add_days( $start, 7 );
		$last  = \spokares_add_days( $start, 8 );
		as_role( 'ares-editor' );
		$purged = did_action( 'spokares_cache_purged' );
		$r      = wfo1r_cli( 'spokares_cli_event_set_date', array( (string) $id, $first, $last ) );
		assert_false( $r['exit'], $r['text'] );
		assert_contains( 'success: Saved WF-O1 training night: ' . \spokares_fmt_date_range( $first, $last ), $r['text'], 'says what it saved' );
		assert_same( array( $first, $last, $first, $last ), array( get_post_meta( $id, 'spk_start', true ), get_post_meta( $id, 'spk_end', true ), get_post_meta( $id, 'spk_sort', true ), get_post_meta( $id, 'spk_end_sort', true ) ), 'the dates and the sort keys' );
		assert_same( '18:00', get_post_meta( $id, 'spk_time_start', true ), 'the time stays' );
		assert_true( did_action( 'spokares_cache_purged' ) > $purged, 'the cache was purged' );

		$r = wfo1r_cli( 'spokares_cli_event_set_date', array( (string) $id, $first ) );
		assert_false( $r['exit'], $r['text'] );
		assert_same( '', get_post_meta( $id, 'spk_end', true ), 'no last day: a one-day event' );

		$r = wfo1r_cli( 'spokares_cli_event_set_date', array( (string) $id, $last, $first ) );
		assert_true( $r['exit'], 'a last day before the first is refused' );
		assert_contains( 'Last day: The last day is before the first day.', $r['text'], 'the form\'s sentence' );
		assert_same( $first, get_post_meta( $id, 'spk_start', true ), 'nothing saved' );

		$r = wfo1r_cli( 'spokares_cli_event_set_date', array( (string) $id, $first, $last ), array( 'dry-run' => true ) );
		assert_contains( 'log: Would save WF-O1 training night', $r['text'], 'a dry run says what would change' );
		assert_same( '', get_post_meta( $id, 'spk_end', true ), 'and saves nothing' );

		$list = wfo1r_cli( 'spokares_cli_event_list', array(), array() );
		$rows = array_column( array_values( array_filter( $list['out'], static fn( $o ) => 'table' === $o[0] ) )[0][1], 'when', 'id' );
		assert_same( \spokares_fmt_date_range( $first, '' ), $rows[ $id ] ?? '', 'list shows it with its date' );
	}
);

test(
	'event set-date: a date that has passed needs --confirm ("The date is right"), which is stored as the form stores it',
	function () {
		$id   = wfo1e_event( \spokares_add_days( \spokares_today(), 20 ) );
		$past = \spokares_add_days( \spokares_today(), -400 );
		as_role( 'ares-editor' );
		$r = wfo1r_cli( 'spokares_cli_event_set_date', array( (string) $id, $past ) );
		assert_true( $r['exit'], 'refused' );
		assert_contains( 'First day: ' . \spokares_fmt_date( $past, 'long' ) . ' has passed. Check the year, or tick the box to publish it anyway. (Add --confirm to tick that box.)', $r['text'], 'the form\'s sentence' );
		assert_not_same( $past, get_post_meta( $id, 'spk_start', true ), 'not saved' );

		$r = wfo1r_cli( 'spokares_cli_event_set_date', array( (string) $id, $past ), array( 'confirm' => true ) );
		assert_false( $r['exit'], $r['text'] );
		assert_same( $past, get_post_meta( $id, 'spk_start', true ), 'saved' );
		$confirmed = get_post_meta( $id, 'spk_confirmed', true );
		assert_same(
			\spokares_event_past_hash(
				array(
					'start' => $past,
					'end'   => '',
				)
			),
			$confirmed['past'] ?? '',
			'the confirmation, as the tick stores it'
		);
	}
);

test(
	'event cancel: ticks Cancelled (an event on a date only), --undo unticks it; needs an account that may edit the event',
	function () {
		$id = wfo1e_event( \spokares_add_days( \spokares_today(), 20 ) );
		as_role( 'subscriber' );
		$r = wfo1r_cli( 'spokares_cli_event_cancel', array( (string) $id ) );
		assert_contains( 'can’t make this change', $r['text'], 'a subscriber is refused' );
		as_anonymous();
		$r = wfo1r_cli( 'spokares_cli_event_cancel', array( (string) $id ) );
		assert_contains( 'add --user=<login>', $r['text'], 'no user is refused' );
		assert_same( '', get_post_meta( $id, 'spk_cancelled', true ), 'not cancelled' );

		as_role( 'ares-editor' );
		$r = wfo1r_cli( 'spokares_cli_event_cancel', array( (string) $id ) );
		assert_false( $r['exit'], $r['text'] );
		assert_same( '1', get_post_meta( $id, 'spk_cancelled', true ), 'cancelled' );
		$r = wfo1r_cli( 'spokares_cli_event_cancel', array( (string) $id ), array( 'undo' => true ) );
		assert_same( '', get_post_meta( $id, 'spk_cancelled', true ), 'on again' );

		update_post_meta( $id, 'spk_date_mode', 'postponed' );
		$r = wfo1r_cli( 'spokares_cli_event_cancel', array( (string) $id ) );
		assert_true( $r['exit'], 'an event without a date isn\'t called off' );
		assert_contains( 'has no date, so it can’t be called off', $r['text'], 'says why' );
		$r = wfo1r_cli( 'spokares_cli_event_cancel', array( '999999' ) );
		assert_contains( 'No event with ID "999999"', $r['text'], 'an unknown ID' );
	}
);
