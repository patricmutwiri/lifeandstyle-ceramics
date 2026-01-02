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

class Instant{

	static function menu(){
		global $siteseo;

		$indexing_toggle = isset($siteseo->setting_enabled['toggle-instant-indexing']) ? $siteseo->setting_enabled['toggle-instant-indexing'] : '';
		$nonce = wp_create_nonce('siteseo_toggle_nonce');

		$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tab_siteseo_general';

		$instant_subtabs = [
			'tab_siteseo_general' => esc_html__('General', 'siteseo'),
			'tab_siteseo_settings' => esc_html__('Settings', 'siteseo'),
			'tab_siteseo_history' => esc_html__('History', 'siteseo'),
		];

		echo '<div id="siteseo-root">';
		Util::admin_header();

		echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">';
		wp_nonce_field('siteseo_instant_indexing');

		Util::render_toggle('Instant Indexing - SiteSEO', 'indexing_toggle', $indexing_toggle, $nonce);

		echo '<div id="siteseo-tabs" class="wrap">
		<div class="siteseo-nav-tab-wrapper">';

		foreach($instant_subtabs as $tab_key => $tab_caption){
			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo '<a id="'.esc_attr($tab_key).'-tab" class="siteseo-nav-tab'.esc_attr($active_class).'" data-tab="'.esc_attr($tab_key).'">'.esc_html($tab_caption).'</a>';
		}

		echo '</div>
		<div class"tab-content-wrapper">
		<div class="siteseo-tab'.($current_tab == 'tab_siteseo_general' ? ' active' : '').'" id="tab_siteseo_general" style="display: none;">';
		self::general();
		echo '</div>     
		<div class="siteseo-tab'.($current_tab == 'tab_siteseo_settings' ? ' active' : '').'" id="tab_siteseo_settings" style="display: none;">';
		self::settings();
		echo '</div>
		<div class="siteseo-tab'.($current_tab == 'tab_siteseo_history' ? 'active' : '').'" id="tab_siteseo_history" style="display : none;">';
		self::history();
		echo '</div>
		</div>';
		Util::submit_btn();
		echo '</form></div>';

	}

	static function general(){
		global $siteseo;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		$options = get_option('siteseo_instant_indexing_option_name');
		//$options = $siteseo->instant_settings;

		$option_engines = !empty($options['engines']) ? $options['engines'] : '';
		$option_search_engine_google = !empty($option_engines['google']) ? $option_engines['google'] : '';
		$option_search_engine_bing = !empty($option_engines['bing']) ? $option_engines['bing'] : '';
		$option_action = !empty($options['instant_indexing_google_action']) ? $options['instant_indexing_google_action'] : '';
		$option_manual_batch = !empty($options['instant_indexing_manual_batch']) ? $options['instant_indexing_manual_batch'] : '';

		echo '<h3 class="siteseo-tabs">'.esc_html__('Instant Indexing','siteseo').'</h3>
		<div class="siteseo_wrap_label">
			<p class="description">'.esc_html__('Utilize the Indexing API to inform Google and Bing about updates or removals of pages from their indexes. The process may take a few minutes. You can submit URLs in batches of up to 100 (maximum 200 requests per day for Google).','siteseo').'</p>
		</div>

		<div class="siteseo-notice">
			<span class="dashicons dashicons-info"></span>
		    <div><h3>'.esc_html__('How does this work?', 'siteseo').'</h3>
			<ol>
			<li>'.
			/* translators: placeholders are just <strong> tag */ 
			wp_kses_post(sprintf(__('Setup your Google / Bing API keys from the %1$s Settings %2$s tab', 'siteseo'), '<strong>', '</strong>')).'</li>
			<li>'.
			/* translators: placeholders are just <strong> tag */ 
			wp_kses_post(sprintf(__('%1$s Enter the URLs %2$s you want to index in the field below.', 'siteseo'), '<strong>', '</strong>')).'</li>
				<li><strong>'.wp_kses_post(__('Save changes', 'siteseo')).'</strong></li>
			<li>'.
			/* translators: placeholders are just <strong> tag */ 
			wp_kses_post(sprintf(__('Click %1$s Submit URLs to Google & Bing  %2$s', 'siteseo'), '<strong>', '</strong>')).'</li>
			</ol>
			</div>
		</div>

		<table class="form-table">
		    <tbody>

		    	<tr>
				<th scope="row">'.esc_html__('select search engines','siteseo').'</th>
				<td> 
					<div class="siteseo_wrap_label"><label for="siteseo_search_engines">
						<input id="siteseo_search_engines" name="siteseo_options[search_engine_google]" type="checkbox"' . (!empty($option_search_engine_google) ? 'checked="yes"' : '') . ' value="1"/>'.esc_html__('Google', 'siteseo') . 
				            '</label></div>
					    <label for="siteseo_search_engines">
					    	<input id="siteseo_search_engines" name="siteseo_options[search_engine_bing]" type="checkbox"' . (!empty($option_search_engine_bing) ? 'checked="yes"' : '') . ' value="1"/>'.esc_html__('Bing', 'siteseo') . 
					    '</label>
				</td>
			</tr>

			<tr>
				<th scope="row">'.esc_html__('Which action to run for Google?', 'siteseo') .'</th>
				<td>
					<div class="siteseo_wrap_label">
						<label>
							<input id="siteseo_update_urls" name="siteseo_options[instant_indexing_actions]" type="radio" value="update_urls" '.checked($option_action, 'update_urls', false).'/>
							'.esc_html__('Update URLs', 'siteseo').'
						</label>
					</div>
					<div class="siteseo_wrap_label">
						<label>
							<input id="siteseo_remove_urls" name="siteseo_options[instant_indexing_actions]" type="radio" value="remove_urls" '.checked($option_action, 'remove_urls', false).'/>
							'.esc_html__('Remove URLs (the URL must return a 404 or 410 status code, or the page must include the <meta name="robots" content="noindex" /> meta tag).', 'siteseo').'
						</label>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">'.esc_html__('Submit URLs for indexing','siteseo').'</th>
				<td>
					<textarea rows="20" name="siteseo_options[instant_indexing_batch]" placeholder="'.esc_html__('Submit one URL per line for search engine submission (maximum of 100 URLs).','siteseo').'">'.esc_attr($option_manual_batch).'</textarea>
				</td>
			</tr>

			<tr>
				<th scope="row"></th>
				<td>
					<button id="siteseo-submit-urls-button" class="btn btnSecondary">'.esc_html__('Submits URLs to Google & Bing', 'siteseo').'</button>
				</td><div style="position:absolute;margin-top:52.5%;margin-left:38%;" class="spinner"></div>
			</tr>
				
			<tr>
				<th scope="row"></th>
				<td>
					<div id="url-submitter-response"></div>
				</td>
			</tr>

		</tbody>
		</table><input type="hidden" name="siteseo_options[general]" value="1"/>';
	}

	static function settings(){
		global $siteseo,$docs;

		if(!empty($_POST['submit'])){
			self::save_settings();
		}

		$docs['instant_indexing']['api'] = 'https://console.cloud.google.com/apis/library/indexing.googleapis.com?hl=en';
		$docs['instant_indexing']['google'] = 'https://siteseo.io/docs/api-cli-dev/use-google-instant-indexing-api-with-siteseo-pro/';

		//$options = $siteseo->instant_settings;
		$options = get_option('siteseo_instant_indexing_option_name');

		$option_google_api_key = !empty($options['instant_indexing_google_api_key']) ? $options['instant_indexing_google_api_key'] : '';
		$option_bing_api_key = !empty($options['instant_indexing_bing_api_key']) ? $options['instant_indexing_bing_api_key'] : '';
		$option_auto_url_submission = !empty($options['instant_indexing_automate_submission']) ? $options['instant_indexing_automate_submission'] : '';

		echo '<h3 class="siteseo-tabs">'.esc_html__('Settings','siteseo').'</h3>
		<table class="form-table">
		<tbody>
		<tr>
		    <th scope="row">'.esc_html__('Instant Indexing Google API Key','siteseo').'</th>
		    <td>    
		        <textarea name="siteseo_options[google_api_key]" rows="12" placeholder="'.esc_html__('Paste your Google Json key file here','siteseo').'">'.esc_html($option_google_api_key).'</textarea>
		    </td>
		</tr>

		<tr>
			<th scope="row">'.esc_html__('Instant Indexing Bing API Key', 'siteseo').'</th>
			<td>
				<input type="text" id="bing-api-key" name="siteseo_options[bing_api_key]" placeholder="'.esc_html__('Enter your Bing Instant Indexing API', 'siteseo').'" value="'.esc_attr($option_bing_api_key).'">
				<button type="button" id="siteseo-generate-api-key-btn" class="btn btnSecondary">'.esc_html__('Generate key', 'siteseo').'</button>
				<p class="description">'.esc_html__('The Bing Indexing API key is generated automatically. Click Generate Key if you need to recreate it or if it missing.', 'siteseo') .'</p>
				<p class="description">'.esc_html__('A key should look like this: YjI4MGQxZmU0NWM1NGY2ZGIxMDk5M2VlYTAxMTUyODI=', 'siteseo') .'</p>
			</td>
		</tr>

		<tr>
		    <th scope="row">'.esc_html__('Automate URL Submission','siteseo').'</th>
		    <td> 
		        <label for="siteseo_search_engines">
		            <input id="siteseo_search_engines" name="siteseo_options[auto_submission]" type="checkbox"'.(!empty($option_auto_url_submission) ? 'checked="yes"' : '').' value="1"/>'.esc_html__('Activate automatic URL submission for the IndexNow API.', 'siteseo') . 
		        '</label>
		        <div class="siteseo_wrap_label">
		            <p class="description">'.esc_html__('Inform search engines via the IndexNow protocol whenever a post is created, updated, or removed.','siteseo').'</p>
		        </div>
		    </td>
		</tr>

		</tbody>
		</table><input type="hidden" name="siteseo_options[setting_tab]" value="1"/>';
	}
	
	static function history(){
		global $siteseo;
		
		$options = get_option('siteseo_instant_indexing_option_name');
		$indexing_history = !empty($options['indexing_history']) ? $options['indexing_history'] : '';
		
		echo'<h3 class="siteseo-tabs">'.esc_html__('History', 'siteseo').'</h3>
		<div class="siteseo_wrap_label">
			<p class="description">'.esc_html__('Most Recent 10 Indexing API Requests.', 'siteseo').'</p>
		</div>
		
		<table class="wp-list-table widefat fixed striped siteseo-history-table">
			<thead><tr>
				<th>'.esc_html__('Time & Date', 'siteseo').'</th>
				<th>'.esc_html__('URLs', 'siteseo').'</th>
				<th>'.esc_html__('Google Response', 'siteseo').'</th>
				<th>'.esc_html__('Bing Response', 'siteseo').'</th>
			</tr></thead>
			<tbody>';
			
			if(empty($indexing_history)){
				echo'<tr>
					<td>'.esc_html__('No submissions yet.', 'siteseo').'</td>
				</tr></tbody></table><br/><br/>';
				
				return;
			}
			
			foreach($indexing_history as $history){
				echo'<tr>
					<td>'.esc_html(date_i18n('Y-m-d H:i:s', $history['time'])).'</td>
					<td>'.esc_html(implode(', ', $history['urls'])).'</td>
					<td>'.(isset($history['google_status_code']) ? esc_html($history['google_status_code']) : 'N/A') . (isset($history['source']) && $history['source'] === 'auto' ? esc_html(' ( Auto )') : '') .'</td>
					<td>'.(isset($history['bing_status_code']) ? esc_html($history['bing_status_code']) : 'N/A') . (isset($history['source']) && $history['source'] === 'auto' ? esc_html(' ( Auto )') : '') . 
					'</td>

				</tr>';
			}
			
			echo'</tr>
			</tbody></table><br/>
			
			<tr>
				<td>
					<button id="siteseo-clear-history" class="btn btnSecondary">'.esc_html__('Clean History', 'siteseo').'</button>
				</td>
			</tr><br/><br/>
			
			<a class="siteseo-show-details">'.esc_html__('Response code guide', 'siteseo').'<span class="dash-icon dashicons dashicons-arrow-down-alt2"></span></a>
			<div class="siteseo-response-code-table">
			<table class="wp-list-table widefat fixed striped siteseo-history-table">
				<thead>
					<tr>
					<th>'.esc_html__('Response Code', 'siteseo').'</th>
					<th>'.esc_html__('Response Message', 'siteseo').'</th>
					<th>'.esc_html__('Reason', 'siteseo').'</th>
					</tr>
				</thead>
				
				<tr>
					<td>'.esc_html__('200', 'siteseo').'</td>
					<td>'.esc_html__('Ok', 'siteseo').'</td>
					<td>'.esc_html__('URLs submitted successfully.', 'siteseo').'</td>
				</tr>
				
				<tr>
					<td>'.esc_html__('202', 'siteseo').'</td>
					<td>'.esc_html__('Accepted', 'siteseo').'</td>
					<td>'.esc_html__('URL received. IndexNow key validation pending.', 'siteseo').'</td>
				</tr>
				
				<tr>
					<td>'.esc_html__('400', 'siteseo').'</td>
					<td>'.esc_html__('Bad Request', 'siteseo').'</td>
					<td>'.esc_html__('Request Invalid format.', 'siteseo').'</td>
				</tr>
				
				<tr>
					<td>'.esc_html__('403', 'siteseo').'</td>
					<td>'.esc_html__('Forbidden', 'siteseo').'</td>
					<td>'.esc_html__('Key not valid.', 'siteseo').'</td>
				</tr>
				
				<tr>
					<td>'.esc_html__('422', 'siteseo').'</td>
					<td>'.esc_html__('Unprocessable Entity', 'siteseo').'</td>
					<td>'.esc_html__('URLs don\'t belong to the host.', 'siteseo').'</td>
				</tr>
				
				<tr>
					<td>'.esc_html__('429', 'siteseo').'</td>
					<td>'.esc_html__('Too Many Requests', 'siteseo').'</td>
					<td>'.esc_html__('Too Many Requests: Potential Spam.', 'siteseo').'</td>
				</tr>
				<tbody>
			</table></div><br/><br/>';
		
	}

	static function save_settings(){
		global $siteseo;

		check_admin_referer('siteseo_instant_indexing');

		if(!siteseo_user_can('manage_instant_indexing')|| !is_admin()){
			return;
		}

		$options = $siteseo->instant_settings;
		
		if(!is_array($options)){
			$options = [];
		}

		if(empty($_POST['siteseo_options'])){
			return;
		}

		if(isset($_POST['siteseo_options']['general'])){
			// general tab
			$options['engines']['bing'] = isset($_POST['siteseo_options']['search_engine_bing']);
			$options['engines']['google'] = isset($_POST['siteseo_options']['search_engine_google']);
			$options['instant_indexing_google_action'] = isset($_POST['siteseo_options']['instant_indexing_actions']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['instant_indexing_actions'])) : 'URL_UPDATED';
			$options['instant_indexing_manual_batch'] = isset($_POST['siteseo_options']['instant_indexing_batch']) ? sanitize_textarea_field(wp_unslash($_POST['siteseo_options']['instant_indexing_batch'])) : '';
		}

		if(isset($_POST['siteseo_options']['setting_tab'])){
			// setting tab
			$options['instant_indexing_google_api_key'] = isset($_POST['siteseo_options']['google_api_key']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['google_api_key'])) : '';
			$options['instant_indexing_bing_api_key'] = isset($_POST['siteseo_options']['bing_api_key']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['bing_api_key'])) : '';
			$options['instant_indexing_automate_submission'] = isset($_POST['siteseo_options']['auto_submission']);
		}

		update_option('siteseo_instant_indexing_option_name', $options);
	}
}
