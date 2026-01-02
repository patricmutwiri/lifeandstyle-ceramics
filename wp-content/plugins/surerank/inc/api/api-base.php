<?php
/**
 * API base.
 *
 * @package SureRank;
 * @since 1.0.0
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Sanitize;
use SureRank\Inc\Meta_Variables\Site;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Api_Base
 *
 * @since 1.0.0
 */
abstract class Api_Base extends WP_REST_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'surerank/v1';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}
	/**
	 * Get API namespace.
	 *
	 * @since 1.0.0
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
				'surerank_rest_cannot_access',
				__( 'You do not have permission to perform this action.', 'surerank' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		// Retrieve the nonce from the request header.
		$nonce = $request->get_header( 'X-WP-Nonce' );

		// Check if nonce is null or empty.
		if ( empty( $nonce ) || ! is_string( $nonce ) ) {
			return new WP_Error(
				'surerank_nonce_verification_failed',
				__( 'Nonce is missing.', 'surerank' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'surerank_nonce_verification_failed',
				__( 'Nonce is invalid.', 'surerank' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Get favicon image URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_favicon() {
		return esc_url( get_site_icon_url( 16 ) );
	}

	/**
	 * Get site variables
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_site_variables() {
		$site           = Site::get_instance();
		$site_variables = $site->get_all_values();
		$variables      = [];

		// Add favicon icon if variable is available and should be a array.
		if ( ! empty( $site_variables ) && is_array( $site_variables ) ) {

			// Keep in key and array format.
			foreach ( $site_variables as $key => $value ) {
				// Verify that value should be an array.

				if ( ! isset( $value['value'] ) ) {
					continue;
				}
				$variables[ $key ] = $value['value'];
			}

			$variables['favicon']       = $this->get_favicon();
			$variables['title']         = __( 'Sample Post', 'surerank' );
			$variables['current_year']  = gmdate( 'Y' );
			$variables['current_month'] = gmdate( 'F' );
		} else {
			$variables = [];
		}

		$variables['page'] = Helper::format_paged_info( 2, 5 );
		return $variables;
	}

	/**
	 * Sanitize object data
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|array<int, string> $data Data to sanitize.
	 * @return array<string, mixed>|array<int, string>
	 */
	public function sanitize_array_data( $data ) {
		return Sanitize::array_deep( [ Sanitize::class, 'sanitize_with_placeholders' ], $data );
	}
}
