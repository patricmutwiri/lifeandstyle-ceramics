<?php
/**
 * Astra Onboarding
 *
 * @since  4.4.37
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astra_Sites_Astra_Onboarding' ) ) {

	/**
	 * Astra Sites Astra Onboarding
	 */
	class Astra_Sites_Astra_Onboarding {

		/**
		 * Instance of Astra_Sites_Astra_Onboarding
		 *
		 * @since  4.4.37
		 * @var self Astra_Sites_Astra_Onboarding
		 */
		private static $instance = null;

		/**
		 * Get Instance
		 *
		 * @since  4.4.37
		 *
		 * @return self Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since  4.4.37
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'init' ), 15 );
		}

		/**
		 * Initialize
		 *
		 * @since  4.4.37
		 */
		public function init() {
			// Only load onboarding if Astra theme is active.
			if ( ! self::is_astra_theme_active() ) {
				return;
			}

			// Bail early if Astra white labeling is enabled.
			if ( function_exists( 'astra_is_white_labelled' ) && astra_is_white_labelled() ) {
				return;
			}

			// Check if One Onboarding class exists.
			if ( ! class_exists( '\One_Onboarding\Core\Register' ) ) {
				return;
			}

			$this->register_astra_onboarding();
		}

		/**
		 * Check if Astra theme is active
		 *
		 * @since  4.4.37
		 * @return bool
		 */
		private static function is_astra_theme_active() {
			$current_theme = get_template();
			return 'astra' === $current_theme;
		}

		/**
		 * Get feature list
		 *
		 * @since  4.4.37
		 * @return array
		 */
		public static function get_feature_list() {
			return array(
				array(
					'title'       => __( 'Global Color & Typography Controls', 'astra-sites' ),
					'description' => __( 'Set global fonts and colors to save time and keep your website looking polished.', 'astra-sites' ),
				),
				array(
					'title'       => __( 'Flexible Layout Options', 'astra-sites' ),
					'description' => __( 'Customize headers, footers, sidebars, blogs, and archive layouts effortlessly.', 'astra-sites' ),
				),
				array(
					'title'       => __( 'Transparent Header', 'astra-sites' ),
					'description' => __( 'Create a transparent header for a modern look that keeps the focus on your content.', 'astra-sites' ),
				),
				array(
					'id'          => 'advanced-hooks',
					'title'       => __( 'Site Builder', 'astra-sites' ),
					'description' => __( 'Build fully custom websites with more control over design and layout.', 'astra-sites' ),
					'isPro'       => true,
				),
				array(
					'id'          => 'header-footer-builder',
					'title'       => __( 'Header & Footer Builder', 'astra-sites' ),
					'description' => __( 'Build headers and footers that give your website a polished, professional look.', 'astra-sites' ),
					'isPro'       => true,
				),
				array(
					'id'          => 'woocommerce',
					'title'       => __( 'Modern WooCommerce Stores', 'astra-sites' ),
					'description' => __( 'Easily create beautiful WooCommerce stores that boost sales and grow your business.', 'astra-sites' ),
					'isPro'       => true,
				),
			);
		}

		/**
		 * Get addon list
		 *
		 * @since  4.4.37
		 * @return array
		 */
		public static function get_addon_list() {
			$icons = array(
				'spectra' => '<svg xmlns="http://www.w3.org/2000/svg" width="51" height="51" viewBox="0 0 51 51" fill="none">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M50.0197 25.0099C50.0197 38.8224 38.8224 50.0197 25.0099 50.0197C11.1973 50.0197 0 38.8224 0 25.0099C0 11.1973 11.1973 0 25.0099 0C38.8224 0 50.0197 11.1973 50.0197 25.0099ZM18.9358 26.4388C14.434 25.2955 13.5051 19.0787 17.4352 16.7921L31.3693 8.57458L30.5833 16.0061L23.4376 20.222C22.8659 20.5793 22.9374 21.4368 23.5805 21.5797L31.012 23.5805C35.5138 24.7238 36.4427 30.9406 32.5126 33.2272L18.5785 41.4447L19.3645 34.0132L26.5102 29.7973C27.0819 29.44 27.0104 28.5825 26.3673 28.4396L18.9358 26.4388Z" fill="#5733FF"/>
					</svg>',
				'surerank' => '<svg xmlns="http://www.w3.org/2000/svg" width="102" height="115" viewBox="0 0 102 115" fill="none">
						<path d="M101.177 42.6533C101.177 19.2953 82.3389 0.365723 59.094 0.365723H0.189453V114.516H10.088C27.2714 114.516 41.4519 101.621 43.5789 84.9269H43.6067L43.6762 67.2128H42.1887C30.2882 67.2128 20.6121 57.6852 20.2785 45.8106H20.2646V40.1666H25.895C33.7638 40.1666 40.7289 44.0783 44.9831 50.0575C49.0426 35.5984 62.2777 24.9951 77.9596 24.9951V30.639V38.2248C77.9596 54.1647 66.0174 67.101 50.1826 67.1988V84.9269C52.2401 101.663 66.4484 114.627 83.6735 114.627H101.204V80.694H77.4869C91.5284 73.8347 101.204 59.3756 101.204 42.6393L101.177 42.6533Z" fill="#4338CA"/>
					</svg>',
				'sureforms' => '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150" fill="none">
						<g clip-path="url(#clip0_7471_122)">
							<mask id="mask0_7471_122" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="150" height="150">
								<path d="M150 0H0V150H150V0Z" fill="white"/>
							</mask>
							<g mask="url(#mask0_7471_122)">
								<path d="M150 0H0V150H150V0Z" fill="#D54407"/>
								<path d="M42.8579 32.1396H107.144V53.5683H53.5723L42.8579 64.2825V53.5683V32.1396Z" fill="white"/>
								<path d="M42.8579 32.1396H107.144V53.5683H53.5723L42.8579 64.2825V53.5683V32.1396Z" fill="white"/>
								<path d="M42.8579 64.2839H96.4291V85.7124H53.5723L42.8579 96.4266V85.7124V64.2839Z" fill="white"/>
								<path d="M42.8579 64.2839H96.4291V85.7124H53.5723L42.8579 96.4266V85.7124V64.2839Z" fill="white"/>
								<path d="M42.8579 96.428H75.0007V117.856H42.8579V96.428Z" fill="white"/>
								<path d="M42.8579 96.428H75.0007V117.856H42.8579V96.428Z" fill="white"/>
							</g>
						</g>
						<defs>
							<clipPath id="clip0_7471_122">
							<rect width="150" height="150" fill="white"/>
							</clipPath>
						</defs>
					</svg>',
				'suremails' => '<svg xmlns="http://www.w3.org/2000/svg" width="33" height="33" viewBox="0 0 33 33" fill="none">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M1.45898 0.183594H31.459C32.0113 0.183594 32.459 0.631309 32.459 1.18359V31.1836C32.459 31.7359 32.0113 32.1836 31.459 32.1836H1.45898C0.9067 32.1836 0.458984 31.7359 0.458984 31.1836V1.18359C0.458984 0.631309 0.9067 0.183594 1.45898 0.183594ZM8.99654 15.7372C9.27484 15.9385 9.66999 15.8724 9.85204 15.6016C10.0533 15.3233 9.98719 14.9281 9.71644 14.7461L7.03639 12.8261C6.94114 12.7526 6.94944 12.6607 6.95359 12.6147C6.95779 12.5688 6.99284 12.4886 7.10814 12.4434L24.9405 7.89698C25.0441 7.87858 25.1051 7.92118 25.1469 7.97128C25.1887 8.0214 25.2305 8.0716 25.1837 8.17848L18.4273 25.2741C18.3874 25.3652 18.3203 25.3866 18.2776 25.4002C18.2701 25.4026 18.2634 25.4047 18.2577 25.407C18.2117 25.4028 18.1198 25.3945 18.063 25.306L16.0207 21.5627C15.9885 21.5088 15.9593 21.4482 15.93 21.3877C15.9008 21.3271 15.8715 21.2666 15.8393 21.2127C15.04 19.3982 14.8382 18.0594 16.3384 16.7405L20.2007 13.1663C20.4629 12.9306 20.4887 12.5437 20.2605 12.3007C20.0249 12.0384 19.638 12.0126 19.3949 12.2408L15.3384 15.7372C13.2944 17.535 13.6348 19.7713 14.9281 22.1679L16.9703 25.9112C17.2312 26.4074 17.7618 26.6871 18.3291 26.6644C18.4786 26.6502 18.6399 26.6092 18.7744 26.5565C19.1394 26.4135 19.4251 26.1243 19.5887 25.75L26.3452 8.65438C26.5481 8.15383 26.4527 7.57068 26.0916 7.15808C25.7305 6.74543 25.1799 6.58433 24.6502 6.70313L6.79864 11.2571C6.25729 11.4026 5.84799 11.8289 5.73289 12.3837C5.61774 12.9385 5.85519 13.4882 6.31649 13.8172L8.99654 15.7372ZM8.15163 20.8842C8.20183 20.8645 8.26218 20.8216 8.30583 20.7852L11.0051 18.4161C11.2334 18.2109 11.2559 17.8741 11.0572 17.6625C10.852 17.4342 10.5153 17.4117 10.3037 17.6104L7.60438 19.9795C7.37608 20.1847 7.35363 20.5215 7.55223 20.7331C7.70433 20.9243 7.93418 20.9693 8.15163 20.8842ZM7.80747 24.6731C7.85772 24.6536 7.91822 24.6109 7.96192 24.5746L12.5494 20.5572C12.7783 20.3527 12.8017 20.016 12.6036 19.8038C12.3991 19.5749 12.0624 19.5515 11.8502 19.7495L7.26277 23.7669C7.03387 23.9715 7.01047 24.3082 7.20852 24.5203C7.35357 24.6953 7.60652 24.7512 7.80747 24.6731Z" fill="#0E7EE8"/>
					</svg>',
			);

			return array(
				array(
					'slug'        => 'ultimate-addons-for-gutenberg',
					'title'       => 'Spectra',
					'logoSvg'     => $icons['spectra'],
					'description' => __( 'Powerful visual website builder made for WordPress. Just click, drag, and create anything, no code, no limits.', 'astra-sites' ),
				),
				array(
					'slug'        => 'surerank',
					'title'       => 'SureRank',
					'logoSvg'     => $icons['surerank'],
					'description' => __( 'Just a simple, lightweight SEO assistant that helps your site rise in the rankings.', 'astra-sites' ),
				),
				array(
					'slug'        => 'sureforms',
					'title'       => 'SureForms',
					'logoSvg'     => $icons['sureforms'],
					'description' => __( 'Create forms that feel like a chat. One question at a time keeps users engaged.', 'astra-sites' ),
				),
				array(
					'slug'        => 'suremails',
					'title'       => 'SureMails',
					'logoSvg'     => $icons['suremails'],
					'description' => __( 'Send emails that get delivered. SureMails is a simple, lightweight email delivery service.', 'astra-sites' ),
				),
			);
		}

		/**
		 * Register Astra onboarding page
		 *
		 * @since  4.4.37
		 */
		private function register_astra_onboarding() {
			\One_Onboarding\Core\Register::register_product(
				'astra',
				array(
					'title'       => __( 'Astra Onboarding', 'astra-sites' ),
					'product'     => array(
						'id'   => 'astra',
						'name' => __( 'Astra', 'astra-sites' ),
					),
					'logo'        => ASTRA_SITES_URI . 'inc/assets/images/astra.svg',
					'screens'     => array(
						'welcome'           => array(
							// Use default heading.
							'description' => __( 'Build a fast, beautiful WordPress site, effortlessly.', 'astra-sites' ),
							'banner'      => array(
								'type'      => 'video',
								'url'       => 'https://www.youtube-nocookie.com/embed/VCkFWDpjCrg?showinfo=0&autoplay=1&modestbranding=1&rel=0',
								'title'     => __( 'Getting Started with', 'astra-sites' ),
								'thumbnail' => ASTRA_SITES_URI . 'inc/assets/images/astra-welcome-thumbnail.png',
							),
							'items'       => array(
								__( 'Trusted by 1.8M+ websites worldwide', 'astra-sites' ),
								__( 'Blazing fast and lightweight for better performance', 'astra-sites' ),
								__( 'Easy customization, no coding or design skills', 'astra-sites' ),
								__( 'WooCommerce-ready for quick online store setup', 'astra-sites' ),
							),
						),
						'user-info'         => array(
							// Use default heading.
							'description'    => __( 'Get helpful updates, new features, and tips to make your website better, while helping us improve Astra.', 'astra-sites' ),
							'sourceOptions'  => array(
								'wordpress' => __( 'WordPress Themes Directory', 'astra-sites' ),
								'google'    => __( 'Google Search', 'astra-sites' ),
								'social'    => __( 'Social Media', 'astra-sites' ),
								'youtube'   => __( 'YouTube', 'astra-sites' ),
								'friend'    => __( 'A friend or colleague', 'astra-sites' ),
								'other'     => __( 'Other', 'astra-sites' ),
							),
							'benefitOptions' => array(
								'ai-and-templates'    => __( 'AI and Templates will help me design quickly', 'astra-sites' ),
								'fast-loading'        => __( 'I need a fast-loading theme', 'astra-sites' ),
								'design-flexibility'  => __( 'I need advanced design flexibility without a bloated builder for free', 'astra-sites' ),
								'updates-and-support' => __( 'I trust Brainstorm Force for regular updates and support', 'astra-sites' ),
								'other'               => __( 'Other (please specify)', 'astra-sites' ),
							),
							'privacyPolicy'  => array(
								'url'   => 'https://wpastra.com/privacy-policy/?utm_source=starter-templates&utm_medium=astra-onboarding&utm_campaign=link',
								'label' => __( 'Privacy Policy', 'astra-sites' ),
							),
						),
						'features'          => array(
							// Use default heading.
							'description' => __( 'Unlock more design control, faster setup, and powerful customization, so you can build a better website, effortlessly.', 'astra-sites' ),
							'featureList' => self::get_feature_list(),
							'upgradeUrl'  => 'https://wpastra.com/pricing/?utm_source=starter-templates&utm_medium=astra-onboarding&utm_campaign=pro-features',
						),
						'starter-templates' => array(), // Use default.
						'add-ons'           => array(
							// Use default heading and description.
							'addonList' => self::get_addon_list(),
						),
						'done'              => array(
							// Use default heading, description and itemsHeading.
							'items' => array(
								__( 'Customize Your Website', 'astra-sites' ),
								__( 'Visit Dashboard', 'astra-sites' ),
								__( 'View Documentation', 'astra-sites' ),
							),
							'cta1'  => array(
								'url'   => admin_url( 'customize.php' ),
								'label' => __( 'Customize Your Site', 'astra-sites' ),
							),
							'cta2'  => array(
								'url'   => admin_url( 'admin.php?page=astra' ),
								'label' => __( 'Visit Dashboard', 'astra-sites' ),
							),
							'cta3'  => array(
								'url'   => 'https://wpastra.com/docs/?utm_source=starter-templates&utm_medium=astra-onboarding',
								'label' => __( 'Docs & Help Center', 'astra-sites' ),
							),
						),
					),
					'exit'        => array(
						'url' => admin_url( 'admin.php?page=astra' ),
						// Use default label.
					),
					'colors'      => array(), // Use default colors.
					'option_name' => 'astra_onboarding',
					'pro_status'  => Astra_Sites::get_instance()->get_plugin_status( 'astra-addon/astra-addon.php' ),
					'pro_slug'    => 'astra-addon',
				)
			);

			// Add completion hooks.
			add_action( 'one_onboarding_completion_astra', array( $this, 'handle_onboarding_completion' ), 10, 2 );

			// Update product referer.
			add_action( 'one_onboarding_plugin_activated', array( $this, 'product_activation_utm_event' ) );
		}

		/**
		 * Handle onboarding completion
		 *
		 * @since  4.4.37
		 * @param array            $completion_data Complete data including onboarding state, user info, and product details.
		 * @param \WP_REST_Request $request The REST request object.
		 */
		public function handle_onboarding_completion( $completion_data, $request ) {
			$screens = isset( $completion_data['screens'] ) ? $completion_data['screens'] : array();

			$is_user_info_screen_skipped = false;
			$is_features_screen_skipped  = false;
			foreach ( $screens as $screen ) {
				if ( 'user-info' === $screen['id'] ) {
					$is_user_info_screen_skipped = isset( $screen['skipped'] ) ? $screen['skipped'] : true; // If not set, consider it skipped.
				}
				if ( 'features' === $screen['id'] ) {
					$is_features_screen_skipped = isset( $screen['skipped'] ) ? $screen['skipped'] : true; // If not set, consider it skipped.
				}
			}

			// Send user info to subscription webhook if the screen was not skipped.
			if ( ! $is_user_info_screen_skipped ) {
				self::generate_lead( $completion_data );
			}

			// Handle pro features selection if the screen was not skipped.
			if ( ! $is_features_screen_skipped ) {
				self::handle_pro_features_selection( $completion_data );
			}
		}

		/**
		 * Send data to subscription webhook
		 *
		 * @since  4.4.37
		 * @param array $data Data to send.
		 */
		private static function generate_lead( $data ) {
			if ( empty( $data['user_info'] ) || ! is_array( $data['user_info'] ) ) {
				return; // Stop if user info is not available.
			}

			$user_info = $data['user_info'];

			$lead_endpoint = Astra_Sites::get_instance()->get_metrics_api_domain() . 'wp-json/bsf-metrics-server/astra/v1/subscribe';
			$lead_data     = array(
				'email'        => isset( $user_info['email'] ) ? $user_info['email'] : '',
				'first_name'   => isset( $user_info['firstName'] ) ? $user_info['firstName'] : '',
				'last_name'    => isset( $user_info['lastName'] ) ? $user_info['lastName'] : '',
				'source'       => isset( $user_info['source'] ) ? $user_info['source'] : '',
				'new_user'     => isset( $user_info['newUser'] ) ? $user_info['newUser'] : '',
				'benefit_id'   => isset( $user_info['benefitId'] ) ? $user_info['benefitId'] : '',
				'benefit_text' => isset( $user_info['benefitText'] ) ? $user_info['benefitText'] : '',
			);

			$response = wp_safe_remote_post(
				$lead_endpoint,
				array(
					'method'  => 'POST',
					'body'    => wp_json_encode( $lead_data ),
					'headers' => array(
						'Content-Type' => 'application/json',
					),
				) 
			);

			// Log response for debugging.
			if ( is_wp_error( $response ) ) {
				astra_sites_error_log( 'Error sending data to subscription webhook: ' . $response->get_error_message() );
			}
		}

		/**
		 * Handle pro features selection
		 *
		 * @since  4.4.37
		 * @param array $completion_data Complete data including onboarding state, user info, and product details.
		 */
		private static function handle_pro_features_selection( $completion_data ) {
			if ( ! defined( 'ASTRA_EXT_VER' ) ) {
				return; // Stop if Astra Pro is not active.
			}

			if ( empty( $completion_data['pro_features'] ) && ! is_array( $completion_data['pro_features'] ) ) {
				return; // Stop if pro features are not available.
			}

			// Enable site builder module if selected.
			if ( in_array( 'advanced-hooks', $completion_data['pro_features'], true ) ) {
				if ( is_callable( array( 'Astra_Ext_Extension', 'activate_extension' ) ) ) {
					Astra_Ext_Extension::activate_extension( 'advanced-hooks', true );
				}
			}

			// header-footer-builder is auto enabled if addon is active.

			// Enable WooCommerce module if selected.
			if ( in_array( 'woocommerce', $completion_data['pro_features'], true ) ) {
				if ( is_callable( array( 'Astra_Ext_Extension', 'activate_extension' ) ) ) {
					Astra_Ext_Extension::activate_extension( 'woocommerce', true );
				}
			}
		}

		/**
		 * Product activation utm event.
		 *
		 * @param string $slug Product slug.
		 * @return void
		 */
		public function product_activation_utm_event( $slug ) {
			if ( empty( $slug ) ) {
				return;
			}

			if ( class_exists( 'BSF_UTM_Analytics' ) && is_callable( array( 'BSF_UTM_Analytics', 'update_referer' ) ) ) {
				// If the plugin is found and the update_referer function is callable, update the referer with the corresponding product slug.
				BSF_UTM_Analytics::update_referer( 'astra', $slug );
			}
		}
	}

	/**
	 * Initialize class
	 */
	Astra_Sites_Astra_Onboarding::get_instance();
}
