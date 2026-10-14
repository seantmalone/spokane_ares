<?php
/**
 * Regression tests for the workflow review's finding D6 (ux/workflows,
 * "Taking a document down deletes its file, so Undo only half works"; PLAN
 * §5.5).
 *
 * "Take it off the site" used to delete the document's file at once, so
 * Restore or Undo brought the document back as a Draft and someone had to
 * find and upload the file again. Now the file (and any image sizes) is set
 * aside in a held folder outside the web root (files.php
 * spokares_hold_attachment(); the host runs OpenLiteSpeed, which ignores
 * .htaccess, so a folder inside public_html would still be served). The
 * attachment and the document's spk_file stay as they were. Restore or Undo
 * moves the file back and the document returns whole, as it was; Delete
 * Permanently (or WordPress's 30-day clean-up) deletes the held file. A file
 * another document or a page uses stays on the web; Pull this file now still
 * deletes outright; when the held folder can't be used the trash deletes the
 * file as before (qa-018-undo-trash-file-test.php).
 *
 * The trash and Undo run as core runs them (the helpers of
 * qa-018-undo-trash-file-test.php).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A published document with an uploaded PNG and two image sizes, stored as
 * the Documents form stores one. Returns array( document ID, attachment ID,
 * array of the files' paths: the file first, then its sizes ).
 *
 * @param string $title Title.
 */
function wf_d6_published_png( string $title ): array {
	as_role( 'admin' );
	$doc = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'publish',
		)
	);
	wp_set_object_terms( $doc, 'forms', 'spk_doc_cat', false );
	$png  = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- a 1x1 PNG.
	$name = 'wf-d6-' . bin2hex( random_bytes( 4 ) );
	add_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	$up = wp_upload_bits( $name . '.png', null, $png );
	remove_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => $title . ' file',
			'post_mime_type' => 'image/png',
			'post_status'    => 'inherit',
		),
		$up['file'],
		$doc,
		true
	);
	if ( is_wp_error( $att ) ) {
		fail( 'wp_insert_attachment: ' . $att->get_error_message() );
	}
	$dir   = dirname( (string) $up['file'] );
	$files = array( (string) $up['file'] );
	$sizes = array();
	foreach ( array( 'thumbnail', 'medium' ) as $size ) {
		$file = $name . '-' . $size . '.png';
		file_put_contents( $dir . '/' . $file, $png ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a stand-in image size.
		$sizes[ $size ] = array(
			'file'      => $file,
			'width'     => 1,
			'height'    => 1,
			'mime-type' => 'image/png',
		);
		$files[]        = $dir . '/' . $file;
	}
	wp_update_attachment_metadata(
		(int) $att,
		array(
			'width'  => 1,
			'height' => 1,
			'file'   => _wp_relative_upload_path( (string) $up['file'] ),
			'sizes'  => $sizes,
		)
	);
	update_post_meta( $doc, 'spk_source', 'upload' );
	update_post_meta( $doc, 'spk_file', (int) $att );
	update_post_meta( $doc, 'spk_file_name', 'Roster form.png' );
	update_post_meta( $doc, 'spk_format', 'PNG' );
	update_post_meta( $doc, 'spk_privacy_ok', '1' );
	clean_post_cache( $doc );
	return array( $doc, (int) $att, $files );
}

/**
 * Take the document off the site from its form, as an ARES Editor.
 *
 * @param int $doc Document.
 */
function wf_d6_take_down( int $doc ): void {
	as_role( 'ares-editor' );
	wp_update_post(
		array(
			'ID'          => $doc,
			'post_author' => user_id( 'ares-editor' ),
		)
	);
	qa_018_trash( $doc, true );
}

/**
 * The held folder of an attachment ('' when it isn't held).
 *
 * @param int $att Attachment.
 */
function wf_d6_held_dir( int $att ): string {
	$held = get_post_meta( $att, '_spk_held', true );
	return is_array( $held ) ? (string) ( $held['dir'] ?? '' ) : '';
}

/**
 * Clean up a test's held files (the framework deletes only the files at an
 * attachment's own address).
 *
 * @param int $att Attachment.
 */
function wf_d6_cleanup( int $att ): void {
	if ( $att && \spokares_attachment_is_held( $att ) ) {
		\spokares_delete_held_files( $att );
		delete_post_meta( $att, '_spk_held' );
	}
}

test(
	'the held folder is outside the WordPress folder and the uploads folder, and made for the site only',
	function () {
		$dir = \spokares_held_dir_ready();
		assert_not_same( '', $dir, 'the held folder can be used on the dev site' );
		$abs = trailingslashit( wp_normalize_path( (string) realpath( ABSPATH ) ) );
		$up  = trailingslashit( wp_normalize_path( (string) realpath( wp_upload_dir()['basedir'] ) ) );
		assert_false( str_starts_with( trailingslashit( $dir ), $abs ), 'the held folder is not under the WordPress folder (' . $dir . ')' );
		assert_false( str_starts_with( trailingslashit( $dir ), $up ), 'the held folder is not under uploads' );
		assert_same( trailingslashit( dirname( untrailingslashit( wp_normalize_path( ABSPATH ) ) ) ) . 'spokares-held', \spokares_held_dir(), 'by default it sits next to the WordPress folder' );

		// A folder the web server could serve is refused (OpenLiteSpeed ignores .htaccess).
		$inside = static fn() => wp_upload_dir()['basedir'] . '/wf-d6-held';
		add_filter( 'spokares_held_dir', $inside );
		try {
			assert_same( '', \spokares_held_dir_ready(), 'a held folder inside uploads is refused' );
		} finally {
			remove_filter( 'spokares_held_dir', $inside );
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- the test's own empty folder.
			@rmdir( wp_upload_dir()['basedir'] . '/wf-d6-held' );
		}
		$web = static fn() => ABSPATH . 'wf-d6-held';
		add_filter( 'spokares_held_dir', $web );
		try {
			assert_same( '', \spokares_held_dir_ready(), 'a held folder inside the WordPress folder is refused' );
		} finally {
			remove_filter( 'spokares_held_dir', $web );
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- the test's own empty folder.
			@rmdir( ABSPATH . 'wf-d6-held' );
		}
	}
);

test(
	'Take it off the site sets the file and its image sizes aside; Undo puts them back and the document is Published again, file and all',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 held png' );
		$url                       = (string) wp_get_attachment_url( $att );
		$attached                  = (string) get_post_meta( $att, '_wp_attached_file', true );
		try {
			wf_d6_take_down( $doc );
			clean_post_cache( $att );
			assert_same( 'attachment', get_post_type( $att ), 'the attachment is kept' );
			assert_same( $attached, (string) get_post_meta( $att, '_wp_attached_file', true ), 'the attachment keeps its own path' );
			foreach ( $files as $file ) {
				assert_false( file_exists( $file ), wp_basename( $file ) . ' is off the web (not in uploads)' );
			}
			$held = wf_d6_held_dir( $att );
			assert_not_same( '', $held, 'the attachment records its held folder' );
			assert_true( str_starts_with( wp_normalize_path( $held ), \spokares_held_dir_ready() . '/' ), 'it is in the held folder' );
			foreach ( $files as $file ) {
				assert_true( is_file( $held . '/' . wp_basename( $file ) ), wp_basename( $file ) . ' is in the held folder' );
			}
			assert_same( $att, absint( get_post_meta( $doc, 'spk_file', true ) ), 'the document still names its file' );
			assert_same( 'Roster form.png', (string) get_post_meta( $doc, 'spk_file_name', true ), 'and its name' );
			assert_true( \spokares_document_lost_file( $doc ), 'while it is held the document has no file on the web' );
			assert_same( 0, \spokares_document_stored( $doc )['file'], 'the form would show no current file' );

			qa_018_undo( $doc );
			assert_same( 'publish', get_post_status( $doc ), 'Undo brings it back Published' );
			foreach ( $files as $file ) {
				assert_true( file_exists( $file ), wp_basename( $file ) . ' is back at its address' );
			}
			assert_false( \spokares_attachment_is_held( $att ), 'nothing is held' );
			assert_false( is_dir( $held ), 'the empty held folder is gone' );
			assert_same( $url, (string) wp_get_attachment_url( $att ), 'same web address as before' );
			assert_false( \spokares_document_lost_file( $doc ), 'the document has its file' );

			// The list's one notice names it and says it is back on the site.
			$r = call_request(
				'GET',
				array(
					'post_type' => 'spk_document',
					'untrashed' => '1',
				),
				array(),
				static fn() => apply_filters( 'bulk_post_updated_messages', array(), array( 'untrashed' => 1 ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
			);
			assert_same( '“WF-D6 held png” is back on the site.', (string) ( $r['returned']['spk_document']['untrashed'] ?? '' ), 'the restore message' );
		} finally {
			wf_d6_cleanup( $att );
		}
	}
);

test(
	'a plain Restore from the Trash view brings back a draft document as a draft, with its file',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 draft restore' );
		wp_update_post(
			array(
				'ID'          => $doc,
				'post_status' => 'draft',
			)
		);
		try {
			wf_d6_take_down( $doc );
			assert_false( file_exists( $files[0] ), 'set-up: the draft\'s file is set aside too' );
			assert_true( wp_untrash_post( $doc ) instanceof \WP_Post, 'Restore' );
			clean_post_cache( $doc );
			assert_same( 'draft', get_post_status( $doc ), 'a draft comes back a draft' );
			assert_true( file_exists( $files[0] ), 'with its file' );
		} finally {
			wf_d6_cleanup( $att );
		}
	}
);

test(
	'the file of a trashed document stays protected by the in-use guard while it is held, and Delete Permanently deletes the held files',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 held purge' );
		try {
			wf_d6_take_down( $doc );
			$held = wf_d6_held_dir( $att );
			assert_not_same( '', $held, 'set-up: held' );

			as_role( 'admin' );
			assert_contains( 'WF-D6 held purge', \spokares_attachment_uses( $att ), 'the trashed document still counts as a use' );
			assert_true( empty( wp_delete_attachment( $att, true ) ), 'the Media Library can\'t delete the held file' );
			clean_post_cache( $att );
			assert_same( 'attachment', get_post_type( $att ), 'the attachment is still there' );
			assert_true( is_file( $held . '/' . wp_basename( $files[0] ) ), 'the held file is still there' );

			assert_true( (bool) wp_delete_post( $doc, true ), 'Delete Permanently' );
			clean_post_cache( $att );
			assert_same( null, get_post( $att ), 'the attachment went with the document' );
			foreach ( $files as $file ) {
				assert_false( file_exists( $held . '/' . wp_basename( $file ) ), wp_basename( $file ) . ' is deleted from the held folder' );
				assert_false( file_exists( $file ), wp_basename( $file ) . ' is not back in uploads' );
			}
			assert_false( is_dir( $held ), 'the held folder is gone' );
		} finally {
			wf_d6_cleanup( $att );
		}
	}
);

test(
	'WordPress\'s own 30-day Trash clean-up deletes a held file too',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 held cron' );
		try {
			wf_d6_take_down( $doc );
			$held = wf_d6_held_dir( $att );
			update_post_meta( $doc, '_wp_trash_meta_time', time() - 31 * DAY_IN_SECONDS );
			as_anonymous();
			wp_scheduled_delete();
			clean_post_cache( $doc );
			clean_post_cache( $att );
			assert_same( null, get_post( $doc ), 'the document is gone' );
			assert_same( null, get_post( $att ), 'the attachment is gone' );
			assert_false( file_exists( $held . '/' . wp_basename( $files[0] ) ), 'the held file is gone' );
		} finally {
			wf_d6_cleanup( $att );
		}
	}
);

test(
	'a file another document uses stays on the web when its document is taken down (not set aside)',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 shared one' );
		$other                     = create_post(
			array(
				'post_type'  => 'spk_document',
				'post_title' => 'WF-D6 shared two',
			)
		);
		update_post_meta( $other, 'spk_file', $att );
		try {
			wf_d6_take_down( $doc );
			assert_false( \spokares_attachment_is_held( $att ), 'not held' );
			assert_true( file_exists( $files[0] ), 'still on the web' );
			$queued = get_transient( 'spokares_notices_' . get_current_user_id() );
			assert_contains( 'still used by WF-D6 shared two', implode( ' ', wp_list_pluck( is_array( $queued ) ? $queued : array(), 'text' ) ), 'the notice says where it is used' );
		} finally {
			wf_d6_cleanup( $att );
		}
	}
);

test(
	'Undo when something else now sits at the file\'s old address keeps the file held and brings the document back as a draft',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 conflict' );
		try {
			wf_d6_take_down( $doc );
			file_put_contents( $files[0], 'another file' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a file now at the old address.
			qa_018_undo( $doc );
			assert_same( 'draft', get_post_status( $doc ), 'a document whose file couldn\'t go back comes back as' );
			assert_true( \spokares_attachment_is_held( $att ), 'the file stays held, nothing is overwritten' );
			assert_same( 'another file', (string) file_get_contents( $files[0] ), 'the other file is untouched' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local test file.
		} finally {
			wf_d6_cleanup( $att );
			wp_delete_file( $files[0] );
		}
	}
);

test(
	'Pull this file now still deletes the file outright (never held)',
	function () {
		list( $doc, $att, $files ) = wf_d6_published_png( 'WF-D6 pull' );
		try {
			as_role( 'admin' );
			$r = get_action( 'spokares_pull_file', array( 'post' => $doc ), array( 'nonce_action' => 'spokares_pull_file_' . $doc ) );
			assert_not_same( null, $r['redirect'], 'Pull redirects to the list' );
			clean_post_cache( $att );
			assert_same( null, get_post( $att ), 'the attachment is deleted' );
			foreach ( $files as $file ) {
				assert_false( file_exists( $file ), wp_basename( $file ) . ' is deleted' );
			}
			assert_same( '', wf_d6_held_dir( $att ), 'nothing was held' );
		} finally {
			wf_d6_cleanup( $att );
		}
	}
);
