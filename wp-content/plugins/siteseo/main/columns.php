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

class Columns{

	static function add_columns($colums){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return $colums;  // toggle disable
		}

		$options = $siteseo->advanced_settings;
		
		if(!empty($options['appearance_title_col'])){
			$colums['seo_title'] = __('Title tag', 'siteseo');
		}
		
		if(!empty($options['appearance_meta_desc_col'])){
			$colums['meta_description'] = __('Meta Desc' , 'siteseo');
		}
		
		if(!empty($options['appearance_redirect_enable_col'])){
			$colums['redirect_enabled'] = __('Redirect?', 'siteseo');
		}
		
		if(!empty($options['appearance_redirect_url_col'])){
			$colums['redirect_url'] = __('Redirect URL', 'siteseo');
		}
		
		if(!empty($options['appearance_canonical'])){
			$colums['canonical_url'] = __('Canonical', 'siteseo');
		}
		
		if(!empty($options['appearance_target_kw_col'])){
			$colums['target_keyword'] = __('Target Kw', 'siteseo');
		}
		
		if(!empty($options['appearance_noindex_col'])){
			$colums['noindex'] = __('noindex?', 'siteseo');
		}
		
		if(!empty($options['appearance_nofollow_col'])){
			$colums['nofollow'] = __('nofollow?', 'siteseo');
		}
		
		if(!empty($options['appearance_words_col'])){
			$colums['word_count'] = __('Words', 'siteseo');
		}
		
		if(!empty($options['appearance_score_col'])){
			$colums['seo_score'] = __('Score', 'siteseo');
		}
		
		return $colums;
	}
	
	static function populate_custom_seo_columns($column, $post_id){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return;  // toggle disable
		}
		
		$options = $siteseo->advanced_settings;

		switch($column){
			
			case 'seo_title':				
				if(!empty($options['appearance_title_col'])){
					$title = get_post_meta($post_id, '_siteseo_titles_title', true);
					echo esc_html(\SiteSEO\TitlesMetas::replace_variables($title));
				}
				break;

			case 'meta_description':
				if(!empty($options['appearance_meta_desc_col'])){
					$desc = get_post_meta($post_id, '_siteseo_titles_desc', true);
					$replaced_desc = \SiteSEO\TitlesMetas::replace_variables($desc);
					echo esc_html($replaced_desc);
				}
				break;

			case 'redirect_enabled':
				if(!empty($options['appearance_meta_desc_col'])){
					$redirect_enabled = get_post_meta($post_id, 'siteseo_redirections_enabled', true);
					echo $redirect_enabled ? esc_html__('Yes', 'siteseo') : esc_html__('No', 'siteseo');
				}
				break;
				
			case 'redirect_url':
				if(!empty($options['appearance_redirect_enable_col'])){
					echo esc_url(get_post_meta($post_id, '_siteseo_redirections_value', true));
				}
				break;

			case 'canonical_url':
				if(!empty($options['appearance_redirect_url_col'])){
					echo esc_url(get_post_meta($post_id, '_siteseo_robots_canonical', true));
				}
				break;

			case 'target_keyword':
				if(!empty($options['appearance_canonical'])){
					$keywords = esc_html(get_post_meta($post_id, '_siteseo_analysis_target_kw', true));
					echo !empty($keywords) ? esc_html($keywords) : '';
				}
				break;
				
			case 'noindex':
				if(!empty($options['appearance_noindex_col'])){
					$noindex = get_post_meta($post_id, '_siteseo_robots_index', true);
					echo $noindex ? esc_html__('Yes', 'siteseo') : esc_html__('No', 'siteseo');
				}
				break;

			case 'nofollow':
				if(!empty($options['appearance_nofollow_col'])){
					$nofollow = get_post_meta($post_id, '_siteseo_robots_follow', true);
					echo $nofollow ? esc_html__('Yes', 'siteseo') : esc_html__('No', 'siteseo');
				}
				break;

			case 'word_count':
				if(!empty($options['appearance_words_col'])){
					$content = get_post_field('post_content', $post_id);
					echo esc_html(str_word_count(wp_strip_all_tags($content)));
				}
				break;

			case 'seo_score':
				if(!empty($options['appearance_score_col'])){
					$score = get_post_meta($post_id, '_siteseo_score', true);
					echo !empty($score) ? esc_html($score) : '';
				}
				break;
		}
		
	}

	static function make_seo_columns_sortable($columns){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return $columns;  // toggle disable
		}
		
		$options = $siteseo->advanced_settings;
		
		if(!empty($options['appearance_title_col'])){
			$columns['seo_title'] = 'seo_title';
		}
		
		if(!empty($options['appearance_meta_desc_col'])){
			$columns['meta_description'] = 'meta_description';
		}
		
		if(!empty($options['appearance_target_kw_col'])){
			$columns['target_keyword'] = 'target_keyword';
		}
		
		if(!empty($options['appearance_words_col'])){
			$columns['word_count'] = 'word_count';
		}

		if(!empty($options['appearance_score_col'])){
			$columns['seo_score'] = 'seo_score';
		}

		return $columns;
		
	}
	
	static function hide_genesis_seo(){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-advanced'])){
			return; // toggle disable
		}
		
		$options = $siteseo->advanced_settings;

		if(!empty($options['appearance_genesis_seo_menu'])){
			remove_theme_support('genesis-seo-settings-menu');
		}
		
		if(!empty($options['appearance_genesis_seo_metaboxe'])){
			remove_action('admin_menu', 'genesis_add_inpost_seo_box');
		}
	}
}