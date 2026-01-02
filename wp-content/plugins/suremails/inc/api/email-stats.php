<?php
/**
 * EmailStats class
 *
 * Handles the REST API endpoint to get email statistics.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Traits\Instance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class EmailStats
 *
 * Handles the `/email-stats` REST API endpoint.
 */
class EmailStats extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/email-stats';

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
					'callback'            => [ $this, 'get_email_stats' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'start_date' => [
							'required'          => true,
							'validate_callback' => static function ( $param ) {
								return strtotime( $param ) !== false;
							},
							'sanitize_callback' => 'sanitize_text_field',
						],
						'end_date'   => [
							'required'          => false,
							'validate_callback' => static function ( $param ) {
								return strtotime( $param ) !== false;
							},
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Retrieves email statistics (total sent and failed emails) for a given date range.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object containing the selected dates.
	 * @return WP_REST_Response The REST response object with email statistics or an error message.
	 */
	public function get_email_stats( $request ) {
		$email_log = EmailLog::instance();

		// Get the date range from the request body.
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		$start_datetime = $start_date ? new \DateTime( $start_date ) : null;
		$end_datetime   = $end_date ? new \DateTime( $end_date ) : null;

		if ( $start_datetime && $end_datetime ) {
			$end_datetime->setTime( 23, 59, 59 );
		}

		if ( $start_datetime && ! $end_datetime ) {
			$end_datetime = clone $start_datetime;
			$end_datetime->setTime( 23, 59, 59 );
		}

		if ( ! $start_datetime ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'The start_date parameter is required.', 'suremails' ),
				],
				400
			);
		}

		$date_from_query = $start_datetime->format( 'Y-m-d H:i:s' );
		$date_to_query   = $end_datetime ? $end_datetime->format( 'Y-m-d H:i:s' ) : $start_datetime->format( 'Y-m-d H:i:s' );

		// 1. Fetch chart data for emails sent and failed within the date range
		$chart_data = $email_log->get(
			[
				'select'   => "
					DATE(created_at) as created_at,
					SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed
				",
				'where'    => [
					'created_at >=' => $date_from_query,
					'created_at <=' => $date_to_query,
				],
				'group_by' => 'DATE(created_at)',
				'order'    => [ 'created_at' => 'ASC' ],
			]
		);

		// 2. Fetch total sent and failed emails for the date range
		$total_counts = $email_log->get(
			[
				'select' => "
					SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed
				",
				'where'  => [
					'created_at >=' => $date_from_query,
					'created_at <=' => $date_to_query,
				],
			]
		);

		$total_sent   = $total_counts[0]['total_sent'] ?? 0;
		$total_failed = $total_counts[0]['total_failed'] ?? 0;

		// Prepare the response.
		return rest_ensure_response(
			[
				'success' => true,
				'data'    => [
					'total_sent'   => (int) $total_sent,
					'total_failed' => (int) $total_failed,
					'chart_data'   => $chart_data, // Include chart data.
					'from'         => $date_from_query,
					'end_at'       => $date_to_query,
				],
			]
		);
	}
}

// Initialize the EmailStats singleton.
EmailStats::instance();
