<?php
/**
 * Email Reports Init class
 *
 * Handles the initialization and hooks for email reports functionality.
 *
 * @package SureRank\Inc\Modules\EmailReports
 * @since 1.6.0
 */

namespace SureRank\Inc\Modules\EmailReports;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Init class
 *
 * Handles initialization and WordPress hooks for email reports functionality.
 */
class Init {



	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.6.0
	 */
	public function __construct() {
		Controller::get_instance();
		add_filter( 'surerank_api_controllers', [ $this, 'register_api_controller' ], 20 );
	}

	/**
	 * Register API controller for this module.
	 *
	 * @param array<string> $controllers Existing controllers.
	 * @return array<string> Updated controllers.
	 * @since 1.6.0
	 */
	public function register_api_controller( $controllers ) {
		$controllers[] = '\SureRank\Inc\Modules\EmailReports\Api';
		return $controllers;
	}
}
