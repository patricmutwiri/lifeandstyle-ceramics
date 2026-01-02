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

class ImageSeo{

	static function init(){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return; // toggle disable
		}
		
		if(!empty($siteseo->advanced_settings['advanced_attachments'])){
			add_action('template_redirect', '\SiteSEO\ImageSeo::redirect_attachment_to_parent');
		}

		if(!empty($siteseo->advanced_settings['advanced_clean_filename'])){
			add_filter('sanitize_file_name', '\SiteSEO\ImageSeo::clean_media_filename', 10, 1);
		}

		if(!empty($siteseo->advanced_settings['advanced_image_auto_alt_editor']) ||
			!empty($siteseo->advanced_settings['advanced_image_auto_caption_editor']) ||
			!empty($siteseo->advanced_settings['advanced_image_auto_desc_editor']) || 
			!empty($siteseo->advanced_settings['advanced_image_auto_title_editor'])
		){
			add_action('add_attachment', '\SiteSEO\ImageSeo::set_image_content');
		}
	}
	
	static function set_image_content($attachment_id){
		global $siteseo;

		if(!wp_attachment_is_image($attachment_id)){
			return;
		}
		
		$attachment = get_post($attachment_id);
		$file_name = pathinfo($attachment->guid, PATHINFO_FILENAME);
		$file_name = sanitize_file_name($file_name);
		$file_name = ucwords(str_replace(['-', '_'], ' ', $file_name));
		
		// WooCommerce product img
		$is_woocommerce_product_image = false;
		$product_title = '';
		
		$parent_id = $attachment->post_parent;
		if(!empty($parent_id)){
			$parent_post = get_post($parent_id);
			if(!empty($parent_post) && $parent_post->post_type === 'product'){
				$is_woocommerce_product_image = true;
				$product_title = get_the_title($parent_id);
			}
		}
		
		$file_name = $is_woocommerce_product_image ? $product_title : $file_name;
		
		// Adding alt text to the image
		if(!empty($siteseo->advanced_settings['advanced_image_auto_alt_editor'])){
			update_post_meta($attachment_id, '_wp_attachment_image_alt', $file_name);
		}

		$options = [];
		$options['ID'] = $attachment_id;

		// Adding Title to the image
		if(!empty($siteseo->advanced_settings['advanced_image_auto_title_editor'])){
			$options['post_title'] = $file_name;
		}

		// Adding Img Caption
		if(!empty($siteseo->advanced_settings['advanced_image_auto_caption_editor'])){
			$options['post_content'] = $file_name;
		}

		// Adding Img Caption
		if(!empty($siteseo->advanced_settings['advanced_image_auto_desc_editor'])){
			$options['post_excerpt'] = $file_name;
		}

		if(count($options) > 1){
			wp_update_post($options);
		}
	}
	
	static function clean_media_filename($filename){
		$filename = strtolower($filename);		
		$filename = preg_replace('/[^a-z0-9-._]+/', '-', $filename);
		$filename = trim($filename, '-.');

		return $filename;
	}

	static function redirect_attachment_to_parent(){

		if(is_attachment()){
 
			$attachment_id = get_queried_object_id();
			$parent_id = wp_get_post_parent_id($attachment_id);

			if($parent_id){
				wp_redirect(get_permalink($parent_id));

			}else{
				wp_redirect(home_url());
			}

			exit; 
		}
	}
}
