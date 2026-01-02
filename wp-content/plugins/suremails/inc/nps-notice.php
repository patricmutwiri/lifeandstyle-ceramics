<?php
/**
 * SureMails NPS Notice
 *
 * This file manages all the rewrite rules and query variable handling for NPS Notice functionality in SureMails.
 *
 * @package suremails
 */

namespace SureMails\Inc;

use Nps_Survey;
use SureMails\Inc\DB\EmailLog;
use SureMails\Inc\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Nps_Notice' ) ) {

	/**
	 * Nps_Notice
	 */
	class Nps_Notice {
		use Instance;

		/**
		 * Array of allowed screens where the NPS survey should be displayed.
		 * This ensures that the NPS survey is only displayed on SureMails pages.
		 *
		 * @var array<string>
		 * @since 1.0.0
		 * @since 1.9.0
		 */
		private static $allowed_screens = [
			'toplevel_page_' . SUREMAILS,
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

			// check if entries in wp_suremails_email_log is more than 5.
			$show = $this->check_email_log_entries();
			if ( ! $show ) {
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

			// Display the NPS survey.
			Nps_Survey::show_nps_notice(
				'nps-survey-suremails',
				[
					'show_if'          => true,
					'dismiss_timespan' => 2 * WEEK_IN_SECONDS,
					'display_after'    => 0,
					'plugin_slug'      => 'suremails',
					'show_on_screens'  => self::$allowed_screens,
					'message'          => [
						'logo'                        => 'data:image/svg+xml;base64,PHN2ZwoJCXdpZHRoPSIyNCIKCQloZWlnaHQ9IjI0IgoJCXZpZXdCb3g9IjAgMCAyNCAyNCIKCQlmaWxsPSJub25lIgoJCXhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKCT4KCQk8cGF0aAoJCQlkPSJNMjMuMjUgMEgwLjc1QzAuMzM1Nzg3IDAgMCAwLjMzNTc4NyAwIDAuNzVWMjMuMjVDMCAyMy42NjQyIDAuMzM1Nzg3IDI0IDAuNzUgMjRIMjMuMjVDMjMuNjY0MiAyNCAyNCAyMy42NjQyIDI0IDIzLjI1VjAuNzVDMjQgMC4zMzU3ODcgMjMuNjY0MiAwIDIzLjI1IDBaIgoJCQlmaWxsPSIjMEQ3RUU4IgoJCS8+CgkJPHBhdGgKCQkJZD0iTTYuNDAyOTIgMTEuNjYzNUM2LjYxMTY1IDExLjgxNDUgNi45MDgwMSAxMS43NjQ5IDcuMDQ0NTUgMTEuNTYxOEM3LjE5NTUyIDExLjM1MzEgNy4xNDU5MSAxMS4wNTY3IDYuOTQyODUgMTAuOTIwMkw0LjkzMjgxIDkuNDgwMTZDNC44NjEzNyA5LjQyNTA3IDQuODY3NiA5LjM1NjExIDQuODcwNzEgOS4zMjE2NEM0Ljg3Mzg2IDkuMjg3MTggNC45MDAxNSA5LjIyNzAzIDQuOTg2NjIgOS4xOTMxN0wxOC4zNjA5IDUuNzgzMzNDMTguNDM4NiA1Ljc2OTUzIDE4LjQ4NDQgNS44MDE0OCAxOC41MTU3IDUuODM5MDZDMTguNTQ3IDUuODc2NjcgMTguNTc4NCA1LjkxNDI0IDE4LjU0MzMgNS45OTQ0NkwxMy40NzYgMTguODE2MkMxMy40NDA5IDE4Ljg5NjQgMTMuMzc3NiAxOC45MDQ1IDEzLjM0ODggMTguOTE1OEMxMy4zMTQzIDE4LjkxMjcgMTMuMjQ1NCAxOC45MDY1IDEzLjIwMjggMTguODQwMUwxMS42NzExIDE2LjAzMjZDMTEuNjIyOCAxNS45NTE4IDExLjU4MzMgMTUuODUwOSAxMS41MzUgMTUuNzcwMUMxMC45MzU1IDE0LjQwOTMgMTAuNzg0MiAxMy40MDUxIDExLjkwOTMgMTIuNDE2TDE0LjgwNiA5LjczNTMxQzE1LjAwMjcgOS41NTg1MyAxNS4wMjIxIDkuMjY4MzkgMTQuODUwOSA5LjA4NjExQzE0LjY3NDIgOC44ODk0MiAxNC4zODQgOC44NzAwNyAxNC4yMDE3IDkuMDQxMThMMTEuMTU5MyAxMS42NjM1QzkuNjI2MzUgMTMuMDExOSA5Ljg4MTYxIDE0LjY4OTEgMTAuODUxNiAxNi40ODY1TDEyLjM4MzMgMTkuMjk0QzEyLjU3ODkgMTkuNjY2MSAxMi45NzY5IDE5Ljg3NTkgMTMuNDAyMyAxOS44NTg5QzEzLjUxNDUgMTkuODQ4MiAxMy42MzU0IDE5LjgxNzUgMTMuNzM2MyAxOS43NzhDMTQuMDEwMSAxOS42NzA3IDE0LjIyNDQgMTkuNDUzOCAxNC4zNDcxIDE5LjE3MzFMMTkuNDE0NCA2LjM1MTM4QzE5LjU2NjYgNS45NzU5NyAxOS40OTUgNS41Mzg2MSAxOS4yMjQyIDUuMjI5MTZDMTguOTUzNCA0LjkxOTY3IDE4LjU0MDUgNC43OTg4NCAxOC4xNDMyIDQuODg3OTRMNC43NTQ1IDguMzAzNDFDNC4zNDg0OSA4LjQxMjU3IDQuMDQxNTEgOC43MzIyNiAzLjk1NTE5IDkuMTQ4MzZDMy44Njg4MiA5LjU2NDQ2IDQuMDQ2OTEgOS45NzY3MyA0LjM5Mjg5IDEwLjIyMzVMNi40MDI5MiAxMS42NjM1WiIKCQkJZmlsbD0id2hpdGUiCgkJLz4KCQk8cGF0aAoJCQlkPSJNNS43Njk3MyAxNS41MjU2QzUuODA3MzggMTUuNTEwOCA1Ljg1MjY0IDE1LjQ3ODYgNS44ODUzOCAxNS40NTEzTDcuOTA5ODEgMTMuNjc0NUM4LjA4MTA3IDEzLjUyMDYgOC4wOTc5MSAxMy4yNjggNy45NDg5MiAxMy4xMDkzQzcuNzk1MDIgMTIuOTM4MSA3LjU0MjQ2IDEyLjkyMTIgNy4zODM3NiAxMy4wNzAyTDUuMzU5MjkgMTQuODQ3QzUuMTg4MDYgMTUuMDAwOSA1LjE3MTIzIDE1LjI1MzUgNS4zMjAxOCAxNS40MTIyQzUuNDM0MjUgMTUuNTU1NyA1LjYwNjY0IDE1LjU4OTQgNS43Njk3MyAxNS41MjU2WiIKCQkJZmlsbD0id2hpdGUiCgkJLz4KCQk8cGF0aAoJCQlkPSJNNS41MTA4OCAxOC4zNjc2QzUuNTQ4NTcgMTguMzUzIDUuNTkzOTQgMTguMzIxIDUuNjI2NzIgMTguMjkzOEw5LjA2NzMgMTUuMjgwN0M5LjIzODk4IDE1LjEyNzMgOS4yNTY1MyAxNC44NzQ4IDkuMTA3OTkgMTQuNzE1NkM4Ljk1NDU4IDE0LjU0NCA4LjcwMjA1IDE0LjUyNjQgOC41NDI5NCAxNC42NzQ5TDUuMTAyMzUgMTcuNjg4QzQuOTMwNjggMTcuODQxNCA0LjkxMzEzIDE4LjA5MzkgNS4wNjE2NyAxOC4yNTNDNS4xNzA0NSAxOC4zODQzIDUuMzYwMTcgMTguNDI2MiA1LjUxMDg4IDE4LjM2NzZaIgoJCQlmaWxsPSJ3aGl0ZSIKCQkvPgoJPC9zdmc+',
						'plugin_name'                 => __( 'SureMail', 'suremails' ),
						'nps_rating_message'          => __( 'How likely are you to recommend SureMail to your friends or colleagues?', 'suremails' ),
						'feedback_title'              => __( 'Thanks a lot for your feedback! 😍', 'suremails' ),
						'plugin_rating_link'          => esc_url( 'https://wordpress.org/support/plugin/suremails/reviews/#new-post' ),
						'plugin_rating_title'         => __( 'Thank you for your feedback', 'suremails' ),
						'plugin_rating_content'       => __( 'We value your input. How can we improve your experience?', 'suremails' ),
						'plugin_rating_button_string' => __( 'Rate SureMail', 'suremails' ),

					],

				]
			);
		}

		/**
		 * Check if there are more than 5 entries in the email log table.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public function check_email_log_entries() {

			$log_count = EmailLog::instance()->get(
				[
					'select' => 'COUNT(*) as total_count',
					'where'  => [ 'status' => 'sent' ],
				]
			);

			if ( ! empty( $log_count ) && isset( $log_count[0]['total_count'] ) && intval( $log_count[0]['total_count'] ) >= 5 ) {
				return true;
			}

			return false;
		}

	}

}
