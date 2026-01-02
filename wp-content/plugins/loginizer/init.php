<?php

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

define('LOGINIZER_VERSION', '2.0.4');
define('LOGINIZER_DIR', dirname(LOGINIZER_FILE));
define('LOGINIZER_URL', plugins_url('', LOGINIZER_FILE));
define('LOGINIZER_PRO_URL', 'https://loginizer.com/features#compare');
define('LOGINIZER_PRICING_URL', 'https://loginizer.com/pricing');
define('LOGINIZER_DOCS', 'https://loginizer.com/docs/');

include_once(LOGINIZER_DIR.'/functions.php');

// Ok so we are now ready to go
register_activation_hook(LOGINIZER_FILE, 'loginizer_activation');

// Is called when the ADMIN enables the plugin
function loginizer_activation(){

	global $wpdb;

	$sql = array();
	
	$sql[] = "DROP TABLE IF EXISTS `".$wpdb->prefix."loginizer_logs`";

	$sql[] = "CREATE TABLE `".$wpdb->prefix."loginizer_logs` (
				`username` varchar(255) NOT NULL DEFAULT '',
				`time` int(10) NOT NULL DEFAULT '0',
				`count` int(10) NOT NULL DEFAULT '0',
				`lockout` int(10) NOT NULL DEFAULT '0',
				`ip` varchar(255) NOT NULL DEFAULT '',
				`url` varchar(255) NOT NULL DEFAULT '',
				UNIQUE KEY `ip` (`ip`)
			) DEFAULT CHARSET=utf8;";

	foreach($sql as $sk => $sv){
		$wpdb->query($sv);
	}
	
	add_option('loginizer_version', LOGINIZER_VERSION);
	add_option('loginizer_options', array());
	add_option('loginizer_last_reset', 0);
	add_option('loginizer_whitelist', array());
	add_option('loginizer_blacklist', array());
	add_option('loginizer_2fa_whitelist', array());
	
	// TODO:: REMOVE THIS AFTER MARCH 2025
	$softwp_upgrade = get_option('loginizer_softwp_upgrade', 0);
	if(!defined('SITEPAD') && empty($softwp_upgrade)){
		loginizer_check_softaculous();
	}
}

/**
 * Updates the database structure for Loginizer
 *
 * If the plugin files are updated but database structure is not updated
 * this function will update the database structure as per the plugin version
 * NOTE: This does not update plugin files it just updates the database structure
 */
function loginizer_update_check(){

global $wpdb;

	$sql = array();
	$current_version = get_option('loginizer_version');
	
	// It must be the 1.0 pre stuff
	if(empty($current_version)){
		$current_version = get_option('lz_version');
	}
	
	$version = (int) str_replace('.', '', $current_version);
	
	// No update required
	if($current_version == LOGINIZER_VERSION){
		return true;
	}
	
	// Is it first run ?
	if(empty($current_version)){
		
		// Reinstall
		loginizer_activation();
		
		// Trick the following if conditions to not run
		$version = (int) str_replace('.', '', LOGINIZER_VERSION);
		
	}

	// Is it less than 1.0.1 ?
	if($version < 101){
		
		// TODO : GET the existing settings
	
		// Get the existing settings		
		$lz_failed_logs = lz_selectquery("SELECT * FROM `".$wpdb->prefix."lz_failed_logs`;", 1);
		$lz_options = lz_selectquery("SELECT * FROM `".$wpdb->prefix."lz_options`;", 1);
		$lz_iprange = lz_selectquery("SELECT * FROM `".$wpdb->prefix."lz_iprange`;", 1);
				
		// Delete the three tables
		$sql = array();
		$sql[] = "DROP TABLE IF EXISTS ".$wpdb->prefix."lz_failed_logs;";
		$sql[] = "DROP TABLE IF EXISTS ".$wpdb->prefix."lz_options;";
		$sql[] = "DROP TABLE IF EXISTS ".$wpdb->prefix."lz_iprange;";

		foreach($sql as $sk => $sv){
			$wpdb->query($sv);
		}
		
		// Delete option
		delete_option('lz_version');
	
		// Reinstall
		loginizer_activation();
	
		// TODO : Save the existing settings

		// Update the existing failed logs to new table
		if(is_array($lz_failed_logs)){
			foreach($lz_failed_logs as $fk => $fv){
				$insert_data = array('username' => $fv['username'], 
									'time' => $fv['time'], 
									'count' => $fv['count'], 
									'lockout' => $fv['lockout'], 
									'ip' => $fv['ip']);
									
				$format = array('%s','%d','%d','%d','%s');
				
				$wpdb->insert($wpdb->prefix.'loginizer_logs', $insert_data, $format);
			}			
		}

		// Update the existing options to new structure
		if(is_array($lz_options)){
			foreach($lz_options as $ok => $ov){
				
				if($ov['option_name'] == 'lz_last_reset'){
					update_option('loginizer_last_reset', $ov['option_value']);
					continue;
				}
				
				$old_option[str_replace('lz_', '', $ov['option_name'])] = $ov['option_value'];
			}
			// Save the options
			update_option('loginizer_options', $old_option);
		}

		// Update the existing iprange to new structure
		if(is_array($lz_iprange)){
			
			$old_blacklist = array();
			$old_whitelist = array();
			$bid = 1;
			$wid = 1;
			foreach($lz_iprange as $ik => $iv){
				
				if(!empty($iv['blacklist'])){
					$old_blacklist[$bid] = array();
					$old_blacklist[$bid]['start'] = long2ip($iv['start']);
					$old_blacklist[$bid]['end'] = long2ip($iv['end']);
					$old_blacklist[$bid]['time'] = strtotime($iv['date']);
					$bid = $bid + 1;
				}
				
				if(!empty($iv['whitelist'])){
					$old_whitelist[$wid] = array();
					$old_whitelist[$wid]['start'] = long2ip($iv['start']);
					$old_whitelist[$wid]['end'] = long2ip($iv['end']);
					$old_whitelist[$wid]['time'] = strtotime($iv['date']);
					$wid = $wid + 1;
				}
			}
			
			if(!empty($old_blacklist)) update_option('loginizer_blacklist', $old_blacklist);
			if(!empty($old_whitelist)) update_option('loginizer_whitelist', $old_whitelist);
		}
		
	}
	
	// Is it less than 1.3.9 ?
	if($version < 139){
		
		$wpdb->query("ALTER TABLE ".$wpdb->prefix."loginizer_logs  ADD `url` VARCHAR(255) NOT NULL DEFAULT '' AFTER `ip`;");
	
	}
	
	// Setting alignment to left in social login ?
	if($version < 201){
		$social_settings = get_option('loginizer_social_settings', []);

		if(!empty($social_settings)){		
			if(!empty($social_settings['login']) && (!empty($social_settings['login']['login_form']) || !empty($social_settings['login']['registration_form']))){
				$social_settings['login']['button_alignment'] = 'left';
			}

			if(!empty($social_settings['woocommerce']) && (!empty($social_settings['woocommmerce']['login_form']) || !empty($social_settings['woocommerce']['registration_form']))){
				$social_settings['woocommerce']['button_alignment'] = 'left';
			}

			if(!empty($social_settings['comment']) && !empty($social_settings['comment']['enable_buttons'])){
				$social_settings['comment']['button_alignment'] = 'left';
			}

			update_option('loginizer_social_settings', $social_settings);	
		}
	}
	
	// Save the new Version
	update_option('loginizer_version', LOGINIZER_VERSION);
	
	// TODO:: REMOVE THIS AFTER MARCH 2025
	$softwp_upgrade = get_option('loginizer_softwp_upgrade', 0);
	if(!defined('SITEPAD') && empty($softwp_upgrade)){
		loginizer_check_softaculous();
	}
	
	// In Sitepad Math Captcha is enabled by default
	if(defined('SITEPAD') && get_option('loginizer_captcha') === false){
		$option['captcha_no_google'] = 1;
		add_option('loginizer_captcha', $option);
	}
	
}

// Add the action to load the plugin 
add_action('plugins_loaded', 'loginizer_load_plugin');

// The function that will be called when the plugin is loaded
function loginizer_load_plugin(){
	
	global $loginizer;
	
	// Check if the installed version is outdated
	loginizer_update_check();

	// Set the array
	if(empty($loginizer)){
		$loginizer = array();
	}
	
	$loginizer['prefix'] = !defined('SITEPAD') ? 'Loginizer ' : 'SitePad ';
	$loginizer['app'] = !defined('SITEPAD') ? 'WordPress' : 'SitePad';
	$loginizer['login_basename'] = !defined('SITEPAD') ? 'wp-login.php' : 'login.php';
	$loginizer['wp-includes'] = !defined('SITEPAD') ? 'wp-includes' : 'site-inc';
	
	// The IP Method to use
	$loginizer['ip_method'] = get_option('loginizer_ip_method');
	if($loginizer['ip_method'] == 3){
		$loginizer['custom_ip_method'] = get_option('loginizer_custom_ip_method');
	}
	
	// Load settings
	$options = get_option('loginizer_options');
	$loginizer['max_retries'] = empty($options['max_retries']) ? 3 : $options['max_retries'];
	$loginizer['lockout_time'] = empty($options['lockout_time']) ? 900 : $options['lockout_time']; // 15 minutes
	$loginizer['max_lockouts'] = empty($options['max_lockouts']) ? 5 : $options['max_lockouts'];
	$loginizer['lockouts_extend'] = empty($options['lockouts_extend']) ? 86400 : $options['lockouts_extend']; // 24 hours
	$loginizer['reset_retries'] = empty($options['reset_retries']) ? 86400 : $options['reset_retries']; // 24 hours
	$loginizer['notify_email'] = empty($options['notify_email']) ? 0 : $options['notify_email'];
	$loginizer['notify_email_address'] = lz_is_multisite() ? get_site_option('admin_email') : get_option('admin_email');
	$loginizer['trusted_ips'] = empty($options['trusted_ips']) ? false : true;
	$loginizer['blocked_screen'] = empty($options['blocked_screen']) ? false : true;
	$loginizer['social_settings'] = get_option('loginizer_social_settings', []);
	
	if(!empty($options['notify_email_address'])){
		$loginizer['notify_email_address'] = $options['notify_email_address'];
		$loginizer['custom_notify_email'] = 1;
	}
	
	// Login Success Email Notification.
	$loginizer['login_mail'] = get_option('loginizer_login_mail', []);
	add_action('init', 'loginizer_load_translation_vars', 0);

	$loginizer['login_mail_subject'] = empty($loginizer['login_mail']['subject']) ? '' : $loginizer['login_mail']['subject'];
	$loginizer['login_mail_body'] = empty($loginizer['login_mail']['body']) ? '' : $loginizer['login_mail']['body'];

	// Load the blacklist and whitelist
	$loginizer['blacklist'] = get_option('loginizer_blacklist', []);
	$loginizer['whitelist'] = get_option('loginizer_whitelist', []);
	$loginizer['2fa_whitelist'] = get_option('loginizer_2fa_whitelist');
	
	// It should not be false
	if(empty($loginizer['2fa_whitelist'])){
		$loginizer['2fa_whitelist'] = array();
	}
	
	// When was the database cleared last time
	$loginizer['last_reset']  = get_option('loginizer_last_reset');

	if(!isset($loginizer['ultimate-member-active'])){
		$um_is_active = in_array('ultimate-member/ultimate-member.php', apply_filters('active_plugins', get_option('active_plugins', [])));
		
		$loginizer['ultimate-member-active'] = !empty($um_is_active) ? true : false;
	}
	
	//print_r($loginizer);
	
	// Clear retries
	if((time() - $loginizer['last_reset']) >= $loginizer['reset_retries']){
		loginizer_reset_retries();
	}
	
	$ins_time = get_option('loginizer_ins_time');
	if(empty($ins_time)){
		$ins_time = time();
		update_option('loginizer_ins_time', $ins_time);
	}
	$loginizer['ins_time'] = $ins_time;
	
	// Set the current IP
	$loginizer['current_ip'] = lz_getip();
	
	// Is Brute Force Disabled ?
	$loginizer['disable_brute'] = get_option('loginizer_disable_brute');

	// Filters and actions
	if(empty($loginizer['disable_brute'])){
	
		// Use this to verify before WP tries to login
		// Is always called and is the first function to be called
		//add_action('wp_authenticate', 'loginizer_wp_authenticate', 10, 2);// Not called by XML-RPC
		add_filter('authenticate', 'loginizer_wp_authenticate', 10001, 3);// This one is called by xmlrpc as well as GUI
		
		// Is called when a login attempt fails
		// Hence Update our records that the login failed
		add_action('wp_login_failed', 'loginizer_login_failed');
		
		// Is called before displaying the error message so that we dont show that the username is wrong or the password
		// Update Error message
		add_action('wp_login_errors', 'loginizer_error_handler', 10001, 2);
		add_action('woocommerce_login_failed', 'loginizer_woocommerce_error_handler', 10001);
		add_action('wp_login', 'loginizer_login_success', 10, 2);
		
		if(!empty($loginizer['ultimate-member-active'])){
			add_action('wp_login_failed', 'loginizer_ultimatemember_error_handler', 10001);
		}

		if(!empty($_COOKIE['lz_social_error']) && !empty($loginizer['social_settings']) && !loginizer_is_blacklisted()){
			add_filter('wp_login_errors', 'loginizer_social_login_error_handler', 10000, 2);
		}
	}
	
	// Social Login Form Actions
	if(!empty($loginizer['social_settings']) && !loginizer_is_blacklisted()){
		if(!empty($loginizer['social_settings']['login']['login_form'])){
			add_action('login_form', 'loginizer_social_btn_login');
		}
	}

	if((function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined( 'DOING_AJAX' ) && DOING_AJAX)){
		include_once LOGINIZER_DIR . '/main/ajax.php';
	}

	if(is_admin()){
		include_once LOGINIZER_DIR . '/main/admin.php';
	}
	
	// ----------------
	// PRO INIT END
	// ----------------
	
	// Secuity checks for social login.
	if(!empty($_GET['lz_social_provider']) && loginizer_can_login() && empty($_GET['lz_api'])){
		add_action('init', 'loginizer_social_login_load');
		return;
	}
}

// Should return NULL if everything is fine
function loginizer_wp_authenticate($user, $username, $password){
	
	global $loginizer, $lz_error, $lz_cannot_login, $lz_user_pass;
	
	if(!empty($username) && !empty($password)){
		$lz_user_pass = 1;
	}
	
	// Are you whitelisted ?
	if(loginizer_is_whitelisted()){
		$loginizer['ip_is_whitelisted'] = 1;
		return $user;

	} else if (!empty($loginizer['trusted_ips'])){
		$lz_cannot_login = 1;

		// This is used by WP Activity Log
		apply_filters( 'wp_login_blocked', $username );
		
		// Shows a blocked screen
		if(!empty($loginizer['blocked_screen'])){
			$lz_error['trusted_ip'] = __('You are restricted from logging in as your IP is not whitelisted.', 'loginizer');
			loginizer_blocked_page($lz_error);
		}
		
		return new WP_Error('ip_blacklisted', __('You are restricted from logging in as your IP is not whitelisted.', 'loginizer'));
	}
	
	// Are you blacklisted ?
	if(loginizer_is_blacklisted()){
		$lz_cannot_login = 1;
		
		// This is used by WP Activity Log
		apply_filters( 'wp_login_blocked', $username );
		
		// Shows a blocked screen
		if(!empty($loginizer['blocked_screen'])){
			loginizer_blocked_page($lz_error);
		}
		
		return new WP_Error('ip_blacklisted', implode('', $lz_error), 'loginizer');
	}
	
	// Is the username blacklisted ?
	if(function_exists('loginizer_user_blacklisted')){
		if(loginizer_user_blacklisted($username)){
			$lz_cannot_login = 1;
		
			// This is used by WP Activity Log
			apply_filters( 'wp_login_blocked', $username );

			return new WP_Error('user_blacklisted', implode('', $lz_error), 'loginizer');
		}
	}
	
	if(loginizer_can_login()){
		return $user;
	}
	
	$lz_cannot_login = 1;

	// This is used by WP Activity Log
	apply_filters( 'wp_login_blocked', $username );
	
	// Shows a blocked screen
	if(!empty($loginizer['blocked_screen'])){
		loginizer_blocked_page($lz_error);
	}
	
	return new WP_Error('ip_blocked', implode('', $lz_error), 'loginizer');

}

function loginizer_can_login(){
	
	global $wpdb, $loginizer, $lz_error;
	
	// Get the logs
	$sel_query = $wpdb->prepare("SELECT * FROM `".$wpdb->prefix."loginizer_logs` WHERE `ip` = %s", $loginizer['current_ip']);
	$result = lz_selectquery($sel_query);
	
	if(!empty($result['count']) && ($result['count'] % $loginizer['max_retries']) == 0){

		// Has he reached max lockouts ?
		if($result['lockout'] >= $loginizer['max_lockouts']){
			$loginizer['lockout_time'] = $loginizer['lockouts_extend'];
		}
		
		// Is he in the lockout time ?
		if($result['time'] >= (time() - $loginizer['lockout_time'])){
			$banlift = ceil((($result['time'] + $loginizer['lockout_time']) - time()) / 60);
			
			//echo 'Current Time '.date('d/M/Y H:i:s P', time()).'<br />';
			//echo 'Last attempt '.date('d/M/Y H:i:s P', $result['time']).'<br />';
			//echo 'Unlock Time '.date('d/M/Y H:i:s P', $result['time'] + $loginizer['lockout_time']).'<br />';
			
			$_time = $banlift.' '.$loginizer['msg']['minutes_err'];
			
			if($banlift > 60){
				$banlift = ceil($banlift / 60);
				$_time = $banlift.' '.$loginizer['msg']['hours_err'];
			}
			
			$lz_error['ip_blocked'] = $loginizer['msg']['lockout_err'].' '.$_time;
			
			if(!empty($loginizer['ultimate-member-active']) && class_exists('UM')){ 
				\UM()->form()->add_error('blocked_msg', $lz_error['ip_blocked']);
			}
			return false;
		}
	}
	
	return true;
}

function loginizer_is_blacklisted(){
	
	global $wpdb, $loginizer, $lz_error;
	
	$blacklist = isset($loginizer['blacklist']) ? $loginizer['blacklist'] : [];
	
	if(empty($blacklist)){
		return false;
	}
	  
	foreach($blacklist as $k => $v){
		
		// Is the IP in the blacklist ?
		if(inet_ptoi($v['start']) <= inet_ptoi($loginizer['current_ip']) && inet_ptoi($loginizer['current_ip']) <= inet_ptoi($v['end'])){
			$result = 1;
			break;
		}
		
		// Is it in a wider range ?
		if(inet_ptoi($v['start']) >= 0 && inet_ptoi($v['end']) < 0){
			
			// Since the end of the RANGE (i.e. current IP range) is beyond the +ve value of inet_ptoi, 
			// if the current IP is <= than the start of the range, it is within the range
			// OR
			// if the current IP is <= than the end of the range, it is within the range
			if(inet_ptoi($v['start']) <= inet_ptoi($loginizer['current_ip'])
				|| inet_ptoi($loginizer['current_ip']) <= inet_ptoi($v['end'])){				
				$result = 1;
				break;
			}
			
		}
		
	}
		
	// You are blacklisted
	if(!empty($result)){
		$lz_error['ip_blacklisted'] = $loginizer['msg']['ip_blacklisted'];
		return true;
	}
	
	return false;
	
}

function loginizer_is_whitelisted(){
	
	global $wpdb, $loginizer, $lz_error;
	
	$whitelist = $loginizer['whitelist'];
			
	if(empty($whitelist)){
		return false;
	}
	  
	foreach($whitelist as $k => $v){
		
		// Is the IP in the blacklist ?
		if(inet_ptoi($v['start']) <= inet_ptoi($loginizer['current_ip']) && inet_ptoi($loginizer['current_ip']) <= inet_ptoi($v['end'])){
			$result = 1;
			break;
		}
		
		// Is it in a wider range ?
		if(inet_ptoi($v['start']) >= 0 && inet_ptoi($v['end']) < 0){
			
			// Since the end of the RANGE (i.e. current IP range) is beyond the +ve value of inet_ptoi, 
			// if the current IP is <= than the start of the range, it is within the range
			// OR
			// if the current IP is <= than the end of the range, it is within the range
			if(inet_ptoi($v['start']) <= inet_ptoi($loginizer['current_ip'])
				|| inet_ptoi($loginizer['current_ip']) <= inet_ptoi($v['end'])){				
				$result = 1;
				break;
			}
			
		}
		
	}
		
	// You are whitelisted
	if(!empty($result)){
		return true;
	}
	
	return false;
	
}

// When the login fails, then this is called
// We need to update the database
function loginizer_login_failed($username, $is_2fa = ''){
	
	global $wpdb, $loginizer, $lz_cannot_login;
	
	// Some plugins are changing the value for username as null so we need to handle it before using it for the INSERT OR UPDATE query
	if(empty($username) || is_null($username)){
		$username = '';
	}
	
	$fail_type = 'Login';
	
	if(!empty($is_2fa)){
		$fail_type = '2FA';
	}

	if(empty($lz_cannot_login) && empty($loginizer['ip_is_whitelisted']) && empty($loginizer['no_loginizer_logs'])){
		
		// The params which comes when social login returns an error, have some characters, which WordPress could not save.
		$server_uri = $_SERVER['REQUEST_URI'];
		if(!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'lz_social_provider') !== FALSE){
			$request_uri = explode('=', $_SERVER['REQUEST_URI']);
			$server_uri = $request_uri[0];
		}

		$url = @addslashes((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$server_uri);
		$url = esc_url($url);
		
		$sel_query = $wpdb->prepare("SELECT * FROM `".$wpdb->prefix."loginizer_logs` WHERE `ip` = %s", $loginizer['current_ip']);
		$result = lz_selectquery($sel_query);
		
		if(!empty($result)){
			$lockout = floor((($result['count']+1) / $loginizer['max_retries']));
			
			$update_data = array('username' => $username, 
								'time' => time(), 
								'count' => $result['count']+1, 
								'lockout' => $lockout, 
								'url' => $url);
			
			$where_data = array('ip' => $loginizer['current_ip']);
			
			$format = array('%s','%d','%d','%d','%s');
			$where_format = array('%s');
			
			$wpdb->update($wpdb->prefix.'loginizer_logs', $update_data, $where_data, $format, $where_format);
			
			// Do we need to email admin ?
			if(!empty($loginizer['notify_email']) && $lockout >= $loginizer['notify_email']){
				
				$lockout_time = $loginizer['lockout_time'];
				
				if($lockout >= $loginizer['max_lockouts']){
					// extended lockout is in hours so we have to convert to minute
					$lockout_time = $loginizer['lockouts_extend'];
				}
				
				$sitename = lz_is_multisite() ? get_site_option('site_name') : get_option('blogname');
				$mail = array();
				$mail['to'] = $loginizer['notify_email_address'];	
				$mail['subject'] = 'Failed '.$fail_type.' Attempts from IP '.$loginizer['current_ip'].' ('.$sitename.')';
				$mail['message'] = 'Hi,

'.($result['count']+1).' failed '.strtolower($fail_type).' attempts and '.$lockout.' lockout(s) from IP '.$loginizer['current_ip'].' on your site :
'.home_url().'

Last '.$fail_type.' Attempt : '.date('d/M/Y H:i:s P', time()).'
Last User Attempt : '.$username.'
IP has been blocked until : '.date('d/M/Y H:i:s P', time() + $lockout_time).'

Regards,
Loginizer';

				@wp_mail($mail['to'], $mail['subject'], $mail['message']);
			}
		}else{
			$result = array();
			$result['count'] = 0;
			
			$insert_data = array('username' => $username, 
								'time' => time(), 
								'count' => 1, 
								'ip' => $loginizer['current_ip'], 
								'lockout' => 0, 
								'url' => $url);
								
			$format = array('%s','%d','%d','%s','%d','%s');
			
			$wpdb->insert($wpdb->prefix.'loginizer_logs', $insert_data, $format);
		}
	
		// We need to add one as this is a failed attempt as well
		$result['count'] = $result['count'] + 1;
		loginizer_update_attempt_stats(0);
		$loginizer['retries_left'] = ($loginizer['max_retries'] - ($result['count'] % $loginizer['max_retries']));
		$loginizer['retries_left'] = $loginizer['retries_left'] == $loginizer['max_retries'] ? 0 : $loginizer['retries_left'];
		
	}
}

function loginizer_login_success($user_login, $user) {
	global $wp_version, $loginizer;

	loginizer_update_attempt_stats(1);
	
	if(empty($loginizer['login_mail'])){
		return;
	}

	if(empty($loginizer['login_mail']['enable'])){
		return;
	}

	if(!empty($loginizer['login_mail']['disable_whitelist'])){
		// Check its whitelist ip
		if(loginizer_is_whitelisted()){
			return;
		}
	}

	if(empty($user_login) && empty($user)){
		error_log('Loginizer: No user information to send email');
		return;
	}

	if(empty($user)){
		$user = get_user_by('login', $user_login);
	}

	if(empty($user)){
		error_log('Loginizer: Unable to get the user');
		return;
	}

	if(empty($loginizer['login_mail']['roles']) || !is_array($loginizer['login_mail']['roles'])){
		return;
	}

	// Check if the user role is enabled for email notification.
	if(!array_intersect($user->roles, $loginizer['login_mail']['roles'])){
		return;
	}

	// current_datetime & wp_timezone_string were introduced in WordPress 5.3
	if(!empty($wp_version) && version_compare($wp_version, '5.3', '>') && function_exists('current_datetime')){
		$time_zone = wp_timezone_string();

		if(!empty($time_zone) && isset($time_zone[1]) && is_numeric($time_zone[1])){
			$time_zone = 'UTC'.$time_zone;
		}

		// Setting up data variables.
		$date = current_datetime()->format('Y-m-d H:i:s') .' '. $time_zone;
	} else {
		$date = date("Y-m-d H:i:s", time()) . ' ' . date_default_timezone_get();
	}

	$sitename = lz_is_multisite() ? get_site_option('site_name') : get_option('blogname');
	$email = $user->data->user_email;

	$vars = array(
		'date' => $date,
		'ip' => esc_html($loginizer['current_ip']),
		'sitename' => $sitename,
		'user_login' => $user_login
	);

	$message = lz_lang_vars_name($loginizer['login_mail_body'], $vars);
	$subject = lz_lang_vars_name($loginizer['login_mail_subject'], $vars);
	
	$headers = [];
	
	// Do we need to send the email as HTML ? 
	if(!empty($loginizer['login_mail']['html_mail'])){
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		
		if(!empty($loginizer['login_mail']['body'])){
			$message = html_entity_decode($message);
		}else{
			$message = preg_replace("/\<br\s*\/\>/i", "<br/>", $message);
			$message = preg_replace('/(?<!<br\/>)\n/i', "<br/>\n", $message);
		}
	}

	// Sending notification
	if(empty(wp_mail($email, $subject, $message, $headers))){
		error_log(__('There was a problem sending your email.', 'loginizer'));
		return;
	}
}

function loginizer_update_attempt_stats($type){

	$stats = get_option('loginizer_login_attempt_stats', []);
	$time = strtotime(date('Y-m-d H:00:00'));
	
	if(empty($stats[$time][$type])){
		$stats[$time][$type] = 0;
	}

	$stats[$time][$type] += 1;

	update_option('loginizer_login_attempt_stats', $stats, false);
}

// Handles the error of the password not being there
function loginizer_error_handler($errors, $redirect_to){
	
	global $wpdb, $loginizer, $lz_user_pass, $lz_cannot_login;

	//echo 'loginizer_error_handler :';print_r($errors->errors);echo '<br>';
	if(is_null($errors) || empty($errors)){
		return true;
	}

	// Remove the empty password error
	if(is_wp_error($errors)){
		
		$codes = $errors->get_error_codes();
		
		foreach($codes as $k => $v){
			if($v == 'invalid_username' || $v == 'incorrect_password'){
				$show_error = 1;
			}
		}
		
		$errors->remove('invalid_username');
		$errors->remove('incorrect_password');
	
		// Add the error
		if(!empty($lz_user_pass) && !empty($show_error) && empty($lz_cannot_login)){
			$errors->add('invalid_userpass', '<b>ERROR:</b> ' . $loginizer['msg']['inv_userpass']);
		}
		
		// Add the number of retires left as well
		if(count($errors->get_error_codes()) > 0 && isset($loginizer['retries_left'])){
			$errors->add('retries_left', loginizer_retries_left());
		}

	}
	
	return $errors;
	
}

// Handles the error of the password not being there
function loginizer_woocommerce_error_handler(){

	global $wpdb, $loginizer, $lz_user_pass, $lz_cannot_login;
	
	if(function_exists('wc_add_notice')){
		wc_add_notice( loginizer_retries_left(), 'error' );
	}
}

function loginizer_ultimatemember_error_handler(){
	
	if(class_exists('UM')){ 
		\UM()->form()->add_error('remaining_tries', loginizer_retries_left());
	}
}

// Handles social login URL
function loginizer_social_login_error_handler($errors = '', $redirect_to = ''){
	global $loginizer;

	loginizer_get_social_error();

	if(empty($loginizer['social_errors'])){
		return $errors;
	}

	if(is_null($errors) || empty($errors) || !is_wp_error($errors)){
		$errors = new WP_Error();
	}

	foreach($loginizer['social_errors'] as $key => $text){
		$errors->add($key, $text);
	}

	return $errors;
}

// Returns a string with the number of retries left
function loginizer_retries_left(){
	
	global $wpdb, $loginizer, $lz_user_pass, $lz_cannot_login;
	
	// If we are to show the number of retries left
	if(isset($loginizer['retries_left'])){
		$retries_left = apply_filters('loginizer_retries_left_num', $loginizer['retries_left']);
		
		return '<b>'.esc_html($retries_left).'</b> '.$loginizer['msg']['attempts_left'];
	}
	
}

function loginizer_reset_retries(){

	global $wpdb, $loginizer;

	$deltime = time() - $loginizer['reset_retries'];

	$del_query = $wpdb->prepare("DELETE FROM `".$wpdb->prefix."loginizer_logs` WHERE `time` <= %d", $deltime);
	$result = $wpdb->query($del_query);

	update_option('loginizer_last_reset', time());

}

function loginizer_load_translation_vars(){
	global $loginizer;
	
	$loginizer['login_mail_default_sub'] = __('Login Successful at $sitename', 'loginizer');
	$loginizer['login_mail_default_msg'] = __('Hello $user_login,

Your account was recently logged in from the IP : $ip
Time : $date 
If it was not you who logged in then please report this to us immediately.

Regards,
$sitename','loginizer');

	if(empty($loginizer['login_mail_subject'])){
		$loginizer['login_mail_subject'] = $loginizer['login_mail_default_sub'];
	}
	
	if(empty($loginizer['login_mail_body'])){
		$loginizer['login_mail_body'] = $loginizer['login_mail_default_msg'];
	}
	
	// Default messages
	$loginizer['d_msg']['inv_userpass'] = __('Incorrect Username or Password', 'loginizer');
	$loginizer['d_msg']['ip_blacklisted'] = __('Your IP has been blacklisted', 'loginizer');
	$loginizer['d_msg']['attempts_left'] = __('attempt(s) left', 'loginizer');
	$loginizer['d_msg']['lockout_err'] = __('You have exceeded maximum login retries<br /> Please try after', 'loginizer');
	$loginizer['d_msg']['minutes_err'] = __('minute(s)', 'loginizer');
	$loginizer['d_msg']['hours_err'] = __('hour(s)', 'loginizer');
	
	// Message Strings
	$loginizer['msg'] = get_option('loginizer_msg', []);
	
	foreach($loginizer['d_msg'] as $lk => $lv){
		if(empty($loginizer['msg'][$lk])){
			$loginizer['msg'][$lk] = $loginizer['d_msg'][$lk];
		}
	}
	
	$loginizer['2fa_d_msg']['otp_app'] = __('Please enter the OTP as seen in your App', 'loginizer');
	$loginizer['2fa_d_msg']['otp_email'] = __('Please enter the OTP emailed to you', 'loginizer');
	$loginizer['2fa_d_msg']['otp_field'] = __('One Time Password', 'loginizer');
	$loginizer['2fa_d_msg']['otp_question'] = __('Please answer your security question', 'loginizer');
	$loginizer['2fa_d_msg']['otp_answer'] = __('Your Answer', 'loginizer');
	
	// Message Strings
	$loginizer['2fa_msg'] = get_option('loginizer_2fa_msg', []);
	
	foreach($loginizer['2fa_d_msg'] as $lk => $lv){
		if(empty($loginizer['2fa_msg'][$lk])){
			$loginizer['2fa_msg'][$lk] = $loginizer['2fa_d_msg'][$lk];
		}
	}
	
}

function loginizer_social_login_load(){
	include_once LOGINIZER_DIR . '/main/social-login.php';
}

// Checks if softaculous is installed on the server.
function loginizer_check_softaculous(){

	// Checking if we have Softaculous installed?
	if(!preg_match('/^\/home(?:\d+)?\/.*\//U', ABSPATH, $matches)){
		return false;
	}

	if(empty($matches) || empty($matches[0])){
		return false;
	}

	$softaculous_path = $matches[0] . '.softaculous/installations.php';
	if(!file_exists($softaculous_path)){
		return false;
	}
	
	// Checking if users has changed the branding of Softaculous.
	$universal_file = '';
	// Plesk, ISPManager, ISPConfig, InterWorx, H-Sphere, CentOS Web Panel, Softaculous Remote and Softaculous Enterprise
	if(file_exists('/usr/local/softaculous/enduser/universal.php')){
		$universal_file = '/usr/local/softaculous/enduser/universal.php';
	}else if(file_exists('/usr/local/cpanel/whostmgr/docroot/cgi/softaculous/enduser/universal.php')){
		$universal_file = '/usr/local/cpanel/whostmgr/docroot/cgi/softaculous/enduser/universal.php';
	}else if(file_exists('/usr/local/directadmin/plugins/softaculous/enduser/universal.php')){
		$universal_file = '/usr/local/directadmin/plugins/softaculous/enduser/universal.php';
	}else if(file_exists('/usr/local/vesta/softaculous/enduser/universal.php')){
		$universal_file = '/usr/local/vesta/softaculous/enduser/universal.php';
	}

	if(empty($universal_file)){
		return false;
	}

	$universal = file_get_contents($universal_file);

	if(empty($universal)){
		return false;
	}

	// Checking if Softaculous is being whitelabeled
	if(preg_match('/\$globals\[["\']sn["\']\]\s.?=\s.?["\']Softaculous["\']/', $universal)){
		update_option('loginizer_softwp_upgrade', time());
	}

	return false;
}

// Sorry to see you going
register_uninstall_hook(LOGINIZER_FILE, 'loginizer_deactivation');

function loginizer_deactivation(){

global $wpdb;

	$sql = array();
	$sql[] = "DROP TABLE ".$wpdb->prefix."loginizer_logs;";

	foreach($sql as $sk => $sv){
		$wpdb->query($sv);
	}

	delete_option('loginizer_version');
	delete_option('loginizer_options');
	delete_option('loginizer_last_reset');
	delete_option('loginizer_whitelist');
	delete_option('loginizer_blacklist');
	delete_option('loginizer_msg');
	delete_option('loginizer_2fa_msg');
	delete_option('loginizer_2fa_email_template');
	delete_option('loginizer_security');
	delete_option('loginizer_wp_admin');
	delete_option('loginizer_csrf_promo_time');
	delete_option('loginizer_backuply_promo_time');
	delete_option('loginizer_promo_time');
	delete_option('loginizer_ins_time');
	delete_option('loginizer_2fa_whitelist');
	delete_option('loginizer_checksums_last_run');
	delete_option('loginizer_checksums_diff');
	delete_option('loginizer_ip_method');
	delete_option('loginizer_2fa_custom_redirect');
	delete_option('external_updates-loginizer-security');
	delete_option('loginizer_login_attempt_stats');

}