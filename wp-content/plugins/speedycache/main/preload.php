<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Preload{
	
	static function build_preload_list(){
		global $wp_rewrite;

		delete_transient('speedycache_preload_transient');

		if(!isset($wp_rewrite)){
			$wp_rewrite = new \WP_Rewrite();
		}

		$preload_urls = [];
		$preload_urls[] = home_url();

		$args = [
			'fields' => 'ids',
			'post_type' => ['post', 'page', 'product', 'docs'],
			'posts_per_page' => 80,
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'DESC',
			'has_password' => false,
		];
		
		$query = new \WP_Query($args);
		if($query->have_posts()){
			$posts = $query->get_posts();
			foreach($posts as $post_id){
				$preload_urls[] = get_permalink($post_id);
			}
		}

		$query = null;
		
		$args = [
			'fields' => 'ids',
			'post_type' => 'page',
			'posts_per_page' => 10,
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'DESC',
			'has_password' => false
		];

		$query = new \WP_Query($args);
		if($query->have_posts()){
			$posts = $query->get_posts();
			foreach($posts as $post_id){
				$preload_urls[] = get_permalink($post_id);
			}
		}

		$preload_urls = array_unique($preload_urls);

		set_transient('speedycache_preload_transient', $preload_urls, HOUR_IN_SECONDS);
		wp_schedule_single_event(time(), 'speedycache_preload_split');
	}

	static function cache(){
		global $speedycache;

		$preload_urls = get_transient('speedycache_preload_transient');
		$cache_urls = 0;

		if(empty($preload_urls) || !is_array($preload_urls)){
			return;
		}

		foreach($preload_urls as $key => $url){
			if($cache_urls >= 10){
				break;
			}

			wp_remote_get($url, [
				'headers' => [
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36'
				],
				'timeout' => 0.01,
				'blocking' => false,
				'sslverify' => false,
			]);

			// Preload mobile version too
			if(!empty($speedycache->options['mobile_theme'])){
				wp_remote_get($url, [
					'headers' => [
						'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/114.0.5735.99 Mobile/15E148 Safari/604.1'
					],
					'timeout' => 0.01,
					'blocking' => false,
					'sslverify' => false,
				]);
			}

			unset($preload_urls[$key]); // We remove from the list to be preloaded
			$cache_urls++;
		}
		
		if(empty($preload_urls)){
			set_transient('speedycache_preload_transient', [], HOUR_IN_SECONDS);
			return;
		}

		wp_schedule_single_event(time() + 60, 'speedycache_preload_split');
		set_transient('speedycache_preload_transient', $preload_urls, HOUR_IN_SECONDS);
	}
	
	// This will push a request to preload URLS
	// TODO: need to add a lock here
	static function url($urls){

		if(!is_array($urls)){
			$urls = [$urls];
		}

		$preload_urls = get_transient('speedycache_preload_transient');
		if(empty($preload_urls) || !is_array($preload_urls)){
			$preload_urls = [];
		}

		$preload_urls = array_merge($preload_urls, $urls);
		$preload_urls = array_unique($preload_urls);

		set_transient('speedycache_preload_transient', $preload_urls, HOUR_IN_SECONDS);

		if(!wp_next_scheduled('speedycache_preload_split')){
			wp_schedule_single_event(time() + 60, 'speedycache_preload_split');
		}
	}
}
