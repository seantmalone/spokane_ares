<?php
/**
 * Exercises & Events › Add an Event and All Events (§3.4): a plain form with
 * the type of event first; fields that don't apply to the type are hidden; a
 * problem keeps a new event off the site with the field outlined (§4.4).
 * List: Upcoming / Past / Drafts, sortable When, Where it shows, Make a copy.
 * Also the one-notice-per-save plumbing of the event and document forms
 * (§4.8), and the scripts of the event and meeting screens (§4.10).
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The form's labels, by field, for the notices ("Saved, except Where: …").
 * The More links rows (links0-2) share the "More links" label.
 */
function spokares_event_field_names(): array {
	return array(
		'kind'       => __( 'Type of event', 'spokares-core' ),
		'title'      => __( 'Event name', 'spokares-core' ),
		'start'      => __( 'First day', 'spokares-core' ),
		'end'        => __( 'Last day', 'spokares-core' ),
		't_start'    => __( 'Start', 'spokares-core' ),
		't_end'      => __( 'End', 'spokares-core' ),
		'summary'    => __( 'Short description', 'spokares-core' ),
		'where'      => __( 'Where', 'spokares-core' ),
		'tasks'      => __( 'What members do', 'spokares-core' ),
		'main_url'   => __( 'Web address', 'spokares-core' ),
		'main_label' => __( 'Words on the button', 'spokares-core' ),
		'links'      => __( 'More links', 'spokares-core' ),
		'contact'    => __( 'Volunteer through', 'spokares-core' ),
	);
}

/**
 * The label of a problem's field ('links1' → "More links").
 *
 * @param string $field Problem key.
 */
function spokares_event_field_name( string $field ): string {
	$names = spokares_event_field_names();
	$base  = str_starts_with( $field, 'links' ) ? 'links' : $field;
	return $names[ $base ] ?? $field;
}

/**
 * The form's length limits (its maxlength attributes), enforced on save too.
 */
function spokares_event_max_lengths(): array {
	return array(
		'summary'    => 90,
		'where'      => 80,
		'main_label' => 60,
		'links'      => 60,
	);
}

/**
 * The event form's values from the request (nonce already verified by the
 * caller), sanitised, typed text kept.
 */
function spokares_event_submitted(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- callers verify the spokares_event_meta nonce first.
	$in   = wp_unslash( $_POST );
	$str  = static fn( $k ) => isset( $in[ $k ] ) && is_scalar( $in[ $k ] ) ? sanitize_text_field( (string) $in[ $k ] ) : '';
	$kind = $str( 'spk_kind' );
	$mode = $str( 'spk_date_mode' );
	$all  = ! empty( $in['spk_all_day'] );
	$v    = array(
		'title'      => isset( $in['post_title'] ) ? sanitize_text_field( (string) $in['post_title'] ) : '',
		'kind'       => array_key_exists( $kind, spokares_event_kinds() ) ? $kind : '',
		'mode'       => in_array( $mode, spokares_event_date_modes(), true ) ? $mode : 'date',
		'start'      => $str( 'spk_start' ),
		'end'        => $str( 'spk_end' ),
		't_start'    => $all ? '' : $str( 'spk_time_start' ),
		't_end'      => $all ? '' : $str( 'spk_time_end' ),
		'cancelled'  => ! empty( $in['spk_cancelled'] ),
		'summary'    => $str( 'spk_summary' ),
		'where'      => $str( 'spk_where' ),
		'tasks'      => isset( $in['spk_tasks'] ) ? sanitize_textarea_field( (string) $in['spk_tasks'] ) : '',
		'main_url'   => $str( 'spk_main_url' ),
		'main_label' => $str( 'spk_main_label' ),
		'links'      => array(),
		'extra_doc'  => absint( $in['spk_extra_doc'] ?? 0 ),
		'contact'    => $str( 'spk_contact_call' ),
		'keep_past'  => ! empty( $in['spk_keep_past'] ),
		'precision'  => in_array( $str( 'spk_precision' ), array( 'day', 'month', 'year' ), true ) ? $str( 'spk_precision' ) : 'day',
		'check'      => ! empty( $in['spk_needs_check'] ),
		'confirm'    => isset( $in['spk_confirm'] ) && is_array( $in['spk_confirm'] ) ? array_map( 'sanitize_key', array_keys( array_filter( $in['spk_confirm'] ) ) ) : array(),
	);
	foreach ( (array) ( $in['spk_links'] ?? array() ) as $link ) {
		if ( ! is_array( $link ) ) {
			continue;
		}
		$label = sanitize_text_field( (string) ( $link['label'] ?? '' ) );
		$url   = sanitize_text_field( (string) ( $link['url'] ?? '' ) );
		if ( '' !== $label || '' !== $url ) {
			$v['links'][] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}
	$v['links'] = array_slice( $v['links'], 0, 3 );
	// "As requested" belongs to Public service; with another type the date
	// simply isn't posted yet.
	if ( 'as-requested' === $v['mode'] && 'public-service' !== $v['kind'] ) {
		$v['mode'] = 'not-posted';
	}
	// phpcs:enable
	return $v;
}

/**
 * What's wrong with an event's values. Returns field => the sentence under
 * the field for blocking problems, plus 'confirm' => [field => hits] for
 * phone/e-mail shapes that need the "Publish it" tick, plus the call sign
 * kept from Volunteer through.
 *
 * @param array $v         Values (spokares_event_submitted() shape).
 * @param array $confirmed Stored confirmations (field => sha1).
 * @return array{problems:array,confirm:array,call:string,dropped:bool}
 */
function spokares_event_problems( array $v, array $confirmed ): array {
	$problems = array();
	$confirm  = array();
	$kind     = $v['kind'];
	$address  = __( 'Type a web address, like https://www.arrl.org/…', 'spokares-core' );

	if ( '' === $kind ) {
		$problems['kind'] = __( 'Pick the type of event.', 'spokares-core' );
	}
	if ( '' === trim( $v['title'] ) ) {
		$problems['title'] = __( 'Type the event name.', 'spokares-core' );
	}
	if ( 'date' === $v['mode'] ) {
		if ( ! spokares_is_ymd( $v['start'] ) ) {
			$problems['start'] = __( 'Pick the first day.', 'spokares-core' );
		} elseif ( '' !== $v['end'] && ( ! spokares_is_ymd( $v['end'] ) || $v['end'] < $v['start'] ) ) {
			$problems['end'] = __( 'The last day is before the first day.', 'spokares-core' );
		}
		if ( '' !== $v['t_start'] && ! spokares_is_hhmm( $v['t_start'] ) ) {
			$problems['t_start'] = __( 'The start time isn’t a time.', 'spokares-core' );
		} elseif ( '' !== $v['t_end'] && ! spokares_is_hhmm( $v['t_end'] ) ) {
			$problems['t_end'] = __( 'The end time isn’t a time.', 'spokares-core' );
		} elseif ( '' !== $v['t_end'] && '' === $v['t_start'] ) {
			// An end time alone: say what is missing, beside the empty box.
			$problems['t_start'] = __( 'The start time is missing: type it, or clear the end time (or tick All day).', 'spokares-core' );
		} elseif ( '' !== $v['t_end'] && $v['t_end'] <= $v['t_start'] ) {
			$problems['t_end'] = __( 'The end time must be after the start time.', 'spokares-core' );
		}
	}
	// The browser's maxlength, checked again here (a request can skip it).
	foreach ( spokares_event_max_lengths() as $field => $max ) {
		$texts_to_cap = 'links' === $field ? wp_list_pluck( $v['links'], 'label' ) : array( (string) $v[ $field ] );
		foreach ( $texts_to_cap as $text ) {
			if ( spokares_too_long( (string) $text, $max ) ) {
				/* translators: %d: number of characters. */
				$problems[ $field ] = sprintf( __( 'Keep it to %d characters.', 'spokares-core' ), $max );
			}
		}
	}
	if ( 'public-service' !== $kind ) {
		if ( '' !== $v['main_url'] && '' === spokares_clean_url( $v['main_url'] ) ) {
			$problems['main_url'] = $address;
		}
		foreach ( $v['links'] as $i => $link ) {
			if ( '' === $link['url'] || '' === spokares_clean_url( $link['url'] ) ) {
				$problems[ 'links' . $i ] = $address;
			} elseif ( '' === trim( $link['label'] ) ) {
				$problems[ 'links' . $i ] = __( 'Each extra link needs its words.', 'spokares-core' );
			}
		}
	}

	$call    = '';
	$dropped = false;
	if ( 'public-service' === $kind && '' !== $v['contact'] ) {
		$cs = spokares_call_sign( $v['contact'] );
		if ( '' === $cs['call'] ) {
			$problems['contact'] = __( 'Type a call sign, like NZ2S, not a name.', 'spokares-core' );
		}
		$call    = $cs['call'];
		$dropped = $cs['dropped'];
	}

	// The words members read. A field the type hides isn't shown on the site,
	// so it isn't checked either.
	$texts = array(
		'title' => $v['title'],
		'where' => $v['where'],
	);
	if ( 'public-service' !== $kind ) {
		$texts['summary']    = $v['summary'];
		$texts['main_label'] = $v['main_label'];
		$texts['links']      = implode( ' ', wp_list_pluck( $v['links'], 'label' ) );
	}
	if ( 'exercise' === $kind ) {
		$texts['tasks'] = $v['tasks'];
	}
	foreach ( $texts as $field => $text ) {
		if ( isset( $problems[ $field ] ) ) {
			continue; // Already not saved (too long).
		}
		$check = spokares_check_field( $text, $field, $confirmed, in_array( $field, $v['confirm'], true ) );
		if ( $check['block'] ) {
			$problems[ $field ] = spokares_problem_sentence( $check );
		} elseif ( $check['confirm'] ) {
			$confirm[ $field ] = $check['confirm'];
		}
	}
	return array(
		'problems' => $problems,
		'confirm'  => $confirm,
		'call'     => $call,
		'dropped'  => $dropped,
	);
}

/**
 * The sentence under a field for each problem and each phone or e-mail that
 * needs its tick (field => sentence).
 *
 * @param array $result spokares_event_problems() result.
 */
function spokares_event_problem_sentences( array $result ): array {
	$sentences = $result['problems'];
	foreach ( $result['confirm'] as $field => $hits ) {
		$sentences[ $field ] = spokares_problem_sentence( array( 'confirm' => $hits ) );
	}
	return $sentences;
}

/**
 * Is this request a save of our event form for this post, with a good nonce?
 *
 * @param int $post_id Post.
 */
function spokares_event_form_ok( int $post_id ): bool {
	if ( ! isset( $_POST['spokares_event_nonce'] ) ) {
		return false;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spokares_event_nonce'] ) ), 'spokares_event_meta' ) ) {
		return false;
	}
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Before the event is written: a problem keeps a new event off the site.
 *
 * @param array $data    Slashed post data.
 * @param array $postarr Raw post array.
 */
function spokares_event_insert_data( $data, $postarr ) {
	if ( 'spk_event' !== ( $data['post_type'] ?? '' ) || empty( $postarr['ID'] ) ) {
		return $data;
	}
	$post_id = (int) $postarr['ID'];
	if ( ! spokares_event_form_ok( $post_id ) ) {
		return spokares_event_insert_without_form( $data, $post_id );
	}
	$v         = spokares_event_submitted();
	$confirmed = get_post_meta( $post_id, 'spk_confirmed', true );
	$result    = spokares_event_problems( $v, is_array( $confirmed ) ? $confirmed : array() );
	$stored    = get_post( $post_id );
	$live      = $stored && 'publish' === $stored->post_status && 'publish' === ( $data['post_status'] ?? '' );

	$GLOBALS['spokares_event_check']           = $result;
	$GLOBALS['spokares_event_check']['before'] = $stored ? $stored->post_status : '';
	if ( $result['problems'] || $result['confirm'] ) {
		if ( $live ) {
			// A published event stays on the site: the fields with a problem
			// keep their live values (not saved), everything else is saved.
			$GLOBALS['spokares_event_check']['hold'] = true;
			if ( isset( $result['problems']['title'] ) || isset( $result['confirm']['title'] ) ) {
				$data['post_title'] = wp_slash( $stored->post_title );
			}
		} elseif ( in_array( $data['post_status'], array( 'publish', 'future', 'pending' ), true ) ) {
			$data['post_status']                        = 'draft';
			$GLOBALS['spokares_event_check']['demoted'] = true;
		}
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'spokares_event_insert_data', 10, 2 );

/**
 * A save that didn't come through our form (core's Quick Edit or bulk Edit
 * handlers, which are refused anyway, or anything else) must not put an
 * event on the site that the form's checks would refuse: for a non-admin,
 * publishing re-runs the checks on the stored fields and keeps the event a
 * draft on any problem.
 *
 * @param array $data    Slashed post data.
 * @param int   $post_id Event.
 */
function spokares_event_insert_without_form( array $data, int $post_id ): array {
	if ( ! is_user_logged_in() || ( defined( 'WP_CLI' ) && WP_CLI ) || current_user_can( 'manage_options' ) ) {
		return $data;
	}
	if ( ! in_array( $data['post_status'] ?? '', array( 'publish', 'future', 'pending' ), true ) ) {
		return $data;
	}
	$stored = get_post( $post_id );
	if ( ! $stored || 'publish' === $stored->post_status ) {
		return $data;
	}
	$v          = spokares_event_form_values( $stored, false );
	$v['title'] = wp_unslash( (string) ( $data['post_title'] ?? '' ) );
	$confirmed  = get_post_meta( $post_id, 'spk_confirmed', true );
	$check      = spokares_event_problems( $v, is_array( $confirmed ) ? $confirmed : array() );
	if ( $check['problems'] || $check['confirm'] ) {
		$data['post_status'] = 'draft';
	}
	return $data;
}

/**
 * The stored fields behind each form field (for a field that isn't saved).
 */
function spokares_event_field_meta(): array {
	// The first and last day, and the start and end time, are pairs: saving
	// one while keeping the other would publish a half-applied change
	// ("1:00 PM–noon"), so a problem with either keeps both.
	return array(
		'kind'       => array( 'spk_kind' ),
		'start'      => array( 'spk_start', 'spk_end', 'spk_date_mode' ),
		'end'        => array( 'spk_start', 'spk_end', 'spk_date_mode' ),
		't_start'    => array( 'spk_time_start', 'spk_time_end' ),
		't_end'      => array( 'spk_time_start', 'spk_time_end' ),
		'summary'    => array( 'spk_summary' ),
		'where'      => array( 'spk_where' ),
		'tasks'      => array( 'spk_tasks' ),
		'main_url'   => array( 'spk_main_url' ),
		'main_label' => array( 'spk_main_label' ),
		'links'      => array( 'spk_links' ),
		'links0'     => array( 'spk_links' ),
		'links1'     => array( 'spk_links' ),
		'links2'     => array( 'spk_links' ),
		'contact'    => array( 'spk_contact_call' ),
	);
}

/**
 * Where an editor's typing into a published event's unsaved fields is kept
 * (per editor and event), so the form shows it again until the next save of
 * that event.
 *
 * @param int $post_id Event.
 */
function spokares_event_typed_key( int $post_id ): string {
	return 'spokares_event_typed_' . get_current_user_id() . '_' . $post_id;
}

/**
 * The typing kept for a published event's unsaved fields (field => value).
 *
 * @param int $post_id Event.
 */
function spokares_event_held( int $post_id ): array {
	$typed = get_transient( spokares_event_typed_key( $post_id ) );
	return is_array( $typed ) ? $typed : array();
}

/**
 * Save the event's fields (everything typed is kept, even on a problem).
 *
 * @param int $post_id Post.
 */
function spokares_save_event( $post_id ): void {
	$post_id = (int) $post_id;
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! spokares_event_form_ok( $post_id ) ) {
		return;
	}
	$v         = spokares_event_submitted();
	$confirmed = get_post_meta( $post_id, 'spk_confirmed', true );
	$confirmed = is_array( $confirmed ) ? $confirmed : array();
	$result    = $GLOBALS['spokares_event_check'] ?? spokares_event_problems( $v, $confirmed );

	// Record "Publish it" ticks for the fields whose text is now confirmed
	// (every field spokares_event_problems() checks).
	foreach ( array( 'title', 'summary', 'where', 'tasks', 'main_label', 'links' ) as $field ) {
		if ( in_array( $field, $v['confirm'], true ) ) {
			$text = 'links' === $field ? implode( ' ', wp_list_pluck( $v['links'], 'label' ) ) : (string) $v[ $field ];
			if ( '' !== $text ) {
				$confirmed[ $field ] = sha1( $text );
			}
		}
	}

	// A published event with a problem: those fields keep their live values.
	$hold      = ! empty( $result['hold'] );
	$held_keys = array();
	$typed     = array();
	if ( $hold ) {
		$map   = spokares_event_field_meta();
		$pairs = array(
			'start'   => array( 'start', 'end' ),
			'end'     => array( 'start', 'end' ),
			't_start' => array( 't_start', 't_end' ),
			't_end'   => array( 't_start', 't_end' ),
		);
		foreach ( array_keys( $result['problems'] + $result['confirm'] ) as $field ) {
			foreach ( $map[ $field ] ?? array() as $key ) {
				$held_keys[ $key ] = true;
			}
			$base = str_starts_with( (string) $field, 'links' ) ? 'links' : (string) $field;
			// Keep both halves of a pair in the form, as typed.
			foreach ( $pairs[ $base ] ?? array( $base ) as $one ) {
				if ( array_key_exists( $one, $v ) ) {
					$typed[ $one ] = $v[ $one ];
				}
			}
		}
		foreach ( array_keys( $typed ) as $field ) {
			unset( $confirmed[ $field ] );
		}
	}
	if ( $typed ) {
		// Shown again (outlined) until this event is saved again.
		set_transient( spokares_event_typed_key( $post_id ), $typed, WEEK_IN_SECONDS );
	} else {
		delete_transient( spokares_event_typed_key( $post_id ) );
	}
	// A copy's problems show from its first save on.
	delete_post_meta( $post_id, '_spk_copied' );

	$contact = '' !== $result['call'] ? $result['call'] : $v['contact'];
	$links   = array();
	foreach ( $v['links'] as $link ) {
		$clean   = spokares_clean_url( $link['url'] );
		$links[] = array(
			'label' => $link['label'],
			'url'   => '' !== $clean ? $clean : $link['url'],
		);
	}
	$main = spokares_clean_url( $v['main_url'] );
	$meta = array(
		'spk_kind'         => $v['kind'],
		'spk_date_mode'    => $v['mode'],
		'spk_start'        => spokares_is_ymd( $v['start'] ) ? $v['start'] : '',
		'spk_end'          => spokares_is_ymd( $v['end'] ) && $v['end'] > $v['start'] ? $v['end'] : '',
		'spk_time_start'   => spokares_is_hhmm( $v['t_start'] ) ? $v['t_start'] : '',
		'spk_time_end'     => spokares_is_hhmm( $v['t_end'] ) ? $v['t_end'] : '',
		// Called off: only an event on a date (the tick sits under its dates).
		'spk_cancelled'    => $v['cancelled'] && 'date' === $v['mode'] ? '1' : '',
		'spk_summary'      => $v['summary'],
		'spk_where'        => $v['where'],
		'spk_tasks'        => $v['tasks'],
		'spk_main_url'     => '' !== $main ? $main : $v['main_url'],
		'spk_main_label'   => $v['main_label'],
		'spk_links'        => $links,
		'spk_extra_doc'    => $v['extra_doc'],
		'spk_contact_call' => $contact,
		'spk_keep_past'    => $v['keep_past'] ? '1' : '',
	);
	if ( current_user_can( 'manage_options' ) ) {
		$meta['spk_needs_check'] = $v['check'] ? '1' : '';
		$meta['spk_precision']   = $v['precision'];
	}
	foreach ( $meta as $key => $value ) {
		if ( isset( $held_keys[ $key ] ) ) {
			continue;
		}
		if ( '' === $value || array() === $value || 0 === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			// The values are unslashed already, and update_post_meta() unslashes
			// again: slash them so a typed backslash ("C:\ARES") is kept.
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}
	}
	if ( $confirmed ) {
		update_post_meta( $post_id, 'spk_confirmed', $confirmed );
	}
	spokares_update_event_sort( $post_id );

	spokares_event_save_notice( $post_id, $result, (string) ( $result['before'] ?? '' ) );
	unset( $GLOBALS['spokares_event_check'] );
}
add_action( 'save_post_spk_event', 'spokares_save_event' );

/**
 * The one notice after an event save (§3.4): what happened and where it
 * shows. A draft saved without a problem gets WordPress's own "Draft saved"
 * message instead (spokares_one_notice_location()).
 *
 * @param int    $post_id Event.
 * @param array  $result  spokares_event_problems() result, with the save's flags.
 * @param string $before  The status before this save.
 */
function spokares_event_save_notice( int $post_id, array $result, string $before ): void {
	$sentences = spokares_event_problem_sentences( $result );
	/* translators: %s: call sign, e.g. "NV2Z". */
	$cut = $result['dropped'] && '' !== $result['call'] ? ' ' . sprintf( __( '(saved %s only; names aren’t posted)', 'spokares-core' ), $result['call'] ) : '';

	if ( ! empty( $result['hold'] ) && $sentences ) {
		// A published event: only the fields with a problem weren't saved.
		$labels = array_values( array_unique( array_map( 'spokares_event_field_name', array_keys( $sentences ) ) ) );
		if ( 1 === count( $sentences ) ) {
			$sentence = (string) reset( $sentences );
			$text     = sprintf(
				/* translators: 1: a field's label, e.g. "Where"; 2: what is wrong and what to do. */
				__( 'Saved, except %1$s: %2$s Everything else is on the site now.', 'spokares-core' ),
				$labels[0],
				lcfirst( $sentence )
			);
		} else {
			$text = sprintf(
				/* translators: %s: fields' labels, e.g. "Where and Short description". */
				__( 'Saved, except %s (outlined in red). Everything else is on the site now.', 'spokares-core' ),
				spokares_and_list( $labels )
			);
		}
		// Amber, as on every other screen's partial save: the save went through.
		spokares_add_notice( 'warning', $text . $cut );
		return;
	}
	if ( ! empty( $result['demoted'] ) ) {
		// Publish was refused: one red notice, the sentences are under the fields.
		$actions = array(
			'kind'  => __( 'Not published yet: pick the type of event, then click Publish.', 'spokares-core' ),
			'title' => __( 'Not published yet: type the event name, then click Publish.', 'spokares-core' ),
			'start' => __( 'Not published yet: pick the first day, then click Publish.', 'spokares-core' ),
		);
		$only    = 1 === count( $sentences ) ? (string) array_key_first( $sentences ) : '';
		if ( isset( $actions[ $only ] ) ) {
			$text = $actions[ $only ];
		} elseif ( '' !== $only ) {
			$text = __( 'Not published yet. Fix the box outlined in red, then click Publish.', 'spokares-core' );
		} else {
			$text = __( 'Not published yet. Fix the boxes outlined in red, then click Publish.', 'spokares-core' );
		}
		spokares_add_notice( 'error', $text );
		return;
	}
	if ( 'publish' !== get_post_status( $post_id ) ) {
		if ( '' !== $cut ) {
			spokares_add_notice( 'info', __( 'Draft saved. It isn’t on the site until you Publish.', 'spokares-core' ) . $cut );
		}
		return;
	}
	$notice = spokares_event_where_notice( $post_id, 'publish' !== $before && '' !== $before );
	spokares_add_notice( $notice['type'], $notice['text'] . $cut, $notice['url'], $notice['label'] );
}

/* ---------------------------------------------------------- where it shows */

/**
 * The lists of the site an event can be in, by place key, with the
 * arguments the members pages give them (theme patterns members-exercises
 * and members-hub).
 */
function spokares_event_list_views(): array {
	return array(
		'next-up'        => array(
			'next-up',
			array(
				'types' => 'exercise',
				'limit' => 2,
			),
		),
		'later'          => array(
			'later',
			array(
				'types'     => 'exercise,training,on-air',
				'limit'     => 12,
				'cardTypes' => 'exercise',
				'cardLimit' => 2,
			),
		),
		'public-service' => array( 'public-service', array() ),
		'past'           => array( 'past', array( 'limit' => 8 ) ),
		'hub'            => array(
			'upcoming',
			array(
				'types' => 'exercise,training',
				'limit' => 2,
				'days'  => 60,
			),
		),
	);
}

/**
 * Every published event's places right now (event ID => place keys, in the
 * order of spokares_event_list_views()). Each list is read once per change
 * of the events (posts' last-changed time) and per day.
 */
function spokares_event_places_map(): array {
	static $cache = array();
	$key          = wp_cache_get_last_changed( 'posts' ) . '|' . spokares_today();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$map = array();
	foreach ( spokares_event_list_views() as $place => [ $view, $args ] ) {
		foreach ( spokares_events( $view, $args ) as $ev ) {
			$map[ (int) $ev['id'] ][] = $place;
		}
	}
	$cache = array( $key => $map );
	return $map;
}

/**
 * Where a published event is listed on the site right now (place keys:
 * next-up, later, public-service, past, hub).
 *
 * @param int $post_id Event.
 */
function spokares_event_places( int $post_id ): array {
	return spokares_event_places_map()[ $post_id ] ?? array();
}

/**
 * The names of the places, as the list's Where it shows column says them.
 */
function spokares_event_place_names(): array {
	return array(
		'next-up'        => __( 'Next up', 'spokares-core' ),
		'later'          => __( 'Later this season', 'spokares-core' ),
		'public-service' => __( 'Public-service events', 'spokares-core' ),
		'past'           => __( 'Past exercises', 'spokares-core' ),
		'hub'            => __( 'For members page', 'spokares-core' ),
	);
}

/**
 * The notice after a published event is saved: where it shows now, and a
 * "See it" link to its row on the Exercises & events page.
 *
 * @param int  $post_id       Event.
 * @param bool $published_now This save published it.
 * @return array{type:string,text:string,url:string,label:string}
 */
function spokares_event_where_notice( int $post_id, bool $published_now ): array {
	$ev     = spokares_event_data( $post_id );
	$lead   = $published_now ? __( 'Published.', 'spokares-core' ) : __( 'Saved.', 'spokares-core' );
	$places = spokares_event_places( $post_id );
	$last   = '' !== ( $ev['end'] ?? '' ) ? $ev['end'] : (string) ( $ev['start'] ?? '' );
	$notice = array(
		'type'  => 'success',
		'text'  => $lead,
		'url'   => spokares_site_url( '/members/exercises/', (string) ( $ev['slug'] ?? '' ) ),
		'label' => __( 'See it', 'spokares-core' ),
	);
	if ( ! $ev ) {
		return $notice;
	}
	if ( ! $places ) {
		$notice['url']   = '';
		$notice['label'] = '';
		if ( 'date' === $ev['mode'] && '' !== $last && $last < spokares_today() ) {
			if ( 'exercise' === $ev['kind'] && ! $ev['keep_past'] ) {
				$notice['type'] = 'info';
				/* translators: %s: "Saved." or "Published.". */
				$notice['text'] = sprintf( __( '%s It isn’t listed anywhere now: it has ended and isn’t kept under Past exercises.', 'spokares-core' ), $lead );
				return $notice;
			}
			$notice['type'] = 'warning';
			$notice['text'] = sprintf(
				$published_now
					/* translators: %s: the event's last day, e.g. "Fri, May 15, 2026". */
					? __( 'Published, but %s has passed, so no list shows it. Check the year.', 'spokares-core' )
					/* translators: %s: the event's last day, e.g. "Fri, May 15, 2026". */
					: __( 'Saved, but %s has passed, so no list shows it. Check the year.', 'spokares-core' ),
				spokares_fmt_date( $last, 'long' )
			);
			return $notice;
		}
		$notice['type'] = 'info';
		$notice['text'] = $lead . ' ' . __( 'It isn’t listed anywhere now.', 'spokares-core' );
		return $notice;
	}
	if ( $ev['cancelled'] ) {
		/* translators: 1: "Saved." or "Published."; 2: the event's last day, e.g. "Sat, Oct 17". */
		$notice['text'] = sprintf( __( '%1$s Members see “Cancelled” until %2$s.', 'spokares-core' ), $lead, spokares_fmt_date( $last, 'short' ) );
		return $notice;
	}
	if ( 'postponed' === $ev['mode'] ) {
		/* translators: %s: "Saved." or "Published.". */
		$notice['text'] = sprintf( __( '%s It shows as Postponed on the Exercises & events page.', 'spokares-core' ), $lead );
		return $notice;
	}
	$names    = spokares_event_place_names();
	$sections = array();
	foreach ( $places as $place ) {
		if ( 'hub' !== $place ) {
			$sections[] = $names[ $place ];
		}
	}
	$hub = in_array( 'hub', $places, true );
	if ( $sections && $hub ) {
		/* translators: 1: "Saved." or "Published."; 2: sections, e.g. "Next up". */
		$where = __( '%1$s It shows under %2$s on the Exercises & events page and in This week on the For members page.', 'spokares-core' );
	} elseif ( $sections ) {
		/* translators: 1: "Saved." or "Published."; 2: sections, e.g. "Later this season". */
		$where = __( '%1$s It shows under %2$s on the Exercises & events page.', 'spokares-core' );
	} else {
		/* translators: 1: "Saved." or "Published.". */
		$where = __( '%1$s It shows in This week on the For members page.', 'spokares-core' );
	}
	$notice['text'] = sprintf( $where, $lead, spokares_and_list( $sections ) );
	return $notice;
}

/**
 * Where a published event shows right now, as the saved notice says it
 * ("Saved. It shows under Later this season on the Exercises & events page.").
 *
 * @param int $post_id Event.
 */
function spokares_event_places_sentence( int $post_id ): string {
	return spokares_event_where_notice( $post_id, false )['text'];
}

/* -------------------------------------------------------- one notice a save */

/**
 * Notices queued during this save only: a save of an event or document
 * starts with none (the flag is set by spokares_add_notice()).
 *
 * @param array $data Slashed post data.
 */
function spokares_notice_flag_reset( $data ) {
	if ( in_array( $data['post_type'] ?? '', array( 'spk_event', 'spk_document' ), true ) ) {
		unset( $GLOBALS['spokares_notice_queued'] );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'spokares_notice_flag_reset', 1 );

/**
 * Each save of an event or document shows exactly one notice (§4.8): when
 * the save queued its own, WordPress's message is dropped; otherwise a post
 * that is a draft after the save gets message 10 ("Draft saved. It isn't on
 * the site until you Publish."), whichever button was used.
 *
 * @param string $location Redirect location.
 * @param int    $post_id  Post.
 */
function spokares_one_notice_location( $location, $post_id ) {
	$post_id = (int) $post_id;
	if ( ! in_array( get_post_type( $post_id ), array( 'spk_event', 'spk_document' ), true ) ) {
		return $location;
	}
	if ( ! empty( $GLOBALS['spokares_notice_queued'] ) ) {
		return remove_query_arg( 'message', (string) $location );
	}
	if ( 'draft' === get_post_status( $post_id ) && str_contains( (string) $location, 'message=' ) ) {
		return add_query_arg( 'message', 10, (string) $location );
	}
	return $location;
}
add_filter( 'redirect_post_location', 'spokares_one_notice_location', 10, 2 );

/* -------------------------------------------------------------------- form */

/**
 * The stored values of an event in the form's shape, with the typing kept
 * from the last save laid over them.
 *
 * @param WP_Post $post      Event.
 * @param bool    $with_held Lay the kept typing over the stored values.
 */
function spokares_event_form_values( WP_Post $post, bool $with_held = true ): array {
	$get       = static fn( $k ) => get_post_meta( $post->ID, $k, true );
	$links     = $get( 'spk_links' );
	$mode      = (string) $get( 'spk_date_mode' );
	$precision = (string) $get( 'spk_precision' );
	$values    = array(
		// The name as typed (stored titles are HTML-filtered: "&" is "&amp;").
		'title'      => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
		'kind'       => (string) $get( 'spk_kind' ),
		'mode'       => in_array( $mode, spokares_event_date_modes(), true ) ? $mode : 'date',
		'start'      => (string) $get( 'spk_start' ),
		'end'        => (string) $get( 'spk_end' ),
		't_start'    => (string) $get( 'spk_time_start' ),
		't_end'      => (string) $get( 'spk_time_end' ),
		'cancelled'  => '1' === (string) $get( 'spk_cancelled' ),
		'summary'    => (string) $get( 'spk_summary' ),
		'where'      => (string) $get( 'spk_where' ),
		'tasks'      => (string) $get( 'spk_tasks' ),
		'main_url'   => (string) $get( 'spk_main_url' ),
		'main_label' => (string) $get( 'spk_main_label' ),
		'links'      => is_array( $links ) ? $links : array(),
		'extra_doc'  => absint( $get( 'spk_extra_doc' ) ),
		'contact'    => (string) $get( 'spk_contact_call' ),
		// A new exercise is listed under Past exercises by default.
		'keep_past'  => 'auto-draft' === $post->post_status ? true : '1' === (string) $get( 'spk_keep_past' ),
		'precision'  => '' !== $precision ? $precision : 'day',
		'check'      => '1' === (string) $get( 'spk_needs_check' ),
		'confirm'    => array(),
	);
	if ( $with_held ) {
		foreach ( spokares_event_held( (int) $post->ID ) as $field => $typed ) {
			if ( array_key_exists( $field, $values ) && 'confirm' !== $field ) {
				$values[ $field ] = $typed;
			}
		}
	}
	return $values;
}

/**
 * Is this event a copy (Make a copy) that hasn't been saved from its form
 * yet? Its problems show from its first save on; First day is outlined.
 *
 * @param WP_Post $post Event.
 */
function spokares_event_is_fresh_copy( WP_Post $post ): bool {
	return '1' === (string) get_post_meta( $post->ID, '_spk_copied', true );
}

/**
 * The problems the form shows for an event (none on a new event or a fresh
 * copy).
 *
 * @param WP_Post $post Event.
 * @param array   $v    Form values.
 */
function spokares_event_form_check( WP_Post $post, array $v ): array {
	if ( 'auto-draft' === $post->post_status || spokares_event_is_fresh_copy( $post ) ) {
		return array(
			'problems' => array(),
			'confirm'  => array(),
			'call'     => '',
			'dropped'  => false,
		);
	}
	$confirmed = get_post_meta( $post->ID, 'spk_confirmed', true );
	return spokares_event_problems( $v, is_array( $confirmed ) ? $confirmed : array() );
}

/**
 * Type of event first (above the event name), then the name's label.
 *
 * @param WP_Post $post Post.
 */
function spokares_event_form_top( $post ): void {
	if ( ! $post instanceof WP_Post || 'spk_event' !== $post->post_type ) {
		return;
	}
	$v        = spokares_event_form_values( $post );
	$problems = spokares_event_form_check( $post, $v )['problems'];
	wp_nonce_field( 'spokares_event_meta', 'spokares_event_nonce' );
	?>
	<fieldset class="spk-card spk-kind-box<?php echo isset( $problems['kind'] ) ? ' spk-field-error' : ''; ?>" id="spk-kind">
		<legend><?php esc_html_e( 'Type of event (required)', 'spokares-core' ); ?></legend>
		<div class="spk-kinds">
			<?php foreach ( spokares_event_kinds() as $key => $label ) : ?>
				<label class="spk-choice"><input type="radio" name="spk_kind" value="<?php echo esc_attr( $key ); ?>" required <?php checked( $key, $v['kind'] ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
		</div>
		<?php spokares_err_text( $problems, 'kind' ); ?>
	</fieldset>
	<label for="title" class="spk-title-label<?php echo isset( $problems['title'] ) ? ' spk-has-error' : ''; ?>"><?php esc_html_e( 'Event name (required)', 'spokares-core' ); ?></label>
	<?php
}
add_action( 'edit_form_top', 'spokares_event_form_top' );

/**
 * The rest of the form (after the event name).
 *
 * @param WP_Post $post Post.
 */
function spokares_event_form_fields( $post ): void {
	if ( ! $post instanceof WP_Post || 'spk_event' !== $post->post_type ) {
		return;
	}
	$v     = spokares_event_form_values( $post );
	$check = spokares_event_form_check( $post, $v );
	$p     = $check['problems'];
	$c     = $check['confirm'];
	// A fresh copy: First day is outlined (no sentence) until its first save.
	$copy_start = spokares_event_is_fresh_copy( $post ) && ! spokares_is_ymd( $v['start'] );
	// All day only when neither time is filled in: an end time alone comes
	// back with the time boxes showing, so its sentence points at them.
	$all_day = '' === $v['t_start'] && '' === $v['t_end'];
	$admin   = current_user_can( 'manage_options' );
	$on_date = 'date' === $v['mode'];
	$docs    = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'publish',
			'numberposts' => 200,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);
	// A stored document that isn't published right now (a draft, say) is
	// still offered, chosen, so saving the form doesn't drop it; the card
	// links it again once the document is published.
	$held_doc = $v['extra_doc'] && ! in_array( $v['extra_doc'], array_map( 'intval', wp_list_pluck( $docs, 'ID' ) ), true ) ? get_post( $v['extra_doc'] ) : null;
	if ( $held_doc && ( 'spk_document' !== $held_doc->post_type || 'trash' === $held_doc->post_status ) ) {
		$held_doc = null;
	}
	$links = array_pad(
		array_values( $v['links'] ),
		3,
		array(
			'label' => '',
			'url'   => '',
		)
	);
	// Month- or year-only dates (older exercises) print as "Oct 2022".
	$shown_as = in_array( $v['precision'], array( 'month', 'year' ), true ) && spokares_is_ymd( $v['start'] )
		? spokares_fmt_date( $v['start'], 'year' === $v['precision'] ? 'year' : 'month' )
		: '';
	$weekday  = static fn( string $ymd ): string => spokares_is_ymd( $ymd ) ? (string) spokares_date_obj( $ymd )->format( 'D' ) : '';
	$address  = __( 'Web address', 'spokares-core' );
	?>
	<div class="spk-event-form" data-spk-kind-form>
		<?php if ( 'auto-draft' === $post->post_status ) : ?>
			<p class="spk-name-match" id="spk-name-match" role="status"></p>
		<?php endif; ?>
		<?php spokares_err_text( $p, 'title' ); ?>
		<?php spokares_event_confirm( $c, 'title' ); ?>

		<fieldset class="spk-card spk-when">
			<legend><?php esc_html_e( 'When', 'spokares-core' ); ?></legend>
			<p class="spk-modes">
				<label class="spk-choice"><input type="radio" name="spk_date_mode" value="date" <?php checked( 'date', $v['mode'] ); ?>> <?php esc_html_e( 'On a date', 'spokares-core' ); ?></label>
				<label class="spk-choice"><input type="radio" name="spk_date_mode" value="postponed" <?php checked( 'postponed', $v['mode'] ); ?>> <?php esc_html_e( 'Postponed', 'spokares-core' ); ?></label>
				<label class="spk-choice"><input type="radio" name="spk_date_mode" value="not-posted" <?php checked( 'not-posted', $v['mode'] ); ?>> <?php esc_html_e( 'Date not posted yet', 'spokares-core' ); ?></label>
				<label class="spk-choice" data-kinds="public-service"><input type="radio" name="spk_date_mode" value="as-requested" <?php checked( 'as-requested', $v['mode'] ); ?>> <?php esc_html_e( 'As requested', 'spokares-core' ); ?></label>
			</p>
			<div class="spk-dates" data-mode="date">
				<p class="spk-days">
					<span class="spk-day">
						<label for="spk-start"><?php esc_html_e( 'First day (required)', 'spokares-core' ); ?></label>
						<input type="date" id="spk-start" name="spk_start" value="<?php echo esc_attr( $v['start'] ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $p, 'start' ) . ( $copy_start ? ' spk-field-error' : '' ) ) ); ?>"<?php echo $on_date ? ' required' : ''; ?>>
						<span class="spk-weekday" data-for="spk-start"><?php echo esc_html( $weekday( $v['start'] ) ); ?></span>
					</span>
					<span class="spk-day">
						<label for="spk-end"><?php esc_html_e( 'Last day', 'spokares-core' ); ?></label>
						<input type="date" id="spk-end" name="spk_end" value="<?php echo esc_attr( $v['end'] ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $p, 'end' ) ) ); ?>">
						<span class="spk-weekday" data-for="spk-end"><?php echo esc_html( $weekday( $v['end'] ) ); ?></span>
					</span>
					<?php if ( '' !== $shown_as ) : ?>
						<?php /* translators: %s: how the Past exercises list prints the date, e.g. "Oct 2022". */ ?>
						<span class="description"><?php echo esc_html( sprintf( __( 'The site shows “%s”.', 'spokares-core' ), $shown_as ) ); ?></span>
					<?php endif; ?>
				</p>
				<?php spokares_err_text( $p, 'start' ); ?>
				<?php spokares_err_text( $p, 'end' ); ?>
				<p>
					<label class="spk-choice"><input type="checkbox" name="spk_all_day" value="1" id="spk-all-day" <?php checked( $all_day ); ?>> <?php esc_html_e( 'All day', 'spokares-core' ); ?></label>
					<span class="spk-times" <?php echo $all_day ? 'hidden' : ''; ?>>
						<label for="spk-t-start"><?php esc_html_e( 'Start', 'spokares-core' ); ?></label>
						<input type="time" id="spk-t-start" name="spk_time_start" value="<?php echo esc_attr( $v['t_start'] ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $p, 't_start' ) ) ); ?>">
						<label for="spk-t-end"><?php esc_html_e( 'End', 'spokares-core' ); ?></label>
						<input type="time" id="spk-t-end" name="spk_time_end" value="<?php echo esc_attr( $v['t_end'] ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $p, 't_end' ) ) ); ?>">
					</span>
				</p>
				<?php spokares_err_text( $p, 't_start' ); ?>
				<?php spokares_err_text( $p, 't_end' ); ?>
				<?php if ( 'publish' === $post->post_status ) : ?>
					<p class="spk-cancel-event">
						<label class="spk-choice"><input type="checkbox" name="spk_cancelled" value="1" id="spk-cancelled" aria-describedby="spk-cancelled-hint" <?php checked( $v['cancelled'] ); ?>> <?php esc_html_e( 'Cancelled', 'spokares-core' ); ?></label>
						<span class="description" id="spk-cancelled-hint"><?php esc_html_e( 'Members see “Cancelled” until the date passes.', 'spokares-core' ); ?></span>
					</p>
				<?php endif; ?>
			</div>
		</fieldset>

		<div class="spk-field">
			<label for="spk-where"><?php esc_html_e( 'Where', 'spokares-core' ); ?></label>
			<input type="text" id="spk-where" name="spk_where" maxlength="80" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'where' ) ); ?>" value="<?php echo esc_attr( $v['where'] ); ?>" aria-describedby="spk-where-hint">
			<p class="description" id="spk-where-hint"><?php esc_html_e( 'A place, or “From your own station”.', 'spokares-core' ); ?></p>
			<?php spokares_err_text( $p, 'where' ); ?>
			<?php spokares_event_confirm( $c, 'where' ); ?>
		</div>

		<div class="spk-field" data-kinds="exercise,training,on-air">
			<label for="spk-summary"><?php esc_html_e( 'Short description', 'spokares-core' ); ?></label>
			<input type="text" id="spk-summary" name="spk_summary" maxlength="90" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'summary' ) ); ?>" value="<?php echo esc_attr( $v['summary'] ); ?>">
			<?php spokares_err_text( $p, 'summary' ); ?>
			<?php spokares_event_confirm( $c, 'summary' ); ?>
		</div>

		<div class="spk-field" data-kinds="exercise">
			<label for="spk-tasks"><?php esc_html_e( 'What members do (one task per line)', 'spokares-core' ); ?></label>
			<textarea id="spk-tasks" name="spk_tasks" rows="5" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'tasks' ) ); ?>"><?php echo esc_textarea( $v['tasks'] ); ?></textarea>
			<?php spokares_err_text( $p, 'tasks' ); ?>
			<?php spokares_event_confirm( $c, 'tasks' ); ?>
		</div>

		<fieldset class="spk-card spk-button" data-kinds="exercise,training,on-air">
			<legend><?php esc_html_e( 'Button', 'spokares-core' ); ?></legend>
			<p>
				<label for="spk-main-url"><?php echo esc_html( $address ); ?></label><br>
				<input type="text" inputmode="url" autocomplete="off" spellcheck="false" id="spk-main-url" name="spk_main_url" class="large-text spk-url<?php echo esc_attr( spokares_err_class( $p, 'main_url' ) ); ?>" value="<?php echo esc_attr( $v['main_url'] ); ?>" placeholder="https://…" aria-describedby="spk-main-url-hint">
				<span class="description" id="spk-main-url-hint"><?php esc_html_e( 'Copy it from your browser’s address bar.', 'spokares-core' ); ?></span>
				<?php spokares_err_text( $p, 'main_url' ); ?>
			</p>
			<p>
				<label for="spk-main-label"><?php esc_html_e( 'Words on the button', 'spokares-core' ); ?></label><br>
				<input type="text" id="spk-main-label" name="spk_main_label" class="regular-text<?php echo esc_attr( spokares_err_class( $p + $c, 'main_label' ) ); ?>" value="<?php echo esc_attr( $v['main_label'] ); ?>" maxlength="60" placeholder="<?php echo esc_attr( spokares_default_main_label( spokares_clean_url( $v['main_url'] ) ) ); ?>">
				<?php spokares_err_text( $p, 'main_label' ); ?>
				<?php spokares_event_confirm( $c, 'main_label' ); ?>
			</p>
		</fieldset>

		<fieldset class="spk-card spk-links" data-kinds="exercise,training,on-air">
			<legend><?php esc_html_e( 'More links (up to 3)', 'spokares-core' ); ?></legend>
			<?php foreach ( $links as $i => $link ) : ?>
				<p class="spk-link-row<?php echo isset( $p[ 'links' . $i ] ) ? ' spk-field-error' : ''; ?>" <?php echo ( $i > 0 && '' === $link['url'] && '' === $link['label'] ) ? 'data-spk-spare hidden' : ''; ?>>
					<label><?php esc_html_e( 'Words', 'spokares-core' ); ?> <input type="text" name="spk_links[<?php echo esc_attr( (string) $i ); ?>][label]" value="<?php echo esc_attr( (string) $link['label'] ); ?>" maxlength="60"></label>
					<label><?php echo esc_html( $address ); ?> <input type="text" inputmode="url" autocomplete="off" spellcheck="false" name="spk_links[<?php echo esc_attr( (string) $i ); ?>][url]" class="regular-text spk-url" value="<?php echo esc_attr( (string) $link['url'] ); ?>" placeholder="https://…"></label>
					<?php spokares_err_text( $p, 'links' . $i ); ?>
				</p>
			<?php endforeach; ?>
			<p><button type="button" class="button spk-add-link"><?php esc_html_e( '+ Add a link', 'spokares-core' ); ?></button></p>
			<?php spokares_err_text( $p, 'links' ); ?>
			<?php spokares_event_confirm( $c, 'links' ); ?>
		</fieldset>

		<div class="spk-field" data-kinds="exercise">
			<label for="spk-extra-doc"><?php esc_html_e( 'Document members need', 'spokares-core' ); ?></label>
			<select id="spk-extra-doc" name="spk_extra_doc">
				<option value="0"><?php esc_html_e( '— none —', 'spokares-core' ); ?></option>
				<?php if ( $held_doc ) : ?>
					<option value="<?php echo esc_attr( (string) $held_doc->ID ); ?>" selected><?php echo esc_html( html_entity_decode( get_the_title( $held_doc ), ENT_QUOTES, 'UTF-8' ) . ' ' . __( '(not shown: the document isn’t published)', 'spokares-core' ) ); ?></option>
				<?php endif; ?>
				<?php foreach ( $docs as $doc ) : ?>
					<option value="<?php echo esc_attr( (string) $doc->ID ); ?>" <?php selected( $doc->ID, $v['extra_doc'] ); ?>><?php echo esc_html( html_entity_decode( get_the_title( $doc ), ENT_QUOTES, 'UTF-8' ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="spk-field" data-kinds="public-service">
			<label for="spk-contact"><?php esc_html_e( 'Volunteer through (call sign)', 'spokares-core' ); ?></label>
			<input type="text" id="spk-contact" name="spk_contact_call" class="regular-text<?php echo esc_attr( spokares_err_class( $p, 'contact' ) ); ?>" value="<?php echo esc_attr( $v['contact'] ); ?>" maxlength="40" list="spk-calls" spellcheck="false">
			<?php spokares_err_text( $p, 'contact' ); ?>
			<datalist id="spk-calls">
				<?php foreach ( spokares_known_calls() as $call ) : ?>
					<option value="<?php echo esc_attr( $call ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
		</div>

		<div class="spk-field" data-kinds="exercise">
			<label class="spk-choice"><input type="checkbox" name="spk_keep_past" value="1" <?php checked( $v['keep_past'] ); ?>> <?php esc_html_e( 'List under Past exercises after it ends', 'spokares-core' ); ?></label>
		</div>

		<?php if ( $admin ) : ?>
			<div class="spk-field spk-admin-only">
				<label for="spk-precision"><?php esc_html_e( 'Past exercises show (webmaster)', 'spokares-core' ); ?></label>
				<select id="spk-precision" name="spk_precision">
					<option value="day" <?php selected( 'day', $v['precision'] ); ?>><?php esc_html_e( 'Month and year', 'spokares-core' ); ?></option>
					<option value="month" <?php selected( 'month', $v['precision'] ); ?>><?php esc_html_e( 'Month and year (day unknown)', 'spokares-core' ); ?></option>
					<option value="year" <?php selected( 'year', $v['precision'] ); ?>><?php esc_html_e( 'Year only', 'spokares-core' ); ?></option>
				</select>
				<label for="spk-menu-order"><?php esc_html_e( 'Order among undated events', 'spokares-core' ); ?></label>
				<input type="number" id="spk-menu-order" name="menu_order" class="small-text" value="<?php echo esc_attr( (string) $post->menu_order ); ?>" min="0" step="10">
			</div>
		<?php endif; ?>

		<p class="spk-foot"><?php esc_html_e( 'Never publish county, hospital, SHARES or 800 MHz channels, or names, phones or e-mails.', 'spokares-core' ); ?></p>
	</div>
	<?php
}
add_action( 'edit_form_after_title', 'spokares_event_form_fields' );

/**
 * Under an event field with a phone number or e-mail address: what to do,
 * and the "Publish it" tick.
 *
 * @param array  $confirm Field => hits.
 * @param string $field   Field.
 */
function spokares_event_confirm( array $confirm, string $field ): void {
	if ( empty( $confirm[ $field ] ) ) {
		return;
	}
	spokares_err_text( array( $field => spokares_problem_sentence( array( 'confirm' => $confirm[ $field ] ) ) ), $field );
	printf(
		'<label class="spk-confirm"><input type="checkbox" name="spk_confirm[%1$s]" value="1"> %2$s</label>',
		esc_attr( $field ),
		esc_html( spokares_confirm_label( $confirm[ $field ] ) )
	);
}

/**
 * Side boxes: Save (with Make a copy once the event is saved) and, for the
 * webmaster, Webmaster check.
 *
 * @param WP_Post $post Post.
 */
function spokares_event_boxes( $post ): void {
	add_meta_box( 'spokares_savebox', __( 'Save', 'spokares-core' ), 'spokares_event_save_box', 'spk_event', 'side', 'high' );
	if ( current_user_can( 'manage_options' ) ) {
		add_meta_box( 'spokares_checking', __( 'Webmaster check', 'spokares-core' ), 'spokares_render_checking_box', 'spk_event', 'side', 'default' );
	}
	unset( $post );
}
add_action( 'add_meta_boxes_spk_event', 'spokares_event_boxes' );

/**
 * The event's Save box.
 *
 * @param WP_Post $post Event.
 */
function spokares_event_save_box( WP_Post $post ): void {
	$links = array();
	if ( 'auto-draft' !== $post->post_status && current_user_can( 'edit_spk_events' ) ) {
		$links[] = array(
			'label' => __( 'Make a copy', 'spokares-core' ),
			'url'   => spokares_event_copy_url( $post->ID ),
		);
	}
	spokares_render_save_box( $post, $links );
}

/**
 * The webmaster's own "Needs checking" flag (administrators only).
 *
 * @param WP_Post $post Post.
 */
function spokares_render_checking_box( WP_Post $post ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$on = '1' === (string) get_post_meta( $post->ID, 'spk_needs_check', true );
	echo '<label><input type="checkbox" name="spk_needs_check" value="1" ' . checked( $on, true, false ) . '> ' . esc_html__( 'Needs checking (webmaster only)', 'spokares-core' ) . '</label>';
}

/**
 * WordPress's own messages after a save, for the rare save that queued no
 * notice of its own (spokares_one_notice_location()), with a link to the
 * place on the site.
 *
 * @param array $messages Messages by post type.
 */
function spokares_event_messages( $messages ) {
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, array( 'spk_event', 'spk_document' ), true ) ) {
		return $messages;
	}
	$is_event                     = 'spk_event' === $post->post_type;
	$url                          = $is_event ? spokares_site_url( '/members/exercises/', $post->post_name ) : spokares_site_url( '/members/documents/', $post->post_name );
	$link                         = ' <a href="' . esc_url( $url ) . '">' . esc_html( $is_event ? __( 'See it on the Exercises & events page', 'spokares-core' ) : __( 'See it on the Documents & forms page', 'spokares-core' ) ) . '</a>';
	$m                            = array(
		1  => esc_html__( 'Saved.', 'spokares-core' ) . $link,
		4  => esc_html__( 'Saved.', 'spokares-core' ) . $link,
		6  => esc_html__( 'Published.', 'spokares-core' ) . $link,
		7  => esc_html__( 'Saved.', 'spokares-core' ),
		8  => esc_html__( 'Submitted.', 'spokares-core' ),
		10 => esc_html__( 'Draft saved. It isn’t on the site until you Publish.', 'spokares-core' ),
	);
	$messages[ $post->post_type ] = $m;
	return $messages;
}
add_filter( 'post_updated_messages', 'spokares_event_messages' );

/**
 * List-screen messages that name the thing ("“Great ShakeOut” is off the
 * site."), not core's "post".
 *
 * @param array $messages    Messages by post type.
 * @param array $bulk_counts Counts by action.
 */
function spokares_bulk_messages( $messages, $bulk_counts ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the message after core's own (nonce-checked) redirect; read-only.
	$list = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
	// Core's redirect after Restore or Undo doesn't name the events (it names
	// only what went to the Trash): spokares_remember_restored() did.
	$restored = ! empty( $bulk_counts['untrashed'] ) && 'spk_event' === $list ? spokares_take_restored( 'spk_event' ) : array();
	foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
		$is_event = 'spk_event' === $type;
		$m        = array();
		foreach ( array( 'updated', 'locked', 'deleted', 'trashed', 'untrashed' ) as $action ) {
			$ids          = $is_event && 'untrashed' === $action && $restored ? $restored : null;
			$m[ $action ] = spokares_bulk_message( $is_event, $action, (int) ( $bulk_counts[ $action ] ?? 0 ), $ids );
		}
		$messages[ $type ] = $m;
	}
	return $messages;
}
add_filter( 'bulk_post_updated_messages', 'spokares_bulk_messages', 10, 2 );

/**
 * One list-screen message; core fills in the number (%s) and prints it as
 * HTML (so a name is escaped here, and its "%" doubled).
 *
 * @param bool       $is_event Event (else document).
 * @param string     $action   updated, locked, deleted, trashed or untrashed.
 * @param int        $n        How many.
 * @param int[]|null $ids      The items it is about (the restored ones);
 *                             null = the redirect's ?ids=.
 */
function spokares_bulk_message( bool $is_event, string $action, int $n, ?array $ids = null ): string {
	$name = 1 === $n ? spokares_bulk_one_name( $ids ) : '';
	$one  = static fn( string $text ): string => str_replace( '%', '%%', esc_html( sprintf( $text, $name ) ) );
	switch ( $action ) {
		case 'updated':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event saved.', '%s events saved.', $n, 'spokares-core' ) : _n( '%s document saved.', '%s documents saved.', $n, 'spokares-core' );
		case 'locked':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event not saved: someone else is editing it.', '%s events not saved: someone else is editing them.', $n, 'spokares-core' ) : _n( '%s document not saved: someone else is editing it.', '%s documents not saved: someone else is editing them.', $n, 'spokares-core' );
		case 'deleted':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event deleted for good.', '%s events deleted for good.', $n, 'spokares-core' ) : _n( '%s document deleted for good.', '%s documents deleted for good.', $n, 'spokares-core' );
		case 'trashed':
			if ( '' !== $name ) {
				$trashed = (int) current( spokares_bulk_ids( $ids ) );
				$was     = (string) get_post_meta( $trashed, '_wp_trash_meta_status', true );
				if ( 'publish' !== ( '' !== $was ? $was : (string) get_post_status( $trashed ) ) ) {
					// A draft (or a fresh copy) was never on the site.
					/* translators: %s: the event's or document's name. */
					return $one( __( '“%s” is in the Trash.', 'spokares-core' ) );
				}
				/* translators: %s: the event's or document's name. */
				return $one( __( '“%s” is off the site.', 'spokares-core' ) );
			}
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event is off the site.', '%s events are off the site.', $n, 'spokares-core' ) : _n( '%s document is off the site.', '%s documents are off the site.', $n, 'spokares-core' );
		default:
			$drafts = spokares_untrashed_as_drafts( $ids );
			if ( '' !== $name ) {
				if ( ! $drafts ) {
					/* translators: %s: the event's or document's name. */
					return $one( __( '“%s” is back on the site.', 'spokares-core' ) );
				}
				return $is_event
					/* translators: %s: the event's name. */
					? $one( __( '“%s” is back as a draft.', 'spokares-core' ) )
					/* translators: %s: the document's name. */
					: $one( __( '“%s” is back as a draft: upload the file again, then Publish.', 'spokares-core' ) );
			}
			if ( $drafts ) {
				/* translators: %s: how many. */
				return $is_event ? _n( '%s event is back as a draft.', '%s events are back as drafts.', $n, 'spokares-core' ) : _n( '%s document is back as a draft.', '%s documents are back as drafts.', $n, 'spokares-core' );
			}
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event is back on the site.', '%s events are back on the site.', $n, 'spokares-core' ) : _n( '%s document is back on the site.', '%s documents are back on the site.', $n, 'spokares-core' );
	}
}

/**
 * The items a list-screen message is about: those given, else the ones the
 * redirect names in ?ids= (after "Take it off the site").
 *
 * @param int[]|null $ids Items, or null for ?ids=.
 * @return int[]
 */
function spokares_bulk_ids( ?array $ids ): array {
	if ( null !== $ids ) {
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the message after core's own (nonce-checked) redirect; read-only.
	return isset( $_GET['ids'] ) ? array_values( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ) ) ) ) ) ) : array();
}

/**
 * The name of the one event or document a list-screen message is about, or ''.
 *
 * @param int[]|null $ids Items, or null for ?ids=.
 */
function spokares_bulk_one_name( ?array $ids = null ): string {
	$ids = spokares_bulk_ids( $ids );
	if ( 1 !== count( $ids ) ) {
		return '';
	}
	$post = get_post( (int) reset( $ids ) );
	return $post ? html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ) : '';
}

/**
 * After a Restore (or Undo) on a list screen: are all the restored items
 * drafts?
 *
 * @param int[]|null $ids The restored items, or null for ?ids=.
 */
function spokares_untrashed_as_drafts( ?array $ids = null ): bool {
	$ids = spokares_bulk_ids( $ids );
	if ( ! $ids ) {
		return false;
	}
	foreach ( $ids as $id ) {
		if ( 'draft' !== get_post_status( $id ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Restore (from the Trash view, as well as Undo) brings an event back as it
 * was: on the site if it was on the site.
 *
 * @param string $status   Status WordPress would restore to.
 * @param int    $post_id  Post.
 * @param string $previous Status before it went to the Trash.
 */
function spokares_untrash_previous_status( $status, $post_id, $previous ) {
	if ( 'spk_event' === get_post_type( (int) $post_id ) && in_array( $previous, array( 'publish', 'draft' ), true ) ) {
		return $previous;
	}
	return $status;
}
add_filter( 'wp_untrash_post_status', 'spokares_untrash_previous_status', 10, 3 );

/* -------------------------------------------------------------- list table */

/**
 * The list's views: Upcoming (default), Past, Drafts, Trash.
 *
 * @param array $views Views.
 */
function spokares_event_views( $views ): array {
	$today   = spokares_today();
	$current = spokares_event_list_view();
	$base    = admin_url( 'edit.php?post_type=spk_event' );
	$count   = static function ( array $args ): int {
		$q = new WP_Query(
			array_merge(
				array(
					'post_type'      => 'spk_event',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				),
				$args
			)
		);
		return (int) $q->found_posts;
	};
	$defs    = array(
		'upcoming' => array(
			__( 'Upcoming', 'spokares-core' ),
			$count(
				array(
					'post_status' => 'publish',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small list.
					'meta_query'  => array(
						array(
							'key'     => 'spk_end_sort',
							'value'   => $today,
							'compare' => '>=',
						),
					),
				)
			),
		),
		'past'     => array(
			__( 'Past', 'spokares-core' ),
			$count(
				array(
					'post_status' => 'publish',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small list.
					'meta_query'  => array(
						array(
							'key'     => 'spk_end_sort',
							'value'   => $today,
							'compare' => '<',
						),
					),
				)
			),
		),
		'drafts'   => array( __( 'Drafts', 'spokares-core' ), $count( array( 'post_status' => 'draft' ) ) ),
	);
	$out     = array();
	$all     = 'trash' !== $current && ( spokares_event_list_searching() || spokares_list_needs_check() );
	if ( $all ) {
		// A search (or the Needs checking link) lists every event, whatever view it was typed in.
		$out['spk_all'] = sprintf(
			'<a href="%1$s" class="current" aria-current="page">%2$s</a>',
			esc_url( remove_query_arg( 'spk_view' ) ),
			esc_html( spokares_list_needs_check() ? __( 'Marked “Needs checking” (upcoming, past and drafts)', 'spokares-core' ) : __( 'Search results from every event (upcoming, past and drafts)', 'spokares-core' ) )
		);
	}
	foreach ( $defs as $key => [ $label, $n ] ) {
		$out[ $key ] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( add_query_arg( 'spk_view', $key, $base ) ),
			! $all && $current === $key ? ' class="current" aria-current="page"' : '',
			esc_html( $label ),
			$n
		);
	}
	if ( isset( $views['trash'] ) ) {
		$out['trash'] = $views['trash'];
	}
	return $out;
}
add_filter( 'views_edit-spk_event', 'spokares_event_views' );

/**
 * Which list view is showing.
 */
function spokares_event_list_view(): string {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- list filtering only.
	if ( isset( $_GET['post_status'] ) && 'trash' === $_GET['post_status'] ) {
		return 'trash';
	}
	$view = isset( $_GET['spk_view'] ) ? sanitize_key( wp_unslash( $_GET['spk_view'] ) ) : '';
	if ( isset( $_GET['post_status'] ) && 'draft' === $_GET['post_status'] ) {
		$view = 'drafts';
	}
	// phpcs:enable
	return in_array( $view, array( 'upcoming', 'past', 'drafts' ), true ) ? $view : 'upcoming';
}

/**
 * Is the events list showing a search?
 */
function spokares_event_list_searching(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filtering only.
	return isset( $_GET['s'] ) && '' !== trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) );
}

/**
 * Filter and order the events list for the chosen view.
 *
 * @param WP_Query $q Query.
 */
function spokares_event_list_query( $q ): void {
	if ( ! is_admin() || ! $q->is_main_query() || 'spk_event' !== $q->get( 'post_type' ) ) {
		return;
	}
	global $pagenow;
	if ( 'edit.php' !== $pagenow ) {
		return;
	}
	$view  = spokares_event_list_view();
	$today = spokares_today();
	if ( 'trash' === $view ) {
		return;
	}
	if ( spokares_event_list_searching() || spokares_list_needs_check() ) {
		// A search, or the Dashboard's "Needs checking" link, looks through
		// every event (upcoming, past and drafts), not just the view's.
		$q->set( 'post_status', array( 'publish', 'draft', 'pending', 'future' ) );
		if ( spokares_list_needs_check() ) {
			$q->set(
				'meta_query',
				array(
					array(
						'key'   => 'spk_needs_check',
						'value' => '1',
					),
				)
			);
		}
	} elseif ( 'drafts' === $view ) {
		$q->set( 'post_status', 'draft' );
	} else {
		$q->set( 'post_status', 'publish' );
		$q->set(
			'meta_query',
			array(
				array(
					'key'     => 'spk_end_sort',
					'value'   => $today,
					'compare' => 'upcoming' === $view ? '>=' : '<',
				),
			)
		);
	}
	$orderby = $q->get( 'orderby' );
	if ( ! $orderby || 'spk_sort' === $orderby ) {
		$q->set( 'meta_key', 'spk_sort' );
		$q->set(
			'orderby',
			array(
				'meta_value' => $q->get( 'order' ) ? $q->get( 'order' ) : ( 'past' === $view ? 'DESC' : 'ASC' ),
				'title'      => 'ASC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'spokares_event_list_query' );

/**
 * Columns: When, Event, Type of event, Where it shows (and the webmaster's
 * Needs checking).
 *
 * @param array $cols Columns.
 */
function spokares_event_columns( $cols ): array {
	$out = array(
		'cb'         => $cols['cb'] ?? '<input type="checkbox">',
		'spk_when'   => __( 'When', 'spokares-core' ),
		'title'      => __( 'Event', 'spokares-core' ),
		'spk_kind'   => __( 'Type of event', 'spokares-core' ),
		'spk_status' => __( 'Where it shows', 'spokares-core' ),
	);
	if ( current_user_can( 'manage_options' ) ) {
		$out['spk_check'] = __( 'Needs checking', 'spokares-core' );
	}
	if ( ! spokares_is_site_admin() ) {
		unset( $out['cb'] ); // No bulk actions for editors (below), so no row ticks.
	}
	return $out;
}
add_filter( 'manage_spk_event_posts_columns', 'spokares_event_columns' );

/**
 * No bulk actions on the events list for editors: its only one was "Move to
 * Trash", a second way to do the form's "Take it off the site".
 *
 * @param array $actions Bulk actions.
 */
function spokares_event_bulk_actions( $actions ): array {
	return spokares_is_site_admin() ? (array) $actions : array();
}
add_filter( 'bulk_actions-edit-spk_event', 'spokares_event_bulk_actions', 20 );

/**
 * Where an event is on the site right now, as the list's Where it shows
 * column says it (HTML: "Happening now" is bold).
 *
 * @param array $ev Event data.
 */
function spokares_event_where_it_shows( array $ev ): string {
	if ( 'trash' === $ev['status'] ) {
		return esc_html__( 'In the Trash', 'spokares-core' );
	}
	if ( 'publish' !== $ev['status'] ) {
		return esc_html__( 'Draft (not on the site)', 'spokares-core' );
	}
	$places = spokares_event_places( (int) $ev['id'] );
	if ( ! $places ) {
		return esc_html__( 'Not listed', 'spokares-core' );
	}
	$parts = array();
	if ( $ev['cancelled'] ) {
		$parts[] = esc_html__( 'Cancelled', 'spokares-core' );
	} elseif ( spokares_event_is_now( $ev ) ) {
		$parts[] = '<strong>' . esc_html__( 'Happening now', 'spokares-core' ) . '</strong>';
	}
	$names = spokares_event_place_names();
	foreach ( $places as $place ) {
		$parts[] = esc_html( $names[ $place ] ?? $place );
	}
	return implode( ' · ', $parts );
}

/**
 * Column values.
 *
 * @param string $col     Column.
 * @param int    $post_id Post.
 */
function spokares_event_column( $col, $post_id ): void {
	$ev = spokares_event_data( (int) $post_id );
	switch ( $col ) {
		case 'spk_when':
			// A month- or year-precision event (imported history) is stored on the
			// 1st of its month or year: print what the public Past list prints
			// ("Oct 2022", "2025"), never an invented day and weekday.
			$style = 'date' === $ev['mode'] && in_array( $ev['precision'], array( 'month', 'year' ), true ) ? 'past' : 'row';
			echo esc_html( str_replace( "\u{00A0}", ' ', spokares_fmt_when( $ev, $style ) ) );
			break;
		case 'spk_kind':
			echo esc_html( spokares_event_kinds()[ $ev['kind'] ] ?? '' );
			break;
		case 'spk_status':
			echo wp_kses( spokares_event_where_it_shows( $ev ), array( 'strong' => array() ) );
			break;
		case 'spk_check':
			echo $ev['check'] ? '<span class="spk-flag">' . esc_html__( 'Yes', 'spokares-core' ) . '</span>' : '';
			break;
	}
}
add_action( 'manage_spk_event_posts_custom_column', 'spokares_event_column', 10, 2 );

/**
 * Sortable When.
 *
 * @param array $cols Sortable columns.
 */
function spokares_event_sortable( $cols ): array {
	$cols['spk_when'] = 'spk_sort';
	return $cols;
}
add_filter( 'manage_edit-spk_event_sortable_columns', 'spokares_event_sortable' );

/**
 * The link that makes a draft copy of an event (Make a copy).
 *
 * @param int $post_id Event.
 */
function spokares_event_copy_url( int $post_id ): string {
	return wp_nonce_url( admin_url( 'admin-post.php?action=spokares_duplicate_event&post=' . $post_id ), 'spokares_duplicate_event_' . $post_id );
}

/**
 * Row actions: Edit · Make a copy · View on site. An event is taken off the
 * site from its own form, where the Save box says what that does.
 *
 * @param array   $actions Actions.
 * @param WP_Post $post    Post.
 */
function spokares_event_row_actions( $actions, $post ) {
	if ( ! $post instanceof WP_Post || 'spk_event' !== $post->post_type || 'trash' === $post->post_status ) {
		return $actions;
	}
	$out = array();
	if ( isset( $actions['edit'] ) ) {
		$out['edit'] = $actions['edit'];
	}
	if ( current_user_can( 'edit_spk_events' ) ) {
		$out['duplicate'] = '<a href="' . esc_url( spokares_event_copy_url( $post->ID ) ) . '">' . esc_html__( 'Make a copy', 'spokares-core' ) . '</a>';
	}
	if ( 'publish' === $post->post_status ) {
		$out['view-site'] = '<a href="' . esc_url( spokares_site_url( '/members/exercises/', $post->post_name ) ) . '">' . esc_html__( 'View on site', 'spokares-core' ) . '</a>';
	}
	return $out;
}
add_filter( 'post_row_actions', 'spokares_event_row_actions', 20, 2 );

/**
 * Make a copy: a draft copy with the dates cleared.
 */
function spokares_handle_duplicate_event(): void {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'spokares_duplicate_event_' . $id );
	$src = get_post( $id );
	if ( ! $src || 'spk_event' !== $src->post_type || ! current_user_can( 'edit_post', $id ) || ! current_user_can( 'edit_spk_events' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to copy this event.', 'spokares-core' ), '', array( 'response' => 403 ) );
	}
	// wp_insert_post() and update_post_meta() both expect slashed data: slash
	// the stored values so their backslashes are copied as they are.
	$new = wp_insert_post(
		wp_slash(
			array(
				'post_type'   => 'spk_event',
				'post_status' => 'draft',
				'post_title'  => $src->post_title,
				'menu_order'  => $src->menu_order,
			)
		),
		true
	);
	if ( is_wp_error( $new ) ) {
		wp_die( esc_html( $new->get_error_message() ) );
	}
	$copy = array( 'spk_kind', 'spk_time_start', 'spk_time_end', 'spk_summary', 'spk_where', 'spk_tasks', 'spk_main_url', 'spk_main_label', 'spk_links', 'spk_extra_doc', 'spk_contact_call', 'spk_keep_past', 'spk_precision', 'spk_needs_check' );
	foreach ( $copy as $key ) {
		$value = get_post_meta( $id, $key, true );
		if ( '' !== $value && array() !== $value ) {
			update_post_meta( $new, $key, wp_slash( $value ) );
		}
	}
	update_post_meta( $new, 'spk_date_mode', 'date' );
	// Opens with First day outlined and no sentences until its first save.
	update_post_meta( $new, '_spk_copied', '1' );
	spokares_update_event_sort( (int) $new );
	spokares_add_notice( 'info', __( 'Copied. Set the new date, then Publish.', 'spokares-core' ) );
	wp_safe_redirect( admin_url( 'post.php?post=' . (int) $new . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_spokares_duplicate_event', 'spokares_handle_duplicate_event' );

/* ------------------------------------------------------------------ assets */

/**
 * The event form's and the meeting screens' own script and styles, after
 * the shared admin-forms script (§4.10).
 *
 * @param string $hook Screen hook.
 */
function spokares_event_assets( $hook ): void {
	$screen   = get_current_screen();
	$events   = $screen && 'spk_event' === $screen->post_type;
	$meetings = str_contains( (string) $hook, 'spokares-meeting' );
	if ( ! $events && ! $meetings ) {
		return;
	}
	wp_enqueue_style( 'spokares-admin-events', SPOKARES_CORE_URL . 'assets/css/admin-events.css', array( 'spokares-admin' ), spokares_asset_version( 'assets/css/admin-events.css' ) );
	wp_enqueue_script( 'spokares-admin-events', SPOKARES_CORE_URL . 'assets/js/admin-events.js', array( 'spokares-admin-forms' ), spokares_asset_version( 'assets/js/admin-events.js' ), true );
	wp_add_inline_script(
		'spokares-admin-events',
		'window.spokaresEvents = ' . wp_json_encode(
			array(
				'weekdays'   => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
				// The Words on the button placeholder: what the site prints when
				// the words are left empty (render.php spokares_default_main_label()).
				'groupsHost' => 'spokaneares-acs.groups.io',
				'words'      => __( 'Details', 'spokares-core' ),
				'wordsGroup' => __( 'Exercise details on groups.io', 'spokares-core' ),
			) + spokares_event_name_check_data()
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'spokares_event_assets', 20 );

/**
 * On Add an Event: the events a typed name is checked against ("Already an
 * event: Lilac Festival Armed Forces Torchlight Parade (Date not posted
 * yet). Open it"), as [id, name, when]. A name WordPress saved on its own
 * from an Add that went no further (no type of event) is left out.
 */
function spokares_event_name_check_data(): array {
	$post = get_post();
	if ( ! $post instanceof WP_Post || 'spk_event' !== $post->post_type || 'auto-draft' !== $post->post_status ) {
		return array();
	}
	$ids    = get_posts(
		array(
			'post_type'   => 'spk_event',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => 300, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- one club's events.
			'fields'      => 'ids',
		)
	);
	$events = array();
	foreach ( $ids as $id ) {
		$ev = spokares_event_data( (int) $id );
		if ( '' === (string) $ev['kind'] || '' === trim( (string) get_the_title( $id ) ) ) {
			continue;
		}
		$style    = 'date' === $ev['mode'] && in_array( $ev['precision'], array( 'month', 'year' ), true ) ? 'past' : 'row';
		$events[] = array( (int) $id, html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ), str_replace( "\u{00A0}", ' ', spokares_fmt_when( $ev, $style ) ) );
	}
	return array(
		'events'   => $events,
		'editBase' => admin_url( 'post.php?action=edit&post=' ),
		/* translators: 1: event name; 2: its date, e.g. "Sat, Oct 3" or "Date not posted yet". */
		'already'  => __( 'Already an event: %1$s (%2$s).', 'spokares-core' ),
		'openIt'   => __( 'Open it', 'spokares-core' ),
	);
}
