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
 * The weeks of the month each net group really has. A week ticked in two
 * groups on Net details belongs to the first of simplex, Winlink, GMRS (the
 * rule the screen states), so the rota, How it works › Other nets and the
 * Winlink assignments all agree.
 *
 * @param array|null $nets spk_nets (default: the stored option).
 * @return array{simplex:int[],winlink:int[],gmrs:int[]}
 */
function spokares_net_weeks( ?array $nets = null ): array {
	$nets  = $nets ?? spokares_opt( 'spk_nets' );
	$taken = array();
	$out   = array();
	foreach ( array( 'simplex', 'winlink', 'gmrs' ) as $kind ) {
		$weeks = array_values( array_diff( array_unique( array_map( 'intval', (array) ( $nets[ $kind . '_nth' ] ?? array() ) ) ), $taken ) );
		sort( $weeks );
		$out[ $kind ] = $weeks;
		$taken        = array_merge( $taken, $weeks );
	}
	return $out;
}

/**
 * What the Tuesday net is on a date, and who has net control.
 * Port of B's fn.netOn(): fifth Tuesday → Simplex; else 2nd/4th → Winlink
 * night; else 3rd → GMRS net at its time. Weeks and time come from spk_nets,
 * a week ticked twice going to the first group (spokares_net_weeks()).
 *
 * @param string $ymd A Tuesday.
 * @return array{date:string,nth:int,kind:string,note:string,state:string,call:string,row_note:string,wl_task:string,wl_form:string,stored:bool}
 */
function spokares_net_on( string $ymd ): array {
	$nets  = spokares_opt( 'spk_nets' );
	$weeks = spokares_net_weeks( $nets );
	$nth   = spokares_nth( $ymd );
	$kind  = '';
	$note  = '';
	if ( in_array( $nth, $weeks['simplex'], true ) ) {
		$kind = 'simplex';
		$note = __( 'Simplex', 'spokares-core' );
	} elseif ( in_array( $nth, $weeks['winlink'], true ) ) {
		$kind = 'winlink';
		$note = __( 'Winlink night', 'spokares-core' );
	} elseif ( in_array( $nth, $weeks['gmrs'], true ) ) {
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
 * A meeting's rule as it is in effect on a date: its pending change
 * (`next`, "Takes effect on") from that change's date on, else the rule as
 * stored. The id, show_home, active and needs_check are always the stored
 * rule's.
 *
 * @param array  $m   Meeting (normalised).
 * @param string $ymd Date.
 */
function spokares_meeting_in_effect( array $m, string $ymd ): array {
	$next = is_array( $m['next'] ?? null ) ? $m['next'] : null;
	if ( ! $next || $ymd < (string) ( $next['from'] ?? '9999-12-31' ) ) {
		return $m;
	}
	foreach ( spokares_meeting_pattern_keys() as $key ) {
		if ( array_key_exists( $key, $next ) ) {
			$m[ $key ] = $next[ $key ];
		}
	}
	return $m;
}

/**
 * Does a meeting's rule fall on this date (under the pattern in effect on
 * that date)?
 *
 * @param array  $m   Meeting.
 * @param string $ymd Date.
 */
function spokares_meeting_on( array $m, string $ymd ): bool {
	$m = spokares_meeting_in_effect( $m, $ymd );
	if ( spokares_weekday( $ymd ) !== (int) $m['weekday'] ) {
		return false;
	}
	if ( in_array( (int) substr( $ymd, 5, 2 ), (array) $m['skip_months'], true ) ) {
		return false;
	}
	return in_array( spokares_nth( $ymd ), (array) $m['nth'], true );
}

/**
 * Rule dates of a meeting between two dates (inclusive). A pending change
 * splits the range at its date: the stored pattern before it, the new one
 * from it on.
 *
 * @param array  $m    Meeting.
 * @param string $from Start.
 * @param string $to   End.
 * @return string[]
 */
function spokares_meeting_rule_dates( array $m, string $from, string $to ): array {
	$split = is_array( $m['next'] ?? null ) ? (string) ( $m['next']['from'] ?? '' ) : '';
	if ( spokares_is_ymd( $split ) && $split > $from ) {
		return array_merge(
			spokares_meeting_pattern_dates( $m, $from, min( $to, spokares_add_days( $split, -1 ) ) ),
			$split <= $to ? spokares_meeting_pattern_dates( spokares_meeting_in_effect( $m, $split ), $split, $to ) : array()
		);
	}
	return spokares_meeting_pattern_dates( spokares_meeting_in_effect( $m, $from ), $from, $to );
}

/**
 * The dates one pattern (weeks, weekday, skipped months) falls on between two
 * dates (inclusive).
 *
 * @param array  $p    Pattern (a meeting rule).
 * @param string $from Start.
 * @param string $to   End.
 * @return string[]
 */
function spokares_meeting_pattern_dates( array $p, string $from, string $to ): array {
	$out = array();
	if ( empty( $p['nth'] ) || $to < $from ) {
		return $out;
	}
	$d = spokares_next_weekday( $from, (int) $p['weekday'] );
	while ( $d <= $to ) {
		if ( ! in_array( (int) substr( $d, 5, 2 ), (array) $p['skip_months'], true ) && in_array( spokares_nth( $d ), (array) $p['nth'], true ) ) {
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
 * The next real dates of every active meeting, with cancellations, moves and
 * one-date notes applied.
 *
 * Each item: meeting (the rule as it is in effect on the first returned date,
 * with the stored rule's id, show_home and active) · dates (list of [date,
 * orig, kind '' | 'moved' | 'note', note]) · cancelled (rule dates from today
 * to the first real date that were cancelled) · changes (every cancelled,
 * moved or noted rule date from today on, for the For members page).
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
			if ( $c && 'note' === $c['kind'] ) {
				// Same date, with a word for that day ("Starts at 10:00 AM this time").
				$occ[] = array(
					'date' => $d,
					'orig' => $d,
					'kind' => 'note',
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
		// The rule as it reads on the first date shown (a pattern that takes
		// effect later describes that later date), with the stored switches.
		$rule = spokares_meeting_in_effect( $m, $first );
		unset( $rule['next'] );
		$out[] = array(
			'meeting'   => $rule,
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
 * A meeting's time words for one date: Home's recurring "evenings" (or
 * "Thursday evenings") becomes "evening" ("Thursday evening"), as B prints a
 * single dated meeting ("Evening"). Other words are kept as typed.
 *
 * @param string $words Time words.
 */
function spokares_time_words_once( string $words ): string {
	return (string) preg_replace(
		'/\b(morning|afternoon|evening|night|weeknight|weekend|lunchtime|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)s\b/iu',
		'$1',
		$words
	);
}

/**
 * The hub line of a meeting on a date: "Sat, Oct 10, 9:00 AM, Second Saturday
 * Workshop", or with time words for that one date: "Thu, Oct 15, evening,
 * Third Thursday training meeting".
 *
 * @param array  $m   Meeting.
 * @param string $ymd Date.
 */
function spokares_meeting_hub_line( array $m, string $ymd ): string {
	$when  = '' !== $m['start'] ? spokares_fmt_time( $m['start'] ) : spokares_time_words_once( $m['time_text'] );
	$parts = array_filter( array( spokares_fmt_date( $ymd, 'short' ), $when, $m['name'] ), static fn( $p ) => '' !== trim( (string) $p ) );
	return implode( ', ', $parts );
}

/**
 * Published events for the lists, as spokares_event_data() arrays.
 *
 * Views (§3.5):
 *  upcoming       types, limit, days: dated, of those kinds, not ended, starting within `days`
 *                 (cancelled ones too: they show with a "Cancelled" tag)
 *  next-up        types, limit: first N upcoming dated events of those kinds, never
 *                 a cancelled one
 *  later          types, cardTypes, cardLimit: every upcoming event of `types`
 *                 except the next-up cards; dated by start, then undated.
 *                 `limit` is not applied here: an event must never vanish
 *                 from Exercises (§2.3 #14), so the list is never cut
 *  public-service dated and not past, then undated
 *  past           limit: exercises kept after they end, newest first (never a
 *                 cancelled one)
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
			$events = array_values( array_filter( $events, static fn( $e ) => ! $e['cancelled'] ) );
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
			// Every one, whatever the block's `limit` says (§2.3 #14 over §3.5's 12).
			return array_merge( $dated, $undated );

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
			$list = array_values( array_filter( $events, static fn( $e ) => 'date' === $e['mode'] && ! $e['cancelled'] && in_array( $e['kind'], $types, true ) ) );
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
