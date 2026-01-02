<?php
/**
 * Get
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get
 * This class will handle all functions to get data from database.
 *
 * @since 1.0.0
 */
class Get {

	/**
	 * Default title length
	 *
	 * @since 1.0.0
	 */
	public const TITLE_LENGTH = 60;

	/**
	 * Default title min length
	 *
	 * @since 1.0.0
	 */
	public const TITLE_MIN_LENGTH = 50;

	/**
	 * Default description length
	 *
	 * @since 1.0.0
	 */
	public const DESCRIPTION_LENGTH = 160;

	/**
	 * Default description min length
	 *
	 * @since 1.0.0
	 */
	public const DESCRIPTION_MIN_LENGTH = 150;

	/**
	 * Default URL length
	 *
	 * @since 1.0.0
	 */
	public const URL_LENGTH = 90;

	/**
	 * Get post meta
	 * This function will get post meta
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single  Single or not.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function post_meta( $post_id, $meta_key, $single = true ) {
		return get_post_meta( $post_id, $meta_key, $single );
	}

	/**
	 * Get term meta
	 * This function will get term meta
	 *
	 * @param int    $term_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single  Single or not.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function term_meta( $term_id, $meta_key, $single = true ) {
		return get_term_meta( $term_id, $meta_key, $single );
	}

	/**
	 * Get user meta
	 * This function will get user meta
	 *
	 * @param int    $user_id User ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single  Single or not.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function user_meta( $user_id, $meta_key, $single = true ) {
		return get_user_meta( $user_id, $meta_key, $single );
	}

	/**
	 * Get all post meta
	 * This function will get all post meta
	 *
	 * @param int $post_id Post ID.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function all_post_meta( $post_id ) {
		$keys = Defaults::get_instance()->get_post_meta_keys();
		$meta = [];
		foreach ( $keys as $key ) {
			$option = self::post_meta( $post_id, 'surerank_settings_' . $key, true );
			if ( ! empty( $option ) ) {
				$meta[ $key ] = $option;
			}
		}

		return Settings::format_array( $meta );
	}

	/**
	 * Get all term meta
	 * This function will get all term meta
	 *
	 * @param int $term_id Term ID.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function all_term_meta( $term_id ) {
		$keys = Defaults::get_instance()->get_post_meta_keys();
		$meta = [];
		foreach ( $keys as $key ) {
			$option = self::term_meta( $term_id, 'surerank_settings_' . $key, true );
			if ( ! empty( $option ) ) {
				$meta[ $key ] = $option;
			}
		}

		return Settings::format_array( $meta );
	}

	/**
	 * Get option
	 * This function will get option
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $default     Default value.
	 * @param string $format      Format.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function option( $option_name, $default = false, $format = 'string' ) {
		$get = get_option( $option_name, $default );

		if ( 'array' === $format ) {
			$validate = Validate::array( $get );
			return empty( $validate ) || ! is_array( $validate ) ? $default : $validate;
		}

		return $get;
	}

	/**
	 * Image dimensions.
	 *
	 * @param array<string, mixed> $meta Meta data.
	 * @since 0.0.1
	 * @return array<string, mixed>
	 */
	public static function fb_image_size( &$meta ) {
		// GET facebook_image_url and height and width.
		$facebook_image_url = $meta['facebook_image_url'] ?? '';
		// get height and width of facebook image.
		$facebook_image_details = self::get_image_dimensions( $facebook_image_url );

		// update facebook image height and width.
		$meta['facebook_image_height'] = $facebook_image_details['height'];
		$meta['facebook_image_width']  = $facebook_image_details['width'];

		return $meta;
	}

	/**
	 * Get image dimensions.
	 *
	 * @param string $image_url Image URL.
	 * @since 0.0.1
	 * @return array<string, mixed>
	 */
	public static function get_image_dimensions( $image_url ) {
		$dimensions = [
			'height' => 0,
			'width'  => 0,
		];

		// If image url is empty then return.
		if ( empty( $image_url ) ) {
			return $dimensions;
		}

		/**
		 * Image details type.
		 *
		 * @var array{
		 *   height?: int,
		 *   width?: int,
		 *   filesize: int,
		 *   file?: string,
		 *   sizes?: array<string, mixed>,
		 *   image_meta?: array<string, mixed>
		 * }|false $image_details
		 */
		$image_details = wp_get_attachment_metadata( attachment_url_to_postid( $image_url ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid

		// If image details are empty then return.
		if ( empty( $image_details ) ) {
			return $dimensions;
		}

		return [
			'height' => $image_details['height'] ?? 0,
			'width'  => $image_details['width'] ?? 0,
		];
	}

	/**
	 * Get description length
	 *
	 * @since 0.0.1
	 * @return int
	 */
	public static function description_length() {
		return (int) apply_filters( 'surerank_description_length', self::DESCRIPTION_LENGTH );
	}

	/**
	 * Get title length
	 *
	 * @since 0.0.1
	 * @return int
	 */
	public static function title_length() {
		return (int) apply_filters( 'surerank_title_length', self::TITLE_LENGTH );
	}

	/**
	 * Get formatted description
	 *
	 * @param string $description Description.
	 * @since 0.0.1
	 * @return string
	 */
	public static function formatted_description( $description ) {
		$limit   = self::description_length();
		$trimmed = mb_substr( trim( $description ), 0, $limit );

		if ( mb_strlen( $description ) > $limit ) {
			$last_space = mb_strrpos( $trimmed, ' ' );
			if ( $last_space !== false ) {
				$trimmed = mb_substr( $trimmed, 0, $last_space );
			}
		}

		return $trimmed;
	}

	/**
	 * Get URL length
	 *
	 * @since 0.0.1
	 * @return int
	 */
	public static function url_length() {
		return (int) apply_filters( 'surerank_url_length', self::URL_LENGTH );
	}
}
