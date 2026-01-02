<?php
/**
 * Google Console Controller Class and Dashboard API Handler
 *
 * Contains the Controller class for Google Console API processing and the Dashboard class for REST API endpoints.
 *
 * @since 1.0.0
 * @package SureRank
 */

/**
 * Controller Class
 */

namespace SureRank\Inc\GoogleSearchConsole;

use DateTime;
use DateTimeZone;
use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Request;

/**
 * APIs Class
 *
 * Responsible for processing APIs for Google Console.
 *
 * @since 1.0.0
 */
class Controller {

	use Get_Instance;

	/**
	 * Google API Base
	 */
	public const GOOGLE_ANALYTICS_API_BASE = 'https://www.googleapis.com/webmasters/v3/';

	/**
	 * Google User Info API Base
	 */
	private const GOOGLE_USER_INFO_API_BASE = 'https://www.googleapis.com/oauth2/v2/userinfo';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Get Sites
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_sites() {
		// We are returning Array, because we will use Send_Json::success() to send the response.
		return GoogleConsole::get_instance()->call_api( self::GOOGLE_ANALYTICS_API_BASE . 'sites' );
	}

	/**
	 * Get Matched Domain or Site URL
	 *
	 * Returns the matching site URL from Google Search Console sites list
	 *
	 * @return string|null Returns the matched site URL or null if no match found
	 */
	public function get_matched_site() {
		$sites = $this->get_sites();
		$sites = $sites['siteEntry'] ?? [];

		if ( empty( $sites ) ) {
			return null;
		}

		$current_site_url = get_site_url();
		$current_site_url = str_replace( [ 'https://', 'http://', 'www.', '/', '//' ], '', $current_site_url );

		foreach ( $sites as $site ) {
			$site_url            = $site['siteUrl'] ?? '';
			$normalized_site_url = $site_url;
			if ( str_starts_with( $site_url, 'sc-domain:' ) ) {
				$normalized_site_url = str_replace( 'sc-domain:', '', $site_url );
			} else {
				$normalized_site_url = str_replace( [ 'https://', 'http://', 'www.', '/', '//' ], '', $site_url );
			}
			if ( $normalized_site_url === $current_site_url ) {
				return $site_url;
			}
		}

		return null;
	}

	/**
	 * Get Auth Status
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function get_auth_status() {
		return Auth::get_instance()->auth_check();
	}

	/**
	 * Get User Site URL
	 *
	 * @since 1.0.0
	 * @return string Returns the site URL or an error array.
	 */
	public function get_user_site_url() {
		$site_url = Auth::get_instance()->get_credentials( null )['site_url'] ?? '';
		if ( empty( $site_url ) ) {
			Send_Json::error(
				[
					'message' => __( 'No site URL found', 'surerank' ),
					'status'  => 400,
				]
			);
		}
		return $site_url;
	}

	/**
	 * Get Site Traffic
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_site_traffic( $request ) {
		$site_url = $this->get_user_site_url();
		$body     = [
			'startDate'  => $this->get_start_date( $request ),
			'endDate'    => $this->get_end_date( $request ),
			'dimensions' => [ 'date' ],
			'rowLimit'   => '500',
			'dataState'  => 'ALL',
		];

		$url                   = self::GOOGLE_ANALYTICS_API_BASE . 'sites/' . $this->get_site_url( $site_url ) . '/searchAnalytics/query';
		$search_analytics_data = $this->get_search_analytics_data( $url, $body );

		if ( isset( $search_analytics_data['rows'] ) && empty( $search_analytics_data['rows'] ) ) {
			return [
				'success' => false,
				'message' => __( 'No data found for the selected date range.', 'surerank' ),
			];
		}

		return $this->process_search_analytics_data( $search_analytics_data );
	}

	/**
	 * Process Search Analytics Data
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|array<int, array<string, mixed>> $search_analytics_data The search analytics data.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function process_search_analytics_data( $search_analytics_data ) {

		if ( isset( $search_analytics_data['responseAggregationType'] ) ) {
			unset( $search_analytics_data['responseAggregationType'] );
		}

		if ( ! isset( $search_analytics_data['rows'] ) || ! is_array( $search_analytics_data['rows'] ) ) {
			$search_analytics_data['data'] = [];
			return $search_analytics_data;
		}

		$search_analytics_data['data'] = array_map(
			static function( $row ) {
				if ( isset( $row['keys'][0] ) ) {
					$row['date'] = $row['keys'][0];
					unset( $row['keys'] );
					unset( $row['ctr'] );
					unset( $row['position'] );
				}
				return $row;
			},
			$search_analytics_data['rows']
		);

		unset( $search_analytics_data['rows'] );

		return $search_analytics_data;
	}

	/**
	 * Get Clicks and Impressions
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_clicks_and_impressions( $request ) {
		$start_date = $this->get_start_date( $request );
		$end_date   = $this->get_end_date( $request );

		return $this->get_clicks_and_impressions_data( $start_date, $end_date );
	}

	/**
	 * Get Clicks and Impressions Data
	 *
	 * @since 1.6.0
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_clicks_and_impressions_data( $start_date, $end_date ) {
		$site_url = $this->get_user_site_url();

		$current_body = [
			'startDate' => $start_date,
			'endDate'   => $end_date,
			'dataState' => 'ALL',
		];

		// Calculate previous period dates.
		$date_range          = ( strtotime( $current_body['endDate'] ) - strtotime( $current_body['startDate'] ) ) / ( 60 * 60 * 24 );
		$previous_end_date   = gmdate( 'Y-m-d', (int) strtotime( $current_body['startDate'] . ' -1 day' ) );
		$previous_start_date = gmdate( 'Y-m-d', (int) strtotime( $previous_end_date . " -{$date_range} days" ) );

		$previous_body = [
			'startDate' => $previous_start_date,
			'endDate'   => $previous_end_date,
			'dataState' => 'ALL',
		];

		$url = self::GOOGLE_ANALYTICS_API_BASE . 'sites/' . $this->get_site_url( $site_url ) . '/searchAnalytics/query';

		// Get data for both periods.
		$current_data  = $this->get_search_analytics_data( $url, $current_body );
		$previous_data = $this->get_search_analytics_data( $url, $previous_body );

		$current_processed = isset( $current_data['rows'] ) && is_array( $current_data['rows'] )
			? array_map(
				static function( $row ) {
					unset( $row['ctr'] );
					unset( $row['position'] );
					return $row;
				},
				$current_data['rows']
			)
			: [];

		$previous_processed = isset( $previous_data['rows'] ) && is_array( $previous_data['rows'] )
			? array_map(
				static function( $row ) {
					unset( $row['ctr'] );
					unset( $row['position'] );
					return $row;
				},
				$previous_data['rows']
			)
			: [];

		$result = [];

		$current_clicks       = array_sum( array_column( $current_processed, 'clicks' ) );
		$current_impressions  = array_sum( array_column( $current_processed, 'impressions' ) );
		$previous_clicks      = array_sum( array_column( $previous_processed, 'clicks' ) );
		$previous_impressions = array_sum( array_column( $previous_processed, 'impressions' ) );

		$result['data']['clicks'] = [
			'current'    => $current_clicks,
			'previous'   => $previous_clicks,
			'percentage' => $previous_clicks > 0
				? round( ( $current_clicks - $previous_clicks ) / $previous_clicks * 100, 2 )
				: ( $current_clicks > 0 ? 100 : 0 ),
		];

		$result['data']['impressions'] = [
			'current'    => $current_impressions,
			'previous'   => $previous_impressions,
			'percentage' => $previous_impressions > 0
				? round( ( $current_impressions - $previous_impressions ) / $previous_impressions * 100, 2 )
				: ( $current_impressions > 0 ? 100 : 0 ),
		];

		return $result;
	}

	/**
	 * Get User Info
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_user_info() {
		return GoogleConsole::get_instance()->call_api( self::GOOGLE_USER_INFO_API_BASE );
	}

	/**
	 * Get Search Analytics Data
	 *
	 * @since 1.0.0
	 * @param string               $url The URL to get the data from.
	 * @param array<string, mixed> $body The body of the request.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_search_analytics_data( $url, $body ) {
		return GoogleConsole::get_instance()->call_api( $url, 'POST', $body );
	}

	/**
	 * Get Start Date
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return string
	 */
	public function get_start_date( $request ) {
		$timezone = new DateTimeZone( apply_filters( 'surerank_search_console_timezone', 'America/Los_Angeles' ) );
		return $request->get_param( 'startDate' )
			? ( new DateTime( $request->get_param( 'startDate' ), $timezone ) )->format( 'Y-m-d' )
			: ( new DateTime( '-365 days', $timezone ) )->format( 'Y-m-d' );
	}

	/**
	 * Get End Date
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return string
	 */
	public function get_end_date( $request ) {
		$timezone = new DateTimeZone( apply_filters( 'surerank_search_console_timezone', 'America/Los_Angeles' ) );
		return $request->get_param( 'endDate' )
			? ( new DateTime( $request->get_param( 'endDate' ), $timezone ) )->format( 'Y-m-d' )
			: ( new DateTime( '-2 day', $timezone ) )->format( 'Y-m-d' );
	}

	/**
	 * Get Content Performance
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_content_performance( $request ) {
		$start_date = $this->get_start_date( $request );
		$end_date   = $this->get_end_date( $request );

		return $this->get_content_performance_data( $start_date, $end_date );
	}

	/**
	 * Get Content Performance Data
	 *
	 * @since 1.6.0
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_content_performance_data( $start_date, $end_date ) {
		$site_url = $this->get_user_site_url();

		$current_body = [
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => [ 'page' ],
			'rowLimit'   => '500',
			'dataState'  => 'ALL',
		];

		// Calculate previous period dates.
		$date_range          = ( strtotime( $current_body['endDate'] ) - strtotime( $current_body['startDate'] ) ) / ( 60 * 60 * 24 );
		$previous_end_date   = gmdate( 'Y-m-d', (int) strtotime( $current_body['startDate'] . ' -1 day' ) );
		$previous_start_date = gmdate( 'Y-m-d', (int) strtotime( $previous_end_date . " -{$date_range} days" ) );

		$previous_body = [
			'startDate'  => $previous_start_date,
			'endDate'    => $previous_end_date,
			'dimensions' => [ 'page' ],
			'rowLimit'   => '500',
			'dataState'  => 'ALL',
		];

		$url = self::GOOGLE_ANALYTICS_API_BASE . 'sites/' . $this->get_site_url( $site_url ) . '/searchAnalytics/query';

		$current_search_analytics_data  = $this->get_search_analytics_data( $url, $current_body );
		$previous_search_analytics_data = $this->get_search_analytics_data( $url, $previous_body );

		// Process current data with safety checks.
		$current_data = [];
		if ( isset( $current_search_analytics_data['rows'] ) && is_array( $current_search_analytics_data['rows'] ) ) {
			$current_data = array_map(
				static function ( $row ) {
					$processed_row = $row;
					if ( isset( $row['keys'][0] ) ) {
						$processed_row['url'] = $row['keys'][0];
						unset( $processed_row['keys'] );
					}
					return $processed_row;
				},
				$current_search_analytics_data['rows']
			);
		}

		// Process previous data with safety checks.
		$previous_data = [];
		if ( isset( $previous_search_analytics_data['rows'] ) && is_array( $previous_search_analytics_data['rows'] ) ) {
			$previous_data = array_map(
				static function ( $row ) {
					$processed_row = $row;
					if ( isset( $row['keys'][0] ) ) {
						$processed_row['url'] = $row['keys'][0];
						unset( $processed_row['keys'] );
					}
					return $processed_row;
				},
				$previous_search_analytics_data['rows']
			);
		}

		$combined_data = [];
		foreach ( $current_data as $current_row ) {
			$url          = $current_row['url'];
			$previous_row = array_filter(
				$previous_data,
				static function ( $row ) use ( $url ) {
					return $row['url'] === $url;
				}
			);

			if ( reset( $previous_row ) === false ) {
				$previous_row = [
					'clicks'      => 0,
					'impressions' => 0,
					'ctr'         => 0,
					'position'    => 0,
				];
			} else {
				$previous_row = reset( $previous_row );
			}

			// Calculate clicks change.
			$clicks_change = 0.0;
			if ( $previous_row['clicks'] > 0 ) {
				$clicks_change = round( ( $current_row['clicks'] - $previous_row['clicks'] ) / $previous_row['clicks'] * 100, 2 );
			} elseif ( $current_row['clicks'] > 0 ) {
				$clicks_change = 100.0;
			}

			// Calculate impressions change.
			$impressions_change = 0.0;
			if ( $previous_row['impressions'] > 0 ) {
				$impressions_change = round( ( $current_row['impressions'] - $previous_row['impressions'] ) / $previous_row['impressions'] * 100, 2 );
			} elseif ( $current_row['impressions'] > 0 ) {
				$impressions_change = 100.0;
			}

			// Calculate CTR change.
			$ctr_change = 0.0;
			if ( $previous_row['ctr'] > 0 ) {
				$ctr_change = round( ( $current_row['ctr'] - $previous_row['ctr'] ) / $previous_row['ctr'] * 100, 2 );
			} elseif ( $current_row['ctr'] > 0 ) {
				$ctr_change = 100.0;
			}

			// Calculate position change.
			$position_change = 0.0;
			if ( $previous_row['position'] > 0 ) {
				$position_change = round( ( $current_row['position'] - $previous_row['position'] ) / $previous_row['position'] * 100, 2 );
			} elseif ( $current_row['position'] > 0 ) {
				$position_change = 100.0;
			}

			$combined_data[] = [
				'url'     => $url,
				'current' => [
					'clicks'      => $current_row['clicks'],
					'impressions' => $current_row['impressions'],
					'ctr'         => $current_row['ctr'],
					'position'    => $current_row['position'],
				],
				'changes' => [
					'clicks'      => $clicks_change,
					'impressions' => $impressions_change,
					'ctr'         => $ctr_change,
					'position'    => $position_change,
				],
			];
		}

		return [
			'success' => true,
			'data'    => $combined_data,
		];
	}

	/**
	 * Get Google Console User Details
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function get_google_console_user_details() {
		return [
			'name'     => Auth::get_instance()->get_credentials( 'name' ) ?? '',
			'email'    => Auth::get_instance()->get_credentials( 'email' ) ?? '',
			'gravatar' => Auth::get_instance()->get_credentials( 'gravatar' ) ?? '',
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
		return SiteVerification::get_instance()->auto_create_and_verify_property();
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
		return SiteVerification::get_instance()->verify_existing_property();
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
		return SiteVerification::get_instance()->get_verification_token( $site_url );
	}

	/**
	 * Store Verification Token
	 *
	 * Stores the verification token for meta tag injection
	 *
	 * @since 1.4.0
	 * @param string $token The verification token.
	 * @return void
	 */
	public function store_verification_token( $token ) {
		SiteVerification::get_instance()->store_verification_token( $token );
	}

	/**
	 * Get Stored Verification Token
	 *
	 * Gets the stored verification token
	 *
	 * @since 1.4.0
	 * @return string|false
	 */
	public function get_stored_verification_token() {
		return SiteVerification::get_instance()->get_stored_verification_token();
	}

	/**
	 * Verify Site
	 *
	 * Verifies a site using Site Verification API
	 *
	 * @since 1.4.0
	 * @param string $site_url The site URL to verify.
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function verify_site( $site_url ) {
		return SiteVerification::get_instance()->verify_site( $site_url );
	}

	/**
	 * Call Site Verification API
	 *
	 * Makes direct wp_remote_request calls to Site Verification API with specific error handling
	 *
	 * @since 1.4.0
	 * @param string               $endpoint The API endpoint URL.
	 * @param string               $method   HTTP method (GET, POST, PUT, etc.).
	 * @param array<string, mixed> $args     Request arguments.
	 * @return array<string, mixed> API response or error array.
	 */
	public function call_site_verification_api( $endpoint, $method = 'GET', $args = [] ) {
		return SiteVerification::get_instance()->call_site_verification_api( $endpoint, $method, $args );
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
		return SiteVerification::get_instance()->add_site( $site_url );
	}

	/**
	 * Get Site URL
	 *
	 * @param string $site_url The site URL to get.
	 * @return string The formatted site URL.
	 */
	private function get_site_url( $site_url ) {
		if ( strpos( $site_url, 'sc-domain' ) === false ) {
			$site_url = urlencode( $site_url );
		}
		return $site_url;
	}

}
