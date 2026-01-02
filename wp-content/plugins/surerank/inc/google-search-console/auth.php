<?php
/**
 * Google Auth Class
 *
 * Responsible for processing auth for Google Console.
 *
 * @since 1.0.0
 * @package SureRank
 */

namespace SureRank\Inc\GoogleSearchConsole;

use SureRank\Inc\Functions\Requests;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

const GOOGLE_CONSOLE_DATA_DEFAULTS = [
	'access_token'  => '',
	'expires'       => '',
	'refresh_token' => '',
	'site_url'      => '',
	'name'          => '',
	'email'         => '',
	'gravatar'      => '',
];

/**
 * Data Class
 *
 * Responsible for processing auth for Google Console.
 *
 * @since 1.0.0
 */
class Auth {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		if ( ! Settings::get( 'enable_google_console' ) ) {
			return;
		}
		add_action( 'admin_init', [ $this, 'authenticate' ] );
		add_filter( 'surerank_dashboard_localization_vars', [ $this, 'add_localization_vars' ] );
		add_filter( 'surerank_api_controllers', [ $this, 'add_gsc_apis' ] );
	}

	/**
	 * Add GCP APIs to the API controllers.
	 *
	 * @since 1.1.0
	 * @param array<int,string> $controllers List of API controllers.
	 * @return array<int,string> Updated list of API controllers.
	 */
	public function add_gsc_apis( $controllers ) {
		$controllers[] = '\SureRank\Inc\GoogleSearchConsole\Dashboard';

		return $controllers;
	}

	/**
	 * Add localisation variables
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $variables Localisation variables.
	 * @return array<string, mixed> Localisation variables.
	 */
	public function add_localization_vars( $variables ) {

		return array_merge(
			$variables,
			[
				'is_gsc_connected'      => Controller::get_instance()->get_auth_status(),
				'has_gsc_site_selected' => ! empty( Auth::get_instance()->get_credentials( 'site_url' ) ),
				'google_console_user'   => Controller::get_instance()->get_google_console_user_details(),
				'auth_url'              => current_user_can( 'manage_options' ) ? Auth::get_auth_url() : '',
			]
		);
	}

	/**
	 * Authenticate
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function authenticate() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		if ( $_GET['action'] !== 'surerank_auth' || $_GET['page'] !== 'surerank' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get the nonce.
		$nonce = isset( $_GET['nonce'] ) ? sanitize_key( $_GET['nonce'] ) : '';

		// If the nonce is not valid, or if there's no token, then abandon ship.
		if ( false === wp_verify_nonce( $nonce, 'surerank_auth_nonce' ) ) {
			return;
		}

		if ( ! isset( $_GET['access_token'] ) || empty( $_GET['access_token'] ) ) {
			return;
		}

		if ( ! isset( $_GET['refresh_token'] ) || empty( $_GET['refresh_token'] ) ) {
			return;
		}

		if ( ! isset( $_GET['expires'] ) || empty( $_GET['expires'] ) ) {
			return;
		}

		$credentials = [
			'access_token'  => sanitize_text_field( $_GET['access_token'] ),
			'expires'       => time() + absint( $_GET['expires'] ),
			'refresh_token' => sanitize_text_field( $_GET['refresh_token'] ),
			'site_url'      => '',
		];

		$this->save_credentials( $credentials );

		$this->save_user_info();
		$sites = Controller::get_instance()->get_sites();

		if ( isset( $sites['error'] ) ) {
			$this->delete_credentials();
			wp_safe_redirect( admin_url( 'admin.php?page=surerank&gcp_error_code=' . $sites['code'] ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=surerank#/search-console' ) );
		exit;
	}

	/**
	 * Save User Info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_user_info() {
		$updated_user_data             = [];
		$user_data                     = Controller::get_instance()->get_user_info();
		$updated_user_data['name']     = $user_data['name'] ?? '';
		$updated_user_data['email']    = $user_data['email'] ?? '';
		$updated_user_data['gravatar'] = $user_data['picture'] ?? '';
		$this->save_credentials( $updated_user_data );
	}

	/**
	 * Save Credentials
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $credentials Credentials.
	 * @return void
	 */
	public function save_credentials( $credentials ) {
		$data = wp_parse_args( $credentials, GOOGLE_CONSOLE_DATA_DEFAULTS );

		// Check if the credentials are already saved. and array_merge with the new credentials.
		$saved_credentials = $this->get_credentials( null );
		if ( $saved_credentials ) {
			$data = array_merge( $saved_credentials, $credentials );
		}

		$data['access_token']  = Utils::encrypt( $data['access_token'] );
		$data['refresh_token'] = Utils::encrypt( $data['refresh_token'] );
		update_option( 'surerank_google_console_credentials', $data );
	}

	/**
	 * Retrieves the value of a specific key from the connections option.
	 *
	 * @param string $key The key to retrieve from the settings.
	 * @param bool   $raw Whether to return the raw value or not.
	 * @param mixed  $default The default value to return if the key does not exist.
	 *
	 * @return mixed The value of the specified key or the default value.
	 */
	public function get_credentials( ?string $key = null, $raw = false, $default = null ) {
		$credentials = get_option( 'surerank_google_console_credentials', [] );

		if ( empty( $credentials ) ) {
			return false;
		}

		if ( $key === null ) {
			if ( ! $raw ) {
				$credentials['access_token']  = Utils::decrypt( $credentials['access_token'] );
				$credentials['refresh_token'] = Utils::decrypt( $credentials['refresh_token'] );
			}
			return $credentials;
		}

		if ( array_key_exists( $key, $credentials ) ) {
			if ( in_array( $key, [ 'access_token', 'refresh_token' ] ) && ! $raw ) {
				return Utils::decrypt( $credentials[ $key ] );
			}
			return $credentials[ $key ];
		}

		return $default;
	}

	/**
	 * Delete credentials
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function delete_credentials() {
		delete_option( 'surerank_google_console_credentials' );
	}

	/**
	 * Auth Check
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function auth_check() {
		$credentials = $this->get_credentials( null );
		if ( ! $credentials || empty( $credentials['access_token'] ) || empty( $credentials['expires'] ) ) {
			return false;
		}

		$expire_time = $credentials['expires'];
		if ( $expire_time < time() ) {
			$refreshed = $this->refresh_token( $credentials );
			if ( ! $refreshed ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get authentication URL
	 * Keeping this code to localize the auth url, as wp_nonce is not working in API function.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_auth_url() {
		if ( Settings::get( 'enable_google_console' ) !== true ) {
			return '';
		}

		$current_screen = get_current_screen();

		if ( ! isset( $current_screen->base ) ) {
			return '';
		}

		if ( 'toplevel_page_surerank' !== $current_screen->base ) {
			return '';
		}

		$query_args = apply_filters(
			'surerank_auth_api_url_query_args',
			[
				'nonce'  => wp_create_nonce( 'surerank_auth_nonce' ),
				'action' => 'surerank_auth',
			]
		);

		$redirect_uri = add_query_arg(
			$query_args,
			admin_url( 'admin.php?page=surerank' )
		);
		return Utils::get_saas_auth_api_url() . 'search-console/connect/?redirect_uri=' . urlencode( $redirect_uri );
	}

	/**
	 * Refresh Token
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $credentials Credentials.
	 * @return bool
	 */
	private function refresh_token( $credentials ) {
		if ( empty( $credentials['refresh_token'] ) ) {
			return false;
		}

		$body = wp_json_encode( [ 'refresh_token' => $credentials['refresh_token'] ] );
		if ( $body === false ) {
			return false;
		}

		$response = Requests::post(
			Utils::get_saas_auth_api_url() . 'api/search-console/refresh-token',
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => $body,
			]
		);

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body            = json_decode( wp_remote_retrieve_body( $response ), true );
			$body['expires'] = time() + absint( $body['expires'] );
			$this->save_credentials( $body );
			return true;
		}

		return false;
	}
}
