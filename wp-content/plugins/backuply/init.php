<?php
/*
* BACKUPLY
* https://backuply.com
* (c) Backuply Team
*/

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

define('BACKUPLY_VERSION', '1.5.1');
define('BACKUPLY_DIR', dirname(BACKUPLY_FILE));
define('BACKUPLY_URL', plugins_url('', BACKUPLY_FILE));
define('BACKUPLY_BACKUP_DIR', str_replace('\\' , '/', WP_CONTENT_DIR).'/backuply/');
define('BACKUPLY_TIMEOUT_TIME', 300);
define('BACKUPLY_DEV', file_exists(dirname(__FILE__).'/DEV.php') ? 1 : 0);

if(BACKUPLY_DEV){
	include_once BACKUPLY_DIR.'/DEV.php';
}

// Some other constants
if(!defined('BACKUPLY_API')){
	define('BACKUPLY_API', 'https://api.backuply.com');
}

define('BACKUPLY_DOCS', 'https://backuply.com/docs/');
define('BACKUPLY_WWW_URL', 'https://backuply.com/');
define('BACKUPLY_PRO_URL', 'https://backuply.com/pricing?from=plugin');

include_once(BACKUPLY_DIR.'/functions.php');

function backuply_died() {
	//backuply_log(serialize(error_get_last()));
	
	$last_error = error_get_last();

	if(!$last_error){
		return false;
	}

	// To show the memory limit error.
	if(!empty($last_error['message']) && strpos($last_error['message'], 'Allowed memory size') !== FALSE){
		backuply_status_log($last_error['message'] . ' you can solve this issue by increasing PHP memory limit', 'error');
	}
	
	// To show maximum time out error.
	if(!empty($last_error['message']) && strpos($last_error['message'], 'Maximum execution time') !== FALSE){
		backuply_status_log($last_error['message'] . ' you can solve this issue by increasing PHP max_execution_time', 'error');
		backuply_kill_process();
	}

	if(!empty($last_error['message']) && !empty($last_error['types']) && $last_error['types'] == 1){
		backuply_status_log($last_error['message'] . ' ' . $last_error['line'] . ' ' .$last_error['file'] . '', 'warning');
	}

}
register_shutdown_function('backuply_died');

// Ok so we are now ready to go
register_activation_hook(BACKUPLY_FILE, 'backuply_activation');

// Is called when the ADMIN enables the plugin
function backuply_activation(){
	global $wpdb, $error;

	update_option('backuply_version', BACKUPLY_VERSION);
	
	backuply_create_backup_folders();
	backuply_add_htaccess();
	backuply_add_web_config();
	backuply_add_index_files();
	backuply_set_config();
	backuply_set_status_key();
	backuply_add_litespeed_noabort();
}

// The function that will be called when the plugin is loaded
add_action('plugins_loaded', 'backuply_load_plugin');

function backuply_load_plugin(){
	global $backuply;
	
	// Set the array
	if(empty($backuply)){
		$backuply = array();
	}

	$backuply['settings'] = get_option('backuply_settings', []);
	$backuply['cron'] = get_option('backuply_cron_settings', []);
	$backuply['auto_backup'] = false;

	if(!defined('BACKUPLY_PRO')){
		$backuply['license'] = get_option('backuply_license', []);
	}

	$backuply['status'] = get_option('backuply_status');
	$backuply['excludes'] = get_option('backuply_excludes');
	$backuply['htaccess_error'] = true;
	$backuply['index_html_error'] = true;
	$backuply['debug_mode'] = !empty(get_option('backuply_debug')) ? true : false;
	$backuply['bcloud_key'] = get_option('bcloud_key', '');
	
	backuply_update_check();
	
	if(!defined('BACKUPLY_PRO') && !empty($backuply['bcloud_key'])){
		include_once BACKUPLY_DIR . '/main/bcloud-cron.php';
	}
	
	add_action('init', 'backuply_handle_self_call'); // To make sure all plugins are loaded.
	add_action('backuply_clean_tmp', 'backuply_delete_tmp'); // This is for daily cleaning of backups folder.
	add_action('backuply_update_quota', 'backuply_schedule_quota_updation'); // This is for scheduled quota updation
	
	if(file_exists(BACKUPLY_BACKUP_DIR . '.htaccess')) {
		$backuply['htaccess_error'] = false;
	}

	if(file_exists(BACKUPLY_BACKUP_DIR . 'index.html')) {
		$backuply['index_html_error'] = false;
	}

	add_filter('cron_schedules', 'backuply_add_cron_interval');
	
	if(is_admin()){
		include_once BACKUPLY_DIR .'/main/admin.php';
	}

	// Cron for Backing Up Files/Database
	add_action('backuply_backup_cron', 'backuply_backup_execute');
	
	// Cron to check for timeout
	add_action('backuply_timeout_check', 'backuply_timeout_check');
}

// If we are doing ajax and its a backuply ajax
if(wp_doing_ajax()){
	include_once(BACKUPLY_DIR.'/main/ajax.php');
}

/**
  * Looks if Backuply just got updated.
 */
function backuply_update_check(){
	
	$sql = array();
	$current_version = get_option('backuply_version');	
	$version = (int) str_replace('.', '', $current_version);
	
	// No update required
	if($current_version == BACKUPLY_VERSION){
		return true;
	}
	
	// Is it first run ?
	if(empty($current_version)){
		backuply_activation();
		return;
	}
	
	if($version < 108){
		backuply_create_backup_folders();
		backuply_add_web_config();
		backuply_add_index_files();
	}
	
	if($version < 109){
		backuply_update_restore_key();
	}
	
	if($version < 120){
		backuply_keys_to_db();
		
		$cron_settings = get_option('backuply_cron_settings');
		
		// Updates both Backuply key and Restore key if custom cron is not enabled.
		if(!empty($cron_settings) && !empty($cron_settings['backuply_cron_schedule']) && $cron_settings['backuply_cron_schedule'] !== 'custom'){
			backuply_set_config();
		}
	}
	
	// Save the new Version
	update_option('backuply_version', BACKUPLY_VERSION);
	backuply_set_status_key(); // We will update the status key on every update to decrease chances of it being stolen.
	
}

// List of core files to backup
function backuply_core_fileindex(){
	$default_fileindex = array('index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-admin', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config-sample.php', 'wp-content', 'wp-cron.php', 'wp-includes', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php', '.htaccess', 'wp-config.php');

	return $default_fileindex;
}

// Cron Schedules for WordPress cron
function backuply_add_cron_interval($schedules){
	// 30 Min
	$schedules['backuply_thirty_min'] = array(
		'interval' => 1800,
		'display'  => esc_html__( 'Every 30 Minutes' )
	);
	
	$schedules['backuply_one_hour'] = array(
		'interval' => 3600,
		'display'  => esc_html__( 'Every One Hour' )
	);

	$schedules['backuply_two_hours'] = array(
		'interval' => 7200,
		'display'  => esc_html__( 'Every Two Hours' )
	);
	
	$schedules['backuply_daily'] = array(
		'interval' => 86400,
		'display'  => esc_html__( 'Once a day' )
	);

	$schedules['backuply_weekly'] = array(
		'interval' => 604800,
		'display' => esc_html__('Once a Week')
	);
	
	$schedules['backuply_monthly'] = array(
		'interval' => 2635200,
		'display' => esc_html__('Once a month')
	);
	
	return $schedules;
}

// Initiates the backup
function backuply_backup_execute(){
	global $wpdb, $backuply, $data;
	
	// Updates the $backuply['status'] var
	$is_active = backuply_active();
	
	if(empty($backuply['status'])){
		return;
	}

	// Update the last active time
	$backuply['status']['last_update'] = time();
	update_option('backuply_status', $backuply['status']);
	
	// Informaton regarding remote location
	$remote_location = '';

	if(!empty($backuply['status']['backup_location'])){
		$backuply_remote_backup_locs = get_option('backuply_remote_backup_locs');
		$backup_location_id = $backuply['status']['backup_location'];
		$remote_location = $backuply_remote_backup_locs[$backup_location_id];
	}

	include(BACKUPLY_DIR.'/backup_ins.php');
	
}

function backuply_handle_self_call(){
	// CURL call for bacukup when its incomplete
	if(isset($_GET['action'])  && ($_GET['action'] === 'backuply_curl_backup' || $_GET['action'] === 'backuply_curl_upload')) {

		if(!wp_verify_nonce(backuply_optreq('security'), 'backuply_nonce')){
			backuply_status_log('Security Check Failed', 'error');
			die();
		}

		backuply_backup_execute();
		wp_send_json(array('success' => true));
	}
}


// Sorry to see you going
register_uninstall_hook(BACKUPLY_FILE, 'backuply_deactivation');

function backuply_deactivation(){	
	delete_option('backuply_version');
	delete_option('backuply_cron_schedules');
	delete_option('backuply_cron_settings');
	delete_option('backuply_remote_backup_locs');
	delete_option('backuply_notify_email_address');
	delete_option('backuply_settings');
	delete_option('backuply_license');
	delete_option('backuply_hide_trial');
	delete_option('backuply_promo_time');
	delete_option('backuply_backup_stopped');
	delete_option('backuply_last_restore');
	delete_option('backuply_last_backup');
	delete_option('backuply_hide_holiday');
	delete_option('backuply_excludes');
	delete_option('backuply_black_friday');
	delete_option('backuply_debug');
	delete_option('external_updates-backuply-pro');
	delete_option('backuply_offer_time');	
	delete_option('backuply_backup_nag');
	delete_option('backuply_config_keys');
	
	// Cleaning all the cron events
	wp_clear_scheduled_hook('backuply_clean_tmp');
	wp_clear_scheduled_hook('backuply_timeout_check');
}
