<?php
/**
 * Loader.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Admin\Admin_Notice;
use SureRank\Inc\Admin\Attachment;
use SureRank\Inc\Admin\BulkActions;
use SureRank\Inc\Admin\BulkEdit;
use SureRank\Inc\Admin\Dashboard;
use SureRank\Inc\Admin\Onboarding;
use SureRank\Inc\Admin\Search_Console_Widget;
use SureRank\Inc\Admin\Seo_Bar;
use SureRank\Inc\Admin\Seo_Popup;
use SureRank\Inc\Admin\Sync;
use SureRank\Inc\Admin\Update_Timestamp;
use SureRank\Inc\Ajax\Ajax;
use SureRank\Inc\Analytics\Analytics;
use SureRank\Inc\Analyzer\PostAnalyzer;
use SureRank\Inc\Analyzer\TermAnalyzer;
use SureRank\Inc\API\Analyzer;
use SureRank\Inc\API\Api_Init;
use SureRank\Inc\BatchProcess\Process;
use SureRank\Inc\Cli\Cli;
use SureRank\Inc\Frontend\Archives;
use SureRank\Inc\Frontend\Canonical;
use SureRank\Inc\Frontend\Common;
use SureRank\Inc\Frontend\Content_Seo;
use SureRank\Inc\Frontend\Crawl_Optimization;
use SureRank\Inc\Frontend\Facebook;
use SureRank\Inc\Frontend\Feed;
use SureRank\Inc\Frontend\Meta_Data;
use SureRank\Inc\Frontend\Meta_Tag_Injection;
use SureRank\Inc\Frontend\Product;
use SureRank\Inc\Frontend\Robots;
use SureRank\Inc\Frontend\Seo_Popup as Seo_Popup_Frontend;
use SureRank\Inc\Frontend\Single;
use SureRank\Inc\Frontend\Special_Page;
use SureRank\Inc\Frontend\Taxonomy;
use SureRank\Inc\Frontend\Title;
use SureRank\Inc\Frontend\Twitter;
use SureRank\Inc\Functions\Cron;
use SureRank\Inc\Functions\Defaults;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\GoogleSearchConsole\Auth;
use SureRank\Inc\Lib\Surerank_Nps_Survey;
use SureRank\Inc\Modules\Ai_Auth\Init as Ai_Auth_Init;
use SureRank\Inc\Modules\Content_Generation\Init as Content_Generation_Init;
use SureRank\Inc\Modules\EmailReports\Init as EmailReports_Init;
use SureRank\Inc\Modules\Fix_Seo_Checks\Init as Fix_Seo_Checks_Init;
use SureRank\Inc\Modules\Nudges\Init as Nudges_Init;
use SureRank\Inc\Nps_Notice;
use SureRank\Inc\Routes;
use SureRank\Inc\Schema\Schemas;
use SureRank\Inc\Sitemap\Checksum;
use SureRank\Inc\Sitemap\Xml_Sitemap;
use SureRank\Inc\ThirdPartyIntegrations\Init as Integrations_Init;

/**
 * Plugin_Loader
 *
 * @since 1.0.0
 */
class Loader {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
		add_action( 'shutdown', [ $this, 'shutdown' ] );

		add_action( 'plugins_loaded', [ $this, 'load_routes' ], 10 );

		add_action( 'init', [ $this, 'load_textdomain' ], 10 );
		add_action( 'init', [ $this, 'load_nps' ], 99 );
		add_action( 'init', [ $this, 'setup' ], 999 );
		add_action( 'init', [ $this, 'flush_rules' ], 999 );

		register_activation_hook( SURERANK_FILE, [ $this, 'activation' ] );
		register_deactivation_hook( SURERANK_FILE, [ $this, 'deactivation' ] );

		add_filter( 'plugin_row_meta', [ $this, 'add_meta_links' ], 10, 2 );
	}

	/**
	 * Enqueue required classes after plugins loaded.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function setup(): void {
		do_action( 'surerank_before_load_components' );

		$this->load_core_components();
		$this->load_environment_components();

		do_action( 'surerank_after_load_components' );
	}

	/**
	 * Load routes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function load_routes() {
		do_action( 'surerank_before_load_routes' );

		Routes::get_instance();
		Analytics::get_instance();
		Admin_Notice::get_instance();

		do_action( 'surerank_before_load_routes' );
	}

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class class name.
	 * @since 1.0.0
	 * @return void
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}

		$class_to_load = $class;

		$filename = preg_replace(
			[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
			[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
			$class_to_load
		);

		if ( is_string( $filename ) ) {
			$filename = strtolower( $filename );

			$file = SURERANK_DIR . $filename . '.php';

			// if the file readable, include it.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Load Plugin Text Domain.
	 * This will load the translation textdomain depending on the file priorities.
	 *      1. Global Languages /wp-content/languages/surerank/ folder
	 *      2. Local directory /wp-content/plugins/surerank/languages/ folder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		// Default languages directory.
		$lang_dir = SURERANK_DIR . 'languages/';

		/**
		 * Filters the languages directory path to use for plugin.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'surerank_languages_directory', $lang_dir );

		$get_locale = get_user_locale();

		$locale = apply_filters( 'plugin_locale', $get_locale, 'surerank' ); //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
		$mofile = sprintf( '%1$s-%2$s.mo', 'surerank', $locale );

		// Setup paths to current locale file.
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		$mofile_local  = $lang_dir . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/surerank/ folder.
			load_textdomain( 'surerank', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/surerank/languages/ folder.
			load_textdomain( 'surerank', $mofile_local );
		}
	}

	/**
	 * Activation Hook
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activation() {
		Update::option( 'surerank_flush_required', 1 );
		Update::option( 'surerank_redirect_on_activation', 'yes' );
		Cron::get_instance()->schedule_sitemap_generation();
	}

	/**
	 * Deactivation Hook
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivation() {
		Update::option( 'surerank_flush_required', 1 );
		Cron::get_instance()->unschedule_sitemap_generation();
		Checksum::get_instance()->clear_checksum();

		delete_option( 'surerank_cron_test_ok' );
	}

	/**
	 * Flush if settings is updated
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function flush_rules() {
		if ( Get::option( 'surerank_flush_required' ) ) {
			Helper::flush();
		}

		delete_option( 'surerank_flush_required' );
	}

	/**
	 * Flush the setting on the shubdown
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function shutdown() {
		update_option( 'rewrite_rules', '' );
	}

	/**
	 * Add meta links to the plugin row (under description).
	 *
	 * @param array<int,string> $links Array of plugin meta links.
	 * @param string            $file Plugin file path.
	 * @return array<int,string> Modified plugin meta links.
	 */
	public function add_meta_links( array $links, string $file ): array {
		if ( SURERANK_BASE === $file ) {
			$stars = '';
			for ( $indx = 0; $indx < 5; $indx++ ) {
				$stars .= '<span class="dashicons dashicons-star-filled" style="color: #ffb900; font-size: 16px; width: 16px; height: 16px; line-height: 1.2;" aria-hidden="true"></span>';
			}
			$links[] = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" role="button">%s</a>',
				esc_url( 'https://wordpress.org/support/plugin/surerank/reviews/#new-post' ),
				esc_attr__( 'Rate our plugin', 'surerank' ),
				$stars
			);
		}
		return $links;
	}

	/**
	 * Load NPS Survey if conditions are met.
	 */
	public function load_nps(): void {
		if ( $this->should_load_nps_survey() ) {
			Surerank_Nps_Survey::get_instance();
			Nps_Notice::get_instance();
		}
	}

	/**
	 * Load core components that are always needed.
	 *
	 * @return void
	 */
	private function load_core_components(): void {
		$core_components = [
			Defaults::class,
			Schemas::class,
			Seo_Bar::class,
			Attachment::class,
			Crawl_Optimization::class,
			Analyzer::class,
			PostAnalyzer::class,
			TermAnalyzer::class,
			Api_Init::class,
			Auth::class,
			Sync::class,
			Cron::class,
			Checksum::class,
			Ai_Auth_Init::class,
			Content_Generation_Init::class,
			EmailReports_Init::class,
			Fix_Seo_Checks_Init::class,
			Nudges_Init::class,
			Integrations_Init::class,
			Process::class,
			Cli::class,
		];

		$this->load_components( $core_components );
	}

	/**
	 * Load environment-specific components.
	 *
	 * @return void
	 */
	private function load_environment_components(): void {
		if ( is_admin() ) {
			$this->load_admin_components();
		} else {
			$this->load_frontend_components();
		}
	}

	/**
	 * Load admin-specific components.
	 *
	 * @return void
	 */
	private function load_admin_components(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_components = [
			Seo_Popup::class,
			Update_Timestamp::class,
			Dashboard::class,
			Onboarding::class,
			BulkActions::class,
			BulkEdit::class,
			Ajax::class,
			Search_Console_Widget::class,
		];

		$this->load_components( $admin_components );
	}

	/**
	 * Load frontend-specific components.
	 *
	 * @return void
	 */
	private function load_frontend_components(): void {
		$frontend_components = [
			Single::class,
			Product::class,
			Taxonomy::class,
			Title::class,
			Canonical::class,
			Common::class,
			Robots::class,
			Facebook::class,
			Twitter::class,
			Special_Page::class,
			Feed::class,
			Seo_Popup_Frontend::class,
			Meta_Data::class,
			Content_Seo::class,
			Meta_Tag_Injection::class,
			Xml_Sitemap::class,
			Archives::class,
		];

		$this->load_components( $frontend_components );
	}

	/**
	 * Check if NPS Survey should be loaded.
	 *
	 * @return bool True if should load.
	 */
	private function should_load_nps_survey(): bool {
		return class_exists( 'SureRank\Inc\Lib\Surerank_Nps_Survey' ) && ! apply_filters( 'surerank_disable_nps_survey', false );
	}

	/**
	 * Load an array of components.
	 *
	 * @param array<string> $components Component class names.
	 * @return void
	 */
	private function load_components( array $components ): void {
		foreach ( $components as $component ) {
			$component::get_instance();
		}
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Loader::get_instance();
