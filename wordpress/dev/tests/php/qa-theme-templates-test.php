<?php
/**
 * Regression tests for the theme's templates (QA-031, QA-102, QA-098; PLAN
 * §4.3 page templates, §6.7 templates and render filters).
 *
 * QA-031: templates/page.html was only header, post content and footer, so
 * a page an administrator adds had no h1 and no column. It is now a plain page
 * (title + column), and How it works and About, which carry their own h1 and
 * full-width sections, get slug templates of their own (page-how-it-works,
 * page-about). Those two are chosen by slug only: they must not show up in
 * the page Template picker, for administrators or editors, so nobody gives
 * another page a layout with no title. The members templates stay offered
 * to administrators (prior-page-template-test.php checks editors).
 *
 * QA-102: every one of the theme's page templates has a readable name (not
 * its file slug) and a description in Appearance › Editor › Templates.
 *
 * QA-098: on the search results page the "search the document library"
 * link carries the query (?q=), and nowhere else is it changed.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

test(
	'How it works and About templates are chosen by slug and offered to nobody; the members templates keep readable names',
	function () {
		$page = get_post( page_id( 'about' ) );
		foreach ( array( 'admin', 'ares-editor', 'core-editor' ) as $role ) {
			as_role( $role );
			$offered = wp_get_theme()->get_page_templates( $page, 'page' );
			assert_false( isset( $offered['page-about'] ), $role . ' is offered page-about' );
			assert_false( isset( $offered['page-how-it-works'] ), $role . ' is offered page-how-it-works' );
		}
		as_role( 'admin' );
		$offered = wp_get_theme()->get_page_templates( $page, 'page' );
		foreach ( array( 'page-members', 'page-documents', 'page-exercises' ) as $slug ) {
			assert_true( isset( $offered[ $slug ] ), 'admin is offered ' . $slug );
			assert_not_same( $slug, $offered[ $slug ] ?? $slug, $slug . ' is offered under its file name' );
		}
	}
);

test(
	'the slug templates resolve for How it works and About; any other page gets the plain page template with a title',
	function () {
		foreach ( array( 'how-it-works', 'about' ) as $slug ) {
			$template = get_block_template( get_stylesheet() . '//page-' . $slug );
			assert_true( $template instanceof \WP_Block_Template, 'page-' . $slug . ' exists' );
			assert_not_contains( 'wp:post-title', $template->content, 'page-' . $slug . ' adds no title (the page has its own h1)' );
			$resolved = resolve_block_template( 'page', array( 'page-' . $slug . '.php', 'page-' . page_id( $slug ) . '.php', 'page.php' ), '' );
			assert_same( 'page-' . $slug, $resolved ? $resolved->slug : '', 'the ' . $slug . ' page resolves to its own template' );
		}
		$resolved = resolve_block_template( 'page', array( 'page-qa-theme-new.php', 'page-999999.php', 'page.php' ), '' );
		assert_same( 'page', $resolved ? $resolved->slug : '', 'a new page resolves to page' );
		assert_contains( 'wp:post-title {"level":1', $resolved->content, 'page.html prints the title as the h1' );
		assert_matches( '/"className":"wrap"/', $resolved->content, 'page.html puts the page in the site column' );
	}
);

test(
	'every theme page template has a readable name and a description',
	function () {
		as_role( 'admin' );
		$found = array();
		foreach ( get_block_templates( array(), 'wp_template' ) as $template ) {
			if ( get_stylesheet() === $template->theme ) {
				$found[ $template->slug ] = $template;
			}
		}
		foreach ( array( 'page-members', 'page-documents', 'page-exercises', 'page-how-it-works', 'page-about' ) as $slug ) {
			assert_true( isset( $found[ $slug ] ), $slug . ' is listed' );
			assert_not_same( $slug, $found[ $slug ]->title, $slug . ' shows its file name' );
			assert_true( '' !== trim( (string) $found[ $slug ]->description ), $slug . ' has a description' );
			$one = get_block_template( get_stylesheet() . '//' . $slug );
			assert_true( '' !== trim( (string) $one->description ), $slug . ' has a description when fetched alone' );
		}
	}
);

test(
	'search results: the document-library link carries the query; elsewhere it is left alone',
	function () {
		$markup = '<!-- wp:paragraph {"className":"results__more search-library"} --><p class="results__more search-library">Forms: <a href="/members/documents/#search">search the library</a>.</p><!-- /wp:paragraph -->';
		global $wp_query, $wp_the_query;
		$saved_query = $wp_query;
		$saved_the   = $wp_the_query;
		try {
			$wp_query     = new \WP_Query( array( 's' => 'ICS 213 & <b>' ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			$wp_the_query = $wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			$html         = do_blocks( $markup );
			assert_contains( 'href="' . esc_url( home_url( '/members/documents/?q=ICS%20213%20%26%20%3Cb%3E#search' ) ) . '"', $html, 'the link on a search page' );
			assert_not_contains( '<b>', $html, 'the query is escaped' );

			$wp_query     = new \WP_Query( array( 'page_id' => page_id( 'about' ) ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			$wp_the_query = $wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
			assert_contains( 'href="/members/documents/#search"', do_blocks( $markup ), 'the link off a search page' );
		} finally {
			$wp_query     = $saved_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
			$wp_the_query = $saved_the; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
		}
	}
);
