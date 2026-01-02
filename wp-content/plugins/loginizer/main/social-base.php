<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

class Loginizer_Social_Base{

	public static $error = [];
	public static $test = false;
	public static $ref = '';
	public static $interim_login = '';
	public static $provider = '';
	public static $storage;

	protected static function login_user($user, $username = '', $password = ''){
		
		if(isset($user) && is_object($user) && property_exists($user, 'ID') && empty(self::$test)){
			clean_user_cache(get_current_user_id());
			clean_user_cache($user->ID);
			wp_clear_auth_cookie();
			
			do_action('authenticate', $user, $user->user_login, '');
			
			// If the user has enabled limit concurrent sessions
			if(defined('LOGINIZER_PRO_VERSION')){
				$limit_session = apply_filters('loginizer_pro_limit_sessions', $user);
				if(!empty($limit_session) && is_wp_error($limit_session)){
					self::$error['concurrent_logins'] = $limit_session->get_error_message();
					return false;
				}
			}

			wp_set_current_user($user->ID, $user->user_login);
			wp_set_auth_cookie($user->ID, true, is_ssl());
			do_action('wp_login', $user->user_login, $user);
			update_user_caches($user);

			return true;
		}
		
		return false;
	}
	
	/**
	 * Creates a User account
	 *
	 * @param  mixed[] $data Data we get from the Social App
	 * @return void
	 */
	protected static function register_account($data){
		global $loginizer;

		$username = $data['first_name'] . $data['last_name'];

		if(empty($username)){
			$parsed_email = explode('@', $data['email']);
			
			if(!empty($parsed_email[0])){
				$username = preg_replace('/[^A-Za-z0-9\-]/', '', $parsed_email[0]);
			}
		}
		
		$username = str_replace(' ', '', strtolower($username));
		$username = sanitize_user($username, true);

		$i = 1;
		while(username_exists($username)){
			$username .= $i;
			$i++;
		}

		$password = wp_generate_password(12);
		$userdata = [
			'user_login' => sanitize_text_field($username),
			'user_pass' => $password,
			'user_email' => sanitize_email($data['email']),
			'role' => (!empty($loginizer['social_settings']['general']['default_role']) ? sanitize_text_field($loginizer['social_settings']['general']['default_role']) : 'subscriber'),
			'show_admin_bar_front' => (!empty($loginizer['social_settings']['general']['hide_admin_bar']) ? false  : true),
		];

		$user_id = wp_insert_user($userdata);

		// TODO: Handle Error here.
		if(is_wp_error($user_id)){
			self::$error['registration_failed'] = __('Something went wrong while creating the user', 'loginizer'). $user_id->get_error_message();
			return;
		}

		if(empty($user_id)){
			self::$error['registration_failed'] =  __('Unable to register your account, try again later!', 'loginizer');
			self::close_tab();
			return;
		}

		update_user_option($user_id, 'default_password_nag', true, true); // This will show alert to user to change the password.
		$user = get_user_by('ID', $user_id);

		// Following the default WordPress registration flow, we will notify user about the creation of new account.
		do_action('register_new_user', $user_id);
		
		// Save avatar if possible.
		$tried_to_download = get_user_meta($user->ID, 'loginizer_avatar_download', true);
		if(!empty($data['photoURL']) && !empty($loginizer['social_settings']['general']['save_avatar']) && empty($tried_to_download)){
			self::save_avatar($data['photoURL'], $user->ID);
		}

		// Logging In the new user.
		self::login_user($user);

		// Closing the tab and redirecting to the admin.
		$redirect_to = admin_url();

		self::close_tab();

	}
	
	/**
	 * Close the Tab or redirects back to the Login Page.
	 *
	 * @param  string $redirect_to URL where the user should be redirected, leave empty if want to redirect to admin.
	 * @return void
	 */
	protected static function close_tab(){
		global $loginizer;

		// Check if the URL is safe to use.
		$redirect_to = '';
		if(!empty(self::$ref)){
			$redirect_to = self::handle_redirect(self::$ref);
		}

		$target_window = 'same'; // If to redirect or to close the poup
		$is_interim = ''; // If interim add query string as a identifier
		if(self::$interim_login == 'lz'){
			$target_window = 'popup';
			$is_interim = '?interim_login=lz';
		} else if(!empty(self::$test)){
			$target_window = 'popup';

			$redirect_to .= '&provider='.self::$provider.'&test=1';
		}else if(!empty($loginizer['social_settings']['general']['target_window'])){
			$target_window = $loginizer['social_settings']['general']['target_window'];
		}

		if(empty($redirect_to) || $redirect_to == admin_url()){
			$redirect_to = admin_url($is_interim);
		}

		if($target_window === 'same'){
			wp_safe_redirect($redirect_to);
			die();
		}

		if(isset(self::$interim_login) && self::$interim_login === 'lz' && is_user_logged_in()){
            echo esc_html__('Login Successful', 'loginizer');
        }

		echo '<script>
		window.opener.location.href="'.wp_validate_redirect(wp_sanitize_redirect($redirect_to)).'";
		window.close();
		</script>';

		die();
	}
	
	// Download the avatar and returns Image ID
	protected static function save_avatar($url, $user_id){

		update_user_meta($user_id, 'loginizer_avatar_download', true);

		$tmp_file = self::download_avatar($url);

		if(is_wp_error($tmp_file) || empty($tmp_file)){
			return $tmp_file;
		}

		$mime = wp_get_image_mime($tmp_file);

		$allowed_mime = [
			'image/webp' => 'webp',
			'image/tiff' => 'tif',
			'image/gif'  => 'gif',
			'image/jpeg' => 'jpg',
			'image/bmp'  => 'bmp',
			'image/png'  => 'png',
		];

		if(!array_key_exists($mime, $allowed_mime)){
			error_log('Loginizer Error: ' . __('The avatar has unsupported mime type.', 'loginizer'));
			return;
		}

		$upload_dir = wp_upload_dir();
		$avatar_upload_dir = trailingslashit($upload_dir['basedir']) . 'lz_avatars';

		if(!wp_mkdir_p($avatar_upload_dir)){
			error_log('Loginizer Error: ' . __('Unable to create Directory to save avatars', 'loginizer'));
			return;
		}

		$avatar_file = wp_hash($user_id) .'.'. $allowed_mime[$mime];
		$avatar_file = wp_unique_filename($avatar_upload_dir, $avatar_file);
		$avatar_file_path = trailingslashit($avatar_upload_dir) . $avatar_file;

		$new_file = copy($tmp_file, $avatar_file_path);
		unlink($tmp_file);

		if(empty($new_file)){
			error_log('Loginizer Error: ' . __('Unable to copy the avatar from the tmp file', 'loginizer'));
			return;
		}

		$avatar_url = $upload_dir['baseurl'] . '/lz_avatars/' . basename($avatar_file);

		$attachment = [
			'guid' => $avatar_url,
			'post_title' => '',
			'post_content' => '',
			'post_author' => $user_id,
			'post_status' => 'private',
			'post_mime_type' => $mime,
		];

		$attachment_id = wp_insert_attachment($attachment, $avatar_file_path);

		if(is_wp_error($attachment_id)){
			unlink($avatar_file_path);
			error_log('Loginizer Error: ' . __('Unable to create an attachment of the Avatar', 'loginizer'));
			return;
		}

		global $wpdb, $blog_id;

		include_once(ABSPATH . 'wp-admin/includes/image.php');

        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $avatar_file_path));

		update_post_meta($attachment_id, '_wp_attachment_wp_user_avatar', $user_id);
		update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id) . 'lz_avatar', $attachment_id);

	}

	private static function download_avatar($url){

		if(empty($url)){
			error_log('Loginizer Error: ' . __('The URL provided to download avatar is empty', 'loginizer'));
			return;
		}

		$tmp_file = uniqid();

		if(empty($tmp_file)){
			error_log('Loginizer Error: ' . __('Unable to create a tmp file!', 'loginizer'));
			return;
		}

		$response = wp_remote_get($url, [
			'timeout' => 30,
			'stream' => true,
			'filename' => $tmp_file,
		]);

		if(is_wp_error($response)){
			unlink($tmp_file);
			error_log('Loginizer Error: ' . __('Download of the avatar failed!', 'loginizer'));
			return;
		}

		$code = wp_remote_retrieve_response_code($response);

		if($code != 200){
			unlink($tmp_file);
			error_log('Loginizer Error: ' . sprintf(__('Download of the avatar failed with error code %s!', 'loginizer'), esc_html($code)));
			return;
		}

		$content_md5 = wp_remote_retrieve_header($response, 'content-md5');
		if(!empty($content_md5)){
			if(!function_exists('verify_file_md5')){
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$md5_check = verify_file_md5($tmp_file, $content_md5);
			if(is_wp_error($md5_check)){
				unlink($tmpfname);
				return $md5_check;
			}
		}

		return $tmp_file;
	}
	
	protected static function handle_redirect($url){

		if(empty($url)){
			return '';
		}
		
		$url = rawurldecode($url);
		$parsed_url = parse_url($url);

		// If we have something in redirect to, then redirect to that page
		if(!empty($parsed_url['query'])){
			preg_match('/(redirect_to|redirect)=([^&]*)/', $parsed_url['query'], $redirect_url);

			if(!empty($redirect_url[2])){
				return rawurldecode($redirect_url[2]);
			}
		}

		// Reloading the page wont show the admin page so we need to redirect it to the admin page.
		if($parsed_url['scheme'].'://'.$parsed_url['host'] . $parsed_url['path'] == wp_login_url()){
			return admin_url();
		}

		// If none of the above happens then we will just make the page reload.
		return $url;
	}
	
	static function trigger_error(){
		global $loginizer;

		if(empty(self::$error)){
			return;
		}
		
		// If we are testing we can just die,
		// becuase we don't want the user to be redirected anywhere
		if(!empty(self::$test) || (!empty(self::$storage) && self::$storage->get('test'))){
			wp_die(wp_kses_post(current(self::$error)));
		}

		if(loginizer_is_whitelisted()){
			$loginizer['ip_is_whitelisted'] = 1;
		}

		do_action('wp_login_failed', '');
		
		self::error_state();
		self::close_tab(); // This will redirect to the appropriate page.
	}

	// Stores the errors to be used once redirected.
	static function error_state(){
		global $loginizer;
		
		$data = [
			'errors' => self::$error,
			'retries_left' => $loginizer['retries_left']
		];
		
		$identifier = uniqid('lz_social', true);
		set_site_transient($identifier, $data, 300);
		
		setcookie('lz_social_error', $identifier, time() + 300, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}
}
