<?php
/**
 * DEV ONLY: a tiny framework for PHP integration tests that run INSIDE the dev
 * WordPress (dev/tests/php/*-test.php). The QA mu-plugin's endpoint
 * (/wp-admin/admin-post.php?action=spokares_dev_tests) loads this file and
 * calls serve(); dev/tests/run-php.sh prints the result. See dev/tests/README.md.
 *
 * A test file:
 *
 *     namespace Spokares\DevTests;
 *     defined( 'ABSPATH' ) || exit;
 *     test( 'a subscriber cannot save the rota', function () {
 *         as_role( 'subscriber' );
 *         $r = post_form( 'spokares_save_rota', array() );
 *         assert_same( 403, $r['status'] );
 *     } );
 *
 * Each test starts signed out with a clean $_GET/$_POST, and afterwards the
 * database tables are put back as they were (rows added, changed or deleted by
 * the test), files of new attachments are deleted and caches are flushed. A
 * PHP warning, notice or deprecation during a test fails it unless the test
 * was registered with array( 'allow_notices' => true ).
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

// Signal messages are test-runner data, sent back as JSON and never printed as HTML.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

require_once __DIR__ . '/class-signal.php';

/* ------------------------------------------------------------------ state */

/**
 * The run's shared state (registered tests, results, the running test).
 */
function &state(): array {
	static $state = array(
		'tests'    => array(),
		'results'  => array(),
		'file'     => '',
		'current'  => null,
		'running'  => false,
		'finished' => false,
		'ob_level' => 0,
		'filter'   => '',
		'started'  => 0.0,
	);
	return $state;
}

/**
 * Register a test.
 *
 * @param string   $name     Test name (unique within its file).
 * @param callable $callback The test; it passes unless it throws or an assertion fails.
 * @param array    $opts     allow_notices (bool): PHP notices don't fail this test.
 */
function test( string $name, callable $callback, array $opts = array() ): void {
	$s            = &state();
	$s['tests'][] = array(
		'file' => $s['file'],
		'name' => $name,
		'fn'   => $callback,
		'opts' => $opts,
	);
}

/**
 * "file.php:12" for the first stack frame inside a *-test.php file.
 *
 * @param array $trace A debug backtrace or an exception's getTrace().
 */
function test_frame( array $trace ): string {
	foreach ( $trace as $frame ) {
		if ( isset( $frame['file'] ) && str_ends_with( (string) $frame['file'], '-test.php' ) ) {
			return basename( (string) $frame['file'] ) . ':' . (int) ( $frame['line'] ?? 0 );
		}
	}
	return '';
}

/* ------------------------------------------------------------- assertions */

/**
 * Count one assertion for the running test.
 */
function count_assertion(): void {
	$s = &state();
	if ( is_array( $s['current'] ) ) {
		++$s['current']['assertions'];
	}
}

/**
 * A short readable form of a value for failure messages.
 *
 * @param mixed $value Value.
 */
function export( $value ): string {
	if ( $value instanceof \WP_Error ) {
		return 'WP_Error(' . $value->get_error_code() . ': ' . $value->get_error_message() . ')';
	}
	if ( is_object( $value ) ) {
		return get_class( $value );
	}
	$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$json = false === $json ? gettype( $value ) : $json;
	return strlen( $json ) > 300 ? substr( $json, 0, 300 ) . '…' : $json;
}

/**
 * Fail the running test.
 *
 * @param string $message Why.
 * @throws Signal Always.
 */
function fail( string $message ): void {
	throw new Signal( 'fail', $message );
}

/**
 * Skip the running test (it counts as neither pass nor fail).
 *
 * @param string $why Why.
 * @throws Signal Always.
 */
function skip( string $why ): void {
	throw new Signal( 'skip', $why );
}

/**
 * Prefix a failure with the caller's message.
 *
 * @param string $message Caller's message.
 * @param string $detail  What was wrong.
 */
function msg( string $message, string $detail ): string {
	return '' === $message ? $detail : $message . ': ' . $detail;
}

/**
 * Assert that a value is exactly true.
 *
 * @param mixed  $value   Value.
 * @param string $message Message.
 */
function assert_true( $value, string $message = '' ): void {
	count_assertion();
	if ( true !== $value ) {
		fail( msg( $message, 'expected true, got ' . export( $value ) ) );
	}
}

/**
 * Assert that a value is exactly false.
 *
 * @param mixed  $value   Value.
 * @param string $message Message.
 */
function assert_false( $value, string $message = '' ): void {
	count_assertion();
	if ( false !== $value ) {
		fail( msg( $message, 'expected false, got ' . export( $value ) ) );
	}
}

/**
 * Assert $expected === $actual.
 *
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 * @param string $message  Message.
 */
function assert_same( $expected, $actual, string $message = '' ): void {
	count_assertion();
	if ( $expected !== $actual ) {
		fail( msg( $message, 'expected ' . export( $expected ) . ', got ' . export( $actual ) ) );
	}
}

/**
 * Assert $expected !== $actual.
 *
 * @param mixed  $unexpected The value it must not be.
 * @param mixed  $actual     Actual.
 * @param string $message    Message.
 */
function assert_not_same( $unexpected, $actual, string $message = '' ): void {
	count_assertion();
	if ( $unexpected === $actual ) {
		fail( msg( $message, 'did not expect ' . export( $actual ) ) );
	}
}

/**
 * Assert $expected == $actual (loose; arrays in any key order).
 *
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 * @param string $message  Message.
 */
function assert_equals( $expected, $actual, string $message = '' ): void {
	count_assertion();
	if ( $expected != $actual ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- loose on purpose; assert_same() is the strict one.
		fail( msg( $message, 'expected ' . export( $expected ) . ', got ' . export( $actual ) ) );
	}
}

/**
 * Assert that a string contains a substring, or an array contains a value (strict).
 *
 * @param mixed        $needle   Substring or value.
 * @param string|array $haystack String or array.
 * @param string       $message  Message.
 */
function assert_contains( $needle, $haystack, string $message = '' ): void {
	count_assertion();
	$found = is_array( $haystack ) ? in_array( $needle, $haystack, true ) : str_contains( (string) $haystack, (string) $needle );
	if ( ! $found ) {
		fail( msg( $message, export( $needle ) . ' not found in ' . export( $haystack ) ) );
	}
}

/**
 * Assert that a string does not contain a substring, or an array a value.
 *
 * @param mixed        $needle   Substring or value.
 * @param string|array $haystack String or array.
 * @param string       $message  Message.
 */
function assert_not_contains( $needle, $haystack, string $message = '' ): void {
	count_assertion();
	$found = is_array( $haystack ) ? in_array( $needle, $haystack, true ) : str_contains( (string) $haystack, (string) $needle );
	if ( $found ) {
		fail( msg( $message, export( $needle ) . ' found in ' . export( $haystack ) ) );
	}
}

/**
 * Assert that a string matches a regular expression.
 *
 * @param string $pattern PCRE pattern with delimiters.
 * @param string $actual  String.
 * @param string $message Message.
 */
function assert_matches( string $pattern, $actual, string $message = '' ): void {
	count_assertion();
	if ( ! preg_match( $pattern, (string) $actual ) ) {
		fail( msg( $message, export( $actual ) . ' does not match ' . $pattern ) );
	}
}

/**
 * Assert count( $value ) === $count.
 *
 * @param int                $count   Expected count.
 * @param array|\Countable   $value   Array or Countable.
 * @param string             $message Message.
 */
function assert_count( int $count, $value, string $message = '' ): void {
	count_assertion();
	$n = is_array( $value ) || $value instanceof \Countable ? count( $value ) : -1;
	if ( $n !== $count ) {
		fail( msg( $message, 'expected ' . $count . ' items, got ' . ( $n < 0 ? 'a ' . gettype( $value ) : $n ) ) );
	}
}

/**
 * Turn a REST response holding an error into its WP_Error (anything else as is).
 *
 * @param mixed $value Value.
 * @return mixed
 */
function as_error( $value ) {
	if ( $value instanceof \WP_REST_Response && $value->is_error() ) {
		return $value->as_error();
	}
	return $value;
}

/**
 * Assert that a value is a WP_Error (or an error REST response), optionally
 * with the given code and HTTP status. Returns the error.
 *
 * @param mixed  $value   Value.
 * @param string $code    Expected error code ('' = any).
 * @param int    $status  Expected data.status (0 = any).
 * @param string $message Message.
 */
function expect_wp_error( $value, string $code = '', int $status = 0, string $message = '' ): \WP_Error {
	count_assertion();
	$value = as_error( $value );
	if ( ! $value instanceof \WP_Error ) {
		fail( msg( $message, 'expected a WP_Error' . ( $code ? ' "' . $code . '"' : '' ) . ', got ' . export( $value instanceof \WP_REST_Response ? $value->get_status() . ' ' . export( $value->get_data() ) : $value ) ) );
	}
	if ( '' !== $code && ! in_array( $code, $value->get_error_codes(), true ) ) {
		fail( msg( $message, 'expected error code "' . $code . '", got ' . export( $value ) ) );
	}
	if ( $status ) {
		$data = $value->get_error_data();
		$got  = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;
		if ( $got !== $status ) {
			fail( msg( $message, 'expected status ' . $status . ', got ' . $got . ' (' . export( $value ) . ')' ) );
		}
	}
	return $value;
}

/**
 * Assert that a value is not a WP_Error (nor an error REST response).
 *
 * @param mixed  $value   Value.
 * @param string $message Message.
 * @return mixed The value.
 */
function expect_not_wp_error( $value, string $message = '' ) {
	count_assertion();
	if ( as_error( $value ) instanceof \WP_Error ) {
		fail( msg( $message, 'unexpected ' . export( as_error( $value ) ) ) );
	}
	return $value;
}

/**
 * Run $callback and assert that it calls wp_die() (optionally with this HTTP
 * status). Returns array( 'message' => text, 'status' => int ).
 *
 * @param callable $callback Code under test.
 * @param int      $status   Expected status (0 = any).
 * @param string   $message  Message.
 */
function expect_die( callable $callback, int $status = 0, string $message = '' ): array {
	count_assertion();
	try {
		$callback();
	} catch ( Signal $s ) {
		if ( 'die' !== $s->kind ) {
			throw $s;
		}
		$got = (int) ( $s->data['status'] ?? 0 );
		if ( $status && $got !== $status ) {
			fail( msg( $message, 'expected wp_die() with status ' . $status . ', got ' . $got . ': ' . $s->getMessage() ) );
		}
		return array(
			'message' => $s->getMessage(),
			'status'  => $got,
		);
	}
	fail( msg( $message, 'expected wp_die(), but the code returned' ) );
	return array();
}

/**
 * Run $callback and assert that it redirects (optionally to a URL containing
 * $contains). Returns the location.
 *
 * @param callable $callback Code under test.
 * @param string   $contains Expected substring of the location ('' = any).
 * @param string   $message  Message.
 */
function expect_redirect( callable $callback, string $contains = '', string $message = '' ): string {
	count_assertion();
	try {
		$callback();
	} catch ( Signal $s ) {
		if ( 'redirect' !== $s->kind ) {
			throw $s;
		}
		if ( '' !== $contains && ! str_contains( $s->getMessage(), $contains ) ) {
			fail( msg( $message, 'expected a redirect to …' . $contains . '…, got ' . $s->getMessage() ) );
		}
		return $s->getMessage();
	}
	fail( msg( $message, 'expected a redirect, but the code returned' ) );
	return '';
}

/* -------------------------------------------------------- acting as a role */

/**
 * The dev accounts (dev/setup/qa-users.json) by role key, each with its user ID.
 */
function accounts(): array {
	static $accounts = null;
	if ( null === $accounts ) {
		$accounts = array();
		foreach ( \spokares_dev_qa_accounts() as $account ) {
			$accounts[ $account['key'] ] = $account;
		}
	}
	return $accounts;
}

/**
 * Role keys: anonymous, subscriber, contributor, author, core-editor,
 * ares-editor, ares-net, admin.
 */
function role_keys(): array {
	return array_keys( accounts() );
}

/**
 * The user ID for a role key (0 for anonymous). Fails when the account is missing.
 *
 * @param string $key Role key.
 */
function user_id( string $key ): int {
	$accounts = accounts();
	if ( ! isset( $accounts[ $key ] ) ) {
		fail( 'unknown role key "' . $key . '" (known: ' . implode( ', ', role_keys() ) . ')' );
	}
	if ( 'anonymous' !== $key && ! $accounts[ $key ]['id'] ) {
		fail( 'the dev account for "' . $key . '" (' . $accounts[ $key ]['login'] . ') does not exist; restart the dev site' );
	}
	return (int) $accounts[ $key ]['id'];
}

/**
 * Act as the dev account for a role key ('anonymous' signs out). Returns the user.
 *
 * @param string $key Role key.
 */
function as_role( string $key ): \WP_User {
	return wp_set_current_user( user_id( $key ) );
}

/**
 * Act as nobody (signed out).
 */
function as_anonymous(): void {
	wp_set_current_user( 0 );
}

/* ---------------------------------------------------------- content lookup */

/**
 * A page's ID by path (home, about, members/documents…). Fails when missing.
 *
 * @param string $path Page path.
 */
function page_id( string $path ): int {
	$page = get_page_by_path( $path, OBJECT, 'page' );
	if ( ! $page ) {
		fail( 'no page at "' . $path . '"' );
	}
	return (int) $page->ID;
}

/**
 * A post's ID by type and slug (e.g. spk_event, set-2026). Fails when missing.
 *
 * @param string $type Post type.
 * @param string $slug Slug.
 */
function post_id( string $type, string $slug ): int {
	$post = get_page_by_path( $slug, OBJECT, $type );
	if ( ! $post ) {
		fail( 'no ' . $type . ' with the slug "' . $slug . '"' );
	}
	return (int) $post->ID;
}

/**
 * Insert a post for the test (removed again after the test). Returns its ID.
 *
 * @param array $args wp_insert_post() args; defaults: a published "QA test post".
 */
function create_post( array $args = array() ): int {
	$id = wp_insert_post(
		wp_slash(
			array_merge(
				array(
					'post_title'  => 'QA test post',
					'post_status' => 'publish',
					'post_type'   => 'post',
				),
				$args
			)
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		fail( 'create_post: ' . $id->get_error_message() );
	}
	return (int) $id;
}

/* -------------------------------------------------- requests and handlers */

/**
 * Run $callback as one request would see it: $_GET, $_POST and $_REQUEST (slashed,
 * as WordPress has them) and the request method, restored afterwards. A
 * redirect or wp_die() ends $callback and is reported. Returns:
 *   redirect  the location, or null
 *   status    the redirect's or wp_die()'s HTTP status, or null
 *   die       the wp_die() text, or null
 *   output    anything printed
 *   returned  $callback's return value
 *
 * @param string   $method   GET or POST.
 * @param array    $get      Query args (unslashed).
 * @param array    $post     Body fields (unslashed).
 * @param callable $callback The code to run.
 * @throws Signal An assertion failure inside $callback.
 */
function call_request( string $method, array $get, array $post, callable $callback ): array {
	// phpcs:disable WordPress.Security.NonceVerification -- the test sets the superglobals; nothing is read here.
	$saved = array( $_GET, $_POST, $_REQUEST, $_SERVER['REQUEST_METHOD'] ?? 'GET' );
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- a simulated request.
	$_GET                      = wp_slash( $get );
	$_POST                     = wp_slash( $post );
	$_REQUEST                  = array_merge( $_GET, $_POST );
	$_SERVER['REQUEST_METHOD'] = strtoupper( $method );
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

	$result  = array(
		'redirect' => null,
		'status'   => null,
		'die'      => null,
		'output'   => '',
		'returned' => null,
	);
	$rethrow = null;
	ob_start();
	try {
		$result['returned'] = $callback();
	} catch ( Signal $s ) {
		if ( 'redirect' === $s->kind ) {
			$result['redirect'] = $s->getMessage();
			$result['status']   = (int) ( $s->data['status'] ?? 302 );
		} elseif ( 'die' === $s->kind ) {
			$result['die']    = $s->getMessage();
			$result['status'] = (int) ( $s->data['status'] ?? 500 );
		} else {
			$rethrow = $s;
		}
	} finally {
		$result['output'] = (string) ob_get_clean();
		list( $_GET, $_POST, $_REQUEST, $_SERVER['REQUEST_METHOD'] ) = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
	}
	// phpcs:enable WordPress.Security.NonceVerification
	if ( $rethrow ) {
		throw $rethrow;
	}
	return $result;
}

/**
 * Submit a plugin form the way admin-post.php does: POST $fields plus
 * action=$action and a valid nonce for the current user, then run
 * do_action( "admin_post_{$action}" ). See call_request() for the result.
 *
 * @param string $action The admin-post action, e.g. spokares_save_rota.
 * @param array  $fields Body fields (unslashed).
 * @param array  $opts   nonce: true (valid, default), false (none) or a string;
 *                       nonce_action: the nonce action if not $action;
 *                       get: query args.
 */
function post_form( string $action, array $fields = array(), array $opts = array() ): array {
	$nonce  = $opts['nonce'] ?? true;
	$fields = array_merge(
		array(
			'action'           => $action,
			'_wp_http_referer' => '/wp-admin/admin.php',
		),
		$fields
	);
	if ( true === $nonce ) {
		$fields['_wpnonce'] = wp_create_nonce( $opts['nonce_action'] ?? $action );
	} elseif ( is_string( $nonce ) ) {
		$fields['_wpnonce'] = $nonce;
	}
	return call_request(
		'POST',
		(array) ( $opts['get'] ?? array() ),
		$fields,
		static function () use ( $action ) {
			do_action( 'admin_post_' . $action ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's admin-post hook, fired as admin-post.php does.
		}
	);
}

/**
 * Follow a GET link to admin-post.php (the Duplicate and Pull row actions):
 * $query plus action=$action and, unless nonce is false, a nonce for
 * $opts['nonce_action'] (default $action).
 *
 * @param string $action The admin-post action.
 * @param array  $query  Query args (unslashed).
 * @param array  $opts   nonce, nonce_action as for post_form().
 */
function get_action( string $action, array $query = array(), array $opts = array() ): array {
	$nonce = $opts['nonce'] ?? true;
	$query = array_merge( array( 'action' => $action ), $query );
	if ( true === $nonce ) {
		$query['_wpnonce'] = wp_create_nonce( $opts['nonce_action'] ?? $action );
	} elseif ( is_string( $nonce ) ) {
		$query['_wpnonce'] = $nonce;
	}
	return call_request(
		'GET',
		$query,
		array(),
		static function () use ( $action ) {
			do_action( 'admin_post_' . $action ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's admin-post hook, fired as admin-post.php does.
		}
	);
}

/**
 * Dispatch a REST request internally (rest_do_request) as the current user.
 * GET and DELETE send $params as the query; other methods as the body.
 *
 * @param string $method HTTP method.
 * @param string $route  Route, e.g. /wp/v2/pages/62.
 * @param array  $params Params.
 * @param array  $query  Extra query params for a POST/PUT.
 */
function rest( string $method, string $route, array $params = array(), array $query = array() ): \WP_REST_Response {
	$method  = strtoupper( $method );
	$request = new \WP_REST_Request( $method, $route );
	if ( in_array( $method, array( 'GET', 'DELETE', 'HEAD' ), true ) ) {
		$request->set_query_params( array_merge( $params, $query ) );
	} else {
		$request->set_query_params( $query );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $params ) );
	}
	return rest_ensure_response( rest_do_request( $request ) );
}

/* --------------------------------------------------------------- clean-up */

/**
 * Tables snapshotted around each test, with their primary key columns.
 */
function db_tables(): array {
	global $wpdb;
	return array(
		$wpdb->posts              => array( 'ID' ),
		$wpdb->postmeta           => array( 'meta_id' ),
		$wpdb->options            => array( 'option_name' ),
		$wpdb->users              => array( 'ID' ),
		$wpdb->usermeta           => array( 'umeta_id' ),
		$wpdb->terms              => array( 'term_id' ),
		$wpdb->term_taxonomy      => array( 'term_taxonomy_id' ),
		$wpdb->term_relationships => array( 'object_id', 'term_taxonomy_id' ),
		$wpdb->termmeta           => array( 'meta_id' ),
		$wpdb->comments           => array( 'comment_ID' ),
		$wpdb->commentmeta        => array( 'meta_id' ),
	);
}

/**
 * Every row of the snapshotted tables, keyed by table then primary key.
 */
function db_snapshot(): array {
	global $wpdb;
	$snap = array();
	foreach ( db_tables() as $table => $keys ) {
		$snap[ $table ] = array();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- dev test snapshot; the table name is $wpdb's own.
		foreach ( (array) $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) as $row ) {
			$id                    = implode( "\0", array_map( static fn( $k ) => (string) $row[ $k ], $keys ) );
			$snap[ $table ][ $id ] = $row;
		}
	}
	return $snap;
}

/**
 * Put the tables back as they were in $before: delete the files of
 * attachments added since, then delete added rows, re-insert deleted rows and
 * restore changed rows; then flush the caches. Returns a count per change.
 *
 * @param array $before A db_snapshot().
 */
function db_restore( array $before ): array {
	global $wpdb;
	$after = db_snapshot();
	$new   = array_diff_key( $after[ $wpdb->posts ], $before[ $wpdb->posts ] );
	foreach ( $new as $row ) {
		if ( 'attachment' === $row['post_type'] ) {
			wp_delete_attachment( (int) $row['ID'], true );
		}
	}
	if ( $new ) {
		$after = db_snapshot();
	}
	$counts = array(
		'deleted'  => 0,
		'inserted' => 0,
		'updated'  => 0,
	);
	foreach ( db_tables() as $table => $keys ) {
		$was = $before[ $table ];
		$now = $after[ $table ];
		foreach ( array_diff_key( $now, $was ) as $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- dev test clean-up.
			$wpdb->delete( $table, array_intersect_key( $row, array_flip( $keys ) ) );
			++$counts['deleted'];
		}
		foreach ( array_diff_key( $was, $now ) as $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- dev test clean-up.
			$wpdb->insert( $table, $row );
			++$counts['inserted'];
		}
		foreach ( array_intersect_key( $was, $now ) as $id => $row ) {
			if ( $row !== $now[ $id ] ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- dev test clean-up.
				$wpdb->update( $table, $row, array_intersect_key( $row, array_flip( $keys ) ) );
				++$counts['updated'];
			}
		}
	}
	wp_cache_flush();
	wp_roles()->for_site();
	if ( function_exists( 'spokares_opt_flush' ) ) {
		\spokares_opt_flush();
	}
	return $counts;
}

/* ------------------------------------------------------------ interception */

/**
 * wp_redirect filter while tests run: end the code under test with a Signal.
 *
 * @param string $location Location.
 * @param int    $status   Status.
 * @throws Signal Always.
 */
function on_redirect( $location, $status ) {
	throw new Signal(
		'redirect',
		(string) $location,
		array( 'status' => (int) $status )
	);
}

/**
 * wp_die handler while tests run: end the code under test with a Signal.
 *
 * @param string|\WP_Error $message Message.
 * @param string|int       $title   Title.
 * @param array|int        $args    Args.
 * @throws Signal Always.
 */
function on_die( $message, $title = '', $args = array() ): void {
	unset( $title );
	if ( $message instanceof \WP_Error ) {
		$message = $message->get_error_message();
	}
	$status = is_array( $args ) && isset( $args['response'] ) ? (int) $args['response'] : ( is_int( $args ) ? $args : 500 );
	throw new Signal(
		'die',
		trim( wp_strip_all_tags( (string) $message ) ),
		array( 'status' => $status )
	);
}

/**
 * The die handler, for every wp_die_*_handler filter.
 */
function die_handler(): string {
	return __NAMESPACE__ . '\\on_die';
}

/**
 * Hook or unhook the redirect and wp_die() interception.
 *
 * @param bool $on On or off.
 */
function intercept( bool $on ): void {
	$filters = array( 'wp_die_handler', 'wp_die_ajax_handler', 'wp_die_json_handler', 'wp_die_jsonp_handler', 'wp_die_xml_handler', 'wp_die_xmlrpc_handler' );
	if ( $on ) {
		add_filter( 'wp_redirect', __NAMESPACE__ . '\\on_redirect', PHP_INT_MAX, 2 );
		foreach ( $filters as $filter ) {
			add_filter( $filter, __NAMESPACE__ . '\\die_handler', PHP_INT_MAX );
		}
		return;
	}
	remove_filter( 'wp_redirect', __NAMESPACE__ . '\\on_redirect', PHP_INT_MAX );
	foreach ( $filters as $filter ) {
		remove_filter( $filter, __NAMESPACE__ . '\\die_handler', PHP_INT_MAX );
	}
}

/* ----------------------------------------------------------------- runner */

/**
 * Run one test and put the site back. Returns its result.
 *
 * @param array $t Registered test.
 */
function run_one( array $t ): array {
	$s            = &state();
	$s['current'] = array(
		'file'       => $t['file'],
		'name'       => $t['name'],
		'assertions' => 0,
	);
	$snapshot     = db_snapshot();
	$notices      = array();
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- collects PHP notices raised by the test.
	set_error_handler(
		static function ( $errno, $errstr, $errfile, $errline ) use ( &$notices ) {
			if ( error_reporting() & $errno ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions -- reads the level only.
				$names     = array(
					E_WARNING         => 'Warning',
					E_NOTICE          => 'Notice',
					E_DEPRECATED      => 'Deprecated',
					E_USER_WARNING    => 'User warning',
					E_USER_NOTICE     => 'User notice',
					E_USER_DEPRECATED => 'User deprecated',
				);
				$notices[] = ( $names[ $errno ] ?? 'PHP error ' . $errno ) . ': ' . $errstr . ' in ' . basename( (string) $errfile ) . ':' . $errline;
			}
			return false; // WordPress's own logging carries on.
		}
	);
	wp_set_current_user( 0 );
	$status  = 'pass';
	$message = '';
	$where   = '';
	$t0      = microtime( true );
	ob_start();
	try {
		call_user_func( $t['fn'] );
	} catch ( Signal $e ) {
		$where = $e->where;
		switch ( $e->kind ) {
			case 'skip':
				$status  = 'skip';
				$message = $e->getMessage();
				break;
			case 'redirect':
				$status  = 'fail';
				$message = 'unexpected redirect to ' . $e->getMessage() . ' (use post_form(), call_request() or expect_redirect())';
				break;
			case 'die':
				$status  = 'fail';
				$message = 'unexpected wp_die() (' . (int) ( $e->data['status'] ?? 0 ) . '): ' . $e->getMessage();
				break;
			default:
				$status  = 'fail';
				$message = $e->getMessage();
		}
	} catch ( \Throwable $e ) {
		$status  = 'error';
		$message = get_class( $e ) . ': ' . $e->getMessage() . ' (' . basename( $e->getFile() ) . ':' . $e->getLine() . ')';
		$where   = test_frame( $e->getTrace() );
		if ( '' === $where && str_ends_with( $e->getFile(), '-test.php' ) ) {
			$where = basename( $e->getFile() ) . ':' . $e->getLine();
		}
	}
	$output = (string) ob_get_clean();
	restore_error_handler();
	$ms = (int) round( ( microtime( true ) - $t0 ) * 1000 );
	if ( 'pass' === $status && $notices && empty( $t['opts']['allow_notices'] ) ) {
		$status  = 'fail';
		$message = count( $notices ) . ' PHP notice(s) during the test: ' . $notices[0];
	}
	if ( 'pass' === $status && 0 === $s['current']['assertions'] ) {
		$message = 'no assertions';
	}
	wp_set_current_user( 0 );
	try {
		$restored = db_restore( $snapshot );
	} catch ( \Throwable $e ) {
		$restored = array( 'error' => $e->getMessage() );
	}
	$assertions   = (int) $s['current']['assertions'];
	$s['current'] = null;
	return array(
		'file'       => $t['file'],
		'name'       => $t['name'],
		'status'     => $status,
		'message'    => $message,
		'where'      => $where,
		'assertions' => $assertions,
		'ms'         => $ms,
		'notices'    => array_slice( $notices, 0, 20 ),
		'output'     => strlen( $output ) > 2000 ? substr( $output, 0, 2000 ) . '…' : $output,
		'restored'   => $restored,
	);
}

/**
 * The JSON report.
 *
 * @param bool $partial The run was cut short (a fatal error).
 */
function report( bool $partial = false ): array {
	$s      = &state();
	$counts = array(
		'pass'  => 0,
		'fail'  => 0,
		'error' => 0,
		'skip'  => 0,
	);
	foreach ( $s['results'] as $r ) {
		++$counts[ $r['status'] ];
	}
	return array(
		'ok'      => ! $partial && 0 === $counts['fail'] + $counts['error'],
		'partial' => $partial,
		'filter'  => $s['filter'],
		'wp'      => get_bloginfo( 'version' ),
		'php'     => PHP_VERSION,
		'counts'  => $counts,
		'ms'      => (int) round( ( microtime( true ) - $s['started'] ) * 1000 ),
		'tests'   => $s['results'],
	);
}

/**
 * Shutdown: if a test ended the request (a fatal error or an exit), still
 * answer with the results so far and the error.
 */
function on_shutdown(): void {
	$s = &state();
	if ( ! $s['running'] || $s['finished'] ) {
		return;
	}
	$err = error_get_last();
	while ( ob_get_level() > $s['ob_level'] ) {
		ob_end_clean();
	}
	$current        = is_array( $s['current'] ) ? $s['current'] : array(
		'file' => '',
		'name' => '(between tests)',
	);
	$s['results'][] = array(
		'file'       => $current['file'],
		'name'       => $current['name'],
		'status'     => 'error',
		'message'    => $err ? 'PHP fatal: ' . $err['message'] . ' (' . basename( (string) $err['file'] ) . ':' . $err['line'] . ')' : 'the request ended during the test (exit or die); its data was not cleaned up: restart the dev site',
		'where'      => '',
		'assertions' => (int) ( $current['assertions'] ?? 0 ),
		'ms'         => 0,
		'notices'    => array(),
		'output'     => '',
		'restored'   => array( 'error' => 'not restored' ),
	);
	if ( ! headers_sent() ) {
		status_header( 200 );
		header( 'Content-Type: application/json; charset=utf-8' );
	}
	echo wp_json_encode( report( true ) );
}

/**
 * Load the test files in $dir, run the tests whose "file::name" contains
 * $filter (case-insensitive), answer with JSON and stop.
 *
 * @param string $dir    Folder with *-test.php files.
 * @param string $filter Filter ('' = all).
 * @param bool   $list_only Only list the tests.
 */
function serve( string $dir, string $filter, bool $list_only = false ): void {
	$s            = &state();
	$s['started'] = microtime( true );
	$s['filter']  = $filter;
	$files        = glob( trailingslashit( $dir ) . '*-test.php' );
	sort( $files );
	foreach ( $files as $file ) {
		$s['file'] = basename( $file );
		require $file;
	}
	$s['file'] = '';
	$tests     = array_values(
		array_filter(
			$s['tests'],
			static fn( $t ) => '' === $filter || false !== stripos( $t['file'] . '::' . $t['name'], $filter )
		)
	);
	if ( $list_only ) {
		wp_send_json( array_map( static fn( $t ) => $t['file'] . '::' . $t['name'], $tests ) );
	}
	$s['running']  = true;
	$s['ob_level'] = ob_get_level();
	register_shutdown_function( __NAMESPACE__ . '\\on_shutdown' );
	intercept( true );
	foreach ( $tests as $t ) {
		$s['results'][] = run_one( $t );
	}
	intercept( false );
	$s['finished'] = true;
	wp_send_json( report() );
}
