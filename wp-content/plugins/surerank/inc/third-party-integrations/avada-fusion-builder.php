<?php
/**
 * Avada Fusion Builder Integration
 *
 * @package SureRank\Inc\ThirdPartyIntegrations
 */

namespace SureRank\Inc\ThirdPartyIntegrations;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Avada Fusion Builder Integration Class
 */
class Avada_Fusion_Builder {

	use Get_Instance;

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( ! $this->is_active() ) {
			return;
		}

		add_filter( 'surerank_post_analyzer_content', [ $this, 'process_fusion_builder_content' ], 10, 2 );
	}

	/**
	 * Check if Avada Fusion Builder is active
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'FusionBuilder' ) || function_exists( 'fusion_is_preview_frame' );
	}

	/**
	 * Process Fusion Builder content using do_shortcode
	 *
	 * @param string   $content Post content.
	 * @param \WP_Post $post Post object.
	 * @return string Processed content.
	 */
	public function process_fusion_builder_content( $content, $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return $content;
		}

		// Check if Fusion Builder is active for this post.
		$fusion_builder_status = get_post_meta( $post->ID, 'fusion_builder_status', true );
		
		if ( 'active' === $fusion_builder_status ) {
			// Process shortcodes for Fusion Builder content.
			return do_shortcode( $content );
		}

		return $content;
	}
}
