<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class InstantIndexing{
	
	static function bing_txt_file(){
		global $wp, $siteseo;
		
		$request = home_url($wp->request);
		if(empty($request)){
			return;
		}

		$api_key = $siteseo->instant_settings['instant_indexing_bing_api_key'];
		$api_url = trailingslashit(home_url()) . $api_key . '.txt';
		
		if($request == $api_url){
			header('X-Robots-Tag: noindex');
			header('Content-Type: text/plain');
			status_header(200);

			echo esc_html($api_key);
			die();
		}
	}
	
	static function submit_urls_to_google($urls){
		$access_token = self::get_google_auth_token();

		if(empty($access_token)){
			return;
		}

		$boundary = wp_rand();
		$batch_body = '';
		$options = get_option('siteseo_instant_indexing_option_name');
		$type = !empty($options['instant_indexing_google_action']) ? $options['instant_indexing_google_action'] : 'URL_UPDATED';
		
		switch($type){
			case 'remove_urls':
				$type = 'URL_DELETED';
				break;
			
			case 'update_urls':
				$type = 'URL_UPDATED';
				break;
		}
		
		// Creating a batch request
		foreach($urls as $url){
			$post_data = wp_json_encode(['url' => $url, 'type' => $type]);

			$batch_body .= '--'.$boundary.PHP_EOL;
			$batch_body .= 'Content-Type: application/http'.PHP_EOL;
			$batch_body .= 'Content-Transfer-Encoding: binary'.PHP_EOL;
			$batch_body .= 'Content-ID: <'.esc_url($url).'>'.PHP_EOL.PHP_EOL;
			$batch_body .= 'POST /v3/urlNotifications:publish HTTP/1.1'.PHP_EOL;
			$batch_body .= 'Content-Type: application/json' . PHP_EOL;
			$batch_body .= 'accept: application/json'.PHP_EOL;
			$batch_body .= 'content-length: '.strlen($post_data).PHP_EOL.PHP_EOL;
			$batch_body .= $post_data.PHP_EOL;
		}

		$batch_body .= '--'.$boundary.'--';

		$response = wp_remote_post('https://indexing.googleapis.com/batch', [
			'body' => $batch_body,
			'headers' => [
				'Content-Type' => 'multipart/mixed; boundary='.$boundary,
				'Authorization' => 'Bearer ' . $access_token
			],
			'timeout' => 30
		]);
		
		if(is_wp_error($response)){
			$response = ['error' => $response->get_error_message()];
			return;
		}
		
		$res_code = wp_remote_retrieve_response_code($response);

		if($res_code > 299){
			return $response;
		}
		
		$batch_response = self::parse_batch_response($response);

		$response = [
			'status_code' => wp_remote_retrieve_response_code($response),
			'urls' => $batch_response,
			'error' => is_wp_error($response) ? $response->get_error_message() : null
		];

		return $response;
	}

	static function parse_batch_response(&$response){
		$response_headers = wp_remote_retrieve_headers($response);
		$response_headers = $response_headers->getAll();

		$content_type = $response_headers['content-type'];

		$urls = [];

		$content_type = explode(';', $content_type);
		$boundary = false;
		foreach($content_type as $part){
			$part = explode('=', $part, 2);
			if (isset($part[0]) && 'boundary' == trim($part[0])) {
				$boundary = $part[1];
			}
		}

		$res_body = wp_remote_retrieve_body($response);
		$res_body = str_replace('--'.$boundary.'--', '--'.$boundary, $res_body);
		$batch_responses = explode('--'.$boundary, $res_body);
		$batch_responses = array_filter($batch_responses);

		foreach($batch_responses as $batch_response){
			$batch_response = trim($batch_response);
			if(empty($batch_response)){
				continue;
			}

			$batch_response = explode("\r\n\r\n", $batch_response);

			if(empty($batch_response[2])){
				continue;
			}

			$batch_body = json_decode($batch_response[2], true);

			if(empty($batch_body)){
				continue;
			}

			if(!empty($batch_body['urlNotificationMetadata']) && !empty($batch_body['urlNotificationMetadata']['url'])){
				$urls[] = sanitize_url($batch_body['urlNotificationMetadata']['url']);
			}
		}

		return $urls;
	}
	
	static function get_google_auth_token(){
		global $siteseo;
		
		$endpoint = 'https://oauth2.googleapis.com/token';
		$scope = 'https://www.googleapis.com/auth/indexing';
		
		if(!function_exists('openssl_sign')){
			return false;
		}

		$google_api_data = isset($siteseo->instant_settings['instant_indexing_google_api_key']) ? $siteseo->instant_settings['instant_indexing_google_api_key'] : '';
		
		if(empty($google_api_data)){
			return false;
		}

		$google_api_data = json_decode($google_api_data, true);
		if(empty($google_api_data)){
			return;
		}

		// Header
		$headers = wp_json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
		$headers = base64_encode($headers);

		// Claim Set
		$now = time();
		$claims = wp_json_encode([
			'iss' => $google_api_data['client_email'],
			'scope' => 'https://www.googleapis.com/auth/indexing',
			'aud' => 'https://oauth2.googleapis.com/token',
			'exp' => $now + 3600,
			'iat' => $now
		]);

		$claims = base64_encode($claims);

		// Make sure base64 encoding is URL-safe
		$headers = str_replace(['+', '/', '='], ['-', '_', ''], $headers);
		$claim = str_replace(['+', '/', '='], ['-', '_', ''], $claims);

		// Sign the JWT with the private key
		$signature_input = "$headers.$claim";
		openssl_sign($signature_input, $signature, $google_api_data['private_key'], OPENSSL_ALGO_SHA256);

		$signature = base64_encode($signature);
		$signature = str_replace(['+', '/', '='], ['-', '_', ''], $signature);

		// JWT Token
		$jwt_assertion = "$signature_input.$signature";

		// OAuth2 Request
		$post_data = http_build_query([
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion' => $jwt_assertion
		]);

		$response = wp_remote_post($endpoint, [
			'body' => $post_data,
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
			]
		]);

		if(is_wp_error($response)){
			return false;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		return isset($body['access_token']) ? $body['access_token'] : false;

	}

	/**
	 * Sends request to index now api with list of urls and key and key location.
	 *
	 * Key location is just the URL to a file which has the same name as the Key
	 * as key.txt and the content in it is the key as well. This is a virtual file.
	 *
	 * @param array $urls List of URLs to submit.
	 * @param string $api_key bing key.
	 *
	 * @return array with 'status_code' and response 'body'
	 */
	static function submit_urls_to_bing($urls, $api_key){
        	$host = wp_parse_url(home_url(), PHP_URL_HOST);
		$key_location = trailingslashit(home_url()) . $api_key . '.txt';

		$endpoint = 'https://api.indexnow.org/indexnow/';
		$body = wp_json_encode([
		    'host' => $host, 
		    'key' => $api_key,
				'keyLocation' => $key_location,
		    'urlList' => $urls
		]);

		$response = wp_remote_post($endpoint, [
		    'body' => $body,
		    'headers' => [
		        'Content-Type' => 'application/json',
		        'Accept' => 'application/json'
		    ],
		    'timeout' => 30
		]);

		return [
		    'status_code' => wp_remote_retrieve_response_code($response),
		    'body' => wp_remote_retrieve_body($response)
		];
	}
	
	// Sends request to bing when the status of post changes
	// We will only do it only if the post is published.
	static function on_status_change($new_status, $old_status, $post){

		if($new_status == $old_status){
			return;
		}
		
		if($new_status != 'publish'){
			return;
		}
		
		if(empty($post) || empty($post->post_type)){
			return;
		}
		
		$post_type_object = get_post_type_object($post->post_type);
		
		// Need to make sure we submit posts whos post type is public
		if(empty($post_type_object) || empty($post_type_object->public)){
			return;
		}

		$options = get_option('siteseo_instant_indexing_option_name', []);

		$bing_api_key = !empty($options['instant_indexing_bing_api_key']) ? $options['instant_indexing_bing_api_key'] : '';
		$google_api_key = !empty($options['instant_indexing_google_api_key']) ? $options['instant_indexing_google_api_key'] : '';
		$url = get_permalink($post);

		$url_list = [$url];
		$res_google = null;
		$res_bing   = null;
		
		if(!empty($bing_api_key)){
			$res_bing = self::submit_urls_to_bing($url_list, $bing_api_key);
		}
		
		if(!empty($google_api_key)){
			$res_google = self::submit_urls_to_google($url_list);
		}
		
		self::save_index_history($url_list, $res_google, $res_bing , 'auto');
				
	}
	
	static function save_index_history($urls, $google_response, $bing_response, $source){
		global $siteseo;
		
		$history = $siteseo->instant_settings;

		if(!isset($history['indexing_history'])){
			$history['indexing_history'] = [];
		}

		array_unshift($history['indexing_history'], [
			'time' => time(),
			'urls' => $urls,
			'google_status_code' => !empty($google_response['status_code']) ? $google_response['status_code'] : null,
			'bing_status_code' => !empty($bing_response['status_code']) ? $bing_response['status_code'] : null,
			'source' => !empty($source) ? $source : 'manual', // 'manual' or 'auto'
		]);
		
		$history['indexing_history'] = array_slice($history['indexing_history'], 0, 10);

		update_option('siteseo_instant_indexing_option_name', $history);
	}	
}
