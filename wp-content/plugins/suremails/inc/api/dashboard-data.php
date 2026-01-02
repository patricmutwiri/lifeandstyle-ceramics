<?php
/**
 * DashboardData class
 *
 * Handles the REST API endpoint for to get the dashboard data.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class DashboardData
 *
 * Handles the `/dashboard-data` REST API endpoint.
 */
class DashboardData extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/dashboard-data';

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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_dashboard_data' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);
	}

	/**
	 * Retrieves dashboard data for email statistics.
	 *
	 * @return WP_REST_Response The REST response object with email statistics.
	 */
	public function get_dashboard_data() {
		$chart_data  = $this->get_chart_data();
		$recent_logs = $this->get_recent_logs();
		$total       = $this->get_total_count( $chart_data );

		return rest_ensure_response(
			array_merge(
				[
					'success'     => true,
					'recent_logs' => $recent_logs,
					'chart_data'  => $chart_data,
				],
				$total,
			)
		);
	}

	/**
	 * Get Total counts including connections.
	 *
	 * @param bool|array<string, mixed> $chart_data Chart data containing sent and failed counts.
	 * @return array<string, mixed>
	 */
	public function get_total_count( $chart_data ) {

		if ( ! is_array( $chart_data ) ) {
			$chart_data = [];
		}
		$total = [
			'total_sent'        => 0,
			'total_failed'      => 0,
			'total_logs'        => 0,
			'total_connections' => 0,
		];

		foreach ( $chart_data as $data ) {
			$total['total_sent']   += (int) ( $data['total_sent'] ?? 0 );
			$total['total_failed'] += (int) ( $data['total_failed'] ?? 0 );
		}

		$total['total_logs'] = $total['total_sent'] + $total['total_failed'];

		$settings                   = Settings::instance()->get_settings();
		$connections                = isset( $settings['connections'] ) && is_array( $settings['connections'] ) ? $settings['connections'] : [];
		$total['total_connections'] = count( $connections );

		return $total;
	}

	/**
	 * Get Recent Logs
	 *
	 * @return array<string, mixed>|false
	 */
	public function get_recent_logs() {
		return EmailLog::instance()->get(
			[
				'order' => [ 'updated_at' => 'DESC' ],
				'limit' => 6,
			]
		);
	}

	/**
	 * Get Chart data
	 *
	 * @return array<string, mixed>|false
	 */
	public function get_chart_data() {
		$email_log = EmailLog::instance();

		$current_date = new \DateTime();
		$start_date   = clone $current_date;
		$start_date->modify( '-30 days' );

		$start_date_formatted = $start_date->format( 'Y-m-d 00:00:00' );
		$end_date_formatted   = $current_date->format( 'Y-m-d 23:59:59' );

		$where = [
			'created_at >=' => $start_date_formatted,
			'created_at <=' => $end_date_formatted,
		];

		// Fetch the dashboard data.
		return $email_log->get(
			[
				'select'   => "
                DATE(created_at) as created_at,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed
            ",
				'where'    => $where,
				'group_by' => 'DATE(created_at)',
				'order'    => [ 'created_at' => 'DESC' ],
			]
		);
	}

}

// Initialize the DashboardData singleton.
DashboardData::instance();
