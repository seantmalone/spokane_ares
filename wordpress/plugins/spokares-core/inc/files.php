<?php
/**
 * Document files (§5.5): where an attachment is used, removing a document's
 * old file from the web, and the deletion guard. Loaded everywhere (REST and
 * WP-CLI too), so nothing deletes a file a document or page still uses except
 * Replace, Trash and Pull, which clear the reference first.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where else is an attachment used? Documents (spk_file) and pages
 * (wp-image-N or its URL in the content). Returns titles.
 *
 * @param int $att_id   Attachment.
 * @param int $except   A document to ignore.
 */
function spokares_attachment_uses( int $att_id, int $except = 0 ): array {
	$uses = array();
	$docs = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'any',
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
			'post_status' => 'any',
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
