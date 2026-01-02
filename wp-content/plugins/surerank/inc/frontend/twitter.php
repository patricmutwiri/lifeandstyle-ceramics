<?php
/**
 * Common Meta Data
 *
 * This file will handle functionality to print Twitter meta_data in the frontend for different requests.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Validate;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Twitter SEO
 * This class will handle functionality to print meta_data in the frontend for Twitter.
 *
 * @since 1.0.0
 */
class Twitter {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'surerank_print_meta', [ $this, 'twitter_meta' ], 1, 1 );
	}

	/**
	 * Prepare and add Twitter meta data.
	 *
	 * @param array<string, mixed> $meta_data Twitter meta data array.
	 * @since 1.0.0
	 * @return void
	 */
	public function twitter_meta( $meta_data ) {
		if ( apply_filters( 'surerank_disable_twitter_tags', false ) ) {
			return;
		}

		if ( is_home() && is_front_page() ) {
			$same_as_facebook = null !== Settings::get( 'twitter_same_as_facebook' ) ? Settings::get( 'twitter_same_as_facebook' ) : false;
		} else {
			$same_as_facebook = $meta_data['twitter_same_as_facebook'] ?? false;
		}

		// Prepare fallback image if not available.
		$image = Image::get_instance();
		$image->get( $meta_data, 'twitter_image_url' );

		// Add Twitter-specific meta tags.
		$this->add_common_tags( $meta_data, $same_as_facebook );
	}

	/**
	 * Add common Twitter tags.
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @param bool                 $same_as_facebook Whether to use Facebook meta as fallback.
	 * @since 1.0.0
	 * @return void
	 */
	private function add_common_tags( $meta_data, $same_as_facebook ) {

		$twitter_image = $this->get_twitter_value( $meta_data, $same_as_facebook, 'facebook_image_url', 'twitter_image_url', 'fallback_image' );
		if ( $twitter_image && ! Image::get_instance()->is_valid_image_extension( $twitter_image ) ) {
			$twitter_image = '';
		}

		$common_tags = [
			'twitter:card'        => Settings::get( 'twitter_card_type' ),
			'twitter:site'        => $this->get_formatted_twitter_site(),
			'twitter:title'       => $this->get_twitter_value( $meta_data, $same_as_facebook, 'facebook_title', 'twitter_title', 'page_title' ),
			'twitter:description' => $this->get_twitter_value( $meta_data, $same_as_facebook, 'facebook_description', 'twitter_description', 'page_description' ),
			'twitter:image'       => $twitter_image,
			'twitter:creator'     => $this->get_formatted_twitter_creator(),
		];

		foreach ( $common_tags as $key => $value ) {
			if ( Validate::not_empty( $value ) ) {
				Meta_Data::get_instance()->meta_html_template( $key, $value, 'name' );
			}
		}

		if ( Helper::wc_status() && Helper::is_product() ) {
			$this->add_product_tags( $meta_data );
		}
	}

	/**
	 * Get Twitter meta value with Facebook fallback logic.
	 *
	 * @param array<string, mixed> $meta_data Meta data array.
	 * @param bool                 $same_as_facebook Whether to use Facebook meta as fallback.
	 * @param string               $facebook_key Facebook meta key.
	 * @param string               $twitter_key Twitter meta key.
	 * @param string|null          $fallback_key Optional fallback key.
	 * @since 1.0.0
	 * @return string
	 */
	private function get_twitter_value( $meta_data, $same_as_facebook, $facebook_key, $twitter_key, $fallback_key = null ) {
		if ( $same_as_facebook ) {
			if ( isset( $meta_data[ $facebook_key ] ) && ! empty( $meta_data[ $facebook_key ] ) ) {
				return $meta_data[ $facebook_key ];
			}
			if ( $fallback_key && isset( $meta_data[ $fallback_key ] ) ) {
				return $meta_data[ $fallback_key ];
			}
		}
		return $meta_data[ $twitter_key ] ?? '';
	}

	/**
	 * Get formatted Twitter creator.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_formatted_twitter_creator() {
		$twitter_creator = Settings::get( 'twitter_profile_fallback' );

		if ( empty( $twitter_creator ) ) {
			return '';
		}

		$twitter_creator = explode( '/', $twitter_creator );
		$twitter_creator = end( $twitter_creator );

		return '@' . $twitter_creator;
	}

	/**
	 * Get formatted Twitter site.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_formatted_twitter_site() {
		$twitter_site = Settings::get( 'twitter_profile_username' );
		$twitter_site = explode( '/', $twitter_site );
		$twitter_site = end( $twitter_site );

		if ( empty( $twitter_site ) ) {
			return '';
		}

		return '@' . $twitter_site;
	}

	/**
	 * Add product-specific Twitter tags.
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @since 1.0.0
	 * @return void
	 */
	private function add_product_tags( $meta_data ) {
		$price        = $meta_data['product_price'] ? $meta_data['product_currency'] . ' ' . $meta_data['product_price'] : '';
		$product_tags = [
			'twitter:label1' => 'Price',
			'twitter:data1'  => $price,
			'twitter:label2' => 'Availability',
			'twitter:data2'  => 'instock' === $meta_data['product_availability'] ? esc_html__( 'In Stock', 'surerank' ) : esc_html__( 'Out of Stock', 'surerank' ),
		];

		foreach ( $product_tags as $key => $value ) {
			if ( Validate::not_empty( $value ) ) {
				Meta_Data::get_instance()->meta_html_template( $key, $value, 'name' );
			}
		}
	}

}
