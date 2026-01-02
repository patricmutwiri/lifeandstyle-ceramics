<?php
/**
 * Instant Indexing API class
 *
 * Handles instant indexing related REST API endpoints for IndexNow and Google Submit URL.
 *
 * @package SureRank\Inc\Modules\Ai_Auth
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Ai_Auth;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\API\Api_Base;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Api
 *
 * Handles instant indexing related REST API endpoints.
 */
class Api extends Api_Base {
	use Get_Instance;

	/**
	 * Register API routes.
	 *
	 * @since 1.4.2
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			'/ai/auth',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'verify_auth' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'accessKey' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$this->get_api_namespace(),
			'/ai/auth',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_auth_url' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
	}

	/**
	 * Verify Auth
	 *
	 * @since 1.4.2
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return void
	 */
	public function verify_auth( $request ) {
		$access_key = $request->get_param( 'accessKey' ) ?? '';

		if ( empty( $access_key ) ) {
			Send_Json::error( [ 'message' => __( 'No access key provided.', 'surerank' ) ] );
		}
		
		$saved = Controller::get_instance()->save_auth( $access_key, Controller::get_instance()->key );

		if ( is_wp_error( $saved ) && $saved instanceof WP_Error ) {
			Send_Json::error( [ 'message' => $saved->get_error_message() ] );
		}

		if ( $saved === false ) {
			Send_Json::error( [ 'message' => __( 'Failed to save authentication data.', 'surerank' ) ] );
		}
		
		Send_Json::success( [ 'message' => __( 'Authentication data saved.', 'surerank' ) ] );
	}

	/**
	 * Submit URLs to IndexNow API.
	 *
	 * @since 1.4.2
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return void
	 */
	public function get_auth_url( $request ) {
		if ( Controller::get_instance()->get_auth_status() ) {
			Send_Json::success( [ 'message' => __( 'Authentication is already completed.', 'surerank' ) ] );
		}

		$auth = Controller::get_instance()->get_auth_url();

		if ( is_wp_error( $auth ) && $auth instanceof WP_Error ) {
			Send_Json::error( [ 'message' => $auth->get_error_message() ] );
		} else {
			Send_Json::success( [ 'auth_url' => $auth ] );
		}

	}
}
