<?php
/**
 * Documents › Hub tiles (§3.4): the four "Most used" tiles on For members.
 * Choosing a document that is already in another slot swaps the two slots,
 * so a tile never silently disappears.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Documents that can be a tile: published, privacy-checked, with a file or a link.
 *
 * @return array<int,string> ID => title
 */
function spokares_tile_candidates(): array {
	$out = array();
	foreach ( spokares_library_documents() as $docs ) {
		foreach ( $docs as $doc ) {
			if ( 'soon' !== $doc['source'] ) {
				$out[ (int) $doc['id'] ] = html_entity_decode( $doc['title'], ENT_QUOTES, 'UTF-8' );
			}
		}
	}
	asort( $out, SORT_NATURAL | SORT_FLAG_CASE );
	return $out;
}

/**
 * The Hub tiles screen.
 */
function spokares_tiles_page(): void {
	if ( ! current_user_can( 'edit_spk_documents' ) ) {
		return;
	}
	$tiles    = spokares_opt( 'spk_tiles' );
	$docs     = spokares_tile_candidates();
	$retained = spokares_retained( 'spokares-tiles' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	$icons    = array(
		'script' => __( 'Script', 'spokares-core' ),
		'form'   => __( 'Form', 'spokares-core' ),
		'log'    => __( 'Log', 'spokares-core' ),
		'book'   => __( 'Book', 'spokares-core' ),
	);
	?>
	<div class="wrap spk-screen spk-tiles">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Hub tiles: the four “Most used” tiles on For members', 'spokares-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/members/', 'quick-links' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="spk-tiles-form">
			<input type="hidden" name="action" value="spokares_save_tiles">
			<?php wp_nonce_field( 'spokares_save_tiles' ); ?>
			<table class="widefat spk-table spk-tiles-table">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Slot', 'spokares-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Document', 'spokares-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Words on the tile', 'spokares-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Icon', 'spokares-core' ); ?></th>
				</tr></thead>
				<tbody>
				<?php
				for ( $i = 0; $i < 4; $i++ ) :
					$slot  = $tiles[ $i ];
					$label = isset( $held[ $i ] ) ? (string) $held[ $i ] : $slot['label'];
					$n     = 'tiles[' . $i . ']';
					?>
					<tr class="spk-tile-row" data-slot="<?php echo esc_attr( (string) ( $i + 1 ) ); ?>">
						<th scope="row">
							<?php echo esc_html( sprintf( /* translators: %d: slot number. */ __( 'Slot %d', 'spokares-core' ), $i + 1 ) ); ?>
							<input type="hidden" name="<?php echo esc_attr( $n ); ?>[orig]" value="<?php echo esc_attr( (string) $slot['doc'] ); ?>">
						</th>
						<td>
							<select name="<?php echo esc_attr( $n ); ?>[doc]" class="spk-tile-doc" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slot number. */ __( 'Document for slot %d', 'spokares-core' ), $i + 1 ) ); ?>">
								<option value="0"><?php esc_html_e( '— none —', 'spokares-core' ); ?></option>
								<?php if ( $slot['doc'] && ! isset( $docs[ $slot['doc'] ] ) ) : ?>
									<option value="<?php echo esc_attr( (string) $slot['doc'] ); ?>" selected><?php echo esc_html( get_the_title( $slot['doc'] ) . ' ' . __( '(not shown: not published, not checked, or “Soon”)', 'spokares-core' ) ); ?></option>
								<?php endif; ?>
								<?php
								foreach ( $docs as $id => $title ) :
									$other = '';
									for ( $j = 0; $j < 4; $j++ ) {
										if ( $j !== $i && $tiles[ $j ]['doc'] === $id ) {
											/* translators: %d: slot number. */
											$other = ' ' . sprintf( __( '(now slot %d)', 'spokares-core' ), $j + 1 );
										}
									}
									?>
									<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $id, $slot['doc'] ); ?> data-title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $title . $other ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<input type="text" class="regular-text spk-tile-label<?php echo esc_attr( spokares_err_class( $errors, "t$i" ) ); ?>" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" maxlength="30" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slot number. */ __( 'Words for slot %d', 'spokares-core' ), $i + 1 ) ); ?>">
							<?php spokares_err_text( $errors, "t$i" ); ?>
						</td>
						<td>
							<select name="<?php echo esc_attr( $n ); ?>[icon]" class="spk-tile-icon" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slot number. */ __( 'Icon for slot %d', 'spokares-core' ), $i + 1 ) ); ?>">
								<?php foreach ( $icons as $key => $name ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $slot['icon'] ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endfor; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'Only published, privacy-checked documents with a file or a link are listed. Choosing one that is already in another slot swaps the two slots.', 'spokares-core' ); ?></p>
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save tiles', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * Save the tiles, swapping when a document moved to another slot.
 */
function spokares_handle_save_tiles(): void {
	spokares_verify_form( 'spokares_save_tiles', 'edit_spk_documents' );
	$posted = isset( $_POST['tiles'] ) && is_array( $_POST['tiles'] ) ? wp_unslash( $_POST['tiles'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$tiles  = spokares_opt( 'spk_tiles' );
	$docs   = spokares_tile_candidates();
	$new    = array();
	$held   = array();
	$errors = array();

	for ( $i = 0; $i < 4; $i++ ) {
		$p    = is_array( $posted[ $i ] ?? null ) ? $posted[ $i ] : array();
		$doc  = absint( spokares_post_str( $p, 'doc' ) );
		$orig = isset( $p['orig'] ) ? absint( spokares_post_str( $p, 'orig' ) ) : $tiles[ $i ]['doc'];
		if ( $doc && ! isset( $docs[ $doc ] ) && $doc !== $tiles[ $i ]['doc'] ) {
			$doc = $tiles[ $i ]['doc']; // Not a valid choice: keep what was there.
		}
		$label = sanitize_text_field( spokares_post_str( $p, 'label' ) );
		$check = spokares_check_field( $label, 'tile' . $i );
		if ( $check['block'] || $check['confirm'] ) {
			$held[ $i ]      = $label;
			$errors[ "t$i" ] = sprintf( /* translators: 1: slot, 2: what was found. */ __( 'Slot %1$d: the words weren’t saved because they mention %2$s.', 'spokares-core' ), $i + 1, spokares_and_list( $check['block'] ? $check['block'] : wp_list_pluck( $check['confirm'], 'what' ) ) );
			$label           = $tiles[ $i ]['label'];
		}
		$new[ $i ] = array(
			'doc'     => $doc,
			'label'   => mb_substr( $label, 0, 30, 'UTF-8' ),
			'icon'    => in_array( $p['icon'] ?? '', array( 'script', 'form', 'log', 'book' ), true ) ? $p['icon'] : $tiles[ $i ]['icon'],
			'changed' => $doc !== $orig,
			'orig'    => $orig,
		);
	}

	// Swap: a document chosen in a changed slot that is still in an unchanged
	// slot moves that slot to the changed slot's old document.
	for ( $i = 0; $i < 4; $i++ ) {
		if ( ! $new[ $i ]['changed'] || ! $new[ $i ]['doc'] ) {
			continue;
		}
		for ( $j = 0; $j < 4; $j++ ) {
			if ( $j === $i || $new[ $j ]['doc'] !== $new[ $i ]['doc'] ) {
				continue;
			}
			// Slot j takes slot i's old document, words and icon; slot i takes
			// slot j's words and icon unless they were typed just now.
			$i_words_untouched    = $new[ $i ]['label'] === $tiles[ $i ]['label'] && $new[ $i ]['icon'] === $tiles[ $i ]['icon'];
			$new[ $j ]['doc']     = $new[ $i ]['orig'];
			$new[ $j ]['label']   = $tiles[ $i ]['label'];
			$new[ $j ]['icon']    = $tiles[ $i ]['icon'];
			$new[ $j ]['changed'] = true;
			if ( $i_words_untouched ) {
				$new[ $i ]['label'] = $tiles[ $j ]['label'];
				$new[ $i ]['icon']  = $tiles[ $j ]['icon'];
			}
		}
	}

	$out = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$out[ $i ] = array(
			'doc'   => $new[ $i ]['doc'],
			'label' => $new[ $i ]['label'],
			'icon'  => $new[ $i ]['icon'],
		);
	}
	update_option( 'spk_tiles', $out );
	spokares_purge_cache();
	foreach ( $errors as $message ) {
		spokares_add_notice( 'error', $message );
	}
	spokares_add_notice( $errors ? 'warning' : 'success', $errors ? __( 'Saved, except the words outlined in red.', 'spokares-core' ) : __( 'Saved.', 'spokares-core' ), spokares_site_url( '/members/', 'quick-links' ), __( 'See it on For members', 'spokares-core' ) );
	if ( $errors ) {
		spokares_retain( 'spokares-tiles', $held, $errors );
	}
	spokares_redirect_to( 'spokares-tiles' );
}
add_action( 'admin_post_spokares_save_tiles', 'spokares_handle_save_tiles' );
