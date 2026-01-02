<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}


class Settings{

	static function base(){
		global $speedycache;

		if(!file_exists(SPEEDYCACHE_CACHE_DIR) || !is_writable(WP_CONTENT_DIR)){
			echo '<div class="notice notice-error">
				<p><strong>Error:</strong> '.esc_html__('SpeedyCache was not able to create the cache directory, there might be a permission issue with the '.(defined('SITEPAD') ? 'sitepad-data' : 'wp-content').' directory.', 'speedycache').'</p>
			</div>';
		}
		
		echo '<div id="speedycache-admin">
			<div id="speedycache-navigation">
				<div class="speedycache-logo">
					<img src="'.esc_url(SPEEDYCACHE_URL . '/assets/images/speedycache.png').'" alt="SpeedyCache Logo" width="200" height="35">
					<span>version '.esc_html(SPEEDYCACHE_VERSION).'</span>
				</div>
				<ul>
					<li><a href="#"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/dashboard.svg"/>Dashboard</a></li>
					<li><a href="#cache"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/cache.svg"/>Cache</a></li>
					<li><a href="#file"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/file.svg"/>File Optimization</a></li>
					<li><a href="#excludes"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/excludes.svg"/>Excludes</a></li>
					<li><a href="#preload"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/preload.svg"/>Preloading</a></li>
					<li><a href="#media"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/media.svg"/>Media</a></li>
					<li><a href="#cdn"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/cdn.svg"/>CDN</a></li>';
					
					if(!defined('SITEPAD')){
						echo '<li><a href="#object"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/object.svg"/>Object Cache</a></li>';
					}
					
					echo' <li><a href="#image"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/image.svg"/>Image Optimization</a></li>';
					
					if(!defined('SITEPAD')){
						echo '<li><a href="#bloat"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/broom.svg"/>Bloat</a></li>';
					}
					
					echo' <li><a href="#db"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/db.svg"/>Database</a></li>
					<li><a href="#settings"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/settings.svg"/>Settings</a></li>';

					if(!defined('SITEPAD') && defined('SPEEDYCACHE_PRO')){
						echo '<li><a href="#license"><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/license.svg"/> License</a></li>';
					}

				echo '<ul>
			</div>
			<div class="speedycache-tabs">
				<div class="speedycache-tab" id="speedycache-dashboard">';
					self::dashboard_tab();
				echo '</div>
			
				<div class="speedycache-tab" id="speedycache-cache">';
					self::cache_tab();
				echo '</div>
				<div class="speedycache-tab" id="speedycache-file">';
					self::file_tab();
				echo '</div>
				
				<div class="speedycache-tab" id="speedycache-preload">';
					self::preload_tab();
				echo '</div>
				<div class="speedycache-tab" id="speedycache-media">';
					self::media_tab();
				echo '</div>
				<div class="speedycache-tab" id="speedycache-excludes">';
					self::excludes_tab();
				echo '</div>
				
				<div class="speedycache-tab" id="speedycache-cdn">';
					self::cdn_tab();
				echo '</div>
				
				<div class="speedycache-tab" id="speedycache-image">';
					do_action('speedycache_image_optm_tmpl');
					if(!defined('SPEEDYCACHE_PRO') || (defined('SPEEDYCACHE_PRO_VERSION') && version_compare(SPEEDYCACHE_PRO_VERSION, '1.2.0', '<'))){
						self::pro_notice('Image Optimization');
					}
				echo '</div>
				
				<div class="speedycache-tab" id="speedycache-object">';
					do_action('speedycache_object_cache_tmpl');
					if(!defined('SPEEDYCACHE_PRO') || (defined('SPEEDYCACHE_PRO_VERSION') && version_compare(SPEEDYCACHE_PRO_VERSION, '1.2.0', '<'))){
						self::pro_notice('Object Cache');
					}
				echo '</div>

				<div class="speedycache-tab" id="speedycache-bloat">';
					do_action('speedycache_bloat_tmpl');
					if(!defined('SPEEDYCACHE_PRO') || (defined('SPEEDYCACHE_PRO_VERSION') && version_compare(SPEEDYCACHE_PRO_VERSION, '1.2.0', '<'))){
						self::pro_notice('Bloat Settings');
					}
				echo '</div>
				
				<div class="speedycache-tab" id="speedycache-db">';
					do_action('speedycache_db_tmpl');
					if(!defined('SPEEDYCACHE_PRO') || (defined('SPEEDYCACHE_PRO_VERSION') && version_compare(SPEEDYCACHE_PRO_VERSION, '1.2.0', '<'))){
						self::pro_notice('DB Optimization');
					}
				echo '</div>

				<div class="speedycache-tab" id="speedycache-settings">';
					self::settings_tab();
				echo '</div>';
				
				if(!defined('SITEPAD')) {
					do_action('speedycache_license_tmpl');
				}
	
			echo '</div>';
			
			if(!defined('SITEPAD')) {
				echo '<div class="speedycache-sidebar">
					<div class="speedycache-need-help">
					<p>Quick Access</p>
					<div class="speedycache-quick-links">
						<div class="speedycache-quick-access-item">
							<span class="dashicons dashicons-format-status"></span>
							<a href="https://softaculous.deskuss.com/open.php?topicId=19" target="_blank">Support</a>
						</div>
						<div class="speedycache-quick-access-item">
							<span class="dashicons dashicons-media-document"></span>
							<a href="https://speedycache.com/docs/" target="_blank">Documentation</a>
						</div>
						<div class="speedycache-quick-access-item">
							<span class="dashicons dashicons-feedback"></span>
							<a href="https://softaculous.deskuss.com/open.php?topicId=19" target="_blank">Feedback</a>
						</div>
						<div class="speedycache-quick-access-item">
							<span class="dashicons dashicons-star-filled"></span><a href="https://wordpress.org/support/plugin/speedycache/reviews/?rate=5#new-post" target="_blank">Rate Us</a>
						</div>
					</div>
				</div>';
				
				if(!defined('SPEEDYCACHE_PRO')){
					self::pro_upsell();
				}
				
				echo '</div>';
			}
		echo '</div>';
		
	}
	
	static function dashboard_tab(){
		global $speedycache;
		
		$speed_results = get_option('speedycache_pagespeed_test', []);
		
		$speed_score = 0;
		$speed_colors = ['#0c6', '#00cc663b', '#080'];
		if(!empty($speed_results)){
			$speed_colors = Util::pagespeed_color($speed_results['score']);
			$speed_score = $speed_results['score'];
		}
		
		
		$speedycache->object_memory = 'None';	
		if(!empty($speedycache->object['enable']) && class_exists('Redis') && class_exists('\SpeedyCache\ObjectCache')){
			try{
				$speedycache->object_memory = \SpeedyCache\ObjectCache::get_memory();
			} catch(\Exception $e) {
				$memory = 'None';
			}
		}
		
		$license_expires = '';
		if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->license['expires'])){
			$license_expires = $speedycache->license['expires'];
			$license_expires = substr($license_expires, 0, 4).'/'.substr($license_expires, 4, 2).'/'.substr($license_expires, 6);
		}
		
		
		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/dashboard.svg" height="32" width="32"/> Dashboard</h2>
			<div class="speedycache-admin-row">
				<div class="speedycache-perf-score speedycache-is-block">
					<div class="speedycache-perf-score-meter-heading">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24"><path d="M349.4 44.6c5.9-13.7 1.5-29.7-10.6-38.5s-28.6-8-39.9 1.8l-256 224c-10 8.8-13.6 22.9-8.9 35.3S50.7 288 64 288H175.5L98.6 467.4c-5.9 13.7-1.5 29.7 10.6 38.5s28.6 8 39.9-1.8l256-224c10-8.8 13.6-22.9 8.9-35.3s-16.6-20.7-30-20.7H272.5L349.4 44.6z"/></svg>
						<h4>Performance Score <span id="speedycache-analyze">[Analyze]<span class="speedycache-spinner"></span></span></h4>
					</div>
					<div class="speedycache-perf-score-meter">
						<div class="speedycache-perf-score-donut">
							<svg width="80%" height="80%" viewBox="0 0 40 40">
								<circle cx="20" cy="20" r="15.91549430918954" fill="'.esc_attr($speed_colors[1]).'"></circle>
								<circle cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke-width="2" stroke-linecap="round" stroke-dasharray="'.esc_html($speed_score).' '.esc_html(100 - $speed_score).'" stroke-dashoffset="25" style="stroke:'.esc_attr($speed_colors[0]).';"></circle>
								<g class="speedycache-donut-text speedycache-donut-text-1">
									<text y="56%" transform="translate(0, 2)">
										<tspan x="50%" text-anchor="middle" style="fill:'.esc_attr($speed_colors[2]).'">'.esc_html($speed_score).'</tspan> 
									</text>
								</g>
							</svg>
						</div>
						<div class="speedycache-perf-score-guide">
							<div>
								<span style="background-color:#f33;"></span>
								0-49
							</div>
							<div>
								<span style="background-color:#fa3;"></span>
								50-89
							</div>
							<div>
								<span style="background-color:#0c6;"></span>
								90-100
							</div>
						
						</div>
					</div>
				</div>
				<div class="speedycache-dashboard-info">';
				if(!defined('SITEPAD')){
					echo'
					<div class="speedycache-licence-brief speedycache-is-block">
						<h4>License</h4>
						<span>Version: '.esc_html(SPEEDYCACHE_VERSION).'</span>
						<span>Status: '.(!defined('SPEEDYCACHE_PRO') ? 'Free' : (!empty($speedycache->license) && defined('SPEEDYCACHE_PRO') ? 'Pro' : 'License not Linked')).'</span>
						<span>Expires on: '.(!defined('SPEEDYCACHE_PRO') ? 'Never' : (!empty($speedycache->license) && !empty($license_expires) ?  esc_html($license_expires) : '')).'</span>
					</div>';
				}
				echo'
				<div class="speedycache-is-block">
					<h4>Cache Info</h4>
					<span>File Cache: '.(!empty($speedycache->options['status']) ? esc_html__('Enabled', 'speedycache') : esc_html__('Disabled', 'speedycache')).'</span>';
					if(!defined('SITEPAD')){
						echo'<span>Object Cache: '.(!empty($speedycache->object['enable']) ? esc_html__('Enabled', 'speedycache') : esc_html__('Disabled', 'speedycache')).'</span>';
					}
					echo'
					<span>CDN: '.(!empty($speedycache->cdn) && !empty($speedycache->cdn['cdn_type']) ? esc_html(ucfirst($speedycache->cdn['cdn_type'])) : 'OFF').'</span>
				</div>
				</div>
			</div>';
			
			// TODO: Need to add this stats code in the Pro version.
			if(defined('SPEEDYCACHE_PRO')){
				do_action('speedycache_pro_stats_tmpl');
			}

			echo '<h3>Manage Cache</h3>
			<form method="POST" action="'.esc_url(admin_url('admin-post.php')).'">';
				wp_nonce_field('speedycache_post_nonce');
			
				echo '<input type="hidden" value="speedycache_delete_cache" name="action"/>
				<div class="speedycache-option-wrap">
					<label for="speedycache_delete_minified" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_delete_minified" name="minified"/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html__('Delete Minified', 'speedycache').'</span>
						<span class="speedycache-option-desc">'.esc_html__('Deletes Minfied/ Combined CSS/JS files', 'speedycache').'</span>
					</div>
				</div>';

				if(defined('SPEEDYCACHE_PRO')){
					echo '<div class="speedycache-option-wrap">
						<label for="speedycache_delete_fonts" class="speedycache-custom-checkbox">
							<input type="checkbox" id="speedycache_delete_fonts" name="fonts"/>
							<div class="speedycache-input-slider"></div>
						</label>
						<div class="speedycache-option-info">
							<span class="speedycache-option-name">'.esc_html__('Delete Fonts', 'speedycache').'</span>
							<span class="speedycache-option-desc">'.esc_html__('Deletes Local Google Fonts', 'speedycache').'</span>
						</div>
					</div>';
				}
				
				echo '<div class="speedycache-option-wrap">
					<label for="speedycache_delete_gravatars" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_delete_gravatars" name="gravatars"/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html__('Delete Gravatars', 'speedycache').'</span>
						<span class="speedycache-option-desc">'.esc_html__('Delete locally hosted Gravatars.', 'speedycache').'</span>
					</div>
				</div>
				<div class="speedycache-option-wrap">
					<label for="speedycache_preload_cache" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_preload_cache" name="preload_cache"/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html__('Preload Cache', 'speedycache').'</span>
						<span class="speedycache-option-desc">'.esc_html__('After cache gets deleted, it restarts auto cache generation.', 'speedycache').'</span>
					</div>
				</div>	
				<div class="speedycache-option-wrap">
					<div class="submit">
						<input type="submit" value="'.esc_html__('Clear all cache and the selections', 'speedycache').'" class="speedycache-button speedycache-btn-black"/>
					</div>
				</div>
			</form>';
			
			if(defined('SPEEDYCACHE_PRO')){
				do_action('speedycache_pro_logs_tmpl');
			}
	}
	
	static function cache_tab(){
		global $speedycache;

		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/cache.svg" height="32" width="32"/> Cache Settings</h2>
		<form method="post">';
		wp_nonce_field('speedycache_ajax_nonce');
		echo '<input type="hidden" value="speedycache_save_cache_settings" name="action"/>
		<div class="speedycache-option-wrap">
			<label for="speedycache_enable_cache" class="speedycache-custom-checkbox">
				<input type="checkbox" '.(!empty($speedycache->options['status']) ? ' checked' : '').' id="speedycache_enable_cache" name="status"/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Enable Cache', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Enables caching', 'speedycache').'</span> 
			</div>
		</div>
		
		<div class="speedycache-option-wrap">
			<label for="speedycache_mobile" class="speedycache-custom-checkbox">
				<input type="checkbox" '.(!empty($speedycache->options['mobile']) ? ' checked' : '').' id="speedycache_mobile" name="mobile"/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Mobile Override', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Disable desktop cache display on mobile devices.', 'speedycache').'</span>
			</div>
		</div>';

		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_mobile_theme" class="speedycache-custom-checkbox">
					<input type="checkbox" '.(!empty($speedycache->options['mobile_theme']) ? ' checked' : '').' id="speedycache_mobile_theme" name="mobile_theme"/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Mobile Cache', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Separate cache for Mobile version of your website, modern themes don\'t require this', 'speedycache').'</span>
				</div>
			</div>';

		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label for="speedycache_mobile_theme" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_mobile_theme" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Mobile Cache', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Separate cache for Mobile version of your website, modern themes don\'t require this', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		echo '<div class="speedycache-option-wrap">
			<label for="speedycache_preload" class="speedycache-custom-checkbox">
				<input type="checkbox" '.(!empty($speedycache->options['preload']) ? ' checked' : '').' id="speedycache_preload" name="preload"/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Preload', 'speedycache').'
				<span class="speedycache-modal-settings-link" setting-id="speedycache_preload" style="display:'.(!empty($speedycache->options['preload']) ? 'inline-block' : 'none').';">- Settings</span></span>
				<span class="speedycache-option-desc">'.esc_html__('Create the cache of all the site automatically', 'speedycache').'</span>
			</div>
		</div>
		<!--SpeedyCache Update Post Modal Starts Here-->
		<div modal-id="speedycache_preload" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-header">
					<div>'.esc_html__('Preload Settings', 'speedycache').'</div>
					<div title="Close Modal" class="speedycache-close-modal">
						<span class="dashicons dashicons-no"></span>
					</div>
				</div>
				<div class="speedycache-modal-content">
					<p style="color:#666;margin-top:0 !important;">'.esc_html__('Select the interval after which you want the preload to run', 'speedycache').'</p>

					<div class="speedycache-form-input">
						<label style="width:100%;">
							<span style="font-weight:500; margin-bottom:5px">'.esc_html__('Select Preload interval', 'speedycache').'</span>
							<select name="preload_interval" value="'.(!empty($speedycache->options['preload_interval']) ? esc_attr($speedycache->options['preload_interval']) : '').'">
								<option value="2" '.(isset($speedycache->options['preload_interval']) ? selected($speedycache->options['preload_interval'], '2', false) : '').'>'.esc_html__('Every 2 hours', 'speedycache').'</option>
								<option value="6" '.(isset($speedycache->options['preload_interval']) ? selected($speedycache->options['preload_interval'], '6', false) : '').'>'.esc_html__('Every 6 hours', 'speedycache').'</option>
								<option value="12" '.(isset($speedycache->options['preload_interval']) ? selected($speedycache->options['preload_interval'], '12', false) : '').'>'.esc_html__('Every 12 hours', 'speedycache').'</option>
								<option value="24" '.(isset($speedycache->options['preload_interval']) ? selected($speedycache->options['preload_interval'], '24', false) : '').'>'.esc_html__('Once a day', 'speedycache').'</option>
								<option value="168" '.(isset($speedycache->options['preload_interval']) ? selected($speedycache->options['preload_interval'], '168', false) : '').'>'.esc_html__('Once a week', 'speedycache').'</option>
							</select>
						</label>
					</div>
				</div>
				<div class="speedycache-modal-footer">
					<button type="button" action="close">
						<span>'.esc_html__('Submit', 'speedycache').'</span>
					</button>
				</div>
			</div>
		</div>
		<div class="speedycache-option-wrap">
			<label for="speedycache_lbc" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_lbc" name="lbc" '.(!empty($speedycache->options['lbc']) ? ' checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Browser Caching', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Stores web data locally for faster loading.', 'speedycache').'</span>
			</div>
		</div>
		
		<div class="speedycache-option-wrap">
			<label for="speedycache_logged_in_user" class="speedycache-custom-checkbox">
				<input type="checkbox" '.(!empty($speedycache->options['logged_in_user']) ? ' checked' : '').' id="speedycache_logged_in_user" name="logged_in_user"/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Logged-in Users', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Serve cached version to logged in user.', 'speedycache').'</span>
			</div>
		</div>
		
		<div class="speedycache-option-wrap">
			<label for="speedycache_gzip_compression" class="speedycache-custom-checkbox">
				<input type="checkbox" '.(!empty($speedycache->options['gzip']) ? ' checked' : '').' id="speedycache_gzip_compression" name="gzip"/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('GZIP Compressions', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Compresses the text files to reduce the size to be sent on the network.', 'speedycache').'</span>
			</div>
		</div>

		<div class="speedycache-option-wrap">
			<label for="speedycache_purge_varnish" class="speedycache-custom-checkbox">
				<input type="checkbox" '.(!empty($speedycache->options['purge_varnish']) ? ' checked' : '').' id="speedycache_purge_varnish" name="purge_varnish" />
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Purge Varnish', 'speedycache').'
				<span class="speedycache-modal-settings-link" setting-id="speedycache_purge_varnish" style="display:'.(!empty($speedycache->options['purge_varnish']) ? 'inline-block' : 'none').';">- Settings</span>
				</span>
				<span class="speedycache-option-desc">'.esc_html__('Deletes cache created by Varnish on Deletion of cache from SpeedyCache', 'speedycache').'</span>
			</div>
		</div>
		
		<!--SpeedyCache Update Post Modal Starts Here-->
		<div modal-id="speedycache_purge_varnish" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-header">
					<div>'.esc_html__('Varnish Settings', 'speedycache').'</div>
					<div title="Close Modal" class="speedycache-close-modal">
						<span class="dashicons dashicons-no"></span>
					</div>
				</div>
				<div class="speedycache-modal-content">
					<p style="color:#666;margin-top:0 !important;">'.esc_html__('If you use any different IP for Varnish than the default then set it here.', 'speedycache').'</p>

					<div class="speedycache-form-input">
						<label style="width:100%;">
							<span style="font-weight:500; margin-bottom:5px">'.esc_html__('Set your Varnish IP', 'speedycache').'</span>
							<input type="text" name="varniship" style="width:100%;" value="'. (!empty($speedycache->options['varniship']) ? esc_attr($speedycache->options['varniship']) : '127.0.0.1').'"/><br/>
						</label>
					</div>
				</div>
				<div class="speedycache-modal-footer">
					<button type="button" action="close">
						<span>'.esc_html__('Submit', 'speedycache').'</span>
					</button>
				</div>
			</div>
		</div>

		<h3>'.esc_html__('Cache Lifespan', 'speedycache').'</h3>
		<p>'.esc_html__('This defines the time after which the cache will be automatically deleted. Set to 0 to disable automatic cache deletion.', 'speedycache').'</p>
		<input type="number" min="0" name="purge_interval" value="'.(isset($speedycache->options['purge_interval']) ? esc_html($speedycache->options['purge_interval']) : 24).'"/>
		<select name="purge_interval_unit">
			<option value="hours" '.(!empty($speedycache->options['purge_interval_unit']) ? selected($speedycache->options['purge_interval_unit'], 'hours', false) : ' selected').'>'.esc_html__('Hours', 'speedycache').'</option>
			<option value="days" '.(!empty($speedycache->options['purge_interval_unit']) ? selected($speedycache->options['purge_interval_unit'], 'days', false) : '').'>'.esc_html__('Days', 'speedycache').'</option>
		</select>
		<div>
		<input type="checkbox" id="speedycache-run-exact-time" name="purge_enable_exact_time" value="1" '.(!empty($speedycache->options['purge_enable_exact_time']) ? checked($speedycache->options['purge_enable_exact_time'], true, false) : '').'/>'.esc_html__('Run at exact time', 'speedycache').'
		<div id="speedycache-exact-time-selector" style="'.(empty($speedycache->options['purge_enable_exact_time']) ? 'display:none;' : '').'">
			<label>
				<input type="time" name="purge_exact_time" value="'.(!empty($speedycache->options['purge_exact_time']) ? esc_attr($speedycache->options['purge_exact_time']) : '').'"/>'.esc_html__('Select exact time', 'speedycache').'
			</label>
			<p class="description">'.esc_html__('This is dependent on WP Cron, which does not guarantee execution at an exact time. For more details, ', 'speedycache').'<a href="https://speedycache.com/docs/caching/running-cache-lifespan-at-specific-time/" target="_blank">click here</a>
		</div>
		<div>
			<input type="checkbox" name="auto_purge_fonts" value="1" '.(!empty($speedycache->options['auto_purge_fonts']) ? checked($speedycache->options['auto_purge_fonts'], true, false) : '').'/>'.esc_html__('Delete Fonts', 'speedycache').'
			<input type="checkbox" name="auto_purge_gravatar" value="1" '.(!empty($speedycache->options['auto_purge_gravatar']) ? checked($speedycache->options['auto_purge_gravatar'], true, false) : '').'/>'.esc_html__(
			'Delete Gravatar', 'speedycache').'
			<p class="description">'.esc_html__('Deletion of these options only takes effect if the lifespan is more than 10 hours.', 'speedycache').'</p>
		</div>
		
		</div>
		';

		self::save_btn();
		echo '</form>';
	}
	
	static function file_tab(){
		global $speedycache;

		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/file.svg" height="32" width="32"/> File Optimization</h2>
		<form method="post">';
		wp_nonce_field('speedycache_ajax_nonce');
		echo '<input type="hidden" name="action" value="speedycache_save_file_settings"/>';
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_minify_html" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_minify_html" name="minify_html" '.(!empty($speedycache->options['minify_html']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Minify HTML', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Removes comments, extra spaces', 'speedycache').'</span>
				</div>
			</div>';
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Minify HTML', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Removes comments, extra spaces', 'speedycache').'</span>
				</div>
			</div>';
		}

		echo '<div class="speedycache-option-wrap">
			<label for="speedycache_minify_css" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_minify_css" name="minify_css" '.(!empty($speedycache->options['minify_css']) ? ' checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Minify CSS', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('You can decrease the size of CSS files', 'speedycache').'</span>
			</div>
		</div>
		
		<div class="speedycache-option-wrap">
			<label for="speedycache_combine_css" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_combine_css" name="combine_css" '.(!empty($speedycache->options['combine_css']) ? ' checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Combine CSS', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Combines CSS files to reduce HTTP requests', 'speedycache').'</span>
			</div>
		</div>';
		
		// Critical CSS Option
		if(!defined('SITEPAD')){
			if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->license) && !empty($speedycache->license['active'])){
				echo '<div class="speedycache-option-wrap">
					<label for="speedycache_critical_css" class="speedycache-custom-checkbox" style="margin-top:0;">
						<input type="checkbox" id="speedycache_critical_css" name="critical_css" '.(!empty($speedycache->options['critical_css']) ? ' checked' : '').'/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html__('Critical CSS', 'speedycache');
						
						if(!empty($speedycache->options['critical_css'])){
							echo ' - 
							<span class="speedycache-action-link" action-name="speedycache_critical_css">'.esc_html__('Create Now', 'speedycache').'</span>
							&nbsp;&nbsp;|&nbsp;&nbsp;
							<span class="speedycache-modal-settings-link" setting-id="speedycache_critical_css">'.esc_html__('Logs', 'speedycache').'</span>';
						}
						echo '</span><span class="speedycache-option-desc">'.esc_html__('It extracts the necessary CSS of the viewport on load to improve load speed.', 'speedycache').'</span>
					</div>
				</div>';
				
				echo wp_kses(\SpeedyCache\CriticalCss::status_modal(), array_merge(wp_kses_allowed_html('post'), [
					'div' => [
						'modal-id' => true,
						'class' => true,
						'title' => true,
						'style' => true,
					]
				]));
			} else { 
				if(empty($speedycache->license) || empty($speedycache->license['active'])){
					$need_key = true;
				}
				
				echo '<div class="speedycache-option-wrap speedycache-disabled">
					<label class="speedycache-custom-checkbox">
						<input type="checkbox" disabled/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html__('Critical CSS', 'speedycache').' <span class="speedycache-premium-tag">'.(!empty($need_key) ? 'Link License Key' : 'Premium').'</span></span>
						<span class="speedycache-option-desc">'.esc_html__('It extracts the necessary CSS of the viewport on load to improve load speed.', 'speedycache').'</span>
					</div>
				</div>';
			}
		}
		
		// Unused CSS
		if(!defined('SITEPAD')){
			if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->license) && !empty($speedycache->license['active'])){
				echo '<div class="speedycache-option-wrap">
					<label for="speedycache_unused_css" class="speedycache-custom-checkbox" style="margin-top:0;">
						<input type="checkbox" id="speedycache_unused_css" name="unused_css" '.(!empty($speedycache->options['unused_css']) ? ' checked' : '').'/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name" title="Unused CSS"><span>'.esc_html__('Unused CSS', 'speedycache').'</span><a href="https://speedycache.com/docs/file-optimization/how-to-remove-unused-css/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>
						<span class="speedycache-modal-settings-link" setting-id="speedycache_unused_css" style="display:'.(!empty($speedycache->options['unused_css']) ? 'inline-block' : 'none').';">- Settings</span>
						</span><span class="speedycache-option-desc">'.esc_html__('It removes the unused CSS.', 'speedycache').'</span>
					</div>
				</div>

				<div modal-id="speedycache_unused_css" class="speedycache-modal">
					<div class="speedycache-modal-wrap">
						<div class="speedycache-modal-header">
							<div>'.esc_html__('Unused CSS Settings', 'speedycache').'</div>
							<div title="Close Modal" class="speedycache-close-modal">
								<span class="dashicons dashicons-no"></span>
							</div>
						</div>
						<div class="speedycache-modal-content speedycache-info-modal">
							<p>'.esc_html__('Extracts the CSS being used on the page.', 'speedycache').'</p>
							<div>
								<label>
									<span style="font-weight:500; margin:20px 0 3px 0; display:block;">'.esc_html__('Load Unused CSS', 'speedycache').'</span>
									<span class="speedycache-model-label-description" style="margin-bottom:5px;">'.esc_html__('Select the way you want the Unused CSS to load.', 'speedycache').'</span>
								</label>
								<input type="radio" id="speedycache_unusedcss_async" name="unusedcss_load" value="async" '.(empty($speedycache->options['unusedcss_load']) || (!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] == 'async') ? 'checked' : '').'/>
								<input type="radio" id="speedycache_unusedcss_interaction" name="unusedcss_load" value="interaction" '.(!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] == 'interaction' ? 'checked' : '').'/>
								<input type="radio" id="speedycache_unusedcss_remove" name="unusedcss_load" value="remove" '.(!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] == 'remove' ? 'checked' : '').'/>
								<div class="speedycache-radio-input">
									<label for="speedycache_unusedcss_async">'.esc_html__('Asynchronously', 'speedycache').'</label>
									<label for="speedycache_unusedcss_interaction">'.esc_html__('On User Interaction', 'speedycache').'</label>
									<label for="speedycache_unusedcss_remove">'.esc_html__('Remove', 'speedycache').'</label>
								</div>
							</div>
							<div class="speedycache-unusedcss-excludes">
								<label for="speedycache_unused_css_exclude_stylesheets" style="width:100%;">
									<span style="font-weight:500; margin:20px 0 3px 0; display:block;">'.esc_html__('Exclude Stylesheets', 'speedycache').'</span>
									<span class="speedycache-model-label-description">'.esc_html__('Enter the URL, name or the stylesheet to be excluded from removing unused CSS.', 'speedycache').'</span>
									<textarea name="unused_css_exclude_stylesheets" id="speedycache_unused_css_exclude_stylesheets" rows="4" placeholder="Enter URL, CSS file name one per line">'.(!empty($speedycache->options['unused_css_exclude_stylesheets']) ? esc_html(implode("\n", $speedycache->options['unused_css_exclude_stylesheets'])) : '').'</textarea>
								</label>
								<br><br>
								<label for="speedycache_unusedcss_include_selector" style="width:100%;">
									<span style="font-weight:500; margin:20px 0 3px 0; dispaly:block;">'.esc_html__('Include Selectors', 'speedycache').'</span>
									<span class="speedycache-model-label-description">'.esc_html__('Enter Selectors you want to be included in used CSS', 'speedycache').'</span>
									<textarea name="unusedcss_include_selector" id="speedycache_unusedcss_include_selector" rows="4" placeholder="Enter selector one per line">'.(!empty($speedycache->options['unusedcss_include_selector']) ? esc_html(implode("\n", $speedycache->options['unusedcss_include_selector'])) : '').'</textarea>
								</label>
							</div>
							<div class="speedycache-modal-footer">
								<button type="button" action="close">
									<span>'.esc_html__('Submit', 'speedycache').'</span>
								</button>
							</div>
						</div>
					</div>
				</div>';
			} else {
				echo '<div class="speedycache-option-wrap speedycache-disabled">
					<label class="speedycache-custom-checkbox">
						<input type="checkbox" disabled/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html__('Unused CSS', 'speedycache').'<span class="speedycache-premium-tag">'.(!empty($need_key) ? 'Link License Key' : 'Premium').'</span></span>
						<span class="speedycache-option-desc">'.esc_html__('It removes the unused CSS from the page.', 'speedycache').'</span>
					</div>
				</div>';
			}
		}
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_minify_js" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_minify_js" name="minify_js" '.(!empty($speedycache->options['minify_js']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Minify JS', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('You can decrease the size of JS files', 'speedycache').'</span>
				</div>
			</div>';
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<div class="speedycache-form-input">
					<label class="speedycache-custom-checkbox">
						<input type="checkbox"disabled/>
						<div class="speedycache-input-slider"></div>
					</label>
				</div>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Minify JS', 'speedycache').'<span class="speedycache-premium-tag">Premium</span></span>
					<span class="speedycache-option-desc">'.esc_html__('You can decrease the size of JS files', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		echo '<div class="speedycache-option-wrap">
			<label for="speedycache_combine_js" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_combine_js" name="combine_js" '.(!empty($speedycache->options['combine_js']) ? ' checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Combine JS', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Reduce HTTP requests by Combining JS files', 'speedycache').'</span>
			</div>
		</div>';

		// Delay JS option
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_delay_js" class="speedycache-custom-checkbox" style="margin-top:0;">
					<input type="checkbox" id="speedycache_delay_js" name="delay_js" '.(!empty($speedycache->options['delay_js']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name"><span>'.esc_html__('Delay JS', 'speedycache').'</span><a href="https://speedycache.com/docs/file-optimization/how-to-delay-js-until-user-interaction/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>
					<span class="speedycache-modal-settings-link" setting-id="speedycache_delay_js" style="display:'.(!empty($speedycache->options['delay_js']) ? 'inline-block' : 'none').';">- Settings</span>
					</span><span class="speedycache-option-desc">'.esc_html__('Delays JS until user interaction(like scroll, click etc) to improve performance', 'speedycache').'</span>
				</div>
			</div>
			
			<div modal-id="speedycache_delay_js" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
						<div>'.esc_html__('Delay JS', 'speedycache').'</div>
						<div title="Close Modal" class="speedycache-close-modal">
							<span class="dashicons dashicons-no"></span>
						</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<p>'.esc_html__('Delay All is a more aggressive option which can increase the chances of breaking the site too.', 'speedycache').'</p>
						<input type="radio" id="speedycache_delayjs_selected" name="delay_js_mode" value="selected" '.(empty($speedycache->options['delay_js_mode']) || (!empty($speedycache->options['delay_js_mode']) && $speedycache->options['delay_js_mode'] == 'selected') ? 'checked' : '').'/>
						<input type="radio" id="speedycache_delayjs_all" name="delay_js_mode" value="all" '.(!empty($speedycache->options['delay_js_mode']) && $speedycache->options['delay_js_mode'] == 'all' ? 'checked' : '').'/>
						
						<div class="speedycache-radio-input">
							<label for="speedycache_delayjs_selected">'.esc_html__('Delay Selected', 'speedycache').'</label>
							<label for="speedycache_delayjs_all">'.esc_html__('Delay All', 'speedycache').'</label>
						</div>
						<div class="speedycache-delay_js_list">
							<label for="speedycache_delay_js_excludes" style="width:100%;">
								<span style="font-weight:500; margin:20px 0 3px 0; display:block;">Scripts to exclude</span>
								<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;">Enter Below The Scipts that you no not want to be delayed.</span>
								<textarea name="delay_js_excludes" id="speedycache_delay_js_excludes" rows="4" placeholder="jquery.min">'.(!empty($speedycache->options['delay_js_excludes']) && is_array($speedycache->options['delay_js_excludes']) ? esc_html(implode("\n", $speedycache->options['delay_js_excludes'])) : '').'</textarea>
							</label>
			
							<label for="speedycache_delay_js_scripts" style="width:100%;">
								<span style="font-weight:500; margin:20px 0 3px 0; dispaly:block;">Scripts to Delay</span>
								<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;">Enter the scripts that you want to be delayed like googletagmanager.com</span>
								<textarea name="delay_js_scripts" id="speedycache_delay_js_scripts" rows="4" placeholder="googletagmanager.com">'.(!empty($speedycache->options['delay_js_scripts']) && is_array($speedycache->options['delay_js_scripts']) ? esc_html(implode("\n", $speedycache->options['delay_js_scripts'])) : '').'</textarea>
								<h5>Suggestions</h5>
								<p style="position: relative;">
								<button class="speedycache-delay-suggestions">
								<span>Use These</span>
								<svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="#e3e3e3"><path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"/></svg>
								</button>
								<span class="speedycache-modal-scripts">
									fbevents.js<br>
									google-analytics.com<br>
									adsbygoogle.js<br>
									googletagmanager.com<br>
									fbq(<br>
									ga( \' <br>
									ga(\'<br>
									/gtm.js<br>
									/gtag/js<br>
									gtag(<br>
									/gtm-<br>
									/gtm.<br>
								</span>
								</p>
							</label>
						</div>
						<div class="speedycache-modal-footer">
							<button type="button" action="close">
								<span>'.esc_html__('Submit', 'speedycache').'</span>
							</button>
						</div>
					</div>
				</div>
			</div>';
		}else{
		echo '<div class="speedycache-option-wrap speedycache-disabled">
			<label class="speedycache-custom-checkbox" style="margin-top:0;">
				<input type="checkbox" disabled/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Delay JS', 'speedycache').'<span class="speedycache-premium-tag">Premium</span></span>
				</span><span class="speedycache-option-desc">'.esc_html__('Delays JS until user interaction(like scroll, click etc) to improve performance', 'speedycache').'</span>
			</div>
		</div>';
		}
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_render_blocking" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_render_blocking" name="render_blocking" '.(!empty($speedycache->options['render_blocking']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name" setting-id="speedycache_render_blocking">'.esc_html__('Defer JS', 'speedycache').'
					<span class="speedycache-modal-settings-link" setting-id="speedycache_render_blocking" style="display:'.(!empty($speedycache->options['render_blocking']) ? 'inline-block' : 'none').';">- Settings</span>
					</span><span class="speedycache-option-desc">'.esc_html__('Defers render-blocking JavaScript resources', 'speedycache').'</span>
				</div>
			</div>

			<div modal-id="speedycache_render_blocking" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-header">
					<div>'.esc_html__('Defer JS', 'speedycache').'</div>
					<div title="Close Modal" class="speedycache-close-modal">
						<span class="dashicons dashicons-no"></span>
					</div>
				</div>
				<div class="speedycache-modal-content speedycache-info-modal">
					
					<div>
						<label for="speedycache_render_blocking_excludes" style="width:100%;">
							<span style="font-weight:500; margin:20px 0 3px 0; display:block;">'.esc_html__('Exclude script from Render Blocking JS', 'speedycache').'</span>
							<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;">'.esc_html__('Add one script per line ,Enter the script URL or script ID', 'speedycache').'</span>
							<textarea name="render_blocking_excludes" id="speedycache_render_blocking_excludes" rows="4" style="width:100%">'.(!empty($speedycache->options['render_blocking_excludes']) && is_array($speedycache->options['render_blocking_excludes']) ? esc_html(implode("\n", $speedycache->options['render_blocking_excludes'])) : '').'</textarea>
						</label>
					</div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span>'.esc_html__('Submit', 'speedycache').'</span>
						</button>
					</div>
				</div>
			</div>
		</div>';
			

		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
					<label class="speedycache-custom-checkbox">
						<input type="checkbox" disabled/>
						<div class="speedycache-input-slider"></div>
					</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Defer JS', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Defers render-blocking JavaScript resources', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		echo '<div class="speedycache-option-wrap">
			<label for="speedycache_disable_emojis" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_disable_emojis" name="disable_emojis" '. (!empty($speedycache->options['disable_emojis']) ? ' checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Disable Emojis', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('You can remove the emoji inline css and wp-emoji-release.min.js', 'speedycache').'</span>
			</div>
		</div>';
		
		// Lazy Render HTML element
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
					<label for="speedycache_lazy_load_html" class="speedycache-custom-checkbox" style="margin-top:0;">
					<input type="checkbox" id="speedycache_lazy_load_html" name="lazy_load_html" '.(!empty($speedycache->options['lazy_load_html']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name"><span>'.esc_html__('Lazy Render HTML Element', 'speedycache').'</span><a href="https://speedycache.com/docs/file-optimization/how-to-lazy-render-html-elements/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>
					<span class="speedycache-modal-settings-link" setting-id="speedycache_lazy_load_html" style="display:'.(!empty($speedycache->options['lazy_load_html']) ? 'inline-block' : 'none').';">- Settings</span>
					</span><span class="speedycache-option-desc">'.esc_html__('Lazy Render a HTML element(class or id) if not in view-port.', 'speedycache').'</span>
				</div>
			</div>
			<div modal-id="speedycache_lazy_load_html" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
						<div>'.esc_html__('Lazy Render HTML Elements', 'speedycache').'</div>
						<div title="Close Modal" class="speedycache-close-modal">
							<span class="dashicons dashicons-no"></span>
						</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<p>'.esc_html__('Lazy Rendering HTML is usually good for Comments.', 'speedycache').'</p>
						<div>
							<label for="speedycache_lazy_load_html_elements" style="width:100%;">
								<span style="font-weight:500; margin:20px 0 3px 0; display:block;">'.esc_html__('Elements to Lazy Render', 'speedycache').'</span>
								<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;">'.esc_html__('Add one element per line, use # as prefix for ID and . as prefix for class.', 'speedycache').'</span>
								<textarea name="lazy_load_html_elements"id="lazy_load_html_elements" rows="4" style="width:100%">'.(!empty($speedycache->options['lazy_load_html_elements']) ? esc_html(implode("\n", $speedycache->options['lazy_load_html_elements'])) : '').'</textarea>
							</label>
						</div>
						<div class="speedycache-modal-footer">
							<button type="button" action="close">
								<span>'.esc_html__('Submit', 'speedycache').'</span>
							</button>
						</div>
					</div>
				</div>
			</div>';
		}else{
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label for="speedycache_lazy_load_html" class="speedycache-custom-checkbox" style="margin-top:0;">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Lazy Render HTML Element', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					</span><span class="speedycache-option-desc">'.esc_html__('Lazy Render a HTML element(class or id) if not in view-port.', 'speedycache').'</span>
				</div>
			</div>';
		}

		self::save_btn();
		echo '</form>';
		
	}

	static function preload_tab(){
		global $speedycache;
		
		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/preload.svg" height="32" width="32"/> Preload Settings</h2>
		<form method="post">';
		wp_nonce_field('speedycache_ajax_nonce');
		echo '<input type="hidden" value="speedycache_save_preload_settings" name="action"/>';
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_critical_images" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_critical_images" name="critical_images" '.(!empty($speedycache->options['critical_images']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Preload Critical Images', 'speedycache').'
					<span class="speedycache-modal-settings-link" setting-id="speedycache_critical_images" style="display:'.(!empty($speedycache->options['critical_images']) ? 'inline-block' : 'none').';">- Settings</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Preloads critical Images to improve LCP', 'speedycache').'</span>
				</div>
			</div>

			<!--SpeedyCache Lazy Load Modal Starts here-->
			<div modal-id="speedycache_critical_images" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
						<div>'.esc_html__('Preload Critical Images', 'speedycache').'</div>
						<div title="Close Modal" class="speedycache-close-modal">
							<span class="dashicons dashicons-no"></span>
						</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<div class="speedycache-modal-block">
							<p>'.esc_html__('Select the number of images you want to be preloaded.', 'speedycache').'</p>
							<table>
							<tr>
								<th>'.esc_html__('Critical Image Count', 'speedycache').'</th>
								<td>
									<div class="speedycache-form-input">
										<select name="critical_image_count" value="'.(!isset($speedycache->options['critical_image_count']) ? '' : esc_attr($speedycache->options['critical_image_count'])).'">';
											$image_count = array('1','2','3','4','5');

											foreach($image_count as $count){
												echo '<option value="'.esc_attr($count).'" '. ((!empty($speedycache->options['critical_image_count']) && $speedycache->options['critical_image_count'] == $count ) ? ' selected' : '') .'>'.esc_html($count).'</option>';
											}

										echo '</select>
									</div>
								</td>
							</tr>
							</table>
						</div>
					</div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span>'.esc_html__('Submit', 'speedycache').'</span>
						</button>
					</div>
				</div>
			</div>';

		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Preload Critical Images', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Preloads critical Images to improve LCP', 'speedycache').'</span>
				</div>				
			</div>';
		}

		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_instant_page" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_instant_page" name="instant_page" '.(!empty($speedycache->options['instant_page']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Instant Page', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Improves page load speed', 'speedycache').'</span>
				</div>
			</div>';
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Instant Page', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Improves page load speed', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		if ( !defined('SITEPAD') && (version_compare( get_bloginfo( 'version' ), '6.8', '>=' )) ) {
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_speculative_loading" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_speculative_loading" name="speculation_loading" '.(!empty($speedycache->options['speculation_loading']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Edit Speculative Loading', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_speculative_loading" style="display:'.(!empty($speedycache->options['speculation_loading']) ? 'inline-block' : 'none').';">- Settings</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Change how aggresive you want preloading/prefetching to happen.','speedycache').'</span>
				</div>
			</div>
			<div modal-id="speedycache_speculative_loading" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
						<div>'.esc_html__('Speculation Settings', 'speedycache').'</div>
						<div title="Close Modal" class="speedycache-close-modal">
							<span class="dashicons dashicons-no"></span>
						</div>
					</div>
					<div class="speedycache-modal-content">
						<p style="color:#666;margin-top:0 !important;"></p>

						<div class="speedycache-form-input">
							<label style="width:100%;">
								<span style="font-weight:500; margin-bottom:5px">'.esc_html__('Select the Speculation Mode', 'speedycache').'</span>
								<select name="speculation_mode" value="'.(!empty($speedycache->options['speculation_mode']) ? esc_attr($speedycache->options['speculation_mode']) : '').'">
									<option value="auto" '.(isset($speedycache->options['speculation_mode']) ? selected($speedycache->options['speculation_mode'], 'auto', false) : '').'>'.esc_html__('Auto', 'speedycache').'</option>
									<option value="prefetch" '.(isset($speedycache->options['speculation_mode']) ? selected($speedycache->options['speculation_mode'], 'prefetch', false) : '').'>'.esc_html__('Prefetch', 'speedycache').'</option>
									<option value="prerender" '.(isset($speedycache->options['speculation_mode']) ? selected($speedycache->options['speculation_mode'], 'prerender', false) : '').'>'.esc_html__('Prerender', 'speedycache').'</option>
									<option value="disabled" '.(isset($speedycache->options['speculation_mode']) ? selected($speedycache->options['speculation_mode'], 'disabled', false) : '').'>'.esc_html__('Disabled', 'speedycache').'</option>
								</select>
							</label>
						</div>
						<div class="speedycache-form-input">
							<label style="width:100%;">
								<span style="font-weight:500; margin-bottom:5px">'.esc_html__('Select Eagerness', 'speedycache').'</span>
								<select name="speculation_eagerness" value="'.(!empty($speedycache->options['speculation_eagerness']) ? esc_attr($speedycache->options['speculation_eagerness']) : '').'">
									<option value="auto" '.(isset($speedycache->options['speculation_eagerness']) ? selected($speedycache->options['speculation_eagerness'], 'auto', false) : '').'>'.esc_html__('Auto', 'speedycache').'</option>
									<option value="eager" '.(isset($speedycache->options['speculation_eagerness']) ? selected($speedycache->options['speculation_eagerness'], 'eager', false) : '').'>'.esc_html__('Eager', 'speedycache').'</option>
									<option value="moderate" '.(isset($speedycache->options['speculation_eagerness']) ? selected($speedycache->options['speculation_eagerness'], 'moderate', false) : '').'>'.esc_html__('Moderate', 'speedycache').'</option>
									<option value="conservative" '.(isset($speedycache->options['speculation_eagerness']) ? selected($speedycache->options['speculation_eagerness'], 'conservative', false) : '').'>'.esc_html__('Conservative', 'speedycache').'</option>
								</select>
							</label>
						</div>
					</div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span>'.esc_html__('Submit', 'speedycache').'</span>
						</button>
					</div>
				</div>
			</div>';
		}
				
		if(!defined('SITEPAD')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_dns_prefetch" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_dns_prefetch" name="dns_prefetch" '.(!empty($speedycache->options['dns_prefetch']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('DNS Prefetch', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_dns_prefetch" style="display:'.(!empty($speedycache->options['dns_prefetch']) ? 'inline-block' : 'none').';">- Settings</span></span>
					<span class="speedycache-option-desc">'.esc_html__('DNS prefetching can make external files load faster.', 'speedycache').'</span>
				</div>
			</div>
			<div modal-id="speedycache_dns_prefetch" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
							<div>'.esc_html__('Prefetch DNS Requests', 'speedycache').'</div>
							<div title="Close Modal" class="speedycache-close-modal">
								<span class="dashicons dashicons-no"></span>
							</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<h3>'.esc_html__('How DNS Prefetch can help?', 'speedycache').'</h3>		
						<p>'.esc_html__('DNS prefetch can improve page load performance by resolving domain names in advance, so that the browser can start loading resources from those domains as soon as possible.', 'speedycache').'</p>
						
						<label><strong>'.esc_html__('URLs to prefetch', 'speedycache').'</strong>
						<span style="display:block;">'.esc_html__('Specify external hosts to be prefetched (no http:, one per line)', 'speedycache').'</span>
						<textarea name="dns_urls" style="width:100%" rows="4" placeholder="//example.com">'.(!empty($speedycache->options['dns_urls']) ? esc_html(implode("\n", $speedycache->options['dns_urls'])) : '').'</textarea>
						</label>
					</div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span>'.esc_html__('Submit', 'speedycache').'</span>
						</button>
					</div>
				</div>
			</div>';
		}

		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_preload_resources" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_preload_resources" name="preload_resources" '.(!empty($speedycache->options['preload_resources']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Preload Resources', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_preload_resources" style="display:'.(!empty($speedycache->options['preload_resources']) ? 'inline-block' : 'none').';">- Settings</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Hints browser to load resources early.', 'speedycache').'</span>
				</div>
			</div>';
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'. esc_html__('Preload Resources', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'. esc_html__('Hints browser to load resources early.', 'speedycache').'</span>
				</div>
			</div>';

		}

		if(!defined('SITEPAD')){
			if(defined('SPEEDYCACHE_PRO')){
				echo '<div class="speedycache-option-wrap">
					<label for="speedycache_pre_connect" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_pre_connect" name="pre_connect" '. (!empty($speedycache->options['pre_connect']) ? 'checked' : '') .'/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'. esc_html__('PreConnect', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_pre_connect" style="display:'. (!empty($speedycache->options['pre_connect']) ? 'inline-block' : 'none').';">- Settings</span></span>
						<span class="speedycache-option-desc">'.esc_html__('Establish early connections to speed up page load.', 'speedycache').'</span>
					</div>
				</div>';
			} else {
				echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'. esc_html__('Preconnect', 'speedycache') .'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'. esc_html__('Establish early connections to speed up page load.', 'speedycache').'</span>
				</div>
			</div>';
			}
		}

		self::save_btn();
		echo '</form>';
		
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div modal-id="speedycache_preload_resources" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
							<div>'.esc_html__('Preload Resource', 'speedycache').'</div>
							<div title="Close Modal" class="speedycache-close-modal">
								<span class="dashicons dashicons-no"></span>
							</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<form class="speedycache-pseudo-form" data-type="preload_resource_list">'.
						wp_kses(self::preload_modal_options('preload_resource', ['type' => true, 'crossorigin' => true, 'priority' => true,  'device' => true]), [
							'input' => ['type' => true, 'value' => true, 'style' => true, 'name' => true, 'placeholder' => true],
							'option' => ['value' => true],
							'select' => ['name' => true, 'required' => true],
							'label' => ['for' => true, 'style' => true],
							'div' => ['class' => true, 'style' => true],
							'span' => ['class' => true, 'title' => true, 'spdf-hover-tooltip' => true, 'spdf-tooltip-position' => true],
							]).'
						<div style="display:flex; justify-content:center;">
							<button type="submit" class="speedycache-button speedycache-btn-black speedycache-preloading-add">Add<span class="speedycache-spinner"></span></button>
						</div>
						</form>';
					
						echo '<p><strong>Note:</strong> Preloading too many resources can actually slow down your website, so it\'s important to only preload the resources that are absolutely necessary for the initial load. These might include fonts, image, CSS or JS files.</p>';

						echo '<div style="width:100%; overflow-x:scroll;"><table class="speedycache-table speedycache-preloading-table" data-type="preload_resource_list">
							<thead>
								<tr>
									<th class="speedycache-table-hitem" scope="col" width="70%">'.esc_html__('Resource', 'speedycache').'</th>
									<th class="speedycache-table-hitem" scope="col" width="15%">'. esc_html__('Type', 'speedycache').'</th>
									<th class="speedycache-table-hitem" scope="col" width="10%"><abbr title="Crossorigin">'. esc_html__('CS', 'speedycache').'</abbr></th>
									<th class="speedycache-table-hitem" scope="col" width="10%">'. esc_html__('Device', 'speedycache').'</th>
									<th class="speedycache-table-hitem" scope="col" width="10%">'. esc_html__('Fetch Priority', 'speedycache').'</th>
									<th class="speedycache-table-hitem" scope="col" width="5%"></th>
								</tr>
							</thead>
							<tbody>';
							
							if(empty($speedycache->options['preload_resource_list']) || !is_array($speedycache->options['preload_resource_list'])){
								echo '<tr><td colspan="6" align="center" class="speedycache-preloading-empty">No Resource Preload added yet</td></tr>';
							} else {
								foreach($speedycache->options['preload_resource_list'] as $pkey => $preload_resource){
									echo '<tr>
										<td>'.esc_url($preload_resource['resource']).'</td>
										<td>'.esc_html($preload_resource['type']).'</td>
										<td>'.(!empty($preload_resource['crossorigin']) ? 'Yes' : 'No').'</td>
										<td>'.(!empty($preload_resource['device']) ? esc_html($preload_resource['device']) : 'All').'</td>
										<td>'.(!empty($preload_resource['fetch_priority']) ? esc_html(ucfirst($preload_resource['fetch_priority'])) : 'Auto').'</td>
										<td data-key="'.esc_attr($pkey).'"><span class="dashicons dashicons-trash"></span></td>
									</tr>';
								}
							}
							
							echo '</tbody>
						</table>
						</div>
					</div>
				</div>
			</div>';
			
			if(!defined('SITEPAD')){
				echo'
				<div modal-id="speedycache_pre_connect" class="speedycache-modal">
					<div class="speedycache-modal-wrap">
						<div class="speedycache-modal-header">
								<div>'. esc_html__('Preconnect', 'speedycache').'</div>
								<div title="Close Modal" class="speedycache-close-modal">
									<span class="dashicons dashicons-no"></span>
								</div>
						</div>
						<div class="speedycache-modal-content speedycache-info-modal">
							<form class="speedycache-pseudo-form" data-type="pre_connect_list">								
							'.wp_kses(self::preload_modal_options('pre_connect', ['crossorigin' => true]), [
								'input' => ['type' => true, 'value' => true, 'style' => true, 'name' => true, 'placeholder' => true],
								'option' => ['value' => true],
								'select' => ['name' => true, 'required' => true],
								'label' => ['for' => true, 'style' => true],
								'div' => ['class' => true, 'style' => true],
								'span' => ['aria-label' => true, 'data-microtip-position' => true, 'role' => true],
								]).'
							<div style="display:flex; justify-content:center;">
								<button tabindex="" type="submit" class="speedycache-button speedycache-btn-black speedycache-preloading-add">Add<span class="speedycache-spinner"></span><span class="dashicons dashicons-yes speedycache-spinner-done"></span></button>
							</div>
							</form>';
							if(!empty($speedycache->options['pre_connect_list']) && count($speedycache->options['pre_connect_list']) > 6){
								echo '<p><strong>Note:</strong> A good rule of thumb is to limit the number of preconnects to 6-8. However, the exact number will vary depending on the specific website and the resources that are being loaded.</p>';
							}

							echo '<table class="speedycache-table speedycache-preloading-table" data-type="pre_connect_list">
								<thead>
									<tr>
										<th class="speedycache-table-hitem" scope="col" width="80%">'.esc_html__('Resource', 'speedycache').'</th>
										<th class="speedycache-table-hitem" scope="col" width="15%">'. esc_html__('Crossorigin', 'speedycache').'</th>
										<th class="speedycache-table-hitem" scope="col" width="5%"></th>
									</tr>
								</thead>
								<tbody>';
								
								if(empty($speedycache->options['pre_connect_list']) || !is_array($speedycache->options['pre_connect_list'])){
									echo '<tr><td colspan="4" align="center" class="speedycache-preloading-empty">'.esc_html__('No PreConnect added yet', 'speedycache').'</td></tr>';
								} else {
									foreach($speedycache->options['pre_connect_list'] as $pkey => $pre_connect){
										echo '<tr>
											<td>'.esc_html($pre_connect['resource']).'</td>
											<td>'.(!empty($pre_connect['crossorigin']) ? 'Yes' : 'No').'</td>
											<td data-key="'.esc_html($pkey).'"><span class="dashicons dashicons-trash"></span></td>
										</tr>';
									}
								}
								echo '</tbody>
							</table>
						</div>
					</div>
				</div>';
			}
		}
	}
	
	static function cdn_tab(){
		global $speedycache;
		
		$default_file_types = ['aac', 'css', 'eot', 'gif', 'jpeg', 'js', 'jpg', 'less', 'mp3', 'mp4', 'ogg', 'otf', 'pdf', 'png', 'svg', 'swf', 'ttf', 'webm', 'webp', 'woff', 'woff2'];

		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/cdn.svg" height="32" width="32"/> CDN Settings</h2>
		<form method="POST">';
		wp_nonce_field('speedycache_ajax_nonce');
		echo '<input type="hidden" value="speedycache_save_cdn_settings" name="action"/>
		<div class="speedycache-option-wrap">
			<label for="speedycache_enable_cdn" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_enable_cdn" name="enable_cdn" '.(!empty($speedycache->cdn['enabled']) ? ' checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Enable CDN', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('This will start rewriting asset URL\'s to the CDN URL', 'speedycache').'</span>
			</div>
		</div>
		
		<div class="speedycache-stacked-option-wrap">
			<div class="speedycache-option-info">
				<label class="speedycache-option-name">'.esc_html__('Select CDN Provider', 'speedycache').'</label>
			</div>
			<div>
				<select id="speedycache-cdn-type" name="cdn_type" value="'.(!empty($speedycache->cdn['cdn_type']) ? esc_attr($speedycache->cdn['cdn_type']) : '').'">
					<option  value="cloudflare" '. (!empty($speedycache->cdn['cdn_type']) ? selected($speedycache->cdn['cdn_type'], 'cloudflare', false) : '').'>Cloudflare</option>
					<option value="bunny" '. (!empty($speedycache->cdn['cdn_type']) ? selected($speedycache->cdn['cdn_type'], 'bunny', false) : '').'>Bunny</option>
					<option value="other" '. (!empty($speedycache->cdn['cdn_type']) ? selected($speedycache->cdn['cdn_type'], 'other', false) : '').'>Others</option>
				</select>
			</div>
		</div>
		<div class="speedycache-stacked-option-wrap">
			<div class="speedycache-option-info">
				<label class="speedycache-option-name">'.esc_html__('CDN URL', 'speedycache').'</label>
				<span class="speedycache-option-desc">'.esc_html__('It is the URL that CDN Provider provides you.', 'speedycache').'</span>
			</div>
			<div>
				<input type="url" name="cdn_url" style="width:50%;" value="'.(!empty($speedycache->cdn['cdn_url']) ? esc_url($speedycache->cdn['cdn_url']) : '').'" id="speedycache-cdn-url" placeholder="https://cdn-url.com"/>
			</div>
		</div>
		
		<div class="speedycache-stacked-option-wrap">
			<div class="speedycache-option-info">
				<label class="speedycache-option-name">'.esc_html__('API Key', 'speedycache').'</label>
				<span class="speedycache-option-desc">'.esc_html__('API keys/ tokens are not required but used to purge cache on CDN when cache from SpeedyCache gets purged.', 'speedycache').'</span>
			</div>
			<div>
				<input type="text" name="cdn_key" style="width:50%;" id="speedycache-cdn-key" value="'.(!empty($speedycache->cdn['cdn_key']) ? esc_html($speedycache->cdn['cdn_key']) : '').'"/>
			</div>
		</div>
		
		<div class="speedycache-stacked-option-wrap">
			<div class="speedycache-option-info">
				<label class="speedycache-option-name">'.esc_html__('File Types', 'speedycache').'</label>
				<span class="speedycache-option-desc">'.esc_html__('Types of files you want to be served through the CDN(one file per line)', 'speedycache').'</span>
			</div>
			<div>
				<textarea name="file_types" style="width:50%;" rows="5">'.(!empty($speedycache->cdn['file_types']) ? esc_html(implode("\n", $speedycache->cdn['file_types'])) : esc_html(implode("\n", $default_file_types))).'</textarea>
			</div>
		</div>
		
		<div class="speedycache-stacked-option-wrap">
			<div class="speedycache-option-info">
				<label class="speedycache-option-name">'.esc_html__('Exclude Sources', 'speedycache').'</label>
				<span class="speedycache-option-desc">'.esc_html__('Files you do not want to be rewritten to a CDN url(one file per line).', 'speedycache').'</span>
			</div>
			<div>
				<textarea name="excludekeywords" style="width:50%;" rows="5">'.(!empty($speedycache->cdn['excludekeywords']) ? esc_html(implode("\n", $speedycache->cdn['excludekeywords'])) : '').'</textarea>
			</div>
		</div>
		
		<div class="speedycache-stacked-option-wrap">
			<div class="speedycache-option-info">
				<label class="speedycache-option-name">'.esc_html__('Specific Sources', 'speedycache').'</label>
				<span class="speedycache-option-desc">'.esc_html__('Specific files which you want to be rewritten using CDN URL(one file per line).', 'speedycache').'</span>
			</div>
			<div>
				<textarea name="keywords" style="width:50%;" rows="5">'.(!empty($speedycache->cdn['keywords']) ? esc_html(implode("\n", $speedycache->cdn['keywords'])) : '').'</textarea>
			</div>
		</div>';
		
		self::save_btn();
		
		echo '</form>';
	}
	
	static function excludes_tab(){
		
		$excludes = get_option('speedycache_exclude', []);

		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/excludes.svg" height="32" width="32"/> Exclude Settings</h2>
		<div class="speedycache-table-actions">
			<div class="speedycache-table-filter">
				<select id="speedycache-type-filter">
					<option value="">All</option>
					<option value="cookie">Cookies</option>
					<option value="js">JS</option>
					<option value="css">CSS</option>
					<option value="useragent">User Agent</option>
					<option value="page">Page</option>
				</select>
			</div>
			<div class="speedycach-table-add-new"><button class="speedycache-button speedycache-btn-black" id="speedycache_add_excludes">'.esc_html__('Add New Rule', 'speedycache').'</button></div>
		</div>
		<div class="speedycache-table speedycache-exclude-list" id="speedycache-exclude-list">
			<table>
			<thead>
				<tr role="row">
					<th>Type</th>
					<th>Prefix</th>
					<th>Content</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>';
				if(empty($excludes)){
					echo '<tr role="row">
						<td colspan="4">'.esc_html__('No exclude rule added yet','speedycache').'</td>
					</tr>';
				} else {
					foreach($excludes as $id => $exclude){
						echo '<tr role="row" data-id='.esc_attr($id).'>
							<td>'.esc_html($exclude['type']).'</td>
							<td>'.esc_html($exclude['prefix']).'</td>';
							if($exclude['prefix'] == 'post_id'){
								if(!is_array($exclude['content'])){
									$exclude['content'] = explode(',', $exclude['content']);
								}

								echo '<td>'.'#ID:';

								foreach($exclude['content'] as $exclude){
									$post = get_post($exclude);
									
									if(empty($post)){
										continue;
									}

									$post_link = get_permalink($post->ID);
									
									echo '<a href="'.esc_url($post_link).'" class="speedycache-tooltip-link" target="_blank">'.esc_html($exclude);
									
									// We show a tool tip with the excluded page URL and title
									echo '<div class="speedycache-link-tooltip">
									<h4>'.esc_html($post->post_title).'</h4>
									<p>'.esc_url($post_link).'</p>
									</div></a>';
								}
								
								echo '</td>';
							}
							else{
								echo'<td>'.esc_html($exclude['content']).'</td>';
							}
							echo'<td><button class="speedycache-button speedycache-delete-rule">Delete<span class="speedycache-spinner"></span></button>
						</tr>';
					}
				}
			echo '</tbody>
			</table>
		</div>
		
		<div modal-id="speedycache_add_excludes" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-header">
					<div>'.esc_html__('Add Exclude Rule', 'speedycache').'</div>
					<div title="Close Modal" class="speedycache-close-modal">
						<span class="dashicons dashicons-no"></span>
					</div>
				</div>
				<div class="speedycache-modal-content speedycache-info-modal">
					<form method="POST">
						<input type="hidden" name="action" value="speedycache_save_excludes"/>';
						wp_nonce_field('speedycache_ajax_nonce');
						echo '<div class="speedycache-input-wrap">
							<label for="speedycache-exclude-type">Exclude Type</label>
							<select name="type" id="speedycache-exclude-type" class="speedycache-100" required>
								<option value="page"/>Page</option>
								<option value="useragent"/>User-Agent</option>
								<option value="cookie"/>Cookie</option>
								<option value="css"/>CSS</option>
								<option value="js"/>JS</option>
							</select>
						</div>
						
						<div class="speedycache-input-wrap">
							<label for="speedycache-exclude-rule-prefix">Exclude</label>
							<select name="prefix" id="speedycache-exclude-rule-prefix" class="speedycache-100" required>
								<option selected="" value="" selected data-partof="page">Select a Value</option>
								<option value="homepage" data-partof="page">Home Page</option>
								<option value="category" data-partof="page">Categories</option>
								<option value="tag" data-partof="page">Tags</option>
								<option value="post" data-partof="page">Posts</option>
								<option value="page" data-partof="page">Pages</option>
								<option value="post_id" data-partof="page">Post ID</option>
								<option value="shortcode" data-partof="page">Shortcode</option>
								<option value="archive" data-partof="page">Archives</option>
								<option value="attachment" data-partof="page">Attachments</option>
								<option value="startwith" data-partof="page">Starts With</option>
								<option value="contain" data-partof="page,useragent,cookie,css,js">Contains</option>
								<option value="exact" data-partof="page">URI Is Equal To</option>';
								if (!defined('SITEPAD')){
									echo '<option value="woocommerce_items_in_cart" data-partof="cookie">has Woocommerce Items in Cart</option>';
								}
								echo'
							</select>
						</div>
						<div class="speedycache-input-wrap" style="display:none;">
							<label for="speedycache-exclude-rule-content">Content</label>
						</div>
						<div class="speedycache-exclude-btn-wrap">
							<button class="speedycache-button speedycache-btn-black">'.esc_html__('Save Rule', 'speedycache').'<span class="speedycache-spinner"></button>
						</div>
					</form>
				</div>
			</div>
		</div>';
	}
	
	static function media_tab(){
		global $speedycache;
		
		// Backward compatibility
		if(!empty($speedycache->options['lazy_load_keywords']) && is_string($speedycache->options['lazy_load_keywords'])){
			$speedycache->options['lazy_load_keywords'] = explode(',', $speedycache->options['lazy_load_keywords']);
		}

		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/media.svg" height="32" width="32"/> Media Settings</h2>
		<form method="POST">';
		wp_nonce_field('speedycache_ajax_nonce');
		
		echo '<input type="hidden" name="action" value="speedycache_save_media_settings"/>';
		if(!defined('SITEPAD')){
			echo'
			<div class="speedycache-option-wrap">
				<label for="speedycache_gravatar_cache" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_gravatar_cache" name="gravatar_cache" '.(!empty($speedycache->options['gravatar_cache']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Gravatar Cache', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Locally host Gravatar', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<input type="hidden" value="'.(isset($speedycache->options['lazy_load_placeholder']) ? esc_attr($speedycache->options['lazy_load_placeholder']) : '').'" id="speedycache_lazy_load_placeholder" name="lazy_load_placeholder"/>
				<input style="display: none;" type="checkbox" '.(isset($speedycache->options['lazy_load_exclude_full_size_img']) ? esc_attr($speedycache->options['lazy_load_exclude_full_size_img']) : '').' id="speedycache_lazy_load_exclude_full_size_img" name="lazy_load_exclude_full_size_img">
				
				<label for="speedycache_lazy_load" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_lazy_load" name="lazy_load" '.(!empty($speedycache->options['lazy_load']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Lazy Load', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_lazy_load" style="display:'.(!empty($speedycache->options['lazy_load']) ? 'inline-block' : 'none').';">- Settings</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Load images and iframes when they enter the browsers viewport', 'speedycache').'</span>
				</div>
			</div>

			<!--SpeedyCache Lazy Load Modal Starts here-->
			<div modal-id="speedycache_lazy_load" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
						<div>'.esc_html__('Lazy Load Settings', 'speedycache').'</div>
						<div title="Close Modal" class="speedycache-close-modal">
							<span class="dashicons dashicons-no"></span>
						</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<div class="speedycache-modal-block">
							<h4>'.esc_html__('Image Placeholder', 'speedycache').'</h4>
							<p>'.esc_html__('Specify an image to be used as a placeholder while other images finish loading.', 'speedycache').'
								<a target="_blank" href="https://speedycache.com/docs/miscellaneous/lazy-load-images-and-iframes/">
								<span class="dashicons dashicons-info"></span>
								</a>
							</p>
							<div class="speedycache-form-input">
								<select name="lazy_load_placeholder" id="speedycache-ll-type" class="speedycache_lazy_load_placeholder speedycache-100" value="'.(!isset($speedycache->options['lazy_load_placeholder']) ? '' : esc_attr($speedycache->options['lazy_load_placeholder'])).'">
									<option value="default" '.((isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] == 'default') ? 'selected' : '').'>'.esc_html(preg_replace("/https?\:\/\//", '', esc_url(SPEEDYCACHE_URL))).'/assets/images/image-palceholder.png'.'</option>
									<option value="custom" '.((isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] == 'custom') ? 'selected' : '').'>'.esc_html__('Custom Placeholder', 'speedycache').'</option>
								</select>
							</div>';
							$hide_css_class = '';
						
							if(isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] != 'custom'){
								$hide_css_class = 'speedycache-hidden '; 
							}
							
							echo '<div class="speedycache-form-input">
								<input type="text" id="speedycache-custom-ll-url"  class="'.esc_attr($hide_css_class).'speedycache-100" placeholder="https://example.com/sample.jpg" name="lazy_load_placeholder_custom_url" value="'.(!isset($speedycache->options['lazy_load_placeholder_custom_url']) ? '' : esc_url($speedycache->options['lazy_load_placeholder_custom_url'])).'"/>
							</div>
						<div class="speedycache-modal-block">
							<h4>'.esc_html__('Exclude above fold images', 'speedycache').'</h4>
							<p>'.esc_html__('Number of images you want to exclude from getting lazyloaded from top of the screen', 'speedycache').'</p>
							<select name="exclude_above_fold">';

							foreach([0,1,2,3,4,5] as $exclude_no){
								$selected = '';
								if(isset($speedycache->options['exclude_above_fold']) && $exclude_no == $speedycache->options['exclude_above_fold']){
									$selected = 'selected';
								}elseif(!isset($speedycache->options['exclude_above_fold']) && $exclude_no == 2){
									$selected = 'selected';
								}

								echo '<option value="'.esc_attr($exclude_no).'" '.esc_attr($selected).'>'.esc_html($exclude_no).'</option>';
							}
							echo '</select>
						</div>

						<div class="speedycache-modal-block">
							<h4>'.esc_html__('Exclude Sources', 'speedycache').'</h4>
							<p>'.esc_html__('It is enough to write a keyword such as', 'speedycache').' <strong>home.jpg or iframe or .gif</strong> instead of full url.</p>
							<div class="speedycache-form-input">
								<label for="speedycache-full-width">
									'.esc_html__('Add Keyword', 'speedycache').'
									<span class="speedycache-input-desc">('.esc_html__('one keyword per line', 'speedycache').')</span>
									<textarea name="lazy_load_keywords" class="speedycache-100" rows="5">'.(empty($speedycache->options['lazy_load_keywords']) ? '' : esc_attr(implode("\n", $speedycache->options['lazy_load_keywords']))).'</textarea>
								</label>
							</div>';
						echo '</div>
					</div></div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span>'.esc_html__('Submit', 'speedycache').'</span>
						</button>
					</div>
				</div>
			</div>';
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Lazy Load', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Load images and iframes when they enter the browsers viewport', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_image_dimensions" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_image_dimensions" name="image_dimensions" '.(!empty($speedycache->options['image_dimensions']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Image Dimensions', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Adds dimensions to the image, to reduce CLS', 'speedycache').'</span>
				</div>
			</div>';	
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Image Dimensions', 'speedycache').'<span class="speedycache-premium-tag">'. esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Adds dimensions to the image, to reduce CLS', 'speedycache').'</span>
				</div>
			</div>';
		}

		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_local_gfonts" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_local_gfonts" name="local_gfonts" '. (!empty($speedycache->options['local_gfonts']) ? 'checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Local Google Fonts', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Loads google fonts from your local server', 'speedycache').'</span>
				</div>
			</div>';
			
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox"disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Local Google Fonts', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Loads google fonts from your local server', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		if(defined('SPEEDYCACHE_PRO')){
			echo '<div class="speedycache-option-wrap">
				<label for="speedycache_google_fonts" class="speedycache-custom-checkbox">
					<input type="checkbox" id="speedycache_google_fonts" name="google_fonts" '.(!empty($speedycache->options['google_fonts']) ? ' checked' : '').'/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Google Fonts', 'speedycache').'</span>
					<span class="speedycache-option-desc">'.esc_html__('Load Google Fonts asynchronously', 'speedycache').'</span>
				</div>
			</div>';
		} else {
			echo '<div class="speedycache-option-wrap speedycache-disabled">
				<label class="speedycache-custom-checkbox">
					<input type="checkbox" disabled/>
					<div class="speedycache-input-slider"></div>
				</label>
				<div class="speedycache-option-info">
					<span class="speedycache-option-name">'.esc_html__('Google Fonts', 'speedycache').'<span class="speedycache-premium-tag">'.esc_html__('Premium', 'speedycache').'</span></span>
					<span class="speedycache-option-desc">'.esc_html__('Load Google Fonts asynchronously', 'speedycache').'</span>
				</div>
			</div>';
		}
		
		echo '<div class="speedycache-option-wrap">
			<label for="speedycache_font_rendering" class="speedycache-custom-checkbox">
				<input type="checkbox" id="speedycache_font_rendering" name="font_rendering" '. (!empty($speedycache->options['font_rendering']) ? 'checked' : '').'/>
				<div class="speedycache-input-slider"></div>
			</label>
			<div class="speedycache-option-info">
				<span class="speedycache-option-name">'.esc_html__('Improve Font Rendering', 'speedycache').'</span>
				<span class="speedycache-option-desc">'.esc_html__('Improved Font rendeing by adding text-rendring CSS.', 'speedycache').'</span>
			</div>
		</div>';

		self::save_btn();
		echo '</form>';
	}
	
	static function settings_tab(){
		echo '<h2><img src="'.esc_url(SPEEDYCACHE_URL).'/assets/images/icons/settings.svg" height="32" width="32"/> '.esc_html__('General Settings', 'speedycache').'</h2>';
		
		$roles = get_editable_roles();
		
		if(!empty($roles)){
			$saved_roles = get_option('speedycache_deletion_roles', []);
			
			echo '<div class="speedycache-option-info">
			<span class="speedycache-option-name">'.esc_html__('Can Delete Cache', 'speedycache').'</span>
			<span class="speedycache-option-desc">'.esc_html__('Allows roles to delete cache using Admin bar and post links, Admin is included by default', 'speedycache').'</span>
			<form method="POST">';
			wp_nonce_field('speedycache_ajax_nonce');
		
			echo '<input type="hidden" name="action" value="speedycache_save_deletion_role_settings"/>
			<div class="speedycache-deletion-roles">';
			foreach($roles as $key => $role){
				// Admin will always have access to everything, so no need to give option to be able to enable it.
				if($key == 'administrator'){
					continue;
				}

				// We need to make sure the user has capability to publish_posts
				// Giving access to anyone other than this capability does not makes sense
				// As giving option to enable subscriber could cause issue becuase of human error.
				if(empty($role['capabilities']) || !is_array($role['capabilities']) || !array_key_exists('publish_posts', $role['capabilities'])){
					continue;
				}
				
				$checked = false;
				if(in_array($key, $saved_roles)){
					$checked = 'checked';
				}

				echo '<label for="speedycache-admin-bar-cap-'.esc_attr($key).'"><input type="checkbox" id="speedycache-admin-bar-cap-'.esc_attr($key).'"name="cache_deletion_roles[]" value="'.esc_attr($key).'" '.esc_attr($checked).'/>'.esc_html($role['name']).'</label>';
			}
			echo '</div>
			<div class="speedycache-btn-spl-wrapper"><button class="speedycache-button speedycache-btn-black" style="margin-top:10px;">Save<span class="speedycache-spinner"></span></button></div></form></div>';
		}

		echo '<div class="speedycache-option-info" style="margin-top:20px;">
		<label class="speedycache-option-name">'.esc_html__('Import / Export Settings', 'speedycache').'</label>
		<span class="speedycache-option-desc" style="margin-bottom:10px;">'.esc_html__('Imports SpeedyCache Settings from another site or Exports your current SpeedyCache Settings as a JSON file', 'speedycache').'</span>
		</div>
		<div>
			<select id="speedycache-import-export" name="img-exp">
				<option value="import">Import</option>
				<option value="export">Export</option>
			</select>
		</div>';

		echo '<!-- Import Section -->
		<form method="POST" enctype="multipart/form-data">
		<input type="hidden" name="action" value="speedycache_import_settings"/>
		<div class="speedycache-option-info speedycache-import-block" style="display:block;">
			<span class="speedycache-option-desc">'.esc_html__('Select a JSON file containing SpeedyCache Settings. This will overwrite your current SpeedyCache Settings', 'speedycache').'</span>
			<input type="file" name="speedycache_import_file" id="speedycache_import_file" accept=".json" required />
			<button class="speedycache-button speedycache-btn-black speedycache-import-settings" style="margin-top:10px;">Import Settings<span class="speedycache-spinner"></span></button>
		</div>
		</form>';

		echo '<!-- Export Section -->
		<form method="POST" enctype="multipart/form-data">
		<input type="hidden" name="action" value="speedycache_export_settings"/>
		<div class="speedycache-option-info speedycache-export-block" style="display:none;">
			<span class="speedycache-option-desc">'.esc_html__('Click the button below to download the current SpeedyCache settings as a JSON file', 'speedycache').'</span>
			<button class="speedycache-button speedycache-btn-black speedycache-export-settings" style="margin-top:10px;">Download Export File<span class="speedycache-spinner"></span></button>
		</div>
		</form>';
	}
	
	static  function preload_modal_options($field_name, $fields){
		if(empty($fields)){
			return '';
		}

		switch($field_name){
			case 'pre_connect':
				$placeholder = 'https://fonts.google.com';
				break;

			default:
				$placeholder = site_url() . '/' . (defined('SITEPAD') ? 'sitepad-data' : 'wp-content') . '/uploads/image.jpg';
		}

		$html = '<div class="speedycache-preloading-options">
		<div class="speedycache-stacked-label" style="width:100%;">
			<label style="width:100%;">
				<span>Resource URL <span spdf-hover-tooltip="Required field" spdf-tooltip-position="bottom">*</span></span>
				<input type="text" name="resource" style="width:100%;" placeholder="'.esc_html($placeholder).'" required/>
			</label>
		</div>';
		
		$html .= '<div class="speedycache-preload-checkboxes">';
		if(isset($fields['parent_selector'])){
			$html .= '<label><input type="checkbox" name="parent_selector" value="true"/>Use Parent Selector</label>';
		}
		
		if(isset($fields['crossorigin'])){
			$html .= '<label><input type="checkbox" name="crossorigin" value="true"/>Crossorigin</label>';
		}
		
		$html .= '</div>';
		
		if(isset($fields['type'])){
			$html .= '<div class="speedycache-stacked-label"><label><span>Resource Type <span spdf-hover-tooltip="Required field" spdf-tooltip-position="top">*</span></span><select name="type" required>
				<option value="">Select Type</option>
				<option value="image">Image</option>
				<option value="font">Font</option>
				<option value="script">Script</option>
				<option value="style">Style</option>
				<option value="audio">Audio</option>"
				<option value="document">Document</option>
				<option value="video">Video</option>
			</select></label></div>';
		}
		
		if(isset($fields['priority'])){
			$html .= '<div class="speedycache-stacked-label"><label>
			<span>Fetch Priority</span>
			<select name="fetch_priority">
				<option value="" selected>Auto</option>
				<option value="high">High</option>
				<option value="low">Low</option>
			</select></label>
			</div>';
		}
		
		if(isset($fields['device'])){
			$html .= '<div class="speedycache-stacked-label"><label>
			<span>Device <span class="dashicons dashicons-editor-help" spdf-hover-tooltip="For this to work, you will need to enable Mobile Override and Mobile Cache options, this is not a required field" spdf-tooltip-position="top"></span></span>
			<select name="device">
				<option value="" selected>All</option>
				<option value="desktop">Desktop</option>
				<option value="mobile">Mobile</option>
			</select></label>
			</div>';
		}
		
		$html .= '</div>';
		
		return $html;

	}
	
	static function pro_notice($tab_name){
		echo '<h2>'.esc_html($tab_name).'</h2>
		<div class="notice notice-warning">
        <p>'.esc_html__('This is a part of SpeedyCache Pro, so update/upgrade to pro to utilize this feature', 'speedycache').'</p>
    </div>';
	}
	
	static function save_btn(){
		echo '<div class="speedycache-save-settings-wrapper"><button class="speedycache-button speedycache-btn-black">'.esc_html__('Save Settings', 'speedycache').'<span class="speedycache-spinner"></span><svg class="speedycache-spinner-done" xmlns="http://www.w3.org/2000/svg" height="15px" viewBox="0 -960 960 960" width="15px" fill="#FFF"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg></button></div>';
	}
	
	static function pro_upsell(){

		$features = [
			'Delay JS Execution',
			'Defer JS Loading',
			'Lazy Load Iframes',
			'Database Cleanup',
			'Critical CSS',
			'and More...',
		];

		echo '<div class="speedycache-promo-modern-card">
			<div class="speedycache-promo-header-group">
			<h3 class="speedycache-promo-title">SpeedyCache</h3>
			<span class="speedycache-promo-badge-pro">Pro</span>
			</div>

			<p class="speedycache-promo-desc">'.esc_html__('Unlock advanced performance features.', 'speedycache').'</p>

			<ul class="speedycache-promo-feature-list">';
			foreach($features as $feature){
				echo '<li class="speedycache-promo-feature-item">
					<div class="speedycache-promo-check-circle">
						<div class="speedycache-promo-check-icon"></div>
					</div>
					'.esc_html($feature).'
				</li>';
			}
			echo '</ul>

			<a href="https://speedycache.com/pricing/?utm_source=plugin_settings" class="speedycache-promo-btn-main" target="_blank">
				<span class="speedycache-promo-btn-text">'.esc_html__('Upgrade to Pro', 'speedycache').'</span>
				<span class="speedycache-promo-arrow">&rarr;</span>
			</a>
		</div>';
	}
}

