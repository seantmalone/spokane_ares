<?php
/**
 * Guard rails on data (§4.4): call signs, the never-publish list, phone and
 * e-mail shapes, web addresses. One set of patterns; the editor guard script
 * receives the same patterns as JSON.
 *
 * @package spokares-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The call-sign pattern (one word, uppercase).
 */
const SPOKARES_CALL_RE = '/^[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,4}(\/[A-Z0-9]+)?$/';

/**
 * Pull one call sign out of what an editor typed.
 * "Frank AG7QP" → AG7QP (dropped: true); "AE7RJ & NZ2S" → AE7RJ (dropped: true);
 * "Frank" → '' (no match). A word also needs a letter, so a year or a phone
 * fragment is never taken for a call sign.
 *
 * @param string $typed Typed text.
 * @return array{call:string,dropped:bool}
 */
function spokares_call_sign( string $typed ): array {
	$words = preg_split( '/[^A-Z0-9\/]+/', strtoupper( trim( $typed ) ), -1, PREG_SPLIT_NO_EMPTY );
	$words = is_array( $words ) ? $words : array();
	foreach ( $words as $word ) {
		if ( preg_match( SPOKARES_CALL_RE, $word ) && preg_match( '/[A-Z]/', $word ) ) {
			return array(
				'call'    => $word,
				'dropped' => count( $words ) > 1,
			);
		}
	}
	return array(
		'call'    => '',
		'dropped' => false,
	);
}

/**
 * The patterns, with what each one means in a sentence ("mentions {what}").
 * Shared by the PHP checks and the editor guard script.
 */
function spokares_text_patterns(): array {
	return array(
		array(
			'level' => 'block',
			'what'  => __( 'a hospital net', 'spokares-core' ),
			're'    => 'hospital\s+net',
		),
		array(
			'level' => 'block',
			'what'  => __( 'a channel number', 'spokares-core' ),
			're'    => '\bchannel\s*\d',
		),
		array(
			'level' => 'block',
			'what'  => __( 'SHARES channels or frequencies', 'spokares-core' ),
			're'    => '\bSHARES\s+(channel|frequenc)',
		),
		array(
			'level' => 'block',
			'what'  => __( '800 MHz', 'spokares-core' ),
			're'    => '\b800\s?MHz',
		),
		array(
			'level' => 'block',
			'what'  => __( 'a talkgroup', 'spokares-core' ),
			're'    => 'talk\s?group',
		),
		array(
			'level' => 'confirm',
			'kind'  => 'phone',
			'what'  => __( 'a phone number', 'spokares-core' ),
			're'    => '(?<!\d)(?:\+?1[\s.-]?)?\(?[2-9]\d{2}\)?[\s.-]?[2-9]\d{2}[\s.-]?\d{4}(?!\d)',
		),
		array(
			'level' => 'confirm',
			'kind'  => 'email',
			'what'  => __( 'an e-mail address', 'spokares-core' ),
			// Any address; addresses @spokares.org are removed before this runs.
			're'    => '[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}',
		),
	);
}

/**
 * Check a piece of plain text. Each hit carries 'match': the first text the
 * pattern found, trimmed, at most 60 characters (the Dashboard quotes it).
 *
 * @param string $text Text.
 * @return array<int,array{level:string,what:string,match:string,kind?:string}>
 */
function spokares_check_text( string $text ): array {
	// A space after every tag first, so adjacent table cells or list items don't
	// run together ("partnersec@spokares.orgThis website" hid the role address).
	$text = wp_strip_all_tags( (string) preg_replace( '/(<[^>]*>)/', '$1 ', $text ) );
	if ( '' === trim( $text ) ) {
		return array();
	}
	// Role addresses at spokares.org are fine.
	$scan  = (string) preg_replace( '/[A-Z0-9._%+-]+@spokares\.org\b/i', ' ', $text );
	$found = array();
	foreach ( spokares_text_patterns() as $p ) {
		if ( preg_match( '/' . $p['re'] . '/iu', $scan, $m ) ) {
			$hit = array(
				'level' => $p['level'],
				'what'  => $p['what'],
				'match' => mb_substr( trim( (string) preg_replace( '/\s+/u', ' ', $m[0] ) ), 0, 60, 'UTF-8' ),
			);
			if ( isset( $p['kind'] ) ) {
				$hit['kind'] = $p['kind'];
			}
			$found[] = $hit;
		}
	}
	return $found;
}

/**
 * Check one field, honouring a previous "Publish it" confirmation.
 *
 * @param string $text      Field text.
 * @param string $field     Field key.
 * @param array  $confirmed Map field => sha1 of confirmed text.
 * @param bool   $tick      The confirm tick was ticked in this request.
 * @return array{block:string[],confirm:array<int,array{what:string,kind:string}>,confirmed:bool}
 *   'block' = never-publish hits; 'confirm' = unconfirmed phone/e-mail hits;
 *   'confirmed' = the text is now confirmed (store its hash).
 */
function spokares_check_field( string $text, string $field, array $confirmed = array(), bool $tick = false ): array {
	$out  = array(
		'block'     => array(),
		'confirm'   => array(),
		'confirmed' => false,
	);
	$hits = spokares_check_text( $text );
	$hash = sha1( $text );
	$ok   = $tick || ( isset( $confirmed[ $field ] ) && hash_equals( (string) $confirmed[ $field ], $hash ) );
	foreach ( $hits as $hit ) {
		if ( 'block' === $hit['level'] ) {
			$out['block'][] = $hit['what'];
		} elseif ( ! $ok ) {
			$out['confirm'][] = array(
				'what' => $hit['what'],
				'kind' => $hit['kind'] ?? 'phone',
			);
		} else {
			$out['confirmed'] = true;
		}
	}
	return $out;
}

/**
 * The one sentence under a field with a problem, for a spokares_check_field()
 * result: what is wrong and what to do. '' when there is no problem.
 *
 * @param array $check spokares_check_field() result.
 */
function spokares_problem_sentence( array $check ): string {
	$block = array_values( array_filter( (array) ( $check['block'] ?? array() ), 'is_string' ) );
	if ( $block ) {
		/* translators: %s: what the text mentions, e.g. "a hospital net". */
		return sprintf( __( 'It mentions %s, which we never publish. Take it out.', 'spokares-core' ), spokares_and_list( $block ) );
	}
	$kinds = array();
	foreach ( (array) ( $check['confirm'] ?? array() ) as $hit ) {
		$kinds[] = is_array( $hit ) ? (string) ( $hit['kind'] ?? 'phone' ) : 'phone';
	}
	$phone = in_array( 'phone', $kinds, true );
	$email = in_array( 'email', $kinds, true );
	if ( $phone && $email ) {
		return __( 'It has a phone number and an e-mail address. Take them out, or tick the box if they’re public agency contacts.', 'spokares-core' );
	}
	if ( $email ) {
		return __( 'It has an e-mail address. Take it out, or tick the box if it’s a public agency address.', 'spokares-core' );
	}
	if ( $phone ) {
		return __( 'It has a phone number. Take it out, or tick the box if it’s a public agency number.', 'spokares-core' );
	}
	return '';
}

/**
 * The confirm tick's words for a field's hits.
 *
 * @param array $confirm Hits from spokares_check_field()['confirm'].
 */
function spokares_confirm_label( array $confirm ): string {
	$kinds = array_unique( wp_list_pluck( $confirm, 'kind' ) );
	if ( in_array( 'phone', $kinds, true ) && in_array( 'email', $kinds, true ) ) {
		return __( 'These are a public agency number and address. Publish them.', 'spokares-core' );
	}
	return in_array( 'email', $kinds, true )
		? __( 'This is a public agency address. Publish it.', 'spokares-core' )
		: __( 'This is a public agency number. Publish it.', 'spokares-core' );
}

/**
 * Sanitise a web address: https only (plus mailto: for role addresses).
 * Returns '' when the typed text isn't one. An address typed without its
 * https:// ("www.arrl.org/kids-day") gets it when the part before the first
 * "/" has a dot, and no spaces or "@"; other text without a scheme is not a
 * web address.
 *
 * @param string $typed Typed address.
 * @param bool   $allow_mailto Accept mailto: links.
 */
function spokares_clean_url( string $typed, bool $allow_mailto = false ): string {
	$typed = trim( $typed );
	if ( '' === $typed ) {
		return '';
	}
	// No scheme (a colon followed by a port number is not one): a web
	// address only when its first part looks like a site name.
	if ( ! preg_match( '#^[a-z][a-z0-9+.\-]*:(?!\d)#i', $typed ) ) {
		$first = (string) preg_split( '#[/?\#]#', $typed, 2 )[0];
		if ( ! str_contains( $first, '.' ) || preg_match( '/[\s@]/u', $first ) ) {
			return '';
		}
		$typed = 'https://' . $typed;
	}
	$protocols = $allow_mailto ? array( 'https', 'mailto' ) : array( 'https' );
	$clean     = esc_url_raw( $typed, $protocols );
	if ( '' === $clean ) {
		return '';
	}
	if ( str_starts_with( $clean, 'mailto:' ) ) {
		return $allow_mailto && str_ends_with( strtolower( $clean ), '@spokares.org' ) ? $clean : '';
	}
	return str_starts_with( $clean, 'https://' ) && '' !== spokares_url_host( $clean ) ? $clean : '';
}

/**
 * A readable list: "a, b and c".
 *
 * @param string[] $items Items.
 */
function spokares_and_list( array $items ): string {
	$items = array_values( array_unique( array_filter( $items ) ) );
	if ( count( $items ) <= 1 ) {
		return (string) ( $items[0] ?? '' );
	}
	$last = array_pop( $items );
	return implode( ', ', $items ) . ' ' . __( 'and', 'spokares-core' ) . ' ' . $last;
}
