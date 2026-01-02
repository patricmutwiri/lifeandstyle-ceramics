<?php
/**
 * SureMails Update Class
 *
 * Handle the updates for the SureMails plugin.
 *
 * @package SureMails\Admin
 */

namespace SureMails\Inc\Admin;

use SureMails\Inc\Settings;
use SureMails\Inc\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * SureMails Update Class
 *
 * @since 0.0.5
 */
class Update {

	use Instance;

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
	private static $db_updates = [
		'1.3.0' => [
			'updater_1_3_0',
		],
		'1.4.2' => [
			'updater_1_4_2',
		],
		'1.5.0' => [
			'updater_1_5_0',
		],
	];

	/**
	 * Constructor
	 *
	 * @since 0.0.5
	 */
	public function __construct() {
		if ( ! defined( 'SUREMAILS_MIGRATIONS' ) ) {
			define( 'SUREMAILS_MIGRATIONS', 'suremails_migrations' );
		}
		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( 'suremails_update_before', [ $this, 'migrate' ] );
	}

	/**
	 * Update
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function init() {

		// Get auto saved version number.
		$saved_version = get_option( 'suremails-version', false );

		// Update auto saved version number.
		if ( ! $saved_version || ! is_string( $saved_version ) ) {

			// Update current version. - Fresh installation.
			update_option( 'suremails-version', SUREMAILS_VERSION );
			return;
		}

		// If equals then return. - Means this is the current version.
		if ( version_compare( $saved_version, SUREMAILS_VERSION, '=' ) ) {
			return;
		}

		do_action( 'suremails_update_before' );

		// Auto update product latest version.
		update_option( 'suremails-version', SUREMAILS_VERSION, false );

		do_action( 'suremails_update_after' );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Get migration status
	 *
	 * @param string $version Version number.
	 * @since 1.3.0
	 * @return bool
	 */
	public function get_migration_status( $version ) {
		$migrations = get_option( SUREMAILS_MIGRATIONS, [] );
		if ( empty( $migrations ) ) {
			return false;
		}
		return $migrations[ $version ] ?? false;
	}

	/**
	 * Set migration status
	 *
	 * @param string $version Version number.
	 * @param bool   $status Status.
	 * @since 1.3.0
	 * @return void
	 */
	public function set_migration_status( $version, $status ) {
		$migrations             = get_option( SUREMAILS_MIGRATIONS, [] );
		$migrations[ $version ] = $status;
		update_option( SUREMAILS_MIGRATIONS, $migrations );
	}

	/**
	 * Migration process
	 *
	 * @return void
	 * @since 1.3.0
	 */
	public function migrate() {
		$current_db_version = get_option( 'suremails-version', false );

		if ( empty( $current_db_version ) ) {
			return;
		}

		if ( count( $this->get_db_update_callbacks() ) > 0 ) {
			foreach ( $this->get_db_update_callbacks() as $version => $update_callbacks ) {
				if ( version_compare( $current_db_version, $version, '<' ) ) {
					foreach ( $update_callbacks as $update_callback ) {
						if ( false === $this->get_migration_status( $version ) ) {
							$this->$update_callback();
							$this->set_migration_status( $version, true );
						}
					}
				}
			}
		}
	}

	/**
	 * Migrations for version 1.3.0
	 *
	 * @return void
	 * @since 1.3.0
	 */
	public function updater_1_3_0() {
		$settings = Settings::instance()->get_raw_settings();

		if ( empty( $settings ) ) {
			return;
		}

		if ( empty( $settings['connections'] ) ) {
			return;
		}

		update_option( SUREMAILS_CONNECTIONS, Settings::instance()->encrypt_all( $settings ) );
	}

	/**
	 * Migrations for version 1.5.0
	 * Update email simulation setting to 'no' if it is already set. This is to ensure that the setting is set to 'no' by default.
	 *
	 * @return void
	 * @since 1.5.0
	 */
	public function updater_1_5_0() {
		$settings = Settings::instance()->get_raw_settings();

		$is_set_email_simulation = isset( $settings['email_simulation'] );
		if ( $is_set_email_simulation ) {
			$settings['email_simulation'] = 'no';
			update_option( SUREMAILS_CONNECTIONS, $settings );
		}
	}

	/**
	 * Migrations for version 1.4.2
	 * Remove empty connection id from settings. The gmail connection id was empty and connections were stored in non encrypted format. This is a fix for that.
	 *
	 * @return void
	 * @since 1.4.2
	 */
	public function updater_1_4_2() {

		$settings            = Settings::instance()->get_raw_settings();
		$empty_connection_id = $settings['connections']['id'] ?? null;

		if ( $empty_connection_id !== null ) {
			unset( $settings['connections']['id'] );
			update_option( SUREMAILS_CONNECTIONS, Settings::instance()->encrypt_all( $settings ) );
		}
	}
}
