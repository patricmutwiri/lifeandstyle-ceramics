<?php
/**
 * FixSeoChecks API class
 *
 * Handles optimize related REST API endpoints.
 *
 * @package SureRank\Inc\Modules\Fix_Seo_Checks
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Fix_Seo_Checks;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\API\Api_Base;
use SureRank\Inc\Functions\Requests;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Api
 *
 * Handles optimize related REST API endpoints.
 */
class Api extends Api_Base {
	use Get_Instance;

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			'/page-seo-checks/fix',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'page_check_fix' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'type'        => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'enum'              => Page::get_instance()->get_fix_it_types(),
					],
					'input_key'   => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'input_value' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'id'          => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'is_taxonomy' => [
						'required'          => false,
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
				],
			]
		);
	}

	/**
	 * Get the list of keyword research.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @return void
	 */
	public function page_check_fix( $request ) {
		$type = $request->get_param( 'type' );

		$site_seo_checks = Page::get_instance();

		$response = null;

		$input_key   = $request->get_param( 'input_key' );
		$input_value = $request->get_param( 'input_value' );
		$id          = $request->get_param( 'id' );
		$is_taxonomy = $request->get_param( 'is_taxonomy' );
		$response    = $site_seo_checks->use_me( $input_key, $input_value, $id, $is_taxonomy );


		// Handle WP_Error responses.
		if ( is_wp_error( $response ) && $response instanceof WP_Error ) {
			Send_Json::error(
				[
					'message' => $response->get_error_message(),
					'type'    => $response->get_error_data()['type'] ?? $type,
				] 
			);
			return;
		}

		// Handle array responses.
		if ( is_array( $response ) && $response['status'] === true ) {
			Send_Json::success( $response );
		} else {
			Send_Json::error( [ 'message' => __( 'An unexpected error occurred. Please try again.', 'surerank' ) ] );
		}
	}
}
