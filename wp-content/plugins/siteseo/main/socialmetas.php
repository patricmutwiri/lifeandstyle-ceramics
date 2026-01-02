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

class SocialMetas{

	static function add_social_graph(){
		global $siteseo;

		if(empty($siteseo->setting_enabled['toggle-social'])){
			return;
		}

		$org_type = !empty($siteseo->social_settings['social_knowledge_type']) && $siteseo->social_settings['social_knowledge_type'] !== 'none' ? $siteseo->social_settings['social_knowledge_type'] : '';
		$org_name = !empty($siteseo->social_settings['social_knowledge_name']) ? $siteseo->social_settings['social_knowledge_name'] : ''; 
		$org_logo = !empty($siteseo->social_settings['social_knowledge_img']) ? $siteseo->social_settings['social_knowledge_img'] : '';
		$org_number = !empty($siteseo->social_settings['social_knowledge_phone']) ? $siteseo->social_settings['social_knowledge_phone'] : '';
		$org_contact_type = !empty($siteseo->social_settings['social_knowledge_contact_type']) ? $siteseo->social_settings['social_knowledge_contact_type'] : '';
		$org_contact_option = !empty($siteseo->social_settings['social_knowledge_contact_option']) ? $siteseo->social_settings['social_knowledge_contact_option'] : '';

		$fb_account = !empty($siteseo->social_settings['social_accounts_facebook']) ? $siteseo->social_settings['social_accounts_facebook'] : '';
		$twitter_account = !empty($siteseo->social_settings['social_accounts_twitter']) ? $siteseo->social_settings['social_accounts_twitter'] : '';
		$insta_account = !empty($siteseo->social_settings['social_accounts_instagram']) ? $siteseo->social_settings['social_accounts_instagram'] : '';
		$yt_account = !empty($siteseo->social_settings['social_accounts_youtube']) ? $siteseo->social_settings['social_accounts_youtube'] : '';
		$pt_account = !empty($siteseo->social_settings['social_accounts_pinterest']) ? $siteseo->social_settings['social_accounts_pinterest'] : '';

		//description
		$site_url = get_site_url();
		$site_description = get_bloginfo('name');
		$site_language = get_bloginfo('language');

		// logo
		$logo_id = attachment_url_to_postid($org_logo);

		// Defaults
		$logo_width  = '';
		$logo_height = '';

		// Get image dimensions if attachment exists
		if(!empty($logo_id)){
			$image_data = wp_get_attachment_image_src($logo_id, 'full');
			if(!empty($image_data)){
				$logo_width = $image_data[1];
				$logo_height = $image_data[2];
			}
		}

		//JSON-LD data
		$json_ld = [
			'@context' => 'https://schema.org',
			'@type' => $org_type ? esc_html($org_type) : 'Organization',
			'@id' => esc_url(trailingslashit($site_url) . '#' . $org_type),
			'name' => esc_html($org_name),
			'url' => esc_url($site_url),
			'logo' => array_filter([
				'@type' => 'ImageObject',
				'@id' => esc_url(trailingslashit($site_url) . '#logo'),
				'url' => esc_url($org_logo),
				'contentUrl' => esc_url($org_logo),
				'caption' => esc_html($org_name),
				'inLanguage' => esc_html($site_language),
				'width' => $logo_width,
				'height' => $logo_height,
			]),
			'description' => esc_html($site_description),
		];

		//contact point
		if(!empty($org_contact_type) && !empty($org_number)){
			$json_ld['contactPoint'] = [
				'@type' => 'ContactPoint',
				'contactType' => esc_html($org_contact_type),
				'telephone' => esc_html($org_number),
			];

			if(!empty($org_contact_option) && $org_contact_option !== 'None'){
				$contact_point['contactOption'] = esc_html($org_contact_option);
			}
		}

		$x_url = 'https://x.com/'.$twitter_account;

		$same_as = array_filter([
			esc_url($fb_account), 
			esc_url($x_url), 
			esc_url($insta_account), 
			esc_url($yt_account), 
			esc_url($pt_account)
		]);

		if(!empty($same_as)){
			$json_ld['sameAs'] = array_values($same_as);
		}

		// Output JSON-LD script
		echo '<script type="application/ld+json">';
		echo wp_json_encode($json_ld, JSON_UNESCAPED_SLASHES);
		echo '</script>';
	}

	static function fb_graph(){
		global $siteseo, $post;

		if(empty($siteseo->setting_enabled['toggle-social']) || empty($siteseo->social_settings['social_facebook_og'])){
			return;
		}

		$fb_page_id = !empty($siteseo->social_settings['social_facebook_link_ownership_id']) ? $siteseo->social_settings['social_facebook_link_ownership_id'] : '';
		$fb_link_owership = !empty($siteseo->social_settings['social_facebook_admin_id']) ? $siteseo->social_settings['social_facebook_admin_id'] : '';
		$og_url = get_home_url();
		$og_sitename = get_bloginfo('name');
		
		// Check
		$post_id = isset($post) && is_object($post) ? $post->ID : '';
		$og_title = get_the_title();
		$og_description = get_bloginfo('description');
		$og_img = !empty($siteseo->social_settings['social_facebook_img']) ? $siteseo->social_settings['social_facebook_img'] : '';

		// Get post types and taxonomies
		$post_types = siteseo_post_types();
		$taxonomies = get_taxonomies(array('public' => true), 'objects');
		
		// home site page
		if(is_home() && is_front_page()){
			$og_title = !empty($siteseo->titles_settings['titles_home_site_title']) ? $siteseo->titles_settings['titles_home_site_title'] : $og_title;
			$og_description = !empty($siteseo->titles_settings['titles_home_site_desc']) ? $siteseo->titles_settings['titles_home_site_desc'] : $og_description;
		}
		
		// single post types
		foreach($post_types as $post_type){
			
			// shop page woocommerces
			if(function_exists('is_shop') && is_shop()){
				$shop_page_id = !defined('SITEPAD') ? wc_get_page_id('shop') : kkart_get_page_id('shop');
				
				$archive_title = '';
				$archive_desc = '';
				
				if(is_post_type_archive()){
					$obj = get_queried_object();
					
					if(!empty($obj) && isset($obj->name)){
						$archive_title = !empty($obj->labels->name) ? $obj->labels->name : '';
						$archive_desc  = !empty($obj->description) ? $obj->description : '';
					}
				}
				
				if(!empty(get_post_meta($shop_page_id, '_siteseo_social_fb_title', true))){
					$og_title = get_post_meta($shop_page_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_post_meta($shop_page_id, '_siteseo_titles_title', true))){
					$og_title = get_post_meta($shop_page_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_archive_titles']['product']['archive_title'])){
					$og_title = $siteseo->titles_settings['titles_archive_titles']['product']['archive_title'];
				} else {
					$og_title = $archive_title;
				}
				
				
				$og_description = !empty(get_post_meta($shop_page_id, '_siteseo_social_fb_desc', true)) ? get_post_meta($shop_page_id, '_siteseo_social_fb_desc', true) : $og_description;
							
				if(!empty(get_post_meta($shop_page_id, '_siteseo_social_fb_desc', true))){
					$og_description = get_post_meta($shop_page_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_post_meta($shop_page_id, '_siteseo_titles_desc', true))){
					$og_description = get_post_meta($shop_page_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_archive_titles']['product']['archive_desc'])){
					$og_description = $siteseo->titles_settings['titles_archive_titles']['product']['archive_desc'];
				} else {
					$og_description = $archive_desc;
				}
				
				$og_description = esc_attr(\SiteSEO\TitlesMetas::replace_variables($og_description));
				
				// OG:IMG
				if(!empty(get_post_meta($shop_page_id, '_siteseo_social_fb_img', true))){
					$og_img = get_post_meta($shop_page_id, '_siteseo_social_fb_img', true);
				} else if(get_the_post_thumbnail_url($post, 'full')){
					$og_img = get_the_post_thumbnail_url($post, 'full');
				} else {
					$og_img = !empty($siteseo->social_settings['social_facebook_img']) ? $siteseo->social_settings['social_facebook_img'] : '';
				}
				
				$og_url = urldecode(get_permalink($shop_page_id));
				break;
			}
			
			// archive page
			if($post_type->has_archive && is_post_type_archive($post_type->name)){
				
				$archive_title = '';
				$archive_desc = '';
				
				if(is_post_type_archive()){
					$obj = get_queried_object();
					
					if(!empty($obj) && isset($obj->name)){
						$archive_title = !empty($obj->labels->name) ? $obj->labels->name : '';
						$archive_desc  = !empty($obj->description) ? $obj->description : '';
					}
				}
				
				$og_title = !empty($siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_title']) ? $siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_title'] : $archive_title;
				$og_description = !empty($siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_desc']) ? $siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_desc'] : $archive_desc;
				
			}
			
			// blog page
			if(is_home() && !is_front_page()){
				$post_id = get_option('page_for_posts');
				
				//OG:title
				if(!empty(get_post_meta($post_id, '_siteseo_social_fb_title', true))){
					$og_title = get_post_meta($post_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_title', true))){
					$og_title = get_post_meta($post_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'])){
					$og_title = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'];
				} else{
					$og_title = $og_title;
				}

				// og:description
				if(!empty(get_post_meta($post_id, '_siteseo_social_fb_desc', true))){
					$og_description = get_post_meta($post_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_desc', true))){
					$og_description = get_post_meta($post_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'])){
					$og_description = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'];
				} elseif(get_the_excerpt($post_id)){
					$og_description = wp_trim_words(get_the_excerpt($post_id), 50);
				}
			}
			
			if(is_singular($post_type->name)){
				
				if(!empty(get_post_meta($post_id, '_siteseo_social_fb_title', true))){
					$og_title = get_post_meta($post_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_title', true))){
					$og_title = get_post_meta($post_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'])){
					$og_title = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'];
				} else{
					$og_title = $og_title;
				}
				
				
				// og:description
				if(!empty(get_post_meta($post_id, '_siteseo_social_fb_desc', true))){
					$og_description = get_post_meta($post_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_desc', true))){
					$og_description = get_post_meta($post_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'])){
					$og_description = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'];
				} elseif(get_the_excerpt($post_id)){
					$og_description = wp_trim_words(get_the_excerpt($post_id), 50);
				}
				
				// OG:IMG
				if(!empty(get_post_meta($post_id, '_siteseo_social_fb_img', true))){
					$og_img = get_post_meta($post_id, '_siteseo_social_fb_img', true);
				} else if(get_the_post_thumbnail_url($post, 'full')){
					$og_img = get_the_post_thumbnail_url($post, 'full');
				} else {
					$og_img = !empty($siteseo->social_settings['social_facebook_img']) ? $siteseo->social_settings['social_facebook_img'] : '';
				}

				$og_url = urldecode(get_permalink($post_id));
				break;
			}
		}

		//  taxonomies
		foreach($taxonomies as $taxonomy){
			if(is_tax($taxonomy->name) || is_category() || is_tag()){
				$term = get_queried_object();
				$term_id = $term->term_id;	
				
				// og:title
				if(!empty(get_term_meta($term_id, '_siteseo_social_fb_title', true))){
					$og_title = get_term_meta($term_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_term_meta($term_id, '_siteseo_titles_title', true))){
					$og_title = get_term_meta($term_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['title'])){
					$og_title = $siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['title'];
				} else{
					$og_title = $og_title;
				}
								
				// og:description
				if(!empty(get_term_meta($term_id, '_siteseo_social_fb_desc', true))){
					$og_description = get_term_meta($term_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_term_meta($term_id, '_siteseo_titles_desc', true))){
					$og_description = get_term_meta($term_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['description'])){
					$og_description = $siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['description'];
				} else{
					$og_description = wp_strip_all_tags(term_description($term_id));
				}
				
				$og_img = !empty(get_term_meta($term_id, '_siteseo_social_fb_img', true)) ? get_term_meta($term_id, '_siteseo_social_fb_img', true) : $og_img;
				$og_url = urldecode(get_term_link($term_id));
				break;
			}
		}
		
		$og_title = esc_attr(\SiteSEO\TitlesMetas::replace_variables($og_title));
		$og_description = esc_attr(\SiteSEO\TitlesMetas::replace_variables($og_description));

		if(!empty($og_img)){
			$og_img = sanitize_url($og_img);
			$og_img_width = 0;
			$og_img_height = 0;

			if(!empty($og_img)){
				$image_info = @getimagesize($og_img);

				if($image_info !== false){
					$og_img_width = $image_info[0];
					$og_img_height = $image_info[1];
				}
			}
		}

		// Setting og:type
		if(is_home() || is_front_page()){
			$og_type = 'website'; // default website
		} elseif(is_singular('product') || is_singular('download')){
			$og_type = 'product';
		} elseif(is_singular()){
			$og_type = 'article';
		} elseif(is_search() || is_archive() || is_404()){
			$og_type = 'object';
		}

		if(!empty($og_url)){
			echo '<meta property="og:url" content="'.esc_html($og_url).'" />';
		}

		if(!empty($og_sitename)){
			echo '<meta property="og:site_name" content="'.esc_html($og_sitename).'" />';
		}

		if(function_exists('get_locale')){
			echo '<meta property="og:locale" content="'.esc_html(get_locale()).'" />';
		}

		if(!empty($og_type)){
			echo '<meta property="og:type" content="'.esc_attr($og_type).'" />';
		}

		if(!empty($og_title)){
			echo '<meta property="og:title" content="'.esc_html($og_title).'" />';
		}

		if(!empty($og_description)){
			echo '<meta property="og:description" content="'.esc_html($og_description).'" />';
		}

		if(!empty($og_img)){
			echo '<meta property="og:image" content="'.esc_html($og_img).'" />';

			if(is_ssl()){
				echo '<meta property="og:secure_url" content="'.esc_html($og_img).'" />';
			}
		}

		if(!empty($og_img_height)){
			echo '<meta property="og:image:height" content="'.esc_attr($og_img_height).'" />';
		}

		if(!empty($og_img_width)){
			echo '<meta property="og:image:width" content="'.esc_attr($og_img_width).'" />';
		}

		if(!empty($fb_page_id)){
			echo '<meta property="fb:pages" content="'.esc_html($fb_page_id) .'" />';
		}

		if(!empty($fb_link_owership)){
			echo '<meta property="fb:admins" content="'. esc_html($fb_link_owership).'" />';
		}
	}

	static function twitter_card(){
		global $siteseo, $post;

		if(empty($siteseo->setting_enabled['toggle-social']) || empty($siteseo->social_settings['social_twitter_card'])){
			return;
		}

		$site_url = get_home_url();
		$twitter_user_name = !empty($siteseo->social_settings['social_accounts_twitter']) ? $siteseo->social_settings['social_accounts_twitter'] : '';

		$post_id = isset($post) && is_object($post) ? $post->ID : '';
		$site_title = get_the_title();
		$site_description = get_bloginfo('description');
		$twitter_img = !empty($siteseo->social_settings['social_twitter_card_img']) ? $siteseo->social_settings['social_twitter_card_img'] : '';
		
		if(empty($twitter_img)){
			$twitter_img = !empty($siteseo->social_settings['social_facebook_img']) ? $siteseo->social_settings['social_facebook_img'] : '';
		}
		
		// home site page
		if(is_home() && is_front_page()){
			$site_title = !empty($siteseo->titles_settings['titles_home_site_title']) ? $siteseo->titles_settings['titles_home_site_title'] : $site_title;
			$site_description = !empty($siteseo->titles_settings['titles_home_site_desc']) ? $siteseo->titles_settings['titles_home_site_desc'] : $site_description;
		}

		// types and taxonomies
		$post_types = siteseo_post_types();
		$taxonomies = get_taxonomies(array('public' => true), 'objects');
		// single post types
		foreach($post_types as $post_type){
			
			// woocommerce
			if(function_exists('is_shop') && is_shop()){
				$shop_page_id = !defined('SITEPAD') ? wc_get_page_id('shop') : kkart_get_page_id('shop');
				
				$archive_title = '';
				$archive_desc = '';
				
				// if all x-meta tags empty then use og option is enabled
				$use_og = (empty(get_post_meta($post_id, '_siteseo_social_twitter_title', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_desc', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_img', true)));
				
				if(is_post_type_archive()){
					$obj = get_queried_object();
					
					if(!empty($obj) && isset($obj->name)){
						$archive_title = !empty($obj->labels->name) ? $obj->labels->name : '';
						$archive_desc  = !empty($obj->description) ? $obj->description : '';
					}
				}
				
				// twitter:title
				if(!empty(get_post_meta($shop_page_id, '_siteseo_social_twitter_title', true))){
					$site_title = get_post_meta($shop_page_id, '_siteseo_social_twitter_title', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($shop_page_id, '_siteseo_social_fb_title', true))){ 
					$site_title = get_post_meta($shop_page_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_post_meta($shop_page_id, '_siteseo_titles_title', true))){
					$site_title = get_post_meta($shop_page_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_archive_titles']['product']['archive_title'])){
					$site_title = $siteseo->titles_settings['titles_archive_titles']['product']['archive_title'];
				} else {
					$site_title = $archive_title;
				}
							
				
				// twitter:description
				if(!empty(get_post_meta($shop_page_id, '_siteseo_social_twitter_desc', true))){
					$site_description = get_post_meta($shop_page_id, '_siteseo_social_twitter_desc', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($shop_page_id, '_siteseo_social_fb_desc', true))){ 
					$site_description = get_post_meta($shop_page_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_post_meta($shop_page_id, '_siteseo_titles_desc', true))){
					$site_description = get_post_meta($shop_page_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_archive_titles']['product']['archive_desc'])){
					$site_description = $siteseo->titles_settings['titles_archive_titles']['product']['archive_desc'];
				} else {
					$site_description = $archive_desc;
				}
				
				// twitter:image
				if(!empty(get_post_meta($shop_page_id, '_siteseo_social_twitter_img', true))){
					$twitter_img = get_post_meta($shop_page_id, '_siteseo_social_twitter_img', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($shop_page_id, '_siteseo_social_fb_img', true))){ 
					$twitter_img = get_post_meta($shop_page_id, '_siteseo_social_fb_img', true);
				}  elseif(get_the_post_thumbnail_url($post, 'full')){
					$twitter_img = get_the_post_thumbnail_url($post, 'full');
				} else {
					$twitter_img = isset($siteseo->social_settings['social_twitter_card_img']) ? $siteseo->social_settings['social_twitter_card_img'] : '';
				}

				$site_url = urldecode(get_permalink($shop_page_id));
				break;
			}
			
			// archive page
			if($post_type->has_archive && is_post_type_archive($post_type->name)){
				
				$archive_title = '';
				$archive_desc = '';
				
				if(is_post_type_archive()){
					$obj = get_queried_object();
					
					if(!empty($obj) && isset($obj->name)){
						$archive_title = !empty($obj->labels->name) ? $obj->labels->name : '';
						$archive_desc  = !empty($obj->description) ? $obj->description : '';
					}
				}
				
				$site_title = !empty($siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_title']) ? $siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_title'] : $archive_title;
				$site_description = !empty($siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_desc']) ? $siteseo->titles_settings['titles_archive_titles'][$post_type->name]['archive_desc'] : $archive_desc;
				
			}
			
			// blog page
			if(is_home() && !is_front_page()){
				$post_id = get_option('page_for_posts');
				$use_og = (empty(get_post_meta($post_id, '_siteseo_social_twitter_title', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_desc', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_img', true)));
				
				// twitter:title
				if(!empty(get_post_meta($post_id, '_siteseo_social_twitter_title', true))){
					$site_title = get_post_meta($post_id, '_siteseo_social_twitter_title', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($post_id, '_siteseo_social_fb_title', true))){ 
					$site_title = get_post_meta($post_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_title', true))){
					$site_title = get_post_meta($post_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'])){
					$site_title = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'];
				} else{
					$site_title = $site_title;
				}
				
				// twitter:description
				if(!empty(get_post_meta($post_id, '_siteseo_social_twitter_desc', true))){
					$site_description = get_post_meta($post_id, '_siteseo_social_twitter_desc', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($post_id, '_siteseo_social_fb_desc', true))){ 
					$site_description = get_post_meta($post_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_desc', true))){
					$site_description = get_post_meta($post_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'])){
					$site_description = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'];
				} elseif(!empty(get_the_excerpt($post_id))){
					$site_description = wp_trim_words(get_the_excerpt($post_id), 50);
				}
			}
			
			if(is_singular($post_type->name)){
				
				$use_og = (empty(get_post_meta($post_id, '_siteseo_social_twitter_title', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_desc', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_img', true)));
				
				// twitter:title
				if(!empty(get_post_meta($post_id, '_siteseo_social_twitter_title', true))){
					$site_title = get_post_meta($post_id, '_siteseo_social_twitter_title', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($post_id, '_siteseo_social_fb_title', true))){ 
					$site_title = get_post_meta($post_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_title', true))){
					$site_title = get_post_meta($post_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'])){
					$site_title = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['title'];
				} else{
					$site_title = $site_title;
				}
				
				
				// twitter:description
				if(!empty(get_post_meta($post_id, '_siteseo_social_twitter_desc', true))){
					$site_description = get_post_meta($post_id, '_siteseo_social_twitter_desc', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($post_id, '_siteseo_social_fb_desc', true))){ 
					$site_description = get_post_meta($post_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_post_meta($post_id, '_siteseo_titles_desc', true))){
					$site_description = get_post_meta($post_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'])){
					$site_description = $siteseo->titles_settings['titles_single_titles'][$post_type->name]['description'];
				} else{
					$site_description = wp_trim_words(get_the_excerpt($post_id), 50);
				}

				// twitter:image
				if(!empty(get_post_meta($post_id, '_siteseo_social_twitter_img', true))){
					$twitter_img = get_post_meta($post_id, '_siteseo_social_twitter_img', true);
				} elseif(!empty($use_og) && !empty(get_post_meta($post_id, '_siteseo_social_fb_img', true))){ 
					$twitter_img = get_post_meta($post_id, '_siteseo_social_fb_img', true);
				} else if(get_the_post_thumbnail_url($post, 'full')){
					$twitter_img = get_the_post_thumbnail_url($post, 'full');
				} else {
					$twitter_img = isset($siteseo->social_settings['social_twitter_card_img']) ? $siteseo->social_settings['social_twitter_card_img'] : '';
				}

				$site_url = urldecode(get_permalink($post_id));
				break;
			}
		}

		//taxonomies
		foreach($taxonomies as $taxonomy){
			
			if(is_tax($taxonomy->name) || is_category() || is_tag()){
				$term = get_queried_object();
				$term_id = $term->term_id;
				
				$use_og = (empty(get_post_meta($post_id, '_siteseo_social_twitter_title', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_desc', true)) && empty(get_post_meta($post_id, '_siteseo_social_twitter_img', true)));
				//twitter:title
				if(!empty(get_term_meta($term_id, '_siteseo_social_twitter_title', true))){
					$site_title = get_term_meta($term_id, '_siteseo_social_twitter_title', true);
				} elseif(!empty($use_og) && !empty(get_term_meta($term_id, '_siteseo_social_fb_title', true))){ 
					$site_title = get_term_meta($term_id, '_siteseo_social_fb_title', true);
				} elseif(!empty(get_term_meta($term_id, '_siteseo_titles_title', true))){
					$site_title = get_term_meta($term_id, '_siteseo_titles_title', true);
				} elseif(!empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['title'])){
					$site_title = $siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['title'];
				} else{
					$site_title = $site_title;
				}
				
				// twitter description
				if(!empty(get_term_meta($term_id, '_siteseo_social_twitter_desc', true))){
					$site_description = get_term_meta($term_id, '_siteseo_social_twitter_desc', true);
				} elseif(!empty($use_og) && !empty(get_term_meta($term_id, '_siteseo_social_fb_desc', true))){ 
					$site_description = get_term_meta($term_id, '_siteseo_social_fb_desc', true);
				} elseif(!empty(get_term_meta($term_id, '_siteseo_titles_desc', true))){
					$site_description = get_term_meta($term_id, '_siteseo_titles_desc', true);
				} elseif(!empty($siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['description'])){
					$site_description = $siteseo->titles_settings['titles_tax_titles'][$taxonomy->name]['description'];
				} else{
					$site_description = wp_strip_all_tags(term_description($term_id));
				}
								
				$twitter_img = !empty(get_term_meta($term_id, '_siteseo_social_twitter_img', true)) ? get_term_meta($term_id, '_siteseo_social_twitter_img', true) : '';
				
				if(empty($twitter_img) && !empty($use_og) && !empty(get_term_meta($term_id, '_siteseo_social_fb_img', true))){
					$twitter_img = get_term_meta($term_id, '_siteseo_social_fb_img', true);
				} else{
					$twitter_img = $twitter_img;
				}
				
				$site_url = urldecode(get_term_link($term_id));
				break;
			}
		}
		
		$site_title = esc_attr(\SiteSEO\TitlesMetas::replace_variables($site_title));
		$site_description = esc_attr(\SiteSEO\TitlesMetas::replace_variables($site_description));
		$x_image_size = 'summary';
		if(!empty($siteseo->social_settings['social_twitter_card_img_size']) && $siteseo->social_settings['social_twitter_card_img_size'] == 'Large'){
			$x_image_size = 'summary_large_image';
		}
		echo '<meta name="twitter:card" content="'.esc_attr($x_image_size).'"/>';
		
		echo '<meta name="twitter:locale" content="'.esc_html(get_locale()).'"/>';
		
		if(!empty($site_title)){
			echo '<meta name="twitter:title"  content="'.esc_html($site_title).'"/>';
		}
		
		if(!empty($site_description)){
			echo '<meta name="twitter:description" content="'.esc_html($site_description).'"/>';
		}
		
		if(!empty($site_url)){
			echo '<meta name="twitter:url" content="'.esc_html($site_url).'"/>';
		}
		
		if(!empty($twitter_user_name)){
			$twitter_user_name = trim($twitter_user_name, '@');
			echo '<meta name="twitter:site" content="@'.esc_html($twitter_user_name).'"/>';
		}
		
		if(!empty($twitter_img)){
			echo '<meta name="twitter:image" content="'.esc_html($twitter_img).'"/>';
		}
	}
}
