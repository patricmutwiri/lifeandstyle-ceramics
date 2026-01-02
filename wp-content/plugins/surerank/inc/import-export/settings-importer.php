<?php
/**
 * Settings Importer class
 *
 * Handles importing SureRank settings from JSON format.
 *
 * @package SureRank\Inc\Import_Export
 * @since 1.2.0
 */

namespace SureRank\Inc\Import_Export;

use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Settings_Importer
 *
 * Handles settings import functionality.
 */
class Settings_Importer {
	use Get_Instance;

	/**
	 * Settings Exporter instance
	 *
	 * @var Settings_Exporter
	 */
	private $exporter;

	/**
	 * Import results
	 *
	 * @var array<string, mixed>
	 */
	private $import_results = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->exporter = Settings_Exporter::get_instance();
	}

	/**
	 * Import settings from data array
	 *
	 * @param array<string, mixed> $settings_data Import data structure.
	 * @param array<string, mixed> $options Import options (overwrite, backup, etc.).
	 * @return array<string, mixed> Import results.
	 */
	public function import( $settings_data, $options = [] ) {
		$this->reset_import_results();

		$options = wp_parse_args(
			$options,
			[
				'overwrite'     => true,
				'create_backup' => true,
			]
		);

		// Validate the import data.
		$validation_result = Utils::validate_import_data( $settings_data );
		if ( ! $validation_result['valid'] ) {
			return Utils::error_response(
				$validation_result['message'],
				$validation_result['errors']
			);
		}

		// Create backup if requested.
		if ( ! empty( $options['create_backup'] ) ) {
			$this->create_backup();
		}

		// Process import.
		$this->process_import( $settings_data, $options );

		return $this->get_import_results();
	}

	/**
	 * Import settings from JSON string.
	 *
	 * @param string               $json_data JSON string containing settings data.
	 * @param array<string, mixed> $options Import options.
	 * @return array<string, mixed> Import results.
	 */
	public function import_from_json( $json_data, $options = [] ) {
		$json_result = Utils::validate_json( $json_data );

		if ( ! $json_result['valid'] ) {
			return Utils::error_response(
				$json_result['message'],
				$json_result['errors']
			);
		}

		return $this->import( $json_result['data'], $options );
	}

	/**
	 * Import settings from uploaded file.
	 *
	 * @param array<string, mixed> $file_data $_FILES array data.
	 * @param array<string, mixed> $options Import options.
	 * @return array<string, mixed> Import results.
	 */
	public function import_from_file( $file_data, $options = [] ) {
		// Validate file.
		$file_validation = Utils::validate_uploaded_file( $file_data );
		if ( ! $file_validation['valid'] ) {
			return Utils::error_response(
				$file_validation['message'],
				$file_validation['errors']
			);
		}

		// Read file content.
		$file_read_result = Utils::read_file_content( $file_data['tmp_name'] );
		if ( ! $file_read_result['success'] ) {
			return $file_read_result;
		}

		return $this->import_from_json( $file_read_result['data'], $options );
	}

	/**
	 * Process the actual import.
	 *
	 * @param array<string, mixed> $settings_data Settings data to import.
	 * @param array<string, mixed> $options Import options.
	 * @return void
	 */
	private function process_import( $settings_data, $options = [] ) {
		$overwrite   = $options['overwrite'] ?? true;
		$settings    = $settings_data['settings'] ?? [];
		$images_data = $settings_data['settings']['images'] ?? [];

		// Get current SureRank settings.
		$current_settings = Settings::get();
		$current_settings = is_array( $current_settings ) ? $current_settings : [];

		// Collect all settings from all categories into one flat array.
		$all_new_settings = [];

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$this->add_import_error( __( 'No settings found to import.', 'surerank' ) );
			return;
		}

		foreach ( $settings as $category => $category_options ) {
			if ( ! $this->exporter->is_valid_category( $category ) ) {
				$this->add_import_error(
					sprintf(
						// translators: %s: Category name.
						__( 'Skipped invalid category: %s', 'surerank' ),
						$category
					)
				);
				continue;
			}

			if ( $category === 'images' && ! empty( $images_data ) ) {
				$processed_options = Utils::process_image_settings_import( $category_options );
				$all_new_settings  = array_merge( $all_new_settings, $processed_options );
				continue;
			}

			$processed_options = $category_options;

			// Merge all settings.
			$all_new_settings = array_merge( $all_new_settings, $processed_options );
		}

		if ( empty( $all_new_settings ) || ! is_array( $all_new_settings ) ) {
			$this->add_import_error( __( 'No settings found to import.', 'surerank' ) );
			return;
		}

		// Merge with existing settings.
		$final_settings = $overwrite
			? array_merge( $current_settings, $all_new_settings )
			: array_merge( $all_new_settings, $current_settings );
		if ( Update::option( SURERANK_SETTINGS, $final_settings ) ) {
			foreach ( array_keys( $all_new_settings ) as $key ) {
				$this->add_import_success( $key );
			}
		} else {
			$this->add_import_error( __( 'Failed to save settings to database.', 'surerank' ) );
		}
	}

	/**
	 * Create backup of current settings
	 *
	 * @return void
	 */
	private function create_backup() {
		// Get all categories current snapshot.
		// This ensures we backup all settings, not just the ones being imported.
		$backup_data = Get::option( SURERANK_SETTINGS, [], 'array' );
		$backup_key  = Utils::generate_backup_key();

		Update::option( $backup_key, $backup_data );
		$this->import_results['backup_key'] = $backup_key;
	}

	/**
	 * Reset import results
	 *
	 * @return void
	 */
	private function reset_import_results() {
		$this->import_results = Utils::init_import_results();
	}

	/**
	 * Add import success
	 *
	 * @param string $option_key Successfully imported option key.
	 * @return void
	 */
	private function add_import_success( $option_key ) {
		$this->import_results['imported_count']++;
		$this->import_results['success_items'][] = $option_key;
	}

	/**
	 * Add import error
	 *
	 * @param string $error Error message.
	 * @return void
	 */
	private function add_import_error( $error ) {
		$this->import_results['errors'][] = $error;
	}
	/**
	 * Get final import results
	 *
	 * @return array<string, mixed> Import results.
	 */
	private function get_import_results() {
		$this->import_results['success'] = $this->import_results['imported_count'] > 0;

		$this->import_results['message'] = $this->import_results['success']
			? sprintf(
				/* translators: %d: Number of settings that were successfully imported */
				__( 'Successfully imported %d settings.', 'surerank' ),
				$this->import_results['imported_count']
			)
			: __( 'No settings were imported.', 'surerank' );

		return $this->import_results;
	}
}
