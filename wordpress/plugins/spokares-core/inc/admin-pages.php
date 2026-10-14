<?php
/**
 * Page Text for editors (UX spec §3.8): the Pages list trimmed to the three
 * pages with words to change, and the Page review box (PLAN §3.4) beside
 * About in the block editor: "Last reviewed" and the page owner, with "Mark
 * reviewed today when I save". A classic side meta box, so no build step.
 * The page type's labels ("Page Text") are set in governance.php, which is
 * loaded on the site and in REST too.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the current user trimmed to Page Text (anyone but an administrator)?
 */
function spokares_page_text_trimmed(): bool {
	return is_user_logged_in() && ! current_user_can( 'manage_options' );
}

/**
 * Is this request the Pages list (edit.php?post_type=page)?
 */
function spokares_is_page_list(): bool {
	global $pagenow;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
	return 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'page' === sanitize_key( wp_unslash( $_GET['post_type'] ) );
}

/**
 * The list shows Home, How it works and About only: the three members pages
 * have no words to edit (their templates hold everything), so their rows
 * were dead ends.
 *
 * @param WP_Query $query Query.
 */
function spokares_page_list_query( $query ): void {
	if ( ! $query instanceof WP_Query || ! $query->is_main_query() || ! spokares_is_page_list() || ! spokares_page_text_trimmed() ) {
		return;
	}
	$query->set( 'post__not_in', array_merge( (array) $query->get( 'post__not_in' ), spokares_members_page_ids() ) );
}
add_action( 'pre_get_posts', 'spokares_page_list_query' );

/**
 * The Title column only: no checkboxes (there is nothing to do to several
 * pages at once), Author or Date.
 *
 * @param array $columns Columns.
 */
function spokares_page_list_columns( $columns ) {
	if ( ! is_array( $columns ) || ! spokares_page_text_trimmed() ) {
		return $columns;
	}
	return array_intersect_key( $columns, array( 'title' => true ) );
}
add_filter( 'manage_page_posts_columns', 'spokares_page_list_columns', 20 );

/**
 * No sorting (three pages), so the Title heading is plain words.
 *
 * @param array $columns Sortable columns.
 */
function spokares_page_list_sortable( $columns ) {
	return spokares_page_text_trimmed() ? array() : $columns;
}
add_filter( 'manage_edit-page_sortable_columns', 'spokares_page_list_sortable', 20 );

/**
 * No date filter.
 *
 * @param bool   $disable   Whether to hide the months dropdown.
 * @param string $post_type Post type.
 */
function spokares_page_list_no_months( $disable, $post_type ) {
	return 'page' === $post_type && spokares_page_text_trimmed() ? true : $disable;
}
add_filter( 'disable_months_dropdown', 'spokares_page_list_no_months', 10, 2 );

/**
 * No "All (6) | Published (6)" line: it counted the members pages too.
 *
 * @param array $views Views.
 */
function spokares_page_list_views( $views ) {
	return spokares_page_text_trimmed() ? array() : $views;
}
add_filter( 'views_edit-page', 'spokares_page_list_views', 20 );

/**
 * No "— Front Page" after Home: WordPress words for a fact editors can't change.
 *
 * @param array   $states Post states.
 * @param WP_Post $post   Post.
 */
function spokares_page_list_states( $states, $post ) {
	if ( ! is_array( $states ) || ! $post instanceof WP_Post || 'page' !== $post->post_type || ! spokares_page_text_trimmed() ) {
		return $states;
	}
	unset( $states['page_on_front'], $states['page_for_posts'] );
	return $states;
}
add_filter( 'display_post_states', 'spokares_page_list_states', 20, 2 );

/**
 * No search box for three pages ("Search Pages" also named the list the old way).
 */
function spokares_page_list_style(): void {
	if ( ! spokares_is_page_list() || ! spokares_page_text_trimmed() ) {
		return;
	}
	echo '<style>.post-type-page #posts-filter .search-box{display:none}</style>' . "\n";
}
add_action( 'admin_head-edit.php', 'spokares_page_list_style' );

/**
 * Add the box to About (the one page that prints its review line).
 *
 * @param WP_Post $post Page.
 */
function spokares_page_review_box( $post ): void {
	if ( ! spokares_page_has_review_box( $post ) ) {
		return;
	}
	add_meta_box( 'spokares_page_review', __( 'Page review', 'spokares-core' ), 'spokares_render_page_review', 'page', 'side', 'default' );
}
add_action( 'add_meta_boxes_page', 'spokares_page_review_box' );

/**
 * The box.
 *
 * @param WP_Post $post Page.
 */
function spokares_render_page_review( WP_Post $post ): void {
	$reviewed = (string) get_post_meta( $post->ID, '_spk_reviewed', true );
	$owner    = (string) get_post_meta( $post->ID, '_spk_owner', true );
	$key      = 'spokares_owner_refused_' . get_current_user_id() . '_' . $post->ID;
	$refused  = get_transient( $key );
	if ( is_array( $refused ) ) {
		delete_transient( $key );
		$owner = (string) ( $refused['typed'] ?? $owner );
	}
	wp_nonce_field( 'spokares_page_review', 'spokares_page_review_nonce' );
	?>
	<p><?php esc_html_e( 'Last reviewed:', 'spokares-core' ); ?>
		<strong class="spk-reviewed-date"><?php echo spokares_is_ymd( $reviewed ) ? esc_html( spokares_fmt_date( $reviewed, 'mdy' ) ) : esc_html__( 'not yet', 'spokares-core' ); ?></strong></p>
	<p><label for="spk-owner"><?php esc_html_e( 'Page owner', 'spokares-core' ); ?></label><br>
		<input type="text" id="spk-owner" name="spk_page_owner" class="widefat<?php echo is_array( $refused ) ? ' spk-field-error' : ''; ?>" maxlength="60" value="<?php echo esc_attr( $owner ); ?>" aria-describedby="spk-owner-problem">
		<span class="spk-error-text" id="spk-owner-problem" <?php echo is_array( $refused ) ? '' : 'hidden'; ?>><?php echo is_array( $refused ) ? esc_html( (string) ( $refused['text'] ?? '' ) ) : ''; ?></span></p>
	<p><label><input type="checkbox" name="spk_mark_reviewed" value="1"> <?php esc_html_e( 'Mark reviewed today when I save', 'spokares-core' ); ?></label></p>
	<?php
}

/**
 * The sentence for a Page owner that wasn't saved.
 *
 * @param array $hits spokares_check_text() hits.
 */
function spokares_page_owner_problem( array $hits ): string {
	return sprintf(
		/* translators: %s: what was found, e.g. "a phone number". */
		__( 'The page owner wasn’t saved: it mentions %s. Type a role (like “Emergency Coordinator”), not a person’s contact details.', 'spokares-core' ),
		spokares_and_list( wp_list_pluck( $hits, 'what' ) )
	);
}

/**
 * The block editor saves this classic box in the background and never draws
 * it again: after that save, untick "Mark reviewed today", show the new
 * date, and say if the Page owner wasn't saved (the same checks as the
 * server's), so the box isn't stale.
 *
 * @param string $hook Screen hook.
 */
function spokares_page_review_script( $hook ): void {
	$screen = get_current_screen();
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'page' !== $screen->post_type ) {
		return;
	}
	$data = array(
		'today'    => spokares_fmt_date( spokares_today(), 'mdy' ),
		'patterns' => array_map(
			static fn( $p ) => array(
				're'   => $p['re'],
				'what' => $p['what'],
			),
			spokares_text_patterns()
		),
		/* translators: %s: what was found, e.g. "a phone number". */
		'refused'  => __( 'The page owner wasn’t saved: it mentions %s. Type a role (like “Emergency Coordinator”), not a person’s contact details.', 'spokares-core' ),
	);
	$js   = '( function ( cfg ) {'
		. 'if ( ! window.wp || ! wp.data ) { return; }'
		. 'var was = false;'
		. 'wp.data.subscribe( function () {'
		. 'var ed = wp.data.select( "core/edit-post" ); if ( ! ed || ! ed.isSavingMetaBoxes ) { return; }'
		. 'var saving = ed.isSavingMetaBoxes();'
		. 'if ( was && ! saving ) {'
		. 'var box = document.getElementById( "spokares_page_review" ); if ( ! box ) { was = saving; return; }'
		. 'var tick = box.querySelector( "input[name=spk_mark_reviewed]" ), date = box.querySelector( ".spk-reviewed-date" );'
		. 'if ( tick && tick.checked ) { tick.checked = false; if ( date ) { date.textContent = cfg.today; } }'
		. 'var owner = box.querySelector( "#spk-owner" ), out = box.querySelector( "#spk-owner-problem" );'
		. 'if ( owner && out ) {'
		. 'var text = owner.value.replace( /[A-Z0-9._%+-]+@spokares\.org\b/gi, " " ), hits = [];'
		. 'cfg.patterns.forEach( function ( p ) { try { if ( new RegExp( p.re, "i" ).test( text ) ) { hits.push( p.what ); } } catch ( e ) {} } );'
		. 'out.textContent = hits.length ? cfg.refused.replace( "%s", hits.join( " and " ) ) : "";'
		. 'out.hidden = ! hits.length; owner.classList.toggle( "spk-field-error", !! hits.length );'
		. 'if ( hits.length ) { owner.setAttribute( "aria-invalid", "true" ); } else { owner.removeAttribute( "aria-invalid" ); }'
		. '}'
		. '}'
		. 'was = saving;'
		. '} );'
		. '} )( ' . wp_json_encode( $data ) . ' );';
	wp_add_inline_script( 'wp-edit-post', $js, 'after' );
}
add_action( 'admin_enqueue_scripts', 'spokares_page_review_script' );

/**
 * Save the box (the block editor posts meta boxes separately; this runs then).
 *
 * @param int $post_id Page.
 */
function spokares_save_page_review( $post_id ): void {
	$post_id = (int) $post_id;
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['spokares_page_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spokares_page_review_nonce'] ) ), 'spokares_page_review' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$owner = isset( $_POST['spk_page_owner'] ) ? sanitize_text_field( wp_unslash( $_POST['spk_page_owner'] ) ) : '';
	$check = spokares_check_text( $owner );
	$key   = 'spokares_owner_refused_' . get_current_user_id() . '_' . $post_id;
	if ( ! $check ) {
		delete_transient( $key );
		if ( '' === $owner ) {
			delete_post_meta( $post_id, '_spk_owner' );
		} else {
			update_post_meta( $post_id, '_spk_owner', wp_slash( $owner ) );
		}
	} else {
		// Not saved: say so in the box (the next time it is drawn), with the typing kept.
		set_transient(
			$key,
			array(
				'typed' => $owner,
				'text'  => spokares_page_owner_problem( $check ),
			),
			5 * MINUTE_IN_SECONDS
		);
	}
	if ( ! empty( $_POST['spk_mark_reviewed'] ) ) {
		update_post_meta( $post_id, '_spk_reviewed', spokares_today() );
	}
	spokares_purge_cache();
}
add_action( 'save_post_page', 'spokares_save_page_review' );

/* ------------------------------------------------------ earlier versions */

/**
 * An editor restores an earlier version of Home, How it works or About
 * (Dashboard › Page Text › Earlier versions, then core's Compare Revisions
 * screen and its Restore This Revision button). Core's restore would copy
 * the version's title, words and search-engine description over the page,
 * and the save floor (spokares_page_insert_guard()) would quietly keep the
 * page when the layout differs while core still said "restored". So for
 * non-admins this answers the restore first: a version whose layout matches
 * the page now (spokares_revision_restore_problem()) gets its words back,
 * and nothing else; any other gets one sentence saying why not. Core's own
 * checks (the revision exists, edit_post, the edit lock) still decide first.
 */
function spokares_revision_restore_for_editors(): void {
	if ( ! spokares_layout_locked_for_user() ) {
		return;
	}
	// Restore This Revision is a link (GET).
	// phpcs:disable WordPress.Security.NonceVerification -- routing only; the restore's nonce is checked below, as core checks it.
	$action      = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$revision_id = isset( $_GET['revision'] ) ? absint( $_GET['revision'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification
	if ( 'restore' !== $action || ! $revision_id ) {
		return;
	}
	$revision = wp_get_post_revision( $revision_id );
	$page     = $revision ? get_post( $revision->post_parent ) : null;
	if ( ! $revision || ! $page || 'page' !== $page->post_type ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $page->ID ) || wp_check_post_lock( $page->ID ) ) {
		return; // Core refuses these itself.
	}
	check_admin_referer( 'restore-post_' . $revision->ID );
	$problem = spokares_revision_restore_problem( $revision, $page );
	if ( '' !== $problem ) {
		wp_die(
			esc_html( spokares_revision_restore_refusal( $problem, $revision, $page ) ),
			esc_html__( 'Not restored', 'spokares-core' ),
			array(
				'response'  => 403,
				'link_url'  => esc_url_raw( add_query_arg( 'revision', $revision->ID, admin_url( 'revision.php' ) ) ),
				'link_text' => esc_html__( 'Back to the earlier versions', 'spokares-core' ),
			)
		);
	}
	wp_restore_post_revision( $revision->ID, array( 'post_content' ) );
	spokares_purge_cache();
	wp_safe_redirect(
		add_query_arg(
			array(
				'message'  => 5,
				'revision' => $revision->ID,
			),
			get_edit_post_link( $page->ID, 'url' )
		)
	);
	exit;
}
add_action( 'load-revision.php', 'spokares_revision_restore_for_editors' );

/**
 * The newest earlier version of a page, for the "Earlier versions" link
 * (core's Compare Revisions screen opens on it). 0 when there is none.
 *
 * @param int $page_id Page.
 */
function spokares_page_latest_revision( int $page_id ): int {
	if ( ! wp_revisions_enabled( get_post( $page_id ) ) ) {
		return 0;
	}
	$ids = wp_get_post_revisions(
		$page_id,
		array(
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'check_enabled'  => false,
		)
	);
	return $ids ? (int) reset( $ids ) : 0;
}
