<?php
/**
 * Two-factor sign-in (§5.4) with the Two-Factor plugin:
 *  - providers: TOTP, Email and Backup codes only;
 *  - every new account gets the Email provider at once (no password-only window);
 *  - enforcement floor: anyone who can edit (edit_posts, or the site's own
 *    rota and Net details capabilities) with no provider is sent to their
 *    profile, which says why, and refused REST (except two-factor/1.0 and
 *    reading their own account), admin-ajax (except the profile screen's
 *    own requests) and async-upload;
 *  - fail closed: without the plugin, non-administrators are refused wp-admin
 *    and administrators see a red notice that says how to fix it.
 *  - plain words (UX spec §3.9): once the phone app is set up it is the
 *    primary method, editors don't see the Primary Method row, and the
 *    plugin's sign-in wording says "the code from my phone app", "e-mail me
 *    a code" and "a printed backup code".
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
	if ( spokares_hard_is_dev() || ! is_user_logged_in() || ! spokares_hard_2fa_can_change_site() || ! spokares_hard_2fa_active() ) {
		return false;
	}
	return ! Two_Factor_Core::is_user_using_two_factor( get_current_user_id() );
}

/**
 * Can the signed-in user change anything the public sees? Anyone who can
 * edit posts, and anyone with the site's own editing capabilities (the rota,
 * Net details and Meeting rules), whatever their role says.
 */
function spokares_hard_2fa_can_change_site(): bool {
	return current_user_can( 'edit_posts' ) || current_user_can( 'spokares_edit_rota' ) || current_user_can( 'spokares_edit_net_details' );
}

/**
 * The fail-closed text editors see (PLAN §5.4).
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
		// Two-Factor's own requests, and the profile screen's keep-alive and
		// compression test (which only answer about the session itself).
		if ( str_starts_with( $action, 'two_factor' ) || str_starts_with( $action, 'two-factor' ) || in_array( $action, array( 'heartbeat', 'wp-compression-test' ), true ) ) {
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
 * REST: refuse users without a provider, except the Two-Factor routes and
 * reading their own account.
 *
 * @param WP_Error|null|true $result Earlier result.
 */
function spokares_hard_2fa_rest( $result ) {
	if ( null !== $result || ! spokares_hard_2fa_missing() ) {
		return $result;
	}
	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? '/' . ltrim( (string) $GLOBALS['wp']->query_vars['rest_route'], '/' ) : '';
	if ( str_starts_with( $route, '/two-factor/1.0' ) ) {
		return $result;
	}
	// The profile screen reads the user's own account (GET /wp/v2/users/me);
	// refusing it threw an uncaught error in the page. A method override
	// (?_method=, X-HTTP-Method-Override) would make it a write, so not then.
	$method   = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	$override = isset( $_GET['_method'] ) || isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only checks whether an override is present.
	if ( '/wp/v2/users/me' === untrailingslashit( $route ) && in_array( $method, array( 'GET', 'HEAD' ), true ) && ! $override ) {
		return $result;
	}
	return new WP_Error( 'spokares_2fa_required', __( 'Set up two-factor sign-in on your profile first.', 'spokares-hardening' ), array( 'status' => 403 ) );
}
add_filter( 'rest_authentication_errors', 'spokares_hard_2fa_rest', 60 );

/**
 * Administrators see a red notice on every screen while the plugin is off.
 * They are the webmaster, so it says what to do, with a link.
 */
function spokares_hard_2fa_notice(): void {
	if ( spokares_hard_is_dev() || spokares_hard_2fa_active() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Sign-in protection is off.', 'spokares-hardening' ) . '</strong> '
		. esc_html__( 'The Two Factor plugin is not active, so editors can’t use wp-admin until it is.', 'spokares-hardening' ) . ' ';
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Reactivate Two Factor on the Plugins screen.', 'spokares-hardening' ) . '</a>';
	} else {
		esc_html_e( 'Reactivate Two Factor on the Plugins screen.', 'spokares-hardening' );
	}
	echo '</p></div>';
}
add_action( 'admin_notices', 'spokares_hard_2fa_notice' );

/**
 * On the profile screen a user without two-factor is sent to, say why the
 * rest of wp-admin is closed to them for now.
 */
function spokares_hard_2fa_setup_notice(): void {
	global $pagenow;
	if ( 'profile.php' !== $pagenow || ! spokares_hard_2fa_missing() ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Set up two-factor sign-in first.', 'spokares-hardening' ) . '</strong> '
		. esc_html__( 'Everyone who can change the website signs in with a code as well as a password. Choose a method under Two-Factor Options below and click Update Profile; then the Dashboard opens.', 'spokares-hardening' )
		. '</p></div>';
}
add_action( 'admin_notices', 'spokares_hard_2fa_setup_notice' );

/**
 * Once the phone app (TOTP) is set up it is the method asked for first at
 * sign-in: always for editors (they don't see the Primary Method row), and
 * for an administrator who hasn't picked one that is still on.
 *
 * @param string $provider Provider key the plugin chose.
 * @param int    $user_id  User.
 */
function spokares_hard_2fa_totp_first( $provider, $user_id ) {
	if ( ! spokares_hard_2fa_active() || 'Two_Factor_Totp' === $provider ) {
		return $provider;
	}
	$available = Two_Factor_Core::get_available_providers_for_user( (int) $user_id );
	if ( ! is_array( $available ) || ! isset( $available['Two_Factor_Totp'] ) ) {
		return $provider;
	}
	$chosen = (string) get_user_meta( (int) $user_id, SPOKARES_HARD_2FA_PROVIDER, true );
	if ( ! user_can( (int) $user_id, 'manage_options' ) || ! isset( $available[ $chosen ] ) ) {
		return 'Two_Factor_Totp';
	}
	return $provider;
}
add_filter( 'two_factor_primary_provider_for_user', 'spokares_hard_2fa_totp_first', 10, 2 );

/**
 * The moment the phone app is set up (its secret key is stored, from the
 * profile's Verify), it becomes the stored primary method too, so an
 * administrator's Primary Method row shows it.
 *
 * @param int    $meta_id  Meta row.
 * @param int    $user_id  User.
 * @param string $meta_key Meta key.
 */
function spokares_hard_2fa_totp_primary( $meta_id, $user_id, $meta_key ): void {
	if ( '_two_factor_totp_key' === $meta_key ) {
		update_user_meta( (int) $user_id, SPOKARES_HARD_2FA_PROVIDER, 'Two_Factor_Totp' );
	}
}
add_action( 'added_user_meta', 'spokares_hard_2fa_totp_primary', 10, 3 );
add_action( 'updated_user_meta', 'spokares_hard_2fa_totp_primary', 10, 3 );

/**
 * No Primary Method row on an editor's profile: the phone app is primary
 * once it is set up (above), so there is nothing to choose.
 */
function spokares_hard_2fa_hide_primary(): void {
	global $pagenow;
	if ( ! in_array( $pagenow, array( 'profile.php', 'user-edit.php' ), true ) || current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<style id="spokares-2fa-primary">.two-factor-primary-method-table,hr:has(+ .two-factor-primary-method-table){display:none}</style>' . "\n";
}
add_action( 'admin_head', 'spokares_hard_2fa_hide_primary' );

/**
 * The Two-Factor plugin's words, in plain English (its own text domain only;
 * the source strings are Two-Factor 0.16's).
 *
 * @param string $translation Translated text.
 * @param string $text        Source text.
 */
function spokares_hard_2fa_words( $translation, $text ) {
	static $words = null;
	if ( null === $words ) {
		$code  = __( 'Code:', 'spokares-hardening' );
		$retry = __( 'That code didn’t work. Wait for the next code in the app, then type it.', 'spokares-hardening' );
		$words = array(
			'Use your authenticator app for time-based one-time passwords (TOTP)' => __( 'Use the code from my phone app', 'spokares-hardening' ),
			'Send a code to your email'         => __( 'E-mail me a code', 'spokares-hardening' ),
			'Use a recovery code'               => __( 'Use a printed backup code', 'spokares-hardening' ),
			'Authentication Code:'              => $code,
			'Verification Code:'                => $code,
			'Recovery Code:'                    => $code,
			// One wrong code: the plugin's refusal, and its short wait before the next try.
			'ERROR: Invalid verification code.' => $retry,
			'ERROR: Too many invalid verification codes, you can try again in %s. This limit protects your account against automated attacks.' => $retry,
		);
	}
	return $words[ $text ] ?? $translation;
}
add_filter( 'gettext_two-factor', 'spokares_hard_2fa_words', 10, 2 );
