<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}
	
// The Loginizer Admin Options Page
function loginizer_page_dashboard(){
	
	global $loginizer, $lz_error, $lz_env;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}
	
	// Dismiss the announcement
	if(isset($_GET['dismiss_announcement'])){
		update_option('loginizer_no_announcement', 1);
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginizer-options');
	}
	
	do_action('loginizer_pre_page_dashboard');
	
	// Is there a IP Method ?
	if(isset($_POST['save_lz_ip_method'])){
		
		$ip_method = (int) lz_optpost('lz_ip_method');
		$custom_ip_method = lz_optpost('lz_custom_ip_method');
		
		if($ip_method >= 0 && $ip_method <= 3){
			update_option('loginizer_ip_method', $ip_method);
		}
		
		// Custom Method name ?
		if($ip_method == 3){
			update_option('loginizer_custom_ip_method', $custom_ip_method);
		}
		
	}
	
	loginizer_page_dashboard_T();
	
}

// The Loginizer Admin Options Page - THEME
function loginizer_page_dashboard_T(){
	
	global $loginizer, $lz_error, $lz_env;

	loginizer_page_header('Dashboard');
?>
<style>
.lz-welcome-panel{
	border: 1px solid #c3c4c7;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
	background: #fff;
	padding:10px;
}

.lz-welcome-panel-content{
	display:inline;
	vertical-align:middle;
}

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
		
	<?php 
	$lz_ip = lz_getip();
	
	if($lz_ip != '127.0.0.1' && @$_SERVER['SERVER_ADDR'] == $lz_ip){
		echo '<div class="update-message notice error inline notice-error notice-alt"><p style="color:red"> &nbsp; '.__('Your Server IP Address seems to match the Client IP detected by Loginizer. You might want to change the IP detection method to HTTP_X_FORWARDED_FOR under System Information section.', 'loginizer').'</p></div><br>';
	 }
	
	loginizer_newsletter_subscribe();
	
	if(!empty($loginizer['backuply_promo']) && $loginizer['backuply_promo'] > 0 && $loginizer['backuply_promo'] < (time() - (7*24*3600))){
		
		loginizer_backuply_promo();
		
	}
	
	
	echo '
	<div class="lz-welcome-panel">
		<div class="lz-welcome-panel-content">'. __('Thank you for choosing Loginizer! Many more features coming soon... &nbsp; Review Loginizer at WordPress &nbsp; &nbsp;', 'loginizer').'<a href="https://wordpress.org/support/view/plugin-reviews/loginizer" class="button button-primary" target="_blank">'. __('Add Review', 'loginizer'). '</a></div>
	</div><br />';

	// Saved ?
	if(!empty($GLOBALS['lz_saved'])){
		echo '<div id="message" class="updated"><p>'. __('The settings were saved successfully', 'loginizer'). '</p></div><br />';
	}
	
	// Any errors ?
	if(!empty($lz_error)){
		lz_report_error($lz_error);echo '<br />';
	}
	
	?>	
	<div style="display:flex; justify-content:space-between;" >
	<div class="postbox" style="width:34%">
		
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Getting Started', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="form-table">
			<tr>
				<td scope="row" valign="top" colspan="2" style="line-height:1.9">
					<i><?php echo __('Welcome to Loginizer Security. By default the <b>Brute Force Protection</b> is immediately enabled. You should start by going over the default settings and tweaking them as per your needs.', 'loginizer'); ?></i>
					<?php 
					if(defined('LOGINIZER_PREMIUM')){
						echo '<br><i>'.__('In the Premium version of Loginizer you have many more features. We recommend you enable features like <b>reCAPTCHA, Two Factor Auth or Email based PasswordLess</b> login. These features will improve your websites security','loginizer').'</i>';
					}else{
						echo '<br><i><a href="'.LOGINIZER_PRICING_URL.'" target="_blank" style="text-decoration:none;color:red;">'.__('Upgrade to Pro</a> for more features like <b>reCAPTCHA, Two Factor Auth, Rename wp-admin and wp-login.php pages, Email based PasswordLess</b> login and more. These features will improve your website\'s security.','loginizer').'</i>';
					}
					?>
				</td>
			</tr>
		</table>
		</form>
		
		</div>
	</div>
	
	<?php 

	$login_attempt_stats = get_option('loginizer_login_attempt_stats', []);
	$success_logins = 1;
	$failed_logins = 0;
	$stats_dataset = [];

	foreach($login_attempt_stats as $attempt_time => $count){
		
		if($attempt_time < strtotime('-30 days')){
			unset($login_attempt_stats[$attempt_time]);
			update_option('loginizer_login_attempt_stats', $login_attempt_stats, false);
			continue;
		}

		$day_month = date('M j', $attempt_time);
		if(empty($stats_dataset[$day_month])){
			$stats_dataset[$day_month] = 0;
		}
		
		if(!empty($login_attempt_stats[$attempt_time][0])){
			$stats_dataset[$day_month] += $login_attempt_stats[$attempt_time][0];
		}
		
		if($attempt_time > strtotime('-24 hours')){

			if(!empty($login_attempt_stats[$attempt_time][0])){
				$failed_logins += $login_attempt_stats[$attempt_time][0];
			}
			
			if(!empty($login_attempt_stats[$attempt_time][1])){
				$success_logins += $login_attempt_stats[$attempt_time][1];
			}

			continue;
		}
	}
	
	$failed_login_color = '#f9fa8e';

	if($failed_logins < 40){
		$failed_login_color = '#f9fa8e';
		$failed_notice = __('Your Website is safe', 'loginizer');
		
	} else if($failed_logins < 70){
		$failed_login_color = '#ffcd56';
		$failed_notice = __('Risk from Brute-force attacks is low, attacks are under control', 'loginizer');
	} else if($failed_logins < 150){
		$failed_login_color = '#f67019';
		$failed_notice = __('Brute-force attacks on your websites are on rise', 'loginizer');
	} else {
		$failed_login_color = '#fc1e4d';
		$failed_notice = __('Your website is under heavy brute-force attacks.<br/> <a href="https://loginizer.com/pricing?utm_source=stats_block" target="_blank">Upgrade to a premium version</a> for added protection if this trend persists. Act fast to secure your site.', 'loginizer');
	}
	
	if(defined('LOGINIZER_PREMIUM')){
		$failed_login_color = '#f53794';
		$failed_notice = __('Your website is being protected by Loginizer Security.', 'loginizer');
	}

	?>

	<div class="postbox" style="width:65%;">
		
		<div class="postbox-header">
		<h2 class="hndle">
			<span><?php echo __('Login Attempts', 'loginizer'); ?></span>
		</h2>
		</div>
		<div class="inside" style="display:flex;">
			<div style="margin-right:50px;">
				<div style="position:relative; width: 250px; height:auto; margin: 0 auto;">
					<canvas id="lz-attempts-chart"></canvas>
					<h3 style="position:absolute; bottom:0%; width:100%; text-align:center;"><?php _e('Total Attempts:', 'loginizer'); ?> <?php echo esc_html($failed_logins + $success_logins); ?></h3>
				</div>
				<div><p style="text-align:center;"><?php echo wp_kses_post($failed_notice); ?></p></div>
				<div style="color:#898989; text-align:right;"><?php _e('Data For Last 24 hours', 'loginizer'); ?></div>
			</div>
			<div style="margin:auto; height:100%; min-height:300px; width:80%;">
				<canvas id="lz-attemt-chart-thirty"></canvas>
			</div>
		</div>
	</div>
	</div>
	
	<div class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('System Information', 'loginizer'); ?></span>
		</h2>
		</div>
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="wp-list-table fixed striped users" cellspacing="1" border="0" width="95%" cellpadding="10" align="center">
		<?php
			echo '
			<tr>
				<th align="left" width="25%">'.__('Loginizer Version', 'loginizer').'</th>
				<td>'.LOGINIZER_VERSION.(defined('LOGINIZER_PREMIUM') ? ' (<font color="green">'.__('Security PRO Version','loginizer').'</font>)' : '').'</td>
			</tr>';
			
			do_action('loginizer_system_information');
			
			echo '<tr>
				<th align="left">'.__('URL', 'loginizer').'</th>
				<td>'.get_site_url().'</td>
			</tr>
			<tr>				
				<th align="left">'.__('Path', 'loginizer').'</th>
				<td>'.ABSPATH.'</td>
			</tr>
			<tr>				
				<th align="left">'.__('Server\'s IP Address', 'loginizer').'</th>
				<td>'.@$_SERVER['SERVER_ADDR'].'</td>
			</tr>
			<tr>				
				<th align="left">'.__('Your IP Address', 'loginizer').'</th>
				<td>'.lz_getip().'
					<div style="float:right">
						Method : 
						<select name="lz_ip_method" id="lz_ip_method" style="font-size:11px; width:150px" onchange="lz_ip_method_handle()">
							<option value="0" '.lz_POSTselect('lz_ip_method', 0, (@$loginizer['ip_method'] == 0)).'>REMOTE_ADDR'.(!empty($_SERVER['REMOTE_ADDR']) ? '(' . esc_html($_SERVER['REMOTE_ADDR']) .')' : '').'</option>
							<option value="1" '.lz_POSTselect('lz_ip_method', 1, (@$loginizer['ip_method'] == 1)).'>HTTP_X_FORWARDED_FOR'.(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '(' . esc_html($_SERVER['HTTP_X_FORWARDED_FOR']) .')' : '').'</option>
							<option value="2" '.lz_POSTselect('lz_ip_method', 2, (@$loginizer['ip_method'] == 2)).'>HTTP_CLIENT_IP'.(!empty($_SERVER['HTTP_CLIENT_IP']) ? '(' . esc_html($_SERVER['HTTP_CLIENT_IP']) .')' : '').'</option>
							<option value="3" '.lz_POSTselect('lz_ip_method', 3, (@$loginizer['ip_method'] == 3)).'>CUSTOM</option>
						</select>
						<input name="lz_custom_ip_method" id="lz_custom_ip_method" type="text" value="'.lz_optpost('lz_custom_ip_method',(empty($loginizer['custom_ip_method']) ? '' : $loginizer['custom_ip_method'])).'" style="font-size:11px; width:100px; display:none" />
						<input name="save_lz_ip_method" class="button button-primary" value="Save" type="submit" />
					</div>
				</td>
			</tr>
			<tr>				
				<th align="left">'.__('wp-config.php is writable', 'loginizer').'</th>
				<td>'.(is_writable(ABSPATH.'/wp-config.php') ? '<span style="color:red">Yes</span>' : '<span style="color:green">No</span>').'</td>
			</tr>';
			
			if(file_exists(ABSPATH.'/.htaccess')){
				echo '
			<tr>				
				<th align="left">'.__('.htaccess is writable', 'loginizer').'</th>
				<td>'.(is_writable(ABSPATH.'/.htaccess') ? '<span style="color:red">Yes</span>' : '<span style="color:green">No</span>').'</td>
			</tr>';
			
			}
			
			// Setting up the dataset for the 30 day chart
			$line_dataset[] = array(
				'label' => __( 'Failed', 'loginizer'),
				'data' => $stats_dataset,
				'backgroundColor' => 'rgb(54, 162, 235)',
				'borderColor' => 'rgb(54, 162, 235)',
			);

			// Enqueues CharJS script and inline the char js
			wp_enqueue_script('chartjs', LOGINIZER_URL.'/assets/js/chart.js', array('jquery'), '3.0.0');
			wp_add_inline_script('chartjs', 'function lz_attempts_chart(){
	const ctx = document.getElementById("lz-attempts-chart");

	new Chart(ctx, {
		type: "doughnut",
		data: {
			labels: ["Failed", "Success"],
			datasets: [{
				label: "Count",
				data: ['.esc_html($failed_logins).', '.esc_html($success_logins).'],
				backgroundColor: [
					"'.esc_html($failed_login_color).'",
					"rgb(54, 162, 235)",
				],
				hoverOffset: 4,
				borderWidth: [0]
			}],
		},
		options : {
			circumference : 180,
			rotation:-90,
			responsive: true,
		}
	});
	
	const thirty_days = document.getElementById("lz-attemt-chart-thirty");
	
	new Chart(thirty_days, {
		type: "line",
		data: {
			datasets: '.json_encode($line_dataset).'
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			hover: {
				mode: "nearest",
				intersect: true
			},
			scales: {
				x: {
					display: true,
					scaleLabel: {
						display: false
					}
					
				},
				y: {
					display: true,
					scaleLabel: {
						display: false
					},
					beginAtZero: true,
					ticks: {
						callback: function(label, index, labels) {
							if (Math.floor(label) === label) {
								return label;
							}
						},
					}
				}
			}
		}
	});
}

lz_attempts_chart();');

		?>
		</table>
		</form>
		
		</div>
	</div>

<script type="text/javascript">

function lz_ip_method_handle(){
	var ele = jQuery('#lz_ip_method');
	if(ele.val() == 3){
		jQuery('#lz_custom_ip_method').show();
	}else{
		jQuery('#lz_custom_ip_method').hide();
	}
};

lz_ip_method_handle();

</script>
	
	<div id="" class="postbox">
	
		<div class="postbox-header">
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('File Permissions', 'loginizer'); ?></span>
		</h2>
		</div>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginizer-options'); ?>
		<table class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
			<?php
			
			echo '
			<tr>
				<th style="background:#EFEFEF;">'.__('Relative Path', 'loginizer').'</th>
				<th style="width:10%; background:#EFEFEF;">'.__('Suggested', 'loginizer').'</th>
				<th style="width:10%; background:#EFEFEF;">'.__('Actual', 'loginizer').'</th>
			</tr>';
			
			if(version_compare(phpversion(), '7.0') < 0){
				$wp_content = basename(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
			}else {
				$wp_content = basename(dirname(__FILE__, 5));
			}
			
			$files_to_check = array('/' => array('0755', '0750'),
								'/wp-admin' => array('0755'),
								'/wp-includes' => array('0755'),
								'/wp-config.php' => array('0444'),
								'/'.$wp_content => array('0755'),
								'/'.$wp_content.'/themes' => array('0755'),
								'/'.$wp_content.'/plugins' => array('0755'));
			
			if(file_exists(ABSPATH.'/.htaccess')){
				$files_to_check['.htaccess'] = array('0444');
			}
			
			$root = ABSPATH;
			
			foreach($files_to_check as $k => $v){
				
				$path = $root.'/'.$k;
				$stat = @stat($path);
				$suggested = $v;
				$actual = substr(sprintf('%o', $stat['mode']), -4);
				
				echo '
			<tr>
				<td>'.$k.'</td>
				<td>'.current($suggested).'</td>
				<td><span '.(!in_array($actual, $suggested) ? 'style="color: red;"' : '').'>'.$actual.'</span></td>
			</tr>';
				
			}
			
			?>
		</table>
		</form>
		
		</div>
	</div>

<?php
	
	loginizer_page_footer();

}