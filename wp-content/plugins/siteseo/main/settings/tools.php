<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO\Settings;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Tools{

	static function menu(){

		echo '<div id="siteseo-root">';
		
		Util::admin_header();

		$plugins = Util::importable_plugins();

		echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">
				<span id="siteseo-tab-title"><strong>'.esc_html__('Tools - SiteSEO','siteseo').'</strong></span><br/><br/>
				<span class="line"></span>
				<div style="siteseo-tools-page">';
					if(!defined('SITEPAD')){
						echo '<h3>'.esc_html__('Import Settings From Other Plugins','siteseo').'</h3>
						<div class="siteseo_wrap_label">
							<p class="description">'.esc_html__('Import posts and terms metadata from the specified source', 'siteseo').'</p>
						</div>

						<p><select id="siteseo-plugin-selector">
							<option value="none">'.esc_html__('Select an option', 'siteseo').'</option>';
							foreach($plugins as $plugin => $name){
								$plugin_slug = explode('/', $plugin);
								$plugin = $plugin_slug[0];

								echo '<option value="'. esc_attr($plugin) . '-migration-tool">'.esc_html($name).'</option>';
							}

						echo '</select></p>
						<p class="description">' . esc_html__('You don\'t need to enable the selected SEO plugin to run the import.', 'siteseo').'</p>';
					
						foreach($plugins as $plugin =>$name){
							self::display_plugins($plugin, $name);
						}

						echo '<span class="line"></span>';
					}
					
					echo '<h3>'.esc_html__('Export plugin settings','siteseo').'</h3>
					<div class="siteseo_wrap_label">
						<p class="description">'.esc_html__('Export the plugin settings for this site as a .json file, making it easy to import the configuration into another site.', 'siteseo').'</p>
					</div>

					<div class="siteseo_wrap_label">
						<button class="btn btnSecondary" id="siteseo-export-btn">'.esc_html__('Export', 'siteseo').'</button>
					</div>
					<span class="line"></span>
					
					<h3>'.esc_html__('Import plugin settings', 'siteseo').'</h3>
					<div class="siteseo_wrap_label">
						<p class="description">'.esc_html__('Import the plugin settings from a .json file. You can obtain this file by exporting the settings from another site using the form above.','siteseo').'</p>
					</div>
							
					<div class=siteseo_wrap_label>
						<input type="file" id="siteseo-import-file" accept=".json" />
					</div>

					<div class="siteseo_wrap_label">
						<button class="brn btnSecondary" id="siteseo-import-btn">'. esc_html__('Import', 'siteseo') .'</button>
					</div>
					
					<span class="line"></span>
					
					<h3>'.esc_html__('Reset All Settings', 'siteseo').'</h3>
					<div class="siteseo_wrap_label"><div class="siteseo-notice is-warning">
						<span id="dashicons-warning" class="dashicons dashicons-info"></span>&nbsp;
						<div><p>'.
						/* translators: placeholders are just <strong> tag */ 
						wp_kses_post(sprintf(__('%1$s WARNING: %2$s Delete all options related to this plugin in your database.','siteseo'), '<strong>', '</strong>')).'</p></div>
					</div></div>
					<button class="btn btnSecondary" id="siteseo-reset-settings">'.esc_html__('Reset settings', 'siteseo').'</button>
	
			</div>
			</form></div>';
	}
	
	static function display_plugins($plugin,$name){
		$seo_title = 'SiteSEO';
		$plugin_slug = explode('/', $plugin);
		$plugin = $plugin_slug[0];
		
		echo '<div id="'.esc_attr($plugin).'-migration-tool" class="postbox siteseo-section-tool">
		<div class="inside">
		<h3>'. /* translators: %s represents the import posts and terms */ 
		sprintf(esc_html__('Import posts and terms (if available) metadata from %s', 'siteseo'), esc_html($name)).'</h3>
		<p>'. esc_html__('By clicking Migrate, we\'ll import:', 'siteseo').'</p>
		<ul>
			<li>'. esc_html__('Title tags', 'siteseo') .'</li>
			<li>'. esc_html__('Meta description', 'siteseo') .'</li>
			<li>'. esc_html__('Facebook Open Graph tags (title, description and image thumbnail)', 'siteseo') .'</li>
			<li>'. esc_html__('Twitter tags (title, description and image thumbnail)', 'siteseo') .'</li>
			<li>'. esc_html__('Meta Robots (noindex, nofollow...)', 'siteseo') .'</li>
			<li>'.esc_html__('Canonical URL', 'siteseo').'</li>';

			if($plugin !='slim-seo' && $plugin != 'surerank'){
				echo '<li>'. esc_html__('Focus / target keywords', 'siteseo') .'<li>';
			}
			
			if($plugin != 'all-in-one-seo-pack' && $plugin !='slim-seo' && $plugin != 'surerank'){
				echo '<li>'. esc_html__('Primary category', 'siteseo') .'</li>';
			}
			
			if('autodescription' == $plugin || 'all-in-one-seo-pack' == $plugin || 'wp-seopress' == $plugin){
				echo '<li>'. esc_html__('Redirect URL', 'siteseo') .'</li>';
			}

			echo '</ul>
					<div class="siteseo_wrap_label">						
						<div class="siteseo-notice is-warning">
							<span id="dashicons-warning" class="dashicons dashicons-warning"></span>&nbsp;
								<p>'. 
								/* translators: %s represents the degree of severity */ 
								wp_kses_post(sprintf(__('<strong>WARNING:</strong> Migration will delete / update all <strong>%1$s posts and terms metadata</strong>. Some dynamic variables will not be interpreted. We do <strong>NOT delete any %2$s data</strong>.', 'siteseo'), esc_html($seo_title), esc_html($name))). '
								</p>
						</div>
					</div>
						
					<button id="siteseo-'.esc_attr($plugin).'-migrate" type="button" class="btn btnSecondary">' 
						. esc_html__('Import now', 'siteseo').'</button><span class="spinner"></span><div class="log"></div>
					</div>
				</div>';
		}

}
