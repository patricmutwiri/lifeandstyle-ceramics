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

class Social{

    static function menu(){
        global $siteseo;

		$social_toggle = isset($siteseo->setting_enabled['toggle-social']) ? $siteseo->setting_enabled['toggle-social'] : '';
		$nonce = wp_create_nonce('siteseo_toggle_nonce');

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'tab_knowledge_graph';

        $social_subtabs = [
            'tab_knowledge_graph' => esc_html__('Knowledge Graph', 'siteseo'),
            'tab_social_accounts' => esc_html__('Your social accounts', 'siteseo'),
            'tab_facebook' => esc_html__('Facebook (Open Graph) ', 'siteseo'),
            'tab_twitter' => esc_html__('X Card', 'siteseo')
        ];
		
		echo '<div id="siteseo-root">';
		
		Util::admin_header();

        echo '<form method="post" id="siteseo-form" class="siteseo-option" name="siteseo-flush">';

        wp_nonce_field('siteseo_social_settings');

		Util::render_toggle('Social Networks - SiteSEO', 'social_toggle', $social_toggle, $nonce);

        echo '<div id="siteseo-tabs" class="wrap">
        <div class="siteseo-nav-tab-wrapper">';

        foreach($social_subtabs as $tab_key => $tab_caption){
			$active_class = ($current_tab === $tab_key) ? ' siteseo-nav-tab-active' : '';
			echo '<a id="' . esc_attr($tab_key) . '-tab" class="siteseo-nav-tab' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab_key) . '">' . esc_html($tab_caption) . '</a>';
        }

        echo '</div>
		<div class="tab-content-wrapper">
        <div class="siteseo-tab' .($current_tab == 'tab_knowledge_graph' ? ' active' : '').'" id="tab_knowledge_graph" style="display: none;">';
        self::knowledge_graph();
        echo '</div>
        <div class="siteseo-tab' .($current_tab == 'tab_social_accounts' ? ' active' : '').'" id="tab_social_accounts" style="display: none;">';
        self::social_accouts();
        echo '</div>
        <div class="siteseo-tab' .($current_tab == 'tab_twitter' ? ' active' : '').'" id="tab_twitter" style="display: none;">';
        self::twitter();
        echo '</div>
        <div class="siteseo-tab' .($current_tab == 'tab_facebook' ? ' active' : '').'" id="tab_facebook" style="display: none;">';
        self::facebook();
        echo '</div>
		</div>'; 

        Util::submit_btn();
        echo '</form></div>';
 
    }

    static function knowledge_graph(){
        global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

        $options = get_option('siteseo_social_option_name');

        //load data
        $option_org_type = !empty($options['social_knowledge_type']) ? $options['social_knowledge_type'] : '';
        $option_org_name = !empty($options['social_knowledge_name']) ? $options['social_knowledge_name'] : '';
        $option_org_logo = !empty($options['social_knowledge_img']) ? $options['social_knowledge_img'] : '';
        $option_org_number = !empty($options['social_knowledge_phone']) ? $options['social_knowledge_phone'] : '';
        $option_org_contact_type = !empty($options['social_knowledge_contact_type']) ? $options['social_knowledge_contact_type'] : '';
        $option_org_contact_option = !empty($options['social_knowledge_contact_option']) ? $options['social_knowledge_contact_option'] : '';

        echo '<h3 class="siteseo-tabs">'.esc_html__('Knowledge Graph','siteseo').'</h3>
        <p class="description">'.esc_html__('Set up Google Knowledge Graph.','siteseo').'</p>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Person or organization','siteseo').'</th>
                    <td>
                        <select name="siteseo_options[org_type]">
                            <option value="none" '.selected($option_org_type, 'none', false).'>'.esc_html__('None','siteseo').'</option>
                            <option value="Person" '.selected($option_org_type, 'Person', false).'>'.esc_html__('Person','siteseo').'</option>
                            <option value="Organization" '.selected($option_org_type, 'Organization', false).'>'.esc_html__('Organization','siteseo').'</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Your name/organization','siteseo').'</th>
                    <td>
                        <input type="text" name="siteseo_options[org_name]" value="'.esc_attr($option_org_name).'" placeholder="'.esc_html__('eg.Miremont','siteseo').'">
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Your photo/organization logo','siteseo').'</th>
                    <td>
                        <input id="knowledge_org_logo_url" autocomplete="off" type="text" name="siteseo_options[org_logo]" value="'.esc_url($option_org_logo).'" placeholder="'.esc_html__('select your logo','siteseo').'">
                        <button id="knowledge_org_logo" class="btn btnSecondary">'.esc_html__('Upload an image','siteseo').'</button>
 			<p class="description">'.esc_html__('JPG, PNG, WebP and GIF allowed.','siteseo').'</p><br />		
 			<img style="width:300px;max-height:400px;" src="'.esc_url($option_org_logo).'" />
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Organizations phone number (only for Organizations)','siteseo').'</th>
                    <td>
                        <input type="text" name="siteseo_options[org_contact_number]" value="'.esc_attr($option_org_number).'" placeholder="'.esc_html__('eg: +33123456789 (internationlized version required)','siteseo').'">
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Contact type (only for Organizations)','siteseo').'</th>
                    <td>
                        <select name="siteseo_options[org_contact_type]">
                            <option value="Customer support" '.selected($option_org_contact_type, 'Customer support', false).'>'.esc_html__('Customer support','siteseo').'</option>
                            <option value="Technical support" '.selected($option_org_contact_type, 'Technical support', false).'>'.esc_html__('Technical support','siteseo').'</option>
                            <option value="Billing support" '.selected($option_org_contact_type, 'Billing support', false).'>'.esc_html__('Billing support','siteseo').'</option>
                            <option value="Bill payment" '.selected($option_org_contact_type, 'Bill payment', false).'>'.esc_html__('Bill payment','siteseo').'</option>
                            <option value="Sales payment" '.selected($option_org_contact_type, 'Sales payment', false).'>'.esc_html__('Sales payment','siteseo').'</option>
                            <option value="Credit card support" '.selected($option_org_contact_type, 'Credit card support', false).'>'.esc_html__('Credit card support','siteseo').'</option>
                            <option value="Emergency support" '.selected($option_org_contact_type, 'Emergency support', false).'>'.esc_html__('Emergency support','siteseo').'</option>
                            <option value="Baggage tracking" '.selected($option_org_contact_type, 'Baggage tracking', false).'>'.esc_html__('Baggage tracking','siteseo').'</option>
                            <option value="Roadside assistance" '.selected($option_org_contact_type, 'Roadside assistance', false).'>'.esc_html__('Roadside assistance','siteseo').'</option>
                            <option value="Package tracking" '.selected($option_org_contact_type, 'Package tracking', false).'>'.esc_html__('Package tracking','siteseo').'</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Contact option (only for Organizations)','siteseo').'</th>
                    <td>
                        <select name="siteseo_options[org_contact_option]">
                            <option value="None" '.selected($option_org_contact_option, 'None', false).'>'.esc_html__('None','siteseo').'</option>
                            <option value="TollFree" '.selected($option_org_contact_option, 'TollFree', false).'>'.esc_html__('TollFree', 'siteseo').'</option>
                            <option value="HearingImpairedSupported" '.selected($option_org_contact_option, 'HearingImpairedSupported', false).'>'.esc_html__('Hearing Impaired Supported','siteseo').'</option>
                        </select>
                    </td>
                </tr>        
            </tbody>
        </table><input type="hidden" name="siteseo_options[knowledge_graph_tab]" value="1" >';
    }

    static function social_accouts(){
        global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		//$options = $siteseo->social_settings;
        $options = get_option('siteseo_social_option_name');

        //load settings
        $facebook_acct = !empty($options['social_accounts_facebook']) ? $options['social_accounts_facebook'] : '';
        $twitter_acct = !empty($options['social_accounts_twitter']) ? $options['social_accounts_twitter'] : '';
        $instagram_acct = !empty($options['social_accounts_instagram']) ? $options['social_accounts_instagram'] : '';
        $youtube_acct = !empty($options['social_accounts_youtube']) ? $options['social_accounts_youtube'] : '';
        $pinterest_acct = !empty($options['social_accounts_pinterest']) ? $options['social_accounts_pinterest'] : '';
        $additional_acct = !empty($options['social_accounts_additional']) ? implode("\n", $options['social_accounts_additional']) : '';

        echo '<h3 class="siteseo-tabs">'.esc_html__('Your social accouts', 'siteseo').'</h3>
         <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Facebook', 'siteseo').'</th>
                    <td>
                        <input type="text" name="siteseo_options[facebook]" placeholder="'.esc_html__('eg: https://facebook.com/my-page-url','siteseo').'" value="'.esc_url($facebook_acct).'">
                    </td>
                </tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('X Username', 'siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[twitter]" placeholder="'.esc_html__('eg : x_username','siteseo').'" value="'.esc_attr($twitter_acct).'">
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Pinterest URL', 'siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[pinterest]" placeholder="'.esc_html__('eg : https://pinterest.com/my-page-url/','siteseo').'" value="'.esc_url($pinterest_acct).'">
					</td>
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Instagram URL', 'siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[instagram]" placeholder="'.esc_html__('eg : https://www.instagram.com/my-page-url/','siteseo').'" value="'.esc_url($instagram_acct).'">
					</td>   
				</tr>

				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('YouTube URL', 'siteseo').'</th>
					<td>
						<input type="text" name="siteseo_options[youtube]" placeholder="'.esc_html__('eg : https://www.youtube.com/my-channel-url/','siteseo').'" value="'.esc_url($youtube_acct).'">
					</td>   
				</tr>
				<tr>
					<th scope="row" style="user-select:auto;">'.esc_html__('Additional Accounts', 'siteseo').'</th>
					<td>
						<textarea name="siteseo_options[additional]" placeholder="'.esc_html__('eg : https://somesite.com/my-channel-url/','siteseo').'">'.esc_textarea($additional_acct).'</textarea>
						<p class="description">'.esc_html__('Enter 1 URL per line.', 'siteseo').'</p>
					</td>
				</tr>
 
            </tbody>
        </table><input type="hidden" name="siteseo_options[social_account_tab]" value="1">';
    }

    static function twitter(){
        global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

		//$options = $siteseo->social_settings;
        $options = get_option('siteseo_social_option_name');

        //load data
        $option_enable_card = !empty($options['social_twitter_card']) ? $options['social_twitter_card'] : '';
	$option_image_size = !empty($options['social_twitter_card_img_size']) ? $options['social_twitter_card_img_size'] : '';
	$option_twitter_img = !empty($options['social_twitter_card_img']) ? $options['social_twitter_card_img'] : '';

        echo '<h3 class="siteseo-tabs">'.esc_html__('X Card','siteseo').'</h3>
        <p class="description">'.esc_html__('Manage your X card','siteseo').'</p>

        <div class="siteseo-notice">
            <span class="dashicons dashicons-info"></span>
            <div>
                <p>'.
		/* translators: placeholders are just <strong> tag */ 
		wp_kses_post(sprintf(__('We generate the %1$s og:image %2$s meta in the following order:', 'siteseo'), '<strong>', '</strong>')).'</p>
                <ol>
                    <li>'.esc_html__('Custom OG Image from the SEO metabox', 'siteseo').'</li>
                    <li>'.esc_html__('Post thumbnail / Product category thumbnail (Featured image)', 'siteseo').'</li>
                    <li>'.esc_html__('First image of your post content', 'siteseo').'</li>
                    <li>'.esc_html__('Global OG Image set in SEO > Social > Open Graph', 'siteseo').'</li>
                    <li>'.esc_html__('Site icon from the Customizer', 'siteseo').'</li>
                </ol>
            </div>
        </div>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" style="user:select-auto;">'.esc_html__('X Card','siteseo').'</th>
                    <td>'.esc_html__('Manage your X Card','siteseo').'</td>
                </tr>

                <tr>
                    <th scope="row" style="user:select-auto;">'.esc_html__('Enable X Card','siteseo').'</th>
                    <td>
                       <label for="enable_twitter_card"><input id="enable_twitter_card" type="checkbox" name="siteseo_options[enable_twitter_card]" '.(!empty($option_enable_card) ? 'checked="checked"' : 'value="1"') . '>'.esc_html__('Enable X card', 'siteseo') .'</label>
                    </td>
                </tr>
				
                <tr>
                    <th scope="row" style="user:select-auto;">'.esc_html__('Default X Image','siteseo').'</th>
                    <td>
                        <input type="text" id="twitter_logo_url" autocomplete="off" name="siteseo_options[twitter_img]" value="'.esc_url($option_twitter_img).'" placeholder="'.esc_html__('Choose your default thumbnail.','siteseo').'">
						<button id="twitter_logo" class="btn btnSecondary">'.esc_html__('Upload a image','siteseo').'</button>
						<p class="description">'.esc_html__('Minimum size: 144x144px (300x157px with large card), recommended aspect ratio 1:1 (2:1 for large card), maximum file size 5MB.','siteseo').'</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user:select-auto;">'.esc_html__('X Card Image Size','siteseo').'</th>
                    <td>
                        <select name="siteseo_options[image_size]">
                            <option value="Default" '.selected($option_image_size, 'Default', false).'>'.esc_html__('Default','siteseo').'</option>
                            <option value="Large" '.selected($option_image_size, 'Large', false).'>'.esc_html__('Large','siteseo').'</option>
                        </select>
                    </td>
                </tr>
				
            </tbody>
        </table><input type="hidden" name="siteseo_options[twitter_tab]" value="1">';
    }

    static function facebook(){
        global $siteseo;

        if(!empty($_POST['submit'])){
            self::save_settings();
        }

        // load seetings
		//$options = $siteseo->social_settings;
        $options = get_option('siteseo_social_option_name');

        $option_fb_enable_og = !empty($options['social_facebook_og']) ? $options['social_facebook_og'] : '';
        $option_fb_img = !empty($options['social_facebook_img']) ? $options['social_facebook_img'] : '';
        $option_fb_defult_img = !empty($options['social_facebook_img_default']) ? $options['social_facebook_img_default'] : '';
        $option_fb_ownership = !empty($options['social_facebook_link_ownership_id']) ? $options['social_facebook_link_ownership_id'] : '';
        $option_fb_admin_id = !empty($options['social_facebook_admin_id']) ? $options['social_facebook_admin_id'] : '';

        echo '<h3 class="siteseo-tabs">'.esc_html__('Facebook (Open Graph)','siteseo').'</h3>
        <p class="description">'.esc_html__('Manage Open Graph data to enhance how your links appear on platforms like Facebook, Pinterest, LinkedIn, and WhatsApp when shared. Improve your click-through rate by including relevant details, such as an attention-grabbing image.','siteseo').'<p>
        
        <div class="siteseo-notice">
            <span class="dashicons dashicons-info"></span>
            <div>
                <p> '.
		/* translators: placeholders are just <strong> tag */ 
		wp_kses_post(sprintf(__('We generate the %1$s OG:image %2$s meta in the following sequence:', 'siteseo'), '<strong>', '</strong>')).'</p>
                <ol>
                    <li>'.esc_html__('Custom OG image from the SEO metabox', 'siteseo').'</li>
                    <li>'.esc_html__('Post thumbnail / Product category thumbnail (Featured image)', 'siteseo').'</li>
                    <li>'.esc_html__('First image in your post content', 'siteseo').'</li>
                    <li>'.esc_html__('Global OG:image set in SEO > Social > OG Card', 'siteseo').'</li>
                </ol>
            </div>
        </div>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Enable OG date','siteseo').'</th>
                    <td>
                        <label for="facebook_graph_enable">
                        <input id="facebook_graph_enable" type="checkbox" name="siteseo_options[enable_fb_og]" '.(!empty($option_fb_enable_og) ? 'checked="yes"' : 'value="1"').'>'. esc_html__('Enable OG data','siteseo') .'
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Default Image','siteseo').'</th>
                    <td>
                        <input id="facebook_org_image_url" autocomplete="off" type="text" name="siteseo_options[fb_image]" value="'.esc_url($option_fb_img).'" palceholder="'.esc_html__('Select your default thumbnail','siteseo').'">
                        <button id="facebook_upload_logo" class="btn btnSecondary">'.esc_html__('Upload a image','siteseo').'</button>
						<p class="description">'.esc_html__('Minimum size: 200x200px, ideal ratio 1.91:1, 8MB max. (e.g., 1640x856px or 3280x1712px for retina screens).','siteseo').'</p>
                        <p class="description">'.esc_html__('If no default image is set, we will use your site icon defined in the Customizer.','siteseo').'</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto;">'.esc_html__('Override Default Image','siteseo').'</th>
                    <td>
                        <div class="siteseo_wrap_label">
                            <label for="override_image_tag">
                            <input id="override_image_tag" type="checkbox" name="siteseo_options[fb_default_img]" '.(!empty($option_fb_defult_img) ? 'checked="yes"' : 'value="1"').' >'.esc_html__('Override all og:image tags with this default image, unless a custom og:image has been set in the SEO metabox.','siteseo').'
                            </label>
                        </div>
			<br /><div class="siteseo-notice is-warning"><p>'.
			/* translators: placeholders are just <strong> tag */ 
			wp_kses_post(sprintf(__('Please define a default %1$s OG image %2$s using the field above.', 'siteseo'), '<strong>','</strong>')).'<p></div>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto">'.esc_html__('Link Ownership ID','siteseo').'</th>
                    <td>
                        <input type="text" placeholder="0123456789" name="siteseo_options[fb_owership_id]" value="'.esc_attr($option_fb_ownership).'">
                        <p class="description">'.esc_html__('Enter one or more Facebook Page IDs linked to a URL to enable link editing and instant article publishing.','siteseo').'</p>
                        <div class="siteseo-styles pre"><pre>'.esc_html('<meta property="fb:pages" content="page ID"/>').'</pre></div>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="user-select:auto">'.esc_html__('Admin ID','siteseo').'</th>
                    <td>
                        <input type="text" placeholder="0123456789" name="siteseo_options[fb_admin_id]" value="'.esc_attr($option_fb_admin_id).'">
                        <p class="description">'.esc_html__('The ID (or a comma-separated list for properties that support multiple IDs) of an app, user of the app, or Page Graph API object.', 'siteseo').'</p>
                        <div class="siteseo-styles pre"><pre>'.esc_html('<meta property="fb:admins" content="admins ID"/>').'</pre></div>
                    </td>
                </tr>

            </tbody>
        </table><input type="hidden" name="siteseo_options[facebook_tab]" value="1">';

    }

    static function save_settings(){
        global $siteseo;

		check_admin_referer('siteseo_social_settings');

		if(!siteseo_user_can('manage_social') || !is_admin()){
			return;
		}

		$options = [];

		if(empty($_POST['siteseo_options'])){
			return;
		}

		if(isset($_POST['siteseo_options']['knowledge_graph_tab'])){

			$options['social_knowledge_type'] = isset($_POST['siteseo_options']['org_type']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['org_type'])) : '';
			$options['social_knowledge_name'] = isset($_POST['siteseo_options']['org_name']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['org_name'])) : '';
			$options['social_knowledge_img'] = isset($_POST['siteseo_options']['org_logo']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['org_logo'])) : '';
			$options['social_knowledge_phone'] = isset($_POST['siteseo_options']['org_contact_number']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['org_contact_number'])) : '';
			$options['social_knowledge_contact_type'] = isset($_POST['siteseo_options']['org_contact_type']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['org_contact_type'])) : '';
			$options['social_knowledge_contact_option'] = isset($_POST['siteseo_options']['org_contact_option']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['org_contact_option'])) : '';
		}

		if(isset($_POST['siteseo_options']['social_account_tab'])){
			$options['social_accounts_facebook'] = isset($_POST['siteseo_options']['facebook']) ? sanitize_url(wp_unslash($_POST['siteseo_options']['facebook'])) : '';
			$options['social_accounts_twitter'] = isset($_POST['siteseo_options']['twitter']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['twitter'])) : '';
			$options['social_accounts_instagram'] = isset($_POST['siteseo_options']['instagram']) ? sanitize_url(wp_unslash($_POST['siteseo_options']['instagram'])) : '';
			$options['social_accounts_youtube'] = isset($_POST['siteseo_options']['youtube']) ? sanitize_url(wp_unslash($_POST['siteseo_options']['youtube'])) : '';
			$options['social_accounts_pinterest'] = isset($_POST['siteseo_options']['pinterest']) ? sanitize_url(wp_unslash($_POST['siteseo_options']['pinterest'])) : '';
			$options['social_accounts_additional'] = isset($_POST['siteseo_options']['additional']) ? explode("\n", sanitize_textarea_field(wp_unslash($_POST['siteseo_options']['additional']))) : '';
			$options['social_accounts_additional'] = Util::clean_url($options['social_accounts_additional']); // We accept 1 url per line.
		}

		if(isset($_POST['siteseo_options']['facebook_tab'])){
			$options['social_facebook_og'] = isset($_POST['siteseo_options']['enable_fb_og']);
			$options['social_facebook_img'] = isset($_POST['siteseo_options']['fb_image']) ? sanitize_url(wp_unslash($_POST['siteseo_options']['fb_image'])) : '';
			$options['social_facebook_img_default'] = isset($_POST['siteseo_options']['fb_default_img']);
			$options['social_facebook_link_ownership_id'] = isset($_POST['siteseo_options']['fb_owership_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['fb_owership_id'])) : '';
			$options['social_facebook_admin_id'] = isset($_POST['siteseo_options']['fb_admin_id']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['fb_admin_id'])) : '';
		}

		if(isset($_POST['siteseo_options']['twitter_tab'])){
			$options['social_twitter_card'] = isset($_POST['siteseo_options']['enable_twitter_card']);
			$options['social_twitter_card_img'] = isset($_POST['siteseo_options']['twitter_img']) ? sanitize_url(wp_unslash($_POST['siteseo_options']['twitter_img'])) : '';
			$options['social_twitter_card_img_size'] = isset($_POST['siteseo_options']['image_size']) ? sanitize_text_field(wp_unslash($_POST['siteseo_options']['image_size'])) : '';
		}

        update_option('siteseo_social_option_name' , $options);
    }
}
