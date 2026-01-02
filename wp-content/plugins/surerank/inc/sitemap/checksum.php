<?php
/**
 * Sitemap Utilities
 *
 * Utility functions to manage XML headers, stylesheets, and other reusable functions for sitemaps.
 *
 * @since 1.2.0
 * @package SureRank
 */

namespace SureRank\Inc\Sitemap;

use SureRank\Inc\Admin\Sync;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Traits\Get_Instance;
use WP_Post;
use WP_Term;

/**
 * Checksum Utility Functions Class
 *
 * Provides methods for checksum generation.
 *
 * @since 1.2.0
 */
class Checksum {
	use Get_Instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! defined( 'SURERANK_SITEMAP_CHECKSUM' ) ) {
			define( 'SURERANK_SITEMAP_CHECKSUM', 'surerank_sitemap_cache_checksum' );
		}

		if ( ! defined( 'SURERANK_SITEMAP_CACHE_CHECKSUM' ) ) {
			define( 'SURERANK_SITEMAP_CACHE_CHECKSUM', 'surerank_sitemap_cache_updated_checksum' );
		}
		add_action( 'wp_after_insert_post', [ $this, 'handle_content_change' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'handle_post_delete' ] );
		add_action( 'created_term', [ $this, 'handle_term_change' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'handle_term_change' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'handle_term_delete' ], 10, 4 );
	}

	/**
	 * Handle content change for posts.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post Post object.
	 * @param bool         $update Whether this is an existing post being updated.
	 * @return void
	 * @since 1.2.0
	 */
	public function handle_content_change( $post_id, $post = null, $update = false ) {
		// Avoid infinite loop.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( $this->should_exclude_post( $post_id, $post ) ) {
			return;
		}

		$this->update_checksum();
	}

	/**
	 * Handle post deletion.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 * @since 1.2.0
	 */
	public function handle_post_delete( $post_id ) {
		$this->update_checksum();
	}

	/**
	 * Handle term change.
	 *
	 * @param int    $term_id Term ID.
	 * @param int    $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 * @since 1.2.0
	 */
	public function handle_term_change( $term_id, $tt_id = null, $taxonomy = null ) {
		// Avoid infinite loop.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $taxonomy ) {
			return;
		}

		if ( $this->should_exclude_term( $term_id, $taxonomy ) ) {
			return;
		}

		$this->update_checksum();
	}

	/**
	 * Handle term deletion.
	 *
	 * @param int          $term_id Term ID.
	 * @param int          $tt_id Term taxonomy ID.
	 * @param string       $taxonomy Taxonomy slug.
	 * @param WP_Term|null $deleted_term Deleted term object.
	 * @return void
	 * @since 1.2.0
	 */
	public function handle_term_delete( $term_id, $tt_id = null, $taxonomy = null, $deleted_term = null ) {
		$this->update_checksum();
	}

	/**
	 * Update the checksum for a given post or term.
	 *
	 * @param string $checksum Checksum value.
	 * @return void
	 * @since 1.2.0
	 */
	public function update_cache_checksum( $checksum = '' ) {

		if ( empty( $checksum ) ) {
			$random_password = wp_generate_password( 20, true, true );
			$timestamp       = time();
			$checksum        = hash( 'sha256', $random_password . $timestamp );
		}

		// Save to options table or use transient for shorter duration.
		update_option( SURERANK_SITEMAP_CACHE_CHECKSUM, $checksum );
	}

	/**
	 * Get the current checksum.
	 *
	 * @return string
	 * @since 1.2.0
	 */
	public function get_checksum() {
		return get_option( SURERANK_SITEMAP_CHECKSUM, '' );
	}

	/**
	 * Get the current checksum.
	 *
	 * @return string
	 * @since 1.2.0
	 */
	public function get_cache_checksum() {
		return get_option( SURERANK_SITEMAP_CACHE_CHECKSUM, '' );
	}

	/**
	 * Clear the checksum.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public function clear_checksum() {
		delete_option( SURERANK_SITEMAP_CHECKSUM );
		delete_option( SURERANK_SITEMAP_CACHE_CHECKSUM );
	}

	/**
	 * Update the checksum for a given post or term.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public function update_checksum() {
		$random_password = wp_generate_password( 20, true, true );
		$timestamp       = time();
		$checksum        = hash( 'sha256', $random_password . $timestamp );

		// Save to options table or use transient for shorter duration.
		update_option( SURERANK_SITEMAP_CHECKSUM, $checksum );
	}

	/**
	 * Check if a post should be excluded from sitemap processing.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return bool True if post should be excluded, false otherwise.
	 * @since 1.2.0
	 */
	private function should_exclude_post( $post_id, $post ) {
		$no_index = Utils::get_noindex_settings();
		if ( in_array( $post->post_type, $no_index ) ) {
			return true;
		}

		if ( Get::post_meta( $post_id, 'surerank_settings_post_no_index', true ) === 'yes' ) {
			return true;
		}

		$included_post_types = Sync::get_instance()->get_included_post_types();

		if ( empty( $included_post_types ) ) {
			return true;
		}

		$included_post_types = array_keys( $included_post_types );
		if ( ! in_array( $post->post_type, $included_post_types, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a term should be excluded from sitemap processing.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return bool True if term should be excluded, false otherwise.
	 * @since 1.2.0
	 */
	private function should_exclude_term( $term_id, $taxonomy ) {
		$no_index = Utils::get_noindex_settings();
		if ( in_array( $taxonomy, $no_index ) ) {
			return true;
		}

		if ( Get::term_meta( $term_id, 'surerank_settings_post_no_index', true ) === 'yes' ) {
			return true;
		}

		$included_taxonomies = Sync::get_instance()->get_included_taxonomies();

		if ( empty( $included_taxonomies ) ) {
			return true;
		}

		$included_taxonomies = array_column( $included_taxonomies, 'slug' );
		if ( ! in_array( $taxonomy, $included_taxonomies, true ) ) {
			return true;
		}

		return false;
	}

}
