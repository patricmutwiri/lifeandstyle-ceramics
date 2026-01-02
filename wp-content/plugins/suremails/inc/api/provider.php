<?php
/**
 * Provider Class
 *
 * Handles the REST API endpoint for retrieving provider information.
 *
 * Endpoint:
 *  - GET /providers: Retrieve all providers or a specific provider based on the `provider` query parameter.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Providers;
use SureMails\Inc\Traits\Instance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Provider
 *
 * @since 0.0.1
 */
class Provider extends Api_Base {

	use Instance;

	/**
	 * Base route for this API.
	 *
	 * @var string
	 */
	protected $rest_base = '/providers';

	/**
	 * Register API route for providers.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		// Endpoint to get provider options.
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_providers' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'provider' => [
							'required'    => false,
							'type'        => 'string',
							'description' => __( 'Optional. Specify the provider key to retrieve details of a specific provider.', 'suremails' ),
						],
					],
				],
			]
		);
	}

	/**
	 * Retrieve provider information.
	 *
	 * Endpoint: GET /get-providers?provider={provider}
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return WP_REST_Response Response containing provider options or all providers.
	 */
	public function get_providers( $request ) {
		$provider = strtolower( sanitize_text_field( $request->get_param( 'provider' ) ) );

		try {

			if ( empty( $provider ) ) {
				$providers = Providers::instance()->get_provider_options();
			} else {
				$providers = Providers::instance()->get_provider_options( $provider );
			}

			if ( empty( $provider ) ) {
				return new WP_REST_Response(
					[
						'success' => true,
						'data'    => [ 'providers' => $providers ],
					],
					200
				);
			}

			if ( ! empty( $providers[ $provider ] ) ) {

				return new WP_REST_Response(
					[
						'success' => true,
						'data'    => $providers[ $provider ],
					],
					200
				);
			}

			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Provider not found.', 'suremails' ),
				],
				404
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}
}

Provider::instance();
