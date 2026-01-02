<?php
/**
 * FixSeoChecks Init class
 *
 * Handles the initialization and hooks for our research functionality.
 *
 * @package SureRank\Inc\Modules\Fix_Seo_Checks
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Fix_Seo_Checks;

use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Modules\Fix_Seo_Checks\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Init class
 *
 * Handles initialization and WordPress hooks for our research functionality.
 */
class Init {

	use Get_Instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		Page::get_instance();

		add_filter( 'surerank_api_controllers', [ $this, 'register_api_controller' ], 20 );
	}

	/**
	 * Register API controller for this module.
	 *
	 * @param array<string> $controllers Existing controllers.
	 * @return array<string> Updated controllers.
	 * @since 1.4.2
	 */
	public function register_api_controller( $controllers ) {
		$controllers[] = '\SureRank\Inc\Modules\Fix_Seo_Checks\Api';
		return $controllers;
	}
}
