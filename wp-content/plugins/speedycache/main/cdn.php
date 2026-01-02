<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class CDN{
	static $allowed_types = [];
	static $cdn_url = '';
	static $specific = [];
	static $excludes = [];
	
	static function rewrite(&$content){
		global $speedycache;

		self::$cdn_url = $speedycache->cdn['cdn_url'];
		self::$allowed_types = $speedycache->cdn['file_types'];
		self::$excludes = $speedycache->cdn['excludekeywords'];
		self::$specific = $speedycache->cdn['keywords'];

		if(empty(self::$cdn_url) || empty(self::$allowed_types)){
			return;
		}

		// Define the patterns to match specific URLs (e.g., images, CSS, JS)
		$base_dir = defined('SITEPAD') ? 'sitepad-data' : 'wp-content';

		if (defined('SITEPAD')){
			global $sitepad;
			$site_inc_url = $sitepad['url'] . '/site-inc';
		} else {
			$site_inc_url = home_url('/wp-includes/');
		}

		$patterns = [
			'/' . preg_quote(home_url("/{$base_dir}/uploads/"), '/') . '([^"\']+)/i',
			'/'.  preg_quote($site_inc_url, '/') . '([^"\']+)/i',
			'/' . preg_quote(home_url("/{$base_dir}/themes/"), '/') . '([^"\']+)/i',
			'/' . preg_quote(defined('SP_PLUGIN_URL') ? SP_PLUGIN_URL : home_url('/wp-content/plugins/'), '/') . '([^"\']+)/i',
			'/' . preg_quote(home_url("/{$base_dir}/cache/"), '/') . '([^"\']+)/i',
		];

		// Loop through each pattern and replace only URLs with the specified file types
		foreach($patterns as $pattern){
			$content = preg_replace_callback($pattern, '\SpeedyCache\CDN::replace_urls', $content);
		}
	}
	
	static function replace_urls($matches) {
		global $speedycache;

		// Get the file extension
		$file_url = preg_replace('/\?.*$/', '', $matches[0]);

		if(empty($file_url)){
			$file_url = $matches[0];
		}
		
		$file_url = trim($file_url, '/');

		if(self::is_excluded($file_url)){
			return $matches[0];
		}
		
		// To rewrite just some specific files only
		if(!empty(self::$specific) && is_array(self::$specific)){
			$is_specific = false;

			foreach(self::$specific as $required_source){
				if(preg_match('/'.preg_quote($required_source).'/i', $file_url)){
					$is_specific = true;
					break;
				}
			}

			if(empty($is_specific)){
				return $matches[0];
			}	
		}
		
		$file_extension = self::get_file_extension($file_url);
		
		// Check if the file extension is in the allowed list
		if(in_array(strtolower($file_extension), self::$allowed_types)){
			$home_url = home_url();

			// Rewrite the URL to use the CDN
			return str_replace($home_url, self::$cdn_url, $matches[0]);
		}

		// If not in the allowed list, return the original URL
		return $matches[0];
	}
	
	static function get_file_extension($url) {
		
		$url = strtok($url, ' ');
		$url = strtok($url, '?');
		
		return strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
	}
	
	static function is_excluded($url){

		if(empty($url)){
			return false;
		}
		
		// array check if just to make sure things dont break for 1.2.0 in which we rewrote the plugin
		if(empty(self::$excludes) || !is_array(self::$excludes)){
			return false;
		}
		
		foreach(self::$excludes as $exclude){
			if(preg_match('/'.preg_quote($exclude).'/i', $url)){				
				return true;
			}
		}

		return false;	
	}
	
	static function purge(){
		global $speedycache;

		// Only cloudflare and Bunny can be purged, that too we only want that to happen if CDN is enabled.
		if(
			empty($speedycache->cdn['enabled']) || 
			empty($speedycache->cdn['cdn_key']) || 
			empty($speedycache->cdn['cdn_type']) || 
			$speedycache->cdn['cdn_type'] == 'other'
		){
			return;
		}

		if($speedycache->cdn['cdn_type'] == 'bunny'){
			self::purge_bunny($speedycache->cdn);
		}elseif($speedycache->cdn['cdn_type'] == 'cloudflare'){
			self::purge_cloudflare($speedycache->cdn);
		}
		
	}
	
	// Get unique pull ID to purge cache on CDN 
	static function bunny_get_pull_id(&$cdn){
		global $speedycache;

		$pull_zone = $cdn['cdn_url']; // bunny cdn calls it cdn url as pull zone
		$access_key = $cdn['cdn_key'];
		
		if(empty($access_key)){
			return array('success' => false, 'message' => __('Bunny CDN Access Key not found', 'speedycache'));
		}
		
		$options = array(
			'headers' => array(
				'AccessKey' => $access_key,
				'accept' => 'application/json'
			)
		);

		$res = wp_remote_get('https://api.bunny.net/pullzone', $options);

		if(is_wp_error($res) || empty($res)){
			if(empty($res)){
				return array('success' => false, 'message' => __('Bunny CDN retuned an empty response', 'speedycache'));
			}
			
			return array('success' => false, 'message' => 'Something Went Wrong: ' . $res->get_error_message());
		}
		
		$res_code = wp_remote_retrieve_response_code($res);
		
		if(substr($res_code, 0, 1) != 2){
			return array('success' => false, 'message' => __('Something Went Wrong: Getting Pull ID was unsuccessful ', 'speedycache') . $res_code);
		}
		
		$res_body = wp_remote_retrieve_body($res);
		
		if(empty($res_body)){
			return array('success' => false, 'message' => __('Bunny CDN pull ID response body is empty', 'speedycache'));
		}

		$res_body = json_decode($res_body, true);
		
		foreach($res_body as $pull_zones){
			if($pull_zones['OriginUrl'] == $cdn['origin_url']){
				return $pull_zones['Id'];
			}
		}

		return array('success' => false, 'message' => __('Bunny Pull Zone not found', 'speedycache'));
	}
	
	static function purge_bunny($cdn){

		if(empty($cdn['cdn_key']) || empty($cdn['cdn_url'])){
			return false;
		}

		$pull_zone = $cdn['cdn_url']; // bunny cdn calls it cdn url as pull zone
		$access_key = $cdn['cdn_key'];
		$pull_id = !empty($cdn['bunny_pull_id']) ? $cdn['bunny_pull_id'] : '';

		if(empty($access_key) || empty($pull_id)){
			return false;
		}

		$options = array(
			'headers' => array(
				'AccessKey' => $access_key,
				'content-type' => 'application/json'
			)
		);

		$res = wp_remote_post('https://api.bunny.net/pullzone/'.$pull_id.'/purgeCache', $options);
		
		if(is_wp_error($res) || empty($res)){
			if(empty($res)){
				return __('Bunny CDN retuned an empty response', 'speedycache');
			}
			
			return 'Something Went Wrong: ' . $res->get_error_message();
		}

		$res_code = wp_remote_retrieve_response_code($res);
		
		if($res_code != 204){
			return esc_html__('Something Went Wrong: Purge was unsuccessful with response code of ', 'speedycache') . $res_code;
		}

		return esc_html__('Success: Bunny CDN purged successfully', 'speedycache');

	}
	
	static function cloudflare_zone_id(&$cdn){
		
		if(empty($cdn['cdn_key'])){
			return false;
		}

		$api_token = $cdn['cdn_key'];
		$domain = parse_url(home_url(), PHP_URL_HOST);

		$url = 'https://api.cloudflare.com/client/v4/zones?name='.$domain;

		$args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
			],
		];

		$response = wp_remote_get($url, $args);

		if (is_wp_error($response)) {
			return 'Error: ' . $response->get_error_message();
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if($body && isset($body['result'][0]['id'])){
			return $body['result'][0]['id']; // This is the Zone ID
		}
		
		return false;
	}
	
	static function purge_cloudflare($cdn){
		
		if(empty($cdn['cloudflare_zone_id']) || empty($cdn['cdn_key'])){
			return;
		}
		
		$zone_id = $cdn['cloudflare_zone_id'];
		$api_token = $cdn['cdn_key'];

		$url = 'https://api.cloudflare.com/client/v4/zones/'.$zone_id.'/purge_cache';

		$args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			],
			'body' => json_encode([
				'purge_everything' => true, // Set to true to purge all cache
			]),
		];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response)) {
			return 'Error: ' . $response->get_error_message();
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if ($body && isset($body['success']) && $body['success'] === true) {
			return esc_html__('Cloudflare cache purged successfully.', 'speedycache');
		}

		return 'Failed to purge Cloudflare cache. ' . (isset($body['errors'][0]['message']) ? $body['errors'][0]['message'] : '');
	}

	// Users can add a custom CDN URL in Head Tag
	static function cdn_preconnect(){
		global $speedycache;

		if(empty($speedycache->options['status']) || empty($speedycache->cdn['enabled']) || empty($speedycache->cdn['cdn_url'])){
			return;
		}
		
		if($speedycache->cdn['cdn_type'] == 'other' || $speedycache->cdn['cdn_type'] == 'bunny'){
			echo '<link rel="preconnect" href="'. esc_url($speedycache->cdn['cdn_url']) .'" crossorigin="anonymous">';
		}
	}
	
}
