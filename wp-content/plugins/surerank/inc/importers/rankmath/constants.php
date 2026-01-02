<?php
/**
 * RankMath Constants
 *
 * Defines constants and utility functions for RankMath SEO plugin importer.
 *
 * @package SureRank\Inc\Importers
 * @since   1.1.0
 */

namespace SureRank\Inc\Importers\Rankmath;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RankMathConstants
 */
class Constants {
	/**
	 * Plugin Name.
	 */
	public const PLUGIN_NAME = 'Rank Math SEO';

	/**
	 * RankMath plugin file path.
	 */
	public const PLUGIN_FILE = 'seo-by-rank-math/rank-math.php';

	/**
	 * Plugin Slug.
	 */
	public const PLUGIN_SLUG = 'rankmath';

	/**
	 * Prefix for RankMath meta keys.
	 */
	public const META_KEY_PREFIX = 'rank_math_';

	/**
	 * Meta keys to exclude during detection.
	 */
	public const EXCLUDED_META_KEYS = [
		'rank_math_internal_links_processed',
		'rank_math_primary_category',
		'rank_math_seo_score',
		'rank_math_analytic_object_id',
	];

	/**
	 * RankMath global robots settings.
	 */
	public const GLOBAL_ROBOTS = [
		'noindex'   => 'no',
		'nofollow'  => 'no',
		'noarchive' => 'no',
	];

	/**
	 * Allowed post and term types for import.
	 */
	public const NOT_ALLOWED_TYPES = [
		'elementor_library',
		'product_shipping_class',
	];

	/**
	 * Mapping of RankMath robots to SureRank robots.
	 */
	public const ROBOTS_MAPPING = [
		'noindex'   => 'post_no_index',
		'nofollow'  => 'post_no_follow',
		'noarchive' => 'post_no_archive',
	];

	/**
	 * Mapping of RankMath placeholders to SureRank placeholders.
	 */
	public const PLACEHOLDERS_MAPPING = [
		'%sitename%'         => '%site_name%',
		'%modified%'         => '%modified%',
		'%date%'             => '%published%',
		'%sep%'              => '-',
		'%page%'             => '%page%',
		'%currenttime%'      => '%currenttime%',
		'%currentyear%'      => '%currentyear%',
		'%currentmonth%'     => '%currentmonth%',
		'%currentday%'       => '%currentday%',
		'%currentdate%'      => '%currentdate%',
		'%org_name%'         => '%org_name%',
		'%org_url%'          => '%org_url%',
		'%org_logo%'         => '%org_logo%',
		'%name%'             => '%author_name%',
		'%post_url%'         => '%post_url%',
		'%title%'            => '%title%',
		'%excerpt%'          => '%excerpt%',
		'%term%'             => '%term_title%',
		'%term_description%' => '%term_description%',
		'%sitedesc%'         => '%tagline%',
	];

	/**
	 * Mapping of RankMath social meta to SureRank social meta.
	 */
	private const SOCIAL_MAPPING = [
		'rank_math_facebook_title'       => [ '', 'facebook_title' ],
		'rank_math_facebook_description' => [ '', 'facebook_description' ],
		'rank_math_facebook_image'       => [ 'open_graph_image', 'facebook_image_url' ],
		'rank_math_facebook_image_id'    => [ 'open_graph_image_id', 'facebook_image_id' ],
		'rank_math_twitter_title'        => [ '', 'twitter_title' ],
		'rank_math_twitter_description'  => [ '', 'twitter_description' ],
		'rank_math_twitter_image'        => [ '', 'twitter_image_url' ],
		'rank_math_twitter_image_id'     => [ '', 'twitter_image_id' ],
	];

	/**
	 * Mapping for global title and description settings.
	 */
	private const TITLE_DESC_MAPPING = [
		'homepage_title'                => 'home_page_title',
		'homepage_description'          => 'home_page_description',
		'homepage_facebook_title'       => 'home_page_facebook_title',
		'homepage_facebook_description' => 'home_page_facebook_description',
	];

	/**
	 * Mapping for archive settings.
	 */
	private const ARCHIVE_SETTINGS_MAPPING = [
		'disable_author_archives' => 'author_archive',
		'disable_date_archives'   => 'date_archive',
		'noindex_paginated_pages' => 'noindex_paginated_pages',
	];

	/**
	 * Mapping for sitemap settings.
	 */
	private const SITEMAP_MAPPING = [
		'include_images' => 'enable_xml_image_sitemap',
	];

	/**
	 * Mapping for robot settings.
	 */
	private const ROBOT_KEYS_MAPPING = [
		'author_custom_robots' => [ 'author_robots', 'author' ],
	];

	/**
	 * Mapping for social settings.
	 */
	private const SOCIAL_SETTINGS_MAPPING = [
		'social_url_facebook'     => 'facebook_page_url',
		'facebook_author_urls'    => 'facebook_author_fallback',
		'twitter_author_names'    => 'twitter_profile_username',
		'homepage_facebook_image' => 'home_page_facebook_image_url',
		'open_graph_image'        => 'fallback_image',
	];

	/**
	 * Get RankMath meta data for a specific post or term.
	 *
	 * @param int  $id         Post or Term ID.
	 * @param bool $is_taxonomy Whether the ID is for a taxonomy term.
	 * @return array<string, mixed> RankMath meta data.
	 */
	public static function rank_math_meta_data( $id, $is_taxonomy = false ) {
		$data                      = $is_taxonomy ? get_term_meta( $id ) : get_post_meta( $id );
		$rank_math_global_settings = get_option( 'rank-math-options-titles', [] );

		if ( ! is_wp_error( $data ) ) {
			foreach ( $rank_math_global_settings as $key => $value ) {
				// If the key is not already present in $data, add it.
				if ( ! array_key_exists( $key, $data ) ) {
					$data[ $key ] = $value;
				}
			}
		}
		return $data;
	}

	/**
	 * Get mapped robots.
	 *
	 * @param array<string, string> $home_page_robots Home page robots.
	 * @return array<string, string> Mapped robots.
	 */
	public static function get_mapped_robots( $home_page_robots ) {
		$mapped_robots = self::GLOBAL_ROBOTS;

		if ( empty( $home_page_robots ) || ! is_array( $home_page_robots ) ) {
			return $mapped_robots;
		}

		foreach ( $home_page_robots as $value ) {
			if ( isset( $mapped_robots[ $value ] ) ) {
				$mapped_robots[ $value ] = 'yes';
			}
		}
		return $mapped_robots;
	}

	/**
	 * Get the robot key based on the type.
	 *
	 * @param string               $type The type of post or term.
	 * @param array<string, mixed> $rank_math_meta RankMath meta data.
	 * @param bool                 $is_taxonomy Whether the type is a taxonomy.
	 * @return string The robot key or 'robots_global' if not found.
	 */
	public static function get_robot_key( $type, $rank_math_meta, $is_taxonomy = false ) {

		if ( empty( $type ) ) {
			return 'robots_global';
		}

		$cpt_custom_robots = $is_taxonomy ? 'tax_' . $type . '_custom_robots' : 'pt_' . $type . '_custom_robots';
		$cpt_robot_key     = $is_taxonomy ? 'tax_' . $type . '_robots' : 'pt_' . $type . '_robots';

		if ( isset( $rank_math_meta[ $cpt_custom_robots ] ) && $rank_math_meta[ $cpt_custom_robots ] === 'on' ) {
			return $cpt_robot_key;
		}

		return 'robots_global';
	}

	/**
	 * Replace RankMath placeholders with SureRank placeholders in a given value.
	 *
	 * @param string|array<string> $value The value containing placeholders to replace.
	 * @param string|null          $separator Optional separator to replace the %sep% placeholder.
	 * @return string The value with placeholders replaced.
	 */
	public static function replace_placeholders( $value, ?string $separator = null ) {
		if ( is_array( $value ) ) {
			$replaced = array_map( static fn( $item) => self::replace_placeholders( $item, $separator ), $value );
			return implode( ', ', $replaced );
		}

		if ( ! is_string( $value ) ) {
			return $value;
		}

		$placeholders = self::PLACEHOLDERS_MAPPING;

		// If the value contains %sep% and a separator is provided, override the default %sep% mapping.
		if ( $separator !== null && strpos( $value, '%sep%' ) !== false ) {
			$placeholders['%sep%'] = $separator;
		}

		// Split the string into parts based on %...% patterns.
		preg_match_all( '/%[^%]+%|[^%]+/', $value, $matches );
		$result = '';

		foreach ( $matches[0] as $part ) {
			// Check if the part is a placeholder (starts and ends with %).
			if ( preg_match( '/^%[^%]+%$/', $part ) ) {
				// Replace placeholder if it exists in PLACEHOLDERS_MAPPING else SKIP.
				if ( isset( $placeholders[ $part ] ) ) {
					$result .= $placeholders[ $part ];
				}
			} else {
				// Keep the part as is (either non-placeholder text or unmatched placeholder).
				$result .= $part;
			}
		}

		return $result;
	}

	/**
	 * Get the page title and description keys based on the type.
	 *
	 * @param string $type The type of post or term.
	 * @param bool   $is_taxonomy Whether the type is a taxonomy.
	 * @return array<string, string> An array containing 'page_title' and 'page_description' keys.
	 */
	public static function get_page_title_description( $type, $is_taxonomy = false ) {
		if ( ! $type ) {
			return [
				'page_title'       => 'pt_page_title',
				'page_description' => 'pt_page_description',
			];
		}

		if ( $is_taxonomy ) {
			return [
				'page_title'       => 'tax_' . $type . '_title',
				'page_description' => 'tax_' . $type . '_description',
			];
		}

		return [
			'page_title'       => 'pt_' . $type . '_title',
			'page_description' => 'pt_' . $type . '_description',
		];
	}

	/**
	 * Get social mapping with filter.
	 *
	 * @return array<string, array<int, string>> Social mapping array.
	 */
	public static function get_social_mapping() {
		return apply_filters( 'surerank_rankmath_social_mapping', self::SOCIAL_MAPPING );
	}

	/**
	 * Get title description mapping with filter.
	 *
	 * @return array<string, string> Title description mapping array.
	 */
	public static function get_title_desc_mapping() {
		return apply_filters( 'surerank_rankmath_title_desc_mapping', self::TITLE_DESC_MAPPING );
	}

	/**
	 * Get archive settings mapping with filter.
	 *
	 * @return array<string, string> Archive settings mapping array.
	 */
	public static function get_archive_settings_mapping() {
		return apply_filters( 'surerank_rankmath_archive_settings_mapping', self::ARCHIVE_SETTINGS_MAPPING );
	}

	/**
	 * Get sitemap mapping with filter.
	 *
	 * @return array<string, string> Sitemap mapping array.
	 */
	public static function get_sitemap_mapping() {
		return apply_filters( 'surerank_rankmath_sitemap_mapping', self::SITEMAP_MAPPING );
	}

	/**
	 * Get robot keys mapping with filter.
	 *
	 * @return array<string, array<int, string>> Robot keys mapping array.
	 */
	public static function get_robot_keys_mapping() {
		return apply_filters( 'surerank_rankmath_robot_keys_mapping', self::ROBOT_KEYS_MAPPING );
	}

	/**
	 * Get social settings mapping with filter.
	 *
	 * @return array<string, string> Social settings mapping array.
	 */
	public static function get_social_settings_mapping() {
		return apply_filters( 'surerank_rankmath_social_settings_mapping', self::SOCIAL_SETTINGS_MAPPING );
	}
}
