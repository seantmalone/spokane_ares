<?php
/**
 * Plugin Name: Spokane ARES QA endpoints (DEV ONLY)
 * Description: Local development only. JSON endpoints for the QA crawler and the test runners in wordpress/dev/: ?dev_qa=whoami|ids|unlock|accounts on any URL, and /wp-admin/admin-post.php?action=spokares_dev_tests[&filter=] which runs dev/tests/php/*-test.php inside WordPress. Never shipped: it lives in wordpress/dev/ and is loaded only by the dev blueprint (setup/mu.php).
 * Version:     0.1.0
 * License:     GPL-2.0-or-later
 * Text Domain: spokares
 *
 * Acts only when ALL of these hold (the same rule as the dev login):
 *   - wp_get_environment_type() === 'local'
 *   - the constant SPOKARES_DEV is defined and true
 *   - the request comes from a loopback address (127.0.0.1 or ::1)
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || exit;

/**
 * Are the QA endpoints allowed for this request? (Local, SPOKARES_DEV, loopback.)
 */
function spokares_dev_qa_allowed(): bool {
	if ( 'local' !== wp_get_environment_type() || ! defined( 'SPOKARES_DEV' ) || ! SPOKARES_DEV ) {
		return false;
	}
	$addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return in_array( $addr, array( '127.0.0.1', '::1' ), true );
}

/**
 * ?dev_qa=<what>: answer with JSON and stop. After wp_loaded, so the post
 * types, taxonomies and roles exist; works on the front end and in wp-admin.
 *
 *   whoami    the signed-in user: id, login, roles, the primitive caps it has
 *             and, for &caps=a,b:12 (cap or cap:object id), current_user_can()
 *   ids       post and term IDs by slug (pages, events, documents, library
 *             sections) and user IDs by login, so no one hard-codes an ID
 *   unlock    delete every post edit lock (_edit_lock), so one role's visit
 *             to the block editor never shows the next role "locked"
 *   accounts  the dev accounts (qa-users.json) with their user IDs
 */
function spokares_dev_qa_endpoint(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- dev-only, loopback-only, read-mostly QA switch.
	if ( ! isset( $_GET['dev_qa'] ) || ! spokares_dev_qa_allowed() ) {
		return;
	}
	$what = sanitize_key( wp_unslash( $_GET['dev_qa'] ) );
	$caps = isset( $_GET['caps'] ) ? sanitize_text_field( wp_unslash( $_GET['caps'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	nocache_headers();
	switch ( $what ) {
		case 'whoami':
			wp_send_json( spokares_dev_qa_whoami( $caps ) );
			break;
		case 'ids':
			wp_send_json( spokares_dev_qa_ids() );
			break;
		case 'unlock':
			wp_send_json( array( 'unlocked' => delete_post_meta_by_key( '_edit_lock' ) ) );
			break;
		case 'accounts':
			wp_send_json( spokares_dev_qa_accounts() );
			break;
		default:
			wp_send_json( array( 'error' => 'unknown dev_qa: ' . $what ), 400 );
	}
}
add_action( 'wp_loaded', 'spokares_dev_qa_endpoint', 1 );

/**
 * The current user, its roles and primitive caps, and current_user_can() for
 * each requested cap ("cap" or "cap:<object id>").
 *
 * @param string $caps Comma-separated caps.
 */
function spokares_dev_qa_whoami( string $caps ): array {
	$user = wp_get_current_user();
	$can  = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', $caps ) ) ) as $spec ) {
		$parts        = explode( ':', $spec, 2 );
		$can[ $spec ] = isset( $parts[1] ) ? current_user_can( $parts[0], (int) $parts[1] ) : current_user_can( $parts[0] );
	}
	$have = array_keys( array_filter( (array) $user->allcaps ) );
	sort( $have );
	return array(
		'id'    => (int) $user->ID,
		'login' => (string) $user->user_login,
		'roles' => array_values( (array) $user->roles ),
		'caps'  => $have,
		'can'   => $can,
	);
}

/**
 * IDs by slug for the content the crawler and tests visit.
 */
function spokares_dev_qa_ids(): array {
	$out = array(
		'wp'        => get_bloginfo( 'version' ),
		'php'       => PHP_VERSION,
		'theme'     => get_stylesheet(),
		'pages'     => array(),
		'events'    => array(),
		'documents' => array(),
		// Post status by slug for the same events and documents (the crawler
		// expects an #anchor only for published events).
		'status'    => array(
			'events'    => array(),
			'documents' => array(),
		),
		'doc_cats'  => array(),
		// Media Library files the crawler opens: the Home hero photo, which the
		// administrator uploaded (page setup tags it _spk_seed = hero).
		'media'     => array(),
		'users'     => array(),
	);
	foreach ( array( 'home', 'how-it-works', 'about', 'members', 'members/documents', 'members/exercises' ) as $path ) {
		$page                  = get_page_by_path( $path, OBJECT, 'page' );
		$out['pages'][ $path ] = $page ? (int) $page->ID : 0;
	}
	foreach ( array(
		'events'    => 'spk_event',
		'documents' => 'spk_document',
	) as $key => $type ) {
		if ( ! post_type_exists( $type ) ) {
			continue;
		}
		$posts = get_posts(
			array(
				'post_type'        => $type,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'numberposts'      => -1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
		foreach ( $posts as $post ) {
			$out[ $key ][ $post->post_name ]           = (int) $post->ID;
			$out['status'][ $key ][ $post->post_name ] = (string) $post->post_status;
		}
	}
	$hero = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'numberposts'      => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one lookup on the dev site.
			'meta_key'         => '_spk_seed',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one lookup on the dev site.
			'meta_value'       => 'hero',
		)
	);
	if ( $hero ) {
		$out['media']['hero'] = (int) $hero[0];
	}
	if ( taxonomy_exists( 'spk_doc_cat' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'spk_doc_cat',
				'hide_empty' => false,
			)
		);
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$out['doc_cats'][ $term->slug ] = (int) $term->term_id;
		}
	}
	foreach ( spokares_dev_qa_accounts() as $account ) {
		if ( '' !== $account['login'] ) {
			$out['users'][ $account['login'] ] = $account['id'];
		}
	}
	return $out;
}

/**
 * The dev accounts from qa-users.json, each with its user ID (0 if missing).
 */
function spokares_dev_qa_accounts(): array {
	$data = wp_json_file_decode( dirname( __DIR__ ) . '/setup/qa-users.json', array( 'associative' => true ) );
	$out  = array();
	foreach ( (array) ( $data['accounts'] ?? array() ) as $account ) {
		if ( ! is_array( $account ) || empty( $account['key'] ) ) {
			continue;
		}
		$login = (string) ( $account['login'] ?? '' );
		$user  = '' !== $login ? get_user_by( 'login', $login ) : false;
		$out[] = array_merge(
			$account,
			array(
				'login' => $login,
				'id'    => $user ? (int) $user->ID : 0,
				'roles' => $user ? array_values( (array) $user->roles ) : array(),
			)
		);
	}
	return $out;
}

/**
 * /wp-admin/admin-post.php?action=spokares_dev_tests[&filter=<text>]: run the
 * PHP integration tests (dev/tests/php/*-test.php) in this request and answer
 * with JSON. admin-post.php is the context the plugin's save handlers run in
 * (is_admin(), the admin includes loaded, not an Ajax request). Runs last on
 * admin_init, so every plugin's admin_init has run; nobody is signed in.
 */
function spokares_dev_tests_endpoint(): void {
	global $pagenow;
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- dev-only, loopback-only test runner.
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	if ( 'admin-post.php' !== $pagenow || 'spokares_dev_tests' !== $action || ! spokares_dev_qa_allowed() ) {
		return;
	}
	$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : '';
	$list   = isset( $_GET['list'] );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	require_once dirname( __DIR__ ) . '/tests/lib/framework.php';
	nocache_headers();
	\Spokares\DevTests\serve( dirname( __DIR__ ) . '/tests/php', $filter, $list );
}
add_action( 'admin_init', 'spokares_dev_tests_endpoint', PHP_INT_MAX );
