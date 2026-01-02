<?php
/**
 * Third-party plugins initialization
 *
 * This file handles initialization of all third-party plugin integrations.
 *
 * @package surerank
 * @since 1.5.0
 */

namespace SureRank\Inc\ThirdPartyIntegrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Traits\Get_Instance;

/**
 * Third-party plugins initialization class
 *
 * Manages loading of all integrations with third-party plugins.
 *
 * @since 1.5.0
 */
class Init {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			$this->load_admin_integrations();
		}
		$this->load_frontend_integrations();
	}

	/**
	 * Load admin-specific integrations
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function load_admin_integrations(): void {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			Elementor::get_instance();
		}

		Avada_Fusion_Builder::get_instance();
	}

	/**
	 * Load frontend-specific integrations
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function load_frontend_integrations(): void {
		Bricks::get_instance();
		Woocommerce::get_instance();
		Angie::get_instance();
		CartFlows::get_instance();
	}
}
