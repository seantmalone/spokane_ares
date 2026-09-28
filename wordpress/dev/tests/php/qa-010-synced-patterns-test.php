<?php
/**
 * Regression tests for QA-010 (PLAN §4.1: only administrators create
 * content; §4.3 layer 6: no patterns for non-admins). Core registers the
 * synced-pattern type wp_block with its own capability map: create_posts is
 * publish_posts, edit_posts is edit_posts, edit_others_posts is
 * edit_others_posts and so on. The site remaps create_posts to
 * manage_options only for post and page (content-surface.php
 * spokares_hard_create_caps(), roles.php spokares_create_caps()), so:
 *
 * - the Author and the core Editor can POST /wp/v2/blocks and publish a
 *   pattern (with a phone number in it: the never-publish check runs only in
 *   the page editor), and it shows in an administrator's inserter under
 *   "My patterns";
 * - the Author keeps edit rights to it, and the core Editor can change any
 *   pattern, an administrator's too, so a synced pattern an administrator
 *   inserts into a page can later be changed around the layout lock;
 * - the Patterns list (edit.php?post_type=wp_block, gated by the type's
 *   edit_posts cap) opens for the Contributor, Author, core Editor and both
 *   ARES Editor accounts.
 *
 * Correct behaviour: no non-administrator can create, publish, list, edit or
 * delete a synced pattern; administrators still can.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Every signed-in role key except the administrator.
 */
function qa_010_non_admin_roles(): array {
	return array( 'subscriber', 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net' );
}

/**
 * Block markup with a phone number (something the site never publishes).
 *
 * @param string $text Paragraph text.
 */
function qa_010_markup( string $text ): string {
	return '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
}

/**
 * IDs of synced patterns with this title, in any status.
 *
 * @param string $title Title.
 */
function qa_010_patterns_titled( string $title ): array {
	$ids   = array();
	$found = get_posts(
		array(
			'post_type'        => 'wp_block',
			'post_status'      => 'any',
			'posts_per_page'   => 100,
			'suppress_filters' => true,
			'fields'           => 'ids',
		)
	);
	foreach ( $found as $id ) {
		if ( get_the_title( $id ) === $title ) {
			$ids[] = (int) $id;
		}
	}
	return $ids;
}

/**
 * A published synced pattern by the given account.
 *
 * @param string $author Role key of its author.
 */
function qa_010_pattern_by( string $author ): int {
	return create_post(
		array(
			'post_type'    => 'wp_block',
			'post_title'   => 'QA-010 pattern by ' . $author,
			'post_content' => qa_010_markup( 'Net control: KD7ABC' ),
			'post_author'  => user_id( $author ),
		)
	);
}

/**
 * The pattern's content, read fresh from the database.
 *
 * @param int $id Pattern ID.
 */
function qa_010_content( int $id ): string {
	clean_post_cache( $id );
	$post = get_post( $id );
	return $post ? (string) $post->post_content : '';
}

test(
	'no non-administrator has the synced-pattern caps that open the Patterns list, Add Pattern or Publish',
	function () {
		$type = get_post_type_object( 'wp_block' );
		assert_true( null !== $type, 'wp_block is registered' );
		foreach ( qa_010_non_admin_roles() as $role ) {
			as_role( $role );
			// edit.php?post_type=wp_block and post-new.php check edit_posts; REST create checks create_posts.
			assert_false( current_user_can( $type->cap->edit_posts ), $role . ': wp_block edit_posts (' . $type->cap->edit_posts . ', the Patterns list)' );
			assert_false( current_user_can( $type->cap->create_posts ), $role . ': wp_block create_posts (' . $type->cap->create_posts . ')' );
			assert_false( current_user_can( $type->cap->publish_posts ), $role . ': wp_block publish_posts (' . $type->cap->publish_posts . ')' );
			assert_false( current_user_can( $type->cap->edit_others_posts ), $role . ': wp_block edit_others_posts (' . $type->cap->edit_others_posts . ')' );
		}
	}
);

test(
	'REST: no non-administrator can create a synced pattern (POST /wp/v2/blocks, published or draft)',
	function () {
		foreach ( array( 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net' ) as $role ) {
			foreach ( array( 'publish', 'draft' ) as $status ) {
				$title = 'QA-010 ' . $role . ' ' . $status;
				as_role( $role );
				$res = rest(
					'POST',
					'/wp/v2/blocks',
					array(
						'title'   => $title,
						'status'  => $status,
						'content' => qa_010_markup( 'Call 509-555-0100' ),
					)
				);
				expect_wp_error( $res, '', 403, $role . ' ' . $status . ': create refused' );
				assert_count( 0, qa_010_patterns_titled( $title ), $role . ' ' . $status . ': no wp_block post was saved' );
			}
		}
	}
);

test(
	'non-administrators cannot change or delete a synced pattern an administrator made (edit_post, delete_post, REST)',
	function () {
		as_role( 'admin' );
		$id       = qa_010_pattern_by( 'admin' );
		$original = qa_010_content( $id );
		foreach ( qa_010_non_admin_roles() as $role ) {
			as_role( $role );
			assert_false( current_user_can( 'edit_post', $id ), $role . ': edit_post on the admin pattern' );
			assert_false( current_user_can( 'delete_post', $id ), $role . ': delete_post on the admin pattern' );
		}
		foreach ( array( 'author', 'core-editor', 'ares-editor' ) as $role ) {
			as_role( $role );
			$res = rest( 'POST', '/wp/v2/blocks/' . $id, array( 'content' => qa_010_markup( 'Changed by ' . $role . ': 509-555-0199' ) ) );
			expect_wp_error( $res, '', 403, $role . ': REST update of the admin pattern refused' );
			assert_same( $original, qa_010_content( $id ), $role . ': the admin pattern is unchanged' );
			$res = rest( 'DELETE', '/wp/v2/blocks/' . $id, array( 'force' => true ) );
			expect_wp_error( $res, '', 403, $role . ': REST delete of the admin pattern refused' );
			assert_same( 'publish', get_post_status( $id ), $role . ': the admin pattern still exists' );
		}
	}
);

test(
	'an Author keeps no edit rights to a synced pattern published under their name',
	function () {
		as_role( 'admin' );
		$id       = qa_010_pattern_by( 'author' );
		$original = qa_010_content( $id );
		as_role( 'author' );
		assert_false( current_user_can( 'edit_post', $id ), 'author: edit_post on own pattern' );
		assert_false( current_user_can( 'delete_post', $id ), 'author: delete_post on own pattern' );
		$res = rest( 'POST', '/wp/v2/blocks/' . $id, array( 'content' => qa_010_markup( 'Call 509-555-0199' ) ) );
		expect_wp_error( $res, '', 403, 'author: REST update of own pattern refused' );
		assert_same( $original, qa_010_content( $id ), 'author: own pattern unchanged' );
	}
);

test(
	'an administrator can still create, edit and delete synced patterns',
	function () {
		as_role( 'admin' );
		$type = get_post_type_object( 'wp_block' );
		assert_true( current_user_can( $type->cap->edit_posts ), 'admin: wp_block edit_posts' );
		assert_true( current_user_can( $type->cap->create_posts ), 'admin: wp_block create_posts' );
		$res = rest(
			'POST',
			'/wp/v2/blocks',
			array(
				'title'   => 'QA-010 admin pattern',
				'status'  => 'publish',
				'content' => qa_010_markup( 'Net control: KD7ABC' ),
			)
		);
		expect_not_wp_error( $res, 'admin: create' );
		assert_same( 201, $res->get_status(), 'admin: create status' );
		$id = (int) $res->get_data()['id'];
		assert_true( current_user_can( 'edit_post', $id ), 'admin: edit_post' );
		expect_not_wp_error( rest( 'POST', '/wp/v2/blocks/' . $id, array( 'content' => qa_010_markup( 'Net control: KD7XYZ' ) ) ), 'admin: update' );
		assert_contains( 'KD7XYZ', qa_010_content( $id ), 'admin: update saved' );
		expect_not_wp_error( rest( 'DELETE', '/wp/v2/blocks/' . $id, array( 'force' => true ) ), 'admin: delete' );
		assert_same( null, get_post( $id ), 'admin: deleted' );
	}
);
