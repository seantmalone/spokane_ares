<?php
/**
 * Tests for the governance-security fixes beyond each bug's own regression
 * test: the paths the fixes added or rely on, and the quality issues fixed
 * in the same pass (PLAN §4.1, §4.3, §5.3, §5.5).
 *
 * - QA-001: wp_delete_post() by a non-administrator leaves a fixed page;
 *   the core Editor has no page-delete capability at all.
 * - QA-002: making an ex-editor an ARES Editor again doesn't restore the old
 *   Net details grant; the Users screen shows who holds it (QA-072).
 * - QA-013: an administrator can still demote an ARES Editor to Subscriber
 *   (how access is taken away), but not to a core role with powers.
 * - QA-014: empty list items are removed from any list, nested or not, and
 *   nothing else in the content changes.
 * - QA-069: deleting a file a page or document uses is refused with a 409
 *   that says where it is used (REST, and the Media Library's delete link).
 * - QA-070: unfiltered_html needs manage_options, whatever wp-config says.
 * - QA-074: a link to an attachment page goes to the file.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The Home hero's attachment ID (_spk_seed = hero, as page setup marks it).
 */
function qa_security_hero_id(): int {
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
	return (int) $ids[0];
}

test(
	'QA-001: wp_delete_post() by a core Editor or an ARES Editor leaves a fixed page in place',
	function () {
		$about = page_id( 'about' );
		foreach ( array( 'core-editor', 'ares-editor' ) as $role ) {
			as_role( $role );
			$res = wp_delete_post( $about, true );
			assert_true( empty( $res ), $role . ': wp_delete_post() was refused' );
			clean_post_cache( $about );
			assert_same( 'publish', get_post_status( $about ), $role . ': About is still published' );
		}
	}
);

test(
	'QA-001: the core Editor has no page-delete capability (so no Trash row or bulk action anywhere)',
	function () {
		as_role( 'core-editor' );
		foreach ( array( 'delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_private_pages' ) as $cap ) {
			assert_false( current_user_can( $cap ), 'core-editor: ' . $cap ); // phpcs:ignore WordPress.WP.Capabilities.Undetermined -- a fixed list of core page caps.
		}
		as_role( 'admin' );
		assert_true( current_user_can( 'delete_pages' ), 'admin: delete_pages (control)' );
	}
);

test(
	'QA-002: making an ex-editor an ARES Editor again does not bring back the old Net details grant',
	function () {
		$id   = user_id( 'ares-net' );
		$user = new \WP_User( $id );
		$user->set_role( 'subscriber' );
		$user = new \WP_User( $id );
		$user->set_role( 'ares_editor' );
		clean_user_cache( $id );
		assert_contains( 'ares_editor', get_userdata( $id )->roles, 'an ARES Editor again' );
		assert_false( user_can( $id, 'spokares_edit_net_details' ), 'the grant came back with the role' );
	}
);

test(
	'QA-072: the Users screen has a Net details column that names the grant holders',
	function () {
		as_role( 'admin' );
		$columns = apply_filters( 'manage_users_columns', array( 'username' => 'Username' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's Users screen filter, applied as the screen does.
		assert_true( isset( $columns['spokares_net_details'] ), 'the column is added' );
		$cell = static fn( string $key ): string => (string) apply_filters( 'manage_users_custom_column', '', 'spokares_net_details', user_id( $key ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's Users screen filter.
		assert_same( 'Yes', $cell( 'ares-net' ), 'the granted ARES Editor' );
		assert_contains( 'administrator', $cell( 'admin' ), 'the administrator' );
		assert_contains( 'No', $cell( 'ares-editor' ), 'an ARES Editor without the grant' );
		assert_contains( 'No', $cell( 'subscriber' ), 'a Subscriber' );
	}
);

test(
	'QA-013: an administrator can demote an ARES Editor to Subscriber (taking access away), not to Author',
	function () {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$id     = user_id( 'ares-editor' );
		$fields = static function ( string $role ) use ( $id ): array {
			$user = get_userdata( $id );
			return array(
				'role'         => $role,
				'email'        => $user->user_email,
				'nickname'     => $user->nickname,
				'display_name' => $user->display_name,
			);
		};
		as_role( 'admin' );
		$res = call_request( 'POST', array(), $fields( 'author' ), static fn() => edit_user( $id ) );
		assert_same( 403, $res['status'], 'Author refused' );
		clean_user_cache( $id );
		assert_same( array( 'ares_editor' ), array_values( get_userdata( $id )->roles ), 'role after the refused change' );

		$res = call_request( 'POST', array(), $fields( 'subscriber' ), static fn() => edit_user( $id ) );
		assert_same( null, $res['die'], 'Subscriber allowed' );
		clean_user_cache( $id );
		assert_same( array( 'subscriber' ), array_values( get_userdata( $id )->roles ), 'demoted' );

		// Nothing is left over for the next role list (Add User).
		assert_same( array( 'administrator', 'ares_editor' ), array_keys( get_editable_roles() ), 'Add User roles afterwards' );
	}
);

test(
	'QA-014: empty list items are removed from nested lists too, and nothing else changes',
	function () {
		$item    = static fn( string $html ): string => "<!-- wp:list-item -->\n" . $html . "\n<!-- /wp:list-item -->";
		$content = "<!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:list {\"className\":\"stops\"} -->\n<ul class=\"wp-block-list stops\">"
			. $item( '<li><strong>One</strong> first.</li>' ) . "\n\n" . $item( '<li> <br> &nbsp;</li>' ) . "\n\n" . $item( '<li><strong>Two</strong> second.</li>' )
			. "</ul>\n<!-- /wp:list --></div>\n<!-- /wp:group -->";
		$clean   = \spokares_strip_empty_list_items( $content );
		assert_not_same( $content, $clean, 'the empty item was removed' );
		assert_count( 2, array_filter( parse_blocks( $clean )[0]['innerBlocks'][0]['innerBlocks'] ), 'two items left' );
		assert_contains( '<strong>One</strong> first.', $clean, 'first item kept' );
		assert_contains( '<strong>Two</strong> second.', $clean, 'last item kept' );
		assert_false( \spokares_layout_changed( $content, $clean ), 'removing an empty item is not a layout change' );

		$no_empty = str_replace( '<li> <br> &nbsp;</li>', '<li>Three</li>', $content );
		assert_same( $no_empty, \spokares_strip_empty_list_items( $no_empty ), 'content without an empty item is returned unchanged' );
	}
);

test(
	'QA-069: REST delete of a file a page uses is a 409 that names the page (after the permission check)',
	function () {
		$hero = qa_security_hero_id();
		as_role( 'admin' );
		$res = rest( 'DELETE', '/wp/v2/media/' . $hero, array( 'force' => true ) );
		$err = expect_wp_error( $res, 'spokares_file_in_use', 409, 'admin REST delete of the hero' );
		assert_contains( 'Home', $err->get_error_message(), 'the message says where the file is used' );
		clean_post_cache( $hero );
		assert_same( 'attachment', get_post_type( $hero ), 'the hero is still there' );

		as_role( 'core-editor' );
		expect_wp_error( rest( 'DELETE', '/wp/v2/media/' . $hero, array( 'force' => true ) ), 'rest_cannot_delete', 403, 'core-editor: the permission refusal comes first' );
	}
);

test(
	'QA-069: the Media Library\'s Delete Permanently for a file a page uses says where, with a way back (409, not 500)',
	function () {
		global $pagenow;
		$hero  = qa_security_hero_id();
		$saved = $pagenow;
		as_role( 'admin' );
		$pagenow = 'post.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated screen, restored below.
		try {
			$res = call_request(
				'GET',
				array(
					'action' => 'delete',
					'post'   => $hero,
				),
				array(),
				static function () {
					\spokares_media_delete_precheck();
				}
			);
		} finally {
			$pagenow = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
		assert_same( 409, $res['status'], 'status' );
		assert_contains( 'Home', (string) $res['die'], 'says where the file is used' );
		assert_contains( 'Back to the Media Library', (string) $res['die'], 'offers a way back to the Media Library' );
	}
);

test(
	'QA-070: unfiltered_html also needs manage_options, whatever wp-config.php says',
	function () {
		$core_editor = user_id( 'core-editor' );
		$caps        = \spokares_map_meta_cap( array( 'unfiltered_html' ), 'unfiltered_html', $core_editor, array() );
		assert_contains( 'manage_options', $caps, 'the mapped caps include manage_options' );
		as_role( 'core-editor' );
		assert_false( current_user_can( 'unfiltered_html' ), 'core-editor: unfiltered_html' );
	}
);

test(
	'QA-074: a link to an attachment page goes to the file (attachment pages are 404)',
	function () {
		$hero = qa_security_hero_id();
		assert_same( wp_get_attachment_url( $hero ), get_attachment_link( $hero ), 'get_attachment_link()' );
		assert_same( wp_get_attachment_url( $hero ), get_permalink( $hero ), 'get_permalink()' );
	}
);
