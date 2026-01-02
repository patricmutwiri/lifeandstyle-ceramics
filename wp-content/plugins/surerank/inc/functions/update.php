<?php
/**
 * Update
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WP_Error;

/**
 * Update
 *
 * @since 1.0.0
 */
class Update {

	/**
	 * Update post meta
	 * This function will update post meta
	 *
	 * @param int                                                $post_id    Post ID.
	 * @param string                                             $meta_key   Meta key.
	 * @param array<string, mixed>|array<int, string>|string|int $meta_value Meta value.
	 *
	 * @since 1.0.0
	 * @return bool|int
	 */
	public static function post_meta( $post_id, $meta_key, $meta_value ) {
		return update_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Update term meta
	 * This function will update post meta
	 *
	 * @param int   $term_id    Post ID.
	 * @param mixed $meta_key   Meta key.
	 * @param mixed $meta_value Meta value.
	 *
	 * @since 1.0.0
	 * @return bool|int|WP_Error
	 */
	public static function term_meta( $term_id, $meta_key, $meta_value ) {
		return update_term_meta( $term_id, $meta_key, $meta_value );
	}

	/**
	 * Update the option.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $option_value Option value.
	 * @param bool   $autoload     Whether to autoload the option.
	 * @since 1.0.0
	 * @return bool
	 */
	public static function option( $option_name, $option_value, $autoload = false ) {
		// Update the option.
		return update_option( $option_name, $option_value, $autoload );
	}

	/**
	 * Update SEO Checks WP Posts.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $seo_checks SEO Checks.
	 * @since 1.0.0
	 * @return bool
	 */
	public static function post_seo_checks( $post_id, $seo_checks ) {
		$existing_seo_checks = get_post_meta( $post_id, SURERANK_SEO_CHECKS, true );
		$existing_seo_checks = is_array( $existing_seo_checks ) ? $existing_seo_checks : [];

		$final_seo_checks = array_filter( array_merge( $existing_seo_checks, $seo_checks ) );

		self::post_meta( $post_id, SURERANK_SEO_CHECKS, $final_seo_checks );
		self::post_meta( $post_id, SURERANK_SEO_CHECKS_LAST_UPDATED, time() );

		return true;
	}

	/**
	 * Update SEO Checks for taxonomies.
	 *
	 * @param int                  $term_id Term ID.
	 * @param array<string, mixed> $seo_checks SEO Checks.
	 * @since 1.0.0
	 * @return bool
	 */
	public static function taxonomy_seo_checks( $term_id, $seo_checks ) {
		$existing_seo_checks = get_term_meta( $term_id, SURERANK_SEO_CHECKS, true );
		$existing_seo_checks = is_array( $existing_seo_checks ) ? $existing_seo_checks : [];

		$final_seo_checks = array_filter( array_merge( $existing_seo_checks, $seo_checks ) );

		self::term_meta( $term_id, SURERANK_SEO_CHECKS, $final_seo_checks );
		self::term_meta( $term_id, SURERANK_SEO_CHECKS_LAST_UPDATED, time() );
		return true;
	}
}
