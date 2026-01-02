<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO\Metaboxes;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Settings{

	static function render_metabox(){
		global $siteseo;

		$metabox_data = [];
		
		$show_content_analysis = true;
		
		if(is_user_logged_in()){
			
			if(is_super_admin()){
				
				$siteseo->display_ca_metaboxe = 1;
				$show_content_analysis = true;
			} else{
				$user = wp_get_current_user();
				$siteseo_user_role = current($user->roles);
				$siteseo_options = get_option('siteseo_advanced_option_name');
				
				$ca_metabox_roles = !empty($siteseo_options['security_metaboxe_ca_role']) ? $siteseo_options['security_metaboxe_ca_role'] : [];
				
				if(array_key_exists($siteseo_user_role, $ca_metabox_roles)){
					$siteseo->display_ca_metaboxe = 1;
					$show_content_analysis = false;
				}
			}
		}
		
		$metabox_data = self::metabox_data();
		self::metabox_form_html($metabox_data, $show_content_analysis);
	}
	
	static function metabox_data(){
		global $post, $siteseo;

		$metabox_data = [];
		$metabox_data['title'] = $post->post_title;
		$metabox_data['excerpt'] = $post->post_excerpt;

		// Getting the first paragraph of the post
		if(empty($metabox_data['excerpt'])){
			$post_seperated = get_extended($post->post_content);

			if(!empty($post_seperated['main'])){
				$metabox_data['excerpt'] = wp_strip_all_tags($post_seperated['main']);
			}
		}

		$metabox_data['keywords'] = get_post_meta($post->ID, 'siteseo_analysis_target_kw',true);
		$metabox_data['meta_title'] = get_post_meta($post->ID, '_siteseo_titles_title', true);
		$metabox_data['meta_desc'] = get_post_meta($post->ID, '_siteseo_titles_desc', true);
		$metabox_data['robots_canonical'] = get_post_meta($post->ID, '_siteseo_robots_canonical', true);
		$metabox_data['robots_primary_cat'] = get_post_meta($post->ID, '_siteseo_robots_primary_cat', true);
		$metabox_data['fb_title'] = get_post_meta($post->ID, '_siteseo_social_fb_title', true);
		$metabox_data['fb_desc'] = get_post_meta($post->ID, '_siteseo_social_fb_desc', true);
		$metabox_data['fb_img'] = get_post_meta($post->ID, '_siteseo_social_fb_img', true);
		$siteseo_social_fb_img_attachment_id = get_post_meta($post->ID, '_siteseo_social_fb_img_attachment_id', true);
		$siteseo_social_fb_img_width = get_post_meta($post->ID, '_siteseo_social_fb_img_width', true);
		$siteseo_social_fb_img_height = get_post_meta($post->ID, '_siteseo_social_fb_img_height', true);
		$metabox_data['x_title'] = get_post_meta($post->ID, '_siteseo_social_twitter_title', true);
		$metabox_data['x_desc'] = get_post_meta($post->ID, '_siteseo_social_twitter_desc', true);
		$metabox_data['x_img'] = get_post_meta($post->ID, '_siteseo_social_twitter_img', true);
		$siteseo_social_twitter_img_attachment_id = get_post_meta($post->ID, '_siteseo_social_twitter_img_attachment_id', true);
		$siteseo_social_twitter_img_width	= get_post_meta($post->ID, '_siteseo_social_twitter_img_width', true);
		$siteseo_social_twitter_img_height	= get_post_meta($post->ID, '_siteseo_social_twitter_img_height', true);
		$metabox_data['redirections_enabled'] = get_post_meta($post->ID, '_siteseo_redirections_enabled', true);
		$metabox_data['redirections_enabled_regex']	= get_post_meta($post->ID, '_siteseo_redirections_enabled_regex', true);
		$metabox_data['redirections_logged_status']	= get_post_meta($post->ID, '_siteseo_redirections_logged_status', true);
		$metabox_data['redirections_type'] = get_post_meta($post->ID, '_siteseo_redirections_type', true);
		$metabox_data['redirections_value'] = get_post_meta($post->ID, '_siteseo_redirections_value', true);
		$metabox_data['redirections_param'] = get_post_meta($post->ID, '_siteseo_redirections_param', true);

		$title_options = get_option('siteseo_titles_option_name', []);
		$metabox_data['disabled_robots'] = [
			'robots_index' => '',
			'robots_follow' => '',
			'archive' => '',
			'snippet' => '',
			'imageindex' => '',
		];

		if(post_password_required($post->ID) === true || !empty($title_options['titles_noindex'])){
			$metabox_data['robots_index'] = 'yes';
			$metabox_data['disabled_robots']['robots_index'] = 'disabled';
		} else{
			$metabox_data['robots_index'] = get_post_meta($post->ID, '_siteseo_robots_index', true);
		}
		
		if(!empty($title_options['titles_nofollow'])){
			$metabox_data['robots_follow'] = 'yes';
			$metabox_data['disabled_robots']['robots_follow'] = 'disabled';
		} else{
			$metabox_data['robots_follow'] = get_post_meta($post->ID, '_siteseo_robots_follow', true);
		}
		
		if(!empty($title_options['titles_noarchive'])){
			$metabox_data['robots_archive'] = 'yes';
			$metabox_data['disabled_robots']['archive'] = 'disabled';
		} else{
			$metabox_data['robots_archive'] = get_post_meta($post->ID, '_siteseo_robots_archive', true);
		}

		if(!empty($title_options['titles_nosnippet'])){
			$metabox_data['robots_snippet'] = 'yes';
			$metabox_data['disabled_robots']['snippet'] = 'disabled';
		} else{
			$metabox_data['robots_snippet'] = get_post_meta($post->ID, '_siteseo_robots_snippet', true);
		}

		if(!empty($title_options['titles_noimageindex'])){
			$metabox_data['robots_imageindex'] = 'yes';
			$metabox_data['disabled_robots']['imageindex'] = 'disabled';
		} else{
			$metabox_data['robots_imageindex'] = get_post_meta($post->ID, '_siteseo_robots_imageindex', true);
		}

		return $metabox_data;
	}
	
	static function metabox_term_data($term){
		global $tag;

		$metabox_data = [];

		$metabox_data['title'] = $tag->name;
		$metabox_data['excerpt'] = $tag->description;
		$metabox_data['meta_title'] = get_term_meta($term->term_id, '_siteseo_titles_title', true);
		$metabox_data['meta_desc'] = get_term_meta($term->term_id, '_siteseo_titles_desc', true);

		// Social Fields
		$metabox_data['fb_title'] = get_term_meta($term->term_id, '_siteseo_social_fb_title', true);
		$metabox_data['fb_desc'] = get_term_meta($term->term_id, '_siteseo_social_fb_desc', true);
		$metabox_data['fb_img'] = get_term_meta($term->term_id, '_siteseo_social_fb_img', true);
		$metabox_data['x_title'] = get_term_meta($term->term_id, '_siteseo_social_twitter_title', true);
		$metabox_data['x_desc'] = get_term_meta($term->term_id, '_siteseo_social_twitter_desc', true);
		$metabox_data['x_img'] = get_term_meta($term->term_id, '_siteseo_social_twitter_img', true);
		
		// Social Dimensions
		$fb_img_id = get_term_meta($term->term_id, '_siteseo_social_fb_img_attachment_id', true);
		$fb_img_width = get_term_meta($term->term_id, '_siteseo_social_fb_img_width', true);
		$fb_img_height = get_term_meta($term->term_id, '_siteseo_social_fb_img_height', true);
		$x_img_id = get_term_meta($term->term_id, '_siteseo_social_twitter_img_attachment_id', true);
		$x_img_width = get_term_meta($term->term_id, '_siteseo_social_twitter_img_width', true);
		$x_img_height = get_term_meta($term->term_id, '_siteseo_social_twitter_img_height', true);

		// Redirection fields
		$metabox_data['redirections_enabled'] = get_term_meta($term->term_id, '_siteseo_redirections_enabled', true);
		$metabox_data['redirections_logged_status']	= get_term_meta($term->term_id, '_siteseo_redirections_logged_status', true);
		$metabox_data['redirections_type'] = get_term_meta($term->term_id, '_siteseo_redirections_type', true);
		$metabox_data['redirections_value'] = get_term_meta($term->term_id, '_siteseo_redirections_value', true);
		$metabox_data['robots_canonical']= get_term_meta($term->term_id, '_siteseo_robots_canonical', true);

		$title_options = get_option('siteseo_titles_option_name', []);
		$metabox_data['disabled_robots'] = [
			'robots_index' => '',
			'robots_follow' => '',
			'archive' => '',
			'snippet' => '',
			'imageindex' => '',
		];

		if(!empty($title_options['titles_noindex'])){
			$metabox_data['robots_index'] = 'yes';
			$metabox_data['disabled_robots']['robots_index'] = 'disabled';
		} else {
			$metabox_data['robots_index'] = get_term_meta($term->term_id, '_siteseo_robots_index', true);
		}

		if(!empty($title_options['titles_nofollow'])){
			$metabox_data['robots_follow'] = 'yes';
			$metabox_data['disabled_robots']['robots_follow'] = 'disabled';
		} else {
			$metabox_data['robots_follow'] = get_term_meta($term->term_id, '_siteseo_robots_follow', true);
		}

		if(!empty($title_options['titles_noarchive'])){
			$metabox_data['robots_archive'] = 'yes';
			$metabox_data['disabled_robots']['archive'] = 'disabled';
		} else {
			$metabox_data['robots_archive'] = get_term_meta($term->term_id, '_siteseo_robots_archive', true);
		}

		if(!empty($title_options['titles_nosnippet'])){
			$metabox_data['robots_snippet'] = 'yes';
			$metabox_data['disabled_robots']['snippet'] = 'disabled';
		} else {
			$metabox_data['robots_snippet'] = get_term_meta($term->term_id, '_siteseo_robots_snippet', true);
		}

		if(!empty($title_options['titles_noimageindex'])){
			$metabox_data['robots_imageindex'] = 'yes';
			$metabox_data['disabled_robots']['imageindex'] = 'disabled';
		} else {
			$metabox_data['robots_imageindex'] = get_term_meta($term->term_id, '_siteseo_robots_imageindex', true);
		}

		return $metabox_data;
	}
		
	static function metabox_form_html(&$metabox_data, $show_content_analysis = false){
		global $siteseo, $post, $pagenow, $typenow;
		
		$pro_settings = isset($siteseo->pro) ? $siteseo->pro : '';
		
		// Checked x is enabled global settings
		$enable_x_card = !empty($siteseo->social_settings['social_twitter_card']);
		
		$data_attr = [];
		$data_attr['data_tax'] = '';
		$data_attr['termId'] = '';

		if('post-new.php' == $pagenow || 'post.php' == $pagenow){
			$data_attr['current_id'] = $post->ID;
			$data_attr['origin'] = 'post';
			$data_attr['title'] = get_the_title($data_attr['current_id']);
		} elseif('term.php' == $pagenow || 'edit-tags.php' == $pagenow){
			global $tag;
			$data_attr['current_id'] = $tag->term_id;
			$data_attr['termId'] = $tag->term_id;
			$data_attr['origin'] = 'term';
			$data_attr['data_tax'] = $tag->taxonomy;
			$data_attr['title'] = $tag->name;
		}

		$data_attr['isHomeId'] = get_option('page_on_front');
		if($data_attr['isHomeId'] === '0'){
			$data_attr['isHomeId'] = '';
		}

		// Static Data
		$home_url = home_url();
		$parsed_url = wp_parse_url($home_url);
		$host_uri = $parsed_url['host'];
		$social_placeholder = SITESEO_ASSETS_URL . '/img/social-placeholder.png';

		$metabox_tag_drop_kses = [
			'button' => [
				'class' => true,
				'type' => true,
			],
			'span' => [
				'class' => true,
			],
			'div' => [
				'class' => true,
				'style' => true,
			],
			'input' => [
				'type' => true,
				'class' => true,
				'name' => true,
				'spellcheck' => true,
				'placeholder' => true,
			],
			'ul' => true,
			'li' => [
				'class' => true,
				'data-*' => true,
				'tabindex' => true,
			]
		];
		
		// Preview of social title and description
		$current_screen = get_current_screen();

		if(!empty($current_screen) && $current_screen->base === 'term'){
			$term_id = isset($_GET['tag_ID']) ? (int)$_GET['tag_ID'] : 0;
			$taxonomy = $current_screen->taxonomy;
		} else{
			$post_type = $current_screen->post_type;
		}

		$site_title_placeholder = '';
		$site_desc_placeholder = '';
		$social_preview_title = '';
		$social_preview_desc = '';
		
		if(!empty($post_type) && !empty($siteseo->titles_settings['titles_single_titles'][$post_type]['title'])){
			$site_title_placeholder = $siteseo->titles_settings['titles_single_titles'][$post_type]['title'];
		} elseif(!empty($taxonomy) && !empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy]['title'])){
			$site_title_placeholder = $siteseo->titles_settings['titles_tax_titles'][$taxonomy]['title'];
		} else{
			$site_title_placeholder = $metabox_data['title'];
		}
		
		if(!empty($post_type) && !empty($siteseo->titles_settings['titles_single_titles'][$post_type]['description'])){
			$site_desc_placeholder = $siteseo->titles_settings['titles_single_titles'][$post_type]['description'];
		} elseif(!empty($taxonomy) && !empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy]['description'])){
			$site_desc_placeholder =  $siteseo->titles_settings['titles_tax_titles'][$taxonomy]['description'];
		} else{
			$site_desc_placeholder = $metabox_data['excerpt'];
		}

		if(!empty($metabox_data['meta_title'])){
			$social_preview_title = $metabox_data['meta_title'];
		} elseif(!empty($post_type) && !empty($siteseo->titles_settings['titles_single_titles'][$post_type]['title'])){
			$social_preview_title = $siteseo->titles_settings['titles_single_titles'][$post_type]['title'];
		} elseif(!empty($taxonomy) && !empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy]['title'])){
			$social_preview_title = $siteseo->titles_settings['titles_tax_titles'][$taxonomy]['title'];
		} else{
			$social_preview_title = get_the_title();
		}
		
		
		if(!empty($metabox_data['meta_desc'])){
			$social_preview_desc = $metabox_data['meta_desc'];
		} elseif(!empty($post_type) && !empty($siteseo->titles_settings['titles_single_titles'][$post_type]['description'])){
			$social_preview_desc = $siteseo->titles_settings['titles_single_titles'][$post_type]['description'];
		} elseif(!empty($taxonomy) && !empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy]['description'])){
			$social_preview_desc = $siteseo->titles_settings['titles_tax_titles'][$taxonomy]['description'];
		} else{
			$social_preview_desc = get_bloginfo('description');
		}
		
		if(empty($siteseo->advanced_settings['appearance_ca_metaboxe']) && !empty($show_content_analysis)){
			$siteseo_metabox_tabs = [
				'content-analysis' => __('Content Analysis', 'siteseo')
			];
		}

		$siteseo_metabox_tabs['title-settings'] = __('Title', 'siteseo');
		$siteseo_metabox_tabs['social-settings'] = __('Social', 'siteseo');
		$siteseo_metabox_tabs['advanced-settings'] = __('Advanced', 'siteseo');

		$siteseo_metabox_tabs['redirect'] = __('Redirects', 'siteseo');
		
		if(!empty($pro_settings['enable_structured_data']) && !empty($pro_settings['toggle_state_stru_data']) && !empty($show_content_analysis)){
			$siteseo_metabox_tabs['structured-data-types'] = __('Structured Data Types', 'siteseo');
		}
		
		if(!empty($pro_settings['toggle_state_video_sitemap']) && !empty($pro_settings['enable_video_sitemap']) && !empty($show_content_analysis)){
			$siteseo_metabox_tabs['video-sitemap'] = __('Video Sitemap', 'siteseo');
		}
		
		if(!empty($pro_settings['toggle_state_google_news']) && !empty($pro_settings['google_news']) && !empty($show_content_analysis)){
			$siteseo_metabox_tabs['google-news'] = __('Google News', 'siteseo');
		}

		echo'<div id="siteseo-metabox-wrapper" class="siteseo-metabox-wrapper">
		<div class="siteseo-metabox-tabs" data-home-id="'.esc_attr($data_attr['isHomeId']).'" data-term-id="'.esc_attr($data_attr['termId']).'" data_id="'.esc_attr($data_attr['current_id']).'" data_origin="'.esc_attr($data_attr['origin']).'" data_tax="'.esc_attr($data_attr['data_tax']).'">';
		
		wp_nonce_field('siteseo_metabox_nonce', 'siteseo_metabox_nonce');

		foreach($siteseo_metabox_tabs as $siteseo_metabox_tab => $siteseo_metabox_tab_title){
			$selected_metabox_tab = '';

			if($siteseo_metabox_tab === 'content-analysis'){
				$selected_metabox_tab = 'siteseo-metabox-tab-label-active';
			}

			if(empty($siteseo->display_ca_metaboxe) && $siteseo_metabox_tab === 'title-settings'){
				$selected_metabox_tab = 'siteseo-metabox-tab-label-active';
			}			
			
			echo'<div class="siteseo-metabox-tab-label '.esc_attr($selected_metabox_tab).'" data-tab="siteseo-metabox-tab-'.esc_attr($siteseo_metabox_tab).'">';
			
			if($siteseo_metabox_tab === 'advanced-settings' && !empty($metabox_data['robots_index'])){
				echo'<span class="dashicons dashicons-hidden siteseo-noindex-warning"></span>';
			}
			
			echo esc_html($siteseo_metabox_tab_title).'</div>';
		}
			
		$home_url = home_url();
		$parsed_home_url = wp_parse_url($home_url);
		
		$ai_logo = SITESEO_ASSETS_URL . '/img/siteseo-ai.svg';
		
		$meta_desc_percentage = '1';
		if(!empty($metabox_data['meta_desc'])){
			$meta_desc_percentage = (strlen($metabox_data['meta_desc'])/160)*100;
		} elseif(!empty($metabox_data['excerpt'])){
			$meta_desc_percentage = (strlen($metabox_data['excerpt'])/160)*100;
		}

		if(intval($meta_desc_percentage) > 100){
			$meta_desc_percentage = '100';
		}

		$meta_title_percentage = '1';
		if(!empty($metabox_data['meta_title'])){
			$meta_title_percentage = (strlen($metabox_data['meta_title'])/60)*100;
		} else if(!empty($metabox_data['title'])){
			$meta_title_percentage = (strlen($metabox_data['title'])/60)*100;
		}

		if(intval($meta_title_percentage) > 100){
			$meta_title_percentage = '100';
		}

		echo'</div>';
		if(empty($siteseo->advanced_settings['appearance_ca_metaboxe']) && $show_content_analysis){
			echo'<div class="siteseo-sidebar-tabs siteseo-sidebar-tabs-opened"><span>'.esc_html__('Content Analysis', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
			<div class="siteseo-metabox-tab-content-analysis siteseo-metabox-tab" style="display:block;">';
				self::content_analysis($post);
			echo'</div>';
		}
		
		$allowed_suggestion_tags = array(
			'button' => array(
				'class' => array(),
				'type' => array(),
			),
			'span' => array(
				'id' => array(),
				'class' => array(),
			),
			'div' => array(
				'class' => array(),
				'style' => array(),
			),
			'input' => array(
				'type' => array(),
				'class' => true,
				'placeholder' => true,
			)
		);
		
		// if all x-settings empty then use same as og option enabled
		$use_og_settings = (empty($metabox_data['x_title']) && empty($metabox_data['x_desc']) && empty($metabox_data['x_img']));
		
		// show image in preview
		if(!empty($metabox_data['x_img'])){
			$x_image = $metabox_data['x_img'];
		} else if(!empty($metabox_data['fb_img']) && !empty($use_og_settings)){ // use og enabled
			$x_image = $metabox_data['fb_img'];
		} else{
			$x_image = $social_placeholder;
		}
		
		// x preview title
		if(!empty($metabox_data['x_title'])){
			$x_title_preview = $metabox_data['x_title'];
		} else if($metabox_data['fb_title'] &&  !empty($use_og_settings)){
			$x_title_preview = $metabox_data['fb_title'];
		} else {
			$x_title_preview = $social_preview_title;
		}
		
		echo'<div class="siteseo-sidebar-tabs '.(empty($siteseo->display_ca_metaboxe) ? 'siteseo-sidebar-tabs-opened' : '').'"><span>'.esc_html__('Title', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
		<div class="siteseo-metabox-tab-title-settings siteseo-metabox-tab" style="'.(empty($siteseo->display_ca_metaboxe) ? 'display:block;' : '').'">
		<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label>'.esc_html__('Search Preview','siteseo').'</label>
			</div>
			<div class="siteseo-metabox-search-preview">
				<div class="siteseo-search-preview-toggle">
					<span id="siteseo-metabox-search-pc" style="display:none">'.esc_html__('Show Desktop version', 'siteseo').'</span>
					<span id="siteseo-metabox-search-mobile">'.esc_html__('Show Mobile version', 'siteseo').'</span>
				</div>
				<div class="siteseo-search-preview-desktop">
					<div class="siteseo-search-preview-metadata">
						<div style="background-color: #e2eeff; border: 1px solid #e2eeff; height:28px; width:28px; padding: 3px; border-radius: 50px; display:flex; align-items:center; justify-content:center;">
						<svg focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0060f0"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"></path></svg>
						</div>
						<div class="siteseo-search-preview-metadata-link">
							<div>'.esc_url($parsed_home_url['host']).'</div>
							<div><cite>'.esc_url(home_url()).'</cite></div>
						</div>
						<div>
						<svg focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path></svg>
						</div>
					</div>
					<h3>'.(!empty($metabox_data['meta_title']) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($metabox_data['meta_title'], true)) : (!empty($site_title_placeholder) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($site_title_placeholder, true)) : 'Post Title here')).'</h3>
					<div class="siteseo-search-preview-description">
					'.(!empty($metabox_data['meta_desc']) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($metabox_data['meta_desc'], true)) : (!empty($site_desc_placeholder) ? esc_html(substr(\SiteSEO\TitlesMetas::replace_variables($site_desc_placeholder, true), 0, 160)) : 'Post description')).'
					</div>
					
				</div>
			</div>
		</div>
		<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label for="siteseo_titles_title_meta">'.esc_html__('Title', 'siteseo').'</label>
			</div>
			<div class="siteseo-metabox-input-wrap">
				<div class="siteseo-metabox-tags">
					<button type="button" class="siteseo-metabox-tag" data-tag="%%post_title%%"><span class="dashicons dashicons-plus"></span> Post Title</button>
					<button type="button" class="siteseo-metabox-tag" data-tag="%%sitetitle%%"><span class="dashicons dashicons-plus"></span> Site Title</button>
					<button type="button" class="siteseo-metabox-tag" data-tag="%%sep%%"><span class="dashicons dashicons-plus"></span>Seperator</button>'.wp_kses(siteseo_suggestion_button_metabox(), $allowed_suggestion_tags);
					if(defined('SITESEO_PRO_VERSION') && !defined('SITEPAD')){
						echo'<span class="siteseo-ai-modal-open" data-context="site-page" title="SiteSEO AI Assistant"><img src="'.esc_url($ai_logo).'" alt="AI Assistant Icon">'.'<label class="siteseo-ai-modal-label">'.esc_html__('Ask AI', 'siteseo').'</label></span>';
					}
				echo'</div>
				<input type="text" id="siteseo_titles_title_meta" class="siteseo_titles_title_meta" name="siteseo_titles_title" placeholder="'.(!empty($site_title_placeholder) ? esc_attr(\SiteSEO\TitlesMetas::replace_variables($site_title_placeholder, true)) : esc_html__('Enter title for this post', 'siteseo')).'" value="'.(!empty($metabox_data['meta_title']) ? esc_html($metabox_data['meta_title']) : '').'"/>
				<div class="siteseo-metabox-limits">
					<span class="siteseo-metabox-limits-meter"><span style="width:'.esc_attr($meta_title_percentage).'%"></span></span>
					<span class="siteseo-metabox-limits-numbers"><em>'.esc_html(strlen($metabox_data['meta_title'])).'</em> out of 60 max recommended characters</span>
				</div>
			</div>
		</div>
		<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label for="siteseo_titles_desc_meta">'.esc_html__('Meta Description', 'siteseo').'</label>
			</div>
			<div class="siteseo-metabox-input-wrap">
				<div class="siteseo-metabox-tags">
					<button type="button" class="siteseo-metabox-tag" data-tag="%%post_excerpt%%"><span class="dashicons dashicons-plus"></span> Post Excerpt</button>'.wp_kses(siteseo_suggestion_button_metabox(), $allowed_suggestion_tags);
					if(defined('SITESEO_PRO_VERSION') && !defined('SITEPAD')){
						echo'<span class="siteseo-ai-modal-open" data-context="site-page" title="SiteSEO AI Assistant"><img src="'.esc_url($ai_logo).'" alt="AI Assistant Icon">'.'<label class="siteseo-ai-modal-label">'.esc_html__('Ask AI', 'siteseo').'</label></span>';
					}
				echo'</div>
				<textarea id="siteseo_titles_desc_meta" class="siteseo_titles_desc_meta" name="siteseo_titles_desc" rows="2" placeholder="'.(!empty($site_desc_placeholder) ? esc_attr(substr(\SiteSEO\TitlesMetas::replace_variables($site_desc_placeholder, true), 0, 160)) : esc_html__('Enter description for this post', 'siteseo')).'">'.(!empty($metabox_data['meta_desc']) ? esc_html($metabox_data['meta_desc']) : '').'</textarea>
				<div class="siteseo-metabox-limits">
					<span class="siteseo-metabox-limits-meter"><span style="width:'.esc_attr($meta_desc_percentage).'%"></span></span>
					<span class="siteseo-metabox-limits-numbers"><em>'.esc_html(strlen($metabox_data['meta_desc'])).'</em> out of 160 max recommended characters</span>
				</div>
			</div>
		</div>
		</div>

		<div class="siteseo-sidebar-tabs"><span>'.esc_html__('Social', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
		<div class="siteseo-metabox-tab-social-settings siteseo-metabox-tab">
			<div class="siteseo-metabox-subtabs">
				<div class="siteseo-metabox-tab-label siteseo-metabox-tab-label-active" data-tab="siteseo-metabox-tab-fb-settings">Facebook</div>
				<div class="siteseo-metabox-tab-label" data-tab="siteseo-metabox-tab-x-settings">X(Twitter)</div>
			</div>
			<div class="siteseo-metabox-tab-fb-settings siteseo-metabox-tab" style="display:block;">
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label>'.esc_html__('Preview', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-fb-preview">
					<div class="siteseo-metabox-fb-image">
						<img src="'.(!empty($metabox_data['fb_img']) ? esc_url($metabox_data['fb_img']) : esc_url($social_placeholder)).'" alt="Facebook preview"/>
					</div>
					<div class="siteseo-metabox-fb-data">
						<div class="siteseo-metabox-fb-host">'.(!empty($host_uri) ? esc_html($host_uri) : '').'</div>
						<div class="siteseo-metabox-fb-title">'.(!empty($metabox_data['fb_title']) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($metabox_data['fb_title'], true)) : esc_html(\SiteSEO\TitlesMetas::replace_variables($social_preview_title, true))).'</div>
						<div class="siteseo-metabox-fb-desc">'.(!empty($metabox_data['fb_desc']) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($metabox_data['fb_desc'], true)) : esc_html(\SiteSEO\TitlesMetas::replace_variables($social_preview_desc, true))).'</div>
					</div>
				</div>
			</div>
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_social_fb_title_meta">'.esc_html__('Facebook Title', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<input type="text" id="siteseo_social_fb_title_meta" class="siteseo_social_fb_title_meta" name="siteseo_social_fb_title" placeholder="'.(!empty($social_preview_title) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($social_preview_title, true)) : '').'" value="'.(!empty($metabox_data['fb_title']) ? esc_attr($metabox_data['fb_title']) : '').'" />
					<div class="siteseo-metabox-tags">
						<button type="button" class="siteseo-facebook-title" data-tag="%%post_title%%"><span class="dashicons dashicons-plus"></span> Post Title</button>
						<button type="button" class="siteseo-facebook-title" data-tag="%%sitetitle%%"><span class="dashicons dashicons-plus"></span> Site Title</button>
						<button type="button" class="siteseo-facebook-title" data-tag="%%sep%%"><span class="dashicons dashicons-plus"></span>Seperator</button>'.wp_kses(siteseo_suggestion_button_metabox(), $allowed_suggestion_tags);
						if(defined('SITESEO_PRO_VERSION') && !defined('SITEPAD')){
							echo'<span class="siteseo-ai-modal-open" data-context="og" title="SiteSEO AI Assistant"><img src="'.esc_url($ai_logo).'" alt="AI Assistant Icon">'.'<label class="siteseo-ai-modal-label">'.esc_html__('Ask AI', 'siteseo').'</label></span>';
						}
					echo'</div>
				</div>
			</div>

			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_social_fb_desc_meta">'.esc_html__('Facebook description', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<textarea id="siteseo_social_fb_desc_meta" class="siteseo_social_fb_desc_meta" name="siteseo_social_fb_desc" rows="2" placeholder="'.(!empty($social_preview_desc) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($social_preview_desc, true)) : '').'">'.(!empty($metabox_data['fb_desc']) ? esc_html($metabox_data['fb_desc']) : '').'</textarea>
					<div class="siteseo-metabox-tags">
						<button type="button" class="siteseo-facebook-desc" data-tag="%%post_excerpt%%"><span class="dashicons dashicons-plus"></span> Post Excerpt</button>'.wp_kses(siteseo_suggestion_button_metabox(), $allowed_suggestion_tags);
						if(defined('SITESEO_PRO_VERSION') && !defined('SITEPAD')){
							echo'<span class="siteseo-ai-modal-open" data-context="og" title="SiteSEO AI Assistant"><img src="'.esc_url($ai_logo).'" alt="AI Assistant Icon">'.'<label class="siteseo-ai-modal-label">'.esc_html__('Ask AI', 'siteseo').'</label></span>';
						}
					echo'</div>
				</div>
			</div>

			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_social_fb_img_meta">'.esc_html__('Facebook Thumbnail', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<span style="color:red; font-weight:bold; display:none;"></span>
					<input type="text" id="siteseo_social_fb_img_meta" name="siteseo_social_fb_img" class="siteseo_social_fb_img_meta" placeholder="'.esc_html__('Enter URL of the Image you want to be shown as the Facebook image', 'siteseo').'" value="'.(!empty($metabox_data['fb_img']) ? esc_url($metabox_data['fb_img']) : '').'"/>
					<p class="description">'.esc_html__('Minimum size: 200x200px, ideal ratio 1.91:1, 8Mb max. (eg: 1640x856px or 3280x1712px for retina screens).', 'siteseo').'</p>
					<input type="hidden" name="siteseo_social_fb_img_attachment_id" id="siteseo_social_fb_img_attachment_id" class="siteseo_social_fb_img_attachment_id" value="">
					<input type="hidden" name="siteseo_social_fb_img_width" id="siteseo_social_fb_img_width" class="siteseo_social_fb_img_width" value="">
					<input type="hidden" name="siteseo_social_fb_img_height" id="siteseo_social_fb_img_height" class="siteseo_social_fb_img_height" value="">
					<button class="components-button is-secondary" id="siteseo_social_fb_img_upload">Upload Image</button>
				</div>
			</div>
			</div>

			<div class="siteseo-metabox-tab-x-settings siteseo-metabox-tab">
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label>'.esc_html__('Preview','siteseo').'</label>
				</div>
				<div>
				<div class="siteseo-metabox-x-preview">
					<div class="siteseo-metabox-x-image">
						<img src="'.($x_image ? esc_url($x_image) : '').'" alt="X preview"/>
					</div>
					<div class="siteseo-metabox-x-data">
						<div class="siteseo-metabox-x-title">'.(!empty($x_title_preview) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($x_title_preview, true)) : '').'</div>
					</div>
				</div>
				<div class="siteseo-metabox-x-host">From '.(!empty($host_uri) ? esc_html($host_uri) : '').'</div>
				</div>
			</div>';
			
			if(!empty($enable_x_card)){
				
				echo'<div class="siteseo-metabox-option-wrap">
					<div class="siteseo-metabox-label-wrap">
						<label>'.esc_html__('Use same as Facebook settings', 'siteseo').'</label>
					</div>
					<div class="siteseo-metabox-input-wrap">
						<label class="siteseo-x-toggle-switch">';
							$checked = !empty($metabox_data['x_title'] || $metabox_data['x_desc'] || $metabox_data['x_img']) ? '' : "checked=checked";
							
							echo'<input name="siteseo_social_use_og_settings" type="checkbox" '.esc_html($checked).'/>
							<span class="siteseo-x-slider"></span>
						</label>
					</div>
				</div>';
			}
			
			if(!empty($enable_x_card)){
				echo'<div class="siteseo-x-settings" '.(!empty($use_og_settings) ? 'style="display:none;"' : '').'>';
			}
			
			echo'<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_social_twitter_title_meta">'.esc_html__('X Title', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<input type="text" id="siteseo_social_twitter_title_meta" class="siteseo_social_twitter_title_meta" name="siteseo_social_twitter_title" placeholder="'.(!empty($social_preview_title) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($social_preview_title, true)) : '').'" value="'.(!empty($metabox_data['x_title']) ? esc_attr($metabox_data['x_title']) : '').'" />
					<div class="siteseo-metabox-tags">
						<button type="button" class="siteseo-x-title" data-tag="%%post_title%%"><span class="dashicons dashicons-plus"></span> Post Title</button>
						<button type="button" class="siteseo-x-title" data-tag="%%sitetitle%%"><span class="dashicons dashicons-plus"></span> Site Title</button>
						<button type="button" class="siteseo-x-title" data-tag="%%sep%%"><span class="dashicons dashicons-plus"></span>Seperator</button>'.wp_kses(siteseo_suggestion_button_metabox(), $allowed_suggestion_tags);
						if(defined('SITESEO_PRO_VERSION') && !defined('SITEPAD')){
							echo'<span class="siteseo-ai-modal-open" data-context="twitter" title="SiteSEO AI Assistant"><img src="'.esc_url($ai_logo).'" alt="AI Assistant Icon">'.'<label class="siteseo-ai-modal-label">'.esc_html__('Ask AI', 'siteseo').'</label></span>';
						}
					echo'</div>
				</div>
			</div>
			
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_social_twitter_desc_meta">'.esc_html__('X description', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<textarea id="siteseo_social_twitter_desc_meta" class="siteseo_social_twitter_desc_meta" name="siteseo_social_twitter_desc" rows="2" placeholder="'.(!empty($social_preview_desc) ? esc_html(\SiteSEO\TitlesMetas::replace_variables($social_preview_desc, true)) : '').'">'.(!empty($metabox_data['x_desc']) ? esc_attr($metabox_data['x_desc']) : '').'</textarea>
					<div class="siteseo-metabox-tags">
						<button type="button" class="siteseo-x-desc" data-tag="%%post_excerpt%%"><span class="dashicons dashicons-plus"></span> Post Excerpt</button>'.wp_kses(siteseo_suggestion_button_metabox(), $allowed_suggestion_tags);
						if(defined('SITESEO_PRO_VERSION') && !defined('SITEPAD')){
							echo'<span class="siteseo-ai-modal-open" data-context="twitter" title="SiteSEO AI Assistant"><img src="'.esc_url($ai_logo).'" alt="AI Assistant Icon">'.'<label class="siteseo-ai-modal-label">'.esc_html__('Ask AI', 'siteseo').'</label></span>';
						}
					echo'</div>
				</div>
			</div>
			
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_social_twitter_img_meta">'.esc_html__('X Thumbnail', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<span style="color:red; font-weight:bold; display:none;"></span>
					<input type="text" id="siteseo_social_twitter_img_meta" class="siteseo_social_twitter_img_meta" name="siteseo_social_twitter_img" placeholder="'.esc_html__('Enter URL of the Image you want to be shown as the X image', 'siteseo').'" value="'.(!empty($metabox_data['x_img']) ? esc_attr($metabox_data['x_img']) : '').'" />
					<p class="description">'.esc_html__('Minimum size: 144x144px (300x157px with large card enabled), ideal ratio 1:1 (2:1 with large card), 5Mb max.', 'siteseo').'</p>
					<input type="hidden" name="siteseo_social_twitter_img_attachment_id" id="siteseo_social_twitter_img_attachment_id" class="siteseo_social_twitter_img_attachment_id" value="">
					<input type="hidden" name="siteseo_social_twitter_img_width" id="siteseo_social_twitter_img_width" class="siteseo_social_twitter_img_width" value="">
					<input type="hidden" name="siteseo_social_twitter_img_height" id="siteseo_social_twitter_img_height" class="siteseo_social_twitter_img_height" value="">
					<button class="components-button is-secondary" id="siteseo_social_twitter_img_upload">Upload Image</button>
				</div>
			</div>';

			if(!empty($enable_x_card)){
				echo'</div>';
			}
			
			echo'</div>
		</div>';
		
		if(!empty($pro_settings['enable_structured_data']) && !empty($pro_settings['toggle_state_stru_data']) && !empty($show_content_analysis)){
			echo'<div class="siteseo-sidebar-tabs"><span>'.esc_html__('Structured Data Types', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
			<div class="siteseo-metabox-tab-structured-data-types siteseo-metabox-tab">';
				// Pro fearure
				do_action('siteseo_display_structured_data_types');
			echo'</div>';
		}
		
		// video sitemap
		if(!empty($pro_settings['toggle_state_video_sitemap']) && !empty($pro_settings['enable_video_sitemap']) && !empty($show_content_analysis)){
			echo'<div class="siteseo-sidebar-tabs"><span>'.esc_html__('Video Sitemap', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
			<div class="siteseo-metabox-tab-video-sitemap siteseo-metabox-tab">';
				do_action('siteseo_display_video_sitemap');
			echo'</div>';
		}
		
		// gooogle news exclude 
		if(!empty($pro_settings['toggle_state_google_news']) && !empty($pro_settings['google_news']) && !empty($show_content_analysis)){
			echo'<div class="siteseo-sidebar-tabs"><span>'.esc_html__('Google News', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
			<div class="siteseo-metabox-tab-google-news siteseo-metabox-tab">';
				do_action('siteseo_display_google_news');
			echo'</div>';
		}
		
		echo'<div class="siteseo-sidebar-tabs"><span>';

		if(!empty($metabox_data['robots_index'])){
			echo'<span class="dashicons dashicons-hidden siteseo-noindex-warning"></span>';
		}
		
		echo esc_html__('Advanced', 'siteseo').'</span><span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></span></div>
		
		<div class="siteseo-metabox-tab-advanced-settings siteseo-metabox-tab">
		<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label for="siteseo_social_twitter_img_meta">'.esc_html__('Meta Robots Settings', 'siteseo').'</label>
				<p class="description">'.
				/* translators: %s represents the degree of severity */
				wp_kses_post(sprintf(__('You cannot uncheck a checkbox? This is normal, and it\'s most likely defined in the <a href="%s">global settings of the plugin.</a>', 'siteseo'), esc_url(admin_url('admin.php?page=siteseo-titles#tab=tab_siteseo_titles_single')))).'</p>
			</div>
			<div class="siteseo-metabox-input-wrap">';
				
			$robots_options = [
				'siteseo_robots_index_meta' => [
					'desc' => __('Do not display this page in search engine results / Sitemaps', 'siteseo'),
					'short' => 'noindex',
					'name' => 'siteseo_robots_index',
					'checked' => $metabox_data['robots_index'],
					'disabled' => $metabox_data['disabled_robots']['robots_index']
				],
				'siteseo_robots_follow_meta' => [
					'desc' => __('Do not follow links for this page', 'siteseo'),
					'short' => 'nofollow',
					'name' => 'siteseo_robots_follow',
					'checked' => $metabox_data['robots_follow'],
					'disabled' => $metabox_data['disabled_robots']['robots_follow']
				],
				'siteseo_robots_imageindex_meta' => [
					'desc' => __('Do not index images for this page', 'siteseo'),
					'short' => 'noimageindex',
					'name' => 'siteseo_robots_imageindex',
					'checked' => $metabox_data['robots_imageindex'],
					'disabled' => $metabox_data['disabled_robots']['imageindex']
				],
				'siteseo_robots_archive_meta' => [
					'desc' => __('Do not display a "Cached" link in the Google search results', 'siteseo'),
					'short' => 'noarchive',
					'name' => 'siteseo_robots_archive',
					'checked' => $metabox_data['robots_archive'],
					'disabled' => $metabox_data['disabled_robots']['archive']
				],
				'siteseo_robots_snippet_meta' => [
					'desc' => __('Do not display a description in search results for this page', 'siteseo'),
					'short' => 'nosnippet',
					'name' => 'siteseo_robots_snippet',
					'checked' => $metabox_data['robots_snippet'],
					'disabled' => $metabox_data['disabled_robots']['snippet']
				]
			];

			foreach($robots_options as $robots_id => $robots_option){
				$checked = '';
				if(!empty($robots_option['checked'])){
					$checked = 'checked';
				}
				
				$disabled = '';
				if(!empty($robots_option['disabled'])){
					$disabled = 'disabled';
					$robots_option['name'] = '';
				}

				echo'<label for="'.esc_attr($robots_id).'" style="display:block; margin-bottom:5px;">
					<input type="checkbox" value="yes" id="'.esc_attr($robots_id).'" class="siteseo-metabox-robots-options" name="'.esc_attr($robots_option['name']).'" '.esc_attr($checked).' '.esc_attr($disabled).'/>
					'.esc_html($robots_option['desc']).' ('.esc_html($robots_option['short']).')
				</label>';
			}
			
			echo'</div>
		</div>
		<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label for="siteseo_robots_canonical_meta">'.esc_html__('Canonical URL', 'siteseo').'</label>
			</div>
			<div class="siteseo-metabox-input-wrap">
				<input id="siteseo_robots_canonical_meta" type="text" name="siteseo_robots_canonical" placeholder="'.esc_url(get_the_permalink()).'" value="'.(!empty($metabox_data['robots_canonical']) ? esc_html($metabox_data['robots_canonical']) : '').'">
			</div>
		</div>';

		if(!empty($pagenow) && !empty($typenow) && ($pagenow == 'post.php' || $pagenow == 'post-new.php') && ($typenow == 'post' || $typenow == 'product')){

			$categories = (object)[];
			if($typenow == 'product'){
				$categories = get_the_terms($post, 'product_cat');
			} else{
				$categories = get_categories();
			}
			
			if(!empty($categories) && !is_wp_error($categories)){
				echo'<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_robots_canonical_meta">'.esc_html__('Select a primary category', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<select id="siteseo_robots_primary_cat" name="siteseo_robots_primary_cat">';
						foreach($categories as $category){
							$selected = '';
							if(!empty($metabox_data['robots_primary_cat']) && $metabox_data['robots_primary_cat'] == $category->term_id){
								$selected = 'selected';
							}

							echo'<option value="'.esc_attr($category->term_id).'" '.esc_attr($selected).'>'.esc_html($category->name).'</option>'; 
						}
					echo'</select>
				</div>
			</div>';
			}
		}
		echo'</div>

		<div class="siteseo-sidebar-tabs"><span>'.esc_html__('Redirects', 'siteseo').'</span>
			<span class="siteseo-sidebar-tabs-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span>
		</span></div>
		
		<div class="siteseo-metabox-tab-redirect siteseo-metabox-tab">
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_redirections_enabled_meta">'.esc_html__('Enable redirection', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<input id="siteseo_redirections_enabled_meta" type="checkbox" name="siteseo_redirections_enabled" value="1" '.(!empty($metabox_data['redirections_enabled']) ? 'checked' : '').'>
				</div>
			</div>
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_redirections_enabled_meta">'.esc_html__('Login status', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<select name="siteseo_redirections_logged_status" id="siteseo_redirections_logged_status">
						<option value="both" '.(!empty($metabox_data['redirections_logged_status']) && $metabox_data['redirections_logged_status'] == 'both' ? 'selected' : '').'>'.esc_html__('All', 'siteseo').'</option>
						<option value="only_logged_in" '.(!empty($metabox_data['redirections_logged_status']) && $metabox_data['redirections_logged_status'] == 'only_logged_in' ? 'selected' : '').'>'.esc_html__('Only when logged In', 'siteseo').'</option>
						<option value="only_not_logged_in" '.(!empty($metabox_data['redirections_logged_status']) && $metabox_data['redirections_logged_status'] == 'only_not_logged_in' ? 'selected' : '').'>'.esc_html__('Only when not logged in', 'siteseo').'</option>
					</select>
				</div>
			</div>
			<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_redirections_type">'.esc_html__('Redirection Type', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<select name="siteseo_redirections_type" id="siteseo_redirections_type">
						<option value="301" '.(!empty($metabox_data['redirections_type']) && $metabox_data['redirections_type'] == '301' ? 'selected' : '').'>'.esc_html__('301 Moved Permanently', 'siteseo').'</option>
						<option value="302" '.(!empty($metabox_data['redirections_type']) && $metabox_data['redirections_type'] == '302' ? 'selected' : '').'>'.esc_html__('302 Found / Moved Temporarily', 'siteseo').'</option>
						<option value="307" '.(!empty($metabox_data['redirections_type']) && $metabox_data['redirections_type'] == '307' ? 'selected' : '').'>'.esc_html__('307 Moved Temporarily', 'siteseo').'</option>';
						if($typenow === 'siteseo_404'){
							echo'<option value="410" '.(!empty($metabox_data['redirections_type']) && $metabox_data['redirections_type'] == '410' ? 'selected' : '').'>'.esc_html__('410 Gone', 'siteseo').'</option>
							<option value="451" '.(!empty($metabox_data['redirections_type']) && $metabox_data['redirections_type'] == '451' ? 'selected' : '').'>'. esc_html__('451 Unavailable For Legal Reasons', 'siteseo').'</option>';
						}
					echo'</select>
				</div>
		</div>
		<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label for="siteseo_redirections_value_meta">'.esc_html__('Redirection URL', 'siteseo').'</label>
			</div>
			<div class="siteseo-metabox-input-wrap">
				<input id="siteseo_redirections_value_meta" type="text" name="siteseo_redirections_value" value="'.(!empty($metabox_data['redirections_value']) ? esc_attr($metabox_data['redirections_value']): '').'">
			</div>
			<input type="hidden" id="analysis_tabs" name="analysis_tabs" value="'.esc_html(wp_json_encode(array_keys($siteseo_metabox_tabs))).'">
		</div>';
		// Note
		if($typenow === 'siteseo_404'){
			echo'<div class="siteseo-metabox-option-wrap">
				<div class="siteseo-metabox-label-wrap">
					<label for="siteseo_redirections_param">'.esc_html__('Query parameters', 'siteseo').'</label>
				</div>
				<div class="siteseo-metabox-input-wrap">
					<select name="siteseo_redirections_param" id="siteseo_redirections_param">
						<option value="exact_match" '.(!empty($metabox_data['redirections_param']) && $metabox_data['redirections_param'] == 'exact_match' ? 'selected' : '').'>'.esc_html__('Exactly parameters with exact match', 'siteseo').'</option>
						<option value="without_param" '.(!empty($metabox_data['redirections_param']) && $metabox_data['redirections_param'] == 'without_param' ? 'selected' : '').'>'.esc_html__('Exclude all parameters', 'siteseo').'</option>
						<option value="with_ignored_param" '.(!empty($metabox_data['redirections_param']) && $metabox_data['redirections_param'] == 'with_ignored_param' ? 'selected' : '').'>'.esc_html__('Exclude all parameters and pass them to the redirection', 'siteseo').'</option>
					</select>
				</div>
			</div>';
		}

		echo'</div>
		</div>';

	}

	static function content_analysis($post){
		
		wp_nonce_field('siteseo_ca_nonce', 'siteseo_content_analysis_nonce');

		$siteseo_real_preview = [
			'siteseo_nonce' => wp_create_nonce('siteseo_real_preview_nonce'),
			'siteseo_real_preview' => admin_url('admin-ajax.php'),
			'i18n' => ['progress' => __('Analysis in progress...', 'siteseo')],
			'ajax_url' => admin_url('admin-ajax.php'),
			'get_preview_meta_title' => wp_create_nonce('get_preview_meta_title'),
			'realtime_nonce' => wp_create_nonce('siteseo_realtime_nonce'),
		];

		$metabox_data = [];

		$metabox_data['analysis_target_kw'] = get_post_meta($post->ID, '_siteseo_analysis_target_kw', true);
		$metabox_data['analysis_data'] = get_post_meta($post->ID, '_siteseo_analysis_data', true);
		$metabox_data['readibility_data'] = get_post_meta($post->ID, '_siteseo_readibility_data', true);
		$metabox_data['meta_title'] = get_post_meta($post->ID, '_siteseo_titles_title', true);
		$metabox_data['meta_desc'] = get_post_meta($post->ID, '_siteseo_titles_desc', true);
		
		$title_options = get_option('siteseo_titles_option_name', []);

		if(self::titles_single_cpt_noindex_option() || !empty($title_options['titles_noindex']) || true === post_password_required($post->ID)){
			$metabox_data['robots_index'] = 'yes';
		} else {
			$metabox_data['robots_index'] = get_post_meta($post->ID, '_siteseo_robots_index', true);
		}

		if(post_password_required($post->ID) === true || !empty($title_options['titles_noindex']) || self::titles_single_cpt_noindex_option()){
			$metabox_data['robots_index'] = 'yes';
		} else{
			$metabox_data['robots_index'] = get_post_meta($post->ID, '_siteseo_robots_index', true);
		}

		if(!empty($title_options['titles_nofollow']) || self::titles_single_cpt_nofollow_option()){
			$metabox_data['robots_follow'] = 'yes';
		} else{
			$metabox_data['robots_follow'] = get_post_meta($post->ID, '_siteseo_robots_follow', true);
		}

		if(!empty($title_options['titles_noarchive'])){
			$metabox_data['robots_archive'] = 'yes';
		} else{
			$metabox_data['robots_archive'] = get_post_meta($post->ID, '_siteseo_robots_archive', true);
		}

		if(!empty($title_options['titles_nosnippet'])){
			$metabox_data['robots_snippet'] = 'yes';
		} else{
			$metabox_data['robots_snippet'] = get_post_meta($post->ID, '_siteseo_robots_snippet', true);
		}

		if(!empty($title_options['titles_noimageindex'])){
			$metabox_data['robots_imageindex'] = 'yes';
		} else{
			$metabox_data['robots_imageindex'] = get_post_meta($post->ID, '_siteseo_robots_imageindex', true);
		}

		$metabox_data['post_id'] = $post->ID;
		$metabox_data['readibility_data'] = get_post_meta($post->ID, '_siteseo_readibility_data', true);
		
		self::siteseo_content_analysis_tab($metabox_data);
	}
	
	
	static function titles_single_cpt_nofollow_option(){
		$siteseo_get_current_cpt = get_post_type();

		$options = get_option('siteseo_titles_option_name');
		if(!empty($options) && isset($options['titles_single_titles'][$siteseo_get_current_cpt]['nofollow'])){
			return $options['titles_single_titles'][$siteseo_get_current_cpt]['nofollow'];
		}
	}
	
	static function btn_secondary_classes() {
		//Classic Editor compatibility
		global $pagenow;
		
		$current_screen = null;
		
		if(function_exists('get_current_screen')){
			$current_screen = get_current_screen();
		}
		
		if(!empty($current_screen) && method_exists($current_screen, 'is_block_editor') && true === $current_screen->is_block_editor()){
			$btn_classes_secondary = 'components-button is-secondary';
		} elseif(isset($pagenow) && ($pagenow === 'term.php' || $pagenow === 'post.php' || $pagenow === 'post-new.php')){
			$btn_classes_secondary = 'button button-secondary';
		} else{
			$btn_classes_secondary = 'btn btnSecondary';
		}

		return $btn_classes_secondary;
	}
	
	static function siteseo_content_analysis_tab(&$metabox_data){
		global $post;
		
		echo '<div class="siteseo-metabox-option-wrap">
			<div class="siteseo-metabox-label-wrap">
				<label for="siteseo_titles_title_meta">' . esc_html__('Focus Keywords', 'siteseo') . '</label>
			</div>
			<div class="siteseo-metabox-input-wrap">
				<div id="siteseo_tags_wrapper" style="display: flex; flex-wrap: wrap; gap: 5px; padding: 5px; border: 1px solid #ccc; border-radius: 5px;">';
					if(!empty($metabox_data['analysis_target_kw'])){
						$tags_arr = explode(',', $metabox_data['analysis_target_kw']);
						
						if(count($tags_arr) > 0){
							foreach($tags_arr as $tag_name){
								echo '<span class="siteseo-tag">'.esc_html($tag_name).'<span class="siteseo-remove-tag">×</span></span>';
							}
						}
					}

					echo '<input id="siteseo_analysis_target_kw_meta" class="siteseo_analysis_target_kw_meta" type="text" placeholder="' . esc_html__('Enter your target keywords', 'siteseo') . '" style="border: none; outline: none; flex: 1; min-width: 150px;" />
					<input type="hidden" id="siteseo_tags_hidden" name="siteseo_analysis_target_kw" value="' . (!empty($metabox_data['analysis_target_kw']) ? esc_attr($metabox_data['analysis_target_kw']) : '') . '" />
				</div>
				<p class="description">Press <kbd>Enter</kbd> key on your keyboard to add keyword</p>
				<button id="siteseo_refresh_seo_analysis" type="button" style="margin-top:10px;" class="'.esc_attr(self::btn_secondary_classes()).'" data_id="'.esc_attr(get_the_ID()).'" data_post_type="'.esc_attr(get_current_screen()->post_type).'"> '.esc_html__('Refresh analysis', 'siteseo').'</button>
				<p class="description">'.esc_html__('Refresh analysis after saving the post to improve the accuracy of the analysis', 'siteseo').'</p>
			</div>
		</div>
		<div id="siteseo-metabox-content-analysis">
			<div id="siteseo-metabox-tabs-container">
				<div class="siteseo-metabox-subtabs">
				<div class="siteseo-metabox-tab-label siteseo-metabox-tab-label-active" data-tab="siteseo-metabox-seo-analysis-tab">'. esc_html__('SEO Analysis', 'siteseo').'</div>
					<div class="siteseo-metabox-tab-label" data-tab="siteseo-metabox-readibility-analysis-tab">'.esc_html__('Content Readability', 'siteseo').'</div>
				</div>
				<div id="siteseo-metabox-tab-content">
					<div class="siteseo-metabox-seo-analysis-tab siteseo-metabox-tab" style="display:block;">';
					
					$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
					$post = get_post($post_id);
					Analysis::display_seo_analysis($post);
			echo'</div>
			<div class="siteseo-metabox-readibility-analysis-tab siteseo-metabox-tab">
				<p class="description">' . 
					esc_html__('This section works as a guide to help you write, better content for your user, this do not have a direct affect on SEO, but it will help you write better content for your users which will help user stay on your site longer, or will improve the Click Through rate.
					Which will signal search engines about the userfulness and likeleyness of your content by your user which indirectly improve SEO of the page.', 'siteseo') . 
				'</p>';
				Analysis::display_content_readibility($metabox_data);
			echo'</div>
					</div>
				</div>
			</div>';
	}

	
	static function titles_single_cpt_noindex_option(){
		$siteseo_get_current_cpt = get_post_type();

		$options = get_option('siteseo_titles_option_name');
		
		if(!empty($options) && isset($options['titles_single_titles'][$siteseo_get_current_cpt]['noindex'])){
			return $options['titles_single_titles'][$siteseo_get_current_cpt]['noindex'];
		}
	}

	static function save_ca_metabox($post_id, $post){

		if(!isset($_POST['siteseo_content_analysis_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['siteseo_content_analysis_nonce'])), 'siteseo_ca_nonce')){
			return $post_id;
		}

		// Post type object
		$post_type = get_post_type_object($post->post_type);

		//Check permission
		if(!current_user_can($post_type->cap->edit_post, $post_id) || !siteseo_user_can_metabox()){
			return $post_id;
		}

		if('attachment' !== get_post_type($post_id)){
			if(isset($_POST['siteseo_analysis_target_kw'])){
				update_post_meta($post_id, '_siteseo_analysis_target_kw', self::clean_post('siteseo_analysis_target_kw'));
			} else{
				delete_post_meta($post_id, '_siteseo_analysis_target_kw');
			}
		}
	}
	
	
	static function save_metabox($post_id, $post){
		
		global $siteseo;
		
		// Security Check
		if(!isset($_POST['siteseo_metabox_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['siteseo_metabox_nonce'])), 'siteseo_metabox_nonce')){
			return $post_id;
		}

		//Post type object
		$post_type = get_post_type_object($post->post_type);
		
		//Check permission
		if(!current_user_can($post_type->cap->edit_post, $post_id) || !siteseo_user_can_metabox()){
			return $post_id;
		}
		
		if('attachment' !== get_post_type($post_id)){
			$analysis_tabs = [];
			$analysis_tabs = json_decode(self::clean_post('analysis_tabs'), true);
			
			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('content-analysis', $analysis_tabs)){
				if(!empty($_POST['siteseo_analysis_target_kw'])){
					update_post_meta($post_id, '_siteseo_analysis_target_kw', self::clean_post('siteseo_analysis_target_kw'));
				} else{
					delete_post_meta($post_id, '_siteseo_analysis_target_kw');
				}
			}
			
			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('title-settings', $analysis_tabs)){
				if(!empty($_POST['siteseo_titles_title'])){
					update_post_meta($post_id, '_siteseo_titles_title', self::clean_post('siteseo_titles_title'));
				} else{
					delete_post_meta($post_id, '_siteseo_titles_title');
				}
				if(!empty($_POST['siteseo_titles_desc'])){
					update_post_meta($post_id, '_siteseo_titles_desc', self::clean_post('siteseo_titles_desc'));
				} else{
					delete_post_meta($post_id, '_siteseo_titles_desc');
				}
			}
			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('advanced-settings', $analysis_tabs)){
				
				if(isset($_POST['siteseo_robots_index'])){
					update_post_meta($post_id, '_siteseo_robots_index', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_robots_index');
				}
				
				if(isset($_POST['siteseo_robots_follow'])){
					update_post_meta($post_id, '_siteseo_robots_follow', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_robots_follow');
				}
				
				if(isset($_POST['siteseo_robots_imageindex'])){
					update_post_meta($post_id, '_siteseo_robots_imageindex', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_robots_imageindex');
				}
				
				if(isset($_POST['siteseo_robots_archive'])){
					update_post_meta($post_id, '_siteseo_robots_archive', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_robots_archive');
				}
				
				if(isset($_POST['siteseo_robots_snippet'])){
					update_post_meta($post_id, '_siteseo_robots_snippet', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_robots_snippet');
				}
				
				if(!empty($_POST['siteseo_robots_canonical'])){
					update_post_meta($post_id, '_siteseo_robots_canonical', self::clean_post('siteseo_robots_canonical'));
				} else{
					delete_post_meta($post_id, '_siteseo_robots_canonical');
				}
				
				if(!empty($_POST['siteseo_robots_primary_cat'])){
					update_post_meta($post_id, '_siteseo_robots_primary_cat', self::clean_post('siteseo_robots_primary_cat'));
				} else{
					delete_post_meta($post_id, '_siteseo_robots_primary_cat');
				}
			}

			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('social-settings', $analysis_tabs)){
				//Facebook
				if(!empty($_POST['siteseo_social_fb_title'])){
					update_post_meta($post_id, '_siteseo_social_fb_title', self::clean_post('siteseo_social_fb_title'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_fb_title');
				}
				
				if(!empty($_POST['siteseo_social_fb_desc'])){
					update_post_meta($post_id, '_siteseo_social_fb_desc', self::clean_post('siteseo_social_fb_desc'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_fb_desc');
				}
				
				if(!empty($_POST['siteseo_social_fb_img'])){
					update_post_meta($post_id, '_siteseo_social_fb_img', self::clean_post('siteseo_social_fb_img'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_fb_img');
				}
				
				if(!empty($_POST['siteseo_social_fb_img_attachment_id']) && !empty($_POST['siteseo_social_fb_img'])){
					update_post_meta($post_id, '_siteseo_social_fb_img_attachment_id', self::clean_post('siteseo_social_fb_img_attachment_id'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_fb_img_attachment_id');
				}
				
				if(!empty($_POST['siteseo_social_fb_img_width']) && !empty($_POST['siteseo_social_fb_img'])){
					update_post_meta($post_id, '_siteseo_social_fb_img_width', self::clean_post('siteseo_social_fb_img_width'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_fb_img_width');
				}
				
				if(!empty($_POST['siteseo_social_fb_img_height']) && !empty($_POST['siteseo_social_fb_img'])){
					update_post_meta($post_id, '_siteseo_social_fb_img_height', self::clean_post('siteseo_social_fb_img_height'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_fb_img_height');
				}

				//Twitter
				if(!empty($_POST['siteseo_social_twitter_title'])){
					update_post_meta($post_id, '_siteseo_social_twitter_title', self::clean_post('siteseo_social_twitter_title'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_twitter_title');
				}
				
				if(!empty($_POST['siteseo_social_twitter_desc'])){
					update_post_meta($post_id, '_siteseo_social_twitter_desc', self::clean_post('siteseo_social_twitter_desc'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_twitter_desc');
				}
				
				if(!empty($_POST['siteseo_social_twitter_img'])){
					update_post_meta($post_id, '_siteseo_social_twitter_img', self::clean_post('siteseo_social_twitter_img'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_twitter_img');
				}
				
				if(!empty($_POST['siteseo_social_twitter_img_attachment_id']) && !empty($_POST['siteseo_social_twitter_img'])){
					update_post_meta($post_id, '_siteseo_social_twitter_img_attachment_id', self::clean_post('siteseo_social_twitter_img_attachment_id'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_twitter_img_attachment_id');
				}
				
				if(!empty($_POST['siteseo_social_twitter_img_width']) && !empty($_POST['siteseo_social_twitter_img'])){
					update_post_meta($post_id, '_siteseo_social_twitter_img_width', self::clean_post('siteseo_social_twitter_img_width'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_twitter_img_width');
				}
				
				if(!empty($_POST['siteseo_social_twitter_img_height']) && !empty($_POST['siteseo_social_twitter_img'])){
					update_post_meta($post_id, '_siteseo_social_twitter_img_height', self::clean_post('siteseo_social_twitter_img_height'));
				} else{
					delete_post_meta($post_id, '_siteseo_social_twitter_img_height');
				}
			}

			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('redirect', $analysis_tabs)){
				if(isset($_POST['siteseo_redirections_type'])){
					update_post_meta($post_id, '_siteseo_redirections_type', self::clean_post('siteseo_redirections_type'));
				}
				
				if(!empty($_POST['siteseo_redirections_value'])){
					update_post_meta($post_id, '_siteseo_redirections_value', self::clean_post('siteseo_redirections_value'));
				} else{
					delete_post_meta($post_id, '_siteseo_redirections_value');
				}
				
				if(isset($_POST['siteseo_redirections_param'])){
					update_post_meta($post_id, '_siteseo_redirections_param', self::clean_post('siteseo_redirections_param'));
				}
				
				if(isset($_POST['siteseo_redirections_enabled'])){
					update_post_meta($post_id, '_siteseo_redirections_enabled', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_redirections_enabled', '');
				}
				
				if(isset($_POST['siteseo_redirections_enabled_regex'])){
					update_post_meta($post_id, '_siteseo_redirections_enabled_regex', 'yes');
				} else{
					delete_post_meta($post_id, '_siteseo_redirections_enabled_regex');
				}
				
				if(isset($_POST['siteseo_redirections_logged_status'])){
					update_post_meta($post_id, '_siteseo_redirections_logged_status', self::clean_post('siteseo_redirections_logged_status'));
				} else{
					delete_post_meta($post_id, '_siteseo_redirections_logged_status');
				}
			}
			
			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('structured-data-types', $analysis_tabs)){
				if(class_exists('\SiteSEOPro\StructuredData') && method_exists('\SiteSEOPro\StructuredData', 'save_metabox')){
					\SiteSEOPro\StructuredData::save_metabox($post_id, $post);
				}
			}
			
			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('video-sitemap', $analysis_tabs)){
				if(class_exists('\SiteSEOPro\VideoSitemap') && method_exists('\SiteSEOPro\VideoSitemap', 'save_video_sitemap')){
					\SiteSEOPro\VideoSitemap::save_video_sitemap($post_id, $post);
				}
			}
			
			if(!empty($analysis_tabs) && is_array($analysis_tabs) && in_array('google-news', $analysis_tabs)){
				if(class_exists('\SiteSEOPro\GoogleNews') && method_exists('\SiteSEOPro\GoogleNews', 'save_google_news')){
					\SiteSEOPro\GoogleNews::save_google_news($post_id, $post);
				}
			}
		}
	}

	static function clean_post($name){
		return self::clean_post_req($name);
	}
	
	static function clean_get($name){
		return self::clean_post_req($name);
	}
	
	static function clean_post_req($name){
		if(empty($name)){
			return '';
		}
	
		if(!isset($_REQUEST[$name])){
			return '';
		}
	
		if(is_array($_REQUEST[$name]) || is_object($_REQUEST[$name])){
			return map_deep(wp_unslash($_REQUEST[$name]), 'sanitize_text_field');
		}

		return sanitize_text_field(wp_unslash($_REQUEST[$name]));
	}
	
	static function universal(){
		global $siteseo, $pagenow, $post;
		
		$post_id = !empty($_REQUEST['post']) ? (int) sanitize_text_field(wp_unslash($_REQUEST['post'])) : 0;
		
		if(empty($post_id)){
			return;
		}
		
		if(!current_user_can('edit_post', $post_id)){
			wp_die(esc_html__('You do not have access to edit this post', 'siteseo'));
		}
		
		$tmp_post = $post;
		$post = get_post($post_id);
		$tmp_pagenow = $pagenow;
		$pagenow = 'post.php';

		if(empty($post)){
			$post = $tmp_post;
			return;
		}
		
		set_current_screen($post->post_type);

		echo '<style>body{height: 100vh;} #wpcontent,#wpbody-content,html.wp-toolbar{padding:0;} .postbox .handle-order-higher, .postbox .handle-order-lower,#minor-publishing-actions,.site-menu-header{display:none !important;} #adminmenumain, #wpfooter, #wpadminbar, #wpwrap > :first-child,  #wpwrap > :nth-child(2) .lnav-col{display:none;} #wpcontent{margin:auto;} #wpbody-content{position:relative;} .siteseo-metabox-tab{background-color:white;} .siteseo-meta-submit-container{position:fixed;bottom: 20px;right : 20px;}  #siteseo_cpt form {position:relative;}.siteseo-btn{display: inline-flex;padding: 0.5rem 1rem;gap: 0.5rem;justify-content: center;align-items: center;border-radius: 0.375rem;font-size: 0.875rem;line-height: 1.25rem;font-weight: 500;white-space: nowrap;cursor:pointer;box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);border:none;text-decoration:none; text-align:center;}.siteseo-btn.primary{background-color:#141b38;color:white;} #wpwrap > :nth-child(2) > div:nth-child(2){max-width:100%}
		.siteseo-spinner{display:none;border-radius:50%;animation: siteseo-spinner 1s linear infinite;height: 0.9375rem;width: 0.9375rem;border: 2px solid #dddcdc80;border-left-color: #e3e3e3;} .siteseo-spinner-active{display:inline-block;} @keyframes siteseo-spinner{ 0% { transform: rotate(0deg);} 100% {transform: rotate(360deg);}} .components-button{align-items: center; -webkit-appearance: none; background: none; border: 0; border-radius: 2px; box-sizing: border-box; color:#1e1e1e; cursor: pointer;display: inline-flex; font-family: inherit; font-size: 13px; font-weight: 400; height: 36px; margin: 0; padding: 6px 12px; text-decoration: none; transition: box-shadow .1s linear;}.components-button.is-secondary {background: #0000; box-shadow: inset 0 0 0 1px #3858e9; color:#3858e9; outline: 1px solid #0000; white-space: nowrap;}.siteseo-sidebar-tabs{display:none;} .notice, .update-nag{ display: none !important;}</style>
		<div id="siteseo_cpt"><form id="siteseo-universal-post" action="post.php" method="post">
		<input type="hidden" name="post_id" value="'.esc_attr($post_id).'"/>';
		wp_nonce_field('siteseo_universal_nonce', 'security');
		self::render_metabox();
		
		echo '<div class="siteseo-meta-submit-container">
			<button type="submit" class="siteseo-btn primary">'.esc_html__('Save Changes', 'siteseo').'<span class="siteseo-spinner"></span></button>
		</div></form></div>
		<script>
		jQuery(document).ready(function(){
			jQuery("#siteseo-universal-post").on("submit", function(event){
				event.preventDefault();
				let jEle = jQuery(event.target),
				spinner = jEle.find(".siteseo-spinner"),
				formData = {};

				jQuery(this).serializeArray().forEach(field => {
					formData[field.name] = field.value;
				});

				formData["action"] = "siteseo_save_universal_metabox";
				
				spinner.addClass("siteseo-spinner-active");

				jQuery.ajax({
					method : "POST",
					url : "'.esc_url(admin_url('admin-ajax.php')).'",
					data : formData,
					success : function(res){
						//console.log(res);
					}
				}).always(function(){
					spinner.removeClass("siteseo-spinner-active");
				})
			});
		});
		</script>';

		$post = $tmp_post;
		$pagenow = $tmp_pagenow;

		global $wp_version;

		if(!empty($wp_version) && version_compare($wp_version, '6.4', '>')){
			remove_action('wp_footer', 'the_block_template_skip_link');
		}

		wp_footer();
		exit;
	}
	
	static function render_term_metabox($term, $taxonomy_name = ''){
		$metabox_data = self::metabox_term_data($term);
		self::metabox_form_html($metabox_data);
	}
	
	static function save_meta_terms($term_id, $post_id = 0){

		// Security Check
		if(!isset($_POST['siteseo_metabox_nonce']) || !wp_verify_nonce(self::clean_post('siteseo_metabox_nonce'), 'siteseo_metabox_nonce') ){
			return $term_id;
		}
		
		// Getting taxonomy
		$term = get_term($term_id);
		$taxonomy = get_taxonomy($term->taxonomy);

		// Is this user allowed to make these changes
		if(!current_user_can($taxonomy->cap->edit_terms, $term_id)) {
			return $term_id;
		}

		$analysis_tabs = [];
		$analysis_tabs = json_decode(self::clean_post('analysis_tabs'), true);
		
		if(empty($analysis_tabs) || !is_array($analysis_tabs)){
			return $term_id;
		}

		$tabs = [
			'title-settings' => [
				'siteseo_titles_title' => '_siteseo_titles_title',
				'siteseo_titles_desc' => '_siteseo_titles_desc',
			],
			'advanced-settings' => [
				'siteseo_robots_index' => '_siteseo_robots_index',
				'siteseo_robots_follow' => '_siteseo_robots_follow',
				'siteseo_robots_imageindex'=> '_siteseo_robots_imageindex',
				'siteseo_robots_archive' => '_siteseo_robots_archive',
				'siteseo_robots_snippet' => '_siteseo_robots_snippet',
				'siteseo_robots_canonical' => '_siteseo_robots_canonical',
			],
			'social-settings' => [
				'siteseo_social_fb_title' => '_siteseo_social_fb_title',
				'siteseo_social_fb_desc' => '_siteseo_social_fb_desc',
				'siteseo_social_fb_img' => '_siteseo_social_fb_img',
				'siteseo_social_fb_img_attachment_id' => '_siteseo_social_fb_img_attachment_id',
				'siteseo_social_fb_img_width' => '_siteseo_social_fb_img_width',
				'siteseo_social_fb_img_height' => '_siteseo_social_fb_img_height',
				'siteseo_social_twitter_title' => '_siteseo_social_twitter_title',
				'siteseo_social_twitter_desc' => '_siteseo_social_twitter_desc',
				'siteseo_social_twitter_img' => '_siteseo_social_twitter_img',
			],
			'redirect' => [
				'siteseo_redirections_type' => '_siteseo_redirections_type',
				'siteseo_redirections_logged_status' => '_siteseo_redirections_logged_status',
				'siteseo_redirections_value' => '_siteseo_redirections_value',
				'siteseo_redirections_enabled' => '_siteseo_redirections_enabled',
			]
		];
		
		// Save the key for all the options which are checkboxes
		$is_checkboxes = [
			'siteseo_robots_index',
			'siteseo_robots_follow',
			'siteseo_robots_imageindex',
			'siteseo_robots_archive',
			'siteseo_robots_snippet',
			'siteseo_redirections_enabled',
		];

		foreach($tabs as $tab => $fields){
			if(!in_array($tab, $analysis_tabs)){
				continue;
			}

			foreach($fields as $post_key => $meta_key){
				if(!empty($_POST[$post_key])){
					$value = in_array($post_key, $is_checkboxes) ? 'yes' : self::clean_post($post_key);
					update_term_meta($term_id, $meta_key, $value);
				} else {
					delete_term_meta($term_id, $meta_key);
				}
			}
		}

		return $term_id;
	}
}

