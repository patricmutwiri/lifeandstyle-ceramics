<?php

namespace CookieAdmin;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class Admin{
	
	static function enqueue_scripts(){
		
		if(!is_admin()){
			return true;
		}
		
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$admin_page = basename(parse_url($request_uri, PHP_URL_PATH));
		
		if($admin_page != 'admin.php'){
			return true;
		}
		
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// List all page slugs where styles should be loaded
		$plugin_pages = [
			'cookieadmin',
			'cookieadmin-settings',
			'cookieadmin-scan-cookies',
			'cookieadmin-consent',
			'cookieadmin-consent-logs',
			'cookieadmin-license',
		];
		
		if(empty($current_page) || !in_array($current_page, $plugin_pages)){
			return true;
		}
		
		//Consent Page CSS
		wp_enqueue_style('cookieadmin-style', COOKIEADMIN_PLUGIN_URL . 'assets/css/cookie.css', [], COOKIEADMIN_VERSION);
		
		//WP Color picker
		wp_enqueue_style('wp-color-picker');
		
		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');	
		$policy = cookieadmin_load_policy();
		
		if(!empty($policy) && !empty($view)){
			
			wp_enqueue_script('cookieadmin_js', COOKIEADMIN_PLUGIN_URL . 'assets/js/cookie.js', [], COOKIEADMIN_VERSION);
		
			$policy['set'] = $view;
			$policy['admin_url'] = admin_url('admin-ajax.php');
			$policy['cookieadmin_nonce'] = wp_create_nonce('cookieadmin_admin_js_nonce');
			//cookieadmin_r_print($policy);die();
			
			$policy['lang']['show_more'] = __('Show more', 'cookieadmin');
			$policy['lang']['show_less'] = __('Show less', 'cookieadmin');
			
			wp_localize_script('cookieadmin_js', 'cookieadmin_policy', $policy);
		}
		
		wp_enqueue_script('cookieadmin_js_footer', COOKIEADMIN_PLUGIN_URL . 'assets/js/footer.js', [], COOKIEADMIN_VERSION);
		wp_localize_script('cookieadmin_js_footer', 'cookieadmin_is_pro', (int) cookieadmin_is_pro());
	}
	
	//Add Main Menu
	static function cookieadmin_plugin_menu(){
		
		$capability = 'activate_plugins';
		
		add_menu_page(__('CookieAdmin', 'cookieadmin'), __('CookieAdmin', 'cookieadmin'), $capability, 'cookieadmin', '\CookieAdmin\Admin::dashboard_page', COOKIEADMIN_PLUGIN_URL .'assets/images/cookieadmin_icon_20.svg');
		
		if (isset($_POST['cookieadmin_save_settings'])) {
			\CookieAdmin\Admin::cookieadmin_save_settings();
		}
		
		add_submenu_page('cookieadmin', __('Dashboard', 'cookieadmin'), __('Dashboard', 'cookieadmin'), $capability, 'cookieadmin', '\CookieAdmin\Admin::dashboard_page');
		
		add_submenu_page('cookieadmin', __('Consent Form', 'cookieadmin'), __('Consent Form', 'cookieadmin'), $capability, 'cookieadmin-consent', '\CookieAdmin\Admin::consent_form_page');
		
		add_submenu_page('cookieadmin', __('Settings', 'cookieadmin'), __('Settings', 'cookieadmin'), $capability, 'cookieadmin-settings', '\CookieAdmin\Admin::settings_page');
		
		add_submenu_page('cookieadmin', __('Scan Cookies', 'cookieadmin'), __('Scan Cookies', 'cookieadmin'), $capability, 'cookieadmin-scan-cookies', '\CookieAdmin\Admin::scan_cookies_page');
		
		if(defined('COOKIEADMIN_PREMIUM')){
			add_submenu_page('cookieadmin', __('Consent Logs', 'cookieadmin'), __('Consent Logs', 'cookieadmin'), $capability, 'cookieadmin-consent-logs', '\CookieAdminPro\Admin::show_consent_logs');

			if(!defined('SITEPAD')){
				add_submenu_page('cookieadmin', __('License', 'cookieadmin'), __('License', 'cookieadmin'), $capability, 'cookieadmin-license', '\CookieAdminPro\License::cookieadmin_show_license');
			}
		}else if(!defined('SITEPAD')){
			// Go Pro link
			add_submenu_page('cookieadmin', __('CookieAdmin Go Pro', 'cookieadmin'), __('Go Pro', 'cookieadmin'), $capability, COOKIEADMIN_PRO_URL);
		}
	}

	// cookieadmin header
	static function header_theme($title = 'Dashboard'){
		
		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg;
			
		echo '
		<div class="cookieadmin-metabox-holder columns-2">
			<div class="cookieadmin-postbox-container">
				<div style="margin: 10px 20px 0 2px;" class="wrap">			
					<div class="cookieadmin-icon">
						<img class="cookieadmin-logo" src="'.esc_attr(COOKIEADMIN_PLUGIN_URL).'assets/images/cookieadmin-logo.png" alt="CookieAdmin Logo"> 
					</div>
				</div>
				<h2>'.esc_html($title).'</h2>';
		
		if(!empty($cookieadmin_error)){
			echo '<div id="cookieadmin_message" class="error"><p>'.esc_html($cookieadmin_error).'</p></div>';
		}
		
		if(!empty($cookieadmin_msg)){
			echo '<div id="cookieadmin_message" class="updated"><p>'.esc_html($cookieadmin_msg).'</p></div>';
		}
	}

	// cookieadmin footer
	static function footer_theme($no_twitter = 0){
		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg;
		
		if(!defined('SITEPAD')){
			echo '</div>
			<div class="cookieadmin-footer">';

			if(empty($no_twitter)){
		
				echo '<br/><div class="cookieadmin-twitter">
					<span>'.esc_html__('Share with your followers', 'cookieadmin').'</span><br /><br />
					<form method="get" action="https://twitter.com/intent/tweet" id="tweet" onsubmit="return cookieadmin_dotweet(this);">
						<textarea name="text" cols="60" row="4" style="resize:none;">'.esc_html__('I easily manage Cookie Consent Banner on my #WordPress site using @cookieadmin', 'cookieadmin').'</textarea>
						<br />
						<input type="submit" value="Tweet!" class="cookieadmin-btn cookieadmin-btn-secondary" onsubmit="return false;" id="twitter-btn" style="margin-top:7px;"/>	
					</form>				
				</div>
				<br/>
				<hr>';
		
			}
		
			echo '<a href="'.esc_url(COOKIEADMIN_WWW_URL).'" target="_blank">CookieAdmin</a><span> v'.esc_html(COOKIEADMIN_VERSION).esc_html__(' You can report any bugs ', 'cookieadmin').'</span><a href="http://wordpress.org/support/plugin/cookieadmin" target="_blank">'.esc_html__('here', 'cookieadmin').'</a>. ';
		
			if(defined('COOKIEADMIN_PREMIUM')){
				echo 'Or email us at <a href="mailto:support@cookieadmin.net">support@cookieadmin.net</a>';
			}
		
			echo '</div>
			</div>';
		}
	}
	
	static function dashboard_page(){
		
		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg, $cookieadmin_settings;
		
		self::header_theme(__('Dashboard', 'cookieadmin'));
		
		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');
		
		echo '
		<div class="cookieadmin_consent-wrap">
			<div class="cookieadmin-admin-row">
				<div class="cookieadmin-stats-block cookieadmin-is-block-25">
					<div class="cookieadmin-stats-name">'.esc_html__('Consent Banner', 'cookieadmin').'</div>
					<div class="cookieadmin-stats-number cookieadmin-green">'.esc_html__('Enabled', 'cookieadmin').'</div>
				</div>
				
				<div class="cookieadmin-stats-block cookieadmin-is-block-25">
					<div class="cookieadmin-stats-name">'.esc_html__('Consent Type', 'cookieadmin').'&nbsp;
						<div class="cookieadmin-block-link"><a href="'.esc_url(admin_url('admin.php?page=cookieadmin-consent')).'">['.esc_html__('Edit', 'cookieadmin').']</a></div>
					</div>
					<div class="cookieadmin-stats-number cookieadmin-uppercase">'.(!empty($view) && $view == 'cookieadmin_us' ? esc_html__('US State Laws', 'cookieadmin') : esc_html__('GDPR', 'cookieadmin')).'</div>
				</div>
				
				<div class="cookieadmin-stats-block cookieadmin-is-block-25">
					<div class="cookieadmin-stats-name">'.esc_html__('Google Consent Mode v2', 'cookieadmin').'&nbsp;
						<div class="cookieadmin-block-link"><a href="'.esc_url(admin_url('admin.php?page=cookieadmin-settings')).'">['.esc_html__('Edit', 'cookieadmin').']</a></div>
					</div>
					'.(!empty($cookieadmin_settings['google_consent_mode_v2']) ? '<div class="cookieadmin-stats-number cookieadmin-green">'.esc_html__('Enabled', 'cookieadmin').'</div>' : '<div class="cookieadmin-stats-number">'.esc_html__('Disabled', 'cookieadmin').'</div>').'
				</div>
				
				<div style="width:25%">
					
				</div>
			</div>
		</div>';
		
		self::footer_theme();
	}

	static function settings_page(){

		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg, $cookieadmin_settings;
		
		self::header_theme(__('Settings', 'cookieadmin'));
		
		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');	
		$policy = cookieadmin_load_policy();
		$policy['set'] = $view;
		$policy['admin_url'] = admin_url('admin-ajax.php');
		$policy['cookieadmin_nonce'] = wp_create_nonce('cookieadmin_admin_js_nonce');

		$cookieadmin_requires_pro = self::is_feature_available(1);
		
		echo '
		<div class="cookieadmin_consent-wrap">
			<form action="" method="post" id="setting_submenu">

			<div class="cookieadmin_consent-contents">
				<div class="cookieadmin_consent_settings">
					<div class="cookieadmin-contents cookieadmin-settings">
						<div class="cookieadmin-setting setting-prior">
							<label class="cookieadmin-title">'.esc_html__('Load Cookies prior to consent', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
							
								<input name="cookieadmin_preload[]" type="checkbox" id="necessary_preload" value="necessary" checked disabled>
								<label class="cookieadmin-input" for="necessary_preload">'.esc_html__('Necessary', 'cookieadmin').'</label>
								
								<input name="cookieadmin_preload[]" type="checkbox" id="functional_preload" value="functional" '.(!empty($policy[$view]['preload']) && in_array("functional", $policy[$view]['preload']) ? 'checked' : '').'>
								<label class="cookieadmin-input" for="functional_preload">'.esc_html__('Functional', 'cookieadmin').'</label>
								
								<input name="cookieadmin_preload[]" type="checkbox" id="analytics_preload" value="analytics" '.(!empty($policy[$view]['preload']) && in_array("analytics", $policy[$view]['preload']) ? 'checked' : '').'>
								<label class="cookieadmin-input" for="analytics_preload">'.esc_html__('Analytical', 'cookieadmin').'</label>
								
								<input name="cookieadmin_preload[]" type="checkbox" id="marketing_preload" value="marketing" '.(!empty($policy[$view]['preload']) && in_array("marketing", $policy[$view]['preload']) ? 'checked' : '').'>
								<label for="marketing_preload">'.esc_html__('Advertisement', 'cookieadmin').'</label>
							</div>
						</div>
						
						<div class="cookieadmin-setting setting-reload">
							<label class="cookieadmin-title" for="cookieadmin_reload_on_consent">'.esc_html__('Reload page on consent', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
								<label class="cookieadmin_toggle">
									<input name="cookieadmin_reload_on_consent" type="checkbox" id="cookieadmin_reload_on_consent" '.(!empty($policy[$view]['reload_on_consent']) ? 'checked' : '').'>
									<span class="cookieadmin_slider"></span>
								</label>
							</div>
						</div>

						<div class="coookieadmin-contents cookieadmin-settings" cookieadmin-pro-only="1">
							<div class="cookieadmin-setting">
								<label class="cookieadmin-title" for="cookieadmin_google_consent_mode_v2">'.esc_html__('Google Consent Mode v2', 'cookieadmin').wp_kses_post($cookieadmin_requires_pro).'</label>
								<div class="cookieadmin-setting-contents">
									<label class="cookieadmin_toggle">
										<input name="cookieadmin_google_consent_mode_v2" type="checkbox" id="cookieadmin_google_consent_mode_v2" '.(!empty($cookieadmin_settings['google_consent_mode_v2']) && cookieadmin_is_pro() ? 'checked' : '').'>
										<span class="cookieadmin_slider"></span>
									</label>
								</div>
							</div>

							<div class="cookieadmin-setting setting-poweredby">
								<label class="cookieadmin-title" for="cookieadmin_hide_powered_by">'.esc_html__('Hide Powered by Link', 'cookieadmin').wp_kses_post($cookieadmin_requires_pro).'</label>
								<div class="cookieadmin-setting-contents">
									<label class="cookieadmin_toggle">
										<input name="cookieadmin_hide_powered_by" type="checkbox" id="cookieadmin_hide_powered_by" '.(!empty($cookieadmin_settings['hide_powered_by']) && cookieadmin_is_pro() ? 'checked' : '').'>
										<span class="cookieadmin_slider"></span>
									</label>
								</div>
							</div>

							<div class="cookieadmin-setting setting-reconsent">
								<label class="cookieadmin-title" for="cookieadmin_hide_reconsent">'.esc_html__('Hide Re-consent Icon', 'cookieadmin').wp_kses_post($cookieadmin_requires_pro).'</label>
								<div class="cookieadmin-setting-contents">
									<label class="cookieadmin_toggle">
										<input name="cookieadmin_hide_reconsent" type="checkbox" id="cookieadmin_hide_reconsent" '.(!empty($cookieadmin_settings['hide_reconsent']) && cookieadmin_is_pro() ? 'checked' : '').'>
										<span class="cookieadmin_slider"></span>
									</label>
								</div>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<div class="cookieadmin-setting-contents">
								<span><input type="submit" name="cookieadmin_save_settings" class="cookieadmin-btn cookieadmin-btn-primary action" value="'.esc_html__('Save Settings', 'cookieadmin').'"></span>
							</div>
						</div>
					</div>
				</div>';

				wp_nonce_field('cookieadmin_admin_nonce', 'cookieadmin_security');
				echo '
				<br/>
				<br/>
			</div>
			</form>
		</div>';
		
		self::footer_theme();
	}

	static function scan_cookies_page(){
		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg, $wpdb;
		
		self::header_theme(__('Scan Cookies', 'cookieadmin'));
		
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');
		$scanned = $wpdb->get_results("SELECT * FROM {$table_name} limit 1");
		
		$cookies_scanned = '';
		$categorized = [];
		$categorized_cookies = [];
		
		if(!empty($scanned)){
			
			$is_categorized = $wpdb->get_var("SELECT * FROM {$table_name} WHERE category IS NOT NULL LIMIT 1");
			
			$scanned_cookies = $wpdb->get_results("SELECT * FROM {$table_name}");
			
			if(empty($is_categorized)){
				
				foreach($scanned_cookies as $row => $data){	
					$exp = 'Session';
					$data->expires = strtotime($data->expires);
					if(!empty($data->expires) && ($data->expires > 0)){
						$exp = round(($data->expires - time()) / 86400);
						if($exp < 1 && !empty($data->max_age)){
							$exp = $data->max_age . ' '.__('days', 'cookieadmin');
						}
					}
					
					$cookies_scanned .= '<tr><td>'.esc_html($data->cookie_name).'</td><td>'.esc_html($exp).'</td><td>'.esc_html($data->path).'</td><td>'.esc_html($data->domain).'</td><td>'.( $data->secure ? 'Yes' : 'No').'</td></tr>';
				
				}
			}
			else{

				foreach($scanned_cookies as $row => $data){	
				
					$exp = 'Session';
					if (!empty($data->expires) && is_string($data->expires)) {
						$timestamp = strtotime($data->expires);
						if ($timestamp && $timestamp > 0) {
							$exp = round(($timestamp - time()) / 86400);
							if ($exp < 1 && !empty($data->max_age)) {
								$exp = $data->max_age;
							}
							$exp .= ' '.__('days', 'cookieadmin');
						}
					}
						
					if(empty($data->category)){
						$data->category = 'Unknown';
					}
					
					if(!isset($categorized[$data->category])){
						$categorized[$data->category] = '';
					}
					
					if(empty($data->description)){
						$data->description = 'Not Available';
					}
					
					$categorized[$data->category] .= '<tr><td>'.esc_html($data->cookie_name).'</td><td>'.esc_html($data->description).'</td><td>'.esc_html($exp).'</td><td> <span class="dashicons dashicons-edit cookieadmin_edit_icon" id="edit_'.esc_attr($data->id).'"></span> <span class="dashicons dashicons-trash cookieadmin_delete_icon" id="delete_'.esc_attr($data->id).'"></span> </td></tr>';

					$categorized_cookies[$data->id]['id'] = $data->id;
					$categorized_cookies[$data->id]['cookie_name'] = $data->cookie_name;
					$categorized_cookies[$data->id]['description'] = $data->description;
					$categorized_cookies[$data->id]['category'] = $data->category;
					$categorized_cookies[$data->id]['expires'] = $exp;

				}
				
				$cookies_scanned .= '<tr><td colspan="5">'.esc_html__('No uncategorized cookies yet. Click on Scan Cookies button to scan for new cookies!', 'cookieadmin').'</td></tr>';
			}
		}else{
			
			$cookies_scanned .= '<tr><td colspan="5">'.esc_html__('No scanned cookies yet. Click on Scan Cookies button to scan for new cookies!', 'cookieadmin').'</td></tr>';
			
		}
		
		wp_register_script('cookieadmin_categorized_cookies', '', array('jquery'), COOKIEADMIN_VERSION, true);
		wp_enqueue_script('cookieadmin_categorized_cookies');
		wp_localize_script('cookieadmin_categorized_cookies', 'categorized_cookies', $categorized_cookies);
		
		echo '
		<div class="cookieadmin_consent-wrap">
			<form action="" method="post">
			<div class="cookieadmin_consent-contents">
				<div class="cookieadmin_consent_settings">
					<div class="cookieadmin-setting cookieadmin-manager-cookie-scan">
						<label class="cookieadmin-title">'.esc_html__('Scanned Cookies', 'cookieadmin').'</label>
						<div class="cookieadmin-setting-contents cookieadmin-cookie-scan">
							<input type="button" class="cookieadmin-btn cookieadmin-btn-primary cookieadmin-scan" value="'.esc_html__('Scan Cookies', 'cookieadmin').'">
						</div>
						<div class="cookieadmin-manager-result">
							<table class="cookieadmin-table cookieadmin-cookie-categorized">
								<thead>
									<tr>
										<th width="30%">'.esc_html__('Name', 'cookieadmin').'</th>
										<th width="50%">'.esc_html__('Description', 'cookieadmin').'</th>
										<th width="10%">'.esc_html__('Expiry', 'cookieadmin').'</th>
										<th width="10%">'.esc_html__('Action', 'cookieadmin').'</th>
									</tr>
								</thead>
								<tbody id="necessary_tbody">
									<tr><td colspan=4>'.esc_html__('Necessary Cookies', 'cookieadmin').'</td></tr>
									'.( !empty($categorized['Necessary']) ? wp_kses_post($categorized['Necessary']) : '<tr class="cookieadmin-empty-row"><td colspan=4>'.esc_html__('No Cookies Found!', 'cookieadmin').'</td></tr>' ).'
								</tbody>
								<tbody id="functional_tbody">
									<tr><td colspan=4>'.esc_html__('Functional Cookies', 'cookieadmin').'</td></tr>
									'.( !empty($categorized['Functional']) ? wp_kses_post($categorized['Functional']) : '<tr class="cookieadmin-empty-row"><td colspan=4>'.esc_html__('No Cookies Found!', 'cookieadmin').'</td></tr>' ).'
								</tbody>
								<tbody id="analytics_tbody">
									<tr><td colspan=4>'.esc_html__('Analytical Cookies', 'cookieadmin').'</td></tr>
									'.( !empty($categorized['Analytics']) ? wp_kses_post($categorized['Analytics']) : '<tr class="cookieadmin-empty-row"><td colspan=4>'.esc_html__('No Cookies Found!', 'cookieadmin').'</td></tr>' ).'
								</tbody>
								<tbody id="marketing_tbody">
									<tr><td colspan=4>'.esc_html__('Marketing Cookies', 'cookieadmin').'</td></tr>
									'.( !empty($categorized['Marketing']) ? wp_kses_post($categorized['Marketing']) : '<tr class="cookieadmin-empty-row"><td colspan=4>'.esc_html__('No Cookies Found!', 'cookieadmin').'</td></tr>' ).'
								</tbody>
								<tbody id="unknown_tbody">
									<tr><td colspan=4>'.esc_html__('Unknown Cookies', 'cookieadmin').'</td></tr>
									'.( !empty($categorized['Unknown']) ? wp_kses_post($categorized['Unknown']) : '<tr class="cookieadmin-empty-row"><td colspan=4>'.esc_html__('No Cookies Found!', 'cookieadmin').'</td></tr>' ).'
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>';
			
			wp_nonce_field('cookieadmin_admin_nonce', 'cookieadmin_security');
			
			echo '
		</div>
		</form>
		<br/>';
		
		self::footer_theme();
		
		echo '
		<!-- Modal Overlay -->
		<div class="cookieadmin_modal-overlay" id="edit-cookie-modal" hidden>
			<div class="cookieadmin_modal-container">
				<div class="cookieadmin_modal-header">
					<h2>'.esc_html__('Edit Cookie', 'cookieadmin').'</h2>
					<button class="cookieadmin_dialog_modal_close_btn">&times;</button>
				</div>

				<div class="cookieadmin_modal-body">
					<div class="cookieadmin_form-group">
						<label for="cookieadmin-dialog-cookie-category">'.esc_html__('Category', 'cookieadmin').'</label>
						<select id="cookieadmin-dialog-cookie-category">
							<option value="Unknown">'.esc_html__('Unknown', 'cookieadmin').'</option>
							<option value="Necessary">'.esc_html__('Necessary', 'cookieadmin').'</option>
							<option value="Functional">'.esc_html__('Functional', 'cookieadmin').'</option>
							<option value="Analytics">'.esc_html__('Analytical', 'cookieadmin').'</option>
							<option value="Marketing">'.esc_html__('Marketing', 'cookieadmin').'</option>
						</select>
					</div>
					
					<div class="cookieadmin_form-group">
						<label for="cookie_id">'.esc_html__('Cookie Name/ID', 'cookieadmin').'</label>
						<input type="text" id="cookieadmin-dialog-cookie-name" Placeholder="'.esc_html__('Enter Cookie Name or id', 'cookieadmin').'">
					</div>

					<div class="cookieadmin_form-group">
						<label for="description">'.esc_html__('Description', 'cookieadmin').'</label>
						<textarea id="cookieadmin-dialog-cookie-desc" Placeholder="'.esc_html__('Enter Cookie description here', 'cookieadmin').'"></textarea>
					</div>

					<div class="cookieadmin_form-group">
						<label for="duration">'.esc_html__('Duration', 'cookieadmin').'</label>
						<input type="text" id="cookieadmin-dialog-cookie-duration" Placeholder="'.esc_html__('30 days', 'cookieadmin').'">
					</div>

					<div class="cookieadmin_modal-footer" style="background-color:#ffffff;">
						<button class="cookieadmin-btn cookieadmin-btn-primary" id="cookieadmin_dialog_save_btn" form="edit-cookie-form">'.esc_html__('Save', 'cookieadmin').'</button>
					</div>
				</div>
			</div>
		</div>';
	}

	static function consent_form_page(){

		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg;
		
		self::header_theme(__('Consent Form', 'cookieadmin'));
		
		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');	
		$policy = cookieadmin_load_policy();
		
		$templates = implode("", cookieadmin_load_consent_template($policy[$view], $view));
		
		$policy['set'] = $view;
		$policy['admin_url'] = admin_url('admin-ajax.php');
		$policy['cookieadmin_nonce'] = wp_create_nonce('cookieadmin_admin_js_nonce');
		
		$cookieadmin_requires_pro = self::is_feature_available(1);

		echo '
		<div class="cookieadmin_consent-wrap">
			<form action="" method="post" id="consent_submenu">
			
			<div class="cookieadmin_consent-contents">
				<div class="cookieadmin_consent_settings">
					<div class="cookieadmin-contents cookieadmin_consent">
					
						<div class="cookieadmin-setting">
							<label class="cookieadmin-title" for="cookieadmin_consent_type">'.esc_html__('Consent Type', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
								<select name="cookieadmin_consent_type" id="cookieadmin_consent_type">
									<option name="cookieadmin_gdpr" id="cookieadmin_gdpr" value="cookieadmin_gdpr">'.esc_html__('GDPR', 'cookieadmin').'</option>
									<option name="cookieadmin_us" id="cookieadmin_us" value="cookieadmin_us">'.esc_html__('US State Laws', 'cookieadmin').'</option>
								</select>
							</div>
						</div>
						
						<div class="cookieadmin-setting cookieadmin_consent-expiry">
							<label class="cookieadmin-title" for="cookieadmin_consent_expiry">'.esc_html__('Consent Expiry', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
								<input type="number" name="cookieadmin_days" id="cookieadmin_consent_expiry" style="max-width:70px;" value="'.esc_attr($policy[$view]['cookieadmin_days']).'">
							</div>
						</div>
						
						<div class="cookieadmin-setting consent-layout">
							<label class="cookieadmin-title">'.esc_html__('Notice Type', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
								<input name="cookieadmin_layout" type="radio" id="cookieadmin_layout_box" value="box">
								<label class="cookieadmin-input" for="cookieadmin_layout_box">'.esc_html__('Box', 'cookieadmin').'</label>
								<input name="cookieadmin_layout" type="radio" id="cookieadmin_layout_footer" value="footer">
								<label class="cookieadmin-input" for="cookieadmin_layout_footer">'.esc_html__('Footer', 'cookieadmin').'</label>
								<input name="cookieadmin_layout" type="radio" id="cookieadmin_layout_popup"  value="popup">
								<label for="cookieadmin_layout_popup">'.esc_html__('Popup', 'cookieadmin').'</label>
							</div>
						</div>
						
						<div class="cookieadmin-setting consent-position">
							<label class="cookieadmin-title">'.esc_html__('Notice Position', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
								<input class="cookieadmin_box_layout" id="cookieadmin_position_bottom_left" name="cookieadmin_position" type="radio" value="bottom_left" checked>
								<label class="cookieadmin_box_layout cookieadmin-input" for="cookieadmin_position_bottom_left">'.esc_html__('Bottom Left', 'cookieadmin').'</label>
								<input class="cookieadmin_box_layout" id="cookieadmin_position_bottom_right" name="cookieadmin_position" type="radio" value="bottom_right">
								<label class="cookieadmin_box_layout cookieadmin-input" for="cookieadmin_position_bottom_right">'.esc_html__('Bottom Right', 'cookieadmin').'</label>
								<input class="cookieadmin_box_layout" id="cookieadmin_position_top_left" name="cookieadmin_position" type="radio" value="top_left">
								<label class="cookieadmin_box_layout cookieadmin-input" for="cookieadmin_position_top_left">'.esc_html__('Top Left', 'cookieadmin').'</label>
								<input class="cookieadmin_box_layout" id="cookieadmin_position_top_right" name="cookieadmin_position" type="radio" value="top_right">
								<label class="cookieadmin_box_layout cookieadmin-input" for="cookieadmin_position_top_right">'.esc_html__('Top Right', 'cookieadmin').'</label>
								<input class="cookieadmin_foter_layout" id="cookieadmin_position_top" name="cookieadmin_position" type="radio" value="top" style="display:none;">
								<label class="cookieadmin_foter_layout cookieadmin-input" for="cookieadmin_position_top" style="display:none;">'.esc_html__('Top', 'cookieadmin').'</label>
								<input class="cookieadmin_foter_layout" id="cookieadmin_position_bottom" name="cookieadmin_position" type="radio" value="bottom" style="display:none;">
								<label class="cookieadmin_foter_layout" for="cookieadmin_position_bottom" style="display:none;">'.esc_html__('Bottom', 'cookieadmin').'</label>
							</div>
						</div>
						
						<div class="cookieadmin-setting consent-modal-layout">
							<label class="cookieadmin-title">'.esc_html__('Preference Position', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents">
								<input id="cookieadmin_modal_center" name="cookieadmin_modal" type="radio" value="center" checked>
								<label class="cookieadmin-input" for="cookieadmin_modal_center">'.esc_html__('Center', 'cookieadmin').'</label>
								<input id="cookieadmin_modal_side" name="cookieadmin_modal" type="radio" value="side">
								<label class="cookieadmin-input" for="cookieadmin_modal_side">'.esc_html__('Side', 'cookieadmin').'</label>
								<input id="cookieadmin_modal_down" name="cookieadmin_modal" type="radio" value="down">
								<label for="cookieadmin_modal_down">'.esc_html__('Draw down', 'cookieadmin').'</label>
							</div>
						</div>
						
						<div class="cookieadmin-setting consent-notice">
							<label class="cookieadmin-title">'.esc_html__('Notice Section', 'cookieadmin').'</label>
							<div class="cookieadmin-setting-contents cookieadmin-vertical">
								<label for="cookieadmin_notice_title_layout">'.esc_html__('Title', 'cookieadmin').'</label>
								<input type="text" id="cookieadmin_notice_title_layout" name="cookieadmin_notice_title" style="width: 270px;" value="'.esc_attr($policy[$view]['cookieadmin_notice_title']).'">
								<label for="cookieadmin_notice_layout" style="margin-top:20px;">'.esc_html__('Notice', 'cookieadmin').'</label>
								<textarea rows="5vh" cols="100vw" id="cookieadmin_notice_layout" name="cookieadmin_notice">'.esc_html($policy[$view]['cookieadmin_notice']).'</textarea>
								<div class="cookieadmin-setting-colors cookieadmin-setting-contents cookieadmin-horizontal">
									<div class="cookieadmin-setting-color cookieadmin-vertical" >
										<label for="cookieadmin_notice_title_color">'.esc_html__('Title', 'cookieadmin').'</label>
										<div class="cookieadmin-color-holder cookieadmin-horizontal">
											<input type="color" id="cookieadmin_notice_title_color_box" name="cookieadmin_notice_title_color_box" value="'.esc_attr($policy[$view]['cookieadmin_notice_title_color']).'">
											<input type="text" id="cookieadmin_notice_title_color" name="cookieadmin_notice_title_color" value="'.esc_attr($policy[$view]['cookieadmin_notice_title_color']).'" class="cookieadmin-color-input">
										</div>
									</div>
									<div class="cookieadmin-setting-color cookieadmin-vertical">
										<label for="cookieadmin_notice_color">'.esc_html__('Content', 'cookieadmin').'</label>
										<div class="cookieadmin-color-holder cookieadmin-horizontal">
											<input type="color" id="cookieadmin_notice_color_box" name="cookieadmin_notice_color_box" value="'.esc_attr($policy[$view]['cookieadmin_notice_color']).'">
											<input type="text" id="cookieadmin_notice_color" name="cookieadmin_notice_color" value="'.esc_attr($policy[$view]['cookieadmin_notice_color']).'" class="cookieadmin-color-input">
										</div>
									</div>
									<div class="cookieadmin-setting-color cookieadmin-vertical">
										<label for="cookieadmin_consent_inside_bg_color">'.esc_html__('Background', 'cookieadmin').'</label>
										<div class="cookieadmin-color-holder cookieadmin-horizontal">
											<input type="color" id="cookieadmin_consent_inside_bg_color_box" name="cookieadmin_consent_inside_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_consent_inside_bg_color']).'">
											<input type="text" id="cookieadmin_consent_inside_bg_color" name="cookieadmin_consent_inside_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_consent_inside_bg_color']).'" class="cookieadmin-color-input">
										</div>
									</div>
									<div class="cookieadmin-setting-color cookieadmin-vertical">
										<label for="cookieadmin_consent_inside_border_color">'.esc_html__('Border', 'cookieadmin').'</label>
										<div class="cookieadmin-color-holder cookieadmin-horizontal">
											<input type="color" id="cookieadmin_consent_inside_border_color_box" name="cookieadmin_consent_inside_border_color_box" value="'.esc_attr($policy[$view]['cookieadmin_consent_inside_border_color']).'">
											<input type="text" id="cookieadmin_consent_inside_border_color" name="cookieadmin_consent_inside_border_color" value="'.esc_attr($policy[$view]['cookieadmin_consent_inside_border_color']).'" class="cookieadmin-color-input">
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="cookieadmin-setting">
						<label class="cookieadmin-title">'.esc_html__('Buttons', 'cookieadmin').'</label>
						<div class="cookieadmin-buttons cookieadmin-setting-contents cookieadmin-horizontal">
							<div class="cookieadmin-button cookieadmin-vertical">
								<input id="cookieadmin_customize_btn" name="cookieadmin_customize_btn" style="max-width:130px;text-align:center;" value="'.esc_attr($policy[$view]['cookieadmin_customize_btn']).'">
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_customize_btn_color_box" name="cookieadmin_customize_btn_color_box" value="'.esc_attr($policy[$view]['cookieadmin_customize_btn_color']).'">
									<input type="text" id="cookieadmin_customize_btn_color" name="cookieadmin_customize_btn_color" value="'.esc_attr($policy[$view]['cookieadmin_customize_btn_color']).'" class="cookieadmin-color-input">
								</div>
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_customize_btn_bg_color_box" name="cookieadmin_customize_btn_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_customize_btn_bg_color']).'">
									<input type="text" id="cookieadmin_customize_btn_bg_color" name="cookieadmin_customize_btn_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_customize_btn_bg_color']).'" class="cookieadmin-color-input">
								</div>
							</div>
							<div class="cookieadmin-button cookieadmin-vertical">
								<input id="cookieadmin_reject_btn" name="cookieadmin_reject_btn" style="max-width:130px;text-align:center;" value="'.esc_attr($policy[$view]['cookieadmin_reject_btn']).'">
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_reject_btn_color_box" name="cookieadmin_reject_btn_color_box" value="'.esc_attr($policy[$view]['cookieadmin_reject_btn_color']).'">
									<input type="text" id="cookieadmin_reject_btn_color" name="cookieadmin_reject_btn_color" value="'.esc_attr($policy[$view]['cookieadmin_reject_btn_color']).'" class="cookieadmin-color-input">
								</div>
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_reject_btn_bg_color_box" name="cookieadmin_reject_btn_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_reject_btn_bg_color']).'">
									<input type="text" id="cookieadmin_reject_btn_bg_color" name="cookieadmin_reject_btn_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_reject_btn_bg_color']).'" class="cookieadmin-color-input">
								</div>
							</div>
							<div class="cookieadmin-button cookieadmin-vertical">
								<input id="cookieadmin_accept_btn" name="cookieadmin_accept_btn" style="max-width:130px;text-align:center;" value="'.esc_attr($policy[$view]['cookieadmin_accept_btn']).'">
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_accept_btn_color_box" name="cookieadmin_accept_btn_color_box" value="'.esc_attr($policy[$view]['cookieadmin_accept_btn_color']).'">
									<input type="text" id="cookieadmin_accept_btn_color" name="cookieadmin_accept_btn_color" value="'.esc_attr($policy[$view]['cookieadmin_accept_btn_color']).'" class="cookieadmin-color-input">
								</div>
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_accept_btn_bg_color_box" name="cookieadmin_accept_btn_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_accept_btn_bg_color']).'">
									<input type="text" id="cookieadmin_accept_btn_bg_color" name="cookieadmin_accept_btn_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_accept_btn_bg_color']).'" class="cookieadmin-color-input">
								</div>
							</div>
							<div class="cookieadmin-button cookieadmin-vertical">
								<input id="cookieadmin_save_btn" name="cookieadmin_save_btn" style="max-width:130px;text-align:center;" value="'.esc_attr($policy[$view]['cookieadmin_save_btn']).'">
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_save_btn_color_box" name="cookieadmin_save_btn_color_box" value="'.esc_attr($policy[$view]['cookieadmin_save_btn_color']).'">
									<input type="text" id="cookieadmin_save_btn_color" name="cookieadmin_save_btn_color" value="'.esc_attr($policy[$view]['cookieadmin_save_btn_color']).'" class="cookieadmin-color-input">
								</div>
								<div class="cookieadmin-color-holder cookieadmin-horizontal">
									<input type="color" id="cookieadmin_save_btn_bg_color_box" name="cookieadmin_save_btn_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_save_btn_bg_color']).'">
									<input type="text" id="cookieadmin_save_btn_bg_color" name="cookieadmin_save_btn_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_save_btn_bg_color']).'" class="cookieadmin-color-input">
								</div>
							</div>
						</div>
					</div>
					<div class="cookieadmin-setting consent-preference">
						<label class="cookieadmin-title">'.esc_html__('Preference Section', 'cookieadmin').'</label>
						<div class="cookieadmin-setting-contents cookieadmin-vertical">
							<label for="cookieadmin_preference_title_layout">'.esc_html__('Title', 'cookieadmin').'</label>
							<input type="text" id="cookieadmin_preference_title_layout" name="cookieadmin_preference_title" style="width: 270px;" value="'.esc_html($policy[$view]['cookieadmin_preference_title']).'">
							<label for="cookieadmin_preference_layout" style="margin-top:20px;">'.esc_html__('Privacy Notice', 'cookieadmin').'</label>
							<textarea rows="8vh" cols="100vw" id="cookieadmin_preference_layout" name="cookieadmin_preference">'.esc_html($policy[$view]['cookieadmin_preference']).'</textarea>
							<div class="cookieadmin-setting-colors cookieadmin-setting-contents cookieadmin-setting-color cookieadmin-horizontal">
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_preference_title_color">'.esc_html__('Title', 'cookieadmin').'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_preference_title_color_box" name="cookieadmin_preference_title_color_box" value="'.esc_attr($policy[$view]['cookieadmin_preference_title_color']).'">
										<input type="text" id="cookieadmin_preference_title_color" name="cookieadmin_preference_title_color" value="'.esc_attr($policy[$view]['cookieadmin_preference_title_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_details_wrapper_color">'.esc_html__('Content', 'cookieadmin').'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_details_wrapper_color_box" name="cookieadmin_details_wrapper_color_box" value="'.esc_attr($policy[$view]['cookieadmin_details_wrapper_color']).'">
										<input type="text" id="cookieadmin_details_wrapper_color" name="cookieadmin_details_wrapper_color" value="'.esc_attr($policy[$view]['cookieadmin_details_wrapper_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_cookie_modal_bg_color">'.esc_html__('Background', 'cookieadmin').'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_cookie_modal_bg_color_box" name="cookieadmin_cookie_modal_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_cookie_modal_bg_color']).'">
										<input type="text" id="cookieadmin_cookie_modal_bg_color" name="cookieadmin_cookie_modal_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_cookie_modal_bg_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_cookie_modal_border_color">'.esc_html__('Border', 'cookieadmin').'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_cookie_modal_border_color_box" name="cookieadmin_cookie_modal_border_color_box" value="'.esc_attr($policy[$view]['cookieadmin_cookie_modal_border_color']).'">
										<input type="text" id="cookieadmin_cookie_modal_border_color" name="cookieadmin_cookie_modal_border_color" value="'.esc_attr($policy[$view]['cookieadmin_cookie_modal_border_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
							</div>
							
							<div class="cookieadmin-setting-colors cookieadmin-setting-contents cookieadmin-setting-color cookieadmin-horizontal" cookieadmin-pro-only="1">
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_links_color">'.esc_html__('Links', 'cookieadmin').wp_kses_post($cookieadmin_requires_pro).'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_links_color_box" name="cookieadmin_links_color_box" value="'.esc_attr($policy[$view]['cookieadmin_links_color']).'">
										<input type="text" id="cookieadmin_links_color" name="cookieadmin_links_color" value="'.esc_attr($policy[$view]['cookieadmin_links_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_slider_on_bg_color">'.esc_html__('Button Switch On', 'cookieadmin').wp_kses_post($cookieadmin_requires_pro).'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_slider_on_bg_color_box" name="cookieadmin_slider_on_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_slider_on_bg_color']).'">
										<input type="text" id="cookieadmin_slider_on_bg_color" name="cookieadmin_slider_on_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_slider_on_bg_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
								<div class="cookieadmin-setting-color cookieadmin-vertical">
									<label for="cookieadmin_slider_off_bg_color">'.esc_html__('Button Switch Off', 'cookieadmin').wp_kses_post($cookieadmin_requires_pro).'</label>
									<div class="cookieadmin-color-holder cookieadmin-horizontal">
										<input type="color" id="cookieadmin_slider_off_bg_color_box" name="cookieadmin_slider_off_bg_color_box" value="'.esc_attr($policy[$view]['cookieadmin_slider_off_bg_color']).'">
										<input type="text" id="cookieadmin_slider_off_bg_color" name="cookieadmin_slider_off_bg_color" value="'.esc_attr($policy[$view]['cookieadmin_slider_off_bg_color']).'" class="cookieadmin-color-input">
									</div>
								</div>
							</div>
							
							<span style="margin-top:30px;">
							
							<input type="submit" name="cookieadmin_save_settings" class="cookieadmin-btn cookieadmin-btn-primary action" value="'.esc_html__('Save Settings', 'cookieadmin').'">
							
							<input type="button" id="cookieadmin_show_preview" name="cookieadmin_show_preview" class="cookieadmin-btn cookieadmin-btn-secondary" value="'.esc_html__('Show Preview', 'cookieadmin').'">
							
							</span>
							
						</div>
					</div>
				</div>
			</div>
			';	
			wp_nonce_field('cookieadmin_admin_nonce', 'cookieadmin_security');
			echo '<br/>
			<br/>
			</form>
		</div>';
		self::footer_theme();
		
		$allowed_tags = cookieadmin_kses_allowed_html();
		echo wp_kses($templates, $allowed_tags);
	}

	static function cookieadmin_scan_cookies($url = ''){
		global $wpdb;
		
		$urls = array(get_site_url());
		
		if(cookieadmin_is_pro()){
			$cookieData = apply_filters('cookieadmin_pro_scan_cookies', $urls);
		}else{
			$cookieData = \CookieAdmin\Scanner::start_scan($urls);
		}
		// cookieadmin_r_print($cookieData);
		
		if(!empty($cookieData) && self::save_raw_scan_results($cookieData)){
			self::cookieadmin_auto_configure_cookies();
		}
		
		wp_send_json_error(array('message' =>__('No cookies found!', 'cookieadmin')));
	}

	static function cookieadmin_table_exists($table_name) {
		global $wpdb;
		
		$query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
		
		return $wpdb->get_var($query) === $table_name;
	}
	
	static function cookieadmin_save_settings(){
		
		global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg, $cookieadmin_settings, $cookieadmin_policies;
	
		// debug_print_backtrace();die;
		
		check_admin_referer('cookieadmin_admin_nonce', 'cookieadmin_security');
	 
		if(!current_user_can('administrator')){
			wp_send_json_error(array('message' => __('Sorry, but you do not have permissions to perform this action', 'cookieadmin')));
		}
		
		$policy = cookieadmin_load_policy();
		
		$cookieadmin_consent_type = isset( $_REQUEST['cookieadmin_consent_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cookieadmin_consent_type'] ) ) : '';

		if(!empty($cookieadmin_consent_type)){
			
			$laws = array('cookieadmin_gdpr' => '', 'cookieadmin_us' => '');
			
			$law = array_key_exists($cookieadmin_consent_type, $laws) ? $cookieadmin_consent_type : 'cookieadmin_gdpr';
			
			if(empty($cookieadmin_error)){
				update_option('cookieadmin_law', $law);
			}
		}

		if(isset($_REQUEST['page']) && $_REQUEST['page'] === 'cookieadmin-settings'){
			
			// Save cookieadmin_settings only on settings page
			$cookieadmin_settings['google_consent_mode_v2'] = (isset( $_REQUEST['cookieadmin_google_consent_mode_v2'] ) ? 1 : 0);
			$cookieadmin_settings['hide_powered_by'] = (isset( $_REQUEST['cookieadmin_hide_powered_by'] ) ? 1 : 0);
			$cookieadmin_settings['hide_reconsent'] = (isset( $_REQUEST['cookieadmin_hide_reconsent'] ) ? 1 : 0);
			
			if(empty($cookieadmin_error)){
				update_option('cookieadmin_settings', $cookieadmin_settings);
			}
			
			// get the consent type from option table, if not saved then return default as 'gdpr'
			$law = get_option('cookieadmin_law', 'cookieadmin_gdpr');

			//set preload and consent field for "cookieadmin-settings" page
			$setting['preload'] = !empty($_REQUEST['cookieadmin_preload']) ? array_map('sanitize_text_field', wp_unslash($_REQUEST['cookieadmin_preload'])) : [];
			$setting['reload_on_consent'] = !empty($_REQUEST['cookieadmin_reload_on_consent']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_reload_on_consent'])) : '';
		}else{
			// set saved or default preload and consent field for for  "cookieadmin-consent" page
			$setting['preload'] = !empty($policy[$law]['preload']) ? $policy[$law]['preload'] : [];
			$setting['reload_on_consent'] = !empty($policy[$law]['reload_on_consent']) ? $policy[$law]['reload_on_consent'] : '';
		}
			
		$setting['cookieadmin_geo_tgt'] = (!empty($_REQUEST['cookieadmin_geo_tgt'])) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_geo_tgt'])) : 'www';
		
		$setting['cookieadmin_layout'] = (!empty($_REQUEST['cookieadmin_layout']) && in_array($_REQUEST['cookieadmin_layout'], array('box', 'footer', 'popup'))) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_layout'])) : (!empty($policy[$law]['cookieadmin_layout']) ? $policy[$law]['cookieadmin_layout'] : 'box');
		
		$setting['cookieadmin_position'] = (!empty($_REQUEST['cookieadmin_position']) && in_array($_REQUEST['cookieadmin_position'],  array('bottom_left', 'bottom_right', 'top_left', 'top_right', 'top', 'bottom'))) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_position'])) : (!empty($policy[$law]['cookieadmin_position']) ? $policy[$law]['cookieadmin_position'] : 'bottom_left');

		$setting['cookieadmin_modal'] = (isset($_REQUEST['cookieadmin_modal']) && in_array($_REQUEST['cookieadmin_modal'], array('center', 'side', 'down'))) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_modal'])) : (!empty($policy[$law]['cookieadmin_modal']) ? $policy[$law]['cookieadmin_modal'] : 'center');
		
		if($setting['cookieadmin_layout'] == 'popup'){
			$setting['cookieadmin_modal'] = 'center';
			unset($setting['cookieadmin_position']);
		}		

		$setting['cookieadmin_notice_title'] = !empty($_REQUEST['cookieadmin_notice_title']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_notice_title'])) : $policy[$law]['cookieadmin_notice_title'];
		$setting['cookieadmin_notice_title_color'] = !empty($_REQUEST['cookieadmin_notice_title_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_notice_title_color'])) : (!empty($policy[$law]['cookieadmin_notice_title_color']) ? $policy[$law]['cookieadmin_notice_title_color'] : '#000000');
		
		$setting['cookieadmin_notice'] = !empty($_REQUEST['cookieadmin_notice']) ? wp_kses(wp_unslash($_REQUEST['cookieadmin_notice']), cookieadmin_kses_allowed_html()) : $policy[$law]['cookieadmin_notice'];
		$setting['cookieadmin_notice_color'] = !empty($_REQUEST['cookieadmin_notice_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_notice_color'])) : (!empty($policy[$law]['cookieadmin_notice_color']) ? $policy[$law]['cookieadmin_notice_color'] : '#000000');
		
		$setting['cookieadmin_consent_inside_bg_color'] = !empty($_REQUEST['cookieadmin_consent_inside_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_consent_inside_bg_color'])) : (!empty($policy[$law]['cookieadmin_consent_inside_bg_color']) ? $policy[$law]['cookieadmin_consent_inside_bg_color'] : '#ffffff');
		$setting['cookieadmin_consent_inside_border_color'] = !empty($_REQUEST['cookieadmin_consent_inside_border_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_consent_inside_border_color'])) : (!empty($policy[$law]['cookieadmin_consent_inside_border_color']) ? $policy[$law]['cookieadmin_consent_inside_border_color'] : '#000000');
		
		$setting['cookieadmin_customize_btn'] = !empty($_REQUEST['cookieadmin_customize_btn']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_customize_btn'])) : (!empty($policy[$law]['cookieadmin_customize_btn']) ? $policy[$law]['cookieadmin_customize_btn'] : 'Customize');
		$setting['cookieadmin_customize_btn_color'] = !empty($_REQUEST['cookieadmin_customize_btn_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_customize_btn_color'])) : (!empty($policy[$law]['cookieadmin_customize_btn_color']) ? $policy[$law]['cookieadmin_customize_btn_color'] : '#ffffff');
		$setting['cookieadmin_customize_btn_bg_color'] = !empty($_REQUEST['cookieadmin_customize_btn_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_customize_btn_bg_color'])) : (!empty($policy[$law]['cookieadmin_customize_btn_bg_color']) ? $policy[$law]['cookieadmin_customize_btn_bg_color'] : '#0000ff');
		
		$setting['cookieadmin_reject_btn'] = !empty($_REQUEST['cookieadmin_reject_btn']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_reject_btn'])) : (!empty($policy[$law]['cookieadmin_reject_btn']) ? $policy[$law]['cookieadmin_reject_btn'] : 'Reject All');
		$setting['cookieadmin_reject_btn_color'] = !empty($_REQUEST['cookieadmin_reject_btn_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_reject_btn_color'])) : (!empty($policy[$law]['cookieadmin_reject_btn_color']) ? $policy[$law]['cookieadmin_reject_btn_color'] : '#ffffff');
		$setting['cookieadmin_reject_btn_bg_color'] = !empty($_REQUEST['cookieadmin_reject_btn_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_reject_btn_bg_color'])) : (!empty($policy[$law]['cookieadmin_reject_btn_bg_color']) ? $policy[$law]['cookieadmin_reject_btn_bg_color'] : '#ff0000');

		$setting['cookieadmin_accept_btn'] = !empty($_REQUEST['cookieadmin_accept_btn']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_accept_btn'])) : (!empty($policy[$law]['cookieadmin_accept_btn']) ? $policy[$law]['cookieadmin_accept_btn'] : 'Accept All');
		$setting['cookieadmin_accept_btn_color'] = !empty($_REQUEST['cookieadmin_accept_btn_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_accept_btn_color'])) : (!empty($policy[$law]['cookieadmin_accept_btn']) ? $policy[$law]['cookieadmin_accept_btn_color'] : '#ffffff');
		$setting['cookieadmin_accept_btn_bg_color'] = !empty($_REQUEST['cookieadmin_accept_btn_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_accept_btn_bg_color'])) : (!empty($policy[$law]['cookieadmin_accept_btn_bg_color']) ? $policy[$law]['cookieadmin_accept_btn_bg_color'] : '#00ff00');

		$setting['cookieadmin_save_btn'] = !empty($_REQUEST['cookieadmin_save_btn']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_save_btn'])) : (!empty($policy[$law]['cookieadmin_save_btn']) ? $policy[$law]['cookieadmin_save_btn'] : 'Save Preferences');
		$setting['cookieadmin_save_btn_color'] = !empty($_REQUEST['cookieadmin_save_btn_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_save_btn_color'])) : (!empty($policy[$law]['cookieadmin_save_btn_color']) ? $policy[$law]['cookieadmin_save_btn_color'] : '#ffffff');
		$setting['cookieadmin_save_btn_bg_color'] = !empty($_REQUEST['cookieadmin_save_btn_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_save_btn_bg_color'])) : (!empty($policy[$law]['cookieadmin_save_btn_bg_color']) ? $policy[$law]['cookieadmin_save_btn_bg_color'] : '#183833');

		$setting['cookieadmin_preference_title'] = !empty($_REQUEST['cookieadmin_preference_title']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_preference_title'])) : $policy[$law]['cookieadmin_preference_title'];
		$setting['cookieadmin_preference_title_color'] = !empty($_REQUEST['cookieadmin_preference_title_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_preference_title_color'])) : (!empty($policy[$law]['cookieadmin_preference_title_color']) ? $policy[$law]['cookieadmin_preference_title_color'] : '#000000');
		
		$setting['cookieadmin_preference'] = !empty($_REQUEST['cookieadmin_preference']) ? wp_kses(wp_unslash($_REQUEST['cookieadmin_preference']), cookieadmin_kses_allowed_html()) : $policy[$law]['cookieadmin_preference'];
		$setting['cookieadmin_details_wrapper_color'] = !empty($_REQUEST['cookieadmin_details_wrapper_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_details_wrapper_color'])) : (!empty($policy[$law]['cookieadmin_details_wrapper_color']) ? $policy[$law]['cookieadmin_details_wrapper_color'] : '#000000');
		
		$setting['cookieadmin_cookie_modal_bg_color'] = !empty($_REQUEST['cookieadmin_cookie_modal_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_cookie_modal_bg_color'])) : (!empty($policy[$law]['cookieadmin_cookie_modal_bg_color']) ? $policy[$law]['cookieadmin_cookie_modal_bg_color'] : '#ffffff');
		$setting['cookieadmin_cookie_modal_border_color'] = !empty($_REQUEST['cookieadmin_cookie_modal_border_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_cookie_modal_border_color'])) : (!empty($policy[$law]['cookieadmin_cookie_modal_border_color']) ? $policy[$law]['cookieadmin_cookie_modal_border_color'] : '#000000');

		$setting['cookieadmin_slider_off_bg_color'] = !empty($_REQUEST['cookieadmin_slider_off_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_slider_off_bg_color'])) : (!empty($policy[$law]['cookieadmin_slider_off_bg_color']) ? $policy[$law]['cookieadmin_slider_off_bg_color'] : '#808080');
		$setting['cookieadmin_slider_on_bg_color'] = !empty($_REQUEST['cookieadmin_slider_on_bg_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_slider_on_bg_color'])) : (!empty($policy[$law]['cookieadmin_slider_on_bg_color']) ? $policy[$law]['cookieadmin_slider_on_bg_color'] : '#3582c4');
		$setting['cookieadmin_links_color'] = !empty($_REQUEST['cookieadmin_links_color']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_links_color'])) : (!empty($policy[$law]['cookieadmin_links_color']) ? $policy[$law]['cookieadmin_links_color'] : '#1863dc');
		
		$setting['cookieadmin_days'] = !empty($_REQUEST['cookieadmin_days']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_days'])) : (!empty($policy[$law]['cookieadmin_days']) ? $policy[$law]['cookieadmin_days'] : '365');
		
		$policy[$law] = $setting;
		
		// Check for certain fields to be saved only if their values is not the same as default
		$cookieadmin_check_changes = array('cookieadmin_notice_title', 'cookieadmin_notice', 'cookieadmin_preference_title', 'cookieadmin_preference', 'reConsent_title', 'cookieadmin_customize_btn', 'cookieadmin_reject_btn', 'cookieadmin_accept_btn', 'cookieadmin_save_btn');
		
		foreach($cookieadmin_check_changes as $c_field){
			foreach($policy as $c_law => $c_val){
				if(!empty($c_val[$c_field]) && $c_val[$c_field] == $cookieadmin_policies[$c_law][$c_field]){
					unset($policy[$c_law][$c_field]);
				}
			}
		}
		
		update_option('cookieadmin_consent_settings', $policy);
		
		if(empty($cookieadmin_error)){
			$cookieadmin_msg = __('Settings saved successfully', 'cookieadmin');
		}
	}
	
	
	static function cookieadmin_auto_configure_cookies(){
		global $wpdb;
		
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');
		$categorized_cookies = [];
		$uncategorized_cookies = [];
		
		$all_cookies = $wpdb->get_results("SELECT cookie_name, category FROM {$table_name}");
		
		foreach($all_cookies as $cookie){
			
			if(!empty($cookie->categorized)){
				$categorized_cookies[] = $cookie->cookie_name;
			}else{
				$uncategorized_cookies[] = $cookie->cookie_name;
			}
		}
		
		if(!empty($uncategorized_cookies)){
			
			$uncategorized_cookies = array_flip($uncategorized_cookies);
			$categorized_cookies = array_flip($categorized_cookies);
			
			
			$categorizd_cookies = \CookieAdmin\CookieCategorizer::categorize_cookies($uncategorized_cookies, $categorized_cookies);
			
			$remove_cookies = $categorizd_cookies['remove_cookies'];
			unset($categorizd_cookies['remove_cookies']);
			if(!empty($remove_cookies)){
				$placeholders = implode(',', array_fill(0, count($remove_cookies), '%s'));
				$sql = $wpdb->prepare("DELETE FROM {$table_name} WHERE raw_name IN ({$placeholders})", ...$remove_cookies);
				$wpdb->query($sql);
			}			
			
			foreach($categorizd_cookies as $cookie_data){
			
				$wpdb->update(
					$table_name,
					[ 'cookie_name' => $cookie_data['cookie_name'], 'category' =>  $cookie_data['category'], 'description' =>  $cookie_data['description'], 'edited' =>  1, 'patterns' =>  $cookie_data['patterns'] ], // Data to update
					[ 'raw_name' => $cookie_data['raw_name'] ], // WHERE 
					[ '%s', '%s', '%s', '%d', '%s' ], // Format for the data
					[ '%s' ]  // Format for the WHERE clause
				);
				
			}
			
			$categorized_cookies = $wpdb->get_results("SELECT id, cookie_name, category, expires, description FROM {$table_name}");
			
			wp_send_json_success($categorized_cookies);
		}
		wp_send_json(['success' => true,
		            'data'    => null,
		            'message'   => __('No cookies to categorize!', 'cookieadmin')]);
	}
	
	static function cookieadmin_edit_cookies(){
		global $wpdb;
		
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');
		
		if(!empty($_REQUEST['cookie_info'])) {
			
			$cookie_info = wp_unslash($_REQUEST['cookie_info']);
			
			$resp = $wpdb->update(
					$table_name,
					[ 'cookie_name' => sanitize_text_field($cookie_info['name']), 'description' =>  sanitize_text_field($cookie_info['description']), 'expires' =>  sanitize_text_field($cookie_info['duration']), 'category' =>  sanitize_text_field($cookie_info['type']), 'edited' => 1], // Data to update
					[ 'id' => sanitize_text_field($cookie_info['id']) ], // WHERE 
					[ '%s', '%s', '%s', '%s', '%d' ], // Format for the data
					[ '%d' ]  // Format for the WHERE clause
				);
			
		}elseif( !empty($_REQUEST['cookie_raw_id']) ){
			
			$cookie_id = (int) sanitize_text_field(wp_unslash($_REQUEST['cookie_raw_id']));
			
			$resp = $wpdb->delete( $table_name, ['id' => $cookie_id], [ '%s' ] );
		}
		
		if ($wpdb->last_error || $resp === false) {
			//error_log('DB Error: ' . $wpdb->last_error); // Log it
			wp_send_json(['success' => true,
				'data'    => null,
				'message'   => __('Error editing cookie, Error: ', 'cookieadmin') . esc_html($wpdb->last_error)]);
		}
		
		wp_send_json_success(__('Cookie updated!', 'cookieadmin'));
		
	}

	static function save_raw_scan_results(array $found_cookies){

		global $wpdb;
		
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');

		if (empty($found_cookies)) {
			return ['inserted' => 0, 'updated' => 0];
		}

		// Step 1: Fetch all existing cookie names from our database in one efficient query.
		$existing_cookies_in_db = $wpdb->get_col("SELECT cookie_name FROM {$table_name}");
		// Use array_flip for very fast 'isset' lookups instead of slow 'in_array' in a loop.
		$existing_cookies_lookup = !empty($existing_cookies_in_db) ? array_flip($existing_cookies_in_db) : [];

		$results = ['inserted' => 0, 'updated' => 0];

		// Step 2: Loop through each cookie found by the scanner.
		foreach ($found_cookies as $cookie_name => $cookie_data) {

			// Step 3: Check if the cookie exists in our DB.
			if (isset($existing_cookies_lookup[$cookie_name])) {
				
				$wpdb->update(
					$table_name,
					[ 'scan_timestamp' => time() ], // Data to update
					[ 'cookie_name' => $cookie_name ], // WHERE clause
					[ '%s' ], // Format for the data
					[ '%s' ]  // Format for the WHERE clause
				);
				$results['updated']++;

			} else {

				// ------ INSERT a NEW cookie ------
				$data = [
					'cookie_name' => sanitize_text_field($cookie_name),
					'domain' => sanitize_text_field($cookie_data['domain']),
					'path' => sanitize_text_field($cookie_data['path']),
					'expires' => !empty($cookie_data['expires']) ? $cookie_data['expires'] : null,
					'max_age' => $cookie_data['Max-Age'] ? $cookie_data['Max-Age'] : null,
					'samesite' => !empty($cookie_data['samesite']) ? sanitize_text_field($cookie_data['samesite']) : null,
					'secure' => (int)($cookie_data['secure'] ?? 0),
					'httponly' => (int)($cookie_data['httponly'] ?? 0),
					'raw_name' => sanitize_text_field($cookie_name),
					'scan_timestamp' => time(),
				];

				$formats = ['%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s'];

				if ($wpdb->insert($table_name, $data, $formats)) {
					$results['inserted']++;
				} else {
					//error_log("CookieAdmin: Error inserting cookie data: " . $wpdb->last_error);
				}
			}
		}
		return $results;
	}
	
	static function plugin_update_notice(){
		if(defined('SOFTACULOUS_PLUGIN_UPDATE_NOTICE')){
			return;
		}

		$to_update_plugins = apply_filters('softaculous_plugin_update_notice', []);

		if(empty($to_update_plugins)){
			return;
		}

		/* translators: %1$s is replaced with a "string" of name of plugins, and %2$s is replaced with "string" which can be "is" or "are" based on the count of the plugin */
		$msg = sprintf(__('New versions of %1$s %2$s available. Updating ensures better performance, security, and access to the latest features.', 'cookieadmin'), '<b>'.esc_html(implode(', ', $to_update_plugins)).'</b>', (count($to_update_plugins) > 1 ? 'are' : 'is')) . ' <a class="button button-primary" href='.esc_url(admin_url('plugins.php?plugin_status=upgrade')).'>Update Now</a>';

		define('SOFTACULOUS_PLUGIN_UPDATE_NOTICE', true); // To make sure other plugins don't return a Notice
		echo '<div class="notice notice-info is-dismissible" id="cookieadmin-plugin-update-notice">
			<p>'.wp_kses_post($msg). '</p>
		</div>';

		wp_register_script('cookieadmin-update-notice', '', ['jquery'], '', true);
		wp_enqueue_script('cookieadmin-update-notice');
		wp_add_inline_script('cookieadmin-update-notice', 'jQuery("#cookieadmin-plugin-update-notice").on("click", function(e){
			let target = jQuery(e.target);

			if(!target.hasClass("notice-dismiss")){
				return;
			}

			var data;
			
			// Hide it
			jQuery("#cookieadmin-plugin-update-notice").hide();
			
			// Save this preference
			jQuery.post("'.admin_url('admin-ajax.php?action=cookieadmin_ajax_handler&cookieadmin_act=close-update-notice').'&cookieadmin_security='.wp_create_nonce('cookieadmin_admin_js_nonce').'", data, function(response) {
				//alert(response);
			});
		});');
	}

	static function plugin_update_notice_filter($plugins = []){
		$plugins['cookieadmin/cookieadmin.php'] = 'CookieAdmin';
		return $plugins;
	}

	static function close_plugin_update_notice(){
		$plugin_update_notice = get_option('softaculous_plugin_update_notice', []);
		$available_update_list = get_site_transient('update_plugins');
		$to_update_plugins = apply_filters('softaculous_plugin_update_notice', []);

		if(empty($available_update_list) || empty($available_update_list->response)){
			return;
		}

		foreach($to_update_plugins as $plugin_path => $plugin_name){
			if(isset($available_update_list->response[$plugin_path])){
				$plugin_update_notice[$plugin_path] = $available_update_list->response[$plugin_path]->new_version;
			}
		}

		update_option('softaculous_plugin_update_notice', $plugin_update_notice);
	}

	static function is_feature_available($return = 0){
		
		if(cookieadmin_is_pro()){
			return '';
		}
		
		$msg = ' <sup style="font-size:11px;"><a href="'.COOKIEADMIN_PRO_URL.'" target="_blank" style="text-decoration:none; color:red;"><b>'.__('Pro', 'cookieadmin').'</b></a></sup>';
		
		if(!empty($return)){
			return $msg;
		}else{
			echo wp_kses_post($msg);
		}

	}
}

