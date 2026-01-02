<?php
/**
 * Schemas class
 *
 * Handles schemas related REST API endpoints for the SureRank plugin.
 *
 * @package SureRank\Inc\API
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\API\Api_Base;
use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SchemasApi
 *
 * Handles schemas related REST API endpoints.
 */
class SchemasApi extends Api_Base {
	use Get_Instance;

	/**
	 * Route Get Term Seo Data
	 */
	protected const GET_POST_BY_QUERY = '/admin/posts';

	/**
	 * Route get variables
	 */
	protected const GET_VARIABLES = '/schemas/variables';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			self::GET_POST_BY_QUERY,
			[
				'methods'             => WP_REST_Server::CREATABLE, // GET Term Seo Data.
				'callback'            => [ $this, 'get_post_by_query' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'q' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$this->get_api_namespace(),
			self::GET_VARIABLES,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_variables' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
	}

	/**
	 * Get Posts By Query
	 *
	 * Handles the REST API request for posts by query.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request REST API Request object.
	 * @since 1.0.0
	 * @return void
	 */
	public function get_post_by_query( $request ) {
		$search_string = sanitize_text_field( $request->get_param( 'q' ) );
		$page          = intval( $request->get_param( 'page' ) ) ? intval( $request->get_param( 'page' ) ) : 1;
		$result        = [];

		if ( empty( $search_string ) ) {
			Send_Json::success( [ 'results' => $result ] );
		}

		try {
			$post_types = array_merge(
				[ 'post', 'page' ],
				array_keys(
					get_post_types(
						[
							'public'   => true,
							'_builtin' => false,
						],
						'names'
					)
				)
			);

			foreach ( $post_types as $post_type ) {
				add_filter( 'posts_search', [ $this, 'search_only_titles' ], 10, 2 );

				$query = new \WP_Query(
					[
						's'              => $search_string,
						'post_type'      => $post_type,
						'posts_per_page' => 10,
						'paged'          => $page,
						'fields'         => 'ids',
					]
				);

				$data = [];
				if ( $query->have_posts() ) {
					foreach ( $query->posts as $post_id ) {
						$post_id = intval( is_object( $post_id ) ? $post_id->ID : $post_id );
						$data[]  = [
							'id'   => 'post-' . $post_id,
							'text' => get_the_title( $post_id ),
						];
					}
				}

				if ( ! empty( $data ) ) {
					$result[] = [
						'text'     => ucfirst( $post_type ),
						'children' => $data,
					];
				}

				remove_filter( 'posts_search', [ $this, 'search_only_titles' ] );
			}

			wp_reset_postdata();

			$output   = 'objects'; // names or objects, note names is the default.
			$operator = 'and'; // also supports 'or'.
			$args     = [
				'public' => true,
			];

			$taxonomies = get_taxonomies( $args, $output, $operator );

			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_terms(
					[
						'taxonomy'   => $taxonomy->name,
						'orderby'    => 'count',
						'hide_empty' => 0,
						'name__like' => $search_string,
					]
				);

				$data = [];

				$label = ucwords( $taxonomy->label );

				if ( ! empty( $terms ) && ! is_wp_error( $terms ) && is_array( $terms ) ) {

					foreach ( $terms as $term ) {

						$term_taxonomy_name = ucfirst( str_replace( '_', ' ', $taxonomy->name ) );

						// for tax-{id}, and tax-{id}-single-{taxonomy} type rules.
						$data[] = [
							'id'   => 'tax-' . $term->term_id,
							'text' => ucwords( $term->name . ' (' . $term_taxonomy_name . ')' ),
						];

						$data[] = [
							'id'   => 'tax-' . $term->term_id . '-single-' . $taxonomy->name,
							'text' => 'All singulars from ' . $term->name,
						];

					}
				}

				if ( is_array( $data ) && ! empty( $data ) ) {
					$result[] = [
						'text'     => $label,
						'children' => $data,
					];
				}
			}

			Send_Json::success( [ 'results' => $result ] );
		} catch ( \Exception $e ) {
			Send_Json::success( [ 'results' => [] ] );
		}
	}

	/**
	 * Search Only Titles
	 *
	 * Filters the WP_Query search to look only in post titles.
	 *
	 * @param string    $search   The search SQL for WHERE clause.
	 * @param \WP_Query $wp_query The current WP_Query object.
	 * @since 1.0.0
	 * @return string
	 */
	public function search_only_titles( $search, $wp_query ) {
		global $wpdb;

		if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
			$q      = $wp_query->query_vars;
			$n      = ! empty( $q['exact'] ) ? '' : '%';
			$search = [];

			foreach ( (array) $q['search_terms'] as $term ) {
				$search[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $n . $wpdb->esc_like( $term ) . $n );
			}

			if ( ! is_user_logged_in() ) {
				$search[] = "{$wpdb->posts}.post_password = ''";
			}

			$search = ' AND ' . implode( ' AND ', $search );
		}

		return $search;
	}
}
