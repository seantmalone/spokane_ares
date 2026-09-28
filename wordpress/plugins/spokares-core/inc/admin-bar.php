<?php
/**
 * The admin bar for editors (§4.2): no WordPress logo, "+ New", comments or
 * Customize; "Edit page" only where page text is editable; and an
 * "Update lists" menu on the front end.
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
	$items = array(
		'rota'      => array( __( 'Net rota', 'spokares-core' ), admin_url( 'admin.php?page=spokares-rota' ), 'spokares_edit_rota' ),
		'events'    => array( __( 'Events', 'spokares-core' ), admin_url( 'edit.php?post_type=spk_event' ), 'edit_spk_events' ),
		'meetings'  => array( __( 'Regular meetings', 'spokares-core' ), admin_url( 'admin.php?page=spokares-meetings' ), 'spokares_edit_rota' ),
		'documents' => array( __( 'Documents', 'spokares-core' ), admin_url( 'edit.php?post_type=spk_document' ), 'edit_spk_documents' ),
		'tiles'     => array( __( 'Hub tiles', 'spokares-core' ), admin_url( 'admin.php?page=spokares-tiles' ), 'edit_spk_documents' ),
	);
	$any   = false;
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
		foreach ( array( 'wp-logo', 'new-content', 'comments', 'customize', 'updates', 'search', 'site-editor', 'edit-site' ) as $node ) {
			$bar->remove_node( $node );
		}
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		// Nothing to find or open for an account that edits nothing.
		$bar->remove_node( 'command-palette' );
	}
	$bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'spokares_admin_bar_trim', PHP_INT_MAX );

/**
 * No command palette (Cmd/Ctrl+K) for accounts that edit nothing: it offers
 * only "Dashboard" and "View site", and every keystroke fires a REST search
 * the account may not make.
 */
function spokares_no_command_palette(): void {
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' );
	}
}
add_action( 'admin_init', 'spokares_no_command_palette' );
