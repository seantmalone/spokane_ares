<?php
/**
 * Regular meetings (cancel or move one date) and Meeting rules (the weeks,
 * days and times), §3.4.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fingerprint of a meeting change (null = no change).
 *
 * @param array|null $c Change.
 */
function spokares_change_hash( ?array $c ): string {
	if ( ! $c ) {
		return md5( 'none' );
	}
	$kind = $c['kind'] ?? '';
	$new  = 'moved' === $kind ? ( $c['new_date'] ?? '' ) : '';
	return md5( (string) wp_json_encode( array( $kind, $new, $c['note'] ?? '' ) ) );
}

/**
 * The rule dates shown for a meeting: the next five, plus any later date that
 * already has a change.
 *
 * @param array $m Meeting.
 */
function spokares_meeting_admin_dates( array $m ): array {
	$today = spokares_today();
	$dates = array_slice( spokares_meeting_rule_dates( $m, $today, spokares_add_days( $today, 400 ) ), 0, 5 );
	foreach ( spokares_opt( 'spk_meetings' )['changes'] as $c ) {
		if ( $c['meeting'] === $m['id'] && $c['date'] >= $today && ! in_array( $c['date'], $dates, true ) ) {
			$dates[] = $c['date'];
		}
	}
	sort( $dates );
	return $dates;
}

/**
 * The Regular meetings screen.
 */
function spokares_meetings_page(): void {
	if ( ! current_user_can( 'spokares_edit_rota' ) ) {
		return;
	}
	$data     = spokares_opt( 'spk_meetings' );
	$retained = spokares_retained( 'spokares-meetings' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	?>
	<div class="wrap spk-screen spk-meetings">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Regular meetings: cancel or move one date', 'spokares-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/', 'visit' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="spokares_save_meetings">
			<?php wp_nonce_field( 'spokares_save_meetings' ); ?>
			<table class="widefat spk-table spk-meetings-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Meeting', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Cancelled', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Moved to', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Note', 'spokares-core' ); ?></th>
					</tr>
				</thead>
				<?php
				foreach ( $data['meetings'] as $m ) :
					if ( ! $m['active'] ) {
						continue;
					}
					$dates = spokares_meeting_admin_dates( $m );
					?>
					<tbody class="spk-meeting">
					<?php
					foreach ( $dates as $i => $d ) :
						$c     = spokares_meeting_change( $m['id'], $d );
						$key   = $m['id'] . '|' . $d;
						$v     = array(
							'cancelled' => $c && 'cancelled' === $c['kind'],
							'moved'     => $c && 'moved' === $c['kind'] ? $c['new_date'] : '',
							'note'      => $c ? $c['note'] : '',
						);
						$v     = isset( $held[ $key ] ) && is_array( $held[ $key ] ) ? array_merge( $v, $held[ $key ] ) : $v;
						$name  = 'm[' . $m['id'] . '][' . $d . ']';
						$label = spokares_fmt_date( $d, 'short' );
						?>
						<tr class="spk-meeting-row<?php echo isset( $errors[ $key ] ) ? ' spk-row-error' : ''; ?>">
							<th scope="row"><?php echo 0 === $i ? esc_html( $m['name'] ) : ''; ?></th>
							<td>
								<?php echo esc_html( $label ); ?>
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[h]" value="<?php echo esc_attr( spokares_change_hash( $c ) ); ?>">
							</td>
							<td><label><input type="checkbox" class="spk-cancel" name="<?php echo esc_attr( $name ); ?>[cancelled]" value="1" <?php checked( (bool) $v['cancelled'] ); ?>> <span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: 1: meeting, 2: date. */ __( '%1$s on %2$s is cancelled', 'spokares-core' ), $m['name'], $label ) ); ?></span></label></td>
							<td><input type="date" class="spk-moved" name="<?php echo esc_attr( $name ); ?>[moved]" value="<?php echo esc_attr( (string) $v['moved'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: meeting, 2: date. */ __( '%1$s on %2$s moved to', 'spokares-core' ), $m['name'], $label ) ); ?>"></td>
							<td>
								<input type="text" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, $key . '-note' ) ); ?>" name="<?php echo esc_attr( $name ); ?>[note]" value="<?php echo esc_attr( (string) $v['note'] ); ?>" maxlength="120" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: meeting, 2: date. */ __( 'Note for %1$s on %2$s', 'spokares-core' ), $m['name'], $label ) ); ?>">
								<?php spokares_err_text( $errors, $key ); ?>
								<?php spokares_err_text( $errors, $key . '-note' ); ?>
								<?php if ( ! empty( $errors[ $key . '-confirm' ] ) ) : ?>
									<label class="spk-confirm"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[confirm]" value="1"> <?php echo esc_html( $errors[ $key . '-confirm' ] ); ?></label>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				<?php endforeach; ?>
			</table>
			<p class="description"><?php esc_html_e( 'Meeting times and weeks are on Meeting rules (ask the webmaster).', 'spokares-core' ); ?></p>
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * Save Regular meetings: changed rows only.
 */
function spokares_handle_save_meetings(): void {
	spokares_verify_form( 'spokares_save_meetings', 'spokares_edit_rota' );
	// Read-change-write of one option: wait for a save in progress, then read fresh.
	spokares_lock_option( 'spk_meetings' );
	$posted  = isset( $_POST['m'] ) && is_array( $_POST['m'] ) ? wp_unslash( $_POST['m'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$data    = spokares_opt( 'spk_meetings' );
	$ids     = wp_list_pluck( $data['meetings'], 'name', 'id' );
	$changes = array();
	foreach ( $data['changes'] as $c ) {
		$changes[ $c['meeting'] . '|' . $c['date'] ] = $c;
	}
	$confirmed = is_array( $data['confirmed'] ?? null ) ? $data['confirmed'] : array();
	$held      = array();
	$errors    = array();
	$saved     = 0;

	foreach ( $posted as $mid => $rows ) {
		$mid = sanitize_key( (string) $mid );
		if ( ! isset( $ids[ $mid ] ) || ! is_array( $rows ) ) {
			continue;
		}
		foreach ( $rows as $d => $p ) {
			$d = (string) $d;
			if ( ! spokares_is_ymd( $d ) || ! is_array( $p ) ) {
				continue;
			}
			$key   = $mid . '|' . $d;
			$label = sprintf( '%s, %s', $ids[ $mid ], spokares_fmt_date( $d, 'short-noyear' ) );
			$orig  = sanitize_text_field( spokares_post_str( $p, 'h' ) );
			$moved = sanitize_text_field( spokares_post_str( $p, 'moved' ) );
			$note  = sanitize_text_field( spokares_post_str( $p, 'note' ) );
			$typed = array(
				'cancelled' => ! empty( $p['cancelled'] ),
				'moved'     => $moved,
				'note'      => $note,
			);
			if ( '' !== $moved && ( ! spokares_is_ymd( $moved ) || $moved === $d ) ) {
				$errors[ $key ] = sprintf( /* translators: %s: meeting and date. */ __( '%s not saved: “Moved to” needs a different date.', 'spokares-core' ), $label );
				$held[ $key ]   = $typed;
				continue;
			}
			$cand = null;
			if ( '' !== $moved ) {
				$cand = array(
					'date'     => $d,
					'meeting'  => $mid,
					'kind'     => 'moved',
					'new_date' => $moved,
					'note'     => $note,
				);
			} elseif ( $typed['cancelled'] ) {
				$cand = array(
					'date'     => $d,
					'meeting'  => $mid,
					'kind'     => 'cancelled',
					'new_date' => '',
					'note'     => $note,
				);
			} elseif ( '' !== $note ) {
				$errors[ $key ] = sprintf( /* translators: %s: meeting and date. */ __( '%s not saved: tick Cancelled or fill in Moved to, then add the note.', 'spokares-core' ), $label );
				$held[ $key ]   = $typed;
				continue;
			}

			$cur = $changes[ $key ] ?? null;
			if ( hash_equals( $orig, spokares_change_hash( $cand ) ) ) {
				continue;
			}
			if ( ! hash_equals( $orig, spokares_change_hash( $cur ) ) ) {
				$errors[ $key ] = sprintf( /* translators: %s: meeting and date. */ __( '%s was changed by someone else while you were editing. Your entry wasn’t saved.', 'spokares-core' ), $label );
				$held[ $key ]   = $typed;
				continue;
			}
			if ( $cand && '' !== $note ) {
				$check = spokares_check_field( $note, $key, $confirmed, ! empty( $p['confirm'] ) );
				if ( $check['block'] || $check['confirm'] ) {
					$what                     = $check['block'] ? $check['block'] : wp_list_pluck( $check['confirm'], 'what' );
					$errors[ $key . '-note' ] = sprintf( /* translators: 1: meeting and date, 2: what was found. */ __( '%1$s: the note wasn’t saved because it mentions %2$s.', 'spokares-core' ), $label, spokares_and_list( $what ) );
					if ( ! $check['block'] ) {
						$errors[ $key . '-confirm' ] = spokares_confirm_label( $check['confirm'] );
					}
					$held[ $key ] = $typed;
					$cand['note'] = (string) ( $cur['note'] ?? '' );
				} elseif ( $check['confirmed'] ) {
					$confirmed[ $key ] = sha1( $note );
				}
			}
			if ( $cand ) {
				$changes[ $key ] = $cand;
			} else {
				unset( $changes[ $key ] );
			}
			++$saved;
		}
	}

	if ( $saved ) {
		$cutoff = spokares_add_days( spokares_today(), -365 );
		$list   = array_values( array_filter( $changes, static fn( $c ) => $c['date'] >= $cutoff ) );
		usort( $list, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
		$data['changes'] = $list;
		if ( $confirmed ) {
			$data['confirmed'] = $confirmed;
		}
		update_option( 'spk_meetings', $data );
		spokares_purge_cache();
		spokares_add_notice( 'success', __( 'Saved.', 'spokares-core' ), spokares_site_url( '/', 'visit' ), __( 'See it on Home', 'spokares-core' ) );
	} elseif ( ! $errors ) {
		spokares_add_notice( 'info', __( 'Nothing changed, so nothing was saved.', 'spokares-core' ) );
	}
	foreach ( $errors as $k => $message ) {
		if ( ! str_ends_with( (string) $k, '-confirm' ) ) {
			spokares_add_notice( 'error', $message );
		}
	}
	if ( $errors ) {
		spokares_retain( 'spokares-meetings', $held, $errors );
	}
	spokares_redirect_to( 'spokares-meetings' );
}
add_action( 'admin_post_spokares_save_meetings', 'spokares_handle_save_meetings' );

/* ------------------------------------------------------------ meeting rules */

/**
 * Weekday names (0 = Sunday).
 */
function spokares_weekday_names(): array {
	return array(
		__( 'Sunday', 'spokares-core' ),
		__( 'Monday', 'spokares-core' ),
		__( 'Tuesday', 'spokares-core' ),
		__( 'Wednesday', 'spokares-core' ),
		__( 'Thursday', 'spokares-core' ),
		__( 'Friday', 'spokares-core' ),
		__( 'Saturday', 'spokares-core' ),
	);
}

/**
 * One meeting's rule fields.
 *
 * @param int    $i      Index.
 * @param array  $m      Meeting (normalised).
 * @param array  $errors Errors.
 * @param bool   $admin  Show admin-only fields.
 */
function spokares_meeting_rule_fields( int $i, array $m, array $errors, bool $admin ): void {
	$n     = 'rules[' . $i . ']';
	$new   = '' === $m['id'];
	$names = spokares_weekday_names();
	$pre   = 'spk-rule-' . $i . '-';
	?>
	<fieldset class="spk-card spk-rule<?php echo isset( $errors[ "r$i" ] ) ? ' spk-row-error' : ''; ?>">
		<legend><?php echo $new ? esc_html__( 'Add a meeting', 'spokares-core' ) : esc_html( $m['name'] ); ?></legend>
		<?php spokares_err_text( $errors, "r$i" ); ?>
		<input type="hidden" name="<?php echo esc_attr( $n ); ?>[id]" value="<?php echo esc_attr( $m['id'] ); ?>">
		<p><label for="<?php echo esc_attr( $pre . 'name' ); ?>"><?php esc_html_e( 'Name', 'spokares-core' ); ?></label><br>
			<input type="text" class="regular-text" id="<?php echo esc_attr( $pre . 'name' ); ?>" name="<?php echo esc_attr( $n ); ?>[name]" value="<?php echo esc_attr( $m['name'] ); ?>" maxlength="80"></p>
		<div class="spk-rule-grid">
			<div><span class="spk-label"><?php esc_html_e( 'Week of the month', 'spokares-core' ); ?></span>
				<?php spokares_week_boxes( $n . '[nth]', $m['nth'], __( 'Week of the month', 'spokares-core' ) ); ?></div>
			<div><label for="<?php echo esc_attr( $pre . 'day' ); ?>"><?php esc_html_e( 'Day', 'spokares-core' ); ?></label><br>
				<select id="<?php echo esc_attr( $pre . 'day' ); ?>" name="<?php echo esc_attr( $n ); ?>[weekday]">
					<?php foreach ( $names as $wd => $day ) : ?>
						<option value="<?php echo esc_attr( (string) $wd ); ?>" <?php selected( $wd, (int) $m['weekday'] ); ?>><?php echo esc_html( $day ); ?></option>
					<?php endforeach; ?>
				</select></div>
			<div><label for="<?php echo esc_attr( $pre . 'start' ); ?>"><?php esc_html_e( 'Start', 'spokares-core' ); ?></label><br>
				<input type="time" id="<?php echo esc_attr( $pre . 'start' ); ?>" name="<?php echo esc_attr( $n ); ?>[start]" value="<?php echo esc_attr( $m['start'] ); ?>"></div>
			<div><label for="<?php echo esc_attr( $pre . 'end' ); ?>"><?php esc_html_e( 'End', 'spokares-core' ); ?></label><br>
				<input type="time" id="<?php echo esc_attr( $pre . 'end' ); ?>" name="<?php echo esc_attr( $n ); ?>[end]" value="<?php echo esc_attr( $m['end'] ); ?>"></div>
		</div>
		<p><label for="<?php echo esc_attr( $pre . 'tt' ); ?>"><?php esc_html_e( 'Time words (instead of the times on Home, e.g. “evenings”)', 'spokares-core' ); ?></label><br>
			<input type="text" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, "r$i-time_text" ) ); ?>" id="<?php echo esc_attr( $pre . 'tt' ); ?>" name="<?php echo esc_attr( $n ); ?>[time_text]" value="<?php echo esc_attr( $m['time_text'] ); ?>" maxlength="40">
			<?php spokares_err_text( $errors, "r$i-time_text" ); ?></p>
		<p><label for="<?php echo esc_attr( $pre . 'hx' ); ?>"><?php esc_html_e( 'Extra words on Home (after the time)', 'spokares-core' ); ?></label><br>
			<input type="text" class="large-text<?php echo esc_attr( spokares_err_class( $errors, "r$i-home_extra" ) ); ?>" id="<?php echo esc_attr( $pre . 'hx' ); ?>" name="<?php echo esc_attr( $n ); ?>[home_extra]" value="<?php echo esc_attr( $m['home_extra'] ); ?>" maxlength="120">
			<?php spokares_err_text( $errors, "r$i-home_extra" ); ?>
			<?php if ( ! empty( $errors[ "r$i-home_extra-confirm" ] ) ) : ?>
				<label class="spk-confirm"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[confirm_home_extra]" value="1"> <?php echo esc_html( $errors[ "r$i-home_extra-confirm" ] ); ?></label>
			<?php endif; ?></p>
		<fieldset class="spk-months"><legend><?php esc_html_e( 'Skip these months', 'spokares-core' ); ?></legend>
			<?php for ( $mo = 1; $mo <= 12; $mo++ ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[skip_months][]" value="<?php echo esc_attr( (string) $mo ); ?>" <?php checked( in_array( $mo, $m['skip_months'], true ) ); ?>> <?php echo esc_html( gmdate( 'M', gmmktime( 0, 0, 0, $mo, 1, 2026 ) ) ); ?></label>
			<?php endfor; ?>
		</fieldset>
		<p>
			<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[show_home]" value="1" <?php checked( $m['show_home'] ); ?>> <?php esc_html_e( 'On Home and the members hub', 'spokares-core' ); ?></label>
			&nbsp; <label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[active]" value="1" <?php checked( $m['active'] ); ?>> <?php esc_html_e( 'Active', 'spokares-core' ); ?></label>
			<?php if ( $admin ) : ?>
				&nbsp; <label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[needs_check]" value="1" <?php checked( $m['needs_check'] ); ?>> <?php esc_html_e( 'Needs checking (webmaster only)', 'spokares-core' ); ?></label>
			<?php endif; ?>
		</p>
	</fieldset>
	<?php
}

/**
 * The Meeting rules screen.
 */
function spokares_meeting_rules_page(): void {
	if ( ! current_user_can( 'spokares_edit_net_details' ) ) {
		return;
	}
	$data     = spokares_opt( 'spk_meetings' );
	$retained = spokares_retained( 'spokares-meeting-rules' );
	$errors   = $retained['errors'];
	$held     = $retained['values'];
	$admin    = current_user_can( 'manage_options' );
	$rules    = $data['meetings'];
	$rules[]  = spokares_normalize_meeting(
		array(
			'id'     => '',
			'active' => true,
		)
	);
	?>
	<div class="wrap spk-screen spk-rules">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Meeting rules', 'spokares-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meetings' ) ); ?>"><?php esc_html_e( 'Cancel or move one date', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<p class="spk-lede"><?php esc_html_e( 'The weeks, days and times of the regular meetings. Rarely changed; to cancel or move a single date, use Regular meetings.', 'spokares-core' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-spk-confirm>
			<input type="hidden" name="action" value="spokares_save_meeting_rules">
			<?php wp_nonce_field( 'spokares_save_meeting_rules' ); ?>
			<?php
			foreach ( $rules as $i => $m ) {
				if ( isset( $held[ $i ] ) && is_array( $held[ $i ] ) ) {
					$m = spokares_normalize_meeting( array_merge( $m, $held[ $i ] ) );
				}
				spokares_meeting_rule_fields( (int) $i, $m, $errors, $admin );
			}
			?>
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save meeting rules', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * Save Meeting rules.
 */
function spokares_handle_save_meeting_rules(): void {
	spokares_verify_form( 'spokares_save_meeting_rules', 'spokares_edit_net_details' );
	spokares_lock_option( 'spk_meetings' );
	$posted = isset( $_POST['rules'] ) && is_array( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$admin  = current_user_can( 'manage_options' );
	$data   = spokares_opt( 'spk_meetings' );
	$by_id  = array();
	foreach ( $data['meetings'] as $m ) {
		$by_id[ $m['id'] ] = $m;
	}
	$confirmed = is_array( $data['confirmed'] ?? null ) ? $data['confirmed'] : array();
	$out       = array();
	$held      = array();
	$errors    = array();
	$seen      = array();

	foreach ( $posted as $i => $p ) {
		if ( ! is_array( $p ) ) {
			continue;
		}
		$i    = (int) $i;
		$id   = sanitize_key( spokares_post_str( $p, 'id' ) );
		$old  = '' !== $id && isset( $by_id[ $id ] ) ? $by_id[ $id ] : null;
		$name = sanitize_text_field( spokares_post_str( $p, 'name' ) );
		$nth  = array_values( array_unique( array_filter( array_map( 'absint', array_filter( (array) ( $p['nth'] ?? array() ), 'is_scalar' ) ), static fn( $n ) => $n >= 1 && $n <= 5 ) ) );
		sort( $nth );
		$typed                = array(
			'name'        => $name,
			'nth'         => $nth,
			'weekday'     => max( 0, min( 6, absint( '' !== spokares_post_str( $p, 'weekday' ) ? spokares_post_str( $p, 'weekday' ) : 6 ) ) ),
			'start'       => spokares_is_hhmm( spokares_post_str( $p, 'start' ) ) ? spokares_post_str( $p, 'start' ) : '',
			'end'         => spokares_is_hhmm( spokares_post_str( $p, 'end' ) ) ? spokares_post_str( $p, 'end' ) : '',
			'time_text'   => sanitize_text_field( spokares_post_str( $p, 'time_text' ) ),
			'home_extra'  => sanitize_text_field( spokares_post_str( $p, 'home_extra' ) ),
			'skip_months' => array_values( array_filter( array_map( 'absint', array_filter( (array) ( $p['skip_months'] ?? array() ), 'is_scalar' ) ), static fn( $n ) => $n >= 1 && $n <= 12 ) ),
			'show_home'   => ! empty( $p['show_home'] ),
			'active'      => ! empty( $p['active'] ),
		);
		$typed['needs_check'] = $admin ? ! empty( $p['needs_check'] ) : (bool) ( $old['needs_check'] ?? false );

		if ( ! $old && '' === $name ) {
			continue; // The empty "Add a meeting" card.
		}
		$problem = '';
		if ( '' === $name ) {
			$problem = __( 'This meeting wasn’t saved: it needs a name.', 'spokares-core' );
		} elseif ( ! $nth ) {
			$problem = sprintf( /* translators: %s: meeting name. */ __( '%s wasn’t saved: tick at least one week of the month.', 'spokares-core' ), $name );
		} elseif ( '' !== $typed['start'] && '' !== $typed['end'] && $typed['end'] <= $typed['start'] ) {
			$problem = sprintf( /* translators: %s: meeting name. */ __( '%s wasn’t saved: the end time must be after the start.', 'spokares-core' ), $name );
		}
		if ( '' !== $problem ) {
			$errors[ "r$i" ] = $problem;
			$held[ $i ]      = $typed;
			if ( $old ) {
				$out[]              = $old;
				$seen[ $old['id'] ] = true;
			}
			continue;
		}
		$m = array_merge( $old ?? array(), $typed );
		if ( ! $old ) {
			$base = sanitize_title( $name );
			$id   = '' !== $base ? $base : 'meeting';
			$n    = 2;
			while ( isset( $by_id[ $id ] ) || isset( $seen[ $id ] ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
		}
		$m['id'] = $id;
		foreach ( array( 'time_text', 'home_extra' ) as $field ) {
			$check = spokares_check_field( $m[ $field ], $id . '-' . $field, $confirmed, ! empty( $p[ 'confirm_' . $field ] ) );
			if ( $check['block'] || $check['confirm'] ) {
				$what                   = $check['block'] ? $check['block'] : wp_list_pluck( $check['confirm'], 'what' );
				$errors[ "r$i-$field" ] = sprintf( /* translators: 1: meeting, 2: what was found. */ __( '%1$s: this wasn’t saved because it mentions %2$s.', 'spokares-core' ), $name, spokares_and_list( $what ) );
				if ( ! $check['block'] ) {
					$errors[ "r$i-$field-confirm" ] = spokares_confirm_label( $check['confirm'] );
				}
				$held[ $i ][ $field ] = $m[ $field ];
				$m[ $field ]          = (string) ( $old[ $field ] ?? '' );
			} elseif ( $check['confirmed'] ) {
				$confirmed[ $id . '-' . $field ] = sha1( $m[ $field ] );
			}
		}
		$out[]       = spokares_normalize_meeting( $m );
		$seen[ $id ] = true;
	}
	// A meeting missing from the form (never expected) is kept, not deleted.
	foreach ( $by_id as $id => $m ) {
		if ( ! isset( $seen[ $id ] ) ) {
			$out[] = $m;
		}
	}

	$data['meetings'] = $out;
	if ( $confirmed ) {
		$data['confirmed'] = $confirmed;
	}
	update_option( 'spk_meetings', $data );
	spokares_purge_cache();
	foreach ( $errors as $k => $message ) {
		if ( ! str_ends_with( (string) $k, '-confirm' ) ) {
			spokares_add_notice( 'error', $message );
		}
	}
	spokares_add_notice(
		$errors ? 'warning' : 'success',
		$errors ? __( 'Saved, except what is outlined in red.', 'spokares-core' ) : __( 'Saved.', 'spokares-core' ),
		spokares_site_url( '/', 'visit' ),
		__( 'See it on Home', 'spokares-core' )
	);
	if ( $errors ) {
		spokares_retain( 'spokares-meeting-rules', $held, $errors );
	}
	spokares_redirect_to( 'spokares-meeting-rules' );
}
add_action( 'admin_post_spokares_save_meeting_rules', 'spokares_handle_save_meeting_rules' );
