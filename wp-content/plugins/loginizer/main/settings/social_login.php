<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

function loginizer_social_login(){

	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	if(defined('LOGINIZER_PREMIUM') && file_exists(LOGINIZER_PRO_DIR . '/main/settings/social_login.php')){
		include_once LOGINIZER_PRO_DIR . '/main/settings/social_login.php';
	}

	$script_data = [
		'nonce' => wp_create_nonce('loginizer_social_nonce'),
		'ajax_url' => admin_url('admin-ajax.php')
	];

	wp_localize_script('loginizer_social_script', 'loginizer_social', $script_data);

	loginizer_page_header('Social Login Settings');
	
	do_action('loginizer_pro_social_auth_notice');

	echo '<div class="tabs-wrapper" style="margin-bottom:10px; width:100%;">
	<nav class="nav-tab-wrapper">
		<a href="?page=loginizer_social_login" class="nav-tab '.(isset($_GET['settings']) ? '' : 'nav-tab-active').'">Provider</a>
		<a href="?page=loginizer_social_login&settings=global" class="nav-tab '.(isset($_GET['settings']) ? 'nav-tab-active' : '').'">Settings</a>
  	</nav>
	</div>';

	// Handling Settings Page
	if(!empty($_GET['settings'])){
		loginizer_general_settings();

		return;
	}

	// Contains the list of all the providers
	include_once LOGINIZER_DIR . '/main/login-providers.php';

	if(empty($loginizer_login_providers)){
		echo '<p>' . esc_html__('Something went wrong can\'t load social App details', 'loginizer') . '</p>';
		return;
	}

	// Handling Provider settings.
	if(!empty($_GET['provider'])){
		$provider_page = sanitize_text_field($_GET['provider']);
		loginizer_provider_settings($provider_page, $loginizer_login_providers);

		return;
	}

	$provider_settings = get_option('loginizer_provider_settings', []);
	$provider_order = get_option('loginizer_social_order', []);

	$providers = [];
	
	// Sorting according to the saved order of the providers.
	if(!empty($provider_order)){
		foreach($provider_order as $key => $num){
			if(!empty($loginizer_login_providers[$key])){
				$providers[$key] = $loginizer_login_providers[$key];
			}
		}

		// TODO:: There is some issue here.
		// To check if something new was added and will be added at the last.
		$remaining = array_diff(array_keys($loginizer_login_providers), array_keys($providers));

		if(!empty($remaining)){
			foreach($remaining as $new_social){
				$providers[$new_social] = $loginizer_login_providers[$new_social];
			}
		}

	} else {
		$providers = $loginizer_login_providers;
	}

	// ------ HTML STARTS HERE -------- //

	echo '<div class="loginizer-social-wrapper">';

	// Image used here is supposed to be same as the key in png format.
	foreach($providers as $key => $provider){

		$action_class = '';
		$action_text = __('Get Started');
		if(array_key_exists($key, $provider_settings)){
			$action_text = __('Settings');
		}

		$action_to = admin_url('admin.php?page=loginizer_social_login&provider='.$key);
		if(!empty($provider['premium']) && !defined('LOGINIZER_PREMIUM')){
			$action_text = __('Upgrade to Pro');
			$action_class = ' button-primary'; // Have added space here at the start of the string
			$action_to = 'https://loginizer.com/pricing';
		}

		echo '<div class="loginizer-social-provider" data-provider="'.esc_attr($key).'">
			<div class="loginizer-social-logo" style="background-color:'.esc_attr($provider['color']).';">
				<div class="loginizer-social-grip">
					<svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 100" fill="#d3d3d3" width="12" height="25"><path d="m12.5 27c-4.7 0-8.5-3.8-8.5-8.5 0-4.7 3.8-8.5 8.5-8.5 4.7 0 8.5 3.8 8.5 8.5 0 4.7-3.8 8.5-8.5 8.5zm0 25c-4.7 0-8.5-3.8-8.5-8.5 0-4.7 3.8-8.5 8.5-8.5 4.7 0 8.5 3.8 8.5 8.5 0 4.7-3.8 8.5-8.5 8.5zm0 28c-4.7 0-8.5-3.8-8.5-8.5 0-4.7 3.8-8.5 8.5-8.5 4.7 0 8.5 3.8 8.5 8.5 0 4.7-3.8 8.5-8.5 8.5zm25-53c-4.7 0-8.5-3.8-8.5-8.5 0-4.7 3.8-8.5 8.5-8.5 4.7 0 8.5 3.8 8.5 8.5 0 4.7-3.8 8.5-8.5 8.5zm0 25c-4.7 0-8.5-3.8-8.5-8.5 0-4.7 3.8-8.5 8.5-8.5 4.7 0 8.5 3.8 8.5 8.5 0 4.7-3.8 8.5-8.5 8.5zm0 28c-4.7 0-8.5-3.8-8.5-8.5 0-4.7 3.8-8.5 8.5-8.5 4.7 0 8.5 3.8 8.5 8.5 0 4.7-3.8 8.5-8.5 8.5z"/></svg>
				</div>';
				if(array_key_exists($key, $provider_settings) && loginizer_show_social_status($loginizer_login_providers[$key])){
					if(!empty($provider_settings[$key]['enabled']) && !empty($provider_settings[$key]['tested'])){
						echo '<div class="loginizer-social-provider-state loginizer-social-provider-state-enabled">Enabled</div>';
					} elseif(!empty($provider_settings[$key]['enabled']) && empty($provider_settings[$key]['tested'])){
						echo '<div class="loginizer-social-provider-state loginizer-social-provider-state-pending">Pending</div>';
					} else {
						echo '<div class="loginizer-social-provider-state loginizer-social-provider-state-disabled">Disabled</div>';
					}
				}

				echo '<div class="loginizer-social-saving-order">'.esc_html__('Saving Order', 'loginizer').'...</div>
				<img src="'.esc_url(LOGINIZER_URL . '/assets/images/social/'.$key.'.png').'" height="60" alt="'.esc_attr($provider['name']).'" style="'.(($provider['name'] === 'Google') ? 'background-color:white; padding:3px;' : '').'"/>
			</div>
			<div class="loginizer-social-provider-action"><h2>'.esc_html($provider['name']).'</h2>
			<a href="'.esc_url($action_to).'" class="button'.esc_attr($action_class).'">'.esc_html($action_text).'</a></div>
		</div>';
	}

	echo '</div>';

	loginizer_page_footer();
}

// Settings page for every Social Provider.
function loginizer_provider_settings($provider, $provider_defaults){

	$provider_settings = get_option('loginizer_provider_settings', []);

	if(empty($provider_settings)){
		update_option('loginizer_social_login_url', wp_login_url());
	}

	if(!empty($_GET['test'])){
		$provider_settings[$provider]['tested'] = true;
		update_option('loginizer_provider_settings', $provider_settings);
	}

	// Checking for the Pro version for the specific Provider
	if(!defined('LOGINIZER_PREMIUM') && (empty($provider_defaults[$provider]) || !empty($provider_defaults[$provider]['premium']))){
		loginizer_feature_available($provider_defaults[$provider]['name'] . ' Social Login Provider');
		loginizer_page_footer();
		return;
	}

	if(!empty($_POST['lz_save_social_provider'])){
		if(!check_admin_referer('loginizer_social_nonce', 'security')){
			$error[] = __('Security Check failed', 'loginizer');
		} else {
			if(empty($provider_settings[$provider])){
				$provider_settings[$provider] = []; // initializing
				$provider_settings[$provider]['tested'] = false;
			}
			
			// Need to make the user to test the login again if the key gets changed, if keys gets dirty
			if(
				(isset($provider_settings[$provider]['client_id']) && $provider_settings[$provider]['client_id'] != sanitize_text_field(wp_unslash($_POST['client_id']))) || 
				(isset($provider_settings[$provider]['client_secret']) && $provider_settings[$provider]['client_secret'] != sanitize_text_field(wp_unslash($_POST['client_secret'])))
			){
				$provider_settings[$provider]['tested'] = false;
			}

			$provider_settings[$provider]['client_id'] = !empty($_POST['client_id']) ? sanitize_text_field(wp_unslash($_POST['client_id'])) : '';
			$provider_settings[$provider]['client_secret'] = !empty($_POST['client_secret']) ? sanitize_text_field(wp_unslash($_POST['client_secret'])) : '';
			$provider_settings[$provider]['enabled'] = !empty($_POST['provider_enabled']) ? true : false;

			if(defined('LOGINIZER_PREMIUM')){
				$provider_settings = apply_filters('loginizer_pro_save_provider_settings', $provider_settings, $provider);
			}

			$provider_settings[$provider]['button_style'] = !empty($_POST['button_style']) ? lz_optpost('button_style') : 'default';
			
			// Making sure we save enable only when required settings are there
			if(!empty($provider_settings[$provider]['enabled'])){
				if(
					(empty($provider_settings[$provider]['client_secret']) || 
					empty($provider_settings[$provider]['client_id'])) &&
					empty($provider_settings[$provider]['loginizer_social_key'])
				){
					$provider_settings[$provider]['tested'] = false;
					$provider_settings[$provider]['enabled'] = false;
				}
			}

			update_option('loginizer_provider_settings', $provider_settings);

			$saved = true;
		}

		if(!empty($error)){
			lz_report_error($error);
		}
		
		if(!empty($saved)){
			echo '<div id="message" class="updated"><p>'
				. esc_html__('The settings were saved successfully', 'loginizer')
				. '</p></div><br />';
		}
	}

	if(!empty($provider_settings[$provider]) && !empty($provider_settings[$provider]['enabled']) && empty($provider_settings[$provider]['tested'])){
		$social_nonce = wp_create_nonce('loginizer_social_check');

		wp_register_script('loginizer-social-test', '', ['jquery'], LOGINIZER_VERSION, ['strategy' => false, 'in_footer' => true]);
		wp_enqueue_script('loginizer-social-test');
		wp_add_inline_script('loginizer-social-test', '
			function loginizer_auth_test_popup() {
				let screenX = window.screenX !== undefined ? window.screenX : window.screenLeft,
				screenY = window.screenY !== undefined ? window.screenY : window.screenTop,
				outer_width = window.outerWidth !== undefined ? window.outerWidth : document.documentElement.clientWidth,
				outer_height = window.outerHeight !== undefined ? window.outerHeight : document.documentElement.clientHeight - 22,
				target_width = 600,
				target_height = 600,
				right = parseInt(screenY + (outer_height - target_height) / 2.5, 10),
				left = parseInt(screenX + (outer_width - target_width) / 2, 10),
				attributes = [];

				if (target_width !== null) {
					attributes.push("width=" + target_width);
				}
				if (target_height !== null) {
					attributes.push("height=" + target_height);
				}
				attributes.push("left=" + left);
				attributes.push("top=" + right);
				attributes.push("scrollbars=1");

				var social_window = window.open("'.esc_url(wp_login_url()).'?lz_social_provider='.esc_html($provider).'&test=true&social_security='.esc_html($social_nonce).'&ref='.esc_url($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']).'", "socialWindow", attributes.join(","));

				if(window.focus){
					social_window.focus();
				}
			return false;
			}'
		);
	}

	echo '<a href="'.esc_url(admin_url('admin.php?page=loginizer_social_login')).'">&#8592; '.esc_html__('Go Back', 'loginizer').'</a>';

	if(!empty($provider_settings[$provider]) && !empty($provider_settings[$provider]['enabled']) && empty($provider_settings[$provider]['tested']) && empty($provider_settings[$provider]['loginizer_social_key'])){
		echo '<div class="loginizer-social-test-alert">
			<h3>'.sprintf(esc_html__('Test %s to be able to use it', 'loginizer'), esc_html($provider_defaults[$provider]['name'])).'</h3>
			<p>'.esc_html__('You have added/updated the keys of this provider so you will need to test it to be able to use it.', 'loginizer').'</p>
			<p>'.esc_html__('Please use the same email with which you have created this account.', 'loginizer').'</p>
			<button class="button button-primary" id="loginizer-test-provider-btn" onclick="loginizer_auth_test_popup()">'.esc_html__('Test Now', 'loginizer').'</button>
		</div>';
	}

	echo '<div style="display:flex; justify-content:space-between;">
	<div style="width:"50%;">
	<h3>'.esc_html($provider_defaults[$provider]['name']).' Settings</h3>

	<form method="POST">
	<table class="form-table">
	<tr>
		<th scope="row"><label for="provider_enabled">Enable</label></th>
		<td><input type="checkbox" name="provider_enabled" id="provider_enabled" '.(!empty($provider_settings[$provider]['enabled']) ? 'checked' : '').' value="1"/></td>
	</tr>';

	$display_keys = '';
	// Loginizer Social Auth Input
	$lz_social_key_providers = function_exists('loginizer_pro_social_auth_providers') ? loginizer_pro_social_auth_providers() : [];

	if(in_array($provider, $lz_social_key_providers)){
		if(empty($provider_settings[$provider])){
			$provider_settings[$provider] = [];
		}

		do_action('loginizer_pro_social_api_settings', $provider_settings[$provider], $provider);

		if(!empty($provider_settings[$provider]['loginizer_social_key'])){
			$display_keys = 'display:none;';
		}
	}

	echo '<tr style="'.esc_attr($display_keys).'">
		<th scope="row"><label for="loginizer_social_client_id">Client ID</label></th>
		<td><input type="text" name="client_id" id="loginizer_social_client_id" value="'.(!empty($provider_settings[$provider]['client_id']) ? esc_attr($provider_settings[$provider]['client_id']) : '').'"/></td>
	</tr>
	<tr style="'.esc_attr($display_keys).'">
		<th scope="row"><label for="loginizer_social_client_secret">Client Secret</label></th>
		<td><input type="text" name="client_secret" id="loginizer_social_client_secret" value="'.(!empty($provider_settings[$provider]['client_secret']) ? esc_attr($provider_settings[$provider]['client_secret']) : '').'"/></td>
	</tr>
	<tr>
		<th scope="row"><label for="button_style_default">Button Style</label></th>
		<td>';
			$styles = $provider_defaults[$provider]['styles'];
			
			foreach($styles as $style => $style_css){
				echo '<label style="padding:20px 20px 20px 0"><input type="radio" name="button_style" id="button_style_'.esc_attr($style).'" value="'.esc_attr($style).'" '.((empty($provider_settings[$provider]['button_style'])) ? 'checked' : checked($provider_settings[$provider]['button_style'], $style, false)).'/>'.esc_attr(ucfirst($style)).'</label>';
			}
		echo '</td>
	</tr>';
	
	if($provider == 'MicrosoftGraph'){
		echo '<tr style="'.esc_attr($display_keys).'">
			<th scope="row"><label for="loginizer_social_ms_account_type">'.esc_html__('Account Type', 'loginizer').'</label>
			<p class="description">'.esc_html__('Which type of accounts you want to allow to be able to signin', 'loginizer').'</p></th>
			<td style="display:block;"><label><input type="radio" name="account_type" value="organizations" '.(!empty($provider_settings[$provider]['account_type']) ? checked($provider_settings[$provider]['account_type'], 'organizations', false) : '').'/>'.esc_html__('Accounts in any Organizational directory', 'loginizer').'</label></td>
			<td style="display:block;"><label><input type="radio" name="account_type" value="common" '.(!empty($provider_settings[$provider]['account_type']) ? checked($provider_settings[$provider]['account_type'], 'common', false) : '').'/>'.esc_html__('Accounts in any Organizational directory and Personal Microsoft accounts', 'loginizer').'</label></td>
			<td style="display:block;"><label><input type="radio" name="account_type" value="consumers" id="loginizer_social_ms_account_type" '.(!empty($provider_settings[$provider]['account_type']) ? checked($provider_settings[$provider]['account_type'], 'consumers', false) : '').'/>'.esc_html__('Personal Microsoft accounts only', 'loginizer').'</label></td>
		</tr>';
	}

	echo '</table>';
	wp_nonce_field('loginizer_social_nonce', 'security');
	echo '<input type="submit" name="lz_save_social_provider" value="Save Settings" class="button button-primary"/>
	</form>
	</div>
	<div class="loginizer-provider-docs" style="width:46%;">';
		if(function_exists('loginizer_how_to_'.$provider)){
			call_user_func('loginizer_how_to_'.$provider);
		} else {
			echo '<p>No Documentation Present</p>';
		}
	echo '</div>
	</div>
	';
	
	loginizer_page_footer();
}

// General Settings Page
function loginizer_general_settings(){
	
	$social_settings = get_option('loginizer_social_settings', []);

	if(!empty($_POST['security'])){
		if(!check_admin_referer('loginizer_social_nonce', 'security')){
			$error[] = __('Security Check failed', 'loginizer');
		} else if(isset($_POST['general_settings'])){
			$social_settings['general']['target_window'] = !empty($_POST['target_window']) ? lz_optpost('target_window') : false;

		} else if(isset($_POST['login_settings'])){
			$social_settings['login']['login_form'] = lz_optpost('login_form');
			$social_settings['login']['button_style'] = lz_optpost('button_style');
			$social_settings['login']['button_shape'] = lz_optpost('button_shape');
			$social_settings['login']['button_position'] = lz_optpost('button_position');
			$social_settings['login']['alignment'] = lz_optpost('alignment');
			$social_settings['login']['button_alignment'] = lz_optpost('button_alignment');
		}
		
		$social_settings = apply_filters('loginizer_social_general_settings', $social_settings);
		$social_settings = map_deep($social_settings, 'sanitize_text_field');

		update_option('loginizer_social_settings', $social_settings);
	}
	
	// NOTE:: The tab switching is done purly through CSS so, if you add any other tab in here then use css, just look for the class of already present tabs in the CSS and you will know.
	echo '
	<div class="loginizer-social-login-settings">
	<div class="loginizer-v-tab">
		<label><input type="radio" name="lz-social-setting-type" value="general" checked/>'.esc_html__('General', 'loginizer').'</label>
		<label><input type="radio" name="lz-social-setting-type" value="login"/>'.esc_html__('Login Form', 'loginizer').'</label>
		<label><input type="radio" name="lz-social-setting-type" value="woocommerce"/>'.esc_html__('WooCommerce', 'loginizer').(!defined('LOGINIZER_PREMIUM') ? '<span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span>' : ''). '</label>
		<label><input type="radio" name="lz-social-setting-type" value="comment"/>'.esc_html__('Comment', 'loginizer').(!defined('LOGINIZER_PREMIUM') ? '<span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span>' : '').'</label>';
		
		if(is_plugin_active('ultimate-member/ultimate-member.php')){
			echo '<label><input type="radio" name="lz-social-setting-type" value="ultimate-member"/>'.esc_html__('Ultimate Member', 'loginizer').(!defined('LOGINIZER_PREMIUM') ? '<span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span>' : '').'</label>';
		}

	echo '</div>

	<div class="lz-v-tab-content">
	<div id="lz-social-general-settings">
		<h2>General Settings</h2>
		<form method="POST">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="save_avatar">Target Window</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="target_window" id="target_window" value="same" '.(!empty($social_settings['general']['target_window']) && $social_settings['general']['target_window'] == 'same' ? 'checked' : (empty($social_settings['general']['target_window']) ? 'checked' : '')).'/>'.esc_html__('Prefer Same window', 'loginizer').'</label>
					<label><input type="radio" name="target_window" id="target_window" value="popup" '.(!empty($social_settings['general']['target_window']) && $social_settings['general']['target_window'] == 'popup' ? 'checked' : '').'/>'.esc_html__('Prefer Popup', 'loginizer').'</label>
					<p class="description">'.esc_html__('Where you want the App Authentication to be visible in', 'loginizer').'</p> 
				</td>
			</tr>';
			
			if(defined('LOGINIZER_PREMIUM')){
				echo '<tr>
					<th scope="row"><label for="save_avatar">Save Avatar</label></th>
					<td class="loginizer-general-settings">
						<input type="checkbox" name="save_avatar" id="save_avatar" '.(!empty($social_settings['general']['save_avatar']) ? 'checked' : '').'/>
						<p class="description">'.esc_html__('On registration save and update user avatar, if possible', 'loginizer').'</p> 
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="register_new">Create User</label></th>
					<td class="loginizer-general-settings">
						<input type="checkbox" name="register_new" id="register_new" '.(!empty($social_settings['general']['register_new']) ? 'checked' : '').'/>
						<p>'.esc_html__('If the user is not present then should loginizer create new user?', 'loginizer').'</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="default_role">New User Role</label></th>
					<td class="loginizer-general-settings">
						<select name="default_role" id="default_role">';
						wp_dropdown_roles(!empty($social_settings['general']['default_role']) ? $social_settings['general']['default_role'] : 'subscriber');
						echo '</select>
						<p>'.esc_html__('What role you want to assign to the user created through social login?', 'loginizer').'</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="admin_bar">'.esc_html__('Hide Admin Bar', 'loginizer').'</label></th>
					<td class="loginizer-general-settings">
						<input type="checkbox" name="admin_bar" id="admin_bar" '.(!empty($social_settings['general']['admin_bar']) ? 'checked' : '').'/>
						<p>'.esc_html__('Do you want the new user to see the Admin Bar in the Front end? Checked means you want to hide it.', 'loginizer'). '</p>
					</td>
				</tr>';
			} else {
				echo '<tr>
					<th scope="row"><label>Save Avatar <span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span></label></th>
					<td class="loginizer-general-settings">
						<input type="checkbox" disabled/>
						<p class="description">'.esc_html__('On registration save and update user avatar, if possible', 'loginizer').'</p> 
					</td>
				</tr>
				<tr>
					<th scope="row"><label>Create User <span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span></label></th>
					<td class="loginizer-general-settings">
						<input type="checkbox" disabled>
						<p>'.esc_html__('If the user is not present then should loginizer create new user?', 'loginizer').'</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label>New User Role <span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span></label></th>
					<td class="loginizer-general-settings">
						<select disabled>
							<option>Subscriber</option>
						</select>
						<p>'.esc_html__('What role you want to assign to the user created through social login?', 'loginizer').'</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label>'.esc_html__('Hide Admin Bar', 'loginizer').' <span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span></label></th>
					<td class="loginizer-general-settings">
						<input type="checkbox" disabled/>
						<p>'.esc_html__('Do you want the new user to see the Admin Bar in the Front end? Checked means you want to hide it.', 'loginizer'). '</p>
					</td>
				</tr>';

			}
		echo '</table>';
		wp_nonce_field('loginizer_social_nonce', 'security');
		echo '<input type="submit" class="button button-primary" name="general_settings" value="Save Settings"/>
		</form>
	</div>
	<div id="lz-social-login-settings">
		<h2>Login Form</h2>
		<form method="POST">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Login Form', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="checkbox" name="login_form" id="login_form" value="yes" '.(!empty($social_settings['login']['login_form']) ? 'checked' : '').'/></label>
					<p>'.esc_html__('Do you want to show social buttons on WordPress login form.', 'loginizer'). '</p>
				</td>
			</tr>';
			
			if(defined('LOGINIZER_PREMIUM')){
				echo '<tr>
					<th scope="row"><label for="registration_form">'.esc_html__('Registration Form', 'loginizer').'</label></th>
					<td class="loginizer-general-settings">
						<label><input type="checkbox" name="registration_form" id="registration_form" value="yes" '.(!empty($social_settings['login']['registration_form']) ? 'checked' : '').'/></label>
						<p>'.esc_html__('Do you want to show social login on the WordPress registration form.', 'loginizer'). '</p>
					</td>
				</tr>';
			} else {
				echo '<tr>
					<th scope="row"><label>'.esc_html__('Registration Form', 'loginizer').'<span class="dashicons dashicons-lock" style="color:#f69d2e;" title="Available in Premium version"></span></label></th>
					<td class="loginizer-general-settings">
						<label><input type="checkbox" disabled/></label>
						<p class="disabled">'.esc_html__('Do you want to show social login on the WordPress registration form.', 'loginizer'). '</p>
					</td>
				</tr>';
			}
			
			echo '<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Button Style', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_style" id="button_style_full" value="full" '.(empty($social_settings['login']['button_style']) ? 'checked' : checked($social_settings['login']['button_style'], 'full', false)).'/>Full Length</label>
					<label><input type="radio" name="button_style" value="icon" '.(!empty($social_settings['login']['button_style']) ? checked($social_settings['login']['button_style'], 'icon', false) : '').'/>Icon</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_shape_square">'.esc_html__('Button Shape', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_shape" id="button_shape_square" value="square" '.(empty($social_settings['login']['button_shape']) ? 'checked' : checked($social_settings['login']['button_shape'], 'square', false)).'>'.esc_html__('Square', 'loginizer').'</label>
					<label><input type="radio" name="button_shape" value="circle" '.(!empty($social_settings['login']['button_shape']) ? checked($social_settings['login']['button_shape'], 'circle', false) : '').'>'.esc_html__('Pill/Circle', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_position">Button Position</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_position" id="button_position" value="below" '.(empty($social_settings['login']['button_position']) ? 'checked' : checked($social_settings['login']['button_position'], 'below', false)).'>Below</label>
					<label><input type="radio" name="button_position" value="below_plus" '.(!empty($social_settings['login']['button_position']) ? checked($social_settings['login']['button_position'], 'below_plus', false) : '').'>'.esc_html__('Below with Seperator', 'loginizer').'</label>
					<label><input type="radio" name="button_position" value="above" '.(!empty($social_settings['login']['button_position']) ? checked($social_settings['login']['button_position'], 'above', false) : '').'>'.esc_html__('Above', 'loginizer').'</label>
					<label><input type="radio" name="button_position" value="above_plus" '.(!empty($social_settings['login']['button_position']) ? checked($social_settings['login']['button_position'], 'above_plus', false) : '').'>'.esc_html__('Above with Seperator', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="social-alignment">'.esc_html__('Container Alignment', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="alignment" id="social-alignment" value="left" '.(empty($social_settings['login']['alignment']) ? 'checked' : checked($social_settings['login']['alignment'], 'left', false)).'>Left</label>
					<label><input type="radio" name="alignment" value="center" '.(!empty($social_settings['login']['alignment']) ? checked($social_settings['login']['alignment'], 'center', false) : '').'>'.esc_html__('Center', 'loginizer').'</label>
					<label><input type="radio" name="alignment" value="right" '.(!empty($social_settings['login']['alignment']) ? checked($social_settings['login']['alignment'], 'right', false) : '').'>'.esc_html__('Right', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="social-alignment">'.esc_html__('Button Alignment', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_alignment" id="social-alignment" value="left" '.(empty($social_settings['login']['button_alignment']) ? 'checked' : checked($social_settings['login']['button_alignment'], 'left', false)).'>Left</label>
					<label><input type="radio" name="button_alignment" value="center" '.(!empty($social_settings['login']['button_alignment']) ? checked($social_settings['login']['button_alignment'], 'center', false) : '').'>'.esc_html__('Center', 'loginizer').'</label>
					<label><input type="radio" name="button_alignment" value="right" '.(!empty($social_settings['login']['button_alignment']) ? checked($social_settings['login']['button_alignment'], 'right', false) : '').'>'.esc_html__('Right', 'loginizer').'</label>
				</td>
			</tr>
		</table>';
		wp_nonce_field('loginizer_social_nonce', 'security');
		echo '<input type="submit" class="button button-primary" name="login_settings" value="'.esc_html__('Save Settings', 'loginizer').'">
		</form>
	</div>
	<div id="lz-social-woocommerce-settings">
		<h2>WooCommerce Settings</h2>';
		loginizer_feature_available('Social login for WooCommerce');
		echo '<form method="POST" loginizer-premium-only="1">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Login Form', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="checkbox" name="login_form" id="login_form" value="login" '.(!empty($social_settings['woocommerce']['login_form']) ? 'checked' : '').'/></label>
					<p>'.esc_html__('Do you want to show social buttons on WooCommerce login form.', 'loginizer'). '</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Registration Form', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="checkbox" name="registration_form" id="registration_form" value="reg" '.(!empty($social_settings['woocommerce']['registration_form']) ? 'checked' : '').'/></label>
					<p>'.esc_html__('Do you want to show social login on the WooCommerce registration form.', 'loginizer'). '</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Button Style', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_style" id="button_style_full" value="full" '.(empty($social_settings['woocommerce']['button_style']) ? 'checked' : checked($social_settings['woocommerce']['button_style'], 'full', false)).'/>'.esc_html__('Full Length', 'loginizer').'</label>
					<label><input type="radio" name="button_style" value="icon" '.(!empty($social_settings['woocommerce']['button_style']) ? checked($social_settings['woocommerce']['button_style'], 'icon', false) : '').'/>'.esc_html__('Icon', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_shape_square">'.esc_html__('Button Shape', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_shape" id="button_shape_square" value="square" '.(empty($social_settings['woocommerce']['button_shape']) ? 'checked' : checked($social_settings['woocommerce']['button_shape'], 'square', false)).'>'.esc_html__('Square', 'loginizer').'</label>
					<label><input type="radio" name="button_shape" value="circle" '.(!empty($social_settings['woocommerce']['button_shape']) ? checked($social_settings['woocommerce']['button_shape'], 'circle', false) : '').'>'.esc_html__('Pill/Circle', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_position">'.esc_html__('Button Position', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_position" id="button_position" value="below" '.(empty($social_settings['woocommerce']['button_position']) ? 'checked' : checked($social_settings['woocommerce']['button_position'], 'below', false)).'>'.esc_html__('Below', 'loginizer').'</label>
					<label><input type="radio" name="button_position" value="below_plus" '.(!empty($social_settings['woocommerce']['button_position']) ? checked($social_settings['woocommerce']['button_position'], 'below_plus', false) : '').'>'.esc_html__('Below with Seperator', 'loginizer').'</label>
					<label><input type="radio" name="button_position" value="above" '.(!empty($social_settings['woocommerce']['button_position']) ? checked($social_settings['woocommerce']['button_position'], 'above', false) : '').'>'.esc_html__('Above', 'loginizer').'</label>
					<label><input type="radio" name="button_position" value="above_plus" '.(!empty($social_settings['woocommerce']['button_position']) ? checked($social_settings['woocommerce']['button_position'], 'above_plus', false) : '').'>'.esc_html__('Above with Seperator', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="social-alignment">'.esc_html__('Container Alignment', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="alignment" id="social-alignment" value="left" '.(empty($social_settings['woocommerce']['alignment']) ? 'checked' : checked($social_settings['woocommerce']['alignment'], 'left', false)).'>Left</label>
					<label><input type="radio" name="alignment" value="center" '.(!empty($social_settings['woocommerce']['alignment']) ? checked($social_settings['woocommerce']['alignment'], 'center', false) : '').'>'.esc_html__('Center', 'loginizer').'</label>
					<label><input type="radio" name="alignment" value="right" '.(!empty($social_settings['woocommerce']['alignment']) ? checked($social_settings['woocommerce']['alignment'], 'right', false) : '').'>'.esc_html__('Right', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="social-alignment">'.esc_html__('Button Alignment', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_alignment" id="social-alignment" value="left" '.(empty($social_settings['woocommerce']['button_alignment']) ? 'checked' : checked($social_settings['woocommerce']['button_alignment'], 'left', false)).'>Left</label>
					<label><input type="radio" name="button_alignment" value="center" '.(!empty($social_settings['woocommerce']['button_alignment']) ? checked($social_settings['woocommerce']['button_alignment'], 'center', false) : '').'>'.esc_html__('Center', 'loginizer').'</label>
					<label><input type="radio" name="button_alignment" value="right" '.(!empty($social_settings['woocommerce']['button_alignment']) ? checked($social_settings['woocommerce']['button_alignment'], 'right', false) : '').'>'.esc_html__('Right', 'loginizer').'</label>
				</td>
			</tr>
		</table>';
		wp_nonce_field('loginizer_social_nonce', 'security');
		echo '<input type="submit" class="button button-primary" name="woocommerce_settings" value="'.esc_html__('Save Settings', 'loginizer').'">
		</form>
	</div>
	<div id="lz-social-comment-settings">
		<h2>Comment Settings</h2>';
		loginizer_feature_available('Social Login for comment');
		echo '<form method="POST" loginizer-premium-only="1">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Show buttons', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="checkbox" name="enable_buttons" id="enable_buttons" value="yes" '.(!empty($social_settings['comment']['enable_buttons']) ? 'checked' : '').'/></label>
					<p>'.esc_html__('Do you want to show social buttons on Comments.', 'loginizer'). '</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_style_full">'.esc_html__('Button Style', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_style" id="button_style_full" value="full" '.(empty($social_settings['comment']['button_style']) ? 'checked' : checked($social_settings['comment']['button_style'], 'full', false)).'/>'.esc_html__('Full Length', 'loginizer').'</label>
					<label><input type="radio" name="button_style" value="icon" '.(!empty($social_settings['comment']['button_style']) ? checked($social_settings['comment']['button_style'], 'icon', false) : '').'/>'.esc_html__('Icon', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="button_shape_square">'.esc_html__('Button Shape', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_shape" id="button_shape_square" value="square" '.(empty($social_settings['comment']['button_shape']) ? 'checked' : checked($social_settings['comment']['button_shape'], 'square', false)).'>'.esc_html__('Square', 'loginizer').'</label>
					<label><input type="radio" name="button_shape" value="circle" '.(!empty($social_settings['comment']['button_shape']) ? checked($social_settings['comment']['button_shape'], 'circle', false) : '').'>'.esc_html__('Pill/Circle', 'loginizer').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="social-alignment">'.esc_html__('Container Alignment', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="alignment" id="social-alignment" value="left" '.(empty($social_settings['comment']['alignment']) ? 'checked' : checked($social_settings['comment']['alignment'], 'left', false)).'>Left</label>
					<label><input type="radio" name="alignment" value="center" '.(!empty($social_settings['comment']['alignment']) ? checked($social_settings['comment']['alignment'], 'center', false) : '').'>'.esc_html__('Center', 'loginizer-security').'</label>
					<label><input type="radio" name="alignment" value="right" '.(!empty($social_settings['comment']['alignment']) ? checked($social_settings['comment']['alignment'], 'right', false) : '').'>'.esc_html__('Right', 'loginizer-security').'</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="social-alignment">'.esc_html__('Button Alignment', 'loginizer').'</label></th>
				<td class="loginizer-general-settings">
					<label><input type="radio" name="button_alignment" id="social-alignment" value="left" '.(empty($social_settings['comment']['button_alignment']) ? 'checked' : checked($social_settings['comment']['button_alignment'], 'left', false)).'>Left</label>
					<label><input type="radio" name="button_alignment" value="center" '.(!empty($social_settings['comment']['button_alignment']) ? checked($social_settings['comment']['button_alignment'], 'center', false) : '').'>'.esc_html__('Center', 'loginizer').'</label>
					<label><input type="radio" name="button_alignment" value="right" '.(!empty($social_settings['comment']['button_alignment']) ? checked($social_settings['comment']['button_alignment'], 'right', false) : '').'>'.esc_html__('Right', 'loginizer').'</label>
				</td>
			</tr>
		</table>';
		wp_nonce_field('loginizer_social_nonce', 'security');
		echo '<input type="submit" class="button button-primary" name="comment_settings" value="'.esc_html__('Save Settings', 'loginizer').'"/>
		</form>
	</div>
	<div id="lz-social-ultimate-member-settings">
		<h2>'.esc_html__('Ultimate Member Settings', 'loginizer').'</h2>';
		loginizer_feature_available('Social Login for Ultimate Member');
		do_action('loginizer_pro_social_um_settings');
	echo '</div>
	</div>';

	loginizer_page_footer();
}

// Checks if we can show the Status of the Social App.
// As if Premium verison is disabled then the status should not be visible.
function loginizer_show_social_status($provider){
	if(!empty($provider['premium']) && !defined('LOGINIZER_PREMIUM')){
		return false;
	}

	return true;
}

function loginizer_how_to_linkedinopenid(){

	// NOTE: update the date of last updated of this doc if you make any change.
	echo '<div>
		<h2>'.esc_html__('Create LinkedIn App', 'loginizer').'</h2>
		<span><b>'.esc_html__('Last Updated','loginizer').':</b> 24th June 2024</span>
		<p>'.esc_html__('To allow your users to be able to login through their LinkedIn Account, you first need to create a LinkedIn App. For that follow the Project creation steps below.', 'loginizer').'</p>
		<ol>
			<li>'.esc_html__('Go to LinkedIn Apps page for that','loginizer').' <a href="https://www.linkedin.com/developers/apps" target="_blank">Navigate to https://www.linkedin.com/developers/apps</a></li>
			<li>'.esc_html__('Once you open the Apps page, click on Create App button, which will take you to fill a form.', 'loginizer').'</li>
			<li>'.esc_html__('On that form fill the App Name and LinkedIn page, if you dont have a linked in page then create once as its required to create a App.','loginizer').' <a href="https://www.linkedin.com/company/setup/new/" target="_blank">https://www.linkedin.com/company/setup/new/</a></li>
			<li>'.esc_html__('After you have filled the LinkedIn page details, upload your logo and read terms and conditions and check the field which say I have read and agreed to these terms and then click on Create App','loginizer').'</li>
			<li>'.esc_html__('You will be redirect to your App page in Product Tab, there look for "Sign In with LinkedIn using OpenID Connect" and click on Request Access, now go to the Auth tab as we need to access Oauth 2.0','loginizer').'</li>
			<li>'.esc_html__('On the Auth tab copy the Client ID and Client Secret and place those keys in Client ID and CLient Secret of the Loginizer LinkedIn Social provider respectively and enable it and save it','loginizer').'</li>
			<li>'.esc_html__('Now on LinkedIn Auth page you will need to add Redirect URL, for that add this URL there', 'loginizer').'<code>'.esc_url(wp_login_url()).'?lz_social_provider=LinkedInOpenID</code></li>
			<li>'.esc_html__('After that you can test in the Loginizer dashboard to see if it is working without any issue', 'loginizer').'</li>
		</ol>
	</div>';
}
