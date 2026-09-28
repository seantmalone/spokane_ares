<?php
/**
 * Regression tests for QA-039 (PLAN §3.4 Net details).
 *
 * The repeater call sign W7GBU is the club's own licence, and How it works
 * names it in page text and in the message-path diagram. Anyone with the
 * Net details grant (spokares_edit_net_details) could change it on Net
 * details, and the page then contradicted itself. The owner decided
 * (2026-09-27) that W7GBU is fixed: the call-sign field is for
 * administrators only. A user with just the grant sees it read-only and a
 * save from that user can't change it, whatever the request posts (checked
 * on the server, not only in the form). Administrators can still change it.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The Net details screen as the current user sees it.
 */
function qa039_screen(): string {
	\spokares_opt_flush();
	ob_start();
	\spokares_net_details_page();
	return (string) ob_get_clean();
}

/**
 * The call-sign box's tag on the Net details screen.
 *
 * @param string $html Screen HTML.
 */
function qa039_call_tag( string $html ): string {
	if ( ! preg_match( '/<input\b[^>]*\bid="spk-p-call"[^>]*>/i', $html, $m ) ) {
		fail( 'the Net details screen has no call-sign box (#spk-p-call)' );
	}
	return $m[0];
}

/**
 * One attribute of a tag, decoded (null when absent).
 *
 * @param string $tag  The tag.
 * @param string $name Attribute.
 */
function qa039_attr( string $tag, string $name ): ?string {
	if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '(?:\s*=\s*("([^"]*)"|\'([^\']*)\'))?(?=[\s>\/])/i', $tag, $m ) ) {
		return html_entity_decode( (string) ( '' !== ( $m[2] ?? '' ) ? $m[2] : ( $m[3] ?? '' ) ), ENT_QUOTES, 'UTF-8' );
	}
	return null;
}

/**
 * The Net details form fields as the screen posts them, from the stored
 * options, with the repeater call sign replaced and, optionally, the
 * form's "what the form showed" record.
 *
 * @param string $call      Call sign to post.
 * @param bool   $with_orig Also post the record the screen draws.
 */
function qa039_fields( string $call, bool $with_orig = false ): array {
	\spokares_opt_flush();
	$radio  = \spokares_opt( 'spk_radio' );
	$nets   = \spokares_opt( 'spk_nets' );
	$fields = array(
		'radio' => array(
			'primary'   => array(
				'call'   => $call,
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
		'nets'  => array(
			'net_time'       => $nets['net_time'],
			'gmrs_time'      => $nets['gmrs_time'],
			'winlink_nth'    => $nets['winlink_nth'],
			'simplex_nth'    => $nets['simplex_nth'],
			'gmrs_nth'       => $nets['gmrs_nth'],
			'winlink_howto'  => $nets['winlink_howto'],
			'open_slot_line' => $nets['open_slot_line'],
		),
	);
	if ( $with_orig ) {
		$fields['orig'] = (string) wp_json_encode( \spokares_net_form_state( $nets, $radio ) );
	}
	return $fields;
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa039_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Save Net details as the current user; returns the notices it queued.
 *
 * @param array $fields Form fields.
 */
function qa039_save( array $fields ): array {
	qa039_notices();
	$res = post_form( 'spokares_save_net_details', $fields );
	assert_same( null, $res['die'], 'the Net details save was refused outright' );
	assert_contains( 'page=spokares-net-details', (string) $res['redirect'], 'the save redirects back to Net details' );
	\spokares_opt_flush();
	return qa039_notices();
}

/**
 * The stored repeater call sign.
 */
function qa039_call(): string {
	\spokares_opt_flush();
	return (string) \spokares_opt( 'spk_radio' )['primary']['call'];
}

test(
	'the seeded repeater call sign is W7GBU and qa-ares-net holds the grant but is not an administrator (control)',
	function () {
		assert_same( 'W7GBU', qa039_call(), 'seeded call sign' );
		as_role( 'ares-net' );
		assert_true( current_user_can( 'spokares_edit_net_details' ), 'qa-ares-net holds the Net details grant' );
		assert_false( current_user_can( 'manage_options' ), 'qa-ares-net is not an administrator' );
	}
);

test(
	'Net details shows the repeater call sign read-only to a user with only the grant',
	function () {
		as_role( 'ares-net' );
		$tag = qa039_call_tag( qa039_screen() );
		assert_same( 'W7GBU', qa039_attr( $tag, 'value' ), 'the box shows the stored call sign' );
		assert_true( null !== qa039_attr( $tag, 'readonly' ), 'the call-sign box is editable for qa-ares-net: ' . $tag );
		assert_true( 'radio[primary][call]' !== qa039_attr( $tag, 'name' ), 'the read-only box is still posted with the save: ' . $tag );
	}
);

test(
	'Net details says why the call sign is read-only, tied to the box',
	function () {
		as_role( 'ares-net' );
		$html = qa039_screen();
		$tag  = qa039_call_tag( $html );
		$by   = (string) qa039_attr( $tag, 'aria-describedby' );
		assert_true( '' !== $by, 'the read-only box has no aria-describedby: ' . $tag );
		$ids   = preg_split( '/\s+/', trim( $by ) );
		$found = '';
		foreach ( $ids as $id ) {
			if ( preg_match( '/<[^>]*\bid="' . preg_quote( $id, '/' ) . '"[^>]*>(.*?)<\//s', $html, $m ) ) {
				$found .= ' ' . wp_strip_all_tags( $m[1] );
			}
		}
		assert_matches( '/administrator/i', $found, 'the description tied to the box says who can change it' );
	}
);

test(
	'Net details keeps the call-sign box editable for an administrator',
	function () {
		as_role( 'admin' );
		$tag = qa039_call_tag( qa039_screen() );
		assert_same( null, qa039_attr( $tag, 'readonly' ), 'the call-sign box is read-only for the administrator: ' . $tag );
		assert_same( 'radio[primary][call]', qa039_attr( $tag, 'name' ), 'the administrator\'s call-sign box is posted with the save' );
		assert_same( 'W7GBU', qa039_attr( $tag, 'value' ), 'the box shows the stored call sign' );
	}
);

test(
	'a user with only the grant cannot change the call sign by posting it (no form record)',
	function () {
		as_role( 'ares-net' );
		$said = qa039_save( qa039_fields( 'K7XYZ' ) );
		assert_same( 'W7GBU', qa039_call(), 'qa-ares-net changed the repeater call sign on the server' );
		assert_contains( 'error', wp_list_pluck( $said, 'type' ), 'qa-ares-net is not told the call sign wasn\'t saved: ' . wp_json_encode( $said ) );
		assert_not_contains( 'success', wp_list_pluck( $said, 'type' ), 'a plain "Saved." for a refused call sign' );
	}
);

test(
	'a user with only the grant cannot change the call sign by posting it (with the form record)',
	function () {
		as_role( 'ares-net' );
		qa039_save( qa039_fields( 'K7XYZ', true ) );
		assert_same( 'W7GBU', qa039_call(), 'qa-ares-net changed the repeater call sign on the server' );
	}
);

test(
	'a refused call sign is not held in the read-only box on the next screen',
	function () {
		as_role( 'ares-net' );
		qa039_save( qa039_fields( 'K7XYZ', true ) );
		$tag = qa039_call_tag( qa039_screen() );
		assert_same( 'W7GBU', qa039_attr( $tag, 'value' ), 'the read-only box shows a call sign that isn\'t stored' );
	}
);

test(
	'a user with only the grant still saves the other net details, and the call sign stays (control)',
	function () {
		as_role( 'ares-net' );
		$fields = qa039_fields( 'W7GBU', true );
		unset( $fields['radio']['primary']['call'] ); // The read-only box isn't posted.
		$fields['radio']['primary']['tone'] = '103.5 Hz';
		$said                               = qa039_save( $fields );
		assert_same( array( 'success' ), array_values( array_unique( wp_list_pluck( $said, 'type' ) ) ), 'the save: ' . wp_json_encode( $said ) );
		assert_same( '103.5 Hz', \spokares_opt( 'spk_radio' )['primary']['tone'], 'the tone is saved' );
		assert_same( 'W7GBU', qa039_call(), 'the call sign' );
	}
);

test(
	'a user with only the grant who posts the stored call sign unchanged gets a plain "Saved." (control)',
	function () {
		as_role( 'ares-net' );
		$said = qa039_save( qa039_fields( 'W7GBU' ) );
		assert_same( array( 'success' ), array_values( array_unique( wp_list_pluck( $said, 'type' ) ) ), 'the save: ' . wp_json_encode( $said ) );
		assert_same( 'W7GBU', qa039_call(), 'the call sign' );
	}
);

test(
	'an administrator can still change the repeater call sign',
	function () {
		as_role( 'admin' );
		$said = qa039_save( qa039_fields( 'k7xyz', true ) );
		assert_same( 'K7XYZ', qa039_call(), 'the administrator\'s new call sign (notices: ' . wp_json_encode( $said ) . ')' );
		assert_contains( 'success', wp_list_pluck( $said, 'type' ), 'the administrator\'s save says "Saved."' );
	}
);

test(
	'an administrator\'s refused call sign is held in the box for correction, but not shown in the preview (control)',
	function () {
		as_role( 'admin' );
		qa039_save( qa039_fields( 'Frank', true ) );
		assert_same( 'W7GBU', qa039_call(), '"Frank" was saved as the repeater call sign' );
		$html = qa039_screen();
		$tag  = qa039_call_tag( $html );
		assert_same( 'Frank', qa039_attr( $tag, 'value' ), 'the administrator\'s typed "Frank" is not kept in the box' );
		if ( ! preg_match( '#<div[^>]*id="spk-net-preview"[^>]*>.*?</dl>#s', $html, $m ) ) {
			fail( 'the Net details screen has no preview box (#spk-net-preview)' );
		}
		assert_not_contains( 'frank', strtolower( wp_strip_all_tags( $m[0] ) ), 'the preview shows the refused call sign' );
	}
);
