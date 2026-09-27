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
 * Trim and extend the admin bar.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function spokares_admin_bar( $bar ): void {
	if ( ! $bar instanceof WP_Admin_Bar || ! is_user_logged_in() ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		foreach ( array( 'wp-logo', 'new-content', 'comments', 'customize', 'updates', 'search', 'site-editor', 'edit-site' ) as $node ) {
			$bar->remove_node( $node );
		}
	}
	$bar->remove_node( 'comments' );

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
