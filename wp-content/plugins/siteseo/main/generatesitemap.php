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

class GenerateSitemap{
	
	private static $paged = 1;

	static function settings(){
		global $siteseo;

		if(empty($siteseo->setting_enabled['toggle-xml-sitemap'])){
			return;	
		}
		
		if(!empty($siteseo->sitemap_settings['xml_sitemap_html_enable'])){
			add_shortcode('siteseo_html_sitemap', '\SiteSEO\GenerateSitemap::html_sitemap');
		}

		if(!empty($siteseo->sitemap_settings['xml_sitemap_general_enable'])){
			self::xml_sitemap();
		}
	}

	static function xml_sitemap(){
		add_filter('query_vars', function($vars){
			$vars[] = 'sitemap_type';
			$vars[] = 'paged';
			$vars[] = 'sitemap-stylesheet';
			return $vars;
		});
	}

	static function add_rewrite_rules(){
		global $siteseo;
		
		add_rewrite_rule('^sitemaps\.xsl$', 'index.php?sitemap-stylesheet=sitemap', 'top');
		add_rewrite_rule('^sitemaps\.xml$', 'index.php?sitemap_type=general', 'top');
		add_rewrite_rule('^author.xml$', 'index.php?sitemap_type=author', 'top');
		add_rewrite_rule('^media-sitemap([0-9]+)?.xml$', 'index.php?sitemap_type=media&paged=$matches[1]', 'top');
		add_rewrite_rule('^news([0-9]+)?.xml$', 'index.php?sitemap_type=news&paged=$matches[1]', 'top');
		add_rewrite_rule('^video-sitemap([0-9]+)?.xml$', 'index.php?sitemap_type=video&paged=$matches[1]', 'top');
		
		if(isset($siteseo->sitemap_settings['xml_sitemap_post_types_list'])){
            foreach($siteseo->sitemap_settings['xml_sitemap_post_types_list'] as $post_type => $settings){
                if(!empty($settings['include'])){
                    add_rewrite_rule('^'.$post_type.'-sitemap([0-9]+)?\.xml$', 'index.php?sitemap_type='.$post_type.'&paged=$matches[1]', 'top');
                }
            }
        }

        if(isset($siteseo->sitemap_settings['xml_sitemap_taxonomies_list'])){
            foreach($siteseo->sitemap_settings['xml_sitemap_taxonomies_list'] as $taxonomy => $settings){
                if(!empty($settings['include'])){
                    add_rewrite_rule('^'.$taxonomy.'-sitemap([0-9]+)?\.xml$', 'index.php?sitemap_type='.$taxonomy.'&paged=$matches[1]', 'top');
                }
            }
        }

		flush_rewrite_rules();
    }


	static function handle_sitemap_requests(){
		global $siteseo;

		$pro_settings = isset($siteseo->pro) ? $siteseo->pro : '';
		self::maybe_redirect();

		// Output the Sitemap style
		if(get_query_var('sitemap-stylesheet') === 'sitemap'){
			self::sitemap_xsl();
			exit;
		}
		
		if(get_query_var('paged')){
			self::$paged = get_query_var('paged');
		}
		
		$type = get_query_var('sitemap_type');
		if(!empty($type)){
			
			if($type === 'news' && !empty($pro_settings['google_news']) && !empty($pro_settings['toggle_state_google_news'])){
				
				self::generate_google_news_sitemap();
				exit;
			}
			
			if($type === 'video' && !empty($pro_settings['toggle_state_video_sitemap']) && !empty($pro_settings['enable_video_sitemap'])){
				self::generate_video_sitemap();
				exit;
			}

			// Custom post type
			if(isset($siteseo->sitemap_settings['xml_sitemap_post_types_list'][$type]) && !empty($siteseo->sitemap_settings['xml_sitemap_post_types_list'][$type]['include'])){
				self::generateSitemap($type);
				exit;
			}

			//custom taxomies type
			if(isset($siteseo->sitemap_settings['xml_sitemap_taxonomies_list'][$type]) && !empty($siteseo->sitemap_settings['xml_sitemap_taxonomies_list'][$type]['include'])){
				self::generate_term_sitemap($type);
				exit;
			}
 
			switch($type){
				case 'general':
					self::generate_index_sitemap();
					break;
				case 'post':
					self::generateSitemap('post');
					break;
				case 'page':
					self::generateSitemap('page');
					break;
				case 'category':
					self::generate_term_sitemap('category');
					break;
				case 'post_tag':
					self::generate_term_sitemap('post_tag');
					break;
				case 'author':
					self::generate_author_sitemap();
					break;
				case 'news':
					self::generate_google_news_sitemap();
					break;
				case 'video':
					self::generate_video_sitemap();
					break;
				default:
					wp_die(esc_html__('Invalid sitemap type.', 'siteseo'));
 			}
 		}
	}
	
	static function generate_index_sitemap(){
		global $siteseo;
		
		$pro_settings = isset($siteseo->pro) ? $siteseo->pro : '';

		header('Content-Type: application/xml; charset=UTF-8');
		
		if(get_option('permalink_structure')){
			$xsl_url = home_url('/sitemaps.xsl');
		} else {
			$xsl_url = home_url('/?sitemaps-stylesheet=sitemap');
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="' . esc_url($xsl_url) . '" ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		// Post types
		if(isset($siteseo->sitemap_settings['xml_sitemap_post_types_list'])){
			foreach($siteseo->sitemap_settings['xml_sitemap_post_types_list'] as $post_type => $settings){
				$posts = get_posts(
					[
						'post_type' => $post_type,
						'fields'=> 'ids',
						'numberposts' => -1,
						'post_status' => 'publish',
						'has_password' => false,
						'no_found_rows' => true,
						'ignore_sticky_posts' => true,
						'update_post_term_cache' => false,
					]
				);

				if(empty($posts)){
					continue;
				}

				$total_pages = (int) ceil(count($posts) / 1000);

				if(!empty($settings['include']) && !empty($total_pages)){
					$last_post = get_posts([
						'post_type' => $post_type,
						'numberposts' => 1,
						'post_status' => 'publish',
						'orderby' => 'modified',
						'order' => 'DESC',
						'fields' => 'ids',
					]);
					
					$lastmod = !empty($last_post) ? get_post_modified_time('c', true, $last_post[0]) : current_time('c');
					
					for($page = 1; $page <= $total_pages; $page++){					
						echo '<sitemap>
							<loc>'.esc_url(home_url("/$post_type-sitemap$page.xml")).'</loc>
							<lastmod>'.esc_xml($lastmod).'</lastmod>
						</sitemap>';
					}
				}
			}
		}

		// Taxonomies
		if(isset($siteseo->sitemap_settings['xml_sitemap_taxonomies_list'])){
			foreach($siteseo->sitemap_settings['xml_sitemap_taxonomies_list'] as $taxonomy => $settings){
				
				$args = [
					'taxonomy' => $taxonomy,
					'hide_empty' => true,
					'fields' => 'count',
					'hierarchical' => false,
					'update_term_meta_cache' => false,
				];
				
				$tax_count = get_terms($args);

				if(is_wp_error($tax_count) || $tax_count == 0){
					continue;
				}

				$total_pages = (int) ceil($tax_count/2000);
				
				if(!empty($settings['include']) && $total_pages > 0){
					$terms = get_terms([
						'taxonomy' => $taxonomy,
						'number' => 1,
						'orderby' => 'term_order',
						'order' => 'DESC',
						'fields' => 'ids',
					]);
					
					$lastmod = !empty($terms) ? current_time('c') : current_time('c'); // taxonomy terms don't have modified, fallback
					
					for($page = 1; $page <= $total_pages; $page++){
						echo '<sitemap>	
							<loc>'.esc_url(home_url("/$taxonomy-sitemap$page.xml")).'</loc>
							<lastmod>'.esc_xml($lastmod).'</lastmod>
						</sitemap>';
					}
				}
			}
		}

		// Author
		if(!empty($siteseo->sitemap_settings['xml_sitemap_author_enable'])){
			echo '<sitemap>
				<loc>'.esc_url(home_url('/author.xml')).'</loc>
				<lastmod>'.esc_xml(current_time('c')).'</lastmod>
			</sitemap>';
		}
		
		// Google News
		if(!empty($pro_settings['google_news']) && !empty($pro_settings['toggle_state_google_news'])){
			echo '<sitemap>
				<loc>'.esc_url(home_url('/news.xml')).'</loc>
				<lastmod>'.esc_xml(current_time('c')).'</lastmod>
			</sitemap>';
		}
		
		// Video
		if(!empty($pro_settings['toggle_state_video_sitemap']) && !empty($pro_settings['enable_video_sitemap'])){
			$video_posts = get_posts([
				'post_type' => $pro_settings['video_sitemap_posts'],
				'fields' => 'ids',
				'numberposts' => 1,
				'orderby' => 'modified',
				'order' => 'DESC',
				'fields' => 'ids',
				'meta_query' => [
					[
						'key' => '_siteseo_video_disabled',
						'compare' => 'NOT EXISTS'
					]
				]
			]);
				
				
			$lastmod = !empty($video_posts) ? get_post_modified_time('c', true, $video_posts[0]) : current_time('c');
			
				for($page = 1; $page <= $total_pages; $page++){
					echo '<sitemap>
						<loc>'.esc_url(home_url("/video-sitemap$page.xml")).'</loc>
						<lastmod>'.esc_xml($lastmod).'</lastmod>
					</sitemap>';
				}

		}
		
		echo '</sitemapindex>';
		exit;
	}

	// post
	static function generate_post_sitemap(){
		self::generateSitemap('post');
	}

	// page
	static function generate_page_sitemap(){
		self::generateSitemap('page');
	}

	// category 
	static function generate_category_sitemap(){
		self::generate_term_sitemap('category');
	}

	//post tag
	static function generate_post_tag_sitemap(){
		self::generate_term_sitemap('post_tag');
	}

	// taxonomy
	static function generate_taxonomy_sitemap(){
		self::generate_term_sitemap('taxonomy');
	}

	// google news pro feature
	static function generate_google_news_sitemap(){
		
		if(class_exists('\SiteSEOPro\GoogleNews') && method_exists('\SiteSEOPro\GoogleNews', 'google_news_sitemap')){
			\SiteSEOPro\GoogleNews::google_news_sitemap();
		}
	}
	
	// video sitemap pro feature
	static function generate_video_sitemap(){
		if(class_exists('\SiteSEOPro\VideoSitemap') && method_exists('\SiteSEOPro\VideoSitemap', 'render_sitemap')){
			\SiteSEOPro\VideoSitemap::render_sitemap();
		}
	}

	static function generateSitemap($post_type){
		global $siteseo;
		
		header('Content-Type: application/xml; charset=utf-8');
		
		$offset = (1000*(self::$paged - 1));

		$posts = get_posts(
		[
			'post_type' => $post_type,
			'post_status' => 'publish',
			'numberposts' => 1000,
			'offset' => $offset,
			'order' => 'DESC',
			'orderby' => 'modified',
			'has_password' => false,
			'no_found_rows' => true,
			'lang' => 'all',
			'meta_query' => [
			[
				'key' => '_siteseo_robots_index',
				'compare' => 'NOT EXISTS'
			]]
		]);

		if(get_option('permalink_structure')){
			$xsl_url = home_url('/sitemaps.xsl');
		} else {
			$xsl_url = home_url('/?sitemaps-stylesheet=sitemap');
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="' . esc_url($xsl_url) . '" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '.((!empty($siteseo->sitemap_settings['xml_sitemap_img_enable'])) ? 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '').'>';

		if(self::$paged == 1){
			if(get_option('show_on_front') === 'page' && get_option('page_on_front')){
				echo "\t".'<url>
				<loc>'.esc_url(trailingslashit(home_url())).'</loc>
				<lastmod>'.esc_html(get_the_modified_date('c', get_option('page_on_front'))).'</lastmod>
				</url>';
			} else{
				echo "\t".'<url>
				<loc>'.esc_url(trailingslashit(home_url())).'</loc>
				</url>';
			}
		}

		foreach($posts as $post){
				
			if($post->ID == get_option('page_on_front')){
				continue;
			}
			
			$image_xml = '';
			if(!empty($siteseo->sitemap_settings['xml_sitemap_img_enable'])){
				$images = self::get_page_images($post);
				if(!empty($images)){
					foreach($images as $image){
						$image_xml .= "<image:image>\n";
						$image_xml .= "\t\t\t<image:loc>".esc_url($image)."</image:loc>\n";
						$image_xml .= "\t\t</image:image>";
					}
				}
			}

			echo "\t<url>
				<loc>".esc_url(get_permalink($post->ID))."</loc>
				<lastmod>".esc_html(get_the_modified_date('c', $post->ID))."</lastmod>
				".$image_xml."
			</url>";
		}

		echo '</urlset>';
		exit;
	}

	static function generate_term_sitemap($taxonomy){
		header('Content-Type: application/xml; charset=utf-8');
		
		$offset = (2000*(self::$paged - 1));

		if(get_option('permalink_structure')){
			$xsl_url = home_url('/sitemaps.xsl');
		} else {
			$xsl_url = home_url('/?sitemaps-stylesheet=sitemap');
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="' . esc_url($xsl_url) . '" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$terms = get_terms([
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'number' => 2000,
			'offset' => $offset,
			'hierarchical' => false,
			'update_term_meta_cache' => false,
			'lang' => 'all',
			'meta_query' => [
			[
				'key' => '_siteseo_robots_index',
				'compare' => 'NOT EXISTS'
			]]
		]);

		foreach($terms as $term){
			
			// most recent post in this term to determine lastmod date
			$recent_posts = get_posts([
				'tax_query' => [
					[
						'taxonomy' => $taxonomy,
						'field' => 'term_id',
						'terms' => $term->term_id,
					]
				],
				'numberposts' => 1,
				'orderby' => 'modified',
				'order' => 'DESC',
				'post_status' => 'publish'
			]);

			$last_mod = '';
			if(!empty($recent_posts)){
				$last_mod = "\n\t\t".'<lastmod>'.esc_html(get_the_modified_date('c', $recent_posts[0]->ID)).'</lastmod>';
			}
			
			echo "\t". '<url>
			<loc>'.esc_url(get_term_link($term)).'</loc>'.$last_mod.'
			</url>';
		}

		echo '</urlset>';
		exit;
	}

	static function generate_author_sitemap(){
		header('Content-Type: application/xml; charset=utf-8');

		if(get_option('permalink_structure')){
			$xsl_url = home_url('/sitemaps.xsl');
		} else {
			$xsl_url = home_url('/?sitemaps-stylesheet=sitemap');
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="' . esc_url($xsl_url) . '" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		// Fetch all authors
 		$authors = get_users(
			['capability' => ['publish_posts']]
		);

		foreach($authors as $author){
			// get the last modified date of the author's most recent post
			$last_post = get_posts([
				'author' => $author->ID,
				'numberposts' => 1,
				'orderby' => 'modified',
				'order' => 'DESC',
				'post_status' => 'publish'
			]);
			
			$lastmod_date = '';
			if(!empty($last_post)){
				$lastmod_date = get_the_modified_date('c', $last_post[0]->ID);
			} else{
				// user registration date if no posts
				$lastmod_date = gmdate('c', strtotime($author->user_registered));
			}

			echo "\t".'<url>
			<loc>'.esc_url(get_author_posts_url($author->ID)).'</loc>
			<lastmod>'.esc_html($lastmod_date).'</lastmod>
		</url>';
		}

		echo '</urlset>';
		exit;
	}

	static function html_sitemap($atts = []){
		global $siteseo;

		$atts = shortcode_atts(
			[
				'cpt' => '', // Default
			],
			$atts,
			'siteseo_html_sitemap'
		);

		$disable_date = !empty($siteseo->sitemap_settings['xml_sitemap_html_date']);
		$order_by = !empty($siteseo->sitemap_settings['xml_sitemap_html_orderby']) ? $siteseo->sitemap_settings['xml_sitemap_html_orderby']  : 'date';
		$order = !empty($siteseo->sitemap_settings['xml_sitemap_html_order']) ? $siteseo->sitemap_settings['xml_sitemap_html_order'] : 'DESC';		
		$exclude_string = isset($siteseo->sitemap_settings['xml_sitemap_html_exclude']) ? $siteseo->sitemap_settings['xml_sitemap_html_exclude'] : '';
		$exclude_pages = [];
		if(!empty($exclude_string)){
			$exclude_pages = array_map('trim', explode(',', $exclude_string));
		}

		$output = '';

		if($order !== 'ASC' && $order !== 'DESC'){
			$order = 'DESC';
		}

		$orderby_map = [
			'post_title' => 'title',
			'modified_date' => 'modified',
			'post_id' => 'ID',
			'menu_order' => 'menu_order',
			'date' => 'date', // Default
		];

		$orderby = !empty($orderby_map[$order_by]) ? $orderby_map[$order_by] : 'date';
		$cpt_list = !empty($atts['cpt']) ? explode(',', $atts['cpt']) : [];

		if(!empty($siteseo->sitemap_settings['xml_sitemap_post_types_list'])){ 
			foreach($siteseo->sitemap_settings['xml_sitemap_post_types_list'] as $post_type => $settings){
				if(!empty($settings['include']) && (empty($cpt_list) || in_array($post_type, $cpt_list))){

					$output .= '<h2>'.esc_html(ucfirst($post_type)).'</h2>';

					$args = [
						'post_type' => $post_type,
						'post_status' => 'publish',
						'numberposts' => -1,
						'orderby' => $orderby,
						'order' => $order,
					];

					$posts = get_posts($args);

					if(!empty($posts)){
						$output .= '<ul>';
						foreach($posts as $post){
							if(in_array($post->ID, $exclude_pages)){
								continue;
							}

							$post_title = get_the_title($post->ID) ?: $post->ID;

							$output .= '<li><a href="'.esc_url(get_permalink($post->ID)).'">'.esc_html($post_title).'</a>';

							if(!$disable_date){
								$output .= '<span class="post-date"> - '.esc_html(get_the_modified_date('j F Y', $post->ID)).'</span>';
							}

							$output .= '</li>';
						}
						$output .= '</ul>';
					}else{
						$output .= '';
					}
				}
			}
		}

		return $output;
	}
	
	static function sitemap_xsl(){
		global $siteseo;
		
		$pro_settings = isset($siteseo->pro) ? $siteseo->pro : '';
		$title = __('XML Sitemap', 'siteseo');
		$generated_by = __('Generated by SiteSEO', 'siteseo');
		$sitemap_index_txt = __('This XML Sitemap Index file contains', 'siteseo');
		$sitemap_count_txt = __('This XML Sitemap contains', 'siteseo');
		$image_count_txt = __('Images', 'siteseo');
		$last_modified_txt = __('Last Modified', 'siteseo');
		$index_sitemap_url = home_url('/sitemaps.xml');
		
		$image_sitemap_enabled = !empty($siteseo->sitemap_settings['xml_sitemap_img_enable']) ? true : false; 
		$video_sitemap_enabled = !empty($pro_settings['toggle_state_video_sitemap']) && !empty($pro_settings['enable_video_sitemap']) ? true : false;
		
		header('Content-Type: application/xml; charset=UTF-8');

		echo '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
	xmlns:html="http://www.w3.org/TR/REC-html40"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
	
	if(!empty($pro_settings['toggle_state_google_news']) && !empty($pro_settings['google_news'])){
		echo "\t" .'xmlns:news="https://www.google.com/schemas/sitemap-news/0.9/"';
	}
    
	if(!empty($pro_settings['toggle_state_video_sitemap']) && !empty($pro_settings['enable_video_sitemap'])){
			echo "\t" .'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"';
    	}
    
	echo "\t" .'xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<title>'.esc_xml($title).'</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<style>
					* {
						box-sizing: border-box;
					}
					body{
						font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
						background-color: #f0f2f5;
						margin: 0;
						padding: 0;
						overflow-x: hidden;
					}
					header{
						background: linear-gradient(135deg, #022448, #034f84);
						padding: 20px;
						color: #ffffff;
						text-align: center;
						width: 100%;
						margin-bottom:15px;
					}
					header h1{
						font-size: 32px;
						margin: 0;
					}
					header p{
						margin: 5px 0 0;
						font-size: 16px;
						text-decoration: underline;
					}
					header .siteseo-index-link a{
						color: #ffffff;
						text-decoration: none;
					}
					header .siteseo-index-link a:hover{
						text-decoration: underline;
					}
					.siteseo-sitemap-container{
						width: 60%;
						margin: 0 auto;
						margin-bottom: 10px;
					}
					.siteseo-sitemap-container a{
						color:#007bff;
						text-decoration: none;
					}

					.siteseo-sitemap-table-wrap{
						background-color: #ffffff;
						box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
						border-radius: 8px;
						margin-top:10px;
						overflow:auto;
					}

					table{
						width: 100%;
						border-collapse: collapse;
					}
					table thead tr{
						background-color: #034f84;
						color: #ffffff;
					}
					table th, table td{
						padding: 10px;
						text-align: left;
					}
					table tbody tr:nth-child(even){
						background-color: #f9f9f9;
					}
					.siteseo-video-thumbnail{
					        max-width: 160px;
					        max-height: 120px;
					        border-radius: 4px;
					}
					.siteseo-video-info{
						margin-left: 15px;
					}
					.siteseo-video-container{
						display: flex;
						align-items: center;
						margin: 10px 0;
					}
					.siteseo-video-title{
						font-weight: bold;
						margin-bottom: 5px;
					}
					.siteseo-video-description{
						color: #555;
						font-size: 14px;
						margin-bottom: 5px;
					}
					.siteseo-video-meta{
						font-size: 13px;
						color: #777;
					}
					.siteseo-video-url{
						word-break: break-all;
						font-size: 13px;
						color: #007bff;
					}
				</style>
			</head>
			<body>
				<header>
					<h1>'.esc_xml($title).'</h1>
					<span>'.esc_xml($generated_by).'</span>
					<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
						<div class="siteseo-description" style="text-align:center;">'.esc_xml($sitemap_index_txt).' <xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/> sitemaps</div>
					</xsl:if>
					
					<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">
						<div class="siteseo-description" style="text-align:center;">'.esc_xml($sitemap_count_txt).' <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs</div>
					</xsl:if>
				</header>
				<div class="siteseo-sitemap-container">
					<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">
					<a href="'.esc_url($index_sitemap_url).'">Index Sitemap</a>
					</xsl:if>
					<div class="siteseo-sitemap-table-wrap">
						<table>
						<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
						<thead>
							<tr>
								<th>URL</th>
								<th>Last Modified</th>
							</tr>
						</thead>
						<tbody>
							<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
								<tr>
									<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
									<td>
										<xsl:value-of select="sitemap:lastmod"/>
										<xsl:if test="not(sitemap:lastmod)">-</xsl:if>
									</td>
								</tr>
							</xsl:for-each>
						</tbody>
						</xsl:if>
						
						<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">';
						
						if($video_sitemap_enabled && class_exists('\SiteSEOPro\VideoSitemap') && method_exists('\SiteSEOPro\VideoSitemap', 'render_video_xsl')){
							 echo'<xsl:if test="sitemap:urlset/sitemap:url/video:video">'
									. \SiteSEOPro\VideoSitemap::render_video_xsl() .
								'</xsl:if>
								<xsl:if test="not(sitemap:urlset/sitemap:url/video:video)">
									<thead>
										<tr>
											<th>URL</th>';
											if(!empty($image_sitemap_enabled)){
												echo'<th>'.esc_xml($image_count_txt).'</th>';
											}
											
										echo'<th>'.esc_xml($last_modified_txt).'</th>
										</tr>
									</thead>
									<tbody>
										<xsl:for-each select="sitemap:urlset/sitemap:url">
											<tr>
												<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>';
												
												if(!empty($image_sitemap_enabled)){
													echo'<td>
														<xsl:value-of select="count(image:image)"/>
													</td>';
												}
												
												echo'<td><xsl:value-of select="sitemap:lastmod"/></td>
											</tr>
										</xsl:for-each>
									</tbody>
								</xsl:if>';
						} else {
							echo'<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">
								<thead>
									<tr>
										<th>URL</th>';
										
										if(!empty($image_sitemap_enabled)){
											echo'<th>'.esc_xml($image_count_txt).'</th>';
										}
										
										echo'<th>'.esc_xml($last_modified_txt).'</th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="sitemap:urlset/sitemap:url">
										<tr>
											<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>';
											
											if(!empty($image_sitemap_enabled)){
												echo'<td>
													<xsl:value-of select="count(image:image)"/>
												</td>';
											}
											
											echo'<td><xsl:value-of select="sitemap:lastmod"/></td>
										</tr>
									</xsl:for-each>
								</tbody>
							  </xsl:if>';
						}
						
						echo'</xsl:if>
						</table>
					</div>
				</div>
			</body>
		</html>

</xsl:template>
</xsl:stylesheet>';
	}
	
	static function get_page_images($post){

		$images = [];
		$thumb = get_the_post_thumbnail_url($post->ID);
		
		if(!empty($thumb)){
			$images[] = $thumb;
		}
		
		if(!class_exists('DOMDocument') || empty($post->post_content)){
			return $images;
		}

		libxml_use_internal_errors(true);
		
		$dom = new \DOMDocument();
		
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $post->post_content);
		$dom->preserveWhiteSpace = false;

		libxml_clear_errors();
		
		$img_tags = $dom->getElementsByTagName('img');
		
		if(empty($img_tags)){
			return;
		}
		
		foreach($img_tags as $img_tag){
			$url = $img_tag->getAttribute('src');

			if(empty($url)){
				continue;
			}

			$url = sanitize_url($url);
			
			// The Image has some different URL which means it does not belongs to our site.
			if(strpos($url, untrailingslashit(home_url())) === FALSE){
				continue;
			}
			
			$images[] = $url;
		}

		return $images;
	}

	static function maybe_redirect(){
		global $wp;

		if(empty($wp) || empty($wp->request)){
			return;
		}
		
		$redirects = ['sitemap.xml', 'wp-sitemap.xml', 'sitemap_index.xml'];

		if(in_array($wp->request, $redirects)){
			wp_safe_redirect(home_url('sitemaps.xml'), 301);
			die();
		}
	}
	
}
