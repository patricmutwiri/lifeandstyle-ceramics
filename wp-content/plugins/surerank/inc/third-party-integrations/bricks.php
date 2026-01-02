<?php
/**
 * Third Party Plugins class - Bricks
 *
 * Handles Bricks Plugin related compatibility.
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
 * Class Bricks
 *
 * Handles Bricks Plugin related compatibility.
 */
class Bricks {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return;
		}
		if ( ! function_exists( 'bricks_is_builder_main' ) || ! bricks_is_builder_main() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', [ $this, 'register_script' ], 9999 );
		add_action( 'wp_enqueue_scripts', [ Dashboard::get_instance(), 'site_seo_check_enqueue_scripts' ], 999 );
		add_filter( 'surerank_globals_localization_vars', [ $this, 'add_localization_vars' ] );
	}

	/**
	 * Add localization variables for Bricks.
	 *
	 * @param array<string,mixed> $vars Localization variables.
	 * @return array<string,mixed> Updated localization variables.
	 * @since 1.1.0
	 */
	public function add_localization_vars( array $vars ) {
		return array_merge(
			$vars,
			[
				'is_bricks' => true,
			]
		);
	}

	/**
	 * Register Script
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function register_script() {
		Seo_Popup::get_instance()->admin_enqueue_scripts();

		$asset_path = SURERANK_DIR . 'build/bricks/index.asset.php';
		$asset_info = file_exists( $asset_path ) ? include $asset_path : [
			'dependencies' => [ 'jquery', 'wp-data' ],
			'version'      => SURERANK_VERSION,
		];

		wp_register_script( 'surerank-bricks', SURERANK_URL . 'build/bricks/index.js', $asset_info['dependencies'], $asset_info['version'], false );
		wp_enqueue_script( 'surerank-bricks' );
	}
}
