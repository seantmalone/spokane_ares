<?php
/**
 * Admin framework shared by every plugin screen: the menu and its order,
 * notices across redirects, retained input (§4.4), help tabs, the Save box
 * for events and documents, and the admin CSS and JavaScript.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------- menu */

/**
 * The plugin's menus (§2): Net Control Schedule (with Net Settings for the
 * grant), Meetings (Cancel or Move a Meeting, with Meeting Schedule for the
 * grant) and Most Used under Documents. The names come from
 * spokares_edit_screen() (R10); each screen's file provides its page callback.
 */
function spokares_admin_menu(): void {
	$hooks = array();
	$name  = static fn( string $key ): string => (string) ( spokares_edit_screen( $key )[0] ?? '' );
	$move  = spokares_page_title( 'meetings' );

	$hooks['spokares-rota'] = add_menu_page( $name( 'rota' ), $name( 'rota' ), 'spokares_edit_rota', 'spokares-rota', 'spokares_rota_page', 'dashicons-microphone', 3 );
	add_submenu_page( 'spokares-rota', $name( 'rota' ), $name( 'rota' ), 'spokares_edit_rota', 'spokares-rota', 'spokares_rota_page' );
	$hooks['spokares-net-details'] = add_submenu_page( 'spokares-rota', $name( 'net-details' ), $name( 'net-details' ), 'spokares_edit_net_details', 'spokares-net-details', 'spokares_net_details_page' );

	// Right after Exercises & Events (menu_position 4); spokares_menu_order() sets the order.
	$hooks['spokares-meetings'] = add_menu_page( $move, $name( 'meetings' ), 'spokares_edit_rota', 'spokares-meetings', 'spokares_meetings_page', 'dashicons-groups', '4.5' );
	add_submenu_page( 'spokares-meetings', $move, $move, 'spokares_edit_rota', 'spokares-meetings', 'spokares_meetings_page' );
	$hooks['spokares-meeting-rules'] = add_submenu_page( 'spokares-meetings', $name( 'meeting-rules' ), $name( 'meeting-rules' ), 'spokares_edit_net_details', 'spokares-meeting-rules', 'spokares_meeting_rules_page' );

	$hooks['spokares-tiles'] = add_submenu_page( 'edit.php?post_type=spk_document', $name( 'tiles' ), $name( 'tiles' ), 'edit_spk_documents', 'spokares-tiles', 'spokares_tiles_page' );
	$hooks['spokares-site']  = add_options_page( __( 'ARES site', 'spokares-core' ), __( 'ARES site', 'spokares-core' ), 'manage_options', 'spokares-site', 'spokares_site_page' );

	foreach ( $hooks as $slug => $hook ) {
		if ( $hook ) {
			add_action( 'load-' . $hook, static fn() => spokares_screen_help( $slug ) );
		}
	}
}
add_action( 'admin_menu', 'spokares_admin_menu' );

/**
 * The two meeting screens moved from under Exercises & Events to their own
 * Meetings menu: an old bookmark (edit.php?post_type=spk_event&page=…) opens
 * the screen at its new address.
 */
function spokares_old_meeting_urls(): void {
	global $pagenow;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'edit.php' === $pagenow && in_array( $page, array( 'spokares-meetings', 'spokares-meeting-rules' ), true ) && ! wp_doing_ajax() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
		exit;
	}
}
add_action( 'admin_init', 'spokares_old_meeting_urls' );
// Under the old parent WordPress may refuse the page before admin_init.
add_action( 'admin_page_access_denied', 'spokares_old_meeting_urls', 0 );

/**
 * Use our menu order.
 */
add_filter( 'custom_menu_order', '__return_true' );

/**
 * Dashboard · Net Control Schedule · Exercises & Events · Meetings ·
 * Documents · Page Text first, then the rest (Profile).
 *
 * @param string[] $order Menu slugs.
 */
function spokares_menu_order( $order ) {
	if ( ! is_array( $order ) ) {
		return $order;
	}
	$want  = array( 'index.php', 'separator1', 'spokares-rota', 'edit.php?post_type=spk_event', 'spokares-meetings', 'edit.php?post_type=spk_document', 'edit.php?post_type=page' );
	$front = array_values( array_filter( $want, static fn( $slug ) => in_array( $slug, $order, true ) ) );
	return array_merge( $front, array_values( array_diff( $order, $front ) ) );
}
add_filter( 'menu_order', 'spokares_menu_order' );

/**
 * A plugin screen's name, as the menu and its h1 give it: the
 * spokares_edit_screen() name (R10), except the Meetings menu's first
 * screen, which is named for its job.
 *
 * @param string $key spokares_edit_screen() key.
 */
function spokares_page_title( string $key ): string {
	if ( 'meetings' === $key ) {
		return __( 'Cancel or Move a Meeting', 'spokares-core' );
	}
	return (string) ( spokares_edit_screen( $key )[0] ?? '' );
}

/**
 * A plugin screen's h1: WordPress's page title (spokares_page_title(), set
 * by the menu). Drawn outside a screen request (a test calling the page
 * function), WordPress has no page to name and get_admin_page_title() would
 * hand core a null, so the name comes from spokares_page_title() directly.
 *
 * @param string $key spokares_edit_screen() key.
 */
function spokares_screen_title( string $key ): string {
	global $title, $plugin_page;
	if ( ! empty( $title ) || is_string( $plugin_page ) ) {
		return get_admin_page_title();
	}
	return spokares_page_title( $key );
}

/* ----------------------------------------------------------------- notices */

/**
 * Remember which events and documents were just restored (Restore, or Undo
 * after "Take it off the site"), for the list's one notice: core's redirect
 * after a restore doesn't name them (it names only what went to the Trash).
 *
 * @param int $post_id Post.
 */
function spokares_remember_restored( $post_id ): void {
	$post_id = (int) $post_id;
	$type    = (string) get_post_type( $post_id );
	if ( ! in_array( $type, array( 'spk_event', 'spk_document' ), true ) ) {
		return;
	}
	$key   = 'spokares_restored_' . $type . '_' . get_current_user_id();
	$ids   = get_transient( $key );
	$ids   = is_array( $ids ) ? $ids : array();
	$ids[] = $post_id;
	set_transient( $key, array_values( array_unique( array_map( 'intval', $ids ) ) ), 5 * MINUTE_IN_SECONDS );
}
add_action( 'untrashed_post', 'spokares_remember_restored' );

/**
 * The events or documents this user just restored, read once (the notice
 * after the redirect uses them up).
 *
 * @param string $type spk_event or spk_document.
 * @return int[]
 */
function spokares_take_restored( string $type ): array {
	$key = 'spokares_restored_' . $type . '_' . get_current_user_id();
	$ids = get_transient( $key );
	delete_transient( $key );
	return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
}

/**
 * Queue a notice for this user's next admin screen (survives the redirect).
 *
 * @param string $type    success | error | warning | info.
 * @param string $text    Plain text.
 * @param string $url     Optional link.
 * @param string $label   Link words.
 */
function spokares_add_notice( string $type, string $text, string $url = '', string $label = '' ): void {
	$key       = 'spokares_notices_' . get_current_user_id();
	$notices   = get_transient( $key );
	$notices   = is_array( $notices ) ? $notices : array();
	$notices[] = array(
		'type'  => in_array( $type, array( 'success', 'error', 'warning', 'info' ), true ) ? $type : 'info',
		'text'  => $text,
		'url'   => $url,
		'label' => $label,
	);
	set_transient( $key, $notices, 5 * MINUTE_IN_SECONDS );
	// The events file drops core's own "message" from the redirect when a
	// notice is queued, so each save shows exactly one notice (§4.8).
	$GLOBALS['spokares_notice_queued'] = true;
}

/**
 * Print and clear the queued notices.
 */
function spokares_print_notices(): void {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	if ( ! is_array( $notices ) || ! $notices ) {
		return;
	}
	delete_transient( $key );
	foreach ( $notices as $n ) {
		printf(
			'<div class="notice notice-%1$s is-dismissible spk-notice"><p>%2$s%3$s</p></div>',
			esc_attr( $n['type'] ),
			esc_html( $n['text'] ),
			'' !== $n['url'] ? ' <a href="' . esc_url( $n['url'] ) . '">' . esc_html( $n['label'] ) . '</a>' : ''
		);
	}
}
add_action( 'admin_notices', 'spokares_print_notices' );

/* ---------------------------------------------------------- retained input */

/**
 * Hold what the editor typed for a field that wasn't saved, across the
 * redirect, for five minutes (§4.4).
 *
 * @param string $screen Screen slug.
 * @param array  $values Submitted values to show again.
 * @param array  $errors Field key => sentence.
 */
function spokares_retain( string $screen, array $values, array $errors ): void {
	set_transient(
		'spokares_retain_' . get_current_user_id() . '_' . $screen,
		array(
			'values' => $values,
			'errors' => $errors,
		),
		5 * MINUTE_IN_SECONDS
	);
}

/**
 * Read (and clear) the held input for a screen.
 *
 * @param string $screen Screen slug.
 * @return array{values:array,errors:array}
 */
function spokares_retained( string $screen ): array {
	$key  = 'spokares_retain_' . get_current_user_id() . '_' . $screen;
	$data = get_transient( $key );
	if ( is_array( $data ) ) {
		delete_transient( $key );
		return array(
			'values' => is_array( $data['values'] ?? null ) ? $data['values'] : array(),
			'errors' => is_array( $data['errors'] ?? null ) ? $data['errors'] : array(),
		);
	}
	return array(
		'values' => array(),
		'errors' => array(),
	);
}

/**
 * Class for a field with a problem.
 *
 * @param array  $errors Field key => sentence.
 * @param string $key    Field key.
 */
function spokares_err_class( array $errors, string $key ): string {
	return isset( $errors[ $key ] ) ? ' spk-field-error' : '';
}

/**
 * The sentence under a field with a problem.
 *
 * @param array  $errors Field key => sentence.
 * @param string $key    Field key.
 */
function spokares_err_text( array $errors, string $key ): void {
	if ( isset( $errors[ $key ] ) ) {
		// admin-forms.js ties the sentence to its outlined field
		// (aria-invalid, aria-describedby).
		echo '<span class="spk-error-text">' . esc_html( $errors[ $key ] ) . '</span>';
	}
}

/**
 * Is a typed value longer than a field allows? (The forms' maxlength is only
 * the browser's check; the save handlers check again.)
 *
 * @param string $text Typed text.
 * @param int    $max  Characters allowed.
 */
function spokares_too_long( string $text, int $max ): bool {
	return mb_strlen( $text, 'UTF-8' ) > $max;
}

/**
 * The events and documents lists narrowed to "Needs checking" (the
 * Dashboard's link): edit.php?…&spk_check=1, administrators only.
 */
function spokares_list_needs_check(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filtering only.
	return ! empty( $_GET['spk_check'] ) && current_user_can( 'manage_options' );
}

/**
 * Posted value helper: unslashed string from $_POST (the caller has already
 * checked the nonce).
 *
 * @param array  $src Source array (already unslashed).
 * @param string $key Key.
 */
function spokares_post_str( array $src, string $key ): string {
	return isset( $src[ $key ] ) && is_scalar( $src[ $key ] ) ? trim( (string) $src[ $key ] ) : '';
}

/**
 * Verify a screen's form: capability first, then the nonce. Dies otherwise.
 *
 * @param string $action Nonce action (same as the admin-post action).
 * @param string $cap    Capability.
 */
function spokares_verify_form( string $action, string $cap ): void {
	if ( ! current_user_can( $cap ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to change this.', 'spokares-core' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( $action );
}

/**
 * Back to a screen after saving (post/redirect/get).
 *
 * @param string $page  Admin page slug.
 * @param array  $args  Extra query args.
 */
function spokares_redirect_to( string $page, array $args = array() ): void {
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * "Last saved {when} by {name}" for a stamp ['at' => ISO, 'by' => user].
 *
 * @param array $stamp Stamp.
 */
function spokares_saved_line( array $stamp ): string {
	if ( empty( $stamp['at'] ) ) {
		return '';
	}
	$ts = strtotime( (string) $stamp['at'] );
	if ( ! $ts ) {
		return '';
	}
	return sprintf(
		/* translators: 1: date and time, 2: a person's display name. */
		__( 'Last saved %1$s by %2$s', 'spokares-core' ),
		wp_date( 'D, M j, g:i A', $ts ),
		spokares_user_name( (int) ( $stamp['by'] ?? 0 ) )
	);
}

/**
 * A stamp for now.
 */
function spokares_stamp(): array {
	return array(
		'at' => wp_date( 'c' ),
		'by' => get_current_user_id(),
	);
}

/* -------------------------------------------------------------- help tabs */

/**
 * The Help ("How to") lines of each screen. Help says only what the screen
 * doesn't (R3): a screen whose fields say it all has no lines, and its tab
 * holds just "Stuck?".
 */
function spokares_help_lines(): array {
	return array(
		'spokares-rota'          => array(
			__( 'Winlink assignments and forms show on the Exercises & events page; notes show on the For members page.', 'spokares-core' ),
		),
		'spokares-net-details'   => array(
			__( 'These settings print on the For members, How it works and Home pages at once.', 'spokares-core' ),
		),
		'spokares-meetings'      => array(
			current_user_can( 'spokares_edit_net_details' )
				? __( 'Regular days and times are on the Meeting Schedule screen. The meeting place: ask the webmaster.', 'spokares-core' )
				: __( 'Regular days and times, and the meeting place: ask the webmaster.', 'spokares-core' ),
		),
		'spokares-meeting-rules' => array(),
		'spokares-tiles'         => array(
			__( 'Only published documents with a file or a link are offered.', 'spokares-core' ),
		),
		'spokares-site'          => array(
			__( 'The meeting place prints on Home under “In person”.', 'spokares-core' ),
			__( 'The groups.io links are part of the page text and the theme’s footer and members menu, not settings here.', 'spokares-core' ),
		),
		'spk_event'              => array(),
		'spk_event_list'         => array(
			__( 'Called off? Open it and tick Cancelled under When.', 'spokares-core' ),
			__( 'Same event next year? Open last year’s and click Make a copy.', 'spokares-core' ),
		),
		'spk_document'           => array(
			__( 'New version? Choose the new file (under Replace with, or Upload a file instead of a link), tick “I checked …” and click Save.', 'spokares-core' ),
		),
		'spk_document_list'      => array(),
		'page_list'              => array(
			__( 'Words only. The members pages are built on the Net Control Schedule, Exercises & Events, Meetings and Documents screens.', 'spokares-core' ),
		),
		'dashboard'              => array(
			__( 'Changes show on the site as soon as you save.', 'spokares-core' ),
		),
		'dashboard-none'         => array(
			__( 'This account can’t change anything on the site.', 'spokares-core' ),
			__( 'Your profile has your name, e-mail, password and two-step sign-in.', 'spokares-core' ),
		),
		'profile'                => array(
			__( 'Name: how your name shows to the other editors.', 'spokares-core' ),
			__( 'E-mail: where password resets and sign-in codes go. It is never shown on the site.', 'spokares-core' ),
			__( 'New password: click Set New Password, then Update Profile.', 'spokares-core' ),
			__( 'New phone? Under Authenticator App click Reset authenticator app, scan the code with the new phone, type its 6 digits and click Verify.', 'spokares-core' ),
		),
	);
}

/**
 * Add the help tab for a screen.
 *
 * @param string $key Screen key.
 */
function spokares_screen_help( string $key ): void {
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	$lines = spokares_help_lines()[ $key ] ?? array();
	$html  = '';
	if ( $lines ) {
		$html = '<ul>';
		foreach ( $lines as $line ) {
			$html .= '<li>' . esc_html( $line ) . '</li>';
		}
		$html .= '</ul>';
	}
	// Every screen's tab ends with (or is only) the way to get help.
	$html .= '<p>' . esc_html__( 'Stuck?', 'spokares-core' ) . ' <a href="mailto:webmaster@spokares.org">webmaster@spokares.org</a></p>';
	$screen->add_help_tab(
		array(
			'id'      => 'spokares-help',
			'title'   => __( 'How to', 'spokares-core' ),
			'content' => $html,
		)
	);
}

/**
 * Help on the event and document lists ('{type}_list') and forms ('{type}'),
 * and, for editors, on the Page Text list ('page_list').
 */
function spokares_cpt_help(): void {
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	if ( in_array( $screen->post_type, array( 'spk_event', 'spk_document' ), true ) ) {
		if ( 'edit' === $screen->base ) {
			spokares_screen_help( $screen->post_type . '_list' );
		} elseif ( 'post' === $screen->base ) {
			spokares_screen_help( $screen->post_type );
		}
	} elseif ( 'page' === $screen->post_type && 'edit' === $screen->base && ! current_user_can( 'manage_options' ) ) {
		spokares_screen_help( 'page_list' );
	}
}
add_action( 'current_screen', 'spokares_cpt_help' );

/* ---------------------------------------------------------------- save box */

/**
 * Our Save box (replaces WordPress's Publish box on events and documents,
 * §3.9): where the item stands, Save (published) or Save draft and Publish,
 * the caller's links (e.g. Make a copy), then Take it off the site (a
 * published item) or Move to Trash (a draft). No Preview, no Visibility, no
 * publish date.
 *
 * @param WP_Post $post  Post.
 * @param array   $links Links under the buttons, each ['label' => …, 'url' => …].
 */
function spokares_render_save_box( WP_Post $post, array $links = array() ): void {
	$status    = $post->post_status;
	$published = 'publish' === $status;
	$type      = get_post_type_object( $post->post_type );
	$can_pub   = $type && current_user_can( $type->cap->publish_posts );
	$labels    = array(
		'publish'    => __( 'On the site', 'spokares-core' ),
		'draft'      => __( 'Not on the site (draft)', 'spokares-core' ),
		'auto-draft' => __( 'Not saved yet', 'spokares-core' ),
		'pending'    => __( 'Waiting for review', 'spokares-core' ),
	);
	// id "submitpost": WordPress's post.js binds its submit handling to the
	// buttons in #submitpost (stop autosave, drop the "Leave site? Changes you
	// made may not be saved" warning, block double clicks). Without it every
	// Publish or Save of a changed event or document asked to leave the page.
	?>
	<div class="spk-savebox" id="submitpost">
		<input type="hidden" name="original_post_status" value="<?php echo esc_attr( $status ); ?>">
		<input type="hidden" name="post_status" value="<?php echo esc_attr( 'auto-draft' === $status ? 'draft' : $status ); ?>">
		<p class="spk-status"><?php echo esc_html( $labels[ $status ] ?? $status ); ?></p>
		<p class="spk-save-buttons">
			<?php if ( $published ) : ?>
				<?php // "save", as WordPress's own Update button: the item stays published. ?>
				<input type="submit" name="save" class="button button-primary button-large" value="<?php esc_attr_e( 'Save', 'spokares-core' ); ?>">
			<?php else : ?>
				<?php // A draft may be saved half-filled: the browser's required checks are for Publish. ?>
				<input type="submit" name="saveasdraft" class="button button-large" value="<?php esc_attr_e( 'Save draft', 'spokares-core' ); ?>" formnovalidate>
				<?php if ( $can_pub ) : ?>
					<input type="submit" name="publish" class="button button-primary button-large" value="<?php esc_attr_e( 'Publish', 'spokares-core' ); ?>">
				<?php endif; ?>
			<?php endif; ?>
		</p>
		<?php foreach ( $links as $link ) : ?>
			<?php
			$label = is_array( $link ) ? trim( (string) ( $link['label'] ?? '' ) ) : '';
			$url   = is_array( $link ) ? (string) ( $link['url'] ?? '' ) : '';
			if ( '' === $label || '' === $url ) {
				continue;
			}
			?>
			<p class="spk-savebox-link"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></p>
		<?php endforeach; ?>
		<?php if ( 'auto-draft' !== $status && current_user_can( 'delete_post', $post->ID ) ) : ?>
			<p class="spk-trash"><a class="submitdelete" id="spk-trash-link" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $published ? __( 'Take it off the site', 'spokares-core' ) : __( 'Move to Trash', 'spokares-core' ) ); ?></a></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Remove WordPress's Publish box (and the slug box) on our types. Documents
 * have their own "Link name (slug)" field for administrators (Admin only);
 * core's hidden slug box sent a second post_name later in the form, so its
 * old value always won and the typed link name was ignored.
 */
function spokares_replace_submitdiv(): void {
	foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
		remove_meta_box( 'submitdiv', $type, 'side' );
		if ( 'spk_document' === $type || ! current_user_can( 'manage_options' ) ) {
			remove_meta_box( 'slugdiv', $type, 'normal' );
		}
		remove_meta_box( 'pageparentdiv', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'spokares_replace_submitdiv', 99 );

/**
 * The name box of an event or document shows the name as typed. Nobody holds
 * unfiltered_html here, so core stores "Q&A" as "Q&amp;A", and the edit
 * screen escapes that again ("Q&amp;A" in the box). Decode it before core
 * escapes it for the box; markup never gets in (the stored title is filtered,
 * and the box escapes what it shows).
 *
 * @param string $title   Stored title.
 * @param int    $post_id Post.
 */
function spokares_edit_title_as_typed( $title, $post_id ) {
	if ( ! in_array( get_post_type( (int) $post_id ), array( 'spk_event', 'spk_document' ), true ) ) {
		return $title;
	}
	return html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' );
}
add_filter( 'edit_post_title', 'spokares_edit_title_as_typed', 10, 2 );

/* ------------------------------------------------------------------ assets */

/**
 * Admin CSS everywhere (small), and the forms script on our screens.
 *
 * @param string $hook Screen hook.
 */
function spokares_admin_assets( $hook ): void {
	wp_enqueue_style( 'spokares-admin', SPOKARES_CORE_URL . 'assets/css/admin.css', array(), spokares_asset_version( 'assets/css/admin.css' ) );

	$screen = get_current_screen();
	$ours   = $screen && (
		in_array( $screen->post_type, array( 'spk_event', 'spk_document' ), true )
		|| str_contains( (string) $hook, 'spokares-' )
	);
	if ( ! $ours ) {
		return;
	}
	wp_enqueue_script( 'spokares-admin-forms', SPOKARES_CORE_URL . 'assets/js/admin-forms.js', array(), spokares_asset_version( 'assets/js/admin-forms.js' ), true );
	// The shared config. Each area adds its own object (spokaresRota,
	// spokaresNet, spokaresEvents, spokaresDocs) from its own file (§4.10).
	wp_add_inline_script(
		'spokares-admin-forms',
		'window.spokaresAdmin = ' . wp_json_encode(
			array(
				'today'    => spokares_today(),
				'weekdays' => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
				// The amateur bands Net Settings accepts (the live check mirrors the save).
				'bands'    => function_exists( 'spokares_amateur_bands' ) ? spokares_amateur_bands() : array(),
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'spokares_admin_assets' );
