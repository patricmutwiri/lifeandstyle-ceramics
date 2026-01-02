<?php
/**
 * OutlookHandler.php
 *
 * Handles sending emails using Microsoft Outlook/Office 365.
 *
 * @package SureMails\Inc\Emails\Providers\Outlook
 */

namespace SureMails\Inc\Emails\Providers\OUTLOOK;

use SureMails\Inc\Emails\Handler\ConnectionHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OutlookHandler
 *
 * Implements the ConnectionHandler to handle Outlook email sending and authentication.
 */
class OutlookHandler implements ConnectionHandler {

	/**
	 * Outlook connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Constructor.
	 *
	 * Initializes connection data.
	 *
	 * @param array $connection_data The connection details.
	 */
	public function __construct( array $connection_data ) {
		$this->connection_data = $connection_data;
	}

	/**
	 * Authenticate the Outlook connection.
	 *
	 * Since Outlook does not provide a direct authentication endpoint, this function
	 * simply saves the connection data and returns a success message.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {
		return [
			'success'    => true,
			'message'    => __( 'Outlook connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send email using Outlook.
	 *
	 * @param array $atts           The email attributes.
	 * @param int   $log_id         The log ID.
	 * @param array $connection      The connection details.
	 * @param array $processed_data The processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		return [
			'success' => false,
			'message' => __( 'Outlook sending not yet implemented.', 'suremails' ),
			'send'    => false,
		];
	}

	/**
	 * Get the Outlook connection options.
	 *
	 * @return array The Outlook connection options.
	 */
	public static function get_options() {
		return [
			'title'          => __( 'Outlook Connection', 'suremails' ),
			'description'    => __( 'Enter the details below to connect with your Microsoft Outlook/Office 365 account.', 'suremails' ),
			'fields'         => self::get_specific_fields(),
			'icon'           => 'OutlookIcon',
			'display_name'   => __( 'Microsoft Outlook/Office 365', 'suremails' ),
			'provider_type'  => 'soon',
			'field_sequence' => [ 'connection_title', 'client_id', 'client_secret', 'redirect_uri', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
		];
	}

	/**
	 * Get the specific fields for the Outlook connection.
	 *
	 * @return array The specific fields for the Outlook connection.
	 */
	public static function get_specific_fields() {
		return [
			'client_id'     => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Client ID', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter your Outlook Client ID', 'suremails' ),
				'encrypt'     => true,
			],
			'client_secret' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Client Secret', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Outlook Client Secret', 'suremails' ),
				'encrypt'     => true,
			],
			'redirect_uri'  => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Redirect URI', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter your Outlook Redirect URI', 'suremails' ),
			],
		];
	}
}
