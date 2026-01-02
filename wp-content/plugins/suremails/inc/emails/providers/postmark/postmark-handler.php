<?php
/**
 * PostmarkHandler.php
 *
 * Handles sending emails using Postmark.
 *
 * @package SureMails\Inc\Emails\Providers\Postmark
 */

namespace SureMails\Inc\Emails\Providers\POSTMARK;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PostmarkHandler
 *
 * Implements the ConnectionHandler to handle Postmark email sending and authentication.
 */
class PostmarkHandler implements ConnectionHandler {

	/**
	 * Postmark connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Request parameters used for every API call.
	 *
	 * @var array
	 */
	protected $params = [];

	/**
	 * The Postmark API URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.postmarkapp.com/email';

	/**
	 * Constructor.
	 *
	 * @param array $connection_data The connection details.
	 */
	public function __construct( array $connection_data ) {
		$this->connection_data = $connection_data;

		// Initialize the protected $params property with default settings and headers.
		$this->params['headers'] = [
			'Accept'                  => 'application/json',
			'Content-Type'            => 'application/json',
			'X-Postmark-Server-Token' => sanitize_text_field( $this->connection_data['server_token'] ?? '' ),
		];
	}

	/**
	 * Authenticate the Postmark connection by verifying that from_email is a verified sender.
	 *
	 * @return array
	 */
	public function authenticate() {
		return [
			'success'    => true,
			'message'    => __( 'Postmark connection saved successfully.', 'suremails' ),
			'error_code' => 200,
		];
	}

	/**
	 * Send email using Postmark.
	 *
	 * @param array $atts           The email attributes.
	 * @param int   $log_id         The log ID.
	 * @param array $connection     The connection details.
	 * @param array $processed_data The processed email data.
	 *
	 * @return array
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,
		];

		$from_name  = $connection['from_name'] ?? '';
		$from_email = sanitize_email( $connection['from_email'] );
		$from       = ! empty( $from_name ) ? $from_name . ' <' . $from_email . '>' : $from_email;

		$to  = implode( ', ', $this->process_recipients( $processed_data['to'] ?? [] ) );
		$cc  = implode( ', ', $this->process_recipients( $processed_data['headers']['cc'] ?? [] ) );
		$bcc = implode( ', ', $this->process_recipients( $processed_data['headers']['bcc'] ?? [] ) );

		$subject   = sanitize_text_field( $atts['subject'] ?? '' );
		$html_body = $atts['message'] ?? '';
		$text_body = wp_strip_all_tags( $html_body );

		$payload = [
			'From'     => $from,
			'To'       => $to,
			'Subject'  => $subject,
			'TextBody' => $text_body,
		];

		if ( ! empty( $cc ) ) {
			$payload['Cc'] = $cc;
		}
		if ( ! empty( $bcc ) ) {
			$payload['Bcc'] = $bcc;
		}

		if ( ! empty( $processed_data['headers']['reply_to'] ) && is_array( $processed_data['headers']['reply_to'] ) ) {
			$reply_to = reset( $processed_data['headers']['reply_to'] );
			if ( is_array( $reply_to ) && isset( $reply_to['email'] ) ) {
				$payload['ReplyTo'] = sanitize_email( $reply_to['email'] );
			} elseif ( is_string( $reply_to ) ) {
				$payload['ReplyTo'] = sanitize_email( $reply_to );
			}
		}

		if ( ! empty( $processed_data['attachments'] ) && is_array( $processed_data['attachments'] ) ) {
			$payload['Attachments'] = $this->get_attachments( $processed_data['attachments'] );
		}

		$payload['MessageStream'] = sanitize_text_field( $connection['message_stream'] ?? 'outbound' );

		$content_type = isset( $processed_data['headers']['content_type'] )
			? strtolower( $processed_data['headers']['content_type'] )
			: 'text/html';

		if ( $content_type === 'text/html' ) {
			$payload['HtmlBody'] = $html_body;
		}

		$json_payload = wp_json_encode( $payload );
		if ( false === $json_payload ) {
			$result['message'] = __( 'Email sending failed via Postmark. Failed to encode email payload to JSON.', 'suremails' );
			return $result;
		}

		$from_regex      = "/(\"From\": \"[a-zA-Z\\d]+)*[\\\\]{2,}'/";
		$cleaned_payload = preg_replace( $from_regex, "'", $json_payload, 1 );

		// Ensure cleaned_payload is always a string (preg_replace can return null on error).
		if ( $cleaned_payload === null ) {
			$cleaned_payload = $json_payload;
		}

		$this->params['body'] = $cleaned_payload;

		$response = wp_safe_remote_post( $this->api_url, $this->params );
		if ( is_wp_error( $response ) ) {
			$result['message']    = __( 'Email sending failed via Postmark. ', 'suremails' ) . $response->get_error_message();
			$result['error_code'] = $response->get_error_code();
			return $result;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( 200 === $response_code && isset( $response_data['MessageID'] ) ) {
			$result['success']  = true;
			$result['message']  = __( 'Email sent successfully via Postmark.', 'suremails' );
			$result['send']     = true;
			$result['email_id'] = $response_data['MessageID'];
		} else {
			$error_message        = $response_data['Message'] ?? __( 'Unknown error.', 'suremails' );
			$result['message']    = __( 'Email sending failed via Postmark. ', 'suremails' ) . $error_message;
			$result['error_code'] = $response_code;
		}

		return $result;
	}

	/**
	 * Get the Postmark connection options.
	 *
	 * @return array
	 */
	public static function get_options() {
		return [
			'title'             => __( 'Postmark Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Postmark account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'PostMarkIcon',
			'display_name'      => __( 'Postmark', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'server_token', 'message_stream', 'from_email', 'force_from_email', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 38,
		];
	}

	/**
	 * Get the specific fields for the Postmark connection.
	 *
	 * @return array
	 */
	public static function get_specific_fields() {
		return [
			'server_token'   => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Server Token', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Postmark Server Token', 'suremails' ),

				'help_text'   => sprintf(
					// translators: %s: postmark URL.
					__( 'Obtain your API key from Postmark. You can find your API key under the API Tokens tab in your account. %1$sClick here%2$s', 'suremails' ),
					'<a href="' . esc_url( 'https://account.postmarkapp.com/servers' ) . '" target="_blank">',
					'</a>'
				),
				'encrypt'     => true,
			],
			'message_stream' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Message Stream ID', 'suremails' ),
				'input_type'  => 'text',
				'default'     => 'outbound',
				'help_text'   => __( 'The Message Stream ID is optional. If not provided, the default outbound stream (Transactional Stream) will be used.', 'suremails' ),
				'placeholder' => __( 'Enter your Postmark Message Stream', 'suremails' ),
			],
		];
	}

	/**
	 * Process recipients into an array of formatted email strings.
	 *
	 * @param array $recipients The recipients to process.
	 * @return array The processed recipients.
	 */
	private function process_recipients( array $recipients ) {
		$result = [];
		if ( ! empty( $recipients ) && is_array( $recipients ) ) {
			foreach ( $recipients as $recipient ) {
				if ( is_array( $recipient ) ) {
					$email = isset( $recipient['email'] ) ? sanitize_email( $recipient['email'] ) : '';
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
	 * Process attachments into a Postmark-compatible attachments array.
	 *
	 * @param array $attachments The attachments to process.
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
				'Name'        => $attachment_values['name'] ?? '',
				'Content'     => $attachment_values['blob'] ?? '',
				'ContentType' => $attachment_values['type'] ?? '',
			];
		}
		return $result;
	}
}
