<?php
/**
 * SendTestEmail class
 *
 * Handles the REST API endpoint for testing email connection.
 *
 * @package SureMails\Inc\API
 */

namespace SureMails\Inc\API;

use Exception;
use SureMails\Inc\ConnectionManager;
use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;
use SureMails\Inc\Traits\SendEmail;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SendTestEmail
 *
 * Handles the `/send-test-email` REST API endpoint.
 */
class SendTestEmail extends Api_Base {

	use Instance;
	use SendEmail;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/send-test-email';

	/**
	 * Register API routes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_send_test_email' ],
					'permission_callback' => [ $this, 'validate_permission' ],
					'args'                => [
						'from_email' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_email',
						],
						'to_email'   => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_email',
						],
						'type'       => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'id'         => [
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
	 * Handle sending a test email through the connection.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST request object.
	 *
	 * @throws Exception Exception.
	 * @return WP_REST_Response The REST API response.
	 */
	public function handle_send_test_email( $request ) {
		try {
			$params     = $request->get_json_params();
			$from_email = $params['from_email'];
			$to_email   = $params['to_email'];
			$id         = $params['id'];

			$options = Settings::instance()->get_settings( 'connections' );

			// Find the connection based on the provided details.
			$connection = $options[ $id ] ?? null;
			if ( empty( $connection ) ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'message' => __( 'Connection not found.', 'suremails' ),
					],
					404
				);
			}

			$connection_manager = ConnectionManager::instance();
			$connection_manager->set_connection( $connection );
			$connection_manager->set_is_testing( true );

			// Prepare email headers.
			$headers = [
				'From: ' . $from_email,
				self::get_html_headers(),
			];

			$body = $this->get_email_template();

			if ( ! $body ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'message' => __( 'Failed to get email template.', 'suremails' ),
					],
					404
				);
			}

			// Translators: %s is the site name.
			$subject = sprintf( __( 'SureMail: Test Email - %s', 'suremails' ), get_bloginfo( 'name' ) );

			// Send the test email.
			if ( self::send( $to_email, $subject, $body, $headers, [] ) ) {
				return new WP_REST_Response(
					[
						'success' => true,
						'message' => __( 'Email sent successfully.', 'suremails' ),
					],
					200
				);
			}
			throw new Exception( __( 'Failed to send test email', 'suremails' ) );
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => sprintf( 'An error occurred: %s', $e->getMessage() ),
				],
				500
			);
		}
	}

	/**
	 * Get Template HTML
	 *
	 * @return string|false
	 * @since 0.0.1
	 */
	private function get_email_template() {
		// Get site name, current timestamp, and year.
		$site_name    = get_bloginfo( 'name' );
		$timestamp    = strtotime( current_time( 'mysql' ) );
		$current_time = $timestamp ? gmdate( 'Y-m-d h:i:s A', $timestamp ) : 'Invalid timestamp';
		$current_year = gmdate( 'Y' );

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test Email</title>
</head>
<body>
	<div style="margin-top: 1.5rem; font-size: 0.875rem; color: #111827;">
		<div style="margin-left: auto; margin-right: auto; max-width: 32.5rem; background-color: #FFFFFF; padding: 1.5rem;">
			<div>
				<p>Hi there,</p>
				<p>This is a test email sent to verify your email connection with SureMail. If you're receiving this message, your setup is working correctly!</p>
				<p>If you have any issues or don't receive this email, please check your settings or contact our support team for assistance.</p>
				<p>Thank you!</p>
				<p>Best regards,<br>The SureMail Team</p>
			</div>
			<div>
				<p>This email was sent from <?php echo esc_attr( $site_name ); ?> at <?php echo esc_attr( $current_time ); ?>.</p>
				<p>&copy; <?php echo esc_attr( $current_year ); ?> SureMail. All rights reserved.</p>
			</div>
		</div>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}
}

// Initialize the SendTestEmail singleton.
SendTestEmail::instance();
