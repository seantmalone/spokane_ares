<?php
/**
 * Regression tests for QA-019 (PLAN §4.4 confirm rule): the event form's
 * "Where" field shows the "This is a public agency number. Publish it." tick
 * for a phone number, and a save with the tick goes through, but the tick is
 * never remembered. spokares_save_event() records the spk_confirmed hash only
 * for title, summary, tasks, main_label and links, while
 * spokares_event_problems() also checks 'where'. So every later save of a
 * published event holds the place back again ("The place has a phone
 * number…"), and a draft saved with the tick is demoted again on Publish.
 * Once ticked, the Where text is confirmed and must not be flagged again
 * until it changes.
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
function qa019_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * Save an event through its form, as the classic post.php save does: the
 * form's fields and nonce in $_POST, then wp_update_post().
 *
 * @param int    $id     Event.
 * @param array  $fields Form fields (post_title, spk_kind, spk_start, spk_where, spk_confirm…).
 * @param string $status publish or draft (the button pressed).
 */
function qa019_save( int $id, array $fields, string $status = 'publish' ): array {
	$post = array_merge(
		array(
			'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ),
			'spk_date_mode'        => 'date',
			'spk_all_day'          => '1',
			'original_post_status' => get_post_status( $id ),
		),
		$fields
	);

	$r = call_request(
		'POST',
		array(),
		$post,
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
	return $r;
}

/**
 * A new draft event for the test, as the current user.
 *
 * @param string $title Title.
 */
function qa019_new( string $title ): int {
	return create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'draft',
			'post_title'  => $title,
			'post_author' => get_current_user_id(),
		)
	);
}

/**
 * The error notices queued for the current user (and clear all notices).
 */
function qa019_errors(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	$notices = is_array( $notices ) ? $notices : array();
	return array_values( wp_list_pluck( array_filter( $notices, static fn( $n ) => 'error' === $n['type'] ), 'text' ) );
}

/**
 * The event form's fields as printed for the stored event.
 *
 * @param int $id Event.
 */
function qa019_form( int $id ): string {
	ob_start();
	spokares_event_form_fields( get_post( $id ) );
	return (string) ob_get_clean();
}

test(
	'a ticked Where on a published event is remembered: a later plain save is not held back',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$place  = 'Spokane DEM, 509-555-0142';
			$fields = array(
				'post_title'  => 'QA-019 published ' . $role,
				'spk_kind'    => 'training',
				'spk_start'   => qa019_day( 20 ),
				'spk_summary' => 'for QA',
				'spk_where'   => 'Spokane DEM',
			);
			$id     = qa019_new( $fields['post_title'] );
			qa019_save( $id, $fields );
			assert_same( 'publish', get_post_status( $id ), $role . ': set-up: published' );
			qa019_errors();

			// Update with a phone number in Where, no tick: held back.
			$fields['spk_where'] = $place;
			qa019_save( $id, $fields );
			assert_same( 'publish', get_post_status( $id ), $role . ': still published after the held save' );
			assert_same( 'Spokane DEM', get_post_meta( $id, 'spk_where', true ), $role . ': the place is held back without the tick' );
			$errors = qa019_errors();
			assert_count( 1, $errors, $role . ': one problem notice without the tick' );
			assert_contains( 'The place has a phone number', (string) reset( $errors ), $role . ': the notice names the place' );

			// Tick "Publish it" and Update: saved.
			qa019_save( $id, array_merge( $fields, array( 'spk_confirm' => array( 'where' => '1' ) ) ) );
			assert_same( 'publish', get_post_status( $id ), $role . ': published after the ticked save' );
			assert_same( $place, get_post_meta( $id, 'spk_where', true ), $role . ': the ticked place is saved' );
			assert_same( array(), qa019_errors(), $role . ': no problem notice with the tick' );

			// Update again with nothing changed and no tick: nothing is flagged.
			qa019_save( $id, $fields );
			$errors = qa019_errors();
			assert_same( array(), $errors, $role . ': an unchanged save after the tick flags nothing: ' . export( $errors ) );
			assert_same( 'publish', get_post_status( $id ), $role . ': still published' );
			assert_same( $place, get_post_meta( $id, 'spk_where', true ), $role . ': the confirmed place stays' );

			// The form no longer shows the tick for the confirmed place.
			assert_not_contains( 'name="spk_confirm[where]"', qa019_form( $id ), $role . ': no Where tick on the form after confirming' );
		}
	}
);

test(
	'a draft saved with the Where tick is not demoted when it is published',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$fields = array(
				'post_title'  => 'QA-019 draft ' . $role,
				'spk_kind'    => 'exercise',
				'spk_start'   => qa019_day( 25 ),
				'spk_summary' => 'for QA',
				'spk_where'   => 'Spokane DEM, 509-555-0142',
			);
			$id     = qa019_new( $fields['post_title'] );
			qa019_errors();

			// Tick "Publish it" and Save draft.
			qa019_save( $id, array_merge( $fields, array( 'spk_confirm' => array( 'where' => '1' ) ) ), 'draft' );
			assert_same( 'draft', get_post_status( $id ), $role . ': saved as a draft' );
			assert_same( array(), qa019_errors(), $role . ': no problem notice with the tick' );

			// Publish (the tick is not shown again, so it is not sent).
			qa019_save( $id, $fields );
			$errors = qa019_errors();
			assert_same( array(), $errors, $role . ': publishing the confirmed draft flags nothing: ' . export( $errors ) );
			assert_same( 'publish', get_post_status( $id ), $role . ': published, not demoted to a draft' );
		}
	}
);

test(
	'a confirmed Where that is then changed is checked again',
	function () {
		as_role( 'ares-editor' );
		$fields = array(
			'post_title'  => 'QA-019 changed place',
			'spk_kind'    => 'training',
			'spk_start'   => qa019_day( 30 ),
			'spk_summary' => 'for QA',
			'spk_where'   => 'Spokane DEM, 509-555-0142',
		);
		$id     = qa019_new( $fields['post_title'] );
		qa019_save( $id, array_merge( $fields, array( 'spk_confirm' => array( 'where' => '1' ) ) ) );
		assert_same( 'publish', get_post_status( $id ), 'set-up: published with the tick' );
		qa019_errors();

		// A different number in Where, no tick: held back again.
		qa019_save( $id, array_merge( $fields, array( 'spk_where' => 'Spokane DEM, 509-555-0199' ) ) );
		assert_same( 'publish', get_post_status( $id ), 'still published' );
		assert_same( 'Spokane DEM, 509-555-0142', get_post_meta( $id, 'spk_where', true ), 'the changed place is held back' );
		$errors = qa019_errors();
		assert_count( 1, $errors, 'one problem notice for the changed place' );
		assert_contains( 'The place has a phone number', (string) reset( $errors ), 'the notice names the place' );
	}
);
