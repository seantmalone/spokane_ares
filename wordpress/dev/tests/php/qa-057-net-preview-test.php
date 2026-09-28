<?php
/**
 * Regression tests for QA-057 (PLAN §3.4): the Net details screen has a
 * "Preview: what the site will say", but spokares_net_preview_lines() built
 * its own sentences instead of printing what the site prints:
 *
 * - With a GMRS week ticked and the GMRS time cleared, the preview said
 *   "ACS GMRS net: 3rd Tuesdays, , for county volunteers …", while How it
 *   works leaves the time out ("3rd Tuesdays, for county volunteers …").
 * - The line labelled "How it works: the alternate repeater" printed the
 *   Copy-button text ("-600 kHz offset"), not the row How it works shows
 *   ("Alternate | 146.880 MHz, −600 kHz, 123 Hz tone").
 * - With no week ticked, the three "Other nets" bullets were printed empty
 *   (and visible) until the first input event ran the script.
 * - After a refused call sign ("Frank"), the preview must keep saying what
 *   the site says, never the refused text (the live-typing half of this is
 *   in dev/tests/e2e/qa-057-net-preview.test.mjs). Only an administrator can
 *   type a call sign since QA-039, so that test signs in as one.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Plain text of some HTML as a reader sees it: tags dropped, entities
 * decoded, no-break spaces as spaces, runs of white space as one space.
 *
 * @param string $html HTML.
 */
function qa057_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( "\u{00A0}", ' ', $text );
	return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Store net facts over the seeded ones (the framework restores the options).
 *
 * @param array $nets  spk_nets keys to change.
 * @param array $radio spk_radio['alternate'] keys to change.
 */
function qa057_set( array $nets, array $radio = array() ): void {
	update_option( 'spk_nets', array_merge( \spokares_opt( 'spk_nets' ), $nets ) );
	if ( $radio ) {
		$all              = \spokares_opt( 'spk_radio' );
		$all['alternate'] = array_merge( $all['alternate'], $radio );
		update_option( 'spk_radio', $all );
	}
}

/**
 * The public HTML of one spokares/net view, as a visitor gets it.
 *
 * @param string $view Block view.
 */
function qa057_site( string $view ): string {
	return do_blocks( '<!-- wp:spokares/net {"view":"' . $view . '"} /-->' );
}

/**
 * The preview lines for the stored net facts.
 */
function qa057_lines(): array {
	return \spokares_net_preview_lines( \spokares_opt( 'spk_nets' ), \spokares_opt( 'spk_radio' ) );
}

/**
 * The public "ACS GMRS net: …" bullet of How it works › Other nets.
 */
function qa057_site_gmrs(): string {
	preg_match_all( '#<li\b[^>]*>(.*?)</li>#s', qa057_site( 'other-nets' ), $m );
	foreach ( $m[1] as $li ) {
		$text = qa057_plain( $li );
		if ( str_starts_with( $text, 'ACS GMRS net:' ) ) {
			return $text;
		}
	}
	fail( 'How it works › Other nets printed no "ACS GMRS net:" bullet' );
	return '';
}

/**
 * The Net details screen as the current user sees it.
 */
function qa057_screen(): string {
	ob_start();
	\spokares_net_details_page();
	return (string) ob_get_clean();
}

/**
 * The preview box of the Net details screen.
 *
 * @param string $html Screen HTML.
 */
function qa057_preview_box( string $html ): string {
	if ( ! preg_match( '#<div[^>]*id="spk-net-preview"[^>]*>.*?</dl>#s', $html, $m ) ) {
		fail( 'the Net details screen has no preview box (#spk-net-preview)' );
	}
	return $m[0];
}

/**
 * The Net details form fields as the screen posts them, from the stored
 * options, with the repeater call sign replaced.
 *
 * @param string $call Call sign to type.
 */
function qa057_fields( string $call ): array {
	$radio = \spokares_opt( 'spk_radio' );
	$nets  = \spokares_opt( 'spk_nets' );
	return array(
		'radio' => array(
			'primary'   => array(
				'call'   => $call,
				'freq'   => $radio['primary']['freq'],
				'offset' => $radio['primary']['offset'],
				'tone'   => $radio['primary']['tone'],
			),
			'alternate' => array(
				'freq'   => $radio['alternate']['freq'],
				'offset' => $radio['alternate']['offset'],
				'tone'   => $radio['alternate']['tone'],
				'show'   => $radio['alternate']['show'] ? '1' : '',
			),
		),
		'nets'  => array(
			'net_time'       => $nets['net_time'],
			'gmrs_time'      => $nets['gmrs_time'],
			'winlink_nth'    => $nets['winlink_nth'],
			'simplex_nth'    => $nets['simplex_nth'],
			'gmrs_nth'       => $nets['gmrs_nth'],
			'winlink_howto'  => $nets['winlink_howto'],
			'open_slot_line' => $nets['open_slot_line'],
		),
	);
}

test(
	'with a GMRS time, the GMRS preview line is the How it works line',
	function () {
		qa057_set(
			array(
				'gmrs_nth'  => array( 3 ),
				'gmrs_time' => '19:30',
			)
		);
		$site = qa057_site_gmrs();
		assert_same( 'ACS GMRS net: 3rd Tuesdays, 7:30 PM, for county volunteers with GMRS licenses.', $site, 'How it works GMRS bullet (sanity check of the comparison)' );
		assert_same( $site, qa057_lines()['gmrs'], 'preview GMRS line differs from How it works' );
	}
);

test(
	'with no GMRS time, the GMRS preview line has no ", ," and is the How it works line',
	function () {
		qa057_set(
			array(
				'gmrs_nth'  => array( 3 ),
				'gmrs_time' => '',
			)
		);
		$site    = qa057_site_gmrs();
		$preview = qa057_lines()['gmrs'];
		assert_not_contains( ', ,', $site, 'How it works itself prints ", ,"' );
		assert_not_contains( ', ,', $preview, 'preview GMRS line with an empty time' );
		assert_same( $site, $preview, 'preview GMRS line differs from How it works' );
	}
);

test(
	'with no GMRS time, the Net details screen preview has no ", ,"',
	function () {
		qa057_set(
			array(
				'gmrs_nth'  => array( 3 ),
				'gmrs_time' => '',
			)
		);
		as_role( 'ares-net' );
		assert_not_contains( ', ,', qa057_plain( qa057_preview_box( qa057_screen() ) ), 'Net details preview box' );
	}
);

test(
	'the alternate repeater preview prints what the How it works row prints',
	function () {
		qa057_set(
			array(),
			array(
				'freq'   => '146.880',
				'offset' => '-600 kHz',
				'tone'   => '123 Hz',
				'show'   => true,
			)
		);
		if ( ! preg_match( '#<dt>\s*Alternate\s*</dt>\s*<dd\b[^>]*>(.*?)</dd>#s', qa057_site( 'settings' ), $m ) ) {
			fail( 'How it works › settings box printed no Alternate row' );
		}
		$site = qa057_plain( $m[1] );
		assert_same( '146.880 MHz, −600 kHz, 123 Hz tone', $site, 'How it works Alternate row (sanity check of the comparison)' );
		$preview = qa057_lines()['alt'];
		assert_contains( $site, $preview, 'preview "How it works: the alternate repeater" does not print the How it works row' );
		assert_not_contains( 'offset', $preview, 'preview alternate line uses the Copy-button wording' );
	}
);

test(
	'with no week ticked, the Other nets preview prints no empty bullets',
	function () {
		qa057_set(
			array(
				'winlink_nth' => array(),
				'simplex_nth' => array(),
				'gmrs_nth'    => array(),
			)
		);
		as_role( 'ares-net' );
		$box = qa057_preview_box( qa057_screen() );
		foreach ( array( 'winlink', 'simplex', 'gmrs' ) as $key ) {
			if ( ! preg_match( '#<li\b([^>]*\bdata-preview="' . $key . '"[^>]*)>(.*?)</li>#s', $box, $m ) ) {
				continue; // Not printed at all: fine.
			}
			if ( '' !== qa057_plain( $m[2] ) ) {
				fail( 'the ' . $key . ' bullet says "' . qa057_plain( $m[2] ) . '" with no week ticked' );
			}
			assert_matches( '#(^|\s)hidden(\s|=|$)#', $m[1], 'the empty ' . $key . ' bullet is printed visible (no hidden attribute)' );
		}
	}
);

test(
	'after a refused call sign, the preview does not show the refused text',
	function () {
		// Administrators only: since QA-039 the call sign is read-only (and
		// refused on save) for a user with just the Net details grant.
		as_role( 'admin' );
		$stored = \spokares_opt( 'spk_radio' )['primary']['call'];
		$res    = post_form( 'spokares_save_net_details', qa057_fields( 'Frank' ) );
		assert_same( null, $res['die'], 'save refused outright' );
		assert_same( $stored, \spokares_opt( 'spk_radio' )['primary']['call'], '"Frank" was saved as the repeater call sign' );
		$html = qa057_screen();
		assert_matches( '#id="spk-p-call"[^>]*value="Frank"#', $html, 'the typed "Frank" is not kept in the call-sign box' );
		assert_not_contains( 'frank', strtolower( qa057_plain( qa057_preview_box( $html ) ) ), 'preview shows the refused call sign' );
	}
);
