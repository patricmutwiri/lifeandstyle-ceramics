<?php
/**
 * AwsHandler.php
 *
 * Handles sending emails using AWS SES.
 *
 * @package SureMails\Inc\Emails\Providers\AWS
 */

namespace SureMails\Inc\Emails\Providers\AWS;

use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Emails\Handler\ConnectionHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AwsHandler
 *
 * Implements the ConnectionHandler to handle AWS SES email sending and authentication.
 */
class AwsHandler implements ConnectionHandler {
	/**
	 * Connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * AWS SES client.
	 *
	 * @var SimpleEmailService
	 */
	private $ses_client = null;

	/**
	 * AWS SES client.
	 *
	 * @var SimpleEmailServiceMessage
	 */
	private $message_client = null;

	/**
	 * Constructor.
	 *
	 * Initializes connection data.
	 *
	 * @param array $connection_data The connection details.
	 */
	public function __construct( array $connection_data ) {
		$this->connection_data = $connection_data;
		$this->ses_client      = new SimpleEmailService( $this->connection_data['username'], $this->connection_data['password'], $this->get_region_endpoint( $this->connection_data['region'] ) );
	}

	/**
	 * Authenticate the AWS SES connection, verifying that from_email is a verified identity.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {
		$result = [
			'success'    => false,
			'message'    => '',
			'error_code' => 200,
		];

		try {

			// Get all verified identities.
			$response = $this->ses_client->listVerifiedEmailAddresses();
			if ( is_wp_error( $response ) ) {
				/* translators: %s: AWS SES error message */
				$result['message'] = sprintf( __( 'AWS SES authentication failed: %s', 'suremails' ), $response->get_error_message() );
				return $result;
			}

			$verified_emails = $response['Addresses'];

			if ( in_array( $this->connection_data['from_email'], $verified_emails, true ) ) {
				$result['success'] = true;
				$result['message'] = __( 'AWS SES authentication successful.', 'suremails' );
				return $result;
			}

			$verified_domains = $response['domains'];

			$from_email_domain = substr( strrchr( $this->connection_data['from_email'], '@' ), 1 );

			$domain_matched = false;
			foreach ( $verified_domains as $verified_domain ) {
				$pos = strpos( $from_email_domain, $verified_domain );
				if ( $pos !== false && $pos === strlen( $from_email_domain ) - strlen( $verified_domain ) ) {
					$domain_matched = true;
					break;
				}
			}

			if ( $domain_matched ) {
				$result['success'] = true;
				$result['message'] = __( 'AWS SES authentication successful.', 'suremails' );
			} else {
				$result['message'] = __( 'AWS SES authentication failed: The from_email or its domain is not a verified identity.', 'suremails' );
			}
		} catch ( \Exception $e ) {
			$result['message'] = __( 'AWS SES authentication failed: ', 'suremails' ) . $e->getMessage();
		}

		return $result;
	}

	/**
	 * Send an email via AWS SES using sendRawEmail, including attachments if provided.
	 *
	 * @param array $atts       The email attributes, such as 'to', 'from', 'subject', 'message', 'headers', 'attachments', etc.
	 * @param int   $log_id     The log ID for the email.
	 * @param array $connection The connection details.
	 * @param array $processed_data The processed data.
	 * @throws \Exception If the email sending fails.
	 * @return array            The result of the email send operation.
	 */
	public function send( array $atts, $log_id, array $connection, array $processed_data ) {
		$result = [
			'success' => false,
			'message' => '',
			'send'    => false,

		];
		$this->message_client = new SimpleEmailServiceMessage();
		$phpmailer            = ConnectionManager::instance()->get_phpmailer();
		$from_email           = $connection['from_email'];
		$from_name            = ! empty( $connection['from_name'] ) ? $connection['from_name'] : __( 'WordPress', 'suremails' );

		$phpmailer->setFrom( $from_email, $from_name );
		if ( ! empty( $connection['return_path'] ) && $connection['return_path'] ) {
			//phpcs:ignore
			$phpmailer->Sender = $phpmailer->From;
		}
		$encoded_raw_message = null;
		if ( $phpmailer->preSend() ) {
			$encoded_raw_message = chunk_split( base64_encode( $phpmailer->getSentMIMEMessage() ), 76, "\n" );
		}
		try {
			$response = $this->ses_client->sendRawEmail( $encoded_raw_message );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			if ( ! empty( $response['MessageId'] ) ) {
				$result['success'] = true;
				$result['message'] = __( 'Email sent successfully via AWS SES.', 'suremails' );
				$result['send']    = true;
			} else {
				throw new \Exception( __( 'Failed to send email. No MessageId returned.', 'suremails' ) );
			}
		} catch ( \Exception $e ) {
			$result['success'] = false;
			/* translators: %s: AWS SES error message */
			$result['message'] = sprintf( __( 'Email sending failed via AWS SES: %s', 'suremails' ), $e->getMessage() );
		}
		return $result;
	}

	/**
	 * Return the option configuration for AWS.
	 *
	 * @return array
	 */
	public static function get_options() {
		return [
			'title'             => __( 'AWS Connection', 'suremails' ),
			'display_name'      => __( 'Amazon SES', 'suremails' ),
			'description'       => __( 'Enter the details below to connect with your Amazon SES account.', 'suremails' ),
			'fields'            => self::get_specific_fields(),
			'icon'              => 'AwsIcon',
			'provider_type'     => 'free',
			'field_sequence'    => [ 'connection_title', 'username', 'password', 'region', 'from_email', 'force_from_email', 'return_path', 'from_name', 'force_from_name', 'priority' ],
			'provider_sequence' => 10,
		];
	}

	/**
	 * Get the specific schema fields for AWS.
	 *
	 * @return array
	 */
	public static function get_specific_fields() {
		return [
			'username'    => [
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'Access Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your AWS access key', 'suremails' ),
				'encrypt'     => true,
			],
			'password'    => [
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'Secret Key', 'suremails' ),
				'input_type'  => 'password',
				'placeholder' => __( 'Enter your AWS secret key', 'suremails' ),
				'encrypt'     => true,
			],
			'return_path' => [
				'default'     => true,
				'required'    => false,
				'datatype'    => 'boolean',
				'help_text'   => __( 'The Return Path is where bounce messages (failed delivery notices) are sent. If it’s off, you might not get these messages. Turn it on to receive bounce notifications at the "From Email" address if delivery fails.', 'suremails' ),
				'label'       => __( 'Return Path', 'suremails' ),
				'input_type'  => 'checkbox',
				'placeholder' => __( 'Enter Return Path', 'suremails' ),
				'depends_on'  => [ 'from_email' ],
			],
			'region'      => [
				'default'     => [
					'label' => 'US East (Virginia) - us-east-1',
					'value' => 'us-east-1',
				],
				'required'    => true,
				'datatype'    => 'string',
				'help_text'   => '',
				'label'       => __( 'Region', 'suremails' ),
				'input_type'  => 'select',
				'placeholder' => __( 'Select Region', 'suremails' ),
				'options'     => [
					[
						'label' => 'US East (Virginia) - us-east-1',
						'value' => 'us-east-1',
					],
					[
						'label' => 'US East (Ohio) - us-east-2',
						'value' => 'us-east-2',
					],
					[
						'label' => 'US West (N. California) - us-west-1',
						'value' => 'us-west-1',
					],
					[
						'label' => 'US West (Oregon) - us-west-2',
						'value' => 'us-west-2',
					],
					[
						'label' => 'Africa (Cape Town) - af-south-1',
						'value' => 'af-south-1',
					],
					[
						'label' => 'Asia Pacific (Hong Kong) - ap-east-1',
						'value' => 'ap-east-1',
					],
					[
						'label' => 'Asia Pacific (Hyderabad) - ap-south-2',
						'value' => 'ap-south-2',
					],
					[
						'label' => 'Asia Pacific (Jakarta) - ap-southeast-3',
						'value' => 'ap-southeast-3',
					],
					[
						'label' => 'Asia Pacific (Melbourne) - ap-southeast-4',
						'value' => 'ap-southeast-4',
					],
					[
						'label' => 'Asia Pacific (Mumbai) - ap-south-1',
						'value' => 'ap-south-1',
					],
					[
						'label' => 'Asia Pacific (Osaka) - ap-northeast-3',
						'value' => 'ap-northeast-3',
					],
					[
						'label' => 'Asia Pacific (Seoul) - ap-northeast-2',
						'value' => 'ap-northeast-2',
					],
					[
						'label' => 'Asia Pacific (Singapore) - ap-southeast-1',
						'value' => 'ap-southeast-1',
					],
					[
						'label' => 'Asia Pacific (Sydney) - ap-southeast-2',
						'value' => 'ap-southeast-2',
					],
					[
						'label' => 'Asia Pacific (Tokyo) - ap-northeast-1',
						'value' => 'ap-northeast-1',
					],
					[
						'label' => 'Canada (Central) - ca-central-1',
						'value' => 'ca-central-1',
					],
					[
						'label' => 'Canada West (Calgary) - ca-west-1',
						'value' => 'ca-west-1',
					],
					[
						'label' => 'Europe (Frankfurt) - eu-central-1',
						'value' => 'eu-central-1',
					],
					[
						'label' => 'Europe (Ireland) - eu-west-1',
						'value' => 'eu-west-1',
					],
					[
						'label' => 'Europe (London) - eu-west-2',
						'value' => 'eu-west-2',
					],
					[
						'label' => 'Europe (Milan) - eu-south-1',
						'value' => 'eu-south-1',
					],
					[
						'label' => 'Europe (Paris) - eu-west-3',
						'value' => 'eu-west-3',
					],
					[
						'label' => 'Europe (Spain) - eu-south-2',
						'value' => 'eu-south-2',
					],
					[
						'label' => 'Europe (Stockholm) - eu-north-1',
						'value' => 'eu-north-1',
					],
					[
						'label' => 'Europe (Zurich) - eu-central-2',
						'value' => 'eu-central-2',
					],
					[
						'label' => 'Israel (Tel Aviv) - il-central-1',
						'value' => 'il-central-1',
					],
					[
						'label' => 'Middle East (Bahrain) - me-south-1',
						'value' => 'me-south-1',
					],
					[
						'label' => 'Middle East (UAE) - me-central-1',
						'value' => 'me-central-1',
					],
					[
						'label' => 'South America (São Paulo) - sa-east-1',
						'value' => 'sa-east-1',
					],
				],
			],
		];
	}

	/**
	 * Get the AWS region endpoint for SES.
	 *
	 * @param string $region The AWS region.
	 * @return string The SES region endpoint.
	 */
	private function get_region_endpoint( $region ) {
		return 'email.' . $region . '.amazonaws.com';
	}
}
