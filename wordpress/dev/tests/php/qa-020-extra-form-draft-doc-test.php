<?php
/**
 * Regression tests for QA-020 (PLAN §3.4, the event form's "Extra form"):
 * the Extra form select lists only published documents. When an exercise's
 * Extra form document is briefly a Draft, its event's form opens on
 * "— none —", so an unrelated Update posts spk_extra_doc=0 and the stored
 * reference is deleted for good. Republishing the document then does not
 * bring the "Extra form:" link back on the SET card (/members/exercises/).
 * The reference must survive a save of the form as it was shown (the Hub
 * tiles screen keeps an unpublished choice, labelled "not shown").
 *
 * The saves post exactly what the rendered form would post, so the test
 * holds for either fix: offering the stored document in the select, or
 * not overwriting a document the select didn't offer.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The fields a browser would submit from the event form as rendered for
 * the current user now (the plugin's part of form#post: the kind box, the
 * nonce and the fields below the event name), plus the event name.
 *
 * @param int $id Event.
 */
function qa020_form_post( int $id ): array {
	$post = get_post( $id );
	ob_start();
	spokares_event_form_top( $post );
	spokares_event_form_fields( $post );
	$html = (string) ob_get_clean();

	$pairs  = array();
	$select = null;
	$chosen = null;
	$first  = null;
	$tags   = new \WP_HTML_Tag_Processor( $html );
	while ( $tags->next_token() ) {
		if ( '#tag' !== $tags->get_token_type() ) {
			continue;
		}
		$tag    = $tags->get_tag();
		$closer = $tags->is_tag_closer();
		if ( 'SELECT' === $tag && $closer ) {
			if ( null !== $select ) {
				$value = null !== $chosen ? $chosen : $first;
				if ( null !== $value ) {
					$pairs[] = array( $select, $value );
				}
			}
			$select = null;
			continue;
		}
		if ( $closer ) {
			continue;
		}
		$name     = $tags->get_attribute( 'name' );
		$disabled = null !== $tags->get_attribute( 'disabled' );
		if ( 'SELECT' === $tag ) {
			$select = ( is_string( $name ) && ! $disabled ) ? $name : null;
			$chosen = null;
			$first  = null;
		} elseif ( 'OPTION' === $tag && null !== $select ) {
			$value = (string) $tags->get_attribute( 'value' );
			if ( null === $first ) {
				$first = $value;
			}
			if ( null !== $tags->get_attribute( 'selected' ) ) {
				$chosen = $value;
			}
		} elseif ( 'TEXTAREA' === $tag && is_string( $name ) && ! $disabled ) {
			$pairs[] = array( $name, ltrim( $tags->get_modifiable_text(), "\n" ) );
		} elseif ( 'INPUT' === $tag && is_string( $name ) && ! $disabled ) {
			$type = strtolower( (string) $tags->get_attribute( 'type' ) );
			if ( in_array( $type, array( 'submit', 'button', 'image', 'file', 'reset' ), true ) ) {
				continue;
			}
			if ( in_array( $type, array( 'checkbox', 'radio' ), true ) && null === $tags->get_attribute( 'checked' ) ) {
				continue;
			}
			$value   = $tags->get_attribute( 'value' );
			$pairs[] = array( $name, is_string( $value ) ? $value : ( in_array( $type, array( 'checkbox', 'radio' ), true ) ? 'on' : '' ) );
		}
	}

	$query  = implode(
		'&',
		array_map(
			static fn( $p ) => rawurlencode( $p[0] ) . '=' . rawurlencode( $p[1] ),
			$pairs
		)
	);
	$fields = array();
	parse_str( $query, $fields );
	assert_true( isset( $fields['spokares_event_nonce'], $fields['spk_kind'] ), 'set-up: the rendered event form has its nonce and kind' );
	assert_true( array_key_exists( 'spk_extra_doc', $fields ), 'set-up: the rendered form has the Extra form select' );
	$fields['post_title'] = $post->post_title;
	return $fields;
}

/**
 * Save an event through its form, as the classic post.php save does: the
 * form's fields in $_POST, then wp_update_post().
 *
 * @param int   $id     Event.
 * @param array $fields Form fields (from qa020_form_post()).
 */
function qa020_save( int $id, array $fields ): void {
	$status = get_post_status( $id );
	$r      = call_request(
		'POST',
		array(),
		array_merge( $fields, array( 'original_post_status' => $status ) ),
		static function () use ( $id, $fields, $status ) {
			return wp_update_post(
				wp_slash(
					array(
						'ID'          => $id,
						'post_title'  => $fields['post_title'],
						'post_status' => $status,
					)
				),
				true
			);
		}
	);
	expect_not_wp_error( $r['returned'], 'save' );
	clean_post_cache( $id );
}

/**
 * Set a document's status as an administrator (the Documents screen's
 * Draft / Publish), then act as $role again.
 *
 * @param int    $doc    Document.
 * @param string $status 'draft' or 'publish'.
 * @param string $role   Role key to switch back to.
 */
function qa020_doc_status( int $doc, string $status, string $role ): void {
	as_role( 'admin' );
	expect_not_wp_error(
		wp_update_post(
			array(
				'ID'          => $doc,
				'post_status' => $status,
			),
			true
		),
		'set the document to ' . $status
	);
	clean_post_cache( $doc );
	assert_same( $status, get_post_status( $doc ), 'set-up: document is ' . $status );
	as_role( $role );
}

/**
 * The SET card's "Extra form:" line on /members/exercises/ (the Next up
 * cards), or '' when the card has none.
 */
function qa020_set_extra_line(): string {
	$html = spokares_render_events(
		array(
			'view'  => 'next-up',
			'types' => 'exercise',
			'limit' => 2,
		)
	);
	if ( ! preg_match( '/<article[^>]*\bid="set-2026"[^>]*>.*?<\/article>/s', $html, $card ) ) {
		fail( 'set-up: no SET card in the Next up cards' );
	}
	return preg_match( '/<p class="ex-form">.*?<\/p>/s', $card[0], $m ) ? $m[0] : '';
}

test(
	'updating an event while its Extra form document is a draft keeps the reference',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$set = post_id( 'spk_event', 'set-2026' );
			$doc = post_id( 'spk_document', 'wa-field-situation-report' );
			assert_same( $doc, absint( get_post_meta( $set, 'spk_extra_doc', true ) ), $role . ': set-up: SET has the Field Situation Report as its Extra form' );
			assert_contains( 'wa-field-situation-report', qa020_set_extra_line(), $role . ': set-up: SET card links the Extra form' );

			// The document is taken off the site for a moment.
			qa020_doc_status( $doc, 'draft', $role );
			assert_same( '', qa020_set_extra_line(), $role . ': a draft document is not linked from the card' );

			// The editor opens the SET event and presses Update without touching the Extra form.
			$fields = qa020_form_post( $set );
			qa020_save( $set, $fields );
			assert_same( 'publish', get_post_status( $set ), $role . ': SET still published' );
			assert_same( $doc, absint( get_post_meta( $set, 'spk_extra_doc', true ) ), $role . ': Extra form reference kept after an Update while the document was a draft (form posted spk_extra_doc=' . $fields['spk_extra_doc'] . ')' );

			// The document is published again: the link comes back.
			qa020_doc_status( $doc, 'publish', $role );
			assert_contains( 'wa-field-situation-report', qa020_set_extra_line(), $role . ': SET card links the Extra form again once the document is republished' );

			// A second, untouched Update keeps it too.
			qa020_save( $set, qa020_form_post( $set ) );
			assert_same( $doc, absint( get_post_meta( $set, 'spk_extra_doc', true ) ), $role . ': Extra form reference kept after a later Update' );
		}
	}
);

test(
	'control: an untouched Update keeps a published Extra form, and choosing none clears it',
	function () {
		as_role( 'ares-editor' );
		$set = post_id( 'spk_event', 'set-2026' );
		$doc = post_id( 'spk_document', 'wa-field-situation-report' );

		$fields = qa020_form_post( $set );
		assert_same( (string) $doc, (string) $fields['spk_extra_doc'], 'the form shows the published Extra form chosen' );
		qa020_save( $set, $fields );
		assert_same( $doc, absint( get_post_meta( $set, 'spk_extra_doc', true ) ), 'kept after an untouched Update' );

		$fields                  = qa020_form_post( $set );
		$fields['spk_extra_doc'] = '0';
		qa020_save( $set, $fields );
		assert_same( '', get_post_meta( $set, 'spk_extra_doc', true ), 'choosing "— none —" removes the Extra form' );
		assert_same( '', qa020_set_extra_line(), 'the SET card has no Extra form line' );
	}
);
