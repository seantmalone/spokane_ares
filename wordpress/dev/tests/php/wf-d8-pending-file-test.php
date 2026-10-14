<?php
/**
 * Regression tests for the workflow review's finding D8 (ux/workflows, "Any
 * refusal means choosing the file again").
 *
 * Browsers can't keep a chosen file across a reload, so a save refused for
 * another box (a missing section, a never-publish word in the name) sent the
 * editor back to the file picker. Now a file that passes its own checks
 * (type, size, inspection, the "I checked" tick) on a refused save waits for
 * the document (meta spk_pending_file: attachment, name, expiry, who): it is
 * uploaded and set aside, not the document's file, and the form says "Your
 * file X is uploaded and waiting: fix the boxes outlined in red, then
 * Publish (no need to choose it again)". The next save that goes through
 * uses it. On a live document the file on the site stays until then. A
 * waiting file unused for 24 hours, replaced by another choice, or whose
 * document is taken off the site or deleted, is deleted (a lazy sweep on
 * admin loads, no cron), never one a document or page uses.
 *
 * PHP tests can't make a real upload (is_uploaded_file()), so the waiting
 * file is made here the way the save makes it (an attachment of the
 * document, then spokares_document_set_pending()); the upload itself on a
 * refused save is covered by dev/tests/e2e/wf-docs.test.mjs.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A PDF attachment of a document, as media_handle_upload() stores one.
 *
 * @param int $doc Document.
 */
function wf_d8_attachment( int $doc ): int {
	// As the document form allows its types for the one upload (spokares_document_do_upload()).
	add_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	$up = wp_upload_bits( 'wf-d8-' . bin2hex( random_bytes( 4 ) ) . '.pdf', null, "%PDF-1.4\n% spokares wf-d8 file\n%%EOF\n" );
	remove_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => 'WF-D8 file',
			'post_mime_type' => 'application/pdf',
			'post_status'    => 'inherit',
		),
		$up['file'],
		$doc,
		true
	);
	if ( is_wp_error( $att ) ) {
		fail( 'wp_insert_attachment: ' . $att->get_error_message() );
	}
	return (int) $att;
}

/**
 * A file waiting for a document, as a refused save leaves it.
 *
 * @param int $doc Document.
 */
function wf_d8_waiting( int $doc ): int {
	$att = wf_d8_attachment( $doc );
	\spokares_document_set_pending( $doc, $att, 'Net script v4.pdf' );
	return $att;
}

/**
 * A new document that went back to Draft after Publish (upload chosen, file
 * waiting), as the editor sees it.
 *
 * @param string $title Title.
 */
function wf_d8_draft( string $title ): int {
	as_role( 'ares-editor' );
	$doc = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'draft',
			'post_author' => user_id( 'ares-editor' ),
		)
	);
	update_post_meta( $doc, 'spk_source', 'upload' );
	return $doc;
}

/**
 * Is the attachment's file on the web (at its own address)?
 *
 * @param int $att Attachment.
 */
function wf_d8_on_web( int $att ): bool {
	return ! \spokares_attachment_is_held( $att ) && is_file( (string) get_attached_file( $att ) );
}

test(
	'the form says the file is waiting, and the file box needs no new choice or tick',
	function () {
		$doc = wf_d8_draft( 'WF-D8 form' );
		wf_d8_waiting( $doc );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_contains( 'Your file Net script v4.pdf is uploaded and waiting: fix the boxes outlined in red, then Publish (no need to choose it again).', $html, 'the line on a draft' );
		assert_false( (bool) preg_match( '#id="spk-privacy"[^>]*required#', $html ), 'the tick is not required' );
		$check = \spokares_document_check( \spokares_document_form_values( get_post( $doc ) ), $doc, array(), null );
		assert_false( isset( $check['problems']['upload'] ), 'no "Choose the file"' );
		assert_same( 'pending', $check['upload'], 'the waiting file stands for the file' );

		$live = uxdoc_published( 'WF-D8 live form', 'upload' );
		wf_d8_waiting( $live );
		as_role( 'ares-editor' );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $live ) );
		assert_contains( 'is uploaded and waiting: fix the boxes outlined in red, then Save (no need to choose it again).', $html, 'Save on a live document' );
		assert_contains( 'spk-current-file', $html, 'the live file is still the current file' );
	}
);

test(
	'a waiting file is set aside off the web, attached to its document, with its time',
	function () {
		$doc = wf_d8_draft( 'WF-D8 held' );
		$att = wf_d8_waiting( $doc );
		$p   = \spokares_document_pending( $doc );
		assert_same( $att, $p['id'], 'the waiting file' );
		assert_same( 'Net script v4.pdf', $p['name'], 'its name as the editor knows it' );
		assert_true( $p['expires'] > time() + 23 * HOUR_IN_SECONDS && $p['expires'] <= time() + DAY_IN_SECONDS, '24 hours' );
		assert_same( user_id( 'ares-editor' ), $p['by'], 'who chose it' );
		assert_same( $doc, (int) get_post_field( 'post_parent', $att ), 'attached to the document (so the media window hides it from those who cannot edit it)' );
		assert_true( \spokares_attachment_is_held( $att ), 'set aside in the held folder' );
		assert_false( wf_d8_on_web( $att ), 'not on the web while it waits' );
		assert_same( 0, absint( get_post_meta( $doc, 'spk_file', true ) ), 'not the document\'s file' );
	}
);

test(
	'a refused Publish keeps the file waiting; the next Publish uses it without choosing it again',
	function () {
		$doc = wf_d8_draft( 'WF-D8 publish' );
		$att = wf_d8_waiting( $doc );

		$fields                = uxdoc_fields( $doc );
		$fields['post_title']  = 'WF-D8 Hospital net schedule';
		$fields['spk_section'] = 'forms';
		uxdoc_notices();
		uxdoc_click( $doc, $fields, 'publish' );
		assert_same( 'draft', get_post_status( $doc ), 'refused: still a draft' );
		assert_same( $att, \spokares_document_pending( $doc )['id'] ?? 0, 'the file still waits' );
		assert_same( 0, absint( get_post_meta( $doc, 'spk_file', true ) ), 'and is not the document\'s file' );
		$notices = implode( ' ', uxdoc_notices() );
		assert_contains( 'Not published yet. Fix the boxes outlined in red, then click Publish.', $notices, 'the refusal' );
		assert_contains( 'Your file Net script v4.pdf is uploaded and waiting: no need to choose it again.', $notices, 'the notice says the file waits' );

		$fields['post_title'] = 'WF-D8 net schedule';
		uxdoc_click( $doc, $fields, 'publish' );
		assert_same( 'publish', get_post_status( $doc ), 'published' );
		assert_same( $att, absint( get_post_meta( $doc, 'spk_file', true ) ), 'the waiting file is the document\'s file' );
		assert_same( 'Net script v4.pdf', (string) get_post_meta( $doc, 'spk_file_name', true ), 'with its name' );
		assert_same( '1', (string) get_post_meta( $doc, 'spk_privacy_ok', true ), 'checked (it was ticked when chosen)' );
		assert_same( 'PDF', (string) get_post_meta( $doc, 'spk_format', true ), 'its format' );
		assert_same( \spokares_today(), (string) get_post_meta( $doc, 'spk_reviewed', true ), 'a new file with the tick is a review (D2)' );
		assert_true( wf_d8_on_web( $att ), 'back on the web' );
		assert_false( metadata_exists( 'post', $doc, 'spk_pending_file' ), 'no longer waiting' );
		assert_false( metadata_exists( 'post', $att, '_spk_pending_for' ), 'the attachment is no longer marked waiting' );
		assert_same( array( 'success: Published.' ), uxdoc_notices(), 'one notice' );
	}
);

test(
	'a live document keeps its file on the site while the new one waits, and swaps only on a save that goes through',
	function () {
		$doc  = uxdoc_published( 'WF-D8 live', 'upload' );
		$live = absint( get_post_meta( $doc, 'spk_file', true ) );
		$att  = wf_d8_waiting( $doc );
		as_role( 'ares-editor' );
		$fields               = uxdoc_fields( $doc );
		$fields['post_title'] = 'WF-D8 live Hospital net';
		uxdoc_notices();
		uxdoc_click( $doc, $fields );
		assert_same( 'publish', get_post_status( $doc ), 'still on the site' );
		assert_same( 'WF-D8 live', get_the_title( $doc ), 'the name is held back' );
		assert_same( $live, absint( get_post_meta( $doc, 'spk_file', true ) ), 'the live file stays' );
		assert_true( wf_d8_on_web( $live ), 'and stays on the web' );
		assert_same( $att, \spokares_document_pending( $doc )['id'] ?? 0, 'the new file still waits' );
		assert_same( array( 'warning: Saved, except the document name (outlined in red). Your file Net script v4.pdf is uploaded and waiting: no need to choose it again.' ), uxdoc_notices(), 'the notice' );

		$fields['post_title'] = 'WF-D8 live';
		uxdoc_click( $doc, $fields );
		assert_same( $att, absint( get_post_meta( $doc, 'spk_file', true ) ), 'the waiting file is on the site now' );
		assert_same( null, get_post( $live ), 'the old file is off the web' );
		assert_false( metadata_exists( 'post', $doc, 'spk_pending_file' ), 'nothing waits' );
		assert_same( array( 'success: Saved. The new file is on the site and the old one is off the web.' ), uxdoc_notices(), 'the notice' );
	}
);

test(
	'a waiting file past its 24 hours is not used, and the lazy sweep deletes it',
	function () {
		$doc = wf_d8_draft( 'WF-D8 expired' );
		$att = wf_d8_waiting( $doc );
		$p   = get_post_meta( $doc, 'spk_pending_file', true );

		$p['expires'] = time() - 1;
		update_post_meta( $doc, 'spk_pending_file', $p );
		assert_same( null, \spokares_document_pending( $doc ), 'expired: no waiting file' );
		$check = \spokares_document_check( \spokares_document_form_values( get_post( $doc ) ), $doc, array(), null );
		assert_true( isset( $check['problems']['upload'] ), 'the form asks for the file again' );

		$fresh_doc = wf_d8_draft( 'WF-D8 fresh' );
		$fresh     = wf_d8_waiting( $fresh_doc );
		set_transient( 'spokares_pending_sweep', 1, HOUR_IN_SECONDS );
		assert_same( 0, \spokares_sweep_pending_files(), 'at most once an hour' );
		assert_same( 1, \spokares_sweep_pending_files( true ), 'one file swept' );
		assert_same( null, get_post( $att ), 'the expired file is deleted' );
		assert_false( metadata_exists( 'post', $doc, 'spk_pending_file' ), 'its document no longer waits' );
		assert_not_same( null, get_post( $fresh ), 'a file still in its time stays' );
		assert_same( $fresh, \spokares_document_pending( $fresh_doc )['id'] ?? 0, 'and still waits' );
	}
);

test(
	'the sweep also deletes a waiting file its document no longer points at, and opening the form drops an expired one',
	function () {
		$doc = wf_d8_draft( 'WF-D8 stray' );
		$att = wf_d8_waiting( $doc );
		delete_post_meta( $doc, 'spk_pending_file' );
		\spokares_sweep_pending_files( true );
		assert_same( null, get_post( $att ), 'a stray waiting file is deleted' );

		$att = wf_d8_waiting( $doc );
		$p   = get_post_meta( $doc, 'spk_pending_file', true );

		$p['expires'] = time() - 1;
		update_post_meta( $doc, 'spk_pending_file', $p );
		$html = uxdoc_render( '\spokares_document_form_fields', get_post( $doc ) );
		assert_not_contains( 'is uploaded and waiting', $html, 'no line for an expired file' );
		assert_same( null, get_post( $att ), 'the form dropped it' );
	}
);

test(
	'another choice replaces the waiting file: a different file waits instead, or a link is saved',
	function () {
		$doc   = wf_d8_draft( 'WF-D8 replaced' );
		$first = wf_d8_waiting( $doc );
		$next  = wf_d8_waiting( $doc );
		assert_same( null, get_post( $first ), 'the first waiting file is deleted when another waits' );
		assert_same( $next, \spokares_document_pending( $doc )['id'] ?? 0, 'the new one waits' );

		$fields                   = uxdoc_fields( $doc );
		$fields['spk_section']    = 'forms';
		$fields['spk_source']     = 'link';
		$fields['spk_url']        = 'https://example.org/wf-d8';
		$fields['spk_privacy_ok'] = '1';
		uxdoc_click( $doc, $fields, 'publish' );
		assert_same( 'publish', get_post_status( $doc ), 'set-up: published as a link' );
		assert_same( null, get_post( $next ), 'the waiting file is deleted' );
		assert_false( metadata_exists( 'post', $doc, 'spk_pending_file' ), 'nothing waits' );
		uxdoc_notices();
	}
);

test(
	'taking the document off the site or deleting it deletes its waiting file',
	function () {
		$doc = wf_d8_draft( 'WF-D8 trashed' );
		$att = wf_d8_waiting( $doc );
		wp_trash_post( $doc );
		assert_same( null, get_post( $att ), 'trashed: deleted' );

		$doc = wf_d8_draft( 'WF-D8 deleted' );
		$att = wf_d8_waiting( $doc );
		as_role( 'admin' );
		wp_delete_post( $doc, true );
		assert_same( null, get_post( $att ), 'deleted for good: deleted' );
	}
);

test(
	'a waiting file another document uses is never deleted (the in-use guard)',
	function () {
		$doc   = wf_d8_draft( 'WF-D8 shared' );
		$att   = wf_d8_waiting( $doc );
		$other = uxdoc_published( 'WF-D8 other', 'link' );
		update_post_meta( $other, 'spk_source', 'upload' );
		update_post_meta( $other, 'spk_file', $att );
		\spokares_document_drop_pending( $doc );
		assert_not_same( null, get_post( $att ), 'kept: another document uses it' );
		assert_false( metadata_exists( 'post', $doc, 'spk_pending_file' ), 'but no longer waiting' );
		\spokares_release_attachment( $att );
	}
);
