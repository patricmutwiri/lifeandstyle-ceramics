<?php
/**
 * DeleteLogs class
 *
 * Handles the REST API endpoint to delete logs.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Emails\ProviderHelper;
use SureMails\Inc\Traits\Instance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class DeleteLogs
 *
 * Handles the `/delete-logs` REST API endpoint.
 */
class DeleteLogs extends Api_Base {

	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/delete-logs';

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
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'delete_email_log' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'log_ids' => [
							'required'          => true,
							'type'              => 'array',
							'sanitize_callback' => static function ( $param ) {
								// Sanitize each value in the array.
								return array_map( 'absint', $param );
							},
						],
					],
				],
			]
		);
	}

	/**
	 *  Deletes selected email logs and removes associated attachments if they are not used in other logs.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return WP_REST_Response The REST API response.
	 */
	public function delete_email_log( $request ) {
		// Retrieve and validate log IDs from the request.
		$log_ids   = $request->get_param( 'log_ids' );
		$email_log = EmailLog::instance();

		// Fetch logs marked for deletion, along with their attachments.
		$logs_to_delete = $email_log->get(
			[
				'select' => 'attachments',
				'where'  => [ 'id IN' => $log_ids ],
			]
		);

		// Return error if no logs are found.
		if ( ! is_array( $logs_to_delete ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Failed to retrieve logs to delete.', 'suremails' ),
				],
				500
			);
		}

		// Use the helper to extract log IDs and unique attachments.
		$extracted            = ProviderHelper::extract_log_data( $logs_to_delete );
		$all_attachments_list = $extracted['attachments'];

		// Build conditions to check for attachments in retained logs.
		$where              = ProviderHelper::build_attachment_like_conditions( $all_attachments_list );
		$where['id NOT IN'] = $log_ids;

		// Fetch logs that are still retained (so we don't delete shared attachments).
		$logs_to_retain = $email_log->get(
			[
				'select' => 'id, attachments',
				'where'  => $where,
			]
		);

		// Track which attachments should be deleted and which should be kept.
		$attachments_kept = [];
		if ( ! empty( $logs_to_retain ) ) {
			foreach ( $logs_to_retain as $log ) {
				if ( ! empty( $log['attachments'] ) ) {
					$attachments_kept = array_merge( $attachments_kept, (array) $log['attachments'] );
				}
			}
		}
		$attachments_kept = array_unique( $attachments_kept );

		// Delete only attachments that are no longer used in retained logs.
		ProviderHelper::delete_unused_attachments( $all_attachments_list, $attachments_kept );

		// Delete email logs from the database.
		$deleted_count = $email_log->delete(
			[
				'ids' => $log_ids,
			]
		);

		// Return an error if deletion fails.
		if ( $deleted_count === false ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Failed to delete the provided log IDs.', 'suremails' ),
				],
				500
			);
		}

		// Construct the success message.
		if ( count( $log_ids ) === $deleted_count ) {
			$message = sprintf(
				// Translators: %d is the number of logs deleted.
				__( '%d log(s) deleted successfully.', 'suremails' ),
				$deleted_count
			);
		} else {
			$remaining = count( $log_ids ) - $deleted_count;
			$message   = sprintf(
				// Translators: %1$d is the number of logs deleted, %2$d is the number of logs that could not be deleted.
				__( '%1$d log(s) deleted successfully. %2$d log(s) could not be deleted.', 'suremails' ),
				$deleted_count,
				$remaining
			);
		}

		// Return the response.
		return new WP_REST_Response(
			[
				'success' => true,
				'message' => $message,
			],
			200
		);
	}
}

// Initialize the DeleteLogs singleton.
DeleteLogs::instance();
