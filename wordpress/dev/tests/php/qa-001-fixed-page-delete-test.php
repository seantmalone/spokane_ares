<?php
/**
 * Regression tests for QA-001 (PLAN §4.1: "no editor can delete a file or a
 * page"; the six pages are fixed). The core Editor role has delete_pages,
 * delete_others_pages and delete_published_pages, and the plugin's
 * map_meta_cap (roles.php spokares_map_meta_cap()) only protects the three
 * members pages (core protects Home as the front page). So a core Editor
 * could:
 *
 * - permanently delete How it works or About over REST (DELETE ?force=true),
 *   so /about/ 404s and the nav links die;
 * - move either page to the Trash (Pages list, bulk action, REST DELETE
 *   without force). wp_trash_post() renames the slug to <slug>__trashed, and
 *   then the page guard (governance.php spokares_page_insert_guard()) forces
 *   the status back to publish: the page is live at /how-it-works__trashed/,
 *   /how-it-works/ 404s and Undo gives HTTP 500.
 *
 * Correct behaviour: no non-administrator can delete or trash a fixed page by
 * any path, and a refused trash leaves the page published at its own slug.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The paths of the six fixed pages.
 */
function qa_001_fixed_paths(): array {
	return array( 'home', 'how-it-works', 'about', 'members', 'members/documents', 'members/exercises' );
}

/**
 * The two fixed pages with page text that a core Editor could delete.
 */
function qa_001_text_paths(): array {
	return array( 'how-it-works', 'about' );
}

/**
 * A page's slug and status, read fresh from the database (null when gone).
 *
 * @param int $id Page ID.
 */
function qa_001_page_state( int $id ): ?array {
	clean_post_cache( $id );
	$p = get_post( $id );
	if ( ! $p ) {
		return null;
	}
	return array(
		'post_status' => (string) $p->post_status,
		'post_name'   => (string) $p->post_name,
	);
}

/**
 * Fail unless the page still exists, published, at its own path.
 *
 * @param int    $id    Page ID.
 * @param string $path  Its path.
 * @param string $label Message prefix.
 */
function qa_001_assert_intact( int $id, string $path, string $label ): void {
	$state = qa_001_page_state( $id );
	assert_true( null !== $state, $label . ': the page still exists' );
	assert_same( 'publish', $state['post_status'], $label . ': status' );
	assert_same( basename( $path ), $state['post_name'], $label . ': slug (no __trashed suffix)' );
	$found = get_page_by_path( $path, OBJECT, 'page' );
	assert_same( $id, $found ? (int) $found->ID : 0, $label . ': /' . $path . '/ still resolves to the page' );
}

test(
	'no non-administrator can delete or trash any of the six fixed pages (delete_post and delete_page)',
	function () {
		$roles = array( 'subscriber', 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net' );
		$ids   = array();
		foreach ( qa_001_fixed_paths() as $path ) {
			$ids[ $path ] = page_id( $path );
		}
		foreach ( $roles as $role ) {
			as_role( $role );
			foreach ( $ids as $path => $id ) {
				assert_false( current_user_can( 'delete_post', $id ), $role . ' delete_post ' . $path );
				assert_false( current_user_can( 'delete_page', $id ), $role . ' delete_page ' . $path );
			}
		}
	}
);

test(
	'REST: the core Editor cannot permanently delete How it works or About (DELETE ?force=true), like the ARES Editor',
	function () {
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			foreach ( qa_001_text_paths() as $path ) {
				$id    = page_id( $path );
				$label = $role . ' ' . $path;
				as_role( $role );
				$res = rest( 'DELETE', '/wp/v2/pages/' . $id, array( 'force' => true ) );
				expect_wp_error( $res, 'rest_cannot_delete', 403, $label . ': force delete refused' );
				qa_001_assert_intact( $id, $path, $label );
			}
		}
	}
);

test(
	'REST: the core Editor cannot move How it works or About to the Trash (DELETE without force)',
	function () {
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			foreach ( qa_001_text_paths() as $path ) {
				$id    = page_id( $path );
				$label = $role . ' ' . $path;
				as_role( $role );
				$res = rest( 'DELETE', '/wp/v2/pages/' . $id );
				expect_wp_error( $res, 'rest_cannot_delete', 403, $label . ': trash refused' );
				qa_001_assert_intact( $id, $path, $label );
			}
		}
	}
);

test(
	'wp_trash_post() by a non-administrator leaves a fixed page published at its own slug (no live <slug>__trashed page)',
	function () {
		foreach ( array( 'core-editor', 'ares-editor' ) as $role ) {
			foreach ( qa_001_text_paths() as $path ) {
				$id    = page_id( $path );
				$label = $role . ' ' . $path;
				as_role( $role );
				wp_trash_post( $id );
				qa_001_assert_intact( $id, $path, $label );
			}
		}
	}
);

test(
	'Pages list: the core Editor is offered no Trash for How it works or About (row action or bulk action)',
	function () {
		global $current_screen, $typenow, $taxnow;
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
		$saved = array( $current_screen, $typenow, $taxnow );
		as_role( 'core-editor' );
		try {
			set_current_screen( 'edit-page' );
			$table = new \WP_Posts_List_Table( array( 'screen' => 'edit-page' ) );
			foreach ( qa_001_text_paths() as $path ) {
				ob_start();
				$table->single_row( get_post( page_id( $path ) ) );
				$row = (string) ob_get_clean();
				assert_not_contains( 'action=trash', $row, $path . ': row offers Move to Trash' );
			}
			// WP_List_Table::bulk_actions() is protected; call it as the screen does.
			$bulk = new \ReflectionMethod( $table, 'bulk_actions' );
			ob_start();
			$bulk->invoke( $table, 'top' );
			$menu = (string) ob_get_clean();
			assert_not_contains( 'value="trash"', $menu, 'bulk actions offer Move to Trash' );
		} finally {
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the globals set_current_screen() changed.
			list( $current_screen, $typenow, $taxnow ) = $saved;
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
);

test(
	'an administrator can still trash and delete a fixed page (the refusal is for non-admins only)',
	function () {
		as_role( 'admin' );
		$about = page_id( 'about' );
		$how   = page_id( 'how-it-works' );
		assert_true( current_user_can( 'delete_post', $about ), 'admin delete_post about' );
		wp_trash_post( $how );
		assert_same( 'trash', qa_001_page_state( $how )['post_status'], 'admin trash: status' );
		expect_not_wp_error( rest( 'DELETE', '/wp/v2/pages/' . $about, array( 'force' => true ) ), 'admin force delete' );
		assert_same( null, qa_001_page_state( $about ), 'admin force delete: About is gone' );
	}
);
