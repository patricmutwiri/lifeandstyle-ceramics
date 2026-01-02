<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if(!defined('ABSPATH')) exit;

// Check request method is Head or get 
if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET'){
	return;
}

if(defined('WP_INSTALLING') && WP_INSTALLING){
	return;
}

if(defined('WP_CLI') && WP_CLI){
	return;
}

if(empty($_SERVER['REQUEST_URI']) || empty($_SERVER['HTTP_HOST']) || empty($_SERVER['HTTP_USER_AGENT'])){
    return false;
}

if(preg_match('/(\/){2}$/', $_SERVER['REQUEST_URI'])){
	return false;
}

function speedycache_ac_serve_cache(){

	$ignored_parameters = ['fbclid', 'utm_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_source_platform', 'gclid', 'dclid', 'msclkid', 'ref', 'fbaction_ids', 'fbc', 'fbp', 'clid', 'mc_cid', 'mc_eid', 'hsCtaTracking', 'hsa_cam', 'hsa_grp', 'hsa_mt', 'hsa_src', 'hsa_ad', 'hsa_acc', 'hsa_net', 'hsa_kw', 'test_speedycache'];

	$uri = '';
	$parsed_uri = [];
	$uri = $_SERVER['REQUEST_URI'];
	$uri = urldecode($uri); // Users use other languages to write as well
	$uri = preg_replace('/\.{2,}/', '', $uri); // Cleaning the path

	$parsed_uri = parse_url($uri);
	if(!empty($parsed_uri) && !empty($parsed_uri['query'])){
		parse_str($parsed_uri['query'], $parsed_query);

		foreach($parsed_query as $query => $value){
			if(in_array($query, $ignored_parameters)){
				unset($parsed_query[$query]);
				continue;
			}
		}

		$uri = $parsed_uri['path'] . (!empty($parsed_query) ? '?'.http_build_query($parsed_query) : '');
	}
	
	// We dont know if the site is a /directory based so we just hit and try
	$site_dir = '';

	$path = '';
	if(!empty($parsed_uri['path'])){
		$path = trim($parsed_uri['path'], '/');
	}

	if(strpos($path, '/') !== FALSE){
		$parsed_path = explode('/', $path);
		$site_dir = $parsed_path[0];
	} elseif(!empty($path)){
		$site_dir = $path;
	}

	$config_file = WP_CONTENT_DIR . '/speedycache-config/' . basename($_SERVER['HTTP_HOST']) . '.php';

	if(!file_exists($config_file)){
		$config_file = WP_CONTENT_DIR . '/speedycache-config/' . basename($_SERVER['HTTP_HOST']) . '.'. $site_dir . '.php';
		if(!file_exists($config_file)){
			return;
		}
	}

	if(!file_exists($config_file)){
		return;
	}

	// Accessing the config file
	include_once $config_file;
	
	if(empty($speedycache_ac_config) || !is_array($speedycache_ac_config)){
		return;
	}

	if(empty($speedycache_ac_config['settings']['status'])){
		return;
	}
	
	// Exclude pages|useragent|cookie
	if(speedycache_ac_excludes($speedycache_ac_config)){
		return;
	}

	if(!empty($speedycache_ac_config['user_agents']) && preg_match('/'.preg_quote($speedycache_ac_config['user_agents']).'/', $_SERVER['HTTP_USER_AGENT'])){
		return;
	}
	
	if(empty($speedycache_ac_config['settings']['logged_in_user']) && preg_grep('/^wordpress_logged_in_/i', array_keys($_COOKIE))){
		return false;
	}

	// check comment author
	if(preg_grep('/comment_author_/i', array_keys($_COOKIE))){
		return false;
	}

	$cache_path = WP_CONTENT_DIR.'/cache/speedycache/' . basename($_SERVER['HTTP_HOST']);

	// For the test cache
	if(isset($_GET['test_speedycache'])){
		$cache_path = '/test'. $uri;
	} else if(!empty($speedycache_ac_config['settings']['mobile']) && preg_match('/Mobile|Android|Silk\/|Kindle|BlackBerry|Opera (Mini|Mobi)/i', $_SERVER['HTTP_USER_AGENT'])) {
		// Check for Mobile
		if(!empty($speedycache_ac_config['settings']['mobile_theme'])){
			$cache_path .= '/mobile-cache' . $uri;
		} else {
			return; // If just mobile is enabled then we don't want to show desktop verison of cache on mobile.
		}
	} else {
		// get path of file
		$cache_path .= '/all'. $uri;
	}
	
	$file_name = 'index';
	if(isset($_COOKIE['wcu_current_currency'])){
		$file_name .= '-' . strtolower($_COOKIE['wcu_current_currency']);
		$file_name = preg_replace('/\.{2,}/', '', $file_name); // Cleaning the path
	}
	$file_name .= '.html';

	//check file extension
	$serving_gz = '';
	if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE && !empty($speedycache_ac_config['settings']['gzip']) && @file_exists($cache_path . '/'. $file_name.'.gz')){
		$serving_gz = '.gz';
		
		// We do not want output compression to be enabled if we are gzipping the page.
		if(function_exists('ini_set')){
			ini_set('zlib.output_compression', 0);
		}

		header('Content-Encoding: gzip');
	}

	if(!file_exists($cache_path . '/'.$file_name . $serving_gz)){
		$serving_gz = '';
	}
	
	if(!file_exists($cache_path . '/'.$file_name . $serving_gz)){
		return;
	}

	if(!headers_sent()){
		header('x-speedycache-source: PHP');
	}

	$cache_created_at = filemtime($cache_path. '/'.$file_name . $serving_gz);
	header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $cache_created_at) . ' GMT');

	$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;

	if($if_modified_since === $cache_created_at){
		header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		exit();
	}

	readfile($cache_path. '/'.$file_name . $serving_gz);
	exit();
}

function speedycache_ac_excludes($excludes){
	if(empty($excludes) || !is_array($excludes)){
		return false;
	}

	$preg_match_rule = '';
	$request_url = !empty($_SERVER['REQUEST_URI']) ? urldecode(trim($_SERVER['REQUEST_URI'], '/')) : '';

	foreach($excludes as $key => $value){
		$value['type'] = !empty($value['type']) ? $value['type'] : 'page';

		if(!empty($value['prefix']) && $value['type'] == 'page'){
			$value['content'] = trim($value['content']);
			$value['content'] = trim($value['content'], '/');
			
			if($value['prefix'] == 'exact' && strtolower($value['content']) == strtolower($request_url)){
				return true;
			}else{
				$preg_match_rule = preg_quote($value['content'], '/');

				if($preg_match_rule){
					if(preg_match('/'.$preg_match_rule.'/i', $request_url)){
						return true;
					}
				}
			}
		}else if($value['type'] == 'useragent'){
			if(preg_match('/'.preg_quote($value['content'], '/').'/i', $_SERVER['HTTP_USER_AGENT'])){
				return true;
			}
		}else if($value['type'] == 'cookie'){
			if(isset($_SERVER['HTTP_COOKIE'])){
				if(preg_match('/'.preg_quote($value['content'], '/').'/i', $_SERVER['HTTP_COOKIE'])){
					return true;
				}
			}
		}
	}
}

speedycache_ac_serve_cache();