<?php
/**
 * Import ajax actions.
 *
 * @package AiBuilder
 */

namespace AiBuilder\Inc\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiBuilder\Inc\Classes\Ai_Builder_Importer_Log;
use AiBuilder\Inc\Classes\Importer\Ai_Builder_Fse_Importer;
use AiBuilder\Inc\Classes\Importer\Ai_Builder_Utils;
use AiBuilder\Inc\Classes\Zipwp\Ai_Builder_ZipWP_Integration;
use AiBuilder\Inc\Traits\Helper;
use AiBuilder\Inc\Traits\Instance;
use STImporter\Importer\Batch\ST_Batch_Processing_Elementor;
use STImporter\Importer\Batch\ST_Batch_Processing_Gutenberg;
use STImporter\Importer\Batch\ST_Batch_Processing_Misc;
use STImporter\Importer\ST_Importer;
use STImporter\Importer\ST_Importer_Helper;
use STImporter\Resetter\ST_Resetter;

/**
 * Class Flows.
 */
class Importer extends AjaxBase {
	use Instance;

	/**
	 * Ajax Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 1.0.42
	 */
	private static $ajax_instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'astra_sites_import_complete', array( $this, 'update_required_options' ) );
	}

	/**
	 * Initiator
	 *
	 * @since 1.0.42
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$ajax_instance ) {
			self::$ajax_instance = new self();
		}
		return self::$ajax_instance;
	}

	/**
	 * Update options.
	 *
	 * @return void
	 */
	public function update_required_options() {
		update_option( 'astra_sites_import_complete', 'yes', false );

		// Mark setup wizard as shown.
		$option_name = class_exists( '\GS\Classes\GS_Helper' ) && is_callable( [ '\GS\Classes\GS_Helper', 'get_setup_wizard_showing_option_name' ] )
			? \GS\Classes\GS_Helper::get_setup_wizard_showing_option_name()
			: 'getting_started_is_setup_wizard_showing';

		update_option( $option_name, true );

		if ( 'ai' === get_option( 'astra_sites_current_import_template_type' ) ) {
			update_option( 'astra_sites_batch_process_complete', 'yes' );
			delete_option( 'ai_import_logger' );
			delete_option( 'astra_sites_import_failed_sites' );
		} else {
			update_option( 'astra_sites_batch_process_complete', 'no' );
		}
		delete_option( 'astra_sites_import_started' );
	}

	/**
	 * Register_ajax_events.
	 *
	 * @return void
	 */
	public function register_ajax_events() {

		$ajax_events = array(
			// Import Part 1 Start.
			'backup_settings',
			'reset_customizer_data',
			'reset_site_options',
			'reset_widgets_data',
			'reset_terms_and_forms',
			'get_deleted_post_ids',
			'reset_posts',
			'download_selected_image',
			'import_customizer_settings',
			'import_spectra_settings',
			'import_surecart_settings',
			// Import Part 1 End.

			// Import Part 2 Start.
			'import_options',
			'import_widgets',
			'page_builder_batch',
			'image_replacement_batch',
			'import_end',
			'set_site_data',
			'import_success',
			// Import Part 2 End.
		);

		$this->init_ajax_events( $ajax_events );
	}

	/**
	 * Backup our existing settings.
	 *
	 * @return void
	 */
	public function backup_settings() {
		Helper::backup_settings();
	}

	/**
	 * Reset posts in chunks.
	 *
	 * @return void
	 *
	 * @since 3.0.8
	 */
	public function reset_posts() {
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}
		}

		if ( class_exists( 'STImporter\Resetter\ST_Resetter' ) ) {
			ST_Resetter::reset_posts();
		}

		Helper::success_response();
	}

	/**
	 * Import Success.
	 *
	 * @return void
	 *
	 * @since 1.2.15
	 */
	public function import_success() {
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}
		}

		do_action( 'astra_sites_import_success' );

		Helper::success_response();
	}

	/**
	 * Reset customizer data
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function reset_customizer_data() {
		Helper::reset_customizer_data();
	}

	/**
	 * Reset site options
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function reset_site_options() {
		Helper::reset_site_options();
	}

	/**
	 * Reset widgets data
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function reset_widgets_data() {

		Helper::reset_widgets_data();
	}

	/**
	 * Reset terms and forms.
	 *
	 * @return void
	 *
	 * @since 3.0.3
	 */
	public function reset_terms_and_forms() {
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}
		}

		if ( class_exists( 'STImporter\Resetter\ST_Resetter' ) ) {
			ST_Resetter::reset_terms_and_forms();
		}

		Helper::success_response();
	}

	/**
	 * Get post IDs to be deleted.
	 *
	 * @return void
	 */
	public function get_deleted_post_ids() {
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}
		}

		Helper::success_response( astra_sites_get_reset_post_data() );
	}

	/**
	 * Download Images
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public function download_selected_image() {

		check_ajax_referer( 'astra-sites', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'data'   => 'You do not have permission to do this action.',
					'status' => false,

				)
			);
		}

		$index  = isset( $_POST['index'] ) ? sanitize_text_field( wp_unslash( $_POST['index'] ) ) : '';
		$images = Ai_Builder_ZipWP_Integration::get_business_details( 'images' );

		if ( empty( $images ) || ! is_array( $images ) ) {
			Helper::error_response(
				array(
					'data'   => __( 'Image not downloaded!', 'astra-sites' ),
					'status' => true,
				)
			);
			return;
		}

		$image = $images[ $index ];

		if ( empty( $image ) || ! is_array( $image ) ) {
			Helper::error_response(
				array(
					'data'   => __( 'Image not downloaded!', 'astra-sites' ),
					'status' => true,
				)
			);
			return;
		}

		$prepare_image = array(
			'id'          => $image['id'],
			'url'         => $image['url'],
			'description' => $image['description'],
		);

		Ai_Builder_Importer_Log::add( 'Downloading Image ' . $image['url'] );

		if ( class_exists( 'STImporter\Importer\ST_Importer_Helper' ) ) {
			$id = ST_Importer_Helper::download_image( $prepare_image );
			Ai_Builder_Importer_Log::add( 'Downloaded Image attachment id: ' . $id );

			Helper::success_response(
				array(
					'data'   => __( 'Image downloaded successfully!', 'astra-sites' ),
					'status' => true,
				)
			);
			return;
		}

		Helper::error_response(
			array(
				'data'   => __( 'Required function not found!', 'astra-sites' ),
				'status' => false,
			)
		);
	}

	/**
	 * Import Customizer Settings.
	 *
	 * @since 1.0.14
	 * @since 1.4.0  The `$customizer_data` was added.
	 *
	 * @return void
	 */
	public function import_customizer_settings() {
		Helper::import_customizer_settings();
	}

	/**
	 * Import Spectra Settings
	 *
	 * @since 3.1.16
	 *
	 * @param  string $url Spectra Settings JSON file URL.
	 * @return void
	 */
	public function import_spectra_settings( $url = '' ) {

		try {
			// Nonce verification.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			// Permission check.
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error(
					array(
						'error' => __( "Permission denied: You don't have sufficient permissions to import Spectra settings. Please contact your site administrator.", 'astra-sites' ),
						'code'  => 'permission_denied',
					)
				);
			}

			// Check if ST_Importer class exists.
			if ( ! class_exists( 'STImporter\Importer\ST_Importer' ) ) {
				Helper::error_response(
					array(
						'error' => __( 'Spectra settings import failed: ST_Importer class not found. Please ensure the importer is properly loaded.', 'astra-sites' ),
						'code'  => 'importer_missing',
					)
				);
			}

			// Get Spectra settings.
			$settings = astra_get_site_data( 'astra-site-spectra-options' );

			// Validate settings data.
			if ( empty( $settings ) ) {
				// This is normal - template might not have Spectra settings.
				Helper::success_response(
					array(
						'message'  => __( 'No Spectra settings found for this template.', 'astra-sites' ),
						'skipped'  => true,
						'settings' => $settings,
					)
				);
				return;
			}

			// Import Spectra settings.
			$result = ST_Importer::import_spectra_settings( $settings ); // @phpstan-ignore-line -- This method exists in ST_Importer.

			// Handle import result.
			if ( false === $result['status'] ) {
				$error_message = isset( $result['error'] ) && ! empty( $result['error'] )
					? $result['error']
					: __( 'Unknown error occurred during Spectra settings import.', 'astra-sites' );

				$error_message = defined( 'WP_CLI' )
					// translators: %s is the error message.
					? sprintf( __( 'Spectra settings import failed: %s', 'astra-sites' ), $error_message )
					: $error_message;

				Helper::error_response(
					array(
						'error'   => $error_message,
						'code'    => 'import_failed',
						'details' => $result,
					)
				);
				return;
			}

			// Success response.
			$success_message = isset( $result['message'] ) && ! empty( $result['message'] )
				? $result['message']
				: __( 'Spectra settings imported successfully.', 'astra-sites' );

			Helper::success_response(
				array(
					'message' => $success_message,
					'url'     => $url,
				)
			);
		} catch ( \Exception $e ) {
			// Catch any unexpected errors.
			$error_message = sprintf(
				// translators: %s is exception error message.
				__( 'Spectra settings import failed: Unexpected error - %s', 'astra-sites' ),
				$e->getMessage()
			);

			Helper::error_response(
				array(
					'error' => $error_message,
					'code'  => 'exception',
					'file'  => $e->getFile(),
					'line'  => $e->getLine(),
				)
			);
		} catch ( \Error $e ) {
			$error_message = sprintf(
				// translators: %s is exception error message.
				__( 'Spectra settings import failed: Fatal error - %s', 'astra-sites' ),
				$e->getMessage()
			);

			Helper::error_response(
				array(
					'error' => $error_message,
					'code'  => 'fatal_error',
					'file'  => $e->getFile(),
					'line'  => $e->getLine(),
				)
			);
		}
	}

	/**
	 * Import Surecart Settings
	 *
	 * @since 3.3.0
	 * @return void
	 */
	public function import_surecart_settings() {
		check_ajax_referer( 'astra-sites', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( "Permission denied: You don't have sufficient permissions to import SureCart settings. Please contact your site administrator.", 'astra-sites' ) );
		}

		if ( ! class_exists( 'STImporter\Importer\ST_Importer' ) ) {
			Helper::error_response( __( 'SureCart import failed: ST_Importer class not found. Please ensure the importer is properly loaded.', 'astra-sites' ) );
			return;
		}

		$id = isset( $_POST['source_id'] ) ? base64_decode( sanitize_text_field( $_POST['source_id'] ) ) : ''; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$result = ST_Importer::import_surecart_settings( $id );
		if ( is_wp_error( $result ) ) {
			// translators: %s: Error message.
			Helper::error_response( sprintf( __( 'SureCart import failed: %s', 'astra-sites' ), $result->get_error_message() ) );
			return;
		}

		Helper::success_response( 'success' );
	}

	/**
	 * Import Options.
	 *
	 * @since 1.0.14
	 * @since 1.4.0 The `$options_data` was added.
	 *
	 * @return void
	 */
	public function import_options() {
		Helper::import_options();
	}

	/**
	 * Import Widgets.
	 *
	 * @since 1.0.14
	 * @since 1.4.0 The `$widgets_data` was added.
	 *
	 * @return void
	 */
	public function import_widgets() {
		Helper::import_widgets();
	}

	/**
	 * Processing GT batch.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	public function page_builder_batch() {

		if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}
		}

		$required_plugins = (array) astra_get_site_data( 'required-plugins' );
		$plugins_slug     = array_column( $required_plugins, 'slug' );

		$status = array(
			'status' => false,
			'msg'    => __( 'Required function not found', 'astra-sites' ),
		);

		if ( in_array( 'elementor', $plugins_slug, true ) ) {
			$status = class_exists( 'STImporter\Importer\Batch\ST_Batch_Processing_Elementor' ) ? ST_Batch_Processing_Elementor::get_instance()->import() : $status;
		} else {
			$status = class_exists( 'STImporter\Importer\Batch\ST_Batch_Processing_Gutenberg' ) ? ST_Batch_Processing_Gutenberg::get_instance()->import() : $status;
		}

		if ( $status['success'] ) {
			Helper::success_response( $status['msg'] );
			return;
		}

		Helper::error_response( $status['msg'] );
	}

	/**
	 * Processing GT batch.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	public function image_replacement_batch() {

		if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}
		}

		$status = class_exists( 'STImporter\Importer\Batch\ST_Batch_Processing_Misc' ) ? ST_Batch_Processing_Misc::get_instance()->import() : array(
			'status' => false,
			'msg'    => __( 'Required function not found', 'astra-sites' ),
		);

		if ( $status['success'] ) {
			Helper::success_response( $status['msg'] );
			return;
		}

		Helper::error_response( $status['msg'] );
	}

	/**
	 * Import End.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	public function import_end() {
		Helper::import_end();
	}

	/**
	 * Set site related data.
	 *
	 * @since 3.0.0-beta.1
	 * @return void
	 */
	public function set_site_data() {

		if ( 'spectra-one' === get_option( 'stylesheet', 'astra' ) ) {
			Ai_Builder_Fse_Importer::set_fse_site_data();
			return;
		}

		check_ajax_referer( 'astra-sites', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'You are not authorized to perform this action.', 'astra-sites' ),
				)
			);
		}

		$param = isset( $_POST['param'] ) ? sanitize_text_field( $_POST['param'] ) : '';

		if ( empty( $param ) ) {
			Helper::error_response(
				array(
					'error' => __( 'Received empty parameters.', 'astra-sites' ),
				)
			);
			return;
		}

		switch ( $param ) {

			case 'site-title':
				$business_name = isset( $_POST['business-name'] ) ? sanitize_text_field( stripslashes( $_POST['business-name'] ) ) : '';
				if ( ! empty( $business_name ) ) {
					try {
						update_option( 'blogname', $business_name );
					} catch ( \Exception $e ) {
						// Failed silently: sometimes Elementor throws exception as it hooks into `update_option_blogname`.
						astra_sites_error_log( 'Handled exception while updating blogname: ' . $e->getMessage() );
					}
				}

				if ( isset( $_POST['show-site-title'] ) ) {
					// Get the value of the POST variable.
					$show_site_title = filter_var( $_POST['show-site-title'], FILTER_VALIDATE_BOOLEAN );

					// Determine the array based on the value of the POST variable.
					$options_array = $show_site_title
						? array(
							'desktop' => true,
							'tablet'  => true,
							'mobile'  => true,
						)
						: array(
							'desktop' => false,
							'tablet'  => false,
							'mobile'  => false,
						);

					// Update the option in the database.
					astra_update_option( 'display-site-title-responsive', $options_array );
					astra_update_option( 'display-site-title', $show_site_title );
				}

				break;

			case 'site-logo' === $param && function_exists( 'astra_get_option' ):
					$logo_id     = isset( $_POST['logo'] ) ? intval( $_POST['logo'] ) : 0;
					$width_index = 'ast-header-responsive-logo-width';
					set_theme_mod( 'custom_logo', $logo_id );

				if ( ! empty( $logo_id ) ) {
					// Disable site title when logo is set.
					astra_update_option( 'display-site-title', false );
				}

					// Set logo width.
					$logo_width = isset( $_POST['logo-width'] ) ? sanitize_text_field( $_POST['logo-width'] ) : '';
					$option     = astra_get_option( $width_index );

				if ( isset( $option['desktop'] ) ) {
					$option['desktop'] = $logo_width;
				}
				astra_update_option( $width_index, $option );

				// Check if transparent header is used in the demo.
				$transparent_header = astra_get_option( 'transparent-header-logo', false );
				$inherit_desk_logo  = astra_get_option( 'different-transparent-logo', false );

				if ( '' !== $transparent_header && $inherit_desk_logo ) {
					astra_update_option( 'transparent-header-logo', wp_get_attachment_url( $logo_id ) );
					$width_index = 'transparent-header-logo-width';
					$option      = astra_get_option( $width_index );

					if ( isset( $option['desktop'] ) ) {
						$option['desktop'] = $logo_width;
					}
					astra_update_option( $width_index, $option );
				}

				$retina_logo = astra_get_option( 'different-retina-logo', false );
				if ( '' !== $retina_logo ) {
					astra_update_option( 'ast-header-retina-logo', wp_get_attachment_url( $logo_id ) );
				}

				$transparent_retina_logo = astra_get_option( 'different-transparent-retina-logo', false );
				if ( '' !== $transparent_retina_logo ) {
					astra_update_option( 'transparent-header-retina-logo', wp_get_attachment_url( $logo_id ) );
				}

				break;

			case 'site-colors' === $param && function_exists( 'astra_get_option' ):
					$palette = isset( $_POST['palette'] ) ? (array) json_decode( stripslashes( $_POST['palette'] ) ) : array();
					$colors  = isset( $palette['colors'] ) ? (array) $palette['colors'] : array();
				if ( ! empty( $colors ) ) {
					$global_palette = astra_get_option( 'global-color-palette' );
					$color_palettes = get_option( 'astra-color-palettes', \Astra_Global_Palette::get_default_color_palette() );

					if ( is_array( $color_palettes ) ) {
						foreach ( $colors as $key => $color ) {
							$global_palette['palette'][ $key ]               = $color;
							$color_palettes['palettes']['palette_1'][ $key ] = $color;
						}
					}

					update_option( 'astra-color-palettes', $color_palettes );
					astra_update_option( 'global-color-palette', $global_palette );
				}
				break;

			case 'site-typography' === $param && function_exists( 'astra_get_option' ):
					$typography = isset( $_POST['typography'] ) ? (array) json_decode( stripslashes( $_POST['typography'] ) ) : '';

					$font_size_body = isset( $typography['font-size-body'] ) ? (array) $typography['font-size-body'] : '';
				if ( ! empty( $font_size_body ) && is_array( $font_size_body ) ) {
					astra_update_option( 'font-size-body', $font_size_body );
				}

				if ( ! empty( $typography['body-font-family'] ) ) {
					astra_update_option( 'body-font-family', $typography['body-font-family'] );
				}

				if ( ! empty( $typography['body-font-variant'] ) ) {
					astra_update_option( 'body-font-variant', $typography['body-font-variant'] );
				}

				if ( ! empty( $typography['body-font-weight'] ) ) {
					astra_update_option( 'body-font-weight', $typography['body-font-weight'] );
				}

				if ( ! empty( $typography['body-line-height'] ) ) {
					astra_update_option( 'body-line-height', $typography['body-line-height'] );
				}

				if ( ! empty( $typography['headings-font-family'] ) ) {
					astra_update_option( 'headings-font-family', $typography['headings-font-family'] );
				}

				if ( ! empty( $typography['headings-font-weight'] ) ) {
					astra_update_option( 'headings-font-weight', $typography['headings-font-weight'] );
				}

				if ( ! empty( $typography['headings-line-height'] ) ) {
					astra_update_option( 'headings-line-height', $typography['headings-line-height'] );
				}

				if ( ! empty( $typography['headings-font-variant'] ) ) {
					astra_update_option( 'headings-font-variant', $typography['headings-font-variant'] );
				}

				break;
		}

		// Clearing Cache on hostinger, Cloudways.
		Ai_Builder_Utils::third_party_cache_plugins_clear_cache();

		Helper::success_response();
	}

}
