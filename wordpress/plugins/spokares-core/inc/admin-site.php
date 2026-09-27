<?php
/**
 * Settings › ARES site (administrators): groups.io addresses and the meeting
 * place (spk_site).
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ARES site screen.
 */
function spokares_site_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$site     = spokares_opt( 'spk_site' );
	$retained = spokares_retained( 'spokares-site' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	$place    = $site['place'];
	$fields   = array(
		'name'   => __( 'Place name', 'spokares-core' ),
		'street' => __( 'Street', 'spokares-core' ),
		'city'   => __( 'City', 'spokares-core' ),
		'state'  => __( 'State', 'spokares-core' ),
		'zip'    => __( 'ZIP code', 'spokares-core' ),
	);
	$addr     = __( 'Web address (copy it from your browser’s address bar)', 'spokares-core' );
	?>
	<div class="wrap spk-screen spk-site">
		<h1><?php esc_html_e( 'ARES site', 'spokares-core' ); ?></h1>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="spokares_save_site">
			<?php wp_nonce_field( 'spokares_save_site' ); ?>
			<h2><?php esc_html_e( 'groups.io', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( array(
					'groupsio_main'  => __( 'Main group', 'spokares-core' ),
					'groupsio_files' => __( 'Member files', 'spokares-core' ),
				) as $key => $label ) :
					?>
					<tr>
						<th scope="row"><label for="spk-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input type="url" class="large-text<?php echo esc_attr( spokares_err_class( $errors, $key ) ); ?>" id="spk-<?php echo esc_attr( $key ); ?>" name="site[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( $held[ $key ] ?? $site[ $key ] ) ); ?>" placeholder="https://">
							<p class="description"><?php echo esc_html( $addr ); ?></p>
							<?php spokares_err_text( $errors, $key ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h2><?php esc_html_e( 'Meeting place (Home, “In person”)', 'spokares-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php foreach ( $fields as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="spk-place-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input type="text" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, 'place_' . $key ) ); ?>" id="spk-place-<?php echo esc_attr( $key ); ?>" name="site[place][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( $held[ 'place_' . $key ] ?? $place[ $key ] ) ); ?>" maxlength="100">
							<?php spokares_err_text( $errors, 'place_' . $key ); ?></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Checking', 'spokares-core' ); ?></th>
					<td><label><input type="checkbox" name="site[place][needs_check]" value="1" <?php checked( $place['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only)', 'spokares-core' ); ?></label></td>
				</tr>
			</table>
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * Save the ARES site settings.
 */
function spokares_handle_save_site(): void {
	spokares_verify_form( 'spokares_save_site', 'manage_options' );
	$in     = isset( $_POST['site'] ) && is_array( $_POST['site'] ) ? wp_unslash( $_POST['site'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$site   = spokares_opt( 'spk_site' );
	$held   = array();
	$errors = array();

	foreach ( array( 'groupsio_main', 'groupsio_files' ) as $key ) {
		$typed = sanitize_text_field( spokares_post_str( $in, $key ) );
		$clean = spokares_clean_url( $typed );
		if ( '' === $typed || '' !== $clean ) {
			$site[ $key ] = $clean;
		} else {
			$held[ $key ]   = $typed;
			$errors[ $key ] = __( 'Not saved: the address must start with https://', 'spokares-core' );
		}
	}
	$place = is_array( $in['place'] ?? null ) ? $in['place'] : array();
	foreach ( array( 'name', 'street', 'city', 'state', 'zip' ) as $key ) {
		$typed = sanitize_text_field( spokares_post_str( $place, $key ) );
		$check = spokares_check_field( $typed, 'place_' . $key );
		if ( $check['block'] || $check['confirm'] ) {
			$held[ 'place_' . $key ]   = $typed;
			$errors[ 'place_' . $key ] = sprintf( /* translators: %s: what was found. */ __( 'Not saved: it mentions %s.', 'spokares-core' ), spokares_and_list( $check['block'] ? $check['block'] : wp_list_pluck( $check['confirm'], 'what' ) ) );
			continue;
		}
		$site['place'][ $key ] = $typed;
	}
	$site['place']['needs_check'] = ! empty( $place['needs_check'] );

	update_option( 'spk_site', $site );
	spokares_purge_cache();
	foreach ( $errors as $message ) {
		spokares_add_notice( 'error', $message );
	}
	spokares_add_notice( $errors ? 'warning' : 'success', $errors ? __( 'Saved, except the fields outlined in red.', 'spokares-core' ) : __( 'Saved.', 'spokares-core' ), spokares_site_url( '/', 'visit' ), __( 'See it on Home', 'spokares-core' ) );
	if ( $errors ) {
		spokares_retain( 'spokares-site', $held, $errors );
	}
	wp_safe_redirect( admin_url( 'options-general.php?page=spokares-site' ) );
	exit;
}
add_action( 'admin_post_spokares_save_site', 'spokares_handle_save_site' );
