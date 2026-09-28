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
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- routing only.
	$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
	// The Edit Media screen of one file (post.php?post=<file>&action=edit) and
	// the old media-upload.php library: Media is hidden, so these are too.
	$media_item = 'post.php' === $pagenow && isset( $_GET['post'], $_GET['action'] ) && 'edit' === $_GET['action'] && 'attachment' === get_post_type( absint( $_GET['post'] ) );
	// phpcs:enable
	$away = in_array( $pagenow, array( 'upload.php', 'media-new.php', 'media-upload.php', 'edit-comments.php', 'tools.php' ), true )
		|| ( in_array( $pagenow, array( 'edit.php', 'post-new.php' ), true ) && 'post' === $type )
		|| $media_item;
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

/**
 * No "Welcome to WordPress!" panel (it pushes the Site Editor, §5.7). Core
 * adds it in wp-admin/includes/admin-filters.php, after plugins load, so it
 * is removed once the admin is set up.
 */
function spokares_no_welcome_panel(): void {
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}
add_action( 'admin_init', 'spokares_no_welcome_panel' );
add_action( 'load-index.php', 'spokares_no_welcome_panel' );

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
 * Your own profile has one screen for non-admins: the trimmed profile.php.
 * user-edit.php?user_id=<you> is the same form without the trim (Website,
 * Biography, Profile Picture, a "View User" link that 404s).
 */
function spokares_own_profile_redirect(): void {
	if ( spokares_is_site_admin() || ! is_user_logged_in() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
	$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
	$method  = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( 'GET' === $method && get_current_user_id() === $user_id ) {
		wp_safe_redirect( admin_url( 'profile.php' ) );
		exit;
	}
}
add_action( 'load-user-edit.php', 'spokares_own_profile_redirect' );

/**
 * The fields the trimmed profile hides (Website, Biography) are not taken
 * from a non-admin's save either, whichever way it arrives (profile.php,
 * user-edit.php, a crafted request): the stored values stay.
 *
 * @param array    $data    User row data about to be saved.
 * @param bool     $update  An update of an existing user.
 * @param int|null $user_id User.
 */
function spokares_keep_hidden_profile_url( $data, $update, $user_id = null ) {
	if ( ! $update || ! $user_id || ! is_user_logged_in() || spokares_is_site_admin() || ! is_array( $data ) ) {
		return $data;
	}
	$stored = get_userdata( (int) $user_id );
	if ( $stored ) {
		$data['user_url'] = (string) $stored->user_url;
	}
	return $data;
}
add_filter( 'wp_pre_insert_user_data', 'spokares_keep_hidden_profile_url', 10, 3 );

/**
 * The same for the Biography (user meta "description").
 *
 * @param array   $meta   User meta about to be saved.
 * @param WP_User $user   User.
 * @param bool    $update An update of an existing user.
 */
function spokares_keep_hidden_profile_bio( $meta, $user, $update ) {
	if ( ! $update || ! $user instanceof WP_User || ! is_user_logged_in() || spokares_is_site_admin() || ! is_array( $meta ) ) {
		return $meta;
	}
	$meta['description'] = (string) get_user_meta( $user->ID, 'description', true );
	return $meta;
}
add_filter( 'insert_user_meta', 'spokares_keep_hidden_profile_bio', 10, 3 );

/**
 * The Dashboard's and the Profile's Help for non-admins describe what they
 * see, not the widgets and options the trim removed.
 */
function spokares_trimmed_help(): void {
	if ( spokares_is_site_admin() ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'profile' ), true ) ) {
		return;
	}
	$screen->remove_help_tabs();
	$screen->set_help_sidebar( '' );
	if ( 'profile' === $screen->id ) {
		spokares_screen_help( 'profile' );
	} else {
		spokares_screen_help( function_exists( 'spokares_has_site_tasks' ) && spokares_has_site_tasks() ? 'dashboard' : 'dashboard-none' );
	}
}
add_action( 'admin_head', 'spokares_trimmed_help' );

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

/**
 * The Site Editor (administrators only): say, where the edits are made, that
 * saving a template, a part or Styles here overrides the theme's files, so
 * later theme updates stop showing (§5.7: never edit in the Site Editor).
 *
 * @param string $hook Screen hook.
 */
function spokares_site_editor_warning( $hook ): void {
	if ( 'site-editor.php' !== $hook ) {
		return;
	}
	$text = __( 'Changes saved here override the theme’s own files, so later theme updates stop showing on the site. Change the theme in its files instead, and leave this screen unsaved.', 'spokares-core' );
	wp_add_inline_script(
		'wp-edit-site',
		'wp.domReady( function () { wp.data.dispatch( "core/notices" ).createWarningNotice( ' . wp_json_encode( $text ) . ', { id: "spokares-site-editor", isDismissible: true } ); } );',
		'after'
	);
}
add_action( 'admin_enqueue_scripts', 'spokares_site_editor_warning' );

/*
 * The site's menus are written into the header and footer parts. Opening
 * Navigation in the Site Editor must not create (and publish) a stray
 * "Navigation" menu that controls nothing.
 */
add_filter( 'wp_navigation_should_create_fallback', '__return_false' );

/*
 * No font library (PLAN §4.3 layer 5). The site's two fonts come with the
 * theme (theme.json), and the upload allowlist in spokares-hardening takes no
 * font files, so every upload or install from the Fonts screen failed ("not
 * allowed to upload this file type"). WordPress 7.1.2 offers the library to
 * everyone with edit_theme_options whatever the theme says (theme.json has no
 * switch for it), so it is turned off here: no Appearance › Fonts, no Fonts
 * screen, and no "Manage fonts" in the Site Editor's Styles › Typography.
 */

/**
 * No Appearance › Fonts link (core adds it in wp-admin/menu.php).
 */
function spokares_no_fonts_menu(): void {
	remove_submenu_page( 'themes.php', 'font-library.php' );
}
add_action( 'admin_menu', 'spokares_no_fonts_menu', 999 );

/**
 * The Fonts screen doesn't open: wp-admin/font-library.php and core's
 * full-page version (any admin URL with ?page=font-library, which core draws
 * on admin_init at priority 10) go to Appearance › Themes instead, or to the
 * Dashboard for an account that can't open Themes.
 */
function spokares_no_fonts_screen(): void {
	global $pagenow;
	if ( wp_doing_ajax() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'font-library.php' !== $pagenow && ! in_array( $page, array( 'font-library', 'font-library-wp-admin' ), true ) ) {
		return;
	}
	wp_safe_redirect( admin_url( current_user_can( 'switch_themes' ) ? 'themes.php' : 'index.php' ) );
	exit;
}
add_action( 'admin_init', 'spokares_no_fonts_screen', 1 );

/**
 * No font management in the editors (Styles › Typography's "Fonts" group and
 * its "Manage fonts" button). The theme's fonts stay selectable where
 * theme.json allows it.
 *
 * @param array $settings Block editor settings.
 */
function spokares_no_font_library_in_editor( $settings ) {
	if ( is_array( $settings ) ) {
		$settings['fontLibraryEnabled'] = false;
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'spokares_no_font_library_in_editor', 20 );
