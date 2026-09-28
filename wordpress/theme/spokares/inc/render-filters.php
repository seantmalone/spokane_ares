<?php
/**
 * Render filters that put back what Option B's static HTML had and core
 * blocks can't store. PLAN.md §6.7 filters 1-4 and 9.
 *
 *  1 Stacking tables: data-label on each body cell, from the header row.
 *  2 Step and level groups: role="list" / role="listitem".
 *  3 External links in page text: class "ext" + a visually hidden "(opens …)".
 *  4 Navigation links: aria-current="page" by path ("is-section" also matches below it).
 *  9 No-break space between a number and AM, PM, MHz, kHz or Hz.
 * 10 Search results: the document-library link carries the query.
 *
 * All of them run on the server, so the markup is right without JavaScript.
 *
 * @package spokares
 */

defined( 'ABSPATH' ) || exit;

/**
 * Space-separated class tokens from a block's className attribute.
 *
 * @param array $block Parsed block.
 * @return string[]
 */
function spokares_theme_block_classes( array $block ): array {
	$class = $block['attrs']['className'] ?? '';
	if ( ! is_string( $class ) || '' === trim( $class ) ) {
		return array();
	}
	return preg_split( '/\s+/', trim( $class ) );
}

/*
 * ------------------------------------------------------------------ 1. Tables
 */

/**
 * Filter 1: a core/table with the class "table--stack" gets data-label on
 * every body <td>, taken from the header cell in the same column, so the
 * phone layout can print "What you do", "Open to"… as labels. Row header
 * cells (<th scope="row">) stay unlabelled: they are the card's title.
 *
 * @param string $html  Rendered block.
 * @param array  $block Parsed block.
 * @return string
 */
function spokares_theme_table_labels( string $html, array $block ): string {
	if ( ! in_array( 'table--stack', spokares_theme_block_classes( $block ), true ) ) {
		return $html;
	}
	if ( ! preg_match( '#<thead\b[^>]*>(.*?)</thead>#is', $html, $head ) ) {
		return $html;
	}
	preg_match_all( '#<t[hd]\b[^>]*>(.*?)</t[hd]>#is', $head[1], $cells );
	$labels = array();
	foreach ( $cells[1] as $cell ) {
		$labels[] = trim( html_entity_decode( wp_strip_all_tags( $cell ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
	if ( ! $labels ) {
		return $html;
	}

	$processor = new WP_HTML_Tag_Processor( $html );
	$in_body   = false;
	$column    = 0;
	while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		$tag = $processor->get_tag();
		if ( 'TBODY' === $tag ) {
			$in_body = ! $processor->is_tag_closer();
			continue;
		}
		if ( ! $in_body || $processor->is_tag_closer() ) {
			continue;
		}
		if ( 'TR' === $tag ) {
			$column = 0;
			continue;
		}
		if ( 'TH' !== $tag && 'TD' !== $tag ) {
			continue;
		}
		if ( 'TD' === $tag && isset( $labels[ $column ] ) && '' !== $labels[ $column ] && null === $processor->get_attribute( 'data-label' ) ) {
			$processor->set_attribute( 'data-label', $labels[ $column ] );
		}
		$span    = $processor->get_attribute( 'colspan' );
		$column += is_string( $span ) ? max( 1, (int) $span ) : 1;
	}
	return $processor->get_updated_html();
}
add_filter( 'render_block_core/table', 'spokares_theme_table_labels', 10, 2 );

/*
 * ------------------------------------------------------------------ 2. List roles on groups
 */

/**
 * Filter 2: the Home join steps and the readiness levels are Groups (core
 * lists can't hold headings and links per item), so give them list
 * semantics back: "steps" / "levels" → role="list", "steps__item" / "level"
 * → role="listitem".
 *
 * @param string $html  Rendered block.
 * @param array  $block Parsed block.
 * @return string
 */
function spokares_theme_group_roles( string $html, array $block ): string {
	$classes = spokares_theme_block_classes( $block );
	if ( ! $classes ) {
		return $html;
	}
	$role = '';
	if ( array_intersect( array( 'steps', 'levels' ), $classes ) ) {
		$role = 'list';
	} elseif ( array_intersect( array( 'steps__item', 'level' ), $classes ) ) {
		$role = 'listitem';
	}
	if ( '' === $role ) {
		return $html;
	}
	$processor = new WP_HTML_Tag_Processor( $html );
	if ( $processor->next_tag() && null === $processor->get_attribute( 'role' ) ) {
		$processor->set_attribute( 'role', $role );
	}
	return $processor->get_updated_html();
}
add_filter( 'render_block_core/group', 'spokares_theme_group_roles', 10, 2 );

/*
 * ------------------------------------------------------------------ 3. External links
 */

/**
 * The name read out in "(opens …)" for an external host. B's own wording for
 * the hosts that appear in page text; otherwise the host without "www.".
 *
 * @param string $host Lower-case host name.
 * @return string
 */
function spokares_theme_link_host_name( string $host ): string {
	$host  = (string) preg_replace( '/^www\./', '', strtolower( $host ) );
	$names = array(
		'spokaneares-acs.groups.io' => 'groups.io',
		'app.leg.wa.gov'            => 'leg.wa.gov',
	);
	$name  = $names[ $host ] ?? $host;

	/**
	 * Filters the site name used in the "(opens …)" note of an external link.
	 *
	 * @param string $name Name to read out.
	 * @param string $host Link host, without "www.".
	 */
	return (string) apply_filters( 'spokares_theme_link_host_name', $name, $host );
}

/**
 * Filter 3: in page text, an <a> to another site gets B's external-link
 * treatment: class "ext" (the small arrow icon, drawn by CSS) and a visually
 * hidden "(opens groups.io)" note. Links that already carry "ext" (the
 * plugin's own output, or hand-written markup) are left alone; mailto:,
 * relative and same-site links are never external.
 *
 * @param string $html Rendered block.
 * @return string
 */
function spokares_theme_mark_external_links( string $html ): string {
	if ( false === stripos( $html, '<a' ) ) {
		return $html;
	}
	$site_host = (string) preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
	$processor = new WP_HTML_Tag_Processor( $html );
	$changed   = false;
	while ( $processor->next_tag( 'A' ) ) {
		$href = $processor->get_attribute( 'href' );
		if ( ! is_string( $href ) || $processor->has_class( 'ext' ) ) {
			continue;
		}
		$href = trim( $href );
		if ( ! preg_match( '#^(?:https?:)?//#i', $href ) ) {
			continue;
		}
		$host = strtolower( (string) wp_parse_url( ( str_starts_with( $href, '//' ) ? 'https:' : '' ) . $href, PHP_URL_HOST ) );
		if ( '' === $host || preg_replace( '/^www\./', '', $host ) === $site_host ) {
			continue;
		}
		$processor->add_class( 'ext' );
		$processor->set_attribute( 'data-spokares-ext', spokares_theme_link_host_name( $host ) );
		$changed = true;
	}
	if ( ! $changed ) {
		return $html;
	}
	$html = $processor->get_updated_html();

	// Move each marker into a visually hidden note just before </a>.
	return (string) preg_replace_callback(
		'#(<a\b[^>]*?)\sdata-spokares-ext="([^"]*)"([^>]*>)(.*?)(</a>)#is',
		static function ( array $m ): string {
			/* translators: %s: the other site's name, for example "groups.io". */
			$note = sprintf( esc_html__( '(opens %s)', 'spokares' ), $m[2] ); // $m[2] was escaped by set_attribute().
			return $m[1] . $m[3] . $m[4] . '<span class="vh"> ' . $note . '</span>' . $m[5];
		},
		$html
	);
}
foreach ( array( 'core/paragraph', 'core/list-item', 'core/heading', 'core/table', 'core/button', 'core/navigation-link', 'core/quote' ) as $spokares_block_name ) {
	add_filter( 'render_block_' . $spokares_block_name, 'spokares_theme_mark_external_links', 20 );
}
unset( $spokares_block_name );

/*
 * ------------------------------------------------------------------ 4. Current page in navigation
 */

/**
 * The request path relative to the site root, without slashes
 * ("", "about", "members/documents").
 *
 * @return string
 */
function spokares_theme_request_path(): string {
	global $wp;
	$path = ( $wp instanceof WP && is_string( $wp->request ) ) ? $wp->request : '';
	return trim( $path, '/' );
}

/**
 * Filter 4: the header, members sub-nav and footer links are typed into the
 * theme's parts (no page IDs), so core never marks them current. Mark
 * aria-current="page" when the link's path is the request path; a link with
 * the class "is-section" (For Members) is also current on every page below
 * it. Links with a "#" fragment and links to other sites are never current.
 * CSS draws B's amber bar from the attribute.
 *
 * @param string $html  Rendered block.
 * @param array  $block Parsed block.
 * @return string
 */
function spokares_theme_nav_current( string $html, array $block ): string {
	$url = $block['attrs']['url'] ?? '';
	if ( ! is_string( $url ) || '' === $url || str_contains( $url, '#' ) || is_search() || is_404() ) {
		return $html;
	}
	$link = wp_parse_url( $url );
	if ( ! is_array( $link ) ) {
		return $html;
	}
	if ( ! empty( $link['host'] ) ) {
		$site_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( 0 !== strcasecmp( (string) $link['host'], $site_host ) ) {
			return $html;
		}
	}
	$home_path = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$link_path = trim( (string) ( $link['path'] ?? '' ), '/' );
	if ( '' !== $home_path && ( $link_path === $home_path || str_starts_with( $link_path, $home_path . '/' ) ) ) {
		$link_path = trim( substr( $link_path, strlen( $home_path ) ), '/' );
	}
	$request    = spokares_theme_request_path();
	$is_section = in_array( 'is-section', spokares_theme_block_classes( $block ), true );
	$current    = ( $link_path === $request )
		|| ( $is_section && '' !== $link_path && str_starts_with( $request . '/', $link_path . '/' ) );
	if ( ! $current ) {
		return $html;
	}
	$processor = new WP_HTML_Tag_Processor( $html );
	if ( $processor->next_tag( 'A' ) ) {
		$processor->set_attribute( 'aria-current', 'page' );
	}
	return $processor->get_updated_html();
}
add_filter( 'render_block_core/navigation-link', 'spokares_theme_nav_current', 10, 2 );

/*
 * ------------------------------------------------------------------ 9. No-break spaces
 */

/**
 * Filter 9: B wrapped "8:00 PM" and "147.300 MHz" in .nowrap spans; editors
 * can't type those, so the server joins a number to AM, PM, MHz, kHz or Hz
 * with a no-break space. Text only: tags and attribute values are untouched.
 *
 * @param string $html Rendered block.
 * @return string
 */
function spokares_theme_nbsp_units( string $html, array $block = array() ): string {
	if ( ! preg_match( '/\d (?:AM|PM|MHz|kHz|Hz)\b/', $html ) ) {
		return $html;
	}
	// The readiness levels' narrow "what you do" cells: B broke these lines
	// freely, and a joined "162.400 MHz" pushes "Stand by" to a fourth line.
	if ( in_array( 'level__do', spokares_theme_block_classes( $block ), true ) ) {
		return $html;
	}
	$parts = preg_split( '/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $parts ) {
		return $html;
	}
	foreach ( $parts as $i => $part ) {
		if ( '' === $part || '<' === $part[0] ) {
			continue;
		}
		$parts[ $i ] = (string) preg_replace( '/(\d) (AM|PM|MHz|kHz|Hz)\b/', "\$1\u{00A0}\$2", $part );
	}
	return implode( '', $parts );
}
foreach ( array( 'core/paragraph', 'core/heading', 'core/list-item', 'core/table', 'core/button', 'core/quote' ) as $spokares_block_name ) {
	add_filter( 'render_block_' . $spokares_block_name, 'spokares_theme_nbsp_units', 30, 2 );
}
unset( $spokares_block_name );

/*
 * ------------------------------------------------------------------ 10. Search results
 */

/**
 * Filter 10: on the search results page (templates/search.html) the paragraph
 * with the class "search-library" sends its first link to the document
 * library with the same query (?q=, which the library filters by on the
 * server too), since site search only covers page text. Anywhere else, or
 * with an empty query, the link is left as typed.
 *
 * @param string $html  Rendered block.
 * @param array  $block Parsed block.
 * @return string
 */
function spokares_theme_search_library_link( string $html, array $block ): string {
	if ( ! is_search() || ! in_array( 'search-library', spokares_theme_block_classes( $block ), true ) ) {
		return $html;
	}
	$query = trim( (string) get_search_query( false ) );
	if ( '' === $query ) {
		return $html;
	}
	$processor = new WP_HTML_Tag_Processor( $html );
	if ( $processor->next_tag( 'A' ) ) {
		$url = add_query_arg( 'q', rawurlencode( mb_substr( $query, 0, 100 ) ), home_url( '/members/documents/' ) ) . '#search';
		$processor->set_attribute( 'href', esc_url_raw( $url ) );
	}
	return $processor->get_updated_html();
}
add_filter( 'render_block_core/paragraph', 'spokares_theme_search_library_link', 10, 2 );
