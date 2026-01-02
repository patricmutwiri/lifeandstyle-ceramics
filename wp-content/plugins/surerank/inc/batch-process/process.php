<?php
/**
 * SureRank Background Process
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\BatchProcess;

use SureRank\Inc\Lib\Background_Process\Wp_Background_Process;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;

/**
 * Image Background Process
 *
 * @since 1.2.0
 */
class Process extends Wp_Background_Process {

	use Get_Instance;
	use Logger;

	/**
	 * Image Process
	 *
	 * @var string
	 */
	protected $action = 'sitemap_process';

	/**
	 * Start time for execution tracking
	 *
	 * @since 1.2.0
	 * @var int
	 */
	protected $start_time;

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		/** WooCommerce-style time limit handling. */
		$this->start_time = time();
		$this->raise_time_limit();

		/** Ensure adequate memory. */
		wp_raise_memory_limit( 'admin' );
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since 1.2.0
	 *
	 * @param object $process Queue item object.
	 * @return mixed
	 */
	protected function task( $process ) {
		$start_time = microtime( true );
		if ( $this->time_exceeded() || $this->memory_exceeded() ) {
			/** Return the process to continue in next batch. */
			return $process;
		}

		if ( method_exists( $process, 'import' ) ) {
			$process->import();
		}

		return false;
	}

	/**
	 * WooCommerce-style time limit handling
	 * Raises execution time limit intelligently
	 *
	 * @param int $limit The limit to raise.
	 * @return void
	 */
	protected function raise_time_limit( $limit = 0 ) {
		$limit              = (int) $limit;
		$max_execution_time = (int) ini_get( 'max_execution_time' );

		/** If already unlimited, don't change. */
		if ( 0 === $max_execution_time ) {
			return;
		}

		/** Default to 300 seconds (5 minutes) if no limit specified. */
		$raise_by = 0 === $limit ? 300 : $limit;

		/** Only raise if current limit is lower. */
		if ( $max_execution_time < $raise_by ) {
			$disable_functions     = ini_get( 'disable_functions' );
			$disable_functions_str = is_string( $disable_functions ) ? $disable_functions : '';

			if ( function_exists( 'set_time_limit' )
				&& false === strpos( $disable_functions_str, 'set_time_limit' )
				&& ! ini_get( 'safe_mode' ) ) {
				$result = set_time_limit( $raise_by );
				if ( ! $result && ! defined( 'WP_CLI' ) ) {
					self::log( 'Failed to set time limit', 'error' );
				}
			}
		}
	}

	/**
	 * Check if time limit is likely to be exceeded
	 * Based on WooCommerce's ActionScheduler implementation
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$execution_time     = $this->get_execution_time();
		$max_execution_time = $this->get_time_limit();

		$time_threshold = $max_execution_time * 0.9;

		return $execution_time >= $time_threshold;
	}

	/**
	 * Check if memory limit is likely to be exceeded
	 * Based on WooCommerce's implementation
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9;
		$current_memory = memory_get_usage( true );
		return $current_memory >= $memory_limit;
	}

	/**
	 * Get execution time since process start
	 *
	 * @return int
	 */
	protected function get_execution_time() {
		if ( 0 === $this->start_time ) {
			$this->start_time = time();
		}
		return time() - $this->start_time;
	}

	/**
	 * Get time limit for processing
	 *
	 * @return int
	 */
	protected function get_time_limit() {
		$time_limit         = 30;
		$max_execution_time = ini_get( 'max_execution_time' );

		if ( $max_execution_time > 0 && $max_execution_time < $time_limit ) {
			$time_limit = $max_execution_time;
		}

		return apply_filters( 'surerank_queue_runner_time_limit', $time_limit );
	}

	/**
	 * Get memory limit in bytes
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || '-1' === $memory_limit ) {
			$memory_limit = '1G';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 *
	 * @since 1.2.0
	 * @return void
	 */
	protected function complete(): void {

		parent::complete();

		do_action( 'surerank_batch_process_complete' );
	}
}
