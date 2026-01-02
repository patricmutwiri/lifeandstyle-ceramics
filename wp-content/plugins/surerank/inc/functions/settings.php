<?php
/**
 * Default Values
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Frontend\Description;
use SureRank\Inc\Frontend\Image;
use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Schema\Utils;
use SureRank\Inc\Schema\Validator;

/**
 * Default Values
 * This class will handle all default values.
 *
 * @since 1.0.0
 */
class Settings {
	/**
	 * Get settings.
	 *
	 * @param string $key Key to get the value.
	 * @param bool   $merge_with_db_value Merge with db value.
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function get( $key = '', $merge_with_db_value = true ) {
		$default_values = self::default_values();

		if ( ! is_array( $default_values ) ) {
			return [];
		}
		if ( ! $merge_with_db_value ) {
			return $default_values;
		}

		$setting_db = Get::option( SURERANK_SETTINGS, [], 'array' );

		$response = is_array( $setting_db ) && is_array( $default_values ) ? array_merge( $default_values, $setting_db ) : $default_values;

		return '' === $key ? $response : ( $response[ $key ] ?? null );
	}

	/**
	 * Migrate default post values.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function prepare_global_meta() {
		$default_values = self::post_meta();

		// Setting default values only for the title and description.
		$default_need_to_set = [
			'page_title',
			'facebook_title',
			'facebook_description',
			'twitter_title',
			'twitter_description',
		];

		// Run foreach loop to set default values.
		foreach ( $default_need_to_set as $key ) {
			$default_values['meta'][ $key ] = $default_values['meta'][ $key ] ?? '';
		}

		// Get global general values.
		$general_setting = self::get_meta_setting( 'general_settings', true );

		// Get advanced settings.
		$advanced_setting = self::get_meta_setting( 'advanced_settings', true );

		// Get social settings.
		$social_setting = self::get_meta_setting( 'social_settings', true );

		// Setting default values for the meta.
		$return_data = array_merge( $advanced_setting, $social_setting );

		// Run foreach loop to set default values.
		foreach ( $default_values['meta'] as $key => $value ) {
			if ( 'facebook_title' === $key || 'twitter_title' === $key ) {
				$return_data[ $key ] = $general_setting['title'] ?? $value;
				continue;
			}
			if ( 'facebook_description' === $key || 'twitter_description' === $key ) {
				$return_data[ $key ] = $general_setting['page_description'] ?? $value;
				continue;
			}

			$return_data[ $key ] = $general_setting[ $key ] ?? $value;
		}

		return $return_data;
	}

	/**
	 * Get meta setting.
	 *
	 * @param string $setting_name Setting name.
	 * @param bool   $merge_with_db_value Merge with db value.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_meta_setting( $setting_name = '', $merge_with_db_value = true ) {
		$default_values = self::default_values( $setting_name );

		if ( ! is_array( $default_values ) ) {
			return [];
		}

		if ( ! $merge_with_db_value ) {
			return $default_values;
		}

		$setting_db    = Get::option( SURERANK_SETTINGS, [], 'array' );
		$setting_local = $default_values;

		return is_array( $setting_db ) && is_array( $setting_local ) ? array_merge( $setting_local, $setting_db ) : $setting_local;
	}

	/**
	 * Migrate default post for post.
	 *
	 * @param array<string, mixed>|string $meta Meta data or only default string is passed.
	 * @param int                         $post_id Post ID.
	 * @param bool                        $is_required_global_meta If global meta is required.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function prepare_post_meta( $meta, $post_id = 0, $is_required_global_meta = false ) {
		$default_values = self::prepare_global_meta();

		// If meta is not array then return default values.
		if ( 'default' === $meta ) {
			$meta = self::post_meta()['meta'];
		}

		// Setting default values if not set.
		foreach ( $meta as $key => $value ) {
			// If value is not empty then continue.
			if ( ! empty( $value ) ) {
				continue;
			}
			// Setting value for the facebook title.
			if ( 'facebook_title' === $key ) {
				$meta['facebook_title'] = ! empty( $meta['page_title'] ) ? $meta['page_title'] : $default_values['page_title'];
			} elseif ( 'twitter_title' === $key && empty( $meta['twitter_same_as_facebook'] ) ) {
				$meta['twitter_title'] = ! empty( $meta['page_title'] ) ? $meta['page_title'] : $default_values['page_title'];
			} elseif ( 'facebook_description' === $key ) {
				$meta['facebook_description'] = ! empty( $meta['page_description'] ) ? $meta['page_description'] : $default_values['page_description'];
			} elseif ( 'twitter_description' === $key && empty( $meta['twitter_same_as_facebook'] ) ) {
				$meta['twitter_description'] = ! empty( $meta['page_description'] ) ? $meta['page_description'] : $default_values['page_description'];
			} elseif ( 'facebook_image_url' === $key ) {
				$get_featured_image         = get_the_post_thumbnail_url( $post_id );
				$social_image               = ! empty( $get_featured_image ) ? $get_featured_image : '';
				$meta['facebook_image_url'] = $social_image;

				// Setting value for the twitter image url.
				if ( empty( $meta['twitter_image_url'] ) && empty( $meta['twitter_same_as_facebook'] ) ) {
					$meta['twitter_image_url'] = $social_image;
				}
			} elseif ( isset( $default_values[ $key ] ) ) {
				$meta[ $key ] = $default_values[ $key ];
			}
		}

		return $is_required_global_meta ? array_merge( [ 'default_global_meta' => $default_values ], $meta ) : $meta;
	}

	/**
	 * Default values for the site meta variables.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function post_meta() {
		return [
			'meta'          => [
				'page_title'               => '',
				'page_description'         => '',
				'robots'                   => [
					'general' => [],
				],
				'facebook_image_url'       => '',
				'facebook_title'           => '',
				'facebook_description'     => '',
				'facebook_image_id'        => '',
				'twitter_image_url'        => '',
				'twitter_title'            => '',
				'twitter_description'      => '',
				'twitter_image_id'         => '',
				'twitter_same_as_facebook' => true,
			],
			'focus_keyword' => '',
			'canonical_url' => '',
		];
	}

	/**
	 * Get global default meta values.
	 * This array will be used to set default values for the global meta setting and same as global admin store reducer default dataSettings so make sure to update both if you are updating this.
	 *
	 * @param string $key Key to get the value.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function default_values( $key = '' ) {
		return Defaults::get_instance()->get_global_defaults( $key );
	}

	/**
	 * Get post default values.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function post_default_values() {
		return Defaults::get_instance()->get_post_defaults();
	}

	/**
	 * Get post default meta values.
	 * This array will be used to set default values for the global meta setting and same as global admin store reducer default dataSettings so make sure to update both if you are updating this.
	 *
	 * @param string $key Key to get the value.
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function default_post_values( $key = '', $post_id = 0, $post_type = '' ) {
		$post_meta = [
			'page_title'                => '%title% - %site_name%',
			'auto_generate_description' => true,
			'page_description'          => '',
			'robots'                    => [
				'general' => [],
			],
			'canonical_url'             => '',
			// Facebook.
			'facebook_title'            => '%title% - %site_name%',
			'facebook_description'      => '%content%',
			'facebook_image_url'        => '',
			'facebook_image_id'         => 0,
			'facebook_image_width'      => 0,
			'facebook_image_height'     => 0,
			// (X) Twitter.
			'twitter_title'             => '%title% - %site_name%',
			'twitter_description'       => '%content%',
			'twitter_image_url'         => '',
			'twitter_image_id'          => 0,
			'twitter_card_type'         => 'summary_large_image',
			'twitter_same_as_facebook'  => true,
			'twitter_profile_username'  => '',
			'twitter_profile_fallback'  => '',
			'schemas'                   => Utils::get_default_schemas(),
		];

		if ( ! empty( $key ) && isset( $post_meta[ $key ] ) ) {
			return $post_meta[ $key ];
		}

		return $post_meta;
	}

	/**
	 * Get term default meta values.
	 * This array will be used to set default values for the global meta setting and same as global admin store reducer default dataSettings so make sure to update both if you are updating this.
	 *
	 * @param string $key Key to get the value.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function default_term_values( $key = '' ) {
		$term_meta = array_merge(
			self::default_post_values(),
			[
				'page_title'           => '%term_title% - %site_name%',
				'page_description'     => '',
				// Facebook.
				'facebook_title'       => '%term_title% - %site_name%',
				'facebook_description' => '%term_description%',
				// (X) Twitter.
				'twitter_title'        => '%term_title% - %site_name%',
				'twitter_description'  => '%term_description%',
				'schemas'              => Utils::get_default_schemas(),
			]
		);

		if ( ! empty( $key ) && isset( $term_meta[ $key ] ) ) {
			return $term_meta[ $key ];
		}

		return $term_meta;
	}

	/**
	 * Prepare schemas for the current post or term.
	 *
	 * @param array<string, mixed> $meta Meta data.
	 * @param string               $post_type Post type.
	 * @param int                  $post_id Post ID.
	 * @param bool                 $is_taxonomy Whether the post is a taxonomy.
	 * @return array<string, mixed>
	 * @since 0.0.1
	 */
	public static function prepare_schemas( $meta, $post_type, $post_id = 0, $is_taxonomy = false ) {
		if ( ! isset( $meta['schemas'] ) ) {
			return [];
		}

		$schemas = [];

		foreach ( $meta['schemas'] as $schema_key => $schema ) {
			if ( Validator::validate_schema_rules( $schema, $post_type, $post_id, $is_taxonomy ) ) {
				$schemas[ $schema_key ] = $schema;
			}
		}

		return $schemas;
	}

	/**
	 * Get the post description.
	 *
	 * @param int                  $id Post ID.
	 * @param array<string, mixed> $meta Meta data.
	 * @param array<string, mixed> $global_values Global values.
	 * @param string               $type post type.
	 * @return string Description.
	 */
	public static function get_description( $id = 0, $meta = [], $global_values = [], $type = 'post' ) {
		if ( 0 === $id ) {
			return '';
		}

		if ( Settings::get( 'auto_generate_description' ) ) {
			$auto_generated_description = Get::formatted_description( Description::get_instance()->{ $type }( $id ) );
		} else {
			$auto_generated_description = $global_values['page_description'] ?? '';
		}

		return $auto_generated_description;
	}

	/**
	 * Format array
	 *
	 * @param array<string, mixed> $array Array to format.
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function format_array( $array ) {
		if ( empty( $array ) ) {
			return [];
		}
		$option_names = Defaults::get_instance()->get_post_meta_keys();
		$all_settings = [];
		foreach ( $option_names as $option_name ) {

			if ( isset( $array[ $option_name ] ) && is_array( $array[ $option_name ] ) ) {
				/**
				 * We are adding empty array to option name if it is empty.
				 *
				 * This will add a option as $robots => [], because it is needed even if the robots is empty.
				 */
				if ( empty( $array[ $option_name ] ) ) {
					$all_settings[ $option_name ] = $array[ $option_name ];
				} else {
					$all_settings = array_merge( $all_settings, $array[ $option_name ] );
				}
			} else {
				$all_settings[ $option_name ] = $array[ $option_name ] ?? null;
			}
		}
		return $all_settings;
	}

	/**
	 * Preparing post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @param bool   $is_taxonomy Whether the post is a taxonomy.
	 * @return array<string, mixed>
	 * @since 0.0.1
	 */
	public static function prep_post_meta( $post_id = 0, $post_type = '', $is_taxonomy = false ) {
		if ( 0 === $post_id ) {
			return [];
		}

		Post::get_instance()->set_post( $post_id );

		$post_meta = Get::all_post_meta( $post_id );
		$post_meta = is_array( $post_meta ) ? $post_meta : [];

		$default_values = self::format_array( Defaults::get_instance()->get_post_defaults( false ) );

		// Getting the global settings.
		$global_values = Settings::get();

		// Get CPT/taxonomy level defaults (extended meta templates) via filter.
		// This allows pro plugin to conditionally add extended meta templates.
		$extended_meta_values = apply_filters(
			'surerank_prep_post_meta_extended_values',
			[],
			$post_type,
			$is_taxonomy,
			$global_values,
			$post_id
		);

		// remove empty values from $post_meta.
		$post_meta = array_filter(
			$post_meta,
			static function( $value ) {
				return Validate::empty_string( $value );
			}
		);

		// Current post meta to match the defaults with 3-level hierarchy:
		// 1. Base defaults -> 2. Global settings -> 3. CPT/Taxonomy defaults -> 4. Post meta.
		$meta = array_merge( $default_values, $global_values, $extended_meta_values, $post_meta );

		$meta = apply_filters( 'surerank_prep_post_meta', $meta, $post_id, $post_type, $is_taxonomy );

		// Prepare schemas for the current post.
		if ( ! empty( $post_type ) ) {
			$meta['schemas'] = self::prepare_schemas( $meta, $post_type, $post_id, $is_taxonomy );
		}

		$meta['auto_generated_og_image'] = self::auto_generated_og_image( $post_id, false );
		$meta['auto_description']        = self::get_description( $post_id, $meta, $global_values, 'post' );

		foreach ( $meta as $key => $value ) {

			// If value is not empty then continue.
			if ( ! empty( $value ) ) {
				continue;
			}
			$title                  = ! empty( $meta['page_title'] ) ? $meta['page_title'] : ( $global_values['page_title'] ?? '' );
			$featured_image         = get_the_post_thumbnail_url( $post_id );
			$featured_image_id      = get_post_thumbnail_id( $post_id );
			$featured_image_details = $featured_image_id ? wp_get_attachment_metadata( $featured_image_id, false ) : [];

			switch ( $key ) {
				case 'facebook_title':
					$meta['facebook_title'] = $title;
					break;

				case 'twitter_title':
					$meta['twitter_title'] = $title;
					break;

				case 'facebook_image_url':
					$meta['facebook_image_url'] = ! empty( $featured_image ) ? $featured_image : '';
					$meta['facebook_image_id']  = $featured_image_id;
					break;

				case 'facebook_description':
					$meta['facebook_description'] = $meta['page_description'];
					break;

				case 'twitter_description':
					if ( ! empty( $meta['twitter_same_as_facebook'] ) ) {
						$meta['twitter_description'] = $meta['page_description'];
					}
					$meta['twitter_description'] = $meta['page_description'];
					break;

				case 'twitter_image_url':
					if ( ! empty( $meta['twitter_same_as_facebook'] ) ) {
						$meta['twitter_image_url'] = $meta['facebook_image_url'] ?? '';
						$meta['twitter_image_id']  = $meta['facebook_image_id'] ?? '';
					} else {
						$meta['twitter_image_url'] = ! empty( $featured_image ) ? $featured_image : '';
						$meta['twitter_image_id']  = $featured_image_id;
					}
					break;
				case 'twitter_profile_username':
					$meta['twitter_profile_username'] = $global_values['twitter_profile_username'] ?? '';
					break;

				case 'twitter_profile_fallback':
					$meta['twitter_profile_fallback'] = $global_values['twitter_profile_fallback'] ?? '';
					break;

				case 'canonical_url':
					Post::get_instance()->set_post( $post_id );
					$meta['canonical_url'] = Post::get_instance()->get_permalink();
					break;
			}
		}

		Get::fb_image_size( $meta );
		return $meta;
	}

	/**
	 * Preparing term meta.
	 *
	 * @param int    $term_id Post ID.
	 * @param string $post_type Post type.
	 * @param bool   $is_taxonomy Whether the post is a taxonomy.
	 * @return array<string, mixed>
	 * @since 0.0.1
	 */
	public static function prep_term_meta( $term_id = 0, $post_type = '', $is_taxonomy = false ) {
		if ( 0 === $term_id ) {
			return [];
		}

		$term_meta = Get::all_term_meta( $term_id );
		$term_meta = is_array( $term_meta ) ? $term_meta : [];

		$default_values = self::format_array( Defaults::get_instance()->get_post_defaults( false ) );

		// Getting the global settings.
		$global_values = Settings::get();

		// Get taxonomy level defaults (extended meta templates) via filter.
		// This allows pro plugin to conditionally add extended meta templates.
		$extended_meta_values = apply_filters(
			'surerank_prep_term_meta_extended_values',
			[],
			$post_type,
			$is_taxonomy,
			$global_values,
			$term_id
		);

		// remove empty values from $term_meta.
		$term_meta = array_filter(
			$term_meta,
			static function( $value ) {
				return Validate::empty_string( $value );
			}
		);

		// Current term meta to match the defaults with 3-level hierarchy:
		// 1. Base defaults -> 2. Global settings -> 3. Taxonomy defaults -> 4. Term meta.
		$meta = array_merge( $default_values, $global_values, $extended_meta_values, $term_meta );

		$meta['page_description']        = str_replace( '%content%', '%term_description%', $meta['page_description'] );
		$meta['auto_description']        = self::get_description( $term_id, $meta, $global_values, 'taxonomy' );
		$meta['auto_generated_og_image'] = self::auto_generated_og_image( $term_id, true );

		// Prepare schemas for the current term.
		if ( ! empty( $post_type ) ) {
			$meta['schemas'] = self::prepare_schemas( $meta, $post_type, $term_id, $is_taxonomy );
		}

		foreach ( $meta as $key => $value ) {
			// If value is not empty then continue.
			$meta[ $key ] = self::replace_meta_variables( $value );
			if ( ! empty( $value ) ) {
				continue;
			}
			$title = ! empty( $meta['page_title'] ) ? $meta['page_title'] : ( $global_values['page_title'] ?? '' );

			switch ( $key ) {
				case 'facebook_title':
					$meta['facebook_title'] = $title;
					break;

				case 'twitter_title':
					$meta['twitter_title'] = $title;
					break;

				case 'facebook_description':
					$meta['facebook_description'] = $meta['page_description'];
					break;

				case 'twitter_description':
					$meta['twitter_description'] = $meta['page_description'];
					break;

				case 'canonical_url':
					$meta['canonical_url'] = self::get_term_url( $term_id );
					break;
			}
		}

		return $meta;
	}

	/**
	 * Get the term url.
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public static function get_term_url( $term_id ) {
		if ( ! $term_id ) {
			return '';
		}

		$term = get_term( $term_id );

		if ( is_wp_error( $term ) || ! $term ) {
			return '';
		}

		$term_link = get_term_link( $term );

		if ( is_wp_error( $term_link ) ) {
			return '';
		}

		return $term_link;
	}

	/**
	 * Replace meta variables.
	 *
	 * @param string|array<int|string, mixed>|null $value Value to replace.
	 * @return string|array<int|string, mixed>
	 */
	public static function replace_meta_variables( &$value ) {
		if ( null === $value ) {
			return ''; // early bail.
		}

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $key => $val ) {
				$value[ $key ] = self::replace_meta_variables( $val );
			}
			return $value;
		}

		$value = str_replace( '%title%', '%term_title%', $value );
		$value = str_replace( '%excerpt%', '%term_description%', $value );
		return $value;
	}

	/**
	 * Get the post image.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $is_taxonomy Whether the post is a taxonomy.
	 * @return string|null
	 */
	public static function auto_generated_og_image( $post_id = 0, $is_taxonomy = false ) {
		if ( 0 === $post_id ) {
			return '';
		}

		if ( $is_taxonomy ) {
			$image = Image::get_instance()->get_taxonomy_image( $post_id );
		} else {
			$image = Image::get_instance()->get_singular_page_image( $post_id );
		}
		return $image;
	}
}
