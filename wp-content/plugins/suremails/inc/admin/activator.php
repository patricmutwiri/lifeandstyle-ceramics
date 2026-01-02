<?php
/**
 * SureMails Plugin Activator
 *
 * This file contains the Activator class which handles the activation process
 * of the SureMails plugin, including creating necessary database tables and checking
 * configuration settings.
 *
 * @package SureMails\Activator
 */

namespace SureMails\Inc\Admin;

use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Utils\LogError;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activator
 *
 * Handles plugin activation tasks such as creating database tables
 * and checking for required configurations.
 */
class Activator {
	use Instance;

	/**
	 * Create the email log database table during plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		update_option( 'suremails_do_redirect', true );
		$status = EmailLog::instance()->create_table();

		// Check if table creation was successful.
		if ( ! is_bool( $status ) && $status instanceof WP_Error ) {
			LogError::instance()->log_error( 'SureMail: Error creating email log table: ' . $status->get_error_message() );
		}
	}
}
