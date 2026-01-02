<?php
/**
 * SmtpHandler.php
 *
 * Handles SMTP-specific email sending functionalities for the SureMails plugin.
 *
 * @package SureMails\Inc\Emails\Providers\SMTP
 */

namespace SureMails\Inc\Emails\Providers\SMTP;

use PHPMailer\PHPMailer\Exception;
use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Emails\Handler\ConnectionHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SmtpHandler
 *
 * Handles SMTP-specific email sending functionalities.
 */
class SmtpHandler implements ConnectionHandler {
	/**
	 * SMTP connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Constructor.
	 *
	 * Initializes SMTP connection data.
	 *
	 * @param array $connection_data The SMTP connection settings.
	 */
	public function __construct( array $connection_data ) {
		$this->connection_data = $connection_data;
	}

	/**
	 * Authenticate the SMTP connection.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {
		return [
			'success'    => true,
			'message'    => '',
			'error_code' => 200,
		];
	}

	/**
	 * Send an email via SMTP, including attachments if provided.
	 *
	 * @param array $atts          The email attributes, such as 'to', 'from', 'subject', 'message', 'headers', 'attachments', etc.
	 * @param int   $log_id        The ID of the email log entry.
	 * @param array $connection    The connection details.
	 * @param array $processed_data The processed email data from ProcessEmailData.
	 * @return array                The result of the email send operation.
	 */
	public function send( array $atts, $log_id, array $connection, array $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,
		];

		try {
			$phpmailer = ConnectionManager::instance()->get_phpmailer();
			// Server settings.
			$phpmailer->isSMTP(); // Set mailer to use SMTP.
			$phpmailer->Host        = sanitize_text_field( $connection['host'] ?? '' ); // Specify main SMTP server.
			$phpmailer->Username    = sanitize_text_field( $connection['username'] ?? '' ); // SMTP username.
			$phpmailer->Password    = sanitize_text_field( $connection['password'] ?? '' ); // SMTP password.
			$phpmailer->SMTPAuth    = ! ( empty( $phpmailer->Username ) && empty( $phpmailer->Password ) ); // Enable SMTP auth only if credentials exist.
			$phpmailer->SMTPAutoTLS = (bool) $connection['auto_tls'];
			$encryption             = strtolower( sanitize_text_field( $connection['encryption'] ) );
			if ( $encryption !== 'none' ) {
				$phpmailer->SMTPSecure = $encryption;
			}
			$phpmailer->Port    = intval( $connection['port'] ); // TCP port to connect to.
			$phpmailer->Timeout = 5; // Set a timeout of 4 seconds.

			$from_email = $connection['from_email'];
			$from_name  = ! empty( $connection['from_name'] ) ? $connection['from_name'] : __( 'WordPress', 'suremails' );

			$phpmailer->setFrom( $from_email, $from_name );

			// Set Return-Path if provided.
			if ( isset( $connection['return_path'] ) && $connection['return_path'] ) {
				$phpmailer->Sender = $phpmailer->From;
			}

			// Send the email.
			$send = $phpmailer->send();

			if ( $send ) {
				$result['success'] = true;
				$result['message'] = __( 'Email sent successfully via SMTP.', 'suremails' );
				$result['send']    = true;
			} else {
				$result['message'] = sprintf(
					// translators: %s: The error message from PHPMailer.
					__( 'Email sending failed via SMTP: %s', 'suremails' ),
					$phpmailer->ErrorInfo
				);
				$result['retries'] = 1; // Increment retries if applicable.
			}
		} catch ( Exception $e ) {
			$result['success'] = false;
			$result['message'] = sprintf(
				// translators: %s: The error message.
				__( 'Email sending failed via SMTP: %s', 'suremails' ),
				$e->getMessage()
			);
			$result['retries'] = 1; // Increment retries if applicable.
		}

		return $result;
	}
	/**
	 * Return the option configuration for SMTP.
	 *
	 * @return array
	 */
	public static function get_options() {
		return [
			'title'             => __( 'SMTP Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your SMTP account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'display_name'      => __( 'Other SMTP Provider', 'suremails' ),
			'icon'              => 'SmtpIcon',
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'host', 'port', 'encryption', 'username', 'password', 'auto_tls', 'from_email', 'force_from_email', 'return_path', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 150,
		];
	}

	/**
	 * Get the specific schema fields for SMTP.
	 *
	 * @return array
	 */
	public static function get_specific_fields() {
		return [
			'host'        => [
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'Host', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter the SMTP host', 'suremails' ),
			],
			'port'        => [
				'required'    => true,
				'datatype'    => 'int',
				'help_text'   => '',
				'label'       => __( 'Port', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter port', 'suremails' ),
			],
			'username'    => [
				'required'    => false,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'Username', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter SMTP username', 'suremails' ),
			],
			'password'    => [
				'required'    => false,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'Password', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter SMTP password', 'suremails' ),
				'encrypt'     => true,
			],
			'return_path' => [
				'default'     => true,
				'required'    => false,
				'datatype'    => 'boolean',
				'help_text'   => __( 'The Return Path is where bounce messages (failed delivery notices) are sent. If it’s off, you might not get these messages. Turn it on to receive bounce notifications at the "From Email" address if delivery fails.', 'suremails' ),
				'label'       => __( 'Return Path', 'suremails' ),
				'input_type'  => 'checkbox',
				'placeholder' => '',
				'depends_on'  => [ 'from_email' ],
			],
			'encryption'  => [
				'default'    => 'TLS',
				'required'   => true,
				'datatype'   => 'string',
				'help_text'  => __( 'Choose SSL for port 465, or TLS for port 25 or 587', 'suremails' ),
				'label'      => __( 'Encryption', 'suremails' ),
				'input_type' => 'select',
				'options'    => [
					'NONE' => __( 'None', 'suremails' ),
					'SSL'  => __( 'SSL', 'suremails' ),
					'TLS'  => __( 'TLS', 'suremails' ),
				],
			],
			'auto_tls'    => [
				'default'     => true,
				'required'    => false,
				'datatype'    => 'boolean',
				'help_text'   => __( 'Enable TLS automatically if the server supports it.', 'suremails' ),
				'label'       => __( 'Auto TLS', 'suremails' ),
				'input_type'  => 'checkbox',
				'placeholder' => '',
			],
		];
	}
}
