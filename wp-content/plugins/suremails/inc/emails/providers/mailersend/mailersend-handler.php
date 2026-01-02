<?php
/**
 * MailersendHandler.php
 *
 * Handles sending emails using MailerSend.
 *
 * @package SureMails\Inc\Emails\Providers\MailerSend
 */

namespace SureMails\Inc\Emails\Providers\Mailersend;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MailersendHandler
 *
 * Implements the ConnectionHandler to handle MailerSend email sending and authentication.
 */
class MailersendHandler implements ConnectionHandler {

	/**
	 * MailerSend connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Expected HTTP code for a successful email send.
	 *
	 * @var int
	 */
	protected $email_sent_code = 202;

	/**
	 * API endpoint.
	 *
	 * Note: Previously, endpoints for different regions were supported.
	 * Now MailerSend uses a fixed endpoint.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.mailersend.com/v1/email';

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
	 * Authenticate the MailerSend connection.
	 *
	 * Since MailerSend does not provide a direct authentication endpoint, this function
	 * simply saves the connection data and returns a success message.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {
		return [
			'success'    => true,
			'message'    => __( 'MailerSend connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send email using MailerSend.
	 *
	 * @param array $atts           The email attributes.
	 * @param int   $log_id         The log ID.
	 * @param array $connection     The connection details.
	 * @param array $processed_data The processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,
		];

		// Prepare the email "from" details.
		$from_name  = $connection['from_name'] ?? '';
		$from_email = sanitize_email( $connection['from_email'] ?? '' );

		// Build the recipient arrays.
		$to  = $this->format_recipients_array( $processed_data['to'] ?? [] );
		$cc  = $this->format_recipients_array( $processed_data['headers']['cc'] ?? [] );
		$bcc = $this->format_recipients_array( $processed_data['headers']['bcc'] ?? [] );

		// Build the content array.
		// At least one of text or html must be provided (per API docs).
		$html = $atts['message'] ?? '';
		$text = wp_strip_all_tags( $atts['message'] ?? '' );

		// Build the JSON body as per MailerSend specifications.
		$content_type = isset( $processed_data['headers']['content_type'] )
			? strtolower( $processed_data['headers']['content_type'] )
			: 'text/html';

		$body = [
			'from'    => [
				'email' => $from_email,
				'name'  => $from_name,
			],
			'to'      => $to,
			'subject' => $atts['subject'] ?? '',
			'text'    => $text,
		];

		if ( $content_type === 'text/html' ) {
			$body['html'] = $html;
			$body['text'] = $text;
		}

		// Optionally add cc and bcc if non-empty.
		if ( ! empty( $cc ) ) {
			$body['cc'] = $cc;
		}
		if ( ! empty( $bcc ) ) {
			$body['bcc'] = $bcc;
		}

		// Add reply_to if provided.
		if ( ! empty( $processed_data['headers']['reply_to'] ) ) {
			$reply_to = $this->get_reply_to( $processed_data['headers']['reply_to'] );
			if ( $reply_to && isset( $reply_to[0]['email'] ) ) {
				$body['reply_to'] = [
					'email' => $reply_to[0]['email'],
					'name'  => $reply_to[0]['name'] ?? '',
				];
			}
		}

		// Add attachments if any.
		if ( ! empty( $processed_data['attachments'] ) && is_array( $processed_data['attachments'] ) ) {
			$body['attachments'] = $this->get_attachments( $processed_data['attachments'] );
		}

		// Encode body as JSON.
		$body_json = wp_json_encode( $body );
		if ( false === $body_json ) {
			$result['message'] = __( 'Email sending failed via MailerSend. Failed to encode email body to JSON.', 'suremails' );
			return $result;
		}

		$params = [
			'body'    => $body_json,
			'headers' => [
				'content-type'  => 'application/json',
				'Authorization' => 'Bearer ' . ( $connection['api_key'] ?? '' ),
			],
			'timeout' => 15,
		];

		// Send the POST request to MailerSend API.
		$response = wp_safe_remote_post( $this->api_url, $params );

		if ( is_wp_error( $response ) ) {
			$result['message']    = __( 'Email sending failed via MailerSend. ', 'suremails' ) . $response->get_error_message();
			$result['error_code'] = $response->get_error_code();
			return $result;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( $response_code === $this->email_sent_code ) {
			$result['success']  = true;
			$result['send']     = true;
			$result['email_id'] = $response_data['x-message-id'] ?? '';
			$result['message']  = __( 'Email sent successfully via MailerSend.', 'suremails' );
		} else {
			$error_message = '';

			if ( isset( $response_data['message'] ) ) {
				$error_message = $response_data['message'];
			} elseif ( isset( $response_data['error'] ) ) {
				$error_message = is_array( $response_data['error'] ) ? wp_json_encode( $response_data['error'] ) : $response_data['error'];
			} elseif ( is_string( $response_body ) && ! empty( $response_body ) ) {
				$error_message = $response_body;
			} else {
				$error_message = __( 'Unknown error.', 'suremails' );
			}

			$result['message'] = sprintf(
				/* translators: %s: Error message */
				__( 'Email sending failed via MailerSend. Error: %s', 'suremails' ),
				$error_message
			);
			$result['error_code'] = $response_code;
		}

		return $result;
	}

	/**
	 * Get the options for the MailerSend connection.
	 *
	 * @return array The options for the MailerSend connection.
	 */
	public static function get_options() {
		return [
			'title'             => __( 'MailerSend Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your MailerSend account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'MailerSendIcon',
			'display_name'      => __( 'MailerSend', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'api_key', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 35,
		];
	}

	/**
	 * Get the specific fields for the MailerSend connection.
	 *
	 * @return array The specific fields for the MailerSend connection.
	 */
	public static function get_specific_fields() {
		return [
			'api_key' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'API Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your MailerSend API key', 'suremails' ),
				'encrypt'     => true,
			],
		];
	}

	/**
	 * Format recipients as an array of arrays with name and email.
	 *
	 * @param array $recipients The recipients.
	 * @return array The formatted recipients.
	 */
	protected function format_recipients_array( array $recipients ) {
		$result = [];
		foreach ( $recipients as $recipient ) {
			if ( is_array( $recipient ) && ! empty( $recipient['email'] ) ) {
				$result[] = [
					'name'  => $recipient['name'] ?? '',
					'email' => sanitize_email( $recipient['email'] ),
				];
			}
		}
		return $result;
	}

	/**
	 * Retrieve the reply-to email address.
	 *
	 * @param mixed $reply_to The reply-to data.
	 * @return array|false The reply-to email address.
	 */
	protected function get_reply_to( $reply_to ) {
		$result = [];
		if ( is_array( $reply_to ) ) {
			$first = reset( $reply_to );
			if ( is_array( $first ) && ! empty( $first['email'] ) ) {
				$result[] = [
					'name'  => $first['name'] ?? '',
					'email' => sanitize_email( $first['email'] ),
				];
				return $result;
			}
		}
		return false;
	}

	/**
	 * Process attachments to be MailerSend-compatible.
	 *
	 * @param array $attachments The attachments.
	 * @return array The processed attachments.
	 */
	private function get_attachments( array $attachments ) {
		$result = [];
		foreach ( $attachments as $attachment ) {
			$attachment_values = ProviderHelper::get_attachment( $attachment );
			if ( ! $attachment_values ) {
				continue;
			}
			$result[] = [
				'content'     => $attachment_values['blob'] ?? '',
				'disposition' => 'attachment',
				'filename'    => $attachment_values['name'] ?? '',
			];
		}
		return $result;
	}
}
