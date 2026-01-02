<?php
/**
 * Phpmail Handler.php
 *
 * Handles sending emails using PHP Mail.
 *
 * @package SureMails\Inc\Emails\Providers\Phpmail
 */

namespace SureMails\Inc\Emails\Providers\Phpmail;

use Exception;
use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PhpmailHandler
 *
 * Implements the ConnectionHandler to handle Phpmail Mail email sending and authentication.
 */
class PhpmailHandler implements ConnectionHandler {

	/**
	 * PHP mail connection data.
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
	 * Authenticate the PHP Mail connection.
	 *
	 * Since PHP Mail does not provide a direct authentication endpoint, this function
	 * simply saves the connection data and returns a success message.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {

		$from_email = sanitize_email( $this->connection_data['from_email'] );

		if ( empty( $this->connection_data['from_email'] ) ) {
			return [
				'success'    => false,
				'message'    => __( 'From Email is missing in the connection data.', 'suremails' ),
				'error_code' => 400,
			];
		}

		return [
			'success'    => true,
			'message'    => __( 'PHP Mail connection failed.', 'suremails' ),
			'error_code' => 500,
		];
	}

	/**
	 * Send an email using PHP Mail.
	 *
	 * @param array $atts The email attributes.
	 * @param int   $log_id The log ID.
	 * @param array $connection The connection details.
	 * @param array $processed_data The processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		$phpmailer = ConnectionManager::instance()->get_phpmailer();

		$from_email = sanitize_email( $connection['from_email'] );
		$from_name  = sanitize_text_field( $connection['from_name'] );
		$phpmailer->setFrom( $from_email, $from_name );
		$phpmailer->isMail();

		$content_type = $processed_data['headers']['content_type'];
		if ( ! empty( $content_type ) && ProviderHelper::is_html( $content_type ) ) {
			$phpmailer->msgHTML( $atts['message'] );
			$phpmailer->AltBody = wp_strip_all_tags( $atts['message'] );
		}

		try {
			if ( $phpmailer->Mailer !== 'mail' ) {
				$phpmailer->Mailer = 'mail';
			}

			$send = $phpmailer->send();
			if ( ! $send ) {
				return [
					'success' => false,
					'message' => __( 'Email sending failed via PHP Mail.', 'suremails' ),
					'send'    => false,
				];
			}
			return [
				'success' => true,
				'message' => __( 'Email sent successfully via PHP Mail.', 'suremails' ),
				'send'    => true,

			];

		} catch ( Exception $e ) {
			return [
				'success' => false,
				// translators: %s: The error message.
				'message' => sprintf( __( 'Email sending failed via PHP Mail: %s', 'suremails' ), $e->getMessage() ),
				'send'    => false,
			];
		}
	}

	/**
	 * Get the PHP Mail connection options.
	 *
	 * @return array The PHP Mail connection options.
	 */
	public static function get_options() {
		return [
			'title'             => __( 'PHP Mail Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your PHP Mail account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'PhpMailIcon',
			'display_name'      => __( 'PHP Mail', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 36,
		];
	}

	/**
	 * Get the PHP Mail connection specific fields.
	 *
	 * @return array The PHP Mail connection specific fields.
	 */
	public static function get_specific_fields() {
		return [];
	}

}
