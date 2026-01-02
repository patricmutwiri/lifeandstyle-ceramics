<?php
/**
 * Crawl Optimization Class
 * This class is responsible for optimizing crawl settings by removing category bases in URLs
 * and managing rewrite rules for categories and product categories in WooCommerce.
 *
 * @since 1.0.0
 * @package surerank
 */

namespace SureRank\Inc\Frontend;

use SureRank\Inc\Crawl_Optimization\Utils;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Crawl_Optimization
 * This class will handle functionality to crawl optimization settings.
 *
 * @since 1.0.0
 */
class Crawl_Optimization {

	use Get_Instance;

	/**
	 * Constructor
	 * Initializes the crawl optimization based on settings and adds necessary actions.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		/**
		 * Filter to remove category base
		 * We need to flush settings while using this filter
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'surerank_remove_category_base', false ) ) {
			add_filter( 'query_vars', [ $this, 'add_custom_redirect_query_var' ] );
			add_filter( 'request', [ $this, 'handle_redirect_for_old_category' ] );
			add_filter( 'category_rewrite_rules', [ $this, 'add_rewrite_rules_for_categories' ] );
			add_filter( 'term_link', [ $this, 'remove_category_base_from_links' ], 10, 3 );
		} else {
			remove_filter( 'query_vars', [ $this, 'add_custom_redirect_query_var' ] );
			remove_filter( 'request', [ $this, 'handle_redirect_for_old_category' ] );
			remove_filter( 'category_rewrite_rules', [ $this, 'add_rewrite_rules_for_categories' ] );
			remove_filter( 'term_link', [ $this, 'remove_category_base_from_links' ], 10 );
		}

		/**
		 * Filter to remove product category base
		 * We need to flush settings while using this filter
		 *
		 * @since 1.0.0
		 */
		if ( Helper::wc_status() && apply_filters( 'surerank_remove_product_category_base', false ) ) {
			add_action( 'created_product_cat', 'flush_rewrite_rules' );
			add_action( 'delete_product_cat', 'flush_rewrite_rules' );
			add_action( 'edited_product_cat', 'flush_rewrite_rules' );
			add_filter( 'product_cat_rewrite_rules', [ $this, 'surerank_filter_product_category_rewrite_rules' ] );
			add_filter( 'term_link', [ $this, 'surerank_remove_product_category_base' ], 10, 3 );
			add_action( 'template_redirect', [ $this, 'surerank_product_category_redirect' ], 1 );
		} else {
			remove_action( 'created_product_cat', 'flush_rewrite_rules' );
			remove_action( 'delete_product_cat', 'flush_rewrite_rules' );
			remove_action( 'edited_product_cat', 'flush_rewrite_rules' );
			remove_filter( 'product_cat_rewrite_rules', [ $this, 'surerank_filter_product_category_rewrite_rules' ] );
			remove_filter( 'term_link', [ $this, 'surerank_remove_product_category_base' ], 10 );
			remove_action( 'template_redirect', [ $this, 'surerank_product_category_redirect' ], 1 );
		}
	}

	/**
	 * Remove Category Base from Links
	 * Removes the base from category links to create cleaner URLs.
	 *
	 * @param string $termlink Original term link.
	 * @param object $term Term object.
	 * @param string $taxonomy Taxonomy name.
	 * @return string Modified term link.
	 * @since 1.0.0
	 */
	public function remove_category_base_from_links( $termlink, $term, $taxonomy ) {
		if ( 'category' !== $taxonomy ) {
			return $termlink;
		}
		$category_base = get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category';

		if ( strpos( $termlink, '/' . $category_base . '/' ) !== false ) {
			$termlink = str_replace( '/' . $category_base . '/', '/', $termlink );
		}

		return $termlink;
	}

	/**
	 * Add Custom Redirect Query Var
	 * Adds a custom query variable to handle category redirects.
	 *
	 * @param array<string, mixed> $query_vars Array of query variables.
	 * @return array<string, mixed>|array<int, string> Modified query variables.
	 * @since 1.0.0
	 */
	public function add_custom_redirect_query_var( $query_vars ) {
		$query_vars[] = 'custom_category_redirect';
		return $query_vars;
	}

	/**
	 * Handle Redirect for Old Category
	 * Redirects users from old category URLs to new URLs without category bases.
	 *
	 * @param array<string, mixed> $query_vars Array of query variables.
	 * @return array<string, mixed>|array<int, string> Modified query variables.
	 * @since 1.0.0
	 */
	public function handle_redirect_for_old_category( $query_vars ) {
		if ( isset( $query_vars['custom_category_redirect'] ) ) {
			$redirect_url = home_url( trailingslashit( $query_vars['custom_category_redirect'] ) );
			wp_safe_redirect( $redirect_url, 301 );
			exit;
		}
		return $query_vars;
	}

	/**
	 * Add Rewrite Rules for Categories
	 * Generates rewrite rules to handle clean category URLs.
	 *
	 * @param array<string, mixed> $rules Existing rewrite rules.
	 * @return array<string, mixed> Modified rewrite rules.
	 * @since 1.0.0
	 */
	public function add_rewrite_rules_for_categories( $rules ) {
		$base          = get_option( 'category_base' );
		$category_base = $base ? $base : 'category';

		$custom_rule = [ "^{$category_base}/(.+)/?$" => 'index.php?custom_category_redirect=$matches[1]' ];

		$clean_category_rules = $this->generate_clean_category_rules();

		return array_merge( $custom_rule, $clean_category_rules, $rules );
	}

	/**
	 * Filter Product Category Rewrite Rules
	 * Modifies rewrite rules for product categories in WooCommerce.
	 *
	 * @param array<string, mixed> $rules Existing product category rewrite rules.
	 * @return array<string, mixed> Modified rewrite rules.
	 */
	public function surerank_filter_product_category_rewrite_rules( $rules ) {
		$categories = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);

		if ( is_array( $categories ) && ! empty( $categories ) ) {
			$slugs = [];
			foreach ( $categories as $category ) {
				$slugs[] = Utils::get_full_category_slug_path( $category );
			}

			$rules = [];
			foreach ( $slugs as $slug ) {
				$rules[ '(' . $slug . ')(/page/(\d+))?/?$' ]    = 'index.php?product_cat=$matches[1]&paged=$matches[3]';
				$rules[ $slug . '/(.+?)/page/?([0-9]{1,})/?$' ] = 'index.php?product_cat=$matches[1]&paged=$matches[2]';
				$rules[ $slug . '/(.+?)/?$' ]                   = 'index.php?product_cat=$matches[1]';
			}
		}

		return apply_filters( 'surerank_product_category_rewrite_rules', $rules );
	}

	/**
	 * Remove Product Category Base
	 * Removes the product category base from product category links.
	 *
	 * @param string $termlink Original term link.
	 * @param object $term Term object.
	 * @param string $taxonomy Taxonomy name.
	 * @return string Modified term link.
	 * @since 1.0.0
	 */
	public function surerank_remove_product_category_base( $termlink, $term, $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			$category_base = get_option( 'woocommerce_permalinks' )['category_base'] ? get_option( 'woocommerce_permalinks' )['category_base'] : 'product-category';
			$termlink      = str_replace( '/' . $category_base, '', $termlink );
		}
		return $termlink;
	}

	/**
	 * Product Category Redirect
	 * Redirects URLs containing the product category base to clean URLs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function surerank_product_category_redirect() {
		if ( ! is_404() ) {
			return;
		}
		$category_base = get_option( 'woocommerce_permalinks' )['category_base'] ? get_option( 'woocommerce_permalinks' )['category_base'] : 'product-category';
		global $wp;
		$current_url = user_trailingslashit( home_url( add_query_arg( [], $wp->request ) ) );
		$regex       = sprintf( '/\/%s\//', str_replace( '/', '\/', $category_base ) );
		if ( preg_match( $regex, $current_url ) ) {
			$new_url = str_replace( '/' . $category_base, '', $current_url );
			wp_safe_redirect( $new_url, 301 );
			exit;
		}
	}

	/**
	 * Generate Clean Category Rules
	 * Generates rewrite rules for each category to allow cleaner URLs.
	 *
	 * @return array<string, mixed> Generated rewrite rules for categories.
	 * @since 1.0.0
	 */
	private function generate_clean_category_rules() {
		$rewrite_rules = [];
		$categories    = Utils::get_all_categories();
		$prefix        = Utils::get_blog_prefix();

		foreach ( $categories as $category ) {
			$path          = Utils::get_category_path( $category ) . $category->slug;
			$rewrite_rules = $this->append_category_rewrite( $rewrite_rules, $path, $prefix );
		}
		return $rewrite_rules;
	}

	/**
	 * Append Category Rewrite
	 * Adds a specific category's rewrite rule to the rules array.
	 *
	 * @param array<string, mixed> $rules Existing rewrite rules.
	 * @param string               $path Category path.
	 * @param string               $prefix Blog prefix if applicable.
	 * @return array<string, mixed> Modified rewrite rules.
	 * @since 1.0.0
	 */
	private function append_category_rewrite( $rules, $path, $prefix ) {
		$rules[ "^{$prefix}({$path})/page/([0-9]{1,})/?$" ] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
		$rules[ "^{$prefix}({$path})/?$" ]                  = 'index.php?category_name=$matches[1]';
		return $rules;
	}
}
