<?php
/**
 * OttoKit class
 *
 * Handles the REST API endpoint to get email statistics.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Traits\Instance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class OttoKit
 *
 * Handles the `/ottokit-status` REST API endpoint.
 */
class OttoKit extends Api_Base {

	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/ottokit-status';

	/**
	 * Register API routes.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_ottokit_status' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Retrieves email statistics (total sent and failed emails) for a given date range.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object containing the selected dates.
	 * @return WP_REST_Response The REST response object with email statistics or an error message.
	 * @since 1.6.0
	 */
	public function get_ottokit_status( $request ) {
		$ottokit_status = apply_filters( 'suretriggers_is_user_connected', '' );

		// Prepare the response.
		return rest_ensure_response(
			[
				'success' => true,
				'data'    => [
					'ottokit_status' => $ottokit_status,
				],
			]
		);
	}
}

// Initialize the OttoKit singleton.
OttoKit::instance();
