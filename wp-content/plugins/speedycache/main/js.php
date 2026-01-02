<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT');
}

use \SpeedyCache\Util;

class JS{
	
	// Depericated since 1.2.0
	// do not use it just to prevent site from breaking.
	static function init(){
		
	}
	static function minify(&$content){
		global $speedycache;
		
		if(!class_exists('\SpeedyCache\Enhanced')){
			return;
		}

		preg_match_all('/<script\s+([^>]+[\s"\'])?src\s*=\s*[\'"]\s*?(?<url>[^\'"]+\.js(?:\?[^\'"]*)?)\s*?[\'"]([^>]+)?\/?><\/script>/is', $content, $tags, PREG_SET_ORDER);

		if(empty($tags)){
			return;
		}

		if(empty($_SERVER['HTTP_HOST'])){
			return;
		}

		$site_host = str_replace('www.', '', sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));
		$site_url = site_url();

		foreach($tags as $tag){

			if(empty($tag['url'])){
				continue;
			}

			$url = $tag['url'];
			
			if(self::is_excluded($url)) continue;

			// We don't want to minify already minified js
			if(strpos($url, '.min.js') !== FALSE){
				continue;
			}

			// We wont process any css that is not present on this WordPress install
			if(strpos($url, $site_host) === FALSE){
				continue;
			}

			$file_path = Util::url_to_path($url);

			if(!file_exists($file_path)){
				continue;
			}

			$file_name = self::file_name($file_path);
			
			if(empty($speedycache->enhanced)){
				\SpeedyCache\Enhanced::init();
			}
			
			$js = file_get_contents($file_path);
			$js = \SpeedyCache\Enhanced::minify_js($js);

			$asset_path = Util::cache_path('assets');
			if(!is_dir($asset_path)){
				mkdir($asset_path, 0755, true);
				touch($asset_path . 'index.html');
			}

			$minified_path = $asset_path.$file_name;

			file_put_contents($minified_path, $js);

			$minified_url = Util::path_to_url($minified_path);
			$content = str_replace($tag['url'], $minified_url, $content);
			
			// TODO: check if there is a preload.
		}
	}
	
	static function combine_head(&$content){
		global $speedycache;

		if (preg_match('/<head.*?>(.*?)<\/head>/is', $content, $head_section)) {			
			$head = preg_replace( '/<!--(.*)-->/Uis', '', $head_section[1]);

			// Regex pattern to match script tags with src attribute in the head section
			preg_match_all('/<script\s+([^>]+[\s"\'])?src\s*=\s*[\'"]\s*?(?<url>[^\'"]+\.js(?:\?[^\'"]*)?)\s*?[\'"]([^>]+)?\/?><\/script>/is', $head, $tags, PREG_SET_ORDER);
			
			if(empty($tags)){
				return;
			}

			if(empty($_SERVER['HTTP_HOST'])){
				return;
			}
			
			$site_host = str_replace('www.', '', sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));
			$site_url = site_url();
			
			$tags = array_reverse($tags);
			
			// There is no sense in combinining just 2 files.
			if(count($tags) < 2){
				return;
			}
			
			$combined_js = '';
			$prev_tag = '';

			foreach($tags as $tag){

				if(empty($tag['url'])){
					continue;
				}
				
				// We wont combine modules.
				if(!empty($tag[1]) && strpos($tag[1], 'module')){
					continue;
				}

				$url = $tag['url'];

				if(self::is_excluded($url)) continue;
				
				// We wont process any js that is not present on this WordPress install
				if(strpos($url, $site_host) === FALSE){
					continue;
				}
				
				$file_path = Util::url_to_path($url);

				if(!file_exists($file_path) || !is_readable($file_path)){
					continue;
				}

				$combined_js = file_get_contents($file_path) . "\n" . $combined_js;
				
				// Removing the JS which has already been combined, as we will add the combined file at the top after title.
				if(!empty($prev_tag)){
					$content = str_replace($prev_tag, '', $content);
				}
				
				// We remove the previous tag, in current iteration, so at last we have a tag to replace wirh the combined script.
				$prev_tag = $tag[0];
			}
			
			if(empty($combined_js)){
				return;
			}
			
			if(class_exists('\SpeedyCache\Enhanced') && !empty($speedycache->options['minify_js'])){
				if(empty($speedycache->enhanced)){
					\SpeedyCache\Enhanced::init();
				}

				$combined_js = \SpeedyCache\Enhanced::minify_js($combined_js);
			}

			// Creating Combined file name
			$file_name = md5($combined_js);
			$file_name = substr($file_name, 0, 16) . '-combined.js';
			
			$asset_path = Util::cache_path('assets');
			if(!is_dir($asset_path)){
				mkdir($asset_path, 0755, true);
				touch($asset_path . 'index.html');
			}

			$combined_path = $asset_path.$file_name;

			file_put_contents($combined_path, $combined_js);
			$final_url = Util::path_to_url($combined_path);

			// Injecting the Combined JS
			if(!empty($prev_tag)){
				$content = str_replace($prev_tag, '<script src="'.esc_url($final_url).'" />', $content);
				return;
			}

			$content = str_replace('</title>', "</title>\n".'<script src="'.esc_url($final_url).'"/>', $content);
		
		}
	}
	
	static function file_name($path){
		$file_hash = md5_file($path);
		$file_name = substr($file_hash, 0, 16) . '-' . basename($path);

		return $file_name;
	}
	
	static function combine_body(&$content){
		global $speedycache;
		
		\SpeedyCache\Enhanced::init();
		\SpeedyCache\Enhanced::set_html($content);

		if(!empty($speedycache->options['minify_js'])){
			$content = \SpeedyCache\Enhanced::combine_js_in_footer(true);
		}else{
			$content = \SpeedyCache\Enhanced::combine_js_in_footer();
		}
	}
	
	static function is_excluded($url){
		$excludes = get_option('speedycache_exclude', []);
		
		// Combining JQUERY will mess up the site.
		if(strpos($url, 'jquery')){
			return true;
		}

		if(empty($excludes)){
			return false;
		}

		foreach($excludes as $exclude){
			if(empty($exclude['type'])){
				continue;
			}

			if($exclude['type'] !== 'js'){
				continue;
			}

			if(empty($exclude['content'])){
				continue;
			}

			if(preg_match('/'.preg_quote($exclude['content'], '/').'/', $url)){
				return true;
			}
		}

		return false;
	}
}
