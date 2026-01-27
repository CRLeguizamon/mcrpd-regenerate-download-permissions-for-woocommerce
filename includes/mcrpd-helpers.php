<?php
/**
 * Helper functions
 *
 * @package MCRPD_Regenerate_Download_Permissions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get option key.
 *
 * @return string
 */
function mcrpd_option_key() : string {
	return 'mcrpd_regen_download_permissions_settings';
}

/**
 * Get default settings.
 *
 * @return array
 */
function mcrpd_default_settings() : array {
	$defaults = array(
		'product_ids_csv' => '',
		'order_statuses'  => 'wc-completed', // comma separated
	);

	/**
	 * Filter default settings.
	 *
	 * @since 1.0.0
	 * @param array $defaults Default settings.
	 */
	return apply_filters( 'mcrpd_default_settings', $defaults );
}

/**
 * Get settings.
 *
 * @return array
 */
function mcrpd_get_settings() : array {
	$saved    = get_option( mcrpd_option_key(), array() );
	$defaults = mcrpd_default_settings();

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return array_merge( $defaults, $saved );
}

/**
 * Parse product IDs from CSV string.
 *
 * @param string $csv Component separated values.
 * @return array
 */
function mcrpd_parse_product_ids( string $csv ) : array {
	$parts = array_map( 'trim', explode( ',', $csv ) );
	$ids   = array_filter( array_map( 'absint', $parts ) );
	return array_values( array_unique( $ids ) );
}

/**
 * Parse order statuses from CSV string.
 *
 * @param string $csv Component separated values.
 * @return array
 */
function mcrpd_parse_statuses( string $csv ) : array {
	$parts = array_map( 'trim', explode( ',', $csv ) );
	$st    = array_filter( array_map( 'sanitize_key', $parts ) );
	return array_values( array_unique( $st ) );
}

/**
 * Check if order contains any of target products.
 *
 * @param WC_Order $order       Order object.
 * @param array    $product_ids Array of product IDs.
 * @return bool
 */
function mcrpd_order_contains_any_product_ids( WC_Order $order, array $product_ids ) : bool {
	if ( empty( $product_ids ) ) {
		return false;
	}

	foreach ( $order->get_items() as $item ) {
		$pid = absint( $item->get_product_id() );
		$vid = absint( $item->get_variation_id() );

		if ( in_array( $pid, $product_ids, true ) || ( $vid && in_array( $vid, $product_ids, true ) ) ) {
			return true;
		}
	}

	return false;
}
