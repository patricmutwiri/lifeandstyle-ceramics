<?php
/**
 * Archives Meta Data
 *
 * This file will handle functionality to print meta data for archives in frontend.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Validate;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Archives Meta Data
 * This class will handle functionality to print meta data for archives in frontend.
 *
 * @since 1.0.0
 */
class Archives {

	use Get_Instance;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'redirect_to_home' ], 1, 1 );
		add_action( 'surerank_print_meta', [ $this, 'paginated_link_relationships' ], 1, 1 );
		add_filter( 'surerank_robots_meta_array', [ $this, 'noindex_paginated_pages' ], 1, 1 );
		add_filter( 'surerank_is_singular_archive', [ $this, 'set_singular_archive' ], 10, 2 );
	}

	/**
	 * Determine if the current page is a singular archive using the filter.
	 *
	 * @param mixed $post Optional. Post object or ID. Defaults to null for the current post.
	 *
	 * @return bool True if it's a singular archive, false otherwise.
	 */
	public static function is_singular_archive( $post = null ) {
		$id = $post ? ( is_int( $post ) ? $post : ( get_post( $post )->ID ?? 0 ) ) : null;
		// Apply filter to determine singular archive status.
		return (bool) apply_filters(
			'surerank_is_singular_archive',
			static::is_static_front_page_as_blog( $id ),
			$id
		);
	}

	/**
	 * Set Singular Archive using the filter.
	 *
	 * @param bool     $is_singular_archive Current singular archive state.
	 * @param int|null $id                  The ID of the post or page being checked.
	 *
	 * @return bool Updated singular archive state.
	 */
	public function set_singular_archive( $is_singular_archive, $id ) {
		$shop_page_id = $this->get_shop_page_id();
		return $is_singular_archive || ( $shop_page_id && $this->is_shop_page( $id ) );
	}

	/**
	 * Check if the front page is configured to display blog posts.
	 *
	 * @param int|null $id Optional. Post ID to check. Defaults to null.
	 *
	 * @return bool True if the front page is set as the blog, false otherwise.
	 */
	public static function is_static_front_page_as_blog( $id ) {

		if ( self::check_front_page_condition() && \is_home() ) {
			return true;
		}

		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return false;
		}

		$posts_page_id = get_option( 'page_for_posts' );
		return $posts_page_id && ( $id ? $posts_page_id === $id : is_page( $posts_page_id ) );
	}

	/**
	 * Paginated Link Relationships.
	 *
	 * Helps search engines locate the correct page in paginated archives.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function paginated_link_relationships() {
		if ( ! ( is_paged() || is_archive() || is_home() ) ) {
			return;
		}

		$settings = Settings::get();
		if ( empty( $settings['paginated_link_relationships'] ) ) {
			return;
		}
		$single_archive        = $this->is_singular_archive();
		$screen_types          = [
			'homepage' => is_home() && ! $single_archive,
			'archives' => is_archive() && ! $single_archive,
			'pages'    => $single_archive,
		];
		$relationship_settings = Validate::array( $settings['paginated_link_relationships'], array_keys( $screen_types ) );

		foreach ( $screen_types as $type => $is_type ) {
			if ( $is_type && in_array( $type, $relationship_settings ) ) {
				$this->print_rel_links();
				break;
			}
		}
	}

	/**
	 * Set Noindex for paginated archive pages beyond the first.
	 *
	 * @param array<string, mixed>|array<int, array<string, mixed>> $robots_meta Robots meta array.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public function noindex_paginated_pages( $robots_meta ) {
		if ( ! is_paged() ) {
			return $robots_meta;
		}

		$settings = Settings::get();
		if ( ! empty( $settings['noindex_paginated_pages'] ) || apply_filters( 'surerank_noindex_paginated_pages', false ) ) {
			$robots_meta = array_map(
				static function ( $value ) {
					return 'index' === $value ? 'noindex' : $value;
				},
				$robots_meta
			);
		}
		return $robots_meta;
	}

	/**
	 * Redirect to home if archive or search is disabled.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function redirect_to_home() {
		if ( ! is_archive() && ! is_search() ) {
			return;
		}

		$settings      = Settings::get();
		$archive_types = [
			'author' => 'author_archive',
			'date'   => 'date_archive',
		];

		foreach ( $archive_types as $type => $setting_key ) {
			if ( call_user_func( "is_{$type}" ) && empty( $settings[ $setting_key ] ) ) {
				wp_safe_redirect( home_url(), 301 );
				exit;
			}
		}
	}

	/**
	 * Check if the current front page is not a static page or if there is a page for posts.
	 *
	 * @return bool True if the condition is met, false otherwise.
	 */
	public static function check_front_page_condition() {
		$show_on_front  = \get_option( 'show_on_front' );
		$page_for_posts = \get_option( 'page_for_posts' );

		return 'page' === $show_on_front || $page_for_posts;
	}

	/**
	 * Check if the given page ID is the WooCommerce shop page.
	 *
	 * @param int|null $id Post ID to check.
	 *
	 * @return bool True if it's the WooCommerce shop page, false otherwise.
	 */
	private function is_shop_page( $id ) {
		return function_exists( 'is_shop' ) && is_shop();
	}

	/**
	 * Get the ID of the WooCommerce shop page.
	 *
	 * @return int|null Shop page ID, or null if WooCommerce is not active.
	 */
	private function get_shop_page_id() {
		return function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : null;
	}

	/**
	 * Print rel links for next and previous pages.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function print_rel_links() {
		global $paged;
		if ( get_next_posts_link() ) {
			echo '<link rel="next" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '">' . PHP_EOL;
		}
		if ( get_previous_posts_link() ) {
			echo '<link rel="prev" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '">' . PHP_EOL;
		}
	}
}
