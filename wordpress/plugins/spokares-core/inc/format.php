<?php
/**
 * Formatting (§3.3): 12-hour times, B's date styles, ranges, ordinals, radio
 * lines. Every block and the dashboard use these, so the site never prints
 * "2000" or "22:00".
 *
 * All functions return PLAIN TEXT (with U+00A0 where house style wants a
 * no-break space). Escape at output with spokares_text() or esc_html().
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * "20:00" → "8:00 PM"; "09:00" → "9:00 AM"; "12:00" → "noon".
 *
 * @param string $hhmm 24-hour time.
 */
function spokares_fmt_time( string $hhmm ): string {
	if ( ! spokares_is_hhmm( $hhmm ) ) {
		return '';
	}
	[ $h, $m ] = array_map( 'intval', explode( ':', $hhmm ) );
	if ( 12 === $h && 0 === $m ) {
		return 'noon';
	}
	$hour = $h % 12;
	return ( 0 === $hour ? 12 : $hour ) . ':' . sprintf( '%02d', $m ) . "\u{00A0}" . ( $h < 12 ? 'AM' : 'PM' );
}

/**
 * A time range: "12:30–3:30 PM" (same half of the day), "9:00 AM–noon",
 * "9:00 AM–1:00 PM". One time alone prints that time.
 *
 * @param string $start Start H:i.
 * @param string $end   End H:i or ''.
 */
function spokares_fmt_time_range( string $start, string $end ): string {
	if ( ! spokares_is_hhmm( $start ) ) {
		return '';
	}
	if ( ! spokares_is_hhmm( $end ) ) {
		return spokares_fmt_time( $start );
	}
	$s_noon = '12:00' === $start;
	$e_noon = '12:00' === $end;
	$s_pm   = (int) substr( $start, 0, 2 ) >= 12;
	$e_pm   = (int) substr( $end, 0, 2 ) >= 12;
	if ( ! $s_noon && ! $e_noon && $s_pm === $e_pm ) {
		// Same half of the day: drop the first AM/PM.
		$first = preg_replace( "/\u{00A0}(AM|PM)$/u", '', spokares_fmt_time( $start ) );
		return $first . '–' . spokares_fmt_time( $end );
	}
	return spokares_fmt_time( $start ) . '–' . spokares_fmt_time( $end );
}

/**
 * Should a date carry its year? Only when it is more than 183 days from today.
 *
 * @param string $ymd Date.
 */
function spokares_needs_year( string $ymd ): bool {
	return abs( spokares_days_between( spokares_today(), $ymd ) ) > 183;
}

/**
 * Format one date.
 *
 * Styles: 'short' "Sat, Oct 3" (+ ", 2027" when far away) · 'short-noyear' ·
 * 'long' "Sat, Sep 26, 2026" (as-of) · 'day' "Oct 10" · 'mdy' "Sep 26, 2026" ·
 * 'month' "Oct 2022" · 'year' "2025".
 *
 * @param string $ymd   Date.
 * @param string $style Style.
 */
function spokares_fmt_date( string $ymd, string $style = 'short' ): string {
	$d = spokares_date_obj( $ymd );
	if ( ! $d ) {
		return '';
	}
	switch ( $style ) {
		case 'long':
			return $d->format( 'D, M j, Y' );
		case 'day':
			return $d->format( 'M j' );
		case 'mdy':
			return $d->format( 'M j, Y' );
		case 'month':
			return $d->format( 'M Y' );
		case 'year':
			return $d->format( 'Y' );
		case 'short-noyear':
			return $d->format( 'D, M j' );
		case 'short':
		default:
			return $d->format( 'D, M j' ) . ( spokares_needs_year( $ymd ) ? $d->format( ', Y' ) : '' );
	}
}

/**
 * A date range: "Sat, Oct 3" · "Sat–Sun, Sep 26–27" · "Mon–Tue, Nov 30–Dec 1"
 * · three days or more "Nov 18–25" (no weekdays). The year is added when the
 * start is far from today; ranges across a new year always show both years.
 *
 * @param string $start Start date.
 * @param string $end   End date or ''.
 */
function spokares_fmt_date_range( string $start, string $end = '' ): string {
	$s = spokares_date_obj( $start );
	if ( ! $s ) {
		return '';
	}
	$e = spokares_date_obj( $end );
	if ( ! $e || $end <= $start ) {
		return spokares_fmt_date( $start, 'short' );
	}
	$days       = spokares_days_between( $start, $end ) + 1;
	$same_month = $s->format( 'Y-m' ) === $e->format( 'Y-m' );
	$same_year  = $s->format( 'Y' ) === $e->format( 'Y' );

	if ( ! $same_year ) {
		$fmt = 2 === $days ? 'D, M j, Y' : 'M j, Y';
		return $s->format( $fmt ) . '–' . $e->format( $fmt );
	}
	$year  = spokares_needs_year( $start ) ? $s->format( ', Y' ) : '';
	$dates = $same_month ? $s->format( 'M j' ) . '–' . $e->format( 'j' ) : $s->format( 'M j' ) . '–' . $e->format( 'M j' );
	if ( 2 === $days ) {
		return $s->format( 'D' ) . '–' . $e->format( 'D' ) . ', ' . $dates . $year;
	}
	return $dates . $year;
}

/**
 * The meta array of an event, read once (defaults filled).
 *
 * @param WP_Post|int $event Event.
 */
function spokares_event_data( $event ): array {
	$post = get_post( $event );
	if ( ! $post ) {
		return array();
	}
	$get  = static fn( string $k ) => get_post_meta( $post->ID, $k, true );
	$mode = (string) $get( 'spk_date_mode' );
	$data = array(
		'id'        => $post->ID,
		'slug'      => $post->post_name,
		'title'     => get_the_title( $post ),
		'status'    => $post->post_status,
		'menu'      => (int) $post->menu_order,
		'kind'      => (string) $get( 'spk_kind' ),
		'mode'      => in_array( $mode, array( 'date', 'not-posted', 'as-requested' ), true ) ? $mode : 'date',
		'start'     => (string) $get( 'spk_start' ),
		'end'       => (string) $get( 'spk_end' ),
		't_start'   => (string) $get( 'spk_time_start' ),
		't_end'     => (string) $get( 'spk_time_end' ),
		'summary'   => (string) $get( 'spk_summary' ),
		'where'     => (string) $get( 'spk_where' ),
		'tasks'     => (string) $get( 'spk_tasks' ),
		'main_url'  => (string) $get( 'spk_main_url' ),
		'main_lbl'  => (string) $get( 'spk_main_label' ),
		'links'     => is_array( $get( 'spk_links' ) ) ? $get( 'spk_links' ) : array(),
		'extra_doc' => absint( $get( 'spk_extra_doc' ) ),
		'contact'   => (string) $get( 'spk_contact_call' ),
		'keep_past' => '1' === (string) $get( 'spk_keep_past' ),
		'precision' => in_array( $get( 'spk_precision' ), array( 'day', 'month', 'year' ), true ) ? $get( 'spk_precision' ) : 'day',
		'check'     => '1' === (string) $get( 'spk_needs_check' ),
		'sort'      => (string) $get( 'spk_sort' ),
		'end_sort'  => (string) $get( 'spk_end_sort' ),
	);
	if ( 'as-requested' === $data['mode'] && 'public-service' !== $data['kind'] ) {
		$data['mode'] = 'not-posted';
	}
	if ( ! spokares_is_ymd( $data['start'] ) ) {
		$data['start'] = '';
	}
	if ( ! spokares_is_ymd( $data['end'] ) || $data['end'] <= $data['start'] ) {
		$data['end'] = '';
	}
	if ( 'date' === $data['mode'] && '' === $data['start'] ) {
		$data['mode'] = 'not-posted';
	}
	if ( ! spokares_is_hhmm( $data['t_start'] ) ) {
		$data['t_start'] = '';
		$data['t_end']   = '';
	}
	if ( ! spokares_is_hhmm( $data['t_end'] ) ) {
		$data['t_end'] = '';
	}
	return $data;
}

/**
 * Is the event happening now: started before today and not ended, or starts
 * today and runs past today.
 *
 * @param array $ev Event data.
 */
function spokares_event_is_now( array $ev ): bool {
	if ( 'date' !== $ev['mode'] ) {
		return false;
	}
	$today = spokares_today();
	$last  = '' !== $ev['end'] ? $ev['end'] : $ev['start'];
	if ( $ev['start'] < $today && $today <= $last ) {
		return true;
	}
	return $ev['start'] === $today && '' !== $ev['end'];
}

/**
 * Format an event's "when".
 *
 * Styles:
 *  'card'  "Sat–Sun, Sep 26–27", "Thu, Oct 15, 10:15 AM", "Sat, Oct 17, 9:00 AM–noon"
 *  'row'   as card, but undated prints "Date not posted" / "As requested"
 *  'meta'  hub line: "All day", "All weekend", "10:15 AM", "9:00 AM–noon"
 *  'past'  "Oct 2022" (day or month precision), "2025" (year precision)
 *
 * @param WP_Post|int|array $event Event, or spokares_event_data() output.
 * @param string            $style Style.
 */
function spokares_fmt_when( $event, string $style ): string {
	$ev = is_array( $event ) ? $event : spokares_event_data( $event );
	if ( ! $ev ) {
		return '';
	}
	if ( 'date' !== $ev['mode'] ) {
		if ( 'row' === $style || 'card' === $style ) {
			return 'as-requested' === $ev['mode'] ? __( 'As requested', 'spokares-core' ) : __( 'Date not posted', 'spokares-core' );
		}
		return '';
	}
	switch ( $style ) {
		case 'meta':
			if ( '' !== $ev['t_start'] ) {
				return spokares_fmt_time_range( $ev['t_start'], $ev['t_end'] );
			}
			if ( '' !== $ev['end'] && 2 === spokares_days_between( $ev['start'], $ev['end'] ) + 1 ) {
				$s = spokares_date_obj( $ev['start'] );
				if ( $s && '6' === $s->format( 'w' ) ) {
					return __( 'All weekend', 'spokares-core' );
				}
			}
			return __( 'All day', 'spokares-core' );
		case 'past':
			return spokares_fmt_date( $ev['start'], 'year' === $ev['precision'] ? 'year' : 'month' );
		case 'row':
		case 'card':
		default:
			$when = spokares_fmt_date_range( $ev['start'], $ev['end'] );
			if ( '' !== $ev['t_start'] ) {
				$when .= ', ' . spokares_fmt_time_range( $ev['t_start'], $ev['t_end'] );
			}
			return $when;
	}
}

/**
 * The date slab for the hub: mon "Sep", day "26–27", dow "Sat–Sun".
 *
 * @param array $ev Event data.
 * @return array{mon:string,day:string,dow:string,range:bool}
 */
function spokares_date_slab( array $ev ): array {
	$s = spokares_date_obj( $ev['start'] );
	if ( ! $s ) {
		return array(
			'mon'   => '',
			'day'   => '',
			'dow'   => '',
			'range' => false,
		);
	}
	$e = '' !== $ev['end'] ? spokares_date_obj( $ev['end'] ) : null;
	if ( ! $e ) {
		return array(
			'mon'   => $s->format( 'M' ),
			'day'   => $s->format( 'j' ),
			'dow'   => $s->format( 'D' ),
			'range' => false,
		);
	}
	return array(
		'mon'   => $s->format( 'M' ) === $e->format( 'M' ) ? $s->format( 'M' ) : $s->format( 'M' ) . '–' . $e->format( 'M' ),
		'day'   => $s->format( 'j' ) . '–' . $e->format( 'j' ),
		'dow'   => $s->format( 'D' ) . '–' . $e->format( 'D' ),
		'range' => true,
	);
}

/**
 * Ordinal for a week of the month in a sentence: 1st, 2nd, 3rd, 4th, fifth.
 *
 * @param int  $n       1-5.
 * @param bool $capital Capitalise "Fifth" (at the start of a line).
 */
function spokares_ordinal( int $n, bool $capital = false ): string {
	$words = array(
		1 => '1st',
		2 => '2nd',
		3 => '3rd',
		4 => '4th',
		5 => $capital ? 'Fifth' : 'fifth',
	);
	return $words[ $n ] ?? (string) $n;
}

/**
 * "2nd and 4th", "1st, 3rd and fifth".
 *
 * @param int[] $nths    Weeks.
 * @param bool  $capital Capitalise the first word.
 */
function spokares_ordinal_list( array $nths, bool $capital = false ): string {
	$nths  = array_values( array_unique( array_map( 'intval', $nths ) ) );
	$words = array();
	foreach ( $nths as $i => $n ) {
		$words[] = spokares_ordinal( $n, $capital && 0 === $i );
	}
	if ( count( $words ) <= 1 ) {
		return (string) ( $words[0] ?? '' );
	}
	$last = array_pop( $words );
	return implode( ', ', $words ) . ' and ' . $last;
}

/**
 * A number as a word for captions ("next five Tuesdays"); digits above ten.
 *
 * @param int $n Number.
 */
function spokares_number_word( int $n ): string {
	$w = array( 'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten' );
	return $w[ $n ] ?? (string) $n;
}

/**
 * Minus signs for display: "-600 kHz" → "−600 kHz" (U+2212).
 *
 * @param string $text Offset text.
 */
function spokares_minus( string $text ): string {
	return (string) preg_replace( '/(^|\s)-(\d)/u', "$1\u{2212}$2", $text );
}

/**
 * Radio setting lines, derived from spk_radio (never stored).
 *
 * Variants:
 *  'display'     "147.300 MHz, +600 kHz, 100 Hz tone"
 *  'bar'         "W7GBU 147.300 MHz, +600 kHz, 100 Hz"
 *  'copy'        "W7GBU 147.300 MHz, +600 kHz offset, 100 Hz tone"
 *  'alt-display' "146.880 MHz, −600 kHz, 123 Hz tone"
 *  'alt-copy'    "Alternate repeater 146.880 MHz, -600 kHz offset, 123 Hz tone"
 *  'freq'        "147.300 MHz"
 *
 * @param string $variant Variant.
 */
function spokares_radio_line( string $variant ): string {
	$radio = spokares_opt( 'spk_radio' );
	$alt   = str_starts_with( $variant, 'alt-' );
	$r     = $alt ? $radio['alternate'] : $radio['primary'];
	$freq  = '' !== $r['freq'] ? $r['freq'] . ' MHz' : '';
	$off   = (string) $r['offset'];
	$tone  = (string) $r['tone'];
	$call  = $alt ? '' : (string) $radio['primary']['call'];

	switch ( $variant ) {
		case 'freq':
			return spokares_nbsp( $freq );
		case 'bar':
			return spokares_nbsp( trim( $call . ' ' . implode( ', ', array_filter( array( $freq, spokares_minus( $off ), $tone ) ) ) ) );
		case 'copy':
		case 'alt-copy':
			$parts = array_filter(
				array(
					$freq,
					'' !== $off ? $off . ' offset' : '',
					'' !== $tone ? $tone . ' tone' : '',
				)
			);
			$lead  = $alt ? __( 'Alternate repeater', 'spokares-core' ) : $call;
			return trim( $lead . ' ' . implode( ', ', $parts ) );
		case 'display':
		case 'alt-display':
		default:
			$parts = array_filter(
				array(
					$freq,
					spokares_minus( $off ),
					'' !== $tone ? $tone . ' tone' : '',
				)
			);
			return spokares_nbsp( implode( ', ', $parts ) );
	}
}
