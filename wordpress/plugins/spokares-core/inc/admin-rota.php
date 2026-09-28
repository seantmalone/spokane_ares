<?php
/**
 * Net Control Schedule (ux/SPEC.md §3.2): one screen, one row per Tuesday.
 * Saves only the rows that changed, keeps someone else's newer edit, holds
 * back a bad field without losing what was typed, and can undo (and redo)
 * the last save.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Winlink form choices. 'ICS-213' stays in the list so a stored
 * 'ICS-213' is never "Other…"; the Form select shows it as the blank
 * "ICS-213 (usual)" choice (§4.1).
 */
function spokares_winlink_forms(): array {
	return array( 'ICS-213', 'ICS-213RR', 'DYFI', 'Welfare Message / Quick Health & Welfare' );
}

/**
 * A comparable fingerprint of a schedule row (missing row = Not posted yet).
 *
 * @param array|null $row Row.
 */
function spokares_rota_hash( ?array $row ): string {
	$row = spokares_normalize_rota_row( $row ?? array() );
	return md5( (string) wp_json_encode( array( $row['state'], $row['call'], $row['note'], $row['wl_task'], $row['wl_form'] ) ) );
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
 * Tuesdays named in a sentence: "Oct 13", "Oct 13 and Oct 20", or for more
 * than three "8 Tuesdays, Nov 10 – Dec 29". An item may carry words after
 * its date ("Oct 13: NZ2S (names aren’t posted)"); with more than three,
 * those follow the range.
 *
 * @param string[]             $dates Y-m-d dates.
 * @param array<string,string> $after Date => words after it.
 */
function spokares_rota_dates_phrase( array $dates, array $after = array() ): string {
	$dates = array_values( array_unique( array_filter( array_map( 'strval', $dates ), 'spokares_is_ymd' ) ) );
	sort( $dates );
	if ( ! $dates ) {
		return '';
	}
	$item = static fn( string $d ): string => spokares_fmt_date( $d, 'day' ) . ( isset( $after[ $d ] ) ? ': ' . $after[ $d ] : '' );
	if ( count( $dates ) <= 3 ) {
		return spokares_and_list( array_map( $item, $dates ) );
	}
	$text = sprintf(
		/* translators: 1: number of Tuesdays, 2: first date, 3: last date. */
		__( '%1$d Tuesdays, %2$s – %3$s', 'spokares-core' ),
		count( $dates ),
		spokares_fmt_date( $dates[0], 'day' ),
		spokares_fmt_date( $dates[ count( $dates ) - 1 ], 'day' )
	);
	foreach ( $dates as $d ) {
		if ( isset( $after[ $d ] ) ) {
			$text .= '; ' . $item( $d );
		}
	}
	return $text;
}

/**
 * The title line after a save or an undo: "Last saved {when} by {name}
 * ({dates})" or "Undone {when} by {name} ({dates})".
 *
 * @param array $stamp spk_rota_saved: at, by, dates, undone.
 */
function spokares_rota_saved_line( array $stamp ): string {
	$ts = empty( $stamp['at'] ) ? false : strtotime( (string) $stamp['at'] );
	if ( ! $ts ) {
		return '';
	}
	$when   = wp_date( 'D, M j, g:i A', $ts );
	$who    = spokares_user_name( (int) ( $stamp['by'] ?? 0 ) );
	$dates  = spokares_rota_dates_phrase( is_array( $stamp['dates'] ?? null ) ? $stamp['dates'] : array() );
	$undone = ! empty( $stamp['undone'] );
	if ( '' === $dates ) {
		return $undone
			/* translators: 1: date and time, 2: a person's name. */
			? sprintf( __( 'Undone %1$s by %2$s', 'spokares-core' ), $when, $who )
			/* translators: 1: date and time, 2: a person's name. */
			: sprintf( __( 'Last saved %1$s by %2$s', 'spokares-core' ), $when, $who );
	}
	return $undone
		/* translators: 1: date and time, 2: a person's name, 3: the Tuesdays it changed. */
		? sprintf( __( 'Undone %1$s by %2$s (%3$s)', 'spokares-core' ), $when, $who, $dates )
		/* translators: 1: date and time, 2: a person's name, 3: the Tuesdays it changed. */
		: sprintf( __( 'Last saved %1$s by %2$s (%3$s)', 'spokares-core' ), $when, $who, $dates );
}

/**
 * The Net Control Schedule screen.
 */
function spokares_rota_page(): void {
	if ( ! current_user_can( 'spokares_edit_rota' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- how many rows to show; read-only.
	$weeks    = isset( $_GET['weeks'] ) ? max( 13, min( 52, absint( $_GET['weeks'] ) ) ) : 13;
	$rows     = spokares_rota_rows( 52 );
	$retained = spokares_retained( 'spokares-rota' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	$nets     = spokares_opt( 'spk_nets' );
	$radio    = spokares_opt( 'spk_radio' );
	$saved    = get_option( 'spk_rota_saved', array() );
	$prev     = get_option( 'spk_rota_prev', array() );
	$forms    = spokares_winlink_forms();
	$user     = get_current_user_id();
	$changed  = get_transient( 'spokares_rota_changed_' . $user );
	$changed  = is_array( $changed ) ? $changed : array();
	delete_transient( 'spokares_rota_changed_' . $user );

	// A row with a problem or unsaved typing is never hidden: show every row
	// up to the last such row (in steps of 13, as "Show 13 more" does).
	$flagged = array_merge( array_keys( $held ), array_map( static fn( $key ) => substr( (string) $key, 0, 10 ), array_keys( $errors ) ) );
	foreach ( $rows as $i => $row ) {
		if ( $i >= $weeks && in_array( $row['date'], $flagged, true ) ) {
			$weeks = (int) min( 52, 13 * ceil( ( $i + 1 ) / 13 ) );
		}
	}
	$saved_line = is_array( $saved ) ? spokares_rota_saved_line( $saved ) : '';
	$undo       = is_array( $prev ) && ! empty( $prev['rows'] );
	?>
	<div class="wrap spk-screen spk-rota">
		<h1 class="wp-heading-inline"><?php echo esc_html( spokares_screen_title( 'rota' ) ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/members/', 'rota' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<?php if ( '' !== $saved_line ) : ?>
			<span class="spk-saved">
				<?php
				echo esc_html( $saved_line );
				if ( $undo ) {
					// Undo (or Redo, after an undo) submits the undo form below.
					echo ' · <button type="submit" form="spk-rota-undo" class="button-link spk-undo">' . ( empty( $prev['undo'] ) ? esc_html__( 'Undo', 'spokares-core' ) : esc_html__( 'Redo', 'spokares-core' ) ) . '</button>';
				}
				?>
			</span>
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
			<p class="description spk-ask"><?php esc_html_e( 'Repeater and net times: ask the webmaster.', 'spokares-core' ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="spk-rota-form" data-spk-guard<?php echo $held ? ' data-spk-dirty' : ''; ?>>
			<input type="hidden" name="action" value="spokares_save_rota">
			<input type="hidden" name="weeks" value="<?php echo esc_attr( (string) $weeks ); ?>">
			<?php wp_nonce_field( 'spokares_save_rota' ); ?>
			<table class="widefat striped spk-table spk-rota-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Tuesday', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Net control', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Winlink assignment', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Form', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Note', 'spokares-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $rows as $i => $row ) :
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
					$usual   = '' === $v['wl_form'] || 'ICS-213' === $v['wl_form'];
					$row_err = isset( $errors[ "$d-call" ] ) || isset( $errors[ "$d-row" ] );
					$classes = 'spk-rota-row' . ( $row_err ? ' spk-row-error' : '' ) . ( in_array( $d, $changed, true ) ? ' spk-row-changed' : '' );
					?>
					<tr class="<?php echo esc_attr( $classes ); ?>" data-date="<?php echo esc_attr( $d ); ?>" data-day="<?php echo esc_attr( $label ); ?>"<?php echo $i >= $weeks ? ' hidden' : ''; ?>>
						<th scope="row">
							<span class="spk-day"><?php echo esc_html( $label ); ?></span>
							<?php if ( '' !== $row['note'] ) : ?>
								<span class="spk-kind"><?php echo esc_html( $row['note'] ); ?></span>
							<?php endif; ?>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[h]" value="<?php echo esc_attr( spokares_rota_hash( $stored ) ); ?>">
						</th>
						<td class="spk-nc">
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'Net control on %s', 'spokares-core' ), $label ) ); ?></legend>
								<label class="spk-choice spk-choice-call"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="call" <?php checked( 'call', $v['state'] ); ?>><span class="screen-reader-text"><?php esc_html_e( 'Call sign', 'spokares-core' ); ?></span></label>
								<input type="text" class="spk-call<?php echo esc_attr( spokares_err_class( $errors, "$d-call" ) ); ?>" name="<?php echo esc_attr( $name ); ?>[call]" value="<?php echo esc_attr( $v['call'] ); ?>" placeholder="<?php esc_attr_e( 'Call sign', 'spokares-core' ); ?>" list="spk-calls" maxlength="40" autocomplete="off" spellcheck="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Call sign for %s', 'spokares-core' ), $label ) ); ?>">
								<label class="spk-choice"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="open" <?php checked( 'open', $v['state'] ); ?>> <?php esc_html_e( 'Volunteer needed', 'spokares-core' ); ?></label>
								<label class="spk-choice"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="none" <?php checked( 'none', $v['state'] ); ?>> <?php esc_html_e( 'No net', 'spokares-core' ); ?></label>
								<label class="spk-choice"><input type="radio" name="<?php echo esc_attr( $name ); ?>[state]" value="tbd" <?php checked( 'tbd', $v['state'] ); ?>> <?php esc_html_e( 'Not posted yet', 'spokares-core' ); ?></label>
							</fieldset>
							<?php if ( isset( $errors[ "$d-call" ] ) ) : ?>
								<?php // The script hides this line once the box holds a call sign (the same sentence as its live check). ?>
								<span class="spk-error-text spk-call-check"><?php echo esc_html( $errors[ "$d-call" ] ); ?></span>
							<?php endif; ?>
							<?php spokares_err_text( $errors, "$d-row" ); ?>
						</td>
						<td class="spk-wl" data-label="<?php echo $winlink ? esc_attr__( 'Winlink assignment', 'spokares-core' ) : ''; ?>">
							<?php if ( $winlink ) : ?>
								<textarea class="spk-wl-task<?php echo esc_attr( spokares_err_class( $errors, "$d-wl_task" ) ); ?>" name="<?php echo esc_attr( $name ); ?>[wl_task]" rows="2" maxlength="120" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Winlink assignment for %s', 'spokares-core' ), $label ) ); ?>"><?php echo esc_textarea( $v['wl_task'] ); ?></textarea>
								<?php spokares_err_text( $errors, "$d-wl_task" ); ?>
								<?php spokares_rota_confirm( $errors, $d, 'wl_task', $name ); ?>
							<?php endif; ?>
						</td>
						<td class="spk-wl-form" data-label="<?php echo $winlink ? esc_attr__( 'Form', 'spokares-core' ) : ''; ?>">
							<?php if ( $winlink ) : ?>
								<select name="<?php echo esc_attr( $name ); ?>[wl_form]" class="spk-form-select" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: date. */ __( 'Winlink form for %s', 'spokares-core' ), $label ) ); ?>">
									<option value="" <?php selected( $usual ); ?>><?php esc_html_e( 'ICS-213 (usual)', 'spokares-core' ); ?></option>
									<?php foreach ( $forms as $f ) : ?>
										<?php if ( 'ICS-213' !== $f ) : ?>
											<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $f, $v['wl_form'] ); ?>><?php echo esc_html( $f ); ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
									<option value="__other" <?php selected( $other ); ?>><?php esc_html_e( 'Other…', 'spokares-core' ); ?></option>
								</select>
								<input type="text" class="spk-form-other<?php echo esc_attr( spokares_err_class( $errors, "$d-wl_form" ) ); ?>" name="<?php echo esc_attr( $name ); ?>[wl_form_other]" value="<?php echo esc_attr( $other ? $v['wl_form'] : '' ); ?>" maxlength="60" placeholder="<?php esc_attr_e( 'Form name', 'spokares-core' ); ?>" aria-label="<?php esc_attr_e( 'Other form name', 'spokares-core' ); ?>" <?php echo $other ? '' : 'hidden'; ?>>
								<?php spokares_err_text( $errors, "$d-wl_form" ); ?>
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
				<?php if ( $weeks < 52 ) : ?>
					<?php // Without the script, the link opens the screen with 13 more rows; the script shows them in place. ?>
					<a class="spk-more-link" href="<?php echo esc_url( add_query_arg( 'weeks', min( 52, $weeks + 13 ), admin_url( 'admin.php?page=spokares-rota' ) ) ); ?>"><?php esc_html_e( 'Show 13 more Tuesdays', 'spokares-core' ); ?></a>
				<?php else : ?>
					<?php esc_html_e( 'That’s as far ahead as you can post.', 'spokares-core' ); ?>
				<?php endif; ?>
			</p>
			<p class="submit spk-submit spk-savebar">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button>
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
 * The screen's words for admin-forms.js (window.spokaresRota).
 *
 * @param string $hook Screen hook.
 */
function spokares_rota_script_words( $hook ): void {
	if ( ! str_ends_with( (string) $hook, '_page_spokares-rota' ) || ! wp_script_is( 'spokares-admin-forms', 'enqueued' ) ) {
		return;
	}
	wp_add_inline_script(
		'spokares-admin-forms',
		'window.spokaresRota = ' . wp_json_encode(
			array(
				'noCall' => __( 'Type a call sign, like NZ2S, not a name.', 'spokares-core' ),
				'end'    => __( 'That’s as far ahead as you can post.', 'spokares-core' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'spokares_rota_script_words', 20 );

/**
 * The "Publish it" tick beside a schedule field that holds a phone number
 * or e-mail address.
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
 * The Tuesdays a set of problems is about (their field keys start with the
 * date), leaving out the "Publish it" ticks.
 *
 * @param array $errors Field key => sentence.
 * @return string[] Dates.
 */
function spokares_rota_error_dates( array $errors ): array {
	$dates = array();
	foreach ( array_keys( $errors ) as $key ) {
		if ( ! str_ends_with( (string) $key, '-confirm' ) ) {
			$dates[] = substr( (string) $key, 0, 10 );
		}
	}
	return array_values( array_unique( $dates ) );
}

/**
 * Save the schedule: changed rows only, then one notice.
 */
function spokares_handle_save_rota(): void {
	spokares_verify_form( 'spokares_save_rota', 'spokares_edit_rota' );
	// Two editors saving at the same moment: the second waits for the first,
	// then reads the rows fresh, so the per-row conflict check sees both.
	spokares_lock_option( 'spk_rota', array( 'spk_rota_saved', 'spk_rota_prev' ) );

	$weeks  = isset( $_POST['weeks'] ) ? max( 13, min( 52, absint( $_POST['weeks'] ) ) ) : 13;
	$posted = isset( $_POST['rota'] ) && is_array( $_POST['rota'] ) ? wp_unslash( $_POST['rota'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field is sanitised below.
	$stored = spokares_opt( 'spk_rota' );
	$saver  = get_option( 'spk_rota_saved', array() );
	$prev   = array();
	$held   = array();
	$errors = array();
	$cut    = array();

	foreach ( $posted as $ymd => $p ) {
		$ymd = (string) $ymd;
		if ( ! spokares_is_ymd( $ymd ) || 2 !== spokares_weekday( $ymd ) || ! is_array( $p ) ) {
			continue;
		}
		$orig    = sanitize_text_field( spokares_post_str( $p, 'h' ) );
		$cur     = $stored[ $ymd ] ?? null;
		$state   = spokares_post_str( $p, 'state' );
		$state   = in_array( $state, array( 'call', 'open', 'none', 'tbd' ), true ) ? $state : 'tbd';
		$typed   = sanitize_text_field( spokares_post_str( $p, 'call' ) );
		$wl_form = sanitize_text_field( spokares_post_str( $p, 'wl_form' ) );
		if ( '__other' === $wl_form ) {
			$wl_form = sanitize_text_field( spokares_post_str( $p, 'wl_form_other' ) );
		}
		// "ICS-213 (usual)" is the blank choice. A row stored as 'ICS-213'
		// shows it, so a blank from that row is the stored value, unchanged.
		if ( '' === $wl_form && 'ICS-213' === ( $cur['wl_form'] ?? '' ) ) {
			$wl_form = 'ICS-213';
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
					$errors[ "$ymd-call" ] = __( 'Type a call sign, like NZ2S, not a name.', 'spokares-core' );
					$held[ $ymd ]          = $typed_row;
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
				/* translators: %s: a person's name. */
				__( '%s changed this Tuesday while you were editing. Your entry wasn’t saved.', 'spokares-core' ),
				spokares_user_name( (int) ( $saver['by'] ?? 0 ) )
			);
			$held[ $ymd ] = $typed_row;
			continue;
		}

		// Free-text fields: never-publish words hold the field back; phone
		// numbers and e-mail addresses need the "Publish it" tick.
		$confirmed = is_array( $cur['confirmed'] ?? null ) ? $cur['confirmed'] : array();
		foreach ( array(
			'note'    => 80,
			'wl_task' => 120,
			'wl_form' => 60,
		) as $field => $max ) {
			// The form's maxlength, checked again (a request can skip it).
			if ( spokares_too_long( $cand[ $field ], $max ) ) {
				/* translators: %d: number of characters. */
				$errors[ "$ymd-$field" ] = sprintf( __( 'Keep it to %d characters.', 'spokares-core' ), $max );
				$held[ $ymd ][ $field ]  = $cand[ $field ];
				$cand[ $field ]          = (string) ( $cur[ $field ] ?? '' );
			}
		}
		foreach ( array( 'note', 'wl_task' ) as $field ) {
			if ( isset( $errors[ "$ymd-$field" ] ) ) {
				continue;
			}
			$check = spokares_check_field( $cand[ $field ], $field, $confirmed, ! empty( $p[ 'confirm_' . $field ] ) );
			if ( $check['block'] || $check['confirm'] ) {
				$errors[ "$ymd-$field" ] = spokares_problem_sentence( $check );
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
		// A Not posted yet row with nothing in it is no row at all. A No net
		// row is kept, even empty: it is posted.
		if ( 'tbd' === $cand['state'] && '' === $cand['note'] && '' === $cand['wl_task'] && '' === $cand['wl_form'] ) {
			unset( $stored[ $ymd ] );
		} else {
			if ( $confirmed ) {
				$cand['confirmed'] = $confirmed;
			}
			$stored[ $ymd ] = $cand;
		}
		if ( $dropped ) {
			/* translators: %s: call sign. */
			$cut[ $ymd ] = sprintf( __( '%s (names aren’t posted)', 'spokares-core' ), $call );
		}
	}

	$saved = array_keys( $prev );
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
		update_option( 'spk_rota_saved', array_merge( $stamp, array( 'dates' => $saved ) ), false );
		set_transient( 'spokares_rota_changed_' . get_current_user_id(), $saved, 5 * MINUTE_IN_SECONDS );
		spokares_purge_cache();
	}

	// One notice for the whole save.
	$bad  = spokares_rota_error_dates( $errors );
	$link = spokares_site_url( '/members/', 'rota' );
	$see  = __( 'See it on the For members page', 'spokares-core' );
	if ( $saved && ! $bad ) {
		$rows  = spokares_rota_rows( 5 );
		$fifth = (string) end( $rows )['date'];
		$text  = sprintf(
			/* translators: %s: the Tuesdays saved, e.g. "Oct 13 and Oct 20". */
			__( 'Saved %s.', 'spokares-core' ),
			spokares_rota_dates_phrase( $saved, $cut )
		);
		if ( max( $saved ) > $fifth ) {
			$text .= ' ' . __( 'The For members page lists the next five Tuesdays.', 'spokares-core' );
		}
		spokares_add_notice( 'success', $text, $link, $see );
	} elseif ( $saved ) {
		spokares_add_notice(
			'warning',
			sprintf(
				/* translators: 1: the Tuesdays saved, 2: the Tuesdays not saved. */
				__( 'Saved %1$s. Not saved: %2$s (outlined in red).', 'spokares-core' ),
				spokares_rota_dates_phrase( $saved, $cut ),
				spokares_rota_dates_phrase( $bad )
			),
			$link,
			$see
		);
	} elseif ( $bad ) {
		/* translators: %s: the Tuesdays not saved. */
		spokares_add_notice( 'error', sprintf( __( 'Not saved: %s (outlined in red).', 'spokares-core' ), spokares_rota_dates_phrase( $bad ) ) );
	} else {
		spokares_add_notice( 'info', __( 'Nothing changed, so nothing was saved.', 'spokares-core' ) );
	}
	if ( $held || $errors ) {
		spokares_retain( 'spokares-rota', $held, $errors );
	}
	spokares_redirect_to( 'spokares-rota', $weeks > 13 ? array( 'weeks' => $weeks ) : array() );
}
add_action( 'admin_post_spokares_save_rota', 'spokares_handle_save_rota' );

/**
 * Undo the last save: put back the rows it changed (whoever made it). Undo
 * again after an undo is Redo: it puts the saved rows back.
 */
function spokares_handle_undo_rota(): void {
	spokares_verify_form( 'spokares_undo_rota', 'spokares_edit_rota' );
	spokares_lock_option( 'spk_rota', array( 'spk_rota_saved', 'spk_rota_prev' ) );
	$weeks = isset( $_POST['weeks'] ) ? max( 13, min( 52, absint( $_POST['weeks'] ) ) ) : 13;
	$args  = $weeks > 13 ? array( 'weeks' => $weeks ) : array();
	$prev  = get_option( 'spk_rota_prev', array() );
	if ( ! is_array( $prev ) || empty( $prev['rows'] ) || ! is_array( $prev['rows'] ) ) {
		spokares_add_notice( 'info', __( 'There is nothing to undo.', 'spokares-core' ) );
		spokares_redirect_to( 'spokares-rota', $args );
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
	$undoing = empty( $prev['undo'] );
	$dates   = array_keys( $redo );
	$stamp   = spokares_stamp();
	update_option( 'spk_rota', $stored );
	// What this took away, flagged as an undo so the title line offers Redo
	// (and a Redo's rows go back to offering Undo).
	update_option(
		'spk_rota_prev',
		array_merge(
			$stamp,
			array(
				'rows' => $redo,
				'undo' => $undoing,
			)
		),
		false
	);
	update_option(
		'spk_rota_saved',
		array_merge(
			$stamp,
			array(
				'dates'  => $dates,
				'undone' => $undoing,
			)
		),
		false
	);
	set_transient( 'spokares_rota_changed_' . get_current_user_id(), $dates, 5 * MINUTE_IN_SECONDS );
	spokares_purge_cache();
	$phrase = spokares_rota_dates_phrase( $dates );
	$one    = 1 === count( $dates );
	if ( $undoing ) {
		$text = $one
			/* translators: %s: a Tuesday, e.g. "Oct 13". */
			? sprintf( __( 'Undone: %s is back as it was.', 'spokares-core' ), $phrase )
			/* translators: %s: the Tuesdays, e.g. "Oct 13 and Oct 20". */
			: sprintf( __( 'Undone: %s are back as they were.', 'spokares-core' ), $phrase );
	} else {
		$text = $one
			/* translators: %s: a Tuesday, e.g. "Oct 13". */
			? sprintf( __( 'Redone: %s is back as it was saved.', 'spokares-core' ), $phrase )
			/* translators: %s: the Tuesdays, e.g. "Oct 13 and Oct 20". */
			: sprintf( __( 'Redone: %s are back as they were saved.', 'spokares-core' ), $phrase );
	}
	spokares_add_notice( 'success', $text, spokares_site_url( '/members/', 'rota' ), __( 'See it on the For members page', 'spokares-core' ) );
	spokares_redirect_to( 'spokares-rota', $args );
}
add_action( 'admin_post_spokares_undo_rota', 'spokares_handle_undo_rota' );
