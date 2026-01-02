<?php
/**
 * Admin Dashboard
 *
 * @since 1.0.0
 * @package surerank
 */

namespace SureRank\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\API\Migrations;
use SureRank\Inc\API\Onboarding;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Import_Export\Settings_Exporter;
use SureRank\Inc\Modules\Nudges\Utils;
use SureRank\Inc\Sitemap\Xml_Sitemap;
use SureRank\Inc\Traits\Enqueue;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Admin Dashboard
 *
 * @method void wp_enqueue_scripts()
 * @since 1.0.0
 */
class Dashboard {

	use Enqueue;
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		$this->enqueue_scripts_admin();
		add_action( 'admin_menu', [ $this, 'add_menu_page' ], 9 );
		add_action( 'admin_init', [ $this, 'redirect_to_dashboard' ] );
		add_action( 'admin_head', [ $this, 'common_css' ] );
		add_action( 'admin_head', [ $this, 'common_js' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'site_seo_check_enqueue_scripts' ] );
	}

	/**
	 * Common JS for admin pages.
	 *
	 * @return void
	 */
	public function common_js() {    ?>
		<script type="text/javascript">
			// This is a common JS file for admin pages.
			// You can add your custom JS code here.
			jQuery(document).ready(function ($) {

				const checkScore = parseInt(window?.surerank_globals?.check_score) || 0;
				if (checkScore <= 0) {
					return;
				}
				const sidebarMenu = $('#toplevel_page_surerank > a > div.wp-menu-name');
				if (!sidebarMenu.length) {
					return;
				}

				// Check if the badge is already added.
				const notificationBadge = sidebarMenu.find('.awaiting-mod');
				if (notificationBadge.length) {
					notificationBadge.text(checkScore);
					return;
				}

				// Add space after the menu name if not already present.
				if (!sidebarMenu.text().endsWith(' ')) {
					sidebarMenu.text(sidebarMenu.text() + ' ');
				}

				// Create and add the badge.
				const badge = $('<span>', {
					class: 'awaiting-mod',
					text: checkScore
				});
				sidebarMenu.append(badge);
			});

// Handle Upgrade menu item click - redirect to pricing page
			jQuery(document).on('click', '#toplevel_page_surerank a[href*="surerank#/upgrade"]', function (e) {
				e.preventDefault();
				const pricingLink = window?.surerank_globals?.pricing_link + '?utm_medium=surerank_upgrade_menu';
				if (pricingLink && !pricingLink.includes('undefined')) {
					window.open(pricingLink, '_blank', 'noopener,noreferrer');
				}
			});
		</script>
		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function site_seo_check_enqueue_scripts() {
		// Add localize JS.
		wp_localize_script(
			'jquery',
			'surerank_globals',
			apply_filters(
				'surerank_globals_localization_vars',
				array_merge(
					[
						'check_score'                => $this->get_seo_score(),
						'exporter_options'           => Settings_Exporter::get_instance()->get_categories(),
						'dashboard_plugins_sequence' => $this->get_plugin_sequence(),
					],
					$this->get_common_variables(),
					$this->get_disabled_settings(),
				)
			)
		);
	}

	/**
	 * Get plugin sequence for dashboard.
	 *
	 * @return array<int,string> $sequence Plugin sequence.
	 * @since 1.4.2
	 */
	public function get_plugin_sequence() {
		$sequence = [
			'ultimate-addons-for-gutenberg',
			'sureforms',
			'suremails',
			'suretriggers',
		];

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$sequence = [
				'ultimate-addons-for-gutenberg',
				'sureforms',
				'suremails',
				'header-footer-elementor',
			];
		}

		return apply_filters( 'surerank_dashboard_plugins_sequence', $sequence );
	}

	/**
	 * Get SEO score.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_seo_score() {
		$seo_checks = Get::option( 'surerank_site_seo_checks', [] );

		if ( ! is_array( $seo_checks ) || empty( $seo_checks ) ) {
			return 0;
		}

		$seo_score = 0;
		foreach ( $seo_checks as $category ) {
			foreach ( $category as $check ) {
				if (
					isset( $check['status'] ) &&
					$check['status'] === 'error' &&
					! ( isset( $check['ignore'] ) && $check['ignore'] )
				) {
					$seo_score++;
				}
			}
		}

		return $seo_score;
	}

	/**
	 * Redirect to dashboard after plugin activation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function redirect_to_dashboard() {
		// Avoid redirection in case of WP_CLI calls.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			return;
		}

		// Avoid redirection in case of ajax calls.
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( isset( $_GET['skip_onboarding'] ) && 'true' === $_GET['skip_onboarding'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			update_option( 'surerank_onboarding_skipped', true );
			$this->redirect_to_page( 'surerank', '#/dashboard' );
		}

		$do_redirect = apply_filters( 'surerank_enable_redirect_on_activation', get_option( 'surerank_redirect_on_activation' ) );

		if ( 'yes' === $do_redirect ) {

			update_option( 'surerank_redirect_on_activation', 'no' );

			if ( ! is_multisite() ) {

				$onboarding_completed = get_option( 'surerank_onboarding_completed' );
				$onboarding_skipped   = get_option( 'surerank_onboarding_skipped' );

				if ( $onboarding_completed || $onboarding_skipped ) {
					$this->redirect_to_page( 'surerank', '#/dashboard' );
				} else {
					$this->redirect_to_page( 'surerank_onboarding' );
				}
			}
		}
	}

	/**
	 * Add menu page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_menu_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$menu_slug = 'surerank';

		// Add the main Dashboard Menu.
		add_menu_page(
			__( 'SureRank', 'surerank' ),
			__( 'SureRank', 'surerank' ),
			'manage_options',
			$menu_slug,
			static function () {
			},
			'none',
			30
		);

		// Register sub menus.
		$this->register_sub_menus( $menu_slug );
	}

	/**
	 * Register sub menus.
	 *
	 * @param string $menu_slug Menu slug.
	 * @since 1.0.0
	 * @return void
	 */
	public function register_sub_menus( $menu_slug ) {
		$capability = 'manage_options';

		$submenus = [
			[
				'id'         => $menu_slug,
				'page_title' => __( 'Dashboard', 'surerank' ),
			],
			[
				'id'         => apply_filters( 'surerank_general_menu_url', 'surerank#/general' ),
				'page_title' => __( 'General', 'surerank' ),
			],
			[
				'id'         => 'surerank#/advanced',
				'page_title' => __( 'Advanced', 'surerank' ),
			],
		];

		if ( Settings::get( 'enable_google_console' ) ) {
			$submenus[] = [
				'id'         => 'surerank#/search-console',
				'page_title' => __( 'Search Console', 'surerank' ),
			];
		}

		$submenus[] = [
			'id'         => 'surerank#/link-manager',
			'page_title' => __( 'Redirections', 'surerank' ),
		];

		$submenus[] = [
			'id'         => 'surerank#/tools',
			'page_title' => __( 'Tools', 'surerank' ),
		];

		if ( ! Utils::get_instance()->is_pro_active() ) {
			$submenus[] = [
				'id'         => 'surerank#/upgrade',
				'page_title' => __( 'Get Pro ↗', 'surerank' ),
			];
		}

		$submenus = apply_filters( 'surerank_wp_admin_submenus', $submenus );

		// Register the submenus.
		$submenu_map = [];

		foreach ( $submenus as $submenu ) {
			$submenu_map[ $submenu['id'] ] = $submenu;
		}

		// Register the submenus.
		foreach ( $submenu_map as $submenu ) {
			add_submenu_page(
				$menu_slug,
				$submenu['page_title'],
				$submenu['page_title'],
				$capability,
				$submenu['id'],
				[ $this, 'render_dashboard' ]
			);
		}
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( empty( $screen->base ) ) {
			return;
		}

		// Is page is top level page.
		$is_top_level_page = 'toplevel_page_surerank' === $screen->base;

		// surerank_page string should be in the start of the $screen->base.
		if ( ! ( 0 === strpos( $screen->base, 'surerank_page' ) || $is_top_level_page ) ) {
			return;
		}

		$page_id = '';
		if ( $is_top_level_page ) {
			$page_id = 'admin-dashboard';
		} else {
			$page_id = str_replace( 'surerank_page_surerank_', 'admin-', $screen->base );

			// If page id has _ then replace it with -.
			if ( strpos( $page_id, '_' ) ) {
				$page_id = str_replace( '_', '-', $page_id );
			}

			// Use admin-dashboard for all main admin pages since they're now combined.
			if ( 'admin-settings' === $page_id ) {
				$page_id = 'admin-dashboard';
			}
		}

		// Load page specific assets.
		$this->build_assets_operations( $page_id, [], [ 'updates' ] );

		// Enqueue vendor and common assets.
		$this->enqueue_vendor_and_common_assets();

		// Load common assets.
		$this->load_common_assets( $page_id );
	}

	/**
	 * Load common assets. which are required on all admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_id Page id.
	 * @return void
	 */
	public function load_common_assets( $page_id ) {
		$this->style_operations(
			'inter-google-font',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap',
			[],
			''
		);

		wp_enqueue_media();

		$this->build_assets_operations(
			$page_id,
			[
				'hook'        => $page_id,
				'object_name' => 'admin_common',
				'data'        => apply_filters(
					'surerank_dashboard_localization_vars',
					[
						'social_profiles'             => Onboarding::social_profiles(),
						'website_details'             => Helper::website_details(),
						'sitemap_url'                 => Xml_Sitemap::get_instance()->get_sitemap_url(),
						'onboarding_complete_status'  => get_option( 'surerank_onboarding_completed', false ) ? 'yes' : 'no',
						'plugins_for_migration'       => Migrations::get_instance()->get_available_plugins(),
						'migration_ever_completed'    => Migrations::has_migration_ever_completed(),
						'migration_completed_plugins' => Migrations::get_completed_migrations(),
						'active_cache_plugins'        => Migrations::is_cache_plugin_active(),
						'robots_data'                 => Helper::get_robots_data(),
						'wp_reading_settings_url'     => admin_url( 'options-reading.php' ),
					]
				),
			]
		);
	}

	/**
	 * Get common admin assets variables.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_common_variables() {
		return apply_filters(
			'surerank_common_localization_vars',
			[
				'_ajax_nonce'                => current_user_can( 'manage_options' ) ? wp_create_nonce( 'surerank_plugin' ) : '',
				'admin_assets_url'           => SURERANK_URL . 'inc/admin/assets',
				'version'                    => SURERANK_VERSION,
				'help_link'                  => esc_url( 'https://surerank.com/docs/' ),
				'support_link'               => esc_url( 'https://surerank.com/support/' ),
				'rating_link'                => esc_url( 'https://wordpress.org/support/plugin/surerank/reviews/#new-post' ),
				'community_link'             => esc_url( 'https://www.facebook.com/groups/surecrafted' ),
				'pricing_link'               => esc_url( 'https://surerank.com/pricing/' ),
				'pro_link'                   => esc_url( 'https://surerank.com/pro/' ),
				'privacy_policy_url'         => esc_url( 'https://surerank.com/privacy-policy/' ),
				'surerank_url'               => esc_url( 'https://surerank.com' ),
				'wp_dashboard_url'           => admin_url( 'admin.php' ),
				'site_url'                   => site_url(),
				'wp_general_settings_url'    => admin_url( 'options-general.php' ),
				'url_length'                 => Get::url_length(),
				'description_length'         => Get::description_length(),
				'title_length'               => Get::title_length(),
				'open_graph_tags'            => apply_filters( 'surerank_disable_open_graph_tags', false ),
				'input_variable_suggestions' => $this->get_input_variable_suggestions(),
				'nudges'                     => Utils::get_instance()->get_nudges(),
				'wp_schema_pro_active'       => Helper::is_wp_schema_pro_active(),
			]
		);
	}

	/**
	 * Get disabled settings.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_disabled_settings() {
		return [
			'enable_page_level_seo' => Settings::get( 'enable_page_level_seo' ),
			'enable_google_console' => Settings::get( 'enable_google_console' ),
			'enable_schemas'        => Settings::get( 'enable_schemas' ),
			'enable_migration'      => Settings::get( 'enable_migration' ),
		];
	}

	/**
	 * Render Html template.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function render_dashboard() {
		Update::option( 'surerank_site_seo_checks_score', 0 );
		echo "<div class='surerank-root surerank-setting-page surerank-styles'><div id='surerank-root'></div></div>";
	}

	/**
	 * Common CSS for admin pages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function common_css() {
		// Early return if it's not admin page.
		if ( ! is_admin() ) {
			return;
		}

		$logo_uri        = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEzLjU1MzcgMS41QzE3Ljg0NTMgMS41IDIxLjMyNTEgNC45Nzg5NSAyMS4zMjUyIDkuMjcwNTFDMjEuMzI1MiAxMi4zNDcgMTkuNTM2OCAxNS4wMDU2IDE2Ljk0MzQgMTYuMjY0NkgyMS4zMjUyVjIyLjVIMTguMDg4OUMxNC45MDg2IDIyLjUgMTIuMjg2MSAyMC4xMTg2IDExLjkwMzMgMTcuMDQySDExLjkwMTRMMTEuOTAzMyAxMy43ODUyQzE0LjgyODMgMTMuNzY2MSAxNy4wMzQyIDExLjM4OTQgMTcuMDM0MiA4LjQ1OTk2VjYuMDI5M0MxNC4xMzcgNi4wMjk0NyAxMS42OTQ4IDcuOTc2ODIgMTAuOTQ0MyAxMC42MzM4QzEwLjE2MDUgOS41MzM0NSA4Ljg3MzgzIDguODE2NSA3LjQxOTkyIDguODE2NDFINi4zODA4NlY5Ljg1MzUySDYuMzgzNzlDNi40NDUxNSAxMi4wMzU2IDguMjMzNzUgMTMuNzg2IDEwLjQzMDcgMTMuNzg2MUgxMC43MDYxTDEwLjY5MzQgMTcuMDQySDEwLjY4NjVDMTAuMjk0MyAyMC4xMDgyIDcuNjc2NzggMjIuNDc4NSA0LjUwMzkxIDIyLjQ3ODVIMi42NzQ4VjEuNUgxMy41NTM3WiIgZmlsbD0iI0EwQTVBQSIvPgo8L3N2Zz4K'; // 24px x 24px in #A0A5AA
		$logo_uri_active = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEzLjU1MzcgMS41QzE3Ljg0NTMgMS41IDIxLjMyNTEgNC45Nzg5NSAyMS4zMjUyIDkuMjcwNTFDMjEuMzI1MiAxMi4zNDcgMTkuNTM2OCAxNS4wMDU2IDE2Ljk0MzQgMTYuMjY0NkgyMS4zMjUyVjIyLjVIMTguMDg4OUMxNC45MDg2IDIyLjUgMTIuMjg2MSAyMC4xMTg2IDExLjkwMzMgMTcuMDQySDExLjkwMTRMMTEuOTAzMyAxMy43ODUyQzE0LjgyODMgMTMuNzY2MSAxNy4wMzQyIDExLjM4OTQgMTcuMDM0MiA4LjQ1OTk2VjYuMDI5M0MxNC4xMzcgNi4wMjk0NyAxMS42OTQ4IDcuOTc2ODIgMTAuOTQ0MyAxMC42MzM4QzEwLjE2MDUgOS41MzM0NSA4Ljg3MzgzIDguODE2NSA3LjQxOTkyIDguODE2NDFINi4zODA4NlY5Ljg1MzUySDYuMzgzNzlDNi40NDUxNSAxMi4wMzU2IDguMjMzNzUgMTMuNzg2IDEwLjQzMDcgMTMuNzg2MUgxMC43MDYxTDEwLjY5MzQgMTcuMDQySDEwLjY4NjVDMTAuMjk0MyAyMC4xMDgyIDcuNjc2NzggMjIuNDc4NSA0LjUwMzkxIDIyLjQ3ODVIMi42NzQ4VjEuNUgxMy41NTM3WiIgZmlsbD0iI0ZGRkZGRiIvPgo8L3N2Zz4K'; // 24px x 24px in #FFFFFF

		?>
		<style>
			#toplevel_page_surerank .wp-submenu li:has(a[href="admin.php?page=surerank_onboarding"]) {
				display: none !important;
			}

			#toplevel_page_surerank .wp-menu-image:before {
				content: "";
				background-image: url('<?php echo $logo_uri; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');
				background-position: center center;
				background-repeat: no-repeat;
				background-size: contain;
			}

			#toplevel_page_surerank .wp-menu-image {
				align-content: center;
			}

			#toplevel_page_surerank.wp-menu-open .wp-menu-image:before {
				background-image: url('<?php echo $logo_uri_active; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');
			}

			/* Upgrade menu item styling */
			#toplevel_page_surerank .wp-submenu li a[href*="surerank#/upgrade"] {
				color: #fff !important;
				font-size: 13px !important;
				font-weight: 500 !important;
				line-height: 20px !important;
				padding-right: 12px !important;
				padding-left: 12px !important;
			}

			#toplevel_page_surerank .wp-submenu li a[href*="surerank#/upgrade"]:hover {
				color: #fff !important;
			}
		</style>
		<?php
	}

	/**
	 * Redirect to a specific admin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page Page slug to redirect.
	 * @param string $hash Optional hash fragment to append.
	 * @return void
	 */
	private function redirect_to_page( $page, $hash = '' ) {
		$url = add_query_arg(
			[ 'page' => $page ],
			admin_url( 'admin.php' )
		);

		if ( ! empty( $hash ) ) {
			$url .= $hash;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Get input variable suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string,string>>
	 */
	private function get_input_variable_suggestions() {
		return apply_filters(
			'surerank_input_variable_suggestions',
			[
				[
					'label'       => __( 'Site Name', 'surerank' ),
					'value'       => '%site_name%',
					'description' => __( 'The name of the site.', 'surerank' ),
				],
				[
					'label'       => __( 'Tagline', 'surerank' ),
					'value'       => '%tagline%',
					'description' => __( 'The tagline of the site.', 'surerank' ),
				],
				[
					'label'       => __( 'Term Title', 'surerank' ),
					'value'       => '%term_title%',
					'description' => __( 'The name of the term.', 'surerank' ),
				],
				[
					'label'       => __( 'Post Title', 'surerank' ),
					'value'       => '%title%',
					'description' => __( 'The title of the post.', 'surerank' ),
				],
				[
					'label'       => __( 'Post Excerpt', 'surerank' ),
					'value'       => '%excerpt%',
					'description' => __( 'The excerpt of the post.', 'surerank' ),
				],
				[
					'label'       => __( 'Post Content', 'surerank' ),
					'value'       => '%content%',
					'description' => __( 'The content of the post.', 'surerank' ),
				],
				[
					'label'       => __( 'Term Description', 'surerank' ),
					'value'       => '%term_description%',
					'description' => __( 'The description of the term.', 'surerank' ),
				],
				[
					'label'       => __( 'Date Published', 'surerank' ),
					'value'       => '%published%',
					'description' => __( 'Publication date of the current post/page OR specified date on date archives', 'surerank' ),
				],
				[
					'label'       => __( 'Date Modified', 'surerank' ),
					'value'       => '%modified%',
					'description' => __( 'Last modification date of the current post/page', 'surerank' ),
				],
				[
					'label'       => __( 'Post URL', 'surerank' ),
					'value'       => '%permalink%',
					'description' => __( 'URL of the current post/page', 'surerank' ),
				],
				[
					'label'       => __( 'Current Date', 'surerank' ),
					'value'       => '%currentdate%',
					'description' => __( 'Current server date', 'surerank' ),
				],
				[
					'label'       => __( 'Current Day', 'surerank' ),
					'value'       => '%currentday%',
					'description' => __( 'Current server day', 'surerank' ),
				],
				[
					'label'       => __( 'Current Month', 'surerank' ),
					'value'       => '%currentmonth%',
					'description' => __( 'Current server month', 'surerank' ),
				],
				[
					'label'       => __( 'Current Year', 'surerank' ),
					'value'       => '%currentyear%',
					'description' => __( 'Current server year', 'surerank' ),
				],
				[
					'label'       => __( 'Current Time', 'surerank' ),
					'value'       => '%currenttime%',
					'description' => __( 'Current server time', 'surerank' ),
				],
				[
					'label'       => __( 'Organization Name', 'surerank' ),
					'value'       => '%org_name%',
					'description' => __( 'The Organization Name added in Local SEO Settings.', 'surerank' ),
				],
				[
					'label'       => __( 'Organization Logo', 'surerank' ),
					'value'       => '%org_logo%',
					'description' => __( 'Organization Logo added in Local SEO Settings.', 'surerank' ),
				],
				[
					'label'       => __( 'Organization URL', 'surerank' ),
					'value'       => '%org_url%',
					'description' => __( 'Organization URL added in Local SEO Settings.', 'surerank' ),
				],
				[
					'label'       => __( 'Post Author Name', 'surerank' ),
					'value'       => '%author_name%',
					'description' => __( "Display author's nicename of the current post, page or author archive.", 'surerank' ),
				],
			]
		);
	}

}
