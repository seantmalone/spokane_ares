<?php
/**
 * The Page review box (§3.4): beside a page in the block editor, "Last
 * reviewed" and the page owner, with "Mark reviewed today when I save".
 * A classic side meta box, so no build step.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the box to pages.
 */
function spokares_page_review_box(): void {
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
		<input type="text" id="spk-owner" name="spk_page_owner" class="widefat<?php echo is_array( $refused ) ? ' spk-field-error' : ''; ?>" maxlength="60" value="<?php echo esc_attr( $owner ); ?>" placeholder="<?php esc_attr_e( 'Emergency Coordinator', 'spokares-core' ); ?>" aria-describedby="spk-owner-problem">
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
