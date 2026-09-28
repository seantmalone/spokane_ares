<?php
/**
 * Regression tests for QA-018 (PLAN §5.5 "Trash removes the old file from the
 * web by default"; §3.4 a published document always has somewhere to go).
 *
 * The document form's "Take it off the site" link (the trash link) carries
 * spk_remove_file=1 (UX spec §3.9: always, there is no tick any more), and
 * admin-documents.php spokares_document_trashed() then deletes the file and
 * clears spk_file. The list then says "1 document moved to the Trash. Undo".
 * Undo is edit.php?doaction=undo&action=untrash, where core adds
 * wp_untrash_post_set_previous_status to wp_untrash_post_status, so the
 * document goes back to its old status: Published, source Upload, and no
 * file. The library lists it as "Soon" with a PDF badge, /docs/<slug>/ is a
 * 404, and nothing tells the user.
 *
 * (A plain Restore from the Trash view already gives a Draft: that is core's
 * default since WordPress 5.6. Only Undo restores the old status.)
 *
 * Correct behaviour: a document whose file was removed when it was trashed
 * comes back from Undo as a Draft, and the list's one notice says to upload
 * the file again (§3.6: "“X” is back as a draft: upload the file again, then
 * Publish."). A document trashed without removing its file (not through the
 * form's link) still comes back Published.
 *
 * The two requests are run as core runs them: the trash as post.php's
 * action=trash (its nonce and spk_remove_file in $_GET, then
 * wp_trash_post()), and Undo as edit.php's case 'untrash' with
 * doaction=undo (the previous-status filter added around wp_untrash_post()).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published document by the role's account with an uploaded PDF, stored as
 * the Documents form stores one (source Upload, spk_file, attached to the
 * document), then sign in as that role. The file is written as an
 * administrator: only the Documents form lets an ARES Editor upload a PDF.
 * Returns array( document ID, attachment ID, file path ).
 *
 * @param string $role  Role key that owns the document and signs in after.
 * @param string $title Title.
 */
function qa_018_published_upload( string $role, string $title ): array {
	as_role( 'admin' );
	$doc = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_author' => user_id( $role ),
		)
	);
	wp_set_object_terms( $doc, 'forms', 'spk_doc_cat', false );
	$up = wp_upload_bits( 'qa-018-' . bin2hex( random_bytes( 4 ) ) . '.pdf', null, "%PDF-1.4\n% spokares QA-018 file\n%%EOF\n" );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => $title . ' file',
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
	update_post_meta( $doc, 'spk_source', 'upload' );
	update_post_meta( $doc, 'spk_file', (int) $att );
	update_post_meta( $doc, 'spk_format', 'PDF' );
	update_post_meta( $doc, 'spk_privacy_ok', '1' );
	assert_same( 'publish', get_post_status( $doc ), 'setup: the document is published' );
	assert_true( file_exists( (string) $up['file'] ), 'setup: the file is on disk' );
	as_role( $role );
	return array( $doc, (int) $att, (string) $up['file'] );
}

/**
 * Move to Trash from the document form, as post.php?action=trash does it:
 * the link's nonce (and spk_remove_file=1 when the box is ticked) in $_GET,
 * the delete_post check, then wp_trash_post().
 *
 * @param int  $doc         Document.
 * @param bool $remove_file Through the form's link (spk_remove_file=1).
 */
function qa_018_trash( int $doc, bool $remove_file ): void {
	$get = array(
		'post'     => $doc,
		'action'   => 'trash',
		'_wpnonce' => wp_create_nonce( 'trash-post_' . $doc ),
	);
	if ( $remove_file ) {
		$get['spk_remove_file'] = '1';
	}
	$r = call_request(
		'GET',
		$get,
		array(),
		static function () use ( $doc ) {
			if ( ! current_user_can( 'delete_post', $doc ) ) {
				fail( 'setup: the user may not trash the document' );
			}
			return wp_trash_post( $doc );
		}
	);
	assert_true( $r['returned'] instanceof \WP_Post, 'setup: wp_trash_post() trashed the document' );
	clean_post_cache( $doc );
	assert_same( 'trash', get_post_status( $doc ), 'setup: the document is in the Trash' );
}

/**
 * Click Undo on "1 document moved to the Trash.", as edit.php's case
 * 'untrash' runs it for doaction=undo: core adds
 * wp_untrash_post_set_previous_status (priority 10) around wp_untrash_post().
 *
 * @param int $doc Document.
 */
function qa_018_undo( int $doc ): void {
	$r = call_request(
		'GET',
		array(
			'post_type' => 'spk_document',
			'doaction'  => 'undo',
			'action'    => 'untrash',
			'ids'       => (string) $doc,
			'_wpnonce'  => wp_create_nonce( 'bulk-posts' ),
		),
		array(),
		static function () use ( $doc ) {
			add_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3 );
			try {
				if ( ! current_user_can( 'delete_post', $doc ) ) {
					fail( 'setup: the user may not restore the document' );
				}
				return wp_untrash_post( $doc );
			} finally {
				remove_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10 );
			}
		}
	);
	assert_true( $r['returned'] instanceof \WP_Post, 'Undo: wp_untrash_post() restored the document' );
	assert_same( null, $r['die'], 'Undo: no wp_die()' );
	clean_post_cache( $doc );
}

test(
	'Undo after "Take it off the site" (file removed) brings the document back as a Draft, not Published',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			list( $doc, $att, $file ) = qa_018_published_upload( $role, 'QA-018 upload doc ' . $role );
			qa_018_trash( $doc, true );

			// The trash really removed the file (the premise of the bug).
			clean_post_cache( $att );
			assert_same( null, get_post( $att ), $role . ': setup: the trash removed the attachment' );
			assert_false( file_exists( $file ), $role . ': setup: the trash removed the file from disk' );
			assert_same( '', (string) get_post_meta( $doc, 'spk_file', true ), $role . ': setup: spk_file is cleared' );

			qa_018_undo( $doc );
			assert_same( 'upload', (string) get_post_meta( $doc, 'spk_source', true ), $role . ': the document still says its source is an upload' );
			assert_same( '', (string) get_post_meta( $doc, 'spk_file', true ), $role . ': and it has no file' );
			assert_same( 'draft', get_post_status( $doc ), $role . ': Undo restored a document with no file as' );
			$live = get_posts(
				array(
					'post_type'   => 'spk_document',
					'post_status' => 'publish',
					'include'     => array( $doc ),
					'fields'      => 'ids',
				)
			);
			assert_count( 0, $live, $role . ': the file-less document must not be in the published library' );
		}
	}
);

test(
	'Undo of a document whose file was removed tells the user, in one notice, to upload the file again',
	function () {
		list( $doc ) = qa_018_published_upload( 'ares-editor', 'QA-018 notice doc' );
		qa_018_trash( $doc, true );
		$key = 'spokares_notices_' . get_current_user_id();
		delete_transient( $key );

		qa_018_undo( $doc );
		$queued = get_transient( $key );
		assert_count( 0, is_array( $queued ) ? $queued : array(), 'Undo queued a notice of its own besides the list\'s' );
		// The list's notice after the redirect (edit.php?post_type=spk_document&untrashed=1).
		$r    = call_request(
			'GET',
			array(
				'post_type' => 'spk_document',
				'untrashed' => '1',
			),
			array(),
			static fn() => apply_filters( 'bulk_post_updated_messages', array(), array( 'untrashed' => 1 ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		);
		$said = (string) ( $r['returned']['spk_document']['untrashed'] ?? '' );
		assert_contains( 'QA-018 notice doc', $said, 'the notice names the document' );
		assert_contains( 'is back as a draft: upload the file again, then Publish.', $said, 'the notice says the file must be uploaded again' );
	}
);

test(
	'Undo after a Trash that kept the file (not the form\'s link) still brings the document back Published with its file',
	function () {
		foreach ( array( 'ares-editor', 'admin' ) as $role ) {
			list( $doc, $att, $file ) = qa_018_published_upload( $role, 'QA-018 kept file ' . $role );
			qa_018_trash( $doc, false );
			assert_true( file_exists( $file ), $role . ': Trash without the box keeps the file' );

			qa_018_undo( $doc );
			assert_same( 'publish', get_post_status( $doc ), $role . ': Undo restores the old status when the file is still there' );
			assert_same( $att, absint( get_post_meta( $doc, 'spk_file', true ) ), $role . ': the file is still the document\'s' );
			assert_true( file_exists( $file ), $role . ': the file is still on disk' );
		}
	}
);
