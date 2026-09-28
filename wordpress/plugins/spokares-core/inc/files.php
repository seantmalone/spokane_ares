<?php
/**
 * Document files (§5.5): where an attachment is used, removing a document's
 * old file from the web, and the deletion guard. Loaded everywhere (REST and
 * WP-CLI too), so nothing deletes a file a document or page still uses except
 * Replace, Trash and Pull, which clear the reference first. A refused delete
 * says where the file is used. The files of documents a user can't edit
 * (drafts, above all) are left out of the media window's routes for them.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where else is an attachment used? Documents (spk_file) and pages
 * (wp-image-N or its URL in the content). Returns titles. A document or page
 * in the Trash counts: it can be restored, and it would come back pointing
 * at a deleted file (WP_Query's 'any' leaves trashed posts out).
 *
 * @param int $att_id   Attachment.
 * @param int $except   A document to ignore.
 */
function spokares_attachment_uses( int $att_id, int $except = 0 ): array {
	$uses = array();
	$docs = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => array( 'any', 'trash' ),
			'numberposts' => 20,
			'fields'      => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small library; runs only when a file is removed.
			'meta_query'  => array(
				array(
					'key'   => 'spk_file',
					'value' => (string) $att_id,
				),
			),
		)
	);
	foreach ( $docs as $id ) {
		if ( (int) $id !== $except ) {
			$uses[] = get_the_title( (int) $id );
		}
	}
	$url   = (string) wp_get_attachment_url( $att_id );
	$pages = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => array( 'any', 'trash' ),
			'numberposts' => 50,
		)
	);
	foreach ( $pages as $page ) {
		if ( str_contains( $page->post_content, 'wp-image-' . $att_id . '"' ) || str_contains( $page->post_content, 'wp-image-' . $att_id . ' ' ) || str_contains( $page->post_content, '"id":' . $att_id . ',' ) || str_contains( $page->post_content, '"id":' . $att_id . '}' ) || ( '' !== $url && str_contains( $page->post_content, $url ) ) ) {
			$uses[] = get_the_title( $page );
		}
	}
	return $uses;
}

/**
 * Remove a document's old file from the web, unless something else uses it.
 * Returns a sentence for the notice ('' when removed quietly).
 *
 * @param int $att_id Attachment.
 * @param int $doc_id The document it belonged to.
 */
function spokares_remove_document_file( int $att_id, int $doc_id ): string {
	if ( ! $att_id || 'attachment' !== get_post_type( $att_id ) ) {
		return '';
	}
	$uses = spokares_attachment_uses( $att_id, $doc_id );
	if ( $uses ) {
		return sprintf(
			/* translators: %s: where the file is used. */
			__( 'The old file is still used by %s, so it stays on the web.', 'spokares-core' ),
			spokares_and_list( array_map( static fn( $t ) => html_entity_decode( (string) $t, ENT_QUOTES, 'UTF-8' ), $uses ) )
		);
	}
	if ( absint( get_post_meta( $doc_id, 'spk_file', true ) ) === $att_id ) {
		delete_post_meta( $doc_id, 'spk_file' );
	}
	$GLOBALS['spokares_delete_ok'][ $att_id ] = true;
	wp_delete_attachment( $att_id, true );
	unset( $GLOBALS['spokares_delete_ok'][ $att_id ] );
	return '';
}

/**
 * A document deleted for good (Empty Trash, Delete Permanently, or WordPress's
 * own clean-up 30 days after it was trashed) takes its file off the web,
 * unless another document or a page uses it. Otherwise WordPress would keep
 * the file as an unattached upload, reachable by its number.
 *
 * @param int     $post_id Post being deleted.
 * @param WP_Post $post    Post.
 */
function spokares_document_deleted( $post_id, $post = null ): void {
	$post = $post instanceof WP_Post ? $post : get_post( (int) $post_id );
	if ( ! $post || 'spk_document' !== $post->post_type ) {
		return;
	}
	$att = absint( get_post_meta( $post->ID, 'spk_file', true ) );
	if ( $att ) {
		spokares_remove_document_file( $att, (int) $post->ID );
	}
}
add_action( 'before_delete_post', 'spokares_document_deleted', 10, 2 );

/**
 * Deletion guard: refuse to delete a file a document or page still uses,
 * except through Replace, Trash and Pull (which clear the reference first).
 *
 * @param WP_Post|false|null $delete Short-circuit value.
 * @param WP_Post            $post   Attachment.
 */
function spokares_guard_attachment_delete( $delete, $post ) {
	if ( null !== $delete || ! $post instanceof WP_Post ) {
		return $delete;
	}
	if ( ! empty( $GLOBALS['spokares_delete_ok'][ $post->ID ] ) ) {
		return $delete;
	}
	return spokares_attachment_uses( (int) $post->ID ) ? false : $delete;
}
add_filter( 'pre_delete_attachment', 'spokares_guard_attachment_delete', 10, 2 );

/**
 * A refusal that says where a file is used, for the Media Library's delete
 * links (the deletion guard alone ends in core's bare "Error in deleting the
 * attachment." with HTTP 500).
 *
 * @param int[] $att_ids Attachments about to be deleted.
 */
function spokares_refuse_in_use_delete( array $att_ids ): void {
	foreach ( $att_ids as $att_id ) {
		$att_id = (int) $att_id;
		if ( 'attachment' !== get_post_type( $att_id ) || ! current_user_can( 'delete_post', $att_id ) ) {
			continue; // Core refuses these itself.
		}
		$uses = spokares_attachment_uses( $att_id );
		if ( ! $uses ) {
			continue;
		}
		$where = spokares_and_list( array_map( static fn( $t ) => html_entity_decode( (string) $t, ENT_QUOTES, 'UTF-8' ), $uses ) );
		wp_die(
			'<p>' . esc_html(
				sprintf(
					/* translators: 1: file title, 2: where it is used. */
					__( '“%1$s” wasn’t deleted: %2$s still uses it. Replace or remove it there first, then delete it.', 'spokares-core' ),
					html_entity_decode( get_the_title( $att_id ), ENT_QUOTES, 'UTF-8' ),
					$where
				)
			) . '</p><p><a href="' . esc_url( admin_url( 'upload.php' ) ) . '">' . esc_html__( 'Back to the Media Library', 'spokares-core' ) . '</a></p>',
			esc_html__( 'File in use', 'spokares-core' ),
			array( 'response' => 409 )
		);
	}
}

/**
 * Media Library: Delete Permanently on the Edit Media screen or a list row
 * (post.php?action=delete), and the list's bulk Delete (upload.php).
 * Checked before core's own handler, which checks the nonce itself.
 */
function spokares_media_delete_precheck(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only check before core verifies the nonce; this only refuses.
	global $pagenow;
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	if ( 'post.php' === $pagenow && 'delete' === $action && isset( $_GET['post'] ) ) {
		spokares_refuse_in_use_delete( array( absint( $_GET['post'] ) ) );
	} elseif ( 'upload.php' === $pagenow && ( 'delete' === $action || ( isset( $_GET['action2'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action2'] ) ) ) ) && isset( $_GET['media'] ) ) {
		spokares_refuse_in_use_delete( array_map( 'absint', (array) wp_unslash( $_GET['media'] ) ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}
add_action( 'load-post.php', 'spokares_media_delete_precheck', 0 );
add_action( 'load-upload.php', 'spokares_media_delete_precheck', 0 );

/**
 * REST DELETE /wp/v2/media/N of a file in use: a 409 that says where, not
 * core's 500 "The post cannot be deleted.". Only once the user may delete
 * the file; otherwise core's own 403 permission refusal stands.
 *
 * @param mixed           $response Response so far (null to go on).
 * @param array           $handler  Route handler.
 * @param WP_REST_Request $request  Request.
 */
function spokares_rest_media_delete_precheck( $response, $handler, $request ) {
	unset( $handler );
	if ( null !== $response || ! $request instanceof WP_REST_Request || 'DELETE' !== $request->get_method() ) {
		return $response;
	}
	if ( ! preg_match( '#^/wp/v2/media/(\d+)$#', (string) $request->get_route(), $m ) ) {
		return $response;
	}
	$att_id = (int) $m[1];
	if ( 'attachment' !== get_post_type( $att_id ) || ! current_user_can( 'delete_post', $att_id ) ) {
		return $response;
	}
	$uses = spokares_attachment_uses( $att_id );
	if ( ! $uses ) {
		return $response;
	}
	return new WP_Error(
		'spokares_file_in_use',
		sprintf(
			/* translators: %s: where the file is used. */
			__( 'This file wasn’t deleted: %s still uses it. Replace or remove it there first.', 'spokares-core' ),
			spokares_and_list( array_map( static fn( $t ) => html_entity_decode( (string) $t, ENT_QUOTES, 'UTF-8' ), $uses ) )
		),
		array( 'status' => 409 )
	);
}
add_filter( 'rest_request_before_callbacks', 'spokares_rest_media_delete_precheck', 10, 3 );

/**
 * The Media Library grid can only show one fixed message when a delete
 * fails; say why it usually does.
 *
 * @param array $strings Media window strings.
 */
function spokares_media_delete_error_text( $strings ) {
	if ( is_array( $strings ) ) {
		$strings['errorDeleting'] = __( 'This file wasn’t deleted: a page or a document still uses it. Replace or remove it there first, then delete it.', 'spokares-core' );
	}
	return $strings;
}
add_filter( 'media_view_strings', 'spokares_media_delete_error_text' );

/* ------------------------------------------- draft documents' files (§5.5) */

/**
 * The attachments of documents the current user can't edit: each one's
 * spk_file and whatever is attached to it. A draft's file has a random name
 * so its address can't be guessed; core's media window routes only check
 * upload_files, so an Author or a core Editor would otherwise be handed it.
 *
 * @return int[]
 */
function spokares_hidden_document_files(): array {
	if ( ! is_user_logged_in() ) {
		return array();
	}
	// get_posts() runs without query filters (suppress_filters defaults to true).
	$docs = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => array( 'any', 'trash' ),
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	$docs = array_values( array_filter( array_map( 'intval', $docs ), static fn( $id ) => ! current_user_can( 'edit_post', $id ) ) );
	if ( ! $docs ) {
		return array();
	}
	update_meta_cache( 'post', $docs );
	$ids = array();
	foreach ( $docs as $doc ) {
		$ids[] = absint( get_post_meta( $doc, 'spk_file', true ) );
	}
	$attached = get_posts(
		array(
			'post_type'       => 'attachment',
			'post_status'     => 'any',
			'post_parent__in' => $docs,
			'numberposts'     => -1,
			'fields'          => 'ids',
		)
	);
	return array_values( array_filter( array_unique( array_merge( $ids, array_map( 'intval', $attached ) ) ) ) );
}

/**
 * The media window's list (admin-ajax query-attachments) leaves those files
 * out, whichever query keys the request sends (post__in wins over
 * post__not_in in WP_Query, so it is trimmed too).
 *
 * @param array $query WP_Query arguments.
 */
function spokares_media_window_hide_document_files( $query ) {
	if ( ! is_array( $query ) ) {
		return $query;
	}
	$hidden = spokares_hidden_document_files();
	if ( ! $hidden ) {
		return $query;
	}
	if ( ! empty( $query['post__in'] ) ) {
		$in                = array_values( array_diff( wp_parse_id_list( $query['post__in'] ), $hidden ) );
		$query['post__in'] = $in ? $in : array( 0 );
	}
	$query['post__not_in'] = array_values( array_unique( array_merge( wp_parse_id_list( $query['post__not_in'] ?? array() ), $hidden ) ) );
	return $query;
}
add_filter( 'ajax_query_attachments_args', 'spokares_media_window_hide_document_files' );

/**
 * admin-ajax get-attachment&id=N refuses those files. Runs before core's
 * handler (priority 1), which takes the id from the request.
 */
function spokares_get_attachment_guard(): void {
	// phpcs:disable WordPress.Security.NonceVerification -- core's handler takes no nonce either; this only refuses.
	$id = 0;
	if ( isset( $_POST['id'] ) ) {
		$id = absint( $_POST['id'] );
	} elseif ( isset( $_GET['id'] ) ) {
		$id = absint( $_GET['id'] );
	}
	// phpcs:enable WordPress.Security.NonceVerification
	if ( $id && in_array( $id, spokares_hidden_document_files(), true ) ) {
		wp_send_json_error();
	}
}
add_action( 'wp_ajax_get-attachment', 'spokares_get_attachment_guard', 0 );

/**
 * The Media Library's list view (upload.php?mode=list) leaves those files
 * out too: it lists file names and a Copy URL action.
 *
 * @param WP_Query $query Query.
 */
function spokares_media_list_hide_document_files( $query ): void {
	global $pagenow;
	if ( ! is_admin() || 'upload.php' !== $pagenow || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
		return;
	}
	$hidden = spokares_hidden_document_files();
	if ( $hidden ) {
		$query->set( 'post__not_in', array_values( array_unique( array_merge( wp_parse_id_list( $query->get( 'post__not_in' ) ), $hidden ) ) ) );
	}
}
add_action( 'pre_get_posts', 'spokares_media_list_hide_document_files' );
