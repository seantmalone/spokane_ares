<?php
/**
 * Regression tests for a Fixer-round bug that shipped without a test
 * (build-notes/plugin.md "Fixer round" › Pages; PLAN §4.2): Quick Edit and
 * bulk Edit on Pages let a non-administrator rename a fixed page and reach
 * its slug, date, author, password, status, parent, order and template. The
 * fix (admin-trim.php) removes the Quick Edit link and the bulk Edit action
 * from Pages for non-admins, and refuses core's handlers themselves (a
 * crafted request doesn't need the link): admin-ajax "inline-save" and
 * edit.php bulk_edit, for pages (non-admins) and for events and documents
 * (everyone).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Post a Quick Edit save the way admin-ajax.php would, with core's own
 * handler hooked behind the plugin's refusal (admin-ajax.php hooks it at
 * priority 1; the refusal is at priority 0). Returns call_request()'s result.
 *
 * @param int    $id    Post ID.
 * @param string $title New title.
 */
function prior_quick_edit_save( int $id, string $title ): array {
	require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
	$post = get_post( $id );
	add_action( 'wp_ajax_inline-save', 'wp_ajax_inline_save', 1 );
	try {
		return call_request(
			'POST',
			array(),
			array(
				'action'       => 'inline-save',
				'_inline_edit' => wp_create_nonce( 'inlineeditnonce' ),
				'post_ID'      => $id,
				'post_type'    => $post->post_type,
				'post_title'   => $title,
				'post_name'    => $post->post_name,
				'post_status'  => $post->post_status,
				'screen'       => 'edit-' . $post->post_type,
				'post_view'    => 'list',
			),
			static function () {
				do_action( 'wp_ajax_inline-save' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's admin-ajax hook, fired as admin-ajax.php does.
			}
		);
	} finally {
		remove_action( 'wp_ajax_inline-save', 'wp_ajax_inline_save', 1 );
	}
}

/**
 * Open edit.php with a bulk Edit submission (the load-edit.php hook, which
 * runs before core's bulk_edit_posts()). Returns call_request()'s result.
 *
 * @param string $post_type Post type of the list.
 * @param int    $id        A post in the selection.
 */
function prior_bulk_edit_load( string $post_type, int $id ): array {
	global $typenow;
	$was     = $typenow;
	$typenow = $post_type; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- simulating edit.php for this post type.
	try {
		return call_request(
			'GET',
			array(
				'post_type' => $post_type,
				'action'    => 'edit',
				'bulk_edit' => 'Update',
				'post'      => array( $id ),
				'_wpnonce'  => wp_create_nonce( 'bulk-posts' ),
			),
			array(),
			static function () {
				do_action( 'load-edit.php' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's screen hook, fired as edit.php does.
			}
		);
	} finally {
		$typenow = $was; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
	}
}

test(
	'Pages offer editors no Quick Edit link and no bulk Edit; administrators keep both',
	function () {
		$about   = get_post( page_id( 'about' ) );
		$actions = array(
			'edit'                 => 'Edit',
			'inline hide-if-no-js' => 'Quick Edit',
			'view'                 => 'View',
		);
		$bulk    = array(
			'edit'  => 'Edit',
			'trash' => 'Move to Trash',
		);
		foreach ( array( 'ares-editor', 'ares-net', 'core-editor' ) as $role ) {
			as_role( $role );
			$row = apply_filters( 'page_row_actions', $actions, $about ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
			assert_false( isset( $row['inline hide-if-no-js'] ), $role . ': Quick Edit link' );
			assert_true( isset( $row['edit'] ), $role . ': the Edit link stays' );
			$b = apply_filters( 'bulk_actions-edit-page', $bulk ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's filter.
			assert_false( isset( $b['edit'] ), $role . ': bulk Edit' );
		}
		as_role( 'admin' );
		$row = apply_filters( 'page_row_actions', $actions, $about ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		assert_true( isset( $row['inline hide-if-no-js'] ), 'admin: Quick Edit link' );
		$b = apply_filters( 'bulk_actions-edit-page', $bulk ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's filter.
		assert_true( isset( $b['edit'] ), 'admin: bulk Edit' );
	}
);

test(
	'a crafted Quick Edit save of a page by an editor is refused (403) and the page keeps its title',
	function () {
		$id    = page_id( 'about' );
		$title = get_post( $id )->post_title;
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			assert_true( current_user_can( 'edit_post', $id ), $role . ' may edit About (so only the refusal stops Quick Edit)' );
			$r = prior_quick_edit_save( $id, 'QA renamed by Quick Edit' );
			clean_post_cache( $id );
			assert_same( $title, get_post( $id )->post_title, $role . ': title' );
			assert_same( 403, $r['status'], $role . ': status' );
			assert_contains( 'Quick Edit is off', (string) $r['die'], $role . ': message' );
		}
	}
);

test(
	'a crafted Quick Edit save of an event or a document is refused for everyone, administrators too',
	function () {
		$ids = array(
			'event'    => post_id( 'spk_event', 'set-2026' ),
			'document' => post_id( 'spk_document', 'ics-213' ),
		);
		foreach ( array( 'ares-editor', 'admin' ) as $role ) {
			foreach ( $ids as $what => $id ) {
				as_role( $role );
				$title = get_post( $id )->post_title;
				$r     = prior_quick_edit_save( $id, 'QA renamed by Quick Edit' );
				clean_post_cache( $id );
				assert_same( $title, get_post( $id )->post_title, $role . ' ' . $what . ': title' );
				assert_same( 403, $r['status'], $role . ' ' . $what . ': status' );
			}
		}
	}
);

test(
	'the Quick Edit refusal leaves an administrator\'s page save alone',
	function () {
		as_role( 'admin' );
		// Only the refusal is hooked here (not core's handler), so an allowed
		// save simply returns.
		$r = call_request(
			'POST',
			array(),
			array( 'post_ID' => page_id( 'about' ) ),
			static function () {
				do_action( 'wp_ajax_inline-save' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's admin-ajax hook.
			}
		);
		assert_same( null, $r['die'], 'no refusal' );
		assert_same( null, $r['status'], 'no status' );
	}
);

test(
	'a crafted bulk Edit of pages by an editor is refused (403) before core applies it',
	function () {
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$r = prior_bulk_edit_load( 'page', page_id( 'about' ) );
			assert_same( 403, $r['status'], $role . ': status' );
			assert_contains( 'Bulk Edit is off', (string) $r['die'], $role . ': message' );
		}
	}
);

test(
	'a crafted bulk Edit of events or documents is refused for everyone, administrators too',
	function () {
		foreach ( array( 'ares-editor', 'admin' ) as $role ) {
			as_role( $role );
			$r = prior_bulk_edit_load( 'spk_event', post_id( 'spk_event', 'set-2026' ) );
			assert_same( 403, $r['status'], $role . ' events' );
			$r = prior_bulk_edit_load( 'spk_document', post_id( 'spk_document', 'ics-213' ) );
			assert_same( 403, $r['status'], $role . ' documents' );
		}
	}
);
