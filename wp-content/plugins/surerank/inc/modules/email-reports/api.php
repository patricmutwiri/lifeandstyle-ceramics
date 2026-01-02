<?php
/**
 * Email Reports API class
 *
 * Handles email reports related REST API endpoints.
 *
 * @package SureRank\Inc\Modules\EmailReports
 * @since 1.6.0
 */

namespace SureRank\Inc\Modules\EmailReports;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\API\Api_Base;
use SureRank\Inc\Functions\Sanitize;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Api
 *
 * @package SureRank\Inc\Modules\EmailReports
 * @since 1.6.0
 */
class Api extends Api_Base {

	use Get_Instance;

	/**
	 * Register API routes.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function register_routes() {
		// Register routes for email reports settings.
		register_rest_route(
			$this->get_api_namespace(),
			'/email-reports/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'validate_permission' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => apply_filters(
						'surerank_email_reports_settings_args',
						[
							'enabled'        => [
								'required'          => true,
								'type'              => 'boolean',
								'sanitize_callback' => 'rest_sanitize_boolean',
							],
							'recipientEmail' => [
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_email',
								'validate_callback' => 'is_email',
							],
							'scheduledOn'    => [
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'enum'              => array_keys( Utils::get_instance()->get_schedule_on_values() ),
							],
						]
					),
				],
			]
		);

		// Register route for sending test email.
		register_rest_route(
			$this->get_api_namespace(),
			'/email-reports/send-test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_test_email' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'recipientEmail' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => 'is_email',
					],
				],
			]
		);
	}

	/**
	 * Get email reports settings.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function get_settings() {
		$settings = Utils::get_instance()->get_settings();
		Send_Json::success(
			[
				'data' => $settings,
			]
		);
	}

	/**
	 * Save email reports settings.
	 * 
	 * @since 1.6.0
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return void
	 */
	public function save_settings( $request ) {
		$enabled         = (bool) $request->get_param( 'enabled' );
		$recipient_email = Sanitize::email( $request->get_param( 'recipientEmail' ) );
		$day_of_week     = Sanitize::text( $request->get_param( 'scheduledOn' ) );

		// If enabled, require recipientEmail.
		if ( $enabled && empty( $recipient_email ) ) {
			Send_Json::error( [ 'message' => __( 'Recipient email is required when enabled.', 'surerank' ) ] );
			return;
		}

		$settings = wp_parse_args(
			[
				'enabled'        => $enabled,
				'recipientEmail' => $recipient_email,
				'scheduledOn'    => $day_of_week,
			],
			Utils::get_instance()->get_settings()
		);

		$settings = apply_filters( 'surerank_email_reports_save_settings', $settings, $request );

		update_option( 'surerank_email_reports_settings', $settings, false ); 

		Send_Json::success(
			[
				'message' => __( 'Email summary settings saved successfully.', 'surerank' ),
				'data'    => $settings,
			]
		);
	}

	/**
	 * Send test email.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 * @return void
	 */
	public function send_test_email( $request ) {
		$recipient_email = Sanitize::email( $request->get_param( 'recipientEmail' ) );

		if ( empty( $recipient_email ) ) {
			$settings        = Utils::get_instance()->get_settings();
			$recipient_email = ! empty( $settings['recipientEmail'] ) ? $settings['recipientEmail'] : null;
		}

		// Validate email.
		if ( empty( $recipient_email ) || ! is_email( $recipient_email ) ) {
			Send_Json::error( [ 'message' => __( 'Please provide a valid email address.', 'surerank' ) ] );
			return;
		}

		// Get the controller instance and send email.
		$controller = Controller::get_instance();
		$sent       = $controller->send_email_to( $recipient_email );

		if ( ! $sent ) {
			Send_Json::error(
				[
					'message' => __( 'Failed to send test email. Please check your email configuration.', 'surerank' ),
				]
			);
			return;
		}
		Send_Json::success(
			[
				'message' => sprintf(
					/* translators: %s: Recipient email address */
					__( 'Test email sent successfully to %s.', 'surerank' ),
					$recipient_email
				),
			]
		);
	}
}
