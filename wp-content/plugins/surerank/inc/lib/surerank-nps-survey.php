<?php
/**
 * Surerank NPS Survey
 *
 * This file manages all the rewrite rules and query variable handling for NPS Survey functionality in SureRank.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Lib;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Surerank_Nps_Survey' ) ) {

	/**
	 * Admin
	 */
	class Surerank_Nps_Survey {
		use Get_Instance;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			$this->version_check();
			add_action( 'init', [ $this, 'load' ], 999 );
			add_filter( 'nps_survey_post_data', [ $this, 'post_nps_data' ] );
		}

		/**
		 * Post NPS Data
		 * 
		 * @param array $data
		 * @return array
		 * @since 1.0.0
		 */
		public function post_nps_data( $data ) {
			$data['plugin_version'] = SURERANK_VERSION;
			return $data;
		}

		/**
		 * Version Check
		 *
		 * @return void
		 */
		public function version_check() {

			$file = realpath( dirname( __FILE__ ) . '/nps-survey/version.json' );

			// Is file exist?
			if ( is_file( $file ) ) {
				// @codingStandardsIgnoreStart
				$file_data = json_decode( file_get_contents( $file ), true );
				// @codingStandardsIgnoreEnd
				global $nps_survey_version, $nps_survey_init;
				$path    = realpath( dirname( __FILE__ ) . '/nps-survey/nps-survey.php' );
				$version = $file_data['nps-survey'] ?? 0;

				if ( null === $nps_survey_version ) {
					$nps_survey_version = '1.0.0';
				}

				// Compare versions.
				if ( version_compare( $version, $nps_survey_version, '>=' ) ) {
					$nps_survey_version = $version;
					$nps_survey_init    = $path;
				}
			}
		}

		/**
		 * Load latest plugin
		 *
		 * @return void
		 */
		public function load() {

			global $nps_survey_version, $nps_survey_init;
			if ( is_file( realpath( $nps_survey_init ) ) ) {
				include_once realpath( $nps_survey_init );
			}
		}
	}

}
