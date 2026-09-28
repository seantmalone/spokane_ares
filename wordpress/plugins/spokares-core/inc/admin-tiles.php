<?php
/**
 * Documents › Most Used (UX spec §3.7): the four buttons under "Most used" on
 * the For members page (option spk_tiles). Choosing a document that is
 * already another button swaps the two, so a button never silently
 * disappears; choosing a new one empties its words, and the site then shows
 * the document's name.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Documents that can be a button: published, privacy-checked, with a file or a link.
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
 * The Most Used screen.
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
	$confirm  = is_array( $held['confirm'] ?? null ) ? $held['confirm'] : array();
	$icons    = array(
		'script' => __( 'Script', 'spokares-core' ),
		'form'   => __( 'Form', 'spokares-core' ),
		'log'    => __( 'Log', 'spokares-core' ),
		'book'   => __( 'Book', 'spokares-core' ),
	);
	$edit     = static fn( int $id ): string => $id ? admin_url( 'post.php?post=' . $id . '&action=edit' ) : '';
	unset( $held['confirm'] );
	?>
	<div class="wrap spk-screen spk-tiles">
		<h1 class="wp-heading-inline"><?php echo esc_html( spokares_screen_title( 'tiles' ) ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/members/', 'quick-links' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<p class="spk-lede"><?php esc_html_e( 'The four buttons under Most used on the For members page, left to right.', 'spokares-core' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="spk-tiles-form" data-spk-guard<?php echo $held ? ' data-spk-dirty' : ''; ?>>
			<input type="hidden" name="action" value="spokares_save_tiles">
			<?php wp_nonce_field( 'spokares_save_tiles' ); ?>
			<table class="widefat spk-table spk-tiles-table">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Button', 'spokares-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Document', 'spokares-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Words on the button', 'spokares-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Icon', 'spokares-core' ); ?></th>
				</tr></thead>
				<tbody>
				<?php
				for ( $i = 0; $i < 4; $i++ ) :
					$slot  = $tiles[ $i ];
					$doc   = (int) $slot['doc'];
					$label = isset( $held[ $i ] ) ? (string) $held[ $i ] : $slot['label'];
					$n     = 'tiles[' . $i . ']';
					$title = $doc ? ( $docs[ $doc ] ?? html_entity_decode( get_the_title( $doc ), ENT_QUOTES, 'UTF-8' ) ) : '';
					?>
					<tr class="spk-tile-row" data-slot="<?php echo esc_attr( (string) ( $i + 1 ) ); ?>">
						<th scope="row">
							<?php echo esc_html( (string) ( $i + 1 ) ); ?>
							<input type="hidden" name="<?php echo esc_attr( $n ); ?>[orig]" value="<?php echo esc_attr( (string) $doc ); ?>">
						</th>
						<td data-label="<?php esc_attr_e( 'Document', 'spokares-core' ); ?>">
							<select name="<?php echo esc_attr( $n ); ?>[doc]" class="spk-tile-doc" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: button number (1-4). */ __( 'Document for button %d', 'spokares-core' ), $i + 1 ) ); ?>">
								<option value="0" data-title="" data-edit=""><?php esc_html_e( '— none —', 'spokares-core' ); ?></option>
								<?php if ( $doc && ! isset( $docs[ $doc ] ) ) : ?>
									<option value="<?php echo esc_attr( (string) $doc ); ?>" selected data-off data-title="<?php echo esc_attr( $title ); ?>" data-edit="<?php echo esc_attr( $edit( $doc ) ); ?>"><?php echo esc_html( $title . ' ' . __( '(not on the site now)', 'spokares-core' ) ); ?></option>
								<?php endif; ?>
								<?php
								foreach ( $docs as $id => $name ) :
									$other = '';
									for ( $j = 0; $j < 4; $j++ ) {
										if ( $j !== $i && (int) $tiles[ $j ]['doc'] === $id ) {
											/* translators: %d: button number (1-4). */
											$other = ' ' . sprintf( __( '(now button %d)', 'spokares-core' ), $j + 1 );
										}
									}
									?>
									<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $id, $doc ); ?> data-title="<?php echo esc_attr( $name ); ?>" data-edit="<?php echo esc_attr( $edit( $id ) ); ?>"><?php echo esc_html( $name . $other ); ?></option>
								<?php endforeach; ?>
							</select>
							<a class="spk-tile-edit" href="<?php echo esc_url( $doc ? $edit( $doc ) : admin_url( 'edit.php?post_type=spk_document' ) ); ?>"<?php echo $doc ? '' : ' hidden'; ?>><?php esc_html_e( 'Replace or change it', 'spokares-core' ); ?><span class="screen-reader-text"> <?php echo esc_html( sprintf( /* translators: %d: button number (1-4). */ __( '(button %d)', 'spokares-core' ), $i + 1 ) ); ?></span></a>
						</td>
						<td data-label="<?php esc_attr_e( 'Words on the button', 'spokares-core' ); ?>">
							<input type="text" class="regular-text spk-tile-label<?php echo esc_attr( spokares_err_class( $errors, "t$i" ) ); ?>" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" maxlength="30" placeholder="<?php echo esc_attr( $title ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: button number (1-4). */ __( 'Words on button %d', 'spokares-core' ), $i + 1 ) ); ?>">
							<?php spokares_err_text( $errors, "t$i" ); ?>
							<?php if ( ! empty( $confirm[ $i ] ) && is_array( $confirm[ $i ] ) ) : ?>
								<label class="spk-confirm"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[confirm]" value="1"> <?php echo esc_html( spokares_confirm_label( $confirm[ $i ] ) ); ?></label>
							<?php endif; ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Icon', 'spokares-core' ); ?>">
							<select name="<?php echo esc_attr( $n ); ?>[icon]" class="spk-tile-icon" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: button number (1-4). */ __( 'Icon for button %d', 'spokares-core' ), $i + 1 ) ); ?>">
								<?php foreach ( $icons as $key => $name ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $slot['icon'] ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endfor; ?>
				</tbody>
			</table>
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * Save the buttons. A document chosen that is already another button swaps
 * the two. A different document whose words weren't changed gets no words:
 * the site then prints the document's name.
 */
function spokares_handle_save_tiles(): void {
	spokares_verify_form( 'spokares_save_tiles', 'edit_spk_documents' );
	$posted = isset( $_POST['tiles'] ) && is_array( $_POST['tiles'] ) ? wp_unslash( $_POST['tiles'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$tiles  = spokares_opt( 'spk_tiles' );
	$docs   = spokares_tile_candidates();
	$stored = array_map( static fn( $t ) => (int) $t['doc'], array_slice( $tiles, 0, 4 ) );
	$ok     = is_array( $tiles['confirmed'] ?? null ) ? $tiles['confirmed'] : array();
	$new    = array();
	$held   = array();
	$errors = array();

	for ( $i = 0; $i < 4; $i++ ) {
		$p    = is_array( $posted[ $i ] ?? null ) ? $posted[ $i ] : array();
		$doc  = absint( spokares_post_str( $p, 'doc' ) );
		$orig = isset( $p['orig'] ) ? absint( spokares_post_str( $p, 'orig' ) ) : $stored[ $i ];
		if ( $doc && ! isset( $docs[ $doc ] ) && $doc !== $stored[ $i ] ) {
			$doc = $stored[ $i ]; // Not a valid choice: keep what was there.
		}
		$label = sanitize_text_field( spokares_post_str( $p, 'label' ) );
		$check = spokares_check_field( $label, 'tile' . $i, $ok, ! empty( $p['confirm'] ) );
		if ( $check['block'] || $check['confirm'] ) {
			$held[ $i ]      = $label;
			$errors[ "t$i" ] = spokares_problem_sentence( $check );
			$label           = $tiles[ $i ]['label'];
			if ( ! $check['block'] ) {
				$held['confirm'][ $i ] = $check['confirm']; // For the "public agency" tick.
			}
		} elseif ( $check['confirmed'] ) {
			$ok[ 'tile' . $i ] = sha1( $label );
		}
		// A different document that isn't moving here from another button, with
		// the old words left as they were: the old words described the old
		// document, so they go (the site shows the new document's name).
		if ( $doc !== $stored[ $i ] && ! in_array( $doc, $stored, true ) && $label === $tiles[ $i ]['label'] && ! isset( $held[ $i ] ) ) {
			$label = '';
		}
		$new[ $i ] = array(
			'doc'     => $doc,
			'label'   => mb_substr( $label, 0, 30, 'UTF-8' ),
			'icon'    => in_array( $p['icon'] ?? '', array( 'script', 'form', 'log', 'book' ), true ) ? $p['icon'] : $tiles[ $i ]['icon'],
			'changed' => $doc !== $orig,
			'orig'    => $orig,
		);
	}

	// Swap: a document chosen in a changed row that is still in an unchanged
	// row moves that row to the changed row's old document.
	for ( $i = 0; $i < 4; $i++ ) {
		if ( ! $new[ $i ]['changed'] || ! $new[ $i ]['doc'] ) {
			continue;
		}
		for ( $j = 0; $j < 4; $j++ ) {
			if ( $j === $i || $new[ $j ]['doc'] !== $new[ $i ]['doc'] ) {
				continue;
			}
			// Row j takes row i's old document, words and icon; row i takes
			// row j's words and icon unless they were typed just now.
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
	if ( $ok ) {
		$out['confirmed'] = $ok;
	}
	update_option( 'spk_tiles', $out );
	spokares_purge_cache();
	if ( $errors ) {
		$rows = array_values( array_map( static fn( $key ) => (int) substr( (string) $key, 1 ) + 1, array_keys( $errors ) ) );
		$text = 1 === count( $rows )
			/* translators: %d: button number (1-4). */
			? sprintf( __( 'Saved, except button %d’s words (outlined in red).', 'spokares-core' ), $rows[0] )
			/* translators: %s: button numbers, e.g. "1 and 3". */
			: sprintf( __( 'Saved, except the words on buttons %s (outlined in red).', 'spokares-core' ), spokares_and_list( array_map( 'strval', $rows ) ) );
		spokares_add_notice( 'warning', $text );
		spokares_retain( 'spokares-tiles', $held, $errors );
	} else {
		spokares_add_notice( 'success', __( 'Saved.', 'spokares-core' ), spokares_site_url( '/members/', 'quick-links' ), __( 'See it on the For members page', 'spokares-core' ) );
	}
	spokares_redirect_to( 'spokares-tiles' );
}
add_action( 'admin_post_spokares_save_tiles', 'spokares_handle_save_tiles' );
