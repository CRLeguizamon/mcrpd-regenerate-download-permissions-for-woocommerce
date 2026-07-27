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
		'batch_size'      => 20,
		'date_from'       => '',
		'date_to'         => '',
		'product_cats'    => '', // comma separated term IDs
	);

	/**
	 * Filter default settings.
	 *
	 * @since 1.0.3
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
 * Get the batch size for AJAX processing.
 *
 * Reads the saved setting, clamps it between 1 and 500,
 * and allows filtering via the 'mcrpd_batch_size' hook.
 *
 * @since 1.1.0
 * @return int
 */
function mcrpd_get_batch_size() : int {
	$settings   = mcrpd_get_settings();
	$batch_size = isset( $settings['batch_size'] ) ? absint( $settings['batch_size'] ) : 20;
	$batch_size = max( 1, min( 500, $batch_size ) );

	/**
	 * Filter the batch size used for AJAX processing.
	 *
	 * @since 1.1.0
	 * @param int $batch_size The batch size (1–500).
	 */
	return (int) apply_filters( 'mcrpd_batch_size', $batch_size );
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
 * Parse category IDs from CSV string.
 *
 * @since 1.1.1
 * @param string $csv Comma separated values.
 * @return array
 */
function mcrpd_parse_category_ids( string $csv ) : array {
	$parts = array_map( 'trim', explode( ',', $csv ) );
	$ids   = array_filter( array_map( 'absint', $parts ) );
	return array_values( array_unique( $ids ) );
}

/**
 * Get IDs of downloadable products belonging to given categories.
 *
 * Queries both simple and variable products in the specified
 * product_cat terms that have at least one downloadable file.
 *
 * @since 1.1.1
 * @param array $cat_ids Array of product_cat term IDs.
 * @return array Array of product IDs.
 */
function mcrpd_get_downloadable_product_ids_in_categories( array $cat_ids ) : array {
	if ( empty( $cat_ids ) ) {
		return array();
	}

	$args = array(
		'status'       => 'publish',
		'limit'        => -1,
		'return'       => 'ids',
		'downloadable' => true,
		'category'     => array(),
	);

	// Resolve category slugs from term IDs for wc_get_products compatibility.
	foreach ( $cat_ids as $term_id ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$args['category'][] = $term->slug;
		}
	}

	if ( empty( $args['category'] ) ) {
		return array();
	}

	$product_ids = wc_get_products( $args );

	// Also include variable products whose variations are downloadable.
	$variable_args = array(
		'status'   => 'publish',
		'limit'    => -1,
		'return'   => 'ids',
		'type'     => 'variable',
		'category' => $args['category'],
	);

	$variable_ids = wc_get_products( $variable_args );

	foreach ( $variable_ids as $parent_id ) {
		$product = wc_get_product( $parent_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			continue;
		}

		$children = $product->get_children();
		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( $variation && $variation->is_downloadable() ) {
				$product_ids[] = $parent_id;
				$product_ids[] = $child_id;
				break; // At least one downloadable variation found.
			}
		}
	}

	return array_values( array_unique( array_map( 'absint', $product_ids ) ) );
}

/**
 * Check if order contains any of target products.
 *
 * @param WC_Order $order       Order object.
 * @param array    $product_ids Array of product IDs.
 * @return bool
 */
function mcrpd_order_contains_any_product_ids( WC_Order $order, array $product_ids ) : bool {
	// Empty array means "no filter" — all orders match.
	if ( empty( $product_ids ) ) {
		return true;
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
