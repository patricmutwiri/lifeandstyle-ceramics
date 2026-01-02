<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

use \SpeedyCache\Util;

class Admin{
	
	static $conflicting_plugins = [];
	
	static function hooks(){
		
		//NOTE:: Most actions will go here
		if(current_user_can('manage_options')){
			add_action('admin_notices', '\SpeedyCache\Admin::combitibility_notice');
			add_action('admin_menu', '\SpeedyCache\Admin::list_menu');
			do_action('speedycache_pro_admin_hooks'); // adds hooks for the pro-version.
			
			if(!defined('SITEPAD')){
				self::notices();
			}
		}
		
		if(self::user_can_delete_cache()){
			add_action('admin_post_speedycache_delete_cache', '\SpeedyCache\Admin::delete_cache');
			add_action('admin_post_speedycache_delete_single', '\SpeedyCache\Admin::delete_single');
			add_action('admin_post_speedycache_delete_single_url', '\SpeedyCache\Admin::delete_single_url');
			
			$post_types = ['post', 'page', 'category', 'tag'];

			foreach($post_types as $post_type){
				add_filter($post_type.'_row_actions', '\SpeedyCache\Admin::delete_link', 10, 2);
			}
		}
	}
	
	static function list_menu(){
		global $speedycache;

		$capability = 'activate_plugins';

		//$speedycache->settings['disabled_tabs'] = apply_filters('speedycache_disabled_tabs', []);

		$url = SPEEDYCACHE_URL.'/assets/images/'. (defined('SITEPAD') ? 'grey-icon.svg' : 'icon.svg');
		
		$hooknames[] = add_menu_page('SpeedyCache Settings', 'SpeedyCache', $capability, 'speedycache', '\SpeedyCache\Settings::base', $url);
	
		foreach($hooknames as $hookname){
			add_action('load-'.$hookname, '\SpeedyCache\Admin::load_assets');
		}
	}
	
	static function load_assets(){
		add_action('admin_enqueue_scripts', '\SpeedyCache\Admin::enqueue_scripts');
	}

	// Enqueues Admin CSS on load of the page
	static function enqueue_scripts(){
		wp_enqueue_style('speedycache-admin', SPEEDYCACHE_URL.'/assets/css/admin.css', [], SPEEDYCACHE_VERSION);
		wp_enqueue_script('speedycache-admin', SPEEDYCACHE_URL . '/assets/js/admin.js', [], SPEEDYCACHE_VERSION);
		
		wp_localize_script('speedycache-admin', 'speedycache_ajax', [
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('speedycache_ajax_nonce'),
			'premium' => defined('SPEEDYCACHE_PRO'),
		]);
	}
	
	// Post action to delete cache through Manage cache options.
	static function delete_cache(){
		check_admin_referer('speedycache_post_nonce');
		
		if(!self::user_can_delete_cache()){
			wp_die(esc_html__('You do not have a required privilege', 'speedycache'));
		}

		$delete['minified'] = isset($_REQUEST['minified']);
		$delete['font'] = isset($_REQUEST['font']);
		$delete['gravatars'] = isset($_REQUEST['gravatars']);
		$delete['domain'] = isset($_REQUEST['domain']);
		$delete['preload'] = isset($_REQUEST['preload_cache']);

		\SpeedyCache\Delete::run($delete);
		$redirect_to = esc_url_raw(wp_unslash($_REQUEST['_wp_http_referer']));

		wp_safe_redirect($redirect_to);
		die();
	}
	
	static function delete_single(){
		check_admin_referer('speedycache_post_nonce', 'security');
		
		if(!self::user_can_delete_cache()){
			wp_die(esc_html__('You do not have a required privilege', 'speedycache'));
		}

		$post_id = Util::sanitize_get('post_id');
		\SpeedyCache\Delete::cache($post_id);

		$redirect_to = esc_url_raw(wp_unslash($_REQUEST['referer']));

		wp_safe_redirect($redirect_to);
		die();
	}
	
	static function delete_single_url(){
		check_admin_referer('speedycache_post_nonce', 'security');
		
		if(!self::user_can_delete_cache()){
			wp_die(esc_html__('You do not have a required privilege', 'speedycache'));
		}

		$url = esc_url_raw(wp_unslash($_REQUEST['referer']));
		\SpeedyCache\Delete::url($url);

		wp_safe_redirect($url);
		die();
	}
	
	static function combitibility_notice(){
		
		$incompatible_plugins = [
			'wp-rocket/wp-rocket.php' => 'WP Rocket',
			'wp-super-cache/wp-cache.php' => 'WP Super Cache',
			'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
			'swift-performance-lite/performance.php' => 'Swift Performance Lite',
			'swift-performance/performance.php' => 'Swift Performance',
			'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
			'wp-optimize/wp-optimize.php' => 'WP Optimize',
			'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
			'flyingpress/flyingpress.php' => 'FlyingPress',
		];

		$conflicting_plugins = [];
		foreach($incompatible_plugins as $plugin_path => $plugin_name){
			if(is_plugin_active($plugin_path)){
				$conflicting_plugins[] = $plugin_name;
			}
		}
		
		if(empty($conflicting_plugins)){
			return;
		}
		
		echo '<div class="notice notice-warning is-dismissible">
		<h3>Conflicting Plugins</h3>
        <p>'.esc_html__('You have activated plugins that conflict with SpeedyCache. We recommend deactivating these plugins to ensure SpeedyCache functions properly.', 'speedycache').'</p>
		<ol>';

		foreach($conflicting_plugins as $plugin){
			echo '<li>'.esc_html($plugin).'</li>';
		}

		echo '</ol></div>';
	}
	
	static function delete_link($actions, $post){
		if(!self::user_can_delete_cache()){
			return;
		}

		$request_url = remove_query_arg( '_wp_http_referer' );

		$actions['speedycache_delete'] = '<a href="'.admin_url('admin-post.php?action=speedycache_delete_single&post_id='.$post->ID.'&security='.wp_create_nonce('speedycache_post_nonce')).'&referer='.esc_url($request_url).'">'.esc_html__('Delete Cache', 'speedycache').'</a>';
		
		return $actions;
	}
	
	static function admin_bar($admin_bar){
		global $post;

		if(!self::user_can_delete_cache()){
		   return;
		}

		$request_url = remove_query_arg('_wp_http_referer');

		$admin_bar->add_menu([
			'id'    => 'speedycache-adminbar',
			'title' => __('SpeedyCache', 'speedycache'),
		]);

		$admin_bar->add_menu(array(
			'id'    => 'speedycache-adminbar-delete-all',
			'title' => __('Delete all Cache', 'speedycache'),
			'parent' => 'speedycache-adminbar',
			'href' => wp_nonce_url(admin_url('admin-post.php?action=speedycache_delete_cache&_wp_http_referer='.esc_url($request_url)),  'speedycache_post_nonce'),
			'meta' => ['class' => 'speedycache-adminbar-options']
		));
		
		$admin_bar->add_menu(array(
			'id'    => 'speedycache-adminbar-delete-minified',
			'title' => __('Delete Cache and Minified', 'speedycache'),
			'parent' => 'speedycache-adminbar',
			'href' => wp_nonce_url(admin_url('admin-post.php?action=speedycache_delete_cache&minified=true&_wp_http_referer='.esc_url($request_url)),  'speedycache_post_nonce'),
			'meta' => ['class' => 'speedycache-adminbar-options']
		));

		if(!is_admin()){
			$admin_bar->add_menu(array(
				'id'    => 'speedycache-adminbar-delete',
				'parent' => 'speedycache-adminbar',
				'title' => __('Clear this page\'s cache', 'speedycache'),
				'href' => wp_nonce_url(admin_url('admin-post.php?action=speedycache_delete_single_url&referer='.esc_url($request_url)), 'speedycache_post_nonce', 'security'),
				'meta' => ['class' => 'speedycache-adminbar-options']
			));
		}
	}
	
	// Checks if the current users role is allowed
	static function user_can_delete_cache(){
		$allowed_roles = get_option('speedycache_deletion_roles', []);
		array_push($allowed_roles, 'administrator'); // admin is default

		$user = wp_get_current_user();
		if(!array_intersect($allowed_roles, $user->roles)){
		   return false;
		}

		return true;
	}

	static function update_notice_filter($plugins = []){
		$plugins['speedycache/speedycache.php'] = 'SpeedyCache';
		return $plugins;
	}
	
	static function notices(){
		// === Plugin Update Notice === //
		$plugin_update_notice = get_option('softaculous_plugin_update_notice', []);
		$available_update_list = get_site_transient('update_plugins'); 
		$plugin_path_slug = 'speedycache/speedycache.php';

		if(
			!empty($available_update_list) &&
			is_object($available_update_list) && 
			!empty($available_update_list->response) &&
			!empty($available_update_list->response[$plugin_path_slug]) && 
			(empty($plugin_update_notice) || empty($plugin_update_notice[$plugin_path_slug]) || (!empty($plugin_update_notice[$plugin_path_slug]) &&
			version_compare($plugin_update_notice[$plugin_path_slug], $available_update_list->response[$plugin_path_slug]->new_version, '<')))
		){
			add_action('admin_notices', '\SpeedyCache\Promo::update_notice');
			add_filter('softaculous_plugin_update_notice', '\SpeedyCache\Admin::update_notice_filter');
		}
		// === Plugin Update Notice === //
	}
}
