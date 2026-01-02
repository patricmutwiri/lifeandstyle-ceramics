<?php
/**
 * Helper
 *
 * @package SureMails\Emails
 * @since 1.2.0
 */

namespace SureMails\Inc\Emails;

use Exception;
use SureMails\Inc\Emails\Handler\ProcessEmailData;
use SureMails\Inc\Emails\Handler\Uploads;
use SureMails\Inc\Utils\LogError;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper
 * This class will handle all helper functions.
 *
 * @since 1.2.0
 */
class ProviderHelper {

	/**
	 * Get Attachment details.
	 *
	 * @param string $attachment Attachment path.
	 * @since 1.2.0
	 * @return array<string,string|false>|false
	 */
	public static function get_attachment( $attachment ) {
		$process_email_data = ProcessEmailData::instance();
		$file               = false;
		$file_type          = '';
		$file_name          = '';
		$id                 = '';
		$extension          = '';
		$file_contents      = '';
		try {
			if ( is_file( $attachment ) && is_readable( $attachment ) ) {
				$file_name     = $process_email_data->get_attachment_name( $attachment );
				$file          = file_get_contents( $attachment );
				$file_contents = $file;
				$mime_type     = mime_content_type( $attachment );
				if ( $mime_type !== false ) {
					$file_type = str_replace( ';', '', trim( $mime_type ) );
				}
				$id        = wp_hash( $attachment );
				$extension = pathinfo( $attachment, PATHINFO_EXTENSION );
			}
		} catch ( Exception $e ) {
			$file = false;
		}

		if ( $file === false ) {
			return false;
		}

		return [
			'type'      => $file_type,
			'name'      => $file_name,
			'blob'      => base64_encode( $file ),
			'id'        => $id,
			'extension' => $extension,
			'content'   => $file_contents,
		];
	}

	/**
	 * Prepare address param.
	 *
	 * @since 1.2.0
	 *
	 * @param array $address Address array.
	 * @return array|string
	 */
	public static function address_format( $address ) {
		$email  = $address['email'] ?? '';
		$name   = $address['name'] ?? '';
		$result = $email;
		if ( ! empty( $name ) ) {
			$result = "{$name} <{$email}>";
		}
		return $result;
	}

	/**
	 * Get the attachments folder path.
	 *
	 * This function retrieves the base folder for email attachments
	 * using the Uploads handler and returns the full attachments folder path.
	 *
	 * @since 1.5.0
	 * @return string The path to the attachments folder.
	 */
	public static function get_attachments_folder() {
		$base_folder = Uploads::get_suremails_base_dir();
		if ( ! is_wp_error( $base_folder ) && isset( $base_folder['path'] ) ) {
			$base_folder = $base_folder['path'];
		} else {
			$base_folder = '';
		}
		// Ensure trailing slash and append attachments directory.
		return trailingslashit( $base_folder ) . 'attachments/';
	}

	/**
	 * Delete unused attachments.
	 *
	 * This function loops through the list of attachments to delete and, if an
	 * attachment is not present in the retained attachments array, it deletes the file.
	 *
	 * @param array $attachments_to_delete List of attachments that could be deleted.
	 * @param array $attachments_kept      List of attachments that are still in use.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function delete_unused_attachments( array $attachments_to_delete, array $attachments_kept ) {
		$attachments_folder = self::get_attachments_folder();

		// If the folder path is empty, there is nothing to do.
		if ( empty( $attachments_folder ) ) {
			return;
		}

		// Ensure the WP Filesystem is available.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		// Loop through each attachment and delete if not kept.
		foreach ( $attachments_to_delete as $attachment ) {
			if ( ! in_array( $attachment, $attachments_kept, true ) ) {
				$file_path = $attachments_folder . $attachment;
				if ( $wp_filesystem->exists( $file_path ) ) {
					if ( ! $wp_filesystem->delete( $file_path ) ) {
						// translators: %s is the file path.
						LogError::instance()->log_error( sprintf( __( 'Unable to remove attachment file: %s', 'suremails' ), $file_path ) );
					}
				}
			}
		}
	}

	/**
	 * Build attachment LIKE query conditions.
	 *
	 * This function accepts an array of attachment names and builds an array
	 * of conditions to be used in a query where clause.
	 *
	 * @param array $attachments List of attachments.
	 * @since 1.5.0
	 * @return array The conditions array.
	 */
	public static function build_attachment_like_conditions( array $attachments ) {
		$where = [];
		$first = true;
		foreach ( $attachments as $attachment ) {
			if ( empty( $attachment ) ) {
				continue;
			}
			if ( $first ) {
				$where['attachments LIKE'] = "%{$attachment}%";
				$first                     = false;
			} else {
				$where['OR attachments LIKE'] = "%{$attachment}%";
			}
		}
		return $where;
	}

	/**
	 * Extract log IDs and unique attachments from an array of logs.
	 *
	 * This function loops through the provided logs and extracts all log IDs
	 * and attachments. Attachments are merged into a unique list.
	 *
	 * @param array $logs Array of logs.
	 * @since  1.5.0
	 * @return array {
	 *     @type array $log_ids Array of log IDs.
	 *     @type array $attachments Array of unique attachments.
	 * }
	 */
	public static function extract_log_data( array $logs ) {
		$log_ids     = [];
		$attachments = [];
		if ( is_array( $logs ) ) {
			foreach ( $logs as $log ) {
				if ( ! empty( $log['id'] ) ) {
					$log_ids[] = $log['id'];
				}
				if ( ! empty( $log['attachments'] ) ) {
					if ( is_array( $log['attachments'] ) ) {
						$attachments = array_merge( $attachments, $log['attachments'] );
					} else {
						$attachments[] = $log['attachments'];
					}
				}
			}
		}
		return [
			'log_ids'     => array_unique( $log_ids ),
			'attachments' => array_unique( $attachments ),
		];
	}

	/**
	 * Check if the content type is HTML.
	 *
	 * This function checks if the provided content type indicates that the email
	 * is in HTML format.
	 *
	 * @param string $content_type The content type to check.
	 * @since 1.8.0
	 * @return bool True if the content type is HTML, false otherwise.
	 */
	public static function is_html( $content_type ) {
		// Check if the content type is HTML.
		return strpos( strtolower( $content_type ), 'text/html' ) !== false;
	}
}
