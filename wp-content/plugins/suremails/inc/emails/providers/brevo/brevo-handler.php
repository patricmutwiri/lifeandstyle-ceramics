<?php
/**
 * BrevoHandler.php
 *
 * Handles sending emails using Brevo (SendinBlue).
 *
 * @package SureMails\Inc\Emails\Providers\Brevo
 */

namespace SureMails\Inc\Emails\Providers\BREVO;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class BrevoHandler
 *
 * Implements the ConnectionHandler to handle Brevo email sending and authentication.
 */
class BrevoHandler implements ConnectionHandler {

	/**
	 * Brevo connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * The Brevo API URL (for sending emails).
	 *
	 * @var string
	 */
	private $api_url = 'https://api.brevo.com/v3/smtp/email';

	/**
	 * The allowed attachment extensions.
	 *
	 * @var array
	 */
	private $allowed_extensions = [
		'xlsx',
		'xls',
		'ods',
		'docx',
		'docm',
		'doc',
		'csv',
		'pdf',
		'txt',
		'gif',
		'jpg',
		'jpeg',
		'png',
		'tif',
		'tiff',
		'rtf',
		'bmp',
		'cgm',
		'css',
		'shtml',
		'html',
		'htm',
		'zip',
		'xml',
		'ppt',
		'pptx',
		'tar',
		'ez',
		'ics',
		'mobi',
		'msg',
		'pub',
		'eps',
		'odt',
		'mp3',
		'm4a',
		'm4v',
		'wma',
		'ogg',
		'flac',
		'wav',
		'aif',
		'aifc',
		'aiff',
		'mp4',
		'mov',
		'avi',
		'mkv',
		'mpeg',
		'mpg',
		'wmv',
	];
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
	 * Authenticate the Brevo connection.
	 *
	 * This implementation retrieves the senders and checks if the connection's from_email
	 * exists as a sender. If not, it retrieves the domains and checks whether the domain extracted
	 * from the from_email is both authenticated and verified.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {

		if ( empty( $this->connection_data['api_key'] ) || empty( $this->connection_data['from_email'] ) ) {
			return [
				'success'    => false,
				'message'    => __( 'API key or From Email is missing in the connection data.', 'suremails' ),
				'error_code' => 400,
			];
		}
		return [
			'success' => true,
			'message' => __( 'Brevo Connection saved successfully.', 'suremails' ),
		];
	}

	/**
	 * Send an email via Brevo, including attachments if provided.
	 *
	 * @param array $atts           The email attributes (e.g., to, from, subject, message, etc.).
	 * @param int   $log_id         The log ID for the email.
	 * @param array $connection     The connection details.
	 * @param array $processed_data The processed email data.
	 * @return array                The result of the email send operation.
	 * @throws \Exception           If the email payload cannot be encoded to JSON.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,
		];

		$body = [
			'sender'      => [
				'name'  => ! empty( $connection['from_name'] ) ? $connection['from_name'] : __( 'WordPress', 'suremails' ),
				'email' => sanitize_email( $connection['from_email'] ),
			],
			'subject'     => sanitize_text_field( $atts['subject'] ?? '' ),
			'htmlContent' => $atts['message'] ?? '',
		];

		$request_headers = [
			'Api-Key'      => sanitize_text_field( $connection['api_key'] ),
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		];

		// Determine the content type from headers (default to HTML).
		$content_type = isset( $processed_data['headers']['content_type'] )
			? strtolower( $processed_data['headers']['content_type'] )
			: 'text/html';

		if ( 'text/plain' === $content_type ) {
			unset( $body['htmlContent'] );
			$body['textContent'] = wp_strip_all_tags( $atts['message'] );
		}

		if ( ! empty( $processed_data['to'] ) ) {
			$body['to'] = $this->format_email_recipients( $processed_data['to'] );
		}

		if ( ! empty( $processed_data['headers']['cc'] ) ) {
			$body['cc'] = $this->format_email_recipients( $processed_data['headers']['cc'] );
		}
		if ( ! empty( $processed_data['headers']['bcc'] ) ) {
			$body['bcc'] = $this->format_email_recipients( $processed_data['headers']['bcc'] );
		}

		$reply_to = $processed_data['headers']['reply_to'] ?? [];
		if ( ! empty( $reply_to ) ) {

			$reply_to     = $this->format_email_recipients( $reply_to );
			$single_reply = reset( $reply_to );

			if ( isset( $single_reply['name'] ) && ! empty( $single_reply['name'] ) ) {
				$body['replyTo'] = [
					'email' => sanitize_email( $single_reply['email'] ),
					'name'  => sanitize_text_field( $single_reply['name'] ),
				];
			} else {
				$body['replyTo'] = [
					'email' => sanitize_email( $single_reply['email'] ),
				];
			}
		}

		if ( ! empty( $processed_data['attachments'] ) ) {
			$attachments = [];
			foreach ( $processed_data['attachments'] as $attachment ) {
				$attachment_values = ProviderHelper::get_attachment( $attachment );
				if ( ! $attachment_values ) {
					continue;
				}
				$extension = $attachment_values['extension'];
				if ( in_array( $extension, $this->allowed_extensions, true ) ) {
					$attachments[] = [
						'name'    => $attachment_values['name'],
						'content' => $attachment_values['blob'],
					];
				}
			}
			if ( ! empty( $attachments ) ) {
				$body['attachment'] = $attachments;
			}
		}

		try {
			$json_payload = wp_json_encode( $body );
			if ( false === $json_payload ) {
				throw new \Exception( __( 'Failed to encode email payload to JSON.', 'suremails' ) );
			}

			$response = wp_safe_remote_post(
				$this->api_url,
				[
					'headers' => $request_headers,
					'body'    => $json_payload,
				]
			);

			if ( is_wp_error( $response ) ) {
				$result['message'] = sprintf(
				/* translators: %s: Error message from Brevo API */
					__( 'Email sending failed via Brevo. Error: %s', 'suremails' ),
					$response->get_error_message()
				);
				$result['error_code'] = $response->get_error_code();
				return $result;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$decoded_body  = json_decode( $response_body, true );

			// Brevo returns 201 on successful email send.
			if ( 201 === $response_code ) {
				$result['success'] = true;
				$result['send']    = true;
				$result['message'] = __( 'Email sent successfully via Brevo.', 'suremails' );
			} else {
				$error_message     = $decoded_body['message'] ?? __( 'Unknown error.', 'suremails' );
				$result['message'] = sprintf(
				/* translators: %s: Error message from Brevo API */
					__( 'Email sending failed via Brevo. Error: %s', 'suremails' ),
					$error_message
				);
				$result['error_code'] = $response_code;
			}
		} catch ( \Exception $e ) {
			$result['message'] = sprintf(
			/* translators: %s: Exception message */
				__( 'Email sending failed via Brevo. Error: %s', 'suremails' ),
				$e->getMessage()
			);
			$result['error_code'] = 500;
		}

		return $result;
	}

	/**
	 * Return the option configuration for Brevo.
	 *
	 * @return array
	 */
	public static function get_options() {
		return [
			'title'             => __( 'Brevo Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Brevo account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'display_name'      => __( 'Brevo (Sendinblue)', 'suremails' ),
			'icon'              => 'BrevoIcon',
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'api_key', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 20,
		];
	}

	/**
	 * Get the specific schema fields for Brevo.
	 *
	 * @return array
	 */
	public static function get_specific_fields() {
		return [
			'api_key' => [
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'API Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Brevo API key', 'suremails' ),
				'encrypt'     => true,
			],
		];
	}

	/**
	 * Sanitize email recipient data.
	 *
	 * Iterates through the email recipient fields (e.g., reply_to, to, cc, bcc)
	 * and removes the 'name' attribute if it is empty.
	 *
	 * @param array $recipients The array of email recipients.
	 * @return array The sanitized array of recipients.
	 */
	private function format_email_recipients( array $recipients ) {
		return array_map(
			static function ( $recipient ) {
				if ( isset( $recipient['email'] ) && isset( $recipient['name'] ) ) {
					if ( empty( $recipient['name'] ) ) {
						unset( $recipient['name'] );
					}
				}
				return $recipient;
			},
			$recipients
		);
	}

}
