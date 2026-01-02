<?php
/**
 * Synchronize Taxonomies
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\BatchProcess;

use SureRank\Inc\Functions\Cache;
use SureRank\Inc\Sitemap\Sitemap;
use SureRank\Inc\Sitemap\Utils;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;
use WP_Term;

/**
 * Synchronize Taxonomies
 *
 * @since 1.2.0
 */
class Sync_Taxonomies extends Sitemap {

	use Get_Instance;
	use Logger;

	/**
	 * Offset
	 *
	 * @var int
	 */
	private $offset = 0;

	/**
	 * Taxonomy
	 *
	 * @var string
	 */
	private $taxonomy = '';

	/**
	 * Chunk Size
	 *
	 * @var int
	 */
	private $chunk_size = 20;

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 * @param int    $offset The offset for pagination.
	 * @param string $taxonomy The taxonomy to process.
	 * @param int    $chunk_size The chunk size for pagination.
	 */
	public function __construct( $offset = 0, $taxonomy = '', $chunk_size = 20 ) {
		$this->offset     = $offset;
		$this->taxonomy   = $taxonomy;
		$this->chunk_size = $chunk_size;
		Cache::init();
	}

	/**
	 * Import
	 *
	 * @since 1.2.0
	 * @return array<string, mixed>
	 */
	public function import() {
		$taxonomy       = ! empty( $this->taxonomy ) ? $this->taxonomy : 'any';
		$current_offset = $this->offset;

		$terms = $this->get_terms( $current_offset, $this->chunk_size );

		if ( empty( $terms ) ) {
			return [
				'success' => true,
				'msg'     => __( 'No terms found for processing.', 'surerank' ),
			];
		}

		$json_data = $this->generate_taxonomies_json( $terms );

		$file_index = ( $current_offset / $this->chunk_size ) + 1;
		$this->save_json_cache( $json_data, $taxonomy, $file_index );
		/* translators: %d: number of terms. */
		$message = sprintf( __( 'JSON generation completed for %d terms.', 'surerank' ), count( $terms ) );
		return [
			'success' => true,
			'msg'     => $message,
		];
	}

	/**
	 * Get terms with pagination.
	 *
	 * @param int $offset Offset for pagination.
	 * @param int $chunk_size Number of terms to retrieve.
	 * @return array<int, WP_Term> Array of WP_Term objects.
	 */
	private function get_terms( $offset, $chunk_size ) {
		$taxonomy          = ! empty( $this->taxonomy ) ? $this->taxonomy : 'any';
		$no_index_settings = $this->get_noindex_settings();

		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'number'     => $chunk_size,
			'offset'     => $offset,
			'orderby'    => 'term_id',
			'order'      => 'DESC',
		];

		$args['meta_query'] = Utils::get_indexable_meta_query( $taxonomy ); //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		$args = apply_filters( 'surerank_sitemap_taxonomies_cache_args', $args, $taxonomy );

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		return array_filter(
			$terms,
			static function( $term ) {
				return $term instanceof WP_Term;
			}
		);
	}

	/**
	 * Generate JSON data for taxonomies
	 *
	 * @param array<WP_Term> $terms Array of WP_Term objects.
	 * @since 1.2.0
	 * @return array<int, array<string, mixed>>
	 */
	private function generate_taxonomies_json( $terms ) {
		$json_data = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$term_data = [
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'link'        => get_term_link( $term ),
				'taxonomy'    => $term->taxonomy,
				'description' => $term->description,
				'count'       => $term->count,
				'updated'     => current_time( 'c' ),
			];

			$json_data[] = $term_data;
		}

		return $json_data;
	}

	/**
	 * Save JSON data to cache
	 *
	 * @param array<int, array<string, mixed>> $json_data The JSON data.
	 * @param string                           $taxonomy The taxonomy name.
	 * @param int                              $file_index The file index.
	 * @since 1.2.0
	 * @return void
	 */
	private function save_json_cache( array $json_data, string $taxonomy, int $file_index ) {
		$safe_taxonomy = sanitize_key( $taxonomy );
		$tax_prefix    = self::get_taxonomy_prefix();
		$filename      = $tax_prefix . '-' . $safe_taxonomy . '-chunk-' . absint( $file_index ) . '.json';
		self::log( 'Saving JSON cache for ' . $taxonomy . ' (file: ' . $filename . ')' );

		$json_string = wp_json_encode( $json_data );
		if ( false === $json_string ) {
			return;
		}

		Cache::store_file( 'sitemap/' . $filename, $json_string );
		Cache::update_sitemap_index( $tax_prefix . '-' . $safe_taxonomy, $file_index, count( $json_data ) );
	}

}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Sync_Taxonomies::get_instance();
