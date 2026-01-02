<?php
/**
 * SparkpostHandler.php
 *
 * Handles sending emails using SparkPost.
 *
 * @package SureMails\Inc\Emails\Providers\SparkPost
 */

namespace SureMails\Inc\Emails\Providers\SPARKPOST;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SparkpostHandler
 *
 * Implements the ConnectionHandler to handle SparkPost email sending and authentication.
 */
class SparkpostHandler implements ConnectionHandler {

	public const API_BASE_US = 'https://api.sparkpost.com/api/v1/';
	public const API_BASE_EU = 'https://api.eu.sparkpost.com/api/v1/';

	/**
	 * SparkPost connection data.
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
	 * Authenticate the SparkPost connection.
	 *
	 * Since SparkPost does not provide a direct authentication endpoint, this function
	 * simply saves the connection data and returns a success message.
	 *
	 * @throws \Exception If the API key, domain, or from email is missing in the connection data.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {

		$api_key    = sanitize_text_field( $this->connection_data['api_key'] ?? '' );
		$from_email = sanitize_email( $this->connection_data['from_email'] ?? '' );
		$region     = sanitize_text_field( $this->connection_data['region'] ?? '' );

		if ( empty( $api_key ) || empty( $from_email ) || empty( $region ) ) {
			return [
				'success' => false,
				'message' => __( 'Required fields are missing.', 'suremails' ),
			];
		}

		if ( ! filter_var( $from_email, FILTER_VALIDATE_EMAIL ) ) {
			return [
				'success' => false,
				'message' => __( 'The "From Email" is not a valid email address.', 'suremails' ),
			];
		}

		return [
			'success'    => true,
			'message'    => __( 'SparkPost connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send email using SparkPost.
	 *
	 * @param array $atts The email attributes.
	 * @param int   $log_id The log ID.
	 * @param array $connection The connection details.
	 * @param array $processed_data The processed email data.
	 *
	 * @throws \Exception If the API key is missing in the connection data.
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {

		$api_key = sanitize_text_field( $connection['api_key'] ?? '' );

		if ( empty( $api_key ) ) {
			return [
				'success' => false,
				'message' => __( 'API key is missing in the connection data.', 'suremails' ),
				'send'    => false,
			];
		}

		$email_payload = [
			'content'    => [
				'from'    => [
					'email' => $connection['from_email'] ?? '',
					'name'  => $connection['from_name'] ?? '',
				],
				'subject' => sanitize_text_field( $processed_data['subject'] ?? '' ),
				'text'    => wp_strip_all_tags( $atts['message'] ?? '' ),
			],
			'recipients' => $this->get_recipients( $processed_data ),
		];

		$content_type = $processed_data['headers']['content_type'];
		if ( ! empty( $content_type ) && ProviderHelper::is_html( $content_type ) ) {
			$email_payload['content']['html'] = $atts['message'] ?? '';
		}

		$reply_to = $processed_data['headers']['reply_to'];
		if ( ! empty( $reply_to ) ) {
			$reply_to                             = reset( $processed_data['headers']['reply_to'] );
			$email_payload['content']['reply_to'] = $this->process_reply_to_recipients( $reply_to );
		}

		if ( ! empty( $processed_data['headers']['cc'] ) ) {
			$cc = [];
			foreach ( $processed_data['headers']['cc'] as $recipient ) {
				if ( is_array( $recipient ) ) {
					$email = isset( $recipient['email'] ) ? sanitize_email( $recipient['email'] ) : '';
					if ( ! empty( $email ) ) {
						$cc[] = $email;
					}
				}
			}

			$email_payload['content']['headers']['CC'] = implode( ', ', $cc );
		}

		if ( ! empty( $processed_data['attachments'] ) ) {
			$email_payload['content']['attachments'] = $this->get_attachments( $processed_data['attachments'] );
		}

		$json_payload = wp_json_encode( $email_payload );
		if ( false === $json_payload ) {
			return [
				'success' => false,
				'message' => __( 'Failed to encode email payload to JSON.', 'suremails' ),
				'send'    => false,
			];
		}

		$region  = ! empty( $connection['region'] ) ? sanitize_text_field( $connection['region'] ) : 'US';
		$api_url = 'EU' === strtoupper( $region ) ? self::API_BASE_EU : self::API_BASE_US;

		$response = wp_safe_remote_post(
			$api_url . 'transmissions',
			[
				'body'    => $json_payload,
				'headers' => [
					'Authorization' => $api_key,
					'Content-Type'  => 'application/json',
				],
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message. */
					__( 'Error: %s', 'suremails' ),
					$response->get_error_message()
				),
				'send'    => false,
			];
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code === 200 ) {
			return [
				'success' => true,
				'message' => __( 'Email sent successfully via SparkPost.', 'suremails' ),
				'send'    => true,
			];
		}

		$error_message = $response_body['errors'][0]['message'] ?? __( 'Unknown error', 'suremails' );
		return [
			'success' => false,
			'message' => sprintf(
				/* translators: %s: Error message. */
				__( 'Email sending failed via SparkPost. Error: %s', 'suremails' ),
				$error_message
			),
			'send'    => false,
		];
	}

	/**
	 * Process reply-to recipients array.
	 *
	 * @param array $recipients Array of recipients.
	 * @return string
	 */
	public function process_reply_to_recipients( $recipients ) {

		$email = isset( $recipients['email'] ) ? sanitize_email( $recipients['email'] ) : '';

		if ( ! empty( $email ) ) {
			return $email;
		}
		return '';
	}

	/**
	 * Get the recipients for the email.
	 *
	 * @param array $processed_data The processed email data.
	 *
	 * @return array The recipients for the email.
	 */
	public function get_recipients( $processed_data ) {

		$to  = [];
		$cc  = [];
		$bcc = [];

		$to_emails = $processed_data['to'] ?? [];
		if ( ! empty( $to_emails ) ) {
			$to = self::process_recipients( $to_emails );
		}

		$cc_emails = $processed_data['headers']['cc'] ?? [];
		if ( ! empty( $cc_emails ) ) {
			$cc = $this->process_recipients( $cc_emails, $to_emails, true );
		}

		$bcc_emails = $processed_data['headers']['bcc'] ?? [];
		if ( ! empty( $bcc_emails ) ) {
			$bcc = $this->process_recipients( $bcc_emails, $to_emails, true );
		}

		return array_merge( $to, $cc, $bcc );
	}

	/**
	 * Get the SparkPost connection options.
	 *
	 * @return array The SparkPost connection options.
	 */
	public static function get_options() {
		return [
			'title'             => __( 'SparkPost Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your SparkPost account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'SparkPostIcon',
			'display_name'      => __( 'SparkPost', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'api_key', 'region', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 95,
		];
	}

	/**
	 * Get the specific fields for the SparkPost connection.
	 *
	 * @return array The specific fields for the SparkPost connection.
	 */
	public static function get_specific_fields() {
		return [
			'api_key' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'API Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your SparkPost API key', 'suremails' ),
				'encrypt'     => true,
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
				'placeholder' => __( 'Select your SpartPost region', 'suremails' ),
				'help_text'   => sprintf(       // translators: %s: www.mailgun.com/about/regions/ URL.
					__( 'Select the endpoint you want to use for sending messages. If you are subject to EU laws, you may need to use the EU region. %1$sLearn more at SparkPost.com%2$s', 'suremails' ),
					'<a href="' . esc_url( 'https://www.sparkpost.com/docs/getting-started/getting-started-sparkpost' ) . '" target="_blank">',
					'</a>'
				),
			],
		];
	}

	/**
	 * Process recipients array.
	 *
	 * @param array $recipients Array of recipients.
	 * @param array $to_mail Array of to email addresses.
	 * @param bool  $is_cc_bcc Whether the recipients are CC/BCC.
	 *
	 * @return array
	 */
	public static function process_recipients( $recipients, $to_mail = [], $is_cc_bcc = false ) {
		$result = [];
		foreach ( $recipients as $recipient ) {
			if ( is_array( $recipient ) ) {
				$email = isset( $recipient['email'] ) ? sanitize_email( $recipient['email'] ) : '';

				if ( ! empty( $email ) ) {
					$address = [
						'email' => $email,
					];

					if ( $is_cc_bcc && ! empty( $to_mail ) ) {
						$emails               = array_column( $to_mail, 'email' );
						$address['header_to'] = implode( ', ', $emails );
					}

					$result[]['address'] = $address;
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
				'name' => $attachment_values['name'] ?? '',
				'data' => $attachment_values['blob'] ?? '',
				'type' => $attachment_values['type'] ?? '',
			];
		}
		return $result;
	}
}
