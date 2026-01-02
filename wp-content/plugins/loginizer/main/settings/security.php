<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

// Loginizer - Security Settings Page
function loginizer_page_security(){
	
	global $loginizer, $lz_error, $lz_env, $wpdb;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	if(!loginizer_is_premium() && count($_POST) > 0){
		$lz_error['not_in_free'] = __('This feature is not available in the Free version. <a href="'.LOGINIZER_PRICING_URL.'" target="_blank" style="text-decoration:none; color:green;"><b>Upgrade to Pro</b></a>', 'loginizer');
		return loginizer_page_security_T();
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginizer-options');
	}

	if(isset($_POST['save_lz'])){
		
		$option['login_slug'] = lz_optpost('login_slug');
		$option['rename_login_secret'] = (int) lz_optpost('rename_login_secret');
		$option['hide_wp_admin'] = !empty($_POST['hide_wp_admin']);
		$option['login_redirect_url'] = lz_optpost('login_redirect_url');
		$option['xmlrpc_slug'] = lz_optpost('xmlrpc_slug');
		$option['xmlrpc_disable'] = (int) lz_optpost('xmlrpc_disable');
		$option['pingbacks_disable'] = (int) lz_optpost('pingbacks_disable');
		
		// Login Slug Valid ?
		if(!empty($option['login_slug'])){
			if(strlen($option['login_slug']) <= 4 || strlen($option['login_slug']) > 50){
				$lz_error['login_slug'] = __('The Login slug length must be greater than <b>4</b> chars and upto <b>50</b> chars long', 'loginizer');
			}
		}
		
		// login slug and admin slug cannot be the same
		$_loginizer_wp_admin = get_option('loginizer_wp_admin');
		if(!empty($_loginizer_wp_admin['admin_slug']) && $_loginizer_wp_admin['admin_slug'] == $option['login_slug']){
			$lz_error['lz_same_slug'] = __('The wp-login.php and wp-admin slugs cannot be the same. Choose unique names for login and admin slugs', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// XML-RPC Slug Valid ?
		if(!empty($option['xmlrpc_slug'])){
			if(strlen($option['xmlrpc_slug']) <= 4 || strlen($option['xmlrpc_slug']) > 50){
				$lz_error['xmlrpc_slug'] = __('The XML-RPC slug length must be greater than <b>4</b> chars and upto <b>50</b> chars long', 'loginizer');
			}
		}
		
		// Is there an error ?
		if(!empty($lz_error)){
			return loginizer_page_security_T();
		}
		
		// Save the options
		update_option('loginizer_security', $option);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	// Reset the username
	if(isset($_POST['save_lz_admin'])){
		
		// Get the new username
		$current_username = lz_optpost('current_username');
		$new_username = lz_optpost('new_username');
		
		if(empty($current_username)){
			$lz_error['current_username_empty'] = __('Current username is required', 'loginizer');
			return loginizer_page_security_T();
		}
		
		if(empty($new_username)){
			$lz_error['new_username_empty'] = __('New username is required', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// Is the starting of the username having 'admin' ?
		if(@strtolower(substr($new_username, 0, 5)) == 'admin'){
			$lz_error['user_exists'] = __('The username begins with <b>admin</b>. Please change it !', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// Lets check if there is such a user
		$found = get_user_by('login', $new_username);
		
		// Found one !
		if(!empty($found->ID)){
			$lz_error['user_exists'] = __('The new username is already assigned to another user', 'loginizer');
			return loginizer_page_security_T();
		}
	
		$old_user = get_user_by('login', $current_username);
		
		if(empty($old_user->ID)){
			$lz_error['current_username_invalid'] = __('No user found with the current username provided', 'loginizer');
			return loginizer_page_security_T();
		}
		
		if(empty($old_user->caps['administrator'])){
			$lz_error['user_not_admin'] = __('The user is not an administrator. Only administrator user\'s username can be changed.', 'loginizer');
			return loginizer_page_security_T();
		}
		
		$is_super_admin = 0;
		if(is_multisite() && is_super_admin($old_user->ID)){
			$is_super_admin = 1;
		}
		
		// Update the username
		$update_data = array('user_login' => $new_username);
		$where_data = array('ID' => $old_user->ID);
		
		$format = array('%s');
		$where_format = array('%d');
		
		$wpdb->update($wpdb->prefix.'users', $update_data, $where_data, $format, $where_format);
		
		// Update the super admins list for multisite
		if(!empty($is_super_admin)){
			
			$super_admins = get_site_option('site_admins');
			
			foreach($super_admins as $sk => $sv){
				// Remove the existing username from super admins list
				if($sv == $current_username){
					unset($super_admins[$sk]);
				}
			}
			
			// Add the new username
			$super_admins[] = $new_username;
			
			update_site_option( 'site_admins', $super_admins );
			
		}
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	// Change the wp-admin slug
	if(isset($_POST['save_lz_wp_admin'])){
		
		// Get the new username
		$option['admin_slug'] = lz_optpost('admin_slug');
		$option['restrict_wp_admin'] = (int) lz_optpost('restrict_wp_admin');
		$option['wp_admin_msg'] = @stripslashes($_POST['wp_admin_msg']);
		$lz_wp_admin_docs = (int) lz_optpost('lz_wp_admin_docs');
		
		// login slug and admin slug cannot be the same
		$_loginizer_security = get_option('loginizer_security');
		if(!empty($_loginizer_security['login_slug']) && $_loginizer_security['login_slug'] == $option['admin_slug']){
			$lz_error['lz_same_slug'] = __('The wp-login.php and wp-admin slugs cannot be the same. Choose unique names for login and admin slugs', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// Did you agree to this ?
		if(!empty($option['admin_slug']) && empty($lz_wp_admin_docs)){
			$lz_error['lz_wp_admin_docs'] = __('You have not confirmed that you have read the guide and configured .htaccess. Please read the guide, configure .htaccess and then save these settings and check this checkbox', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// Length
		if(!empty($option['admin_slug']) && (strlen($option['admin_slug']) <= 4 || strlen($option['admin_slug']) > 50)){
			$lz_error['admin_slug'] = __('The new Admin slug length must be greater than <b>4</b> chars and upto <b>50</b> chars long', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// Only regular characters
		if(preg_match('/[^\w\d\-_]/is', $option['admin_slug'])){
			$lz_error['admin_slug_chars'] = __('Special characters are not allowed', 'loginizer');
			return loginizer_page_security_T();
		}
		
		// Update the option
		update_option('loginizer_wp_admin', $option);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	
	// Save blacklisted usernames
	if(isset($_POST['save_lz_bl_users'])){
		
		$usernames = isset($_POST['lz_bl_users']) && is_array($_POST['lz_bl_users']) ? $_POST['lz_bl_users'] : array();
		
		// Process the usernames i.e. remove blanks
		foreach($usernames as $k => $v){
			$v = trim($v);
			
			// Unset blank values
			if(empty($v)){
				unset($usernames[$k]);
			}
			
			// Disallow these special characters to avoid XSS or any other security vulnerability
			if(preg_match('/[\<\>\"\']/', $v)){
				unset($usernames[$k]);
			}
		}
		
		// Update the blacklist
		update_option('loginizer_username_blacklist', array_values($usernames));
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	
	// Save blacklisted domains
	if(isset($_POST['save_lz_bl_domains'])){
		
		$domains = isset($_POST['lz_bl_domains']) && is_array($_POST['lz_bl_domains']) ? $_POST['lz_bl_domains'] : array();
		
		// Process the domains i.e. remove blanks
		foreach($domains as $k => $v){
			$v = trim($v);
			
			// Unset blank values
			if(empty($v)){
				unset($domains[$k]);
			}
			
			// Disallow these special characters to avoid XSS or any other security vulnerability
			if(preg_match('/[\<\>\"\']/', $v)){
				unset($domains[$k]);
			}
		}
		
		// Update the blacklist
		update_option('loginizer_domains_blacklist', array_values($domains));
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	
	if(isset($_POST['save_lz_csrf_protection'])){
		update_option('loginizer_csrf_protection', empty(lz_optpost('enable_csrf_protection')) ? false : true);
		
		delete_transient('loginizer_csrf_mod_rewrite');
		$GLOBALS['lz_saved'] = true;
	}
	
	if(isset($_POST['save_lz_limit_session'])){
		$limit_session = map_deep($_POST['limit_session'], 'sanitize_text_field');
		
		if(empty($limit_session)){
			delete_option('loginizer_limit_session');
		} else {
			update_option('loginizer_limit_session', $limit_session);
		}

		$GLOBALS['lz_saved'] = true;
	}
	
	// Call theme
	loginizer_page_security_T();
	
}

// Loginizer - Security Settings Page Theme
function loginizer_page_security_T(){
	
	global $loginizer, $lz_error, $lz_env;

	// Universal header
	loginizer_page_header('Security Settings');
	
	loginizer_feature_available('Security Settings');
	
	// Saved ?
	if(!empty($GLOBALS['lz_saved'])){
		echo '<div id="message" class="updated"><p>'. __('The settings were saved successfully', 'loginizer'). '</p></div><br />';
	}
	
	// Any errors ?
	if(!empty($lz_error)){
		lz_report_error($lz_error);echo '<br />';
	}
	
	$current_admin = get_user_by('id', 1);

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

<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Rename Login Page', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('You can rename your Login page from','loginizer'). ' <b> '. $loginizer['login_basename'].' </b> '.__(' to anything of your choice e.g. mylogin. This would make it very difficult for automated attack bots to know where to login !','loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label><?php echo __('New Login Slug', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the original login URL', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo (!empty($loginizer['login_slug']) ? lz_POSTval('login_slug', $loginizer['login_slug']) : ''); ?>" name="login_slug" />
				</td>
			</tr>
	
<?php

if(!defined('SITEPAD')){

?>
			<tr>
				<td scope="row" valign="top" style="width:200px !important">
					<label><?php echo __('Access Secretly Only', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If set, then all Login URL\'s will still point to '.$loginizer['login_basename'].' and users will have to access the New Login Slug by typing it in the browser.', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="rename_login_secret" <?php echo lz_POSTchecked('rename_login_secret', (empty($loginizer['rename_login_secret']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:200px !important">
					<label><?php echo __('Hide WP Admin', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If the user is not logged in they wont be able to access wp-admin url.', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="hide_wp_admin" <?php echo lz_POSTchecked('hide_wp_admin', (empty($loginizer['hide_wp_admin']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:200px !important">
					<label><?php echo __('Redirect URL', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Which page should be shown when someone tries to access wp-admin when it is hidden.', 'loginizer'); ?></span>
					<span class="exp"><?php echo __('Default: HomePage.', 'loginizer'); ?></span>
				</td>
				<td>
					<?php echo esc_url(home_url('/')); ?>&nbsp;<input type="text" style="width:20%;" name="login_redirect_url" value="<?php echo (!empty($loginizer['login_redirect_url']) ? lz_POSTval('login_redirect_url', $loginizer['login_redirect_url']) : ''); ?>"/>
				</td>
			</tr>
	
<?php

}

?>
		</table><br />
		<center><input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>
	
	<?php
	
	if(!defined('SITEPAD')){

	?>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('XML-RPC Settings', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('WordPress\'s XML-RPC feature allows external services to access and modify content on the site. Services like the Jetpack plugin, the WordPress mobile app, pingbacks, etc make use of the XML-RPC feature. If this site does not use a service that requires XML-RPC, please <b>disable</b> the XML-RPC feature as it prevents attackers from using the feature to attack the site. If your service can use a custom XML-RPC URL, you can also <b>rename</b> the XML-RPC page to a <b>custom slug</b>.', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label><?php echo __('Disable XML-RPC', 'loginizer'); ?></label>
				</td>
				<td>
					<input type="checkbox" value="1" name="xmlrpc_disable" <?php echo lz_POSTchecked('xmlrpc_disable', (empty($loginizer['xmlrpc_disable']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label><?php echo __('Disable Pingbacks', 'loginizer'); ?></label>
				</td>
				<td>
					<input type="checkbox" value="1" name="pingbacks_disable" <?php echo lz_POSTchecked('pingbacks_disable', (empty($loginizer['pingbacks_disable']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label><?php echo __('New XML-RPC Slug', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the original XML-RPC URL', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo (!empty($loginizer['xmlrpc_slug']) ? lz_optpost('xmlrpc_slug', $loginizer['xmlrpc_slug']) : ''); ?>" name="xmlrpc_slug" />
				</td>
			</tr>
		</table><br />
		<center><input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>
	
	<?php
	
	}

	?>
	
</form>
	
<?php

if(!defined('SITEPAD')){

?>

<script type="text/javascript">

function lz_update_htaccess_admin(e){
	
	var admin_name = jQuery(e).val();
	
	if(admin_name.length == 0){
		admin_name = 'wp-admin';
	}

	var textarea = jQuery('.lz-htaccess-textarea');
	
	if(textarea.length == 0) {
		return;
	}
	
	var htaccess = textarea.val();
	htaccess = htaccess.replace(/\^.+?\(/, '^' + admin_name + '(');
	textarea.val(htaccess);

}


function dirname(path) {
  return path.replace(/\\/g, '/').replace(/\/[^/]*\/?$/, '');
}

function lz_test_wp_admin(){
	
	var data = new Object();
	data["action"] = "loginizer_wp_admin";
	data["nonce"]	= "<?php echo wp_create_nonce('loginizer_admin_ajax');?>";
	
	var new_ajaxurl = dirname(dirname(ajaxurl))+'/'+jQuery('#lz_admin_slug').val()+'/admin-ajax.php';
	
	// AJAX and on success function
	jQuery.post(new_ajaxurl, data, function(response){
		
		if(response['result'] == 1){
			alert("<?php echo __('Everything seems to be good. You can proceed to save the settings !', 'loginizer'); ?>");
		}		
	
	// Throw an error for failures
	}).fail(function() {
		alert("<?php echo __('There was an error connecting to WordPress with the new Admin Slug. Did you configure everything properly ?', 'loginizer'); ?>");
	});
	//jQuery.ajax('<input type="text" size="30" value="" name="lz_bl_users[]" class="lz_bl_users" />');
	return false;
};

</script>

<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Rename wp-admin access', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<?php 
				if(preg_match('/(apache|litespeed|lsws)/is', $_SERVER["SERVER_SOFTWARE"])){
					// Supported. Do nothing
				}else{
					echo '<tr>
					<td scope="row" valign="top" colspan="2">
						<div style="color:#a94442; background-color:#f2dede; border-color:#ebccd1; padding:15px; border:1px solid transparent; border-radius:4px;">'.__('Rename wp-admin access feature is supported only on Apache and Litespeed', 'loginizer').'</div>
					</td>
					</tr>';
				}
				
				if(defined('LOGINIZER_PREMIUM') && !empty($loginizer['enable_csrf_protection']) && empty($loginizer['admin_slug'])){

					echo '<div style="color: #856404; background-color: #fff3cd; border-color: #ffeeba; padding: 15px; font-size:1rem; font-weight:400;">'.esc_html__('Note: Be careful while changing the Admin name as your CSRF Protection is on', 'loginizer').'</div>';
				
				}
			?>
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('You can rename your WordPress Admin access URL <b>wp-admin</b> to anything of your choice e.g. my-admin. This will require you to change .htaccess, so please follow','loginizer'); ?> <a href="<?php echo LOGINIZER_DOCS;?>Renaming_the_WP-Admin_Area" target="_blank"><?php echo __('our guide','loginizer').'</a> '.__('on how to do so !','loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label><?php echo __('New wp-admin Slug', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set blank to reset to the original wp-admin URL', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo (!empty($loginizer['admin_slug']) ? lz_optpost('admin_slug', $loginizer['admin_slug']) : ''); ?>" name="admin_slug" id="lz_admin_slug" onchange="lz_update_htaccess_admin(this)"/>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:200px !important">
					<label><?php echo __('Disable wp-admin access', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If set, then only the new admin slug will work and access to the Old Admin Slug i.e. wp-admin will be disabled. If anyone accesses wp-admin, a warning will be shown.<br><label>NOTE: Please use this option cautiously !</label>', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" id="lz_restrict_wp_admin" onchange="lz_wp_admin_msg_toggle()" value="1" name="restrict_wp_admin" <?php echo lz_POSTchecked('restrict_wp_admin', (empty($loginizer['restrict_wp_admin']) ? false : true)); ?> />
				</td>
			</tr>
			<tr id="lz_wp_admin_msg_row" style="display:none">
				<td scope="row" valign="top">
					<label><?php echo __('WP-Admin Error Message', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Error message to show if someone accesses wp-admin', 'loginizer'); ?></span> Default : <?php echo (!empty($loginizer['wp_admin_d_msg']) ? $loginizer['wp_admin_d_msg'] : ''); ?>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo lz_htmlizer(!empty($_POST['wp_admin_msg']) ? stripslashes($_POST['wp_admin_msg']) : (!empty($loginizer['wp_admin_msg']) ? $loginizer['wp_admin_msg'] : '')); ?>" name="wp_admin_msg" id="lz_wp_admin_msg" />
				</td>
			</tr>

			<?php
				loginizer_htaccess_rules();
			?>
			<tr>
				<td scope="row" valign="top" style="width:200px !important">
					<label><?php echo __('I have setup .htaccess', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('You need to confirm that you have configured .htaccess as per <a href="'.LOGINIZER_DOCS.'Renaming_the_WP-Admin_Area" target="_blank">our guide</a> so that we can safely enable this feature', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="lz_wp_admin_docs" />
					<input type="button" onclick="lz_test_wp_admin()" class="button" style="background: #5cb85c; color:white; border:#5cb85c" value="<?php echo __('Test New WP-Admin Slug', 'loginizer'); ?>" />
				</td>
			</tr>
		</table><br />
		<center><input name="save_lz_wp_admin" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>
</form>

<script type="text/javascript">
function lz_csrf_htaccess_update(e){
	event.preventDefault();
	
	var tb = jQuery(e).closest('table'),
	csrf_enabled = tb.find('[name="enable_csrf_protection"]'),
	admin_name = tb.find('#lz_admin_slug');
	
	var data = new Object();
	
	// Setting admin name if anything is set
	if(admin_name && admin_name.val()){
		data['admin_name'] = admin_name.val();
	}
	
	if(csrf_enabled){
		data['csrf'] = true;
	} else {
		data['csrf'] = false;
	}
	
	data['action'] = 'loginizer_update_csrf_mod';
	data['nonce']	= '<?php echo wp_create_nonce('loginizer_admin_ajax');?>';
	
	var new_ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>'
	
	// AJAX and on success function
	jQuery.post(new_ajaxurl, data, function(response){
		
		if(response['success'] == true){
			alert("<?php esc_html_e('.htaccess has been updated !', 'loginizer'); ?>");
		}
	
	// Throw an error for failures
	}).fail(function() {
		alert("<?php esc_html_e('Was unable to update the .htaccess file so please update it manually', 'loginizer'); ?>");
	});
	
	return false;
	
}

function lz_show_rewrite_rule(e){
	event.preventDefault();
	jQuery(e).closest('td').find('textarea').toggle();
}


</script>

<!-- Begin CSRF Protection -->
<form action="" method="post" loginizer-premium-only="1">
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php esc_html_e('CSRF Protection', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
			<?php wp_nonce_field('loginizer-options'); ?>
			<table class="form-table">
				<tr>
					<td scope="row" valign="top" colspan="2">
						<i><?php esc_html_e('This prevents CSRF attacks by adding a session string to admin URLs, making them nearly impossible for attackers to predict.', 'loginizer'); ?></i>
					</td>
				</tr>
				<tr>
					<td scope="row" valign="top" style="width:400px !important">
						<label><?php esc_html_e('Enable CSRF Protection', 'loginizer'); ?></label><br>
						<span class="exp"><?php esc_html_e('If enabled, it will update the URL of wp-admin with a random session string in the URL making it hard to predict the URL.', 'loginizer'); ?></span>
					</td>
					<td valign="top">
						<input type="checkbox" value="1" name="enable_csrf_protection" <?php echo lz_POSTchecked('enable_csrf_protection', (empty($loginizer['enable_csrf_protection']) ? false : true)); ?> />
					</td>
				</tr>
				<?php
					loginizer_htaccess_rules(true);
				?>
			</table><br />
			<div style="text-align: center;"><input name="save_lz_csrf_protection" class="button button-primary action" value="<?php esc_html_e('Save Settings', 'loginizer'); ?>" type="submit" />
			</div>
		</div>
	</div>	
</form>
<!-- End CSRF Protection -->


<script type="text/javascript">

function lz_wp_admin_msg_toggle(){
	var ele = jQuery('#lz_restrict_wp_admin')[0];
	if(ele.checked){
		jQuery('#lz_wp_admin_msg_row').show();
	}else{
		jQuery('#lz_wp_admin_msg_row').hide();
	}
};

lz_wp_admin_msg_toggle();

</script>
	

<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Change Admin Username', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('You can change the Admin Username from here to anything of your choice e.g. iamtheboss. This would make it very difficult for automated attack bots to know what is the admin username !', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label for="current_username"><?php echo __('Current Username', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('The current username you want to change', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo lz_optpost('current_username', (!empty($current_admin->user_login) ? $current_admin->user_login : '')); ?>" name="current_username" id="current_username" />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label for="new_username"><?php echo __('New Username', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('The new username you want to set', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" size="50" value="<?php echo lz_optpost('new_username', ''); ?>" name="new_username" id="new_username" />
				</td>
			</tr>
		</table><br />
		<i><?php echo __('Note: Username can be changed only for administrator users.'); ?></i>
		<center><input name="save_lz_admin" class="button button-primary action" value="<?php echo __('Set the Username', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>
</form>

<script type="text/javascript">
function add_lz_bl_users(){
	jQuery("#lz_bl_users").append('<input type="text" size="30" value="" name="lz_bl_users[]" class="lz_bl_users" />');
	return false;
};
</script>

<style>
.lz_bl_users, .lz_bl_domains{
	margin-bottom:20px;
}
</style>

<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Username Auto Blacklist', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('Attackers generally use common usernames like <b>admin, administrator, or variations of your domain name / business name</b>. You can specify such username here and Loginizer will auto-blacklist the IP Address(s) of clients who try to use such username(s).', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important; vertical-align:top !important;">
					<label><?php echo __('Username(s)', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('You can use - <b>*</b> (Star)- as a wild card as well. Blank fields will be ignored', 'loginizer'); ?></span>
				</td>
				<td>
					<div id="lz_bl_users">
					<?php
					
					$usernames = isset($_POST['lz_bl_users']) && is_array($_POST['lz_bl_users']) ? $_POST['lz_bl_users'] : (!empty($loginizer['username_blacklist']) ? $loginizer['username_blacklist'] : []);
					
					if(empty($usernames)){
						$usernames = array();
						$usernames[] = '';
					}
					
					foreach($usernames as $_user){
						
						// Disallow these special characters to avoid XSS or any other security vulnerability
						if(preg_match('/[\<\>\"\']/', $_user)){
							continue;
						}
						
						echo '<input type="text" size="30" value="'.$_user.'" name="lz_bl_users[]" class="lz_bl_users" />';
					}
					
					?>
					</div>
					<br />
					<input class="button" type="button" value="<?php echo __('Add New Username', 'loginizer'); ?>" onclick="return add_lz_bl_users();" style="float:right" />
				</td>
			</tr>
		</table><br />
		<center><input name="save_lz_bl_users" class="button button-primary action" value="<?php echo __('Save Username(s)', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>
</form>

<script type="text/javascript">
function add_lz_bl_domains(){
	jQuery("#lz_bl_domains").append('<input type="text" size="30" value="" name="lz_bl_domains[]" class="lz_bl_domains" />');
	return false;
};
</script>


<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('New Registration Domain Blacklist', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i>If you would like to ban new registrations from a particular domain, you can use this utility to do so.</i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important; vertical-align:top !important;">
					<label><?php echo __('Domain(s)', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('You can use - <b>*</b> (Star)- as a wild card as well. Blank fields will be ignored', 'loginizer'); ?></span>
				</td>
				<td>
					<div id="lz_bl_domains">
					<?php
					
					$domains = isset($_POST['lz_bl_domains']) && is_array($_POST['lz_bl_domains']) ? $_POST['lz_bl_domains'] : (!empty($loginizer['domains_blacklist']) ? $loginizer['domains_blacklist'] : []);
					
					if(empty($domains)){
						$domains = array();
						$domains[] = '';
					}
					
					foreach($domains as $_domain){
						
						// Disallow these special characters to avoid XSS or any other security vulnerability
						if(preg_match('/[\<\>\"\']/', $_domain)){
							continue;
						}
						
						echo '<input type="text" size="30" value="'.$_domain.'" name="lz_bl_domains[]" class="lz_bl_domains" />';
					}
					
					?>
					</div>
					<br />
					<input class="button" type="button" value="<?php echo __('Add New Domain', 'loginizer'); ?>" onclick="return add_lz_bl_domains();" style="float:right" />
				</td>
			</tr>
		</table><br />
		<center><input name="save_lz_bl_domains" class="button button-primary action" value="<?php echo __('Save Domains(s)', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>	
</form>

<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Limit Concurrent Sessions', 'loginizer'); ?>
		</h2>
		</div>
		
		<div class="inside">

		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php echo __('This feature will help limit the number of devices your user can login to concurrently', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:300px !important">
					<label><?php echo __('Enable', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Enabling it will start limiting number of devices the user can login on concurrently', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="checkbox" value="1" name="limit_session[enable]" <?php echo (!empty($_POST['limit_session']['enable']) || (!empty($loginizer['limit_session']['enable']))) ? 'checked' : false; ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:300px !important">
					<label><?php echo __('Limit Type', 'loginizer'); ?></label><br>
				</td>
				<td>
					<input type="radio" value="block" name="limit_session[type]" <?php echo ((!empty($_POST['limit_session']['type']) && $_POST['limit_session']['type'] == 'block') || (!empty($loginizer['limit_session']['type']) && $loginizer['limit_session']['type'] == 'block' ) ? 'checked' : false); ?> />
					<span class="exp"><?php echo '<strong>'.__('Block', 'loginizer') . ' : </strong>' . __('Blocks all the login attempts if limit is reached', 'loginizer'); ?></span><br/>
					<input type="radio" value="destroy" name="limit_session[type]" <?php echo ((!empty($_POST['limit_session']['type']) && $_POST['limit_session']['type'] == 'destroy') || (!empty($loginizer['limit_session']['type']) && $loginizer['limit_session']['type'] == 'destroy' ) ? 'checked' : false); ?> />
					<span class="exp"><?php echo '<strong>'.__('Destroy', 'loginizer') . ' : </strong>' . __('Revokes all the sessions on successful login', 'loginizer'); ?></span>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:40% !important">
					<label><?php echo __('Max Session Count', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Set Maximum number of sessions can be created', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="number" min="1" max="10" size="20" value="<?php echo (!empty($_POST['limit_session']['count']) ? esc_attr($_POST['limit_session']['count']) : (!empty($loginizer['limit_session']['count']) ? esc_attr($loginizer['limit_session']['count']) : 1)); ?>" name="limit_session[count]" />
				</td>
			</tr>
			<tr>
			<tr>
				<td scope="row" valign="top">
					<label><?php echo __('Exclude Roles', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('Excluded roles won\'t face session limit checks', 'loginizer'); ?></span>
				</td>
				<td>
				<div style="max-height:120px;; overflow-y:auto;">
			<?php	
			global $wp_roles;

			foreach($wp_roles->roles as $key => $role){
				$checked = '';

				if(!empty($_POST['limit_session']['roles']) && in_array($key, $_POST['limit_session']['roles'])
					|| !empty($loginizer['limit_session']['roles']) && in_array($key, $loginizer['limit_session']['roles'])){
						$checked = 'checked';
				}
				
				
				echo '<input type="checkbox" value="'.esc_attr($key).'" name="limit_session[roles][]" '.esc_attr($checked).'/>'. esc_html($role['name']) . '<br/>';
			}
			?>
			</div>
			</td>
			</tr>
		</table><br/>
		<center><input name="save_lz_limit_session" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
	
		</div>
	</div>	
</form>

<?php

}
	
	loginizer_page_footer();
	
}

// .htaccess UI options for wp-admin and CSRF
function loginizer_htaccess_rules($is_csrf = false){
	global $loginizer;
	
	$admin_slug = 'wp-admin';

	if(!empty($loginizer['admin_slug'])){
		$admin_slug = $loginizer['admin_slug'];
	}

	// getting sub directory if any
	$home_root = parse_url(home_url());

	if(isset($home_root['path'])){
		$home_root = trailingslashit($home_root['path']);
	} else {
		$home_root = '/';
	}
		
	// Selecting admin slug
	$admin_slug = 'wp-admin';

	if(!empty($loginizer['admin_slug'])){
		$admin_slug = $loginizer['admin_slug'];
	}
	
	// Setting the rule
	$rule = '# BEGIN Loginizer' . "\n";
	$rule .= '<IfModule mod_rewrite.c>' . "\n";
	$rule .= 'RewriteEngine On' . "\n";
	$rule .= 'RewriteBase ' . $home_root . "\n\n";
	$rule .= 'RewriteRule ^' . $admin_slug . '(-lzs.{20})?(/?)(.*) wp-admin/$3 [L]' . "\n";
	$rule .= '</IfModule>' . "\n";
	$rule .= '# END Loginizer' . "\n";

	if(is_writable(ABSPATH . '/.htaccess')){
		echo '<tr>
			<td scope="row" valign="top" style="width:400px !important">
				<label>'. esc_html__('Update .htaccess', 'loginizer').'</label><br>
				<span class="exp">'. (!empty($is_csrf) ? esc_html__('Rewrites rule for CSRF session URL', 'loginizer') : esc_html__('Rewrites rule to change wp-admin and if you have a Multisite then check', 'loginizer') . ' <a href="'.LOGINIZER_DOCS.'Renaming_the_WP-Admin_Area" target="_blank">our guide</a>') . '</span>
			</td>
			<td valign="top">
				<button class="button" style="background: #5cb85c; color:white; border:#5cb85c;" onclick="lz_csrf_htaccess_update(this)">Update .htaccess</button><a onClick="lz_show_rewrite_rule(this)" href="#" style="margin-left:5px; line-height: 2; font-weight:500;">Show Rewrite Rule</a><br/><br/>
				
				<textarea rows="8" readonly style="display:none;" class="lz-htaccess-textarea">' . trim($rule) . '</textarea>
			</td>
		</tr>';
		
	} else {
		echo '<tr>
			<td scope="row" valign="top" style="width:400px !important">
				<label>'. esc_html__('Manually Update .htaccess', 'loginizer') . '</label><br>
				<span class="exp">' . esc_html__('You can manually update your .htaccess by adding the given code at the top of your .htaccess file', 'loginizer'). '</span>
			</td>
			<td valign="top">
				<textarea rows="8" readonly class="lz-htaccess-textarea">' . trim($rule) . '</textarea>
			</td>
		</tr>';
	}
	
}