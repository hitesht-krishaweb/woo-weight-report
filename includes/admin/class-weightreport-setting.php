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

use Woo\WeightReport\WeightReport_PDFGenerator;
use Woo\WeightReport\Table\Woo_Order_List_Table;

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The core plugin class extend method.
 *
 * @since 1.0.0
 */
class WeightReport_Setting extends WeightReport_Admin {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'current_screen', array( $this, 'check_and_init_hooks' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'ganerate_order_table_pdf' ) );
		add_action( 'wp_ajax_change_order_paiddate', array( $this, 'handle_change_order_paiddate' ) );
		add_action( 'wp_ajax_nopriv_change_order_paiddate', array( $this, 'handle_change_order_paiddate' ) );
		add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'wc_update_custom_meta' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'wc_update_custom_meta' ) );
		add_action( 'woocommerce_order_status_cancelled_to_processing', array( $this, 'wc_update_custom_meta_blacklist' ) );
	}

	/**
	 * Checks the current admin screen and initializes hooks if it matches the target screen.
	 *
	 * This method is used to add specific actions and scripts only on the
	 * target WooCommerce admin page, identified by its screen ID.
	 *
	 * @param WP_Screen $screen The current screen object.
	 */
	public function check_and_init_hooks( $screen ) {
		// Set screen option only for your custom admin page.
		if ( 'woocommerce_page_woo-weight-report' !== $screen->id && 'woocommerce_page_woo-under-review' !== $screen->id ) {
			return;
		}
		add_action( 'load-woocommerce_page_woo-weight-report', array( $this, 'weight_report_add_screen_options' ) );
		add_action( 'load-woocommerce_page_woo-under-review', array( $this, 'weight_report_add_screen_options_review' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_order_table_script' ) );
	}

	/**
	 * Add sub menu for woo weight report.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu_setting() {
		add_submenu_page(
			'woocommerce',
			__( 'Under Review', 'woo-weight-report' ),
			__( 'Under Review', 'woo-weight-report' ),
			'manage_options',
			'woo-under-review',
			array( &$this, 'under_review_render_page' )
		);
		add_submenu_page(
			'woocommerce',
			__( 'Weight Report', 'woo-weight-report' ),
			__( 'Weight Report', 'woo-weight-report' ),
			'manage_options',
			'woo-weight-report',
			array( &$this, 'render_page' )
		);
	}

	/**
	 * Save the screen option for items per page.
	 *
	 * This function is hooked into the 'set-screen-option' filter to save
	 * the user's choice for the number of items per page. It checks if
	 * the current option is 'items_per_page_option' and, if so, returns the
	 * value cast to an integer. Otherwise, it returns the original status.
	 *
	 * @param bool|int $status The current screen option status.
	 * @param string   $option The name of the option to update.
	 * @param int      $value  The value to be saved for the option.
	 *
	 * @return int The updated value for the screen option.
	 */
	public function order_set_screen_option_setting( $status, $option, $value ) {
		return 'items_per_page' === $option ? absint( $value ) : $status;
	}

	/**
	 * Save the screen option for items per page.
	 *
	 * This function is hooked into the 'set-screen-option' filter to save
	 * the user's choice for the number of items per page. It checks if
	 * the current option is 'items_per_page_option' and, if so, returns the
	 * value cast to an integer. Otherwise, it returns the original status.
	 *
	 * @param bool|int $status The current screen option status.
	 * @param string   $option The name of the option to update.
	 * @param int      $value  The value to be saved for the option.
	 *
	 * @return int The updated value for the screen option.
	 */
	public function order_under_review_set_screen_option_setting( $status, $option, $value ) {
		return 'review_per_page' === $option ? absint( $value ) : $status;
	}

	/**
	 * Add screen option for items per page.
	 */
	public function weight_report_add_screen_options() {

		// Set screen option only for your custom admin page.
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Items per page', 'woo-weight-report' ),
				'default' => 10,
				'option'  => 'items_per_page',
			)
		);
	}

	/**
	 * Add screen option for items per page.
	 */
	public function weight_report_add_screen_options_review() {
		// Set screen option only for your custom admin page.
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Items per page', 'woo-weight-report' ),
				'default' => 10,
				'option'  => 'review_per_page',
			)
		);
	}

	/**
	 * Render HTML for woo weight report.
	 *
	 * @since 1.0.0
	 */
	public static function render_page() {

		// Check that the user has the appropriate capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( class_exists( 'Woo\WeightReport\Table\Woo_Order_List_Table' ) ) :
			$orders_table = new Woo_Order_List_Table();
			$orders_table->set_under_review( false );
			$orders_table->prepare_items();
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'Orders', 'woo-weight-report' ); ?></h2>
			<?php
			$orders_table->display();
			?>
			</div>
			<style>
				.striped>tbody tr.order-status-cancelled {
					background-color: #FF0000;
				}
				tr.order-status-cancelled .paiddate_data {
					pointer-events: none;
				}
			</style>
			<?php
		endif;
	}

	/**
	 * Render HTML for woo weight report.
	 *
	 * @since 1.0.0
	 */
	public static function under_review_render_page() {

		// Check that the user has the appropriate capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( class_exists( 'Woo\WeightReport\Table\Woo_Order_List_Table' ) ) :
			$orders_table = new Woo_Order_List_Table();
			$orders_table->set_under_review( true );
			$orders_table->prepare_items();
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'Under Review Orders', 'woo-weight-report' ); ?></h2>
			<?php
			$orders_table->display();
			?>
			</div>
			<style>
				tr.order-status-cancelled .paiddate_data {
					pointer-events: none;
				}
			</style>
			<?php
		endif;
	}

	/**
	 * Register JS in Admin.
	 *
	 * @since 1.0.0
	 */
	public static function register_order_table_script() {

		// Register the script with a handle, source URL, dependencies, version, and additional settings.
		wp_register_script(
			'sweetalert-weight',
			plugins_url( 'woo-order-weight-report/assets/js/sweetalert.min.js', ),
			array(),
			WOW_REPORT_VER,
			array( 'strategy' => 'defer' )
		);

		wp_register_script(
			'admin-table-weight',
			plugins_url( 'woo-order-weight-report/assets/js/admin.js', ),
			array( 'sweetalert-weight' ),
			WOW_REPORT_VER,
			array( 'strategy' => 'defer' )
		);

		wp_localize_script(
			'admin-table-weight',
			'changedate',
			array(
				'nonce' => wp_create_nonce( 'change_post_date_nonce' ),
			)
		);

		// Enqueue the registered script for use.
		wp_enqueue_script( 'admin-table-weight' );
	}

	/**
	 * Ganerate order table pdf.
	 *
	 * @since 1.0.0
	 */
	public function ganerate_order_table_pdf() {
		$getdata = wp_unslash( $_GET );

		$nonce = isset( $requestdata['ordernonce'] ) && ! empty( $requestdata['ordernonce'] ) ? $requestdata['ordernonce'] : '';
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'ordernonce_weight' ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'woo-weight-report' ) );
		}

		if ( class_exists( 'Woo\WeightReport\Table\Woo_Order_List_Table' )
			&& class_exists( 'Woo\WeightReport\WeightReport_PDFGenerator' )
			&& class_exists( 'GI_Certificate' )
			&& isset( $getdata['pdf'] ) && 'ganerated' === $getdata['pdf'] ) :

			require_once WP_PLUGIN_DIR . '/gi-certificate-plugin/vendor/autoload.php';

			$pdf          = new WeightReport_PDFGenerator();
			$orders_table = new Woo_Order_List_Table();
			$orders_table->prepare_items();
			ob_start();
			?>
				<table repeat_header="1">
					<thead>
						<tr style="background-color:#e5e5e5;">
						<th><?php esc_html_e( 'Order No:', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Date Paid', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Silver Ounces', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Silver Grams', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Gold Ounces', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Gold Grams', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Platinum Ounces', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Platinum Grams', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Copper Ounces', 'woo-weight-report' ); ?></th>
						<th><?php esc_html_e( 'Copper Grams', 'woo-weight-report' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $orders_table->display_rows(); ?>
					</tbody>
				</table>
			<?php
			$html_content = ob_get_clean();
			try {
				$pdf->generate_pdf( $html_content, 'Report-' . time() . '.pdf' );
			} catch ( \Mpdf\MpdfException $e ) {
				error_log( 'PDF generation error: ' . $e->getMessage() ); //phpcs:ignore
			}

		endif;
	}

	/**
	 * Admin Date change AJAX.
	 *
	 * @since 1.0.0
	 */
	public function handle_change_order_paiddate() {
		// Check nonce for security.
		check_ajax_referer( 'change_post_date_nonce', '_ajax_nonce' );

		$postdata = wp_unslash( $_POST );

		// Sanitize and validate input.
		$orderid  = isset( $postdata['orderid'] ) ? absint( $postdata['orderid'] ) : 0;
		$new_date = isset( $postdata['new_date'] ) ? sanitize_text_field( $postdata['new_date'] ) : '';
		$new_time = isset( $postdata['new_time'] ) ? sanitize_text_field( $postdata['new_time'] ) : '';

		if ( 'wc-processing' !== get_post_status( $orderid ) ) {
			wp_send_json_error(
				array(
					'verify_status' => true,
					'message'       => sprintf(
						'Current order status is %s and has not been updated.',
						get_post_status( $orderid )
					),
				)
			);
			wp_die();
		}

		if ( $orderid && $new_date && $new_time ) {
			// Combine date and time, and try converting to timestamp.
			$datetime_string = $new_date . ' ' . $new_time;
			$timestamp       = strtotime( $datetime_string );

			// Check if the timestamp is valid.
			if ( false === $timestamp ) {
				wp_send_json_error( array( 'message' => __( 'Invalid date or time format', 'woo-weight-report' ) ) );
			}

			$order = wc_get_order( $orderid );

			if ( ! empty( $order ) ) {
				$order->set_date_paid( $datetime_string );
				$order->save();
				wp_send_json_success(
					array(
						'verify_status'   => false,
						'orderid'         => $orderid,
						'datetime_string' => $datetime_string,
					)
				);
			} else {
				wp_send_json_error( array( 'message' => __( 'Date not updated in order', 'woo-weight-report' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid input data', 'woo-weight-report' ) ) );
		}

		// Stop further execution.
		wp_die();
	}

	/**
	 * Updates the custom meta for marking an order as under review.
	 *
	 * This method updates the `_status_under_review` meta key for a specific order
	 * with the value `'yes'`. This is used to indicate that the order is currently
	 * under review.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id The ID of the order to update.
	 */
	public function wc_update_custom_meta( $order_id ) {
		update_post_meta( $order_id, '_status_under_review', 'yes' );
	}

	/**
	 * Updates the custom meta for marking an order as blacklisted.
	 *
	 * This method updates the `_order_under_blacklist` meta key for a specific order
	 * with the value `'yes'`. This is used to indicate that the order is flagged as blacklisted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id The ID of the order to update.
	 */
	public function wc_update_custom_meta_blacklist( $order_id ) {
		update_post_meta( $order_id, '_order_under_blacklist', 'yes' );
	}
}
