<?php
/**
 * The admin bar for editors (§4.2): no WordPress logo, "+ New", comments,
 * Customize, command palette or "Howdy,"; "Edit Page Text" only where page
 * text is editable; and an "Update lists" menu on the front end.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extend the admin bar: the "Update lists" menu on the front end.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function spokares_admin_bar( $bar ): void {
	if ( ! $bar instanceof WP_Admin_Bar || ! is_user_logged_in() ) {
		return;
	}
	if ( is_admin() ) {
		return;
	}
	// The screens' own names, URLs and capabilities (R10).
	$items = array();
	foreach ( array( 'rota', 'events', 'meetings', 'documents', 'tiles' ) as $key ) {
		$screen = spokares_edit_screen( $key );
		if ( $screen ) {
			$items[ $key ] = $screen;
		}
	}
	$any = false;
	foreach ( $items as $cap_item ) {
		if ( current_user_can( $cap_item[2] ) ) {
			$any = true;
			break;
		}
	}
	if ( ! $any ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'spokares-lists',
			'title' => __( 'Update lists', 'spokares-core' ),
			'href'  => admin_url( 'index.php' ),
		)
	);
	foreach ( $items as $id => [ $label, $url, $cap ] ) {
		if ( current_user_can( $cap ) ) {
			$bar->add_node(
				array(
					'parent' => 'spokares-lists',
					'id'     => 'spokares-lists-' . $id,
					'title'  => $label,
					'href'   => $url,
				)
			);
		}
	}
}
add_action( 'admin_bar_menu', 'spokares_admin_bar', 999 );

/**
 * Remove what editors don't use (§4.2). This runs last: WordPress adds some
 * nodes late (Search at 9999, after an earlier removal would have run).
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function spokares_admin_bar_trim( $bar ): void {
	if ( ! $bar instanceof WP_Admin_Bar || ! is_user_logged_in() ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		// No command palette for editors either (§3.9): the menu and the
		// Dashboard are the ways in.
		foreach ( array( 'wp-logo', 'new-content', 'comments', 'customize', 'updates', 'search', 'site-editor', 'edit-site', 'command-palette' ) as $node ) {
			$bar->remove_node( $node );
		}
		// The account menu shows the name alone, without "Howdy,": core's
		// title is "Howdy, <span class="display-name">…</span>" plus the picture.
		$account = $bar->get_node( 'my-account' );
		$pos     = $account && is_string( $account->title ) ? strpos( $account->title, '<span class="display-name">' ) : false;
		if ( $account && $pos ) {
			$meta               = is_array( $account->meta ) ? $account->meta : array();
			$meta['menu_title'] = wp_get_current_user()->display_name;
			$bar->add_node(
				array(
					'id'    => 'my-account',
					'title' => substr( $account->title, $pos ),
					'meta'  => $meta,
				)
			);
		}
	}
	$bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'spokares_admin_bar_trim', PHP_INT_MAX );

/**
 * No command palette (Cmd/Ctrl+K) for anyone but administrators (§3.9): for
 * an editor it only offers WordPress screens the menu already has, and for
 * an account that edits nothing every keystroke fires a REST search it may
 * not make.
 */
function spokares_no_command_palette(): void {
	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' );
	}
}
add_action( 'admin_init', 'spokares_no_command_palette' );
