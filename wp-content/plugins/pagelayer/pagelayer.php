<?php
/*
Plugin Name: Pagelayer
Plugin URI: http://wordpress.org/plugins/pagelayer/
Description: Pagelayer is a WordPress page builder plugin. Its very easy to use and very light on the browser.
Version: 2.0.7
Author: Pagelayer Team
Author URI: https://pagelayer.com/
License: LGPL v2.1
License URI: http://www.gnu.org/licenses/lgpl-2.1.html
*/

// We need the ABSPATH
if (!defined('ABSPATH')) exit;

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

$softac_tmp_plugins = get_option('active_plugins');

// Is the premium plugin loaded ?
if(!defined('SITEPAD') && in_array('pagelayer-pro/pagelayer-pro.php', $softac_tmp_plugins) ){
	
	// Was introduced in 1.8.8
	$pagelayer_pro_info = get_option('pagelayer_pro_version');
	
	if(!empty($pagelayer_pro_info) && version_compare($pagelayer_pro_info, '1.8.8', '>=')){
		// Let Pagelayer load
	
	// Lets check for older versions
	}else{
		
		if(!function_exists( 'get_plugin_data' )){
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pagelayer_pro_info = get_plugin_data(WP_PLUGIN_DIR . '/pagelayer-pro/pagelayer-pro.php');
		
		if(
			!empty($pagelayer_pro_info) &&
			version_compare($pagelayer_pro_info['Version'], '1.8.6', '<')
		){
			return;
		}
		
	}
}

// If PAGELAYER_VERSION exists then the plugin is loaded already !
if(defined('PAGELAYER_VERSION')) {
	return;
}

define('PAGELAYER_FILE', __FILE__);

include_once(dirname(__FILE__).'/init.php');
