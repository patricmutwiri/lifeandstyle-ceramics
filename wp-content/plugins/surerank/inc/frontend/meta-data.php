<?php
/**
 * Meta Data
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

use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Meta_Variables\Site;
use SureRank\Inc\Meta_Variables\Term;
use SureRank\Inc\Traits\Get_Instance;
use WP_Post;
use WP_Term;

/**
 * Meta Data
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Meta_Data {

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
		/**
		 * If it's a ajax request, we don't need to call wp_head or wp hook here.
		 *
		 * @since 1.3.0
		 */
		if ( \wp_doing_ajax() ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'print_meta_data' ], 2 );
		add_action( 'wp', [ $this, 'set_meta_data' ], 1 );
	}

	/**
	 * Add meta data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_meta_data() {
		$this->print_meta();
	}

	/**
	 * Add meta data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_meta_data() {
		$this->set_meta();
	}

	/**
	 * Meta HTML Template
	 *
	 * @param string       $attr_key meta attribute key.
	 * @param string|false $attr_value meta attribute value.
	 * @param string       $attr_type meta attribute type.
	 * @since 1.0.0
	 * @return void
	 */
	public function meta_html_template( $attr_key, $attr_value, $attr_type = 'name' ) {
		if ( empty( $attr_value ) ) {
			return;
		}

		if ( 'property' === $attr_type ) {
			echo '<meta property="' . esc_attr( $attr_key ) . '" content="' . esc_attr( $attr_value ) . '">' . PHP_EOL;
		} else {
			echo '<meta name="' . esc_attr( $attr_key ) . '" content="' . esc_attr( $attr_value ) . '">' . PHP_EOL;
		}
	}

	/**
	 * Get Meta Data
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>|null
	 */
	public function get_meta_data() {
		return $this->meta_data;
	}

	/**
	 * Set meta data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function set_meta() {
		$queried_object = get_queried_object();
		if ( is_singular() && $queried_object instanceof WP_Post ) {
			Post::get_instance()->set_post( intval( $queried_object->ID ) );
		} elseif ( ( is_tax() || is_category() || is_tag() ) && $queried_object instanceof WP_Term ) {
			Term::get_instance()->set_term( intval( $queried_object->term_id ) );
		} elseif ( is_front_page() || is_home() ) {
			Site::get_instance();
		} elseif ( is_author() || is_date() ) {
			// For archive pages, we don't need to initialize any specific meta variable instance.
			// But we still need to apply the filter to allow archive meta to be added.
			$this->initialize_archive_variables();
		} else {
			return;
		}

		$this->meta_data = apply_filters( 'surerank_set_meta', $this->meta_data );
	}

	/**
	 * This function prints available meta on current page.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function print_meta() {
		echo '<!-- SureRank Meta Data -->' . PHP_EOL;

		do_action( 'surerank_print_meta', $this->meta_data );

		echo PHP_EOL . '<!-- /SureRank Meta Data -->' . PHP_EOL;
	}

	/**
	 * Initialize archive variables - intentionally empty.
	 * This method exists to avoid empty elseif statement coding standard violation.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function initialize_archive_variables() {
		// Intentionally empty - archive pages don't need specific meta variable initialization.
		// The filter application happens after this method call.
	}
}
