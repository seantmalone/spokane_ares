<?php
/**
 * Regression tests for the workflow review's finding D1 (ux/workflows,
 * "Pages and documents are reviewed through two different interfaces").
 *
 * About had a "Mark reviewed today when I save" tick (_spk_reviewed);
 * documents had a Last reviewed date box and a "Mark reviewed today" button
 * under More options (spk_reviewed, with "Marked by X." from v0.3.0). Now
 * both use one control (admin-common.php spokares_review_control()): the
 * line "Last reviewed Oct 13, 2026 by Frank." and the tick "Mark reviewed
 * today when I save". The meta keys are kept; pages now record who too
 * (_spk_reviewed_by). Only administrators may type another date, in a box
 * under the document's More options.
 *
 * The document saves run as post.php does (uxdoc_click() in
 * ux-documents-most-used-test.php).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Save About's Page review box, as the block editor posts it.
 *
 * @param array $fields Extra fields (spk_mark_reviewed, spk_page_owner).
 */
function wf_d1_save_about( array $fields ): void {
	$about = page_id( 'about' );
	$r     = call_request(
		'POST',
		array(),
		array_merge(
			array(
				'spokares_page_review_nonce' => wp_create_nonce( 'spokares_page_review' ),
				'spk_page_owner'             => (string) get_post_meta( $about, '_spk_owner', true ),
			),
			$fields
		),
		static fn() => \spokares_save_page_review( $about )
	);
	assert_same( null, $r['die'], 'the Page review box saved' );
}

/**
 * The line beside the tick for a user and today.
 *
 * @param string $key Role key.
 */
function wf_d1_line_today( string $key ): string {
	return 'Last reviewed ' . \spokares_fmt_date( \spokares_today(), 'mdy' ) . ' by ' . get_userdata( user_id( $key ) )->display_name . '.';
}

test(
	'About and a document show the same control: the line and the tick "Mark reviewed today when I save", and no date box or button for editors',
	function () {
		as_role( 'ares-editor' );
		$about = page_id( 'about' );
		delete_post_meta( $about, '_spk_reviewed' );
		delete_post_meta( $about, '_spk_reviewed_by' );
		$doc  = uxdoc_published( 'WF-D1 same', 'link' );
		$page = uxdoc_render( '\spokares_render_page_review', get_post( $about ) );
		as_role( 'ares-editor' );
		$form = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		foreach ( array(
			'About'      => $page,
			'a document' => $form,
		) as $where => $html ) {
			assert_contains( '<p class="spk-reviewed-line">Last reviewed: not yet.</p>', $html, $where . ': the line' );
			assert_contains( '<input type="checkbox" name="spk_mark_reviewed" value="1" class="spk-mark-reviewed"> Mark reviewed today when I save', $html, $where . ': the tick' );
		}
		assert_not_contains( 'spk-reviewed-today', $form, 'the old "Mark reviewed today" button' );
		assert_not_contains( 'name="spk_reviewed"', $form, 'an editor gets no date box' );
		assert_not_contains( 'Marked by', $form, 'the old "Marked by" line' );

		as_role( 'admin' );
		$form = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_matches( '#<details class="spk-more">.*name="spk_reviewed".*</details>#s', $form, 'the administrator types a date under More options' );
		assert_contains( 'Mark reviewed today when I save', $form, 'and has the tick too' );
	}
);

test(
	'About: the tick marks today and records who; the box then says when and by whom',
	function () {
		$about = page_id( 'about' );
		update_post_meta( $about, '_spk_reviewed', '2025-01-02' );
		delete_post_meta( $about, '_spk_reviewed_by' );
		as_role( 'ares-editor' );
		$html = uxdoc_render( '\spokares_render_page_review', get_post( $about ) );
		assert_contains( 'Last reviewed Jan 2, 2025.', $html, 'a date with nobody recorded (before D1)' );

		wf_d1_save_about( array() );
		assert_same( '2025-01-02', (string) get_post_meta( $about, '_spk_reviewed', true ), 'unticked: the date stays' );
		assert_same( '', (string) get_post_meta( $about, '_spk_reviewed_by', true ), 'unticked: nobody recorded' );

		wf_d1_save_about( array( 'spk_mark_reviewed' => '1' ) );
		assert_same( \spokares_today(), (string) get_post_meta( $about, '_spk_reviewed', true ), 'ticked: today' );
		assert_same( user_id( 'ares-editor' ), absint( get_post_meta( $about, '_spk_reviewed_by', true ) ), 'ticked: who' );
		$html = uxdoc_render( '\spokares_render_page_review', get_post( $about ) );
		assert_contains( esc_html( wf_d1_line_today( 'ares-editor' ) ), $html, 'the line says when and by whom' );

		as_role( 'admin' );
		wf_d1_save_about( array( 'spk_mark_reviewed' => '1' ) );
		assert_same( user_id( 'admin' ), absint( get_post_meta( $about, '_spk_reviewed_by', true ) ), 'ticked again the same day: the new reviewer' );
	}
);

test(
	'About: the block editor script redraws the line with today and the user after a ticked save',
	function () {
		as_role( 'ares-editor' );
		global $wp_scripts;
		wp_scripts();
		wp_enqueue_script( 'wp-edit-post' );
		set_current_screen( 'page' );
		\spokares_page_review_script( 'post.php' );
		$after = implode( "\n", (array) ( $wp_scripts->registered['wp-edit-post']->extra['after'] ?? array() ) );
		assert_contains( '.spk-reviewed-line', $after, 'the script redraws the line' );
		assert_contains( wp_json_encode( wf_d1_line_today( 'ares-editor' ) ), $after, 'with today and who' );
	}
);

test(
	'a document: the tick marks today and records who; an editor cannot type a date; an unticked save changes nothing',
	function () {
		$doc = uxdoc_published( 'WF-D1 doc', 'link' );
		update_post_meta( $doc, 'spk_reviewed', '2025-03-04' );
		update_post_meta( $doc, 'spk_reviewed_by', user_id( 'admin' ) );
		as_role( 'ares-editor' );

		uxdoc_click( $doc, uxdoc_fields( $doc ) );
		assert_same( '2025-03-04', (string) get_post_meta( $doc, 'spk_reviewed', true ), 'unticked: the date stays' );
		assert_same( user_id( 'admin' ), absint( get_post_meta( $doc, 'spk_reviewed_by', true ) ), 'unticked: who stays' );

		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_reviewed' => '2020-01-01' ) ) );
		assert_same( '2025-03-04', (string) get_post_meta( $doc, 'spk_reviewed', true ), 'a date sent by an editor is ignored' );

		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_mark_reviewed' => '1' ) ) );
		assert_same( \spokares_today(), (string) get_post_meta( $doc, 'spk_reviewed', true ), 'ticked: today' );
		assert_same( user_id( 'ares-editor' ), absint( get_post_meta( $doc, 'spk_reviewed_by', true ) ), 'ticked: who' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( esc_html( wf_d1_line_today( 'ares-editor' ) ), $html, 'the line says when and by whom' );
		assert_contains( '<input type="checkbox" name="spk_mark_reviewed" value="1" class="spk-mark-reviewed">', $html, 'the tick is unticked again' );
		uxdoc_notices();
	}
);

test(
	'a document: an administrator may type another date (or empty it); the tick wins over a typed date',
	function () {
		$doc = uxdoc_published( 'WF-D1 admin', 'link' );
		as_role( 'admin' );
		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_reviewed' => '2026-01-15' ) ) );
		assert_same( '2026-01-15', (string) get_post_meta( $doc, 'spk_reviewed', true ), 'the typed date' );
		assert_same( user_id( 'admin' ), absint( get_post_meta( $doc, 'spk_reviewed_by', true ) ), 'who typed it' );

		uxdoc_click(
			$doc,
			array_merge(
				uxdoc_fields( $doc ),
				array(
					'spk_reviewed'      => '2026-01-15',
					'spk_mark_reviewed' => '1',
				)
			)
		);
		assert_same( \spokares_today(), (string) get_post_meta( $doc, 'spk_reviewed', true ), 'the tick makes it today' );

		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_reviewed' => '' ) ) );
		assert_false( metadata_exists( 'post', $doc, 'spk_reviewed' ), 'an emptied date is not stored' );
		assert_false( metadata_exists( 'post', $doc, 'spk_reviewed_by' ), 'and nobody is recorded' );
		uxdoc_notices();
	}
);

test(
	'D2, D3 and D4 still hold: a first file published with the tick is a review; the Needs review count follows the tick; bulk stays the administrator\'s',
	function () {
		// D2 (a new file) needs a real upload: dev/tests/e2e/wf-docs.test.mjs.
		// Publishing a link with a fresh tick on a document never reviewed is a review.
		$doc = uxdoc_published( 'WF-D1 d2', 'link' );
		wp_update_post(
			array(
				'ID'          => $doc,
				'post_status' => 'draft',
			)
		);
		delete_post_meta( $doc, 'spk_privacy_ok' );
		as_role( 'ares-editor' );
		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_privacy_ok' => '1' ) ), 'publish' );
		assert_same( 'publish', get_post_status( $doc ), 'set-up: published' );
		assert_same( \spokares_today(), (string) get_post_meta( $doc, 'spk_reviewed', true ), 'published with the tick: reviewed today' );

		// D3: a due document leaves Needs review once ticked.
		update_post_meta( $doc, 'spk_reviewed', \spokares_add_days( \spokares_today(), -400 ) );
		assert_true( in_array( $doc, \spokares_documents_needing_review(), true ), 'set-up: due' );
		uxdoc_click( $doc, array_merge( uxdoc_fields( $doc ), array( 'spk_mark_reviewed' => '1' ) ) );
		assert_false( in_array( $doc, \spokares_documents_needing_review(), true ), 'ticked: no longer due' );

		// D4: editors still get no bulk action.
		$r = call_request( 'GET', array( 'post_type' => 'spk_document' ), array(), static fn() => \spokares_document_bulk_actions( array() ) );
		assert_false( isset( $r['returned']['spk_mark_reviewed'] ), 'no bulk Mark reviewed today for an editor' );
		uxdoc_notices();
	}
);
