<?php
/**
 * Tests for the Net Control Schedule and Net Settings screens after the
 * 2026-09-27 editor review (ux/SPEC.md §3.2, §3.3, §4.1, §4.2, §4.6, R7):
 * No net, the "ICS-213 (usual)" form choice, one notice per save, the
 * title line's Undo and Redo, the changed-row tint, every row drawn for
 * "Show 13 more Tuesdays", and Net Settings' week selects and
 * "(was …)" notice.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The notices queued for the current user (and clear them).
 */
function uxncs_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * The first of the next 13 Tuesdays that matches a test, as its row.
 *
 * @param callable $pick Row => bool.
 * @param int      $skip How many matches to pass over.
 */
function uxncs_row( callable $pick, int $skip = 0 ): array {
	foreach ( \spokares_rota_rows( 13 ) as $row ) {
		if ( $pick( $row ) && 0 === $skip-- ) {
			return $row;
		}
	}
	fail( 'no Tuesday in the next 13 fits the test' );
	return array();
}

/**
 * A row's fields as the screen posts them untouched (the Form select sends
 * '' for "ICS-213 (usual)"), with some replaced.
 *
 * @param string $date   Tuesday.
 * @param array  $change Fields to replace.
 */
function uxncs_fields( string $date, array $change = array() ): array {
	$row    = \spokares_net_on( $date );
	$stored = array(
		'state'   => $row['state'],
		'call'    => $row['call'],
		'note'    => $row['row_note'],
		'wl_task' => $row['wl_task'],
		'wl_form' => $row['wl_form'],
	);
	$posted = array_merge( $stored, array( 'h' => \spokares_rota_hash( $stored ) ) );
	if ( 'ICS-213' === $posted['wl_form'] ) {
		$posted['wl_form'] = '';
	}
	return array_merge( $posted, $change );
}

/**
 * Save the schedule with these rows as the current user; returns the notices.
 *
 * @param array $rows Date => fields.
 */
function uxncs_save( array $rows ): array {
	uxncs_notices();
	$res = post_form(
		'spokares_save_rota',
		array(
			'weeks' => '13',
			'rota'  => $rows,
		)
	);
	assert_same( null, $res['die'], 'the save was refused' );
	assert_contains( 'page=spokares-rota', (string) $res['redirect'], 'the save goes back to the screen' );
	\spokares_unlock_option( 'spk_rota' );
	\spokares_opt_flush();
	return uxncs_notices();
}

/**
 * The Net Control Schedule screen as the current user sees it.
 *
 * @param array $get Query args.
 */
function uxncs_screen( array $get = array() ): string {
	$res = call_request(
		'GET',
		array_merge( array( 'page' => 'spokares-rota' ), $get ),
		array(),
		static function () {
			\spokares_rota_page();
		}
	);
	assert_same( null, $res['die'], 'the screen did not wp_die()' );
	return (string) $res['output'];
}

/**
 * One row's <tr> … </tr> from the screen.
 *
 * @param string $html Screen HTML.
 * @param string $date Tuesday.
 */
function uxncs_tr( string $html, string $date ): string {
	if ( ! preg_match( '#<tr class="[^"]*" data-date="' . preg_quote( $date, '#' ) . '"[^>]*>.*?</tr>#s', $html, $m ) ) {
		fail( 'the screen has no row for ' . $date );
	}
	return $m[0];
}

/**
 * The text a person reads: tags dropped, entities decoded, white space as
 * one space.
 *
 * @param string $html HTML.
 */
function uxncs_plain( string $html ): string {
	$html = (string) preg_replace( '#<(script|style|datalist)\b.*?</\1>#s', ' ', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * The fields a browser submits from a form as drawn: inputs (ticked boxes
 * only), each select's chosen option, textareas.
 *
 * @param string $html Form markup.
 */
function uxncs_browser_fields( string $html ): array {
	$attr  = static function ( string $tag, string $name ): ?string {
		return preg_match( '/\s' . $name . '="([^"]*)"/', $tag, $m ) ? html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) : null;
	};
	$pairs = array();
	preg_match_all( '/<input\b[^>]*>/i', $html, $inputs );
	foreach ( $inputs[0] as $tag ) {
		$name = $attr( $tag, 'name' );
		$type = strtolower( (string) $attr( $tag, 'type' ) );
		if ( null === $name || in_array( $type, array( 'submit', 'button', 'file' ), true ) ) {
			continue;
		}
		if ( in_array( $type, array( 'checkbox', 'radio' ), true ) && ! preg_match( '/\schecked\b/', $tag ) ) {
			continue;
		}
		$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( (string) $attr( $tag, 'value' ) );
	}
	preg_match_all( '/<select\b([^>]*)>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER );
	foreach ( $selects as $select ) {
		preg_match_all( '/<option\b[^>]*>/i', $select[2], $options );
		$chosen = $options[0][0] ?? '';
		foreach ( $options[0] as $option ) {
			if ( preg_match( '/\sselected\b/', $option ) ) {
				$chosen = $option;
			}
		}
		$pairs[] = rawurlencode( (string) $attr( '<select' . $select[1] . '>', 'name' ) ) . '=' . rawurlencode( (string) $attr( $chosen, 'value' ) );
	}
	preg_match_all( '/<textarea\b([^>]*)>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER );
	foreach ( $areas as $area ) {
		$pairs[] = rawurlencode( (string) $attr( '<textarea' . $area[1] . '>', 'name' ) ) . '=' . rawurlencode( html_entity_decode( $area[2], ENT_QUOTES, 'UTF-8' ) );
	}
	$fields = array();
	wp_parse_str( implode( '&', $pairs ), $fields );
	unset( $fields['_wpnonce'], $fields['_wp_http_referer'], $fields['action'] );
	return $fields;
}

/**
 * The Net Settings screen as the current user sees it.
 */
function uxncs_net_screen(): string {
	\spokares_opt_flush();
	ob_start();
	\spokares_net_details_page();
	return (string) ob_get_clean();
}

/**
 * Open Net Settings, change some fields as a person would, and Save.
 * Returns the notices.
 *
 * @param callable $change Form fields => changed form fields.
 */
function uxncs_net_save( callable $change ): array {
	if ( ! preg_match( '/<form\b.*?<\/form>/is', uxncs_net_screen(), $form ) ) {
		fail( 'Net Settings has no form' );
	}
	uxncs_notices();
	$res = post_form( 'spokares_save_net_details', $change( uxncs_browser_fields( $form[0] ) ) );
	assert_same( null, $res['die'], 'the Net Settings save was refused' );
	\spokares_opt_flush();
	return uxncs_notices();
}

/* -------------------------------------------------- Net Control Schedule */

test(
	'the Net Control Schedule screen: its title, five columns and four Net control choices, with no word "rota" on it',
	function () {
		as_role( 'ares-net' );
		$html = uxncs_screen();
		assert_matches( '#<h1[^>]*>Net Control Schedule</h1>#', $html, 'the title' );
		preg_match_all( '#<th scope="col">(.*?)</th>#', $html, $cols );
		assert_same( array( 'Tuesday', 'Net control', 'Winlink assignment', 'Form', 'Note' ), $cols[1], 'the columns (no Shows column)' );
		$row = uxncs_tr( $html, \spokares_rota_rows( 1 )[0]['date'] );
		preg_match_all( '#<input type="radio"[^>]*value="([a-z]+)"#', $row, $values );
		assert_same( array( 'call', 'open', 'none', 'tbd' ), $values[1], 'the Net control choices, in order' );
		assert_matches( '#<span class="screen-reader-text">Call sign</span></label>\s*<input type="text" class="spk-call[^"]*"[^>]*placeholder="Call sign"#', $row, 'the first choice has no visible word; its box follows, with the placeholder "Call sign"' );
		foreach ( array( 'Volunteer needed', 'No net', 'Not posted yet' ) as $label ) {
			assert_contains( '> ' . $label . '</label>', $row, 'the choice "' . $label . '"' );
		}
		assert_not_contains( 'spk-rota-status', $html, 'the Shows column\'s status line is gone' );
		assert_matches( '#<form [^>]*id="spk-rota-form"[^>]*data-spk-guard#', $html, 'the form warns before you leave with unsaved changes' );
		assert_same( 1, preg_match_all( '#<button type="submit" class="button button-primary[^"]*">Save</button>#', $html ), 'one primary Save button' );
		assert_not_contains( 'Undo last save', $html, 'no Undo button beside Save' );
		assert_false( (bool) preg_match( '/\brota\b/i', uxncs_plain( $html ) ), 'the word "rota" is on the screen: ' . uxncs_plain( $html ) );
		assert_not_contains( 'Repeater and net times: ask the webmaster.', $html, 'the grant holder is told to ask the webmaster' );

		as_role( 'ares-editor' );
		assert_contains( 'Repeater and net times: ask the webmaster.', uxncs_screen(), 'a plain editor is told whom to ask' );
	}
);

test(
	'No net: a Tuesday saved as No net is kept even with nothing else in it, counts as posted, and Not posted yet takes it away again',
	function () {
		as_role( 'ares-editor' );
		$row  = uxncs_row( static fn( $r ) => 'tbd' === $r['state'] && '' === $r['wl_task'] && ! $r['stored'] );
		$date = $row['date'];
		$said = uxncs_save( array( $date => uxncs_fields( $date, array( 'state' => 'none' ) ) ) );
		assert_same( 'none', \spokares_opt( 'spk_rota' )[ $date ]['state'] ?? null, 'the No net row is stored' );
		assert_same( 'none', \spokares_net_on( $date )['state'], 'the schedule reads it as No net' );
		assert_count( 1, $said, 'one notice' );
		assert_same( 'success', $said[0]['type'], 'the notice type' );
		assert_contains( 'Saved ' . \spokares_fmt_date( $date, 'day' ) . '.', $said[0]['text'], 'the notice names the Tuesday' );
		assert_same( 'See it on the For members page', $said[0]['label'], 'the notice\'s link' );
		$summary = \spokares_rota_summary();
		assert_true( $summary['through'] >= $date, 'a No net Tuesday counts as posted (through ' . $summary['through'] . ')' );
		assert_not_contains( $date, $summary['tbd'], 'the No net Tuesday is listed as Not posted yet' );
		assert_not_contains( $date, $summary['open'], 'the No net Tuesday is listed as Volunteer needed' );

		$said = uxncs_save( array( $date => uxncs_fields( $date, array( 'state' => 'tbd' ) ) ) );
		assert_false( isset( \spokares_opt( 'spk_rota' )[ $date ] ), 'an empty Not posted yet row is not kept' );
		assert_same( 'success', $said[0]['type'] ?? '', 'saving it back to Not posted yet' );
	}
);

test(
	'the Form select: a stored ICS-213 shows as "ICS-213 (usual)", and an untouched save leaves the row as it is',
	function () {
		as_role( 'ares-editor' );
		$row  = uxncs_row( static fn( $r ) => 'ICS-213' === $r['wl_form'] && '' !== $r['wl_task'] );
		$date = $row['date'];
		$tr   = uxncs_tr( uxncs_screen(), $date );
		assert_matches( '#<option value=""\s+selected=[\'"]selected[\'"]\s*>ICS-213 \(usual\)</option>#', $tr, 'the blank choice reads "ICS-213 (usual)" and is chosen' );
		assert_not_contains( '<option value="ICS-213"', $tr, 'a second "ICS-213" choice' );
		assert_contains( '<textarea class="spk-wl-task', $tr, 'the Winlink assignment is a two-line box' );

		$said = uxncs_save( array( $date => uxncs_fields( $date ) ) );
		assert_same( array( 'Nothing changed, so nothing was saved.' ), wp_list_pluck( $said, 'text' ), 'an untouched ICS-213 row' );
		assert_same( 'ICS-213', \spokares_opt( 'spk_rota' )[ $date ]['wl_form'], 'the stored form' );

		uxncs_save( array( $date => uxncs_fields( $date, array( 'wl_task' => 'Your go-kit list' ) ) ) );
		assert_same( 'Your go-kit list', \spokares_opt( 'spk_rota' )[ $date ]['wl_task'], 'the new assignment' );
		assert_same( 'ICS-213', \spokares_opt( 'spk_rota' )[ $date ]['wl_form'], 'the stored form after a change to the assignment' );
	}
);

test(
	'one notice per save: a Tuesday saved with its name cut, one not saved, or nothing changed',
	function () {
		as_role( 'ares-editor' );
		$open  = uxncs_row( static fn( $r ) => 'open' === $r['state'] )['date'];
		$other = uxncs_row( static fn( $r ) => 'call' === $r['state'] && '' === $r['wl_task'] && \spokares_rota_rows( 1 )[0]['date'] !== $r['date'] )['date'];
		$said  = uxncs_save(
			array(
				$open  => uxncs_fields(
					$open,
					array(
						'state' => 'call',
						'call'  => 'Tom K7ABC',
					)
				),
				$other => uxncs_fields( $other, array( 'call' => 'Frank' ) ),
			)
		);
		assert_count( 1, $said, 'one notice: ' . wp_json_encode( $said ) );
		assert_same( 'warning', $said[0]['type'], 'some saved, some not' );
		assert_same(
			'Saved ' . \spokares_fmt_date( $open, 'day' ) . ': K7ABC (names aren’t posted). Not saved: ' . \spokares_fmt_date( $other, 'day' ) . ' (outlined in red).',
			$said[0]['text'],
			'the notice'
		);
		assert_same( 'K7ABC', \spokares_opt( 'spk_rota' )[ $open ]['call'], 'the call sign without the name' );
		$retained = \spokares_retained( 'spokares-rota' );
		assert_same( 'Type a call sign, like NZ2S, not a name.', $retained['errors'][ "$other-call" ] ?? '', 'the line under the box' );
		assert_same( 'Frank', $retained['values'][ $other ]['call'] ?? '', 'the typing is kept for the form' );

		$said = uxncs_save( array( $other => uxncs_fields( $other, array( 'call' => 'Frank' ) ) ) );
		assert_same( array( 'error' ), wp_list_pluck( $said, 'type' ), 'none saved' );
		assert_same( 'Not saved: ' . \spokares_fmt_date( $other, 'day' ) . ' (outlined in red).', $said[0]['text'], 'the notice' );
		\spokares_retained( 'spokares-rota' );

		$said = uxncs_save( array( $other => uxncs_fields( $other ) ) );
		assert_same( array( 'info' ), wp_list_pluck( $said, 'type' ), 'nothing changed' );
		assert_same( 'Nothing changed, so nothing was saved.', $said[0]['text'], 'the notice' );
	}
);

test(
	'a save of more than three Tuesdays names them as a range and says the For members page lists five',
	function () {
		as_role( 'ares-editor' );
		$rows = array();
		foreach ( array_slice( \spokares_rota_rows( 13 ), 5 ) as $row ) {
			if ( 'tbd' === $row['state'] && count( $rows ) < 4 ) {
				$rows[ $row['date'] ] = uxncs_fields( $row['date'], array( 'state' => 'open' ) );
			}
		}
		assert_count( 4, $rows, 'set-up: four Not posted yet Tuesdays past the fifth' );
		$dates = array_keys( $rows );
		$said  = uxncs_save( $rows );
		assert_same(
			array( 'Saved 4 Tuesdays, ' . \spokares_fmt_date( $dates[0], 'day' ) . ' – ' . \spokares_fmt_date( $dates[3], 'day' ) . '. The For members page lists the next five Tuesdays.' ),
			wp_list_pluck( $said, 'text' ),
			'the notice'
		);
	}
);

test(
	'Undo and Redo: the title line names the Tuesdays; each notice says what it did; the changed rows are tinted once',
	function () {
		as_role( 'ares-editor' );
		$date = uxncs_row( static fn( $r ) => 'open' === $r['state'] )['date'];
		$day  = \spokares_fmt_date( $date, 'day' );
		uxncs_save(
			array(
				$date => uxncs_fields(
					$date,
					array(
						'state' => 'call',
						'call'  => 'K7ABC',
					)
				),
			)
		);
		$html = uxncs_screen();
		assert_matches( '#<span class="spk-saved">\s*Last saved [^<]+ \(' . preg_quote( $day, '#' ) . '\) · <button type="submit" form="spk-rota-undo" class="button-link spk-undo">Undo</button>\s*</span>#', $html, 'the title line after a save' );
		assert_contains( 'spk-row-changed', uxncs_tr( $html, $date ), 'the saved row is tinted' );
		assert_not_contains( 'spk-row-changed', uxncs_screen(), 'the tint lasts one page view' );

		uxncs_notices();
		post_form( 'spokares_undo_rota', array( 'weeks' => '13' ) );
		\spokares_unlock_option( 'spk_rota' );
		\spokares_opt_flush();
		$said = uxncs_notices();
		assert_same( array( 'Undone: ' . $day . ' is back as it was.' ), wp_list_pluck( $said, 'text' ), 'the Undo notice' );
		assert_same( 'open', \spokares_opt( 'spk_rota' )[ $date ]['state'], 'the Tuesday is back as it was' );
		$html = uxncs_screen();
		assert_matches( '#<span class="spk-saved">\s*Undone [^<]+ \(' . preg_quote( $day, '#' ) . '\) · <button[^>]*>Redo</button>\s*</span>#', $html, 'the title line after an undo' );
		assert_contains( 'spk-row-changed', uxncs_tr( $html, $date ), 'the undone row is tinted' );

		post_form( 'spokares_undo_rota', array( 'weeks' => '13' ) );
		\spokares_unlock_option( 'spk_rota' );
		\spokares_opt_flush();
		$said = uxncs_notices();
		assert_same( array( 'Redone: ' . $day . ' is back as it was saved.' ), wp_list_pluck( $said, 'text' ), 'the Redo notice' );
		assert_same( 'K7ABC', \spokares_opt( 'spk_rota' )[ $date ]['call'], 'the saved call sign is back' );
		assert_matches( '#<span class="spk-saved">\s*Last saved [^<]+ · <button[^>]*>Undo</button>\s*</span>#', uxncs_screen(), 'the title line after a redo' );
	}
);

test(
	'a Tuesday past the rows shown with a problem is never hidden',
	function () {
		as_role( 'ares-editor' );
		$row  = \spokares_rota_rows( 20 )[16];
		$date = $row['date'];
		uxncs_save(
			array(
				$date => uxncs_fields(
					$date,
					array(
						'state' => 'call',
						'call'  => 'Frank',
					)
				),
			)
		);
		$html = uxncs_screen();
		assert_false( (bool) preg_match( '/\shidden[\s>]/', (string) strstr( uxncs_tr( $html, $date ), '>', true ) ), 'the row with the problem is hidden' );
		assert_matches( '#<input type="hidden" name="weeks" value="26">#', $html, 'the rows up to it show, in steps of 13' );
		assert_matches( '#<form [^>]*id="spk-rota-form"[^>]*data-spk-dirty#', $html, 'the form counts the kept typing as unsaved' );
	}
);

/* ----------------------------------------------------------- Net Settings */

test(
	'the Net Settings screen: four sections, "Members see" lines, a plain frequency box, and no banner, preview box or confirm',
	function () {
		as_role( 'ares-net' );
		$html = uxncs_net_screen();
		assert_matches( '#<h1[^>]*>Net Settings</h1>#', $html, 'the title' );
		preg_match_all( '#<h2>(.*?)</h2>#', $html, $h2 );
		assert_same( array( 'The Tuesday net', 'Alternate repeater', 'Which Tuesdays', 'Wording on the site' ), $h2[1], 'the sections' );
		assert_not_contains( 'spk-banner', $html, 'the red banner' );
		assert_not_contains( 'spk-net-preview', $html, 'the preview box' );
		assert_not_contains( 'data-spk-confirm', $html, 'the confirm dialog' );
		assert_matches( '#<form [^>]*id="spk-net-form"[^>]*data-spk-guard#', $html, 'the leave guard' );
		assert_matches( '#<input type="text" id="spk-p-freq"(?![^>]*\bpattern=)[^>]*inputmode="decimal"#', $html, 'the frequency box: decimal keyboard, no pattern' );
		assert_contains( 'Frequency (MHz) (required)', $html, 'the frequency label' );
		assert_contains( 'Net time (required)', $html, 'the net time label' );
		assert_same( 2 + 1, substr_count( $html, '>Members see</th>' ), 'a "Members see" line for the net, the alternate and the other nets' );
		assert_matches( '#data-preview="bar">8:00 PM · W7GBU 147\.300 MHz, \+600 kHz, 100 Hz<#', $html, 'members see the net line' );
		foreach ( array( 'Regular net', 'Winlink night', 'Starts on simplex', 'GMRS net' ) as $kind ) {
			assert_same( 5, substr_count( $html, '>' . $kind . '</option>' ), 'each week offers "' . $kind . '"' );
		}
		assert_not_contains( 'A week ticked twice', $html, 'the ticked-twice rule' );
		assert_contains( 'Shown under the Net Control Schedule on the For members page; the “Volunteer needed” tags link to it.', $html, 'the Asking for volunteers hint' );
		assert_same( 1, preg_match_all( '#<button type="submit" class="button button-primary[^"]*">Save</button>#', $html ), 'one Save button' );
	}
);

test(
	'Net Settings: one select per week sets one kind of net, and the notice says what changed with the old value',
	function () {
		as_role( 'ares-net' );
		$was  = \spokares_net_week_kinds( \spokares_opt( 'spk_nets' ) );
		$said = uxncs_net_save(
			static function ( array $f ): array {
				$f['nets']['week']['1']        = 'winlink';
				$f['nets']['week']['3']        = 'regular';
				$f['nets']['net_time']         = '19:30';
				$f['radio']['primary']['tone'] = '103.5 Hz';
				return $f;
			}
		);
		assert_same( 'regular', $was[1], 'set-up: the 1st Tuesday is a regular net' );
		assert_same( 'gmrs', $was[3], 'set-up: the 3rd Tuesday is the GMRS net' );
		$nets = \spokares_opt( 'spk_nets' );
		assert_same( array( 1, 2, 4 ), $nets['winlink_nth'], 'the Winlink nights' );
		assert_same( array(), $nets['gmrs_nth'], 'the GMRS weeks' );
		assert_same( array( 5 ), $nets['simplex_nth'], 'the simplex weeks' );
		assert_count( 1, $said, 'one notice' );
		assert_same( 'success', $said[0]['type'], 'the notice type' );
		assert_same( 'Saved: net time 7:30 PM (was 8:00 PM); tone 103.5 Hz (was 100 Hz); 1st Tuesday Winlink night (was regular net); 3rd Tuesday regular net (was GMRS net).', str_replace( "\u{00A0}", ' ', $said[0]['text'] ), 'the notice' );
		assert_same( 'See it on the How it works page', $said[0]['label'], 'the link' );
		$stamp = get_option( 'spk_net_saved' );
		assert_same( get_current_user_id(), (int) ( $stamp['by'] ?? 0 ), 'the Last saved stamp' );
		assert_contains( 'Last saved ', uxncs_net_screen(), 'the title line' );
	}
);

test(
	'Net Settings: nothing changed, partly saved and not saved each give one notice, and nothing changed writes no stamp',
	function () {
		as_role( 'ares-net' );
		delete_option( 'spk_net_saved' );
		$said = uxncs_net_save( static fn( array $f ): array => $f );
		assert_same( array( 'Nothing changed, so nothing was saved.' ), wp_list_pluck( $said, 'text' ), 'an untouched save' );
		assert_false( get_option( 'spk_net_saved' ), 'an untouched save wrote a Last saved stamp' );

		$said = uxncs_net_save(
			static function ( array $f ): array {
				$f['radio']['primary']['freq'] = '155.340';
				$f['radio']['primary']['tone'] = '103.5 Hz';
				return $f;
			}
		);
		assert_same( array( 'warning' ), wp_list_pluck( $said, 'type' ), 'partly saved' );
		assert_same( 'Saved, except the frequency (outlined in red).', $said[0]['text'], 'the notice' );
		assert_same( '103.5 Hz', \spokares_opt( 'spk_radio' )['primary']['tone'], 'the tone is saved' );
		assert_same( '147.300', \spokares_opt( 'spk_radio' )['primary']['freq'], 'the frequency is kept' );
		$html = uxncs_net_screen();
		assert_matches( '#<p class="spk-error-text" id="spk-p-freq-hint"[^>]*>Not an amateur frequency; the site keeps 147\.300\.</p>#', $html, 'one line in place of the hint' );

		$said = uxncs_net_save(
			static function ( array $f ): array {
				$f['radio']['primary']['freq'] = '147.3';
				return $f;
			}
		);
		assert_same( array( 'error' ), wp_list_pluck( $said, 'type' ), 'not saved' );
		assert_same( 'Not saved: the frequency (outlined in red).', $said[0]['text'], 'the notice' );
		assert_contains( '>Three digits after the point, like 147.300.</p>', uxncs_net_screen(), 'the line for a bad shape' );
	}
);
