<?php
/*
* SITESEO
* https://siteseo.io
* (c) SiteSEO Team
*/

namespace SiteSEO\Metaboxes;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Analysis{
	
	static function display_content_readibility($metabox_data){
		
		if(empty($metabox_data) || !isset($metabox_data['post_id'])){
			return; // Safeguard in case $metabox_data is incomplete or invalid.
		}

		$post_id = $metabox_data['post_id'];
		$readability_data = get_post_meta($post_id, '_siteseo_readibility_data', true);

		// Fetch and analyze readability data
		$analyzes = self::analyze_content($readability_data);

		// Render the readability data
		self::render_readibility($analyzes, $readability_data);
	}
	
	static function analyze_content($data){
		$analyzes = [];

		// Analyze different aspects
		$analyzes = self::analyze_power_word($analyzes, $data);
		$analyzes = self::analyze_title_number($analyzes, $data);
		$analyzes = self::analyze_passive_voice($analyzes, $data);
		$analyzes = self::analyze_paragraph_length($analyzes, $data);

		return $analyzes;
	}

	static function analyze_power_word($analyzes, $data){
		
		if(!empty($data['power_words'])){
			$analyzes['power_words'] = [
				'desc' => '<p>'.__('Good: You have power words in your title.', 'siteseo').'</p>',
				'impact' => 'good',
				'title'=>'Power Word in title'
			];
		} else{
			$analyzes['power_words'] = [
				'desc' => '<p>'.__('Consider adding power words to your title for better impact.', 'siteseo').'</p>',
				'impact' => 'low',
				'title'=> __('Power Word in title', 'siteseo')
			];
		}
		return $analyzes;
	}

	static function analyze_title_number($analyzes, $data){
		
		if(!empty($data['number_found'])){
			$analyzes['number_found'] = [
				/* translators: %s is the number found in the title */
				'desc' => '<p>'.sprintf(__('Good: Your title contains the number "%s".', 'siteseo'), $data['number_found']).'</p>',
				'impact' => 'good',
				'title' => 'Number in title'
			];
		} else{
			$analyzes['number_found'] = [
				'desc' => '<p>'.__('Consider adding a number to your title to improve CTR.', 'siteseo').'</p>',
				'impact' => 'low',
				'title' => 'Number in title'
			];
		}
		
		return $analyzes;
	}

	static function analyze_passive_voice($analyzes, $data){

		//$analyzes['title'] ='Passive Voice';
		if(empty($data['passive_voice'])){
			$analyzes['passive_voice'] = [
				'desc' => '<p>'.__('Great: No passive voice detected.', 'siteseo').'</p>',
				'impact' => 'good',
				'title' => 'Passive Voice'
			];
		} else {
		    $percentage = round(($data['passive_voice']['passive_sentences'] * 100) / $data['passive_voice']['total_sentences']);
		    if($percentage <= 10){
			$analyzes['passive_voice'] = [
				/* translators: %s is the number found in the title */
				'desc' => '<p>'.sprintf(__('Good: Only %s%% of sentences use passive voice.', 'siteseo'), $percentage).'</p>',
				'impact' => 'good',
				'title' => 'Passive Voice'
			];
		    } elseif($percentage < 20){
			$analyzes['passive_voice'] = [
				/* translators: %s is the percentage of sentences using passive voice */
				'desc' => '<p>'.sprintf(__('Okay: %s%% of sentences use passive voice. Try to reduce it.', 'siteseo'), $percentage).'</p>',
				'impact' => 'medium',
				'title' => 'Passive Voice'
			];
		    } else{
		        $analyzes['passive_voice'] = [
				/* translators: %s is the percentage of sentences using passive voice */
				'desc' => '<p>'.sprintf(__('High: %s%% of sentences use passive voice. Consider revising.', 'siteseo'), $percentage).'</p>',
				'impact' => 'high',
				'title' => 'Passive Voice'
		        ];
		    }
		}
		return $analyzes;
	}

	static function analyze_paragraph_length($analyzes, $data){

		if(empty($data['paragraph_length']) || $data['paragraph_length'] < 150){
		    $analyzes['paragraph_length'] = [
		        'desc' => '<p>Good: Your paragraphs are concise.</p>',
		        'impact' => 'good',
					'title' => 'Paragraph Length'
		    ];
		} else{
		    $analyzes['paragraph_length'] = [
			/* translators: %s is the current paragraph length in words */
		        'desc' => '<p>'.sprintf(__('Consider reducing paragraph length. Current length: %s words.', 'siteseo'), $data['paragraph_length']) . '</p>',
		        'impact' => 'low',
					'title' => 'Paragraph Length'
		    ];
		}
		return $analyzes;
	}

	static function render_readibility($analyzes, $analysis_data, $echo = true){
		$acceptable_svg = [
			'svg' => [
				'role' => true,
				'aria-hidden' => true,
				'focusable' => true,
				'width' => true,
				'height' => true,
				'viewbox' => true,
				'version' => true,
				'xmlns' => true,
				'fill' => true,
			],
			'path' => ['fill' => true, 'd' => true]
		];

		if(!empty($analyzes)){
			$order = ['1' => 'high', '2' => 'medium', '3' => 'low', '4' => 'good'];
			usort($analyzes, function ($a, $b) use ($order) {
				return array_search($a['impact'], $order) - array_search($b['impact'], $order);
			});

			// Define SVG icons
			$high_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>';
			$medium_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>';
			$good_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>';

			// Generate HTML
			$html = '<div id="siteseo-readibility-tabs">';
			foreach($analyzes as $key => $value){
				$impact_icon = '';
				switch ($value['impact']) {
					case 'high': $impact_icon = $high_icon; break;
					case 'medium': case 'low': $impact_icon = $medium_icon; break;
					case 'good': $impact_icon = $good_icon; break;
				}
				$html .= '<div class="siteseo-analysis-block">';
				$html .= '<div class="siteseo-analysis-block-title">';
				$html .= '<div><span class="impact ' . esc_attr($value['impact']) .'" aria-hidden="true">' . $impact_icon . '</span>' .
				/* translators: %s represents the degree of severity */
				'<span class="screen-reader-text">'. sprintf(esc_html__('Degree of severity: %s','siteseo'), esc_html($value['impact'])).'</span>' .
				esc_html($value['title']) . '</div>';
				$html .= '<span class="siteseo-arrow" aria-hidden="true"></span></div>';
				$html .= '<div class="siteseo-analysis-block-content">' . wp_kses_post($value['desc']) . '</div>';
				$html .= '</div>';
			}
			$html .= '</div>';
			
			if($echo){
				$allowed_html = array_merge(wp_kses_allowed_html('post'), $acceptable_svg);
				echo wp_kses($html, $allowed_html);
				return;
			}
			return $html;
		}
	}

	// Analaysis
	static function display_seo_analysis($post){
		$seo_analysis = self::perform_seo_analysis($post);
		
		echo '<div id="siteseo-analysis-tabs">
			<div id="siteseo-analysis-tabs-1">
				<div class="siteseo-analysis-summary">';
					if(!empty($seo_analysis)){
						// grp
						usort($seo_analysis['checks'], function ($a, $b) {
							$order = ['error' => 0, 'warning' => 1, 'good' => 2];
							
							$a_status_class = isset($a['status_class']) ? $a['status_class'] : '';
							$b_status_class = isset($b['status_class']) ? $b['status_class'] : '';
							
							$a_order = isset($order[$a_status_class]) ? $order[$a_status_class] : 3;
							$b_order = isset($order[$b_status_class]) ? $order[$b_status_class] : 3;
							
							return $a_order - $b_order;
						});

						echo '<div class="siteseo-analysis-summary-pill">';
							// counts logic
							if(!empty($seo_analysis['error_count'])){
								echo '<span><svg fill="#f33" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M24 22h-24l12-20z"/></svg>'.esc_html($seo_analysis['error_count']). ' Errors</span>';
							}
						
							if(!empty($seo_analysis['warning_count'])){
								echo '<span><svg xmlns="http://www.w3.org/2000/svg" fill="#fa3" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96z"/></svg>'.esc_html($seo_analysis['warning_count']). ' Warnings</span>';
							}
							
							if(!empty($seo_analysis['good_count'])){
								echo '<span><svg xmlns="http://www.w3.org/2000/svg" fill="#0c6" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512z"/></svg><span>'.esc_html($seo_analysis['good_count']). ' Good</span></span>';
							}
					
						echo '</div>
					</div><!-- .analysis-score -->';

				 // A triangle with exclamation in it.
				$medium_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>';

				 // A check inside a solid circle
				$good_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>';
				
				$high_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>';
				
				$allowed_svg_tags = ['svg' => ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true], 'path' => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true]];

			   foreach($seo_analysis['checks'] as $check){

						echo '<div class="siteseo-analysis-block">';
							if(isset($check['label'])){
								echo'<div class="siteseo-analysis-block-title">';
									if(isset($check['status_class'])){
										$impact_icon = '';
										switch($check['status_class']){
											case 'good':
												$impact_icon = $good_icon;
												break;
											case 'warning':
												$impact_icon = $medium_icon_svg;
												break;
											case 'error':
												$impact_icon = $high_icon_svg;
												break;
										}

										echo '<div><span class="impact '.esc_attr($check['status_class']).'" aria-hidden="true">'.wp_kses($impact_icon, $allowed_svg_tags).'</span>
										
										<span class="screen-reader-text">'.
										/* translators: %s represents the degree of severity */
										sprintf(esc_html__('Degree of severity: %s','siteseo'), esc_html($check['status_class'])).'</span>';
									}

									echo esc_html($check['label']).'</div>
									<span class="siteseo-arrow" aria-hidden="true"></span>
								</div>';
							}
							if(isset($check['details'])){
								echo '<div class="siteseo-analysis-block-content" aria-hidden="true">'.wp_kses_post($check['details']).'</div>';
							}
						echo '</div><!-- .siteseo-analysis-block -->';
					}

				echo '</div><!-- #siteseo-analysis-tabs-1 -->
				</div><!-- #siteseo-analysis-tabs -->';
			}

	}

	static function perform_seo_analysis($post){
		
		$content = $post->post_content;
		$title = $post->post_title;
		$title = !empty(get_post_meta($post->ID, '_siteseo_titles_title',true)) ? get_post_meta($post->ID, '_siteseo_titles_title', true) : $title;
		$permalink = get_permalink($post->ID);
		$keywords = get_post_meta($post->ID, '_siteseo_analysis_target_kw', true);
		$meta_desc = get_post_meta($post->ID, '_siteseo_titles_desc', true);
		
		// Bricks
		if(defined('BRICKS_DB_PAGE_CONTENT')){
			$is_bricks_page = get_post_meta($post->ID, BRICKS_DB_PAGE_CONTENT, true);

			if(!empty($is_bricks_page) && is_array($is_bricks_page)){
				$content = self::get_bricks_page_content($is_bricks_page);
			}
		}
			
		if(empty($meta_desc)){
			$meta_desc = '';
		}

		$analysis = [
			'good_count' => 0,
			'warning_count' => 0,
			'error_count' => 0,
			'checks' => []
		];

		$canonical_check = self::check_canonical_url($permalink);
		$analysis['checks'][] = $canonical_check;
		self::update_analysis_score($analysis, $canonical_check);

		$word_count_check = self::check_word_count($content);
		$analysis['checks'][] = $word_count_check;
		self::update_analysis_score($analysis, $word_count_check);

		$keywords_density_check = self::check_keywords_density($content, $keywords);
		$analysis['checks'][] = $keywords_density_check;
		self::update_analysis_score($analysis, $keywords_density_check);

		$meta_title_check = self::check_meta_title($title);
		$analysis['checks'][] = $meta_title_check;
		self::update_analysis_score($analysis, $meta_title_check);

		$meta_description_check = self::check_meta_description($content, $meta_desc);
		$analysis['checks'][] = $meta_description_check;
		self::update_analysis_score($analysis, $meta_description_check);

		$image_alt_check = self::check_image_alt_texts($content);
		$analysis['checks'][] = $image_alt_check;
		self::update_analysis_score($analysis, $image_alt_check);

		$links_outbound_check = self::analyze_outbound_links($content);
		$analysis['checks'][] = $links_outbound_check;
		self::update_analysis_score($analysis, $links_outbound_check);

		$links_internal_check = self::analyze_internal_links($content);
		$analysis['checks'][] = $links_internal_check;
		self::update_analysis_score($analysis, $links_internal_check);

		$headings_check = self::check_headings($content);
		$analysis['checks'][] = $headings_check;
		self::update_analysis_score($analysis, $headings_check);

		$social_tags_check = self::check_social_meta_tags($post);
		$analysis['checks'][] = $social_tags_check;
		self::update_analysis_score($analysis, $social_tags_check);

		$structured_data_check = self::check_structured_data($post);
		$analysis['checks'][] = $structured_data_check;
		self::update_analysis_score($analysis, $structured_data_check);

		$permalink_keywords_check = self::check_keywords_in_permalink($permalink, $keywords);
		$analysis['checks'][] = $permalink_keywords_check;
		self::update_analysis_score($analysis, $permalink_keywords_check);

		$meta_robots_check = self::check_meta_robots($post);
		$analysis['checks'][] = $meta_robots_check;
		self::update_analysis_score($analysis, $meta_robots_check);

		$last_modified_check = self::check_last_modified_date($post);
		$analysis['checks'][] = $last_modified_check;
		self::update_analysis_score($analysis, $last_modified_check);

		$nofollow_links_check = self::analyze_nofollow_links($content);
		$analysis['checks'][] = $nofollow_links_check;
		self::update_analysis_score($analysis, $nofollow_links_check);
		
		$readability_data = [];
		$readability_data = self::analyze_readability($content, $title);
		update_post_meta($post->ID, '_siteseo_readibility_data', $readability_data);
		$analysis['checks'][] = $readability_data;

		return $analysis;
	}
	
	static function update_analysis_score(&$analysis, $check){
		switch($check['status']){
		    case 'Good':
		        $analysis['good_count']++;
		        break;
		    case 'Warning':
		        $analysis['warning_count']++;
		        break;
		    case 'Error':
		        $analysis['error_count']++;
		        break;
		}
	}
	
	static function check_canonical_url($permalink){
		$response = wp_remote_get($permalink);
		if(is_wp_error($response)){
			return [
				'label' => 'Canonical URL',
				'status' => 'Error',
				'status_class' => 'error',
				'details' => '<p>' . __('Unable to check canonical URL.', 'siteseo') . '</p>'
			];
		}
		
		$content = wp_remote_retrieve_body($response);
		
		preg_match_all('/<link[^>]+rel=[\'"](canonical)[\'"][^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
		
		$canonical_urls = !empty($matches[2]) ? array_unique($matches[2]) : [];
		$count = count($canonical_urls);
		
		$details = '';
		$status = 'Warning';
		
		$details .= '<p>'. __('A canonical URL is required by search engines to handle duplicate content.', 'siteseo') .'</p>';
		
		if($count > 0){
			
			$details .= '<p>' .
			
			sprintf(
				/* translators: %s represents the degree of severity */
				_n(
					'We found %s canonical URL in your source code. Below, the list:',
					'We found %s canonical URLs in your source code. Below, the list:',
					$count,
					'siteseo'
				),
				number_format_i18n($count)
			) . '</p>';
			
			$details .= '<ul>';
			foreach($canonical_urls as $link){
				$details .= '<li>' .
					'<span class="dashicons dashicons-arrow-right"></span>' .
					'<a href="' . esc_url($link) . '" target="_blank">' . 
					esc_url($link) . 
					'</a>' .
					'<span class="dashicons dashicons-external"></span>' .
					'</li>';
			}
			$details .= '</ul>';
			
			if($count > 1){
				$status = 'Error';
				$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
					__('You must fix this. Canonical URL duplication is bad for SEO.', 'siteseo') . '</p>';
			} else{
				$status = 'Good';
			}
		} else{
			if(get_post_meta(get_the_ID(), '_siteseo_robots_index', true)){
				$status = 'Good';
				$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>' . 
					__('This page doesn\'t have any canonical URL because your post is set to <strong>noindex</strong>. This is normal.', 'siteseo') . '</p>';
			} else{
				$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
					__('This page doesn\'t have any canonical URL.', 'siteseo') . '</p>';
			}
		}
		
		return [
			'label' => 'Canonical URL',
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}
	
	static function check_word_count($content){
		$word_count = str_word_count(wp_strip_all_tags($content));
		$unique_words = count(array_unique(str_word_count(wp_strip_all_tags($content), 1)));
		
		$details = '';
		
		if($word_count != 0){
			
			$details = '<p>'. __('Word count isn\'t a direct ranking factor, but it\'s important for your content to be high-quality, relevant, and unique. To meet these criteria, your article should include a sufficient number of paragraphs to ensure adequate word count.', 'siteseo') .'</p>';
		
			$details .= '<ul>';
			/* translators: %d represents the words found */
			$details .= '<li>'. sprintf(__('%d words found', 'siteseo'), $word_count) .'</li>';
			/* translators: %d represents the unique words found */
			$details .= '<li>'. sprintf(__('%d unique words found', 'siteseo'), $unique_words) .'</li>';
			$details .= '</ul>';
				
		}
		
		if($word_count == 0){
			$status = 'Error';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('No content? Try adding a few more paragraphs.!', 'siteseo') . '</p>';
		} elseif($word_count > 300){
			$status = 'Good';
			$details .= '<li><span class="dashicons dashicons-thumbs-up"></span>' . __('Your content contains over 300 words, which meets the minimum requirement for a post', 'siteseo') .'</li>';
		} else{
			$status = 'Error';
			$details .= '<li><span class="dashicons dashicons-thumbs-down"></span>' . __('Your content is too brief. Consider adding a few more paragraphs!', 'siteseo') .'</li>';
		}

		return [
			'label' => __('Word Count', 'siteseo'),
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}

	static function check_keywords_density($content, $keywords){
		$content = mb_strtolower(wp_strip_all_tags($content), 'UTF-8');
		$keywords = array_filter(explode(',', trim($keywords)));
		$content_words = str_word_count($content, 1);
		$count_words = count($content_words);
		$details = '';

		if(empty($keywords) || empty($content_words)){
			$details .= '<p>' . __('We couldn\'t calculate the keyword density. This may be because you haven\'t added any content or your target keywords are missing from the post.', 'siteseo') . '</p>';
			
			return [
				'label' => __('Keyword Density', 'siteseo'),
				'status' => 'Error',
				'status_class' => 'error',
				'details' => $details
			];
		}

		// calulate
		$density_details = [];
		$all_density = [];
    
		foreach($keywords as $keyword){
			$keyword_occurrence = 0;

			$keyword = mb_strtolower(trim($keyword), 'UTF-8');

			$keyword_words = preg_split('/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY);
			$keyword_word_count = count($keyword_words);

			// Count occurrences
			if($keyword_word_count > 1){

				$pattern = '/' . preg_quote($keyword, '/') . '/iu';
				preg_match_all($pattern, $content, $matches);
				$keyword_occurrence = count($matches[0]);
			} else {
				$word_counts = array_count_values($content_words);
				$keyword_occurrence = isset($word_counts[$keyword]) ? $word_counts[$keyword] : 0;

			}

			// Calculate density
			if($count_words > 0){
				$kw_density = ($keyword_occurrence * $keyword_word_count * 100) / $count_words;
			} else {
				$kw_density = 0;
			}

			$all_density[] = number_format($kw_density, 2);
			
			$density_details[] = 
			/* translators: %s represents the degree of severity */ 
			sprintf(
				'<ul><li><span class="dashicons dashicons-arrow-right"></span>%s</li></ul>',
				sprintf(
					/* translators: %s represents a keyword density of */
					esc_html__('%1$s was found %2$d times in your content, a keyword density of %3$s%%', 'siteseo'),
					esc_html($keyword),
					$keyword_occurrence,
					number_format($kw_density, 2)
				)
			);
		}

		$details .= implode('', $density_details);

		$details .= '<p class="description">'.
			sprintf(
				__('Find out more about <a href="%s" target="_blank">keywords stuffing</a>', 'siteseo'),
				'https://www.youtube.com/watch?v=Rk4qgQdp2UA'
			) .
		'</p>';

		if(count($all_density) === 0){
			return [
				'label' => __('Keyword Density', 'siteseo'),
				'status' => 'Error',
				'status_class' => 'error',
				'details' => $details
			];
		}

		//avg density
		$avg_density = array_sum($all_density)/count($all_density);

		$status = ($avg_density > 1) ? 'Good' : 'Warning';

		return [
			'label' => __('Keyword Density', 'siteseo'),
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}
	
	static function check_meta_title($title, $keywords = []){
		$details = '';
		$status = 'Good';
		$status_class = 'good';
		
		$title = \SiteSEO\TitlesMetas::replace_variables($title);

		// mb_strlen for correct UTF-8
		$title_length = mb_strlen($title, 'UTF-8');

		// length
		if(empty($title)){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
			__('A custom title has not been set for this post. If the global meta title works for you, you can disregard this recommendation.', 'siteseo') . '</p>';
			$status = 'Warning';
			$status_class = 'warning';
		} elseif($title_length > 60){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' .
			__('Your custom title is too lengthy.', 'siteseo') . '</p>';
			$status = 'Warning';
			$status_class = 'warning';
		} elseif($title_length >= 10 && $title_length <= 60){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>' .
			__('The length of your title is appropriate.', 'siteseo') . '</p>';
		} else{
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
			__('Your custom title is too short.', 'siteseo') . '</p>';
			$status = 'Warning';
			$status_class = 'warning';
			
		}

		if(!empty($keywords)){
			$keyword_counts = [];
			foreach($keywords as $kw_name){
				$kw_count = substr_count(strtolower($title), strtolower($kw_name));
				if($kw_count > 0){
					$keyword_counts[] = $kw_count;
					/* translators: %s represents the degree of severity */
					$details .= '<ul><li><span class="dashicons dashicons-arrow-right"></span>'. sprintf(esc_html__('%1$s was found %2$d times.', 'siteseo'), $kw_name, $kw_count) . '</li></ul>';
				}
			}

			if(!empty($keyword_counts)){
				$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'.__('The target keywords are included in the Meta Title', 'siteseo').'</p>';
			} else {
				$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'.__('None of your target keywords are present in the Meta Title.', 'siteseo').'</p>';
				$status = $status === 'Good' ? 'Warning' : $status;
				$status_class = $status_class === 'good' ? 'warning' : $status_class;
			}
		}

		return [
			'label' => __('Meta Title', 'siteseo'),
			'status' => $status,
			'status_class' => $status_class,
			'details' => $details
		];
	}

	static function check_meta_description($content, $meta_description = '', $keywords = []){
		$details = '';
		$status = 'Good';
		$status_class = 'good';

		$desc = !empty($meta_description) ? $meta_description : wp_trim_words($content, 20);
		$description = \SiteSEO\TitlesMetas::replace_variables($desc);

		// UTF-8
		$description_length = mb_strlen($description, 'UTF-8');

		// desc length
		if(empty($meta_description)){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
				__('A custom meta description has not been set for this post. If the global meta description works for you, you can ignore this recommendation.', 'siteseo') . '</p>';
				
			$status = 'Warning';
			$status_class = 'warning';
		} elseif($description_length > 160){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('Your custom meta description is too lengthy', 'siteseo') .'</p>';
			$status = 'Warning';
			$status_class = 'warning';
		} elseif($description_length >= 50 && $description_length <= 160){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('The length of your meta description is appropriate.', 'siteseo').'</p>';
		} else{
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('The description has been set properly.', 'siteseo').'</p>';
		}

		if(!empty($keywords)){
			$keyword_counts = [];
			foreach($keywords as $kw_name){
				$kw_count = substr_count(strtolower($description), strtolower($kw_name));
				if($kw_count > 0){
					$keyword_counts[] = $kw_count;
					$details .= '<li><span class="dashicons dashicons-arrow-right"></span>' . 
						/* translators: %s represents the key word count */
						sprintf(esc_html__('%1$s was found %2$d times.', 'siteseo'), $kw_name, $kw_count) . '</li>';
				}
			}

			if(!empty($keyword_counts)){
				$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('The target keywords are included in the Meta description.', 'siteseo') . '</p>';
			} else{
				$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('None of your target keywords are included in the Meta description.', 'siteseo') . '</p>';
				$status = $status === 'Good' ? 'Warning' : $status;
				$status_class = $status_class === 'good' ? 'warning' : $status_class;
			}
		}

		return [
			'label' => 'Meta Description',
			'status' => $status,
			'status_class' => $status_class,
			'details' => $details
		];
	}
	
	static function check_image_alt_texts($content){

			$result = [
				'label' => __('Alternative texts of images', 'siteseo'),
				'status' => 'Good',
				'status_class' => 'good',
				'details' => ''
			];

			if(empty($content)){
				$result['status'] = 'Warning';
				$result['status_class'] = 'warning';
				$result['details'] = '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
					__('No content found to analyze. Please add some content to check for images and alt texts.', 'siteseo') . '</p>';
				return $result;
			}

			preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $image_matches);
			preg_match_all('/<img[^>]+alt=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $alt_matches);

			$images_count = count($image_matches[0]);
			$alt_text_count = count(array_filter($alt_matches[1], 'strlen'));

			if($images_count === 0){
				$result['status'] = 'Warning';
				$result['status_class'] = 'warning';
				$result['details'] = '<p><span class="dashicons dashicons-thumbs-down"></span>' .
					__('We couldn\'t find any images in your content. Adding media can boost your SEO.', 'siteseo') . '</p>';
				return $result;
			}

			if($images_count !== $alt_text_count){
				$result['status'] = ($alt_text_count > 0) ? 'Warning' : 'Error';
				$result['status_class'] = strtolower($result['status']);
				
				$result['details'] = '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
					esc_html__('No alternative text has been found for these images. Alt tags are essential for SEO and accessibility. Please edit your images in the media library or using your preferred page builder, and provide alternative text.', 'siteseo') . '</p>';
				
	
				if(!empty($image_matches[1])){
					$result['details'] .= '<ul class="attachments">';
					foreach($image_matches[1] as $index => $img){
						if(empty($alt_matches[1][$index])){
							$result['details'] .= '<li class="attachment"><figure>' .
								'<img src="' . esc_url($img) . '"/>' .
								'<figcaption style="word-break: break-all;">' . esc_html($img) . '</figcaption>' .
								'</figure></li>';
						}
					}
					$result['details'] .= '</ul>';
				}
				
				$result['details'] .= '<p>'. __('Please note that we scan all your source code, which means some missing alternative text for images may be found in your header, sidebar, or footer.', 'siteseo') .'</p>';
			} else{
    
				$result['details'] = '<p><span class="dashicons dashicons-thumbs-up"></span>' . 
					__('All alternative tags have been completed. Great job!', 'siteseo') .'</p>';
			}

			return $result;
    	}
	

	static function analyze_outbound_links($content){

		preg_match_all('/<a[^>]+href=([\'"])(?!#)([^\'"]+)[\'"][^>]*>/i', $content, $links);

		$outbound_links = array_filter($links[2], function($link){
			return strpos($link, get_site_url()) === false;
		});
		
		$total_outbound = count($outbound_links);
		$nofollow_count = preg_match_all('/rel=[\'"]nofollow[\'"]/', implode(' ', $links[0]));
		
		$status = $total_outbound > 0 ? 'Good' : 'Warning';
		
		$details = '';
		
		$details .= '<p>'. __('The internet is based on the concept of hyperlinks, so linking to different websites is completely natural. However, avoid linking to low-quality or spammy sites. If youre uncertain about a site quality, add the "nofollow" attribute to your link.', 'siteseo') .'</p>';
		
		if($total_outbound > 0){
			/* translators: %s represents the detected outbound links on page  */
			$details .= '<p>'.sprintf(__('We detected %s outbound links on your page. Below is the list.', 'siteseo'), $total_outbound) .'</p>';
			$details .= '<ul>';
			
			foreach($outbound_links as $link){
				$details .= '<li><span class="dashicons dashicons-arrow-right"></span>';
				$details .= '<a href="'.esc_url($link).'" target="_blank">'.esc_url($link).'</a></li>';
			}
			
			$details .= '</ul>';
		} else{
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
				__('This page does not contain any outbound links.', 'siteseo') . '</p>';
		}
		
		return [
			'label' => __('Outbound Links', 'siteseo'),
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}
	
	static function analyze_internal_links($content){

		preg_match_all('/<a[^>]+href=([\'"])(?!#)([^\'"]+)[\'"][^>]*>(.*?)<\/a>/i', $content, $links, PREG_SET_ORDER);

		$internal_links = array_filter($links, function($link) {
			return strpos($link[2], get_site_url()) !== false;
		});
		
		$total_internal = count($internal_links);

		$status = $total_internal > 0 ? 'Good' : 'Warning';
		
		$details = '';

		$details .= '<p>'. __('Internal links are crucial for both SEO and user experience. Always aim to interconnect your content using meaningful and relevant anchor text.', 'siteseo') .'</p>';
		
		if($total_internal > 0){
			/* translators: %s represents the internal links pointing to page */
			$details .= '<p>'. sprintf(__('We identified %s internal links pointing to this page.', 'siteseo'), $total_internal) .'</p>';
			$details .= '<ul>';
	
			foreach($internal_links as $link){
				$url = $link[2];
				$post_id = url_to_postid($url);
				
				$details .= '<li><span class="dashicons dashicons-arrow-right"></span>';
				$details .= '<a href="'.esc_url($url) .'" target="_blank">'. esc_html($url) . '</a>';
				
				if($post_id){
					$details .= '<a class="nounderline" href="' . get_edit_post_link($post_id) . '" ' .
							   'title="' . 
							   /* translators: %s represents the degree of severity */
							   sprintf(__('edit %s', 'siteseo'), esc_html(get_the_title($post_id))) . '">' .
							   '<span class="dashicons dashicons-edit-large"></span></a>';
				}
				
				$details .= '</li>';
			}
			
			$details .= '</ul>';
		} else{
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
				__('This page has no internal links from other content. Links from archive pages are not counted as internal links because they lack contextual relevance.', 'siteseo') . '</p>';
		}
		
		return [
			'label' => __('Internal Links', 'siteseo'),
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}
	
	static function analyze_nofollow_links($content){
	
		preg_match_all('/<a[^>]+href=([\'"])([^\'"]+)[\'"][^>]*>(.*?)<\/a>/i', $content, $all_links, PREG_SET_ORDER);
		
		$nofollow_links = array_filter($all_links, function($link){
			return preg_match('/rel=[\'"][^\'"]*nofollow[^\'"]*[\'"]/', $link[0]);
		});
		
		$total_nofollow = count($nofollow_links);

		$status = $total_nofollow > 0 ? 'Warning' : 'Good';
		
		$details = '';
		
		if($total_nofollow > 0){
			
			$details .= '<p>' .
			/* translators: %d represents the number nofollow attribute */ 
			sprintf( esc_html__('We found %d links with the nofollow attribute on your page. Avoid overusing the nofollow attribute in links. Below is the list:', 'siteseo'),
				$total_nofollow
			) . '</p>';
			
			$details .= '<ul>';
			
			foreach($nofollow_links as $link){
				$href = $link[2];
				$link_text = $link[3];
				
				$details .= '<li>'.
					'<span class="dashicons dashicons-arrow-right"></span>'.
					'<a href="' . esc_url($href) . '" target="_blank">'.esc_html($link_text).'</a>'.
					'<span class="dashicons dashicons-external"></span>'.
					'</li>';
			}
			
			$details .= '</ul>';
		} else{
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>' . 
				__('This page does not contain any nofollow links.', 'siteseo') . '</p>';
		}
		
		return [
			'label' => __('Nofollow Links', 'siteseo'),
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}

	static function check_headings($content, $keywords = []) {
		$details = '';
		$status = 'Good';
		$status_class = 'good';
		if(empty(trim($content))){
			return [
				'label' => 'Headings',
				'status' => 'Warning',
				'status_class' => 'warning',
				'details' => '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('No content available to check headings.', 'siteseo') . '</p>'
			];
		}

		preg_match_all('/<h([1-6])([^>]*)>(.*?)<\/h\1>/is', $content, $heading_matches);
		
		if(empty($heading_matches[0])){
			return [
				'label' => 'Headings',
				'status' => 'Error',
				'status_class' => 'error',
				'details' => '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('No headings found in the content. Using headings is essential for both SEO and accessibility!', 'siteseo') . '</p>'
			];
		}

		$heading_counts = array_count_values($heading_matches[1]);
		$total_headings = count($heading_matches[0]);

		
		$h1_count = isset($heading_counts[1]) ? $heading_counts[1] : 0;
		if($h1_count > 0){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . 
				/* translators: %d represents the number of h1 tags */
				sprintf(esc_html__('We found %d Heading 1 (H1) tags in your content.', 'siteseo'), $h1_count+1) . '</p>';

			$details .= '<p>' . __('You should avoid using more than one H1 heading in your post content. The rule is simple: each web page should have only one H1, which benefits both SEO and accessibility. Below is the list:', 'siteseo') . '</p>';

			$details .= '<ul>';
			foreach(array_keys($heading_matches[1], '1') as $index){
				$details .= '<li><span class="dashicons dashicons-arrow-right"></span>' . 
					wp_strip_all_tags($heading_matches[0][$index]) . '</li>';
			}
			$details .= '</ul>';
			$status = 'Warning';
			$status_class = 'warning';
		}
		foreach([2, 3] as $level){
			$level_count = isset($heading_counts[$level]) ? $heading_counts[$level] : 0;
			$details .= '<p><span class="dashicons dashicons-info"></span>' .
				/* translators: %d represents the heading */ 
				sprintf(__('Found %1$d H%2$d heading(s)', 'siteseo'), $level_count, $level) . '</p>';

			if($level_count > 0){
				$details .= '<ul>';
				foreach(array_keys($heading_matches[1], (string)$level) as $index){
					$details .= '<li><span class="dashicons dashicons-arrow-right"></span>'. 
						wp_strip_all_tags($heading_matches[0][$index]) .'</li>';
				}
				$details .= '</ul>';
			}

			if(!empty($keywords) && $level_count > 0){
				$keyword_found = false;
				$keyword_details = '<ul>';
				
				foreach($keywords as $kw_name){
					$kw_count = 0;
					foreach(array_keys($heading_matches[1], (string)$level) as $index){
						$kw_count += substr_count(
							strtolower(wp_strip_all_tags($heading_matches[0][$index])), 
							strtolower($kw_name)
						);
					}
					
					if($kw_count > 0){
						$keyword_found = true;
						$keyword_details .= '<li><span class="dashicons dashicons-arrow-right"></span>' .
							/* translators: %s represents the degree of severity */ 
							sprintf(esc_html__('%1$s was found %2$d times.', 'siteseo'), $kw_name, $kw_count) . '</li>';
					}
				}
				$keyword_details .= '</ul>';

				if($keyword_found){
					$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>' .
						/* translators: %s represents the target keywords  */ 
						sprintf(__('Target keywords were found in Heading %1$d (H%2$d).', 'siteseo'), $level, $level) . '</p>' . 
						$keyword_details;
				} else{
					$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' .
						/* translators: %d represents the target keywords */ 
						sprintf(__('None of your target keywords were found in Heading %1$d (H%2$d).', 'siteseo'), $level, $level) . '</p>';
					if($status === 'Good'){
						$status = 'Warning';
						$status_class = 'warning';
					}
				}
			}
		}

		return [
			'label' => 'Headings',
			'status' => $status,
			'status_class' => $status_class,
			'details' => $details
		];
	}
	
	static function check_social_meta_tags($post = null){
		if(!$post){
			$post = get_queried_object();
		}
		
		$details = '';
		$status = 'Good';
		$status_class = 'good';
		
		$og_titles = get_post_meta($post->ID, '_siteseo_social_fb_title', true);
		$og_title = $og_titles ? $og_titles : '';
		
		if(empty($og_title)){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your Open Graph Title tag has not been set!', 'siteseo') .'</p>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your Open Graph Title is missing!', 'siteseo') .'</p>';
			$status = 'Error';
			$status_class = 'error';
		} else{
			$details .= '<h4>'. __('Open Graph Title', 'siteseo') . '</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('An Open Graph Title tag was found in your source code.', 'siteseo') . '</p>';
			$details .= '<ul><li><span class="dashicons dashicons-arrow-right"></span>' . esc_html($og_title) . '</li></ul>';
		}

		$og_descriptions = get_post_meta($post->ID, '_siteseo_social_fb_desc', true);
		$og_description = $og_descriptions ? $og_descriptions : '';

		if(empty($og_description)){
			$details .= '<h4>'. __('Open Graph Description', 'siteseo') .'</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your Open Graph Description has not been set!','siteseo').'</p>';
			$status = $status === 'Good' ? 'Warning' : $status;
			$status_class = $status_class === 'good' ? 'warning' : $status_class;
		} else {
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>' .
				/* translators: %s represents the og description */
				sprintf(esc_html__('We found %s og:description in your content.', 'siteseo'), $og_descriptions) . '</p>';
		}

		// OG Check
		$og_images = get_post_meta($post->ID, '_siteseo_social_fb_img', true);
		$og_image = $og_images ? $og_images : '';
		
		if(empty($og_image)){
			$details .= '<h4>'. __('Open Graph Image', 'siteseo') .'</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your Open Graph Image has not been set!' ,'siteseo') .'</p>';
			$status = $status === 'Good' ? 'Warning' : $status;
			$status_class = $status_class === 'good' ? 'warning' : $status_class;
		} else{
			/* translators: %s represents the og images */
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. sprintf(esc_html__('We found %s og:image in your content.', 'siteseo'), $og_images) . '</p>';
		}

		// Open Graph
		$og_site_name = get_bloginfo('name');
		if(empty($og_site_name)){
			$details .= '<h4>'. __('Open Graph Site Name', 'siteseo') .'</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your Open Graph Site Name has not been set!' ,'siteseo') .'</p>';
			$status = $status === 'Good' ? 'Warning' : $status;
			$status_class = ($status_class === 'good') ? 'warning' : $status_class;
		}

		// Twitter
		$twitter_title = get_post_meta($post->ID, '_siteseo_social_twitter_title', true);
		if(empty($twitter_title)){
			$details .= '<h4>'. __('X Title', 'siteseo').'</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your X Title has not been set!', 'siteseo').'</p>';
			$status = $status === 'Good' ? 'Warning' : $status;
			$status_class = $status_class === 'good' ? 'warning' : $status_class;
		}

		$twitter_description = get_post_meta($post->ID, '_siteseo_social_twitter_desc', true);
		if(empty($twitter_description)){
			$details .= '<h4>'. __('X Description','siteseo').'</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your X Description has not been set!','siteseo') .'</p>';
			$status = $status === 'Good' ? 'Warning' : $status;
			$status_class = $status_class === 'good' ? 'warning' : $status_class;
		}

		$twitter_image = get_post_meta($post->ID, '_siteseo_social_twitter_img', true);
		if(empty($twitter_image)){
			$details .= '<h4>'. __('X Image', 'siteseo') .'</h4>';
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('Your X Image has not been set!', 'siteseo').'</p>';
			$status = $status === 'Good' ? 'Warning' : $status;
			$status_class = ($status_class === 'good') ? 'warning' : $status_class;
		}

		return [
			'label' => __('Social Meta Tags', 'siteseo'),
			'status' => $status,
			'status_class' => $status_class,
			'details' => $details
		];

	}

    static function check_structured_data($post){
        $schema_type = get_post_meta($post->ID, '_siteseo_structured_data_type', true);
        $status = !empty($schema_type) ? 'Good' : 'Warning';

        return [
            'label' => 'Structured Data',
            'status' => $status,
            'status_class' => strtolower($status),
            'details' => !empty($schema_type) ? 'Schema Type: '.$schema_type : 'No schema defined'
        ];
    }

	static function check_keywords_in_permalink($permalink, $keywords){
		$keywords = array_filter(explode(',', trim($keywords)));
		$permalink = str_replace('-', ' ', strtolower(basename($permalink)));
		$content_words = str_word_count($permalink, 1);
		$count_words = count($content_words);


		$kw_density = [];
		$matching_keywords = [];
		
		foreach($keywords as $keyword){
			$keyword_occurrence = 0;
			$keyword = strtolower(trim($keyword));
			
			// If keyword has multiple words
			if(str_word_count($keyword) > 1){
				$pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
				preg_match_all($pattern, $permalink, $matches);
				$keyword_occurrence = count($matches[0]);
			} else {
				$word_counts = array_count_values($content_words);
				$keyword_occurrence = isset($word_counts[$keyword]) ? $word_counts[$keyword] : 0;
			}
		
			// Calculate density as percentage
			$kw_density[] = ($keyword_occurrence * str_word_count($keyword) * 100)/$count_words;
			if(count($kw_density) > 0){
				$matching_keywords[] = $keyword;
				break;
			}
		}

		$status = !empty($kw_density) ? 'Good' : 'Error';

		$details = '';
		if($status === 'Good'){	
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('Great! One of your target keywords is included in your permalink.', 'siteseo') .'</p>';
			$details .= '<ul><li><span class="dashicons dashicons-arrow-right"></span>' . implode(', ', $matching_keywords) . '</li></ul>';
		} elseif($permalink === get_home_url() || $permalink === home_url()){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('This is your homepage, so this check doesn\'t apply as there is no slug.', 'siteseo') . '</p>';
		} else{
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('You should include one of your target keywords in your permalink.', 'siteseo') . '</p>';
		}

		return [
			'label' => __('Keywords in Permalink', 'siteseo'),
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
		
	}
	
	static function check_meta_robots($post){

		$noindex = get_post_meta($post->ID, '_siteseo_robots_index', true);
		$nofollow = get_post_meta($post->ID, '_siteseo_robots_follow', true);
		$noimageindex = get_post_meta($post->ID, '_siteseo_robots_imageindex', true);
		$noarchive = get_post_meta($post->ID, '_siteseo_robots_archive', true);
		$nosnippet = get_post_meta($post->ID, '_siteseo_robots_snippet', true);

		$count_meta_robots = 0;
		$details = '';

		$meta_robots_checks = [
			'noindex' => $noindex,
			'nofollow' => $nofollow,
			'noimageindex' => $noimageindex,
			'noarchive' => $noarchive,
			'nosnippet' => $nosnippet,
		];

		foreach($meta_robots_checks as $robot => $value){
			if($value !== 'yes'){
				continue;
			}

			$count_meta_robots++;
			switch($robot){
				case 'noindex':
					$details .= '<p data-robots="noindex"><span class="dashicons dashicons-thumbs-down"></span>' . __('<strong>noindex</strong> is enabled! Search engines cannot index this page', 'siteseo') . '</p>';
					break;
				case 'nofollow':
					$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('<strong>nofollow</strong> is on! Search engines can\'t follow your links on this page.', 'siteseo') . '</p>';
					break;
				case 'noimageindex':
					$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('<strong>nofollow</strong> is enabled! Search engines cannot follow the links on this page.', 'siteseo').'</p>';
					break;
				case 'noarchive':
					$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('<strong>noarchive</strong> is enabled! Search engines will not cache your page.', 'siteseo') .'</p>';
					break;
				case 'nosnippet':
					$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>' . __('<strong>nosnippet</strong> is enabled! Search engines will not display a snippet of this page in the search results.', 'siteseo') .'</p>';
					break;
			}
		}

		if($count_meta_robots > 0){
			/* translators: %s represents the robots tags */ 
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. sprintf(esc_html__('We found %s meta robots tags on your page. There may be an issue with your theme!', 'siteseo'), $count_meta_robots) .'</p>';
		}
		
		if($noindex !== 'yes'){
			$details .= '<p data-robots="index"><span class="dashicons dashicons-thumbs-up"></span>'. __('<strong>noindex</strong> is disabled. Search engines will index this page.', 'siteseo') .'</p>';
		}
		if($nofollow !== 'yes'){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('<strong>nofollow</strong> is disabled. Search engines will follow links on this page.', 'siteseo') .'</p>';
		}
		
		if($noimageindex !== 'yes'){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('<strong>noimageindex</strong> is disabled. Google will index the images on this page.', 'siteseo') .'</p>';
		}
		if($noarchive !== 'yes'){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('<strong>noarchive</strong> is disabled. Search engines will probably cache your page.', 'siteseo') .'</p>';
		}
		if($nosnippet !== 'yes'){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>'. __('<strong>nosnippet</strong> is disabled. Search engines will display a snippet of this page in search results.', 'siteseo') .'</p>';
		}
		
		if($count_meta_robots === 0){
			$details .= '<p><span class="dashicons dashicons-thumbs-up"></span>' . __('We found no meta robots on this page. It means, your page is index,follow. Search engines will index it, and follow links. ', 'siteseo') . '</p>';
		}

		$status = ($count_meta_robots === 0) ? 'Good' : 
				  (($count_meta_robots <= 2) ? 'Warning' : 'Error');

		return [
			'label' => 'Meta Robots',
			'status' => $status,
			'status_class' => strtolower($status),
			'details' => $details
		];
	}

    static function check_last_modified_date($post){
        $last_modified = get_the_modified_date('Y-m-d', $post);
        $days_since_modified = round((time() - strtotime($last_modified)) / (60 * 60 * 24));

        $status = $days_since_modified < 30 ? 'Good' : 
                  ($days_since_modified < 90 ? 'Warning' : 'Error');
				  
		$details = '';
		
		if($status == 'Error'){
			$details .= '<p><span class="dashicons dashicons-thumbs-down"></span>'. __('This post is a little old!', 'siteseo') .'</p>';
		}
		
		if($days_since_modified < 365){
			$details .='<p><span class="dashicons dashicons-thumbs-up"></span>'.__('The last modified date of this article is less than 1 year. Cool', 'siteseo') .'</p>';
		}
		
		$details .= '<p>'.__('Search engines love fresh content. Update regularly your articles without entirely rewriting your content and give them a boost in search rankings. SiteSEO takes care of the technical part', 'siteseo').'</p>';

        return [
            'label' => 'Last Modified Date',
            'status' => $status,
            'status_class' => strtolower($status),
			'details' => $details
        ];
    }
	
	static function analyze_readability($post, $title){
		$data = [];

		// These are power words specifically for headlines.
		// These are not hard rules, but they are perceived to have a higher CTR if used in the heading.
		$power_words = ['exclusive', 'revealed', 'secrets', 'ultimate', 'proven', 'unleashed', 'discover', 'breakthrough', 'shocking', 'insider', 'elite', 'uncovered', 'powerful', 'guaranteed', 'transformative', 'instant', 'revolutionary', 'unbelievable', 'top', 'best', 'must-have', 'limited', 'rare', 'unique', 'unprecedented', 'premium', 'urgent', 'today', 'now', 'latest', 'new', 'free', 'bonus', 'offer', 'sensational', 'astonishing', 'incredible', 'jaw-dropping', 'unmissable', 'essential', 'critical', 'vital', 'pivotal', 'game-changer', 'spotlight', 'trending', 'hot', 'popular', 'featured', 'special', 'limited-time', 'hurry', 'last chance', 'countdown'];
		
		if(!empty($title)){
			// Checking power words.
			$title_words = explode(' ', strtolower($title));

			$present_power_words = array_intersect($title_words, $power_words);

			if(!empty($present_power_words)){
				$data['power_words'] = $present_power_words;
			}

			// Checking number in the Title
			if(preg_match('/\s?\d+\s/', preg_quote($title), $number)){
				$data['number_found'] = $number[0];
			}
		}
		
		// We are checking paragarph length too.
		if(!isset($data['paragraph_length'])){
			$data['paragraph_length'] = 0;
		}
		
		if(!empty($post)){
			preg_match_all('/<p>.*<\/p>/U', $post, $paragraphs);

			foreach($paragraphs[0] as $paragraph){
				$paragraph = normalize_whitespace(wp_strip_all_tags($paragraph));
				
				$data['paragraph_length'] += substr_count($paragraph, ' ') + 1; // updating paragraph length
				self::analyse_passive_voice($paragraph, $data);
			}
		}

		return $data;
	}
	
	static function analyse_passive_voice($paragraph, &$data){

		if(empty($paragraph)){
			return;
		}

		$sentences = explode('.', $paragraph);
		$passive_count = 0;

		if(!isset($data['passive_voice']['passive_sentences'])){
			$data['passive_voice']['passive_sentences'] = 0;
		}
		
		if(!isset($data['passive_voice']['total_sentences'])){
			$data['passive_voice']['total_sentences'] = 0;
		}

		if(count($sentences) === 0){
			return;
		}

		foreach($sentences as $sentence){
			if(empty($sentence)){
				continue;
			}

			$sentence = normalize_whitespace($sentence);
			$is_passive = self::sentence_is_passive($sentence);
			
			if($is_passive == true){
				$passive_count++;
			}
		}

		$data['passive_voice']['passive_sentences'] += $passive_count;
		$data['passive_voice']['total_sentences'] += count($sentences);
	}

	static function sentence_is_passive($sentence){
		$be_words = ['am', 'is', 'are', 'was', 'were', 'be', 'being', 'been'];

		// TODO: We can check if "en" ending words are a comman pattern too, then we will remove the en ending words too from here.
		$past_particles = ['gone' ,'done' ,'seen' ,'taken' ,'eaten' ,'written' ,'driven' ,'spoken' ,'broken' ,'chosen' ,'fallen' ,'forgotten' ,'forgiven' ,'hidden' ,'known' ,'grown' ,'drawn' ,'flown' ,'thrown' ,'blown' ,'shown' ,'worn' ,'sworn' ,'torn' ,'woken' ,'begun' ,'sung' ,'run' ,'swum' ,'shaken' ,'given' ,'proven' ,'ridden' ,'risen' ,'shone' ,'shot' ,'fought' ,'thought' ,'bought' ,'brought' ,'caught' ,'taught' ,'built' ,'felt' ,'kept' ,'slept' ,'left' ,'lost' ,'meant' ,'met' ,'read' ,'sold' ,'sent' ,'spent' ,'stood' ,'understood' ,'won' ,'held' ,'told' ,'heard' ,'paid' ,'laid' ,'said' ,'found' ,'made' ,'learned' ,'put'];
		
		if(empty($sentence)){
			return false;
		}
		
		$words = explode(' ', $sentence);

		for($i = 0; $i < count($words); $i++){
			// Checking if we have a be word
			if(!in_array($words[$i], $be_words)){
				continue;
			}

			// If be word is there then need to check if next one is past particle with mostly ends with ed.
			if(strpos($words[$i+1], 'ed') != strlen($words[$i+1]) - 2){
				if(!in_array($words[$i+1], $past_particles)){
					continue;
				}
			}

			return true;
		}

		return false;
	}

	static function get_bricks_page_content($bricks_page){

		if(empty($bricks_page) || !is_array($bricks_page)){
			return;
		}

		$content = \Bricks\Frontend::render_data($bricks_page);

		$content = strip_shortcodes($content); // remove shortcodes
		$content = wp_strip_all_tags($content); // remove HTML tags

		return $content;
	}

}
