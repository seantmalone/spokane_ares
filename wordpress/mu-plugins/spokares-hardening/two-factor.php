<?php
/**
 * Two-factor sign-in (§5.4) with the Two-Factor plugin:
 *  - providers: TOTP, Email and Backup codes only;
 *  - every new account gets the Email provider at once (no password-only window);
 *  - enforcement floor: anyone who can edit (edit_posts) with no provider is
 *    sent to their profile, and refused REST (except two-factor/1.0),
 *    admin-ajax and async-upload;
 *  - fail closed: without the plugin, non-administrators are refused wp-admin
 *    and administrators see a red notice.
 * Dev only (local + SPOKARES_DEV): enforcement, fail-closed and the
 * Email-on-register step are skipped.
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

const SPOKARES_HARD_2FA_ENABLED  = '_two_factor_enabled_providers';
const SPOKARES_HARD_2FA_PROVIDER = '_two_factor_provider';

/**
 * Is the Two-Factor plugin loaded?
 */
function spokares_hard_2fa_active(): bool {
	return class_exists( 'Two_Factor_Core' );
}

/**
 * Only TOTP, Email and Backup codes.
 *
 * @param array $providers Class name => file.
 */
function spokares_hard_2fa_providers( $providers ) {
	if ( ! is_array( $providers ) ) {
		return $providers;
	}
	return array_intersect_key( $providers, array_flip( array( 'Two_Factor_Totp', 'Two_Factor_Email', 'Two_Factor_Backup_Codes' ) ) );
}
add_filter( 'two_factor_providers', 'spokares_hard_2fa_providers', 99 );

/**
 * A new account starts with e-mailed codes on.
 *
 * @param int $user_id New user.
 */
function spokares_hard_2fa_on_register( $user_id ): void {
	if ( spokares_hard_is_dev() ) {
		return;
	}
	$enabled_key  = spokares_hard_2fa_active() && defined( 'Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY' ) ? Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY : SPOKARES_HARD_2FA_ENABLED;
	$provider_key = spokares_hard_2fa_active() && defined( 'Two_Factor_Core::PROVIDER_USER_META_KEY' ) ? Two_Factor_Core::PROVIDER_USER_META_KEY : SPOKARES_HARD_2FA_PROVIDER;
	$enabled      = get_user_meta( (int) $user_id, $enabled_key, true );
	$enabled      = is_array( $enabled ) ? $enabled : array();
	if ( ! in_array( 'Two_Factor_Email', $enabled, true ) ) {
		$enabled[] = 'Two_Factor_Email';
		update_user_meta( (int) $user_id, $enabled_key, $enabled );
	}
	if ( ! get_user_meta( (int) $user_id, $provider_key, true ) ) {
		update_user_meta( (int) $user_id, $provider_key, 'Two_Factor_Email' );
	}
}
add_action( 'user_register', 'spokares_hard_2fa_on_register' );

/**
 * Does this logged-in user need two-factor set up before doing anything?
 */
function spokares_hard_2fa_missing(): bool {
	if ( spokares_hard_is_dev() || ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) || ! spokares_hard_2fa_active() ) {
		return false;
	}
	return ! Two_Factor_Core::is_user_using_two_factor( get_current_user_id() );
}

/**
 * The fail-closed notice text.
 */
function spokares_hard_2fa_off_text(): string {
	return __( 'Sign-in protection is off. Contact the webmaster.', 'spokares-hardening' );
}

/**
 * wp-admin: without the plugin, refuse non-administrators; without a
 * provider, send the user to set one up.
 */
function spokares_hard_2fa_admin(): void {
	if ( spokares_hard_is_dev() || ! is_user_logged_in() ) {
		return;
	}
	$ajax = wp_doing_ajax();
	if ( ! spokares_hard_2fa_active() ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( spokares_hard_2fa_off_text() ), '', array( 'response' => 403 ) );
		}
		return;
	}
	if ( ! spokares_hard_2fa_missing() ) {
		return;
	}
	global $pagenow;
	if ( $ajax || 'async-upload.php' === $pagenow ) {
		// phpcs:disable WordPress.Security.NonceVerification -- only the action name is read, to allow Two-Factor's own requests.
		$raw    = $_POST['action'] ?? ( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised on the next line.
		$action = is_string( $raw ) ? sanitize_key( wp_unslash( $raw ) ) : '';
		// phpcs:enable
		if ( str_starts_with( $action, 'two_factor' ) || str_starts_with( $action, 'two-factor' ) ) {
			return;
		}
		wp_die( esc_html__( 'Set up two-factor sign-in on your profile first.', 'spokares-hardening' ), '', array( 'response' => 403 ) );
	}
	// Only their own profile, where two-factor is set up.
	if ( 'profile.php' === $pagenow ) {
		return;
	}
	wp_safe_redirect( admin_url( 'profile.php#two-factor-options' ) );
	exit;
}
add_action( 'admin_init', 'spokares_hard_2fa_admin', 1 );

/**
 * REST: refuse users without a provider, except the Two-Factor routes.
 *
 * @param WP_Error|null|true $result Earlier result.
 */
function spokares_hard_2fa_rest( $result ) {
	if ( null !== $result || ! spokares_hard_2fa_missing() ) {
		return $result;
	}
	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
	if ( str_starts_with( '/' . ltrim( $route, '/' ), '/two-factor/1.0' ) ) {
		return $result;
	}
	return new WP_Error( 'spokares_2fa_required', __( 'Set up two-factor sign-in on your profile first.', 'spokares-hardening' ), array( 'status' => 403 ) );
}
add_filter( 'rest_authentication_errors', 'spokares_hard_2fa_rest', 60 );

/**
 * Administrators see a red notice on every screen while the plugin is off.
 */
function spokares_hard_2fa_notice(): void {
	if ( spokares_hard_is_dev() || spokares_hard_2fa_active() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>' . esc_html( spokares_hard_2fa_off_text() ) . '</strong> ' . esc_html__( 'The Two-Factor plugin is not active, so editors are locked out of wp-admin until it is.', 'spokares-hardening' ) . '</p></div>';
}
add_action( 'admin_notices', 'spokares_hard_2fa_notice' );
