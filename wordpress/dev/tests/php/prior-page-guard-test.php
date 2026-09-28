<?php
/**
 * Regression tests for a Fixer-round bug that shipped without a test
 * (build-notes/plugin.md "Fixer round" › Pages; PLAN §4.3, §8.1 #1): a
 * non-administrator could put a page behind a password, change its publish
 * date or author, or take it off the site (draft, private, scheduled). The
 * page guard (governance.php spokares_page_insert_guard()) keeps a page's
 * password, publish date, author and published status, as well as its slug,
 * parent and order, as stored, on every save path: REST (the block editor)
 * and wp_update_post() (the classic form and Quick Edit).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The page fields the guard keeps, read fresh from the database.
 *
 * @param int $id Page ID.
 */
function prior_page_guard_fields( int $id ): array {
	clean_post_cache( $id );
	$p = get_post( $id );
	return array(
		'post_password' => (string) $p->post_password,
		'post_status'   => (string) $p->post_status,
		'post_date'     => (string) $p->post_date,
		'post_date_gmt' => (string) $p->post_date_gmt,
		'post_author'   => (int) $p->post_author,
		'post_name'     => (string) $p->post_name,
		'post_parent'   => (int) $p->post_parent,
		'menu_order'    => (int) $p->menu_order,
	);
}

/**
 * A user who is not the page's stored author (so an author change shows).
 *
 * @param int $id Page ID.
 */
function prior_page_guard_new_author( int $id ): int {
	$stored = (int) get_post( $id )->post_author;
	foreach ( array( 'ares-editor', 'core-editor', 'admin' ) as $key ) {
		if ( user_id( $key ) !== $stored ) {
			return user_id( $key );
		}
	}
	return 0;
}

test(
	'REST: an editor cannot set a page password, publish date or author',
	function () {
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			$id     = page_id( 'about' );
			$before = prior_page_guard_fields( $id );
			$author = prior_page_guard_new_author( $id );
			assert_same( 'publish', $before['post_status'], 'About is published before the test' );
			as_role( $role );
			$res = rest(
				'POST',
				'/wp/v2/pages/' . $id,
				array(
					'password' => 'qa-secret',
					'date'     => '2019-03-04T05:06:07',
					'author'   => $author,
				)
			);
			expect_not_wp_error( $res, $role . ': the save itself goes through' );
			$after = prior_page_guard_fields( $id );
			assert_same( '', $after['post_password'], $role . ': password' );
			assert_same( $before['post_date'], $after['post_date'], $role . ': publish date' );
			assert_same( $before['post_date_gmt'], $after['post_date_gmt'], $role . ': publish date (GMT)' );
			assert_same( $before['post_author'], $after['post_author'], $role . ': author' );
			assert_same( 'publish', $after['post_status'], $role . ': status' );
		}
	}
);

test(
	'REST: an editor cannot take a page off the site (draft, pending, private, scheduled)',
	function () {
		$attempts = array(
			array( 'status' => 'draft' ),
			array( 'status' => 'pending' ),
			array( 'status' => 'private' ),
			array(
				'status' => 'future',
				'date'   => '2031-02-03T04:05:06',
			),
			array( 'date' => '2031-02-03T04:05:06' ), // A future date alone would schedule it.
		);
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			foreach ( $attempts as $params ) {
				$id     = page_id( 'about' );
				$before = prior_page_guard_fields( $id );
				$label  = $role . ' ' . export( $params );
				as_role( $role );
				// The request itself is accepted (the role may publish pages), so
				// only the guard stands between it and the stored status.
				expect_not_wp_error( rest( 'POST', '/wp/v2/pages/' . $id, $params ), $label . ': the save itself goes through' );
				$after = prior_page_guard_fields( $id );
				assert_same( 'publish', $after['post_status'], $label . ': status' );
				assert_same( $before['post_date'], $after['post_date'], $label . ': publish date' );
				assert_same( $before['post_date_gmt'], $after['post_date_gmt'], $label . ': publish date (GMT)' );
			}
		}
	}
);

test(
	'classic save (wp_update_post): an editor cannot change a page password, date, author, status, slug, parent or order',
	function () {
		foreach ( array( 'ares-editor', 'core-editor' ) as $role ) {
			foreach ( array( 'about', 'how-it-works' ) as $path ) {
				$id     = page_id( $path );
				$before = prior_page_guard_fields( $id );
				$author = prior_page_guard_new_author( $id );
				as_role( $role );
				$saved = wp_update_post(
					wp_slash(
						array(
							'ID'            => $id,
							'post_password' => 'qa-secret',
							'post_date'     => '2019-03-04 05:06:07',
							'post_date_gmt' => '2019-03-04 13:06:07',
							'edit_date'     => true,
							'post_author'   => $author,
							'post_status'   => 'draft',
							'post_name'     => 'qa-moved-' . $path,
							'post_parent'   => page_id( 'home' ),
							'menu_order'    => 42,
						)
					),
					true
				);
				expect_not_wp_error( $saved, $role . ' ' . $path . ': the save itself goes through' );
				assert_same( $before, prior_page_guard_fields( $id ), $role . ' ' . $path . ': every guarded field as stored' );
			}
		}
	}
);

test(
	'an administrator can still change a page password, date, author and status (the guard is for non-admins only)',
	function () {
		$id     = page_id( 'about' );
		$before = prior_page_guard_fields( $id );
		$author = prior_page_guard_new_author( $id );
		as_role( 'admin' );
		expect_not_wp_error(
			rest(
				'POST',
				'/wp/v2/pages/' . $id,
				array(
					'password' => 'qa-secret',
					'date'     => '2019-03-04T05:06:07',
					'author'   => $author,
				)
			)
		);
		$after = prior_page_guard_fields( $id );
		assert_same( 'qa-secret', $after['post_password'], 'password' );
		assert_same( '2019-03-04 05:06:07', $after['post_date'], 'publish date' );
		assert_same( $author, $after['post_author'], 'author' );
		assert_not_same( $before['post_author'], $after['post_author'], 'author changed' );

		expect_not_wp_error(
			rest(
				'POST',
				'/wp/v2/pages/' . $id,
				array(
					'status'   => 'draft',
					'password' => '',
				)
			)
		);
		assert_same( 'draft', prior_page_guard_fields( $id )['post_status'], 'status' );
	}
);
