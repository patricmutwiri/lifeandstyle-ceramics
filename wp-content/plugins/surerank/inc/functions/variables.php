<?php
/**
 * Variables
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Meta_Variables\Site;
use SureRank\Inc\Meta_Variables\Term;

/**
 * Variables
 *
 * @package surerank
 * @since 0.0.1
 */
class Variables {

	/**
	 * We will replace these keys with the actual values.
	 *
	 * @var array<string, mixed>|array<int, array<string, mixed>> $replacement_fields This array contains the smart tags like %title%, %description% etc.
	 * Further we need to add all meta fields name wherever we want to replace the smart tags.
	 *
	 * @since 1.0.0
	 */
	public static $replacement_fields = [
		// Seo meta popup fields.
		'page_title',
		'page_description',
		// Seo meta popup field. Social meta fields.
		'facebook_title',
		'facebook_description',
		'facebook_image_url',
		'twitter_title',
		'twitter_description',
		'twitter_image_url',
	];

	/**
	 * This function will replace the variables in the string with the actual values.
	 *
	 * @param array<int|string, mixed> $data    array of strings which needs replacement.
	 * @param int                      $post_id Post ID.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function replace( $data, $post_id ) {

		if ( empty( Validate::array( $data ) || ! $post_id ) ) {
			return [];
		}

		$chunks              = [];
		$replacement_strings = [];
		$output              = [];

		foreach ( self::$replacement_fields as $key ) {
			if ( ! empty( $data[ $key ] ) ) {
				$string = $data[ $key ];
				$chunks = array_merge( $chunks, self::get_chunks( $string ) );

				foreach ( $chunks as $chunk ) {
					$replacement_strings[ '%' . $chunk . '%' ] = self::get_key_value( $chunk, $post_id );
				}

				$output[ $key ] = strtr( $string, $replacement_strings );
			}
		}

		return array_merge( $data, $output );
	}

	/**
	 * Get key value
	 *
	 * @param string $key    Key to search for.
	 * @param int    $post_id Post ID.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_key_value( $key, $post_id = 0 ) {
		$classes = self::get_classes();

		foreach ( $classes as $class ) {
			if ( $post_id ) {
				if ( method_exists( $class, 'set_post' ) ) {
					$class->set_post( $post_id );
				}
			}

			if ( method_exists( $class, 'get_key_value' ) ) {
				$meta = $class->get_key_value( $key );
			}
			if ( ! empty( $meta ) ) {
				return Sanitize::text( $meta[ $key ]['value'] );
			}

			// Check for custom fields.
			if ( strpos( $key, 'custom_field.' ) === 0 && method_exists( $class, 'get_custom_field' ) ) {
				$field_name = str_replace( 'custom_field.', '', $key );
				$value      = $class->get_custom_field( $field_name );
				if ( $value ) {
					return Sanitize::text( $value );
				}
			}
		}
		return '';
	}

	/**
	 * Get chunks
	 *
	 * @param string $string String to search for chunks.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public static function get_chunks( $string ) {
		$chunks  = [];
		$pattern = '/%([^%\s]+)%/u';
		preg_match_all( $pattern, $string, $matches );

		if ( ! empty( $matches[1] ) ) {
			$chunks = $matches[1];
		}
		return $chunks;
	}

	/**
	 * Get available classes
	 *
	 * @since 1.0.0
	 * @return array<string,object>
	 */
	private static function get_classes() {
		return [
			'post' => Post::get_instance(),
			'site' => Site::get_instance(),
			'term' => Term::get_instance(),
		];
	}
}
