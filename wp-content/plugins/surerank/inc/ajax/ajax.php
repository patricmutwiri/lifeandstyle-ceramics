<?php
/**
 * SureRank Ajax initialize.
 *
 * @package SureRank\Ajax
 */

namespace SureRank\Inc\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Traits\Get_Instance;

/**
 * Ajax class.
 *
 * @package SureRank\Inc\Ajax
 */
class Ajax {
	use Get_Instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_surerank_activate_plugin', [ $this, 'activate_plugin' ] );
		add_action( 'wp_ajax_surerank_activate_theme', [ $this, 'activate_theme' ] );
	}

	/**
	 * Handle plugin activation.
	 *
	 * @return void
	 * @since 0.0.2
	 */
	public function activate_plugin() {
		// Check ajax referer.
		check_ajax_referer( 'surerank_plugin', '_ajax_nonce' );

		// Check if the request is an ajax request and early return if not.
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'Not an ajax request.', 'surerank' ),
				],
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'You do not have permission to activate plugins.', 'surerank' ),
				],
			);
		}

		// Get plugin slug from request.
		$plugin_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'No plugin specified.', 'surerank' ),
				],
			);
		}

		// Disable redirection to plugin page after activation.
		add_filter( 'wp_redirect', '__return_false' );

		// Activate the plugin.
		$result = activate_plugin( $plugin_slug );

		// Check if activation was successful.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => $result->get_error_message(),
				],
			);
		}

		// Clear plugins cache.
		wp_clean_plugins_cache();

		// Send success response.
		wp_send_json_success(
			[
				'success' => true,
				'message' => __( 'Plugin activated successfully.', 'surerank' ),
			],
		);
	}

	/**
	 * Handle theme activation.
	 *
	 * @return void
	 * @since 0.0.2
	 */
	public function activate_theme() {
		// Check ajax referer.
		check_ajax_referer( 'surerank_plugin', '_ajax_nonce' );

		// Check if the request is an ajax request and early return if not.
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'Not an ajax request.', 'surerank' ),
				],
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'customize' ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'You do not have permission to activate themes.', 'surerank' ),
				],
			);
		}

		// Get theme slug from request.
		$theme_stylesheet = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $theme_stylesheet ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'No theme specified.', 'surerank' ),
				],
			);
		}

		// Activate the theme.
		switch_theme( $theme_stylesheet );

		// Send success response.
		wp_send_json_success(
			[
				'success' => true,
				'message' => __( 'Theme activated successfully.', 'surerank' ),
			],
		);
	}
}
