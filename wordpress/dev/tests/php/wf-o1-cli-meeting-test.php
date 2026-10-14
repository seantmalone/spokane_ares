<?php
/**
 * Regression tests for workflow finding O1 (ux/workflows/content.py), the
 * meetings part: a cancelled or moved meeting went in over SSH around the
 * checks of Cancel or Move a Meeting. Now `wp spokares meeting
 * cancel|move|note|clear|extra|show` go through the screen's own check and
 * write (spokares_meetings_check(), spokares_meetings_write()). The command
 * functions are called with a stand-in WP_CLI (wfo1r_cli(), in
 * wf-o1-cli-rota-test.php).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A shown monthly meeting and a hidden one, with no changes; the shown
 * one's next two dates.
 *
 * @return string[]
 */
function wfo1m_meetings(): array {
	update_option(
		'spk_meetings',
		array(
			'meetings' => array(
				array(
					'id'        => 'workshop',
					'name'      => 'Second Saturday Workshop',
					'nth'       => array( 2 ),
					'weekday'   => 6,
					'start'     => '09:00',
					'end'       => '12:00',
					'show_home' => true,
					'active'    => true,
				),
				array(
					'id'        => 'board',
					'name'      => 'Board meeting',
					'nth'       => array( 1 ),
					'weekday'   => 1,
					'start'     => '18:00',
					'show_home' => false,
					'active'    => false,
				),
			),
			'changes'  => array(),
		)
	);
	\spokares_opt_flush();
	$dates = \spokares_meeting_admin_dates( \spokares_opt( 'spk_meetings' )['meetings'][0] );
	return array( $dates[0], $dates[1] );
}

/**
 * The stored changes, read fresh: "{meeting}|{date}" => change.
 */
function wfo1m_changes(): array {
	\spokares_opt_flush();
	wp_cache_delete( 'alloptions', 'options' );
	$out = array();
	foreach ( \spokares_opt( 'spk_meetings' )['changes'] as $c ) {
		$out[ $c['meeting'] . '|' . $c['date'] ] = $c;
	}
	return $out;
}

test(
	'meeting cancel, move, note and clear: saved as Cancel or Move saves them, with its notice words',
	function () {
		list( $a, $b ) = wfo1m_meetings();
		as_role( 'ares-editor' );
		$purged = did_action( 'spokares_cache_purged' );
		$r      = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $a ), array( 'note' => 'Thanksgiving week' ) );
		assert_false( $r['exit'], $r['text'] );
		assert_contains( 'success: Saved: ' . \spokares_fmt_date( $a, 'short' ) . ' cancelled.', $r['text'], 'the screen\'s words' );
		$c = wfo1m_changes()[ 'workshop|' . $a ];
		assert_same( array( 'cancelled', 'Thanksgiving week' ), array( $c['kind'], $c['note'] ), 'stored' );
		assert_true( did_action( 'spokares_cache_purged' ) > $purged, 'the cache was purged' );

		// By name, any case; moved to a later day, the note kept.
		$to = \spokares_add_days( $b, 1 );
		$r  = wfo1r_cli( 'spokares_cli_meeting_move', array( 'second saturday workshop', $a, $to ) );
		assert_false( $r['exit'], $r['text'] );
		$c = wfo1m_changes()[ 'workshop|' . $a ];
		assert_same( array( 'moved', $to, 'Thanksgiving week' ), array( $c['kind'], $c['new_date'], $c['note'] ), 'moved, the note kept' );

		$r = wfo1r_cli( 'spokares_cli_meeting_note', array( 'workshop', $b, 'Starts at 10:00 AM this time' ) );
		assert_false( $r['exit'], $r['text'] );
		assert_contains( \spokares_fmt_date( $b, 'short' ) . ' note posted', $r['text'], 'a note on its own' );
		assert_same( 'note', wfo1m_changes()[ 'workshop|' . $b ]['kind'], 'the meeting is on, with a note' );

		$r = wfo1r_cli( 'spokares_cli_meeting_clear', array( 'workshop', $a ) );
		assert_contains( \spokares_fmt_date( $a, 'short' ) . ' is back on', $r['text'], 'back on' );
		$r = wfo1r_cli( 'spokares_cli_meeting_clear', array( 'workshop', $b ) );
		assert_contains( \spokares_fmt_date( $b, 'short' ) . ' note removed', $r['text'], 'the note removed' );
		assert_same( array(), wfo1m_changes(), 'no changes left' );
		$r = wfo1r_cli( 'spokares_cli_meeting_clear', array( 'workshop', $b ) );
		assert_contains( 'Nothing changed, so nothing was saved.', $r['text'], 'nothing to clear' );
	}
);

test(
	'meeting: refused with the screen\'s sentences (a past or own "Moved to" date, a phone number without --confirm, a never-publish word), and dates and meetings the screen doesn\'t list',
	function () {
		list( $a, $b ) = wfo1m_meetings();
		as_role( 'ares-editor' );
		$r = wfo1r_cli( 'spokares_cli_meeting_move', array( 'workshop', $a, \spokares_add_days( \spokares_today(), -3 ) ) );
		assert_true( $r['exit'], 'a past Moved to is refused' );
		assert_contains( '“Moved to” must be today or later.', $r['text'], 'the screen\'s sentence' );
		$r = wfo1r_cli( 'spokares_cli_meeting_move', array( 'workshop', $a, $b ) );
		assert_contains( '“Moved to” is already one of this meeting’s dates.', $r['text'], 'its own date' );

		$r = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $a ), array( 'note' => 'Questions: 509-555-1234' ) );
		assert_true( $r['exit'], 'a phone number needs --confirm' );
		assert_contains( 'It has a phone number. Take it out, or tick the box if it’s a public agency number. (Add --confirm to tick that box.)', $r['text'], 'the sentence' );
		assert_contains( 'The rest was saved', $r['text'], 'the cancellation is saved without the note, as on the screen' );
		assert_same( '', wfo1m_changes()[ 'workshop|' . $a ]['note'], 'no note' );
		$r = wfo1r_cli(
			'spokares_cli_meeting_cancel',
			array( 'workshop', $a ),
			array(
				'note'    => 'Questions: 509-555-1234',
				'confirm' => true,
			)
		);
		assert_false( $r['exit'], $r['text'] );
		assert_same( 'Questions: 509-555-1234', wfo1m_changes()[ 'workshop|' . $a ]['note'], '--confirm publishes it' );

		$r = wfo1r_cli( 'spokares_cli_meeting_note', array( 'workshop', $b, 'On the talkgroup' ), array( 'confirm' => true ) );
		assert_true( $r['exit'], 'never-publish words are refused' );
		assert_contains( 'It mentions a talkgroup, which we never publish.', $r['text'], 'the sentence' );
		assert_false( isset( wfo1m_changes()[ 'workshop|' . $b ] ), 'nothing saved' );

		$off = \spokares_add_days( $a, 1 );
		$r   = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $off ) );
		assert_contains( 'isn’t one of the coming dates of Second Saturday Workshop', $r['text'], 'not one of its dates' );
		$r = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'board', $a ) );
		assert_contains( 'No meeting called "board". Use one of: workshop (Second Saturday Workshop).', $r['text'], 'a hidden meeting\'s dates aren\'t on the screen either' );

		as_role( 'subscriber' );
		$r = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $b ) );
		assert_contains( 'can’t make this change', $r['text'], 'a subscriber is refused' );
		as_anonymous();
		$r = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $b ) );
		assert_contains( 'add --user=<login>', $r['text'], 'no user is refused' );
		$r = wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $b ), array( 'dry-run' => true ) );
		assert_contains( 'log: Would save: ' . \spokares_fmt_date( $b, 'short' ) . ' cancelled.', $r['text'], 'a dry run says what would change' );
		assert_false( isset( wfo1m_changes()[ 'workshop|' . $b ] ), 'and saves nothing' );
	}
);

test(
	'meeting extra: a one-off date through "Add a one-off date"\'s checks; clear removes it; show lists it',
	function () {
		list( $a ) = wfo1m_meetings();
		as_role( 'ares-net' );
		$day = \spokares_add_days( $a, 2 );
		$r   = wfo1r_cli( 'spokares_cli_meeting_extra', array( 'other', $day ), array( 'start' => '6pm' ) );
		assert_true( $r['exit'], 'another meeting needs its name' );
		assert_contains( 'The one-off date, Name: Type the name of the meeting.', $r['text'], 'the screen\'s sentence' );
		$r = wfo1r_cli( 'spokares_cli_meeting_extra', array( 'workshop', $a ) );
		assert_contains( 'That is already one of this meeting’s regular dates.', $r['text'], 'a regular date' );
		$r = wfo1r_cli(
			'spokares_cli_meeting_extra',
			array( 'other', $day ),
			array(
				'name'  => 'Holiday potluck',
				'start' => '18:00',
				'end'   => '5:00 PM',
			)
		);
		assert_contains( 'The end time must be after the start time.', $r['text'], 'the times' );

		$r = wfo1r_cli(
			'spokares_cli_meeting_extra',
			array( 'other', $day ),
			array(
				'name'  => 'Holiday potluck',
				'start' => '6pm',
				'end'   => '8:30 PM',
				'note'  => 'Bring a dish',
			)
		);
		assert_false( $r['exit'], $r['text'] );
		assert_contains( 'Holiday potluck on ' . \spokares_fmt_date( $day, 'short' ) . ' added', $r['text'], 'the screen\'s words' );
		$c = wfo1m_changes()[ '_other|' . $day ];
		assert_same( array( 'extra', '18:00', '20:30', 'Holiday potluck', 'Bring a dish' ), array( $c['kind'], $c['start'], $c['end'], $c['name'], $c['note'] ), 'stored, the times as the form sends them' );
		$r = wfo1r_cli( 'spokares_cli_meeting_extra', array( 'other', $day ), array( 'name' => 'Holiday potluck' ) );
		assert_contains( 'That date is already listed under One-off dates.', $r['text'], 'twice' );

		$r     = wfo1r_cli( 'spokares_cli_meeting_show', array(), array() );
		$table = array_values( array_filter( $r['out'], static fn( $o ) => 'table' === $o[0] ) )[0][1];
		$rows  = array_values( array_filter( $table, static fn( $row ) => $row['date'] === $day ) );
		assert_same( 'one-off', $rows[0]['status'] ?? '', 'show lists the one-off date' );
		assert_same( 'Holiday potluck', $rows[0]['name'] ?? '', 'by its name' );

		$r = wfo1r_cli( 'spokares_cli_meeting_clear', array( 'other', $day ) );
		assert_contains( 'Holiday potluck on ' . \spokares_fmt_date( $day, 'short' ) . ' removed', $r['text'], 'removed' );
		assert_same( array(), wfo1m_changes(), 'gone' );
	}
);

test(
	'meeting: the form still saves through the same check (the screen and the command store the same change)',
	function () {
		list( $a, $b ) = wfo1m_meetings();
		as_role( 'ares-editor' );
		post_form(
			'spokares_save_meetings',
			array(
				'm' => array(
					'workshop' => array(
						$a => array(
							'h'         => \spokares_change_hash( null ),
							'cancelled' => '1',
							'note'      => 'Snow',
						),
					),
				),
			)
		);
		\spokares_unlock_option( 'spk_meetings' ); // The form's save keeps it to the end of the request.
		wfo1r_cli( 'spokares_cli_meeting_cancel', array( 'workshop', $b ), array( 'note' => 'Snow' ) );
		$changes = wfo1m_changes();
		assert_same( array_merge( $changes[ 'workshop|' . $a ], array( 'date' => $b ) ), $changes[ 'workshop|' . $b ], 'the same change' );
	}
);
