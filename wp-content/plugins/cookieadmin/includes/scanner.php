<?php

namespace CookieAdmin;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class Scanner{

	static $home_url;
	static $urls_to_scan = [];
	static $visited_urls = [];
	static $raw_redirect_headers = [];
	static $found_cookies = [];
	static $scan_limit = 10;


	static function start_scan(){

		self::$home_url = get_home_url();
		self::$urls_to_scan[] = self::$home_url;

		while (! empty(self::$urls_to_scan) && count(self::$visited_urls) < self::$scan_limit){
			$url = array_shift(self::$urls_to_scan); // Get the next URL from the queue

			if (in_array($url, self::$visited_urls, true)){
				continue;
			}

			self::scan_single_url($url);
		}

		return self::$found_cookies;
	}


	static function scan_single_url($url){
		
		self::$visited_urls[] = $url;

		$response = wp_remote_get($url, [
			'sslverify' => false,
			'timeout'   => 15,
			'redirection' => 5
		]);

		if (is_wp_error($response)) return;

		if (! empty($response['cookies'])){

			$all_headers = wp_remote_retrieve_headers($response);
			$raw_cookie_headers = isset($all_headers['set-cookie']) ? $all_headers['set-cookie'] : [];

			self::process_and_store_cookies($response['cookies'], $raw_cookie_headers);
		}

		$body = wp_remote_retrieve_body($response);
		if (empty($body)) return;

		$dom = new \DOMDocument();
		@$dom->loadHTML($body, LIBXML_NOERROR | LIBXML_NOWARNING);

		self::find_and_queue_links($dom);
		self::scan_forms_on_page($url, $dom);
	}


	static function scan_forms_on_page($page_url, $dom){

		$forms = $dom->getElementsByTagName('form');

		foreach ($forms as $form){

			$method = strtolower($form->getAttribute('method'));
			if ($method !== 'post'){
				continue;
			}
			
			self::$raw_redirect_headers = [];

			$action_url = $form->getAttribute('action');
			if (empty($action_url) || $action_url[0] === '#'){
				$action_url = $page_url;
			} elseif ($action_url[0] === '/'){
				$action_url = self::$home_url . $action_url;
			}

			$post_data = [];
			$inputs = $form->getElementsByTagName('input');
			foreach ($inputs as $input){
				$name = $input->getAttribute('name');
				$type = strtolower($input->getAttribute('type'));
				if (empty($name)){
					continue;
				}
				$value = $input->getAttribute('value');

				switch ($type){
                case 'text':
                case 'email':
                case 'url':
                case 'password':
                case 'search':
                    // If the value is empty, provide dummy data. Otherwise, use the existing value.
                    if (empty($value)){
                        if($type === 'email') $post_data[$name] = 'scanner@cookieadmin.net';
                        elseif($type === 'url') $post_data[$name] = 'https://cookieadmin.net';
                        else $post_data[$name] = 'Scanner Tester - '.uniqid();
                    } else {
                        $post_data[$name] = $value;
                    }
                    break;
                
                case 'hidden':
                    $post_data[$name] = $value;
                    break;

                case 'checkbox':
                case 'radio':
					if (empty($value)) $post_data[$name] = true;
					break;

                case 'submit':
                    $post_data[$name] = $value;
                    break;
				}
			}

			$textareas = $form->getElementsByTagName('textarea');
			foreach ($textareas as $textarea){
				$name = $textarea->getAttribute('name');
				if(!empty($name) && !isset($post_data[$name])){
					$post_data[$name] = 'This is a test comment from the scanner. - '.uniqid();
				}
			}

			if (!empty($post_data)){

				add_action('requests-before_redirect_check', [self::class, 'capture_redirect_headers'], 10, 1);

				$post_response = wp_remote_post($action_url, [
					'sslverify' => false,
					'body'      => $post_data,
				]);

				remove_action('requests-before_redirect_check', [self::class, 'capture_redirect_headers'], 10);

				$final_headers = wp_remote_retrieve_headers($post_response);
				$final_cookie_headers = isset($final_headers['set-cookie']) ? $final_headers['set-cookie'] : [];
				if (!is_array($final_cookie_headers)) $final_cookie_headers = [$final_cookie_headers];

				$all_raw_cookie_headers = array_merge(self::$raw_redirect_headers, $final_cookie_headers);

				if (!is_wp_error($post_response) && !empty($post_response['cookies'])){
					self::process_and_store_cookies($post_response['cookies'], $all_raw_cookie_headers);
				}

			}
		}
	}


	static function process_and_store_cookies($cookie_objects, $all_raw_headers){

		if(!is_array($all_raw_headers)){
			 $all_raw_headers = [$all_raw_headers];
		}

		foreach ($cookie_objects as $cookie){
			
			if (! isset(self::$found_cookies[$cookie->name])){
				
				$is_secure = false;
				$is_httponly = false;
				$max_age = null;
				$samesite = null;			
			
				foreach ($all_raw_headers as $header_string){
					
					if (!empty($header_string) && strpos(trim($header_string), $cookie->name . '=') === 0){
						
						$is_secure  = preg_match('/\bsecure\b/i', $header_string) === 1;
						$is_httponly = preg_match('/\bhttponly\b/i', $header_string) === 1;
						
						if (preg_match('/;\s*Max-Age\s*=\s*([0-9]+)/i', $header_string, $matches)){
							$max_age = (int) $matches[1];
						}
						
						if (preg_match('/;\s*SameSite\s*=\s*(Strict|Lax|None)/i', $header_string, $matches)){
							$samesite = ucfirst(strtolower($matches[1]));
						}
						break;	
					}
				}
			
				self::$found_cookies[$cookie->name] = [
					'cookie_name' => $cookie->name,
					'expires' => $cookie->expires,
					'path' => $cookie->path,
					'domain' => $cookie->domain,
					'secure' => $is_secure,
					'httponly' => $is_httponly,
					'Max-Age' => $max_age,
					'samesite' => $samesite,
				];
			}
		}
	}


	static function find_and_queue_links($dom){

		$links = $dom->getElementsByTagName('a');

		foreach ($links as $link){
			$href = $link->getAttribute('href');

			if (strpos($href, self::$home_url) === 0 || preg_match('/^\/(?!\/)/', $href)){

				if ($href[0] === '/'){
					$href = self::$home_url . $href;
				}

				if (! in_array($href, self::$visited_urls) && ! in_array($href, self::$urls_to_scan)){
					self::$urls_to_scan[] = $href;
				}
			}
		}
	}
	
	static function capture_redirect_headers($response){
		
		if (is_object($response) && isset($response->headers['set-cookie'])){
			
			$cookies = $response->headers['set-cookie'];
			
			if (! is_array($cookies)){
				$cookies = [$cookies];
			}
			
			self::$raw_redirect_headers = array_merge(self::$raw_redirect_headers, $cookies);
		}
		
		return $response;
	}
}
