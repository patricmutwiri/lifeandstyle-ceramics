<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

function loginizer_sso(){
	global $loginizer, $error;
	
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}

	if(empty($_POST['lz_generate_sso']) && empty($_POST['lz_delete_sso'])){
		loginizer_sso_t();
		return;
	}
	
	if(!defined('LOGINIZER_PREMIUM')){
		$error[] = __('SSO is a Pro feature so it can not be used with the free version.', 'loginizer');
		loginizer_sso_t();
		return;
	}
	
	// Checking for form nonce
	if(!wp_verify_nonce($_POST['security'], 'loginizer_nonce')){
		$error[] = __('Security Check Failed!', 'loginizer');
		loginizer_sso_t();
		return;
	}
	
	if(!empty($_POST['lz_delete_sso'])){
		loginizer_delete_sso();
		return;
	}

	if(empty($_POST['sso_user'])){
		$error[] = __('Please select a user for whom you want to generate the link', 'loginizer');
		loginizer_sso_t();
		return;
	}

	$sso_ttl = 600;
	if(!empty($_POST['sso_ttl']) && is_numeric($_POST['sso_ttl'])){
		$sso_ttl = (int) sanitize_text_field($_POST['sso_ttl']);
	}
	
	$sso_attempts = 1;
	if(!empty($_POST['sso_attempts']) && is_numeric($_POST['sso_attempts'])){
		$sso_attempts = (int) sanitize_text_field($_POST['sso_attempts']);
		
		// The attempts need to be 15 or less
		if($sso_attempts > 15 || $sso_attempts < 1){
			$sso_attempts = 1;
		}
	}

	$username = sanitize_text_field($_POST['sso_user']);
	$user = get_user_by('login', $username);
	
	if(empty($user) || empty($user->ID)){
		$error[] = __('The given user was not found !', 'loginizer');
		loginizer_sso_t();
		return;
	}

	$loginizer['sso_link'] = loginizer_create_sso($user->ID, $sso_ttl, $sso_attempts);

	loginizer_sso_t();
}

function loginizer_delete_sso(){
	global $error;
	
	if(empty($_POST['lz_checksso_link'])){
		$error[] = __('Please select SSO Links to delete!', 'loginizer');
		loginizer_sso_t();
		return;
	}
	
	$sso_ids = map_deep($_POST['lz_checksso_link'], 'sanitize_text_field');
	
	if(empty($_POST['lz_checksso_link'])){
		$error[] = __('SSO IDs were malformed', 'loginizer');
		loginizer_sso_t();
		return;
	}
	
	$sso_links = get_option('loginizer_sso_links', []);
	$update_sso_links = false;

	foreach($sso_ids as $sso_id){
		delete_user_meta($sso_id, 'loginizer_sso_' . $sso_id);
		delete_user_meta($sso_id, 'loginizer_sso_' . $sso_id . '_expires');
		delete_user_meta($sso_id, 'loginizer_sso_' . $sso_id . '_attempts');
		
		if(!empty($sso_links)){
			unset($sso_links[$sso_id]);
			$update_sso_links = true;
		}
	}
	
	if(!empty($update_sso_links)){
		update_option('loginizer_sso_links', $sso_links);
	}
	
	loginizer_sso_t();
}


function loginizer_sso_t(){
	global $loginizer, $error;
	
	loginizer_page_header('SSO');
	loginizer_feature_available('Single Sign-On');
	
	lz_report_error($error);
?>
<style>
.loginizer-sso-link{
padding:1rem 1rem;
color:#052c65;
background-color:#cfe2ff;
border:1px solid #9ec5fe;
border-radius:0.375rem;
}

.loginizer-sso-copy{
margin-right:10px;
cursor:pointer;
font-weight:500;
}
</style>

<script>
jQuery(document).ready(function(){
	
	jQuery('.loginizer-sso-copy').on('click', function(){
		navigator.clipboard.writeText(jQuery(this).parent().text());
		jQuery(this).removeClass('dashicons');
		jQuery(this).removeClass('dashicons-admin-page');
		jQuery(this).text('Copied');
		
		setTimeout(() =>{
			jQuery(this).text('');
			jQuery(this).addClass('dashicons');
			jQuery(this).addClass('dashicons-admin-page');
		}, 1000);
		
	});
	
	jQuery('#lz_check_all_sso_link').on('change', function(){
		if(jQuery(this).is(':checked')){
			jQuery('input[name="lz_checksso_link[]"]').prop('checked', true);
			return;
		}
		
		jQuery('input[name="lz_checksso_link[]"]').prop('checked', false);
	});

});
</script>


<div id="" class="postbox" loginizer-premium-only="1">

	<div class="postbox-header">
	<h2 class="hndle ui-sortable-handle">
		<span><?php esc_html_e('Generate SSO', 'loginizer');?></span>
	</h2>
	</div>
	
	<div class="inside">
		<?php
		$user_list = get_users();
		
		$sso_links = get_option('loginizer_sso_links', []);
		?>
		<form action="" method="post" enctype="multipart/form-data">
		<?php if(!empty($loginizer['sso_link'])){
			echo '<div class="loginizer-sso-link"><span class="dashicons dashicons-admin-page loginizer-sso-copy"></span>'.esc_url($loginizer['sso_link']).'</div>';
		} 
		
		echo wp_nonce_field('loginizer_nonce', 'security');
		?>

		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2">
					<i><?php esc_html_e('You can generate SSO link or send it to an email, to give them access to your site\'s admin panel', 'loginizer'); ?></i>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:300px !important">
					<label for="lz-sso-email"><?php esc_html_e('Email', 'loginizer'); ?></label><br>
					<span class="exp"><?php esc_html_e('Email of the person you want to send the SSO to', 'loginizer'); ?></span>
				</td>
				<td>
					<input id="lz-sso-email" type="email" name="sso_email" placeholder="name@email.com"/>
					<p class="description"><?php esc_html_e('You can leave it empty if you just want to create a SSO link', 'loginizer'); ?></p>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top" style="width:300px !important">
					<label for="lz-sso-user"><?php esc_html_e('User', 'loginizer'); ?></label><br>
					<span class="exp"><?php esc_html_e('User for who\'s account you want to generate the SSO', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="text" id="lz-sso-user" name="sso_user" placeholder="Username">
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label for="lz-sso-ttl"><?php esc_html_e('Time to Live', 'loginizer'); ?></label><br>
					<span class="exp"><?php esc_html_e('Select the duration for which the SSO stays alive', 'loginizer'); ?></span>
				</td>
				<td>
					<select id="lz-sso-ttl" name="sso_ttl" style="width:175px;">
						<option value="300">5 minutes</option>
						<option value="600">10 minutes</option>
						<option value="1800">30 minutes</option>
						<option value="3600">1 hour</option>
						<option value="21600">6 hours</option>
						<option value="43200">12 hours</option>
						<option value="86400">24 hours</option>
						<option value="172800">2 Days</option>
					</select>
				</td>
			</tr>
			<tr>
				<td scope="row" valign="top">
					<label for="lz-sso-attempts"><?php esc_html_e('Login Attempts', 'loginizer'); ?></label><br>
					<span class="exp"><?php esc_html_e('Number of times you want your user to be able to login through same link by default it\'s 1 time and maximum is 15 times', 'loginizer'); ?></span>
				</td>
				<td>
					<input type="number" id="lz-sso-attempts" name="sso_attempts" min="1" max="15" placeholder="Attempt Count" value="1" style="width:175px;">
				</td>
			</tr>
			<tr>
				<td>
				</td>
				<td>
					<input type="submit" class="button button-primary" name="lz_generate_sso" value="Generate SSO"/>
				</td>
			</tr>
			
		</table>
		</form>
		<br/>
		<form method="POST">
		<?php echo wp_nonce_field('loginizer_nonce', 'security'); ?>
		<table class="wp-list-table widefat fixed users" border="0">
			<tr>
				<th scope="row" valign="top" style="background:#EFEFEF;" width="20"><input type="checkbox" id="lz_check_all_sso_link" style="margin-left:-1px;"/></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php esc_html_e('User ID','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php esc_html_e('Username','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php esc_html_e('SSO Link','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php esc_html_e('Attempts Remaining','loginizer'); ?></th>
				<th scope="row" valign="top" style="background:#EFEFEF;"><?php esc_html_e('Expiring in','loginizer'); ?> <span class="dashicons dashicons-clock"></span></th>
			</tr>
			
			<?php
			
			if(empty($sso_links)){
				echo '<tr><td colspan="4">'.esc_html__('No SSO link has been created yet.', 'loginizer').'</td></tr>';
			} else {
				$expired_links = [];
				
				foreach($sso_links as $u_id => $sso_link){
					$user_info = get_userdata($u_id);
					$expire_utime = get_user_meta($u_id, 'loginizer_sso_'.$u_id.'_expires', true);
					$sso_attempts = get_user_meta($u_id, 'loginizer_sso_'.$u_id.'_attempts', true);

					if(empty($expire_utime)){
						$expired_links[] = $u_id;
						continue;
					}
					
					if($expire_utime < time()){
						$expired_links[] = $u_id;
						continue;
					}

					echo '<tr><td><input type="checkbox" name="lz_checksso_link[]" value="'.esc_attr($u_id).'" style="margin-left:-1px;"/></td>
					<td>'.esc_html($u_id).'</td>
					<td>'.esc_html($user_info->user_login).'</td>
					<td>'.esc_url($sso_link).'</td>
					<td>'.esc_html($sso_attempts).'</td>
					<td>'.esc_html(human_time_diff(time(), $expire_utime)).'</td>
					</tr>';
				}
				
				
				foreach($expired_links as $expired_link){
					delete_user_meta($expired_link, 'loginizer_sso_'. $expired_link);
					delete_user_meta($expired_link, 'loginizer_sso_'. $expired_link. '_expires');
					
					unset($sso_links[$expired_link]);
				}
				
				if(!empty($expired_links)){
					update_option('loginizer_sso_links', $sso_links);
				}
			}

			?>
			
		</table><br/>
		<input type="submit" name="lz_delete_sso" class="button button-primary action" value="<?php esc_html_e('Delete Selected Links', 'loginizer');?>">
		</form>
	</div>
</div>

<?php
loginizer_page_footer();

}