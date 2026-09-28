<?php
/**
 * Tests for the volunteer-editor UX pass on Exercises & Events, the two
 * meeting screens and the data layer (ux/SPEC.md §3.4, §3.5, §4.1, §4.3,
 * §4.4, §4.8): the event form's fields and order (create = edit), Postponed
 * and Cancelled, one notice per save, the Where it shows column, Make a copy,
 * a note on its own as a one-date meeting change, "Takes effect on", the one
 * "Show on the site" tick, and the new states and labels in the data layer.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * A day this many days after the site's "today".
 *
 * @param int $days Days (negative for earlier).
 */
function uxevt_day( int $days ): string {
	return \spokares_add_days( \spokares_today(), $days );
}

/**
 * The notices queued for the current user (and clear them).
 */
function uxevt_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * The event form as the edit screen draws it (the type of event, the name's
 * label and the fields after the name).
 *
 * @param int $id Event.
 */
function uxevt_form( int $id ): string {
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
function uxevt_attr( string $tag, string $name ): string {
	if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag, $m ) ) {
		return html_entity_decode( '' !== ( $m[2] ?? '' ) ? $m[2] : ( $m[3] ?? '' ), ENT_QUOTES, 'UTF-8' );
	}
	return '';
}

/**
 * Is a boolean attribute (checked, selected, required) present on a tag?
 *
 * @param string $tag  The tag.
 * @param string $name Attribute.
 */
function uxevt_has( string $tag, string $name ): bool {
	return (bool) preg_match( '/\s' . preg_quote( $name, '/' ) . '(\s|=|>|\/)/i', $tag );
}

/**
 * The fields a browser submits from this markup: inputs (ticked boxes and
 * chosen radios only), textareas and the chosen option of each select.
 *
 * @param string $html Form markup.
 */
function uxevt_fields( string $html ): array {
	$pairs = array();
	preg_match_all( '/<input\b[^>]*>/i', $html, $inputs );
	foreach ( $inputs[0] as $input ) {
		$name = uxevt_attr( $input, 'name' );
		$type = strtolower( uxevt_attr( $input, 'type' ) );
		if ( '' === $name || in_array( $type, array( 'submit', 'button', 'file', 'image', 'reset' ), true ) ) {
			continue;
		}
		if ( in_array( $type, array( 'checkbox', 'radio' ), true ) ) {
			if ( ! uxevt_has( $input, 'checked' ) ) {
				continue;
			}
			$value = uxevt_has( $input, 'value' ) ? uxevt_attr( $input, 'value' ) : 'on';
		} else {
			$value = uxevt_attr( $input, 'value' );
		}
		$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( $value );
	}
	preg_match_all( '/<textarea\b([^>]*)>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER );
	foreach ( $areas as $area ) {
		$name = uxevt_attr( '<textarea' . $area[1] . '>', 'name' );
		if ( '' !== $name ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( html_entity_decode( $area[2], ENT_QUOTES, 'UTF-8' ) );
		}
	}
	preg_match_all( '/<select\b([^>]*)>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER );
	foreach ( $selects as $select ) {
		$name = uxevt_attr( '<select' . $select[1] . '>', 'name' );
		if ( '' === $name ) {
			continue;
		}
		preg_match_all( '/<option\b[^>]*>/i', $select[2], $options );
		$chosen = $options[0][0] ?? '';
		foreach ( $options[0] as $option ) {
			if ( uxevt_has( $option, 'selected' ) ) {
				$chosen = $option;
				break;
			}
		}
		if ( '' !== $chosen ) {
			$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( uxevt_attr( $chosen, 'value' ) );
		}
	}
	$fields = array();
	wp_parse_str( implode( '&', $pairs ), $fields );
	return $fields;
}

/**
 * Click a Save box button on the event's edit screen, as post.php's
 * "editpost" does: the form's fields, then edit_post() and redirect_post().
 *
 * @param int    $id     Event.
 * @param array  $typing Fields the editor sets over the drawn form (null = leave out).
 * @param string $button publish, save or saveasdraft.
 */
function uxevt_click( int $id, array $typing, string $button ): array {
	$post   = get_post( $id );
	$status = $post->post_status;
	$fields = uxevt_fields( uxevt_form( $id ) );
	$fields = array_merge(
		$fields,
		array(
			'post_title'           => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
			'action'               => 'editpost',
			'originalaction'       => 'editpost',
			'post_ID'              => (string) $id,
			'post_type'            => $post->post_type,
			'post_author'          => (string) $post->post_author,
			'_wpnonce'             => wp_create_nonce( 'update-post_' . $id ),
			'original_post_status' => $status,
			'post_status'          => 'auto-draft' === $status ? 'draft' : $status,
		)
	);
	foreach ( $typing as $name => $value ) {
		if ( null === $value ) {
			unset( $fields[ $name ] );
		} else {
			$fields[ $name ] = $value;
		}
	}
	$labels            = array(
		'publish'     => 'Publish',
		'save'        => 'Save',
		'saveasdraft' => 'Save draft',
	);
	$fields[ $button ] = $labels[ $button ];
	if ( 'auto-draft' === $status ) {
		$fields['auto_draft'] = '1';
	}
	uxevt_notices();
	$res = call_request(
		'POST',
		array(),
		$fields,
		static function () {
			$saved = edit_post();
			redirect_post( $saved );
		}
	);
	assert_same( null, $res['die'], $button . ' did not wp_die()' );
	clean_post_cache( $id );
	$res['notices'] = uxevt_notices();
	return $res;
}

/**
 * The query args of a redirect.
 *
 * @param array $res call_request() result.
 */
function uxevt_query( array $res ): array {
	$query = array();
	wp_parse_str( (string) wp_parse_url( (string) $res['redirect'], PHP_URL_QUERY ), $query );
	return $query;
}

/**
 * A published event made directly (removed after the test).
 *
 * @param string $title Title.
 * @param array  $meta  Meta.
 * @param string $status Post status.
 */
function uxevt_event( string $title, array $meta, string $status = 'publish' ): int {
	as_role( 'admin' );
	$id = create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => $status,
			'post_title'  => $title,
		)
	);
	foreach ( array_merge( array( 'spk_date_mode' => 'date' ), $meta ) as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	\spokares_update_event_sort( $id );
	return $id;
}

/**
 * Is an event in one of the site's lists?
 *
 * @param int    $id   Event.
 * @param string $view spokares_events() view.
 * @param array  $args Its arguments.
 */
function uxevt_in( int $id, string $view, array $args = array() ): bool {
	return in_array( $id, array_map( 'intval', wp_list_pluck( \spokares_events( $view, $args ), 'id' ) ), true );
}

/**
 * The Where it shows cell of an event, as the list prints it.
 *
 * @param int $id Event.
 */
function uxevt_where( int $id ): string {
	ob_start();
	\spokares_event_column( 'spk_status', $id );
	return (string) ob_get_clean();
}

/**
 * The visible labels of the event form, in order (legends and labels).
 *
 * @param string $html Form markup.
 */
function uxevt_labels( string $html ): array {
	preg_match_all( '#<(legend|label)\b[^>]*>(.*?)</\1>#s', $html, $m );
	$out = array();
	foreach ( $m[2] as $inner ) {
		// A tick's words follow its box; a radio's words are choices, not field labels.
		if ( preg_match( '/type="radio"/', $inner ) ) {
			continue;
		}
		$text = trim( html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES, 'UTF-8' ) );
		if ( '' !== $text ) {
			$out[] = $text;
		}
	}
	return $out;
}

/* ------------------------------------------------------------ event form */

test(
	'the event form: the same fields, order and labels on Add and Edit; required types; no "(optional)", no type hint',
	function () {
		as_role( 'ares-net' );
		$add  = get_default_post_to_edit( 'spk_event', true )->ID;
		$html = uxevt_form( $add );
		$want = array(
			'Type of event (required)',
			'Event name (required)',
			'When',
			'First day (required)',
			'Last day',
			'All day',
			'Start',
			'End',
			'Where',
			'Short description',
			'What members do (one task per line)',
			'Button',
			'Web address',
			'Words on the button',
			'More links (up to 3)',
			'Words',
			'Web address',
			'Words',
			'Web address',
			'Words',
			'Web address',
			'Document members need',
			'Volunteer through (call sign)',
			'List under Past exercises after it ends',
		);
		assert_same( $want, uxevt_labels( $html ), 'Add an Event: labels in order' );
		assert_not_contains( '(optional)', $html, 'no field says (optional)' );
		assert_not_contains( 'On the air:', $html, 'no "On the air: …" sentence' );
		assert_not_contains( 'Where it shows', $html, 'no per-type "Where it shows" hints' );
		assert_not_contains( 'spk-error-text', $html, 'a new event shows no problems' );
		preg_match_all( '/<input\b[^>]*name="spk_kind"[^>]*>/', $html, $kinds );
		assert_count( 4, $kinds[0], 'four types of event' );
		foreach ( $kinds[0] as $radio ) {
			assert_true( uxevt_has( $radio, 'required' ), 'each type radio is required: ' . $radio );
		}
		assert_contains( 'Training or meeting', $html, 'type label' );
		assert_contains( 'On the air (from home stations)', $html, 'type label' );
		assert_matches( '#<label class="spk-choice" data-kinds="public-service"><input type="radio" name="spk_date_mode" value="as-requested"#', $html, 'As requested only for Public service' );
		assert_matches( '#value="postponed"[^>]*> Postponed</label>#', $html, 'the Postponed choice' );
		assert_matches( '#<input type="date" id="spk-start"[^>]*required>#', $html, 'First day is required when On a date' );
		assert_matches( '#<input type="text" inputmode="url"[^>]*id="spk-main-url"[^>]*placeholder="https://…"#', $html, 'the web address is a text box for URLs, with https://… as its placeholder' );
		assert_contains( 'Copy it from your browser’s address bar.', $html, 'the web address hint, once' );
		assert_same( 1, substr_count( $html, 'Copy it from your browser’s address bar.' ), 'the hint appears once' );
		assert_matches( '#id="spk-main-label"[^>]*placeholder="Details"#', $html, 'Words on the button: "Details" by default' );
		assert_matches( '#id="spk-summary"[^>]*maxlength="90"#', $html, 'Short description: 90 characters' );
		assert_matches( '#<div class="spk-field" data-kinds="exercise,training,on-air">\s*<label for="spk-summary">#', $html, 'Short description is not for Public service' );
		assert_matches( '#<input type="checkbox" name="spk_keep_past" value="1"\s+checked#', $html, 'List under Past exercises is ticked on Add' );
		assert_contains( '<p class="spk-foot">Never publish county, hospital, SHARES or 800 MHz channels, or names, phones or e-mails.</p>', $html, 'the plain foot line' );
		assert_not_contains( 'name="spk_cancelled"', $html, 'no Cancelled tick on Add' );

		// Edit: the same fields, plus the Cancelled tick of a published event.
		$set  = post_id( 'spk_event', 'set-2026' );
		$edit = uxevt_labels( uxevt_form( $set ) );
		$with = $want;
		array_splice( $with, 8, 0, array( 'Cancelled' ) );
		assert_same( $with, $edit, 'Edit Event: the same labels, with Cancelled under When' );
		assert_contains( 'Members see “Cancelled” until the date passes.', uxevt_form( $set ), 'the Cancelled hint' );
		assert_matches( '#<span class="spk-weekday" data-for="spk-start">Sat</span>#', uxevt_form( $set ), 'the weekday after First day' );

		// Words on the button follows a groups.io address.
		update_post_meta( $set, 'spk_main_url', 'https://spokaneares-acs.groups.io/g/main/topic/1' );
		assert_matches( '#id="spk-main-label"[^>]*placeholder="Exercise details on groups.io"#', uxevt_form( $set ), 'groups.io placeholder' );
	}
);

test(
	'the Webmaster check box and the Needs checking column are for administrators only',
	function () {
		global $wp_meta_boxes;
		$saved = $wp_meta_boxes;
		try {
			as_role( 'ares-net' );
			\spokares_event_boxes( get_post( post_id( 'spk_event', 'set-2026' ) ) );
			assert_false( isset( $wp_meta_boxes['spk_event']['side']['default']['spokares_checking'] ), 'no Webmaster check box for an editor' );
			assert_false( isset( \spokares_event_columns( array() )['spk_check'] ), 'no Needs checking column for an editor' );
			assert_same( array( 'spk_when', 'title', 'spk_kind', 'spk_status' ), array_keys( \spokares_event_columns( array() ) ), 'columns (no row ticks for an editor)' );
			assert_same( array( 'When', 'Event', 'Type of event', 'Where it shows' ), array_values( \spokares_event_columns( array() ) ), 'column names' );
			$wp_meta_boxes = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
			as_role( 'admin' );
			\spokares_event_boxes( get_post( post_id( 'spk_event', 'set-2026' ) ) );
			assert_true( isset( $wp_meta_boxes['spk_event']['side']['default']['spokares_checking'] ), 'the webmaster keeps the box' );
			assert_true( isset( \spokares_event_columns( array() )['spk_check'] ), 'the webmaster keeps the column' );
		} finally {
			$wp_meta_boxes = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);

/* ------------------------------------------------------ one notice a save */

test(
	'one notice per event save: Publish and Save say where it shows, WordPress\'s own message is dropped; a draft gets "Draft saved"',
	function () {
		as_role( 'ares-net' );
		$id  = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$id,
			array(
				'post_title'    => 'UX training night',
				'spk_kind'      => 'training',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 75 ),
			),
			'publish'
		);
		assert_same( 'publish', get_post_status( $id ), 'published' );
		assert_false( isset( uxevt_query( $res )['message'] ), 'WordPress\'s message is dropped: ' . $res['redirect'] );
		assert_count( 1, $res['notices'], 'one notice: ' . export( $res['notices'] ) );
		assert_same( 'success', $res['notices'][0]['type'], 'success' );
		assert_same( 'Published. It shows under Later this season on the Exercises & events page.', $res['notices'][0]['text'], 'the notice' );
		assert_same( 'See it', $res['notices'][0]['label'], 'the link words' );
		assert_contains( '/members/exercises/#ux-training-night', $res['notices'][0]['url'], 'the link goes to its row' );

		$res = uxevt_click( $id, array(), 'save' );
		assert_false( isset( uxevt_query( $res )['message'] ), 'Save: WordPress\'s message is dropped' );
		assert_same( array( 'Saved. It shows under Later this season on the Exercises & events page.' ), wp_list_pluck( $res['notices'], 'text' ), 'Save: one notice' );

		// Save draft: no notice of ours, so WordPress's message 10 is the one notice.
		$draft = get_default_post_to_edit( 'spk_event', true )->ID;
		$res   = uxevt_click( $draft, array( 'post_title' => 'UX half-filled draft' ), 'saveasdraft' );
		assert_same( 'draft', get_post_status( $draft ), 'a draft' );
		assert_same( '10', (string) ( uxevt_query( $res )['message'] ?? '' ), 'message 10' );
		assert_same( array(), $res['notices'], 'no notice of ours' );
		$GLOBALS['post'] = get_post( $draft ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- as the edit screen sets it.
		$messages        = apply_filters( 'post_updated_messages', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		unset( $GLOBALS['post'] );
		assert_same( 'Draft saved. It isn’t on the site until you Publish.', $messages['spk_event'][10], 'the draft message' );
		assert_contains( 'See it on the Exercises &amp; events page', $messages['spk_event'][1], 'the saved message\'s link' );
	}
);

test(
	'Publish refused: one red notice (no green one), and the sentence under the field',
	function () {
		as_role( 'ares-editor' );
		$id  = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$id,
			array(
				'post_title'    => 'UX no type',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 20 ),
			),
			'publish'
		);
		assert_same( 'draft', get_post_status( $id ), 'kept off the site' );
		assert_false( isset( uxevt_query( $res )['message'] ), 'no "Draft saved" beside the red notice' );
		assert_same( array( 'error' ), wp_list_pluck( $res['notices'], 'type' ), 'one red notice' );
		assert_same( 'Not published yet: pick the type of event, then click Publish.', $res['notices'][0]['text'], 'the notice' );
		$html = uxevt_form( $id );
		assert_matches( '#<fieldset class="spk-card spk-kind-box spk-field-error" id="spk-kind">#', $html, 'the type box is outlined' );
		assert_contains( '<span class="spk-error-text">Pick the type of event.</span>', $html, 'the sentence under the type' );

		// Two problems: the notice points at the outlined boxes.
		$res = uxevt_click(
			$id,
			array(
				'post_title'   => '',
				'spk_kind'     => 'exercise',
				'spk_main_url' => 'not an address',
			),
			'publish'
		);
		assert_same( array( 'Not published yet. Fix the boxes outlined in red, then click Publish.' ), wp_list_pluck( $res['notices'], 'text' ), 'several problems' );
		$html = uxevt_form( $id );
		assert_contains( 'Type a web address, like https://www.arrl.org/…', $html, 'the address sentence' );
	}
);

test(
	'a web address typed without https:// is saved with it; a published event\'s field that isn\'t saved is named by its label',
	function () {
		as_role( 'ares-editor' );
		$id = get_default_post_to_edit( 'spk_event', true )->ID;
		uxevt_click(
			$id,
			array(
				'post_title'    => 'UX address',
				'spk_kind'      => 'exercise',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 80 ),
				'spk_main_url'  => 'www.arrl.org/kids-day',
				'spk_links'     => array(
					array(
						'label' => 'Forms',
						'url'   => 'www.arrl.org/forms',
					),
				),
			),
			'publish'
		);
		assert_same( 'publish', get_post_status( $id ), 'published' );
		assert_same( 'https://www.arrl.org/kids-day', get_post_meta( $id, 'spk_main_url', true ), 'the main address' );
		assert_same( 'https://www.arrl.org/forms', get_post_meta( $id, 'spk_links', true )[0]['url'] ?? '', 'the extra link' );

		$res = uxevt_click( $id, array( 'spk_where' => 'EOC, call 509-555-0142' ), 'save' );
		assert_same( 'publish', get_post_status( $id ), 'still on the site' );
		assert_same( array( 'warning' ), wp_list_pluck( $res['notices'], 'type' ), 'one amber notice, as on the other screens' );
		assert_same( 'Saved, except Where: it has a phone number. Take it out, or tick the box if it’s a public agency number. Everything else is on the site now.', $res['notices'][0]['text'], 'the notice names the field by its label' );
		assert_same( '', (string) get_post_meta( $id, 'spk_where', true ), 'Where is not saved' );

		// The typing stays in the form until the next save of the event, not one page view.
		assert_contains( 'value="EOC, call 509-555-0142"', uxevt_form( $id ), 'kept on the first view' );
		assert_contains( 'value="EOC, call 509-555-0142"', uxevt_form( $id ), 'kept on the next view too' );
		uxevt_click( $id, array( 'spk_where' => 'EOC' ), 'save' );
		assert_same( 'EOC', get_post_meta( $id, 'spk_where', true ), 'saved once fixed' );
		assert_not_contains( '509-555-0142', uxevt_form( $id ), 'the old typing is gone after the save' );
	}
);

test(
	'Volunteer through: a name with the call sign saves the call sign and says so in the same notice',
	function () {
		as_role( 'ares-editor' );
		$id  = get_default_post_to_edit( 'spk_event', true )->ID;
		$res = uxevt_click(
			$id,
			array(
				'post_title'       => 'UX parade',
				'spk_kind'         => 'public-service',
				'spk_date_mode'    => 'as-requested',
				'spk_contact_call' => 'Frank NV2Z',
			),
			'publish'
		);
		assert_same( 'NV2Z', get_post_meta( $id, 'spk_contact_call', true ), 'the call sign only' );
		assert_same( array( 'Published. It shows under Public-service events on the Exercises & events page. (saved NV2Z only; names aren’t posted)' ), wp_list_pluck( $res['notices'], 'text' ), 'one notice' );

		// As requested belongs to Public service: with another type it is saved as Date not posted yet.
		uxevt_click(
			$id,
			array(
				'spk_kind'      => 'training',
				'spk_date_mode' => 'as-requested',
			),
			'save'
		);
		assert_same( 'not-posted', get_post_meta( $id, 'spk_date_mode', true ), 'as-requested with another type' );
	}
);

test(
	'the redirect filter drops WordPress\'s message for events and documents when a notice is queued',
	function () {
		as_role( 'admin' );
		$event = post_id( 'spk_event', 'set-2026' );
		$doc   = post_id( 'spk_document', 'ics-213' );
		$page  = page_id( 'about' );
		$draft = uxevt_event( 'UX draft', array(), 'draft' );
		$loc   = static fn( int $id ): string => (string) apply_filters( 'redirect_post_location', admin_url( 'post.php?post=' . $id . '&action=edit&message=1' ), $id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.

		$GLOBALS['spokares_notice_queued'] = true;
		assert_not_contains( 'message=', $loc( $event ), 'event: WordPress\'s message dropped' );
		assert_not_contains( 'message=', $loc( $doc ), 'document: WordPress\'s message dropped' );
		assert_contains( 'message=1', $loc( $page ), 'a page is left alone' );

		unset( $GLOBALS['spokares_notice_queued'] );
		assert_contains( 'message=1', $loc( $event ), 'no notice queued: the published event keeps message 1' );
		assert_contains( 'message=10', $loc( $draft ), 'no notice queued: a draft gets message 10' );

		// A save starts with no notice queued (an earlier notice in the same request doesn't count).
		$GLOBALS['spokares_notice_queued'] = true;
		wp_update_post(
			array(
				'ID'         => $draft,
				'post_title' => 'UX draft again',
			)
		);
		assert_false( isset( $GLOBALS['spokares_notice_queued'] ), 'the flag is reset when an event save starts' );
	}
);

/* ----------------------------------------------------- postponed, cancelled */

test(
	'Postponed and Date not posted yet: undated, listed under Later this season, never Next up; the When text says which',
	function () {
		$postponed = uxevt_event(
			'UX postponed exercise',
			array(
				'spk_kind'      => 'exercise',
				'spk_date_mode' => 'postponed',
				'spk_start'     => uxevt_day( 5 ),
			)
		);
		$ev        = \spokares_event_data( $postponed );
		assert_same( 'postponed', $ev['mode'], 'mode' );
		assert_same( 'Postponed', \spokares_fmt_when( $ev, 'row' ), 'row' );
		assert_same( 'Postponed', \spokares_fmt_when( $ev, 'card' ), 'card' );
		assert_same( '9999-12-31', get_post_meta( $postponed, 'spk_sort', true ), 'sorts with the undated events' );
		$next = array(
			'types' => 'exercise',
			'limit' => 0,
		);
		assert_false( uxevt_in( $postponed, 'next-up', $next ), 'not in Next up' );
		assert_true(
			uxevt_in(
				$postponed,
				'later',
				array(
					'types'     => 'exercise,training,on-air',
					'cardTypes' => 'exercise',
					'cardLimit' => 2,
				)
			),
			'in Later this season'
		);
		assert_same( 'Later this season', uxevt_where( $postponed ), 'Where it shows' );
		assert_same( 'Saved. It shows as Postponed on the Exercises & events page.', \spokares_event_places_sentence( $postponed ), 'the notice' );

		$unposted = uxevt_event(
			'UX date not posted',
			array(
				'spk_kind'      => 'training',
				'spk_date_mode' => 'not-posted',
			)
		);
		assert_same( 'Date not posted yet', \spokares_fmt_when( \spokares_event_data( $unposted ), 'row' ), 'row' );
		update_post_meta( $unposted, 'spk_kind', 'public-service' );
		update_post_meta( $unposted, 'spk_date_mode', 'as-requested' );
		assert_same( 'As requested', \spokares_fmt_when( \spokares_event_data( $unposted ), 'row' ), 'As requested stays' );
	}
);

test(
	'Cancelled: kept in Later this season and This week (with the tag), never in Next up or Past exercises; only on a date',
	function () {
		$id = uxevt_event(
			'UX cancelled exercise',
			array(
				'spk_kind'      => 'exercise',
				'spk_start'     => uxevt_day( 3 ),
				'spk_keep_past' => '1',
				'spk_cancelled' => '1',
			)
		);
		assert_true( \spokares_event_data( $id )['cancelled'], 'cancelled' );
		$next = array(
			'types' => 'exercise',
			'limit' => 0,
		);
		assert_false( uxevt_in( $id, 'next-up', $next ), 'not in Next up' );
		assert_true(
			uxevt_in(
				$id,
				'later',
				array(
					'types'     => 'exercise,training,on-air',
					'cardTypes' => 'exercise',
					'cardLimit' => 2,
				)
			),
			'in Later this season'
		);
		assert_true(
			uxevt_in(
				$id,
				'upcoming',
				array(
					'types' => 'exercise,training',
					'days'  => 60,
				)
			),
			'in This week\'s list'
		);
		assert_true( uxevt_in( $id, 'all-upcoming' ), 'in all-upcoming' );
		assert_matches( '/^Cancelled · Later this season/', uxevt_where( $id ), 'Where it shows' );
		assert_same( 'Saved. Members see “Cancelled” until ' . \spokares_fmt_date( uxevt_day( 3 ), 'short' ) . '.', \spokares_event_places_sentence( $id ), 'the notice' );

		update_post_meta( $id, 'spk_date_mode', 'postponed' );
		assert_false( \spokares_event_data( $id )['cancelled'], 'a postponed event is not "cancelled"' );

		$past = uxevt_event(
			'UX cancelled past exercise',
			array(
				'spk_kind'      => 'exercise',
				'spk_start'     => uxevt_day( -40 ),
				'spk_keep_past' => '1',
				'spk_cancelled' => '1',
			)
		);
		assert_false( uxevt_in( $past, 'past' ), 'never in Past exercises' );
		delete_post_meta( $past, 'spk_cancelled' );
		assert_true( uxevt_in( $past, 'past' ), 'control: kept when it went ahead' );
	}
);

test(
	'the Cancelled tick: saved from a published event\'s form, only when On a date; the notice says until when',
	function () {
		as_role( 'ares-editor' );
		$id = get_default_post_to_edit( 'spk_event', true )->ID;
		uxevt_click(
			$id,
			array(
				'post_title'    => 'UX drill',
				'spk_kind'      => 'exercise',
				'spk_date_mode' => 'date',
				'spk_start'     => uxevt_day( 12 ),
			),
			'publish'
		);
		assert_contains( 'name="spk_cancelled"', uxevt_form( $id ), 'the tick on a published event' );
		$res = uxevt_click( $id, array( 'spk_cancelled' => '1' ), 'save' );
		assert_same( '1', get_post_meta( $id, 'spk_cancelled', true ), 'stored' );
		assert_same( array( 'Saved. Members see “Cancelled” until ' . \spokares_fmt_date( uxevt_day( 12 ), 'short' ) . '.' ), wp_list_pluck( $res['notices'], 'text' ), 'the notice' );

		uxevt_click( $id, array( 'spk_cancelled' => null ), 'save' );
		assert_same( '', get_post_meta( $id, 'spk_cancelled', true ), 'unticked: back on' );

		uxevt_click(
			$id,
			array(
				'spk_cancelled' => '1',
				'spk_date_mode' => 'postponed',
			),
			'save'
		);
		assert_same( '', get_post_meta( $id, 'spk_cancelled', true ), 'not stored for a postponed event' );
		$res = uxevt_click( $id, array(), 'save' );
		assert_same( array( 'Saved. It shows as Postponed on the Exercises & events page.' ), wp_list_pluck( $res['notices'], 'text' ), 'the postponed notice' );
	}
);

/* ------------------------------------------------------------ where it shows */

test(
	'Where it shows: the page sections, the For members page, Happening now, Not listed, Draft and In the Trash',
	function () {
		as_role( 'ares-editor' );
		assert_same( 'Next up · For members page', uxevt_where( post_id( 'spk_event', 'set-2026' ) ), 'SET' );
		assert_same( 'Public-service events', uxevt_where( post_id( 'spk_event', 'bloomsday-2027' ) ), 'Bloomsday' );
		assert_same( 'Later this season', uxevt_where( post_id( 'spk_event', 'srd-2026' ) ), 'SKYWARN Recognition Day' );
		assert_same( 'Past exercises', uxevt_where( post_id( 'spk_event', 'past-2022-10-rockford-exercise' ) ), 'a kept past exercise' );

		$now = uxevt_event(
			'UX running exercise',
			array(
				'spk_kind'  => 'exercise',
				'spk_start' => uxevt_day( -1 ),
				'spk_end'   => uxevt_day( 1 ),
			)
		);
		assert_matches( '#^<strong>Happening now</strong> · Next up#', uxevt_where( $now ), 'happening now' );

		$ended = uxevt_event(
			'UX ended training',
			array(
				'spk_kind'  => 'training',
				'spk_start' => uxevt_day( -10 ),
			)
		);
		assert_same( 'Not listed', uxevt_where( $ended ), 'ended' );
		assert_same( 'Saved, but ' . \spokares_fmt_date( uxevt_day( -10 ), 'long' ) . ' has passed, so no list shows it. Check the year.', \spokares_event_places_sentence( $ended ), 'past date notice' );
		$exercise = uxevt_event(
			'UX ended exercise not kept',
			array(
				'spk_kind'  => 'exercise',
				'spk_start' => uxevt_day( -10 ),
			)
		);
		assert_same( 'Saved. It isn’t listed anywhere now: it has ended and isn’t kept under Past exercises.', \spokares_event_places_sentence( $exercise ), 'ended and not kept' );

		$draft = uxevt_event( 'UX draft', array( 'spk_kind' => 'training' ), 'draft' );
		assert_same( 'Draft (not on the site)', uxevt_where( $draft ), 'draft' );
		wp_trash_post( $ended );
		assert_same( 'In the Trash', uxevt_where( $ended ), 'trash' );
		ob_start();
		\spokares_event_column( 'spk_kind', $draft );
		assert_same( 'Training or meeting', (string) ob_get_clean(), 'Type of event column' );
	}
);

/* ----------------------------------------------------- make a copy, trash */

test(
	'Make a copy: the row action and the Save box say it; the copy opens with First day outlined and no sentences',
	function () {
		as_role( 'ares-editor' );
		$set     = post_id( 'spk_event', 'set-2026' );
		$core    = array(
			'edit'  => '<a>Edit</a>',
			'trash' => '<a>Trash</a>',
		);
		$actions = \spokares_event_row_actions( $core, get_post( $set ) );
		assert_same( array( 'edit', 'duplicate', 'view-site' ), array_keys( $actions ), 'Edit · Make a copy · View on site (no Trash)' );
		assert_contains( '>Make a copy</a>', $actions['duplicate'], 'the copy link' );

		ob_start();
		\spokares_event_save_box( get_post( $set ) );
		$box = (string) ob_get_clean();
		assert_matches( '#<p class="spk-savebox-link"><a href="[^"]*action=spokares_duplicate_event[^"]*">Make a copy</a></p>#', $box, 'Make a copy in the Save box' );

		$res = get_action( 'spokares_duplicate_event', array( 'post' => (string) $set ), array( 'nonce_action' => 'spokares_duplicate_event_' . $set ) );
		assert_matches( '/post\.php\?post=(\d+)&action=edit/', (string) $res['redirect'], 'opens the copy' );
		preg_match( '/post=(\d+)/', (string) $res['redirect'], $m );
		$copy = (int) $m[1];
		assert_same( array( 'Copied. Set the new date, then Publish.' ), wp_list_pluck( uxevt_notices(), 'text' ), 'the notice' );
		$html = uxevt_form( $copy );
		assert_not_contains( 'spk-error-text', $html, 'no sentences on a fresh copy' );
		assert_matches( '#<input type="date" id="spk-start"[^>]*class="spk-field-error"#', $html, 'First day is outlined' );
		assert_not_contains( 'spk-kind-box spk-field-error', $html, 'the type is copied' );

		// From its first save on, the problems show.
		uxevt_click( $copy, array(), 'saveasdraft' );
		assert_contains( 'Pick the first day.', uxevt_form( $copy ), 'after the first save' );
	}
);

test(
	'Take it off the site and Restore: the list names the event, and Restore brings it back as it was',
	function () {
		as_role( 'ares-editor' );
		$set   = post_id( 'spk_event', 'set-2026' );
		$title = get_the_title( $set );
		$r     = call_request( 'GET', array( 'ids' => (string) $set ), array(), static fn() => \spokares_bulk_message( true, 'trashed', 1 ) );
		assert_same( '“' . $title . '” is off the site.', (string) $r['returned'], 'trashed' );
		wp_trash_post( $set );
		wp_untrash_post( $set );
		assert_same( 'publish', get_post_status( $set ), 'Restore brings back the previous status' );
		// Core's redirect after Restore or Undo (…&untrashed=1) doesn't name the event: the list remembered it.
		$notice = static fn(): string => (string) ( call_request(
			'GET',
			array(
				'post_type' => 'spk_event',
				'untrashed' => '1',
			),
			array(),
			static fn() => apply_filters( 'bulk_post_updated_messages', array(), array( 'untrashed' => 1 ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter.
		)['returned']['spk_event']['untrashed'] ?? '' );
		assert_same( '“' . $title . '” is back on the site.', $notice(), 'restored' );
		assert_same( '1 event is back on the site.', str_replace( '%s', '1', $notice() ), 'read once: a later notice has no restored event to name' );
		$r = call_request( 'GET', array( 'ids' => $set . ',' . post_id( 'spk_event', 'srd-2026' ) ), array(), static fn() => \spokares_bulk_message( true, 'trashed', 2 ) );
		assert_same( '%s events are off the site.', (string) $r['returned'], 'two at once: WordPress fills in the number' );
	}
);

/* -------------------------------------------------------------- meetings */

/**
 * Save Cancel or Move a Meeting with these rows; return the notices and the
 * kept typing.
 *
 * @param array $m Posted m[meeting][date] rows.
 */
function uxevt_save_meetings( array $m ): array {
	uxevt_notices();
	$res = post_form( 'spokares_save_meetings', array( 'm' => $m ) );
	assert_contains( 'page=spokares-meetings', (string) $res['redirect'], 'back to Cancel or Move a Meeting' );
	\spokares_unlock_option( 'spk_meetings' );
	\spokares_opt_flush();
	return array(
		'notices'  => uxevt_notices(),
		'retained' => \spokares_retained( 'spokares-meetings' ),
	);
}

/**
 * One posted row, as the browser sends it.
 *
 * @param string $mid       Meeting.
 * @param string $date      Rule date.
 * @param array  $row       cancelled, moved, note.
 */
function uxevt_row( string $mid, string $date, array $row ): array {
	return array_merge( array( 'h' => \spokares_change_hash( \spokares_meeting_change( $mid, $date ) ) ), $row );
}

test(
	'a note on its own is a one-date change: stored as a note, listed as the date with its note; unticking Cancelled takes a cancellation back',
	function () {
		as_role( 'ares-editor' );
		$date  = \spokares_meeting_admin_dates( \spokares_opt( 'spk_meetings' )['meetings'][0] )[1];
		$label = \spokares_fmt_date( $date, 'short' );
		$note  = 'Starts at 10:00 AM this time';

		$out = uxevt_save_meetings( array( 'workshop' => array( $date => uxevt_row( 'workshop', $date, array( 'note' => $note ) ) ) ) );
		$c   = \spokares_meeting_change( 'workshop', $date );
		assert_same( 'note', (string) ( $c['kind'] ?? '' ), 'a note change' );
		assert_same( $note, (string) ( $c['note'] ?? '' ), 'its note' );
		assert_count( 1, $out['notices'], 'one notice' );
		assert_same( 'Saved: ' . $label . ' note posted.', $out['notices'][0]['text'], 'the notice' );
		assert_same( 'See it on the Home page', $out['notices'][0]['label'], 'the link' );

		$item = null;
		foreach ( \spokares_next_meetings( 1, $date ) as $x ) {
			if ( 'workshop' === $x['meeting']['id'] ) {
				$item = $x;
			}
		}
		assert_same(
			array(
				'date' => $date,
				'orig' => $date,
				'kind' => 'note',
				'note' => $note,
			),
			$item['dates'][0] ?? null,
			'the date is on, with its note'
		);
		assert_same( array( 'note' ), wp_list_pluck( $item['changes'], 'kind' ), 'listed in changes' );

		// Cancel it (the note stays with it), then untick Cancelled without the script: back on.
		$out = uxevt_save_meetings(
			array(
				'workshop' => array(
					$date => uxevt_row(
						'workshop',
						$date,
						array(
							'cancelled' => '1',
							'note'      => $note,
						)
					),
				),
			)
		);
		assert_same( 'cancelled', (string) ( \spokares_meeting_change( 'workshop', $date )['kind'] ?? '' ), 'cancelled' );
		assert_same( 'Saved: ' . $label . ' cancelled.', $out['notices'][0]['text'], 'cancelled notice' );
		$out = uxevt_save_meetings( array( 'workshop' => array( $date => uxevt_row( 'workshop', $date, array( 'note' => $note ) ) ) ) );
		assert_true( null === \spokares_meeting_change( 'workshop', $date ), 'the change is removed, not turned into a note' );
		assert_same( 'Saved: ' . $label . ' is back on.', $out['notices'][0]['text'], 'back on' );

		// Moved to the same date: the sentence under the row.
		$out = uxevt_save_meetings( array( 'workshop' => array( $date => uxevt_row( 'workshop', $date, array( 'moved' => $date ) ) ) ) );
		assert_same( array( 'Not saved: ' . $label . ' (outlined in red).' ), wp_list_pluck( $out['notices'], 'text' ), 'one error notice' );
		assert_same( 'Pick a different date. For a new time or room on the same day, use the Note alone.', $out['retained']['errors'][ 'workshop|' . $date ] ?? '', 'the sentence' );

		$out = uxevt_save_meetings( array( 'workshop' => array( $date => uxevt_row( 'workshop', $date, array() ) ) ) );
		assert_same( array( 'Nothing changed, so nothing was saved.' ), wp_list_pluck( $out['notices'], 'text' ), 'nothing changed' );
	}
);

test(
	'Cancel or Move a Meeting: its title, columns, Save button and leave guard; Meeting Schedule linked for the grant only',
	function () {
		as_role( 'ares-net' );
		ob_start();
		\spokares_meetings_page();
		$html = (string) ob_get_clean();
		assert_contains( '<h1 class="wp-heading-inline">Cancel or Move a Meeting</h1>', $html, 'title' );
		assert_matches( '#<a class="page-title-action" href="[^"]*page=spokares-meeting-rules">Meeting Schedule</a>#', $html, 'the h1 button' );
		preg_match_all( '#<th scope="col">(.*?)</th>#', $html, $cols );
		assert_same( array( 'Meeting', 'Date', 'Cancelled', 'Moved to', 'Note (shown with the date)' ), $cols[1], 'columns' );
		assert_matches( '#<form [^>]*data-spk-guard>#', $html, 'the leave guard' );
		assert_matches( '#<button type="submit" class="button button-primary button-large">Save</button>#', $html, 'Save' );
		assert_not_contains( 'Meeting times and weeks', $html, 'no foot cross-link' );

		$data = \spokares_opt( 'spk_meetings' );
		foreach ( $data['meetings'] as $i => $m ) {
			$data['meetings'][ $i ]['show_home'] = false;
		}
		update_option( 'spk_meetings', $data );
		ob_start();
		\spokares_meetings_page();
		$html = (string) ob_get_clean();
		assert_contains( 'No regular meetings are shown on the site.', $html, 'empty state' );
		assert_matches( '#No regular meetings are shown on the site\.\s*<a href="[^"]*page=spokares-meeting-rules">Meeting Schedule</a>#', $html, 'with the link for the grant' );
	}
);

/**
 * The Meeting Schedule screen's form, as the current user sees it.
 */
function uxevt_rules_screen(): string {
	\spokares_opt_flush();
	ob_start();
	\spokares_meeting_rules_page();
	$html = (string) ob_get_clean();
	if ( ! preg_match( '/<form\b.*?<\/form>/is', $html, $form ) ) {
		fail( 'Meeting Schedule has no form' );
	}
	return $form[0];
}

/**
 * The card index of a meeting in the posted fields ('' = Add a meeting).
 *
 * @param array  $fields Fields.
 * @param string $id     Meeting id.
 */
function uxevt_card( array $fields, string $id ): int {
	foreach ( (array) ( $fields['rules'] ?? array() ) as $i => $card ) {
		if ( is_array( $card ) && ( $card['id'] ?? '' ) === $id ) {
			return (int) $i;
		}
	}
	fail( 'no card for "' . $id . '"' );
	return -1;
}

/**
 * Save Meeting Schedule with these fields; return the notices.
 *
 * @param array $fields Fields.
 */
function uxevt_save_rules( array $fields ): array {
	uxevt_notices();
	unset( $fields['_wpnonce'], $fields['_wp_http_referer'] );
	$res = post_form( 'spokares_save_meeting_rules', $fields );
	assert_same( null, $res['die'], 'the save did not wp_die()' );
	\spokares_unlock_option( 'spk_meetings' );
	\spokares_opt_flush();
	return uxevt_notices();
}

/**
 * A stored meeting by id.
 *
 * @param string $id Meeting id.
 */
function uxevt_meeting( string $id ): array {
	\spokares_opt_flush();
	foreach ( \spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}
	fail( 'no meeting ' . $id );
	return array();
}

test(
	'Meeting Schedule: the card\'s fields in order, one "Show on the site" tick, "Takes effect on" last; the Add card the same',
	function () {
		as_role( 'ares-net' );
		ob_start();
		\spokares_meeting_rules_page();
		$page = (string) ob_get_clean();
		assert_contains( '<h1 class="wp-heading-inline">Meeting Schedule</h1>', $page, 'title' );
		assert_matches( '#<a class="page-title-action" href="[^"]*page=spokares-meetings">Cancel or Move a Meeting</a>#', $page, 'the h1 button' );
		assert_not_contains( 'spk-lede', $page, 'no lede' );
		assert_not_contains( 'data-spk-confirm', $page, 'no confirm dialog' );
		assert_matches( '#<form [^>]*data-spk-guard>#', $page, 'the leave guard' );
		assert_matches( '#<button type="submit" class="button button-primary button-large">Save</button>#', $page, 'Save' );

		$cards = preg_split( '/(?=<fieldset\b[^>]*\bspk-rule\b)/i', $page );
		$first = uxevt_labels( $cards[1] );
		$last  = uxevt_labels( end( $cards ) );
		$want  = array( 'Name', 'Week of the month', 'Day', 'Start', 'End', 'Time as words', 'Extra words on Home (after the time)', 'Skip these months', 'Show on the site', 'Takes effect on' );
		$strip = static fn( array $l ): array => array_values( array_filter( $l, static fn( $x ) => ! in_array( $x, array( '1st', '2nd', '3rd', '4th', '5th', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Second Saturday Workshop', 'Add a meeting' ), true ) ) );
		assert_same( $want, $strip( $first ), 'a meeting card' );
		assert_same( $want, $strip( $last ), 'the Add a meeting card' );
		assert_matches( '#placeholder="evenings"#', $cards[1], 'Time as words placeholder' );
		assert_contains( 'Leave empty for now', end( $cards ), 'the hint on the Add card too' );
		assert_matches( '#name="rules\[\d+\]\[show_home\]" value="1"\s+checked#', end( $cards ), 'Show on the site is ticked on the Add card' );
		assert_not_contains( '[active]', $page, 'no second "Active" tick' );
		assert_not_contains( 'Needs checking', $page, 'no webmaster flag for an editor' );
		assert_not_contains( 'administrator', $page, 'never "administrator"' );
		// The hidden Winlink workshop (active, not shown) reads unticked.
		$fields = uxevt_fields( uxevt_rules_screen() );
		assert_false( isset( $fields['rules'][ uxevt_card( $fields, 'winlink-workshop' ) ]['show_home'] ), 'active but hidden shows unticked' );
	}
);

test(
	'Show on the site: one tick writes show_home and active; left as it was, both stay as stored; a hidden meeting is said',
	function () {
		as_role( 'ares-net' );
		$fields  = uxevt_fields( uxevt_rules_screen() );
		$notices = uxevt_save_rules( $fields );
		assert_same( array( 'Nothing changed, so nothing was saved.' ), wp_list_pluck( $notices, 'text' ), 'unchanged' );
		$ww = uxevt_meeting( 'winlink-workshop' );
		assert_same( array( false, true ), array( $ww['show_home'], $ww['active'] ), 'the hidden Winlink workshop keeps active' );

		$fields = uxevt_fields( uxevt_rules_screen() );
		unset( $fields['rules'][ uxevt_card( $fields, 'workshop' ) ]['show_home'] );
		$notices = uxevt_save_rules( $fields );
		$m       = uxevt_meeting( 'workshop' );
		assert_same( array( false, false ), array( $m['show_home'], $m['active'] ), 'both off' );
		assert_same( array( 'Saved. Second Saturday Workshop isn’t on the site: tick Show on the site.' ), wp_list_pluck( $notices, 'text' ), 'the notice' );

		$fields = uxevt_fields( uxevt_rules_screen() );
		$fields['rules'][ uxevt_card( $fields, 'workshop' ) ]['show_home'] = '1';
		$notices = uxevt_save_rules( $fields );
		$m       = uxevt_meeting( 'workshop' );
		assert_same( array( true, true ), array( $m['show_home'], $m['active'] ), 'both on' );
		assert_matches( '/^Saved\. Next Second Saturday Workshop: \w{3}, \w{3} \d+\.$/', (string) ( $notices[0]['text'] ?? '' ), 'the notice names the next date' );
	}
);

test(
	'Takes effect on: a pattern change from a later date is stored as next, the dates switch on that date, and it can be dropped',
	function () {
		as_role( 'ares-net' );
		$from   = gmdate( 'Y-m-01', (int) strtotime( uxevt_day( 70 ) ) );
		$fields = uxevt_fields( uxevt_rules_screen() );
		$i      = uxevt_card( $fields, 'third-thursday' );

		$fields['rules'][ $i ]['nth']  = array( '4' );
		$fields['rules'][ $i ]['from'] = $from;
		$notices                       = uxevt_save_rules( $fields );

		$m = uxevt_meeting( 'third-thursday' );
		assert_same( array( 3 ), $m['nth'], 'the rule in effect now is unchanged' );
		assert_same( $from, $m['next']['from'] ?? '', 'the change is kept with its date' );
		assert_same( array( 4 ), $m['next']['nth'] ?? array(), 'the new weeks' );
		$first = \spokares_meeting_first_date( $m['next'], $from );
		assert_same( 4, \spokares_nth( $first ), 'first on a 4th Thursday' );
		assert_same(
			array( 'Saved. From ' . \spokares_fmt_date( $from, 'long' ) . ': Third Thursday training meeting, first on ' . \spokares_fmt_date( $first, 'short-noyear' ) . '.' ),
			wp_list_pluck( $notices, 'text' ),
			'the notice'
		);

		// The pattern in effect on each date.
		foreach ( \spokares_meeting_rule_dates( $m, \spokares_today(), \spokares_add_days( $from, 120 ) ) as $d ) {
			assert_same( $d < $from ? 3 : 4, \spokares_nth( $d ), $d . ': the week in effect on that date' );
			assert_true( \spokares_meeting_on( $m, $d ), $d . ': a meeting date' );
		}
		$after = \spokares_next_meetings( 1, $from );
		foreach ( $after as $item ) {
			if ( 'third-thursday' === $item['meeting']['id'] ) {
				assert_same( array( 4 ), $item['meeting']['nth'], 'Home describes the pattern in effect on the next date' );
				assert_true( $item['meeting']['show_home'], 'with the stored switches' );
				assert_false( isset( $item['meeting']['next'] ), 'and no pending change' );
			}
		}

		// The card: the stored pattern, one line for the change, and "Drop this change".
		$html = uxevt_rules_screen();
		assert_contains( 'From ' . \spokares_fmt_date( $from, 'long' ) . ': Third Thursday training meeting, 4th Thursday, evenings.', $html, 'the pending line' );
		assert_contains( '[drop_next]', $html, 'the Drop this change tick' );

		// On its date the change becomes the rule.
		$now = \spokares_normalize_meeting( array_merge( $m, array( 'next' => array_merge( $m['next'], array( 'from' => \spokares_today() ) ) ) ) );
		assert_same( array( 4 ), $now['nth'], 'merged on its date' );
		assert_false( isset( $now['next'] ), 'and dropped' );

		$fields = uxevt_fields( $html );
		$fields['rules'][ uxevt_card( $fields, 'third-thursday' ) ]['drop_next'] = '1';
		uxevt_save_rules( $fields );
		assert_false( isset( uxevt_meeting( 'third-thursday' )['next'] ), 'dropped' );
	}
);

test(
	'a pattern change now removes the cancels, moves and notes on dates that are no longer meeting dates, and says so',
	function () {
		as_role( 'ares-net' );
		$dates = \spokares_meeting_admin_dates( uxevt_meeting( 'third-thursday' ) );
		$moved = \spokares_add_days( $dates[1], -1 );
		uxevt_save_meetings( array( 'third-thursday' => array( $dates[1] => uxevt_row( 'third-thursday', $dates[1], array( 'moved' => $moved ) ) ) ) );
		assert_same( 'moved', (string) ( \spokares_meeting_change( 'third-thursday', $dates[1] )['kind'] ?? '' ), 'set-up: moved' );

		$fields = uxevt_fields( uxevt_rules_screen() );
		$fields['rules'][ uxevt_card( $fields, 'third-thursday' ) ]['nth'] = array( '4' );
		$notices = uxevt_save_rules( $fields );
		assert_true( null === \spokares_meeting_change( 'third-thursday', $dates[1] ), 'the move is removed' );
		assert_count( 1, $notices, 'one notice' );
		assert_contains( \spokares_fmt_date( $dates[1], 'day' ) . ' is no longer a meeting date, so its move to ' . \spokares_fmt_date( $moved, 'day' ) . ' was removed.', $notices[0]['text'], 'named' );
		assert_matches( '/^Saved\. Next Third Thursday training meeting: /', $notices[0]['text'], 'with the next date' );
	}
);

test(
	'a new meeting with a later "Takes effect on" has no dates before it, and its card shows the coming pattern',
	function () {
		as_role( 'ares-net' );
		$from   = gmdate( 'Y-m-01', (int) strtotime( uxevt_day( 70 ) ) );
		$fields = uxevt_fields( uxevt_rules_screen() );
		$new    = uxevt_card( $fields, '' );

		$fields['rules'][ $new ]['name']    = 'UX first-Monday practice';
		$fields['rules'][ $new ]['nth']     = array( '1' );
		$fields['rules'][ $new ]['weekday'] = '1';
		$fields['rules'][ $new ]['start']   = '19:00';
		$fields['rules'][ $new ]['from']    = $from;
		$notices                            = uxevt_save_rules( $fields );

		$m = uxevt_meeting( 'ux-first-monday-practice' );
		assert_same( array(), $m['nth'], 'no weeks before its date' );
		assert_same( array(), \spokares_meeting_rule_dates( $m, \spokares_today(), \spokares_add_days( $from, -1 ) ), 'no dates before it' );
		assert_true( count( \spokares_meeting_rule_dates( $m, $from, \spokares_add_days( $from, 60 ) ) ) > 0, 'dates from it on' );
		assert_matches( '/^Saved\. From .*: UX first-Monday practice, first on Mon, /', (string) ( $notices[0]['text'] ?? '' ), 'the notice' );

		$fields = uxevt_fields( uxevt_rules_screen() );
		$card   = $fields['rules'][ uxevt_card( $fields, 'ux-first-monday-practice' ) ];
		assert_same( array( '1' ), $card['nth'] ?? array(), 'the card shows the coming weeks' );
		assert_same( $from, $card['from'] ?? '', 'and its date in Takes effect on' );
		$again = uxevt_save_rules( $fields );
		assert_same( array( 'Nothing changed, so nothing was saved.' ), wp_list_pluck( $again, 'text' ), 'saving it unchanged changes nothing' );
	}
);

/* ------------------------------------------------------------ data layer */

test(
	'the data layer: No net rows, the note change kind, the pending change, labels and the asking-for-volunteers line',
	function () {
		$row = \spokares_normalize_rota_row(
			array(
				'state' => 'none',
				'call'  => 'NZ2S',
				'note'  => 'Holiday',
			)
		);
		assert_same( array( 'none', '', 'Holiday' ), array( $row['state'], $row['call'], $row['note'] ), 'No net keeps its state and drops the call sign' );
		$clean = \spokares_sanitize_opt_rota( array( '2026-10-06' => array( 'state' => 'none' ) ) );
		assert_same( 'none', $clean['2026-10-06']['state'], 'the sanitizer keeps No net' );
		assert_contains( 'Volunteer needed', \spokares_option_defaults()['spk_nets']['open_slot_line'], 'the default line' );

		$clean = \spokares_sanitize_opt_meetings(
			array(
				'meetings' => array(
					array(
						'id'   => 'x',
						'name' => 'X',
						'nth'  => array( 2 ),
						'next' => array(
							'from'      => '2099-01-01',
							'nth'       => array( 4 ),
							'time_text' => '<b>evenings</b>',
						),
					),
				),
				'changes'  => array(
					array(
						'date'    => '2026-11-14',
						'meeting' => 'x',
						'kind'    => 'note',
						'note'    => 'Room 2',
					),
				),
			)
		);
		assert_same( 'note', $clean['changes'][0]['kind'], 'a note change is kept' );
		assert_same( array( 4 ), $clean['meetings'][0]['next']['nth'], 'a pending change is kept' );
		assert_same( 'evenings', $clean['meetings'][0]['next']['time_text'], 'and cleaned' );
		assert_same( 'X', $clean['meetings'][0]['next']['name'], 'its name defaults to the meeting\'s' );
		$bad = \spokares_normalize_meeting(
			array(
				'id'   => 'x',
				'nth'  => array( 2 ),
				'next' => array( 'from' => 'soon' ),
			)
		);
		assert_false( isset( $bad['next'] ), 'an invalid pending change is dropped' );

		$labels = get_post_type_object( 'spk_event' )->labels;
		assert_same(
			array( 'Exercises & Events', 'Exercises & Events', 'All Events', 'Add an Event', 'Edit Event', 'No events here.' ),
			array( $labels->name, $labels->menu_name, $labels->all_items, $labels->add_new_item, $labels->edit_item, $labels->not_found ),
			'event labels'
		);
		$labels = get_post_type_object( 'spk_document' )->labels;
		assert_same( array( 'All Documents', 'Add a Document', 'Edit Document' ), array( $labels->all_items, $labels->add_new_item, $labels->edit_item ), 'document labels' );
		assert_same( 'Sections', get_taxonomy( 'spk_doc_cat' )->labels->name, 'section labels' );
		assert_same( 'Section', get_taxonomy( 'spk_doc_cat' )->labels->singular_name, 'section labels' );
		assert_same(
			array(
				'exercise'       => 'Exercise',
				'training'       => 'Training or meeting',
				'on-air'         => 'On the air (from home stations)',
				'public-service' => 'Public service',
			),
			\spokares_event_kinds(),
			'types of event'
		);
		$meta = get_registered_meta_keys( 'post', 'spk_event' );
		assert_true( isset( $meta['spk_cancelled'] ), 'spk_cancelled is registered' );
		assert_true( isset( get_registered_meta_keys( 'post', 'spk_document' )['spk_file_name'] ), 'spk_file_name is registered' );
		assert_same( 'postponed', \spokares_sanitize_meta_date_mode( 'postponed' ), 'postponed is a When value' );
		assert_same( 'date', \spokares_sanitize_meta_date_mode( 'someday' ), 'anything else is On a date' );
	}
);
