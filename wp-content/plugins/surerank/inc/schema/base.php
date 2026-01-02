<?php
/**
 * Base Schema Type
 *
 * This file provides a base class for schema types.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

/**
 * Base Schema Type
 *
 * Provides a base class for defining schema types.
 * Child classes must implement the `get` and `schema_data` methods.
 *
 * @package SureRank
 * @since 1.0.0
 */
abstract class Base {

	/**
	 * Get schema variables.
	 *
	 * This method should be implemented by child classes to define schema fields.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	abstract public function get();

	/**
	 * Get schema data.
	 *
	 * @return array<string, mixed>
	 * @since 1.0.0
	 */
	abstract public function schema_data();

	/**
	 * Render schema data with dynamic content
	 *
	 * @param array<string, mixed> $schema Schema configuration.
	 * @param Render               $renderer Variable renderer instance.
	 * @return array<string, mixed> Rendered schema data.
	 * @since 1.2.0
	 */
	public function render_schema( $schema, $renderer ) {
		$fields = Data::get_schema_type( $schema );
		$type   = $schema['fields']['@type'] ?? $schema['type'] ?? 'Thing';

		$schema_render = new Schema_Render( $type, $fields, $renderer );
		return $schema_render->render();
	}

	/**
	 * Get schema type options.
	 *
	 * @return array<string, mixed>
	 */
	public function get_schema_type_options() {
		return [];
	}

	/**
	 * Add a helper property to the schema.
	 *
	 * @param string               $id The property ID.
	 * @param array<string, mixed> $args Arguments for the property.
	 *
	 * @return array<string, mixed>
	 */
	protected function add_helper_property( $id, $args = [] ) {
		return Helper::get_instance()->get_property( $id, $args );
	}

	/**
	 * Parse Schema Fields
	 *
	 * Parses schema fields recursively to include only required data.
	 *
	 * @param array<int, array<string, mixed>> $fields Schema fields.
	 * @return array<string, mixed> Parsed fields.
	 */
	protected function parse_fields( $fields ) {
		$parsed = [];
		foreach ( $fields as $field ) {
			if ( empty( $field['id'] ) || ( empty( $field['required'] ) && empty( $field['show'] ) ) ) {
				continue;
			}

			$type                   = $field['type'] ?? 'Text';
			$value                  = 'Group' === $type ? $this->parse_group( $field ) : ( $field['std'] ?? '' );
			$parsed[ $field['id'] ] = $value;

		}
		return $parsed;
	}

	/**
	 * Parse Group Fields
	 *
	 * Parses group fields recursively.
	 *
	 * @param array<string, mixed> $field Group field data.
	 * @return array<string, mixed>|array<int, array<string, mixed>> Parsed group fields.
	 */
	protected function parse_group( $field ) {
		$group_fields = $this->parse_fields( $field['fields'] );
		if ( 1 === count( $group_fields ) && isset( $group_fields['@type'] ) ) { // Yoda condition.
			return [];
		}

		if ( ! empty( $field['cloneable'] ) ) {
			return [ $group_fields ];
		}

		return $group_fields;
	}
}
