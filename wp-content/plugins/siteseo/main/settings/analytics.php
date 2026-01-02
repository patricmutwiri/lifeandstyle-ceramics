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

class Analytics{

	static function menu(){
		global $siteseo;

		$analytics_toggle = isset($siteseo->setting_enabled['toggle-google-analytics']) ? $siteseo->setting_enabled['toggle-google-analytics'] : '';
		$nonce = wp_create_nonce('siteseo_toggle_nonce');

		$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tab_google_analytics';

		$analytics_sub_tags = [
			'tab_google_analytics' => esc_html__('Google Analytics', 'siteseo'),
			'tab_matomo' => esc_html__('Matomo', 'siteseo'),
			'tab_clarity' => esc_html__('Clarity', 'siteseo'),
			'tab_advanced' => esc_html__('Advanced', 'siteseo'),
			'tab_cookie' => esc_html__('Cookie bar / GDPR', 'siteseo'),
			'tab_custom_tracking' => esc_html__('Custom Tracking', 'siteseo'),
		];
		
		echo '<div id="siteseo-root">';
		
		Util::admin_header();

		echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">';
		wp_nonce_field('siteseo_analytics_settings');

		Util::render_toggle('Analytics - SiteSEO', 'analytics_toggle', $analytics_toggle, $nonce);
        
		echo '<div id="siteseo-tabs" class="wrap">
			<div class="siteseo-nav-tab-wrapper">';

		foreach($analytics_sub_tags as $tab_key => $tab_caption){
			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo '<a id="'.esc_attr($tab_key).'-tab" class="siteseo-nav-tab'.esc_attr($active_class).'" data-tab="'.esc_attr($tab_key).'">'.esc_html($tab_caption).'</a>';
		}

		echo '</div>
		<div class="tab-content-wrapper">
		<div class="siteseo-tab'.($current_tab == 'tab_google_analytics' ? ' active' : '').'" id="tab_google_analytics" style="display: none;">';
		self::google_anlytics();
		echo '</div>     
		<div class="siteseo-tab'.($current_tab == 'tab_matomo' ? ' active' : '') . '" id="tab_matomo" style="display: none;">';
		self::matomo();
		echo '</div>     
		<div class="siteseo-tab'.($current_tab == 'tab_clarity' ? ' active' : '').'" id="tab_clarity" style="display: none;">';
		self::clarity();
		echo '</div>     
		<div class="siteseo-tab'.($current_tab == 'tab_advanced' ? ' active' : '').'" id="tab_advanced" style="display: none;">';
		self::advanced();
		echo '</div>     
		<div class="siteseo-tab'.($current_tab == 'tab_cookie' ? ' active' : '').'" id="tab_cookie" style="display: none;">';
		self::cookies();
		echo '</div>  
		<div class="siteseo-tab'.($current_tab == 'tab_custom_tracking' ? 'active' : '').'" id="tab_custom_tracking" style="display: none;">';
		self::custom_tracking();
		echo '</div>
		</div>';
		Util::submit_btn();
		echo '</form></div>';
	}
	
	static function custom_tracking(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}
		
		//$options = $siteseo->analaytics_settings;
		$options = get_option('siteseo_google_analytics_option_name');

		$option_head_tracking = !empty($options['google_analytics_other_tracking']) ? $options['google_analytics_other_tracking'] : '';
		$option_body_tracking = !empty($options['google_analytics_other_tracking_body']) ? $options['google_analytics_other_tracking_body'] : '';
		$option_footer_tracking = !empty($options['google_analytics_other_tracking_footer']) ? $options['google_analytics_other_tracking_footer'] : '';
		
		echo '<h3 class="siteseo-tabs">'.esc_html__('Custom Tracking','siteseo').'</h3>
			<P class="description">'.esc_html__('Add custom scripts like GTM or Facebook Pixel by copying and pasting the provided code into the HEAD, BODY, or FOOTER sections.','siteseo').'</p>
			<table class="form-table">
				<tbody>

					<tr>
						<th scope="row">'.esc_html__('[HEAD] Add an additional tracking code (like Facebook Pixel, Hotjar...)','siteseo').'</th>
						<td>
							<textarea name="siteseo_options[head_tracking]" rows="16" placeholder="'.esc_html__('Paste your tracking code here, such as Google Tag Manager (head). Do NOT paste GA4 or Universal Analytics codes, as they are automatically included in your source code.','siteseo').'">'.esc_html($option_head_tracking).'</textarea>
							<p class="description">'.esc_html__('This code will be added in the head section of your page','siteseo').'</p>
						</td>
					</tr>

					<tr>
						<th scope="row">'.esc_html__('[BODY] Add an additional tracking code (like Google Tag Manager...)','siteseo').'</th>
						<td>
							<textarea name="siteseo_options[body_tracking]" rows="16" placeholder="'.esc_html__('This code will be added just after the opening body tag of your page','siteseo').'">'.esc_html($option_body_tracking).'</textarea>
							<p>'.esc_html__('This code will be added just after the opening body tag of your page','siteseo').'</p>
							<p>'.esc_html__('You don‘t see your code? Make sure to call wp_body_open(); just after the opening body tag in your theme.','siteseo').'</p>
						</td>
					</tr>

					<tr>
						<th scope="row">'.esc_html__('[BODY (FOOTER)] Add an additional tracking code (like Google Tag Manager...)', 'siteseo').'</th>
						<td>
							<textarea name="siteseo_options[footer_tracking]" rows="16" placeholder="'.esc_html__('Paste your tracking code here(footer)','siteseo').'">'.esc_html($option_footer_tracking).'</textarea> 
							<p>'.esc_html__('This code will be added just after the closing body tag of your page','siteseo').'</P>
						</td>
					</tr>
				</tbody>
			</table><input type="hidden" name="siteseo_options[custom_tracking_tab]" value="1"/>';
		
	}

	static function cookies(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		//$options = $siteseo->analaytics_settings;
		$options = get_option('siteseo_google_analytics_option_name');

		$option_cookies_postion = !empty($options['google_analytics_hook']) ? $options['google_analytics_hook'] : '';
		$option_tracking_opt = !empty($options['google_analytics_disable']) ? $options['google_analytics_disable'] : '';
		$option_half_disable = !empty($options['google_analytics_half_disable']) ? $options['google_analytics_half_disable'] : '';
		$option_opt_choices = !empty($options['google_analytics_opt_out_edit_choice']) ? $options['google_analytics_opt_out_edit_choice'] : '';
		$option_opt_msg = !empty($options['google_analytics_opt_out_msg']) ? $options['google_analytics_opt_out_msg'] : '';
		$option_opt_msg_ok = !empty($options['google_analytics_opt_out_msg_ok']) ? $options['google_analytics_opt_out_msg_ok'] : '';
		$option_opt_msg_close = !empty($options['google_analytics_opt_out_msg_close']) ? $options['google_analytics_opt_out_msg_close'] : '';
		$option_opt_msg_edit = !empty($options['google_analytics_opt_out_msg_edit']) ? $options['google_analytics_opt_out_msg_edit'] : '';
		$option_cd_exp_date = !empty($options['google_analytics_cb_exp_date']) ? $options['google_analytics_cb_exp_date'] : '30';
		$option_cd_pos = !empty($options['google_analytics_cb_pos']) ? $options['google_analytics_cb_pos'] : '';
		$option_cd_txt_align = !empty($options['google_analytics_cb_txt_align']) ? $options['google_analytics_cb_txt_align'] : '';
		$option_cd_width = !empty($options['google_analytics_cb_width']) ? $options['google_analytics_cb_width'] : '';
		$option_cd_backdrop = !empty($options['google_analytics_cb_backdrop']) ? $options['google_analytics_cb_backdrop'] : '';
		$option_cd_scheme = !empty($options['google_analytics_cb_scheme']) ? $options['google_analytics_cb_scheme'] : '';

		//colors load
		$option_backdrop_bg = !empty($options['google_analytics_cb_backdrop_bg']) ? $options['google_analytics_cb_backdrop_bg'] : '';
		$option_cookiebar_bg = !empty($options['google_analytics_cb_bg']) ? $options['google_analytics_cb_bg'] : '#ffffff';
		$option_cookiebar_txt = !empty($options['google_analytics_cb_txt_col']) ? $options['google_analytics_cb_txt_col'] : '#000000';
		$option_cookiebar_lk = !empty($options['google_analytics_cb_lk_col']) ? $options['google_analytics_cb_lk_col'] : '#0000ff';
		$option_primarybtn_bg = !empty($options['google_analytics_cb_btn_bg']) ? $options['google_analytics_cb_btn_bg'] : '#0073aa';
		$option_primarybtn_bg_hov = !empty($options['google_analytics_cb_btn_bg_hov']) ? $options['google_analytics_cb_btn_bg_hov'] : '#005f8b';
		$option_primarybtn_txt = !empty($options['google_analytics_cb_btn_col']) ? $options['google_analytics_cb_btn_col'] : '#ffffff';
		$option_primarybtn_txt_hov = !empty($options['google_analytics_cb_btn_col_hov']) ? $options['google_analytics_cb_btn_col_hov'] : '#ffffff';
		$option_sec_bg = !empty($options['google_analytics_cb_btn_sec_bg']) ? $options['google_analytics_cb_btn_sec_bg'] : '#cccccc';
		$option_sec_bg_hov = !empty($options['google_analytics_cb_btn_sec_bg_hov']) ? $options['google_analytics_cb_btn_sec_bg_hov'] : '#aaaaaa';
		$option_sec_bg_txt = !empty($options['google_analytics_cb_btn_sec_col']) ? $options['google_analytics_cb_btn_sec_col'] : '#000000';
		$option_sec_bg_txt_hov = !empty($options['google_analytics_cb_btn_sec_col_hov']) ? $options['google_analytics_cb_btn_sec_col_hov'] : '#000000';

		echo '<h3 class="siteseo-tabs">'.esc_html__('Cookies','siteseo').'</h3>
		<p class="description">'.esc_html__('Easily manage user consent for GDPR and customize your cookie bar.','siteseo').'</p>
		<p class="description">'.esc_html__('Compatible with Google Analytics and Matomo.','siteseo').'</p>
		<table>
			<tbody class="form-table">
				<tr>
					<div class="siteseo-notice" id="custom-dimensions">
						<span class="dashicons dashicons-info"></span>
						<p>'.
						/* translators: placeholders are just <strong> tag */ 
						wp_kses_post(sprintf(__('%1$s Note : %2$s This feature applies only to cookies added through SiteSEO analytics and tracking.', 'siteseo'), '<strong>', '</strong>')).'</p>
					</div>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Where to load the cookie bar?','siteseo').'</th>
					<td>
						<select name="siteseo_options[cookie_pos]">
							<option value="wp_body_open" '.selected($option_cookies_postion, 'wp_body_open', false).'>'.esc_html__('After the opening body tag (recommended)', 'siteseo').'</option>
							<option value="wp_footer" '.selected($option_cookies_postion, 'wp_footer', false).'>'.esc_html__('Footer', 'siteseo') .'</option>
							<option value="wp_head" '.selected($option_cookies_postion, 'wp_head', false).'>'.esc_html__('Header (not recommended)', 'siteseo').'</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope"user-select:auto">'.esc_html__('Analytics tracking opt-in','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[opt_tracking]" type="checkbox" '.(!empty($option_tracking_opt) ? 'checked="yes"' : 'value="1"').' />
								'.esc_html__('Obtain user consent for analytics tracking, as required by GDPR.', 'siteseo').'
                        </label><br/><br/>
						<label>
						<input type="checkbox" name="siteseo_options[half_disable]" '.(!empty($option_half_disable) ? 'checked="yes"' : 'value="1"').' />
							'.esc_html__('Display and automatically accept if user does not accept or reject within 10 seconds.','siteseo').'
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto:">'.esc_html__('Allow user to change its choice','siteseo').'</th>
					<td>
						<label>
							<input name="siteseo_options[opt_edit_choices]" type="checkbox" '.(!empty($option_opt_choices) ? 'checked="yes"' : 'value="1"').' />
							Allow user to change its choice about cookies'.esc_html__('Request user consent for analytics tracking (required by GDPR)', 'siteseo').'
                        </label>
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Consent message for user tracking','siteseo').'</th>
					<td>
						<textarea placeholder="'.esc_html__('Enter your message (HTML allowed)','siteseo').'" name="siteseo_options[opt_msg]" >'.esc_attr($option_opt_msg).'</textarea>
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Accept button for user tracking','siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[opt_msg_ok]" value="'.esc_attr($option_opt_msg_ok).'" placeholder="'.esc_html__('Accept','siteseo').'"> 
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Close button','siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[opt_close]" value="'.esc_attr($option_opt_msg_close).'" placeholder="'.esc_attr__('default:X', 'siteseo').'">
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;" >'.esc_html__('Edit cookies button','siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[opt_edit_btn]" value="'.esc_attr($option_opt_msg_edit).'" placeholder="'.esc_attr__('default:Manage cookie', 'siteseo').'">
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('User consent cookie expiration date','siteseo').'</th>
					<td>
						<input type="number"  name="siteseo_options[cd_exp_date]" value="'.esc_attr($option_cd_exp_date).'" >
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Cookie bar position','siteseo').'</th>
					<td>
						<select name="siteseo_options[cd_pos]">
							<option value="bottom" '.selected($option_cd_pos, 'bottom', false).'>'.esc_html__('Bottom (default)','siteseo').'</option>
							<option value="middle" '.selected($option_cd_pos, 'middle', false).'>'.esc_html__('Middle','siteseo').'</option>
							<option value="top" '.selected($option_cd_pos, 'top', false).'>'.esc_html__('Top','siteseo').'</option>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="use-select:auto;">'.esc_html__('Text alignment','siteseo').'</th>
					<td>
						<select name="siteseo_options[cd_txt_align]">
							<option value="center" '.selected($option_cd_txt_align, 'center', false).'>'.esc_html__('Center (default)', 'siteseo').'</option>
							<option value="left" '.selected($option_cd_txt_align, 'left', false).'>'.esc_html__('Left', 'siteseo').'</option>
							<option value="right" '.selected($option_cd_txt_align, 'right', false).'>'.esc_html__('Right', 'siteseo').'</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Cookie bar width', 'siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[cd_width]"  value="'.esc_attr($option_cd_width).'"/>
						<p class="description">'.esc_html__('The default unit is Pixels. To use percentages, simply add % after your custom value (e.g., 80%).', 'siteseo').'</p>
						<br/>
						<span class="line"></span>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;"></th>
					<td>
						<h3>'.esc_html__('Backdrop', 'siteseo').'</h3>
						<p>'.esc_html__('Customize the backdrop of the cookie bar.', 'siteseo').'</p><br/>
						<label>
							<input type="checkbox" name="siteseo_options[cd_backdrop]" '.(!empty($option_cd_backdrop) ? 'checked="yes"' : 'value="1"').'>'. esc_html__('Display a backdrop with the cookie bar', 'siteseo') .'
						</label>
						<br/><br/>
						<p>'.esc_html__('Background color:','siteseo').'</p><br/>
						<input type="color" placeholder="Select color" name="siteseo_options[backdrop_bg]" value="'.esc_attr($option_backdrop_bg).'"/>
						<br/></br/>
						<span class="line"></span>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto;"></th>
					<td>
						<h3>'.esc_html__('Main settings', 'siteseo').'</h3>
						<p>'.esc_html__('Customize the general settings of the cookie bar', 'siteseo').'</p>
						<p>'.esc_html__('Background color:', 'siteseo').'</p><br/>
						<input type="color"  placeholder="'.esc_html__('Select color', 'siteseo').'" name="siteseo_options[cookiesbar_bg]" value="'.esc_attr($option_cookiebar_bg).'"/>
						<p>'.esc_html__('Text color:', 'siteseo').'</p><br/>
						<input type="color"  placeholder="'.esc_html__('Select color', 'siteseo').'" name="siteseo_options[cookiebar_txt]" value="'.esc_attr($option_cookiebar_txt).'"/>
						<p>'.esc_html__('Link color: ','siteseo').'</p></br>
						<input type="color"  placeholder="'.esc_html__('Select color', 'siteseo').'" name="siteseo_options[line_co]" value="'.esc_attr($option_cookiebar_lk).'"/><br/></br>
						<span class="line"></span>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="use-select:auto"></th>
					<td>
						<h3>'.esc_html__('Primary button', 'siteseo').'</h3>
						<p>'.esc_html__('Customize the Accept button', 'siteseo').'</p><br/>
						<p>'.esc_html__('Background color:', 'siteseo').'</p>
						<input type="color"  placeholder="'.esc_html__('Select color', 'siteseo').'" name="siteseo_options[primary_btn_bg]" value="'.esc_attr($option_primarybtn_bg).'"/><br/><br/>
						<p>'.esc_html__('Background color on hover:','siteseo').'</p>
						<input type="color" name="siteseo_options[primary_btn_bg_hov]" value="'.esc_attr($option_primarybtn_bg_hov).'" /><br/><br/>
						<p>'.esc_html__('Text color:', 'siteseo').'</p>
						<input type="color" name="siteseo_options[primary_btn_txt]" value="'.esc_attr($option_primarybtn_txt).'"/>
						<p>'. esc_html__('Text color on hover:', 'siteseo') .'</p>
						<input type="color" name="siteseo_options[primary_btn_txt_hov]" value="'.esc_attr($option_primarybtn_txt_hov).'"/><br/><br/>
						<span class="line"></span>
					</td>
				</tr>
				
				<tr>
					<th scope="row" style="user-select:auto"></th>
					<td>
						<h3>'.esc_html__('Secondary button', 'siteseo').'</h3>
						<p>'.esc_html__('Customize the Accept button', 'siteseo').'</p><br/>
						<p>'.esc_html__('Background color:', 'siteseo').'</p>
						<input type="color"  placeholder="'.esc_html__('Select color','siteseo').'" name="siteseo_options[sec_btn_bg]" value="'.esc_attr($option_sec_bg).'"/><br/><br/>
						<p>'.esc_html__('Background color on hover:', 'siteseo').'</p>
						<input type="color" name="siteseo_options[sec_btn_bg_hov]" value="'.esc_attr($option_sec_bg_hov).'"/><br/><br/>
						<p>'.esc_html__('Text color:', 'siteseo').'</p>
						<input type="color" name="siteseo_options[sec_btn_txt]" value="'.esc_attr($option_sec_bg_txt).'"/>
						<p>'.esc_html__('Text color on hover:', 'siteseo').'</p>
						<input type="color" name="siteseo_options[sec_btn_txt_hov]" value="'.esc_attr($option_sec_bg_txt_hov).'"/>
					</td>
				</tr>
			</tbody>
		</table><input type="hidden" name="siteseo_options[cookies_tab]" value="1"/>'; 

	}
	

	static function matomo(){
		global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		//$options = $siteseo->analaytics_settings;
		$options = get_option('siteseo_google_analytics_option_name');

		$option_enable_matomo = !empty($options['google_analytics_matomo_enable']) ? $options['google_analytics_matomo_enable'] : '';
		$option_self_hosted = !empty($options['google_analytics_matomo_self_hosted']) ? $options['google_analytics_matomo_self_hosted'] : '';
		$option_matomo_id = !empty($options['google_analytics_matomo_id']) ? $options['google_analytics_matomo_id'] : '';
		$option_site_id = !empty($options['google_analytics_matomo_site_id']) ? $options['google_analytics_matomo_site_id'] : '';
		$option_sub_domain = !empty($options['google_analytics_matomo_subdomains']) ? $options['google_analytics_matomo_subdomains'] : '';
		$option_site_domain = !empty($options['google_analytics_matomo_site_domain']) ? $options['google_analytics_matomo_site_domain'] : '';
		$option_enable_corss_domain = !empty($options['google_analytics_matomo_cross_domain']) ? $options['google_analytics_matomo_cross_domain'] : '';
		$option_no_js = !empty($options['google_analytics_matomo_no_js']) ? $options['google_analytics_matomo_no_js'] : '';
		$option_cross_domain_sites = !empty($options['google_analytics_matomo_cross_domain_sites']) ? $options['google_analytics_matomo_cross_domain_sites'] : '';
		$option_no_cookies = !empty($options['google_analytics_matomo_no_cookies']) ? $options['google_analytics_matomo_no_cookies'] : '';
		$options_link_tracking = !empty($options['google_analytics_matomo_link_tracking']) ? $options['google_analytics_matomo_link_tracking'] : '';
		$options_no_heatmaps = !empty($options['google_analytics_matomo_no_heatmaps']) ? $options['google_analytics_matomo_no_heatmaps'] : '';
		$options_matomo_dtn = !empty($options['google_analytics_matomo_dnt']) ? $options['google_analytics_matomo_dnt'] : '';

		$matomo_subtabs = [
			'tracking' => 'Tracking',
		];

		echo '<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<div class="siteseo-container">';
								$is_first = true;
								foreach($matomo_subtabs as $post_key => $post_val){
									$active_class = $is_first ? 'active' : '';
									echo '<a href="#'.esc_attr($post_key).'" class="'.esc_attr($active_class).'">'.esc_html($post_val).'</a>';
									$is_first = false;
								}
							echo '</div>
					</th>
					<td>
						<h3>'.esc_html__('Matomo', 'siteseo').'</h3>
						<div class="siteseo_wrap_label" id="tracking">
						<p class="description">'.esc_html__('Track your users with privacy in mind using Matomo. We support both On-Premise and Cloud installations.', 'siteseo').'</p>
						</div>
						<span class="line"></span>
						<h3>'.esc_html__('Tracking', 'siteseo').'</h3>
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">'.esc_html__('Enable Matomo tracking.', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[enable_matomo]" '.(!empty($option_enable_matomo) ? 'checked="yes"' : '').' value="1"/>
											'.esc_html__('Enable Matomo tracking', 'siteseo') .'
										</label>
										<p class="description">'.esc_html__('A Matomo Cloud account or a self-hosted Matomo installation is necessary.', 'siteseo').'</p>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Self hosted Matomo installation.', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[self_hosted]" '.(!empty($option_self_hosted) ? 'checked="yes"' : '').' value="1" "/>
											'.esc_html__('Yes, self-hosted installation', 'siteseo').'
										</label>
										
									</td>
								</tr>
								
								<tr>
									<th scope="row">'.esc_html__('Enter your tracking ID', 'siteseo').'</th>
									<td>
										<input type="text" placeholder="'.esc_html__('Enter "example" if you Matomo account URL is "example.matomo.cloud', 'siteseo').'" name="siteseo_options[tracking_id]" value="'.esc_attr($option_matomo_id).'"/>
										'.wp_kses_post('<p class="description">Enter only the host without quotes, such as "example.matomo.cloud" </br> (Cloud) or "matomo.example.com" (self-hosted).</p>').'
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enter your site ID.', 'siteseo').'</th>
									<td>
										<input type="text" placeholder="'.esc_html__('Enter your site ID', 'siteseo').'" name="siteseo_options[site_id]" value="'.esc_attr($option_site_id).'"/>
										<p class="description">'.
										/* translators: placeholders are just <strong> tag */ 
										wp_kses_post(sprintf(__('To find your site ID, visit your %1$s Matomo Cloud %2$s account, go to Websites, and click Manage. The <br/>"Site ID" will be displayed on the right side.', 'siteseo'), '<strong>', '</strong>')).'</p>
										<p class="description">'.
										/* translators: placeholder is just <br> tag */ 
										wp_kses_post(sprintf(__('For self-hosted installations, navigate to your Matomo administration, then go to Settings, Websites, %1$s and Manage. From the list of websites, locate the "ID" line.', 'siteseo'), '<br/>')).'<p>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Track visitors across all subdomains', 'siteseo').'</th>
									<td>
										<label>
										<input type="checkbox" name="siteseo_options[track_visitors]" '.(!empty($option_sub_domain) ? 'checked="yes"' : '').' value="1"/>
										'.esc_html__('Monitor one domain along with its subdomains on the same website.','siteseo') .'
										<p class=description">'.esc_html__('If a visitor visits x.example.com and y.example.com, they will be counted as a single unique visitor.', 'siteseo').'</p>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Prepend the site domain.', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[site_domain]" '.(!empty($option_site_domain) ? 'checked="yes"' : '').' value="1" />
											'.esc_html__('Add the site domain before the page title when tracking', 'siteseo').'
											<p class="description">For example, if someone visits the About page on blog.example.com, it will be recorded as "blog / About".<br/> This provides a simple way to get an overview of your traffic by subdomain.</p>`
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Track users with JavaScript disabled.', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[track_users]" '.(!empty($option_no_js) ? 'checked="yes"' : '').' value="1" />
											'. esc_html__('Track users with JavaScript disabled', 'siteseo').'
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enables cross domain linking', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[enable_cross_domains]" '.(!empty($option_enable_corss_domain) ? 'checked="yes"' : '').' value="1" />
											'.esc_html__('Enables cross domain linking', 'siteseo').'
										</label>
										<p class="description">'.esc_html__('By default, the visitor ID, which uniquely identifies each visitor, is stored in the browser first-party cookies. These cookies can only be accessed by pages on the same domain.', 'siteseo').'</p> 
										<p class="description">'.esc_html__('Enabling cross-domain tracking allows you to monitor all actions and pageviews of a specific visitor within the same session, even when they visit pages across different domains.', 'siteseo').'</p> 
										<p class="description">'.esc_html__('When a user clicks on a link to one of your site alias URLs, a URL parameter, <code>pk_vid</code>, will be appended, forwarding the Visitor ID.', 'siteseo').'</p>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Cross domain', 'siteseo').'</th>
									<td>
										<input type="text" name="siteseo_options[corss_domains]" value="'.esc_attr($option_cross_domain_sites).'" placeholder="'.esc_attr('Enter your domains: siteseo.io,sub.siteseo.io,sub2.siteseo.io').'"/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">'.esc_html__('Enable DoNotTrack detection', 'siteseo').'</th>
									<td>
										<input type="checkbox" name="siteseo_options[enable_donottack]" '.(!empty($options_matomo_dtn) ? 'checked="yes"' : '').' value="1"/>
										'.esc_html__('Activate client-side Do Not Track detection.', 'siteseo').'
										<p class="description">'.esc_html__('Tracking requests will be blocked if visitors opt out of being tracked.', 'siteseo').'</p>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Disable all tracking cookies.', 'siteseo').'</th>
									<td>
										<input type="checkbox" name="siteseo_options[disabled_cookies]" '.(!empty($option_no_cookies) ? 'checked="yes"' : '').' value="1" />
										'.esc_html__('Disables all first-party cookies. Any existing Matomo cookies for this site will be deleted on the next page view.', 'siteseo').'
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Download & Outlink tracking.', 'siteseo').'</th>	
									<td>
										<input type="checkbox" name="siteseo_options[outlink_tracking]" '.(!empty($options_link_tracking) ? 'checked="yes"' : '').' value="1" />
										'.esc_html__('Enabling Download & Outlink tracking','siteseo').'
										<p class="description">By default, files with any of these extensions will be treated as a "download" in the Matomo interface.<p>
										<div class="siteseo-styles pre"><pre>7z|aac|arc|arj|apk|asf|asx|avi|bin|bz|bz2|csv|deb|dmg|doc|exe|flv|gif|gz|gzip|hqx|jar|jpg|jpeg|js|mp2|mp3|mp4|mpg|mpeg|mov|movie|msi|msp|odb|odf|odg|odp|ods|odt|ogg|ogv| pdf|phps|png|ppt|qt|qtm|ra|ram|rar|rpm|sea|sit|tar|tbz|tbz2|tgz|torrent|txt|wav|wma|wmv|wpd|xls|xml|z|zip</pre></div>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Disable all heatmaps and session recordings.', 'siteseo').'</th>
									<td>
										<input type="checkbox" '.(!empty($options_no_heatmaps) ? 'checked="yes"' : '').' value="1" name="siteseo_options[disabled_heatmaps]" />
										'.esc_html__('Turns off all heatmaps and session recordings.', 'siteseo').'
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table><input type="hidden" name="siteseo_options[matomo_tab]" value="1"/>';
	}

	static function advanced(){
		global $siteseo,$wp_roles;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		if(!isset($wp_roles)){
			$wp_roles = new \WP_Roles();
		}

		//$options = $siteseo->analaytics_settings;
		$options = get_option('siteseo_google_analytics_option_name');

		$option_track_authors = !empty($options['google_analytics_cd_author']) ? $options['google_analytics_cd_author'] : '';
		$option_track_categories = !empty($options['google_analytics_cd_category']) ? $options['google_analytics_cd_category'] : '';
		$option_track_tag = !empty($options['google_analytics_cd_tag']) ? $options['google_analytics_cd_tag'] : '';
		$option_track_post_types = !empty($options['google_analytics_cd_post_type']) ? $options['google_analytics_cd_post_type'] : '';
		$option_logged_user = !empty($options['google_analytics_cd_logged_in_user']) ? $options['google_analytics_cd_logged_in_user'] : '';

		$adavnced_subtabs =[
			'custom-dimensions' => 'Custom Dimensions',
			'Misc' => 'Misc',
		];

		echo '<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<div class="siteseo-container">';
								$is_first = true;
								foreach($adavnced_subtabs as $post_key => $post_val){
									$active_class = $is_first ? 'active' : '';
									echo '<a href="#'.esc_attr($post_key).'" class="'.esc_attr($active_class).'">'.esc_html($post_val).'</a>';
									$is_first = false;
								}
							echo '</div>
						</th>

						<td>
							<h3>'.esc_html__('Advanced settings', 'siteseo').'</h3>
							<div class="siteseo-notice" id="custom-dimensions">
								<span class="dashicons dashicons-info"></span>
								<p>'.esc_html__('All advanced settings are compatible with both Google Analytics and Matomo tracking codes.', 'siteseo').'</p>
							</div>

							<br/>

							<span class="line"></span>
							<h3>'.esc_html__('Custom Dimensions', 'siteseo').'</h3>
							<div class="siteseo_wrap_label"><p class="description">'.esc_html__('Set up your Google Analytics custom dimensions.', 'siteseo').'</p></div>
							<div class="siteseo_wrap_label"><p class="description">'.esc_html__('Custom dimensions and metrics are similar to the default ones in Google Analytics, but you have the flexibility to create your own.', 'siteseo').'</p></div>
							<div class="description"><p class="description">'.esc_html__('Utilize custom dimensions to gather and analyze data that Google Analytics does not track automatically.', 'siteseo').'</p></div>
							<div class="description"><p class="description">'.esc_html__('Remember, you must also configure your custom dimensions in your Google Analytics account. Click the help icon for more information.', 'siteseo').'</p></div>

							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">'.esc_html__('Track Authors', 'siteseo').'</th>
										<td>
											<select name="siteseo_options[track_authors]">
												<option value="none" '.selected($option_track_authors, 'none', false).'>'.esc_html__('None','siteseo').'</option>';
												for($i = 1; $i <= 20; ++$i){
													/* translators: %d represents the custom dimension */
													echo '<option '.selected($option_track_authors, 'dimension' . $i, false).' value="dimension'.esc_attr($i).'">'.sprintf(esc_html__('Custom Dimension #%d', 'siteseo'), esc_html($i)).'</option>';
												}
											echo '</select>
										</td>
									</tr>

									<tr>
										<th scope="row">'.esc_html__('Track Categories', 'siteseo').'</th>
										<td>
											<select name="siteseo_options[track_categories]">
												<option value="none" '.selected($option_track_categories, 'none', false).'>'.esc_html__('None','siteseo').'</option>';
												for($i = 1; $i <= 20; ++$i){
													/* translators: %d represents the custom dimension */
													echo '<option '.selected($option_track_categories, 'dimension' . $i, false).' value="dimension'.esc_attr($i).'">'.sprintf(esc_html__('Custom Dimension #%d', 'siteseo'), esc_html($i)).'</option>';
												}
											echo '</select>
										</td>
									</tr>

									<tr>
										<th scope="row">'.esc_html__('Track Tags', 'siteseo').'</th>
										<td>
											<select name="siteseo_options[track_tags]">
												<option value="none" '.selected($option_track_tag, 'none', false).'>'.esc_html__('None','siteseo').'</option>';
												for($i = 1; $i <= 20; ++$i){
													/* translators: %d represents the custom dimension */
													echo '<option '.selected($option_track_tag, 'dimension' . $i, false).' value="dimension'.esc_attr($i).'">'.sprintf(esc_html__('Custom Dimension #%d', 'siteseo'), esc_html($i)).'</option>';
												}
											echo '</select>
										</td>
									</tr>

									<tr>
										<th scope="row">'.esc_html__('Track Post Types','siteseo').'</th>
										<td>
											<select name="siteseo_options[track_post_types]">
												<option value="none" '.selected($option_track_post_types, 'none', false).'>'.esc_html__('None','siteseo').'</option>';
												for($i = 1; $i <= 20; ++$i){
													/* translators: %d represents the custom dimension */
													echo '<option '.selected($option_track_post_types, 'dimension' . $i, false).' value="dimension'.esc_attr($i).'">'.sprintf(esc_html__('Custom Dimension #%d', 'siteseo'), esc_html($i)).'</option>';
												}
											echo '</select>
										</td>
									</tr>

									<tr>
										<th scope="row">'.esc_html__('Track Logged In Users','siteseo').'</th>
										<td>
											<select name="siteseo_options[track_user]">
												<option value="none" '.selected($option_logged_user, 'none', false).'>'.esc_html__('None','siteseo').'</option>';
												for($i = 1; $i <= 20; ++$i){
													/* translators: %d represents the custom dimension */
													echo '<option '.selected($option_logged_user, 'dimension' . $i, false).' value="dimension'.esc_attr($i).'">'. sprintf(esc_html__('Custom Dimension #%d', 'siteseo'), esc_html($i)).'</option>';
												}
											echo '</select>
										</td>
									</tr>

								</tbody>
							</table>

							<div class="description" id="Misc"><span class="line"></span>
							<h3>'.esc_html__('Misc','siteseo').'</h3>
							<table>
								<tbody class="form-table">
									<tr>
										<th scope="row">'.esc_html__('Exclude user roles from tracking (Google Analytics and Matomo)','siteseo').'</th>
										<td>';
										foreach($wp_roles->get_names() as $key => $value){
											$select = isset($options['google_analytics_roles'][$key]);

											echo '<p>
												<label>
													<input name="siteseo_options[misc_roles]['.esc_attr($key).']" type="checkbox" '.(!empty($select) ? 'checked="yes"' : 'value="1"').'/>
													<strong>'. esc_html($value) .'</strong> (<em> '. esc_html(translate_user_role($value,  'default')) .'</em>)
												</label>
											</p>';	
										}
										echo '</td>
									</tr>
								</tbody>
							</table>
							</div>
						</td>
					</tr>
				</tbody>
			</table><input type="hidden" name="siteseo_options[advanced_tab]" value="1"/>';
	}
	
	static function clarity(){
		global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		//$options = $siteseo->analaytics_settings;
		$options = get_option('siteseo_google_analytics_option_name');

		$option_enable_clarity = !empty($options['google_analytics_clarity_enable']) ? $options['google_analytics_clarity_enable'] : '';
		$option_project_id = !empty($options['google_analytics_clarity_project_id']) ? $options['google_analytics_clarity_project_id'] : '';

		echo '<h3 class="siteseo-tabs">'.esc_html__('Microsoft Clarity', 'siteseo').'</h3>
		<p class="description">'.esc_html__('Use Microsoft Clarity to capture session recordings, access instant heatmaps, and gain powerful insights for free. Understand how users interact with your site to enhance the user experience and boost conversions.', 'siteseo').'</p>

		<div class="siteseo-notice">
            		<span class="dashicons dashicons-info"></span>
           		 <p>'. 
				 /* translators: %s represents the microsoft clarity api url */
				 wp_kses_post(sprintf(__('Create your first Microsoft Clarity project %1$shere%2$s.', 'siteseo'), '<a href="https://clarity.microsoft.com/" target="_blank">', '</a>')) .'</p>
		</div>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Enable Microsoft Clarity','siteseo').'</th>
					<td>
						<input type="checkbox" name="siteseo_options[microsoft_clarity]" '.(!empty($option_enable_clarity) ? 'checked="yes"' : ''). ' value="1">
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Enter your Clarity project ID', 'siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[project_id]" placeholder="'.esc_attr__('Enter your Project Id', 'siteseo').'" value="'.esc_attr($option_project_id).'" >
						<p><span class="dashicons dashicons-external"></span>
							<a href="https://siteseo.io/docs/analytics/find-my-microsoft-clarity-project-id/" target="_blank">
								'.esc_html__('Find your project ID', 'siteseo').'
							</a>
							</span>
						</p>
					</td>
				</tr>

			</tbody>
		</table><input type="hidden" name="siteseo_options[clarity_tab]" value="1" />';

	}

	static function google_anlytics(){
		global $siteseo;
		
        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		$options = get_option('siteseo_google_analytics_option_name');
		//$options = $siteseo->analaytics_settings;

		$option_enable_anaytics = !empty($options['google_analytics_enable']) ? $options['google_analytics_enable'] : '';
		$option_anaytics_id = !empty($options['google_analytics_ga4']) ? $options['google_analytics_ga4'] : '';
		$option_enable_optimize = !empty($options['google_analytics_link_tracking_enable']) ? $options['google_analytics_link_tracking_enable'] : '';
		$option_enable_download_tracking= !empty($options['google_analytics_download_tracking_enable']) ? $options['google_analytics_download_tracking_enable'] : '';
 		$option_download_tracking = !empty($options['google_analytics_download_tracking']) ? $options['google_analytics_download_tracking'] : '';
		$option_affiliate_tracking_enable = !empty($options['google_analytics_affiliate_tracking_enable']) ? $options['google_analytics_affiliate_tracking_enable'] : '';
		$option_affiliate_tracking = !empty($options['google_analytics_affiliate_tracking']) ? $options['google_analytics_affiliate_tracking'] : '';
		$option_phone_tracking = !empty($options['google_analytics_phone_tracking']) ? $options['google_analytics_phone_tracking'] : '';

		$option_container_id = !empty($options['google_analytics_optimize']) ? $options['google_analytics_optimize'] : '';
		$option_conversion_id = !empty($options['google_analytics_ads']) ? $options['google_analytics_ads'] : '';
		$option_ip_anonymization = !empty($options['google_analytics_ip_anonymization']) ? $options['google_analytics_ip_anonymization'] : '';
		$option_links_attribution = !empty($options['google_analytics_link_attribution']) ? $options['google_analytics_link_attribution'] : '';
		$option_domain_tracking = !empty($options['google_analytics_cross_enable']) ? $options['google_analytics_cross_enable'] : '';
		$option_cross_domain = !empty($options['google_analytics_cross_domain']) ? $options['google_analytics_cross_domain'] : '';
		$option_enable_remarketing = !empty($options['google_analytics_remarketing']) ? $options['google_analytics_remarketing'] : '';
 		
		$google_analytics_fileds = [
			'general-settings' =>'General',
			'tracking-settings'=>'Tracking',
			'events-settings' => 'Events'
		];

		echo '<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
					<div class="siteseo-container">';
						$is_first = true;
						foreach($google_analytics_fileds as $post_key => $post_val){
							$active_class = $is_first ? 'active' : '';
							echo '<a href="#'.esc_attr($post_key).'" class="'.esc_attr($active_class).'">'.esc_html($post_val).'</a>';
							$is_first = false;
						}
				echo '</div></th>
				<td>
					<div id="general-settings">
						<h3>'.esc_html__('Google Anlytics', 'siteseo').'</h3>
						<div class="siteseo_wrap_label"><p class="description">'.esc_html__('Connect your Google Analytics to your website. The tracking code will be automatically added to your site.', 'siteseo') .'</p></div>
						<span class="line"></span>
						<div class="siteseo_wrap_label"><p class="'.esc_html__('description">Link your Google Analytics to your website. The tracking code will be automatically added to your site', 'siteseo').'</p></div>
						<span class="line"></span>
						<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">'.esc_html__('General', 'siteseo').'</th>
										<td></td>
									</tr>

									<tr>
										<th scope="row">'.esc_html__('Enable Google Analytics tracking', 'siteseo').'</th>
										<td>
											<label><input type="checkbox" name="siteseo_options[google_anlytics_tracking]" '.(!empty($option_enable_anaytics) ? 'checked="yes"' : '') . ' value="1"/> ' . esc_html__('Activate Google Analytics tracking using the Global Site Tag (gtag.js).', 'siteseo') . '</label>
										</td>
									</tr>

									<tr>
										<th scope="row">'.esc_html__('Enter your measurement ID (GA4)', 'siteseo').'</th>
										<td>
											<input type="text" placeholder="'.esc_attr__('Enter your measurement ID (G-XXXXXXXXXX)','siteseo').'" name="siteseo_options[anlytics_measurement_id]" value="'.esc_attr($option_anaytics_id).'">
											<p>
												<span class="dashicons dashicons-external"></span>
												<a href="https://support.google.com/analytics/answer/9539598?hl=en&ref_topic=9303319" target="_blank">'.esc_html__('Find your measurement ID', 'siteseo').'</a>
											</p>
										</td>
									</tr>
								</tbody>
						</table>
					</div></div>
					
					<div id="tracking-settings">
						<span class="line"></span>
						<h3>'.esc_html__('Tracking','siteseo').'</h3>
						<p class="description">'.esc_html__('Set up your Google Analytics tracking code.', 'siteseo').'</P>
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">'.esc_html__('Enable Google Optimize.', 'siteseo').'</th>
									<td>
										<label>
											<input type="text" name="siteseo_options[container_id]" placeholder="'.esc_attr__('Enter your Google Optimize container ID.', 'siteseo').'" value="'.esc_attr($option_container_id).'"/>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enable Google Ads','siteseo').'</th>	
									<td>
										<label>
											<input type="text" placeholder="'.esc_attr__('Enter your Google Ads conversion ID (eg: AW-123456789).', 'siteseo').'" name="siteseo_options[conversion_id]" value="'.esc_attr($option_conversion_id).'"/>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enable remarketing, demographics, and interests reporting', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[enable_remarketing]" '.(!empty($option_enable_remarketing) ? 'checked="yes"' : '').' value="1"/>
											'. esc_html__('Enable remarketing, demographics, and interests reporting', 'siteseo').'
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enable IP Anonymization', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[ip_anonymiza]" '.(!empty($option_ip_anonymization) ? 'checked="yes"' : '').'/>
											'. esc_html__('Enable IP Anonymization', 'siteseo') .'
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enhanced Link Attribution', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[link_attribution]" '.(!empty($option_links_attribution) ? 'checked="yes"' : '').'/>
											'. esc_html__('Enhanced Link Attribution', 'siteseo').'
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enable cross-domain tracking', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[domain_tracking]" '.(!empty($option_domain_tracking) ? 'checked="yes"' : '').' />
											'. esc_html__('Enable cross-domain tracking', 'siteseo').'
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Cross domains', 'siteseo').'</th>
									<td>
										<input type="text" placeholder="Enter your domains: siteseo.io,sub.siteseo.io,sub2.siteseo.io" name="siteseo_options[cross_domain]" value="'.esc_attr($option_cross_domain).'" />
									</td>
								</tr>

							</tbody>
						<table>
					</div>
					
					<div id="events-settings">
						<span class="line"></span>
						<h3>'.esc_html__('Events', 'siteseo').'</h3>
						<P class="description">'.esc_html__('Track events in Google Analytics', 'siteseo').'</p>
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">'.esc_html__('Enable Google Optimize', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[google_optimize]" '.(!empty($option_enable_optimize) ? 'checked="yes"' : ''). ' value="1">' . esc_html__(' Enable external links tracking', 'siteseo') . '
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row">'.esc_html__('Enable downloads tracking (eg: PDF, XLSX, DOCX...)', 'siteseo').'</th>
									<td>
										<label>
											<input type="checkbox" name="siteseo_options[enable_download_tracking]" '.(!empty($option_enable_download_tracking) ? 'checked="yes"' : '').' value="1"> '.esc_html__('Enable download tracking', 'siteseo').'
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row">'.esc_html__('Track downloads clicks', 'siteseo').'</th>
									<td>
										<input type="text" placeholder="pdf|docs|pptx|zip" name="siteseo_options[track_downlaods]" value="'.esc_attr($option_download_tracking).'"/>
										<p class="description">'.esc_html__('Separate each file type extensions with a pipe "|"','siteseo').'</p>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Enable affiliate/outbound links tracking (eg: aff, go, out, recommends)', 'siteseo').'</th>
									<td>
										<label>
										<input type="checkbox" name="siteseo_options[aff_tracking_enable]" '.(!empty($option_affiliate_tracking_enable ) ? 'checked="yes"' : '').' value="1"/>
										'. esc_html__('Enable affiliate/outbound tracking','siteseo') .'</label>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Track affiliate/outbound links','siteseo').'</th>
									<td>
										<input type="text" name="siteseo_options[aff_tracking]" placeholder="aff|go|out" value="'.esc_attr($option_affiliate_tracking).'"/>
										<p class="description">'.esc_html__('Separate each keyword with a pipe "|"', 'siteseo').'</p>
									</td>
								</tr>

								<tr>
									<th scope="row">'.esc_html__('Track phone links','siteseo').'</th>
									<td>
										<input type="checkbox" name="siteseo_options[track_phones]" '.(!empty($option_phone_tracking ) ? 'checked="yes"' : '') . ' value="1"/>
										'.esc_html__(' Enable tracking of "tel:" links' , 'siteseo'). '
										<div class="siteseo-styles pre"><pre>'.esc_html('<a href="tel:+33123456789">').'</pre></div>
									</td>
								</tr>
							</tbody>
						</table
					</div>
				</td>
			</tbody>
		</table>
		<input type="hidden" name="siteseo_options[analytics_tab]" value="1"/>';

	}

	static function save_settings(){

		global $siteseo;

		check_admin_referer('siteseo_analytics_settings');

		if(!siteseo_user_can('manage_analytics') || !is_admin()){
			return;
		}

		$options = [];
		
		if(empty($_POST['siteseo_options'])){
			return;
		}
		
		if(isset($_POST['siteseo_options']['analytics_tab'])){
			$options['google_analytics_enable'] = isset($_POST['siteseo_options']['google_anlytics_tracking']);
			$options['google_analytics_ga4'] = isset($_POST['siteseo_options']['anlytics_measurement_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['anlytics_measurement_id'])) : '';
			$options['google_analytics_link_tracking_enable'] = isset($_POST['siteseo_options']['google_optimize']);
			$options['google_analytics_download_tracking_enable'] = isset($_POST['siteseo_options']['enable_download_tracking']);
			$options['google_analytics_download_tracking'] = isset($_POST['siteseo_options']['track_downlaods']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['track_downlaods'])) : '';
			$options['google_analytics_affiliate_tracking_enable'] = isset($_POST['siteseo_options']['aff_tracking_enable']);
			$options['google_analytics_affiliate_tracking'] = isset($_POST['siteseo_options']['aff_tracking']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['aff_tracking'])) : '';
			$options['google_analytics_phone_tracking'] = isset($_POST['siteseo_options']['track_phones']);
			$options['google_analytics_optimize'] = isset($_POST['siteseo_options']['container_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['container_id'])) : '';
			$options['google_analytics_ads'] = isset($_POST['siteseo_options']['conversion_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['conversion_id'])) : '';
			$options['google_analytics_remarketing'] = isset($_POST['siteseo_options']['enable_remarketing']);
			$options['google_analytics_ip_anonymization'] = isset($_POST['siteseo_options']['ip_anonymiza']);
			$options['google_analytics_link_attribution'] = isset($_POST['siteseo_options']['link_attribution']);
			$options['google_analytics_cross_enable'] = isset($_POST['siteseo_options']['domain_tracking']);
			$options['google_analytics_cross_domain'] = isset($_POST['siteseo_options']['cross_domain']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cross_domain'])) : '';
		}
		
		if(isset($_POST['siteseo_options']['clarity_tab'])){
			$options['google_analytics_clarity_enable'] = isset($_POST['siteseo_options']['microsoft_clarity']);
			$options['google_analytics_clarity_project_id'] = isset($_POST['siteseo_options']['project_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['project_id'])) : '';
		}
		
		if(isset($_POST['siteseo_options']['matomo_tab'])){
			$options['google_analytics_matomo_enable'] = isset($_POST['siteseo_options']['enable_matomo']);
			$options['google_analytics_matomo_self_hosted'] = isset($_POST['siteseo_options']['self_hosted']);
			$options['google_analytics_matomo_id'] = isset($_POST['siteseo_options']['tracking_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['tracking_id'])) : '';
			$options['google_analytics_matomo_site_id'] = isset($_POST['siteseo_options']['site_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['site_id'])) : '';
			$options['google_analytics_matomo_subdomains'] = isset($_POST['siteseo_options']['track_visitors']);
			$options['google_analytics_matomo_site_domain'] = isset($_POST['siteseo_options']['site_domain']);
			$options['google_analytics_matomo_cross_domain'] = isset($_POST['siteseo_options']['enable_cross_domains']);
			$options['google_analytics_matomo_no_js'] = isset($_POST['siteseo_options']['track_users']);
			$options['google_analytics_matomo_cross_domain_sites'] = isset($_POST['siteseo_options']['corss_domains']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['corss_domains'])) : '';
			$options['google_analytics_matomo_no_cookies'] = isset($_POST['siteseo_options']['disabled_cookies']);
			$options['google_analytics_matomo_link_tracking'] = isset($_POST['siteseo_options']['outlink_tracking']);
			$options['google_analytics_matomo_no_heatmaps'] = isset($_POST['siteseo_options']['disabled_heatmaps']);
			$options['google_analytics_matomo_dnt'] = isset($_POST['siteseo_options']['enable_donottack']);
		}
		
		if(isset($_POST['siteseo_options']['advanced_tab'])){
		
			$options['google_analytics_cd_author'] = isset($_POST['siteseo_options']['track_authors']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['track_authors'])) : '';
			$options['google_analytics_cd_category'] = isset($_POST['siteseo_options']['track_categories']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['track_categories'])) : '';
			$options['google_analytics_cd_tag'] = isset($_POST['siteseo_options']['track_tags']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['track_tags'])) : '';
			$options['google_analytics_cd_post_type'] = isset($_POST['siteseo_options']['track_post_types']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['track_post_types'])) : '';
			$options['google_analytics_cd_logged_in_user'] = isset($_POST['siteseo_options']['track_user']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['track_user'])) : '';
			
			// mics roles
			if(isset($_POST['siteseo_options']['misc_roles'])){
				$options['google_analytics_roles'] = map_deep(wp_unslash($_POST['siteseo_options']['misc_roles']), 'sanitize_text_field');
			}
		}
		
		if(isset($_POST['siteseo_options']['custom_tracking_tab']) && current_user_can('unfiltered_html')){
			// NOTE: These options can not be sanitized as we need user to be able to add some JS code, so we have added a capability check which only a super admin can have.
			$options['google_analytics_other_tracking'] = isset($_POST['siteseo_options']['head_tracking']) ? wp_unslash($_POST['siteseo_options']['head_tracking']) : '';
			$options['google_analytics_other_tracking_body'] = isset($_POST['siteseo_options']['body_tracking']) ? wp_unslash($_POST['siteseo_options']['body_tracking']) : '';
			$options['google_analytics_other_tracking_footer'] = isset($_POST['siteseo_options']['footer_tracking']) ? wp_unslash($_POST['siteseo_options']['footer_tracking']) : '';
		}
		
		if(isset($_POST['siteseo_options']['cookies_tab'])){
			
			$options['google_analytics_hook'] = isset($_POST['siteseo_options']['cookie_pos']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cookie_pos'])) : '';
			$options['google_analytics_disable'] = isset($_POST['siteseo_options']['opt_tracking']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['opt_tracking'])) : '';
			$options['google_analytics_half_disable'] = isset($_POST['siteseo_options']['half_disable']);
			$options['google_analytics_opt_out_edit_choice'] = isset($_POST['siteseo_options']['opt_edit_choices']);
			$options['google_analytics_opt_out_msg'] = isset($_POST['siteseo_options']['opt_msg']) ? wp_kses_post(wp_unslash($_POST['siteseo_options']['opt_msg'])) : 'We use cookies to enhance your experience.';
			$options['google_analytics_opt_out_msg_ok'] = isset($_POST['siteseo_options']['opt_msg_ok']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['opt_msg_ok'])) : 'Accept';
			$options['google_analytics_opt_out_msg_edit'] = isset($_POST['siteseo_options']['opt_edit_btn']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['opt_edit_btn'])) : 'Manage cookies';
			$options['google_analytics_opt_out_msg_close'] = isset($_POST['siteseo_options']['opt_close']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['opt_close'])) : 'X';
			$options['google_analytics_cb_exp_date'] = isset($_POST['siteseo_options']['cd_exp_date']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cd_exp_date'])) : '30';
			$options['google_analytics_cb_pos'] = isset($_POST['siteseo_options']['cd_pos']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cd_pos'])) : 'center';
			$options['google_analytics_cb_txt_align'] = isset($_POST['siteseo_options']['cd_txt_align']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cd_txt_align'])) : 'center';
			$options['google_analytics_cb_width'] = isset($_POST['siteseo_options']['cd_width']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cd_width'])) : '100%';
			$options['google_analytics_cb_scheme'] = isset($_POST['siteseo_options']['google_analytics_cb_scheme']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['google_analytics_cb_scheme'])) : '';

			// Colors
			$options['google_analytics_cb_backdrop'] = isset($_POST['siteseo_options']['cd_backdrop']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cd_backdrop'])) : '';
			$options['google_analytics_cb_backdrop_bg'] = isset($_POST['siteseo_options']['backdrop_bg']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['backdrop_bg'])) : '';
			$options['google_analytics_cb_bg'] = isset($_POST['siteseo_options']['cookiesbar_bg']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cookiesbar_bg'])) : '';
			$options['google_analytics_cb_txt_col'] = isset($_POST['siteseo_options']['cookiebar_txt']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['cookiebar_txt'])) : '';
			$options['google_analytics_cb_lk_col'] = isset($_POST['siteseo_options']['line_co']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['line_co'])) : '';
			$options['google_analytics_cb_btn_bg'] = isset($_POST['siteseo_options']['primary_btn_bg']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['primary_btn_bg'])) : '';
			$options['google_analytics_cb_btn_bg_hov'] = isset($_POST['siteseo_options']['primary_btn_bg_hov']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['primary_btn_bg_hov'])) : '';
			$options['google_analytics_cb_btn_col'] = isset($_POST['siteseo_options']['primary_btn_txt']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['primary_btn_txt'])) : '';
			$options['google_analytics_cb_btn_col_hov'] = isset($_POST['siteseo_options']['primary_btn_txt_hov']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['primary_btn_txt_hov'])) : '';
			$options['google_analytics_cb_btn_sec_bg'] = isset($_POST['siteseo_options']['sec_btn_bg']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['sec_btn_bg'])) : '';
			$options['google_analytics_cb_btn_sec_bg_hov'] = isset($_POST['siteseo_options']['sec_btn_bg_hov']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['sec_btn_bg_hov'])) : '';
			$options['google_analytics_cb_btn_sec_col'] = isset($_POST['siteseo_options']['sec_btn_txt']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['sec_btn_txt'])) : '';
			$options['google_analytics_cb_btn_sec_col_hov'] = isset($_POST['siteseo_options']['sec_btn_txt_hov']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['sec_btn_txt_hov'])) : '';
		}

		update_option('siteseo_google_analytics_option_name', $options);
	}

}
