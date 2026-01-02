<?php
/**
 * Logs class
 *
 * Handles email logs related REST API endpoints for the SureMails plugin.
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
 * Class Logs
 *
 * Handles email logs related REST API endpoints.
 */
class Logs extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/email-logs';

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
	}

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
				'methods'             => WP_REST_Server::CREATABLE, // POST method.
				'callback'            => [ $this, 'get_email_logs' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'page'       => [
						'required'          => false,
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'per_page'   => [
						'required'          => false,
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
					'start_date' => [
						'required'          => false,
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
					'filter'     => [
						'required'          => false,
						'validate_callback' => static function ( $param ) {
							return in_array( $param, EmailLog::instance()->get_statuses(), true );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
					'search'     => [
						'required'          => false,
						'validate_callback' => static function ( $param ) {
							return is_string( $param ) && strlen( $param ) > 0;
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Retrieves email logs with optional filters and search.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return WP_REST_Response The REST API response with email logs.
	 */
	public function get_email_logs( $request ) {

		// Retrieve parameters from the request.
		$page       = max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) );
		$per_page   = max( 1, (int) ( $request->get_param( 'per_page' ) ?? 10 ) );
		$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
		$end_date   = sanitize_text_field( $request->get_param( 'end_date' ) );
		$filter     = sanitize_text_field( $request->get_param( 'filter' ) );
		$search     = sanitize_text_field( $request->get_param( 'search' ) );

		$start_datetime = $start_date ? new \DateTime( $start_date ) : null;
		$end_datetime   = $end_date ? new \DateTime( $end_date ) : null;

		// Build 'where' conditions based on request parameters.
		$where = [];
		// Set start and end date in where array.
		if ( ! empty( $start_datetime ) ) {
			if ( empty( $end_datetime ) ) {
				$end_datetime = $start_datetime;
			}
			$start_datetime->setTime( 0, 0, 0 );
			$end_datetime->setTime( 23, 59, 59 );
			$where['updated_at >='] = $start_datetime->format( 'Y-m-d H:i:s' );
			$where['updated_at <='] = $end_datetime->format( 'Y-m-d H:i:s' );
		}

		if ( in_array( $filter, EmailLog::instance()->get_statuses(), true ) ) {
			$where['status'] = $filter;
		}
		if ( ! empty( $search ) ) {
			$where['subject LIKE']     = '%' . $search . '%';
			$where['OR email_to LIKE'] = '%' . $search . '%';
		}

		// Fetch paginated logs.
		$logs = $this->get_logs( $where, $page, $per_page );

		// Get the total count of logs.
		$total_count = $this->get_total_count( $where );

		if ( $logs === false || $total_count === false ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Failed to retrieve email logs.', 'suremails' ),
				],
				500
			);
		}

		// Return the response.
		return rest_ensure_response(
			[
				'success'     => true,
				'logs'        => $logs,
				'total_count' => $total_count,
			]
		);
	}

	/**
	 * Retrieve email logs with pagination.
	 *
	 * @param array $where    The 'where' conditions for the query.
	 * @param int   $page     The current page number.
	 * @param int   $per_page The number of logs per page.
	 * @return array|false The fetched logs or false on failure.
	 */
	protected function get_logs( array $where, int $page, int $per_page ) {
		$email_log = EmailLog::instance();

		$offset = ( $page - 1 ) * $per_page;

		return $email_log->get(
			[
				'where'  => $where,
				'order'  => [ 'updated_at' => 'DESC' ],
				'limit'  => $per_page,
				'offset' => $offset,
			]
		);
	}

	/**
	 * Retrieve the total count of email logs.
	 *
	 * @param array $where The 'where' conditions for the query.
	 * @return int|false The total count or false on failure.
	 */
	protected function get_total_count( array $where ) {
		$email_log = EmailLog::instance();

		$count_results = $email_log->get(
			[
				'select' => 'COUNT(*) as total_count',
				'where'  => $where,
			]
		);

		if ( $count_results === false || empty( $count_results ) ) {
			return false;
		}

		return (int) ( $count_results[0]['total_count'] ?? 0 );
	}
}

Logs::instance();
