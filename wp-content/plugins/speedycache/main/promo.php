<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Promo{
	static function update_notice(){
		if(defined('SOFTACULOUS_PLUGIN_UPDATE_NOTICE')){
			return;
		}

		$to_update_plugins = apply_filters('softaculous_plugin_update_notice', []);

		if(empty($to_update_plugins)){
			return;
		}

		/* translators: %1$s is replaced with a "string" of name of plugins, and %2$s is replaced with "string" which can be "is" or "are" based on the count of the plugin */
		$msg = sprintf(__('New versions of %1$s %2$s available. Updating ensures better performance, security, and access to the latest features.', 'speedycache'), '<b>'.esc_html(implode(', ', $to_update_plugins)).'</b>', (count($to_update_plugins) > 1 ? 'are' : 'is')) . ' <a class="button button-primary" href='.esc_url(admin_url('plugins.php?plugin_status=upgrade')).'>Update Now</a>';

		define('SOFTACULOUS_PLUGIN_UPDATE_NOTICE', true); // To make sure other plugins don't return a Notice
		echo '<div class="notice notice-info is-dismissible" id="speedycache-plugin-update-notice">
			<p>'.$msg. '</p>
		</div>';

		wp_register_script('speedycache-update-notice', '', ['jquery'], '', true);
		wp_enqueue_script('speedycache-update-notice');
		wp_add_inline_script('speedycache-update-notice', 'jQuery("#speedycache-plugin-update-notice").on("click", function(e){
			let target = jQuery(e.target);

			if(!target.hasClass("notice-dismiss")){
				return;
			}

			var data;
			
			// Hide it
			jQuery("#speedycache-plugin-update-notice").hide();
			
			// Save this preference
			jQuery.post("'.admin_url('admin-ajax.php?action=speedycache_close_update_notice').'&security='.wp_create_nonce('speedycache_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});');
	}
	
}