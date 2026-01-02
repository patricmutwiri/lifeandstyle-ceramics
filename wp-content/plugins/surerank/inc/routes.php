<?php
/**
 * Custom Sitemap Routes
 *
 * This file manages all the rewrite rules and query variable handling
 * for custom sitemap functionality in SureRank.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Sitemap\Xml_Sitemap;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Custom Sitemap Routes
 *
 * This class manages all the rewrite rules and query variable handling
 * for custom sitemap functionality in SureRank.
 *
 * @since 1.0.0
 */
class Routes {

	use Get_Instance;
	/**
	 * Register rewrite rules and query variables.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		// Add rewrite rules.
		add_action( 'init', [ $this, 'register_rewrite_rules' ], 1 );
	}

	/**
	 * Register custom rewrite rules.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rewrite_rules() {
		$this->sitemap_routes();
	}

	/**
	 * Register custom rewrite rules for the sitemap.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function sitemap_routes() {
		global $wp;
		global $wp_rewrite;

		// Add default rewrite rules.
		add_rewrite_rule( '^' . Xml_Sitemap::get_slug() . '$', 'index.php?surerank_sitemap=1', 'top' );
		add_rewrite_rule( '^([a-z]+)-stylesheet\.xsl$', 'index.php?surerank_sitemap_type=$matches[1]', 'top' );
		// Handle prefixed sitemap URLs (cpt- and tax-).
		$cpt_prefix = Xml_Sitemap::get_post_type_prefix();
		$tax_prefix = Xml_Sitemap::get_taxonomy_prefix();

		add_rewrite_rule(
			'^(' . $cpt_prefix . '|' . $tax_prefix . ')-([a-z0-9_-]+)-sitemap-([0-9]+)?\.xml$',
			'index.php?surerank_sitemap=$matches[2]&surerank_prefix=$matches[1]&surerank_sitemap_page=$matches[3]',
			'top'
		);

		// generic sitemap rewrite rule for non-cpt and non-tax sitemaps.
		add_rewrite_rule(
			'^([a-z0-9_-]+)-sitemap-([0-9]+)?\.xml$',
			'index.php?surerank_sitemap=$matches[1]&surerank_sitemap_page=$matches[2]',
			'top'
		);

		$wp->add_query_var( 'surerank_sitemap' );
		$wp->add_query_var( 'surerank_prefix' );
		$wp->add_query_var( 'surerank_sitemap_type' );
		$wp->add_query_var( 'surerank_sitemap_page' );
	}

}
