<?php
/**
 * Regression tests for workflow review E8 (ux/workflows/content.py; PLAN
 * §3.4 "Make a copy (draft copy, dates cleared)"): a copy kept the same
 * name with nothing to prompt a rename (the duplicate-name warning runs only
 * on Add), and it inherited the administrator-only Needs checking flag.
 *
 * Now the copy doesn't carry Needs checking, and it opens with the name box
 * outlined (as First day is) and "Check the name (copied from 2026)." under
 * it, until its first save. The notice reads "Copied. Check the name, set
 * the new date, then Publish."
 *
 * Uses the helpers of ux-events-meetings-test.php (uxevt_event(),
 * uxevt_form(), uxevt_click(), uxevt_notices(), uxevt_day()).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Make a copy of an event as the current user; returns the copy's ID.
 *
 * @param int $id Event.
 */
function wfe8_copy( int $id ): int {
	$res = get_action( 'spokares_duplicate_event', array( 'post' => (string) $id ), array( 'nonce_action' => 'spokares_duplicate_event_' . $id ) );
	if ( ! preg_match( '/post=(\d+)/', (string) $res['redirect'], $m ) ) {
		fail( 'Make a copy did not open the copy: ' . export( $res ) );
	}
	return (int) $m[1];
}

test(
	'a copy doesn\'t carry the webmaster\'s Needs checking flag',
	function () {
		$id = uxevt_event(
			'WF-E8 flagged',
			array(
				'spk_kind'        => 'exercise',
				'spk_start'       => uxevt_day( -300 ),
				'spk_needs_check' => '1',
			)
		);
		foreach ( array( 'admin', 'ares-editor' ) as $role ) {
			as_role( $role );
			$copy = wfe8_copy( $id );
			assert_same( '', (string) get_post_meta( $copy, 'spk_needs_check', true ), $role . ': not copied' );
			assert_same( 'exercise', (string) get_post_meta( $copy, 'spk_kind', true ), $role . ': the rest is copied (control)' );
		}
	}
);

test(
	'a fresh copy opens with the name outlined and "Check the name (copied from <year>)."; its first save clears both',
	function () {
		$start = uxevt_day( -300 );
		$id    = uxevt_event(
			'WF-E8 Field Day',
			array(
				'spk_kind'  => 'exercise',
				'spk_start' => $start,
			)
		);
		as_role( 'ares-editor' );
		uxevt_notices();
		$copy = wfe8_copy( $id );
		assert_same( array( 'Copied. Check the name, set the new date, then Publish.' ), wp_list_pluck( uxevt_notices(), 'text' ), 'the notice' );
		$html = uxevt_form( $copy );
		assert_contains( '<label for="title" class="spk-title-label spk-has-error">', $html, 'the name is outlined' );
		assert_contains( '<p class="spk-name-match" id="spk-copy-name">Check the name (copied from ' . substr( $start, 0, 4 ) . ').</p>', $html, 'the line under the name' );
		assert_not_contains( 'spk-error-text', $html, 'still no red sentences on a fresh copy' );

		uxevt_click(
			$copy,
			array(
				'post_title' => 'WF-E8 Field Day (next year)',
				'spk_start'  => uxevt_day( 60 ),
			),
			'publish'
		);
		assert_same( 'publish', get_post_status( $copy ), 'published' );
		$html = uxevt_form( $copy );
		assert_not_contains( 'spk-copy-name', $html, 'the line goes after the first save' );
		assert_contains( '<label for="title" class="spk-title-label">', $html, 'and so does the outline' );
	}
);

test(
	'a copy of an undated event names the year the original was added',
	function () {
		$id = uxevt_event(
			'WF-E8 parade',
			array(
				'spk_kind'      => 'public-service',
				'spk_date_mode' => 'not-posted',
			)
		);
		as_role( 'ares-editor' );
		$copy = wfe8_copy( $id );
		assert_contains( 'Check the name (copied from ' . get_the_date( 'Y', $id ) . ').', uxevt_form( $copy ), 'the year it was added' );
	}
);
