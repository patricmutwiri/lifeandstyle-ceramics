<?php
/**
 * Third Party Plugins class - Elementor
 *
 * Handles Elementor Plugin related compatibility.
 *
 * @package SureRank\Inc\ThirdPartyIntegrations
 */

namespace SureRank\Inc\ThirdPartyIntegrations;

use SureRank\Inc\Admin\Dashboard;
use SureRank\Inc\Admin\Seo_Popup;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Elementor
 *
 * Handles Elementor Plugin related compatibility.
 */
class Elementor {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'register_script' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ Dashboard::get_instance(), 'site_seo_check_enqueue_scripts' ], 999 );
		// Add meta box trigger in the Elementor editor.
		add_action( 'elementor/editor/before_enqueue_scripts', [ Seo_Popup::get_instance(), 'add_meta_box_trigger' ], 5 );
		// Enqueue admin scripts in the Elementor editor.
		add_action( 'elementor/editor/after_enqueue_scripts', [ Seo_Popup::get_instance(), 'admin_enqueue_scripts' ] );
	}

	/**
	 * Register Script
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_script() {
		wp_register_script( 'surerank-elementor', SURERANK_URL . 'build/elementor/index.js', [ 'jquery', 'wp-data' ], SURERANK_VERSION, false );
		wp_enqueue_style( 'surerank-elementor-tooltip', SURERANK_URL . 'build/elementor/style.css', [], SURERANK_VERSION );
		wp_enqueue_script( 'surerank-elementor' );
	}
}
