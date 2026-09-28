<?php
/**
 * Regression tests for QA-024 (PLAN §3.4 Regular meetings, §4.4 guard
 * rails: "Dates and times | events, meetings | block"; a refused field is
 * not saved, and the typed text comes back outlined with one sentence).
 *
 * Regular meetings › "Moved to" refused only an unparseable date or the
 * row's own date. A date before today, or another scheduled date of the
 * same meeting, was saved with "Saved. See it on Home", and Home then
 * published wrong meeting information: a workshop moved to a past date
 * simply vanished (Home skipped to the following month with no word of the
 * missing date), and a Third Thursday moved onto its own earlier (cancelled)
 * date showed "Next: Thu, Oct 15 (moved from Nov 19)" although Oct 15 was
 * cancelled.
 *
 * Each save posts exactly what the Regular meetings form posts for one row
 * (the row's change fingerprint, Cancelled, Moved to and Note).
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
function qa024_meeting( string $id ): array {
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}
	fail( 'no meeting ' . $id . ' in spk_meetings' );
	return array();
}

/**
 * The rule dates the Regular meetings screen lists for a meeting.
 *
 * @param string $id Meeting id.
 */
function qa024_dates( string $id ): array {
	$dates = \spokares_meeting_admin_dates( qa024_meeting( $id ) );
	if ( count( $dates ) < 3 ) {
		fail( $id . ': expected at least three dates on the screen, got ' . implode( ', ', $dates ) );
	}
	return $dates;
}

/**
 * One posted row of the form, as the browser sends it.
 *
 * @param string $mid       Meeting id.
 * @param string $date      Rule date.
 * @param string $moved     Moved to.
 * @param bool   $cancelled Cancelled ticked.
 * @param string $note      Note.
 */
function qa024_row( string $mid, string $date, string $moved = '', bool $cancelled = false, string $note = '' ): array {
	$row = array(
		'h'     => \spokares_change_hash( \spokares_meeting_change( $mid, $date ) ),
		'moved' => $moved,
		'note'  => $note,
	);
	if ( $cancelled ) {
		$row['cancelled'] = '1';
	}
	return $row;
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa024_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Save the Regular meetings form with these rows; return the queued notices
 * and the held input (spokares_retained()).
 *
 * @param array $m Posted m[meeting][date] rows.
 */
function qa024_save( array $m ): array {
	qa024_notices();
	$res = post_form( 'spokares_save_meetings', array( 'm' => $m ) );
	assert_contains( 'page=spokares-meetings', (string) $res['redirect'], 'the save redirects back to Regular meetings' );
	\spokares_opt_flush();
	return array(
		'notices'  => qa024_notices(),
		'retained' => \spokares_retained( 'spokares-meetings' ),
	);
}

/**
 * Assert a row was refused: nothing stored for it, no success notice, one
 * error notice naming the meeting, the typed date held back for the form.
 *
 * @param array  $out   qa024_save() result.
 * @param string $mid   Meeting id.
 * @param string $date  Rule date.
 * @param string $moved The typed Moved to date.
 * @param string $msg   Message prefix.
 */
function qa024_assert_refused( array $out, string $mid, string $date, string $moved, string $msg ): void {
	$c = \spokares_meeting_change( $mid, $date );
	assert_true( null === $c, $msg . ': nothing is stored for ' . $date . ' (stored: ' . wp_json_encode( $c ) . ')' );

	$types = wp_list_pluck( $out['notices'], 'type' );
	$texts = wp_list_pluck( $out['notices'], 'text' );
	assert_not_contains( 'success', $types, $msg . ': no "Saved." notice (' . implode( ' | ', $texts ) . ')' );
	$errors = array_values( wp_list_pluck( array_filter( $out['notices'], static fn( $n ) => 'error' === $n['type'] ), 'text' ) );
	assert_count( 1, $errors, $msg . ': one error notice (' . implode( ' | ', $texts ) . ')' );
	assert_contains( qa024_meeting( $mid )['name'], (string) ( $errors[0] ?? '' ), $msg . ': the notice names the meeting' );

	$key = $mid . '|' . $date;
	assert_true( isset( $out['retained']['errors'][ $key ] ), $msg . ': the row is outlined (held errors: ' . implode( ', ', array_keys( $out['retained']['errors'] ) ) . ')' );
	assert_same( $moved, (string) ( $out['retained']['values'][ $key ]['moved'] ?? '' ), $msg . ': the typed date is kept in the box' );
}

test(
	'Regular meetings: a Moved to date before today is not saved',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$date = qa024_dates( 'workshop' )[1];
			$past = \spokares_add_days( \spokares_today(), -30 );
			$out  = qa024_save( array( 'workshop' => array( $date => qa024_row( 'workshop', $date, $past ) ) ) );
			qa024_assert_refused( $out, 'workshop', $date, $past, $role . ': ' . $date . ' moved to ' . $past );

			// Home must still list the date as it was (not silently skip it).
			$next = \spokares_next_meetings( 3, \spokares_add_days( $date, -1 ) );
			foreach ( $next as $item ) {
				if ( 'workshop' === $item['meeting']['id'] ) {
					assert_same( $date, $item['dates'][0]['date'] ?? '', $role . ': Home still shows the workshop on ' . $date );
				}
			}
			\spokares_unlock_option( 'spk_meetings' );
		}
	}
);

test(
	'Regular meetings: a Moved to date of long ago (2025-01-01) is not saved',
	function () {
		as_role( 'ares-editor' );
		$date = qa024_dates( 'workshop' )[1];
		$out  = qa024_save( array( 'workshop' => array( $date => qa024_row( 'workshop', $date, '2025-01-01' ) ) ) );
		qa024_assert_refused( $out, 'workshop', $date, '2025-01-01', $date . ' moved to 2025-01-01' );
	}
);

test(
	'Regular meetings: a Moved to date on another scheduled date of the same meeting is not saved',
	function () {
		as_role( 'ares-net' );
		$dates = qa024_dates( 'third-thursday' );
		$out   = qa024_save( array( 'third-thursday' => array( $dates[1] => qa024_row( 'third-thursday', $dates[1], $dates[0] ) ) ) );
		qa024_assert_refused( $out, 'third-thursday', $dates[1], $dates[0], $dates[1] . ' moved onto ' . $dates[0] );
	}
);

test(
	'Regular meetings: moving a date onto an earlier date cancelled in the same save is refused, the cancellation is saved',
	function () {
		as_role( 'ares-editor' );
		$dates = qa024_dates( 'third-thursday' );
		$out   = qa024_save(
			array(
				'third-thursday' => array(
					$dates[0] => qa024_row( 'third-thursday', $dates[0], '', true ),
					$dates[1] => qa024_row( 'third-thursday', $dates[1], $dates[0] ),
				),
			)
		);
		$cancel = \spokares_meeting_change( 'third-thursday', $dates[0] );
		assert_same( 'cancelled', (string) ( $cancel['kind'] ?? '' ), $dates[0] . ' is cancelled' );
		$c = \spokares_meeting_change( 'third-thursday', $dates[1] );
		assert_true( null === $c, $dates[1] . ' moved onto the cancelled ' . $dates[0] . ' is not stored (stored: ' . wp_json_encode( $c ) . ')' );
		$errors = array_values( wp_list_pluck( array_filter( $out['notices'], static fn( $n ) => 'error' === $n['type'] ), 'text' ) );
		assert_count( 1, $errors, 'one error notice (' . implode( ' | ', wp_list_pluck( $out['notices'], 'text' ) ) . ')' );
		assert_true( isset( $out['retained']['errors'][ 'third-thursday|' . $dates[1] ] ), 'the refused row is outlined' );
	}
);

test(
	'Regular meetings: a Moved to date later than today and off the schedule is still saved',
	function () {
		as_role( 'ares-editor' );
		$date  = qa024_dates( 'workshop' )[1];
		$moved = \spokares_add_days( $date, 7 );
		$out   = qa024_save( array( 'workshop' => array( $date => qa024_row( 'workshop', $date, $moved, false, 'Hall booked' ) ) ) );
		$c     = \spokares_meeting_change( 'workshop', $date );
		assert_same( 'moved', (string) ( $c['kind'] ?? '' ), 'the move is stored' );
		assert_same( $moved, (string) ( $c['new_date'] ?? '' ), 'the new date is stored' );
		assert_contains( 'success', wp_list_pluck( $out['notices'], 'type' ), 'a "Saved." notice' );
		assert_count( 0, $out['retained']['errors'], 'nothing is outlined' );
	}
);

test(
	'Regular meetings: an unchanged row whose stored Moved to date has since passed does not block the save',
	function () {
		as_role( 'ares-editor' );
		$dates = qa024_dates( 'workshop' );

		// Stored earlier, before today: the first listed date was moved to a date that is now past.
		$past = \spokares_add_days( \spokares_today(), -3 );
		$data = \spokares_opt( 'spk_meetings' );

		$data['changes'][] = array(
			'date'     => $dates[0],
			'meeting'  => 'workshop',
			'kind'     => 'moved',
			'new_date' => $past,
			'note'     => '',
		);
		update_option( 'spk_meetings', $data );
		\spokares_opt_flush();

		// The whole form posts every row: the old move unchanged, and a new cancellation.
		$out = qa024_save(
			array(
				'workshop' => array(
					$dates[0] => qa024_row( 'workshop', $dates[0], $past ),
					$dates[1] => qa024_row( 'workshop', $dates[1], '', true ),
				),
			)
		);
		assert_same( 'cancelled', (string) ( \spokares_meeting_change( 'workshop', $dates[1] )['kind'] ?? '' ), $dates[1] . ' is cancelled' );
		assert_same( $past, (string) ( \spokares_meeting_change( 'workshop', $dates[0] )['new_date'] ?? '' ), 'the earlier move is kept' );
		assert_count( 0, $out['retained']['errors'], 'the unchanged row is not outlined (' . implode( ' | ', wp_list_pluck( $out['notices'], 'text' ) ) . ')' );
	}
);
