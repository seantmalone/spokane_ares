<?php
/**
 * Regression tests for QA-054 (PLAN §4.4 "Never throw away what the editor
 * typed", §3 field limits): the plugin's save handlers relied on the
 * browser's maxlength and type="time" checks. A request made without them
 * (curl, or a browser with the attributes removed) was saved with a plain
 * "Saved." notice:
 *
 * - a 327-character rota note (the field's maxlength is 80),
 * - a 660-character open-slot line (maxlength 160), printed in full under
 *   the rota on /members/,
 * - a 270-character meeting name (maxlength 80),
 * - a 400-character event Short line (maxlength 90) and a 300-character
 *   Where (maxlength 80), both live on a published event,
 * - a 200-character document note (maxlength 60), and a 9-word note
 *   although the field says "8 words or fewer".
 *
 * Invalid times were also dropped without a word: a net time of 25:00 or
 * an empty one, and a GMRS time of "7pm", kept the old value under
 * "Saved.", and a meeting start time of 25:00 cleared the stored time.
 *
 * Expected (§4.4): on a settings screen the field is not saved and keeps its
 * stored value, the typed text comes back in the form with an error for the
 * field, and the notice is not a plain "Saved.". On a post form (Events,
 * Documents) the over-long text never goes live: the field is held back on
 * a published item or the item is saved as a Draft, and an error sentence
 * says why.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Plain text of exactly $len characters. It matches none of the
 * never-publish, phone or e-mail patterns, and sanitize_text_field() leaves
 * it as it is.
 *
 * @param int $len Length.
 */
function qa054_text( int $len ): string {
	$text = substr( str_repeat( 'Bring a spare battery and the go-kit list. ', 1 + intdiv( $len, 20 ) ), 0, $len - 1 );
	return $text . 'x';
}

/**
 * A day this many days after the site's "today".
 *
 * @param int $days Days.
 */
function qa054_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( \spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa054_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Is $typed one of the held values (at any depth)?
 *
 * @param array  $values Held values.
 * @param string $typed  Typed text.
 */
function qa054_has_value( array $values, string $typed ): bool {
	foreach ( $values as $value ) {
		if ( is_array( $value ) ? qa054_has_value( $value, $typed ) : (string) $value === $typed ) {
			return true;
		}
	}
	return false;
}

/**
 * The editor was told: an error notice, and no plain "Saved.".
 *
 * @param array  $notices Notices.
 * @param string $msg     Message prefix.
 */
function qa054_assert_told( array $notices, string $msg ): void {
	assert_contains( 'error', wp_list_pluck( $notices, 'type' ), $msg . ': no error notice (notices: ' . wp_json_encode( wp_list_pluck( $notices, 'text' ) ) . ')' );
	assert_not_contains( 'Saved.', wp_list_pluck( $notices, 'text' ), $msg . ': a plain "Saved." notice' );
}

/**
 * A settings screen held the field back for the form: an error for it (so
 * it is outlined) and, when something was typed, the typed text.
 *
 * @param array  $retained spokares_retained() for the screen.
 * @param string $typed    What was typed.
 * @param string $msg      Message prefix.
 */
function qa054_assert_retained( array $retained, string $typed, string $msg ): void {
	assert_true( (bool) $retained['errors'], $msg . ': no field error kept for the form, so nothing is outlined' );
	if ( '' !== $typed ) {
		assert_true( qa054_has_value( $retained['values'], $typed ), $msg . ': the typed text is not kept for the form' );
	}
}

/* ------------------------------------------------------------- Net details */

/**
 * The Net details form fields as the screen posts them, with some of the
 * nets fields replaced.
 *
 * @param array $nets_in Nets fields to replace.
 */
function qa054_net_fields( array $nets_in ): array {
	$radio = \spokares_opt( 'spk_radio' );
	$nets  = \spokares_opt( 'spk_nets' );
	return array(
		'radio' => array(
			'primary'   => array(
				'call'   => $radio['primary']['call'],
				'freq'   => $radio['primary']['freq'],
				'offset' => $radio['primary']['offset'],
				'tone'   => $radio['primary']['tone'],
			),
			'alternate' => array(
				'freq'   => $radio['alternate']['freq'],
				'offset' => $radio['alternate']['offset'],
				'tone'   => $radio['alternate']['tone'],
				'show'   => $radio['alternate']['show'] ? '1' : '',
			),
		),
		'nets'  => array_merge(
			array(
				'net_time'       => $nets['net_time'],
				'gmrs_time'      => $nets['gmrs_time'],
				'winlink_nth'    => array_map( 'strval', $nets['winlink_nth'] ),
				'simplex_nth'    => array_map( 'strval', $nets['simplex_nth'] ),
				'gmrs_nth'       => array_map( 'strval', $nets['gmrs_nth'] ),
				'winlink_howto'  => $nets['winlink_howto'],
				'open_slot_line' => $nets['open_slot_line'],
			),
			$nets_in
		),
	);
}

/**
 * Save Net details as the current user; return the stored nets, the held
 * input and the notices.
 *
 * @param array  $nets_in Nets fields to replace.
 * @param string $msg     Message prefix.
 */
function qa054_save_net( array $nets_in, string $msg ): array {
	qa054_notices();
	$res = post_form( 'spokares_save_net_details', qa054_net_fields( $nets_in ) );
	assert_same( null, $res['die'], $msg . ': save refused' );
	assert_contains( 'page=spokares-net-details', (string) $res['redirect'], $msg . ': redirect back to the screen' );
	return array(
		'nets'     => \spokares_opt( 'spk_nets' ),
		'retained' => \spokares_retained( 'spokares-net-details' ),
		'notices'  => qa054_notices(),
	);
}

test(
	'control: Net details saved as shown says "Saved." and keeps the times',
	function () {
		as_role( 'ares-net' );
		$before = \spokares_opt( 'spk_nets' );
		$out    = qa054_save_net( array(), 'unchanged form' );
		assert_same( $before['net_time'], $out['nets']['net_time'], 'net time' );
		assert_same( $before['gmrs_time'], $out['nets']['gmrs_time'], 'GMRS time' );
		assert_same( $before['open_slot_line'], $out['nets']['open_slot_line'], 'open-slot line' );
		assert_same( array(), $out['retained']['errors'], 'no field errors' );
		assert_contains( 'Saved.', wp_list_pluck( $out['notices'], 'text' ), 'the success notice' );
	}
);

test(
	'Net details: a net time of 25:00 is held back with an error, not dropped under "Saved."',
	function () {
		as_role( 'ares-net' );
		$before = \spokares_opt( 'spk_nets' )['net_time'];
		$out    = qa054_save_net( array( 'net_time' => '25:00' ), 'net time 25:00' );
		assert_same( $before, $out['nets']['net_time'], 'net time 25:00: the stored net time changed' );
		qa054_assert_retained( $out['retained'], '25:00', 'net time 25:00' );
		qa054_assert_told( $out['notices'], 'net time 25:00' );
	}
);

test(
	'Net details: an empty net time is held back with an error, not dropped under "Saved."',
	function () {
		as_role( 'ares-net' );
		$before = \spokares_opt( 'spk_nets' )['net_time'];
		$out    = qa054_save_net( array( 'net_time' => '' ), 'empty net time' );
		assert_same( $before, $out['nets']['net_time'], 'empty net time: the stored net time changed' );
		qa054_assert_retained( $out['retained'], '', 'empty net time' );
		qa054_assert_told( $out['notices'], 'empty net time' );
	}
);

test(
	'Net details: a GMRS time of "7pm" is held back with an error, not dropped under "Saved."',
	function () {
		as_role( 'ares-net' );
		$before = \spokares_opt( 'spk_nets' )['gmrs_time'];
		assert_not_same( '', $before, 'set-up: the seeded GMRS time' );
		$out = qa054_save_net( array( 'gmrs_time' => '7pm' ), 'GMRS time 7pm' );
		assert_same( $before, $out['nets']['gmrs_time'], 'GMRS time 7pm: the stored GMRS time changed' );
		qa054_assert_retained( $out['retained'], '7pm', 'GMRS time 7pm' );
		qa054_assert_told( $out['notices'], 'GMRS time 7pm' );
	}
);

test(
	'Net details: a 660-character open-slot line (maxlength 160) is held back, not saved',
	function () {
		as_role( 'ares-net' );
		$before = \spokares_opt( 'spk_nets' )['open_slot_line'];
		$typed  = qa054_text( 660 );
		$out    = qa054_save_net( array( 'open_slot_line' => $typed ), '660-character open-slot line' );
		assert_true( mb_strlen( $out['nets']['open_slot_line'] ) <= 160, '660-character open-slot line: stored ' . mb_strlen( $out['nets']['open_slot_line'] ) . ' characters (form maxlength 160), printed under the rota on /members/' );
		assert_same( $before, $out['nets']['open_slot_line'], '660-character open-slot line: the stored line changed' );
		qa054_assert_retained( $out['retained'], $typed, '660-character open-slot line' );
		qa054_assert_told( $out['notices'], '660-character open-slot line' );
	}
);

/* ----------------------------------------------------------------- Net rota */

test(
	'Net rota: a 327-character note (maxlength 80) is held back on its row, not saved',
	function () {
		as_role( 'ares-editor' );
		$row    = \spokares_rota_rows( 13 )[1];
		$date   = $row['date'];
		$stored = array(
			'state'   => $row['state'],
			'call'    => $row['call'],
			'note'    => $row['row_note'],
			'wl_task' => $row['wl_task'],
			'wl_form' => $row['wl_form'],
		);
		$typed  = qa054_text( 327 );
		qa054_notices();
		$res = post_form(
			'spokares_save_rota',
			array(
				'weeks' => '13',
				'rota'  => array(
					$date => array_merge(
						$stored,
						array(
							'h'    => \spokares_rota_hash( $stored ),
							'note' => $typed,
						)
					),
				),
			)
		);
		assert_same( null, $res['die'], 'save refused' );
		assert_contains( 'page=spokares-rota', (string) $res['redirect'], 'redirect back to the rota' );

		$after = \spokares_opt( 'spk_rota' )[ $date ]['note'] ?? '';
		assert_true( mb_strlen( $after ) <= 80, '327-character rota note: stored ' . mb_strlen( $after ) . ' characters (form maxlength 80), printed on /members/' );
		assert_same( $stored['note'], $after, '327-character rota note: the stored note changed' );
		qa054_assert_retained( \spokares_retained( 'spokares-rota' ), $typed, '327-character rota note' );
		$notices = qa054_notices();
		assert_contains( 'error', wp_list_pluck( $notices, 'type' ), '327-character rota note: no error notice (notices: ' . wp_json_encode( wp_list_pluck( $notices, 'text' ) ) . ')' );
		assert_not_contains( 'success', wp_list_pluck( $notices, 'type' ), '327-character rota note: a success notice ("Saved 1 Tuesday.") for a row that changed only in the held-back note' );
	}
);

/* ------------------------------------------------------------ Meeting rules */

/**
 * One meeting's rule fields as the Meeting rules screen posts them.
 *
 * @param array $m Meeting (normalised).
 */
function qa054_rule_fields( array $m ): array {
	return array(
		'id'          => $m['id'],
		'name'        => $m['name'],
		'nth'         => array_map( 'strval', $m['nth'] ),
		'weekday'     => (string) $m['weekday'],
		'start'       => $m['start'],
		'end'         => $m['end'],
		'time_text'   => $m['time_text'],
		'home_extra'  => $m['home_extra'],
		'skip_months' => array_map( 'strval', $m['skip_months'] ),
		'show_home'   => $m['show_home'] ? '1' : '',
		'active'      => $m['active'] ? '1' : '',
	);
}

/**
 * Save Meeting rules with one meeting's fields replaced; return that
 * meeting as stored after the save, the held input and the notices.
 *
 * @param int    $index   Index of the meeting in spk_meetings.
 * @param array  $replace Fields to replace for it.
 * @param string $msg     Message prefix.
 */
function qa054_save_rules( int $index, array $replace, string $msg ): array {
	$meetings = \spokares_opt( 'spk_meetings' )['meetings'];
	$rules    = array();
	foreach ( $meetings as $i => $m ) {
		$rules[ $i ] = qa054_rule_fields( $m );
	}
	$rules[ $index ] = array_merge( $rules[ $index ], $replace );
	$id              = $meetings[ $index ]['id'];
	qa054_notices();
	$res = post_form( 'spokares_save_meeting_rules', array( 'rules' => $rules ) );
	assert_same( null, $res['die'], $msg . ': save refused' );
	assert_contains( 'page=spokares-meeting-rules', (string) $res['redirect'], $msg . ': redirect back to the screen' );
	$after = null;
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $id ) {
			$after = $m;
		}
	}
	assert_true( is_array( $after ), $msg . ': the meeting is still there' );
	return array(
		'meeting'  => $after,
		'retained' => \spokares_retained( 'spokares-meeting-rules' ),
		'notices'  => qa054_notices(),
	);
}

test(
	'Meeting rules: a 270-character meeting name (maxlength 80) is held back, not saved',
	function () {
		as_role( 'ares-net' );
		$before = \spokares_opt( 'spk_meetings' )['meetings'][0];
		$typed  = qa054_text( 270 );
		$out    = qa054_save_rules( 0, array( 'name' => $typed ), '270-character meeting name' );
		assert_true( mb_strlen( $out['meeting']['name'] ) <= 80, '270-character meeting name: stored ' . mb_strlen( $out['meeting']['name'] ) . ' characters (form maxlength 80)' );
		assert_same( $before['name'], $out['meeting']['name'], '270-character meeting name: the stored name changed' );
		qa054_assert_retained( $out['retained'], $typed, '270-character meeting name' );
		qa054_assert_told( $out['notices'], '270-character meeting name' );
	}
);

test(
	'Meeting rules: a start time of 25:00 is held back with an error, not saved as "no time"',
	function () {
		as_role( 'ares-net' );
		$index = null;
		foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $i => $m ) {
			if ( null === $index && '' !== $m['start'] ) {
				$index = (int) $i;
			}
		}
		if ( null === $index ) {
			skip( 'no seeded meeting has a start time' );
		}
		$before = \spokares_opt( 'spk_meetings' )['meetings'][ $index ];
		$out    = qa054_save_rules( $index, array( 'start' => '25:00' ), 'meeting start 25:00' );
		assert_same( $before['start'], $out['meeting']['start'], 'meeting start 25:00: the stored start time changed' );
		qa054_assert_retained( $out['retained'], '25:00', 'meeting start 25:00' );
		qa054_assert_told( $out['notices'], 'meeting start 25:00' );
	}
);

/* ------------------------------------------------------------------- Events */

/**
 * A published exercise with a short line and a place.
 */
function qa054_event(): int {
	$id = create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'publish',
			'post_title'  => 'QA054 Winlink drill',
			'post_author' => get_current_user_id(),
		)
	);
	update_post_meta( $id, 'spk_kind', 'exercise' );
	update_post_meta( $id, 'spk_date_mode', 'date' );
	update_post_meta( $id, 'spk_start', qa054_day( 10 ) );
	update_post_meta( $id, 'spk_summary', 'Check in on the Winlink net.' );
	update_post_meta( $id, 'spk_where', 'EOC room B' );
	return $id;
}

/**
 * Click Update on a published event's edit screen with these fields
 * replaced, as the browser sends the form (edit_post(), as post.php runs it).
 *
 * @param int   $id      Event.
 * @param array $replace Form fields to replace.
 */
function qa054_update_event( int $id, array $replace ): void {
	$fields = array_merge(
		array(
			'action'               => 'editpost',
			'originalaction'       => 'editpost',
			'post_ID'              => (string) $id,
			'post_type'            => 'spk_event',
			'post_title'           => get_post_field( 'post_title', $id ),
			'post_status'          => 'publish',
			'original_post_status' => 'publish',
			'save'                 => 'Update',
			'_wpnonce'             => wp_create_nonce( 'update-post_' . $id ),
			'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ),
			'spk_kind'             => 'exercise',
			'spk_date_mode'        => 'date',
			'spk_start'            => (string) get_post_meta( $id, 'spk_start', true ),
			'spk_all_day'          => '1',
			'spk_summary'          => (string) get_post_meta( $id, 'spk_summary', true ),
			'spk_where'            => (string) get_post_meta( $id, 'spk_where', true ),
		),
		$replace
	);
	$res    = call_request(
		'POST',
		array(),
		$fields,
		static function () {
			return edit_post();
		}
	);
	assert_same( null, $res['die'], 'edit_post() did not wp_die()' );
	assert_same( $id, (int) $res['returned'], 'edit_post() returned the event' );
	clean_post_cache( $id );
	assert_same( 'exercise', (string) get_post_meta( $id, 'spk_kind', true ), 'the form was saved by the plugin handler' );
}

test(
	'Events: a 400-character Short line (maxlength 90) never goes live on a published event',
	function () {
		as_role( 'ares-editor' );
		$id = qa054_event();
		qa054_notices();
		qa054_update_event( $id, array( 'spk_summary' => qa054_text( 400 ) ) );
		$status  = get_post_status( $id );
		$summary = (string) get_post_meta( $id, 'spk_summary', true );
		assert_false( 'publish' === $status && mb_strlen( $summary ) > 90, 'a ' . mb_strlen( $summary ) . '-character Short line is live on a published event (form maxlength 90)' );
		$notices = qa054_notices();
		assert_contains( 'error', wp_list_pluck( $notices, 'type' ), '400-character Short line: no error sentence (notices: ' . wp_json_encode( wp_list_pluck( $notices, 'text' ) ) . ')' );
	}
);

test(
	'Events: a 300-character Where (maxlength 80) never goes live on a published event',
	function () {
		as_role( 'ares-editor' );
		$id = qa054_event();
		qa054_notices();
		qa054_update_event( $id, array( 'spk_where' => qa054_text( 300 ) ) );
		$status = get_post_status( $id );
		$where  = (string) get_post_meta( $id, 'spk_where', true );
		assert_false( 'publish' === $status && mb_strlen( $where ) > 80, 'a ' . mb_strlen( $where ) . '-character Where is live on a published event (form maxlength 80)' );
		$notices = qa054_notices();
		assert_contains( 'error', wp_list_pluck( $notices, 'type' ), '300-character Where: no error sentence (notices: ' . wp_json_encode( wp_list_pluck( $notices, 'text' ) ) . ')' );
	}
);

/* ---------------------------------------------------------------- Documents */

/**
 * A published, privacy-checked link document in Forms (made by an
 * administrator).
 */
function qa054_document(): int {
	as_role( 'admin' );
	$id = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'publish',
			'post_title'  => 'QA054 Winlink form',
		)
	);
	wp_set_object_terms( $id, 'forms', 'spk_doc_cat', false );
	update_post_meta( $id, 'spk_source', 'link' );
	update_post_meta( $id, 'spk_url', 'https://example.org/qa054-form' );
	update_post_meta( $id, 'spk_format', 'Online form' );
	update_post_meta( $id, 'spk_privacy_ok', '1' );
	update_post_meta( $id, 'spk_note', 'Use the current version.' );
	return $id;
}

/**
 * Click Update on a published document's edit screen with this note, as
 * the browser sends the form (edit_post(), as post.php runs it).
 *
 * @param int    $doc  Document.
 * @param string $note Short note.
 */
function qa054_update_document( int $doc, string $note ): void {
	$fields = array(
		'action'                  => 'editpost',
		'originalaction'          => 'editpost',
		'post_ID'                 => (string) $doc,
		'post_type'               => 'spk_document',
		'post_title'              => get_post_field( 'post_title', $doc ),
		'post_status'             => 'publish',
		'original_post_status'    => 'publish',
		'save'                    => 'Update',
		'_wpnonce'                => wp_create_nonce( 'update-post_' . $doc ),
		'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
		'spk_section'             => 'forms',
		'spk_source'              => 'link',
		'spk_url'                 => 'https://example.org/qa054-form',
		'spk_privacy_ok'          => '1',
		'spk_format'              => 'Online form',
		'spk_version'             => '2026',
		'spk_note'                => $note,
		'spk_source_label'        => '',
	);
	$res    = call_request(
		'POST',
		array(),
		$fields,
		static function () {
			return edit_post();
		}
	);
	assert_same( null, $res['die'], 'edit_post() did not wp_die()' );
	assert_same( $doc, (int) $res['returned'], 'edit_post() returned the document' );
	clean_post_cache( $doc );
	assert_same( '2026', (string) get_post_meta( $doc, 'spk_version', true ), 'the form was saved by the plugin handler' );
}

test(
	'Documents: a 200-character note (maxlength 60) never goes live on a published document',
	function () {
		$doc = qa054_document();
		as_role( 'ares-editor' );
		qa054_notices();
		qa054_update_document( $doc, qa054_text( 200 ) );
		$status = get_post_status( $doc );
		$note   = (string) get_post_meta( $doc, 'spk_note', true );
		assert_false( 'publish' === $status && mb_strlen( $note ) > 60, 'a ' . mb_strlen( $note ) . '-character note is live on a published document (form maxlength 60)' );
		$notices = qa054_notices();
		assert_contains( 'error', wp_list_pluck( $notices, 'type' ), '200-character note: no error sentence (notices: ' . wp_json_encode( wp_list_pluck( $notices, 'text' ) ) . ')' );
	}
);

test(
	'Documents: a 9-word note ("8 words or fewer") is refused or warned about',
	function () {
		$doc = qa054_document();
		as_role( 'ares-editor' );
		$note = 'Answer on an ICS-213 before the Tuesday net starts';
		assert_same( 9, count( preg_split( '/\s+/', $note ) ), 'set-up: nine words' );
		assert_true( mb_strlen( $note ) <= 60, 'set-up: within the 60-character maxlength' );
		qa054_notices();
		qa054_update_document( $doc, $note );
		$told = array_filter(
			qa054_notices(),
			static fn( $n ) => in_array( $n['type'], array( 'error', 'warning' ), true ) && preg_match( '/\bnote\b/i', (string) $n['text'] )
		);
		assert_true( (bool) $told, 'a 9-word note was saved without a word although the field says "8 words or fewer"' );
	}
);
