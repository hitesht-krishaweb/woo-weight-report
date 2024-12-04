<?php
/**
 * Plugin Name: Woo Order Weight Report
 * Description: This is custom module for woocommerce order weight calculation report.
 * Version: 1.0
 * Author: Imaginate Solutions
 * License: GPL2
 * Requires Plugins: woocommerce
 * Text Domain: woo-weight-report
 * Domain Path: /languages
 *
 * @package WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WOW_REPORT_VER', '1.0' );
define( 'WOW_REPORT_ABSPATH', __DIR__ );

require_once WOW_REPORT_ABSPATH . '/vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 */
function activate_woo_weight_report() {
	// action add after activate plugin.
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_woo_weight_report() {
	// action add after deactivate plugin.
}

register_activation_hook( __FILE__, 'activate_woo_weight_report' );
register_deactivation_hook( __FILE__, 'deactivate_woo_weight_report' );


/**
 * Initilization class.
 */
function woo_weight_report_class_init() {
	if ( class_exists( 'Woo\WeightReport' ) ) {
		return new Woo\WeightReport();
	}
	return null;
}

$init = woo_weight_report_class_init();
