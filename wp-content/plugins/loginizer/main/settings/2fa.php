<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

// Loginizer - Two Factor Auth Page
function loginizer_page_2fa(){
	
	global $loginizer, $lz_error, $lz_env, $lz_roles, $lz_options, $saved_msgs;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	if(!loginizer_is_premium() && count($_POST) > 0){
		$lz_error['not_in_free'] = __('This feature is not available in the Free version. <a href="'.LOGINIZER_PRICING_URL.'" target="_blank" style="text-decoration:none; color:green;"><b>Upgrade to Pro</b></a>', 'loginizer');
		return loginizer_page_2fa_T();
	}

	$lz_roles = get_editable_roles();
	
	if(empty($lz_roles)){
		$lz_roles = array();
	}
	
	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginizer-options');
	}
	
	// Settings submitted
	if(isset($_POST['save_lz'])){
		
		// In the future there can be more settings
		$option['2fa_app'] = (int) lz_optpost('2fa_app');
		$option['2fa_email'] = (int) lz_optpost('2fa_email');
		$option['question'] = (int) lz_optpost('question');
		$option['2fa_email_force'] = (int) lz_optpost('2fa_email_force');
		
		// Any roles to apply to ?
		foreach($lz_roles as $k => $v){
			
			if(lz_optpost('2fa_roles_'.$k)){
				$option['2fa_roles'][$k] = 1;
			}
			
		}
		
		// If its all, then blank it
		if(lz_optpost('2fa_roles_all') || empty($option['2fa_roles'])){
			$option['2fa_roles'] = '';
		}
		
		// Is there an error ?
		if(!empty($lz_error)){
			return loginizer_page_2fa_T();
		}
		
		// Save the options
		update_option('loginizer_2fa', $option);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
		// update the rewrite rules for WooCommerce to make security settings page accessible from woo commerce client area
		if((!empty($option['2fa_app']) || !empty($option['2fa_email']) || !empty($option['question']) || !empty($option['2fa_email_force'])) && class_exists('WooCommerce')){
			loginizer_woocommerce_rewrite_rule();
		}
		
	}

	// Reset a users 2FA
	if(isset($_POST['reset_user_lz'])){
		
		$_username = lz_optpost('lz_user_2fa_disable');
		
		// Try to get the user
		$user_search = get_user_by('login', $_username);
		
		// If not found then search by email
		if(empty($user_search)){
			$user_search = get_user_by('email', $_username);
		}
		
		// If not found then give error
		if(empty($user_search)){
			$lz_error['2fa_user_not'] = __('There is no such user with the email or username you submitted', 'loginizer');
			return loginizer_page_2fa_T();
		}
		
		// Get the user prefences
		$user_pref = get_user_meta($user_search->ID, 'loginizer_user_settings');
		
		// Blank it
		$user_pref['pref'] = 'none';
		
		// Save it
		update_user_meta($user_search->ID, 'loginizer_user_settings', $user_pref);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = __('The user\'s 2FA settings have been reset', 'loginizer');
		
	}
	
	if(isset($_POST['save_2fa_custom_redirect'])){
		
		if(!empty($_POST['lz_2fa_custom_login_redirect'])){
			$loginizer['2fa_custom_login_redirect'] = map_deep($_POST['lz_2fa_custom_login_redirect'], 'sanitize_text_field');

			update_option('loginizer_2fa_custom_redirect', $loginizer['2fa_custom_login_redirect']);

			$GLOBALS['lz_saved'] = true;
		}
	}
	
	if(isset($_POST['save_2fa_email_template_lz'])){
		
		// In the future there can be more settings
		$option['2fa_email_sub'] = @stripslashes($_POST['lz_2fa_email_sub']);
		$option['2fa_email_msg'] = @stripslashes($_POST['lz_2fa_email_msg']);
		$option['2fa_email_html'] = isset($_POST['lz_2fa_email_html']);
		
		// Is there an error ?
		if(!empty($lz_error)){
			return loginizer_page_2fa_T();
		}
		
		// Save the options
		update_option('loginizer_2fa_email_template', $option);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	// Save the messages
	if(isset($_POST['save_msgs_lz'])){
		
		$msgs['otp_app'] = lz_optpost('msg_otp_app');
		$msgs['otp_email'] = lz_optpost('msg_otp_email');
		$msgs['otp_field'] = lz_optpost('msg_otp_field');
		$msgs['otp_question'] = lz_optpost('msg_otp_question');
		$msgs['otp_answer'] = lz_optpost('msg_otp_answer');
		
		// Update them
		update_option('loginizer_2fa_msg', $msgs);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = __('Messages were saved successfully', 'loginizer');
		
	}
	
	// Delete a Whitelist IP range
	if(isset($_POST['delid'])){
		
		$delid = (int) lz_optreq('delid');
		
		// Unset and save
		$whitelist = $loginizer['2fa_whitelist'];
		unset($whitelist[$delid]);
		update_option('loginizer_2fa_whitelist', $whitelist);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = __('The Whitelist IP range has been deleted successfully', 'loginizer');
		
	}
	
	// Delete all Blackist IP ranges
	if(isset($_POST['del_all_whitelist'])){
		
		// Unset and save
		update_option('loginizer_2fa_whitelist', array());
		
		// Mark as saved
		$GLOBALS['lz_saved'] = __('The Whitelist IP range(s) have been cleared successfully', 'loginizer');
		
	}
	
	// Add IP range to 2FA whitelist
	if(isset($_POST['2fa_whitelist_iprange'])){

		$start_ip = lz_optpost('start_ip_w_2fa');
		$end_ip = lz_optpost('end_ip_w_2fa');
		
		if(empty($start_ip)){
			$lz_error[] = __('Please enter the Start IP', 'loginizer');
			return loginizer_page_2fa_T();
		}
		
		// If no end IP we consider only 1 IP
		if(empty($end_ip)){
			$end_ip = $start_ip;
		}
				
		if(!lz_valid_ip($start_ip)){
			$lz_error[] = __('Please provide a valid start IP', 'loginizer');
		}
		
		if(!lz_valid_ip($end_ip)){
			$lz_error[] = __('Please provide a valid end IP', 'loginizer');
		}
			
		if(inet_ptoi($start_ip) > inet_ptoi($end_ip)){
			
			// BUT, if 0.0.0.1 - 255.255.255.255 is given, it will not work
			if(inet_ptoi($start_ip) >= 0 && inet_ptoi($end_ip) < 0){
				// This is right
			}else{
				$lz_error[] = __('The End IP cannot be smaller than the Start IP', 'loginizer');
			}
			
		}
		
		if(empty($lz_error)){
			
			$whitelist = $loginizer['2fa_whitelist'];
			
			foreach($whitelist as $k => $v){
				
				// This is to check if there is any other range exists with the same Start or End IP
				if(( inet_ptoi($start_ip) <= inet_ptoi($v['start']) && inet_ptoi($v['start']) <= inet_ptoi($end_ip) )
					|| ( inet_ptoi($start_ip) <= inet_ptoi($v['end']) && inet_ptoi($v['end']) <= inet_ptoi($end_ip) )
				){
					$lz_error[] = __('The Start IP or End IP submitted conflicts with an existing IP range !', 'loginizer');
					break;
				}
				
				// This is to check if there is any other range exists with the same Start IP
				if(inet_ptoi($v['start']) <= inet_ptoi($start_ip) && inet_ptoi($start_ip) <= inet_ptoi($v['end'])){
					$lz_error[] = __('The Start IP is present in an existing range !', 'loginizer');
					break;
				}
				
				// This is to check if there is any other range exists with the same End IP
				if(inet_ptoi($v['start']) <= inet_ptoi($end_ip) && inet_ptoi($end_ip) <= inet_ptoi($v['end'])){
					$lz_error[] = __('The End IP is present in an existing range!', 'loginizer');
					break;
				}
				
			}
			
			$newid = ( empty($whitelist) ? 0 : max(array_keys($whitelist)) ) + 1;
			
			if(empty($lz_error)){
				
				$whitelist[$newid] = array();
				$whitelist[$newid]['start'] = $start_ip;
				$whitelist[$newid]['end'] = $end_ip;
				$whitelist[$newid]['time'] = time();
				
				update_option('loginizer_2fa_whitelist', $whitelist);
		
				// Mark as saved
				$GLOBALS['lz_saved'] = __('Whitelist IP range for Two Factor Authentication added successfully', 'loginizer');
				
			}
			
		}
	}
	
	
	$lz_options = get_option('loginizer_2fa_email_template');
	$saved_msgs = get_option('loginizer_2fa_msg');
	$loginizer['2fa_whitelist'] = get_option('loginizer_2fa_whitelist');
	
	// Call theme
	loginizer_page_2fa_T();
	
}


// Loginizer - Two Factor Auth Page
function loginizer_page_2fa_T(){
	
	global $loginizer, $lz_error, $lz_env, $lz_roles, $lz_options, $saved_msgs;
	
	// Universal header
	loginizer_page_header('Two Factor Authentication');
	
	loginizer_feature_available('Two-Factor Authentication');
	
	// Saved ?
	if(!empty($GLOBALS['lz_saved'])){
		echo '<div id="message" class="updated"><p>'. __(is_string($GLOBALS['lz_saved']) ? $GLOBALS['lz_saved'] : 'The settings were saved successfully', 'loginizer'). '</p></div><br />';
	}
	
	// Any errors ?
	if(!empty($lz_error)){
		lz_report_error($lz_error);echo '<br />';
	}

	?>

<style>
input[type="text"], textarea, select {
    width: 70%;
}

.form-table label{
	font-weight:bold;
}

.exp{
	font-size:12px;
}
</style>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Two Factor Authentication Settings', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('Please choose from the following Two Factor Authentication methods. Each user can choose any one method from the ones enabled by you. You can enable all or anyone that you would like.', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:70% !important">
					<label><?php echo __('OTP via App', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('After entering the correct login credentials, the user will be asked for the OTP. The OTP will be obtained from the users mobile app e.g. <b>Google Authenticator, Authy, etc.</b>', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="2fa_app" <?php echo lz_POSTchecked('2fa_app', (empty($loginizer['2fa_app']) ? false : true), 'save_lz'); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label><?php echo __('OTP via Email', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('After entering the correct login credentials, the user will be asked for the OTP. The OTP will be emailed to the user.', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="2fa_email" <?php echo lz_POSTchecked('2fa_email', (empty($loginizer['2fa_email']) ? false : true), 'save_lz'); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label><?php echo __('User Defined Question & Answer', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('In this method the user will be asked to set a secret personal question and answer. After entering the correct login credentials, the user will be asked to answer the question set by them, thus increasing the security', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="question" <?php echo lz_POSTchecked('question', (empty($loginizer['question']) ? false : true), 'save_lz'); ?> />
				</td>
			</tr>
		</table><br />
		
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" style="width:70% !important">
					<label><?php echo __('Force OTP via Email', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If the user does not have any 2FA method selected, this will enforce the OTP via Email for the users.', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="2fa_email_force" <?php echo lz_POSTchecked('2fa_email_force', (empty($loginizer['2fa_email_force']) ? false : true), 'save_lz'); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:70% !important">
					<label><?php echo __('Apply 2FA to Roles', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Select the Roles to which 2FA should be applied.', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" onchange="lz_roles_handle()" name="2fa_roles_all" id="2fa_roles_all" <?php echo lz_POSTchecked('2fa_roles_all', (empty($loginizer['2fa_roles']) ? true : false), 'save_lz'); ?> /> All<br />
					<?php
					
					foreach($lz_roles as $k => $v){
						echo '<span class="lz_roles"><input type="checkbox" value="1" name="2fa_roles_'.$k.'" '.lz_POSTchecked('2fa_roles_'.$k, (empty($loginizer['2fa_roles'][$k]) ? false : true), 'save_lz').' /> '.$v['name'].'<br /></span>';
					}
					
					?>
				</td>
			</tr>
		</table><br />
		<center><input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
		</form>
	
		</div>
	</div>

<script type="text/javascript">

function lz_roles_handle(){
	
	var obj = jQuery("#2fa_roles_all")[0];
	
	if(obj.checked){
		jQuery(".lz_roles").hide();
	}else{
		jQuery(".lz_roles").show();
	}
	
}

lz_roles_handle();

</script>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('OTP via Email Template', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td colspan="2" valign="top">
					<?php echo __('Customize the email template to be used when sending the OTP to login via Email for 2FA.', 'loginizer'); ?><br>
					<?php echo __('If you do not make changes below the default email template will be used !', 'loginizer'); ?>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:350px !important">
					<label><?php echo __('Email Subject', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the default subject', 'loginizer'); ?></span>
					<br />Default : <?php echo (!empty($loginizer['2fa_email_d_sub']) ? $loginizer['2fa_email_d_sub'] : ''); ?>
				</td>
				<td valign="top">
					<input type="text" size="40" value="<?php echo lz_htmlizer(!empty($_POST['lz_2fa_email_sub']) ? stripslashes($_POST['lz_2fa_email_sub']) : (empty($lz_options['2fa_email_sub']) ? '' : $lz_options['2fa_email_sub'])); ?>" name="lz_2fa_email_sub" />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label><?php echo __('Email Body', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the default message', 'loginizer'); ?></span>
					<br />Default : <pre style="font-size:10px"><?php echo (!empty($loginizer['2fa_email_d_msg']) ? $loginizer['2fa_email_d_msg'] : ''); ?></pre>
				</td>
				<td valign="top">
					<textarea rows="10" name="lz_2fa_email_msg"><?php echo lz_htmlizer(!empty($_POST['lz_2fa_email_msg']) ? stripslashes($_POST['lz_2fa_email_msg']) : (empty($lz_options['2fa_email_msg']) ? '' : $lz_options['2fa_email_msg'])); ?></textarea>
					<br />
					Variables :
					<br />$otp - The OTP for login
					<br />$site_name - The Site Name
					<br />$site_url - The Site URL
					<br />$email  - Users Email
					<br />$display_name  - Users Display Name
					<br />$user_login  - Username
					<br />$first_name  - Users First Name
					<br />$last_name  - Users Last Name
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:350px !important">
					<label for="loginizer_2fa_html_mail"><?php echo __('Send email as HTML', 'loginizer'); ?></label>
				</td>
				<td>
					<input type="checkbox" value="1" name="lz_2fa_email_html" id="lz_2fa_email_html" <?php echo lz_POSTchecked('lz_2fa_email_html', (empty($loginizer['2fa_email_html']) ? false : true)); ?> />
				</td>
			</tr>
		</table><br />
		<center><input name="save_2fa_email_template_lz" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
		</form>
	
		</div>
	</div>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Custom Messages for OTP', 'loginizer'); ?></span>
		</h2>
		</div>

		<div class="inside">

			<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
				<?php wp_nonce_field('loginizer-options'); ?>
				<table class="form-table">
					<tr>
						<td colspan="2" valign="top">
							<?php echo __('Customize the title for OTP field displayed to the user on the login form.', 'loginizer'); ?><br>
							<?php echo __('If you do not make changes below the default messages will be used !', 'loginizer'); ?>
						</td>
					</tr>
					<tr>
						<td scope="row" valign="top" style="width:350px !important">
							<label for="msg_otp_app"><?php echo __('OTP via APP','loginizer'); ?></label><br />
							<?php echo __('Default: <em>&quot;' . $loginizer['2fa_d_msg']['otp_app']. '&quot;</em>', 'loginizer'); ?>
						</td>
						<td>
							<input type="text" size="50" value="<?php echo esc_attr(empty($saved_msgs['otp_app']) ? '' : $saved_msgs['otp_app']); ?>" name="msg_otp_app" id="msg_otp_app" style="width:auto !important;" />
							<br />
						</td>
					</tr>
					<tr>
						<td scope="row" valign="top" style="width:350px !important">
							<label for="msg_otp_email"><?php echo __('OTP via Email','loginizer'); ?></label><br />
							<?php echo __('Default: <em>&quot;' . $loginizer['2fa_d_msg']['otp_email']. '&quot;</em>', 'loginizer'); ?>
						</td>
						<td>
							<input type="text" size="50" value="<?php echo esc_attr(empty($saved_msgs['otp_email']) ? '' : $saved_msgs['otp_email']); ?>" name="msg_otp_email" id="msg_otp_email" style="width:auto !important;" />
							<br />
						</td>
					</tr>
					<tr>
						<td scope="row" valign="top" style="width:350px !important">
							<label for="msg_otp_field"><?php echo __('Title for OTP field','loginizer'); ?></label><br />
							<?php echo __('Default: <em>&quot;' . $loginizer['2fa_d_msg']['otp_field']. '&quot;</em>', 'loginizer'); ?>
						</td>
						<td>
							<input type="text" size="50" value="<?php echo esc_attr(empty($saved_msgs['otp_field']) ? '' : $saved_msgs['otp_field']); ?>" name="msg_otp_field" id="msg_otp_field" style="width:auto !important;" />
							<br />
						</td>
					</tr>
					<tr>
						<td scope="row" valign="top" style="width:350px !important">
							<label for="msg_otp_question"><?php echo __('Title for Security Question','loginizer'); ?></label><br />
							<?php echo __('Default: <em>&quot;' . $loginizer['2fa_d_msg']['otp_question']. '&quot;</em>', 'loginizer'); ?>
						</td>
						<td>
							<input type="text" size="50" value="<?php echo esc_attr(empty($saved_msgs['otp_question']) ? '' : $saved_msgs['otp_question']); ?>" name="msg_otp_question" id="msg_otp_question" style="width:auto !important;" />
							<br />
						</td>
					</tr>
					<tr>
						<td scope="row" valign="top" style="width:350px !important">
							<label for="msg_otp_answer"><?php echo __('Title for Security Answer','loginizer'); ?></label><br />
							<?php echo __('Default: <em>&quot;' . $loginizer['2fa_d_msg']['otp_answer']. '&quot;</em>', 'loginizer'); ?>
						</td>
						<td>
							<input type="text" size="50" value="<?php echo esc_attr(empty($saved_msgs['otp_answer']) ? '' : $saved_msgs['otp_answer']); ?>" name="msg_otp_answer" id="msg_otp_answer" style="width:auto !important;" />
							<br />
						</td>
					</tr>
				</table><br />
				<center><input name="save_msgs_lz" class="button button-primary action" value="<?php echo __('Save Messages','loginizer'); ?>" type="submit" /></center>
			</form>
		</div>
	</div>
	
	<!--Bypass a single user-->
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Disable Two Factor Authentication for a User', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('Here you can disable the Two Factor Authentication settings of a user. In the event a user has forgotten his secret answer or lost his Device App, he will not be able to login. You can reset such a users settings from here.', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label><?php echo __('Username / Email', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('The username or email of the user whose 2FA you would like to disable', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo lz_optpost('lz_user_2fa_disable', ''); ?>" name="lz_user_2fa_disable" />
				</td>
			</tr>
		</table><br />
		
		<center><input name="reset_user_lz" class="button button-primary action" value="<?php echo __('Reset 2FA for User', 'loginizer'); ?>" type="submit" /></center>
		</form>
	
		</div>
	</div>
	
	<br />
	
<?php
	
	wp_enqueue_script('jquery-paginate', LOGINIZER_URL.'/assets/js/jquery-paginate.js', array('jquery'), '1.10.15');
	
?>

<style>
.page-navigation a {
margin: 5px 2px;
display: inline-block;
padding: 5px 8px;
color: #0073aa;
background: #e5e5e5 none repeat scroll 0 0;
border: 1px solid #ccc;
text-decoration: none;
transition-duration: 0.05s;
transition-property: border, background, color;
transition-timing-function: ease-in-out;
}
 
.page-navigation a[data-selected] {
background-color: #00a0d2;
color: #fff;
}
</style>

<script>

jQuery(document).ready(function(){
	jQuery('#lz_wl_2fa_table').paginate({ limit: 11, navigationWrapper: jQuery('#lz_wl_2fa_nav')});
});

// Delete a 2FA Whitelist IP Range
function del_2fa_confirm(field, todo_id, msg){
	var ret = confirm(msg);
	
	if(ret){
		jQuery('#lz_wl_2fa_todo').attr('name', field);
		jQuery('#lz_wl_2fa_todo').val(todo_id);
		jQuery('#lz_wl_2fa_form').submit();
	}
	
	return false;
	
}

// Delete all 2FA Whitelist IP Ranges
function del_2fa_confirm_all(msg){
	var ret = confirm(msg);
	
	if(ret){
		return true;
	}
	
	return false;
	
}

</script>
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Disable Two Factor Authentication for IP', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php echo __('Enter the IP you want to whitelist for two factor authentication', 'loginizer'); ?>
		<form action="" method="post" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="start_ip_w_2fa"><?php echo __('Start IP','loginizer'); ?></label></th>
				<td>
					<input type="text" size="25" style="width:auto;" value="<?php echo(lz_optpost('start_ip_w_2fa')); ?>" name="start_ip_w_2fa" id="start_ip_w_2fa"/> <?php echo __('Start IP of the range','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="end_ip_w_2fa"><?php echo __('End IP (Optional)','loginizer'); ?></label></th>
				<td>
					<input type="text" size="25" style="width:auto;" value="<?php echo(lz_optpost('end_ip_w_2fa')); ?>" name="end_ip_w_2fa" id="end_ip_w_2fa"/> <?php echo __('End IP of the range. <br />If you want to whitelist single IP leave this field blank.','loginizer'); ?> <br />
				</td>
			</tr>
		</table><br />
		<input name="2fa_whitelist_iprange" class="button button-primary action" value="<?php echo __('Add Whitelist IP Range','loginizer'); ?>" type="submit" />		
		<input style="float:right" name="del_all_whitelist" onclick="return del_2fa_confirm_all('<?php echo __('Are you sure you want to delete all Whitelist IP Range(s) for 2FA ?','loginizer'); ?>')" class="button action" value="<?php echo __('Delete All Whitelist IP Range(s) for 2FA','loginizer'); ?>" type="submit" />
		</form>
		</div>
		
		<div id="lz_wl_2fa_nav" style="margin: 5px 10px; text-align:right"></div>
		<table id="lz_wl_2fa_table" class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
		<tr>
			<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Start IP','loginizer'); ?></th>
			<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('End IP','loginizer'); ?></th>
			<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Date (DD/MM/YYYY)','loginizer'); ?></th>
			<th scope="row" valign="top" style="background:#EFEFEF;" width="100"><?php echo __('Options','loginizer'); ?></th>
		</tr>
		<?php
			if(empty($loginizer['2fa_whitelist'])){
				echo '
				<tr>
					<td colspan="4">
						'.__('No Whitelist IPs for Two Factor Authentication. You will see whitelisted IP ranges here.', 'loginizer').'
					</td>
				</tr>';
			}else{
				foreach($loginizer['2fa_whitelist'] as $ik => $iv){
					echo '
					<tr>
						<td>
							'.$iv['start'].'
						</td>
						<td>
							'.$iv['end'].'
						</td>
						<td>
							'.date('d/m/Y', $iv['time']).'
						</td>
						<td>
							<a class="submitdelete" href="javascript:void(0)" onclick="return del_2fa_confirm(\'delid\', '.$ik.', \'Are you sure you want to delete this IP range for 2FA ?\')">Delete</a>
						</td>
					</tr>';
				}
			}
		?>
		</table>
		<br />
		<form action="" method="post" id="lz_wl_2fa_form">
		<?php wp_nonce_field('loginizer-options'); ?>
		<input type="hidden" value="" name="" id="lz_wl_2fa_todo"/> 
		</form>
		<br />
	
	</div>
		
	<!--Custom Redirects based on role-->
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Custom Redirects based on roles', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('Here you can set the URL, which you wish your user to get redirected to after login via 2FA.', 'loginizer'); ?></i>
				</td>
			</tr>
			<?php
			global $wp_roles;

			foreach($wp_roles->roles as $key => $role){
				echo'<tr>
					<td scope="row" valign="top">
						<label>'. esc_html($role['name']).'</label><br>
					</td>
					<td>
						<input type="text" size="50" value="'.(!empty($loginizer['2fa_custom_login_redirect']) && !empty($loginizer['2fa_custom_login_redirect'][$key]) ? esc_attr($loginizer['2fa_custom_login_redirect'][$key]) : '').'" placeholder="'.site_url().'" name="lz_2fa_custom_login_redirect['.esc_html($key).']" />
					</td>
				</tr>';
			}
			?>
			
		</table><br />
		
		<center><input name="save_2fa_custom_redirect" class="button button-primary action" value="<?php echo __('Save Custom URLs', 'loginizer'); ?>" type="submit" /></center>
		</form>
	
		</div>
	</div>

	<?php
	loginizer_page_footer();
	
}