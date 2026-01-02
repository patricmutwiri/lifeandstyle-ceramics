<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

// Third Party Plugins
class Install{
	
	// Called during activation
	static function activate(){
		global $speedycache;
		
		if(empty($speedycache)){
			$speedycache = new \SpeedyCache();
		}
		
		$speedycache->options = get_option('speedycache_options', []);
		$speedycache->options['lbc'] = true;
		$speedycache->options['minify_css'] = true;
		$speedycache->options['gzip'] = true;

		update_option('speedycache_options', $speedycache->options);
		update_option('speedycache_version', SPEEDYCACHE_VERSION);

		\SpeedyCache\Htaccess::init();
		self::set_advanced_cache();
		\SpeedyCache\Util::set_config_file();
	}

	// Called during Deactivation
	static function deactivate(){

		if(is_file(ABSPATH.'.htaccess') && is_writable(ABSPATH.'.htaccess')){
			$htaccess = file_get_contents(ABSPATH.'.htaccess');
			$htaccess = preg_replace("/#\s?BEGIN\s?speedycache.*?#\s?END\s?speedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?Gzipspeedycache.*?#\s?END\s?Gzipspeedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?LBCspeedycache.*?#\s?END\s?LBCspeedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?WEBPspeedycache.*?#\s?END\s?WEBPspeedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?SpeedyCacheheaders.*?#\s?END\s?SpeedyCacheheaders/s", '', $htaccess);
			$htaccess = preg_replace('/\n\n+/', "\n\n", $htaccess); // Cleans extra white space which gets added
			@file_put_contents(ABSPATH.'.htaccess', $htaccess);
		}
		
		self::remove_constant();
		wp_clear_scheduled_hook('speedycache_preload');
		wp_clear_scheduled_hook('speedycache_purge_cache');
		wp_clear_scheduled_hook('speedycache_preload_split');
	}
	
	static function set_advanced_cache(){

		if(file_exists(WP_CONTENT_DIR . '/advanced-cache.php')){
			unlink(WP_CONTENT_DIR . '/advanced-cache.php');
		}

		if(!copy(SPEEDYCACHE_DIR . '/main/advanced-cache.php', WP_CONTENT_DIR . '/advanced-cache.php')){
			return;
		}

		// Adding WP_CACHE Constant
		self::add_constant();
	}

	// Adds WP_CACHE constant in wp-config.php
	static function add_constant(){
		
		if(defined('SITEPAD')){
			$cache_config_file = WP_CONTENT_DIR . '/enable-advanced-cache.php';

			if ( ! file_exists($cache_config_file) ) {
				$content = "<?php\n// If this file exists, advanced caching will be enabled\n";
				file_put_contents($cache_config_file, $content);
			}
			
			return;
		}
		
		$wp_config_file = ABSPATH . '/wp-config.php';

		if(!file_exists($wp_config_file) || !is_writable($wp_config_file)){
			return false;
		}

		$wp_config_content = file_get_contents($wp_config_file);

		if(empty($wp_config_content)){
			return;
		}

		// Removing if WP_CACHE is already placed
		$wp_config_content = preg_replace('/define\(\s*["\']WP_CACHE[\'\"].*/', '', $wp_config_content);
		
		// Adding the Constant
		$wp_config_content = preg_replace('/<\?php/', "<?php\ndefine('WP_CACHE', true); // Added by SpeedyCache\n", $wp_config_content);

		$wp_config_content = preg_replace('/\n\n+/', "\n\n", $wp_config_content); // Cleans extra white space which gets added
		
		file_put_contents($wp_config_file, $wp_config_content);
	}

	// Removes WP_CACHE Constant.
	static function remove_constant(){
		
		if(defined('SITEPAD')){
			$file_to_delete = WP_CONTENT_DIR . '/enable-advanced-cache.php';

			if ( file_exists($file_to_delete) && is_writable($file_to_delete) ) {
				unlink($file_to_delete);
			}
			return;
		}
		
		$wp_config_file = ABSPATH . '/wp-config.php';

		if(!file_exists($wp_config_file) || !is_writable($wp_config_file)){
			return false;
		}
		
		$wp_config_content = file_get_contents($wp_config_file);
		
		if(empty($wp_config_content)){
			return;
		}

		// Removing if WP_CACHE is already placed
		$wp_config_content = preg_replace('/define\(\s*["\']WP_CACHE[\'\"].*/', '', $wp_config_content);
		$wp_config_content = preg_replace('/\n\n+/', "\n\n", $wp_config_content); // Cleans extra white space which gets added

		file_put_contents($wp_config_file, $wp_config_content);
	}
	
	static function uninstall(){
		delete_option('speedycache_version'); 
		delete_option('speedycache_options');
		delete_option('speedycache_cdn');
		delete_option('speedycache_delete_cache_logs');
		delete_option('speedycache_img');
		delete_option('speedycache_object_cache');
		delete_option('speedycache_ccss_logs');
		delete_option('speedycache_license');
		
		if(defined('SPEEDYCACHE_PRO')){
			\SpeedyCache\Util::delete_cwebp();
		}
	}

}
