<?php
/**
 * Plugin Installer for Starter Templates Importer
 *
 * @package Starter Templates Importer
 */

namespace STImporter\Importer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Installer Class
 */
class ST_Plugin_Installer {

	/**
	 * Install and activate a plugin
	 *
	 * @since 1.1.21
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Plugin main file path (relative to plugins directory).
	 * @return array<string, mixed> Result array with status and message
	 */
	public static function install_and_activate_plugin( $plugin_slug, $plugin_file ) {
		// Check user capabilities.
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return array(
				'status' => false,
				'error'  => __( "Permission denied: You don't have sufficient permissions to install or activate plugins. Please contact your site administrator.", 'astra-sites' ),
			);
		}

		try {
			// Check if plugin is already active.
			if ( is_plugin_active( $plugin_file ) ) {
				return array(
					'status'  => true,
					// translators: %s is the plugin slug.
					'message' => sprintf( __( '%s plugin is already active.', 'astra-sites' ), $plugin_slug ),
				);
			}

			// Check if plugin is installed but not active.
			if ( self::is_plugin_installed( $plugin_file ) ) {
				$activation_result = self::activate_plugin( $plugin_file );
				if ( $activation_result['status'] ) {
					return array(
						'status'  => true,
						// translators: %s is the plugin slug.
						'message' => sprintf( __( '%s plugin activated successfully.', 'astra-sites' ), $plugin_slug ),
					);
				} else {
					return $activation_result;
				}
			}

			// Plugin not installed, need to install first.
			$installation_result = self::install_plugin( $plugin_slug );
			if ( ! $installation_result['status'] ) {
				return $installation_result;
			}

			// Now activate the plugin.
			$activation_result = self::activate_plugin( $plugin_file );
			if ( $activation_result['status'] ) {
				return array(
					'status'  => true,
					// translators: %s is the plugin slug.
					'message' => sprintf( __( '%s plugin installed and activated successfully.', 'astra-sites' ), $plugin_slug ),
				);
			} else {
				return $activation_result;
			}
		} catch ( \Exception $e ) {
			return array(
				'status' => false,
				// translators: %s is the exception message.
				'error'  => sprintf( __( 'Plugin installation failed: %s', 'astra-sites' ), $e->getMessage() ),
			);
		}
	}

	/**
	 * Check if plugin is installed
	 *
	 * @since 1.1.21
	 *
	 * @param string $plugin_file Plugin main file path.
	 * @return bool
	 */
	private static function is_plugin_installed( $plugin_file ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();
		return isset( $installed_plugins[ $plugin_file ] );
	}

	/**
	 * Install plugin
	 *
	 * @since 1.1.21
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array<string, mixed> Result array
	 */
	private static function install_plugin( $plugin_slug ) {
		// Include necessary WordPress files.
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		}

		try {
			// Get plugin information.
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin_slug,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

			if ( is_wp_error( $api ) ) {
				return array(
					'status' => false,
					// translators: %s is the error message.
					'error'  => sprintf( __( 'Plugin information not found: %s', 'astra-sites' ), $api->get_error_message() ),
				);
			}

			// Install the plugin.
			$download_link  = isset( $api->download_link ) ? $api->download_link : '';
			$upgrader       = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$install_result = $upgrader->install( $download_link );

			if ( is_wp_error( $install_result ) ) {
				return array(
					'status' => false,
					// translators: %s is the error message.
					'error'  => sprintf( __( 'Plugin installation failed: %s', 'astra-sites' ), $install_result->get_error_message() ),
				);
			}

			if ( ! $install_result ) {
				return array(
					'status' => false,
					'error'  => __( 'Plugin installation failed for unknown reason.', 'astra-sites' ),
				);
			}

			return array(
				'status'  => true,
				// translators: %s is the plugin slug.
				'message' => sprintf( __( '%s plugin installed successfully.', 'astra-sites' ), $plugin_slug ),
			);

		} catch ( \Exception $e ) {
			return array(
				'status' => false,
				// translators: %s is the exception message.
				'error'  => sprintf( __( 'Plugin installation exception: %s', 'astra-sites' ), $e->getMessage() ),
			);
		}
	}

	/**
	 * Activate plugin
	 *
	 * @since 1.1.21
	 *
	 * @param string $plugin_file Plugin main file path.
	 * @return array<string, mixed> Result array
	 */
	private static function activate_plugin( $plugin_file ) {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$activation_result = activate_plugin( $plugin_file );

		if ( is_wp_error( $activation_result ) ) {
			return array(
				'status' => false,
				// translators: %s is the error message.
				'error'  => sprintf( __( 'Plugin activation failed: %s', 'astra-sites' ), $activation_result->get_error_message() ),
			);
		}

		self::after_plugin_activate( $plugin_file );

		return array(
			'status'  => true,
			'message' => __( 'Plugin activated successfully.', 'astra-sites' ),
		);
	}

	/**
	 * After Plugin Activate
	 *
	 * @since 4.4.39
	 *
	 * @param  string               $plugin_init        Plugin Init File.
	 * @param  array<string, mixed> $options            Site Options.
	 * @param  array<string, mixed> $enabled_extensions Enabled Extensions.
	 * @param  string               $plugin_slug        Plugin slug.
	 * @param  bool                 $was_plugin_active  Flag indicating if the plugin was already active.
	 * @return void
	 */
	public static function after_plugin_activate( $plugin_init = '', $options = array(), $enabled_extensions = array(), $plugin_slug = '', $was_plugin_active = false ) {
		$data = array(
			'astra_site_options' => $options,
			'enabled_extensions' => $enabled_extensions,
			'plugin_slug'        => $plugin_slug,
			'was_plugin_active'  => $was_plugin_active,
		);

		do_action( 'astra_sites_after_plugin_activation', $plugin_init, $data );
	}

	/**
	 * Install and activate Spectra plugin specifically
	 *
	 * @since 1.1.21
	 *
	 * @return array<string, mixed> Result array
	 */
	public static function install_spectra_plugin() {
		return self::install_and_activate_plugin( 'ultimate-addons-for-gutenberg', 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php' );
	}
}
