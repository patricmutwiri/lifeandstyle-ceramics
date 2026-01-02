<?php
/**
 * Terms class
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Meta_Variables;

use SureRank\Inc\Frontend\Description;
use SureRank\Inc\Traits\Custom_Field;
use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

/**
 * This class deals with variables related to terms.
 *
 * @since 0.0.1
 */
class Term extends Variables {

	use Get_Instance, Custom_Field;

	/**
	 * Stores variables array.
	 *
	 * @var array<string, mixed>
	 * @since 0.0.1
	 */
	public $variables = [];

	/**
	 * Category of variables.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	public $category = 'terms';

	/**
	 * Stores current term.
	 *
	 * @var \WP_Term|null
	 * @since 0.0.1
	 */
	public $term;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		$this->variables = [
			'ID'               => [
				'label'       => __( 'ID', 'surerank' ),
				'description' => __( 'The unique identifier for a term.', 'surerank' ),
			],
			'term_title'       => [
				'label'       => __( 'Term Name', 'surerank' ),
				'description' => __( 'The name of the term.', 'surerank' ),
			],
			'term_description' => [
				'label'       => __( 'Description', 'surerank' ),
				'description' => __( 'The description of the term.', 'surerank' ),
			],
			'slug'             => [
				'label'       => __( 'Slug', 'surerank' ),
				'description' => __( 'The slug of the term.', 'surerank' ),
			],
			'permalink'        => [
				'label'       => __( 'Permalink', 'surerank' ),
				'description' => __( 'The permalink of the term.', 'surerank' ),
			],
		];
	}

	/**
	 * Get current term id
	 *
	 * @since 0.0.1
	 * @return int|false
	 */
	public function get_ID() {
		if ( ! empty( $this->term ) ) {
			return $this->term->term_id;
		}
		return false;
	}

	/**
	 * Get name of current term.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_term_title() {
		if ( ! empty( $this->term ) ) {
			return $this->term->name;
		}
		return '';
	}

	/**
	 * Get current term description.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_term_description() {
		if ( ! empty( $this->term ) ) {
			return Description::get_instance()->sanitize_description( $this->term->description );
		}
		return '';
	}

	/**
	 * Get slug of current term.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_slug() {
		if ( ! empty( $this->term ) ) {
			return $this->term->slug;
		}
		return '';
	}

	/**
	 * Returns permalink of current term.
	 *
	 * @since 0.0.1
	 * @return string|false
	 */
	public function get_permalink() {
		if ( ! empty( $this->term ) ) {
			$permalink = get_term_link( $this->term );
			return $permalink instanceof WP_Error ? false : $permalink;
		}
		return false;
	}

	/**
	 * This function sets $term variable, required for meta_variables based on term.
	 *
	 * @param int $term_id Term id to set $term variable to retrieve relevant variables.
	 * @since 0.0.1
	 * @return void
	 */
	public function set_term( $term_id = 0 ) {
		if ( ! empty( $term_id ) ) {
			$term       = get_term( $term_id );
			$this->term = $term instanceof WP_Error ? null : $term;
		}
	}

	/**
	 * Get the meta type for custom fields trait.
	 *
	 * @since 1.6.0
	 * @return string
	 */
	protected function get_meta_type() {
		return 'term';
	}
}
