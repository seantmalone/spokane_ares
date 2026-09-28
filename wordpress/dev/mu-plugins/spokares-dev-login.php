<?php
/**
 * Plugin Name: Spokane ARES dev login (DEV ONLY)
 * Description: Local development only. ?dev_login=1 signs in "admin", ?dev_login=editor signs in "editor", ?dev_login=<login> signs in any account in dev/setup/qa-users.json (qa-subscriber, qa-contributor, qa-author, qa-core-editor, qa-ares-net); ?dev_edit=<post_type>:<slug> opens that item's edit screen. Never shipped: it lives in wordpress/dev/ and is loaded only by the dev blueprint.
 * Version:     0.1.0
 * License:     GPL-2.0-or-later
 * Text Domain: spokares
 *
 * Acts only when ALL of these hold (PLAN.md §6.0 rule 9, §6.10):
 *   - wp_get_environment_type() === 'local'
 *   - the constant SPOKARES_DEV is defined and true
 *   - the request comes from a loopback address (127.0.0.1 or ::1)
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the dev login allowed for this request?
 */
function spokares_dev_login_allowed(): bool {
	if ( 'local' !== wp_get_environment_type() || ! defined( 'SPOKARES_DEV' ) || ! SPOKARES_DEV ) {
		return false;
	}
	$addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return in_array( $addr, array( '127.0.0.1', '::1' ), true );
}

/**
 * The dev accounts (dev/setup/qa-users.json), keyed by role key. Each has at
 * least 'login'; the anonymous entry has an empty login.
 *
 * @return array<string, array>
 */
function spokares_dev_accounts(): array {
	static $accounts = null;
	if ( null !== $accounts ) {
		return $accounts;
	}
	$accounts = array();
	$data     = wp_json_file_decode( dirname( __DIR__ ) . '/setup/qa-users.json', array( 'associative' => true ) );
	foreach ( (array) ( $data['accounts'] ?? array() ) as $account ) {
		if ( is_array( $account ) && ! empty( $account['key'] ) ) {
			$accounts[ (string) $account['key'] ] = $account;
		}
	}
	return $accounts;
}

/**
 * The user login a ?dev_login= value names: 1 or admin is "admin", editor is
 * "editor", and any login in qa-users.json is itself. Empty when unknown.
 *
 * @param string $which The sanitized ?dev_login= value.
 */
function spokares_dev_login_name( string $which ): string {
	if ( '1' === $which || 'admin' === $which ) {
		return 'admin';
	}
	if ( 'editor' === $which ) {
		return 'editor';
	}
	foreach ( spokares_dev_accounts() as $account ) {
		$login = (string) ( $account['login'] ?? '' );
		$alias = (string) ( $account['dev_login'] ?? '' );
		if ( '' !== $login && in_array( $which, array( $login, $alias ), true ) ) {
			return $login;
		}
	}
	return '';
}

/**
 * ?dev_login=1 (admin), ?dev_login=editor or ?dev_login=<a qa-users.json
 * login>: sign that user in and redirect to the same URL without the parameter.
 */
function spokares_dev_login(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- dev-only, loopback-only sign-in switch.
	if ( ! isset( $_GET['dev_login'] ) || ! spokares_dev_login_allowed() ) {
		return;
	}
	$which = sanitize_key( wp_unslash( $_GET['dev_login'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$login = spokares_dev_login_name( $which );
	if ( '' === $login ) {
		return;
	}
	$user = get_user_by( 'login', $login );
	if ( ! $user ) {
		wp_die( esc_html( sprintf( 'Dev login: no user "%s" in this site.', $login ) ), 'Dev login', array( 'response' => 404 ) );
	}
	if ( get_current_user_id() !== $user->ID ) {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, false );
	}
	nocache_headers();
	wp_safe_redirect( remove_query_arg( 'dev_login' ) );
	exit;
}
add_action( 'init', 'spokares_dev_login', 0 );

/**
 * ?dev_edit=<post_type>:<slug> (after signing in): redirect to that item's
 * edit screen, e.g. spk_event:set-2026, spk_document:ics-213, page:home.
 * Used by dev/shots.mjs; posts are looked up by slug, so IDs never matter.
 */
function spokares_dev_edit_redirect(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- dev-only, read-only redirect.
	if ( ! isset( $_GET['dev_edit'] ) || isset( $_GET['dev_login'] ) || ! is_user_logged_in() || ! spokares_dev_login_allowed() ) {
		return;
	}
	$spec = sanitize_text_field( wp_unslash( $_GET['dev_edit'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$parts = explode( ':', $spec, 2 );
	if ( 2 !== count( $parts ) ) {
		return;
	}
	$type = sanitize_key( $parts[0] );
	$path = implode( '/', array_map( 'sanitize_title', explode( '/', $parts[1] ) ) );
	if ( ! post_type_exists( $type ) ) {
		wp_die( esc_html( sprintf( 'Dev edit: unknown post type "%s".', $type ) ), 'Dev edit', array( 'response' => 404 ) );
	}
	$post = get_page_by_path( $path, OBJECT, $type );
	if ( ! $post ) {
		wp_die( esc_html( sprintf( 'Dev edit: no %1$s with the slug "%2$s".', $type, $path ) ), 'Dev edit', array( 'response' => 404 ) );
	}
	$link = get_edit_post_link( $post->ID, 'raw' );
	if ( ! $link ) {
		wp_die( esc_html__( 'Dev edit: this user cannot edit that item.', 'spokares' ), 'Dev edit', array( 'response' => 403 ) );
	}
	nocache_headers();
	wp_safe_redirect( $link );
	exit;
}
add_action( 'wp_loaded', 'spokares_dev_edit_redirect' ); // After init: the post types exist by then.
