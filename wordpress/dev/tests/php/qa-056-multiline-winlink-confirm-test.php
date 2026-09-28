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
 * The saves submit what the Net details form shows: the textarea text as
 * the screen renders it, with its line breaks sent as CRLF like a browser.
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
	delete_transient( 'spokares_retain_' . get_current_user_id() . '_spokares-net-details' );
}

/**
 * The text the Net details screen puts in the Winlink sentence box (the
 * held text after a hold, else the stored text), as a browser submits it.
 * Rendering the screen also clears the held input, as opening it does.
 */
function qa056_form_howto(): string {
	ob_start();
	\spokares_net_details_page();
	$html = (string) ob_get_clean();
	if ( ! preg_match( '/<textarea[^>]*id="spk-howto"[^>]*>(.*?)<\/textarea>/s', $html, $m ) ) {
		fail( 'the Net details screen has no Winlink sentence box' );
	}
	$text = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	// A browser drops one leading line break and sends line breaks as CRLF.
	$text = (string) preg_replace( '/^\r?\n/', '', $text );
	return (string) preg_replace( '/\r\n|\r|\n/', "\r\n", $text );
}

/**
 * The Net details form fields as the screen posts them.
 *
 * @param string $howto The Winlink sentence box.
 * @param string $tone  The primary repeater's tone ('' = keep the stored one).
 * @param bool   $tick  Tick "Publish it" for the Winlink sentence.
 */
function qa056_fields( string $howto, string $tone = '', bool $tick = false ): array {
	$radio  = \spokares_opt( 'spk_radio' );
	$nets   = \spokares_opt( 'spk_nets' );
	$fields = array(
		'radio' => array(
			'primary'   => array(
				'call'   => $radio['primary']['call'],
				'freq'   => $radio['primary']['freq'],
				'offset' => $radio['primary']['offset'],
				'tone'   => '' !== $tone ? $tone : $radio['primary']['tone'],
			),
			'alternate' => array(
				'freq'        => $radio['alternate']['freq'],
				'offset'      => $radio['alternate']['offset'],
				'tone'        => $radio['alternate']['tone'],
				'show'        => $radio['alternate']['show'] ? '1' : '',
				'needs_check' => $radio['alternate']['needs_check'] ? '1' : '',
			),
		),
		'nets'  => array(
			'net_time'       => $nets['net_time'],
			'gmrs_time'      => $nets['gmrs_time'],
			'winlink_nth'    => $nets['winlink_nth'],
			'simplex_nth'    => $nets['simplex_nth'],
			'gmrs_nth'       => $nets['gmrs_nth'],
			'needs_check'    => $nets['needs_check'] ? '1' : '',
			'winlink_howto'  => $howto,
			'open_slot_line' => $nets['open_slot_line'],
		),
	);
	if ( $tick ) {
		$fields['confirm'] = array( 'winlink_howto' => '1' );
	}
	return $fields;
}

/**
 * Save Net details as the current user and return what the save left
 * behind: the stored options, the held input and errors, and the notices.
 * The held input stays in place for the screen to show.
 *
 * @param array  $fields Form fields.
 * @param string $msg    Message prefix.
 */
function qa056_save( array $fields, string $msg ): array {
	$user = get_current_user_id();
	delete_transient( 'spokares_notices_' . $user );
	$res = post_form( 'spokares_save_net_details', $fields );
	assert_same( null, $res['die'], $msg . ': save refused' );
	assert_contains( 'page=spokares-net-details', (string) $res['redirect'], $msg . ': redirect back to the screen' );

	$notices  = get_transient( 'spokares_notices_' . $user );
	$retained = get_transient( 'spokares_retain_' . $user . '_spokares-net-details' );
	delete_transient( 'spokares_notices_' . $user );
	return array(
		'nets'    => \spokares_opt( 'spk_nets' ),
		'radio'   => \spokares_opt( 'spk_radio' ),
		'errors'  => is_array( $retained['errors'] ?? null ) ? $retained['errors'] : array(),
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
	assert_contains( 'Saved.', $out['notices'], $msg . ': no plain "Saved." notice' );
}

test(
	'a ticked two-line Winlink sentence with a phone number is not flagged again when only the tone changes',
	function () {
		$typed = "Answer the assignment by Winlink to the net control station.\r\nQuestions? Call the EOC desk at 509-555-0100.";
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
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

			// Save 3: change only the tone. The sentence is the unchanged, confirmed text.
			$tone = '100 Hz' === $out['radio']['primary']['tone'] ? '103.5 Hz' : '100 Hz';
			$out  = qa056_save( qa056_fields( qa056_form_howto(), $tone ), $role . ' save 3 (only the tone changed)' );
			qa056_assert_saved( $out, $role . ' save 3 (only the tone changed)' );
			assert_same( $tone, $out['radio']['primary']['tone'], $role . ' save 3: the new tone was not saved' );
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
		as_role( 'ares-net' );
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
		as_role( 'ares-net' );
		qa056_baseline();
		$typed = "Answer the assignment by Winlink to the net control station.\r\nQuestions? Call the EOC desk at 509-555-0100.";
		qa056_save( qa056_fields( $typed ), 'save 1' );
		qa056_save( qa056_fields( qa056_form_howto(), '', true ), 'save 2 (ticked)' );

		$out = qa056_save( qa056_fields( str_replace( '0100', '0199', $typed ) ), 'save 3 (number changed)' );
		assert_true( isset( $out['errors']['winlink_howto-confirm'] ), 'save 3: a changed phone number was published without a tick' );
		assert_not_contains( '509-555-0199', $out['nets']['winlink_howto'], 'save 3: the changed number was stored' );
	}
);
