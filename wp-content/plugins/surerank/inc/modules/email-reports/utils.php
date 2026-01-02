<?php
/**
 * Utils class.
 *
 * @package SureRank\Inc\Modules\EmailReports
 * @since 1.6.0
 */

namespace SureRank\Inc\Modules\EmailReports;

use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The Utils class.
 *
 * @package SureRank\Inc\Modules\EmailReports
 * @since 1.6.0
 */
class Utils {




	use Get_Instance;

	/**
	 * Get email reports settings.
	 *
	 * @since 1.6.0
	 * @return array<string, mixed> The settings array.
	 */
	public function get_settings() {
		return get_option(
			'surerank_email_reports_settings',
			$this->get_default_settings()
		);
	}

	/**
	 * Default email reports settings.
	 *
	 * @since 1.6.0
	 * @return array<string, mixed> The default settings array.
	 */
	public function get_default_settings() {
		$defaults = [
			'enabled'        => false,
			'recipientEmail' => '',
			'scheduledOn'    => 'sunday',
		];

		// Allow Pro to extend defaults with additional fields.
		return apply_filters( 'surerank_email_reports_default_settings', $defaults );
	}

	/**
	 * Validate email reports settings.
	 *
	 * @since 1.6.0
	 * @param array<string, mixed> $settings The settings to validate.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return new WP_Error( 'invalid_settings', __( 'Settings must be an array.', 'surerank' ) );
		}

		if ( isset( $settings['enabled'] ) && ! is_bool( $settings['enabled'] ) ) {
			return new WP_Error( 'invalid_enabled', __( 'Enabled must be a boolean.', 'surerank' ) );
		}

		if ( isset( $settings['recipientEmail'] ) && ! is_email( $settings['recipientEmail'] ) && ! empty( $settings['recipientEmail'] ) ) {
			return new WP_Error( 'invalid_email', __( 'Recipient email must be a valid email address.', 'surerank' ) );
		}

		if ( isset( $settings['scheduledOn'] ) && ! in_array( $settings['scheduledOn'], array_keys( $this->get_schedule_on_values() ), true ) ) {
			return new WP_Error( 'invalid_day_of_week', __( 'Day of week must be a valid day.', 'surerank' ) );
		}

		return true;
	}

	/**
	 * Schedule on values
	 *
	 * @since 1.6.0
	 * @return array<string, string> Array of day names.
	 */
	public function get_schedule_on_values() {
		return [
			'sunday'    => __( 'Sunday', 'surerank' ),
			'monday'    => __( 'Monday', 'surerank' ),
			'tuesday'   => __( 'Tuesday', 'surerank' ),
			'wednesday' => __( 'Wednesday', 'surerank' ),
			'thursday'  => __( 'Thursday', 'surerank' ),
			'friday'    => __( 'Friday', 'surerank' ),
			'saturday'  => __( 'Saturday', 'surerank' ),
		];
	}

	/**
	 * Format number for display (K, M, B, T, Q notation).
	 *
	 * @since 1.6.0
	 * @param int|float $num Number to format.
	 * @return string Formatted number.
	 */
	public static function format_number( $num ) {
		// Handle negative numbers.
		$is_negative = $num < 0;
		$num         = abs( $num );

		if ( $num >= 1000000000000000 ) {
			$formatted = round( $num / 1000000000000000, 1 );
			$result    = $formatted == floor( $formatted ) ? (string) floor( $formatted ) . 'Q' : (string) $formatted . 'Q';
		} elseif ( $num >= 1000000000000 ) {
			$formatted = round( $num / 1000000000000, 1 );
			$result    = $formatted == floor( $formatted ) ? (string) floor( $formatted ) . 'T' : (string) $formatted . 'T';
		} elseif ( $num >= 1000000000 ) {
			$formatted = round( $num / 1000000000, 1 );
			$result    = $formatted == floor( $formatted ) ? (string) floor( $formatted ) . 'B' : (string) $formatted . 'B';
		} elseif ( $num >= 1000000 ) {
			$formatted = round( $num / 1000000, 1 );
			$result    = $formatted == floor( $formatted ) ? (string) floor( $formatted ) . 'M' : (string) $formatted . 'M';
		} elseif ( $num >= 1000 ) {
			$formatted = round( $num / 1000, 1 );
			$result    = $formatted == floor( $formatted ) ? (string) floor( $formatted ) . 'K' : (string) $formatted . 'K';
		} else {
			$result = (string) number_format( $num );
		}

		return $is_negative ? '-' . $result : $result;
	}
}
