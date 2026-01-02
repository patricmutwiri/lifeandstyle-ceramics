<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

if(!defined('FILEORGANIZER_VERSION')){
	die('Hacking Attempt!');
}

add_action('wp_ajax_fileorganizer_file_folder_manager', 'fileorganizer_ajax_handler');
function fileorganizer_ajax_handler(){
	global $fileorganizer;
	
	// Check nonce
	check_admin_referer( 'fileorganizer_ajax' , 'fileorganizer_nonce' );
	
	// Check capability
	$capability = fileorganizer_get_capability();
	
	if(!current_user_can($capability)){
		return;
	}

	// Load saved settings
	$url = site_url();
	$path = !empty($fileorganizer->options['root_path']) ? fileorganizer_cleanpath($fileorganizer->options['root_path']) : $fileorganizer->default_path;
	
	if(!defined('FILEORGANIZER_PRO') || empty($fileorganizer->options['disable_path_restriction'])){
		$path = fileorganizer_validate_path($path) ? $path : $fileorganizer->default_path;
	}

	if(is_multisite()){
		$url = network_home_url();
	}

	// Set restrictions
	$restrictions = [
		array(
			'pattern' => '/.tmb/',
			'read' => false,
			'write' => false,
			'hidden' => true,
			'locked' => false,
		),
		array(
			'pattern' => '/.quarantine/',
			'read' => false,
			'write' => false,
			'hidden' => true,
			'locked' => false,
		)
	];
	
	// Hide .htaccess?
	if(!empty($fileorganizer->options['hide_htaccess'])) {
		$restrictions[] = array(
			'pattern' => '/.htaccess/',
			'read' => false,
			'write' => false,
			'hidden' => true,
			'locked' => false
		);
	}

	$disable_commands = array('help', 'preference', 'hide', 'netmount');	

	$config = array();

	// Configure elfinder
	$config[0] = array(
		'driver' => 'LocalFileSystem',
		'path' => $path,
		'URL' => $url,
		'winHashFix' => DIRECTORY_SEPARATOR !== '/',
		'accessControl' => 'access',
		'acceptedName' => 'validName',
		'uploadMaxSize' => 0,
		'disabled' => $disable_commands,
		'attributes' => $restrictions
	);

	// Is trash enabled?
	if (!empty($fileorganizer->options['enable_trash'])) {
		$uploads_dir = wp_upload_dir();

		$trash_dir = fileorganizer_cleanpath($uploads_dir['basedir'].'/fileorganizer/.trash');
		$trash_glob = glob($trash_dir . '-*/', GLOB_ONLYDIR);

		if(!empty($trash_glob) && !empty($trash_glob[0])){
			$trash_dir = $trash_glob[0];
			$trash_name = basename($trash_dir);
		}

		if(empty($trash_name) || !file_exists($trash_dir)){
			$randomness = wp_generate_password(12, false);
			$trash_dir .= '-' . $randomness;
			$trash_name = basename($trash_dir);
			mkdir($trash_dir . '/.tmb', 0755, true);
		}

		if(!file_exists($trash_dir . '/index.php')){
			file_put_contents($trash_dir . '/index.php', '<?php //Silence is golden');
			chmod($trash_dir . '/index.php', 0444);
		}

		// Configure trash
		$config[1] = array(
			'id' => '1',
			'driver' => 'Trash',
			'path' => $trash_dir,
			'tmbURL' => $uploads_dir['baseurl'].'/fileorganizer/'.$trash_name.'/.tmb/',
			'winHashFix' => DIRECTORY_SEPARATOR !== '/',
			'uploadDeny' => array(''),
			'uploadAllow' => array(''),
			'uploadOrder' => array('deny', 'allow'),
			'accessControl' => 'access',
			'disabled' => $disable_commands,
			'attributes' => $restrictions,
		);
		$config[0]['trashHash'] = 't1_Lw';
	}

	$config = apply_filters('fileorganizer_manager_config', $config);

	$el_config = array(
		'locale' => 'zh_CN',
		'debug' => false,
		'roots' => $config,
		'bind' => array(
			'mkdir' => function(&$path, &$name, $src, $elfinder, $volume){
				global $fileorganizer;

				if(empty($fileorganizer->options['enable_trash']) || empty($name['added']) || !is_array($name['added']) || empty($volume)){
					return;
				}

				foreach($name['added'] as $added){
					$dir_path = $volume->realpath($added['hash']);

					if(empty($dir_path) || strpos($dir_path, '.trash-') === FALSE){
						return;
					}

					if(!file_exists($dir_path . '/index.php')){
						file_put_contents($dir_path . '/index.php', '<?php //Silence is golden');
						chmod($dir_path . '/index.php', 0444);
					}
				}
			},
			'upload.presave' => function(&$path, &$name, $src, $elfinder, $volume) {
				
				if( !current_user_can('activate_plugins') ) {
					$validate = wp_check_filetype( $name );
				
					if( $validate['type'] == false ){
						return array( 'error' => __('File type is not allowed.', 'fileorganizer'));
					}
				}
				
				if( !current_user_can('unfiltered_html') ) {
					
					$content = file_get_contents($src);
					
					$is_xss = '';
					
					while(true){
						$found = fileorganizer_xss_content($content);
						
						if(strlen($found) > 0){
							
							// Check if the file is an SVG then allow 'svg', 'xml' tags
							if( in_array($found, array('svg', 'xml')) &&
								( mime_content_type($src) == 'image/svg+xml' ||
								in_array(pathinfo($name, PATHINFO_EXTENSION), array('svg', 'svgz') ) )
							){
								$content = str_replace($found, '', $content);
								continue;
							}
							
							$is_xss = $found;
						}
						
						break;
					}
					
					// Unfiltered_html cap needs to be checked
					if(strlen($is_xss) > 0 ){
						return array( 'error' => __('Following not allowed content found ').' : -"'. $is_xss .'" in file '.$name);
					}
					
				}
				
				return true;
			}

		)
	);

	// Load autoloader
	require FILEORGANIZER_DIR.'/manager/php/autoload.php';

	// Load FTP driver?
	if(defined('FILEORGANIZER_PRO') && !empty($fileorganizer->options['enable_ftp'])){	
		elFinder::$netDrivers['ftp'] = 'FTP';
	}
	
	// run elFinder
	$connector = new elFinderConnector(new elFinder($el_config));
	$connector->run();
}

// Change fileorganizer theme
add_action('wp_ajax_fileorganizer_switch_theme', 'fileorganizer_switch_theme');
function fileorganizer_switch_theme(){
	
	//Check nonce
	check_admin_referer( 'fileorganizer_ajax' , 'fileorganizer_nonce' );

	if(!current_user_can('manage_options')){
		wp_send_json(array( 'error' => 'Permision Denide!' ), 400);
	}

	$theme = fileorganizer_optpost('theme');

	$options = get_option('fileorganizer_options', array());
	$options['theme'] = $theme;
    update_option('fileorganizer_options', $options);
	
	$theme_path = !empty($theme) ? '/themes/'.$theme : '';
	
	// Return requested theme path
	$path = FILEORGANIZER_URL.'/manager'.$theme_path.'/css/theme.css';

	$response = array(
		'success' => true,
		'stylesheet' => $path
	);

	wp_send_json($response, 200);
}

add_action('wp_ajax_fileorganizer_hide_promo', 'fileorganizer_hide_promo');
function fileorganizer_hide_promo(){
	
	//Check nonce
	check_admin_referer( 'fileorganizer_promo_nonce' , 'security' );
	
	// Save value in minus
	update_option('fileorganizer_promo_time', (0 - time()));
	die('DONE');
}

// As per the JS specification
function fileorganizer_unescapeHTML($str){
	$replace = [
		'#93' => ']',
		'#91' => '[',
		//'#61' => '=',
		'lt' => '<',
		'gt' => '>',
		'quot' => '"',
		//'amp' => '&',
		'#39' => '\'',
		'#92' => '\\'
	];
	
	foreach($replace as $k => $v){
		$str = str_replace('&'.$k.';', $v, $str);
	}
	return $str;
}

// Check for XSS codes in our shortcodes submitted
function fileorganizer_xss_content($data){
	$data = fileorganizer_unescapeHTML($data);
	$data = preg_split('/\s/', $data);
	$data = implode('', $data);
	//echo $data;
	
	// For PDF file
	if(preg_match('/\/JavaScript/is', $data)){
		return '/JavaScript';
	}
	
	// This is also for PDF file
	if(preg_match('/\/JS/is', $data)){
		return '/JS';
	}
	
	if(preg_match('/["\']javascript\:/is', $data)){
		return 'javascript';
	}
	
	if(preg_match('/["\']vbscript\:/is', $data)){
		return 'vbscript';
	}
	
	if(preg_match('/\-moz\-binding\:/is', $data)){
		return '-moz-binding';
	}
	
	if(preg_match('/expression\(/is', $data)){
		return 'expression';
	}
	
	if(preg_match('/\<(iframe|frame|script|style|link|applet|embed|xml|svg|object|layer|ilayer|meta)/is', $data, $matches)){
		return $matches[1];
	}
	
	// These events not start with on
	$not_allowed = array('click', 'dblclick', 'mousedown', 'mousemove', 'mouseout', 'mouseover', 'mouseup', 'load', 'unload', 'change', 'submit', 'reset', 'select', 'blur', 'focus', 'keydown', 'keypress', 'keyup', 'afterprint', 'beforeprint', 'beforeunload', 'error', 'hashchange', 'message', 'offline', 'online', 'pagehide', 'pageshow', 'popstate', 'resize', 'storage', 'contextmenu', 'input', 'invalid', 'search', 'mousewheel', 'wheel', 'drag', 'dragend', 'dragenter', 'dragleave', 'dragover', 'dragstart', 'drop', 'scroll', 'copy', 'cut', 'paste', 'abort', 'canplay', 'canplaythrough', 'cuechange', 'durationchange', 'emptied', 'ended', 'loadeddata', 'loadedmetadata', 'loadstart', 'pause', 'play', 'playing', 'progress', 'ratechange', 'seeked', 'seeking', 'stalled', 'suspend', 'timeupdate', 'volumechange', 'waiting', 'toggle', 'animationstart', 'animationcancel', 'animationend', 'animationiteration', 'auxclick', 'beforeinput', 'beforematch', 'beforexrselect', 'compositionend', 'compositionstart', 'compositionupdate', 'contentvisibilityautostatechange', 'focusout', 'focusin', 'fullscreenchange', 'fullscreenerror', 'gotpointercapture', 'lostpointercapture', 'mouseenter', 'mouseleave', 'pointercancel', 'pointerdown', 'pointerenter', 'pointerleave', 'pointermove', 'pointerout', 'pointerover', 'pointerrawupdate', 'pointerup', 'scrollend', 'securitypolicyviolation', 'touchcancel', 'touchend', 'touchmove', 'touchstart', 'transitioncancel', 'transitionend', 'transitionrun', 'transitionstart', 'MozMousePixelScroll', 'DOMActivate', 'afterscriptexecute', 'beforescriptexecute', 'DOMMouseScroll', 'willreveal', 'gesturechange', 'gestureend', 'gesturestart', 'mouseforcechanged', 'mouseforcedown', 'mouseforceup', 'mouseforceup');
	
	$not_allowed = implode('|', $not_allowed);
		
	if(preg_match('/(on|onwebkit)+('.($not_allowed).')=/is', $data, $matches)){
		return $matches[1]+$matches[2];
	}
	
	return;
}

function fileorganizer_close_update_notice(){

	if(!wp_verify_nonce($_GET['security'], 'fileorganizer_promo_nonce')){
		wp_send_json_error('Security Check failed!');
	}

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
add_action('wp_ajax_fileorganizer_close_update_notice', 'fileorganizer_close_update_notice');