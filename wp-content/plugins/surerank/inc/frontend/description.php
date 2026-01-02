<?php
/**
 * Description
 *
 * This file handles the functionality to generate a description automatically for post/page/taxonomy.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Sanitize;
use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Class Description
 * Handles functionality to auto-generate Open Graph description based on post content, post excerpt, or archive description.
 *
 * @since 1.0.0
 */
class Description {
	use Get_Instance;

	/**
	 * Get the generated Open Graph description.
	 *
	 * @param int    $post_id Optional post ID. If not provided, it will be determined automatically.
	 * @param string $post_type Post type (default empty string).
	 * @return string Generated description.
	 */
	public function get( int $post_id = 0, string $post_type = '' ): string {
		if ( 0 === $post_id ) {
			$post_id = (int) $this->get_id();
		}

		if ( 0 === $post_id ) {
			return '';
		}

		$description = $this->get_description( $post_id );

		return self::sanitize_description( $description );
	}

	/**
	 * Get the post description.
	 *
	 * @param int $post_id Post ID.
	 * @return string Description.
	 */
	public function post( int $post_id ): string {
		$content = $this->get_post_description( $post_id );

		if ( empty( $content ) ) {
			return '';
		}

		return self::sanitize_description( $content );
	}

	/**
	 * Get the taxonomy description.
	 *
	 * @param int $term_id Term ID.
	 * @return string Description.
	 */
	public function taxonomy( int $term_id ): string {
		$content = $this->get_archive_description( $term_id );

		if ( empty( $content ) ) {
			return '';
		}

		return self::sanitize_description( $content );
	}

	/**
	 * Get the current post ID based on the context.
	 *
	 * @return int|null Post ID or null if not found.
	 */
	public function get_id(): ?int {
		if ( is_admin() ) {
			$admin_id = $this->get_admin_id();
			return is_int( $admin_id ) ? $admin_id : null;
		}

		if ( is_feed() ) {
			return absint( get_the_ID() );
		}

		return get_queried_object_id();
	}

	/**
	 * Sanitize and limit description length.
	 *
	 * @param string $description Description text.
	 * @return string Sanitized description.
	 */
	public static function sanitize_description( string $description ): string {
		if ( empty( $description ) ) {
			return '';
		}

		$remove_tags = apply_filters(
			'surerank_description_remove_tags',
			[
				'script',
				'style',
				'iframe',
				'noscript',
				'form',
				'input',
				'select',
				'button',
				'textarea',
				'svg',
				'canvas',
				'video',
				'audio',
				'embed',
				'object',
				'li',
				'ul',
				'ol',
				'table',
				'img',
			]
		);

		$replace_tags = apply_filters(
			'surerank_description_replace_tags',
			[
				'address',
				'article',
				'aside',
				'blockquote',
				'details',
				'div',
				'footer',
				'header',
				'hgroup',
				'hr',
				'nav',
				'ol',
				'p',
				'section',
			]
		);

		// This sanitize all the shortcodes.
		$description = Sanitize::sanitize_shortcode( $description );

		// This will remove all the tags and replace them with a space.
		$description = preg_replace( '#<(' . implode( '|', $remove_tags ) . ')[^>]*>.*?</\1>#si', ' ', (string) $description );

		// This will replace the tags with a space.
		$description = preg_replace( '#</?(' . implode( '|', $replace_tags ) . ')[^>]*>#si', ' ', (string) $description );

		$description = wp_strip_all_tags( (string) $description );
		$description = html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ); // Decode HTML entities.
		$description = trim( (string) preg_replace( '/\s+/', ' ', $description ) );

		$description = str_replace( '[…]', '', $description );
		return trim( Get::formatted_description( $description ) );
	}

	/**
	 * Get the appropriate description based on context.
	 *
	 * @param int $post_id Optional post ID.
	 * @return string Description.
	 */
	private function get_description( int $post_id ): string {
		$show_on_front = get_option( 'show_on_front' );
		$front_page_id = get_option( 'page_on_front' );
		$posts_page_id = get_option( 'page_for_posts' );

		if ( 'page' === $show_on_front && is_front_page() ) {
			return esc_html__( 'Welcome to ', 'surerank' ) . get_bloginfo( 'name' );
		}

		if ( 'posts' === $show_on_front && is_home() ) {
			return esc_html__( 'Latest posts from ', 'surerank' ) . get_bloginfo( 'name' );
		}

		if ( 'page' === $show_on_front && is_home() && $posts_page_id === $post_id ) {
			return $this->get_archive_description();
		}

		if ( is_archive() ) {
			return $this->get_archive_description();
		}

		return $this->get_post_description( $post_id );
	}

	/**
	 * Get the post ID when inside the admin panel.
	 *
	 * @return int|null Admin post ID or null if not applicable.
	 */
	private function get_admin_id(): ?int {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'post' === $screen->base ) {
			return absint( get_the_ID() );
		}

		if ( $screen && 'edit-tags' === $screen->base ) {
			$term_id = filter_input( INPUT_GET, 'tag_ID', FILTER_VALIDATE_INT );
			return is_int( $term_id ) ? $term_id : null;
		}

		return null;
	}

	/**
	 * Get post/page description.
	 *
	 * @param int $post_id Post ID.
	 * @return string Description.
	 */
	private function get_post_description( int $post_id ): string {

		$post_instance = Post::get_instance();
		$post_instance->set_post( $post_id );

		return $post_instance->get_content();
	}

	/**
	 * Get archive description.
	 *
	 * @param int|null $term_id Term ID.
	 * @return string Archive description.
	 */
	private function get_archive_description( ?int $term_id = null ): string {
		if ( $term_id ) {
			return (string) term_description( $term_id );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return (string) term_description();
		}

		if ( is_author() ) {
			return (string) get_the_author_meta( 'description' );
		}

		if ( is_post_type_archive() ) {
			$post_type = get_queried_object();
			return isset( $post_type->description ) ? (string) $post_type->description : '';
		}

		return '';
	}

}
