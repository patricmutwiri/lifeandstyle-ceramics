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

use DOMDocument;
use DOMXPath;

class TableofContent{
	
	static function enable_toc(){
        global $siteseo;

        if(empty($siteseo->setting_enabled['toggle-advanced']) || empty($siteseo->advanced_settings['toc_enable'])){
            return;
        }

        add_filter('the_content', '\SiteSEO\TableofContent::add_ids_to_headings');
    }

	static function render_toc(){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-advanced']) || empty($siteseo->advanced_settings['toc_enable'])){
			return;
		}

		static $siteseo_toc_run = false;

		if(!empty($siteseo_toc_run)){
			return '<p style="padding: 1rem; background-color:#fff3cd; color:#664d03; border:1px solid #ffe69c; border-radius: 0.375rem">'.esc_html__('Table of content shortcode can be used only once on a page, this page is using the shortcode more than once. Please remove the extra table of content shortcodes for this warning to go away.', 'siteseo').'</p>';
		}

		$options = get_option('siteseo_advanced_option_name');
		//$options = $siteseo->advanced_settings;
		
		$content = get_the_content();

		if(empty($content)){
			return;
		}

		$heading_type = (!empty($options['toc_heading_type']) ? $options['toc_heading_type'] : 'ul');

		$dom = new DOMDocument();
		$internalErrors = libxml_use_internal_errors(true);
		$dom->preserveWhiteSpace = false;

		$html = '';

		if($dom->loadHTML('<?xml encoding="utf-8" ?>' . $content)){
			$xpath = new DOMXPath($dom);

			$heading_list = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
			
			$headings_to_scan = [];
			foreach($heading_list as $h){
				if(!empty($options['toc_excluded_headings']) && is_array($options['toc_excluded_headings']) && in_array($h, $options['toc_excluded_headings'])){
					continue;
				}

				$headings_to_scan[] = '//'.$h;
			}

			if(empty($headings_to_scan)){
				return;
			}

			$headings_to_scan = implode('|', $headings_to_scan);
			
			// The imploded string will look like this //h1|//h2|//h3|//h4|//h5|//h6 
			$headings = $xpath->query($headings_to_scan);

			if(empty($headings)){
				return;
			}

			$last_h = 0;
			$open_ul = 0;
			
			$html .= '<style>.siteseo-toc-wrapper { padding: 20px; border: 1px solid #a2a9b1; background-color: #f8f9fa;}.siteseo-toc-wrapper p { display:flex; align-items:center; gap: 10px; font-size: 1.5rem; font-weight: 500; margin: 0 0 10px 0;}.siteseo-toc-wrapper > '.esc_html($heading_type).' { margin: 0; padding: 0;}.siteseo-toc-wrapper p>label { font-weight: 400; font-size: 0.9rem;}#siteseo-toc-toggle~span { cursor: pointer;}#siteseo-toc-toggle:checked~.siteseo-toc-hide,p:has(#siteseo-toc-toggle:checked) ~ '.esc_html($heading_type).' { display: none;}#siteseo-toc-toggle:not(:checked) ~ .siteseo-toc-hide{ display: inline;}#siteseo-toc-toggle:not(:checked) ~ .siteseo-toc-show { display: none;}</style>
			<div class="siteseo-toc-wrapper">
			<p>'.(!empty($options['toc_label']) ? esc_html($options['toc_label']) : esc_html__('Table of Content', 'siteseo')).' <label for="siteseo-toc-toggle">
			<input type="checkbox" style="display:none;" id="siteseo-toc-toggle" name="siteseo-toc-toggle"/>
			[<span class="siteseo-toc-hide">hide</span><span class="siteseo-toc-show">show</span>]</label></p>
			<'.esc_html($heading_type).'>';

			foreach($headings as $heading){
				$title = trim(wp_strip_all_tags($heading->nodeValue));
				$id = $heading->getAttribute('id');
				$current_h = (int) substr($heading->tagName, 1);
				
				if(empty($id)){
					$id = '#'.self::title_to_id($title);
				}else{
					$id = '#'.$id;
				}

				if($current_h > $last_h){				
					$html .= '<'.esc_html($heading_type).'>';
					$open_ul++;
				}else{
					while($current_h <= $open_ul){
						$html .= '</'.esc_html($heading_type).'>';
						$open_ul--;
					}
				}

				$html .= '<li><a href="'.esc_attr($id).'">'.esc_html($title).'</a></li>';
				$last_h = $current_h;
			}

			$html .= '</'.esc_html($heading_type).'></div>';
			
			$siteseo_toc_run = true;
		}

		return $html;
	}

	// Converts heading text content to ID to be used as link
	static function title_to_id($title){
		
		$id = trim(wp_strip_all_tags($title));
		$id = remove_accents($title);
		$id = sanitize_title_with_dashes($id);
		$id = urlencode($id);

		return $id;
	}

	static function add_ids_to_headings($content){

		if(empty($content)){
			return $content;
		}

		// If the page does not have the shortcode then we don't need to update the id's in the heading.
		if(!has_shortcode($content, 'siteseo_toc')){
			return $content;
		}
		
		$dom = new DOMDocument();
		$internalErrors = libxml_use_internal_errors(true);
		$dom->preserveWhiteSpace = false;

		$html = '';

		if($dom->loadHTML('<?xml encoding="utf-8" ?>' . $content)){
			$xpath = new DOMXPath($dom);

			$headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');

			if(empty($headings)){
				return;
			}

			foreach($headings as $heading){
				$title = trim(wp_strip_all_tags($heading->nodeValue));
				$id = $heading->getAttribute('id');
				
				if(!empty($id)){
					continue;
				}
				
				if(empty($title)){
					continue;
				}
				
				$id = self::title_to_id($title);

				$heading->setAttribute('id', $id);
			}

			$content = $dom->saveHTML($dom->documentElement);
		}

		return $content;

	}

}