<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

include_once(LOGINIZER_DIR.'/lib/IPv6/IPv6.php');

// Get the client IP
function _lz_getip(){
	if(isset($_SERVER["REMOTE_ADDR"])){
		return $_SERVER["REMOTE_ADDR"];
	}elseif(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	}elseif(isset($_SERVER["HTTP_CLIENT_IP"])){
		return $_SERVER["HTTP_CLIENT_IP"];
	}
}

// Get the client IP
function lz_getip(){
	
	global $loginizer;
	
	// Just so that we have something
	$ip = _lz_getip();
	
	$loginizer['ip_method'] = (int) @$loginizer['ip_method'];
	
	if(isset($_SERVER["REMOTE_ADDR"])){
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && @$loginizer['ip_method'] == 1){
		if(strpos($_SERVER["HTTP_X_FORWARDED_FOR"], ',')){
			$temp_ip = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
			$ip = trim($temp_ip[0]);
		}else{
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
	}
	
	if(isset($_SERVER["HTTP_CLIENT_IP"]) && @$loginizer['ip_method'] == 2){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}
	
	if(@$loginizer['ip_method'] == 3 && isset($_SERVER[@$loginizer['custom_ip_method']])){
		$ip = $_SERVER[@$loginizer['custom_ip_method']];
	}
	
	// Hacking fix for X-Forwarded-For
	if(!lz_valid_ip($ip)){
		return '';
	}
	
	return $ip;
	
}

// Execute a select query and return an array
function lz_selectquery($query, $array = 0){
	global $wpdb;
	
	$result = $wpdb->get_results($query, 'ARRAY_A');
	
	if(empty($array)){
		return current($result);
	}else{
		return $result;
	}
}

// Check if an IP is valid
function lz_valid_ip($ip){

	if(empty($ip)){
		return false;
	}
	
	// IPv6
	if(lz_valid_ipv6($ip)){
		return true;
	}
	
	// IPv4
	if(!ip2long($ip) || !lz_valid_ipv4($ip)){
		return false;
	}
	
	return true;
}

function lz_valid_ipv4($ip){
	if(!preg_match('/^(\d){1,3}\.(\d){1,3}\.(\d){1,3}\.(\d){1,3}$/is', $ip) || substr_count($ip, '.') != 3){			
		return false;
	}
	
	$r = explode('.', $ip);
	
	foreach($r as $v){
		$v = (int) $v;
		if($v > 255 || $v < 0){
			return false;
		}
	}
	
	return true;
	
}

function lz_valid_ipv6($ip){

	$pattern = '/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/';
	
	if(!preg_match($pattern, $ip)){
		return false;	
	}
	
	return true;
	
}

// Check if a field is posted via POST else return default value
function lz_optpost($name, $default = ''){
	
	if(!empty($_POST[$name])){
		return lz_inputsec(lz_htmlizer(trim($_POST[$name])));
	}
	
	return $default;	
}

// Check if a field is posted via GET else return default value
function lz_optget($name, $default = ''){
	
	if(!empty($_GET[$name])){
		return lz_inputsec(lz_htmlizer(trim($_GET[$name])));
	}
	
	return $default;	
}

// Check if a field is posted via GET or POST else return default value
function lz_optreq($name, $default = ''){
	
	if(!empty($_REQUEST[$name])){
		return lz_inputsec(lz_htmlizer(trim($_REQUEST[$name])));
	}
	
	return $default;	
}

// For filling in posted values
function lz_POSTval($name, $default = ''){
	
	return (!empty($_POST) ? (!isset($_POST[$name]) ? '' : esc_html($_POST[$name])) : $default);

}

function lz_POSTchecked($name, $default = false, $submit_name = ''){
	
	if(!empty($submit_name)){
		$post_to_check = isset($_POST[$submit_name]) ? $_POST[$submit_name] : '';
	}else{
		$post_to_check = $_POST;
	}
	
	return (!empty($post_to_check) ? (isset($_POST[$name]) ? 'checked="checked"' : '') : (!empty($default) ? 'checked="checked"' : ''));

}

function lz_POSTselect($name, $value, $default = false){
	
	if(empty($_POST)){
		if(!empty($default)){
			return 'selected="selected"';
		}
	}else{
		if(isset($_POST[$name])){
			if(trim($_POST[$name]) == $value){
				return 'selected="selected"';
			}
		}
	}

}

function lz_POSTradio($name, $val, $default = null){
	
	return (!empty($_POST) ? (@$_POST[$name] == $val ? 'checked="checked"' : '') : (!is_null($default) && $default == $val ? 'checked="checked"' : ''));

}

function lz_inputsec($string){

	$string = addslashes($string);
	
	// This is to replace ` which can cause the command to be executed in exec()
	$string = str_replace('`', '\`', $string);
	
	return $string;

}

function lz_htmlizer($string){

	$string = htmlentities($string, ENT_QUOTES, 'UTF-8');
	
	preg_match_all('/(&amp;#(\d{1,7}|x[0-9a-fA-F]{1,6});)/', $string, $matches);//r_print($matches);
	
	foreach($matches[1] as $mk => $mv){		
		$tmp_m = lz_entity_check($matches[2][$mk]);
		$string = str_replace($matches[1][$mk], $tmp_m, $string);
	}
	
	return $string;
	
}

function lz_entity_check($string){
	
	//Convert Hexadecimal to Decimal
	$num = ((substr($string, 0, 1) === 'x') ? hexdec(substr($string, 1)) : (int) $string);
	
	//Squares and Spaces - return nothing 
	$string = (($num > 0x10FFFF || ($num >= 0xD800 && $num <= 0xDFFF) || $num < 0x20) ? '' : '&#'.$num.';');
	
	return $string;
			
}

// Check if a checkbox is selected
function lz_is_checked($post){

	if(!empty($_POST[$post])){
		return true;
	}	
	return false;
}

// Reoort an error
function lz_report_error($error = array()){

	if(empty($error)){
		return true;
	}
	
	$error_string = '<b>Please fix the below error(s) :</b> <br />';
	
	foreach($error as $ek => $ev){
		$error_string .= '* '.$ev.'<br />';
	}
	
	echo '<div id="message" class="error"><p>'
					. __($error_string, 'loginizer')
					. '</p></div>';
}

// Report a notice
function lz_report_notice($notice = array()){

	global $wp_version;
	
	if(empty($notice)){
		return true;
	}
	
	// Which class do we have to use ?
	if(version_compare($wp_version, '3.8', '<')){
		$notice_class = 'updated';
	}else{
		$notice_class = 'updated';
	}
	
	$notice_string = '<b>Please check the below notice(s) :</b> <br />';
	
	foreach($notice as $ek => $ev){
		$notice_string .= '* '.$ev.'<br />';
	}
	
	echo '<div id="message" class="'.$notice_class.'"><p>'
					. __($notice_string, 'loginizer')
					. '</p></div>';
}

// Convert an objext to array
function lz_objectToArray($d){
	
	if(is_object($d)){
		$d = get_object_vars($d);
	}
	
	if(is_array($d)){
		return array_map(__FUNCTION__, $d); // recursive
	}elseif(is_object($d)){
		return lz_objectToArray($d);
	}else{
		return $d;
	}
}

// Sanitize variables
function lz_sanitize_variables($variables = array()){
	
	if(is_array($variables)){
		foreach($variables as $k => $v){
			$variables[$k] = trim($v);
			$variables[$k] = escapeshellcmd($v);
		}
	}else{
		$variables = escapeshellcmd(trim($variables));
	}
	
	return $variables;
}

// Is multisite ?
function lz_is_multisite() {
	
	if(function_exists('get_site_option') && function_exists('is_multisite') && is_multisite()){
		return true;
	}
	
	return false;
}

// Generate a random string
function lz_RandomString($length = 10){
	$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0; $i < $length; $i++){
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function lz_print($array){

	echo '<pre>';
	print_r($array);
	echo '</pre>';

}

function lz_cleanpath($path){
	$path = str_replace('\\\\', '/', $path);
	$path = str_replace('\\', '/', $path);
	$path = str_replace('//', '/', $path);
	return rtrim($path, '/');
}

// Returns the Numeric Value of results Per Page
function lz_get_page($get = 'page', $resperpage = 50){

	$resperpage = (!empty($_REQUEST['reslen']) && is_numeric($_REQUEST['reslen']) ? (int) lz_optreq('reslen') : $resperpage);
	
	if(lz_optget($get)){
		$pg = (int) lz_optget($get);
		$pg = $pg - 1;		
		$page = ($pg * $resperpage);
		$page = ($page <= 0 ? 0 : $page);
	}else{	
		$page = 0;		
	}	
	return $page;
}

// Replaces the Variables with the supplied ones
function lz_lang_vars_name($str, $array){
	
	foreach($array as $k => $v){
		
		$str = str_replace('$'.$k, $v, $str);
	
	}
	
	return $str;

}

// Check if Loginizer is premium
function loginizer_is_premium(){
	return defined('LOGINIZER_PREMIUM');
}

function loginizer_feature_available($feature = '', $return = 0){
	
	if(loginizer_is_premium()){
		return true;
	}
	
	$msg = '';
	
	if(!empty($feature)){
		$msg .= '<b>'.$feature.'</b> is available in the Pro version of Loginizer. ';
	}
	
	$msg .= '<a href="'.LOGINIZER_PRICING_URL.'" target="_blank" style="text-decoration:none; color:green;"><b>Upgrade to Pro</b></a>';
	
	if(!empty($return)){
		return $msg;
	}else{
		echo '<div style="color:#a94442; background-color:#f2dede; border-color:#ebccd1; padding:15px; margin-bottom:20px; border:1px solid transparent; border-radius:4px;">'.$msg.'</div>';
	}

}

// Checks if the email is valid
function lz_valid_email($email){

	return filter_var($email, FILTER_VALIDATE_EMAIL);

}

function loginizer_blocked_page($lz_error){

	// We are checking if this is a login page or not
	if(false === stripos(wp_login_url(), $_SERVER['REQUEST_URI'])){
		return;
	}

	echo '<!DOCTYPE html><html><head>
		<meta name="robots" content="noindex, noarchive">
		<meta name="referrer" content="strict-origin-when-cross-origin">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>'.__('You can not access this page', 'loginizer').'</title>
		<style>html,body{height:100%}
		.loginizer-blocked-wrapper{display:flex; align-items:center; justify-content:space-between; flex-direction:column; height:100%;} footer{border-top:1px solid #dadada; width:40vh; text-align:center;}</style>
		</head>
		<body style="font-family:sans-serif; font-size:calc(15px + 0.390625vw);margin:0;">
			<div class="loginizer-blocked-wrapper">
			<div style="margin-top:10em;">
				<h1 align="center">'.__('You can not access this page', 'loginizer').'</h1>
				<p align="center">'.wp_kses_post(implode('', $lz_error)).'</p>
			</div>
		<footer>';
		if(!defined('LOGINIZER_PREMIUM')){
			echo '<p>Powered by Loginizer</p>';
		} else {
			echo '<p>Powered by ' . esc_html(get_bloginfo('name')) . '</p>';
		}
		
		echo '</footer></div></body></html>';
		die();
}

function loginizer_social_css($position = 'below'){
	global $loginizer;

	// Starting the CSS
	$css  = '#lz-social-login-btns{display:none;max-width:350px;width:100%;box-sizing:border-box}.lz-social-login-btns-icon{flex-direction:row;flex-wrap:wrap;gap:8px}.lz-social-login-btns-full{flex-wrap:wrap;}.lz-social-top-padding{padding-top:20px}.lz-social-bottom-padding{padding-bottom:20px}.loginizer-social-button{display:flex;align-items:center;cursor:pointer;margin-bottom:10px;box-sizing:border-box;text-align:center;line-height:1;border:none;outline:none}.lz-social-button-icon{padding:0;width:40px;height:40px}.lz-social-button-icon-circle{border-radius:20px}.lz-social-button-icon-square{border-radius:3px}.lz-social-button-btn{padding:0 12px;width:100%;max-width:400px}.loginizer-social-button img{border-radius:2px;max-width:24px !important;}.loginizer-social-btn-logo{display:flex;padding:8px;align-items:center}.loginizer-social-btn-text{text-align:center;margin-left:15px;padding:10px 0;font-size:16px}';
	
	// CSS for the divider with the text OR in the middle
	if(!empty($loginizer['social_settings']) && ($position === 'below' || $position === 'above')){
		$css .= '.loginizer-social-divider{flex: 1 0 100%; font-size:17px; font-weight:700; margin-bottom:10px; display:flex;align-items:center}.loginizer-social-divider::after,.loginizer-social-divider::before{flex:1;content:"";padding:1px;background-color:#ececec;margin:5px}';
	}
	
	wp_register_style('loginizer-social', '', [], LOGINIZER_VERSION);
	wp_enqueue_style('loginizer-social');
	wp_add_inline_style('loginizer-social', $css);
}

// Echos the HTML of the Social button
function loginizer_social_btn($return = false, $page_type = 'login', $short_atts = []){
	global $loginizer, $loginizer_login_providers;

	// We don't want to give social login option to blacklisted IP's
	if(loginizer_is_blacklisted() || !loginizer_can_login()){
		return;
	}

	$provider_settings = get_option('loginizer_provider_settings', []);
	$provider_order = get_option('loginizer_social_order', []); // in what order the icons are to be shown

	$providers = [];

	// Sorting according to the saved order of the providers.
	if(!empty($provider_order)){
		foreach($provider_order as $key => $num){
			if(!empty($provider_settings[$key])){
				$providers[$key] = $provider_settings[$key];
			}
		}
	} else {
		$providers = $provider_settings;
	}

	if(empty($providers)){
		return;
	}

	// ------ HTML STARTS HERE -------- //

	$style = 'icon';
	$shape = 'square';
	$position = 'below';
	if(!empty($loginizer['social_settings'])){
		$social_settings = $loginizer['social_settings'];

		// Setting Button Style
		if(!empty($social_settings[$page_type]['button_style']) && $social_settings[$page_type]['button_style'] === 'full'){
			$style = 'full';
		}

		// Setting Button Shape
		if(!empty($social_settings[$page_type]['button_shape']) && $social_settings[$page_type]['button_shape'] !== 'square'){
			$shape = $social_settings[$page_type]['button_shape'];
		}

		// Setting position
		if(!empty($social_settings[$page_type]['button_position']) && $social_settings[$page_type]['button_position'] !== 'below'){
			$position = $social_settings[$page_type]['button_position'];
		}
	}
	
	$container_alignment = isset($loginizer['social_settings'][$page_type]['alignment']) ? $loginizer['social_settings'][$page_type]['alignment'] : 'auto';
	$button_alignment = isset($loginizer['social_settings'][$page_type]['button_alignment']) ? $loginizer['social_settings'][$page_type]['button_alignment'] : 'center';
	
	// Shortcode style
	if(!empty($short_atts['type'])){
		$style = esc_html($short_atts['type']);
	}

	// Shortcode Shape square or circle
	if(!empty($short_atts['shape'])){
		$shape = esc_html($short_atts['shape']);
	}
	
	// Shortcode for Container alignment
	if(!empty($short_atts['container_alignment'])){
		$container_alignment = esc_html($short_atts['container_alignment']);
	}
	
	// Shortcode for Button alignment
	if(!empty($short_atts['button_alignment'])){
		$button_alignment = esc_html($short_atts['button_alignment']);
	}

	// Setting divider position
	$divider_pos = '';
	if(!empty($short_atts['divider']) && $short_atts['divider'] == 'above'){
		$divider_pos = 'above';
	}elseif(empty($short_atts) && !empty($loginizer['social_settings']) && $position === 'below_plus'){
		$divider_pos = 'above';
	}elseif(!empty($short_atts['divider']) && $short_atts['divider'] == 'below'){
		$divider_pos = 'below';
	}elseif(empty($short_atts) && !empty($loginizer['social_settings']) && $position === 'above_plus'){
		$divider_pos = 'below';
	}

	loginizer_social_css($divider_pos);

	$padding = [
		'above' => 'bottom',
		'below' => 'top'
	];

	$social_buttons = '<div id="lz-social-login-btns" class="'.($style == 'icon' ? 'lz-social-login-btns-icon' : 'lz-social-login-btns-full').' '.($position == 'above' || $position == 'below' ? 'lz-social-'.$padding[$position].'-padding' : '').'" style="justify-self:'.esc_attr($container_alignment).'; justify-content:'.esc_attr($button_alignment).';">';

	// HTML of the divider in case the buttons are below the login fields
	if(!empty($divider_pos) && $divider_pos == 'above'){
		$social_buttons .= '<div class="loginizer-social-divider">OR</div><br>';
	}

	include_once LOGINIZER_DIR . '/main/login-providers.php';

	foreach($providers as $provider => $settings){
		if(empty($settings['enabled']) || empty($settings['tested'])){
			continue;
		}

		// Filters Pro Only Auth
		if(!empty($loginizer_login_providers[$provider]['premium']) && !defined('LOGINIZER_PREMIUM')){
			continue;
		}

		$name = $loginizer_login_providers[$provider]['name'];
		$color = $loginizer_login_providers[$provider]['color'];

		$btn_style = 'default';
		if(!empty($provider_settings[$provider]['button_style'])){
			$btn_style = $provider_settings[$provider]['button_style'];
		}
		
		// TODO:: Improve the comment here
		// The image is named based on button style as Facebook has different images for different style
		// And if some more social options are added they could have the same behaviour too.
		$img_url = LOGINIZER_URL .'/assets/images/social/'.$provider.'.png';
		if('default' !== $btn_style){
			if(file_exists(LOGINIZER_DIR .'/assets/images/social/'.$provider.'-'.$btn_style.'.png')){
				$img_url = LOGINIZER_URL .'/assets/images/social/'.$provider.'-'.$btn_style.'.png';
			}
		}

		$social_buttons .= '<div class="loginizer-social-button '.($style == 'icon' ? 'lz-social-button-icon' : 'lz-social-button-btn').' '.($shape == 'circle' ? 'lz-social-button-icon-circle' : 'lz-social-button-icon-square').'" onclick="loginizer_auth_popup(\''.esc_js($provider).'\', '.esc_js(!empty($settings['loginizer_social_key']) ? true : 0).')" style="'.((!empty($loginizer_login_providers[$provider]['styles']) && !empty($loginizer_login_providers[$provider]['styles'][$btn_style])) ? esc_attr($loginizer_login_providers[$provider]['styles'][$btn_style]) : esc_attr('background-color:'. esc_attr($color)). '; color:#FFF;') .'">
		<div class="loginizer-social-btn-logo">
			<img src="'.esc_url($img_url).'" height="24" alt="'.esc_attr($name).' Icon"/>
		</div>';

		// Button text in case of full button
		if(!empty($loginizer['social_settings']) && $style === 'full'){
			$social_buttons .= '<div class="loginizer-social-btn-text">'.esc_html__('Login With', 'loginizer').' <strong>'.esc_html($name).'</strong>
		</div>';
		}

		$social_buttons .= '</div>';
	}

	if(empty($social_buttons)){
		$social_buttons .= esc_html__('No Auth Provider configured', 'loginizer');
	}

	// HTML of the divider in case the buttons are above the login fields
	if(!empty($divider_pos) && $divider_pos == 'below'){
		$social_buttons .= '<br><div class="loginizer-social-divider">OR</div>';
	}

	$social_buttons .= '</div>';
	loginizer_add_social_js($page_type);
	
	if($return){
		return $social_buttons;
	}

	echo wp_kses($social_buttons, [
		'div' => ['class' => true, 'id' => true, 'style' => true, 'onclick' => true],
		'img' => ['src' => true, 'height' => true, 'alt' => true, 'width' => true],
		'strong' => true,
	]);
}

function loginizer_add_social_js($page_type){
	global $loginizer;

	if(wp_script_is('loginizer-social-js') || loginizer_is_blacklisted() || !empty($loginizer['social_script_added'])){
		return;
	}

	$loginizer['social_script_added'] = false;
	$func = 'append';
	if(!empty($loginizer['social_settings']) && !empty($loginizer['social_settings'][$page_type]['button_position']) && strpos($loginizer['social_settings'][$page_type]['button_position'], 'above') !== FALSE){
		$func = 'prepend';
	}

	$target_window = 'same';
	if(isset($_GET['interim-login']) && $_GET['interim-login'] == 1){
		$target_window = 'popup';
	} elseif(!empty($loginizer['social_settings']['general']['target_window'])){
		$target_window = $loginizer['social_settings']['general']['target_window'];
	}

	$social_nonce = wp_create_nonce('loginizer_social_check');

	wp_register_script('loginizer-social-js', '', ['jquery'], LOGINIZER_VERSION, ['strategy' => false, 'in_footer' => true]);
	wp_enqueue_script('loginizer-social-js');
	wp_add_inline_script('loginizer-social-js', '
	
		

		let lz_form = document.querySelectorAll("#loginform, #registerform, .woocommerce-form-login, .woocommerce-form-register, #front-login-form, #setupform"),
		lz_social_btns = document.querySelectorAll("#lz-social-login-btns"),
		lz_target_window = "'.esc_html($target_window).'",
		lz_is_interim = "'.(!empty($_GET['interim-login']) ? '&interim-login=0' : '').'"; // as for interim it should only open a popup
		
		if(lz_target_window == "same"){
			let loader = "<style>#loginizer-social-loader-wrap{display:none; align-items:center; justify-content:center; position:fixed; top:0; left:0; width:100%; height:100vh; background-color:rgba(0, 0, 0, 0.2);} .loginizer-social-loader{width:48px;height:48px;border:5px solid #fff;border-bottom-color:transparent;border-radius:50%;display:inline-block;box-sizing:border-box;animation:1s linear infinite rotation}@keyframes rotation{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style>";
			loader += "<div id=\"loginizer-social-loader-wrap\"><div class=\"loginizer-social-loader\"></div></div>";

			jQuery("body").append(loader);
		}
		

		lz_social_btns.forEach((btns) => {
			btns.style.display="flex";
		});
		
		if(lz_form.length > 0){
			lz_form.forEach((form, index) => {
				if (lz_social_btns[index]) {
					lz_social_btns[index].style.display="flex";
					form.'.esc_html($func).'(lz_social_btns[index]);
				}
			});
		}

		function loginizer_auth_popup(provider, isAPI) {
			let target_url = "'.esc_url(wp_login_url()).'?social_security='.esc_html($social_nonce).'&lz_social_provider=" + provider+lz_is_interim;

			if(isAPI){
				target_url += "&lz_api=1";
			}
			
			let redirect_to = "'.(!empty($_GET['redirect_to']) ? wp_validate_redirect(esc_url($_GET['redirect_to'])) : '') .'";
			
			if(redirect_to.length){
				target_url += "&ref="+redirect_to;
			} else {
				target_url += "&ref='.esc_url((is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']).'"
			}

			if(lz_target_window == "same"){
				jQuery("#loginizer-social-loader-wrap").css("display", "flex");
				
				window.location.href = target_url;

				return;
			}

			let screen_x = window.screenX !== undefined ? window.screenX : window.screenLeft,
			screen_y = window.screenY !== undefined ? window.screenY : window.screenTop,
			outer_width = window.outerWidth !== undefined ? window.outerWidth : document.documentElement.clientWidth,
			outer_height = window.outerHeight !== undefined ? window.outerHeight : document.documentElement.clientHeight - 22,
			target_width = 600,
			target_height = 600,
			right = parseInt(screen_y + (outerHeight - target_height) / 2.5, 10),
			left = parseInt(screen_x + (outerWidth - target_width) / 2, 10),
			attributes = [];

			if(target_width !== null){
				attributes.push("width=" + target_width);
			}
			if(target_height !== null){
				attributes.push("height=" + target_height);
			}
			attributes.push("left=" + left);
			attributes.push("top=" + right);
			attributes.push("scrollbars=1");

			var auth_window = window.open(target_url, "authWindow", attributes.join(","));

			if(window.focus){
				auth_window.focus();
			}
			return false;
		}'
	);

	$loginizer['social_script_added'] = true;

}

function loginizer_social_btn_login($return = false, $id = ''){
	loginizer_social_btn($return, 'login');
}

function loginizer_get_social_error(){
	global $loginizer;

	// If we already have the error set
	if(!empty($loginizer['social_errors'])){
		return;
	}

	if(empty($_COOKIE) || empty($_COOKIE['lz_social_error'])){
		return;
	}

	$error_identifier = sanitize_text_field(wp_unslash($_COOKIE['lz_social_error']));
	$social_errors = get_site_transient($error_identifier);

	if(empty($social_errors['errors'])){
		return;
	}

	// Setting error data
	$loginizer['retries_left'] = $social_errors['retries_left'];
	$loginizer['social_errors'] = $social_errors['errors'];

	// Cleaning the error data
	delete_site_transient($error_identifier); // Deleting the data
	//setcookie('lz_social_error', '', time() - 300, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true); // have to delete 
}
