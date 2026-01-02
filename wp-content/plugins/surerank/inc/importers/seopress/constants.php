<?php
/**
 * SEOPress Constants
 *
 * Defines constants and utility functions for SEOPress plugin importer.
 *
 * @package SureRank\Inc\Importers
 * @since   1.3.0
 */

namespace SureRank\Inc\Importers\Seopress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SeopressConstants
 */
class Constants {
	/**
	 * Human-readable plugin name.
	 */
	public const PLUGIN_NAME = 'SEOPress';

	/**
	 * Plugin slug.
	 */
	public const PLUGIN_SLUG = 'seopress';

	/**
	 * SEOPress plugin file path.
	 */
	public const PLUGIN_FILE = 'wp-seopress/seopress.php';

	/**
	 * Prefix for SEOPress meta keys.
	 */
	public const META_KEY_PREFIX = '_seopress_';

	/**
	 * SEOPress global robots settings.
	 */
	public const GLOBAL_ROBOTS = [
		'noindex'  => 'no',
		'nofollow' => 'no',
	];

	/**
	 * Allowed post and term types for import.
	 */
	public const NOT_ALLOWED_TYPES = [
		'elementor_library',
		'product_shipping_class',
	];

	/**
	 * Mapping of SEOPress robots to SureRank robots for individual posts/terms.
	 */
	public const ROBOTS_MAPPING = [
		'noindex'   => 'post_no_index',
		'nofollow'  => 'post_no_follow',
		'noarchive' => 'post_no_archive',
	];

	public const EXCLUDED_META_KEYS = [
		'_seopress_analysis_target_kw',
		'_seopress_analysis_data',
		'_seopress_content_analysis',
	];

	/**
	 * Mapping of SEOPress social meta to SureRank social meta.
	 */
	public const SOCIAL_MAPPING = [
		'_seopress_social_fb_title'                  => [ '', 'facebook_title' ],
		'_seopress_social_fb_desc'                   => [ '', 'facebook_description' ],
		'_seopress_social_fb_img'                    => [ '', 'facebook_image_url' ],
		'_seopress_social_fb_img_attachment_id'      => [ '', 'facebook_image_id' ],
		'_seopress_social_twitter_title'             => [ '', 'twitter_title' ],
		'_seopress_social_twitter_desc'              => [ '', 'twitter_description' ],
		'_seopress_social_twitter_img'               => [ '', 'twitter_image_url' ],
		'_seopress_social_twitter_img_attachment_id' => [ '', 'twitter_image_id' ],
	];

	/**
	 * Mapping of SEOPress placeholders to SureRank placeholders.
	 * Based on RankMath SEOPress importer patterns.
	 */
	public const PLACEHOLDERS_MAPPING = [
		'%%sitetitle%%'             => '%site_name%',
		'%%tagline%%'               => '%tagline%',
		'%%post_title%%'            => '%title%',
		'%%post_excerpt%%'          => '%excerpt%',
		'%%post_date%%'             => '%published%',
		'%%post_modified_date%%'    => '%modified%',
		'%%post_author%%'           => '%author_name%',
		'%%post_category%%'         => '%category%',
		'%%post_tag%%'              => '%tag%',
		'%%_category_title%%'       => '%term_title%',
		'%%_category_description%%' => '%term_description%',
		'%%tag_title%%'             => '%term_title%',
		'%%tag_description%%'       => '%term_description%',
		'%%term_title%%'            => '%term_title%',
		'%%term_description%%'      => '%term_description%',
		'%%archive_title%%'         => '%title%',
		'%%archive_date%%'          => '%currentdate%',
		'%%archive_date_day%%'      => '%currentday%',
		'%%archive_date_month%%'    => '%currentmonth%',
		'%%archive_date_year%%'     => '%currentyear%',
		'%%currentdate%%'           => '%currentdate%',
		'%%sep%%'                   => ' - ',
	];

	/**
	 * Mapping of SEOPress nested robot keys to SureRank keys.
	 * These handle nested structures like seopress_titles_single_titles[post][noindex]
	 */
	public const ROBOT_KEYS_MAPPING = [
		'seopress_titles_single_titles'   => 'single_titles_robots', // Nested under post_type.
		'seopress_titles_archives_titles' => 'archive_robots', // Nested under archive_type.
		'seopress_titles_tax_titles'      => 'taxonomy_robots', // Nested under taxonomy.
	];

	/**
	 * Mapping of SEOPress nested title and description settings to SureRank.
	 * These map from nested array paths like seopress_titles_single_titles[post][title]
	 */
	public const TITLE_DESC_MAPPING = [
		'seopress_titles_home_title'          => 'home_page_title',
		'seopress_titles_home_desc'           => 'home_page_description',
		'seopress_titles_single_titles'       => 'page_title', // Nested under post_type for title.
		'seopress_titles_single_descriptions' => 'page_description', // Nested under post_type for description.
	];

	/**
	 * Mapping of SEOPress archive settings.
	 */
	public const ARCHIVE_SETTINGS_MAPPING = [
		'titles_archives_noindex' => 'date_archive',
		'titles_author_noindex'   => 'author_archive',
	];

	/**
	 * Mapping of SEOPress social/OG settings to SureRank.
	 */
	public const SOCIAL_SETTINGS_MAPPING = [
		'seopress_social_option_name' => [
			'social_twitter_card_og' => 'twitter_card_type',
			'social_knowledge_name'  => 'website_name',
			'social_knowledge_img'   => 'website_logo',
			'social_knowledge_phone' => 'website_owner_phone',
		],
	];

	/**
	 * Mapping of SEOPress sitemap settings.
	 */
	public const SITEMAP_MAPPING = [
		'seopress_xml_sitemap_option_name' => [
			'xml_sitemap_general_enable' => 'enable_xml_sitemap',
			'xml_sitemap_html_enable'    => 'enable_html_sitemap',
			'xml_sitemap_img_enable'     => 'enable_xml_image_sitemap',
			'xml_sitemap_video_enable'   => 'enable_xml_video_sitemap',
			'xml_sitemap_news_enable'    => 'enable_xml_news_sitemap',
		],
	];

	/**
	 * Get meta data for SEOPress post or term.
	 *
	 * @param int    $id          Post or term ID.
	 * @param bool   $is_taxonomy Whether it's a taxonomy term.
	 * @param string $type        Post type or taxonomy.
	 * @return array<string, mixed>
	 */
	public static function seopress_meta_data( int $id, bool $is_taxonomy, string $type = '' ): array {
		$meta_data = [];

		if ( $is_taxonomy ) {
			$term_meta = get_term_meta( $id );
			if ( is_array( $term_meta ) ) {
				foreach ( $term_meta as $key => $value ) {
					if ( str_starts_with( $key, self::META_KEY_PREFIX ) ) {
						$meta_data[ $key ] = is_array( $value ) && isset( $value[0] ) ? $value[0] : $value;
					}
				}
			}
		} else {
			$post_meta = get_post_meta( $id );
			if ( is_array( $post_meta ) ) {
				foreach ( $post_meta as $key => $value ) {
					if ( str_starts_with( $key, self::META_KEY_PREFIX ) ) {
						$meta_data[ $key ] = is_array( $value ) && isset( $value[0] ) ? $value[0] : $value;
					}
				}
			}
		}

		$meta_data['separator'] = '-';
		return $meta_data;
	}

	/**
	 * Get social mapping with filter support.
	 *
	 * @return array<string, array<int, string>> Social mapping array.
	 */
	public static function get_social_mapping(): array {
		return apply_filters( 'surerank_seopress_social_mapping', self::SOCIAL_MAPPING );
	}

	/**
	 * Get title and description mapping with filter support.
	 *
	 * @return array<string, string> Title and description mapping array.
	 */
	public static function get_title_desc_mapping(): array {
		return apply_filters( 'surerank_seopress_title_desc_mapping', self::TITLE_DESC_MAPPING );
	}

	/**
	 * Get archive settings mapping with filter support.
	 *
	 * @return array<string, string> Archive settings mapping array.
	 */
	public static function get_archive_settings_mapping(): array {
		return apply_filters( 'surerank_seopress_archive_settings_mapping', self::ARCHIVE_SETTINGS_MAPPING );
	}

	/**
	 * Get sitemap mapping with filter support.
	 *
	 * @return array<string, array<string, string>> Sitemap mapping array.
	 */
	public static function get_sitemap_mapping(): array {
		return apply_filters( 'surerank_seopress_sitemap_mapping', self::SITEMAP_MAPPING );
	}

	/**
	 * Get robot keys mapping with filter support.
	 *
	 * @return array<string, string> Robot keys mapping array.
	 */
	public static function get_robot_keys_mapping(): array {
		return apply_filters( 'surerank_seopress_robot_keys_mapping', self::ROBOT_KEYS_MAPPING );
	}

	/**
	 * Get social settings mapping with filter support.
	 *
	 * @return array<string, array<string, string>> Social settings mapping array.
	 */
	public static function get_social_settings_mapping(): array {
		return apply_filters( 'surerank_seopress_social_settings_mapping', self::SOCIAL_SETTINGS_MAPPING );
	}

	/**
	 * Get robots mapping with filter support.
	 *
	 * @return array<string, string> Robots mapping array.
	 */
	public static function get_robots_mapping(): array {
		return apply_filters( 'surerank_seopress_robots_mapping', self::ROBOTS_MAPPING );
	}

	/**
	 * Replace SEOPress placeholders with SureRank equivalents.
	 *
	 * @param string $text      Text containing placeholders.
	 * @param string $separator Default separator.
	 * @return string
	 */
	public static function replace_placeholders( string $text, string $separator = ' - ' ): string {
		if ( empty( $text ) ) {
			return '';
		}

		$mapping            = self::PLACEHOLDERS_MAPPING;
		$mapping['%%sep%%'] = $separator;

		return str_replace( array_keys( $mapping ), array_values( $mapping ), $text );
	}

}
