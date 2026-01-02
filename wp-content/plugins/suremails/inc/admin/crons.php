<?php
/**
 * Crons Class
 *
 * This file contains the logic for handling Crons for the SureMails plugin.
 *
 * @package SureMails\Inc\Admin
 */

namespace SureMails\Inc\Admin;

use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Controller\ContentGuard;
use SureMails\Inc\Controller\Emails;
use SureMails\Inc\Controller\Logger;
use SureMails\Inc\Controller\WeeklySummary;
use SureMails\Inc\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Crons
 *
 * Main class for handling crons.
 */
class Crons {
	use Instance;

	/**
	 * Crons initialization function.
	 */
	protected function __construct() {
		add_action( 'suremails_cleanup_cron', [ $this, 'delete_old_email_logs' ] );
		add_action( 'suremails_weekly_summary', [ $this, 'handle_weekly_summary' ] );

		if ( ! wp_next_scheduled( 'suremails_cleanup_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'suremails_cleanup_cron' ); // Schedule cleanup daily.
		}

		if ( ! wp_next_scheduled( 'suremails_weekly_summary' ) ) {
			wp_schedule_event( time(), 'daily', 'suremails_weekly_summary' ); // Daily check for weekly summary.
		}

		// Hook to retry failed emails.
		add_action( 'suremails_retry_failed_email', [ 'SureMails\Inc\Controller\Emails', 'retry_failed_email' ], 10, 1 );
	}

	/**
	 * Deletes old email logs based on the configured retention period.
	 *
	 * @return void
	 */
	public function delete_old_email_logs() {

		if ( class_exists( 'SureMails\Inc\Controller\Emails' ) ) {
			Emails::instance()->delete_old_email_logs();
		}

		if ( class_exists( 'SureMails\Inc\Controller\ContentGuard' ) ) {
			ContentGuard::flush_hashes();
		}
	}

	/**
	 * Handle sending of weekly summary emails (runs daily, internally checks if it's the correct day).
	 *
	 * @return void
	 */
	public function handle_weekly_summary() {
		if ( class_exists( 'SureMails\Inc\Controller\WeeklySummary' ) ) {
			WeeklySummary::instance()->maybe_send_summary();
		}
	}

	/**
	 * Schedules a retry for a failed email.
	 *
	 * @param int $log_id   The log ID of the email.
	 *
	 * @return void
	 */
	public function schedule_retry_failed_email( int $log_id ) {
		if ( empty( $log_id ) || ConnectionManager::instance()->get_is_resend() ) {
			return;
		}

		$logger    = Logger::instance();
		$log_entry = $logger->get_log( $log_id );
		if ( ! $log_entry ) {
			return;
		}

		if ( is_wp_error( $log_entry ) ) {
			return;
		}

		// Check if the maximum number of retries has been reached.
		if ( isset( $log_entry['meta'] ) && isset( $log_entry['meta']['retry'] ) && intval( $log_entry['meta']['retry'] ) >= 1 ) {
			return;
		}

		// Schedule the retry event after 30 minutes.
		wp_schedule_single_event( time() + 1800, 'suremails_retry_failed_email', [ $log_id ] );
	}
}

// Instantiate the singleton instance of Crons.
Crons::instance();
