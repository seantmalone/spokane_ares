<?php
/**
 * Regression tests for a Fixer-round bug that shipped without a test
 * (build-notes/plugin.md "Fixer round" › Documents; PLAN §5.5, §8.3 #4): a
 * document deleted for good (Delete Permanently, Empty Trash, or WordPress's
 * own clean-up 30 days after Trash) left its uploaded file behind as an
 * unattached upload, still public at its address and findable by its
 * attachment number. The fix (files.php spokares_document_deleted(), on
 * before_delete_post, loaded everywhere) deletes the file with the document
 * unless another document or a page still uses it.
 *
 * The must-use plugin's half (attachment pages 404, never a redirect to the
 * file) is checked by checks.sh for ?attachment_id= and ?p=, and by
 * dev/tests/e2e/prior-orphan-files.test.mjs for the other addresses.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Upload a small PDF as an attachment (as the Documents form stores one) and
 * return array( attachment ID, file path ). The framework deletes attachments
 * a test adds.
 *
 * @param string $name File name.
 */
function prior_orphan_upload( string $name ): array {
	$up = wp_upload_bits( $name, null, "%PDF-1.4\n% spokares dev test file\n%%EOF\n" );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => 'QA orphan test file',
			'post_mime_type' => 'application/pdf',
			'post_status'    => 'inherit',
		),
		$up['file'],
		0,
		true
	);
	if ( is_wp_error( $att ) ) {
		fail( 'wp_insert_attachment: ' . $att->get_error_message() );
	}
	return array( (int) $att, (string) $up['file'] );
}

/**
 * A published document whose file is the attachment.
 *
 * @param string $title Title.
 * @param int    $att   Attachment ID.
 */
function prior_orphan_document( string $title, int $att ): int {
	$id = create_post(
		array(
			'post_type'  => 'spk_document',
			'post_title' => $title,
		)
	);
	update_post_meta( $id, 'spk_file', $att );
	return $id;
}

test(
	'deleting a document for good deletes its file and attachment',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = prior_orphan_upload( 'qa-orphan-delete.pdf' );
		$doc                = prior_orphan_document( 'QA orphan document', $att );
		assert_true( file_exists( $file ), 'the file is there before' );
		wp_trash_post( $doc );
		assert_true( file_exists( $file ), 'Trash alone keeps the file (it can be restored)' );
		assert_true( (bool) wp_delete_post( $doc, true ), 'Delete Permanently' );
		clean_post_cache( $att );
		assert_same( null, get_post( $att ), 'attachment post' );
		assert_false( file_exists( $file ), 'file on disk' );
	}
);

test(
	'WordPress\'s own Trash clean-up (wp_scheduled_delete) deletes the file too',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = prior_orphan_upload( 'qa-orphan-cron.pdf' );
		$doc                = prior_orphan_document( 'QA orphan cron document', $att );
		wp_trash_post( $doc );
		// Trashed 31 days ago, as WP-Cron finds it.
		update_post_meta( $doc, '_wp_trash_meta_time', time() - 31 * DAY_IN_SECONDS );
		as_anonymous(); // WP-Cron runs signed out.
		wp_scheduled_delete();
		clean_post_cache( $doc );
		clean_post_cache( $att );
		assert_same( null, get_post( $doc ), 'the document is gone' );
		assert_same( null, get_post( $att ), 'attachment post' );
		assert_false( file_exists( $file ), 'file on disk' );
	}
);

test(
	'deleting a document keeps a file another document still uses',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = prior_orphan_upload( 'qa-orphan-shared.pdf' );
		$doc                = prior_orphan_document( 'QA shared file one', $att );
		$other              = prior_orphan_document( 'QA shared file two', $att );
		wp_delete_post( $doc, true );
		clean_post_cache( $att );
		assert_same( 'attachment', get_post_type( $att ), 'attachment kept' );
		assert_true( file_exists( $file ), 'file kept' );

		// The last document that uses it takes it along.
		wp_delete_post( $other, true );
		clean_post_cache( $att );
		assert_same( null, get_post( $att ), 'attachment gone with the last document' );
		assert_false( file_exists( $file ), 'file gone with the last document' );
	}
);

test(
	'deleting any other post leaves attachments alone',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = prior_orphan_upload( 'qa-orphan-event.pdf' );
		$event              = create_post(
			array(
				'post_type'  => 'spk_event',
				'post_title' => 'QA event with a stray spk_file',
			)
		);
		update_post_meta( $event, 'spk_file', $att );
		wp_delete_post( $event, true );
		clean_post_cache( $att );
		assert_same( 'attachment', get_post_type( $att ), 'attachment kept' );
		assert_true( file_exists( $file ), 'file kept' );
	}
);
