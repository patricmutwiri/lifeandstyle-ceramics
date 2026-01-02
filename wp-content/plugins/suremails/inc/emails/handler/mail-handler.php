<?php
/**
 * MailHandler.php
 *
 * Handles sending emails using different connections and logging the activities.
 *
 * @package SureMails\Inc\Emails\Handler
 */

namespace SureMails\Inc\Emails\Handler;

use SureMails\Inc\Admin\Crons;
use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Controller\ContentGuard;
use SureMails\Inc\Controller\Logger;
use SureMails\Inc\Emails\DefaultMailHandler;
use SureMails\Inc\Utils\LogError;
use WP_Error;

/**
 * Class MailHandler
 *
 * Handles sending emails using different connections and logging the activities.
 */
class MailHandler {

	/**
	 * Singleton instance.
	 *
	 * @var MailHandler|null
	 */
	private static $instance = null;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * ConnectionManager instance.
	 *
	 * @var ConnectionManager
	 */
	private $connection_manager;

	/**
	 * ProcessEmailData instance.
	 *
	 * @var ProcessEmailData
	 */
	private $email_data_processor;

	/**
	 * ContentGuard instance.
	 *
	 * @var ContentGuard
	 */
	private $content_guard;
	/**
	 * Private constructor to enforce Singleton pattern.
	 */
	private function __construct() {
		$this->logger               = Logger::instance();
		$this->connection_manager   = ConnectionManager::instance();
		$this->email_data_processor = ProcessEmailData::instance();
		$this->content_guard        = ContentGuard::instance();
		add_filter( 'suremails_before_send_email', [ $this->content_guard, 'check_email_content' ], 10 );
	}

	/**
	 * Retrieves the Singleton instance of MailHandler.
	 *
	 * @return MailHandler The Singleton instance.
	 */
	public static function get_instance(): MailHandler {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Handles sending an email based on the provided attributes.
	 *
	 * @param array $atts The email attributes.
	 * @return bool|null The result of the email sending process.
	 */
	public static function handle_mail( array $atts ) {
		return self::get_instance()->process_mail( $atts );
	}

	/**
	 * Processes the email sending logic.
	 *
	 * @param array $atts The email attributes.
	 * @return bool The result of the email sending process.
	 */
	private function process_mail( array $atts ) {

		// Apply pre_wp_mail filter.
		$pre_wp_mail = apply_filters( 'pre_wp_mail', null, $atts );
		if ( $pre_wp_mail !== null ) {
			return $pre_wp_mail;
		}

		$mail_data = [
			'to'          => $atts['to'],
			'subject'     => $atts['subject'],
			'message'     => $atts['message'],
			'headers'     => $atts['headers'],
			'attachments' => $atts['attachments'],
		];

		$result = apply_filters( 'suremails_before_send_email', $atts );

		if ( is_array( $result ) ) {
			$mail_data['categories'] = $result;
			do_action( 'suremails_mail_blocked', $mail_data );
			return false;
		}

		// Get the globally shared PHPMailer instance.
		$phpmailer = $this->connection_manager->get_phpmailer();

		$processed_data = $this->process_email_data( $atts );

		$connection = $this->connection_manager->get_connection();
		if ( $connection === null ) {
			$connection = $this->determine_connection( $processed_data['headers'] );
		}
		$connection = $this->review_email_settings( $connection, $processed_data['headers']['from'] );

		// Initialize handler_response.
		$handler_response = [
			'atts'             => $processed_data,
			'status'           => Logger::STATUS_PENDING,
			'message'          => '',
			'success'          => false,
			'source'           => $connection['type'] ?? 'Default',
			'connection_title' => $connection['connection_title'] ?? 'Default',
			'from'             => [
				'email' => $connection['from_email'] ?? '',
				'name'  => ! empty( $connection['from_name'] ) ? $connection['from_name'] : __( 'WordPress', 'suremails' ),
			],
		];

		if ( $connection === null ) {
			// Send via DefaultMailHandler.
			$send = DefaultMailHandler::send_mail( $atts );
			if ( $send ) {
				$handler_response['status']  = Logger::STATUS_SENT;
				$handler_response['message'] = __( 'Sent using Default WordPress Handler', 'suremails' );
				$handler_response['success'] = true;
			} else {
				$handler_response['message'] = __( 'Failed to send email using Default WordPress Handler', 'suremails' );
				$handler_response['success'] = false;

			}

			$this->handle_response( $handler_response );
			return $send;
		}
		// Use handler.
		$handler = ConnectionHandlerFactory::create( $connection );
		if ( ! $handler instanceof ConnectionHandler ) {
			$handler_response['message'] = __( 'Invalid connection type.', 'suremails' );
			$this->handle_response( $handler_response );
			return false;
		}

		if ( ! apply_filters( 'suremails_send_email', '__return_true' ) ) {
			return false;
		}

		// Send via handler.
		$send_result = $handler->send( $atts, $this->logger->get_id(), $connection, $processed_data );

		// Setting status and messageee.
		$handler_response['success']         = $send_result['success'] ?? false;
		$handler_response['status']          = $handler_response['success'] ? Logger::STATUS_SENT : Logger::STATUS_FAILED;
		$handler_response['message']         = $send_result['message'] ?? ( $handler_response['success'] ? __( 'Email sent successfully.', 'suremails' ) : __( 'Failed to send email.', 'suremails' ) );
		$handler_response['email_simulated'] = $send_result['email_simulated'] ?? false;

		if ( $handler_response['success'] ) {
			do_action( 'wp_mail_succeeded', $mail_data );
			$this->handle_response( $handler_response );
			$this->connection_manager->reset();
			$this->logger->set_id();
			return true;
		}

		// After Failed Actions:.
		$mail_error_data                             = $mail_data;
		$mail_error_data['phpmailer_exception_code'] = 0;

		// Log the result.
		$log_id = $this->handle_response( $handler_response );

		// Attempt fallback if not in testing mode.
		if ( ! $this->connection_manager->get_is_testing() ) {
			$next_connection = $this->connection_manager->get_next_connection();
			if ( $next_connection !== null ) {
				$this->logger->set_id( $log_id );
				$this->connection_manager->set_connection( $next_connection );
				$send_result_fallback = self::handle_mail( $atts );
				if ( $send_result_fallback ) {
					$this->connection_manager->reset();
					return true;
				}
			}
		}
		if ( $this->should_trigger_failed_email() && $handler_response['status'] === Logger::STATUS_FAILED ) {
			do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $handler_response['message'], $mail_data ) );
		}

		$resend_log_id = $this->logger->get_id();
		if (
			is_int( $resend_log_id )
			&& $handler_response['status'] === Logger::STATUS_FAILED &&
			! $this->connection_manager->get_is_testing()
		) {
			Crons::instance()->schedule_retry_failed_email( $resend_log_id );
		}
		$this->connection_manager->reset();
		return false;
	}

	/**
	 * Processes the email data using ProcessEmailData class and populates PHPMailer.
	 *
	 * @param array $atts The email attributes.
	 * @return array Processed email data.
	 */
	private function process_email_data( array $atts ) {
		$to          = $atts['to'] ?? [];
		$headers     = $atts['headers'] ?? [];
		$message     = $atts['message'] ?? '';
		$attachments = $atts['attachments'] ?? [];
		$subject     = $atts['subject'] ?? '';

		$processed_data = $this->email_data_processor->process_all( $to, $headers, $message, $attachments, $subject );

		// Update $atts with processed data.
		$atts['to']          = $processed_data['to'];
		$atts['headers']     = $this->email_data_processor->format_processed_headers( $processed_data['headers'] );
		$atts['message']     = $processed_data['message'];
		$atts['attachments'] = $processed_data['attachments'];
		$atts['subject']     = $processed_data['subject'];

		return $processed_data;
	}

	/**
	 * Handles the response from email sending and logging.
	 *
	 * @param array $handler_response The response data from the handler.
	 * @return int|null The log ID after handling the response.
	 */
	private function handle_response( array $handler_response ) {

		$new_server_response = [
			'retry'      => 0,
			'Message'    => $handler_response['message'],
			'Connection' => $handler_response['connection_title'],
			'timestamp'  => current_time( 'mysql' ),
			'simulated'  => $handler_response['email_simulated'] ?? false,

		];

		$atts              = $handler_response['atts'];
		$status            = $handler_response['status'];
		$source            = $handler_response['source'];
		$from_email        = $handler_response['from']['email'];
		$from_name         = $handler_response['from']['name'];
		$email_from        = "{$from_name} <{$from_email}>";
		$email_to          = $this->email_data_processor->format_email_recipients( $atts['to'] );
		$formatted_headers = $this->email_data_processor->format_processed_headers( $atts['headers'] );

		// Prepare log data.
		$log_data = $this->logger->prepare_log_data(
			[
				'email_from'  => $email_from,
				'email_to'    => $email_to,
				'subject'     => $atts['subject'] ?? '',
				'body'        => $atts['message'] ?? '',
				'headers'     => $formatted_headers,
				'attachments' => $atts['uploaded_attachments'] ?? [],
				'status'      => $status,
				'response'    => [ $new_server_response ],
				'connection'  => $source,
			]
		);

		if ( $log_data['status'] !== Logger::STATUS_SENT && ! $this->connection_manager->get_is_testing() && ! $this->connection_manager->get_is_resend() ) {
			$log_data['status'] = Logger::STATUS_PENDING;
		}

		// Check if log_id is already set.
		$log_id = $this->logger->get_id();

		if ( $log_id === null ) {

			// First time logging.
			$log_id = $this->logger->log_email( $log_data );

			if ( is_wp_error( $log_id ) ) {
				// translators: %s is the error message.
				LogError::instance()->log_error( sprintf( __( 'Failed to log email: %s', 'suremails' ), $log_id->get_error_message() ) );
				return null;
			}
			if ( is_int( $log_id ) && $log_data['status'] === Logger::STATUS_PENDING ) {
				$this->logger->set_id( $log_id );
				return $log_id;
			}
			return null;
		}

		// Update existing log.
		$log_entry = (array) $this->logger->get_log( $log_id );
		$meta      = $log_entry['meta'] ?? [
			'retry'  => 0,
			'resend' => 0,
		];

		if ( $this->should_retry_increase() ) {
			$meta['retry'] = (int) $meta['retry'] + 1;
		}

		if ( $log_data['status'] === Logger::STATUS_PENDING && $meta['retry'] >= 1 ) {
			$log_data['status'] = Logger::STATUS_FAILED;
		}
		$new_server_response['retry'] = $meta['retry'];

		if ( $this->connection_manager->get_is_resend() && $log_data['status'] === Logger::STATUS_SENT ) {
			$meta['resend'] += 1;
		}

		$existing_responses = $log_entry['response'];
		if ( ! is_array( $existing_responses ) ) {
			$existing_responses = [];
		}

		$existing_responses[] = $new_server_response;

		$update_data = [
			'status'     => $log_data['status'],
			'response'   => $existing_responses,
			'updated_at' => current_time( 'mysql' ),
			'connection' => $source,
			'meta'       => $meta,
			'email_from' => $log_data['email_from'],
		];

		$update_result = $this->logger->update_log( $log_id, $update_data );
		if ( is_wp_error( $update_result ) || ! $update_result ) {
			// Handle update failure if necessary.
			// translators: %d is the log ID that failed to update.
			LogError::instance()->log_error( sprintf( __( 'Failed to update log ID %d.', 'suremails' ), $log_id ) );
			return null;
		}

		do_action( 'suremails_after_send_mail', $log_data, $log_id );

		// Return the $log_id for further use.
		return $log_id;
	}

	/**
	 * Determines which connection to use based on email attributes.
	 *
	 * @param array $headers The email headers.
	 * @return array|null The connection details or null if not found.
	 */
	private function determine_connection( array $headers ) {
		$from       = $headers['from'] ?? null;
		$from_name  = ! empty( $from['name'] ) ? $from['name'] : null;
		$from_email = ! empty( $from['email'] ) ? $from['email'] : null;

		$connection = null;

		if ( $from_email !== null ) {
			$connection = $this->get_connection_from_email( $from_email );
		}

		if ( $connection === null ) {
			// No connection found for the from_email. Use the default connection first then get all connections from from_email of default connection and use as fallback sequence based on priority.
			$default_connection = $this->connection_manager->get_default_connection( false );
			$default_from_email = $default_connection['from_email'] ?? '';
			$this->connection_manager->set_from_email( $default_from_email );

			// Swap default connection to true to get all connections from from_email of default connection but first connection should be default connection.
			$this->connection_manager->swap_default_connection = true;
			$connection                                        = $this->get_connection_from_email( $default_from_email );
		}

		return $connection;
	}

	/**
	 * Get connection details based on from_email and priority.
	 *
	 * @param string $from_email The email address to match.
	 * @return array|null The connection details with the highest priority or null if not found.
	 */
	private function get_connection_from_email( string $from_email ) {
		$best_connection = null;

		if ( $this->connection_manager->get_from_email() ) {
			$best_connection = $this->connection_manager->get_next_connection();
		}
		$this->connection_manager->set_from_email( $from_email );
		return $this->connection_manager->get_priority_based_fallback_connection();
	}

	/**
	 * Determines whether to increase the retry count.
	 *
	 * @return bool
	 */
	private function should_retry_increase() {
		return (
			! $this->connection_manager->get_is_resend() &&
			$this->connection_manager->get_is_retried() &&
			$this->connection_manager->get_is_first()
		) || (
			$this->connection_manager->is_default &&
			$this->connection_manager->get_is_retried()
		);
	}

	/**
	 * Determines whether to trigger the failed email action.
	 * This trigger will only trigger if the email is retried or testing and failed and it's last connection or default connection.
	 *
	 * @return bool Whether to trigger the failed email action.
	 */
	private function should_trigger_failed_email() {
		if ( $this->connection_manager->get_is_testing() ) {
			return true;
		}

		if ( $this->connection_manager->get_is_retried() && $this->connection_manager->is_default ) {
			return true;
		}

		if ( $this->connection_manager->get_is_retried() && $this->connection_manager->get_is_last() ) {
			return true;
		}

		if ( $this->connection_manager->get_is_resend() && $this->connection_manager->get_is_last() ) {
			return true;
		}

		return false;
	}

	/**
	 * Reviews and updates the connection's email settings based on the given $from and force settings.
	 *
	 * @param array|null $connection The connection details (associative array).
	 * @param array|null $from       The "from" details, typically containing 'name' and 'email'.
	 * @param string     $default_name    The default name to use if none is provided and force is false (e.g., 'WordPress').
	 * @return array|null            The updated connection or null if the connection is null.
	 */
	private function review_email_settings( ?array $connection, ?array $from, string $default_name = '' ) {
		if ( $connection === null ) {
			return null;
		}

		$default_name = ! empty( $default_name ) ? $default_name : __( 'WordPress', 'suremails' );

		if ( isset( $connection['force_from_name'] ) ) {
			if ( $connection['force_from_name'] === false ) {
				if ( ! empty( $from['name'] ) ) {
					$connection['from_name'] = $from['name'];
				} elseif ( empty( $connection['from_name'] ) ) {

					$connection['from_name'] = $default_name;
				}
			}
		}

		if ( isset( $connection['force_from_email'] ) ) {
			if ( $connection['force_from_email'] === false ) {
				if ( ! empty( $from['email'] ) ) {
					$connection['from_email'] = $from['email'];
				}
			}
		}

		$phpmailer = $this->connection_manager->get_phpmailer();
		$phpmailer->setFrom( $connection['from_email'], $connection['from_name'] );

		return $connection;
	}

}
