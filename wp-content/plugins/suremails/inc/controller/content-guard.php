<?php
/**
 * ContentGuard class
 *
 * Handles the moderation of the email content.
 *
 * @package SureMails\Inc\Controller
 */

namespace SureMails\Inc\Controller;

use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Emails\Handler\ProcessEmailData;
use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Utils\LogError;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ContentGuard
 *
 * Handles the moderation of the email content.
 *
 * @since 1.0.0
 */
class ContentGuard {

	use Instance;

	/**
	 * Status of the content.
	 *
	 * @var bool
	 */
	public $status = false;

	/**
	 * Hashes of the content.
	 *
	 * @var array
	 */
	public $hashes = [];

	/**
	 * ContentGuard constructor.
	 */
	public function __construct() {
		if ( ! defined( 'SUREMAIL_HASHES' ) ) {
			define( 'SUREMAIL_HASHES', 'suremails_content_guard_hashes' );
		}
		$this->status = Settings::instance()->get_content_guard_status();
		$this->hashes = $this->get_hashes();
	}

	/**
	 * Check the email content and determine if it should be blocked.
	 *
	 * @param array $atts The email attributes.
	 * @return bool|array The status of the email content.
	 */
	public function check_email_content( array $atts ) {
		$result = true;
		if ( ! $this->status ) {
			return $result;
		}

		$subject = $atts['subject'] ?? '';
		$message = $atts['message'] ?? '';

		$is_flagged = $this->check( $subject, $message );

		if ( $is_flagged['status'] === true ) {
			$atts['content_guard'] = $is_flagged['response'];
			$result                = $is_flagged['response']['categories'];
			$this->handle_content_guard_response( $atts );
		}

		return $result;
	}

	/**
	 * Check if the content is protected.
	 *
	 * @param string $content The content to check.
	 * @return array Moderation response.
	 * @since 1.0.0
	 */
	public function validate( $content ) {
		$response = wp_safe_remote_post(
			SUREMAILS_CONTENT_GUARD_MIDDLEWARE . 'moderations',
			[
				'body' => [
					'input' => $content,
				],
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return [
				'status'  => 'error',
				'message' => __( 'An error occurred while validating the content.', 'suremails' ),
			];
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Generate hash for the content.
	 *
	 * @param string $content The content to generate hash for.
	 * @return string The generated hash.
	 * @since 1.0.0
	 */
	public function generate_hash( $content ) {
		$nouns   = $this->find_proper_nouns( $content );
		$content = $this->trim_strings( $content, $nouns );
		return md5( $content );
	}

	/**
	 * Get all the hashes.
	 *
	 * @return array Associative array of hashes and their responses.
	 * @since 1.0.0
	 */
	public function get_hashes() {
		return get_option( SUREMAIL_HASHES, [] );
	}

	/**
	 * Add a hash with its corresponding response.
	 *
	 * @param string $hash The hash to add.
	 * @param array  $response The moderation response associated with the hash.
	 * @since 1.0.0
	 * @return void
	 */
	public function add_hash( $hash, $response ) {
		$hashes = $this->hashes;

		if ( is_array( $hashes ) && ! empty( $hashes ) && array_key_exists( $hash, $hashes ) ) {
			return;
		}
		$hashes[ $hash ] = $response;
		update_option( SUREMAIL_HASHES, $hashes );
	}

	/**
	 * Check if the hash exists.
	 *
	 * @param string $hash The hash to check.
	 * @return bool
	 * @since 1.0.0
	 */
	public function hash_exists( $hash ) {
		$hashes = $this->hashes;
		return array_key_exists( $hash, $hashes );
	}

	/**
	 * Get the response associated with a hash.
	 *
	 * @param string $hash The hash to get the response for.
	 * @return array|bool The response array if the hash exists, false otherwise.
	 * @since 1.0.0
	 */
	public function get_hash( $hash ) {
		$hashes = $this->hashes;
		if ( array_key_exists( $hash, $hashes ) ) {
			return $hashes[ $hash ];
		}
		return false;
	}

	/**
	 * Process the content.
	 *
	 * @param string $subject The subject of the email.
	 * @param string $message The message body of the email.
	 * @return array The result of the moderation process.
	 * @since 1.0.0
	 */
	public function check( $subject = '', $message = '' ) {
		$result = [
			'status'   => false,
			'response' => [],
		];

		$content = $subject . wp_strip_all_tags( $message );
		$hash    = $this->generate_hash( $content );

		$is_flagged = $this->get_hash( $hash );

		if ( $is_flagged !== false && is_array( $is_flagged ) && ! empty( $is_flagged ) ) {

			if ( isset( $is_flagged['flagged'] ) && $is_flagged['flagged'] === true ) {
				$result['status']   = true;
				$result['response'] = $is_flagged;
				return $result;
			}

			$result['status']   = false;
			$result['response'] = $is_flagged;
			return $result;
		}

		$response = $this->validate( $content );

		if ( ! isset( $response['status'] ) && isset( $response['flagged'] ) ) {
			$this->add_hash( $hash, $response );
			$this->hashes [ $hash ] = $response;
			if ( $response['flagged'] === true ) {
				$result['status']   = true;
				$result['response'] = $response;
				return $result;
			}
			$result['status']   = false;
			$result['response'] = $response;
			return $result;
		}

		return $result;
	}

	/**
	 * Find proper nouns in the text.
	 *
	 * @param string $text The text to search for proper nouns.
	 * @return array The list of proper nouns found in the text.
	 * @since 1.3.0
	 */
	public function find_proper_nouns( $text ) {
		// Tokenize text into sentences using full stops, new lines, or other delimiters.
		$sentences = preg_split( '/(\.|\n|\?|!)/', $text );

		if ( ! $sentences ) {
			return [];
		}

		$proper_nouns = [];

		foreach ( $sentences as $sentence ) {
			// Trim and extract words.
			$words = preg_split( '/\s+/', trim( $sentence ) );

			if ( ! $words ) {
				continue;
			}

			foreach ( $words as $index => $word ) {
				// Remove punctuation from the word.
				$word = preg_replace( '/[^a-zA-Z]/', '', $word );

				if ( empty( $word ) ) {
					continue;
				}

				// Check if the word starts with an uppercase letter and is not the first word of a sentence.
				if ( preg_match( '/^[A-Z][a-z]+$/', $word ) && ( $index > 0 || empty( $proper_nouns ) ) ) {
					$proper_nouns[] = $word;
				}
			}
		}

		return array_unique( $proper_nouns );
	}

	/**
	 * Trim all the given strings from the text.
	 *
	 * @param string $text The text to trim.
	 * @param array  $strings The strings to trim from the text.
	 *
	 * @return string The trimmed text.
	 * @since 1.3.0
	 */
	public function trim_strings( $text, $strings ) {
		// Trim all the given strings from the text.
		foreach ( $strings as $string ) {
			$text = str_replace( $string, '', $text );
		}

		return $text;
	}

	/**
	 * Delete the content guard hashes.
	 *
	 * @return void
	 */
	public static function flush_hashes() {
		update_option( 'suremails_content_guard_hashes', [] );
	}

	/**
	 * Handles the response when content is flagged.
	 *
	 * @param array $atts The email attributes with content guard response.
	 * @return int|bool The log ID after handling the response.
	 */
	private function handle_content_guard_response( array $atts ) {

		$new_server_response = [
			'retry'      => 0,
			'Message'    => __( 'Email content is flagged.', 'suremails' ),
			'Connection' => __( 'Reputation Shield', 'suremails' ),
			'timestamp'  => current_time( 'mysql' ),
		];

		$email_data_processor = ProcessEmailData::instance();
		$logger               = Logger::instance();
		$connection_manager   = ConnectionManager::instance();
		$atts['to']           = $email_data_processor->process_to( $atts['to'] );
		$atts['headers']      = $email_data_processor->process_headers( $atts['headers'] );
		$atts['attachments']  = $email_data_processor->process_attachments( $atts['attachments'] );
		$from_email           = $atts['headers']['from']['email'];
		$email_from           = '';
		if ( ! empty( $from_email ) ) {
			$from_name  = ! empty( $atts['headers']['from']['name'] ) ? $atts['headers']['from']['name'] : 'WordPress';
			$email_from = "{$from_name} <{$from_email}>";
		}

		$handler_response = $logger->prepare_log_data(
			[
				'email_to'    => $email_data_processor->format_email_recipients( $atts['to'] ),
				'email_from'  => ! empty( $email_from ) ? $email_from : ' ',
				'subject'     => $atts['subject'],
				'body'        => $atts['message'],
				'attachments' => $atts['attachments'],
				'headers'     => $email_data_processor->format_processed_headers( $atts['headers'] ),
				'status'      => Logger::STATUS_BLOCKED,
				'connection'  => '',
				'meta'        => [
					'retry'         => 0,
					'resend'        => 0,
					'content_guard' => $atts['content_guard'],
				],
				'response'    => [ $new_server_response ],
			]
		);

		$log_id = $logger->get_id();

		if ( $log_id === null ) {
			// First time logging.
			$log_id = $logger->log_email( $handler_response );

			if ( is_wp_error( $log_id ) ) {
				// translators: %s is the error message.
				LogError::instance()->log_error( sprintf( __( 'Failed to log email: %s', 'suremails' ), $log_id->get_error_message() ) );
				return false;
			}

			return $log_id;
		}
		$log_entry = (array) $logger->get_log( $log_id );
		$meta      = $log_entry['meta'] ?? [
			'retry'  => 0,
			'resend' => 0,
		];

		if ( $connection_manager->get_is_retried() ) {
			$meta['retry'] += 1;
		}

		$existing_responses = $log_entry['response'];
		if ( ! is_array( $existing_responses ) ) {
			$existing_responses = [];
		}
		$meta['content_guard']        = $atts['content_guard'];
		$new_server_response['retry'] = $meta['retry'];
		$existing_responses[]         = $new_server_response;
		$update_data                  = [
			'meta'       => $meta,
			'response'   => $existing_responses,
			'status'     => Logger::STATUS_BLOCKED,
			'updated_at' => current_time( 'mysql' ),
			'connection' => '',
		];
		$update_result                = $logger->update_log( $log_id, $update_data );
		$connection_manager->reset();
		if ( is_wp_error( $update_result ) || ! $update_result ) {
			// translators: %d is the log ID.
			LogError::instance()->log_error( sprintf( __( 'Failed to update log ID %d.', 'suremails' ), $log_id ) );
			return false;
		}
		return $log_id;
	}

}
