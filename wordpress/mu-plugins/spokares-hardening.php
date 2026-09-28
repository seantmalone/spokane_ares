<?php
/**
 * Plugin Name:       Spokane ARES hardening
 * Description:       Security floor for spokares.org: XML-RPC and enumeration closed, security headers, upload checks, two-factor enforcement, authenticated SMTP and the old-URL map. Must-use, so it can't be switched off from wp-admin.
 * Version:           0.2.0
 * Requires at least: 7.1
 * Requires PHP:      8.3
 * Author:            Spokane County ARES-ACS
 * License:           GPL-2.0-or-later
 * Text Domain:       spokares-hardening
 *
 * Works with the theme and spokares-core absent or inactive, and never
 * fatals if the Two-Factor plugin is missing (it fails closed instead).
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

define( 'SPOKARES_HARDENING_VERSION', '0.2.0' );

/**
 * True only in the local dev environment (environment type `local` AND the
 * SPOKARES_DEV constant). The two-factor skips go through this.
 */
function spokares_hard_is_dev(): bool {
	return 'local' === wp_get_environment_type() && defined( 'SPOKARES_DEV' ) && SPOKARES_DEV;
}

foreach ( array( 'hardening', 'enumeration', 'content-surface', 'headers', 'uploads', 'two-factor', 'smtp', 'redirects' ) as $spokares_hard_module ) {
	$spokares_hard_file = __DIR__ . '/spokares-hardening/' . $spokares_hard_module . '.php';
	if ( is_readable( $spokares_hard_file ) ) {
		require $spokares_hard_file;
	}
}
unset( $spokares_hard_module, $spokares_hard_file );
