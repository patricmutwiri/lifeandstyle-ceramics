<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

//ABSPATH is required.	
if(!defined('ABSPATH')) exit;

define('FILEORGANIZER_DIR', dirname( FILEORGANIZER_FILE ));
define('FILEORGANIZER_BASE', plugin_basename(FILEORGANIZER_FILE));
define('FILEORGANIZER_URL', plugins_url('', FILEORGANIZER_FILE));
define('FILEORGANIZER_BASE_NAME', basename(FILEORGANIZER_DIR));
define('FILEORGANIZER_WP_CONTENT_DIR', defined('WP_CONTENT_FOLDERNAME') ? WP_CONTENT_FOLDERNAME : 'wp-content');
define('FILEORGANIZER_DEV', file_exists(dirname(__FILE__).'/dev.php') ? 1 : 0);

function fileorganizer_died(){
	print_r(error_get_last());
}

if(FILEORGANIZER_DEV){
	include_once FILEORGANIZER_DIR.'/DEV.php';
	//register_shutdown_function('fileorganizer_died');
}

if(!class_exists('FileOrganizer')){
class FileOrganizer{
	public $options = array();
	public $default_path = '';
}
}

function fileorganizer_autoloader($class){
	
	if(!preg_match('/^FileOrganizer\\\(.*)/is', $class, $m)){
		return;
	}
	
	// For Free
	if(file_exists(FILEORGANIZER_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(FILEORGANIZER_DIR.'/main/'.strtolower($m[1]).'.php');
	}
	
	// For Pro
	if(defined('FILEORGANIZER_PRO_DIR') && file_exists(FILEORGANIZER_PRO_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(FILEORGANIZER_PRO_DIR.'/main/'.strtolower($m[1]).'.php');
	}
}

spl_autoload_register(__NAMESPACE__.'\fileorganizer_autoloader');

// Ok so we are now ready to go
register_activation_hook( FILEORGANIZER_FILE , 'fileorganizer_activation');

// Is called when the ADMIN enables the plugin
function fileorganizer_activation(){
	global $wpdb;

	$sql = array();

	add_option('fileorganizer_version', FILEORGANIZER_VERSION);

}

// Looks if FileOrganizer just got updated
function fileorganizer_update_check(){

	$sql = array();
	$current_version = get_option('fileorganizer_version');	
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == FILEORGANIZER_VERSION){
		return true;
	}

	// Is it first run ?
	if(empty($current_version)){

		// Reinstall
		fileorganizer_activation();

		// Trick the following if conditions to not run
		$version = (int) str_replace('.', '', FILEORGANIZER_VERSION);

	}

	// Adding index.php to trash folder, if it already exists.
	if(version_compare($current_version, '1.0.7', '<=')){
		$uploads_dir = wp_upload_dir();
		$trash_dir = fileorganizer_cleanpath($uploads_dir['basedir'].'/fileorganizer/.trash');

		if(file_exists($trash_dir)){
			fileorganizer_recursive_indexphp($trash_dir, 8); // Adding index.php files

			$randomness = wp_generate_password(12, false);
			$new_dir_name = $trash_dir . '-' . $randomness;

			rename($trash_dir, $new_dir_name);
		}
	}

	// Save the new Version
	update_option('fileorganizer_version', FILEORGANIZER_VERSION);
	
}

// Creates index.php file recursively
// This is needed only if user upgrades from any version below 1.0.8
// NOTE: So remove when not needed.
function fileorganizer_recursive_indexphp($trash_dir, $depth){

	if($depth <= 0){
		return;
	}

	if(!is_dir($trash_dir)){
		return;
	}

	$sub_dirs = scandir($trash_dir);

	if(empty($sub_dirs)){
		return false;
	}

	foreach($sub_dirs as $file){
		$file_path = $trash_dir . '/' . $file;
		if(!is_dir($file_path) || in_array($file, ['..', '.'])){
			continue;
		}

		$depth--;
		fileorganizer_recursive_indexphp($file_path, $depth);
	}

	if(!file_exists($trash_dir . '/index.php')){
		file_put_contents($trash_dir . '/index.php', '<?php //Silence is golden');
		chmod($trash_dir . '/index.php', 0444);
	}
}

// Add action to load FileOrganizer
add_action('plugins_loaded', 'fileorganizer_load_plugin');
function fileorganizer_load_plugin(){
	global $fileorganizer, $sitepad;
			
	if(empty($fileorganizer)){
		$fileorganizer = new FileOrganizer();
	}
	
	// Check if the installed version is outdated
	fileorganizer_update_check();
	
	$options = get_option('fileorganizer_options');
	$fileorganizer->options = empty($options) ? array() : $options;
	$fileorganizer->default_path = !defined('SITEPAD') ? ABSPATH : $sitepad['path'];
	
	if(!defined('SITEPAD') && is_admin() && !defined('FILEORGANIZER_PRO') && current_user_can('activate_plugins')){
		// The promo time
		$promo_time = get_option('fileorganizer_promo_time');
		if(empty($promo_time)){
			$promo_time = time();
			update_option('fileorganizer_promo_time', $promo_time);
		}

		// Are we to show the FileOrganizer promo
		if(!empty($promo_time) && $promo_time > 0 && $promo_time < (time() - (7 * 86400))){
			add_action('admin_notices', 'fileorganizer_promo');
		}
	}
	
	if(!defined('SITEPAD') && is_admin() && current_user_can('manage_options')){
		// === Plugin Update Notice === //
		$plugin_update_notice = get_option('softaculous_plugin_update_notice', []);
		$available_update_list = get_site_transient('update_plugins'); 
		$plugin_path_slug = 'fileorganizer/fileorganizer.php';

		if(
			!empty($available_update_list) &&
			is_object($available_update_list) && 
			!empty($available_update_list->response) &&
			!empty($available_update_list->response[$plugin_path_slug]) && 
			(empty($plugin_update_notice) || empty($plugin_update_notice[$plugin_path_slug]) || (!empty($plugin_update_notice[$plugin_path_slug]) &&
			version_compare($plugin_update_notice[$plugin_path_slug], $available_update_list->response[$plugin_path_slug]->new_version, '<')))
		){
			add_action('admin_notices', 'fileorganizer_plugin_update_notice_handle');
			add_filter('softaculous_plugin_update_notice', 'fileorganizer_plugin_update_notice_filter');
		}
		// === Plugin Update Notice === //
	}
}

// This adds the left menu in WordPress Admin page
add_action('network_admin_menu', 'fileorganizer_admin_menu', 5);
add_action('admin_menu', 'fileorganizer_admin_menu', 5);
function fileorganizer_admin_menu() {

	global $wp_version;
	
	// TODO : Capability for accessing this page
	$capability = fileorganizer_get_capability();
	$manu_capability = 'manage_options';
	
	if(is_multisite()){
		$manu_capability = 'manage_network_options';
	}

	// Add the menu page
	add_menu_page(__('FILE ORGANIZER'), __('File Organizer'), $capability, 'fileorganizer', 'fileorganizer_page_handler', 'dashicons-category');
	
	// Add Settings Page
	add_submenu_page( 'fileorganizer', __('Settings'), __('Settings'), $manu_capability, 'fileorganizer-settings', 'fileorganizer_settings_handler');
	
	if(defined('FILEORGANIZER_PRO')){

		// Restrictins by user
		add_submenu_page( 'fileorganizer', __('User Restrictions'), __('User Restrictions'), $manu_capability, 'fileorganizer-user-restrictions', 'fileorganizer_restrictions_handler');
		
		// Restrictins by  user role
		add_submenu_page( 'fileorganizer', __('User Role Restrictions'), __('User Role Restrictions'), $manu_capability, 'fileorganizer-user-role-restrictions', 'fileorganizer_role_restrictions_handler');
		
		if(!defined('SITEPAD')){
			// Add License Page
			add_submenu_page( 'fileorganizer', __('License'), __('License'), $manu_capability, 'fileorganizer-license', 'fileorganizer_license_handler');
		}
	}
}

// Register admin style
add_action( 'admin_init', 'fileorganizer_admin_init');
function fileorganizer_admin_init(){
	wp_register_style('forg-admin', FILEORGANIZER_URL .'/css/admin.css', array(), FILEORGANIZER_VERSION);
}

function fileorganizer_page_handler(){
	global $fileorganizer;
	
	// Register scripts
	wp_register_script('forg-elfinder', FILEORGANIZER_URL .'/manager/js/elfinder.min.js', array('jquery', 'jquery-ui-droppable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'jquery-ui-slider', 'jquery-ui-button', 'jquery-ui-sortable','wp-codemirror'), FILEORGANIZER_VERSION);

	// Load Language dynamically
	if(!empty($fileorganizer->options['default_lang']) && $fileorganizer->options['default_lang'] != 'en') {
		wp_register_script( 'forg-lang', FILEORGANIZER_URL .'/manager/js/i18n/elfinder.'.sanitize_file_name($fileorganizer->options['default_lang']).'.js', array('jquery'), FILEORGANIZER_VERSION);
	}

	// Register styles
	wp_register_style('forg-jquery-ui', FILEORGANIZER_URL .'/css/jquery-ui/jquery-ui.css', array(), FILEORGANIZER_VERSION);
	wp_register_style('forg-elfinder', FILEORGANIZER_URL .'/manager/css/elfinder.min.css', array('forg-admin', 'forg-jquery-ui','wp-codemirror'), FILEORGANIZER_VERSION);
	
	// Load theme dynamically
	$theme_path = !empty($fileorganizer->options['theme']) ? '/themes/'.$fileorganizer->options['theme'] : '';	
	wp_register_style('forg-theme', FILEORGANIZER_URL.'/manager'.$theme_path.'/css/theme.css', array(), FILEORGANIZER_VERSION);

	// Include the handler
	include_once (FILEORGANIZER_DIR .'/main/fileorganizer.php');
	
	// Render HTML
	fileorganizer_render_page();
	
}

// Include the setting handler
function fileorganizer_settings_handler(){
	include_once (FILEORGANIZER_DIR .'/main/settings.php');
	fileorganizer_settings_page();
}

function fileorganizer_restrictions_handler(){
	include_once FILEORGANIZER_PRO_DIR .'/main/user_restrictions.php';
	fileorganizer_user_restriction_render();
}

function fileorganizer_plugin_update_notice_filter($plugins = []){
	$plugins['fileorganizer/fileorganizer.php'] = 'FileOrganizer';
	return $plugins;
}

function fileorganizer_plugin_update_notice_handle(){
	if(!function_exists('fileorganizer_plugin_update_notice')){
		include_once(FILEORGANIZER_DIR.'/main/promo.php');		
	}
	
	fileorganizer_plugin_update_notice();
}

function fileorganizer_role_restrictions_handler(){
	include_once FILEORGANIZER_PRO_DIR .'/main/role_restrictions.php';
}

function fileorganizer_license_handler(){
	include_once FILEORGANIZER_PRO_DIR .'/main/license.php';
}

// Check if a field is posted via GET else return default value
function fileorganizer_optget($name, $default = ''){
	
	if(!empty($_GET[$name])){
		return fileorganizer_clean($_GET[$name]);
	}
	
	return $default;	
}

// Check if a field is posted via POST else return default value
function fileorganizer_optpost($name, $default = ''){
	
	if(!empty($_POST[$name])){
		return fileorganizer_clean($_POST[$name]);
	}
	
	return $default;	
}

// Check if a field is posted via REQUEST else return default value
function fileorganizer_optreq($name, $default = ''){
	
	if(!empty($_REQUEST[$name])){
		return fileorganizer_clean($_REQUEST[$name]);
	}
	
	return $default;	
}

function fileorganizer_clean($var){
	
	if(is_array($var) || is_object($var)){
		$var = map_deep($var, 'wp_unslash');
		return map_deep($var, 'sanitize_text_field');
	}
	
	if(is_scalar($var)){
		$var = wp_unslash($var);
		return sanitize_text_field($var);
	}

	return '';

}

function fileorganizer_cleanpath($path){
	$path = str_replace('\\\\', '/', $path);
	$path = str_replace('\\', '/', $path);
	$path = str_replace('//', '/', $path);
	return rtrim($path, '/');
}

function fileorganizer_get_capability(){
	
	$capability = 'activate_plugins';
	
	return apply_filters('fileorganizer_get_capability', $capability);
}

// Load ajax
if(wp_doing_ajax()){
	include_once FILEORGANIZER_DIR . '/main/ajax.php';
}

// Show the promo
function fileorganizer_promo(){
	include_once(FILEORGANIZER_DIR.'/main/promo.php');
	fileorganizer_base_promo();
}

function fileorganizer_notify($message, $type = 'updated', $dismissible = true){
	$is_dismissible = '';
	
	if(!empty($dismissible)){
		$is_dismissible = 'is-dismissible';
	}
	
	if(!empty($message)){
		echo '<div class="'.esc_attr($type).' '.esc_attr($dismissible).' notice">
			<p>'.wp_kses_post($message).'</p>
		</div>';
	}
}

// Check we are outside installtion directory ?
function fileorganizer_validate_path($path) {
	global $fileorganizer;
	
	$currentDirectory = fileorganizer_cleanpath(realpath($fileorganizer->default_path)); // change
	$absolutePath = fileorganizer_cleanpath(realpath($path));
	
	if($currentDirectory === $absolutePath){
		return true;
	}

    return strpos($absolutePath, $currentDirectory) !== false;
}