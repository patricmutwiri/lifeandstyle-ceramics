<?php
/**
 * Ai site setup
 *
 * @since 3.0.0-beta.1
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use STImporter\Importer\ST_Importer;

if ( ! class_exists( 'Astra_Sites_Onboarding_Setup' ) ) :

	/**
	 * AI Site Setup
	 */
	class Astra_Sites_Onboarding_Setup {

		/**
		 * Instance
		 *
		 * @since 4.0.0
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		 /**
		 * Initiator
		 *
		 * @since 4.0.0
		 * @return mixed 
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}


		/**
		 * Constructor
		 *
		 * @since 3.0.0-beta.1
		 */
		public function __construct() {
			add_action( 'st_before_sending_error_report', array( $this, 'delete_transient_for_import_process' ) );
			add_action( 'st_before_sending_error_report', array( $this, 'temporary_cache_errors' ), 10, 1 );
			add_action( 'wp_ajax_astra-sites-import_prepare_xml', array( $this, 'import_prepare_xml' ) );
			add_action( 'wp_ajax_bsf_analytics_optin_status', array( $this, 'bsf_analytics_optin_status' ) );
			add_action( 'wp_ajax_astra_sites_track_onboarding_step', array( $this, 'track_onboarding_step' ) );
		}

		/**
		 * Track onboarding step
		 *
		 * @return void
		 * @since 4.4.42
		 */
		public function track_onboarding_step() {
			// Verify Nonce.
			check_ajax_referer( 'wp_rest', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( esc_html__( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			if ( empty( $_POST ) || ! isset( $_POST['step_visited'] ) ) {
				wp_send_json_error( esc_html__( 'Missing required parameter.', 'astra-sites' ) );
			}

			$step = sanitize_text_field( wp_unslash( $_POST['step_visited'] ) );

			// Valid steps
			$valid_steps = array(
				'welcome',
				'page-builder',
				'template-listing',
				'template-preview',
				'features',
				'survey',
				'import'
			);

			// Validate step key
			if ( ! in_array( $step, $valid_steps, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid step.', 'astra-sites' ) );
			}

			if( ! class_exists( 'Astra_Sites_Page' ) ) {
				require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-page.php';
			}
			// Get existing tracked steps
			$steps_visited = Astra_Sites_Page::get_instance()->get_setting( 'steps_visited' );

			// Initialize if not array
			if ( ! is_array( $steps_visited ) ) {
				$steps_visited = array();
			}
			
			// Check if step is already tracked
			if ( ! in_array( $step, $steps_visited, true ) ) {
				// Add step to tracked steps
				Astra_Sites_Page::get_instance()->update_settings( [
					'steps_visited' => array_merge( $steps_visited, array( $step ) ),	
				] );
			}

			wp_send_json_success();
		}

		/**
		 * Prepare XML Data.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function import_prepare_xml() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( "Permission denied: You don't have permission to initiate the import process. Please contact your site administrator.", 'astra-sites' ) );
			}

			do_action( 'astra_sites_before_import_prepare_xml' );

			if ( ! class_exists( 'XMLReader' ) ) {
				astra_sites_error_log( 'XMLReader PHP not exist!' );
				wp_send_json_error( __( 'Import failed: The XMLReader PHP extension is missing on your server. Please contact your hosting provider to enable it.', 'astra-sites' ) );
			}

			$wxr_url = astra_get_site_data( 'astra-site-wxr-path' );

			// Enhanced WXR URL validation with context
			if ( empty( $wxr_url ) || 'null' === $wxr_url || '' === trim( $wxr_url ) ) {
				astra_sites_error_log( 'Missing WXR url' );
				wp_send_json_error( __( 'Import failed: This template does not include site content (posts, pages, media). You can proceed with design-only import or choose a different template.', 'astra-sites' ) );
			}

			$result = array(
				'status' => false,
			);

			if ( ! class_exists( 'STImporter\Importer\ST_Importer' ) ) {
				wp_send_json_error( __( 'Import failed: The required import library is not loaded. This may be due to a plugin conflict. Please deactivate other plugins temporarily and try again, or reinstall the Starter Templates plugin.', 'astra-sites' ) );
			}

			try {
				$result = ST_Importer::prepare_xml_data( $wxr_url );
			} catch ( \Exception $e ) {
				astra_sites_error_log( 'ST_Importer::prepare_xml_data Exception: ' . $e->getMessage() );
				// translators: %s is the exception message.
				wp_send_json_error( sprintf( __( 'Import failed: Unexpected error - %s', 'astra-sites' ), $e->getMessage() ) );
			} catch ( \Error $e ) {
				astra_sites_error_log( 'ST_Importer::prepare_xml_data Fatal Error: ' . $e->getMessage() );
				wp_send_json_error( sprintf( __( 'Import failed: Fatal error - %s', 'astra-sites' ), $e->getMessage() ) );
			}
			
			if ( false === $result['status'] ) {
				/* translators: %s: Error message */
				wp_send_json_error( sprintf( __( 'Import failed: %s', 'astra-sites' ), $result['error'] ) );
			} else {
				wp_send_json_success( $result['data'] );
			}
		}

		/**
		 * BSF Analytics Opt-in.
		 *
		 * @return void
		 * @since 4.4.19
		 */
		public function bsf_analytics_optin_status() {
			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( esc_html__( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			if ( empty( $_POST ) || ! isset( $_POST['bsfUsageTracking'] ) ) {
				wp_send_json_error( esc_html__( 'Missing required parameter.', 'astra-sites' ) );
			}

			$opt_in = filter_input( INPUT_POST, 'bsfUsageTracking', FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';

			update_site_option( 'bsf_analytics_optin', $opt_in );

			wp_send_json_success( esc_html__( 'Usage tracking updated successfully.', 'astra-sites' ) );
		}

		/**
		 * Delete transient for import process.
		 *
		 * @param array<string|int, mixed> $posted_data
		 * @since 3.1.4
		 * @return void
		 */
		public function temporary_cache_errors( $posted_data ) {
			update_option( 'astra_sites_cached_import_error', $posted_data, false );
		}

		/**
		 * Delete transient for import process.
		 *
		 * @since 3.1.4
		 * @return void
		 */
		public function delete_transient_for_import_process() {
			delete_transient( 'astra_sites_import_started' );
		}
	}

	Astra_Sites_Onboarding_Setup::get_instance();

endif;
