<?php
/**
 * Initialize API.
 *
 * @package SureRank\Inc\API
 * @since 1.0.0
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Api_Init
 *
 * @since 1.0.0
 */
class Api_Init {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Register REST API routes.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		$controllers = [
			'\SureRank\Inc\API\Admin',
			'\SureRank\Inc\API\Post',
			'\SureRank\Inc\API\Install_Products',
			'\SureRank\Inc\API\Term',
			'\SureRank\Inc\API\Onboarding',
			'\SureRank\Inc\API\Analyzer',
			'\SureRank\Inc\API\Migrations',
			'\SureRank\Inc\API\Import_Export_Settings',
			'\SureRank\Inc\API\Sitemap',
			'\SureRank\Inc\API\RobotsTxt',
		];

		$controllers = apply_filters( 'surerank_api_controllers', $controllers );

		foreach ( $controllers as $controller_class ) {
			if ( class_exists( $controller_class ) ) {
				$controller = $controller_class::get_instance();
				$controller->register_routes();
			}
		}
	}
}
