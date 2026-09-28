<?php
/**
 * Regression tests for QA-023: sorting Documents by the "Reviewed" column
 * hides every document that has never been reviewed. The document save
 * deletes an empty "Last reviewed" (spk_reviewed) instead of storing '', and
 * spokares_document_list_query() sorts with meta_key=spk_reviewed +
 * orderby=meta_value, which WP_Query turns into an INNER JOIN on that key. So
 * a draft saved without a review date is listed under Documents › Drafts, but
 * clicking the Reviewed header shows "0 items", and All loses it too. The
 * sort must only order the list: the same documents, a never-reviewed one
 * ("Not yet") counting as the oldest.
 *
 * The list query is run the way edit.php runs it (wp_edit_posts_query() with
 * the list's query args in $_GET, so it is the main query in the admin).
 *
 * Note for the fix: an OR meta_query of EXISTS + NOT EXISTS ordered by the
 * EXISTS clause lists every document again, but its join is not limited to
 * the spk_reviewed key, so a never-reviewed document is sorted by whichever of
 * its other meta rows GROUP BY keeps (the last test catches that). Order by a
 * join that carries the key in its ON (the NOT EXISTS clause's), where a
 * missing date is NULL.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Save a new draft document through its form (as post.php does: the form's
 * fields and nonce in $_POST, then wp_update_post()) with "Last reviewed" left
 * empty. Returns its ID.
 *
 * @param string $title Title.
 */
function qa023_save_draft( string $title ): int {
	$id = create_post(
		array(
			'post_type'   => 'spk_document',
			'post_status' => 'draft',
			'post_title'  => $title,
			'post_author' => get_current_user_id(),
		)
	);
	$r  = call_request(
		'POST',
		array(),
		array(
			'spokares_document_nonce' => wp_create_nonce( 'spokares_document_meta' ),
			'original_post_status'    => 'draft',
			'post_title'              => $title,
			'spk_section'             => 'net-ops',
			'spk_source'              => 'soon',
			'spk_reviewed'            => '',
		),
		static function () use ( $id, $title ) {
			return wp_update_post(
				wp_slash(
					array(
						'ID'          => $id,
						'post_title'  => $title,
						'post_status' => 'draft',
					)
				),
				true
			);
		}
	);
	expect_not_wp_error( $r['returned'], 'save the draft' );
	clean_post_cache( $id );
	assert_same( 'draft', get_post_status( $id ), 'set-up: saved as a draft' );
	assert_false( metadata_exists( 'post', $id, 'spk_reviewed' ), 'set-up: an empty Last reviewed is not stored' );
	return $id;
}

/**
 * The Documents list (wp-admin/edit.php?post_type=spk_document&…) as the
 * current user: the IDs on the page, in order, and the "N items" count. The
 * main query and the globals it sets are put back afterwards.
 *
 * @param array $args Query args after post_type (post_status, orderby, order, spk_section).
 */
function qa023_list( array $args ): array {
	// One page holds every document, so the IDs are the whole list.
	update_user_option( get_current_user_id(), 'edit_spk_document_per_page', 500 );

	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated edit.php main query; every global is put back below.
	global $wp, $wp_query, $wp_the_query;
	$before       = $GLOBALS;
	$saved        = array( $wp, $wp_query, $wp_the_query );
	$wp           = clone $wp;
	$wp_the_query = new \WP_Query();
	$wp_query     = $wp_the_query;
	$ids          = array();
	$found        = -1;
	$vars         = array();
	try {
		call_request(
			'GET',
			array_merge( array( 'post_type' => 'spk_document' ), $args ),
			array(),
			static function () {
				wp_edit_posts_query();
			}
		);
		$ids   = array_map( 'intval', wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
		$found = (int) $GLOBALS['wp_query']->found_posts;
		$vars  = array_keys( (array) $GLOBALS['wp_query']->query_vars );
	} finally {
		list( $wp, $wp_query, $wp_the_query ) = $saved;
		// WP::register_globals() copied the query vars (and these) into globals.
		foreach ( array_merge( $vars, array( 'query_string', 'posts', 'post', 'request', 'more', 'single', 'authordata' ) ) as $key ) {
			if ( array_key_exists( $key, $before ) ) {
				$GLOBALS[ $key ] = $before[ $key ]; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- restoring core's globals.
			} else {
				unset( $GLOBALS[ $key ] );
			}
		}
	}
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	return array(
		'ids'   => $ids,
		'found' => $found,
	);
}

/**
 * The Reviewed value of each document, in list order ('' = never reviewed).
 *
 * @param array $ids Document IDs.
 */
function qa023_dates( array $ids ): array {
	return array_map( static fn( $id ) => (string) get_post_meta( $id, 'spk_reviewed', true ), $ids );
}

test(
	'Documents › Drafts sorted by Reviewed still lists a draft that has never been reviewed',
	function () {
		foreach ( array( 'ares-editor', 'ares-net', 'admin' ) as $role ) {
			as_role( $role );
			$id    = qa023_save_draft( 'QA-023 unreviewed draft ' . $role );
			$plain = qa023_list( array( 'post_status' => 'draft' ) );
			assert_contains( $id, $plain['ids'], $role . ': set-up: the draft is under Drafts' );

			foreach ( array( 'asc', 'desc' ) as $order ) {
				$sorted = qa023_list(
					array(
						'post_status' => 'draft',
						'orderby'     => 'spk_reviewed',
						'order'       => $order,
					)
				);
				assert_contains( $id, $sorted['ids'], $role . ': Drafts sorted by Reviewed (' . $order . ') still lists the unreviewed draft' );
				assert_same( $plain['found'], $sorted['found'], $role . ': Drafts sorted by Reviewed (' . $order . ') has the same item count' );
			}

			// The Section filter and the sort together.
			$section = qa023_list(
				array(
					'post_status' => 'draft',
					'spk_section' => 'net-ops',
					'orderby'     => 'spk_reviewed',
					'order'       => 'asc',
				)
			);
			assert_contains( $id, $section['ids'], $role . ': Net operations drafts sorted by Reviewed still list the unreviewed draft' );
		}
	}
);

test(
	'Documents › All sorted by Reviewed lists the same documents as the unsorted list',
	function () {
		foreach ( array( 'ares-editor', 'admin' ) as $role ) {
			as_role( $role );
			$id    = qa023_save_draft( 'QA-023 unreviewed ' . $role );
			$plain = qa023_list( array() );
			assert_contains( $id, $plain['ids'], $role . ': set-up: the draft is under All' );
			$want = $plain['ids'];
			sort( $want );

			foreach ( array( 'asc', 'desc' ) as $order ) {
				$sorted = qa023_list(
					array(
						'orderby' => 'spk_reviewed',
						'order'   => $order,
					)
				);
				$got    = $sorted['ids'];
				sort( $got );
				assert_same( $plain['found'], $sorted['found'], $role . ': All sorted by Reviewed (' . $order . ') has the same item count' );
				assert_same( $want, $got, $role . ': All sorted by Reviewed (' . $order . ') lists the same documents' );
			}
		}
	}
);

test(
	'a never-reviewed document sorts as the oldest: first ascending, last descending',
	function () {
		as_role( 'ares-editor' );
		$id  = qa023_save_draft( 'QA-023 never reviewed' );
		$asc = qa023_list(
			array(
				'orderby' => 'spk_reviewed',
				'order'   => 'asc',
			)
		);
		assert_contains( $id, $asc['ids'], 'ascending: the never-reviewed document is listed' );
		$dates = qa023_dates( $asc['ids'] );
		assert_same( '', $dates[0] ?? null, 'ascending: "Not yet" comes first: ' . export( array_slice( $dates, 0, 3 ) ) );
		$dated = array_values( array_filter( $dates, 'strlen' ) );
		$want  = $dated;
		sort( $want, SORT_STRING );
		assert_same( $want, $dated, 'ascending: the reviewed documents are oldest review first' );

		$desc = qa023_list(
			array(
				'orderby' => 'spk_reviewed',
				'order'   => 'desc',
			)
		);
		assert_contains( $id, $desc['ids'], 'descending: the never-reviewed document is listed' );
		$dates = qa023_dates( $desc['ids'] );
		assert_same( '', end( $dates ), 'descending: "Not yet" comes last: ' . export( array_slice( $dates, -3 ) ) );
		$dated = array_values( array_filter( $dates, 'strlen' ) );
		$want  = $dated;
		rsort( $want, SORT_STRING );
		assert_same( $want, $dated, 'descending: the reviewed documents are newest review first' );
	}
);
