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

use SureRank\Inc\API\Onboarding;
use SureRank\Inc\Schema\Utils;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Default Values
 * This class will handle all default values.
 *
 * @since 1.0.0
 */
class Defaults {

	use Get_Instance;

	/**
	 * Default values for the global - General.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_general_defaults = [
		'separator'                 => '-',
		'page_title'                => '%title% - %site_name%',
		'auto_generate_description' => true,
		'page_description'          => '%content%',
		'auto_description'          => '',
		'fallback_image'            => '',
		'auto_generated_og_image'   => '',
		'canonical_url'             => '',
		'focus_keyword'             => '',
	];

	/**
	 * Default values for the global - Homepage.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $feature_management_defaults = [
		'enable_page_level_seo' => true,
		'enable_google_console' => true,
		'enable_schemas'        => true,
		'enable_migration'      => true,
	];

	/**
	 * Default values for the global - Homepage.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_homepage_defaults = [
		'home_page_title'                 => '%site_name% - %tagline%',
		'home_page_description'           => '%tagline%',
		'home_page_facebook_image_url'    => '',
		'home_page_facebook_title'        => '',
		'home_page_facebook_description'  => '',
		'home_page_twitter_image_url'     => '',
		'home_page_twitter_title'         => '',
		'home_page_twitter_description'   => '',
		'home_page_robots'                => [
			'general' => [],
		],
		'index_home_page_paginated_pages' => true,
	];

	/**
	 * Default values for the global - Social.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_social_defaults = [
		'open_graph_tags'             => true,
		'facebook_meta_tags'          => true,
		'twitter_meta_tags'           => true,
		'oembeded_scripts'            => true,
		'oembeded_og_title'           => true,
		'oembeded_social_images'      => true,
		'oembeded_remove_author_name' => true,
		'facebook_page_url'           => '',
		'facebook_author_fallback'    => '',
		'twitter_card_type'           => 'summary_large_image',
		'twitter_same_as_facebook'    => true,
		'twitter_profile_username'    => '',
		'twitter_profile_fallback'    => '',
	];

	/**
	 * Default values for the global - Special Pages.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_special_pages_defaults = [
		'author_archive'               => false,
		'date_archive'                 => false,
		'noindex_paginated_pages'      => false,
		'paginated_link_relationships' => [ 'homepage', 'pages', 'archives' ],
	];

	/**
	 * Default values for the global - Advanced - Feeds.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_advanced_feeds_default = [
		'addlink_to_source_below_feed_entries' => true,
		'remove_global_comments_feed'          => false,
		'remove_post_authors_feed'             => false,
		'remove_post_types_feed'               => false,
		'remove_category_feed'                 => false,
		'remove_tag_feeds'                     => false,
		'remove_custom_taxonomy_feeds'         => false,
		'remove_search_results_feed'           => false,
		'remove_atom_rdf_feeds'                => false,
	];

	/**
	 * Default values for the global - Advanced - Sitemaps.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_advanced_sitemaps_default = [
		'enable_xml_sitemap'       => true,
		'enable_xml_image_sitemap' => true,
	];

	/**
	 * Default values for the global - Advanced - Images.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_advanced_images_defaults = [
		'redirect_attachment_pages_to_post_parent' => true,
		'auto_set_image_title'                     => true,
		'auto_set_image_alt'                       => true,
	];

	/**
	 * Default values for the global - Advanced - Miscellaneous.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_advanced_miscellaneous_defaults = [
		'surerank_analytics_optin' => false,
	];

	/**
	 * Default values for the global - Advanced - Robots.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $global_advanced_robots_default = [
		'no_index'   => [
			'post_format',
			'attachment',
			'author',
			'date',
			'search',
		],
		'no_follow'  => [],
		'no_archive' => [],
	];

	/**
	 * Default values for the Post/CPT.
	 *
	 * @var array<string, mixed>
	 * @since 1.0.0
	 */
	private $post_defaults = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->post_defaults = [
			'general'         => $this->get_general_defaults(),
			'post_no_index'   => '',
			'post_no_follow'  => '',
			'post_no_archive' => '',
			'social'          => array_merge(
				$this->get_social_defaults(),
				[
					// Facebook.
					'facebook_title'           => '',
					'facebook_description'     => '',
					'facebook_image_url'       => '',
					'facebook_image_id'        => 0,
					'facebook_image_width'     => 0,
					'facebook_image_height'    => 0,
					// (X) Twitter.
					'twitter_title'            => '',
					'twitter_description'      => '',
					'twitter_image_url'        => '',
					'twitter_image_id'         => 0,
					'twitter_card_type'        => 'summary_large_image',
					'twitter_same_as_facebook' => true,
					'twitter_profile_username' => '',
					'twitter_profile_fallback' => '',
				]
			),
			'schemas'         => [
				'schemas' => Utils::get_default_schemas(),
			],
		];
	}

	/**
	 * Default values for the global.
	 *
	 * @param string $key Key.
	 * @return mixed
	 * @since 1.0.0
	 */
	public function get_global_defaults( $key = '' ) {

		$social_profiles_array = [];
		foreach ( Onboarding::social_profiles() as $profile ) {
			$social_profiles_array[ $profile['id'] ] = '';
		}

		$data_settings = apply_filters(
			'surerank_global_defaults',
			array_merge(
				// General Settings.
				$this->get_general_defaults(),
				$this->get_homepage_defaults(),
				$this->get_social_defaults(),
				$this->feature_management_defaults(),
				$this->get_special_pages_defaults(),
				$this->get_feeds_defaults(),
				$this->get_sitemap_defaults(),
				$this->get_robots_defaults(),
				$this->get_images_defaults(),
				$this->get_advanced_miscellaneous_defaults(),
				[
					'schemas' => Utils::get_default_schemas(),
				],
				[
					'social_profiles' => $social_profiles_array,
				]
			)
		);

		if ( ! empty( $key ) && isset( $data_settings[ $key ] ) ) {
			return $data_settings[ $key ];
		}

		return $data_settings;
	}

	/**
	 * Default values for the Post/CPT.
	 *
	 * @param bool $flat Flat.
	 * @return array<string, mixed>
	 * @since 1.0.0
	 */
	public function get_post_defaults( $flat = true ) {
		$all_settings = [];

		if ( ! $flat ) {
			return apply_filters(
				'surerank_post_defaults',
				$this->post_defaults,
			);
		}

		foreach ( $this->post_defaults as $option_value ) {

			if ( ! empty( $option_value ) ) {
				$all_settings = array_merge( $all_settings, $option_value );
			}
		}

		return apply_filters(
			'surerank_post_defaults',
			$all_settings,
		);
	}

	/**
	 * Values for the Post/CPT meta keys.
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get_post_meta_keys() {
		if ( empty( $this->post_defaults ) ) {
			return [];
		}
		return array_keys( $this->post_defaults );
	}

	/**
	 * Default values for the onboarding.
	 *
	 * @return array<string, mixed>
	 * @since 1.0.0
	 */
	public function get_onboarding_defaults() {
		return apply_filters(
			'surerank_onboarding_defaults',
			[
				'first_name'          => '',
				'last_name'           => '',
				'email'               => '',
				'subscribe'           => false,
				'agree_to_terms'      => false,
				'website_type'        => [],
				'website_name'        => '',
				'website_owner_name'  => '',
				'website_owner_phone' => '',
				'website_logo'        => [],
				'about_page'          => [],
				'contact_page'        => [],
				'social_profiles'     => [
					'facebook'  => '',
					'twitter'   => '',
					'instagram' => '',
					'youtube'   => '',
					'linkedin'  => '',
					'tiktok'    => '',
					'pinterest' => '',
					'reddit'    => '',
					'snapchat'  => '',
					'twitch'    => '',
					'whatsapp'  => '',
					'telegram'  => '',
					'vimeo'     => '',
					'yelp'      => '',
				],
			],
		);
	}

	/**
	 * Get general defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_general_defaults() {
		return apply_filters( 'surerank_general_defaults', $this->global_general_defaults );
	}

	/**
	 * Get homepage defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_homepage_defaults() {
		return apply_filters( 'surerank_homepage_defaults', $this->global_homepage_defaults );
	}

	/**
	 * Get social defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_social_defaults() {
		return apply_filters( 'surerank_social_defaults', $this->global_social_defaults );
	}

	/**
	 * Get feature management defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function feature_management_defaults() {
		return apply_filters( 'surerank_feature_management_defaults', $this->feature_management_defaults );
	}

	/**
	 * Get special pages defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_special_pages_defaults() {
		return apply_filters( 'surerank_special_pages_defaults', $this->global_special_pages_defaults );
	}

	/**
	 * Get feeds defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_feeds_defaults() {
		return apply_filters( 'surerank_feeds_defaults', $this->global_advanced_feeds_default );
	}

	/**
	 * Get sitemap defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_sitemap_defaults() {
		return apply_filters( 'surerank_sitemap_defaults', $this->global_advanced_sitemaps_default );
	}

	/**
	 * Get robots defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_robots_defaults() {
		return apply_filters( 'surerank_robots_defaults', $this->global_advanced_robots_default );
	}

	/**
	 * Get images defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_images_defaults() {
		return apply_filters( 'surerank_images_defaults', $this->global_advanced_images_defaults );
	}

	/**
	 * Get advanced miscellaneous defaults with filter.
	 *
	 * @return array<string, mixed>
	 * @since 1.2.0
	 */
	private function get_advanced_miscellaneous_defaults() {
		return apply_filters( 'surerank_advanced_miscellaneous_defaults', $this->global_advanced_miscellaneous_defaults );
	}
}
