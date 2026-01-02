<?php
/**
 * Index_Now Module
 *
 * Main module class for handling instant indexing functionality.
 *
 * @package SureRank\Inc\Modules\Ai_Auth
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Ai_Auth;

use SureRank\Inc\Traits\Get_Instance;
use WP;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Controller class
 *
 * Main module class for instant indexing functionality.
 */
class Controller {

	use Get_Instance;

	/**
	 * Module settings key.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	public const SETTINGS_KEY = 'surerank_auth';

	/**
	 * Encryption key.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	public $key;

	/**
	 * Get Auth URL.
	 * 
	 * @since 1.4.2
	 * @return string|WP_Error
	 */
	public function get_auth_url() {
		// Generate a random key of 16 characters.
		$this->key = wp_generate_password( 16, false );

		// Prepare the token data.
		$token_data = [
			'redirect-back' => admin_url( 'admin.php?page=surerank' ),
			'key'           => $this->key,
			'site-url'      => site_url(),
			'nonce'         => wp_create_nonce( 'surerank_ai_auth_nonce' ),
		];

		$encoded_token_data = wp_json_encode( $token_data );

		if ( empty( $encoded_token_data ) ) {
			return new WP_Error( 'failed_to_encode_token_data', __( 'Failed to encode the token data.', 'surerank' ) );
		}

		return SURERANK_BILLING_PORTAL . 'auth/?token=' . base64_encode( $encoded_token_data );
	}

	/**
	 * Get Auth status.
	 * 
	 * @since 1.4.2
	 * @return bool
	 */
	public function get_auth_status() {
		$auth_status = get_option( self::SETTINGS_KEY, false );
		return ! empty( $auth_status );
	}

	/**
	 * Save Auth.
	 * 
	 * @since 1.4.2
	 * @param string $data Data to save.
	 * @param string $key Key to use for encryption.
	 * @param string $method Encryption method. Default is AES-256-CBC.
	 * @return bool|WP_Error
	 */
	public function save_auth( $data, $key, $method = 'AES-256-CBC' ) {

		// Decode the data and split IV and encrypted data.
		$decoded_data = base64_decode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		// if the data is not base64 encoded then return false.
		if ( empty( $decoded_data ) ) {
			return new WP_Error( 'failed_to_decode', __( 'Failed to decode the access key.', 'surerank' ) );
		}

		// split the key and encrypted data.
		[$key, $encrypted] = explode( '::', $decoded_data, 2 );

		// Decrypt the data using the key.
		$decrypted = openssl_decrypt( $encrypted, $method, $key, 0, $key );

		// if the decryption returns false then send error.
		if ( empty( $decrypted ) ) {
			return new WP_Error( 'failed_to_decrypt', __( 'Failed to decrypt the access key.', 'surerank' ) );
		}

		// json decode the decrypted data.
		$decrypted_data_array = json_decode( $decrypted, true );

		if ( ! is_array( $decrypted_data_array ) || empty( $decrypted_data_array ) ) {
			return new WP_Error( 'failed_to_json_decode', __( 'Failed to json decode the decrypted data.', 'surerank' ) );
		}

		// verify the nonce that comes in $encrypted_email_array.
		if ( ! empty( $decrypted_data_array['nonce'] ) && ! wp_verify_nonce( $decrypted_data_array['nonce'], 'surerank_ai_auth_nonce' ) ) {
			return new WP_Error( 'nonce_verification_failed', __( 'Nonce verification failed.', 'surerank' ) );
		}

		// check if the user email is present in the decrypted data.
		if ( empty( $decrypted_data_array['user_email'] ) ) {
			return new WP_Error( 'no_user_email', __( 'No user email found in the decrypted data.', 'surerank' ) );
		}

		// remove the nonce from the decrypted data before saving it to the options.
		unset( $decrypted_data_array['nonce'] );

		// save the user email to the options.
		update_option( self::SETTINGS_KEY, $decrypted_data_array );

		return true;
	}
}
