<?php
/**
 * Posts class
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Meta_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Frontend\Description;
use SureRank\Inc\Traits\Custom_Field;
use SureRank\Inc\Traits\Get_Instance;

/**
 * This class deals with variables related to posts.
 *
 * @since 0.0.1
 */
class Post extends Variables {

	use Get_Instance, Custom_Field;

	/**
	 * Stores variables array.
	 *
	 * @var array<string, mixed>
	 * @since 0.0.1
	 */
	public $variables = [];

	/**
	 * Category of variables.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	public $category = 'post';

	/**
	 * Stores current post.
	 *
	 * @var \WP_Post|null
	 * @since 0.0.1
	 */
	public $post;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		$this->variables = [
			'ID'            => [
				'label'       => __( 'ID', 'surerank' ),
				'description' => __( 'The unique identifier for a post.', 'surerank' ),
			],
			'title'         => [
				'label'       => __( 'Post Title', 'surerank' ),
				'description' => __( 'The title of the post.', 'surerank' ),
			],
			'excerpt'       => [
				'label'       => __( 'Excerpt', 'surerank' ),
				'description' => __( 'The excerpt of the post.', 'surerank' ),
			],
			'content'       => [
				'label'       => __( 'Content', 'surerank' ),
				'description' => __( 'The content of the post.', 'surerank' ),
			],
			'permalink'     => [
				'label'       => __( 'Permalink', 'surerank' ),
				'description' => __( 'The permalink of the site.', 'surerank' ),
			],
			'published'     => [
				'label'       => __( 'Date Published', 'surerank' ),
				'description' => __( 'Publication date of the current post/page OR specified date on date archives', 'surerank' ),
			],
			'modified'      => [
				'label'       => __( 'Date Modified', 'surerank' ),
				'description' => __( 'Last modification date of the current post/page', 'surerank' ),
			],
			'author_name'   => [
				'label'       => __( 'Author Name', 'surerank' ),
				'description' => __( 'The name of the author of the current post/page', 'surerank' ),
			],
			'archive_title' => [
				'label'       => __( 'Archive Title', 'surerank' ),
				'description' => __( 'The title of the current archive. Example "Day/Month/Year Archives: " or "Author Archives: "', 'surerank' ),
			],
		];
	}

	/**
	 * Get current post id
	 *
	 * @since 0.0.1
	 * @return int|false
	 */
	public function get_ID() {
		if ( ! empty( $this->post ) ) {
			return $this->post->ID;
		}
		return get_the_ID();
	}

	/**
	 * Get title of current post.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_title() {
		if ( ! empty( $this->post ) ) {
			return get_the_title( $this->post );
		}
		return get_the_title();
	}

	/**
	 * Get current post excerpt.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_excerpt() {
		if ( ! empty( $this->post ) ) {
			return Description::get_instance()->sanitize_description( get_the_excerpt( $this->post ) );
		}
		return Description::get_instance()->sanitize_description( get_the_excerpt() );
	}

	/**
	 * Get content of current post.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_content() {
		if ( ! empty( $this->post ) ) {
			return Description::get_instance()->sanitize_description( get_the_content( null, false, $this->post ) );
		}
		return Description::get_instance()->sanitize_description( get_the_content() );
	}

	/**
	 * Returns permalink of current post.
	 *
	 * @since 0.0.1
	 * @return string|false
	 */
	public function get_permalink() {
		if ( ! empty( $this->post ) ) {
			return get_permalink( $this->post );
		}
		return get_permalink();
	}

	/**
	 * This function sets $post variable, required for meta_variables based on post.
	 *
	 * @param int $post_id Post id to set $post variable to retrieve relevant variables.
	 * @since 0.0.1
	 * @return void
	 */
	public function set_post( $post_id = 0 ) {
		if ( ! empty( $post_id ) ) {
			$this->post = get_post( $post_id );
		}
	}

	/**
	 * Get publication date of current post.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_published() {
		if ( ! empty( $this->post ) ) {
			$date = get_the_date( '', $this->post );
			return is_string( $date ) ? $date : '';
		}
		$date = get_the_date();
		return is_string( $date ) ? $date : '';
	}

	/**
	 * Get modified date of current post.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_modified() {
		if ( ! empty( $this->post ) ) {
			$modified = get_the_modified_date( '', $this->post );
			return is_string( $modified ) ? $modified : '';
		}
		$modified = get_the_modified_date();
		return is_string( $modified ) ? $modified : '';
	}

	/**
	 * Get author name of current post.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_author_name() {
		if ( ! empty( $this->post ) && ! empty( $this->post->post_author ) ) {
			$user_id = (int) $this->post->post_author;
		} else {
			$post_id = $this->get_ID();
			$user_id = 0;
			if ( $post_id ) {
				$post = get_post( $post_id );
				if ( $post && ! empty( $post->post_author ) ) {
					$user_id = (int) $post->post_author;
				}
			}
			if ( ! $user_id ) {
				$user_id = get_the_author_meta( 'ID' );
			}
		}
		$name = '';
		if ( is_int( $user_id ) ) {
			$name = get_the_author_meta( 'display_name', $user_id );
		}
		return $name;
	}

	/**
	 * Get archive title prefix for author and date archives only.
	 * This function is relevant only for author and date archives and for SureRank Pro.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_archive_title() {
		if ( is_author() ) {
			return __( 'Author: ', 'surerank' ) . get_the_author();
		}
		if ( is_date() ) {
			if ( is_day() ) {
				return __( 'Day Archives: ', 'surerank' ) . get_the_date();
			}
			if ( is_month() ) {
				return __( 'Month Archives: ', 'surerank' ) . get_the_date( 'F Y' );
			}
			if ( is_year() ) {
				return __( 'Year Archives: ', 'surerank' ) . get_the_date( 'Y' );
			}
		}

		return '';
	}

	/**
	 * Get the meta type for custom fields trait.
	 *
	 * @since 1.6.0
	 * @return string
	 */
	protected function get_meta_type() {
		return 'post';
	}

}
