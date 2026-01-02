<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}


// ------- ACTIONS -------/
add_action('wp_ajax_loginizer_dismiss_csrf', 'loginizer_dismiss_csrf');
add_action('wp_ajax_loginizer_dismiss_backuply', 'loginizer_dismiss_backuply');
add_action('wp_ajax_loginizer_dismiss_social_alert', 'loginizer_dismiss_social_alert');
add_action('wp_ajax_loginizer_dismiss_newsletter', 'loginizer_dismiss_newsletter');
add_action('wp_ajax_loginizer_failed_login_export', 'loginizer_failed_login_export');
add_action('wp_ajax_loginizer_export', 'loginizer_export');
add_action('wp_ajax_loginizer_social_order', 'loginizer_social_order');
add_action('wp_ajax_loginizer_dismiss_license_alert', 'loginizer_dismiss_license_alert');
add_action('wp_ajax_loginizer_dismiss_softwp_alert', 'loginizer_dismiss_softwp_alert');
add_action('wp_ajax_loginizer_close_update_notice', 'loginizer_close_update_notice');

// ----- FUNCTIONS ------//

function loginizer_dismiss_csrf(){

	// Some AJAX security
	check_ajax_referer('loginizer_admin_ajax', 'nonce');
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	update_option('loginizer_csrf_promo_time', (0 - time()));
	echo 1;
	wp_die();
}

function loginizer_dismiss_social_alert(){

	// Some AJAX security
	check_ajax_referer('loginizer_admin_ajax', 'nonce');

	if(!current_user_can('manage_options')){
		wp_send_json_error('Sorry, but you do not have permissions to change settings.');
	}

	update_option('loginizer_social_login_url', wp_login_url());
	
	wp_send_json_success();
}

function loginizer_dismiss_backuply(){

	// Some AJAX security
	check_ajax_referer('loginizer_admin_ajax', 'nonce');

	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	update_option('loginizer_backuply_promo_time', (0 - time()));
	echo 1;
	wp_die();
}

function loginizer_dismiss_newsletter(){

	// Some AJAX security
	check_ajax_referer('loginizer_admin_ajax', 'nonce');

	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	update_option('loginizer_dismiss_newsletter', time());
	echo 1;
	wp_die();
}

//Export Failed Login Attempts
function loginizer_failed_login_export(){
	
	global $wpdb;
	// Some AJAX security
	check_ajax_referer('loginizer_admin_ajax', 'nonce');
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	$csv_array = lz_selectquery("SELECT * FROM `".$wpdb->prefix."loginizer_logs` ORDER BY `time` DESC", 1);
	$filename = 'loginizer-failed-login-attempts';
	
	if(empty($csv_array)){
		echo -1;
		echo __('No data to export', 'loginizer');
		wp_die();
	}
		
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename='.$filename.'.csv');
	
	$allowed_fields = array('ip' => 'IP', 'attempted_username' => 'Attempted Username', 'last_f_attemp' => 'Last Failed Attempt', 'f_attempts_count' => 'Failed Attempts Count', 'lockouts_count' => 'Lockouts Count', 'url_attacked' => 'URL Attacked');

	$file = fopen("php://output","w");
	
	fputcsv($file, array_values($allowed_fields));
	
	foreach($csv_array as $failed_attempts){
		
		$row = array($failed_attempts['ip'], sanitize_user($failed_attempts['username'], true), date('d/M/Y H:i:s P', $failed_attempts['time']), $failed_attempts['count'], $failed_attempts['lockout'], $failed_attempts['url']);
		fputcsv($file, $row);
	}


	fclose($file);
	
	wp_die();

}

// Export CSV
function loginizer_export(){

	// Some AJAX security
	check_ajax_referer('loginizer_admin_ajax', 'nonce');
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	$lz_csv_type = lz_optpost('lz_csv_type');
	
	switch($lz_csv_type){
		
		case 'blacklist':
		$csv_array = get_option('loginizer_blacklist');
		$filename = 'loginizer-blacklist';
		break;
		
		case 'whitelist':
		$csv_array = get_option('loginizer_whitelist');
		$filename = 'loginizer-whitelist';
		break;
	}
	
	if(empty($csv_array)){
		echo -1;
		echo __('No data to export', 'loginizer');
		wp_die();
	}
		
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename='.$filename.'.csv');
	
	$allowed_fields = array('start' => 'Start IP', 'end' => 'End IP', 'time' => 'Time');

	$file = fopen("php://output","w");
	
	fputcsv($file, array_values($allowed_fields));

	foreach($csv_array as $ik => $iv){
		
		$iv['start'] = $iv['start'];
		$iv['end'] = $iv['end'];
		$iv['time'] = date('d/m/Y', $iv['time']);
		
		$row = array();
		foreach($allowed_fields as $ak => $av){
			$row[$ak] = $iv[$ak];
		}
		
		fputcsv($file, $row);
	}

	fclose($file);
	
	wp_die();
}

function loginizer_social_order(){
	
	// Some AJAX security
	check_ajax_referer('loginizer_social_nonce', 'security');

	if(!current_user_can('manage_options')){
		wp_die(__('Sorry, but you do not have permissions to change settings.', 'loginizer'));
	}
	
	$order = map_deep(map_deep($_POST['order'], 'wp_unslash'), 'sanitize_text_field');

	update_option('loginizer_social_order', array_flip($order));
	
	wp_send_json_success();
	
}

function loginizer_dismiss_license_alert(){
	// Some AJAX security
	check_ajax_referer('loginizer_license_notice', 'security');

	if(!current_user_can('manage_options')){
		wp_die(__('Sorry, but you do not have permissions to change settings.', 'loginizer'));
	}
	
	update_option('loginizer_license_notice', (0 - time()), false);
	die('DONE');
}

function loginizer_dismiss_softwp_alert(){
	// Some AJAX security
	check_ajax_referer('loginizer_softwp_notice', 'security');

	if(!current_user_can('activate_plugins')){
		wp_die(__('Sorry, but you do not have permissions to change settings.', 'loginizer'));
	}

	update_option('loginizer_softwp_upgrade', (0 - time()), false);
	die('DONE');
}

function loginizer_close_update_notice(){

	if(!wp_verify_nonce($_GET['security'], 'loginizer_promo_nonce')){
		wp_send_json_error('Security Check failed!');
	}
	
	if(!current_user_can('manage_options')){
		wp_send_json_error('You don\'t have privilege to close this notice!');
	}
	
	$plugin_update_notice = get_option('softaculous_plugin_update_notice', []);
	$available_update_list = get_site_transient('update_plugins');
	$to_update_plugins = apply_filters('softaculous_plugin_update_notice', []);
	
	if(empty($available_update_list) || empty($available_update_list->response)){
		return;
	}
	
	foreach($to_update_plugins as $plugin_path => $plugin_name){
		if(isset($available_update_list->response[$plugin_path])){
			$plugin_update_notice[$plugin_path] = $available_update_list->response[$plugin_path]->new_version;
		}
	}

	update_option('softaculous_plugin_update_notice', $plugin_update_notice);
}