<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}



// Loginizer - Checksum load data
function loginizer_page_checksums_L(&$files, &$_ignores){
	
	global $loginizer, $lz_error, $lz_env;
	
	// Load any mismatched files and ignores
	$files = get_option('loginizer_checksums_diff');
	$_ignores = get_option('loginizer_checksums_ignore');
	$_ignores = is_array($_ignores) ? $_ignores : array(); // SHOULD ALWAYS BE PURE
	$ignores = array();
	
	foreach($_ignores as $ik => $iv){
		$ignores[$iv] = array();
		if(!empty($files[$iv])){
			$ignores[$iv] = $files[$iv];
		}
	}
	
	$lz_env['files'] = $files;
	$lz_env['ignores'] = $ignores;

}
	
// Loginizer - PasswordLess Page
function loginizer_page_checksums(){
	
	global $loginizer, $lz_error, $lz_env;
	
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	if(!loginizer_is_premium() && count($_POST) > 0){
		$lz_error['not_in_free'] = __('This feature is not available in the Free version. <a href="'.LOGINIZER_PRICING_URL.'" target="_blank" style="text-decoration:none; color:green;"><b>Upgrade to Pro</b></a>', 'loginizer');
		return loginizer_page_checksums_T();
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginizer-options');
	}
	
	// Are we to run it ?
	if(isset($_REQUEST['lz_run_checksum'])){
		loginizer_checksums();
	}
	
	loginizer_page_checksums_L($files, $_ignores);
	
	$lz_env['csum_freq'][1] = __('Once a Day', 'loginizer');
	$lz_env['csum_freq'][7] = __('Once a Week', 'loginizer');
	$lz_env['csum_freq'][30] = __('Once a Month', 'loginizer');
	
	if(isset($_POST['save_lz'])){
		
		// In the future there can be more settings
		$option['disable_checksum'] = (int) lz_optpost('disable_checksum');
		$option['no_checksum_email'] = (int) lz_optpost('no_checksum_email');
		$option['checksum_frequency'] = (int) lz_optpost('checksum_frequency');
		$option['checksum_time'] = lz_optpost('checksum_time');
		
		// Is there an error ?
		if(!empty($lz_error)){
			return loginizer_page_checksums_T();
		}
		
		// Save the options
		update_option('loginizer_checksums', $option);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	// Add or remove from ignore list
	if(isset($_POST['save_lz_csum_ig'])){
		
		if(@is_array($_POST['checksum_del_ignore'])){
			
			foreach($_POST['checksum_del_ignore'] as $k => $v){
				$key = array_search($v, $_ignores);
				if($key !== false){
					unset($_ignores[$key]);
				}
			}
			
			// Save it
			update_option('loginizer_checksums_ignore', $_ignores);
			
		}
		
		if(@is_array($_POST['checksum_add_ignore'])){
			
			foreach($_POST['checksum_add_ignore'] as $k => $v){
				if(!empty($files[$v])){
					$_ignores[] = $v;
				}
			}
			
			// Save it
			update_option('loginizer_checksums_ignore', $_ignores);
			
		}
		
		// Reload
		loginizer_page_checksums_L($files, $_ignores);
		
		// Mark as saved
		$GLOBALS['lz_saved'] = true;
		
	}
	
	// Call theme
	loginizer_page_checksums_T();
}

// Loginizer - PasswordLess Page Theme
function loginizer_page_checksums_T(){
	
	global $loginizer, $lz_error, $lz_env;
	
	// Universal header
	loginizer_page_header('File Checksum Settings');
	
	loginizer_feature_available('File Checksum');
	
	if(defined('LOGINIZER_PRO_DIR_URL')){
		wp_enqueue_script('jquery-clockpicker', LOGINIZER_PRO_DIR_URL.'/assets/js/jquery-clockpicker.min.js', array('jquery'), '0.0.7');
		wp_enqueue_style('jquery-clockpicker', LOGINIZER_PRO_DIR_URL.'/assets/css/jquery-clockpicker.min.css', array(), '0.0.7');
	}
	
	// Saved ?
	if(!empty($GLOBALS['lz_saved'])){
		echo '<div id="message" class="updated"><p>'. __('The settings were saved successfully', 'loginizer'). '</p></div><br />';
	}
	
	// Did we just run the checksums
	if(isset($_REQUEST['lz_run_checksum'])){
		echo '<div id="message" class="updated"><p>'. __('The Checksum process was executed successfully', 'loginizer'). '</p></div><br />';
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

<script>
function lz_apply_status(ele, the_class){
	
	var status = ele.checked;
	jQuery(the_class).each(function(){
		this.checked = status;
	});
	
}
</script>

	<div id="" class="postbox">
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Checksum Settings', 'loginizer'); ?></span>
		</h2>
		</div>
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" style="width:400px !important">
					<label><?php echo __('Disable Checksum of WP Core', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If disabled, Loginizer will not check your sites core files against the WordPress checksum list.', 'loginizer'); ?></span>
				</td>
				<td valign="top">
					<input type="checkbox" value="1" name="disable_checksum" <?php echo lz_POSTchecked('disable_checksum', (empty($loginizer['disable_checksum']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:400px !important">
					<label><?php echo __('Disable Email of Checksum Results', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If checked, Loginizer will not email you the checksum results.', 'loginizer'); ?></span>
				</td>
				<td valign="top">
					<input type="checkbox" value="1" name="no_checksum_email" <?php echo lz_POSTchecked('no_checksum_email', (empty($loginizer['no_checksum_email']) ? false : true)); ?> />
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:400px !important">
					<label><?php echo __('Checksum Frequency', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If Checksum is enabled, at what frequency should the checksums be performed.', 'loginizer'); ?></span>
				</td>
				<td valign="top">					
					<select name="checksum_frequency">
						<?php
							foreach($lz_env['csum_freq'] as $k => $v){
								echo '<option '.lz_POSTselect('checksum_frequency', $k, ((!empty($loginizer['checksum_frequency']) && $loginizer['checksum_frequency'] == $k) ? true : false)).' value="'.$k.'">'.$v.'</value>';								
							}
						?>
					</select>
				</td>
			</tr>
			<tr id="lz_checksum_time">
				<td scope="row" valign="top" style="width:400px !important">
					<label><?php echo __('Time of Day', 'loginizer'); ?></label><br>
					<span class="exp"><?php echo __('If Checksum is enabled, what time of day should Loginizer do the check. Note : The check will be done on or after this time has elapsed as per the accesses being made.', 'loginizer'); ?></span>
				</td>
				<td valign="top">
					<div class="input-group clockpicker" data-autoclose="true">
						<input type="text" name="checksum_time" class="form-control" value="<?php echo (empty($loginizer['checksum_time']) ? '00:00' : $loginizer['checksum_time']);?>">
						<span class="input-group-addon">
							<span class="glyphicon glyphicon-time"></span>
						</span>
					</div>
					<script type="text/javascript">
					jQuery(document).ready(function(){
						(function($) {
							$('.clockpicker').clockpicker({donetext: 'Done'});
						})(jQuery);
					});
					</script>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php echo __('If disabled, Loginizer will not check your sites core files against the WordPress checksum list.', 'loginizer'); ?>
				</td>
			</tr>
		</table><br />
		<center><input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings', 'loginizer'); ?>" type="submit" /><input name="lz_run_checksum" style="float:right; background: #5cb85c; color:white; border:#5cb85c" class="button button-secondary" value="<?php echo __('Do a Checksum Now', 'loginizer'); ?>" type="submit" /></center>
		</form>
	
		</div>
	</div>
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Mismatching Files', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="wp-list-table fixed striped users" border="0" width="100%" cellpadding="10" align="center">
			<?php
			
			$files = $lz_env['files'];
			
			// Avoid undefined notice for $files
			if(!empty($files)){
				foreach($files as $k => $v){
					if(!empty($lz_env['ignores'][$k])){
						unset($files[$k]);
					}
				}
			}
			
			echo '
			<tr>
				<th style="background:#EFEFEF;">'.__('Relative Path', 'loginizer').'</th>
				<th style="width:240px; background:#EFEFEF;">'.__('Found', 'loginizer').'</th>
				<th style="width:240px; background:#EFEFEF;">'.__('Should be', 'loginizer').'</th>
				<th style="width:10px; background:#EFEFEF;"><input type="checkbox" onchange="lz_apply_status(this, \'.csum_add_ig\');" /></th>
			</tr>';
			
			if(is_array($files) && count($files) > 0){
				
				foreach($files as $k => $v){
					
					echo '
				<tr>
					<td>'.$k.'</td>
					<td>'.$v['cur_md5'].'</td>
					<td>'.$v['md5'].'</td>
					<td><input type="checkbox" name="checksum_add_ignore[]" class="csum_add_ig" value="'.$k.'" /></td>
				</tr>';
					
				}
				
			}else{
				
				echo '
				<tr>
					<td colspan="4" align="center">'.__('This is great ! No file with any wrong checksum has been found.','loginizer').'</td>
				</tr>';
				
			}
			
			?>
		</table><br />
		<center><input name="save_lz_csum_ig" class="button button-primary action" value="<?php echo __('Add Selected to Ignore List', 'loginizer'); ?>" type="submit" /></center>
		</form>
		</div>
		
	</div>
	<br />
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Ignore List', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data" loginizer-premium-only="1">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="wp-list-table fixed striped users" border="0" width="100%" cellpadding="10" align="center">
			<?php

			$ignores = $lz_env['ignores'];
			
			echo '
			<tr>
				<th style="background:#EFEFEF;">'.__('Relative Path', 'loginizer').'</th>
				<th style="width:240px; background:#EFEFEF;">'.__('Found', 'loginizer').'</th>
				<th style="width:240px; background:#EFEFEF;">'.__('Should be', 'loginizer').'</th>
				<th style="width:10px; background:#EFEFEF;"><input type="checkbox" onchange="lz_apply_status(this, \'.csum_del_ig\');" /></th>
			</tr>';
	
			// Load any mismatched files
			$files = $ignores;
			
			if(is_array($files) && count($files) > 0){
				
				foreach($files as $k => $v){
					
					echo '
				<tr>
					<td>'.$k.'</td>
					<td>'.$v['cur_md5'].'</td>
					<td>'.$v['md5'].'</td>
					<td><input type="checkbox" name="checksum_del_ignore[]" class="csum_del_ig" value="'.$k.'" /></td>
				</tr>';
					
				}
				
			}else{
				
				echo '
				<tr>
					<td colspan="4" align="center">'.__('No files have been added to the ignore list','loginizer').'</td>
				</tr>';
				
			}
			
			?>
		</table><br />
		<center><input name="save_lz_csum_ig" class="button button-primary action" value="<?php echo __('Remove Selected from Ignore List', 'loginizer'); ?>" type="submit" /></center>
		</form>
		</div>
		
	</div>
	<br />

	<?php
	loginizer_page_footer();
	
}