<?php
/**
 * Google Console Class
 *
 * Responsible for processing APIs for Google Console.
 *
 * @since 1.0.0
 * @package SureRank
 */

namespace SureRank\Inc\GoogleSearchConsole;

use SureRank\Inc\Functions\Requests;
use SureRank\Inc\Traits\Get_Instance;

/**
 * APIs Class
 *
 * Responsible for processing APIs for Google Console.
 *
 * @since 1.0.0
 */
class GoogleConsole {

	use Get_Instance;
	/**
	 * Instance object.
	 *
	 * @var self Class Instance.
	 */
	private static $instance = null;

	/**
	 * Get Header.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	public function get_header() {
		$credentials = Auth::get_instance()->get_credentials( null );
		return [
			'Authorization' => 'Bearer ' . ( $credentials['access_token'] ?? '' ),
			'Accept'        => 'application/json',
		];
	}

	/**
	 * Call API
	 *
	 * @since 1.0.0
	 * @param string                                  $endpoint Endpoint.
	 * @param string                                  $method Method.
	 * @param array<string, mixed>|array<int, string> $args Args.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function call_api( $endpoint, $method = 'GET', $args = [] ) {
		if ( ! Auth::get_instance()->auth_check() ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid credentials', 'surerank' ),
				]
			);
		}

		$request_args            = [];
		$request_args['headers'] = $this->get_header();
		$request_args['method']  = $method;

		switch ( strtoupper( $method ) ) {
			case 'POST':
			case 'PUT':
			case 'PATCH':
				$request_args['headers']['Content-Type'] = 'application/json';
				$request_args['body']                    = ! empty( $args ) ? (string) wp_json_encode( $args ) : '{}';
				break;
			case 'GET':
			case 'DELETE':
				if ( ! empty( $args ) ) {
					$endpoint = add_query_arg( $args, $endpoint );
				}
				break;
			default:
				break;
		}

		$response = Requests::request( $endpoint, $request_args );

		if ( is_wp_error( $response ) ) {
			return [
				'error'   => true,
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
			];
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_body    = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response_body, true );

		if ( $response_code >= 400 ) {

			$error         = $decoded_response['error'] ?? [];
			$error_message = $error['message'] ?? __( 'Unknown error', 'surerank' );

			return [
				'error'   => true,
				'message' => $error_message,
				'code'    => $response_code,
			];
		}

		return $decoded_response;
	}
}
