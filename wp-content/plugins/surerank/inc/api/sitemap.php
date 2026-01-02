<?php
/**
 * Sitemap API class
 *
 * Handles sitemap cache generation related REST API endpoints for the SureRank plugin.
 *
 * @package SureRank\Inc\API
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Admin\Sync;
use SureRank\Inc\Functions\Cache;
use SureRank\Inc\Functions\Cron;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sitemap
 *
 * Handles sitemap cache generation related REST API endpoints.
 */
class Sitemap extends Api_Base {
	use Get_Instance;

	/**
	 * Route for generating sitemap cache
	 */
	protected const GENERATE_CACHE = '/sitemap/generate-cache';

	/**
	 * Route for manual batch processing when crons are disabled
	 */
	protected const GENERATE_CACHE_MANUAL = '/sitemap/generate-cache-manual';

	/**
	 * Route for getting cachable items
	 */
	protected const GET_CACHABLE_ITEMS = '/sitemap/get-cachable-items';

	/**
	 * Register API routes.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			self::GENERATE_CACHE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_cache' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
		register_rest_route(
			$this->get_api_namespace(),
			self::GENERATE_CACHE_MANUAL,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_cache_manual' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);

		register_rest_route(
			$this->get_api_namespace(),
			'prepare-cache',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'prepare_cache' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
	}

	/**
	 * Prepare cache.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request REST API request object.
	 * @since 1.4.3
	 * @return void
	 */
	public function prepare_cache( $request ) {
		Cache::clear_all();
		$classes    = Sync::get_instance()->generate_classes();
		$chunk_size = apply_filters( 'surerank_sitemap_json_chunk_size', 20 );
		$items      = [];
		$type       = '';
		$categories = [
			'post'     => [],
			'taxonomy' => [],
			'other'    => [],
		];

		foreach ( $classes as $index => $class ) {
			$array = (array) $class;
			$item  = [];

			if ( empty( $array ) ) {
				$item['type'] = 'other';
				$item['slug'] = $index;
				$item['page'] = 1;
				$type         = 'other';
			}

			foreach ( $array as $key => $value ) {
				if ( false !== strpos( $key, 'post_type' ) ) {
					$item['slug'] = $value;
					$item['type'] = 'post';
					$type         = 'post';
				}
				if ( false !== strpos( $key, 'taxonomy' ) ) {
					$item['slug'] = $value;
					$item['type'] = 'taxonomy';
					$type         = 'taxonomy';
				}
				if ( false !== strpos( $key, 'offset' ) ) {
					$page         = $value !== 0 ? ( $value / $chunk_size ) + 1 : 1;
					$item['page'] = $page;
				}
			}

			$items[]               = $item;
			$item['class']         = get_class( $class );
			$categories[ $type ][] = $item;
		}
		update_option( 'surerank_sitemap_classes', $categories );
		Send_Json::success(
			[ 'data' => $items ]
		);
	}

	/**
	 * Generate sitemap cache (cron-based).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request REST API request object.
	 * @since 1.2.0
	 * @return void
	 */
	public function generate_cache( $request ) {
		try {
			if ( Helper::are_crons_available() ) {
				wp_schedule_single_event( time() + 10, Cron::SITEMAP_CRON_EVENT, [ 'yes' ] );

				Send_Json::success(
					[
						'message'     => __( 'Sitemap cache generation has started.', 'surerank' ),
						'description' => __( 'This may take up to 5 minutes, please wait before checking the sitemap.', 'surerank' ),
					]
				);
			} else {
				Send_Json::error(
					[
						'code'    => 'cron_disabled',
						'message' => __( 'CRONs are disabled on this website. Please enable the CRON functionality to use this feature.', 'surerank' ),
					]
				);
			}
		} catch ( \Exception $e ) {
			Send_Json::error(
				[
					'message' => sprintf(
							/* translators: %s: Error message */
						__( 'Failed to start cache generation: %s', 'surerank' ),
						$e->getMessage()
					),
				]
			);
		}
	}

	/**
	 * Generate sitemap cache manually (batch processing for when crons are disabled).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request REST API request object.
	 * @since 1.4.3
	 * @return \WP_Error|void
	 */
	public function generate_cache_manual( $request ) {
		$page       = $request->get_param( 'page' );
		$type       = $request->get_param( 'type' );
		$slug       = $request->get_param( 'slug' );
		$classes    = get_option( 'surerank_sitemap_classes' );
		$chunk_size = apply_filters( 'surerank_sitemap_json_chunk_size', 20 );

		if ( empty( $classes ) || empty( $classes[ $type ] ) ) {
			return new \WP_Error( 'classes_not_found', __( 'Sitemap classes not found.', 'surerank' ) );
		}

		$class = $classes[ $type ];
		$class = array_filter(
			$class,
			static function( $item ) use ( $page, $slug ) {
				return $item['page'] === $page && $item['slug'] === $slug;
			}
		);

		if ( ! is_array( $class ) || empty( $class ) ) {
			return new \WP_Error( 'class_not_found', __( 'Sitemap class not found.', 'surerank' ) );
		}

		$class_info = reset( $class );
		if ( false === $class_info ) {
			return new \WP_Error( 'class_not_found', __( 'Sitemap class not found.', 'surerank' ) );
		}

		if ( ! isset( $class_info['class'] ) ) {
			return new \WP_Error( 'class_info_not_found', __( 'Sitemap class information not found.', 'surerank' ) );
		}

		$class_name = $class_info['class'];
		if ( ! class_exists( $class_name ) ) {
			return new \WP_Error( 'class_not_exists', __( 'Sitemap class does not exist.', 'surerank' ) );
		}

		$class_instance = new $class_name( ( $page - 1 ) * $chunk_size, $slug, $chunk_size );
		if ( ! method_exists( $class_instance, 'import' ) ) {
			return new \WP_Error( 'import_method_not_found', __( 'Import method not found in sitemap class.', 'surerank' ) );
		}

		$result = $class_instance->import();
		Send_Json::success( $result );
	}
}
