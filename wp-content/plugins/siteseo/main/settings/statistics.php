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

class Statistics{

	static function init(){
		global $siteseo;

		$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'tab_dashbord';

		wp_enqueue_script('siteseo-chart-js', SITESEO_ASSETS_URL . '/js/chart.umd.min.js', [], SITESEO_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
		wp_enqueue_script('siteseo-gsc-charts', SITESEO_ASSETS_URL . '/js/gsc-charts.js', ['jquery', 'siteseo-chart-js'], SITESEO_VERSION, ['strategy' => 'defer', 'in_footer' => true]);

		$saved_data = get_option('siteseo_search_console_data', []);
		wp_localize_script('siteseo-gsc-charts', 'siteseo_chart_data', $saved_data);

		if(isset($_GET['siteseo_auth_code']) && class_exists('\SiteSEOPro\GoogleConsole') && method_exists('\SiteSEOPro\GoogleConsole', 'generate_tokens')){
			\SiteSEOPro\GoogleConsole::generate_tokens();
		}

		if(isset($_GET['siteseo_auth_code']) && class_exists('\SiteSEOPro\GoogleConsole')){
			add_action('admin_footer', '\SiteSEO\Settings\Statistics::connect_site_dialogbox');
		}

		$site_connected = false;
		if(class_exists('\SiteSEOPro\GoogleConsole') && method_exists('\SiteSEOPro\GoogleConsole', 'is_connected')){
			$site_connected = \SiteSEOPro\GoogleConsole::is_connected();
		}

		$statistics_subtabs = [
			'tab_dashbord' => esc_html__('Dashboard', 'siteseo'),
			'tab_seo_statistics' => esc_html__('Site Search Traffic', 'siteseo'),
			'tab_keyword_rank' => esc_html__('Keyword Rank Tracker', 'siteseo'),
			'tab_content_ranking' => esc_html__('Content Ranking', 'siteseo'),
			'tab_audience' => esc_html__('Audience Overview', 'siteseo'),
		];

		echo'<div id="siteseo-root" class="siteseo-search-console">';
		Util::admin_header();

		$show_sample_data = isset($_GET['sample_data']) && $_GET['sample_data'] === '1';

		if(!class_exists('\SiteSEOPro\GoogleConsole') && !$show_sample_data){
			echo'<div class="siteseo-blur-overlay"></div>
			<div class="siteseo-pro-notice-center">
				<div class="siteseo-pro-notice-content">
					<span class="dashicons dashicons-lock siteseo-lock-icon"></span>
					<h2>'.esc_html__('Search Console Pro Feature', 'siteseo').'</h2>
					<p>'.esc_html__('Upgrade to PRO to unlock Google Search Console integration and access real-time search analytics data.', 'siteseo').'</p>
					<div class="siteseo-option siteseo-pro-notice-buttons">
					<a href="https://siteseo.io/pricing" class="siteseo-option btnPrimary" target="_blank">' . esc_html__('Buy Pro', 'siteseo') . '</a>
					<a href="'.esc_url(add_query_arg('sample_data', '1')).'" class="siteseo-option btnSecondary">'.esc_html__('Explore Sample Data', 'siteseo').'</a>
				</div>
			</div>
			</div>';
		} elseif(class_exists('\SiteSEOPro\GoogleConsole') && !$show_sample_data && empty($site_connected)){
			echo'<div class="siteseo-blur-overlay"></div>
			<div class="siteseo-pro-notice-center">
				<div class="siteseo-pro-notice-content">
					<span class="dashicons dashicons-admin-links siteseo-link-icon"></span>
					<h2>'.esc_html__('Search Console statistics', 'siteseo').'</h2>
					<p>'.esc_html__('Please connect your Google Search Console account to unlock real-time search analytics data.', 'siteseo').'</p>
					<div class="siteseo-option siteseo-pro-notice-buttons">
					<form method="post">';
					wp_nonce_field('siteseo_pro_connect_google');
					echo '<input type="hidden" name="redirect_type" value="settings">
						<button type="submit" name="siteseo_pro_connect_btn" class="siteseo-option btnPrimary siteseo-connect-btn">'.esc_html__('Connect Search Console', 'siteseo') .'</button>
					</form>
					<a href="'.esc_url(add_query_arg('sample_data', '1')).'" class="siteseo-option btnSecondary">'.esc_html__('Explore Sample Data', 'siteseo').'</a>
				</div>
			</div>
			</div>';
		}

		echo'<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">
		<div class="siteseo-toggle-cnt"><span id="siteseo-tab-title"><strong>'.esc_html__('Google Search Console Statistics', 'siteseo').'</strong></span></div>';

		if(!empty($site_connected)){

			if(class_exists('\SiteSEOPro\GoogleConsole') && method_exists('\SiteSEOPro\GoogleConsole', 'get_site_url')){
				$site_url = \SiteSEOPro\GoogleConsole::get_site_url();
			}

			if(!empty($site_url)){
				echo'<div class="siteseo-statistics-wrapper">
					<span class="siteseo-statistics-sites">Site: '.(strpos($site_url, 'sc-domain:') === 0 ? sanitize_text_field($site_url) : esc_url($site_url)).' <span id="siteseo-refresh-search-stats" class="dashicons dashicons-update" title="'.esc_attr__('Update Stats', 'siteseo').'"></span></span>
					<span class="siteseo-statistics-disconnect"><span class="dashicons dashicons-migrate"></span>'.esc_html__('Disconnect', 'siteseo').'</span>
					<span class="siteseo-statistics-data-range">'.esc_html__('[Last 90 days data]', 'siteseo').'</span>
				</div>';
			}
		}

		echo'<div id="siteseo-tabs" class="wrap">
		<div class="siteseo-nav-tab-wrapper">';

		foreach($statistics_subtabs as $tab_key => $tab_caption){
			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo'<a id="' . esc_attr($tab_key) . '-tab" class="siteseo-nav-tab' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab_key) . '">' . esc_html($tab_caption) . '</a>';
		}

		echo'</div>
		<div class="tab-content-wrapper">
		<div class="siteseo-tab' .($current_tab == 'tab_dashbord' ? ' active' : '').'" id="tab_dashbord" style="display: none;">';
		self::dashbord_tab();
		echo'</div>
		<div class="siteseo-tab' .($current_tab == 'tab_seo_statistics' ? ' active' : '').'" id="tab_seo_statistics" style="display: none;">';
		self::seo_statistics_tab();
		echo'</div>
		<div class="siteseo-tab' .($current_tab == 'tab_keyword_rank' ? ' active' : '').'" id="tab_keyword_rank" style="display: none;">';
		self::keyword_ranking_tab();
		echo'</div>
		<div class="siteseo-tab' .($current_tab == 'tab_content_ranking' ? ' active' : '').'" id="tab_content_ranking" style="display: none;">';
		self::content_ranking_tab();
		echo'</div>
		<div class="siteseo-tab '.($current_tab =='tab_audience' ? ' active' : '').'" id="tab_audience" style="display:none;">';
		self::audience_tab();
		echo'</div>
		</div>';
		echo'</form></div>';
	}

	static function fetch_data(){
		$is_connected = '';

		if(class_exists('\SiteSEOPro\GoogleConsole') && method_exists('\SiteSEOPro\GoogleConsole', 'is_connected')){
			$is_connected = \SiteSEOPro\GoogleConsole::is_connected();
		}

		$analytics_data = get_option('siteseo_search_console_data', []);

		if(empty($analytics_data) && empty($is_connected)){
			// Show sample data when not connected
			return [
				'metrics' => self::sample_metrics_data(),
				'top_pages' => self::sample_top_pages(),
				'top_loss_pages' => array_slice(self::sample_top_pages(), 0, 3), // Sample loss pages
				'top_winning_pages' => array_slice(self::sample_top_pages(), 3, 3), // Sample winning pages
				'keywords' => self::sample_keywords(),
				'top_winning_keywords' => array_slice(self::sample_keywords(), 0, 3), // Sample winning keywords
				'top_loss_keywords' => array_slice(self::sample_keywords(), 3, 2), // Sample loss keywords
				'content_ranking' => self::sample_content_ranking(),
				'country_data' => self::sample_country_data(), 
				'device_data' => self::sample_device_data(),
				'is_sample' => true
			];
		}
		
		return [
			'metrics' => isset($analytics_data['metrics']) ? $analytics_data['metrics'] : [
				'impressions' => [
					'current' => '0',
					'change' => '0',
					'trend' => 'neutral',
					'chart_data' => []
				],
				'clicks' => [
					'current' => '0',
					'change' => '0',
					'trend' => 'neutral',
					'chart_data' => []
				],
				'ctr' => [
					'current' => '0%',
					'change' => '0',
					'trend' => 'neutral',
					'chart_data' => []
				],
				'position' => [
					'current' => '0',
					'change' => '0',
					'trend' => 'neutral',
					'chart_data' => []
				]
			],
			'top_pages' => isset($analytics_data['top_pages']) ? array_slice($analytics_data['top_pages'], 0, 5) : [],
			'top_loss_pages' => isset($analytics_data['top_loss_pages']) ? array_slice($analytics_data['top_loss_pages'], 0, 5) : [],
			'top_winning_pages' => isset($analytics_data['top_winning_pages']) ? array_slice($analytics_data['top_winning_pages'], 0, 5) : [],
			'keywords' => isset($analytics_data['top_keywords']) ? array_slice($analytics_data['top_keywords'], 0, 5) : [],
			'top_winning_keywords' => isset($analytics_data['top_winning_keywords']) ? array_slice($analytics_data['top_winning_keywords'], 0, 5) : [],
			'top_loss_keywords' => isset($analytics_data['top_loss_keywords']) ? array_slice($analytics_data['top_loss_keywords'], 0, 5) : [],
			'content_ranking' => isset($analytics_data['content_ranking']) ? $analytics_data['content_ranking'] : [],
			'country_data' => isset($analytics_data['country_audience']) ? $analytics_data['country_audience'] : [],
			'device_data' => isset($analytics_data['device_audience']) ? $analytics_data['device_audience'] : [],
			'is_sample' => false
		];
	}

	static function dashbord_tab(){	
		self::connect_notices();

		$data = self::fetch_data();
		$metrics = $data['metrics'];
		$top_pages = $data['top_pages'];
		$top_loss_pages = $data['top_loss_pages'];
		$top_winning_pages = $data['top_winning_pages'];
		$keywords = $data['keywords'];
		$top_winning_keywords = $data['top_winning_keywords'];
		$top_loss_keywords = $data['top_loss_keywords'];

		echo'<div class="siteseo-stats-container">
			<h2 class="siteseo-stats-title">'.esc_html__('Search Performance', 'siteseo').'</h2>
			<hr class="siteseo-stats-separator">

			<div class="siteseo-dashboard-grid">
				<div class="siteseo-metric-card">
					<div class="siteseo-metric-header">
						<span class="siteseo-metric-title">'.esc_html__('Search Impressions', 'siteseo').'</span>
					</div>
					
					<div class="siteseo-metric-value-row">
						<span class="siteseo-metric-value">'.esc_html($metrics['impressions']['current']).'</span>
							<span class="siteseo-metric-change '.($metrics['impressions']['trend'] == 'negative' ? 'siteseo-change-negative' : ($metrics['impressions']['trend'] == 'positive' ? 'siteseo-change-positive' : '')).'">
								'.($metrics['impressions']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['impressions']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
								'.esc_html($metrics['impressions']['change']).'
							</span>
					</div>
						
					<div class="siteseo-chart-container">
						<canvas id="siteseo_impressions_chart" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
					</div>
				</div>

					<div class="siteseo-metric-card">
						<div class="siteseo-metric-header">
							<span class="siteseo-metric-title">'.esc_html__('Total Clicks', 'siteseo').'</span>
						</div>
						
						<div class="siteseo-metric-value-row">
							<span class="siteseo-metric-value">'.esc_html($metrics['clicks']['current']).'</span>
							<span class="siteseo-metric-change '.($metrics['clicks']['trend'] == 'negative' ? 'siteseo-change-negative' : ($metrics['clicks']['trend'] == 'positive' ? 'siteseo-change-positive' : '')).'">
								'.($metrics['clicks']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['clicks']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
								'.esc_html($metrics['clicks']['change']).'
							</span>
						</div>
						
						<div class="siteseo-chart-container">
							<canvas id="siteseo_clicks_chart" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
						</div>
					</div>

					<div class="siteseo-metric-card">
						<div class="siteseo-metric-header">
							<span class="siteseo-metric-title">'.esc_html__('Avg. CTR', 'siteseo').'</span>
						</div>
						<div class="siteseo-metric-value-row">
							<span class="siteseo-metric-value">'.esc_html($metrics['ctr']['current']).'</span>
							<span class="siteseo-metric-change '.($metrics['ctr']['trend'] == 'negative' ? 'siteseo-change-negative' : ($metrics['ctr']['trend'] == 'positive' ? 'siteseo-change-positive' : '')).'">
								'.($metrics['ctr']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['ctr']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
								'.esc_html($metrics['ctr']['change']).'
							</span>
						</div>
						<div class="siteseo-chart-container">
							<canvas id="siteseo_ctr_chart" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
						</div>
					</div>

					<div class="siteseo-metric-card">
						<div class="siteseo-metric-header">
							<span class="siteseo-metric-title">'.esc_html__('Avg. Position', 'siteseo').'</span>
						</div>
						<div class="siteseo-metric-value-row">
							<span class="siteseo-metric-value">'.esc_html($metrics['position']['current']).'</span>
							<span class="siteseo-metric-change '.($metrics['position']['trend'] == 'negative' ? 'siteseo-change-negative' : ($metrics['position']['trend'] == 'positive' ? 'siteseo-change-positive' : '')).'">
								'.($metrics['position']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['position']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
								'.esc_html($metrics['position']['change']).'
							</span>
						</div>
						<div class="siteseo-chart-container">
							<canvas id="siteseo_position_chart" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
						</div>
					</div>
				</div>
			</div>';
		
		if(!empty($top_pages) || $data['is_sample']){

			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Content Performance ( Top 5 )', 'siteseo').'</h2>
				<div class="siteseo-inner-tabs-wrap">
					<input type="radio" id="siteseo-statistics-top-page" name="siteseo-inner-tabs-pages" checked>
					<input type="radio" id="siteseo-statistics-top-loss" name="siteseo-inner-tabs-pages">
					<input type="radio" id="siteseo-statistics-top-winning" name="siteseo-inner-tabs-pages">
					
					<ul class="siteseo-inner-tabs">
						<li class="siteseo-inner-tab"><label for="siteseo-statistics-top-page">'.esc_html__('Top Pages', 'siteseo').'</label></li>
						<li class="siteseo-inner-tab"><label for="siteseo-statistics-top-loss">'.esc_html__('Top loss', 'siteseo').'</label></li>
						<li class="siteseo-inner-tab"><label for="siteseo-statistics-top-winning">'.esc_html__('Top winning', 'siteseo').'</label></li>
					</ul>
						
					<div class="siteseo-inner-tab-content">	
						<table class="wp-list-table widefat fixed striped siteseo-history-table">
							<thead><tr>
								<th>'.esc_html__('Title', 'siteseo').'</th>
								<th>'.esc_html__('Score', 'siteseo').'</th>
								<th>'.esc_html__('Indexed', 'siteseo').'</th>
								<th>'.esc_html__('Clicks', 'siteseo').'</th>
								<th>'.esc_html__('Impressions', 'siteseo').'</th>
								<th>'.esc_html__('Position', 'siteseo').'</th>
								<th>'.esc_html__('Diff', 'siteseo').'</th>
							</tr>
							</thead>
						<tbody>';

						foreach($top_pages as $page){
							
							$score = $page['truseo_score'];
							
							if($score >= 80){
								$badge_class = 'siteseo-gsc-score-good';
							} elseif ($score >= 50){
								$badge_class = 'siteseo-gsc-score-avg';
							} else{
								$badge_class = 'siteseo-gsc-score-bad';
							}
							
							echo'<tr>
								<td>'.esc_html($page['title']).'</td>
								<td><span class="'.esc_attr($badge_class).'">'.esc_html($page['truseo_score']).'/100</span></td>
								<td><span class="dashicons '.($page['indexed'] ? 'dashicons-yes-alt siteseo-statistics-index-icon' : 'dashicons-dismiss siteseo-statistics-noindex-icon').'"></span></td>
								<td>'.esc_html($page['clicks']).'</td>
								<td>'.esc_html($page['impressions']).'</td>
								<td>'.esc_html($page['position']).'</td>
								<td style="color:'.(isset($page['diff']) && strpos($page['diff'], '+') === 0 ? '#28a745' : '#dc3545').';">'.esc_html($page['diff']).'</td>
							</tr>';
						}
						
						if(empty($top_pages) && !$data['is_sample']){
							echo '<tr><td colspan="7" style="text-align:center;">'.esc_html__('No data available yet', 'siteseo').'</td></tr>';
						}
						
						echo'</tbody></table>
					</div>
					
					<div class="siteseo-inner-tab-content">
						 <table class="wp-list-table widefat fixed striped siteseo-history-table">
							<thead><tr>
								<th>'.esc_html__('Title', 'siteseo').'</th>
								<th>'.esc_html__('Score', 'siteseo').'</th>
								<th>'.esc_html__('Indexed', 'siteseo').'</th>
								<th>'.esc_html__('Clicks', 'siteseo').'</th>
								<th>'.esc_html__('Impressions', 'siteseo').'</th>
								<th>'.esc_html__('Position', 'siteseo').'</th>
								<th>'.esc_html__('Diff', 'siteseo').'</th>
							</tr>
							</thead>
						<tbody>';

						// Use top_loss_pages instead of top_pages
						foreach($top_loss_pages as $page){
							
							$score = $page['truseo_score'];
							
							if($score >= 80){
								$badge_class = 'siteseo-gsc-score-good';
							} elseif($score >= 50){
								$badge_class = 'siteseo-gsc-score-avg';
							} else{
								$badge_class = 'siteseo-gsc-score-bad';
							}
							
							echo'<tr>
								<td>'.esc_html($page['title']).'</td>
								<td><span class="'.esc_attr($badge_class).'">'.esc_html($page['truseo_score']).'/100</span></td>
								<td><span class="dashicons '.($page['indexed'] ? 'dashicons-yes-alt siteseo-statistics-index-icon' : 'dashicons-dismiss siteseo-statistics-noindex-icon').'"></span></td>
								<td>'.esc_html($page['clicks']).'</td>
								<td>'.esc_html($page['impressions']).'</td>
								<td>'.esc_html($page['position']).'</td>
								<td style="color:#dc3545;">'.esc_html($page['diff']).'</td>
							</tr>';
						}
						
						if(empty($top_loss_pages) && !$data['is_sample']){
							echo '<tr><td colspan="7" style="text-align:center;">'.esc_html__('No loss pages data available', 'siteseo').'</td></tr>';
						}

						echo'</tbody></table>
					</div>
					
					<div class="siteseo-inner-tab-content">
						 <table class="wp-list-table widefat fixed striped siteseo-history-table">
							<thead><tr>
								<th>'.esc_html__('Title', 'siteseo').'</th>
								<th>'.esc_html__('Score', 'siteseo').'</th>
								<th>'.esc_html__('Indexed', 'siteseo').'</th>
								<th>'.esc_html__('Clicks', 'siteseo').'</th>
								<th>'.esc_html__('Impressions', 'siteseo').'</th>
								<th>'.esc_html__('Position', 'siteseo').'</th>
								<th>'.esc_html__('Diff', 'siteseo').'</th>
							</tr>
							</thead>
						<tbody>';

						// Use top_winning_pages instead of top_pages
						foreach($top_winning_pages as $page){
							$score = $page['truseo_score'];
							if($score >= 80){
								$badge_class = 'siteseo-gsc-score-good';
							} elseif($score >= 50){
								$badge_class = 'siteseo-gsc-score-avg';
							} else{
								$badge_class = 'siteseo-gsc-score-bad';
							}
							
							echo'<tr>
								<td>'.esc_html($page['title']).'</td>
								<td><span class="'.esc_attr($badge_class).'">'.esc_html($page['truseo_score']).'/100</span></td>
								<td><span class="dashicons '.($page['indexed'] ? 'dashicons-yes-alt siteseo-statistics-index-icon' : 'dashicons-dismiss siteseo-statistics-noindex-icon').'"></span></td>
								<td>'.esc_html($page['clicks']).'</td>
								<td>'.esc_html($page['impressions']).'</td>
								<td>'.esc_html($page['position']).'</td>
								<td style="color:#28a745;">'.esc_html($page['diff']).'</td>
							</tr>';
						}
						
						if(empty($top_winning_pages) && !$data['is_sample']){
							echo '<tr><td colspan="7" style="text-align:center;">'.esc_html__('No winning pages data available', 'siteseo').'</td></tr>';
						}
						
						echo'</tbody></table>
					</div>
				</div>
			</div>';
		}
		
		if(!empty($keywords) || $data['is_sample']){
			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Keyword Rankings ( Top 5 )', 'siteseo').'</h2>

				<div class="siteseo-inner-tabs-wrap">
					<input type="radio" id="siteseo-statistics-top-keywords" name="siteseo-inner-tabs-keywords" checked>
					<input type="radio" id="siteseo-statistics-winning-keywords" name="siteseo-inner-tabs-keywords">
					<input type="radio" id="siteseo-statistics-loss-keywords" name="siteseo-inner-tabs-keywords">

					<ul class="siteseo-inner-tabs">
						<li class="siteseo-inner-tab"><label for="siteseo-statistics-top-keywords">'.esc_html__('Top Keywords', 'siteseo').'</label></li>
						<li class="siteseo-inner-tab"><label for="siteseo-statistics-winning-keywords">'.esc_html__('Top Winning', 'siteseo').'</label></li>
						<li class="siteseo-inner-tab"><label for="siteseo-statistics-loss-keywords">'.esc_html__('Top LOSS', 'siteseo').'</label></li>
					</ul>
						
				<div class="siteseo-inner-tab-content">	

					<table class="wp-list-table widefat fixed striped siteseo-history-table">
						<thead>
							<tr>
								<th>'.esc_html__('Keyword', 'siteseo').'</th>
								<th>'.esc_html__('Clicks', 'siteseo').'</th>
								<th>'.esc_html__('Impressions', 'siteseo').'</th>
								<th>'.esc_html__('CTR', 'siteseo').'</th>
								<th>'.esc_html__('Position', 'siteseo').'</th>
							</tr>
						</thead>
						<tbody>';
						
						foreach($keywords as $keyword){
							echo'<tr>
								<td class="siteseo-table-row">'.esc_html($keyword['keyword']).'</td>
								<td style="font-weight:bold;">'.esc_html($keyword['clicks']).'</td>
								<td>'.esc_html($keyword['impressions']).'</td>
								<td>'.esc_html($keyword['ctr']).'</td>
								<td>'.esc_html($keyword['position']).'</td>
							</tr>';
						}
						
						if(empty($keywords) && !$data['is_sample']){
							echo'<tr><td colspan="5" style="text-align:center;">'.esc_html__('No data available yet', 'siteseo').'</td></tr>';
						}
						
						echo'</tbody>
					</table>
				</div>
				<div class="siteseo-inner-tab-content">
					<table class="wp-list-table widefat fixed striped siteseo-history-table">
						<thead>
							<tr>
								<th>'.esc_html__('Keyword', 'siteseo').'</th>
								<th>'.esc_html__('Points', 'siteseo').'</th>
								<th>'.esc_html__('Clicks', 'siteseo').'</th>
								<th>'.esc_html__('Position', 'siteseo').'</th>
							</tr>
						</thead>
						<tbody>';
						
						// Use top_winning_keywords instead of keywords
						foreach($top_winning_keywords as $keyword){
							echo'<tr>
								<td class="siteseo-table-row">'.esc_html($keyword['keyword']).'</td>
								<td style="color:#28a745;font-weight:bold;">'.esc_html($keyword['points']).'</td>
								<td>'.esc_html($keyword['clicks']).'</td>
								<td>'.esc_html($keyword['position']).'</td>
							</tr>';
						}
						
						if(empty($top_winning_keywords) && !$data['is_sample']){
							echo'<tr><td colspan="4" style="text-align:center;">'.esc_html__('No winning keywords data available', 'siteseo').'</td></tr>';
						}

						echo'</tbody>
					</table>
				</div>

				<div class="siteseo-inner-tab-content">
					<table class="wp-list-table widefat fixed striped siteseo-history-table">
						<thead>
							<tr>
								<th>'.esc_html__('Keyword', 'siteseo').'</th>
								<th>'.esc_html__('Points', 'siteseo').'</th>
								<th>'.esc_html__('Clicks', 'siteseo').'</th>
								<th>'.esc_html__('Position', 'siteseo').'</th>	   
							</tr>
						</thead>
						<tbody>';

						// Use top_loss_keywords instead of keywords
						foreach($top_loss_keywords as $keyword){
							echo'<tr>
								<td class="siteseo-table-row">'.esc_html($keyword['keyword']).'</td>
								<td style="color:#dc3545;font-weight:bold;">'.esc_html($keyword['points']).'</td>
								<td>'.esc_html($keyword['clicks']).'</td>
								<td>'.esc_html($keyword['position']).'</td>
							</tr>';
						}

						if(empty($top_loss_keywords) && !$data['is_sample']){
							echo'<tr><td colspan="4" style="text-align:center;">'.esc_html__('No loss keywords data available', 'siteseo').'</td></tr>';
						}

						echo'</tbody>
					</table>
				</div>
				</div>
			</div>';
		}
	}

	static function seo_statistics_tab(){
		self::connect_notices();

		$data = self::fetch_data();
		$metrics = $data['metrics'];
		$top_pages = $data['top_pages'];
		
		echo'<div class="siteseo-stats-container">
			<h2 class="siteseo-stats-title">'.esc_html__('Site Search Traffic', 'siteseo').'</h2>
			<hr class="siteseo-stats-separator">
			<ul class="siteseo-stats-list">
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Search Impressions', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($metrics['impressions']['current']).'</span>
						<span class="siteseo-stat-change '.($metrics['impressions']['trend'] == 'negative' ? 'negative' : ($metrics['impressions']['trend'] == 'positive' ? 'positive' : '')).'">
							'.($metrics['impressions']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['impressions']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
							'.esc_html($metrics['impressions']['change']).'
						</span>
					</div>
				</li>
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Total Clicks', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($metrics['clicks']['current']).'</span>
						<span class="siteseo-stat-change '.($metrics['clicks']['trend'] == 'negative' ? 'negative' : ($metrics['clicks']['trend'] == 'positive' ? 'positive' : '')).'">
							'.($metrics['clicks']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['clicks']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
							'.esc_html($metrics['clicks']['change']).'
						</span>
					</div>
				</li>
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Avg. CTR', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($metrics['ctr']['current']).'</span>
						<span class="siteseo-stat-change '.($metrics['ctr']['trend'] == 'negative' ? 'negative' : ($metrics['ctr']['trend'] == 'positive' ? 'positive' : '')).'">
							'.($metrics['ctr']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-'.($metrics['ctr']['trend'] == 'negative' ? 'down' : 'up').'"></span>' : '').'
							'.esc_html($metrics['ctr']['change']).'
						</span>
					</div>
				</li>
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Avg. Position.', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($metrics['position']['current']).'</span>
						<span class="siteseo-stat-change '.($metrics['position']['trend'] == 'negative' ? 'negative' : ($metrics['position']['trend'] == 'positive' ? 'positive' : '')) . '">
						'.($metrics['position']['trend'] != 'neutral' ? '<span class="dashicons dashicons-arrow-' . ($metrics['position']['trend'] == 'negative' ? 'down' : 'up') . '"></span>' : ''
							) . '
							'.esc_html($metrics['position']['change']).'
						</span>
					</div>
				</li>
			</ul>
		   <canvas id="seo_statistics" width="950" height="250" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
		</div>';
		
		if(!empty($top_pages) || $data['is_sample']){
			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Content Analysis ( Top 5 )', 'siteseo').'</h2>	
				<table class="wp-list-table widefat fixed striped siteseo-history-table">
					<thead><tr>
						<th>'.esc_html__('Page', 'siteseo').'</th>
						<th>'.esc_html__('Status', 'siteseo').'</th>
						<th>'.esc_html__('Clicks', 'siteseo').'</th>
						<th>'.esc_html__('Avg position', 'siteseo').'</th>
						<th>'.esc_html__('Impressions', 'siteseo').'</th>
						<th>'.esc_html__('Content Score', 'siteseo').'</th>
					</tr>
					</thead>
				<tbody>';
				
				foreach($top_pages as $page){
					
					$score = $page['truseo_score'];
					if($score >= 80){
						$badge_class = 'siteseo-gsc-score-good';
					} elseif ($score >= 50){
						$badge_class = 'siteseo-gsc-score-avg';
					} else{
						$badge_class = 'siteseo-gsc-score-bad';
					}
						
					echo'<tr>
						<td>'.esc_html($page['title']).'</td>
						<td>'.(isset($page['indexed']) ? ($page['indexed'] ? '<span class="dashicons dashicons-yes-alt" style="color:#28a745;"></span>' : '<span class="dashicons dashicons-dismiss" style="color:#dc3545;"></span>') : '<span class="dashicons dashicons-editor-help" style="color:#6c757d;"></span>').'</td>
						<td>'.esc_html($page['clicks']).'</td>
						<td>'.esc_html($page['position']).'</td>
						<td>'.esc_html($page['impressions']).'</td>
						<td><span class="'.esc_attr($badge_class).'">'.esc_html($page['truseo_score']).'/100</span></td>
					</tr>';
				}
				
				if(empty($top_pages) && !$data['is_sample']){
					echo'<tr><td colspan="6" style="text-align:center;">'.esc_html__('No data available yet', 'siteseo').'</td></tr>';
				}
				
				echo'</tbody></table>
			</div>';
		}
	}
		
	static function keyword_ranking_tab(){
		self::connect_notices();
	
		$data = self::fetch_data();
		$keywords = $data['keywords'];

		// Get actual analytics data if available
		$analytics_data = get_option('siteseo_search_console_data', []);
		$has_actual_data = !empty($analytics_data) && !isset($analytics_data['error']);
	
		// Prepare values based on actual data or sample
		if($has_actual_data && !$data['is_sample']){
			// Use actual data
			$total_keywords = isset($analytics_data['top_keywords']) ? count($analytics_data['top_keywords']) : 0;
			$total_impressions = isset($analytics_data['metrics']['impressions']['current']) ? $analytics_data['metrics']['impressions']['current'] : '0';
			$avg_ctr = isset($analytics_data['metrics']['ctr']['current']) ? $analytics_data['metrics']['ctr']['current'] : '0%';
			
			$impression_change = isset($analytics_data['metrics']['impressions']['change']) ? $analytics_data['metrics']['impressions']['change'] : '0';
			$impression_trend = isset($analytics_data['metrics']['impressions']['trend']) ? $analytics_data['metrics']['impressions']['trend'] : 'neutral';
			
			$ctr_change = '0';
			$ctr_trend = 'neutral';
			
		} else{
			// Use sample data or zeros
			$total_keywords = $data['is_sample'] ? '19K' : '0';
			$total_impressions = $data['is_sample'] ? '15M' : '0';
			$avg_ctr = $data['is_sample'] ? '48.25%' : '0%';
			$impression_change = $data['is_sample'] ? '475.7K' : '0';
			$impression_trend = $data['is_sample'] ? 'negative' : 'neutral';
			$ctr_change = $data['is_sample'] ? '1' : '0';
			$ctr_trend = $data['is_sample'] ? 'negative' : 'neutral';
		}
	
		echo'<div class="siteseo-stats-container">
			<h2 class="siteseo-stats-title">'.esc_html__('Keyword Positions', 'siteseo').'</h2>
			<hr class="siteseo-stats-separator">
			<ul class="siteseo-stats-list">
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Total Keyword', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($total_keywords).'</span>
						'.($data['is_sample'] ? '<span class="siteseo-stat-change positive">
							<span class="dashicons dashicons-arrow-up"></span>2.9K
						</span>' : '').'
					</div>
				</li>
			
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Search Impressions', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($total_impressions).'</span>
						'.((!$data['is_sample'] && $has_actual_data) ? 
						'<span class="siteseo-stat-change '.esc_attr($impression_trend).'">
							<span class="dashicons dashicons-arrow-'.($impression_trend === 'positive' ? 'up' : 'down').'"></span>'
							.esc_html($impression_change).'
						</span>' : 
						($data['is_sample'] ? 
						'<span class="siteseo-stat-change negative">
							<span class="dashicons dashicons-arrow-down"></span>475.7K
						</span>' : '')).'
					</div>
				</li>
			
				<li class="siteseo-stat-item">
					<div class="siteseo-stat-header">
						<span class="siteseo-stat-label">'.esc_html__('Avg. CTR', 'siteseo').'</span>
					</div>
					<div class="siteseo-stat-value-group">
						<span class="siteseo-stat-value">'.esc_html($avg_ctr).'</span>
						'.($data['is_sample'] ? 
						'<span class="siteseo-stat-change negative">
							<span class="dashicons dashicons-arrow-down"></span>1
						</span>' : '').'
					</div>
				</li>
			</ul>
		
			<div style="display:flex; gap:20px; flex-wrap:wrap;">
				<div style="flex:1; min-width:280px; max-width:50%;">
					<canvas id="siteseo_keyword_muti_line_chart" data-sample="'.($data['is_sample'] ? '1' : '0').'" data-actual="'.($has_actual_data ? '1' : '0').'"></canvas>
				</div>
				<div style="flex:1; min-width:280px; max-width:50%;">
					<canvas id="siteseo_keyword_bar_chart" data-sample="'.($data['is_sample'] ? '1' : '0').'" data-actual="'.($has_actual_data ? '1' : '0').'"></canvas>
				</div>
			</div>
		</div>';
		
		if(!empty($keywords) || $data['is_sample']){
			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Content Analysis ( Top 5 )', 'siteseo').'</h2>
				<table class="wp-list-table widefat fixed striped siteseo-history-table">
					<thead><tr>
						<th>'.esc_html__('Keywords', 'siteseo').'</th>
						<th>'.esc_html__('Clicks', 'siteseo').'</th>
						<th>'.esc_html__('Avg. CTR', 'siteseo').'</th>
						<th>'.esc_html__('Impressions', 'siteseo').'</th>
						<th>'.esc_html__('Position', 'siteseo').'</th>
					</tr>
					</thead>
				<tbody>';
				
				foreach($keywords as $keyword){
					echo'<tr>
						<td class="siteseo-table-row">'.esc_html($keyword['keyword']).'</td>
						<td>'.esc_html($keyword['clicks']).'</td>
						<td>'.esc_html($keyword['ctr']).'</td>
						<td>'.esc_html($keyword['impressions']).'</td>
						<td>'.esc_html($keyword['position']).'</td>
					</tr>';
				}
				
				if(empty($keywords) && !$data['is_sample']){
					echo'<tr><td colspan="6" style="text-align:center;">'.esc_html__('No data available yet', 'siteseo').'</td></tr>';
				}
				
				echo'</tbody></table>
			</div>';
		}
	}

	static function content_ranking_tab(){
		self::connect_notices();
		
		$data = self::fetch_data();
		$content_ranking = $data['content_ranking'];

		if(!empty($content_ranking) || $data['is_sample']){
			// Pagination setup
			$items_per_page = 10;
			$total_items = count($content_ranking);
			$total_pages = ceil($total_items / $items_per_page);
			
			$current_page = isset($_GET['cr_page']) ? max(1, intval($_GET['cr_page'])) : 1;

			$offset = ($current_page - 1) * $items_per_page;
			
			$paged_items = array_slice($content_ranking, $offset, $items_per_page);
			
			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Content Analysis (Top 30)', 'siteseo').'</h2>
				<table class="wp-list-table widefat fixed striped siteseo-history-table">
					<thead><tr>
						<th>'.esc_html__('Title', 'siteseo').'</th>
						<th>'.esc_html__('Indexed', 'siteseo').'</th>
						<th>'.esc_html__('Last Update on', 'siteseo').'</th>
						<th>'.esc_html__('Loss', 'siteseo').'</th>
						<th>'.esc_html__('Drop (%)', 'siteseo').'</th>
						<th>'.esc_html__('Performance Score', 'siteseo').'</th>
					</tr>
					</thead>
				<tbody>';

				foreach($paged_items as $content){
					$score = (int) explode('/', $content['performance_score'])[0];
					
					if($score >= 80){
						$badge_class = 'siteseo-gsc-score-good';
					} elseif($score >= 50){
						$badge_class = 'siteseo-gsc-score-avg';
					} else{
						$badge_class = 'siteseo-gsc-score-bad';
					}
					
					echo'<tr>
						<td>'.esc_html($content['title']).'</td> 
						<td><span class="dashicons '.($content['indexed'] === 'Yes' ? 'dashicons-yes-alt siteseo-statistics-index-icon' : 'dashicons-dismiss siteseo-statistics-noindex-icon').'"></span></td>
						<td>'.esc_html($content['last_update']).'</td>
						<td style="color:#dc3545;">'.esc_html($content['loss']).'</td>
						<td style="color:#dc3545;">'.esc_html($content['drop_percent']).'</td>
						<td><span class="'.esc_attr($badge_class).'">'.esc_html($content['performance_score']).'</span></td>
					</tr>';
				}
				
				if(empty($content_ranking) && !$data['is_sample']){
					echo'<tr><td colspan="6" style="text-align:center;">'.esc_html__('No data available yet', 'siteseo').'</td></tr>';
				}
				
				echo'</tbody></table>';
			
			// Pagination controls
			if($total_pages > 1){
				echo'<div class="siteseo-pagination" style="margin-top: 20px; text-align: center;">';
				
				// Previous btn
				if($current_page > 1){
					echo'<a href="'.esc_url(add_query_arg('cr_page', $current_page - 1)).'" class="siteseo-option btnSecondary">'.esc_html__('Previous', 'siteseo').'</a> ';
				}
				
				// Page numbers
				for($i = 1; $i <= $total_pages; $i++){
					if($i == $current_page){
						echo'<span class="siteseo-option btnSecondary" style="margin: 0 2px;">'.esc_html($i).'</span> ';
					} else{
						echo'<a href="'.esc_url(add_query_arg('cr_page', $i)).'" class="siteseo-option btnSecondary" style="margin: 0 2px;">'.esc_html($i).'</a> ';
					}
				}
				
				// Next btn
				if($current_page < $total_pages){
					echo'<a href="'.esc_url(add_query_arg('cr_page', $current_page + 1)).'" class="siteseo-option btnSecondary">'.esc_html__('Next', 'siteseo').'</a>';
				}
				
				echo'</div>';
			}
			
			echo'</div>';
		}
	}

	static function audience_tab(){
		self::connect_notices();
		$data = self::fetch_data();
		$country_data = $data['country_data'];
		$device_data = $data['device_data'];

		echo'<div class="siteseo-audience-statisc">
			<div class="siteseo-stats-container">
				<h2 class="siteseo-stat-title">'.esc_html__('Device breakdown', 'siteseo').'</h2>
				 <hr class="siteseo-stats-separator">
				 <canvas id="siteseo_device_statics" height="250px" width="520px" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
			</div>
			
			<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Top countries by click ( Top 5 )', 'siteseo').'</h2>
				<hr class="siteseo-stats-separator">
				<canvas id="siteseo_country_statics" height="300px" width="520px" data-sample="'.($data['is_sample'] ? '1' : '0').'"></canvas>
			</div>
		</div>';

		if(!empty($device_data) || $data['is_sample']){
			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Device Performance', 'siteseo').'</h2>
					<table class="wp-list-table widefat fixed striped siteseo-history-table">
						<thead><tr>
							<th>'.esc_html__('Device', 'siteseo').'</th>
							<th>'.esc_html__('Clicks', 'siteseo').'</th>
							<th>'.esc_html__('Impressions', 'siteseo').'</th>
						</tr>
						</thead>
					<tbody>';
					
					foreach($device_data as $data){
						echo'<tr>
							<td style="font-weight:bold;">'.esc_html( is_array($data['device']) ? implode(', ', $data['device']) : $data['device']).'</td>
							<td>'.esc_html($data['clicks']).'</td>
							<td>'.esc_html($data['impressions']).'</td>
						</tr>';
					}
					
				echo'</tbody></table>
			</div>';
		}

		if(!empty($country_data) || $data['is_sample']){
			echo'<div class="siteseo-stats-container">
				<h2 class="siteseo-stats-title">'.esc_html__('Country Performance ( Top 5 )', 'siteseo').'</h2>
					<table class="wp-list-table widefat fixed striped siteseo-history-table">
						<thead><tr>
							<th>'.esc_html__('Country', 'siteseo').'</th>
							<th>'.esc_html__('Clicks', 'siteseo').'</th>
							<th>'.esc_html__('Impressions', 'siteseo').'</th>
						</tr>
						</thead>
					<tbody>';
					
					foreach($country_data as $data){
						echo '<tr>
							<td style="font-weight:bold;">'.esc_html($data['country']).'</td>
							<td>'.esc_html($data['clicks']).'</td>
							<td>'.esc_html($data['impressions']).'</td>
						</tr>';
					}
					
					echo'</tbody></table>
				</div>';
		}
	}
	
	static function connect_notices(){
		
		$analytics_data = '';
		$gsc_connected = '';
		
		if(class_exists('\SiteSEOPro\GoogleConsole') && method_exists('\SiteSEOPro\GoogleConsole', 'is_connected')){
			$analytics_data = get_option('siteseo_search_console_data', []);

			$gsc_connected = \SiteSEOPro\GoogleConsole::is_connected();
		}
		
		 if(empty($gsc_connected)){
			
			echo'<div class="siteseo-notice is-warning"><p>'.wp_kses_post(__('The data shown here is only a <strong> sample from Google Analytics</strong> how SiteSEO will display your site\'s analytics once connected.', 'siteseo')).'</p>';

			if(class_exists('\SiteSEOPro\GoogleConsole')){
				echo'<form method="post">';
						wp_nonce_field('siteseo_pro_connect_google');
						echo '<input type="hidden" name="redirect_type" value="settings">
						<button type="submit" name="siteseo_pro_connect_btn" class="siteseo-statistics-connect-btn">'.esc_html__('Connect Search Console', 'siteseo') .'</button>
					</form>';
			} else{
				  echo'<a href="https://siteseo.io/pricing" class="siteseo-statistics-connect-btn" target="_blank">'.esc_html__('Buy Pro', 'siteseo').'</a>';
			}
			
			echo'</div>';
		} elseif(empty($analytics_data['top_pages']) && !empty($gsc_connected)){
			echo '<div class="siteseo-notice is-info"><p>'.wp_kses_post(__('Connected to Google Search Console. If this is your first time connecting the site and data doesn’t appear yet, please wait—Google may take a few days to populate the data. You can also try refreshing.', 'siteseo')).'</p></div>';
		}
	}

	static function sample_device_data(){
		 return [
			[
				'device' => 'Mobile',
				'clicks' => '48k',
				'impressions' => '285k',
				'ctr' => '5.2%',
			],
			[
				'device' => 'Desktop',
				'clicks' => '70k',
				'impressions' => '196k',
				'ctr' => '3.8%',
			],
			[
			
				'device' => 'Tablet',
				'clicks' => '10k',
				'impressions' => '185k',
				'ctr' => '8.1%',
			],
		];
	}

	static function sample_country_data(){
		
		 return [
			[
				'country' => 'India',
				'clicks' => '8K',
				'impressions' => '154K',
				'ctr' => '5.2%',
			],
			[
				'country' => 'Poland',
				'clicks' => '5K',
				'impressions' => '132K',
				'ctr' => '3.8%',
			],
			[
			
				'country' => 'South Africa',
				'clicks' => '15K',
				'impressions' => '185k',
				'ctr' => '8.1%',
			],
			[
				'country' => 'Russia',
				'clicks' => '19K',
				'impressions' => '200k',
				'ctr' => '9.5%',
			],
			[
				'country' => 'United kingdom',
				'clicks' => '12K',
				'impressions' => '150k',
				'ctr' => '5.5%',
			],
		];
		
	}
	
	static function sample_metrics_data(){
		return [
			'impressions' => [
				'current' => '15M',
				'change' => '-475.7K',
				'trend' => 'negative',
				'chart_data' => [12, 19, 3, 5, 2, 3, 10, 8, 12, 14, 13, 15]
			],
			'clicks' => [
				'current' => '111.5K',
				'change' => '+1.7K',
				'trend' => 'positive',
				'chart_data' => [7, 11, 5, 8, 3, 7, 4, 5, 6, 7, 6, 8]
			],
			'ctr' => [
				'current' => '0.74%',
				'change' => '+0.03%',
				'trend' => 'positive',
				'chart_data' => [0.5, 0.6, 0.7, 0.65, 0.75, 0.7, 0.6, 0.7, 0.72, 0.74, 0.73, 0.75]
			],
			'position' => [
				'current' => '49',
				'change' => '+1',
				'trend' => 'negative',
				'chart_data' => [55, 50, 45, 50, 48, 52, 55, 50, 49, 47, 48, 49]
			]
		];
	}

	static function sample_top_pages(){
		return [
			[
				'title' => '/blog/',
				'truseo_score' => '95',
				'indexed' => true,
				'clicks' => '4.5K',
				'impressions' => '57.2K',
				'position' => '23',
				'diff' => '+2'
			],
			[
				'title' => '/contact-us/',
				'truseo_score' => '88',
				'indexed' => false,
				'clicks' => '1.2K',
				'impressions' => '1.2M',
				'position' => '40',
				'diff' => '-5'
			],
			[
				'title' => '/support/',
				'truseo_score' => '92',
				'indexed' => false,
				'clicks' => '15K',
				'impressions' => '1.9M',
				'position' => '16',
				'diff' => '+3'
			],
			[
				'title' => '/pricing/',
				'truseo_score' => '85',
				'indexed' => true,
				'clicks' => '8.7K',
				'impressions' => '890K',
				'position' => '12',
				'diff' => '+1'
			],
			[
				'title' => '/features/',
				'truseo_score' => '90',
				'indexed' => true,
				'clicks' => '12.3K',
				'impressions' => '1.5M',
				'position' => '8',
				'diff' => '+4'
			]
		];
	}

	static function sample_keywords(){
		return [
			[
				'keyword' => 'One click seo plugin',
				'clicks' => '8K',
				'ctr' => '5.2%',
				'impressions' => '154K',
				'position' => '3',
				'trend' => 'up',
				'points' => '90'
			],
			[
				'keyword' => 'wordpress seo',
				'clicks' => '5K',
				'ctr' => '3.8%',
				'impressions' => '132K',
				'position' => '7',
				'trend' => 'up',
				'points' => '80'
			],
			[
				'keyword' => 'best seo plugin plugin',
				'clicks' => '15K',
				'ctr' => '8.1%',
				'impressions' => '185K',
				'position' => '2',
				'trend' => 'up',
				'points' => '85'
			],
			[
				'keyword' => 'seo optimization',
				'clicks' => '3.2K',
				'ctr' => '2.1%',
				'impressions' => '152K',
				'position' => '15',
				'trend' => 'down',
				'points' => '88'
			],
			[
				'keyword' => 'website ranking',
				'clicks' => '6.8K',
				'ctr' => '4.5%',
				'impressions' => '151K',
				'position' => '5',
				'trend' => 'up',
				'points' => '70'
			]
		];
	}
	
	static function sample_content_ranking(){
		return [
			[
				'title' => 'Ultimate SEO Guide 2024',
				'indexed' => 'Yes',
				'last_update' => '2024-01-15',
				'loss' => '2.1K',
				'drop_percent' => '5.2%',
				'performance_score' => '88'
			],
			[
				'title' => 'WordPress Optimization Tips',
				'indexed' => 'Yes',
				'last_update' => '2024-01-10',
				'loss' => '1.5K',
				'drop_percent' => '3.8%',
				'performance_score' => '92'
			],
			[
				'title' => 'Mobile SEO Strategies',
				'indexed' => 'No',
				'last_update' => '2024-01-08',
				'loss' => '3.2K',
				'drop_percent' => '8.1%',
				'performance_score' => '75'
			],
			[
				'title' => 'Content Marketing Guide',
				'indexed' => 'Yes',
				'last_update' => '2024-01-12',
				'loss' => '0.8K',
				'drop_percent' => '2.1%',
				'performance_score' => '95'
			]
		];
	}

	static function connect_site_dialogbox(){

		$gsc_sites = '';
		$current_site_url = trailingslashit(get_site_url());

		if(class_exists('\SiteSEOPro\GSCSetup') && method_exists('\SiteSEOPro\GSCSetup', 'get_pre_connected_sites')){
			$gsc_sites = \SiteSEOPro\GSCSetup::get_pre_connected_sites();
		}

		$current_site_exists = false;
		if(!empty($gsc_sites) && !isset($gsc_sites['error'])){
			foreach($gsc_sites as $site){
				if($site['siteUrl'] === $current_site_url){
					$current_site_exists = true;
					break;
				}
			}
		}

		echo'<div id="siteseo-site-connection-dialog" title="'.esc_attr__('Connect site search console', 'siteseo').'" style="display:none;">
			<div class="siteseo-dialog-content">';

		echo'<div id="siteseo-main-section">';

		if($current_site_exists){
			// Tab: Current Site
			echo'<div class="siteseo-option-primary" style="margin-bottom:20px;">
				<p style="color:#1d2327;font-weight:400;font-size:14px;">'.esc_html__('This site is already in your Google Search Console account.', 'siteseo').'</p>
				<div class="siteseo-form-group">
					<input type="text" id="siteseo-site-url" value="' . esc_attr($current_site_url) . '" class="regular-text" readonly
						style="width:100%;padding:8px;background:#f6f7f7;border:1px solid #8c8f94;color:#666;cursor:not-allowed;" />
				</div>
				<div class="siteseo-dialog-actions">
					<button type="button" class="button button-primary siteseo-action-btn" id="siteseo-connect-existing">'.esc_html__('Connect', 'siteseo').'</button>
					<span class="spinner"></span>
				</div>
			</div>';
			if(!empty($gsc_sites)){
				echo '<div class="siteseo-option-secondary">
					<button type="button" class="button button-link" id="siteseo-show-existing-properties" style="width:100%;text-decoration:none;">'.esc_html__('Select from existing sites', 'siteseo').'</button>
				</div>';
			}
		} else{
			// Not connected yet
			echo'<div class="siteseo-option-primary" style="margin-bottom:20px;">
				<p style="color:#1d2327;font-weight:400;font-size:14px;">'.esc_html__('This domain isn\'t yet connected to Google Search Console.', 'siteseo').'</p>
				<div class="siteseo-form-group">
					<input type="text" id="siteseo-new-domain-url" value="' . esc_attr($current_site_url) . '" class="regular-text" readonly
						style="width:100%;padding:8px;background:#f6f7f7;border:1px solid #8c8f94;color:#666;cursor:not-allowed;" />
				</div>
				<button type="button" class="button button-primary siteseo-action-btn siteseo-create-btn" id="siteseo-create-gsc-property" style="margin-bottom:10px;">'.esc_html__('Connect New Domain', 'siteseo').'</button>
				<span class="spinner"></span>
			</div>';

			if(!empty($gsc_sites)){
				echo'<div class="siteseo-option-secondary">
					<button type="button" class="button button-link" id="siteseo-show-existing-properties" style="width:100%;text-decoration:none;">'.esc_html__('Connect with existing properties', 'siteseo').'</button>
				</div>';
			}
		}

		echo'</div>';

		if(!empty($gsc_sites) && !isset($gsc_sites['error'])){
			echo'<div id="siteseo-existing-properties-section" style="display:none;">
				<p style="color:#1d2327;font-weight:400;font-size:14px;">'. esc_html__('Select an existing site from your Google Search Console account.', 'siteseo').'</p>
				<div class="siteseo-form-group">
					<select id="siteseo-site-url" name="existing_site_url" class="siteseo-select-box">';

						foreach($gsc_sites as $site){
							echo'<option value="'.esc_attr($site['siteUrl']).'">'.esc_html($site['siteUrl']).'</option>';
						}

			echo'</select>
				</div>
				<div class="siteseo-dialog-actions">
					<button type="button" class="button button-secondary siteseo-action-btn" id="siteseo-back-to-main">'.esc_html__('Back', 'siteseo').'</button>
					<button type="button" class="button button-primary siteseo-action-btn" id="siteseo-connect-existing">'.esc_html__('Connect', 'siteseo').'</button>
					<span class="spinner" style="margin-top:7px;"></span>
				</div>
			</div>';
		}

		echo'</div></div>'; // End dialog content + wrapper
	}
}
