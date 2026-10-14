<?php
/**
 * Regression tests for workflow review E6 (ux/workflows/content.py; PLAN
 * §3.4 events, §4.8 one notice per save): Save draft on an event with
 * problems showed WordPress's "Draft saved. It isn’t on the site until you
 * Publish." The red outlines were on the form, but the message implied all
 * was well. Now the notice says so: "Draft saved. 2 boxes need fixing
 * before you can Publish." (amber; WordPress's own message is dropped). A
 * draft with nothing to fix still gets WordPress's message.
 *
 * The saves use the helpers of ux-events-meetings-test.php (uxevt_click(),
 * uxevt_query(), uxevt_day()).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'Save draft with two problems: "Draft saved. 2 boxes need fixing before you can Publish." and no WordPress message',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$id  = get_default_post_to_edit( 'spk_event', true )->ID;
			$res = uxevt_click( $id, array( 'post_title' => 'WF-E6 half-filled' ), 'saveasdraft' );
			assert_same( 'draft', get_post_status( $id ), $role . ': a draft' );
			assert_false( isset( uxevt_query( $res )['message'] ), $role . ': WordPress\'s message is dropped: ' . $res['redirect'] );
			assert_same( array( 'Draft saved. 2 boxes need fixing before you can Publish.' ), wp_list_pluck( $res['notices'], 'text' ), $role . ': the notice (no type, no first day)' );
			assert_same( array( 'warning' ), wp_list_pluck( $res['notices'], 'type' ), $role . ': amber' );
		}
	}
);

test(
	'Save draft with one problem says "1 box"; a phone number to tick counts as a box',
	function () {
		as_role( 'ares-editor' );
		$id  = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$id,
			array(
				'post_title'    => 'WF-E6 one box',
				'spk_kind'      => 'training',
				'spk_date_mode' => 'date',
				'spk_start'     => '',
			),
			'saveasdraft'
		);
		assert_same( array( 'Draft saved. 1 box needs fixing before you can Publish.' ), wp_list_pluck( $res['notices'], 'text' ), 'one problem' );

		$res = uxevt_click(
			$id,
			array(
				'spk_start' => uxevt_day( 30 ),
				'spk_where' => 'Call 509-555-0142',
			),
			'saveasdraft'
		);
		assert_same( array( 'Draft saved. 1 box needs fixing before you can Publish.' ), wp_list_pluck( $res['notices'], 'text' ), 'the phone number to tick' );
	}
);

test(
	'Save draft with nothing to fix still gets WordPress\'s "Draft saved" message (control)',
	function () {
		as_role( 'ares-editor' );
		$id  = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$id,
			array(
				'post_title'    => 'WF-E6 complete draft',
				'spk_kind'      => 'training',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 30 ),
			),
			'saveasdraft'
		);
		assert_same( array(), $res['notices'], 'no notice of ours' );
		assert_same( '10', (string) ( uxevt_query( $res )['message'] ?? '' ), 'message 10' );
	}
);
