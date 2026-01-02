<?php
/*
Plugin Name: FileOrganizer
Plugin URI: https://wordpress.org/plugins/fileorganizer/
Description: FileOrganizer is a plugin that helps you to manage all files in your WordPress Site.
Version: 1.1.8
Author: Softaculous Team
Author URI: https://fileorganizer.net
Text Domain: fileorganizer
*/

// We need the ABSPATH
if(!defined('ABSPATH')) exit;

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

$_tmp_plugins = get_option('active_plugins', []);

if(!defined('SITEPAD') && in_array('fileorganizer-pro/fileorganizer-pro.php', $_tmp_plugins)){

	// Was introduced in 1.0.9
	$fileorganizer_pro_info = get_option('fileorganizer_pro_version');
	
	if(!empty($fileorganizer_pro_info) && version_compare($fileorganizer_pro_info, '1.0.9', '>=')){
		// Let Fileorganizer load
	
	// Lets check for older versions
	}else{

		if(!function_exists('get_plugin_data')){
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$fileorganizer_pro_info = get_plugin_data(WP_PLUGIN_DIR . '/fileorganizer-pro/fileorganizer-pro.php');
		
		if(!empty($fileorganizer_pro_info) && version_compare($fileorganizer_pro_info['Version'], '1.0.9', '<')){
			return;
		}
	}
}

// If FILEORGANIZER_VERSION exists then the plugin is loaded already !
if(defined('FILEORGANIZER_VERSION')){
	return;
}

define('FILEORGANIZER_FILE', __FILE__);
define('FILEORGANIZER_VERSION', '1.1.8');

include_once(dirname(__FILE__).'/init.php');
