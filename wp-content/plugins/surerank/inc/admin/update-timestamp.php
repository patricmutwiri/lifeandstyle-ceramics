<?php
/**
 * Update Timestamp
 *
 * @since 1.0.0
 * @package surerank
 */

namespace SureRank\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Update Timestamp
 *
 * @since 1.0.0
 */
class Update_Timestamp {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		self::init_actions();
	}

	/**
	 * Initialize actions in which we will update the timestamp.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public static function init_actions() {
		/**
		 * These options represent a list of settings for which we intend to update the timestamp upon modification.
		 */
		$actions_name = [
			'blogname',
			'blogdescription',
			'new_admin_email',
		];

		foreach ( $actions_name as $action_name ) {
			add_action( "update_option_{$action_name}", [ 'SureRank\Inc\Admin\Update_Timestamp', 'timestamp_option' ] );
		}
	}

	/**
	 * Update the timestamp option.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function timestamp_option() {
		// Update the timestamp option.
		Update::option( SURERANK_SEO_LAST_UPDATED, time() );
	}
}
