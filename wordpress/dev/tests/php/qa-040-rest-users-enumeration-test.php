<?php
/**
 * Regression tests for QA-040 (PLAN §5.3, no user enumeration): signed-in
 * users without list_users must see at most their own account through the
 * REST users routes. The hardening plugin (enumeration.php) removes the
 * /wp/v2/users routes only for signed-out visitors, so a subscriber,
 * contributor, author, core Editor or ARES Editor gets core's behaviour:
 * GET /wp/v2/users lists every account with a published post in a REST post
 * type (the administrator's slug "admin", name, author link and avatar hash
 * on a fresh site), ?who=authors (anyone with edit_posts) lists every
 * author-level account, /wp/v2/users/1 answers 200, and a page's embedded
 * author carries the same record.
 *
 * Nothing on the site needs these for other accounts: pages are the only
 * type in the block editor, and editor-guard.js removes the post-status
 * panel (where Author lives), so the editor asks only for /wp/v2/users/me
 * (checked in a browser as the ARES Editor and the administrator).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The signed-in dev accounts that do not have list_users.
 */
function qa_040_roles(): array {
	return array( 'subscriber', 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net' );
}

/**
 * The accounts in a users REST response, as "id:slug" (none for an error).
 *
 * @param mixed $data A users response's data: one user or a list of them.
 */
function qa_040_accounts_in( $data ): array {
	if ( ! is_array( $data ) || isset( $data['code'] ) ) {
		return array();
	}
	$rows = isset( $data['id'] ) ? array( $data ) : $data;
	$out  = array();
	foreach ( $rows as $row ) {
		if ( is_array( $row ) && isset( $row['id'] ) && ! isset( $row['code'] ) ) {
			$out[] = (int) $row['id'] . ':' . ( isset( $row['slug'] ) ? (string) $row['slug'] : '' );
		}
	}
	return $out;
}

/**
 * The accounts other than the current user's that a users REST response shows.
 *
 * @param \WP_REST_Response $res A response from rest().
 */
function qa_040_others( \WP_REST_Response $res ): array {
	if ( $res->is_error() ) {
		return array();
	}
	$me = get_current_user_id();
	return array_values(
		array_filter(
			qa_040_accounts_in( $res->get_data() ),
			static function ( $account ) use ( $me ) {
				return (int) $account !== $me;
			}
		)
	);
}

/**
 * GET /wp/v2/users?who=authors as the current user. Core's users controller
 * (WordPress 7.1) still passes "who" on to WP_User_Query, which reports its
 * own "who is deprecated" notice on every such query, whoever asks and
 * whatever this site does; only that one notice is kept quiet, so any other
 * notice still fails the test.
 */
function qa_040_who_authors(): \WP_REST_Response {
	$core_who = false;
	$spot     = static function ( $function_name, $message ) use ( &$core_who ) {
		$core_who = 'WP_User_Query' === $function_name && str_contains( (string) $message, '<code>who</code>' );
	};
	$quiet    = static function ( $trigger ) use ( &$core_who ) {
		$quiet_this = $core_who;
		$core_who   = false;
		return $quiet_this ? false : $trigger;
	};
	add_action( 'deprecated_argument_run', $spot, 10, 2 );
	add_filter( 'deprecated_argument_trigger_error', $quiet );
	try {
		return rest(
			'GET',
			'/wp/v2/users',
			array(
				'who'      => 'authors',
				'per_page' => 100,
			)
		);
	} finally {
		remove_action( 'deprecated_argument_run', $spot, 10 );
		remove_filter( 'deprecated_argument_trigger_error', $quiet );
	}
}

test(
	'control: an administrator still lists every account, and every signed-in role can read its own at /wp/v2/users/me',
	function () {
		as_role( 'admin' );
		$all   = expect_not_wp_error( rest( 'GET', '/wp/v2/users', array( 'per_page' => 100 ) ), 'admin: /wp/v2/users' );
		$slugs = array_column( $all->get_data(), 'slug' );
		foreach ( array( 'admin', 'editor', 'qa-subscriber', 'qa-core-editor' ) as $slug ) {
			assert_contains( $slug, $slugs, 'admin: /wp/v2/users lists ' . $slug );
		}

		foreach ( qa_040_roles() as $key ) {
			as_role( $key );
			assert_false( current_user_can( 'list_users' ), $key . ': precondition, no list_users' );
			$me = expect_not_wp_error( rest( 'GET', '/wp/v2/users/me' ), $key . ': /wp/v2/users/me (the block editor needs it)' );
			assert_same( user_id( $key ), (int) $me->get_data()['id'], $key . ': /wp/v2/users/me is the signed-in account' );
		}
	}
);

test(
	'without list_users, GET /wp/v2/users shows at most your own account (no admin slug)',
	function () {
		$queries = array(
			array( 'per_page' => 100 ),
			array(
				'per_page' => 100,
				'orderby'  => 'name',
			),
			array( 'search' => 'adm' ),
			array( 'slug' => 'admin' ),
			array( 'include' => array( user_id( 'admin' ) ) ),
		);
		$leaks   = array();
		foreach ( qa_040_roles() as $key ) {
			as_role( $key );
			foreach ( $queries as $query ) {
				$others = qa_040_others( rest( 'GET', '/wp/v2/users', $query ) );
				if ( $others ) {
					$leaks[] = $key . ' ?' . rawurldecode( http_build_query( $query ) ) . ' -> ' . implode( ', ', $others );
				}
			}
		}
		assert_same( array(), $leaks, 'other accounts listed (role ?query -> id:slug)' );
	}
);

test(
	'without list_users, GET /wp/v2/users?who=authors does not list other accounts (no admin slug)',
	function () {
		$leaks = array();
		foreach ( qa_040_roles() as $key ) {
			as_role( $key );
			$others = qa_040_others( qa_040_who_authors() );
			if ( $others ) {
				$leaks[] = $key . ' -> ' . implode( ', ', $others );
			}
		}
		assert_same( array(), $leaks, 'other accounts listed by ?who=authors (role -> id:slug)' );
	}
);

test(
	'without list_users, another account cannot be read at /wp/v2/users/<id>',
	function () {
		$leaks = array();
		foreach ( qa_040_roles() as $key ) {
			as_role( $key );
			foreach ( array( 'admin', 'core-editor' ) as $target ) {
				if ( $target === $key ) {
					continue;
				}
				$res = rest( 'GET', '/wp/v2/users/' . user_id( $target ) );
				if ( ! $res->is_error() ) {
					$leaks[] = $key . ' -> /wp/v2/users/' . user_id( $target ) . ' (' . $target . '): ' . $res->get_status() . ' ' . implode( ', ', qa_040_accounts_in( $res->get_data() ) );
				}
			}
		}
		assert_same( array(), $leaks, 'another account readable by ID (role -> route: status id:slug)' );
	}
);

test(
	'without list_users, a page\'s embedded author does not reveal the administrator\'s account',
	function () {
		$about = page_id( 'about' );
		assert_same( user_id( 'admin' ), (int) get_post_field( 'post_author', $about ), 'precondition: the administrator is the About page\'s author' );
		$leaks = array();
		foreach ( qa_040_roles() as $key ) {
			as_role( $key );
			$res  = expect_not_wp_error( rest( 'GET', '/wp/v2/pages/' . $about ), $key . ': the About page over REST (control)' );
			$data = rest_get_server()->response_to_data( $res, array( 'author' ) );
			$seen = array();
			foreach ( isset( $data['_embedded']['author'] ) ? (array) $data['_embedded']['author'] : array() as $embedded ) {
				$seen = array_merge( $seen, qa_040_accounts_in( $embedded ) );
			}
			if ( $seen ) {
				$leaks[] = $key . ' -> ' . implode( ', ', $seen );
			}
		}
		assert_same( array(), $leaks, 'the About page\'s _embedded author (role -> id:slug)' );
	}
);
