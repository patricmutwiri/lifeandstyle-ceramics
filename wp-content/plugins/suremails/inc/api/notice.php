<?php
/**
 * Disable admin notice API.
 *
 * @package SureMails\Inc\API
 * @since 1.7.0
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
 * Class Notice
 *
 * Handles the `/disable-notice` REST API endpoint.
 */
class Notice extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/disable-notice';

	/**
	 * Register API routes.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'handle_notice' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Disable admin notice for 15 days.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle_notice( $request ) {
		// Calculate “now + 15 days”.
		$expiry_time = time() + ( 1296000 );
		update_option( 'suremails_notice_dismissal_time', $expiry_time );

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Notice disabled for 15 days.', 'suremails' ),
			]
		);
	}
}

// Initialize the Notice singleton.
Notice::instance();
