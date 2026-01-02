<?php
/**
 * Render
 *
 * This file will handle functionality for all Render.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

/**
 * Class Render
 *
 * Handles the rendering of dynamic values with placeholders in a structured format.
 */
class Render {
	/**
	 * Data collected for rendering.
	 *
	 * @var array<string, mixed>
	 */
	private $data;

	/**
	 * Render constructor.
	 *
	 * @param Data $data Data instance containing the values to be rendered.
	 */
	public function __construct( Data $data ) {
		$this->data = $data->collect();
	}

	/**
	 * Render the value, supporting both strings and arrays.
	 *
	 * @param mixed $value The value to render (string or array).
	 * @return void
	 */
	public function render( &$value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as &$item ) {
				$this->render( $item ); // Recursively render array items.
			}
			return;
		}

		if ( ! is_string( $value ) || ! str_contains( $value, '%' ) ) {
			return;
		}

		// Match variables like %post.title% or %schemas.article%.
		preg_match_all( '/%([^%\s]+)%/', $value, $matches );

		foreach ( $matches[1] as $match ) {
			$keys        = explode( '.', $match );
			$replacement = $this->resolve_variable( $keys, $this->data );

			if ( is_array( $replacement ) ) {
				$value = $replacement;
			} else {
				$value = str_replace( "%{$match}%", $replacement ?? '', $value );
			}

			// Check for additional variables introduced in the replacement and resolve recursively.
			$this->render( $value );
		}
	}

	/**
	 * Resolve a variable from a data source using dot notation keys.
	 *
	 * @param array<string, mixed>|array<int, string> $keys Array of keys to traverse the data structure.
	 * @param array<string, mixed>                    $data The data array to search within.
	 * @return mixed|null Resolved value or null if not found.
	 */
	private function resolve_variable( array $keys, array $data ) {
		$current = $data;
		foreach ( $keys as $key ) {
			if ( is_array( $current ) && isset( $current[ $key ] ) ) {
				$current = $current[ $key ];
			} elseif ( is_object( $current ) && isset( $current->$key ) ) {
				$current = $current->$key;
			} else {
				return null; // Key not found, return null.
			}
		}
		return $current;
	}
}
