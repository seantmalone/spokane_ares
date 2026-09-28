<?php
/**
 * Regression tests for QA-060 (PLAN §2 "Past exercise: 'Oct 2022' (day or
 * month precision), '2025' (year precision)", §3 spk_precision: imported
 * history uses month/year).
 *
 * Events › Past: the "When" column formats spk_start with spokares_fmt_when()
 * 'row' and ignores spk_precision. A past exercise known only to the year or
 * the month is stored with its start on the first of the year or month, so
 * the list invents a day and a weekday: Field Day 2025 (precision year) shows
 * "Wed, Jan 1, 2025" and the Rockford exercise (precision month) shows
 * "Sat, Oct 1, 2022", where the public Past exercises list says "2025" and
 * "Oct 2022". A day-precision event keeps its full date.
 *
 * The column is read from a row drawn by WP_Posts_List_Table on the
 * edit-spk_event screen, as edit.php draws it.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The text of the Events list's When cell for one event, as the current user
 * sees it on wp-admin/edit.php?post_type=spk_event.
 *
 * @param int $id Event.
 */
function qa060_when_cell( int $id ): string {
	global $current_screen, $typenow, $taxnow;
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
	$saved = array( $current_screen, $typenow, $taxnow );
	$row   = '';
	try {
		set_current_screen( 'edit-spk_event' );
		$table = new \WP_Posts_List_Table( array( 'screen' => 'edit-spk_event' ) );
		clean_post_cache( $id );
		ob_start();
		$table->single_row( get_post( $id ) );
		$row = (string) ob_get_clean();
	} finally {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the globals set_current_screen() changed.
		list( $current_screen, $typenow, $taxnow ) = $saved;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	if ( ! preg_match( '/<td\b[^>]*\bcolumn-spk_when\b[^>]*>(.*?)<\/td>/is', $row, $m ) ) {
		fail( 'the Events list row for event ' . $id . ' has no When cell: ' . $row );
	}
	$text = html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * The public Past exercises text for an event ("2025", "Oct 2022").
 *
 * @param int $id Event.
 */
function qa060_public_past( int $id ): string {
	return trim( str_replace( "\u{00A0}", ' ', \spokares_fmt_when( $id, 'past' ) ) );
}

/**
 * A published past exercise with this start and precision. Returns its ID.
 *
 * @param string $start     Y-m-d.
 * @param string $precision day|month|year.
 */
function qa060_past_exercise( string $start, string $precision ): int {
	$id = create_post(
		array(
			'post_type'   => 'spk_event',
			'post_status' => 'publish',
			'post_title'  => 'QA-060 ' . $precision . ' ' . $start,
		)
	);
	foreach (
		array(
			'spk_kind'      => 'exercise',
			'spk_date_mode' => 'date',
			'spk_start'     => $start,
			'spk_end'       => '',
			'spk_keep_past' => '1',
			'spk_precision' => $precision,
		) as $key => $value
	) {
		update_post_meta( $id, $key, $value );
	}
	if ( function_exists( 'spokares_dev_event_sort' ) ) {
		\spokares_dev_event_sort( $id );
	}
	clean_post_cache( $id );
	return $id;
}

/**
 * Weekday names as the list prints them.
 */
function qa060_weekday_regex(): string {
	return '/\b(Mon|Tue|Wed|Thu|Fri|Sat|Sun)\b/';
}

test(
	'Events › Past: Field Day 2025 (year precision) shows "2025", not an invented "Wed, Jan 1, 2025"',
	function () {
		$id = post_id( 'spk_event', 'past-2025-field-day-in-spokane-valley-as-w7gbu' );
		assert_same( 'year', (string) get_post_meta( $id, 'spk_precision', true ), 'set-up: Field Day 2025 is year precision' );
		assert_same( '2025', qa060_public_past( $id ), 'set-up: the public Past list says 2025 (control)' );
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$cell = qa060_when_cell( $id );
			assert_contains( '2025', $cell, $role . ': the When cell names the year' );
			assert_false( (bool) preg_match( qa060_weekday_regex(), $cell ), $role . ': the When cell invents a weekday for a year-precision event: "' . $cell . '"' );
			assert_not_contains( 'Jan 1', $cell, $role . ': the When cell invents a day (Jan 1) for a year-precision event: "' . $cell . '"' );
			assert_same( '2025', $cell, $role . ': the When cell for a year-precision event is "2025", as the public Past list shows' );
		}
	}
);

test(
	'Events › Past: the Rockford exercise (month precision) shows "Oct 2022", not an invented "Sat, Oct 1, 2022"',
	function () {
		$id = post_id( 'spk_event', 'past-2022-10-rockford-exercise' );
		assert_same( 'month', (string) get_post_meta( $id, 'spk_precision', true ), 'set-up: Rockford is month precision' );
		assert_same( 'Oct 2022', qa060_public_past( $id ), 'set-up: the public Past list says Oct 2022 (control)' );
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$cell = qa060_when_cell( $id );
			assert_contains( 'Oct', $cell, $role . ': the When cell names the month' );
			assert_contains( '2022', $cell, $role . ': the When cell names the year' );
			assert_false( (bool) preg_match( qa060_weekday_regex(), $cell ), $role . ': the When cell invents a weekday for a month-precision event: "' . $cell . '"' );
			assert_not_contains( 'Oct 1', $cell, $role . ': the When cell invents a day (Oct 1) for a month-precision event: "' . $cell . '"' );
			assert_same( 'Oct 2022', $cell, $role . ': the When cell for a month-precision event is "Oct 2022", as the public Past list shows' );
		}
	}
);

test(
	'Events › Past: every imported month- or year-precision exercise shows what the public Past list shows',
	function () {
		as_role( 'ares-editor' );
		$ids = get_posts(
			array(
				'post_type'      => 'spk_event',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a handful of dev events.
				'meta_query'     => array(
					array(
						'key'     => 'spk_precision',
						'value'   => array( 'month', 'year' ),
						'compare' => 'IN',
					),
				),
			)
		);
		assert_true( count( $ids ) >= 2, 'set-up: the import has month- and year-precision past exercises' );
		foreach ( $ids as $id ) {
			$cell = qa060_when_cell( (int) $id );
			$want = qa060_public_past( (int) $id );
			assert_same( $want, $cell, get_the_title( $id ) . ' (' . get_post_meta( $id, 'spk_precision', true ) . ' precision, start ' . get_post_meta( $id, 'spk_start', true ) . ')' );
		}
	}
);

test(
	'Events list: a new year- or month-precision past exercise shows only the year or the month and year',
	function () {
		as_role( 'ares-editor' );
		$year  = qa060_past_exercise( '2024-01-01', 'year' );
		$month = qa060_past_exercise( '2023-05-01', 'month' );
		$cell  = qa060_when_cell( $year );
		assert_same( '2024', $cell, 'year precision, start 2024-01-01' );
		$cell = qa060_when_cell( $month );
		assert_same( 'May 2023', $cell, 'month precision, start 2023-05-01' );
	}
);

test(
	'Events list: a day-precision past event keeps its full date with the weekday (control)',
	function () {
		as_role( 'ares-editor' );
		$id   = qa060_past_exercise( '2025-06-14', 'day' );
		$cell = qa060_when_cell( $id );
		assert_true( (bool) preg_match( qa060_weekday_regex(), $cell ), 'the full date has its weekday: "' . $cell . '"' );
		assert_contains( 'Jun 14', $cell, 'the full date has its day' );
		assert_same( trim( str_replace( "\u{00A0}", ' ', \spokares_fmt_when( $id, 'row' ) ) ), $cell, 'the When cell is the row format' );
	}
);

test(
	'Events list: an upcoming day-precision event keeps its full date (control)',
	function () {
		as_role( 'admin' );
		$id   = post_id( 'spk_event', 'set-2026' );
		$cell = qa060_when_cell( $id );
		assert_same( trim( str_replace( "\u{00A0}", ' ', \spokares_fmt_when( $id, 'row' ) ) ), $cell, 'SET 2026 When cell' );
		assert_true( (bool) preg_match( qa060_weekday_regex(), $cell ), 'the full date has its weekday: "' . $cell . '"' );
	}
);
