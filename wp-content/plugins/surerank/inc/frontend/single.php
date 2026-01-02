<?php
/**
 * Common Meta Data
 *
 * This file will handle functionality to print meta_data in frontend for different requests.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Validate;
use SureRank\Inc\Functions\Variables;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Single Page SEO
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Single {

	use Get_Instance;

	/**
	 * Meta Data
	 *
	 * @var array<string, mixed>|null $meta_data Post meta data.
	 * @since 1.0.0
	 */
	private $meta_data = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'surerank_set_meta', [ $this, 'get_meta_data' ], 1 );
	}

	/**
	 * Add meta data
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @since 1.0.0
	 * @return array<string, mixed> $meta_data
	 */
	public function get_meta_data( $meta_data ) {
		if ( ! is_singular() ) {
			return $meta_data;
		}

		$post_id = get_the_ID();

		if ( empty( $post_id ) ) {
			return $meta_data;
		}

		if ( null !== $this->meta_data ) {
			return $meta_data;
		}

		$post_type       = get_post_type( $post_id ) ? get_post_type( $post_id ) : '';
		$this->meta_data = Variables::replace( Validate::array( Settings::prep_post_meta( intval( $post_id ), $post_type, false ) ), intval( $post_id ) );
		return $this->meta_data;
	}
}
