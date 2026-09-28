<?php
/**
 * Regression tests for QA-064 (PLAN §3.3 Tuesday note, §3.5 net › winlink,
 * §3.4 Net details): after Net details moves the Winlink nights, the
 * Exercises page's "Winlink assignments" list still prints the assignments
 * typed for the old Winlink nights, on Tuesdays the rota now calls something
 * else.
 *
 * Repro: Net details, Winlink nights 2nd + 4th changed to 1st + 3rd and the
 * GMRS net moved to the 2nd Tuesday; Save ("Saved.", no warning). /members/
 * then says Tue, Oct 13 is "GMRS net, 7:30 PM", while /members/exercises/
 * lists "Tue, Oct 13 · Did You Feel It report (ShakeOut practice) · DYFI"
 * and five more assignments on Tuesdays that are no longer Winlink nights.
 *
 * The cause: spokares_render_net() view `winlink` lists every spk_rota row
 * dated today or later with a wl_task, whatever spokares_net_on() says that
 * Tuesday is. The Net rota screen offers the Winlink boxes only on Winlink
 * nights (and on a row that still holds an old task, so it can be cleared),
 * so a task on another Tuesday is left over from the old weeks. The public
 * list must show only Winlink-night Tuesdays, so it agrees with the rota's
 * Note column, and those rows must not use up the list's limit.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The next twelve Tuesdays from today (SPOKARES_TODAY in dev).
 *
 * @return string[] Dates.
 */
function qa064_tuesdays(): array {
	return wp_list_pluck( \spokares_rota_rows( 12 ), 'date' );
}

/**
 * The task text the test stores for a date.
 *
 * @param string $prefix "old" (typed under the old weeks) or "new".
 * @param string $ymd    Date.
 */
function qa064_task( string $prefix, string $ymd ): string {
	return 'QA064 ' . $prefix . ' Winlink task for ' . $ymd;
}

/**
 * One stored rota row with a Winlink assignment.
 *
 * @param string $task The assignment.
 */
function qa064_row( string $task ): array {
	return array(
		'state'   => 'open',
		'call'    => '',
		'note'    => '',
		'wl_task' => $task,
		'wl_form' => 'ICS-213',
	);
}

/**
 * The seeded net rules (Winlink 2nd + 4th, GMRS 3rd, simplex 5th), and a
 * rota with an assignment on every Winlink night under those rules, as the
 * Net rota screen lets an editor type them. Returns the dated tasks.
 *
 * @return array<string,string> Date => task.
 */
function qa064_baseline(): array {
	$nets                = \spokares_opt( 'spk_nets' );
	$nets['winlink_nth'] = array( 2, 4 );
	$nets['gmrs_nth']    = array( 3 );
	$nets['simplex_nth'] = array( 5 );
	update_option( 'spk_nets', $nets );

	$rota  = array();
	$tasks = array();
	foreach ( qa064_tuesdays() as $ymd ) {
		if ( 'winlink' === \spokares_net_on( $ymd )['kind'] ) {
			$tasks[ $ymd ] = qa064_task( 'old', $ymd );
			$rota[ $ymd ]  = qa064_row( $tasks[ $ymd ] );
		}
	}
	update_option( 'spk_rota', $rota );
	assert_true( count( $tasks ) >= 4, 'baseline: fewer than four Winlink nights in twelve Tuesdays' );
	return $tasks;
}

/**
 * The Net details form as the screen posts it, with new Winlink and GMRS weeks.
 *
 * @param int[] $winlink Winlink weeks.
 * @param int[] $gmrs    GMRS weeks.
 */
function qa064_net_fields( array $winlink, array $gmrs ): array {
	$radio = \spokares_opt( 'spk_radio' );
	$nets  = \spokares_opt( 'spk_nets' );
	return array(
		'radio' => array(
			'primary'   => array(
				'call'   => $radio['primary']['call'],
				'freq'   => $radio['primary']['freq'],
				'offset' => $radio['primary']['offset'],
				'tone'   => $radio['primary']['tone'],
			),
			'alternate' => array(
				'freq'        => $radio['alternate']['freq'],
				'offset'      => $radio['alternate']['offset'],
				'tone'        => $radio['alternate']['tone'],
				'show'        => $radio['alternate']['show'] ? '1' : '',
				'needs_check' => $radio['alternate']['needs_check'] ? '1' : '',
			),
		),
		'nets'  => array(
			'net_time'       => $nets['net_time'],
			'gmrs_time'      => $nets['gmrs_time'],
			'winlink_nth'    => array_map( 'strval', $winlink ),
			'simplex_nth'    => array_map( 'strval', $nets['simplex_nth'] ),
			'gmrs_nth'       => array_map( 'strval', $gmrs ),
			'needs_check'    => $nets['needs_check'] ? '1' : '',
			'winlink_howto'  => $nets['winlink_howto'],
			'open_slot_line' => $nets['open_slot_line'],
		),
	);
}

/**
 * Save Net details with new weeks as the current user.
 *
 * @param int[]  $winlink Winlink weeks.
 * @param int[]  $gmrs    GMRS weeks.
 * @param string $msg     Message prefix.
 */
function qa064_save_weeks( array $winlink, array $gmrs, string $msg ): void {
	$res = post_form( 'spokares_save_net_details', qa064_net_fields( $winlink, $gmrs ) );
	assert_same( null, $res['die'], $msg . ': Net details save refused' );
	assert_contains( 'page=spokares-net-details', (string) $res['redirect'], $msg . ': no redirect back to Net details' );
	$nets = \spokares_opt( 'spk_nets' );
	assert_same( $winlink, $nets['winlink_nth'], $msg . ': the Winlink weeks were not saved' );
	assert_same( $gmrs, $nets['gmrs_nth'], $msg . ': the GMRS weeks were not saved' );
}

/**
 * Exercises › Winlink assignments as a visitor gets it (the block as the
 * Exercises template places it): the table's dates in order, and the HTML.
 *
 * @return array{dates:string[],html:string}
 */
function qa064_winlink_list(): array {
	as_anonymous();
	$html = do_blocks( '<!-- wp:spokares/net {"view":"winlink","limit":8} /-->' );
	preg_match_all( '#<th scope="row"><time datetime="(\d{4}-\d{2}-\d{2})"#', $html, $m );
	return array(
		'dates' => $m[1],
		'html'  => $html,
	);
}

test(
	'after Net details moves the Winlink nights, Exercises lists no assignment on a Tuesday that is no longer a Winlink night',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$old = qa064_baseline();

			// Winlink 2nd + 4th → 1st + 3rd, GMRS 3rd → 2nd (the report's change).
			qa064_save_weeks( array( 1, 3 ), array( 2 ), $role );

			// The editor then types assignments for the new Winlink nights.
			$rota = \spokares_opt( 'spk_rota' );
			$new  = array();
			foreach ( qa064_tuesdays() as $ymd ) {
				if ( 'winlink' === \spokares_net_on( $ymd )['kind'] ) {
					$new[ $ymd ]  = qa064_task( 'new', $ymd );
					$rota[ $ymd ] = qa064_row( $new[ $ymd ] );
				}
			}
			update_option( 'spk_rota', $rota );
			assert_true( count( $new ) >= 4 && count( $new ) <= 8, $role . ': expected four to eight new Winlink nights in twelve Tuesdays, got ' . count( $new ) );

			$list = qa064_winlink_list();

			// 1. No old-weeks assignment on a Tuesday the rota now calls something else.
			foreach ( $old as $ymd => $task ) {
				$net = \spokares_net_on( $ymd );
				assert_not_same( 'winlink', $net['kind'], $role . ': test setup, ' . $ymd . ' is still a Winlink night' );
				assert_not_contains(
					$task,
					$list['html'],
					$role . ': Exercises › Winlink assignments lists ' . \spokares_fmt_date( $ymd, 'short' ) . ' ("' . $task . '"), but the rota says that Tuesday is "' . ( '' !== $net['note'] ? $net['note'] : 'a plain net' ) . '"'
				);
			}

			// 2. Every listed date is a Winlink night on the rota.
			foreach ( $list['dates'] as $ymd ) {
				assert_same( 'winlink', \spokares_net_on( $ymd )['kind'], $role . ': Exercises › Winlink assignments lists ' . $ymd . ', which the rota does not call a Winlink night' );
			}

			// 3. The new Winlink nights are all listed, in date order (the stale rows must not use up the limit of 8).
			foreach ( $new as $ymd => $task ) {
				assert_contains( $task, $list['html'], $role . ': the assignment for the new Winlink night ' . $ymd . ' is missing' );
			}
			assert_same( array_keys( $new ), $list['dates'], $role . ': Exercises › Winlink assignments should list exactly the new Winlink nights, in order' );
		}
	}
);

test(
	'control: with the Winlink weeks unchanged, Exercises lists every Winlink-night assignment',
	function () {
		as_role( 'ares-net' );
		$old  = qa064_baseline();
		$list = qa064_winlink_list();
		assert_matches( '#<table class="[^"]*\bex-winlink\b#', $list['html'], 'no Winlink assignments table' );
		foreach ( $old as $ymd => $task ) {
			assert_contains( $task, $list['html'], 'the assignment for Winlink night ' . $ymd . ' is missing' );
		}
		assert_same( array_slice( array_keys( $old ), 0, 8 ), $list['dates'], 'the Winlink nights, in date order' );
	}
);
