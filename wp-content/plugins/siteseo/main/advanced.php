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

class Advanced{
	
	static function tags(){
		global $siteseo;

		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return; // toggle disable
		}
		// meta tags
		if(!empty($siteseo->advanced_settings['advanced_google'])){
			echo '<meta name="google-site-verification" content="'.esc_attr($siteseo->advanced_settings['advanced_google']).'" />' . "\n";
		}

		if(!empty($siteseo->advanced_settings['advanced_bing'])){
			echo '<meta name="msvalidate.01" content="'.esc_attr($siteseo->advanced_settings['advanced_bing']).'" />' . "\n";
		}

		if(!empty($siteseo->advanced_settings['advanced_pinterest'])){
			echo '<meta name="p:domain_verify" content="'.esc_attr($siteseo->advanced_settings['advanced_pinterest']).'" />';
		}

		if(!empty($siteseo->advanced_settings['advanced_yandex'])){
			echo '<meta name="yandex-verification" content="'.esc_attr($siteseo->advanced_settings['advanced_yandex']).'" />';
		}

		if(!empty($siteseo->advanced_settings['advanced_wp_rsd'])){
			remove_action('wp_head', 'rsd_link');
		}

	}
	
	static function remove_links(){
		global $siteseo;

		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return; // toggle disable
		}

		if(!empty($siteseo->advanced_settings['advanced_wp_rsd'])){
			remove_action('wp_head', 'rsd_link');
		}

		if(!empty($siteseo->advanced_settings['advanced_wp_wlw'])){
			remove_action('wp_head', 'wlwmanifest_link');
		}

		if(!empty($siteseo->advanced_settings['advanced_wp_shortlink'])){
			remove_action('wp_head', 'wp_shortlink_wp_head');
		}

		if(!empty($siteseo->advanced_settings['advanced_wp_generator'])){
			remove_action('wp_head', 'wp_generator');
		}

		if(!empty($siteseo->advanced_settings['advanced_comments_form_link'])){
			add_filter('comment_form_default_fields', '\SiteSEO\Advanced::remove_comment_url_field');
		}

		if(!empty($siteseo->advanced_settings['advanced_comments_author_url'])){
			add_filter('get_comment_author_link', '\SiteSEO\Advanced::remove_author_link_if_profile_url');
		}

		if(!empty($siteseo->advanced_settings['advanced_hentry'])){
			add_filter('post_class', '\SiteSEO\Advanced::remove_hentry_post_class');
		}

		if(!empty($siteseo->advanced_settings['advanced_noreferrer'])){
			add_filter('the_content', '\SiteSEO\Advanced::remove_noreferrer_from_post_content');
		}

		if(!empty($siteseo->advanced_settings['advanced_tax_desc_editor'])){
			add_action('edit_term', '\SiteSEO\Advanced::add_wp_editor_to_taxonomy_description', 10, 2);
		}
		
		if(!empty($siteseo->advanced_settings['advanced_category_url'])){
			add_action('init', '\SiteSEO\Advanced::remove_category_base', 111);
			add_action('template_redirect', '\SiteSEO\Advanced::redirect_category');
		}
	}
	
	static function add_wp_editor_to_taxonomy_description($tag, $tt_id = 0){

		if('edit' !== get_current_screen()->base || 'edit-tags' !== get_current_screen()->id){
			return;
		}

		if(isset($tag->description)){
			$editor_settings = array(
				'textarea_name' => 'description',
				'textarea_rows' => 10,
				'editor_class' => 'wp-editor-area',
				'media_buttons' => true,
				'tinymce' => true,
				'quicktags' => true,
			);

			wp_editor($tag->description, 'description', $editor_settings);
		}
	}

	static function remove_noreferrer_from_post_content($content){
		$content = preg_replace('/(<a\s+[^>]*rel=["\'][^"\']*?)(\s*\bnoreferrer\b\s*)([^"\']*["\'][^>]*>)/i', '$1$3', $content);
		return $content;
	}

	static function remove_hentry_post_class($classes){
		$classes = array_diff($classes, array('hentry'));
		return $classes;
	}

	static function remove_comment_url_field($fields){
		if(isset($fields['url'])){
			unset($fields['url']);
		}

		return $fields;
	}

	static function remove_author_link_if_profile_url($comment_author_link = '', $comment_author = '', $comment_id = 0){
		if(empty($comment_id)){
			return $comment_author;
		}

		$comment = get_comment($comment_id);
		
		if(empty($comment) || !is_object($comment)){
			return $comment_author;
		}

		$user_id = $comment->user_id;

		if(!empty($user_id)){
			$user_website = get_the_author_meta('user_url', $user_id);

			if($user_website){
				return get_comment_author($comment_id);
			}
		}

		return $comment_author;
	}
	
	static function remove_category_base(){
		
		$categories = get_categories(array('hide_empty' => false));
		$category_slugs = wp_list_pluck($categories, 'slug');

		if(empty($category_slugs)){
			return;
		}
    
		$category_pattern = '(' . implode('|', $category_slugs) .')';
    
		add_rewrite_rule(
			'^'.$category_pattern.'/?$',
			'index.php?category_name=$matches[1]',
			'top'
		);
		
		// Add rule for handle pagination  
		add_rewrite_rule(
			'^'.$category_pattern.'/page/([0-9]+)/?$',
			'index.php?category_name=$matches[1]&paged=$matches[2]',
			'top'
		);
	}
	
	static function redirect_category(){
		if(is_category() && !is_admin()){
			$category = get_query_var('category_name');
			$category_base = get_option('category_base');
			$base_to_check = !empty($category_base) ? $category_base : 'category';

			if(!empty($category) && strpos(sanitize_url($_SERVER['REQUEST_URI']), '/'.$base_to_check.'/') !== false){
				wp_safe_redirect(home_url('/' . $category . '/'), 301);
				exit;
			}
		}
	}
		
	static function remove_wc_category_base(){
		global $siteseo;

		if(empty($siteseo->advanced_settings['advanced_product_cat_url']) || empty($siteseo->setting_enabled['toggle-advanced'])){
			return;
		}
		
		if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
			return;
		}
		
		add_filter('term_link', '\SiteSEO\Advanced::remove_category_base_woo', 10, 3);
		add_filter('request', '\SiteSEO\Advanced::category_url_request');
		add_action('created_product_cat', 'flush_rewrite_rules');
		add_action('delete_product_cat', 'flush_rewrite_rules');
		add_action('edited_product_cat', 'flush_rewrite_rules');
		add_action('parse_request', '\SiteSEO\Advanced::old_category_url_request');
		
	}
	
	static function remove_category_base_woo($termlink, $term, $taxonomy){
		if($taxonomy === 'product_cat'){
			$category_base = '/product-category/';
			return str_replace($category_base, '/', $termlink);
		}

		return $termlink;
	}
	
	static function category_url_request($query_vars){
		if(!isset($query_vars['product_cat']) && isset($query_vars['pagename'])){
			$pagename = $query_vars['pagename'];
			$term = get_term_by('slug', $pagename, 'product_cat');

			if($term){
				$query_vars['product_cat'] = $term->slug;
				unset($query_vars['pagename']);
			}
		}

		return $query_vars;
	}
	
	static function old_category_url_request($wp){
		
		if(!isset($wp->query_vars['pagename'])){
			return;
		}
		
		$pagename = $wp->query_vars['pagename'];
		$term = get_term_by('slug', $pagename, 'product_cat');
		
		if($term){
			$wp->query_vars['product_cat'] = $term->slug;
			unset($wp->query_vars['pagename']);
		}
		
	}
}
