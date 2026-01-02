<?php
/**
 * Requests class.
 *
 * Handles HTTP requests for SEO analysis.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Functions;

use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Requests
 *
 * Handles HTTP requests for SEO analysis.
 */
class Requests {
	use Get_Instance;

	/**
	 * Get the status code of a URL.
	 *
	 * @param string               $url The URL to check. It will behave like wp_safe_remote_head.
	 * @param array<string, mixed> $args The arguments of the request.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function head( $url, $args = [] ) {
		return wp_safe_remote_head(
			$url,
			array_merge(
				[
					'timeout'     => 10, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
					'redirection' => 0,
				],
				$args
			)
		);
	}

	/**
	 * Get the body of a URL. It will behave like wp_safe_remote_get.
	 *
	 * @param string               $url The URL to get the body of.
	 * @param array<string, mixed> $args The arguments of the request.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( $url, $args = [] ) {
		return wp_safe_remote_get(
			$url,
			$args
		);
	}

	/**
	 * Post to a URL. It will behave like wp_safe_remote_post.
	 *
	 * @param string               $url The URL to post to.
	 * @param array<string, mixed> $args The arguments of the post.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function post( $url, $args ) {
		return wp_safe_remote_post(
			$url,
			$args
		);
	}

	/**
	 * Request to a URL. It will behave like wp_safe_remote_request.
	 *
	 * @param string               $url The URL to request to.
	 * @param array<string, mixed> $args The arguments of the request.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function request( $url, $args ) {
		return wp_safe_remote_request(
			$url,
			$args
		);
	}
}
