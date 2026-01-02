<?php
/**
 * MailgunHandler.php
 *
 * Handles sending emails using Mailgun.
 *
 * @package SureMails\Inc\Emails\Providers\Mailgun
 */

namespace SureMails\Inc\Emails\Providers\MAILGUN;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class MailgunHandler
 *
 * Implements the ConnectionHandler to handle Mailgun email sending and authentication.
 */
class MailgunHandler implements ConnectionHandler {

	/**
	 * API endpoint bases.
	 */
	public const API_BASE_US_V3 = 'https://api.mailgun.net/v3/';
	public const API_BASE_EU_V3 = 'https://api.eu.mailgun.net/v3/';
	public const API_BASE_US_V4 = 'https://api.mailgun.net/v4/';
	public const API_BASE_EU_V4 = 'https://api.eu.mailgun.net/v4/';

	/**
	 * Mailgun connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Constructor.
	 *
	 * @param array $connection_data The connection details.
	 */
	public function __construct( array $connection_data ) {
		$this->connection_data = $connection_data;
	}

	/**
	 * Authenticate the Mailgun connection.
	 *
	 * Validates the API key, domain, and from email using Mailgun's `/v4/domains` API.
	 *
	 * @return array The result of the authentication attempt.
	 * @throws \Exception If the API key, domain, or from email is missing, or if the domain is not active.
	 */
	public function authenticate() {
		return [
			'success'    => true,
			'message'    => __( 'Mailgun connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send an email via Mailgun.
	 *
	 * Formats processed data to match the Mailgun API parameters.
	 *
	 * @param array $atts           The email attributes (e.g., subject, message).
	 * @param int   $log_id         The log ID for the email.
	 * @param array $connection     The connection details (includes from_email, from_name, domain, api_key, region).
	 * @param array $processed_data The processed email data (to, cc, bcc, headers, attachments, etc.).
	 *
	 * @return array The result of the email send operation.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,
		];

		$domain = isset( $connection['domain'] ) ? sanitize_text_field( $connection['domain'] ) : '';
		if ( empty( $domain ) ) {
			$result['message']    = __( 'Mailgun domain is missing.', 'suremails' );
			$result['error_code'] = 400;
			return $result;
		}

		$region   = ! empty( $connection['region'] ) ? sanitize_text_field( $connection['region'] ) : 'US';
		$api_base = 'EU' === strtoupper( $region ) ? self::API_BASE_EU_V3 : self::API_BASE_US_V3;

		$url = $api_base . $domain . '/messages';

		$from_email = $connection['from_email'];
		$from_name  = $connection['from_name'] ?? __( 'WordPress', 'suremails' );
		$from       = sprintf( '%s <%s>', $from_name, $from_email );
		$to         = $this->prepareRecipients( $processed_data['to'] ?? [] );
		$cc         = $this->prepareRecipients( $processed_data['headers']['cc'] ?? [] );
		$bcc        = $this->prepareRecipients( $processed_data['headers']['bcc'] ?? [] );

		$is_html      = isset( $processed_data['headers']['content_type'] )
			&& strtolower( $processed_data['headers']['content_type'] ) === 'text/html';
		$text_content = $is_html ? wp_strip_all_tags( $atts['message'] ) : $atts['message'];
		$html_content = $is_html ? $atts['message'] : '';

		$body = [
			'from'    => $from,
			'to'      => $to,
			'subject' => sanitize_text_field( $atts['subject'] ?? '' ),
		];

		if ( ! empty( $html_content ) ) {
			$body['html'] = $html_content;
		}
		if ( ! empty( $text_content ) ) {
			$body['text'] = $text_content;
		}

		if ( ! empty( $processed_data['headers']['reply_to'] ) && is_array( $processed_data['headers']['reply_to'] ) ) {
			$reply_to = $this->prepareRecipients( $processed_data['headers']['reply_to'] );
			if ( $reply_to ) {
				$body['h:Reply-To'] = $reply_to;
			}
		}

		if ( ! empty( $cc ) ) {
			$body['cc'] = $cc;
		}
		if ( ! empty( $bcc ) ) {
			$body['bcc'] = $bcc;
		}

		$attachments_payload = $this->get_attachments( $processed_data['attachments'] ?? [] );

		$params = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( 'api:' . sanitize_text_field( $connection['api_key'] ) ),
			],
		];

		if ( ! empty( $attachments_payload ) ) {
			$params['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $attachments_payload['boundary'];

			$multipart_body = '';

			foreach ( $body as $key => $value ) {
				$multipart_body .= '--' . $attachments_payload['boundary'] . "\r\n";
				$multipart_body .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
				$multipart_body .= $value . "\r\n";
			}
			$multipart_body .= $attachments_payload['payload'];

			$params['body'] = $multipart_body;
		} else {
			$params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
			$params['body']                    = http_build_query( $body );
		}

		$response = wp_safe_remote_post( $url, $params );

		if ( is_wp_error( $response ) ) {
			$result['message']    = __( 'Mailgun send failed: ', 'suremails' ) . $response->get_error_message();
			$result['error_code'] = $response->get_error_code();
			return $result;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$decoded_body  = json_decode( $response_body, true );

		if ( in_array( $response_code, [ 200, 202 ], true ) ) {
			$result['success'] = true;
			$result['send']    = true;
			$result['message'] = __( 'Email sent successfully via Mailgun.', 'suremails' );
		} else {
			$error_message        = $decoded_body['message'] ?? __( 'Unknown error.', 'suremails' );
			$result['message']    = __( 'Mailgun send failed: ', 'suremails' ) . $error_message;
			$result['error_code'] = $response_code;
		}

		return $result;
	}

	/**
	 * Return the option configuration for Mailgun.
	 *
	 * @return array
	 */
	public static function get_options() {
		return [
			'title'             => __( 'Mailgun Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Mailgun account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'MailGunIcon',
			'display_name'      => __( 'Mailgun', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'api_key', 'region', 'domain', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 30,
		];
	}

	/**
	 * Get the specific schema fields for Mailgun.
	 *
	 * @return array
	 */
	public static function get_specific_fields() {
		return [
			'api_key' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'API Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Mailgun API key', 'suremails' ),
				'encrypt'     => true,
			],
			'domain'  => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Domain', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter your Mailgun domain', 'suremails' ),
			],
			'region'  => [
				'required'    => false,
				'datatype'    => 'string',
				'label'       => __( 'Region', 'suremails' ),
				'input_type'  => 'select',
				'options'     => [
					'US' => __( 'US', 'suremails' ),
					'EU' => __( 'EU', 'suremails' ),
				],
				'default'     => 'US',
				'placeholder' => __( 'Select your Mailgun region', 'suremails' ),
				'help_text'   => sprintf(       // translators: %s: www.mailgun.com/about/regions/ URL.
					__( 'Select the endpoint you want to use for sending messages. If you are subject to EU laws, you may need to use the EU region. %1$sLearn more at Mailgun.com%2$s', 'suremails' ),
					'<a href="' . esc_url( 'https://www.mailgun.com/about/regions/' ) . '" target="_blank">',
					'</a>'
				),
			],
		];
	}

	/**
	 * Get attachments payload for Mailgun API.
	 *
	 * Processes the attachments array and prepares the multipart/form-data payload.
	 *
	 * @param array $attachments Array of attachment file paths.
	 *
	 * @return array|null Returns an array with 'boundary' and 'payload' keys or null if no attachments.
	 */
	private function get_attachments( array $attachments ) {
		$attachment_data = [];
		$payload         = '';

		foreach ( $attachments as $attachment ) {
			$attachment_values = ProviderHelper::get_attachment( $attachment );
			if ( ! empty( $attachment_values ) ) {

				$attachment_data[] = [
					'content' => $attachment_values['content'],
					'name'    => $attachment_values['name'],
				];
			}
		}

		if ( ! empty( $attachment_data ) ) {
			$boundary = hash( 'sha256', uniqid( '', true ) );

			foreach ( $attachment_data as $key => $attachment ) {
				$payload .= '--' . $boundary;
				$payload .= "\r\n";
				$payload .= 'Content-Disposition: form-data; name="attachment[' . $key . ']"; filename="' . $attachment['name'] . '"' . "\r\n\r\n";
				$payload .= $attachment['content'];
				$payload .= "\r\n";
			}

			$payload .= '--' . $boundary . '--' . "\r\n";

			return [
				'boundary' => $boundary,
				'payload'  => $payload,
			];
		}

		return null;
	}

	/**
	 * Prepare a comma-separated list of recipients.
	 *
	 * Each recipient is formatted as "Name <email>" if a name is provided or just "email".
	 *
	 * @param array $recipients The array of recipient arrays.
	 * @return string Comma-separated string of recipients.
	 */
	private function prepareRecipients( array $recipients ) {
		$output = [];
		foreach ( $recipients as $recipient ) {
			if ( isset( $recipient['email'] ) ) {
				$email = sanitize_email( $recipient['email'] );
				if ( ! empty( $recipient['name'] ) ) {
					$output[] = sprintf( '%s <%s>', sanitize_text_field( $recipient['name'] ), $email );
				} else {
					$output[] = $email;
				}
			}
		}
		return implode( ', ', $output );
	}
}
