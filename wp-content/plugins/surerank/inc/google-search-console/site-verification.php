<?php
/**
 * Site Verification Class
 *
 * Handles Google Site Verification API operations.
 *
 * @since 1.4.0
 * @package SureRank
 */

namespace SureRank\Inc\GoogleSearchConsole;

use SureRank\Inc\Traits\Get_Instance;

/**
 * Site Verification Class
 *
 * Responsible for handling Google Site Verification API operations.
 *
 * @since 1.4.0
 */
class SiteVerification {

	use Get_Instance;

	/**
	 * Google Site Verification API Base
	 */
	private const GOOGLE_SITE_VERIFICATION_API_BASE = 'https://www.googleapis.com/siteVerification/v1/';

	/**
	 * Verification Token Option Name
	 */
	private const VERIFICATION_TOKEN_OPTION = 'surerank_gsc_verification_token';

	/**
	 * Constructor
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Call Site Verification API
	 *
	 * Makes API calls using GoogleConsole call_api method with site verification specific error handling
	 *
	 * @since 1.4.0
	 * @param string               $endpoint The API endpoint URL.
	 * @param string               $method   HTTP method (GET, POST, PUT, etc.).
	 * @param array<string, mixed> $args     Request arguments.
	 * @return array<string, mixed> API response or error array.
	 */
	public function call_site_verification_api( $endpoint, $method = 'GET', $args = [] ) {
		$result = GoogleConsole::get_instance()->call_api( $endpoint, $method, $args );

		if ( isset( $result['error'] ) && $result['error'] ) {
			$original_error_message = $result['message'] ?? __( 'Unknown error', 'surerank' );
			$response_code          = $result['code'] ?? 0;
			$custom_error_message   = $original_error_message;

			switch ( $response_code ) {
				case 400:
					if ( strpos( $original_error_message, 'already verified' ) !== false ) {
						$custom_error_message = __( 'This site is already verified with Google.', 'surerank' );
					} elseif ( strpos( $original_error_message, 'invalid' ) !== false ) {
						$custom_error_message = __( 'Invalid site URL format for verification. Please check the URL.', 'surerank' );
					}
					break;
				case 401:
					$custom_error_message = __( 'Authentication expired. Please disconnect and reconnect your Google account.', 'surerank' );
					break;
				case 403:
					$custom_error_message = __( 'Access denied. Please disconnect and reconnect your Google account with the necessary permissions.', 'surerank' );
					break;
				case 404:
					$custom_error_message = __( 'Verification resource not found. Please try again.', 'surerank' );
					break;
				case 409:
					$custom_error_message = __( 'Site verification conflict. This site may already be verified by another user.', 'surerank' );
					break;
				case 429:
					$custom_error_message = __( 'Too many verification requests. Please wait a moment and try again.', 'surerank' );
					break;
				case 500:
				case 502:
				case 503:
					$custom_error_message = __( 'Google Site Verification service is temporarily unavailable. Please try again later.', 'surerank' );
					break;
				default:
					if ( empty( $original_error_message ) || $original_error_message === 'Unknown error' ) {
						$custom_error_message = sprintf(
							/* translators: %d: HTTP status code */
							__( 'Site verification error (Code: %d). Please try again.', 'surerank' ),
							$response_code
						);
					}
					break;
			}

			return [
				'error'            => true,
				'message'          => $custom_error_message,
				'original_message' => $original_error_message,
				'code'             => $result['code'] ?? 0,
				'http_code'        => $response_code,
			];
		}

		return $result;
	}

	/**
	 * Get Verification Token
	 *
	 * Gets the HTML tag verification token for a site using Site Verification API
	 *
	 * @since 1.4.0
	 * @param string $site_url The site URL to get verification token for.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_verification_token( $site_url ) {
		$url  = self::GOOGLE_SITE_VERIFICATION_API_BASE . 'token';
		$body = [
			'site'               => [
				'type'       => 'SITE',
				'identifier' => $site_url,
			],
			'verificationMethod' => 'META',
		];

		$result = $this->call_site_verification_api( $url, 'POST', $body );

		if ( isset( $result['error'] ) ) {
			return $result;
		}

		if ( ! isset( $result['token'] ) ) {
			return [
				'error'   => true,
				'message' => __( 'No verification token received', 'surerank' ),
				'code'    => 400,
			];
		}

		// Extract token from HTML meta tag if needed.
		$token = $result['token'];
		if ( strpos( $token, '<meta' ) !== false ) {
			// Extract content attribute from meta tag.
			preg_match( '/content="([^"]*)"/', $token, $matches );
			if ( isset( $matches[1] ) ) {
				$token = $matches[1];
			}
		}

		return [
			'token' => $token,
		];
	}

	/**
	 * Store Verification Token
	 *
	 * Stores the verification token for meta tag injection permanently
	 *
	 * @since 1.4.0
	 * @param string $token The verification token.
	 * @return void
	 */
	public function store_verification_token( $token ) {
		update_option( self::VERIFICATION_TOKEN_OPTION, $token );
	}

	/**
	 * Get Stored Verification Token
	 *
	 * Gets the stored verification token from options
	 *
	 * @since 1.4.0
	 * @return string|false
	 */
	public function get_stored_verification_token() {
		return get_option( self::VERIFICATION_TOKEN_OPTION, false );
	}

	/**
	 * Verify Site
	 *
	 * Verifies a site using Site Verification API
	 * Note: Verification may take 1-2 hours or up to 2 days to complete
	 *
	 * @since 1.4.0
	 * @param string $site_url The site URL to verify.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function verify_site( $site_url ) {
		$url  = self::GOOGLE_SITE_VERIFICATION_API_BASE . 'webResource?verificationMethod=META';
		$body = [
			'site' => [
				'type'       => 'SITE',
				'identifier' => $site_url,
			],
		];

		$result = $this->call_site_verification_api( $url, 'POST', $body );

		if ( isset( $result['error'] ) ) {
			$original_message = $result['original_message'] ?? $result['message'];
			$http_code        = $result['http_code'] ?? $result['code'] ?? 0;

			if ( $http_code === 400 ) {
				$error_details = [];
				if ( isset( $result['original_message'] ) ) {
					$decoded_error = json_decode( $result['original_message'], true );
					if ( is_array( $decoded_error ) && isset( $decoded_error['error'] ) ) {
						$error_details = $decoded_error['error'];
					}
				}

				$error_reason = $error_details['errors'][0]['reason'] ?? '';

				if ( $error_reason === 'badRequest' ) {
					return [
						'pending'          => true,
						'success'          => false,
						'message'          => __( 'Site verification is pending. Google needs time to crawl and verify your site. This may take 1-2 hours or up to 2 days.', 'surerank' ),
						'original_message' => $original_message,
					];
				}
			}

			return $result;
		}

		return [
			'success' => true,
			'message' => __( 'Site verified successfully', 'surerank' ),
		];
	}

	/**
	 * Add Site
	 *
	 * Adds a site to Google Search Console
	 *
	 * @since 1.4.0
	 * @param string $site_url The site URL to add.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function add_site( $site_url ) {
		$url    = Controller::GOOGLE_ANALYTICS_API_BASE . 'sites/' . urlencode( $site_url );
		$result = GoogleConsole::get_instance()->call_api( $url, 'PUT', [] );

		if ( isset( $result['error'] ) && $result['error'] ) {
			return $result;
		}

		return [
			'success' => true,
			'message' => __( 'Site added successfully', 'surerank' ),
		];
	}

	/**
	 * Auto Create and Verify Property
	 *
	 * Creates and verifies a Search Console property following the documented flow
	 * Simplified version that only handles URL-prefix properties since subdomain logic moved to frontend
	 *
	 * @since 1.4.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function auto_create_and_verify_property() {
		return $this->perform_verification( true );
	}

	/**
	 * Verify Existing Property
	 *
	 * Verifies an existing Search Console property that's already added but not verified
	 *
	 * @since 1.4.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function verify_existing_property() {
		return $this->perform_verification( false );
	}

	/**
	 * Perform Verification Process
	 *
	 * Common method to handle verification for both creating new and verifying existing properties
	 *
	 * @since 1.4.0
	 * @param bool $add_site Whether to add the site to Search Console before verification.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	private function perform_verification( $add_site = false ) {
		$property_url = $this->get_site_url();

		// Step 1: Get verification token from Site Verification API.
		$verification_token = $this->get_verification_token( $property_url );
		if ( isset( $verification_token['error'] ) ) {
			return $verification_token;
		}
		// Step 2: Store verification token for meta tag injection.
		$this->store_verification_token( $verification_token['token'] );
		// Step 3: Add site to Search Console if required.
		if ( $add_site ) {
			$add_result = $this->add_site( $property_url );
			if ( isset( $add_result['error'] ) ) {
				return $add_result;
			}
		}

		// Step 4: Verify the property using Site Verification API.
		$verify_result = $this->verify_site( $property_url );
		if ( isset( $verify_result['error'] ) ) {
			return $verify_result;
		}
		// Step 5: Update credentials with the new site URL.
		$credentials             = Auth::get_instance()->get_credentials( null );
		$credentials['site_url'] = $property_url;
		Auth::get_instance()->save_credentials( $credentials );

		// Handle pending verification case.
		if ( isset( $verify_result['pending'] ) && $verify_result['pending'] ) {
			$message = $add_site
				? __( 'Property created successfully. Verification is pending and may take 1-2 hours or up to 2 days.', 'surerank' )
				: __( 'Verification is pending and may take 1-2 hours or up to 2 days.', 'surerank' );
			return [
				'success'  => true,
				'pending'  => true,
				'message'  => $message,
				'site_url' => $property_url,
			];
		}

		$message = $add_site
			? __( 'Property created and verified successfully', 'surerank' )
			: __( 'Property verified successfully', 'surerank' );
		return [
			'success'  => true,
			'message'  => $message,
			'site_url' => $property_url,
		];
	}

	/**
	 * Get Site URL for Verification
	 *
	 * Gets the site URL to use for verification
	 *
	 * @since 1.4.0
	 * @return string
	 */
	private function get_site_url() {
		$site_url = get_site_url();

		return str_replace( 'http://', 'https://', rtrim( $site_url, '/' ) ) . '/';
	}
}
