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
	wp_nonce_field( 'spokares_page_review', 'spokares_page_review_nonce' );
	?>
	<p><?php esc_html_e( 'Last reviewed:', 'spokares-core' ); ?>
		<strong><?php echo spokares_is_ymd( $reviewed ) ? esc_html( spokares_fmt_date( $reviewed, 'mdy' ) ) : esc_html__( 'not yet', 'spokares-core' ); ?></strong></p>
	<p><label for="spk-owner"><?php esc_html_e( 'Page owner', 'spokares-core' ); ?></label><br>
		<input type="text" id="spk-owner" name="spk_page_owner" class="widefat" maxlength="60" value="<?php echo esc_attr( $owner ); ?>" placeholder="<?php esc_attr_e( 'Emergency Coordinator', 'spokares-core' ); ?>"></p>
	<p><label><input type="checkbox" name="spk_mark_reviewed" value="1"> <?php esc_html_e( 'Mark reviewed today when I save', 'spokares-core' ); ?></label></p>
	<?php
}

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
	if ( ! $check ) {
		if ( '' === $owner ) {
			delete_post_meta( $post_id, '_spk_owner' );
		} else {
			update_post_meta( $post_id, '_spk_owner', $owner );
		}
	}
	if ( ! empty( $_POST['spk_mark_reviewed'] ) ) {
		update_post_meta( $post_id, '_spk_reviewed', spokares_today() );
	}
	spokares_purge_cache();
}
add_action( 'save_post_page', 'spokares_save_page_review' );
