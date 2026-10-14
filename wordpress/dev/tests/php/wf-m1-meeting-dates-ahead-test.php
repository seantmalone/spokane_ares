<?php
/**
 * Regression tests for workflow finding M1 (ux/workflows/content.py): Cancel
 * or Move a Meeting offered only the next 5 dates of each meeting, so a
 * potluck or room change announced further ahead couldn't be entered. Now
 * it offers about six months; the first 5 show, "Show more dates of …"
 * shows the rest in place, and a later date with a change always shows.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Two shown meetings for the test: a monthly one and a twice-monthly one.
 */
function wfm1_meetings(): void {
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
					'id'        => 'twice',
					'name'      => 'Tech night',
					'nth'       => array( 1, 3 ),
					'weekday'   => 3,
					'start'     => '19:00',
					'show_home' => true,
					'active'    => true,
				),
			),
			'changes'  => array(),
		)
	);
	\spokares_opt_flush();
}

/**
 * The Cancel or Move screen as the current user sees it.
 *
 * @param array $get Query args.
 */
function wfm1_screen( array $get = array() ): string {
	$res = call_request(
		'GET',
		array_merge( array( 'page' => 'spokares-meetings' ), $get ),
		array(),
		static function () {
			\spokares_meetings_page();
		}
	);
	return (string) $res['output'];
}

/**
 * The meeting's tbody from the screen.
 *
 * @param string $html Screen.
 * @param string $name Meeting name.
 */
function wfm1_body( string $html, string $name ): string {
	foreach ( explode( '<tbody class="spk-meeting">', $html ) as $part ) {
		if ( str_contains( $part, $name ) ) {
			return (string) strstr( $part, '</tbody>', true );
		}
	}
	fail( 'no rows for ' . $name );
	return '';
}

test(
	'Cancel or Move offers about six months of dates; the first five show and "Show more dates" has the rest',
	function () {
		as_role( 'ares-editor' );
		wfm1_meetings();
		$today = \spokares_today();
		$m     = \spokares_opt( 'spk_meetings' )['meetings'][0];
		$dates = \spokares_meeting_admin_dates( $m );
		assert_true( count( $dates ) >= 6, 'about six monthly dates (' . implode( ', ', $dates ) . ')' );
		assert_true( max( $dates ) <= \spokares_add_days( $today, 183 ), 'no further than about six months' );
		assert_true( max( $dates ) > \spokares_add_days( $today, 140 ), 'and close to it' );

		$body = wfm1_body( wfm1_screen(), 'Second Saturday Workshop' );
		$rows = substr_count( $body, '<tr class="spk-meeting-row' );
		assert_same( count( $dates ), $rows, 'every date is on the page (so "Show more" keeps typing)' );
		assert_same( $rows - 5, preg_match_all( '#<tr class="spk-meeting-row[^"]*" hidden>#', $body ), 'all but the first five are hidden' );
		assert_matches( '#<a class="spk-more-dates" href="[^"]*dates=all[^"]*">Show more dates of Second Saturday Workshop</a>#', $body, 'the Show more dates link' );

		$all = wfm1_body( wfm1_screen( array( 'dates' => 'all' ) ), 'Second Saturday Workshop' );
		assert_same( 0, preg_match_all( '#<tr class="spk-meeting-row[^"]*" hidden>#', $all ), 'without the script, the link shows every date' );
		assert_not_contains( 'spk-more-dates', $all, 'and no link' );

		$twice = \spokares_meeting_admin_dates( \spokares_opt( 'spk_meetings' )['meetings'][1] );
		assert_true( count( $twice ) >= 11, 'a twice-monthly meeting offers about twelve dates' );
	}
);

test(
	'a date past the first five can be cancelled, and then always shows; a change past six months still shows',
	function () {
		as_role( 'ares-editor' );
		wfm1_meetings();
		$dates = \spokares_meeting_admin_dates( \spokares_opt( 'spk_meetings' )['meetings'][0] );
		$later = $dates[5];
		delete_transient( 'spokares_notices_' . get_current_user_id() );
		post_form(
			'spokares_save_meetings',
			array(
				'm' => array(
					'workshop' => array(
						$later => array(
							'h'         => \spokares_change_hash( null ),
							'cancelled' => '1',
							'note'      => 'Room booked for the fair',
						),
					),
				),
			)
		);
		\spokares_unlock_option( 'spk_meetings' );
		\spokares_opt_flush();
		$c = \spokares_meeting_change( 'workshop', $later );
		assert_same( 'cancelled', $c['kind'] ?? '', 'the sixth date is cancelled' );
		$notices = get_transient( 'spokares_notices_' . get_current_user_id() );
		assert_same( 'Saved: ' . \spokares_fmt_date( $later, 'short' ) . ' cancelled.', $notices[0]['text'] ?? '', 'the notice' );

		$body = wfm1_body( wfm1_screen(), 'Second Saturday Workshop' );
		assert_matches( '#<tr class="spk-meeting-row">\s*<th[^>]*>\s*<span class="screen-reader-text">Second Saturday Workshop</span>\s*</th>\s*<td class="spk-meeting-date"[^>]*>\s*' . preg_quote( \spokares_fmt_date( $later, 'short' ), '#' ) . '#', $body, 'the changed date is not hidden' );

		// A change further out than six months (made before, or by hand) still shows.
		$far  = \spokares_meeting_rule_dates( \spokares_opt( 'spk_meetings' )['meetings'][0], \spokares_add_days( \spokares_today(), 200 ), \spokares_add_days( \spokares_today(), 260 ) )[0];
		$data = \spokares_opt( 'spk_meetings' );

		$data['changes'][] = array(
			'date'     => $far,
			'meeting'  => 'workshop',
			'kind'     => 'note',
			'new_date' => '',
			'note'     => 'Holiday potluck',
		);
		update_option( 'spk_meetings', $data );
		\spokares_opt_flush();
		assert_contains( $far, \spokares_meeting_admin_dates( $data['meetings'][0] ), 'the far change is offered' );
		assert_contains( 'value="Holiday potluck"', wfm1_body( wfm1_screen(), 'Second Saturday Workshop' ), 'and shows' );
	}
);
