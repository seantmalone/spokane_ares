<?php
/**
 * Regression tests for workflow review E2 (ux/workflows/content.py; PLAN
 * §3.4 events): a wrong year was caught only after the event was live, as
 * an amber notice ("Published, but Fri, May 15, 2026 has passed, so no list
 * shows it. Check the year."), and the same notice came back on every save
 * of an old exercise, which is noise for historic records.
 *
 * Now a past date is asked about before the save, with the same mechanism
 * as the phone-number "Publish it" tick: the sentence under the date
 * ("Sat, May 15, 2026 has passed. Check the year, or tick the box to publish
 * it anyway.") and a "The date is right. Publish it." tick. A new event or
 * a draft is not published until it is ticked; a published event whose
 * dates are changed to a past date keeps its live dates (the rest is saved).
 * It is asked only when the dates are new or changed: an old published
 * event saved with its dates as they were gets no question. Once ticked,
 * those dates are not asked about again. After the save the notice is plain
 * information ("Published. It isn’t listed anywhere now: … has passed.").
 *
 * The saves use the helpers of ux-events-meetings-test.php (uxevt_click(),
 * uxevt_form(), uxevt_event(), uxevt_day()).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Add a training event through the form and click a button.
 *
 * @param string $first  First day.
 * @param string $last   Last day ('' = none).
 * @param string $button publish or saveasdraft.
 * @param bool   $tick   Tick "The date is right".
 * @return array [event ID, the click's result]
 */
function wfe2_add( string $first, string $last, string $button = 'publish', bool $tick = false ): array {
	$id     = get_default_post_to_edit( 'spk_event', true )->ID;
	$typing = array(
		'post_title'    => 'WF-E2 training night',
		'spk_kind'      => 'training',
		'spk_date_mode' => 'date',
		'spk_start'     => $first,
		'spk_end'       => $last,
	);
	if ( $tick ) {
		$typing['spk_confirm'] = array( 'past' => '1' );
	}
	return array( $id, uxevt_click( $id, $typing, $button ) );
}

/**
 * The past-date sentence for a day.
 *
 * @param string $day Y-m-d.
 */
function wfe2_sentence( string $day ): string {
	return \spokares_fmt_date( $day, 'long' ) . ' has passed. Check the year, or tick the box to publish it anyway.';
}

test(
	'a new event with a past date is not published: the notice and the sentence say the date has passed, with a tick under When',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$day          = uxevt_day( -30 );
			[ $id, $res ] = wfe2_add( $day, '' );
			assert_same( 'draft', get_post_status( $id ), $role . ': not published' );
			assert_same( array( 'error' ), wp_list_pluck( $res['notices'], 'type' ), $role . ': one red notice' );
			assert_same( 'Not published yet: ' . \spokares_fmt_date( $day, 'long' ) . ' has passed. Check the year, or tick the box under When, then click Publish.', $res['notices'][0]['text'], $role . ': the notice' );
			$html = uxevt_form( $id );
			assert_contains( esc_html( wfe2_sentence( $day ) ), $html, $role . ': the sentence under the date' );
			assert_matches( '#<label class="spk-confirm"><input type="checkbox" name="spk_confirm\[past\]" value="1"> The date is right\. Publish it\.</label>#', $html, $role . ': the tick' );
			assert_matches( '#<input type="date" id="spk-start"[^>]*class="spk-field-error"#', $html, $role . ': First day is outlined' );
			assert_same( $day, (string) get_post_meta( $id, 'spk_start', true ), $role . ': the typed date is kept' );
		}
	}
);

test(
	'ticking "The date is right" publishes it, and the notice says it isn’t listed because the date has passed',
	function () {
		as_role( 'ares-editor' );
		$day          = uxevt_day( -30 );
		[ $id, $res ] = wfe2_add( $day, '', 'publish', true );
		assert_same( 'publish', get_post_status( $id ), 'published' );
		assert_same( array( 'Published. It isn’t listed anywhere now: ' . \spokares_fmt_date( $day, 'long' ) . ' has passed.' ), wp_list_pluck( $res['notices'], 'text' ), 'the notice' );
		assert_same( array( 'info' ), wp_list_pluck( $res['notices'], 'type' ), 'information, not amber' );
		assert_not_contains( 'has passed', uxevt_form( $id ), 'no question on the published form' );
	}
);

test(
	'a past multi-day event names its last day, under Last day',
	function () {
		as_role( 'ares-editor' );
		$first  = uxevt_day( -12 );
		$last   = uxevt_day( -10 );
		[ $id ] = wfe2_add( $first, $last );
		$html   = uxevt_form( $id );
		assert_same( 'draft', get_post_status( $id ), 'not published' );
		assert_contains( esc_html( wfe2_sentence( $last ) ), $html, 'the sentence names the last day' );
		assert_matches( '#<input type="date" id="spk-end"[^>]*class="spk-field-error"#', $html, 'Last day is outlined' );
	}
);

test(
	'an old published event saved with its dates as they were gets no question, and no amber notice',
	function () {
		$day = uxevt_day( -200 );
		$id  = uxevt_event(
			'WF-E2 2025 exercise',
			array(
				'spk_kind'      => 'exercise',
				'spk_start'     => $day,
				'spk_keep_past' => '1',
			)
		);
		as_role( 'ares-editor' );
		assert_not_contains( 'has passed', uxevt_form( $id ), 'no question on the form' );
		$res = uxevt_click( $id, array( 'spk_where' => 'Spokane EOC' ), 'save' );
		assert_same( 'Spokane EOC', (string) get_post_meta( $id, 'spk_where', true ), 'saved' );
		assert_same( array( 'success' ), wp_list_pluck( $res['notices'], 'type' ), 'one green notice: ' . export( $res['notices'] ) );
		assert_same( 'Saved. It shows under Past exercises on the Exercises & events page.', $res['notices'][0]['text'], 'the notice' );

		$training = uxevt_event(
			'WF-E2 old training',
			array(
				'spk_kind'  => 'training',
				'spk_start' => $day,
			)
		);
		$res      = uxevt_click( $training, array(), 'save' );
		assert_same( array( 'Saved. It isn’t listed anywhere now: ' . \spokares_fmt_date( $day, 'long' ) . ' has passed.' ), wp_list_pluck( $res['notices'], 'text' ), 'no "Check the year" on an old event' );
		assert_same( array( 'info' ), wp_list_pluck( $res['notices'], 'type' ), 'information' );
	}
);

test(
	'a published event changed to a past date keeps its live date until the tick; the rest is saved',
	function () {
		$future = uxevt_day( 40 );
		$past   = uxevt_day( -325 );
		$id     = uxevt_event(
			'WF-E2 drill',
			array(
				'spk_kind'  => 'training',
				'spk_start' => $future,
			)
		);
		as_role( 'ares-editor' );
		$res = uxevt_click(
			$id,
			array(
				'spk_start' => $past,
				'spk_where' => 'Fire Station 1',
			),
			'save'
		);
		assert_same( 'publish', get_post_status( $id ), 'still on the site' );
		assert_same( $future, (string) get_post_meta( $id, 'spk_start', true ), 'the live date is kept' );
		assert_same( 'Fire Station 1', (string) get_post_meta( $id, 'spk_where', true ), 'the rest is saved' );
		assert_same( array( 'Saved, except First day: ' . wfe2_sentence( $past ) . ' Everything else is on the site now.' ), wp_list_pluck( $res['notices'], 'text' ), 'the notice keeps the date\'s capital' );
		$html = uxevt_form( $id );
		assert_matches( '#<input type="date" id="spk-start" name="spk_start" value="' . $past . '"#', $html, 'the typed date comes back' );
		assert_contains( 'name="spk_confirm[past]"', $html, 'with the tick' );

		$res = uxevt_click( $id, array( 'spk_confirm' => array( 'past' => '1' ) ), 'save' );
		assert_same( $past, (string) get_post_meta( $id, 'spk_start', true ), 'ticked: the past date is saved' );
		assert_same( array( 'info' ), wp_list_pluck( $res['notices'], 'type' ), 'the notice is information' );
	}
);

test(
	'a past date ticked on a draft is not asked about again at Publish; a changed date is',
	function () {
		as_role( 'ares-editor' );
		$day    = uxevt_day( -30 );
		[ $id ] = wfe2_add( $day, '', 'saveasdraft', true );
		assert_same( 'draft', get_post_status( $id ), 'a draft' );
		assert_not_contains( 'has passed', uxevt_form( $id ), 'the ticked date is not asked about on the form' );
		uxevt_click( $id, array(), 'publish' );
		assert_same( 'publish', get_post_status( $id ), 'published without a second tick' );

		[ $other ] = wfe2_add( $day, '', 'saveasdraft', true );
		uxevt_click( $other, array( 'spk_start' => uxevt_day( -31 ) ), 'publish' );
		assert_same( 'draft', get_post_status( $other ), 'another past date is asked about again' );
	}
);

test(
	'a date today or later is never asked about (control)',
	function () {
		as_role( 'ares-editor' );
		foreach ( array( 0, 20 ) as $days ) {
			[ $id, $res ] = wfe2_add( uxevt_day( $days ), '' );
			assert_same( 'publish', get_post_status( $id ), 'published: ' . $days );
			assert_same( array( 'success' ), wp_list_pluck( $res['notices'], 'type' ), 'green: ' . $days );
		}
		// A multi-day event that started yesterday and ends tomorrow is not past.
		[ $id ] = wfe2_add( uxevt_day( -1 ), uxevt_day( 1 ) );
		assert_same( 'publish', get_post_status( $id ), 'running now' );
	}
);
