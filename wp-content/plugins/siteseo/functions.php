<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

function siteseo_admin_header(){
   \SiteSEO\Settings\Util::admin_header();
}

function siteseo_submit_button($value = ''){
	\SiteSEO\Settings\Util::submit_btn();
}

function siteseo_suggestions_variable(){
	return [
		'%%sep%%' => 'Separator',
		'%%sitetitle%%' => 'Site Title',
		'%%tagline%%' => 'Tagline',
		'%%post_title%%' => 'Post title',
		'%%post_excerpt%%' => 'Post excerpt',
		'%%post_content%%' => 'Post content / product description',
		'%%post_thumbnail_url%%' => 'Post thumbnail URL',
		'%%post_url%%' => 'Post url',
		'%%post_date%%' => 'Post date',
		'%%post_modified_date%%' => 'Post modified date',
		'%%post_author%%' => 'Post author',
		'%%post_category%%' => 'Post category',
		'%%post_tag%%' => 'Post_tag',
		'%%_category_title%%' => 'Category title',
		'%%_category_description%%' => 'Category description',
		'%%tag_title%%' => 'Tag title',
		'%%tag_description%%' => 'Tag description',
		'%%term_title%%' => 'Term title',
		'%%term_description%%' => 'Term description',
		'%%search_keywords%%' => 'Search keywords',
		'%%current_pagination%%' => 'Current number page',
		'%%page%%' => 'Page number with context',
		'%%cpt_plural%%' => 'Plural Post Type Archive name',
		'%%archive_title%%' => 'Archive_title',
		'%%archive_date%%' => 'Archive_date',
		'%%archive_date_day%%' => 'Day Archive date',
		'%%archive_date_month%%' => 'Month Archive title',
		'%%archive_date_month_name%%' => 'Month name Archive title',
		'%%archive_date_year%%' => 'Year Archive title',
		'%%_cf_your_custom_field_name%%' => 'Custom fields from post, page, post type and term taxonomy',
		'%%_ct_your_custom_taxonomy_slug%%' => 'Custom term taxonomy from post, page or post type',
		'%%wc_single_cat%%' => 'Single product category',
		'%%wc_single_tag%%' => 'Single product tag',
		'%%wc_single_short_desc%%' => 'Single product short description',
		'%%wc_single_price%%' => 'Single product price',
		'%%wc_single_price_exe_tax' => 'Single product price taxes excluded',
		'%%wc_sku%%' => 'Single SKU Product',
		'%%currentday%%' => 'Current day',
		'%%currentmonth%%' => 'Current month',
		'%%currentmonth_short%%' => 'Current month in 3 letter',
		'%%currentyear%%' => 'Current year',
		'%%currentdate%%' => 'Current date',
		'%%currenttime%%' => 'Current time',
		'%%author_first_name%%' => 'Author first name',
		'%%author_last_name%%' => 'Author last name',
		'%%author_website%%' => 'Author website',
		'%%author_nickname%%' => 'Author nickname',
		'%%author_bio%%' => 'Author biography',
		'%%_ucf_your_user_meta%%' => 'Custom User Meta',
		'%%currentmonth_num%%' => 'Current month in digital format',
		'%%target_keyword%%' => 'Target keywords',
		'%%wc_parent_cat%%' => 'Product Single Parent Category',
	];
}

function siteseo_suggestion_button(){

	$suggest_variable = siteseo_suggestions_variable();

	if(empty($suggest_variable)){
		return;
	}

	echo '<button class="tag-select-btn"><span id="icon" class="dashicons dashicons-arrow-down-alt2"></span></button>
	<div class="siteseo-suggestions-wrapper" style="position:relative;">
	<div class="siteseo-suggetion">
		<div class="siteseo-search-box-container">
			<input type="text" class="siteseo-search-box" placeholder="Search a tag...">
		</div>
		<div class="siteseo-suggestions-container">';
		foreach($suggest_variable as $key =>$value){
			echo '<div class="section">'.esc_html($value).'
				<div class="item">
					<div class="tag">'.esc_html($key).'</div>
				</div>
			</div>';
		}
	echo '</div>
	</div>
	</div>';
}

function siteseo_suggestion_button_metabox(){
    $suggest_variable = siteseo_suggestions_variable();

    if(empty($suggest_variable)){
        return;
    }

    return '<button class="siteseo-tag-select-btn" type="button">
            <span id="icon" class="dashicons dashicons-arrow-down-alt2"></span>
        </button>
		<div class="siteseo-suggestions-wrapper" style="position:relative;">
        <div class="siteseo-suggetion">
            <div class="siteseo-search-box-container">
                <input type="text" class="search-box" placeholder="Search a tag...">
            </div>
            <div class="siteseo-suggestions-container">' .
            implode('', array_map(function($key, $value){
                return '<div class="section">'.esc_html($value).'
                    <div class="item">
                        <div class="tag">'.esc_html($key).'</div>
                    </div>
                </div>';
            }, array_keys($suggest_variable), $suggest_variable)). 
            '</div>
        </div>
	</div>';
}

function siteseo_get_docs_links(){
	$siteseo_docs = [];

	$siteseo_docs = [
		'page_speed' => [
			'api' => SITESEO_DOCS . 'api-cli-dev/add-your-google-page-speed-insights-api-key-to-siteseo/',
			'google' => 'https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com',
		]
	];
	
	return $siteseo_docs;
}

function siteseo_universal_assets(){
	global $siteseo, $post;
	
	$post_id = isset($post->ID) ? $post->ID : get_the_ID();
	
	if(!current_user_can('edit_post', $post_id)){
		return;
	}

	// Checking if it is a block editor
	if(function_exists('get_current_screen')){
		$screen = get_current_screen();
		
		if(!empty($screen) && method_exists($screen, 'is_block_editor') && $screen->is_block_editor() === true){
			if(empty($siteseo->advanced_settings['appearance_universal_metabox'])){
				return;
			}
			
			$is_gutenberg = true;
		}
	}

	if (
		!empty($is_gutenberg) ||
		isset($_GET['fl_builder']) ||
		isset($_GET['elementor-preview']) ||
		isset($_GET['ct_builder']) ||
		isset($_GET['vc_editable']) ||
		isset($_GET['brizy_edit']) ||
		isset($_GET['tve']) ||
		isset($_GET['pagelayer-live']) ||
		(!empty(get_queried_object_id()) && is_admin_bar_showing()) // To show when user is viewing the page as a admin
		&& !is_category() && !is_tax() && !is_tag() // exclude
    ) {
		wp_enqueue_script('siteseo-universal-metabox', SITESEO_ASSETS_URL . '/js/universal-metabox.js', ['jquery'], SITESEO_VERSION);
		wp_localize_script('siteseo-universal-metabox', 'siteseo_universal', [
			'asset_url' => SITESEO_ASSETS_URL,
			'post_id' => $post_id,
			'site_url' => site_url(),
			'metabox_url' => admin_url('admin.php?page=siteseo-metabox-wizard'),
		]);

		if(!defined('SITEPAD') && defined('SITESEO_PRO_VERSION') && class_exists('\SiteSEOPro\AI')){
			add_action('wp_footer', '\SiteSEOPro\AI::modal');
		}
	}
}

function siteseo_post_types(){
	
	$args = ['show_ui' => true, 'public'  => true];

	$post_types = get_post_types($args, 'objects', 'and');
	unset(
		$post_types['attachment'],
		$post_types['elementor_library'],
		$post_types['customer_discount'],
		$post_types['cuar_private_file'],
		$post_types['cuar_private_page'],
		$post_types['ct_template'],
		$post_types['e-floating-buttons'],
		$post_types['pagelayer-template'],
		$post_types['hostim_footer'],
		$post_types['mega_menu']
	);
	
	return apply_filters('siteseo_post_types', $post_types);
	
}

function siteseo_user_can($cap){
	return current_user_can('manage_options') || current_user_can('siteseo_'. $cap);
}

function siteseo_user_can_metabox(){
	if(!is_user_logged_in()){
		return false;
	}
	
	global $siteseo;

	$metabox_roles = !empty($siteseo->advanced_settings['security_metaboxe_role']) ? $siteseo->advanced_settings['security_metaboxe_role'] : [];

	$user = wp_get_current_user();
	$user_role = current($user->roles);

	if(array_key_exists($user_role, $metabox_roles)){
		return false;
	}
	
	return true;
}

function siteseo_remove_elementor_description_meta_tag(){
	remove_action('wp_head', 'hello_elementor_add_description_meta_tag');
}

function siteseo_plugin_update_notice_filter($plugins = []){
	$plugins['siteseo/siteseo.php'] = 'SiteSEO';
	return $plugins;
}