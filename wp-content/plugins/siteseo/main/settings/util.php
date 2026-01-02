<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO\Settings;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Util{

	static function clean_text($text){
		return sanitize_text_field(wp_unslash($text));
	}

	static function clean_url($url){
		if(is_array($url)){
			return map_deep(wp_unslash($url), 'sanitize_url');
		}

		return sanitize_url(wp_unslash($url));
	}

	static function render_toggle($title, $toggle_key, $toggle_state, $nonce, $label = false){
		$is_active = $toggle_state ? 'active' : '';
		$state_text = $toggle_state ? 'Disable' : 'Enable';

		// for dashbord screen
		if(!empty($label)){
			echo '<div class="siteseo-toggle-cnt">
				<div class="siteseo-toggle-Sw '.esc_attr($is_active).'" id="siteseo-toggleSw-' . esc_attr($toggle_key) . '" data-nonce="'.esc_attr($nonce).'" data-toggle-key="'.esc_attr($toggle_key).'" data-action="siteseo_save_'.esc_attr($toggle_key).'"></div>
				<input type="hidden" name="siteseo_options['.esc_attr($toggle_key) . ']" id="'.esc_attr($toggle_key).'" value="'.esc_attr($toggle_state).'">
			</div>';
		}else{

			echo '<div class="siteseo-toggle-cnt">
				<span id="siteseo-tab-title"><strong>'.esc_html($title).'</strong></span>
				<div class="siteseo-toggle-Sw '.esc_attr($is_active).'" id="siteseo-toggleSw-'.esc_attr($toggle_key).'" data-nonce="' . esc_attr($nonce) . '" data-toggle-key="'.esc_attr($toggle_key).'" data-action="siteseo_save_'.esc_attr($toggle_key).'"></div>
				<span id="siteseo-arrow-icon" class="dashicons dashicons-arrow-left-alt siteseo-arrow-icon"></span>
				<p class="toggle_state_'.esc_attr($toggle_key).'">'.esc_html($state_text).'</p>
				<input type="hidden" name="siteseo_options['.esc_attr($toggle_key).']" id="'.esc_attr($toggle_key).'" value="'.esc_attr($toggle_state).'">
			</div>';
		}
	}
	
	static function admin_header(){
		echo '<div class="siteseo-navbar">
			<div class="logo">
				<img alt="'.esc_html__('siteseo logo', 'siteseo').'" height="30" src="'. esc_url(SITESEO_ASSETS_URL).'/img/logo-24.svg'.'" width="40"/>
				<div class="siteseo-breadcrumb">
					<a href="#">'.esc_html__('Home', 'siteseo').'</a>
					<span>/</span>
					<a class="active" href="">'.esc_html(get_admin_page_title()).'</a>
				</div>
			</div>';
			
			echo'<div class="links">
					<span class="siteseo-header-version-badge">v'.esc_html(SITESEO_VERSION).'</span>
					<a target="_blank" href="https://siteseo.io/docs/">'.esc_html__('Docs', 'siteseo').'</a>';
					
					if(!defined('SITEPAD')){
						echo'<a target="_blank" class="support" href="https://softaculous.deskuss.com/open.php?topicId=22">'.esc_html__('Support', 'siteseo').'</a>';
					}
				echo'</div>
			</div>';
	}
	
	static function importable_plugins(){
		return [
			'wordpress-seo/wp-seo.php' => 'Yoast SEO',
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All In One SEO',
			'autodescription/autodescription.php' => 'The SEO Framework',
			'seo-by-rank-math/rank-math.php' => 'Rank Math',
			'wp-seopress/seopress.php' => 'SEOPress',
			'slim-seo/slim-seo.php' => 'Slim SEO',
			'surerank/surerank.php' => 'Surerank'
		];
	}

	static function submit_btn($value = ''){
		echo '<div class="siteseo-submit-button"><input type="submit" id="submit" name="submit" value="'.esc_attr($value ?: 'Save changes') . '" class="submit-button"></div>';
	}
	
	static function extract_content($input){

		if(preg_match('/content=["\']([^"\']+)["\']/', $input, $matches)){
			return $matches[1];
		}
		
		return $input;
	}
}