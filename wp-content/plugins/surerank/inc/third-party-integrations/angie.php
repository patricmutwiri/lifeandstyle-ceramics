<?php
/**
 * Third Party Plugins class - Elementor - Angie
 *
 * Handles Elementor - Angie Plugin related compatibility.
 *
 * @package SureRank\Inc\ThirdPartyIntegrations
 */

namespace SureRank\Inc\ThirdPartyIntegrations;

use SureRank\Inc\API\Admin;
use SureRank\Inc\API\Api_Base;
use SureRank\Inc\API\Post;
use SureRank\Inc\API\Term;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Api
 *
 * Handles analysis related REST API endpoints.
 */
class Angie extends Api_Base {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.4.3
	 */
	public function __construct() {
		if ( ! defined( 'ANGIE_VERSION' ) ) {
			return;
		}
		// Elementor - Angie Integration.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Enqueue JavaScript MCP server with SDK dependency
	 *
	 * @return void
	 * @since 1.4.3
	 */
	public function enqueue_scripts(): void {
		$asset_path = SURERANK_DIR . 'build/angie/index.asset.php';
		$asset_info = file_exists( $asset_path ) ? include $asset_path : [
			'dependencies' => [],
			'version'      => SURERANK_VERSION,
		];

		wp_enqueue_script_module(
			'surerank-angie',
			SURERANK_URL . 'build/angie/index.js',
			$asset_info['dependencies'],
			$asset_info['version']
		);
	}

	/**
	 * Register API routes.
	 *
	 * @since 1.4.3
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'surerank/v1',
			'/angie/toggle-sitemap',
			[
				'methods'             => WP_REST_Server::CREATABLE, // GET method.
				'callback'            => [ $this, 'toggle_sitemap' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'enable' => [
						'required'          => true,
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
				],
			]
		);

		register_rest_route(
			'surerank/v1',
			'/angie/bulk-robots-settings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'bulk_robots_settings' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'type'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'name'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'action' => [
						'required' => true,
						'type'     => 'string',
						'enum'     => $this->get_allowed_robots_actions(),
					],
					'ids'    => [
						'required'          => false,
						'type'              => 'array',
						'sanitize_callback' => static function( $ids ) {
							return array_map( 'absint', (array) $ids );
						},
					],
				],
			]
		);

		register_rest_route(
			'surerank/v1',
			'/angie/indexable-status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'indexable_status' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'id'   => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'type' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			'surerank/v1',
			'/angie/title-and-meta-description',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_title_and_meta_description' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'id'               => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'type'             => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'title'            => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'meta_description' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			'surerank/v1',
			'/angie/toggle-settings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'toggle_settings' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'settings' => [
						'required' => true,
						'type'     => 'object',
					],
				],
			]
		);

		register_rest_route(
			'surerank/v1',
			'/angie/get-available-types',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_available_types' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
	}

	/**
	 * Analyze page SEO
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string,string|true>
	 *
	 * @since 1.4.3
	 */
	public function toggle_sitemap( $request ) {
		$enable                     = $request->get_param( 'enable' );
		$data                       = Settings::get();
		$data['enable_xml_sitemap'] = $enable;
		Update::option( SURERANK_SETTINGS, $data );

		return [
			'success' => true,
			'message' => $enable
				? __( 'XML Sitemap turned on successfully.', 'surerank' )
				: __( 'XML Sitemap turned off successfully.', 'surerank' ),
		];
	}

	/**
	 * Apply bulk robots settings
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>|WP_Error
	 *
	 * @since 1.4.3
	 */
	public function bulk_robots_settings( $request ) {
		$type   = $request->get_param( 'type' ); // cpt/taxonomy.
		$action = $request->get_param( 'action' );
		$name   = $request->get_param( 'name' );
		$ids    = $request->get_param( 'ids' );

		if ( empty( $action ) || empty( $name ) ) {
			return new WP_Error(
				'surerank_invalid_parameter',
				__( 'Missing required parameters: type, action and name are required.', 'surerank' ),
				[ 'status' => 400 ]
			);
		}

		$is_taxonomy  = $type === 'taxonomy';
		$is_post_type = $type === 'cpt';

		if ( ! $is_taxonomy && ! $is_post_type ) {
			return new WP_Error(
				'surerank_invalid_parameter',
				__( 'Invalid type. Must be a valid post type or taxonomy.', 'surerank' ),
				[ 'status' => 400 ]
			);
		}

		if ( $is_taxonomy ) {
			$object_ids  = $this->get_terms_by_taxonomy( $name, $ids );
			$object_type = 'terms';
		} else {
			$objects_ids = $this->get_posts_by_type( $name, $ids );
			$object_type = 'posts';
		}

		if ( empty( $objects_ids ) ) {
			return [
				'success'       => true,
				'message'       => sprintf(
					/* translators: %s: object type */
					__( 'No %s found for the specified type.', 'surerank' ),
					$object_type
				),
				'updated_count' => 0,
			];
		}

		$updated_count = $this->apply_bulk_settings_to_objects( $objects_ids, $action, $is_taxonomy );

		$action_labels = $this->get_action_labels();
		$action_label  = $action_labels[ $action ] ?? $action;

		return [
			'success'       => true,
			'message'       => sprintf(
				/* translators: %1$s: action performed, %2$d: number of objects_ids updated, %3$s: type */
				__( 'Applied %1$s to %2$d %3$s.', 'surerank' ),
				$action_label,
				$updated_count,
				$name
			),
			'updated_count' => $updated_count,
			'type'          => $type,
			'name'          => $name,
			'action'        => $action,
			'object_type'   => $object_type,
		];
	}

	/**
	 * Get indexable status
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>
	 */
	public function indexable_status( $request ) {
		$id     = $request->get_param( 'id' );
		$type   = $request->get_param( 'type' ) ?? 'post';
		$option = 'surerank_settings_post_no_index';

		$meta      = $type === 'post' ? get_post_meta( $id, $option, true ) : get_term_meta( $id, $option, true );
		$indexable = $meta === 'yes' ? 'not_indexable' : 'indexable';

		$reason = __( 'Post is indexable', 'surerank' );
		if ( $meta === 'yes' ) {
			$reason = __( 'This post is set to noindex by SureRank settings from SEO popup.', 'surerank' );
		}

		if ( ! $meta ) {
			$settings = Settings::get();
			$no_index = $settings['no_index'] ?? [];
			if ( in_array( $type, $no_index, true ) ) {
				$indexable = 'not_indexable';
				$reason    = __( 'This post is set to noindex by SureRank Global Robots Settings.', 'surerank' );
			}
		}

		return [
			'indexable' => $indexable,
			'reason'    => $reason,
		];
	}

	/**
	 * Update title and meta description
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>
	 */
	public function update_title_and_meta_description( $request ) {
		$id               = $request->get_param( 'id' );
		$type             = $request->get_param( 'type' );
		$title            = $request->get_param( 'title' );
		$meta_description = $request->get_param( 'meta_description' );

		$data = [];

		if ( ! empty( $title ) ) {
			$data['page_title'] = $title;
		}

		if ( ! empty( $meta_description ) ) {
			$data['page_description'] = $meta_description;
		}

		if ( empty( $data ) ) {
			return [
				'success' => false,
				'message' => __( 'Title or meta description is empty.', 'surerank' ),
			];
		}

		if ( $type === 'post' ) {
			Post::update_post_meta_common( $id, $data );
		} else {
			Term::update_term_meta_common( $id, $data );
		}

		return [
			'success' => true,
			'message' => __( 'Title and meta description updated successfully.', 'surerank' ),
		];
	}

	/**
	 * Toggle multiple settings
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>
	 * @since 1.4.3
	 */
	public function toggle_settings( $request ): array {
		$settings = $request->get_param( 'settings' );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return [
				'success' => false,
				'message' => __( 'No valid settings provided.', 'surerank' ),
			];
		}

		Admin::get_instance()->update_global_options( $settings );

		return [
			'success' => true,
			'message' => __( 'Settings updated successfully.', 'surerank' ),
		];
	}

	/**
	 * Get available post types and taxonomies
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>
	 * @since 1.4.3
	 */
	public function get_available_types( $request ) {
		$post_types = Helper::get_formatted_post_types();
		$taxonomies = Helper::get_formatted_taxonomies();

		return [
			'post_types'     => $post_types,
			'taxonomies'     => $taxonomies,
			'post_type_keys' => array_keys( $post_types ),
			'taxonomy_keys'  => array_keys( $taxonomies ),
		];
	}

	/**
	 * Get allowed robots actions
	 *
	 * @return array<string> Array of allowed robots action values
	 * @since 1.4.3
	 */
	private function get_allowed_robots_actions(): array {
		return [ 'noindex', 'index', 'nofollow', 'follow', 'noarchive', 'archive' ];
	}

	/**
	 * Get posts by post type
	 *
	 * @param string          $post_type Post type.
	 * @param array<int>|null $ids Optional array of specific post IDs.
	 * @return array<int> Array of post IDs.
	 */
	private function get_posts_by_type( string $post_type, ?array $ids = null ): array {
		$args = [
			'post_type'      => $post_type,
			'post_status'    => [ 'publish', 'draft', 'private' ],
			'posts_per_page' => ! empty( $ids ) ? -1 : 50, // Limit to 50 when no specific IDs provided.
			'fields'         => 'ids',
			'no_found_rows'  => true,
		];

		if ( ! empty( $ids ) ) {
			$args['post__in'] = $ids;
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() || empty( $query->posts ) ) {
			return [];
		}

		return array_map(
			static function( $post ): int {
				return is_int( $post ) ? $post : (int) $post->ID;
			},
			$query->posts
		);
	}

	/**
	 * Get terms by taxonomy
	 *
	 * @param string          $taxonomy Taxonomy name.
	 * @param array<int>|null $ids Optional array of specific term IDs.
	 * @return array<int> Array of term IDs.
	 */
	private function get_terms_by_taxonomy( string $taxonomy, ?array $ids = null ): array {
		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		];

		if ( ! empty( $ids ) ) {
			$args['include'] = $ids;
		}

		$terms = get_terms( $args );
		return is_wp_error( $terms ) ? [] : $terms;
	}

	/**
	 * Apply bulk settings to objects (posts or terms)
	 *
	 * @param array<int> $object_ids Array of object IDs.
	 * @param string     $action Action to perform.
	 * @param bool       $is_taxonomy Whether these are terms (true) or posts (false).
	 * @return int Number of objects updated.
	 */
	private function apply_bulk_settings_to_objects( array $object_ids, string $action, bool $is_taxonomy ): int {
		$updated_count = 0;
		$meta_key      = $this->get_meta_key_for_action( $action );
		$meta_value    = $this->get_meta_value_for_action( $action, null );

		foreach ( $object_ids as $object_id ) {
			if ( $is_taxonomy ) {
				if ( $this->update_term_meta( $object_id, $meta_key, $meta_value ) ) {
					$updated_count++;
				}
			} else {
				if ( $this->update_post_meta( $object_id, $meta_key, $meta_value ) ) {
					$updated_count++;
				}
			}
		}

		return $updated_count;
	}

	/**
	 * Get meta key for action
	 *
	 * @param string $action Action.
	 * @return string Meta key.
	 */
	private function get_meta_key_for_action( string $action ): string {
		$action_map = [
			'noindex'   => 'post_no_index',
			'index'     => 'post_no_index',
			'nofollow'  => 'post_no_follow',
			'follow'    => 'post_no_follow',
			'noarchive' => 'post_no_archive',
			'archive'   => 'post_no_archive',
		];

		return $action_map[ $action ] ?? '';
	}

	/**
	 * Get meta value for action
	 *
	 * @param string $action Action.
	 * @param mixed  $value Value.
	 * @return string Meta value.
	 */
	private function get_meta_value_for_action( string $action, $value ): string {
		if ( $value !== null && $value !== '' ) {
			return $value ? 'yes' : 'no';
		}

		$action_values = [
			'noindex'   => 'yes',
			'index'     => 'no',
			'nofollow'  => 'yes',
			'follow'    => 'no',
			'noarchive' => 'yes',
			'archive'   => 'no',
		];

		return $action_values[ $action ] ?? 'no';
	}

	/**
	 * Update post meta
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value.
	 * @return bool Success status.
	 */
	private function update_post_meta( int $post_id, string $meta_key, string $meta_value ): bool {
		$full_meta_key = 'surerank_settings_' . $meta_key;
		$result        = Update::post_meta( $post_id, $full_meta_key, $meta_value );
		return is_bool( $result ) ? $result : (bool) $result;
	}

	/**
	 * Update term meta
	 *
	 * @param int    $term_id Term ID.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value.
	 * @return bool Success status.
	 */
	private function update_term_meta( int $term_id, string $meta_key, string $meta_value ): bool {
		$full_meta_key = 'surerank_settings_' . $meta_key;
		$result        = Update::term_meta( $term_id, $full_meta_key, $meta_value );
		return is_bool( $result ) ? $result : ! is_wp_error( $result );
	}

	/**
	 * Get action labels
	 *
	 * @return array<string, string> Action labels.
	 */
	private function get_action_labels(): array {
		return [
			'noindex'   => __( 'noindex', 'surerank' ),
			'index'     => __( 'index', 'surerank' ),
			'nofollow'  => __( 'nofollow', 'surerank' ),
			'follow'    => __( 'follow', 'surerank' ),
			'noarchive' => __( 'noarchive', 'surerank' ),
			'archive'   => __( 'archive', 'surerank' ),
		];
	}
}
