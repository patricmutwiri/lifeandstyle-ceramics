<?php
/**
 * Meta Data
 *
 * This file will handle functionality to print meta_data in frontend for different requests.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Validate;
use SureRank\Inc\Functions\Variables;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Home Page SEO
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Special_Page {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'surerank_set_meta', [ $this, 'add_meta_data' ], 1, 1 );
	}

	/**
	 * Add meta data
	 *
	 * @param array<string, mixed> $meta Meta Data.
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function add_meta_data( $meta ) {
		if ( ! is_home() && ! is_front_page() ) {
			return $meta;
		}

		$meta = get_option( 'show_on_front' ) === 'page'
			? $this->get_meta_for_static_homepage( $meta )
			: self::get_meta_for_dynamic_homepage();

		return Get::fb_image_size( $meta );
	}

	/**
	 * Get the meta data for the homepage.
	 *
	 * @return array<string, mixed> Meta data for the homepage.
	 * @since 1.0.0
	 */
	public static function get_meta_for_dynamic_homepage() {

		$page_meta = Settings::get();
		$robots    = isset( $page_meta['home_page_robots'] ) && is_array( $page_meta['home_page_robots'] ) && isset( $page_meta['home_page_robots']['general'] )
		? $page_meta['home_page_robots']['general']
		: [];

		if ( ! isset( $page_meta['home_page_facebook_title'] ) || $page_meta['home_page_facebook_title'] === '' ) {
			$page_meta['home_page_facebook_title'] = $page_meta['home_page_title'] ?? '';
		}

		if ( ! isset( $page_meta['home_page_facebook_description'] ) || $page_meta['home_page_facebook_description'] === '' ) {
			$page_meta['home_page_facebook_description'] = $page_meta['home_page_description'] ?? '';
		}

		if ( ! isset( $page_meta['home_page_twitter_title'] ) || $page_meta['home_page_twitter_title'] === '' ) {
			$page_meta['home_page_twitter_title'] = $page_meta['home_page_title'] ?? '';
		}

		if ( ! isset( $page_meta['home_page_twitter_description'] ) || $page_meta['home_page_twitter_description'] === '' ) {
			$page_meta['home_page_twitter_description'] = $page_meta['home_page_description'] ?? '';
		}

		$page_meta = [
			'page_title'           => $page_meta['home_page_title'],
			'page_description'     => $page_meta['home_page_description'],
			'facebook_title'       => $page_meta['home_page_facebook_title'],
			'facebook_description' => $page_meta['home_page_facebook_description'],
			'facebook_image_url'   => $page_meta['home_page_facebook_image_url'] ?? $page_meta['fallback_image'] ?? '',
			'twitter_title'        => $page_meta['home_page_twitter_title'],
			'twitter_description'  => $page_meta['home_page_twitter_description'],
			'twitter_image_url'    => $page_meta['home_page_twitter_image_url'] ?? $page_meta['fallback_image'] ?? '',
			'post_no_index'        => in_array( 'noindex', $robots ) ? 'yes' : 'no',
			'post_no_follow'       => in_array( 'nofollow', $robots ) ? 'yes' : 'no',
			'post_no_archive'      => in_array( 'noarchive', $robots ) ? 'yes' : 'no',
			'canonical_url'        => self::get_canonical_url( $page_meta ),
		];

		return Variables::replace( $page_meta, (int) get_the_ID() );
	}

	/**
	 * Get the page id.
	 *
	 * @return int Page id.
	 * @since 1.3.0
	 */
	public static function get_page_id() {
		if ( is_home() && ! is_front_page() && Get::option( 'page_for_posts' ) ) {
			return Get::option( 'page_for_posts' );
		}

		return (int) get_the_ID();
	}

	/**
	 * Get the canonical url.
	 *
	 * @param array<string, mixed> $meta Meta data.
	 * @return string Canonical url.
	 * @since 1.3.0
	 */
	public static function get_canonical_url( $meta ) {

		if ( is_home() && is_front_page() ) {
			return get_home_url();
		}

		return $meta['canonical_url'] ?? '';
	}

	/**
	 * Get meta for static homepage.
	 *
	 * @param array<string, mixed> $meta Meta Data.
	 * @return array<string, mixed> Meta data for static homepage.
	 * @since 1.0.0
	 */
	private function get_meta_for_static_homepage( $meta ) {
		$auto_generated_og_image = $meta['auto_generated_og_image'] ?? '';
		$meta                    = [
			'page_title'           => $meta['page_title'] ?? '',
			'page_description'     => $meta['page_description'] ?? '',
			'facebook_title'       => $meta['facebook_title'] ?? '',
			'facebook_description' => $meta['facebook_description'] ?? '',
			'facebook_image_url'   => $meta['facebook_image_url'] ?? $auto_generated_og_image,
			'twitter_title'        => $meta['twitter_title'] ?? '',
			'twitter_description'  => $meta['twitter_description'] ?? '',
			'twitter_image_url'    => $meta['twitter_image_url'] ?? $auto_generated_og_image,
			'post_no_index'        => $meta['post_no_index'] ?? 'no',
			'post_no_follow'       => $meta['post_no_follow'] ?? 'no',
			'post_no_archive'      => $meta['post_no_archive'] ?? 'no',
			'canonical_url'        => self::get_canonical_url( $meta ),
		];

		$post_meta = Variables::replace( Validate::array( Settings::prep_post_meta( intval( self::get_page_id() ) ) ), intval( self::get_page_id() ) );
		return array_merge( $meta, $post_meta );
	}
}
