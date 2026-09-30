<?php
/**
 * Plugin Name: WooCommerce ERP
 * Plugin URI:  https://getproservices.net/wordpress-erp
 * Description: Modular enterprise ERP for WooCommerce — inventory, purchasing, multi-warehouse, double-entry accounting, POS, courier & COD, HR/payroll, VAT/Mushak (Bangladesh-ready) and full financial reports. Developed by Get Pro Services.
 * Version:     1.96.0
 * Author:      Get Pro Services
 * Author URI:  https://getproservices.net
 * Text Domain: wc-enterprise-erp
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * License: Commercial. See license.txt
 */

defined( 'ABSPATH' ) || exit;

define( 'WCEERP_VERSION', '1.96.0' );
define( 'WCEERP_FILE', __FILE__ );
define( 'WCEERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCEERP_URL', plugin_dir_url( __FILE__ ) );
define( 'WCEERP_MIN_PHP', '7.4' );

// HPOS compatibility.
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

require_once WCEERP_PATH . 'includes/helpers.php';
require_once WCEERP_PATH . 'includes/class-erp-installer.php';
require_once WCEERP_PATH . 'includes/class-erp-license.php';
require_once WCEERP_PATH . 'includes/class-erp-invoice-settings.php';
require_once WCEERP_PATH . 'includes/class-erp-order-payments.php';
require_once WCEERP_PATH . 'includes/class-erp-reset.php';
require_once WCEERP_PATH . 'includes/class-erp-backup.php';
require_once WCEERP_PATH . 'includes/class-erp-health.php';
require_once WCEERP_PATH . 'includes/class-erp-messaging.php';
require_once WCEERP_PATH . 'includes/class-erp-document.php';
require_once WCEERP_PATH . 'includes/class-erp-payout-import.php';
require_once WCEERP_PATH . 'includes/class-erp-updater.php';
require_once WCEERP_PATH . 'includes/class-erp-pos-service.php';
require_once WCEERP_PATH . 'includes/class-erp-api-auth.php';
require_once WCEERP_PATH . 'includes/class-erp-public-api.php';
require_once WCEERP_PATH . 'includes/class-erp-vault.php';
require_once WCEERP_PATH . 'includes/class-erp-rules-client.php';
require_once WCEERP_PATH . 'includes/class-erp-limits.php';
require_once WCEERP_PATH . 'includes/class-erp-pin.php';
require_once WCEERP_PATH . 'includes/class-erp-security.php';
require_once WCEERP_PATH . 'includes/class-erp-2fa.php';
require_once WCEERP_PATH . 'includes/class-erp-portal.php';
require_once WCEERP_PATH . 'includes/class-erp-pwa.php';
require_once WCEERP_PATH . 'includes/class-erp-capabilities.php';
require_once WCEERP_PATH . 'includes/abstracts/class-erp-module.php';
require_once WCEERP_PATH . 'includes/class-erp-core.php';

register_activation_hook( __FILE__, array( 'WCEERP_Installer', 'activate' ) );

/**
 * Run the installer whenever the stored schema version falls behind the
 * plugin version. dbDelta is idempotent and every migration is additive,
 * so this is safe to run on any upgrade -- and it means customers no
 * longer have to deactivate and reactivate to get new tables or columns.
 */
add_action( 'plugins_loaded', function () {
	if ( get_option( 'wceerp_version' ) === WCEERP_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WCEERP_Installer' ) ) {
		return;
	}
	WCEERP_Installer::activate();
}, 5 );
add_action( 'wp_initialize_site', array( 'WCEERP_Installer', 'on_new_site' ), 20 );
if ( is_multisite() ) {
	require_once __DIR__ . '/includes/class-erp-network.php';
	WCEERP_Network::boot();
}
register_deactivation_hook( __FILE__, array( 'WCEERP_Installer', 'deactivate' ) );

/**
 * Main accessor.
 */
function wceerp() {
	return WCEERP_Core::instance();
}

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wc-enterprise-erp', false, dirname( plugin_basename( WCEERP_FILE ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce ERP requires WooCommerce to be installed and active.', 'wc-enterprise-erp' ) . '</p></div>';
		} );
		return;
	}
	wceerp()->boot();
	WCEERP_Pin::boot();
	WCEERP_Security::boot();
	WCEERP_TwoFactor::boot();
	WCEERP_Portal::boot();
	WCEERP_PWA::boot();
	if ( wceerp_get_option( 'rules_mode', 'local' ) === 'remote' ) {
		WCEERP_Rules_Client::init();
		WCEERP_Public_API::init();
		WCEERP_Updater::init();
		WCEERP_Messaging::init();
		WCEERP_Document::init();
	}
}, 20 );
