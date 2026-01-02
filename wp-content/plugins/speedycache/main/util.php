<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Util{
	static function sanitize_get($name, $default = ''){
		if(empty($_GET[$name])){
			return $default;
		}
		
		if(is_array($_GET[$name]) || is_object($_GET[$name])){
			return map_deep(wp_unslash($_GET[$name]), 'sanitize_text_field');
		}

		return sanitize_text_field(wp_unslash($_GET[$name]));
	}

	static function sanitize_post($name, $default = ''){
		if(empty($_POST[$name])){
			return $default;
		}
		
		if(is_array($_POST[$name]) || is_object($_POST[$name])){
			return map_deep(wp_unslash($_POST[$name]), 'sanitize_text_field');
		}

		return sanitize_text_field(wp_unslash($_POST[$name]));
	}

	static function sanitize_request($name, $default = ''){
		if(empty($_REQUEST[$name])){
			return $default;
		}
		
		if(is_array($_REQUEST[$name]) || is_object($_REQUEST[$name])){
			return map_deep(wp_unslash($_REQUEST[$name]), 'sanitize_text_field');
		}

		return sanitize_text_field(wp_unslash($_REQUEST[$name]));
	}
	
	static function sanitize_server($name, $default = ''){
		if(empty($_SERVER[$name])){
			return $default;
		}

		return sanitize_text_field(wp_unslash($_SERVER[$name]));
	}
	
	static function pagespeed_color($score){

		// The structure of this array is 0 => [Stroke Color, Background Color, Text Color]
		$score_color_map = array(
			0 => ['#c00', '#c003', '#c00'], // Red
			50 => ['#fa3', '#ffa50036', '#fa3'],// Orange
			90 => ['#0c6', '#00cc663b', '#080']// Green
		);

		if($score >= 0 && $score < 50){
			return $score_color_map[0];
		}

		if($score >= 50  && $score < 90){
			return $score_color_map[50];
		}

		return $score_color_map[90];
	}
	
	static function url_to_path($url){
		$url = preg_replace('/\?.*/', '', $url); // Removing any query string
		$dir_slug = str_replace(site_url(), '', $url);
		if(defined('SITEPAD')){
			global $sitepad;
			$file_path = $sitepad['path'] . '/' .trim($dir_slug, '/');
		} else {
			$file_path = ABSPATH . trim($dir_slug, '/');
		}
		return wp_normalize_path($file_path);
	}
	
	static function path_to_url($path){
		$path = wp_normalize_path($path);
		if(defined('SITEPAD')){
			global $sitepad;
			$abs_path = wp_normalize_path($sitepad['path']);
		} else {
			$abs_path = wp_normalize_path(ABSPATH);
		}
		$path = str_replace($abs_path, '', $path);
		$url = site_url() . '/' . $path;

		return $url;
	}
	
	static function cache_path($loc = ''){

		if((defined('WP_CLI') && WP_CLI) || empty($_SERVER['HTTP_HOST'])){
			global $blog_id;

			$url = get_option('home');
	
			if(!empty($blog_id) && is_multisite()){
				switch_to_blog($blog_id);
				$url = get_option('home');
				restore_current_blog();
			}

			$url = wp_parse_url($url);			
			$host = $url['host'];

			return trailingslashit(SPEEDYCACHE_CACHE_DIR . '/'.$host.'/'.$loc);
		}
		
		$host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
		return trailingslashit(SPEEDYCACHE_CACHE_DIR . '/'.$host.'/'.$loc);
	}
	
	// Creates a config file based on the URL of the website
	static function set_config_file(){
		global $speedycache;
		
		$export_config['settings']['status'] = !empty($speedycache->options['status']);
		$export_config['settings']['gzip'] = !empty($speedycache->options['gzip']);
		$export_config['settings']['logged_in_user'] = !empty($speedycache->options['logged_in_user']);
		$export_config['settings']['mobile_theme'] = !empty($speedycache->options['mobile_theme']);
		$export_config['settings']['mobile'] = !empty($speedycache->options['mobile']);
		//$export_config['user_agents'] = speedycache_get_excluded_useragent();
		$export_config['excludes'] = get_option('speedycache_exclude', []);

		$config = var_export($export_config, true);

		$url = get_site_url();
		$file = parse_url(untrailingslashit($url));
		$file['path'] = (!empty($file['path'])) ? str_replace( '/', '.', untrailingslashit($file['path'])) : '';
		$config_file_path = WP_CONTENT_DIR .'/speedycache-config/'. strtolower($file['host']) . $file['path'] . '.php';
		
		if(!file_exists(WP_CONTENT_DIR .'/speedycache-config/')){
			if(mkdir(WP_CONTENT_DIR .'/speedycache-config/', 0755, true)){
				touch(WP_CONTENT_DIR .'/speedycache-config/index.html');
				file_put_contents(WP_CONTENT_DIR .'/speedycache-config/.htaccess', 'deny from all');
			}
		}

		$config_temp = file_get_contents(SPEEDYCACHE_DIR . '/assets/config-template.php');
		$config_content = str_replace("'REPLACE_CONFIG'", $config, $config_temp);
		file_put_contents($config_file_path, $config_content);
	}
	
	static function dir_size($dir){
		$size = 0;

		foreach(glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $file){
			$size += is_file($file) ? filesize($file) : self::dir_size($file);
		}

		return $size;
	}
	
	static function cache_lifespan(){
		global $speedycache;

		$schedule_time = 0;

		if(empty($speedycache->options['purge_interval'])){
			return $schedule_time;
		}

		if(!empty($speedycache->options['purge_enable_exact_time']) && !empty($speedycache->options['purge_exact_time'])){
			$schedule_time = DAY_IN_SECONDS;
		} elseif($speedycache->options['purge_interval_unit'] == 'hours'){
			$schedule_time = HOUR_IN_SECONDS * $speedycache->options['purge_interval'];
		} elseif($speedycache->options['purge_interval_unit'] == 'days'){
			$schedule_time = DAY_IN_SECONDS * $speedycache->options['purge_interval'];
		}

		return (int) $schedule_time;
	}
	
	static function lifespan_cron(){
		global $speedycache;
		
		if(empty($speedycache->options['purge_interval'])){
			return;
		}
		
		if(!empty(wp_next_scheduled('speedycache_purge_cache'))){
			return;
		}

		if(!empty($speedycache->options['purge_enable_exact_time']) && !empty($speedycache->options['purge_exact_time'])){
			// Getting the exact time of the user's timezone by using the offset and strtotime return gtm time.
			$future_timestamp = strtotime('today '.$speedycache->options['purge_exact_time']);
			$offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
			$current_time = time() - $offset;
			$future_timestamp -= $offset;

			if(time() > $future_timestamp){
				$future_timestamp = strtotime('tomorrow '.$speedycache->options['purge_exact_time']);
				$future_timestamp -= $offset;
			}

			$schedule_time = $future_timestamp - time();
			
		} elseif($speedycache->options['purge_interval_unit'] == 'hours'){
			$schedule_time = HOUR_IN_SECONDS * $speedycache->options['purge_interval'];
		} elseif($speedycache->options['purge_interval_unit'] == 'days'){
			$schedule_time = DAY_IN_SECONDS * $speedycache->options['purge_interval'];
		}

		wp_schedule_event(time() + $schedule_time, 'speedycache_expired_cache_schedule', 'speedycache_purge_cache');

	}
	
	static function preload_cron(){
		global $speedycache;
		
		if(empty($speedycache->options['preload_interval']) || empty($speedycache->options['preload'])){
			return;
		}
		
		if(wp_next_scheduled('speedycache_preload')){
			return;
		}

		$schedule_time = HOUR_IN_SECONDS * (int) $speedycache->options['preload_interval'];

		wp_schedule_event(time() + $schedule_time, 'speedycache_preload_cache_schedule', 'speedycache_preload');

	}
	
	static function custom_expiry_cron($schedules){
		
		$cache_interval = self::cache_lifespan();
		if(empty($cache_interval)){
			return $schedules;
		}

		$schedules['speedycache_expired_cache_schedule'] = [
			'interval' => $cache_interval,
			'display' => __('SpeedyCache Cache Lifespan cron', 'speedycache'),
		];

		return $schedules;
	}
	
	static function custom_preload_cron($schedules){
		global $speedycache;

		if(empty($speedycache->options['preload_interval'])){
			return $schedules;
		}
		
		$cache_interval = $speedycache->options['preload_interval'] * HOUR_IN_SECONDS;
		if(empty($cache_interval)){
			return $schedules;
		}

		$schedules['speedycache_preload_cache_schedule'] = [
			'interval' => $cache_interval,
			'display' => __('SpeedyCache Cache Preload cron', 'speedycache'),
		];

		return $schedules;
	}
	
	static function custom_cron($schedules){

		// Every 14 days (Fortnight)
		$schedules['speedycache_fortnight'] = [
			'interval' => 14 * DAY_IN_SECONDS,
			'display' => __('Once Every Fortnight', 'speedycache'),
		];

		// Monthly (approx 30 days)
		$schedules['speedycache_monthly'] = [
			'interval' => 30 * DAY_IN_SECONDS,
			'display' => __('Once Monthly', 'speedycache'),
		];
		
		return $schedules;
	}
	
	// Deletes binaries
	static function delete_cwebp(){
		
		$binary_dir = wp_upload_dir()['basedir'] .'/speedycache-binary';
		
		if(!file_exists($binary_dir)){
			return;
		}

		$binaries = @scandir($binary_dir);
		$binaries = array_diff($binaries, ['.', '..']);
		
		if(empty($binaries)){
			@rmdir($binary_dir);
			return;
		}
		
		foreach($binaries as $binary){
			if(file_exists($binary_dir.'/'.$binary)){
				@unlink($binary_dir.'/'.$binary);
			}
		}
	}
}
