<?php
/**
 * Settings.php
 *
 * Provides functionality to retrieve specific settings from the connections option.
 *
 * @package SureMails\Inc\Settings
 */

namespace SureMails\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureMails\Inc\Traits\Instance;

const DEFAULT_USER_DETAILS = [
	'first_name' => '',
	'last_name'  => '',
	'email'      => '',
	'skip'       => 'no',
	'lead'       => false,
];

const DEFAULT_CONNECTION_DETAILS = [
	'connections'             => [],
	'default_connection'      => [
		'type'             => '',
		'email'            => '',
		'id'               => '',
		'connection_title' => '',
	],
	'log_emails'              => 'yes',
	'delete_email_logs_after' => '30_days',
	'email_simulation'        => 'no',
];

const MISC_SETTINGS = [
	'email_summary'        => 'yes',
	'email_summary_active' => 'yes',
	'email_summary_day'    => 'monday',
	'email_summary_index'  => '1',
];

/**
 * Class Settings
 *
 * Handles fetching specific settings from the connections option.
 */
class Settings {

	use Instance;

	/**
	 * The connections array.
	 *
	 * @var array|null
	 * @since 1.3.0
	 */
	public static $connections = null;

	/**
	 * Retrieves the value of a specific key from the connections option.
	 *
	 * @param string $key The key to retrieve from the settings.
	 * @param mixed  $default The default value to return if the key does not exist.
	 *
	 * @return mixed The value of the specified key or the default value.
	 */
	public function get_settings( ?string $key = null, $default = null ) {
		if ( self::$connections === null ) {
			self::$connections = $this->pre_process(
				$this->get_raw_settings()
			);
		}

		if ( $key === null ) {
			return self::$connections;
		}

		if ( array_key_exists( $key, self::$connections ) ) {
			return self::$connections[ $key ];
		}

		return $default;
	}

	/**
	 * Retrieves the value of a specific key from the connections option.
	 *
	 * @return mixed The value of the specified key or the default value.
	 * @since 1.3.0
	 */
	public function get_raw_settings() {
		return get_option(
			SUREMAILS_CONNECTIONS,
			DEFAULT_CONNECTION_DETAILS
		);
	}

	/**
	 * Retrieves the value of a specific key from the 'suremails_content_guard_user_details' option.
	 *
	 * @param string $key The key to retrieve from the settings.
	 * @param mixed  $default The default value to return if the key does not exist.
	 * @since 1.0.0
	 * @return mixed The value of the specified key or the default value.
	 */
	public function get_user_details( ?string $key = null, $default = null ) {
		$settings = wp_parse_args( get_option( 'suremails_content_guard_user_details', [] ), DEFAULT_USER_DETAILS );

		if ( $key === null ) {
			return $settings;
		}

		if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Sets the value of the 'suremails_content_guard_user_details' option.
	 *
	 * @param mixed $details The value to save.
	 * @since 1.0.0
	 * @return void
	 */
	public function set_user_details( $details ) {
		update_option( 'suremails_content_guard_user_details', wp_parse_args( $details, DEFAULT_USER_DETAILS ) );
	}

	/**
	 * Retrieves the value of a specific key from the 'suremails_content_guard_user_details' option.
	 *
	 * @return bool
	 */
	public function show_content_guard_lead_popup() {
		$user_details = $this->get_user_details();

		if ( 'no' === $user_details['skip'] && $user_details['lead'] === false ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the value of a specific key from the 'suremails_content_guard_user_details' option.
	 *
	 * @return bool
	 */
	public function get_content_guard_status() {
		$status = get_option( 'suremails_content_guard_activated', 'no' );
		if ( $status === 'yes' ) {
			return true;
		}
		return false;
	}

	/**
	 * Get keys to be encrypted.
	 *
	 * @param string $slug Slug of the connection.
	 * @return array
	 * @since 1.3.0
	 */
	public function get_encryptable_keys( $slug ) {
		$classname = ucfirst( strtolower( $slug ) ) . 'Handler';
		$class     = 'SureMails\\Inc\\Emails\\Providers\\' . $slug . '\\' . $classname;
		$options   = $class::get_options();
		$fields    = $options['fields'] ?? [];

		return array_keys(
			array_filter(
				$fields,
				static function( $field ) {
					return isset( $field['encrypt'] ) && $field['encrypt'] === true;
				}
			)
		);
	}

	/**
	 * Pre process all the connection encrytable fields.
	 *
	 * @param array $settings Connections array.
	 * @return array
	 * @since 1.3.0
	 */
	public function pre_process( $settings ) {
		if ( empty( $settings['connections'] ) ) {
			return $settings;
		}
		foreach ( $settings['connections'] as $key => $connection ) {
			$slug             = $connection['type'] ?? '';
			$encryptable_keys = $this->get_encryptable_keys( $slug );
			foreach ( $encryptable_keys as $field ) {
				if ( isset( $connection[ $field ] ) ) {
					$settings['connections'][ $key ][ $field ] = $this->decrypt( $connection[ $field ] );
				}
			}
		}
		return $settings;
	}

	/**
	 * Encrypt data using base64.
	 *
	 * @param string $input The input string which needs to be encrypted.
	 * @since 1.3.0
	 * @return string The encrypted string.
	 */
	public static function encrypt( $input ) {
		// If the input is empty or not a string, then abandon ship.
		if ( empty( $input ) || ! is_string( $input ) ) {
			return '';
		}

		// Encrypt the input and return it.
		$base_64 = base64_encode( $input ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rtrim( $base_64, '=' );
	}

	/**
	 * Decrypt data using base64.
	 *
	 * @param string $input The input string which needs to be decrypted.
	 * @since 1.3.0
	 * @return string The decrypted string.
	 */
	public static function decrypt( $input ) {
		// If the input is empty or not a string, then abandon ship.
		if ( empty( $input ) || ! is_string( $input ) ) {
			return '';
		}

		// Decrypt the input and return it.
		$base_64 = $input . str_repeat( '=', strlen( $input ) % 4 );
		return base64_decode( $base_64 );
	}

	/**
	 * Encrpt all the connection fields.
	 *
	 * @since 1.3.0
	 * @param array $settings The settings array.
	 * @return array The encrypted string.
	 */
	public function encrypt_all( $settings ) {
		if ( empty( $settings['connections'] ) ) {
			return $settings;
		}
		foreach ( $settings['connections'] as $key => $connection ) {
			$slug             = $connection['type'] ?? '';
			$encryptable_keys = $this->get_encryptable_keys( $slug );
			foreach ( $encryptable_keys as $field ) {
				if ( isset( $connection[ $field ] ) && is_string( $connection[ $field ] ) ) {
					$settings['connections'][ $key ][ $field ] = $this->encrypt( $settings['connections'][ $key ][ $field ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Encrypt data using base64.
	 *
	 * @param array $connection_data The input string which needs to be encrypted.
	 * @since 1.4.0
	 * @return void The encrypted string.
	 */
	public function update_connection( $connection_data ) {
		$settings                       = $this->get_settings();
		$id                             = $connection_data['id'];
		$settings['connections'][ $id ] = $connection_data;
		$encrypted_settings             = $this->encrypt_all( $settings );
		update_option( SUREMAILS_CONNECTIONS, $encrypted_settings );
		Settings::$connections = null;
	}

	/**
	 * Get the simulation status. If the email simulation is enabled, return true.
	 * Otherwise, return false.
	 *
	 * @since 1.5.0
	 * @return bool The simulation status. True if the email simulation is enabled, false otherwise.
	 */
	public function get_email_simulation_status() {
		$options = $this->get_settings();
		if ( isset( $options['email_simulation'] ) && $options['email_simulation'] === 'yes' ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the value of a specific key from the 'suremails_settings' option.
	 *
	 * @return array|mixed|null The value of the specified key or null if not found.
	 * @param string $key The key to retrieve from the settings.
	 */
	public function get_misc_settings( $key = null ) {
		$settings = get_option( 'suremails_settings', MISC_SETTINGS );

		if ( $key === null ) {
			return $settings;
		}

		if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return null;
	}

	/**
	 * Update the value of a specific key in the 'suremails_settings' option.
	 *
	 * @param string $key The key to update in the settings.
	 * @param mixed  $value The new value to set for the specified key.
	 * @return void
	 */
	public function update_misc_settings( $key, $value ) {
		$settings = get_option( 'suremails_settings', MISC_SETTINGS );

		if ( empty( $key ) ) {
			return;
		}

		if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
			$settings[ $key ] = $value;
		}

		update_option( 'suremails_settings', $settings );
	}
}
