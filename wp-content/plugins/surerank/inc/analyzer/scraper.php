<?php
/**
 * Scraper class.
 *
 * Handles HTTP requests for SEO analysis.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Analyzer;

use SureRank\Inc\Functions\Requests;
use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Scraper
 *
 * Fetches HTML content from URLs.
 */
class Scraper {
	use Get_Instance;

	/**
	 * Last HTTP response.
	 *
	 * @var array<string,mixed>|WP_Error|null
	 */
	private $last_response = null;

	/**
	 * Last response body.
	 *
	 * @var string|WP_Error|null
	 */
	private $last_body = null;

	/**
	 * Last response code.
	 *
	 * @var int|null
	 */
	private $response_code = null;

	/**
	 * Fetch HTML content from a URL.
	 *
	 * @param string $url The URL to scrape.
	 * @return string|WP_Error HTML content or error on failure.
	 */
	public function fetch( string $url ) {
		$this->last_response = $this->call_request( $url );
		if ( is_wp_error( $this->last_response ) ) {
			return $this->last_response;
		}

		$this->last_body = wp_remote_retrieve_body( $this->last_response );
		if ( empty( $this->last_body ) ) {
			$this->last_body = new WP_Error(
				'empty_response',
				__( 'Empty response from URL.', 'surerank' )
			);
		}

		return $this->last_body;
	}

	/**
	 * Get a specific header from the last HTTP response.
	 *
	 * @param string $header The header name.
	 * @return string|WP_Error Header value or WP_Error if unavailable.
	 */
	public function get_header( string $header ) {
		if ( ! $this->last_response || is_wp_error( $this->last_response ) ) {
			return new WP_Error(
				'no_response',
				__( 'No response available to retrieve header.', 'surerank' )
			);
		}

		$value = wp_remote_retrieve_header( $this->last_response, $header );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Get the body from the last HTTP response.
	 *
	 * @return string|WP_Error Response body or WP_Error if unavailable.
	 */
	public function get_body() {
		if ( ! $this->last_body ) {
			return new WP_Error(
				'no_body',
				__( 'No response body available.', 'surerank' )
			);
		}

		return $this->last_body;
	}

	/**
	 * Make HTTP request to URL.
	 *
	 * @param string $url The URL to request.
	 * @return array<string,mixed>|WP_Error HTTP response or WP_Error on failure.
	 */
	public function call_request( string $url ) {
		return Requests::get( $url, apply_filters( 'surerank_scraper_headers', [] ) );
	}

	/**
	 * Fetch HTTP status code from a URL.
	 *
	 * @param string $url The URL to check status for.
	 * @return int|WP_Error Status code or WP_Error on failure.
	 */
	public function fetch_status( string $url ) {
		$this->last_response = $this->call_request( $url );
		if ( is_wp_error( $this->last_response ) ) {
			return new WP_Error(
				'no_response',
				__( 'No response available to retrieve status.', 'surerank' )
			);
		}
		$this->response_code = (int) wp_remote_retrieve_response_code( $this->last_response );
		return $this->response_code;
	}
}
