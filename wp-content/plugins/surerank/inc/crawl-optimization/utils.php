<?php
/**
 * Utils for Crawl Optimization
 *
 * @since 0.0.1
 * @package surerank
 */

namespace SureRank\Inc\Crawl_Optimization;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Traits\Get_Instance;

/**
 * Crawl Optimization Utility Functions
 * Manages Crawl Optimization.
 *
 * @since 1.0.0
 */
class Utils {
	use Get_Instance;

	/**
	 * Get Blog Prefix
	 * Determines if the blog prefix should be included in rewrite rules.
	 *
	 * @return string Blog prefix if needed, otherwise empty.
	 * @since 1.0.0
	 */
	public static function get_blog_prefix() {
		return is_multisite() && ! is_subdomain_install() && is_main_site() && strpos( get_option( 'permalink_structure' ), '/blog/' ) === 0 ? 'blog/' : '';
	}

	/**
	 * Get All Categories
	 * Retrieves all categories, including those that are empty.
	 *
	 * @return array<int, \WP_Term> List of category objects.
	 * @since 1.0.0
	 */
	public static function get_all_categories() {
		return get_categories( [ 'hide_empty' => false ] );
	}

	/**
	 * Get Category Path
	 * Constructs the full path for a category, including parent categories if any.
	 *
	 * @param object $category Category object.
	 * @return string Path to the category.
	 * @since 1.0.0
	 */
	public static function get_category_path( $category ) {
		return $category instanceof \WP_Term && $category->parent ? self::get_parent_path( $category->parent ) : '';
	}

	/**
	 * Get Parent Path
	 * Constructs the path for parent categories if a category has parents.
	 *
	 * @param int $parent_id Parent category ID.
	 * @return string Parent category path.
	 * @since 1.0.0
	 */
	public static function get_parent_path( $parent_id ) {
		$parent_path = get_category_parents( $parent_id, false, '/', true );
		return is_wp_error( $parent_path ) ? '' : $parent_path;
	}

	/**
	 * Get Full Category Slug Path
	 * Retrieves the full slug path for a category, including parent categories.
	 *
	 * @param \WP_Term $category The category term object.
	 * @return string The category slug path, or empty if an error occurs.
	 */
	public static function get_full_category_slug_path( \WP_Term $category ) {
		if ( 0 === $category->parent ) {
			return $category->slug;
		}

		$parent_path = get_term_parents_list(
			$category->term_id,
			'product_cat',
			[
				'separator' => '/',
				'link'      => false,
			]
		);

		return is_wp_error( $parent_path ) ? '' : trim( $parent_path, '/' );
	}
}
