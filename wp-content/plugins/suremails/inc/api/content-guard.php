<?php
/**
 * ContentGuard class
 *
 * Handles the REST API endpoint for the Content Guard.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ContentGuard
 */
class ContentGuard extends Api_Base {
	use Instance;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/content-guard';

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base . '/activate',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'activate' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
			]
		);

		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base . '/user-details',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_user_details' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'first_name' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'last_name'  => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'email'      => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_email',
						],
						'skip'       => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Initiates the auth process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {

		$activated        = get_option( 'suremails_content_guard_activated', 'no' );
		$activated_status = 'yes' === $activated ? 'no' : 'yes';
		update_option( 'suremails_content_guard_activated', $activated_status );

		wp_send_json_success();
	}

	/**
	 * Handles the access key.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @since 1.0.0
	 * @return void
	 */
	public function save_user_details( $request ) {

		$body = $request->get_params();

		Settings::instance()->set_user_details( $body );

		if ( 'no' === $body['skip'] ) {
			$this->subscribe_user( $body );
		}

		wp_send_json_success();
	}

	/**
	 * Subscribes the user to the email list.
	 *
	 * @param array $details The user details.
	 * @since 1.0.0
	 * @return void
	 */
	public function subscribe_user( $details ) {

		$subscription_status = Settings::instance()->get_user_details( 'lead', false );

		if ( $subscription_status ) {
			return;
		}

		$url = 'https://websitedemos.net/wp-json/suremails/v1/subscribe/';

		$args = [
			'body' => [
				'EMAIL'     => $details['email'],
				'FIRSTNAME' => $details['first_name'],
				'LASTNAME'  => $details['last_name'],
			],
		];

		$response = wp_safe_remote_post( $url, $args );

		if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			$details['lead'] = true;

			Settings::instance()->set_user_details( $details );
		}
	}

}

// Initialize the ContentGuard singleton.
ContentGuard::instance();
