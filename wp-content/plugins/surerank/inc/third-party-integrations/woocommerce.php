<?php
/**
 * WooCommerce integration
 *
 * This file handles WooCommerce compatibility for SureRank SEO.
 *
 * @package surerank
 * @since 1.5.0
 */

namespace SureRank\Inc\ThirdPartyIntegrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Woocommerce integration class
 *
 * Handles Woocommerce specific SEO optimizations and compatibility.
 *
 * @since 1.5.0
 */
class Woocommerce {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		if ( ! Helper::wc_status() ) {
			return;
		}

		$this->init_hooks();
	}

	/**
	 * Prepare post meta for WooCommerce products
	 *
	 * @since 1.5.0
	 * @param array<string, mixed> $meta Post meta.
	 * @param int                  $post_id Post id.
	 * @param string               $post_type Post type.
	 * @param bool                 $is_taxonomy Is taxonomy.
	 * @return array<string, mixed> Prepared post meta.
	 */
	public function prep_post_meta( $meta, $post_id, $post_type, $is_taxonomy ): array {
		$cart_id     = get_option( 'woocommerce_cart_page_id' );
		$checkout_id = get_option( 'woocommerce_checkout_page_id' );
		$account_id  = get_option( 'woocommerce_myaccount_page_id' );

		if ( $is_taxonomy ) {
			return $meta; /* If taxonomy, return early. */
		}

		/* Early return if post_id is not equal to cart, checkout or account page id. */
		if ( (int) $post_id !== (int) $cart_id && (int) $post_id !== (int) $checkout_id && (int) $post_id !== (int) $account_id ) {
			return $meta;
		}

		$no_index = $meta['post_no_index'] ?? '';

		if ( empty( $no_index ) ) {
			$meta['post_no_index'] = 'yes';
		}

		return $meta;
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function init_hooks(): void {
		add_filter( 'surerank_prep_post_meta', [ $this, 'prep_post_meta' ], 10, 4 );
	}
}
