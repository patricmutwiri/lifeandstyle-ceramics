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

class Ajax{

	static function hooks(){
		add_action('wp_ajax_siteseo_save_titles_meta_toggle', '\SiteSEO\Ajax::save_toggle_state');
		add_action('wp_ajax_siteseo_save_sitemap_toggle', '\SiteSEO\Ajax::save_toggle_state');
		add_action('wp_ajax_siteseo_save_indexing_toggle', '\SiteSEO\Ajax::save_toggle_state');
		add_action('wp_ajax_siteseo_save_advanced_toggle', '\SiteSEO\Ajax::save_toggle_state');
		add_action('wp_ajax_siteseo_save_social_toggle', '\SiteSEO\Ajax::save_toggle_state');
		add_action('wp_ajax_siteseo_save_analytics_toggle', '\SiteSEO\Ajax::save_toggle_state');
		add_action('wp_ajax_siteseo_generate_bing_api_key', '\SiteSEO\Ajax::generate_bing_api_key');
		add_action('wp_ajax_siteseo_url_submitter_submit', '\SiteSEO\Ajax::instant_indexing');
		add_action('wp_ajax_siteseo_refresh_analysis', '\SiteSEO\Ajax::refresh_seo_analysis');
		add_action('wp_ajax_siteseo_export_settings', '\SiteSEO\Ajax::export_settings');
		add_action('wp_ajax_siteseo_import_settings', '\SiteSEO\Ajax::import_settings');
		add_action('wp_ajax_siteseo_reset_settings', '\SiteSEO\Ajax::reset_settings');
		add_action('wp_ajax_siteseo_migrate_seo', '\SiteSEO\Ajax::handle_import');
		add_action('wp_ajax_siteseo_dismiss_intro', '\SiteSEO\Ajax::dismiss_intro');
		add_action('wp_ajax_siteseo_save_universal_metabox', '\SiteSEO\Ajax::save_universal_metabox');
		add_action('wp_ajax_siteseo_resolve_variables', '\SiteSEO\Ajax::resolve_variables');
		add_action('wp_ajax_siteseo_clear_indexing_history', '\SiteSEO\Ajax::clear_indexing_history');
		add_action('wp_ajax_siteseo_close_update_notice', '\SiteSEO\Ajax::close_update_notice');
		
		// This is just to make sure, close of update notice works.
		if(isset($_GET['action']) && 'siteseo_close_update_notice' === sanitize_text_field(wp_unslash($_GET['action']))){
			add_filter('softaculous_plugin_update_notice', 'siteseo_plugin_update_notice_filter');
		}
		
		// Onboarding Actions
		add_action('wp_ajax_siteseo_save_onboarding_settings', '\SiteSEO\Ajax::save_onboarding_settings');
	}

	static function handle_import(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!siteseo_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}
		
		$plugin = !empty($_POST['plugin']) ? sanitize_text_field(wp_unslash($_POST['plugin'])) : '';
		
		switch($plugin){
			case 'wordpress-seo':
				$result = \SiteSEO\Import::yoast_seo();
				break;
			case 'all-in-one-seo-pack':
				$result = \SiteSEO\Import::aio_seo();
				break;
			case 'autodescription':
				$result = \SiteSEO\Import::seo_framework();
				break;
			case 'wp-seopress':
				$result = \SiteSEO\Import::seo_press();
				break;
			case 'seo-by-rank-math':
				$result = \SiteSEO\Import::rank_math();
				break;
			case 'slim-seo':
				$result = \SiteSEO\Import::slim_seo();
				break;
			case 'surerank':
				$result = \SiteSEO\Import::surerank();
				break;
			default:
				throw new \Exception('Invalid plugin selected');
		}
		
		if(empty($result)){
			wp_send_json_error(['message' => __('Invalid plugin selected', 'siteseo')]);
		}
		

		update_option('siteseo_last_migration_log', $result['log'], false);
		wp_send_json_success(['message' => $result['message']]);
	}

	static function reset_settings(){

		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}
		
		$options = [
			'siteseo_toggle',
			'siteseo_titles_option_name',
			'siteseo_social_option_name',
			'siteseo_advanced_option_name',
			'siteseo_instant_indexing_option_name',
			'siteseo_xml_sitemap_option_name',
			'siteseo_google_analytics_option_name',
			'siteseo_dismiss_intro',
			'siteseo_pro_options'
		];

		foreach($options as $option){
			delete_option($option);
		}

		wp_send_json_success(['message' => esc_html__('Settings reset successfully.', 'siteseo')]);

	}

	static function import_settings(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}
		
		if(!isset($_FILES['import_file'])){
			wp_send_json_error(array('message' => 'No file was uploaded.'));
		}
		
		// If name or tmp path is not available return
		if(empty($_FILES['import_file']['name']) || empty($_FILES['import_file']['tmp_name'])){
			wp_send_json_error(array('message' => 'No file was uploaded.'));
		}

		$imported_file = $_FILES['import_file']['tmp_name'];
		$filename = sanitize_file_name($_FILES['import_file']['name']);
		$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		// Verify file exists and is readable
		if(!file_exists($imported_file) || !is_readable($imported_file) || !is_uploaded_file($imported_file)){
			wp_send_json_error(array('message' => __('Uploaded file is not readable.', 'siteseo')));
		}
		
		$mime_type = sanitize_text_field(wp_unslash($_FILES['import_file']['type']));
		
		if($mime_type !== 'application/json'){
			wp_send_json_error(array('message' => __('Invalid mime type. The uploaded file has invalid mime type', 'siteseo')));
		}

		// Making sure is the correct file format
		if($file_extension !== 'json') {
			wp_send_json_error(array('message' => __('Invalid file type. Please upload a JSON file.', 'siteseo')));
		}
		
		$file_contents = file_get_contents($imported_file);

		$settings = json_decode($file_contents, true);

		if(json_last_error() !== JSON_ERROR_NONE){
			wp_send_json_error(array('message' => __('Invalid JSON file.', 'siteseo')));
		}
		
		if(empty($settings) || !is_array($settings)){
			wp_send_json_error(array('message' => __('Invalid settings format.', 'siteseo')));
		}

		$settings = map_deep(wp_unslash($settings), 'sanitize_textarea_field');

		if(isset($settings['siteseo_titles_option_name'])){
			update_option('siteseo_titles_option_name', $settings['siteseo_titles_option_name']);
		}
		
		if(isset($settings['siteseo_social_option_name'])){
			update_option('siteseo_social_option_name', $settings['siteseo_social_option_name']);
		}
		
		if(isset($settings['siteseo_xml_sitemap_option_name'])){
			update_option('siteseo_xml_sitemap_option_name', $settings['siteseo_xml_sitemap_option_name']);
		}
		
		if(isset($settings['siteseo_toggle'])){
			update_option('siteseo_toggle', $settings['siteseo_toggle']);
		}
		
		if(isset($settings['siteseo_advanced_option_name'])){
			update_option('siteseo_advanced_option_name', $settings['siteseo_advanced_option_name']);
		}
		
		if(isset($settings['siteseo_instant_indexing_option_name'])){
			update_option('siteseo_instant_indexing_option_name', $settings['siteseo_instant_indexing_option_name']);
		}
		
		if(isset($settings['siteseo_google_analytics_option_name'])){
			update_option('siteseo_google_analytics_option_name', $settings['siteseo_google_analytics_option_name']);
		}
		
		// Pro
		if(isset($settings['siteseo_pro_options'])){
			update_option('siteseo_pro_options', $settings['siteseo_pro_options']);
		}
		

		wp_send_json_success(['message' => esc_html__('Settings imported successfully.', 'siteseo')]);	
	}
	
	static function export_settings(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}

		$export_data = array(
			'siteseo_titles_option_name' => get_option('siteseo_titles_option_name'),
			'siteseo_social_option_name' => get_option('siteseo_social_option_name'),
			'siteseo_xml_sitemap_option_name' => get_option('siteseo_xml_sitemap_option_name'),
			'siteseo_toggle' => get_option('siteseo_toggle'),
			'siteseo_google_analytics_option_name' => get_option('siteseo_google_analytics_option_name'),
			'siteseo_instant_indexing_option_name' => get_option('siteseo_instant_indexing_option_name'),
			'siteseo_advanced_option_name' => get_option('siteseo_advanced_option_name'),
			'siteseo_pro_options' =>get_option('siteseo_pro_options'),
		);

		$file_name = 'siteseo-settings-export-' . current_time('m-d-Y') . '.json';

		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="'.$file_name.'"');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		echo wp_json_encode($export_data);		
		exit;
	}

	static function refresh_seo_analysis(){

		check_ajax_referer('siteseo_admin_nonce', 'nonce');

		if(!current_user_can('siteseo_manage')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}

		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		$post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : '';
		$target_keywords = isset($_POST['target_keywords']) ? sanitize_text_field(wp_unslash($_POST['target_keywords'])) : '';
		
		$post = get_post($post_id);
		if(!$post || !current_user_can('edit_post', $post_id)){
			wp_send_json_error(['message' => __('Invalid post or insufficient permissions', 'siteseo')]);
		}
		
		update_post_meta($post_id, '_siteseo_analysis_target_kw', $target_keywords);
	
		$analysis_data = \SiteSEO\Metaboxes\Analysis::perform_seo_analysis($post);

		update_post_meta($post_id, '_siteseo_analysis_data', $analysis_data);

		ob_start();
		\SiteSEO\Metaboxes\Analysis::display_seo_analysis($post);
		$analysis_html = ob_get_clean();

		wp_send_json_success([
			'html' => $analysis_html,
			'analysis_data' => $analysis_data
		]);
	}
	
	static function instant_indexing(){
		
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!siteseo_user_can('manage_instant_indexing')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}
		
		// Validate input
		if(!isset($_POST['search_engine'], $_POST['urls'])){
			wp_send_json_error(['message' => esc_html__('Missing required parameters', 'siteseo')]);
		}
	
		$urls = sanitize_textarea_field(wp_unslash($_POST['urls']));
	
		$options = get_option('siteseo_instant_indexing_option_name');
		$google_api_key = isset($options['instant_indexing_google_api_key']) ? $options['instant_indexing_google_api_key'] : '';
		$bing_api_key = isset($options['instant_indexing_bing_api_key']) ? $options['instant_indexing_bing_api_key'] : '';
		$url_list = array_filter(array_map('trim', explode("\n", $urls))); 
	
		if(empty($url_list)){
			wp_send_json_error(['message' => 'No valid URLs provided']);
		}

		$response = [];

		try{

			if(!empty($options['engines']['google']) && !empty($google_api_key)){
				$response['google'] = \SiteSEO\InstantIndexing::submit_urls_to_google($url_list);	
			} 

			if(!empty($options['engines']['bing']) && !empty($bing_api_key)){
				$response['bing'] = \SiteSEO\InstantIndexing::submit_urls_to_bing($url_list, $bing_api_key);
			}

			if(empty($response)){
				wp_send_json_error(['message' => 'No search engines configured or missing API keys']);
			}
			
			$res_google = !empty($response['google']) ? $response['google'] : null;
			$res_bing = !empty($response['bing']) ? $response['bing'] : null;
			
			\SiteSEO\InstantIndexing::save_index_history($url_list, $res_google, $res_bing, null);
			
			wp_send_json_success([
				'message' => 'URLs submitted successfully', 
				'details' => $response
			]);

		} catch(\Exception $e){
			wp_send_json_error(['message' => $e->getMessage()]);
		}
    }
	
	static function generate_bing_api_key(){
		
		if(!check_ajax_referer('siteseo_admin_nonce', 'nonce', false)){
			wp_send_json_error('Invalid nonce');
			return;
		}
		
		if(!siteseo_user_can('manage_instant_indexing')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}

		$lowercase = range('a', 'z');
		$uppercase = range('A', 'Z');
		$numbers = range('0', '9');
		$dash = ['-'];

		$characters = array_merge($lowercase, $uppercase, $numbers, $dash);
		$characters_length = count($characters);

		$length = 32;
		$api_key = '';

		for($i = 0; $i < $length; $i++){
			$api_key .= $characters[random_int(0, $characters_length - 1)];
		}

		wp_send_json_success(['api_key' => $api_key]);
	}
	
	static function save_toggle_state(){

		check_ajax_referer('siteseo_toggle_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'siteseo')]);
		}
		
		$action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '';
		switch($action){
			case 'siteseo_save_titles_meta_toggle':
				$toggle_key = 'toggle-titles';
				break;
			case 'siteseo_save_sitemap_toggle':
				$toggle_key = 'toggle-xml-sitemap';
				break;
			case 'siteseo_save_indexing_toggle':
				$toggle_key = 'toggle-instant-indexing';
				break;
			case 'siteseo_save_advanced_toggle':
				$toggle_key = 'toggle-advanced';
				break;
			case 'siteseo_save_social_toggle':
				$toggle_key = 'toggle-social';
				break;
			case 'siteseo_save_analytics_toggle':
				$toggle_key = 'toggle-google-analytics';
				break;
			default:
				wp_send_json_error(['message' => __('Invalid action', 'siteseo')]);
				return;
		}

		$toggle_value = isset($_POST['toggle_value']) ? sanitize_text_field(wp_unslash($_POST['toggle_value'])) : '0';

		$options = get_option('siteseo_toggle', []);
		$options[$toggle_key] = $toggle_value;
		$updated = update_option('siteseo_toggle', $options);

		if($updated){
			wp_send_json_success([
				'message' => ucfirst($toggle_key) . ' toggle state saved successfully',
				'value' => $toggle_value
			]);
		}

		wp_send_json_error(['message' => __('Failed to save toggle state', 'siteseo')]);
	}	
	
	static function save_onboarding_settings(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('manage_options')){
			wp_send_json_error(__('You do not have required permission to edit this file.', 'siteseo'));
		}
		
		if(empty($_POST['step'])){
			wp_send_json_error(['message' => __('Could not figure out the current step', 'siteseo')]);
		}
		
		$step_name = !empty($_POST['step']) ? sanitize_text_field(wp_unslash($_POST['step'])) : '';
		
		switch($step_name){
			case 'your-site':
				$title_options = get_option('siteseo_titles_option_name', []);
				$social_options = get_option('siteseo_social_option_name', []);

				if(!empty($_POST['data']['website_name'])){
					$title_options['titles_home_site_title'] = sanitize_text_field(wp_unslash($_POST['data']['website_name']));
				}

				if(!empty($_POST['data']['alternate_site_name'])){
					$title_options['titles_home_site_title_alt'] = sanitize_text_field(wp_unslash($_POST['data']['alternate_site_name']));
				}

				if(!empty($_POST['data']['site_type'])){
					$social_options['social_knowledge_type'] = sanitize_text_field(wp_unslash($_POST['data']['site_type']));
				}

				if(!empty($_POST['data']['organization_name'])){
					$social_options['social_knowledge_name'] = sanitize_text_field(wp_unslash($_POST['data']['organization_name']));
				}

				if(!empty($_POST['data']['organization_logo'])){
					$social_options['social_knowledge_img'] = sanitize_url(wp_unslash($_POST['data']['organization_logo']));
				}

				if(!empty($_POST['data']['social_fb'])){
					$social_options['social_accounts_facebook'] = sanitize_url(wp_unslash($_POST['data']['social_fb']));
				}

				if(!empty($_POST['data']['social_x'])){
					$social_options['social_accounts_twitter'] = sanitize_text_field(wp_unslash($_POST['data']['social_x']));
				}

				if(!empty($_POST['data']['social_additional'])){
					$social_options['social_accounts_additional'] = explode("\n", sanitize_textarea_field(wp_unslash($_POST['data']['social_additional'])));
				}

				update_option('siteseo_titles_option_name', $title_options);
				update_option('siteseo_social_option_name', $social_options);

				wp_send_json_success();
				break;

			case 'indexing':

				if(empty($_POST['data'])){
					break;
				}

				$data = map_deep(wp_unslash($_POST['data']), 'sanitize_text_field');
				$site_status = !empty($data['site_status']) ? $data['site_status'] : '';

				if(empty($site_status)){
					wp_send_json_error(['message' => __('Request data did not reach the backend', 'siteseo')]);
				}

				$title_options = get_option('siteseo_titles_option_name', []);
				
				if($site_status == 'underconstruction'){
					$title_options['titles_noindex'] = true;
					update_option('siteseo_titles_option_name', $title_options);
					wp_send_json_success();
				}
				
				// Saving Post type indexing values
				if(!empty($data['post_types'])){
					if(!is_array($data['post_types'])){
						$data['post_types'] = [$data['post_types']];
					}

					$post_types = get_post_types(['public' =>  true, 'show_ui' => true], 'objects', 'and');
					unset($post_types['attachment']);
					
					foreach($post_types as $post){
						if(in_array($post->name, $data['post_types'])){
							$title_options['titles_single_titles'][$post->name]['noindex'] = $post->name;
						}
					}
				}
				
				// Saving Taxonomies indexing values
				if(!empty($data['taxonomies'])){
					if(!is_array($data['taxonomies'])){
						$data['taxonomies'] = [$data['taxonomies']];
					}

					$taxonomies = get_taxonomies(['public' =>  true, 'show_ui' => true], 'objects', 'and');

					foreach($taxonomies as $taxonomy){
						if(in_array($taxonomy->name, $data['taxonomies'])){
							$title_options['titles_tax_titles'][$taxonomy->name]['noindex'] = $taxonomy->name;
						}
					}
				}

				update_option('siteseo_titles_option_name', $title_options);
				wp_send_json_success();
				
				break;
				
			case 'advanced':
				$data = map_deep(wp_unslash($_POST['data']), 'sanitize_text_field');
				$advanced_options = get_option('siteseo_advanced_option_name');

				$title_options = get_option('siteseo_titles_option_name', []);				
				$title_options['titles_archives_author_noindex'] = isset($data['author_noindex']) ? $data['author_noindex'] : '';

				$advanced_options['advanced_attachments_file'] = isset($data['redirect_attachment']) ? $data['redirect_attachment'] : '';
				$advanced_options['advanced_category_url'] = isset($data['category_url']) ? $data['category_url'] : '';
				$advanced_options['appearance_universal_metabox_disable'] = isset($data['universal_seo_metabox']) ? '' : '1';
				$advanced_options['appearance_universal_metabox'] = isset($data['universal_seo_metabox']) ? '1' : '';
				
				update_option('siteseo_titles_option_name', $title_options);
				update_option('siteseo_advanced_option_name', $advanced_options);
				wp_send_json_success();
				break;
		}
	}
	
	static function dismiss_intro(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('siteseo_manage')){
			wp_send_json_error(__('You do not have required permission to edit this file.', 'siteseo'));
		}
		
		update_option('siteseo_dismiss_intro', time());
	}
	
	static function save_universal_metabox(){
		check_ajax_referer('siteseo_universal_nonce', 'security');
		
		if(!current_user_can('siteseo_manage') || !siteseo_user_can_metabox()){
			wp_send_json_error(__('You do not have required permission to edit this file.', 'siteseo'));
		}
		
		if(empty($_POST['post_id'])){
			wp_send_json_error(__('Post ID not found', 'siteseo'));
		}
		
		$post_id = sanitize_text_field(wp_unslash($_POST['post_id']));

		if(!current_user_can('edit_post', $post_id)){
			wp_send_json_error(__('You do not have required permission to edit this file.', 'siteseo'));
		}

		$post = get_post($post_id);
		
		\SiteSEO\Metaboxes\Settings::save_metabox($post_id, $post);
	}
	
	static function resolve_variables(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('siteseo_manage')){
			wp_send_json_error(__('You do not have required permission to edit this file.', 'siteseo'));
		}

		if(empty($_POST['content']) || empty($_POST['post_id'])){
			wp_send_json_error(__('The required content or ID is empty', 'siteseo'));
		}
		
		global $post, $wp_query;
		
		$post_id = (int) sanitize_text_field(wp_unslash($_POST['post_id']));
		$content = sanitize_text_field(wp_unslash($_POST['content']));
		
		if(!current_user_can('edit_post', $post_id)){
			wp_send_json_error(__('You do not have permission to access this post', 'siteseo'));
		}

		$tmp_post = $post;
		$post = get_post($post_id);
		$replaced_content = \SiteSEO\TitlesMetas::replace_variables($content, true);
		$post = $tmp_post;

		wp_send_json_success($replaced_content);
	}
	
	static function clear_indexing_history(){
		check_ajax_referer('siteseo_admin_nonce', 'nonce');
		
		if(!current_user_can('manage_options')){
			wp_send_json_error(__('You do not have required permission to clear indexing history.', 'siteseo'));
		}
		
		global $siteseo;
		
		$indexing_history = $siteseo->instant_settings;
    
		if(is_array($indexing_history) && isset($indexing_history['indexing_history'])){
			unset($indexing_history['indexing_history']);
			update_option('siteseo_instant_indexing_option_name', $indexing_history);
		}
    
		wp_send_json_success();
	}
	
	static function close_update_notice(){
		check_ajax_referer('siteseo_promo_nonce', 'security');

		if(!current_user_can('manage_options')){
			wp_send_json_error('You don\'t have privilege to close this notice!');
		}

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
}
