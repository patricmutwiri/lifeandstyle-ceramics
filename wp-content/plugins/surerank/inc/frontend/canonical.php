<?php
/**
 * Canonical Meta Data
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

use SureRank\Inc\Traits\Get_Instance;
use WP_Term;

/**
 * Canonical URL
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Canonical {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'index_rel_link' );
		add_action( 'surerank_print_meta', [ $this, 'print_canonical_url' ], 1, 1 );
	}

	/**
	 * Add meta data
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @since 1.0.0
	 * @return void
	 */
	public function print_canonical_url( $meta_data ) {
		$url = '';
		if ( is_singular() ) {
			$url = $meta_data['canonical_url'] ?? get_the_permalink();
		}

		if ( is_archive() ) {
			$taxonomy = get_queried_object();
			if ( $taxonomy instanceof \WP_Term ) {
				$url = get_term_link( $taxonomy );
			}
		}

		if ( is_home() || is_front_page() ) {
			$url = $meta_data['canonical_url'] ?? get_home_url();
		}

		if ( ! is_404() ) {
			if ( is_search() ) {
				$url = get_search_link();
			} elseif ( is_paged() && is_singular() ) {
				$url = $meta_data['canonical_url'] ?? get_permalink();
			} elseif ( is_paged() ) {
				$url = $meta_data['canonical_url'] ?? get_pagenum_link( get_query_var( 'paged' ) );
			} elseif ( is_tax() || is_category() || is_tag() ) {
				$queried_object = get_queried_object();
				$url            = isset( $meta_data['canonical_url'] ) && ! empty( $meta_data['canonical_url'] )
					? $meta_data['canonical_url']
					: ( $queried_object instanceof WP_Term ? get_term_link( $queried_object ) : '' );
			} else {
				global $wp;
				$url = $meta_data['canonical_url'] ?? user_trailingslashit( home_url( add_query_arg( [], $wp->request ) ) );
			}
		}

		$this->print_canonical( $url );
	}

	/**
	 * Print the canonical url.
	 *
	 * @param string $url Canonical URL.
	 * @return void
	 * @since 1.0.0
	 */
	public function print_canonical( $url ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . PHP_EOL;
	}

}
