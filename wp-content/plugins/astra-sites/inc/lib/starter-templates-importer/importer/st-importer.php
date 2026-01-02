<?php
/**
 * Starter Templates Importer - Module.
 *
 * This file is used to register and manage the Zip AI Modules.
 *
 * @package Starter Templates Importer
 */

namespace STImporter\Importer;

use STImporter\Importer\WXR_Importer\ST_WXR_Importer;
use STImporter\Importer\ST_Importer_Helper;
use STImporter\Importer\ST_Widget_Importer;
use STImporter\Importer\ST_Plugin_Installer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The Module Class.
 */
class ST_Importer {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var self Class object.
	 */
	private static $instance = null;

	/**
	 * Initiator of this class.
	 *
	 * @since 1.0.0
	 * @return self initialized object of this class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initiate import process flog.
	 *
	 * @since 1.0.0
	 * @param string $template_type template type.
	 * @param string $uuid uuid.
	 * @return array<string, mixed>
	 */
	public static function set_import_process_start_flag( $template_type, $uuid = '' ) {

		if ( empty( $uuid ) && 'ai' === $template_type ) {
			return array(
				'status' => false,
				'error'  => __( 'uuid is empty.', 'astra-sites' ),
			);
		}

		if ( ! empty( $uuid ) ) {
			update_option( 'astra_sites_ai_import_started', 'yes', false );
		}
		do_action( 'st_before_start_import_process' );
		update_option( 'astra_sites_import_started', 'yes' );
		do_action( 'astra_sites_import_start' );

		return array(
			'status' => true,
			'error'  => __( 'Import process start flof set successfully.', 'astra-sites' ),
		);
	}

	/**
	 * Import Spectra Settings
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings spectra settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function import_spectra_settings( $settings = array() ) {

		// Check if Spectra plugin is available.
		if ( ! is_callable( 'UAGB_Admin_Helper::get_instance' ) ) {
			// Try to install and activate Spectra plugin.
			$install_result = ST_Plugin_Installer::install_spectra_plugin();

			if ( ! $install_result['status'] ) {
				return array(
					'status' => false,
					'error'  => sprintf(
						// translators: Spectra plugin installation failed message.
						__( 'Spectra plugin installation failed: %s', 'astra-sites' ),
						$install_result['error']
					),
				);
			}

			// Manually load the plugin file after activation.
			$plugin_file = WP_PLUGIN_DIR . '/ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';
			if ( file_exists( $plugin_file ) ) {
				include_once $plugin_file;
			}

			// Check again after manual loading.
			if ( ! is_callable( 'UAGB_Admin_Helper::get_instance' ) ) {
				return array(
					'status' => false,
					'error'  => __( 'Spectra plugin installed but class not loaded. Please refresh and try again.', 'astra-sites' ),
				);
			}
		}

		if ( empty( $settings ) ) {
			return array(
				'status' => false,
				'error'  => __( 'Spectra settings are empty.', 'astra-sites' ),
			);
		}

		try {
			\UAGB_Admin_Helper::get_instance()->update_admin_settings_shareable_data( $settings ); // @phpstan-ignore-line

			return array(
				'status'  => true,
				'message' => __( 'Spectra settings imported successfully.', 'astra-sites' ),
			);
		} catch ( \Exception $e ) {
			return array(
				'status' => false,
				// translators: %s is the exception message.
				'error'  => sprintf( __( 'Spectra settings import failed: %s', 'astra-sites' ), $e->getMessage() ),
			);
		}
	}

	/**
	 * Import Surecart Settings
	 *
	 * @since 1.0.0
	 *
	 * @param int $id id.
	 * @return array<string, mixed>
	 */
	public static function import_surecart_settings( $id = 0 ) {

		if ( ! is_callable( 'SureCart\Models\ProvisionalAccount::create' ) ) {
			return array(
				'status' => false,
				'error'  => __( 'SureCart\Models\ProvisionalAccount::create function is not callable.', 'astra-sites' ),
			);
		}

		// Create Account if surecart selected as a feature.
		$create_account = isset( $_POST['create_account'] ) && 'true' === $_POST['create_account'] ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$email          = ST_Importer_Helper::get_business_details( 'business_email' );
		$currency       = isset( $_POST['source_currency'] ) ? sanitize_text_field( $_POST['source_currency'] ) : 'usd'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$data = array(
			'email'            => empty( $email ) ? get_option( 'admin_email' ) : $email,
			'account_currency' => $currency,
		);

		if ( ! $create_account ) {
			$id = ! empty( $id ) ? base64_decode( sanitize_text_field( (string) $id ) ) : ''; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( empty( $id ) ) {
				return array(
					'status' => false,
					'error'  => __( 'Id is empty.', 'astra-sites' ),
				);
			}

			$data['source_account_id'] = $id;
		}

		$token = \SureCart\Models\ApiToken::get(); // @phpstan-ignore-line

		if ( ! empty( $token ) ) {
			return array(
				'status' => true,
				'msg'    => __( 'Account is already created.', 'astra-sites' ),
			);
		}

		$template_data = ST_Importer_File_System::get_instance()->get_demo_content();
		$products      = $template_data['astra-site-surecart-settings']['products'] ?? null;

		// If no products, set seed to true to create sample products.
		if ( empty( $products ) || ! is_array( $products ) ) {
			$data['seed'] = true;
		} else {
			// Collect all image hash URLs first to minimize DB hits.
			$hash_urls = [];
			foreach ( $products as $product ) {
				foreach ( $product['gallery'] ?? [] as $attachment ) {
					if ( ! empty( $attachment['url'] ) ) {
						$hash_urls[] = ST_Importer_Helper::get_hash_image( $attachment['url'] );
					}
				}
			}

			// Filter out empty hashes and get unique ones.
			$hash_urls = array_filter( array_unique( $hash_urls ) );

			// Map all hashes to their attachment IDs in one query.
			global $wpdb;
			$hash_map = [];
			if ( ! empty( $hash_urls ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $hash_urls ), '%s' ) );
				$results      = $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $wpdb->postmeta is a table name, and $placeholders is dynamically created based on array size.
						"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_astra_sites_image_hash' AND meta_value IN ($placeholders)",
						$hash_urls
					),
					ARRAY_A
				);

				foreach ( $results as $row ) {
					$hash_map[ $row['meta_value'] ] = (int) $row['post_id'];
				}
			}

			// Build final products array with resolved gallery IDs.
			foreach ( $products as $index => $product ) {
				$gallery_ids = [];

				foreach ( $product['gallery'] ?? [] as $attachment ) {
					$hash_url = ST_Importer_Helper::get_hash_image( $attachment['url'] ?? '' );
					if ( ! empty( $hash_url ) && isset( $hash_map[ $hash_url ] ) ) {
						$gallery_ids[] = $hash_map[ $hash_url ];
					}
				}

				$products[ $index ]['gallery_ids'] = $gallery_ids;

				// Map nested data arrays if they exist, else set to empty arrays or default values.
				$products[ $index ]['prices']              = ! empty( $product['prices']['data'] ) ? $product['prices']['data'] : array( array( 'amount' => 9900 ) );
				$products[ $index ]['variants']            = ! empty( $product['variants']['data'] ) ? $product['variants']['data'] : array();
				$products[ $index ]['variant_options']     = ! empty( $product['variant_options']['data'] ) ? $product['variant_options']['data'] : array();
				$products[ $index ]['product_collections'] = ! empty( $product['product_collections']['data'] ) ? $product['product_collections']['data'] : array();
			}

			$data['products'] = $products;
		}

		return \SureCart\Models\ProvisionalAccount::create(  // @phpstan-ignore-line
			$data
		);
	}

	/**
	 * Import Customizer Settings.
	 *
	 * @since 1.0.0
	 *
	 * @param  array<string, mixed> $customizer_data Customizer Data.
	 * @return array<string, mixed>
	 */
	public static function import_customizer_settings( $customizer_data = array() ) {

		if ( empty( $customizer_data ) ) {
			return array(
				'status' => false,
				'error'  => __( 'Customizer data is empty.', 'astra-sites' ),
			);
		}

		update_option( '_astra_sites_old_customizer_data', $customizer_data, false );

		// Update Astra Theme customizer settings.
		if ( isset( $customizer_data['astra-settings'] ) ) {
			update_option( 'astra-settings', $customizer_data['astra-settings'] );
		}

		// Add Custom CSS.
		if ( isset( $customizer_data['custom-css'] ) ) {
			wp_update_custom_css_post( $customizer_data['custom-css'] );
		}

		return array(
			'status'  => true,
			'message' => __( 'Customizer data imported successfully.', 'astra-sites' ),
		);

	}

	/**
	 * Prepare XML Data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $wxr_url url.
	 * @return array<string, mixed>
	 */
	public static function prepare_xml_data( $wxr_url ) {

		if ( ! ST_WXR_Importer::is_valid_wxr_url( $wxr_url ) ) {
			return array(
				'status' => false,
				/* Translators: %s is WXR URL. */
				'error'  => sprintf( __( 'Invalid WXR Request URL - %s', 'astra-sites' ), $wxr_url ),
			);
		}

		$overrides = array(
			'wp_handle_sideload' => 'upload',
		);

		// Download XML file.
		$xml_path = ST_WXR_Importer::download_file( $wxr_url, $overrides );

		if ( $xml_path['success'] ) {

			$post = array(
				'post_title'     => basename( $wxr_url ),
				'guid'           => $xml_path['data']['url'],
				'post_mime_type' => $xml_path['data']['type'],
			);

			// As per wp-admin/includes/upload.php.
			$post_id = wp_insert_attachment( $post, $xml_path['data']['file'] );

			if ( is_wp_error( $post_id ) ) { // @phpstan-ignore-line
				return array(
					'status' => false,
					// translators: %s is the error message.
					'error'  => sprintf( __( 'Error occurred while inserting XML file: %s', 'astra-sites' ), $post_id->get_error_message() ),
				);
			}

			if ( ! is_int( $post_id ) ) {
				return array(
					'status' => false,
					'error'  => __( 'There was an error downloading the XML file.', 'astra-sites' ),
				);
			} else {
				update_option( 'astra_sites_imported_wxr_id', $post_id, false );
				$attachment_metadata = wp_generate_attachment_metadata( $post_id, $xml_path['data']['file'] );
				wp_update_attachment_metadata( $post_id, $attachment_metadata );
				$data        = ST_WXR_Importer::get_xml_data( $xml_path['data']['file'], $post_id );
				$data['xml'] = $xml_path['data'];
				return array(
					'status' => true,
					'data'   => $data,
				);
			}
		} else {
			$error_message = isset( $xml_path['data'] )
				? $xml_path['data']
				: __( 'Could not download data file. Please check your internet connection and try again.', 'astra-sites' );

			return array(
				'status' => false,
				// translators: %s is the download error message.
				'error'  => sprintf( __( 'File download failed: %s', 'astra-sites' ), $error_message ),
			);
		}
	}

	/**
	 * Update post option
	 *
	 * @since 1.1.18
	 *
	 * @return void
	 */
	public static function set_elementor_kit() {

		// Update Elementor Theme Kit Option.
		$args = array(
			'post_type'   => 'elementor_library',
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_query'  => array( //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Setting elementor kit. WP Query would have been expensive.
				array(
					'key'   => '_astra_sites_imported_post',
					'value' => '1',
				),
				array(
					'key'   => '_elementor_template_type',
					'value' => 'kit',
				),
			),
		);

		$query = get_posts( $args );
		if ( ! empty( $query ) && isset( $query[0]->ID ) ) {
			update_option( 'elementor_active_kit', $query[0]->ID );
		}
	}

	/**
	 * Import site options.
	 *
	 * @since  1.0.0
	 *
	 * @param array<string, mixed> $options Array of options to be imported from the demo.
	 *
	 * @return array<string, mixed>
	 */
	public static function import_options( $options = array() ) {

		if ( empty( $options ) ) {
			return array(
				'status' => false,
				'error'  => __( 'Site options are empty!', 'astra-sites' ),
			);
		}

			// Set meta for tracking the post.
		if ( is_array( $options ) ) {
			update_option( '_astra_sites_old_site_options', $options, false );
		}

		try {
			foreach ( $options as $option_name => $option_value ) {

				// Is option exist in defined array site_options()?
				if ( null !== $option_value ) {
					switch ( $option_name ) {
						case 'page_for_posts':
						case 'page_on_front':
								ST_Option_Importer::update_page_id_by_option_value( $option_name, $option_value );
							break;

						// nav menu locations.
						case 'nav_menu_locations':
								ST_Option_Importer::set_nav_menu_locations( $option_value );
							break;

						// insert logo.
						case 'custom_logo':
								ST_Option_Importer::insert_logo( $option_value );
							break;

						case 'site_title':
							try {
								update_option( 'blogname', $option_value );
							} catch ( \Exception $e ) {
								// Failed silently: sometimes Elementor throws exception as it hooks into `update_option_blogname`.
								astra_sites_error_log( 'Handled exception while updating blogname: ' . $e->getMessage() );
							}
							break;

						case 'elementor_active_kit':
							if ( '' !== $option_value ) {
								self::set_elementor_kit();
							}
							break;

						default:
							update_option( $option_name, $option_value );
							break;
					}
				}
			}

			do_action( 'st_importer_import_site_options', $options );

			return array(
				'status'  => true,
				'message' => __( 'Options imported successfully.', 'astra-sites' ),
			);
		} catch ( \Exception $e ) {
			return array(
				'status' => false,
				'error'  => $e,
			);
		}

	}

	/**
	 * Import Widgets.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $widgets_data Widgets Data.
	 * @param  string $data Widgets Data.
	 * @return array<string, mixed>
	 */
	public static function import_widgets( $widgets_data, $data = '' ) {

		if ( is_object( $data ) ) { // @phpstan-ignore-line
			// $data is set and is an object.
			$widgets_data = $data;
		} elseif ( is_string( $data ) ) {
			// $data is set but is not an object.
			$widgets_data = (object) json_decode( $data );
		}

		if ( ! is_object( $widgets_data ) ) {
			return array(
				'status' => false,
				'error'  => __( 'Widget data is empty!', 'astra-sites' ),
			);
		}

		ST_Widget_Importer::import_widgets_data( $widgets_data );
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
		update_option( '_astra_sites_old_widgets_data', $sidebars_widgets, false );
		return array(
			'status'  => true,
			'message' => __( 'Widgets imported successfully.', 'astra-sites' ),
		);

	}
}
