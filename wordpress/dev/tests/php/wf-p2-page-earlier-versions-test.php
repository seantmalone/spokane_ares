<?php
/**
 * Regression tests for workflow review P2: getting back an earlier version
 * of Home, How it works or About was the webmaster's job. Now an editor can
 * restore one from core's Compare Revisions screen (Dashboard › Page Text ›
 * Earlier versions) when its layout matches the page now; only its words
 * come back. A version with another layout, or with never-publish words the
 * page doesn't have, is refused with one sentence. Before this, core's
 * restore went through the save floor, which quietly kept the page while
 * core said "restored".
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Save About as an administrator with words added to its first paragraph
 * (and, optionally, an extra paragraph block at the end), and return the
 * revision that save made.
 *
 * @param string $words  Words to add.
 * @param bool   $layout Also add a paragraph block (a layout change).
 * @param string $base   The content to start from.
 */
function wfp2_admin_save( string $words, bool $layout, string $base ): int {
	as_role( 'admin' );
	$at = strpos( $base, "</p>\n<!-- /wp:paragraph -->" );
	if ( false === $at ) {
		fail( 'About has no paragraph' );
	}
	$content = substr( $base, 0, $at ) . ' ' . $words . substr( $base, $at );
	if ( $layout ) {
		$content .= "\n\n<!-- wp:paragraph -->\n<p>An extra part.</p>\n<!-- /wp:paragraph -->";
	}
	$id = wp_update_post(
		wp_slash(
			array(
				'ID'           => page_id( 'about' ),
				'post_content' => $content,
			)
		),
		true
	);
	expect_not_wp_error( $id, 'the administrator\'s save' );
	$latest = wp_get_post_revisions(
		page_id( 'about' ),
		array(
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	assert_count( 1, $latest, 'the save made a revision' );
	return (int) reset( $latest );
}

/**
 * Restore a revision as the current user, through the plugin's answer to
 * revision.php?action=restore.
 *
 * @param int $revision Revision ID.
 */
function wfp2_restore( int $revision ): array {
	return call_request(
		'GET',
		array(
			'action'   => 'restore',
			'revision' => $revision,
			'_wpnonce' => wp_create_nonce( 'restore-post_' . $revision ),
		),
		array(),
		'spokares_revision_restore_for_editors'
	);
}

/**
 * About's stored post, fresh.
 */
function wfp2_about(): \WP_Post {
	clean_post_cache( page_id( 'about' ) );
	return get_post( page_id( 'about' ) );
}

test(
	'an editor restores an earlier version with the same layout: its words come back, the title and description stay',
	function () {
		$base = wfp2_about()->post_content;
		$old  = wfp2_admin_save( 'Version A words.', false, $base );
		wfp2_admin_save( 'Version B words.', false, $base );
		wp_update_post(
			array(
				'ID'           => page_id( 'about' ),
				'post_title'   => 'About now',
				'post_excerpt' => 'The description now.',
			)
		);
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$res = wfp2_restore( $old );
			assert_matches( '/post\.php\?post=' . page_id( 'about' ) . '&(amp;)?action=edit&message=5&revision=' . $old . '/', (string) $res['redirect'], $role . ': back to the editor with core\'s "restored" message' );
			$about = wfp2_about();
			assert_contains( 'Version A words.', $about->post_content, $role . ': the words came back' );
			assert_not_contains( 'Version B words.', $about->post_content, $role );
			assert_same( 'About now', $about->post_title, $role . ': the title stays' );
			assert_same( 'The description now.', $about->post_excerpt, $role . ': the search-engine description stays' );
			wfp2_admin_save( 'Version B words.', false, $base );
		}
	}
);

test(
	'an editor can\'t restore a version from before a layout change: one sentence, and the page is unchanged',
	function () {
		$base = wfp2_about()->post_content;
		$old  = wfp2_admin_save( 'Version L words.', true, $base );
		wfp2_admin_save( 'Version B words.', false, $base );
		$before = wfp2_about()->post_content;
		as_role( 'ares-editor' );
		$res = wfp2_restore( $old );
		assert_same( 403, $res['status'], 'refused' );
		assert_contains( 'Not restored: that version has a different layout from the page now', (string) $res['die'], 'the sentence' );
		assert_contains( 'webmaster', (string) $res['die'], 'says who can' );
		assert_same( $before, wfp2_about()->post_content, 'About as stored' );
	}
);

test(
	'an editor can\'t restore a version with never-publish words the page doesn\'t have',
	function () {
		$base = wfp2_about()->post_content;
		$old  = wfp2_admin_save( 'Our hospital net meets Tuesdays.', false, $base );
		wfp2_admin_save( 'Version B words.', false, $base );
		as_role( 'ares-editor' );
		$res = wfp2_restore( $old );
		assert_same( 403, $res['status'], 'refused' );
		assert_contains( 'Not restored: that version mentions a hospital net, which we never publish.', (string) $res['die'], 'the sentence' );
		assert_not_contains( 'hospital net', wfp2_about()->post_content, 'About as stored' );
	}
);

test(
	'administrators are left to core\'s restore; a restore without a valid nonce is refused',
	function () {
		$base = wfp2_about()->post_content;
		$old  = wfp2_admin_save( 'Version L words.', true, $base );
		wfp2_admin_save( 'Version B words.', false, $base );
		as_role( 'admin' );
		$res = wfp2_restore( $old );
		assert_same( null, $res['redirect'], 'the plugin does nothing for an administrator' );
		assert_same( null, $res['die'], 'no refusal for an administrator' );

		as_role( 'ares-editor' );
		$res = call_request(
			'GET',
			array(
				'action'   => 'restore',
				'revision' => $old,
				'_wpnonce' => 'nope',
			),
			array(),
			'spokares_revision_restore_for_editors'
		);
		assert_true( null !== $res['die'], 'refused without a valid nonce' );
		assert_not_contains( 'Version L words.', wfp2_about()->post_content, 'About as stored' );
	}
);

test(
	'the Dashboard\'s Page Text section links each page\'s earlier versions for an editor',
	function () {
		$base = wfp2_about()->post_content;
		$rev  = wfp2_admin_save( 'Version A words.', false, $base );
		as_role( 'ares-editor' );
		ob_start();
		\spokares_dashboard_widget();
		$html = (string) ob_get_clean();
		assert_contains( 'Earlier versions:', $html, 'the line' );
		assert_matches( '#revision\.php\?revision=' . $rev . '"#', $html, 'About\'s link opens its newest earlier version' );
		assert_same( $rev, \spokares_page_latest_revision( page_id( 'about' ) ), 'spokares_page_latest_revision()' );
	}
);
