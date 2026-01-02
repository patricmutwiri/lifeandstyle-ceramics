<?php
/*
Plugin Name: Loginizer
Plugin URI: https://wordpress.org/extend/plugins/loginizer/
Description: Loginizer is a WordPress plugin which helps you fight against bruteforce attack by blocking login for the IP after it reaches maximum retries allowed. You can blacklist or whitelist IPs for login using Loginizer.
Version: 2.0.4
Text Domain: loginizer
Author: Softaculous
Author URI: https://www.loginizer.com
License: LGPLv2.1
*/

/*
Copyright (C) 2013 Loginizer (email : support@loginizer.com)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

$_tmp_plugins = get_option('active_plugins', []);

if(!defined('SITEPAD') && in_array('loginizer-security/loginizer-security.php', $_tmp_plugins)){

	// Was introduced in 1.9.0
	$loginizer_pro_info = get_option('loginizer_pro_version');
	
	if(!empty($loginizer_pro_info) && version_compare($loginizer_pro_info, '1.9.0', '>=')){
		// Let Loginizer load
	
	// Lets check for older versions
	}else{

		if(!function_exists('get_plugin_data')){
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$loginizer_pro_info = get_plugin_data(WP_PLUGIN_DIR . '/loginizer-security/loginizer-security.php');
		
		if(!empty($loginizer_pro_info) && version_compare($loginizer_pro_info['Version'], '1.8.9', '<')){
			return;
		}
	}
}


function loginizer_load_plugin_textdomain(){
	load_plugin_textdomain('loginizer', FALSE, basename( dirname( __FILE__ ) ) . '/languages/');
}

add_action('init', 'loginizer_load_plugin_textdomain', 0);

// Is the premium plugin active ?
if(defined('LOGINIZER_VERSION')){
	return;
}

define('LOGINIZER_FILE', __FILE__);

include_once(dirname(__FILE__).'/init.php');

