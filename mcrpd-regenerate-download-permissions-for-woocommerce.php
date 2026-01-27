<?php
/**
 * Plugin Name: MCRPD Regenerate Download Permissions for woocommerce
 * Requires Plugins: woocommerce
 * Description: Regenerate downloadable permissions for orders in batches.
 * Version: 1.0.0
 * Author: crleguizamon
 * Author URI: https://mcodform.com/
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * License: GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'MCRPD_VERSION', '1.0.0' );
define( 'MCRPD_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCRPD_INC', MCRPD_DIR . 'includes/' );
define( 'MCRPD_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin.
 */
function mcrpd_init() {
	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'mcrpd_missing_wc_notice' );
		return;
	}

	// Load helpers.
	require_once MCRPD_INC . 'mcrpd-helpers.php';

	// Load classes.
	require_once MCRPD_INC . 'class-mcrpd-admin.php';
	require_once MCRPD_INC . 'class-mcrpd-ajax.php';

	// Initialize classes.
	MCRPD_Admin::init();
	MCRPD_Ajax::init();
}
add_action( 'plugins_loaded', 'mcrpd_init' );

/**
 * Display missing WooCommerce notice.
 */
function mcrpd_missing_wc_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'MCRPD Regenerate Download Permissions requires WooCommerce to be installed and active.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></p>
	</div>
	<?php
}
