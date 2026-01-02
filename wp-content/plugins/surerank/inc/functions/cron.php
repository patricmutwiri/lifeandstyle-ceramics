<?php
/**
 * Cron functionality for SureRank plugin.
 *
 * @since 1.2.0
 * @package surerank
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;

/**
 * Cron
 *
 * @since 1.2.0
 */
class Cron {
	use Get_Instance;
	use Logger;

	/**
	 * Cron event name for sitemap generation.
	 *
	 * @since 1.2.0
	 */
	public const SITEMAP_CRON_EVENT = 'surerank_generate_sitemap_cron';

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function __construct() {
		add_action( self::SITEMAP_CRON_EVENT, [ $this, 'generate_sitemap_cron' ], 10, 1 );
		add_action( 'wp_loaded', [ $this, 'ensure_cron_scheduled' ] );
		add_filter( 'cron_schedules', [ $this, 'cron_schedules' ] );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @since 1.2.0
	 * @param array<string,array<string,int|string>> $schedules Array of cron schedules.
	 * @return array<string,array<string,int|string>> Modified array of cron schedules.
	 */
	public function cron_schedules( $schedules ) {
		$schedules['every_six_hours'] = [
			'interval' => 21600, // 4 times a day (every 6 hours)
			'display'  => __( 'Every 6 hours', 'surerank' ),
		];
		return $schedules;
	}

	/**
	 * Schedule the sitemap generation cron job.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function schedule_sitemap_generation() {
		if ( ! wp_next_scheduled( self::SITEMAP_CRON_EVENT ) ) {
			wp_schedule_event( time(), 'every_six_hours', self::SITEMAP_CRON_EVENT );
			$this->log( __( 'Sitemap generation cron job scheduled.', 'surerank' ) );
		}
	}

	/**
	 * Unschedule the sitemap generation cron job.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function unschedule_sitemap_generation() {
		$timestamp = wp_next_scheduled( self::SITEMAP_CRON_EVENT );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::SITEMAP_CRON_EVENT );
			$this->log( __( 'Sitemap generation cron job unscheduled.', 'surerank' ) );
		}
	}

	/**
	 * Generate sitemap via cron job.
	 *
	 * @param string $force Force flag to regenerate cache.
	 * @since 1.2.0
	 * @return void
	 */
	public function generate_sitemap_cron( $force = '' ) {
		$this->log( __( 'Starting sitemap generation via cron job.', 'surerank' ) );

		do_action( 'surerank_start_building_cache', $force );

		$this->log( __( 'Sitemap generation cron job completed.', 'surerank' ) );
	}

	/**
	 * Check if sitemap generation cron is scheduled.
	 *
	 * @since 1.2.0
	 * @return bool True if scheduled, false otherwise.
	 */
	public function is_sitemap_cron_scheduled() {
		return wp_next_scheduled( self::SITEMAP_CRON_EVENT ) !== false;
	}

	/**
	 * Get next scheduled time for sitemap generation.
	 *
	 * @since 1.2.0
	 * @return int|false Next scheduled timestamp or false if not scheduled.
	 */
	public function get_next_sitemap_cron_time() {
		return wp_next_scheduled( self::SITEMAP_CRON_EVENT );
	}

	/**
	 * Ensure cron is scheduled on wp_loaded.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ensure_cron_scheduled() {
		if ( ! wp_next_scheduled( self::SITEMAP_CRON_EVENT ) ) {
			$this->schedule_sitemap_generation();
		}
	}

	/**
	 * Manually trigger sitemap generation (for testing purposes).
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function trigger_sitemap_generation() {
		$this->generate_sitemap_cron();
	}
}
