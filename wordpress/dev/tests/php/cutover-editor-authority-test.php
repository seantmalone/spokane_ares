<?php
/**
 * Production cutover: an ARES Editor publishes the content they own, and
 * does not gain administrator authority.
 *
 * Ordinary posts are not a public content type (the blog surface is 404
 * and creating a post or a page still requires manage_options). publish_posts
 * was still withheld on the role. Editors can publish an ordinary post that
 * already exists, and they publish events and documents themselves. There is
 * no webmaster check box on those screens.
 *
 * Administrators keep users, roles, site settings, themes, plugins and
 * synced patterns. Deployment approval is unchanged (it lives in the
 * GitHub production environment, not in this role).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'an ARES Editor has publish_posts and publishes an event and a document without a webmaster check',
	function () {
		as_role( 'ares-editor' );
		assert_true( current_user_can( 'publish_posts' ), 'publish_posts is on the ARES Editor role' );
		assert_true( current_user_can( 'publish_spk_events' ), 'the editor publishes events' );
		assert_true( current_user_can( 'publish_spk_documents' ), 'the editor publishes documents' );
		assert_true( current_user_can( 'publish_pages' ), 'the editor publishes the existing pages' );
		assert_true( current_user_can( 'spokares_edit_rota' ), 'the editor updates the Net Control Schedule' );

		as_role( 'admin' );
		$draft = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'draft',
				'post_title'  => 'Cutover ordinary draft',
				'post_author' => user_id( 'ares-editor' ),
			),
			true
		);
		assert_true( is_int( $draft ) && $draft > 0, 'set-up: an ordinary draft exists' );
		as_role( 'ares-editor' );
		$published = rest( 'POST', '/wp/v2/posts/' . $draft, array( 'status' => 'publish' ) );
		assert_same( 200, $published->get_status(), 'the editor publishes their own ordinary draft (' . $published->get_status() . ')' );
		assert_same( 'publish', get_post_status( $draft ), 'the ordinary post is published' );

		$event = wp_insert_post(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'publish',
				'post_title'  => 'Cutover editor event',
			),
			true
		);
		assert_true( is_int( $event ) && $event > 0, 'the editor can insert an event (' . ( is_wp_error( $event ) ? $event->get_error_message() : '' ) . ')' );
		assert_same( 'publish', get_post_status( $event ), 'the event is published by the editor' );

		$doc = wp_insert_post(
			array(
				'post_type'   => 'spk_document',
				'post_status' => 'publish',
				'post_title'  => 'Cutover editor document',
			),
			true
		);
		assert_true( is_int( $doc ) && $doc > 0, 'the editor can insert a document' );
		assert_same( 'publish', get_post_status( $doc ), 'the document is published by the editor' );

		global $wp_meta_boxes;
		$saved = $wp_meta_boxes;
		try {
			\spokares_event_boxes( get_post( $event ) );
			assert_false( isset( $wp_meta_boxes['spk_event']['side']['default']['spokares_checking'] ), 'an editor has no Webmaster check box on an event' );
			as_role( 'admin' );
			\spokares_event_boxes( get_post( $event ) );
			assert_false( isset( $wp_meta_boxes['spk_event']['side']['default']['spokares_checking'] ), 'an administrator has no Webmaster check box either' );
			\spokares_document_boxes();
			assert_false( isset( $wp_meta_boxes['spk_document']['side']['default']['spokares_checking'] ), 'an administrator has no Webmaster check box on a document' );
		} finally {
			$wp_meta_boxes = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
			wp_delete_post( (int) $event, true );
			wp_delete_post( (int) $doc, true );
		}
	}
);

test(
	'an ARES Editor cannot create ordinary posts or pages, and cannot administer the site',
	function () {
		as_role( 'ares-editor' );
		$post_type = get_post_type_object( 'post' );
		$page_type = get_post_type_object( 'page' );
		$pattern   = get_post_type_object( 'wp_block' );
		assert_true( $post_type instanceof \WP_Post_Type && $page_type instanceof \WP_Post_Type && $pattern instanceof \WP_Post_Type, 'post types are registered' );
		assert_false( current_user_can( $post_type->cap->create_posts ), 'creating an ordinary post stays manage_options (' . $post_type->cap->create_posts . ')' );
		assert_false( current_user_can( $page_type->cap->create_posts ), 'creating a page stays manage_options' );
		assert_false( current_user_can( $pattern->cap->publish_posts ), 'synced patterns stay administrators only (' . $pattern->cap->publish_posts . ')' );
		assert_false( current_user_can( 'manage_options' ), 'no site settings' );
		assert_false( current_user_can( 'edit_theme_options' ), 'no theme or menu editing' );
		assert_false( current_user_can( 'install_plugins' ), 'no plugins' );
		assert_false( current_user_can( 'activate_plugins' ), 'no plugin activation' );
		assert_false( current_user_can( 'edit_plugins' ), 'no plugin files' );
		assert_false( current_user_can( 'edit_themes' ), 'no theme files' );
		assert_false( current_user_can( 'update_core' ), 'no core updates' );
		assert_false( current_user_can( 'promote_users' ), 'no role changes' );
		assert_false( current_user_can( 'create_users' ), 'no user creation' );
		assert_false( current_user_can( 'delete_users' ), 'no user deletion' );
		assert_false( current_user_can( 'list_users' ), 'no user list' );
		assert_false( current_user_can( 'delete_pages' ), 'pages cannot be deleted' );
		assert_false( current_user_can( 'unfiltered_html' ), 'no unfiltered HTML' );

		$made = rest(
			'POST',
			'/wp/v2/posts',
			array(
				'title'  => 'Cutover ordinary post',
				'status' => 'publish',
			)
		);
		expect_wp_error( $made, 'rest_cannot_create', 403, 'REST refuses a new ordinary post' );

		as_role( 'admin' );
		assert_true( current_user_can( 'manage_options' ), 'an administrator keeps site settings' );
		assert_true( current_user_can( 'edit_theme_options' ), 'an administrator keeps the theme' );
		assert_true( current_user_can( 'promote_users' ), 'an administrator keeps roles' );
		assert_true( current_user_can( 'install_plugins' ), 'an administrator keeps plugins' );
		assert_true( current_user_can( $post_type->cap->create_posts ), 'an administrator can create an ordinary post' );
	}
);
