<?php
/**
 * Regression tests for workflow review E4 (ux/workflows/content.py; PLAN
 * §3.4 "Cancelled keeps its row … until the date passes"): switching a
 * cancelled event to Postponed (or Date not posted yet) dropped the
 * Cancelled flag with no message.
 *
 * Chosen behaviour: Cancelled still belongs to an event on a date (it reads
 * "Cancelled until Sat, Oct 17", and the tick sits under the dates), so a
 * When other than On a date clears it, and the notice now says so:
 * "Cancelled is cleared: only an event on a date can be cancelled." Keeping
 * a hidden flag that comes back on a switch to On a date is what E5 removes
 * for the other boxes.
 *
 * The saves use the helpers of ux-events-meetings-test.php (uxevt_click(),
 * uxevt_event(), uxevt_day()).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'a cancelled event switched to Postponed or Date not posted yet: Cancelled is cleared and the notice says so',
	function () {
		foreach ( array( 'postponed', 'not-posted' ) as $mode ) {
			$id = uxevt_event(
				'WF-E4 cancelled drill ' . $mode,
				array(
					'spk_kind'      => 'training',
					'spk_start'     => uxevt_day( 12 ),
					'spk_cancelled' => '1',
				)
			);
			as_role( 'ares-editor' );
			$res = uxevt_click( $id, array( 'spk_date_mode' => $mode ), 'save' );
			assert_same( '', (string) get_post_meta( $id, 'spk_cancelled', true ), $mode . ': cleared' );
			assert_count( 1, $res['notices'], $mode . ': one notice' );
			assert_contains( ' Cancelled is cleared: only an event on a date can be cancelled.', $res['notices'][0]['text'], $mode . ': said' );
		}
	}
);

test(
	'unticking Cancelled on a dated event, or saving a postponed event that was never cancelled, says nothing about it (control)',
	function () {
		$id = uxevt_event(
			'WF-E4 back on',
			array(
				'spk_kind'      => 'training',
				'spk_start'     => uxevt_day( 12 ),
				'spk_cancelled' => '1',
			)
		);
		as_role( 'ares-editor' );
		$res = uxevt_click( $id, array( 'spk_cancelled' => null ), 'save' );
		assert_not_contains( 'Cancelled is cleared', $res['notices'][0]['text'], 'unticked' );

		$res = uxevt_click( $id, array( 'spk_date_mode' => 'postponed' ), 'save' );
		assert_same( array( 'Saved. It shows as Postponed on the Exercises & events page.' ), wp_list_pluck( $res['notices'], 'text' ), 'never cancelled' );
	}
);
