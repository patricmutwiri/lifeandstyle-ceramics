<?php
/**
 * Initialize API.
 *
 * @package SureMails\Inc\API
 * @since 0.0.1
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Api_Init
 *
 * @since 0.0.1
 */
class Api_Init {
	use Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {
		// Register REST API routes.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register API routes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		$controllers = [
			'\SureMails\Inc\API\Logs',
			'\SureMails\Inc\API\SaveTestConnection',
			'\SureMails\Inc\API\SendTestEmail',
			'\SureMails\Inc\API\ResendEmail',
			'\SureMails\Inc\API\DeleteConnection',
			'\SureMails\Inc\API\DashboardData',
			'\SureMails\Inc\API\EmailStats',
			'\SureMails\Inc\API\DeleteLogs',
			'SureMails\Inc\API\GetSettings',
			'SureMails\Inc\API\SetSettings',
			'SureMails\Inc\API\RecommendedPlugin',
			'SureMails\Inc\API\ContentGuard',
			'SureMails\Inc\API\Provider',
			'SureMails\Inc\API\Auth',
			'SureMails\Inc\API\OttoKit',
			'SureMails\Inc\API\Onboarding',
			'SureMails\Inc\API\Notice',
		];

		foreach ( $controllers as $controller_class ) {
			if ( class_exists( $controller_class ) ) {
				$controller = $controller_class::instance();
				$controller->register_routes();
			}
		}
	}
}
