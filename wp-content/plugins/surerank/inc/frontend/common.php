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

use SureRank\Inc\Functions\Validate;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Common SEO
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Common {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'surerank_print_meta', [ $this, 'common_meta' ], 1, 1 );
		add_filter( 'pre_get_document_title', [ $this, 'get_document_title' ], 1, 1 );
	}

	/**
	 * Add meta data
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @since 1.0.0
	 * @return void
	 */
	public function common_meta( $meta_data ) {
		$this->print_page_description( $meta_data );
	}

	/**
	 * Print the Page Description meta
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @since 1.0.0
	 * @return void
	 */
	public function print_page_description( $meta_data ) {

		$description = $meta_data['page_description'] ?? '';

		if ( ! empty( $description ) && Validate::string( $description ) ) {
			Meta_Data::get_instance()->meta_html_template( 'description', $description );
		}
	}

	/**
	 * Get the document title
	 *
	 * @param string $title Document Title.
	 * @since 1.0.0
	 * @return string $title
	 */
	public function get_document_title( $title ) {
		$meta_data = Meta_Data::get_instance()->get_meta_data();
		if ( ! empty( $meta_data['page_title'] ) && Validate::string( $meta_data['page_title'] ) ) {
			$title = esc_html( $meta_data['page_title'] );
		}

		return $title;
	}

}
