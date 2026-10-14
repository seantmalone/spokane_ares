<?php
/**
 * Regression tests for the workflow review's finding D12 (ux/workflows, "A
 * Most Used button disappears silently").
 *
 * When a button's document went to Draft or the Trash, the button vanished
 * from the For members page and only the Most Used screen said "(not on the
 * site now)". Now the save's notice and the list's "off the site" notice say
 * "It was button 2 on Most Used; that button is now empty on the site.", and
 * the Dashboard's Needs attention has a line while a button's document isn't
 * on the site.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Make a fresh published link document button 2. Returns its ID.
 */
function wf_d12_button_two(): int {
	$doc             = uxdoc_published( 'WF-D12 button doc', 'link' );
	$tiles           = \spokares_opt( 'spk_tiles' );
	$tiles[1]['doc'] = $doc;
	update_option( 'spk_tiles', $tiles );
	assert_same( 2, \spokares_document_button( $doc ), 'set-up: it is button 2' );
	assert_true( \spokares_tile_shows( $doc ), 'set-up: the site shows its button' );
	return $doc;
}

test(
	'saving a Most Used document as a draft says its button is now empty',
	function () {
		$doc = wf_d12_button_two();
		as_role( 'ares-editor' );
		uxdoc_notices();
		uxdoc_click( $doc, uxdoc_fields( $doc ), 'saveasdraft' );
		assert_same( 'draft', get_post_status( $doc ), 'set-up: saved as a draft' );
		assert_same( array( 'info: Draft saved. It isn’t on the site until you Publish. It was button 2 on Most Used; that button is now empty on the site.' ), uxdoc_notices(), 'the notice' );

		// Published again: no such sentence.
		$fields                   = uxdoc_fields( $doc );
		$fields['spk_privacy_ok'] = '1';
		uxdoc_click( $doc, $fields, 'publish' );
		foreach ( uxdoc_notices() as $notice ) {
			assert_not_contains( 'Most Used', $notice, 'a published button document' );
		}
	}
);

test(
	'switching a Most Used document to "Soon" says its button is now empty',
	function () {
		$doc = wf_d12_button_two();
		as_role( 'ares-editor' );
		uxdoc_notices();
		$fields               = uxdoc_fields( $doc );
		$fields['spk_source'] = 'soon';
		uxdoc_click( $doc, $fields );
		assert_contains( 'It was button 2 on Most Used; that button is now empty on the site.', implode( ' ', uxdoc_notices() ), 'the notice' );
	}
);

test(
	'"Take it off the site" of a Most Used document says its button is now empty',
	function () {
		$doc = wf_d12_button_two();
		as_role( 'ares-editor' );
		wp_update_post(
			array(
				'ID'          => $doc,
				'post_author' => user_id( 'ares-editor' ),
			)
		);
		qa_018_trash( $doc, true );
		$r = call_request(
			'GET',
			array(
				'post_type' => 'spk_document',
				'trashed'   => '1',
				'ids'       => (string) $doc,
			),
			array(),
			static fn() => apply_filters( 'bulk_post_updated_messages', array(), array( 'trashed' => 1 ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		);
		assert_same( '“WF-D12 button doc” is off the site. It was button 2 on Most Used; that button is now empty on the site.', (string) ( $r['returned']['spk_document']['trashed'] ?? '' ), 'the list\'s notice' );
	}
);

test(
	'the Dashboard says when a Most Used button\'s document isn\'t on the site',
	function () {
		$doc = wf_d12_button_two();
		as_role( 'ares-editor' );
		$render = static function (): string {
			ob_start();
			\spokares_dashboard_widget();
			return (string) ob_get_clean();
		};
		assert_not_contains( 'WF-D12 button doc', $render(), 'a button on the site: no line' );
		wp_update_post(
			array(
				'ID'          => $doc,
				'post_status' => 'draft',
			)
		);
		clean_post_cache( $doc );
		assert_same( array( 2 => $doc ), \spokares_tiles_off_site(), 'button 2 is off the site' );
		assert_contains( 'Most Used button 2 is empty on the site: “WF-D12 button doc” isn’t on the site now. <a href="' . esc_url( admin_url( 'admin.php?page=spokares-tiles' ) ) . '">Pick another document or publish it</a>', $render(), 'the Needs attention line' );
	}
);
