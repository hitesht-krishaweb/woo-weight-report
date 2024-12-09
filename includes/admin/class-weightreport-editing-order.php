<?php
/**
 * WooCommerce Weight Report - Core Plugin Class
 *
 * Handles custom functionalities for WooCommerce Order Editing pages.
 *
 * @since      1.0.0
 * @package    WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

namespace Woo\WeightReport\Editing_Order;

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WeightReport_Editing_Order
 *
 * This class provides functionalities like displaying custom notices and handling admin actions
 * for orders marked as "under review."
 *
 * @since 1.0.0
 */
class WeightReport_Editing_Order {

	/**
	 * Initializes the core plugin functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Add admin actions only for users with 'manage_options' capability.
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'add_meta_boxes', array( $this, 'weightreport_add_under_review_meta_box' ) );
			add_action( 'admin_notices', array( $this, 'add_custom_notice_and_button' ) );
			add_action( 'wp_ajax_handle_custom_admin_action', array( $this, 'handle_custom_admin_action' ) );
		}
	}

	/**
	 * Adds the Test Order meta box to WooCommerce order edit pages.
	 *
	 * @since 1.0.0
	 */
	public function weightreport_add_under_review_meta_box() {
		add_meta_box(
			'weightreport_under_review_meta_box',
			__( 'Under Review', 'woo-weight-report' ),
			array( $this, 'weightreport_under_review_meta_box_callback' ),
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
	public function weightreport_under_review_meta_box_callback( $post ) {
		$is_under_review = get_post_meta( $post->ID, '_status_under_review', true );
		?>
		<label for="order_checkbox">
			<input type="checkbox" id="order_checkbox" name="review_status" value="<?php echo esc_attr( $is_under_review ); ?>" <?php checked( $is_under_review, 'yes' ); ?> />
			<?php
			esc_html_e( 'This order is a under review.', 'woo-weight-report' );
			?>
		</label>
		<p>
		<?php
		submit_button( __( 'Submit', 'woo-weight-report' ), 'primary', 'submit-form', false );
		echo '</p>';
	}

	/**
	 * Displays a custom admin notice and action button on WooCommerce order edit pages.
	 *
	 * This function adds a notice when an order is under review (based on meta key `_status_under_review`)
	 * and provides a button to perform an admin action.
	 *
	 * @since 1.0.0
	 */
	public function add_custom_notice_and_button() {
		$screen = get_current_screen();

		// Display the notice only on the WooCommerce Order Edit screen.
		if ( $screen && 'shop_order' === $screen->id ) {
			$order_id        = get_the_ID(); // Get the current order ID from the URL.
			$post_order_data = get_post_meta( $order_id, '_status_under_review', true );

			// Show the notice only if the order is under review.
			if ( 'yes' !== $post_order_data ) {
				return;
			}
			?>
			<div class="notice notice-info is-dismissible woo-reviewaction">
				<p>
					<?php
					/* translators: %d: Order ID */
					printf( esc_html__( 'This order #%d is under review.', 'woo-weight-report' ), esc_html( $order_id ) );
					?>
				</p>
				<p>
					<a href="#" class="button button-primary" id="review-action" data-order-id="<?php echo esc_attr( $order_id ); ?>">
						<?php esc_html_e( 'Accept', 'woo-weight-report' ); ?>
					</a>
				</p>
			</div>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#review-action').on('click', function(e) {
						e.preventDefault();

						const orderId = $(this).data('order-id');

						// AJAX request to perform the action.
						$.ajax({
							url: ajaxurl,
							method: 'POST',
							data: {
								action: 'handle_custom_admin_action',
								order_id: orderId
							},
							success: function(response) {
								alert(response.data.message);
								location.reload(); // Reload the page after success.
							},
							error: function() {
								alert('<?php esc_js( __( 'Something went wrong!', 'woo-weight-report' ) ); ?>');
							}
						});
					});
				});
			</script>
			<?php
		}
	}

	/**
	 * Handles the AJAX action for the custom admin button.
	 *
	 * This function processes the custom action triggered by the button click,
	 * updates the order meta to mark it as no longer under review, and sends a response.
	 *
	 * @since 1.0.0
	 */
	public function handle_custom_admin_action() {
		// Verify user permissions.
		if ( ! current_user_can( 'manage_woocommerce' ) ) { //phpcs:ignore
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'woo-weight-report' ) ), 403 );
		}

		$postdata = wp_unslash( $_POST ); //phpcs:ignore

		// Get the order ID from the AJAX request.
		$order_id = isset( $postdata['order_id'] ) ? intval( $postdata['order_id'] ) : 0;

		// Validate and process the order ID.
		if ( $order_id ) {
			update_post_meta( $order_id, '_status_under_review', 'no' );

			// Perform the custom action (e.g., update order meta).
			wp_send_json_success(
				array(
					/* translators: %d: Order ID */
					'message' => sprintf( esc_html__( 'Action successfully performed on Order #%d', 'woo-weight-report' ), $order_id ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid order ID', 'woo-weight-report' ) ) );
		}
	}
}
