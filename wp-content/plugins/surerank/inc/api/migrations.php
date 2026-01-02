<?php
/**
 * Migration API Class
 *
 * Handles migration-related REST API endpoints for the plugin.
 *
 * @package SureRank\Inc\API
 * @since   1.1.0
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Importers\Importer;
use SureRank\Inc\Importers\ImporterUtils;
use SureRank\Inc\Importers\Rankmath\RankMath;
use SureRank\Inc\Importers\Seopress\Seopress;
use SureRank\Inc\Importers\Yoast\Yoast;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migrations
 *
 * Handles the REST API endpoints for migrations from other SEO plugins
 * and for retrieving all post IDs.
 */
class Migrations extends Api_Base {

	use Get_Instance;

	/**
	 * Route Migrated Data
	 */
	protected const MIGRATED_DATA = '/migration/migrated-data';

	/**
	 * API endpoint for migrating posts.
	 */
	private const ENDPOINT_POSTS = '/migration/posts';

	/**
	 * API endpoint for migrating terms.
	 */
	private const ENDPOINT_TERMS = '/migration/terms';

	/**
	 * API endpoint for migrating global settings.
	 */
	private const ENDPOINT_GLOBAL = '/migration/global-settings';

	/**
	 * API endpoint for deactivating a plugin.
	 *
	 * @since 1.1.0
	 */
	private const ENDPOINT_DEACTIVATE = '/plugins/deactivate';

	/**
	 * Batch size for processing large datasets.
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Map of slug ⇒ importer class.
	 *
	 * @var array<string, class-string>
	 */
	private array $importers = [
		'rankmath' => RankMath::class,
		'seopress' => Seopress::class,
		'yoast'    => Yoast::class,
	];

	/**
	 * Register the /migration/posts, /migration/terms, /migration/global-settings routes.
	 */
	public function register_routes(): void {

		if ( ! Settings::get( 'enable_migration' ) ) {
			return;
		}

		$namespace = $this->get_api_namespace();

		// -------- Migrate posts -------- .
		register_rest_route(
			$namespace,
			self::ENDPOINT_POSTS,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'migrate_posts' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'plugin_slug' => [
							'type'        => 'string',
							'required'    => true,
							/* translators: %s: list of plugin slugs */
							'description' => sprintf( __( 'Plugin slug to migrate from (e.g. %s).', 'surerank' ), implode( ', ', array_keys( $this->importers ) ) ),
							'enum'        => array_keys( $this->importers ),
						],
						'post_ids'    => [
							'type'              => [ 'array', 'integer' ],
							'required'          => true,
							'description'       => __( 'Post IDs to migrate.', 'surerank' ),
							'validate_callback' => fn( $param) => $this->validate_ids( $param ),
						],
						'cleanup'     => [
							'type'        => 'boolean',
							'required'    => false,
							'default'     => false,
							'description' => __( 'Whether to clean up source data after import.', 'surerank' ),
						],
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_all_posts' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'page'        => [
							'type'        => 'integer',
							'required'    => false,
							'default'     => 1,
							'description' => __( 'Page number for pagination.', 'surerank' ),
						],
						'plugin_slug' => [
							'type'        => 'string',
							'required'    => true,
							/* translators: %s: list of plugin slugs */
							'description' => sprintf( __( 'Plugin slug to filter posts by (e.g. %s).', 'surerank' ), implode( ', ', array_keys( $this->importers ) ) ),
							'enum'        => array_keys( $this->importers ),
							'default'     => 'rankmath',
						],
					],
				],
			]
		);

		// -------- Migrate terms --------.
		register_rest_route(
			$namespace,
			self::ENDPOINT_TERMS,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'migrate_terms' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'plugin_slug' => [
							'type'        => 'string',
							'required'    => true,
							/* translators: %s: list of plugin slugs */
							'description' => sprintf( __( 'Plugin slug to migrate from (e.g. %s).', 'surerank' ), implode( ', ', array_keys( $this->importers ) ) ),
							'enum'        => array_keys( $this->importers ),
						],
						'term_ids'    => [
							'type'              => [ 'array', 'integer' ],
							'required'          => true,
							'description'       => __( 'Term IDs to migrate.', 'surerank' ),
							'validate_callback' => fn( $param) => $this->validate_ids( $param ),
						],
						'cleanup'     => [
							'type'        => 'boolean',
							'required'    => false,
							'default'     => false,
							'description' => __( 'Whether to clean up source data after import.', 'surerank' ),
						],
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_all_terms' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'page'        => [
							'type'        => 'integer',
							'required'    => false,
							'default'     => 1,
							'description' => __( 'Page number for pagination.', 'surerank' ),
						],
						'plugin_slug' => [
							'type'        => 'string',
							'required'    => true,
							'default'     => 'rankmath',
							/* translators: %s: list of plugin slugs */
							'description' => sprintf( __( 'Plugin slug to filter terms by (e.g. %s).', 'surerank' ), implode( ', ', array_keys( $this->importers ) ) ),
							'enum'        => array_keys( $this->importers ),
						],
					],
				],
			]
		);

		// -------- Migrate global settings --------.
		register_rest_route(
			$namespace,
			self::ENDPOINT_GLOBAL,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'migrate_global_settings' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'plugin_slug' => [
						'type'        => 'string',
						'required'    => true,
						/* translators: %s: list of plugin slugs */
						'description' => sprintf( __( 'Plugin slug to migrate global settings from (e.g. %s).', 'surerank' ), implode( ', ', array_keys( $this->importers ) ) ),
						'enum'        => array_keys( $this->importers ),
					],
					'cleanup'     => [
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
						'description' => __( 'Whether to clean up source global data after import.', 'surerank' ),
					],
				],
			]
		);

		// -------- Deactivate plugin --------.
		register_rest_route(
			$namespace,
			self::ENDPOINT_DEACTIVATE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'deactivate_plugin' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'plugin_slug' => [
						'type'        => 'string',
						'required'    => true,
						'description' => __( 'Plugin slug to deactivate.', 'surerank' ),
						'enum'        => array_keys( $this->importers ),
					],
				],
			]
		);

		// -------- Mark migration completed --------.
		register_rest_route(
			$namespace,
			'/migration/completed',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'mark_migration_completed_endpoint' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'plugin_slug' => [
						'type'        => 'string',
						'required'    => true,
						'description' => __( 'Plugin slug that was migrated from.', 'surerank' ),
						'enum'        => array_keys( $this->importers ),
					],
				],
			]
		);
		// -------- Get migrated data --------.
		register_rest_route(
			$this->get_api_namespace(),
			self::MIGRATED_DATA,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_migrated_data' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			]
		);
	}

	/**
	 * Get Migrated Data
	 *
	 * @since 1.2.0
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return array<string, mixed>
	 */
	public function get_migrated_data( $request ) {
		$surerank_onboarding = Get::option( 'surerank_settings_onboarding', [] );
		$surerank_settings   = Get::option( SURERANK_SETTINGS, [] );

		return [
			'social_profiles' => $surerank_settings['social_profiles'] ?? [],
			'website_details' => $surerank_onboarding,
		];
	}

	/**
	 * Handle the migration request for posts.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 * @return void
	 */
	public function migrate_posts( $request ) {
		$ids_raw = $request->get_param( 'post_ids' );
		$cleanup = (bool) $request->get_param( 'cleanup' );
		$ids     = is_array( $ids_raw ) ? $ids_raw : [ $ids_raw ];

		$importer = $this->validate_and_get_importer( $request );

		if ( ! $this->validate_importer_methods( $importer, 'post' ) ) {
			Send_Json::error(
				[ 'message' => __( 'Invalid importer methods.', 'surerank' ) ]
			);
		}

		$results = $this->process_migration( $ids, $importer, 'post' );
		$results = array_merge( $results, $this->handle_cleanup( $importer, $cleanup, $results['success'] ) );
		$results = $this->format_response( $results, $importer, $ids, 'posts' );

		Send_Json::success( $results );
	}

	/**
	 * Handle the migration request for terms.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 * @return void
	 */
	public function migrate_terms( $request ) {
		$ids_raw = $request->get_param( 'term_ids' );
		$cleanup = (bool) $request->get_param( 'cleanup' );
		$ids     = is_array( $ids_raw ) ? $ids_raw : [ $ids_raw ];

		$importer = $this->validate_and_get_importer( $request );

		if ( ! $this->validate_importer_methods( $importer, 'term' ) ) {
			Send_Json::error(
				[ 'message' => __( 'Invalid importer methods.', 'surerank' ) ]
			);
		}

		$results = $this->process_migration( $ids, $importer, 'term' );
		$results = array_merge( $results, $this->handle_cleanup( $importer, $cleanup, $results['success'] ) );
		$results = $this->format_response( $results, $importer, $ids, 'terms' );

		Send_Json::success( $results );
	}

	/**
	 * Handle the migration request for global settings.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 */
	public function migrate_global_settings( $request ): void {
		$plugin_slug = $request->get_param( 'plugin_slug' );
		$cleanup     = (bool) $request->get_param( 'cleanup' );

		$importer_class = $this->importers[ $plugin_slug ];

		/**
		 * The importer class must implement Importer.
		 *
		 * @var Importer $importer
		 * */
		$importer = new $importer_class();

		// Validate that the importer implements the required interface.
		if ( ! $importer instanceof Importer ) {
			Send_Json::error(
				[
					'message' => sprintf(
						/* translators: %s: importer class name */
						__( 'Invalid importer class: %s does not implement Importer.', 'surerank' ),
						$importer_class
					),
				]
			);
		}

		$results = $importer->import_global_settings();

		// Trigger action after all free migration is done.
		do_action( 'surerank_migration_after', $plugin_slug );

		// Ensure results is an array.
		if ( ! is_array( $results ) ) {
			$results = [
				'success' => false,
				'message' => __( 'Invalid response from importer.', 'surerank' ),
			];
		}

		// Only run cleanup if migration was successful.
		if ( $cleanup && $results['success'] && method_exists( $importer, 'cleanup' ) ) {
			$cleanup_resp = $importer->cleanup();

			// Ensure cleanup response is valid.
			if ( is_array( $cleanup_resp ) ) {
				$results['cleanup']         = $cleanup_resp['success'];
				$results['cleanup_message'] = $cleanup_resp['message'];
			}
		}

		$plugin_name        = method_exists( $importer, 'get_plugin_name' ) ? $importer->get_plugin_name() : 'Unknown';
		$results['message'] = sprintf(
			/* translators: 1: import status, 2: plugin name */
			__( 'Global settings %1$s from %2$s.', 'surerank' ),
			$results['success'] ? __( 'imported successfully', 'surerank' ) : __( 'failed to import', 'surerank' ),
			$plugin_name
		);

		if ( $results['success'] ) {
			Send_Json::success( $results );
		} else {
			Send_Json::error( $results );
		}
	}

	/**
	 * Retrieve all post IDs grouped by post type, excluding those already migrated.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 */
	public function get_all_posts( $request ): void {
		$page        = (int) $request->get_param( 'page' );
		$plugin_slug = $request->get_param( 'plugin_slug' );
		$batch_size  = self::BATCH_SIZE;

		$importer_class = $this->importers[ $plugin_slug ];

		/**
		 * The importer class must implement Importer.
		 *
		 * @var Importer $importer
		 * */
		$importer   = new $importer_class();
		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'names'
		);

		unset( $post_types['attachment'] );
		$post_types = array_values( $post_types );

		// Calculate offset for pagination.
		$offset = ( $page - 1 ) * $batch_size;

		$post_id_and_total_items = $importer->get_count_and_posts( $post_types, $batch_size, $offset );
		$total_items             = $post_id_and_total_items['total_items'];
		$post_ids                = $post_id_and_total_items['post_ids'];
		$grouped                 = $this->group_items( $post_ids );

		Send_Json::success(
			[
				'data'       => $grouped,
				'pagination' => [
					'current_page' => $page,
					'total_pages'  => (int) ceil( $total_items / $batch_size ),
					'total_items'  => $total_items,
					'per_page'     => self::BATCH_SIZE,
				],
			]
		);
	}

	/**
	 * Retrieve all term IDs grouped by taxonomy, excluding those already migrated.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 */
	public function get_all_terms( $request ): void {
		$page        = (int) $request->get_param( 'page' );
		$plugin_slug = $request->get_param( 'plugin_slug' );
		$batch_size  = self::BATCH_SIZE;

		// Initialize response data.
		$grouped            = [];
		$total_terms        = 0;
		$term_ids           = [];
		$taxonomies_objects = $this->get_public_taxonomies();
		if ( empty( $taxonomies_objects ) ) {
			Send_Json::error(
				[
					'message' => __( 'No public taxonomies found.', 'surerank' ),
				]
			);
			return;
		}
			$importer_class = $this->importers[ $plugin_slug ];
			/**
			 * The importer class must implement Importer.
			 *
			 * @var Importer $importer
			 */
			$importer = new $importer_class();

			$taxonomies = array_map(
				static function ( $taxonomy ) {
					return $taxonomy->name ?? '';
				},
				$taxonomies_objects
			);
			$taxonomies = array_filter( $taxonomies );

			// Calculate offset for pagination.
			$offset = ( $page - 1 ) * $batch_size;

			// Fetch term IDs with batching.
			$term_ids_and_total_items = $importer->get_count_and_terms(
				$taxonomies,
				$taxonomies_objects,
				$batch_size,
				$offset
			);
			$total_terms              = $term_ids_and_total_items['total_items'] ?? 0;
			$term_ids                 = $term_ids_and_total_items['term_ids'] ?? [];
			$grouped                  = $this->group_items( $term_ids, true, $taxonomies_objects );

		Send_Json::success(
			[
				'data'       => $grouped,
				'pagination' => [
					'current_page' => $page,
					'total_pages'  => 'yoast' === $plugin_slug ? 1 : ( $total_terms > 0 ? (int) ceil( $total_terms / $batch_size ) : 1 ),
					'total_items'  => $total_terms,
					'per_page'     => self::BATCH_SIZE,
				],
			]
		);
	}

	/**
	 * Get a list of SEO plugins available for migration.
	 *
	 * Returns a list of installed plugins that are available for migration.
	 * The results are cached for the duration of the request.
	 *
	 * @return array<string, array{name: string, active: bool}> Array of plugin slugs => plugin names that are available for migration.
	 * @since 1.1.0
	 */
	public function get_available_plugins(): array {
		// Early return if no importers are configured.
		if ( empty( $this->importers ) ) {
			return [];
		}

		// Use static cache to avoid repeated processing.
		static $available_plugins = null;
		if ( null !== $available_plugins ) {
			return $available_plugins;
		}

		// Map of supported plugin slugs to their details.
		$supported_plugins = $this->get_supported_plugins();

		$installed_plugins = get_plugins();
		$available_plugins = [];

		// Single pass through the data to build the final array.
		foreach ( $supported_plugins as $key => $plugin ) {
			if ( isset( $this->importers[ $key ] ) && isset( $installed_plugins[ $plugin['slug'] ] ) ) {
				$available_plugins[ $key ] = [
					'name'   => $plugin['name'],
					'active' => $this->is_plugin_active( $plugin['slug'] ),
				];
			}
		}

		return $available_plugins;
	}

	/**
	 * Check if the plugin is active or not.
	 *
	 * @param string $plugin Slug of the plugin to check.
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function is_plugin_active( $plugin ) {
		foreach ( $this->importers as $slug => $importer_class ) {
			if ( ! class_exists( $importer_class ) ) {
				continue;
			}

			$importer = new $importer_class();

			if ( ! method_exists( $importer, 'get_plugin_file' ) || ! method_exists( $importer, 'is_plugin_active' ) ) {
				continue;
			}

			if ( $importer->get_plugin_file() === $plugin ) {
				return (bool) $importer->is_plugin_active();
			}
		}

		return false;
	}

	/**
	 * Deactivate a plugin after migration.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 *
	 * @return void
	 */
	public function deactivate_plugin( $request ): void {
		$plugin_slug = $request->get_param( 'plugin_slug' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			Send_Json::error( [ 'message' => __( 'You do not have permission to deactivate plugins.', 'surerank' ) ] );
		}

		$plugin_path = $this->get_plugin_path_from_slug( (string) $plugin_slug );

		if ( ! $plugin_path ) {
			Send_Json::error( [ 'message' => __( 'Plugin not found.', 'surerank' ) ] );
		}

		if ( ! $this->is_plugin_active( (string) $plugin_path ) ) {
			Send_Json::success( [ 'message' => __( 'Plugin is already inactive.', 'surerank' ) ] );
			return;
		}

		$to_be_deactivated_plugins = $this->get_associated_plugins( (string) $plugin_path );

		deactivate_plugins( (array) $to_be_deactivated_plugins );

		Send_Json::success( [ 'message' => __( 'Plugin deactivated successfully.', 'surerank' ) ] );
	}

	/**
	 * Mark migration as completed endpoint handler.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 * @return void
	 * @since 1.1.0
	 */
	public function mark_migration_completed_endpoint( $request ): void {
		$plugin_slug = $request->get_param( 'plugin_slug' );

		if ( empty( $plugin_slug ) || ! is_string( $plugin_slug ) ) {
			Send_Json::error( [ 'message' => __( 'Invalid plugin slug.', 'surerank' ) ] );
		}

		// Mark the migration as completed.
		self::mark_migration_completed( $plugin_slug );

		Send_Json::success(
			[
				'message'             => sprintf(
					// translators: %s: plugin slug.
					__( 'Migration from %s marked as completed successfully.', 'surerank' ),
					$plugin_slug
				),
				'migration_completed' => true,
			]
		);
	}

	/**
	 * Mark migration as completed globally.
	 *
	 * @param string $plugin_slug The plugin slug that was migrated from.
	 * @return void
	 * @since 1.1.0
	 */
	public static function mark_migration_completed( string $plugin_slug ): void {
		$completed_migrations = get_option( 'surerank_completed_migrations', [] );

		if ( ! is_array( $completed_migrations ) ) {
			$completed_migrations = [];
		}

		$completed_migrations[ $plugin_slug ] = [
			'timestamp' => time(),
		];

		update_option( 'surerank_completed_migrations', $completed_migrations );

		// Set global flag that any migration has been completed.
		update_option( 'surerank_migration_ever_completed', true );
	}

	/**
	 * Check if migration has ever been completed.
	 *
	 * @return bool True if any migration has ever been completed.
	 * @since 1.1.0
	 */
	public static function has_migration_ever_completed(): bool {
		return (bool) get_option( 'surerank_migration_ever_completed', false );
	}

	/**
	 * Get completed migrations information.
	 *
	 * @return array<string, array<string, mixed>> Array of completed migrations with details.
	 * @since 1.1.0
	 */
	public static function get_completed_migrations(): array {
		$completed_migrations = get_option( 'surerank_completed_migrations', [] );

		if ( ! is_array( $completed_migrations ) ) {
			return [];
		}

		return $completed_migrations;
	}

	/**
	 * Check if migration from specific plugin has been completed.
	 *
	 * @param string $plugin_slug The plugin slug to check.
	 * @return bool True if migration from this plugin was completed.
	 * @since 1.1.0
	 */
	public static function is_plugin_migration_completed( string $plugin_slug ): bool {
		$completed_migrations = self::get_completed_migrations();
		return isset( $completed_migrations[ $plugin_slug ] );
	}

	/**
	 * Check if any cache plugin is currently active on the website.
	 *
	 * This method efficiently detects the most popular WordPress cache plugins
	 * by checking for defined constants, function existence, and class existence
	 * rather than using is_plugin_active() which requires loading plugin data.
	 *
	 * @since 1.2.0
	 * @return array{
	 *     'has_cache_plugin': bool,
	 *     'active_plugins': array<string, array{
	 *         'name': string,
	 *         'slug': string,
	 *         'detection_method': string
	 *     }>
	 * } Array containing status and details of active cache plugins.
	 */
	public static function has_active_cache_plugin(): array {
		// Use static cache to avoid repeated processing during the same request.
		static $cache_result = null;
		if ( null !== $cache_result ) {
			return $cache_result;
		}

		$active_cache_plugins = [];

		// Define cache plugins with their detection methods.
		$cache_plugins = [
			// Premium Cache Plugins.
			'wp-rocket'                => [
				'name'             => __( 'WP Rocket', 'surerank' ),
				'slug'             => 'wp-rocket/wp-rocket.php',
				'detection_method' => 'constant',
				'detection_value'  => 'WP_ROCKET_VERSION',
			],
			'flyingpress'              => [
				'name'             => __( 'FlyingPress', 'surerank' ),
				'slug'             => 'flyingpress/flyingpress.php',
				'detection_method' => 'constant',
				'detection_value'  => 'FLYINGPRESS_VERSION',
			],
			'nitropack'                => [
				'name'             => __( 'NitroPack', 'surerank' ),
				'slug'             => 'nitropack/main.php',
				'detection_method' => 'class',
				'detection_value'  => 'NitroPack\\WordPress\\NitroPack',
			],
			'wp-optimize-premium'      => [
				'name'             => __( 'WP-Optimize Premium', 'surerank' ),
				'slug'             => 'wp-optimize-premium/wp-optimize.php',
				'detection_method' => 'constant',
				'detection_value'  => 'WPO_PREMIUM',
			],
			'swift-performance'        => [
				'name'             => __( 'Swift Performance', 'surerank' ),
				'slug'             => 'swift-performance/performance.php',
				'detection_method' => 'class',
				'detection_value'  => 'Swift_Performance',
			],

			// Popular Free Cache Plugins.
			'litespeed-cache'          => [
				'name'             => __( 'LiteSpeed Cache', 'surerank' ),
				'slug'             => 'litespeed-cache/litespeed-cache.php',
				'detection_method' => 'constant',
				'detection_value'  => 'LSCWP_V',
			],
			'w3-total-cache'           => [
				'name'             => __( 'W3 Total Cache', 'surerank' ),
				'slug'             => 'w3-total-cache/w3-total-cache.php',
				'detection_method' => 'constant',
				'detection_value'  => 'W3TC_VERSION',
			],
			'wp-super-cache'           => [
				'name'             => __( 'WP Super Cache', 'surerank' ),
				'slug'             => 'wp-super-cache/wp-super-cache.php',
				'detection_method' => 'constant',
				'detection_value'  => 'WPCACHEHOME',
			],
			'wp-fastest-cache'         => [
				'name'             => __( 'WP Fastest Cache', 'surerank' ),
				'slug'             => 'wp-fastest-cache/wpFastestCache.php',
				'detection_method' => 'class',
				'detection_value'  => 'WpFastestCache',
			],
			'wp-optimize'              => [
				'name'             => __( 'WP-Optimize', 'surerank' ),
				'slug'             => 'wp-optimize/wp-optimize.php',
				'detection_method' => 'class',
				'detection_value'  => 'WP_Optimize',
			],
			'breeze'                   => [
				'name'             => __( 'Breeze', 'surerank' ),
				'slug'             => 'breeze/breeze.php',
				'detection_method' => 'constant',
				'detection_value'  => 'BREEZE_VERSION',
			],
			'cache-enabler'            => [
				'name'             => __( 'Cache Enabler', 'surerank' ),
				'slug'             => 'cache-enabler/cache-enabler.php',
				'detection_method' => 'class',
				'detection_value'  => 'Cache_Enabler',
			],
			'comet-cache'              => [
				'name'             => __( 'Comet Cache', 'surerank' ),
				'slug'             => 'comet-cache/comet-cache.php',
				'detection_method' => 'constant',
				'detection_value'  => 'COMET_CACHE_VERSION',
			],
			'swift-performance-lite'   => [
				'name'             => __( 'Swift Performance Lite', 'surerank' ),
				'slug'             => 'swift-performance-lite/performance.php',
				'detection_method' => 'class',
				'detection_value'  => 'Swift_Performance_Lite',
			],

			// Object Cache Plugins.
			'redis-cache'              => [
				'name'             => __( 'Redis Object Cache', 'surerank' ),
				'slug'             => 'redis-cache/redis-cache.php',
				'detection_method' => 'constant',
				'detection_value'  => 'WP_REDIS_VERSION',
			],

			// Performance & Cache Plugins.
			'hummingbird-performance'  => [
				'name'             => __( 'Hummingbird Performance', 'surerank' ),
				'slug'             => 'hummingbird-performance/wp-hummingbird.php',
				'detection_method' => 'constant',
				'detection_value'  => 'WPHB_VERSION',
			],
			'autoptimize'              => [
				'name'             => __( 'Autoptimize', 'surerank' ),
				'slug'             => 'autoptimize/autoptimize.php',
				'detection_method' => 'class',
				'detection_value'  => 'autoptimizeMain',
			],
			'sg-cachepress'            => [
				'name'             => __( 'SiteGround Speed Optimizer', 'surerank' ),
				'slug'             => 'sg-cachepress/sg-cachepress.php',
				'detection_method' => 'class',
				'detection_value'  => 'SiteGround_Optimizer\\Supercacher\\Supercacher',
			],
			'cloudflare'               => [
				'name'             => __( 'Cloudflare', 'surerank' ),
				'slug'             => 'cloudflare/cloudflare.php',
				'detection_method' => 'class',
				'detection_value'  => 'CF\\WordPress\\Hooks',
			],
			'hyper-cache'              => [
				'name'             => __( 'Hyper Cache', 'surerank' ),
				'slug'             => 'hyper-cache/plugin.php',
				'detection_method' => 'class',
				'detection_value'  => 'HyperCache',
			],
			'perfmatters'              => [
				'name'             => __( 'Perfmatters', 'surerank' ),
				'slug'             => 'perfmatters/perfmatters.php',
				'detection_method' => 'class',
				'detection_value'  => 'Perfmatters\\Config',
			],

			// Specialized Cache Plugins.
			'speedycache'              => [
				'name'             => __( 'SpeedyCache', 'surerank' ),
				'slug'             => 'speedycache/speedycache.php',
				'detection_method' => 'class',
				'detection_value'  => 'SpeedyCache',
			],
			'docket-cache'             => [
				'name'             => __( 'Docket Cache', 'surerank' ),
				'slug'             => 'docket-cache/docket-cache.php',
				'detection_method' => 'constant',
				'detection_value'  => 'DOCKET_CACHE_VERSION',
			],
			'tenweb-speed-optimizer'   => [
				'name'             => __( '10Web Booster', 'surerank' ),
				'slug'             => 'tenweb-speed-optimizer/tenweb_speed_optimizer.php',
				'detection_method' => 'class',
				'detection_value'  => 'TenWebOptimizer',
			],
			'wp-cloudflare-page-cache' => [
				'name'             => __( 'Super Page Cache', 'surerank' ),
				'slug'             => 'wp-cloudflare-page-cache/wp-cloudflare-page-cache.php',
				'detection_method' => 'class',
				'detection_value'  => 'CF_Page_Cache',
			],
			'wp-rest-cache'            => [
				'name'             => __( 'WP REST Cache', 'surerank' ),
				'slug'             => 'wp-rest-cache/wp-rest-cache.php',
				'detection_method' => 'class',
				'detection_value'  => 'WP_REST_Cache_Plugin',
			],
			'cache-control'            => [
				'name'             => __( 'Cache Control', 'surerank' ),
				'slug'             => 'cache-control/cache-control.php',
				'detection_method' => 'class',
				'detection_value'  => 'Cache_Control',
			],

			// Additional Cache Plugins.
			'aruba-hispeed-cache'      => [
				'name'             => __( 'Aruba HiSpeed Cache', 'surerank' ),
				'slug'             => 'aruba-hispeed-cache/aruba-hispeed-cache.php',
				'detection_method' => 'class',
				'detection_value'  => 'ArubaHiSpeedCacheWp',
			],
			'atec-cache-apcu'          => [
				'name'             => __( 'atec Cache APCu', 'surerank' ),
				'slug'             => 'atec-cache-apcu/atec-cache-apcu.php',
				'detection_method' => 'class',
				'detection_value'  => 'ATEC_cache_apcu',
			],
		];

		// Check each cache plugin for active status.
		foreach ( $cache_plugins as $key => $plugin ) {
			$is_active = false;

			switch ( $plugin['detection_method'] ) {
				case 'constant':
					$is_active = defined( $plugin['detection_value'] );
					break;

				case 'function':
					$is_active = function_exists( $plugin['detection_value'] );
					break;

				case 'class':
					$is_active = class_exists( $plugin['detection_value'] );
					break;

				default:
					$is_active = false;
					break;
			}

			// If plugin is active, add it to the results.
			if ( $is_active ) {
				$active_cache_plugins[ $key ] = [
					'name'             => $plugin['name'],
					'slug'             => $plugin['slug'],
					'detection_method' => sprintf(
						/* translators: 1: detection method, 2: detection value */
						__( 'Detected via %1$s: %2$s', 'surerank' ),
						$plugin['detection_method'],
						$plugin['detection_value']
					),
				];
			}
		}

		$cache_result = [
			'has_cache_plugin' => ! empty( $active_cache_plugins ),
			'active_plugins'   => $active_cache_plugins,
		];

		return $cache_result;
	}

	/**
	 * Simple helper method to check if any cache plugin is active.
	 *
	 * This is a lightweight wrapper around has_active_cache_plugin()
	 * that returns only a boolean value for simpler usage.
	 *
	 * @since 1.2.0
	 * @return bool True if any cache plugin is active, false otherwise.
	 */
	public static function is_cache_plugin_active(): bool {
		$result = self::has_active_cache_plugin();
		return $result['has_cache_plugin'];
	}

	/**
	 * Get associated plugins that should be deactivated along with the main plugin.
	 *
	 * @param string $plugin_path The main plugin path.
	 * @return array<string> List of associated plugin paths to deactivate.
	 * @since 1.1.1
	 */
	private function get_associated_plugins( string $plugin_path ) {
		$associated_plugins = [];
		switch ( $plugin_path ) {
			case 'seo-by-rank-math/rank-math.php':
				$associated_plugins = [ 'seo-by-rank-math/rank-math.php', 'seo-by-rank-math-pro/rank-math-pro.php' ];
				break;
			case 'wordpress-seo/wp-seo.php':
				$associated_plugins = [ 'wordpress-seo/wp-seo.php', 'wordpress-seo-premium/wp-seo-premium.php' ];
				break;
			case 'wp-seopress/seopress.php':
				$associated_plugins = [ 'wp-seopress/seopress.php', 'wp-seopress-pro/seopress-pro.php' ];
				break;
		}

		return $associated_plugins;
	}

	/**
	 * Validates the plugin slug and returns the importer instance.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request.
	 * @return Importer The validated importer instance.
	 */
	private function validate_and_get_importer( $request ) {
		$plugin_slug = $request->get_param( 'plugin_slug' );

		if ( ! is_string( $plugin_slug ) ) {
			Send_Json::error(
				[ 'message' => __( 'Invalid plugin slug specified.', 'surerank' ) ]
			);
		}

		$importer_class = $this->importers[ $plugin_slug ];
		/**
		 * The importer class must implement Importer.
		 *
		 * @var Importer $importer
		 */
		$importer = new $importer_class();

		if ( ! $importer instanceof Importer ) {
			Send_Json::error(
				[
					'message' => sprintf(
						// translators: %s: importer class name.
						__( 'Invalid importer class: %s does not implement Importer.', 'surerank' ),
						$importer_class
					),
				]
			);
		}

		return $importer;
	}

	/**
	 * Get supported plugins for migration.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_supported_plugins(): array {
		$supported_plugins = [];

		foreach ( $this->importers as $slug => $importer_class ) {
			if ( ! class_exists( $importer_class ) ) {
				continue;
			}

			$importer = new $importer_class();

			if ( ! method_exists( $importer, 'get_plugin_name' ) || ! method_exists( $importer, 'get_plugin_file' ) ) {
				continue;
			}

			$supported_plugins[ $slug ] = [
				'name' => $importer->get_plugin_name(),
				'slug' => $importer->get_plugin_file(),
			];
		}

		return $supported_plugins;
	}

	/**
	 * Get plugin path from slug.
	 *
	 * @param string $slug The plugin slug.
	 * @return string|null The plugin path or null if not found.
	 */
	private function get_plugin_path_from_slug( string $slug ): ?string {
		$supported_plugins = $this->get_supported_plugins();
		return $supported_plugins[ $slug ]['slug'] ?? null;
	}

	/**
	 * Validates the importer methods for the given type (post or term).
	 *
	 * @param Importer $importer The importer instance.
	 * @param string   $type The type of item ('post' or 'term').
	 * @return bool True if methods exist, false otherwise.
	 */
	private function validate_importer_methods( Importer $importer, string $type ): bool {
		$detect_method = 'detect_' . $type;
		$import_method = 'import_' . $type;
		return method_exists( $importer, $detect_method ) && method_exists( $importer, $import_method );
	}

	/**
	 * Processes items (posts or terms) for migration.
	 *
	 * @param array<int> $ids The array of item IDs.
	 * @param Importer   $importer The importer instance.
	 * @param string     $type The type of item ('post' or 'term').
	 * @return array<int|string, mixed> The results array with success count, failed items, and import data.
	 */
	private function process_migration( array $ids, Importer $importer, string $type ): array {
		$results          = [
			'success'      => 0,
			'failed_items' => [],
		];
		$send_import_data = [];
		$detect_method    = 'detect_' . $type;
		$import_method    = 'import_' . $type;

		foreach ( $ids as $id ) {
			try {
				$detect = $importer->$detect_method( (int) $id );

				if ( is_array( $detect ) && isset( $detect['no_data_found'] ) && $detect['no_data_found'] ) {
					$send_import_data[ $id ] = $detect['message'] ?? __( 'No data found for this item.', 'surerank' );
					$results['success']++;
					continue;
				}

				if ( ! $detect['success'] ) {
					$results['failed_items'][ $id ] = $detect['message'] ?? __( 'Detection failed.', 'surerank' );
					continue;
				}

				$import = $importer->$import_method( (int) $id );

				if ( ! is_array( $import ) || ! isset( $import['success'] ) ) {
					$results['failed_items'][ $id ] = __( 'Invalid import response.', 'surerank' );
					continue;
				}

				$send_import_data[ $id ] = isset( $import['data'] ) && is_array( $import['data'] ) ? $import['data'] : [];

				if ( $import['success'] ) {
					$results['success']++;
				} else {
					$results['failed_items'][ $id ] = $import['message'] ?? __( 'Import failed.', 'surerank' );
				}
			} catch ( \Exception $e ) {
				$results['failed_items'][ $id ] = sprintf(
					// translators: %s: error message.
					__( 'Error: %s', 'surerank' ),
					$e->getMessage()
				);
			}
		}

		$results['passed_items'] = $send_import_data;
		return $results;
	}

	/**
	 * Handles cleanup after successful imports.
	 *
	 * @param Importer $importer The importer instance.
	 * @param bool     $cleanup Whether cleanup is requested.
	 * @param int      $success_count The number of successful imports.
	 * @return array<string, mixed> The cleanup results.
	 */
	private function handle_cleanup( Importer $importer, bool $cleanup, int $success_count ): array {
		$results = [];
		if ( $cleanup && $success_count > 0 && method_exists( $importer, 'cleanup' ) ) {
			$cleanup_resp = $importer->cleanup();
			if ( is_array( $cleanup_resp ) ) {
				$results['cleanup']         = $cleanup_resp['success'];
				$results['cleanup_message'] = $cleanup_resp['message'];
			}
		}
		return $results;
	}

	/**
	 * Formats the final migration response.
	 *
	 * @param array<int|string, mixed> $results The migration results.
	 * @param Importer                 $importer The importer instance.
	 * @param array<int>               $ids The array of item IDs.
	 * @param string                   $item_type The type of item ('posts' or 'terms').
	 * @return array<string, mixed> The formatted results array.
	 */
	private function format_response( array $results, Importer $importer, array $ids, string $item_type ): array {
		$plugin_name        = method_exists( $importer, 'get_plugin_name' ) ? $importer->get_plugin_name() : 'Unknown';
		$results['message'] = sprintf(
			// translators: 1: imported count, 2: total count, 3: item type, 4: plugin name.
			__( 'Imported %1$d of %2$d %3$s from %4$s.', 'surerank' ),
			$results['success'],
			count( $ids ),
			$item_type,
			$plugin_name
		);
		return $results;
	}

	/**
	 * Helper to validate ID arrays/values.
	 *
	 * @param mixed $param Incoming param.
	 * @return bool
	 */
	private function validate_ids( $param ): bool {
		// Accept both array and single integer.
		if ( is_numeric( $param ) && (int) $param > 0 ) {
			return true;
		}

		if ( ! is_array( $param ) || empty( $param ) ) {
			return false;
		}

		// Validate all array elements are positive integers.
		return array_reduce(
			$param,
			static fn( $valid, $id) => $valid && is_numeric( $id ) && (int) $id > 0,
			true
		);
	}

	/**
	 * Fetch all public taxonomies, excluding unsupported ones.
	 *
	 * @return array<string, object> Array of taxonomy objects.
	 */
	private function get_public_taxonomies(): array {

		return ImporterUtils::get_excluded_taxonomies();
	}

	/**
	 * Group IDs by taxonomy or post type.
	 *
	 * @param array<int|string>     $ids Array of term IDs or post IDs.
	 * @param bool                  $is_taxonomy True to group terms, False to group posts.
	 * @param array<string, object> $taxonomies_objects Array of taxonomy objects (required if $is_taxonomy is true).
	 *
	 * @return array<string, array<mixed>> Grouped data.
	 */
	private function group_items( array $ids, bool $is_taxonomy = false, array $taxonomies_objects = [] ): array {
		$grouped = [];

		foreach ( $ids as $id ) {
			if ( $is_taxonomy ) {
				$term = get_term( (int) $id );
				if ( is_wp_error( $term ) || ! $term ) {
					continue;
				}
				$type  = $term->taxonomy;
				$label = $taxonomies_objects[ $type ]->label ?? $type;
				$key   = 'term_ids';
			} else {
				$type = get_post_type( (int) $id );
				if ( false === $type ) {
					continue;
				}
				$object = get_post_type_object( $type );
				$label  = isset( $object->labels ) ? $object->labels->name : $type;
				$key    = 'post_ids';
			}

			if ( ! isset( $grouped[ $type ] ) ) {
				$grouped[ $type ] = [
					'count' => 0,
					'title' => $label,
					$key    => [],
				];
			}

			$grouped[ $type ][ $key ][] = (int) $id;
			$grouped[ $type ]['count']++;
		}

		return $grouped;
	}
}
