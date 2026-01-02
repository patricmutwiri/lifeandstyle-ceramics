<?php
/**
 * Install_Products class
 *
 * Handles installed products related REST API endpoints for the SureRank plugin.
 *
 * @package SureRank\Inc\API
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Install_Products
 *
 * Handles installed products related REST API endpoints.
 */
class Install_Products extends Api_Base {
	use Get_Instance;

	/**
	 * Route Get Installed Plugins and Themes
	 */
	protected const INSTALLED_PLUGINS_AND_THEMES = '/plugins/installed';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			self::INSTALLED_PLUGINS_AND_THEMES,
			[
				'methods'             => WP_REST_Server::READABLE, // GET method.
				'callback'            => [ $this, 'get_installed_plugins_and_themes' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
	}

	/**
	 * Get the list of installed plugins and themes and active plugins and themes.
	 *
	 * @return void
	 */
	public function get_installed_plugins_and_themes() {
		// Include necessary WordPress files for plugin functions.
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get all installed plugins.
		$all_plugins       = get_plugins();
		$installed_plugins = [];
		$active_plugins    = [];

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$slug                = dirname( $plugin_file );
			$installed_plugins[] = $slug;

			if ( is_plugin_active( $plugin_file ) ) {
				$active_plugins[] = $slug;
			}
		}

		// Get all installed themes.
		$all_themes       = wp_get_themes();
		$installed_themes = [];
		$active_theme     = get_stylesheet();
		$active_themes    = [];

		foreach ( $all_themes as $theme_slug => $theme_data ) {
			$installed_themes[] = $theme_slug;

			if ( $theme_slug === $active_theme ) {
				$active_themes[] = $theme_slug;
			}
		}

		Send_Json::success(
			[
				'success' => true,
				'data'    => [
					'plugins' => [
						'installed' => $installed_plugins,
						'active'    => $active_plugins,
					],
					'themes'  => [
						'installed' => $installed_themes,
						'active'    => $active_themes,
					],
				],
			]
		);
	}
}
