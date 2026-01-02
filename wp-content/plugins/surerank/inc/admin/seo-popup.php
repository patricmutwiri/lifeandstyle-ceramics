<?php
/**
 * Post Popup
 *
 * @since 1.0.0
 * @package surerank
 */

namespace SureRank\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Frontend\Crawl_Optimization;
use SureRank\Inc\Frontend\Image_Seo;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Enqueue;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Post Popup
 *
 * @method void wp_enqueue_scripts()
 * @since 1.0.0
 */
class Seo_Popup {

	use Enqueue;
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		$this->enqueue_scripts_admin();
		add_action( 'category_term_edit_form_top', [ $this, 'add_meta_box_trigger' ] );
		add_action( 'created_category', [ $this, 'update_category_seo_values' ] );
		add_action( 'edited_category', [ $this, 'update_category_seo_values' ] );
	}

	/**
	 * Add tags
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_meta_box_trigger() {
		echo '<span id="seo-popup" class="surerank-root"></span>';
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_media();

		$screen      = $this->get_current_screen_safe();
		$editor_type = $this->detect_editor_type( $screen );

		if ( ! $this->should_enqueue_scripts( $editor_type, $screen ) ) {
			return;
		}

		$context_data = $this->get_context_data( $editor_type, $screen );
		$this->enqueue_assets( $editor_type, $context_data );
	}

	/**
	 * Update seo values
	 *
	 * @param int $term_id Post ID.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function update_category_seo_values( $term_id ) {
		// Validate post ID.
		if ( empty( $term_id ) || ! is_int( $term_id ) ) {
			return;
		}

		// Update post seo values.
		$result = Update::term_meta( $term_id, [], [] );

		if ( is_wp_error( $result ) ) {
			return;
		}

		do_action( 'surerank_after_update_category_seo_values', $term_id );
	}

	/**
	 * Get keyword checks configuration
	 *
	 * @since 1.0.0
	 * @return array
	 */
	/**
	 * Get keyword checks configuration
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	public function keyword_checks() {
		return [
			'keyword_in_title',
			'keyword_in_description',
			'keyword_in_url',
			'keyword_in_content',
		];
	}

	/**
	 * Get page checks configuration
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	public function page_checks() {
		return [
			'h2_subheadings',
			'image_alt_text',
			'media_present',
			'links_present',
			'url_length',
			'search_engine_title',
			'search_engine_description',
			'canonical_url',
			'all_links',
			'open_graph_tags',
			'broken_links',
		];
	}

	/**
	 * Get current screen safely.
	 *
	 * @return \WP_Screen|null
	 */
	private function get_current_screen_safe() {
		return function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	}

	/**
	 * Detect the current editor type.
	 *
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return string Editor type.
	 */
	private function detect_editor_type( $screen ): string {
		if ( class_exists( \Elementor\Plugin::class ) && \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
			return 'elementor';
		}

		if ( function_exists( 'bricks_is_builder_main' ) && bricks_is_builder_main() ) {
			return 'bricks';
		}

		if ( $screen && $screen->is_block_editor ) {
			return 'block';
		}

		return 'classic';
	}

	/**
	 * Check if scripts should be enqueued.
	 *
	 * @param string          $editor_type Editor type.
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return bool True if scripts should be enqueued.
	 */
	private function should_enqueue_scripts( string $editor_type, $screen ): bool {
		if ( $editor_type === 'bricks' ) {
			return true;
		}

		return $screen && ! empty( $screen->base ) && in_array( $screen->base, [ 'post', 'term' ], true );
	}

	/**
	 * Get context data for the current page.
	 *
	 * @param string          $editor_type Editor type.
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return array{post_data: array<string, mixed>, term_data: array<string, mixed>, post_type: string, is_taxonomy: bool} Context data.
	 */
	private function get_context_data( string $editor_type, $screen ): array {
		$post_data = $this->get_post_data( $editor_type, $screen );
		$term_data = $this->get_term_data( $screen );

		return [
			'post_data'   => $post_data,
			'term_data'   => $term_data,
			'post_type'   => $this->get_post_type( $editor_type, $screen ),
			'is_taxonomy' => $this->is_taxonomy( $editor_type, $screen ),
		];
	}

	/**
	 * Get post data if on post edit screen.
	 *
	 * @param string          $editor_type Editor type.
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return array<string, mixed> Post data.
	 */
	private function get_post_data( string $editor_type, $screen ): array {
		if ( ( $screen && 'post' === $screen->base ) || $editor_type === 'bricks' ) {
			return [
				'post_id'     => get_the_ID(),
				'editor_type' => $editor_type,
				'link'        => get_the_permalink( (int) get_the_ID() ),
			];
		}

		return [];
	}

	/**
	 * Get term data if on term edit screen.
	 *
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return array<string, mixed> Term data.
	 */
	private function get_term_data( $screen ): array {
		if ( ! $screen || 'term' !== $screen->base ) {
			return [];
		}

		global $tag_ID;

		$final_link = get_term_link( (int) $tag_ID );
		if ( is_wp_error( $final_link ) ) {
			return [];
		}

		$final_link = $this->process_category_link( $final_link, $tag_ID, $screen );

		return [
			'term_id' => $tag_ID,
			'link'    => $final_link,
		];
	}

	/**
	 * Process category link if needed.
	 *
	 * @param string          $link Term link.
	 * @param int             $tag_ID Term ID.
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return string Processed link.
	 */
	private function process_category_link( string $link, int $tag_ID, $screen ): string {
		if ( $screen && 'category' === $screen->taxonomy && apply_filters( 'surerank_remove_category_base', false ) ) {
			$term = get_term( $tag_ID );
			if ( $term && ! is_wp_error( $term ) ) {
				return Crawl_Optimization::get_instance()->remove_category_base_from_links( $link, $term, $screen->taxonomy );
			}
		}

		return $link;
	}

	/**
	 * Get post type for current context.
	 *
	 * @param string          $editor_type Editor type.
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return string Post type.
	 */
	private function get_post_type( string $editor_type, $screen ): string {
		if ( $editor_type === 'bricks' ) {
			$post_type = get_the_ID() ? get_post_type( get_the_ID() ) : false;
			return $post_type !== false ? $post_type : '';
		}

		if ( ! $screen ) {
			return '';
		}

		return ! empty( $screen->taxonomy ) ? $screen->taxonomy : $screen->post_type;
	}

	/**
	 * Check if current context is taxonomy.
	 *
	 * @param string          $editor_type Editor type.
	 * @param \WP_Screen|null $screen Current screen object.
	 * @return bool True if taxonomy context.
	 */
	private function is_taxonomy( string $editor_type, $screen ): bool {
		if ( $editor_type === 'bricks' ) {
			return false;
		}

		return $screen && ! empty( $screen->taxonomy );
	}

	/**
	 * Enqueue assets for SEO popup.
	 *
	 * @param string                                                                                                        $editor_type Editor type.
	 * @param array{post_data: array<string, mixed>, term_data: array<string, mixed>, post_type: string, is_taxonomy: bool} $context_data Context data.
	 * @return void
	 */
	private function enqueue_assets( string $editor_type, array $context_data ): void {
		$this->enqueue_vendor_and_common_assets();

		$this->build_assets_operations(
			'seo-popup',
			[
				'hook'        => 'seo-popup',
				'object_name' => 'seo_popup',
				'data'        => array_merge(
					[
						'admin_assets_url'   => SURERANK_URL . 'inc/admin/assets',
						'site_icon_url'      => get_site_icon_url( 16 ),
						'editor_type'        => $editor_type,
						'post_type'          => $context_data['post_type'],
						'is_taxonomy'        => $context_data['is_taxonomy'],
						'description_length' => Get::description_length(),
						'title_length'       => Get::title_length(),
						'keyword_checks'     => $this->keyword_checks(),
						'page_checks'        => $this->page_checks(),
						'image_seo'          => Image_Seo::get_instance()->status(),
					],
					$context_data['post_data'],
					$context_data['term_data']
				),
			]
		);
	}
}
