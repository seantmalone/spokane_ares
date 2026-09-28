<?php
/**
 * Regression tests for QA-044 (PLAN §5.5 deletion guard: "nothing deletes a
 * file a document or page still uses except Replace, Trash and Pull, which
 * clear the reference first").
 *
 * files.php spokares_attachment_uses() looks for documents with
 * post_status 'any', and WP_Query's 'any' leaves out the statuses that are
 * excluded from search, trash among them. So once a document is moved to the
 * Trash WITHOUT "Also remove its file from the web" (which keeps spk_file and
 * the file, so the document can be restored), the guard no longer sees it:
 * an administrator (or a core Editor, for a file they uploaded) can delete
 * the attachment from the Media Library or over REST, and when the document
 * is restored its spk_file points at an attachment that no longer exists.
 * While the document was published the same delete was refused.
 *
 * Correct behaviour: a document in the Trash still counts as a use of its
 * file (it can be restored), so the guard refuses the delete and the file
 * stays on disk; the restored document still has its file. Deleting the
 * document for good (prior-orphan-files-test.php) and Trash with the box
 * ticked (qa-018-undo-trash-file-test.php) still take the file off the web.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published document with an uploaded PDF, stored as the Documents form
 * stores one (source Upload, spk_file, the attachment attached to the
 * document). The document is created by the administrator; the attachment's
 * author is the given role's account. Returns array( document ID,
 * attachment ID, file path ).
 *
 * @param string $title    Title.
 * @param string $uploader Role key that owns the attachment.
 */
function qa_044_published_upload( string $title, string $uploader ): array {
	as_role( 'admin' );
	$doc = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'publish',
		)
	);
	wp_set_object_terms( $doc, 'forms', 'spk_doc_cat', false );
	$up = wp_upload_bits( 'qa-044-' . bin2hex( random_bytes( 4 ) ) . '.pdf', null, "%PDF-1.4\n% spokares QA-044 file\n%%EOF\n" );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => $title . ' file',
			'post_mime_type' => 'application/pdf',
			'post_status'    => 'inherit',
			'post_author'    => user_id( $uploader ),
		),
		$up['file'],
		$doc,
		true
	);
	if ( is_wp_error( $att ) ) {
		fail( 'wp_insert_attachment: ' . $att->get_error_message() );
	}
	update_post_meta( $doc, 'spk_source', 'upload' );
	update_post_meta( $doc, 'spk_file', (int) $att );
	update_post_meta( $doc, 'spk_format', 'PDF' );
	update_post_meta( $doc, 'spk_privacy_ok', '1' );
	assert_same( 'publish', get_post_status( $doc ), 'setup: the document is published' );
	assert_true( file_exists( (string) $up['file'] ), 'setup: the file is on disk' );
	return array( $doc, (int) $att, (string) $up['file'] );
}

/**
 * Move to Trash from the document form with "Also remove its file from the
 * web" unticked, as post.php?action=trash runs it (the link's nonce in $_GET,
 * no spk_remove_file), as the administrator.
 *
 * @param int $doc Document.
 */
function qa_044_trash_keep_file( int $doc ): void {
	as_role( 'admin' );
	$r = call_request(
		'GET',
		array(
			'post'     => $doc,
			'action'   => 'trash',
			'_wpnonce' => wp_create_nonce( 'trash-post_' . $doc ),
		),
		array(),
		static function () use ( $doc ) {
			return wp_trash_post( $doc );
		}
	);
	assert_true( $r['returned'] instanceof \WP_Post, 'setup: wp_trash_post() trashed the document' );
	clean_post_cache( $doc );
	assert_same( 'trash', get_post_status( $doc ), 'setup: the document is in the Trash' );
}

/**
 * The document still owns a live file: spk_file names an attachment that
 * exists and whose file is on disk.
 *
 * @param int    $doc    Document.
 * @param int    $att    Attachment.
 * @param string $file   File path.
 * @param string $prefix Message prefix.
 */
function qa_044_assert_file_kept( int $doc, int $att, string $file, string $prefix ): void {
	clean_post_cache( $att );
	assert_same( 'attachment', get_post_type( $att ), $prefix . ': the attachment a restorable document uses was deleted' );
	assert_true( file_exists( $file ), $prefix . ': the file a restorable document uses was removed from disk' );
	assert_same( $att, absint( get_post_meta( $doc, 'spk_file', true ) ), $prefix . ': the document still names its file' );
}

test(
	'control: the guard refuses to delete the file of a published document',
	function () {
		list( $doc, $att, $file ) = qa_044_published_upload( 'QA-044 published doc', 'admin' );
		as_role( 'admin' );
		assert_contains( 'QA-044 published doc', \spokares_attachment_uses( $att ), 'the published document counts as a use' );
		$res = wp_delete_attachment( $att, true );
		assert_true( empty( $res ), 'wp_delete_attachment() of a published document\'s file is refused' );
		qa_044_assert_file_kept( $doc, $att, $file, 'published' );
	}
);

test(
	'a document in the Trash (file kept) still counts as a use of its file',
	function () {
		list( $doc, $att ) = qa_044_published_upload( 'QA-044 trashed doc uses', 'admin' );
		qa_044_trash_keep_file( $doc );
		assert_contains( 'QA-044 trashed doc uses', \spokares_attachment_uses( $att ), 'spokares_attachment_uses() lists the trashed document' );
	}
);

test(
	'an administrator cannot delete the file of a trashed document; restoring it keeps its file',
	function () {
		list( $doc, $att, $file ) = qa_044_published_upload( 'QA-044 trashed doc admin', 'admin' );
		qa_044_trash_keep_file( $doc );
		assert_true( file_exists( $file ), 'setup: Trash without the box keeps the file' );

		as_role( 'admin' );
		$res = wp_delete_attachment( $att, true );
		assert_true( empty( $res ), 'wp_delete_attachment() of a trashed document\'s file is refused' );
		qa_044_assert_file_kept( $doc, $att, $file, 'after the delete attempt' );

		// Restore from the Trash (core gives a Draft): it still has its file.
		assert_true( wp_untrash_post( $doc ) instanceof \WP_Post, 'setup: the document was restored' );
		clean_post_cache( $doc );
		assert_same( 'draft', get_post_status( $doc ), 'setup: Restore gives a Draft' );
		qa_044_assert_file_kept( $doc, $att, $file, 'restored' );
		assert_true( '' !== (string) wp_get_attachment_url( $att ), 'restored: the document\'s file still has a web address' );
	}
);

test(
	'REST DELETE /wp/v2/media/N?force=true of a trashed document\'s file is refused',
	function () {
		list( $doc, $att, $file ) = qa_044_published_upload( 'QA-044 trashed doc rest', 'admin' );
		qa_044_trash_keep_file( $doc );

		as_role( 'admin' );
		$res = rest( 'DELETE', '/wp/v2/media/' . $att, array( 'force' => true ) );
		expect_wp_error( $res, '', 0, 'REST delete of a trashed document\'s file' );
		qa_044_assert_file_kept( $doc, $att, $file, 'after REST DELETE' );
	}
);

test(
	'a core Editor cannot delete their own upload while a trashed document uses it',
	function () {
		list( $doc, $att, $file ) = qa_044_published_upload( 'QA-044 trashed doc core editor', 'core-editor' );
		qa_044_trash_keep_file( $doc );

		as_role( 'core-editor' );
		assert_true( current_user_can( 'delete_post', $att ), 'setup: the core Editor may delete their own upload' );
		$res = wp_delete_attachment( $att, true );
		assert_true( empty( $res ), 'wp_delete_attachment() by the core Editor of a trashed document\'s file is refused' );
		qa_044_assert_file_kept( $doc, $att, $file, 'core Editor' );
	}
);

test(
	'deleting the trashed document for good still takes its file off the web',
	function () {
		list( $doc, $att, $file ) = qa_044_published_upload( 'QA-044 trashed doc purge', 'admin' );
		qa_044_trash_keep_file( $doc );
		as_role( 'admin' );
		assert_true( (bool) wp_delete_post( $doc, true ), 'Delete Permanently' );
		clean_post_cache( $att );
		assert_same( null, get_post( $att ), 'the attachment went with the document' );
		assert_false( file_exists( $file ), 'the file went with the document' );
	}
);
