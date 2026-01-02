<?php
/**
 * Meta Tag Injection Class
 *
 * Responsible for injecting Google Search Console verification meta tags.
 *
 * @since 1.4.0
 * @package SureRank
 */

namespace SureRank\Inc\Frontend;

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Meta Tag Injection Class
 *
 * Handles the injection of Google verification meta tags into the site head.
 *
 * @since 1.4.0
 */
class Meta_Tag_Injection {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function __construct() {

		$enabled = Settings::get( 'enable_google_console' ) ?? false;
		if ( $enabled ) {
			add_action( 'surerank_print_meta', [ $this, 'output_verification_meta_tag' ] );
			return;
		}
	}

	/**
	 * Output Verification Meta Tag
	 *
	 * Outputs the Google Search Console verification meta tag if one exists
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function output_verification_meta_tag() {
		$verification_token = get_option( 'surerank_gsc_verification_token', false );

		if ( ! empty( $verification_token ) ) {
			echo "\n" . '<meta name="google-site-verification" content="' . esc_attr( $verification_token ) . '" />' . "\n";
		}
	}
}
