<?php
/**
 * ZohoHandler.php
 *
 * Handles sending emails using Zoho Mail via direct API call.
 *
 * @package SureMails\Inc\Emails\Providers\Zoho
 */

namespace SureMails\Inc\Emails\Providers\ZOHO;

use SureMails\Inc\Emails\Handler\ConnectionHandler;
use SureMails\Inc\Emails\ProviderHelper;
use SureMails\Inc\Settings;
use SureMails\Inc\Utils\Utils;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ZohoHandler
 *
 * Implements the ConnectionHandler to handle Zoho Mail email sending and authentication.
 */
class ZohoHandler implements ConnectionHandler {

	/**
	 * OAuth token endpoint - will be determined dynamically based on region.
	 */
	private const TOKEN_URL_TEMPLATE = 'https://accounts.zoho.%s/oauth/v2/token';

	/**
	 * Zoho connection data.
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
	 * Authenticate the Zoho connection.
	 *
	 * This method handles the entire OAuth flow using direct API calls.
	 *
	 * @return array
	 */
	public function authenticate() {
		$result = [
			'success' => false,
			'message' => __( 'Failed to authenticate with Zoho Mail.', 'suremails' ),
		];

		$tokens    = [];
		$auth_code = $this->connection_data['auth_code'] ?? '';

		// First-time exchange of authorization code.
		if ( ! empty( $auth_code ) ) {

			$redirect_uri = $this->connection_data['redirect_url'] ?? Utils::get_admin_url();

			$body = [
				'code'          => $auth_code,
				'grant_type'    => 'authorization_code',
				'redirect_uri'  => $redirect_uri,
				'client_id'     => $this->connection_data['client_id'] ?? '',
				'client_secret' => $this->connection_data['client_secret'] ?? '',
			];

			$token_url = $this->get_token_url();

			$tokens = $this->api_call( $token_url, $body, 'POST' );

			if ( is_wp_error( $tokens ) ) {
				$result['message'] = __( 'Zoho OAuth Error: ', 'suremails' ) . $tokens->get_error_message();
				return $result;
			}

			// Refresh the tokens using existing refresh token.
		} elseif ( ! empty( $this->connection_data['refresh_token'] ) ) {
			$new_tokens = $this->get_new_token();
			if ( isset( $new_tokens['success'] ) && $new_tokens['success'] === false ) {
				$result['message'] = __( 'Failed to authenticate with Zoho Mail. ', 'suremails' ) . ( $new_tokens['message'] ?? '' );
				return $result;
			}
			$tokens = $new_tokens;
		} else {
			$result['message'] = __( 'No authorization code or refresh token provided. Please authenticate first.', 'suremails' );
			return $result;
		}

		// Validate token response.
		if ( ! is_array( $tokens ) || empty( $tokens['access_token'] ) || empty( $tokens['expires_in'] ) ) {
			$result['message'] = __( 'Failed to retrieve authentication tokens. Please try to re-authenticate.', 'suremails' );
			return $result;
		}

		// Merge in token data and timestamps.
		$result                 = array_merge( $result, $tokens );
		$result['expire_stamp'] = time() + $tokens['expires_in'];
		$result['success']      = true;
		$result['message']      = __( 'Successfully authenticated with Zoho Mail.', 'suremails' );

		$this->connection_data['access_token'] = $tokens['access_token'];
		$account_details                       = $this->get_account_details();

		if ( empty( $account_details ) || ! isset( $account_details['account_id'] ) || ! isset( $account_details['from_email'] ) ) {
			$result['message'] = __( 'Failed to get Zoho account details.', 'suremails' );
			return $result;
		}
		$result['account_id'] = $account_details['account_id'];
		$result['from_email'] = $account_details['from_email'];
		return $result;
	}

	/**
	 * Send email using Zoho Mail via direct API call.
	 *
	 * @param array $atts             Email attributes.
	 * @param int   $log_id           Log ID.
	 * @param array $connection_data  Connection data.
	 * @param array $processed_data   Processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection_data, $processed_data ) {

		$response = $this->check_tokens();
		if ( isset( $response['success'] ) && $response['success'] === false ) {
			return $response;
		}

		// Get account details from connection data or fetch them.
		$account_id = $this->connection_data['account_id'] ?? null;

		if ( empty( $account_id ) ) {
				return [
					'success' => false,
					'message' => __( 'Failed to get Zoho account details.', 'suremails' ),
				];
		}

		$from_name  = $this->connection_data['from_name'] ?? '';
		$from_email = $this->connection_data['from_email'] ?? '';

		if ( ! empty( $from_name ) ) {
			$from = $from_name . ' <' . $from_email . '>';
		} else {
			$from = $from_email;
		}

		$content_type = $processed_data['headers']['content_type'] ?? '';
		$is_html      = ProviderHelper::is_html( $content_type );
		$mail_format  = $is_html ? 'html' : 'plaintext';

		// Zoho Mail API expects specific payload format.
		$email_payload = [
			'fromAddress' => $from,
			'toAddress'   => $this->process_recipients( $processed_data['to'] ?? [] ),
			'subject'     => sanitize_text_field( $processed_data['subject'] ?? '' ),
			'content'     => $atts['message'] ?? '',
			'mailFormat'  => $mail_format,
		];

		$cc_emails = $processed_data['headers']['cc'] ?? [];
		if ( ! empty( $cc_emails ) ) {
			$cc_addresses = $this->process_recipients( $cc_emails );
			if ( ! empty( $cc_addresses ) ) {
				$email_payload['ccAddress'] = $cc_addresses;
			}
		}

		$bcc_emails = $processed_data['headers']['bcc'] ?? [];
		if ( ! empty( $bcc_emails ) ) {
			$bcc_addresses = $this->process_recipients( $bcc_emails );
			if ( ! empty( $bcc_addresses ) ) {
				$email_payload['bccAddress'] = $bcc_addresses;
			}
		}

		if ( ! empty( $processed_data['attachments'] ) ) {
			$email_payload['attachments'] = $this->get_attachments( $processed_data['attachments'] );
		}

		$body = wp_json_encode( $email_payload );
		if ( false === $body ) {
			return [
				'success' => false,
				'message' => __( 'Email sending failed via Zoho Mail. Failed to encode email message to JSON.', 'suremails' ),
			];
		}

		$args = [
			'headers' => [
				'Authorization' => 'Zoho-oauthtoken ' . ( $this->connection_data['access_token'] ?? '' ),
				'Content-Type'  => 'application/json',
			],
			'body'    => $body,
			'timeout' => 30,
		];

		// Try alternative endpoint format based on Zoho Mail API structure.
		$mail_domain = $this->get_mail_api_domain();
		$send_url    = "https://{$mail_domain}/api/accounts/{$account_id}/messages";

		$request = wp_remote_post( $send_url, $args );
		if ( is_wp_error( $request ) ) {
			return [
				'success' => false,
				'message' => $request->get_error_message(),
			];
		}

		$response_body = json_decode( wp_remote_retrieve_body( $request ), true );
		$status_code   = wp_remote_retrieve_response_code( $request );

		if ( $status_code === 200 && ! empty( $response_body ) ) {
			return [
				'success'  => true,
				'message'  => __( 'Email sent successfully via Zoho Mail.', 'suremails' ),
				'email_id' => $response_body['data']['messageId'] ?? '',
			];
		}

		$msg = __( 'Email sending failed via Zoho Mail.', 'suremails' );
		if ( ! empty( $response_body['data']['errorCode'] ) ) {
			$msg .= ' ' . $response_body['data']['errorCode'];
		}
		return [
			'success' => false,
			'message' => $msg,
		];
	}

	/**
	 * Get Zoho authorization URL.
	 *
	 * @param array $params The parameters passed in the API request.
	 * @return array Returns the Zoho auth URL or an error response.
	 */
	public static function get_auth_url( $params ) {
		$client_id     = isset( $params['client_id'] ) ? sanitize_text_field( $params['client_id'] ) : '';
		$client_secret = isset( $params['client_secret'] ) ? sanitize_text_field( $params['client_secret'] ) : '';
		$region        = isset( $params['region'] ) ? sanitize_text_field( $params['region'] ) : 'com';

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return [ 'error' => __( 'Client ID and Client Secret are required.', 'suremails' ) ];
		}

		$redirect_uri = isset( $params['redirect_url'] ) ? sanitize_text_field( $params['redirect_url'] ) : Utils::get_admin_url();
		$base_url     = sprintf( 'https://accounts.zoho.%s/oauth/v2/auth', $region );
		$auth_url     = $base_url . '?' . http_build_query(
			[
				'client_id'     => $client_id,
				'redirect_uri'  => $redirect_uri,
				'response_type' => 'code',
				'scope'         => 'ZohoMail.messages.CREATE ZohoMail.accounts.READ',
				'state'         => 'zoho',
				'access_type'   => 'offline',
				'prompt'        => 'consent',
			]
		);

		return [
			'auth_url' => $auth_url,
		];
	}

	/**
	 * Get the Zoho connection options.
	 *
	 * @return array The Zoho connection options.
	 */
	public static function get_options() {
		return [
			'title'             => __( 'Zoho Connection', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Zoho Mail account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'ZohoIcon',
			'display_name'      => __( 'Zoho Mail', 'suremails' ),
			'provider_type'     => 'free',
			'field_sequence'    => [
				'connection_title',
				'region',
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
			'help_texts'        => [
				'from_email' => __( "The 'From Email' must match your Zoho Mail account address. SureMail will automatically use 'from_email' set in Zoho account.", 'suremails' ),
			],
			'provider_sequence' => 140,
		];
	}

	/**
	 * Get the Zoho connection specific fields.
	 *
	 * @return array The Zoho specific fields.
	 */
	public static function get_specific_fields() {
		$redirect_uri = Utils::get_admin_url();

		return [
			'region'        => [
				'required'   => true,
				'datatype'   => 'string',
				'label'      => __( 'Zoho Region', 'suremails' ),
				'input_type' => 'select',
				'default'    => 'in',
				'options'    => [
					[
						'value' => 'com',
						'label' => __( 'United States - com', 'suremails' ),
					],
					[
						'value' => 'in',
						'label' => __( 'India - in', 'suremails' ),
					],
					[
						'value' => 'eu',
						'label' => __( 'Europe - eu', 'suremails' ),
					],
					[
						'value' => 'com.au',
						'label' => __( 'Australia - com.au', 'suremails' ),
					],
					[
						'value' => 'jp',
						'label' => __( 'Japan - jp', 'suremails' ),
					],
					[
						'value' => 'ca',
						'label' => __( 'Canada - ca', 'suremails' ),
					],
					[
						'value' => 'com.cn',
						'label' => __( 'China - com.cn', 'suremails' ),
					],
				],
				'help_text'  => __( 'Select your Zoho region. This should match the region where you created your Zoho account.', 'suremails' ),
			],
			'client_id'     => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Client ID', 'suremails' ),
				'input_type'  => 'text',
				'placeholder' => __( 'Enter your Zoho Client ID', 'suremails' ),
				'help_text'   => sprintf(
					// translators: %s: Documentation link.
					__( 'Get Client ID and Secret ID from Zoho Developer Console. Follow the Zoho Mail %s', 'suremails' ),
					'<a href="' . esc_url( 'https://suremails.com/docs/zoho?utm_campaign=suremails&utm_medium=suremails-dashboard' ) . '" target="_blank">' . __( 'documentation.', 'suremails' ) . '</a>'
				),
			],
			'client_secret' => [
				'required'    => true,
				'datatype'    => 'string',
				'label'       => __( 'Client Secret', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your Zoho Client Secret', 'suremails' ),
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
				'help_text'   => __( 'Copy the above URL and add it to the "Authorized Redirect URIs" section in your Zoho Developer Console. Ensure the URL matches exactly.', 'suremails' ),
				'copy_button' => true,
			],
			'auth_button'   => [
				'required'        => false,
				'datatype'        => 'string',
				'input_type'      => 'button',
				'button_text'     => __( 'Authenticate with Zoho', 'suremails' ),
				'alt_button_text' => __( 'Click here to re-authenticate', 'suremails' ),
				'on_click'        => [
					'params' => [
						'provider' => 'zoho',
						'client_id',
						'client_secret',
						'redirect_url',
						'region',
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
			'account_id'    => [
				'datatype' => 'string',
			],
		];
	}

	/**
	 * Get the correct token URL based on the region.
	 *
	 * @return string The token URL for the user's region.
	 */
	private function get_token_url() {
		$region = $this->connection_data['region'] ?? 'com';

		return sprintf( self::TOKEN_URL_TEMPLATE, $region );
	}

	/**
	 * Get the correct mail API domain based on the region.
	 * Based on WP Mail SMTP Pro implementation.
	 *
	 * @return string The mail API domain for the user's region.
	 */
	private function get_mail_api_domain() {
		// Get the region from connection data.
		$region = $this->connection_data['region'] ?? 'com';

		// Map regions to mail API domains (following WP Mail SMTP pattern).
		$domain_map = [
			'com'    => 'mail.zoho.com',
			'in'     => 'mail.zoho.in',
			'eu'     => 'mail.zoho.eu',
			'com.au' => 'mail.zoho.com.au',
			'jp'     => 'mail.zoho.jp',
			'ca'     => 'mail.zohocloud.ca',
			'com.cn' => 'mail.zoho.com.cn',
		];

		return $domain_map[ $region ] ?? 'mail.zoho.com';
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
			'method'  => $type,
			'timeout' => 15,
		];

		if ( ! empty( $body ) ) {
			if ( $type === 'POST' && strpos( $url, 'token' ) !== false ) {
				// For OAuth token requests, use application/x-www-form-urlencoded.
				$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
				$args['body']                    = http_build_query( $body );
			} else {
				// For API calls, use JSON.
				$args['headers']['Content-Type'] = 'application/json';
				$json                            = wp_json_encode( $body );
				if ( false === $json ) {
					return new WP_Error( 422, __( 'Failed to encode body to JSON.', 'suremails' ) );
				}
				$args['body'] = $json;
			}
		}

		$request = wp_remote_request( $url, $args );
		if ( is_wp_error( $request ) ) {
			return new WP_Error( 422, $request->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $request );
		$response_body = wp_remote_retrieve_body( $request );
		$response      = json_decode( $response_body, true );

		if ( $response_code !== 200 || ! empty( $response['error'] ) ) {
			$error_message = '';

			if ( ! empty( $response['error_description'] ) ) {
				$error_message = $response['error_description'];
			} elseif ( ! empty( $response['error'] ) ) {
				$error_message = is_string( $response['error'] ) ? $response['error'] : __( 'OAuth error occurred', 'suremails' );
			} elseif ( $response_code !== 200 ) {
				/* translators: %1$d: HTTP response code, %2$s: response body */
				$error_message = sprintf( __( 'HTTP %1$d: %2$s', 'suremails' ), $response_code, $response_body );
			} else {
				$error_message = __( 'Unknown error from Zoho API.', 'suremails' );
			}

			return new WP_Error( $response_code, $error_message );
		}

		return $response;
	}

	/**
	 * Check the tokens and refresh if necessary.
	 *
	 * @since 1.9.0
	 *
	 * @return array The result of the token check.
	 */
	private function check_tokens() {
		$result = [
			'success' => false,
			'message' => __( 'Failed to get new token from Zoho API.', 'suremails' ),
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
					__( 'Email sending failed via Zoho Mail. Failed to refresh Zoho token: %s', 'suremails' ),
					$new->get_error_message()
				);
				return $result;
			}

			if ( empty( $new['access_token'] ) || empty( $new['expires_in'] ) ) {
				$result['message'] = __( 'Failed to refresh Zoho token. Invalid token response received.', 'suremails' );
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
			'client_id'     => $this->connection_data['client_id'] ?? '',
			'client_secret' => $this->connection_data['client_secret'] ?? '',
			'refresh_token' => $refresh_token,
		];
		return $this->api_call( $this->get_token_url(), $body, 'POST' );
	}

	/**
	 * Get a new access token using the refresh token.
	 *
	 * @return array The new token data.
	 */
	private function get_new_token() {
		$refresh_token = $this->connection_data['refresh_token'] ?? '';
		if ( empty( $refresh_token ) ) {
			return [
				'success' => false,
				'message' => __( 'Refresh token not found.', 'suremails' ),
			];
		}

		$tokens = $this->client_refresh_token( $refresh_token );
		if ( is_wp_error( $tokens ) ) {
			return [
				'success' => false,
				'message' => $tokens->get_error_message(),
			];
		}
		return array_merge( $tokens, [ 'success' => true ] );
	}

	/**
	 * Get the Zoho account details.
	 * Following WP Mail SMTP Pro implementation pattern.
	 *
	 * @return array|false The account details array with 'account_id' and 'from_email' or false on failure.
	 */
	private function get_account_details() {
		// Check if access token is available.
		if ( empty( $this->connection_data['access_token'] ) ) {
			return false;
		}

		$mail_domain  = $this->get_mail_api_domain();
		$accounts_url = "https://{$mail_domain}/api/accounts";

		$args = [
			'headers' => [
				'Authorization' => 'Zoho-oauthtoken ' . $this->connection_data['access_token'],
				'Content-Type'  => 'application/json',
			],
			'method'  => 'GET',
			'timeout' => 15,
		];

		$request = wp_remote_request( $accounts_url, $args );
		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $request );
		$response_data = json_decode( $response_body, true );

		if ( ! is_array( $response_data ) || ! isset( $response_data['data'][0] ) ) {
			return false;
		}

		$account = $response_data['data'][0] ?? [];

		if ( empty( $account['accountId'] ) ) {
			return false;
		}

		$account_details = [
			'account_id' => $account['accountId'],
		];

		// Get the from email if available.
		if ( isset( $account['sendMailDetails'][0]['fromAddress'] ) && ! empty( $account['sendMailDetails'][0]['fromAddress'] ) ) {
			$account_details['from_email'] = $account['sendMailDetails'][0]['fromAddress'];
		}

		return $account_details;
	}

	/**
	 * Process attachments by uploading them to Zoho API first, then preparing the attachment array.
	 * Following Zoho Mail API requirements for attachment handling.
	 *
	 * @param array $attachments Array of attachment file paths.
	 * @return array
	 */
	private function get_attachments( $attachments ) {
		$result      = [];
		$mail_domain = $this->get_mail_api_domain();
		$account_id  = $this->connection_data['account_id'] ?? '';

		if ( empty( $account_id ) ) {
			return $result;
		}

		foreach ( $attachments as $attachment ) {
			$attachment_data = ProviderHelper::get_attachment( $attachment );

			if ( $attachment_data === false || empty( $attachment_data['content'] ) || empty( $attachment_data['name'] ) ) {
				continue;
			}

			// Upload the attachment via Zoho API first.
			$upload_url = "https://{$mail_domain}/api/accounts/{$account_id}/messages/attachments";
			$upload_url = add_query_arg( 'fileName', $attachment_data['name'], $upload_url );

			$upload_args = [
				'headers' => [
					'Authorization' => 'Zoho-oauthtoken ' . ( $this->connection_data['access_token'] ?? '' ),
					'Content-Type'  => 'application/octet-stream',
				],
				'body'    => $attachment_data['content'],
				'method'  => 'POST',
				'timeout' => 15,
			];

			$upload_response = wp_safe_remote_post( $upload_url, $upload_args );

			if ( is_wp_error( $upload_response ) || wp_remote_retrieve_response_code( $upload_response ) !== 200 ) {
				continue;
			}

			$upload_body = json_decode( wp_remote_retrieve_body( $upload_response ), true );

			if ( ! empty( $upload_body['data'] ) ) {
				$result[] = $upload_body['data'];
			}
		}

		return $result;
	}

	/**
	 * Process recipients array.
	 *
	 * @param array $recipients Array of recipients (each can be array with 'email' and 'name' keys or string email).
	 *
	 * @return string Comma-separated list of formatted email addresses.
	 */
	private function process_recipients( $recipients ) {
		$result = [];
		foreach ( $recipients as $recipient ) {
			if ( is_array( $recipient ) ) {
				$email = isset( $recipient['email'] ) ? sanitize_email( $recipient['email'] ) : '';
				$name  = $recipient['name'] ?? '';

				if ( empty( $email ) ) {
					continue;
				}

				if ( ! empty( $name ) ) {
					$result[] = $name . ' <' . $email . '>';
				} else {
					$result[] = $email;
				}
			}
		}

		return implode( ',', $result );
	}
}
