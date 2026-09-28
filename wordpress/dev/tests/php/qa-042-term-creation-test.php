<?php
/**
 * Regression tests for QA-042 (PLAN §4.1: an ARES Editor lacks
 * manage_categories, and only administrators add content; §4.2: the core
 * roles must not reach what an ARES Editor can't). WordPress lets anyone
 * with a taxonomy's assign_terms cap create terms in a non-hierarchical
 * taxonomy over REST, and assign_terms is edit_posts for post_tag and
 * wp_pattern_category. So the Contributor, the Author, the core Editor and
 * both ARES Editor accounts could POST /wp/v2/tags and
 * /wp/v2/wp_pattern_category (and the same through /batch/v1) and get 201:
 * the terms persisted until an administrator removed them. The core Editor
 * also has manage_categories, so it could create categories over REST and
 * open Posts › Categories, Posts › Tags and Pattern Categories
 * (edit-tags.php) with add, edit and delete, which ARES Editors are refused.
 *
 * Correct behaviour: no non-administrator can create a category, a post tag
 * or a pattern category (REST refuses with rest_cannot_create, 403, and no
 * term is stored), directly or through the batch route; the core Editor
 * lacks the manage, edit and delete term caps for those taxonomies (so
 * edit-tags.php refuses it too); administrators still can do all of it.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The taxonomies this bug is about, by REST route.
 */
function qa042_routes(): array {
	return array(
		'categories'          => 'category',
		'tags'                => 'post_tag',
		'wp_pattern_category' => 'wp_pattern_category',
	);
}

/**
 * Try to create a term over REST as the current user and check it was
 * refused with rest_cannot_create (403) and that nothing was stored.
 *
 * @param string $role  Role key (for the messages).
 * @param string $route REST route under /wp/v2/.
 */
function qa042_expect_refused( string $role, string $route ): void {
	$taxonomy = qa042_routes()[ $route ];
	$name     = 'qa042 ' . $role . ' ' . $route;
	$res      = rest( 'POST', '/wp/v2/' . $route, array( 'name' => $name ) );
	expect_wp_error( $res, 'rest_cannot_create', 403, $role . ' POST /wp/v2/' . $route . ' (got HTTP ' . $res->get_status() . ')' );
	assert_true( null === term_exists( $name, $taxonomy ), $role . ': no ' . $taxonomy . ' term may be stored' );
}

test(
	'a contributor cannot create a post tag or a pattern category over REST',
	function () {
		as_role( 'contributor' );
		qa042_expect_refused( 'contributor', 'tags' );
		qa042_expect_refused( 'contributor', 'wp_pattern_category' );
	}
);

test(
	'an author cannot create a post tag or a pattern category over REST',
	function () {
		as_role( 'author' );
		qa042_expect_refused( 'author', 'tags' );
		qa042_expect_refused( 'author', 'wp_pattern_category' );
	}
);

test(
	'an ARES Editor cannot create a post tag or a pattern category over REST',
	function () {
		as_role( 'ares-editor' );
		qa042_expect_refused( 'ares-editor', 'tags' );
		qa042_expect_refused( 'ares-editor', 'wp_pattern_category' );
	}
);

test(
	'an ARES Editor with the Net details grant cannot create a post tag or a pattern category over REST',
	function () {
		as_role( 'ares-net' );
		qa042_expect_refused( 'ares-net', 'tags' );
		qa042_expect_refused( 'ares-net', 'wp_pattern_category' );
	}
);

test(
	'the core Editor cannot create a category, a post tag or a pattern category over REST',
	function () {
		as_role( 'core-editor' );
		qa042_expect_refused( 'core-editor', 'categories' );
		qa042_expect_refused( 'core-editor', 'tags' );
		qa042_expect_refused( 'core-editor', 'wp_pattern_category' );
	}
);

test(
	'the batch route does not create terms for an ARES Editor or a contributor',
	function () {
		foreach ( array( 'ares-editor', 'contributor' ) as $role ) {
			as_role( $role );
			$res = rest(
				'POST',
				'/batch/v1',
				array(
					'requests' => array(
						array(
							'method' => 'POST',
							'path'   => '/wp/v2/tags',
							'body'   => array( 'name' => 'qa042 batch tag ' . $role ),
						),
						array(
							'method' => 'POST',
							'path'   => '/wp/v2/wp_pattern_category',
							'body'   => array( 'name' => 'qa042 batch pattern category ' . $role ),
						),
					),
				)
			);
			$data = $res->get_data();
			assert_true( isset( $data['responses'] ) && is_array( $data['responses'] ), $role . ': the batch route answers with a responses list' );
			assert_count( 2, $data['responses'], $role . ': one answer per request' );
			foreach ( $data['responses'] as $i => $answer ) {
				assert_same( 403, (int) ( $answer['status'] ?? 0 ), $role . ': batch request ' . $i . ' must be refused' );
				assert_same( 'rest_cannot_create', (string) ( $answer['body']['code'] ?? '' ), $role . ': batch request ' . $i . ' error code' );
			}
			assert_true( null === term_exists( 'qa042 batch tag ' . $role, 'post_tag' ), $role . ': no tag stored through the batch route' );
			assert_true( null === term_exists( 'qa042 batch pattern category ' . $role, 'wp_pattern_category' ), $role . ': no pattern category stored through the batch route' );
		}
	}
);

test(
	'the core Editor cannot manage, edit or delete categories, tags or pattern categories',
	function () {
		as_role( 'admin' );
		$terms = array();
		foreach ( qa042_routes() as $taxonomy ) {
			$made = wp_insert_term( 'qa042 existing ' . $taxonomy, $taxonomy );
			expect_not_wp_error( $made, 'setup: an administrator adds a ' . $taxonomy . ' term' );
			$terms[ $taxonomy ] = (int) $made['term_id'];
		}

		as_role( 'core-editor' );
		foreach ( $terms as $taxonomy => $term_id ) {
			$tax = get_taxonomy( $taxonomy );
			assert_true( $tax instanceof \WP_Taxonomy, $taxonomy . ' is registered' );
			// edit-tags.php refuses without manage_terms; adding needs edit_terms.
			assert_false( current_user_can( $tax->cap->manage_terms ), 'core-editor: manage_terms (' . $tax->cap->manage_terms . ') on ' . $taxonomy . ' opens edit-tags.php' );
			assert_false( current_user_can( $tax->cap->edit_terms ), 'core-editor: edit_terms (' . $tax->cap->edit_terms . ') on ' . $taxonomy . ' allows adding terms' );
			assert_false( current_user_can( $tax->cap->delete_terms ), 'core-editor: delete_terms (' . $tax->cap->delete_terms . ') on ' . $taxonomy );
			assert_false( current_user_can( 'edit_term', $term_id ), 'core-editor: edit_term on an existing ' . $taxonomy . ' term' );
			assert_false( current_user_can( 'delete_term', $term_id ), 'core-editor: delete_term on an existing ' . $taxonomy . ' term' );
		}
	}
);

test(
	'an administrator can still create categories, tags and pattern categories over REST',
	function () {
		as_role( 'admin' );
		foreach ( qa042_routes() as $route => $taxonomy ) {
			$name = 'qa042 admin ' . $route;
			$res  = rest( 'POST', '/wp/v2/' . $route, array( 'name' => $name ) );
			expect_not_wp_error( $res, 'admin POST /wp/v2/' . $route );
			assert_same( 201, $res->get_status(), 'admin POST /wp/v2/' . $route . ' status' );
			assert_true( null !== term_exists( $name, $taxonomy ), 'admin: the ' . $taxonomy . ' term is stored' );
		}
	}
);
