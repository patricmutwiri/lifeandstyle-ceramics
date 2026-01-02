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

class Import{

	static function rank_math(){
		$imported_count = 0;
		$log = [];
		
		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		foreach($posts as $post){
	 
			$robots = get_post_meta($post->ID, 'rank_math_robots', true);
			$robots_array = is_array($robots) ? $robots : [];

			$meta_mapping = [
				'_siteseo_titles_title' => self::replace_snippet_vars(get_post_meta($post->ID, 'rank_math_title', true),'rank_math'),
				'_siteseo_titles_desc' => self::replace_snippet_vars(get_post_meta($post->ID, 'rank_math_description', true),'rank_math'),
				'_siteseo_social_fb_title' => self::replace_snippet_vars(get_post_meta($post->ID, 'rank_math_facebook_title', true),'rank_math'),
				'_siteseo_social_fb_desc' => self::replace_snippet_vars(get_post_meta($post->ID, 'rank_math_facebook_description', true),'rank_math'),
				'_siteseo_social_fb_img' => get_post_meta($post->ID, 'rank_math_facebook_image', true),
				'_siteseo_social_twitter_title' => self::replace_snippet_vars(get_post_meta($post->ID, 'rank_math_twitter_title', true),'rank_math'),
				'_siteseo_social_twitter_desc' => self::replace_snippet_vars(get_post_meta($post->ID, 'rank_math_twitter_description', true),'rank_math'),
				'_siteseo_social_twitter_img' => get_post_meta($post->ID, 'rank_math_twitter_image', true),
				'_siteseo_robots_index' => in_array('noindex', $robots_array) ? '1' : '',
				'_siteseo_robots_follow' => in_array('nofollow', $robots_array) ? '1' : '',
				'_siteseo_robots_imageindex' => in_array('noimageindex', $robots_array) ? '1' : '',
				'_siteseo_robots_archive' => in_array('noarchive', $robots_array) ? '1' : '',
				'_siteseo_robots_snippet' => in_array('nosnippet', $robots_array) ? '1' : '',
				'_siteseo_robots_canonical' => get_post_meta($post->ID, 'rank_math_canonical_url', true),
				'_siteseo_analysis_target_kw' => get_post_meta($post->ID, 'rank_math_focus_keyword', true),
				'_siteseo_robots_primary_cat' => get_post_meta($post->ID, 'rank_math_primary_category', true)
			];
				
			foreach($meta_mapping as $siteseo_key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $siteseo_key, $value);
					$imported_count++;
				}
			}
			$log[] = "Imported post ID: {$post->ID}";
		}
		
		$taxonomies = get_taxonomies();
		
		foreach($taxonomies as $taxonomy){
			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
			]);
			
			foreach($terms as $term){
				$term_robots = get_term_meta($term->term_id, 'rank_math_robots', true);
				$term_robots_array = is_array($term_robots) ? $term_robots : [];

				$meta_mapping = [
					'_siteseo_titles_title' => self::replace_snippet_vars(get_term_meta($term->term_id, 'rank_math_title', true),'rank_math'),
					'_siteseo_titles_desc' => self::replace_snippet_vars(get_term_meta($term->term_id, 'rank_math_description', true),'rank_math'),
					'_siteseo_social_fb_title' => self::replace_snippet_vars(get_term_meta($term->term_id, 'rank_math_facebook_title', true),'rank_math'),
					'_siteseo_social_fb_desc' => self::replace_snippet_vars(get_term_meta($term->term_id, 'rank_math_facebook_description', true),'rank_math'),
					'_siteseo_social_fb_img' => get_term_meta($term->term_id, 'rank_math_facebook_image', true),
					'_siteseo_social_twitter_title' => self::replace_snippet_vars(get_term_meta($term->term_id, 'rank_math_twitter_title', true),'rank_math'),
					'_siteseo_social_twitter_desc' => self::replace_snippet_vars(get_term_meta($term->term_id, 'rank_math_twitter_description', true),'rank_math'),
					'_siteseo_social_twitter_img' => get_term_meta($term->term_id, 'rank_math_twitter_image', true),
					'_siteseo_robots_index' => in_array('noindex', $term_robots_array) ? '1' : '',
					'_siteseo_robots_follow' => in_array('nofollow', $term_robots_array) ? '1' : '',
					'_siteseo_robots_imageindex' => in_array('noimageindex', $term_robots_array) ? '1' : '',
					'_siteseo_robots_archive' => in_array('noarchive', $term_robots_array) ? '1' : '',
					'_siteseo_robots_snippet' => in_array('nosnippet', $term_robots_array) ? '1' : '',
					'_siteseo_robots_canonical' => get_term_meta($term->term_id, 'rank_math_canonical_url', true),
					'_siteseo_analysis_target_kw' => get_term_meta($term->term_id, 'rank_math_focus_keyword', true)
				];
				
				foreach($meta_mapping as $siteseo_key => $value){
					if(!empty($value)){
						update_term_meta($term->term_id, $siteseo_key, $value);
						$imported_count++;
					}
				}
				
				$log[] = "Imported term ID: {$term->term_id}";
			}
		}
		
		return [
			'count' => $imported_count,
			'log' => $log,
			/* translators: %d count of items imported */
			'message' => sprintf(__('Rank Math import completed. Imported %d items.', 'siteseo'), $imported_count)
		];
	}

	static function yoast_seo(){
		$imported_count = 0;
		$log = [];
    
		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		foreach($posts as $post){
			$yoast_robots = get_post_meta($post->ID, '_yoast_wpseo_meta-robots-adv', true);
			$robots_array = $yoast_robots ? explode(',', $yoast_robots) : [];
			
			$meta_mapping = [
				'_siteseo_titles_title' => self::replace_snippet_vars(get_post_meta($post->ID, '_yoast_wpseo_title', true),'yoast_seo'),
				'_siteseo_titles_desc' => self::replace_snippet_vars(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true),'yoast_seo'),
				'_siteseo_social_fb_title' => self::replace_snippet_vars(get_post_meta($post->ID, '_yoast_wpseo_opengraph-title', true),'yoast_seo'),
				'_siteseo_social_fb_desc' => self::replace_snippet_vars(get_post_meta($post->ID, '_yoast_wpseo_opengraph-description', true),'yoast_seo'),
				'_siteseo_social_fb_img' => get_post_meta($post->ID, '_yoast_wpseo_opengraph-image', true),
				'_siteseo_social_twitter_title' => self::replace_snippet_vars(get_post_meta($post->ID, '_yoast_wpseo_twitter-title', true),'yoast_seo'),
				'_siteseo_social_twitter_desc' => self::replace_snippet_vars(get_post_meta($post->ID, '_yoast_wpseo_twitter-description', true),'yoast_seo'),
				'_siteseo_social_twitter_img' => get_post_meta($post->ID, '_yoast_wpseo_twitter-image', true),
				'_siteseo_robots_index' => get_post_meta($post->ID, '_yoast_wpseo_meta-robots-noindex', true) ? '1' : '',
				'_siteseo_robots_follow' => get_post_meta($post->ID, '_yoast_wpseo_meta-robots-nofollow', true) ? '1' : '',
				'_siteseo_robots_imageindex' => in_array('noimageindex', $robots_array) ? '1' : '',
				'_siteseo_robots_archive' => in_array('noarchive', $robots_array) ? '1' : '',
				'_siteseo_robots_snippet' => in_array('nosnippet', $robots_array) ? '1' : '',
				'_siteseo_robots_canonical' => get_post_meta($post->ID, '_yoast_wpseo_canonical', true),
				'_siteseo_analysis_target_kw' => get_post_meta($post->ID, '_yoast_wpseo_focuskw', true),
				'_siteseo_robots_primary_cat' => get_post_meta($post->ID, '_yoast_wpseo_primary_category', true)
			];
			
			foreach($meta_mapping as $siteseo_key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $siteseo_key, $value);
					$imported_count++;
				}
			}
			$log[] = "Imported post ID: {$post->ID}";
		}
		
		$yoastseo_term_meta = get_option('wpseo_taxonomy_meta');
		
		foreach($yoastseo_term_meta as $taxonomy){
			
			if(!is_array($taxonomy)){
				continue;
			}
			
			foreach($taxonomy as $term_id => $term){
				$meta_mapping = [
					'_siteseo_titles_title' => self::replace_snippet_vars((!empty($term['wpseo_title']) ? $term['wpseo_title'] : ''),'yoast_seo'),
					'_siteseo_titles_desc' => self::replace_snippet_vars((!empty($term['wpseo_desc']) ? $term['wpseo_desc'] : ''),'yoast_seo'),
					'_siteseo_social_fb_title' => self::replace_snippet_vars((!empty($term['wpseo_opengraph-title']) ? $term['wpseo_opengraph-title'] : ''),'yoast_seo'),
					'_siteseo_social_fb_desc' => self::replace_snippet_vars((!empty($term['wpseo_opengraph-description']) ? $term['wpseo_opengraph-description'] : ''),'yoast_seo'),
					'_siteseo_social_fb_img' => !empty($term['wpseo_opengraph-image']) ? $term['wpseo_opengraph-image'] : '',
					'_siteseo_social_twitter_title' => self::replace_snippet_vars((!empty($term['wpseo_twitter-title']) ? $term['wpseo_twitter-title'] : ''),'yoast_seo'),
					'_siteseo_social_twitter_desc' => self::replace_snippet_vars((!empty($term['wpseo_twitter-description']) ? $term['wpseo_twitter-description'] : ''),'yoast_seo'),
					'_siteseo_social_twitter_img' => !empty($term['wpseo_twitter-image']) ? $term['wpseo_twitter-image'] : '',
					'_siteseo_robots_index' => !empty($term['wpseo_noindex']) ? 'yes' : '',
					'_siteseo_robots_follow' => !empty($term['wpseo_nofollow']) ? 'yes' : '',
					'_siteseo_robots_imageindex' => !empty($term['wpseo_noimageindex']) ? 'yes' : '',
					'_siteseo_robots_archive' => !empty($term['wpseo_noarchive']) ? 'yes' : '',
					'_siteseo_robots_snippet' => !empty($term['wpseo_nosnippet']) ? 'yes' : '',
					'_siteseo_analysis_target_kw' => !empty($term['wpseo_focuskw']) ? $term['wpseo_focuskw'] : '',
					'_siteseo_robots_primary_cat' => !empty($term['wpseo_primary_category']) ? $term['wpseo_primary_category'] : '',
					'_siteseo_robots_canonical' => !empty($term['wpseo_canonical']) ? $term['wpseo_canonical'] : '',
				];
				
				foreach($meta_mapping as $siteseo_key => $value){
					if(!empty($value)){
						update_term_meta($term_id, $siteseo_key, $value);
						$imported_count++;
					}
				}
				
				$log[] = "Imported term ID: {$term_id}";
			}
		}
		
		return [
			'count' => $imported_count,
			'log' => $log,
			/* translators: %d count of items imported */
			'message' => sprintf(__('Yoast SEO import completed. Imported %d items.', 'siteseo'), $imported_count)
		];
	}

	static function aio_seo(){
		$imported_count = 0;
		$log = [];
		
		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		foreach($posts as $post){

			$meta_mapping = [
				'_siteseo_titles_title' => self::replace_snippet_vars((get_post_meta($post->ID, '_aioseo_title', true)),'aio_seo'),
				'_siteseo_titles_desc' => self::replace_snippet_vars((get_post_meta($post->ID, '_aioseo_description', true)),'aio_seo'),
				'_siteseo_social_fb_title' => self::replace_snippet_vars((get_post_meta($post->ID, '_aioseo_og_title', true)),'aio_seo'),
				'_siteseo_social_fb_desc' => self::replace_snippet_vars((get_post_meta($post->ID, '_aioseo_og_description', true)),'aio_seo'),
				'_siteseo_social_fb_img' => get_post_meta($post->ID, '_aioseo_og_image', true),
				'_siteseo_social_twitter_title' => self::replace_snippet_vars((get_post_meta($post->ID, '_aioseo_twitter_title', true)),'aio_seo'),
				'_siteseo_social_twitter_desc' => self::replace_snippet_vars((get_post_meta($post->ID, '_aioseo_twitter_description', true)),'aio_seo'),
				'_siteseo_social_twitter_img' => get_post_meta($post->ID, '_aioseo_twitter_image', true),
				'_siteseo_robots_index' => get_post_meta($post->ID, '_aioseo_noindex', true) ? '1' : '',
				'_siteseo_robots_follow' => get_post_meta($post->ID, '_aioseo_nofollow', true) ? '1' : '',
				'_siteseo_robots_imageindex' => '1',
				'_siteseo_robots_archive' => '1',
				'_siteseo_robots_snippet' => '1', // Default to 1 if not specified
				'_siteseo_robots_canonical' => get_post_meta($post->ID, '_aioseo_canonical_url', true),
				'_siteseo_analysis_target_kw' => get_post_meta($post->ID, '_aioseo_keywords', true)
			];

			foreach($meta_mapping as $siteseo_key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $siteseo_key, $value);
					$imported_count++;
				}
			}
			
			$log[] = "Imported post ID: {$post->ID}";
		}
		
		$taxonomies = get_taxonomies();
		
		foreach($taxonomies as $taxonomy){
			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
			]);
			
			foreach($terms as $term){
				
				$meta_mapping = [
					'_siteseo_titles_title' => self::replace_snippet_vars((get_term_meta($term->term_id, '_aioseo_title', true)),'aio_seo'),
					'_siteseo_titles_desc' => self::replace_snippet_vars((get_term_meta($term->term_id, '_aioseo_description', true)),'aio_seo'),
					'_siteseo_social_fb_title' => self::replace_snippet_vars((get_term_meta($term->term_id, '_aioseo_og_title', true)),'aio_seo'),
					'_siteseo_social_fb_desc' => self::replace_snippet_vars((get_term_meta($term->term_id, '_aioseo_og_description', true)),'aio_seo'),
					'_siteseo_social_fb_img' => get_term_meta($term->term_id, '_aioseo_og_image', true),
					'_siteseo_social_twitter_title' => self::replace_snippet_vars((get_term_meta($term->term_id, '_aioseo_twitter_title', true)),'aio_seo'),
					'_siteseo_social_twitter_desc' => self::replace_snippet_vars((get_term_meta($term->term_id, '_aioseo_twitter_description', true)),'aio_seo'),
					'_siteseo_social_twitter_img' => get_term_meta($term->term_id, '_aioseo_twitter_image', true),
					'_siteseo_robots_index' => get_term_meta($term->term_id, '_aioseo_noindex', true) ? '1' : '',
					'_siteseo_robots_follow' => get_term_meta($term->term_id, '_aioseo_nofollow', true) ? '1' : '',
					'_siteseo_robots_canonical' => get_term_meta($term->term_id, '_aioseo_canonical_url', true),
					'_siteseo_analysis_target_kw' => get_term_meta($term->term_id, '_aioseo_keywords', true)
				];
				
			
				foreach($meta_mapping as $siteseo_key => $value){
					if(!empty($value)){
						update_term_meta($term->term_id, $siteseo_key, $value);
						$imported_count++;
					}
				}
				
				$log[] = "Imported term ID: {$term->term_id}";
			}
			
		}
		
		return	[
			'count' => $imported_count,
			'log' => $log,
			/* translators: %d count of items imported */
			'message' => sprintf(__('All In One SEO import completed. Imported %d items.', 'siteseo'), $imported_count)
		];
	}

	static function seo_framework(){
		$imported_count = 0;
		$log = [];

		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		foreach($posts as $post){
			$meta_mapping = [
				'_siteseo_titles_title' => get_post_meta($post->ID, '_genesis_title', true),
				'_siteseo_titles_desc' => get_post_meta($post->ID, '_genesis_description', true),
				'_siteseo_social_fb_title' => get_post_meta($post->ID, '_open_graph_title', true),
				'_siteseo_social_fb_desc' => get_post_meta($post->ID, '_open_graph_description', true),
				'_siteseo_social_fb_img' => get_post_meta($post->ID, '_social_image_url', true),
				'_siteseo_social_twitter_title' => get_post_meta($post->ID, '_twitter_title', true),
				'_siteseo_social_twitter_desc' => get_post_meta($post->ID, '_twitter_description', true),
				'_siteseo_social_twitter_img' => get_post_meta($post->ID, '_twitter_image', true),
				'_siteseo_robots_index' => get_post_meta($post->ID, '_genesis_noindex', true) ? '1' : '',
				'_siteseo_robots_follow' => get_post_meta($post->ID, '_genesis_nofollow', true) ? '1' : '',
				'_siteseo_robots_imageindex' => '1',
				'_siteseo_robots_archive' => '1',
				'_siteseo_robots_snippet' => '1',
				'_siteseo_robots_canonical' => get_post_meta($post->ID, '_genesis_canonical_uri', true),
				'_siteseo_analysis_target_kw' => get_post_meta($post->ID, '_genesis_keywords', true)
			];
			
			foreach($meta_mapping as $siteseo_key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $siteseo_key, $value);
					$imported_count++;
				}
			}
			$log[] = "Imported post ID: {$post->ID}";
		}
		
		$taxonomies = get_taxonomies();
		
		foreach($taxonomies as $taxonomy){
			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
			]);
			
			foreach($terms as $term){
				$term_data = get_term_meta($term->term_id, 'autodescription-term-settings', true);
				
				if(empty($term_data) || !is_array($term_data)){
					continue;
				}
				
				$meta_mapping = [
					'_siteseo_titles_title' => !empty($term_data['doctitle']) ? $term_data['doctitle'] : '',
					'_siteseo_titles_desc' => !empty($term_data['description']) ? $term_data['description'] : '',
					'_siteseo_social_fb_title' => !empty($term_data['og_title']) ? $term_data['og_title'] : '',
					'_siteseo_social_fb_desc' => !empty($term_data['og_description']) ? $term_data['og_description'] : '',
					'_siteseo_social_fb_img' => !empty($term_data['social_image_url']) ? $term_data['social_image_url'] : '',
					'_siteseo_social_twitter_title' => !empty($term_data['tw_title']) ? $term_data['tw_title'] : '',
					'_siteseo_social_twitter_desc' => !empty($term_data['tw_description']) ? $term_data['tw_description'] : '',
					'_siteseo_social_twitter_img' => !empty($term_data['social_image_url']) ? $term_data['social_image_url'] : '',
					'_siteseo_robots_index' => !empty($term_data['noindex']) ? true : '',
					'_siteseo_robots_follow' => !empty($term_data['nofollow'] ) ? true : '',
					'_siteseo_robots_archive' => !empty($term_data['noarchive']) ? true : '',
					'_siteseo_robots_canonical' => !empty($term_data['canonical']) ? $term_data['canonical'] : '',
					'_siteseo_analysis_target_kw' => !empty($term_data['keywords']) ? $term_data['keywords'] : '',
				];
			
				foreach($meta_mapping as $siteseo_key => $value){
					if(!empty($value)){
						update_term_meta($term->term_id, $siteseo_key, $value);
						$imported_count++;
					}
				}
				
				$log[] = "Imported term ID: {$term->term_id}";
			}
		}

		return [
			'count' => $imported_count,
			'log' => $log,
			/* translators: %d count of items imported */
			'message' => sprintf(__('SEO Framework import completed. Imported %d items.', 'siteseo'), $imported_count)
		];
	}

	static function seo_press(){
		$imported_count = 0;
		$log = [];

		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		foreach($posts as $post){
			
			$robots_index = get_post_meta($post->ID, '_seopress_robots_index', true);
			$robots_follow = get_post_meta($post->ID, '_seopress_robots_follow', true);
			$robots_imageindex = get_post_meta($post->ID, '_seopress_robots_imageindex', true);
			$robots_archive = get_post_meta($post->ID, '_seopress_robots_archive', true);
			
			$meta_mapping = [
				'_siteseo_titles_title' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_post_meta($post->ID, '_seopress_titles_title', true)),
				'_siteseo_titles_desc' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($post->ID, '_seopress_titles_desc', true)),
				'_siteseo_social_fb_title' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($post->ID, '_seopress_social_fb_title', true)),
				'_siteseo_social_fb_desc' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($post->ID, '_seopress_social_fb_desc', true)),
				'_siteseo_social_fb_img' => get_post_meta($post->ID,'_seopress_social_fb_img',true),
				'_siteseo_social_twitter_title' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($post->ID, '_seopress_social_twitter_title', true)),
				'_siteseo_social_twitter_desc' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($post->ID, '_seopress_social_twitter_desc', true)),
				'_siteseo_social_twitter_img' => get_post_meta($post->ID, '_seopress_social_twitter_img', true),
				'_siteseo_robots_index' => (!empty($robots_index) && $robots_index === 'yes') ? '1' : '0',
				'_siteseo_robots_follow' => (!empty($robots_follow) && $robots_follow === 'yes') ? '1' : '0',
				'_siteseo_robots_imageindex' => (!empty($robots_imageindex) && $robots_imageindex === 'yes') ? '1' : '0',
				'_siteseo_robots_archive' => (!empty($robots_archive) && $robots_archive === 'yes') ? '1' : '0',
				'_siteseo_robots_snippet' => get_post_meta($post->ID, '_seopress_robots_snippet', true),
				'_siteseo_robots_canonical' => get_post_meta($post->ID, '_seopress_robots_canonical', true),
				'_siteseo_analysis_target_kw' => get_post_meta($post->ID, '_seopress_analysis_target_kw', true),
				'_siteseo_redirections_enabled' => get_post_meta($post->ID, '_seopress_redirections_enabled', true) ? '1' : '0',
				'_siteseo_redirections_value' => get_post_meta($post->ID, '_seopress_redirections_value', true),
				'_siteseo_redirections_type' => get_post_meta($post->ID, '_seopress_redirections_type', true),
				'_siteseo_redirections_param' => get_post_meta($post->ID, '_seopress_redirections_param', true),
				'_siteseo_redirections_logged_status' => get_post_meta($post->ID, '_seopress_redirections_logged_status', true),
				'_siteseo_redirections_enabled_regex' => get_post_meta($post->ID, '_seopress_redirections_enabled_regex', true),
	
			];

			foreach($meta_mapping as $siteseo_key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $siteseo_key, $value);
					$imported_count++;
				}
			}
			$log[] = "Imported post ID: {$post->ID}";
		}
		
		$taxonomies = get_taxonomies();
		
		foreach($taxonomies as $taxonomy){
			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
			]);
			
			foreach($terms as $term){
				
				$robots_index = get_term_meta($term->term_id, '_seopress_robots_index', true);
				$robots_follow = get_term_meta($term->term_id, '_seopress_robots_follow', true);
				$robots_imageindex = get_term_meta($term->term_id, '_seopress_robots_imageindex', true);
				$robots_archive = get_term_meta($term->term_id, '_seopress_robots_archive', true);
				
				$meta_mapping = [
					'_siteseo_titles_title' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($term->term_id, '_seopress_titles_title', true)),
					'_siteseo_titles_desc' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($term->term_id, '_seopress_titles_desc', true)),
					'_siteseo_social_fb_title' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($term->term_id, '_seopress_social_fb_title', true)),
					'_siteseo_social_fb_desc' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($term->term_id, '_seopress_social_fb_desc', true)),
					'_siteseo_social_fb_img' => get_term_meta($term->term_id,'_seopress_social_fb_img',true),
					'_siteseo_social_twitter_title' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($term->term_id, '_seopress_social_twitter_title', true)),
					'_siteseo_social_twitter_desc' => str_replace('%%wc_single_price_exc_tax%%', '%%wc_single_price_exe_tax%%', get_term_meta($term->term_id, '_seopress_social_twitter_desc', true)),
					'_siteseo_social_twitter_img' => get_term_meta($term->term_id, '_seopress_social_twitter_img', true),
					'_siteseo_robots_index' => (!empty($robots_index) && $robots_index === 'yes') ? '1' : '0',
					'_siteseo_robots_follow' => (!empty($robots_follow) && $robots_follow === 'yes') ? '1' : '0',
					'_siteseo_robots_imageindex' => (!empty($robots_imageindex) && $robots_imageindex === 'yes') ? '1' : '0',
					'_siteseo_robots_archive' => (!empty($robots_archive) && $robots_archive === 'yes') ? '1' : '0',
					'_siteseo_robots_snippet' => get_term_meta($term->term_id, '_seopress_robots_snippet', true),
					'_siteseo_robots_canonical' => get_term_meta($term->term_id, '_seopress_robots_canonical', true),
					'_siteseo_analysis_target_kw' => get_term_meta($term->term_id, '_seopress_analysis_target_kw', true),
					'_siteseo_redirections_enabled' => get_term_meta($term->term_id, '_seopress_redirections_enabled', true) ? '1' : '0',
					'_siteseo_redirections_value' => get_term_meta($term->term_id, '_seopress_redirections_value', true),
					'_siteseo_redirections_type' => get_term_meta($term->term_id, '_seopress_redirections_type', true),
					'_siteseo_redirections_param' => get_term_meta($term->term_id, '_seopress_redirections_param', true),
					'_siteseo_redirections_logged_status' => get_term_meta($term->term_id, '_seopress_redirections_logged_status', true),
					'_siteseo_redirections_enabled_regex' => get_term_meta($term->term_id, '_seopress_redirections_enabled_regex', true),
		
				];
				
				foreach($meta_mapping as $siteseo_key => $value){
					if(!empty($value)){
						update_term_meta($term->term_id, $siteseo_key, $value);
						$imported_count++;
					}
				}
				
				$log[] = "Imported term ID: {$term->term_id}";
			}
		}

		return [
			'count' => $imported_count,
			'log' => $log,
			/* translators: %d count of items imported */
			'message' => sprintf(__('SEOPress migration completed. Imported %d items.', 'siteseo'), $imported_count)
		];
	}
	
	static function slim_seo(){
		$imported_count = 0;
		$log = [];
		
		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		
		$taxonomies = get_taxonomies(array('public' => true), 'objects');
		
		foreach($posts as $post){
			$post_data = get_post_meta($post->ID, 'slim_seo', true);
			
			if(empty($post_data) || !is_array($post_data)){
				continue;
			}
			
			$meta_mapping = [
				'_siteseo_titles_title' => self::replace_snippet_vars((!empty($post_data['title']) ? $post_data['title'] : ''),'slim_seo'),
				'_siteseo_titles_desc' => self::replace_snippet_vars((!empty($post_data['description']) ? $post_data['description'] : ''),'slim_seo'),
				'_siteseo_social_fb_img' => !empty($post_data['facebook_image']) ? $post_data['facebook_image'] : '',
				'_siteseo_social_twitter_img' => !empty($post_data['twitter_image']) ? $post_data['twitter_image'] : '',
				'_siteseo_robots_index'         => isset($post_data['noindex']) && $post_data['noindex'] ? '1' : '',
				'_siteseo_robots_canonical' => !empty($post_data['canonical']) ? $post_data['canonical'] : '',
			];
			
			foreach($meta_mapping as $siteseo_key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $siteseo_key, $value);
					$imported_count++;
				}
			}
			
			$log[] = "Imported post ID: {$post->ID}";
		}
		
		$taxonomies = get_taxonomies();
		
		foreach($taxonomies as $taxonomy){
			
			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
			]);
        
			foreach($terms as $term){
				$term_data = get_term_meta($term->term_id, 'slim_seo', true);
				
				if(empty($term_data) || !is_array($term_data)){
					continue;
				}
				
				$meta_mapping = [
					'_siteseo_titles_title' => self::replace_snippet_vars((!empty($term_data['title']) ? $term_data['title'] : ''),'slim_seo'),
					'_siteseo_titles_desc' => self::replace_snippet_vars((!empty($term_data['description']) ? $term_data['description'] : ''),'slim_seo'),
					'_siteseo_social_fb_img' => !empty($term_data['facebook_image']) ? $term_data['facebook_image'] : '',
					'_siteseo_social_twitter_img' => !empty($term_data['twitter_image']) ? $term_data['twitter_image'] : '',
					'_siteseo_robots_index' => !empty($term_data['noindex']) ? true : '',
					'_siteseo_robots_canonical' => !empty($term_data['canonical']) ? $term_data['canonical'] : '',
				];
				
				foreach($meta_mapping as $siteseo_key => $value){
					if(!empty($value)){
						update_term_meta($term->term_id, $siteseo_key, $value);
						$imported_count++;
					}
				}
				
				$log[] = "Imported term ID: {$term->term_id}";
			}
		}
		
		return [
			'count' => $imported_count,
			'log' => $log,
			/* translators: %d count of items imported */
			'message' => sprintf(__('Slim SEO import completed. Imported %d items.', 'siteseo'), $imported_count)
		];
	}

	static function replace_snippet_vars($string, $plugin){
		if(empty($string)) return $string;
		
		// We should not process the string if it does not have a variable
		if(!preg_match('/%|{|#/', $string)){
			return $string;
		}

		static $plugins = [
			'rank_math' => [
				'%sep%' => '%%sep%%',
				'%sitename%' => '%%sitetitle%%',
				'%sitedesc%' => '%%tagline%%',
				'%title%' => '%%post_title%%',
				'%excerpt%' => '%%post_excerpt%%',
				'%excerpt_only%' => '%%post_excerpt%%',
				'%post_thumbnail%' => '%%post_thumbnail_url%%',
				'%url%' => '%%post_url%%',
				'%date%' => '%%post_date%%',
				'%modified%' => '%%post_modified_date%%',
				'%name%' => '%%post_author%%',
				'%category%' => '%%post_category%%',
				'%tag%' => '%%post_tag%%',
				'%term%' => '%%term_title%%',
				'%term_description%' => '%%term_description%%',
				'%search_query%' => '%%search_keywords%%',
				'%pagenumber%' => '%%current_pagination%%',
				'%page%' => '%%page%%',
				'%archive_title%' => '%%archive_title%%',
				'%sitename_with_sep%' => '%%sep%%%%sitetitle%%',
				'%pt_plural%' => '%%cpt_plural%%',
				'%wc_shortdesc%' => '%%wc_single_short_desc%%',
				'%wc_price%' => '%%wc_single_price%%',
				'%wc_sku%' => '%%wc_sku%%',
				'%currentday%' => '%%currentday%%',
				'%currentmonth%' => '%%currentmonth%%',
				'%currentyear%' => '%%currentyear%%',
				'%currentdate%' => '%%currentdate%%',
				'%currenttime%' => '%%currenttime%%',
				'%user_description%' => '%%author_bio%%',
				'%focuskw%' => '%%target_keyword%%'
			],
			'yoast_seo' => [
				'%%sitename%%' => '%%sitetitle%%',
				'%%sitedesc%%' => '%%tagline%%',
				'%%title%%' => '%%post_title%%',
				'%%excerpt%%' => '%%post_excerpt%%',
				'%%excerpt_only%%' => '%%post_excerpt%%',
				'%%content%%' => '%%post_content%%',
				'%%thumbnail%%' => '%%post_thumbnail_url%%',
				'%%url%%' => '%%post_url%%',
				'%%date%%' => '%%post_date%%',
				'%%modified%%' => '%%post_modified_date%%',
				'%%author%%' => '%%post_author%%',
				'%%category%%' => '%%post_category%%',
				'%%primary_category%%' => '%%post_category%%',
				'%%tag%%' => '%%post_tag%%',
				'%%category_description%%' => '%%_category_description%%',
				'%%searchphrase%%' => '%%search_keywords%%',
				'%%pagenumber%%' => '%%current_pagination%%',
				'%%year%%' => '%%currentyear%%',
				'%%month%%' => '%%currentmonth%%',
				'%%day%%' => '%%currentday%%',
				'%%author_firstname%%' => '%%author_first_name%%',
				'%%author_lastname%%' => '%%author_last_name%%',
				'%%author_description%%' => '%%author_bio%%',
				'%%product_sku%%' => '%%wc_sku%%',
				'%%product_price%%' => '%%wc_single_price%%',
				'%%product_shortdesc%%' => '%%wc_single_short_desc%%',
				'%%product_category%%' => '%%wc_single_cat%%',
				'%%product_tag%%' => '%%wc_single_tag%%'
			],
			'aio_seo' => [
				'#separator_sa' => '%%sep%%',
				'#site_title' => '%%sitetitle%%',
				'#tagline' => '%%tagline%%',
				'#post_title' => '%%post_title%%',
				'#post_excerpt' => '%%post_excerpt%%',
				'#post_content' => '%%post_content%%',
				'#post_thumbnail' => '%%post_thumbnail_url%%',
				'#post_url' => '%%post_url%%',
				'#post_date' => '%%post_date%%',
				'#post_modified_date' => '%%post_modified_date%%',
				'#categories' => '%%post_category%%',
				'#post_tags' => '%%post_tag%%',
				'#author_name' => '%%post_author%%',
				'#taxonomy_title' => '%%post_category%%',
				'#tag_title' => '%%post_tag%%',
				'#term_name' => '%%term_title%%',
				'#term_description' => '%%term_description%%',
				'#search_query' => '%%search_keywords%%',
				'#page_number' => '%%page%%',
				'#pt_plural' => '%%cpt_plural%%',
				'#wc_short_description' => '%%wc_single_short_desc%%',
				'#wc_price' => '%%wc_single_price%%',
				'#wc_sku' => '%%wc_sku%%',
				'#current_day' => '%%currentday%%',
				'#current_month' => '%%currentmonth%%',
				'#current_year' => '%%currentyear%%',
				'#current_date' => '%%currentdate%%',
				'#current_time' => '%%currenttime%%',
				'#author_first_name' => '%%author_first_name%%',
				'#author_last_name' => '%%author_last_name%%',
				'#author_description' => '%%author_bio%%',
				'#focus_keyword' => '%%target_keyword%%',
				'#custom_field' => '%%_cf_your_custom_field_name%%'
			],
			'slim_seo' => [
				'{{ sep }}' => '%%sep%%',
				'{{ site.title }}' => '%%sitetitle%%',
				'{{ post.title }}' => '%%post_title%%',
				'{{ post.excerpt }}' => '%%post_excerpt%%',
				'{{ post.content }}' => '%%post_content%%',
				'{{ post_type.plural }}' => '%%cpt_plural%%',
				'{{ post.thumbnail }}' => '%%post_thumbnail_url%%',
				'{{ post.url }}' => '%%post_url%%',
				'{{ post.date }}' => '%%post_date%%',
				'{{ post.modified_date }}' => '%%post_modified_date%%',
				'{{ author.display_name }}' => '%%post_author%%',
				'{{ post.categories }}' => '%%post_category%%',
				'{{ post.tags }}' => '%%post_tag%%',
				'{{ term.name }}' => '%%term_title%%',
				'{{ term.description }}' => '%%term_description%%',
				'{{ page }}' => '%%page%%',
				'{{ current.month }}' => '%%currentmonth%%',
				'{{ current.day }}' => '%%currentday%%',
				'{{ current.year }}' => '%%currentyear%%',
				'{{ author.first_name }}' => '%%author_first_name%%',
				'{{ author.last_name }}' => '%%author_last_name%%',
				'{{ author.website_url }}' => '%%author_website%%',
				'{{ author.nickname }}' => '%%author_nickname%%',
				'{{ author.description }}' => '%%author_bio%%'
			],
			'surerank' => [
				'%site_name%'=> '%%sitetitle%%',
				'%title%' => '%%post_title%%',
				'%tagline%' => '%%tagline%%',
				'%post_url%' => '%%post_url%%',
				'%content%' => '%%post_content%%',
				'%sep%' => '%%sep%%',
				'%excerpt%' => '%%post_excerpt%%',
				'%author_name%' => '%%post_author%%',
				'%category%' => '%%post_category%%',
				'%tag%' => '%%post_tag%%',
				'%term_title%' => '%%term_title%%',
				'%term_description%' => '%%term_description%%',
				'%modified%' => '%%post_modified_date%%',
				'%currentdate%' => '%%currentday%%',
				'%currentmonth%' => '%%currentmonth%%',
				'%currentyear%' => '%%currentyear%%',
				
			]
		];

		if(!empty($plugins[$plugin])){
			return str_replace(array_keys($plugins[$plugin]), array_values($plugins[$plugin]), $string);
		}

		return $string;
	}

	static function surerank(){

		$imported_count = 0;
		$log = [];

		$posts = get_posts(['posts_per_page' => -1, 'post_type' => 'any', 'post_status' => 'any']);
		foreach($posts as $post){

			$general = get_post_meta($post->ID, 'surerank_settings_general', true);
			$general = is_array($general) ? $general : [];
			$social = get_post_meta($post->ID, 'surerank_settings_social', true);
			$social = is_array($social) ? $social : [];
			$noindex = get_post_meta($post->ID, 'surerank_settings_post_no_index', true);
			$nofollow = get_post_meta($post->ID, 'surerank_settings_post_no_follow', true);
			$noarchive = get_post_meta($post->ID, 'surerank_settings_post_no_archive', true);

			$meta_mapping = [

				'_siteseo_titles_title' => !empty($general['page_title']) ? self::replace_snippet_vars($general['page_title'], 'surerank') : '',
				'_siteseo_titles_desc' => !empty($general['page_description']) ? self::replace_snippet_vars($general['page_description'], 'surerank') : '',
				'_siteseo_robots_canonical' => !empty($general['canonical_url']) ? $general['canonical_url'] : '',
				'_siteseo_social_fb_title' => !empty($social['facebook_title']) ? self::replace_snippet_vars($social['facebook_title'], 'surerank') : '',
				'_siteseo_social_fb_desc' => !empty($social['facebook_description']) ? self::replace_snippet_vars($social['facebook_description'], 'surerank') : '',			
				'_siteseo_social_twitter_title' => !empty($social['twitter_title']) ? self::replace_snippet_vars($social['twitter_title'], 'surerank') : '',
				'_siteseo_social_twitter_desc' => !empty($social['twitter_description']) ? self::replace_snippet_vars($social['twitter_description'], 'surerank') : '',
				'_siteseo_social_fb_img' => !empty($social['facebook_image_url']) ? $social['facebook_image_url'] : '',
				'_siteseo_social_twitter_img' => !empty($social['twitter_image_url']) ? $social['twitter_image_url'] : '',

				// Robots
				'_siteseo_robots_index' => ($noindex === 'yes')   ? '1' : '',
				'_siteseo_robots_follow' => ($nofollow === 'yes')  ? '1' : '',
				'_siteseo_robots_archive' => ($noarchive === 'yes') ? '1' : '',
			];

			foreach($meta_mapping as $key => $value){
				if(!empty($value)){
					update_post_meta($post->ID, $key, $value);
					$imported_count++;
				}
			}

			$log[] = "Imported SureRank post ID: {$post->ID}";
		}
		
		$taxonomies = get_taxonomies();

		foreach($taxonomies as $taxonomy){

			$terms = get_terms([
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]);

			if(is_wp_error($terms) || empty($terms)){
				continue;
			}

			foreach($terms as $term){

				$general = get_term_meta($term->term_id, 'surerank_settings_general', true);
				$general = is_array($general) ? $general : [];
				$social = get_term_meta($term->term_id, 'surerank_settings_social', true);
				$social = is_array($social) ? $social : [];
				$noindex = get_term_meta($term->term_id, 'surerank_settings_term_no_index', true);
				$nofollow = get_term_meta($term->term_id, 'surerank_settings_term_no_follow', true);
				$noarchive = get_term_meta($term->term_id, 'surerank_settings_term_no_archive', true);

				$meta_mapping = [

					// Titles
					'_siteseo_titles_title' => !empty($general['page_title']) ? self::replace_snippet_vars($general['page_title'], 'surerank') : '',
					'_siteseo_titles_desc' => !empty($general['page_description']) ? self::replace_snippet_vars($general['page_description'], 'surerank') : '',
					'_siteseo_robots_canonical' => !empty($general['canonical_url']) ? $general['canonical_url'] : '',
					'_siteseo_social_fb_title' => !empty($social['facebook_title']) ? self::replace_snippet_vars($social['facebook_title'], 'surerank') : '',
					'_siteseo_social_fb_desc' => !empty($social['facebook_description']) ? self::replace_snippet_vars($social['facebook_description'], 'surerank') : '',
					'_siteseo_social_twitter_title' => !empty($social['twitter_title']) ? self::replace_snippet_vars($social['twitter_title'], 'surerank') : '',
					'_siteseo_social_twitter_desc' => !empty($social['twitter_description']) ? self::replace_snippet_vars($social['twitter_description'], 'surerank') : '',
					'_siteseo_social_fb_img' => !empty($social['facebook_image_url']) ? $social['facebook_image_url'] : '',
					'_siteseo_social_twitter_img' => !empty($social['twitter_image_url']) ? $social['twitter_image_url'] : '',

					// Robots
					'_siteseo_robots_index'   => ($noindex === 'yes')   ? '1' : '',
					'_siteseo_robots_follow'  => ($nofollow === 'yes')  ? '1' : '',
					'_siteseo_robots_archive' => ($noarchive === 'yes') ? '1' : '',
				];

				foreach($meta_mapping as $key => $value){
					if(!empty($value)){
						update_term_meta($term->term_id, $key, $value);
						$imported_count++;
					}
				}

				$log[] = "Imported SureRank term ID: {$term->term_id}";
			}
		}

		return [
			'count' => $imported_count,
			'log' => $log,
			'message' => sprintf(__('SureRank import completed. Imported %d items.', 'siteseo'), $imported_count),
		];
	}
}
