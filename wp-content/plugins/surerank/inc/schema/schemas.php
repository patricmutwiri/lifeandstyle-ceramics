<?php
/**
 * Schemas
 *
 * This file handles functionality for all Schemas.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * Schemas
 *
 * This class handles functionality for all Schemas.
 *
 * @since 1.0.0
 */
class Schemas {

	use Get_Instance;

	/**
	 * Schema Data
	 *
	 * @var array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	private $schema_data = [];

	/**
	 * Constructor
	 *
	 * Initializes schema data and sets up hooks for schema handling.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! self::should_load_schemas() ) {
			return;
		}

		add_filter( 'surerank_api_controllers', [ $this, 'add_schemas_apis' ] );
		add_filter( 'surerank_common_localization_vars', [ $this, 'add_localization_vars' ] );
		add_action( 'surerank_print_meta', [ $this, 'print_schema_data' ], 10 );
		add_action( 'wp', [ $this, 'set_schema_data' ], 1 );
		Products::get_instance();
		Custom_Fields::get_instance();
	}

	/**
	 * Add GSC APIs to the API controllers.
	 *
	 * @since 1.1.0
	 * @param array<int,string> $controllers List of API controllers.
	 * @return array<int,string> Updated list of API controllers.
	 */
	public function add_schemas_apis( $controllers ) {
		$controllers[] = '\SureRank\Inc\Schema\SchemasApi';
		return $controllers;
	}

	/**
	 * Add localisation variables
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $variables Localisation variables.
	 * @return array<string, mixed> Localisation variables.
	 */
	public function add_localization_vars( $variables ) {

		return array_merge(
			$variables,
			[
				'schema_rules'        => Rules::get_schema_rules_selections(),
				'default_schemas'     => Utils::get_default_schema_options(),
				'schema_type_options' => Utils::get_schema_type_options(),
				'schema_type_data'    => Utils::get_schema_type_data(),
				'schema_variables'    => Variables::get_instance()->get_schema_variables(),
			]
		);
	}

	/**
	 * Print Schema Data
	 *
	 * Outputs the schema data in JSON-LD format in the frontend.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_schema_data() {
		$schemas  = $this->get_active_schemas();
		$rendered = [];

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $schemas as $schema ) {
			if ( Validator::validate_schema_rules( $schema ) ) { // Validate schema rules.
				$type         = $schema['fields']['@type'] ?? $schema['type'] ?? 'Thing';
				$schema_class = Utils::get_schema_types()[ $schema['title'] ] ?? null;

				if ( $schema_class && class_exists( $schema_class ) ) {
					$schema_instance = $schema_class::get_instance();
					$rendered[]      = $schema_instance->render_schema( $schema, new Render( new Data() ) );
				}
			}
		}

		$schema_data = [
			'@context' => 'https://schema.org',
			'@graph'   => array_filter( $rendered ),
		];

		if ( empty( $schema_data['@graph'] ) ) {
			return;
		}

		echo '<script type="application/ld+json" id="surerank-schema">' . wp_json_encode( $schema_data, $this->get_wp_json_encode_flags() ) . '</script>';
	}

	/**
	 * Get wp_json_encode Flags
	 *
	 * Retrieves the wp_json_encode 2nd parameter flag as per debug mode.
	 *
	 * @return int wp_json_encode 2nd parameter flag.
	 * @since 1.0.0
	 */
	public function get_wp_json_encode_flags() {
		if ( defined( 'SURERANK_DEBUG' ) && SURERANK_DEBUG ) {
			return JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		}
		return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
	}

	/**
	 * Get Active Schemas
	 *
	 * Retrieves the active schemas based on settings.
	 *
	 * @return array<mixed> Active schemas.
	 * @since 1.0.0
	 */
	public function get_active_schemas() {

		$post_schema   = $this->get_post_schema(); // Get post schema.
		$global_schema = $this->get_global_schema(); // Get global schema.

		if ( ! empty( $post_schema ) ) {
			return $post_schema;
		}

		if ( ! empty( $global_schema ) ) {
			return $global_schema;
		}

		return [];
	}

	/**
	 * Set Schema Data
	 *
	 * Sets schema data through a WordPress filter.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_schema_data() {
		$this->schema_data = apply_filters( 'surerank_set_schema', $this->schema_data );
	}

	/**
	 * Get Post Schema
	 *
	 * Retrieves the schema data for a post.
	 *
	 * @return array<string, mixed> Post schema data.
	 * @since 1.0.0
	 */
	public function get_post_schema() {

		$object      = get_queried_object();
		$schema_data = [];

		if ( $object instanceof WP_Post ) {
			$post_id     = $object->ID;
			$schema_data = Get::post_meta( $post_id, 'surerank_settings_schemas', true );
		} elseif ( $object instanceof WP_Term ) {
			$post_id     = $object->term_id;
			$schema_data = Get::term_meta( $post_id, 'surerank_settings_schemas', true );
		} elseif ( $object instanceof WP_User ) {
			$post_id     = $object->ID;
			$schema_data = Get::user_meta( $post_id, 'surerank_settings_schemas', true );
		} else {
			return [];
		}

		$post_schema = $schema_data['schemas'] ?? [];
		return ! empty( $post_schema ) ? $post_schema : [];
	}

	/**
	 * Get Global Schema
	 *
	 * Retrieves the global schema data.
	 *
	 * @return array<string, mixed> Global schema data.
	 * @since 1.0.0
	 */
	public function get_global_schema() {

		$global_schema = Settings::get();
		$global_schema = $global_schema['schemas'] ?? Utils::get_default_schemas();

		if ( ! empty( $global_schema ) ) {
			return $global_schema;
		}

		return [];
	}

	/**
	 * Check if SureRank schemas should be loaded.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public static function should_load_schemas() {
		if ( ! Settings::get( 'enable_schemas' ) ) {
			return false;
		}

		if ( ! apply_filters( 'surerank_print_schema', true ) ) {
			return false;
		}

		if ( Helper::is_wp_schema_pro_active() ) {
			return false;
		}

		return true;
	}
}
