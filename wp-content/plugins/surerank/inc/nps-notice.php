<?php
/**
 * SureRank NPS Notice
 *
 * This file manages all the rewrite rules and query variable handling for NPS Notice functionality in SureRank.
 *
 * @package surerank
 */

namespace SureRank\Inc;

use Nps_Survey;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Nps_Notice' ) ) {

	/**
	 * Nps_Notice
	 */
	class Nps_Notice {

		use Get_Instance;

		/**
		 * Array of allowed screens where the NPS survey should be displayed.
		 * This ensures that the NPS survey is only displayed on SureForms pages.
		 *
		 * @var array<string>
		 * @since 1.0.0
		 */
		private static $allowed_screens = [
			'toplevel_page_surerank',
		];

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			add_action( 'admin_footer', [ $this, 'show_nps_notice' ], 999 );
		}

		/**
		 * Render NPS Survey
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function show_nps_notice() {
			// Ensure the Nps_Survey class exists before proceeding.
			if ( ! class_exists( 'Nps_Survey' ) ) {
				return;
			}

			/**
			 * Check if the constant WEEK_IN_SECONDS is already defined.
			 * This ensures that the constant is not redefined if it's already set by WordPress or other parts of the code.
			 */
			if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
				// Define the WEEK_IN_SECONDS constant with the value of 604800 seconds (equivalent to 7 days).
				define( 'WEEK_IN_SECONDS', 604800 );
			}

			/**
			 * Check if the constant DAYS_IN_SECONDS is already defined.
			 * This ensures that the constant is not redefined if it's already set by WordPress or other parts of the code.
			 */
			if ( ! defined( 'DAYS_IN_SECONDS' ) ) {
				// Define the DAYS_IN_SECONDS constant with the value of 86400 seconds (equivalent to 1 day).
				define( 'DAYS_IN_SECONDS', 86400 );
			}

			// Display the NPS survey.
			Nps_Survey::show_nps_notice(
				'nps-survey-surerank',
				[
					'show_if'          => true,
					'dismiss_timespan' => 2 * WEEK_IN_SECONDS,
					'display_after'    => 5 * DAYS_IN_SECONDS, // Display the NPS survey after 5 days.
					'plugin_slug'      => 'surerank',
					'show_on_screens'  => self::$allowed_screens,
					'message'          => [
						'logo'                        => Helper::logo_uri(),
						'plugin_name'                 => __( 'SureRank', 'surerank' ),
						'nps_rating_message'          => __( 'How likely are you to recommend SureRank to your friends or colleagues?', 'surerank' ),
						'feedback_title'              => __( 'Thanks a lot for your feedback! 😍', 'surerank' ),
						'feedback_content'            => __( 'Could you please do us a favor and give us a 5-star rating on WordPress? It would help others choose SureRank with confidence. Thank you!', 'surerank' ),
						'plugin_rating_link'          => esc_url( 'https://wordpress.org/support/plugin/surerank/reviews/#new-post' ),
						'plugin_rating_title'         => __( 'Thank you for your feedback', 'surerank' ),
						'plugin_rating_content'       => __( 'We value your input. How can we improve your experience?', 'surerank' ),
						'plugin_rating_button_string' => __( 'Rate SureRank', 'surerank' ),

					],

				]
			);
		}

	}

}
