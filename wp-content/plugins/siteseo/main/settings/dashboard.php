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

class Dashboard{

	static function dashboard_tab(){
		global $siteseo;
		
		$pro = get_option('siteseo_pro_options');
		$options = $siteseo->setting_enabled;

		$titles_meta_toggle = isset($options['toggle-titles']) ? $options['toggle-titles'] : '';
		$sitemap_toggle = isset($options['toggle-xml-sitemap']) ? $options['toggle-xml-sitemap'] : '';
		$social_toggle = isset($options['toggle-social']) ? $options['toggle-social'] : '';
		$advanced_toggle = isset($options['toggle-advanced']) ? $options['toggle-advanced'] : '';
		$analytics_toggle = isset($options['toggle-google-analytics']) ? $options['toggle-google-analytics'] : '';
		$indexing_toggle = isset($options['toggle-instant-indexing']) ? $options['toggle-instant-indexing'] : '';

		//pro-features
		$toggle_localBusiness = isset($pro['toggle_state_local_buz']) ? $pro['toggle_state_local_buz'] : '';
		$toggle_easy_digital = isset($pro['toggle_state_easy_digital']) ? $pro['toggle_state_easy_digital'] : '';
		$toggle_woocommerce  = isset($pro['toggle_state_woocommerce']) ? $pro['toggle_state_woocommerce'] : '';
		$toggle_structured_data = isset($pro['toggle_state_stru_data']) ? $pro['toggle_state_stru_data'] : '';
		$toggle_state_redirect = isset($pro['toggle_state_redirect_monitoring']) ? $pro['toggle_state_redirect_monitoring'] : '';
		$toggle_state_google_news = isset($pro['toggle_state_google_news']) ? $pro['toggle_state_google_news'] : '';
		$toggle_state_video_sitemap = isset($pro['toggle_state_video_sitemap']) ? $pro['toggle_state_video_sitemap'] : '';
		$toggle_state_llm_txt = isset($pro['toggle_state_llm_txt']) ? $pro['toggle_state_llm_txt'] : '';

		$nonce = wp_create_nonce('siteseo_toggle_nonce');

		$pro_nonce = wp_create_nonce('siteseo_pro_toggle_nonce');

		$siteseo_dashboard_img = SITESEO_ASSETS_URL.'/img/seo-get-started.jpg';
		$siteseo_loginizer_product = SITESEO_ASSETS_URL.'/img/loginizer_product.png';
		
		echo'<div id="siteseo-root">';

		Util::admin_header();
		$dismissed_intro = get_option('siteseo_dismiss_intro', 0);

		echo'<div id="siteseo-dashbord">';
		
		if(empty($dismissed_intro) && !defined('SITEPAD')){
			echo '<div class="siteseo-dashbord-intro">
			   <div class="siteseo-text-content">
					<h2>'.esc_html__('HOW-TO GET STARTED', 'siteseo').'</h2><h1>'.esc_html__('Welcome to SiteSEO!', 'siteseo').'</h1>
					<p>'.esc_html__('Launch our installation wizard to quickly and easily configure the basic SEO settings for your site. Cant find the answers to your questions? Write us at support@siteseo.io. A happiness engineer will be happy to help you.', 'siteseo').'</p>
					<div class="siteseo-buttons">
					<a class="get-started" href="?page=siteseo-onboarding">'.esc_html__('Get started', 'siteseo').'</a>
					<a class="dismiss" id="siteseo-dismiss-get-started" href="#">'.esc_html__('Dismiss', 'siteseo').'</a>
					</div>
				</div>
				<div class="siteseo-image-content"><img alt="'.esc_html__('Illustration of a megaphone with various icons representing SEO and digital marketing', 'siteseo').'" height="470" src="'.esc_url($siteseo_dashboard_img).'" width="470"/>
				</div>
			</div>';
		}

			echo '<div class="siteseo-dashbord-content">
				<section class="siteseo-dashboard-features">
					<h2>'.esc_html__('Manage SiteSEO Features', 'siteseo').'</h2></br/>
					<div class="siteseo-dashbord-container">
						<div class="siteseo-card">
							<div class="siteseo-card-body">
								<span class="dashicons dashicons-edit-large siteseo-card-icon"></span>
								<h3>'.esc_html__('Titles &amp; Metas', 'siteseo').'</h3>
								<p>'.esc_html__('Manage all your titles and metas for post types, taxonomies more...', 'siteseo').'</p>
							</div>
							<div class="siteseo-card-footer">
								<a href="admin.php?page=siteseo-titles">'.esc_html__('Settings', 'siteseo').'</a>';
								Util::render_toggle('Titles & Metas -SiteSEO', 'titles_meta_toggle', $titles_meta_toggle, $nonce, true);
						   echo'</div>
						</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-networking siteseo-card-icon"></span>
					<h3>'.esc_html__('XML & HTML Sitemaps', 'siteseo').'</h3>
					<p>'.esc_html__('Manage your XML - Image - Video- Taxonomies - HTML Sitemap more...', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">
					<a href="admin.php?page=siteseo-sitemaps">'.esc_html__('Settings', 'siteseo').'</a>';
					Util::render_toggle('Sitemaps - SiteSEO', 'sitemap_toggle', $sitemap_toggle, $nonce,true);
				echo'</div>
			</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-share siteseo-card-icon"></span>
					<h3>'.esc_html__('Social Networks', 'siteseo').'</h3>
					<p>'.esc_html__('Open Graph, X Card, Google Knowledge Graph and more...', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">
					<a href="admin.php?page=siteseo-social">'.esc_html__('Settings','siteseo').'</a>';
					Util::render_toggle('Social - SiteSEO', 'social_toggle', $social_toggle, $nonce,true);
				echo'</div>
			</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-performance siteseo-card-icon"></span>
					<h3>'.esc_html__('Analytics', 'siteseo').'</h3>
					<p>'.esc_html__('Track everything about your visitors with Analytics/Matomo more...', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">
					<a href="admin.php?page=siteseo-analytics">'.esc_html__('Settings','siteseo').'</a>';
					Util::render_toggle('Analytics - SiteSEO', 'analytics_toggle', $analytics_toggle, $nonce,true);
				echo'</div>
			</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-superhero siteseo-card-icon"></span>
					<h3>'.esc_html__('Instant Indexing','siteseo').'</h3>
					<p>'.esc_html__('Ping Google & Bing to quickly index your content. Updated and  remove submit URLs','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">
					<a href="admin.php?page=siteseo-instant-indexing">'.esc_html__('Settings','siteseo').'</a>';
					Util::render_toggle('Instant indexing - SiteSEO', 'indexing_toggle', $indexing_toggle, $nonce,true);
				echo'</div>
			</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-format-gallery siteseo-card-icon"></span>
					<h3>'.esc_html__('Image SEO','siteseo').'</h3>
					<p>'.esc_html__('Optimize your images for SEO. Configure advanced settings more...','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">
					<a href="admin.php?page=siteseo-advanced">'.esc_html__('Settings','siteseo').'</a>';
					Util::render_toggle('Advanced - SiteSEO', 'advanced_toggle', $advanced_toggle, $nonce,true);
				echo'</div>
			</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-upload siteseo-card-icon"></span>
					<h3>'.esc_html__('Tools', 'siteseo').'</h3>
					<p>'.esc_html__('Import/Export plugin settings from site to site. Reset settings more...', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">
					<a href="admin.php?page=siteseo-tools">'.esc_html__('Settings', 'siteseo').'</a>
					<div class="siteseo-toggle-container">
					</div>
				</div>
			</div>';
			if(!defined('SITEPAD')){
				echo'<div class="siteseo-card">
					<div class="siteseo-card-body">
						<span class="dashicons dashicons-cart siteseo-card-icon"></span>
						<h3>'.esc_html__('WooCommerces SEO','siteseo'),'</h3>
						<p>'.esc_html__('Add meta tags required for WooCommerce SEO','siteseo').'</p>
					</div>
					<div class="siteseo-card-footer">';
						if(defined('SITESEO_PRO_VERSION')){
							echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings','siteseo').'</a>';
						} else{
							echo'<div class="siteseo-pro-badge">Pro</div>';
						}
						
						if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
							\SiteSEOPro\Settings\Util::render_toggle('woocommerce', $toggle_woocommerce, $pro_nonce, true);
						} 
					echo'</div></div>';
			

			echo '<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-money-alt siteseo-card-icon"></span>
					<h3>'.esc_html__('Easy Digital Downloads', 'siteseo').'</h3>
					<p>'.esc_html__('Add meta tags required for Easy Digitial Downloads SEO', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings','siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('edd', $toggle_easy_digital, $pro_nonce,true);
					}
				echo'</div></div>';
			}
			echo '<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-code-standards siteseo-card-icon"></span>
					<h3>'.esc_html__('Page Speed', 'siteseo').'</h3>
					<p>'.esc_html__('Enhance Your Website Performance with PageSpeed Insights','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else {
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
				echo'</div>
			</div>

			<div class="siteseo-card">
				'.((time() < strtotime('30 November 2025')) ? '<span class="siteseo-feature-update-badge">Updated</span>' : '') .'
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-list-view siteseo-card-icon"></span>
					<h3>'.esc_html__('Structured Data','siteseo').'</h3>
					<p>'.esc_html__('Enhance Search Visibility with Structured Data Optimization','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('structured', $toggle_structured_data, $pro_nonce,true);
					}
				echo'</div>
			</div>

			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-location siteseo-card-icon"></span>
					<h3>'.esc_html__('Local Business', 'siteseo').'</h3>
					<p>'.esc_html__('Optimize Your Online Presence for Local Business Success', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo '<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('local', $toggle_localBusiness, $pro_nonce, true);
					}
				echo'</div>
			</div>
			
			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-editor-unlink siteseo-card-icon"></span>
					<h3>'.esc_html__('Redirections / 404 monitoring','siteseo').'</h3>
					<p>'.esc_html__('Track 404 errors and set up redirects to improve user experience and SEO.','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('404_monitoring', $toggle_state_redirect, $pro_nonce, true);
					}
				echo'</div>
			</div>
			
			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-index-card siteseo-card-icon"></span>
					<h3>'.esc_html__('Google News','siteseo').'</h3>
					<p>'.esc_html__('Generate and manage a Google News sitemap to ensure your news articles get indexed quickly.','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('google_news', $toggle_state_google_news, $pro_nonce, true);
					}
				echo'</div>
			</div>
			
			<div class="siteseo-card">
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-format-video siteseo-card-icon"></span>
					<h3>'.esc_html__('Video Sitemap','siteseo').'</h3>
					<p>'.esc_html__('Create and manage a video sitemap to help search engines index your video content efficiently.','siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('video_sitemap', $toggle_state_video_sitemap, $pro_nonce, true);
					}
				echo'</div>
			</div>

			<div class="siteseo-card">
				'.((time() < strtotime('30 November 2025')) ? '<span class="siteseo-feature-new-badge">New</span>' : '') .'
				<div class="siteseo-card-body">
					<span class="dashicons dashicons-media-text siteseo-card-icon"></span>
					<h3>'.esc_html__('LLMs txt', 'siteseo').'</h3>
					<p>'.esc_html__('Generate an llms.txt file with a single click to help AI crawlers better understand, index, and represent your business accurately.', 'siteseo').'</p>
				</div>
				<div class="siteseo-card-footer">';
					if(defined('SITESEO_PRO_VERSION')){
						echo'<a href="admin.php?page=siteseo-pro-page">'.esc_html__('Settings', 'siteseo').'</a>';
					} else{
						echo'<div class="siteseo-pro-badge">Pro</div>';
					}
					
					if(class_exists('\SiteSEOPro\Settings\Util') && method_exists('\SiteSEOPro\Settings\Util', 'render_toggle')){
						\SiteSEOPro\Settings\Util::render_toggle('llm_txt', $toggle_state_llm_txt, $pro_nonce, true);
					}
				echo'</div>
			</div>';

	echo'</div></section>';
	
	if(!defined('SITEPAD')){
		echo'<section class="siteseo-dashboard-extras">';
			if(defined('SITESEO_PRO_VERSION') && defined('SITESEO_PRO_AI_BUY')){
				do_action('siteseo_pro_show_ai_tokens_sidebar');
			}
			echo '<div class="siteseo-need-help">
				<p>Quick Access</p>
				<div class="siteseo-quick-links">
					<div class="siteseo-quick-access-item">
						<span class="dashicons dashicons-format-status"></span>
						<a href="https://softaculous.deskuss.com/open.php?topicId=22" target="_blank">Support</a>
					</div>
					<div class="siteseo-quick-access-item">
						<span class="dashicons dashicons-media-document"></span>
						<a href="https://siteseo.io/docs/" target="_blank">Documentation</a>
					</div>
					<div class="siteseo-quick-access-item">
						<span class="dashicons dashicons-feedback"></span>
						<a href="https://softaculous.deskuss.com/open.php?topicId=22" target="_blank">Feedback</a>
					</div>
					<div class="siteseo-quick-access-item">
						<span class="dashicons dashicons-star-filled" style="color:#FFD700;"></span><a href="https://wordpress.org/support/plugin/siteseo/reviews/?rate=5#new-post" target="_blank">Rate Us</a>
					</div>
				</div>
			</div>
			<div class="siteseo-admin-softaculous-branding">SiteSEO - A Softaculous Product</div>';

			if(!defined('SITEPAD') && !defined('SITESEO_PRO_VERSION')){
				self::pro_upsell();
			}

			echo '</section>';
	}

	echo '</div></div></div>';
	}

	static function pro_upsell(){

		$features = [
			'Search Statistics',
			'Advanced Sitemaps',
			'Redirection Management',
			'AI-Generations Titles & Descriptions',
			'LLMs.txt Support',
			'and More…',
		];

		echo '<div class="siteseo-promo-modern-card">
			<div class="siteseo-promo-header-group">
			<h3 class="siteseo-promo-title">SiteSEO</h3>
			<span class="siteseo-promo-badge-pro">Pro</span>
			</div>

			<p class="siteseo-promo-desc">'.esc_html__('Unlock advanced performance features.', 'siteseo').'</p>

			<ul class="siteseo-promo-feature-list">';
			foreach($features as $feature){
				echo '<li class="siteseo-promo-feature-item">
					<div class="siteseo-promo-check-circle">
						<div class="siteseo-promo-check-icon"></div>
					</div>
					'.esc_html($feature).'
				</li>';
			}
			echo '</ul>

			<a href="https://siteseo.io/pricing/?utm_source=plugin_settings" class="siteseo-promo-btn-main" target="_blank">
				<span class="siteseo-promo-btn-text">'.esc_html__('Upgrade to Pro', 'siteseo').'</span>
				<span class="siteseo-promo-arrow">&rarr;</span>
			</a>
		</div>';
	}

}
