<?php
/**
 * ResendEmail class
 *
 * Handles the REST API endpoint for resending an email.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Controller\Logger;
use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Traits\SendEmail;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ResendEmail
 *
 * Handles the `/resend-email' REST API endpoint.
 */
class ResendEmail extends Api_Base {
	use Instance;
	use SendEmail;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/resend-email';

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
					'methods'             => WP_REST_Server::CREATABLE, // POST method.
					'callback'            => [ $this, 'handle_resend_email' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Handle resending an email based on log ID.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return WP_REST_Response The REST API response.
	 */
	public function handle_resend_email( $request ) {
		$email_log = EmailLog::instance();
		$log_ids   = $request->get_param( 'log_ids' );

		if ( ! is_array( $log_ids ) || empty( $log_ids ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Invalid log IDs provided.', 'suremails' ),
				],
				400
			);
		}

		$sanitized_log_ids  = array_map( 'intval', $log_ids );
		$results            = [];
		$logger             = Logger::instance();
		$connection_manager = ConnectionManager::instance();
		foreach ( $sanitized_log_ids as $log_id ) {
			$logs = $email_log->get(
				[
					'where' => [ 'id' => $log_id ],
					'limit' => 1, // Ensure we only fetch one record.
				]
			);

			if ( empty( $logs ) ) {
				$results[] = [
					'log_id'  => $log_id,
					'success' => false,
					'message' => __( 'Log not found.', 'suremails' ),
				];
				continue;
			}

			$log = $logs[0];

			// Prepare the email attributes.
			$atts = [
				'to'          => maybe_unserialize( $log['email_to'] ),
				'subject'     => $log['subject'],
				'message'     => $log['body'],
				'headers'     => $log['headers'],
				'attachments' => $log['attachments'],
			];

			$logger->set_id( $log_id );
			$connection_manager->set_is_resend( true );

			$email_sent = self::send( $atts['to'], $atts['subject'], $atts['message'], $atts['headers'], $atts['attachments'] );

			if ( $email_sent ) {
				$results[] = [
					'log_id'  => $log_id,
					'success' => true,
					'message' => __( 'Email resent successfully.', 'suremails' ),
				];
			} else {
				$results[] = [
					'log_id'  => $log_id,
					'success' => false,
					'message' => __( 'Failed to resend email.', 'suremails' ),
				];
			}
		}
		$connection_manager->set_is_resend( false );

		$overall_success = array_reduce( $results, static fn( $carry, $item) => $carry || $item['success'], false );

		return new WP_REST_Response(
			[
				'success' => $overall_success,
				'results' => $results,
			],
			$overall_success ? 200 : 500
		);
	}

}

// Initialize the ResendEmail singleton.
ResendEmail::instance();
