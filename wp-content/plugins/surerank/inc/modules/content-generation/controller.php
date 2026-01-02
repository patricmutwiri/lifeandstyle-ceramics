<?php
/**
 * Content Generation Controller
 *
 * Main module controller for handling content generation functionality.
 *
 * @package SureRank\Inc\Modules\Content_Generation
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Content_Generation;

use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Controller class
 *
 * Main module class for content generation functionality.
 */
class Controller {

	use Get_Instance;

	/**
	 * Generate Content for a given post.
	 * 
	 * @param array<string,string> $inputs Inputs for content generation.
	 * @param string               $type Type of content to generate (e.g., 'page_title').
	 * 
	 * @return string|WP_Error Generated content string or error object.
	 * @since 1.4.2
	 */
	public function generate_content( $inputs, $type = 'page_title' ) {
		$inputs = wp_parse_args(
			$inputs,
			[
				'page_title'    => '',
				'site_tagline'  => '',
				'site_name'     => '',
				'page_content'  => '',
				'focus_keyword' => '',
			]
		);

		$args = [
			'type'   => $type,
			'inputs' => $inputs,
			'source' => 'openai',
		];

		$response = Utils::get_instance()->send_api_request( $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		$decoded_response = json_decode( $response_body, true );


		if ( ! is_array( $decoded_response ) ) {
			return new WP_Error( 'content_generation_error', __( 'Unable to generate content at this time. Please check your input and try again, or contact support if you need help.', 'surerank' ) );
		}

		if ( isset( $decoded_response['code'] ) ) {
			$code = $decoded_response['code'];
			/* translators: %s is response code */
			$message = isset( $decoded_response['message'] ) ? $decoded_response['message'] : sprintf( __( 'Failed to generate content with error code %s.', 'surerank' ), $code );

			$custom_error_messages = Utils::get_custom_error_messages();

			if ( isset( $custom_error_messages[ $code ] ) ) {
				$message = $custom_error_messages[ $code ];
			}

			return new WP_Error( $code, $message );
		}

		if ( ! isset( $decoded_response['content'] ) ) {
			return new WP_Error( 'content_generation_error', __( 'Unable to generate content at this time. Please try again, or contact support if you need help.', 'surerank' ) );
		}

		return $decoded_response['content'];
	}
}
