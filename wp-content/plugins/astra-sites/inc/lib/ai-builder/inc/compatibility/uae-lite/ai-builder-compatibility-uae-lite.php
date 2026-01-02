<?php
/**
 * AI Builder Compatibility for 'UAE Lite'
 *
 * @package AI Builder
 * @since 1.2.37
 */

namespace AiBuilder\Inc\Compatibility\UAE_Lite;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Ai_Builder_Compatibility_UAE_Lite' ) ) {

	/**
	 * UAE Lite Compatibility
	 *
	 * @since 1.2.37
	 */
	class Ai_Builder_Compatibility_UAE_Lite {
		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.2.37
		 */
		private static $instance = null;

		/**
		 * Constructor
		 *
		 * @since 1.2.37
		 */
		public function __construct() {
			add_action( 'astra_sites_after_plugin_activation', array( $this, 'activation' ), 10, 1 );
		}

		/**
		 * Initiator
		 *
		 * @since 1.2.37
		 * @return object initialized object of class.
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Disable redirection for UAE Lite plugin when activated via Starter templates import process.
		 * This is also prevent redirection for plugin activation from elementor.
		 *
		 * @since 1.2.37
		 * @param string $plugin_init The plugin init.
		 * @return void
		 */
		public function activation( $plugin_init ) {
			// Skip if not UAE Lite plugin.
			if ( 'header-footer-elementor/header-footer-elementor.php' !== $plugin_init ) {
				return;
			}

			// Check if we're in an import context.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified earlier in the call stack.
			$trigger_origin    = isset( $_POST['trigger_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['trigger_origin'] ) ) : '';
			$is_import_context = astra_sites_has_import_started() || 'elementor' === $trigger_origin;

			if ( $is_import_context ) {
				// Prevent UAE Lite onboarding to maintain uninterrupted import flow.
				delete_option( 'hfe_start_onboarding' );
			}
		}
	}

	/**
	 * Kicking this off by calling 'instance()' method
	 */
	Ai_Builder_Compatibility_UAE_Lite::instance();

}
