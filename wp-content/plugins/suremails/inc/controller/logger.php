<?php
/**
 * Logger.php
 *
 * Handles logging of email sends, failures, and related activities.
 *
 * @package SureMails\Inc\Logger
 */

namespace SureMails\Inc\Controller;

use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Utils\LogError;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Logger
 *
 * Handles logging of email sends, failures, and related activities.
 */
class Logger {

	use Instance;
	/**
	 * Status constant for failed emails.
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Status constant for sent emails.
	 */
	public const STATUS_SENT = 'sent';

	/**
	 * Status constant for pending emails.
	 */
	public const STATUS_PENDING = 'pending';

	/**
	 * Status constant for blocked emails.
	 */
	public const STATUS_BLOCKED = 'blocked';

	/**
	 * Log entry ID.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Sender email address.
	 *
	 * @var string|null
	 */
	private $email_from;

	/**
	 * Recipient email addresses.
	 *
	 * @var string|null
	 */
	private $email_to;

	/**
	 * Email subject.
	 *
	 * @var string|null
	 */
	private $subject;

	/**
	 * Email message content.
	 *
	 * @var string|null
	 */
	private $message;

	/**
	 * Email headers.
	 *
	 * @var string|null
	 */
	private $headers;

	/**
	 * Email attachments.
	 *
	 * @var string|null
	 */
	private $attachments;

	/**
	 * Email sending status.
	 *
	 * @var string|null
	 */
	private $status;

	/**
	 * Response message from email sending attempt.
	 *
	 * @var string|null
	 */
	private $response;

	/**
	 * Number of retry attempts.
	 *
	 * @var int
	 */
	private $retries = 0;

	/**
	 * Additional data or metadata.
	 *
	 * @var string|null
	 */
	private $extra;

	/**
	 * Number of times the email was resent.
	 *
	 * @var int
	 */
	private $resent = 0;

	/**
	 * Source of the email sending attempt.
	 *
	 * @var string|null
	 */
	private $source;

	/**
	 * Timestamp when the log entry was created.
	 *
	 * @var string|null
	 */
	private $created_at;

	/**
	 * Timestamp when the log entry was last updated.
	 *
	 * @var string|null
	 */
	private $updated_at;

	/**
	 * Settings helper instance.
	 *
	 * @var Settings
	 */
	private $settings_helper;

	/**
	 * EmailLog instance.
	 *
	 * @var EmailLog
	 */
	private $email_log;

	/**
	 * Logger constructor.
	 *
	 * Initializes the database connection, sets the table name, initializes Settings, and EmailLog.
	 */
	public function __construct() {
		$this->settings_helper = Settings::instance();
		$this->email_log       = EmailLog::instance(); // Initialize EmailLog instance.
	}

	/**
	 * Get the log entry ID.
	 *
	 * @return int|null The log entry ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the log entry ID.
	 *
	 * @param int|null $id The log entry ID.
	 * @return void The log entry ID.
	 */
	public function set_id( $id = null ) {
		$this->id = $id;
	}

	/**
	 * Get the sender email address.
	 *
	 * @return string|null Sender email address.
	 */
	public function get_email_from() {
		return $this->email_from;
	}

	/**
	 * Set the sender email address.
	 *
	 * @param string $email_from Sender email address.
	 * @return void Sender email address.
	 */
	public function set_email_from( $email_from ) {
		$this->email_from = $email_from;
	}

	/**
	 * Get the recipient email addresses.
	 *
	 * @return string|null Recipient email addresses.
	 */
	public function get_email_to() {
		return $this->email_to;
	}

	/**
	 * Set the recipient email addresses.
	 *
	 * @param string $email_to Recipient email addresses.
	 * @return void Recipient email addresses.
	 */
	public function set_email_to( $email_to ) {
		$this->email_to = $email_to;
	}

	/**
	 * Get the email subject.
	 *
	 * @return string|null Email subject.
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 * Set the email subject.
	 *
	 * @param string $subject Email subject.
	 * @return void Email subject.
	 */
	public function set_subject( $subject ) {
		$this->subject = $subject;
	}

	/**
	 * Get the email message content.
	 *
	 * @return string|null Email message content.
	 */
	public function get_message() {
		return $this->message;
	}

	/**
	 * Set the email message content.
	 *
	 * @param string $message Email message content.
	 * @return void Email message content.
	 */
	public function set_message( $message ) {
		$this->message = $message;
	}

	/**
	 * Get the email headers.
	 *
	 * @return string|null Email headers.
	 */
	public function get_headers() {
		return $this->headers;
	}

	/**
	 * Set the email headers.
	 *
	 * @param string $headers Email headers.
	 * @return void Email headers.
	 */
	public function set_headers( $headers ) {
		$this->headers = $headers;
	}

	/**
	 * Get the email attachments.
	 *
	 * @return string|null Email attachments.
	 */
	public function get_attachments() {
		return $this->attachments;
	}

	/**
	 * Set the email attachments.
	 *
	 * @param string $attachments Email attachments.
	 * @return void Email attachments.
	 */
	public function set_attachments( $attachments ) {
		$this->attachments = $attachments;
	}

	/**
	 * Get the email sending status.
	 *
	 * @return string|null Email sending status.
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Set the email sending status.
	 *
	 * @param string $status Email sending status.
	 * @return void Email sending status.
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * Get the response message from the email sending attempt.
	 *
	 * @return string|null Response message.
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Set the response message from the email sending attempt.
	 *
	 * @param string $response Response message.
	 * @return void Response message.
	 */
	public function set_response( $response ) {
		$this->response = $response;
	}

	/**
	 * Get the number of retry attempts.
	 *
	 * @return int Number of retry attempts.
	 */
	public function get_retries() {
		return $this->retries;
	}

	/**
	 * Set the number of retry attempts.
	 *
	 * @param int $retries Number of retry attempts.
	 * @return void Number of retry attempts.
	 */
	public function set_retries( $retries ) {
		$this->retries = $retries;
	}

	/**
	 * Get the additional data or metadata.
	 *
	 * @return string|null Additional data.
	 */
	public function get_extra() {
		return $this->extra;
	}

	/**
	 * Set the additional data or metadata.
	 *
	 * @param string $extra Additional data.
	 * @return void Additional data.
	 */
	public function set_extra( $extra ) {
		$this->extra = $extra;
	}

	/**
	 * Get the number of times the email was resent.
	 *
	 * @return int Number of resends.
	 */
	public function get_resent() {
		return $this->resent;
	}

	/**
	 * Set the number of times the email was resent.
	 *
	 * @param int $resent Number of resends.
	 * @return void Number of resends.
	 */
	public function set_resent( $resent ) {
		$this->resent = $resent;
	}

	/**
	 * Get the source of the email sending attempt.
	 *
	 * @return string|null Source.
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Set the source of the email sending attempt.
	 *
	 * @param string $source Source.
	 * @return void Source.
	 */
	public function set_source( $source ) {
		$this->source = $source;
	}

	/**
	 * Get the timestamp when the log entry was created.
	 *
	 * @return string|null Creation timestamp.
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * Set the timestamp when the log entry was created.
	 *
	 * @param string $created_at Creation timestamp.
	 * @return void Creation timestamp.
	 */
	public function set_created_at( $created_at ) {
		$this->created_at = $created_at;
	}

	/**
	 * Get the timestamp when the log entry was last updated.
	 *
	 * @return string|null Update timestamp.
	 */
	public function get_updated_at() {
		return $this->updated_at;
	}

	/**
	 * Set the timestamp when the log entry was last updated.
	 *
	 * @param string $updated_at Update timestamp.
	 * @return void Update timestamp.
	 */
	public function set_updated_at( $updated_at ) {
		$this->updated_at = $updated_at;
	}

	/**
	 * Prepares log data by merging provided data with default values.
	 *
	 * @param array $args The data to merge with defaults.
	 * @return array The prepared log data.
	 */
	public function prepare_log_data( array $args = [] ): array {
		$defaults = [
			'email_from'  => '',
			'email_to'    => '',
			'subject'     => '',
			'body'        => '',
			'headers'     => '',
			'attachments' => [],
			'status'      => self::STATUS_PENDING,
			'response'    => [],
			'connection'  => '',
			'meta'        => [
				'retry'         => 0,
				'resend'        => 0,
				'content_guard' => '',
			],
		];

		return wp_parse_args( $args, $defaults );
	}
	/**
	 * Logs an email attempt.
	 *
	 * @param array $data The data to log.
	 * @return int|WP_Error|false The ID of the log entry on success, WP_Error on failure, or false if logging is disabled.
	 */
	public function log_email( array $data ) {
		// Check if logging is enabled.
		$log_emails = $this->settings_helper->get_settings( 'log_emails', 'yes' );

		if ( strtolower( $log_emails ) !== 'yes' ) {
			// Logging is disabled. Optionally, return false or a WP_Error.
			return false;
		}

		// Prepare data for insertion.
		$insert_data = [
			'email_from'  => $data['email_from'] ?? '',
			'email_to'    => $data['email_to'] ?? '',
			'subject'     => $data['subject'] ?? '',
			'body'        => $data['body'] ?? '',
			'headers'     => $data['headers'] ?? '',
			'attachments' => $data['attachments'] ?? [],
			'status'      => $data['status'] ?? self::STATUS_PENDING,
			'response'    => $data['response'] ?? '',
			'meta'        => $data['meta'] ?? [
				'retry'  => 0,
				'resend' => 0,
			],
			'connection'  => $data['connection'] ?? '',
			'created_at'  => current_time( 'mysql' ),
		];

		// Insert the log entry using the EmailLog class.
		try {
			$inserted_id = $this->email_log->insert( $insert_data );

			if ( ! $inserted_id ) {
				// Capture and log the error.
				LogError::instance()->log_error( __( 'Failed to insert email log. Database error.', 'suremails' ) );
				return new WP_Error( 'logger_insert_failed', 'Failed to insert email log. Database error.' );
			}

			return (int) $inserted_id;
		} catch ( \Exception $e ) {
			// Log the exception for debugging purposes.
			LogError::instance()->log_error( $e->getMessage() );
			return new WP_Error( 'logger_insert_exception', __( 'An exception occurred: ', 'suremails' ) . $e->getMessage() );
		}
	}

	/**
	 * Updates an existing email log entry.
	 *
	 * @param int   $log_id The ID of the log entry to update.
	 * @param array $data   The data to update.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_log( int $log_id, array $data ) {

		// Update the log entry using the EmailLog class.
		try {
			$update_result = $this->email_log->update( $log_id, $data );

			if ( is_wp_error( $update_result ) ) {
				return $update_result;
			}
			if ( $update_result ) {
				return true;
			}

			// If update_result is false, log the error.
			// translators: %d: Log ID.
			LogError::instance()->log_error( sprintf( __( 'Failed to update log ID %d.', 'suremails' ), $log_id ) );
			// translators: %d: Log ID.
			return new WP_Error( 'logger_update_failed', sprintf( __( 'Failed to update log ID %d.', 'suremails' ), $log_id ) );
		} catch ( \Exception $e ) {
			// translators: 1: Log ID, 2: Exception message.
			LogError::instance()->log_error( sprintf( __( 'Exception while updating log ID %1$d: %2$s', 'suremails' ), $log_id, $e->getMessage() ) );
			// translators: %s: Exception message.
			return new WP_Error( 'logger_update_exception', sprintf( __( 'An exception occurred: %s', 'suremails' ), $e->getMessage() ) );
		}
	}

	/**
	 * Retrieves a specific email log entry by its ID.
	 *
	 * @param int $log_id The ID of the log entry to retrieve.
	 * @return array|WP_Error|false The log entry as an associative array, WP_Error on failure, or false if not found.
	 */
	public function get_log( int $log_id ) {
		if ( empty( $log_id ) ) {
			return new WP_Error( 'logger_get_invalid_id', __( 'Invalid log ID provided.', 'suremails' ) );
		}

		// Use the EmailLog class to retrieve the log entry.
		$logs = $this->email_log->get(
			[
				'select' => '*',
				'where'  => [ 'id' => $log_id ],
				'limit'  => 1,
			]
		);

		if ( $logs === false ) {
			// translators: %d: Log ID.
			LogError::instance()->log_error( sprintf( __( 'Failed to retrieve log ID %d.', 'suremails' ), $log_id ) );
			// translators: %d: Log ID.
			return new WP_Error( 'logger_get_failed', sprintf( __( 'Failed to retrieve log ID %d.', 'suremails' ), $log_id ) );
		}

		if ( empty( $logs ) ) {
			return false; // No log entry found with the provided ID.
		}
		return $logs[0]; // Return the first (and only) log entry.
	}
}
