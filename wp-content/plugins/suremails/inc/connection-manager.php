<?php
/**
 * ConnectionManager.php
 *
 * Manages email connections, fallback mechanisms, and testing states.
 *
 * @package SureMails\Inc\Emails\Handler
 */

namespace SureMails\Inc;

use PHPMailer\PHPMailer\PHPMailer;
use SureMails\Inc\Controller\Logger;
use SureMails\Inc\Traits\Instance;

/**
 * Class ConnectionManager
 *
 * Manages email connections, including fallback connections and testing states.
 */
class ConnectionManager {

	use Instance;

	/**
	 * If it's the default connection.
	 *
	 * @var bool
	 */
	public $is_default = false;

	/**
	 * If email simulation is enabled. If enabled, the email will not be sent. Instead, it will be logged. Default is false.
	 *
	 * @var bool
	 */
	public $email_simulation = false;

	/**
	 * If no connection found from a from_email then this will be set to true to get from_email connections same a from_email of default connection.
	 * The default connection will be the first connection in the fallback sequence of from_email connections.
	 *
	 * @var bool
	 */
	public $swap_default_connection = false;

	/**
	 * Global PHPMailer instance.
	 *
	 * @var PHPMailer|null
	 */
	private static ?PHPMailer $phpmailer = null;

	/**
	 * All available connections.
	 *
	 * @var array
	 */
	private $connections = [];

	/**
	 * Array of connections filtered by from_email.
	 *
	 * @var array
	 */
	private $from_email_connections = [];

	/**
	 * The currently selected connection data.
	 *
	 * @var array|null
	 */
	private $current_connection = null;

	/**
	 * Indicates if the current email send is using a fallback connection.
	 *
	 * @var bool
	 */
	private $is_fallback = false;

	/**
	 * Indicates if the current email send is a test email.
	 *
	 * @var bool
	 */
	private $is_testing = false;

	/**
	 * The email address to send from.
	 *
	 * @var string|null
	 */
	private $from_email = null;

	/**
	 * The index of the current connection in the fallback sequence.
	 *
	 * @var int|null
	 */
	private $current_index = null;

	/**
	 * If it's the last connection in the fallback sequence.
	 *
	 * @var bool
	 */
	private $is_last = false;

	/**
	 * If it's a resend.
	 *
	 * @var bool
	 */
	private $is_resend = false;

	/**
	 * If it's a retry.
	 *
	 * @var bool
	 */
	private $is_retried = false;

	/**
	 * If it's the first connection.
	 *
	 * @var bool
	 */
	private $is_first = false;

	/**
	 * Private constructor to enforce Singleton pattern.
	 *
	 * Initializes connection data and sets up hooks for refreshing connections.
	 */
	private function __construct() {
		$this->connections      = Settings::instance()->get_settings();
		$this->email_simulation = Settings::instance()->get_email_simulation_status();
	}

	/**
	 * Sets the current connection data.
	 *
	 * @param array $connection The connection data to set.
	 * @return void
	 */
	public function set_connection( array $connection ) {
		$this->current_connection = $connection;
	}

	/**
	 * Retrieves the current connection data.
	 *
	 * @return array|null The current connection data or null if not set.
	 */
	public function get_connection() {
		return $this->current_connection;
	}

	/**
	 * Sets the entire connections data.
	 *
	 * @param array $connections The array of all connection data.
	 * @return void
	 */
	public function set_connections( array $connections ) {
		$this->connections = $connections;
	}

	/**
	 * Retrieves all connections data.
	 *
	 * @return array|null The array of all connections or null if not set.
	 */
	public function get_connections() {
		return $this->connections;
	}

	/**
	 * Sets the fallback status.
	 *
	 * @param bool $is_fallback Indicates if using fallback connection.
	 * @return void
	 */
	public function set_is_fallback( bool $is_fallback ) {
		$this->is_fallback = $is_fallback;
	}

	/**
	 * Retrieves the fallback status.
	 *
	 * @return bool The fallback status.
	 */
	public function get_is_fallback() {
		return $this->is_fallback;
	}

	/**
	 * Sets the from_email address.
	 *
	 * @param string $from_email The email address to send from.
	 * @return void
	 */
	public function set_from_email( string $from_email ) {
		$this->from_email = strtolower( $from_email );
	}

	/**
	 * Retrieves the from_email address.
	 *
	 * @return string|null The from_email address or null if not set.
	 */
	public function get_from_email() {
		return $this->from_email;
	}
	/**
	 * Sets the testing status.
	 *
	 * @param bool $is_testing Indicates if sending a test email.
	 * @return void
	 */
	public function set_is_testing( bool $is_testing ) {
		$this->is_testing = $is_testing;
	}

	/**
	 * Retrieves the testing status.
	 *
	 * @return bool The testing status.
	 */
	public function get_is_testing() {
		return $this->is_testing;
	}

	/**
	 * Sets the last status.
	 *
	 * @param bool $is_retried Indicates if the email is retried.
	 * @return void
	 */
	public function set_is_retry( bool $is_retried ) {
		$this->is_retried = $is_retried;
	}

	/**
	 * Retrieves the last status.
	 *
	 * @return bool The last status.
	 */
	public function get_is_retried() {
		return $this->is_retried;
	}

	/**
	 * Retrieves the last status.
	 *
	 * @return bool The last status.
	 */
	public function get_is_last() {
		return $this->is_last;
	}

	/**
	 * Sets the is first connection.
	 *
	 * @return bool
	 */
	public function get_is_first() {
		return $this->is_first;
	}

	/**
	 * Set if the email is a resend.
	 *
	 * @param bool $is_resend Indicates if the email is a resend.
	 * @return void
	 */
	public function set_is_resend( bool $is_resend ) {
		$this->is_resend = $is_resend;
	}

	/**
	 * Retrieves the resend status.
	 *
	 * @return bool The resend status.
	 */
	public function get_is_resend() {
		return $this->is_resend;
	}

	/**
	 * Initializes the PHPMailer instance if not already set.
	 *
	 * @return PHPMailer The PHPMailer instance.
	 */
	public function get_phpmailer() {
		if ( ! self::$phpmailer ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

			self::$phpmailer             = new PHPMailer( true );
			self::$phpmailer::$validator = static function ( $email ) {
				return (bool) is_email( $email );
			};
		}

		return self::$phpmailer;
	}

	/**
	 * Resets the PHPMailer instance to its default state.
	 *
	 * @return void
	 */
	public function reset_phpmailer() {
		self::$phpmailer = null;
	}

	/**
	 * Adjusts the current PHPMailer instance with connection-specific settings.
	 *
	 * @param array $connection The connection settings.
	 * @return void
	 */
	public function configure_phpmailer( array $connection ) {
		$phpmailer = $this->get_phpmailer();

		$from_email = $connection['from_email'] ?? null;
		$from_name  = $connection['from_name'] ?? null;

		if ( $from_email ) {
			$phpmailer->setFrom( $from_email, $from_name );
		}
	}

	/**
	 * Retrieves the next connection based on priority.
	 *
	 * @return array|null The next connection details or null if no more connections are available.
	 */
	public function get_next_connection() {
		if ( $this->current_index === null ) {
			$this->current_index = 0;
			$this->is_first      = true;
		} else {
			$this->current_index++;
			$this->is_first = false;
		}

		$next_index             = $this->current_index;
		$from_email_connections = $this->from_email_connections;

		if ( $next_index + 1 >= count( $from_email_connections ) ) {
			$this->is_last = true;
		}
		if ( $next_index >= count( $from_email_connections ) ) {

			$this->is_last       = true;
			$this->current_index = null;
			return null;
		}

		return $from_email_connections[ $next_index ];
	}

	/**
	 * Retrieves the fallback connection based on priority.
	 *
	 * @return array|null The fallback connection details or null if not found.
	 */
	public function get_priority_based_fallback_connection() {
		$connections = $this->connections;
		if ( empty( $connections ) ) {
			return null;
		}
		$connections = $connections['connections'] ?? null;
		if ( ! $connections ) {
			return null;
		}

		$from_email_connections = $this->get_from_email_connections();

		if ( empty( $from_email_connections ) ) {
			return null;
		}

		return $this->get_next_connection();
	}

	/**
	 * Resets the ConnectionManager state to default.
	 *
	 * @return void
	 */
	public function reset() {
		$this->current_index           = null;
		$this->is_fallback             = false;
		$this->is_testing              = false;
		$this->from_email_connections  = [];
		$this->current_connection      = null;
		$this->from_email              = null;
		$this->is_last                 = false;
		$this->is_first                = false;
		$this->is_default              = false;
		$this->is_resend               = false;
		$this->is_retried              = false;
		$this->swap_default_connection = false;
		Logger::instance()->set_id( null );
	}

	/**
	 * Retrieves the default connection based on settings.
	 *
	 * @param bool $set_checks bool If set to true, it will set the is_default property.
	 * @return array|null The default connection details or null if not found.
	 */
	public function get_default_connection( $set_checks = true ) {
		$settings    = $this->get_connections();
		$connections = $settings['connections'] ?? null;

		if ( ! $connections ) {
			return null;
		}

		$default_connection_id = $settings['default_connection']['id'] ?? null;
		$default_connection    = $connections[ $default_connection_id ] ?? null;
		if ( $default_connection && $set_checks ) {
			$this->is_default = true;
		}
		return $default_connection;
	}

	/**
	 * Retrieves and sorts connections filtered by the current from_email.
	 *
	 * @return array|null The array of filtered and sorted connections or null if not found.
	 */
	private function get_from_email_connections() {
		if ( $this->get_from_email() === null ) {
			return null;
		}

		$connections = $this->connections['connections'] ?? null;

		if ( ! $connections ) {
			return null;
		}

		$from_email_connections = [];

		foreach ( $connections as $connection ) {
			if ( strtolower( $connection['from_email'] ) === $this->from_email ) {
				$send = $connection;

				$from_email_connections[] = $send;
			}
		}

		// Sort the $from_email_connections array by 'priority' in ascending order.
		usort(
			$from_email_connections,
			static function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		// If no connection found from a from_email then this will be set to true to get from_email connections same a from_email of default connection.
		// The default connection will be the first connection in the fallback sequence of from_email connections. This is to ensure that the default connection is always tried first. It will try with default connection then it will try with other from_email connections based on priority.
		if ( $this->swap_default_connection ) {
			$default_connection_id = $this->connections['default_connection']['id'] ?? null;
			if ( $default_connection_id ) {
				foreach ( $from_email_connections as $key => $from_email_connection ) {
					if ( $from_email_connection['id'] === $default_connection_id ) {
						$default_connection = $from_email_connections[ $key ];
						unset( $from_email_connections[ $key ] );
						array_unshift( $from_email_connections, $default_connection );
					}
				}
			}
		}

		$this->from_email_connections = $from_email_connections;

		return $from_email_connections;
	}
}
