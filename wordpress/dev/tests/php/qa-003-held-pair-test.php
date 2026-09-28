<?php
/**
 * Regression tests for QA-003 (PLAN §4.4): on a published event, a problem
 * with the end time or the last day holds back only that one field, so the
 * new start time or first day is saved anyway. The live event then shows a
 * half-applied change ("1:00 PM–noon", or a Dec 4–5 event shown as just
 * "Mon, Dec 14") while the notice says the event is still on the site as it
 * was. The start and end of a pair (times, days) must be held back together.
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
function qa003_day( int $days ): string {
	return gmdate( 'Y-m-d', (int) strtotime( spokares_today() . ' +' . $days . ' days' ) );
}

/**
 * Save an event through its form, as the classic post.php save does: the
 * form's fields and nonce in $_POST, then wp_update_post().
 *
 * @param int   $id     Event.
 * @param array $fields Form fields (post_title, spk_kind, spk_start, spk_time_start…).
 */
function qa003_save( int $id, array $fields ): array {
	$post = array_merge(
		array(
			'spokares_event_nonce' => wp_create_nonce( 'spokares_event_meta' ),
			'spk_date_mode'        => 'date',
			'original_post_status' => get_post_status( $id ),
		),
		$fields
	);

	$r = call_request(
		'POST',
		array(),
		$post,
		static function () use ( $id, $fields ) {
			return wp_update_post(
				wp_slash(
					array(
						'ID'          => $id,
						'post_title'  => $fields['post_title'],
						'post_status' => 'publish',
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
 * A published training event with these fields, saved through the form as
 * the current user.
 *
 * @param array $fields Form fields.
 */
function qa003_published( array $fields ): int {
	$id = create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'draft',
			'post_title'  => $fields['post_title'],
			'post_author' => get_current_user_id(),
		)
	);
	qa003_save( $id, $fields );
	assert_same( 'publish', get_post_status( $id ), 'set-up: the event is published' );
	return $id;
}

/**
 * The event's row in "Later this season" (Exercises & events), as the site
 * prints it now.
 *
 * @param int $id Event.
 */
function qa003_row( int $id ): string {
	$html = spokares_render_events(
		array(
			'view'      => 'later',
			'types'     => 'training',
			'limit'     => 100,
			'cardTypes' => 'exercise',
			'cardLimit' => 2,
		)
	);
	$slug = get_post_field( 'post_name', $id );
	if ( ! preg_match( '/<tr id="' . preg_quote( $slug, '/' ) . '"[^>]*>.*?<\/tr>/s', $html, $m ) ) {
		fail( 'no Later this season row for ' . $slug );
	}
	return $m[0];
}

/**
 * The notices queued for the current user (and clear them).
 */
function qa003_notices(): array {
	$key     = 'spokares_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	delete_transient( $key );
	return is_array( $notices ) ? $notices : array();
}

/**
 * Assert the save took the "held back, still on the site as it was" path.
 *
 * @param string $why Problem sentence expected in the notice.
 * @param string $msg Message prefix.
 */
function qa003_assert_held_notice( string $why, string $msg ): void {
	$errors = wp_list_pluck( array_filter( qa003_notices(), static fn( $n ) => in_array( $n['type'], array( 'error', 'warning' ), true ) ), 'text' );
	assert_count( 1, $errors, $msg . ': one problem notice' );
	$text = (string) reset( $errors );
	assert_contains( 'Saved, except ', $text, $msg . ': held-back notice' );
	assert_contains( 'Everything else is on the site now.', $text, $msg . ': held-back notice says the rest is live' );
	assert_contains( lcfirst( $why ), $text, $msg . ': names the problem' );
}

test(
	'an end time before the new start time holds back both times of a published event',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$fields = array(
				'post_title'     => 'QA-003 time pair ' . $role,
				'spk_kind'       => 'training',
				'spk_start'      => qa003_day( 11 ),
				'spk_time_start' => '09:00',
				'spk_time_end'   => '12:00',
				'spk_summary'    => 'for QA',
			);
			$id     = qa003_published( $fields );
			$before = qa003_row( $id );
			assert_matches( '/9:00\x{00A0}AM–noon/u', $before, $role . ': set-up row' );
			qa003_notices();

			// Update: Start 13:00, End 12:30 (the end is before the start).
			qa003_save(
				$id,
				array_merge(
					$fields,
					array(
						'spk_time_start' => '13:00',
						'spk_time_end'   => '12:30',
					)
				)
			);
			assert_same( 'publish', get_post_status( $id ), $role . ': still published' );
			qa003_assert_held_notice( 'The end time must be after the start time.', $role );
			assert_same( '09:00', get_post_meta( $id, 'spk_time_start', true ), $role . ': live start time held back with the end time' );
			assert_same( '12:00', get_post_meta( $id, 'spk_time_end', true ), $role . ': live end time held back' );
			$after = qa003_row( $id );
			assert_false( (bool) preg_match( '/1:00\x{00A0}PM–noon/u', $after ), $role . ': live row shows a half-applied change: ' . $after );
			assert_same( $before, $after, $role . ': live row unchanged' );
		}
	}
);

test(
	'a last day before the new first day holds back both days of a published event',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$first  = qa003_day( 41 );
			$last   = qa003_day( 42 );
			$fields = array(
				'post_title'  => 'QA-003 date pair ' . $role,
				'spk_kind'    => 'training',
				'spk_start'   => $first,
				'spk_end'     => $last,
				'spk_all_day' => '1',
				'spk_summary' => 'for QA',
			);
			$id     = qa003_published( $fields );
			$before = qa003_row( $id );
			qa003_notices();

			// Update: First day a week later, Last day before it.
			qa003_save(
				$id,
				array_merge(
					$fields,
					array(
						'spk_start' => qa003_day( 48 ),
						'spk_end'   => qa003_day( 43 ),
					)
				)
			);
			assert_same( 'publish', get_post_status( $id ), $role . ': still published' );
			qa003_assert_held_notice( 'The last day is before the first day.', $role );
			assert_same( $first, get_post_meta( $id, 'spk_start', true ), $role . ': live first day held back with the last day' );
			assert_same( $last, get_post_meta( $id, 'spk_end', true ), $role . ': live last day held back' );
			assert_same( $before, qa003_row( $id ), $role . ': live row unchanged' );
		}
	}
);

test(
	'the held-back form still shows both times the editor typed',
	function () {
		as_role( 'ares-editor' );
		$fields = array(
			'post_title'     => 'QA-003 typed pair',
			'spk_kind'       => 'training',
			'spk_start'      => qa003_day( 11 ),
			'spk_time_start' => '09:00',
			'spk_time_end'   => '12:00',
		);

		$id = qa003_published( $fields );
		qa003_save(
			$id,
			array_merge(
				$fields,
				array(
					'spk_time_start' => '13:00',
					'spk_time_end'   => '12:30',
				)
			)
		);
		$form = spokares_event_form_values( get_post( $id ) );
		assert_same( '13:00', $form['t_start'], 'typed start time kept in the form' );
		assert_same( '12:30', $form['t_end'], 'typed end time kept in the form' );
	}
);

test(
	'control: a good new time pair on a published event saves both times',
	function () {
		as_role( 'ares-editor' );
		$fields = array(
			'post_title'     => 'QA-003 good pair',
			'spk_kind'       => 'training',
			'spk_start'      => qa003_day( 11 ),
			'spk_time_start' => '09:00',
			'spk_time_end'   => '12:00',
		);

		$id = qa003_published( $fields );
		qa003_notices();
		qa003_save(
			$id,
			array_merge(
				$fields,
				array(
					'spk_time_start' => '13:00',
					'spk_time_end'   => '14:30',
				)
			)
		);
		$errors = array_filter( qa003_notices(), static fn( $n ) => in_array( $n['type'], array( 'error', 'warning' ), true ) );
		assert_count( 0, $errors, 'no problem notice' );
		assert_same( '13:00', get_post_meta( $id, 'spk_time_start', true ), 'new start saved' );
		assert_same( '14:30', get_post_meta( $id, 'spk_time_end', true ), 'new end saved' );
	}
);
