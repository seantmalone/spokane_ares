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
 * The Net rota menu and the plugin's submenus. Each screen's file provides
 * its page callback and load hook.
 */
function spokares_admin_menu(): void {
	$hooks = array();

	$hooks['spokares-rota'] = add_menu_page(
		__( 'Net rota', 'spokares-core' ),
		__( 'Net rota', 'spokares-core' ),
		'spokares_edit_rota',
		'spokares-rota',
		'spokares_rota_page',
		'dashicons-microphone',
		3
	);
	add_submenu_page( 'spokares-rota', __( 'Net rota', 'spokares-core' ), __( 'Net rota', 'spokares-core' ), 'spokares_edit_rota', 'spokares-rota', 'spokares_rota_page' );
	$hooks['spokares-net-details'] = add_submenu_page( 'spokares-rota', __( 'Net details', 'spokares-core' ), __( 'Net details', 'spokares-core' ), 'spokares_edit_net_details', 'spokares-net-details', 'spokares_net_details_page' );

	$events                          = 'edit.php?post_type=spk_event';
	$hooks['spokares-meetings']      = add_submenu_page( $events, __( 'Regular meetings', 'spokares-core' ), __( 'Regular meetings', 'spokares-core' ), 'spokares_edit_rota', 'spokares-meetings', 'spokares_meetings_page' );
	$hooks['spokares-meeting-rules'] = add_submenu_page( $events, __( 'Meeting rules', 'spokares-core' ), __( 'Meeting rules', 'spokares-core' ), 'spokares_edit_net_details', 'spokares-meeting-rules', 'spokares_meeting_rules_page' );

	$hooks['spokares-tiles'] = add_submenu_page( 'edit.php?post_type=spk_document', __( 'Hub tiles', 'spokares-core' ), __( 'Hub tiles', 'spokares-core' ), 'edit_spk_documents', 'spokares-tiles', 'spokares_tiles_page' );
	$hooks['spokares-site']  = add_options_page( __( 'ARES site', 'spokares-core' ), __( 'ARES site', 'spokares-core' ), 'manage_options', 'spokares-site', 'spokares_site_page' );

	foreach ( $hooks as $slug => $hook ) {
		if ( $hook ) {
			add_action( 'load-' . $hook, static fn() => spokares_screen_help( $slug ) );
		}
	}
}
add_action( 'admin_menu', 'spokares_admin_menu' );

/**
 * Use our menu order.
 */
add_filter( 'custom_menu_order', '__return_true' );

/**
 * Dashboard · Net rota · Events · Documents · Pages first, then the rest.
 *
 * @param string[] $order Menu slugs.
 */
function spokares_menu_order( $order ) {
	if ( ! is_array( $order ) ) {
		return $order;
	}
	$want  = array( 'index.php', 'separator1', 'spokares-rota', 'edit.php?post_type=spk_event', 'edit.php?post_type=spk_document', 'edit.php?post_type=page' );
	$front = array_values( array_filter( $want, static fn( $slug ) => in_array( $slug, $order, true ) ) );
	return array_merge( $front, array_values( array_diff( $order, $front ) ) );
}
add_filter( 'menu_order', 'spokares_menu_order' );

/* ----------------------------------------------------------------- notices */

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
		echo '<span class="spk-error-text">' . esc_html( $errors[ $key ] ) . '</span>';
	}
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
 * The help lines of each screen (3-5 lines each).
 */
function spokares_help_lines(): array {
	return array(
		'spokares-rota'          => array(
			__( 'Each row is one Tuesday net. Type a call sign, or choose Open (ask for a volunteer) or Not posted yet.', 'spokares-core' ),
			__( 'Call signs only, never names. If you type more, only the first call sign is kept.', 'spokares-core' ),
			__( 'On Winlink nights, type the assignment and pick the form. The Note appears in the rota’s Note column.', 'spokares-core' ),
			__( 'Only the rows you changed are saved. Made a mistake? Press Undo last save.', 'spokares-core' ),
		),
		'spokares-net-details'   => array(
			__( 'These facts print on the members hub, How it works and Home at once.', 'spokares-core' ),
			__( 'Amateur frequencies only. Never county, hospital, SHARES, 800 MHz or channel numbers.', 'spokares-core' ),
			__( 'Check the preview at the bottom before you save.', 'spokares-core' ),
		),
		'spokares-meetings'      => array(
			__( 'Tick Cancelled beside a date, or type the new date in Moved to. Add a short note if it helps.', 'spokares-core' ),
			__( 'Home shows the next real date, and the members hub shows the change for two weeks before it.', 'spokares-core' ),
			__( 'Meeting times and weeks are on Meeting rules (ask the webmaster).', 'spokares-core' ),
		),
		'spokares-meeting-rules' => array(
			__( 'Each row is a regular meeting: the weeks of the month, the day and the times.', 'spokares-core' ),
			__( 'Time words (for example “evenings”) replace the times on Home.', 'spokares-core' ),
			__( 'Untick Active to stop showing a meeting. To cancel one date, use Regular meetings instead.', 'spokares-core' ),
		),
		'spokares-tiles'         => array(
			__( 'The four “Most used” tiles on For members. Each opens its document.', 'spokares-core' ),
			__( 'Only published, privacy-checked documents with a file or a link can be a tile.', 'spokares-core' ),
			__( 'Choosing a document that is already in another slot swaps the two slots.', 'spokares-core' ),
		),
		'spokares-site'          => array(
			__( 'The groups.io addresses and the meeting place used across the site.', 'spokares-core' ),
			__( 'The meeting place prints on Home under “In person”.', 'spokares-core' ),
		),
		'spk_event'              => array(
			__( 'Pick the kind first. Fields that don’t apply to that kind are hidden.', 'spokares-core' ),
			__( 'All day is ticked by default; untick it to add times.', 'spokares-core' ),
			__( 'A yearly event? Duplicate last year’s from All events, change the date, and Publish.', 'spokares-core' ),
			__( 'Never publish county, hospital, SHARES or 800 MHz channels, or names, phones or e-mails.', 'spokares-core' ),
		),
		'spk_document'           => array(
			__( 'Pick the section, then where the file is: upload it, link to another site, or “Soon”.', 'spokares-core' ),
			__( 'Tick the Privacy check first; the file chooser unlocks and the file uploads when you save.', 'spokares-core' ),
			__( 'New version? Use Replace with; the old file is removed from the web and the link stays the same.', 'spokares-core' ),
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
	$lines  = spokares_help_lines()[ $key ] ?? array();
	if ( ! $screen || ! $lines ) {
		return;
	}
	$html = '<ul>';
	foreach ( $lines as $line ) {
		$html .= '<li>' . esc_html( $line ) . '</li>';
	}
	$html .= '</ul><p>' . esc_html__( 'Stuck?', 'spokares-core' ) . ' <a href="mailto:webmaster@spokares.org">webmaster@spokares.org</a></p>';
	$screen->add_help_tab(
		array(
			'id'      => 'spokares-help',
			'title'   => __( 'How to', 'spokares-core' ),
			'content' => $html,
		)
	);
}

/**
 * Help on the event and document screens.
 */
function spokares_cpt_help(): void {
	$screen = get_current_screen();
	if ( $screen && in_array( $screen->post_type, array( 'spk_event', 'spk_document' ), true ) && in_array( $screen->base, array( 'post', 'edit' ), true ) ) {
		spokares_screen_help( $screen->post_type );
	}
}
add_action( 'current_screen', 'spokares_cpt_help' );

/* ---------------------------------------------------------------- save box */

/**
 * Our Save box (replaces WordPress's Publish box on events and documents):
 * Save draft, Publish (Update once published) and Move to Trash. No Preview,
 * no Visibility, no publish date.
 *
 * @param WP_Post $post       Post.
 * @param string  $trash_note Optional control under the trash link (documents).
 */
function spokares_render_save_box( WP_Post $post, string $trash_note = '' ): void {
	$status    = $post->post_status;
	$published = 'publish' === $status;
	$type      = get_post_type_object( $post->post_type );
	$can_pub   = $type && current_user_can( $type->cap->publish_posts );
	$labels    = array(
		'publish'    => __( 'Published: on the site', 'spokares-core' ),
		'draft'      => __( 'Draft: not on the site', 'spokares-core' ),
		'auto-draft' => __( 'New: not saved yet', 'spokares-core' ),
		'pending'    => __( 'Waiting for review', 'spokares-core' ),
	);
	// id "submitpost": WordPress's post.js binds its submit handling to the
	// buttons in #submitpost (stop autosave, drop the "Leave site? Changes you
	// made may not be saved" warning, block double clicks). Without it every
	// Publish or Update of a changed event or document asked to leave the page.
	?>
	<div class="spk-savebox" id="submitpost">
		<input type="hidden" name="original_post_status" value="<?php echo esc_attr( $status ); ?>">
		<input type="hidden" name="post_status" value="<?php echo esc_attr( 'auto-draft' === $status ? 'draft' : $status ); ?>">
		<p class="spk-status"><?php echo esc_html__( 'Status:', 'spokares-core' ) . ' ' . esc_html( $labels[ $status ] ?? $status ); ?></p>
		<p class="spk-save-buttons">
			<?php if ( $published ) : ?>
				<?php // "save", as WordPress's own Update button: the notice then reads "Saved.", not "Published.". ?>
				<input type="submit" name="save" class="button button-primary button-large" value="<?php esc_attr_e( 'Update', 'spokares-core' ); ?>">
			<?php else : ?>
				<input type="submit" name="saveasdraft" class="button button-large" value="<?php esc_attr_e( 'Save draft', 'spokares-core' ); ?>">
				<?php if ( $can_pub ) : ?>
					<input type="submit" name="publish" class="button button-primary button-large" value="<?php esc_attr_e( 'Publish', 'spokares-core' ); ?>">
				<?php endif; ?>
			<?php endif; ?>
		</p>
		<?php if ( $published && 'spk_document' === $post->post_type ) : ?>
			<?php // A draft document leaves the lists and /docs/, but its uploaded file keeps its web address. ?>
			<p class="spk-unpublish"><input type="submit" name="saveasdraft" class="button-link" value="<?php esc_attr_e( 'Save as draft (takes it off the lists)', 'spokares-core' ); ?>"></p>
			<?php if ( absint( get_post_meta( $post->ID, 'spk_file', true ) ) ) : ?>
				<p class="spk-unpublish-note"><?php esc_html_e( 'An uploaded file stays at its web address. To take it off the web too, Move to Trash with “Also remove its file from the web” ticked, or ask the webmaster to pull it.', 'spokares-core' ); ?></p>
			<?php endif; ?>
		<?php elseif ( $published ) : ?>
			<p class="spk-unpublish"><input type="submit" name="saveasdraft" class="button-link" value="<?php esc_attr_e( 'Save as draft (takes it off the site)', 'spokares-core' ); ?>"></p>
		<?php endif; ?>
		<?php if ( 'auto-draft' !== $status && current_user_can( 'delete_post', $post->ID ) ) : ?>
			<p class="spk-trash"><a class="submitdelete" id="spk-trash-link" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php esc_html_e( 'Move to Trash', 'spokares-core' ); ?></a></p>
			<?php
			if ( '' !== $trash_note ) {
				echo wp_kses(
					$trash_note,
					array(
						'p'     => array( 'class' => true ),
						'label' => array(),
						'input' => array(
							'type'    => true,
							'id'      => true,
							'checked' => true,
						),
					)
				);
			}
			?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Remove WordPress's Publish box (and the slug box for non-admins) on our types.
 */
function spokares_replace_submitdiv(): void {
	foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
		remove_meta_box( 'submitdiv', $type, 'side' );
		if ( ! current_user_can( 'manage_options' ) ) {
			remove_meta_box( 'slugdiv', $type, 'normal' );
		}
		remove_meta_box( 'pageparentdiv', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'spokares_replace_submitdiv', 99 );

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
	wp_add_inline_script(
		'spokares-admin-forms',
		'window.spokaresAdmin = ' . wp_json_encode(
			array(
				'today'    => spokares_today(),
				'notYet'   => __( 'Not yet published', 'spokares-core' ),
				'open'     => __( 'Open', 'spokares-core' ),
				'noCall'   => __( 'No call sign: not saved', 'spokares-core' ),
				'confirm'  => __( 'Save these changes? They show on the site at once.', 'spokares-core' ),
				/* translators: %d: a hub tile slot number (1-4). */
				'nowSlot'  => __( '(now slot %d)', 'spokares-core' ),
				'every'    => __( 'Every Tuesday', 'spokares-core' ),
				'weekdays' => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'spokares_admin_assets' );
