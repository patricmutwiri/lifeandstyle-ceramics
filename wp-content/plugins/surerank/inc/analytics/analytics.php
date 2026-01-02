<?php
/**
 * Analytics class helps to connect BSFAnalytics.
 *
 * @package surerank.
 */

namespace SureRank\Inc\Analytics;

use SureRank\Inc\Functions\Defaults;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\GoogleSearchConsole\Controller;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Analytics class.
 *
 * @since 1.4.0
 */
class Analytics {
	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 1.4.0
	 */
	public function __construct() {

		if ( ! class_exists( 'Astra_Notices' ) ) {
			require_once SURERANK_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
		}

		/*
		* BSF Analytics.
		*/
		if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
			require_once SURERANK_DIR . 'inc/lib/bsf-analytics/class-bsf-analytics-loader.php';
		}

		if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
			return;
		}

		$surerank_bsf_analytics = \BSF_Analytics_Loader::get_instance();

		$surerank_bsf_analytics->set_entity(
			[
				'surerank' => [
					'product_name'        => 'SureRank',
					'path'                => SURERANK_DIR . 'inc/lib/bsf-analytics',
					'author'              => 'SureRank',
					'time_to_display'     => '+24 hours',
					'hide_optin_checkbox' => true,
				],
			]
		);

		add_filter( 'bsf_core_stats', [ $this, 'add_surerank_analytics_data' ] );
	}

	/**
	 * Callback function to add SureRank specific analytics data.
	 *
	 * @param array<string, mixed> $stats_data existing stats_data.
	 * @since 1.4.0
	 * @return array<string, mixed>
	 */
	public function add_surerank_analytics_data( $stats_data ) {
		$other_stats               = [
			'site_language'     => get_locale(),
			'gsc_connected'     => $this->get_gsc_connected(),
			'plugin_version'    => SURERANK_VERSION,
			'php_version'       => phpversion(),
			'wordpress_version' => get_bloginfo( 'version' ),
			'is_active'         => $this->is_active(),
		];
		$stats                     = array_merge(
			$other_stats,
			$this->get_failed_site_seo_checks(),
			$this->get_enabled_features()
		);
		$stats_data['plugin_data'] = [
			'surerank' => $stats,
		];
		return $stats_data;
	}

	/**
	 * Compare top-level and one-level nested settings with defaults.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @param array<string, mixed> $defaults Default settings.
	 * @return array<string, mixed> Changed settings (top-level + one-level deep).
	 */
	public static function shallow_two_level_diff( array $settings, array $defaults ) {
		$difference = [];

		if ( isset( $defaults['surerank_analytics_optin'] ) ) {
			unset( $defaults['surerank_analytics_optin'] );
		}

		foreach ( $settings as $key => $value ) {

			// Key missing in defaults = changed.
			if ( ! array_key_exists( $key, $defaults ) ) {
				$difference[ $key ] = $value;
				continue;
			}

			// If value is an array, only check one level deep.
			if ( is_array( $value ) && is_array( $defaults[ $key ] ) ) {
				$nested_diff = [];
				foreach ( $value as $sub_key => $sub_value ) {
					if ( ! array_key_exists( $sub_key, $defaults[ $key ] ) || $sub_value !== $defaults[ $key ][ $sub_key ] ) {
						$nested_diff[ $sub_key ] = $sub_value;
					}
				}
				if ( ! empty( $nested_diff ) ) {
					$difference[ $key ] = $nested_diff;
				}
			} elseif ( $value !== $defaults[ $key ] ) {
				// Compare scalar values directly.
				$difference[ $key ] = $value;
			}
		}

		return $difference;
	}

	/**
	 * Get failed site SEO checks.
	 *
	 * @return array<string,int>
	 */
	private function get_failed_site_seo_checks() {
		$failed_checks      = Get::option( 'surerank_site_seo_checks', [] );
		$failed_checks_list = [];
		foreach ( $failed_checks as $check ) {
			foreach ( $check as $key => $value ) {
				if ( isset( $value['status'] ) && $value['status'] === 'error' ) {
					$failed_checks_list[ $key ] = 0;
				}
			}
		}
		return $failed_checks_list;
	}

	/**
	 * Get enabled features.
	 *
	 * @return array<string, mixed>
	 */
	private function get_enabled_features() {
		return [
			'enable_page_level_seo' => Settings::get( 'enable_page_level_seo' ),
			'enable_google_console' => Settings::get( 'enable_google_console' ),
			'enable_schemas'        => Settings::get( 'enable_schemas' ),
		];
	}

	/**
	 * Get Google Search Console connected status.
	 *
	 * @return bool
	 */
	private function get_gsc_connected() {
		return Controller::get_instance()->get_auth_status();
	}

	/**
	 * Check if SureRank is active (has settings different from defaults).
	 *
	 * @return bool
	 * @since 1.5.0
	 */
	private function is_active() {

		$surerank_defaults = Defaults::get_instance()->get_global_defaults();

		$surerank_settings = get_option( SURERANK_SETTINGS, [] );

		if ( is_array( $surerank_settings ) && is_array( $surerank_defaults ) ) {
				$changed_settings = self::shallow_two_level_diff( $surerank_settings, $surerank_defaults );
			if ( count( $changed_settings ) >= 1 ) {
				return true;
			}
		}

		global $wpdb;
			$like = $wpdb->esc_like( 'surerank_settings_' ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$posts = $wpdb->get_col(
				$wpdb->prepare(
					"
						SELECT DISTINCT pm.post_id
						FROM {$wpdb->postmeta} pm
						INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
						WHERE pm.meta_key LIKE %s
						AND p.post_status = 'publish'
						LIMIT 1
					",
					$like
				)
			);

		if ( ! empty( $posts ) && is_array( $posts ) ) {
			return true;
		}

		return false;
	}
}
