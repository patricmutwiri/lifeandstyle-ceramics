<?php
/**
 * Importer Utilities
 *
 * Shared helper functions for all importer classes.
 *
 * @package SureRank\Inc\Importers
 * @since   1.1.0
 */

namespace SureRank\Inc\Importers;

use Exception;
use SureRank\Inc\Admin\Update_Timestamp;
use SureRank\Inc\API\Admin;
use SureRank\Inc\API\Post;
use SureRank\Inc\API\Term;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Schema\Helper as SchemaHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ImporterUtils
 *
 * Provides shared functionality (response builder, metadata persistence) for importers.
 */
final class ImporterUtils {

	/**
	 * Build a standard response array.
	 *
	 * @param string $message Human-readable message.
	 * @param bool   $success Whether the operation succeeded.
	 * @param mixed  $data    Optional additional data to include in the response.
	 * @param bool   $no_data_found Whether no data was found during the operation.
	 * @phpstan-return array{success: bool, message: string, data: mixed}
	 */
	public static function build_response( string $message, bool $success = true, $data = [], $no_data_found = false ): array {
		return [
			'success'       => $success,
			'message'       => $message,
			'data'          => $data,
			'no_data_found' => $no_data_found,
		];
	}

	/**
	 * Update SureRank post_meta for a given post.
	 *
	 * Replaces implementation of update_post_meta_data in each importer.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Prepared SureRank meta values to write.
	 * @phpstan-return array{success: bool, message: string}
	 */
	public static function update_post_meta_data( int $post_id, array $data ): array {
		try {
			Post::update_post_meta_common( $post_id, $data );

			// Mark this post as having completed migration.
			self::update_surerank_migrated( $post_id );

			return self::build_response(
				sprintf(
					/* translators: %s: post ID */
					__( 'Successfully updated SureRank settings for post ID %s.', 'surerank' ),
					$post_id
				)
			);
		} catch ( Exception $e ) {
			return self::build_response(
				sprintf(
					/* translators: 1: post ID, 2: error message */
					__( 'Failed to update SureRank settings for post ID %1$s. Error: %2$s', 'surerank' ),
					$post_id,
					$e->getMessage()
				),
				false
			);
		}
	}

	/**
	 * Update SureRank term_meta for a given term.
	 *
	 * Replaces implementation of update_term_meta_data in each importer.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $data    Prepared SureRank meta values to write.
	 * @phpstan-return array{success: bool, message: string}
	 */
	public static function update_term_meta_data( int $term_id, array $data ): array {
		try {

			Term::update_term_meta_common( $term_id, $data );
			self::update_surerank_migrated( $term_id, false );

			return self::build_response(
				sprintf(
					/* translators: %s: term ID */
					__( 'Successfully updated SureRank settings for term ID %s.', 'surerank' ),
					$term_id
				)
			);
		} catch ( Exception $e ) {
			return self::build_response(
				sprintf(
					/* translators: 1: term ID, 2: error message */
					__( 'Failed to update SureRank settings for term ID %1$s. Error: %2$s', 'surerank' ),
					$term_id,
					$e->getMessage()
				),
				false
			);
		}
	}

	/**
	 * Update global settings.
	 *
	 * @param array<string, mixed> $data Data to update.
	 * @return void
	 */
	public static function update_global_settings( $data ) {
		$db_options = Settings::get();

		$updated_options = Admin::get_instance()->get_updated_options( $data, $db_options );

		Helper::update_flush_rules( $updated_options );

		if ( is_array( $updated_options ) && array_intersect( [ 'social_profiles', 'facebook_page_url', 'twitter_profile_username' ], $updated_options ) ) {
			$data['social_profiles']['facebook'] = $data['facebook_page_url'] ?? '';

			if ( ! empty( $data['twitter_profile_username'] ) ) {
				$data['social_profiles']['twitter'] = str_replace( '@', '', $data['twitter_profile_username'] );
			}

			Admin::get_instance()->process_onboarding_data( $data, $data );
		}

		$data = array_merge( $db_options, $data );

		if ( Update::option( SURERANK_SETTINGS, $data ) ) {
			// Update global timestamp.
			Update_Timestamp::timestamp_option();
		}
	}

	/**
	 * Update SureRank migration status for a term.
	 *
	 * @param int  $id Term ID.
	 * @param bool $is_post Whether the ID refers to a post (true) or term (false).
	 * @return void
	 */
	public static function update_surerank_migrated( $id, $is_post = true ) {

		if ( $is_post ) {
			Update::post_meta( $id, 'surerank_migration', 1 );
			return;
		}

		Update::term_meta( $id, 'surerank_migration', 1 );
	}

	/**
	 * Map social profiles from Rank Math to SureRank format.
	 *
	 * @param string|array<string> $social_profiles Social profiles from Rank Math.
	 * @param array<string,string> $surerank_social_profiles SureRank social profiles mapping.
	 * @return array<string,string> Mapped social profiles.
	 */
	public static function get_mapped_social_profiles( $social_profiles, $surerank_social_profiles ) {

		if ( empty( $social_profiles ) ) {
			return $surerank_social_profiles;
		}

		$decoded_profiles = [];

		if ( is_string( $social_profiles ) ) {
			$decoded_profiles = array_filter( array_map( 'trim', explode( "\n", $social_profiles ) ) );
			$decoded_profiles = array_values( $decoded_profiles );
		} elseif ( is_array( $social_profiles ) ) {
			$decoded_profiles = $social_profiles;
		}
		foreach ( $surerank_social_profiles as $key => $value ) {

			foreach ( $decoded_profiles as $profile ) {
				// Check if $key is a substring of the profile URL (case-insensitive if needed).
				if ( stripos( $profile, $key ) !== false ) {
					$surerank_social_profiles[ $key ] = $profile;
					break; // Found the first matching profile, no need to continue.
				}
				// Special handling for WhatsApp, Telegram, and Bluesky.
				if ( self::is_special_social_platform( $profile, $key ) ) {
					$surerank_social_profiles[ $key ] = $profile;
					break;
				}
			}
		}

		return $surerank_social_profiles;
	}

	/**
	 * Get the taxonomies that should be excluded from the import.
	 *
	 * @return array<string, \WP_Taxonomy> Array of taxonomy objects.
	 * @since 1.1.0
	 */
	public static function get_excluded_taxonomies() {
		$taxonomies_objects = get_taxonomies(
			[ 'public' => true ],
			'objects'
		);

		$unsupported = SchemaHelper::UNSUPPORTED_TAXONOMIES;
		foreach ( $unsupported as $slug ) {
			if ( isset( $taxonomies_objects[ $slug ] ) ) {
				unset( $taxonomies_objects[ $slug ] );
			}
		}

		return $taxonomies_objects;
	}

	/**
	 * Prepares placeholders and parameters for SQL queries.
	 *
	 * @param array<string> $items Items for placeholders (e.g., post types or taxonomies).
	 * @param array<string> $excluded_keys Excluded meta keys.
	 * @param string        $meta_prefix Meta key prefix.
	 * @param int|null      $batch_size Batch size for pagination (null for count queries).
	 * @param int|null      $offset Offset for pagination (null for count queries).
	 * @return array{placeholders: array<string>, params: array<int|string>} Placeholders and parameters.
	 */
	public static function prepare_query_params( array $items, array $excluded_keys, string $meta_prefix, ?int $batch_size = null, ?int $offset = null ): array {
		$placeholders = [];
		$params       = array_merge(
			[ 'surerank_migration', $meta_prefix ],
			$excluded_keys,
			$items
		);

		// Create placeholders for items and excluded keys.
		$placeholders[] = implode( ',', array_fill( 0, count( $excluded_keys ), '%s' ) ); // For excluded keys.
		$placeholders[] = implode( ',', array_fill( 0, count( $items ), '%s' ) ); // For items.

		if ( $batch_size !== null && $offset !== null ) {
			$params[] = $batch_size;
			$params[] = $offset;
		}

		return [
			'placeholders' => $placeholders,
			'params'       => $params,
		];
	}

	/**
	 * Get total count of posts for pagination.
	 *
	 * @param array<string> $post_types Array of post type names.
	 * @param array<string> $excluded_keys Array of excluded meta keys.
	 * @param string        $meta_prefix Meta key prefix.
	 * @return int Total number of posts.
	 */
	public static function get_total_posts_count( array $post_types, array $excluded_keys, string $meta_prefix ): int {
		global $wpdb;

		/**
		 * Prepares query parameters for post migration.
		 *
		 * This method sets up placeholders for database query to find posts that:
		 * - Have no 'surerank_migration' meta key
		 * - Have at least one meta key matching the prefix
		 * - Are not in the excluded keys list
		 * - Belong to specified post types
		 *
		 * The query is structured to:
		 * 1. Create placeholders for excluded keys
		 * 2. Create placeholders for post types
		 * 3. Filter by main meta key 'surerank_migration'
		 * 4. Filter by secondary meta keys using prefix matching
		 * 5. Count distinct post IDs meeting these criteria
		 *
		 * This is used for identifying posts that need migration in the WordPress database.
		 */
		$query_data = self::prepare_query_params( $post_types, $excluded_keys, $meta_prefix );

		$sql = "SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    AND pm.meta_key = %s
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                    AND pm2.meta_key LIKE %s";

		if ( ! empty( $excluded_keys ) ) {
			$sql .= " AND pm2.meta_key NOT IN ({$query_data['placeholders'][0]})";
		}

		$sql .= " WHERE p.post_type IN ({$query_data['placeholders'][1]})
                 AND p.post_status != 'auto-draft'
                 AND pm.meta_id IS NULL";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var($wpdb->prepare($sql, $query_data['params'])); // phpcs:ignore
	}

	/**
	 * Get post IDs for the given parameters.
	 *
	 * @param array<string> $post_types Array of post type names.
	 * @param array<string> $excluded_keys Array of excluded meta keys.
	 * @param string        $meta_prefix Meta key prefix.
	 * @param int           $batch_size Number of posts to retrieve.
	 * @param int           $offset Offset for pagination.
	 * @return array<int> Array of post IDs.
	 */
	public static function get_post_ids( array $post_types, array $excluded_keys, string $meta_prefix, int $batch_size, int $offset ): array {
		global $wpdb;

		$query_data = self::prepare_query_params( $post_types, $excluded_keys, $meta_prefix, $batch_size, $offset );

		$sql = "SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    AND pm.meta_key = %s
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                    AND pm2.meta_key LIKE %s";

		if ( ! empty( $excluded_keys ) ) {
			$sql .= " AND pm2.meta_key NOT IN ({$query_data['placeholders'][0]})";
		}

		$sql .= " WHERE p.post_type IN ({$query_data['placeholders'][1]})
                 AND p.post_status != 'auto-draft'
                 AND pm.meta_id IS NULL
                 ORDER BY p.ID
                 LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col($wpdb->prepare($sql, $query_data['params'])); // phpcs:ignore
	}

	/**
	 * Get total count of terms for pagination.
	 *
	 * @param array<string> $taxonomies Array of taxonomy names.
	 * @param array<string> $excluded_keys Array of excluded meta keys.
	 * @param string        $meta_prefix Meta key prefix.
	 * @return int Total number of terms.
	 */
	public static function get_total_terms_count( array $taxonomies, array $excluded_keys, string $meta_prefix ): int {
		global $wpdb;

		$query_data = self::prepare_query_params( $taxonomies, $excluded_keys, $meta_prefix );

		$sql = "SELECT COUNT(DISTINCT t.term_id)
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
                    AND tm.meta_key = %s
                INNER JOIN {$wpdb->termmeta} tm2 ON t.term_id = tm2.term_id
                    AND tm2.meta_key LIKE %s";

		if ( ! empty( $excluded_keys ) ) {
			$sql .= " AND tm2.meta_key NOT IN ({$query_data['placeholders'][0]})";
		}

		$sql .= " WHERE tt.taxonomy IN ({$query_data['placeholders'][1]})
                 AND tm.meta_id IS NULL";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var($wpdb->prepare($sql, $query_data['params'])); // phpcs:ignore
	}

	/**
	 * Get term IDs for the given parameters.
	 *
	 * @param array<string> $taxonomies Array of taxonomy names.
	 * @param array<string> $excluded_keys Array of excluded meta keys.
	 * @param string        $meta_prefix Meta key prefix.
	 * @param int           $batch_size Number of terms to retrieve.
	 * @param int           $offset Offset for pagination.
	 * @return array<int> Array of term IDs.
	 */
	public static function get_term_ids( array $taxonomies, array $excluded_keys, string $meta_prefix, int $batch_size, int $offset ): array {
		global $wpdb;

		/**
		 * Prepares query parameters for term migration.
		 *
		 * This method sets up placeholders for database query to find terms that:
		 * - Have no 'surerank_migration' meta key
		 * - Have at least one meta key matching the prefix
		 * - Are not in the excluded keys list
		 * - Belong to specified taxonomies
		 *
		 * The query is structured to:
		 * 1. Create placeholders for excluded keys
		 * 2. Create placeholders for taxonomies
		 * 3. Filter by main meta key 'surerank_migration'
		 * 4. Filter by secondary meta keys using prefix matching
		 * 5. Select distinct term IDs meeting these criteria
		 *
		 * This is used for identifying terms that need migration in the WordPress database.
		 */

		$query_data = self::prepare_query_params( $taxonomies, $excluded_keys, $meta_prefix, $batch_size, $offset );

		$sql = "SELECT DISTINCT t.term_id
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
                    AND tm.meta_key = %s
                INNER JOIN {$wpdb->termmeta} tm2 ON t.term_id = tm2.term_id
                    AND tm2.meta_key LIKE %s";

		if ( ! empty( $excluded_keys ) ) {
			$sql .= " AND tm2.meta_key NOT IN ({$query_data['placeholders'][0]})";
		}

		$sql .= " WHERE tt.taxonomy IN ({$query_data['placeholders'][1]})
                 AND tm.meta_id IS NULL
                 ORDER BY t.term_id
                 LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col($wpdb->prepare($sql, $query_data['params'])); // phpcs:ignore
	}

	/**
	 * Check if the given type is valid.
	 *
	 * @param string                     $type The type to check.
	 * @param array<string>              $not_valid_types List of invalid types.
	 * @param array<string|\WP_Taxonomy> $public_types List of public types.
	 * @param bool                       $is_taxonomy Whether the type is a taxonomy.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_type_valid( $type, $not_valid_types = [], $public_types = [], $is_taxonomy = false ) {
		if ( $type === '' || empty( $type ) ) {
			return false;
		}

		if ( in_array( $type, $not_valid_types, true ) ) {
			return false;
		}

		if ( $is_taxonomy && ! isset( $public_types[ $type ] ) ) {
			return false;
		}

		if ( ! $is_taxonomy && ! in_array( $type, $public_types, true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Return a response indicating an invalid post type.
	 *
	 * @param int  $post_id The ID of the post or term.
	 * @param bool $is_taxonomy Whether the ID refers to a taxonomy (true) or post (false).
	 * @return array{success: bool, message: string}
	 */
	public static function not_valid_response( $post_id, $is_taxonomy = false ): array {
		$message = $is_taxonomy
			? sprintf(
				/* translators: %d: term ID */
				__( 'Invalid Term ID %d.', 'surerank' ),
				$post_id
			)
			: sprintf(
				/* translators: %d: post ID */
				__( 'Invalid Post ID %d.', 'surerank' ),
				$post_id
			);

		return self::build_response(
			$message,
			false,
			[],
			true
		);
	}

	/**
	 * Check if a profile URL matches a specific social platform.
	 *
	 * @param string $profile The profile URL to check.
	 * @param string $key The social platform key.
	 * @return bool True if the profile matches the platform.
	 */
	private static function is_special_social_platform( string $profile, string $key ): bool {
		$platform_patterns = [
			'whatsapp' => 'https://wa.me/',
			'telegram' => 'https://t.me/',
			'bluesky'  => 'https://bsky.app/',
		];

		return isset( $platform_patterns[ $key ] ) && stripos( $profile, $platform_patterns[ $key ] ) !== false;
	}
}
