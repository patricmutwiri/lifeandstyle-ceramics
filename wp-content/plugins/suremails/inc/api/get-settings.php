<?php
/**
 * Settings class
 *
 * Handles settings and configurations for the SureMails plugin, including retrieving,
 * updating settings, and cleaning up old email logs based on the retention period.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings
 *
 * @since 0.0.1
 */
class GetSettings extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/get-settings';

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
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Retrieves the current settings for SureMails.
	 *
	 * @return WP_REST_Response Returns an array with the current settings.
	 */
	public function get_settings() {

		$options = Settings::instance()->get_settings();
		$misc    = Settings::instance()->get_misc_settings();

			$options['emailSummaryDay'] = $misc['email_summary_day'] ?? 'monday';
			$options['emailSummary']    = $misc['email_summary_active'] ?? 'yes';

		$options['analytics'] = get_option( SetSettings::SUREMAILS_ANALYTICS, 'no' );

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $options,
			]
		);
	}

}

/**
 * Instantiate the Settings class to register actions and filters.
 */
GetSettings::instance();
