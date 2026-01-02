<?php
/**
 * Auth class
 *
 * Handles authentication API requests for the SureMails plugin.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Emails\Providers\GMAIL\GmailHandler;
use SureMails\Inc\Emails\Providers\ZOHO\ZohoHandler;
use SureMails\Inc\Traits\Instance;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Auth
 *
 * @since 0.0.1
 */
class Auth extends Api_Base {

	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/get-auth-url';

	/**
	 * Register API routes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		$namespace = $this->get_api_namespace();

		register_rest_route(
			$namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE, // POST method.
					'callback'            => [ $this, 'get_auth_url' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Retrieves the auth URL based on the provider.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request The REST request instance.
	 * @return WP_REST_Response Returns the auth URL or an error.
	 */
	public function get_auth_url( $request ): WP_REST_Response {
		$params = $request->get_json_params();

		$provider     = isset( $params['provider'] ) ? sanitize_text_field( $params['provider'] ) : '';
		$provider_key = strtolower( $provider );

		if ( ! in_array( $provider_key, array_keys( $this->get_supported_providers() ), true ) ) {
			return new WP_REST_Response( [ 'error' => __( 'Unsupported provider.', 'suremails' ) ], 400 );
		}

		$supported_providers = $this->get_supported_providers();
		$handler_class       = $supported_providers[ $provider_key ];
		$response            = $handler_class::get_auth_url( $params );

		if ( isset( $response['error'] ) ) {
			return new WP_REST_Response( $response, 400 );
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get supported OAuth providers
	 *
	 * @return array
	 */
	private function get_supported_providers() {
		return [
			'gmail' => GmailHandler::class,
			'zoho'  => ZohoHandler::class,
		];
	}

}

// Instantiate the Auth class to register the routes.
Auth::instance();
