<?php
/**
 * Sanitize
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sanitize
 *
 * @since 1.0.0
 */
class Sanitize {

	/**
	 * Sanitize text
	 * if the value after sanitize_text is empty, return the default value
	 *
	 * @param string $value   Value to sanitize.
	 * @param string $default Default value.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function text( $value, $default = '' ) {
		return sanitize_text_field( $value ) ? $value : $default;
	}

	/**
	 * Sanitize multi-dimension array.
	 *
	 * @param callable                                $function Function reference.
	 * @param array<string, mixed>|array<int, string> $data_array Array what we need to sanitize.
	 * @since  0.0.1
	 * @return array<string, mixed>|array<int, string>
	 */
	public static function array_deep( callable $function, array $data_array ) {
		if ( ! is_callable( $function ) ) {
			return [];
		}

		if ( empty( $data_array ) ) {
			return [];
		}

		$response = [];
		foreach ( $data_array as $key => $data ) {
			if ( is_array( $data ) ) {
				$response[ $key ] = self::array_deep( $function, $data );
			} else {
				$response[ $key ] = $function( $data );
			}
		}

		return $response;
	}

	/**
	 * Custom sanitization function to allow specific placeholders like %term_title%
	 *
	 * Sanitize data recursively, while preserving any placeholder patterns in the format %placeholder%.
	 * The default sanitize_text_field function would remove certain characters, such as the '%' symbol,
	 * potentially breaking placeholders like %term_title%. By using the custom sanitize_with_placeholders
	 * function, we ensure that placeholders are retained exactly as they are (e.g., %term_title% remains intact),
	 * while still sanitizing other parts of the text to prevent any malicious input.
	 *
	 * @param string|bool $text The input text to sanitize.
	 * @return string|bool The sanitized text with placeholders retained.
	 */
	public static function sanitize_with_placeholders( $text ) {

		if ( is_bool( $text ) ) {
			return $text;
		}

		if ( empty( $text ) ) {
			return '';
		}

		if ( ! is_string( $text ) ) {
			return $text;
		}

		if ( filter_var( $text, FILTER_VALIDATE_URL ) ) {
			return esc_url_raw( $text );
		}
		return sanitize_text_field( $text );
	}

	/**
	 * Sanitize shortcode
	 *
	 * @since 1.2.0
	 * @param string $text The text to sanitize.
	 * @return string The sanitized text.
	 */
	public static function sanitize_shortcode( $text ) {
		if ( empty( $text ) ) {
			return '';
		}

		if ( ! is_string( $text ) ) {
			return $text;
		}

		return do_shortcode( $text );
	}

	/**
	 * Sanitize Email address
	 *
	 * @since 1.6.0
	 * @param string $email The email address to sanitize.
	 * @param string $default Default value if email is invalid.
	 * @return string The sanitized email address.
	 */
	public static function email( $email, $default = '' ) {
		$sanitized_email = sanitize_email( $email );
		return is_email( $sanitized_email ) ? $sanitized_email : $default;
	}
}
