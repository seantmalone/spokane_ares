<?php
/**
 * DEV ONLY (PLAN.md §6.10, blueprint step 5). Writes two one-line loaders into
 * the Playground site's wp-content/mu-plugins/ so the must-use code stays live
 * from the mounted repo folders:
 *
 *   spokares-dev-login.php  -> /spokares-dev/mu-plugins/spokares-dev-login.php
 *   spokares-hardening.php  -> /spokares-mu/spokares-hardening.php (if present)
 *
 * Runs without WordPress loaded (the blueprint defines SPOKARES_DEV_SETUP).
 *
 * @package spokares-dev
 */

defined( 'ABSPATH' ) || defined( 'SPOKARES_DEV_SETUP' ) || exit;

$spokares_dev_mu_dir = '/wordpress/wp-content/mu-plugins';
if ( ! is_dir( $spokares_dev_mu_dir ) ) {
	mkdir( $spokares_dev_mu_dir, 0755, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- dev setup, WordPress not loaded.
}

$spokares_dev_loaders = array(
	'spokares-dev-login.php' => "<?php\n// DEV ONLY loader written by wordpress/dev/setup/mu.php.\nif ( is_readable( '/spokares-dev/mu-plugins/spokares-dev-login.php' ) ) {\n\trequire '/spokares-dev/mu-plugins/spokares-dev-login.php';\n}\n",
	'spokares-hardening.php' => "<?php\n// DEV ONLY loader written by wordpress/dev/setup/mu.php (production copies the real files into mu-plugins/).\nif ( is_readable( '/spokares-mu/spokares-hardening.php' ) ) {\n\trequire '/spokares-mu/spokares-hardening.php';\n}\n",
);

$spokares_dev_log = array();
foreach ( $spokares_dev_loaders as $spokares_dev_name => $spokares_dev_code ) {
	file_put_contents( $spokares_dev_mu_dir . '/' . $spokares_dev_name, $spokares_dev_code ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- dev setup.
	$spokares_dev_log[] = 'mu.php: wrote loader ' . $spokares_dev_name;
}
if ( ! is_readable( '/spokares-mu/spokares-hardening.php' ) ) {
	$spokares_dev_log[] = 'mu.php: SKIP /spokares-mu/spokares-hardening.php not found (hardening not loaded)';
}

if ( is_dir( '/spokares-log' ) ) {
	file_put_contents( '/spokares-log/setup.log', implode( "\n", $spokares_dev_log ) . "\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- dev setup.
}
