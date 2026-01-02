<?php
/**
 * Phpmail Handler.php
 *
 * Handles sending emails using PHP Mail.
 *
 * @package SureMails\Inc\Emails\Providers\Simulation
 */

namespace SureMails\Inc\Emails\Providers\Simulator;

use SureMails\Inc\Emails\Handler\ConnectionHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SimulationHandler
 *
 * Implements the ConnectionHandler to handle Phpmail Mail email sending and authentication.
 */
class SimulationHandler implements ConnectionHandler {

	/**
	 * PHP mail connection data.
	 *
	 * @var array
	 */
	protected $connection_data;

	/**
	 * Constructor.
	 *
	 * Initializes connection data.
	 *
	 * @param array $connection_data The connection details.
	 */
	public function __construct( array $connection_data ) {
		$this->connection_data = $connection_data;
	}

	/**
	 * Authenticate the PHP Mail connection.
	 *
	 * Since PHP Mail does not provide a direct authentication endpoint, this function
	 * simply saves the connection data and returns a success message.
	 *
	 * @return array The result of the authentication attempt.
	 */
	public function authenticate() {

		return [
			'success' => false,
			'message' => '',
		];
	}

	/**
	 * Send an email using PHP Mail.
	 *
	 * @param array $atts The email attributes.
	 * @param int   $log_id The log ID.
	 * @param array $connection The connection details.
	 * @param array $processed_data The processed email data.
	 *
	 * @return array The result of the sending attempt.
	 */
	public function send( array $atts, $log_id, array $connection, $processed_data ) {
			return [
				'success'         => true,
				'message'         => __( 'Email sending was simulated, but no email was actually sent.', 'suremails' ),
				'send'            => true,
				'email_simulated' => true,
			];
	}

	/**
	 * Get the PHP Mail connection options.
	 *
	 * @return array The PHP Mail connection options.
	 */
	public static function get_options() {
		return [];
	}
}
