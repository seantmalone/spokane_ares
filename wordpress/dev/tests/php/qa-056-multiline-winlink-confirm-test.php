<?php
/**
 * Regression tests for QA-056 (PLAN §4.4 confirm rule, §3.4 Net details):
 * a Winlink sentence typed on two lines with a phone number is held back
 * ("The Winlink sentence wasn’t saved because it mentions a phone number."),
 * and ticking "This is a public agency number. Publish it." saves it. But
 * every later save of the screen, even one that changes only the Tone,
 * holds the unchanged sentence back again with the same error.
 *
 * The cause: spokares_settings_text() records sha1() of the text as typed
 * (with its line break) in spk_nets['confirmed'], and the handler then
 * collapses the whitespace before storing ("Plain one-line text on the
 * site"). The screen shows the stored one-line text, so the next save sends
 * that, its hash never matches, and the phone number counts as unconfirmed.
 * A single-line sentence is remembered after one tick; a two-line one must
 * be too.
 *
 * The sentence moved to the Net Control Schedule screen (workflow finding
 * N7), so the saves now submit what that form shows: the textarea text as
 * the screen renders it, with its line breaks sent as CRLF like a browser.
 * "Only the tone changes" became "only a Tuesday's note changes".
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Start from a Winlink sentence with no contact details and nothing
 * confirmed, so each role's run starts the same. The framework restores
 * the options after the test.
 */
function qa056_baseline(): void {
	$nets                  = \spokares_opt( 'spk_nets' );
	$nets['winlink_howto'] = 'Answer the assignment by Winlink to the net control station.';
	unset( $nets['confirmed'] );
	update_option( 'spk_nets', $nets );
	\spokares_opt_flush();
	delete_transient( 'spokares_retain_' . get_current_user_id() . '_spokares-rota' );
}

/**
 * The text the Net Control Schedule screen puts in the Winlink sentence box
 * (the held text after a hold, else the stored text), as a browser submits it.
 */
function qa056_form_howto(): string {
	\spokares_opt_flush();
	ob_start();
	\spokares_rota_page();
	$html = (string) ob_get_clean();
	if ( ! preg_match( '/<textarea[^>]*id="spk-howto"[^>]*>(.*?)<\/textarea>/s', $html, $m ) ) {
		fail( 'the Net Control Schedule screen has no Winlink sentence box' );
	}
	$text = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	// A browser drops one leading line break and sends line breaks as CRLF.
	$text = (string) preg_replace( '/^\r?\n/', '', $text );
	return (string) preg_replace( '/\r\n|\r|\n/', "\r\n", $text );
}

/**
 * The schedule's form fields for the two sentences as the screen posts
 * them, and maybe one Tuesday's note changed.
 *
 * @param string $howto The Winlink sentence box.
 * @param string $note  A note for the first Tuesday ('' = no row change).
 * @param bool   $tick  Tick "Publish it" for the Winlink sentence.
 */
function qa056_fields( string $howto, string $note = '', bool $tick = false ): array {
	$nets   = \spokares_opt( 'spk_nets' );
	$fields = array(
		'weeks'   => '13',
		'words'   => array(
			'winlink_howto'  => $howto,
			'open_slot_line' => $nets['open_slot_line'],
		),
		'words_h' => array(
			'winlink_howto'  => md5( \spokares_rota_word_text( 'winlink_howto', $nets['winlink_howto'] ) ),
			'open_slot_line' => md5( \spokares_rota_word_text( 'open_slot_line', $nets['open_slot_line'] ) ),
		),
	);
	if ( '' !== $note ) {
		$row            = \spokares_rota_rows( 1 )[0];
		$stored         = array(
			'state'   => $row['state'],
			'call'    => $row['call'],
			'note'    => $row['row_note'],
			'wl_task' => $row['wl_task'],
			'wl_form' => $row['wl_form'],
		);
		$fields['rota'] = array( $row['date'] => array_merge( $stored, array( 'h' => \spokares_rota_hash( $stored ) ), array( 'note' => $note ) ) );
	}
	if ( $tick ) {
		$fields['words_confirm'] = array( 'winlink_howto' => '1' );
	}
	return $fields;
}

/**
 * Save the schedule as the current user and return what the save left
 * behind: the stored nets, the held input's errors (keys without "words-"),
 * and the notices.
 *
 * @param array  $fields Form fields.
 * @param string $msg    Message prefix.
 */
function qa056_save( array $fields, string $msg ): array {
	$user = get_current_user_id();
	delete_transient( 'spokares_notices_' . $user );
	$res = post_form( 'spokares_save_rota', $fields );
	assert_same( null, $res['die'], $msg . ': save refused' );
	assert_contains( 'page=spokares-rota', (string) $res['redirect'], $msg . ': redirect back to the screen' );
	\spokares_unlock_option( 'spk_rota' );
	\spokares_opt_flush();

	$notices  = get_transient( 'spokares_notices_' . $user );
	$retained = get_transient( 'spokares_retain_' . $user . '_spokares-rota' );
	$errors   = array();
	foreach ( is_array( $retained['errors'] ?? null ) ? $retained['errors'] : array() as $key => $sentence ) {
		$errors[ (string) preg_replace( '/^words-/', '', (string) $key ) ] = $sentence;
	}
	delete_transient( 'spokares_notices_' . $user );
	return array(
		'nets'    => \spokares_opt( 'spk_nets' ),
		'rota'    => \spokares_opt( 'spk_rota' ),
		'errors'  => $errors,
		'notices' => is_array( $notices ) ? wp_list_pluck( $notices, 'text' ) : array(),
	);
}

/**
 * Assert a save went through with no field held back.
 *
 * @param array  $out What qa056_save() returned.
 * @param string $msg Message prefix.
 */
function qa056_assert_saved( array $out, string $msg ): void {
	assert_false( isset( $out['errors']['winlink_howto'] ), $msg . ': the confirmed Winlink sentence was held back again ("' . ( $out['errors']['winlink_howto'] ?? '' ) . '")' );
	assert_false( isset( $out['errors']['winlink_howto-confirm'] ), $msg . ': the "Publish it" tick was asked for again' );
	assert_count( 0, $out['errors'], $msg . ': unexpected field errors' );
	assert_count( 1, $out['notices'], $msg . ': one notice' );
	assert_matches( '/^(Saved .+\.|Nothing changed, so nothing was saved\.)$/', (string) ( $out['notices'][0] ?? '' ), $msg . ': the notice says it was saved (or that nothing changed), not that something was held back' );
}

test(
	'a ticked two-line Winlink sentence with a phone number is not flagged again when only a Tuesday’s note changes',
	function () {
		$typed = "Answer the assignment by Winlink to the net control station.\r\nQuestions? Call the EOC desk at 509-555-0100.";
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			qa056_baseline();

			// Save 1: the two-line sentence is held back with the tick offered.
			$out = qa056_save( qa056_fields( $typed ), $role . ' save 1' );
			assert_true( isset( $out['errors']['winlink_howto'] ), $role . ' save 1: the phone number was not held back (the confirm rule did not run)' );
			assert_true( isset( $out['errors']['winlink_howto-confirm'] ), $role . ' save 1: no "Publish it" tick offered' );

			// Save 2: tick "Publish it" on the held text and Save.
			$shown = qa056_form_howto();
			assert_contains( '509-555-0100', $shown, $role . ' save 2: the held sentence is not in the box' );
			$out = qa056_save( qa056_fields( $shown, '', true ), $role . ' save 2 (ticked)' );
			qa056_assert_saved( $out, $role . ' save 2 (ticked)' );
			$stored = $out['nets']['winlink_howto'];
			assert_contains( '509-555-0100', $stored, $role . ' save 2: the confirmed sentence was not stored' );

			// Save 3: change only a Tuesday's note. The sentence is the unchanged, confirmed text.
			$note = 'Note from ' . $role;
			$out  = qa056_save( qa056_fields( qa056_form_howto(), $note ), $role . ' save 3 (only a note changed)' );
			qa056_assert_saved( $out, $role . ' save 3 (only a note changed)' );
			assert_same( $note, $out['rota'][ \spokares_rota_rows( 1 )[0]['date'] ]['note'] ?? '', $role . ' save 3: the note was not saved' );
			assert_same( $stored, $out['nets']['winlink_howto'], $role . ' save 3: the Winlink sentence changed' );

			// Save 4: save again with nothing changed.
			$out = qa056_save( qa056_fields( qa056_form_howto() ), $role . ' save 4 (nothing changed)' );
			qa056_assert_saved( $out, $role . ' save 4 (nothing changed)' );
		}
	}
);

test(
	'control: a ticked one-line Winlink sentence is remembered, and a changed number is flagged again',
	function () {
		as_role( 'ares-editor' );
		qa056_baseline();
		$typed = 'Answer the assignment by Winlink to the net control station. Questions? Call the EOC desk at 509-555-0100.';

		$out = qa056_save( qa056_fields( $typed ), 'save 1' );
		assert_true( isset( $out['errors']['winlink_howto-confirm'] ), 'save 1: no "Publish it" tick offered' );
		$out = qa056_save( qa056_fields( qa056_form_howto(), '', true ), 'save 2 (ticked)' );
		qa056_assert_saved( $out, 'save 2 (ticked)' );
		$out = qa056_save( qa056_fields( qa056_form_howto() ), 'save 3 (nothing changed)' );
		qa056_assert_saved( $out, 'save 3 (nothing changed)' );

		// A different number is new text: it must be confirmed again.
		$out = qa056_save( qa056_fields( str_replace( '0100', '0199', qa056_form_howto() ) ), 'save 4 (number changed)' );
		assert_true( isset( $out['errors']['winlink_howto-confirm'] ), 'save 4: a changed phone number was published without a tick' );
		assert_not_contains( '509-555-0199', $out['nets']['winlink_howto'], 'save 4: the changed number was stored' );
	}
);

test(
	'control: a changed number in a confirmed two-line Winlink sentence is flagged again',
	function () {
		as_role( 'ares-editor' );
		qa056_baseline();
		$typed = "Answer the assignment by Winlink to the net control station.\r\nQuestions? Call the EOC desk at 509-555-0100.";
		qa056_save( qa056_fields( $typed ), 'save 1' );
		qa056_save( qa056_fields( qa056_form_howto(), '', true ), 'save 2 (ticked)' );

		$out = qa056_save( qa056_fields( str_replace( '0100', '0199', $typed ) ), 'save 3 (number changed)' );
		assert_true( isset( $out['errors']['winlink_howto-confirm'] ), 'save 3: a changed phone number was published without a tick' );
		assert_not_contains( '509-555-0199', $out['nets']['winlink_howto'], 'save 3: the changed number was stored' );
	}
);
