<?php
/**
 * WooCommerce Weight Report - Core Plugin Class
 *
 * @since      1.0.0
 *
 * @package    WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

namespace Woo\WeightReport\Testing_Order;

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Core Plugin Class - WeightReport_Testing_Order
 *
 * @since 1.0.0
 */
class WeightReport_Testing_Order {

	/**
	 * Initializes the core plugin functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'add_meta_boxes', array( $this, 'weightreport_add_test_order_meta_box' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'weightreport_save_test_order_meta_box' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'weightreport_enqueue_admin_order_script' ) );
		}
	}

	/**
	 * Adds the Test Order meta box to WooCommerce order edit pages.
	 *
	 * @since 1.0.0
	 */
	public function weightreport_add_test_order_meta_box() {
		add_meta_box(
			'weightreport_test_order_meta_box',
			__( 'Test Order', 'woo-weight-report' ),
			array( $this, 'weightreport_test_order_meta_box_callback' ),
			'shop_order',
			'side',
			'high'
		);
	}

	/**
	 * Callback function for displaying the Test Order meta box.
	 *
	 * Displays a checkbox allowing admins to mark the order as a test order.
	 *
	 * @param WP_Post $post The post object for the current WooCommerce order.
	 */
	public function weightreport_test_order_meta_box_callback( $post ) {
		$is_test_order    = get_post_meta( $post->ID, '_test_order', true );
		$testorder_status = get_post_meta( $post->ID, '_test_order_status', true );
		?>
		<label for="order_checkbox">
			<input type="hidden" name="test_order_status" value="yes" />
			<input type="checkbox" id="order_checkbox" name="order_checkbox" value="yes" <?php checked( $is_test_order, 'yes' ); ?> />
			<?php esc_html_e( 'This is a test order', 'woo-weight-report' ); ?>
		</label>
		<?php
	}

	/**
	 * Saves the Test Order meta box checkbox value.
	 *
	 * Updates the post meta for test orders when the order is saved in WooCommerce.
	 *
	 * @param int $post_id The ID of the WooCommerce order post.
	 */
	public function weightreport_save_test_order_meta_box( $post_id ) {
		if ( isset( $_POST['order_checkbox'] ) && ! empty( $_POST['order_checkbox'] ) ) { //phpcs:ignore
			update_post_meta( $post_id, '_test_order', 'yes' );
		} else {
			update_post_meta( $post_id, '_test_order', 'no' );
		}

		if ( isset( $_POST['test_order_status'] ) && ! empty( $_POST['test_order_status'] ) ) { //phpcs:ignore
			update_post_meta( $post_id, '_test_order_status', 'yes' );
		}
	}

	/**
	 * Enqueues custom JavaScript on WooCommerce order admin pages.
	 *
	 * The script is localized to expose test order status data for use in JavaScript.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function weightreport_enqueue_admin_order_script( $hook ) {
		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			global $post_type, $post;
			if ( 'shop_order' === $post_type ) {
				$_test_order  = get_post_meta( $post->ID, '_test_order', true );
				$_test_status = get_post_meta( $post->ID, '_test_order_status', true );
				wp_enqueue_script( 'admin-order-script', plugins_url( '/woo-order-weight-report/assets/js/admin-order.js' ), array( 'jquery' ), '1.0', true );
				wp_localize_script(
					'admin-order-script',
					'checkorder',
					array(
						'status' => ! empty( $_test_status ) ? $_test_status : false,
					)
				);
			}
		}
	}
}
