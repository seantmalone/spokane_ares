<?php
/**
 * Events › Add event and All events (§3.4): a plain form, kind first; fields
 * that don't apply to the kind are hidden; a problem saves everything as a
 * Draft with the field outlined (§4.4). List: Upcoming / Past / Drafts,
 * sortable When, Duplicate.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field labels used in the problem sentences.
 */
function spokares_event_field_names(): array {
	return array(
		'title'      => __( 'the event name', 'spokares-core' ),
		'summary'    => __( 'the short line', 'spokares-core' ),
		'where'      => __( 'the place', 'spokares-core' ),
		'tasks'      => __( 'the task list', 'spokares-core' ),
		'main_label' => __( 'the button text', 'spokares-core' ),
		'links'      => __( 'the link text', 'spokares-core' ),
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
		'mode'       => in_array( $mode, array( 'date', 'not-posted', 'as-requested' ), true ) ? $mode : 'date',
		'start'      => $str( 'spk_start' ),
		'end'        => $str( 'spk_end' ),
		't_start'    => $all ? '' : $str( 'spk_time_start' ),
		't_end'      => $all ? '' : $str( 'spk_time_end' ),
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
	if ( 'as-requested' === $v['mode'] && 'public-service' !== $v['kind'] ) {
		$v['mode'] = 'not-posted';
	}
	// phpcs:enable
	return $v;
}

/**
 * What's wrong with an event's values. Returns field => sentence for blocking
 * problems, plus 'confirm' => [field => [hits]] for phone/e-mail shapes that
 * need the "Publish it" tick, plus 'fixes' (call sign trimmed).
 *
 * @param array $v         Values (spokares_event_submitted() shape).
 * @param array $confirmed Stored confirmations (field => sha1).
 * @return array{problems:array,confirm:array,call:string,dropped:bool}
 */
function spokares_event_problems( array $v, array $confirmed ): array {
	$names    = spokares_event_field_names();
	$problems = array();
	$confirm  = array();
	$kind     = $v['kind'];

	if ( '' === $kind ) {
		$problems['kind'] = __( 'Pick a kind first.', 'spokares-core' );
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
		} elseif ( '' !== $v['t_end'] && ( ! spokares_is_hhmm( $v['t_end'] ) || '' === $v['t_start'] || $v['t_end'] <= $v['t_start'] ) ) {
			$problems['t_end'] = __( 'The end time must be after the start time.', 'spokares-core' );
		}
	}
	if ( 'public-service' !== $kind ) {
		if ( '' !== $v['main_url'] && '' === spokares_clean_url( $v['main_url'] ) ) {
			$problems['main_url'] = __( 'The main link must be a web address that starts with https://', 'spokares-core' );
		}
		foreach ( $v['links'] as $i => $link ) {
			if ( '' === $link['url'] || '' === spokares_clean_url( $link['url'] ) ) {
				$problems[ 'links' . $i ] = __( 'Each extra link needs a web address that starts with https://', 'spokares-core' );
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
			$problems['contact'] = __( 'Volunteer through: call sign only, no names.', 'spokares-core' );
		}
		$call    = $cs['call'];
		$dropped = $cs['dropped'];
	}

	$texts = array(
		'title'   => $v['title'],
		'summary' => $v['summary'],
		'where'   => $v['where'],
	);
	if ( 'exercise' === $kind ) {
		$texts['tasks'] = $v['tasks'];
	}
	if ( 'public-service' !== $kind ) {
		$texts['main_label'] = $v['main_label'];
		$texts['links']      = implode( ' ', wp_list_pluck( $v['links'], 'label' ) );
	}
	foreach ( $texts as $field => $text ) {
		$check = spokares_check_field( $text, $field, $confirmed, in_array( $field, $v['confirm'], true ) );
		if ( $check['block'] ) {
			$problems[ $field ] = sprintf(
				/* translators: 1: field, e.g. "the short line"; 2: what was found. */
				__( '%1$s mentions %2$s.', 'spokares-core' ),
				ucfirst( $names[ $field ] ),
				spokares_and_list( $check['block'] )
			);
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
 * Before the event is written: a problem means Draft (never on the site).
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

	$GLOBALS['spokares_event_check'] = $result;
	if ( $result['problems'] || $result['confirm'] ) {
		if ( $live ) {
			// A published event stays on the site as it is: the fields with a
			// problem keep their live values (the save holds them back, as the
			// settings screens do), everything else is saved.
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
 * event on the site that the form's checks would hold back: for a
 * non-admin, publishing re-runs the checks on the stored fields and keeps
 * the event a Draft on any problem.
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
 * The stored fields behind each form field (for holding a field back).
 */
function spokares_event_field_meta(): array {
	return array(
		'kind'       => array( 'spk_kind' ),
		'start'      => array( 'spk_start', 'spk_date_mode' ),
		'end'        => array( 'spk_end' ),
		't_start'    => array( 'spk_time_start' ),
		't_end'      => array( 'spk_time_end' ),
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
 * What an editor typed into a published event's held-back fields, kept for
 * five minutes so the form shows it again (outlined, with the tick).
 *
 * @param int $post_id Event.
 */
function spokares_event_held( int $post_id ): array {
	static $held = array();
	if ( ! isset( $held[ $post_id ] ) ) {
		$held[ $post_id ] = spokares_retained( 'spk_event_' . $post_id )['values'];
	}
	return $held[ $post_id ];
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

	// Record "Publish it" ticks for the fields whose text is now confirmed.
	foreach ( array( 'title', 'summary', 'tasks', 'main_label', 'links' ) as $field ) {
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
		$map = spokares_event_field_meta();
		foreach ( array_keys( $result['problems'] + $result['confirm'] ) as $field ) {
			foreach ( $map[ $field ] ?? array() as $key ) {
				$held_keys[ $key ] = true;
			}
			$base = str_starts_with( (string) $field, 'links' ) ? 'links' : (string) $field;
			if ( array_key_exists( $base, $v ) ) {
				$typed[ $base ] = $v[ $base ];
			}
		}
		foreach ( array_keys( $typed ) as $field ) {
			unset( $confirmed[ $field ] );
		}
		if ( $typed ) {
			spokares_retain( 'spk_event_' . $post_id, $typed, array() );
		}
	}

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
			update_post_meta( $post_id, $key, $value );
		}
	}
	if ( $confirmed ) {
		update_post_meta( $post_id, 'spk_confirmed', $confirmed );
	}
	spokares_update_event_sort( $post_id );

	// One sentence naming the problem (§4.4).
	$sentences = array_values( $result['problems'] );
	$names     = spokares_event_field_names();
	foreach ( $result['confirm'] as $field => $hits ) {
		$sentences[] = sprintf(
			/* translators: 1: field, 2: what was found. */
			__( '%1$s has %2$s. If it is public, tick the box beside it and save again.', 'spokares-core' ),
			ucfirst( $names[ $field ] ?? $field ),
			spokares_and_list( wp_list_pluck( $hits, 'what' ) )
		);
	}
	if ( $sentences ) {
		if ( $hold ) {
			$lead = __( 'Saved, and the event is still on the site as it was, but these changes were held back (your words are kept in the form):', 'spokares-core' );
		} elseif ( ! empty( $result['demoted'] ) ) {
			$lead = __( 'Saved as a draft, so it’s off the site:', 'spokares-core' );
		} else {
			$lead = __( 'Saved. Before you publish:', 'spokares-core' );
		}
		spokares_add_notice( 'error', $lead . ' ' . implode( ' ', $sentences ) );
	}
	if ( 'publish' === get_post_status( $post_id ) ) {
		spokares_add_notice( 'info', spokares_event_places_sentence( $post_id ) );
	}
	if ( $result['dropped'] && '' !== $result['call'] ) {
		/* translators: %s: call sign. */
		spokares_add_notice( 'warning', sprintf( __( 'Volunteer through: saved %s only. Call signs only; names are never stored.', 'spokares-core' ), $result['call'] ) );
	}
	unset( $GLOBALS['spokares_event_check'] );
}
add_action( 'save_post_spk_event', 'spokares_save_event' );

/**
 * Where each kind of event shows on the site (said beside the kind, so the
 * editor knows where to look after publishing). The views are the ones the
 * members templates use.
 */
function spokares_event_kind_places(): array {
	return array(
		'exercise'       => __( 'Where it shows: Exercises & events › Later this season, until it is one of the next two exercises; then a Next up card there, with the tasks, links and extra form. Also This week on For members when it is one of the next two exercises or trainings in the next 60 days. Never on Home.', 'spokares-core' ),
		'training'       => __( 'Where it shows: Exercises & events › Later this season. Also This week on For members when it is one of the next two exercises or trainings in the next 60 days. Never on Home.', 'spokares-core' ),
		'on-air'         => __( 'Where it shows: Exercises & events › Later this season. Never on Home.', 'spokares-core' ),
		'public-service' => __( 'Where it shows: Exercises & events › Public-service events. Never on Home.', 'spokares-core' ),
	);
}

/**
 * Where a published event shows right now, as one sentence for the notice
 * after a save.
 *
 * @param int $post_id Event.
 */
function spokares_event_places_sentence( int $post_id ): string {
	$in     = static fn( array $list ): bool => in_array( $post_id, array_map( 'intval', wp_list_pluck( $list, 'id' ) ), true );
	$places = array();
	if ( $in(
		spokares_events(
			'next-up',
			array(
				'types' => 'exercise',
				'limit' => 2,
			)
		)
	) ) {
		$places[] = __( 'a Next up card on Exercises & events', 'spokares-core' );
	}
	$later = array(
		'types'     => 'exercise,training,on-air',
		'limit'     => 12,
		'cardTypes' => 'exercise',
		'cardLimit' => 2,
	);
	if ( $in( spokares_events( 'later', $later ) ) ) {
		$places[] = __( 'Later this season on Exercises & events', 'spokares-core' );
	}
	if ( $in( spokares_events( 'public-service' ) ) ) {
		$places[] = __( 'Public-service events on Exercises & events', 'spokares-core' );
	}
	$hub = array(
		'types' => 'exercise,training',
		'limit' => 2,
		'days'  => 60,
	);
	if ( $in( spokares_events( 'upcoming', $hub ) ) ) {
		$places[] = __( 'This week on For members', 'spokares-core' );
	}
	if ( $in( spokares_events( 'past', array( 'limit' => 8 ) ) ) ) {
		$places[] = __( 'Past exercises on Exercises & events', 'spokares-core' );
	}
	if ( ! $places ) {
		return __( 'Published, but no list shows it right now (it has ended, or the lists are full). Events never show on Home.', 'spokares-core' );
	}
	/* translators: %s: places, e.g. "Later this season on Exercises & events and This week on For members". */
	return sprintf( __( 'On the site now: %s. Events never show on Home.', 'spokares-core' ), spokares_and_list( $places ) );
}

/* -------------------------------------------------------------------- form */

/**
 * The stored values of an event in the form's shape, with any held-back
 * typing of the last save laid over them.
 *
 * @param WP_Post $post      Event.
 * @param bool    $with_held Lay the held-back typing over the stored values.
 */
function spokares_event_form_values( WP_Post $post, bool $with_held = true ): array {
	$get       = static fn( $k ) => get_post_meta( $post->ID, $k, true );
	$links     = $get( 'spk_links' );
	$mode      = (string) $get( 'spk_date_mode' );
	$precision = (string) $get( 'spk_precision' );
	$values    = array(
		'title'      => $post->post_title,
		'kind'       => (string) $get( 'spk_kind' ),
		'mode'       => '' !== $mode ? $mode : 'date',
		'start'      => (string) $get( 'spk_start' ),
		'end'        => (string) $get( 'spk_end' ),
		't_start'    => (string) $get( 'spk_time_start' ),
		't_end'      => (string) $get( 'spk_time_end' ),
		'summary'    => (string) $get( 'spk_summary' ),
		'where'      => (string) $get( 'spk_where' ),
		'tasks'      => (string) $get( 'spk_tasks' ),
		'main_url'   => (string) $get( 'spk_main_url' ),
		'main_label' => (string) $get( 'spk_main_label' ),
		'links'      => is_array( $links ) ? $links : array(),
		'extra_doc'  => absint( $get( 'spk_extra_doc' ) ),
		'contact'    => (string) $get( 'spk_contact_call' ),
		// New events keep past exercises by default.
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
 * Kind first (above the event name).
 *
 * @param WP_Post $post Post.
 */
function spokares_event_form_top( $post ): void {
	if ( ! $post instanceof WP_Post || 'spk_event' !== $post->post_type ) {
		return;
	}
	$v        = spokares_event_form_values( $post );
	$problems = 'auto-draft' === $post->post_status ? array() : spokares_event_problems( $v, (array) get_post_meta( $post->ID, 'spk_confirmed', true ) )['problems'];
	wp_nonce_field( 'spokares_event_meta', 'spokares_event_nonce' );
	?>
	<fieldset class="spk-card spk-kind-box<?php echo isset( $problems['kind'] ) ? ' spk-field-error' : ''; ?>" id="spk-kind">
		<legend><?php esc_html_e( 'Kind (pick one first)', 'spokares-core' ); ?></legend>
		<?php foreach ( spokares_event_kinds() as $key => $label ) : ?>
			<label class="spk-choice"><input type="radio" name="spk_kind" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $v['kind'] ); ?>> <?php echo esc_html( $label ); ?></label>
		<?php endforeach; ?>
		<p class="description"><?php esc_html_e( 'On the air: an on-air event members join from their own stations, like SKYWARN Recognition Day.', 'spokares-core' ); ?></p>
		<?php spokares_err_text( $problems, 'kind' ); ?>
		<?php foreach ( spokares_event_kind_places() as $kind_key => $places ) : ?>
			<p class="spk-places" data-kinds="<?php echo esc_attr( $kind_key ); ?>"><?php echo esc_html( $places ); ?></p>
		<?php endforeach; ?>
	</fieldset>
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
	$v         = spokares_event_form_values( $post );
	$confirmed = get_post_meta( $post->ID, 'spk_confirmed', true );
	$check     = 'auto-draft' === $post->post_status ? array(
		'problems' => array(),
		'confirm'  => array(),
	) : spokares_event_problems( $v, is_array( $confirmed ) ? $confirmed : array() );
	$p         = $check['problems'];
	$c         = $check['confirm'];
	$all_day   = '' === $v['t_start'];
	$addr      = __( 'Web address (copy it from your browser’s address bar)', 'spokares-core' );
	$admin     = current_user_can( 'manage_options' );
	$docs      = get_posts(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'publish',
			'numberposts' => 200,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);
	$links     = array_pad(
		array_values( $v['links'] ),
		3,
		array(
			'label' => '',
			'url'   => '',
		)
	);
	?>
	<div class="spk-event-form" data-spk-kind-form>
		<?php spokares_err_text( $p, 'title' ); ?>
		<?php spokares_event_confirm( $c, 'title' ); ?>

		<fieldset class="spk-card spk-when">
			<legend><?php esc_html_e( 'When', 'spokares-core' ); ?></legend>
			<p class="spk-modes">
				<label class="spk-choice"><input type="radio" name="spk_date_mode" value="date" <?php checked( 'date', $v['mode'] ); ?>> <?php esc_html_e( 'On a date', 'spokares-core' ); ?></label>
				<label class="spk-choice"><input type="radio" name="spk_date_mode" value="not-posted" <?php checked( 'not-posted', $v['mode'] ); ?>> <?php esc_html_e( 'Date not posted yet', 'spokares-core' ); ?></label>
				<label class="spk-choice" data-kinds="public-service"><input type="radio" name="spk_date_mode" value="as-requested" <?php checked( 'as-requested', $v['mode'] ); ?>> <?php esc_html_e( 'As requested', 'spokares-core' ); ?></label>
			</p>
			<div class="spk-dates" data-mode="date">
				<p>
					<label for="spk-start"><?php esc_html_e( 'First day', 'spokares-core' ); ?></label>
					<input type="date" id="spk-start" name="spk_start" value="<?php echo esc_attr( $v['start'] ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $p, 'start' ) ) ); ?>">
					<label for="spk-end"><?php esc_html_e( 'Last day', 'spokares-core' ); ?></label>
					<input type="date" id="spk-end" name="spk_end" value="<?php echo esc_attr( $v['end'] ); ?>" class="<?php echo esc_attr( trim( spokares_err_class( $p, 'end' ) ) ); ?>">
					<span class="description"><?php esc_html_e( '(optional)', 'spokares-core' ); ?></span>
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
			</div>
		</fieldset>

		<div class="spk-field">
			<label for="spk-summary"><?php esc_html_e( 'Short line for lists (90 characters)', 'spokares-core' ); ?></label>
			<input type="text" id="spk-summary" name="spk_summary" maxlength="90" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'summary' ) ); ?>" value="<?php echo esc_attr( $v['summary'] ); ?>">
			<p class="description"><?php esc_html_e( 'Shown after the name in lists, and at the top of the card.', 'spokares-core' ); ?></p>
			<?php spokares_err_text( $p, 'summary' ); ?>
			<?php spokares_event_confirm( $c, 'summary' ); ?>
		</div>

		<div class="spk-field">
			<label for="spk-where"><?php esc_html_e( 'Where (optional)', 'spokares-core' ); ?></label>
			<input type="text" id="spk-where" name="spk_where" maxlength="80" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'where' ) ); ?>" value="<?php echo esc_attr( $v['where'] ); ?>">
			<p class="description"><?php esc_html_e( 'A place, or “From your own station”. Shown after the date on the cards and in the lists.', 'spokares-core' ); ?></p>
			<?php spokares_err_text( $p, 'where' ); ?>
			<?php spokares_event_confirm( $c, 'where' ); ?>
		</div>

		<div class="spk-field" data-kinds="exercise">
			<label for="spk-tasks"><?php esc_html_e( 'What members do (one task per line; shown on the Next up card)', 'spokares-core' ); ?></label>
			<textarea id="spk-tasks" name="spk_tasks" rows="5" class="large-text<?php echo esc_attr( spokares_err_class( $p + $c, 'tasks' ) ); ?>"><?php echo esc_textarea( $v['tasks'] ); ?></textarea>
			<?php spokares_err_text( $p, 'tasks' ); ?>
			<?php spokares_event_confirm( $c, 'tasks' ); ?>
		</div>

		<fieldset class="spk-card" data-kinds="exercise,training,on-air">
			<legend><?php esc_html_e( 'Main link (shown as the button)', 'spokares-core' ); ?></legend>
			<p>
				<label for="spk-main-url"><?php echo esc_html( $addr ); ?></label><br>
				<input type="url" id="spk-main-url" name="spk_main_url" class="large-text<?php echo esc_attr( spokares_err_class( $p, 'main_url' ) ); ?>" value="<?php echo esc_attr( $v['main_url'] ); ?>" placeholder="https://">
				<?php spokares_err_text( $p, 'main_url' ); ?>
			</p>
			<p>
				<label for="spk-main-label"><?php esc_html_e( 'Button words', 'spokares-core' ); ?></label><br>
				<input type="text" id="spk-main-label" name="spk_main_label" class="regular-text<?php echo esc_attr( spokares_err_class( $p + $c, 'main_label' ) ); ?>" value="<?php echo esc_attr( $v['main_label'] ); ?>" maxlength="60" placeholder="<?php esc_attr_e( 'Exercise details on groups.io', 'spokares-core' ); ?>">
				<?php spokares_err_text( $p, 'main_label' ); ?>
				<?php spokares_event_confirm( $c, 'main_label' ); ?>
			</p>
		</fieldset>

		<fieldset class="spk-card spk-links" data-kinds="exercise,training,on-air">
			<legend><?php esc_html_e( 'More links (up to 3)', 'spokares-core' ); ?></legend>
			<?php foreach ( $links as $i => $link ) : ?>
				<p class="spk-link-row<?php echo isset( $p[ 'links' . $i ] ) ? ' spk-field-error' : ''; ?>" <?php echo ( $i > 0 && '' === $link['url'] && '' === $link['label'] ) ? 'data-spk-spare hidden' : ''; ?>>
					<label><?php esc_html_e( 'Words', 'spokares-core' ); ?> <input type="text" name="spk_links[<?php echo esc_attr( (string) $i ); ?>][label]" value="<?php echo esc_attr( (string) $link['label'] ); ?>" maxlength="60"></label>
					<label><?php echo esc_html( $addr ); ?> <input type="url" name="spk_links[<?php echo esc_attr( (string) $i ); ?>][url]" class="regular-text" value="<?php echo esc_attr( (string) $link['url'] ); ?>" placeholder="https://"></label>
					<?php spokares_err_text( $p, 'links' . $i ); ?>
				</p>
			<?php endforeach; ?>
			<p><button type="button" class="button spk-add-link"><?php esc_html_e( '+ Add a link', 'spokares-core' ); ?></button></p>
			<?php spokares_err_text( $p, 'links' ); ?>
			<?php spokares_event_confirm( $c, 'links' ); ?>
		</fieldset>

		<div class="spk-field" data-kinds="exercise">
			<label for="spk-extra-doc"><?php esc_html_e( 'Extra form', 'spokares-core' ); ?></label>
			<select id="spk-extra-doc" name="spk_extra_doc">
				<option value="0"><?php esc_html_e( '— none —', 'spokares-core' ); ?></option>
				<?php foreach ( $docs as $doc ) : ?>
					<option value="<?php echo esc_attr( (string) $doc->ID ); ?>" <?php selected( $doc->ID, $v['extra_doc'] ); ?>><?php echo esc_html( html_entity_decode( get_the_title( $doc ), ENT_QUOTES, 'UTF-8' ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'A published document members need for this exercise.', 'spokares-core' ); ?></p>
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
			<span class="spk-label"><?php esc_html_e( 'After it ends:', 'spokares-core' ); ?></span>
			<label><input type="checkbox" name="spk_keep_past" value="1" <?php checked( $v['keep_past'] ); ?>> <?php esc_html_e( 'Keep in Past exercises', 'spokares-core' ); ?></label>
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

		<p class="spk-never"><?php esc_html_e( 'Never publish: no county, hospital, SHARES or 800 MHz channels; no names, phones or e-mails.', 'spokares-core' ); ?></p>
	</div>
	<?php
}
add_action( 'edit_form_after_title', 'spokares_event_form_fields' );

/**
 * The "Publish it" tick beside an event field with a phone/e-mail shape.
 *
 * @param array  $confirm Field => hits.
 * @param string $field   Field.
 */
function spokares_event_confirm( array $confirm, string $field ): void {
	if ( empty( $confirm[ $field ] ) ) {
		return;
	}
	printf(
		'<label class="spk-confirm"><input type="checkbox" name="spk_confirm[%1$s]" value="1"> %2$s</label>',
		esc_attr( $field ),
		esc_html( spokares_confirm_label( $confirm[ $field ] ) )
	);
}

/**
 * Side boxes: Save and Checking.
 *
 * @param WP_Post $post Post.
 */
function spokares_event_boxes( $post ): void {
	add_meta_box( 'spokares_savebox', __( 'Save', 'spokares-core' ), static fn( $p ) => spokares_render_save_box( $p ), 'spk_event', 'side', 'high' );
	add_meta_box( 'spokares_checking', __( 'Webmaster check', 'spokares-core' ), 'spokares_render_checking_box', 'spk_event', 'side', 'default' );
	unset( $post );
}
add_action( 'add_meta_boxes_spk_event', 'spokares_event_boxes' );

/**
 * The "Needs checking (webmaster only)" box: editable by administrators,
 * shown read-only to editors.
 *
 * @param WP_Post $post Post.
 */
function spokares_render_checking_box( WP_Post $post ): void {
	$on = '1' === (string) get_post_meta( $post->ID, 'spk_needs_check', true );
	if ( current_user_can( 'manage_options' ) ) {
		echo '<label><input type="checkbox" name="spk_needs_check" value="1" ' . checked( $on, true, false ) . '> ' . esc_html__( 'Needs checking (webmaster only)', 'spokares-core' ) . '</label>';
		return;
	}
	// This box is only the webmaster's own flag; problems with the words are
	// outlined in red on the form itself.
	echo '<p>' . ( $on ? esc_html__( 'Marked for the webmaster to check.', 'spokares-core' ) : esc_html__( 'Not marked for the webmaster.', 'spokares-core' ) ) . '</p>';
}

/**
 * Saved messages that link to the place on the site.
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
	$link                         = ' <a href="' . esc_url( $url ) . '">' . esc_html( $is_event ? __( 'See it on Exercises & events', 'spokares-core' ) : __( 'See it on Documents & forms', 'spokares-core' ) ) . '</a>';
	$draft                        = esc_html__( 'Draft saved. Drafts never show on the site.', 'spokares-core' );
	$m                            = array(
		1  => esc_html__( 'Saved.', 'spokares-core' ) . $link,
		4  => esc_html__( 'Saved.', 'spokares-core' ) . $link,
		6  => esc_html__( 'Published.', 'spokares-core' ) . $link,
		7  => esc_html__( 'Saved.', 'spokares-core' ),
		8  => esc_html__( 'Submitted.', 'spokares-core' ),
		10 => $draft,
	);
	$messages[ $post->post_type ] = $m;
	return $messages;
}
add_filter( 'post_updated_messages', 'spokares_event_messages' );

/**
 * List-screen messages that name the thing ("1 event moved to the Trash."),
 * not core's "post".
 *
 * @param array $messages    Messages by post type.
 * @param array $bulk_counts Counts by action.
 */
function spokares_bulk_messages( $messages, $bulk_counts ) {
	foreach ( array( 'spk_event', 'spk_document' ) as $type ) {
		$is_event = 'spk_event' === $type;
		$m        = array();
		foreach ( array( 'updated', 'locked', 'deleted', 'trashed', 'untrashed' ) as $action ) {
			$m[ $action ] = spokares_bulk_message( $is_event, $action, (int) ( $bulk_counts[ $action ] ?? 0 ) );
		}
		$messages[ $type ] = $m;
	}
	return $messages;
}

/**
 * One list-screen message; core fills in the number (%s).
 *
 * @param bool   $is_event Event (else document).
 * @param string $action   updated, locked, deleted, trashed or untrashed.
 * @param int    $n        How many.
 */
function spokares_bulk_message( bool $is_event, string $action, int $n ): string {
	switch ( $action ) {
		case 'updated':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event updated.', '%s events updated.', $n, 'spokares-core' ) : _n( '%s document updated.', '%s documents updated.', $n, 'spokares-core' );
		case 'locked':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event not updated: someone else is editing it.', '%s events not updated: someone else is editing them.', $n, 'spokares-core' ) : _n( '%s document not updated: someone else is editing it.', '%s documents not updated: someone else is editing them.', $n, 'spokares-core' );
		case 'deleted':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event deleted for good.', '%s events deleted for good.', $n, 'spokares-core' ) : _n( '%s document deleted for good.', '%s documents deleted for good.', $n, 'spokares-core' );
		case 'trashed':
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event moved to the Trash.', '%s events moved to the Trash.', $n, 'spokares-core' ) : _n( '%s document moved to the Trash.', '%s documents moved to the Trash.', $n, 'spokares-core' );
		default:
			/* translators: %s: how many. */
			return $is_event ? _n( '%s event restored from the Trash.', '%s events restored from the Trash.', $n, 'spokares-core' ) : _n( '%s document restored from the Trash.', '%s documents restored from the Trash.', $n, 'spokares-core' );
	}
}
add_filter( 'bulk_post_updated_messages', 'spokares_bulk_messages', 10, 2 );

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
	foreach ( $defs as $key => [ $label, $n ] ) {
		$out[ $key ] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( add_query_arg( 'spk_view', $key, $base ) ),
			$current === $key ? ' class="current" aria-current="page"' : '',
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
	if ( 'drafts' === $view ) {
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
 * Columns: When, Event name, Kind, Status, Needs checking.
 *
 * @param array $cols Columns.
 */
function spokares_event_columns( $cols ): array {
	return array(
		'cb'         => $cols['cb'] ?? '<input type="checkbox">',
		'spk_when'   => __( 'When', 'spokares-core' ),
		'title'      => __( 'Event name', 'spokares-core' ),
		'spk_kind'   => __( 'Kind', 'spokares-core' ),
		'spk_status' => __( 'Status', 'spokares-core' ),
		'spk_check'  => __( 'Needs checking', 'spokares-core' ),
	);
}
add_filter( 'manage_spk_event_posts_columns', 'spokares_event_columns' );

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
			echo esc_html( str_replace( "\u{00A0}", ' ', spokares_fmt_when( $ev, 'row' ) ) );
			break;
		case 'spk_kind':
			echo esc_html( spokares_event_kinds()[ $ev['kind'] ] ?? __( '(no kind)', 'spokares-core' ) );
			break;
		case 'spk_status':
			if ( 'publish' !== $ev['status'] ) {
				esc_html_e( 'Draft', 'spokares-core' );
			} elseif ( spokares_event_is_now( $ev ) ) {
				echo '<strong>' . esc_html__( 'Happening now', 'spokares-core' ) . '</strong>';
			} elseif ( 'date' === $ev['mode'] && ( '' !== $ev['end'] ? $ev['end'] : $ev['start'] ) < spokares_today() ) {
				esc_html_e( 'Past', 'spokares-core' );
			} else {
				esc_html_e( 'Upcoming', 'spokares-core' );
			}
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
 * Row actions: Edit · Duplicate · Trash · View on site.
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
		$url              = wp_nonce_url( admin_url( 'admin-post.php?action=spokares_duplicate_event&post=' . $post->ID ), 'spokares_duplicate_event_' . $post->ID );
		$out['duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'spokares-core' ) . '</a>';
	}
	if ( isset( $actions['trash'] ) ) {
		$out['trash'] = $actions['trash'];
	}
	if ( 'publish' === $post->post_status ) {
		$out['view-site'] = '<a href="' . esc_url( spokares_site_url( '/members/exercises/', $post->post_name ) ) . '">' . esc_html__( 'View on site', 'spokares-core' ) . '</a>';
	}
	return $out;
}
add_filter( 'post_row_actions', 'spokares_event_row_actions', 20, 2 );

/**
 * Duplicate: a draft copy with the dates cleared.
 */
function spokares_handle_duplicate_event(): void {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'spokares_duplicate_event_' . $id );
	$src = get_post( $id );
	if ( ! $src || 'spk_event' !== $src->post_type || ! current_user_can( 'edit_post', $id ) || ! current_user_can( 'edit_spk_events' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to copy this event.', 'spokares-core' ), '', array( 'response' => 403 ) );
	}
	$new = wp_insert_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'draft',
			'post_title'  => $src->post_title,
			'menu_order'  => $src->menu_order,
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
			update_post_meta( $new, $key, $value );
		}
	}
	update_post_meta( $new, 'spk_date_mode', 'date' );
	spokares_update_event_sort( (int) $new );
	spokares_add_notice( 'info', __( 'Copied as a draft. Set the new date, then Publish.', 'spokares-core' ) );
	wp_safe_redirect( admin_url( 'post.php?post=' . (int) $new . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_spokares_duplicate_event', 'spokares_handle_duplicate_event' );
