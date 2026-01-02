<?php
/**
 * Taxonomy Meta Data
 *
 * This file will handle functionality to print meta_data in frontend for taxonomy requests.
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
 * Taxonomy SEO
 * This class will handle functionality to print meta_data in frontend for taxonomy requests.
 *
 * @since 1.0.0
 */
class Taxonomy {

	use Get_Instance;

	/**
	 * Meta Data
	 *
	 * @var array<string, mixed>|null $meta_data Term meta data.
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
	 * @return array<string, mixed>
	 */
	public function get_meta_data( $meta_data ) {
		if ( ! is_tax() && ! is_category() && ! is_tag() ) {
			return $meta_data;
		}

		$term = get_queried_object();
		if ( empty( $term ) || ! isset( $term->term_id ) ) {
			return $meta_data;
		}

		if ( null !== $this->meta_data ) {
			return $meta_data;
		}

		$term_id         = intval( $term->term_id );
		$taxonomy        = $term->taxonomy ?? '';
		$this->meta_data = Variables::replace( Validate::array( Settings::prep_term_meta( $term_id, $taxonomy, true ) ), $term_id );

		return $this->meta_data;
	}
}
