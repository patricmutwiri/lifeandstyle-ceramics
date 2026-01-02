<?php
/**
 * Import Export Settings API
 *
 * Handles REST API endpoints for importing and exporting SureRank settings.
 *
 * @package SureRank\Inc\API
 * @since 1.2.0
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Import_Export\Settings_Exporter;
use SureRank\Inc\Import_Export\Settings_Importer;
use SureRank\Inc\Import_Export\Utils;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Import_Export_Settings.
 *
 * Handles import/export settings REST API endpoints.
 */
class Import_Export_Settings extends Api_Base {

	use Get_Instance;

	/**
	 * Route for exporting settings.
	 */
	private const EXPORT_SETTINGS = '/export-settings';

	/**
	 * Route for importing settings.
	 */
	private const IMPORT_SETTINGS = '/import-settings';

	/**
	 * Settings Exporter instance.
	 *
	 * @var Settings_Exporter
	 */
	private $exporter;

	/**
	 * Settings Importer instance.
	 *
	 * @var Settings_Importer
	 */
	private $importer;

	/**
	 * Register API routes.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function register_routes() {
		$this->register_export_settings_route();
		$this->register_import_settings_route();
	}

	/**
	 * Export settings based on selected categories.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return void
	 */
	public function export_settings( $request ) {
		try {
			$categories = $request->get_param( 'categories' );

			// Export settings using the exporter class.
			$export_result = $this->get_exporter()->export( $categories );

			// Check if export was successful.
			if ( ! $export_result['success'] ) {
				Send_Json::error(
					[
						'message' => $export_result['message'],
						'status'  => 400,
					]
				);
			}

			Send_Json::success( $export_result['data'] );
		} catch ( \Exception $e ) {
			/* translators: %s: Error message from export operation */
			Send_Json::error(
				[
					/* translators: %s: Error message from export failure */
					'message' => sprintf( __( 'Export failed: %s', 'surerank' ), $e->getMessage() ),
					'status'  => 500,
				]
			);
		}
	}

	/**
	 * Import settings from uploaded data.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return void
	 */
	public function import_settings( $request ) {
		try {
			$settings_data = $request->get_param( 'settings_data' );
			$options       = apply_filters(
				'surerank_import_settings_options',
				[
					'overwrite'     => true,
					'create_backup' => true,
				]
			);

			// Import settings using the importer class.
			$import_result = $this->get_importer()->import( $settings_data, $options );

			if ( ! $import_result['success'] ) {
				Send_Json::error(
					[
						'message' => $import_result['message'],
						'status'  => 400,
						'errors'  => $import_result['errors'] ?? [],
					]
				);
			}

			Send_Json::success( $import_result );
		} catch ( \Exception $e ) {
			Send_Json::error(
				[
					/* translators: %s: Error message from import operation */
					'message' => sprintf( __( 'Import failed: %s', 'surerank' ), $e->getMessage() ),
					'status'  => 500,
				]
			);
		}
	}

	/**
	 * Validate categories parameter.
	 *
	 * @param array<int, string> $categories Categories to validate.
	 * @return bool
	 */
	public function validate_categories( $categories ) {
		if ( ! is_array( $categories ) || empty( $categories ) ) {
			return false;
		}

		$valid_categories = array_keys( $this->get_exporter()->get_categories() );

		foreach ( $categories as $category ) {
			if ( ! is_string( $category ) || ! in_array( $category, $valid_categories, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitize categories parameter.
	 *
	 * @param array<int, string> $categories Categories to sanitize.
	 * @return array<int, string>
	 */
	public function sanitize_categories( $categories ) {
		$valid_categories = array_keys( $this->get_exporter()->get_categories() );
		return Utils::sanitize_categories( $categories, $valid_categories );
	}

	/**
	 * Register the export settings route.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function register_export_settings_route() {
		register_rest_route(
			$this->get_api_namespace(),
			self::EXPORT_SETTINGS,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'export_settings' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'categories' => [
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => [ $this, 'validate_categories' ],
						'sanitize_callback' => [ $this, 'sanitize_categories' ],
					],
				],
			]
		);
	}

	/**
	 * Register the import settings route.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function register_import_settings_route() {
		register_rest_route(
			$this->get_api_namespace(),
			self::IMPORT_SETTINGS,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'import_settings' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'settings_data' => [
						'required'          => true,
						'type'              => 'object',
						'sanitize_callback' => [ $this, 'sanitize_array_data' ],
					],
				],
			]
		);
	}

	/**
	 * Get Settings_Exporter instance.
	 *
	 * @return Settings_Exporter
	 */
	private function get_exporter() {
		if ( null === $this->exporter ) {
			$this->exporter = Settings_Exporter::get_instance();
		}
		return $this->exporter;
	}

	/**
	 * Get Settings_Importer instance.
	 *
	 * @return Settings_Importer
	 */
	private function get_importer() {
		if ( null === $this->importer ) {
			$this->importer = Settings_Importer::get_instance();
		}
		return $this->importer;
	}
}
