<?php
/**
 * The file that defines the core plugin class
 *
 * @since      1.0.0
 *
 * @package    WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

namespace Woo;

use Woo\WeightReport\Admin\WeightReport_Setting;
use Woo\WeightReport\Testing_Order\WeightReport_Testing_Order;

/**
 * The core plugin class.
 */
class WeightReport {
	/**
	 * Plugin initialization construction method.
	 *
	 * @since      1.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_loaded_hook' ) );
	}

	/**
	 * Initiate all class Hook.
	 *
	 * @since      1.0.0
	 */
	public function init_loaded_hook() {
		load_plugin_textdomain(
			'woo-weight-report',
			false,
			basename( WOW_REPORT_ABSPATH ) . '/languages'
		);
		new WeightReport_Setting();
		// new WeightReport_Testing_Order();
	}
}
