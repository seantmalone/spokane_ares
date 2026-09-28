<?php
/**
 * Regression tests for QA-017 (PLAN §3.4 documents, §5.5 files): on a
 * published document that already has a good uploaded file, choosing a file
 * that the form refuses under "Replace with" (a type outside PDF, DOCX, XLSX,
 * JPEG and PNG, or a file too big to arrive) and clicking Update demotes the
 * whole document to Draft. /docs/<slug>/ then answers 404, the row leaves
 * Documents & forms and a hub tile that points at it is lost, although the
 * current file was never touched.
 *
 * The cause: spokares_document_insert_data() passes the upload refusal to
 * spokares_document_problems(), which counts any refused upload as a
 * publishing problem, even when a good file is already attached. A mislabelled
 * fake.pdf takes the other path (it passes the form's own check, then
 * media_handle_upload() refuses it in spokares_save_document(), which demotes
 * only a document with no file yet), so it stays published.
 *
 * Correct behaviour: a refused replacement is reported ("File not uploaded:
 * …") and the document stays published with its current file. A published
 * document that has no good file still goes back to Draft.
 *
 * The saves run as the classic post.php save does: the document form's fields
 * and nonce in $_POST, the chosen file in $_FILES['spk_upload'], then
 * wp_update_post() with post_status publish (the Update button). The file
 * here is not a real HTTP upload, so is_uploaded_file() is false and the
 * form refuses it at its "didn't arrive" check before the type check. Both
 * are the same refused-replacement path through spokares_document_problems();
 * the browser repro with a real .php upload is in qa/ISSUES.md (QA-017).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The library section the test documents go in.
 */
function qa017_section(): string {
	$sections = spokares_library_sections();
	if ( ! $sections ) {
		fail( 'set-up: the site has no library sections' );
	}
	return array_key_exists( 'forms', $sections ) ? 'forms' : (string) array_key_first( $sections );
}

/**
 * A published document with a good uploaded PDF, set up as the Documents form
 * stores one (section, source upload, Privacy check, spk_file). The current
 * user is its author. Returns array( document ID, attachment ID, file path ).
 *
 * @param string $title Title.
 */
function qa017_published_with_file( string $title ): array {
	$doc = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		)
	);
	wp_set_object_terms( $doc, qa017_section(), 'spk_doc_cat', false );
	// The document allowlist, as spokares_document_do_upload() adds it.
	add_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	$up = wp_upload_bits( sanitize_title( $title ) . '-' . bin2hex( random_bytes( 4 ) ) . '.pdf', null, "%PDF-1.4\n% spokares QA-017 current file\n%%EOF\n" );
	remove_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
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
	update_post_meta( $doc, 'spk_privacy_ok', '1' );
	update_post_meta( $doc, 'spk_format', 'PDF' );
	update_post_meta( $doc, 'spk_file', (int) $att );
	clean_post_cache( $doc );
	return array( $doc, (int) $att, (string) $up['file'] );
}

/**
 * Click Update on the document's edit screen as the current user, with this
 * file (or none) chosen under "Replace with". Returns the call_request() result.
 *
 * @param int        $doc  Document.
 * @param array|null $file The $_FILES['spk_upload'] entry, or null for no file.
 */
function qa017_update( int $doc, ?array $file ): array {
	$post  = get_post( $doc );
	$terms = get_the_terms( $doc, 'spk_doc_cat' );
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification -- a simulated upload: the test sets $_FILES and puts the old one back below.
	$saved_files = $_FILES;
	$_FILES      = null === $file ? array() : array( 'spk_upload' => $file );
	try {
		$r = call_request(
			'POST',
			array(),
			array(
				'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
				'original_post_status'    => get_post_status( $doc ),
				'post_title'              => $post->post_title,
				'spk_section'             => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
				'spk_source'              => 'upload',
				'spk_privacy_ok'          => '1',
				'spk_remove_old'          => '1', // Ticked by default on the form.
				'spk_format'              => (string) get_post_meta( $doc, 'spk_format', true ),
			),
			static function () use ( $doc, $post ) {
				return wp_update_post(
					wp_slash(
						array(
							'ID'          => $doc,
							'post_title'  => $post->post_title,
							'post_status' => 'publish',
						)
					),
					true
				);
			}
		);
	} finally {
		$_FILES = $saved_files;
	}
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.Security.NonceVerification
	expect_not_wp_error( $r['returned'], 'save' );
	clean_post_cache( $doc );
	return $r;
}

/**
 * A chosen file the form refuses: a PHP script under "Replace with".
 *
 * @param string $path Where the chosen file's temporary copy is.
 */
function qa017_php_file( string $path ): array {
	file_put_contents( $path, "<?php echo 'QA-017'; ?>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a throwaway temp file for the simulated upload.
	return array(
		'name'     => 'replacement.php',
		'type'     => 'application/x-php',
		'tmp_name' => $path,
		'error'    => UPLOAD_ERR_OK,
		'size'     => (int) filesize( $path ),
	);
}

/**
 * The error notices queued for the current user (spokares_add_notice()).
 */
function qa017_error_notices(): array {
	$notices = get_transient( 'spokares_notices_' . get_current_user_id() );
	$out     = array();
	foreach ( is_array( $notices ) ? $notices : array() as $n ) {
		if ( 'error' === ( $n['type'] ?? '' ) ) {
			$out[] = (string) ( $n['text'] ?? '' );
		}
	}
	return $out;
}

/**
 * Save with a refused replacement and check the document is still live with
 * its current file, and that the refusal was reported.
 *
 * @param string     $role Role key.
 * @param array|null $file The refused $_FILES entry.
 */
function qa017_assert_refused_replacement_keeps_it_live( string $role, ?array $file ): void {
	as_role( $role );
	list( $doc, $att, $path ) = qa017_published_with_file( 'QA-017 ' . $role . ' document' );
	$slug                     = get_post_field( 'post_name', $doc );
	$url                      = (string) wp_get_attachment_url( $att );

	// Control: a plain Update (no file chosen) keeps it published, so the
	// document is otherwise publishable and the save path is the real one.
	qa017_update( $doc, null );
	assert_same( 'publish', get_post_status( $doc ), $role . ' set-up: Update with no file chosen keeps it published' );
	assert_same( $url, spokares_doc_target( $slug ), $role . ' set-up: /docs/' . $slug . '/ sends visitors to the current file' );
	delete_transient( 'spokares_notices_' . get_current_user_id() );

	qa017_update( $doc, $file );

	$notices = qa017_error_notices();
	assert_true( (bool) array_filter( $notices, static fn( $n ) => str_starts_with( $n, 'File not uploaded' ) ), $role . ': the refused file is reported (' . implode( ' | ', $notices ) . ')' );
	assert_same( $att, absint( get_post_meta( $doc, 'spk_file', true ) ), $role . ': the current file is still the document\'s file' );
	assert_true( file_exists( $path ), $role . ': the current file is still on disk' );
	assert_same( 'publish', get_post_status( $doc ), $role . ': a refused replacement must not unpublish the document' );
	assert_same( $url, spokares_doc_target( $slug ), $role . ': /docs/' . $slug . '/ still sends visitors to the current file' );
}

test(
	'ARES Editor: a .php file chosen under Replace with is refused and the document stays published with its file',
	function () {
		$tmp = wp_tempnam( 'qa-017' );
		try {
			qa017_assert_refused_replacement_keeps_it_live( 'ares-editor', qa017_php_file( $tmp ) );
		} finally {
			wp_delete_file( $tmp );
		}
	}
);

test(
	'ARES Editor: a replacement PDF too big to arrive is refused and the document stays published with its file',
	function () {
		qa017_assert_refused_replacement_keeps_it_live(
			'ares-editor',
			array(
				'name'     => 'replacement.pdf',
				'type'     => '',
				'tmp_name' => '',
				'error'    => UPLOAD_ERR_INI_SIZE,
				'size'     => 0,
			)
		);
	}
);

test(
	'ARES Editor with the net grant and administrator: a refused replacement keeps the document published',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			$tmp = wp_tempnam( 'qa-017' );
			try {
				qa017_assert_refused_replacement_keeps_it_live( $role, qa017_php_file( $tmp ) );
			} finally {
				wp_delete_file( $tmp );
			}
		}
	}
);

test(
	'guard: a published document with no good file still goes back to Draft when its chosen file is refused',
	function () {
		as_role( 'ares-editor' );
		list( $doc, $att ) = qa017_published_with_file( 'QA-017 no good file' );
		delete_post_meta( $doc, 'spk_file' );
		$tmp = wp_tempnam( 'qa-017' );
		try {
			qa017_update( $doc, qa017_php_file( $tmp ) );
		} finally {
			wp_delete_file( $tmp );
		}
		assert_same( 'draft', get_post_status( $doc ), 'nothing good to publish: it is a draft' );
		assert_same( 0, absint( get_post_meta( $doc, 'spk_file', true ) ), 'no file was attached' );
		assert_same( 'attachment', get_post_type( $att ), 'the unused attachment was not touched' );
	}
);
