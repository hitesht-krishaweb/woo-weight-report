<?php
/**
 * WooCommerce Order Weight Report Table Class
 *
 * This file defines the custom WP_List_Table class for displaying
 * WooCommerce order data, including custom weight fields like silver, gold,
 * platinum, and copper in ounces and grams.
 *
 * @since      1.0.0
 * @package    WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

namespace Woo\WeightReport\Table;

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include WP_List_Table class if not already included.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Woo_Order_List_Table
 *
 * Custom WP_List_Table for displaying WooCommerce order data.
 *
 * @since 1.0.0
 */
class Woo_Order_List_Table extends \WP_List_Table {

	/**
	 * Total weight of silver in ounces.
	 *
	 * @var float
	 */
	protected $total_silver_oz = 0;

	/**
	 * Total weight of silver in grams.
	 *
	 * @var float
	 */
	protected $total_silver_gram = 0;

	/**
	 * Total weight of gold in ounces.
	 *
	 * @var float
	 */
	protected $total_gold_oz = 0;

	/**
	 * Total weight of gold in grams.
	 *
	 * @var float
	 */
	protected $total_gold_gram = 0;

	/**
	 * Total weight of platinum in ounces.
	 *
	 * @var float
	 */
	protected $total_platinum_oz = 0;

	/**
	 * Total weight of platinum in grams.
	 *
	 * @var float
	 */
	protected $total_platinum_gram = 0;

	/**
	 * Total weight of copper in ounces.
	 *
	 * @var float
	 */
	protected $total_copper_oz = 0;

	/**
	 * Total weight of copper in gram.
	 *
	 * @var float
	 */
	protected $total_copper_gram = 0;

	/**
	 * Under View varibale.
	 *
	 * @var float
	 */
	private $under_review = false;


	/**
	 * Define the column headers for the list table.
	 *
	 * @return array Column headers.
	 */
	public function get_columns() {

		$getdata = wp_unslash( $_GET );
		$nonce   = isset( $getdata['ordernonce'] ) && ! empty( $getdata['ordernonce'] ) ? $getdata['ordernonce'] : '';
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'ordernonce_weight' ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'woo-weight-report' ) );
		}

		$columns = array(
			'orderid'       => esc_html__( 'Order No:', 'woo-weight-report' ),
			'paiddate'      => esc_html__( 'Date Paid', 'woo-weight-report' ),
			'silver-oz'     => esc_html__( 'Silver Ounces', 'woo-weight-report' ),
			'silver-gram'   => esc_html__( 'Silver Grams', 'woo-weight-report' ),
			'gold-oz'       => esc_html__( 'Gold Ounces', 'woo-weight-report' ),
			'gold-gram'     => esc_html__( 'Gold Grams', 'woo-weight-report' ),
			'platinum-oz'   => esc_html__( 'Platinum Ounces', 'woo-weight-report' ),
			'platinum-gram' => esc_html__( 'Platinum Grams', 'woo-weight-report' ),
			'copper-oz'     => esc_html__( 'Copper Ounces', 'woo-weight-report' ),
			'copper-gram'   => esc_html__( 'Copper Grams', 'woo-weight-report' ),
		);

		if ( ! isset( $getdata['pdf'] ) && ! array_key_exists( 'pdf', $getdata ) ) :
			$columns['product'] = sprintf(
				'%s<button class="button toggle-view-all">%s</button>',
				esc_html__( 'Product', 'woo-weight-report' ),
				esc_html__( 'View All', 'woo-weight-report' )
			);
		endif;

		return $columns;
	}

	/**
	 * Sets the under-review status.
	 *
	 * This method sets the `under_review` property to the specified value.
	 * Use this to mark an object as being under review.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $status Optional. The status to set. Defaults to false.
	 */
	public function set_under_review( $status = false ) {
		$this->under_review = $status; // Save the value to a class property.
	}

	/**
	 * Retrieves the under-review status.
	 *
	 * This method returns the value of the `under_review` property.
	 * Use this to check if an object is marked as being under review.
	 *
	 * @since 1.0.0
	 *
	 * @return bool The current under-review status.
	 */
	public function get_under_review() {
		return $this->under_review; // Retrieve the current value of the property.
	}


	/**
	 * Define which columns can be sorted.
	 *
	 * @return array Sortable columns.
	 */
	protected function get_sortable_columns() {
		return array(
			'paiddate' => array( 'paiddate', true ), // Sort by Order ID.
		);
	}

	/**
	 * Prepares the data for display in the table.
	 */
	public function prepare_items() {

		// Reset the total weights for all metals.
		$this->total_silver_oz     = 0;
		$this->total_silver_gram   = 0;
		$this->total_gold_oz       = 0;
		$this->total_gold_gram     = 0;
		$this->total_platinum_oz   = 0;
		$this->total_platinum_gram = 0;
		$this->total_copper_oz     = 0;
		$this->total_copper_gram   = 0;

		// Define the number of items to display per page.
		$per_page_option = $this->get_under_review() ? 'review_per_page' : 'items_per_page';
		$per_page        = $this->get_items_per_page( $per_page_option, 20 );

		$current_page = $this->get_pagenum();
		$total_items  = $this->get_total_items_count();

		// Prepare the columns and sorting arguments.
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, array(), $sortable );

		// Pagination args.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // Total number of items.
				'per_page'    => $per_page,    // Number of items per page.
			)
		);

		// Fetch data according to pagination.
		$data = $this->get_order_data( $per_page, $current_page );

		usort( $data, array( &$this, 'usort_reorder' ) );

		$this->items = $data;
	}

	/**
	 * Get total number of items (orders).
	 */
	public function get_total_items_count() {
		$current_page = $this->get_pagenum();

		if ( true !== $this->get_under_review() ) :
			$args = $this->get_query_by_filter( -1, $current_page );
		else :
			$args = $this->get_query_for_review( -1, $current_page );
		endif;
		$args['fields'] = 'ids';

		$orders = get_posts( $args );
		return count( $orders );
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @param object|array $item The current item.
	 */
	public function single_row( $item ) {

		$getdata = wp_unslash( $_GET );
		$nonce   = isset( $getdata['ordernonce'] ) && ! empty( $getdata['ordernonce'] ) ? $getdata['ordernonce'] : '';
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'ordernonce_weight' ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'woo-weight-report' ) );
		}

		$order_data = wc_get_order( $item['orderid'] );

		// Test order status variable.
		$_test_order  = get_post_meta( $item['orderid'], '_test_order', true );
		$order_status = $order_data->get_status();

		if ( ! isset( $getdata['pdf'] ) || 'yes' !== $_test_order ) :
			echo '<tr class="order-status-' . esc_attr( $order_status ) . '">';
			$this->single_row_columns( $item );
			echo '</tr>';
		endif;

		if ( ! isset( $getdata['pdf'] ) ) :

			echo '<tr class="table-view-pro hidden" id="table-view-' . esc_attr( $item['orderid'] ) . '" >';
			if ( ! empty( $order_data ) ) :
				?>
				<td colspan="<?php echo esc_attr( $this->get_column_count() ); ?>" style="padding-bottom:10px;">
					<table class="striped" style="width: 100%; border-collapse: collapse;border: 2px solid #f6f7f7;">
						<thead>
							<tr>
								<th style="width: 25%;"><?php esc_html_e( 'Name', 'woo-weight-report' ); ?></th>
								<th style="width: 25%;"><?php esc_html_e( 'SKU', 'woo-weight-report' ); ?></th>
								<th style="width: 25%;"><?php esc_html_e( 'Quantity', 'woo-weight-report' ); ?></th>
								<th style="width: 25%;"><?php esc_html_e( 'Weight', 'woo-weight-report' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $order_data->get_items() as $product ) :
								$product_id   = $product->get_product_id();
								$weight_value = get_post_meta( $product_id, '_custom_weight_value', true );
								$edit_link    = sprintf(
									'<a target="_blank" href="%s">%s</a>',
									esc_url( get_edit_post_link( $product_id ) ),
									esc_html( $product->get_name() )
								);
								?>
								<tr>
									<td><?php echo wp_kses_post( $edit_link ); ?></td>
									<td>
										<?php
										if ( $product && $product->get_product() ) {
											echo esc_html( $product->get_product()->get_sku() );
										}
										?>
									</td>
									<td><?php echo esc_html( $product->get_quantity() ); ?></td>
									<td><?php echo esc_html( $weight_value ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</td>
				<?php
			endif;
			echo '</tr>';

		endif;
	}

	/**
	 * Retrieves WooCommerce order data with pagination.
	 *
	 * @param int $per_page     Items per page.
	 * @param int $current_page Current page number.
	 * @return array Order data formatted for the list table.
	 */
	public function get_order_data( $per_page = 10, $current_page = 1 ) {

		if ( true !== $this->get_under_review() ) :
			$args = $this->get_query_by_filter( $per_page, $current_page );
		else :
			$args = $this->get_query_for_review( $per_page, $current_page );
		endif;

		$orders = get_posts( $args );
		$data   = array();

		$requestdata = wp_unslash( $_GET ); //phpcs:ignore

		// Loop through the orders and format the data.
		foreach ( $orders as $order ) {
			$order_data     = wc_get_order( $order->ID );
			$product_weight = $this->order_weight_by_product( $order_data );
			$weight_lable   = $this->get_metalname_by_lang();

			// Test order status variable.
			$_test_order  = get_post_meta( $order->ID, '_test_order', true );
			$order_status = $order_data->get_status();

			$silver_oz     = $this->get_weight_by_metal( $weight_lable['silver'], 'ounces', $product_weight );
			$silver_gram   = $this->get_weight_by_metal( $weight_lable['silver'], 'grams', $product_weight );
			$gold_oz       = $this->get_weight_by_metal( $weight_lable['gold'], 'ounces', $product_weight );
			$gold_gram     = $this->get_weight_by_metal( $weight_lable['gold'], 'grams', $product_weight );
			$platinum_oz   = $this->get_weight_by_metal( $weight_lable['platinum'], 'ounces', $product_weight );
			$platinum_gram = $this->get_weight_by_metal( $weight_lable['platinum'], 'grams', $product_weight );
			$copper_oz     = $this->get_weight_by_metal( $weight_lable['copper'], 'ounces', $product_weight );
			$copper_gram   = $this->get_weight_by_metal( $weight_lable['copper'], 'grams', $product_weight );

			if ( 'yes' !== $_test_order && ( 'processing' === $order_status || 'cancelled' === $order_status ) ) :
				// Accumulate the total weights for each metal in both ounces and grams.
				$this->total_silver_oz     += $silver_oz;
				$this->total_silver_gram   += $silver_gram;
				$this->total_gold_oz       += $gold_oz;
				$this->total_gold_gram     += $gold_gram;
				$this->total_platinum_oz   += $platinum_oz;
				$this->total_platinum_gram += $platinum_gram;
				$this->total_copper_oz     += $copper_oz;
				$this->total_copper_gram   += $copper_gram;
			endif;

			if ( $order_data->get_date_paid() && isset( $requestdata['pdf'] ) && 'ganerated' === $requestdata['pdf'] ) :
				$get_date_paid = $order_data->get_date_paid()->date( 'Y-m-d' );
			elseif ( $order_data->get_date_paid() ) :
				$get_date_paid = $order_data->get_date_paid()->date( 'Y-m-d H:i:s' );
			endif;

			$data[] = array(
				'orderid'       => $order->ID,
				'paiddate'      => $get_date_paid,
				'silver-oz'     => $silver_oz ? $silver_oz : '-',
				'silver-gram'   => $silver_gram ? $silver_gram : '-',
				'gold-oz'       => $gold_oz ? $gold_oz : '-',
				'gold-gram'     => $gold_gram ? $gold_gram : '-',
				'platinum-oz'   => $platinum_oz ? $platinum_oz : '-',
				'platinum-gram' => $platinum_gram ? $platinum_gram : '-',
				'copper-oz'     => $copper_oz ? $copper_oz : '-	',
				'copper-gram'   => $copper_gram ? $copper_gram : '-	',
				'product'       => '',
			);
		}
		return $data;
	}

	/**
	 * Render the order ID column with a clickable edit link.
	 *
	 * @param array $item The data for the current item in the list table.
	 * @return string The HTML for the order ID column with a link to edit the order.
	 */
	public function column_orderid( $item ) {
		// Define the edit link for the order ID.
		$edit_link = sprintf(
			'<a target="_blank" href="%s">#%s</a>',
			esc_url( get_edit_post_link( $item['orderid'] ) ),
			esc_html( $item['orderid'] )
		);

		return sprintf( '%s ', $edit_link );
	}

	/**
	 * Render the paid date column with a clickable.
	 *
	 * @param array $item The data for the current item in the list table.
	 * @return string The HTML for the order ID column with a link to edit the order.
	 */
	public function column_paiddate( $item ) {
		// Define the edit link for the order ID.
		$date_span = sprintf(
			'<div style="cursor: pointer;" class="paiddate_data" data-id="%s" data-date="%s">%s</div>',
			esc_attr( $item['orderid'] ),
			esc_html( $item['paiddate'] ),
			esc_html( $item['paiddate'] )
		);

		return sprintf( '%s ', $date_span );
	}

	/**
	 * Render the products column with a clickable edit link.
	 *
	 * @param array $item The data for the current item in the list table.
	 * @return string The HTML for the order ID column with a link to edit the order.
	 */
	public function column_product( $item ) {
		// Define the toggle for the order ID.
		$toggle_btn = sprintf(
			'<button class="button toggle-view" data-id="%s">%s</button>',
			esc_html( $item['orderid'] ),
			esc_html__( 'View', 'woo-weight-report' )
		);

		return sprintf( '%s ', $toggle_btn );
	}

	/**
	 * Get WP query arguments for filtering WooCommerce orders.
	 *
	 * @param int $per_page     Number of orders per page.
	 * @param int $current_page Current page number.
	 * @return array Query arguments.
	 */
	public function get_query_by_filter( $per_page, $current_page ) {
		$args = array(
			'post_type'      => 'shop_order',
			'post_status'    => array( 'wc-processing' ),
			'meta_key'       => '_date_paid', //phpcs:ignore
			'orderby'        => 'meta_value_num',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		$requestdata = wp_unslash( $_GET );
		$nonce       = isset( $requestdata['ordernonce'] ) && ! empty( $requestdata['ordernonce'] ) ? $requestdata['ordernonce'] : '';
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'ordernonce_weight' ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'woo-weight-report' ) );
		}

		$current_status = isset( $requestdata['order_status'] ) ? sanitize_text_field( $requestdata['order_status'] ) : '';
		$start_date     = isset( $requestdata['start_date'] ) ? sanitize_text_field( $requestdata['start_date'] ) : '';
		$end_date       = isset( $requestdata['end_date'] ) ? sanitize_text_field( $requestdata['end_date'] ) : '';

		if ( ! empty( $current_status ) && 'any' !== $current_status ) :
			$args['post_status'] = $current_status;
		endif;

		$meta_query = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_order_under_blacklist',
					'value'   => 'yes',
					'compare' => '!=', // Not equal to 'yes'.
				),
				array(
					'key'     => '_order_under_blacklist',
					'compare' => 'NOT EXISTS', // Include if the meta key doesn't exist.
				),
				array(
					'key'     => '_order_under_blacklist',
					'value'   => '', // Include if the meta value is empty.
					'compare' => '=',
				),
			),
		);

		if ( isset( $requestdata['filter_month'] ) && ! empty( $requestdata['filter_month'] ) ) {
			$today       = gmdate( 'Y-m-d' );
			$month_start = gmdate( 'Y-m-01' );
			$year_start  = gmdate( 'Y-01-01' );

			switch ( $requestdata['filter_month'] ) {
				case 'current_month':
					$meta_query[] = array(
						'key'     => '_date_paid',
						'value'   => array( strtotime( $month_start ), strtotime( $today ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
					break;
				case 'last_15_days':
					$meta_query[] = array(
						'key'     => '_date_paid',
						'value'   => array( strtotime( '-15 days' ), strtotime( $today ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
					break;
				case 'last_month':
					$meta_query[] = array(
						'key'     => '_date_paid',
						'value'   => array(
							strtotime( gmdate( 'Y-m-01', strtotime( 'first day of last month' ) ) ),
							strtotime( gmdate( 'Y-m-t', strtotime( 'last day of last month' ) ) ),
						),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
					break;
				case 'last_quarter':
					$meta_query[] = array(
						'key'     => '_date_paid',
						'value'   => array( strtotime( '-3 months' ), strtotime( $today ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
					break;
				case 'last_year':
					$meta_query[] = array(
						'key'     => '_date_paid',
						'value'   => array(
							strtotime( gmdate( 'Y-01-01', strtotime( '-1 year' ) ) ),
							strtotime( gmdate( 'Y-12-31', strtotime( '-1 year' ) ) ),
						),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
					break;
				case 'current_year':
					$meta_query[] = array(
						'key'     => '_date_paid',
						'value'   => array( strtotime( $year_start ), strtotime( $today ) ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
					break;
				case 'custom_range':
					if ( ! empty( $start_date ) && ! empty( $end_date ) ) :
						$meta_query[] = array(
							'key'     => '_date_paid',
							'value'   => array( strtotime( $start_date ), strtotime( $end_date . ' +1 day' ) ),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						);
					endif;
					break;
				case 'custom_month':
					if ( isset( $requestdata['m'] ) && ! empty( $requestdata['m'] ) ) :
						$mstart_date  = gmdate( 'Y-m-d', strtotime( $requestdata['m'] . '01' ) );
						$mend_date    = gmdate( 'Y-m-d', strtotime( $requestdata['m'] . '01 +1 month -1 day' ) );
						$meta_query[] = array(
							'key'     => '_date_paid',
							'value'   => array( strtotime( $mstart_date ), strtotime( $mend_date ) ),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						);
					endif;
					break;
			}
		}
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query; //phpcs:ignore
		}

		return apply_filters( 'get_query_by_filter_args', $args );
	}

	/**
	 * Get WP query arguments for filtering WooCommerce orders.
	 *
	 * @param int $per_page     Number of orders per page.
	 * @param int $current_page Current page number.
	 * @return array Query arguments.
	 */
	public function get_query_for_review( $per_page, $current_page ) {

		$args = array(
			'post_type'      => 'shop_order',
			'post_status'    => array( 'wc-cancelled' ),
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'meta_query'     => array( //phpcs:ignore
				array(
					'key'     => '_status_under_review',
					'value'   => 'yes',
					'compare' => '=', // Equal to 'yes'.
				),
			),
		);

		return apply_filters( 'get_query_for_review_args', $args );
	}

	/**
	 * Calculate the total weight of a specific metal type in a specific weight unit.
	 *
	 * This function sums up the weights for a given metal type (e.g., gold, silver) and weight type (e.g., ounces, grams)
	 * from the provided data array.
	 *
	 * @param string $metaltype  The type of metal (e.g., 'gold', 'silver', 'platinum', 'copper').
	 * @param string $weighttype The unit of measurement (e.g., 'ounces', 'grams').
	 * @param array  $data       The array containing weight data, typically retrieved from an order or product.
	 *
	 * @return float The total weight of the specified metal and weight type, or 0 if no data is available.
	 */
	public function get_weight_by_metal( $metaltype = '', $weighttype = '', $data = array() ) {
		if ( ! empty( $data ) ) :
			if ( isset( $data[ $metaltype ][ $weighttype ] ) && ! empty( $data[ $metaltype ][ $weighttype ] ) ) :
				return array_sum( $data[ $metaltype ][ $weighttype ] );
			endif;
		endif;
	}


	/**
	 * Retrieve the metal names based on the current language setting.
	 *
	 * This function returns an array of metal names translated according to the current language setting using WPML.
	 * Supported languages are Japanese ('ja') and English ('en'). The function applies a WPML filter to determine the
	 * current language.
	 *
	 * @return array An associative array mapping metal types ('gold', 'silver', 'platinum', 'copper') to their respective names
	 *               in the current language.
	 */
	public function get_metalname_by_lang() {
		$current_lang = apply_filters( 'wpml_current_language', null );
		switch ( $current_lang ) {
			case 'ja':
				$labels = array(
					'gold'     => __( '金', 'woo-weight-report' ),
					'silver'   => __( '銀', 'woo-weight-report' ),
					'platinum' => __( 'プラチナ', 'woo-weight-report' ),
					'copper'   => __( '銅', 'woo-weight-report' ),
				);
				break;
			case 'en':
				$labels = array(
					'gold'     => __( 'Gold', 'woo-weight-report' ),
					'silver'   => __( 'Silver', 'woo-weight-report' ),
					'platinum' => __( 'Platinum', 'woo-weight-report' ),
					'copper'   => __( 'Copper', 'woo-weight-report' ),
				);
				break;
			default:
				$labels = array();

		}
		return $labels;
	}

	/**
	 * Output extra controls above the table for filtering orders.
	 *
	 * This function generates a form for filtering orders by month range,
	 * order status, and custom date ranges. It includes nonce verification
	 * for security and ensures sanitized input for query parameters.
	 *
	 * @param string $which The location of the extra navigation ('top' or 'bottom').
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which && true !== $this->get_under_review() ) {

			$requestdata = wp_unslash( $_GET );
			$nonce       = isset( $requestdata['ordernonce'] ) && ! empty( $requestdata['ordernonce'] ) ? $requestdata['ordernonce'] : '';
			if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'ordernonce_weight' ) ) {
				wp_die( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'woo-weight-report' ) );
			}
			$current_status = isset( $requestdata['order_status'] ) ? sanitize_text_field( $requestdata['order_status'] ) : '';
			$filter_month   = isset( $requestdata['filter_month'] ) ? sanitize_text_field( $requestdata['filter_month'] ) : '';
			$per_page       = isset( $requestdata['per_page'] ) ? sanitize_text_field( $requestdata['per_page'] ) : '';
			$start_date     = isset( $requestdata['start_date'] ) ? sanitize_text_field( $requestdata['start_date'] ) : '';
			$end_date       = isset( $requestdata['end_date'] ) ? sanitize_text_field( $requestdata['end_date'] ) : '';
			?>
			<form method="get">
				<input type="hidden" name="page" value="woo-weight-report" />
				<div class="alignleft actions">
					<select name="filter_month" id="filter-month">
						<option value=""><?php esc_html_e( 'Please select a range', 'woo-weight-report' ); ?></option>
						<option value="current_month" <?php selected( $filter_month, 'current_month' ); ?>>
							<?php esc_html_e( 'Current Month', 'woo-weight-report' ); ?>
						</option>
						<option value="last_15_days" <?php selected( $filter_month, 'last_15_days' ); ?>>
							<?php esc_html_e( 'Last 15 Days', 'woo-weight-report' ); ?>
						</option>
						<option value="last_month" <?php selected( $filter_month, 'last_month' ); ?>>
							<?php esc_html_e( 'Last Month', 'woo-weight-report' ); ?>
						</option>
						<option value="last_quarter" <?php selected( $filter_month, 'last_quarter' ); ?>>
							<?php esc_html_e( 'Last Quarter', 'woo-weight-report' ); ?>
						</option>
						<option value="last_year" <?php selected( $filter_month, 'last_year' ); ?>>
							<?php esc_html_e( 'Last Year', 'woo-weight-report' ); ?>
						</option>
						<option value="current_year" <?php selected( $filter_month, 'current_year' ); ?>>
							<?php esc_html_e( 'Current Year', 'woo-weight-report' ); ?>
						</option>
						<option value="custom_range" <?php selected( $filter_month, 'custom_range' ); ?>>
							<?php esc_html_e( 'Custom Range', 'woo-weight-report' ); ?>
						</option>
						<option value="custom_month" <?php selected( $filter_month, 'custom_month' ); ?>>
							<?php esc_html_e( 'Custom Month', 'woo-weight-report' ); ?>
						</option>
					</select>
					<input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" id="start_date_weight" 
							placeholder="<?php esc_attr_e( 'Start Date', 'woo-weight-report' ); ?>" 
							style="display:<?php echo ! empty( $start_date ) && ! empty( $end_date ) ? 'inline-block' : 'none'; ?>;">
					<input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" id="end_date_weight" 
							placeholder="<?php esc_attr_e( 'End Date', 'woo-weight-report' ); ?>" 
							style="display:<?php echo ! empty( $start_date ) && ! empty( $end_date ) ? 'inline-block' : 'none'; ?>;">
				</div>
				<div class="alignleft actions">
					<?php $this->months_dropdown( 'shop_order' ); ?>
				</div>
				<div class="alignleft actions">
					<select name="order_status" id="order_status">
						<option value="any" <?php selected( $current_status, 'any' ); ?>><?php echo esc_html__( 'All Status', 'woocommerce' ); ?></option>
						<option value="wc-processing" <?php selected( $current_status, 'wc-processing' ); ?>><?php echo esc_html__( 'Processing', 'woocommerce' ); ?></option>
						<option value="wc-completed" <?php selected( $current_status, 'wc-completed' ); ?>><?php echo esc_html__( 'Completed', 'woocommerce' ); ?></option>
					</select>
					<?php wp_nonce_field( 'ordernonce_weight', 'ordernonce', false ); ?>
					<?php submit_button( __( 'Filter', 'woo-weight-report' ), 'action', '', false, array( 'id' => 'weight-filter' ) ); ?>
				</div>
			</form>
			<?php
			if ( class_exists( 'GI_Certificate' ) ) :
				$pageurl = add_query_arg(
					array(
						'page' => 'woo-weight-report',
						'pdf'  => 'ganerated',
					),
				);
				?>
				<a class="button" href="<?php echo esc_url( $pageurl ); ?>"><?php esc_html_e( 'Generate PDF Report', 'woo-weight-report' ); ?></a>
				<?php
			endif;
		}
	}



	/**
	 * Sort the data by a specific column.
	 *
	 * @param array $a First item to compare.
	 * @param array $b Second item to compare.
	 * @return int Comparison result.
	 */
	public function usort_reorder( $a, $b ) {
		$getdata = wp_unslash( $_GET ); //phpcs:ignore
		$orderby = ( ! empty( $getdata['orderby'] ) ) ? $getdata['orderby'] : 'paiddate';
		$order   = ( ! empty( $getdata['order'] ) ) ? $getdata['order'] : 'desc';

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( 'desc' === $order ) ? -$result : $result;
	}

	/**
	 * Default column rendering.
	 *
	 * @param array  $item        The current item.
	 * @param string $column_name The name of the current column.
	 * @return string Column output.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'orderid':
			case 'paiddate':
			case 'silver-oz':
			case 'silver-gram':
			case 'gold-oz':
			case 'gold-gram':
			case 'platinum-oz':
			case 'platinum-gram':
			case 'copper-oz':
			case 'copper-gram':
			case 'product':
				return esc_html( $item[ $column_name ] ); // Escape output for security.
			default:
				return; // Fallback for unknown columns.
		}
	}

	/**
	 * Get data by order.
	 *
	 * @param object $order The current order object.
	 * @return array return weight.
	 */
	public function order_weight_by_product( $order ) {
		$order_items = $order->get_items();
		$weight_data = array();

		// Loop through each product to get product ID.
		foreach ( $order_items as $item_id => $item ) :
			$product_id   = $item->get_product_id();
			$quantity     = $item->get_quantity();
			$metal_data   = wc_get_product_terms( $product_id, 'pa_metal-type', array( 'fields' => 'names' ) );
			$metaltype    = ! empty( $metal_data ) ? array_shift( $metal_data ) : '';
			$denomination = get_post_meta( $product_id, '_custom_weight_denomination', true );
			$weight_value = get_post_meta( $product_id, '_custom_weight_value', true );
			// Append new value of weight.
			if ( ! empty( $denomination ) && ! empty( $weight_value ) && ! empty( $metaltype ) ) :
				$weight_data[ $metaltype ][ $denomination ][] = (float) $weight_value * (int) $quantity;
			endif;

		endforeach;
		return $weight_data;
	}

	/**
	 * Display dynamically calculated totals for metals in the custom table row.
	 */
	public function display_rows() {

		$getdata = wp_unslash( $_GET );
		$nonce   = isset( $getdata['ordernonce'] ) && ! empty( $getdata['ordernonce'] ) ? $getdata['ordernonce'] : '';
		if ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'ordernonce_weight' ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'woo-weight-report' ) );
		}

		// Display default rows.
		parent::display_rows();

		// Add a custom row for total weights.
		?>
		<tr class="custom-bottom-row">
			<td colspan="2">
				<strong><?php esc_html_e( 'Total Weight:', 'woo-weight-report' ); ?></strong>
			</td>
			<td><strong><?php echo esc_html( $this->total_silver_oz ); ?> oz</strong></td>
			<td><strong><?php echo esc_html( $this->total_silver_gram ); ?> grams</strong></td>
			<td><strong><?php echo esc_html( $this->total_gold_oz ); ?> oz</strong></td>
			<td><strong><?php echo esc_html( $this->total_gold_gram ); ?> grams</strong></td>
			<td><strong><?php echo esc_html( $this->total_platinum_oz ); ?> oz</strong></td>
			<td><strong><?php echo esc_html( $this->total_platinum_gram ); ?> grams</strong></td>
			<td><strong><?php echo esc_html( $this->total_copper_oz ? $this->total_copper_oz : '0' ); ?> oz</strong></td>
			<td><strong><?php echo esc_html( $this->total_copper_gram ? $this->total_copper_gram : '0' ); ?> grams</strong></td>
			<?php echo ( ! isset( $getdata['pdf'] ) && ! array_key_exists( 'pdf', $getdata ) ) ? '<td class="empty"></td>' : ''; ?>
		</tr>
		<?php
	}

	/**
	 * Displays a dropdown for filtering items in the list table by month.
	 *
	 * @global wpdb      $wpdb      WordPress database abstraction object.
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 *
	 * @param string $post_type The post type.
	 */
	protected function months_dropdown( $post_type ) {
		global $wpdb, $wp_locale;

		/**
		 * Filters whether to remove the 'Months' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool   $disable   Whether to disable the drop-down. Default false.
		 * @param string $post_type The post type.
		 */
		if ( apply_filters( 'disable_months_dropdown', false, $post_type ) ) {
			return;
		}

		/**
		 * Filters whether to short-circuit performing the months dropdown query.
		 *
		 * @since 5.7.0
		 *
		 * @param object[]|false $months   'Months' drop-down results. Default false.
		 * @param string         $post_type The post type.
		 */
		$months = apply_filters( 'pre_months_dropdown_query', false, $post_type );

		// phpcs:disable
		if ( ! is_array( $months ) ) {
			$extra_checks = "AND post_status NOT IN ('auto-draft', 'wc-on-hold', 'wc-pending', 'wc-failed', 'wc-checkout-draft', 'wc-cancelled')";
			$allowed_statuses = array('wc-processing' );

			if (isset($_GET['post_status']) && 'trash' === $_GET['post_status']) {
				$extra_checks = $wpdb->prepare(' AND post_status = %s', $_GET['post_status']);
			} elseif (isset($_GET['post_status'])) {
				$extra_checks = $wpdb->prepare(' AND post_status = %s', sanitize_text_field($_GET['post_status']));
			} else {
				$extra_checks .= $wpdb->prepare(" AND post_status IN (%s, %s)", $allowed_statuses[0], $allowed_statuses[1] );
			}

			$months = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
					FROM $wpdb->posts
					WHERE post_type = %s
					$extra_checks
					ORDER BY post_date DESC",
					$post_type
				)
			);
		}

		/**
		 * Filters the 'Months' drop-down results.
		 *
		 * @since 3.7.0
		 *
		 * @param object[] $months    Array of the months drop-down query results.
		 * @param string   $post_type The post type.
		 */
		$months = apply_filters( 'months_dropdown_results', $months, $post_type );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 === $month_count && 0 === (int) $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php echo get_post_type_object( $post_type )->labels->filter_by_date; ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
		<?php
		foreach ( $months as $arc_row ) {
			if ( 0 === (int) $arc_row->year ) {
				continue;
			}

			$month = zeroise( $arc_row->month, 2 );
			$year  = $arc_row->year;

			printf(
				"<option %s value='%s'>%s</option>\n",
				selected( $m, $year . $month, false ),
				esc_attr( $arc_row->year . $month ),
				/* translators: 1: Month name, 2: 4-digit year. */
				sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
			);
		}
		?>
		</select>
		<?php
		// phpcs:enable
	}
}
