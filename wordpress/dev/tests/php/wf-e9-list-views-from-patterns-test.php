<?php
/**
 * Regression tests for workflow review E9 (ux/workflows/content.py; PLAN
 * §6.4 block views): the Where it shows column and the saved notice
 * copied the theme's block settings (the limits, types and the 60-day
 * window) into the plugin, so editing a pattern made them drift from what
 * the site really shows.
 *
 * Now spokares_event_list_views() reads the spokares/events blocks of the
 * theme's patterns (members-exercises and members-hub), with the block's
 * defaults for an attribute a pattern leaves out, as the page does. The
 * plugin's own numbers (spokares_event_list_defaults()) are used only when
 * a pattern isn't registered; the first test keeps them equal to the
 * patterns. The theme's patterns are unchanged.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'the list views come from the theme\'s patterns, and the plugin\'s fallback numbers still match them',
	function () {
		$views = \spokares_event_list_views();
		assert_same( \spokares_event_list_defaults(), $views, 'the fallback equals what the patterns say' );
		$lists = \spokares_event_pattern_lists( 'spokares/members-exercises' );
		assert_same( 'exercise', $lists['next-up']['types'] ?? null, 'next-up is read from members-exercises (control)' );
		assert_same( 8, $lists['past']['limit'] ?? null, 'and the past list (control)' );
		assert_same( 60, $views['hub'][1]['days'], 'the hub\'s window is read from members-hub (control)' );
	}
);

test(
	'a pattern edit moves the Where it shows lists with it (a past list of 3)',
	function () {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$slug     = 'spokares/members-exercises';
		$saved    = $registry->get_registered( $slug );
		if ( ! $saved ) {
			fail( 'set-up: ' . $slug . ' is not registered' );
		}
		$props = array_diff_key( $saved, array_flip( array( 'name', 'filePath' ) ) );
		$edit  = str_replace( '{"view":"past","limit":8}', '{"view":"past","limit":3}', (string) $saved['content'] );
		assert_not_same( $saved['content'], $edit, 'set-up: the past block was found' );
		$registry->unregister( $slug );
		$registry->register( $slug, array_merge( $props, array( 'content' => $edit ) ) );
		try {
			$views = \spokares_event_list_views();
			assert_same( array( 'past', array( 'limit' => 3 ) ), $views['past'], 'the past list follows the pattern' );
			assert_same( \spokares_event_list_defaults()['next-up'], $views['next-up'], 'the rest as before' );
		} finally {
			$registry->unregister( $slug );
			$registry->register( $slug, $props );
		}
		assert_same( \spokares_event_list_defaults(), \spokares_event_list_views(), 'restored' );
	}
);

test(
	'without the patterns the plugin\'s numbers are used',
	function () {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$saved    = array();
		foreach ( array( 'spokares/members-exercises', 'spokares/members-hub' ) as $slug ) {
			$saved[ $slug ] = array_diff_key( (array) $registry->get_registered( $slug ), array_flip( array( 'name' ) ) );
			$registry->unregister( $slug );
		}
		try {
			assert_same( \spokares_event_list_defaults(), \spokares_event_list_views(), 'fallback' );
		} finally {
			foreach ( $saved as $slug => $props ) {
				$registry->register( $slug, $props );
			}
		}
	}
);
