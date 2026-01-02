<?php
/**
 * Search Console Dashboard Widget
 *
 * @since 1.5.0
 * @package surerank
 */

namespace SureRank\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\GoogleSearchConsole\Auth;
use SureRank\Inc\GoogleSearchConsole\Controller;
use SureRank\Inc\Traits\Enqueue;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Search Console Widget
 *
 * @method void wp_enqueue_scripts()
 * @since 1.5.0
 */
class Search_Console_Widget {

	use Enqueue;
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function __construct() {
		if ( ! current_user_can( 'manage_options' ) || ! Settings::get( 'enable_google_console' ) ) {
			return;
		}

		$this->enqueue_scripts_admin();
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
	}

	/**
	 * Register dashboard widget
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function register_dashboard_widget() {
		wp_add_dashboard_widget(
			'surerank_search_console_widget',
			__( 'SureRank Website Insights', 'surerank' ),
			[ $this, 'render_widget' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render widget content
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_widget() {
		echo '<div class="surerank-root surerank-widget surerank-styles"><div id="surerank-widget-root"></div></div>';
	}

	/**
	 * Enqueue widget assets
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		// Only load on dashboard page.
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		// Enqueue widget assets.
		$this->enqueue_vendor_and_common_assets();
		$this->build_assets_operations( 'search-console-widget', $this->get_widget_localized_data(), [ 'updates' ] );
	}

	/**
	 * Get widget localized data
	 *
	 * @since 1.5.0
	 * @return array<string, array<string, bool|string>|string> Localized data for the widget
	 */
	private function get_widget_localized_data() {
		return [
			'hook'        => 'search-console-widget',
			'object_name' => 'search_console_widget',
			'data'        => [
				'is_gsc_connected'      => Controller::get_instance()->get_auth_status(),
				'has_gsc_site_selected' => ! empty( Auth::get_instance()->get_credentials( 'site_url' ) ),
				'gsc_selected_site'     => Auth::get_instance()->get_credentials( 'site_url' ),
				'settings_page_url'     => admin_url( 'admin.php?page=surerank#/search-console' ),
				'_ajax_nonce'           => wp_create_nonce( 'surerank_plugin' ),
			],
		];
	}
}
