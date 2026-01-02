<?php
/**
 * Abstract Sitemap Class
 *
 * This file contains the abstract base class for sitemap generation.
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Get;
use WP_Query;
use WP_Term;

/**
 * Abstract Sitemap
 * Base class for sitemap generation with common functionality.
 *
 * @since 1.2.0
 */
abstract class Sitemap {

	/**
	 * Get noindex settings.
	 *
	 * @return array<string, mixed>|array<int, string>
	 */
	public function get_noindex_settings() {
		$settings = Get::option( SURERANK_SETTINGS );
		return $settings['no_index'] ?? [];
	}

	/**
	 * Get post type prefix for sitemap URLs.
	 *
	 * @return string
	 */
	public static function get_post_type_prefix() {
		return apply_filters( 'surerank_sitemap_post_type_prefix', 'post-type' );
	}

	/**
	 * Get taxonomy prefix for sitemap URLs.
	 *
	 * @return string
	 */
	public static function get_taxonomy_prefix() {
		return apply_filters( 'surerank_sitemap_taxonomy_prefix', 'taxonomy-type' );
	}

	/**
	 * Check if the post is noindex.
	 *
	 * @param int|null    $post_id The post ID.
	 * @param string|null $post_type The post type.
	 * @return bool
	 */
	public function is_noindex( $post_id = null, $post_type = null ) {
		if ( ! $post_id || ! $post_type ) {
			return true;
		}
		return $this->indexable( $post_id, $post_type, 'get_post_meta', 'noindex' );
	}

	/**
	 * Check if the term is noindex.
	 *
	 * @param int|null    $term_id The term ID.
	 * @param string|null $taxonomy The taxonomy.
	 * @return bool
	 */
	public function is_noindex_term( $term_id = null, $taxonomy = null ) {
		if ( ! $term_id || ! $taxonomy ) {
			return true;
		}
		return $this->indexable( $term_id, $taxonomy, 'get_term_meta', 'noindex' );
	}

	/**
	 * Get meta query for indexable content based on no_index settings
	 *
	 * @param string $content_type The post type or taxonomy name.
	 * @return array<int|string, mixed> The meta query array.
	 */
	public function get_indexable_meta_query( string $content_type ): array {
		return Utils::get_indexable_meta_query( $content_type );
	}

	/**
	 * Meta Query Args.
	 *
	 * @param array<string, mixed> $args The arguments to modify.
	 * @return array<string, mixed>|array<int, string>
	 */
	public function meta_query_args( $args ) {
		$args['meta_query'] = [ //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'     => 'surerank_settings_post_no_index',
				'value'   => 'no',
				'compare' => '=',
			],
		];
		return $args;
	}

	/**
	 * Get total count.
	 *
	 * @param string $type The type to get the total count for.
	 * @return int
	 */
	public function get_total_count( $type ) {
		$no_index = $this->get_noindex_settings();

		if ( in_array( $type, $no_index, true ) ) {
			$total_count = $this->check_noindex( $type, 'count' );
		} else {
			if ( post_type_exists( $type ) ) {
				$total_count = wp_count_posts( $type )->publish ?? 0;
			} else {
				$total_count = wp_count_terms( [ 'taxonomy' => $type ] );
			}
		}

		return $total_count;
	}

	/**
	 * Helper function to check noindex status.
	 *
	 * @param string $type The type to check against (post type or taxonomy).
	 * @param string $action The action to perform (check, count, or get).
	 * @return bool|int
	 */
	protected function check_noindex( $type, $action = 'check' ) {
		$taxonomy_type = str_replace( '-', '_', $type );
		$count         = $this->get_content_count( $type, $taxonomy_type );

		if ( $action === 'count' ) {
			return $count;
		}

		return $count === 0 && $this->is_type_noindexed( $type, $taxonomy_type );
	}

	/**
	 * Get posts query for sitemap generation.
	 *
	 * @param string               $type Post type.
	 * @param int|null             $page Page number (optional for HTML sitemaps).
	 * @param int|null             $offset Number of posts to retrieve (optional for HTML sitemaps).
	 * @param array<string, mixed> $additional_args Additional arguments.
	 * @return WP_Query|null
	 */
	protected function get_posts_query( $type, $page = null, $offset = null, $additional_args = [] ) {
		if ( $this->check_noindex( $type, 'check' ) ) {
			return null;
		}

		$no_index = $this->get_noindex_settings();

		$args = [
			'post_type'     => $type,
			'post_status'   => 'publish',
			'cache_results' => true,
			'post__not_in'  => apply_filters( 'surerank_exclude_posts_from_sitemap', [] ),
		];

		$args = array_merge( $args, $additional_args );

		if ( $page !== null && $offset !== null ) {
			$args['posts_per_page'] = $offset;
			$args['paged']          = $page;
		} else {
			$args['posts_per_page'] = -1;
		}

		if ( in_array( $type, $no_index, true ) ) {
			$args = $this->meta_query_args( $args );
		}

		return new WP_Query( $args );
	}

	/**
	 * Get terms query for sitemap generation.
	 *
	 * @param string   $taxonomy Taxonomy name.
	 * @param int|null $page Page number (optional for HTML sitemaps).
	 * @param int|null $offset Number of terms to retrieve (optional for HTML sitemaps).
	 * @return array<int, int|string|WP_Term>|bool
	 */
	protected function get_terms_query( $taxonomy, $page = null, $offset = null ) {
		if ( $this->check_noindex( $taxonomy, 'check' ) ) {
			return false;
		}

		$no_index = $this->get_noindex_settings();

		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		];

		if ( $page !== null && $offset !== null ) {
			$calculated_offset = ( $page - 1 ) * $offset;
			$args['number']    = $offset;
			$args['offset']    = $calculated_offset;
		}

		if ( in_array( $taxonomy, $no_index, true ) ) {
			$args = $this->meta_query_args( $args );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return false;
		}

		if ( empty( $terms ) || ! is_array( $terms ) ) {
			return false;
		}

		return $terms;
	}

	/**
	 * Helper function to check noindex status.
	 *
	 * @param mixed  $id The ID to check (post ID or term ID).
	 * @param string $type The type to check against (post type or taxonomy).
	 * @param string $meta_key The meta key to look up.
	 * @param string $settings_key The settings key to fall back to.
	 * @return bool
	 */
	protected function indexable( $id, $type, $meta_key, $settings_key ) {
		if ( ! $id ) {
			return true;
		}

		$meta = $meta_key === 'get_post_meta'
			? get_post_meta( $id, 'surerank_settings_post_no_index', true )
			: get_term_meta( $id, 'surerank_settings_post_no_index', true );

		if ( $meta === 'yes' ) {
			return true;
		}

		if ( $meta === 'no' ) {
			return false;
		}

		$no_index = $this->get_noindex_settings();

		if ( in_array( $type, $no_index, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get content count for a type.
	 *
	 * @param string $type The type to check.
	 * @param string $taxonomy_type The taxonomy type.
	 * @return int Content count.
	 */
	private function get_content_count( string $type, string $taxonomy_type ): int {
		if ( taxonomy_exists( $taxonomy_type ) ) {
			return $this->get_taxonomy_count( $taxonomy_type );
		}

		if ( post_type_exists( $type ) ) {
			return $this->get_post_type_count( $type );
		}

		return 0;
	}

	/**
	 * Get taxonomy count.
	 *
	 * @param string $taxonomy_type The taxonomy type.
	 * @return int Term count.
	 */
	private function get_taxonomy_count( string $taxonomy_type ): int {
		$args = [
			'taxonomy'   => $taxonomy_type,
			'hide_empty' => false,
		];

		$args  = $this->meta_query_args( $args );
		$terms = get_terms( $args );

		return is_array( $terms ) ? count( $terms ) : 0;
	}

	/**
	 * Get post type count.
	 *
	 * @param string $type The post type.
	 * @return int Post count.
	 */
	private function get_post_type_count( string $type ): int {
		$args = [
			'post_type'      => $type,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		];

		$args  = $this->meta_query_args( $args );
		$query = new WP_Query( $args );

		return $query->found_posts;
	}

	/**
	 * Check if type is in noindex settings.
	 *
	 * @param string $type The type to check.
	 * @param string $taxonomy_type The taxonomy type.
	 * @return bool True if noindexed.
	 */
	private function is_type_noindexed( string $type, string $taxonomy_type ): bool {
		$no_index = $this->get_noindex_settings();

		if ( taxonomy_exists( $taxonomy_type ) ) {
			return in_array( $taxonomy_type, $no_index, true );
		}

		return in_array( $type, $no_index, true );
	}
}
