<?php
/**
 * Breadcrumb.php
 *
 * This file handles functionality for all Breadcrumbs.
 *
 * @package surerank
 */

namespace SureRank\Inc\Frontend;

use SureRank\Inc\Traits\Get_Instance;
use WP_Post;
use WP_Term;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumbs
 *
 * This class handles functionality for all Breadcrumbs.
 */
class Breadcrumbs {

	use Get_Instance;

	/**
	 * Breadcrumb trail.
	 *
	 * @var array<array{name: string, link: string}>
	 */
	private $crumbs = [];

	/**
	 * Get the breadcrumb trail.
	 *
	 * @return array<array<string, mixed>>
	 */
	public function get_crumbs() {
		if ( empty( $this->crumbs ) ) {
			$this->generate();
		}

		return $this->crumbs;
	}

	/**
	 * Generate the breadcrumb trail.
	 *
	 * @return void
	 */
	private function generate(): void {
		$this->maybe_add_home_crumb();
		$this->add_page_specific_crumbs();
	}

	/**
	 * Add page-specific breadcrumbs based on current page type.
	 *
	 * @return void
	 */
	private function add_page_specific_crumbs(): void {
		$crumb_handler = $this->get_crumb_handler();
		if ( $crumb_handler ) {
			$crumb_handler();
		}
	}

	/**
	 * Get the appropriate crumb handler for the current page.
	 *
	 * @return mixed The crumb handler callback or null.
	 */
	private function get_crumb_handler() {
		$handlers = $this->get_crumb_handlers();

		foreach ( $handlers as $condition => $handler ) {
			if ( $this->check_page_condition( $condition ) ) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * Get all crumb handlers mapped to their conditions.
	 *
	 * @return array<string, array<int, $this|string>> Map of conditions to handlers.
	 */
	private function get_crumb_handlers(): array {
		return [
			'category'  => [ $this, 'add_category_crumbs' ],
			'tag'       => [ $this, 'add_tag_crumbs' ],
			'tax'       => [ $this, 'add_tax_crumbs' ],
			'singular'  => [ $this, 'add_singular_crumbs' ],
			'author'    => [ $this, 'add_author_crumbs' ],
			'date'      => [ $this, 'add_date_crumbs' ],
			'search'    => [ $this, 'add_search_crumb' ],
			'not_found' => [ $this, 'add_404_crumb' ],
		];
	}

	/**
	 * Check if a page condition is met.
	 *
	 * @param string $condition The condition to check.
	 * @return bool True if condition is met.
	 */
	private function check_page_condition( string $condition ): bool {
		$checks = [
			'category'  => 'is_category',
			'tag'       => 'is_tag',
			'tax'       => 'is_tax',
			'singular'  => 'is_singular',
			'author'    => 'is_author',
			'date'      => 'is_date',
			'search'    => 'is_search',
			'not_found' => 'is_404',
		];

		return isset( $checks[ $condition ] ) && call_user_func( $checks[ $condition ] );
	}

	/**
	 * Add search results breadcrumb.
	 *
	 * @return void
	 */
	private function add_search_crumb(): void {
		$this->add_crumb( 'Search results for: ' . get_search_query(), '' );
	}

	/**
	 * Add 404 page breadcrumb.
	 *
	 * @return void
	 */
	private function add_404_crumb(): void {
		$this->add_crumb( '404 Not Found', '' );
	}

	/**
	 * Add category breadcrumbs.
	 *
	 * @return void
	 */
	private function add_category_crumbs() {
		$category = get_queried_object();

		if ( $category instanceof WP_Term ) {
			$this->add_term_hierarchy_crumbs( $category, 'category' );
		}
	}

	/**
	 * Add tag breadcrumbs.
	 *
	 * @return void
	 */
	private function add_tag_crumbs() {
		$tag = get_queried_object();

		if ( $tag instanceof WP_Term ) {
			$this->add_crumb( $tag->name, get_tag_link( $tag ) );
		}
	}

	/**
	 * Add taxonomy breadcrumbs.
	 *
	 * @return void
	 */
	private function add_tax_crumbs() {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$this->add_term_hierarchy_crumbs( $term, $term->taxonomy );
		}
	}

	/**
	 * Add singular (post or page) breadcrumbs.
	 *
	 * @return void
	 */
	private function add_singular_crumbs() {
		global $post;
		if ( ! ( $post instanceof WP_Post ) || ! $post->post_type ) {
			return;
		}

		$this->add_post_type_breadcrumbs( $post );
		$this->add_crumb( get_the_title( $post ), get_permalink( $post ) );
	}

	/**
	 * Add post type specific breadcrumbs.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function add_post_type_breadcrumbs( WP_Post $post ): void {
		if ( 'product' === $post->post_type ) {
			$this->add_product_breadcrumbs( $post );
			return;
		}

		$this->add_custom_post_type_archive_crumb( $post );
		$this->add_post_hierarchy_or_taxonomy_crumbs( $post );
	}

	/**
	 * Add product specific breadcrumbs.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function add_product_breadcrumbs( WP_Post $post ): void {
		$this->add_shop_crumb();
		$this->add_product_terms_crumbs( $post );
	}

	/**
	 * Add shop breadcrumb if WooCommerce is active.
	 *
	 * @return void
	 */
	private function add_shop_crumb(): void {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		$shop_page_id   = wc_get_page_id( 'shop' );
		$shop_permalink = get_permalink( $shop_page_id );

		if ( $shop_permalink ) {
			$this->add_crumb( 'Shop', $shop_permalink );
		}
	}

	/**
	 * Add custom post type archive crumb.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function add_custom_post_type_archive_crumb( WP_Post $post ): void {
		$excluded_types = [ 'post', 'page', 'product' ];
		if ( in_array( $post->post_type, $excluded_types, true ) ) {
			return;
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj ) {
			return;
		}

		$archive_link = get_post_type_archive_link( $post->post_type );
		if ( $archive_link ) {
			$this->add_crumb( $post_type_obj->labels->singular_name, $archive_link );
		}
	}

	/**
	 * Add post hierarchy or taxonomy breadcrumbs.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function add_post_hierarchy_or_taxonomy_crumbs( WP_Post $post ): void {
		if ( is_post_type_hierarchical( $post->post_type ) ) {
			$this->add_post_hierarchy_crumbs( $post );
			return;
		}

		$this->add_primary_taxonomy_crumbs( $post );
	}

	/**
	 * Add primary taxonomy breadcrumbs.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function add_primary_taxonomy_crumbs( WP_Post $post ): void {
		$taxonomy = $this->get_primary_taxonomy( $post->post_type );
		if ( empty( $taxonomy ) ) {
			return;
		}

		$terms = get_the_terms( $post->ID, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return;
		}

		$primary_term = $terms[0];
		$this->add_term_hierarchy_crumbs( $primary_term, $taxonomy );
	}

	/**
	 * Add breadcrumbs for a term and its ancestors.
	 *
	 * @param WP_Term $term Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 *
	 * @return void
	 */
	private function add_term_hierarchy_crumbs( WP_Term $term, string $taxonomy ) {
		$ancestors = $this->get_term_ancestors( $term, $taxonomy );

		foreach ( $ancestors as $ancestor ) {
			$link = $this->get_term_link( $ancestor, $taxonomy );
			if ( $link ) {
				$this->add_crumb( $ancestor->name, $link );
			}
		}

		if ( empty( $ancestors ) || end( $ancestors )->term_id !== $term->term_id ) {
			$link = $this->get_term_link( $term, $taxonomy );
			if ( $link ) {
				$this->add_crumb( $term->name, $link );
			}
		}
	}

	/**
	 * Get a term's ancestors in order from root to direct parent.
	 *
	 * @param WP_Term $term Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 * @return array<int, \WP_Term> Array of WP_Term objects.
	 */
	private function get_term_ancestors( WP_Term $term, string $taxonomy ) {
		$ancestors     = [];
		$original_term = $term;

		while ( $term->parent ) {
			$term = get_term( $term->parent, $taxonomy );

			if ( ! ( $term instanceof WP_Term ) ) {
				break;
			}

			array_unshift( $ancestors, $term );
		}

		return $ancestors;
	}

	/**
	 * Get term link safely.
	 *
	 * @param WP_Term $term Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 * @return string|null Term link or null if error.
	 */
	private function get_term_link( WP_Term $term, string $taxonomy ): ?string {
		$link = get_term_link( $term->term_id, $taxonomy );
		return is_wp_error( $link ) ? null : $link;
	}

	/**
	 * Get the primary taxonomy for a given post type.
	 *
	 * @param string $post_type The post type slug.
	 * @return string|null The taxonomy slug or null if not found.
	 */
	private function get_primary_taxonomy( string $post_type ): ?string {
		$taxonomies = get_object_taxonomies( $post_type, 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );

			if ( $taxonomy_obj && $taxonomy_obj->hierarchical ) {
				return $taxonomy;
			}
		}

		return $taxonomies[0] ?? null;
	}

	/**
	 * Add breadcrumbs for hierarchical post types like Pages.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function add_post_hierarchy_crumbs( WP_Post $post ) {
		$ancestors = $this->get_post_ancestors( $post );

		foreach ( $ancestors as $ancestor ) {
			if ( get_permalink( $ancestor ) ) {
				$this->add_crumb( get_the_title( $ancestor ), get_permalink( $ancestor ) );
			}
		}
	}

	/**
	 * Get a post's ancestors in order from root to direct parent.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<int, \WP_Post> Array of WP_Post objects.
	 */
	private function get_post_ancestors( WP_Post $post ) {
		$parents       = [];
		$original_post = $post;

		while ( $post->post_parent ) {
			$post = get_post( $post->post_parent );
			if ( $post instanceof WP_Post ) {
				array_unshift( $parents, $post );
			} else {
				break;
			}
		}

		return $parents;
	}

	/**
	 * Add product category breadcrumbs.
	 *
	 * @param WP_Post $post Product post object.
	 * @return void
	 */
	private function add_product_terms_crumbs( WP_Post $post ) {
		$term_name  = apply_filters( 'surerank_product_breadcrumbs_term_name', 'product_cat' );
		$categories = get_the_terms( $post->ID, $term_name );

		if ( is_array( $categories ) && ! empty( $categories ) ) {
			$primary_category = $categories[0];
			$this->add_term_hierarchy_crumbs( $primary_category, $term_name );
		}
	}

	/**
	 * Add author breadcrumbs.
	 *
	 * @return void
	 */
	private function add_author_crumbs() {
		$author = get_queried_object();

		if ( $author instanceof WP_User ) {
			$this->add_crumb( 'Author: ' . $author->display_name, get_author_posts_url( $author->ID ) );
		}
	}

	/**
	 * Add date breadcrumbs.
	 *
	 * @return void
	 */
	private function add_date_crumbs() {
		if ( is_year() ) {
			$year = (string) get_the_date( 'Y' );
			$this->add_crumb( $year, (string) get_year_link( intval( $year ) ) );
		}
		if ( is_month() ) {
			$year  = get_the_date( 'Y' );
			$month = get_the_date( 'm' );
			$this->add_crumb( (string) get_the_date( 'F Y' ), (string) get_month_link( intval( $year ), intval( $month ) ) );
		}
		if ( is_day() ) {
			$year  = get_the_date( 'Y' );
			$month = get_the_date( 'm' );
			$day   = get_the_date( 'd' );
			$this->add_crumb( (string) get_the_date( 'j F Y' ), (string) get_day_link( intval( $year ), intval( $month ), intval( $day ) ) );
		}
	}

	/**
	 * Add an item to the breadcrumb trail.
	 *
	 * @param string $name Name of the breadcrumb.
	 * @param string $link URL for the breadcrumb.
	 * @return void
	 */
	private function add_crumb( string $name, string $link = '' ) {

		if ( empty( $name ) ) {
			return;
		}

		$this->crumbs[] = [
			'name' => esc_html( $name ),
			'link' => esc_url( $link ),
		];
	}

	/**
	 * Add a home breadcrumb.
	 *
	 * @return void
	 */
	private function maybe_add_home_crumb() {
		$home_name = apply_filters( 'surerank_home_breadcrumb_name', 'Home' );
		$this->add_crumb( $home_name, home_url() );
	}
}
