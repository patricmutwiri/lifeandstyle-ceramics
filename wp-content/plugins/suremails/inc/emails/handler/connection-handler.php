<?php
/**
 * SureMails ConnectionHandler
 *
 * This file contains the the common functionalities for all the email providers.
 *
 * @package SureMails\Inc\Emails
 */

namespace SureMails\Inc\Emails\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface for handling email connections.
 */
interface ConnectionHandler {
	/**
	 * Authenticate the connection.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate();

	/**
	 * Send an email.
	 *
	 * @param array    $atts The email attributes, such as 'to', 'from', 'subject', 'message', 'headers', 'attachments', etc.
	 * @param int|null $log_id used to find the log from database.
	 * @param array    $connection The connection data/credentials used to connect and send data.
	 * @param array    $processed_data The processed data.
	 * @return array The result of the email send operation.
	 */
	public function send( array $atts, $log_id, array $connection, array $processed_data);

	/**
	 * Return the option configuration for this provider.
	 *
	 * The returned array should have the following structure:
	 * [
	 *     'title'         => (string) Provider title,
	 *     'description'   => (string) A brief description,
	 *     'fields'        => (array) A merged list of base fields plus provider-specific fields,
	 *     'logo'          => (string) URL/path to the provider logo,
	 *     'provider_type' => (string) e.g. 'free', 'soon', or 'paid'
	 * ]
	 *
	 * @return array
	 */
	public static function get_options();
}
