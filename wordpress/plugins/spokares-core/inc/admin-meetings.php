<?php
/**
 * Meetings (§3.5): Cancel or Move a Meeting (one date: cancelled, moved, or
 * a note for that day) and Meeting Schedule (the weeks, days and times, with
 * "Takes effect on" for a change from a later date).
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
 * Is a meeting shown on the site (Home and the For members page)?
 *
 * @param array $m Meeting.
 */
function spokares_meeting_shown( array $m ): bool {
	return ! empty( $m['active'] ) && ! empty( $m['show_home'] );
}

/**
 * The Cancel or Move a Meeting screen.
 */
function spokares_meetings_page(): void {
	if ( ! current_user_can( 'spokares_edit_rota' ) ) {
		return;
	}
	$data     = spokares_opt( 'spk_meetings' );
	$retained = spokares_retained( 'spokares-meetings' );
	$held     = $retained['values'];
	$errors   = $retained['errors'];
	$grant    = current_user_can( 'spokares_edit_net_details' );
	$schedule = (string) ( spokares_edit_screen( 'meeting-rules' )[0] ?? '' );
	// Only the meetings the site shows: a change to a hidden one would appear nowhere.
	$shown = array_values( array_filter( $data['meetings'], 'spokares_meeting_shown' ) );
	?>
	<div class="wrap spk-screen spk-meetings">
		<h1 class="wp-heading-inline"><?php echo esc_html( spokares_screen_title( 'meetings' ) ); ?></h1>
		<?php if ( $grant ) : ?>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meeting-rules' ) ); ?>"><?php echo esc_html( $schedule ); ?></a>
		<?php endif; ?>
		<a class="page-title-action" href="<?php echo esc_url( spokares_site_url( '/', 'visit' ) ); ?>"><?php esc_html_e( 'View on site', 'spokares-core' ); ?></a>
		<hr class="wp-header-end">
		<?php if ( ! $shown ) : ?>
			<p>
				<?php esc_html_e( 'No regular meetings are shown on the site.', 'spokares-core' ); ?>
				<?php if ( $grant ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meeting-rules' ) ); ?>"><?php echo esc_html( $schedule ); ?></a>
				<?php endif; ?>
			</p>
		<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-spk-guard>
			<input type="hidden" name="action" value="spokares_save_meetings">
			<?php wp_nonce_field( 'spokares_save_meetings' ); ?>
			<table class="widefat spk-table spk-meetings-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Meeting', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Cancelled', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Moved to', 'spokares-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Note (shown with the date)', 'spokares-core' ); ?></th>
					</tr>
				</thead>
				<?php
				foreach ( $shown as $m ) :
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
						$bad   = isset( $errors[ $key ] );
						?>
						<tr class="spk-meeting-row<?php echo $bad ? ' spk-row-error' : ''; ?>">
							<th scope="row"<?php echo 0 === $i ? '' : ' class="spk-name-repeat"'; ?>>
								<?php if ( 0 === $i ) : ?>
									<?php echo esc_html( $m['name'] ); ?>
								<?php else : ?>
									<?php // Every row names its meeting for screen readers; the name shows once. ?>
									<span class="screen-reader-text"><?php echo esc_html( $m['name'] ); ?></span>
								<?php endif; ?>
							</th>
							<td class="spk-meeting-date" data-label="<?php esc_attr_e( 'Date', 'spokares-core' ); ?>">
								<?php echo esc_html( $label ); ?>
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[h]" value="<?php echo esc_attr( spokares_change_hash( $c ) ); ?>">
							</td>
							<td data-label="<?php esc_attr_e( 'Cancelled', 'spokares-core' ); ?>"><label><input type="checkbox" class="spk-cancel" name="<?php echo esc_attr( $name ); ?>[cancelled]" value="1" data-stored-kind="<?php echo esc_attr( $c ? (string) $c['kind'] : '' ); ?>" <?php checked( (bool) $v['cancelled'] ); ?>> <span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: 1: meeting, 2: date. */ __( '%1$s on %2$s is cancelled', 'spokares-core' ), $m['name'], $label ) ); ?></span></label></td>
							<td data-label="<?php esc_attr_e( 'Moved to', 'spokares-core' ); ?>"><input type="date" class="spk-moved<?php echo $bad && '' !== (string) $v['moved'] ? ' spk-field-error' : ''; ?>" name="<?php echo esc_attr( $name ); ?>[moved]" value="<?php echo esc_attr( (string) $v['moved'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: meeting, 2: date. */ __( '%1$s on %2$s moved to', 'spokares-core' ), $m['name'], $label ) ); ?>"></td>
							<td data-label="<?php esc_attr_e( 'Note (shown with the date)', 'spokares-core' ); ?>">
								<input type="text" class="regular-text spk-note<?php echo esc_attr( spokares_err_class( $errors, $key . '-note' ) ); ?>" name="<?php echo esc_attr( $name ); ?>[note]" value="<?php echo esc_attr( (string) $v['note'] ); ?>" maxlength="120" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: meeting, 2: date. */ __( 'Note for %1$s on %2$s', 'spokares-core' ), $m['name'], $label ) ); ?>">
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
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button></p>
		</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save Cancel or Move a Meeting: changed rows only, then one notice that
 * names the dates.
 */
function spokares_handle_save_meetings(): void {
	spokares_verify_form( 'spokares_save_meetings', 'spokares_edit_rota' );
	// Read-change-write of one option: wait for a save in progress, then read fresh.
	spokares_lock_option( 'spk_meetings' );
	$posted = isset( $_POST['m'] ) && is_array( $_POST['m'] ) ? wp_unslash( $_POST['m'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$data   = spokares_opt( 'spk_meetings' );
	$ids    = wp_list_pluck( $data['meetings'], 'name', 'id' );
	$rules  = array();
	foreach ( $data['meetings'] as $m ) {
		$rules[ $m['id'] ] = $m;
	}
	$today   = spokares_today();
	$changes = array();
	foreach ( $data['changes'] as $c ) {
		$changes[ $c['meeting'] . '|' . $c['date'] ] = $c;
	}
	$confirmed = is_array( $data['confirmed'] ?? null ) ? $data['confirmed'] : array();
	$held      = array();
	$errors    = array();
	$done      = array();
	$failed    = array();

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
			$label = spokares_fmt_date( $d, 'short' );
			$sort  = $d . '|' . $mid;
			$orig  = sanitize_text_field( spokares_post_str( $p, 'h' ) );
			$moved = sanitize_text_field( spokares_post_str( $p, 'moved' ) );
			$note  = sanitize_text_field( spokares_post_str( $p, 'note' ) );
			$typed = array(
				'cancelled' => ! empty( $p['cancelled'] ),
				'moved'     => $moved,
				'note'      => $note,
			);
			$cur   = $changes[ $key ] ?? null;
			// Unticking Cancelled (or clearing Moved to) puts the date back on:
			// the note left from the cancellation or move goes with it.
			if ( ! $typed['cancelled'] && '' === $moved && $cur && in_array( $cur['kind'], array( 'cancelled', 'moved' ), true ) && $note === (string) $cur['note'] ) {
				$note = '';
			}
			if ( '' !== $moved && ( ! spokares_is_ymd( $moved ) || $moved === $d ) ) {
				$errors[ $key ]  = $moved === $d
					? __( 'Pick a different date. For a new time or room on the same day, use the Note alone.', 'spokares-core' )
					: __( 'Pick the new date.', 'spokares-core' );
				$held[ $key ]    = $typed;
				$failed[ $sort ] = $label;
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
				// A note on its own: the meeting is on, with a word for that day.
				$cand = array(
					'date'     => $d,
					'meeting'  => $mid,
					'kind'     => 'note',
					'new_date' => '',
					'note'     => $note,
				);
			}

			if ( hash_equals( $orig, spokares_change_hash( $cand ) ) ) {
				continue;
			}
			if ( ! hash_equals( $orig, spokares_change_hash( $cur ) ) ) {
				$errors[ $key ]  = __( 'Someone else changed this date while you were editing. Your entry wasn’t saved.', 'spokares-core' );
				$held[ $key ]    = $typed;
				$failed[ $sort ] = $label;
				continue;
			}
			// A new "Moved to" date must be a real later date (§4.4): not in
			// the past (Home would skip the meeting without a word), and not
			// one of this meeting's own scheduled dates.
			if ( $cand && 'moved' === $cand['kind'] ) {
				$problem = '';
				if ( $moved < $today ) {
					$problem = __( '“Moved to” must be today or later.', 'spokares-core' );
				} elseif ( spokares_meeting_on( $rules[ $mid ], $moved ) ) {
					$problem = __( '“Moved to” is already one of this meeting’s dates. Pick a different day, or tick Cancelled.', 'spokares-core' );
				}
				if ( '' !== $problem ) {
					$errors[ $key ]  = $problem;
					$held[ $key ]    = $typed;
					$failed[ $sort ] = $label;
					continue;
				}
			}
			$note_ok = true;
			if ( $cand && spokares_too_long( $note, 120 ) ) {
				/* translators: %d: number of characters. */
				$errors[ $key . '-note' ] = sprintf( __( 'Keep it to %d characters.', 'spokares-core' ), 120 );
				$note_ok                  = false;
			} elseif ( $cand && '' !== $note ) {
				$check = spokares_check_field( $note, $key, $confirmed, ! empty( $p['confirm'] ) );
				if ( $check['block'] || $check['confirm'] ) {
					$errors[ $key . '-note' ] = spokares_problem_sentence( $check );
					if ( ! $check['block'] ) {
						$errors[ $key . '-confirm' ] = spokares_confirm_label( $check['confirm'] );
					}
					$note_ok = false;
				} elseif ( $check['confirmed'] ) {
					$confirmed[ $key ] = sha1( $note );
				}
			}
			if ( ! $note_ok ) {
				$held[ $key ]    = $typed;
				$failed[ $sort ] = $label;
				if ( 'note' === $cand['kind'] ) {
					continue; // The note was the whole change.
				}
				// The cancellation or move is saved, with the note it had.
				$cand['note'] = (string) ( $cur['note'] ?? '' );
				if ( spokares_change_hash( $cand ) === spokares_change_hash( $cur ) ) {
					continue; // Only the note changed, and it wasn't saved.
				}
			}
			if ( $cand ) {
				$changes[ $key ] = $cand;
			} else {
				unset( $changes[ $key ] );
			}
			$done[ $sort ] = spokares_meeting_change_words( $cand, $cur, $d );
		}
	}

	if ( $done ) {
		$cutoff = spokares_add_days( spokares_today(), -365 );
		$list   = array_values( array_filter( $changes, static fn( $c ) => $c['date'] >= $cutoff ) );
		usort( $list, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
		$data['changes'] = $list;
		if ( $confirmed ) {
			$data['confirmed'] = $confirmed;
		}
		update_option( 'spk_meetings', $data );
		spokares_purge_cache();
	}
	ksort( $done );
	ksort( $failed );
	/* translators: %s: what was saved, e.g. "Sat, Oct 10 cancelled; Thu, Oct 15 moved to Wed, Oct 14". */
	$saved = $done ? sprintf( __( 'Saved: %s.', 'spokares-core' ), implode( '; ', $done ) ) : '';
	/* translators: %s: dates, e.g. "Thu, Oct 15". */
	$not = $failed ? sprintf( __( 'Not saved: %s (outlined in red).', 'spokares-core' ), spokares_and_list( array_values( array_unique( $failed ) ) ) ) : '';
	$url = spokares_site_url( '/', 'visit' );
	$see = __( 'See it on the Home page', 'spokares-core' );
	if ( $done && ! $failed ) {
		spokares_add_notice( 'success', $saved, $url, $see );
	} elseif ( $done ) {
		spokares_add_notice( 'warning', $saved . ' ' . $not, $url, $see );
	} elseif ( $failed ) {
		spokares_add_notice( 'error', $not );
	} else {
		spokares_add_notice( 'info', __( 'Nothing changed, so nothing was saved.', 'spokares-core' ) );
	}
	if ( $errors ) {
		spokares_retain( 'spokares-meetings', $held, $errors );
	}
	spokares_redirect_to( 'spokares-meetings' );
}
add_action( 'admin_post_spokares_save_meetings', 'spokares_handle_save_meetings' );

/**
 * What a saved row now says, for the notice: "Sat, Oct 10 cancelled", "Thu,
 * Oct 15 moved to Wed, Oct 14", "Sat, Nov 14 note posted", "Sat, Oct 10 is
 * back on".
 *
 * @param array|null $cand The change now stored (null = none).
 * @param array|null $cur  The change stored before.
 * @param string     $d    Rule date.
 */
function spokares_meeting_change_words( ?array $cand, ?array $cur, string $d ): string {
	$date = spokares_fmt_date( $d, 'short' );
	if ( ! $cand && $cur && 'note' === $cur['kind'] ) {
		/* translators: %s: date, e.g. "Sat, Nov 14". */
		return sprintf( __( '%s note removed', 'spokares-core' ), $date );
	}
	if ( ! $cand ) {
		/* translators: %s: date, e.g. "Sat, Oct 10". */
		return sprintf( __( '%s is back on', 'spokares-core' ), $date );
	}
	switch ( $cand['kind'] ) {
		case 'moved':
			/* translators: 1: date, e.g. "Thu, Oct 15"; 2: new date, e.g. "Wed, Oct 14". */
			return sprintf( __( '%1$s moved to %2$s', 'spokares-core' ), $date, spokares_fmt_date( $cand['new_date'], 'short' ) );
		case 'note':
			/* translators: %s: date, e.g. "Sat, Nov 14". */
			return sprintf( __( '%s note posted', 'spokares-core' ), $date );
		default:
			/* translators: %s: date, e.g. "Sat, Oct 10". */
			return sprintf( __( '%s cancelled', 'spokares-core' ), $date );
	}
}

/* --------------------------------------------------------- meeting schedule */

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
 * A stored meeting as its card shows it: `show` is the one "Show on the site"
 * tick (show_home and active), `from` is "Takes effect on", and `pending` is
 * a change that takes effect later (the line under the legend). A meeting
 * that starts on a later date (no weeks of its own yet) shows its coming
 * pattern in the fields, with that date in "Takes effect on".
 *
 * @param array $stored Stored meeting (normalised).
 */
function spokares_meeting_card_rule( array $stored ): array {
	$m            = $stored;
	$m['show']    = spokares_meeting_shown( $stored );
	$m['from']    = '';
	$m['pending'] = null;
	$next         = is_array( $stored['next'] ?? null ) ? $stored['next'] : null;
	if ( $next ) {
		if ( ! $stored['nth'] ) {
			foreach ( spokares_meeting_pattern_keys() as $key ) {
				$m[ $key ] = $next[ $key ];
			}
			$m['from'] = $next['from'];
		} else {
			$m['pending'] = $next;
		}
	}
	unset( $m['next'] );
	return $m;
}

/**
 * A meeting card as it is drawn (field key => text). A save changes only the
 * fields the editor changed, so an older screen never puts back someone
 * else's newer save (§8.2 #18).
 *
 * @param array $m Card rule (spokares_meeting_card_rule(), maybe with typing laid over).
 */
function spokares_meeting_form_state( array $m ): array {
	$show = array_key_exists( 'show', $m ) ? (bool) $m['show'] : ( ! empty( $m['show_home'] ) && ! empty( $m['active'] ) );
	$m    = spokares_normalize_meeting_pattern( $m );
	$list = static function ( array $l ): string {
		$l = array_values( array_unique( array_map( 'intval', $l ) ) );
		sort( $l );
		return implode( ',', $l );
	};
	return array(
		'name'        => (string) $m['name'],
		'nth'         => $list( $m['nth'] ),
		'weekday'     => (string) (int) $m['weekday'],
		'start'       => (string) $m['start'],
		'end'         => (string) $m['end'],
		'time_text'   => (string) $m['time_text'],
		'home_extra'  => (string) $m['home_extra'],
		'skip_months' => $list( $m['skip_months'] ),
		'show'        => $show ? '1' : '',
		'from'        => (string) ( $m['from'] ?? '' ),
		'needs_check' => ! empty( $m['needs_check'] ) ? '1' : '',
	);
}

/**
 * A pattern's weeks and day in words, for the pending-change line: "4th
 * Thursday", "2nd and 4th Saturdays".
 *
 * @param array $p Pattern.
 */
function spokares_meeting_days_words( array $p ): string {
	$p   = spokares_normalize_meeting_pattern( $p );
	$day = spokares_weekday_names()[ $p['weekday'] ] ?? '';
	return trim( spokares_ordinal_list( $p['nth'] ) . ' ' . ( count( $p['nth'] ) > 1 ? $day . 's' : $day ) );
}

/**
 * The week-of-the-month boxes of a meeting card.
 *
 * @param string $name    Field name (without []).
 * @param array  $current Ticked weeks.
 * @param string $legend  Legend for screen readers.
 */
function spokares_meeting_week_boxes( string $name, array $current, string $legend ): void {
	echo '<fieldset class="spk-weeks"><legend class="screen-reader-text">' . esc_html( $legend ) . '</legend>';
	for ( $n = 1; $n <= 5; $n++ ) {
		printf(
			'<label><input type="checkbox" name="%1$s[]" value="%2$d" %3$s> %4$s</label> ',
			esc_attr( $name ),
			(int) $n,
			checked( in_array( $n, $current, true ), true, false ),
			esc_html( 5 === $n ? __( '5th', 'spokares-core' ) : spokares_ordinal( $n ) )
		);
	}
	echo '</fieldset>';
}

/**
 * One meeting card.
 *
 * @param int   $i      Index.
 * @param array $m      Card rule, with typing that wasn't saved laid over it.
 * @param array $errors Errors.
 * @param bool  $admin  Show the webmaster's own flag.
 * @param array $stored The stored meeting's card rule (empty for "Add a meeting").
 */
function spokares_meeting_rule_fields( int $i, array $m, array $errors, bool $admin, array $stored = array() ): void {
	$n       = 'rules[' . $i . ']';
	$new     = '' === $m['id'];
	$names   = spokares_weekday_names();
	$pre     = 'spk-rule-' . $i . '-';
	$error   = isset( $errors[ "r$i" ] ) || isset( $errors[ "r$i-conflict" ] );
	$legend  = $new ? __( 'Add a meeting', 'spokares-core' ) : (string) ( '' !== ( $stored['name'] ?? '' ) ? $stored['name'] : $m['name'] );
	$pending = $stored['pending'] ?? null;
	$show    = array_key_exists( 'show', $m ) ? (bool) $m['show'] : spokares_meeting_shown( $m );
	?>
	<fieldset class="spk-card spk-rule<?php echo $error ? ' spk-row-error' : ''; ?>">
		<legend><?php echo esc_html( $legend ); ?></legend>
		<?php if ( is_array( $pending ) ) : ?>
			<p class="spk-pending">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: date, e.g. "Fri, Jan 1, 2027"; 2: meeting name; 3: weeks and day, e.g. "4th Thursday"; 4: time, e.g. "7:00 PM" or "evenings". */
						__( 'From %1$s: %2$s, %3$s, %4$s.', 'spokares-core' ),
						spokares_fmt_date( (string) $pending['from'], 'long' ),
						$pending['name'],
						spokares_meeting_days_words( $pending ),
						'' !== $pending['time_text'] ? $pending['time_text'] : spokares_fmt_time_range( $pending['start'], $pending['end'] )
					)
				);
				?>
				<label class="spk-choice"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[drop_next]" value="1"> <?php esc_html_e( 'Drop this change', 'spokares-core' ); ?></label>
			</p>
		<?php endif; ?>
		<?php spokares_err_text( $errors, "r$i" ); ?>
		<?php spokares_err_text( $errors, "r$i-conflict" ); ?>
		<input type="hidden" name="<?php echo esc_attr( $n ); ?>[id]" value="<?php echo esc_attr( $m['id'] ); ?>">
		<?php if ( $stored ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $n ); ?>[orig]" value="<?php echo esc_attr( (string) wp_json_encode( spokares_meeting_form_state( $stored ) ) ); ?>">
		<?php endif; ?>
		<p><label for="<?php echo esc_attr( $pre . 'name' ); ?>"><?php esc_html_e( 'Name', 'spokares-core' ); ?></label><br>
			<input type="text" class="regular-text" id="<?php echo esc_attr( $pre . 'name' ); ?>" name="<?php echo esc_attr( $n ); ?>[name]" value="<?php echo esc_attr( $m['name'] ); ?>" maxlength="80"></p>
		<div class="spk-rule-grid">
			<div><span class="spk-label"><?php esc_html_e( 'Week of the month', 'spokares-core' ); ?></span>
				<?php spokares_meeting_week_boxes( $n . '[nth]', $m['nth'], __( 'Week of the month', 'spokares-core' ) ); ?></div>
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
		<p><label for="<?php echo esc_attr( $pre . 'tt' ); ?>"><?php esc_html_e( 'Time as words', 'spokares-core' ); ?></label><br>
			<input type="text" class="regular-text<?php echo esc_attr( spokares_err_class( $errors, "r$i-time_text" ) ); ?>" id="<?php echo esc_attr( $pre . 'tt' ); ?>" name="<?php echo esc_attr( $n ); ?>[time_text]" value="<?php echo esc_attr( $m['time_text'] ); ?>" maxlength="40" placeholder="<?php esc_attr_e( 'evenings', 'spokares-core' ); ?>">
			<?php spokares_err_text( $errors, "r$i-time_text" ); ?>
			<?php if ( ! empty( $errors[ "r$i-time_text-confirm" ] ) ) : ?>
				<label class="spk-confirm"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[confirm_time_text]" value="1"> <?php echo esc_html( $errors[ "r$i-time_text-confirm" ] ); ?></label>
			<?php endif; ?></p>
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
		<p class="spk-show">
			<label class="spk-choice"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[show_home]" value="1" <?php checked( $show ); ?>> <?php esc_html_e( 'Show on the site', 'spokares-core' ); ?></label>
			<?php if ( $admin ) : ?>
				<label class="spk-choice"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[needs_check]" value="1" <?php checked( ! empty( $m['needs_check'] ) ); ?>> <?php esc_html_e( 'Needs checking (webmaster only)', 'spokares-core' ); ?></label>
			<?php endif; ?>
		</p>
		<p class="spk-from">
			<label for="<?php echo esc_attr( $pre . 'from' ); ?>"><?php esc_html_e( 'Takes effect on', 'spokares-core' ); ?></label><br>
			<input type="date" id="<?php echo esc_attr( $pre . 'from' ); ?>" name="<?php echo esc_attr( $n ); ?>[from]" value="<?php echo esc_attr( (string) ( $m['from'] ?? '' ) ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $errors, "r$i-from" ) ) ); ?>" aria-describedby="<?php echo esc_attr( $pre . 'from-hint' ); ?>">
			<span class="description" id="<?php echo esc_attr( $pre . 'from-hint' ); ?>"><?php esc_html_e( 'Leave empty for now', 'spokares-core' ); ?></span>
		</p>
	</fieldset>
	<?php
}

/**
 * The Meeting Schedule screen.
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
	$cards    = array_map( 'spokares_meeting_card_rule', $data['meetings'] );
	// The "Add a meeting" card: the same fields, shown on the site by default.
	$cards[] = spokares_meeting_card_rule(
		spokares_normalize_meeting(
			array(
				'id'        => '',
				'active'    => true,
				'show_home' => true,
			)
		)
	);
	?>
	<div class="wrap spk-screen spk-rules">
		<h1 class="wp-heading-inline"><?php echo esc_html( spokares_screen_title( 'meeting-rules' ) ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=spokares-meetings' ) ); ?>"><?php echo esc_html( spokares_page_title( 'meetings' ) ); ?></a>
		<hr class="wp-header-end">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-spk-guard>
			<input type="hidden" name="action" value="spokares_save_meeting_rules">
			<?php wp_nonce_field( 'spokares_save_meeting_rules' ); ?>
			<?php
			foreach ( $cards as $i => $card ) {
				$stored = '' !== $card['id'] ? $card : array();
				$m      = $card;
				if ( isset( $held[ $i ] ) && is_array( $held[ $i ] ) ) {
					$m = array_merge( $m, spokares_normalize_meeting_pattern( array_merge( $m, $held[ $i ] ) ) );
					foreach ( array( 'start', 'end', 'show', 'from', 'needs_check' ) as $key ) {
						// As typed (a time that isn't a time comes back to be fixed).
						if ( array_key_exists( $key, $held[ $i ] ) ) {
							$m[ $key ] = $held[ $i ][ $key ];
						}
					}
				}
				spokares_meeting_rule_fields( (int) $i, $m, $errors, $admin, $stored );
			}
			?>
			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'spokares-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

/**
 * The first date a pattern falls on from a date (within about a year).
 *
 * @param array  $p    Pattern.
 * @param string $from Date.
 */
function spokares_meeting_first_date( array $p, string $from ): string {
	$dates = spokares_meeting_pattern_dates( spokares_normalize_meeting_pattern( $p ), $from, spokares_add_days( $from, 400 ) );
	return (string) ( $dates[0] ?? '' );
}

/**
 * Save Meeting Schedule: each card's changed fields; a pattern change with
 * "Takes effect on" later than today is kept as the meeting's pending
 * change. Cancels, moves and notes on dates that are no longer meeting dates
 * are removed and named in the one notice.
 */
function spokares_handle_save_meeting_rules(): void {
	spokares_verify_form( 'spokares_save_meeting_rules', 'spokares_edit_net_details' );
	spokares_lock_option( 'spk_meetings' );
	$posted = isset( $_POST['rules'] ) && is_array( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field below.
	$admin  = current_user_can( 'manage_options' );
	$data   = spokares_opt( 'spk_meetings' );
	$today  = spokares_today();
	$by_id  = array();
	foreach ( $data['meetings'] as $m ) {
		$by_id[ $m['id'] ] = $m;
	}
	$confirmed = is_array( $data['confirmed'] ?? null ) ? $data['confirmed'] : array();
	$out       = array();
	$held      = array();
	$errors    = array();
	$seen      = array();
	$failed    = array();
	$now       = array();
	$later     = array();
	$hidden    = array();
	$labels    = array(
		'name'        => __( 'the name', 'spokares-core' ),
		'nth'         => __( 'the weeks of the month', 'spokares-core' ),
		'weekday'     => __( 'the day', 'spokares-core' ),
		'start'       => __( 'the start time', 'spokares-core' ),
		'end'         => __( 'the end time', 'spokares-core' ),
		'time_text'   => __( 'the time as words', 'spokares-core' ),
		'home_extra'  => __( 'the extra words', 'spokares-core' ),
		'skip_months' => __( 'the skipped months', 'spokares-core' ),
		'show'        => __( '“Show on the site”', 'spokares-core' ),
		'from'        => __( '“Takes effect on”', 'spokares-core' ),
		'needs_check' => __( '“Needs checking”', 'spokares-core' ),
	);
	$patterns  = spokares_meeting_pattern_keys();

	foreach ( $posted as $i => $p ) {
		if ( ! is_array( $p ) ) {
			continue;
		}
		$i    = (int) $i;
		$id   = sanitize_key( spokares_post_str( $p, 'id' ) );
		$old  = '' !== $id && isset( $by_id[ $id ] ) ? $by_id[ $id ] : null;
		$card = $old ? spokares_meeting_card_rule( $old ) : null;
		$nth  = array_values( array_unique( array_filter( array_map( 'absint', array_filter( (array) ( $p['nth'] ?? array() ), 'is_scalar' ) ), static fn( $n ) => $n >= 1 && $n <= 5 ) ) );
		$skip = array_values( array_unique( array_filter( array_map( 'absint', array_filter( (array) ( $p['skip_months'] ?? array() ), 'is_scalar' ) ), static fn( $n ) => $n >= 1 && $n <= 12 ) ) );
		sort( $nth );
		sort( $skip );
		$from = sanitize_text_field( spokares_post_str( $p, 'from' ) );
		// What was typed, as typed (times too, so a bad one comes back in the form).
		$typed                = array(
			'name'        => sanitize_text_field( spokares_post_str( $p, 'name' ) ),
			'nth'         => $nth,
			'weekday'     => max( 0, min( 6, absint( '' !== spokares_post_str( $p, 'weekday' ) ? spokares_post_str( $p, 'weekday' ) : 6 ) ) ),
			'start'       => sanitize_text_field( spokares_post_str( $p, 'start' ) ),
			'end'         => sanitize_text_field( spokares_post_str( $p, 'end' ) ),
			'time_text'   => sanitize_text_field( spokares_post_str( $p, 'time_text' ) ),
			'home_extra'  => sanitize_text_field( spokares_post_str( $p, 'home_extra' ) ),
			'skip_months' => $skip,
			'show'        => ! empty( $p['show_home'] ),
			'from'        => spokares_is_ymd( $from ) ? $from : '',
		);
		$typed['needs_check'] = $admin ? ! empty( $p['needs_check'] ) : (bool) ( $old['needs_check'] ?? false );

		if ( ! $old && '' === $typed['name'] ) {
			continue; // The empty "Add a meeting" card.
		}

		// A field this editor didn't change keeps what is stored now (maybe
		// someone else's newer save); a field both changed isn't saved.
		$use       = $typed;
		$conflicts = array();
		$orig      = $old ? spokares_posted_orig( $p['orig'] ?? '' ) : null;
		if ( $card && null !== $orig ) {
			$shown = spokares_meeting_form_state( array_merge( $card, $typed ) );
			$state = spokares_meeting_form_state( $card );
			foreach ( $state as $key => $current ) {
				if ( ! array_key_exists( $key, $orig ) || ( 'needs_check' === $key && ! $admin ) ) {
					continue;
				}
				if ( 'start' === $key || 'end' === $key ) {
					$shown[ $key ] = $typed[ $key ]; // As typed, even when it isn't a time.
				}
				if ( $shown[ $key ] === $orig[ $key ] ) {
					$use[ $key ] = $card[ $key ];
				} elseif ( $current !== $orig[ $key ] && $current !== $shown[ $key ] ) {
					$use[ $key ]       = $card[ $key ];
					$conflicts[ $key ] = $labels[ $key ];
				}
			}
		}
		$name = '' !== $use['name'] ? $use['name'] : (string) ( $old['name'] ?? '' );

		// The sentence goes in the card; the notice names the meeting.
		$problem = '';
		if ( '' === $use['name'] ) {
			$problem = __( 'It needs a name.', 'spokares-core' );
		} elseif ( spokares_too_long( $use['name'], 80 ) ) {
			$problem = __( 'Keep the name to 80 characters.', 'spokares-core' );
		} elseif ( ! $use['nth'] ) {
			$problem = __( 'Tick at least one week of the month.', 'spokares-core' );
		} elseif ( '' !== $use['start'] && ! spokares_is_hhmm( $use['start'] ) ) {
			$problem = __( 'The start time isn’t a time.', 'spokares-core' );
		} elseif ( '' !== $use['end'] && ! spokares_is_hhmm( $use['end'] ) ) {
			$problem = __( 'The end time isn’t a time.', 'spokares-core' );
		} elseif ( '' !== $use['end'] && '' === $use['start'] ) {
			// Home and the For members page print a meeting's time from its
			// start: an end alone would make the time vanish from both.
			$problem = __( 'An end time needs a start time. Type the start, or clear the end.', 'spokares-core' );
		} elseif ( '' !== $use['start'] && '' !== $use['end'] && $use['end'] <= $use['start'] ) {
			$problem = __( 'The end time must be after the start time.', 'spokares-core' );
		} elseif ( spokares_too_long( $use['time_text'], 40 ) || spokares_too_long( $use['home_extra'], 120 ) ) {
			$problem = __( 'Keep the time as words to 40 characters and the extra words to 120.', 'spokares-core' );
		}
		if ( '' !== $problem ) {
			$errors[ "r$i" ] = $problem;
			$held[ $i ]      = $typed;
			$failed[]        = '' !== $name ? $name : __( 'the new meeting', 'spokares-core' );
			if ( $old ) {
				$out[]              = $old;
				$seen[ $old['id'] ] = true;
			}
			continue;
		}
		if ( $conflicts ) {
			$errors[ "r$i-conflict" ] = sprintf(
				/* translators: %s: the fields, e.g. "the start time and the end time". */
				__( 'Someone else changed %s while you were editing. Check it and save again.', 'spokares-core' ),
				spokares_and_list( array_values( $conflicts ) )
			);
			foreach ( array_keys( $conflicts ) as $key ) {
				$held[ $i ][ $key ] = $typed[ $key ];
			}
			$failed[] = $name;
		}

		if ( ! $old ) {
			$base = sanitize_title( $use['name'] );
			$id   = '' !== $base ? $base : 'meeting';
			$n    = 2;
			while ( isset( $by_id[ $id ] ) || isset( $seen[ $id ] ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
		}
		// The words members read: a phone, an e-mail or a never-publish word is not saved.
		foreach ( array( 'time_text', 'home_extra' ) as $field ) {
			$check = spokares_check_field( $use[ $field ], $id . '-' . $field, $confirmed, ! empty( $p[ 'confirm_' . $field ] ) );
			if ( $check['block'] || $check['confirm'] ) {
				$errors[ "r$i-$field" ] = spokares_problem_sentence( $check );
				if ( ! $check['block'] ) {
					$errors[ "r$i-$field-confirm" ] = spokares_confirm_label( $check['confirm'] );
				}
				$held[ $i ][ $field ] = $use[ $field ];
				$use[ $field ]        = (string) ( $card[ $field ] ?? '' );
				$failed[]             = $name;
			} elseif ( $check['confirmed'] ) {
				$confirmed[ $id . '-' . $field ] = sha1( $use[ $field ] );
			}
		}

		$pattern = array();
		foreach ( $patterns as $key ) {
			$pattern[ $key ] = $use[ $key ];
		}
		// "Show on the site" sets both switches when it is changed; left as it
		// was, a meeting keeps both as stored.
		$flags = array(
			'show_home'   => (bool) $use['show'],
			'active'      => (bool) $use['show'],
			'needs_check' => (bool) $use['needs_check'],
		);
		if ( $card && (bool) $use['show'] === (bool) $card['show'] ) {
			$flags['show_home'] = (bool) $old['show_home'];
			$flags['active']    = (bool) $old['active'];
		}
		$takes      = $use['from'];
		$later_date = spokares_is_ymd( $takes ) && $takes > $today;
		if ( ! $old ) {
			$m = array_merge( array( 'id' => $id ), $pattern, $flags );
			if ( $later_date ) {
				// A meeting that starts later: no dates of its own before then.
				$m['nth']  = array();
				$m['next'] = array_merge( $pattern, array( 'from' => $takes ) );
			}
		} else {
			$m        = array_merge( $old, $flags );
			$was      = array_intersect_key( spokares_meeting_form_state( $card ), array_flip( $patterns ) );
			$is       = array_intersect_key( spokares_meeting_form_state( $pattern ), array_flip( $patterns ) );
			$changed  = $was !== $is;
			$upcoming = '' !== $card['from']; // The card showed a meeting that starts later.
			if ( ! empty( $p['drop_next'] ) && ! $upcoming ) {
				unset( $m['next'] );
			}
			if ( $changed || ( $upcoming && $takes !== $card['from'] ) ) {
				if ( $later_date ) {
					$m['next'] = array_merge( $pattern, array( 'from' => $takes ) );
				} else {
					$m = array_merge( $m, $pattern );
					if ( $upcoming ) {
						unset( $m['next'] );
					}
				}
			}
		}
		$m['id'] = $id;
		$m       = spokares_normalize_meeting( $m );
		$out[]   = $m;

		$seen[ $id ] = true;
		if ( $old && spokares_normalize_meeting( $old ) == $m ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- the same rule, key order aside.
			continue;
		}
		if ( ! spokares_meeting_shown( $m ) ) {
			$hidden[ $id ] = $m['name'];
		} elseif ( isset( $m['next'] ) && ( ! $old || ( $old['next'] ?? null ) != $m['next'] ) ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- the same change, key order aside.
			$later[ $id ] = $m;
		} else {
			$now[ $id ] = $m['name'];
		}
	}
	// A meeting missing from the form (never expected) is kept, not deleted.
	foreach ( $by_id as $id => $m ) {
		if ( ! isset( $seen[ $id ] ) ) {
			$out[] = $m;
		}
	}

	// Cancels, moves and notes on dates that are no longer meeting dates go.
	$rules = array();
	foreach ( $out as $m ) {
		$rules[ $m['id'] ] = $m;
	}
	$orphans = array();
	$changes = array();
	foreach ( $data['changes'] as $c ) {
		$rule = $rules[ $c['meeting'] ] ?? null;
		if ( $rule && $c['date'] >= $today && ! spokares_meeting_on( $rule, $c['date'] ) ) {
			$orphans[] = spokares_meeting_orphan_words( $c );
			continue;
		}
		$changes[] = $c;
	}

	$data['meetings'] = $out;
	$data['changes']  = $changes;
	if ( $confirmed ) {
		$data['confirmed'] = $confirmed;
	}
	$saved = $now || $later || $hidden || $orphans;
	if ( $saved ) {
		update_option( 'spk_meetings', $data );
		spokares_purge_cache();
		spokares_opt_flush();
	}

	$said = array();
	if ( $now ) {
		$next = array();
		foreach ( spokares_next_meetings( 1 ) as $item ) {
			$next[ $item['meeting']['id'] ] = $item['dates'][0]['date'] ?? '';
		}
		foreach ( $now as $id => $name ) {
			if ( '' !== ( $next[ $id ] ?? '' ) ) {
				/* translators: 1: meeting name; 2: date, e.g. "Sat, Nov 14". */
				$said[] = sprintf( __( 'Next %1$s: %2$s.', 'spokares-core' ), $name, spokares_fmt_date( $next[ $id ], 'short' ) );
			}
		}
	}
	foreach ( $later as $m ) {
		$first = spokares_meeting_first_date( $m['next'], $m['next']['from'] );
		/* translators: 1: date, e.g. "Fri, Jan 1, 2027"; 2: meeting name; 3: its first date then, e.g. "Thu, Jan 28". */
		$said[] = sprintf( __( 'From %1$s: %2$s, first on %3$s.', 'spokares-core' ), spokares_fmt_date( $m['next']['from'], 'long' ), $m['next']['name'], spokares_fmt_date( $first, 'short-noyear' ) );
	}
	foreach ( $hidden as $name ) {
		/* translators: %s: meeting name. */
		$said[] = sprintf( __( '%s isn’t on the site: tick Show on the site.', 'spokares-core' ), $name );
	}
	$said = array_merge( $said, $orphans );
	$url  = spokares_site_url( '/', 'visit' );
	$see  = __( 'See it on the Home page', 'spokares-core' );
	if ( $failed ) {
		$names = spokares_and_list( array_values( array_unique( array_filter( $failed ) ) ) );
		if ( $saved ) {
			/* translators: %s: meeting names. */
			spokares_add_notice( 'warning', trim( sprintf( __( 'Saved, except %s (outlined in red).', 'spokares-core' ), $names ) . ' ' . implode( ' ', $said ) ), $url, $see );
		} else {
			/* translators: %s: meeting names. */
			spokares_add_notice( 'error', sprintf( __( 'Not saved: %s (outlined in red).', 'spokares-core' ), $names ) );
		}
		spokares_retain( 'spokares-meeting-rules', $held, $errors );
	} elseif ( $saved ) {
		spokares_add_notice( 'success', trim( __( 'Saved.', 'spokares-core' ) . ' ' . implode( ' ', $said ) ), $url, $see );
	} else {
		spokares_add_notice( 'info', __( 'Nothing changed, so nothing was saved.', 'spokares-core' ) );
	}
	spokares_redirect_to( 'spokares-meeting-rules' );
}
add_action( 'admin_post_spokares_save_meeting_rules', 'spokares_handle_save_meeting_rules' );

/**
 * The notice's sentence for a change removed because its date is no longer
 * a meeting date.
 *
 * @param array $c Change.
 */
function spokares_meeting_orphan_words( array $c ): string {
	$date = spokares_fmt_date( $c['date'], 'day' );
	if ( 'moved' === $c['kind'] && spokares_is_ymd( $c['new_date'] ) ) {
		/* translators: 1: date, e.g. "Oct 15"; 2: new date, e.g. "Oct 14". */
		return sprintf( __( '%1$s is no longer a meeting date, so its move to %2$s was removed.', 'spokares-core' ), $date, spokares_fmt_date( $c['new_date'], 'day' ) );
	}
	if ( 'note' === $c['kind'] ) {
		/* translators: %s: date, e.g. "Oct 15". */
		return sprintf( __( '%s is no longer a meeting date, so its note was removed.', 'spokares-core' ), $date );
	}
	/* translators: %s: date, e.g. "Oct 15". */
	return sprintf( __( '%s is no longer a meeting date, so its cancellation was removed.', 'spokares-core' ), $date );
}
