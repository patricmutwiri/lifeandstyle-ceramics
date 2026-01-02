<?php
/**
 * Import Export Utilities
 *
 * Utility functions for import/export functionality.
 *
 * @package SureRank\Inc\Import_Export
 * @since 1.2.0
 */

namespace SureRank\Inc\Import_Export;

use SureRank\Inc\Functions\Sanitize;
use SureRank\Inc\Functions\Validate;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Utils
 *
 * Utility functions for import/export operations.
 */
class Utils {

	/**
	 * Image setting keys that need special handling during import.
	 */
	private const IMAGE_KEYS = [
		'fallback_image',
		'home_page_facebook_image_url',
		'home_page_twitter_image_url',
	];

	/**
	 * Create success response
	 *
	 * @param mixed  $data Success data.
	 * @param string $message Success message.
	 * @return array<string, mixed> Success response array.
	 */
	public static function success_response( $data = [], $message = '' ) {
		$response = [
			'success' => true,
			'data'    => $data,
		];

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		return $response;
	}

	/**
	 * Create error response
	 *
	 * @param string             $message Error message.
	 * @param array<int, string> $errors Array of error details.
	 * @param mixed              $data Additional data.
	 * @return array<string, mixed> Error response array.
	 */
	public static function error_response( $message = '', $errors = [], $data = null ) {
		$response = [
			'success' => false,
			'message' => $message,
		];

		if ( ! empty( $errors ) ) {
			$response['errors'] = $errors;
		}

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		return $response;
	}

	/**
	 * Create validation result
	 *
	 * @param bool               $valid Whether validation passed.
	 * @param string             $message Validation message.
	 * @param array<int, string> $errors Array of validation errors.
	 * @return array<string, mixed> Validation result array.
	 */
	public static function validation_result( $valid, $message = '', $errors = [] ) {
		return [
			'valid'   => $valid,
			'message' => $message,
			'errors'  => Validate::array( $errors, [] ),
		];
	}

	/**
	 * Validate file upload data
	 *
	 * @param array<string, mixed> $file_data $_FILES array data.
	 * @return array<string, mixed> Validation result.
	 */
	public static function validate_uploaded_file( $file_data ) {
		$errors = [];

		// Validate file_data structure.
		if ( ! is_array( $file_data ) ) {
			return self::validation_result(
				false,
				__( 'Invalid file data.', 'surerank' ),
				[ __( 'File data must be an array.', 'surerank' ) ]
			);
		}

		// Check for upload errors.
		if ( ! empty( $file_data['error'] ) ) {
			switch ( $file_data['error'] ) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$errors[] = __( 'The uploaded file exceeds the maximum file size.', 'surerank' );
					break;
				case UPLOAD_ERR_PARTIAL:
					$errors[] = __( 'The uploaded file was only partially uploaded.', 'surerank' );
					break;
				case UPLOAD_ERR_NO_FILE:
					$errors[] = __( 'No file was uploaded.', 'surerank' );
					break;
				default:
					$errors[] = __( 'File upload failed.', 'surerank' );
			}
		}

		// Validate required fields.
		if ( empty( $file_data['name'] ) ) {
			$errors[] = __( 'File name is missing.', 'surerank' );
		}

		if ( empty( $file_data['tmp_name'] ) ) {
			$errors[] = __( 'Temporary file path is missing.', 'surerank' );
		}

		// Check file type.
		if ( ! empty( $file_data['name'] ) ) {
			$file_extension = strtolower( pathinfo( $file_data['name'], PATHINFO_EXTENSION ) );
			if ( 'json' !== $file_extension ) {
				$errors[] = __( 'Only JSON files are allowed.', 'surerank' );
			}
		}

		// Check file size (max 5MB).
		$max_size = 5 * 1024 * 1024; // 5MB
		if ( ! empty( $file_data['size'] ) && $file_data['size'] > $max_size ) {
			$errors[] = sprintf(
			/* translators: %s: Maximum file size limit */
				__( 'File size exceeds maximum limit of %s.', 'surerank' ),
				number_format( $max_size / 1024 / 1024, 2 ) . ' MB'
			);
		}

		if ( ! empty( $errors ) ) {
			return self::validation_result(
				false,
				__( 'File validation failed.', 'surerank' ),
				$errors
			);
		}

		return self::validation_result(
			true,
			__( 'File is valid.', 'surerank' )
		);
	}

	/**
	 * Validate JSON data
	 *
	 * @param string $json_data JSON string to validate.
	 * @return array<string, mixed> Validation result with decoded data.
	 */
	public static function validate_json( $json_data ) {
		if ( ! is_string( $json_data ) || empty( trim( $json_data ) ) ) {
			return self::validation_result(
				false,
				__( 'Invalid JSON data.', 'surerank' ),
				[ __( 'JSON data must be a non-empty string.', 'surerank' ) ]
			);
		}

		$decoded_data = json_decode( $json_data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return self::validation_result(
				false,
				__( 'Invalid JSON format.', 'surerank' ),
				[ json_last_error_msg() ]
			);
		}

		$result         = self::validation_result(
			true,
			__( 'JSON is valid.', 'surerank' )
		);
		$result['data'] = $decoded_data;

		return $result;
	}

	/**
	 * Validate import data structure
	 *
	 * @param array<string, mixed> $settings_data Import data to validate.
	 * @return array<string, mixed> Validation result.
	 */
	public static function validate_import_data( $settings_data ) {
		$errors = [];

		// Check if data is array.
		if ( ! is_array( $settings_data ) ) {
			return self::validation_result(
				false,
				__( 'Invalid settings data format.', 'surerank' ),
				[ __( 'Settings data must be an array.', 'surerank' ) ]
			);
		}

		// Check required fields.
		if ( ! isset( $settings_data['plugin'] ) || 'surerank' !== $settings_data['plugin'] ) {
			$errors[] = __( 'Invalid plugin identifier. This file does not contain SureRank settings.', 'surerank' );
		}

		if ( ! isset( $settings_data['settings'] ) || ! is_array( $settings_data['settings'] ) ) {
			$errors[] = __( 'Missing or invalid settings data.', 'surerank' );
		}

		// Validate version compatibility if needed.
		if ( isset( $settings_data['version'] ) ) {
			$version_check = self::validate_version_compatibility( $settings_data['version'] );
			if ( ! $version_check['compatible'] ) {
				$errors[] = $version_check['message'];
			}
		}

		if ( ! empty( $errors ) ) {
			return self::validation_result(
				false,
				__( 'Import data validation failed.', 'surerank' ),
				$errors
			);
		}

		return self::validation_result(
			true,
			__( 'Import data is valid.', 'surerank' )
		);
	}

	/**
	 * Validate version compatibility
	 *
	 * @param string $import_version Version from import data.
	 * @return array<string, mixed> Compatibility result.
	 */
	public static function validate_version_compatibility( $import_version ) {
		// For now, we'll accept all versions.
		// In the future, you might want to add version-specific logic.
		return [
			'compatible' => true,
			'message'    => __( 'Version compatible.', 'surerank' ),
		];
	}

	/**
	 * Sanitize categories array
	 *
	 * @param array<int, string> $categories Categories to sanitize.
	 * @param array<int, string> $valid_categories Valid category keys.
	 * @return array<int, string> Sanitized categories.
	 */
	public static function sanitize_categories( $categories, $valid_categories = [] ) {
		if ( ! is_array( $categories ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $categories as $category ) {
			$clean_category = Sanitize::text( $category );
			if ( ! empty( $clean_category ) && ( empty( $valid_categories ) || in_array( $clean_category, $valid_categories, true ) ) ) {
				$sanitized[] = $clean_category;
			}
		}

		return array_unique( $sanitized );
	}

	/**
	 * Get export header with metadata
	 *
	 * @return array<string, mixed> Export metadata.
	 */
	public static function get_export_header() {
		return [
			'plugin'     => 'surerank',
			'version'    => defined( 'SURERANK_VERSION' ) ? SURERANK_VERSION : '1.0.0',
			'timestamp'  => current_time( 'mysql' ),
			'site_url'   => get_site_url(),
			'wp_version' => get_bloginfo( 'version' ),
		];
	}

	/**
	 * Generate backup key
	 *
	 * @return string Backup option key.
	 */
	public static function generate_backup_key() {
		return 'surerank_settings_backup_' . time();
	}

	/**
	 * Read file content safely
	 *
	 * @param string $file_path Path to file.
	 * @return array<string, mixed> Result with content or error.
	 */
	public static function read_file_content( $file_path ) {
		if ( ! is_string( $file_path ) || empty( trim( $file_path ) ) ) {
			return self::error_response(
				__( 'Invalid file path.', 'surerank' )
			);
		}

		if ( ! file_exists( $file_path ) ) {
			return self::error_response(
				__( 'File does not exist.', 'surerank' )
			);
		}

		if ( ! is_readable( $file_path ) ) {
			return self::error_response(
				__( 'File is not readable.', 'surerank' )
			);
		}

		// Use VIP-compatible file reading if available, otherwise fallback to standard function.
		$content = file_get_contents( $file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		if ( false === $content ) {
			return self::error_response(
				__( 'Failed to read file content.', 'surerank' )
			);
		}

		return self::success_response( $content );
	}

	/**
	 * Initialize import results structure
	 *
	 * @return array<string, mixed> Initial import results array.
	 */
	public static function init_import_results() {
		return [
			'success'        => false,
			'imported_count' => 0,
			'errors'         => [],
			'warnings'       => [],
			'success_items'  => [],
			'backup_key'     => null,
			'message'        => '',
		];
	}

	/**
	 * Download and save image from URL to WordPress media library
	 *
	 * @param string $image_url URL of the image to download.
	 * @param string $setting_key Setting key for naming context (unused, kept for compatibility).
	 * @return array<string, mixed> Result with new URL or error.
	 */
	public static function download_and_save_image( $image_url, $setting_key = '' ) {
		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			return self::error_response(
				__( 'Invalid image URL.', 'surerank' )
			);
		}

		// Include required WordPress functions.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Prepare file array for sideload.
		$file_array = [];

		// Get filename from URL.
		$file_array['name'] = wp_basename( $image_url );

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $image_url );

		// If error downloading, return the error.
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return self::error_response(
			/* translators: %s: Error message from download_url */
				sprintf( __( 'Failed to download image: %s', 'surerank' ), $file_array['tmp_name']->get_error_message() )
			);
		}

		// Do the validation and storage using WordPress media_handle_sideload.
		$attachment_id = media_handle_sideload( $file_array, 0, null );

		// If error storing permanently, clean up and return error.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_array['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			return self::error_response(
			/* translators: %s: Error message from media_handle_sideload */
				sprintf( __( 'Failed to save image: %s', 'surerank' ), $attachment_id->get_error_message() )
			);
		}

		// Store the original source URL in meta for reference.
		add_post_meta( $attachment_id, '_surerank_source_url', $image_url );
		add_post_meta( $attachment_id, '_surerank_imported', true );

		// Get the new URL.
		$new_url = wp_get_attachment_url( $attachment_id );
		if ( ! $new_url ) {
			return self::error_response(
				__( 'Failed to get attachment URL.', 'surerank' )
			);
		}

		$filename = get_attached_file( $attachment_id );
		$filename = $filename ? basename( $filename ) : '';

		return self::success_response(
			[
				'url'           => $new_url,
				'attachment_id' => $attachment_id,
				'filename'      => $filename,
				'source'        => 'download_new_file',
				'reused'        => false,
			],
			__( 'Image downloaded and saved successfully.', 'surerank' )
		);
	}

	/**
	 * Process image settings during import.
	 *
	 * @param array<string, mixed> $image_urls Settings array to process.
	 * @return array<string, mixed> Processed settings with updated image URLs.
	 */
	public static function process_image_settings_import( $image_urls ) {
		$image_keys         = self::IMAGE_KEYS;
		$processed_settings = ! empty( $image_urls ) ? $image_urls : [];
		foreach ( $image_keys as $key ) {
			if ( ! isset( $processed_settings[ $key ] ) || empty( $processed_settings[ $key ] ) ) {
				continue;
			}

			$image_url = $processed_settings[ $key ];

			// Skip local URLs to avoid downloading the same images.
			if ( strpos( $image_url, home_url() ) !== false ) {
				continue;
			}

			// Try to download from URL if it's accessible.
			if ( filter_var( $image_url, FILTER_VALIDATE_URL ) && self::is_url_accessible( $image_url ) ) {
				$download_result = self::download_and_save_image( $image_url, $key );
				if ( $download_result['success'] ) {
					$processed_settings[ $key ] = $download_result['data']['url'] ?? '';
				}
			}
		}

		return $processed_settings;
	}

	/**
	 * Get image setting keys
	 *
	 * @return array<int, string> Array of image setting keys.
	 */
	public static function get_image_setting_keys() {
		return self::IMAGE_KEYS;
	}

	/**
	 * Check if a URL is accessible and returns an image
	 *
	 * @param string $url URL to check.
	 * @return bool True if URL is accessible and returns valid image content.
	 */
	public static function is_url_accessible( $url ) {
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Use WordPress HTTP API with minimal timeout for quick check.
		$response = wp_safe_remote_head(
			$url,
			[
				'timeout'    => 3, // Quick timeout for accessibility check.
				'user-agent' => 'SureRank WordPress Plugin',
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return false;
		}

		// Check if the response indicates it's an image.
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! empty( $content_type ) ) {
			// Handle case where content-type could be an array.
			$content_type_string = is_array( $content_type ) ? $content_type[0] : $content_type;
			if ( strpos( $content_type_string, 'image/' ) === 0 ) {
				return true;
			}
		}

		// If no content-type header or not an image type, return false.
		return false;
	}

}
