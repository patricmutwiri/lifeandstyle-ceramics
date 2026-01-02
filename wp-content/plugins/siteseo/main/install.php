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

class Install{
	
	static function activate(){
		self::default_settings();
		update_option('siteseo_version', SITESEO_VERSION);
	}

	static function deactivate(){
		flush_rewrite_rules();
	}

	static function uninstall(){
		flush_rewrite_rules();
		delete_option('siteseo_version');
		delete_option('siteseo_toggle');
		delete_option('siteseo_titles_option_name');
		delete_option('siteseo_social_option_name');
		delete_option('siteseo_advanced_option_name');
		delete_option('siteseo_instant_indexing_option_name');
		delete_option('siteseo_xml_sitemap_option_name');
		delete_option('siteseo_google_analytics_option_name');
		delete_option('siteseo_dismiss_intro');
	}
	
	static function action_links($links, $file){
		
		if($file === plugin_basename(SITESEO_FILE)){
				
			$links['siteseo-settings'] = '<a href="'.admin_url('admin.php?page=siteseo').'">'. __('Settings', 'siteseo').'</a>';
			$links['siteseo-wizard'] = '<a href="'.admin_url('?page=siteseo-onboarding').'">'. __('Configuration Wizard', 'siteseo').'</a>';
			$links['siteseo-docs-link'] = '<a href="https://siteseo.io/docs/" target="_blank"">'. __('Docs', 'siteseo').'</a>';
		
		}
		
		return $links;	
	}

	static function default_settings(){
		// We do not need to set defaults if we just upgrading the plugin
		$current_version = get_option('siteseo_version');
		if(!empty($current_version)){
			return;
		}
		
		$titles_metas = get_option('siteseo_titles_option_name', []);
		$social_settings = get_option('siteseo_social_option_name', []);
		$advanced_settings = get_option('siteseo_advanced_option_name', []);
		$sitemap_settings = get_option('siteseo_xml_sitemap_option_name', []);
		
		$toggle_settings = [
			'toggle-titles' => true,
			'toggle-xml-sitemap' => true,
			'toggle-instant-indexing' => true,
			'toggle-advanced' => true,
			'toggle-social' => true,
			'toggle-google-analytics' => true
		];

		// Titles and Metas
		$titles_metas['titles_sep'] = '-';
		$titles_metas['titles_home_site_title'] = !isset($titles_metas['titles_home_site_title']) ? '%%sitetitle%%' : $titles_metas['titles_home_site_title'];
		$titles_metas['titles_home_site_desc'] = !isset($titles_metas['titles_home_site_desc']) ? '%%tagline%%' : $titles_metas['titles_home_site_desc'];
		
		$post_types = siteseo_post_types();
		if(!empty($post_types) && is_array($post_types)){
			$post_types = array_keys($post_types);

			foreach($post_types as $post_type){
				$titles_metas['titles_single_titles'][$post_type]['title'] = !isset($titles_metas['titles_single_titles'][$post_type]['title']) ? '%%post_title%% %%sep%% %%sitetitle%%' : $titles_metas['titles_single_titles'][$post_type]['title'];
				$titles_metas['titles_single_titles'][$post_type]['description'] = !isset($titles_metas['titles_single_titles'][$post_type]['description']) ? '%%post_excerpt%% ' : $titles_metas['titles_single_titles'][$post_type]['description'];
			}
		}
		
		$taxonomies = get_taxonomies(array('public' => true), 'objects');
		if(!empty($taxonomies) && is_array($taxonomies)){
			$taxonomies = array_keys($taxonomies);
			
			foreach($taxonomies as $taxonomy){
				$titles_metas['titles_tax_titles'][$taxonomy]['title'] = !isset($titles_metas['titles_tax_titles'][$taxonomy]['title']) ? '%%_category_title%% %%sep%% %%sitetitle%%' : $titles_metas['titles_tax_titles'][$taxonomy]['title'];
				$titles_metas['titles_tax_titles'][$taxonomy]['description'] = !isset($titles_metas['titles_tax_titles'][$taxonomy]['description']) ? '%%_category_description%%' : $titles_metas['titles_tax_titles'][$taxonomy]['description'];	
			}
		}

		$titles_metas['titles_archives_author_title'] = !isset($titles_metas['titles_archives_author_title']) ? '%%post_author%% %%sep%% %%sitetitle%%' : $titles_metas['titles_archives_author_title'];
		$titles_metas['titles_archives_author_noindex'] = !isset($titles_metas['titles_archives_author_noindex']) ? true : '';
		$titles_metas['titles_archives_date_title'] = !isset($titles_metas['titles_archives_date_title']) ? '%%archive_date%% %%sep%% %%sitetitle%%' : '';
		$titles_metas['titles_archives_date_noindex'] = !isset($titles_metas['titles_archives_date_noindex']) ? true : '';
		$titles_metas['titles_archives_search_title_noindex'] = !isset($titles_metas['titles_archives_search_title_noindex']) ? true : '';
		$titles_metas['titles_nositelinkssearchbox'] = !isset($titles_metas['titles_nositelinkssearchbox']) ? true : '';
		$titles_metas['titles_archives_search_title'] = !isset($titles_metas['titles_archives_search_title']) ? '%%search_keywords%% %%sep%% %%sitetitle%%' : '';
		$titles_metas['titles_archives_404_title'] = !isset($titles_metas['titles_archives_404_title']) ? '404 - Page not found %%sep%% %%sitetitle%%' : $titles_metas['titles_archives_404_title'];

		// Social	
		$social_settings['social_twitter_card'] = true;
		$social_settings['social_facebook_og'] = true;

		// Sitemap
		$sitemap_settings['xml_sitemap_general_enable'] = true;
		$sitemap_settings['xml_sitemap_post_types_list']['post']['include'] = true;
		$sitemap_settings['xml_sitemap_post_types_list']['page']['include'] = true;
		$sitemap_settings['xml_sitemap_taxonomies_list']['category']['include'] = true;
		$sitemap_settings['xml_sitemap_img_enable'] = true; 

		// Advanced
		$advanced_settings['advanced_attachments'] = true;
		$advanced_settings['appearance_universal_metabox'] = true;

		update_option('siteseo_toggle', $toggle_settings);
		update_option('siteseo_titles_option_name', $titles_metas);
		update_option('siteseo_social_option_name', $social_settings);
		update_option('siteseo_xml_sitemap_option_name', $sitemap_settings);
		update_option('siteseo_advanced_option_name', $advanced_settings);

	}

}
