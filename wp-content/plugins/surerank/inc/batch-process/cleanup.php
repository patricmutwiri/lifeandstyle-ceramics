<?php
/**
 * Cleanup
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\BatchProcess;

use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;

/**
 * Cleanup
 *
 * @since 1.2.0
 */
class Cleanup {

	use Get_Instance;
	use Logger;

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
	}

	/**
	 * Import
	 *
	 * @since 1.2.0
	 * @return array<string, mixed>
	 */
	public function import() {

		self::log( 'Cleanup Process is completed.' );
		delete_option( 'surerank_cache_generation_post_offset' );
		do_action( 'surerank_batch_process_complete' );
		return [
			'success' => true,
			'msg'     => __( 'Cleanup Process is completed.', 'surerank' ),
		];
	}

}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Cleanup::get_instance();
