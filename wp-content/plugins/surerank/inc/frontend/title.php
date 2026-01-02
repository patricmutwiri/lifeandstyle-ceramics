<?php
/**
 * Title Meta Data
 *
 * Handles functionality to print titles in the frontend for different requests.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Traits\Get_Instance;
use WP_Term;

/**
 * Title
 * Manages title generation for various WordPress frontend requests.
 *
 * @since 0.0.1
 */
class Title {

	use Get_Instance;

	/**
	 * Post meta data.
	 *
	 * @var array<string, mixed>|null
	 * @since 0.0.1
	 */
	private $meta_data = null;

	/**
	 * Default separator for title parts.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	private $default_separator = ' - ';

	/**
	 * Cached site name.
	 *
	 * @var string|null
	 * @since 0.0.1
	 */
	private $site_name = null;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		// Bail early if automatic titles are disabled.
		if ( apply_filters( 'surerank_disable_automatic_titles', false ) ) {
			return;
		}

		add_filter( 'surerank_set_meta', [ $this, 'get_meta_data' ], 10, 1 );
		add_filter( 'pre_get_document_title', [ $this, 'replace_title' ], 20, 1 );
		add_filter( 'wp_title', [ $this, 'replace_title' ], 20, 1 );
	}

	/**
	 * Store meta data.
	 *
	 * @param array<string, mixed> $meta_data Meta data to store.
	 * @return array<string, mixed> Modified meta data.
	 * @since 0.0.1
	 */
	public function get_meta_data( $meta_data ) {
		$this->meta_data = $meta_data;
		return $meta_data; // Return to maintain filter chain.
	}

	/**
	 * Replace title based on request type.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function replace_title( $title ) {
		$meta_title = $this->meta_data['page_title'] ?? $title;

		if ( is_post_type_archive() && apply_filters( 'surerank_enable_post_type_archive_auto_title', false ) ) {
			$title = $this->post_type_archive_title( $meta_title );
		} elseif ( is_archive() && apply_filters( 'surerank_enable_archive_auto_title', false ) ) {
			$title = $this->archive_title( $meta_title );
		} elseif ( is_search() && apply_filters( 'surerank_enable_search_auto_title', true ) ) {
			$title = $this->search_title( $meta_title );
		} elseif ( is_404() && apply_filters( 'surerank_enable_404_auto_title', true ) ) {
			$title = $this->not_found_title( $meta_title );
		} elseif ( is_singular() || is_home() || is_front_page() ) {
			$title = $meta_title;
			$title = $this->add_pagination_title( $title );
		}

		// Apply global title filter.
		return apply_filters( 'surerank_final_title', $title, $meta_title );
	}

	/**
	 * Generate post type archive title.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function post_type_archive_title( $title ) {
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$title = get_the_title( get_option( 'woocommerce_shop_page_id', '' ) );
		} else {
			$object = get_queried_object();
			$title  = $object && isset( $object->label ) ? __( 'Archives: ', 'surerank' ) . $object->label : $title;
		}

		$title = $this->add_pagination_title( $title );
		$title = $this->add_site_name( $title );

		return apply_filters( 'surerank_post_type_archive_title', $title );
	}

	/**
	 * Generate archive title.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function archive_title( $title ) {
		if ( is_category() ) {
			$title = __( 'Category: ', 'surerank' ) . $title;
		} elseif ( is_tag() ) {
			$title = __( 'Tag: ', 'surerank' ) . $title;
		} elseif ( is_author() ) {
			if ( empty( $title ) ) {
				// Use default format.
				$title = __( 'Author: ', 'surerank' ) . get_the_author();
			}
			if ( empty( $this->meta_data['page_title'] ) ) {
				$title = $this->add_site_name( $title );
			}
		} elseif ( is_date() ) {
			if ( empty( $title ) ) {
				// Use default format.
				if ( is_day() ) {
					$title = __( 'Day Archives: ', 'surerank' ) . get_the_date();
				} elseif ( is_month() ) {
					$title = __( 'Month Archives: ', 'surerank' ) . get_the_date( 'F Y' );
				} elseif ( is_year() ) {
					$title = __( 'Year Archives: ', 'surerank' ) . get_the_date( 'Y' );
				}
			}
			if ( empty( $this->meta_data['page_title'] ) ) {
				$title = $this->add_site_name( $title );
			}
		} elseif ( is_tax() ) {
			$term = \get_queried_object();
			if ( $term instanceof WP_Term ) {
				$title = $this->get_taxonomy_title( $term );
			}
			$title = $this->add_site_name( $title );
		}

		return apply_filters( 'surerank_archive_title', $title );
	}

	/**
	 * Generate search title.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function search_title( $title ) {
		$search_query = get_search_query();
		/* translators: %s: Search query */
		$title = sprintf( __( 'Search Results for: %s', 'surerank' ), $search_query ? $search_query : __( 'No query', 'surerank' ) );
		$title = $this->add_pagination_title( $title );
		$title = $this->add_site_name( $title );

		return apply_filters( 'surerank_search_title', $title, $search_query );
	}

	/**
	 * Generate 404 title.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function not_found_title( $title ) {
		$title = __( '404: Page not found', 'surerank' );
		$title = $this->add_site_name( $title );

		return apply_filters( 'surerank_not_found_title', $title );
	}

	/**
	 * Add site name to title.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function add_site_name( $title ) {
		if ( empty( $title ) || ! apply_filters( 'surerank_show_site_name', true ) ) {
			return $title;
		}

		if ( $this->site_name === null ) {
			$this->site_name = get_bloginfo( 'name', 'display' );
		}

		$separator = apply_filters( 'surerank_title_separator', $this->default_separator );
		return $title . $separator . $this->site_name;
	}

	/**
	 * Add pagination to title.
	 *
	 * @param string $title Original title.
	 * @return string Modified title.
	 * @since 0.0.1
	 */
	public function add_pagination_title( $title ) {
		if ( empty( $title ) || ! apply_filters( 'surerank_show_pagination', true ) || ! is_paged() ) {
			return $title;
		}

		$paged_info = Helper::get_paged_info();
		return $title . $paged_info;
	}

	/**
	 * Get taxonomy title.
	 *
	 * @param WP_Term $term Term object.
	 * @return string Taxonomy title.
	 * @since 0.0.1
	 */
	public function get_taxonomy_title( $term ) {
		$taxonomy   = get_taxonomy( $term->taxonomy );
		$tax_label  = $taxonomy->label ?? '';
		$term_title = $term->name;
		return $tax_label . ': ' . $term_title;
	}
}
