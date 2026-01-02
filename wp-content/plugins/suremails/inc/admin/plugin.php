<?php
/**
 * SureMails Plugin Class
 *
 * This file contains the main admin class for the SureMails plugin.
 *
 * @package SureMails\Admin
 */

namespace SureMails\Inc\Admin;

use SureMails\Inc\API\RecommendedPlugin;
use SureMails\Inc\Onboarding;
use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 *
 * Main class for the SureMails Plugin admin functionalities.
 */
class Plugin {
	use Instance;

	/**
	 * Plugin initialization function.
	 */
	protected function __construct() {
		// Hook into WordPress actions and filters.
		add_action( 'admin_init', [ $this, 'activation_redirect' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_notice_scripts' ] );
		add_action( 'admin_notices', [ $this, 'check_configuration' ] );
		add_action( 'admin_head', [ $this, 'hide_duplicate_menu_css' ] );

		// Add settings link to the plugin action links.
		add_filter( 'plugin_action_links_' . SUREMAILS_BASE, [ $this, 'add_settings_link' ] );
	}

	/**
	 * Plugin initialization function.
	 *
	 * @return void
	 */
	public function activation_redirect() {
		// Avoid redirection in case of WP_CLI calls.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			return;
		}

		// Avoid redirection in case of ajax calls.
		if ( wp_doing_ajax() ) {
			return;
		}

		$do_redirect = apply_filters( 'suremails_enable_redirect_activation', get_option( 'suremails_do_redirect' ) );

		if ( $do_redirect ) {

			update_option( 'suremails_do_redirect', false );

			if ( ! is_multisite() ) {
				$page = SUREMAILS;

				// Check if the user completed onboarding setup.
				$done_onboarding_setup = Onboarding::instance()->get_onboarding_status();
				// Check if the user has any connections (For old users).
				$connections = Settings::instance()->get_settings( 'connections' );

				if ( ! $done_onboarding_setup && ( empty( $connections ) || count( $connections ) === 0 ) ) {
					$page = SUREMAILS . '#/onboarding';
				}

				wp_safe_redirect(
					Utils::get_admin_url( str_replace( SUREMAILS, '', $page ) )
				);
				exit;
			}
		}
	}

	/**
	 * Check if the plugin is configured correctly and display a notice if not.
	 *
	 * @return void
	 */
	public function check_configuration() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// If notice is disabled (within expiry), do not show.
		if ( $this->is_notice_disabled() ) {
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$options      = Settings::instance()->get_settings();

		if ( ! empty( $options['connections'] ) || $current_page === SUREMAILS ) {
			return;
		}

		?>
			<div id="suremails-admin-notice" class="notice notice-warning is-dismissible">
			</div>
		<?php
	}

	/**
	 * Enqueue admin notice scripts.
	 *
	 * @return void
	 */
	public function enqueue_admin_notice_scripts() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// If notice is disabled (within expiry), do not enqueue.
		if ( $this->is_notice_disabled() ) {
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$options      = Settings::instance()->get_settings();
		// If the user is on the SureMails settings page or there are connections, don't show the notice.
		if ( ! empty( $options['connections'] ) || $current_page === SUREMAILS ) {
			return;
		}

		$assets = require_once SUREMAILS_DIR . 'build/admin-notice.asset.php';

		if ( ! isset( $assets ) ) {
			return;
		}

		wp_register_script(
			'suremails-admin-notice',
			SUREMAILS_PLUGIN_URL . 'build/admin-notice.js',
			[ 'wp-element', 'wp-dom-ready', 'wp-i18n', 'wp-api-fetch' ],
			$assets['version'],
			true
		);

		wp_enqueue_script(
			'suremails-admin-notice',
			SUREMAILS_PLUGIN_URL . 'build/admin-notice.js',
			[ 'wp-element', 'wp-dom-ready', 'wp-i18n' ],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'suremails-admin-notice',
			SUREMAILS_PLUGIN_URL . 'build/admin-notice.css',
			[],
			$assets['version'],
		);

		wp_localize_script(
			'suremails-admin-notice',
			'suremailsNotice',
			[
				'dashboardUrl'  => esc_url( Utils::get_admin_url( '/dashboard' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'onboardingURL' => Utils::get_admin_url( '/onboarding/welcome' ),
			]
		);

		// Set the script translations.
		wp_set_script_translations( 'suremails-admin-notice', 'suremails', SUREMAILS_DIR . 'languages' );
	}

	/**
	 * Add settings page to the WordPress admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'SureMail Settings', 'suremails' ),
			__( 'SureMail SMTP', 'suremails' ),
			'manage_options',
			SUREMAILS,
			[ $this, 'render_suremails_frontend' ],
			'none',
			30
		);

		// Add submenu items using helper function.
		$this->add_suremails_submenus();
	}

	/**
	 * Enqueue admin scripts and styles for the SureMails settings page.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Check if we're on a SureMails admin page.
		if ( ! $this->is_suremails_admin_page( $hook ) ) {
			return;
		}

		$assets = require_once SUREMAILS_DIR . '/build/main.asset.php';

		if ( ! isset( $assets ) ) {
			return;
		}

		wp_register_script(
			'suremails-react-script',
			SUREMAILS_PLUGIN_URL . 'build/main.js',
			[ 'wp-api-fetch', 'wp-components', 'wp-i18n', 'wp-hooks', 'updates' ],
			$assets['version'],
			true
		);

		// Enqueue your custom React script.
		wp_enqueue_script(
			'suremails-react-script',
			SUREMAILS_PLUGIN_URL . 'build/main.js', // Adjust the path if necessary.
			[ 'wp-element', 'wp-api-fetch', 'wp-dom-ready', 'wp-api', 'wp-components', 'wp-i18n', 'wp-hooks' ],
			$assets['version'],
			true // Load in footer.
		);

		wp_enqueue_script( 'suremails-suretriggers-integration', 'https://app.ottokit.com/js/v2/embed.js', [], SUREMAILS_VERSION, true );

		// RTL checks.
		$rtl_suffix = is_rtl() ? '-rtl' : '';
		$file_name  = 'main' . $rtl_suffix . '.css';

		// Enqueue your custom styles.
		wp_enqueue_style(
			'suremails-react-styles',
			SUREMAILS_PLUGIN_URL . 'build/' . $file_name,
			[],
			$assets['version'],
		);

		// Localize script to pass data to React.
		wp_localize_script(
			'suremails-react-script',
			'suremails',
			[
				'siteUrl'                      => esc_url( get_site_url( get_current_blog_id() ) ),
				'attachmentUrl'                => $this->get_attachment_url(),
				'userEmail'                    => wp_get_current_user()->user_email,
				'version'                      => SUREMAILS_VERSION,
				'nonce'                        => current_user_can( 'manage_options' ) ? wp_create_nonce( 'wp_rest' ) : '',
				'_ajax_nonce'                  => current_user_can( 'manage_options' ) ? wp_create_nonce( 'suremails_plugin' ) : '',
				'contentGuardPopupStatus'      => Settings::instance()->show_content_guard_lead_popup(),
				'contentGuardActiveStatus'     => get_option( 'suremails_content_guard_activated', 'no' ),
				'termsURL'                     => 'https://suremails.com/terms?utm_campaign=suremails&utm_medium=suremails-dashboard',
				'privacyPolicyURL'             => 'https://suremails.com/privacy-policy?utm_campaign=suremails&utm_medium=suremails-dashboard',
				'docsURL'                      => 'https://suremails.com/docs?utm_campaign=suremails&utm_medium=suremails-dashboard',
				'supportURL'                   => 'https://suremails.com/contact/?utm_campaign=suremails&utm_medium=suremails-dashboard',
				'adminURL'                     => Utils::get_admin_url(),
				'ottokit_connected'            => apply_filters( 'suretriggers_is_user_connected', '' ),
				'ottokit_admin_url'            => admin_url( 'admin.php?page=suretriggers' ),
				'pluginInstallationPermission' => current_user_can( 'install_plugins' ),
				'onboardingCompleted'          => Onboarding::instance()->get_onboarding_status(),
				'recommendedPluginsData'       => RecommendedPlugin::get_recommended_plugins_sequence(),
			]
		);

		// Set the script translations.
		wp_set_script_translations( 'suremails-react-script', 'suremails', SUREMAILS_DIR . 'languages' );

		// Hide duplicate main menu item in submenu.
		wp_add_inline_style(
			'suremails-react-styles',
			'
			#adminmenu .toplevel_page_' . SUREMAILS . ' .wp-submenu li.wp-first-item,
			#adminmenu .toplevel_page_' . SUREMAILS . ' .wp-submenu li.wp-first-item a { 
				display: none !important; 
			}
		'
		);
	}

	/**
	 * Render the React application inside the SureMails settings page.
	 *
	 * @return void
	 */
	public function render_suremails_frontend() {
		echo '<div id="suremails-root-app"></div>';
	}

	/**
	 * Add a "Settings" and a "Setup Wizard" link on the Plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Updated plugin action links.
	 */
	public function add_settings_link( array $links ) {

		$settings_url = Utils::get_admin_url( 'settings' );
		$links[]      = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'suremails' ) . '</a>';

		$wizard_url = Utils::get_admin_url( '/onboarding/welcome' );
		$links[]    = '<a href="' . esc_url( $wizard_url ) . '">' . __( 'Setup Wizard', 'suremails' ) . '</a>';

		return $links;
	}

	/**
	 * Hide duplicate menu item with CSS in admin head.
	 *
	 * @return void
	 */
	public function hide_duplicate_menu_css() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$logo_uri_active = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzE3NTEyXzM2NDAzKSI+CjxwYXRoIGQ9Ik0yOS4zMzMgMS4xMjEwOUMyOS45ODM0IDAuOTc1MjMxIDMwLjY0MjggMS4xNzQ0NiAzMS4wNzEzIDEuNjY0MDZDMzEuNTAzNCAyLjE1ODAyIDMxLjYxNzEgMi44NTc2IDMxLjM3NSAzLjQ1NTA4TDMxLjM3MyAzLjQ1ODk4TDIwLjk1OCAyOS44MTE1QzIwLjc1MzcgMzAuMjc5MSAyMC40MDY2IDMwLjYyMzMgMTkuOTc4NSAzMC43OTFDMTkuODE0NSAzMC44NTUzIDE5LjYxNjkgMzAuOTA0NSAxOS40NDA0IDMwLjkyMjlDMTguNzQ1IDMwLjk0NjEgMTguMTIyNyAzMC42MDM0IDE3LjgyMDMgMzAuMDI4M0wxNy44MTY0IDMwLjAyMTVMMTQuNjY3IDI0LjI0NzFIMTQuNjY2QzEzLjY4NjMgMjIuNDMxIDEzLjEwMTggMjAuNzMyNiAxMy4xMTQzIDE5LjE4NDZDMTMuMTI2NiAxNy42NjUzIDEzLjcxNDQgMTYuMjQzNCAxNS4xODc1IDE0Ljk0NjNMMjEuNDQ0MyA5LjU1MzcxTDIxLjQ1MjEgOS41NDY4OEwyMS40NiA5LjUzOTA2QzIxLjYxMjggOS4zOTU3MyAyMS44ODg0IDkuMzg3MTYgMjIuMDgxMSA5LjYwMTU2TDIyLjA4NzkgOS42MDkzOEMyMi4yMzEzIDkuNzYyMDggMjIuMjQwOSAxMC4wMzc2IDIyLjAyNjQgMTAuMjMwNUwyMi4wMjA1IDEwLjIzNTRMMTYuMDYzNSAxNS43NDhDMTQuODMyMyAxNi44MzM2IDE0LjI1MzkgMTcuOTc1MSAxNC4xODg1IDE5LjI1MkMxNC4xMjQ4IDIwLjQ5NiAxNC41NTQgMjEuODAzMyAxNS4xNzU4IDIzLjIxNDhMMTUuMTg4NSAyMy4yNDMyTDE1LjIwNDEgMjMuMjcwNUMxNS4yNDAyIDIzLjMzMSAxNS4yNzYxIDIzLjQwMzQgMTUuMzIzMiAyMy41MDFDMTUuMzYzOSAyMy41ODUxIDE1LjQxNTggMjMuNjkxNCAxNS40NzQ2IDIzLjc5MlYyMy43OTNMMTguNjI0IDI5LjU2NjRMMTguNjMyOCAyOS41ODJMMTguNjQyNiAyOS41OTc3QzE4Ljg3MjEgMjkuOTU0OSAxOS4yNTA1IDI5Ljk3NTMgMTkuMzE4NCAyOS45ODE0TDE5LjQzNTUgMjkuOTkxMkwxOS41NDEgMjkuOTUwMkMxOS41NDY2IDI5Ljk0ODQgMTkuNTY0MSAyOS45NDMzIDE5LjU4MTEgMjkuOTM3NUMxOS42MTc4IDI5LjkyNDkgMTkuNjczNyAyOS45MDQgMTkuNzM1NCAyOS44NjkxQzE5Ljg3MDQgMjkuNzkyNyAyMCAyOS42NjgyIDIwLjA4MyAyOS40Nzg1TDIwLjA4NjkgMjkuNDcwN0wyMC4wODk4IDI5LjQ2MTlMMzAuNTExNyAzLjA5Mjc3TDMwLjUxMDcgMy4wOTE4QzMwLjU3NzQgMi45MzEzOCAzMC42MDM2IDIuNzUzMDIgMzAuNTQ3OSAyLjU3MDMxQzMwLjQ5ODYgMi40MDg5OSAzMC4zOTU3IDIuMjk1NiAzMC4zNzQgMi4yNjk1M1YyLjI2ODU1TDMwLjMxOTMgMi4yMDg5OEMzMC4yNTgxIDIuMTQ3MiAzMC4xNzUyIDIuMDgxNzQgMzAuMDY2NCAyLjAzNDE4QzI5LjkxNTggMS45Njg0IDI5Ljc1MTEgMS45NTI3NCAyOS41ODQgMS45ODI0MkwyOS41NjY0IDEuOTg1MzVMMjkuNTQ3OSAxLjk5MDIzTDIuMDQxOTkgOS4wMDI5M0wyLjAxMTcyIDkuMDEwNzRMMS45ODM0IDkuMDIxNDhDMS42NzQ1OCA5LjE0MjQxIDEuNSA5LjM3NjI3IDEuNDQ1MzEgOS42MDc0MkwxLjQyOTY5IDkuNzA2MDVDMS40MjEzNyA5Ljc5ODExIDEuMzgxMjIgMTAuMTg5MyAxLjc1IDEwLjQ3MzZMMS43NTY4NCAxMC40Nzg1TDEuNzYzNjcgMTAuNDg0NEw1Ljg5NzQ2IDEzLjQ0NTNMNS45MDMzMiAxMy40NTAyTDUuOTEwMTYgMTMuNDU0MUM2LjA3OTg3IDEzLjU2ODQgNi4xNDY5IDEzLjg1MjYgNS45OTMxNiAxNC4wNjU0TDUuOTg4MjggMTQuMDcyM0w1Ljk4MzQgMTQuMDgwMUM1Ljg2OTA4IDE0LjI0OTkgNS41ODQwMiAxNC4zMTcxIDUuMzcxMDkgMTQuMTYzMUw1LjM3MDEyIDE0LjE2MTFMMS4yMzUzNSAxMS4yMDAyVjExLjE5OTJMMS4xMzI4MSAxMS4xMjExQzAuNjM5Njk4IDEwLjcxNjEgMC40MDg0NTYgMTAuMTAzNSAwLjUzNDE4IDkuNDk3MDdDMC42NzI4MDUgOC44Mjg4NyAxLjE2MjM3IDguMzE1OTcgMS44MTczOCA4LjEzOTY1TDI5LjMzMiAxLjEyMDEyTDI5LjMzMyAxLjEyMTA5WiIgZmlsbD0id2hpdGUiIHN0cm9rZT0id2hpdGUiLz4KPHBhdGggZD0iTTcuNDMxNjQgMTcuODI3MUM3LjUzNTg1IDE3LjcyOTMgNy43MzQxMiAxNy43MTQ5IDcuODc5ODggMTcuODc3TDcuODg2NzIgMTcuODg0OEM3Ljk4NDYgMTcuOTg5MSA3Ljk5ODk2IDE4LjE4NzMgNy44MzY5MSAxOC4zMzNMMy42ODc1IDIxLjk3NTZDMy42NjU2NCAyMS45OTM4IDMuNjQwNTggMjIuMDEyOCAzLjYxNzE5IDIyLjAyODNDMy42MDU4NiAyMi4wMzU4IDMuNTk2MyAyMi4wNDE0IDMuNTg5ODQgMjIuMDQ0OUMzLjU4Mjg5IDIyLjA0ODcgMy41ODI0IDIyLjA0OSAzLjU4Nzg5IDIyLjA0NjlDMy40MzI3MiAyMi4xMDc2IDMuMzE4ODUgMjIuMDcxNSAzLjIzNjMzIDIxLjk2NzhMMy4yMjM2MyAyMS45NTIxTDMuMjA5OTYgMjEuOTM3NUwzLjE3NzczIDIxLjg5MzZDMy4xMTIzMyAyMS43ODQ4IDMuMTE3ODMgMjEuNjE5MyAzLjI1NTg2IDIxLjQ5MjJMNy40MTg5NSAxNy44Mzc5TDcuNDI1NzggMTcuODMzTDcuNDMxNjQgMTcuODI3MVoiIGZpbGw9IndoaXRlIiBzdHJva2U9IndoaXRlIi8+CjxwYXRoIGQ9Ik05LjgxNzM4IDIxLjExOTFDOS45MDg4NyAyMS4wMzM5IDEwLjA3MjMgMjEuMDEzMSAxMC4yMDkgMjEuMTE4MkwxMC4yNjU2IDIxLjE3MDlMMTAuMjcyNSAyMS4xNzg3QzEwLjM1NzkgMjEuMjcwMiAxMC4zNzkzIDIxLjQzMzYgMTAuMjc0NCAyMS41NzAzTDEwLjIyMTcgMjEuNjI3TDMuMTU5MTggMjcuODExNUMzLjEzNzQxIDI3LjgyOTYgMy4xMTIzOSAyNy44NDg4IDMuMDg4ODcgMjcuODY0M0MzLjA3NzM2IDI3Ljg3MTggMy4wNjgwMSAyNy44NzczIDMuMDYxNTIgMjcuODgwOUwzLjA1OTU3IDI3Ljg4MjhDMi45Mzk1NSAyNy45Mjk0IDIuNzgwMzggMjcuODg5NSAyLjcwMTE3IDI3Ljc5MzlMMi42OTIzOCAyNy43ODIyTDIuNjgxNjQgMjcuNzcxNUMyLjU4NTE0IDI3LjY2NzggMi41NzExNiAyNy40NzIgMi43Mjk0OSAyNy4zMjcxTDkuODA1NjYgMjEuMTI5OUw5LjgxMTUyIDIxLjEyNUw5LjgxNzM4IDIxLjExOTFaIiBmaWxsPSJ3aGl0ZSIgc3Ryb2tlPSJ3aGl0ZSIvPgo8L2c+CjxkZWZzPgo8Y2xpcFBhdGggaWQ9ImNsaXAwXzE3NTEyXzM2NDAzIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiBmaWxsPSJ3aGl0ZSIvPgo8L2NsaXBQYXRoPgo8L2RlZnM+Cjwvc3ZnPgo=';

		$logo_uri = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzE3NTEyXzM2NDAzKSI+CjxwYXRoIGQ9Ik0yOS4zMzMgMS4xMjEwOUMyOS45ODM0IDAuOTc1MjMxIDMwLjY0MjggMS4xNzQ0NiAzMS4wNzEzIDEuNjY0MDZDMzEuNTAzNCAyLjE1ODAyIDMxLjYxNzEgMi44NTc2IDMxLjM3NSAzLjQ1NTA4TDMxLjM3MyAzLjQ1ODk4TDIwLjk1OCAyOS44MTE1QzIwLjc1MzcgMzAuMjc5MSAyMC40MDY2IDMwLjYyMzQgMTkuOTc4NSAzMC43OTFDMTkuODE0NSAzMC44NTUzIDE5LjYxNjkgMzAuOTA0NSAxOS40NDA0IDMwLjkyMjlDMTguNzQ1IDMwLjk0NjEgMTguMTIyNyAzMC42MDM0IDE3LjgyMDMgMzAuMDI4M0wxNy44MTY0IDMwLjAyMTVMMTQuNjY3IDI0LjI0NzFIMTQuNjY2QzEzLjY4NjMgMjIuNDMxIDEzLjEwMTggMjAuNzMyNiAxMy4xMTQzIDE5LjE4NDZDMTMuMTI2NiAxNy42NjUzIDEzLjcxNDQgMTYuMjQzNCAxNS4xODc1IDE0Ljk0NjNMMjEuNDQ0MyA5LjU1MzcxTDIxLjQ1MjEgOS41NDY4OEwyMS40NiA5LjUzOTA2QzIxLjYxMjggOS4zOTU3MyAyMS44ODg0IDkuMzg3MTYgMjIuMDgxMSA5LjYwMTU2TDIyLjA4NzkgOS42MDkzOEMyMi4yMzEzIDkuNzYyMDkgMjIuMjQwOSAxMC4wMzc2IDIyLjAyNjQgMTAuMjMwNUwyMi4wMjA1IDEwLjIzNTRMMTYuMDYzNSAxNS43NDhDMTQuODMyMyAxNi44MzM2IDE0LjI1MzkgMTcuOTc1MSAxNC4xODg1IDE5LjI1MkMxNC4xMjQ4IDIwLjQ5NiAxNC41NTQgMjEuODAzMyAxNS4xNzU4IDIzLjIxNDhMMTUuMTg4NSAyMy4yNDMyTDE1LjIwNDEgMjMuMjcwNUMxNS4yNDAyIDIzLjMzMSAxNS4yNzYxIDIzLjQwMzQgMTUuMzIzMiAyMy41MDFDMTUuMzYzOSAyMy41ODUxIDE1LjQxNTggMjMuNjkxNCAxNS40NzQ2IDIzLjc5MlYyMy43OTNMMTguNjI0IDI5LjU2NjRMMTguNjMyOCAyOS41ODJMMTguNjQyNiAyOS41OTc3QzE4Ljg3MjEgMjkuOTU0OSAxOS4yNTA1IDI5Ljk3NTMgMTkuMzE4NCAyOS45ODE0TDE5LjQzNTUgMjkuOTkxMkwxOS41NDEgMjkuOTUwMkMxOS41NDY2IDI5Ljk0ODQgMTkuNTY0MSAyOS45NDMzIDE5LjU4MTEgMjkuOTM3NUMxOS42MTc4IDI5LjkyNDkgMTkuNjczNyAyOS45MDQgMTkuNzM1NCAyOS44NjkxQzE5Ljg3MDQgMjkuNzkyNyAyMCAyOS42NjgyIDIwLjA4MyAyOS40Nzg1TDIwLjA4NjkgMjkuNDcwN0wyMC4wODk4IDI5LjQ2MTlMMzAuNTExNyAzLjA5Mjc3TDMwLjUxMDcgMy4wOTE4QzMwLjU3NzQgMi45MzEzNyAzMC42MDM2IDIuNzUzMDIgMzAuNTQ3OSAyLjU3MDMxQzMwLjQ5ODYgMi40MDg5OSAzMC4zOTU3IDIuMjk1NTkgMzAuMzc0IDIuMjY5NTNWMi4yNjg1NUwzMC4zMTkzIDIuMjA4OThDMzAuMjU4MSAyLjE0NzIgMzAuMTc1MiAyLjA4MTc0IDMwLjA2NjQgMi4wMzQxOEMyOS45MTU4IDEuOTY4NCAyOS43NTExIDEuOTUyNzQgMjkuNTg0IDEuOTgyNDJMMjkuNTY2NCAxLjk4NTM1TDI5LjU0NzkgMS45OTAyM0wyLjA0MTk5IDkuMDAyOTNMMi4wMTE3MiA5LjAxMDc0TDEuOTgzNCA5LjAyMTQ4QzEuNjc0NTggOS4xNDI0MSAxLjUgOS4zNzYyNyAxLjQ0NTMxIDkuNjA3NDJMMS40Mjk2OSA5LjcwNjA1QzEuNDIxMzcgOS43OTgxMSAxLjM4MTIxIDEwLjE4OTMgMS43NSAxMC40NzM2TDEuNzU2ODQgMTAuNDc4NUwxLjc2MzY3IDEwLjQ4NDRMNS44OTc0NiAxMy40NDUzTDUuOTAzMzIgMTMuNDUwMkw1LjkxMDE2IDEzLjQ1NDFDNi4wNzk4NyAxMy41Njg0IDYuMTQ2OSAxMy44NTI2IDUuOTkzMTYgMTQuMDY1NEw1Ljk4ODI4IDE0LjA3MjNMNS45ODM0IDE0LjA4MDFDNS44NjkwOCAxNC4yNDk5IDUuNTg0MDIgMTQuMzE3MSA1LjM3MTA5IDE0LjE2MzFMNS4zNzAxMiAxNC4xNjExTDEuMjM1MzUgMTEuMjAwMlYxMS4xOTkyTDEuMTMyODEgMTEuMTIxMUMwLjYzOTY5OCAxMC43MTYxIDAuNDA4NDU2IDEwLjEwMzUgMC41MzQxOCA5LjQ5NzA3QzAuNjcyODA1IDguODI4ODcgMS4xNjIzNyA4LjMxNTk3IDEuODE3MzggOC4xMzk2NUwyOS4zMzIgMS4xMjAxMkwyOS4zMzMgMS4xMjEwOVoiIGZpbGw9IiNBMEE1QUEiIHN0cm9rZT0iI0EwQTVBQSIvPgo8cGF0aCBkPSJNNy40MzE2NCAxNy44MjcxQzcuNTM1ODUgMTcuNzI5MyA3LjczNDEyIDE3LjcxNDkgNy44Nzk4OCAxNy44NzdMNy44ODY3MiAxNy44ODQ4QzcuOTg0NiAxNy45ODkxIDcuOTk4OTYgMTguMTg3MyA3LjgzNjkxIDE4LjMzM0wzLjY4NzUgMjEuOTc1NkMzLjY2NTY0IDIxLjk5MzggMy42NDA1OCAyMi4wMTI4IDMuNjE3MTkgMjIuMDI4M0MzLjYwNTg2IDIyLjAzNTggMy41OTYzIDIyLjA0MTQgMy41ODk4NCAyMi4wNDQ5QzMuNTgyODkgMjIuMDQ4NyAzLjU4MjQgMjIuMDQ5IDMuNTg3ODkgMjIuMDQ2OUMzLjQzMjcyIDIyLjEwNzYgMy4zMTg4NSAyMi4wNzE1IDMuMjM2MzMgMjEuOTY3OEwzLjIyMzYzIDIxLjk1MjFMMy4yMDk5NiAyMS45Mzc1TDMuMTc3NzMgMjEuODkzNkMzLjExMjMzIDIxLjc4NDggMy4xMTc4MyAyMS42MTkzIDMuMjU1ODYgMjEuNDkyMkw3LjQxODk1IDE3LjgzNzlMNy40MjU3OCAxNy44MzNMNy40MzE2NCAxNy44MjcxWiIgZmlsbD0iI0EwQTVBQSIgc3Ryb2tlPSIjQTBBNUFBIi8+CjxwYXRoIGQ9Ik05LjgxNzM4IDIxLjExOTFDOS45MDg4NyAyMS4wMzM5IDEwLjA3MjMgMjEuMDEzMSAxMC4yMDkgMjEuMTE4MkwxMC4yNjU2IDIxLjE3MDlMMTAuMjcyNSAyMS4xNzg3QzEwLjM1NzkgMjEuMjcwMiAxMC4zNzkzIDIxLjQzMzYgMTAuMjc0NCAyMS41NzAzTDEwLjIyMTcgMjEuNjI3TDMuMTU5MTggMjcuODExNUMzLjEzNzQxIDI3LjgyOTYgMy4xMTIzOSAyNy44NDg4IDMuMDg4ODcgMjcuODY0M0MzLjA3NzM2IDI3Ljg3MTggMy4wNjgwMSAyNy44NzczIDMuMDYxNTIgMjcuODgwOUwzLjA1OTU3IDI3Ljg4MjhDMi45Mzk1NSAyNy45Mjk0IDIuNzgwMzggMjcuODg5NSAyLjcwMTE3IDI3Ljc5MzlMMi42OTIzOCAyNy43ODIyTDIuNjgxNjQgMjcuNzcxNUMyLjU4NTE0IDI3LjY2NzggMi41NzExNiAyNy40NzIgMi43Mjk0OSAyNy4zMjcxTDkuODA1NjYgMjEuMTI5OUw5LjgxMTUyIDIxLjEyNUw5LjgxNzM4IDIxLjExOTFaIiBmaWxsPSIjQTBBNUFBIiBzdHJva2U9IiNBMEE1QUEiLz4KPC9nPgo8ZGVmcz4KPGNsaXBQYXRoIGlkPSJjbGlwMF8xNzUxMl8zNjQwMyI+CjxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0id2hpdGUiLz4KPC9jbGlwUGF0aD4KPC9kZWZzPgo8L3N2Zz4K'
		?>
		<style>
			#adminmenu .toplevel_page_<?php echo esc_attr( SUREMAILS ); ?> .wp-submenu li.wp-first-item,
			#adminmenu .toplevel_page_<?php echo esc_attr( SUREMAILS ); ?> .wp-submenu li.wp-first-item a {
				display: none !important;
			}

			#toplevel_page_<?php echo esc_attr( SUREMAILS ); ?> .wp-menu-image:before {
				content: "";
				background-image: url('<?php echo $logo_uri; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');
				background-position: center center;
				background-repeat: no-repeat;
				background-size: contain;
			}

			#toplevel_page_<?php echo esc_attr( SUREMAILS ); ?> .wp-menu-image {
				align-content: center;
			}

			#toplevel_page_<?php echo esc_attr( SUREMAILS ); ?>.wp-menu-open .wp-menu-image:before {
				background-image: url('<?php echo $logo_uri_active; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');
			}
		</style>
		<?php
	}

	/**
	 * Add SureMails submenu items.
	 *
	 * @return void
	 */
	private function add_suremails_submenus() {
		$submenu_items = [
			[
				'title' => __( 'Dashboard', 'suremails' ),
				'path'  => '/dashboard',
			],
			[
				'title' => __( 'Settings', 'suremails' ),
				'path'  => '/settings',
			],
			[
				'title' => __( 'Connections', 'suremails' ),
				'path'  => '/connections',
			],
			[
				'title' => __( 'Email Logs', 'suremails' ),
				'path'  => '/logs',
			],
			[
				'title' => __( 'Notifications', 'suremails' ),
				'path'  => '/notifications',
			],
		];

		foreach ( $submenu_items as $item ) {
			add_submenu_page(
				SUREMAILS,
				$item['title'],
				$item['title'],
				'manage_options',
				SUREMAILS . '#' . $item['path'],
				[ $this, 'render_suremails_frontend' ]
			);
		}
	}

	/**
	 * Get the attachment URL.
	 * This is used to display the attachment in the email log. The attachment URL is used to display the attachment in the email log.
	 * The attachment URL is different for multisite and single site installations. For multisite, the attachment URL is based on the current blog ID.
	 *
	 * @return string
	 */
	private function get_attachment_url() {

		$attachment_base_url = '';
		if ( is_multisite() ) {
			$current_blog_id     = get_current_blog_id();
			$attachment_base_url = esc_url( get_site_url( $current_blog_id ) ) . '/wp-content/uploads/sites/' . $current_blog_id . '/suremails/attachments/';
		} else {
			$attachment_base_url = esc_url( get_site_url() ) . '/wp-content/uploads/suremails/attachments/';
		}
		return $attachment_base_url;
	}

	/**
	 * Check if the current page is a SureMails admin page.
	 *
	 * @param string $hook The page hook.
	 * @return bool True if on a SureMails page, false otherwise.
	 */
	private function is_suremails_admin_page( $hook ) {
		// Main page.
		if ( $hook === 'toplevel_page_' . SUREMAILS ) {
			return true;
		}

		// Submenu pages.
		$submenu_hooks = [
			'suremails_page_' . SUREMAILS . '#/dashboard',
			'suremails_page_' . SUREMAILS . '#/settings',
			'suremails_page_' . SUREMAILS . '#/connections',
			'suremails_page_' . SUREMAILS . '#/logs',
			'suremails_page_' . SUREMAILS . '#/notifications',
			'suremails_page_' . SUREMAILS . '#/add-ons',
		];

		return in_array( $hook, $submenu_hooks, true );
	}

	/**
	 * Check if the notice is currently disabled.
	 *
	 * @return bool True if notice is disabled (within expiry), false if notice should be shown.
	 */
	private function is_notice_disabled() {
		$notice_expiry = get_option( 'suremails_notice_dismissal_time', 0 );
		if ( ! $notice_expiry ) {
			return false; // No expiry set, so notice is not disabled.
		}

		// Check if the current time is greater than or equal to the notice expiry time.
		if ( time() >= $notice_expiry ) {
			// Expired: remove the option so notice can be shown next time.
			delete_option( 'suremails_notice_dismissal_time' );
			return false; // Notice is NOT disabled anymore.
		}

		// Still within disabled period.
		return true; // Notice is disabled.
	}
}

// Instantiate the singleton instance of Plugin.
Plugin::instance();
