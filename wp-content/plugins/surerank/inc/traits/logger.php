<?php
/**
 * Logger.
 *
 * @package surerank;
 * @since 1.2.0
 */

namespace SureRank\Inc\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Logger
 *
 * @since 1.0.0`
 */
trait Logger {

	/**
	 * Log an error message to the WordPress debug log.
	 *
	 * @param string $message The error message to log.
	 * @param string $type    The type of log message. Can be 'log', 'error', or 'warning'.
	 *
	 * @return void
	 */
	public static function log( string $message, string $type = 'log' ) {
		if ( defined( 'SURERANK_DEBUG' ) && SURERANK_DEBUG ) {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			if ( 'log' === $type || 'info' === $type ) {
				\WP_CLI::log( $message );
			} elseif ( 'error' === $type ) {
				\WP_CLI::error( $message );
			} else {
				\WP_CLI::warning( $message );
			}
		}
	}

}
