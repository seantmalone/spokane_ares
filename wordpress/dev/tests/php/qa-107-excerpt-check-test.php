<?php
/**
 * Tests for QA-107 (PLAN §4.4, "Page text"): a page's excerpt is printed as
 * its <meta name="description"> (theme inc/head.php), so it is public text,
 * but the page-text checks read only post_content. An ARES Editor could set
 * About's excerpt to "Call 509-555-9999" (the Page panel, or REST) and it was
 * saved and published with no warning, and the Dashboard didn't flag it.
 *
 * The excerpt now gets the same checks as the page text. It is the
 * webmaster's (UX spec §3.8: editors no longer see the Excerpt panel), so
 * the checks are administrators' only: their Dashboard's Needs attention
 * lists "The search-engine description of <page> has what looks like …",
 * and the editors' guard script checks the page text only (saving is never
 * blocked).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The Site tasks widget as the current user sees it.
 */
function qa_107_dashboard(): string {
	ob_start();
	spokares_dashboard_widget();
	return (string) ob_get_clean();
}

/**
 * About's title as the widget prints it (decoded, then escaped).
 */
function qa_107_about_title(): string {
	return esc_html( html_entity_decode( get_the_title( page_id( 'about' ) ), ENT_QUOTES, 'UTF-8' ) );
}

test(
	'QA-107: an ARES Editor saves a phone number in About\'s excerpt over REST: saved (warn, never block), and the webmaster\'s Dashboard flags the description',
	function () {
		as_role( 'ares-editor' );
		$about = page_id( 'about' );
		$res   = rest( 'POST', '/wp/v2/pages/' . $about, array( 'excerpt' => 'Call 509-555-9999' ) );
		expect_not_wp_error( $res, 'REST save of the excerpt' );
		assert_same( 'Call 509-555-9999', get_post( $about )->post_excerpt, 'the excerpt is saved (page text is warned about, not blocked)' );

		// The search-engine description is the webmaster's: only an
		// administrator's Needs attention names it (UX spec §3.1).
		$title = qa_107_about_title();
		assert_not_contains( 'The search-engine description of', qa_107_dashboard(), 'an ARES Editor\'s Dashboard names the description they can\'t see' );
		as_role( 'admin' );
		$html = qa_107_dashboard();
		assert_contains( 'Needs attention', $html, 'Dashboard has a Needs attention box' );
		assert_contains( 'The search-engine description of ' . $title . ' has what looks like a phone number (509-555-9999).', $html, 'Dashboard names About\'s description, what it found and the text' );
		assert_not_contains( '<li>' . $title . ' has what looks like', $html, 'About\'s page text itself is clean, so no page-text line' );
	}
);

test(
	'QA-107: never-publish words and personal e-mail addresses in an excerpt are flagged; role addresses are not',
	function () {
		as_role( 'ares-editor' );
		$about = page_id( 'about' );
		$cases = array(
			'Our hospital net meets on Tuesdays.'      => 'a hospital net',
			'Ask on the county 800 MHz system.'        => '800 MHz',
			'Write to someone@example.com to join.'    => 'an e-mail address',
			'Write to webmaster@spokares.org to join.' => '',
		);
		foreach ( $cases as $excerpt => $what ) {
			wp_update_post(
				array(
					'ID'           => $about,
					'post_excerpt' => $excerpt,
				)
			);
			clean_post_cache( $about );
			$status = array_values( array_filter( spokares_page_text_status(), static fn( $item ) => $item['page']->ID === $about ) );
			assert_count( 1, $status, 'About is one of the checked pages' );
			$found = wp_list_pluck( $status[0]['excerpt_hits'], 'what' );
			if ( '' === $what ) {
				assert_same( array(), $found, $excerpt );
			} else {
				assert_contains( $what, $found, $excerpt );
			}
		}
	}
);

test(
	'QA-107: the real excerpts of Home, How it works and About raise nothing (no false Needs attention line)',
	function () {
		as_role( 'ares-editor' );
		$status = spokares_page_text_status();
		assert_count( 3, $status, 'the three text pages' );
		foreach ( $status as $item ) {
			assert_same( array(), $item['excerpt_hits'], $item['page']->post_name . ' excerpt: ' . $item['page']->post_excerpt );
		}
		as_role( 'admin' );
		assert_not_contains( 'The search-engine description of', qa_107_dashboard(), 'Dashboard' );
	}
);

test(
	'QA-107: the editor guard checks an ARES Editor\'s page text, not the search-engine description (the webmaster\'s, UX spec §3.8)',
	function () {
		global $current_screen, $typenow, $taxnow;
		$saved = array( $current_screen, $typenow, $taxnow );
		as_role( 'ares-editor' );
		try {
			set_current_screen( 'page' );
			spokares_enqueue_editor_guard();
			$before = wp_scripts()->get_data( 'spokares-editor-guard', 'before' );
			$config = is_array( $before ) ? implode( "\n", $before ) : '';
			assert_matches( '/window\.spokaresGuard = \{.*"message":"On the site now: this page has what looks like %1\$s \(%2\$s\)\./', $config, 'spokaresGuard.message (the page-text warning)' );
			assert_not_contains( '"excerpt":', $config, 'an excerpt warning for editors (they can\'t see or change the description)' );
		} finally {
			wp_dequeue_script( 'spokares-editor-guard' );
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the globals set_current_screen() changed.
			list( $current_screen, $typenow, $taxnow ) = $saved;
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
);
