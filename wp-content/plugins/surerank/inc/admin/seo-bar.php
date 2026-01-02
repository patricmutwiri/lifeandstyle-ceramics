<?php
/**
 * SEO Bar class.
 *
 * Handles the addition of a custom column and enqueuing of assets for the SureRank plugin in WordPress post/page and taxonomy admin views.
 *
 * @package SureRank\Inc\Admin
 */

namespace SureRank\Inc\Admin;

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Enqueue;
use SureRank\Inc\Traits\Get_Instance;

/**
 * SureRank Seo Bar
 *
 * Handles the addition of a custom column and enqueuing of assets for the SureRank plugin in WordPress post/page and taxonomy admin views.
 */
class Seo_Bar {

	use Get_Instance;
	use Enqueue;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Settings::get( 'enable_page_level_seo' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->enqueue_scripts_admin();
		add_action( 'admin_init', [ $this, 'setup_columns' ] );
		add_action( 'admin_init', [ $this, 'setup_taxonomy_columns' ] );
	}

	/**
	 * Override enqueue_scripts to prevent wp_enqueue_scripts action.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
	}

	/**
	 * Enqueue admin scripts and styles for admin-seo-bar.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( 'edit.php' === $hook && 'edit' === $screen->base && $this->display_metabox( $screen->post_type, 'wp_posts' ) ) {
			// Enqueue vendor and common assets.
			$this->enqueue_vendor_and_common_assets();

			$this->build_assets_operations(
				'admin-seo-bar',
				[
					'hook'        => 'admin-seo-bar',
					'object_name' => 'seo_bar',
					'data'        => [
						'post_type' => $screen->post_type,
						'type'      => 'post',
					],
				]
			);
			do_action( 'surerank_seo_bar_enqueue_post_type_scripts', $hook, $screen );
		}

		if ( 'edit-tags.php' === $hook && 'edit-tags' === $screen->base && $this->display_metabox( $screen->taxonomy, 'wp_terms' ) ) {

			// Enqueue vendor and common assets.
			$this->enqueue_vendor_and_common_assets();

			$this->build_assets_operations(
				'admin-seo-bar',
				[
					'hook'        => 'admin-seo-bar',
					'object_name' => 'seo_bar',
					'data'        => [
						'taxonomy' => $screen->taxonomy,
						'type'     => 'taxonomy',
					],
				]
			);
			do_action( 'surerank_seo_bar_enqueue_taxonomy_scripts', $hook, $screen );
		}
	}

	/**
	 * Adds the custom column to the post admin table.
	 *
	 * @param array<string, string> $columns The existing columns.
	 * @return array<string, string> The modified columns.
	 */
	public function column_heading( $columns ) {
		if ( $this->display_metabox() === false ) {
			return $columns;
		}

		$new_columns   = [];
		$target_column = apply_filters( 'surerank_seo_bar_column_position_post', 5 );
		$custom_column = [
			'surerank-data' => __( 'SEO Checks', 'surerank' ),
		];

		return array_slice( $columns, 0, $target_column, true ) + $custom_column + array_slice( $columns, $target_column, null, true );
	}

	/**
	 * Adds the custom column to the taxonomy admin table.
	 *
	 * @param array<string, string> $columns The existing columns.
	 * @return array<string, string> The modified columns.
	 */
	public function column_heading_taxonomy( $columns ) {
		if ( $this->display_metabox() === false ) {
			return $columns;
		}

		$new_columns   = [];
		$target_column = apply_filters( 'surerank_seo_bar_column_position_taxonomy', 4 );
		$custom_column = [
			'surerank-data' => __( 'SEO Checks', 'surerank' ),
		];
		return array_slice( $columns, 0, $target_column, true ) + $custom_column + array_slice( $columns, $target_column, null, true );
	}

	/**
	 * Renders column content for posts or taxonomies.
	 *
	 * @param string|int $column_name The name of the column.
	 * @param int        $id          The ID of the post or term.
	 * @return void
	 */
	public function column_content( $column_name, $id ) {
		if ( ! $id ) {
			return;
		}

		// Skip if the page is built with Bricks builder.
		if ( get_post_meta( $id, '_bricks_page_content_2', true ) ) {
			return;
		}

		$post_title = get_the_title( $id );

		if ( $column_name === 'surerank-data' ) {
			echo '<span id="surerank-seo-popup-' . esc_attr( (string) $id ) . '" class="surerank-root surerank-page-score" data-title="' . esc_attr( (string) $post_title ) . '" data-id="' . esc_attr( (string) $id ) . '"><div class="bg-gray-200 animate-pulse w-full h-6 rounded-full max-w-32"></div></span>';
		}
	}

	/**
	 * Sets up column filters and actions for accessible post types.
	 *
	 * @return void
	 */
	public function setup_columns() {
		$post_types = $this->get_accessible_post_types();
		foreach ( $post_types as $post_type ) {
			if ( $this->display_metabox( $post_type, 'wp_posts' ) !== false ) {
				add_filter( "manage_{$post_type}_posts_columns", [ $this, 'column_heading' ], 10, 1 );
				add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'column_content' ], 10, 2 );
			}
		}
	}

	/**
	 * Sets up column filters and actions for accessible taxonomies.
	 *
	 * @return void
	 */
	public function setup_taxonomy_columns() {
		$taxonomies = $this->get_accessible_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			if ( $this->display_metabox( $taxonomy, 'wp_terms' ) !== false ) {
				add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'column_heading_taxonomy' ], 10, 1 );
				add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'column_content_taxonomy' ], 10, 3 );
			}
		}
	}

	/**
	 * Renders column content for taxonomies.
	 *
	 * @param string $content     The current column content.
	 * @param string $column_name The name of the column.
	 * @param int    $term_id     The ID of the term.
	 * @return string
	 */
	public function column_content_taxonomy( $content, $column_name, $term_id ) {
		if ( ! $term_id ) {
			return $content;
		}

		$term_title = get_term( $term_id );

		if ( is_wp_error( $term_title ) ) {
			$term_title = '';
		} else {
			$term_title = $term_title->name ?? '';
		}

		if ( $column_name === 'surerank-data' ) {
			echo '<span id="surerank-seo-popup-' . esc_attr( (string) $term_id ) . '" class="surerank-root surerank-page-score" data-title="' . esc_attr( (string) $term_title ) . '" data-id="' . esc_attr( (string) $term_id ) . '"><div class="bg-gray-200 animate-pulse w-full h-6 rounded-full max-w-32"></div></span>';
		}

		return $content;
	}

	/**
	 * Checks if the metabox should be displayed.
	 *
	 * @param string $post_type_or_taxonomy Post type or taxonomy to check.
	 * @param string $object_type Object type to check.
	 * @return bool Whether the metabox should be displayed.
	 */
	public static function display_metabox( $post_type_or_taxonomy = '', $object_type = '' ) {
		if ( $object_type === 'wp_posts' ) {
			if ( in_array( $post_type_or_taxonomy, apply_filters( 'surerank_excluded_post_types_from_seo_checks', [ 'elementor_library', 'sureforms_form', 'astra-advanced-hook' ] ), true ) ) {
				return false;
			}
		}

		if ( $object_type === 'wp_terms' ) {
			if ( in_array( $post_type_or_taxonomy, apply_filters( 'surerank_excluded_taxonomies_from_seo_checks', [] ), true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieves accessible post types.
	 *
	 * @return array<string> List of accessible post types.
	 */
	private function get_accessible_post_types() {
		return get_post_types( [ 'public' => true ], 'names' );
	}

	/**
	 * Retrieves accessible taxonomies.
	 *
	 * @return array<string> List of accessible taxonomies.
	 */
	private function get_accessible_taxonomies() {
		return get_taxonomies( [ 'public' => true ], 'names' );
	}
}
