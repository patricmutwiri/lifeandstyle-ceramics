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

class PrimaryCategory{
	
	static function wc_primary_category($none_terms, $terms, $post){
		$primary_cat = null;

		if(!empty($post)){
			$wc_primary_cat = get_post_meta($post->ID, '_siteseo_robots_primary_cat', true);
			if(isset($wc_primary_cat) && '' != $wc_primary_cat && 'none' != $wc_primary_cat){
				
				if(null != $post->post_type && 'product' == $post->post_type){
					$primary_cat = get_term($wc_primary_cat, 'product_cat');
				}
				
				if(!is_wp_error($primary_cat) && null != $primary_cat){
					return $primary_cat;
				}
			} else{
				return $none_terms;
			}
		} else{
			return $none_terms;
		}
	}

	static function add_primary_category($none_cate, $cats, $post){
		$primary_cat = null;

		if(!empty($post)){
			$robots_primary_cat = get_post_meta($post->ID, '_siteseo_robots_primary_cat', true);
			if(isset($robots_primary_cat) && '' != $robots_primary_cat && 'none' != $robots_primary_cat){
				
				if(null != $post->post_type && 'post' == $post->post_type){
					$primary_cat = get_category($robots_primary_cat);
				}
				
				if(!is_wp_error($primary_cat) && null != $primary_cat){
					return $primary_cat;
				}
			} else{
				return $none_cate;
			}
		} else{
			return $none_cate;
		}
	}
	
	static function replace_breadcrumb_categories($crumbs, $breadcrumb){
		if(!is_product()){
			return $crumbs;
		}

		global $post;
		$primary_cat_id = get_post_meta($post->ID, '_siteseo_robots_primary_cat', true);
		
		if(!empty($primary_cat_id) && $primary_cat_id !== 'none'){
			$primary_cat = get_term($primary_cat_id, 'product_cat');
			
			if(!empty($primary_cat) && !is_wp_error($primary_cat)){
				
				$new_crumbs = [];
				foreach($crumbs as $key => $crumb){
					
					if($key === 0 || (isset($crumb[1]) && strpos($crumb[1], '?post_type=product') !== false)){
						$new_crumbs[] = $crumb;
					}
				}
				
				$ancestors = get_ancestors($primary_cat->term_id, 'product_cat');
				$ancestors = array_reverse($ancestors);
				
				foreach($ancestors as $ancestor_id){
					$ancestor = get_term($ancestor_id, 'product_cat');
					if(!empty($ancestor) && !is_wp_error($ancestor)){
						$new_crumbs[] = [
							$ancestor->name,
							get_term_link($ancestor)
						];
					}
				}
				
				if(!empty($primary_cat) && !is_wp_error($primary_cat)){
					$new_crumbs[] = [
						$primary_cat->name,
						get_term_link($primary_cat)
					];
				}
				
				if(count($crumbs) > 0){
					$new_crumbs[] = $crumbs[count($crumbs) - 1];
				}
				
				return $new_crumbs;
			}
		}

		return $crumbs;
	}
}