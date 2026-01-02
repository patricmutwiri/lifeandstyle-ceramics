<?php

// functions.php is deprecated in favour of a classed based UTIL
// So try not to use any function from this file in any new code,
// as this file will be removed from future versions.

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

// Check if a field is posted via GET else return default value
// Deprecated since 1.2.0 use Util::sanitize_get
function speedycache_optget($name, $default = ''){
	
	if(!empty($_GET[$name])){
		return \SpeedyCache\Util::sanitize_get($name);
	}
	
	return $default;	
}

// Deprecated since 1.2.0 use Util::sanitize_request
function speedycache_optreq($name, $default = ''){

	if(!empty($_REQUEST[$name])){
		return \SpeedyCache\Util::sanitize_request($name);
	}
	
	return $default;	
}

// Deprecated since 1.2.0 use Util::sanitize_server
function speedycache_optserver($index, $default = ''){
	return !empty($index) && !empty($_SERVER[$index]) ? sanitize_text_field(wp_unslash($_SERVER[$index])) : $default;
}

// Check if a field is posted via POST else return default value
// Deprecated since 1.2.0 use Util::sanitize_post
function speedycache_optpost($name, $default = ''){
	
	if(!empty($_POST[$name])){
		return \SpeedyCache\Util::sanitize_post($name);
	}
	
	return $default;	
}

// Deprecated since 1.2.0 nonce is handled on in each ajax function
function speedycache_verify_nonce($nonce, $nonce_name){
	if(!wp_verify_nonce($nonce, $nonce_name)){
		wp_send_json(array('success' => false, 'message' => 'Security check failed'));
	}
}

// Deprecated since 1.2.0 use Util::cache_path
function speedycache_cache_path($loc = ''){
	if(!empty($loc)){
		$loc = trim($loc, '/');
	}
	
	return \SpeedyCache\Util::cache_path($loc);
}

// Checks if the given plugin active
// Deprectaed do not use it
function speedycache_is_plugin_active($plugin){
	return in_array($plugin, (array) get_option('active_plugins', array()), true);
}

// Speculation Rules
function speedycache_speculation_rules_config($config){
	if(null === $config){
		return null;
	}

	global $speedycache;

	$mode = isset($speedycache->options['speculation_mode']) ? $speedycache->options['speculation_mode'] : 'auto';
	$eagerness = isset($speedycache->options['speculation_eagerness']) ? $speedycache->options['speculation_eagerness'] : 'auto';

	// Disbaling Speculation Loading
	if($mode == 'disabled'){
		return null;
	}

	if(!in_array($mode, ['prefetch', 'prerender', 'auto'], true)){
		$mode = 'auto';
	}

	if(!in_array($eagerness, ['eager', 'moderate', 'conservative', 'auto'], true)){
		$eagerness = 'auto';
	}

	return array(
		'mode' => $mode,
		'eagerness' => $eagerness,
	);
}
