<?php
/**
 * Regression tests for QA-059 (PLAN §3.4 Meeting rules and Net details,
 * §8.2 #18 "Rota overwrites": changed rows only, with a conflict notice;
 * the same rule for the other shared settings screens).
 *
 * Two people open Meeting rules. A changes the Second Saturday Workshop to
 * 10:00–13:00 and saves; B, whose screen was opened earlier, changes the
 * Third Thursday time words and saves. Both see "Saved.", and A's workshop
 * times are silently put back to 09:00–12:00, because the form posts every
 * field of every meeting and the save writes all of them, with no record of
 * what the form showed when it was opened. Net details does the same (A's
 * new Tone is put back by B's open-slot line save). Net rota and Regular
 * meetings keep the newer save and tell the editor instead.
 *
 * Each save posts exactly what the browser sends from the screen as it was
 * drawn for that user (every input, ticked box, chosen option and textarea
 * of the rendered form), with the editor's typing applied, so any hidden
 * "what the form showed" fields a fix adds are sent as well.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * One attribute of a tag, decoded ('' when absent).
 *
 * @param string $tag  The tag.
 * @param string $name Attribute.
 */
function qa059_attr( string $tag, string $name ): string {
	if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag, $m ) ) {
		return html_entity_decode( '' !== ( $m[2] ?? '' ) ? $m[2] : ( $m[3] ?? '' ), ENT_QUOTES, 'UTF-8' );
	}
	return '';
}

/**
 * Is a boolean attribute (checked, selected) present on a tag?
 *
 * @param string $tag  The tag.
 * @param string $name Attribute.
 */
function qa059_has( string $tag, string $name ): bool {
	return (bool) preg_match( '/\s' . preg_quote( $name, '/' ) . '(\s|=|>|\/)/i', $tag );
}

/**
 * The fields a browser submits from this form: inputs (only ticked boxes),
 * the chosen option of each select, and textareas.
 *
 * @param string $html Form markup.
 */
function qa059_browser_fields( string $html ): array {
	$pairs = array();
	preg_match_all( '/<input\b[^>]*>/i', $html, $inputs );
	foreach ( $inputs[0] as $input ) {
		$name = qa059_attr( $input, 'name' );
		$type = strtolower( qa059_attr( $input, 'type' ) );
		if ( '' === $name || in_array( $type, array( 'submit', 'button', 'file', 'image', 'reset' ), true ) ) {
			continue;
		}
		if ( in_array( $type, array( 'checkbox', 'radio' ), true ) ) {
			if ( ! qa059_has( $input, 'checked' ) ) {
				continue;
			}
			$value = qa059_has( $input, 'value' ) ? qa059_attr( $input, 'value' ) : 'on';
		} else {
			$value = qa059_attr( $input, 'value' );
		}
		$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( $value );
	}
	preg_match_all( '/<select\b([^>]*)>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER );
	foreach ( $selects as $select ) {
		$name = qa059_attr( '<select' . $select[1] . '>', 'name' );
		if ( '' === $name ) {
			continue;
		}
		preg_match_all( '/<option\b[^>]*>/i', $select[2], $options );
		$chosen = $options[0][0] ?? '';
		foreach ( $options[0] as $option ) {
			if ( qa059_has( $option, 'selected' ) ) {
				$chosen = $option;
				break;
			}
		}
		if ( '' !== $chosen ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( qa059_attr( $chosen, 'value' ) );
		}
	}
	preg_match_all( '/<textarea\b([^>]*)>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER );
	foreach ( $areas as $area ) {
		$name = qa059_attr( '<textarea' . $area[1] . '>', 'name' );
		if ( '' !== $name ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( html_entity_decode( $area[2], ENT_QUOTES, 'UTF-8' ) );
		}
	}
	$fields = array();
	wp_parse_str( implode( '&', $pairs ), $fields );
	return $fields;
}

/**
 * Open a screen as the current user: the fields its form would submit.
 *
 * @param string $screen 'rules' (Meeting rules) or 'net' (Net details).
 */
function qa059_open( string $screen ): array {
	\spokares_opt_flush();
	ob_start();
	if ( 'rules' === $screen ) {
		\spokares_meeting_rules_page();
	} else {
		\spokares_net_details_page();
	}
	$html = (string) ob_get_clean();
	if ( ! preg_match( '/<form\b.*?<\/form>/is', $html, $form ) ) {
		fail( $screen . ': the screen has no form for ' . wp_get_current_user()->user_login );
	}
	return qa059_browser_fields( $form[0] );
}

/**
 * The Meeting rules card index of a meeting in the posted fields.
 *
 * @param array  $fields Form fields.
 * @param string $id     Meeting id.
 */
function qa059_card( array $fields, string $id ): int {
	foreach ( (array) ( $fields['rules'] ?? array() ) as $i => $card ) {
		if ( is_array( $card ) && ( $card['id'] ?? '' ) === $id ) {
			return (int) $i;
		}
	}
	fail( 'Meeting rules has no card for ' . $id . ': ' . wp_json_encode( $fields ) );
	return -1;
}

/**
 * A stored meeting rule by id.
 *
 * @param string $id Meeting id.
 */
function qa059_meeting( string $id ): array {
	\spokares_opt_flush();
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}
	fail( 'no meeting ' . $id . ' in spk_meetings' );
	return array();
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa059_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Click Save on a screen with these fields, as the current user. Returns
 * the notices the save queued.
 *
 * @param string $screen 'rules' or 'net'.
 * @param array  $fields Form fields (with the editor's typing).
 */
function qa059_save( string $screen, array $fields ): array {
	$action = 'rules' === $screen ? 'spokares_save_meeting_rules' : 'spokares_save_net_details';
	$page   = 'rules' === $screen ? 'spokares-meeting-rules' : 'spokares-net-details';
	qa059_notices();
	unset( $fields['_wpnonce'], $fields['_wp_http_referer'] );
	$res = post_form( $action, $fields );
	assert_same( null, $res['die'], $action . ' did not wp_die()' );
	assert_contains( 'page=' . $page, (string) $res['redirect'], $action . ' redirects back to its screen' );
	\spokares_unlock_option( 'spk_meetings' );
	\spokares_opt_flush();
	return qa059_notices();
}

/**
 * Notices as one line for failure messages.
 *
 * @param array $notices Notices.
 */
function qa059_said( array $notices ): string {
	return implode( ' | ', array_map( static fn( $n ) => $n['type'] . ': ' . $n['text'], $notices ) );
}

/**
 * The save told the editor that something wasn't saved (an error or
 * warning, and no plain "Saved.").
 *
 * @param array  $notices Notices.
 * @param string $msg     Message prefix.
 */
function qa059_assert_told( array $notices, string $msg ): void {
	$types = wp_list_pluck( $notices, 'type' );
	assert_true( in_array( 'error', $types, true ) || in_array( 'warning', $types, true ), $msg . ': the editor is told the entry wasn’t saved (notices: ' . qa059_said( $notices ) . ')' );
	assert_not_contains( 'success', $types, $msg . ': no plain "Saved." (notices: ' . qa059_said( $notices ) . ')' );
}

test(
	'Meeting Schedule: a save from a screen opened before someone else\'s save keeps their Workshop times',
	function () {
		$before = qa059_meeting( 'workshop' );
		assert_same( array( '09:00', '12:00' ), array( $before['start'], $before['end'] ), 'the seeded Workshop is 09:00–12:00 (control)' );

		// B opens Meeting rules first.
		as_role( 'ares-net' );
		$b = qa059_open( 'rules' );

		// A opens it, changes the Workshop to 10:00–13:00 and saves.
		as_role( 'admin' );
		$a                         = qa059_open( 'rules' );
		$w                         = qa059_card( $a, 'workshop' );
		$a['rules'][ $w ]['start'] = '10:00';
		$a['rules'][ $w ]['end']   = '13:00';
		$said_a                    = qa059_save( 'rules', $a );
		assert_contains( 'success', wp_list_pluck( $said_a, 'type' ), 'A\'s save succeeds (control): ' . qa059_said( $said_a ) );
		$mid = qa059_meeting( 'workshop' );
		assert_same( array( '10:00', '13:00' ), array( $mid['start'], $mid['end'] ), 'A\'s Workshop times are stored (control)' );

		// B, still on the screen opened earlier, changes the Third Thursday time words and saves.
		as_role( 'ares-net' );
		$t                             = qa059_card( $b, 'third-thursday' );
		$b['rules'][ $t ]['time_text'] = 'early evenings';
		$said_b                        = qa059_save( 'rules', $b );

		$after = qa059_meeting( 'workshop' );
		assert_same(
			array( '10:00', '13:00' ),
			array( $after['start'], $after['end'] ),
			'B\'s save from the older screen silently put A\'s Workshop times back to ' . $after['start'] . '–' . $after['end'] . ' (B was told: ' . qa059_said( $said_b ) . ')'
		);
		if ( 'early evenings' !== qa059_meeting( 'third-thursday' )['time_text'] ) {
			qa059_assert_told( $said_b, 'B\'s Third Thursday change was not stored' );
		}
	}
);

test(
	'Meeting Schedule: the same Workshop time changed on an older screen does not overwrite the newer save, and the editor is told',
	function () {
		as_role( 'ares-net' );
		$b = qa059_open( 'rules' );

		as_role( 'admin' );
		$a                         = qa059_open( 'rules' );
		$w                         = qa059_card( $a, 'workshop' );
		$a['rules'][ $w ]['start'] = '10:00';
		$a['rules'][ $w ]['end']   = '13:00';
		qa059_save( 'rules', $a );

		as_role( 'ares-net' );
		$w                         = qa059_card( $b, 'workshop' );
		$b['rules'][ $w ]['start'] = '08:00';
		$said_b                    = qa059_save( 'rules', $b );

		$after = qa059_meeting( 'workshop' );
		assert_same(
			array( '10:00', '13:00' ),
			array( $after['start'], $after['end'] ),
			'B\'s start time from the older screen replaced A\'s newer Workshop times with ' . $after['start'] . '–' . $after['end'] . ' (B was told: ' . qa059_said( $said_b ) . ')'
		);
		qa059_assert_told( $said_b, 'B\'s conflicting Workshop time' );
	}
);

test(
	'Net details: a save from a screen opened before someone else\'s save keeps their Tone',
	function () {
		\spokares_opt_flush();
		assert_same( '100 Hz', \spokares_opt( 'spk_radio' )['primary']['tone'], 'the seeded Tone is 100 Hz (control)' );

		// B opens Net details first.
		as_role( 'admin' );
		$b = qa059_open( 'net' );

		// A opens it, changes the Tone and saves.
		as_role( 'ares-net' );
		$a                             = qa059_open( 'net' );
		$a['radio']['primary']['tone'] = '103.5 Hz';
		$said_a                        = qa059_save( 'net', $a );
		assert_contains( 'success', wp_list_pluck( $said_a, 'type' ), 'A\'s save succeeds (control): ' . qa059_said( $said_a ) );
		assert_same( '103.5 Hz', \spokares_opt( 'spk_radio' )['primary']['tone'], 'A\'s Tone is stored (control)' );

		// B, still on the screen opened earlier, changes the open-slot line and saves.
		as_role( 'admin' );
		$b['nets']['open_slot_line'] = 'Open slot? Say so on the Tuesday net.';
		$said_b                      = qa059_save( 'net', $b );

		assert_same(
			'103.5 Hz',
			\spokares_opt( 'spk_radio' )['primary']['tone'],
			'B\'s save from the older screen silently put A\'s Tone back (B was told: ' . qa059_said( $said_b ) . ')'
		);
		if ( 'Open slot? Say so on the Tuesday net.' !== \spokares_opt( 'spk_nets' )['open_slot_line'] ) {
			qa059_assert_told( $said_b, 'B\'s open-slot line was not stored' );
		}
	}
);

test(
	'Net details: the same sentence changed on an older screen does not overwrite the newer save, and the editor is told',
	function () {
		as_role( 'ares-net' );
		$b = qa059_open( 'net' );

		as_role( 'admin' );
		$a                           = qa059_open( 'net' );
		$a['nets']['open_slot_line'] = 'Open slot? Tell the Net Manager on the net.';
		qa059_save( 'net', $a );

		as_role( 'ares-net' );
		$b['nets']['open_slot_line'] = 'Open slot? Say so on the Tuesday net.';
		$said_b                      = qa059_save( 'net', $b );

		assert_same(
			'Open slot? Tell the Net Manager on the net.',
			\spokares_opt( 'spk_nets' )['open_slot_line'],
			'B\'s open-slot line from the older screen replaced A\'s newer one (B was told: ' . qa059_said( $said_b ) . ')'
		);
		qa059_assert_told( $said_b, 'B\'s conflicting open-slot line' );
	}
);

test(
	'a screen opened after someone else\'s save still saves normally on Meeting Schedule and Net Settings (control)',
	function () {
		as_role( 'admin' );
		$a                         = qa059_open( 'rules' );
		$w                         = qa059_card( $a, 'workshop' );
		$a['rules'][ $w ]['start'] = '10:00';
		$a['rules'][ $w ]['end']   = '13:00';
		qa059_save( 'rules', $a );
		$n                             = qa059_open( 'net' );
		$n['radio']['primary']['tone'] = '103.5 Hz';
		qa059_save( 'net', $n );

		as_role( 'ares-net' );
		$b                             = qa059_open( 'rules' );
		$t                             = qa059_card( $b, 'third-thursday' );
		$b['rules'][ $t ]['time_text'] = 'early evenings';
		$said                          = qa059_save( 'rules', $b );
		assert_contains( 'success', wp_list_pluck( $said, 'type' ), 'Meeting Schedule: "Saved." (' . qa059_said( $said ) . ')' );
		assert_same( 'early evenings', qa059_meeting( 'third-thursday' )['time_text'], 'Meeting Schedule: B\'s time words are stored' );
		assert_same( '10:00', qa059_meeting( 'workshop' )['start'], 'Meeting Schedule: A\'s Workshop start is kept' );

		$m                           = qa059_open( 'net' );
		$m['nets']['open_slot_line'] = 'Open slot? Say so on the Tuesday net.';
		$said                        = qa059_save( 'net', $m );
		assert_contains( 'success', wp_list_pluck( $said, 'type' ), 'Net details: "Saved." (' . qa059_said( $said ) . ')' );
		assert_same( 'Open slot? Say so on the Tuesday net.', \spokares_opt( 'spk_nets' )['open_slot_line'], 'Net details: B\'s open-slot line is stored' );
		assert_same( '103.5 Hz', \spokares_opt( 'spk_radio' )['primary']['tone'], 'Net details: A\'s Tone is kept' );
	}
);
