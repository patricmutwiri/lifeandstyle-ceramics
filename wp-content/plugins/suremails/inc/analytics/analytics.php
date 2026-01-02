<?php
/**
 * Analytics Class
 *
 * @since 1.6.0
 * @package SureMails\Inc\Analytics
 */

namespace SureMails\Inc\Analytics;

use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Analytics
 */
class Analytics {

	use Instance;

	/**
	 * Constructor: hook analytics filter.
	 */
	public function __construct() {
		add_filter( 'bsf_core_stats', [ $this, 'add_analytics_data' ] );
	}

	/**
	 * Add analytics data to bsf_core_stats.
	 *
	 * @param array $stats_data Existing stats data.
	 * @return array Modified stats data with SureMails metrics.
	 */
	public function add_analytics_data( $stats_data ) {
		$settings         = Settings::instance()->get_settings();
		$connections_data = isset( $settings['connections'] ) && is_array( $settings['connections'] ) ? $settings['connections'] : [];

		$email_simulation  = Settings::instance()->get_email_simulation_status();
		$log_email         = ( isset( $settings['log_emails'] ) && $settings['log_emails'] === 'yes' );
		$backup_connection = $this->get_backup_connection_status( $connections_data );
		$connections       = $this->get_connection_counts_by_type( $connections_data );
		$content_guard     = Settings::instance()->get_content_guard_status();

		$stats_data['plugin_data']['suremails'] = [
			'version'        => SUREMAILS_VERSION,
			'site_language'  => get_locale(),
			'php_version'    => phpversion(),
			'array_values'   => [
				'connections'  => $connections,
				'form_plugins' => $this->get_form_plugins(),
			],
			'boolean_values' => [
				'email_simulation'    => $email_simulation,
				'log_email'           => $log_email,
				'content_guard'       => $content_guard,
				'ottokit_integration' => apply_filters( 'suretriggers_is_user_connected', '' ),
				'backup_connection'   => $backup_connection,
			],
		];

		return $stats_data;
	}

	/**
	 * Get active form plugin slugs.
	 */
	private function get_form_plugins(): array {
		$forms = [];

		$known_form_plugins = [
			'gravityforms/gravityforms.php' => 'gravityforms',
			'fluentform/fluentform.php'     => 'fluentforms',
			'wpforms-lite/wpforms-lite.php' => 'wpforms',
			'wpforms/wpforms.php'           => 'wpforms',
			'ws-form/ws-form.php'           => 'wsform',
			'sureforms/sureforms.php'       => 'sureforms',
		];

		$active_plugins = get_option( 'active_plugins', [] );

		foreach ( $known_form_plugins as $plugin_file => $slug ) {
			if ( in_array( $plugin_file, $active_plugins, true ) ) {
				$forms[] = $slug;
			}
		}

		return array_values( array_unique( $forms ) );
	}

	/**
	 * Count email connections by type.
	 *
	 * @param array $connections Connections data.
	 * @return array Count of connections by type.
	 */
	private function get_connection_counts_by_type( array $connections ): array {
		$counts = [];

		foreach ( $connections as $conn ) {
			if ( isset( $conn['type'] ) ) {
				$type            = strtolower( $conn['type'] );
				$counts[ $type ] = isset( $counts[ $type ] ) ? $counts[ $type ] + 1 : 1;
			}
		}

		return $counts;
	}

	/**
	 * Check for duplicate from_email (used for backup detection).
	 *
	 * @param array $connections Connections data.
	 * @return bool True if a duplicate from_email is found, otherwise false.
	 */
	private function get_backup_connection_status( array $connections ): bool {
		$from_emails = [];

		foreach ( $connections as $conn ) {
			if ( isset( $conn['from_email'] ) ) {
				$email = $conn['from_email'];
				if ( in_array( $email, $from_emails, true ) ) {
					return true;
				}
				$from_emails[] = $email;
			}
		}

		return false;
	}
}
