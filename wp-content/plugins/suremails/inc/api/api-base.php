<?php
/**
 * API base.
 *
 * @package SureMails;
 * @since 0.0.1
 */

namespace SureMails\Inc\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Api_Base
 *
 * @since 0.0.1
 */
abstract class Api_Base extends WP_REST_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'suremails/v1';

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
	}
	/**
	 * Get API namespace.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_api_namespace() {
		return $this->namespace;
	}

	/**
	 * Validate the nonce for REST API requests.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return bool|WP_Error True if valid, WP_REST_Response if invalid.
	 */
	public function validate_permission( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'suremails_rest_cannot_access',
				__( 'You do not have permission to perform this action.', 'suremails' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		// Retrieve the nonce from the request header.
		$nonce = $request->get_header( 'X-WP-Nonce' );

		// Check if nonce is null or empty.
		if ( empty( $nonce ) || ! is_string( $nonce ) ) {
			return new WP_Error(
				'suremails_nonce_verification_failed',
				__( 'Nonce is missing.', 'suremails' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'suremails_nonce_verification_failed',
				__( 'Nonce is invalid.', 'suremails' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}
}
