<?php
/**
 * Site Meta Variables
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Meta_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

/**
 * This class deals with variables related to the site.
 *
 * @since 0.0.1
 */
class Site extends Variables {

	use Get_Instance;

	/**
	 * Stores variables array.
	 *
	 * @var array<string, mixed>
	 * @since 0.0.1
	 */
	public $variables = [];

	/**
	 * Category of variables.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	public $category = 'site';

	/**
	 * Organization details.
	 *
	 * @var array<string, mixed>|null
	 * @since 1.0.0
	 */
	public $org_details = null;
	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		$this->variables = [
			'separator'    => [
				'label'       => __( 'Separator', 'surerank' ),
				'description' => __( 'Separator', 'surerank' ),
			],
			'tagline'      => [
				'label'       => __( 'Site Tagline', 'surerank' ),
				'description' => __( 'The tagline of the site.', 'surerank' ),
			],
			'site_name'    => [
				'label'       => __( 'Site Name', 'surerank' ),
				'description' => __( 'The blog info of the site.', 'surerank' ),
			],
			'site_url'     => [
				'label'       => __( 'Site Address', 'surerank' ),
				'description' => __( 'The URL of the Site', 'surerank' ),
			],
			'page'         => [
				'label'       => __( 'Page', 'surerank' ),
				'description' => __( 'The current page number.', 'surerank' ),
			],
			'search_query' => [
				'label'       => __( 'Search Query', 'surerank' ),
				'description' => __( 'Search query (only available on search results page)', 'surerank' ),
			],
			'currentdate'  => [
				'label'       => __( 'Current Date', 'surerank' ),
				'description' => __( 'Current server date', 'surerank' ),
			],
			'currentday'   => [
				'label'       => __( 'Current Day', 'surerank' ),
				'description' => __( 'Current server day', 'surerank' ),
			],
			'currentmonth' => [
				'label'       => __( 'Current Month', 'surerank' ),
				'description' => __( 'Current server month', 'surerank' ),
			],
			'currentyear'  => [
				'label'       => __( 'Current Year', 'surerank' ),
				'description' => __( 'Current server year', 'surerank' ),
			],
			'currenttime'  => [
				'label'       => __( 'Current Time', 'surerank' ),
				'description' => __( 'Current server time', 'surerank' ),
			],
			'org_name'     => [
				'label'       => __( 'Organization Name', 'surerank' ),
				'description' => __( 'The Organization Name added in Local SEO Settings.', 'surerank' ),
			],
			'org_logo'     => [
				'label'       => __( 'Organization Logo', 'surerank' ),
				'description' => __( 'Organization Logo added in Local SEO Settings.', 'surerank' ),
			],
			'org_url'      => [
				'label'       => __( 'Organization URL', 'surerank' ),
				'description' => __( 'Organization URL added in Local SEO Settings.', 'surerank' ),
			],
		];

		$this->org_details = get_option( 'surerank_settings_onboarding', [] );
	}

	/**
	 * Get separator
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_separator() {
		/**
		 * Get separator.
		 */
		$separator = Settings::get( 'separator' );
		if ( ! empty( $separator ) && is_string( $separator ) ) {
			return $separator;
		}

		return '-';
	}

	/**
	 * Get site description/tagline
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_tagline() {
		return get_bloginfo( 'description' );
	}

	/**
	 * Get site name
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_site_name() {
		$site_name = get_bloginfo( 'name' );

		if ( empty( $site_name ) ) {
			return '';
		}

		return html_entity_decode( $site_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Get site URL
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_site_url() {
		return get_bloginfo( 'url' );
	}

	/**
	 * Get current page number
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_page() {
		return (string) Helper::get_paged_info();
	}

	/**
	 * Get search query
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_search_query() {
		if ( is_search() ) {
			return get_search_query();
		}
		return '';
	}

	/**
	 * Get current date
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_currentdate() {
		return date_i18n( get_option( 'date_format' ) );
	}

	/**
	 * Get current day
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_currentday() {
		return date_i18n( 'j' );
	}

	/**
	 * Get current month
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_currentmonth() {
		return date_i18n( 'F' );
	}

	/**
	 * Get current year
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_currentyear() {
		return date_i18n( 'Y' );
	}

	/**
	 * Get current time
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_currenttime() {
		return date_i18n( get_option( 'time_format' ) );
	}

	/**
	 * Get Organization Name (from the Schema fields).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_org_name() {
		$fields = $this->get_org_schema_fields();

		if ( isset( $fields['website_name'] ) && ! empty( $fields['website_name'] ) ) {
			return (string) $fields['website_name'];
		}
		return '';
	}

	/**
	 * Get organization logo
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_org_logo() {
		$fields = $this->get_org_schema_fields();

		if ( isset( $fields['website_logo'] ) && ! empty( $fields['website_logo'] ) ) {
			return (string) $fields['website_logo'];
		}
		return '';
	}

	/**
	 * Get organization URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_org_url() {
		$fields = $this->get_org_schema_fields();

		if ( isset( $fields['website_url'] ) && ! empty( $fields['website_url'] ) ) {
			return (string) $fields['website_url'];
		}
		return '';
	}

	/**
	 * Find and return the raw 'fields' array for the Organization schema.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function get_org_schema_fields() {

		$settings = $this->org_details;

		$website_name = $settings['website_name'] ?? null;

		if ( $website_name === null ) {
			$website_name = get_bloginfo( 'name' );
		}

		$website_logo = $settings['website_logo']['url'] ?? null;

		if ( $website_logo === null ) {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			if ( $custom_logo_id ) {
				$website_logo = wp_get_attachment_image_src( $custom_logo_id, 'full' )[0] ?? null;
			}
		}

		$website_url = $settings['website_url'] ?? null;
		if ( $website_url === null ) {
			$website_url = get_bloginfo( 'url' );
		}

		return [
			'website_name' => $website_name,
			'website_logo' => $website_logo,
			'website_url'  => $website_url,
		];
	}

}
