<?php
/**
 * Importer Interface
 *
 * Defines the contract for all importer classes in the plugin.
 *
 * @package SureRank\Inc\Importers
 * @since   1.1.0
 */

namespace SureRank\Inc\Importers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Importer
 *
 * Every import-related method returns:
 * [
 *     'success' => bool,
 *     'message' => string,
 * ]
 */
interface Importer {

	/**
	 * Get the human-readable name of the plugin being imported from.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string;

	/**
	 * Get the plugin file path for the plugin being imported from.
	 *
	 * @return string
	 */
	public function get_plugin_file(): string;

	/**
	 * Check if the plugin is active.
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function is_plugin_active(): bool;

	/**
	 * Detect whether the source plugin has data for the given post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function detect_post( int $post_id): array;

	/**
	 * Detect whether the source plugin has data for the given term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function detect_term( int $term_id): array;

	/**
	 * Import data for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post( int $post_id): array;

	/**
	 * Import data for a specific term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term( int $term_id): array;

	/**
	 * Import global settings from the source plugin.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function import_global_settings(): array;

	/**
	 * Clean up source-plugin data after import (if desired).
	 *
	 * @return array{success: bool, message: string}
	 */
	public function cleanup(): array;

	/**
	 * Import meta-robots settings for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post_meta_robots( int $post_id): array;

	/**
	 * Import meta-robots settings for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term_meta_robots( int $term_id): array;

	/**
	 * Import general SEO settings for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post_general_settings( int $post_id): array;

	/**
	 * Import general SEO settings for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term_general_settings( int $term_id): array;

	/**
	 * Import social metadata for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post_social( int $post_id): array;

	/**
	 * Import social metadata for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term_social( int $term_id): array;

	/**
	 * Persist SureRank settings for a post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data     Prepared meta array.
	 * @return array{success: bool, message: string}
	 */
	public function update_post_meta_data( int $post_id, array $data): array;

	/**
	 * Persist SureRank settings for a term.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $data    Prepared meta array.
	 * @return array{success: bool, message: string}
	 */
	public function update_term_meta_data( int $term_id, array $data): array;

	/**
	 * Get the keys that should be excluded from the import.
	 *
	 * @param array<string> $post_types Post types to check.
	 * @param int           $batch_size Number of posts to fetch in one batch.
	 * @param int           $offset     Offset for pagination.
	 * @return array{total_items: int, post_ids: array<string|int>}
	 * @since 1.1.0
	 */
	public function get_count_and_posts( $post_types, $batch_size, $offset );

	/**
	 * Get the keys that should be excluded from the import.
	 *
	 * @param array<string> $taxonomies Term types to check.
	 * @param array<mixed>  $taxonomies_objects Array of taxonomy objects.
	 * @param int           $batch_size Number of terms to fetch in one batch.
	 * @param int           $offset     Offset for pagination.
	 * @return array<mixed>
	 * @since 1.1.0
	 */
	public function get_count_and_terms( $taxonomies, $taxonomies_objects, $batch_size, $offset );
}
