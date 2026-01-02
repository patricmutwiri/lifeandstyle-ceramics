<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

// The Loginizer Admin Options Page
function loginizer_page_brute_force(){

	global $wpdb, $wp_roles, $loginizer;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginizer-options');
	}
	
	// BEGIN THEME
	loginizer_page_header('Brute Force Settings');
	
	// Load the blacklist and whitelist
	$loginizer['blacklist'] = get_option('loginizer_blacklist');
	$loginizer['whitelist'] = get_option('loginizer_whitelist');
	
	// Disable Brute Force
	if(isset($_POST['disable_brute_lz'])){
		
		// Save the options
		update_option('loginizer_disable_brute', 1);
		
		$loginizer['disable_brute'] = 1;
		
		echo '<div id="message" class="updated"><p>'
			. __('The Brute Force Protection feature is now disabled', 'loginizer')
			. '</p></div><br />';
		
	}
	
	// Enable brute force
	if(isset($_POST['enable_brute_lz'])){
			
		// Save the options
		update_option('loginizer_disable_brute', 0);
		
		$loginizer['disable_brute'] = 0;
		
		echo '<div id="message" class="updated"><p>'
			. __('The Brute Force Protection feature is now enabled', 'loginizer')
			. '</p></div><br />';
		
	}
	
	if(isset($_POST['save_lz_login_email'])){
		$login_email['enable'] = (int) lz_optpost('loginizer_login_mail_enable');
		$login_email['disable_whitelist'] = (int) lz_optpost('loginizer_login_mail_disable_whitelist');
		$login_email['html_mail'] = (!empty(lz_optpost('loginizer_notify_html_mail')) ? true : false);
		$login_email['subject'] = sanitize_textarea_field(wp_unslash($_POST['loginizer_login_mail_subject']));
		$login_email['body'] = wp_kses_post(wp_unslash($_POST['loginizer_login_mail_body']));
		$login_email['roles'] = !empty($_POST['loginizer_login_mail_roles']) ? map_deep($_POST['loginizer_login_mail_roles'], 'sanitize_text_field') : [];

		// Save the options
		update_option('loginizer_login_mail', $login_email);
		$loginizer['login_mail'] = $login_email;

		// Mark as saved
		$GLOBALS['lz_saved'] = true;
	}
	
	// The Brute Force Settings
	if(isset($_POST['save_lz'])){
		
		$max_retries = (int) lz_optpost('max_retries');
		$lockout_time = (int) lz_optpost('lockout_time');
		$max_lockouts = (int) lz_optpost('max_lockouts');
		$lockouts_extend = (int) lz_optpost('lockouts_extend');
		$reset_retries = (int) lz_optpost('reset_retries');
		$notify_email = (int) lz_optpost('notify_email');
		$notify_email_address = lz_optpost('notify_email_address');
		$trusted_ips = lz_optpost('trusted_ips');
		$blocked_screen = lz_optpost('blocked_screen');
		
		if(!empty($notify_email_address) && !lz_valid_email($notify_email_address)){
			$error[] = __('Email address is invalid', 'loginizer');
		}
		
		if(empty(loginizer_is_whitelisted()) && isset($_POST['trusted_ips'])){
			$error[] = __('Add your IP to whitelist to enable Trusted IP\'s', 'loginizer');
		}
		
		if(!empty($max_retries) && $max_retries < 0){
			$error[] = __('Max Retries value is invalid', 'loginizer');
		}
		
		if(!empty($lockout_time) && $lockout_time < 0){
			$error[] = __('Lockout Time value is invalid', 'loginizer');
		}
		
		if(!empty($max_lockouts) && $max_lockouts < 0){
			$error[] = __('Max Lockouts value is invalid', 'loginizer');
		}
		
		if(!empty($lockouts_extend) && $lockouts_extend < 0){
			$error[] = __('Extended Lockout value is invalid', 'loginizer');
		}
		
		if(!empty($reset_retries) && $reset_retries < 0){
			$error[] = __('Reset Retries value is invalid', 'loginizer');
		}
		
		if(!empty($notify_email) && $notify_email < 0){
			$error[] = __('Email Notification value is invalid', 'loginizer');
		}
		
		$lockout_time = $lockout_time * 60;
		$lockouts_extend = $lockouts_extend * 60 * 60;
		$reset_retries = $reset_retries * 60 * 60;
		
		if(empty($error)){
			
			$option['max_retries'] = $max_retries;
			$option['lockout_time'] = $lockout_time;
			$option['max_lockouts'] = $max_lockouts;
			$option['lockouts_extend'] = $lockouts_extend;
			$option['reset_retries'] = $reset_retries;
			$option['notify_email'] = $notify_email;
			$option['notify_email_address'] = $notify_email_address;
			$option['trusted_ips'] = $trusted_ips;
			$option['blocked_screen'] = $blocked_screen;
			
			// Save the options
			update_option('loginizer_options', $option);
			
			$saved = true;
			
		}else{
			lz_report_error($error);
		}
	
		if(!empty($notice)){
			lz_report_notice($notice);	
		}
			
		if(!empty($saved)){
			echo '<div id="message" class="updated"><p>'
				. __('The settings were saved successfully', 'loginizer')
				. '</p></div><br />';
		}
	
	}
	
	// Delete a Blackist IP range
	if(isset($_POST['bdelid'])){
		
		$delid = (int) lz_optreq('bdelid');
		
		// Unset and save
		$blacklist = $loginizer['blacklist'];
		unset($blacklist[$delid]);
		update_option('loginizer_blacklist', $blacklist);
		
		echo '<div id="message" class="updated fade"><p>'
			. __('The Blacklist IP range has been deleted successfully', 'loginizer')
			. '</p></div><br />';
			
	}
	
	// Delete all Blackist IP ranges
	if(isset($_POST['del_all_blacklist'])){
		
		// Unset and save
		update_option('loginizer_blacklist', array());
		
		echo '<div id="message" class="updated fade"><p>'
			. __('The Blacklist IP range(s) have been cleared successfully', 'loginizer')
			. '</p></div><br />';
			
	}
	
	// Delete a Whitelist IP range
	if(isset($_POST['delid'])){
		
		$delid = (int) lz_optreq('delid');
		
		// Unset and save
		$whitelist = $loginizer['whitelist'];
		unset($whitelist[$delid]);
		update_option('loginizer_whitelist', $whitelist);
		
		echo '<div id="message" class="updated fade"><p>'
			. __('The Whitelist IP range has been deleted successfully', 'loginizer')
			. '</p></div><br />';
			
	}
	
	// Delete all Blackist IP ranges
	if(isset($_POST['del_all_whitelist'])){
		
		// Unset and save
		update_option('loginizer_whitelist', array());
		
		echo '<div id="message" class="updated fade"><p>'
			. __('The Whitelist IP range(s) have been cleared successfully', 'loginizer')
			. '</p></div><br />';
			
	}
	
	// Reset All Logs
	if(isset($_POST['lz_reset_all_ip'])){
	
		$result = $wpdb->query("DELETE FROM `".$wpdb->prefix."loginizer_logs` WHERE `time` > 0");
		
		echo '<div id="message" class="updated fade"><p>'
					. __('All the IP Logs have been cleared', 'loginizer')
					. '</p></div><br />';
	}
	
	// Reset Logs
	if(isset($_POST['lz_reset_ip']) && isset($_POST['lz_reset_ips']) && is_array($_POST['lz_reset_ips'])){

		$ips = $_POST['lz_reset_ips'];
		
		foreach($ips as $ip){
			if(!lz_valid_ip($ip)){
				$error[] = 'The IP - '.esc_html($ip).' is invalid !';
			}
		}
		
		if(count($ips) < 1){
			$error[] = __('There are no IPs submitted', 'loginizer');
		}
		
		// Should we start deleting logs
		if(empty($error)){
			
			foreach($ips as $ip){			
				$result = $wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."loginizer_logs` WHERE `ip` = %s", $ip));			
			}
			
			if(empty($error)){
				
				echo '<div id="message" class="updated fade"><p>'
						. __('The selected IP Logs have been reset', 'loginizer')
						. '</p></div><br />';
				
			}
			
		}
		
		if(!empty($error)){
			lz_report_error($error);echo '<br />';
		}
		
	}
	
	if(isset($_POST['blacklist_iprange'])){

		$start_ip = lz_optpost('start_ip');
		$end_ip = lz_optpost('end_ip');
		
		// If no end IP we consider only 1 IP
		if(empty($end_ip)){
			$end_ip = $start_ip;
		}
		
		// Validate the IP against all checks
		loginizer_iprange_validate($start_ip, $end_ip, $loginizer['blacklist'], $error);
		
		if(empty($error)){
		
			$blacklist = $loginizer['blacklist'];
			
			$newid = ( empty($blacklist) ? 0 : max(array_keys($blacklist)) ) + 1;
			
			$blacklist[$newid] = array();
			$blacklist[$newid]['start'] = $start_ip;
			$blacklist[$newid]['end'] = $end_ip;
			$blacklist[$newid]['time'] = time();
			
			update_option('loginizer_blacklist', $blacklist);
			
			echo '<div id="message" class="updated fade"><p>'
					. __('Blacklist IP range added successfully', 'loginizer')
					. '</p></div><br />';
			
		}
		
		if(!empty($error)){
			lz_report_error($error);echo '<br />';
		}
		
	}
	
	if(isset($_POST['whitelist_iprange'])){

		$start_ip = lz_optpost('start_ip_w');
		$end_ip = lz_optpost('end_ip_w');
		
		// If no end IP we consider only 1 IP
		if(empty($end_ip)){
			$end_ip = $start_ip;
		}
		
		// Validate the IP against all checks
		loginizer_iprange_validate($start_ip, $end_ip, $loginizer['whitelist'], $error);
		
		if(empty($error)){
			
			$whitelist = $loginizer['whitelist'];
			
			$newid = ( empty($whitelist) ? 0 : max(array_keys($whitelist)) ) + 1;
			
			$whitelist[$newid] = array();
			$whitelist[$newid]['start'] = $start_ip;
			$whitelist[$newid]['end'] = $end_ip;
			$whitelist[$newid]['time'] = time();
			
			update_option('loginizer_whitelist', $whitelist);
			
			echo '<div id="message" class="updated fade"><p>'
					. __('Whitelist IP range added successfully', 'loginizer')
					. '</p></div><br />';
			
		}

		if(!empty($error)){
			lz_report_error($error);echo '<br />';
		}
	}
	
	if(isset($_POST['lz_import_csv'])){

		if(!empty($_FILES['lz_import_file_csv']['name'])){

			$lz_csv_type = lz_optpost('lz_csv_type');
			
			// Is the submitted type in the allowed list ? 
			if(!in_array($lz_csv_type, array('blacklist', 'whitelist'))){
				$error[] = __('Invalid import type', 'loginizer');
			}
			
			if(empty($error)){
				
				//Get the extension of the file
				$csv_file_name = basename($_FILES['lz_import_file_csv']['name']);
				$csv_ext_name = strtolower(pathinfo($csv_file_name, PATHINFO_EXTENSION));

				//Check if it's a csv file
				if($csv_ext_name == 'csv'){
					
					$file = fopen($_FILES['lz_import_file_csv']['tmp_name'], "r");

					$line_count = 0;
					$update_record = 0;
					
					while($content = fgetcsv($file)){

						//Increment the $line_count
						$line_count++;
						
						//Skip the first line
						if($line_count <= 1){
							continue;
						}
						
						if(loginizer_iprange_validate($content[0], $content[1], $loginizer[$lz_csv_type], $error, $line_count)){
							
							$newid = ( empty($loginizer[$lz_csv_type]) ? 0 : max(array_keys($loginizer[$lz_csv_type])) ) + 1;
							
							$loginizer[$lz_csv_type][$newid] = array();
							$loginizer[$lz_csv_type][$newid]['start'] = $content[0];
							$loginizer[$lz_csv_type][$newid]['end'] = $content[1];
							$loginizer[$lz_csv_type][$newid]['time'] = time();
							
							$update_record = 1;
							
						}
					}
					
					fclose($file);
					
					if(!empty($update_record)){
						
						update_option('loginizer_'.$lz_csv_type, $loginizer[$lz_csv_type]);
						
						echo '<div id="message" class="updated fade"><p>'
								. __('Imported '.ucfirst($lz_csv_type).' IP range(s) successfully', 'loginizer')
								. '</p></div><br />';
						
					}
					
					if(!empty($error)){
						lz_report_error($error);echo '<br />';
					}
				}
				
			}
		}
	}
 
	//Brute Force Bulk Blacklist/ Whitelist Ip
	if(isset($_POST['lz_blacklist_selected_ip'])){
		if(isset($_POST['lz_reset_ips']) && is_array($_POST['lz_reset_ips'])){

			$ips = $_POST['lz_reset_ips'];
			
			foreach($ips as $ip){
				if(!lz_valid_ip($ip)){
					$error[] = sprintf(__('The IP - %s is invalid !', 'loginizer'), esc_html($ip));
				}
			}
			
			if(count($ips) < 1){
				$error[] = __('There are no IPs submitted', 'loginizer');
			}
			
			// Should we start deleting logs
			if(empty($error)){
				
				$update_record = 0;
				
				foreach($ips as $ip){
					
					if(loginizer_iprange_validate($ip, '', $loginizer['blacklist'], $error)){
							
						$newid = ( empty($loginizer['blacklist']) ? 0 : max(array_keys($loginizer['blacklist'])) ) + 1;
						
						$loginizer['blacklist'][$newid] = array();
						$loginizer['blacklist'][$newid]['start'] = $ip;
						$loginizer['blacklist'][$newid]['end'] = $ip;
						$loginizer['blacklist'][$newid]['time'] = time();
						
						$update_record = 1;
					}
				}
				
				if(!empty($update_record)){
						
					update_option('loginizer_blacklist', $loginizer['blacklist']);
					
					echo '<div id="message" class="updated fade"><p>'
							. __('The selected IP(s) have been blacklisted', 'loginizer')
							. '</p></div><br />';
					
				}
				
			}
		}else{
			$error[] = __('No IP(s) selected', 'loginizer');
		}
			
		if(!empty($error)){
			lz_report_error($error);echo '<br />';
		}
	}
	
	// Save the messages
	if(isset($_POST['save_err_msgs_lz'])){

		$msgs['inv_userpass'] = lz_optpost('msg_inv_userpass');
		$msgs['ip_blacklisted'] = lz_optpost('msg_ip_blacklisted');
		$msgs['attempts_left'] = lz_optpost('msg_attempts_left');
		$msgs['lockout_err'] = lz_optpost('msg_lockout_err');
		$msgs['minutes_err'] = lz_optpost('msg_minutes_err');
		$msgs['hours_err'] = lz_optpost('msg_hours_err');

		// Update them
		update_option('loginizer_msg', $msgs);
				
		echo '<div id="message" class="updated fade"><p>'
				. __('Error messages were saved successfully', 'loginizer')
				. '</p></div><br />';
				
	}

	// Count the Results
	$tmp = lz_selectquery("SELECT COUNT(*) AS num FROM `".$wpdb->prefix."loginizer_logs`");
	//print_r($tmp);
	
	// Which Page is it
	$lz_env['res_len'] = 10;
	$lz_env['cur_page'] = lz_get_page('lzpage', $lz_env['res_len']);
	$lz_env['num_res'] = $tmp['num'];
	$lz_env['max_page'] = ceil($lz_env['num_res'] / $lz_env['res_len']);
	
	// Get the logs
	$result = lz_selectquery("SELECT * FROM `".$wpdb->prefix."loginizer_logs` 
							ORDER BY `time` DESC 
							LIMIT ".$lz_env['cur_page'].", ".$lz_env['res_len']."", 1);
	//print_r($result);
	
	$lz_env['cur_page'] = ($lz_env['cur_page'] / $lz_env['res_len']) + 1;
	$lz_env['cur_page'] = $lz_env['cur_page'] < 1 ? 1 : $lz_env['cur_page'];
	$lz_env['next_page'] = ($lz_env['cur_page'] + 1) > $lz_env['max_page'] ? $lz_env['max_page'] : ($lz_env['cur_page'] + 1);
	$lz_env['prev_page'] = ($lz_env['cur_page'] - 1) < 1 ? 1 : ($lz_env['cur_page'] - 1);
	
	// Reload the settings
	$loginizer['blacklist'] = get_option('loginizer_blacklist');
	$loginizer['whitelist'] = get_option('loginizer_whitelist');
	
	$saved_msgs = get_option('loginizer_msg');
	
	?>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<?php echo '<span>'.__('Failed Login Attempts Logs', 'loginizer').'</span> &nbsp; ('.__('Past', 'loginizer').' '.($loginizer['reset_retries']/60/60).' '.__('hours', 'loginizer').')'; ?>
		</h2>
		</div>
		
		<script>
		function yesdsd(){
			window.location = '<?php echo menu_page_url('loginizer_brute_force', false);?>&lzpage='+jQuery("#current-page-selector").val();
			return false;
		}
		
		function lz_export_ajax(lz_csv_type){
	
			var data = new Object();
			data["action"] = lz_csv_type != "failed_login" ? "loginizer_export" : "loginizer_failed_login_export";
			data["lz_csv_type"] = lz_csv_type;
			data["nonce"]	= "<?php echo wp_create_nonce('loginizer_admin_ajax'); ?>";
			
			var admin_url = "<?php admin_url(); ?>"+"admin-ajax.php";
			
			jQuery.post(admin_url, data, function(response){
				
				// Was the ajax call successful ?
				if(response.substring(0,2) == "-1"){
					
					var err_message = response.substring(2);
					
					if(err_message){
						alert(err_message);
					}else{
						alert("Failed to export data");
					}
					
					return false;
				}
				
				/*
				* Make CSV downloadable
				*/
				var downloadLink = document.createElement("a");
				var fileData = ['\ufeff'+response];

				var blobObject = new Blob(fileData,{
				 type: "text/csv;charset=utf-8;"
				});

				var url = URL.createObjectURL(blobObject);
				downloadLink.href = url;
				downloadLink.download = "loginizer-"+lz_csv_type+".csv";

				/*
				* Actually download CSV
				*/
				document.body.appendChild(downloadLink);
				downloadLink.click();
				document.body.removeChild(downloadLink);
				
			});
			
		}
		
		</script>
		
		<form method="get" onsubmit="return yesdsd();">
			<div class="tablenav">
				<p class="tablenav-pages" style="margin: 5px 10px" align="right">
					<span class="displaying-num"><?php echo $lz_env['num_res'];?> items</span>
					<span class="pagination-links">
						<a class="first-page" href="<?php echo menu_page_url('loginizer_brute_force', false).'&lzpage=1';?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
						<a class="prev-page" href="<?php echo menu_page_url('loginizer_brute_force', false).'&lzpage='.$lz_env['prev_page'];?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
						<span class="paging-input">
							<label for="current-page-selector" class="screen-reader-text">Current Page</label>
							<input class="current-page" id="current-page-selector" name="lzpage" value="<?php echo $lz_env['cur_page'];?>" size="3" aria-describedby="table-paging" type="text"><span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $lz_env['max_page'];?></span></span>
						</span>						
						<a class="next-page" href="<?php echo menu_page_url('loginizer_brute_force', false).'&lzpage='.$lz_env['next_page'];?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
						<a class="last-page" href="<?php echo menu_page_url('loginizer_brute_force', false).'&lzpage='.$lz_env['max_page'];?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
					</span>
				</p>
			</div>
		</form>
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginizer-options'); ?>
		<div class="inside">
		<table class="wp-list-table widefat fixed users" border="0">
			<tr>
				<th scope="row" valign="top" style="background:#EFEFEF;" width="20"><input type="checkbox" id="lz_check_all_logs" onchange="lz_multiple_check()" style="margin-left:-1px;"/></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('IP','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Attempted Username','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Last Failed Attempt  (DD/MM/YYYY)','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Failed Attempts Count','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Lockouts Count','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;" width="150"><?php echo __('URL Attacked','loginizer'); ?></th>
			</tr>
			<?php
			
			if(empty($result)){
				echo '
				<tr>
					<td colspan="4">
						'.__('No Logs. You will see logs about failed login attempts here.', 'loginizer').'
					</td>
				</tr>';
			}else{
				foreach($result as $ik => $iv){
					$status_button = (!empty($iv['status']) ? 'disable' : 'enable');
					echo '
					<tr>
						<td>
							<input type="checkbox" value="'.esc_attr($iv['ip']).'" name="lz_reset_ips[]" class="lz_shift_select_logs lz_check_all_logs" />
						</td>
						<td>
							<a href="https://ipinfo.io/'.esc_html($iv['ip']).'" target="_blank">'.esc_html($iv['ip']).'&nbsp;<span class="dashicons dashicons-external"></span></a>
						</td>
						<td>
							'.esc_html($iv['username']).'
						</td>
						<td>
							'.date('d/M/Y H:i:s P', $iv['time']).'
						</td>
						<td>
							'.esc_html($iv['count']).'
						</td>
						<td>
							'.esc_html($iv['lockout']).'
						</td>
						<td>
							'.esc_html($iv['url']).'
						</td>
					</tr>';
				}
			}
			
			?>
		</table>
		
		<br>
		<input name="lz_reset_ip" class="button button-primary action" value="<?php echo __('Remove From Logs', 'loginizer'); ?>" type="submit" />
		&nbsp; &nbsp; 
		<input name="lz_reset_all_ip" class="button button-primary action" value="<?php echo __('Clear All Logs', 'loginizer'); ?>" type="submit" />
		&nbsp; &nbsp; 
		<input name="lz_blacklist_selected_ip" class="button button-primary action" value="<?php echo __('Blacklist Selected IPs', 'loginizer'); ?>" type="submit" />
		&nbsp; &nbsp; 
		<input name="lz_export_csv" onclick="lz_export_ajax('failed_login'); return false;" class="button button-primary action" value="<?php echo __('Export CSV', 'loginizer'); ?>" type="submit" />
		</div>
	</div>
	</form>
	<br />
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Brute Force Settings', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="max_retries"><?php echo __('Max Retries','loginizer'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo lz_optpost('max_retries', $loginizer['max_retries']); ?>" name="max_retries" id="max_retries" /> <?php echo __('Maximum failed attempts allowed before lockout','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="lockout_time"><?php echo __('Lockout Time','loginizer'); ?></label></th>
				<td>
				<input type="text" size="3" value="<?php echo (!empty($lockout_time) ? $lockout_time : $loginizer['lockout_time']) / 60; ?>" name="lockout_time" id="lockout_time" /> <?php echo __('minutes','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="max_lockouts"><?php echo __('Max Lockouts','loginizer'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo lz_optpost('max_lockouts', $loginizer['max_lockouts']); ?>" name="max_lockouts" id="max_lockouts" /> <?php echo __('','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="lockouts_extend"><?php echo __('Extend Lockout','loginizer'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo (!empty($lockouts_extend) ? $lockouts_extend : $loginizer['lockouts_extend']) / 60 / 60; ?>" name="lockouts_extend" id="lockouts_extend" /> <?php echo __('hours. Extend Lockout time after Max Lockouts','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="reset_retries"><?php echo __('Reset Retries','loginizer'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo (!empty($reset_retries) ? $reset_retries : $loginizer['reset_retries']) / 60 / 60; ?>" name="reset_retries" id="reset_retries" /> <?php echo __('hours','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="notify_email"><?php echo __('Email Notification','loginizer'); ?></label></th>
				<td>
					<?php echo __('after ','loginizer'); ?>
					<input type="text" size="3" value="<?php echo (!empty($notify_email) ? $notify_email : $loginizer['notify_email']); ?>" name="notify_email" id="notify_email" /> <?php echo __('lockouts <br />0 to disable email notifications','loginizer'); ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="notify_email_address"><?php echo __('Email Address','loginizer'); ?></label></th>
				<td>
					<input type="text" value="<?php echo (!empty($notify_email_address) ? $notify_email_address : (!empty($loginizer['custom_notify_email']) ? $loginizer['notify_email_address'] : '')); ?>" name="notify_email_address" id="notify_email_address" size="30" /> <br /><?php echo __('failed login attempts notifications will be sent to this email','loginizer'); ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="trusted_ips"><?php echo __('Trusted IP\'s','loginizer'); ?></label></th>
				<td>
					<input type="checkbox" <?php echo lz_POSTchecked('trusted_ips', (empty($loginizer['trusted_ips']) ? false : true)); ?> name="trusted_ips" id="trusted_ips"/>
					<?php _e('If enabled Loginizer will only allow whitlisted IP\'s to Login.', 'loginizer'); ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="blocked_screen"><?php echo __('Blocked Screen','loginizer'); ?></label></th>
				<td>
					<input type="checkbox" <?php echo lz_POSTchecked('blocked_screen', (empty($loginizer['blocked_screen']) ? false : true)); ?> name="blocked_screen" id="blocked_screen"/>
					<?php _e('Shows an error page in place of the login page if the user is locked out or blacklisted, to prevent attackers from attempting to log in when locked out, which saves resources', 'loginizer'); ?>
				</td>
			</tr>
		</table><br />
		<input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings','loginizer'); ?>" type="submit" />
		<?php
		
		if(empty($loginizer['disable_brute'])){		
			
			echo '<input name="disable_brute_lz" class="button action" value="'.__('Disable Brute Force Protection','loginizer').'" type="submit" style="float:right" />';
			
		}else{
			
			echo '<input name="enable_brute_lz" class="button button-primary action" value="'.__('Enable Brute Force Protection','loginizer').'" type="submit" style="float:right" />';
			
		}
		
		?>
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
	jQuery('#lz_bl_table').paginate({ limit: 11, navigationWrapper: jQuery('#lz_bl_nav')});
	jQuery('#lz_wl_table').paginate({ limit: 11, navigationWrapper: jQuery('#lz_wl_nav')});
	lz_multiple_check();
	lz_shift_check_all('lz_shift_select_logs');
});

// Delete a Blacklist / Whitelist IP Range
function del_confirm(field, todo_id, msg){
	var ret = confirm(msg);
	
	if(ret){
		jQuery('#lz_bl_wl_todo').attr('name', field);
		jQuery('#lz_bl_wl_todo').val(todo_id);
		jQuery('#lz_bl_wl_form').submit();
	}
	
	return false;
	
}

// Delete all Blacklist / Whitelist IP Ranges
function del_confirm_all(msg){
	var ret = confirm(msg);
	
	if(ret){
		return true;
	}
	
	return false;
	
}

//Check all the failed log attempts
function lz_multiple_check(){
	jQuery("#lz_check_all_logs").on("click", function(event){
		if(this.checked == true){
			jQuery(".lz_check_all_logs").prop("checked", true);
		}else{
			jQuery(".lz_check_all_logs").prop("checked", false);
		}
	});
}

//To select the installations/backups using shift key
function lz_shift_check_all(check_class){ 

    var checkboxes = jQuery("."+check_class);
    var lastChecked = null;

    checkboxes.click(function(event){
        if(!lastChecked){
            lastChecked = this;
            return;
        }

        if(event.shiftKey){
            var start = checkboxes.index(this);
            var end = checkboxes.index(lastChecked);
			
            checkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop("checked", this.checked);
        }

        lastChecked = this;
    });
};

</script>
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Blacklist IP','loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php echo __('Enter the IP you want to blacklist from login','loginizer'); ?>
	
		<form action="" method="post">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="start_ip"><?php echo __('Start IP','loginizer'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(lz_optpost('start_ip')); ?>" name="start_ip" id="start_ip"/> <?php echo __('Start IP of the range','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="end_ip"><?php echo __('End IP (Optional)','loginizer'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(lz_optpost('end_ip')); ?>" name="end_ip" id="end_ip"/> <?php echo __('End IP of the range. <br />If you want to blacklist single IP leave this field blank.','loginizer'); ?> <br />
				</td>
			</tr>
		</table><br />
		<input name="blacklist_iprange" class="button button-primary action" value="<?php echo __('Add Blacklist IP Range','loginizer'); ?>" type="submit" />
		<input style="float:right" name="del_all_blacklist" onclick="return del_confirm_all('<?php echo __('Are you sure you want to delete all Blacklist IP Range(s) ?','loginizer'); ?>')" class="button action" value="<?php echo __('Delete All Blacklist IP Range(s)','loginizer'); ?>" type="submit" />
		</form>
		</div>
		
		<div id="lz_bl_nav" style="margin: 5px 10px; text-align:right"></div>
		
		<!--Brute Force Blacklist Import CSV Form-->
		<div class="inside" id="blacklist_csv" style="display:none;">
			<form action="" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('loginizer-options'); ?>
				<input type="hidden" value="blacklist" name="lz_csv_type" />
				<h3><?php echo __('Import Blacklist IPs (CSV)', 'loginizer'); ?>:</h3>
				<input type="file" name="lz_import_file_csv" value="Import CSV" />
				<br><br>
				<input name="lz_import_csv" class="button button-primary action" value="<?php echo __('Submit', 'loginizer'); ?>" type="submit" />
			</form>
		</div>
		<!---->
		
		<!--Brute Force Blacklist Export CSV Form-->
		<div class="inside" style="float:right;">
			<form action="" method="post">
				<?php wp_nonce_field('loginizer-options'); ?>
				<input type="hidden" value="blacklist" name="lz_csv_type" />
				<input class="button button-primary action" value="<?php echo __('Import CSV', 'loginizer'); ?>" type="button" onclick="jQuery('#blacklist_csv').toggle();"/>
				<input name="lz_export_csv" onclick="lz_export_ajax('blacklist'); return false;" class="button button-primary action" value="<?php echo __('Export CSV', 'loginizer'); ?>" type="submit" />
			</form>
		
		</div>
		<!---->
		
		<table id="lz_bl_table" class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
			<tr>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Start IP','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('End IP','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Date (DD/MM/YYYY)','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;" width="100"><?php echo __('Options','loginizer'); ?></th>
			</tr>
			<?php
				if(empty($loginizer['blacklist'])){
					echo '
					<tr>
						<td colspan="4">
							'.__('No Blacklist IPs. You will see blacklisted IP ranges here.', 'loginizer').'
						</td>
					</tr>';
				}else{
					foreach($loginizer['blacklist'] as $ik => $iv){
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
								<a class="submitdelete" href="javascript:void(0)" onclick="return del_confirm(\'bdelid\', '.$ik.', \'Are you sure you want to delete this IP range ?\')">Delete</a>
							</td>
						</tr>';
					}
				}
			?>
		</table>
		<br />
		<form action="" method="post" id="lz_bl_wl_form">
		<?php wp_nonce_field('loginizer-options'); ?>
		<input type="hidden" value="" name="" id="lz_bl_wl_todo"/> 
		</form>
	</div>
	
	<br />
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Whitelist IP', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<?php echo __('Enter the IP you want to whitelist for login','loginizer'); ?>
		<form action="" method="post">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="start_ip_w"><?php echo __('Start IP','loginizer'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(lz_optpost('start_ip_w')); ?>" name="start_ip_w" id="start_ip_w"/> <?php echo __('Start IP of the range','loginizer'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="end_ip_w"><?php echo __('End IP (Optional)','loginizer'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(lz_optpost('end_ip_w')); ?>" name="end_ip_w" id="end_ip_w"/> <?php echo __('End IP of the range. <br />If you want to whitelist single IP leave this field blank.','loginizer'); ?> <br />
				</td>
			</tr>
		</table><br />
		<input name="whitelist_iprange" class="button button-primary action" value="<?php echo __('Add Whitelist IP Range','loginizer'); ?>" type="submit" />		
		<input style="float:right" name="del_all_whitelist" onclick="return del_confirm_all('<?php echo __('Are you sure you want to delete all Whitelist IP Range(s) ?','loginizer'); ?>')" class="button action" value="<?php echo __('Delete All Whitelist IP Range(s)','loginizer'); ?>" type="submit" />
		</form>
		</div>
		
		<div id="lz_wl_nav" style="margin: 5px 10px; text-align:right"></div>
		
		<!--Brute Force Whitelist Import CSV Form-->
		<div class="inside" id="lz_whitelist_csv_div" style="display:none;">
			<form action="" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('loginizer-options'); ?>
				<input type="hidden" value="whitelist" name="lz_csv_type" />
				<h3><?php echo __('Import Whitelist IPs (CSV)', 'loginizer'); ?>:</h3>
				<input type="file" name="lz_import_file_csv" value="Import CSV" />
				<br><br>
				<input name="lz_import_csv" class="button button-primary action" value="<?php echo __('Submit', 'loginizer'); ?>" type="submit" />
			</form>
		</div>
		<!---->
		
		<!--Brute Force Whitelist Export CSV Form-->
		<div class="inside" style="float:right;">
			<form action="" method="post">
				<?php wp_nonce_field('loginizer-options'); ?>
				<input type="hidden" value="whitelist" name="lz_csv_type" />
				<input class="button button-primary action" value="<?php echo __('Import CSV', 'loginizer'); ?>" type="button" onclick="jQuery('#lz_whitelist_csv_div').toggle();"/>
				<input name="lz_export_csv" onclick="lz_export_ajax('whitelist'); return false;" class="button button-primary action" value="<?php echo __('Export CSV', 'loginizer'); ?>" type="submit" />
			</form>
		</div>
		<!---->
		
		<table id="lz_wl_table" class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
		<tr>
			<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Start IP','loginizer'); ?></th>
			<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('End IP','loginizer'); ?></th>
			<th scope="row" valign="top" style="background:#EFEFEF;"><?php echo __('Date (DD/MM/YYYY)','loginizer'); ?></th>
			<th scope="row" valign="top" style="background:#EFEFEF;" width="100"><?php echo __('Options','loginizer'); ?></th>
		</tr>
		<?php
			if(empty($loginizer['whitelist'])){
				echo '
				<tr>
					<td colspan="4">
						'.__('No Whitelist IPs. You will see whitelisted IP ranges here.', 'loginizer').'
					</td>
				</tr>';
			}else{
				foreach($loginizer['whitelist'] as $ik => $iv){
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
							<a class="submitdelete" href="javascript:void(0)" onclick="return del_confirm(\'delid\', '.$ik.', \'Are you sure you want to delete this IP range ?\')">Delete</a>
						</td>
					</tr>';
				}
			}
		?>
		</table>
		<br />
	
	</div>

	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Error Messages', 'loginizer'); ?></span>
		</h2>
		</div>

		<div class="inside">

			<form action="" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('loginizer-options'); ?>
				<table class="form-table">
					<tr>
						<th scope="row" valign="top"><label for="msg_inv_userpass"><?php echo __('Failed Login Attempt','loginizer'); ?></label></th>
						<td>
							<input type="text" size="25" value="<?php echo (empty($saved_msgs['inv_userpass']) ? '' : esc_attr($saved_msgs['inv_userpass'])); ?>" name="msg_inv_userpass" id="msg_inv_userpass" />
							<?php echo __('Default: <em>&quot;' . $loginizer['d_msg']['inv_userpass']. '&quot;</em>', 'loginizer'); ?><br />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="msg_ip_blacklisted"><?php echo __('Blacklisted IP','loginizer'); ?></label></th>
						<td>
							<input type="text" size="25" value="<?php echo (empty($saved_msgs['ip_blacklisted']) ? '' : esc_attr($saved_msgs['ip_blacklisted'])); ?>" name="msg_ip_blacklisted" id="msg_ip_blacklisted" />
							<?php echo __('Default: <em>&quot;' . $loginizer['d_msg']['ip_blacklisted']. '&quot;</em>', 'loginizer'); ?><br />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="msg_attempts_left"><?php echo __('Attempts Left','loginizer'); ?></label></th>
						<td>
							<input type="text" size="25" value="<?php echo (empty($saved_msgs['attempts_left']) ? '' : esc_attr($saved_msgs['attempts_left'])); ?>" name="msg_attempts_left" id="msg_attempts_left" />
							<?php echo __('Default: <em>&quot;' . $loginizer['d_msg']['attempts_left']. '&quot;</em>', 'loginizer'); ?><br />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="msg_lockout_err"><?php echo __('Lockout Error','loginizer'); ?></label></th>
						<td>
							<input type="text" size="25" value="<?php echo (empty($saved_msgs['lockout_err']) ? '' : esc_attr($saved_msgs['lockout_err'])); ?>" name="msg_lockout_err" id="msg_lockout_err" />
							<?php echo __('Default: <em>&quot;' . strip_tags($loginizer['d_msg']['lockout_err']). '&quot;</em>', 'loginizer'); ?><br />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="msg_minutes_err"><?php echo __('Minutes','loginizer'); ?></label></th>
						<td>
							<input type="text" size="25" value="<?php echo (empty($saved_msgs['minutes_err']) ? '' : esc_attr($saved_msgs['minutes_err'])); ?>" name="msg_minutes_err" id="msg_minutes_err" />
							<?php echo __('Default: <em>&quot;' . strip_tags($loginizer['d_msg']['minutes_err']). '&quot;</em>', 'loginizer'); ?><br />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label for="msg_hours_err"><?php echo __('Hours','loginizer'); ?></label></th>
						<td>
							<input type="text" size="25" value="<?php echo (empty($saved_msgs['hours_err']) ? '' : esc_attr($saved_msgs['hours_err'])); ?>" name="msg_hours_err" id="msg_hours_err" />
							<?php echo __('Default: <em>&quot;' . strip_tags($loginizer['d_msg']['hours_err']). '&quot;</em>', 'loginizer'); ?><br />
						</td>
					</tr>
				</table><br />
				<input name="save_err_msgs_lz" class="button button-primary action" value="<?php echo __('Save Error Messages','loginizer'); ?>" type="submit" />
			</form>
		</div>
	</div>
	
	<div id="" class="postbox">
		<div class="postbox-header">
			<h2 class="hndle ui-sortable-handle">
				<span><?php echo __('Login Notification', 'loginizer');?></span>
			</h2>
		</div>
		<div class="inside">
			<form action="" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field('loginizer-options'); ?>
			<table class="form-table">
				<tr>
					<td scope="row" valign="top" style="width:350px !important">
						<label for="loginizer_login_mail_enable"><?php echo __('Enable Notification', 'loginizer'); ?></label>
						<p class="description"><?php echo __('If enabled, user will get notified about successful login attempt.', 'loginizer'); ?></p>
					</td>
					<td>
						<input type="checkbox" value="1" name="loginizer_login_mail_enable" id="loginizer_login_mail_enable" <?php echo lz_POSTchecked('loginizer_login_mail_enable', (empty($loginizer['login_mail']['enable']) ? false : true)); ?> />

					</td>
				</tr>

				<tr>
					<td scope="row" valign="top" style="width:350px !important">
						<label for="loginizer_notify_disable_whitelist"><?php echo __('Disable for whitelisted IPs', 'loginizer'); ?></label>
						<p class="description"><?php echo __("If checked, don't notify whitelisted IPs.", 'loginizer'); ?></p>
					</td>
					<td>
						<input type="checkbox" value="1" name="loginizer_login_mail_disable_whitelist" id="loginizer_login_mail_disable_whitelist" <?php echo lz_POSTchecked('loginizer_login_mail_disable_whitelist', (empty($loginizer['login_mail']['disable_whitelist']) ? false : true)); ?> />
					</td>
				</tr>
				
				<tr>
					<td scope="row" valign="top" style="width:350px !important">
						<label for="loginizer_notify_html_mail"><?php echo __('Send email as HTML', 'loginizer'); ?></label>
					</td>
					<td>
						<input type="checkbox" value="1" name="loginizer_notify_html_mail" id="loginizer_notify_html_mail" <?php echo lz_POSTchecked('loginizer_notify_html_mail', (empty($loginizer['login_mail']['html_mail']) ? false : true)); ?> />
					</td>
				</tr>

				<tr>
					<td scope="row" valign="top">
						<label for="loginizer_login_mail_subject"><?php echo __('Email Subject', 'loginizer'); ?></label><br>
						<span class="exp"><?php echo __('Set blank to reset to the default subject', 'loginizer'); ?></span>
						<br />Default : <pre style="font-size:10px"><?php echo esc_html($loginizer['login_mail_default_sub']); ?></pre>
					</td>
					<td valign="top">
						<input type="text" size="40" value="<?php echo !empty($_POST['loginizer_login_mail_subject']) ? esc_html(wp_unslash($_POST['loginizer_login_mail_subject'])) : (empty($loginizer['login_mail']['subject']) ? '' : esc_html($loginizer['login_mail']['subject'])); ?>" name="loginizer_login_mail_subject" id="loginizer_login_mail_subject" />
						<br />Variables :
						<br />$sitename - The Site Name
						<br />$user_login - User Name
					</td>
				</tr>

				<tr>
					<td scope="row" valign="top">
						<label for="loginizer_login_mail_body"><?php echo __('Email Body', 'loginizer'); ?></label><br>
						<span class="exp"><?php echo __('Set blank to reset to the default message', 'loginizer'); ?></span>
						<br />Default : <pre style="font-size:10px"><?php echo esc_html($loginizer['login_mail_default_msg']); ?></pre>
					</td>
					<td valign="top">
						<textarea rows="10" style="width:70%" name="loginizer_login_mail_body" id="loginizer_login_mail_body"><?php echo !empty($_POST['loginizer_login_mail_body']) ? esc_html(wp_unslash($_POST['loginizer_login_mail_body'])) : (empty($loginizer['login_mail']['body']) ? '' : esc_html($loginizer['login_mail']['body'])); ?></textarea>
						<br />Variables :
						<br />$sitename - The Site Name
						<br />$user_login - User Name
						<br />$date - Time and Date ( current date and time of Login )
						<br />$ip - Device IP Address from which login happned
					</td>
						
					</td>
				</tr>
				<tr><br>
					<td scope="row" valign="top" style="width:350px !important">
						<label for="loginizer_login_mail_roles"><?php echo __('Select Roles', 'loginizer'); ?></label><br/>
						<span class="exp"><?php echo __('Select the user roles for whom you want to send successful login notification.', 'loginizer'); ?></span>
					</td>
					<td align="top">
					<?php
						$editable_roles = get_editable_roles();
						echo '<div style="max-height:150px; overflow:auto;">';

						foreach($editable_roles as $role => $details) {
							$name = translate_user_role($details['name']);
							// Preselect specified role.
							if((!empty($loginizer['login_mail']['roles']) && in_array($role, $loginizer['login_mail']['roles'])) || (!empty($_POST['loginizer_login_mail_roles']) && in_array($role, $_POST['loginizer_login_mail_roles']))){
								echo '<input type="checkbox" checked name="loginizer_login_mail_roles[]" value="' . esc_attr($role) . '" style="margin-top:5px">'.esc_html($name).'</option>';
							} else {
								echo '<input type="checkbox" value="' . esc_attr($role) . '" name="loginizer_login_mail_roles[]">'.esc_html($name).'</option>';
							}

							echo '<br/>';
						}
						echo '</div>';
					?>
					</td>
				</tr>
			</table><br />
			<center><input name="save_lz_login_email" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /></center>
			</form>
		
		</div>
	</div>

<?php

loginizer_page_footer();

}

// IP range validations
function loginizer_iprange_validate($start_ip, $end_ip, $cur_list, &$error = array(), $line_count = ''){
	
	$line_error = '';
	if(!empty($line_count)){
		$line_error = ' '.__('Line no.', 'loginizer').' '.$line_count;
	}
			
	if(empty($start_ip)){
		$cur_error[] = __('Please enter the Start IP', 'loginizer').$line_error;
	}

	// If no end IP we consider only 1 IP
	if(empty($end_ip)){
		$end_ip = $start_ip;
	}
	
	if(!lz_valid_ip($start_ip)){
		$cur_error[] = __('Please provide a valid start IP', 'loginizer').$line_error;
	}
	
	if(!lz_valid_ip($end_ip)){
		$cur_error[] = __('Please provide a valid end IP', 'loginizer').$line_error;
	}
	
	if(inet_ptoi($start_ip) > inet_ptoi($end_ip)){
		
		// BUT, if 0.0.0.1 - 255.255.255.255 is given, it will not work
		if(inet_ptoi($start_ip) >= 0 && inet_ptoi($end_ip) < 0){
			// This is right
		}else{
			$cur_error[] = __('The End IP cannot be smaller than the Start IP', 'loginizer').$line_error;
		}
		
	}
			
	if(!empty($cur_error)){
		
		foreach($cur_error as $rk => $rv){
			$error[] = $rv;
		}
		
		return false;
	}
	
	if(!empty($cur_list)){
		
		foreach($cur_list as $k => $v){
			
			// This is to check if there is any other range exists with the same Start or End IP
			if(( inet_ptoi($start_ip) <= inet_ptoi($v['start']) && inet_ptoi($v['start']) <= inet_ptoi($end_ip) )
				|| ( inet_ptoi($start_ip) <= inet_ptoi($v['end']) && inet_ptoi($v['end']) <= inet_ptoi($end_ip) )
			){
				$cur_error[] = __('The Start IP or End IP submitted conflicts with an existing IP range !', 'loginizer').$line_error;
				break;
			}
			
			// This is to check if there is any other range exists with the same Start IP
			if(inet_ptoi($v['start']) <= inet_ptoi($start_ip) && inet_ptoi($start_ip) <= inet_ptoi($v['end'])){
				$cur_error[] = __('The Start IP is present in an existing range !', 'loginizer').$line_error;
				break;
			}
			
			// This is to check if there is any other range exists with the same End IP
			if(inet_ptoi($v['start']) <= inet_ptoi($end_ip) && inet_ptoi($end_ip) <= inet_ptoi($v['end'])){
				$cur_error[] = __('The End IP is present in an existing range!', 'loginizer').$line_error;
				break;
			}
			
		}
		
	}
			
	if(!empty($cur_error)){
		
		foreach($cur_error as $rk => $rv){
			$error[] = $rv;
		}
		
		return false;
	}
	
	return true;
}