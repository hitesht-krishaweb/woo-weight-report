<?php
/**
 * The file that defines the core plugin class
 *
 * @since      1.0.0
 *
 * @package    WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

namespace Woo\WeightReport\Admin;

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The core plugin class.
 */
abstract class WeightReport_Admin {

	/**
	 * Abstract method for add new admin menu.
	 *
	 * @since 1.0.0
	 */
	abstract protected function admin_menu_setting();

	/**
	 * Abstract method for saving the screen option for the number of items displayed per page.
	 *
	 * This method should be implemented in a subclass to handle the logic of
	 * saving the user's preference for how many items are shown on the screen.
	 * It takes the current status, the option name, and the value to be saved.
	 *
	 * @param string $status The current status of the items (e.g., 'all', 'pending', 'completed').
	 * @param string $option The name of the option to be saved.
	 * @param mixed  $value The value to be saved for the specified option.
	 *
	 * @return bool True on successful saving of the option, false otherwise.
	 *
	 * @since 1.0.0
	 */
	abstract protected function order_set_screen_option_setting( $status, $option, $value );

	/**
	 * Abstract method for saving the screen option for the number of items displayed per page.
	 *
	 * This method should be implemented in a subclass to handle the logic of
	 * saving the user's preference for how many items are shown on the screen.
	 * It takes the current status, the option name, and the value to be saved.
	 *
	 * @param string $status The current status of the items (e.g., 'all', 'pending', 'completed').
	 * @param string $option The name of the option to be saved.
	 * @param mixed  $value The value to be saved for the specified option.
	 *
	 * @return bool True on successful saving of the option, false otherwise.
	 *
	 * @since 1.0.0
	 */
	abstract protected function order_under_review_set_screen_option_setting( $status, $option, $value );

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu_setting' ) );
			add_filter( 'set_screen_option_items_per_page', array( $this, 'order_set_screen_option_setting' ), 10, 3 );
			add_filter( 'set_screen_option_review_per_page', array( $this, 'order_under_review_set_screen_option_setting' ), 10, 3 );
		}
	}
}
