<?php
/**
 * Page SEO Checks class
 *
 * Module class for fixing Page SEO Checks functionality.
 *
 * @package SureRank\Inc\Modules\Fix_Seo_Checks
 * @since 1.4.2
 */

namespace SureRank\Inc\Modules\Fix_Seo_Checks;

use SureRank\Inc\API\Post;
use SureRank\Inc\API\Term;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Page SEO Checks class
 *
 * Main module class for fixing Page SEO Checks functionality.
 */
class Page {

	use Get_Instance;

	/**
	 * Get API types.
	 * 
	 * @return array<int,string> Array of API types.
	 * @since 1.4.2
	 */
	public function get_fix_it_types() {
		return apply_filters(
			'surerank_content_generation_page_types',
			[
				'content-generation',
			]
		);
	}

	/**
	 * Implementation for content generation fix.
	 *
	 * @param string $input_key   Input key.
	 * @param string $input_value Input value.
	 * @param int    $id          Post ID.
	 * @param bool   $is_taxonomy Whether the input is a taxonomy or not.
	 * 
	 * @return array{status: bool, message: string, type: string}|\WP_Error Response array with status, message, and type or WP_Error on failure.
	 * @since 1.4.2
	 */
	public function use_me( $input_key, $input_value, $id, $is_taxonomy = false ) {
		$method = Utils::create_use_me_method( $input_key );

		$classes = Utils::get_use_classes();

		if ( empty( $classes ) || ! is_array( $classes ) ) {
			return Utils::get_instance()->send_response( false, __( 'An error occurred while applying the content generation fix.', 'surerank' ), 'content_generation' );
		}
		
		foreach ( $classes as $class ) {
			if ( is_callable( [ $class::get_instance(), $method ] ) ) {
				return $class::get_instance()->{$method}( $input_value, $id, $is_taxonomy );
			}
		}

		return Utils::get_instance()->send_response( false, __( 'An error occurred while applying the content generation fix.', 'surerank' ), 'content_generation' );
	}

	/**
	 * Common function to update meta data for post or term.
	 *
	 * @param string $input_value    Input value.
	 * @param int    $id             Post or Term ID.
	 * @param bool   $is_taxonomy    Whether the input is a taxonomy or not.
	 * @param string $meta_key       Meta key to update.
	 * @param string $error_message  Error message for validation failure.
	 * @param string $success_message Success message.
	 * @param string $response_type  Response type for the fix.
	 * 
	 * @return array{status: bool, message: string, type: string}|\WP_Error Response array with status, message, and type or WP_Error on failure.
	 * @since 1.4.2
	 */
	private function update_meta_common( $input_value, $id, $is_taxonomy, $meta_key, $error_message, $success_message, $response_type ) {
		if ( empty( $id ) || empty( $input_value ) ) {
			return Utils::get_instance()->send_response( false, $error_message, $response_type );
		}

		$data = [
			$meta_key => $input_value,
		];

		if ( $is_taxonomy ) {
			$term = get_term( $id );
			if ( is_wp_error( $term ) || empty( $term ) ) {
				return Utils::get_instance()->send_response( false, __( 'Term not found.', 'surerank' ), $response_type );
			}
			Term::update_term_meta_common( $id, $data );
		} else {
			$post = get_post( $id );
			if ( empty( $post ) ) {
				return Utils::get_instance()->send_response( false, __( 'Post not found or not published.', 'surerank' ), $response_type );
			}
			Post::update_post_meta_common( $id, $data );
		}
		
		Utils::get_instance()->clear_cache( $id, $is_taxonomy );
		
		return Utils::get_instance()->send_response( true, $success_message, $response_type );
	}

	/**
	 * Fix the search engine title.
	 *
	 * @param string $input_value Input value.
	 * @param int    $id          Post ID.
	 * @param bool   $is_taxonomy Whether the input is a taxonomy or not.
	 * 
	 * @return array{status: bool, message: string, type: string}|\WP_Error Response array with status, message, and type or WP_Error on failure.
	 * @since 1.4.2
	 */
	private function use_search_engine_title( $input_value = '', $id = 0, $is_taxonomy = false ) {
		return $this->update_meta_common(
			$input_value,
			$id,
			$is_taxonomy,
			'page_title',
			__( 'Invalid input for page title fix.', 'surerank' ),
			__( 'Page title fix applied successfully.', 'surerank' ),
			'search_engine_title'
		);
	}

	/**
	 * Fix the search engine description.
	 *
	 * @param string $input_value Input value.
	 * @param int    $id          Post ID.
	 * @param bool   $is_taxonomy Whether the input is a taxonomy or not.
	 * 
	 * @return array{status: bool, message: string, type: string}|\WP_Error Response array with status, message, and type or WP_Error on failure.
	 * @since 1.4.2
	 */
	private function use_search_engine_description( $input_value = '', $id = 0, $is_taxonomy = false ) {
		return $this->update_meta_common(
			$input_value,
			$id,
			$is_taxonomy,
			'page_description',
			__( 'Invalid input for meta description fix.', 'surerank' ),
			__( 'Meta description fix applied successfully.', 'surerank' ),
			'search_engine_description'
		);
	}
}
