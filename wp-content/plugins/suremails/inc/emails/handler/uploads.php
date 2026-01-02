<?php
/**
 * Uploads.php
 *
 * Provides methods for handling file operations for email attachments and uploads.
 *
 * @package SureMails\Inc\Utilities
 */

namespace SureMails\Inc\Emails\Handler;

use SureMails\Inc\Traits\Instance;
use WP_Error;

/**
 * Class Uploads
 *
 * Provides methods for handling file operations for email attachments and uploads.
 */
class Uploads {

	use Instance;

	/**
	 * Directory name for SureMails uploads.
	 *
	 * @since 1.5.0
	 */
	public const BASE_FOLDER = 'suremails';

	/**
	 * Process and update a list of file attachments.
	 *
	 * @since 1.5.0
	 *
	 * @param array $attachments List of attachment information.
	 * @return array Modified attachment list.
	 */
	public function handle_attachments( $attachments ) {
		return array_map(
			function ( $attachment ) {
				// Unpack our attachment parameters.
				$path      = $attachment;
				$filename  = wp_basename( $path );
				$is_string = false;

				$new_path = $this->handle_single_attachment( $path, $filename, $is_string );
				if ( ! empty( $new_path ) ) {
					$attachment = $new_path;
				}

				return $attachment;
			},
			$attachments
		);
	}

	/**
	 * Retrieve the base SureMails uploads directory.
	 *
	 * @since 1.5.0
	 *
	 * @return array|WP_Error Array with 'path' and 'url' or an error.
	 */
	public static function get_suremails_base_dir() {
		$upload_info = wp_upload_dir();
		if ( ! empty( $upload_info['error'] ) ) {
			return new WP_Error( 'suremails_upload_dir_error', $upload_info['error'] );
		}

		$folder = self::BASE_FOLDER;

		$base_dir = realpath( $upload_info['basedir'] );
		if ( $base_dir === false ) {
			return new WP_Error( 'suremails_upload_dir_error', __( 'Invalid upload base directory.', 'suremails' ) );
		}
		$base   = trailingslashit( $base_dir ) . $folder;
		$custom = apply_filters( 'suremails_uploads_base_dir', $base );

		if ( wp_is_writable( $custom ) ) {
			$base = $custom;
		}

		if ( ! file_exists( $base ) && ! wp_mkdir_p( $custom ) ) {
			return new WP_Error(
				'suremails_upload_dir_create_failed',
				// translators: %s is the directory path.
				sprintf( __( 'Cannot create directory %s. Check parent directory permissions.', 'suremails' ), esc_html( $base ) )
			);
		}

		if ( ! wp_is_writable( $custom ) ) {
			return new WP_Error(
				'suremails_upload_dir_not_writable',
				// translators: %s is the directory path.
				sprintf( __( 'Directory %s is not writable.', 'suremails' ), esc_html( $base ) )
			);
		}

		return [
			'path' => $base,
			'url'  => trailingslashit( $upload_info['baseurl'] ) . $folder,
		];
	}

	/**
	 * Generate an .htaccess file to secure the uploads folder.
	 *
	 * @since 1.5.0
	 *
	 * @param string|null $folder_path Optional. Directory path. Defaults to base directory.
	 * @return bool True on success, false on failure.
	 */
	public static function generate_htaccess_file( $folder_path = null ) {
		if ( null === $folder_path ) {
			$base_dir = self::get_suremails_base_dir();
			if ( is_wp_error( $base_dir ) ) {
				return false;
			}
			$folder_path = $base_dir['path'];
		}

		if ( ! is_dir( $folder_path ) || is_link( $folder_path ) ) {
			return false;
		}

		$ht_file = wp_normalize_path( trailingslashit( $folder_path ) . '.htaccess' );
		$content = apply_filters(
			'suremails_htaccess_content',
			'<Files *>
  SetHandler none
  SetHandler default-handler
  RemoveHandler .cgi .php .php3 .php4 .php5 .php7 .phtml .phar .phps .pht .phpt .inc .pl .py .pyc .pyo
  RemoveType .cgi .php .php3 .php4 .php5 .php7 .phtml .phar .phps .pht .phpt .inc .pl .py .pyc .pyo
</Files>
<IfModule mod_php5.c>
  php_flag engine off
</IfModule>
<IfModule mod_php7.c>
  php_flag engine off
</IfModule>
<IfModule mod_php8.c>
  php_flag engine off
</IfModule>
<IfModule headers_module>
  Header set X-Robots-Tag "noindex"
</IfModule>'
		);

		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		return insert_with_markers( $ht_file, 'SureMails', $content );
	}

	/**
	 * Create an empty index.html file in the specified folder if missing.
	 *
	 * @since 1.5.0
	 *
	 * @param string|null $folder_path Optional. Directory path. Defaults to base directory.
	 * @return bool True on success, false on failure.
	 */
	public static function generate_index_html( $folder_path = null ) {
		if ( null === $folder_path ) {
			$base_dir = self::get_suremails_base_dir();
			if ( is_wp_error( $base_dir ) ) {
				return false;
			}
			$folder_path = $base_dir['path'];
		}

		if ( ! is_dir( $folder_path ) || is_link( $folder_path ) ) {
			return false;
		}

		$index = wp_normalize_path( trailingslashit( $folder_path ) . 'index.html' );
		if ( file_exists( $index ) ) {
			return false;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		$result = $wp_filesystem->put_contents( $index, '' );
		return $result !== false;
	}

	/**
	 * Generate web.config file for IIS servers.
	 *
	 * @since 1.9.1
	 *
	 * @param string|null $folder_path Optional. Directory path. Defaults to base directory.
	 * @return bool True on success, false on failure.
	 */
	public static function generate_webconfig_file( $folder_path = null ) {
		if ( null === $folder_path ) {
			$base_dir = self::get_suremails_base_dir();
			if ( is_wp_error( $base_dir ) ) {
				return false;
			}
			$folder_path = $base_dir['path'];
		}

		if ( ! is_dir( $folder_path ) || is_link( $folder_path ) ) {
			return false;
		}

		$dest = wp_normalize_path( trailingslashit( $folder_path ) . 'web.config' );
		if ( file_exists( $dest ) ) {
			return false;
		}

		$content = apply_filters(
			'suremails_web_config_content',
			'<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <security>
      <requestFiltering>
        <hiddenSegments>
          <add segment="attachments" />
        </hiddenSegments>
        <fileExtensions allowUnlisted="true">
          <add fileExtension=".php" allowed="false" />
          <add fileExtension=".phtml" allowed="false" />
          <add fileExtension=".php3" allowed="false" />
          <add fileExtension=".php4" allowed="false" />
          <add fileExtension=".php5" allowed="false" />
          <add fileExtension=".php7" allowed="false" />
          <add fileExtension=".phar" allowed="false" />
          <add fileExtension=".phps" allowed="false" />
          <add fileExtension=".pht" allowed="false" />
          <add fileExtension=".phpt" allowed="false" />
          <add fileExtension=".inc" allowed="false" />
        </fileExtensions>
      </requestFiltering>
    </security>
    <handlers>
      <!-- Explicitly remove PHP handler for this directory -->
      <remove name="PHP_via_FastCGI" />
      <remove name="php-7.4.33" />
      <remove name="php" />
    </handlers>
  </system.webServer>
</configuration>'
		);

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$result = $wp_filesystem->put_contents( $dest, $content );
		return $result !== false;
	}

	/**
	 * Generate index.php file to block direct access.
	 *
	 * @since 1.9.1
	 *
	 * @param string|null $folder_path Optional. Directory path. Defaults to base directory.
	 * @return bool True on success, false on failure.
	 */
	public static function generate_index_php( $folder_path = null ) {
		if ( null === $folder_path ) {
			$base_dir = self::get_suremails_base_dir();
			if ( is_wp_error( $base_dir ) ) {
				return false;
			}
			$folder_path = $base_dir['path'];
		}

		if ( ! is_dir( $folder_path ) || is_link( $folder_path ) ) {
			return false;
		}

		$dest = wp_normalize_path( trailingslashit( $folder_path ) . 'index.php' );
		if ( file_exists( $dest ) ) {
			return false;
		}

		$content = apply_filters(
			'suremails_index_php_content',
			'<?php
// Silence is golden.
http_response_code( 403 );
exit;
'
		);

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$result = $wp_filesystem->put_contents( $dest, $content );
		return $result !== false;
	}

	/**
	 * Generate .user.ini file for PHP-FPM configurations.
	 *
	 * @since 1.9.1
	 *
	 * @param string|null $folder_path Optional. Directory path. Defaults to base directory.
	 * @return bool True on success, false on failure.
	 */
	public static function generate_user_ini_file( $folder_path = null ) {
		if ( null === $folder_path ) {
			$base_dir = self::get_suremails_base_dir();
			if ( is_wp_error( $base_dir ) ) {
				return false;
			}
			$folder_path = $base_dir['path'];
		}

		if ( ! is_dir( $folder_path ) || is_link( $folder_path ) ) {
			return false;
		}

		$dest = wp_normalize_path( trailingslashit( $folder_path ) . '.user.ini' );
		if ( file_exists( $dest ) ) {
			return false;
		}

		$content = apply_filters(
			'suremails_user_ini_content',
			'; Disable dangerous PHP functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
; Disable PHP execution
engine = Off
; Prevent auto-prepend/append attacks
auto_prepend_file =
auto_append_file ='
		);

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$result = $wp_filesystem->put_contents( $dest, $content );
		return $result !== false;
	}

	/**
	 * Generate all protection files for multi-server compatibility.
	 *
	 * @since 1.9.1
	 *
	 * @return bool True if all succeeded, false otherwise.
	 */
	public static function generate_protection_files() {
		$base_dir = self::get_suremails_base_dir();
		if ( is_wp_error( $base_dir ) ) {
			return false;
		}

		$results = [];

		// Protect base directory.
		$results[] = self::generate_htaccess_file();
		$results[] = self::generate_webconfig_file();
		$results[] = self::generate_user_ini_file();
		$results[] = self::generate_index_html();
		$results[] = self::generate_index_php();

		// Protect attachments subdirectory (where files are actually uploaded).
		$attachments_dir = trailingslashit( $base_dir['path'] ) . 'attachments';
		// Create attachments directory if it doesn't exist.
		if ( ! is_dir( $attachments_dir ) ) {
			wp_mkdir_p( $attachments_dir );
		}

		if ( is_dir( $attachments_dir ) ) {
			// Add protection files for defense in depth.
			$results[] = self::generate_htaccess_file( $attachments_dir );
			$results[] = self::generate_webconfig_file( $attachments_dir );
			$results[] = self::generate_user_ini_file( $attachments_dir );
			$results[] = self::generate_index_html( $attachments_dir );
			$results[] = self::generate_index_php( $attachments_dir );
		}

		return ! in_array( false, $results, true );
	}

	/**
	 * Process a single attachment file by obfuscating its path and saving its data.
	 *
	 * @since 1.5.0
	 *
	 * @param string $path       The original file path or content.
	 * @param string $filename   The original file name.
	 * @param bool   $is_string  Indicates if the attachment is provided as a string.
	 * @return string|false New file path if stored successfully; false otherwise.
	 */
	private function handle_single_attachment( $path, $filename = '', $is_string = false ) {
		$content = $this->fetch_attachment_content( $path, $is_string );
		if ( $content === false ) {
			return false;
		}

		// When not a string-based attachment, use the original file name if none provided.
		if ( ! $is_string && $filename === '' ) {
			$filename = wp_basename( $path );
		}

		$filename = sanitize_file_name( $filename );
		$stored   = $this->save_file( $content, $filename );
		return empty( $stored ) ? $path : $stored;
	}

	/**
	 * Retrieve the content from a given file or string.
	 *
	 * @since 1.5.0
	 *
	 * @param string $source    The file path or the file content.
	 * @param bool   $is_string Whether the source is a string of content.
	 * @return string|false File content or false on failure.
	 */
	private function fetch_attachment_content( $source, $is_string ) {
		if ( ! $is_string ) {
			if ( ! is_readable( $source ) ) {
				return false;
			}
			return file_get_contents( $source );
		}
		return $source;
	}

	/**
	 * Save file content to a new location inside the SureMails uploads directory.
	 *
	 * @since 1.5.0
	 *
	 * @param string $content       The file data.
	 * @param string $original_name The original file name.
	 * @return string|false New file path on success or false on failure.
	 */
	private function save_file( $content, $original_name ) {
		$upload_dir = $this->get_uploads_folder();

		if ( is_wp_error( $upload_dir ) ) {
			return false;
		}

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}

		// Get the extension from the original file name.
		$extension = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );

		if ( ! $this->is_allowed_file_type( $extension, $content ) ) {
			return false;
		}

		$hash = hash( 'md5', $content );

		$random_suffix = wp_generate_password( 12, false );
		$new_name      = substr( $hash, 0, 16 ) . '-' . $random_suffix . '-' . basename( $original_name );

		$upload_dir = trailingslashit( $upload_dir );
		$new_path   = $upload_dir . $new_name;

		if ( is_file( $new_path ) ) {
			return $new_path;
		}

		// Ensure the upload directory is writable using the WP helper.
		if ( ! wp_is_writable( $upload_dir ) ) {
			return false;
		}

		// Initialize the WP Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$result = $wp_filesystem->put_contents( $new_path, $content, FS_CHMOD_FILE );
		return $result ? $new_path : false;
	}

	/**
	 * Get the SureMails-specific uploads folder.
	 *
	 * @since 1.5.0
	 *
	 * @return string|WP_Error The absolute folder path or error.
	 */
	private function get_uploads_folder() {
		$base = self::get_suremails_base_dir();
		if ( is_wp_error( $base ) ) {
			return $base;
		}
		return trailingslashit( trailingslashit( $base['path'] ) . 'attachments' );
	}

	/**
	 * Validate file type against whitelist and MIME type.
	 *
	 * @since 1.9.1
	 *
	 * @param string $extension File extension.
	 * @param string $content   File content for MIME type detection.
	 * @return bool True if file type is allowed, false otherwise.
	 */
	private function is_allowed_file_type( $extension, $content ) {
		$allowed_extensions = apply_filters(
			'suremails_allowed_file_extensions',
			[
				'jpg',
				'jpeg',
				'png',
				'gif',
				'webp',
				'bmp',
				'ico',
				'pdf',
				'txt',
				'csv',
				'rtf',
			]
		);

		if ( empty( $extension ) ) {
			return false;
		}

		if ( ! in_array( $extension, $allowed_extensions, true ) ) {
			return false;
		}

		if ( ! class_exists( 'finfo' ) ) {
			return false;
		}

		$finfo = new \finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->buffer( $content );

		$allowed_mimes = apply_filters(
			'suremails_allowed_mime_types',
			[
				'image/jpeg',
				'image/jpg',
				'image/png',
				'image/gif',
				'image/webp',
				'image/bmp',
				'image/x-ms-bmp',
				'image/x-icon',
				'image/vnd.microsoft.icon',
				'application/pdf',
				'text/plain',
				'text/csv',
				'text/rtf',
				'application/rtf',
			]
		);

		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			return false;
		}

		$mime_extension_map = [
			'image/jpeg'               => [ 'jpg', 'jpeg' ],
			'image/jpg'                => [ 'jpg', 'jpeg' ],
			'image/png'                => [ 'png' ],
			'image/gif'                => [ 'gif' ],
			'image/webp'               => [ 'webp' ],
			'image/bmp'                => [ 'bmp' ],
			'image/x-ms-bmp'           => [ 'bmp' ],
			'image/x-icon'             => [ 'ico' ],
			'image/vnd.microsoft.icon' => [ 'ico' ],
			'application/pdf'          => [ 'pdf' ],
			'text/plain'               => [ 'txt' ],
			'text/csv'                 => [ 'csv' ],
			'text/rtf'                 => [ 'rtf' ],
			'application/rtf'          => [ 'rtf' ],
		];

		if ( isset( $mime_extension_map[ $mime ] ) ) {
			if ( ! in_array( $extension, $mime_extension_map[ $mime ], true ) ) {
				return false;
			}
		}

		return true;
	}
}
