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

class OnBoarding{
	
	static $current_step = '';
	static $import_options = [];
	static $steps = [];
	static $current_step_no = 1;

	static function init(){
		if(wp_doing_ajax()){
			return;
		}
		
		self::$steps = [
			'your-site' => [
				'title' => 'Your Site',
				'desc' => 'Your site and social data',
				'fn' => '\SiteSEO\Settings\OnBoarding::site_page',
			],
			'indexing' => [
				'title' => 'Indexing',
				'desc' => 'Select post type indexing',
				'fn' => '\SiteSEO\Settings\OnBoarding::indexing_page',
			],
			'advanced' => [
				'title' => 'Advanced',
				'desc' => 'URL configuration',
				'fn' => '\SiteSEO\Settings\OnBoarding::advanced_page',
			],
			'ready' => [
				'title' => 'Ready',
				'desc' => 'All set now!',
				'fn' => '\SiteSEO\Settings\OnBoarding::ready_page',
			],
		];
		
		$active_plugins = get_option('active_plugins', []);
		$importable_plugins = Util::importable_plugins();
		$importable_plugins = array_keys($importable_plugins);
		
		$importable_found = array_intersect($active_plugins, $importable_plugins);
		
		if(!empty($importable_found)){
			$import_step = [
				'import' => [
					'title' => 'Import',
					'desc' => 'Importing meta data',
					'fn' => '\SiteSEO\Settings\OnBoarding::import_page',
				]
			];
			
			self::$steps = array_merge($import_step, self::$steps);
			self::$import_options = $importable_found;
		}
		
		self::$current_step = !empty($_REQUEST['step']) ? sanitize_text_field(wp_unslash($_REQUEST['step'])) : '';

		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
		remove_all_actions('network_admin_notices');
		add_action('admin_menu', '\SiteSEO\Settings\OnBoarding::add_to_menu');
		add_action('admin_init', '\SiteSEO\Settings\OnBoarding::page');
	}

	static function enqueue_assets(){
		wp_enqueue_media();
		wp_enqueue_script('siteseo-onboarding', SITESEO_ASSETS_URL . '/js/onboarding.js', ['jquery'], SITESEO_VERSION, true);
		wp_enqueue_style('siteseo-onboarding' , SITESEO_ASSETS_URL . '/css/onboarding.css', [], SITESEO_VERSION);
		wp_add_inline_script('siteseo-onboarding', "let siteseo_onboarding = ".wp_json_encode([
			'nonce' => wp_create_nonce('siteseo_admin_nonce'),
			'ajax_url' => admin_url('admin-ajax.php'),
		]));
	}
	
	static function add_to_menu(){
		add_submenu_page('', __('SiteSEO Onboarding', 'siteseo'), 'Onboarding', 'manage_options', 'siteseo-onboarding', 'SiteSEO\Settings\Onboarding::wizard');
	}
	
	static function page(){
		self::enqueue_assets();
		
		ob_start();

	?><!DOCTYPE html>
<html <?php language_attributes();?>>
<?php echo'<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>'.esc_html__('SiteSEO OnBoarding', 'siteseo').'</title>';
wp_print_head_scripts();
wp_print_styles('siteseo-onboarding');
	
echo '</head>
<body>';
	self::wizard();
	if(function_exists('wp_print_media_templates')){
		wp_print_media_templates();
	}
	wp_print_footer_scripts();
	wp_print_scripts('siteseo-onboarding');
	echo '</body>
</html>';
		die();
	}
	
	static function wizard(){

		echo '<div id="siteseo-onboarding-root">
	<div class="siteseo-onboarding-nav-wrapper">
	<nav>
		<div class="content">
		<div class="header">
			<img src="'.esc_url(SITESEO_ASSETS_URL) .'/img/siteseo-white.png" height="40"/>
			<a href="'.esc_url(admin_url('?page=siteseo')).'" title="'.esc_attr__('Exit to SiteSEO Dashboard', 'siteseo').'"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="M200-120q-33 0-56.5-23.5T120-200v-160h80v160h560v-560H200v160h-80v-160q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm220-160-56-58 102-102H120v-80h346L364-622l56-58 200 200-200 200Z"/></svg></a>
		</div>';

		$step_count = 1;
		echo '<div class="steps">';
		foreach(self::$steps as $step_slug => $step){
			echo '<div class="step">
				<div class="step-milestone" data-step="'.esc_attr($step_count).'" data-step-slug="'.esc_attr($step_slug).'"></div>
				<div class="step-info"><span>'.esc_html($step['title']).'</span><span class="description">'.esc_html($step['desc']).'</span></div>
			</div>';
			$step_count++;
		}
		echo '
		</div>
		</div>
		<div class="footer">A Softaculous Product</div>
	</nav>
	</div>
	<main>
		<div class="siteseo-onboarding-content">';
		self::welcome_page();
		
		foreach(self::$steps as $step){
			call_user_func($step['fn']);
			self::$current_step_no++; // increasing the step number after we have rendered the step page.
		}

		echo '</div>
	
	</main>
</div>';

	}

	static function welcome_page(){
		
		$is_active = empty(self::$current_step) ? 'siteseo-step-active' : '';
		
		echo '<div class="siteseo-step-page siteseo-step-is-welcome '.esc_attr($is_active).'" data-step="welcome">
			<h1>'.esc_html__('Welcome to the SiteSEO Setup Wizard', 'siteseo').'</h1>
			<p>'.esc_html__('This wizard will guide you through setting up SiteSEO and help you get started in no time.', 'siteseo').'</p>
			<button class="siteseo-btn primary" id="siteseo-onboarding-begin" style="margin-top:20px">'.esc_html__('Let\'s begin!', 'siteseo').'</button>
		</div>';
		
	}
	
	static function import_page(){
		$is_active = !empty(self::$current_step) && self::$current_step == 'import' ? 'siteseo-step-active' : '';

		echo '<div class="siteseo-step-page '.esc_attr($is_active).'" data-step="import">
		<span>Step '.esc_html(self::$current_step_no).' of '.count(self::$steps).'</span>
		<h1>'.esc_html__('Import data from your current SEO plugin', 'siteseo').'</h1>
		<p>'.esc_html__('SiteSEO has detected the presence of other SEO plugins. To ensure a smooth transition, please select the plugins you wish to import SEO data from', 'siteseo').'</p>
		<div class="siteseo-onboarding-import-plugins">';
			$importable_plugins = Util::importable_plugins();

			echo '<form><div class="siteseo-radio-input">';
			foreach(self::$import_options as $plugin){
				$id = strtolower(str_replace(' ', '-', $plugin));
				echo '<input type="radio" name="plugin_name" value="'.esc_attr($id).'" id="'.esc_attr($id).'"/>
				<label for="'.esc_attr($id).'">'.esc_html($importable_plugins[$plugin]).'</label>';
			}
			echo '</div>
			<div class="siteseo-onboarding-import-info">
				<details>
					<summary>'.esc_html__('What will be imported?', 'siteseo').'</summary>
					<ul>
						<li>'. esc_html__('Title tags', 'siteseo') .'</li>
						<li>'. esc_html__('Meta description', 'siteseo') .'</li>
						<li>'. esc_html__('Facebook Open Graph tags (title, description and image thumbnail)', 'siteseo') .'</li>
						<li>'. esc_html__('Twitter tags (title, description and image thumbnail)', 'siteseo') .'</li>
						<li>'. esc_html__('Meta Robots (noindex, nofollow...)', 'siteseo') .'</li>
						<li>'. esc_html__('Canonical URL', 'siteseo').'</li>
						<li>'. esc_html__('Focus / target keywords', 'siteseo') .'</li>
					</ul>
				</details>
				<button class="siteseo-btn primary" id="siteseo-do-import">Import</button>
				<p class="siteseo-onboarding-msg"></p>
			</div>
			</form>
			<div class="siteseo-onboarding-content-footer">
			<button class="siteseo-skip-step siteseo-btn secondary">'.esc_html__('Skip Step', 'siteseo').'</button><button class="siteseo-btn primary siteseo-skip-step">'.esc_html__('Next Step', 'siteseo').'</button>
			</div>
		</div>
		</div>';
	}
	
	static function site_page(){
		$title_options = get_option('siteseo_titles_option_name', []);
		$social_options = get_option('siteseo_social_option_name', []);

		$site_name = !empty($title_options['titles_home_site_title']) ? $title_options['titles_home_site_title'] : '%%sitetitle%%';
		$alt_site_name = !empty($title_options['titles_home_site_title_alt']) ? $title_options['titles_home_site_title_alt'] : '';
		$site_type = !empty($social_options['social_knowledge_type']) ? $social_options['social_knowledge_type'] : '';
		$org_name = !empty($social_options['social_knowledge_name']) ? $social_options['social_knowledge_name'] : '';
		$org_img = !empty($social_options['social_knowledge_img']) ? $social_options['social_knowledge_img'] : '';
		$fb_url	= !empty($social_options['social_accounts_facebook']) ? $social_options['social_accounts_facebook'] : '';
		$x_account = !empty($social_options['social_accounts_twitter']) ? $social_options['social_accounts_twitter'] : '';
		$additional_url = !empty($social_options['social_accounts_additional']) ? implode("\n", $social_options['social_accounts_additional']) : '';
		
		$is_active = !empty(self::$current_step) && self::$current_step == 'your-site' ? 'siteseo-step-active' : '';
		echo '<div class="siteseo-step-page '.esc_attr($is_active).'" data-step="your-site">
		<span>Step '.esc_html(self::$current_step_no).' of '.count(self::$steps).'</span>
		<h1>Your Site: '.esc_html(get_bloginfo('name')).'</h1>
		<p>'.esc_html__('We need some basic information about your site, so we can built up the knowledge graph', 'siteseo').'</p>
		<form>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Website Name', 'siteseo').'</label>
				<input type="text" name="website_name" value="'.esc_attr($site_name).'"/>
				<p class="siteseo-input-description">'.esc_html__('Enter the name of your site as it should appear in search results, %%sitetitle%% is a dynamic variable for your site title', 'siteseo').'</p>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Altername sitename', 'siteseo').'</label>
				<input type="text" name="alternate_site_name" value="'.esc_attr($alt_site_name).'" placeholder="Alternate site name"/>
				<p class="siteseo-input-description">'.esc_html__('The website\'s alternate name, like a common acronym or shorter version, if applicable.', 'siteseo').'</p>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Is your site about an Organization or a Person?', 'siteseo').'</label>
				<select type="text" name="site_type">
					<option value="Person" '.selected($site_type, 'Person', false).'>Person</option>
					<option value="Organization" '.selected($site_type, 'Organization', false).'>Organization</option>
				</select>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Your/Organization name', 'siteseo').'</label>
				<input type="text" name="organization_name" placeholder="eg:. My Company Name" value="'.esc_attr($org_name).'"/>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Organization Logo', 'siteseo').'</label>
				<button id="siteseo-onboarding-img-holder">
				<img src="'.esc_url($org_img).'"/>
				<svg xmlns="http://www.w3.org/2000/svg" height="50px" viewBox="0 -960 960 960" width="50px" fill="#5f6368" style="'.(!empty($org_img) ? 'display:none;' : '').'"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Z"/></svg></button>
				<p class="siteseo-input-description">'.esc_html__('A square image is preferred, with a minimum size of 112x112 pixels.', 'siteseo').'</p>
				<input type="hidden" name="organization_logo" value="'.esc_url($org_img).'"/>
				<button class="siteseo-btn primary" id="siteseo-upload-org-img" style="align-self:flex-start">'.esc_html__('Select Image', 'siteseo').'</button>
			</div>
			<h4>Social Details</h4>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Facebook page URL', 'siteseo').'</label>
				<input type="text" name="social_fb" value="'.esc_url($fb_url).'" placeholder="eg: https://facebook.com/my-page-url"/>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('X Username', 'siteseo').'</label>
				<input type="text" name="social_x" value="'.esc_attr($x_account).'" placeholder="eg: @x_account"/>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Additional Accounts', 'siteseo').'</label>
				<textarea rows="3" name="social_additional" placeholder="eg:https://somesocial.com/my-page">'.esc_textarea($additional_url).'</textarea>
				<p class="siteseo-input-description">'.esc_html__('Enter 1 URL per line.', 'siteseo').'</p>
			</div>
			
			<div class="siteseo-onboarding-content-footer">
				<button class="siteseo-skip-step siteseo-btn secondary">'.esc_html__('Skip Step', 'siteseo').'</button><button class="siteseo-btn primary siteseo-save-n-continue">'.esc_html__('Save and Continue', 'siteseo').'<span class="siteseo-spinner"></span></button>
			</div>
		</form>
		</div>';
	}
	
	static function indexing_page(){
		$is_active = !empty(self::$current_step) && self::$current_step == 'indexing' ? 'siteseo-step-active' : '';
		$post_types = get_post_types(['public' =>  true, 'show_ui' => true], 'objects', 'and');
		unset($post_types['attachment']);
		
		$taxonomies = get_taxonomies(['public' =>  true, 'show_ui' => true], 'objects', 'and');

		echo '<div class="siteseo-step-page '.esc_attr($is_active).'" data-step="indexing">
		<span>Step '.esc_html(self::$current_step_no).' of '.count(self::$steps).'</span>
		<h1>Indexing</h1>
		<p>'.esc_html__('Let us know which parts of your website you’d like to be crawled.', 'siteseo').'</p>
		<form method="POST">
		<div class="siteseo-input-block">
			<label>'.esc_html__('Is your site under construction or live?', 'siteseo').'</label>
			<div class="siteseo-radiogroup">
				<label><input type="radio" name="site_status" value="underconstruction"/>Under Construction</label>
				<label><input type="radio" name="site_status" value="live" checked/>Live</label>
			</div>
			<p class="siteseo-input-description">'.esc_html__('If your site is under construction then Search Engines will be discouraged to crawl your site by adding noindex metatag attribute and sitemap will be disabled.', 'siteseo').'</p>
		</div>
		<div class="siteseo-live-site-options" style="margin-top:35px;">
			<p>'.esc_html__('Choose items to exclude from search results', 'siteseo').'</p>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Post Types', 'siteseo').'</label>
				<div class="siteseo-radiogroup">';
				if(!empty($post_types)){
				foreach($post_types as $post){
					echo '<div><input type="checkbox" name="post_types" value="'.esc_attr($post->name).'" id="post_type_'.esc_attr($post->name).'"/>
					<label for="post_type_'.esc_attr($post->name).'">'.esc_html($post->label).'</label>
					</div>';
				}

				echo '<p class="siteseo-input-description">'.esc_html__('Discourage search engines from indexing these post types.', 'siteseo').'</p>';
			} else {
				echo '<p class="siteseo-input-description">'.esc_html__('No post type found.', 'siteseo').'</p>';
			}
			
			//TODO:: Will need to add options for Archive as well
			echo '</div>
			</div>
			<div class="siteseo-input-block">
				<label>'.esc_html__('Taxonomies', 'siteseo').'</label>
				<div class="siteseo-radiogroup">';
				if(!empty($taxonomies)){
					foreach($taxonomies as $taxonomy){
						echo '<div><input type="checkbox" name="taxonomies" value="'.esc_attr($taxonomy->name).'" id="taxonomy_'.esc_attr($taxonomy->name).'"/>
						<label for="taxonomy_'.esc_attr($taxonomy->name).'">'.esc_html($taxonomy->label).'</label>
						</div>';
					}

					echo '<p class="siteseo-input-description">'.esc_html__('Discourage search engines from indexing these taxonomies.', 'siteseo').'</p>
					<p class="siteseo-input-description">'.esc_html__('Note: We strongly recommend disabling the indexing of tags to avoid potential duplicate content issues that could negatively impact your site\'s SEO.', 'siteseo').'</p>';
				} else {
					echo '<p class="siteseo-input-description">'.esc_html__('No taxonomy found.', 'siteseo').'</p>';
				}
			echo '</div>
			</div>
		</div>
		<div class="siteseo-onboarding-content-footer">
			<button class="siteseo-skip-step siteseo-btn secondary">'.esc_html__('Skip Step', 'siteseo').'</button><button class="siteseo-btn primary siteseo-save-n-continue">'.esc_html__('Save and Continue', 'siteseo').'<span class="siteseo-spinner"></span></button>
		</div>
		</form>
		</div>';
	}
	
	static function advanced_page(){
		$is_active = !empty(self::$current_step) && self::$current_step == 'advanced' ? 'siteseo-step-active' : '';
		echo '<div class="siteseo-step-page '.esc_attr($is_active).'" data-step="advanced">
		<span>Step '.esc_html(self::$current_step_no).' of '.count(self::$steps).'</span>
		<h1>Advanced Options</h1>
		<p>'.esc_html__('We\'re nearly there—just a few final optimizations left!', 'siteseo').'</p>
		<form method="POST">
			<div class="siteseo-input-block">
				<div class="siteseo-radiogroup">
					<label><input type="checkbox" name="universal_metabox" checked/>Enable Universal Metabox</label>
				</div>
				<p class="siteseo-input-description">'.esc_html__('Universal metabox makes SiteSEO on page content SEO helper compatible with every Page Builder, so if you are not using Gutenberg then this is a must.', 'siteseo').'</p>
			</div>
			<div class="siteseo-input-block">
				<div class="siteseo-radiogroup">
					<label><input type="checkbox" name="author_noindex"/>'.esc_html__('Don\'t let search engines index author archive pages', 'siteseo').'</label>
				</div>
				<p class="siteseo-input-description">'.esc_html__('Recommended: Enable this option if you are the sole author of the site to prevent duplicate content on author archive pages.', 'siteseo').'</p>
			</div>
			<div class="siteseo-input-block">
				<div class="siteseo-radiogroup">
					<label><input type="checkbox" name="redirect_attachment"/>'.esc_html__('Redirect attachment pages to the file itself', 'siteseo').'</label>
				</div>
				<p class="siteseo-input-description">'.esc_html__('By default SiteSEO redirects to the parent post.', 'siteseo').'</p>
			</div>
			<div class="siteseo-input-block">
				<div class="siteseo-radiogroup">
					<label><input type="checkbox" name="category_url"/>'.esc_html__('Remove /category/ in your permalinks', 'siteseo').'</label>
				</div>
				<p class="siteseo-input-description">'.esc_html__('This reduces the length of the URL.', 'siteseo').'</p>
			</div>';

			do_action('siteseo_gsc_onboarding');

			echo '<div class="siteseo-onboarding-content-footer">
				<button class="siteseo-skip-step siteseo-btn secondary">'.esc_html__('Skip Step', 'siteseo').'</button><button class="siteseo-btn primary siteseo-save-n-continue">'.esc_html__('Save and Continue', 'siteseo').'<span class="siteseo-spinner"></span></button>
			</div>
		</form>
		</div>';
	}
	
	static function ready_page(){
		$is_active = !empty(self::$current_step) && self::$current_step == 'ready' ? 'siteseo-step-active' : '';
		echo '<div class="siteseo-step-page '.esc_attr($is_active).'" data-step="ready">
		<span>Step '.esc_html(self::$current_step_no).' of '.count(self::$steps).'</span>
		<h1>Done! 🎉</h1>
		<p>'.esc_html__('We are done with the setup, now you can start making content and submit the Sitemap to the search engines.', 'siteseo').'</p>
		<h4>'.esc_html__('What Next?', 'siteseo').'</h4>
		<ol style="margin:0">
			<li><a href="?page=siteseo-sitemaps" target="_blank">'.esc_html__('Configure your Sitemap', 'siteseo').'</a></li>
			<li>'.esc_html__('Submit yours sitemap to search engines', 'siteseo').'</li>
		</ol>
		
		<h4>'.esc_html__('You can also, subscribe to our newletter', 'siteseo').'</h4>
		'.esc_html__('You will get', 'siteseo').'
		<ul style="list-style-type:none">
			<li><span class="dashicons dashicons-minus"></span> '.esc_html__('Alerted about Google Algorithm changes.', 'siteseo').'</li>
			<li><span class="dashicons dashicons-minus"></span> '.esc_html__('Updates about our products.', 'siteseo').'</li>
			<li><span class="dashicons dashicons-minus"></span> '.esc_html__('Improve SEO of your website with our resourceful blogs.', 'siteseo').'</li>
		</ul>
		<a class="siteseo-btn secondary" href="https://siteseo.io/subscribe/" style="align-self:flex-start;" target="_blank">'.esc_html__('Subscribe', 'siteseo').'</a>
		<div class="siteseo-onboarding-content-footer">
			<a href="'.esc_url(admin_url()).'"class="siteseo-btn primary">Go to Dashboard</a>
			<a href="?page=siteseo"class="siteseo-btn primary">Review Settings</a>
			<a href="https://siteseo.io/docs/" class="siteseo-btn primary" target="_blank">Knowledge Base</a>
		</div>
		</div>';
	}
}

