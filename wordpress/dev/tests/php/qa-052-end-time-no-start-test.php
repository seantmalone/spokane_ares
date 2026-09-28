<?php
/**
 * Regression tests for QA-052 (PLAN §3.4 events, §4.4 guard rails: a
 * refused field is kept, comes back outlined with one sentence, and a retry
 * never drops what was typed).
 *
 * Events › Add event: an editor unticks All day, types only an End time
 * (18:00) and clicks Publish. The save refuses it ("The end time must be
 * after the start time."), keeps the event a Draft and stores the end time,
 * but spokares_event_form_fields() decides All day from the start time
 * alone ($all_day = '' === $v['t_start']), so the form comes back with All
 * day ticked again and the Start/End boxes hidden: the outlined end box and
 * its sentence point at fields the editor cannot see, and the sentence talks
 * about "after the start time" when no start was typed. Clicking Publish
 * again then sends spk_all_day=1, the save clears the end time and the event
 * is published as an all-day event without a word.
 *
 * Each save runs as post.php's "editpost" does (edit_post() with what the
 * browser sends: the rendered event form's inputs, the Save box's hidden
 * fields and the Publish button), so the second Publish posts exactly what
 * the re-rendered form holds.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A day this many days after the site's "today".
 *
 * @param int $days Days.
 */
function qa052_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( \spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * The event form as the edit screen draws it (Kind box and the fields after
 * the event name).
 *
 * @param int $id Event.
 */
function qa052_form_html( int $id ): string {
	clean_post_cache( $id );
	$post = get_post( $id );
	ob_start();
	\spokares_event_form_top( $post );
	\spokares_event_form_fields( $post );
	return (string) ob_get_clean();
}

/**
 * One attribute of a tag, decoded ('' when absent).
 *
 * @param string $tag  The tag.
 * @param string $name Attribute.
 */
function qa052_attr( string $tag, string $name ): string {
	if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag, $m ) ) {
		return html_entity_decode( '' !== ( $m[2] ?? '' ) ? $m[2] : ( $m[3] ?? '' ), ENT_QUOTES, 'UTF-8' );
	}
	return '';
}

/**
 * Is a boolean attribute (checked, hidden) present on a tag?
 *
 * @param string $tag  The tag.
 * @param string $name Attribute.
 */
function qa052_has( string $tag, string $name ): bool {
	return (bool) preg_match( '/\s' . preg_quote( $name, '/' ) . '(\s|=|>|\/)/i', $tag );
}

/**
 * The fields a browser submits from this HTML: hidden, text, date and time
 * inputs, ticked boxes and chosen radios, and textareas (hidden elements
 * are submitted too; only submit buttons and unticked boxes are not).
 *
 * @param string $html Form markup.
 */
function qa052_browser_fields( string $html ): array {
	$pairs = array();
	preg_match_all( '/<input\b[^>]*>/i', $html, $inputs );
	foreach ( $inputs[0] as $input ) {
		$name = qa052_attr( $input, 'name' );
		$type = strtolower( qa052_attr( $input, 'type' ) );
		if ( '' === $name || in_array( $type, array( 'submit', 'button', 'file', 'image', 'reset' ), true ) ) {
			continue;
		}
		if ( in_array( $type, array( 'checkbox', 'radio' ), true ) ) {
			if ( ! qa052_has( $input, 'checked' ) ) {
				continue;
			}
			$value = qa052_has( $input, 'value' ) ? qa052_attr( $input, 'value' ) : 'on';
		} else {
			$value = qa052_attr( $input, 'value' );
		}
		$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( $value );
	}
	preg_match_all( '/<textarea\b([^>]*)>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER );
	foreach ( $areas as $area ) {
		$name = qa052_attr( '<textarea' . $area[1] . '>', 'name' );
		if ( '' !== $name ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( html_entity_decode( $area[2], ENT_QUOTES, 'UTF-8' ) );
		}
	}
	$fields = array();
	wp_parse_str( implode( '&', $pairs ), $fields );
	return $fields;
}

/**
 * Click Publish on the event's edit screen, as post.php's "editpost" does:
 * edit_post() with the form's fields, the Save box's hidden fields and the
 * Publish button. Runs as the current user.
 *
 * @param int   $id     Event.
 * @param array $fields The event form's fields (as qa052_browser_fields() reads them, with the editor's typing).
 */
function qa052_publish( int $id, array $fields ): array {
	$post   = get_post( $id );
	$status = $post->post_status;
	$fields = array_merge(
		$fields,
		array(
			'action'               => 'editpost',
			'originalaction'       => 'editpost',
			'post_ID'              => (string) $id,
			'post_type'            => $post->post_type,
			'post_author'          => (string) $post->post_author,
			'_wpnonce'             => wp_create_nonce( 'update-post_' . $id ),
			'original_post_status' => $status,
			'post_status'          => 'auto-draft' === $status ? 'draft' : $status,
			'publish'              => 'Publish',
		)
	);
	if ( 'auto-draft' === $status ) {
		$fields['auto_draft'] = '1';
	}
	$res = call_request(
		'POST',
		array(),
		$fields,
		static function () {
			return edit_post();
		}
	);
	assert_same( null, $res['die'], 'Publish did not wp_die()' );
	clean_post_cache( $id );
	return $res;
}

/**
 * Add event as the current user, then type into the new form and click
 * Publish. Returns the event ID.
 *
 * @param array $typing Fields the editor sets (null = untick / leave out).
 */
function qa052_add_event( array $typing ): int {
	$id = get_default_post_to_edit( 'spk_event', true )->ID;
	assert_same( 'auto-draft', get_post_status( $id ), 'Add event made an auto-draft (control)' );
	$fields = qa052_browser_fields( qa052_form_html( $id ) );
	foreach ( $typing as $name => $value ) {
		if ( null === $value ) {
			unset( $fields[ $name ] );
		} else {
			$fields[ $name ] = $value;
		}
	}
	qa052_publish( $id, $fields );
	return $id;
}

/**
 * The editor's typing for an exercise with these time boxes.
 *
 * @param string $start   Start time typed ('' = empty).
 * @param string $end     End time typed ('' = empty).
 * @param bool   $all_day All day ticked.
 */
function qa052_typing( string $start, string $end, bool $all_day = false ): array {
	return array(
		'post_title'     => 'QA052 evening drill',
		'spk_kind'       => 'exercise',
		'spk_date_mode'  => 'date',
		'spk_start'      => qa052_day( 20 ),
		'spk_summary'    => 'Evening drill at the EOC',
		'spk_all_day'    => $all_day ? '1' : null,
		'spk_time_start' => $start,
		'spk_time_end'   => $end,
	);
}

/**
 * What the When box of the rendered form shows: the All day tick, whether
 * the Start/End boxes are hidden, the two time inputs and the sentences.
 *
 * @param string $html Form markup.
 */
function qa052_when( string $html ): array {
	if ( ! preg_match( '/<fieldset\b[^>]*\bspk-when\b[^>]*>.*?<\/fieldset>/is', $html, $box ) ) {
		fail( 'the event form has no When box: ' . $html );
	}
	$box  = $box[0];
	$find = static function ( string $name ) use ( $box ): string {
		return preg_match( '/<input\b[^>]*\bname=["\']' . preg_quote( $name, '/' ) . '["\'][^>]*>/i', $box, $m ) ? $m[0] : '';
	};
	$all   = $find( 'spk_all_day' );
	$start = $find( 'spk_time_start' );
	$end   = $find( 'spk_time_end' );
	if ( '' === $all || '' === $start || '' === $end ) {
		fail( 'the When box lacks All day, Start or End: ' . $box );
	}
	$times_hidden = preg_match( '/<span\b[^>]*\bspk-times\b[^>]*>/i', $box, $span ) ? qa052_has( $span[0], 'hidden' ) : false;
	preg_match_all( '/<span class="spk-error-text">(.*?)<\/span>/s', $box, $errors );
	return array(
		'all_day'      => qa052_has( $all, 'checked' ),
		'times_hidden' => $times_hidden,
		'start'        => qa052_attr( $start, 'value' ),
		'end'          => qa052_attr( $end, 'value' ),
		'start_error'  => str_contains( qa052_attr( $start, 'class' ), 'spk-field-error' ),
		'end_error'    => str_contains( qa052_attr( $end, 'class' ), 'spk-field-error' ),
		'errors'       => array_map( static fn( $e ) => html_entity_decode( wp_strip_all_tags( $e ), ENT_QUOTES, 'UTF-8' ), $errors[1] ),
		'tags'         => $all . ' ' . ( $span[0] ?? '(no spk-times span)' ),
	);
}

test(
	'an end time with no start time: the form comes back with All day unticked and the time boxes showing, the end kept and outlined',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$id = qa052_add_event( qa052_typing( '', '18:00' ) );
			assert_same( 'draft', get_post_status( $id ), $role . ': an end time with no start is refused, so the event is a draft (control)' );
			assert_same( '18:00', (string) get_post_meta( $id, 'spk_time_end', true ), $role . ': the typed end time is kept (control)' );

			$when = qa052_when( qa052_form_html( $id ) );
			assert_true( count( $when['errors'] ) > 0, $role . ': the When box says what is wrong (control)' );
			assert_false( $when['all_day'], $role . ': All day comes back ticked although the editor unticked it and typed an end time: ' . $when['tags'] );
			assert_false( $when['times_hidden'], $role . ': the Start/End boxes come back hidden, so the outlined field and its sentence ("' . implode( ' ', $when['errors'] ) . '") point at nothing the editor can see' );
			assert_same( '18:00', $when['end'], $role . ': the End box shows the typed 18:00' );
			assert_true( $when['start_error'] || $when['end_error'], $role . ': a time box is outlined' );
		}
	}
);

test(
	'an end time with no start time: the sentence says the start time is missing',
	function () {
		as_role( 'ares-editor' );
		$id   = qa052_add_event( qa052_typing( '', '18:00' ) );
		$when = qa052_when( qa052_form_html( $id ) );
		$said = array_filter(
			$when['errors'],
			static fn( $e ) => preg_match( '/\bstart\b/i', $e ) && false === stripos( $e, 'must be after' )
		);
		assert_true( count( $said ) > 0, 'no start time was typed, but the form only says: "' . implode( ' ', $when['errors'] ) . '"' );
	}
);

test(
	'an end time with no start time: clicking Publish again keeps the end time and the event stays a draft',
	function () {
		foreach ( array( 'ares-editor', 'ares-net' ) as $role ) {
			as_role( $role );
			$id = qa052_add_event( qa052_typing( '', '18:00' ) );
			assert_same( 'draft', get_post_status( $id ), $role . ': first Publish is refused (control)' );

			// The editor clicks Publish again without touching the form: the browser sends what it shows.
			$fields = qa052_browser_fields( qa052_form_html( $id ) );
			qa052_publish( $id, $fields + array( 'post_title' => 'QA052 evening drill' ) );

			assert_same( '18:00', (string) get_post_meta( $id, 'spk_time_end', true ), $role . ': the second Publish (sent with spk_all_day=' . wp_json_encode( $fields['spk_all_day'] ?? null ) . ') silently cleared the typed end time' );
			assert_same( 'draft', get_post_status( $id ), $role . ': the second Publish put the event on the site as all-day although the end time was never fixed' );
		}
	}
);

test(
	'an all-day event comes back with All day ticked and the time boxes hidden (control)',
	function () {
		as_role( 'ares-editor' );
		$id = qa052_add_event( qa052_typing( '', '', true ) );
		assert_same( 'publish', get_post_status( $id ), 'published' );
		$when = qa052_when( qa052_form_html( $id ) );
		assert_true( $when['all_day'], 'All day is ticked' );
		assert_true( $when['times_hidden'], 'the time boxes are hidden' );
		assert_count( 0, $when['errors'], 'no sentence' );
	}
);

test(
	'an event with a start and an end time comes back with All day unticked and both times shown (control)',
	function () {
		as_role( 'ares-net' );
		$id = qa052_add_event( qa052_typing( '18:00', '20:00' ) );
		assert_same( 'publish', get_post_status( $id ), 'published' );
		$when = qa052_when( qa052_form_html( $id ) );
		assert_false( $when['all_day'], 'All day is unticked' );
		assert_false( $when['times_hidden'], 'the time boxes show' );
		assert_same( '18:00', $when['start'], 'start' );
		assert_same( '20:00', $when['end'], 'end' );
	}
);

test(
	'an end time before the start time is still refused with the end outlined and the times shown (control)',
	function () {
		as_role( 'ares-editor' );
		$id = qa052_add_event( qa052_typing( '19:00', '18:00' ) );
		assert_same( 'draft', get_post_status( $id ), 'refused, so a draft' );
		$when = qa052_when( qa052_form_html( $id ) );
		assert_false( $when['all_day'], 'All day is unticked' );
		assert_false( $when['times_hidden'], 'the time boxes show' );
		assert_true( $when['end_error'], 'the end is outlined' );
		assert_contains( 'The end time must be after the start time.', implode( ' ', $when['errors'] ), 'the sentence' );
	}
);
