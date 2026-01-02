<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

use CloudLinux\Imunify\App\Model\Feature;
use CloudLinux\Imunify\App\Model\FeatureType;
use CloudLinux\Imunify\App\Model\ScanData;
use CloudLinux\Imunify\App\Exception\ApiException;

/**
 * Data store implementation that uses PHP files.
 */
class DataStore {

	/**
	 * Data directory name.
	 */
	const DIRECTORY = 'imunify-security';

	/**
	 * Scan data file name.
	 */
	const SCAN_DATA_FILE = 'scan_data.php';

	/**
	 * Authentication file name.
	 */
	const AUTH_FILE = 'auth.php';

	/**
	 * Transient key prefix for error throttling.
	 */
	const ERROR_THROTTLE_PREFIX = 'imunify_security_error_';

	/**
	 * API host.
	 */
	const API_HOST = '127.0.0.1';

	/**
	 * API port.
	 */
	const API_PORT = 11234;

	/**
	 * API endpoint path.
	 */
	const API_ENDPOINT = '/api/v1/rpc';

	/**
	 * API timeout in seconds.
	 */
	const API_TIMEOUT = 30;

	/**
	 * Scan data.
	 *
	 * @var ScanData|null
	 */
	private $scanData = null;

	/**
	 * Data directory location. Default is WP_CONTENT_DIR.
	 *
	 * @var string
	 */
	private $dataDirectoryLocation = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dataDirectoryLocation = WP_CONTENT_DIR;
	}

	/**
	 * Checks if scan data file is available.
	 *
	 * @return bool
	 */
	public function isDataAvailable() {
		return $this->isDataFileAvailable( self::SCAN_DATA_FILE );
	}

	/**
	 * Checks if data file is available.
	 *
	 * @param string $filename The name of the file.
	 *
	 * @return bool
	 */
	public function isDataFileAvailable( $filename ) {
		$filepath = $this->getDataFilePath( $filename );
		return file_exists( $filepath ) && is_readable( $filepath );
	}

	/**
	 * Changes data directory and clears the data to make sure it's reloaded when requested again.
	 *
	 * @param string $directory The new directory.
	 *
	 * @return void
	 */
	public function changeDataDirectory( $directory ) {
		$this->dataDirectoryLocation = $directory;
		$this->scanData              = null;
	}

	/**
	 * Get the base directory path for data files
	 *
	 * @return string
	 */
	private function getDataDirectory() {
		return $this->dataDirectoryLocation . DIRECTORY_SEPARATOR . self::DIRECTORY;
	}

	/**
	 * Get the full path to a data file
	 *
	 * @param string $filename The name of the file.
	 *
	 * @return string
	 */
	private function getDataFilePath( $filename ) {
		return trailingslashit( $this->getDataDirectory() ) . $filename;
	}

	/**
	 * Retrieves the scan data.
	 *
	 * If not already loaded, it will load it from the file.
	 *
	 * @return ScanData|null
	 */
	public function getScanData() {
		if ( ! $this->scanData ) {
			$rawData = $this->load( self::SCAN_DATA_FILE );
			if ( ! $rawData ) {
				return null;
			}

			$this->scanData = ScanData::fromArray( $rawData );
		}
		return $this->scanData;
	}

	/**
	 * Get list of features.
	 *
	 * @return \CloudLinux\Imunify\App\Model\Feature[]
	 */
	public function getFeatures() {

		$scanData = $this->getScanData();
		$config   = $scanData ? $scanData->getConfig() : array();

		return array(
			Feature::fromType(
				FeatureType::MALWARE_SCANNING,
				$config
			),
			Feature::fromType(
				FeatureType::MALWARE_CLEANUP,
				$config
			),
			Feature::fromType(
				FeatureType::PROACTIVE_DEFENCE,
				$config
			),
		);
	}

	/**
	 * Loads data from given file.
	 *
	 * @param string $filename The name of the file.
	 *
	 * @return array|null
	 */
	private function load( $filename ) {
		if ( ! $this->isDataFileAvailable( $filename ) ) {
			return null;
		}

		$filepath = $this->getDataFilePath( $filename );

		/**
		 * PHP is able to catch parsing errors since version 7.0. This is a workaround that allows to catch parsing
		 * errors if supported while keeping compatibility with PHP 5.6 that does not support Throwable.
		 */
		if ( interface_exists( 'Throwable' ) ) {
			try {
				$rawData = include $filepath;
				return $this->processRawDataFromFile( $rawData, $filepath );
			} catch ( \Throwable $t ) {
				$this->processFileLoadingError( $filename, $t );
				return null;
			}
		} else {
			try {
				$rawData = include $filepath;
				return $this->processRawDataFromFile( $rawData, $filepath );
			} catch ( \Exception $e ) {
				$this->processFileLoadingError( $filename, $e );
				return null;
			}
		}
	}

	/**
	 * Processes the raw data from the file.
	 * This method is used to convert the raw data into a ScanData object.
	 * If the data is not valid, it will log an error and return null.
	 *
	 * @param mixed  $rawData   The raw data from the file.
	 * @param string $filepath The path to the file.
	 *
	 * @return array|null
	 */
	private function processRawDataFromFile( $rawData, $filepath ) {
		if ( ! is_array( $rawData ) ) {
			do_action(
				'imunify_security_set_error',
				E_WARNING,
				'File scan_data.php returned unexpected data',
				__FILE__,
				__LINE__,
				array(
					'file' => $filepath,
					'data' => var_export( $rawData, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				)
			);
			return null;
		}

		return $rawData;
	}

	/**
	 * Processes the error that occurred while loading data from a file.
	 *
	 * @param string                $filename The name of the file.
	 * @param \Throwable|\Exception $e The exception.
	 *
	 * @return void
	 */
	private function processFileLoadingError( $filename, $e ) {
		$this->handleError(
			$filename . ' file loading failed with error  ' . $e->getMessage(),
			'file_loading_failed',
			array(
				'file'       => $filename,
				'error_type' => get_class( $e ),
			),
			false
		);
	}

	/**
	 * Handles errors by logging them with a unique identifier.
	 * Errors are throttled to once per hour per error code.
	 * Optionally throws an ApiException.
	 *
	 * @param string $message The error message.
	 * @param string $errorCode The unique error identifier.
	 * @param array  $context Additional context data.
	 * @param bool   $throwException Whether to throw an ApiException. Default true.
	 *
	 * @return void
	 * @throws ApiException When $throwException is true.
	 *
	 * @since 2.0.0
	 */
	public function handleError( $message, $errorCode, $context = array(), $throwException = true ) {
		$transientKey = self::ERROR_THROTTLE_PREFIX . $errorCode;

		// Check if this error was already reported in the last hour.
		if ( ! get_transient( $transientKey ) ) {

			// Set transient to prevent this error from being reported again for an hour.
			set_transient( $transientKey, true, HOUR_IN_SECONDS );

			do_action(
				'imunify_security_set_error',
				E_WARNING,
				$message,
				__FILE__,
				__LINE__,
				array(
					'fingerprint' => array( $errorCode ),
					'context'     => $context,
				)
			);
		}

		if ( $throwException ) {
			throw new ApiException( $message, $errorCode );
		}
	}

	/**
	 * Load authentication token from the auth file.
	 *
	 * @return string|null The token or null if not found.
	 */
	private function loadCredentials() {
		if ( ! $this->isDataFileAvailable( self::AUTH_FILE ) ) {
			return null;
		}

		$auth = $this->load( self::AUTH_FILE );
		if ( ! $auth || ! isset( $auth['token'] ) ) {
			return null;
		}

		return $auth['token'];
	}

	/**
	 * Load data for the given commands and params.
	 *
	 * @param array $commands List of agent commands.
	 * @param array $params   List of parameters.
	 *
	 * @return array
	 * @throws ApiException When the API request fails.
	 */
	public function loadData( $commands, $params ) {
		$token = $this->loadCredentials();
		if ( ! $token ) {
			$this->handleError(
				'Failed to load API credentials',
				'api_credentials_load_failed'
			);
		}

		// Remove the JWT token from the params if it exists. It is passed in headers.
		if ( isset( $params['jwt'] ) ) {
			unset( $params['jwt'] );
		}

		$requestData = array(
			'command' => $commands,
			'params'  => empty( $params ) ? new \stdClass() : $params, // Use stdClass for empty params to avoid JSON encoding issues.
		);

		$apiUrl = $this->getApiUrl();

		$response = wp_remote_post(
			$apiUrl,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => \wp_json_encode( $requestData ),
				'timeout' => self::API_TIMEOUT,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->handleError(
				'API request failed: ' . $response->get_error_message(),
				'api_request_failed',
				array( 'error' => $response->get_error_message() )
			);
		}

		$httpCode = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $httpCode ) {
			$this->handleError(
				'API request failed with status code: ' . $httpCode,
				'api_request_status_error',
				array( 'status_code' => $httpCode )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->handleError(
				'Failed to parse API response: ' . json_last_error_msg(),
				'api_response_parse_error',
				array( 'error' => json_last_error_msg() )
			);
		}

		// Check that the response contains the expected structure (messages and result).
		if ( ! isset( $data['messages'] ) || ! isset( $data['result'] ) ) {
			$this->handleError(
				'Invalid API response structure',
				'api_response_structure_error',
				array(
					'response' => $data,
					'commands' => $commands,
					'params'   => $params,
				)
			);
		}

		if ( ! isset( $data['data'] ) ) {
			$data['data'] = array();
		}

		$command = implode( ' ', $commands );
		$data    = $this->processApiResponseData( $data, $command );

		return $data;
	}

	/**
	 * Get the username from the data.
	 *
	 * The username is retrieved from the scan data.
	 *
	 * @return string Username.
	 */
	public function getUsername() {
		$scanData = $this->getScanData();
		if ( $scanData ) {
			return $scanData->getUsername();
		}
		return '';
	}

	/**
	 * Get the API URL.
	 *
	 * This method constructs the API URL based on the defined constants or defaults.
	 *
	 * @return string The API URL.
	 *
	 * @since 2.0.0
	 */
	private function getApiUrl() {
		if ( defined( 'IMUNIFY_SECURITY_API_URL' ) && ! empty( IMUNIFY_SECURITY_API_URL ) ) {
			return IMUNIFY_SECURITY_API_URL;
		}

		return sprintf(
			'http://%s:%d%s',
			self::API_HOST,
			self::API_PORT,
			self::API_ENDPOINT
		);
	}

	/**
	 * Process API response data by applying command-specific modifications.
	 *
	 * This method handles post-processing of API response data, including:
	 * - Adding upgrade button for permissions list command
	 * - Modifying proactive defense settings for config show command
	 * - Injecting WordPress plugin version for get-package-versions command
	 * - Merging license data from scan data
	 *
	 * @param array  $data    The API response data to process.
	 * @param string $command The command string that was executed.
	 *
	 * @return array The processed data.
	 *
	 * @since 2.0.0
	 */
	private function processApiResponseData( $data, $command ) {

		if ( 'config show' === $command ) {
			// Add upgrade button to the permissions list.
			if ( isset( $data['data']['items']['PERMISSIONS'] ) && is_array( $data['data']['items']['PERMISSIONS'] ) ) {
				$data['data']['items']['PERMISSIONS']['upgrade_button'] = true;
			}
			// Disable user override for proactive defense in config show.
			if ( isset( $data['data']['items']['PERMISSIONS']['user_override_proactive_defense'] ) ) {
				$data['data']['items']['PERMISSIONS']['user_override_proactive_defense'] = false;
			}
		}

		// Update versioning information to include the WordPress plugin version.
		if ( 'get-package-versions' === $command ) {
			if ( isset( $data['data']['items'] ) && is_array( $data['data']['items'] ) ) {
				$data['data']['items']['imunify-wp-plugin'] = IMUNIFY_SECURITY_VERSION;
			}
		}

		// Inject missing license data to the response from scan data.
		if ( array_key_exists( 'license', $data['data'] ) && is_array( $data['data']['license'] ) ) {
			$scanData = $this->getScanData();
			if ( $scanData ) {
				$license = $scanData->getLicense();
				if ( ! empty( $license ) ) {
					$data['data']['license'] = array_merge( $license, $data['data']['license'] );
				}
			}
		}

		return $data;
	}
}
