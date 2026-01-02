<?php

if (!defined('ABSPATH')){
    exit;
}

/* function cookieadmin_died(){
	print_r(error_get_last());
}
register_shutdown_function('cookieadmin_died'); */

// Checks if we are to update ?
function cookieadmin_update_check(){
	global $wpdb;

	$current_version = get_option('cookieadmin_version');
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == COOKIEADMIN_VERSION){
		return true;
	}

	// Save the new Version
	update_option('cookieadmin_version', COOKIEADMIN_VERSION);
	
}

function cookieadmin_load_plugin(){
		
	// Check if the installed version is outdated
	cookieadmin_update_check();
	
	///////////////////////////
	// Common loading
	///////////////////////////
	
	if(wp_doing_ajax()){
		add_action('wp_ajax_cookieadmin_ajax_handler', 'cookieadmin_ajax_handler');
		add_action('wp_ajax_nopriv_cookieadmin_ajax_handler', 'cookieadmin_ajax_handler');
	}
	
	add_filter( 'kses_allowed_protocols', 'cookieadmin_kses_allowed_protocols');
	
	///////////////////////////
	// Admin loading
	///////////////////////////
	
	if(is_admin()){
		return cookieadmin_load_plugin_admin();
	}
	
	///////////////////////////
	// Enduser loading
	///////////////////////////
	
	add_action('wp_enqueue_scripts', '\CookieAdmin\Enduser::enqueue_scripts');
	
	// Insert Cookie blocker in the head.
	//add_action('send_headers', '\CookieAdmin\Enduser::cookieadmin_block_cookie_init_php', 100);
	// add_action('init', '\CookieAdmin\Enduser::cookieadmin_block_cookie_head_js', 0);
	
	//add Cookie Banner to user page
	if(!cookieadmin_is_editor_mode()){
		add_action('wp_footer', '\CookieAdmin\Enduser::cookieadmin_show_banner');
	}
	
	add_filter('script_loader_tag', '\CookieAdmin\Enduser::check_if_cookies_allowed', 10, 3);
	
}

function cookieadmin_load_plugin_admin(){
	
	global $cookieadmin, $cookieadmin_settings;
	
	if(!is_admin() || !current_user_can('administrator')){
		return false;
	}
	
	add_action('admin_enqueue_scripts', '\CookieAdmin\Admin::enqueue_scripts');
	
	add_action('admin_menu', '\CookieAdmin\Admin::cookieadmin_plugin_menu');
	
	// === Plugin Update Notice === //
	$plugin_update_notice = get_option('softaculous_plugin_update_notice', []);
	$available_update_list = get_site_transient('update_plugins'); 
	$plugin_path_slug = 'cookieadmin/cookieadmin.php';

	if(
		!empty($available_update_list) &&
		is_object($available_update_list) && 
		!empty($available_update_list->response) &&
		!empty($available_update_list->response[$plugin_path_slug]) && 
		(empty($plugin_update_notice) || empty($plugin_update_notice[$plugin_path_slug]) || (!empty($plugin_update_notice[$plugin_path_slug]) &&
		version_compare($plugin_update_notice[$plugin_path_slug], $available_update_list->response[$plugin_path_slug]->new_version, '<')))
	){
		add_action('admin_notices', '\CookieAdmin\Admin::plugin_update_notice');
		add_filter('softaculous_plugin_update_notice', '\CookieAdmin\Admin::plugin_update_notice_filter');
	}
	// === Plugin Update Notice END === //
	
}

function cookieadmin_is_editor_mode(){
	
	if (isset($_GET['pagelayer-live']) || isset($_GET['fl_builder'])) {
		return true;
	}
	
	if(isset($_GET['vc_action']) && $_GET['vc_action'] == 'vc_inline'){
		return true;
	}
	
	if(isset($_GET['elementor-preview']) || (isset($_GET['action']) && $_GET['action'] == 'elementor')){
		return true;
	}

	return false;
	
}

function cookieadmin_ajax_handler(){
	
	$cookieadmin_fn = (!empty($_REQUEST['cookieadmin_act']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_act'])) : '');
	
	if(empty($cookieadmin_fn)){
		wp_send_json_error(array('message' => 'Action not posted'));
	}
	
	// Define a whitelist of allowed functions
	$user_allowed_actions = array();
	
	$admin_allowed_actions = array(
		'scan_cookies' => 'cookieadmin_scan_cookies',
		'cookieadmin-auto-categorize' => 'cookieadmin_auto_configure_cookies',
		'cookieadmin-edit-cookie' => 'cookieadmin_edit_cookies',
		'close-update-notice' => 'close_plugin_update_notice'
	);
	
	$general_actions = array(
		'categorize_cookies' => 'cookieadmin_categorize_cookies',
	);
	
	if(array_key_exists($cookieadmin_fn, $user_allowed_actions)){
		
		check_ajax_referer('cookieadmin_js_nonce', 'cookieadmin_security');
		header_remove('Set-Cookie');
		call_user_func('\CookieAdmin\Enduser::'.$user_allowed_actions[$cookieadmin_fn]);
		
	}elseif(array_key_exists($cookieadmin_fn, $admin_allowed_actions)){
		
		check_ajax_referer('cookieadmin_admin_js_nonce', 'cookieadmin_security');
	 
		if(!current_user_can('administrator')){
			wp_send_json_error(array('message' => 'Sorry, but you do not have permissions to perform this action'));
		}
		
		call_user_func('\CookieAdmin\Admin::'.$admin_allowed_actions[$cookieadmin_fn]);
		
	}elseif(array_key_exists($cookieadmin_fn, $general_actions)){
		
		check_ajax_referer('cookieadmin_js_nonce', 'cookieadmin_security');
		header_remove('Set-Cookie');
		call_user_func($general_actions[$cookieadmin_fn]);
		
	}else{
		wp_send_json_error(array('message' => 'Unauthorized action'));
	}
	
}

// Load policies from the file and database and merge them.
function cookieadmin_load_policy(){
	global $cookieadmin_policies;
	
	$policy = get_option('cookieadmin_consent_settings', array());
	
	if(empty($policy) || !is_array($policy)){
		$policy = array();
	}
	
	if(!file_exists(COOKIEADMIN_DIR.'assets/cookie/policies.php')){
		return $policy;
	}
	
	include_once(COOKIEADMIN_DIR.'assets/cookie/policies.php');
	$j_policy = $cookieadmin_policies;
	// print_r($j_policy);
	
	if(empty($j_policy) || !is_array($j_policy)){
		return $policy;
	}

	return array_replace_recursive($j_policy, $policy);
}

function cookieadmin_load_strings(){
	
	$cookieadmin_powered_by_html = '<div class="cookieadmin-poweredby"><a href="https://cookieadmin.net/?utm_source=wpplugin&utm_medium=footer" target="_blank"><span>[[powered_by]]</span> [[logo_svg]]</a></div>';
	
	$cookieadmin_powered_by_html = apply_filters('cookieadmin_powered_by_html', $cookieadmin_powered_by_html);
	
	
	return [
			'powered_by_html' => $cookieadmin_powered_by_html,
			'logo_svg' => cookieadmin_logo_svg(),
			'plugin_url' => esc_url(COOKIEADMIN_PLUGIN_URL),
			'powered_by' => __('Powered by', 'cookieadmin'),
			'show_more' => __('show more', 'cookieadmin'),
			'reconsent' => __('Re-consent', 'cookieadmin'),
			'cookie_preferences' => __('Cookie Preferences', 'cookieadmin'),
			'remark_standard' => __('Standard', 'cookieadmin'),
			'remark' => __('Remark', 'cookieadmin'),
			'none' => __('None', 'cookieadmin'),
			'necessary_cookies' => __('Necessary Cookies', 'cookieadmin'),
			'necessary_cookies_desc' => __('Necessary cookies enable essential site features like secure log-ins and consent preference adjustments. They do not store personal data.', 'cookieadmin'),
			'functional_cookies' => __('Functional Cookies', 'cookieadmin'),
			'functional_cookies_desc' => __('Functional cookies support features like content sharing on social media, collecting feedback, and enabling third-party tools.', 'cookieadmin'),
			'analytical_cookies' => __('Analytical Cookies', 'cookieadmin'),
			'analytical_cookies_desc' => __('Analytical cookies track visitor interactions, providing insights on metrics like visitor count, bounce rate, and traffic sources.', 'cookieadmin'),
			'advertisement_cookies' => __('Advertisement Cookies', 'cookieadmin'),
			'advertisement_cookies_desc' => __('Advertisement cookies deliver personalized ads based on your previous visits and analyze the effectiveness of ad campaigns.', 'cookieadmin'),
			'unclassified_cookies' => __('Unclassified Cookies', 'cookieadmin'),
			'unclassified_cookies_desc' => __('Unclassified cookies are cookies that we are in the process of classifying, together with the providers of individual cookies.', 'cookieadmin'),
		];
}

//Loads consent data from file
function cookieadmin_load_consent_template($policy, $view){
	
	if(!file_exists(COOKIEADMIN_DIR.'assets/cookie/template.json')){
		return false;
	}
	
	$content = json_decode(file_get_contents(COOKIEADMIN_DIR.'assets/cookie/template.json'), true);
	
	if(empty($content)){
		return false;
	}
	
	$template = array();
	$template[$view] = $content['cookieadmin_layout'][$policy['cookieadmin_layout']];
	$template[$view] .= $content['cookieadmin_modal'][$policy['cookieadmin_modal']];
	$template[$view] .= $content['cookieadmin_reconsent'];
	
	$cookieadmin_strings = cookieadmin_load_strings();
	
	foreach($cookieadmin_strings as $ck => $cv){
		$template[$view] = str_replace('[['.$ck.']]', $cv, $template[$view]);
	}
	
	$template[$view] = apply_filters('cookieadmin_consent_banner_template', $template[$view]);
	
	return $template;
	
}

// Still in progress| No use for now.
function cookieadmin_compare_consent_id($consent_id) {
	
	if (strlen($consent_id) !== 32) {
        return false;
    }
	
	// Split into random part and signature
    $random_part = substr($consent_id, 0, 16);
    $provided_signature = substr($consent_id, 16, 16);
    
    // Recompute the HMAC
    $expected_hmac = hash_hmac('sha256', $random_part . $domain, $secret_key);
    $expected_signature = substr($expected_hmac, 0, 16);
	
    return hash_equals($provided_signature, $expected_signature);
}

function cookieadmin_r_print($array){
	echo '<pre>';
	print_r($array);
	echo '</pre>';
}

function cookieadmin_load_cookies_csv($cookie_names = array(), $like = 0) {
    global $wpdb;
		
	$cookies_list = [];
	
    $csv_file = COOKIEADMIN_DIR . 'assets/open-cookie-database/list.csv';
	
    // Check if file exists
    if ( ! file_exists( $csv_file ) ) {
		return new WP_Error( 'csv_missing', 'The cookie CSV file is missing: '.$csv_file );
    }
    
    if ( ( $handle = fopen( $csv_file, 'r' ) ) !== FALSE ) {
		
		$cookies_list = [];

        $headers = fgetcsv( $handle, 10000, ",", "\"", "\\" );

        while ( ( $data = fgetcsv( $handle, 10000, ",", "\"", "\\" ) ) !== FALSE ) {
            // 0: cookie_id, 1: Platform, 2: Category, 3: Cookie / Data Key name,
            // 4: Domain, 5: Description, 6: Retention period, 7: Data Controller,
            // 8: User Privacy & GDPR Rights Portals, 9: Wildcard match

            $cookie_id    = isset( $data[0] ) ? trim( $data[0] ) : '';
            $cookie_name  = isset( $data[3] ) ? trim( $data[3] ) : '';
            $platform     = isset( $data[1] ) ? trim( $data[1] ) : '';
            $category     = isset( $data[2] ) ? trim( $data[2] ) : '';
            $domain       = isset( $data[4] ) ? trim( $data[4] ) : '';
            $description  = isset( $data[5] ) ? trim( $data[5] ) : '';
            $retention    = isset( $data[6] ) ? trim( $data[6] ) : '';
            $wildcard     = isset( $data[9] ) ? (int) $data[9] : 0;
            $patterns     = isset( $data[10] ) ? trim($data[10]) : '';

            if ( empty( $cookie_id ) || empty( $cookie_name ) ) {
                continue;
            }
			
			if(!empty($cookie_names)){
				
				if(!empty($like)){
					
					$matched = 0;
					foreach($cookie_names as $prefix){
						if (substr($cookie_name, 0, strlen($prefix)) === $prefix) {
							$matched = 1;
							break;
						}
					}
					
					if(empty($matched)){
						continue;
					}
					
				}else{
					if(!in_array($cookie_name, $cookie_names)){
						continue;
					}
				}
			}

			// Add the row to the current batch
			$cookies_list[] = [
				'cookie_id' => $cookie_id,
				'cookie_name' => $cookie_name,
				'platform' => $platform,
				'category' => $category,
				'domain' => $domain,
				'description' => $description,
				'retention' => $retention,
				'wildcard' => $wildcard,
				'patterns' => $patterns
			];
        }

        fclose( $handle );
		
    } else {
		return new WP_Error( 'csv_open_fail', 'Failed to open Cookies CSV file: '.$csv_file );
    }
	
    return $cookies_list;
}

function cookieadmin_categorize_cookies($cookies = []){
	
	global $cookieadmin_lang, $cookieadmin_error, $cookieadmin_msg, $wpdb;
	
	if(!empty($_REQUEST['cookieadmin_cookies'])){
		
		$raw_cookies = json_decode( wp_unslash( $_REQUEST['cookieadmin_cookies'] ), true );

		if ( is_array( $raw_cookies ) ) {
			$sanitized_cookies = [];

			array_walk( $raw_cookies, function( $value, $key ) use ( &$sanitized_cookies ) {
				$sanitized_key = sanitize_key( $key );
				$sanitized_cookies[ $sanitized_key ] = sanitize_text_field($value);
			} );
			
			unset($raw_cookies);
		}
	}else{
		$sanitized_cookies = $cookies;
	}

	
	if(empty($sanitized_cookies)){
		return [
            'success' => false,
            'data'    => null,
            'error'   => 'Please provide valid cookie names.',
        ];
	}
	
	$cookies_info = cookieadmin_load_cookies_csv(array_keys($sanitized_cookies));
	
	if(empty($cookies_info) || is_wp_error($cookies_info)){
		return [
	            'success' => false,
	            'data'    => null,
	            'error'   => 'Failed to load Cookies list',
	        ];
	}
	
	foreach($cookies_info as $info){
		$sanitized_cookies[$info['cookie_name']]['source'] = !empty($info['domain']) ? $info['domain'] : "unknown";
		$sanitized_cookies[$info['cookie_name']]['category'] = !empty($info['category']) ? strtolower($info['category']) : "un_c";
		$sanitized_cookies[$info['cookie_name']]['description'] = !empty($info['description']) ? $info['description'] : "unknown";
		$sanitized_cookies[$info['cookie_name']]['duration'] = !empty($info['retention']) ? $info['retention'] : "unknown";
		$sanitized_cookies[$info['cookie_name']]['platform'] = !empty($info['platform']) ? $info['platform'] : "unknown";;
	}
	
	if(wp_doing_ajax()){
		wp_send_json_success($sanitized_cookies);
	}
	
	return $sanitized_cookies;
}

function cookieadmin_is_pro(){
	return defined('COOKIEADMIN_PREMIUM');
}

function cookieadmin_kses_allowed_html(){
	
	$allowed_tags = wp_kses_allowed_html( 'post' );

	// Add input tag for cookie consent form
	$allowed_tags['input'] = array(
		'type'    => true,
		'name'    => true,
		'value'   => true,
		'class'   => true,
		'id'      => true,
		'checked' => true,
		'disabled' => true,
		'placeholder' => true,
	);

	$allowed_tags['defs'] = array();
	
	$allowed_tags['a'] = array(
		'href' => true,
	);
	
	$allowed_tags['br'] = array();
	
	$allowed_tags['image'] = array(
		'href' => true,
		'id' => true,
		'width' => true,
		'height' => true,
	);

	$allowed_tags['use'] = array(
		'href' => true,
		'transform' => true,
		'x' => true,
		'y' => true,
	);

	$allowed_tags['path'] = array(
		'class' => true,
		'd'     => true,
	);

	$allowed_tags['style'] = array(
		'fill' => true,
	);

	$allowed_tags['svg'] = array(
		'class' => true,
		'xmlns' => true,
		'viewbox' => true,
		'width' => true,
		'height' => true,
	);
	
	return $allowed_tags;
}

function cookieadmin_kses_allowed_protocols($protocols){
	
	global $cookieadmin_settings;
	
	if(empty($cookieadmin_settings['hide_powered_by'])){
		$protocols[] = 'data';
	}

	return $protocols;
}

function cookieadmin_logo_svg(){
	
	return '<svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 829 137" width="90" height="15"><defs><image width="512" height="512" id="img1" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAMAAADDpiTIAAAAAXNSR0IB2cksfwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAv1QTFRFAAAAAB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05AB05BrNJ4wAAAP90Uk5TABFos+L/YQ8aivXziRw7qvw5BVzLyloEFH3n5nsTJJf2lSI9sP2tAlbJxlIBEHbg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"/><image  width="84" height="82" id="img2" href="data:image/png;base64,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"/></defs><style>.a{fill:#001d39}.b{fill:#985e23}</style><use  href="#img1" transform="matrix(.269,0,0,.269,.483,.483)"/><path class="a" d="m174.9 108.6q-10 0-18.2-4.7-8.1-4.8-12.9-13-4.7-8.3-4.7-18.3 0-10.2 4.6-18.4 4.6-8.2 12.6-12.8 8-4.7 18-4.7 5.4 0 10.6 1.3 5.2 1.3 8.9 3.7l-2.7 15.5q-7.2-2.7-13-2.7-8 0-12.3 4.7-4.3 4.6-4.3 13.3 0 8.5 4.5 13.3 4.6 4.7 12.7 4.7 3.1 0 5.8-0.6 2.7-0.6 6.6-2.2l2.8 15.8q-8.7 5.1-19 5.1zm52.4-0.8q-8.4 0-15-3.3-6.5-3.3-10.2-9.1-3.6-5.9-3.6-13.5 0-7.6 3.7-13.4 3.7-5.9 10.2-9.1 6.6-3.2 15-3.2 8.5 0 15 3.3 6.6 3.2 10.2 9 3.6 5.8 3.6 13.4 0 7.7-3.7 13.6-3.6 5.8-10.2 9.1-6.5 3.2-15 3.2zm0.2-15q4.1 0 6.7-3 2.7-3.1 2.7-7.9 0-4.8-2.7-7.8-2.6-3.1-6.7-3.1-4.1 0-6.8 3.1-2.6 3-2.6 7.8 0 4.8 2.6 7.9 2.7 3 6.8 3zm61.8 15q-8.4 0-15-3.3-6.5-3.3-10.2-9.1-3.6-5.9-3.6-13.5 0-7.6 3.7-13.4 3.7-5.9 10.2-9.1 6.6-3.2 15-3.2 8.5 0 15 3.3 6.6 3.2 10.2 9 3.6 5.8 3.6 13.4 0 7.7-3.7 13.6-3.6 5.8-10.2 9.1-6.5 3.2-15 3.2zm0.2-15q4.1 0 6.7-3 2.7-3.1 2.7-7.9 0-4.8-2.7-7.8-2.6-3.1-6.7-3.1-4.1 0-6.8 3.1-2.6 3-2.6 7.8 0 4.8 2.6 7.9 2.7 3 6.8 3zm85.8 15q-6.7 0-10.8-1.7-4-1.8-6.6-5.9-2.6-4.1-4.6-11.7l-5.9 4.7v13.8h-21.2l0.1-65.9 21.2-3.1-0.1 34.2 25.7-21 11.5 15.7-16.1 11.2q1.2 7.6 3.4 10.6 2.3 3 6.7 3 1.8 0 4.3-0.6l-2 16.4q-3 0.3-5.6 0.3zm32.3 0q-8.4 0-13.1-5.4-4.7-5.4-4.7-14.7v-30.8h21.2v29.9q0 2.3 1.2 3.6 1.3 1.2 3.3 1.2 2 0 4.1-1.2l1.1 13.9q-2.2 1.7-5.8 2.6-3.5 0.9-7.3 0.9zm-7.2-58.4q-4.7 0-8-2.9-3.2-3-3.2-7.3 0-4.3 3.4-7.2 3.4-2.9 7.8-2.9 4.6 0 7.9 3 3.4 3 3.4 7.1 0 4.4-3.4 7.3-3.4 2.9-7.9 2.9zm52.5 58.4q-8.3 0-14.8-3.3-6.4-3.4-10-9.2-3.6-5.9-3.6-13.3 0-7.6 3.7-13.5 3.7-5.9 10-9.1 6.3-3.2 14-3.2 7.4 0 12.6 3.3 5.3 3.2 7.9 8.8 2.6 5.5 2.6 12.4 0 1.8-0.2 2.6l-30 3.9q1.2 3.5 3.9 5.1 2.7 1.5 7.1 1.5 6.8 0 14.7-3.5l2.5 13.4q-9.3 4.1-20.4 4.1zm-8.6-29.8l15.5-3.4q-1.6-6.3-7.6-6.3-3.6 0-5.7 2.7-2.1 2.7-2.2 7z"/><path class="b" d="m477.5 107l19-68.8h30.4l19.9 68.8h-23.5l-2.6-13.2h-17.8l-2.9 13.2zm27.5-25.5h13.3l-6.5-30.1zm68.9 26.3q-6.8 0-12.4-3.6-5.5-3.6-8.7-9.5-3.2-6-3.2-12.7 0-7.1 3.3-13 3.4-6 9-9.4 5.7-3.4 12.1-3.4 5.8 0 9.8 2.4 4.1 2.4 6.6 7.7v-25.2l21.1-3.1v69h-21.1v-9.3q-4.7 10.1-16.5 10.1zm6.6-15.8q4.2 0 7-3 2.9-3 2.9-7v-0.4q0-3.9-3-6.7-3-2.9-6.9-2.9-4.2 0-7.1 3-2.8 2.9-2.8 7 0 4.2 2.9 7.1 3 2.9 7 2.9zm41.5 15v-49.4h20.1v10.2q3.4-5.9 7.7-8.7 4.4-2.9 10.4-2.9 5.9 0 10.2 3 4.4 2.9 6.6 7.9 6.4-10.9 18.6-10.9 5.8 0 10.1 3.1 4.4 3.1 6.8 9 2.4 5.8 2.4 13.7v25h-21.1v-25q0-5.1-1.8-7.7-1.7-2.6-4.8-2.6-3.4 0-5.9 2.9-2.4 2.9-2.4 7v25.4h-20.9v-25q0-5.1-1.8-7.7-1.7-2.6-4.9-2.6-3.4 0-5.8 2.9-2.4 2.9-2.4 7v25.4zm119.6 0.8q-8.4 0-13.1-5.4-4.7-5.4-4.7-14.7v-30.8h21.2v29.9q0 2.3 1.2 3.6 1.3 1.2 3.3 1.2 2 0 4.1-1.2l1.1 13.9q-2.2 1.7-5.8 2.6-3.5 0.9-7.3 0.9zm-7.2-58.4q-4.7 0-8-2.9-3.2-3-3.2-7.3 0-4.3 3.4-7.2 3.4-2.9 7.8-2.9 4.6 0 7.9 3 3.4 3 3.4 7.1 0 4.4-3.4 7.3-3.4 2.9-7.9 2.9zm26.6 57.6v-49.4h20.1v10.2q5.8-11.6 18.4-11.6 9.6 0 15 6.9 5.5 6.8 5.5 18.9v25h-21v-27.1q0-4.3-1.9-6.6-1.8-2.3-5.1-2.3-2.4 0-4.7 1.6-2.3 1.5-3.8 4.4-1.4 2.9-1.4 6.6v23.4z"/><use  href="#img2" x="30" y="28"/></svg>';
}