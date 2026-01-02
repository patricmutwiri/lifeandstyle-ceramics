<?php
/**
 * Attachment Meta Data
 *
 * This file will handle functionality to print meta_data in frontend for different requests.
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Meta_Variables\Post;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Attachment Page SEO
 * This class will handle functionality to print meta_data in frontend for different requests.
 *
 * @since 1.0.0
 */
class Attachment {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'template_redirect' ] );
		add_action( 'add_attachment', [ $this, 'generate_attachment_attributes' ] );
	}

	/**
	 * Filter title
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function template_redirect() {
		if ( ! empty( Settings::get( 'redirect_attachment_pages_to_post_parent' ) ) ) {
			global $post;
			if ( is_attachment() && isset( $post->post_parent ) && is_numeric( $post->post_parent ) && ( 0 !== $post->post_parent ) ) {
				wp_reset_postdata();
				$location = get_permalink( intval( $post->post_parent ) );
				if ( is_string( $location ) ) {
					wp_safe_redirect( $location, 301 );
					exit;
				}
			} elseif ( is_attachment() && isset( $post->post_parent ) && is_numeric( $post->post_parent ) && ( 0 === $post->post_parent ) ) {
				wp_safe_redirect( get_home_url(), 302 );
				exit;
			}
		}
	}

	/**
	 * Process title
	 *
	 * @param string $title Title.
	 * @return string
	 * @since 1.0.0
	 */
	public function process_title( $title ) {
		// Sanitize the title: remove hyphens, underscores & extra spaces.
		$title = preg_replace( '%\s*[-_\s]+\s*%', ' ', $title );

		if ( empty( $title ) ) {
			return '';
		}

		// Lowercase attributes.
		return strtolower( $title );
	}

	/**
	 * Get attachment title
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 * @since 1.0.0
	 */
	public function get_attachment_title( $post_id ) {
		Post::get_instance()->set_post( intval( $post_id ) );

		if ( empty( Post::get_instance()->post ) ) {
			return '';
		}

		$parent = Post::get_instance()->post->post_parent ? Post::get_instance()->post->post_parent : null;

		if ( empty( $parent ) ) {
			$cpt = get_post_type( $post_id ) ? get_post_type( $post_id ) : null;
		} else {
			$cpt = get_post_type( $parent ) ? get_post_type( $parent ) : null;
		}

		$title = '';
		if ( isset( $cpt ) && 'product' === $cpt ) {
			$parent_post = get_post( $parent );
			if ( ! empty( $parent_post ) ) {
				$title = $parent_post->post_title; // Use the product title for WooCommerce products.
			}
		} else {
			$title = Post::get_instance()->post->post_title;
		}

		return $title;
	}

	/**
	 * Generate image attributes
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $bulk Bulk.
	 * @return void
	 * @since 1.0.0
	 */
	public function generate_attachment_attributes( $post_id, $bulk = false ) {
		if ( ! wp_attachment_is_image( $post_id ) ) {
			return;
		}

		$title = $this->process_title( $this->get_attachment_title( $post_id ) );

		if ( ! empty( Settings::get( 'auto_set_image_title' ) ) ) {
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $title,
				]
			);
		}

		if ( ! empty( Settings::get( 'auto_set_image_alt' ) ) ) {
			update_post_meta( $post_id, '_wp_attachment_image_alt', $title );
		}
	}
}
