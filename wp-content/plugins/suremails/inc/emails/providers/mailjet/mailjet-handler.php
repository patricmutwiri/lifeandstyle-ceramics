<?php
/**
 * MailjetHandler.php
 *
 * Handles sending emails using Mailjet.
 *
 * @package SureMails\Inc\Emails\Providers\Mailjet
 */

namespace SureMails\Inc\Emails\Providers\MAILJET;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MailjetHandler
 *
 * Implements the ConnectionHandler to handle Mailjet email sending and authentication.
 */
class MailjetHandler implements ConnectionHandler {

	/**
	 * Mailjet Send Mail API base URL.
	 *
	 * @var string
	 */
	public $send_email_api_url = 'https://api.mailjet.com/v3.1/send';

	/**
	 * Mailjet Authenticate User API base URL.
	 *
	 * @var string
	 */
	public $authenticate_api_url = 'https://api.mailjet.com/v3/REST/user';

	/**
	 * Mailjet connection data.
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
	 * Authenticate the Mailjet connection.
	 *
	 * Since Mailjet does not provide a direct authentication endpoint, this function
	 * simply saves the connection data and returns a success message.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {

		if ( empty( $this->connection_data['api_key'] ) || empty( $this->connection_data['from_email'] ) || empty( $this->connection_data['secret_key'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Authentication keys are missing.', 'suremails' ),
			];
		}

		return [
			'success'    => true,
			'message'    => __( 'Mailjet connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send email using Mailjet.
	 *
	 * @param array $atts Email attributes.
	 * @param int   $log_id Log ID.
	 * @param array $connection Connection data.
	 * @param array $processed_data Processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {

		$api_key    = $this->connection_data['api_key'] ?? '';
		$secret_key = $this->connection_data['secret_key'] ?? '';

		if ( empty( $api_key ) || empty( $secret_key ) ) {
			return [
				'success' => false,
				'message' => __( 'Mailjet API key and Secret key are required.', 'suremails' ),
				'send'    => false,
			];
		}

		$email_payload = [
			'Messages' => [
				[
					'From'     => [
						'Email' => $connection['from_email'] ?? '',
						'Name'  => $connection['from_name'] ?? '',
					],
					'To'       => $this->process_recipients( $processed_data['to'] ?? [] ),
					'Subject'  => sanitize_text_field( $processed_data['subject'] ?? '' ),
					'TextPart' => wp_strip_all_tags( $atts['message'] ?? '' ),
				],
			],
		];

		$content_type = $processed_data['headers']['content_type'];
		if ( ! empty( $content_type ) && ProviderHelper::is_html( $content_type ) ) {
			$email_payload['Messages'][0]['HTMLPart'] = $atts['message'];
		}

		$reply_to = $processed_data['headers']['reply_to'];
		if ( ! empty( $reply_to ) ) {
			$reply_to                                = reset( $processed_data['headers']['reply_to'] );
			$email_payload['Messages'][0]['ReplyTo'] = $this->process_reply_to_recipients( $reply_to );
		}

		$cc_emails = $processed_data['headers']['cc'] ?? [];
		if ( ! empty( $cc_emails ) ) {
			$email_payload['Messages'][0]['Cc'] = $this->process_recipients( $cc_emails );
		}

		$bcc_emails = $processed_data['headers']['bcc'] ?? [];
		if ( ! empty( $bcc_emails ) ) {
			$email_payload['Messages'][0]['Bcc'] = $this->process_recipients( $bcc_emails );
		}

		if ( ! empty( $processed_data['attachments'] ) ) {
			$email_payload['Messages'][0]['Attachments'] = $this->get_attachments( $processed_data['attachments'] );
		}

		$json_payload = wp_json_encode( $email_payload );
		if ( false === $json_payload ) {
			return [
				'success' => false,
				'message' => __( 'Failed to encode email payload to JSON.', 'suremails' ),
				'send'    => false,
			];
		}

		$response = wp_safe_remote_post(
			$this->send_email_api_url,
			[
				'body'    => $json_payload,
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( "{$api_key}:{$secret_key}" ),
					'Content-Type'  => 'application/json',
				],
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				/* translators: %s: Error message. */
				'message' => sprintf( __( 'Email sending failed via Mailjet. %s', 'suremails' ), $response->get_error_message() ),
				'send'    => false,
			];
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code === 200 ) {
			return [
				'success' => true,
				'message' => __( 'Email sent successfully via Mailjet.', 'suremails' ),
				'send'    => true,
			];
		}

		return [
			'success' => false,
			'message' => sprintf(
				/* translators: %s: Error message. */
				__( 'Email sending failed via Mailjet. %s', 'suremails' ),
				$response_body['ErrorMessage']
			),
			'send'    => false,
		];
	}

	/**
	 * Get the Mailjet connection options.
	 *
	 * @return array The Mailjet connection options.
	 */
	public static function get_options() {
		return [
			'title'             => __( 'Mailjet Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Mailjet account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'MailjetIcon',
			'display_name'      => __( 'Mailjet', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'api_key', 'secret_key', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 36,
		];
	}

	/**
	 * Get the specific schema fields for Mailjet.
	 *
	 * @return array The specific schema fields for Mailjet.
	 */
	public static function get_specific_fields() {
		return [
			'api_key'    => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'API Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Mailjet API key', 'suremails' ),
				'encrypt'     => true,
			],
			'secret_key' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Secret Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Mailjet Secret key', 'suremails' ),
				'encrypt'     => true,
			],
		];
	}

	/**
	 * Process recipients array.
	 *
	 * @param array $recipients Array of recipients.
	 * @return array
	 */
	public function process_recipients( $recipients ) {
		$result = [];
		foreach ( $recipients as $recipient ) {
			if ( is_array( $recipient ) ) {
				$email = isset( $recipient['email'] ) ? sanitize_email( $recipient['email'] ) : ( isset( $recipient['from_email'] ) ? sanitize_email( $recipient['from_email'] ) : '' );
				$name  = isset( $recipient['name'] ) ? sanitize_text_field( $recipient['name'] ) : '';

				if ( ! empty( $email ) ) {
					$result[] = [
						'Email' => $email,
						'Name'  => $name,
					];
				}
			}
			if ( is_string( $recipient ) ) {
				$email = sanitize_email( $recipient );
				if ( ! empty( $email ) ) {
					$result[] = [
						'Email' => $email,
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Process reply-to recipients array.
	 *
	 * @param array $recipients Array of recipients.
	 * @return array
	 */
	public function process_reply_to_recipients( $recipients ) {

		$email = isset( $recipients['email'] ) ? sanitize_email( $recipients['email'] ) : '';
		$name  = isset( $recipients['name'] ) ? sanitize_text_field( $recipients['name'] ) : '';

		if ( ! empty( $email ) ) {
			return [
				'Email' => $email,
				'Name'  => $name,
			];
		}

		return [];
	}

	/**
	 * Process attachments by reading the file, encoding its contents in base64 and preparing the attachment array.
	 *
	 * @param array $attachments Array of attachment file paths.
	 * @return array
	 */
	private function get_attachments( $attachments ) {
		$result = [];
		foreach ( $attachments as $attachment ) {

			$attachment_values = ProviderHelper::get_attachment( $attachment );

			if ( ! $attachment_values ) {
				continue;
			}

			$result[] = [
				'Filename'      => $attachment_values['name'] ?? '',
				'Base64Content' => $attachment_values['blob'] ?? '',
				'ContentType'   => $attachment_values['type'] ?? '',
			];
		}
		return $result;
	}
}
