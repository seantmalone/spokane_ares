<?php
/**
 * Document files (§5.5): where an attachment is used, removing a document's
 * old file from the web, setting the file of a document taken off the site
 * aside outside the web root (and back on Restore), a file waiting after a
 * refused save (D8), and the deletion guard.
 * Loaded everywhere (REST and WP-CLI too), so nothing deletes a file a
 * document or page still uses except Replace, Trash and Pull, which clear
 * the reference first. A refused delete
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
		// Set aside when its document was taken off the site, but something
		// uses it now: it belongs on the web.
		spokares_release_attachment( $att_id );
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

/* ------------------------------------------------- held files (§5.5, D6) */

/**
 * The folder where "Take it off the site" sets a document's file aside:
 * SPOKARES_HELD_DIR, or spokares-held next to the WordPress folder (on the
 * live host ~/spokares-held, beside public_html). It must be outside the web
 * root: the host runs OpenLiteSpeed, which ignores .htaccess (PLAN §8), so a
 * deny rule inside public_html would not keep the file private.
 */
function spokares_held_dir(): string {
	$dir = defined( 'SPOKARES_HELD_DIR' ) ? (string) SPOKARES_HELD_DIR : trailingslashit( dirname( untrailingslashit( wp_normalize_path( ABSPATH ) ) ) ) . 'spokares-held';
	/**
	 * Filters the held folder (the dev tests point it somewhere unusable).
	 *
	 * @param string $dir Folder.
	 */
	$dir = (string) apply_filters( 'spokares_held_dir', $dir );
	return '' === $dir ? '' : untrailingslashit( wp_normalize_path( $dir ) );
}

/**
 * The held folder, made if need be (for the site's user only), or '' when it
 * can't be used: it can't be made or written to, or it is inside the
 * WordPress folder, the uploads folder or the web server's document root,
 * where the web server could serve it.
 */
function spokares_held_dir_ready(): string {
	$dir = spokares_held_dir();
	if ( '' === $dir ) {
		return '';
	}
	// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions -- a private folder outside the web root; WP_Filesystem would ask for FTP details.
	if ( ! is_dir( $dir ) && ! @mkdir( $dir, 0700, true ) ) {
		return '';
	}
	// phpcs:enable
	$real = realpath( $dir );
	if ( false === $real || ! wp_is_writable( $real ) ) {
		return '';
	}
	$real = trailingslashit( wp_normalize_path( $real ) );
	$web  = array( ABSPATH, wp_upload_dir( null, false )['basedir'] );
	if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
		$web[] = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
	}
	foreach ( $web as $root ) {
		$root = '' !== (string) $root ? realpath( (string) $root ) : false;
		if ( false !== $root && str_starts_with( $real, trailingslashit( wp_normalize_path( $root ) ) ) ) {
			return ''; // The web server could serve it.
		}
	}
	return untrailingslashit( $real );
}

/**
 * Has this attachment's file been set aside (its document is in the Trash)?
 * The attachment keeps its own path; the file isn't there.
 *
 * @param int $att_id Attachment.
 */
function spokares_attachment_is_held( int $att_id ): bool {
	return $att_id && is_array( get_post_meta( $att_id, '_spk_held', true ) );
}

/**
 * An attachment's folder and its files' names: the file, its image sizes
 * (a PDF's preview too), the original of a scaled image and any copies from
 * image editing.
 *
 * @param int $att_id Attachment.
 * @return array{0:string,1:string[]} Folder ('' when it has no file) and names.
 */
function spokares_attachment_files( int $att_id ): array {
	$file = (string) get_attached_file( $att_id, true );
	if ( '' === $file ) {
		return array( '', array() );
	}
	$names = array( wp_basename( $file ) );
	$meta  = wp_get_attachment_metadata( $att_id, true );
	if ( is_array( $meta ) ) {
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
			if ( is_array( $size ) && ! empty( $size['file'] ) ) {
				$names[] = wp_basename( (string) $size['file'] );
			}
		}
		if ( ! empty( $meta['original_image'] ) ) {
			$names[] = wp_basename( (string) $meta['original_image'] );
		}
	}
	$backup = get_post_meta( $att_id, '_wp_attachment_backup_sizes', true );
	foreach ( is_array( $backup ) ? $backup : array() as $size ) {
		if ( is_array( $size ) && ! empty( $size['file'] ) ) {
			$names[] = wp_basename( (string) $size['file'] );
		}
	}
	return array( dirname( $file ), array_values( array_unique( $names ) ) );
}

/**
 * Move a file: rename, or copy and delete (the two folders can be on
 * different disks).
 *
 * @param string $from From.
 * @param string $to   To.
 */
function spokares_move_file( string $from, string $to ): bool {
	// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions -- moving an upload to or from the private held folder.
	if ( @rename( $from, $to ) ) {
		return true;
	}
	if ( @copy( $from, $to ) ) {
		if ( @unlink( $from ) ) {
			return true;
		}
		@unlink( $to );
	}
	// phpcs:enable
	return false;
}

/**
 * Set an attachment's files aside in the held folder, where the web server
 * can't serve them. False (and nothing moved) when the folder can't be used
 * or the file isn't there.
 *
 * @param int $att_id Attachment.
 */
function spokares_hold_attachment( int $att_id ): bool {
	if ( spokares_attachment_is_held( $att_id ) ) {
		return true;
	}
	list( $dir, $names ) = spokares_attachment_files( $att_id );
	$base                = spokares_held_dir_ready();
	if ( '' === $dir || '' === $base || ! is_file( $dir . '/' . $names[0] ) ) {
		return false;
	}
	$to = $base . '/' . $att_id . '-' . bin2hex( random_bytes( 4 ) );
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- private folder outside the web root.
	if ( ! @mkdir( $to, 0700 ) ) {
		return false;
	}
	$moved = array();
	foreach ( $names as $name ) {
		if ( ! is_file( $dir . '/' . $name ) ) {
			continue; // A size that was never made.
		}
		if ( ! spokares_move_file( $dir . '/' . $name, $to . '/' . $name ) ) {
			foreach ( $moved as $back ) {
				spokares_move_file( $to . '/' . $back, $dir . '/' . $back );
			}
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- the empty folder just made.
			@rmdir( $to );
			return false;
		}
		$moved[] = $name;
	}
	update_post_meta(
		$att_id,
		'_spk_held',
		array(
			'dir'   => $to,
			'files' => $moved,
		)
	);
	return true;
}

/**
 * Put a held attachment's files back where they were. True when nothing is
 * held any more; false when a file couldn't go back (it stays held, and its
 * document has no file on the web).
 *
 * @param int $att_id Attachment.
 */
function spokares_release_attachment( int $att_id ): bool {
	$held = get_post_meta( $att_id, '_spk_held', true );
	if ( ! is_array( $held ) ) {
		return true;
	}
	list( $dir ) = spokares_attachment_files( $att_id );
	$from        = (string) ( $held['dir'] ?? '' );
	$names       = array_map( 'wp_basename', array_map( 'strval', (array) ( $held['files'] ?? array() ) ) );
	if ( '' === $dir || '' === $from || ! wp_mkdir_p( $dir ) ) {
		return false;
	}
	foreach ( $names as $name ) {
		if ( file_exists( $dir . '/' . $name ) ) {
			return false; // Something else is at its old address now.
		}
	}
	$left = array();
	foreach ( $names as $name ) {
		if ( is_file( $from . '/' . $name ) && ! spokares_move_file( $from . '/' . $name, $dir . '/' . $name ) ) {
			$left[] = $name;
		}
	}
	if ( $left ) {
		$held['files'] = $left;
		update_post_meta( $att_id, '_spk_held', $held );
		return false;
	}
	delete_post_meta( $att_id, '_spk_held' );
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- the attachment's own, now empty, held folder.
	@rmdir( $from );
	return true;
}

/**
 * An attachment deleted for good takes its held files with it (WordPress
 * deletes only the files at the attachment's own address).
 *
 * @param int $att_id Attachment.
 */
function spokares_delete_held_files( $att_id ): void {
	$held = get_post_meta( (int) $att_id, '_spk_held', true );
	if ( ! is_array( $held ) || empty( $held['dir'] ) ) {
		return;
	}
	$dir = (string) $held['dir'];
	// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions -- the attachment's own held folder.
	foreach ( (array) ( $held['files'] ?? array() ) as $name ) {
		$path = $dir . '/' . wp_basename( (string) $name );
		if ( is_file( $path ) ) {
			@unlink( $path );
		}
	}
	@rmdir( $dir );
	// phpcs:enable
}
add_action( 'delete_attachment', 'spokares_delete_held_files' );

/**
 * Restore and Undo of a document put its held file back first, so it comes
 * back whole, as it was (spokares_document_untrash_status() then sees the
 * file). Runs before the status is chosen, wherever the restore comes from.
 *
 * @param int $post_id Post.
 */
function spokares_document_untrash_file( $post_id ): void {
	if ( 'spk_document' !== get_post_type( (int) $post_id ) ) {
		return;
	}
	$att = absint( get_post_meta( (int) $post_id, 'spk_file', true ) );
	if ( $att && spokares_attachment_is_held( $att ) ) {
		spokares_release_attachment( $att );
	}
}
add_action( 'untrash_post', 'spokares_document_untrash_file' );

/* -------------------------------------------- a file waiting (D8, §5.5) */

/**
 * How long a file stays waiting for its document's next save, in hours.
 */
function spokares_document_pending_hours(): int {
	return 24;
}

/**
 * The file waiting for a document (UX finding D8): a file that passed its own
 * checks (type, size, inspection, the "I checked" tick) on a save another box
 * refused. It is uploaded, set aside, and not the document's file: the next
 * save that goes through uses it, so the editor doesn't choose it again.
 * Null when there is none, or it has expired or gone.
 *
 * @param int $post_id Document.
 * @return array{id:int,name:string,expires:int,by:int}|null
 */
function spokares_document_pending( int $post_id ): ?array {
	$p   = get_post_meta( $post_id, 'spk_pending_file', true );
	$att = is_array( $p ) ? absint( $p['id'] ?? 0 ) : 0;
	if ( ! $att || (int) ( $p['expires'] ?? 0 ) < time() || 'attachment' !== get_post_type( $att ) ) {
		return null;
	}
	return array(
		'id'      => $att,
		'name'    => (string) ( $p['name'] ?? '' ),
		'expires' => (int) $p['expires'],
		'by'      => absint( $p['by'] ?? 0 ),
	);
}

/**
 * Keep an uploaded file waiting for a document, in place of any file that
 * was waiting. The file is set aside in the held folder while it waits
 * (off the web), when that folder can be used; otherwise it keeps its
 * random address, as a draft's file does.
 *
 * @param int    $post_id Document.
 * @param int    $att     The uploaded attachment.
 * @param string $name    The file's name as the editor knows it.
 */
function spokares_document_set_pending( int $post_id, int $att, string $name ): void {
	spokares_document_drop_pending( $post_id, $att );
	spokares_hold_attachment( $att );
	update_post_meta( $att, '_spk_pending_for', $post_id );
	update_post_meta(
		$post_id,
		'spk_pending_file',
		array(
			'id'      => $att,
			'name'    => $name,
			'expires' => time() + spokares_document_pending_hours() * HOUR_IN_SECONDS,
			'by'      => get_current_user_id(),
		)
	);
}

/**
 * Use the waiting file: back on the web, no longer waiting. Returns its
 * attachment ID, or 0 when there is none or it couldn't be put back.
 *
 * @param int $post_id Document.
 */
function spokares_document_take_pending( int $post_id ): int {
	$p = spokares_document_pending( $post_id );
	if ( ! $p ) {
		return 0;
	}
	if ( spokares_attachment_is_held( $p['id'] ) && ! spokares_release_attachment( $p['id'] ) ) {
		return 0;
	}
	delete_post_meta( $post_id, 'spk_pending_file' );
	delete_post_meta( $p['id'], '_spk_pending_for' );
	return $p['id'];
}

/**
 * Forget a document's waiting file and delete it, unless a document or page
 * uses it (the deletion guard) or it is the document's own file.
 *
 * @param int $post_id Document.
 * @param int $keep    An attachment never to delete (the one about to wait).
 */
function spokares_document_drop_pending( int $post_id, int $keep = 0 ): void {
	$p = get_post_meta( $post_id, 'spk_pending_file', true );
	if ( '' === $p || false === $p ) {
		return;
	}
	delete_post_meta( $post_id, 'spk_pending_file' );
	$att = is_array( $p ) ? absint( $p['id'] ?? 0 ) : 0;
	spokares_delete_pending_attachment( $att, $keep );
}

/**
 * Delete a waiting file's attachment (its held files go with it,
 * spokares_delete_held_files()). Never one a document or page uses, nor the
 * one to keep.
 *
 * @param int $att  Attachment.
 * @param int $keep An attachment never to delete.
 */
function spokares_delete_pending_attachment( int $att, int $keep = 0 ): void {
	if ( ! $att || $att === $keep || 'attachment' !== get_post_type( $att ) ) {
		return;
	}
	if ( spokares_attachment_uses( $att ) ) {
		delete_post_meta( $att, '_spk_pending_for' ); // In use: it is no longer a waiting file.
		return;
	}
	wp_delete_attachment( $att, true );
}

/**
 * A document taken off the site or deleted for good takes its waiting file
 * with it.
 *
 * @param int $post_id Post.
 */
function spokares_document_pending_gone( $post_id ): void {
	if ( 'spk_document' === get_post_type( (int) $post_id ) ) {
		spokares_document_drop_pending( (int) $post_id );
	}
}
add_action( 'trashed_post', 'spokares_document_pending_gone' );
add_action( 'before_delete_post', 'spokares_document_pending_gone' );

/**
 * Delete waiting files nobody used: past their time, or no longer the file
 * their document waits for. Lazy, on an admin page load at most once an hour
 * (no cron job: PLAN keeps scheduled work to the host's own checks).
 *
 * @param bool $force Sweep now, whenever the last sweep was.
 */
function spokares_sweep_pending_files( bool $force = false ): int {
	if ( ! $force && get_transient( 'spokares_pending_sweep' ) ) {
		return 0;
	}
	set_transient( 'spokares_pending_sweep', 1, HOUR_IN_SECONDS );
	$atts = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'numberposts' => 50,
			'fields'      => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- at most once an hour, on a small library.
			'meta_key'    => '_spk_pending_for',
		)
	);
	$n    = 0;
	foreach ( $atts as $att ) {
		$att = (int) $att;
		$doc = absint( get_post_meta( $att, '_spk_pending_for', true ) );
		$p   = $doc ? get_post_meta( $doc, 'spk_pending_file', true ) : '';
		$own = is_array( $p ) && absint( $p['id'] ?? 0 ) === $att;
		if ( $own && (int) ( $p['expires'] ?? 0 ) >= time() && 'spk_document' === get_post_type( $doc ) ) {
			continue; // Still waiting.
		}
		if ( $own ) {
			delete_post_meta( $doc, 'spk_pending_file' );
		}
		spokares_delete_pending_attachment( $att );
		++$n;
	}
	return $n;
}

/**
 * The sweep, on admin page loads.
 */
function spokares_sweep_pending_files_on_admin(): void {
	spokares_sweep_pending_files();
}
add_action( 'admin_init', 'spokares_sweep_pending_files_on_admin' );

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
