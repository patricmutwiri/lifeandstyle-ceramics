<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Admin{
	
	static function permission(){
		add_action('admin_init','\SiteSEO\Admin::add_capabilities');
	}

	static function init(){
		global $siteseo, $pagenow;

		if(!empty($_GET['page']) && $_GET['page'] == 'siteseo-onboarding'){
			\SiteSEO\Settings\OnBoarding::init();
		}
		
		// === Plugin Update Notice === //
		if(current_user_can('administrator')){
			$plugin_update_notice = get_option('softaculous_plugin_update_notice', []);
			$available_update_list = get_site_transient('update_plugins'); 
			$plugin_path_slug = 'siteseo/siteseo.php';

			if(
				!empty($available_update_list) &&
				is_object($available_update_list) && 
				!empty($available_update_list->response) &&
				!empty($available_update_list->response[$plugin_path_slug]) && 
				(empty($plugin_update_notice) || empty($plugin_update_notice[$plugin_path_slug]) || (!empty($plugin_update_notice[$plugin_path_slug]) &&
				version_compare($plugin_update_notice[$plugin_path_slug], $available_update_list->response[$plugin_path_slug]->new_version, '<')))
			){
				add_action('admin_notices', '\SiteSEO\Admin::update_notice');
				add_filter('softaculous_plugin_update_notice', 'siteseo_plugin_update_notice_filter');
			}
		}
		// === Plugin Update Notice === //
		
		add_action('admin_menu', '\SiteSEO\Admin::add_menu');
		
		// We do not anything else after this.
		if(!empty($_REQUEST['page']) && sanitize_text_field(wp_unslash($_GET['page'])) == 'siteseo-metabox-wizard'){
			add_action('admin_enqueue_scripts', '\SiteSEO\Admin::enqueue_metaboxes');
			return;
		}

		if($pagenow == 'post.php' || $pagenow == 'post-new.php'){
			add_action('admin_enqueue_scripts', '\SiteSEO\Admin::enqueue_metaboxes');
			add_action('add_meta_boxes', '\SiteSEO\Admin::add_metaboxes');
		}

		if($pagenow == 'term.php' || $pagenow == 'edit-tags.php'){
			add_action('admin_enqueue_scripts', '\SiteSEO\Admin::enqueue_metaboxes');
			add_action('admin_init', '\SiteSEO\Admin::add_term_metabox');
		}
		
		add_filter('plugin_action_links', '\SiteSEO\Install::action_links', 10, 2);
		
		if(!defined('SITEPAD')){
			add_filter('admin_footer_text', '\SiteSEO\Admin::rating_promotion');
		}

		add_action('admin_enqueue_scripts', '\SiteSEO\Admin::enqueue_script');
		add_action('enqueue_block_editor_assets', '\SiteSEO\Admin::enqueue_metaboxes');
		add_filter( 'block_categories_all','\SiteSEO\Admin::create_siteseo_block');
		add_filter('admin_body_class', '\SiteSEO\Admin::body_class', 10, 1);
		
		add_action('admin_bar_menu', '\SiteSEO\Admin::admin_bar', PHP_INT_MAX);
		add_action('admin_bar_menu', '\SiteSEO\Admin::noindex_warning', 100);
		add_action('admin_enqueue_scripts', '\SiteSEO\Admin::header_enqueue');
		add_action('admin_enqueue_scripts', '\SiteSEO\Admin::enqueue_admin_styles');

		// We do not want to show any metabox if we have universal metabox enabled.
		if(empty($siteseo->setting_enabled['toggle-advanced']) || empty($siteseo->advanced_settings['appearance_universal_metabox'])){
			add_action('enqueue_block_editor_assets', '\SiteSEO\Admin::enqueue_sidebar');
		}

		// Coloumn
		add_filter('manage_posts_columns', '\SiteSEO\Columns::add_columns');
		add_filter('manage_pages_columns', '\SiteSEO\Columns::add_columns');
		add_action('manage_posts_custom_column', '\SiteSEO\Columns::populate_custom_seo_columns', 10, 2);
		add_action('manage_pages_custom_column', '\SiteSEO\Columns::populate_custom_seo_columns', 10, 2);
		add_filter('manage_edit-post_sortable_columns', '\SiteSEO\Columns::make_seo_columns_sortable');
		add_filter('manage_edit-page_sortable_columns', '\SiteSEO\Columns::make_seo_columns_sortable');
		add_action('admin_menu', '\SiteSEO\Columns::hide_genesis_seo', 999);
		add_action('woocommerce_process_product_meta', '\SiteSEO\Metaboxes\Settings::save_metabox', 10, 2);
		add_action('save_post', '\SiteSEO\Metaboxes\Settings::save_ca_metabox', 10, 2);
		add_action('save_post', '\SiteSEO\Metaboxes\Settings::save_metabox', 10, 2);
	}
	
	static function enqueue_admin_styles($hook){
		if($hook !== 'edit.php'){
			return;
		}

		wp_enqueue_style('siteseo-admin-columns', SITESEO_ASSETS_URL.'/css/admin-columns.css', [], SITESEO_VERSION);
	}
	
	static function add_capabilities(){
		global $siteseo;

		$options = get_option('siteseo_advanced_option_name');
		$roles = wp_roles();

		foreach($roles->get_names() as $role_slug => $role_name){
			$role = get_role($role_slug);
			if(empty($role)) continue;

			if($role_slug === 'administrator'){
				$role->add_cap('siteseo_manage', true); // Adding the only cap to admin.
				continue;
			}
			
			// The structure is page name => capability name without the prefix of siteseo_manage_
			// Will need to add it here whenever a new page is added to SiteSEO
			$pages = [
				'titles' => 'titles',
				'xml-sitemap' => 'sitemap', 
				'social' => 'social',
				'google-analytics' => 'analytics',
				'instant-indexing' => 'instant_indexing',
				'advanced' => 'advanced', 
			];

			$has_access = 0; // To make sure siteseo_manage is added once.
			foreach($pages as $page => $cap){
				$option_key = "siteseo_advanced_security_metaboxe_siteseo-{$page}";
				if(!empty($options[$option_key][$role_slug]) && !empty($siteseo->setting_enabled['toggle-advanced'])){
					$has_access++;

					if($has_access == 1){
						$role->add_cap('siteseo_manage', true);
					}
                    
					$role->add_cap('siteseo_manage_'.$cap, true);
				} else {
					$role->remove_cap('siteseo_manage_'.$cap);
				}
			}
			
			// If no one has this access then just remove siteseo_manage as well.
			if(empty($has_access)){
				$role->remove_cap('siteseo_manage');
			}
		}
	}

	static function body_class($classes){
		if(empty($_GET['page']) || strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'siteseo') === FALSE){
			return $classes;
		}

		$classes .= ' siteseo-admin-body';
		
		return $classes;
	}
	
	static function noindex_warning($wp_admin_bar){
		global $siteseo;
		
		$noindex_enabled = !empty($siteseo->titles_settings['titles_noindex']) ? true : ''; 
		$disable_noindex = !empty($siteseo->advanced_settings['appearance_adminbar_noindex']) ? true : '';

		if(empty($noindex_enabled) || !empty($disable_noindex)){
			return $wp_admin_bar;
		}
		
		$wp_admin_bar->add_node([
			'id'    => 'noindex-warning',
			'title' => '<div class="warning-container"><svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="#FFFFFF"><path d="m768-91-72-72q-48.39 32-103.19 49Q538-97 480.49-97q-79.55 0-149.52-30Q261-157 208.5-209.5T126-331.97q-30-69.97-30-149.52 0-57.51 17-112.32 17-54.8 49-103.19l-72-73 51-51 678 679-51 51Zm-288-78q43.69 0 84.85-12Q606-193 643-216L215-644q-23 37-35 78.15-12 41.16-12 84.85 0 129.67 91.16 220.84Q350.33-169 480-169Zm318-97-53-52q22-37 34.5-78.15Q792-437.31 792-481q0-129.67-91.16-220.84Q609.67-793 480-793q-43 0-84.5 12T317-747l-53-52q48.39-32 103.19-49Q422-865 479.9-865q80.1 0 149.6 30t122 82.5Q804-700 834-630.5t30 149.6q0 57.9-17 112.36T798-266ZM536-531ZM432-427Z"/></svg>' . esc_html__('Noindex is on!', 'siteseo') . '</div>',
			'href'  => admin_url('admin.php?page=siteseo-titles'),              
			'meta'  => [
					'class' => 'siteseo-noindex-warning', 
			],
		]);

	}

	static function add_menu(){
		$capability = 'siteseo_manage';
		$siteseo_icon = SITESEO_ASSETS_URL.'/img/logo-24.svg';
		$current_user = wp_get_current_user();
		$is_admin = in_array('administrator', $current_user->roles);
		$options = get_option('siteseo_advanced_option_name');

		add_menu_page(__('SiteSEO', 'siteseo'), 'SiteSEO', 'manage_options', 'siteseo', '\SiteSEO\Settings\Dashboard::dashboard_tab', esc_url($siteseo_icon));

		add_submenu_page('siteseo', __('Dashboard', 'siteseo'), 'Dashboard', 'manage_options', 'siteseo','\SiteSEO\Settings\Dashboard::dashboard_tab');

		$menu_pages = [
			'titles' => [
				'slug' => 'siteseo-titles',
				'title' => __('Titles & Metas', 'siteseo'),
				'callback' => '\SiteSEO\Settings\Titles::menu',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-titles'
		    	],
			'sitemap' => [
				'slug' => 'siteseo-sitemaps',
				'title' => __('Sitemaps', 'siteseo'),
				'callback' => '\SiteSEO\Settings\Sitemap::menu',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-xml-sitemap'
			],
			'social' => [
				'slug' => 'siteseo-social',
				'title' => __('Social Networks', 'siteseo'),
				'callback' => '\SiteSEO\Settings\Social::menu',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-social'
			],
			'analytics' => [
				'slug' => 'siteseo-analytics',
				'title' => __('Analytics', 'siteseo'),
				'callback' => '\SiteSEO\Settings\Analytics::menu',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-google-analytics'
			],
			'indexing' => [
				'slug' => 'siteseo-instant-indexing',
				'title' => __('Instant Indexing', 'siteseo'),
				'callback' => '\SiteSEO\Settings\Instant::menu',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-instant-indexing'
			],
			'advanced' => [
				'slug' => 'siteseo-advanced',
				'title' => __('Advanced', 'siteseo'),
				'callback' => '\SiteSEO\Settings\Advanced::menu',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-advanced'
			],
		];

		foreach($menu_pages as $page){
			$show_page = $is_admin;

			if(!$is_admin){
				foreach($current_user->roles as $role){
					if(isset($options[$page['option_key']][$role]) && $options[$page['option_key']][$role]){
						$show_page = true;
						break;
					}
				}
			}

			if($show_page){
				add_submenu_page('siteseo', $page['title'], $page['title'], $capability, $page['slug'], $page['callback']);
			}
		}
		
		if(!empty($is_admin)){
			add_submenu_page('siteseo', __('Search Statistics', 'siteseo'), __('Search Statistics', 'siteseo') . (( time() < strtotime('31 January 2026') ) ? ' <span style="color:#28a745;margin-left:2px;">NEW</span>' : ''), 'manage_options', 'siteseo-search-statistics', '\SiteSEO\Settings\Statistics::init');
			
			add_submenu_page('siteseo', __('Tools', 'siteseo'), 'Tools', 'manage_options','siteseo-tools' ,'\SiteSEO\Settings\Tools::menu');
		}
	
		// Page for Universal metabox
		add_submenu_page('admin.php', __('Universal MetaBox', 'siteseo'), __('Universal MetaBox', 'siteseo'), 'edit_posts', 'siteseo-metabox-wizard',  '\SiteSEO\Metaboxes\Settings::universal');
	}
	
	static function admin_bar($wp_admin_bar){
		global $siteseo;

		$current_user = wp_get_current_user();
		$is_admin = in_array('administrator', $current_user->roles);
		
		$disable_admin_bar = !empty($siteseo->advanced_settings['appearance_adminbar']) ? true : '';

		if(!$is_admin && !current_user_can('siteseo_access') || !empty($disable_admin_bar)){
			return;
		}

		$siteseo_icon = SITESEO_ASSETS_URL . '/img/logo-24.svg';
		$wp_admin_bar->add_node([
			'id' => 'siteseo',
			'title' => '<span><img src="'.esc_url($siteseo_icon).'" alt="SiteSEO Logo" '.
					  'style="height:20px;vertical-align:middle;margin-right:5px;">'. 
					  esc_html__('SiteSEO', 'siteseo') .'</span>',
			'href' => admin_url('admin.php?page=siteseo'),
			'meta' => ['class' => 'siteseo-admin-bar']
		]);

		$options = get_option('siteseo_advanced_option_name');

		$submenus = [
			'siteseo-dashboard' => [
				'title' => __('Dashboard', 'siteseo'),
				'page' => 'siteseo',
				'option_key' => null
			],
			'siteseo-titles' => [
				'title' => __('Titles & Metas', 'siteseo'),
				'page' => 'siteseo-titles',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-titles'
			],
			'siteseo-sitemaps' => [
				'title' => __('Sitemaps', 'siteseo'),
				'page' => 'siteseo-sitemaps',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-xml-sitemap'
			],
			'siteseo-social' => [
				'title' => __('Social Networks', 'siteseo'),
				'page' => 'siteseo-social',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-social'
			],
			'siteseo-analytics' => [
				'title' => __('Analytics', 'siteseo'),
				'page' => 'siteseo-analytics',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-google-analytics'
			],
			'siteseo-instant-indexing' => [
				'title' => __('Instant Indexing', 'siteseo'),
				'page' => 'siteseo-instant-indexing',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-instant-indexing'
			],
			'siteseo-advanced' => [
				'title' => __('Advanced', 'siteseo'),
				'page' => 'siteseo-advanced',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-advanced'
			],
			'siteseo-tools' => [
				'title' => __('Tools', 'siteseo'),
				'page' => 'siteseo-tools',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-import-export'
			],
			'search-statistics' => [
				'title' => __('Search Statistics', 'siteseo'),
				'page' => 'siteseo-search-statistics',
				'option_key' => 'siteseo_advanced_security_metaboxe_siteseo-search-statistics'
			],
		];


		foreach($submenus as $id => $submenu){
			$show_item = $is_admin;

			if(!$is_admin && $submenu['option_key']){
				foreach($current_user->roles as $role){
					if(isset($options[$submenu['option_key']][$role]) && $options[$submenu['option_key']][$role]){
						$show_item = true;
						break;
					}
				}
			}

			if($show_item || $submenu['option_key'] === null){
				$wp_admin_bar->add_node([
					'id' => $id,
					'parent' => 'siteseo',
					'title' => $submenu['title'],
					'href' => admin_url('admin.php?page=' . $submenu['page'])
		        ]);
			}
		}
		
		if(current_user_can('administrator')){
			$wp_admin_bar->add_node([
				'id' => 'siteseo-configuration-wizard',
				'parent' => 'siteseo',
				'title' => __('Configuration Wizard', 'siteseo'),
				'href' => admin_url('admin.php?page=siteseo-onboarding')
			]);		
		}
		
		// Pro
		if(is_plugin_active('siteseo-pro/siteseo-pro.php') && !defined('SITEPAD')){
			$show_pro = $is_admin;

			if(!$show_pro){
				foreach($current_user->roles as $role){
					if(isset($options['siteseo_advanced_security_page_pro'][$role]) && $options['siteseo_advanced_security_page_pro'][$role]){
						$show_pro = true;
						break;
					}
				}
			}

			if($show_pro){
				$wp_admin_bar->add_node([
					'id' => 'siteseo-pro-page',
					'parent' => 'siteseo',
					'title' => __('Pro Features', 'siteseo'),
					'href' => admin_url('admin.php?page=siteseo-pro-page')
				]);
			}
		}
	}

	static function header_enqueue($hook){
		wp_enqueue_style('siteseo-admin', SITESEO_ASSETS_URL.'/css/header.css', [], SITESEO_VERSION);

		$allowed_pages = ['post.php', 'post-new.php', 'edit.php'];
		if(in_array($hook, $allowed_pages)){
			wp_enqueue_style('siteseo-metabox-pages',SITESEO_ASSETS_URL . '/css/header.css', [], SITESEO_VERSION);
		}
	}

	static function enqueue_metaboxes(){
		
		$social_placeholder = SITESEO_ASSETS_URL . '/img/social-placeholder.png';
		wp_enqueue_media();
		wp_enqueue_style('siteseo-metabox-pages', SITESEO_ASSETS_URL.'/css/metabox.css', [], SITESEO_VERSION);
		wp_enqueue_script('siteseo-metabox', SITESEO_ASSETS_URL.'/js/metabox.js', ['jquery'], SITESEO_VERSION, ['strategy'  => 'defer', 'in_footer' => true]);
		wp_localize_script('siteseo-metabox', 'siteseoAdminAjax', [
	            'url'   => admin_url('admin-ajax.php'), 
	            'nonce' => wp_create_nonce('siteseo_admin_nonce'),
		    'social_placeholder'   => esc_url($social_placeholder)
	        ]);
			
		do_action('siteseo_structured_data_types_enqueue');
			
	}

	static function cookies_bar(){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-google-analytics']) || empty($siteseo->analaytics_settings['google_analytics_disable'])){
			return;
		}
		
		wp_enqueue_style('siteseo-admin-cookies', SITESEO_ASSETS_URL.'/css/cookies.css', [], SITESEO_VERSION);
		wp_enqueue_script('siteseo-cookies-js', SITESEO_ASSETS_URL.'/js/cookies-bar.js', ['jquery'], SITESEO_VERSION, true);
	}

	static function enqueue_script(){
		if(empty($_GET['page']) || strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'siteseo') === FALSE){
			return;
		}
		
		$current_user = wp_get_current_user();
		$is_admin = in_array('administrator', $current_user->roles);

		if($is_admin || current_user_can('siteseo_manage')){
			wp_enqueue_media();
			wp_enqueue_script('siteseo-admin', SITESEO_ASSETS_URL.'/js/admin.js', ['jquery'], SITESEO_VERSION, true);
			wp_enqueue_style('siteseo-admin-bar', SITESEO_ASSETS_URL .'/css/admin-bar.css', [], SITESEO_VERSION);
			wp_enqueue_style('siteseo-admin-pages', SITESEO_ASSETS_URL.'/css/siteseo.css', [], SITESEO_VERSION);

			wp_localize_script('siteseo-admin', 'siteseoAdminAjax', array( 
				'url'   => admin_url('admin-ajax.php'), 
				'nonce' => wp_create_nonce('siteseo_admin_nonce') 
			));
		}
	}

	static function register_sitmap_block(){
		global $siteseo;
			
		if(empty($siteseo->sitemap_settings['xml_sitemap_html_enable'])){
			return;
		}
		
		wp_register_script('sitemap-html-block', SITESEO_ASSETS_URL.'/js/block.js', array('wp-blocks', 'wp-element', 'wp-editor'), filemtime(SITESEO_ASSETS_PATH . '/js/block.js'));

		$html = \SiteSEO\GenerateSitemap::html_sitemap();

		// Localize
		wp_localize_script('sitemap-html-block', 'siteseositemap', array(
			'previewData' => $html,
		));

		register_block_type('siteseo/html-sitemap', array(
			'editor_script' => 'sitemap-html-block',
			'render_callback' => '\SiteSEO\GenerateSitemap::html_sitemap'
		));

	}

	static function create_siteseo_block($categories){
		$siteseo[] = [
			'slug'  => 'siteseo',
			'title' => 'SiteSEO'
		];

		return $siteseo;	
	}
	
	static function enqueue_sidebar(){
		$assets = include SITESEO_ASSETS_PATH . '/js/sidebar/build/index.asset.php';
		$css_file = SITESEO_ASSETS_PATH . '/js/sidebar/build/index.css';
		
		$js_dependencies = $assets['dependencies'];
		
		wp_enqueue_style('siteseo-sidebar', SITESEO_ASSETS_URL . '/js/sidebar/build/index.css', [], $assets['version'].time());
		wp_enqueue_script('siteseo-sidebar', SITESEO_ASSETS_URL . '/js/sidebar/build/index.js', $js_dependencies, $assets['version']);

		wp_localize_script('siteseo-sidebar', 'siteseo_sidebar', [
			'nonce' => wp_create_nonce('siteseo_sidebar_nonce'),
			'ajax_url' => admin_url('admin-ajax.php')
		]);
	}
	
	static function add_metaboxes($post_type, $post = false){
		global $siteseo;
		
		if(!is_user_logged_in()){
			return;
		}

		$metabox_roles = !empty($siteseo->advanced_settings['security_metaboxe_role']) ? $siteseo->advanced_settings['security_metaboxe_role'] : [];
		
		$allow_user = true;

		$user = wp_get_current_user();

		$user_role = current($user->roles);

		if(array_key_exists($user_role, $metabox_roles)){
			$allow_user = false;
		}

		if(empty($allow_user)){
			return;
		}
		
		// Checking if it is a block editor
		if(function_exists('get_current_screen')){
			$screen = get_current_screen();
			
			if(!empty($screen) && method_exists($screen, 'is_block_editor') && $screen->is_block_editor() === true){
				if(!empty($siteseo->advanced_settings['appearance_universal_metabox']) && empty($siteseo->advanced_settings['appearance_universal_metabox_disable'])){
					return;
				}
			}
		}
		
		$post_types = siteseo_post_types();
		$post_types = array_keys($post_types);

		foreach($post_types as $post_type){
			if(empty($siteseo->titles_settings['titles_single_titles'][$post_type]['disabled'])){
				add_meta_box('siteseo-post-metabox', 'SiteSEO', '\SiteSEO\Metaboxes\Settings::render_metabox', $post_type, 'normal', 'high');
			}
		}
	}
	
	static function add_term_metabox(){
		global $siteseo;

		$metabox_roles = !empty($siteseo->advanced_settings['security_metaboxe_role']) ? $siteseo->advanced_settings['security_metaboxe_role'] : [];

		$allow_user = true; 
		
		if(is_user_logged_in()){
			$user = wp_get_current_user();
			
			if(is_super_admin()){
				$allow_user = true;
			} else{				
				$user_role = current($user->roles);

				if(array_key_exists($user_role, $metabox_roles)){
					$allow_user = false;
				}
			}
		}

		if(empty($allow_user)){
			return;
		}

		$taxonomies = get_taxonomies(['show_ui' => true, 'public'  => true], 'objects', 'and');
		$taxonomies = array_keys($taxonomies);

		foreach($taxonomies as $key){
			add_action($key . '_edit_form', '\SiteSEO\Metaboxes\Settings::render_term_metabox', 10, 2);
			add_action('edit_' . $key, '\SiteSEO\Metaboxes\Settings::save_meta_terms', 10, 2);
		}
	}
	
	static function rating_promotion($text){
		global $wp_version;
		
		$screen = get_current_screen();
		
		if(!isset($screen->id) || strpos($screen->id, 'siteseo') === false){
			return $text;
		}
		
		$linkText = esc_html__('Give us a 5-star rating!', 'siteseo');
		$href = 'https://wordpress.org/support/plugin/siteseo/reviews/?filter=5#new-post';

		
		$link1 = wp_kses_post(sprintf(
		/* translators: 1: URL to review page, 2: Link title text */
			__('<a href="%1$s" target="_blank" title="%2$s" id="siteseo-start-promo">&#9733;&#9733;&#9733;&#9733;&#9733;</a>', 'siteseo'),
			esc_url($href),
			$linkText
		));
		
		$link2 = wp_kses_post(sprintf(
			/* translators: 1: URL to review page, 2: Link title text */
			__('<a href="%1$s" target="_blank" title="%2$s">WordPress.org</a>', 'siteseo'),
			esc_url($href),
			$linkText
		));

		ob_start();

		printf(
		/* translators: 1: SiteSEO, 2: Star rating link, 3: WordPress.org link */
			wp_kses_post(__('Please rate %1$s %2$s on %3$s to help us spread the word. Thank you!', 'siteseo')) . '<br>',
			wp_kses_post(sprintf('<strong>%s</strong>', esc_html__('SiteSEO', 'siteseo'))),
			wp_kses_post($link1),
			wp_kses_post($link2)
		);

		printf(
			wp_kses_post('<p class="alignright">%1$s</p>'),
			sprintf(
				/* translators: 1: WordPress version, 2: SiteSEO version */
				esc_html__('WordPress %1$s | SiteSEO %2$s', 'siteseo'),
				esc_html($wp_version),
				esc_html(SITESEO_VERSION)
			)
		);
		
		remove_filter('update_footer', 'core_update_footer');

		return ob_get_clean();
	}
	
	static function update_notice(){
		if(defined('SOFTACULOUS_PLUGIN_UPDATE_NOTICE')){
			return;
		}

		$to_update_plugins = apply_filters('softaculous_plugin_update_notice', []);

		if(empty($to_update_plugins)){
			return;
		}

		/* translators: %1$s is replaced with a "string" of name of plugins, and %2$s is replaced with "string" which can be "is" or "are" based on the count of the plugin */
		$msg = sprintf(__('New version of %1$s %2$s available. Updating ensures better performance, security, and access to the latest features.', 'siteseo'), '<b>'.esc_html(implode(', ', $to_update_plugins)).'</b>', (count($to_update_plugins) > 1 ? 'are' : 'is')) . ' <a class="button button-primary" href='.esc_url(admin_url('plugins.php?plugin_status=upgrade')).'>Update Now</a>';

		define('SOFTACULOUS_PLUGIN_UPDATE_NOTICE', true); // To make sure other plugins don't return a Notice
		echo '<div class="notice notice-info is-dismissible" id="siteseo-plugin-update-notice">
			<p>'.$msg. '</p>
		</div>';

		wp_register_script('siteseo-update-notice', '', ['jquery'], SITESEO_VERSION, true);
		wp_enqueue_script('siteseo-update-notice');
		wp_add_inline_script('siteseo-update-notice', 'jQuery("#siteseo-plugin-update-notice").on("click", function(e){
			let target = jQuery(e.target);

			if(!target.hasClass("notice-dismiss")){
				return;
			}

			var data;
			
			// Hide it
			jQuery("#siteseo-plugin-update-notice").hide();
			
			// Save this preference
			jQuery.post("'.admin_url('admin-ajax.php?action=siteseo_close_update_notice').'&security='.wp_create_nonce('siteseo_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});');
	}
}
