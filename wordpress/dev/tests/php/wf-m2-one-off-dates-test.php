<?php
/**
 * Regression tests for workflow finding M2 (ux/workflows/content.py): there
 * was nowhere to put an extra or special meeting. A change could attach only
 * to a regular date of a shown meeting, so an annual meeting, a holiday
 * potluck on another day, or a date for a hidden meeting had no home.
 *
 * Now Cancel or Move a Meeting has "Add a one-off date" (kind 'extra' in
 * spk_meetings['changes']: date, start, end, note, and a name for another
 * meeting), Home and the For members page list it, and a Remove tick takes
 * it away.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A shown monthly meeting and a hidden one, with no changes.
 */
function wfm2_meetings(): void {
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
}

/**
 * A day in the coming week that isn't a regular date of the workshop.
 *
 * @param int $from Days from today to start looking.
 */
function wfm2_day( int $from = 3 ): string {
	$m = \spokares_opt( 'spk_meetings' )['meetings'][0];
	for ( $i = $from; $i < $from + 10; $i++ ) {
		$d = \spokares_add_days( \spokares_today(), $i );
		if ( ! \spokares_meeting_on( $m, $d ) ) {
			return $d;
		}
	}
	fail( 'no free day' );
	return '';
}

/**
 * Save Cancel or Move with these fields; return the notices and held input.
 *
 * @param array $fields Form fields (extra, extra_remove, m).
 */
function wfm2_save( array $fields ): array {
	delete_transient( 'spokares_notices_' . get_current_user_id() );
	$res = post_form( 'spokares_save_meetings', $fields );
	assert_same( null, $res['die'], 'the save was refused' );
	\spokares_unlock_option( 'spk_meetings' );
	\spokares_opt_flush();
	$notices = get_transient( 'spokares_notices_' . get_current_user_id() );
	delete_transient( 'spokares_notices_' . get_current_user_id() );
	return array(
		'notices'  => is_array( $notices ) ? $notices : array(),
		'retained' => \spokares_retained( 'spokares-meetings' ),
	);
}

/**
 * Text as a reader sees it.
 *
 * @param string $html HTML.
 */
function wfm2_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( (string) preg_replace( '/<(span|li|p|strong|b)\b/', ' <$1', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

test(
	'Add a one-off date under a meeting: it is stored, named in the notice, and listed on Home and the For members page',
	function () {
		as_role( 'ares-editor' );
		wfm2_meetings();
		$day = wfm2_day();
		$out = wfm2_save(
			array(
				'extra' => array(
					'meeting' => 'workshop',
					'date'    => $day,
					'start'   => '18:00',
					'end'     => '20:00',
					'note'    => 'Holiday potluck',
				),
			)
		);
		assert_same( array( 'success' ), wp_list_pluck( $out['notices'], 'type' ), 'one notice' );
		assert_same( 'Saved: Second Saturday Workshop on ' . \spokares_fmt_date( $day, 'short' ) . ' added.', $out['notices'][0]['text'], 'the notice' );
		$extras = \spokares_meeting_extras();
		assert_count( 1, $extras, 'one one-off date' );
		assert_same( array( $day, 'workshop', 'extra', '18:00', '20:00', 'Holiday potluck' ), array( $extras[0]['date'], $extras[0]['meeting'], $extras[0]['kind'], $extras[0]['start'], $extras[0]['end'], $extras[0]['note'] ), 'stored as typed' );
		assert_same( null, \spokares_meeting_change( 'workshop', $day ), 'it is not a change to a regular date' );

		$item = \spokares_next_meetings( 1 )[0];
		assert_same( 'workshop', $item['meeting']['id'], 'the workshop item' );
		assert_same( $day, $item['extras'][0]['date'] ?? '', 'carries its one-off date' );
		assert_true( (bool) array_filter( $item['changes'], static fn( $c ) => 'extra' === $c['kind'] ), 'and lists it with the changes' );
		assert_not_same( $day, $item['dates'][0]['date'], 'the regular Next date is unchanged' );

		$home = wfm2_plain( \spokares_render_meetings( array( 'view' => 'home' ) ) );
		assert_contains( 'Extra: ' . \spokares_fmt_date( $day, 'short' ) . ', 6:00–8:00 PM, Holiday potluck', $home, 'Home' );
		$hub = wfm2_plain( \spokares_render_meetings( array( 'view' => 'next' ) ) );
		assert_contains( 'Extra: ' . \spokares_fmt_date( $day, 'short' ) . ', 6:00–8:00 PM, Second Saturday Workshop. Holiday potluck.', $hub, 'For members' );

		// The screen lists it with a Remove tick; ticking it takes it away.
		$res = call_request(
			'GET',
			array( 'page' => 'spokares-meetings' ),
			array(),
			static function () {
				\spokares_meetings_page();
			}
		);
		assert_contains( 'name="extra_remove[workshop|' . $day . ']"', (string) $res['output'], 'the Remove tick' );
		assert_contains( '<legend>Add a one-off date</legend>', (string) $res['output'], 'the Add fields' );
		$out = wfm2_save( array( 'extra_remove' => array( 'workshop|' . $day => '1' ) ) );
		assert_same( array( 'Saved: Second Saturday Workshop on ' . \spokares_fmt_date( $day, 'short' ) . ' removed.' ), wp_list_pluck( $out['notices'], 'text' ), 'the notice' );
		assert_count( 0, \spokares_meeting_extras(), 'removed' );
		assert_not_contains( 'Extra:', wfm2_plain( \spokares_render_meetings( array( 'view' => 'home' ) ) ), 'and off Home' );
	}
);

test(
	'a one-off date for another meeting (a name) or a hidden one is listed as its own line',
	function () {
		as_role( 'ares-editor' );
		wfm2_meetings();
		$day   = wfm2_day();
		$later = wfm2_day( 20 );
		wfm2_save(
			array(
				'extra' => array(
					'meeting' => \spokares_meeting_other_id(),
					'name'    => 'Annual meeting',
					'date'    => $day,
					'start'   => '19:00',
				),
			)
		);
		wfm2_save(
			array(
				'extra' => array(
					'meeting' => 'board',
					'date'    => $later,
					'note'    => 'Budget vote',
				),
			)
		);
		assert_count( 2, \spokares_meeting_extras(), 'both stored' );
		$home = wfm2_plain( \spokares_render_meetings( array( 'view' => 'home' ) ) );
		assert_contains( 'Annual meeting On ' . \spokares_fmt_date( $day, 'short' ) . ', 7:00 PM', $home, 'another meeting, with its name' );
		assert_contains( 'Board meeting On ' . \spokares_fmt_date( $later, 'short' ) . ', Budget vote', $home, 'a hidden meeting' );
		$hub = wfm2_plain( \spokares_render_meetings( array( 'view' => 'next' ) ) );
		assert_contains( 'Extra: ' . \spokares_fmt_date( $day, 'short' ) . ', 7:00 PM, Annual meeting.', $hub, 'For members, within two weeks' );
		assert_contains( 'Next meeting: ', $hub, 'the regular next meeting is still named' );

		// The sanitiser keeps the kind and its fields.
		$clean = \spokares_sanitize_opt_meetings( get_option( 'spk_meetings' ) );
		$other = array_values( array_filter( $clean['changes'], static fn( $c ) => '_other' === $c['meeting'] ) )[0] ?? array();
		assert_same( array( 'extra', 'Annual meeting', '19:00', '' ), array( $other['kind'] ?? '', $other['name'] ?? '', $other['start'] ?? '', $other['end'] ?? '' ), 'sanitised' );
	}
);

test(
	'a one-off date is refused with a sentence: in the past, on a regular date, twice, an end with no start, a phone number, no name',
	function () {
		as_role( 'ares-editor' );
		wfm2_meetings();
		$m     = \spokares_opt( 'spk_meetings' )['meetings'][0];
		$rule  = \spokares_meeting_rule_dates( $m, \spokares_today(), \spokares_add_days( \spokares_today(), 60 ) )[0];
		$day   = wfm2_day();
		$base  = array(
			'meeting' => 'workshop',
			'date'    => $day,
		);
		$cases = array(
			array( array( 'date' => \spokares_add_days( \spokares_today(), -3 ) ), 'extra', 'The date must be today or later.' ),
			array( array( 'date' => $rule ), 'extra', 'That is already one of this meeting’s regular dates. Use its row above to cancel it, move it or add a note.' ),
			array( array( 'end' => '20:00' ), 'extra', 'An end time needs a start time. Type the start, or clear the end.' ),
			array( array( 'meeting' => '' ), 'extra', 'Pick the meeting, or Another meeting and type its name.' ),
			array( array( 'meeting' => '_other' ), 'extra-name', 'Type the name of the meeting.' ),
			array( array( 'note' => str_repeat( 'x', 121 ) ), 'extra-note', 'Keep it to 120 characters.' ),
		);
		foreach ( $cases as $case ) {
			$out = wfm2_save( array( 'extra' => array_merge( $base, $case[0] ) ) );
			assert_same( $case[2], $out['retained']['errors'][ $case[1] ] ?? '', 'the sentence for ' . wp_json_encode( $case[0] ) );
			assert_same( array( 'Not saved: the one-off date (outlined in red).' ), wp_list_pluck( $out['notices'], 'text' ), 'the notice for ' . wp_json_encode( $case[0] ) );
			assert_count( 0, \spokares_meeting_extras(), 'nothing stored for ' . wp_json_encode( $case[0] ) );
		}
		assert_same( str_repeat( 'x', 121 ), $out['retained']['values']['extra']['note'] ?? '', 'the typing is kept for the form' );

		// A phone number needs the Publish it tick.
		$out = wfm2_save( array( 'extra' => array_merge( $base, array( 'note' => 'Call 509-555-0100 for the gate code' ) ) ) );
		assert_true( isset( $out['retained']['errors']['extra-confirm'] ), 'the Publish it tick is offered' );
		assert_count( 0, \spokares_meeting_extras(), 'not stored yet' );
		$out = wfm2_save(
			array(
				'extra' => array_merge(
					$base,
					array(
						'note'    => 'Call 509-555-0100 for the gate code',
						'confirm' => '1',
					)
				),
			)
		);
		assert_count( 1, \spokares_meeting_extras(), 'stored after the tick' );

		// The same date again.
		$out = wfm2_save( array( 'extra' => $base ) );
		assert_same( 'That date is already listed under One-off dates.', $out['retained']['errors']['extra'] ?? '', 'twice' );
	}
);

test(
	'Meeting Schedule: a pattern change that makes a one-off date a regular date removes it and says so; other changes keep it',
	function () {
		as_role( 'ares-net' );
		wfm2_meetings();
		// A Saturday that is the 4th of its month.
		$sat = '';
		for ( $i = 1; $i < 70 && '' === $sat; $i++ ) {
			$d = \spokares_add_days( \spokares_today(), $i );
			if ( 6 === \spokares_weekday( $d ) && 4 === \spokares_nth( $d ) ) {
				$sat = $d;
			}
		}
		$other = wfm2_day();
		wfm2_save(
			array(
				'extra' => array(
					'meeting' => 'workshop',
					'date'    => $sat,
					'note'    => 'Swap meet',
				),
			)
		);
		wfm2_save(
			array(
				'extra' => array(
					'meeting' => '_other',
					'name'    => 'Annual meeting',
					'date'    => $other,
				),
			)
		);
		assert_count( 2, \spokares_meeting_extras(), 'set-up' );

		// Open Meeting Schedule and tick the 4th week for the workshop (qa-059 helpers).
		$fields                       = qa059_open( 'rules' );
		$w                            = qa059_card( $fields, 'workshop' );
		$fields['rules'][ $w ]['nth'] = array( '2', '4' );
		$said                         = qa059_save( 'rules', $fields );
		assert_contains( \spokares_fmt_date( $sat, 'day' ) . ' is now a regular meeting date, so its one-off date was removed.', implode( ' ', wp_list_pluck( $said, 'text' ) ), 'the notice' );
		$left = \spokares_meeting_extras();
		assert_count( 1, $left, 'the other one-off date is kept' );
		assert_same( 'Annual meeting', $left[0]['name'], 'the annual meeting' );
	}
);
