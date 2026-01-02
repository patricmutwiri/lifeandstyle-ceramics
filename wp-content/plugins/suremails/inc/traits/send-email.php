<?php
/**
 * Trait.
 *
 * @package SureMails\Inc\Traits;
 * @since 0.0.1
 */

namespace SureMails\Inc\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait Instance.
 */
trait SendEmail {

	/**
	 * Send email to the user.
	 *
	 * @since 0.0.1
	 *
	 * @param string           $to          The email address to send to.
	 * @param string           $subject     The email subject.
	 * @param string           $message     The email message.
	 * @param string|array     $headers     The email headers.
	 * @param array<int,mixed> $attachments The email attachments.
	 * @return bool|null
	 */
	public static function send( $to, $subject, $message, $headers, $attachments ) {
		return wp_mail( $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Get the email headers.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public static function get_html_headers() {
		return 'Content-Type: text/html; charset=UTF-8';
	}
	/**
	 * Get the email headers.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public static function get_text_headers() {
		return 'Content-Type: text/plain; charset=UTF-8';
	}
}
