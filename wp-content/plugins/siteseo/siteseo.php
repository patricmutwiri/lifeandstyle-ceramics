<?php
/*
Plugin Name: SiteSEO - SEO Simplified
Plugin URI: https://siteseo.io/
Description: SiteSEO is an easy, fast and powerful SEO plugin for WordPress. Unlock your Website's potential and Maximize your online visibility with our SiteSEO!
Author: Softaculous
Version: 1.3.5
Requires at least: 5.0
Author URI: https://siteseo.io/
License: GPLv2
Text Domain: siteseo
*/

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

if(defined('SITESEO_VERSION')){
	return;
}

define('SITESEO_VERSION', '1.3.5');
define('SITESEO_FILE', __FILE__);
define('SITESEO_DOCS', 'https://siteseo.io/docs/');
define('SITESEO_DIR_PATH', plugin_dir_path(SITESEO_FILE));
define('SITESEO_DIR_URL', plugin_dir_url(SITESEO_FILE));
define('SITESEO_ASSETS_PATH', SITESEO_DIR_PATH . 'assets');
define('SITESEO_ASSETS_URL', SITESEO_DIR_URL . 'assets');
define('SITESEO_DEV', file_exists(SITESEO_DIR_PATH.'DEV.php'));

include_once(SITESEO_DIR_PATH . 'functions.php');

function siteseo_autoloader($class){
	if(!preg_match('/^SiteSEO\\\(.*)/is', $class, $m)){
		return;
	}

	$m[1] = str_replace('\\', '/', $m[1]);

	// Include file
	if(file_exists(SITESEO_DIR_PATH . 'main/'.strtolower($m[1]).'.php')){
		include_once(SITESEO_DIR_PATH.'main/'.strtolower($m[1]).'.php');
	}
}

spl_autoload_register('siteseo_autoloader');

register_activation_hook(SITESEO_FILE, '\SiteSEO\Install::activate');
register_deactivation_hook(SITESEO_FILE, '\SiteSEO\Install::deactivate');
register_uninstall_hook(SITESEO_FILE, '\SiteSEO\Install::uninstall');
add_action('plugins_loaded', 'siteseo_load_plugin');

function siteseo_load_plugin(){
	global $siteseo;

	if(empty($siteseo)){
		$siteseo = new StdClass();
	}
	
	// Loading all the options.
	$siteseo->setting_enabled = get_option('siteseo_toggle', []);
	$siteseo->titles_settings = get_option('siteseo_titles_option_name', []);
	$siteseo->social_settings = get_option('siteseo_social_option_name', []);
	$siteseo->advanced_settings = get_option('siteseo_advanced_option_name', []);
	$siteseo->instant_settings = get_option('siteseo_instant_indexing_option_name', []);
	$siteseo->sitemap_settings = get_option('siteseo_xml_sitemap_option_name', []);
	$siteseo->analaytics_settings = get_option('siteseo_google_analytics_option_name', []);
	
	siteseo_check_update();
	
	if(!empty($siteseo->setting_enabled['toggle-advanced'])){
		add_action('init','\SiteSEO\ImageSeo::init', 11); // Upload happens with AJAX so we need this here
	}

	if(wp_doing_ajax()){
		\SiteSEO\Ajax::hooks();
		return;
	}

	if(defined('SITESEO_PRO_VERSION') && version_compare(SITESEO_PRO_VERSION, '1.1.5', '<')){ 
		if(!function_exists('is_plugin_active')){ 
			require_once ABSPATH . 'wp-admin/includes/plugin.php'; 
		} 
	}

	// Image & Sitemap block
	add_action('init', '\SiteSEO\Admin::register_sitmap_block');

	// TOC 
	add_shortcode('siteseo_toc', '\SiteSEO\TableofContent::render_toc');
	add_action('init', '\SiteSEO\TableofContent::enable_toc');

	if(!empty($siteseo->setting_enabled['toggle-xml-sitemap']) && !empty($siteseo->sitemap_settings['xml_sitemap_general_enable'])){
		add_filter('wp_sitemaps_enabled', '__return_false'); // Disabling default WP Sitemap

		add_action('init', '\SiteSEO\GenerateSitemap::add_rewrite_rules', 20);
		add_action('template_redirect', '\SiteSEO\GenerateSitemap::handle_sitemap_requests', 0);
	}

	if(!empty($siteseo->sitemap_settings['toggle-xml-sitemap']) && !empty($siteseo->sitemap_settings['xml_sitemap_general_enable'])){
		add_shortcode('siteseo_html_sitemap', '\SiteSEO\GenerateSitemap::html_sitemap');
	}

	// Redirect
	add_action('template_redirect', '\SiteSEO\GoogleAnalytics::handle_custom_redirect');
	add_action('init', '\SiteSEO\Advanced::remove_wc_category_base');

	if(!empty($siteseo->setting_enabled['toggle-instant-indexing']) && !empty($siteseo->instant_settings['instant_indexing_bing_api_key'])){
		add_action('template_redirect', '\SiteSEO\InstantIndexing::bing_txt_file', 0);

		if(!empty($siteseo->instant_settings['instant_indexing_automate_submission'])){
			add_action('transition_post_status', '\SiteSEO\InstantIndexing::on_status_change', 10, 3);
		}
	}

	\SiteSEO\Admin::permission();

	if(!empty($siteseo->setting_enabled['toggle-advanced']) && empty($siteseo->advanced_settings['appearance_universal_metabox_disable']) && siteseo_user_can_metabox()){
		add_action('wp_enqueue_scripts', 'siteseo_universal_assets');
		add_action('enqueue_block_editor_assets', 'siteseo_universal_assets');
		
		if(defined('SITESEO_PRO_VERSION') && version_compare(SITESEO_PRO_VERSION, '1.2.6', '>=')){
			add_action('wp_enqueue_scripts', '\SiteSEOPro\Admin::enqueue_metabox');
			add_action('enqueue_block_editor_assets', '\SiteSEOPro\Admin::enqueue_metabox');
		}
	}
	
	if(!is_admin()){
		// Code that will be used in the frontend will go here.

		if(defined('WPB_VC_VERSION') && !empty($_GET['vc_editable']) && $_GET['vc_editable'] === 'true'){
			return; // WPBakery
		}

		remove_action('wp_head', 'rel_canonical');
		add_action('after_setup_theme', 'siteseo_remove_elementor_description_meta_tag');
			
		// Cookies enqueue
		add_action('wp_enqueue_scripts', '\SiteSEO\Admin::cookies_bar');

		// Titles and Metas
		add_action('wp_head', '\SiteSEO\TitlesMetas::add_nositelinkssearchbox', 1);
		add_action('wp_head', '\SiteSEO\TitlesMetas::add_canonical_url', 1);
		add_filter('wp_title', '\SiteSEO\TitlesMetas::modify_site_title', 15, 2);
		add_filter('pre_get_document_title', '\SiteSEO\TitlesMetas::modify_site_title', 15);
		add_action('wp_head', '\SiteSEO\TitlesMetas::add_meta_description', 1);
		add_filter('wp_robots', '\SiteSEO\TitlesMetas::advanced_metas', 999);
		add_action('wp_head', '\SiteSEO\TitlesMetas::add_rel_link_pages', 9);
		add_action('wp_head', '\SiteSEO\TitlesMetas::date_time_publish', 3);

		// Social
		add_action('wp_head', '\SiteSEO\SocialMetas::add_social_graph', 1);
		add_action('wp_head', '\SiteSEO\SocialMetas::fb_graph', 1);
		add_action('wp_head', '\SiteSEO\SocialMetas::twitter_card', 1);

		// Sitemaps
		add_action('init', '\SiteSEO\GenerateSitemap::settings', 5);

		// Image & Advanced
		add_action('wp_head', '\SiteSEO\Advanced::tags');
		add_action('init', '\SiteSEO\Advanced::remove_links');

		// Analaytics
		add_action('init', '\SiteSEO\GoogleAnalytics::ga_render');
		
		add_filter('post_link_category', '\SiteSEO\PrimaryCategory::add_primary_category', 10, 3);
		add_filter('wc_product_post_type_link_product_cat', '\SiteSEO\PrimaryCategory::wc_primary_category', 10, 3);
		add_filter('woocommerce_get_breadcrumb', '\SiteSEO\PrimaryCategory::replace_breadcrumb_categories', 10, 2);
		
		return;
	}

	if(is_admin()){
		\SiteSEO\Admin::init();
	}
}

function siteseo_check_update(){
	global $siteseo;

	$current_version = get_option('siteseo_version');	
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == SITESEO_VERSION){
		return true;
	}
	
	if($version < 115){
		// Older version had enable index, and if it was set it actually meant it was enabled.
		$options = $siteseo->titles_settings['titles_single_titles'];
		
		if(!empty($options)){
			foreach($options as &$option){
				if(!empty($option['enable'])){
					unset($option['enable']);
					$option['disabled'] = true;
				}
			}
			
			$siteseo->titles_settings['titles_single_titles'] = $options;
		}

		update_option('siteseo_titles_option_name', $siteseo->titles_settings);
	}

	if($version > 115 && $version < 117){
		$options = $siteseo->titles_settings['titles_single_titles'];

		// In 115 if enabled was true then the options will be enabled, which was an issue
		// As it means user will have to manually enable every metabox option.
		if(!empty($options)){
			foreach($options as &$option){
				if(!empty($option['enable'])){
					unset($option['enable']);
				} elseif(isset($option['enable'])){
					$option['disabled'] = true;
					unset($option['enable']);
				}
			}

			$siteseo->titles_settings['titles_single_titles'] = $options;
		}

		update_option('siteseo_titles_option_name', $siteseo->titles_settings);
	}

	// Is it first run ?
	if(empty($current_version)){
		\SiteSEO\Install::activate();
		return;
	}

	update_option('siteseo_version', SITESEO_VERSION);
}

