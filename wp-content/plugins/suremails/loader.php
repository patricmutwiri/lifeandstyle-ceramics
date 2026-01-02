<?php
/**
 * Loader Class
 *
 * This file is responsible for initializing the SureMails plugin components.
 *
 * @package SureMails
 */

namespace SureMails;

use SureMails\Inc\Admin\Activator;
use SureMails\Inc\Admin\Crons;
use SureMails\Inc\Admin\Plugin;
use SureMails\Inc\Admin\Update;
use SureMails\Inc\Ajax\Ajax;
use SureMails\Inc\Analytics\Analytics;
use SureMails\Inc\API\Api_Init;
use SureMails\Inc\Controller\ContentGuard;
use SureMails\Inc\Controller\WeeklySummary;
use SureMails\Inc\Lib\Suremails_Nps_Survey;
use SureMails\Inc\Nps_Notice;

/**
 * Class Loader
 *
 * The Loader class is responsible for loading the SureMails plugin, handling translations,
 * autoloading, and initializing components like email settings, connections, and activation hooks.
 *
 * @package SureMails
 */
class Loader {
	/**
	 * Singleton instance of the Loader class.
	 *
	 * @var Loader|null
	 */
	private static $instance = null;

	/**
	 * Loader constructor.
	 * Private to enforce singleton pattern.
	 */
	private function __construct() {

		if ( ! defined( 'SUREMAILS_CONTENT_GUARD_MIDDLEWARE' ) ) {
			define( 'SUREMAILS_CONTENT_GUARD_MIDDLEWARE', 'https://credits.startertemplates.com/suremails/' );
		}

		/**
		 * Register the autoloader.
		 */
		$this->register_autoload();

		/**
		 * Load the plugin textdomain for translations.
		 */
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'setup' ] );
		add_action( 'plugin_loaded', [ $this, 'setup_ajax_instance' ] );

		/**
		 * The code that runs during plugin activation.
		 */
		register_activation_hook( SUREMAILS_FILE, [ $this, 'activate' ] );

		/**
		 * The code that runs during plugin deactivation.
		 */
		register_deactivation_hook( SUREMAILS_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Setup the plugin, register hooks, and initialize components.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->load_libraries();

		// Bail if doing AJAX.
		if ( wp_doing_ajax() ) {
			return;
		}
		// Admin loaders.
		if ( is_admin() ) {
			Plugin::instance();
		}
		Update::instance();
		ContentGuard::instance();
		Crons::instance();
		Api_Init::instance();
		Analytics::instance();
		WeeklySummary::instance();
	}

	/**
	 * Load libraries.
	 *
	 * @return void
	 */
	public function load_libraries() {

		if ( ! class_exists( 'Astra_Notices' ) ) {
			require_once SUREMAILS_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
		}

		if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
			require_once SUREMAILS_DIR . 'inc/lib/bsf-analytics/class-bsf-analytics-loader.php';
		}

		if ( ! class_exists( '\BSF_UTM_Analytics' ) ) {
			$utm_path = SUREMAILS_DIR . 'inc/lib/bsf-analytics/modules/utm-analytics.php';
			if ( file_exists( $utm_path ) ) {
				require_once $utm_path;
			}
		}

		$srml_bsf_analytics = \BSF_Analytics_Loader::get_instance();

		$srml_bsf_analytics->set_entity(
			[
				'suremails' => [
					'product_name'        => 'SureMail',
					'path'                => SUREMAILS_DIR . 'inc/lib/bsf-analytics',
					'author'              => 'SureMail',
					'time_to_display'     => '+24 hours',
					'hide_optin_checkbox' => true,
				],
			]
		);

		// load nps survey.
		if ( class_exists( 'SureMails\Inc\Lib\Suremails_Nps_Survey' ) && ! apply_filters( 'suremails_disable_nps_survey', false ) ) {
			Suremails_Nps_Survey::instance();
			Nps_Notice::instance();
		}
	}

	/**
	 * Setup the Ajax instance.
	 *
	 * @return void
	 */
	public function setup_ajax_instance() {
		if ( ! wp_doing_ajax() ) {
			return;
		}
		Ajax::instance();
	}

	/**
	 * Get the singleton instance of the Loader class.
	 *
	 * @return Loader Singleton instance of the Loader class.
	 */
	public static function instance(): Loader {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public function activate(): void {
		Activator::instance()->activate();
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// On Deactivation of the plugin.
	}

	/**
	 * Autoload classes based on their namespace and path.
	 *
	 * @param string $class The fully-qualified class name.
	 * @return void
	 */
	public function autoload( string $class ): void {
		// Define namespace and base directory for your project.
		$namespace      = 'SureMails\\Inc\\';
		$base_directory = SUREMAILS_DIR . 'inc/';

		// Ensure the class belongs to the current namespace.
		if ( strpos( $class, $namespace ) !== 0 ) {
			return;
		}

		// Strip the namespace prefix from the class.
		$relative_class = substr( $class, strlen( $namespace ) );

		// Convert namespace separators to directory separators.
		// and handle CamelCase to kebab-case conversion for filenames.
		$filename = preg_replace(
			[ '/^' . preg_quote( $namespace, '/' ) . '/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
			[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
			$relative_class
		);

		// Ensure the filename is in lowercase.
		if ( is_string( $filename ) ) {

			$filename = strtolower( $filename );

			// Construct the full file path.
			$file = $base_directory . $filename . '.php';

			// Include the file if it exists and is readable.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Load Plugin Text Domain.
	 * This will load the translation textdomain depending on the file priorities.
	 *      1. Global Languages /wp-content/languages/suremails/ folder
	 *      2. Local directory /wp-content/plugins/suremails/languages/ folder
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function load_textdomain(): void {
		// Default languages directory.
		$lang_dir = SUREMAILS_DIR . 'languages/';

		/**
		 * Filters the languages directory path to use for plugin.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'suremails_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter.
		global $wp_version;

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Language Locale for plugin
		 *
		 * Uses get_user_locale()` in WordPress 4.7 or greater,
		 * otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'suremails' );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
		$mofile = sprintf( '%1$s-%2$s.mo', 'suremails', $locale );

		// Setup paths to current locale file.
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		$mofile_local  = $lang_dir . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/suremails/ folder.
			load_textdomain( 'suremails', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/suremails/languages/ folder.
			load_textdomain( 'suremails', $mofile_local );
		}
	}

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	private function register_autoload(): void {
		spl_autoload_register( [ $this, 'autoload' ] );
	}
}

// Initialize the loader singleton.
Loader::instance();
