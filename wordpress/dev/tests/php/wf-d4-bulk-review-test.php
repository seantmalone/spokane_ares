<?php
/**
 * Regression tests for the workflow review's finding D4 (ux/workflows, "Bulk
 * 'Mark reviewed today' rubber-stamps").
 *
 * Any editor could tick 35 rows and mark them all reviewed without opening
 * one, and rows that were skipped got no word. Now the bulk action is the
 * administrator's only (an editor reviews a document by opening it), it
 * records who (spk_reviewed_by, also written when the form changes Last
 * reviewed), it skips documents that aren't on the site and names them in
 * its notice. Editors get no row ticks outside the Trash, as on the events
 * list.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Run the bulk action as handle_bulk_actions does, with the bulk nonce.
 *
 * @param int[] $ids Documents.
 */
function wf_d4_bulk( array $ids ): array {
	return call_request(
		'GET',
		array(
			'post_type' => 'spk_document',
			'action'    => 'spk_mark_reviewed',
			'_wpnonce'  => wp_create_nonce( 'bulk-posts' ),
		),
		array(),
		static fn() => \spokares_document_bulk_handle( 'edit.php?post_type=spk_document', 'spk_mark_reviewed', $ids )
	);
}

test(
	'editors are not offered "Mark reviewed today" in bulk, and get no row ticks outside the Trash',
	function () {
		as_role( 'ares-editor' );
		$r = call_request( 'GET', array( 'post_type' => 'spk_document' ), array(), static fn() => \spokares_document_bulk_actions( array( 'edit' => 'Edit' ) ) );
		assert_same( array(), $r['returned'], 'no bulk actions for an editor' );
		$r = call_request( 'GET', array( 'post_type' => 'spk_document' ), array(), static fn() => \spokares_document_columns( array() ) );
		assert_false( isset( $r['returned']['cb'] ), 'no row ticks' );
		$r = call_request( 'GET', array( 'post_status' => 'trash' ), array(), static fn() => \spokares_document_columns( array() ) );
		assert_true( isset( $r['returned']['cb'] ), 'the Trash keeps its row ticks (Restore, Delete permanently)' );

		as_role( 'admin' );
		$r = call_request( 'GET', array( 'post_type' => 'spk_document' ), array(), static fn() => \spokares_document_bulk_actions( array() ) );
		assert_true( isset( $r['returned']['spk_mark_reviewed'] ), 'the administrator has it' );
	}
);

test(
	'an editor who sends the bulk action anyway is refused, and nothing is marked',
	function () {
		$doc = uxdoc_published( 'WF-D4 editor', 'link' );
		as_role( 'ares-editor' );
		$r = wf_d4_bulk( array( $doc ) );
		assert_not_same( null, $r['die'], 'refused' );
		assert_same( 403, $r['status'], 'with a 403' );
		assert_same( '', (string) get_post_meta( $doc, 'spk_reviewed', true ), 'not marked' );
	}
);

test(
	'the administrator marks published documents, records who, and names the skipped drafts',
	function () {
		$one   = uxdoc_published( 'WF-D4 one', 'link' );
		$two   = uxdoc_published( 'WF-D4 two', 'link' );
		$draft = uxdoc_published( 'WF-D4 draft', 'link' );
		wp_update_post(
			array(
				'ID'          => $draft,
				'post_status' => 'draft',
			)
		);
		as_role( 'admin' );
		delete_transient( 'spokares_notices_' . get_current_user_id() );
		$r = wf_d4_bulk( array( $one, $two, $draft ) );
		assert_same( null, $r['die'], 'no refusal' );
		foreach ( array( $one, $two ) as $id ) {
			assert_same( spokares_today(), (string) get_post_meta( $id, 'spk_reviewed', true ), get_the_title( $id ) . ' marked today' );
			assert_same( user_id( 'admin' ), absint( get_post_meta( $id, 'spk_reviewed_by', true ) ), get_the_title( $id ) . ' records who' );
		}
		assert_same( '', (string) get_post_meta( $draft, 'spk_reviewed', true ), 'the draft is not marked' );
		assert_same( array( 'warning: Marked 2 documents reviewed today. Not marked, because it isn’t on the site: WF-D4 draft.' ), uxdoc_notices(), 'the notice' );
	}
);

test(
	'the form records who changed Last reviewed, and shows it',
	function () {
		$doc = uxdoc_published( 'WF-D4 form', 'link' );
		as_role( 'ares-editor' );
		$fields                 = uxdoc_fields( $doc );
		$fields['spk_reviewed'] = spokares_today();
		uxdoc_click( $doc, $fields );
		assert_same( user_id( 'ares-editor' ), absint( get_post_meta( $doc, 'spk_reviewed_by', true ) ), 'the editor is recorded' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( 'Marked by ' . esc_html( get_userdata( user_id( 'ares-editor' ) )->display_name ) . '.', $html, 'the form says who' );

		as_role( 'admin' );
		$fields                 = uxdoc_fields( $doc );
		$fields['spk_reviewed'] = spokares_today();
		uxdoc_click( $doc, $fields );
		assert_same( user_id( 'ares-editor' ), absint( get_post_meta( $doc, 'spk_reviewed_by', true ) ), 'a save that leaves the date alone keeps who' );
		$fields['spk_reviewed'] = '';
		uxdoc_click( $doc, $fields );
		assert_same( '', (string) get_post_meta( $doc, 'spk_reviewed_by', true ), 'an emptied date forgets who' );
	}
);
