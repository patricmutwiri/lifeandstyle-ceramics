<?php
/**
 * GmailHandler.php
 *
 * Handles sending emails using Gmail via direct API call.
 *
 * @package SureMails\Inc\Emails\Providers\Gmail
 */

namespace SureMails\Inc\Emails\Providers\GMAIL;

use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Settings;
use SureMails\Inc\Utils\Utils;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GmailHandler
 *
 * Implements the ConnectionHandler to handle Gmail email sending and authentication.
 */
class GmailHandler implements ConnectionHandler {

	/**
	 * OAuth token endpoint.
	 */
	private const TOKEN_URL = 'https://accounts.google.com/o/oauth2/token';

	/**
	 * Gmail send API endpoint.
	 */
	private const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

	/**
	 * Gmail connection data.
	 *
	 * @var array
	 */
	private $connection_data;

	/**
	 * Constructor.
	 *
	 * Initializes connection data.
	 *
	 * @param array $connection_data The connection details.
	 */
	public function __construct( array $connection_data ) {
		// Ensure our connection data is available.
		$this->connection_data = $connection_data;
	}

	/**
	 * Authenticate the Gmail connection.
	 *
	 * This method handles the entire OAuth flow using direct API calls.
	 *
	 * @return void|array
	 */
	public function authenticate() {
		$result = [
			'success' => false,
			'message' => __( 'Failed to authenticate with Gmail.', 'suremails' ),
		];

		$tokens    = [];
		$auth_code = $this->connection_data['auth_code'] ?? '';

		// First-time exchange of authorization code.
		if ( ! empty( $auth_code ) ) {
			$body   = [
				'code'          => $auth_code,
				'grant_type'    => 'authorization_code',
				'redirect_uri'  => $this->connection_data['redirect_url'] ?? Utils::get_admin_url(),
				'client_id'     => $this->connection_data['client_id'],
				'client_secret' => $this->connection_data['client_secret'],
			];
			$tokens = $this->api_call( self::TOKEN_URL, $body, 'POST' );

			if ( is_wp_error( $tokens ) ) {
				$result['message'] = $tokens->get_error_message();
				return $result;
			}

			// Refresh the tokinss using existing refresh token.
		} elseif ( ! empty( $this->connection_data['refresh_token'] ) ) {
			$new_tokens = $this->get_new_token();
			if ( isset( $new_tokens['success'] ) && $new_tokens['success'] === false ) {
				return $result;
			}
			$tokens = $new_tokens;
		} else {
			$result['message'] = __( 'No authorization code or refresh token provided. Please authenticate first.', 'suremails' );
			return $result;
		}

		// Validate token response.
		if ( ! is_array( $tokens ) || empty( $tokens['access_token'] ) || empty( $tokens['expires_in'] ) ) {
			$result['message'] = __( 'Failed to retrieve authentication tokens. Please try to re-authenticate', 'suremails' );
			return $result;
		}

		// Merge in token data and timestamps.
		$result                 = array_merge( $result, $tokens );
		$result['expire_stamp'] = time() + $tokens['expires_in'];
		$result['success']      = true;
		$result['message']      = __( 'Successfully authenticated with Gmail.', 'suremails' );

		return $result;
	}

	/**
	 * Send email using Gmail via direct API call.
	 *
	 * @param array $atts             Email attributes.
	 * @param int   $log_id           Log ID.
	 * @param array $connection_data  Connection data.
	 * @param array $processed_data   Processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection_data, $processed_data ) {

		$result = [
			'success' => false,
			'message' => __( 'Email sending failed via Gmail.', 'suremails' ),
		];

		$response = $this->check_tokens();
		if ( isset( $response['success'] ) && $response['success'] === false ) {
			return $response;
		}

		$phpmailer = ConnectionManager::instance()->get_phpmailer();
		$phpmailer->setFrom( $this->connection_data['from_email'], $this->connection_data['from_name'] );
		if ( ! empty( $this->connection_data['return_path'] ) && $this->connection_data['return_path'] === true ) {
			$phpmailer->Sender = $this->connection_data['from_email']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		$phpmailer->preSend();
		$raw_message         = $phpmailer->getSentMIMEMessage();
		$encoded_raw_message = base64_encode( $raw_message );
		// Convert to URL-safe Base64 encoding.
		$encoded_raw_message = str_replace( [ '+', '/', '=' ], [ '-', '_', '' ], $encoded_raw_message );

		$body = wp_json_encode( [ 'raw' => $encoded_raw_message ] );
		if ( false === $body ) {
			return [
				'success' => false,
				'message' => __( 'Email sending failed via Gmail. Failed to encode email message to JSON.', 'suremails' ),
			];
		}
		$args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->connection_data['access_token'],
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'method'  => 'POST',
			'timeout' => 15,
		];

		$request = wp_remote_post( self::SEND_URL, $args );
		if ( is_wp_error( $request ) ) {
			return [
				'success' => false,
				'message' => $request->get_error_message(),
			];
		}

		$response_body = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! empty( $response_body['id'] ) ) {
			return [
				'success'  => true,
				'message'  => __( 'Email sent successfully via Gmail.', 'suremails' ),
				'email_id' => $response_body['id'],
			];
		}

		$msg = __( 'Email sending failed via Gmail.', 'suremails' );
		if ( ! empty( $response_body['error']['message'] ) ) {
			$msg .= ' ' . $response_body['error']['message'];
		}
		return [
			'success' => false,
			'message' => $msg,
		];
	}

	/**
	 * Get Gmail authorization URL.
	 *
	 * @param array $params The parameters passed in the API request.
	 * @return array Returns the Gmail auth URL or an error response.
	 */
	public static function get_auth_url( $params ) {
		$client_id     = isset( $params['client_id'] ) ? sanitize_text_field( $params['client_id'] ) : '';
		$client_secret = isset( $params['client_secret'] ) ? sanitize_text_field( $params['client_secret'] ) : '';

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return [ 'error' => __( 'Client ID and Client Secret are required.', 'suremails' ) ];
		}

		$redirect_uri = isset( $params['redirect_url'] ) ? sanitize_text_field( $params['redirect_url'] ) : Utils::get_admin_url();

		$auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query(
			[
				'client_id'              => $client_id,
				'redirect_uri'           => $redirect_uri,
				'response_type'          => 'code',
				'scope'                  => 'https://mail.google.com/',
				'state'                  => 'gmail',
				'access_type'            => 'offline',
				'approval_prompt'        => 'force',
				'include_granted_scopes' => 'true',
			]
		);

		return [
			'auth_url' => $auth_url,
		];
	}

	/**
	 * Get the Gmail connection options.
	 *
	 * @return array The Gmail connection options.
	 */
	public static function get_options() {
		return [
			'title'             => __( 'Gmail Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Gmail account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'GmailIcon',
			'display_name'      => __( 'Google Workspace / Gmail', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [
				'connection_title',
				'client_id',
				'client_secret',
				'redirect_url',
				'auth_button',
				'from_email',
				'force_from_email',
				'return_path',
				'from_name',
				'force_from_name',
				'priority',
				'auth_code',
			],
			'provider_sequence' => 27,
		];
	}

	/**
	 * Get the Gmail connection specific fields.
	 *
	 * @return array The Gmail specific fields.
	 */
	public static function get_specific_fields() {
		$redirect_uri = Utils::get_admin_url();

		return [
			'client_id'     => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Client ID', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter your Gmail Client ID', 'suremails' ),
				'help_text'   => sprintf(
					// translators: %s: Documentation link.
					__( 'Get Client ID and Secret ID from Google Cloud Platform. Follow the Gmail %s', 'suremails' ),
					'<a href="' . esc_url( 'https://suremails.com/docs/gmail?utm_campaign=suremails&utm_medium=suremails-dashboard' ) . '" target="_blank">' . __( 'documentation.', 'suremails' ) . '</a>'
				),
			],
			'client_secret' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Client Secret', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Gmail Client Secret', 'suremails' ),
				'encrypt'     => true,
			],
			'auth_code'     => [
				'required'    => false,
				'datatype'    => 'string',
				'input_type'  => 'password',
				'placeholder' => __( 'Paste the authorization code or refresh token here.', 'suremails' ),
				'encrypt'     => true,
				'class_name'  => 'hidden',
			],
			'redirect_url'  => [
				'required'    => false,
				'datatype'    => 'string',
				'label'       => __( 'Redirect URI', 'suremails' ),
				'input_type'  => 'text',
				'read_only'   => true,
				'default'     => $redirect_uri,
				'help_text'   => __( 'Copy the above URL and add it to the "Authorized Redirect URIs" section in your Google Cloud Project. Ensure the URL matches exactly.', 'suremails' ),
				'copy_button' => true,
			],
			'auth_button'   => [
				'required'        => false,
				'datatype'        => 'string',
				'input_type'      => 'button',
				'button_text'     => __( 'Authenticate with Google', 'suremails' ),
				'alt_button_text' => __( 'Click here to re-authenticate', 'suremails' ),
				'on_click'        => [
					'params' => [
						'provider' => 'gmail',
						'client_id',
						'client_secret',
						'redirect_url',
					],
				],
				'size'            => 'sm',
			],
			'return_path'   => [
				'default'     => true,
				'required'    => false,
				'datatype'    => 'boolean',
				'help_text'   => __( 'The Return Path is where bounce messages (failed delivery notices) are sent. Enable this to receive bounce notifications at the "From Email" address if delivery fails.', 'suremails' ),
				'label'       => __( 'Return Path', 'suremails' ),
				'input_type'  => 'checkbox',
				'placeholder' => __( 'Enter Return Path', 'suremails' ),
				'depends_on'  => [ 'from_email' ],
			],
			'refresh_token' => [
				'datatype'   => 'string',
				'input_type' => 'password',
				'encrypt'    => true,
			],
			'access_token'  => [
				'datatype' => 'string',
				'encrypt'  => true,
			],
		];
	}
	/**
	 * Make an API call.
	 *
	 * @param string $url   The URL to call.
	 * @param array  $body  The body arguments.
	 * @param string $type  The HTTP method to use.
	 *
	 * @return array|WP_Error The API response.
	 */
	private function api_call( $url, $body = [], $type = 'GET' ) {
		$args = [
			'headers' => [
				'Content-Type'              => 'application/json',
				'Content-Transfer-Encoding' => 'binary',
				'MIME-Version'              => '1.0',
			],
			'method'  => $type,
			'timeout' => 15,
		];

		if ( ! empty( $body ) ) {
			$json = wp_json_encode( $body );
			if ( false === $json ) {
				return new WP_Error( 422, __( 'Failed to encode body to JSON.', 'suremails' ) );
			}
			$args['body'] = $json;
		}

		$request = wp_remote_request( $url, $args );
		if ( is_wp_error( $request ) ) {
			return new WP_Error( 422, $request->get_error_message() );
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! empty( $response['error'] ) ) {

			$error = $response['error_description']
				?? ( $response['error']['message'] ?? __( 'Unknown error from Gmail API.', 'suremails' ) );
			return new WP_Error( 422, $error );
		}

		return $response;
	}

	/**
	 * Check the tokens and refresh if necessary.
	 *
	 * @since 1.4.0
	 *
	 * @return array The result of the token check.
	 */
	private function check_tokens() {
		$result = [
			'success' => false,
			'message' => __( 'Failed to get new token from Gmail API.', 'suremails' ),
		];

		if (
			empty( $this->connection_data['refresh_token'] )
			|| empty( $this->connection_data['access_token'] )
			|| empty( $this->connection_data['expire_stamp'] )
		) {
			return $result;
		}

		if ( time() > $this->connection_data['expire_stamp'] - 500 ) {
			$new = $this->client_refresh_token( $this->connection_data['refresh_token'] );
			if ( is_wp_error( $new ) ) {
				$result['message'] = sprintf(
					// translators: %s: Error message.
					__( 'Email sending failed via Gmail. Failed to refresh Gmail token: %s', 'suremails' ),
					$new->get_error_message()
				);
				return $result;
			}

			// Update stored tokens.
			$this->connection_data['access_token']  = $new['access_token'];
			$this->connection_data['expire_stamp']  = time() + $new['expires_in'];
			$this->connection_data['expires_in']    = $new['expires_in'];
			$this->connection_data['refresh_token'] = $new['refresh_token'] ?? $this->connection_data['refresh_token'];
			Settings::instance()->update_connection( $this->connection_data );
		}

		return [
			'success' => true,
			'message' => __( 'Successfully updated tokens.', 'suremails' ),
		];
	}

	/**
	 * Refresh the access token using the refresh token.
	 *
	 * @param string $refresh_token The refresh token.
	 * @return array|WP_Error The new token data.
	 */
	private function client_refresh_token( $refresh_token ) {
		$body = [
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->connection_data['client_id'],
			'client_secret' => $this->connection_data['client_secret'],
			'refresh_token' => $refresh_token,
		];
		return $this->api_call( self::TOKEN_URL, $body, 'POST' );
	}

	/**
	 * Get a new access token using the refresh token.
	 *
	 * @return array The new token data.
	 */
	private function get_new_token() {
		$tokens = $this->client_refresh_token( $this->connection_data['refresh_token'] );
		if ( is_wp_error( $tokens ) ) {
			return [
				'success' => false,
				'message' => $tokens->get_error_message(),
			];
		}
		return array_merge( $tokens, [ 'success' => true ] );
	}
}
