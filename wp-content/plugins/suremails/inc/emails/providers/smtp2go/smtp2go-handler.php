<?php
/**
 * SMTP2GOHandler.php
 *
 * Handles sending emails using SMTP2GO.
 *
 * @package SureMails\Inc\Emails\Providers\SMTP2GO
 */

namespace SureMails\Inc\Emails\Providers\SMTP2GO;

use Exception;
use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SMTP2GOHandler
 *
 * Implements the ConnectionHandler to handle SMTP2GO email sending and authentication.
 */
class Smtp2goHandler implements ConnectionHandler {

	/**
	 * SMTP2GO connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * SMTP2GO API endpoint.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.smtp2go.com/v3/email/send';

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
	 * Authenticate the SMTP2GO connection by verifying the API key.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {
		$result = [
			'success'    => false,
			'message'    => '',
			'error_code' => 200,
		];

		if ( empty( $this->connection_data['api_key'] ) || empty( $this->connection_data['from_email'] ) ) {
			return [
				'success'    => false,
				'message'    => __( 'API key or From Email is missing in the connection data.', 'suremails' ),
				'error_code' => 400,
			];
		}

		return [
			'success'    => true,
			'message'    => __( 'SMTP2GO connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send an email via SMTP2GO, including attachments if provided.
	 *
	 * This method builds the payload according to the following parameter schema:
	 *
	 * - sender*         : string (email address to send from)
	 * - to*             : array of strings (email addresses to send to)
	 * - cc              : array of strings (email addresses to cc)
	 * - bcc             : array of strings (email addresses to bcc)
	 * - subject*        : string (subject of the email)
	 * - html_body       : string (HTML encoded email body)
	 * - text_body       : string (plain text email body)
	 * - custom_headers  : array of objects (custom header objects)
	 * - attachments     : array of objects (attachment objects)
	 *
	 * @param array $atts           The email attributes (subject, message, html_body, text_body, etc.).
	 * @param int   $log_id         The log ID for the email.
	 * @param array $connection     The connection details.
	 * @param array $processed_data The processed email data (recipients, headers, attachments, etc.).
	 *
	 * @return array The result of the email send operation.
	 * @throws Exception If the email payload cannot be encoded to JSON.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,
		];

		$email_payload = [
			'sender'    => $connection['from_name'] . ' <' . $connection['from_email'] . '>',
			'to'        => $this->process_recipients( $processed_data['to'] ),
			'cc'        => $this->process_recipients( $processed_data['headers']['cc'] ?? [] ),
			'bcc'       => $this->process_recipients( $processed_data['headers']['bcc'] ?? [] ),
			'subject'   => sanitize_text_field( $atts['subject'] ?? '' ),
			'text_body' => wp_strip_all_tags( $atts['message'] ?? '' ),
		];

		$content_type = $processed_data['headers']['content_type'];
		if ( ! empty( $content_type ) && ProviderHelper::is_html( $content_type ) ) {
			$email_payload['html_body'] = $atts['message'];
		}

		if ( ! empty( $processed_data['headers']['reply_to'] ) ) {
			$reply_to                        = reset( $processed_data['headers']['reply_to'] );
			$email_payload['custom_headers'] = [
				[
					'header' => 'Reply-To',
					'value'  => $reply_to['email'],
				],
			];
		}

		if ( ! empty( $processed_data['attachments'] ) ) {
			$email_payload['attachments'] = $this->get_attachments( $processed_data['attachments'] );
		}

		$json_payload = wp_json_encode( $email_payload );
		if ( false === $json_payload ) {
			throw new Exception( __( 'Failed to encode email payload to JSON.', 'suremails' ) );
		}

		$request_headers = $this->get_request_headers();
		$params          = [
			'body'        => $json_payload,
			'headers'     => $request_headers,
			'timeout'     => 30,
			'httpversion' => '1.1',
			'blocking'    => true,
		];

		try {
			$response = wp_safe_remote_post( $this->api_url, $params );

			if ( is_wp_error( $response ) ) {
				$result['message'] = sprintf(
					/* translators: %s: Error message from SMTP2GO */
					__( 'Email sending failed via SMTP2GO. Error: %s', 'suremails' ),
					$response->get_error_message()
				);
				$result['error_code'] = $response->get_error_code();
				return $result;
			}
				$response_body   = wp_remote_retrieve_body( $response );
				$response_code   = wp_remote_retrieve_response_code( $response );
				$server_response = json_decode( $response_body, true );

			if ( 200 === $response_code ) {
				$result['success']  = true;
				$result['message']  = __( 'Email sent successfully via SMTP2GO.', 'suremails' );
				$result['send']     = true;
				$result['email_id'] = $server_response['email_id'] ?? '';
			} else {
				$error_message     = ! empty( $server_response['error'] ) ? $server_response['error'] : __( 'SMTP2GO Server Error', 'suremails' );
				$result['message'] = sprintf(
					/* translators: %s: Error message from SMTP2GO */
					__( 'Email sending failed via SMTP2GO. Error: %s', 'suremails' ),
					$error_message
				);

				$result['error_code'] = $response_code;
			}
		} catch ( Exception $e ) {
			$result['message'] = sprintf(
				/* translators: %s: Exception message */
				__( 'Email sending failed via SMTP2GO. Error: %s', 'suremails' ),
				$e->getMessage()
			);
			$result['error_code'] = 500;
		}

		return $result;
	}

	/**
	 * Return the option configuration for SMTP2GO.
	 *
	 * @return array
	 */
	public static function get_options() {
		return [
			'title'             => __( 'SMTP2GO Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your SMTP2GO account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'display_name'      => __( 'SMTP2GO', 'suremails' ),
			'icon'              => 'SMTP2GoIcon',
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'api_key', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 50,
		];
	}

	/**
	 * Get the specific schema fields for SMTP2GO.
	 *
	 * @return array
	 */
	public static function get_specific_fields() {
		return [
			'api_key' => [
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => sprintf(       // translators: %s: https://app.smtp2go.com/login/ URL.
					__( 'Click on this link to generate an API Key from SMTP2GO - %1$sCreate API Key%2$s', 'suremails' ),
					'<a href="' . esc_url( 'https://app.smtp2go.com/sending/apikeys/' ) . '" target="_blank">',
					'</a>'
				),
				'label'       => __( 'API Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your SMTP2GO API Key', 'suremails' ),
				'encrypt'     => true,
			],
		];
	}

	/**
	 * Reusable function to process recipient arrays.
	 *
	 * This function handles recipients provided as either an array (with keys 'email' or 'from_email' and optional 'name')
	 * or as a plain email string. If a name is provided, it formats the recipient as "Name <email>".
	 *
	 * @param array $recipients Array of recipients.
	 * @return array Processed array of email strings.
	 */
	private static function process_recipients( $recipients ) {
		$result = [];
		if ( ! empty( $recipients ) && is_array( $recipients ) ) {
			foreach ( $recipients as $recipient ) {
				if ( is_array( $recipient ) ) {
					$email = isset( $recipient['email'] ) ? sanitize_email( $recipient['email'] ) : ( isset( $recipient['from_email'] ) ? sanitize_email( $recipient['from_email'] ) : '' );
					$name  = isset( $recipient['name'] ) ? sanitize_text_field( $recipient['name'] ) : '';
					if ( ! empty( $email ) ) {
						$result[] = ! empty( $name ) ? $name . ' <' . $email . '>' : $email;
					}
				} elseif ( is_string( $recipient ) ) {
					$result[] = sanitize_email( $recipient );
				}
			}
		}
		return $result;
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
				'filename' => $attachment_values['name'] ?? '',
				'fileblob' => $attachment_values['blob'] ?? '',
				'mimetype' => $attachment_values['type'] ?? '',
			];
		}
		return $result;
	}

	/**
	 * Build the request headers for the API request.
	 *
	 * @return array
	 */
	private function get_request_headers() {
		return [
			'Content-Type'      => 'application/json',
			'X-Smtp2go-Api-Key' => sanitize_text_field( $this->connection_data['api_key'] ),
			'timeout'           => 30,
			'httpversion'       => '1.1',
			'blocking'          => true,
		];
	}
}
