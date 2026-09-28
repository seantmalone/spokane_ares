<?php
/**
 * Tests for QA-095 and QA-096 (PLAN §6.4 `net` views, §4.2 front-end edit
 * links).
 *
 * QA-095: with no Winlink, simplex or GMRS week ticked on Net details, How it
 * works › Other nets printed nothing outside the editor, so the page-text
 * heading "Other nets" stood over an empty body. It now says that no other
 * nets are scheduled (and grant holders still get the edit link).
 *
 * QA-096: the net bar on /members/ ended with "Edit this list in Net
 * details", right above the rota table, as if it edited the rota. The radio
 * settings views (bar, settings box, From home) are not lists: their link
 * reads "Edit these settings in Net details". Lists keep "Edit this list in …".
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Plain text of some HTML.
 *
 * @param string $html HTML.
 */
function qafront095_plain( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\u{00A0}", ' ', $text ) ) );
}

/**
 * A spokares/net view as the site draws it for the current user.
 *
 * @param string $view View.
 */
function qafront095_net( string $view ): string {
	return do_blocks( '<!-- wp:spokares/net {"view":"' . $view . '"} /-->' );
}

/**
 * The texts of the front-end edit links in some HTML.
 *
 * @param string $html HTML.
 * @return string[]
 */
function qafront095_edit_links( string $html ): array {
	preg_match_all( '#<p class="spk-edit-link"><a href="[^"]*">(.*?)</a></p>#s', $html, $m );
	return array_map( __NAMESPACE__ . '\qafront095_plain', $m[1] );
}

/**
 * Untick every net week (the framework restores the option).
 */
function qafront095_no_weeks(): void {
	update_option(
		'spk_nets',
		array_merge(
			\spokares_opt( 'spk_nets' ),
			array(
				'winlink_nth' => array(),
				'simplex_nth' => array(),
				'gmrs_nth'    => array(),
			)
		)
	);
}

test(
	'Other nets with no week ticked: a visitor reads a sentence, not an empty section',
	function () {
		qafront095_no_weeks();
		as_anonymous();
		$html = qafront095_net( 'other-nets' );
		assert_contains( 'is-view-other-nets', $html, 'the block prints its wrapper' );
		assert_same( 'No other nets are scheduled right now.', qafront095_plain( $html ), 'the sentence, and nothing else for a visitor' );
		assert_not_contains( '<li', $html, 'no empty bullets' );
	}
);

test(
	'Other nets with no week ticked: a Net Settings grant holder also gets the edit link',
	function () {
		qafront095_no_weeks();
		as_role( 'ares-net' );
		$html = qafront095_net( 'other-nets' );
		assert_contains( 'No other nets are scheduled right now.', qafront095_plain( $html ), 'the sentence' );
		assert_same( array( 'Edit this list in Net Settings' ), qafront095_edit_links( $html ), 'the edit link' );
	}
);

test(
	'Other nets (control): with the seeded weeks, the three bullets and no "no other nets" sentence',
	function () {
		as_anonymous();
		$html = qafront095_net( 'other-nets' );
		assert_same( 3, substr_count( $html, '<li' ), 'three bullets' );
		assert_not_contains( 'No other nets', $html, 'no empty-state sentence' );
	}
);

test(
	'net bar, settings box and From home: grant holders get "Edit these settings in Net Settings", not "Edit this list"',
	function () {
		foreach ( array( 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			foreach ( array( 'bar', 'settings', 'from-home' ) as $view ) {
				assert_same( array( 'Edit these settings in Net Settings' ), qafront095_edit_links( qafront095_net( $view ) ), $role . ' › ' . $view );
			}
			assert_same( array( 'Edit this list in Net Settings' ), qafront095_edit_links( qafront095_net( 'other-nets' ) ), $role . ' › other-nets is a list' );
			assert_same( array( 'Edit this list in Net Control Schedule' ), qafront095_edit_links( qafront095_net( 'rota' ) ), $role . ' › the Net Control Schedule keeps its own link' );
		}
	}
);

test(
	'net bar: no edit link for a visitor or an ARES Editor without the Net details grant',
	function () {
		foreach ( array( 'anonymous', 'subscriber', 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			assert_same( array(), qafront095_edit_links( qafront095_net( 'bar' ) ), $role . ' › bar' );
		}
	}
);
