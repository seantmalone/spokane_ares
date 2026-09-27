<?php
/**
 * Documents › Add document and All documents (§3.4, §5.5).
 *
 * A file is uploaded only when the form is saved, only after the Privacy
 * check is ticked, only in the allowed types, with a random suffix on its
 * name. Replace and Trash remove the old file from the web by default. An
 * administrator can pull a file at once. Nothing deletes a file a document or
 * page still uses, except those paths.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formats offered (value = what the site prints).
 */
function spokares_doc_formats(): array {
	return array( 'PDF', 'DOCX', 'XLSX', 'JPEG', 'PNG', 'Online form', 'Online course', 'Online course, free', 'Video', 'Winlink form', 'Software', 'PDF or app' );
}

/**
 * File types a document may upload: extension => mime.
 */
function spokares_doc_mimes(): array {
	return array(
		'pdf'      => 'application/pdf',
		'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
	);
}

/**
 * The format word for an uploaded file's extension.
 *
 * @param string $ext Extension.
 */
function spokares_format_for_ext( string $ext ): string {
	$map = array(
		'pdf'  => 'PDF',
		'docx' => 'DOCX',
		'xlsx' => 'XLSX',
		'jpg'  => 'JPEG',
		'jpeg' => 'JPEG',
		'png'  => 'PNG',
	);
	return $map[ strtolower( $ext ) ] ?? '';
}

/* ------------------------------------------------------------ the request */

/**
 * Is this request a save of our document form for this post, with a good nonce?
 *
 * @param int $post_id Post.
 */
function spokares_document_form_ok( int $post_id ): bool {
	if ( ! isset( $_POST['spokares_document_nonce'] ) ) {
		return false;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spokares_document_nonce'] ) ), 'spokares_document_meta' ) ) {
		return false;
	}
	return current_user_can( 'edit_post', $post_id );
}

/**
 * The document form's values from the request (nonce verified by the caller).
 */
function spokares_document_submitted(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- callers verify the spokares_document_meta nonce first.
	$in     = wp_unslash( $_POST );
	$str    = static fn( $k ) => isset( $in[ $k ] ) && is_scalar( $in[ $k ] ) ? sanitize_text_field( (string) $in[ $k ] ) : '';
	$source = $str( 'spk_source' );
	$v      = array(
		'title'      => isset( $in['post_title'] ) ? sanitize_text_field( (string) $in['post_title'] ) : '',
		'section'    => sanitize_key( $str( 'spk_section' ) ),
		'source'     => in_array( $source, array( 'upload', 'link', 'soon' ), true ) ? $source : '',
		'privacy'    => ! empty( $in['spk_privacy_ok'] ),
		'url'        => $str( 'spk_url' ),
		'version'    => $str( 'spk_version' ),
		'note'       => $str( 'spk_note' ),
		'label'      => $str( 'spk_source_label' ),
		'format'     => $str( 'spk_format' ),
		'howto_lbl'  => $str( 'spk_howto_label' ),
		'howto_url'  => $str( 'spk_howto_url' ),
		'most_used'  => ! empty( $in['spk_most_used'] ),
		'owner'      => $str( 'spk_owner' ),
		'reviewed'   => $str( 'spk_reviewed' ),
		'check'      => ! empty( $in['spk_needs_check'] ),
		'keywords'   => $str( 'spk_keywords' ),
		'sublinks'   => array(),
		'remove_old' => ! empty( $in['spk_remove_old'] ),
		'confirm'    => isset( $in['spk_confirm'] ) && is_array( $in['spk_confirm'] ) ? array_map( 'sanitize_key', array_keys( array_filter( $in['spk_confirm'] ) ) ) : array(),
	);
	foreach ( (array) ( $in['spk_sublinks'] ?? array() ) as $s ) {
		if ( ! is_array( $s ) ) {
			continue;
		}
		$label = sanitize_text_field( (string) ( $s['label'] ?? '' ) );
		$url   = sanitize_text_field( (string) ( $s['url'] ?? '' ) );
		if ( '' === $label && '' === $url ) {
			continue;
		}
		$id              = sanitize_title( (string) ( $s['id'] ?? '' ) );
		$first           = strtok( $label, '.' );
		$v['sublinks'][] = array(
			'id'    => '' !== $id ? $id : sanitize_title( false !== $first && '' !== $first ? $first : $label ),
			'label' => $label,
			'title' => sanitize_text_field( (string) ( $s['title'] ?? '' ) ),
			'url'   => $url,
		);
	}
	$v['sublinks'] = array_slice( $v['sublinks'], 0, 6 );
	// phpcs:enable
	return $v;
}

/**
 * The file chosen in this request, if any: [name, tmp_name, error, size].
 */
function spokares_document_upload(): ?array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- callers verify the nonce first.
	if ( empty( $_FILES['spk_upload'] ) || ! is_array( $_FILES['spk_upload'] ) ) {
		return null;
	}
	$f     = $_FILES['spk_upload']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- file array; the name is sanitised below and the file is checked by wp_handle_upload.
	$error = (int) ( $f['error'] ?? UPLOAD_ERR_NO_FILE );
	// phpcs:enable
	if ( UPLOAD_ERR_NO_FILE === $error ) {
		return null;
	}
	return array(
		'name'     => sanitize_file_name( (string) ( $f['name'] ?? '' ) ),
		'tmp_name' => (string) ( $f['tmp_name'] ?? '' ),
		'error'    => $error,
		'size'     => (int) ( $f['size'] ?? 0 ),
	);
}

/**
 * Check a chosen file before anything is stored. Returns '' when it may be
 * uploaded, else the sentence that says why not.
 *
 * @param array $file    spokares_document_upload().
 * @param bool  $privacy Privacy check ticked.
 */
function spokares_document_upload_problem( array $file, bool $privacy ): string {
	if ( ! $privacy ) {
		return __( 'File not uploaded: tick the Privacy check first, then choose the file again.', 'spokares-core' );
	}
	if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return __( 'File not uploaded: it didn’t arrive (it may be too big). Try again, or ask the webmaster.', 'spokares-core' );
	}
	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	if ( '' === spokares_format_for_ext( $ext ) ) {
		return __( 'File not uploaded: use a PDF, DOCX, XLSX, JPEG or PNG file.', 'spokares-core' );
	}
	if ( in_array( $ext, array( 'docx', 'xlsx' ), true ) ) {
		if ( ! function_exists( 'spokares_hard_inspect_office_file' ) ) {
			return __( 'File not uploaded: Word and Excel files can’t be checked here. Upload a PDF instead, or ask the webmaster.', 'spokares-core' );
		}
		$ok = spokares_hard_inspect_office_file( $file['tmp_name'] );
		if ( is_wp_error( $ok ) ) {
			return $ok->get_error_message();
		}
	}
	return '';
}

/**
 * Field names for the problem sentences.
 */
function spokares_document_field_names(): array {
	return array(
		'title'     => __( 'the document name', 'spokares-core' ),
		'note'      => __( 'the short note', 'spokares-core' ),
		'howto_lbl' => __( 'the “How to” text', 'spokares-core' ),
		'label'     => __( 'the source', 'spokares-core' ),
		'sublinks'  => __( 'the extra-link text', 'spokares-core' ),
	);
}

/**
 * What stops a document from being published.
 *
 * @param array    $v         Values.
 * @param int      $post_id   Document.
 * @param array    $confirmed Confirmations.
 * @param string   $upload    '' (no file chosen), 'ok', or the upload problem.
 * @return array{problems:array,confirm:array}
 */
function spokares_document_problems( array $v, int $post_id, array $confirmed, string $upload ): array {
	$names    = spokares_document_field_names();
	$problems = array();
	$confirm  = array();
	if ( '' === trim( $v['title'] ) ) {
		$problems['title'] = __( 'Type the document name.', 'spokares-core' );
	}
	if ( ! array_key_exists( $v['section'], spokares_library_sections() ) ) {
		$problems['section'] = __( 'Pick the library section.', 'spokares-core' );
	}
	if ( '' === $v['source'] ) {
		$problems['source'] = __( 'Say where the file is.', 'spokares-core' );
	}
	if ( ! $v['privacy'] ) {
		$problems['privacy'] = __( 'Tick the Privacy check to publish.', 'spokares-core' );
	}
	if ( 'upload' === $v['source'] ) {
		$has = absint( get_post_meta( $post_id, 'spk_file', true ) ) && wp_get_attachment_url( absint( get_post_meta( $post_id, 'spk_file', true ) ) );
		if ( '' !== $upload && 'ok' !== $upload ) {
			$problems['upload'] = $upload;
		} elseif ( ! $has && 'ok' !== $upload ) {
			$problems['upload'] = __( 'Choose the file to upload.', 'spokares-core' );
		}
	}
	if ( 'link' === $v['source'] && '' === spokares_clean_url( $v['url'] ) ) {
		$problems['url'] = __( 'The web address must start with https://', 'spokares-core' );
	}
	if ( '' !== $v['howto_url'] && '' === spokares_clean_url( $v['howto_url'] ) ) {
		$problems['howto_url'] = __( 'The “How to” web address must start with https://', 'spokares-core' );
	}
	foreach ( $v['sublinks'] as $s ) {
		if ( '' === spokares_clean_url( $s['url'] ) || '' === $s['label'] ) {
			$problems['sublinks'] = __( 'Each extra link needs words and a web address that starts with https://', 'spokares-core' );
		}
	}
	$texts = array(
		'title'     => $v['title'],
		'note'      => $v['note'],
		'howto_lbl' => $v['howto_lbl'],
		'label'     => $v['label'],
		'sublinks'  => implode( ' ', array_merge( wp_list_pluck( $v['sublinks'], 'label' ), wp_list_pluck( $v['sublinks'], 'title' ) ) ),
	);
	foreach ( $texts as $field => $text ) {
		$check = spokares_check_field( $text, $field, $confirmed, in_array( $field, $v['confirm'], true ) );
		if ( $check['block'] ) {
			$problems[ $field ] = sprintf(
				/* translators: 1: field, 2: what was found. */
				__( '%1$s mentions %2$s.', 'spokares-core' ),
				ucfirst( $names[ $field ] ),
				spokares_and_list( $check['block'] )
			);
		} elseif ( $check['confirm'] ) {
			$confirm[ $field ] = $check['confirm'];
		}
	}
	return array(
		'problems' => $problems,
		'confirm'  => $confirm,
	);
}

/**
 * Before the document is written: decide Draft vs published, check the file.
 *
 * @param array $data    Slashed post data.
 * @param array $postarr Raw post array.
 */
function spokares_document_insert_data( $data, $postarr ) {
	if ( 'spk_document' !== ( $data['post_type'] ?? '' ) || empty( $postarr['ID'] ) ) {
		return $data;
	}
	$post_id = (int) $postarr['ID'];
	$stored  = get_post( $post_id );

	// Link names (slugs) are anchors and stable links: read-only for
	// non-admins once published.
	if ( $stored && 'publish' === $stored->post_status && ! current_user_can( 'manage_options' ) ) {
		$data['post_name'] = wp_slash( $stored->post_name );
	} elseif ( '' !== (string) ( $data['post_name'] ?? '' ) && in_array( $data['post_name'], spokares_document_reserved_slugs(), true ) ) {
		// A document called "Forms" would share its row id with the Forms
		// section (and "search" with the search box) on Documents & forms.
		$data['post_name'] = wp_unique_post_slug( $data['post_name'] . '-document', $post_id, $data['post_status'], 'spk_document', 0 );
	}
	if ( ! spokares_document_form_ok( $post_id ) ) {
		return $data;
	}
	$v = spokares_document_submitted();
	// A new document goes to the end of its section unless a position was typed.
	if ( $stored && 'auto-draft' === $stored->post_status && 0 === (int) ( $data['menu_order'] ?? 0 ) && '' !== $v['section'] ) {
		$data['menu_order'] = spokares_document_next_position( $v['section'] );
	}
	$file   = spokares_document_upload();
	$upload = '';
	if ( $file && 'upload' === $v['source'] ) {
		$problem = spokares_document_upload_problem( $file, $v['privacy'] );
		$upload  = '' === $problem ? 'ok' : $problem;
	}
	$confirmed        = get_post_meta( $post_id, 'spk_confirmed', true );
	$result           = spokares_document_problems( $v, $post_id, is_array( $confirmed ) ? $confirmed : array(), $upload );
	$result['upload'] = $upload;

	if ( ( $result['problems'] || $result['confirm'] ) && in_array( $data['post_status'], array( 'publish', 'future', 'pending' ), true ) ) {
		$data['post_status'] = 'draft';
		$result['demoted']   = true;
	}
	$GLOBALS['spokares_document_check'] = $result;
	return $data;
}
add_filter( 'wp_insert_post_data', 'spokares_document_insert_data', 10, 2 );

/**
 * Slugs a document may not take: the ids of the library's sections and of
 * the other things on Documents & forms.
 */
function spokares_document_reserved_slugs(): array {
	return array_merge( array_keys( spokares_doc_sections() ), array_keys( spokares_library_sections() ), array( 'main', 'search', 'not-here', 'most-used', 'top' ) );
}

/**
 * The position after the last document in a section (10, 20, 30 …).
 *
 * @param string $section Section slug.
 */
function spokares_document_next_position( string $section ): int {
	$last = get_posts(
		array(
			'post_type'        => 'spk_document',
			'post_status'      => array( 'publish', 'draft', 'pending' ),
			'numberposts'      => 1,
			'orderby'          => 'menu_order',
			'order'            => 'DESC',
			'suppress_filters' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one small library, on a document's first save only.
			'tax_query'        => array(
				array(
					'taxonomy' => 'spk_doc_cat',
					'field'    => 'slug',
					'terms'    => $section,
				),
			),
		)
	);
	return $last ? (int) $last[0]->menu_order + 10 : 10;
}

/**
 * The upload allowlist for one call (documents only).
 *
 * @return array
 */
function spokares_doc_mimes_filter(): array {
	return spokares_doc_mimes();
}

/**
 * Upload the chosen file for a document. Returns the attachment ID or an error.
 *
 * @param int   $post_id Document.
 * @param array $file    spokares_document_upload().
 * @return int|WP_Error
 */
function spokares_document_do_upload( int $post_id, array $file ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	$post = get_post( $post_id );
	$base = $post && '' !== $post->post_name ? $post->post_name : sanitize_title( pathinfo( $file['name'], PATHINFO_FILENAME ) );
	$base = '' !== $base ? $base : 'document';
	// A random suffix, so a file saved with a draft isn't at a guessable address.
	$_FILES['spk_upload']['name'] = substr( $base, 0, 60 ) . '-' . bin2hex( random_bytes( 4 ) ) . '.' . $ext;

	add_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	$direct = ! defined( 'SPOKARES_HARDENING_VERSION' );
	if ( $direct ) {
		add_filter( 'upload_mimes', 'spokares_doc_mimes_filter', PHP_INT_MAX );
	}
	$att = media_handle_upload( 'spk_upload', $post_id, array(), array( 'test_form' => false ) );
	remove_filter( 'spokares_hard_upload_mimes', 'spokares_doc_mimes_filter', 99 );
	if ( $direct ) {
		remove_filter( 'upload_mimes', 'spokares_doc_mimes_filter', PHP_INT_MAX );
	}
	return $att;
}

/**
 * Save the document's fields and, when allowed, its file.
 *
 * @param int $post_id Post.
 */
function spokares_save_document( $post_id ): void {
	static $busy = false;
	$post_id     = (int) $post_id;
	if ( $busy || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! spokares_document_form_ok( $post_id ) ) {
		return;
	}
	$busy      = true;
	$v         = spokares_document_submitted();
	$admin     = current_user_can( 'manage_options' );
	$confirmed = get_post_meta( $post_id, 'spk_confirmed', true );
	$confirmed = is_array( $confirmed ) ? $confirmed : array();
	$result    = $GLOBALS['spokares_document_check'] ?? array(
		'problems' => array(),
		'confirm'  => array(),
		'upload'   => '',
	);

	// The section (one term).
	$tax = get_taxonomy( 'spk_doc_cat' );
	if ( $tax && array_key_exists( $v['section'], spokares_library_sections() ) && current_user_can( $tax->cap->assign_terms ) ) {
		wp_set_object_terms( $post_id, $v['section'], 'spk_doc_cat', false );
	}

	foreach ( array( 'title', 'note', 'howto_lbl', 'label', 'sublinks' ) as $field ) {
		if ( in_array( $field, $v['confirm'], true ) ) {
			$text = 'sublinks' === $field
				? implode( ' ', array_merge( wp_list_pluck( $v['sublinks'], 'label' ), wp_list_pluck( $v['sublinks'], 'title' ) ) )
				: (string) $v[ $field ];
			if ( '' !== $text ) {
				$confirmed[ $field ] = sha1( $text );
			}
		}
	}

	$url   = spokares_clean_url( $v['url'] );
	$label = $v['label'];
	if ( 'link' === $v['source'] && '' === $label && '' !== $url ) {
		$label = spokares_host_name( $url ); // "filled in from the web address".
	}
	$meta = array(
		'spk_source'       => '' !== $v['source'] ? $v['source'] : 'soon',
		'spk_url'          => '' !== $url ? $url : $v['url'],
		'spk_source_label' => $label,
		'spk_format'       => $v['format'],
		'spk_version'      => mb_substr( $v['version'], 0, 30, 'UTF-8' ),
		'spk_note'         => $v['note'],
		'spk_howto_label'  => $v['howto_lbl'],
		'spk_howto_url'    => $v['howto_url'],
		'spk_most_used'    => $v['most_used'] ? '1' : '',
		'spk_owner'        => $v['owner'],
		// Publishing with the Privacy check ticked is a review: an empty "Last
		// reviewed" becomes today.
		'spk_reviewed'     => spokares_is_ymd( $v['reviewed'] ) ? $v['reviewed'] : ( $v['privacy'] && 'publish' === get_post_status( $post_id ) ? spokares_today() : '' ),
		'spk_privacy_ok'   => $v['privacy'] ? '1' : '',
	);
	if ( $admin ) {
		$meta['spk_needs_check'] = $v['check'] ? '1' : '';
		$meta['spk_keywords']    = $v['keywords'];
		$meta['spk_sublinks']    = $v['sublinks'];
	}
	foreach ( $meta as $key => $value ) {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
	if ( $confirmed ) {
		update_post_meta( $post_id, 'spk_confirmed', $confirmed );
	}

	// The file: only with the Privacy check, only now, only the allowed types.
	$file     = spokares_document_upload();
	$messages = array();
	if ( $file && 'upload' === $v['source'] && 'ok' === ( $result['upload'] ?? '' ) ) {
		$old = absint( get_post_meta( $post_id, 'spk_file', true ) );
		$att = spokares_document_do_upload( $post_id, $file );
		if ( is_wp_error( $att ) ) {
			$messages[] = __( 'File not uploaded:', 'spokares-core' ) . ' ' . $att->get_error_message();
			if ( ! $old && 'publish' === get_post_status( $post_id ) ) {
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'draft',
					)
				);
				$result['demoted'] = true;
			}
		} else {
			update_post_meta( $post_id, 'spk_file', (int) $att );
			$ext = strtolower( pathinfo( (string) get_attached_file( (int) $att ), PATHINFO_EXTENSION ) );
			update_post_meta( $post_id, 'spk_format', spokares_format_for_ext( $ext ) );
			if ( $old && $old !== (int) $att && $v['remove_old'] ) {
				$note = spokares_remove_document_file( $old, $post_id );
				if ( '' !== $note ) {
					$messages[] = $note;
				}
			}
		}
	} elseif ( $file && 'upload' === $v['source'] && '' !== ( $result['upload'] ?? '' ) && 'ok' !== $result['upload'] ) {
		$messages[] = $result['upload'];
	}

	$names     = spokares_document_field_names();
	$sentences = array_values( array_filter( $result['problems'], static fn( $k ) => 'upload' !== $k || empty( $file ), ARRAY_FILTER_USE_KEY ) );
	foreach ( $result['confirm'] as $field => $hits ) {
		$sentences[] = sprintf(
			/* translators: 1: field, 2: what was found. */
			__( '%1$s has %2$s. If it is public, tick the box beside it and publish again.', 'spokares-core' ),
			ucfirst( $names[ $field ] ?? $field ),
			spokares_and_list( wp_list_pluck( $hits, 'what' ) )
		);
	}
	foreach ( $messages as $m ) {
		spokares_add_notice( 'error', $m );
	}
	if ( $sentences ) {
		$lead = ! empty( $result['demoted'] )
			? __( 'Saved as a draft, so it’s off the site:', 'spokares-core' )
			: __( 'Saved. Before you publish:', 'spokares-core' );
		spokares_add_notice( 'error', $lead . ' ' . implode( ' ', $sentences ) );
	}
	unset( $GLOBALS['spokares_document_check'] );
	$busy = false;
}
add_action( 'save_post_spk_document', 'spokares_save_document' );

/**
 * The form needs multipart for the file chooser.
 *
 * @param WP_Post $post Post.
 */
function spokares_document_form_tag( $post ): void {
	if ( $post instanceof WP_Post && 'spk_document' === $post->post_type ) {
		echo ' enctype="multipart/form-data"';
	}
}
add_action( 'post_edit_form_tag', 'spokares_document_form_tag' );

/**
 * Trash with "Also remove its file from the web" ticked: remove the file.
 * WordPress has already checked the trash nonce and the capability.
 *
 * @param int $post_id Post.
 */
function spokares_document_trashed( $post_id ): void {
	$post_id = (int) $post_id;
	if ( 'spk_document' !== get_post_type( $post_id ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- verified just below with WordPress's own trash nonce.
	if ( empty( $_GET['spk_remove_file'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'trash-post_' . $post_id ) || ! current_user_can( 'delete_post', $post_id ) ) {
		return;
	}
	// phpcs:enable
	$att = absint( get_post_meta( $post_id, 'spk_file', true ) );
	if ( $att ) {
		$note = spokares_remove_document_file( $att, $post_id );
		if ( '' !== $note ) {
			spokares_add_notice( 'warning', $note );
		}
	}
}
add_action( 'trashed_post', 'spokares_document_trashed' );

/**
 * Pull this file now (administrators): delete the file at once, set the
 * document to "Soon" and Draft, and record who and when.
 */
function spokares_handle_pull_file(): void {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'spokares_pull_file_' . $id );
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) || 'spk_document' !== get_post_type( $id ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to pull this file.', 'spokares-core' ), '', array( 'response' => 403 ) );
	}
	$att = absint( get_post_meta( $id, 'spk_file', true ) );
	delete_post_meta( $id, 'spk_file' );
	update_post_meta( $id, 'spk_source', 'soon' );
	update_post_meta( $id, 'spk_pulled', spokares_stamp() );
	wp_update_post(
		array(
			'ID'          => $id,
			'post_status' => 'draft',
		)
	);
	if ( $att && 'attachment' === get_post_type( $att ) ) {
		$GLOBALS['spokares_delete_ok'][ $att ] = true;
		wp_delete_attachment( $att, true );
	}
	spokares_purge_cache();
	spokares_add_notice( 'success', __( 'The file is off the web and the document is a draft marked “Soon”.', 'spokares-core' ) );
	wp_safe_redirect( admin_url( 'edit.php?post_type=spk_document' ) );
	exit;
}
add_action( 'admin_post_spokares_pull_file', 'spokares_handle_pull_file' );

/* -------------------------------------------------------------------- form */

/**
 * The stored values in the form's shape.
 *
 * @param WP_Post $post Document.
 */
function spokares_document_form_values( WP_Post $post ): array {
	$get   = static fn( $k ) => get_post_meta( $post->ID, $k, true );
	$terms = get_the_terms( $post, 'spk_doc_cat' );
	$subs  = $get( 'spk_sublinks' );
	return array(
		'title'      => $post->post_title,
		'section'    => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
		'source'     => (string) $get( 'spk_source' ),
		'privacy'    => '1' === (string) $get( 'spk_privacy_ok' ),
		'url'        => (string) $get( 'spk_url' ),
		'version'    => (string) $get( 'spk_version' ),
		'note'       => (string) $get( 'spk_note' ),
		'label'      => (string) $get( 'spk_source_label' ),
		'format'     => (string) $get( 'spk_format' ),
		'howto_lbl'  => (string) $get( 'spk_howto_label' ),
		'howto_url'  => (string) $get( 'spk_howto_url' ),
		'most_used'  => '1' === (string) $get( 'spk_most_used' ),
		'owner'      => (string) $get( 'spk_owner' ),
		'reviewed'   => (string) $get( 'spk_reviewed' ),
		'check'      => '1' === (string) $get( 'spk_needs_check' ),
		'keywords'   => (string) $get( 'spk_keywords' ),
		'sublinks'   => is_array( $subs ) ? $subs : array(),
		'remove_old' => true,
		'confirm'    => array(),
	);
}

/**
 * The document form (after the name).
 *
 * @param WP_Post $post Post.
 */
function spokares_document_form_fields( $post ): void {
	if ( ! $post instanceof WP_Post || 'spk_document' !== $post->post_type ) {
		return;
	}
	$v         = spokares_document_form_values( $post );
	$confirmed = get_post_meta( $post->ID, 'spk_confirmed', true );
	$new       = 'auto-draft' === $post->post_status;
	$check     = $new ? array(
		'problems' => array(),
		'confirm'  => array(),
	) : spokares_document_problems( $v, $post->ID, is_array( $confirmed ) ? $confirmed : array(), '' );
	$p         = $check['problems'];
	$c         = $check['confirm'];
	$admin     = current_user_can( 'manage_options' );
	$file      = absint( get_post_meta( $post->ID, 'spk_file', true ) );
	$file_url  = $file ? (string) wp_get_attachment_url( $file ) : '';
	$addr      = __( 'Web address (copy it from your browser’s address bar)', 'spokares-core' );
	$source    = '' !== $v['source'] ? $v['source'] : 'upload';
	$pulled    = get_post_meta( $post->ID, 'spk_pulled', true );
	$tile      = 0;
	$tiles     = spokares_opt( 'spk_tiles' );
	for ( $i = 0; $i < 4; $i++ ) {
		if ( $tiles[ $i ]['doc'] === $post->ID ) {
			$tile = $i + 1;
		}
	}
	wp_nonce_field( 'spokares_document_meta', 'spokares_document_nonce' );
	?>
	<div class="spk-doc-form">
		<?php spokares_err_text( $p, 'title' ); ?>
		<?php spokares_event_confirm( $c, 'title' ); ?>
		<?php if ( is_array( $pulled ) && ! empty( $pulled['at'] ) ) : ?>
			<p class="spk-banner spk-banner--amber">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: a person's name, 2: date and time. */
						__( 'The file was pulled from the web by %1$s on %2$s.', 'spokares-core' ),
						spokares_user_name( (int) ( $pulled['by'] ?? 0 ) ),
						wp_date( 'D, M j, Y, g:i A', (int) strtotime( (string) $pulled['at'] ) )
					)
				);
				?>
			</p>
		<?php endif; ?>

		<h2 class="spk-section-title"><?php esc_html_e( 'Basics', 'spokares-core' ); ?></h2>

		<fieldset class="spk-card<?php echo isset( $p['section'] ) ? ' spk-field-error' : ''; ?>">
			<legend><?php esc_html_e( 'Library section (required; pick one)', 'spokares-core' ); ?></legend>
			<?php foreach ( spokares_library_sections() as $slug => $label ) : ?>
				<label class="spk-choice"><input type="radio" name="spk_section" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, $v['section'] ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
			<?php spokares_err_text( $p, 'section' ); ?>
		</fieldset>

		<fieldset class="spk-card spk-source">
			<legend><?php esc_html_e( 'Where the file is', 'spokares-core' ); ?></legend>
			<label class="spk-choice"><input type="radio" name="spk_source" value="upload" <?php checked( 'upload', $source ); ?>> <?php esc_html_e( 'Upload a file', 'spokares-core' ); ?></label>
			<label class="spk-choice"><input type="radio" name="spk_source" value="link" <?php checked( 'link', $source ); ?>> <?php esc_html_e( 'Link to another site', 'spokares-core' ); ?></label>
			<label class="spk-choice"><input type="radio" name="spk_source" value="soon" <?php checked( 'soon', $source ); ?>> <?php esc_html_e( 'Not available yet (“Soon”)', 'spokares-core' ); ?></label>

			<p class="spk-privacy<?php echo isset( $p['privacy'] ) ? ' spk-field-error' : ''; ?>">
				<label><input type="checkbox" name="spk_privacy_ok" value="1" id="spk-privacy" <?php checked( $v['privacy'] ); ?>>
				<strong><?php esc_html_e( 'Privacy check:', 'spokares-core' ); ?></strong>
				<?php esc_html_e( 'no personal phones, home addresses, personal e-mails, member lists or rosters, and no county, hospital, SHARES or 800 MHz details.', 'spokares-core' ); ?></label>
				<span class="description"><?php esc_html_e( '(required to publish; tick it to unlock the file chooser)', 'spokares-core' ); ?></span>
			</p>

			<div class="spk-upload" data-source="upload">
				<?php if ( $file && '' !== $file_url ) : ?>
					<p><?php esc_html_e( 'Current file:', 'spokares-core' ); ?>
						<strong><?php echo esc_html( wp_basename( $file_url ) ); ?></strong>
						<?php
						$bytes = (int) @filesize( (string) get_attached_file( $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file just shows no size.
						if ( $bytes ) {
							echo ' · ' . esc_html( size_format( $bytes ) );
						}
						?>
						· <a href="<?php echo esc_url( $file_url ); ?>"><?php esc_html_e( 'Open', 'spokares-core' ); ?></a></p>
					<p><label for="spk-upload"><?php esc_html_e( 'Replace with', 'spokares-core' ); ?></label>
						<input type="file" id="spk-upload" name="spk_upload" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" <?php disabled( ! $v['privacy'] ); ?>>
						<label><input type="checkbox" name="spk_remove_old" value="1" checked> <?php esc_html_e( 'Remove the old file from the web (recommended)', 'spokares-core' ); ?></label></p>
				<?php else : ?>
					<p><label for="spk-upload"><?php esc_html_e( 'File', 'spokares-core' ); ?></label>
						<input type="file" id="spk-upload" name="spk_upload" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" <?php disabled( ! $v['privacy'] ); ?> class="<?php echo esc_attr( trim( spokares_err_class( $p, 'upload' ) ) ); ?>">
						<span class="description"><?php esc_html_e( '(it uploads when you click Save draft or Publish)', 'spokares-core' ); ?></span></p>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'PDF, DOCX, XLSX, JPEG or PNG.', 'spokares-core' ); ?></p>
				<?php spokares_err_text( $p, 'upload' ); ?>
			</div>

			<div class="spk-link" data-source="link">
				<p><label for="spk-url"><?php echo esc_html( $addr ); ?></label><br>
					<input type="url" id="spk-url" name="spk_url" class="large-text<?php echo esc_attr( spokares_err_class( $p, 'url' ) ); ?>" value="<?php echo esc_attr( $v['url'] ); ?>" placeholder="https://"></p>
				<?php spokares_err_text( $p, 'url' ); ?>
			</div>
		</fieldset>

		<div class="spk-field">
			<label for="spk-version"><?php esc_html_e( 'Version or date', 'spokares-core' ); ?></label>
			<input type="text" id="spk-version" name="spk_version" class="regular-text" maxlength="30" value="<?php echo esc_attr( $v['version'] ); ?>">
		</div>
		<div class="spk-field">
			<label for="spk-note"><?php esc_html_e( 'Short note (8 words or fewer)', 'spokares-core' ); ?></label>
			<input type="text" id="spk-note" name="spk_note" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'note' ) ); ?>" maxlength="60" value="<?php echo esc_attr( $v['note'] ); ?>">
			<?php spokares_err_text( $p, 'note' ); ?>
			<?php spokares_event_confirm( $c, 'note' ); ?>
		</div>

		<details class="spk-more"<?php echo ( isset( $p['howto_url'] ) || isset( $p['howto_lbl'] ) || isset( $c['howto_lbl'] ) || isset( $p['label'] ) ) ? ' open' : ''; ?>>
			<summary><?php esc_html_e( 'More options', 'spokares-core' ); ?></summary>
			<div class="spk-field">
				<label for="spk-label"><?php esc_html_e( 'Source shown on the page', 'spokares-core' ); ?></label>
				<input type="text" id="spk-label" name="spk_source_label" class="regular-text<?php echo esc_attr( spokares_err_class( $p + $c, 'label' ) ); ?>" maxlength="40" value="<?php echo esc_attr( $v['label'] ); ?>">
				<span class="description"><?php esc_html_e( '(filled in from the web address)', 'spokares-core' ); ?></span>
				<?php spokares_err_text( $p, 'label' ); ?>
				<?php spokares_event_confirm( $c, 'label' ); ?>
			</div>
			<div class="spk-field">
				<label for="spk-format"><?php esc_html_e( 'Format', 'spokares-core' ); ?></label>
				<select id="spk-format" name="spk_format">
					<option value=""><?php esc_html_e( '— none —', 'spokares-core' ); ?></option>
					<?php
					$formats = spokares_doc_formats();
					if ( '' !== $v['format'] && ! in_array( $v['format'], $formats, true ) ) {
						array_unshift( $formats, $v['format'] );
					}
					foreach ( $formats as $f ) :
						?>
						<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $f, $v['format'] ); ?>><?php echo esc_html( $f ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="description"><?php esc_html_e( '(automatic for uploads)', 'spokares-core' ); ?></span>
			</div>
			<fieldset class="spk-field">
				<legend><?php esc_html_e( '“How to” link', 'spokares-core' ); ?></legend>
				<label><?php esc_html_e( 'Words', 'spokares-core' ); ?> <input type="text" name="spk_howto_label" class="regular-text<?php echo esc_attr( spokares_err_class( $p + $c, 'howto_lbl' ) ); ?>" maxlength="60" value="<?php echo esc_attr( $v['howto_lbl'] ); ?>"></label>
				<label><?php echo esc_html( $addr ); ?> <input type="url" name="spk_howto_url" class="regular-text<?php echo esc_attr( spokares_err_class( $p, 'howto_url' ) ); ?>" value="<?php echo esc_attr( $v['howto_url'] ); ?>" placeholder="https://"></label>
				<?php spokares_err_text( $p, 'howto_lbl' ); ?>
				<?php spokares_err_text( $p, 'howto_url' ); ?>
				<?php spokares_event_confirm( $c, 'howto_lbl' ); ?>
			</fieldset>
			<div class="spk-field">
				<label><input type="checkbox" name="spk_most_used" value="1" <?php checked( $v['most_used'] ); ?>> <?php esc_html_e( 'Show under “Most used”', 'spokares-core' ); ?></label>
			</div>
			<div class="spk-field">
				<label for="spk-order"><?php esc_html_e( 'Position in section (lower shows first)', 'spokares-core' ); ?></label>
				<input type="number" id="spk-order" name="menu_order" class="small-text" min="0" step="10" value="<?php echo $new && 0 === (int) $post->menu_order ? '' : esc_attr( (string) $post->menu_order ); ?>" placeholder="<?php esc_attr_e( 'last', 'spokares-core' ); ?>">
				<?php if ( $new ) : ?>
					<span class="description"><?php esc_html_e( '(empty: at the end of its section)', 'spokares-core' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="spk-field">
				<label for="spk-owner"><?php esc_html_e( 'Who keeps it current', 'spokares-core' ); ?></label>
				<input type="text" id="spk-owner" name="spk_owner" class="regular-text" maxlength="60" value="<?php echo esc_attr( $v['owner'] ); ?>">
				<label for="spk-reviewed"><?php esc_html_e( 'Last reviewed', 'spokares-core' ); ?></label>
				<input type="date" id="spk-reviewed" name="spk_reviewed" value="<?php echo esc_attr( $v['reviewed'] ); ?>">
				<button type="button" class="button spk-reviewed-today" data-target="spk-reviewed"><?php esc_html_e( 'Mark reviewed today', 'spokares-core' ); ?></button>
			</div>
			<p class="description">
				<?php if ( $tile ) : ?>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: slot number, 2: tile words. */
							__( 'Hub tile: slot %1$d, “%2$s”.', 'spokares-core' ),
							$tile,
							$tiles[ $tile - 1 ]['label']
						)
					);
					?>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-tiles' ) ); ?>"><?php esc_html_e( 'Change tiles on Documents › Hub tiles.', 'spokares-core' ); ?></a>
			</p>
		</details>

		<?php if ( $admin ) : ?>
			<details class="spk-more spk-admin-only"<?php echo isset( $p['sublinks'] ) ? ' open' : ''; ?>>
				<summary><?php esc_html_e( 'Admin only', 'spokares-core' ); ?></summary>
				<fieldset class="spk-field spk-sublinks">
					<legend><?php esc_html_e( 'Extra links (for example FEMA course codes)', 'spokares-core' ); ?></legend>
					<?php
					$subs = array_values( $v['sublinks'] );
					for ( $i = 0; $i < 6; $i++ ) :
						$s = $subs[ $i ] ?? array(
							'id'    => '',
							'label' => '',
							'title' => '',
							'url'   => '',
						);
						?>
						<p class="spk-sub-row" <?php echo ( $i > 0 && '' === ( $s['label'] ?? '' ) && '' === ( $s['url'] ?? '' ) ) ? 'data-spk-spare hidden' : ''; ?>>
							<input type="hidden" name="spk_sublinks[<?php echo esc_attr( (string) $i ); ?>][id]" value="<?php echo esc_attr( (string) ( $s['id'] ?? '' ) ); ?>">
							<label><?php esc_html_e( 'Words', 'spokares-core' ); ?> <input type="text" name="spk_sublinks[<?php echo esc_attr( (string) $i ); ?>][label]" class="small-text spk-wide" value="<?php echo esc_attr( (string) ( $s['label'] ?? '' ) ); ?>"></label>
							<label><?php esc_html_e( 'Full title', 'spokares-core' ); ?> <input type="text" name="spk_sublinks[<?php echo esc_attr( (string) $i ); ?>][title]" class="regular-text" value="<?php echo esc_attr( (string) ( $s['title'] ?? '' ) ); ?>"></label>
							<label><?php esc_html_e( 'Web address', 'spokares-core' ); ?> <input type="url" name="spk_sublinks[<?php echo esc_attr( (string) $i ); ?>][url]" class="regular-text" value="<?php echo esc_attr( (string) ( $s['url'] ?? '' ) ); ?>"></label>
						</p>
					<?php endfor; ?>
					<p><button type="button" class="button spk-add-sub"><?php esc_html_e( '+ Add', 'spokares-core' ); ?></button></p>
					<?php spokares_err_text( $p, 'sublinks' ); ?>
					<?php spokares_event_confirm( $c, 'sublinks' ); ?>
				</fieldset>
				<div class="spk-field">
					<label for="spk-keywords"><?php esc_html_e( 'Search words', 'spokares-core' ); ?></label>
					<input type="text" id="spk-keywords" name="spk_keywords" class="large-text" value="<?php echo esc_attr( $v['keywords'] ); ?>">
				</div>
				<div class="spk-field">
					<label for="spk-slug"><?php esc_html_e( 'Link name (slug)', 'spokares-core' ); ?></label>
					<input type="text" id="spk-slug" name="post_name" class="regular-text" value="<?php echo esc_attr( $post->post_name ); ?>" pattern="[a-z0-9-]*">
					<p class="description"><?php esc_html_e( 'The row anchor and the stable link /docs/<slug>/. Changing it breaks old links.', 'spokares-core' ); ?></p>
				</div>
			</details>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'edit_form_after_title', 'spokares_document_form_fields' );

/**
 * Side boxes: Save (with the file option on Trash) and Checking.
 */
function spokares_document_boxes(): void {
	add_meta_box(
		'spokares_savebox',
		__( 'Save', 'spokares-core' ),
		static function ( $post ) {
			$note = '<p class="spk-trash-file"><label><input type="checkbox" id="spk-trash-file" checked> ' . esc_html__( 'Also remove its file from the web', 'spokares-core' ) . '</label></p>';
			spokares_render_save_box( $post, $note );
		},
		'spk_document',
		'side',
		'high'
	);
	add_meta_box( 'spokares_checking', __( 'Webmaster check', 'spokares-core' ), 'spokares_render_checking_box', 'spk_document', 'side', 'default' );
}
add_action( 'add_meta_boxes_spk_document', 'spokares_document_boxes' );

/**
 * The Trash link on documents removes the file unless the box is unticked
 * (the admin-forms script drops the parameter when it is).
 *
 * @param string $link    Delete link.
 * @param int    $post_id Post.
 */
function spokares_document_trash_link( $link, $post_id ) {
	if ( 'spk_document' === get_post_type( (int) $post_id ) && str_contains( (string) $link, 'action=trash' ) ) {
		return add_query_arg( 'spk_remove_file', '1', $link );
	}
	return $link;
}
add_filter( 'get_delete_post_link', 'spokares_document_trash_link', 10, 2 );

/* -------------------------------------------------------------- list table */

/**
 * Columns: Document, Section, Source, Format, Version, Most used, Tile slot,
 * Reviewed, Needs checking.
 *
 * @param array $cols Columns.
 */
function spokares_document_columns( $cols ): array {
	return array(
		'cb'                   => $cols['cb'] ?? '<input type="checkbox">',
		'title'                => __( 'Document', 'spokares-core' ),
		'taxonomy-spk_doc_cat' => __( 'Section', 'spokares-core' ),
		'spk_source'           => __( 'Source', 'spokares-core' ),
		'spk_format'           => __( 'Format', 'spokares-core' ),
		'spk_version'          => __( 'Version', 'spokares-core' ),
		'spk_most_used'        => __( 'Most used', 'spokares-core' ),
		'spk_tile'             => __( 'Tile slot', 'spokares-core' ),
		'spk_reviewed'         => __( 'Reviewed', 'spokares-core' ),
		'spk_check'            => __( 'Needs checking', 'spokares-core' ),
	);
}
add_filter( 'manage_spk_document_posts_columns', 'spokares_document_columns' );

/**
 * Column values.
 *
 * @param string $col     Column.
 * @param int    $post_id Post.
 */
function spokares_document_column( $col, $post_id ): void {
	$post_id = (int) $post_id;
	$get     = static fn( $k ) => (string) get_post_meta( $post_id, $k, true );
	switch ( $col ) {
		case 'spk_source':
			$src = $get( 'spk_source' );
			if ( 'upload' === $src ) {
				echo absint( $get( 'spk_file' ) ) ? esc_html__( 'File', 'spokares-core' ) : esc_html__( 'File (none yet)', 'spokares-core' );
			} elseif ( 'link' === $src ) {
				esc_html_e( 'Link', 'spokares-core' );
			} else {
				esc_html_e( 'Soon', 'spokares-core' );
			}
			if ( '1' !== $get( 'spk_privacy_ok' ) ) {
				echo '<br><span class="spk-flag">' . esc_html__( 'Privacy not checked', 'spokares-core' ) . '</span>';
			}
			break;
		case 'spk_format':
			echo esc_html( $get( 'spk_format' ) );
			break;
		case 'spk_version':
			echo esc_html( $get( 'spk_version' ) );
			break;
		case 'spk_most_used':
			echo '1' === $get( 'spk_most_used' ) ? esc_html__( 'Yes', 'spokares-core' ) : '';
			break;
		case 'spk_tile':
			$tiles = spokares_opt( 'spk_tiles' );
			for ( $i = 0; $i < 4; $i++ ) {
				if ( $tiles[ $i ]['doc'] === $post_id ) {
					echo esc_html( (string) ( $i + 1 ) );
				}
			}
			break;
		case 'spk_reviewed':
			$d   = $get( 'spk_reviewed' );
			$old = ! spokares_is_ymd( $d ) || $d < spokares_add_days( spokares_today(), -365 );
			echo '<span class="' . ( $old ? 'spk-flag' : '' ) . '">' . esc_html( spokares_is_ymd( $d ) ? spokares_fmt_date( $d, 'mdy' ) : __( 'Not yet', 'spokares-core' ) ) . '</span>';
			break;
		case 'spk_check':
			echo '1' === $get( 'spk_needs_check' ) ? '<span class="spk-flag">' . esc_html__( 'Yes', 'spokares-core' ) . '</span>' : '';
			break;
	}
}
add_action( 'manage_spk_document_posts_custom_column', 'spokares_document_column', 10, 2 );

/**
 * Sortable: Document and Reviewed.
 *
 * @param array $cols Sortable columns.
 */
function spokares_document_sortable( $cols ): array {
	$cols['spk_reviewed'] = 'spk_reviewed';
	return $cols;
}
add_filter( 'manage_edit-spk_document_sortable_columns', 'spokares_document_sortable' );

/**
 * Section filter and the Reviewed sort.
 *
 * @param WP_Query $q Query.
 */
function spokares_document_list_query( $q ): void {
	if ( ! is_admin() || ! $q->is_main_query() || 'spk_document' !== $q->get( 'post_type' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filtering only.
	$section = isset( $_GET['spk_section'] ) ? sanitize_key( wp_unslash( $_GET['spk_section'] ) ) : '';
	if ( '' !== $section && array_key_exists( $section, spokares_library_sections() ) ) {
		$q->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'spk_doc_cat',
					'field'    => 'slug',
					'terms'    => $section,
				),
			)
		);
	}
	if ( 'spk_reviewed' === $q->get( 'orderby' ) ) {
		$q->set( 'meta_key', 'spk_reviewed' );
		$q->set( 'orderby', 'meta_value' );
	} elseif ( ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', 'title' );
		$q->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'spokares_document_list_query' );

/**
 * The section dropdown above the list.
 *
 * @param string $post_type Post type.
 */
function spokares_document_filter_ui( $post_type ): void {
	if ( 'spk_document' !== $post_type ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filtering only.
	$current = isset( $_GET['spk_section'] ) ? sanitize_key( wp_unslash( $_GET['spk_section'] ) ) : '';
	echo '<label class="screen-reader-text" for="spk-section-filter">' . esc_html__( 'Filter by section', 'spokares-core' ) . '</label>';
	echo '<select name="spk_section" id="spk-section-filter"><option value="">' . esc_html__( 'All sections', 'spokares-core' ) . '</option>';
	foreach ( spokares_library_sections() as $slug => $label ) {
		echo '<option value="' . esc_attr( $slug ) . '"' . selected( $slug, $current, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'spokares_document_filter_ui' );

/**
 * Bulk actions: Mark reviewed today (no bulk Edit, no bulk Trash).
 *
 * @param array $actions Actions.
 */
function spokares_document_bulk_actions( $actions ): array {
	unset( $actions['edit'], $actions['trash'] );
	$actions['spk_mark_reviewed'] = __( 'Mark reviewed today', 'spokares-core' );
	return $actions;
}
add_filter( 'bulk_actions-edit-spk_document', 'spokares_document_bulk_actions', 20 );

/**
 * Run "Mark reviewed today" (WordPress checked the bulk-posts nonce).
 *
 * @param string $redirect Redirect URL.
 * @param string $action   Action.
 * @param int[]  $ids      Posts.
 */
function spokares_document_bulk_handle( $redirect, $action, $ids ) {
	if ( 'spk_mark_reviewed' !== $action ) {
		return $redirect;
	}
	check_admin_referer( 'bulk-posts' );
	$n = 0;
	foreach ( (array) $ids as $id ) {
		$id = (int) $id;
		if ( 'spk_document' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
			update_post_meta( $id, 'spk_reviewed', spokares_today() );
			++$n;
		}
	}
	/* translators: %d: number of documents. */
	spokares_add_notice( 'success', sprintf( _n( 'Marked %d document reviewed today.', 'Marked %d documents reviewed today.', $n, 'spokares-core' ), $n ) );
	return $redirect;
}
add_filter( 'handle_bulk_actions-edit-spk_document', 'spokares_document_bulk_handle', 10, 3 );

/**
 * Row actions: Edit · View on site · (admins) Pull this file now. No Trash
 * here: trash from the edit screen, where the file box is.
 *
 * @param array   $actions Actions.
 * @param WP_Post $post    Post.
 */
function spokares_document_row_actions( $actions, $post ) {
	if ( ! $post instanceof WP_Post || 'spk_document' !== $post->post_type || 'trash' === $post->post_status ) {
		return $actions;
	}
	$out = array();
	if ( isset( $actions['edit'] ) ) {
		$out['edit'] = $actions['edit'];
	}
	if ( 'publish' === $post->post_status ) {
		$out['view-site'] = '<a href="' . esc_url( spokares_site_url( '/members/documents/', $post->post_name ) ) . '">' . esc_html__( 'View on site', 'spokares-core' ) . '</a>';
	}
	if ( current_user_can( 'manage_options' ) && absint( get_post_meta( $post->ID, 'spk_file', true ) ) ) {
		$url         = wp_nonce_url( admin_url( 'admin-post.php?action=spokares_pull_file&post=' . $post->ID ), 'spokares_pull_file_' . $post->ID );
		$out['pull'] = '<a class="spk-danger" href="' . esc_url( $url ) . '">' . esc_html__( 'Pull this file now', 'spokares-core' ) . '</a>';
	}
	return $out;
}
add_filter( 'post_row_actions', 'spokares_document_row_actions', 20, 2 );
