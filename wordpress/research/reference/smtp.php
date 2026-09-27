<?php
/**
 * Authenticated SMTP for every wp_mail() call (reference for spokares-core/includes/mail.php).
 *
 * Replaces an SMTP plugin. The credentials live only in wp-config.php, never in
 * the database (where an SQL-injection bug or a leaked DB backup would expose
 * them) and never in git:
 *
 *   define( 'SPOKARES_SMTP_HOST', 'mail.spokares.org' ); // must match the TLS certificate
 *   define( 'SPOKARES_SMTP_PORT', 587 );                 // 587 STARTTLS, or 465 implicit TLS
 *   define( 'SPOKARES_SMTP_USER', 'website@spokares.org' );
 *   define( 'SPOKARES_SMTP_PASS', '(long random password)' );
 *
 * Without the constants (local Playground, staging) mail is left alone.
 *
 * @package spokares-core
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
		$phpmailer->Host       = SPOKARES_SMTP_HOST;
		$phpmailer->Port       = $port;
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = SPOKARES_SMTP_USER;
		$phpmailer->Password   = SPOKARES_SMTP_PASS;
		$phpmailer->SMTPSecure = ( 465 === $port ) ? 'ssl' : 'tls';
		$phpmailer->Timeout    = 15;
		// Certificate checks stay on: never set SMTPOptions verify_peer to false.

		// One aligned sender for SPF/DKIM/DMARC. Third argument false keeps the
		// envelope sender (Sender) that is set on the next line.
		$phpmailer->setFrom( SPOKARES_SMTP_USER, 'Spokane County ARES-ACS website', false );
		$phpmailer->Sender = SPOKARES_SMTP_USER;
	}
);

// Log the PHPMailer error text (for example "SMTP Error: Could not authenticate.")
// but never the message body. Recipients are role addresses, not visitors.
add_action(
	'wp_mail_failed',
	static function ( $error ) {
		error_log( 'spokares-core: wp_mail failed: ' . $error->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
);
