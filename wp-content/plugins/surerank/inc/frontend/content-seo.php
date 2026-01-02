<?php
/**
 * Unified Content SEO Enhancement Module
 *
 * Handles both image and link SEO enhancements in a single pass for optimal performance.
 *
 * @package surerank
 * @since 1.5.0
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SureRank\Inc\Traits\Get_Instance;

/**
 * Unified content SEO enhancement handler
 *
 * @since 1.5.0
 */
class Content_Seo {

	use Get_Instance;

	/**
	 * Image SEO processor
	 *
	 * @var Image_Seo
	 */
	private $image_processor;

	/**
	 * Link SEO processor
	 *
	 * @var Link_Seo
	 */
	private $link_processor;

	/**
	 * WordPress hooks configuration
	 *
	 * @var array<string, array<string, int>>
	 */
	private $hooks;

	/**
	 * Initialize content enhancement
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->load_processors();
		$this->initialize_hooks_config();
		$this->register_hooks();
	}

	/**
	 * Process images only
	 *
	 * @param string   $content Content to enhance.
	 * @param int|null $post_id Post ID context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	public function enhance_images_only( $content, $post_id = null ) {
		if ( empty( $content ) || strpos( $content, '<img' ) === false ) {
			return $content;
		}

		$clean_content = $this->remove_script_style_tags( $content );
		$image_tags    = $this->extract_tags( $clean_content, 'img' );

		if ( empty( $image_tags ) ) {
			return $content;
		}

		return $this->process_content( $content, $image_tags, [], $post_id );
	}

	/**
	 * Process links only
	 *
	 * @param string   $content Content to enhance.
	 * @param int|null $post_id Post ID context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	public function enhance_links_only( $content, $post_id = null ) {
		if ( empty( $content ) || strpos( $content, '<a' ) === false ) {
			return $content;
		}

		$clean_content = $this->remove_script_style_tags( $content );
		$link_tags     = $this->extract_tags( $clean_content, 'a' );

		if ( empty( $link_tags ) ) {
			return $content;
		}

		return $this->process_content( $content, [], $link_tags, $post_id );
	}

	/**
	 * Unified content enhancement (both images and links)
	 *
	 * @param string   $content Content to enhance.
	 * @param int|null $post_id Post ID context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	public function enhance_content( $content, $post_id = null ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$has_images = strpos( $content, '<img' ) !== false;
		$has_links  = strpos( $content, '<a' ) !== false;

		if ( ! $has_images && ! $has_links ) {
			return $content;
		}

		$clean_content = $this->remove_script_style_tags( $content );
		$image_tags    = $has_images ? $this->extract_tags( $clean_content, 'img' ) : [];
		$link_tags     = $has_links ? $this->extract_tags( $clean_content, 'a' ) : [];

		if ( empty( $image_tags ) && empty( $link_tags ) ) {
			return $content;
		}

		return $this->process_content( $content, $image_tags, $link_tags, $post_id );
	}

	/**
	 * Load sub-processors
	 *
	 * @since 1.5.0
	 */
	private function load_processors(): void {
		$this->image_processor = new Image_Seo();
		$this->link_processor  = new Link_Seo();
	}

	/**
	 * Initialize hooks configuration
	 *
	 * @since 1.5.0
	 */
	private function initialize_hooks_config(): void {
		$this->hooks = [
			'global' => [
				'the_content' => 11,
			],
			'image'  => [
				'post_thumbnail_html' => 11,
				'woocommerce_single_product_image_thumbnail_html' => 11,
			],
			'link'   => [
				'widget_text'  => 11,
				'comment_text' => 11,
			],
		];
	}

	/**
	 * Register WordPress hooks based on enabled features
	 *
	 * @since 1.5.0
	 */
	private function register_hooks(): void {
		$has_images = $this->image_processor->is_enabled();
		$has_links  = $this->link_processor->is_enabled();

		if ( ! $has_images && ! $has_links ) {
			return;
		}

		if ( $has_images && ! $has_links ) {
			$this->register_image_only_hooks();
		} elseif ( $has_links && ! $has_images ) {
			$this->register_link_only_hooks();
		} else {
			$this->register_unified_hooks();
		}
	}

	/**
	 * Register hooks for image-only processing
	 *
	 * @since 1.5.0
	 */
	private function register_image_only_hooks(): void {
		$filters = array_merge(
			$this->hooks['global'],
			$this->hooks['image']
		);

		foreach ( $filters as $hook => $priority ) {
			add_filter( $hook, [ $this, 'enhance_images_only' ], $priority, 2 );
		}
	}

	/**
	 * Register hooks for link-only processing
	 *
	 * @since 1.5.0
	 */
	private function register_link_only_hooks(): void {
		$filters = array_merge(
			$this->hooks['global'],
			$this->hooks['link']
		);

		foreach ( $filters as $hook => $priority ) {
			add_filter( $hook, [ $this, 'enhance_links_only' ], $priority, 2 );
		}
	}

	/**
	 * Register hooks for unified processing
	 *
	 * @since 1.5.0
	 */
	private function register_unified_hooks(): void {
		$filters = array_merge(
			$this->hooks['global'],
			$this->hooks['image'],
			$this->hooks['link']
		);

		foreach ( $filters as $hook => $priority ) {
			add_filter( $hook, [ $this, 'enhance_content' ], $priority, 2 );
		}
	}

	/**
	 * Remove script and style tags from content
	 *
	 * @param string $content Raw content.
	 * @return string Cleaned content
	 * @since 1.5.0
	 */
	private function remove_script_style_tags( $content ): string {
		$result = preg_replace( '/<(script|style)[^>]*?>.*?<\/\1>/si', '', $content );
		return $result !== null ? $result : $content;
	}

	/**
	 * Extract tags using unified regex
	 *
	 * @param string $content Clean content.
	 * @param string $tag_type Either 'img' or 'a'.
	 * @return array<string> Matching tags
	 * @since 1.5.0
	 */
	private function extract_tags( $content, $tag_type ): array {
		if ( $tag_type === 'img' ) {
			return $this->image_processor->extract_processable_images( $content );
		}
		if ( $tag_type === 'a' ) {
			return $this->link_processor->extract_processable_links( $content );
		}

		return [];
	}

	/**
	 * Process content with extracted tags
	 *
	 * @param string        $content Original content.
	 * @param array<string> $image_tags Image tags to process.
	 * @param array<string> $link_tags Link tags to process.
	 * @param int|null      $post_id Post context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	private function process_content( $content, $image_tags, $link_tags, $post_id ): string {
		$processed_content = $content;

		if ( ! empty( $image_tags ) ) {
			$processed_content = $this->image_processor->process_images( $processed_content, $image_tags, $post_id );
		}

		if ( ! empty( $link_tags ) ) {
			$processed_content = $this->link_processor->process_links( $processed_content, $link_tags, $post_id );
		}

		return $processed_content;
	}
}
