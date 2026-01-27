<?php
/**
 * AJAX Class
 *
 * @package MCRPD_Regenerate_Download_Permissions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MCRPD_Ajax
 */
class MCRPD_Ajax {

	/**
	 * Initialize AJAX hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_mcrpd_regen_step', array( __CLASS__, 'process_step' ) );
	}

	/**
	 * Process a single batch step.
	 */
	public static function process_step() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ) ), 403 );
		}

		check_ajax_referer( 'mcrpd_regen_ajax_nonce', 'nonce' );

		if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_order' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not available.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ) ), 500 );
		}

		if ( ! function_exists( 'wc_downloadable_product_permissions' ) ) {
			wp_send_json_error( array( 'message' => __( 'wc_downloadable_product_permissions() not available.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ) ), 500 );
		}

		global $wpdb;

		$settings    = mcrpd_get_settings();
		$product_ids = mcrpd_parse_product_ids( (string) $settings['product_ids_csv'] );
		$statuses    = mcrpd_parse_statuses( (string) $settings['order_statuses'] );

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid product IDs configured.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ) ), 400 );
		}
		if ( empty( $statuses ) ) {
			$statuses = array( 'wc-completed' );
		}

		$page = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;

		// Fetch 20 orders per batch (AJAX requirement).
		$args = array(
			'status'   => $statuses,
			'limit'    => 20,
			'paged'    => $page,
			'paginate' => true,
			'orderby'  => 'ID',
			'order'    => 'ASC',
			'type'     => 'shop_order',
			'return'   => 'objects',
		);

		/**
		 * Filter query arguments for fetching orders.
		 *
		 * @since 1.0.1
		 * @param array $args Query arguments.
		 */
		$args = apply_filters( 'mcrpd_regen_query_args', $args );

		$query = wc_get_orders( $args );

		$orders     = is_object( $query ) && isset( $query->orders ) ? (array) $query->orders : array();
		$max_pages  = is_object( $query ) && isset( $query->max_num_pages ) ? absint( $query->max_num_pages ) : 0;

		$scanned = count( $orders );
		$updated = 0;

		$permissions_table = $wpdb->prefix . 'woocommerce_downloadable_product_permissions';

		/**
		 * Action before batch processing.
		 *
		 * @since 1.0.1
		 * @param array $orders List of orders in current batch.
		 * @param int   $page   Current page.
		 */
		do_action( 'mcrpd_before_batch_processing', $orders, $page );

		foreach ( $orders as $order ) {
			if ( ! ( $order instanceof WC_Order ) ) {
				continue;
			}

			// Only touch orders that contain our target products.
			// Allow filtering this check.
			$should_process = mcrpd_order_contains_any_product_ids( $order, $product_ids );

			/**
			 * Filter whether to process a specific order.
			 *
			 * @since 1.0.1
			 * @param bool     $should_process Whether to process.
			 * @param WC_Order $order          Order object.
			 */
			if ( ! apply_filters( 'mcrpd_should_process_order', $should_process, $order ) ) {
				continue;
			}

			$order_id = $order->get_id();

			// Reset permissions for the order, then regenerate.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $permissions_table, array( 'order_id' => $order_id ), array( '%d' ) );

			try {
				wc_downloadable_product_permissions( $order_id, true );
				$updated++;
				
				/**
				 * Action after order is processed.
				 *
				 * @since 1.0.1
				 * @param int      $order_id Order ID.
				 * @param WC_Order $order    Order object.
				 */
				do_action( 'mcrpd_order_processed', $order_id, $order );

			} catch ( Throwable $e ) {
				// If it fails, we simply continue; we could log error here.
			}
		}

		/**
		 * Action after batch processing.
		 *
		 * @since 1.0.1
		 * @param array $orders List of orders in current batch.
		 * @param int   $page   Current page.
		 */
		do_action( 'mcrpd_after_batch_processing', $orders, $page );

		$done = ( $max_pages > 0 ) ? ( $page >= $max_pages ) : true;

		wp_send_json_success( array(
			'page'      => $page,
			'max_pages' => $max_pages,
			'next_page' => $done ? $page : ( $page + 1 ),
			'scanned'   => $scanned,
			'updated'   => $updated,
			'done'      => $done,
			'message'   => sprintf(
				/* translators: 1: Page number, 2: Scanned count, 3: Updated count */
				esc_html__( 'Batch page %1$d: scanned %2$d orders, updated %3$d matching orders.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				$page,
				$scanned,
				$updated
			),
		) );
	}
}
