<?php
/**
 * Admin simplification (§4.2): trimmed menus for non-admins, Media and Posts
 * redirected away, dashboard widgets, a shorter profile screen, comments off.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the current user an administrator (for trimming decisions only; every
 * write still checks its own capability)?
 */
function spokares_is_site_admin(): bool {
	return current_user_can( 'manage_options' );
}

/**
 * Hide the menus editors don't use.
 */
function spokares_trim_menus(): void {
	if ( spokares_is_site_admin() ) {
		return;
	}
	foreach ( array( 'edit.php', 'upload.php', 'edit-comments.php', 'tools.php' ) as $slug ) {
		remove_menu_page( $slug );
	}
	remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );
}
add_action( 'admin_menu', 'spokares_trim_menus', 999 );

/**
 * Send non-admins away from Media and Posts screens to the Dashboard.
 */
function spokares_trim_redirects(): void {
	if ( spokares_is_site_admin() || wp_doing_ajax() ) {
		return;
	}
	global $pagenow;
	$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
	$away = in_array( $pagenow, array( 'upload.php', 'media-new.php', 'edit-comments.php', 'tools.php' ), true )
		|| ( in_array( $pagenow, array( 'edit.php', 'post-new.php' ), true ) && 'post' === $type );
	if ( $away ) {
		wp_safe_redirect( admin_url( 'index.php' ) );
		exit;
	}
}
add_action( 'admin_init', 'spokares_trim_redirects' );

/**
 * Dashboard: only "Site tasks" for editors; Welcome, Quick Draft and News off for all.
 */
function spokares_trim_dashboard(): void {
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	if ( ! spokares_is_site_admin() ) {
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_php_nag', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_browser_nag', 'dashboard', 'normal' );
	}
}
add_action( 'wp_dashboard_setup', 'spokares_trim_dashboard', 20 );
remove_action( 'welcome_panel', 'wp_welcome_panel' );

/**
 * Profile for non-admins: no colour scheme picker, no extra contact fields.
 */
function spokares_trim_profile(): void {
	if ( spokares_is_site_admin() ) {
		return;
	}
	remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
}
add_action( 'admin_init', 'spokares_trim_profile' );
add_filter( 'user_contactmethods', static fn( $methods ) => spokares_is_site_admin() ? $methods : array() );

/**
 * Body class so admin.css can hide the profile rows editors don't need
 * (keyboard shortcuts, toolbar, website, biography, picture).
 *
 * @param string $classes Classes.
 */
function spokares_admin_body_class( $classes ): string {
	if ( ! spokares_is_site_admin() ) {
		$classes .= ' spk-editor';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'spokares_admin_body_class' );


/**
 * No Quick Edit on our types (their forms hold the rules).
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Post.
 */
function spokares_row_actions_trim( $actions, $post ) {
	if ( $post instanceof WP_Post && in_array( $post->post_type, array( 'spk_event', 'spk_document' ), true ) ) {
		unset( $actions['inline hide-if-no-js'], $actions['view'] );
	}
	return $actions;
}
add_filter( 'post_row_actions', 'spokares_row_actions_trim', 10, 2 );

/**
 * No Quick Edit on pages for editors: it offers the title, slug, date,
 * author, password, private, parent, order, template and status, and the
 * pages are fixed (the server keeps all of those anyway).
 *
 * @param array $actions Row actions.
 */
function spokares_page_row_actions_trim( $actions ) {
	if ( ! spokares_is_site_admin() ) {
		unset( $actions['inline hide-if-no-js'] );
	}
	return $actions;
}
add_filter( 'page_row_actions', 'spokares_page_row_actions_trim', 10 );

/**
 * No bulk Edit on our types.
 *
 * @param array $actions Bulk actions.
 */
function spokares_bulk_edit_trim( $actions ) {
	unset( $actions['edit'] );
	return $actions;
}
add_filter( 'bulk_actions-edit-spk_event', 'spokares_bulk_edit_trim' );
add_filter( 'bulk_actions-edit-spk_document', 'spokares_bulk_edit_trim' );

/**
 * No bulk Edit on pages for editors (it has the same fields as Quick Edit).
 *
 * @param array $actions Bulk actions.
 */
function spokares_page_bulk_edit_trim( $actions ) {
	if ( ! spokares_is_site_admin() ) {
		unset( $actions['edit'] );
	}
	return $actions;
}
add_filter( 'bulk_actions-edit-page', 'spokares_page_bulk_edit_trim' );

/**
 * Is Quick Edit or bulk Edit closed for this post type and user? Always for
 * events and documents (their forms hold the checks, and hiding the links
 * doesn't stop a crafted request); for pages, for everyone but administrators.
 *
 * @param string $post_type Post type.
 */
function spokares_inline_edit_closed( string $post_type ): bool {
	if ( in_array( $post_type, array( 'spk_event', 'spk_document' ), true ) ) {
		return true;
	}
	return 'page' === $post_type && ! spokares_is_site_admin();
}

/**
 * Refuse core's Quick Edit handler (admin-ajax "inline-save") where it is
 * closed. Runs before core's own handler (priority 1).
 */
function spokares_refuse_inline_save(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only routing check before core verifies its own nonce; this only refuses.
	$id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;
	if ( $id && spokares_inline_edit_closed( (string) get_post_type( $id ) ) ) {
		wp_die( esc_html__( 'Quick Edit is off here. Open the item and use its form.', 'spokares-core' ), '', array( 'response' => 403 ) );
	}
}
add_action( 'wp_ajax_inline-save', 'spokares_refuse_inline_save', 0 );

/**
 * Refuse core's bulk Edit (edit.php?action=edit&bulk_edit) where it is closed.
 */
function spokares_refuse_bulk_edit(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- read-only routing check before core verifies its own nonce; this only refuses.
	if ( ! isset( $_GET['bulk_edit'] ) && ! isset( $_POST['bulk_edit'] ) ) {
		return;
	}
	global $typenow;
	if ( spokares_inline_edit_closed( (string) $typenow ) ) {
		wp_die( esc_html__( 'Bulk Edit is off here. Open each item and use its form.', 'spokares-core' ), '', array( 'response' => 403 ) );
	}
}
add_action( 'load-edit.php', 'spokares_refuse_bulk_edit', 0 );

/**
 * The Save box on events and documents can't be hidden or folded away:
 * without it an editor has no Save, Publish or Update button at all. Screen
 * Options is also off on those two forms for editors.
 *
 * @param string[]  $hidden Hidden box ids.
 * @param WP_Screen $screen Screen.
 */
function spokares_never_hide_savebox( $hidden, $screen ) {
	if ( is_array( $hidden ) && $screen instanceof WP_Screen && in_array( $screen->post_type, array( 'spk_event', 'spk_document' ), true ) ) {
		$hidden = array_values( array_diff( $hidden, array( 'spokares_savebox', 'submitdiv' ) ) );
	}
	return $hidden;
}
add_filter( 'hidden_meta_boxes', 'spokares_never_hide_savebox', 99, 2 );

/**
 * Never draw the Save box folded ("closed").
 *
 * @param string[] $classes Box classes.
 */
function spokares_savebox_open( $classes ) {
	return is_array( $classes ) ? array_values( array_diff( $classes, array( 'closed' ) ) ) : $classes;
}
add_filter( 'postbox_classes_spk_event_spokares_savebox', 'spokares_savebox_open' );
add_filter( 'postbox_classes_spk_document_spokares_savebox', 'spokares_savebox_open' );

/**
 * No Screen Options tab on the event and document forms for editors.
 *
 * @param bool      $show   Show the tab.
 * @param WP_Screen $screen Screen.
 */
function spokares_no_screen_options( $show, $screen ) {
	if ( $screen instanceof WP_Screen && 'post' === $screen->base && in_array( $screen->post_type, array( 'spk_event', 'spk_document' ), true ) && ! spokares_is_site_admin() ) {
		return false;
	}
	return $show;
}
add_filter( 'screen_options_show_screen', 'spokares_no_screen_options', 10, 2 );

/**
 * A plain sentence instead of WordPress's "Sorry, you are not allowed to
 * access this page." on the two screens only the webmaster (or someone the
 * EC names) uses.
 */
function spokares_access_denied_message(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which screen was asked for; read-only.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( ! in_array( $page, array( 'spokares-net-details', 'spokares-meeting-rules' ), true ) ) {
		return;
	}
	$what = 'spokares-net-details' === $page ? __( 'Net details (the repeater settings, net times and Winlink, simplex and GMRS weeks)', 'spokares-core' ) : __( 'Meeting rules (the regular meetings’ weeks, days and times)', 'spokares-core' );
	wp_die(
		'<p>' . esc_html(
			sprintf(
				/* translators: %s: the screen and what it holds. */
				__( '%s are changed by the webmaster, or by someone the Emergency Coordinator has named. Ask the webmaster: webmaster@spokares.org.', 'spokares-core' ),
				$what
			)
		) . '</p><p><a href="' . esc_url( admin_url( 'index.php' ) ) . '">' . esc_html__( 'Back to the Dashboard', 'spokares-core' ) . '</a></p>',
		esc_html__( 'Ask the webmaster', 'spokares-core' ),
		array( 'response' => 403 )
	);
}
add_action( 'admin_page_access_denied', 'spokares_access_denied_message' );

/**
 * The title placeholders on our forms.
 *
 * @param string  $text Placeholder.
 * @param WP_Post $post Post.
 */
function spokares_title_placeholder( $text, $post ) {
	if ( $post instanceof WP_Post ) {
		if ( 'spk_event' === $post->post_type ) {
			return __( 'Event name, as members will see it', 'spokares-core' );
		}
		if ( 'spk_document' === $post->post_type ) {
			return __( 'Document name', 'spokares-core' );
		}
	}
	return $text;
}
add_filter( 'enter_title_here', 'spokares_title_placeholder', 10, 2 );

/**
 * No month filter on our lists (events are filtered by the views; documents
 * by section).
 *
 * @param bool   $disable   Disable the dropdown.
 * @param string $post_type Post type.
 */
function spokares_no_months_dropdown( $disable, $post_type ) {
	return in_array( $post_type, array( 'spk_event', 'spk_document' ), true ) ? true : $disable;
}
add_filter( 'disable_months_dropdown', 'spokares_no_months_dropdown', 10, 2 );
