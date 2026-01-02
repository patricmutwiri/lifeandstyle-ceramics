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
use SureRank\Inc\Sitemap\Xml_Sitemap;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Home Page SEO
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Robots {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'surerank_print_meta', [ $this, 'print_meta' ], 1 );
		remove_all_filters( 'wp_robots' );
		add_filter( 'robots_txt', [ $this, 'generate_custom_robots_txt' ], 10, 2 ); //phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.robots_txt
	}

	/**
	 * Get existing robots.txt content
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_default_robots_txt() {
		$public   = absint( get_option( 'blog_public' ) );
		$default  = __( '# SureRank will generate robots.txt automatically.', 'surerank' ) . "\n";
		$default .= "User-Agent: *\n";
		$default .= 0 === $public ? "Disallow: /wp-admin/\n Allow: /wp-admin/admin-ajax.php\n" : "Disallow: /wp-admin/\n";

		return apply_filters( 'robots_txt', $default, $public );
	}

	/**
	 * Add meta data
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $robots_meta meta data.
	 * @return void
	 */
	public function print_meta( $robots_meta ) {

		$noindex   = $robots_meta['post_no_index'] ?? false;
		$nofollow  = $robots_meta['post_no_follow'] ?? false;
		$noarchive = $robots_meta['post_no_archive'] ?? false;

		/**
		 * Example of robots meta array:
		 * [ "general" => [ "noindex", "nofollow", "noarchive" ] ]
		 */
		$robots_meta_keys  = [ 'noindex', 'nofollow', 'noarchive' ];
		$robots_meta_array = [];

		// If robots_meta general exists, use it.
		if ( ! empty( $noindex ) || ! empty( $nofollow ) || ! empty( $noarchive ) ) {
			if ( $noindex === 'yes' ) {
				$robots_meta_array[] = 'noindex';
			}

			if ( $nofollow === 'yes' ) {
				$robots_meta_array[] = 'nofollow';
			}

			if ( $noarchive === 'yes' ) {
				$robots_meta_array[] = 'noarchive';
			}
		} else {
			// Otherwise, fallback to Settings-based values.
			if ( $this->is_specified_page_type( Settings::get( 'no_index' ) ) ) {
				$robots_meta_array[] = 'noindex';
			}

			if ( $this->is_specified_page_type( Settings::get( 'no_follow' ) ) ) {
				$robots_meta_array[] = 'nofollow';
			}

			if ( $this->is_specified_page_type( Settings::get( 'no_archive' ) ) ) {
				$robots_meta_array[] = 'noarchive';
			}
		}

		// Default to 'index' and 'follow' if not already set.
		if ( ! in_array( 'noindex', $robots_meta_array, true ) ) {
			$robots_meta_array[] = 'index';
		}

		if ( ! in_array( 'nofollow', $robots_meta_array, true ) ) {
			$robots_meta_array[] = 'follow';
		}

		if ( ! Get::option( 'blog_public' ) ) {
			$robots_meta_array   = array_diff( $robots_meta_array, [ 'index', 'follow' ] );
			$robots_meta_array[] = 'noindex';
			$robots_meta_array[] = 'nofollow';
		}

		// Prepare the final meta tag value.
		$robots_meta_value = implode( ', ', apply_filters( 'surerank_robots_meta_array', array_unique( $robots_meta_array ) ) );

		// Call the meta_html_template method with the prepared value.
		Meta_Data::get_instance()->meta_html_template( 'robots', $robots_meta_value );
	}

	/**
	 * Generates a custom robots.txt content with an updated Sitemap directive.
	 *
	 * @since 0.0.1
	 * @since 1.2.0
	 * @param string $output The current robots.txt output.
	 * @param bool   $public Whether the site is public and should be indexed.
	 * @return string Updated robots.txt content with the Sitemap directive added or modified.
	 */
	public function generate_custom_robots_txt( $output, $public ) {

		if ( ! $public ) {
			return $output;
		}

		$custom_content = Get::option( SURERANK_ROBOTS_TXT_CONTENT, '' );

		if ( is_admin() ) {
			return $this->add_sitemap_directive( $output );
		}

		if ( ! empty( $custom_content ) ) {
			return $custom_content;
		}

		if ( ! empty( Settings::get( 'enable_xml_sitemap' ) ) ) {
			return $this->add_sitemap_directive( $output );
		}
		return $output;
	}

	/**
	 * Check of specified page type exists
	 *
	 * @since 1.0.0
	 * @param array<int, string> $types page types.
	 * @return bool
	 */
	public function is_specified_page_type( $types = [] ): bool {
		foreach ( $types as $type ) {
			if ( $this->is_empty_taxonomy() ) {
				return true;
			}

			if ( $this->matches_page_type( $type ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add sitemap directive to robots.txt
	 *
	 * @since 1.5.0
	 * @param string $output The current robots.txt output.
	 * @return string Updated robots.txt content with the Sitemap directive added or modified.
	 */
	private function add_sitemap_directive( $output ) {
		if ( ! empty( Settings::get( 'enable_xml_sitemap' ) ) ) {
			$sitemap_url       = home_url( Xml_Sitemap::get_slug() );
			$sitemap_directive = "Sitemap: {$sitemap_url}" . PHP_EOL;

			if ( preg_match( '/^sitemap:\s.*$/im', $output ) ) {
				$output = preg_replace( '/^sitemap:\s.*$/im', $sitemap_directive, $output );
			} else {
				$output .= PHP_EOL . $sitemap_directive;
			}
		}
		return (string) $output;
	}

	/**
	 * Check if current page is an empty taxonomy archive.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_empty_taxonomy() {
		if ( ! is_archive() ) {
			return false;
		}

		$taxonomy = get_queried_object();
		if ( ! $taxonomy instanceof \WP_Term ) {
			return false;
		}

		return $taxonomy->count === 0;
	}

	/**
	 * Check if the current page matches the specified type.
	 *
	 * @since 1.0.0
	 * @param string $type The page type to check.
	 * @return bool
	 */
	private function matches_page_type( string $type ) {
		$type_checks = [
			'post_tag' => 'is_tag',
			'author'   => 'is_author',
			'date'     => 'is_date',
			'category' => 'is_category',
			'search'   => 'is_search',
			'archive'  => 'is_archive',
		];

		// Check standard page types.
		if ( isset( $type_checks[ $type ] ) && call_user_func( $type_checks[ $type ] ) ) {
			return true;
		}

		// Check post format.
		if ( 'post_format' === $type && is_tax( 'post_format' ) ) {
			return true;
		}

		// Check custom post types.
		if ( post_type_exists( $type ) && is_singular( $type ) ) {
			return true;
		}

		// Check custom taxonomies.
		if ( taxonomy_exists( $type ) && is_tax( $type ) ) {
			return true;
		}

		return false;
	}

}
