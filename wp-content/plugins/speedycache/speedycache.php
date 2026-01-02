<?php
/*
Plugin Name: SpeedyCache
Plugin URI: https://speedycache.com
Description: SpeedyCache is a plugin that helps you reduce the load time of your website by means of caching, minification, and compression of your website.
Version: 1.3.7
Author: Softaculous Team
Author URI: https://speedycache.com/
Text Domain: speedycache
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// We need the ABSPATH
if (!defined('ABSPATH')) exit;

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

$_tmp_plugins = get_option('active_plugins', []);

// Is the premium plugin loaded ?
if(!defined('SITEPAD') && in_array('speedycache-pro/speedycache-pro.php', $_tmp_plugins) ){
	$speedycache_pro_info = get_option('speedycache_pro_version');
	
	if(!empty($speedycache_pro_info) && version_compare($speedycache_pro_info, '1.1.1', '>=')){
		// Let SpeedyCache load
	
	// Lets check for older versions
	}else{
		
		if(!function_exists('get_plugin_data')){
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$speedycache_pro_info = get_plugin_data(WP_PLUGIN_DIR . '/speedycache-pro/speedycache-pro.php');
		
		if(
			!empty($speedycache_pro_info) &&
			version_compare($speedycache_pro_info['Version'], '1.1.1', '<')
		){
			return;
		}
	}
}

// If SPEEDYCACHE_VERSION exists then the plugin is loaded already !
if(defined('SPEEDYCACHE_VERSION')) {
	return;
}

define('SPEEDYCACHE_VERSION', '1.3.7');
define('SPEEDYCACHE_DIR', dirname(__FILE__));
define('SPEEDYCACHE_FILE', __FILE__);
define('SPEEDYCACHE_BASE', plugin_basename(SPEEDYCACHE_FILE));
define('SPEEDYCACHE_URL', plugins_url('', __FILE__));
define('SPEEDYCACHE_BASE_NAME', basename(SPEEDYCACHE_DIR));
define('SPEEDYCACHE_WP_CONTENT_DIR', defined('SITEPAD') ? 'sitepad-data' : (defined('WP_CONTENT_FOLDERNAME') ? WP_CONTENT_FOLDERNAME : 'wp-content'));
define('SPEEDYCACHE_CACHE_DIR', WP_CONTENT_DIR . '/cache/speedycache');
define('SPEEDYCACHE_WP_CONTENT_URL', content_url());
define('SPEEDYCACHE_CONFIG_DIR', WP_CONTENT_DIR . '/speedycache-config');
define('SPEEDYCACHE_CACHE_URL', content_url('/cache/speedycache'));
define('SPEEDYCACHE_DEV', file_exists(SPEEDYCACHE_DIR.'/DEV.php'));

if(SPEEDYCACHE_DEV){
	include_once SPEEDYCACHE_DIR .'/DEV.php';
}

if(!defined('SPEEDYCACHE_API')){
	define('SPEEDYCACHE_API', 'https://api.speedycache.com/');
}

function speedycache_autoloader($class){
	
	if(!preg_match('/^SpeedyCache\\\(.*)/is', $class, $m)){
		return;
	}
	
	$m[1] = str_replace('\\', '/', $m[1]);

	if(strpos($class, 'SpeedyCache\lib') === 0){
		if(file_exists(SPEEDYCACHE_DIR.'/'.$m[1].'.php')){
			include_once(SPEEDYCACHE_DIR.'/'.$m[1].'.php');
		}
	}
	
	// For Free
	if(file_exists(SPEEDYCACHE_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(SPEEDYCACHE_DIR.'/main/'.strtolower($m[1]).'.php');
	}
	
	// For Pro
	if(defined('SPEEDYCACHE_PRO_DIR') && file_exists(SPEEDYCACHE_PRO_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(SPEEDYCACHE_PRO_DIR.'/main/'.strtolower($m[1]).'.php');
	}
}

spl_autoload_register(__NAMESPACE__.'\speedycache_autoloader');

if(!class_exists('SpeedyCache')){
#[\AllowDynamicProperties]
class SpeedyCache{}
}

register_activation_hook(__FILE__, '\SpeedyCache\Install::activate');
register_deactivation_hook(__FILE__, '\SpeedyCache\Install::deactivate');
register_uninstall_hook(__FILE__, '\SpeedyCache\Install::uninstall');
add_action('plugins_loaded', 'speedycache_load_plugin');

function speedycache_load_plugin(){
	global $speedycache;
	
	if(empty($speedycache)){
		$speedycache = new SpeedyCache();
	}
	
	speedycache_update_check();

	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
		return;
	}
	
	// This file is just to handle deprecation.
	include_once __DIR__ . '/functions.php';

	$speedycache->options = get_option('speedycache_options', []);
	$speedycache->settings['noscript'] = '';
	$speedycache->cdn = get_option('speedycache_cdn', []);
	$speedycache->settings['cdn'] = $speedycache->cdn;
	$speedycache->image['settings'] = get_option('speedycache_img', []);
	$speedycache->object = get_option('speedycache_object_cache', ['admin' => true, 'persistent' => true]);
	$speedycache->bloat = get_option('speedycache_bloat', []);
	
	if(!is_dir(SPEEDYCACHE_CACHE_DIR) && is_writable(WP_CONTENT_DIR)){
		if(mkdir(SPEEDYCACHE_CACHE_DIR, 0755, true)){
			touch(SPEEDYCACHE_CACHE_DIR .'/index.html');
		}
	}
	
	// Creating config folder if it dosent exists
	if(!is_dir(SPEEDYCACHE_CONFIG_DIR) && is_writable(WP_CONTENT_DIR)){
		if(mkdir(SPEEDYCACHE_CONFIG_DIR, 0755, true)){
			touch(SPEEDYCACHE_CONFIG_DIR .'/index.html');
		}
	}
	
	add_action('cron_schedules', '\SpeedyCache\Util::custom_cron');

	if(wp_doing_ajax() && !empty($_REQUEST['action']) && strpos($_REQUEST['action'], 'speedycache') === 0){
		\SpeedyCache\Ajax::hooks();
		return; // we don't want to process anything else if it is Ajax
	}

	// NOTE:: If actions or code which are required to run on both admin and front grows then move that to a seperate file and keep this file small.
	add_action('speedycache_purge_cache', '\SpeedyCache\Delete::expired_cache'); // Schedule action for cache lifespan
	add_action('cron_schedules', '\SpeedyCache\Util::custom_expiry_cron');
	add_action('cron_schedules', '\SpeedyCache\Util::custom_preload_cron');
	add_action('init', '\SpeedyCache\Util::lifespan_cron');
	add_action('init', '\SpeedyCache\Util::preload_cron');
	add_action('speedycache_preload_split', '\SpeedyCache\Preload::cache');
	add_action('speedycache_preload', '\SpeedyCache\Preload::build_preload_list');
	add_action('after_switch_theme', '\SpeedyCache\Delete::run'); // Deletes cache when Theme changes
	add_action('wp_update_nav_menu', '\SpeedyCache\Delete::run'); // Deletes cache when Menu is saved
	add_action('transition_post_status', '\SpeedyCache\Delete::on_status_change', 10, 3);
	add_action('transition_comment_status', '\SpeedyCache\Delete::on_comment_status', 10, 3);
	add_action('admin_bar_menu', '\SpeedyCache\Admin::admin_bar', PHP_INT_MAX);
	add_action('woocommerce_order_status_changed', '\SpeedyCache\Delete::order');

	if(!empty($speedycache->options['status']) && !empty($speedycache->cdn['enabled']) && !empty($speedycache->cdn['cdn_url'])){
		add_action('wp_head','\SpeedyCache\CDN::cdn_preconnect', 5);
	}

	if(!empty($speedycache->options['speculation_loading'])){
		add_filter('wp_speculation_rules_configuration', 'speedycache_speculation_rules_config');
	}
	
	if(class_exists('\SpeedyCache\Bloat') && !empty($speedycache->bloat)){
		\SpeedyCache\Bloat::actions();
	}
	
	if(!is_admin()){
		\SpeedyCache\Cache::init();
		return;
	}

	\SpeedyCache\Admin::hooks();
}

// Looks if SpeedyCache just got updated
function speedycache_update_check(){
	$current_version = get_option('speedycache_version');	
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == SPEEDYCACHE_VERSION){
		return true;
	}

	// Is it first run ?
	if(empty($current_version)){
		\SpeedyCache\Install::activate();
		return;
	}
	
	if(version_compare($current_version, '1.2.0', '<')){
		// Cleaning the cache because we have a new way
		if(file_exists(SPEEDYCACHE_CACHE_DIR)){
			\SpeedyCache\Delete::rmdir(SPEEDYCACHE_CACHE_DIR);
		}
		
		\SpeedyCache\Install::activate();
		\SpeedyCache\Util::set_config_file();
	}
	
	// TODO: Remove when the version above 1.2.7 is 90% in adoption.
	if(version_compare($current_version, '1.2.8', '<')){
		$options = get_option('speedycache_options', []);
		$options['logged_in_user'] = false;

		update_option('speedycache_options', $options);
	}

	// Save the new Version
	update_option('speedycache_version', SPEEDYCACHE_VERSION);
}