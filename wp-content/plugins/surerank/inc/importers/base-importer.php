<?php
/**
 * Base Importer Class
 *
 * Provides a base for importing data from other SEO plugins.
 *
 * @package SureRank\Inc\Importers
 * @since   1.1.0
 */

namespace SureRank\Inc\Importers;

use SureRank\Inc\Functions\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Class BaseImporter
 *
 * Provides shared functionality for importers.
 */
abstract class BaseImporter implements Importer {

	/**
	 * Post types supported by the importer.
	 *
	 * @var array<string, string>
	 */
	protected array $post_types = [];

	/**
	 * Taxonomies supported by the importer.
	 *
	 * @var array<string, \WP_Taxonomy>
	 */
	protected array $taxonomies = [];

	/**
	 * Default SureRank meta for the current post or term.
	 *
	 * @var array<string, mixed>
	 */
	protected array $default_surerank_meta = [];

	/**
	 * Raw source meta for the current post or term.
	 *
	 * @var array<string, mixed>
	 */
	protected array $source_meta = [];

	/**
	 * SureRank settings to be imported.
	 *
	 * @var array<string, mixed>
	 */
	protected array $surerank_settings = [];

	/**
	 * Source plugin settings to be imported.
	 *
	 * @var array<string, mixed>
	 */
	protected array $source_settings = [];

	/**
	 * Type of the current post or term.
	 *
	 * @var string
	 */
	protected string $type = '';

	/**
	 * Constructor to initialize the importer.
	 */
	public function __construct() {
		$this->post_types = get_post_types( [ 'public' => true ], 'names' );
		$this->taxonomies = ImporterUtils::get_excluded_taxonomies();
	}

	/**
	 * Get the name of the source plugin.
	 *
	 * @return string
	 * @since 1.1.0
	 */
	abstract public function get_plugin_name(): string;

	/**
	 * Detect whether the source plugin has data for the given post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function detect_post( int $post_id ): array {
		$meta          = get_post_meta( $post_id );
		$excluded_keys = $this->get_excluded_meta_keys();

		if ( $this->has_source_meta( $meta, $excluded_keys ) ) {
			return ImporterUtils::build_response(
				sprintf(
					// translators: 1: plugin name, 2: post ID.
					__( '%1$s data detected for post %2$d.', 'surerank' ),
					$this->get_plugin_name(),
					$post_id
				),
				true
			);
		}

		ImporterUtils::update_surerank_migrated( $post_id );

		return ImporterUtils::build_response(
			sprintf(
				// translators: 1: plugin name, 2: post ID.
				__( 'No %1$s data found for post %2$d.', 'surerank' ),
				$this->get_plugin_name(),
				$post_id
			),
			false,
			[],
			true
		);
	}

	/**
	 * Import data for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post( int $post_id ): array {
		$post_type = get_post_type( $post_id ) ? get_post_type( $post_id ) : '';
		$is_valid  = ImporterUtils::is_type_valid( $post_type, $this->get_not_allowed_types(), $this->post_types );
		if ( ! $is_valid ) {
			return ImporterUtils::not_valid_response( $post_id );
		}

		$this->type                  = $post_type;
		$this->default_surerank_meta = Settings::prep_post_meta( $post_id, $post_type, false );
		$this->source_meta           = $this->get_source_meta_data( $post_id, false, $this->type );
		unset( $this->default_surerank_meta['schemas'] );

		[ $any_success, $combine_data ] = $this->import_and_persist_meta( $post_id, false );

		if ( $any_success ) {
			return ImporterUtils::build_response(
				sprintf(
					// translators: 1: plugin name, 2: post ID.
					__( '%1$s data imported successfully for post %2$d.', 'surerank' ),
					$this->get_plugin_name(),
					$post_id
				),
				true,
				$combine_data
			);
		}

		return ImporterUtils::build_response(
			sprintf(
				// translators: 1: plugin name, 2: post ID.
				__( 'No %1$s fields were imported for post %2$d.', 'surerank' ),
				$this->get_plugin_name(),
				$post_id
			),
			false,
			$combine_data
		);
	}

	/**
	 * Import data for a specific term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term( int $term_id ): array {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return ImporterUtils::not_valid_response( $term_id, true );
		}
		$is_valid = ImporterUtils::is_type_valid( $term->taxonomy, $this->get_not_allowed_types(), $this->taxonomies, true );
		if ( ! $is_valid ) {
			return ImporterUtils::not_valid_response( $term_id, true );
		}
		$this->type                  = $term->taxonomy;
		$this->default_surerank_meta = Settings::prep_term_meta( $term_id, $term->taxonomy, true );
		unset( $this->default_surerank_meta['schemas'] );
		$this->source_meta = $this->get_source_meta_data( $term_id, true, $this->type );

		[$any_success, $combine_data] = $this->import_and_persist_meta( $term_id, true );

		if ( $any_success ) {
			return ImporterUtils::build_response(
				sprintf(
					// translators: 1: plugin name, 2: term ID.
					__( '%1$s data imported successfully for term %2$d.', 'surerank' ),
					$this->get_plugin_name(),
					$term_id
				),
				true,
				$combine_data
			);
		}

		return ImporterUtils::build_response(
			sprintf(
				// translators: 1: plugin name, 2: term ID.
				__( 'No %1$s fields were imported for term %2$d.', 'surerank' ),
				$this->get_plugin_name(),
				$term_id
			),
			false,
			$combine_data
		);
	}

	/**
	 * Persist SureRank settings for a post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data     Prepared meta array.
	 * @return array{success: bool, message: string}
	 */
	public function update_post_meta_data( int $post_id, array $data ): array {
		return ImporterUtils::update_post_meta_data( $post_id, $data );
	}

	/**
	 * Persist SureRank settings for a term.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $data    Prepared meta array.
	 * @return array{success: bool, message: string}
	 */
	public function update_term_meta_data( int $term_id, array $data ): array {
		return ImporterUtils::update_term_meta_data( $term_id, $data );
	}

	/**
	 * Clean up source-plugin data after import (if desired).
	 *
	 * @return array{success: bool, message: string}
	 */
	public function cleanup(): array {
		return ImporterUtils::build_response(
			sprintf(
				// translators: %s: plugin name.
				__( 'No clean-up required for %s data.', 'surerank' ),
				$this->get_plugin_name()
			),
			true
		);
	}

	/**
	 * Get the keys that should be excluded from the import.
	 *
	 * @param array<string> $post_types Post types to check.
	 * @param int           $batch_size Number of posts to fetch in one batch.
	 * @param int           $offset     Offset for pagination.
	 * @return array{total_items: int, post_ids: array<string|int>}
	 * @since 1.1.0
	 */
	public function get_count_and_posts( $post_types, $batch_size, $offset ) {
		$meta_prefix   = $this->get_meta_key_prefix() . '%';
		$excluded_keys = $this->get_excluded_meta_keys();
		$total_items   = ImporterUtils::get_total_posts_count( $post_types, $excluded_keys, $meta_prefix );
		$post_ids      = ImporterUtils::get_post_ids( $post_types, $excluded_keys, $meta_prefix, $batch_size, $offset );

		return [
			'total_items' => $total_items,
			'post_ids'    => $post_ids,
		];
	}

	/**
	 * Get the keys that should be excluded from the import.
	 *
	 * @param array<string> $taxonomies Term types to check.
	 * @param array<mixed>  $taxonomies_objects Taxonomy objects to check.
	 * @param int           $batch_size Number of terms to fetch in one batch.
	 * @param int           $offset     Offset for pagination.
	 * @return array<mixed>
	 * @since 1.1.0
	 */
	public function get_count_and_terms( $taxonomies, $taxonomies_objects, $batch_size, $offset ) {
		$meta_prefix   = $this->get_meta_key_prefix() . '%';
		$excluded_keys = $this->get_excluded_meta_keys();
		$total_items   = ImporterUtils::get_total_terms_count( $taxonomies, $excluded_keys, $meta_prefix );
		$term_ids      = ImporterUtils::get_term_ids( $taxonomies, $excluded_keys, $meta_prefix, $batch_size, $offset );

		return [
			'total_items' => $total_items,
			'term_ids'    => $term_ids,
		];
	}

	/**
	 * Get the not allowed types for the importer.
	 *
	 * @return array<string>
	 */
	abstract protected function get_not_allowed_types(): array;

	/**
	 * Get the source meta data for a post or term.
	 *
	 * @param int    $id          The ID of the post or term.
	 * @param bool   $is_taxonomy Whether it is a taxonomy.
	 * @param string $type        The type of post or term.
	 * @return array<string, mixed>
	 */
	abstract protected function get_source_meta_data( int $id, bool $is_taxonomy, string $type = '' ): array;

	/**
	 * Get the meta key prefix for the importer.
	 *
	 * @return string
	 */
	abstract protected function get_meta_key_prefix(): string;

	/**
	 * Get the meta keys to exclude for the importer.
	 *
	 * @return array<string>
	 */
	abstract protected function get_excluded_meta_keys(): array;

	/**
	 * Check if source meta data exists.
	 *
	 * @param array<string, mixed>|\WP_Error $meta          The meta data.
	 * @param array<string>                  $excluded_keys Keys to exclude.
	 * @return bool
	 */
	protected function has_source_meta( $meta, array $excluded_keys = [] ): bool {
		if ( ! $meta || is_wp_error( $meta ) ) {
			return false;
		}

		foreach ( array_keys( $meta ) as $key ) {
			if ( str_starts_with( (string) $key, $this->get_meta_key_prefix() ) && ! in_array( $key, $excluded_keys, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Import and persist meta for a post or term.
	 *
	 * @param int  $id          The ID of the post or term.
	 * @param bool $is_taxonomy Whether it is a taxonomy.
	 * @return array{bool, array<string, mixed>}
	 */
	protected function import_and_persist_meta( int $id, bool $is_taxonomy ): array {
		$general_settings = $is_taxonomy ? $this->import_term_general_settings( $id ) : $this->import_post_general_settings( $id );
		$robots_settings  = $is_taxonomy ? $this->import_term_meta_robots( $id ) : $this->import_post_meta_robots( $id );
		$social_settings  = $is_taxonomy ? $this->import_term_social( $id ) : $this->import_post_social( $id );

		$persist = $is_taxonomy
			? $this->update_term_meta_data( $id, $this->default_surerank_meta )
			: $this->update_post_meta_data( $id, $this->default_surerank_meta );

		$combine_data = [
			'general' => $general_settings,
			'robots'  => $robots_settings,
			'social'  => $social_settings,
			'updated' => $persist,
		];
		$any_success  = $general_settings['success'] || $robots_settings['success'] || $social_settings['success'] || $persist['success'];

		return [ $any_success, $combine_data ];
	}
}
