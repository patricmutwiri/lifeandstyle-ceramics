<?php
/**
 * Image Meta Data
 *
 * Handles functionality to retrieve and manage image metadata for frontend requests,
 * particularly for Open Graph image tags.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Image Meta Data Handler
 *
 * Manages image metadata retrieval for frontend requests, optimized for Open Graph tags.
 *
 * @since 0.0.1
 */
class Image {
	use Get_Instance;

	/**
	 * Cache for image data
	 *
	 * @var array<string, string|null>
	 */
	private $image_cache = [];

	/**
	 * Retrieves Open Graph image URL based on page type
	 *
	 * @return string|null OG image URL or null if none found
	 * @since 1.0.0
	 */
	public function get_og_image() {
		if ( post_password_required() ) {
			return null;
		}

		if ( ! get_queried_object_id() ) {
			return null;
		}

		$cache_key = md5( (string) wp_json_encode( get_queried_object_id() ) );
		if ( isset( $this->image_cache[ $cache_key ] ) ) {
			return $this->image_cache[ $cache_key ];
		}

		$image_url = null;
		if ( is_singular() ) {
			$image_url = $this->get_singular_page_image();
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$image_url = $this->get_taxonomy_image();
		}

		$this->image_cache[ $cache_key ] = $image_url;
		return $image_url;
	}

	/**
	 * Get attachment ID by URL
	 *
	 * @param string $url Image URL.
	 * @return int Attachment ID or 0 if not found
	 * @since 1.0.0
	 */
	public function attachment_by_url( $url ) {
		$attachment_id = attachment_url_to_postid( $url );
		return (int) $attachment_id;
	}

	/**
	 * Prepare fallback image
	 *
	 * @param array<string, mixed> $meta_data Meta Data.
	 * @param string               $key       Meta key to populate.
	 * @since 1.0.0
	 * @return void
	 */
	public function get( &$meta_data, $key ) {
		$fallback_image = Settings::get( 'fallback_image' );

		if ( ! empty( $meta_data[ $key ] ) && $this->is_valid_image_extension( $meta_data[ $key ] ) ) {
			return;
		}

		$meta_data[ $key ] = '';
		if ( $this->get_og_image() ) {
			$meta_data[ $key ] = $this->get_og_image();
		} elseif ( $fallback_image && $this->is_valid_image_extension( $fallback_image ) ) {
			$meta_data[ $key ] = $fallback_image;
		}
	}

	/**
	 * Retrieves taxonomy page image
	 *
	 * @param int|null $term_id The term ID.
	 * @return string|null Taxonomy image URL or null if none found
	 * @since 1.0.0
	 */
	public function get_taxonomy_image( $term_id = null ) {
		$term_description = '';
		if ( ! $term_id ) {
			$term_description = term_description();
		} else {
			$term_data = get_term( $term_id );
			if ( $term_data ) {
				$term_description = $term_data->description ?? '';
			}
		}

		if ( ! $term_description ) {
			return null;
		}

		return $this->get_first_content_image( $term_description );
	}

	/**
	 * Retrieves singular page image
	 *
	 * @param int|null $post_id The post ID.
	 * @return string|null Singular page image URL or null if none found
	 * @since 1.0.0
	 */
	public function get_singular_page_image( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_queried_object_id();
		}

		if ( ! $post_id ) {
			return null;
		}

		$post_instance = Post::get_instance();
		$post_instance->set_post( $post_id );

		$content = $post_instance->get_content();
		return $content ? $this->get_first_content_image( $content ) : null;
	}

	/**
	 * Validates image file extension
	 *
	 * @param string $url Image URL.
	 * @return bool Whether extension is valid
	 * @since 1.0.0
	 */
	public function is_valid_image_extension( string $url ) {
		$valid_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' ];
		$extension        = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		return ! empty( $extension ) && in_array( $extension, $valid_extensions, true );
	}

	/**
	 * Extracts first valid image from content
	 *
	 * @param string $content The content to search.
	 * @return string|null Image URL or null if none found
	 * @since 1.0.0
	 */
	private function get_first_content_image( string $content ) {
		if ( empty( $content ) || strpos( $content, '<img' ) === false ) {
			return null;
		}

		if ( ! preg_match( '/<img\s+[^>]*?src=["\']([^"\']+)["\']/i', $content, $match ) ) {
			return null;
		}

		$src = trim( $match[1] );
		if ( empty( $src ) ) {
			return null;
		}

		$absolute_url = $this->ensure_absolute_url( $src );
		$image_data   = $this->get_image_data_by_url( $absolute_url );

		return $image_data && $this->is_valid_og_image( $image_data ) ? $image_data['url'] : null;
	}

	/**
	 * Gets image data by attachment ID
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return array<string, mixed>|null Image data array or null if invalid
	 * @since 1.0.0
	 */
	private function get_image_data_by_id( int $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return null;
		}

		$image = wp_get_attachment_image_src( $attachment_id, 'large' );
		if ( ! $image ) {
			return null;
		}

		[$url, $width, $height] = $image;
		return [
			'id'     => $attachment_id,
			'url'    => $url,
			'width'  => $width,
			'height' => $height,
			'type'   => get_post_mime_type( $attachment_id ),
			'alt'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?? '',
		];
	}

	/**
	 * Gets image data by URL
	 *
	 * @param string $url Image URL.
	 * @return array<string, mixed>|null Image data array or null if invalid
	 * @since 1.0.0
	 */
	private function get_image_data_by_url( string $url ) {
		$url = filter_var( $url, FILTER_VALIDATE_URL ) ? $url : null;
		if ( ! $url ) {
			return null;
		}

		$attachment_id = $this->attachment_by_url( $url );
		if ( $attachment_id > 0 ) {
			return $this->get_image_data_by_id( $attachment_id );
		}

		if ( ! $this->is_valid_image_extension( $url ) ) {
			return null;
		}

		return [ 'url' => $url ];
	}

	/**
	 * Validates image for Open Graph use
	 *
	 * @param array<string, mixed> $image_data Image data to validate.
	 * @return bool Whether image meets OG requirements
	 * @since 1.0.0
	 */
	private function is_valid_og_image( array $image_data ) {
		if ( empty( $image_data['url'] ) || ! $this->is_valid_image_extension( $image_data['url'] ) ) {
			return false;
		}

		if ( isset( $image_data['width'] ) && isset( $image_data['height'] ) ) {
			return $image_data['width'] >= apply_filters( 'surerank_og_dimensions_min_width', 200 ) &&
					$image_data['height'] >= apply_filters( 'surerank_og_dimensions_min_height', 200 ) &&
					$image_data['width'] <= apply_filters( 'surerank_og_dimensions_max_width', 2000 ) &&
					$image_data['height'] <= apply_filters( 'surerank_og_dimensions_max_height', 2000 );
		}

		return true;
	}

	/**
	 * Ensures URL is absolute
	 *
	 * @param string $url Input URL.
	 * @return string Absolute URL
	 * @since 1.0.0
	 */
	private function ensure_absolute_url( string $url ): string {
		if ( empty( $url ) ) {
			return '';
		}

		$sanitized_url = filter_var( $url, FILTER_SANITIZE_URL );
		if ( ! $sanitized_url ) {
			return '';
		}

		return filter_var( $sanitized_url, FILTER_VALIDATE_URL ) ? $sanitized_url : home_url( $sanitized_url );
	}
}
