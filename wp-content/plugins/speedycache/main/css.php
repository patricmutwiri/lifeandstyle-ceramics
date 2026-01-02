<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

use \SpeedyCache\lib\Minify;
use \SpeedyCache\Util;

class CSS{
	
	static function minify(&$content){
		global $speedycache;
		
		if(empty($content)){
			return;
		}

		preg_match_all('/<link\s+([^>]+[\s"\'])?href\s*=\s*[\'"]\s*?(?<url>[^\'"]+\.css(?:\?[^\'"]*)?)\s*?[\'"]([^>]+)?\/?>/Umsi', $content, $tags, PREG_SET_ORDER);

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

			// We don't want to minify already minified css
			if(strpos($url, '.min.css') !== FALSE){
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
			$asset_path = Util::cache_path('assets');
			if(!is_dir($asset_path)){
				mkdir($asset_path, 0755, true);
				touch($asset_path . 'index.html');
			}

			$minified_path = $asset_path.$file_name;

			// If we already have a minified file then we dont need to process it again.
			if(!file_exists($minified_path)){
				$minified = new Minify\CSS($file_path);
				$minified = $minified->minify();

				$minified = self::fix_relative_path($minified, $url);
				file_put_contents($minified_path, $minified);
			}

			$minified_url = Util::path_to_url($minified_path);
			$content = str_replace($tag['url'], $minified_url, $content);
			
			// TODO: check if there is a preload.
		}
		
	}

	static function file_name($path){
		$file_hash = md5_file($path);
		$file_name = substr($file_hash, 0, 16) . '-' . basename($path);

		return $file_name;
	}
	
	static function combine(&$content){
		if(empty($content)){
			return;
		}

		preg_match_all('/<link\s+([^>]+[\s"\'])?href\s*=\s*[\'"]\s*?(?<url>[^\'"]+\.css(?:\?[^\'"]*)?)\s*?[\'"]([^>]+)?\/?>/Umsi', $content, $tags, PREG_SET_ORDER);
		
		if(empty($tags)){
			return;
		}

		if(empty($_SERVER['HTTP_HOST'])){
			return;
		}

		$site_host = str_replace('www.', '', sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));
		$site_url = site_url();

		$combined_css = '';
		$prev_tag = '';
		
		$tags = array_reverse($tags);

		foreach($tags as $tag){

			if(empty($tag['url'])){
				continue;
			}

			$url = $tag['url'];
			
			if(self::is_excluded($url)) continue;

			// We wont process any css that is not present on this WordPress install
			if(strpos($url, $site_host) === FALSE){
				continue;
			}

			$file_path = Util::url_to_path($url);

			if(!file_exists($file_path) || !is_readable($file_path)){
				continue;
			}
			
			$new_css = file_get_contents($file_path);
			$new_css = self::fix_relative_path($new_css, $url);

			$combined_css = $new_css . "\n" . $combined_css;

			// Removing the CSS which has already been combined, as we will add the combined file at the top after title.
			if(!empty($prev_tag)){
				$content = str_replace($prev_tag, '', $content);
			}

			// We remove the previous tag, in current iteration, so at last we have a tag to replace wirh the combined script.
			$prev_tag = $tag[0];
			
			//TODO: Need to remove any preload added by any plugin or a theme.
		}

		if(empty($combined_css)){
			return;
		}

		// Creating Combined file name
		$file_name = md5($combined_css);
		$file_name = substr($file_name, 0, 16) . '-combined.css';
		
		$asset_path = Util::cache_path('assets');
		if(!is_dir($asset_path)){
			mkdir($asset_path, 0755, true);
			touch($asset_path . 'index.html');
		}

		$combined_path = $asset_path.$file_name;

		file_put_contents($combined_path, $combined_css);
		$final_url = Util::path_to_url($combined_path);

		// Injecting the Combined CSS
		if(!empty($prev_tag)){
			$content = str_replace($prev_tag, '<link rel="stylesheet" href="'.esc_url($final_url).'" />', $content);
			return;
		}

		$content = str_replace('</title>', "</title>\n".'<link rel="stylesheet" href="'.esc_url($final_url).'" />', $content);
	}
	
	static function is_excluded($url){
		$excludes = get_option('speedycache_exclude', []);

		if(empty($excludes)){
			return false;
		}

		foreach($excludes as $exclude){
			if(empty($exclude['type'])){
				continue;
			}

			if($exclude['type'] !== 'css'){
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
	
	static function fix_relative_path($content, $base_url){
		
		// We need base url as the relative url file will be in the same folder as file at the base url or will be relative to path of the base url
		$content = preg_replace_callback('/url\(\s*["\']?(?!http|https|\/\/)([^"\')]+)["\']?\s*\)/i', function($matches) use ($base_url) {
			$relative_path = $matches[1];
			$relative_path = trim($relative_path, '/');
			$base_path = Util::url_to_path($base_url);

			if(strpos($relative_path, '..') === 0 || strpos($relative_path, './') === 0){
				$parameter = '';
				// Some URL's had query parameters, that were breaking when using realpath
				if(strpos($relative_path, '?') !== FALSE){
					$parsed_path = explode('?', $relative_path);
					$parameter = $parsed_path[1];
					$relative_path = $parsed_path[0];
					$parsed_path = null;
				}

				$absolute_path = realpath(dirname($base_path) . '/' . $relative_path);
				$absolute_url = Util::path_to_url($absolute_path);
				
				// Appending the parameter again
				if(!empty($parameter)){
					$absolute_url .= '?'. $parameter;
				}
			} else if(strpos($relative_path, 'wp-content') === 0){
				$absolute_url = site_url() . '/'. $relative_path;
			}

			if(empty($absolute_url)){
				$absolute_url = $relative_path;
			}
			
			return 'url("' . $absolute_url . '")';
			
		}, $content);
		
		return $content;
	}
}
