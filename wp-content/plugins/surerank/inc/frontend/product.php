<?php
/**
 * Product Meta Data
 *
 * This file will handle functionality to print product-specific meta_data in the frontend for WooCommerce product pages.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Product SEO
 * This class will handle functionality to print product-specific meta_data in the frontend.
 *
 * @since 1.0.0
 */
class Product {

	use Get_Instance;

	/**
	 * Meta Data
	 *
	 * @var array<string, mixed>|null $meta_data Product meta data.
	 * @since 1.0.0
	 */
	private $meta_data = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Check if WooCommerce is active.
		if ( ! Helper::wc_status() ) {
			return;
		}
		// Set product-specific meta data.
		add_filter( 'surerank_set_meta', [ $this, 'get_meta_data' ], 1 );
	}

	/**
	 * Get and set product-specific meta data
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_meta_data( $meta_data ) {
		if ( ! Helper::is_product() && ! Helper::wc_status() ) {
			return $meta_data;
		}

		$product_id = get_the_ID();
		if ( empty( $product_id ) ) {
			return $meta_data;
		}

		if ( null !== $this->meta_data ) {
			return $meta_data;
		}

		$this->meta_data = $this->prepare_product_meta( $product_id );

		if ( empty( $this->meta_data ) ) {
			return $meta_data;
		}

		if ( ! $meta_data ) {
			return $this->meta_data;
		}

		return array_merge( $meta_data, $this->meta_data );
	}

	/**
	 * Prepare product-specific meta data
	 *
	 * @param int $product_id Product ID.
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	private function prepare_product_meta( $product_id ) {
		$product = \wc_get_product( $product_id );

		if ( ! $product ) {
			return [];
		}

		return [
			'product_price'        => $product->get_price(),
			'product_currency'     => get_woocommerce_currency(),
			'product_availability' => $product->is_in_stock() ? 'instock' : 'outofstock',
		];
	}
}
