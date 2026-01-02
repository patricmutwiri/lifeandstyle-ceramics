<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class GoogleAnalytics{

	static function ga_render(){
		global $siteseo;
		
		if(empty($siteseo->setting_enabled['toggle-google-analytics'])){
			return;
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_clarity_enable']) && !empty($siteseo->analaytics_settings['google_analytics_clarity_project_id'])){
			add_action('wp_head', '\SiteSEO\GoogleAnalytics::microsoft_clarity');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_enable']) && !empty($siteseo->analaytics_settings['google_analytics_ga4'])){
			add_action('wp_head', '\SiteSEO\GoogleAnalytics::ga_tracking_code');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_enable'])){
			add_action('wp_footer', '\SiteSEO\GoogleAnalytics::tracking');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_link_tracking_enable'])){
			add_action('wp_footer', '\SiteSEO\GoogleAnalytics::add_tracking_script');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_matomo_enable']) && !empty($siteseo->analaytics_settings['google_analytics_matomo_site_id'])){
			add_action('wp_footer', '\SiteSEO\GoogleAnalytics::matomo_tracking_code');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_other_tracking'])){
			add_action('wp_head', '\SiteSEO\GoogleAnalytics::add_custom_head_script');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_other_tracking_body'])){
			add_action('wp_body_open', '\SiteSEO\GoogleAnalytics::add_custom_body_script');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_other_tracking_footer'])){
			add_action('wp_footer', '\SiteSEO\GoogleAnalytics::add_custom_footer_script');
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_opt_out_edit_choice'])){
			$load_cookies_bar = $siteseo->analaytics_settings['google_analytics_hook'];
			add_action($load_cookies_bar, '\SiteSEO\GoogleAnalytics::render_cookie_bar');
		}
		
	}

	static function render_cookie_bar(){
		global $siteseo;

		if(empty($siteseo->setting_enabled['toggle-google-analytics']) || empty($siteseo->analaytics_settings['google_analytics_disable'])){
			return;
		}

		// Plugin edit pages on which we should not show the cookie notice.
		$page_builders = ['fl_builder', 'elementor-preview', 'ct_builder', 'vc_editable', 'brizy_edit', 'tve', 'pagelayer-live'];

		foreach($page_builders as $builder){
			if(isset($_GET[$builder])){
				return;
			}
		}

		// load setting
		$auto_accept_cookies = !empty($siteseo->analaytics_settings['google_analytics_half_disable']) ? $siteseo->analaytics_settings['google_analytics_half_disable'] : '';
		$cookies_msg = !empty($siteseo->analaytics_settings['google_analytics_opt_out_msg']) ? $siteseo->analaytics_settings['google_analytics_opt_out_msg'] : 'By visiting our site, you agree to our privacy policy regarding cookies, tracking statistics, etc.';
		$accept_btn_msg = !empty($siteseo->analaytics_settings['google_analytics_opt_out_msg_ok']) ? $siteseo->analaytics_settings['google_analytics_opt_out_msg_ok'] : 'Accept';
		$close_btn_msg = !empty($siteseo->analaytics_settings['google_analytics_opt_out_msg_close']) ? $siteseo->analaytics_settings['google_analytics_opt_out_msg_close'] : 'X';
		$edit_btn_msg = !empty($siteseo->analaytics_settings['google_analytics_opt_out_msg_edit']) ? $siteseo->analaytics_settings['google_analytics_opt_out_msg_edit'] : 'Manage cookies';
		$cookies_expir = !empty($siteseo->analaytics_settings['google_analytics_cb_exp_date']) ? $siteseo->analaytics_settings['google_analytics_cb_exp_date'] : '';
		$bar_postion = !empty($siteseo->analaytics_settings['google_analytics_cb_pos']) ? $siteseo->analaytics_settings['google_analytics_cb_pos'] : '' ;
		$bar_width = !empty($siteseo->analaytics_settings['google_analytics_cb_width']) ? $siteseo->analaytics_settings['google_analytics_cb_width'] : '';
		$display_backrop = !empty($siteseo->analaytics_settings['google_analytics_cb_backdrop']) ? true : '';

		// colors load 
		$backdrop_bg = !empty($siteseo->analaytics_settings['google_analytics_cb_backdrop_bg']) ? $siteseo->analaytics_settings['google_analytics_cb_backdrop_bg'] : '';
		$cookiebar_bg = !empty($siteseo->analaytics_settings['google_analytics_cb_bg']) ? $siteseo->analaytics_settings['google_analytics_cb_bg'] : '#ffffff';
		$cookiebar_bg_txt = !empty($siteseo->analaytics_settings['google_analytics_cb_txt_col']) ? $siteseo->analaytics_settings['google_analytics_cb_txt_col'] : '#000000';
		$cookiesbar_bg_lk = !empty($siteseo->analaytics_settings['google_analytics_cb_lk_col']) ? $siteseo->analaytics_settings['google_analytics_cb_lk_col'] : '#0073aa';
		$primary_btn_bg = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_bg']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_bg'] : '#0073aa';
		$primary_btn_bg_hov = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_bg']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_bg'] : '#ffffff';
		$primary_btn_txt = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_col']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_col'] : '#005177';
		$primary_btn_txt_hov = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_col']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_col'] : '#ffffff';
		$sec_btn_bg = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_sec_bg']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_sec_bg'] : '#cccccc';
		$sec_btn_bg_txt = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_sec_col']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_sec_col'] : '#000000';
		$sec_btn_bg_hov = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_sec_bg_hov']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_sec_bg_hov'] : '#aaaaaa';
		$sec_btn_txt_hov = !empty($siteseo->analaytics_settings['google_analytics_cb_btn_sec_col_hov']) ? $siteseo->analaytics_settings['google_analytics_cb_btn_sec_col_hov'] : '#000000';

		//position
		$position_class = '';
		$backdrop = false;
		switch(strtolower($bar_postion)){
			case 'middle':
				$position_class = 'siteseo-cookie-bar-middle';
				$backdrop = true;
				break;
			case 'top':
				$position_class = 'siteseo-cookie-bar-top';
				$backdrop = false;
				break;
			default:
				$position_class = 'siteseo-cookie-bar-bottom';
				$backdrop = false;
		}

		/* Translators: %s is the background color */
		$bar_styles = sprintf(
			'background-color: %s; color: %s;',
			esc_attr($cookiebar_bg),
			esc_attr($cookiebar_bg_txt)
		);

		
		$backdrop_html = '';
		if(!empty($display_backrop) && $backdrop){
			$backdrop_html = '<div id="siteseo-cookie-bar-backdrop" style="background-color:'.esc_attr($backdrop_bg).'" class="siteseo-cookie-bar-backdrop"></div>';
		}

		$css = '<style>
			#siteseo-cookie-bar-accept{
				--primary-btn-bg: '.esc_attr($primary_btn_bg).';
				--primary-btn-text: '.esc_attr($primary_btn_txt).';
				--primary-btn-hover-bg: '.esc_attr($primary_btn_bg_hov).';
				--primary-btn-hover-text: '.esc_attr($primary_btn_txt_hov).';
			}
			#siteseo-cookie-bar-close{
				--secondary-btn-bg: '.esc_attr($sec_btn_bg).';
				--secondary-btn-text: '.esc_attr($sec_btn_bg_txt).';
				--secondary-btn-hover-bg: '.esc_attr($sec_btn_bg_hov).';
				--secondary-btn-hover-text: '.esc_attr($sec_btn_txt_hov).';
			}
		</style>';
		
		$html = $backdrop_html;
		$html .= '<div id="siteseo-cookie-bar" class="siteseo-cookie-bar '.esc_attr($position_class).'" style="'.esc_attr($bar_styles).'" data-half-disable="'.esc_attr($auto_accept_cookies).'">
			<div class="siteseo-cookie-bar-content">
				<span>'.wp_kses_post($cookies_msg).'</span>
				<div class="siteseo-cookie-bar-buttons">
					<button id="siteseo-cookie-bar-accept" class="siteseo-cookie-bar-button siteseo-cookie-bar-primary-btn" style="background-color: '.esc_attr($primary_btn_bg).'; color: '.esc_attr($primary_btn_txt).'">
						'.esc_html($accept_btn_msg).'
					</button>
					<button id="siteseo-cookie-bar-close" class="siteseo-cookie-bar-button siteseo-cookie-bar-secondary-btn" style="background-color: '.esc_attr($sec_btn_bg).'; color: '.esc_attr($sec_btn_bg_txt).'">
						'.esc_html($close_btn_msg).'
					</button>
				</div>
			</div>
		</div>
		<button id="siteseo-cookie-bar-manage-btn" class="siteseo-cookie-bar-button siteseo-cookie-bar-primary-btn" style="background-color: '.$primary_btn_bg.'; color: '.esc_attr($primary_btn_txt).'">
			'.esc_html($edit_btn_msg).'
		</button>';
		
		echo wp_kses_post($html);
	}
	
	static function update_src_tag($tag, $handle, $src){
		global $siteseo;
		
		$tracking_handles = [
			'siteseo-gtag',
			'siteseo-matomo-tracking',
			'siteseo-microsoft-clarity',
			'siteseo-microsoft-clarity-js-after',
			'siteseo-ga-tracking'
		];
		
		if(!in_array($handle, $tracking_handles)){
			return $tag;
		}
		
		if(!empty($siteseo->analaytics_settings['google_analytics_disable']) && !isset($_COOKIE['siteseo-user-consent-accept']) && !isset($_COOKIE['siteseo-user-consent-close'])){
			$tag = str_replace(' src=', ' data-src-siteseo=', $tag);
		}
		
		return $tag;
	}
		
	static function process_script_src($scripts){
		global $siteseo;
		
		if(!empty($siteseo->analaytics_settings['google_analytics_disable']) && !isset($_COOKIE['siteseo-user-consent-accept']) && !isset($_COOKIE['siteseo-user-consent-close'])){
			$scripts = preg_replace('/(<script[^>]*) src=(["\'])(.*?)\\2/i', '$1 data-src-siteseo=$2$3$2', $scripts);
		}
    
		return $scripts;
	}
	
	static function add_custom_head_script(){
		global $siteseo;
        
		$scripts = $siteseo->analaytics_settings['google_analytics_other_tracking'];
       
		$scripts = self::process_script_src($scripts);
        
		echo wp_kses($scripts, [
			'script' => [
				'async' => [],
				'src' => [],
				'data-src-siteseo' => [],
				'type' => []
			]
		]);
	}
	
	static function add_custom_body_script(){
		global $siteseo;
		
		$scripts = $siteseo->analaytics_settings['google_analytics_other_tracking_body'];
		
		$scripts = self::process_script_src($scripts);
		
		echo wp_kses($scripts, [
			'script' => [
				'async' => [],
				'src' => [],
				'data-src-siteseo' => [],
				'type' => []
			]
		]);	
	}
	
	static function add_custom_footer_script(){
		global $siteseo;
		
		$scripts = $siteseo->analaytics_settings['google_analytics_other_tracking_footer'];
		
		$scripts = self::process_script_src($scripts);
		
		echo wp_kses($scripts, [
			'script' => [
				'async' => [],
				'src' => [],
				'data-src-siteseo' => [],
				'type' => []
			]
		]);
	}
	
	static function exclude_user_tracking(){
		global $siteseo;
	
		if(!is_user_logged_in()){
			return false;
		}

		$current_user = wp_get_current_user();
		if(!$current_user){
			return false;
		}

		$excluded_roles = isset($siteseo->analaytics_settings['google_analytics_roles']) ? $siteseo->analaytics_settings['google_analytics_roles'] : [];

		if(empty($excluded_roles)){
			return false;
		}

		foreach($current_user->roles as $user_role){
			if(isset($excluded_roles[$user_role])){
				return true;
			}
		}

		return false;
	}
	
	static function custom_dimensions(){
		global $siteseo;
		$dimensions = [];
		$settings = $siteseo->analaytics_settings;

		// Track Authors
		if(!empty($settings['track_authors']) && $settings['track_authors'] !== 'none'){
			if(is_singular()){
				$author_id = get_post_field('post_author', get_the_ID());
				$author_name = get_the_author_meta('display_name', $author_id);
				$dimensions[$settings['track_authors']] = $author_name;
			}
		}

		// Track Categories
		if(!empty($settings['track_categories']) && $settings['track_categories'] !== 'none'){
			if(is_singular()){
				$categories = get_the_category();
				if(!empty($categories)){
					$category_names = array_map(function($cat){
						return $cat->name;
					}, $categories);
					$dimensions[$settings['track_categories']] = implode(', ', $category_names);
				}
			}
		}

		// Track Tags
		if(!empty($settings['track_tags']) && $settings['track_tags'] !== 'none'){
			if(is_singular()){
				$tags = get_the_tags();
				if(!empty($tags)){
					$tag_names = array_map(function($tag){
						return $tag->name;
					}, $tags);
					$dimensions[$settings['track_tags']] = implode(', ', $tag_names);
				}
			}
		}

		// Track Post Types
		if(!empty($settings['track_post_types']) && $settings['track_post_types'] !== 'none'){
			if(is_singular()){
				$dimensions[$settings['track_post_types']] = get_post_type();
			}
		}

		// Track Logged In Users
		if(!empty($settings['track_user']) && $settings['track_user'] !== 'none'){
			if(is_user_logged_in()){
				$current_user = wp_get_current_user();
				$dimensions[$settings['track_user']] = $current_user->roles[0];
			}
		}

		return $dimensions;
		
	}
	
	static function matomo_tracking_code(){
		global $siteseo;
		
		if(self::exclude_user_tracking()){
			return;
		}
		
		$settings = $siteseo->analaytics_settings;
		
		$tracking_url = !empty($settings['google_analytics_matomo_id']) ? $settings['google_analytics_matomo_id'] : '';
		$site_id = !empty($settings['google_analytics_matomo_site_id']) ? $settings['google_analytics_matomo_site_id'] : '';
		$cross_domain = !empty($settings['google_analytics_matomo_cross_domain']) ? $settings['google_analytics_matomo_cross_domain'] : '';
		$do_not_track = !empty($settings['google_analytics_matomo_dnt']) ? $settings['google_analytics_matomo_dnt'] : '';
		$disable_cookies = !empty($settings['google_analytics_matomo_no_cookies']) ? $settings['google_analytics_matomo_no_cookies'] : '';
		$disable_heatmaps = !empty($settings['google_analytics_matomo_no_heatmaps']) ? $settings['google_analytics_matomo_no_heatmaps'] : '';
		$track_subdomains = !empty($settings['google_analytics_matomo_subdomains']) ? $settings['google_analytics_matomo_subdomains'] : '';
		$track_js_disabled = !empty($settings['google_analytics_matomo_no_js']) ? $settings['google_analytics_matomo_no_js'] : '';
		
		wp_register_script('siteseo-matomo-tracking', false, [], null, [
			'strategy' => true,
			'in_footer' => true,
		]);
		wp_enqueue_script('siteseo-matomo-tracking');
		
		add_filter('script_loader_tag', '\SiteSEO\GoogleAnalytics::update_src_tag', 10, 3);
			wp_add_inline_script('siteseo-matomo-tracking', "var _paq = _paq || [];
			_paq.push(['setSiteId', '".esc_html($site_id)."']);
			_paq.push(['setTrackerUrl', '".esc_html($tracking_url)."']);
            
		    if(".esc_html($cross_domain).") _paq.push(['enableCrossDomainLinking']);
		    if(".esc_html($do_not_track).") _paq.push(['setDoNotTrack', true]);
		    if(".esc_html($disable_cookies).") _paq.push(['disableCookies']);
		    if(".esc_html($disable_heatmaps).") _paq.push(['disableAllHeatmaps']);
		    if(".esc_html($track_subdomains).") _paq.push(['setDocumentTitle', document.domain + '/' + document.title]);

		    _paq.push(['trackPageView']);
		    _paq.push(['enableLinkTracking']);
            
		    (function(){
				var u=\"".esc_html($tracking_url)."\";
		        _paq.push(['setTrackerUrl', u + 'matomo.php']);
				_paq.push(['setSiteId', '".esc_html($site_id)."']);
		        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
		        g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
		    })();");

		if($track_js_disabled){
			echo '<noscript><img src="'.esc_url($tracking_url.'/matomo.php?idsite='.$site_id.'&rec=1').'" style="border:0" alt="" /></noscript>';
		}
	}
	
	
	static function add_tracking_script(){
		global $siteseo;
		
		if(self::exclude_user_tracking()){
			return;
		}

		echo '<script>
		document.addEventListener("DOMContentLoaded", function() {';

		if(!empty($siteseo->analaytics_settings['google_analytics_link_tracking_enable'])){
			echo 'document.querySelectorAll("a").forEach(function(link){
				if(link.hostname !== location.hostname){
					link.addEventListener("click", function(){
						gtag("event", "click", { "event_category": "External Link", "event_label": link.href });
					});
				}
			});';
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_download_tracking_enable']) && !empty($siteseo->analaytics_settings['google_analytics_download_tracking'])) {

			$fileExtensions = preg_replace('/\s+/', '', $siteseo->analaytics_settings['google_analytics_download_tracking']);
			$fileExtensionsPattern = str_replace('|', '|\\.', $fileExtensions); 

			echo 'document.querySelectorAll("a[href$=\'.' .esc_js(str_replace('|', '\'], a[href$=\'.', $fileExtensions)) . '\']").forEach(function(link) {
				link.addEventListener("click", function() {
					gtag("event", "download", { "event_category": "Download", "event_label": link.href });
				});
			});';
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_affiliate_tracking_enable']) && ! empty($siteseo->analaytics_settings['google_analytics_affiliate_tracking'])){
			$keywords = wp_json_encode( explode( ',', $siteseo->analaytics_settings['google_analytics_affiliate_tracking']));
			echo 'const keywords = '.esc_attr($keywords).';
			document.querySelectorAll("a").forEach(function(link){
				keywords.forEach(function(keyword){
					if(link.href.includes(keyword.trim())){
						link.addEventListener("click", function(){
							gtag("event", "click", { "event_category": "Affiliate/Outbound Link", "event_label": link.href });
						});
					}
				});
			});';
		}

		if(!empty($siteseo->analaytics_settings['google_analytics_phone_tracking'])){
			echo 'document.querySelectorAll("a[href^=\'tel:\']").forEach(function(link){
				link.addEventListener("click", function() {
					gtag("event", "click", { "event_category": "Telephone Link", "event_label": link.href });
				});
			});';
		}

		echo '});
		</script>';
	}
	
	static function tracking(){
		global $siteseo;
		
		if(self::exclude_user_tracking()){
			return;
		}
		
		$settings = $siteseo->analaytics_settings;

		$ga_id = !empty($settings['google_analytics_optimize']) ? $settings['google_analytics_optimize'] : '';
		$conversion_id = !empty($settings['google_analytics_ads']) ? $settings['google_analytics_ads'] : '';
		$optimize_id = !empty($settings['google_analytics_ads']) ? $settings['google_analytics_ads'] : '';
		$remarketing = !empty($settings['google_analytics_remarketing']) ? $settings['google_analytics_remarketing'] : '';
		$anonymize_ip = !empty($settings['google_analytics_ip_anonymization']) ? $settings['google_analytics_ip_anonymization'] : '';
		$enhanced_link = !empty($settings['google_analytics_link_attribution']) ? $settings['google_analytics_link_attribution'] : '';
		$cross_domain = !empty($settings['google_analytics_cross_domain']) ? $settings['google_analytics_cross_domain'] : '';
		$cross_domain_name = !empty($settings['google_analytics_remarketing']) ? $settings['google_analytics_remarketing'] : '';

		//custom dimensions
		$get_custom_dimensions = self::custom_dimensions();
		
		wp_enqueue_script('siteseo-gtag', 'https://www.googletagmanager.com/gtag/js?id=' . esc_attr($ga_id), [], SITESEO_VERSION, [
			'strategy' => 'async',
		]);
		
		add_filter('script_loader_tag', '\SiteSEO\GoogleAnalytics::update_src_tag', 10, 3);
		
		$gtag_config = [
			'anonymize_ip' => $anonymize_ip ? true : false,
			'link_attribution' => $enhanced_link ? true : false,
		];

		if($cross_domain && $cross_domain_name){
			$gtag_config['linker'] = [
				'domains' => [$cross_domain_name]
			];
		}

		// config
		foreach($get_custom_dimensions as $dimension => $value){
			$gtag_config[$dimension] = $value;
		}

		$inline_script = 'window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag("js", new Date());

		window.addEventListener("load", function (){
			var links = document.querySelectorAll("a");
			for(let i = 0; i < links.length; i++){
				links[i].addEventListener("click", function(e) {
					var n = this.href.includes("' . wp_parse_url(home_url(), PHP_URL_HOST) . '");
					if (n == false) {
						gtag("event", "click", {"event_category": "external links","event_label" : this.href});
					}
				});
			}
		});

		gtag("config", "'.esc_js($ga_id).'", '.wp_json_encode($gtag_config).');';

		if($optimize_id){
			$inline_script .= 'gtag("config", "'.esc_js($optimize_id).'");';
		}
		
		if($conversion_id){
			$inline_script .= 'gtag("config", "'.esc_js($conversion_id).'");';
		}
		
		if($remarketing){
			$inline_script .= 'gtag("set", "allow_google_signals", true);';
		}

		wp_add_inline_script('siteseo-gtag', $inline_script);
	}
	
	static function microsoft_clarity(){
		global $siteseo;

		if(self::exclude_user_tracking()){
			return;
		}

		$project_id = !empty($siteseo->analaytics_settings['google_analytics_clarity_project_id']) ? $siteseo->analaytics_settings['google_analytics_clarity_project_id'] : '';

		if(empty($project_id)){
			return;
		}
		
		wp_register_script('siteseo-microsoft-clarity', '', [], SITESEO_VERSION, true);
		
		wp_enqueue_script('siteseo-microsoft-clarity');

		$inline_script = "(function(c,l,a,r,i,t,y){ 
			c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
			t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;
			y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
			})(window, document, 'clarity', 'script', '".esc_js($project_id)."');";

		wp_add_inline_script('siteseo-microsoft-clarity', $inline_script);
		
		add_filter('script_loader_tag', '\SiteSEO\GoogleAnalytics::update_src_tag', 10, 3);
		
	}

	static function ga_tracking_code(){
		global $siteseo;
		
		if(self::exclude_user_tracking()){
			return;
		}

		$ga_id = isset($siteseo->analaytics_settings['google_analytics_ga4']) ? esc_attr($siteseo->analaytics_settings['google_analytics_ga4']) : '';
		
		if(empty($ga_id)){
			return;
		}

		wp_enqueue_script('siteseo-ga-tracking', 'https://www.googletagmanager.com/gtag/js?id=' . $ga_id, [], SITESEO_VERSION, true);
		
		add_filter('script_loader_tag', '\SiteSEO\GoogleAnalytics::add_async_attribute', 10, 2);
		
		$inline_script = "
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '{$ga_id}');
		";
		
		wp_add_inline_script('siteseo-ga-tracking', $inline_script);

	}
	
	static function add_async_attribute($tag, $handle){
		global $siteseo;
		if('siteseo-ga-tracking' === $handle){
       			
			if(!empty($siteseo->analaytics_settings['google_analytics_disable'])){
				if(isset($_COOKIE['siteseo-user-consent-accept']) && $_COOKIE['siteseo-user-consent-accept'] === 'true'){
					return str_replace(' src', ' async src', $tag);
				} else{
					return str_replace(' src', ' data-src-siteseo', $tag);
				}
			}
		}
		
		return $tag;
	}
	
	/** TODO:: temporary in this file*/	
	 static function handle_custom_redirect(){
		global $post;

		// post types and taxonomies
		$post_types = siteseo_post_types();
		$taxonomies = get_taxonomies(array('public' => true), 'objects');


		$is_singular = false;
		foreach($post_types as $post_type){
			if(is_singular($post_type->name)){
				$is_singular = true;
				break;
			}
		}

		$is_taxonomy = false;
		foreach($taxonomies as $taxonomy){
			if(is_tax($taxonomy->name)){
				$is_taxonomy = true;
				break;
			}
		}

		if($is_singular || is_404() || $is_taxonomy || is_category() || is_tag()){
			if(is_404()){
				$args = array(
					'post_type' => 'siteseo_404',
					'posts_per_page' => 1,
					'meta_query' => array(
						array(
							'key' => '_siteseo_redirections_enabled',
							'value' => 'yes',
							'compare' => '='
						)
					)
				);
				$redirect_posts = get_posts($args);

				if(empty($redirect_posts)){
					return;
				}
				$post = $redirect_posts[0];
			}

			// Taxonomy archives
			if($is_taxonomy || is_category() || is_tag()){
				$term = get_queried_object();
				$term_id = $term->term_id;

				// Check redirection is enabled
				$enable_redirect = get_term_meta($term_id, '_siteseo_redirections_enabled', true);

				if(empty($enable_redirect)){
					return;
				}

				$login_status = get_term_meta($term_id, '_siteseo_redirections_logged_status', true);
				$redirect_type = get_term_meta($term_id, '_siteseo_redirections_type', true);
				$redirect_url = get_term_meta($term_id, '_siteseo_redirections_value', true);
				$param_handling = get_term_meta($term_id, '_siteseo_redirections_param', true);
			}
			// Singular posts, pages, and products
			else{
				$enable_redirect = get_post_meta($post->ID, '_siteseo_redirections_enabled', true);

				if(empty($enable_redirect)){
					return;
				}

				$login_status = get_post_meta($post->ID, '_siteseo_redirections_logged_status', true);
				$redirect_type = get_post_meta($post->ID, '_siteseo_redirections_type', true);
				$redirect_url = get_post_meta($post->ID, '_siteseo_redirections_value', true);
				$param_handling = get_post_meta($post->ID, '_siteseo_redirections_param', true);
			}

			if($login_status === 'only_logged_in' && !is_user_logged_in()){
				return;
			}

			if($login_status === 'only_not_logged_in' && is_user_logged_in()){
				return;
			}

			if(!empty($redirect_url)){
				$final_url = $redirect_url;
				
				if(is_404() && !empty($_SERVER['QUERY_STRING'])){
					switch($param_handling){
						case 'exact_match':
							$current_params = sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING']));
							$redirect_params = wp_parse_url($redirect_url, PHP_URL_QUERY);
							if($current_params !== $redirect_params){
								return;
							}
							break;
							
						case 'without_param':
							$final_url = strtok($redirect_url, '?');
							break;
							
						case 'with_ignored_param':
							$query_string = sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING']));
							$final_url = $redirect_url;
							if(!empty($query_string)){
								$final_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . $query_string;
							}
							break;
					}
				}

				$status_code = !empty($redirect_type) ? intval($redirect_type) : 301;
				
				if(in_array($status_code, [410, 451]) && is_404()){
					status_header($status_code);
					nocache_headers();
					include(get_query_template('404'));
					exit;
				}

				wp_redirect($final_url, $status_code);
				exit;
			}
		}
	}
}
