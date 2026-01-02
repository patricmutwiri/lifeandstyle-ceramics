<?php
/**
 * Nudges Init class
 *
 * Handles the initialization and hooks for our pro nudges functionality.
 *
 * @package SureRank\Inc\Modules\Nudges
 * @since 1.5.0
 */

namespace SureRank\Inc\Modules\Nudges;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Init class
 *
 * Handles initialization and WordPress hooks for our pro nudges functionality.
 */
class Init {



	use Get_Instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'surerank_api_controllers', [ $this, 'register_api_controller' ], 20 );
		add_filter( 'surerank_globals_localization_vars', [ $this, 'add_localization_vars' ] );
	}

	/**
	 * Add localization variables for nudges.
	 *
	 * @param array<string,mixed> $vars Localization variables.
	 * @return array<string,mixed> Updated localization variables.
	 * @since 1.5.0
	 */
	public function add_localization_vars( array $vars ) {
		return array_merge(
			$vars,
			[
				'is_pro_active' => Utils::get_instance()->is_pro_active(),
			]
		);
	}

	/**
	 * Register API controller for this module.
	 *
	 * @param array<string> $controllers Existing controllers.
	 * @return array<string> Updated controllers.
	 * @since 1.5.0
	 */
	public function register_api_controller( $controllers ) {
		$controllers[] = '\SureRank\Inc\Modules\Nudges\Api';
		return $controllers;
	}
}
