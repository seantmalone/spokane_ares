<?php
/**
 * Net rota (§3.4): one screen, one row per Tuesday. Saves only the rows that
 * changed, keeps someone else's newer edit, holds back a bad field without
 * losing what was typed, and can undo the last save.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Winlink form choices.
 */
function spokares_winlink_forms(): array {
	return array( 'ICS-213', 'ICS-213RR', 'DYFI', 'Welfare Message / Quick Health & Welfare' );
}

/**
 * A comparable fingerprint of a rota row (missing row = Not posted yet).
 *
 * @param array|null $row Row.
 */
function spokares_rota_hash( ?array $row ): string {
	$row = spokares_normalize_rota_row( $row ?? array() );
	return md5( (string) wp_json_encode( array( $row['state'], $row['call'], $row['note'], $row['wl_task'], $row['wl_form'] ) ) );
}

/**
 * What the public sees for a row.
 *
 * @param array $row Row.
 */
function spokares_rota_shows( array $row ): string {
	if ( 'call' === $row['state'] && '' !== $row['call'] ) {
		return $row['call'];
	}
	return 'open' === $row['state'] ? __( 'Open', 'spokares-core' ) : __( 'Not yet published', 'spokares-core' );
}

/**
 * Call signs used before, for the suggestions list.
 */
function spokares_known_calls(): array {
	$calls = array();
	foreach ( spokares_opt( 'spk_rota' ) as $row ) {
		if ( '' !== $row['call'] ) {
			$calls[] = $row['call'];
		}
	}
	$calls = array_unique( $calls );
	sort( $calls );
	return $calls;
}

/**
 * The Net rota screen.
 */
function spokares_rota_page(): void {
	if ( ! current_user_can( 'spokares_edit_rota' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- how many rows to show; read-only.
	$weeks    = isset( $_GET['weeks'] ) ? max( 13, min( 52, absint( $_GET['weeks'] ) ) ) : 13;
	$rows     = spokares_rota_rows( $weeks );
	$retained = spokares_retained( 'spokares-rota' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	$nets     = spokares_opt( 'spk_nets' );
	$radio    = spokares_opt( 'spk_radio' );
	$saved    = get_option( 'spk_rota_saved', array() );
	$prev     = get_option( 'spk_rota_prev', array() );
	$forms    = spokares_winlink_forms();
	?>
	<div class="wrap spk-screen spk-rota">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Net rota', 'spokares-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/members/', 'rota' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<?php if ( is_array( $saved ) && $saved ) : ?>
			<span class="spk-saved"><?php echo esc_html( spokares_saved_line( $saved ) ); ?></span>
		<?php endif; ?>
		<hr class="wp-header-end">
		<p class="spk-lede">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: time, 2: call sign and frequency. */
					__( 'Tuesday net, %1$s, %2$s. Call signs only, no names.', 'spokares-core' ),
					spokares_fmt_time( $nets['net_time'] ),
					trim( $radio['primary']['call'] . ' ' . spokares_radio_line( 'freq' ) )
				)
			);
			?>
		</p>
		<?php if ( ! current_user_can( 'spokares_edit_net_details' ) ) : ?>
			<p class="description spk-ask"><?php esc_html_e( 'Repeater details, the net time and the Winlink, simplex and GMRS weeks are on Net details: ask the webmaster (webmaster@spokares.org).', 'spokares-core' ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="spk-rota-form">
			<input type="hidden" name="action" value="spokares_save_rota">
			<input type="hidden" name="weeks" value="<?php echo esc_attr( (string) $weeks ); ?>">
			<?php wp_nonce_field( 'spokares_save_rota' ); ?>
			<table class="widefat striped spk-table spk-rota-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Tuesday', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Net control', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Shows', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Winlink assignment', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Form', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Note', 'spokares-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $rows as $row ) :
					$d       = $row['date'];
					$name    = 'rota[' . $d . ']';
					$stored  = array(
						'state'   => $row['state'],
						'call'    => $row['call'],
						'note'    => $row['row_note'],
						'wl_task' => $row['wl_task'],
						'wl_form' => $row['wl_form'],
					);
					$v       = isset( $held[ $d ] ) && is_array( $held[ $d ] ) ? array_merge( $stored, $held[ $d ] ) : $stored;
					$label   = spokares_fmt_date( $d, 'short' );
					$winlink = 'winlink' === $row['kind'] || '' !== $v['wl_task'];
					$other   = '' !== $v['wl_form'] && ! in_array( $v['wl_form'], $forms, true );
					$row_err = isset( $errors[ "$d-call" ] ) || isset( $errors[ "$d-row" ] );
					?>
					<tr class="spk-rota-row<?php echo $row_err ? ' spk-row-error' : ''; ?>" data-date="<?php echo esc_attr( $d ); ?>">
						<th scope="row">
							<?php echo esc_html( $label ); ?>
							<?php if ( '' !== $row['note'] ) : ?>
								<span class="spk-kind"><?php echo esc_html( $row['note'] ); ?></span>
							<?php endif; ?>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[h]" value="<?php echo esc_attr( spokares_rota_hash( $stored ) ); ?>">
						</th>
						<td class="spk-nc" data-label="<?php esc_attr_e( 'Net control', 'spokares-core' ); ?>">
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'Net control on %s', 'spokares-core' ), $label ) ); ?></legend>
								<label class="spk-choice"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="call" <?php checked( 'call', $v['state'] ); ?>> <?php esc_html_e( 'Call sign', 'spokares-core' ); ?></label>
								<input type="text" class="spk-call<?php echo esc_attr( spokares_err_class( $errors, "$d-call" ) ); ?>" name="<?php echo esc_attr( $name ); ?>[call]" value="<?php echo esc_attr( $v['call'] ); ?>" list="spk-calls" maxlength="40" autocomplete="off" spellcheck="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Call sign for %s', 'spokares-core' ), $label ) ); ?>">
								<label class="spk-choice"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="open" <?php checked( 'open', $v['state'] ); ?>> <?php esc_html_e( 'Open', 'spokares-core' ); ?></label>
								<label class="spk-choice"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="tbd" <?php checked( 'tbd', $v['state'] ); ?>> <?php esc_html_e( 'Not posted yet', 'spokares-core' ); ?></label>
							</fieldset>
							<?php spokares_err_text( $errors, "$d-call" ); ?>
							<?php spokares_err_text( $errors, "$d-row" ); ?>
						</td>
						<td class="spk-shows" aria-live="polite" data-label="<?php esc_attr_e( 'Shows on the site', 'spokares-core' ); ?>"><?php echo esc_html( spokares_rota_shows( spokares_normalize_rota_row( $stored ) ) ); ?></td>
						<td class="spk-wl" data-label="<?php echo $winlink ? esc_attr__( 'Winlink assignment', 'spokares-core' ) : ''; ?>">
							<?php if ( $winlink ) : ?>
								<input type="text" class="spk-wl-task<?php echo esc_attr( spokares_err_class( $errors, "$d-wl_task" ) ); ?>" name="<?php echo esc_attr( $name ); ?>[wl_task]" value="<?php echo esc_attr( $v['wl_task'] ); ?>" maxlength="120" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Winlink assignment for %s', 'spokares-core' ), $label ) ); ?>">
								<?php spokares_err_text( $errors, "$d-wl_task" ); ?>
								<?php spokares_rota_confirm( $errors, $d, 'wl_task', $name ); ?>
							<?php endif; ?>
						</td>
						<td class="spk-wl-form" data-label="<?php echo $winlink ? esc_attr__( 'Form', 'spokares-core' ) : ''; ?>">
							<?php if ( $winlink ) : ?>
								<select name="<?php echo esc_attr( $name ); ?>[wl_form]" class="spk-form-select" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Winlink form for %s', 'spokares-core' ), $label ) ); ?>">
									<option value=""><?php esc_html_e( '— none —', 'spokares-core' ); ?></option>
									<?php foreach ( $forms as $f ) : ?>
										<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $f, $v['wl_form'] ); ?>><?php echo esc_html( $f ); ?></option>
									<?php endforeach; ?>
									<option value="__other" <?php selected( $other ); ?>><?php esc_html_e( 'Other…', 'spokares-core' ); ?></option>
								</select>
								<input type="text" class="spk-form-other" name="<?php echo esc_attr( $name ); ?>[wl_form_other]" value="<?php echo esc_attr( $other ? $v['wl_form'] : '' ); ?>" maxlength="60" placeholder="<?php esc_attr_e( 'Form name', 'spokares-core' ); ?>" aria-label="<?php esc_attr_e( 'Other form name', 'spokares-core' ); ?>" <?php echo $other ? '' : 'hidden'; ?>>
							<?php endif; ?>
						</td>
						<td class="spk-note" data-label="<?php esc_attr_e( 'Note', 'spokares-core' ); ?>">
							<input type="text" class="<?php echo esc_attr( trim( spokares_err_class( $errors, "$d-note" ) ) ); ?>" name="<?php echo esc_attr( $name ); ?>[note]" value="<?php echo esc_attr( $v['note'] ); ?>" maxlength="80" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Note for %s', 'spokares-core' ), $label ) ); ?>">
							<?php spokares_err_text( $errors, "$d-note" ); ?>
							<?php spokares_rota_confirm( $errors, $d, 'note', $name ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<datalist id="spk-calls">
				<?php foreach ( spokares_known_calls() as $call ) : ?>
					<option value="<?php echo esc_attr( $call ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
			<p class="spk-rota-more">
				<a href="<?php echo esc_url( add_query_arg( 'weeks', min( 52, $weeks + 13 ), admin_url( 'admin.php?page=spokares-rota' ) ) ); ?>">
					<?php
					/* translators: %d: number of Tuesdays. */
					echo esc_html( sprintf( __( 'Showing %d Tuesdays from this week. Show 13 more', 'spokares-core' ), $weeks ) );
					?>
				</a>
			</p>
			<p class="submit spk-submit">
				<?php if ( is_array( $prev ) && ! empty( $prev['rows'] ) ) : ?>
					<button type="submit" form="spk-rota-undo" class="button">
						<?php
						$prev_who  = spokares_user_name( (int) ( $prev['by'] ?? 0 ) );
						$prev_when = wp_date( 'D, M j, g:i A', (int) strtotime( (string) ( $prev['at'] ?? '' ) ) );
						echo esc_html(
							empty( $prev['undo'] )
								/* translators: 1: a person's name, 2: date and time. */
								? sprintf( __( 'Undo last save (%1$s, %2$s)', 'spokares-core' ), $prev_who, $prev_when )
								/* translators: 1: a person's name, 2: date and time of their undo. */
								: sprintf( __( 'Put back what was undone (%1$s undid it, %2$s)', 'spokares-core' ), $prev_who, $prev_when )
						);
						?>
					</button>
				<?php endif; ?>
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save rota', 'spokares-core' ); ?></button>
			</p>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="spk-rota-undo">
			<input type="hidden" name="action" value="spokares_undo_rota">
			<input type="hidden" name="weeks" value="<?php echo esc_attr( (string) $weeks ); ?>">
			<?php wp_nonce_field( 'spokares_undo_rota' ); ?>
		</form>
	</div>
	<?php
}

/**
 * The "Publish it" tick beside a rota field that holds a phone number or
 * e-mail address.
 *
 * @param array  $errors Errors.
 * @param string $d      Date.
 * @param string $field  Field key.
 * @param string $name   Row input name.
 */
function spokares_rota_confirm( array $errors, string $d, string $field, string $name ): void {
	$key = "$d-$field-confirm";
	if ( empty( $errors[ $key ] ) ) {
		return;
	}
	printf(
		'<label class="spk-confirm"><input type="checkbox" name="%1$s[confirm_%2$s]" value="1"> %3$s</label>',
		esc_attr( $name ),
		esc_attr( $field ),
		esc_html( $errors[ $key ] )
	);
}

/**
 * Save the rota: changed rows only.
 */
function spokares_handle_save_rota(): void {
	spokares_verify_form( 'spokares_save_rota', 'spokares_edit_rota' );
	// Two editors saving at the same moment: the second waits for the first,
	// then reads the rows fresh, so the per-row conflict check sees both.
	spokares_lock_option( 'spk_rota', array( 'spk_rota_saved', 'spk_rota_prev' ) );

	$weeks  = isset( $_POST['weeks'] ) ? absint( $_POST['weeks'] ) : 13;
	$posted = isset( $_POST['rota'] ) && is_array( $_POST['rota'] ) ? wp_unslash( $_POST['rota'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field is sanitised below.
	$stored = spokares_opt( 'spk_rota' );
	$saver  = get_option( 'spk_rota_saved', array() );
	$prev   = array();
	$held   = array();
	$errors = array();
	$notes  = array();
	$forms  = spokares_winlink_forms();

	foreach ( $posted as $ymd => $p ) {
		$ymd = (string) $ymd;
		if ( ! spokares_is_ymd( $ymd ) || 2 !== spokares_weekday( $ymd ) || ! is_array( $p ) ) {
			continue;
		}
		$label   = spokares_fmt_date( $ymd, 'short-noyear' );
		$orig    = sanitize_text_field( spokares_post_str( $p, 'h' ) );
		$cur     = $stored[ $ymd ] ?? null;
		$state   = spokares_post_str( $p, 'state' );
		$state   = in_array( $state, array( 'call', 'open', 'tbd' ), true ) ? $state : 'tbd';
		$typed   = sanitize_text_field( spokares_post_str( $p, 'call' ) );
		$wl_form = sanitize_text_field( spokares_post_str( $p, 'wl_form' ) );
		if ( '__other' === $wl_form ) {
			$wl_form = sanitize_text_field( spokares_post_str( $p, 'wl_form_other' ) );
		}
		$typed_row = array(
			'state'   => $state,
			'call'    => $typed,
			'note'    => sanitize_text_field( spokares_post_str( $p, 'note' ) ),
			'wl_task' => sanitize_text_field( spokares_post_str( $p, 'wl_task' ) ),
			'wl_form' => $wl_form,
		);

		// The call sign: first word that matches; none → the row isn't saved.
		$call    = '';
		$dropped = false;
		if ( 'call' === $state ) {
			if ( '' === $typed ) {
				$state = 'tbd';
			} else {
				$cs = spokares_call_sign( $typed );
				if ( '' === $cs['call'] ) {
					$errors[ "$ymd-call" ] = sprintf(
						/* translators: %s: date. */
						__( '%s not saved: call sign only, no names.', 'spokares-core' ),
						$label
					);
					$held[ $ymd ] = $typed_row;
					continue;
				}
				$call    = $cs['call'];
				$dropped = $cs['dropped'];
			}
		}
		$cand = array(
			'state'   => $state,
			'call'    => $call,
			'note'    => $typed_row['note'],
			'wl_task' => $typed_row['wl_task'],
			'wl_form' => $typed_row['wl_form'],
		);

		if ( hash_equals( $orig, spokares_rota_hash( $cand ) ) ) {
			continue; // Unchanged.
		}
		if ( ! hash_equals( $orig, spokares_rota_hash( $cur ) ) ) {
			$errors[ "$ymd-row" ] = sprintf(
				/* translators: 1: date, 2: a person's name. */
				__( '%1$s was changed by %2$s while you were editing. Your entry wasn’t saved.', 'spokares-core' ),
				$label,
				spokares_user_name( (int) ( $saver['by'] ?? 0 ) )
			);
			$held[ $ymd ] = $typed_row;
			continue;
		}

		// Free-text fields: never-publish words hold the field back; phone
		// numbers and e-mail addresses need the "Publish it" tick.
		$confirmed = is_array( $cur['confirmed'] ?? null ) ? $cur['confirmed'] : array();
		foreach ( array( 'note', 'wl_task' ) as $field ) {
			$check = spokares_check_field( $cand[ $field ], $field, $confirmed, ! empty( $p[ 'confirm_' . $field ] ) );
			if ( $check['block'] || $check['confirm'] ) {
				$what                    = $check['block'] ? $check['block'] : wp_list_pluck( $check['confirm'], 'what' );
				$errors[ "$ymd-$field" ] = sprintf(
					/* translators: 1: date, 2: what was found. */
					__( '%1$s: this wasn’t saved because it mentions %2$s.', 'spokares-core' ),
					$label,
					spokares_and_list( $what )
				);
				if ( ! $check['block'] ) {
					$errors[ "$ymd-$field-confirm" ] = spokares_confirm_label( $check['confirm'] );
				}
				$held[ $ymd ][ $field ] = $cand[ $field ];
				$cand[ $field ]         = (string) ( $cur[ $field ] ?? '' );
			} elseif ( $check['confirmed'] ) {
				$confirmed[ $field ] = sha1( $cand[ $field ] );
			}
		}
		if ( isset( $held[ $ymd ] ) ) {
			$held[ $ymd ] = array_merge( $cand, $held[ $ymd ], array( 'call' => '' !== $call ? $call : $typed ) );
		}
		if ( hash_equals( $orig, spokares_rota_hash( $cand ) ) ) {
			continue; // Nothing left to save after holding back.
		}

		$prev[ $ymd ] = $cur;
		if ( 'tbd' === $cand['state'] && '' === $cand['note'] && '' === $cand['wl_task'] && '' === $cand['wl_form'] ) {
			unset( $stored[ $ymd ] );
		} else {
			if ( $confirmed ) {
				$cand['confirmed'] = $confirmed;
			}
			$stored[ $ymd ] = $cand;
		}
		if ( $dropped ) {
			$notes[] = sprintf(
				/* translators: 1: date, 2: call sign. */
				__( '%1$s: saved %2$s only. One call sign per Tuesday; names are never stored.', 'spokares-core' ),
				$label,
				$call
			);
		}
	}

	if ( $prev ) {
		// Prune rows more than a year old.
		$cutoff = spokares_add_days( spokares_today(), -365 );
		foreach ( array_keys( $stored ) as $ymd ) {
			if ( $ymd < $cutoff ) {
				unset( $stored[ $ymd ] );
			}
		}
		$stamp = spokares_stamp();
		update_option( 'spk_rota', $stored );
		update_option( 'spk_rota_prev', array_merge( $stamp, array( 'rows' => $prev ) ), false );
		update_option( 'spk_rota_saved', $stamp, false );
		spokares_purge_cache();
		spokares_add_notice(
			'success',
			sprintf(
				/* translators: %d: number of Tuesdays. */
				_n( 'Saved %d Tuesday.', 'Saved %d Tuesdays.', count( $prev ), 'spokares-core' ),
				count( $prev )
			),
			spokares_site_url( '/members/', 'rota' ),
			__( 'See it on For members', 'spokares-core' )
		);
	} elseif ( ! $errors ) {
		spokares_add_notice( 'info', __( 'Nothing changed, so nothing was saved.', 'spokares-core' ) );
	}
	foreach ( $notes as $note ) {
		spokares_add_notice( 'warning', $note );
	}
	foreach ( $errors as $key => $message ) {
		if ( ! str_ends_with( (string) $key, '-confirm' ) ) {
			spokares_add_notice( 'error', $message );
		}
	}
	if ( $held || $errors ) {
		spokares_retain( 'spokares-rota', $held, $errors );
	}
	spokares_redirect_to( 'spokares-rota', $weeks > 13 ? array( 'weeks' => $weeks ) : array() );
}
add_action( 'admin_post_spokares_save_rota', 'spokares_handle_save_rota' );

/**
 * Undo the last save: put back the rows it changed (whoever made it).
 */
function spokares_handle_undo_rota(): void {
	spokares_verify_form( 'spokares_undo_rota', 'spokares_edit_rota' );
	spokares_lock_option( 'spk_rota', array( 'spk_rota_saved', 'spk_rota_prev' ) );
	$weeks = isset( $_POST['weeks'] ) ? absint( $_POST['weeks'] ) : 13;
	$prev  = get_option( 'spk_rota_prev', array() );
	if ( ! is_array( $prev ) || empty( $prev['rows'] ) || ! is_array( $prev['rows'] ) ) {
		spokares_add_notice( 'info', __( 'There is nothing to undo.', 'spokares-core' ) );
		spokares_redirect_to( 'spokares-rota' );
	}
	$stored = spokares_opt( 'spk_rota' );
	$redo   = array();
	foreach ( $prev['rows'] as $ymd => $row ) {
		$ymd = (string) $ymd;
		if ( ! spokares_is_ymd( $ymd ) ) {
			continue;
		}
		$redo[ $ymd ] = $stored[ $ymd ] ?? null;
		if ( is_array( $row ) ) {
			$stored[ $ymd ] = $row;
		} else {
			unset( $stored[ $ymd ] );
		}
	}
	$stamp = spokares_stamp();
	update_option( 'spk_rota', $stored );
	// The next "undo" puts back what this one took away: flag it, so its
	// button and notice say so instead of "Undo last save".
	update_option(
		'spk_rota_prev',
		array_merge(
			$stamp,
			array(
				'rows' => $redo,
				'undo' => empty( $prev['undo'] ),
			)
		),
		false
	);
	update_option( 'spk_rota_saved', $stamp, false );
	spokares_purge_cache();
	$who  = spokares_user_name( (int) ( $prev['by'] ?? 0 ) );
	$when = wp_date( 'D, M j, g:i A', (int) strtotime( (string) ( $prev['at'] ?? '' ) ) );
	spokares_add_notice(
		'success',
		empty( $prev['undo'] )
			/* translators: 1: a person's name, 2: date and time of their save. */
			? sprintf( __( 'Undone: %1$s’s save of %2$s. The Tuesdays it changed are back as they were.', 'spokares-core' ), $who, $when )
			/* translators: 1: a person's name, 2: date and time of their undo. */
			: sprintf( __( 'Put back: the Tuesdays %1$s undid on %2$s.', 'spokares-core' ), $who, $when ),
		spokares_site_url( '/members/', 'rota' ),
		__( 'See it on For members', 'spokares-core' )
	);
	spokares_redirect_to( 'spokares-rota', $weeks > 13 ? array( 'weeks' => $weeks ) : array() );
}
add_action( 'admin_post_spokares_undo_rota', 'spokares_handle_undo_rota' );
