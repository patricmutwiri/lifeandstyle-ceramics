<?php
/**
 * Getting Started Admin
 *
 * @since 1.0.0
 * @package Getting Started Admin
 */

namespace GS\Classes;

/**
 * GS Admin
 */
class GS_Admin {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 1.0.0
	 */
	private static $instance = null;

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
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! $this->is_show_setup() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'setup_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_head', array( $this, 'hide_notices_on_getting_started_page' ) );

		// Finish Setup notice.
		add_action( 'admin_notices', array( $this, 'display_dashboard_banner' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_notice_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_notice_scripts' ) );

		// AJAX handlers for notice dismissal.
		add_action( 'wp_ajax_dismiss_gs_notice', array( $this, 'handle_dismiss_notice_ajax' ) );
	}

	/**
	 * Check if show setup wizard.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_show_setup() {
		$option_name       = GS_Helper::get_setup_wizard_showing_option_name();
		$is_wizard_showing = apply_filters( 'getting_started_is_setup_wizard_showing', get_option( $option_name, false ) );

		if ( $is_wizard_showing ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the icon for the Getting Started page.
	 *
	 * @param string $fill_color The fill color for the icon.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_icon( $fill_color = 'currentColor' ) {
		$icon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12.002 8.712L13.5087 7.20467C14.0659 6.64757 14.508 5.98615 14.8097 5.25819C15.1113 4.53023 15.2666 3.74998 15.2667 2.962V0.733334H13.038C12.2501 0.733327 11.4699 0.888516 10.7419 1.19004C10.0139 1.49156 9.3525 1.93352 8.79534 2.49067L7.28801 3.99733L3.51667 3.526L0.693341 6.34933L9.65001 15.3067L12.4733 12.4833L12.002 8.712ZM10.8073 9.906L11.0693 12.0013L9.65001 13.4207L8.47134 12.242L10.8073 9.906ZM6.09401 5.192L3.75734 7.528L2.58001 6.34933L3.99934 4.93L6.09401 5.192ZM3.75734 11.2987L1.40067 13.656L0.458008 12.7133L2.81467 10.3567L3.75734 11.2987ZM5.64334 13.1853L3.28601 15.542L2.34334 14.5993L4.70001 12.242L5.64334 13.1853Z" fill="' . $fill_color . '"/>
			</svg>';

		return apply_filters( 'getting_started_icon', $icon );
	}

	/**
	 * Add submenu to admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup_menu() {
		if ( current_user_can( 'manage_options' ) ) {
			$parent_slug   = 'getting-started';
			$capability    = 'manage_options';
			$menu_priority = apply_filters( 'getting_started_menu_priority', 1 );

			// Get the count of incomplete steps.
			$incomplete_steps = GS_Helper::get_incomplete_actions_count();

			$menu_text   = __( 'Finish Setup', 'astra-sites' );
			$bubble_text = $incomplete_steps
				? '<span class="awaiting-mod">' . $incomplete_steps . '</span>'
				: '<span class="awaiting-mod" style="background-color: #15803d;"><svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="scale: 1.5; translate: 0 1px;"><path d="M20 6 9 17l-5-5"/></svg></span>';

			add_menu_page(
				$menu_text,
				'<span>' . $menu_text . '</span> ' . $bubble_text,
				$capability,
				$parent_slug,
				array( $this, 'render_page' ),
				'data:image/svg+xml;base64,' . base64_encode( self::get_icon( 'white' ) ), //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$menu_priority
			);
		}
	}

	/**
	 * Renders the admin settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_page() {
		echo "<div id='getting-started-page' class='getting-started-style'></div>";
	}

	/**
	 * Load script for block editor and elementor editor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'getting-started' !== sanitize_text_field( $_GET['page'] ) ) {
			return;
		}

		$this->load_script();
	}

	/**
	 * Load all the required files in the importer.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_script() {

		$handle            = 'getting-started-script';
		$build_path        = GS_DIR . 'build/';
		$build_url         = GS_URL . 'build/';
		$script_asset_path = $build_path . 'main.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => GS_VER,
			);

		$script_dep = array_merge( $script_info['dependencies'], array( 'jquery' ) );

		wp_enqueue_script(
			$handle,
			$build_url . 'main.js',
			$script_dep,
			$script_info['version'],
			true
		);

		$content = $this->getting_started_content();

		$data = apply_filters(
			'getting_started_vars',
			array(
				'ajaxurl'                => esc_url( admin_url( 'admin-ajax.php' ) ),
				'_ajax_nonce'            => wp_create_nonce( 'getting-started' ),
				'rest_api_nonce'         => ( current_user_can( 'manage_options' ) ) ? wp_create_nonce( 'wp_rest' ) : '',
				'icon'                   => self::get_icon(),
				'iconURL'                => esc_url( apply_filters( 'getting_started_logo_url', '' ) ),
				'title'                  => sanitize_text_field( $content['title'] ),
				'description'            => sanitize_text_field( $content['description'] ),
				'footerLogoURL'          => esc_url_raw( $content['footer_logo'] ),
				'footerPluginName'       => sanitize_text_field( $content['footer_plugin_name'] ),
				'footerPluginURL'        => esc_url_raw( $content['footer_plugin_url'] ),
				'congratulationsTitle'   => sanitize_text_field( $content['congratulations_title'] ),
				'congratulationsContent' => sanitize_text_field( $content['congratulations_content'] ),
				'adminDashboardURL'      => admin_url(),
			)
		);

		// Add localize JS.
		wp_localize_script(
			$handle,
			'gettingStartedVars',
			$data
		);

		// Enqueue CSS.
		wp_enqueue_style( 'getting-started-style', GS_URL . 'build/style-main.css', array(), GS_VER );
		wp_enqueue_style( 'getting-started-google-fonts', $this->google_fonts_url(), array(), 'all' );
	}

	/**
	 * Get the Getting Started content.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	public function getting_started_content() {

		return apply_filters(
			'getting_started_content',
			array(
				'title'                   => $this->get_title(),
				'description'             => __( 'Complete these steps to take full control of your website.', 'astra-sites' ),
				'footer_logo'             => '',
				'footer_plugin_name'      => 'Starter Templates',
				'footer_plugin_url'       => 'https://startertemplates.com/',
				'congratulations_title'   => __( 'Awesome, You did it! 😍', 'astra-sites' ),
				'congratulations_content' => $this->get_congratulations_content(),
			)
		);
	}

	/**
	 * Generate and return the Google fonts url.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function google_fonts_url() {

		$fonts_url     = '';
		$font_families = array(
			'Figtree:400,500,600,700',
		);

		$query_args = array(
			'family' => rawurlencode( implode( '|', $font_families ) ),
			'subset' => rawurlencode( 'latin,latin-ext' ),
		);

		$fonts_url = add_query_arg( $query_args, '//fonts.googleapis.com/css' );

		return $fonts_url;
	}

	/**
	 * Get title for the page.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Hi there 👋', 'astra-sites' );
	}

	/**
	 * Get the Congratulations section content.
	 *
	 * @since 1.0.0
	 * @return string|false
	 */
	public function get_congratulations_content() {
		return __(
			'🎉 Congratulations on completing the tasks and instructional videos to take full control of your website.\n 🚀 Now, you\'re well-equipped to make your website thrive. Best of luck with your website journey, and may it bring you great achievements and opportunities in the digital world!',
			'astra-sites'
		);
	}

	/**
	 * Hide all admin notices on the Getting Started page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function hide_notices_on_getting_started_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'getting-started' === $_GET['page'] ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );

			// Add custom CSS to hide any remaining notices.
			echo '<style>
				.notice, .updated, .update-nag, .error, .warning {
					display: none !important;
				}
			</style>';
		}
	}

	/**
	 * Handle AJAX request for dismissing notices.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function handle_dismiss_notice_ajax() {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['_security'] ?? '' ), 'gs_dismiss_woopayments_notice' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security verification failed.', 'astra-sites' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Insufficient permissions.', 'astra-sites' ),
				)
			);
		}

		// Get notice ID.
		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( 'Notice ID is required' );
		}

		$show_after = 14 * 24 * 60 * 60; // 2 weeks in seconds.

		// Set a transient that expires in 2 weeks (1209600 seconds) - to dismiss the notice.
		set_transient( 'getting_started_notice_dismissed', true, $show_after );

		wp_send_json_success( 'Notice dismissed successfully' );
	}

	/**
	 * Check if notice is dismissed.
	 *
	 * @since 1.0.2
	 * @return bool True if dismissed, false otherwise.
	 */
	private function is_notice_dismissed() {
		// Check if the banner was dismissed and the transient is still valid.
		if ( get_transient( 'getting_started_notice_dismissed' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Custom Elementor Dashboard Banner.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function display_dashboard_banner() {
		// Show only on Dashboard page.
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'dashboard' !== $screen->base ) {
			return;
		}

		if ( $this->is_notice_dismissed() ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible getting-started-banner">
			<div class="gs-banner-content">
				<div class="gs-rocket-icon">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M23.3225 0.67751C17.7411 0.102693 12.2976 2.77559 8.94353 7.14704C7.22192 9.38599 6.05218 12.0675 5.6958 14.9933L9.00675 18.3042C11.9326 17.9479 14.6141 16.7781 16.853 15.0565C21.2245 11.7025 23.8974 6.25897 23.3225 0.67751ZM14.4043 9.59576C13.3869 8.57834 13.3868 6.92866 14.4043 5.91122C15.4217 4.89378 17.0714 4.89378 18.0888 5.9112C19.1063 6.92864 19.1063 8.57836 18.0888 9.5958C17.0714 10.6132 15.4217 10.6132 14.4043 9.59576Z" fill="#5C2DDD"/>
						<path d="M6.38956 14.2994C4.82152 13.8559 3.16127 13.9465 1.42889 14.4546C2.3285 9.64049 5.46985 7.28663 9.63729 6.45312C10.0005 9.37189 8.98318 12.1031 6.38956 14.2994Z" fill="#5C2DDD"/>
						<path d="M9.7005 17.6104C10.144 19.1785 10.0534 20.8387 9.54529 22.5711C14.3594 21.6715 16.7132 18.5302 17.5468 14.3627C14.628 13.9996 11.8968 15.0168 9.7005 17.6104Z" fill="#5C2DDD"/>
						<path d="M6.33459 21.409C4.9694 22.7742 1.4458 22.6132 1.4458 22.6132C1.4458 22.6132 1.28482 19.0896 2.65001 17.7245C3.42891 16.9456 4.20204 16.707 4.91191 16.8306C4.04111 17.7302 4.10721 19.9518 4.10721 19.9518C4.10721 19.9518 6.32888 20.0179 7.22842 19.1471C7.35204 19.857 7.11349 20.6301 6.33459 21.409Z" fill="#5C2DDD"/>
					</svg>
				</div>
				<h2>
					<?php esc_html_e( 'Your Website is Almost Ready!', 'astra-sites' ); ?>
				</h2>
				<div class="gs-banner-inner-content">
					<p>
						<?php esc_html_e( 'This quick, checklist-style course walks you through the final steps of site settings, essential setups, and more so your website is polished, professional, and ready for the world!', 'astra-sites' ); ?>
					</p>
					<a
						href="<?php echo esc_url( admin_url( 'admin.php?page=getting-started&source=dashboard-banner' ) ); ?>"
						class="button button-primary gs-finish-setup-button"
					>
						<?php esc_html_e( 'Continue to finish setup', 'astra-sites' ); ?>
					</a>
				</div>
			</div>

			<svg width="137" height="128" viewBox="0 0 137 128" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M92.5056 13.6258L98.6457 15.9344C102.265 13.8868 106.857 7.06253 111.303 10.3427L127.083 26.1375C129.898 30.6909 122.581 35.1312 121.19 38.9364L123.523 44.6006C128.254 46.0159 136.227 43.7305 136.813 50.5751C137.388 57.2776 136.421 64.8531 136.735 71.6484C135.803 77.2053 127.577 75.2708 123.604 76.7731L121.211 82.6664C122.515 85.2825 127.545 89.5546 127.804 92.304C127.969 94.0616 126.839 95.3754 125.797 96.6167C123.087 99.8418 114.905 108.25 111.681 110.599C106.319 114.503 102.729 107.159 98.2275 105.286L92.6015 107.757C91.0447 111.942 93.2201 120.216 87.1033 120.79C80.179 121.44 72.1685 120.338 65.131 120.709C60.1934 119.88 61.4074 111.44 60.5216 107.797L54.3321 105.228L46.3826 111.388C44.8316 112.203 42.7607 111.895 41.4304 110.799L25.9438 95.2913C23.0103 90.8075 29.7022 86.2367 31.7905 82.7187L29.2171 76.5382C24.9301 75.4796 16.7395 77.1937 16.2051 71.1466C15.5864 64.1338 16.5885 56.0856 16.3416 48.9713C17.4482 44.2003 25.9322 46.0275 29.4059 44.3598L31.7411 38.5448C29.0806 33.7855 22.1854 30.543 26.9371 24.815C29.9432 21.1897 36.7862 14.3944 40.4168 11.3549C42.3483 9.73657 43.6959 8.68377 46.2722 9.86418C47.8755 10.6008 53.7396 15.9924 54.579 15.9402L60.4286 13.3909C61.8838 8.86359 59.5892 0.940087 66.2114 0.348434C71.2536 -0.101106 81.7968 -0.135909 86.8013 0.362935C93.2463 1.00679 91.1115 9.14492 92.5086 13.6229L92.5056 13.6258ZM72.6826 33.4462C51.265 35.6243 41.5582 62.1123 55.2006 78.232C73.127 99.4096 107.69 84.1688 103.72 56.7033C101.457 41.039 88.2128 31.8684 72.6826 33.4462Z" fill="#EDE6FF"/>
				<path d="M58.7332 112.899L33.5288 117.777L34.5398 124.137L66.1511 125.266L73.3574 111.688L58.7332 112.899Z" fill="#E8B18D"/>
				<path d="M58.5687 97.3111L55.0537 115.274C55.0537 115.274 67.891 124.443 67.6324 123.679C67.3738 122.915 70.348 97.2993 70.348 97.2993H58.5569L58.5687 97.3111Z" fill="#E8B18D"/>
				<path d="M70.395 80.1236C70.395 80.1236 60.4966 81.5343 58.4747 89.1285C55.8296 99.0269 54.8186 100.449 54.8186 100.449L68.2672 102.401L70.395 80.1118V80.1236Z" fill="#936FF5"/>
				<path d="M66.1041 91.5385L64.5171 126.218H100.713L102.453 84.2029C102.488 80.8173 98.4443 78.1017 95.0586 78.231L86.5944 78.5837L70.395 80.1119L66.1041 91.5385Z" fill="#936FF5"/>
				<path d="M65.6221 102.024L66.4332 91.3027" stroke="#936FF5" stroke-width="0.928707" stroke-miterlimit="10"/>
				<path d="M87.8286 78.5954C87.8286 78.5954 101.218 76.4441 104.675 80.5469C109.024 85.7194 112.151 94.548 112.151 94.548L97.6212 99.1681L87.8286 78.6072V78.5954Z" fill="#936FF5"/>
				<path d="M97.6212 99.156L95.0702 100.061L91.1085 90.0923" stroke="#7B57DF" stroke-width="0.928707" stroke-miterlimit="10"/>
				<path d="M97.6212 99.1562L106.238 118.483L117.771 108.443L112.151 94.5361L97.6212 99.1562Z" fill="#F0C4A7"/>
				<path d="M104.945 115.25L77.7302 121.057L78.8235 126.218L110.752 127.041C110.752 127.041 126.199 123.608 117.759 108.443L104.933 115.25H104.945Z" fill="#F0C4A7"/>
				<path d="M77.7304 121.057C77.7304 121.057 64.4228 115.849 60.7903 123.82L60.955 126.406H81.5276L77.7304 121.069V121.057Z" fill="#F0C4A7"/>
				<path d="M104.945 115.25L111.387 114.356" stroke="#C98F7E" stroke-width="0.611301" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M70.1481 62.3374C70.4772 64.4534 69.0783 66.4402 67.021 66.7693C64.9638 67.0985 63.0358 65.6408 62.7184 63.513C62.3892 61.3969 63.7882 59.4102 65.8454 59.0811C67.9027 58.7519 69.8306 60.2096 70.1598 62.3374H70.1481Z" fill="#E8B18D"/>
				<path d="M72.9458 71.2952L73.7452 80.547C74.0156 83.7916 76.1081 85.3669 79.1999 85.0613C81.845 84.8026 84.6311 83.2744 84.2667 80.4177L82.3269 65.9229L72.9341 71.2952H72.9458Z" fill="#F0C4A7"/>
				<path d="M73.4515 77.4437C73.4515 77.4437 81.0457 77.432 82.6445 68.7444C83.15 66.0054 72.8402 70.3432 72.8402 70.3432L73.4515 77.4437Z" fill="#1F2326"/>
				<path d="M75.9555 73.7403C70.6772 74.2106 65.9396 70.2841 65.4106 65.8405L64.329 56.5416C63.659 50.9812 67.3738 45.8909 72.7344 45.0327C78.2244 44.1628 84.3491 47.9011 85.2191 53.5792L85.9714 60.8325C86.6533 67.357 82.2331 72.7411 75.9555 73.7286V73.7403Z" fill="#F0C4A7"/>
				<path d="M70.6299 61.3853L70.5946 64.4535L71.8995 64.3947" stroke="#322B29" stroke-width="0.623056" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M75.7671 56.4832C75.7671 56.4832 73.5923 56.4832 73.6393 57.4002C73.6393 57.4002 73.6981 58.0115 75.5908 57.9762C77.4834 57.9409 77.9654 57.7528 78.0947 57.1533C78.2123 56.5655 76.7781 56.4009 75.7671 56.4832Z" fill="#1F2326"/>
				<path d="M67.0445 57.2711C67.0445 57.2711 68.8196 56.9301 69.0312 57.7648C69.0312 57.7648 69.1605 58.3408 67.6087 58.5995C66.057 58.8581 65.528 58.7053 65.3399 58.2468C65.1283 57.7295 66.1863 57.3534 67.0327 57.2593L67.0445 57.2711Z" fill="#1F2326"/>
				<path d="M68.6432 60.9507C68.702 61.468 68.3493 61.9382 67.8438 61.997C67.3383 62.0557 66.8916 61.6913 66.8328 61.1741C66.774 60.6568 67.1267 60.1866 67.6322 60.1278C68.1377 60.069 68.5844 60.4334 68.6432 60.9507Z" fill="#1F2326"/>
				<path d="M76.5784 59.9981C76.6372 60.5153 76.2845 60.9856 75.779 61.0443C75.2735 61.1031 74.8268 60.7387 74.768 60.2214C74.7092 59.7042 75.0619 59.2339 75.5674 59.1752C76.0729 59.1164 76.5196 59.4808 76.5784 59.9981Z" fill="#1F2326"/>
				<path d="M70.3478 66.5225C70.3478 66.5225 72.217 67.5452 74.862 66.3579" stroke="#322B29" stroke-width="0.623056" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M65.4692 40.989C67.4324 39.978 72.5461 38.6731 73.9921 43.1638C79.1059 42.4232 87.2996 41.9648 88.4282 46.3732C90.074 52.8389 85.8654 59.6572 85.8654 59.6572L84.6076 60.4096C84.6076 60.4096 81.1631 58.9048 81.4923 51.005C81.4923 51.005 69.7953 54.6728 64.0114 52.0395C58.2276 49.3944 59.556 44.022 65.4692 40.989Z" fill="#2E353A"/>
				<path d="M90.8148 60.245C91.1322 62.3493 89.7568 64.3125 87.723 64.6299C85.6893 64.9474 83.7848 63.5131 83.4557 61.4089C83.1383 59.3046 84.5137 57.3414 86.5474 57.024C88.5812 56.7066 90.4856 58.1408 90.8148 60.245Z" fill="#F0C4A7"/>
				<path d="M86.0184 61.9376C86.0184 61.9376 85.5952 59.175 88.2167 59.2103" stroke="#322B29" stroke-width="0.623056" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M88.6869 78.4663L70.3949 80.1239C70.3949 80.1239 71.0415 86.3074 78.6122 85.9783C87.5348 85.5903 88.6869 78.4663 88.6869 78.4663Z" fill="#F0C4A7"/>
				<path d="M76.0019 126.525H49.5397V123.539H76.0019C76.8248 123.539 77.4949 124.209 77.4949 125.032C77.4949 125.854 76.8248 126.525 76.0019 126.525Z" fill="#936FF5"/>
				<path d="M53.0194 126.524H15.0365C13.0145 126.524 11.2394 125.172 10.6986 123.233L3.85675 98.6748C3.2572 96.4999 4.8795 94.3486 7.13661 94.3486H42.2629C43.6148 94.3486 44.7904 95.2538 45.1313 96.5587L53.0077 126.524H53.0194Z" fill="#936FF5"/>
				<path d="M26.8862 115.133C28.7171 115.133 30.2013 113.648 30.2013 111.818C30.2013 109.987 28.7171 108.502 26.8862 108.502C25.0553 108.502 23.571 109.987 23.571 111.818C23.571 113.648 25.0553 115.133 26.8862 115.133Z" fill="white"/>
				<path d="M1 126.407H127.375" stroke="#936FF5" stroke-width="1.17558" stroke-linecap="round"/>
			</svg>
		</div>
		<?php
	}

	/**
	 * Enqueue styles for admin notice.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function enqueue_admin_notice_styles() {
		// Show only on Dashboard page.
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'dashboard' !== $screen->base ) {
			return;
		}

		if ( $this->is_notice_dismissed() ) {
			return;
		}

		$css = '
			.getting-started-banner {
				display: flex;
				justify-content: space-between;
				padding: 2em;
				align-items: center;
				border-left-color: #5C2DDD !important;
			}

			.getting-started-banner .notice-dismiss::before {
				content: "\00D7";
				font-weight: bold;
			}

			.gs-banner-content {
				margin-left: 2.75em;
				width: 70%;
				display: flex;
				flex-direction: column;
			}

			.gs-banner-content h2 {
				position: relative;
				font-size: 1.25rem;
				line-height: 2rem;
				margin: 0 0 2px;
			}

			.gs-banner-content .gs-rocket-icon {
				position: absolute;
				left: 22px;
				top: 34px
			}

			.gs-banner-inner-content {
				flex-grow: 1;
				display: flex;
				flex-direction: column;
				justify-content: space-between;
				gap: 1em;
			}

			.gs-banner-inner-content p {
				margin: 0;
				font-size: 1rem;
				line-height: 24px;
			}

			.gs-finish-setup-button {
				width: max-content;
				background-color: #5C2DDD !important;
				border-color: #5C2DDD !important;
			}

			.getting-started-banner .gs-rocket-icon svg {
				width: unset;
			}

			.getting-started-banner svg {
				width: 20%;
			}

			/* Optional: Limit banner width or add responsiveness */
			@media screen and (max-width: 768px) {
				.gs-banner-content {
					width: 100%;
				}	
			
				.getting-started-banner svg {
					display: none;
				}
			}
		';

		wp_add_inline_style( 'common', $css );
	}

	/**
	 * Enqueue script for admin notice.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function enqueue_admin_notice_scripts() {
		// Show only on Dashboard page.
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'dashboard' !== $screen->base ) {
			return;
		}

		// Exit if notice already dismissed.
		if ( $this->is_notice_dismissed() ) {
			return;
		}

		$nonce    = wp_create_nonce( 'gs_dismiss_woopayments_notice' );
		$ajax_url = admin_url( 'admin-ajax.php' );

		$script = sprintf(
			"
			jQuery( function( $ ) {
				$( document ).on( 'click', '.is-dismissible .notice-dismiss, .getting-started-banner .notice-dismiss', function( event ) {
					event.preventDefault();
	
					const formData = new FormData();
					formData.append( 'action', 'dismiss_gs_notice' );
					formData.append( '_security', '%s' );
					formData.append( 'notice_id', 'getting_started_notice' );
	
					fetch( '%s', {
						method: 'POST',
						body: formData,
						credentials: 'same-origin'
					} )
					.then( response => {
						if ( ! response.ok ) {
							throw new Error( 'Network response was not ok: ' + response.status );
						}
						return response.json();
					} )
					.catch( error => {
						console.error( 'Error dismissing notice:', error );
					} );
				} );
			} );
			",
			esc_js( $nonce ),
			esc_url( $ajax_url )
		);

		wp_add_inline_script( 'common', $script );
	}

}

/**
 * Kicking this off by calling 'get_instance()' method
 */
GS_Admin::get_instance();
