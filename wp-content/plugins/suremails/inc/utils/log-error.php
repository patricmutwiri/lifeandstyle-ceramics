<?php
/**
 * Log Error Class.
 *
 * @package SureMails;
 * @since 0.0.1
 */

namespace SureMails\Inc\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureMails\Inc\Traits\Instance;

/**
 * LogError
 *
 * @since 0.0.1
 */
class LogError {
	use Instance;

	/**
	 * Log an error message to the WordPress debug log.
	 *
	 * @param string $message The error message to log.
	 *
	 * @return void
	 */
	public function log_error( string $message ) {
		if ( defined( 'SUREMAILS_DEBUG' ) && SUREMAILS_DEBUG ) {
			error_log( '[SureMails EmailLog Error] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

}
