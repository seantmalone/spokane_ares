<?php
/**
 * Regression tests for QA-011 (PLAN §4.1: "no editor can delete a file";
 * §5.5 deletion guard). The core Editor role has edit_others_posts and
 * delete_others_posts, and attachments use the post capabilities, so a core
 * Editor could open Edit Media for the Home hero photo (uploaded by the
 * administrator), crop or rotate it in place (the Home hero then loses its
 * srcset, sizes and fetchpriority), change its alt text over REST, and
 * delete any other user's unused file (REST DELETE /wp/v2/media/N). For the
 * in-use hero the deletion guard stops the delete, but only as a bare 500
 * after the permission check has already said yes.
 *
 * Correct behaviour, as for the ARES Editor: a non-administrator cannot edit
 * or delete another user's Media Library file (the hero included); REST and
 * the image editor's Ajax handler refuse with a permission error; an
 * administrator still can; and editors can still edit their own new uploads
 * (alt text in the block editor's media window).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The non-administrator role keys.
 */
function qa_011_non_admin_roles(): array {
	return array( 'subscriber', 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net' );
}

/**
 * The Home hero photo's attachment ID (found by _spk_seed = hero, as page
 * setup does), checked to be in use on Home and not uploaded by the core
 * Editor.
 */
function qa_011_hero_id(): int {
	$ids = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'fields'      => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one lookup in a dev test.
			'meta_key'    => '_spk_seed',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one lookup in a dev test.
			'meta_value'  => 'hero',
		)
	);
	if ( ! $ids ) {
		fail( 'no attachment with _spk_seed = hero (page setup did not run?)' );
	}
	$id = (int) $ids[0];
	assert_true( (bool) \spokares_attachment_uses( $id ), 'the hero photo is in use on a page' );
	assert_not_same( user_id( 'core-editor' ), (int) get_post_field( 'post_author', $id ), 'the hero was not uploaded by the core Editor' );
	return $id;
}

/**
 * Upload a 1x1 PNG as the current user (PNG, so non-administrators may
 * upload it too) and return array( attachment ID, file path ). The framework
 * deletes attachments a test adds.
 *
 * @param string $name File name.
 */
function qa_011_upload( string $name ): array {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- a fixed 1x1 PNG.
	$up = wp_upload_bits( $name, null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=' ) );
	if ( ! empty( $up['error'] ) ) {
		fail( 'wp_upload_bits: ' . $up['error'] );
	}
	$att = wp_insert_attachment(
		array(
			'post_title'     => 'QA-011 test file',
			'post_mime_type' => 'image/png',
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

test(
	'no non-administrator can edit or delete the Home hero photo (edit_post, delete_post)',
	function () {
		$hero = qa_011_hero_id();
		foreach ( qa_011_non_admin_roles() as $role ) {
			as_role( $role );
			assert_false( current_user_can( 'edit_post', $hero ), $role . ' edit_post on the hero' );
			assert_false( current_user_can( 'delete_post', $hero ), $role . ' delete_post on the hero' );
		}
	}
);

test(
	'no non-administrator can edit or delete another user\'s unused file (edit_post, delete_post)',
	function () {
		as_role( 'admin' );
		list( $att ) = qa_011_upload( 'qa-011-admin-unused.png' );
		assert_same( array(), \spokares_attachment_uses( $att ), 'the file is unused' );
		foreach ( qa_011_non_admin_roles() as $role ) {
			as_role( $role );
			assert_false( current_user_can( 'edit_post', $att ), $role . ' edit_post on the admin\'s file' );
			assert_false( current_user_can( 'delete_post', $att ), $role . ' delete_post on the admin\'s file' );
		}
	}
);

test(
	'REST: the core Editor cannot delete another user\'s unused file (403, file kept), like the ARES Editor',
	function () {
		as_role( 'admin' );
		list( $att, $file ) = qa_011_upload( 'qa-011-admin-delete.png' );
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$res = rest( 'DELETE', '/wp/v2/media/' . $att, array( 'force' => true ) );
			expect_wp_error( $res, 'rest_cannot_delete', 403, $role . ': delete refused' );
			clean_post_cache( $att );
			assert_same( 'attachment', get_post_type( $att ), $role . ': the attachment is still there' );
			assert_true( file_exists( $file ), $role . ': the file is still on disk' );
		}
	}
);

test(
	'REST: the core Editor\'s delete of the in-use hero photo is a 403 permission refusal, not a 500',
	function () {
		$hero = qa_011_hero_id();
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$res = rest( 'DELETE', '/wp/v2/media/' . $hero, array( 'force' => true ) );
			expect_wp_error( $res, 'rest_cannot_delete', 403, $role . ': hero delete refused' );
			clean_post_cache( $hero );
			assert_same( 'attachment', get_post_type( $hero ), $role . ': the hero is still there' );
		}
	}
);

test(
	'REST: the core Editor cannot change the hero photo\'s details (POST /wp/v2/media/<hero>)',
	function () {
		$hero = qa_011_hero_id();
		$alt  = (string) get_post_meta( $hero, '_wp_attachment_image_alt', true );
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$res = rest(
				'POST',
				'/wp/v2/media/' . $hero,
				array(
					'alt_text' => 'QA-011 changed',
					'title'    => 'QA-011 changed',
				)
			);
			expect_wp_error( $res, 'rest_cannot_edit', 403, $role . ': edit refused' );
			wp_cache_delete( $hero, 'post_meta' );
			assert_same( $alt, (string) get_post_meta( $hero, '_wp_attachment_image_alt', true ), $role . ': alt text unchanged' );
		}
	}
);

test(
	'REST: the core Editor cannot rotate or crop the hero photo (POST /wp/v2/media/<hero>/edit)',
	function () {
		$hero = qa_011_hero_id();
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$res = rest(
				'POST',
				'/wp/v2/media/' . $hero . '/edit',
				array(
					'src'       => (string) wp_get_attachment_url( $hero ),
					'modifiers' => array(
						array(
							'type' => 'rotate',
							'args' => array( 'angle' => 90 ),
						),
					),
				)
			);
			expect_wp_error( $res, 'rest_cannot_edit', 403, $role . ': image edit refused' );
		}
	}
);

test(
	'Ajax image editor (Edit Image on the Edit Media screen): the core Editor cannot open the hero photo',
	function () {
		require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
		$hero = qa_011_hero_id();
		add_filter( 'wp_doing_ajax', '__return_true' );
		try {
			foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
				as_role( $role );
				$r = call_request(
					'POST',
					array(),
					array(
						'action'      => 'image-editor',
						'postid'      => $hero,
						'do'          => 'open',
						'_ajax_nonce' => wp_create_nonce( 'image_editor-' . $hero ),
					),
					static function () {
						\wp_ajax_image_editor();
					}
				);
				assert_not_contains( '"success":true', $r['output'], $role . ': the image editor opened' );
				assert_same( '-1', $r['die'], $role . ': refused by the edit_post check' );
			}
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
		}
	}
);

test(
	'an administrator can still edit and delete another user\'s file and edit the hero',
	function () {
		as_role( 'ares-editor' );
		list( $att, $file ) = qa_011_upload( 'qa-011-editor-upload.png' );
		$hero               = qa_011_hero_id();
		as_role( 'admin' );
		assert_true( current_user_can( 'edit_post', $hero ), 'admin edit_post on the hero' );
		assert_true( current_user_can( 'edit_post', $att ), 'admin edit_post on the ARES Editor\'s file' );
		assert_true( current_user_can( 'delete_post', $att ), 'admin delete_post on the ARES Editor\'s file' );
		expect_not_wp_error( rest( 'DELETE', '/wp/v2/media/' . $att, array( 'force' => true ) ), 'admin REST delete' );
		clean_post_cache( $att );
		assert_same( null, get_post( $att ), 'the attachment is gone' );
		assert_false( file_exists( $file ), 'the file is gone' );
	}
);

test(
	'editors can still edit their own new upload (alt text in the media window)',
	function () {
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			list( $att ) = qa_011_upload( 'qa-011-own-' . $role . '.png' );
			assert_true( current_user_can( 'edit_post', $att ), $role . ' edit_post on its own upload' );
		}
	}
);
