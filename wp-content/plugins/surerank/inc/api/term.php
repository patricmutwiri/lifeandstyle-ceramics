<?php
/**
 * Term class
 *
 * Handles term related REST API endpoints for the SureRank plugin.
 *
 * @package SureRank\Inc\API
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Functions\Defaults;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Term
 *
 * Handles term related REST API endpoints.
 */
class Term extends Api_Base {
	use Get_Instance;

	/**
	 * Route Get Term Seo Data
	 */
	protected const TERM_SEO_DATA = '/term/settings';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		$namespace = $this->get_api_namespace();
		$this->register_all_term_routes( $namespace );
	}

	/**
	 * Get term seo data
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @since 1.0.0
	 * @return void
	 */
	public function get_term_seo_data( $request ) {

		$term_id     = $request->get_param( 'term_id' );
		$post_type   = $request->get_param( 'post_type' );
		$is_taxonomy = $request->get_param( 'is_taxonomy' );

		$data = self::get_term_data_by_id( $term_id, $post_type, $is_taxonomy );

		Send_Json::success( $data );
	}

	/**
	 * Get term data by id
	 *
	 * @param int    $term_id Term ID.
	 * @param string $post_type Post type.
	 * @param bool   $is_taxonomy Is taxonomy.
	 * @return array<string, mixed>
	 */
	public static function get_term_data_by_id( $term_id, $post_type, $is_taxonomy ) {
		$all_options            = Settings::format_array( Defaults::get_instance()->get_post_defaults( false ) );
		$data                   = array_intersect_key( Settings::prep_term_meta( $term_id, $post_type, $is_taxonomy ), $all_options );
		$decode_data            = Utils::decode_html_entities_recursive( $data ) ?? $data;
		$global_defaults        = Settings::get();
		$decode_global_defaults = Utils::decode_html_entities_recursive( $global_defaults ) ?? $global_defaults;
		return [
			'data'           => $decode_data,
			'global_default' => $decode_global_defaults,
		];
	}

	/**
	 * Update seo data
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @since 1.0.0
	 * @return void
	 */
	public function update_term_seo_data( $request ) {

		$term_id = $request->get_param( 'term_id' );
		$data    = $request->get_param( 'metaData' );

		self::update_term_meta_common( $term_id, $data );

		if ( is_wp_error( $this->run_checks( $term_id ) ) ) {
			Send_Json::error( [ 'message' => __( 'Error while running SEO Checks.', 'surerank' ) ] );
		}

		Send_Json::success( [ 'message' => __( 'Data updated', 'surerank' ) ] );
	}

	/**
	 * Common method to process and update term meta data
	 *
	 * @param int                  $term_id Term ID to update.
	 * @param array<string, mixed> $data Data to update.
	 * @return void
	 */
	public static function update_term_meta_common( int $term_id, array $data ) {
		$all_options = Defaults::get_instance()->get_post_defaults( false );
		/** Getting post meta if exists, otherwise getting all options(defaults) */
		$term_meta = Get::all_term_meta( $term_id );
		if ( ! empty( $term_meta ) ) {
			$data = array_merge( $term_meta, $data );
		}

		$processed_options = Utils::process_option_values( $all_options, $data );

		foreach ( $processed_options as $option_name => $new_option_value ) {
			Update::term_meta( $term_id, 'surerank_settings_' . $option_name, $new_option_value );
		}
	}

	/**
	 * Run checks
	 *
	 * @param int $term_id Term ID.
	 * @return WP_Error|array<string, mixed>
	 */
	public function run_checks( $term_id ) {
		if ( ! $term_id ) {
			return new WP_Error( 'no_term_id', __( 'No term ID provided.', 'surerank' ) );
		}

		$term = get_term( $term_id );

		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'no_term', __( 'No term found.', 'surerank' ) );
		}

		return apply_filters( 'surerank_run_term_seo_checks', $term_id, $term );
	}

	/**
	 * Register all term routes
	 *
	 * @param string $namespace The API namespace.
	 * @return void
	 */
	private function register_all_term_routes( $namespace ) {
		$this->register_get_term_seo_data_route( $namespace );
		$this->register_update_term_seo_data_route( $namespace );
	}

	/**
	 * Register get term SEO data route
	 *
	 * @param string $namespace The API namespace.
	 * @return void
	 */
	private function register_get_term_seo_data_route( $namespace ) {
		register_rest_route(
			$namespace,
			self::TERM_SEO_DATA,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_term_seo_data' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => $this->get_term_seo_data_args(),
			]
		);
	}

	/**
	 * Register update term SEO data route
	 *
	 * @param string $namespace The API namespace.
	 * @return void
	 */
	private function register_update_term_seo_data_route( $namespace ) {
		register_rest_route(
			$namespace,
			self::TERM_SEO_DATA,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_term_seo_data' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => $this->get_update_term_seo_data_args(),
			]
		);
	}

	/**
	 * Get term SEO data arguments
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_term_seo_data_args() {
		return [
			'term_id'   => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			],
			'post_type' => [
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Get update term SEO data arguments
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_update_term_seo_data_args() {
		return [
			'term_id'  => [
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			],
			'metaData' => [
				'type'              => 'object',
				'required'          => true,
				'sanitize_callback' => [ $this, 'sanitize_array_data' ],
			],
		];
	}
}
