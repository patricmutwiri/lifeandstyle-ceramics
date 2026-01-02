<?php
/**
 * Cache Functions
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Traits\Get_Instance;

/**
 * Cache class for file storage operations
 *
 * @since 1.2.0
 */
class Cache {

	use Get_Instance;

	/**
	 * Cache directory path
	 *
	 * @var string
	 */
	private static $cache_dir = '';

	/**
	 * Initialize cache directory
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function init() {
		self::$cache_dir = wp_upload_dir()['basedir'] . '/surerank/';

		// Create cache directory if it doesn't exist.
		if ( ! file_exists( self::$cache_dir ) ) {
			$result = wp_mkdir_p( self::$cache_dir );
		}
	}

	/**
	 * Store data to file
	 *
	 * @param string $filename The filename to store data.
	 * @param string $data The data to store.
	 * @since 1.2.0
	 * @return bool True on success, false on failure
	 */
	public static function store_file( string $filename, string $data ): bool {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		// Sanitize filename and prevent directory traversal attacks.
		$filename = self::sanitize_filename( $filename );

		$filepath = self::$cache_dir . $filename;

		// Create directory if it doesn't exist.
		$dir = dirname( $filepath );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Use WordPress filesystem API. for better security.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem->put_contents( $filepath, $data, FS_CHMOD_FILE );
	}

	/**
	 * Get data from file
	 *
	 * @param string $filename The filename to retrieve data from.
	 * @since 1.2.0
	 * @return string|false File contents on success, false on failure
	 */
	public static function get_file( string $filename ) {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		// Sanitize filename to prevent directory traversal attacks.
		$filename = self::sanitize_filename( $filename );

		$filepath = self::$cache_dir . $filename;

		if ( ! file_exists( $filepath ) ) {
			return false;
		}

		// Use WordPress filesystem API.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem->get_contents( $filepath );
	}

	/**
	 * Delete cache file
	 *
	 * @param string $filename The filename to delete.
	 * @since 1.2.0
	 * @return bool True on success, false on failure
	 */
	public static function delete_file( string $filename ): bool {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		// Sanitize filename and prevent directory traversal attacks.
		$filename = self::sanitize_filename( $filename );

		$filepath = self::$cache_dir . $filename;

		if ( ! file_exists( $filepath ) ) {
			return false;
		}

		// Use WordPress filesystem API.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem->delete( $filepath );
	}

	/**
	 * Clear all cache files
	 *
	 * @since 1.2.0
	 * @return bool True on success, false on failure
	 */
	public static function clear_all(): bool {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		if ( ! file_exists( self::$cache_dir ) ) {
			return true;
		}

		// Use WordPress filesystem API.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem->delete( self::$cache_dir, true );
	}

	/**
	 * Get cache file path
	 *
	 * @param string $filename The filename.
	 * @since 1.2.0
	 * @return string Full file path
	 */
	public static function get_file_path( string $filename ): string {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		// Sanitize filename to prevent directory traversal attacks.
		$filename = self::sanitize_filename( $filename );

		return self::$cache_dir . $filename;
	}

	/**
	 * Check if cache file exists
	 *
	 * @param string $filename The filename to check.
	 * @since 1.2.0
	 * @return bool True if file exists, false otherwise
	 */
	public static function file_exists( string $filename ): bool {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		// Sanitize filename to prevent directory traversal attacks.
		$filename = self::sanitize_filename( $filename );

		return file_exists( self::$cache_dir . $filename );
	}

	/**
	 * Get all files from cache directory
	 *
	 * @since 1.2.0
	 * @param string $directory Optional directory name to scan (e.g., 'sitemap', 'metadata').
	 * @return array<string> Array of filenames in the cache directory
	 */
	public static function get_all_files( string $directory = '' ) {
		if ( empty( self::$cache_dir ) ) {
			self::init();
		}

		$target_dir = self::$cache_dir;
		if ( ! empty( $directory ) ) {
			// Sanitize directory name and prevent directory traversal.
			$directory  = self::sanitize_filename( $directory );
			$target_dir = self::$cache_dir . $directory . '/';
		}

		if ( ! file_exists( $target_dir ) ) {
			return [];
		}

		$files = scandir( $target_dir );
		if ( false === $files ) {
			return [];
		}

		$json_files = array_filter(
			$files,
			static function( $file ) {
				return $file !== '.' && $file !== '..' && pathinfo( $file, PATHINFO_EXTENSION ) === 'json';
			}
		);

		return array_values( $json_files );
	}

	/**
	 * Update sitemap index when a new chunk is created
	 *
	 * @param string $type Content type (post, category, etc.).
	 * @param int    $chunk_number The chunk number.
	 * @param int    $url_count Number of URLs in this chunk.
	 * @since 1.2.0
	 * @return void
	 */
	public static function update_sitemap_index( string $type, int $chunk_number, int $url_count ) {

		$sitemap_threshold = apply_filters( 'surerank_sitemap_threshold', 200 );
		$chunk_size        = apply_filters( 'surerank_sitemap_json_chunk_size', 20 );

		$chunks_per_sitemap   = (int) ceil( $sitemap_threshold / $chunk_size );
		$sitemap_index_number = (int) ceil( $chunk_number / $chunks_per_sitemap );

		$sitemap_index_filename = 'sitemap/' . $type . '-sitemap-' . $sitemap_index_number . '.json';
		$sitemap_index_data     = self::get_sitemap_index_data( $sitemap_index_filename, $type, $sitemap_index_number );

		$sitemap_index_data['updated_at'] = current_time( 'c' );

		self::update_unified_sitemap_index( $sitemap_index_filename );
	}

	/**
	 * Sanitize filename to prevent directory traversal attacks.
	 *
	 * @param string $filename The filename to sanitize.
	 * @since 1.2.0
	 * @return string Sanitized filename.
	 */
	private static function sanitize_filename( string $filename ): string {
		// Prevent directory traversal attacks.
		$filename = str_replace( '..', '', $filename );
		return ltrim( $filename, '/' ); // Remove leading slash.
	}

	/**
	 * Get sitemap index data, create if it doesn't exist
	 *
	 * @param string $filename Sitemap index filename.
	 * @param string $type Content type.
	 * @param int    $index_number Sitemap index number.
	 * @since 1.2.0
	 * @return array<string, mixed> Sitemap index data
	 */
	private static function get_sitemap_index_data( string $filename, string $type, int $index_number ): array {
		$existing_data = self::get_file( $filename );

		if ( $existing_data ) {
			$decoded_data = json_decode( $existing_data, true );
			if ( $decoded_data && is_array( $decoded_data ) ) {
				return $decoded_data;
			}
		}

		$sitemap_threshold = apply_filters( 'surerank_sitemap_threshold', 200 );

		return [
			'type'         => $type,
			'index_number' => $index_number,
		];
	}

	/**
	 * Update unified sitemap index
	 *
	 * @param string $sitemap_filename The sitemap filename that was just created/updated.
	 * @since 1.2.0
	 * @return void
	 */
	private static function update_unified_sitemap_index( string $sitemap_filename ) {
		$unified_index_filename = 'sitemap/sitemap_index.json';
		$unified_index_data     = self::get_unified_sitemap_index_data( $unified_index_filename );

		$xml_filename = str_replace( '.json', '.xml', $sitemap_filename );
		$xml_filename = str_replace( 'sitemap/', '', $xml_filename );
		$sitemap_url  = home_url( $xml_filename );

		$sitemap_exists = false;
		foreach ( $unified_index_data as &$sitemap_entry ) {
			if ( $sitemap_entry['link'] === $sitemap_url ) {
				$sitemap_entry['updated'] = current_time( 'c' );
				$sitemap_exists           = true;
				break;
			}
		}

		if ( ! $sitemap_exists ) {
			$unified_index_data[] = [
				'link'    => $sitemap_url,
				'updated' => current_time( 'c' ),
			];
		}

		usort(
			$unified_index_data,
			static function( $a, $b ) {
				return strnatcmp( $a['link'], $b['link'] );
			}
		);

		$json_data = wp_json_encode( $unified_index_data, JSON_PRETTY_PRINT );
		if ( $json_data ) {
			self::store_file( $unified_index_filename, $json_data );
		}
	}

	/**
	 * Get unified sitemap index data, create if it doesn't exist
	 *
	 * @param string $filename Unified sitemap index filename.
	 * @since 1.2.0
	 * @return array<string, mixed> Unified sitemap index data
	 */
	private static function get_unified_sitemap_index_data( string $filename ) {
		$existing_data = self::get_file( $filename );

		if ( $existing_data ) {
			$decoded_data = json_decode( $existing_data, true );
			if ( $decoded_data && is_array( $decoded_data ) ) {
				return $decoded_data;
			}
		}

		return [];
	}
}
