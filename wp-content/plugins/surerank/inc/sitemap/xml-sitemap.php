<?php
/**
 * Common Meta Data
 *
 * This file handles functionality to generate sitemap in frontend.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Cache;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

/**
 * XML Sitemap
 * Handles functionality to generate XML sitemaps.
 *
 * @since 1.0.0
 */
class Xml_Sitemap extends Sitemap {

	use Get_Instance;
	/**
	 * Sitemap slug to be used across the class.
	 *
	 * @var string
	 */
	private static $sitemap_slug = 'sitemap_index';

	/**
	 * Constructor
	 *
	 * Sets up the sitemap functionality if XML sitemaps are enabled in settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		add_filter(
			'surerank_flush_rewrite_settings',
			[ $this, 'flush_settings' ],
			10,
			1
		);

		if ( ! Settings::get( 'enable_xml_sitemap' ) ) {
			return;
		}

		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'parse_query', [ $this, 'parse_query' ], 1 );
	}

	/**
	 * Array of settings to flush rewrite rules on update settings
	 *
	 * @param array<string, mixed> $settings Existing settings to flush.
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function flush_settings( $settings ) {
		$settings[] = 'enable_xml_sitemap';
		$settings[] = 'enable_xml_image_sitemap';
		return $settings;
	}

	/**
	 * Returns the sitemap slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_slug(): string {
		$sitemap_slug = apply_filters( 'surerank_sitemap_slug', self::$sitemap_slug );
		$sitemap_slug = empty( $sitemap_slug ) ? self::$sitemap_slug : $sitemap_slug;
		return $sitemap_slug . '.xml';
	}

	/**
	 * Redirects default WordPress sitemap requests to custom sitemap URLs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function template_redirect() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$current_url = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$current_url = explode( '/', $current_url );
		$last_url    = end( $current_url );

		$sitemap = [
			'sitemap.xml',
			'wp-sitemap.xml',
			'index.xml',
		];

		if ( in_array( $last_url, $sitemap, true ) ) {
			wp_safe_redirect( '/' . self::get_slug(), 301 );
			exit;
		}
	}

	/**
	 * Parses custom query variables and triggers sitemap generation.
	 *
	 * @param \WP_Query $query Current query object.
	 * @since 1.0.0
	 * @return void
	 */
	public function parse_query( \WP_Query $query ) {
		if ( ! $query->is_main_query() && ! is_admin() ) {
			return;
		}

		$type  = sanitize_text_field( get_query_var( 'surerank_sitemap' ) );
		$style = sanitize_text_field( get_query_var( 'surerank_sitemap_type' ) );

		if ( ! $type && ! $style ) {
			return;
		}

		if ( $style ) {
			Utils::output_stylesheet( $style );
		}

		$page      = absint( get_query_var( 'surerank_sitemap_page' ) ) ? absint( get_query_var( 'surerank_sitemap_page' ) ) : 1;
		$threshold = apply_filters( 'surerank_sitemap_threshold', 200 );

		do_action( 'surerank_sitemap_before_generation', $type, $page, $threshold );

		$this->generate_sitemap( $type, $page, $threshold );

		do_action( 'surerank_sitemap_after_generation', $type, $page, $threshold );
	}

	/**
	 * Generates the appropriate sitemap based on the requested type.
	 *
	 * @param string $type Sitemap type requested.
	 * @param int    $page Current page number for paginated sitemaps.
	 * @param int    $threshold Threshold for splitting sitemaps.
	 * @since 1.0.0
	 * @return void
	 */
	public function generate_sitemap( string $type, int $page, $threshold ) {

		$sitemap = [];

		if ( '1' === $type ) {
			$sitemap_index = Cache::get_file( 'sitemap/sitemap_index.json' );
			if ( $sitemap_index ) {
				$sitemap = json_decode( $sitemap_index, true );
				$this->sitemapindex( $sitemap );
			}
		}

		$this->generate_main_sitemap( $type, $page, $threshold );
	}

	/**
	 * Generates the main sitemap for a specific type, page, and offset.
	 *
	 * @param string $type Post type or taxonomy.
	 * @param int    $page Current page number.
	 * @param int    $offset Number of posts to retrieve.
	 * @since 1.0.0
	 * @return void
	 */
	public function generate_main_sitemap( string $type, int $page, int $offset = 1000 ) {
		remove_all_actions( 'parse_query' );
		$sitemap = [];

		$prefix_param = sanitize_text_field( get_query_var( 'surerank_prefix' ) );
		if ( Cache::file_exists( 'sitemap/sitemap_index.json' ) ) {
			$sitemap = $this->get_sitemap_from_cache( $type, $page, $prefix_param );
			$this->generate_main_sitemap_xml( $sitemap );
		}
	}

	/**
	 * Outputs the sitemap index as XML.
	 *
	 * @param array<string, mixed>|array<int, string> $sitemap Sitemap index data.
	 * @since 1.0.0
	 * @return void
	 */
	public function sitemapindex( array $sitemap ) {
		echo Utils::sitemap_index( $sitemap ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML is safely generated in sitemap_index
		exit;
	}

	/**
	 * Outputs the main sitemap as XML.
	 *
	 * @param array<string, mixed>|array<int, string> $sitemap Sitemap data for main sitemap.
	 * @since 1.0.0
	 * @return void
	 */
	public function generate_main_sitemap_xml( array $sitemap ) {
		echo Utils::sitemap_main( $sitemap ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML is safely generated in sitemap_main
		exit;
	}

	/**
	 * Get sitemap url
	 *
	 * @return string
	 */
	public function get_sitemap_url() {
		return home_url( self::get_slug() );
	}

	/**
	 * Get sitemap from cache
	 *
	 * @param string $type Sitemap type.
	 * @param int    $page Page number.
	 * @param string $prefix_param Prefix name.
	 * @return array<string, mixed>|array<int, string>
	 */
	private function get_sitemap_from_cache( string $type, int $page, string $prefix_param ) {
		// Calculate which chunks belong to this page based on threshold and chunk size.
		$sitemap_threshold = apply_filters( 'surerank_sitemap_threshold', 200 );
		$chunk_size        = apply_filters( 'surerank_sitemap_json_chunk_size', 20 );

		$chunks_per_sitemap = (int) ceil( $sitemap_threshold / $chunk_size );
		$start_chunk        = ( $page - 1 ) * $chunks_per_sitemap + 1;
		$end_chunk          = $page * $chunks_per_sitemap;

		$combined_sitemap = [];
		for ( $chunk_number = $start_chunk; $chunk_number <= $end_chunk; $chunk_number++ ) {
			$chunk_file      = $prefix_param . '-' . $type . '-chunk-' . $chunk_number . '.json';
			$cache_file_data = Cache::get_file( 'sitemap/' . $chunk_file );

			if ( ! $cache_file_data ) {
				continue;
			}

			$chunk_data = json_decode( $cache_file_data, true );
			if ( is_array( $chunk_data ) ) {
				$combined_sitemap = array_merge( $combined_sitemap, $chunk_data );
			}
		}

		return $combined_sitemap;
	}
}
