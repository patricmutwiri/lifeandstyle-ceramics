<?php
/**
 * Meta Variables
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Meta_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This is the base class for all meta_variables classes.
 * All meta_variables classes will extend this class.
 * Handles common function required for data generation.
 *
 * @since 0.0.1
 */
class Variables {

	/**
	 * Variables
	 *
	 * @var array<string, mixed>
	 */
	public $variables = [];

	/**
	 * Category
	 *
	 * @var string
	 */
	public $category = '';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Check if variables are empty.
	 *
	 * @since 0.0.1
	 * @return bool
	 */
	public function check_empty_variables() {
		if ( empty( $this->variables ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get values of all variables in a class
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_all_values() {
		if ( ! $this->check_empty_variables() ) {
			return [];
		}

		$new_variables = [];
		foreach ( $this->variables as $key => $variable ) {
			$new_variables = $this->create_new_variables( $new_variables, $key );
		}
		return $new_variables;
	}

	/**
	 * Get value of specific key in a class
	 * for example `ID` from `Post` class.
	 *
	 * @param string $key Key.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_key_value( $key ) {
		if ( ! $this->check_empty_variables() ) {
			return [];
		}

		if ( ! isset( $this->variables[ $key ] ) ) {
			return [];
		}

		$new_variables = [];
		return $this->create_new_variables( $new_variables, $key );
	}

	/**
	 * Create new variables
	 *
	 * @param array<string, mixed> $new_variables New variables.
	 * @param string               $key           Key.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function create_new_variables( $new_variables, $key ) {
		$value = $this->get_variable_value( $key );
		if ( false !== $value ) {
			$this->variables[ $key ]['value'] = $value;
			$new_variables[ $key ]            = $this->variables[ $key ];
		}
		return $new_variables;
	}

	/**
	 * Get variable value
	 *
	 * @param string $key Key.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_variable_value( $key ) {
		$method = "get_{$key}";
		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}
		return false;
	}

}
