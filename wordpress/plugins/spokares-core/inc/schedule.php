<?php
/**
 * Schedule engine (§3.3): nth weekdays, the Tuesday note, rota rows, meetings
 * with their cancellations and moves, and the event lists for every view.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Week of the month: 1-5 (5 = a fifth occurrence).
 *
 * @param string $ymd Date.
 */
function spokares_nth( string $ymd ): int {
	return (int) floor( ( (int) substr( $ymd, 8, 2 ) - 1 ) / 7 ) + 1;
}

/**
 * Weekday 0 (Sunday) … 6 (Saturday).
 *
 * @param string $ymd Date.
 */
function spokares_weekday( string $ymd ): int {
	$d = spokares_date_obj( $ymd );
	return $d ? (int) $d->format( 'w' ) : -1;
}

/**
 * The first date on or after $ymd that falls on $weekday.
 *
 * @param string $ymd     Date.
 * @param int    $weekday 0-6.
 */
function spokares_next_weekday( string $ymd, int $weekday ): string {
	$wd = spokares_weekday( $ymd );
	return spokares_add_days( $ymd, ( $weekday - $wd + 7 ) % 7 );
}

/**
 * What the Tuesday net is on a date, and who has net control.
 * Port of B's fn.netOn(): fifth Tuesday → Simplex; else 2nd/4th → Winlink
 * night; else 3rd → GMRS net at its time. Weeks and time come from spk_nets.
 *
 * @param string $ymd A Tuesday.
 * @return array{date:string,nth:int,kind:string,note:string,state:string,call:string,row_note:string,wl_task:string,wl_form:string,stored:bool}
 */
function spokares_net_on( string $ymd ): array {
	$nets = spokares_opt( 'spk_nets' );
	$nth  = spokares_nth( $ymd );
	$kind = '';
	$note = '';
	if ( in_array( $nth, $nets['simplex_nth'], true ) ) {
		$kind = 'simplex';
		$note = __( 'Simplex', 'spokares-core' );
	} elseif ( in_array( $nth, $nets['winlink_nth'], true ) ) {
		$kind = 'winlink';
		$note = __( 'Winlink night', 'spokares-core' );
	} elseif ( in_array( $nth, $nets['gmrs_nth'], true ) ) {
		$kind = 'gmrs';
		$time = spokares_fmt_time( $nets['gmrs_time'] );
		/* translators: %s: time, e.g. "7:30 PM". */
		$note = '' !== $time ? sprintf( __( 'GMRS net, %s', 'spokares-core' ), $time ) : __( 'GMRS net', 'spokares-core' );
	}
	$rota   = spokares_opt( 'spk_rota' );
	$stored = isset( $rota[ $ymd ] );
	$row    = $stored ? $rota[ $ymd ] : spokares_normalize_rota_row( array() );
	return array(
		'date'     => $ymd,
		'nth'      => $nth,
		'kind'     => $kind,
		'note'     => $note,
		'state'    => $row['state'],
		'call'     => $row['call'],
		'row_note' => $row['note'],
		'wl_task'  => $row['wl_task'],
		'wl_form'  => $row['wl_form'],
		'stored'   => $stored,
	);
}

/**
 * The next $weeks Tuesdays from today (today included when it is a Tuesday).
 *
 * @param int         $weeks Number of Tuesdays.
 * @param string|null $from  Start date (default today).
 * @return array<int,array> spokares_net_on() rows.
 */
function spokares_rota_rows( int $weeks, ?string $from = null ): array {
	$first = spokares_next_weekday( $from ?? spokares_today(), 2 );
	$rows  = array();
	for ( $i = 0; $i < max( 0, $weeks ); $i++ ) {
		$rows[] = spokares_net_on( spokares_add_days( $first, 7 * $i ) );
	}
	return $rows;
}

/**
 * Does a meeting's rule fall on this date?
 *
 * @param array  $m   Meeting.
 * @param string $ymd Date.
 */
function spokares_meeting_on( array $m, string $ymd ): bool {
	if ( spokares_weekday( $ymd ) !== (int) $m['weekday'] ) {
		return false;
	}
	if ( in_array( (int) substr( $ymd, 5, 2 ), $m['skip_months'], true ) ) {
		return false;
	}
	return in_array( spokares_nth( $ymd ), $m['nth'], true );
}

/**
 * Rule dates of a meeting between two dates (inclusive).
 *
 * @param array  $m    Meeting.
 * @param string $from Start.
 * @param string $to   End.
 * @return string[]
 */
function spokares_meeting_rule_dates( array $m, string $from, string $to ): array {
	$out = array();
	if ( empty( $m['nth'] ) ) {
		return $out;
	}
	$d = spokares_next_weekday( $from, (int) $m['weekday'] );
	while ( $d <= $to ) {
		if ( spokares_meeting_on( $m, $d ) ) {
			$out[] = $d;
		}
		$d = spokares_add_days( $d, 7 );
	}
	return $out;
}

/**
 * The change recorded for a meeting's rule date, if any.
 *
 * @param string $meeting_id Meeting id.
 * @param string $ymd        Rule date.
 */
function spokares_meeting_change( string $meeting_id, string $ymd ): ?array {
	foreach ( spokares_opt( 'spk_meetings' )['changes'] as $c ) {
		if ( $c['meeting'] === $meeting_id && $c['date'] === $ymd ) {
			return $c;
		}
	}
	return null;
}

/**
 * The next real dates of every active meeting, with cancellations and moves
 * applied.
 *
 * Each item: meeting (the rule) · dates (list of [date, orig, kind '' | 'moved',
 * note]) · cancelled (rule dates from today to the first real date that were
 * cancelled) · changes (every cancelled/moved rule date from today on, for the
 * hub's notices).
 *
 * @param int         $per_meeting How many real dates per meeting.
 * @param string|null $from        Start date (default today).
 */
function spokares_next_meetings( int $per_meeting = 1, ?string $from = null ): array {
	$today = $from ?? spokares_today();
	$out   = array();
	foreach ( spokares_opt( 'spk_meetings' )['meetings'] as $m ) {
		if ( ! $m['active'] ) {
			continue;
		}
		// Look back two months so a date moved later than its rule date is still found.
		$rule_dates = spokares_meeting_rule_dates( $m, spokares_add_days( $today, -62 ), spokares_add_days( $today, 400 ) );
		$occ        = array();
		$changes    = array();
		foreach ( $rule_dates as $d ) {
			$c = spokares_meeting_change( $m['id'], $d );
			if ( $c && $d >= $today ) {
				$changes[] = $c;
			}
			if ( $c && 'cancelled' === $c['kind'] ) {
				continue;
			}
			if ( $c && 'moved' === $c['kind'] && spokares_is_ymd( $c['new_date'] ) ) {
				$occ[] = array(
					'date' => $c['new_date'],
					'orig' => $d,
					'kind' => 'moved',
					'note' => $c['note'],
				);
				continue;
			}
			$occ[] = array(
				'date' => $d,
				'orig' => $d,
				'kind' => '',
				'note' => '',
			);
		}
		$occ = array_values( array_filter( $occ, static fn( $o ) => $o['date'] >= $today ) );
		usort( $occ, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
		$dates     = array_slice( $occ, 0, max( 1, $per_meeting ) );
		$cancelled = array();
		$first     = $dates[0]['date'] ?? '9999-12-31';
		foreach ( $changes as $c ) {
			if ( 'cancelled' === $c['kind'] && $c['date'] < $first ) {
				$cancelled[] = $c['date'];
			}
		}
		$out[] = array(
			'meeting'   => $m,
			'dates'     => $dates,
			'cancelled' => $cancelled,
			'changes'   => $changes,
		);
	}
	return $out;
}

/**
 * The Home line of a meeting: name + ", " + (time words or time range) +
 * (", " + home extra).
 *
 * @param array $m Meeting.
 */
function spokares_meeting_home_line( array $m ): string {
	$when  = '' !== $m['time_text'] ? $m['time_text'] : spokares_fmt_time_range( $m['start'], $m['end'] );
	$parts = array_filter( array( $m['name'], $when, $m['home_extra'] ), static fn( $p ) => '' !== trim( (string) $p ) );
	return implode( ', ', $parts );
}

/**
 * The hub line of a meeting on a date: "Sat, Oct 10, 9:00 AM, Second Saturday Workshop".
 *
 * @param array  $m   Meeting.
 * @param string $ymd Date.
 */
function spokares_meeting_hub_line( array $m, string $ymd ): string {
	$when  = '' !== $m['start'] ? spokares_fmt_time( $m['start'] ) : $m['time_text'];
	$parts = array_filter( array( spokares_fmt_date( $ymd, 'short' ), $when, $m['name'] ), static fn( $p ) => '' !== trim( (string) $p ) );
	return implode( ', ', $parts );
}

/**
 * Published events for the lists, as spokares_event_data() arrays.
 *
 * Views (§3.5):
 *  upcoming       types, limit, days: dated, of those kinds, not ended, starting within `days`
 *  next-up        types, limit: first N upcoming dated events of those kinds
 *  later          types, limit, cardTypes, cardLimit: every upcoming event of `types`
 *                 except the next-up cards; dated by start, then undated
 *  public-service dated and not past, then undated
 *  past           limit: exercises kept after they end, newest first
 *  all-upcoming   every upcoming published event (dashboard)
 *
 * @param string $view View.
 * @param array  $args Arguments.
 * @return array<int,array>
 */
function spokares_events( string $view, array $args = array() ): array {
	$today = spokares_today();
	$types = array_values(
		array_intersect(
			array_map( 'trim', explode( ',', (string) ( $args['types'] ?? '' ) ) ),
			array_keys( spokares_event_kinds() )
		)
	);
	$limit = max( 0, (int) ( $args['limit'] ?? 0 ) );

	if ( 'past' === $view ) {
		$meta = array(
			'relation' => 'AND',
			array(
				'key'   => 'spk_kind',
				'value' => 'exercise',
			),
			array(
				'key'   => 'spk_keep_past',
				'value' => '1',
			),
			array(
				'key'     => 'spk_end_sort',
				'value'   => $today,
				'compare' => '<',
				'type'    => 'CHAR',
			),
		);
	} else {
		$meta = array(
			array(
				'key'     => 'spk_end_sort',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'CHAR',
			),
		);
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'spk_event',
			'post_status'            => 'publish',
			'posts_per_page'         => 500,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a small club calendar; the list is bounded by date.
			'meta_query'             => $meta,
		)
	);

	$events = array();
	foreach ( $query->posts as $post ) {
		$ev = spokares_event_data( $post );
		if ( ! $ev || '' === $ev['kind'] ) {
			continue;
		}
		$events[] = $ev;
	}

	$dated_sort   = static function ( array $a, array $b ): int {
		return array( $a['start'], $a['t_start'], $a['title'] ) <=> array( $b['start'], $b['t_start'], $b['title'] );
	};
	$undated_sort = static function ( array $a, array $b ): int {
		return array( $a['menu'], $a['title'] ) <=> array( $b['menu'], $b['title'] );
	};

	switch ( $view ) {
		case 'past':
			usort(
				$events,
				static fn( $a, $b ) => array( $b['start'], $b['title'] ) <=> array( $a['start'], $a['title'] )
			);
			$list = $events;
			break;

		case 'public-service':
			$pub     = array_filter( $events, static fn( $e ) => 'public-service' === $e['kind'] );
			$dated   = array_values( array_filter( $pub, static fn( $e ) => 'date' === $e['mode'] ) );
			$undated = array_values( array_filter( $pub, static fn( $e ) => 'date' !== $e['mode'] ) );
			usort( $dated, $dated_sort );
			usort( $undated, $undated_sort );
			$list = array_merge( $dated, $undated );
			break;

		case 'later':
			$cards   = spokares_events(
				'next-up',
				array(
					'types' => (string) ( $args['cardTypes'] ?? 'exercise' ),
					'limit' => (int) ( $args['cardLimit'] ?? 2 ),
				)
			);
			$skip    = wp_list_pluck( $cards, 'id' );
			$mine    = array_filter( $events, static fn( $e ) => in_array( $e['kind'], $types, true ) && ! in_array( $e['id'], $skip, true ) );
			$dated   = array_values( array_filter( $mine, static fn( $e ) => 'date' === $e['mode'] ) );
			$undated = array_values( array_filter( $mine, static fn( $e ) => 'date' !== $e['mode'] ) );
			usort( $dated, $dated_sort );
			usort( $undated, $undated_sort );
			$list = array_merge( $dated, $undated );
			break;

		case 'upcoming':
			$days = max( 1, (int) ( $args['days'] ?? 60 ) );
			$last = spokares_add_days( $today, $days );
			$list = array_values(
				array_filter(
					$events,
					static fn( $e ) => 'date' === $e['mode'] && in_array( $e['kind'], $types, true ) && $e['start'] <= $last
				)
			);
			usort( $list, $dated_sort );
			break;

		case 'next-up':
			$list = array_values( array_filter( $events, static fn( $e ) => 'date' === $e['mode'] && in_array( $e['kind'], $types, true ) ) );
			usort( $list, $dated_sort );
			break;

		case 'all-upcoming':
		default:
			$dated   = array_values( array_filter( $events, static fn( $e ) => 'date' === $e['mode'] ) );
			$undated = array_values( array_filter( $events, static fn( $e ) => 'date' !== $e['mode'] ) );
			usort( $dated, $dated_sort );
			usort( $undated, $undated_sort );
			$list = array_merge( $dated, $undated );
			break;
	}

	return $limit > 0 ? array_slice( $list, 0, $limit ) : $list;
}
