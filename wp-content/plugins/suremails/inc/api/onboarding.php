<?php
/**
 * Onboarding Class
 *
 * Handles the REST API endpoint for retrieving onboarding information.
 *
 * Endpoint:
 *  - POST /onboarding: Set onboarding flag information.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Onboarding as OnboardingSettings;
use SureMails\Inc\Traits\Instance;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Onboarding
 *
 * @since 0.0.1
 */
class Onboarding extends Api_Base {

	use Instance;

	/**
	 * Base route for this API.
	 *
	 * @var string
	 */
	protected $rest_base = '/onboarding';

	/**
	 * Register API route for onboarding.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		// Endpoint to set onboarding flag information.
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base . '/set-status',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'set_onboarding_flag' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Set onboarding completion status.
	 *
	 * @since 0.0.1
	 * @return WP_REST_Response
	 */
	public function set_onboarding_flag() {
		// Set the onboarding status to yes always.
		OnboardingSettings::instance()->set_onboarding_status( 'yes' );

		return new WP_REST_Response( [ 'success' => true ] );
	}
}

// Initialize the Onboarding singleton.
Onboarding::instance();
