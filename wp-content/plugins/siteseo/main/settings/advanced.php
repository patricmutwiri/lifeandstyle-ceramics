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

class Advanced{

	static function menu(){
		global $siteseo;

		$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tab_image_seo'; // Default tab

		$titles_meta_subtabs = [
			'tab_image_seo' => esc_html__('Image SEO', 'siteseo'),
			'tab_advanced' => esc_html__('Advanced', 'siteseo'),
			'tab_appearance' => esc_html__('Appearance', 'siteseo'),
			'tab_security' => esc_html__('Security', 'siteseo'),
			'tab_toc' => esc_html__('Table of content', 'siteseo')
		];
		
		
		echo '<div id="siteseo-root">';
		Util::admin_header();

		echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">';

		wp_nonce_field('siteseo_advance_settings');

		$advanced_toggle = isset($siteseo->setting_enabled['toggle-advanced']) ? $siteseo->setting_enabled['toggle-advanced'] : '';
		$nonce = wp_create_nonce('siteseo_toggle_nonce');

		Util::render_toggle('Image SEO & Advanced Settings - SiteSEO', 'advanced_toggle', $advanced_toggle, $nonce);

		echo '<div id="siteseo-tabs" class="wrap">
			<div class="siteseo-nav-tab-wrapper">';

		foreach($titles_meta_subtabs as $tab_key => $tab_caption){

			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo '<a id="' . esc_attr($tab_key) . '-tab" class="siteseo-nav-tab' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab_key) . '">' . esc_html($tab_caption) . '</a>';
		}

		echo '</div>		
		<div class="tab-content-wrapper">
		<div class="siteseo-tab '.($current_tab == 'tab_image_seo' ? ' active' : '').'" id="tab_image_seo" style="display: none;">';
		self::image_seo();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_advanced' ? ' active' : '').'" id="tab_advanced" style="display: none;">';
		self::advanced();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_appearance' ? ' active' : '').'" id="tab_appearance" style="display: none;">';
		self::appearance();
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_security' ? ' active' : '').'" id="tab_security" style="display: none;">';
		self::security(); 
		echo '</div>
		<div class="siteseo-tab '.($current_tab == 'tab_toc' ? ' active' : '').'" id="tab_toc" style="display: none;">';
		self::toc(); 
		echo '</div>
		</div>';

		Util::submit_btn();
		echo '</form></div>';

	}

	static function image_seo(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		//$options = $siteseo->$advanced_settings;
		$options = get_option('siteseo_advanced_option_name');

		$option_attachment = isset($options['advanced_attachments']) ? $options['advanced_attachments'] : '';
		$option_attachment_file = isset($options['advanced_attachments_file']) ? $options['advanced_attachments_file'] : '';
		$option_clean_filename = isset($options['advanced_clean_filename']) ? $options['advanced_clean_filename'] : '';
		$option_img_title = isset($options['advanced_image_auto_title_editor']) ? $options['advanced_image_auto_title_editor'] : '';
		$option_img_alt = isset($options['advanced_image_auto_alt_editor']) ? $options['advanced_image_auto_alt_editor'] : '';
		$option_target_key = isset($options['advanced_image_auto_alt_target_kw']) ? $options['advanced_image_auto_alt_target_kw'] : '';
		$option_cap_img = isset($options['advanced_image_auto_caption_editor']) ? $options['advanced_image_auto_caption_editor'] : '';
		$option_desc_img = isset($options['advanced_image_auto_desc_editor']) ? $options['advanced_image_auto_desc_editor'] : '';

		echo '<h3 class="siteseo-tabs">'.esc_html__('Image SEO','siteseo').'</h3>
        <p>'.esc_html__('Images can drive significant traffic to your site. Be sure to always add alt text, optimize file sizes, and properly name the files, among other best practices.','siteseo').'</p>

        <table class="form-table">
            <tbody class="siteseo_tbody">
                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Redirect attachment pages to the post parent page.','siteseo').'</th>
                    <td>
                        <label>
				        <input name="siteseo_options[attachment]" type="checkbox"'.(!empty($option_attachment) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Redirect attachment pages to the post parent, or to the homepage if no parent exists.', 'siteseo'). 
			            '</label>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Redirect attachment pages to their file URL','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[attachment_file]" type="checkbox"'.(!empty($option_attachment_file) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Redirect attachment pages to their respective file URLs (e.g., https://www.example.com/my-image-file.jpg).', 'siteseo'). 
                        '</label>
                        <p class="description">'.esc_html__('If this option is enabled, it will override the redirection of attachment pages to the post parent.','siteseo').'</p>
                    </td>
                <tr>

				<tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Cleaning media filename','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[clean_filename]" type="checkbox"'.(!empty($option_clean_filename) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('When uploading media, remove accents, spaces, and capital letters, and enforce UTF-8 encoding.', 'siteseo'). 
                        '</label>
                        <p class="description">'.esc_html__('e.g. "ExãMple 1 cópy!.jpg" => "example-1-copy.jpg"','siteseo').'</p>
                    </td>
                <tr>

				<tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Automatically set the image Title','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[auto_img_title]" type="checkbox"'.(!empty($option_img_title) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('When uploading an image file, automatically set the title to match the filename.', 'siteseo'). 
                        '</label>
                        <p class="description">' . esc_html__('We use the product title for ', 'siteseo') . esc_html(!defined('SITEPAD') ? 'WooCommerce' : 'Kkart') . esc_html__(' items.', 'siteseo') . '</p>
                    </td>
                <tr>

				<tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Automatically set the image Alt txt','siteseo').'</th>
                    <td>
                        <label><input name="siteseo_options[auto_img_alt]" type="checkbox"'.(!empty($option_img_alt) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('When uploading an image file, automatically set the alt text to match the filename.', 'siteseo'). 
                        '</label>
                    </td>
                <tr>

				<tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Automatically set the image alt text using target keywords..','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[auto_target_keyword]" type="checkbox"'.(!empty($option_target_key) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Use the target keywords if no alt text is set for the image.', 'siteseo'). 
                        '</label>
                        <p class="description">'.esc_html__('This setting will apply only to images without alt text on the frontend. It is retroactive, meaning if you disable it, images that previously had empty alt text will revert to being empty.','siteseo').'</p>
                    </td>
                <tr>

				<tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Automatically set the image Caption','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[caption_image]" type="checkbox"'.(!empty($option_cap_img) ? 'checked="yes"' : '') . ' value="1"/>'.esc_html__('When uploading an image file, automatically set the caption to match the filename.', 'siteseo') . 
                        '</label>
                    </td>
                <tr>

				<tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Automatically set the image Description','siteseo').'</th>
                    <td>
                        <label>
                            <input name="siteseo_options[description_img]" type="checkbox"'.(!empty($option_desc_img) ? 'checked="yes"' : ''). ' value="1"/>'.esc_html__('When uploading an image file, automatically set the description to match the filename.', 'siteseo'). 
                        '</label>
                    </td>
                <tr>

			</tbody>
		</table><input type="hidden" name="siteseo_options[image_seo]" value="1"/>';
	}
	
	static function advanced(){
		global $siteseo, $wp_version;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		//$options = $siteseo->advanced_settings;
		$options = get_option('siteseo_advanced_option_name');
		
		$platform_name = defined('SITEPAD') ? 'SitePad' : 'WordPress';

		$option_taxonomy_desc = isset($options['advanced_tax_desc_editor']) ? $options['advanced_tax_desc_editor'] : '';
		$option_category_url = isset($options['advanced_category_url']) ? $options['advanced_category_url'] : '';
		$option_noreferrer_link = isset($options['advanced_noreferrer']) ? $options['advanced_noreferrer'] : '';
		$option_wp_generator = isset($options['advanced_wp_generator']) ? $options['advanced_wp_generator'] : '';
		$option_hentry_post = isset($options['advanced_hentry']) ? $options['advanced_hentry'] : '';
		$option_author_url = isset($options['advanced_comments_author_url']) ? $options['advanced_comments_author_url'] : '';
		$option_site_fileds = isset($options['advanced_comments_website']) ? $options['advanced_comments_website'] : '';
		$option_rel_attributes = isset($options['advanced_comments_form_link']) ? $options['advanced_comments_form_link'] : '';
		$option_shortlink = isset($options['advanced_wp_shortlink']) ? $options['advanced_wp_shortlink'] : '';
		$option_wlw_meta_tag = isset($options['advanced_wp_wlw']) ? $options['advanced_wp_wlw'] : '';
		$option_rsd_meta_tag = isset($options['advanced_wp_rsd']) ? $options['advanced_wp_rsd'] : '';
		$option_google_meta_value = isset($options['advanced_google']) ? $options['advanced_google'] : '';
		$option_bing_meta_value = isset($options['advanced_bing']) ? $options['advanced_bing'] : '';
		$option_pinterest_meta_value = isset($options['advanced_pinterest']) ? $options['advanced_pinterest'] : '';
		$option_yandex_meta_value = isset($options['advanced_yandex']) ? $options['advanced_yandex'] : '';
		$option_remove_wocommerce_cat_url = isset($options['advanced_product_cat_url']) ? $options['advanced_product_cat_url'] : '';
		
		echo '<h3 class="siteseo-tabs">'.esc_html__('Advanced','siteseo').'</h3>
		<p class="description">'.esc_html__('Advanced SEO options for advanced users.','siteseo').'</p>
		<table class="form-table"/>
			<tbody>
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Add WP Editor to taxonomy description textarea','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[taxonomy_desc]" type="checkbox"'.(!empty($option_taxonomy_desc) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Add the TINYMCE editor to the term description field to enable rich text formatting.', 'siteseo'). 
			            '</label>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove /category/ in URL','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[category_url]" type="checkbox"'.(!empty($option_category_url) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Remove /category/ in your permalinks', 'siteseo'). 
			            '</label>
						<p class="description">'.esc_html('e.g. "https://example.com/category/my-post-category/" => "https://example.com/my-post-category/"').'</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">'.esc_html__('Remove category base from product permalinks', 'siteseo').'</th>
					<td>
						<label>
							<input type="checkbox" name="siteseo_options[remove_cate_woocommerce]" '.(!empty($option_remove_wocommerce_cat_url) ? 'checked="yes"' : '').' value="1"/>
							'.
							/* translators: placeholders are just html tags */ 
							wp_kses_post(sprintf(__('Remove %1$s product-category %2$s in your permalinks', 'siteseo'), '<strong>','</strong>')).'
							</label>
						</div>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove noreferrer link attribute in post content','siteseo').'</th>
					<td>
						<label>
				        <input name="siteseo_options[noreferrer_link]" type="checkbox"'.(!empty($option_noreferrer_link) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Remove the noreferrer link attribute from the source code.', 'siteseo'). 
			            '</label>
						<p class="description">'.esc_html__('Useful for affiliate links (eg: Amazon)','siteseo').'</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.
					// translators: %s is the platform name
					sprintf(esc_html__('Remove %s meta generator tag', 'siteseo'), esc_html( $platform_name )).'</th>
					<td>
						<label>
							<input name="siteseo_options[wp_generator_meta]" type="checkbox"'.(!empty($option_wp_generator) ? 'checked="yes"' : '').' value="1"/>'. // translators: %s is the platform name
							sprintf(esc_html__('Remove %s meta generator in source code', 'siteseo'), esc_html( $platform_name)). 
						'</label>
						<div class="siteseo-styles pre"><pre>'.esc_html('<meta name="generator" content="WordPress '.(!empty($wp_version) ? $wp_version : '6.8.2').'" />').'</pre></div>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove hentry post class','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[hentry_post]" type="checkbox"' . (!empty($option_hentry_post) ? 'checked="yes"' : '') . ' value="1"/>' . esc_html__('Remove the `hentry` post class to prevent Google from treating it as structured data (schema)', 'siteseo') . 
						'</label>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove author URL','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[comments_author_url]" type="checkbox"'.(!empty($option_author_url) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Remove the comment author URL in comments if the website field is filled in the profile page.', 'siteseo'). 
						'</label>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove website field from comment form','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[website_filed]" type="checkbox"'.(!empty($option_site_fileds) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Remove the website field from the comment form to reduce spam.', 'siteseo'). 
						'</label>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Add the "nofollow", "noopener", and "noreferrer" rel attributes to the links in the comment form.','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[comment_form_link]" type="checkbox"'.(!empty($option_rel_attributes) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Prevent search engines from following or indexing the link to the comment form by adding a "noindex", "nofollow" directive.', 'siteseo'). 
						'</label>
						
						<div class="siteseo-styles pre"><pre>'.esc_url('https://www.example.com/my-blog-post/#respond').'</pre></div>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.
						// translators: %s is the platform name
					    sprintf(esc_html__('Remove %s shortlink meta tag', 'siteseo'), esc_html( $platform_name)).
					'</th>
					<td>
						<label>
							<input name="siteseo_options[shortlink]" type="checkbox"'.(!empty($option_shortlink) ? 'checked="yes"' : '') .' value="1"/>'. // translators: %s is the platform name
							sprintf(esc_html__('Remove %s shortlink meta tag in source.', 'siteseo'), esc_html( $platform_name )).
						'</label>
						
						<div class="siteseo-styles pre"><pre>'.esc_html('<link rel="shortlink" href="https://www.example.com/"/>').'</pre></div>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove Windows Live Writer meta tag','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[wlw_meta]" type="checkbox"'.(!empty($option_wlw_meta_tag) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Remove the Windows Live Writer meta tag from the source code.', 'siteseo') . 
						'</label>
						
						<div class="siteseo-styles pre"><pre>'.esc_html('<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="https://www.example.com/wp-includes/wlwmanifest.xml" />').'</pre></div>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Remove RSD meta tag','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[rsd_meta]" type="checkbox"'.(!empty($option_rsd_meta_tag) ? 'checked="yes"' : ''). ' value="1"/>'. esc_html__('Remove the Really Simple Discovery (RSD) meta tag from the source code.', 'siteseo'). 
						'</label>
						<p class="description">'.
						// translators: %s is the platform name
						sprintf(esc_html__('The %s Site Health feature will display an HTTPS warning if you enable this option. However, this is a false positive.', 'siteseo'), esc_html($platform_name)).'</p>
						<div class="siteseo-styles pre"><pre>' . esc_html('<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="https://www.example.com/wp-includes/wlwmanifest.xml" />') . '</pre></div>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Google site verification','siteseo').'</th>
					<td>
						<input name="siteseo_options[google_meta_value]" type="text" placeholder="'.esc_html__('Enter Google meta value site verification','siteseo').'" value="'.esc_attr($option_google_meta_value).'"/>
						<p class="description">'.
						/* translators: placeholders are just <strong> tag */ 
						wp_kses_post(sprintf(__('If your site is already verified in %1$s Google Search Console %2$s, you can leave this field blank.', 'siteseo'), '<strong>', '</strong>')).'
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Bing site verification','siteseo').'</th>
					<td>
						<input name="siteseo_options[bing_meta_value]" type="text" placeholder="'.esc_html__('Enter Bing meta value site verification','siteseo').'" value="'.esc_attr($option_bing_meta_value).'"/>
						<p class="description">'.
						/* translators: placeholders are just <strong> tag */ 
						wp_kses_post(sprintf(__('If your site is already verified in %1$s Bing Webmaster Tools %2$s, you can leave this field blank.', 'siteseo'), '<strong>', '</strong>')).'</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Pinterest site verification','siteseo').'</th>
					<td>
						<input name="siteseo_options[pinterest_meta_value]" type="text" placeholder="'.esc_html__('Enter the Pinterest meta value for site verification.','siteseo').'" value="'.esc_attr($option_pinterest_meta_value).'"/>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Yandex site verification','siteseo').'</th>
					<td>
						<input name="siteseo_options[yandex_meta_value]" type="text" placeholder="'.esc_html__('Enter the Yandex meta value for site verification.','siteseo').'" value="'.esc_attr($option_yandex_meta_value).'"/>
					</td>
				</tr>
			</tbody>
		</table><input type="hidden" name="siteseo_options[advanced_tab]" value="1"/>';

	}

	static function appearance(){
		
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		//$options = $siteseo->advanced_settings;
		$options = get_option('siteseo_advanced_option_name');

		$option_enable_universal_metabox = isset($options['appearance_universal_metabox']) ? $options['appearance_universal_metabox'] : '';
		$option_disable_universal_metabox = isset($options['appearance_universal_metabox_disable']) ? $options['appearance_universal_metabox_disable'] : '';
		$option_content_analysis_metabox = isset($options['appearance_ca_metaboxe']) ? $options['appearance_ca_metaboxe'] : '';
		$option_hide_genesis_metabox = isset($options['appearance_genesis_seo_metaboxe']) ? $options['appearance_genesis_seo_metaboxe'] : ''; 
		$option_structured_type_metabox = isset($options['appearance_advice_schema']) ? $options['appearance_advice_schema'] : '';
		$option_admin_bar = isset($options['appearance_adminbar']) ? $options['appearance_adminbar'] : '';
		$option_noindex_admin_bar = isset($options['appearance_adminbar_noindex']) ? $options['appearance_adminbar_noindex'] : '';
		$option_column_title = isset($options['appearance_title_col']) ? $options['appearance_title_col'] : '';
		$option_column_desc = isset($options['appearance_meta_desc_col']) ? $options['appearance_meta_desc_col'] : '';
		$option_column_redirect = isset($options['appearance_redirect_enable_col']) ? $options['appearance_redirect_enable_col'] : '';
		$option_column_post_type = isset($options['appearance_redirect_url_col']) ? $options['appearance_redirect_url_col'] : '';
		$option_column_canonical_post_ty = isset($options['appearance_canonical']) ? $options['appearance_canonical'] : '';
		$option_column_target_key = isset($options['appearance_target_kw_col']) ? $options['appearance_target_kw_col'] : '';
		$option_column_noindex = isset($options['appearance_noindex_col']) ? $options['appearance_noindex_col'] : '';
		$option_column_nofollow = isset($options['appearance_nofollow_col']) ? $options['appearance_nofollow_col'] : '';
		$option_column_words_no = isset($options['appearance_words_col']) ? $options['appearance_words_col'] : '';
		$option_column_score = isset($options['appearance_score_col']) ? $options['appearance_score_col'] : '';
		$option_misc_genesis_metabox = isset($options['appearance_genesis_seo_menu']) ? $options['appearance_genesis_seo_menu'] : '';

		$appearance_fields =[
			'metaboxes'=>'Metaboxes',
			'Columns' => 'Columns',
			'Misc' =>'Misc',
		];
		
		if(!defined('SITEPAD')){
			$appearance_fields['admin-bar'] = 'Admin bar';
		}
		
		echo '<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
						 <div class="siteseo-container">';
							$is_first = true;
							foreach($appearance_fields as $post_key => $post_val){
								$active_class = $is_first ? 'active' : '';
								echo '<a href="#'.esc_attr($post_key).'" class="'.esc_attr($active_class).'">'.esc_html($post_val).'</a>';
								$is_first = false;
							}
						echo '</div>
						</th>
						<td>
							<div>
								<h3>'.esc_html__('Appearance','siteseo').'</h3>
								<div class="siteseo_wrap_label">
									<p class="description">'.esc_html__('Customize the plugin to fit your needs','siteseo').'</p>
								</div>
								<span class="line"></span>
								<h3>'.esc_html__('Metaboxes','siteseo').'</h3>
								<p>'.esc_html__('Edit your SEO metadata directly from your favorite page builder.','siteseo').'</p>
								<table class="form-table" id="metaboxes">
									<tbody>';
									if(!defined('SITEPAD')){
										echo '<tr>
											<th scope="row">'.esc_html__('Universal Metabox (Gutenberg)','siteseo').'</th>
											<td>
												<label><input type="checkbox" name="siteseo_options[enable_universal_metabox]" '.(!empty($option_enable_universal_metabox) ? 'checked="yes"' : 'value="1"').' "/> ' . esc_html__('Enable the universal SEO metabox for the Block Editor (Gutenberg)', 'siteseo') . '<label>
											</td>
										</tr>';
									}
										echo '<tr>
											<th scope="row">'.esc_html__('Disable Universal Metabox','siteseo').'</th>
											<td>
												</label><input type="checkbox" name="siteseo_options[disable_universal_metabox]" '.(!empty($option_disable_universal_metabox) ? 'checked="yes"' : 'value="1"').' "/>'.esc_html__('Disable the universal SEO metabox','siteseo').'
											</td>
										</tr>
										
										<tr>
											<th scope="row">'.esc_html__('Remove Content Analysis Metabox','siteseo').'</th>
											<td>
												<label><input type="checkbox" '.(!empty($option_content_analysis_metabox) ? 'checked="yes"' : 'value="1"').'  name="siteseo_options[remove_content_analysis]"> '.esc_html__(' Remove Content Analysis Metabox','siteseo').'</label>
												<p class="description">'.esc_html__('By checking this option, we will no longer track the significant keywords','siteseo').'</p>
											</td>
										</tr>
										
										
										<tr>
											<th scope=row">'.esc_html__('Hide Genesis SEO Metabox','siteseo').'</th>
											<td>
												<label><input type="checkbox" '.(!empty($option_hide_genesis_metabox) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[genesis_metabox]"> '.esc_html__(' Remove Genesis SEO Metabox', 'siteseo').'</label>
											</td>
										</tr>
										
										<tr>
											<th scope="row">'.esc_html__('Hide advice in Structured Data Types metabox','siteseo').'</th>
											<td>
												<label><input type="checkbox" '.(!empty($option_structured_type_metabox) ? 'checked="yes"' : 'value="1"').'    name="siteseo_options[structured_data_types_metabox]">'.esc_html__(' Remove the advice if None schema selected', 'siteseo').'</label>
											</td>
										</tr>
									</tbody>
									</table>';
									
									if(!defined('SITEPAD')){
										echo'<div id="Admin-bar">
										<span class="line"></span>
										<h3>'.esc_html__('Admin bar','siteseo').'</h3>
										<p class="description">'.esc_html__('The admin bar appears on the top of your pages when logged in to your WP admin','siteseo').'</p>
										<table>
										<tbody>
											<tr>
												<th scope="row">'.esc_html__('SEO in admin bar','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_admin_bar) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[admin_bar]"> '.esc_html__('Remove SEO from Admin Bar in backend and frontend', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Noindex in admin bar','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_noindex_admin_bar) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[noindex_admin_bar]"> '.esc_html__('Remove noindex item from Admin Bar in backend and frontend', 'siteseo').'</label>
												</td>
											</tr>
											
										</tbody>
										</table>
										</div>';
									}
									
									echo'<div id="Columns">
									<span class="line"></span>
									<h3>'.esc_html__('Columns','siteseo').'</h3>
									<p>'.esc_html__('Customize the SEO columns displayed in the posts/pages list.','siteseo').'</p>
									<table>
										<tbody>
											<tr>
												<th scope="row">'.esc_html__('Show Title tag column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_title) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[title_post_types]"> '.esc_html__('Add title column', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show Meta description column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_desc) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[desc_post_types]"> '.esc_html__('Add meta description column','siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show Redirection Enable column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_redirect) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[redirect_post_types]"> '.esc_html__('Add redirection enable column', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show Redirect URL column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_post_type) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[redirect_url_post_types]"> '.esc_html__('Add redirection URL column', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show canonical URL column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_canonical_post_ty) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[url_column_post_types]"> '.esc_html__('Add canonical URL column', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show Target Keyword column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_target_key) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[keyword_column_post_types]"> '.esc_html__('Add target keyword column', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show noindex column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_noindex) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[noindex_column_post_types]"> '.esc_html__('Display noindex status', 'siteseo').'</label>
												</td>
											</tr>
											
											<tr>
												<th scope="row">'.esc_html__('Show nofollow column in post types','siteseo').'</th>
													<td>
														<label><input type="checkbox" '.(!empty($option_column_nofollow) ? 'checked="yes"' : 'value="1"').'    name="siteseo_options[nofollow_column_post_types]"> '.esc_html__('Display nofollow status', 'siteseo').'</label>
													</td>
											</tr>											
											
											<th scope="row">'.esc_html__('Show total number of words column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_words_no) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[words_column_post_types]"> '.esc_html__('Display total number of words in content', 'siteseo').'</label>
												</td>
											</tr>
											
											<th scope="row">'.esc_html__('Show content analysis score column in post types','siteseo').'</th>
												<td>
													<label><input type="checkbox" '.(!empty($option_column_score) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[score_column_post_types]"> '.esc_html__('Display Content Analysis results column ("Good" or "Should be improved")', 'siteseo').'</label>
												</td>
											</tr>
											
										</tbody>
									</table>
									</div>
									<div id="Misc">
										<span class="line"></span>
										<h3>'.esc_html__('Misc','siteseo').'</h3>
										<div class="siteseo_wrap_label"><p>'.esc_html__('Miscellaneous settings for the SEO plugin.','siteseo').'</p></div>
										<table class="form-table">
											<tbody>
												<tr>
													<th scope="row">'.esc_html__('Hide Genesis SEO Settings link','siteseo').'</th>
													<td>
														<label><input type="checkbox" '.(!empty($option_misc_genesis_metabox) ? 'checked="yes"' : 'value="1"').' name="siteseo_options[genesis_seo_settings]">'.esc_html__('Remove Genesis SEO link in WP Admin Menu', 'siteseo') .'</label>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="siteseo_options[appearance_tab]"  value="1"/>';
	}
	
	static function security(){
		global $siteseo, $wp_roles, $submenu;
		
		if(!current_user_can('administrator')){
			echo '<div class="siteseo_wrap_label">
				<div class="siteseo-notice is-warning">
					<span id="dashicons-warning" class="dashicons dashicons-info"></span>&nbsp;
					<p>Only Admin can change security settings</p>
				</div>
			</div>';
			return;
		}

		if(!empty($_POST['submit'])){
			self::save_settings();
		}
		
		$options = get_option('siteseo_advanced_option_name');
		$menus_check = $submenu['siteseo'];
		$security_fields = [
			'siteseo-metaboxes' => 'SiteSEO Metaboxes',
			'siteseo-settings-pages' => 'SiteSEO setting pages',
		];
		
		$roles = get_editable_roles();

		if(empty($roles)){
			return;
		}

		wp_nonce_field('siteseo_advance_settings');

		echo '<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<div class="siteseo-container">';
						$is_first = true;
						foreach($security_fields as $post_key => $post_val){
							$active_class = $is_first ? 'active' : '';
							echo '<a href="#' . esc_attr($post_key) . '" class="' . esc_attr($active_class) . '">' . esc_html($post_val) . '</a>';
							$is_first = false;
						}
						echo '</div>
					</th>
					<td>
						<div>
							<h3 class="siteseo-tabs">'.esc_html__('Security', 'siteseo').'</h3>
							<div class="siteseo_wrap_label">
								<p class="description">'.esc_html__('Control access to SEO settings and metaboxes by user roles.', 'siteseo').'</p>
							</div>
							
							<h3 class="siteseo-tabs">'.esc_html__('SiteSEO metaboxes', 'siteseo').'</h3>
							<div class="siteseo_wrap_label">
								<p>'.esc_html__('Check a user role to prevent it from editing a specific metabox.', 'siteseo').'</p>
							</div>
							
							<table class="form-table" id="siteseo-metaboxes">
								<tbody>
									<tr>
										<th scope="row">'.esc_html__('Block SEO metabox to user roles', 'siteseo').'</th>
										<td>';
										foreach($roles as $key => $role){
											if(empty($role['capabilities']) || !is_array($role['capabilities']) || !array_key_exists('publish_posts' , $role['capabilities'])){
												continue;
											}

											$checked = isset($options['security_metaboxe_role'][$key]) ? 'checked' : '';
											echo '<label>
												<input type="checkbox" name="siteseo_options[security_metaboxe_role]['.esc_attr($key).']" value="1" '.esc_attr($checked).'/>
												<strong>'.esc_html($role['name']).'</strong>
											</label><br/><br/>';
										}
										echo '</td>
									</tr>
									
									<tr>
										<th scope="row">'.esc_html__('Block Content analysis metabox to user roles', 'siteseo').'</th>
										<td>';
										foreach($roles as $key => $role){
											if(empty($role['capabilities']) || !is_array($role['capabilities']) || !array_key_exists('publish_posts' , $role['capabilities'])){
												continue;
											}

											$checked = isset($options['security_metaboxe_ca_role'][$key]) ? 'checked' : '';
										echo '<label>
											<input type="checkbox" name="siteseo_options[security_metaboxe_ca_role]['.esc_attr($key).']" value="1" '.esc_attr($checked).'/><strong>'.esc_html($role['name']).'</strong>
											</label><br/><br/>';
										}
										echo '</td>
									</tr>
								</tbody>
							</table>
							
							<span class="line"></span>
							
							<h3 class="siteseo-tabs">'.esc_html__('SiteSEO settings pages', 'siteseo').'</h3>
							<p class="description">'.esc_html__('Check a user role to allow it to edit a specific settings page', 'siteseo').'</p>
							
							<table class="form-table" id="siteseo-settings-pages">
								<tbody>';
								
								$settings_pages = [
									'titles' => esc_html__('Titles & Metas', 'siteseo'),
									'xml-sitemap' => esc_html__('Sitemaps', 'siteseo'),
									'social' => esc_html__('Social Networks', 'siteseo'),
									'google-analytics' => esc_html__('Analytics', 'siteseo'),
									'instant-indexing' => esc_html__('Instant Indexing', 'siteseo'),
									'advanced' => esc_html__('Advanced', 'siteseo'),
								];
								
								foreach($settings_pages as $page_key => $page_title){
									echo '<tr>
										<th scope="row">' . esc_html($page_title) . '</th>
										<td>';
										foreach($roles as $role_key => $role){
											if(empty($role['capabilities']) || !is_array($role['capabilities']) || !array_key_exists('publish_posts' , $role['capabilities']) || $role_key == 'administrator'){
												continue;
											}
											
											$checked = isset($options['siteseo_advanced_security_metaboxe_siteseo-' . $page_key][$role_key]) ? 'checked' : '';
											echo '<label>
												<input type="checkbox" name="siteseo_options[siteseo_advanced_security_metaboxe_siteseo-' . esc_attr($page_key) . '][' . esc_attr($role_key) . ']" value="1" ' . esc_attr($checked) . '/>
												<strong>' . esc_html($role['name']) . '</strong>
											</label><br/><br/>';
										}
										echo '</td>
									</tr>';
								}

								//Pro 
								if(is_plugin_active('siteseo-pro/siteseo.php') && !defined('SITEPAD')){
									echo '<tr>
										<th scope="row">' . esc_html__('Pro Features', 'siteseo') . '</th>
										<td>';
										foreach($roles as $key => $role){
											if(empty($role['capabilities']) || !is_array($role['capabilities']) || !array_key_exists('publish_posts' , $role['capabilities']) || $key == 'administrator'){
												continue;
											}
											
											$checked = isset($options['siteseo_advanced_security_page_pro'][$key]) ? 'checked' : '';
											echo '<label>
												<input type="checkbox" name="siteseo_options[siteseo_advanced_security_page_pro][' . esc_attr($key) . ']" value="1" ' . esc_attr($checked) . '/>
												<strong>'.esc_html($role['name']).'</strong>
											</label><br/><br/>';
										}
										echo '</td>
									</tr>';
								}

								echo '</tbody>
							</table>
						</div>
					</td>
				</tr>
			</tbody></table><input type="hidden" name="siteseo_options[security_tab]" value="1"/>';
	}
	
	static function toc(){
		global $siteseo;
		
		if(!empty($_POST['submit'])){
			self::save_settings();
		}
		
		$options = get_option('siteseo_advanced_option_name');

		$option_toc_enable = isset($options['toc_enable']) ? $options['toc_enable'] : '';
		$option_toc_label = isset($options['toc_label']) ? $options['toc_label'] : '';
		$option_headings = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
		$option_list_types = [
			'ol' => __('Ordered List', 'siteseo'),
			'ul' => __('Unordered List', 'siteseo')
		];

		echo'<h3 class="siteseo-tabs">'.esc_html__('Table of Contents', 'siteseo').'</h3>
		<p>'.esc_html__('A table of content works as an index section for your post or page. It helps search engines understand your page structure and users find specific sections quickly, which might help SEO, as it helps search engines better understand the structure of your content and also improves user experience.', 'siteseo').'</p>
		<p>'.esc_html__('To use Table of Content on your pages, you can use this shortcode', 'siteseo').' <code>[siteseo_toc]</code></p>

		<table class="form-table">
			<tr>
				<th scope="row">'.esc_html__('Enable TOC', 'siteseo').'</th>
				<td>
					<label>
						<input type="checkbox" value="1" id="siteseo_toc_enable" name="siteseo_options[toc_enable]" '.checked($option_toc_enable, true, false).'/>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">'.esc_html__('TOC Label', 'siteseo').'</th>
				<td>
					<label>
						<input type="text" value="'.esc_attr($option_toc_label).'" name="siteseo_options[toc_label]" placeholder="'.esc_attr__('Table of content', 'siteseo').'"/>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">'.esc_html__('Exclude Headings', 'siteseo').'</th>
				<td>
					<div style="display:flex; gap: 20px;">';
					foreach($option_headings as $heading){
						$checked = !empty($options) && !empty($options['toc_excluded_headings']) && is_array($options['toc_excluded_headings']) && in_array($heading, $options['toc_excluded_headings']);
						echo '<label>
							<input type="checkbox" value="'.esc_attr($heading).'" name="siteseo_options[toc_excluded_headings][]" '.checked($checked, true, false).'/>'.esc_html(strtoupper($heading)).'
						</label>';
					}
					echo '</div>
				</td>
			</tr>
			<tr>
				<th scope="row">'.esc_html__('List Type', 'siteseo').'</th>
				<td>
					<div>
						<label>
							<select name="siteseo_options[toc_heading_type]">';
								foreach($option_list_types as $list_type => $list_title){
									$selected = !empty($options['toc_heading_type']) && $options['toc_heading_type'] == $list_type ? 'selected' : '';
									echo '<option value="'.esc_attr($list_type).'" '.esc_attr($selected).'>'.esc_html($list_title).'</option>';
								}
							echo '</select>
						</label>
					</div>
				</td>
			</tr>
		</table><input type="hidden" name="siteseo_options[toc_tab]" value="1" />';
	}

	static function save_settings(){
		global $siteseo;
		
		check_admin_referer('siteseo_advance_settings');
		
		if(!siteseo_user_can('manage_advanced') || !is_admin()){
			return;
		}
		
		$options = [];
		
		if(empty($_POST['siteseo_options'])){
			return;
		}
		
		if(isset($_POST['siteseo_options']['image_seo'])){
			$options['advanced_attachments'] = isset($_POST['siteseo_options']['attachment']);
			$options['advanced_attachments_file'] = isset($_POST['siteseo_options']['attachment_file']);
			$options['advanced_clean_filename'] = isset($_POST['siteseo_options']['clean_filename']);
			$options['advanced_image_auto_title_editor'] = isset($_POST['siteseo_options']['auto_img_title']);
			$options['advanced_image_auto_alt_editor'] = isset($_POST['siteseo_options']['auto_img_alt']);
			$options['advanced_image_auto_alt_target_kw'] = isset($_POST['siteseo_options']['auto_target_keyword']);
			$options['advanced_image_auto_caption_editor'] = isset($_POST['siteseo_options']['caption_image']);
			$options['advanced_image_auto_desc_editor'] = isset($_POST['siteseo_options']['description_img']);

		}
		
		if(isset($_POST['siteseo_options']['advanced_tab'])){
			
			$options['advanced_product_cat_url'] = isset($_POST['siteseo_options']['remove_cate_woocommerce']);
			$options['advanced_tax_desc_editor'] = isset($_POST['siteseo_options']['taxonomy_desc']);
			$options['advanced_category_url'] = isset($_POST['siteseo_options']['category_url']);
			$options['advanced_noreferrer'] = isset($_POST['siteseo_options']['noreferrer_link']);
			$options['advanced_wp_generator'] = isset($_POST['siteseo_options']['wp_generator_meta']);
			$options['advanced_hentry'] = isset($_POST['siteseo_options']['hentry_post']);
			$options['advanced_comments_author_url'] = isset($_POST['siteseo_options']['comments_author_url']);
			$options['advanced_comments_website'] = isset($_POST['siteseo_options']['website_filed']);
			$options['advanced_comments_form_link'] = isset($_POST['siteseo_options']['comment_form_link']);
			$options['advanced_wp_shortlink'] = isset($_POST['siteseo_options']['shortlink']);
			$options['advanced_wp_rsd'] = isset($_POST['siteseo_options']['rsd_meta']);
			$options['advanced_wp_wlw'] = isset($_POST['siteseo_options']['wlw_meta']);
			$options['advanced_google'] = isset($_POST['siteseo_options']['google_meta_value']) ? sanitize_text_field(Util::extract_content(wp_unslash($_POST['siteseo_options']['google_meta_value']))) : '';
			$options['advanced_bing'] = isset($_POST['siteseo_options']['bing_meta_value']) ? sanitize_text_field(Util::extract_content(wp_unslash($_POST['siteseo_options']['bing_meta_value']))) : '';
			$options['advanced_pinterest'] = isset($_POST['siteseo_options']['pinterest_meta_value']) ? sanitize_text_field(Util::extract_content(wp_unslash($_POST['siteseo_options']['pinterest_meta_value']))) : '';
			$options['advanced_yandex'] = isset($_POST['siteseo_options']['yandex_meta_value']) ? sanitize_text_field(Util::extract_content(wp_unslash($_POST['siteseo_options']['yandex_meta_value']))) : '';

		}
		
		if(isset($_POST['siteseo_options']['appearance_tab'])){
			
			$options['appearance_universal_metabox'] = isset($_POST['siteseo_options']['enable_universal_metabox']);
			$options['appearance_universal_metabox_disable'] = isset($_POST['siteseo_options']['disable_universal_metabox']);
			$options['appearance_ca_metaboxe'] = isset($_POST['siteseo_options']['remove_content_analysis']);
			$options['appearance_genesis_seo_metaboxe'] = isset($_POST['siteseo_options']['genesis_metabox']);
			$options['appearance_advice_schema'] = isset($_POST['siteseo_options']['structured_data_types_metabox']);
			$options['appearance_adminbar'] = isset($_POST['siteseo_options']['admin_bar']);
			$options['appearance_adminbar_noindex'] = isset($_POST['siteseo_options']['noindex_admin_bar']);
			$options['appearance_title_col'] = isset($_POST['siteseo_options']['title_post_types']);
			$options['appearance_meta_desc_col'] = isset($_POST['siteseo_options']['desc_post_types']);
			$options['appearance_redirect_enable_col'] = isset($_POST['siteseo_options']['redirect_post_types']);
			$options['appearance_redirect_url_col'] = isset($_POST['siteseo_options']['redirect_url_post_types']);
			$options['appearance_canonical'] = isset($_POST['siteseo_options']['url_column_post_types']);
			$options['appearance_target_kw_col'] = isset($_POST['siteseo_options']['keyword_column_post_types']);
			$options['appearance_noindex_col'] = isset($_POST['siteseo_options']['noindex_column_post_types']);
			$options['appearance_nofollow_col'] = isset($_POST['siteseo_options']['nofollow_column_post_types']);
			$options['appearance_words_col'] = isset($_POST['siteseo_options']['words_column_post_types']);
			$options['appearance_score_col'] = isset($_POST['siteseo_options']['score_column_post_types']);
			$options['appearance_genesis_seo_menu'] = isset($_POST['siteseo_options']['genesis_seo_settings']);
			
		}
		
		if(isset($_POST['siteseo_options']['toc_tab'])){

			$options['toc_enable'] = isset($_POST['siteseo_options']['toc_enable']);
			$options['toc_label'] = isset($_POST['siteseo_options']['toc_label']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['toc_label'])) : '';
			$options['toc_heading_type'] = isset($_POST['siteseo_options']['toc_heading_type']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['toc_heading_type'])) : '';

			//toc_excluded_headings
			if(isset($_POST['siteseo_options']['toc_excluded_headings'])){
				$options['toc_excluded_headings'] = map_deep(wp_unslash($_POST['siteseo_options']['toc_excluded_headings']), 'sanitize_text_field');
			}
			
		}
		
		
		if(isset($_POST['siteseo_options']['security_tab']) && current_user_can('manage_options')){
			
			$has_admin = ['security_metaboxe_role', 'security_metaboxe_ca_role'];
			
			$settings = [
				'security_metaboxe_role',
				'security_metaboxe_ca_role',
				'siteseo_advanced_security_metaboxe_siteseo-titles',
				'siteseo_advanced_security_metaboxe_siteseo-xml-sitemap',
				'siteseo_advanced_security_metaboxe_siteseo-social',
				'siteseo_advanced_security_metaboxe_siteseo-google-analytics',
				'siteseo_advanced_security_metaboxe_siteseo-instant-indexing',
				'siteseo_advanced_security_metaboxe_siteseo-advanced',
				'siteseo_advanced_security_metaboxe_siteseo-import-export'
			];

			$advanced_options = map_deep($_POST['siteseo_options'], function($value){
				
				if(!empty($value)){
					if(is_string($value)){
						return true;
					}

					return sanitize_text_field(wp_unslash($value));
				}

				return '';
			});
			
			$filtered_options = [];
			$roles = get_editable_roles();
			foreach($settings as $setting){
				$accepted_roles = [];
				foreach($roles as $key => $role){
					// We will skip, admin if the setting does not require it.
					if(!in_array($setting, $has_admin) && $key ==  'administrator'){
						continue;
					}
					
					// We only accept roles which has capability to 'publish_posts', as giving access to anyone less does not makes sense.
					if(empty($role['capabilities']) || !is_array($role['capabilities']) || !array_key_exists('publish_posts' , $role['capabilities'])){
						continue;
					}

					array_push($accepted_roles, $key);
				}
				
				// Making sure the roles being pushed are the once we want.
				if(isset($advanced_options[$setting])){
					$filtered_options[$setting] = array_intersect(
						$advanced_options[$setting],
						array_flip($accepted_roles)
					);
				}
			}

			$options = array_merge($options, $filtered_options);
		}

		update_option('siteseo_advanced_option_name', $options);
	}

}
