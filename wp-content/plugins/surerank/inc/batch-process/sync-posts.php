<?php
/**
 * Synchronize Posts
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\BatchProcess;

use SureRank\Inc\Functions\Cache;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Sitemap\Sitemap;
use SureRank\Inc\Sitemap\Utils;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;
use WP_Post;
use WP_Query;

/**
 * Synchronize Posts
 *
 * @since 1.2.0
 */
class Sync_Posts extends Sitemap {

	use Get_Instance;
	use Logger;

	/**
	 * Offset
	 *
	 * @var int
	 */
	private $offset = 0;

	/**
	 * Post Type
	 *
	 * @var string
	 */
	private $post_type = '';

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
	 * @param string $post_type The post type to process.
	 * @param int    $chunk_size The chunk size for pagination.
	 * @return void
	 */
	public function __construct( $offset = 0, $post_type = '', $chunk_size = 20 ) {
		$this->offset     = $offset;
		$this->post_type  = $post_type;
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
		$post_type      = ! empty( $this->post_type ) ? $this->post_type : 'any';
		$current_offset = $this->offset;

		$posts = $this->get_posts( $current_offset, $this->chunk_size );

		if ( empty( $posts ) ) {
			return [
				'success' => true,
				'msg'     => __( 'No posts found for processing.', 'surerank' ),
			];
		}

		$json_data = $this->generate_posts_json( $posts );

		$file_index = ( $current_offset / $this->chunk_size ) + 1;
		$this->save_json_cache( $json_data, $post_type, $file_index );
		/* translators: %d: number of posts */
		$message = sprintf( __( 'JSON generation completed for %d posts.', 'surerank' ), count( $posts ) );

		return [
			'success' => true,
			'msg'     => $message,
		];
	}

	/**
	 * Get posts with pagination.
	 *
	 * @param int $offset Offset for pagination.
	 * @param int $chunk_size Number of posts to retrieve.
	 * @return array<int|WP_Post> Array of post IDs.
	 */
	private function get_posts( $offset, $chunk_size ) {
		$post_type         = ! empty( $this->post_type ) ? $this->post_type : 'any';
		$no_index_settings = $this->get_noindex_settings();

		$args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $chunk_size,
			'offset'              => $offset,
			'orderby'             => 'ID',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		];

		$args['meta_query'] = Utils::get_indexable_meta_query( $post_type ); //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		$args = apply_filters( 'surerank_sitemap_posts_cache_args', $args, $post_type );

		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Generate JSON data for posts
	 *
	 * @param array<string, mixed> $posts Array of post data.
	 * @since 1.2.0
	 * @return array<int, array<string, mixed>>
	 */
	private function generate_posts_json( $posts ) {
		$json_data = [];

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$permalink = get_permalink( $post->ID );

			$post_data = [
				'id'          => $post->ID,
				'title'       => get_the_title( $post->ID ),
				'link'        => $permalink,
				'post_type'   => $post->post_type,
				'updated'     => get_the_modified_date( 'c', $post->ID ),
				'images'      => 0,
				'images_data' => [],
			];

			if ( Settings::get( 'enable_xml_image_sitemap' ) ) {
				$images = Utils::get_images_from_post( $post->ID );

				if ( is_array( $images ) && ! empty( $images ) ) {
					$post_data['images']      = count( $images );
					$post_data['images_data'] = array_map(
						static function ( $image_url ) use ( $post ) {
							return [
								'link'    => esc_url( $image_url ),
								'updated' => get_the_modified_date( 'c', $post->ID ),
							];
						},
						$images
					);
				}
			}

			$post_data = apply_filters( 'surerank_sitemap_sync_posts_post_data', $post_data, $post );

			$json_data[] = $post_data;
		}

		return $json_data;
	}

	/**
	 * Save JSON data to cache
	 *
	 * @param array<int, array<string, mixed>> $json_data The JSON data.
	 * @param string                           $post_type The post type.
	 * @param int                              $file_index The file index.
	 * @since 1.2.0
	 * @return void
	 */
	private function save_json_cache( array $json_data, string $post_type, int $file_index ) {
		$safe_post_type = sanitize_key( $post_type );
		$cpt_prefix     = self::get_post_type_prefix();
		$filename       = $cpt_prefix . '-' . $safe_post_type . '-chunk-' . absint( $file_index ) . '.json';

		self::log( 'Saving JSON cache for ' . $post_type . ' (file: ' . $filename . ')' );

		$json_string = wp_json_encode( $json_data );
		if ( false === $json_string ) {
			return;
		}

		Cache::store_file( 'sitemap/' . $filename, $json_string );
		Cache::update_sitemap_index( $cpt_prefix . '-' . $safe_post_type, $file_index, count( $json_data ) );
	}

}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Sync_Posts::get_instance();
