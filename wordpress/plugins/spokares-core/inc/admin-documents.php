<?php
/**
 * Documents (UX spec §3.6): Add a Document and Edit Document, their save, the
 * Documents list, and the library sections.
 *
 * A file is uploaded only when the form is saved, only with a fresh "I
 * checked this file" tick, only in the allowed types, with a random suffix on
 * its name. Replace, a switch away from "Upload a file" and "Take it off the
 * site" remove the old file from the web (an administrator may keep it on
 * Replace, and can pull a file at once). Nothing deletes a file a document or
 * page still uses, except those paths.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formats offered for a link (value = what the site prints).
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
		'source'     => in_array( $source, array( 'upload', 'link', 'soon' ), true ) ? $source : 'soon',
		'privacy'    => ! empty( $in['spk_privacy_ok'] ),
		'url'        => $str( 'spk_url' ),
		'version'    => $str( 'spk_version' ),
		'note'       => $str( 'spk_note' ),
		'label'      => $str( 'spk_source_label' ),
		'format'     => $str( 'spk_format' ),
		'howto_lbl'  => $str( 'spk_howto_label' ),
		'howto_url'  => $str( 'spk_howto_url' ),
		'most_used'  => ! empty( $in['spk_most_used'] ),
		'place'      => $str( 'spk_place' ),
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
 * The file chosen in this request, if any: [name, original, tmp_name, error, size].
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
		// The name as the editor knows it ("Net script v4.pdf"), for "Current file".
		'original' => mb_substr( sanitize_text_field( wp_basename( (string) ( $f['name'] ?? '' ) ) ), 0, 120, 'UTF-8' ),
		'tmp_name' => (string) ( $f['tmp_name'] ?? '' ),
		'error'    => $error,
		'size'     => (int) ( $f['size'] ?? 0 ),
	);
}

/**
 * Check a chosen file before anything is stored. Returns '' when it may be
 * uploaded, else the sentence under the file box that says what to do.
 *
 * @param array $file    spokares_document_upload().
 * @param bool  $privacy "I checked this file" ticked.
 */
function spokares_document_upload_problem( array $file, bool $privacy ): string {
	if ( ! $privacy ) {
		return __( 'Tick “I checked this file” first, then choose the file again.', 'spokares-core' );
	}
	if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return __( 'The file didn’t arrive (it may be too big). Choose it again, or ask the webmaster.', 'spokares-core' );
	}
	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	if ( 'doc' === $ext ) {
		return __( '.doc files aren’t accepted. In Word, use File › Save As › PDF, then choose it again.', 'spokares-core' );
	}
	if ( '' === spokares_format_for_ext( $ext ) ) {
		return __( 'Use a PDF, Word (.docx), Excel (.xlsx), JPEG or PNG file.', 'spokares-core' );
	}
	if ( in_array( $ext, array( 'docx', 'xlsx' ), true ) ) {
		if ( ! function_exists( 'spokares_hard_inspect_office_file' ) ) {
			return __( 'Word and Excel files can’t be checked here. Choose a PDF instead, or ask the webmaster.', 'spokares-core' );
		}
		$ok = spokares_hard_inspect_office_file( $file['tmp_name'] );
		if ( is_wp_error( $ok ) ) {
			return $ok->get_error_message();
		}
	}
	return '';
}

/**
 * What each field is called in a notice ("Saved, except the short note").
 */
function spokares_document_field_names(): array {
	return array(
		'title'     => __( 'the document name', 'spokares-core' ),
		'section'   => __( 'the section', 'spokares-core' ),
		'upload'    => __( 'the new file', 'spokares-core' ),
		'url'       => __( 'the web address', 'spokares-core' ),
		'note'      => __( 'the short note', 'spokares-core' ),
		'howto_lbl' => __( 'the “How to” words', 'spokares-core' ),
		'howto_url' => __( 'the “How to” web address', 'spokares-core' ),
		'label'     => __( 'the site name', 'spokares-core' ),
		'sublinks'  => __( 'the extra links', 'spokares-core' ),
		'owner'     => __( 'the owner', 'spokares-core' ),
	);
}

/**
 * The form's length limits (its maxlength attributes), enforced on save too.
 */
function spokares_document_max_lengths(): array {
	return array(
		'note'      => 60,
		'label'     => 40,
		'howto_lbl' => 60,
		'owner'     => 60,
	);
}

/**
 * The words of the privacy tick for a source ("I checked this file" …).
 *
 * @param string $which 'file', 'new-file' or 'link'.
 */
function spokares_document_privacy_words( string $which ): string {
	switch ( $which ) {
		case 'new-file':
			return __( 'I checked the new file: no personal phone numbers, home addresses, personal e-mails or member lists, and no county, hospital, SHARES or 800 MHz details.', 'spokares-core' );
		case 'link':
			return __( 'I checked the page it links to: no personal phone numbers, home addresses, personal e-mails or member lists, and no county, hospital, SHARES or 800 MHz details.', 'spokares-core' );
		default:
			return __( 'I checked this file: no personal phone numbers, home addresses, personal e-mails or member lists, and no county, hospital, SHARES or 800 MHz details.', 'spokares-core' );
	}
}

/**
 * The document as stored before this save: its source, web address,
 * privacy tick and file (0 when it has none on the web).
 *
 * @param int $post_id Document.
 * @return array{source:string,url:string,privacy:bool,file_id:int,file:int}
 */
function spokares_document_stored( int $post_id ): array {
	$source = (string) get_post_meta( $post_id, 'spk_source', true );
	$att    = absint( get_post_meta( $post_id, 'spk_file', true ) );
	return array(
		'source'  => in_array( $source, array( 'upload', 'link', 'soon' ), true ) ? $source : 'soon',
		'url'     => (string) get_post_meta( $post_id, 'spk_url', true ),
		'privacy' => '1' === (string) get_post_meta( $post_id, 'spk_privacy_ok', true ),
		'file_id' => $att,
		'file'    => $att && '' !== (string) wp_get_attachment_url( $att ) ? $att : 0,
	);
}

/**
 * Is this document "Soon" (not ready yet, and no course links)?
 *
 * @param int $post_id Document.
 */
function spokares_document_is_soon( int $post_id ): bool {
	$source = (string) get_post_meta( $post_id, 'spk_source', true );
	$subs   = get_post_meta( $post_id, 'spk_sublinks', true );
	return ! in_array( $source, array( 'upload', 'link' ), true ) && ! ( is_array( $subs ) && $subs );
}

/**
 * What stops these values from going live, one sentence per field.
 *
 * The privacy rule (§3.6): a fresh tick is needed whenever something new goes
 * public (a new document, a new file, a new or changed web address, a switch
 * from Soon or Link to a file). Name, note or version edits need no tick.
 *
 * @param array      $v         Values (submitted, or stored for the form).
 * @param int        $post_id   Document.
 * @param array      $confirmed Field => sha1 of text confirmed as public.
 * @param array|null $file      The file chosen in this request (Upload only).
 * @return array{problems:array,confirm:array,upload:string,new_address:bool}
 *   'upload' = '' (no file chosen), 'ok', or the refused file's sentence.
 */
function spokares_document_check( array $v, int $post_id, array $confirmed, ?array $file ): array {
	$stored   = spokares_document_stored( $post_id );
	$problems = array();
	$confirm  = array();
	$upload   = '';
	$new_addr = false;

	if ( '' === trim( $v['title'] ) ) {
		$problems['title'] = __( 'Type the document name.', 'spokares-core' );
	}
	if ( ! array_key_exists( $v['section'], spokares_library_sections() ) ) {
		$problems['section'] = __( 'Pick the section.', 'spokares-core' );
	}
	if ( 'upload' === $v['source'] ) {
		if ( $file ) {
			$why    = spokares_document_upload_problem( $file, $v['privacy'] );
			$upload = '' === $why ? 'ok' : $why;
			if ( '' !== $why ) {
				$problems['upload'] = $why;
			}
		} elseif ( ! $stored['file'] ) {
			$problems['upload'] = $v['privacy']
				? __( 'Choose the file.', 'spokares-core' )
				: __( 'Tick “I checked this file”, then choose the file.', 'spokares-core' );
		} elseif ( ! $stored['privacy'] && ! $v['privacy'] ) {
			$problems['privacy'] = __( 'Check the file, then tick this box.', 'spokares-core' );
		}
	} elseif ( 'link' === $v['source'] ) {
		$clean = spokares_clean_url( $v['url'] );
		if ( '' === $clean ) {
			$problems['url'] = __( 'Type a web address, like https://training.fema.gov/…', 'spokares-core' );
		}
		$new_addr = 'link' !== $stored['source'] || spokares_clean_url( $stored['url'] ) !== $clean || ! $stored['privacy'];
		if ( $new_addr && ! $v['privacy'] ) {
			$problems['privacy'] = __( 'Check the page, then tick this box.', 'spokares-core' );
		}
	}

	// The browser's maxlength, checked again here (a request can skip it).
	foreach ( spokares_document_max_lengths() as $field => $max ) {
		if ( spokares_too_long( (string) $v[ $field ], $max ) ) {
			/* translators: %d: number of characters. */
			$problems[ $field ] = sprintf( __( 'Keep it to %d characters.', 'spokares-core' ), $max );
		}
	}
	if ( '' !== $v['howto_url'] && '' === spokares_clean_url( $v['howto_url'] ) ) {
		$problems['howto_url'] = __( 'Type a web address, like https://training.fema.gov/…', 'spokares-core' );
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
		if ( isset( $problems[ $field ] ) ) {
			continue; // Already a problem (too long).
		}
		$check = spokares_check_field( $text, $field, $confirmed, in_array( $field, $v['confirm'], true ) );
		if ( $check['block'] || $check['confirm'] ) {
			$problems[ $field ] = spokares_problem_sentence( $check );
		}
		if ( ! $check['block'] && $check['confirm'] ) {
			$confirm[ $field ] = $check['confirm'];
		}
	}
	return array(
		'problems'    => $problems,
		'confirm'     => $confirm,
		'upload'      => $upload,
		'new_address' => $new_addr,
	);
}

/**
 * A published document with problems stays on the site: what can't go live
 * is held back (not saved), the rest is saved. A problem with the new file or
 * web address keeps the stored source (file or address) as it is. If the
 * stored source has nothing to show either, the document goes back to Draft.
 *
 * @param array $check   spokares_document_check() result.
 * @param int   $post_id Document.
 */
function spokares_document_hold( array $check, int $post_id ): array {
	foreach ( array_keys( $check['problems'] ) as $key ) {
		if ( in_array( $key, array( 'upload', 'url', 'privacy' ), true ) ) {
			$check['keep_source'] = true;
		} else {
			$check['held'][] = $key;
		}
	}
	if ( $check['keep_source'] ) {
		$stored = spokares_document_stored( $post_id );
		$shows  = 'soon' === $stored['source']
			|| ( 'upload' === $stored['source'] && $stored['file'] )
			|| ( 'link' === $stored['source'] && '' !== spokares_clean_url( $stored['url'] ) );
		if ( ! $shows ) {
			$check['demoted'] = true;
		}
	}
	return $check;
}

/**
 * Before the document is written: Publish, Draft, or keep it published and
 * hold back what can't go live.
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
	$v         = spokares_document_submitted();
	$file      = 'upload' === $v['source'] ? spokares_document_upload() : null;
	$confirmed = get_post_meta( $post_id, 'spk_confirmed', true );
	$check     = spokares_document_check( $v, $post_id, is_array( $confirmed ) ? $confirmed : array(), $file );
	$check    += array(
		'held'        => array(),
		'keep_source' => false,
		'demoted'     => false,
		'live'        => $stored && 'publish' === $stored->post_status,
	);
	$publish   = in_array( $data['post_status'] ?? '', array( 'publish', 'future', 'pending' ), true );
	if ( $check['problems'] && $publish ) {
		if ( $check['live'] ) {
			$check = spokares_document_hold( $check, $post_id );
			if ( in_array( 'title', $check['held'], true ) && ! $check['demoted'] ) {
				$data['post_title'] = wp_slash( $stored->post_title );
			}
		}
		if ( ! $check['live'] || $check['demoted'] ) {
			$data['post_status']  = 'draft';
			$check['demoted']     = true;
			$check['held']        = array();
			$check['keep_source'] = false;
		}
	}
	$GLOBALS['spokares_document_check'] = $check;
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
 * Every document (not in the Trash) in site order: section order, then
 * place, then name.
 *
 * @return array<int,array{id:int,title:string,section:string,menu:int,soon:bool}>
 */
function spokares_document_site_list(): array {
	$posts = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'numberposts' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- one small club library.
			'orderby'     => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);
	$out   = array();
	foreach ( $posts as $p ) {
		$terms = get_the_terms( $p, 'spk_doc_cat' );
		$subs  = get_post_meta( $p->ID, 'spk_sublinks', true );
		$out[] = array(
			'id'      => (int) $p->ID,
			'title'   => html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ),
			'section' => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
			'menu'    => (int) $p->menu_order,
			'soon'    => spokares_document_is_soon( (int) $p->ID ),
			// Course links ("IS-100.c", "Introduction to …"), for the name check.
			'links'   => is_array( $subs ) ? array_values(
				array_map(
					static fn( $l ) => array( (string) ( $l['label'] ?? '' ), (string) ( $l['title'] ?? '' ) ),
					array_filter( $subs, 'is_array' )
				)
			) : array(),
		);
	}
	$rank = array_flip( array_keys( spokares_library_sections() ) );
	usort(
		$out,
		static fn( $a, $b ) => array( $rank[ $a['section'] ] ?? PHP_INT_MAX, $a['menu'], mb_strtolower( $a['title'], 'UTF-8' ) )
			<=> array( $rank[ $b['section'] ] ?? PHP_INT_MAX, $b['menu'], mb_strtolower( $b['title'], 'UTF-8' ) )
	);
	return $out;
}

/**
 * Put a document where "Place in section" says, and number its section 10,
 * 20, 30 … (§3.6). The neighbours are written directly: wp_update_post()
 * would run this form's save hooks on them.
 *
 * @param int    $post_id Document.
 * @param string $section Section slug.
 * @param string $place   'top', 'end' or the ID of the document to follow.
 */
function spokares_document_place( int $post_id, string $section, string $place ): void {
	global $wpdb;
	$others = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'numberposts' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- one small club library section.
			'exclude'     => array( $post_id ),
			'orderby'     => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one small library section, on a document save only.
			'tax_query'   => array(
				array(
					'taxonomy' => 'spk_doc_cat',
					'field'    => 'slug',
					'terms'    => $section,
				),
			),
		)
	);
	$ids = array_map( 'intval', wp_list_pluck( $others, 'ID' ) );
	$at  = count( $ids );
	if ( 'top' === $place ) {
		$at = 0;
	} elseif ( ctype_digit( $place ) && in_array( (int) $place, $ids, true ) ) {
		$at = (int) array_search( (int) $place, $ids, true ) + 1;
	}
	array_splice( $ids, $at, 0, array( $post_id ) );
	foreach ( $ids as $i => $id ) {
		$order = ( $i + 1 ) * 10;
		if ( (int) get_post_field( 'menu_order', $id ) !== $order ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $order ), array( 'ID' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the post cache is cleaned just below.
			clean_post_cache( $id );
		}
	}
}

/**
 * The retained input of this document's last save (typing that wasn't saved,
 * and why), read once per request: the name box and the form both use it.
 *
 * @param int  $post_id Document.
 * @param bool $forget  Drop this request's copy (a save is about to keep new input).
 * @return array{values:array,errors:array}
 */
function spokares_document_retained( int $post_id, bool $forget = false ): array {
	static $cache = array();
	if ( $forget ) {
		unset( $cache[ $post_id ] );
		return array(
			'values' => array(),
			'errors' => array(),
		);
	}
	if ( ! isset( $cache[ $post_id ] ) ) {
		$cache[ $post_id ] = spokares_retained( 'spk_document_' . $post_id );
	}
	return $cache[ $post_id ];
}

/**
 * Save the document's fields and, when allowed, its file. Ends with the one
 * notice of the save (§3.6).
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
	$stored    = spokares_document_stored( $post_id );
	$confirmed = get_post_meta( $post_id, 'spk_confirmed', true );
	$confirmed = is_array( $confirmed ) ? $confirmed : array();
	$result    = ( $GLOBALS['spokares_document_check'] ?? array() ) + array(
		'problems'    => array(),
		'confirm'     => array(),
		'upload'      => '',
		'new_address' => false,
		'held'        => array(),
		'keep_source' => false,
		'demoted'     => false,
		'live'        => false,
	);
	$held      = $result['held'];
	$keep      = $result['keep_source'];
	$source    = $keep ? $stored['source'] : $v['source'];
	$file      = 'upload' === $source && ! $keep ? spokares_document_upload() : null;

	// The section (one term).
	$tax = get_taxonomy( 'spk_doc_cat' );
	if ( $tax && ! in_array( 'section', $held, true ) && array_key_exists( $v['section'], spokares_library_sections() ) && current_user_can( $tax->cap->assign_terms ) ) {
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

	$meta = array(
		'spk_version'   => mb_substr( $v['version'], 0, 30, 'UTF-8' ),
		'spk_most_used' => $v['most_used'] ? '1' : '',
	);
	if ( ! $keep ) {
		$url   = spokares_clean_url( $v['url'] );
		$label = $v['label'];
		$subs  = get_post_meta( $post_id, 'spk_sublinks', true );
		if ( 'link' !== $source && 'link' === $stored['source'] && ! ( is_array( $subs ) && $subs ) && ! $v['sublinks'] ) {
			// Away from a link: the old site's name no longer applies (and on
			// the Documents & forms page it would stand in for "Soon").
			$label = '';
		} elseif ( 'link' === $source && '' === $label && '' !== $url ) {
			$label = spokares_host_name( $url ); // "Site name shown" is filled from the web address.
		}
		$meta['spk_source'] = $source;
		$meta['spk_url']    = '' !== $url ? $url : $v['url'];
		$meta['spk_format'] = $v['format'];
		if ( ! in_array( 'label', $held, true ) ) {
			$meta['spk_source_label'] = $label;
		}
		// What is live stays checked; something new needs this save's tick.
		if ( 'soon' === $source ) {
			$ok = true; // Nothing is published.
		} elseif ( 'link' === $source ) {
			$ok = $result['new_address'] ? $v['privacy'] : $stored['privacy'];
		} else {
			$ok = $v['privacy'] || ( $stored['file'] && $stored['privacy'] );
		}
		$meta['spk_privacy_ok'] = $ok ? '1' : '';
	}
	foreach ( array(
		'note'      => 'spk_note',
		'howto_lbl' => 'spk_howto_label',
		'owner'     => 'spk_owner',
	) as $field => $key ) {
		if ( ! in_array( $field, $held, true ) ) {
			$meta[ $key ] = $v[ $field ];
		}
	}
	if ( ! in_array( 'howto_url', $held, true ) ) {
		$clean                 = spokares_clean_url( $v['howto_url'] );
		$meta['spk_howto_url'] = '' !== $clean ? $clean : $v['howto_url'];
	}
	// Publishing with a fresh privacy tick is a review: an empty "Last
	// reviewed" becomes today.
	$meta['spk_reviewed'] = spokares_is_ymd( $v['reviewed'] ) ? $v['reviewed'] : ( $v['privacy'] && 'publish' === get_post_status( $post_id ) ? spokares_today() : '' );
	if ( $admin ) {
		$meta['spk_needs_check'] = $v['check'] ? '1' : '';
		$meta['spk_keywords']    = $v['keywords'];
		if ( ! in_array( 'sublinks', $held, true ) ) {
			$meta['spk_sublinks'] = $v['sublinks'];
		}
	}
	foreach ( $meta as $key => $value ) {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			// The values are unslashed already, and update_post_meta() unslashes
			// again: slash them so a typed backslash ("C:\ARES") is kept.
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}
	}
	if ( $confirmed ) {
		update_post_meta( $post_id, 'spk_confirmed', $confirmed );
	}

	// Place in section, whatever the document's status (§3.6).
	$section = wp_get_object_terms( $post_id, 'spk_doc_cat', array( 'fields' => 'slugs' ) );
	$section = is_array( $section ) && $section ? (string) $section[0] : '';
	if ( '' !== $section && ( '' !== $v['place'] || 'end' === spokares_document_default_place( $post_id ) ) ) {
		spokares_document_place( $post_id, $section, '' !== $v['place'] ? $v['place'] : 'end' );
	}

	// Editors always take the old file off the web; an administrator may keep it.
	$remove_old = ! $admin || $v['remove_old'];
	$kept       = '';
	$replaced   = false;
	$refused    = '';

	// "Where the file is" moved away from "Upload a file": the uploaded file is
	// out of use and the form no longer shows it. Take it off the web, unless
	// another document or a page uses it.
	if ( ! $keep && $stored['file_id'] && 'upload' !== $source ) {
		if ( $remove_old ) {
			$kept = spokares_remove_document_file( $stored['file_id'], $post_id );
			if ( '' !== $kept ) {
				delete_post_meta( $post_id, 'spk_file' ); // Kept for the other use; this document no longer points at it.
			}
			delete_post_meta( $post_id, 'spk_file_name' );
		} else {
			$kept = __( 'The old file stays on the web (“Remove the old file from the web” was unticked).', 'spokares-core' );
		}
	}

	// The file: only with a fresh tick, only now, only the allowed types.
	if ( $file && 'ok' === $result['upload'] ) {
		$old = absint( get_post_meta( $post_id, 'spk_file', true ) );
		$att = spokares_document_do_upload( $post_id, $file );
		if ( is_wp_error( $att ) ) {
			$refused = $att->get_error_message();
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
			update_post_meta( $post_id, 'spk_file_name', wp_slash( '' !== $file['original'] ? $file['original'] : $file['name'] ) );
			update_post_meta( $post_id, 'spk_privacy_ok', '1' );
			$ext = strtolower( pathinfo( (string) get_attached_file( (int) $att ), PATHINFO_EXTENSION ) );
			update_post_meta( $post_id, 'spk_format', spokares_format_for_ext( $ext ) );
			if ( $old && $old !== (int) $att ) {
				$replaced = true;
				$kept     = $remove_old
					? spokares_remove_document_file( $old, $post_id )
					: __( 'The old file stays on the web (“Remove the old file from the web” was unticked).', 'spokares-core' );
			}
		}
	} elseif ( '' !== $result['upload'] && 'ok' !== $result['upload'] ) {
		$refused = $result['upload'];
	}

	// Typing that wasn't saved, and the reason, for the form after the redirect.
	$errors = $result['problems'];
	if ( '' !== $refused ) {
		$errors['upload'] = $refused;
	}
	$values = array();
	foreach ( $held as $field ) {
		if ( isset( $v[ $field ] ) && is_string( $v[ $field ] ) ) {
			$values[ $field ] = $v[ $field ];
		}
	}
	if ( $keep ) {
		$values += array_intersect_key( $v, array_flip( array( 'source', 'url', 'label', 'format' ) ) );
	}
	spokares_document_retained( $post_id, true );
	if ( $values || '' !== $refused || $result['demoted'] ) {
		spokares_retain( 'spk_document_' . $post_id, $values, $errors );
	}

	spokares_document_notice( $post_id, $v, $result, $refused, $replaced, $kept );
	unset( $GLOBALS['spokares_document_check'] );
	$busy = false;
}
add_action( 'save_post_spk_document', 'spokares_save_document' );

/**
 * "Place in section" before any choice: "At the end" for a document that has
 * never been saved, else '' (it stays where it is).
 *
 * @param int $post_id Document.
 */
function spokares_document_default_place( int $post_id ): string {
	$check = $GLOBALS['spokares_document_check'] ?? array();
	return empty( $check['live'] ) && 0 === (int) get_post_field( 'menu_order', $post_id ) ? 'end' : '';
}

/**
 * The one notice of a document save (§3.6, R3, R6).
 *
 * @param int    $post_id  Document.
 * @param array  $v        Submitted values.
 * @param array  $result   The check made before the save.
 * @param string $refused  Why a chosen file was refused ('' when it wasn't).
 * @param bool   $replaced A new file replaced the old one.
 * @param string $kept     Why the old file stays on the web ('' when it doesn't).
 */
function spokares_document_notice( int $post_id, array $v, array $result, string $refused, bool $replaced, string $kept ): void {
	$status = get_post_status( $post_id );
	$see    = array( spokares_site_url( '/members/documents/', (string) get_post_field( 'post_name', $post_id ) ), __( 'See it on the Documents & forms page', 'spokares-core' ) );
	$after  = '' !== $kept ? ' ' . $kept : '';

	if ( $result['demoted'] ) {
		$keys = array_keys( $result['problems'] );
		if ( array( 'privacy' ) === $keys && '' === $refused ) {
			$tick = 'link' === $v['source'] ? __( 'I checked the page it links to', 'spokares-core' ) : __( 'I checked this file', 'spokares-core' );
			/* translators: %s: the words of the privacy tick, e.g. "I checked this file". */
			spokares_add_notice( 'error', sprintf( __( 'Not published yet: tick “%s”, then click Publish.', 'spokares-core' ), $tick ) );
		} else {
			spokares_add_notice( 'error', __( 'Not published yet. Fix the boxes outlined in red, then click Publish.', 'spokares-core' ) );
		}
		return;
	}

	if ( 'publish' === $status ) {
		$names = spokares_document_field_names();
		$not   = array();
		foreach ( $result['held'] as $field ) {
			$not[] = $names[ $field ] ?? $field;
		}
		if ( $result['keep_source'] ) {
			$not[] = 'link' === $v['source'] ? $names['url'] : $names['upload'];
		} elseif ( '' !== $refused ) {
			$not[] = $names['upload'];
		}
		if ( $not ) {
			/* translators: %s: the fields that weren't saved, e.g. "the new file". */
			spokares_add_notice( 'warning', sprintf( __( 'Saved, except %s (outlined in red).', 'spokares-core' ), spokares_and_list( $not ) ) );
		} elseif ( $replaced ) {
			$text = '' === $kept
				? __( 'Saved. The new file is on the site and the old one is off the web.', 'spokares-core' )
				: __( 'Saved. The new file is on the site.', 'spokares-core' ) . $after;
			spokares_add_notice( 'success', $text, $see[0], $see[1] );
		} else {
			spokares_add_notice( 'success', ( $result['live'] ? __( 'Saved.', 'spokares-core' ) : __( 'Published.', 'spokares-core' ) ) . $after, $see[0], $see[1] );
		}
		return;
	}

	if ( '' !== $refused ) {
		spokares_add_notice( 'warning', __( 'Draft saved, except the new file (outlined in red).', 'spokares-core' ) . $after );
		return;
	}
	spokares_add_notice( 'info', __( 'Draft saved. It isn’t on the site until you Publish.', 'spokares-core' ) . $after );
}

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
 * "Take it off the site" (the Save box's trash link, which carries
 * spk_remove_file=1) takes the file off the web too (§3.9). WordPress has
 * already checked the trash nonce and the capability. A document trashed any
 * other way keeps its file, so it can be restored with it.
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
		} else {
			delete_post_meta( $post_id, 'spk_file_name' );
		}
	}
}
add_action( 'trashed_post', 'spokares_document_trashed' );

/**
 * The document form's trash link takes the file off the web
 * (spokares_document_trashed()).
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

/**
 * Does this document say "Upload a file" but have no file on the web (its
 * file was removed when it went to the Trash)?
 *
 * @param int $post_id Document.
 */
function spokares_document_lost_file( int $post_id ): bool {
	if ( 'spk_document' !== get_post_type( $post_id ) || 'upload' !== (string) get_post_meta( $post_id, 'spk_source', true ) ) {
		return false;
	}
	$att = absint( get_post_meta( $post_id, 'spk_file', true ) );
	return ! $att || ! wp_get_attachment_url( $att );
}

/**
 * Restore and Undo bring a document back as it was (Published or Draft),
 * except one whose file was removed from the web when it went to the Trash:
 * it comes back as a Draft, never Published without a file. Runs after
 * WordPress's own wp_untrash_post_set_previous_status (10).
 *
 * @param string $status   Status to restore to.
 * @param int    $post_id  Post.
 * @param string $previous The status before the Trash.
 */
function spokares_document_untrash_status( $status, $post_id, $previous = '' ) {
	if ( 'spk_document' !== get_post_type( (int) $post_id ) ) {
		return $status;
	}
	if ( spokares_document_lost_file( (int) $post_id ) ) {
		return 'draft';
	}
	return in_array( $previous, array( 'publish', 'draft', 'pending', 'private' ), true ) ? $previous : $status;
}
add_filter( 'wp_untrash_post_status', 'spokares_document_untrash_status', 20, 3 );

/**
 * The Documents list's notice after "Take it off the site", Undo or Restore
 * (§3.6): the document by name, and where it is now. One notice.
 *
 * @param array $messages    Messages by post type.
 * @param array $bulk_counts Counts by action.
 */
function spokares_document_bulk_messages( $messages, $bulk_counts ) {
	$messages = is_array( $messages ) ? $messages : array();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the message after core's own (nonce-checked) redirect; read-only.
	$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
	if ( 'spk_document' !== $type ) {
		return $messages;
	}
	$quote = static fn( int $id ): string => str_replace( '%', '%%', esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) );
	$m     = $messages['spk_document'] ?? array();

	if ( ! empty( $bulk_counts['trashed'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- as above.
		$ids = isset( $_GET['ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ) ) ) ) ) : array();
		if ( 1 === count( $ids ) ) {
			$id           = (int) reset( $ids );
			$was          = (string) get_post_meta( $id, '_wp_trash_meta_status', true );
			$m['trashed'] = sprintf(
				/* translators: %s: document name. */
				'publish' === $was ? __( '“%s” is off the site.', 'spokares-core' ) : __( '“%s” is in the Trash.', 'spokares-core' ),
				$quote( $id )
			);
		} else {
			/* translators: %s: how many. */
			$m['trashed'] = _n( '%s document is off the site.', '%s documents are off the site.', (int) $bulk_counts['trashed'], 'spokares-core' );
		}
	}

	if ( ! empty( $bulk_counts['untrashed'] ) ) {
		// Core's redirect doesn't name what it restored: spokares_remember_restored() did.
		$ids = spokares_take_restored( 'spk_document' );
		if ( 1 === count( $ids ) ) {
			$id = (int) reset( $ids );
			if ( 'publish' === get_post_status( $id ) ) {
				/* translators: %s: document name. */
				$text = __( '“%s” is back on the site.', 'spokares-core' );
			} elseif ( spokares_document_lost_file( $id ) ) {
				/* translators: %s: document name. */
				$text = __( '“%s” is back as a draft: upload the file again, then Publish.', 'spokares-core' );
			} else {
				/* translators: %s: document name. */
				$text = __( '“%s” is back as a draft.', 'spokares-core' );
			}
			$m['untrashed'] = sprintf( $text, $quote( $id ) );
		} else {
			$drafts         = array_filter( $ids, static fn( $id ) => 'draft' === get_post_status( $id ) );
			$m['untrashed'] = $drafts
				/* translators: %s: how many. */
				? _n( '%s document is back; drafts need their file uploaded again, then Publish.', '%s documents are back; drafts need their file uploaded again, then Publish.', (int) $bulk_counts['untrashed'], 'spokares-core' )
				/* translators: %s: how many. */
				: _n( '%s document is back on the site.', '%s documents are back on the site.', (int) $bulk_counts['untrashed'], 'spokares-core' );
		}
	}
	$messages['spk_document'] = $m;
	return $messages;
}
add_filter( 'bulk_post_updated_messages', 'spokares_document_bulk_messages', 20, 2 );

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
	delete_post_meta( $id, 'spk_file_name' );
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
	$src   = (string) $get( 'spk_source' );
	return array(
		// The name as typed (stored titles are HTML-filtered: "&" is "&amp;").
		'title'      => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
		'section'    => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
		'source'     => in_array( $src, array( 'upload', 'link', 'soon' ), true ) ? $src : ( 'auto-draft' === $post->post_status ? 'upload' : 'soon' ),
		'privacy'    => false,
		'url'        => (string) $get( 'spk_url' ),
		'version'    => (string) $get( 'spk_version' ),
		'note'       => (string) $get( 'spk_note' ),
		'label'      => (string) $get( 'spk_source_label' ),
		'format'     => (string) $get( 'spk_format' ),
		'howto_lbl'  => (string) $get( 'spk_howto_label' ),
		'howto_url'  => (string) $get( 'spk_howto_url' ),
		'most_used'  => '1' === (string) $get( 'spk_most_used' ),
		'place'      => '',
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
 * "Place in section": the choices for a section, and the one that says where
 * the document is now.
 *
 * @param int    $post_id Document.
 * @param string $section Section slug ('' when none is picked yet).
 * @param bool   $is_new  Never saved.
 * @return array{0:array<string,string>,1:string} Choices (value => words) and the chosen value.
 */
function spokares_document_place_choices( int $post_id, string $section, bool $is_new ): array {
	$choices = array( 'top' => __( 'At the top', 'spokares-core' ) );
	$chosen  = 'end';
	$prev    = 'top';
	$found   = false;
	foreach ( '' !== $section ? spokares_document_site_list() : array() as $doc ) {
		if ( $doc['section'] !== $section ) {
			continue;
		}
		if ( $doc['id'] === $post_id ) {
			$found  = true;
			$chosen = $prev;
			continue;
		}
		/* translators: %s: document name. */
		$choices[ (string) $doc['id'] ] = sprintf( __( 'After “%s”', 'spokares-core' ), $doc['title'] );
		$prev                           = (string) $doc['id'];
	}
	$choices['end'] = __( 'At the end', 'spokares-core' );
	if ( $is_new || ! $found || 2 === count( $choices ) ) {
		$chosen = 'end';
	}
	return array( $choices, $chosen );
}

/**
 * The "public agency" tick under a field with a phone number or e-mail
 * address (its sentence is printed with the field's other problems).
 *
 * @param array  $confirm Field => hits.
 * @param string $field   Field.
 */
function spokares_document_confirm_tick( array $confirm, string $field ): void {
	if ( empty( $confirm[ $field ] ) ) {
		return;
	}
	printf(
		'<label class="spk-confirm"><input type="checkbox" name="spk_confirm[%1$s]" value="1"> %2$s</label>',
		esc_attr( $field ),
		esc_html( spokares_confirm_label( $confirm[ $field ] ) )
	);
}

/**
 * The name's visible label, above the name box (§3.6: create = edit).
 *
 * @param WP_Post $post Post.
 */
function spokares_document_title_label( $post ): void {
	if ( $post instanceof WP_Post && 'spk_document' === $post->post_type ) {
		echo '<label for="title" class="spk-title-label">' . esc_html__( 'Document name (required)', 'spokares-core' ) . '</label>';
	}
}
add_action( 'edit_form_top', 'spokares_document_title_label' );

/**
 * The name box shows the name that wasn't saved (held back on a published
 * document), until the next save.
 *
 * @param string $title   Title.
 * @param int    $post_id Post.
 */
function spokares_document_held_title( $title, $post_id ) {
	global $pagenow;
	if ( ! is_admin() || 'post.php' !== $pagenow || 'spk_document' !== get_post_type( (int) $post_id ) ) {
		return $title;
	}
	$held = spokares_document_retained( (int) $post_id )['values'];
	return isset( $held['title'] ) ? (string) $held['title'] : $title;
}
add_filter( 'edit_post_title', 'spokares_document_held_title', 20, 2 );

/**
 * The document form (after the name).
 *
 * @param WP_Post $post Post.
 */
function spokares_document_form_fields( $post ): void {
	if ( ! $post instanceof WP_Post || 'spk_document' !== $post->post_type ) {
		return;
	}
	$new       = 'auto-draft' === $post->post_status;
	$admin     = current_user_can( 'manage_options' );
	$retained  = $new ? array(
		'values' => array(),
		'errors' => array(),
	) : spokares_document_retained( $post->ID );
	$v         = spokares_document_form_values( $post );
	$v         = array_merge( $v, array_intersect_key( $retained['values'], $v ) );
	$stored    = spokares_document_stored( $post->ID );
	$confirmed = get_post_meta( $post->ID, 'spk_confirmed', true );
	// The link's tick stands for the page that is live; anything new needs it again.
	$v['privacy'] = 'link' === $v['source'] && 'link' === $stored['source'] && $stored['privacy'] && spokares_clean_url( $v['url'] ) === spokares_clean_url( $stored['url'] );
	$check        = $new ? array(
		'problems' => array(),
		'confirm'  => array(),
	) : spokares_document_check( $v, $post->ID, is_array( $confirmed ) ? $confirmed : array(), null );
	$p            = array_merge( $check['problems'], $retained['errors'] );
	$c            = $check['confirm'];
	$source       = $v['source'];
	$file         = $stored['file'];
	$file_url     = $file ? (string) wp_get_attachment_url( $file ) : '';
	$pulled       = get_post_meta( $post->ID, 'spk_pulled', true );
	$button       = 0;
	foreach ( array_slice( spokares_opt( 'spk_tiles' ), 0, 4 ) as $i => $tile ) {
		if ( (int) $tile['doc'] === $post->ID ) {
			$button = $i + 1;
		}
	}
	list( $places, $place ) = spokares_document_place_choices( $post->ID, $v['section'], $new );
	$tick_file              = $file && $stored['privacy'] ? 'new-file' : 'file';
	$err                    = static fn( string $key ): string => isset( $p[ $key ] ) ? ' spk-field-error' : '';
	wp_nonce_field( 'spokares_document_meta', 'spokares_document_nonce' );
	?>
	<div class="spk-doc-form">
		<?php if ( $new ) : ?>
			<p class="spk-name-match" id="spk-name-match" role="status"></p>
		<?php endif; ?>
		<?php if ( isset( $p['title'] ) ) : ?>
			<span class="spk-error-text" id="spk-title-problem"><?php echo esc_html( $p['title'] ); ?></span>
		<?php endif; ?>
		<?php spokares_document_confirm_tick( $c, 'title' ); ?>
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

		<fieldset class="spk-card spk-doc-section<?php echo esc_attr( $err( 'section' ) ); ?>">
			<legend><?php esc_html_e( 'Section (required)', 'spokares-core' ); ?></legend>
			<?php foreach ( spokares_library_sections() as $slug => $label ) : ?>
				<label class="spk-choice"><input type="radio" name="spk_section" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, $v['section'] ); ?> required> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
			<?php spokares_err_text( $p, 'section' ); ?>
		</fieldset>

		<fieldset class="spk-card spk-source">
			<legend><?php esc_html_e( 'Where the file is', 'spokares-core' ); ?></legend>
			<p class="spk-source-choices">
				<label class="spk-choice"><input type="radio" name="spk_source" value="upload" <?php checked( 'upload', $source ); ?>> <?php echo esc_html( 'link' === $stored['source'] && ! $new ? __( 'Upload a file instead', 'spokares-core' ) : __( 'Upload a file', 'spokares-core' ) ); ?></label>
				<label class="spk-choice"><input type="radio" name="spk_source" value="link" <?php checked( 'link', $source ); ?>> <?php esc_html_e( 'Link to another site', 'spokares-core' ); ?></label>
				<label class="spk-choice"><input type="radio" name="spk_source" value="soon" <?php checked( 'soon', $source ); ?>> <?php esc_html_e( 'Not ready yet (shows “Soon”)', 'spokares-core' ); ?></label>
			</p>

			<div class="spk-upload" data-source="upload"<?php echo 'upload' === $source ? '' : ' hidden'; ?>>
				<?php if ( $file && '' !== $file_url ) : ?>
					<?php
					$name  = (string) get_post_meta( $post->ID, 'spk_file_name', true );
					$bytes = (int) @filesize( (string) get_attached_file( $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file just shows no size.
					$parts = array(
						sprintf(
							/* translators: 1: file name, 2: upload date, e.g. "Sep 27". */
							__( 'Current file: %1$s, uploaded %2$s', 'spokares-core' ),
							'' !== $name ? $name : wp_basename( $file_url ),
							get_the_date( 'M j', $file )
						),
					);
					if ( $bytes ) {
						$parts[] = size_format( $bytes );
					}
					?>
					<p class="spk-current-file"><?php echo esc_html( implode( ' · ', $parts ) ); ?> · <a href="<?php echo esc_url( $file_url ); ?>"><?php esc_html_e( 'Open', 'spokares-core' ); ?></a></p>
				<?php endif; ?>
				<p class="spk-privacy<?php echo esc_attr( isset( $p['privacy'] ) || isset( $p['upload'] ) ? ' spk-field-error' : '' ); ?>">
					<label><input type="checkbox" name="spk_privacy_ok" value="1" id="spk-privacy" data-spk-tick="<?php echo esc_attr( $tick_file ); ?>" <?php disabled( 'upload' !== $source ); ?> <?php echo ! $file || ! $stored['privacy'] ? 'required' : ''; ?>>
					<?php echo esc_html( spokares_document_privacy_words( $tick_file ) ); ?></label>
				</p>
				<?php if ( 'upload' === $source ) : ?>
					<?php spokares_err_text( $p, 'privacy' ); ?>
				<?php endif; ?>
				<p class="spk-file">
					<label for="spk-upload"><?php echo esc_html( $file ? __( 'Replace with', 'spokares-core' ) : __( 'File', 'spokares-core' ) ); ?></label>
					<input type="file" id="spk-upload" name="spk_upload" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" disabled class="<?php echo esc_attr( trim( $err( 'upload' ) ) ); ?>">
				</p>
				<?php if ( ! $file ) : ?>
					<p class="description"><?php esc_html_e( 'PDF, Word (.docx), Excel (.xlsx), JPEG or PNG.', 'spokares-core' ); ?></p>
				<?php elseif ( $admin ) : ?>
					<p><label><input type="checkbox" name="spk_remove_old" value="1" checked> <?php esc_html_e( 'Remove the old file from the web', 'spokares-core' ); ?></label></p>
				<?php endif; ?>
				<?php spokares_err_text( $p, 'upload' ); ?>
			</div>

			<div class="spk-link" data-source="link"<?php echo 'link' === $source ? '' : ' hidden'; ?>>
				<div class="spk-field">
					<label for="spk-url"><?php esc_html_e( 'Web address', 'spokares-core' ); ?></label>
					<input type="text" inputmode="url" id="spk-url" name="spk_url" class="large-text<?php echo esc_attr( $err( 'url' ) ); ?>" value="<?php echo esc_attr( $v['url'] ); ?>" data-stored="<?php echo esc_attr( 'link' === $stored['source'] && $stored['privacy'] ? $stored['url'] : '' ); ?>" aria-describedby="spk-url-hint" autocomplete="off" spellcheck="false">
					<p class="description" id="spk-url-hint"><?php esc_html_e( 'Copy it from your browser’s address bar.', 'spokares-core' ); ?></p>
					<?php spokares_err_text( $p, 'url' ); ?>
				</div>
				<div class="spk-field">
					<label for="spk-label"><?php esc_html_e( 'Site name shown', 'spokares-core' ); ?></label>
					<input type="text" id="spk-label" name="spk_source_label" class="regular-text<?php echo esc_attr( $err( 'label' ) ); ?>" maxlength="40" value="<?php echo esc_attr( $v['label'] ); ?>" placeholder="<?php echo esc_attr( '' !== spokares_clean_url( $v['url'] ) ? spokares_host_name( spokares_clean_url( $v['url'] ) ) : '' ); ?>">
					<?php spokares_err_text( $p, 'label' ); ?>
					<?php spokares_document_confirm_tick( $c, 'label' ); ?>
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
				</div>
				<p class="spk-privacy<?php echo esc_attr( 'link' === $source && isset( $p['privacy'] ) ? ' spk-field-error' : '' ); ?>">
					<label><input type="checkbox" name="spk_privacy_ok" value="1" id="spk-privacy-link" data-spk-tick="link" <?php checked( $v['privacy'] ); ?> <?php disabled( 'link' !== $source ); ?> required>
					<?php echo esc_html( spokares_document_privacy_words( 'link' ) ); ?></label>
				</p>
				<?php if ( 'link' === $source ) : ?>
					<?php spokares_err_text( $p, 'privacy' ); ?>
				<?php endif; ?>
			</div>
		</fieldset>

		<div class="spk-field">
			<label for="spk-version"><?php esc_html_e( 'Version or date', 'spokares-core' ); ?></label>
			<input type="text" id="spk-version" name="spk_version" class="regular-text" maxlength="30" value="<?php echo esc_attr( $v['version'] ); ?>" placeholder="<?php esc_attr_e( 'v3 or Sept 2026', 'spokares-core' ); ?>">
		</div>
		<div class="spk-field">
			<label for="spk-note"><?php esc_html_e( 'Short note (60 characters)', 'spokares-core' ); ?></label>
			<input type="text" id="spk-note" name="spk_note" class="large-text<?php echo esc_attr( $err( 'note' ) ); ?>" maxlength="60" value="<?php echo esc_attr( $v['note'] ); ?>">
			<span class="spk-left" id="spk-note-left" aria-live="polite"></span>
			<?php spokares_err_text( $p, 'note' ); ?>
			<?php spokares_document_confirm_tick( $c, 'note' ); ?>
		</div>
		<?php if ( ! $admin && $v['sublinks'] ) : ?>
			<p class="spk-course-links">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: the course links' words, e.g. "IS-100.c, IS-200.c". */
						__( 'Course links: %s (the webmaster changes these).', 'spokares-core' ),
						implode( ', ', array_filter( array_map( static fn( $s ) => trim( (string) ( $s['label'] ?? '' ) ), $v['sublinks'] ) ) )
					)
				);
				?>
			</p>
		<?php endif; ?>

		<details class="spk-more"<?php echo ( isset( $p['howto_url'] ) || isset( $p['howto_lbl'] ) || isset( $c['howto_lbl'] ) || isset( $p['owner'] ) ) ? ' open' : ''; ?>>
			<summary><?php esc_html_e( 'More options', 'spokares-core' ); ?></summary>
			<fieldset class="spk-field spk-howto">
				<legend><?php esc_html_e( '“How to” link', 'spokares-core' ); ?></legend>
				<label><?php esc_html_e( 'Words', 'spokares-core' ); ?> <input type="text" name="spk_howto_label" class="regular-text<?php echo esc_attr( $err( 'howto_lbl' ) ); ?>" maxlength="60" value="<?php echo esc_attr( $v['howto_lbl'] ); ?>"></label>
				<label><?php esc_html_e( 'Web address', 'spokares-core' ); ?> <input type="text" inputmode="url" name="spk_howto_url" id="spk-howto-url" class="regular-text<?php echo esc_attr( $err( 'howto_url' ) ); ?>" value="<?php echo esc_attr( $v['howto_url'] ); ?>" autocomplete="off" spellcheck="false"></label>
				<?php spokares_err_text( $p, 'howto_lbl' ); ?>
				<?php spokares_err_text( $p, 'howto_url' ); ?>
				<?php spokares_document_confirm_tick( $c, 'howto_lbl' ); ?>
			</fieldset>
			<div class="spk-field">
				<?php if ( $button ) : ?>
					<?php if ( $v['most_used'] ) : ?>
						<input type="hidden" name="spk_most_used" value="1">
					<?php endif; ?>
					<p class="spk-button-line">
						<?php
						printf(
							/* translators: 1: button number (1-4), 2: link to the Most Used screen. */
							esc_html__( 'It’s button %1$d on %2$s.', 'spokares-core' ),
							(int) $button,
							'<a href="' . esc_url( admin_url( 'admin.php?page=spokares-tiles' ) ) . '">' . esc_html__( 'the Most Used screen', 'spokares-core' ) . '</a>'
						);
						?>
					</p>
				<?php else : ?>
					<label><input type="checkbox" name="spk_most_used" value="1" <?php checked( $v['most_used'] ); ?>> <?php esc_html_e( 'Also list under Most used', 'spokares-core' ); ?></label>
				<?php endif; ?>
			</div>
			<div class="spk-field">
				<label for="spk-place"><?php esc_html_e( 'Place in section', 'spokares-core' ); ?></label>
				<select id="spk-place" name="spk_place">
					<?php foreach ( $places as $value => $words ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $value, $place ); ?>><?php echo esc_html( $words ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="spk-field">
				<label for="spk-owner"><?php esc_html_e( 'Owner', 'spokares-core' ); ?></label>
				<input type="text" id="spk-owner" name="spk_owner" class="regular-text<?php echo esc_attr( $err( 'owner' ) ); ?>" maxlength="60" value="<?php echo esc_attr( $v['owner'] ); ?>">
				<?php spokares_err_text( $p, 'owner' ); ?>
			</div>
			<div class="spk-field">
				<label for="spk-reviewed"><?php esc_html_e( 'Last reviewed', 'spokares-core' ); ?></label>
				<input type="date" id="spk-reviewed" name="spk_reviewed" value="<?php echo esc_attr( $v['reviewed'] ); ?>">
				<button type="button" class="button spk-reviewed-today" data-target="spk-reviewed"><?php esc_html_e( 'Mark reviewed today', 'spokares-core' ); ?></button>
			</div>
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
					<?php spokares_document_confirm_tick( $c, 'sublinks' ); ?>
				</fieldset>
				<div class="spk-field">
					<label for="spk-keywords"><?php esc_html_e( 'Search words', 'spokares-core' ); ?></label>
					<input type="text" id="spk-keywords" name="spk_keywords" class="large-text" value="<?php echo esc_attr( $v['keywords'] ); ?>">
				</div>
				<div class="spk-field">
					<label for="spk-slug"><?php esc_html_e( 'Link name (slug)', 'spokares-core' ); ?></label>
					<input type="text" id="spk-slug" name="post_name" class="regular-text" value="<?php echo esc_attr( $post->post_name ); ?>" pattern="[a-z0-9\-]*">
					<p class="description"><?php esc_html_e( 'The row anchor and the stable link /docs/<slug>/. Changing it breaks old links.', 'spokares-core' ); ?></p>
				</div>
			</details>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'edit_form_after_title', 'spokares_document_form_fields' );

/**
 * Side boxes: Save, and (administrators) Webmaster check.
 */
function spokares_document_boxes(): void {
	add_meta_box( 'spokares_savebox', __( 'Save', 'spokares-core' ), static fn( $post ) => spokares_render_save_box( $post ), 'spk_document', 'side', 'high' );
	if ( current_user_can( 'manage_options' ) ) {
		add_meta_box( 'spokares_checking', __( 'Webmaster check', 'spokares-core' ), 'spokares_render_checking_box', 'spk_document', 'side', 'default' );
	}
}
add_action( 'add_meta_boxes_spk_document', 'spokares_document_boxes' );

/**
 * The form's script and styles (assets/js/admin-documents.js), on the
 * document screens and on Most Used, after the shared admin-forms script.
 *
 * @param string $hook Screen hook.
 */
function spokares_document_assets( $hook ): void {
	$screen = get_current_screen();
	$docs   = $screen && 'spk_document' === $screen->post_type && in_array( $screen->base, array( 'post', 'edit' ), true );
	$tiles  = str_contains( (string) $hook, 'spokares-tiles' );
	if ( ! $docs && ! $tiles ) {
		return;
	}
	wp_enqueue_style( 'spokares-admin-documents', SPOKARES_CORE_URL . 'assets/css/admin-documents.css', array( 'spokares-admin' ), spokares_asset_version( 'assets/css/admin-documents.css' ) );
	if ( $docs && 'edit' === $screen->base ) {
		return; // The list needs no script.
	}
	wp_enqueue_script( 'spokares-admin-documents', SPOKARES_CORE_URL . 'assets/js/admin-documents.js', array( 'spokares-admin-forms' ), spokares_asset_version( 'assets/js/admin-documents.js' ), true );
	$cfg  = array(
		/* translators: %d: a Most Used button number (1-4). */
		'nowButton' => __( '(now button %d)', 'spokares-core' ),
	);
	$post = $docs ? get_post() : null;
	if ( $post instanceof WP_Post ) {
		$cfg += array(
			'id'          => (int) $post->ID,
			'isNew'       => 'auto-draft' === $post->post_status,
			'editBase'    => admin_url( 'post.php?action=edit&post=' ),
			// [id, name, section, Soon, course links], in site order: for the name check and Place in section.
			'docs'        => array_map( static fn( $d ) => array( $d['id'], $d['title'], $d['section'], $d['soon'] ? 1 : 0, $d['links'] ), spokares_document_site_list() ),
			'hosts'       => spokares_host_names(),
			/* translators: 1: document name, 2: " (Soon)" when it isn't ready yet. */
			'already'     => __( 'Already a document: %1$s%2$s.', 'spokares-core' ),
			/* translators: 1: a course link's words, e.g. "IS-100.c"; 2: the document it is under, e.g. "FEMA courses". */
			'alreadyLink' => __( 'Already on the site: %1$s, under %2$s.', 'spokares-core' ),
			'soon'        => __( ' (Soon)', 'spokares-core' ),
			'openIt'      => __( 'Open it', 'spokares-core' ),
			/* translators: %d: characters left. */
			'left'        => __( '%d left', 'spokares-core' ),
			'top'         => __( 'At the top', 'spokares-core' ),
			/* translators: %s: document name. */
			'after'       => __( 'After “%s”', 'spokares-core' ),
			'end'         => __( 'At the end', 'spokares-core' ),
		);
	}
	wp_add_inline_script( 'spokares-admin-documents', 'window.spokaresDocs = ' . wp_json_encode( $cfg ) . ';', 'before' );
}
add_action( 'admin_enqueue_scripts', 'spokares_document_assets', 20 );

/* -------------------------------------------------------------- list table */

/**
 * Columns: Document, Section, Source, Version, Reviewed, and (administrators)
 * Needs checking.
 *
 * @param array $cols Columns.
 */
function spokares_document_columns( $cols ): array {
	$out = array(
		'cb'                   => $cols['cb'] ?? '<input type="checkbox">',
		'title'                => __( 'Document', 'spokares-core' ),
		'taxonomy-spk_doc_cat' => __( 'Section', 'spokares-core' ),
		'spk_source'           => __( 'Source', 'spokares-core' ),
		'spk_version'          => __( 'Version', 'spokares-core' ),
		'spk_reviewed'         => __( 'Reviewed', 'spokares-core' ),
	);
	if ( current_user_can( 'manage_options' ) ) {
		$out['spk_check'] = __( 'Needs checking', 'spokares-core' );
	}
	return $out;
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
			$src  = $get( 'spk_source' );
			$subs = get_post_meta( $post_id, 'spk_sublinks', true );
			if ( 'upload' === $src ) {
				echo absint( $get( 'spk_file' ) ) ? esc_html__( 'File', 'spokares-core' ) : esc_html__( 'File (none yet)', 'spokares-core' );
			} elseif ( 'link' === $src ) {
				esc_html_e( 'Link', 'spokares-core' );
			} elseif ( is_array( $subs ) && $subs ) {
				/* translators: %d: number of course links. */
				echo esc_html( sprintf( __( 'Links (%d)', 'spokares-core' ), count( $subs ) ) );
			} else {
				esc_html_e( 'Soon', 'spokares-core' );
			}
			if ( in_array( $src, array( 'upload', 'link' ), true ) && '1' !== $get( 'spk_privacy_ok' ) ) {
				echo '<br><span class="spk-flag">' . esc_html__( 'Privacy not checked', 'spokares-core' ) . '</span>';
			}
			break;
		case 'spk_version':
			echo esc_html( $get( 'spk_version' ) );
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
 * Sortable: Document and Reviewed (the Dashboard links to Reviewed, oldest first).
 *
 * @param array $cols Sortable columns.
 */
function spokares_document_sortable( $cols ): array {
	$cols['spk_reviewed'] = 'spk_reviewed';
	return $cols;
}
add_filter( 'manage_edit-spk_document_sortable_columns', 'spokares_document_sortable' );

/**
 * Every document on one page.
 */
function spokares_document_per_page(): int {
	return 200;
}
add_filter( 'edit_spk_document_per_page', 'spokares_document_per_page' );

/**
 * The meta query of the "Soon" view: not ready yet, and no course links.
 */
function spokares_document_soon_meta_query(): array {
	return array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array(
				'key'   => 'spk_source',
				'value' => 'soon',
			),
			array(
				'key'     => 'spk_source',
				'compare' => 'NOT EXISTS',
			),
		),
		array(
			'key'     => 'spk_sublinks',
			'compare' => 'NOT EXISTS',
		),
	);
}

/**
 * Is the list showing the Soon view (edit.php?…&spk_source=soon)?
 */
function spokares_document_list_soon(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filtering only.
	return isset( $_GET['spk_source'] ) && 'soon' === sanitize_key( wp_unslash( $_GET['spk_source'] ) );
}

/**
 * The list: Section filter, Soon view, Needs checking, the Reviewed sort,
 * and site order by default (§3.6).
 *
 * @param WP_Query $q Query.
 */
function spokares_document_list_query( $q ): void {
	if ( ! is_admin() || ! $q->is_main_query() || 'spk_document' !== $q->get( 'post_type' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- list filtering only.
	$section = isset( $_GET['spk_section'] ) ? sanitize_key( wp_unslash( $_GET['spk_section'] ) ) : '';
	$chosen  = isset( $_GET['orderby'] ) && '' !== $_GET['orderby'];
	// phpcs:enable
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
	$meta = array();
	if ( spokares_list_needs_check() ) {
		$meta[] = array(
			'key'   => 'spk_needs_check',
			'value' => '1',
		);
	}
	if ( spokares_document_list_soon() ) {
		// What members see as "Soon" (the Dashboard's "{n} marked Soon").
		$meta[] = spokares_document_soon_meta_query();
		$q->set( 'post_status', 'publish' );
	}
	if ( $meta ) {
		$q->set( 'meta_query', array_merge( array( 'relation' => 'AND' ), $meta ) );
	}
	if ( 'spk_reviewed' === $q->get( 'orderby' ) ) {
		// Sort only: a document never reviewed has no spk_reviewed row, and a
		// plain meta_key sort (an INNER JOIN) would drop it from the list. The
		// NOT EXISTS clause's LEFT JOIN carries the key in its ON, so its
		// meta_value is the date or NULL ("Not yet", sorted as the oldest).
		$order = 'DESC' === strtoupper( (string) $q->get( 'order' ) ) ? 'DESC' : 'ASC';
		$sort  = array(
			'relation'         => 'OR',
			'spk_reviewed_day' => array(
				'key'     => 'spk_reviewed',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'spk_reviewed',
				'compare' => 'EXISTS',
			),
		);
		if ( $meta ) {
			$sort = array(
				'relation' => 'AND',
				$sort,
				array_merge( array( 'relation' => 'AND' ), $meta ),
			);
		}
		$q->set( 'meta_query', $sort );
		$q->set(
			'orderby',
			array(
				'spk_reviewed_day' => $order,
				'title'            => 'ASC',
			)
		);
	} elseif ( ! $chosen ) {
		// Site order: the section's order is applied to the result in PHP
		// (spokares_document_site_order()); the rest is SQL.
		$q->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
		$q->set( 'spk_site_order', true );
	}
}
add_action( 'pre_get_posts', 'spokares_document_list_query' );

/**
 * The list in site order: section order, then place, then name.
 *
 * @param array    $posts Posts.
 * @param WP_Query $q     Query.
 */
function spokares_document_site_order( $posts, $q ) {
	if ( ! $q instanceof WP_Query || ! $q->get( 'spk_site_order' ) || ! is_array( $posts ) || count( $posts ) < 2 ) {
		return $posts;
	}
	$rank = array_flip( array_keys( spokares_library_sections() ) );
	$key  = static function ( $p ) use ( $rank ): array {
		$p     = get_post( $p );
		$terms = $p ? get_the_terms( $p, 'spk_doc_cat' ) : false;
		$slug  = $terms && ! is_wp_error( $terms ) ? $terms[0]->slug : '';
		return array( $rank[ $slug ] ?? PHP_INT_MAX, $p ? (int) $p->menu_order : 0, $p ? mb_strtolower( $p->post_title, 'UTF-8' ) : '' );
	};
	usort( $posts, static fn( $a, $b ) => $key( $a ) <=> $key( $b ) );
	return $posts;
}
add_filter( 'the_posts', 'spokares_document_site_order', 10, 2 );

/**
 * How many documents members see as "Soon" (published, not ready yet).
 */
function spokares_document_soon_count(): int {
	$q = new WP_Query(
		array(
			'post_type'      => 'spk_document',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one small library.
			'meta_query'     => spokares_document_soon_meta_query(),
		)
	);
	return (int) $q->found_posts;
}

/**
 * The views: All · Published · Drafts · Soon (n) · Trash ("Mine" for
 * administrators only), and the one line above the table (§3.6).
 *
 * @param array $views Views.
 */
function spokares_document_views( $views ): array {
	$views = is_array( $views ) ? $views : array();
	if ( ! current_user_can( 'manage_options' ) ) {
		unset( $views['mine'] );
	}
	$on = spokares_document_list_soon();
	$n  = spokares_document_soon_count();
	if ( $on && isset( $views['all'] ) ) {
		$views['all'] = str_replace( array( ' class="current"', ' aria-current="page"' ), '', $views['all'] );
	}
	$trash = $views['trash'] ?? null;
	unset( $views['trash'] );
	if ( $n || $on ) {
		$views['spk_soon'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( admin_url( 'edit.php?post_type=spk_document&spk_source=soon' ) ),
			$on ? ' class="current" aria-current="page"' : '',
			esc_html__( 'Soon', 'spokares-core' ),
			$n
		);
	}
	if ( null !== $trash ) {
		$views['trash'] = $trash;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which list view; read-only.
	if ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) {
		// The list's one line of help, above the views and the table.
		echo '<p class="spk-cue">' . esc_html__( 'Click a name to change it, give it a new file or take it off the site.', 'spokares-core' ) . '</p>';
	}
	return $views;
}
add_filter( 'views_edit-spk_document', 'spokares_document_views' );

/**
 * The section dropdown above the list.
 *
 * @param string $post_type Post type.
 */
function spokares_document_filter_ui( $post_type ): void {
	if ( 'spk_document' !== $post_type ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- list filtering only.
	$current = isset( $_GET['spk_section'] ) ? sanitize_key( wp_unslash( $_GET['spk_section'] ) ) : '';
	if ( '' === $current && isset( $_GET['taxonomy'], $_GET['term'] ) && 'spk_doc_cat' === $_GET['taxonomy'] ) {
		// The Section column's links filter with taxonomy=…&term=…: show that section chosen.
		$current = sanitize_key( wp_unslash( $_GET['term'] ) );
	}
	if ( spokares_document_list_soon() ) {
		echo '<input type="hidden" name="spk_source" value="soon">';
	}
	// phpcs:enable
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
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which list view; read-only.
	$in_trash = isset( $_GET['post_status'] ) && 'trash' === $_GET['post_status'];
	if ( ! $in_trash ) {
		// The Trash view keeps only Restore and Delete permanently.
		$actions['spk_mark_reviewed'] = __( 'Mark reviewed today', 'spokares-core' );
	}
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
 * here: a document is taken off the site from its own form.
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
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=spokares_pull_file&post=' . $post->ID ), 'spokares_pull_file_' . $post->ID );
		$ask = sprintf(
			/* translators: %s: document name. */
			__( 'Delete the file of “%s” from the web now? This can’t be undone: the document becomes a “Soon” draft until a file is uploaded again.', 'spokares-core' ),
			html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' )
		);
		$out['pull'] = '<a class="spk-danger" data-spk-ask="' . esc_attr( $ask ) . '" href="' . esc_url( $url ) . '">' . esc_html__( 'Pull this file now', 'spokares-core' ) . '</a>';
	}
	return $out;
}
add_filter( 'post_row_actions', 'spokares_document_row_actions', 20, 2 );

/* -------------------------------------------------------- library sections */

/**
 * Documents filed in a section (any status but the Trash).
 *
 * @param int $term_id Section.
 */
function spokares_section_document_count( int $term_id ): int {
	$q = new WP_Query(
		array(
			'post_type'      => 'spk_document',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one small library, on the Sections screen only.
			'tax_query'      => array(
				array(
					'taxonomy' => 'spk_doc_cat',
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		)
	);
	return (int) $q->found_posts;
}

/**
 * The next free position (after the last section), so a new section goes
 * to the end of the library instead of the top.
 */
function spokares_section_next_order(): int {
	$max   = 0;
	$terms = get_terms(
		array(
			'taxonomy'   => 'spk_doc_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	foreach ( is_array( $terms ) ? $terms : array() as $id ) {
		$max = max( $max, (int) get_term_meta( (int) $id, 'spk_order', true ) );
	}
	return $max + 1;
}

/**
 * Sections › Add: the Position field.
 */
function spokares_section_add_fields(): void {
	?>
	<div class="form-field">
		<label for="spk-order"><?php esc_html_e( 'Position', 'spokares-core' ); ?></label>
		<input type="number" name="spk_order" id="spk-order" min="0" step="1" value="" placeholder="<?php esc_attr_e( 'last', 'spokares-core' ); ?>">
		<p><?php esc_html_e( 'Where the section sits on Documents & forms (lower shows first). Empty: after the last section.', 'spokares-core' ); ?></p>
	</div>
	<?php
}
add_action( 'spk_doc_cat_add_form_fields', 'spokares_section_add_fields' );

/**
 * Sections › Edit: the Position field, and what changing the slug does.
 *
 * @param WP_Term $term Section.
 */
function spokares_section_edit_fields( $term ): void {
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	?>
	<tr class="form-field">
		<th scope="row"><label for="spk-order"><?php esc_html_e( 'Position', 'spokares-core' ); ?></label></th>
		<td><input type="number" name="spk_order" id="spk-order" min="0" step="1" value="<?php echo esc_attr( (string) (int) get_term_meta( $term->term_id, 'spk_order', true ) ); ?>">
			<p class="description"><?php esc_html_e( 'Where the section sits on Documents & forms (lower shows first).', 'spokares-core' ); ?></p></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><?php esc_html_e( 'Before you change the slug', 'spokares-core' ); ?></th>
		<td><p class="description spk-flag">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: the section's slug, e.g. "training". */
					__( 'The slug is the section’s link on Documents & forms (#%s). Pages link to it (for example About), so changing it breaks those links.', 'spokares-core' ),
					$term->slug
				)
			);
			?>
		</p></td>
	</tr>
	<?php
}
add_action( 'spk_doc_cat_edit_form_fields', 'spokares_section_edit_fields' );

/**
 * Save the Position (core checked the form's nonce and manage_terms).
 *
 * @param int $term_id Section.
 */
function spokares_section_save( $term_id ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the Add/Edit term forms' own nonces are checked by core before these hooks.
	$typed = isset( $_POST['spk_order'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['spk_order'] ) ) ) : null;
	if ( null === $typed ) {
		return; // Not our form (Quick Edit is off; a term made in code keeps its own).
	}
	$order = '' === $typed ? ( 'created_spk_doc_cat' === current_action() ? spokares_section_next_order() : 0 ) : absint( $typed );
	update_term_meta( (int) $term_id, 'spk_order', $order );
}
add_action( 'created_spk_doc_cat', 'spokares_section_save' );
add_action( 'edited_spk_doc_cat', 'spokares_section_save' );

/**
 * Sections list: a Position column.
 *
 * @param array $cols Columns.
 */
function spokares_section_columns( $cols ): array {
	$cols              = is_array( $cols ) ? $cols : array();
	$cols['spk_order'] = __( 'Position', 'spokares-core' );
	return $cols;
}
add_filter( 'manage_edit-spk_doc_cat_columns', 'spokares_section_columns' );

/**
 * Sections list: the Position value.
 *
 * @param string $out     Output.
 * @param string $column  Column.
 * @param int    $term_id Section.
 */
function spokares_section_column( $out, $column, $term_id ) {
	if ( 'spk_order' === $column ) {
		return esc_html( (string) (int) get_term_meta( (int) $term_id, 'spk_order', true ) );
	}
	return $out;
}
add_filter( 'manage_spk_doc_cat_custom_column', 'spokares_section_column', 10, 3 );

/**
 * Sections list: no Quick Edit (it changes the slug with no warning), and no
 * Delete for a section that still holds documents.
 *
 * @param array   $actions Row actions.
 * @param WP_Term $term    Section.
 */
function spokares_section_row_actions( $actions, $term ) {
	unset( $actions['inline hide-if-no-js'] );
	if ( $term instanceof WP_Term && isset( $actions['delete'] ) && spokares_section_document_count( (int) $term->term_id ) ) {
		$actions['delete'] = '<span class="description">' . esc_html__( 'Move its documents to another section to delete it', 'spokares-core' ) . '</span>';
	}
	return $actions;
}
add_filter( 'spk_doc_cat_row_actions', 'spokares_section_row_actions', 10, 2 );

/**
 * Never delete a section that still holds documents: they would stay
 * published but drop out of the library without a word.
 *
 * @param int    $term_id  Term.
 * @param string $taxonomy Taxonomy.
 */
function spokares_section_delete_guard( $term_id, $taxonomy ): void {
	if ( 'spk_doc_cat' !== $taxonomy ) {
		return;
	}
	$n = spokares_section_document_count( (int) $term_id );
	if ( $n ) {
		wp_die(
			esc_html(
				sprintf(
					/* translators: %d: number of documents. */
					_n( 'This section still holds %d document. Move it to another section first, then delete the section.', 'This section still holds %d documents. Move them to another section first, then delete the section.', $n, 'spokares-core' ),
					$n
				)
			),
			esc_html__( 'Section not deleted', 'spokares-core' ),
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}
}
add_action( 'pre_delete_term', 'spokares_section_delete_guard', 10, 2 );
