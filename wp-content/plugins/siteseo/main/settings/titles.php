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

class Titles{

	static function menu(){
		global $siteseo;

		$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tab_siteseo_home'; // Default tab

		$titles_meta_subtabs = [
			'tab_siteseo_home' => esc_html__('Home', 'siteseo'),
			'tab_siteseo_post_types' => esc_html__('Post types', 'siteseo'),
			'tab_siteseo_archives' => esc_html__('Archives', 'siteseo'),
			'tab_siteseo_taxonomies' => esc_html__('Taxonomies', 'siteseo'),
			'tab_siteseo_advanced' => esc_html__('Advanced','siteseo')
		];

		echo '<div id="siteseo-root">';
		Util::admin_header();

		echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">';

		wp_nonce_field('siteseo_title_settings');

		$titles_meta_toggle = isset($siteseo->setting_enabled['toggle-titles']) ? $siteseo->setting_enabled['toggle-titles'] : '';
		$nonce = wp_create_nonce('siteseo_toggle_nonce');

		Util::render_toggle('Titles & Metas - SiteSEO', 'titles_meta_toggle', $titles_meta_toggle, $nonce);

		echo '<div id="siteseo-tabs" class="wrap">
		<div class="siteseo-nav-tab-wrapper">';
		
		foreach($titles_meta_subtabs as $tab_key => $tab_caption) {
			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo '<a id="' . esc_attr($tab_key) . '-tab" class="siteseo-nav-tab' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab_key) . '">' . esc_html($tab_caption) . '</a>';
		}

		echo '</div>     
		<div class="tab-content-wrapper">
		<div class="siteseo-tab '.($current_tab == 'tab_siteseo_home' ? ' active' : '').'" id="tab_siteseo_home" style="display: none;">';
		self::home();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_siteseo_post_types' ? ' active' : '').'" id="tab_siteseo_post_types" style="display: none;">';
		self::post_types();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_siteseo_archives' ? ' active' : '').'" id="tab_siteseo_archives" style="display: none;">';
		self::archives();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_siteseo_taxonomies' ? ' active' : '').'" id="tab_siteseo_taxonomies" style="display: none;" style="display: none;">';
		self::taxonomies();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_siteseo_advanced' ? ' active' : '').'" id="tab_siteseo_advanced">';
		self::advanced(); 
		echo '</div>
		</div>';
		Util::submit_btn();
		echo '</form></div>';

	}

    static function home(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		$options = get_option('siteseo_titles_option_name');
		//$options = $siteseo->titles_settings;

		$option_separator = !empty($options['titles_sep']) ? $options['titles_sep'] : '';
		$option_site_title = !empty($options['titles_home_site_title']) ? $options['titles_home_site_title'] : '';
		$option_site_title_alt = !empty($options['titles_home_site_title_alt']) ? $options['titles_home_site_title_alt'] : '';
		$option_site_desc = !empty($options['titles_home_site_desc']) ? $options['titles_home_site_desc'] : '';

		if(get_option('show_on_front') === 'page'){
			$front_page_id = get_option('page_on_front');
			$edit_link = get_edit_post_link($front_page_id, '');
			
			echo'<div class="siteseo_wrap_label">
				<div class="siteseo-notice is-warning">
					<span id="dashicons-warning" class="dashicons dashicons-info"></span>&nbsp;
					<p>'. // translators: %s is the platform name
					wp_kses_post(sprintf(__('A static page is set as your site front page <strong>(%s Dashboard > Settings > Reading)</strong>. To add an SEO title, description, and meta tags for the homepage, please click here -', 'siteseo'), !defined('SITEPAD') ? 'WP' : 'Sitepad')) . '</p>
					<div>
						<a href="'.esc_url($edit_link).'" target="_blank">'.esc_html__('Edit Home Page', 'siteseo').'</a>
					</div>
				</div>
			</div>';
			return;
		}

		echo '<h3 class="siteseo-tabs">'.esc_html__('HOME','siteseo').'</h3>
		<div class="siteseo-notice">
			<span id="siteseo-dash-icon" class="dashicons dashicons-info"></span>&nbsp;
			<p>'.esc_html__('Search engines use the title and meta description to create a snippet of your site for the search results page.', 'siteseo').'</p>
		</div>

		<p>'.esc_html__('Personalize the title and meta description for your homepage.','siteseo').'</p>

		<span class="dashicons dashicons-external"></span>
		<a href="'.esc_attr('https://siteseo.io/docs/meta/google-uses-the-wrong-meta-title-meta-description-in-search-results/').'" target="_blank">'.esc_html__('Incorrect meta title or description appearing in search results?', 'siteseo').'</a>

		<table class="form-table">
			<tbody>
                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Separator','siteseo').'</th>
                    <td>
                        <input type="text" name="siteseo_options[separator]" placeholder="'.esc_attr__('Specify your separator, e.g:-','siteseo').'" value="'.esc_attr($option_separator).'">
                        <p class="description">'.esc_attr__('Include this separator using %%sep%% in your title and meta description.','siteseo').'</p>
                    </td>
                </tr>

                <td colspan="2"><span class="dashed-line"></span></td>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Site title','siteseo').'</th>
                    <td>
                        <input type="text" name="siteseo_options[site_title]" value="'.esc_attr($option_site_title).'" placeholder="'.esc_html__('My fantastic site','siteseo').'">
                        <div class="wrap-tags">
                            <button class="tag-title-btn" id="btn-site-title" data-tag="%%sitetitle%%"><span id="icon" class="dashicons dashicons-insert"></span>'.
							esc_html__('SITE TITLE','siteseo').'</button>
                            <button class="tag-title-btn" id="btn-separator" data-tag="%%sep%%"><span id="icon" class="dashicons dashicons-insert"></span>'.
							esc_html__('SEPARATOR','siteseo').'</button>
                            <button class="tag-title-btn" id="btn-tagline" data-tag="%%tagline%%"><span id="icon" class="dashicons dashicons-insert"></span>'.
							esc_html__('TAGLINE','siteseo').'</button>';
							siteseo_suggestion_button();
                        echo '</div>
                    </td>
                </tr>

                <td colspan="2"><span class="dashed-line"></span></td>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Alternative site title','siteseo').'</th>
                    <td>
                        <input type="text" value="'.esc_attr($option_site_title_alt).'"  name="siteseo_options[alt_site_title]" placeholder="'.esc_html__('Alternative website title','siteseo').'">
                        <p class="description">'.esc_html__('The alternative name of the website (e.g., a commonly recognized acronym or shorter name, if applicable). Ensure the name meets the criteria.', 'siteseo').'</p>
                    </td>
                </tr>

                <td colspan="2"><span class="dashed-line"></span></td>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Meta description','siteseo').'</th>
                    <td>
                        <textarea type="text" name="siteseo_options[media_desc]" placeholder="'.esc_html__('This is an awesome website about galactic creatures.','siteseo').'">'.esc_html($option_site_desc).'</textarea>
                        <div class="wrap-tags">
                            <button class="tag-title-btn" id="btn-tagline-meta" data-tag="%%tagline%%"><span id="icon" class="dashicons dashicons-insert"></span>'.
							esc_html__('TAGLINE','siteseo').'</button>';
                            siteseo_suggestion_button();  
                       echo '</div>
                    </td>
                </tr>
            </tbody>
        </table>
		<input type="hidden" name="siteseo_options[home_tab]" value="1"/>';
    }

    static function advanced(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

        //$options = $siteseo->titles_settings;
		$options = get_option('siteseo_titles_option_name');

		$option_noindex = !empty($options['titles_noindex']) ? $options['titles_noindex'] : '';
		$option_nofollow = !empty($options['titles_nofollow']) ? $options['titles_nofollow'] : '';
		$option_noimage = !empty($options['titles_noimageindex']) ? $options['titles_noimageindex'] : '';
		$option_noarchive = !empty($options['titles_noarchive']) ? $options['titles_noarchive'] : '';
		$option_nosnippet = !empty($options['titles_nosnippet']) ? $options['titles_nosnippet'] : '';
		$option_nositelinkssearchbox = !empty($options['titles_nositelinkssearchbox']) ? $options['titles_nositelinkssearchbox'] : '';
		$option_page_rel = !empty($options['titles_paged_rel']) ? $options['titles_paged_rel'] : '';
		$option_paged_noindex = !empty($options['titles_paged_noindex']) ? $options['titles_paged_noindex'] : '';
		$option_attachments_noindex = !empty($options['titles_attachments_noindex']) ? $options['titles_attachments_noindex'] : '';

		echo '<h3 class="siteseo-tabs">'.esc_html__('Advanced','siteseo').'</h3>
		<p>'.esc_html__('Customize your metas for all pages','siteseo').'</p>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('noindex','siteseo').'</th>
					<td>
						<label>
						<input name="siteseo_options[noindex]" type="checkbox" '.(!empty($option_noindex) ? 'checked="yes"' : '') . ' value="1"/>' . esc_html__('noindex', 'siteseo') . 
						'</label>
						<p class="description">'.esc_attr__('Do not show all pages of the site in Google search results and avoid displaying "Cached" links in search results.','siteseo').'</p>
						'.wp_kses_post('<p class="description">Check also the<strong>"Search engine visibility"</strong> setting from the <a href="%s">WordPress Reading page</a></p>').'
					</td>
				</tr>

				<td colspan="2"><span class="dashed-line"></span></td>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('nofollow','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[no_follow]" type="checkbox"'.(!empty($option_nofollow) ? 'checked="yes"' : '') . ' value="1"/>'. esc_html__('nofollow', 'siteseo'). 
						'</label>
						<p class="description">'.esc_html__('Do not follow links on all pages.','siteseo').'</p>
					</td>
				<tr>

				<td colspan="2"><span class="dashed-line"></span></td>
					
					<tr>
						<th scope="row" style="user-select:auto;">'.esc_html__('noimageindex','siteseo').'</th>
						<td>
							<label>
								<input name="siteseo_options[no_image]" type="checkbox"'.(!empty($option_noimage) ? 'checked="yes"' : '').' value="1"/>'. esc_html__('noimageindex', 'siteseo'). 
							'</label>
							<p class="description">'.esc_html__('Do not follow links on any pages.','siteseo').'</p>
						</td>
					<tr>

				<td colspan="2"><span class="dashed-line"></span></td>

					<tr>
						<th scope="row" style="user-select:auto;">'.esc_html__('noarchive','siteseo').'</th>
						<td>
							<label>
								<input name="siteseo_options[no_archive]" type="checkbox"'.(!empty($option_noarchive) ? 'checked="yes"' : '').' value="1"/>' . esc_html__('noarchive', 'siteseo'). 
							'</label>
							<p class="description">'.esc_html__('Do not show a "Cached" link in Google search results.','siteseo').'</p>
						</td>
					</tr>

				<td colspan="2"><span class="dashed-line"></span></td>

						<tr>
							<th scope="row" style="user-select:auto;">'.esc_html__('nosnippet','siteseo').'</th>
							<td>
								<label>
									<input name="siteseo_options[no_snippet]" type="checkbox"'.(!empty($option_nosnippet) ? 'checked="yes"' : '').' value="1"/>' . esc_html__('nosnippet', 'siteseo'). 
								'</label>
								<p class="description">'.esc_html__('Do not show a description in the Google search results for any pages.','siteseo').'</p>
							</td>
						</tr>

				<td colspan="2"><span class="dashed-line"></span></td>

						<tr>
							<th scope="row" style="user-select:auto;">'.esc_html__('nositelinkssearchbox','siteseo').'</th>
							<td>
								<label>
									<input name="siteseo_options[no_site_links_searchbox]" type="checkbox"'.(!empty($option_nositelinkssearchbox) ? 'checked="yes"' : ''). ' value="1"/>' . esc_html__('nositelinkssearchbox', 'siteseo') . 
								'</label>
								<p class="description">'.esc_html__('Prevents Google from displaying a sitelinks search box in search results. Enabling this option will remove the "Website" schema from your source code.','siteseo') .'</p>
							</td>
						</tr>

                <td colspan="2"><span class="dashed-line"></span></td>

                 <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Indicate paginated content to Google','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[page_rel]" type="checkbox"' . (!empty($option_page_rel) ? 'checked="yes"' : '') . ' value="1"/>' . esc_html__('Add rel next/prev link in head of paginated archive pages', 'siteseo') . 
                        '</label>
                        <p class="description">'.esc_html__('eg: https://example.com/category/my-category/page/2/.','siteseo').'</p>
                    </td>
                </tr>

                <td colspan="2"><span class="dashed-line"></span></td>

                 <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('noindex on paged archives','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[titles_paged_noindex]" type="checkbox" '. (!empty($option_paged_noindex) ? 'checked="yes"' : '') . ' value="1"/>'.esc_html__('Add a "noindex" meta robots for all paginated archive pages', 'siteseo'). 
                        '</label>
                        <p class="description">'.esc_html__('eg: https://example.com/category/my-category/page/2/.','siteseo').'</p>
                    </td>
                </tr>

                <td colspan="2"><span class="dashed-line"></span></td>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('noindex on attachment pages','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[attachments_noindex]" type="checkbox"' . (!empty($option_attachments_noindex) ? 'checked="yes"' : '').' value="1"/>'.esc_html__(' Add a "noindex" meta robots for all attachment pages', 'siteseo') . 
                        '</label>
                        <p class="description">'.esc_html__('eg: https://example.com/my-media-attachment-page.','siteseo').'</p>
                    </td>
                </tr>
            </tbody>
        </table><input type="hidden" name="siteseo_options[advanced_tab]" value="1"/>';
    }

	static function archives(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

        	// $options = $siteseo->titles_settings;
		$options = get_option('siteseo_titles_option_name');

        	// Load settings
		$option_author_title = !empty($options['titles_archives_author_title']) ? $options['titles_archives_author_title'] : '';
		$option_author_desc = !empty($options['titles_archives_author_desc']) ? $options['titles_archives_author_desc'] : '';
		$option_author_noindex = !empty($options['titles_archives_author_noindex']) ? $options['titles_archives_author_noindex'] : '';
		$option_author_disabled = !empty($options['titles_archives_author_disable']) ? $options['titles_archives_author_disable'] : '';
		$option_date_title = !empty($options['titles_archives_date_title']) ? $options['titles_archives_date_title'] : '';
		$option_date_desc = !empty($options['titles_archives_date_desc']) ? $options['titles_archives_date_desc'] : '';
		$option_date_noindex = !empty($options['titles_archives_date_noindex']) ? $options['titles_archives_date_noindex'] : '';
		$option_date_disabled = !empty($options['titles_archives_date_disable']) ? $options['titles_archives_date_disable'] : '';
		$option_search_title = !empty($options['titles_archives_search_title']) ? $options['titles_archives_search_title'] : '';
		$option_search_desc = !empty($options['titles_archives_search_desc']) ? $options['titles_archives_search_desc'] : '';
		$option_search_noindex = !empty($options['titles_archives_search_title_noindex']) ? $options['titles_archives_search_title_noindex'] : '';
		$option_404_title = !empty($options['titles_archives_404_title']) ? $options['titles_archives_404_title'] : '';
		$option_404_desc = !empty($options['titles_archives_404_desc']) ? $options['titles_archives_404_desc'] : '';
    
		$archives_fields = [
			'author-archives' => 'Author archives',
			'date-archives'   => 'Date archives',
			'search-archives' => 'Search archives',
			'404-archives'    => '404 archives'
		];
		
		$post_types = siteseo_post_types();

		echo'<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
					<div class="siteseo-container">';
					$is_first = true;
					foreach($archives_fields as $post_key => $post_val){
						$active_class = $is_first ? 'active' : '';
						echo '<a href="-' . esc_attr($post_key) . '" class="' . esc_attr($active_class) . '">' . esc_html($post_val) . '</a>';
						$is_first = false;
					}

					foreach($post_types as $post_name => $post_type){
						if($post_type->has_archive){
							$active_class = $is_first ? 'active' : '';
							echo '<a href="-' . esc_attr($post_name) . '" class="' . esc_attr($active_class) . '">' . esc_html($post_type->label) . '</a>';
							$is_first = false;
						}
					}

					echo '</div>
					</th>
					<td>
					<div id="author-archives">
						<h3>'.esc_html__('Archives', 'siteseo').'</h3>
						<div class="siteseo_wrap_label">
							<p class="description">'.esc_html__('Personalize your meta descriptions for all archives.','siteseo').'</p>
						</div>
						<span class="line"></span>
						<h3>'.esc_html__('Author archives', 'siteseo').'</h3>
						<div class="siteseo_wrap_label"><p>'.esc_html__('Title template', 'siteseo').'</p></div>
						<input type="text" name="siteseo_options[author_title]" value="'. esc_attr($option_author_title) . '">
    
						<div class="wrap-tags">
							<button class="tag-title-btn" id="btn-author-acrhive-title" data-tag="%%post_author%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('POST AUTHOR','siteseo').'
							</button>
							<button class="tag-title-btn" id="btn-author-acrhive-separator" data-tag="%%sep%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR','siteseo').'
							</button>
							<button class="tag-title-btn" id="btn-author-acrhive-sitetitle" data-tag="%%sitetitle%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE','siteseo').'
							</button>';
							siteseo_suggestion_button();
						echo '</div>
    
						<div class="siteseo_wrap_label"><p>Meta description template</p></div>
						<textarea name="siteseo_options[author_desc]">'.esc_html($option_author_desc) . '</textarea><br>
						<div class="siteseo_wrap_label">
							<label>
							<input name="siteseo_options[author_noindex]" type="checkbox" '.(!empty($option_author_noindex) ? 'checked="yes"' : '').' value="1"/>' . wp_kses_post('Do not display author archives in search engine results <strong>(noindex)</strong>') .'
							</label>
    
							<label>
							<input name="siteseo_options[author_disable]" type="checkbox" '.(!empty($option_author_disabled) ? 'checked="yes"' : '').' value="1"/>
							' . esc_html__('Disable author archives', 'siteseo') . '
							</label>
						</div>
						<span class="line"><span>
					</div>
    
					<div id="date-archives">
						<h3>'.esc_html__('Date archives', 'siteseo').'</h3>
						<div class="siteseo_wrap_label"><p>'.esc_html__('Title template','siteseo').'</p></div>
							<input type="text" name="siteseo_options[date_title]" value="'. esc_attr($option_date_title) .'">
							<div class="wrap-tags">
								<button class="tag-title-btn" id="btn-date-archive" data-tag="%%archive_date%%">
									<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('DATE ARCHIVES','siteseo').'
								</button>
								<button class="tag-title-btn" id="btn-date-separator" data-tag="%%sep%%">
									<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR','siteseo').'
								</button>
								<button class="tag-title-btn" id="btn-date-sitetitle" data-tag="%%sitetitle%%">
									<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE','siteseo').'
								</button>';
								siteseo_suggestion_button();
							echo '</div>
    
						<div class="siteseo_wrap_label"><p>'.esc_html__('Meta description template','siteseo').'</p></div>
						<textarea name="siteseo_options[date_desc]">'.esc_attr($option_date_desc).'</textarea><br>
						<div class="siteseo_wrap_label">
							<label>
							<input name="siteseo_options[date_noindex]" type="checkbox" '.(!empty($option_date_noindex) ? 'checked="yes"' : '').' value="1"/>' . wp_kses_post('Do not display date archives in search engine results <strong>(noindex)</strong>') . '
							</label>

							<label>
							<input name="siteseo_options[date_disable]" type="checkbox" '.(!empty($option_date_disabled) ? 'checked="yes"' : '').' value="1"/>
							' . esc_html__('Disable date archives', 'siteseo') . '
							</label>
						</div>
						<span class="line"><span>
					</div>
						
					<div id="search-archives">
						<h3>Search archives</h3>
						<div class="siteseo_wrap_label"><p>'.esc_html__('Title template','siteseo').'</p></div>
						<input type="text" name="siteseo_options[search_title]" value="'.esc_attr($option_search_title).'">

						<div class="wrap-tags">
							<button class="tag-title-btn" id="btn-search-keyword" data-tag="%%search_keywords%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEARCH KEYWORDS','siteseo').'
							</button>
							<button class="tag-title-btn"  id="btn-search-separator" data-tag="%%sep%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR','siteseo').'
							</button>
							<button class="tag-title-btn" id="btn-search-sitetitle" data-tag="%%sitetitle%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE','siteseo').'
							</button>';
							siteseo_suggestion_button();
						echo '</div>

						<div class="siteseo_wrap_label"><p>'.esc_html__('Meta description template','siteseo').'</p></div>
						<textarea name="siteseo_options[search_desc]">'.esc_attr($option_search_desc).'</textarea><br>
						<div class="siteseo_wrap_label">
							<label>
							<input name="siteseo_options[search_noindex]" type="checkbox" '.(!empty($option_search_noindex) ? 'checked="yes"' : '').' value="1"/>
							' . wp_kses_post('Do not display date archives in search engine results <strong>(noindex)</strong>') . '
							</label>
						</div>
						<span class="line"><span>
					</div>
						
					<div id="404-archives">
						<h3>404 archives</h3>
						<div class="siteseo_wrap_label"><p>'.esc_html__('Title template','siteseo').'</p></div>
						<input type="text" name="siteseo_options[title_404]" value="'.esc_attr($option_404_title).'">

						<div class="wrap-tags">
							<button class="tag-title-btn" id="btn-404-sitetitle" data-tag="%%sep%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR','siteseo').'
							</button>
							<button class="tag-title-btn" id="btn-404-separator" data-tag="%%sitetitle%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE','siteseo').'
							</button>';
						siteseo_suggestion_button();
						echo '</div>

						<div class="siteseo_wrap_label"><p>'.esc_html__('Meta description template','siteseo').'</p></div>
						<textarea name="siteseo_options[desc_404]">'.esc_attr($option_404_desc).'</textarea><br>
					</div>
					<br/><br/>
					<span class="line"></span>';

				foreach($post_types as $post_name => $post_type){
					if(empty($post_type->has_archive)){
						continue;
					}

					$post_data = isset($options['titles_archive_titles'][$post_name]) ? $options['titles_archive_titles'][$post_name] : '';

					$archive_title = !empty($post_data['archive_title'])? $post_data['archive_title'] : '';
					$archive_description = !empty($post_data['archive_desc']) ? $post_data['archive_desc'] : '';
					$archive_noindex = !empty($post_data['archive_noindex']) ? $post_data['archive_noindex'] : '';
					$archive_nofollow = !empty($post_data['archive_nofollow']) ? $post_data['archive_nofollow'] : '';

					$value_check = !empty($options['titles_archive_titles'][$post_name]['archive_title']) ? $options['titles_archive_titles'][$post_name]['archive_title'] : '';

					echo'<div id="'.esc_attr($post_name).'">
						<h3>'.esc_html($post_type->label).'</h3>
						<div class="siteseo_wrap_label"><p>'.esc_html__('Title template','siteseo').'</p></div>
						<input type="text" name="siteseo_options['.esc_attr($post_name).'][archive_title]" value="'.esc_attr($archive_title).'">

						<div class="wrap-tags">
							<button class="tag-title-btn" data-tag="%%cpt_plural%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('POST TYPE ARCHIVE NAME','siteseo').'
							</button>
							<button class="tag-title-btn" data-tag="%%sep%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR','siteseo').'
							</button>
							<button class="tag-title-btn" data-tag="%%sitetitle%%">
								<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE','siteseo').'
							</button>';
						   siteseo_suggestion_button();
						echo'</div>

						<div class="siteseo_wrap_label"><p>'.esc_html__('Meta description template','siteseo').'</p></div>
						<textarea name="siteseo_options['.esc_attr($post_name).'][archive_desc]">'.esc_attr($archive_description).'</textarea><div class="wrap-tags">';
						siteseo_suggestion_button();
					echo'</div></div><br>
					<div class="siteseo_wrap_label">
						<label>
						<input name="siteseo_options['.esc_attr($post_name).'][archive_noindex]" type="checkbox" '.(!empty($archive_noindex) ? 'checked="yes"' : '').' value="1"/>
						' . wp_kses_post('Do not display author archives in search engine results <strong>(noindex)</strong>') .'
						</label><br/><br/>
						<label>
						<input name="siteseo_options['.esc_attr($post_name).'][archive_nofollow]" type="checkbox" '.(!empty($archive_nofollow) ? 'checked="yes"' : 'value="1"').' />'.wp_kses_post(__('Do not follow links for this taxonomy archive<strong>(nofollow)</strong>', 'siteseo')).'
						</label>
					</div><span class="line"><span>';
				}
	                    echo'</td>
	                </tr>
	            </tbody>
	        </table>
	        <input type="hidden" name="siteseo_options[archives_tab]" value="1"/>';
	}
    

    static function post_types(){
        global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		$options = get_option('siteseo_titles_option_name');
		$post_types = siteseo_post_types();

        echo '<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <div class="siteseo-container">';
                                $is_first = true;
                                foreach($post_types as $post_type => $post_arr){
                                    $active_class = $is_first ? 'active' : '';
                                    echo '<a href="#'.esc_attr($post_type).'-types" class="'.esc_attr($active_class).'">'.esc_html($post_arr->labels->name).'</a>';
                                    $is_first = false;
                                }
                        echo '</div>
                        </th><td>
						<div>
							<h3>'.esc_html__('Post Types', 'siteseo').'</h3>
						   <div class="siteseo_wrap_label"><p class="description">'.esc_html__('Personalize your titles and meta descriptions for single custom post types.', 'siteseo').'</p></div>
						</div>';

		foreach($post_types as $post_type => $post_arr){
			// Load array
			$post_data = isset($options['titles_single_titles'][$post_type]) ? $options['titles_single_titles'][$post_type] : '';

			// Load settings
			$option_post_disabled = !empty($post_data['disabled']) ? true : false;
			$option_post_title = !empty($post_data['title']) ? $post_data['title'] : '';
			$option_post_desc = !empty($post_data['description']) ? $post_data['description'] : '';
			$option_noindex = !empty($post_data['noindex']) ? $post_data['noindex'] : '';
			$option_nofollow = !empty($post_data['nofollow']) ? $post_data['nofollow'] : '';
			$option_date = !empty($post_data['date']) ? $post_data['date'] : '';
			$option_thumb_gcs = !empty($post_data['thumb_gcs']) ? $post_data['thumb_gcs'] : '';

			echo '<div id="'.esc_attr($post_type).'-types">';
				echo '<div class="siteseo-toggle-cnt">
						<h3>'.esc_html($post_arr->labels->name.' ['.$post_type.']').'</h3>&nbsp;&nbsp;
						<div class="siteseo-toggle-meta '.($option_post_disabled ? '' : 'active').'" id="siteseo-toggle-meta-'.esc_attr($post_type).'"></div>
						<span id="siteseo-arrow-icon" class="dashicons dashicons-arrow-left-alt siteseo-arrow-icon"></span>
						<p class="toggle_state_posts" id="toggle_state_'.esc_attr($post_type).'">'.(!$option_post_disabled ? ' Click to hide any SEO metaboxes / columns for this post type' : ' Click to show any SEO metaboxes / columns for this post type').'</p>
						<input type="hidden" name="siteseo_options['.esc_attr($post_type).'][disabled]" id="enable_'.esc_attr($post_type).'_toggle" value="'.esc_attr($option_post_disabled).'" class="siteseo-suboption-toggle">
					</div>
					
					<div class="siteseo_wrap_label"><p>'.esc_html__('Title template', 'siteseo').'</p></div>
					<input type="text" name="siteseo_options['.esc_attr($post_type).'][title]" value="'.esc_attr($option_post_title).'">
					<div class="wrap-tags">
						<button class="tag-title-btn" id="btn-post-title" data-tag="%%post_title%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('POST TITLE', 'siteseo').'
						</button>
						<button class="tag-title-btn" id="btn-post-separator" data-tag="%%sep%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR', 'siteseo').'
						</button>
						<button class="tag-title-btn" id="btn-post-site-title" data-tag="%%sitetitle%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE', 'siteseo').'
						</button>';
						siteseo_suggestion_button();
					echo '</div>
					<div class="siteseo_wrap_label"><p class="description">'.esc_html__('Meta description template', 'siteseo').'</p></div>
					<textarea name="siteseo_options['.esc_attr($post_type).'][desc]">'.esc_attr($option_post_desc).'</textarea>
					<div class="wrap-tags">
						<button class="tag-title-btn" id="btn-'.esc_attr($post_type).'-meta" data-tag="%%post_excerpt%%">
							<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('POST EXCERPT', 'siteseo').'
						</button>';
						siteseo_suggestion_button();
					echo '</div>
					<div class="siteseo_wrap_label">
						<label>
							<input name="siteseo_options['.esc_attr($post_type).'][noindex]" type="checkbox" '.(!empty($option_noindex) ? 'checked="yes"' : 'value="1"').' />
							'.wp_kses_post(__('Do not display this single post type in search engine results <strong>(noindex)</strong>', 'siteseo')).'
						</label>
					</div>';
					
					if(!empty($option_noindex)){
						echo '<div class="siteseo_wrap_label">
							<div class="siteseo-notice is-error">
								<p>'.wp_kses_post(__('This custom post type is <strong>NOT</strong> excluded from your XML sitemaps despite the fact that it is set to <strong>NOINDEX</strong>.', 'siteseo')).'
								</p>
							</div>
						</div>';
					}
					
					echo '<div class="siteseo_wrap_label">
						<label>
							<input name="siteseo_options['.esc_attr($post_type).'][nofollow]" type="checkbox" '.(!empty($option_nofollow) ? 'checked="yes"' : 'value="1"').' />
							'.wp_kses_post(__('Do not follow links for this single post type <strong>nofollow</strong>', 'siteseo')).'
						</label>
					</div>
					<div class="siteseo_wrap_label">
						<label>
							<input name="siteseo_options['.esc_attr($post_type).'][date]" type="checkbox" '.(!empty($option_date) ? 'checked="yes"' : 'value="1"').' />
							'.esc_html__('Display date in Google search results by adding article:published_time and article:modified_time meta?', 'siteseo').'
						</label>
						<p class="description">'. esc_html__('Unchecking this does not prevent Google from displaying the post date in search results.', 'siteseo').'</p>
					</div>
					<div class="siteseo_wrap_label">
						<label>
							<input name="siteseo_options['.esc_attr($post_type).'][thumb_gcs]" type="checkbox" '.(!empty($option_thumb_gcs) ? 'checked="yes"' : 'value="1"').' />
							'.esc_html__('Display post thumbnail in Google Custom Search results?', 'siteseo').'
						</label>
						<p class="description">'.esc_html__('This option does not apply to traditional search results.', 'siteseo').'</p>
					</div>
				</div>';
		}
                    echo '</td></tr>
                </tbody>
            </table>
			<input type="hidden" name="siteseo_options[post_types_tab]" value="1"/>';
    }
    
    static function taxonomies(){
        global $siteseo;
 
        if(!empty($_POST['submit'])){
            self::save_settings();
        }

        // $options = $siteseo->titles_setting;
        $options = get_option('siteseo_titles_option_name');
		$taxonomies = get_taxonomies(['show_ui' => true, 'public'  => true], 'objects', 'and');

		echo '<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<div class="siteseo-container">';
							$is_first = true;
							foreach($taxonomies as $fields_key => $fields_val){
								$active_class = $is_first ? 'active' : '';
								echo '<a href="#'.esc_attr($fields_key).'-types" class="'.esc_attr($active_class).'">'.esc_html($fields_val->label).'</a>';
								$is_first = false;
							}
            echo '</th>
                    <td>
                        <h3>'.esc_html__('Taxonomies','siteseo').'</h3>
						<div class="siteseo_wrap_label">
                        <p class="description">'.esc_html__('Personalize your meta descriptions for all taxonomy archives.','siteseo').'</p>
						</div>';
		
		foreach($taxonomies as $taxonomy => $_tax){
			// Load array
			$options_tax = isset($options['titles_tax_titles'][$taxonomy]) ? $options['titles_tax_titles'][$taxonomy] : '';
			
			// Load settings
			$option_disabled_category = !empty($options_tax['disabled']) ? $options_tax['disabled'] : '';
			$option_cat_title = !empty($options_tax['title']) ? $options_tax['title'] : '';
			$option_cat_desc = !empty($options_tax['description']) ? $options_tax['description'] : '';
			$option_cat_noindex = !empty($options_tax['noindex']) ? $options_tax['noindex'] : '';
			$option_cat_nofollow = !empty($options_tax['nofollow']) ? $options_tax['nofollow'] : '';
	 
			$taxonomies_fields = [
				'categories-types' => 'Categories',
				'tags-types' => 'Tags',
			];

			echo '<div id="'.esc_attr($taxonomy).'-types"><h3>'.esc_html($_tax->label.'['.$taxonomy.']').'</h3>
				<div class="siteseo-toggle-cnt">
					<div class="siteseo-toggle-meta '.($option_disabled_category ? '' : 'active').'" id="siteseo-toggle-meta-category"></div>
					<span id="siteseo-arrow-icon" class="dashicons dashicons-arrow-left-alt siteseo-arrow-icon"></span>
					<p class="toggle_state_category" id="toggle_state_category">' .($option_disabled_category ? 'Enable' : 'Disable'). '</p>
					<input type="hidden" name="siteseo_options['.esc_attr($taxonomy).'][disabled]" id="enable_'.esc_attr($taxonomy).'" value="'.esc_attr($option_disabled_category).'" class="siteseo-suboption-toggle">
				</div>

				<div class="siteseo_wrap_label">
					<p class="description">'.esc_html__('Title template','siteseo').'</p>
				</div>
				<input type="text" value="'.esc_attr($option_cat_title).'" name="siteseo_options['.esc_attr($taxonomy).'][title]">
				<div class="wrap-tags">
					<button class="tag-title-btn" data-tag="%%_category_title%%">
						<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('CATEGORY TITLE','siteseo').'
					</button>
					<button class="tag-title-btn"  data-tag="%%sep%%">
						<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SEPARATOR','siteseo').'
					</button>
					<button class="tag-title-btn"  data-tag="%%sitetitle%%">
						<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('SITE TITLE','siteseo').'
					</button>';
						siteseo_suggestion_button(); 
				echo '</div>
				<div class="siteseo_wrap_label">
					<p class="description">Meta description template</p>
				</div>
				<textarea name="siteseo_options['.esc_attr($taxonomy).'][desc]">'.esc_attr($option_cat_desc).'</textarea>
				<div class="wrap-tags">
					<button class="tag-title-btn" data-tag="%%_category_description%%">
						<span id="icon" class="dashicons dashicons-insert"></span>'.esc_html__('CATEGORY DESCRIPTION','siteseo').'
					</button>';
					siteseo_suggestion_button();
				echo '</div>
				<div class="siteseo_wrap_label">
					<label>
						<input name="siteseo_options['.esc_attr($taxonomy).'][noindex]" type="checkbox" '.(!empty($option_cat_noindex) ? 'checked="yes"' : 'value="1"').' />
						'.wp_kses_post(__('Do not display this taxonomy archive in search engine results<strong>(noindex)</strong>', 'siteseo')).'
					</label>
				</div>';
				
				if(!empty($option_cat_noindex)){
					echo '<div class="siteseo_wrap_label">
						<div class="siteseo-notice is-error">
							<p>'.wp_kses_post(__('This custom taxonomy is <strong>NOT</strong> excluded from your XML sitemaps despite the fact that it is set to <strong>NOINDEX</strong>. We recommend that you check this out.', 'siteseo')).'
							</p>
						</div>
					</div>';
				}
				
				echo '<div class="siteseo_wrap_label">
					<label>
						<input name="siteseo_options['.esc_attr($taxonomy).'][nofollow]" type="checkbox" '.(!empty($option_cat_nofollow) ? 'checked="yes"' : 'value="1"').' />
						'.wp_kses_post(__('Do not follow links for this taxonomy archive<strong>(nofollow)</strong>', 'siteseo')).'
					</label>
				</div>
			</div>';
		}
                    echo '</td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="siteseo_options[taxonomies_tab]" value="1"/>';
    }
    

    static function save_settings(){
        global $siteseo;
		
		check_admin_referer('siteseo_title_settings');

		if(!siteseo_user_can('manage_title') || !is_admin()){
			return;
		}

		$options = [];

		if(empty($_POST['siteseo_options'])){
			return;
		}

        if(isset($_POST['siteseo_options']['home_tab'])){
            
            $options['titles_sep'] = isset($_POST['siteseo_options']['separator']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['separator'])) : '';
			$options['titles_home_site_title'] = isset($_POST['siteseo_options']['site_title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['site_title'])) : '';
			$options['titles_home_site_title_alt'] = isset($_POST['siteseo_options']['alt_site_title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['alt_site_title'])) : '';
			$options['titles_home_site_desc'] = isset($_POST['siteseo_options']['media_desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['media_desc'])) : '';
        }

        if(isset($_POST['siteseo_options']['advanced_tab'])){

            $options['titles_noindex'] = isset($_POST['siteseo_options']['noindex']);
			$options['titles_nofollow'] = isset($_POST['siteseo_options']['no_follow']);
			$options['titles_noimageindex'] = isset($_POST['siteseo_options']['no_image']);
			$options['titles_noarchive'] = isset($_POST['siteseo_options']['no_archive']);
            $options['titles_nosnippet'] = isset($_POST['siteseo_options']['no_snippet']);
            $options['titles_nositelinkssearchbox'] = isset($_POST['siteseo_options']['no_site_links_searchbox']);
            $options['titles_paged_rel'] = isset($_POST['siteseo_options']['page_rel']);
            $options['titles_paged_noindex'] = isset($_POST['siteseo_options']['titles_paged_noindex']);
            $options['titles_attachments_noindex'] = isset($_POST['siteseo_options']['attachments_noindex']);

        }

        if(isset($_POST['siteseo_options']['post_types_tab'])){
			
			$post_types = siteseo_post_types();
			$post_types = array_keys($post_types);

			// Saving Posts
			foreach($post_types as $post_type){
				$options['titles_single_titles'][$post_type]['disabled'] = !empty($_POST['siteseo_options'][$post_type]['disabled']) ? true : false;
				$options['titles_single_titles'][$post_type]['title'] = isset($_POST['siteseo_options'][$post_type]['title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options'][$post_type]['title'])) : '';
				$options['titles_single_titles'][$post_type]['description'] = isset($_POST['siteseo_options'][$post_type]['desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options'][$post_type]['desc'])) : '';
				$options['titles_single_titles'][$post_type]['noindex'] = isset($_POST['siteseo_options'][$post_type]['noindex']);
				$options['titles_single_titles'][$post_type]['nofollow'] = isset($_POST['siteseo_options'][$post_type]['nofollow']);
				$options['titles_single_titles'][$post_type]['date'] = isset($_POST['siteseo_options'][$post_type]['date']);
				$options['titles_single_titles'][$post_type]['thumb_gcs'] = isset($_POST['siteseo_options'][$post_type]['thumb_gcs']);
			}
        }

        if(isset($_POST['siteseo_options']['archives_tab'])){
			
			$post_types = siteseo_post_types();
			$post_types = array_keys($post_types);
			
            $options['titles_archives_author_title'] = isset($_POST['siteseo_options']['author_title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['author_title'])) : '';
			$options['titles_archives_author_desc'] = isset($_POST['siteseo_options']['author_desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['author_desc'])) : '';
			$options['titles_archives_author_noindex'] = isset($_POST['siteseo_options']['author_noindex']);
			$options['titles_archives_author_disable'] = isset($_POST['siteseo_options']['author_disable']);
            
			$options['titles_archives_date_title'] = isset($_POST['siteseo_options']['date_title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['date_title'])) : '';
			$options['titles_archives_date_desc'] = isset($_POST['siteseo_options']['date_desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['date_desc'])) : '';
			$options['titles_archives_date_noindex'] = isset($_POST['siteseo_options']['date_noindex']);
			$options['titles_archives_date_disable'] = isset($_POST['siteseo_options']['date_disable']);
            
			$options['titles_archives_search_title'] = isset($_POST['siteseo_options']['search_title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['search_title'])) : '';
			$options['titles_archives_search_desc'] = isset($_POST['siteseo_options']['search_desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['search_desc'])) : '';
			$options['titles_archives_search_title_noindex'] = isset($_POST['siteseo_options']['search_noindex']);
            
			$options['titles_archives_404_title'] = isset($_POST['siteseo_options']['title_404']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['title_404'])) : '';
			$options['titles_archives_404_desc'] = isset($_POST['siteseo_options']['desc_404'])  ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['desc_404'])) : '';
			
			foreach($post_types as $post_type){					
				$options['titles_archive_titles'][$post_type]['archive_title'] = isset($_POST['siteseo_options'][$post_type]['archive_title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options'][$post_type]['archive_title'])) : '';
				$options['titles_archive_titles'][$post_type]['archive_desc'] = isset($_POST['siteseo_options'][$post_type]['archive_desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options'][$post_type]['archive_desc'])) : '';
				$options['titles_archive_titles'][$post_type]['archive_noindex'] = isset($_POST['siteseo_options'][$post_type]['archive_noindex']);
				$options['titles_archive_titles'][$post_type]['archive_nofollow'] = isset($_POST['siteseo_options'][$post_type]['archive_nofollow']);
			}

        }

        if(isset($_POST['siteseo_options']['taxonomies_tab'])){
			$taxonomies = get_taxonomies(['show_ui' => true, 'public'  => true], 'objects', 'and');
			$taxonomies = array_keys($taxonomies);

			foreach($taxonomies as $taxonomy){
				$options['titles_tax_titles'][$taxonomy]['disabled'] = !empty($_POST['siteseo_options'][$taxonomy]['disabled']) ? true : false;
				$options['titles_tax_titles'][$taxonomy]['title'] = isset($_POST['siteseo_options'][$taxonomy]['title']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options'][$taxonomy]['title'])) : '';
				$options['titles_tax_titles'][$taxonomy]['description'] = isset($_POST['siteseo_options'][$taxonomy]['desc']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options'][$taxonomy]['desc'])) : '';
				$options['titles_tax_titles'][$taxonomy]['noindex'] = isset($_POST['siteseo_options'][$taxonomy]['noindex']);
				$options['titles_tax_titles'][$taxonomy]['nofollow'] = isset($_POST['siteseo_options'][$taxonomy]['nofollow']);
			}
        }

        update_option('siteseo_titles_option_name', $options);
    }
 }
