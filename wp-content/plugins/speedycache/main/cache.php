<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

use \SpeedyCache\Util;

class Cache {
	static $cache_file_path = '';
	static $ignored_parameters = ['fbclid', 'utm_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_source_platform', 'gclid', 'dclid', 'msclkid', 'ref', 'fbaction_ids', 'fbc', 'fbp', 'clid', 'mc_cid', 'mc_eid', 'hsCtaTracking', 'hsa_cam', 'hsa_grp', 'hsa_mt', 'hsa_src', 'hsa_ad', 'hsa_acc', 'hsa_net', 'hsa_kw'];
	
	static $content = '';

	static function init(){
		global $speedycache;

		if(!defined('SPEEDYCACHE_SERVER_HOST')){
			define('SPEEDYCACHE_SERVER_HOST', Util::sanitize_server('HTTP_HOST'));
		}

		if(!defined('SITEPAD')){
			if(!empty($speedycache->options['dns_prefetch']) && !empty($speedycache->options['dns_urls'])){
				add_filter('wp_resource_hints', '\SpeedyCache\Cache::dns_prefetch_hint', 10, 2);
			}
		}
		
		// Filter for Gravatar cache. We are updating the URL of the gravatar here so the local hosted Gravatar URL will be cached.
		if(!empty($speedycache->options['gravatar_cache'])){
			add_filter('get_avatar_data', '\SpeedyCache\Gravatar::get_avatar_data', 10, 2);
		}

		// Loads Instant Page to improve load page speed by 1%
		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->options['instant_page'])){
			add_action('wp_enqueue_scripts', '\SpeedyCache\Cache::instant_page');
		}

		if(!empty($speedycache->options['disable_emojis'])){
			add_action('init', '\SpeedyCache\Cache::disable_emojis');
		}
		
		// Optimizes images when a page gets loaded and it finds no image optimized
		if(class_exists('\SpeedyCache\Image') && !empty($speedycache->image['settings']['automatic_optm'])){
			add_filter('the_content', '\SpeedyCache\Image::optimize_on_fly');
		}
		
		// Adds preconnect
		if(class_exists('\SpeedyCache\Enhanced')){
			if(!defined('SITEPAD')){
				if(!empty($speedycache->options['pre_connect']) && !empty($speedycache->options['pre_connect_list'])){
					add_filter('wp_resource_hints', '\SpeedyCache\Enhanced::pre_connect_hint', 10, 2);
				}
			}
		
			// Adds Preload link tag to the head
			if(!empty($speedycache->options['preload_resources'])){
				add_action('wp_head', '\SpeedyCache\Enhanced::preload_resource', 0);
			}
		}

		// Image URL rewrite
		if(class_exists('\SpeedyCache\Image') && !empty($speedycache->image['settings']['url_rewrite'])){
			add_filter('the_content', 'SpeedyCache\Image::rewrite_url_to_webp', 10);
		}

		ob_start('\SpeedyCache\Cache::optimize');
	}

	static function create(){
		global $speedycache;

		$cache_path = self::cache_path();

		if(!file_exists($cache_path)){
			mkdir($cache_path, 0755, true);
		}

		$cache_path .= '/' . self::cache_file_name();
		
		$mobile = '';
		if(strpos($cache_path, 'mobile-cache') !== FALSE){
			$mobile = 'Mobile: ';
		}
		
		$cache_path = wp_normalize_path($cache_path);

		file_put_contents($cache_path, self::$content . "\n<!-- ".esc_html($mobile)."Cache by SpeedyCache https://speedycache.com -->");

		if(function_exists('gzencode') && !empty($speedycache->options['gzip'])){
			$gzidded_content = gzencode(self::$content . "\n<!-- ".esc_html($mobile)."Cache by SpeedyCache https://speedycache.com -->");
			file_put_contents($cache_path . '.gz', $gzidded_content);
		}

		delete_option('speedycache_html_size');
		delete_option('speedycache_assets_size');
	}
	
	static function cache_file_name(){		
		$file_name = 'index';

		if(isset($_COOKIE['wcu_current_currency'])){
			$file_name .= '-'. strtolower(sanitize_file_name($_COOKIE['wcu_current_currency']));
		}

		return $file_name . '.html';
	}
	

	static function cache_path(){
		global $speedycache;
		
		if(!file_exists(SPEEDYCACHE_CACHE_DIR)){
			if(mkdir(SPEEDYCACHE_CACHE_DIR, 0755, true)){
				touch(SPEEDYCACHE_CACHE_DIR . '/index.html');
			}
		}

		$host = $_SERVER['HTTP_HOST'];
		$request_uri = urldecode(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
		$request_uri = preg_replace('/\.{2,}/', '', $request_uri); // Cleaning the path
		$request_uri = remove_query_arg(self::$ignored_parameters, $request_uri); // Cleaning ignored query
		$parsed_uri = wp_parse_url($request_uri);

		$path = SPEEDYCACHE_CACHE_DIR;
		$path .= '/' . $host;

		if(wp_is_mobile() && !empty($speedycache->options['mobile_theme'])){
			$path .= '/mobile-cache';
		} else {
			$path .= '/all';
		}
			
		// Handling WeGlot
		if(function_exists('weglot_get_current_full_url')){
			$weglot_url = weglot_get_current_full_url();
			$weglot_path = parse_url($weglot_url, PHP_URL_PATH);
			
			$path .= $weglot_path;
		} else {
			$path .= $parsed_uri['path'];
		}

		self::$cache_file_path = $path;

		return $path;
	}

	static function can_cache(){
		global $speedycache;
		
		if(empty($speedycache->options['status'])) return false;

		if(empty($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'GET') return false;
		
		if(defined('WP_CLI') && !empty(WP_CLI)) return false;

		if(defined('REST_REQUEST') && !empty(REST_REQUEST)) return false;

		if(function_exists('http_response_code') && (http_response_code() > 309)) return false;
		
		if(preg_match('/\./', $_SERVER['REQUEST_URI'])) return false;
		
		if (defined('SITEPAD')) {
			if (preg_match('/(site-admin|login|wp-register|wp-comments-post|cron|sp-json)/', $_SERVER['REQUEST_URI'])) {
				return false;
			}
		} else {
			if (preg_match('/(wp-(?:admin|login|register|comments-post|cron|json))/', $_SERVER['REQUEST_URI'])) {
				return false;
			}
		}
		
		if(preg_match('/html.*\s(amp|⚡)/', substr(self::$content, 0, 300))) return false;
		
		if(wp_is_mobile() && !empty($speedycache->options['mobile']) && empty($speedycache->options['mobile_theme'])) return false;

		if(is_admin()) return false;

		// Since: 1.2.8 we will only cache the page if user is not logged-in.
		if(is_user_logged_in()) return false;
		
		if(!preg_match( '/<\s*\/\s*html\s*>/i', self::$content)) return false;

		if(is_singular() && post_password_required()) return false;
		
		if(function_exists('is_404') && is_404()) return false;

		if(self::is_excluded()) return false;

		if(function_exists('is_cart') && is_cart()) return false;

		if(function_exists('is_checkout') && is_checkout()) return false;
		
		if(function_exists('is_account_page') && is_account_page()) return false;

		if(!self::can_handle_query()) return false;
	
		return true;
	}

	static function optimize($content){
		global $speedycache;
		
		self::$content = &$content;
		
		$start_time = microtime(TRUE);

		if(!self::can_cache()){
			return self::$content;
		}
		
		self::clean_html();

		// Minify HTML
		if(class_exists('\SpeedyCache\Enhanced') && !empty($speedycache->options['minify_html']) && (defined('SPEEDYCACHE_PRO_VERSION') && version_compare(SPEEDYCACHE_PRO_VERSION, '1.2.0', '>='))){
			\SpeedyCache\Enhanced::init();
			\SpeedyCache\Enhanced::minify_html(self::$content);
		}

		// ADD Font Rendering CSS
		if(!empty($speedycache->options['font_rendering'])){
			self::$content = str_replace('</head>', '<style>body{text-rendering: optimizeSpeed;}</style></head>', self::$content);
		}
		
		// Lazy Load HTML elements
		if(class_exists('\SpeedyCache\Enhanced') && !empty($speedycache->options['lazy_load_html']) && !empty($speedycache->options['lazy_load_html_elements'])){
			self::$content = \SpeedyCache\Enhanced::lazy_load_html(self::$content);
		}

		if(!empty($speedycache->options['combine_css'])){
			\SpeedyCache\CSS::combine(self::$content);
		}

		if(!empty($speedycache->options['minify_css'])){
			\SpeedyCache\CSS::minify(self::$content);
		}
		
		if(!empty($speedycache->options['combine_js'])){
			\SpeedyCache\JS::combine_head(self::$content);
		}
		
		// if(class_exists('\SpeedyCache\Enhanced') && !empty($speedycache->options['combine_js'])){
			// \SpeedyCache\JS::combine_body($content);
		// }
		
		if(!empty($speedycache->options['minify_js'])){
			\SpeedyCache\JS::minify(self::$content);
		}
		
		// Adds Image dimensions to the Image which does not have height or width
		if(class_exists('\SpeedyCache\Enhanced') && !empty($speedycache->options['image_dimensions'])){
			self::$content = \SpeedyCache\Enhanced::image_dimensions(self::$content);
		}

		// Google Fonts
		if(class_exists('\SpeedyCache\GoogleFonts') && !empty($speedycache->options['local_gfonts'])){
			\SpeedyCache\GoogleFonts::get($content);
			self::$content = \SpeedyCache\GoogleFonts::replace(self::$content);
			self::$content = \SpeedyCache\GoogleFonts::add_swap(self::$content);
		}

		// Preload Critical Images
		if(class_exists('\SpeedyCache\Enhanced') && !empty($speedycache->options['critical_images'])){
			self::$content = \SpeedyCache\Enhanced::preload_critical_images(self::$content);
		}

		// Delay JS
		if(!empty($speedycache->options['delay_js']) && class_exists('\SpeedyCache\ProOptimizations')){
			\SpeedyCache\ProOptimizations::delay_js(self::$content);
		}
		
		// Defer JS
		if(!empty($speedycache->options['render_blocking']) && class_exists('\SpeedyCache\ProOptimizations')){
			\SpeedyCache\ProOptimizations::defer_js(self::$content);
		}
		
		// IMG Lazy Load
		if(class_exists('\SpeedyCache\ProOptimizations') && !empty($speedycache->options['lazy_load'])){
			\SpeedyCache\ProOptimizations::img_lazy_load(self::$content);
		}
		
		// For other plugins to hook into.
		self::$content = (string) apply_filters('speedycache_content', self::$content);

		// ----- DO NOT DO ANY OPTIMIZATION BELOW THIS ------
		// Unused and Critical CSS
		if(
			!empty($_SERVER['HTTP_HOST']) && 
			!empty($_SERVER['REQUEST_URI']) && 
			!empty($_SERVER['HTTP_USER_AGENT']) && 
			class_exists('\SpeedyCache\ProOptimizations') && 
			speedycache_optserver('HTTP_USER_AGENT') !== 'SpeedyCacheCCSS' && 
			!(defined('SITEPAD'))
		){
			$post_meta = get_post_meta(get_the_ID(), 'speedycache_post_meta', true);
			
			if(!empty($speedycache->options['critical_css']) && empty($post_meta['disable_critical_css'])){
				\SpeedyCache\ProOptimizations::critical_css();
			}
			
			if(empty($post_meta['disable_unused_css']) && !empty($speedycache->options['unused_css'])){
				\SpeedyCache\ProOptimizations::unused_css();
			}
		}
		
		// Rewriting to a CDN
		if(
			!empty($speedycache->cdn) && 
			!empty($speedycache->cdn['enabled']) && 
			!empty($speedycache->cdn['cdn_url']) && 
			!empty($speedycache->cdn['cdn_type']) && 
			$speedycache->cdn['cdn_type'] !== 'cloudflare'
		){
			\SpeedyCache\CDN::rewrite(self::$content);
		}

		self::create();
		$end_time = microtime(TRUE);

		self::$content .= '<!-- Cached by SpeedyCache, it took '.($end_time - $start_time).'s-->';
		self::$content .= '<!-- Refresh to see the cached version -->';
		
		if(file_exists(self::$cache_file_path)){
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime(self::$cache_file_path)) . ' GMT');
		}
		return self::$content;
	}
	
	static function clean_html(){
		self::$content = str_replace("\r\n", "\n", trim(self::$content));
	}
	
	static function is_excluded(){
		global $speedycache;
		
		$excludes = get_option('speedycache_exclude', []);
		
		if(empty($excludes)){
			return false;
		}

		$is_excluded = false;

		foreach($excludes as $rule){
			switch($rule['type']){
				case 'page':
					$is_excluded = self::is_page_excluded($rule);
					break;

				case 'useragent':
					$is_excluded = self::is_useragent_excluded($rule);
					break;

				case 'cookie':
					$is_excluded = self::is_cookie_excluded($rule);
					break;
			}

			if(!empty($is_excluded)){
				return true;
			}
		}

		return false;
	}
	
	static function can_handle_query(){
		$uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
		$uri = remove_query_arg(self::$ignored_parameters, $uri);
		$parsed_uri = wp_parse_url($uri);

		if(!empty($parsed_uri['query'])){
			return false;
		}

		return true;
	}
	
	static function is_page_excluded($rule){

		if(empty($rule['prefix'])){
			return false;
		}

		if($rule['prefix'] === 'homepage'){
			return is_front_page();
		}
		
		if($rule['prefix'] === 'page'){
			return is_page();
		}

		if($rule['prefix'] === 'post_id' && !empty($rule['content'])){
			$excluded_ids = is_array($rule['content']) ? $rule['content'] : explode(',', $rule['content']);
			return in_array(get_queried_object_id(), $excluded_ids);
		}
		
		// Excludes a page if it has the given shortcode.
		if($rule['prefix'] === 'shortcode' && !empty($rule['content'])){
			if(self::has_shortcode($rule['content'])){
				return true;
			}
		}

		if($rule['prefix'] === 'category'){
			return is_category();
		}
		
		if($rule['prefix'] === 'archive'){
			return is_archive();
		}
		
		if($rule['prefix'] === 'tag'){
			return is_tag();
		}
		
		if($rule['prefix'] === 'attachment'){
			return is_attachment();
		}
		
		if($rule['prefix'] === 'startwith' && !empty($rule['content'])){
			return (bool) preg_match('/^'.preg_quote($rule['content'], '/').'/', trim($_SERVER['REQUEST_URI'], '/'));
		}
		
		if($rule['prefix'] === 'contain' && !empty($rule['content'])){
			return (bool) preg_match('/'.preg_quote($rule['content'], '/').'/', trim($_SERVER['REQUEST_URI'], '/'));
		}
		
		if($rule['prefix'] === 'exact' && !empty($rule['content'])){
			return trim($rule['content'], '/') === trim($_SERVER['REQUEST_URI'], '/');
		}
		
		return false;
	}
	
	static function is_cookie_excluded($rule){
		if(!isset($_SERVER['HTTP_COOKIE'])){
			return false;
		}

		$cookie = sanitize_text_field(wp_unslash($_SERVER['HTTP_COOKIE']));

		return preg_match('/'.preg_quote($rule['content'], '/').'/i', $cookie);
	}
	
	static function is_useragent_excluded($rule){
		return preg_match('/'.preg_quote($rule['content'], '/').'/i', $_SERVER['HTTP_USER_AGENT']);
	}
	
	// Adds DNS prefetch
	static function dns_prefetch_hint($urls, $relation_type){
		global $speedycache;

		if($relation_type !== 'dns-prefetch'){
			return $urls;
		}

		foreach($speedycache->options['dns_urls'] as $url) {
			if(!empty($url)){
				$urls[] = $url;
			}
		}

		return $urls;
	}
	
	// Depricated since 1.2.0 do not use it
	// Just to prevent site from breaking
	static function create_dir($path, $content, $type = ''){
	}
	
	
	static function disable_emojis(){
		add_filter('emoji_svg_url', '__return_false');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('admin_print_styles', 'print_emoji_styles');
		remove_filter('the_content_feed', 'wp_staticize_emoji');
		remove_filter('comment_text_rss', 'wp_staticize_emoji'); 
		remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	}
	
	static function instant_page(){
		wp_enqueue_script('speedycache_instant_page', SPEEDYCACHE_PRO_URL . '/assets/js/instantpage.js', array(), SPEEDYCACHE_PRO_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
	}
	
	/*
	* @param string $shortcode shortcode tag name.
	* @return bool.
	*/
	static function has_shortcode($shortcode){
		global $post;

		return \has_shortcode($post->post_content, $shortcode);
	}
}
