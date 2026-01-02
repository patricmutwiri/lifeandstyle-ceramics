<?php
/**
 * Settings Exporter class
 *
 * Handles exporting SureRank settings to JSON format.
 *
 * @package SureRank\Inc\Import_Export
 * @since 1.2.0
 */

namespace SureRank\Inc\Import_Export;

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Settings_Exporter
 *
 * @since 1.2.0
 */
class Settings_Exporter {

	use Get_Instance;

	/**
	 * Export settings for specified categories
	 *
	 * @param array<int, string> $categories Array of category IDs to export.
	 * @return array<string, mixed> Export data with success status and data/message.
	 */
	public function export( $categories = [] ) {
		if ( empty( $categories ) ) {
			return Utils::error_response(
				__( 'No categories specified for export.', 'surerank' )
			);
		}

		$export_data             = Utils::get_export_header();
		$export_data['settings'] = [];

		$exported_count = 0;

		foreach ( $categories as $category ) {
			if ( ! $this->is_valid_category( $category ) ) {
				continue;
			}

			$category_data = $this->export_category( $category, $categories );
			if ( ! empty( $category_data ) ) {
				$export_data['settings'][ $category ] = $category_data;
				$exported_count++;
			}
		}

		$export_data = self::clean_up_image_keys( $export_data );

		if ( 0 === $exported_count ) {
			return Utils::error_response(
				__( 'No settings found to export for the selected categories.', 'surerank' )
			);
		}

		return Utils::success_response(
			$export_data,
			sprintf(
				/* translators: %d: number of categories exported */
				__( 'Successfully exported settings for %d categories.', 'surerank' ),
				$exported_count
			)
		);
	}

	/**
	 * Import settings from JSON data.
	 *
	 * @param array<string,mixed> $export_data JSON formatted settings data.
	 * @return array<string, mixed> Import results.
	 */
	public static function clean_up_image_keys( $export_data ) {
		$image_keys      = Utils::get_image_setting_keys();
		$global_settings = $export_data['settings']['global'] ?? [];
		foreach ( $image_keys as $key ) {
			if ( isset( $global_settings[ $key ] ) ) {
				$global_settings[ $key ] = '';
			}
		}
		$export_data['settings']['global'] = $global_settings;
		return $export_data;
	}

	/**
	 * Export settings for a specific category
	 *
	 * @param string             $category Category ID.
	 * @param array<int, string> $categories All selected categories for conditional logic.
	 * @return array<string, mixed>|bool Category settings data.
	 */
	public function export_category( $category, $categories = [] ) {
		$all_settings = $this->get_all_settings();
		if ( empty( $all_settings ) ) {
			return [];
		}

		switch ( $category ) {
			case 'global':
				return $this->get_global_settings( $all_settings );
			case 'schema':
				return $this->get_schema_settings( $all_settings );
			case 'images':
				return $this->get_image_settings( $all_settings );
			default:
				return [];
		}
	}

	/**
	 * Check if a category is valid
	 *
	 * @param string $category Category ID.
	 * @return bool True if valid, false otherwise.
	 */
	public function is_valid_category( $category ) {
		return array_key_exists( $category, $this->get_categories() );
	}

	/**
	 * Get all available categories for export
	 *
	 * @return array<string, string> Array of categories with IDs and labels.
	 */
	public function get_categories() {
		return [
			'global' => __( 'Global Settings', 'surerank' ),
			'schema' => __( 'Schema Settings', 'surerank' ),
			'images' => __( 'Required Resources', 'surerank' ),
		];
	}

	/**
	 * Get all settings (defaults merged with saved)
	 *
	 * @return array<string, mixed> All settings data.
	 */
	private function get_all_settings() {

		return Settings::get();
	}

	/**
	 * Get global settings with conditional schema exclusion
	 *
	 * @param array<string, mixed> $all_settings All settings.
	 * @return array<string, mixed> Global settings.
	 */
	private function get_global_settings( $all_settings ) {
		// If schema category is not selected, exclude schemas field from global export.
			$global_settings = $all_settings;
			unset( $global_settings['schemas'] );
			return $global_settings;
	}

	/**
	 * Get schema settings
	 *
	 * @param array<string, mixed> $all_settings All settings.
	 * @return array<string, mixed> Schema settings.
	 */
	private function get_schema_settings( $all_settings ) {
		return isset( $all_settings['schemas'] ) ? [ 'schemas' => $all_settings['schemas'] ] : [];
	}

	/**
	 * Get image settings
	 *
	 * @param array<string, mixed> $all_settings All settings.
	 * @return array<string, mixed> Image settings.
	 */
	private function get_image_settings( $all_settings ) {
		$image_keys = Utils::get_image_setting_keys();
		$images     = [];
		foreach ( $image_keys as $key ) {
			if ( isset( $all_settings[ $key ] ) ) {
				$images[ $key ] = $all_settings[ $key ];
			}
		}
		return $images;
	}

}
