<?php
/**
 * DeleteConnection class
 *
 * Handles the REST API endpoint for deleting a connection.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class DeleteConnection
 *
 * Handles the `/delete-connection` REST API endpoint.
 */
class DeleteConnection extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/delete-connection';

	/**
	 * Register API routes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base,
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_connection' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'type'       => [
						'required' => true,
						'type'     => 'string',
					],
					'from_email' => [
						'required' => true,
						'type'     => 'string',
					],
					'id'         => [
						'required' => true,
						'type'     => 'string',
					],
				],
			]
		);
	}

	/**
	 * Delete a connection.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return WP_REST_Response The REST API response.
	 */
	public function delete_connection( $request ) {
		$connection_type = sanitize_text_field( $request->get_param( 'type' ) );
		$from_email      = sanitize_email( $request->get_param( 'from_email' ) );
		$connection_id   = sanitize_text_field( $request->get_param( 'id' ) );
		$options         = Settings::instance()->get_raw_settings();

		// Ensure 'connections' is an associative array.
		if ( ! isset( $options['connections'] ) || ! is_array( $options['connections'] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'No connections found.', 'suremails' ),
				],
				404
			);
		}

		// Check if the connection exists.
		if ( ! isset( $options['connections'][ $connection_id ] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Connection not found.', 'suremails' ),
				],
				404
			);
		}

		$connection = $options['connections'][ $connection_id ];

		// Verify the connection attributes.
		if ( $connection['type'] !== $connection_type || $connection['from_email'] !== $from_email ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Connection details do not match.', 'suremails' ),
				],
				400
			);
		}

		// Remove the connection.
		unset( $options['connections'][ $connection_id ] );

		// Handle default and fallback connections.
		$options = $this->handle_default_and_fallback_connections( $options, $connection );

		// Update the connections option.
		update_option( SUREMAILS_CONNECTIONS, $options );

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Connection deleted successfully.', 'suremails' ),
			],
			200
		);
	}

	/**
	 * Handle default and fallback connections after deletion.
	 *
	 * @param array $options     The connections options array (passed by reference).
	 * @param array $deleted_conn The deleted connection's data.
	 * @return array
	 */
	private function handle_default_and_fallback_connections( $options, $deleted_conn ) {
		// Handle default connection.
		if (
			isset( $options['default_connection'] ) &&
			$options['default_connection']['id'] === $deleted_conn['id']
		) {

				// Set the connection with the highest priority as the new default.
				$options['default_connection'] = $this->get_highest_priority_connection( $options['connections'] );

		}

		return $options;
	}

	/**
	 * Get the connection with the highest priority.
	 *
	 * @param array $connections The associative array of connections.
	 * @return array The connection with the highest priority or an empty array.
	 */
	private function get_highest_priority_connection( $connections ) {
		if ( empty( $connections ) ) {
			return [
				'type'             => '',
				'email'            => '',
				'id'               => '',
				'connection_title' => '',
			];
		}

		// Sort connections by priority ascending.
		uasort(
			$connections,
			static function ( $a, $b ) {
				return intval( $a['priority'] ) - intval( $b['priority'] );
			}
		);

		// Return the connection with the lowest priority number (highest priority).
		$first_connection = reset( $connections );

		if ( $first_connection ) {
			return [
				'type'             => $first_connection['type'],
				'email'            => $first_connection['from_email'],
				'id'               => $first_connection['id'],
				'connection_title' => $first_connection['connection_title'],
			];
		}

		return [
			'type'             => '',
			'email'            => '',
			'id'               => '',
			'connection_title' => '',
		];
	}
}

// Initialize the DeleteConnection singleton.
DeleteConnection::instance();
