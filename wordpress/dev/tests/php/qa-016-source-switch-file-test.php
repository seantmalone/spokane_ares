<?php
/**
 * Regression tests for QA-016 (PLAN §5.5, §8.3 #1 and #4): on a published
 * document with an uploaded file, changing "Where the file is" from "Upload
 * a file" to "Not available yet (Soon)" or "Link to another site" and
 * clicking Update saved with only "Saved." and left the old file on the web
 * (its address still answered 200). The form hides the upload box once the
 * source is not Upload, so the editor can no longer see or remove the file,
 * and only an administrator's "Pull this file now" took it down.
 *
 * Taking a document's file out of use must take the file off the web, as
 * Replace and Trash do by default (the form still sends "Remove the old file
 * from the web", ticked, from the hidden upload box), through the files.php
 * helper, so a file another document or a page uses is kept and the notice
 * says where.
 *
 * Not covered on purpose: clearing the Format. Link and Soon documents may
 * carry a format of their own (the library has Link documents marked PDF),
 * so a stored "PDF" after the switch is not by itself wrong.
 *
 * The save runs as WordPress's post.php does it: edit_post() with the edit
 * form's fields in $_POST, which fires wp_insert_post_data and
 * save_post_spk_document.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Store a small PDF as an attachment, the way the Documents form leaves one.
 * Runs as the current user (call as admin: PDF is an administrator's type in
 * the Media Library). The framework deletes attachments a test adds.
 *
 * @param string $name File name.
 * @return array{0:int,1:string} Attachment ID and file path.
 */
function qa016_upload( string $name ): array {
	$up = wp_upload_bits( $name, null, "%PDF-1.4\n% spokares dev test file\n%%EOF\n" );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => 'QA-016 test file',
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
 * A published, privacy-checked document in Forms whose file is the
 * attachment, as the form stores it after Publish with an upload.
 *
 * @param string $title Title.
 * @param int    $att   Attachment ID.
 */
function qa016_document( string $title, int $att ): int {
	$id = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'publish',
		)
	);
	wp_set_object_terms( $id, 'forms', 'spk_doc_cat', false );
	update_post_meta( $id, 'spk_source', 'upload' );
	update_post_meta( $id, 'spk_file', $att );
	update_post_meta( $id, 'spk_format', 'PDF' );
	update_post_meta( $id, 'spk_privacy_ok', '1' );
	update_post_meta( $id, 'spk_version', '2026' );
	return $id;
}

/**
 * Click Update on the document's edit screen with "Where the file is" set to
 * $source, as the browser sends the form: the Format select still on PDF,
 * the hidden upload box's "Remove the old file from the web" still ticked,
 * no file chosen. Runs edit_post() as post.php does, as the current user.
 *
 * @param int    $doc    Document.
 * @param string $source 'soon' or 'link'.
 * @param string $url    The web address for a link.
 */
function qa016_update_source( int $doc, string $source, string $url = '' ): array {
	$fields = array(
		'action'                  => 'editpost',
		'originalaction'          => 'editpost',
		'post_ID'                 => (string) $doc,
		'post_type'               => 'spk_document',
		'post_title'              => get_the_title( $doc ),
		'post_status'             => 'publish',
		'original_post_status'    => 'publish',
		'save'                    => 'Update',
		'_wpnonce'                => wp_create_nonce( 'update-post_' . $doc ),
		'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
		'spk_section'             => 'forms',
		'spk_source'              => $source,
		'spk_privacy_ok'          => '1',
		'spk_remove_old'          => '1',
		'spk_url'                 => $url,
		'spk_version'             => '2026',
		'spk_note'                => '',
		'spk_source_label'        => '',
		'spk_format'              => 'PDF',
	);
	return call_request(
		'POST',
		array(),
		$fields,
		static function () {
			return edit_post();
		}
	);
}

/**
 * The texts of the notices queued for the current user.
 */
function qa016_notices(): array {
	$n = get_transient( 'spokares_notices_' . get_current_user_id() );
	return is_array( $n ) ? array_map( static fn( $x ) => (string) ( $x['text'] ?? '' ), $n ) : array();
}

/**
 * The save went through the plugin's handler (not a broken simulation).
 *
 * @param array  $res    call_request() result.
 * @param int    $doc    Document.
 * @param string $source Expected source.
 */
function qa016_assert_saved( array $res, int $doc, string $source ): void {
	assert_same( null, $res['die'], 'edit_post() did not wp_die()' );
	assert_same( $doc, (int) $res['returned'], 'edit_post() returned the document' );
	clean_post_cache( $doc );
	assert_same( $source, (string) get_post_meta( $doc, 'spk_source', true ), 'the new source was saved' );
	assert_same( 'publish', get_post_status( $doc ), 'still published' );
}

test(
	'an ARES Editor switching an uploaded document to Soon takes the old file off the web',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = qa016_upload( 'qa016-soon.pdf' );
		$doc                = qa016_document( 'QA016 Soon document', $att );
		assert_true( file_exists( $file ), 'the file is on the web before' );

		as_role( 'ares-editor' );
		$res = qa016_update_source( $doc, 'soon' );
		qa016_assert_saved( $res, $doc, 'soon' );

		clean_post_cache( $att );
		assert_false( file_exists( $file ), 'the old file is still on disk, so its address still answers 200' );
		assert_same( null, get_post( $att ), 'the old attachment is still there' );
		assert_same( 0, absint( get_post_meta( $doc, 'spk_file', true ) ), 'spk_file still points at the old file' );
	}
);

test(
	'an administrator switching an uploaded document to a Link takes the old file off the web',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = qa016_upload( 'qa016-link.pdf' );
		$doc                = qa016_document( 'QA016 Link document', $att );

		$res = qa016_update_source( $doc, 'link', 'https://example.org/online-form' );
		qa016_assert_saved( $res, $doc, 'link' );
		assert_same( 'https://example.org/online-form', (string) get_post_meta( $doc, 'spk_url', true ), 'the link was saved' );

		clean_post_cache( $att );
		assert_false( file_exists( $file ), 'the old file is still on disk, so its address still answers 200' );
		assert_same( null, get_post( $att ), 'the old attachment is still there' );
		assert_same( 0, absint( get_post_meta( $doc, 'spk_file', true ) ), 'spk_file still points at the old file' );
	}
);

test(
	'switching to Soon keeps a file another document still uses, and the notice says where',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = qa016_upload( 'qa016-shared.pdf' );
		$doc                = qa016_document( 'QA016 Shared one', $att );
		qa016_document( 'QA016 Shared two', $att );

		as_role( 'ares-editor' );
		$res = qa016_update_source( $doc, 'soon' );
		qa016_assert_saved( $res, $doc, 'soon' );

		clean_post_cache( $att );
		assert_same( 'attachment', get_post_type( $att ), 'the shared attachment is kept' );
		assert_true( file_exists( $file ), 'the shared file is kept' );
		$said = implode( ' | ', qa016_notices() );
		assert_contains( 'QA016 Shared two', $said, 'no notice says the old file stays on the web because another document uses it (notices: "' . $said . '")' );
	}
);
