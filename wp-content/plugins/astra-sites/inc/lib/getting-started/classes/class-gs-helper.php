<?php
/**
 * Getting Started Helper
 *
 * @since 1.0.0
 * @package Getting Started Helper
 */

namespace GS\Classes;

/**
 * GS Helper
 */
class GS_Helper {

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
	 * Return default action items.
	 *
	 * @since 1.0.0
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_default_action_items() {

		$admin_url = admin_url();

		// Dynamic completion checks.
		$site_title_set = 'WordPress' !== get_bloginfo( 'name' ) && ! empty( get_bloginfo( 'name' ) );
		$tagline_set    = ! empty( get_bloginfo( 'description' ) );
		$permalink_set  = '/%postname%/' === get_option( 'permalink_structure' );

		$action_items = [
			[
				'id'          => 'wordpress',
				'title'       => __( 'WordPress Basics', 'astra-sites' ),
				'description' => __( 'Get your site’s foundation rock-solid so it looks professional and runs smoothly from day one.', 'astra-sites' ),
				'category'    => 'basics',
				'steps'       => [
					[
						'id'        => 'setup-title-and-tagline',
						'completed' => $site_title_set && $tagline_set,
						'title'     => __( 'Set Site Title & Tagline', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'options-general.php#:~:text=Site%20Title,Tagline',
						],
						'content'   => [
							[
								'type' => 'paragraph',
								'text' => __( 'Your site title and tagline show up in browser tabs and search results. Make a strong first impression by customizing them to match your brand. A 10-second tweak that boosts professionalism and SEO.', 'astra-sites' ),
							],
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/title-and-tagline.png',
								],
							],
						],
					],
					[
						'id'        => 'review-admin-email',
						'completed' => false,
						'title'     => __( 'Review Admin Email', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Review', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'options-general.php#:~:text=Administration%20Email%20Address',
						],
						'content'   => [
							[
								'type' => 'paragraph',
								'text' => __( 'Your admin email is where you’ll receive important site alerts like user messages, order updates, and security notifications. Make sure it’s correct so you don’t miss anything critical.', 'astra-sites' ),
							],
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/admin-email.png',
								],
							],
						],
					],
					[
						'id'        => 'setup-permalinks',
						'completed' => $permalink_set,
						'title'     => __( 'Choose how your page links look', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'options-permalink.php',
						],
						'content'   => [
							[
								'type' => 'paragraph',
								'text' => __( 'Set up clean, readable URLs for your website that look better, help with SEO, and are easier to share. Setting your permalinks takes just a few click and makes your site feel pro from day one.', 'astra-sites' ),
							],
							[
								'type' => 'list',
								'data' => [
									'items' => [
										__( 'Bad example: yourwebsite.com/page_id=123', 'astra-sites' ),
										__( 'Good example: yourwebsite.com/about-us', 'astra-sites' ),
									],
								],
							],
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/permalinks.png',
								],
							],
						],
					],
					[
						'id'        => 'review-seo-visibility',
						'completed' => false,
						'title'     => __( 'Search Engine Visibility', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Review', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'options-reading.php#:~:text=about%20feeds.-,Search%20engine%20visibility,-Search%20engine%20visibility',
						],
						'content'   => [
							[
								'type' => 'paragraph',
								'text' => __( 'If this setting is left unchecked, search engines can’t find your site even if it’s ready. Make sure your site is visible to Google and others so you can start showing up in search results.', 'astra-sites' ),
							],
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/seo-visibility.png',
								],
							],
						],
					],
				],
			],
		];

		if ( wp_get_theme()->get_template() === 'astra' ) {
			// Astra theme dynamic checks.
			$custom_logo              = get_theme_mod( 'custom_logo' );
			$site_icon                = get_option( 'site_icon' );
			$logo_site_icon_completed = ! empty( $custom_logo ) && ! empty( $site_icon );

			$action_items[] = [
				'id'          => 'astra-theme',
				'title'       => __( 'Design, Style & Theme', 'astra-sites' ),
				'description' => __( 'Create a memorable brand experience that looks professional and instantly recognizable.', 'astra-sites' ),
				'category'    => 'basics',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/VCkFWDpjCrg?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'customize-style-guide',
						'completed' => false,
						'title'     => __( 'Customize Style Guide', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'customize.php?autofocus=astra-tour',
						],
						'content'   => [
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/astra-style-guide.png',
								],
							],
						],
					],
					[
						'id'        => 'add-logo-and-site-icon',
						'completed' => $logo_site_icon_completed,
						'title'     => __( 'Add Logo and Site Icon', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'customize.php?autofocus[section]=astra-site-identity',
						],
						'content'   => [
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/astra-add-logo.png',
								],
							],
						],
					],
					[
						'id'        => 'customize-header',
						'completed' => false,
						'title'     => __( 'Customize Header', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'customize.php?autofocus[section]=section-header-builder-layout',
						],
						'content'   => [
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/customize-header.png',
								],
							],
						],
					],
					[
						'id'        => 'customize-footer',
						'completed' => false,
						'title'     => __( 'Customize Footer', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'customize.php?autofocus[section]=section-footer-builder-layout',
						],
						'content'   => [
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/customize-footer.png',
								],
							],
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php' ) ) {
			$action_items[] = [
				'id'          => 'spectra',
				'title'       => __( 'Website Pages', 'astra-sites' ),
				'description' => __( 'Review and polish every page so your message is crystal clear and your audience knows exactly what to do.', 'astra-sites' ),
				'category'    => 'basics',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/jSZ1M2finRE?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'review-pages',
						'completed' => false,
						'title'     => __( 'Review Pages', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Review', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'edit.php?post_type=page',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'suremails/suremails.php' ) ) {
			$action_items[] = [
				'id'          => 'suremails',
				'title'       => __( 'Setup Emails', 'astra-sites' ),
				'description' => __( 'Ensure your site’s emails land in inboxes so you never miss an opportunity to connect or sell.', 'astra-sites' ),
				'category'    => 'basics',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/I6xLBC54iHs?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'connect-smtp',
						'completed' => self::is_suremails_connected(),
						'title'     => __( 'Connect SMTP provider', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'options-general.php?page=suremail#/connections',
						],
						'content'   => [
							'type'  => 'link',
							'title' => __( 'Learn More', 'astra-sites' ),
							'url'   => 'https://suremails.com/docs/connections-tab/',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'sureforms/sureforms.php' ) ) {
			$action_items[] = [
				'id'          => 'sureforms',
				'title'       => __( 'Contact Form', 'astra-sites' ),
				'description' => __( 'Make it effortless for visitors to reach out to you, turning curious browsers into real leads.', 'astra-sites' ),
				'category'    => 'basics',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/7w91hnumviU?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'review-contact-form',
						'completed' => false,
						'title'     => __( 'Review contact form', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Review', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'edit.php?post_type=sureforms_form',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'surerank/surerank.php' ) ) {
			$action_items[] = [
				'id'          => 'surerank',
				'title'       => __( 'Optimize Your Site', 'astra-sites' ),
				'description' => __( 'Make your pages accessible and optimized for users, AI bots and search engines', 'astra-sites' ),
				'category'    => 'basics',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/O8W3PmgYOiE?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'review-site-optimization',
						'completed' => false,
						'title'     => __( 'Review Site Optimization', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Review', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'admin.php?page=surerank#/dashboard',
						],
					],
					[
						'id'        => 'connect-search-console',
						'completed' => false,
						'title'     => __( 'Connect Search Console', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Set Up', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'admin.php?page=surerank#/search-console',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'surecart/surecart.php' ) ) {
			// SureCart dynamic checks.
			$is_connected    = class_exists( '\SureCart\Models\ApiToken' ) && \SureCart\Models\ApiToken::get();
			$sc_shop_page_id = get_option( 'surecart_shop_page_id', 0 );

			$action_items[] = [
				'id'          => 'surecart',
				'title'       => __( 'Launch Your Store', 'astra-sites' ),
				'description' => __( 'Start selling and generating revenue by setting up an online store seamlessly in minutes.', 'astra-sites' ),
				'category'    => 'sale-online',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/cyE5ObYk7FM?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'setup-surecart-account',
						'completed' => $is_connected,
						'title'     => __( 'Set up SureCart account', 'astra-sites' ),
						'cta'       => [
							'url' => $is_connected ? esc_url( $admin_url ) . 'admin.php?page=sc-settings&tab=connection' : esc_url( $admin_url ) . 'admin.php?page=sc-getting-started',
						],
					],
					[
						'id'            => 'create-new-product',
						'completed'     => false,
						'title'         => __( 'Create new product', 'astra-sites' ),
						'cta'           => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=sc-products&action=edit',
						],
						'prerequisites' => [ 'setup-surecart-account' ],
					],
					[
						'id'            => 'design-shop-page',
						'completed'     => false,
						'title'         => __( 'Design Shop Page', 'astra-sites' ),
						'cta'           => [
							'url' => esc_url( $admin_url ) . 'post.php?post=' . $sc_shop_page_id . '&action=edit',
						],
						'prerequisites' => [ 'setup-surecart-account' ],
					],
					[
						'id'            => 'connect-payment-gateway',
						'completed'     => false,
						'title'         => __( 'Connect payment gateway', 'astra-sites' ),
						'cta'           => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=sc-settings&tab=processors',
						],
						'prerequisites' => [ 'setup-surecart-account' ],
					],
				],
			];
		}

		if ( is_plugin_active( 'cartflows/cartflows.php' ) ) {
			$action_items[] = [
				'id'          => 'cartflows',
				'title'       => __( 'Create Funnel', 'astra-sites' ),
				'description' => __( 'Guide your visitors step-by-step to buy more, boosting sales without extra ad spend.', 'astra-sites' ),
				'category'    => 'sale-online',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/h_gSHrAaLuA?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'create-product',
						'completed' => false,
						'title'     => __( 'Create product you\'re selling', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'post-new.php?post_type=product',
						],
					],
					[
						'id'        => 'setup-store-checkout',
						'completed' => false,
						'title'     => __( 'Setup Store Checkout', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=cartflows&path=store-checkout',
						],
					],
					[
						'id'        => 'build-funnel',
						'completed' => false,
						'title'     => __( 'Design beautiful Thank-You page', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=cartflows&path=flows',
						],
					],
					[
						'id'        => 'offer-related-product',
						'completed' => false,
						'title'     => __( 'Offer a related product to sell', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=cartflows&path=flows',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'modern-cart/modern-cart.php' ) ) {
			$action_items[] = [
				'id'          => 'modern-cart',
				'title'       => __( 'Customize Your Cart Style', 'astra-sites' ),
				'description' => __( 'Add a sleek, slide-out cart to match your brand style that enhances your WooCommerce store’s shopping experience and boosts conversions', 'astra-sites' ),
				'category'    => 'sale-online',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/jAsON8waa8g?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'customize-cart-style',
						'completed' => false,
						'title'     => __( 'Set and Enable Modern Cart', 'astra-sites' ),
						'cta'       => [
							'label' => __( 'Set Up', 'astra-sites' ),
							'url'   => esc_url( $admin_url ) . 'admin.php?page=moderncart_settings',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'latepoint/latepoint.php' ) ) {
			$action_items[] = [
				'id'          => 'latepoint',
				'title'       => __( 'Accept Appointments', 'astra-sites' ),
				'description' => __( 'Streamline your booking process so clients can book and pay you with zero hassle.', 'astra-sites' ),
				'category'    => 'sale-online',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/PJvWhxoetV8?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'add-service',
						'completed' => false,
						'title'     => __( 'Add Service', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=latepoint&route_name=services__index',
						],
					],
					[
						'id'        => 'enable-notifications',
						'completed' => false,
						'title'     => __( 'Enable notifications', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=latepoint&route_name=settings__notifications',
						],
					],
					[
						'id'        => 'connect-payment-gateway',
						'completed' => false,
						'title'     => __( 'Connect payment gateway', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=latepoint&route_name=settings__payments',
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'presto-player/presto-player.php' ) ) {
			$action_items[] = [
				'id'          => 'presto-player',
				'title'       => __( 'Add Engaging Videos', 'astra-sites' ),
				'description' => __( 'Showcase your brand personality with videos that inform, inspire, and engage.', 'astra-sites' ),
				'category'    => 'level-up',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/2umGLzRrGII?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'upload-video',
						'completed' => false,
						'title'     => __( 'Upload video to Media Hub', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'edit.php?post_type=pp_video_block',
						],
						'content'   => [
							[
								'type' => 'paragraph',
								'text' => __( 'The media hub is a flexible way to add audio or video to your site. It allows you to save media which you can later use in any post or page on your site.', 'astra-sites' ),
							],
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/upload-video.png',
								],
							],
						],
					],
					[
						'id'        => 'add-presto-player-block',
						'completed' => false,
						'title'     => __( 'Add Presto Player block to pages', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'post-new.php?post_type=page',
						],
						'content'   => [
							[
								'type' => 'paragraph',
								'text' => __( 'Embed videos with modern features like chapters, overlays, and custom branding and keep people watching and make your content unforgettable.', 'astra-sites' ),
							],
							[
								'type' => 'image',
								'data' => [
									'src' => 'https://websitedemos.net/wp-content/uploads/2025/07/embed-video-through-presto-player-block.jpeg',
								],
							],
						],
					],
				],
			];
		}

		if ( is_plugin_active( 'suretriggers/suretriggers.php' ) ) {
			// OttoKit dynamic checks.
			$is_ottokit_connected = class_exists( '\SureTriggers\Models\SaasApiToken' ) && \SureTriggers\Models\SaasApiToken::get();

			$action_items[] = [
				'id'          => 'ottokit',
				'title'       => __( 'Automate Recurring Website Tasks', 'astra-sites' ),
				'description' => __( 'Save hours each week by automating routine website tasks, so you can focus on growing your business.', 'astra-sites' ),
				'category'    => 'level-up',
				'cta'         => [
					'type' => 'video',
					'url'  => 'https://www.youtube-nocookie.com/embed/xR7icLAkMgE?modestbranding=1',
				],
				'steps'       => [
					[
						'id'        => 'create-ottokit-account',
						'completed' => $is_ottokit_connected,
						'title'     => __( 'Create OttoKit account', 'astra-sites' ),
						'cta'       => [
							'url' => 'https://app.ottokit.com/register?source_type=st-finish-setup&redirect_url=' . esc_url( $admin_url ) . 'admin.php?page=suretriggers',
						],
					],
					[
						'id'        => 'create-connections',
						'completed' => false,
						'title'     => __( 'Add connections', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=suretriggers',
						],
					],
					[
						'id'        => 'create-workflows',
						'completed' => false,
						'title'     => __( 'Set up “when this happens, do that” actions', 'astra-sites' ),
						'cta'       => [
							'url' => esc_url( $admin_url ) . 'admin.php?page=suretriggers',
						],
					],
				],
			];
		}

		$categorized = apply_filters( 'getting_started_categorized_action_items', true );
		if ( ! $categorized ) {
			// If not categorized, the remove category from action items.
			foreach ( $action_items as &$item ) {
				unset( $item['category'] );
			}
		}

		/**
		 * Filter to modify the default action items.
		 *
		 * @since 1.0.0
		 * @param array<int, array<string, mixed>> $action
		 * @return array<int, array<string, mixed>>
		 */
		return apply_filters( 'getting_started_action_items', $action_items );
	}

	/**
	 * Get the categories of action items.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, string>> Categories of action items.
	 */
	public static function get_action_items_categories() {
		$categories = [
			'basics'      => [
				'name' => __( 'Set Up The Basics', 'astra-sites' ),
			],
			'sale-online' => [
				'name' => __( 'Start Selling Online', 'astra-sites' ),
			],
			'level-up'    => [
				'name' => __( 'Level Up Your Site', 'astra-sites' ),
			],
		];

		/**
		 * Filter to modify the action items categories.
		 *
		 * @since 1.0.0
		 * @param array<string, array<string, string>> $categories Categories of action items.
		 * @return array<string, array<string, string>>
		 */
		return apply_filters( 'getting_started_action_items_categories', $categories );
	}

	/**
	 * Checks if SureMail has at least one connection configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_suremails_connected() {
		if ( ! is_plugin_active( 'suremails/suremails.php' ) ) {
			return false;
		}

		// Get SureMails connections from options.
		$suremails_connections_option = defined( 'SUREMAILS_CONNECTIONS' ) ? SUREMAILS_CONNECTIONS : 'suremails_connections';
		$suremails_connections        = get_option( $suremails_connections_option, array() );
		if ( is_array( $suremails_connections ) && isset( $suremails_connections['connections'] ) && ! empty( $suremails_connections['connections'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the count of incomplete actions.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public static function get_incomplete_actions_count() {
		$action_items        = self::get_default_action_items();
		$action_items_status = get_option( 'getting_started_action_items', array() );

		$incomplete_actions_count = 0;
		foreach ( $action_items as $item ) {
			if ( isset( $item['steps'] ) && is_array( $item['steps'] ) ) {
				$all_steps_completed = true;
				foreach ( $item['steps'] as $step ) {
					/**
					 * Check if the step is completed.
					 *
					 * @var array<string, array{ steps: array<string, mixed> }> $action_items_status
					 */
					$step_status_db    = isset( $action_items_status[ $item['id'] ]['steps'][ $step['id'] ] );
					$default_completed = isset( $step['completed'] ) && $step['completed'];
					$step_completed    = $step_status_db ?
						$action_items_status[ $item['id'] ]['steps'][ $step['id'] ] :
						$default_completed;

					if ( ! $step_completed ) {
						$all_steps_completed = false;
						break;
					}
				}

				// If all steps are completed, we don't count this action item.
				if ( ! $all_steps_completed ) {
					$incomplete_actions_count++;
				}
			}
		}

		return $incomplete_actions_count;
	}

	/**
	 * Get the count of incomplete steps.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public static function get_incomplete_steps_count() {
		$action_items        = self::get_default_action_items();
		$action_items_status = get_option( 'getting_started_action_items', array() );

		$incomplete_steps_count = 0;
		foreach ( $action_items as $item ) {
			if ( isset( $item['steps'] ) && is_array( $item['steps'] ) ) {
				foreach ( $item['steps'] as $step ) {
					/**
					 * Determine if the step is incomplete.
					 *
					 * @var array<string, array{ steps: array<string, mixed> }> $action_items_status
					 */
					if ( ! ( isset( $step['completed'] ) && $step['completed'] ) || ! ( isset( $action_items_status[ $item['id'] ]['steps'][ $step['id'] ] ) && $action_items_status[ $item['id'] ]['steps'][ $step['id'] ] ) ) {
						$incomplete_steps_count++;
					}
				}
			}
		}

		return $incomplete_steps_count;
	}

	/**
	 * Get the option name for the setup wizard showing status.
	 *
	 * @since 1.0.1
	 *
	 * @return string
	 */
	public static function get_setup_wizard_showing_option_name() {
		/**
		 * Filter to modify the option name for the setup wizard showing status.
		 *
		 * @since 1.0.1
		 * @param string $option_name The option name.
		 * @return string
		 */
		return apply_filters( 'getting_started_wizard_option_name', 'getting_started_is_setup_wizard_showing' );
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
GS_Helper::get_instance();
