<?php
/**
 * Term Analyzer class.
 *
 * Performs SEO checks for WordPress terms with consistent output for UI.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Analyzer;

use SureRank\Inc\API\Admin;
use SureRank\Inc\API\Term;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;
use WP_Term;

/**
 * Term Analyzer class.
 */
class TermAnalyzer {
	use Get_Instance;
	use Logger;
	/**
	 * Term title.
	 *
	 * @var string|null
	 */
	private $term_title = '';

	/**
	 * Term description.
	 *
	 * @var string|null
	 */
	private $term_description = '';

	/**
	 * Canonical URL.
	 *
	 * @var string|null
	 */
	private $canonical_url = '';

	/**
	 * Term ID.
	 *
	 * @var int|null
	 */
	private $term_id;

	/**
	 * Term permalink.
	 *
	 * @var string
	 */
	private $term_permalink = '';

	/**
	 * Term content.
	 *
	 * @var string
	 */
	private $term_content = '';

	/**
	 * Constructor.
	 */
	private function __construct() {
		if ( ! Settings::get( 'enable_page_level_seo' ) ) {
			return;
		}
		add_action( 'edited_term', [ $this, 'save_term' ], 10, 3 );
		add_action( 'save_term', [ $this, 'save_term' ], 10, 3 );
		add_filter( 'surerank_run_term_seo_checks', [ $this, 'run_checks' ], 10, 2 );
	}

	/**
	 * Handle term save to run SEO checks.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) || ! $term instanceof WP_Term ) {
			return;
		}

		if ( in_array( $term->taxonomy, apply_filters( 'surerank_excluded_taxonomies_from_seo_checks', [] ), true ) ) {
			return;
		}

		$response = $this->run_checks( $term_id, $term );

		if ( isset( $response['status'] ) && 'error' === $response['status'] ) {
			self::log( $response['message'] );
		}

		Update::term_meta( $term_id, 'surerank_taxonomy_updated_at', time() );
	}

	/**
	 * Run SEO checks for the term.
	 *
	 * @param int     $term_id Term ID.
	 * @param WP_Term $term    Term object.
	 * @return array<string, mixed>
	 */
	public function run_checks( $term_id, $term ) {
		$this->term_id = $term_id;

		if ( ! $this->term_id || ! $term instanceof WP_Term ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid term ID or term object.', 'surerank' ),
			];
		}

		$meta_data = Term::get_term_data_by_id( $term_id, $term->taxonomy, false );
		$variables = Admin::get_instance()->get_variables( null, $term_id );
		$meta_data = Utils::get_meta_data( $meta_data );

		foreach ( $meta_data as $key => $value ) {
			$meta_data[ $key ] = Helper::replacement( $key, $value, $variables );
		}

		$this->term_title       = $meta_data['page_title'] ?? ''; // we are keeping meta_data['page_title'] as we are using this globally.
		$this->term_description = $meta_data['page_description'] ?? ''; // same for meta_data['page_description'] as above.
		$this->canonical_url    = $meta_data['canonical_url'] ?? '';
		$this->term_permalink   = ! is_wp_error( get_term_link( (int) $term_id ) ) ? get_term_link( (int) $term_id ) : '';
		$this->term_content     = $term->description;

		$rendered_content = $term->description;
		$result           = $this->analyze( $meta_data );

		$success = Update::taxonomy_seo_checks( $term_id, $result );

		if ( ! $success ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update SEO checks', 'surerank' ),
			];
		}

		return $result;
	}

	/**
	 * Analyze the term.
	 *
	 * @param array<string, mixed> $meta_data Meta data.
	 * @return array<string, mixed>
	 */
	private function analyze( array $meta_data ) {
		// Get focus keyword for keyword checks.
		$focus_keyword = $meta_data['focus_keyword'] ?? '';

		return [
			'url_length'                => Utils::check_url_length( $this->term_permalink ),
			'search_engine_title'       => Utils::analyze_title( $this->term_title ),
			'search_engine_description' => Utils::analyze_description( $this->term_description ),
			'canonical_url'             => $this->canonical_url(),
			'open_graph_tags'           => Utils::open_graph_tags(),
			// Keyword checks.
			'keyword_in_title'          => Utils::analyze_keyword_in_title( $this->term_title, $focus_keyword ),
			'keyword_in_description'    => Utils::analyze_keyword_in_description( $this->term_description, $focus_keyword ),
			'keyword_in_url'            => Utils::analyze_keyword_in_url( $this->term_permalink, $focus_keyword ),
			'keyword_in_content'        => Utils::analyze_keyword_in_content( $this->term_content, $focus_keyword ),
		];
	}

	/**
	 * Get canonical URL.
	 *
	 * @return array<string, mixed>
	 */
	private function canonical_url() {
		if ( $this->canonical_url === null ) {
			return [
				'status'  => 'error',
				'message' => __( 'No canonical URL provided.', 'surerank' ),
			];
		}

		$permalink = get_term_link( (int) $this->term_id );
		if ( ! $permalink || is_wp_error( $permalink ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'No permalink provided.', 'surerank' ),
			];
		}

		return Utils::analyze_canonical_url( $this->canonical_url, $permalink );
	}
}
