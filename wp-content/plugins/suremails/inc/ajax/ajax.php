<?php
/**
 * SureMails Ajax initialize.
 *
 * @package SureMails\Ajax
 */

namespace SureMails\Inc\Ajax;

use SureMails\Inc\Traits\Instance;

/**
 * Ajax class.
 *
 * @package SureMails\Inc\Ajax
 */
class Ajax {
	use Instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_suremails-activate_plugin', [ $this, 'handle_activate_plugin' ] );
		add_action( 'wp_ajax_suremails-activate_theme', [ $this, 'activate_theme' ] );
	}

	/**
	 * Handle plugin activation.
	 *
	 * @return void
	 * @since 0.0.2
	 */
	public function handle_activate_plugin() {
		// Check ajax referer.
		check_ajax_referer( 'suremails_plugin', '_ajax_nonce' );

		// Check if the request is an ajax request and early return if not.
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'Not an ajax request.', 'suremails' ),
				],
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'You do not have permission to activate plugins.', 'suremails' ),
				],
			);
		}

		// Get plugin slug from request.
		$plugin_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'No plugin specified.', 'suremails' ),
				],
			);
		}

		// Disable redirection to plugin page after activation.
		add_filter( 'wp_redirect', '__return_false' );

		// Activate the plugin.
		$result = activate_plugin( $plugin_slug, '', false, true );

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
		if ( class_exists( '\BSF_UTM_Analytics' ) && is_callable( '\BSF_UTM_Analytics::update_referer' ) ) {
			$plugin_slug = pathinfo( $plugin_slug, PATHINFO_FILENAME ); // Retrives the plugin slug from the init.
			\BSF_UTM_Analytics::update_referer( 'suremails', $plugin_slug );
		}

		// Send success response.
		wp_send_json_success(
			[
				'success' => true,
				'message' => __( 'Plugin activated successfully.', 'suremails' ),
			]
		);
	}

	/**
	 * Handle theme activation.
	 *
	 * @return void
	 * @since 1.6.0
	 */
	public function activate_theme() {
		// Check ajax referer.
		check_ajax_referer( 'suremails_plugin', '_ajax_nonce' );

		// Ensure this is an ajax request.
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'Not an ajax request.', 'suremails' ),
				]
			);
		}

		// Check user capabilities – using 'switch_themes' which is standard for theme activation.
		if ( ! current_user_can( 'switch_themes' ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'You do not have permission to activate themes.', 'suremails' ),
				]
			);
		}

		// Get theme slug from request.
		$theme_stylesheet = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $theme_stylesheet ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'No theme specified.', 'suremails' ),
				]
			);
		}

		// Activate the theme.
		switch_theme( $theme_stylesheet );

		$theme_slug = pathinfo( $theme_stylesheet, PATHINFO_FILENAME ); // Retrieves the theme slug from the stylesheet.
		if ( class_exists( '\BSF_UTM_Analytics' ) && is_callable( '\BSF_UTM_Analytics::update_referer' ) ) {
			$theme_slug = pathinfo( $theme_stylesheet, PATHINFO_FILENAME ); // Retrieves the theme slug from the stylesheet.
			\BSF_UTM_Analytics::update_referer( 'suremails', $theme_slug );
		}

		// Send success response.
		wp_send_json_success(
			[
				'success' => true,
				'message' => __( 'Theme activated successfully.', 'suremails' ),
			]
		);
	}
}
