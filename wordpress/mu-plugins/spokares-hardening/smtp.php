<?php
/**
 * Authenticated SMTP for every wp_mail() call (§5.1; from the tested
 * research/reference/smtp.php). Credentials live only in wp-config.php:
 *
 *   define( 'SPOKARES_SMTP_HOST', 'mail.spokares.org' ); // must match the TLS certificate
 *   define( 'SPOKARES_SMTP_PORT', 587 );                 // 587 STARTTLS, or 465 implicit TLS
 *   define( 'SPOKARES_SMTP_USER', 'website@spokares.org' );
 *   define( 'SPOKARES_SMTP_PASS', '(long random password)' );
 *
 * Without the constants (local Playground, staging) mail is left alone.
 *
 * @package spokares-hardening
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		if ( ! defined( 'SPOKARES_SMTP_HOST' ) || ! defined( 'SPOKARES_SMTP_USER' ) || ! defined( 'SPOKARES_SMTP_PASS' ) ) {
			return;
		}
		$port = defined( 'SPOKARES_SMTP_PORT' ) ? (int) SPOKARES_SMTP_PORT : 587;

		$phpmailer->isSMTP();
		$phpmailer->Host       = SPOKARES_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		$phpmailer->Port       = $port; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		$phpmailer->SMTPAuth   = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		$phpmailer->Username   = SPOKARES_SMTP_USER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		$phpmailer->Password   = SPOKARES_SMTP_PASS; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		$phpmailer->SMTPSecure = ( 465 === $port ) ? 'ssl' : 'tls'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		$phpmailer->Timeout    = 15; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		// Certificate checks stay on: never set SMTPOptions verify_peer to false.

		// One aligned sender for SPF/DKIM/DMARC; the envelope sender is set next.
		$phpmailer->setFrom( SPOKARES_SMTP_USER, 'Spokane County ARES-ACS website', false );
		$phpmailer->Sender = SPOKARES_SMTP_USER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
	}
);

// Log the PHPMailer error text, never the message body.
add_action(
	'wp_mail_failed',
	static function ( $error ) {
		if ( $error instanceof WP_Error ) {
			error_log( 'spokares-hardening: wp_mail failed: ' . $error->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- operator diagnostics.
		}
	}
);
