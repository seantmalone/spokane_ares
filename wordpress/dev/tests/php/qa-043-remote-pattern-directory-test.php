<?php
/**
 * Regression tests for QA-043 (PLAN §4.3 layer 5: no remote patterns; theme
 * inc/setup.php: "every pattern on this site is the theme's own").
 *
 * The theme returns false from should_load_remote_block_patterns, but that
 * filter only stops core loading WordPress.org patterns into the editor's
 * registry. Core's REST route GET /wp/v2/pattern-directory/patterns
 * (WP_REST_Pattern_Directory_Controller, gated only by edit_posts) is still
 * registered, so any signed-in account with edit_posts (Contributor, Author,
 * core Editor, both ARES Editor accounts, administrators) can make the server
 * call api.wordpress.org/patterns/1.0/ and get WordPress.org patterns back:
 * 200 with "Mountain Intro Cards" and the like.
 *
 * Correct behaviour: the route is gone for everyone (rest_no_route, 404) and
 * a request for it makes no outbound call to api.wordpress.org/patterns.
 *
 * The outbound call is caught with pre_http_request and answered with an
 * empty list, so the tests never touch the network; a unique search term
 * keeps a cached answer (the controller's transient) from hiding the call.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * The core route that proxies the WordPress.org Pattern Directory.
 */
function qa_043_route(): string {
	return '/wp/v2/pattern-directory/patterns';
}

/**
 * Requests the Pattern Directory route as each role, catching (and faking)
 * any call the server makes to api.wordpress.org/patterns.
 *
 * @param string[] $roles Role keys.
 * @return array{responses: array<string, \WP_REST_Response>, routes: array<string, bool>, calls: string[]}
 */
function qa_043_probe( array $roles ): array {
	$calls = array();
	$spy   = static function ( $pre, $args, $url ) use ( &$calls ) {
		unset( $args );
		if ( false !== strpos( (string) $url, 'api.wordpress.org/patterns' ) ) {
			$calls[] = (string) $url;
			return array(
				'headers'  => array(),
				'body'     => '[]',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}
		return $pre;
	};
	$out = array(
		'responses' => array(),
		'routes'    => array(),
		'calls'     => array(),
	);
	add_filter( 'pre_http_request', $spy, 1, 3 );
	try {
		foreach ( $roles as $role ) {
			as_role( $role );
			$routes                    = rest_get_server()->get_routes();
			$out['routes'][ $role ]    = isset( $routes[ qa_043_route() ] );
			$out['responses'][ $role ] = rest(
				'GET',
				qa_043_route(),
				array(
					'per_page' => 1,
					'search'   => 'qa043' . $role . wp_generate_password( 10, false ),
				)
			);
		}
	} finally {
		remove_filter( 'pre_http_request', $spy, 1 );
	}
	$out['calls'] = $calls;
	return $out;
}

test(
	'no editing role can reach the remote Pattern Directory over REST (rest_no_route 404, no call to api.wordpress.org)',
	function () {
		$roles = array( 'contributor', 'author', 'core-editor', 'ares-editor', 'ares-net' );
		$probe = qa_043_probe( $roles );
		assert_count( 0, $probe['calls'], 'the server called api.wordpress.org/patterns: ' . implode( ', ', $probe['calls'] ) );
		foreach ( $roles as $role ) {
			expect_wp_error( $probe['responses'][ $role ], 'rest_no_route', 404, $role . ': GET ' . qa_043_route() . ' (status ' . $probe['responses'][ $role ]->get_status() . ')' );
			assert_false( $probe['routes'][ $role ], $role . ': ' . qa_043_route() . ' is still registered' );
		}
	}
);

test(
	'administrators get no remote patterns over REST either (the theme turns them off site-wide)',
	function () {
		$probe = qa_043_probe( array( 'admin' ) );
		assert_count( 0, $probe['calls'], 'the server called api.wordpress.org/patterns: ' . implode( ', ', $probe['calls'] ) );
		expect_wp_error( $probe['responses']['admin'], 'rest_no_route', 404, 'admin: GET ' . qa_043_route() . ' (status ' . $probe['responses']['admin']->get_status() . ')' );
		assert_false( $probe['routes']['admin'], 'admin: ' . qa_043_route() . ' is still registered' );
	}
);

test(
	'the theme still turns off remote block patterns (should_load_remote_block_patterns is false)',
	function () {
		as_role( 'admin' );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's filter, asked as core asks it.
		assert_false( (bool) apply_filters( 'should_load_remote_block_patterns', true ), 'should_load_remote_block_patterns' );
	}
);
