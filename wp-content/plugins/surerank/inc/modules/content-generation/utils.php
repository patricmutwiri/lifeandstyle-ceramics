<?php
/**
 * Content Generation Utils
 *
 * Utils module class for handling content generation functionality.
 *
 * @package SureRank\Inc\Modules\Content_Generation
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Content_Generation;

use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Functions\Requests;
use SureRank\Inc\Modules\Ai_Auth\Controller as Ai_Auth_Controller;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Utils class
 *
 * Main module class for content generation functionality.
 */
class Utils {

	use Get_Instance;

	/**
	 * Get API types.
	 * 
	 * @return array<int,string> Array of API types.
	 * @since 1.4.2
	 */
	public function get_api_types() {
		return apply_filters(
			'surerank_content_generation_types',
			[
				'page_title',
				'home_page_title',
				'page_description',
				'home_page_description',
				'social_title',
				'social_description',
				'site_tag_line',
				'page_url_slug',
			]
		);
	}

	/**
	 * Prepare inputs for content generation.
	 *
	 * @param int|null $id Post or term ID (optional).
	 * @param bool     $is_taxonomy Whether the ID is for a taxonomy term.
	 * @return array<string,string> Array of inputs for content generation.
	 * @since 1.4.2
	 */
	public function prepare_content_inputs( $id = null, $is_taxonomy = false ) {
		$title   = '';
		$content = '';

		if ( ! empty( $id ) ) {
			if ( $is_taxonomy ) {
				$term = get_term( $id );
				if ( $term && ! is_wp_error( $term ) ) {
					$title   = $term->name;
					$content = $term->description;
				}
			} else {
				$post = get_post( $id );
				if ( $post ) {
					$title   = get_the_title( $id );
					$content = $post->post_content;
				}
			}
		}
		// Limit to 500 words (wp_trim_words handles tag stripping and whitespace).
		if ( ! empty( $content ) ) {
			$content = wp_trim_words( $content, 500, '' );
		}

		return apply_filters(
			'surerank_content_generation_inputs',
			[
				'site_name'     => get_bloginfo( 'name' ),
				'site_tagline'  => get_bloginfo( 'description' ),
				'page_title'    => $title,
				'page_content'  => $content,
				'focus_keyword' => $this->get_focus_keyword( $id, $is_taxonomy ),
			]
		);
	}

	/**
	 * Get focus keyword for the post or term.
	 *
	 * @param int|null $post_id Post ID or Term ID.
	 * @param bool     $is_taxonomy Whether it's a taxonomy term.
	 * @return string Focus keyword.
	 * @since 1.4.2
	 */
	private function get_focus_keyword( $post_id = null, $is_taxonomy = false ) {
		if ( empty( $post_id ) ) {
			return '';
		}

		if ( $is_taxonomy ) {
			$term_meta = get_term_meta( $post_id, 'surerank_settings_general', true );
			return $term_meta['focus_keyword'] ?? '';
		}

		$post_meta = get_post_meta( $post_id, 'surerank_settings_general', true );
		return $post_meta['focus_keyword'] ?? '';
	}

	/**
	 * Get Credit System API URL.
	 *
	 * @return string API URL.
	 * @since 1.5.0
	 */
	public static function get_credit_system_api_url() {
		if ( ! defined( 'SURERANK_CREDIT_SERVER_API' ) ) {
			define( 'SURERANK_CREDIT_SERVER_API', 'https://credits.startertemplates.com/' );
		}
		return SURERANK_CREDIT_SERVER_API;
	}

		/**
		 * Get Auth Token.
		 *
		 * @since 1.5.0
		 * @return string|WP_Error
		 */
	public function get_auth_token() {
		$token = apply_filters( 'surerank_content_generation_auth_token', $this->get_auth_data( 'user_email' ) );

		if ( empty( $token ) ) {
			$token = $this->get_auth_data( 'user_email' );
		}

		return $token;
	}

	/**
	 * Get Auth Data.
	 * 
	 * @since 1.4.2
	 * @param string $key Optional. Key to retrieve specific data. Default is empty which returns all data.
	 * @return array<string, mixed>|WP_Error
	 */
	private function get_auth_data( $key = '' ) {
		$auth_data = get_option( Ai_Auth_Controller::SETTINGS_KEY, false );

		if ( empty( $auth_data ) ) {
			return new WP_Error( 'no_auth_data', __( 'No authentication data found.', 'surerank' ) );
		}

		if ( ! empty( $key ) && is_string( $key ) ) {
			return $auth_data[ $key ] ?? new WP_Error( 'no_key_found', __( 'No data found for the provided key.', 'surerank' ) );
		}

		return $auth_data;
	}

	/**
	 * Get custom error messages for API responses.
	 *
	 * @since 1.5.0
	 * @return array<string, string> Array of error codes and their custom messages.
	 */
	public static function get_custom_error_messages() {
		return [
			'internal_server_error' => __( 'Something went wrong on our end. Please try again in a moment, or contact support if you need help.', 'surerank' ),
			'require_pro'           => __( 'You\'ve reached your free usage limit. Upgrade to Pro for additional credits to continue generating content. require_pro', 'surerank' ),
			'limit_exceeded'        => __( 'You\'ve used all your AI credits for today. Your credits will refresh automatically tomorrow, so you can continue creating content.', 'surerank' ),
		];
	}

	/**
	 * Send API request to service.
	 *
	 * @since 1.5.0
	 * @param array<string, mixed> $request_data Request data to send.
	 * @param string               $route        API route (e.g., 'surerank/generate/content').
	 * @param int                  $timeout      Request timeout in seconds (default: 30).
	 * @return array<string, mixed>|\WP_Error API response or WP_Error.
	 */
	public function send_api_request( $request_data, $route = 'surerank/generate/content', $timeout = 30 ) {
		$auth_token = self::get_auth_token();

		if ( empty( $auth_token ) || is_wp_error( $auth_token ) ) {
			return new WP_Error( 'no_auth_token', __( 'No authentication token found. Please connect your account.', 'surerank' ) );
		}

		$url = $this->get_credit_system_api_url() . $route;

		$body = wp_json_encode( $request_data );

		if ( false === $body ) {
			return new WP_Error( 'json_encode_error', __( 'Failed to encode request data to JSON.', 'surerank' ) );
		}

		/**
		* The API request response.
		*
		* @var array<string, mixed>|\WP_Error $response
		*/
		$response = Requests::post(
			$url,
			[
				'headers' => array(
					'X-Token'      => base64_encode( $auth_token ),
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => $body,
				'timeout' => $timeout, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);

		return $response;
	}

}
