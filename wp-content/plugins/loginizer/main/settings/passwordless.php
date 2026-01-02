<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

// Loginizer - PasswordLess Page
function loginizer_page_passwordless(){
	
	global $loginizer, $lz_error, $lz_env;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	if(!loginizer_is_premium() && count($_POST) > 0){
		$lz_error['not_in_free'] = __('This feature is not available in the Free version. <a href="'.LOGINIZER_PRICING_URL.'" target="_blank" style="text-decoration:none; color:green;"><b>Upgrade to Pro</b></a>', 'loginizer');
		return loginizer_page_passwordless_T();
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginizer-options');
	}
	
	if(isset($_POST['save_lz'])){
		
		// In the future there can be more settings
		$option['email_pass_less'] = (int) lz_optpost('email_pass_less');
		$option['passwordless_sub'] = @stripslashes($_POST['lz_passwordless_sub']);
		$option['passwordless_msg'] = @stripslashes($_POST['lz_passwordless_msg']);
		$option['passwordless_html'] = (int) lz_optpost('lz_passwordless_html');
		$option['passwordless_redirect'] = esc_url_raw($_POST['lz_passwordless_redirect']);
		$option['passwordless_redirect_for'] = !empty($_POST['lz_passwordless_redirect_for']) ? map_deep($_POST['lz_passwordless_redirect_for'], 'sanitize_text_field') : [];
		$option['passwordless_disabled_for'] = !empty($_POST['lz_passwordless_disabled_for']) ? map_deep(wp_unslash($_POST['lz_passwordless_disabled_for']), 'sanitize_text_field') : [];

		// Is there an error ?
		if(!empty($lz_error)){
			return loginizer_page_passwordless_T();
		}
		
		// Save the options
		update_option('loginizer_epl', $option);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	// Call theme
	loginizer_page_passwordless_T();
}

// Loginizer - PasswordLess Page Theme
function loginizer_page_passwordless_T(){
	
	global $loginizer, $lz_error, $lz_env;
	
	$lz_options = get_option('loginizer_epl');
	
	// Universal header
	loginizer_page_header('PasswordLess Settings');
	
	loginizer_feature_available('PasswordLess Login');
	
	// Saved ?
	if(!empty($GLOBALS['lz_saved'])){
		echo '<div id="message" class="updated"><p>'. __('The settings were saved successfully', 'loginizer'). '</p></div><br />';
	}
	
	// Any errors ?
	if(!empty($lz_error)){
		lz_report_error($lz_error);echo '<br />';
	}

	?>

<style>
input[type="text"], textarea, select {
    width: 90%;
}

.form-table label{
	font-weight:bold;
}

.form-table td{
	vertical-align:top;
}

.exp{
	font-size:12px;
}
</style>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('PasswordLess Settings', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" style="width:350px !important"><label for="email_pass_less"><?php echo __('Enable PasswordLess Login', 'loginizer'); ?></label></td>
				<td>
					<input type="checkbox" value="1" name="email_pass_less" id="email_pass_less" <?php echo lz_POSTchecked('email_pass_less', (empty($loginizer['email_pass_less']) ? false : true)); echo (defined('SITEPAD') ? 'disabled="disabled"' : '') ?> />
				</td>
			</tr>
			<tr>
				<td colspan="2" valign="top">
					<?php echo __('If enabled, the login screen will just ask for the username <b>OR</b> email address of the user. If such a user exists, an email with a <b>One Time Login </b> link will be sent to the email address of the user. The link will be valid for 10 minutes only.', 'loginizer'); ?><br><br>
					<?php echo __('If a wrong username/email is given, the brute force checker will prevent any brute force attempt !', 'loginizer'); ?>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label for="lz_passwordless_sub"><?php echo __('Email Subject', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the default subject', 'loginizer'); ?></span>
					<br />Default : <?php echo (!empty($loginizer['pl_d_sub']) ? $loginizer['pl_d_sub'] : ''); ?>
				</td>
				<td valign="top">
					<input type="text" size="40" value="<?php echo lz_htmlizer(!empty($_POST['lz_passwordless_sub']) ? stripslashes($_POST['lz_passwordless_sub']) : (empty($lz_options['passwordless_sub']) ? '' : $lz_options['passwordless_sub'])); ?>" name="lz_passwordless_sub" id="lz_passwordless_sub" />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label for="lz_passwordless_msg"><?php echo __('Email Body', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the default message', 'loginizer'); ?></span>
					<br />Default : <pre style="font-size:10px"><?php echo (!empty($loginizer['pl_d_msg']) ? $loginizer['pl_d_msg'] : '') ; ?></pre>
				</td>
				<td valign="top">
					<textarea rows="10" name="lz_passwordless_msg" id="lz_passwordless_msg"><?php echo lz_htmlizer(!empty($_POST['lz_passwordless_msg']) ? stripslashes($_POST['lz_passwordless_msg']) : (empty($lz_options['passwordless_msg']) ? '' : $lz_options['passwordless_msg'])); ?></textarea>
					<br />
					Variables :
					<br />$email  - Users Email
					<br />$site_name - The Site Name
					<br />$site_url - The Site URL
					<br />$login_url - The Login URL
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top"><label for="lz_passwordless_html"><?php echo __('Send email as HTML', 'loginizer'); ?></label></td>
				<td>
					<input type="checkbox" value="1" name="lz_passwordless_html" id="lz_passwordless_html" <?php echo lz_POSTchecked('lz_passwordless_html', (empty($loginizer['passwordless_html']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:350px !important">
					<label for="lz_passwordless_redirect"><?php echo __('Custom redirect to', 'loginizer'); ?></label><br/>
					<span class="exp"><?php echo __('Redirects user to a page of your website other than the admin panel', 'loginizer'); ?></span>
				</td>
				<td align="top">
					<input type="text" size="40" value="<?php echo lz_htmlizer(!empty($_POST['lz_passwordless_redirect']) ? stripslashes($_POST['lz_passwordless_redirect']) : (empty($lz_options['passwordless_redirect']) ? '' : $lz_options['passwordless_redirect'])); ?>" name="lz_passwordless_redirect" id="lz_passwordless_redirect" />
				</td>
			</tr>
			
			<tr>
				<td scope="row" valign="top" style="width:350px !important">
					<label for="lz_passwordless_redirect_for"><?php echo __('Custom redirect for', 'loginizer'); ?></label><br/>
					<span class="exp"><?php echo __('Select the user roles for whom this custom redirect will be used', 'loginizer'); ?></span>
				</td>
				<td align="top">
				<?php
					$editable_roles = get_editable_roles();
					echo '<div style="max-height:120px; overflow:auto;">';
					$r = '';
					foreach($editable_roles as $role => $details) {
						$name = translate_user_role( $details['name'] );
						// Preselect specified role.
						if(!empty($lz_options['passwordless_redirect_for']) && in_array($role, $lz_options['passwordless_redirect_for'])) {
							$r .= "\n\t<input type=\"checkbox\" checked name=\"lz_passwordless_redirect_for[]\" value='" . esc_attr($role) . "' style=\"margin-top:5px\">$name</option>";
						} else {
							$r .= "\n\t<input type=\"checkbox\" value='" . esc_attr($role) . "' name=\"lz_passwordless_redirect_for[]\">$name</option>";
						}

						$r .= '<br/>';
					}
					echo $r . '</div>';
				?>
				</td>
			</tr>
			
			<tr>
				<td scope="row" valign="top" style="width:350px !important">
					<label for="lz_passwordless_disabled_for"><?php echo __('Disable for', 'loginizer'); ?></label><br/>
					<span class="exp"><?php echo __('Which login form you want to disable passwordless login for', 'loginizer'); ?></span>
				</td>
				<td align="top">
				<?php
					$forms = ['admin', 'woocommerce'];
					echo '<div>';
					$r = '';
					foreach($forms as $form) {
						// Preselect specified form.
						if(!empty($lz_options['passwordless_disabled_for']) && in_array($form, $lz_options['passwordless_disabled_for'])){
							$r .= "\n\t<input type=\"checkbox\" checked name=\"lz_passwordless_disabled_for[]\" value='" . esc_attr($form) . "' style=\"margin-top:5px\">".esc_html(ucfirst($form))."</option>";
						} else {
							$r .= "\n\t<input type=\"checkbox\" value='" . esc_attr($form) . "' name=\"lz_passwordless_disabled_for[]\">".esc_html(ucfirst($form))."</option>";
						}

						$r .= '<br/>';
					}
					echo $r . '</div>';
				?>
				</td>
			</tr>
			
		</table><br />
		<center><input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
		</form>
	
		</div>
	</div>
	<br />

	<?php
	loginizer_page_footer();
	
}