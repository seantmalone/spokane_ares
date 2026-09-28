<?php
/**
 * Render functions for the seven dynamic blocks (§6.4). Each returns markup
 * built from escaped values; blocks/<name>/render.php echoes it.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ events */

/**
 * spokares/events.
 *
 * @param array $a Attributes.
 */
function spokares_render_events( array $a ): string {
	$view  = (string) ( $a['view'] ?? 'upcoming' );
	$types = (string) ( $a['types'] ?? 'exercise,training' );
	$limit = (int) ( $a['limit'] ?? 2 );

	switch ( $view ) {
		case 'upcoming':
			$days   = max( 1, (int) ( $a['days'] ?? 60 ) );
			$events = spokares_events(
				'upcoming',
				array(
					'types' => $types,
					'limit' => $limit,
					'days'  => $days,
				)
			);
			$html   = spokares_block_open( $view );
			if ( ! $events ) {
				/* translators: %d: number of days. */
				$html .= '<p class="m-line">' . esc_html( sprintf( __( 'Nothing scheduled in the next %d days.', 'spokares-core' ), $days ) ) . '</p>';
			} else {
				$html .= '<ul class="events">';
				foreach ( $events as $ev ) {
					$now   = spokares_event_is_now( $ev );
					$slab  = spokares_date_slab( $ev );
					$html .= '<li class="event' . ( $now ? ' is-now' : '' ) . '">';
					$html .= '<span class="date-slab' . ( $slab['range'] ? ' date-slab--range' : '' ) . '" aria-hidden="true">'
						. '<span class="date-slab__mon">' . esc_html( $slab['mon'] ) . '</span>'
						. '<span class="date-slab__day">' . esc_html( $slab['day'] ) . '</span>'
						. '<span class="date-slab__dow">' . esc_html( $slab['dow'] ) . '</span></span>';
					$html .= '<span class="event__body"><span class="event__title">' . spokares_text( $ev['title'] ) . '</span>'
						. '<span class="event__meta"><span class="vh">' . spokares_text( spokares_fmt_date_range( $ev['start'], $ev['end'] ) ) . '. </span>'
						. spokares_text( spokares_fmt_when( $ev, 'meta' ) ) . spokares_cancelled_tag( $ev )
						. ( '' !== trim( $ev['where'] ) ? '<span class="event__where"> · ' . spokares_text( $ev['where'] ) . '</span>' : '' ) . '</span>';
					if ( $now ) {
						$html .= '<span class="event__now"><span class="tag tag--now">' . esc_html__( 'Happening now', 'spokares-core' ) . '</span></span>';
					}
					$html .= '</span></li>';
				}
				$html .= '</ul>';
			}
			return $html . spokares_block_tail( 'events' ) . '</div>';

		case 'next-up':
			$events = spokares_events(
				'next-up',
				array(
					'types' => $types,
					'limit' => $limit,
				)
			);
			$html   = spokares_block_open( $view );
			if ( ! $events ) {
				$html .= '<p class="m-line">' . esc_html__( 'No exercises scheduled yet.', 'spokares-core' ) . '</p>';
				return $html . spokares_block_tail( 'events' ) . '</div>';
			}
			$html .= '<div class="ex-cards">';
			foreach ( $events as $i => $ev ) {
				$html .= spokares_render_event_card( $ev, 0 === $i );
			}
			$html .= '</div>';
			return $html . spokares_block_tail( 'events' ) . '</div>';

		case 'later':
			// Every row, whatever `limit` says: an event must never vanish
			// from Exercises (§2.3 #14), and its row is the #slug anchor that
			// "View on site" and the save notice link to.
			$events = spokares_events(
				'later',
				array(
					'types'     => $types,
					'cardTypes' => (string) ( $a['cardTypes'] ?? 'exercise' ),
					'cardLimit' => (int) ( $a['cardLimit'] ?? 2 ),
				)
			);
			$html   = spokares_block_open( $view );
			if ( ! $events ) {
				$html .= '<p class="m-line">' . esc_html__( 'Nothing else scheduled yet.', 'spokares-core' ) . '</p>';
				return $html . spokares_block_tail( 'events' ) . '</div>';
			}
			$html .= '<table class="table ex-table ex-later">'
				. '<caption class="vh">' . esc_html__( 'Exercises and on-air events later this season', 'spokares-core' ) . '</caption>'
				. '<thead class="vh"><tr><th scope="col">' . esc_html__( 'When', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'What', 'spokares-core' ) . '</th></tr></thead><tbody>';
			foreach ( $events as $ev ) {
				$target = spokares_event_title_target( $ev );
				$title  = '' !== $target ? spokares_link( $target, $ev['title'] ) : spokares_text( $ev['title'] );
				$html  .= '<tr id="' . esc_attr( $ev['slug'] ) . '"' . ( $ev['check'] ? ' class="needs-verify"' : '' ) . '>'
					. '<th scope="row">' . spokares_text( spokares_fmt_when( $ev, 'row' ) ) . spokares_cancelled_tag( $ev ) . '</th>'
					. '<td>' . $title . spokares_later_rest( $ev, $target )
						. ( '' !== trim( $ev['where'] ) ? '<span class="ex-where"> ' . spokares_text( '(' . $ev['where'] . ')' ) . '</span>' : '' ) . '</td></tr>';
			}
			$html .= '</tbody></table>';
			return $html . spokares_block_tail( 'events' ) . '</div>';

		case 'public-service':
			$events = spokares_events( 'public-service' );
			$html   = spokares_block_open( $view );
			if ( ! $events ) {
				$html .= '<p class="m-line">' . esc_html__( 'No public-service events posted yet.', 'spokares-core' ) . '</p>';
				return $html . spokares_block_tail( 'events' ) . '</div>';
			}
			$html .= '<table class="table table--dense ex-table ex-public">'
				. '<caption class="vh">' . esc_html__( 'Public-service events', 'spokares-core' ) . '</caption>'
				. '<thead><tr><th scope="col">' . esc_html__( 'Event', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'When', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Volunteer through', 'spokares-core' ) . '</th></tr></thead><tbody>';
			foreach ( $events as $ev ) {
				$call  = spokares_call_sign( $ev['contact'] )['call'];
				$html .= '<tr id="' . esc_attr( $ev['slug'] ) . '"' . ( $ev['check'] ? ' class="needs-verify"' : '' ) . '>'
					. '<th scope="row">' . spokares_text( $ev['title'] ) . ( '' !== trim( $ev['where'] ) ? '<span class="ex-where"> ' . spokares_text( '(' . $ev['where'] . ')' ) . '</span>' : '' ) . '</th>'
					. '<td data-label="' . esc_attr__( 'When', 'spokares-core' ) . '">' . spokares_text( spokares_fmt_when( $ev, 'row' ) ) . spokares_cancelled_tag( $ev ) . '</td>'
					. '<td data-label="' . esc_attr__( 'Volunteer through', 'spokares-core' ) . '">' . esc_html( $call ) . '</td></tr>';
			}
			$html .= '</tbody></table>';
			return $html . spokares_block_tail( 'events' ) . '</div>';

		case 'past':
			$events = spokares_events( 'past', array( 'limit' => $limit ) );
			if ( ! $events ) {
				return spokares_block_placeholder( __( 'Past exercises appear here after they end.', 'spokares-core' ), 'events' );
			}
			$html = spokares_block_open( $view ) . '<ul class="ex-past">';
			foreach ( $events as $ev ) {
				$html .= '<li><span class="ex-past__when">' . esc_html( spokares_fmt_when( $ev, 'past' ) ) . '</span> '
					. '<span' . ( $ev['check'] ? ' class="needs-verify"' : '' ) . '>' . spokares_text( $ev['title'] ) . '</span></li>';
			}
			$html .= '</ul>';
			return $html . spokares_block_tail( 'events' ) . '</div>';
	}
	return spokares_block_placeholder( __( 'Pick a view for this list.', 'spokares-core' ) );
}

/**
 * The "Cancelled" tag printed after a cancelled event's When text (its row
 * stays listed until the date passes), or ''.
 *
 * @param array $ev Event data.
 */
function spokares_cancelled_tag( array $ev ): string {
	return empty( $ev['cancelled'] ) ? '' : ' <span class="tag tag--line">' . esc_html__( 'Cancelled', 'spokares-core' ) . '</span>';
}

/**
 * Where an event's title links in "Later this season": the main link, else
 * the first "more link" (never for public service).
 *
 * @param array $ev Event data.
 */
function spokares_event_title_target( array $ev ): string {
	if ( 'public-service' === $ev['kind'] ) {
		return '';
	}
	$main = spokares_clean_url( $ev['main_url'] );
	if ( '' !== $main ) {
		return $main;
	}
	foreach ( $ev['links'] as $link ) {
		$url = spokares_clean_url( (string) ( $link['url'] ?? '' ) );
		if ( '' !== $url ) {
			return $url;
		}
	}
	return '';
}

/**
 * An event's usable "more links" (a URL and a label each), in order.
 *
 * @param array $links Stored links.
 * @return array<int,array{label:string,url:string}>
 */
function spokares_clean_links( array $links ): array {
	$out = array();
	foreach ( $links as $link ) {
		$url   = spokares_clean_url( (string) ( $link['url'] ?? '' ) );
		$label = trim( (string) ( $link['label'] ?? '' ) );
		if ( '' !== $url && '' !== $label ) {
			$out[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}
	return $out;
}

/**
 * Another site's name as a link label on its own line: "shakeout.org",
 * "USGS", "The National Weather Service" ('' for a link to this site).
 *
 * @param string $url URL.
 */
function spokares_site_label( string $url ): string {
	if ( ! spokares_is_external( $url ) ) {
		return '';
	}
	$name = spokares_host_name( $url );
	return str_starts_with( $name, 'the ' ) ? 'The ' . substr( $name, 4 ) : $name;
}

/**
 * Make links inside an escaped short line: a link whose words appear in the
 * line is made there (B's ShakeOut line links "DYFI" inside it). Returns the
 * line and the links that found no place in it.
 *
 * @param string $summary Escaped text.
 * @param array  $links   spokares_clean_links() entries.
 * @return array{0:string,1:array}
 */
function spokares_weave_links( string $summary, array $links ): array {
	$left = array();
	foreach ( $links as $link ) {
		$needle = spokares_text( $link['label'] );
		$pos    = '' !== $summary ? spokares_word_pos( $summary, $needle ) : -1;
		if ( $pos >= 0 ) {
			$summary = substr( $summary, 0, $pos ) . spokares_link( $link['url'], $link['label'] ) . substr( $summary, $pos + strlen( $needle ) );
		} else {
			$left[] = $link;
		}
	}
	return array( $summary, $left );
}

/**
 * The rest of a "Later this season" row after the title: ": summary", with
 * the event's other links kept. A link whose words appear in the summary is
 * made there; any other link follows the summary, after ". " (or just a
 * space when the summary already ends in a mark: "asks you to: Forms").
 *
 * @param array  $ev     Event data.
 * @param string $target The URL the title already links to ('' for none).
 */
function spokares_later_rest( array $ev, string $target ): string {
	$summary = '' !== $ev['summary'] ? spokares_text( $ev['summary'] ) : '';
	$extra   = array();
	if ( 'public-service' !== $ev['kind'] ) {
		$links = $ev['links'];
		$main  = spokares_clean_url( $ev['main_url'] );
		if ( '' !== $main && '' !== $target && $main !== $target ) {
			array_unshift(
				$links,
				array(
					'label' => '' !== trim( $ev['main_lbl'] ) ? $ev['main_lbl'] : spokares_default_main_label( $main ),
					'url'   => $main,
				)
			);
		}
		$links                   = array_values( array_filter( spokares_clean_links( array_slice( $links, 0, 4 ) ), static fn( $l ) => $l['url'] !== $target ) );
		list( $summary, $extra ) = spokares_weave_links( $summary, $links );
	}
	$out = '' !== $summary ? ': ' . $summary : '';
	if ( $extra ) {
		$join = '' === $out ? ': ' : ( '' !== spokares_end_mark( $ev['summary'] ) ? ' ' : '. ' );
		$out .= $join . implode( ', ', array_map( static fn( $l ) => spokares_link( $l['url'], $l['label'] ), $extra ) );
	}
	return $out;
}

/**
 * Where a whole word (or phrase) first appears in escaped text, outside any
 * link already in it, or -1.
 *
 * @param string $html   Escaped text (may already hold links).
 * @param string $needle Escaped words.
 */
function spokares_word_pos( string $html, string $needle ): int {
	if ( ! preg_match_all( '/<[^>]*>|[^<]+/', $html, $parts, PREG_OFFSET_CAPTURE ) ) {
		return -1;
	}
	$depth = 0;
	foreach ( $parts[0] as $part ) {
		if ( str_starts_with( $part[0], '<' ) ) {
			if ( str_starts_with( $part[0], '</a' ) ) {
				--$depth;
			} elseif ( preg_match( '/^<a[\s>]/i', $part[0] ) ) {
				++$depth;
			}
			continue;
		}
		if ( $depth > 0 ) {
			continue;
		}
		if ( preg_match( '/(?<![\p{L}\p{N}])' . preg_quote( $needle, '/' ) . '(?![\p{L}\p{N}])/u', $part[0], $m, PREG_OFFSET_CAPTURE ) ) {
			return $part[1] + $m[0][1];
		}
	}
	return -1;
}

/**
 * The default words of an event's main button.
 *
 * @param string $url Main link.
 */
function spokares_default_main_label( string $url ): string {
	return 'spokaneares-acs.groups.io' === spokares_url_host( $url )
		? __( 'Exercise details on groups.io', 'spokares-core' )
		: __( 'Details', 'spokares-core' );
}

/**
 * One "Next up" card.
 *
 * @param array $ev    Event data.
 * @param bool  $first The first card is red with the page's one red button.
 */
function spokares_render_event_card( array $ev, bool $first ): string {
	$classes = 'card ex-card' . ( $first ? ' card--red' : '' ) . ( $ev['check'] ? ' needs-verify' : '' );
	$links   = 'public-service' !== $ev['kind'] ? spokares_clean_links( array_slice( $ev['links'], 0, 3 ) ) : array();
	$html    = '<article class="' . esc_attr( $classes ) . '" id="' . esc_attr( $ev['slug'] ) . '">';
	$html   .= '<h3>' . spokares_text( $ev['title'] ) . '</h3>';
	$html   .= '<p class="ex-when"><time datetime="' . esc_attr( $ev['start'] ) . '">' . spokares_text( spokares_fmt_when( $ev, 'card' ) ) . '</time>' . spokares_cancelled_tag( $ev );
	if ( spokares_event_is_now( $ev ) ) {
		$html .= ' <span class="ex-status"><span class="tag tag--now">' . esc_html__( 'Happening now', 'spokares-core' ) . '</span></span>';
	}
	$html .= '</p>';
	if ( '' !== trim( $ev['where'] ) ) {
		$html .= '<p class="ex-where"><span class="vh">' . esc_html__( 'Where:', 'spokares-core' ) . ' </span>' . spokares_text( $ev['where'] ) . '</p>';
	}
	if ( '' !== trim( $ev['summary'] ) ) {
		// The list fragment ("send a DYFI report by Winlink…") stands alone on
		// a card, so it is printed as a sentence, with its links made inside it.
		list( $summary, $links ) = spokares_weave_links( spokares_text( spokares_sentence( $ev['summary'] ) ), array_values( $links ) );
		$html                   .= '<p class="ex-summary">' . $summary . '</p>';
	}
	if ( 'exercise' === $ev['kind'] ) {
		$tasks = array_slice( array_values( array_filter( array_map( 'trim', explode( "\n", $ev['tasks'] ) ) ) ), 0, 10 );
		if ( $tasks ) {
			$html .= '<ol class="ex-tasks">';
			foreach ( $tasks as $task ) {
				$html .= '<li>' . spokares_text( $task ) . '</li>';
			}
			$html .= '</ol>';
		}
		$doc = $ev['extra_doc'] ? spokares_public_document( $ev['extra_doc'] ) : null;
		if ( $doc ) {
			$html .= '<p class="ex-form">' . esc_html__( 'You’ll need:', 'spokares-core' ) . ' <a href="' . esc_url( spokares_site_url( '/members/documents/', $doc->post_name ) ) . '">' . spokares_text( get_the_title( $doc ) ) . '</a></p>';
		}
	}
	if ( 'public-service' !== $ev['kind'] ) {
		foreach ( $links as $link ) {
			// A more link named like the event ("Great ShakeOut", which titles
			// the Later this season row) would repeat the card's title: its line
			// names the site instead.
			if ( spokares_same_words( $link['label'], $ev['title'] ) ) {
				$site          = spokares_site_label( $link['url'] );
				$link['label'] = '' !== $site ? $site : __( 'Details', 'spokares-core' );
			}
			$html .= '<p class="ex-link">' . spokares_link( $link['url'], $link['label'] ) . '</p>';
		}
		$main = spokares_clean_url( $ev['main_url'] );
		if ( '' !== $main ) {
			$label = '' !== trim( $ev['main_lbl'] ) ? $ev['main_lbl'] : spokares_default_main_label( $main );
			$html .= '<p class="ex-action">' . spokares_link( $main, $label, $first ? 'btn btn--red' : 'btn btn--ghost' ) . '</p>';
		}
	}
	return $html . '</article>';
}

/**
 * A document that may appear on the site (published and privacy-checked), or null.
 *
 * @param int $id Document ID.
 */
function spokares_public_document( int $id ): ?WP_Post {
	$doc = get_post( $id );
	if ( ! $doc || 'spk_document' !== $doc->post_type || 'publish' !== $doc->post_status ) {
		return null;
	}
	return '1' === (string) get_post_meta( $doc->ID, 'spk_privacy_ok', true ) ? $doc : null;
}

/* --------------------------------------------------------------------- net */

/**
 * spokares/net.
 *
 * @param array $a Attributes.
 */
function spokares_render_net( array $a ): string {
	$view  = (string) ( $a['view'] ?? 'rota' );
	$nets  = spokares_opt( 'spk_nets' );
	$radio = spokares_opt( 'spk_radio' );
	$p     = $radio['primary'];
	$time  = spokares_fmt_time( $nets['net_time'] );

	switch ( $view ) {
		case 'bar':
			$first  = trim( $p['call'] . ' ' . ( '' !== $p['freq'] ? $p['freq'] . ' MHz' : '' ) );
			$second = implode( ', ', array_filter( array( spokares_minus( $p['offset'] ), $p['tone'] ) ) );
			$html   = spokares_block_open( $view ) . '<div class="netbar dark' . ( mb_strlen( $first . ' ' . $second, 'UTF-8' ) > 35 ? ' netbar--long' : '' ) . '"><p class="netbar__line">'
				. '<span class="netbar__time">' . spokares_text( $time ) . '</span> <span class="netbar__set">'
				. '<span class="nowrap">' . spokares_text( $first . ( '' !== $second ? ',' : '' ) ) . '</span>'
				. ( '' !== $second ? ' <span class="nowrap">' . spokares_text( $second ) . '</span>' : '' )
				. '</span></p>'
				. spokares_copy_button( spokares_radio_line( 'copy' ), __( 'radio settings', 'spokares-core' ), 'btn btn--light btn--sm' )
				. '</div>';
			wp_enqueue_script_module( '@spokares/copy' );
			// Not a list, and the schedule under it has its own link.
			return $html . spokares_block_tail( 'net-details', true, spokares_settings_link_words() ) . '</div>';

		case 'settings':
			$alt  = $radio['alternate'];
			$html = spokares_block_open( $view ) . '<div class="netbox" id="weekly-net">'
				/* translators: %s: time, e.g. "8:00 PM". */
				. '<p class="netbox__when">' . spokares_text( sprintf( __( 'Every Tuesday, %s', 'spokares-core' ), $time ) ) . '</p>'
				. '<dl class="netbox__rows">'
				. '<div><dt>' . esc_html( $p['call'] ) . '</dt><dd>' . spokares_text( spokares_radio_line( 'display' ) ) . '</dd>'
				/* translators: %s: call sign, e.g. "W7GBU". */
				. '<dd>' . spokares_copy_button( spokares_radio_line( 'copy' ), sprintf( __( '%s settings', 'spokares-core' ), $p['call'] ), 'btn btn--ghost btn--sm netbox__copy' ) . '</dd></div>';
			if ( $alt['show'] && '' !== $alt['freq'] ) {
				$html .= '<div' . ( $alt['needs_check'] ? ' class="needs-verify"' : '' ) . '><dt>' . esc_html__( 'Alternate', 'spokares-core' ) . '</dt><dd>' . spokares_text( spokares_radio_line( 'alt-display' ) ) . '</dd>'
					. '<dd>' . spokares_copy_button( spokares_radio_line( 'alt-copy' ), __( 'alternate settings', 'spokares-core' ), 'btn btn--ghost btn--sm netbox__copy' ) . '</dd></div>';
			}
			$html .= '</dl></div>';
			wp_enqueue_script_module( '@spokares/copy' );
			return $html . spokares_block_tail( 'net-details', true, spokares_settings_link_words() ) . '</div>';

		case 'rota':
			$weeks = max( 1, min( 26, (int) ( $a['weeks'] ?? 5 ) ) );
			$html  = spokares_block_open( $view ) . '<table class="table table--dense hub-rota">'
				/* translators: %s: a number word, e.g. "five". */
				. '<caption class="vh">' . esc_html( sprintf( __( 'Net control, next %s Tuesdays', 'spokares-core' ), spokares_number_word( $weeks ) ) ) . '</caption>'
				. '<thead><tr><th scope="col">' . esc_html__( 'Tuesday', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Net control', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Note', 'spokares-core' ) . '</th></tr></thead><tbody>';
			foreach ( spokares_rota_rows( $weeks ) as $i => $row ) {
				$html .= '<tr' . ( 0 === $i ? ' class="is-next"' : '' ) . '><th scope="row"><time datetime="' . esc_attr( $row['date'] ) . '">' . esc_html( spokares_fmt_date( $row['date'], 'short' ) ) . '</time></th>'
					. '<td data-label="' . esc_attr__( 'Net control', 'spokares-core' ) . '">' . spokares_rota_who( $row ) . '</td>'
					. '<td data-label="' . esc_attr__( 'Note', 'spokares-core' ) . '"' . ( '' !== $row['kind'] && $nets['needs_check'] ? ' class="needs-verify"' : '' ) . '>' . spokares_rota_note( $row ) . '</td></tr>';
			}
			$html .= '</tbody></table>';
			if ( '' !== trim( $nets['open_slot_line'] ) ) {
				$html .= '<p class="hub-open" id="open-slot">' . spokares_text( $nets['open_slot_line'] ) . '</p>';
			}
			return $html . spokares_block_tail( 'rota' ) . '</div>';

		case 'winlink':
			$limit = max( 1, (int) ( $a['limit'] ?? 8 ) );
			$today = spokares_today();
			$rows  = array();
			foreach ( spokares_opt( 'spk_rota' ) as $ymd => $row ) {
				// Only a Tuesday the rota calls a Winlink night: a task typed
				// under weeks Net details has since moved is not listed.
				// No net that Tuesday: no assignment either.
				if ( $ymd >= $today && 'none' !== ( $row['state'] ?? '' ) && '' !== trim( $row['wl_task'] ) && 2 === spokares_weekday( (string) $ymd ) && 'winlink' === spokares_net_on( (string) $ymd )['kind'] ) {
					$rows[ $ymd ] = $row;
				}
			}
			ksort( $rows );
			$rows = array_slice( $rows, 0, $limit, true );
			$html = spokares_block_open( $view );
			if ( '' !== trim( $nets['winlink_howto'] ) ) {
				$html .= '<p class="m-line ex-howto">' . spokares_text( $nets['winlink_howto'] ) . '</p>';
			}
			if ( ! $rows ) {
				$html .= '<p class="m-line">' . esc_html__( 'No assignments posted yet.', 'spokares-core' ) . '</p>';
				return $html . spokares_block_tail( 'rota' ) . '</div>';
			}
			$html .= '<table class="table table--dense ex-table ex-winlink"><caption class="vh">' . esc_html__( 'Winlink assignments', 'spokares-core' ) . '</caption>'
				. '<thead><tr><th scope="col">' . esc_html__( 'Date', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Assignment', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Form', 'spokares-core' ) . '</th></tr></thead><tbody>';
			$i     = 0;
			foreach ( $rows as $ymd => $row ) {
				$html .= '<tr' . ( 0 === $i ? ' class="is-next"' : '' ) . '><th scope="row"><time datetime="' . esc_attr( $ymd ) . '">' . esc_html( spokares_fmt_date( $ymd, 'short' ) ) . '</time></th>'
					. '<td data-label="' . esc_attr__( 'Assignment', 'spokares-core' ) . '">' . spokares_text( $row['wl_task'] ) . '</td>'
					. '<td data-label="' . esc_attr__( 'Form', 'spokares-core' ) . '">' . spokares_text( $row['wl_form'] ) . '</td></tr>';
				++$i;
			}
			$html .= '</tbody></table>';
			return $html . spokares_block_tail( 'rota' ) . '</div>';

		case 'from-home':
			$html = spokares_block_open( $view ) . '<p><strong>' . esc_html__( 'From home:', 'spokares-core' ) . '</strong> '
				. esc_html__( 'listen to the Tuesday net,', 'spokares-core' ) . ' <strong>' . spokares_text( $time ) . '</strong>, '
				. ( '' !== $p['freq'] ? '<strong>' . spokares_text( spokares_radio_line( 'freq' ) ) . '</strong>, ' : '' )
				. esc_html__( 'on any scanner or 2-meter radio. No license needed.', 'spokares-core' ) . '</p>';
			return $html . spokares_block_tail( 'net-details', true, spokares_settings_link_words() ) . '</div>';

		case 'other-nets':
			// Each week once, under the group the rota gives it.
			$weeks = spokares_net_weeks( $nets );
			$items = array();
			if ( $weeks['winlink'] ) {
				$items[] = '<li><strong>' . esc_html__( 'Winlink nights:', 'spokares-core' ) . '</strong> '
					/* translators: %s: weeks, e.g. "2nd and 4th". */
					. esc_html( sprintf( __( '%s Tuesdays; net control gives a', 'spokares-core' ), spokares_ordinal_list( $weeks['winlink'] ) ) )
					. ' <a href="' . esc_url( spokares_site_url( '/members/exercises/', 'winlink-assignments' ) ) . '">' . esc_html__( 'Winlink assignment', 'spokares-core' ) . '</a> '
					. esc_html__( 'during the net.', 'spokares-core' ) . '</li>';
			}
			if ( $weeks['simplex'] ) {
				$items[] = '<li><strong>' . esc_html(
					/* translators: %s: weeks, e.g. "Fifth". */
					sprintf( __( '%s Tuesdays:', 'spokares-core' ), spokares_ordinal_list( $weeks['simplex'], true ) )
				) . '</strong> '
					/* translators: %s: repeater call sign. */
					. esc_html( sprintf( __( 'the net starts on simplex, then moves to %s.', 'spokares-core' ), $p['call'] ) ) . '</li>';
			}
			if ( $weeks['gmrs'] ) {
				$gmrs    = spokares_fmt_time( $nets['gmrs_time'] );
				$items[] = '<li><strong>' . esc_html__( 'ACS GMRS net:', 'spokares-core' ) . '</strong> '
					. spokares_text(
						'' !== $gmrs
							/* translators: 1: weeks, e.g. "3rd"; 2: time. */
							? sprintf( __( '%1$s Tuesdays, %2$s, for county volunteers with GMRS licenses.', 'spokares-core' ), spokares_ordinal_list( $weeks['gmrs'] ), $gmrs )
							/* translators: %s: weeks, e.g. "3rd". */
							: sprintf( __( '%s Tuesdays, for county volunteers with GMRS licenses.', 'spokares-core' ), spokares_ordinal_list( $weeks['gmrs'] ) )
					) . '</li>';
			}
			if ( ! $items ) {
				if ( spokares_is_editor_preview() ) {
					return spokares_block_placeholder( __( 'No other nets are set on the Net Settings screen.', 'spokares-core' ), 'net-details' );
				}
				// The "Other nets" heading is page text, so say so rather than leave it empty.
				return spokares_block_open( $view ) . '<p>' . esc_html__( 'No other nets are scheduled right now.', 'spokares-core' ) . '</p>'
					. spokares_block_tail( 'net-details' ) . '</div>';
			}
			$html = spokares_block_open( $view ) . '<ul class="lines' . ( $nets['needs_check'] ? ' needs-verify' : '' ) . '">' . implode( '', $items ) . '</ul>';
			return $html . spokares_block_tail( 'net-details' ) . '</div>';
	}
	return spokares_block_placeholder( __( 'Pick a view for this list.', 'spokares-core' ) );
}

/**
 * A copy button (hidden until the copy module runs).
 *
 * @param string $text    Text to copy.
 * @param string $what    Visually hidden end of the label.
 * @param string $classes Button classes.
 */
function spokares_copy_button( string $text, string $what, string $classes ): string {
	return '<button class="' . esc_attr( $classes ) . '" type="button" data-copy-text="' . esc_attr( $text ) . '" hidden>'
		. spokares_icon( 'copy' ) . esc_html__( 'Copy', 'spokares-core' ) . '<span class="vh"> ' . esc_html( $what ) . '</span></button>';
}

/**
 * The Net control cell of a Net Control Schedule row: the call sign,
 * "Volunteer needed" (it links to the line under the table), "No net" or
 * "Not posted yet".
 *
 * @param array $row spokares_net_on() row.
 */
function spokares_rota_who( array $row ): string {
	if ( 'call' === $row['state'] && '' !== $row['call'] ) {
		return '<b>' . esc_html( $row['call'] ) . '</b>';
	}
	if ( 'open' === $row['state'] ) {
		return '<a class="tag tag--open" href="#open-slot">' . esc_html__( 'Volunteer needed', 'spokares-core' ) . '</a>';
	}
	if ( 'none' === $row['state'] ) {
		return '<span class="rota-tbd rota-none">' . esc_html__( 'No net', 'spokares-core' ) . '</span>';
	}
	return '<span class="rota-tbd">' . esc_html__( 'Not posted yet', 'spokares-core' ) . '</span>';
}

/**
 * The Note cell of a Net Control Schedule row: the rule note (linked where
 * it helps), then the row's own note after "; ". A No net Tuesday has only
 * its own note (the rule note would name a net that isn't held).
 *
 * @param array $row spokares_net_on() row.
 */
function spokares_rota_note( array $row ): string {
	if ( 'none' === $row['state'] ) {
		return '' !== trim( $row['row_note'] ) ? spokares_text( $row['row_note'] ) : '';
	}
	$parts = array();
	if ( 'simplex' === $row['kind'] ) {
		$parts[] = '<a href="' . esc_url( spokares_site_url( '/members/documents/', 'net-scripts' ) ) . '">' . esc_html( $row['note'] ) . '</a>';
	} elseif ( 'winlink' === $row['kind'] ) {
		$parts[] = '<a href="' . esc_url( spokares_site_url( '/members/exercises/', 'winlink-assignments' ) ) . '">' . esc_html( $row['note'] ) . '</a>';
	} elseif ( '' !== $row['note'] ) {
		$parts[] = spokares_text( $row['note'] );
	}
	if ( '' !== trim( $row['row_note'] ) ) {
		$parts[] = spokares_text( $row['row_note'] );
	}
	return implode( '; ', $parts );
}

/* ---------------------------------------------------------------- meetings */

/**
 * spokares/meetings.
 *
 * @param array $a Attributes.
 */
function spokares_render_meetings( array $a ): string {
	$view = (string) ( $a['view'] ?? 'home' );
	$all  = spokares_next_meetings( 1 );

	if ( 'home' === $view ) {
		$place = spokares_opt( 'spk_site' )['place'];
		$where = implode( ', ', array_filter( array( $place['name'], $place['street'], $place['city'] ), static fn( $x ) => '' !== trim( (string) $x ) ) );
		$html  = spokares_block_open( $view ) . '<p' . ( $place['needs_check'] ? ' class="needs-verify"' : '' ) . '><strong>' . esc_html__( 'In person', 'spokares-core' ) . '</strong>'
			. ( '' !== $where ? ' ' . esc_html__( 'at', 'spokares-core' ) . ' ' . spokares_text( $where ) : '' ) . ':</p>';
		$items = '';
		foreach ( $all as $item ) {
			$m = $item['meeting'];
			if ( ! $m['show_home'] ) {
				continue;
			}
			$items .= '<li><span' . ( $m['needs_check'] ? ' class="needs-verify"' : '' ) . '>' . spokares_text( spokares_meeting_home_line( $m ) ) . '</span>';
			$next   = $item['dates'][0] ?? null;
			if ( $next ) {
				$extra = '';
				if ( 'moved' === $next['kind'] ) {
					/* translators: %s: short date, e.g. "Oct 10". */
					$extra = ' ' . sprintf( __( '(moved from %s)', 'spokares-core' ), spokares_fmt_date( $next['orig'], 'day' ) );
				} elseif ( 'note' === $next['kind'] && '' !== trim( $next['note'] ) ) {
					// That date's own words: "(Starts at 10:00 AM this time)".
					$extra = ' (' . rtrim( trim( $next['note'] ), '.' ) . ')';
				} elseif ( $item['cancelled'] ) {
					/* translators: %s: short dates, e.g. "Oct 10". */
					$extra = ' ' . sprintf( __( '(%s cancelled)', 'spokares-core' ), spokares_and_list( array_map( static fn( $d ) => spokares_fmt_date( $d, 'day' ), $item['cancelled'] ) ) );
				}
				/* translators: %s: date, e.g. "Sat, Oct 10". */
				$items .= '<span class="meetings__next">' . spokares_text( sprintf( __( 'Next: %s', 'spokares-core' ), spokares_fmt_date( $next['date'], 'short' ) ) . $extra ) . '</span>';
			}
			$items .= '</li>';
		}
		if ( '' !== $items ) {
			$html .= '<ul class="meetings">' . $items . '</ul>';
		}
		return $html . spokares_block_tail( 'meetings' ) . '</div>';
	}

	if ( 'next' === $view ) {
		$only  = sanitize_key( (string) ( $a['meeting'] ?? '' ) );
		$today = spokares_today();
		$soon  = spokares_add_days( $today, 14 );
		$pick  = array_values(
			array_filter(
				$all,
				static fn( $x ) => '' !== $only ? $x['meeting']['id'] === $only : $x['meeting']['show_home']
			)
		);
		$lines = array();
		foreach ( $pick as $item ) {
			foreach ( $item['changes'] as $c ) {
				if ( $c['date'] < $today || $c['date'] > $soon ) {
					continue;
				}
				$date = spokares_fmt_date( $c['date'], 'short' );
				$name = $item['meeting']['name'];
				if ( 'moved' === $c['kind'] && spokares_is_ymd( $c['new_date'] ) ) {
					/* translators: 1: date, 2: meeting name, 3: new date. */
					$text = sprintf( __( '%1$s: %2$s moved to %3$s.', 'spokares-core' ), $date, $name, spokares_fmt_date( $c['new_date'], 'short' ) );
				} elseif ( 'note' === $c['kind'] ) {
					// A note on its own: "Sat, Nov 14: Second Saturday Workshop. Starts at 10:00 AM this time."
					/* translators: 1: date, 2: meeting name. */
					$text      = sprintf( __( '%1$s: %2$s.', 'spokares-core' ), $date, $name );
					$c['note'] = spokares_sentence( $c['note'] );
				} else {
					/* translators: 1: date, 2: meeting name. */
					$text = sprintf( __( '%1$s: %2$s cancelled.', 'spokares-core' ), $date, $name );
				}
				if ( '' !== trim( $c['note'] ) ) {
					$text .= ' ' . $c['note'];
				}
				$lines[ $c['date'] . $item['meeting']['id'] ] = $text;
			}
		}
		ksort( $lines );
		usort(
			$pick,
			static fn( $x, $y ) => strcmp( $x['dates'][0]['date'] ?? '9999-12-31', $y['dates'][0]['date'] ?? '9999-12-31' )
		);
		$html = spokares_block_open( $view );
		foreach ( $lines as $text ) {
			$html .= '<p class="m-line hub-meeting-change">' . spokares_text( $text ) . '</p>';
		}
		$first = $pick[0] ?? null;
		if ( $first && ! empty( $first['dates'][0] ) ) {
			$html .= '<p class="m-line hub-meeting"><b>' . esc_html__( 'Next meeting:', 'spokares-core' ) . '</b> '
				. spokares_text( spokares_meeting_hub_line( $first['meeting'], $first['dates'][0]['date'] ) ) . '</p>';
		}
		return $html . spokares_block_tail( 'meetings' ) . '</div>';
	}
	return spokares_block_placeholder( __( 'Pick a view for this list.', 'spokares-core' ) );
}

/* -------------------------------------------------------------------- docs */

/**
 * Library sections from the taxonomy, ordered by spk_order (slug => label).
 */
function spokares_library_sections(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'spk_doc_cat',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}
	usort(
		$terms,
		static fn( $x, $y ) => array( (int) get_term_meta( $x->term_id, 'spk_order', true ), $x->name ) <=> array( (int) get_term_meta( $y->term_id, 'spk_order', true ), $y->name )
	);
	$out = array();
	foreach ( $terms as $t ) {
		$out[ $t->slug ] = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
	}
	return $out;
}

/**
 * One document's library data (published and privacy-checked only), or null.
 *
 * @param WP_Post $post Document.
 */
function spokares_document_data( WP_Post $post ): ?array {
	$get    = static fn( string $k ) => get_post_meta( $post->ID, $k, true );
	$source = (string) $get( 'spk_source' );
	$source = in_array( $source, array( 'upload', 'link', 'soon' ), true ) ? $source : 'soon';
	$file   = absint( $get( 'spk_file' ) );
	$url    = '';
	if ( 'upload' === $source ) {
		$url = $file ? (string) wp_get_attachment_url( $file ) : '';
		if ( '' === $url ) {
			$source = 'soon';
		}
	} elseif ( 'link' === $source ) {
		$url = spokares_clean_url( (string) $get( 'spk_url' ) );
		if ( '' === $url ) {
			$source = 'soon';
		}
	}
	$terms = get_the_terms( $post, 'spk_doc_cat' );
	return array(
		'id'        => $post->ID,
		'slug'      => $post->post_name,
		'title'     => get_the_title( $post ),
		'menu'      => (int) $post->menu_order,
		'section'   => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
		'source'    => $source,
		'file'      => $file,
		'url'       => $url,
		'label'     => (string) $get( 'spk_source_label' ),
		'format'    => (string) $get( 'spk_format' ),
		'version'   => (string) $get( 'spk_version' ),
		'note'      => (string) $get( 'spk_note' ),
		'howto_lbl' => (string) $get( 'spk_howto_label' ),
		'howto_url' => spokares_clean_url( (string) $get( 'spk_howto_url' ) ),
		'sublinks'  => is_array( $get( 'spk_sublinks' ) ) ? $get( 'spk_sublinks' ) : array(),
		'most_used' => '1' === (string) $get( 'spk_most_used' ),
		'keywords'  => (string) $get( 'spk_keywords' ),
		'check'     => '1' === (string) $get( 'spk_needs_check' ),
	);
}

/**
 * Every published, privacy-checked document, grouped by section, in order.
 *
 * @return array<string,array<int,array>> section slug => documents
 */
function spokares_library_documents(): array {
	$posts = get_posts(
		array(
			'post_type'        => 'spk_document',
			'post_status'      => 'publish',
			'numberposts'      => 500,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'suppress_filters' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a small club library.
			'meta_query'       => array(
				array(
					'key'   => 'spk_privacy_ok',
					'value' => '1',
				),
			),
		)
	);
	// The four Most Used buttons' documents are always under the Most used
	// filter, with any document ticked "Also list under Most used".
	$tiles  = array_filter( array_map( 'absint', wp_list_pluck( array_slice( (array) spokares_opt( 'spk_tiles' ), 0, 4 ), 'doc' ) ) );
	$groups = array();
	foreach ( $posts as $post ) {
		$doc = spokares_document_data( $post );
		if ( $doc && '' !== $doc['section'] ) {
			$doc['most_used']            = $doc['most_used'] || in_array( (int) $doc['id'], $tiles, true );
			$groups[ $doc['section'] ][] = $doc;
		}
	}
	return $groups;
}

/**
 * The lowercase search text of a document row.
 *
 * @param array $doc Document data.
 */
function spokares_document_haystack( array $doc ): string {
	$parts = array( $doc['title'], $doc['note'], $doc['keywords'], $doc['format'], $doc['version'], $doc['slug'] );
	foreach ( $doc['sublinks'] as $s ) {
		$parts[] = (string) ( $s['label'] ?? '' );
		$parts[] = (string) ( $s['title'] ?? '' );
		$parts[] = (string) ( $s['id'] ?? '' );
	}
	$text = html_entity_decode( wp_strip_all_tags( implode( ' ', $parts ) ), ENT_QUOTES, 'UTF-8' );
	return (string) preg_replace( '/\s+/u', ' ', mb_strtolower( $text, 'UTF-8' ) );
}

/**
 * The library's query, read from the URL: q (≤ 100 characters) and section
 * (an existing section slug or "most-used"; anything else is ignored).
 *
 * @param array $sections Section slugs => labels.
 * @return array{q:string,section:string}
 */
function spokares_library_query( array $sections ): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a public, read-only search form.
	$q       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
	// phpcs:enable
	$q = trim( mb_substr( $q, 0, 100, 'UTF-8' ) );
	if ( 'most-used' !== $section && ! isset( $sections[ $section ] ) ) {
		$section = '';
	}
	return array(
		'q'       => $q,
		'section' => $section,
	);
}

/**
 * spokares/docs.
 *
 * @param array $a Attributes.
 */
function spokares_render_docs( array $a ): string {
	$view = (string) ( $a['view'] ?? 'library' );
	$lib  = home_url( '/members/documents/' );

	if ( 'search' === $view ) {
		$html = spokares_block_open( $view ) . '<form class="searchbar hub-search" id="search" role="search" action="' . esc_url( $lib ) . '" method="get">'
			. '<label class="hub-search__label" for="hub-q">' . esc_html__( 'Search documents and forms', 'spokares-core' ) . '</label>'
			. '<div class="searchbar__field">' . spokares_icon( 'search' ) . '<input id="hub-q" type="search" name="q" maxlength="100" placeholder="' . esc_attr__( 'ICS-213, net script, task book', 'spokares-core' ) . '" autocomplete="off"></div>'
			. '<button class="btn btn--ink" type="submit">' . esc_html__( 'Search', 'spokares-core' ) . '</button></form>';
		// A search box isn't a list: no front-end "Edit this list" link here.
		return $html . spokares_block_tail( 'documents', false ) . '</div>';
	}

	if ( 'tiles' === $view ) {
		$tiles = spokares_opt( 'spk_tiles' );
		$items = '';
		// One query for the four documents, their meta and terms, instead of
		// four of each.
		$ids = array_filter( array_map( 'absint', wp_list_pluck( array_slice( $tiles, 0, 4 ), 'doc' ) ) );
		if ( $ids && function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $ids, true, true );
		}
		for ( $i = 0; $i < 4; $i++ ) {
			$slot = $tiles[ $i ];
			$doc  = $slot['doc'] ? spokares_public_document( $slot['doc'] ) : null;
			if ( ! $doc ) {
				continue;
			}
			$data = spokares_document_data( $doc );
			if ( ! $data || 'soon' === $data['source'] ) {
				continue;
			}
			$label  = '' !== trim( $slot['label'] ) ? $slot['label'] : $data['title'];
			$items .= '<li><a class="tile" href="' . esc_url( home_url( '/docs/' . $doc->post_name . '/' ) ) . '">' . spokares_icon( $slot['icon'] ) . '<span class="tile__label">' . spokares_text( $label ) . '</span></a></li>';
		}
		if ( '' === $items ) {
			return spokares_block_placeholder( __( 'Most used: no buttons set.', 'spokares-core' ), 'tiles' );
		}
		return spokares_block_open( $view ) . '<ul class="quick">' . $items . '</ul>' . spokares_block_tail( 'tiles' ) . '</div>';
	}

	if ( 'library' !== $view ) {
		return spokares_block_placeholder( __( 'Pick a view for this list.', 'spokares-core' ) );
	}

	$sections = spokares_library_sections();
	$groups   = spokares_library_documents();
	$query    = spokares_library_query( $sections );
	$words    = '' !== $query['q'] ? preg_split( '/\s+/u', mb_strtolower( $query['q'], 'UTF-8' ), -1, PREG_SPLIT_NO_EMPTY ) : array();
	$total    = 0;
	$shown    = 0;

	$body = '';
	foreach ( $sections as $slug => $label ) {
		$docs = $groups[ $slug ] ?? array();
		if ( ! $docs ) {
			continue;
		}
		$rows    = '';
		$visible = 0;
		foreach ( $docs as $doc ) {
			++$total;
			$hay   = spokares_document_haystack( $doc );
			$match = true;
			foreach ( (array) $words as $w ) {
				if ( ! str_contains( $hay, $w ) ) {
					$match = false;
					break;
				}
			}
			if ( 'most-used' === $query['section'] ) {
				$match = $match && $doc['most_used'];
			} elseif ( '' !== $query['section'] ) {
				$match = $match && $slug === $query['section'];
			}
			if ( $match ) {
				++$visible;
				++$shown;
			}
			$rows .= '<tr id="' . esc_attr( $doc['slug'] ) . '" data-doc="' . esc_attr( $doc['slug'] ) . '" data-search="' . esc_attr( $hay ) . '"'
				. ( $doc['most_used'] ? ' data-most-used' : '' ) . ( $match ? '' : ' hidden' ) . '>'
				. '<td>' . spokares_document_title_cell( $doc ) . '</td>'
				. '<td data-label="' . esc_attr__( 'Format', 'spokares-core' ) . '">' . spokares_document_format_cell( $doc ) . '</td>'
				. '<td data-label="' . esc_attr__( 'Source', 'spokares-core' ) . '">' . spokares_document_source_cell( $doc ) . '</td></tr>';
		}
		$body .= '<section class="doc-group" id="' . esc_attr( $slug ) . '" data-section="' . esc_attr( $slug ) . '"' . ( $visible ? '' : ' hidden' ) . '>'
			. '<div class="doc-group__head"><h2>' . esc_html( $label ) . '</h2></div>'
			. '<table class="table doc-table"><caption class="vh">' . esc_html( $label ) . '</caption>'
			. '<thead class="vh"><tr><th scope="col">' . esc_html__( 'Document', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Format', 'spokares-core' ) . '</th><th scope="col">' . esc_html__( 'Source', 'spokares-core' ) . '</th></tr></thead>'
			. '<tbody>' . $rows . '</tbody></table></section>';
	}

	$filtered = '' !== $query['q'] || '' !== $query['section'];
	$count    = $filtered
		/* translators: 1: documents shown, 2: all documents. */
		? sprintf( _n( 'Showing %1$d of %2$d document.', 'Showing %1$d of %2$d documents.', $total, 'spokares-core' ), $shown, $total )
		/* translators: %d: number of documents. */
		: sprintf( _n( 'Showing all %d document.', 'Showing all %d documents.', $total, 'spokares-core' ), $total );

	$chips = '<li><a class="chip" href="' . esc_url( $lib ) . '"' . ( '' === $query['section'] ? ' aria-current="true"' : '' ) . '>' . esc_html__( 'All', 'spokares-core' ) . '</a></li>'
		. '<li><a class="chip" href="' . esc_url( add_query_arg( 'section', 'most-used', $lib ) ) . '" data-section="most-used"' . ( 'most-used' === $query['section'] ? ' aria-current="true"' : '' ) . '>' . esc_html__( 'Most used', 'spokares-core' ) . '</a></li>';
	foreach ( $sections as $slug => $label ) {
		if ( empty( $groups[ $slug ] ) ) {
			continue;
		}
		$chips .= '<li><a class="chip" href="' . esc_url( add_query_arg( 'section', $slug, $lib ) ) . '" data-section="' . esc_attr( $slug ) . '"' . ( $slug === $query['section'] ? ' aria-current="true"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
	}

	$html = spokares_block_open( $view ) . '<div class="lib" data-doc-library><div class="lib__rail">'
		. '<form class="searchbar lib__search" id="search" role="search" action="' . esc_url( $lib ) . '" method="get">'
		. '<label class="lib__label" for="doc-q">' . esc_html__( 'Search the library', 'spokares-core' ) . '</label>'
		. '<div class="searchbar__field">' . spokares_icon( 'search' ) . '<input id="doc-q" type="search" name="q" maxlength="100" value="' . esc_attr( $query['q'] ) . '" placeholder="' . esc_attr__( 'preamble, 213, task book', 'spokares-core' ) . '" autocomplete="off"></div>'
		. ( '' !== $query['section'] ? '<input type="hidden" name="section" value="' . esc_attr( $query['section'] ) . '">' : '' )
		. '<button class="btn btn--ink" type="submit">' . esc_html__( 'Search', 'spokares-core' ) . '</button></form>'
		. '<nav class="lib__cats" aria-label="' . esc_attr__( 'Document categories', 'spokares-core' ) . '"><ul class="chips">' . $chips . '</ul></nav>'
		. '</div><div class="lib__main">'
		. '<p class="doc-count" aria-live="polite">' . esc_html( $count ) . '</p>'
		. '<div class="card doc-empty"' . ( $filtered && 0 === $shown ? '' : ' hidden' ) . '><p>' . esc_html__( 'Nothing matches', 'spokares-core' ) . ' “<span data-doc-empty-q>' . esc_html( $query['q'] ) . '</span>”.</p>'
		. '<a class="btn btn--ghost btn--sm" href="' . esc_url( $lib ) . '" data-doc-clear>' . esc_html__( 'Clear search', 'spokares-core' ) . '</a></div>'
		. $body
		. '</div></div>';

	wp_enqueue_script_module( '@spokares/doc-library' );
	return $html . spokares_block_tail( 'documents' ) . '</div>';
}

/**
 * The Document cell: the title (linked by source), then the note, then the
 * how-to link, each on its own line.
 *
 * @param array $doc Document data.
 */
function spokares_document_title_cell( array $doc ): string {
	$verify = $doc['check'] ? ' needs-verify' : '';
	if ( $doc['sublinks'] ) {
		$name  = '' !== $doc['label'] ? $doc['label'] : '';
		$links = array();
		foreach ( $doc['sublinks'] as $s ) {
			$url = spokares_clean_url( (string) ( $s['url'] ?? '' ) );
			if ( '' === $url || '' === trim( (string) ( $s['label'] ?? '' ) ) ) {
				continue;
			}
			$site = '' !== $name ? $name : spokares_host_name( $url );
			$aria = trim( (string) $s['label'] ) . ( '' !== trim( (string) ( $s['title'] ?? '' ) ) ? ', ' . trim( (string) $s['title'] ) : '' );
			/* translators: 1: course code and title, 2: website name. */
			$aria    = sprintf( __( '%1$s (opens %2$s)', 'spokares-core' ), $aria, $site );
			$id      = sanitize_html_class( (string) ( $s['id'] ?? '' ) );
			$links[] = '<a' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' class="nowrap" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria ) . '">' . esc_html( (string) $s['label'] ) . '</a>';
		}
		$cell = '<span class="doc-title' . $verify . '">' . spokares_text( $doc['title'] ) . ':</span> <span class="doc-links">' . implode( ', ', $links ) . '</span>';
	} elseif ( 'upload' === $doc['source'] ) {
		$cell = '<a class="doc-title' . $verify . '" href="' . esc_url( home_url( '/docs/' . $doc['slug'] . '/' ) ) . '">' . spokares_text( $doc['title'] ) . '</a>';
	} elseif ( 'link' === $doc['source'] ) {
		$cell = spokares_link( $doc['url'], $doc['title'], 'doc-title' . $verify, $doc['label'] );
	} else {
		$cell = '<span class="doc-title' . $verify . '">' . spokares_text( $doc['title'] ) . '</span>';
	}
	if ( '' !== trim( $doc['note'] ) ) {
		$cell .= '<span class="doc-note">' . spokares_text( $doc['note'] ) . '</span>';
	}
	if ( '' !== $doc['howto_url'] && '' !== trim( $doc['howto_lbl'] ) ) {
		$cell .= '<span class="doc-note">' . spokares_link( $doc['howto_url'], $doc['howto_lbl'] ) . '</span>';
	}
	return $cell;
}

/**
 * The Format cell: format, then the version (each part after "; " kept whole).
 *
 * @param array $doc Document data.
 */
function spokares_document_format_cell( array $doc ): string {
	$out = '' !== trim( $doc['format'] ) ? '<span class="fmt">' . esc_html( $doc['format'] ) . '</span>' : '';
	if ( '' !== trim( $doc['version'] ) ) {
		$parts = array_map( static fn( $p ) => '<span class="nowrap">' . esc_html( trim( $p ) ) . '</span>', explode( ';', $doc['version'] ) );
		$out  .= ( '' !== $out ? ' ' : '' ) . '<span class="doc-ver">' . implode( '; ', $parts ) . '</span>';
	}
	return $out;
}

/**
 * The Source cell: the source label, or a "Soon" tag.
 *
 * @param array $doc Document data.
 */
function spokares_document_source_cell( array $doc ): string {
	if ( '' !== trim( $doc['label'] ) ) {
		return esc_html( $doc['label'] );
	}
	if ( 'soon' === $doc['source'] ) {
		return '<span class="tag tag--line">' . esc_html__( 'Soon', 'spokares-core' ) . '</span>';
	}
	return '';
}

/* --------------------------------------------------------------------- toc */

/**
 * The H2 headings with anchors in a post's content: [ [id, text], … ].
 *
 * @param string $content Post content.
 */
function spokares_toc_items( string $content ): array {
	$items = array();
	$walk  = static function ( array $blocks ) use ( &$walk, &$items ): void {
		foreach ( $blocks as $b ) {
			if ( 'core/heading' === ( $b['blockName'] ?? '' ) && 2 === (int) ( $b['attrs']['level'] ?? 2 ) ) {
				$id = (string) ( $b['attrs']['anchor'] ?? '' );
				if ( '' === $id && preg_match( '/\sid="([^"]+)"/', (string) $b['innerHTML'], $m ) ) {
					$id = $m[1];
				}
				if ( '' !== $id ) {
					$text    = trim( html_entity_decode( wp_strip_all_tags( (string) $b['innerHTML'] ), ENT_QUOTES, 'UTF-8' ) );
					$items[] = array( $id, $text );
				}
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $content ) );
	return $items;
}

/**
 * spokares/toc.
 *
 * @param array    $a     Attributes.
 * @param WP_Block $block Block (for the postId context).
 */
function spokares_render_toc( array $a, $block ): string {
	$post_id = ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) ? (int) $block->context['postId'] : (int) get_the_ID();
	$post    = $post_id ? get_post( $post_id ) : null;
	$items   = $post ? spokares_toc_items( (string) $post->post_content ) : array();
	if ( ! $items ) {
		return spokares_block_placeholder( __( 'The contents list fills in from this page’s headings.', 'spokares-core' ) );
	}
	$labels = array();
	foreach ( explode( ';', (string) ( $a['labels'] ?? '' ) ) as $pair ) {
		$kv = array_map( 'trim', explode( '=', $pair, 2 ) );
		if ( 2 === count( $kv ) && '' !== $kv[0] && '' !== $kv[1] ) {
			$labels[ $kv[0] ] = $kv[1];
		}
	}
	$title = '' !== trim( (string) ( $a['title'] ?? '' ) ) ? (string) $a['title'] : __( 'On this page', 'spokares-core' );
	$list  = '<ol>';
	foreach ( $items as [ $id, $text ] ) {
		$list .= '<li><a href="#' . esc_attr( $id ) . '">' . esc_html( $labels[ $id ] ?? $text ) . '</a></li>';
	}
	$list .= '</ol>';

	wp_enqueue_script_module( '@spokares/toc' );
	return spokares_block_open()
		. '<nav class="toc toc--side" aria-labelledby="toc-title"><p class="toc__title" id="toc-title">' . esc_html( $title ) . '</p>' . $list . '<span class="toc__rail" aria-hidden="true"></span></nav>'
		. '<details class="toc-mobile"><summary>' . esc_html( $title ) . '</summary><nav class="toc" aria-label="' . esc_attr( $title ) . '">' . $list . '</nav></details>'
		. '<button class="btn btn--ink contents-fab" type="button" hidden>' . spokares_icon( 'menu' ) . esc_html( $title ) . '</button>'
		. '</div>';
}

/* ----------------------------------------------------------- last-reviewed */

/**
 * spokares/last-reviewed.
 *
 * @param array    $a     Attributes (none).
 * @param WP_Block $block Block.
 */
function spokares_render_last_reviewed( array $a, $block ): string {
	unset( $a );
	$post_id  = ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) ? (int) $block->context['postId'] : (int) get_the_ID();
	$reviewed = $post_id ? (string) get_post_meta( $post_id, '_spk_reviewed', true ) : '';
	if ( ! spokares_is_ymd( $reviewed ) ) {
		return spokares_block_placeholder( __( 'Last reviewed appears here once a date is set in the Page review box.', 'spokares-core' ) );
	}
	$owner = trim( (string) get_post_meta( $post_id, '_spk_owner', true ) );
	$html  = spokares_block_open() . '<p class="chapter-meta">' . esc_html__( 'Last reviewed', 'spokares-core' ) . ' <time datetime="' . esc_attr( $reviewed ) . '">' . esc_html( spokares_fmt_date( $reviewed, 'mdy' ) ) . '</time>.'
		/* translators: %s: role, e.g. "Emergency Coordinator". */
		. ( '' !== $owner ? ' ' . esc_html( sprintf( __( 'Page owner: %s.', 'spokares-core' ), $owner ) ) : '' ) . '</p>';
	return $html . spokares_block_tail( 'page-review', false ) . '</div>';
}

/* -------------------------------------------------------------------- asof */

/**
 * spokares/asof.
 */
function spokares_render_asof(): string {
	/* translators: %s: date, e.g. "Sat, Sep 26, 2026". */
	return spokares_block_open() . '<p class="page-head__meta">' . esc_html( sprintf( __( 'Schedule as of %s.', 'spokares-core' ), spokares_fmt_date( spokares_today(), 'long' ) ) ) . '</p></div>';
}
