<?php
/**
 * Regression tests for QA-058 (PLAN §3.4 Meeting rules, §4.4 guard rails:
 * "Dates and times | events, meetings | block | ... end time after start
 * time"; on a settings screen the refused field is not saved, keeps its
 * stored value, and comes back outlined with one sentence).
 *
 * Meeting rules only compared the End time with the Start time when both
 * were filled in. Clearing Start on the Second Saturday Workshop and
 * keeping End 11:00 was saved with "Saved. See it on Home": the rule was
 * stored as start '' / end '11:00', and because Home formats a time range
 * from the start time and the hub prints only the start time, the meeting's
 * time vanished from both without a word. Home read "Second Saturday
 * Workshop, then a Winlink workshop, 12:30–3:30 PM" and the hub "Sat,
 * Oct 10, Second Saturday Workshop". Events › Add event refuses the same
 * input (an End time with no Start, QA-052).
 *
 * Each save posts exactly what the browser sends from the Meeting rules
 * screen as it is drawn for the user (every input, ticked box and chosen
 * option of the rendered form), with the editor's typing applied.
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
function qa058_attr( string $tag, string $name ): string {
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
function qa058_has( string $tag, string $name ): bool {
	return (bool) preg_match( '/\s' . preg_quote( $name, '/' ) . '(\s|=|>|\/)/i', $tag );
}

/**
 * The Meeting rules screen as the current user sees it.
 */
function qa058_screen(): string {
	\spokares_opt_flush();
	ob_start();
	\spokares_meeting_rules_page();
	$html = (string) ob_get_clean();
	if ( ! preg_match( '/<form\b.*?<\/form>/is', $html, $form ) ) {
		fail( 'Meeting rules has no form for ' . wp_get_current_user()->user_login );
	}
	return $form[0];
}

/**
 * The fields a browser submits from this form: inputs (only ticked boxes)
 * and the chosen option of each select.
 *
 * @param string $html Form markup.
 */
function qa058_browser_fields( string $html ): array {
	$pairs = array();
	preg_match_all( '/<input\b[^>]*>/i', $html, $inputs );
	foreach ( $inputs[0] as $input ) {
		$name = qa058_attr( $input, 'name' );
		$type = strtolower( qa058_attr( $input, 'type' ) );
		if ( '' === $name || in_array( $type, array( 'submit', 'button', 'file', 'image', 'reset' ), true ) ) {
			continue;
		}
		if ( in_array( $type, array( 'checkbox', 'radio' ), true ) ) {
			if ( ! qa058_has( $input, 'checked' ) ) {
				continue;
			}
			$value = qa058_has( $input, 'value' ) ? qa058_attr( $input, 'value' ) : 'on';
		} else {
			$value = qa058_attr( $input, 'value' );
		}
		$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( $value );
	}
	preg_match_all( '/<select\b([^>]*)>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER );
	foreach ( $selects as $select ) {
		$name = qa058_attr( '<select' . $select[1] . '>', 'name' );
		if ( '' === $name ) {
			continue;
		}
		preg_match_all( '/<option\b[^>]*>/i', $select[2], $options );
		$chosen = $options[0][0] ?? '';
		foreach ( $options[0] as $option ) {
			if ( qa058_has( $option, 'selected' ) ) {
				$chosen = $option;
				break;
			}
		}
		if ( '' !== $chosen ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( qa058_attr( $chosen, 'value' ) );
		}
	}
	$fields = array();
	wp_parse_str( implode( '&', $pairs ), $fields );
	return $fields;
}

/**
 * The card index of a meeting in the posted fields ('' = the "Add a
 * meeting" card).
 *
 * @param array  $fields Form fields.
 * @param string $id     Meeting id.
 */
function qa058_card( array $fields, string $id ): int {
	foreach ( (array) ( $fields['rules'] ?? array() ) as $i => $card ) {
		if ( is_array( $card ) && ( $card['id'] ?? '' ) === $id ) {
			return (int) $i;
		}
	}
	fail( 'Meeting rules has no card for "' . $id . '": ' . wp_json_encode( $fields ) );
	return -1;
}

/**
 * A stored meeting rule by id (null when there is none).
 *
 * @param string $id Meeting id.
 */
function qa058_meeting( string $id ): ?array {
	\spokares_opt_flush();
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}
	return null;
}

/**
 * A stored meeting rule by name (null when there is none).
 *
 * @param string $name Meeting name.
 */
function qa058_meeting_named( string $name ): ?array {
	\spokares_opt_flush();
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['name'] === $name ) {
			return $m;
		}
	}
	return null;
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa058_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Notices as one line for failure messages.
 *
 * @param array $notices Notices.
 */
function qa058_said( array $notices ): string {
	return implode( ' | ', array_map( static fn( $n ) => $n['type'] . ': ' . $n['text'], $notices ) );
}

/**
 * Click "Save meeting rules" with these fields, as the current user.
 * Returns the notices the save queued.
 *
 * @param array $fields Form fields (with the editor's typing).
 */
function qa058_save( array $fields ): array {
	qa058_notices();
	unset( $fields['_wpnonce'], $fields['_wp_http_referer'] );
	$res = post_form( 'spokares_save_meeting_rules', $fields );
	assert_same( null, $res['die'], 'the save did not wp_die()' );
	assert_contains( 'page=spokares-meeting-rules', (string) $res['redirect'], 'the save redirects back to Meeting Schedule' );
	\spokares_unlock_option( 'spk_meetings' );
	\spokares_opt_flush();
	return qa058_notices();
}

/**
 * The card of the redrawn screen for a meeting: its fieldset markup, the
 * Start and End boxes and the sentences in it.
 *
 * @param string $html  Form markup (qa058_screen()).
 * @param int    $index Card index.
 */
function qa058_redrawn_card( string $html, int $index ): array {
	// Each card runs from its <fieldset class="spk-card spk-rule…"> to the next one (cards hold a nested fieldset).
	$cards = preg_split( '/(?=<fieldset\b[^>]*\bspk-rule\b)/i', $html );
	$card  = '';
	foreach ( array_slice( (array) $cards, 1 ) as $c ) {
		if ( str_contains( $c, 'name="rules[' . $index . '][id]"' ) ) {
			$card = $c;
			break;
		}
	}
	if ( '' === $card ) {
		fail( 'the redrawn Meeting rules screen has no card ' . $index . ': ' . $html );
	}
	$find = static function ( string $field ) use ( $card, $index ): string {
		return preg_match( '/<input\b[^>]*\bname="rules\[' . $index . '\]\[' . $field . '\]"[^>]*>/i', $card, $m ) ? $m[0] : '';
	};
	preg_match( '/<fieldset\b[^>]*>/i', $card, $open );
	preg_match_all( '/<span class="spk-error-text">(.*?)<\/span>/s', $card, $sentences );
	$start = $find( 'start' );
	$end   = $find( 'end' );
	return array(
		'start'     => qa058_attr( $start, 'value' ),
		'end'       => qa058_attr( $end, 'value' ),
		'outlined'  => str_contains( qa058_attr( $open[0] ?? '', 'class' ), 'spk-row-error' )
			|| str_contains( qa058_attr( $start, 'class' ), 'spk-field-error' )
			|| str_contains( qa058_attr( $end, 'class' ), 'spk-field-error' ),
		'sentences' => array_map( static fn( $e ) => html_entity_decode( wp_strip_all_tags( $e ), ENT_QUOTES, 'UTF-8' ), $sentences[1] ),
	);
}

test(
	'Meeting Schedule: an End time with no Start is not saved, the stored times stay, and the typed End comes back outlined with a sentence',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$before = qa058_meeting( 'workshop' );
			assert_same( '09:00', (string) ( $before['start'] ?? '' ), $role . ': the seeded workshop starts at 09:00 (control)' );
			assert_same( '12:00', (string) ( $before['end'] ?? '' ), $role . ': the seeded workshop ends at 12:00 (control)' );

			// Clear Start, keep End as 11:00; in the same save change the Third Thursday time words.
			$fields = qa058_browser_fields( qa058_screen() );
			$ws     = qa058_card( $fields, 'workshop' );
			$tt     = qa058_card( $fields, 'third-thursday' );

			$fields['rules'][ $ws ]['start']     = '';
			$fields['rules'][ $ws ]['end']       = '11:00';
			$fields['rules'][ $tt ]['time_text'] = 'Thursday evenings';

			$notices = qa058_save( $fields );
			$said    = qa058_said( $notices );

			$after = qa058_meeting( 'workshop' );
			assert_same(
				array( '09:00', '12:00' ),
				array( (string) ( $after['start'] ?? '' ), (string) ( $after['end'] ?? '' ) ),
				$role . ': the workshop was stored as start "' . ( $after['start'] ?? '' ) . '" / end "' . ( $after['end'] ?? '' ) . '" although an End time with no Start is refused by PLAN §4.4 (notices: ' . $said . ')'
			);
			assert_not_contains( 'success', wp_list_pluck( $notices, 'type' ), $role . ': a plain "Saved." notice for a save that should have been held back (' . $said . ')' );
			// One notice: "Saved, except Second Saturday Workshop (outlined in red)." (a warning when
			// the other card's change is saved), or "Not saved: …" (an error) when nothing else changed.
			$errors = array_values( wp_list_pluck( array_filter( $notices, static fn( $n ) => in_array( $n['type'], array( 'error', 'warning' ), true ) ), 'text' ) );
			assert_count( 1, $notices, $role . ': one notice for the save (' . $said . ')' );
			assert_true( count( $errors ) > 0, $role . ': no error or warning notice for the End time with no Start (' . $said . ')' );
			assert_matches( '/^(Saved, except|Not saved:) Second Saturday Workshop \(outlined in red\)\./', implode( ' ', $errors ), $role . ': the notice names the meeting (' . $said . ')' );

			// Every other field is saved (§4.4 settings screens).
			assert_same( 'Thursday evenings', (string) ( qa058_meeting( 'third-thursday' )['time_text'] ?? '' ), $role . ': the other meeting\'s change in the same save is saved' );

			// The form comes back with what was typed, outlined, with a sentence.
			$card = qa058_redrawn_card( qa058_screen(), $ws );
			assert_same( '', $card['start'], $role . ': the Start box comes back as typed (empty)' );
			assert_same( '11:00', $card['end'], $role . ': the End box comes back with the typed 11:00' );
			assert_true( $card['outlined'], $role . ': the workshop card or its time boxes are outlined in red' );
			assert_true( count( $card['sentences'] ) > 0, $role . ': the workshop card says what is wrong' );
			assert_matches( '/\bstart/i', implode( ' ', $card['sentences'] ), $role . ': the sentence is about the missing Start time' );
		}
	}
);

test(
	'Meeting Schedule: after an End time with no Start is refused, Home and the hub still show the meeting time',
	function () {
		as_role( 'ares-net' );
		$fields = qa058_browser_fields( qa058_screen() );
		$ws     = qa058_card( $fields, 'workshop' );

		$fields['rules'][ $ws ]['start'] = '';
		$fields['rules'][ $ws ]['end']   = '11:00';
		$said                            = qa058_said( qa058_save( $fields ) );

		$m = qa058_meeting( 'workshop' );
		assert_true( null !== $m, 'the workshop is still a meeting' );
		$home = \spokares_meeting_home_line( $m );
		assert_matches( '/9:00/', $home, 'Home lost the meeting time: "' . $home . '" (notices: ' . $said . ')' );

		$next = \spokares_next_meetings( 1 );
		$date = '';
		foreach ( $next as $item ) {
			if ( 'workshop' === $item['meeting']['id'] ) {
				$date = (string) ( $item['dates'][0]['date'] ?? '' );
			}
		}
		assert_true( '' !== $date, 'the workshop has a next date (control)' );
		$hub = \spokares_meeting_hub_line( $m, $date );
		assert_matches( '/9:00/', $hub, 'the hub lost the meeting time: "' . $hub . '"' );
	}
);

test(
	'Meeting Schedule: a new meeting with an End time and no Start is not added',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$fields = qa058_browser_fields( qa058_screen() );
			$new    = qa058_card( $fields, '' );

			$fields['rules'][ $new ]['name']  = 'QA058 evening practice';
			$fields['rules'][ $new ]['nth']   = array( '1' );
			$fields['rules'][ $new ]['start'] = '';
			$fields['rules'][ $new ]['end']   = '20:00';

			$notices = qa058_save( $fields );
			$said    = qa058_said( $notices );
			$added   = qa058_meeting_named( 'QA058 evening practice' );
			assert_true( null === $added, $role . ': the new meeting was added with an End time and no Start: ' . wp_json_encode( $added ) . ' (notices: ' . $said . ')' );
			assert_not_contains( 'success', wp_list_pluck( $notices, 'type' ), $role . ': a plain "Saved." notice (' . $said . ')' );

			$card = qa058_redrawn_card( qa058_screen(), $new );
			assert_same( '20:00', $card['end'], $role . ': the typed End comes back in the Add a meeting card' );
			assert_true( $card['outlined'], $role . ': the Add a meeting card is outlined' );
		}
	}
);

test(
	'Meeting Schedule: a Start with no End, and a Start before End, are still saved (control)',
	function () {
		as_role( 'ares-net' );
		$fields = qa058_browser_fields( qa058_screen() );
		$ws     = qa058_card( $fields, 'workshop' );

		$fields['rules'][ $ws ]['start'] = '10:00';
		$fields['rules'][ $ws ]['end']   = '';
		$notices                         = qa058_save( $fields );
		$m                               = qa058_meeting( 'workshop' );
		assert_same( array( '10:00', '' ), array( (string) $m['start'], (string) $m['end'] ), 'start only is stored (' . qa058_said( $notices ) . ')' );
		assert_contains( 'success', wp_list_pluck( $notices, 'type' ), 'a "Saved." notice (' . qa058_said( $notices ) . ')' );

		$fields = qa058_browser_fields( qa058_screen() );

		$fields['rules'][ $ws ]['start'] = '10:00';
		$fields['rules'][ $ws ]['end']   = '13:00';
		$notices                         = qa058_save( $fields );
		$m                               = qa058_meeting( 'workshop' );
		assert_same( array( '10:00', '13:00' ), array( (string) $m['start'], (string) $m['end'] ), 'start and end are stored (' . qa058_said( $notices ) . ')' );
		assert_contains( 'success', wp_list_pluck( $notices, 'type' ), 'a "Saved." notice (' . qa058_said( $notices ) . ')' );
	}
);

test(
	'Meeting Schedule: a meeting with time words and no times (Third Thursday) still saves unchanged (control)',
	function () {
		as_role( 'admin' );
		$notices = qa058_save( qa058_browser_fields( qa058_screen() ) );
		assert_not_contains( 'error', wp_list_pluck( $notices, 'type' ), 'an unchanged save is not refused (' . qa058_said( $notices ) . ')' );
		assert_same( array( 'Nothing changed, so nothing was saved.' ), array_values( wp_list_pluck( $notices, 'text' ) ), 'an unchanged save says so (' . qa058_said( $notices ) . ')' );
		$tt = qa058_meeting( 'third-thursday' );
		assert_same( array( '', '', 'evenings' ), array( (string) $tt['start'], (string) $tt['end'], (string) $tt['time_text'] ), 'Third Thursday keeps its time words and no times' );
	}
);

test(
	'Meeting Schedule: an End time before the Start is still refused (control)',
	function () {
		as_role( 'ares-net' );
		$fields = qa058_browser_fields( qa058_screen() );
		$ws     = qa058_card( $fields, 'workshop' );

		$fields['rules'][ $ws ]['start'] = '11:00';
		$fields['rules'][ $ws ]['end']   = '10:00';
		$notices                         = qa058_save( $fields );
		$m                               = qa058_meeting( 'workshop' );
		assert_same( array( '09:00', '12:00' ), array( (string) $m['start'], (string) $m['end'] ), 'the stored times stay (' . qa058_said( $notices ) . ')' );
		assert_not_contains( 'success', wp_list_pluck( $notices, 'type' ), 'no plain "Saved." notice' );

		$card = qa058_redrawn_card( qa058_screen(), $ws );
		assert_same( array( '11:00', '10:00' ), array( $card['start'], $card['end'] ), 'the typed times come back' );
		assert_true( $card['outlined'], 'the card is outlined' );
		assert_matches( '/\bstart/i', implode( ' ', $card['sentences'] ), 'the sentence mentions the start' );

		// The same for a new meeting in the Add a meeting card.
		$fields = qa058_browser_fields( qa058_screen() );
		$new    = qa058_card( $fields, '' );

		$fields['rules'][ $new ]['name']  = 'QA058 evening practice';
		$fields['rules'][ $new ]['nth']   = array( '1' );
		$fields['rules'][ $new ]['start'] = '20:00';
		$fields['rules'][ $new ]['end']   = '19:00';
		qa058_save( $fields );
		assert_true( null === qa058_meeting_named( 'QA058 evening practice' ), 'the new meeting is not added' );
		$card = qa058_redrawn_card( qa058_screen(), $new );
		assert_same( array( '20:00', '19:00' ), array( $card['start'], $card['end'] ), 'the typed times come back in the Add a meeting card' );
		assert_true( $card['outlined'], 'the Add a meeting card is outlined' );
	}
);
