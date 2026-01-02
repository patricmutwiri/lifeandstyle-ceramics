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

class Sitemap{

    static function menu(){
		global $siteseo;

		$sitemap_toggle = isset($siteseo->setting_enabled['toggle-xml-sitemap']) ? $siteseo->setting_enabled['toggle-xml-sitemap'] : '';
		$nonce = wp_create_nonce('siteseo_toggle_nonce');

		$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tab_sitemap_general'; // Default tab

		$titles_meta_subtabs = [
			'tab_sitemap_general' => esc_html__('Home', 'siteseo'),
			'tab_sitemap_post_types' => esc_html__('Post types', 'siteseo'),
			'tab_sitemap_taxonomy ' => esc_html__('Taxonomy', 'siteseo'),
			'tab_sitmap_html' => esc_html__('HTML Sitemap', 'siteseo')
		];
		
		echo '<div id="siteseo-root">';
		Util::admin_header();

		echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">';

		wp_nonce_field('siteseo_sitemap_settings');

		Util::render_toggle('Sitemaps - SiteSEO', 'sitemap_toggle', $sitemap_toggle, $nonce);

		echo '<div id="siteseo-tabs" class="wrap">
		<div class="siteseo-nav-tab-wrapper">';

		foreach($titles_meta_subtabs as $tab_key => $tab_caption){
			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo '<a id="' . esc_attr($tab_key) . '-tab" class="siteseo-nav-tab' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab_key) . '">' . esc_html($tab_caption) . '</a>';
		}

		echo '</div>
		<div class="tab-content-wrapper">
		<div class="siteseo-tab' .($current_tab == 'tab_sitemap_general' ? ' active' : '').'" id="tab_sitemap_general" style="display: none;">';
		self::general_sitemaps();
		echo '</div>  
		<div class="siteseo-tab' .($current_tab == 'tab_sitemap_post_types' ? ' active' : '').'" id="tab_sitemap_post_types" style="display: none;">';
		self::post_types_sitemaps();
		echo '</div>
		<div class="siteseo-tab' .($current_tab == 'tab_sitemap_taxonomy' ? ' active' : '').'" id="tab_sitemap_taxonomy" style="display: none;">';
		self::taxonomy_sitemap();
		echo '</div>  
		<div class="siteseo-tab' .($current_tab == 'tab_sitmap_html' ? ' active' : '').'" id="tab_sitmap_html" style="display: none;">';
		self::html_sitemap();
		echo '</div>
		</div>';

		Util::submit_btn();
		echo '</form></div>';
	}

    static function general_sitemaps(){
        global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		//$options = $siteseo->sitemap_settings;
		$options = get_option('siteseo_xml_sitemap_option_name', []);

		$xml_sitemap = !empty($options['xml_sitemap_general_enable']) ? $options['xml_sitemap_general_enable'] : '';
		$img_sitemap = !empty($options['xml_sitemap_img_enable']) ? $options['xml_sitemap_img_enable'] : '';
		$author_sitemap = !empty($options['xml_sitemap_author_enable']) ? $options['xml_sitemap_author_enable'] : '';
		$html_sitemap = !empty($options['xml_sitemap_html_enable']) ? $options['xml_sitemap_html_enable'] : '';

		echo '<h3 class="siteseo-tabs">'.esc_html__('General','siteseo').'</h3>
		<p>'.esc_html__('Sitemaps are pages which help search engine, know your site better and makes it easier for them to index the pages.','siteseo').'</p>
		<p>'.esc_html__('Not having a sitemap does not mean search engines won\'t be able to crawl your website, but sitemaps make it easier for them to discover all the URLs which are needed to be indexed.','siteseo').'</p>
		 <div class="siteseo-styles pre"><pre><span class="dashicons dashicons-external"></span><a href="'.esc_url(get_option('home')).'/sitemaps.xml" target="_blank">' . esc_url(get_option('home')) . '/sitemaps.xml</a></pre></div>
	        <div class="siteseo-notice">
			<span id="siteseo-dash-icon" class="dashicons dashicons-info"></span>
	        	<p>'.
			/* translators: placeholders are just <strong> tag */ 
			wp_kses_post(sprintf(__('To view your sitemap, %1$s enable permalinks %2$s (other than the default one) and save the settings to flush them.', 'siteseo'), '<strong>', '</strong>')).'</p>
	        </div>

	        <table class="form-table">
	            <tbody>
	                <tr>
	                    <th scope="row">'.esc_html__('Enable XML Sitemap','siteseo').'</th>
	                    <td>
	                     <label><input id="siteseo_enable_sitemap" name="siteseo_options[enable_xml_sitemap]" type="checkbox" '.(!empty($xml_sitemap) ? 'checked' : '').' value="1"/>'. esc_html__('Enable XML Sitemap', 'siteseo').'</label>
	                    </td>
	                </tr>

	                <tr>
	                    <th scope="row">'.esc_html__('Enable Image Sitemap','siteseo').'</th>
	                    <td>
	                        <label><input id="siteseo_image_sitemap" name="siteseo_options[enable_img_sitemap]" type="checkbox" '.(!empty($img_sitemap) ? 'checked' : '').' value="1"/>'. esc_html__('Enable Image Sitemap for standard images, image galleries, featured images, and WooCommerce / Kkart product images.)', 'siteseo').'</label>
	                        <p class="description">'.esc_html__('Images in XML sitemaps are only visible from the source code.', 'siteseo').'</p>
	                    </td>
	                </tr>

	                <tr>
	                    <th scope="row">'.esc_html__('Enable Author Sitemap','siteseo').'</th>
	                    <td>
	                        <label><input id="siteseo_author_sitemap" name="siteseo_options[enable_author_sitemap]" type="checkbox" '.(!empty($author_sitemap) ? 'checked' : '').' value="1"/>' . esc_html__('Enable Author Sitemap', 'siteseo').'</label>
	                        <p class="description">'.esc_html__('Ensure that you enable the author archive from SEO, under the Titles & Metas section, in the Archives tab.','siteseo').'</p>
	                    </td>
	                </tr> 

	                  <tr>
	                    <th scope="row">'.esc_html__('Enable HTML Sitemap','siteseo').'</th>
	                    <td>
	                        <label><input id="siteseo_html_sitemap" name="siteseo_options[enable_html_sitemap]" type="checkbox" '.(!empty($html_sitemap) ? 'checked' : ''). ' value="1"/>' . esc_html__('Enable HTML Sitemap', 'siteseo').'</label>
	                    </td>
	                </tr> 
				</tbody>
	        </table>
		<input type="hidden" name="siteseo_options[general_sitemaps] value="1"/>';

    }

	static function post_types_sitemaps(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		//$options = $siteseo->sitemap_settings;
		$options = get_option('siteseo_xml_sitemap_option_name', []);
		
		$option_sitemap_posts = !empty($options['xml_sitemap_post_types_list']['post']['include']) ? $options['xml_sitemap_post_types_list']['post']['include'] : '';
		$option_sitemap_pages = !empty($options['xml_sitemap_post_types_list']['page']['include']) ? $options['xml_sitemap_post_types_list']['page']['include'] : '';
		$option_sitemap_media = !empty($options['xml_sitemap_post_types_list']['media']['include']) ? $options['xml_sitemap_post_types_list']['media']['include'] : '';

		$post_types = siteseo_post_types();

		echo '<h3 class="siteseo-tabs">'.esc_html__('Post Types', 'siteseo').'</h3>
			<p>'.esc_html__('Select Post Types to Include or Exclude', 'siteseo').'</p>
				<table class="form-table">
					<tbody>';

						foreach($post_types as $post_type){
							$post_type_name = $post_type->name;
							$post_type_label = $post_type->labels->singular_name;
							$option_sitemap_custom = !empty($options['xml_sitemap_post_types_list'][$post_type_name]['include']) ? 'checked="yes"' : '';

							echo '<tr>
									<th></th>
									<td>
										<label for="sitemap_post_types_'.esc_attr($post_type_name).'">
											<h4>'.esc_html($post_type_label).' <em>(['.esc_html($post_type_name).'])</em></h4>
											<input id="sitemap_post_types_'.esc_attr($post_type_name).'" name="siteseo_options[xml_sitemap_post_types_list]['.esc_attr($post_type_name).'][include]" type="checkbox" '.esc_attr($option_sitemap_custom).' value="1"/>
											'.esc_html__('Include', 'siteseo').'
										</label>
									</td>
								</tr>';
						}

					echo '</tbody>
				</table>
				<input type="hidden" name= siteseo_options[post_types_tab] value="1"/>';
	}

    static function taxonomy_sitemap(){
        global $siteseo;
		
        if(!empty($_POST['submit'])){
            self::save_settings();
        }
		
		//$options = $siteseo->sitemap_settings;
		$get_taxonomies = get_taxonomies(['public' => true, 'show_ui' => true], 'objects');
		$check_taxonomies = apply_filters('siteseo_sitemaps_tax', $get_taxonomies);
	
		$options = get_option('siteseo_xml_sitemap_option_name');
		$option_category = isset($options['xml_sitemap_taxonomies_list']['category']['include']) ? $options['xml_sitemap_taxonomies_list']['category']['include'] : '';
		$option_post_tags = isset($options['xml_sitemap_taxonomies_list']['post_tag']['include']) ? $options['xml_sitemap_taxonomies_list']['post_tag']['include'] : '';
		
		$get_taxonomies = get_taxonomies();
		$check_taxomies = apply_filters('siteseo_sitemaps_tax', $get_taxonomies);
		
		$excluded_taxonomies = ['post_format', 'category', 'post_tag'];

        echo '<h3 class="siteseo-tabs">'.esc_html__('Taxonomies', 'siteseo').'</h3>
			<p>'.esc_html__('Select Taxonomies to Include or Exclude', 'siteseo').'</p>
            <table class="form-table">
                <tr scope="row">
                    <th>'.esc_html__('Select to INCLUDE Taxonomies', 'siteseo').'</th>
					
					<td><br/><br/>
						<label for="sitemap_post_types_pages">
							<h4>'.esc_html__('Categories ', 'siteseo').' <em>[categories]</em></h4>
							<input id="sitemap_post_types_pages" name="siteseo_options[xml_sitemap_taxonomies_list][category][include]" type="checkbox" '.(!empty($option_category) ? 'checked' : 'value="1"').'/>
							'.esc_html__('Include', 'siteseo').'
						</label>	
					</td>
                </tr>
            
				<tr>
					<th></th>
					<td>
						<label for="sitemap_post_types_pages">
							<h4>'.esc_html__('Tags', 'siteseo').' <em>[post_tag]</em></h4>
							<input id="sitemap_post_types_pages" name="siteseo_options[xml_sitemap_taxonomies_list][post_tag][include]" type="checkbox" '.(!empty($option_post_tags) ? 'checked' : 'value="1"').'/>
							'.esc_html__('Include', 'siteseo').'
						</label>
					</td>
				</tr>';
				
				foreach($check_taxonomies as $taxonomy_name => $taxonomy_obj){

					if(in_array($taxonomy_name, $excluded_taxonomies)){
						continue;
					}

					//check selected
					$is_included = !empty($options['xml_sitemap_taxonomies_list'][$taxonomy_name]['include']);

					// Generate a row for the taxonomy
					echo '<tr scope="row">
							<th></th>
							<td>
								<label for="sitemap_taxonomy_'.esc_attr($taxonomy_name).'">
									<h4>'.esc_html($taxonomy_obj->labels->name).' <em>[' . esc_html($taxonomy_name).']</em></h4>
									<input id="sitemap_taxonomy_' . esc_attr($taxonomy_name) . '" 
										   name="siteseo_options[xml_sitemap_taxonomies_list][' . esc_attr($taxonomy_name).'][include]" 
										   type="checkbox" '.($is_included ? 'checked' : '').' value="1" />
									' . esc_html__('Include', 'siteseo') . '
								</label>
							</td>
						  </tr>';
				}

            echo '</table><input type="hidden" name="siteseo_options[taxonomy_sitemap_tabs]" value="1">';

    }

    static function html_sitemap(){

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

	//$options = $siteseo->$sitemap_settings;
	$options = get_option('siteseo_xml_sitemap_option_name', []);
		
	$include_pages = !empty($options['xml_sitemap_html_mapping']) ? $options['xml_sitemap_html_mapping'] : '';
	$exclude_page = !empty($options['xml_sitemap_html_exclude']) ? $options['xml_sitemap_html_exclude'] : '';
	$order = !empty($options['xml_sitemap_html_order']) ? $options['xml_sitemap_html_order'] : '';
	$order_by = !empty($options['xml_sitemap_html_orderby']) ? $options['xml_sitemap_html_orderby'] : '';
	$disable_date = !empty($options['xml_sitemap_html_date']) ? $options['xml_sitemap_html_date'] : '';
	$remove_archive = !empty($options['xml_sitemap_html_archive_links']) ? $options['xml_sitemap_html_archive_links'] : '';
		

        echo '<h3 class="siteseo-tabs">'.esc_html__('HTML Sitemap', 'siteseo').'</h3>
        <p>'.esc_html__('Generate an HTML sitemap for your visitors to improve your SEO.','siteseo').'</p>
        <p>'.esc_html__('Restricted to 1,000 posts per post type. You can change the order and sorting settings below.','siteseo').'</p>

        <div class="siteseo-notice"><span class="dashicons dashicons-info"></span>
        <div>
            <h3>'.esc_html__('How to make use of the HTML Sitemap?', 'siteseo').'</h3>
            <h4>'.esc_html__('Block Editor', 'siteseo').'</h4>
		<p>'.
		/* translators: placeholders are just <strong> tag */ 
		wp_kses_post(sprintf(__('Insert the HTML sitemap block via the %1$s Block Editor %2$s.', 'siteseo'), '<strong>', '</strong>')).'</p>
            <h4>'.esc_html__('Shortcode', 'siteseo').'</h4>
            <p>'.esc_html__('You can also insert this shortcode into your content (post, page, custom post type, etc.):', 'siteseo').'</p>
            <div class="siteseo-styles pre"><pre>'.esc_attr('[siteseo_html_sitemap]').'</div></pre>
            <p>'.esc_html__('To include specific custom post types, use the CPT attribute:', 'siteseo') .'</p>
            <div class="siteseo-styles pre"><pre>'.esc_attr('[siteseo_html_sitemap cpt="post,product"]').'</div></pre>
            <h4>'.esc_html__('Other', 'siteseo').'</h4>
            <p>'.esc_html__('Display the sitemap dynamically by entering an ID in the first field below.', 'siteseo').'</p>
			</div>
		</div>
        
        <table class="form-table">
             <tr scope="row">
                <th>'.esc_html__('Post, Page, or Custom Post Type IDs to display:','siteseo').'</th>
                <td>
                    <input type="text" value="'.esc_html($include_pages).'" name="siteseo_options[page_numbers]" placeholder="'.esc_html__('eg:2, 28, 68','siteseo').'">
                </td>
            </tr>

            <tr scope="row">
                <th>'.esc_html__('Exclude Posts, Pages, Custom Post Types or Terms IDs:','siteseo').'</th>
                <td>
                    <input type="text" value="'.esc_html($exclude_page).'" name="siteseo_options[exclude_page]" placeholder="'.esc_html__('eg: 13 ,8 ,28','siteseo').'">
                </td>
            </tr>

            <tr scope="row">
                <th>'.esc_html__('Order:','siteseo').'</th>
                <td>
                    <select name="siteseo_options[order]">
                        <option value="DESC" '.selected($order, 'DESC', false).'>'.esc_html__('DESC (descending order from highest to lowest values (3, 2, 1; c, b, a))','siteseo').'</option>
                         <option value="ASC" '.selected($order, 'ASC', false).'>'.esc_html__('ASC (ascending order from lowest to highest values (1, 2, 3; a, b, c))','siteseo').'</option>
                    </select>
                </td>
            </tr>

            <tr scope="row">
                <th>'.esc_html__('Order By:','siteseo').'</th>
                <td>
                    <select name="siteseo_options[order_by]">
                        <option value="date" '.selected($order_by, 'date', false).'>'.esc_html__('Deafult (date)','siteseo').'</option>
                        <option value="post_title" '.selected($order_by, 'post_title', false).'>'.esc_html__('Post Title','siteseo').'</option>
                        <option value="modified_date" '.selected($order_by, 'modified_date', false).'>'.esc_html__('Modified date','siteseo').'</option>
                        <option value="post_id" '.selected($order_by, 'post_id', false).'>'.esc_html__('POST ID','siteseo').'</option>
                        <option value="menu_order" '.selected($order_by, 'menu_order', false).'>'.esc_html__('Menu Order','siteseo').'</option>
                    </select>
                </td>
            </tr>
            <tr scope="row">
                <th>'.esc_html__('Disable Date:','siteseo').'</th>
                <td>
                    <label for="sitemap_html_date">
                        <input id="sitemap_html_date" name="siteseo_options[disable_date]" type="checkbox" '.(!empty($disable_date) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Disable the date display after each post, page, or post type?', 'siteseo'). 
                    '</label>
                </td>
            </tr>

            <tr scope="row">
                <th>'.esc_html__('Remove Archive Links:','siteseo').'</th>
                <td>
                    <label for="sitemap_remove_link">
                        <input id="sitemap_remove_link" name="siteseo_options[remove_links]" type="checkbox" '.(!empty($remove_archive) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Remove links from archive pages (e.g., Products).', 'siteseo'). 
                    '</label>
                </td>
            </tr>
        </table><input type="hidden" name="siteseo_options[html_sitemap]" value="1"/>';
    }

    static function save_settings(){
		global $siteseo;

		check_admin_referer('siteseo_sitemap_settings');

		if(!siteseo_user_can('manage_sitemap') || !is_admin()){
			return;
		}

		$options = [];

		if(empty($_POST['siteseo_options'])){
			return;
		}
		
		if(isset($_POST['siteseo_options']['general_sitemaps'])){
			$options['xml_sitemap_general_enable'] = isset($_POST['siteseo_options']['enable_xml_sitemap']);
			$options['xml_sitemap_img_enable'] = isset($_POST['siteseo_options']['enable_img_sitemap']);
			$options['xml_sitemap_author_enable'] = isset($_POST['siteseo_options']['enable_author_sitemap']);
			$options['xml_sitemap_html_enable'] = isset($_POST['siteseo_options']['enable_html_sitemap']);

			flush_rewrite_rules();
		}

		if(isset($_POST['siteseo_options']['html_sitemap'])){
			
			$options['xml_sitemap_html_mapping'] = isset($_POST['siteseo_options']['page_numbers']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['page_numbers'])) : '';
			$options['xml_sitemap_html_exclude'] = isset($_POST['siteseo_options']['exclude_page']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['exclude_page'])) : '';
			$options['xml_sitemap_html_order'] = isset($_POST['siteseo_options']['order'])? sanitize_text_field(wp_unslash($_POST['siteseo_options']['order'])) : '';
			$options['xml_sitemap_html_orderby'] = isset($_POST['siteseo_options']['order_by']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['order_by'])) : '';
			$options['xml_sitemap_html_date'] = isset($_POST['siteseo_options']['disable_date']);
			$options['xml_sitemap_html_archive_links'] = isset($_POST['siteseo_options']['remove_links']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['remove_links'])) : '';
		}

		// posts 
		if(isset($_POST['siteseo_options']['post_types_tab'])){
			if(isset($_POST['siteseo_options']['xml_sitemap_post_types_list'])){
				$xml_post_types = map_deep(wp_unslash($_POST['siteseo_options']['xml_sitemap_post_types_list']), 'sanitize_text_field');
				foreach($xml_post_types as $posttypes_key => $posttypes_value) {
					if(isset($posttypes_value['include'])) {
						$options['xml_sitemap_post_types_list'][$posttypes_key]['include'] = $posttypes_value['include'];
					}
				}
			}
		}

		// Taxonomies
		if(isset($_POST['siteseo_options']['taxonomy_sitemap_tabs'])){
			if(isset($_POST['siteseo_options']['xml_sitemap_taxonomies_list'])){
				$xml_tax_list = map_deep(wp_unslash($_POST['siteseo_options']['xml_sitemap_taxonomies_list']), 'sanitize_text_field');
				foreach($xml_tax_list as $taxonomy_key => $taxonomy_value){
						if(isset($taxonomy_value['include'])){
							$options['xml_sitemap_taxonomies_list'][$taxonomy_key]['include'] = $taxonomy_value['include'];
						}
				}
			}
		}

		update_option('siteseo_xml_sitemap_option_name',$options);
    }
}
