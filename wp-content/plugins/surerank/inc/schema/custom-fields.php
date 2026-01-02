<?php
/**
 * Custom Fields Integration for Schema
 *
 * This file handles custom fields integration with schema variables and data.
 *
 * @package SureRank
 * @since 1.6.0
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Meta_Variables\Term;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Class Custom_Fields
 *
 * Integrates custom fields (including ACF) with schema variables and data.
 *
 * @package SureRank\Inc\Schema
 * @since 1.6.0
 */
class Custom_Fields {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * Initializes filters for custom fields integration.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		add_filter( 'surerank_default_schema_variables', [ $this, 'add_custom_field_variables' ], 10 );
		add_filter( 'surerank_schema_data', [ $this, 'add_custom_field_data' ], 10 );
	}

	/**
	 * Add custom field variables to schema variable selector
	 *
	 * @param array<string, string> $variables Existing variables.
	 *
	 * @since 1.6.0
	 * @return array<string, string>
	 */
	public function add_custom_field_variables( $variables ) {
		global $post;

		if ( $post ) {
			$post_instance = Post::get_instance();
			$post_instance->set_post( $post->ID );
			$variables = $this->process_custom_fields_for_variables( $post_instance->get_all_custom_fields(), $variables );
		}

		$queried_object = get_queried_object();
		if ( $queried_object instanceof \WP_Term ) {
			$term_instance = Term::get_instance();
			$term_instance->set_term( $queried_object->term_id );
			$variables = $this->process_custom_fields_for_variables( $term_instance->get_all_custom_fields(), $variables );
		}

		return $variables;
	}

	/**
	 * Add custom field data to schema data
	 *
	 * @param array<string, mixed> $data Existing schema data.
	 *
	 * @since 1.6.0
	 * @return array<string, mixed>
	 */
	public function add_custom_field_data( $data ) {
		$data['custom_field'] = [];

		if ( isset( $data['post']['ID'] ) ) {
			$post_instance = Post::get_instance();
			$post_instance->set_post( $data['post']['ID'] );
			$data['custom_field'] = $this->process_custom_fields_for_data( $post_instance->get_all_custom_fields() );
		}

		if ( isset( $data['term']['ID'] ) ) {
			$term_instance = Term::get_instance();
			$term_instance->set_term( $data['term']['ID'] );
			$data['custom_field'] = $this->process_custom_fields_for_data( $term_instance->get_all_custom_fields() );
		}

		return $data;
	}

	/**
	 * Process custom fields and add them to variables array.
	 *
	 * @param array<string, array{label: string, value: mixed}> $custom_fields Custom fields data.
	 * @param array<string, string>                             $variables    Existing variables.
	 * @since 1.6.0
	 * @return array<string, string>
	 */
	private function process_custom_fields_for_variables( array $custom_fields, array $variables ): array {
		foreach ( $custom_fields as $key => $field_data ) {
			$field_name                                  = str_replace( 'custom_field.', '', $key );
			$variables[ "%custom_field.{$field_name}%" ] = sprintf(
				/* translators: %s is replaced with the custom field label. */
				__( 'Custom Field: %s', 'surerank' ),
				$field_data['label']
			);
		}

		return $variables;
	}

	/**
	 * Process custom fields and return them as an array for data.
	 *
	 * @param array<string, array{label: string, value: mixed}> $custom_fields Custom fields data.
	 * @since 1.6.0
	 * @return array<string, mixed>
	 */
	private function process_custom_fields_for_data( array $custom_fields ): array {
		$fields = [];
		foreach ( $custom_fields as $key => $field_data ) {
			$field_name            = str_replace( 'custom_field.', '', $key );
			$fields[ $field_name ] = $field_data['value'];
		}

		return $fields;
	}
}
