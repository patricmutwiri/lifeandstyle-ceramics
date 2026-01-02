<?php
/**
 * Display Rules
 *
 * This file handles functionality for all Rules.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\Traits\Get_Instance;

/**
 * Rules
 *
 * This class handles the logic for display rules and location selections.
 *
 * @since 1.0.0
 */
class Rules {
	use Get_Instance;

	/**
	 * Get Location Selections
	 *
	 * Retrieves the options for location rules based on post types and taxonomies.
	 *
	 * @param mixed $consider_type Consider type (single or archive) for specific options.
	 * @return array<string, mixed> The array of location selection options.
	 * @since 1.0.0
	 */
	public static function get_schema_rules_selections( $consider_type = false ): array {
		$post_types        = self::get_filtered_post_types();
		$selection_options = self::initialize_selection_options( $consider_type );

		// Add WooCommerce product types if available.
		self::add_woocommerce_options( $selection_options );

		// Add taxonomy-based options.
		self::add_taxonomy_options( $selection_options, $post_types, $consider_type );

		// Add specific target option.
		$selection_options['specific-target'] = self::get_specific_target_option();

		return apply_filters( 'surerank_display_on_list', $selection_options );
	}

	/**
	 * Get target rules for generating the markup for rule selector.
	 *
	 * @since  1.0.0
	 *
	 * @param object $post_type Post type parameter.
	 * @param object $taxonomy Taxonomy for creating the target rule markup.
	 * @param mixed  $consider_type Consider type for dealing with rule options.
	 * @return array<string, mixed>
	 */
	public static function get_post_target_rule_options( $post_type, $taxonomy, $consider_type = false ) {
		if ( ! $post_type instanceof \WP_Post_Type || ! $taxonomy instanceof \WP_Taxonomy ) {
			return [];
		}

		$post_key    = str_replace( ' ', '-', strtolower( $post_type->label ) );
		$post_label  = ucwords( $post_type->label );
		$post_name   = $post_type->name;
		$post_option = [];

		if ( 'archive' !== $consider_type ) {
			/* translators: %s: Post type label */
			$all_posts                          = sprintf( __( 'All %s', 'surerank' ), $post_label );
			$post_option[ $post_name . '|all' ] = $all_posts;
		}

		if ( 'pages' !== $post_key && 'single' !== $consider_type ) {
			/* translators: %s: Post type label */
			$all_archive                                = sprintf( __( 'All %s Archive', 'surerank' ), $post_label );
			$post_option[ $post_name . '|all|archive' ] = $all_archive;
		}

		if ( 'single' !== $consider_type ) {
			if ( in_array( $post_type->name, $taxonomy->object_type, true ) ) {
				$tax_label = ucwords( $taxonomy->label );
				$tax_name  = $taxonomy->name;
				/* translators: %s: Taxonomy label */
				$tax_archive = sprintf( __( 'All %s Archive', 'surerank' ), $tax_label );

				$post_option[ $post_name . '|all|taxarchive|' . $tax_name ] = $tax_archive;
			}
		}

		return [
			'post_key' => $post_key,
			'label'    => $post_label,
			'value'    => $post_option,
		];
	}

	/**
	 * Get filtered post types.
	 *
	 * @return array<string, \WP_Post_Type> Filtered post types.
	 */
	private static function get_filtered_post_types(): array {
		// Get built-in post types.
		$builtin_types = self::get_post_types_by_builtin( true );
		unset( $builtin_types['attachment'] );

		// Get custom post types.
		$custom_types = self::get_post_types_by_builtin( false );

		// Merge and apply filter.
		$post_types = array_merge( $builtin_types, $custom_types );
		return apply_filters( 'surerank_location_rule_post_types', $post_types );
	}

	/**
	 * Get post types by builtin status.
	 *
	 * @param bool $builtin Whether to get builtin types.
	 * @return array<string, \WP_Post_Type> Post types.
	 */
	private static function get_post_types_by_builtin( bool $builtin ): array {
		$args = [
			'public'   => true,
			'_builtin' => $builtin,
		];

		$post_types = get_post_types( $args, 'objects' );
		return array_filter( $post_types, static fn( $pt ) => $pt instanceof \WP_Post_Type );
	}

	/**
	 * Initialize selection options based on consider type.
	 *
	 * @param mixed $consider_type Consider type.
	 * @return array<string, mixed> Initial selection options.
	 */
	private static function initialize_selection_options( $consider_type ): array {
		$global_val   = self::get_global_values( $consider_type );
		$basic_option = [
			'basic' => [
				'label' => __( 'Basic', 'surerank' ),
				'value' => $global_val,
			],
		];

		if ( 'single' === $consider_type ) {
			return $basic_option;
		}

		return array_merge(
			$basic_option,
			[
				'special-pages' => [
					'label' => __( 'Special Pages', 'surerank' ),
					'value' => self::get_special_pages(),
				],
			]
		);
	}

	/**
	 * Get global values based on consider type.
	 *
	 * @param mixed $consider_type Consider type.
	 * @return array<string, string> Global values.
	 */
	private static function get_global_values( $consider_type ): array {
		$values = [
			'basic-global' => __( 'Entire Website', 'surerank' ),
		];

		if ( 'single' === $consider_type ) {
			$values['basic-singulars'] = __( 'All Singulars', 'surerank' );
		} elseif ( 'archive' === $consider_type ) {
			$values['basic-archives'] = __( 'All Archives', 'surerank' );
		} else {
			$values['basic-singulars'] = __( 'All Singulars', 'surerank' );
			$values['basic-archives']  = __( 'All Archives', 'surerank' );
		}

		return $values;
	}

	/**
	 * Get special pages.
	 *
	 * @return array<string, string> Special pages.
	 */
	private static function get_special_pages(): array {
		$special_pages = [
			'special-404'    => __( '404 Page', 'surerank' ),
			'special-search' => __( 'Search Page', 'surerank' ),
			'special-blog'   => __( 'Blog / Posts Page', 'surerank' ),
			'special-front'  => __( 'Front Page', 'surerank' ),
			'special-date'   => __( 'Date Archive', 'surerank' ),
			'special-author' => __( 'Author Archive', 'surerank' ),
		];

		if ( class_exists( 'WooCommerce' ) ) {
			$special_pages['special-woo-shop'] = __( 'WooCommerce Shop Page', 'surerank' );
		}

		return $special_pages;
	}

	/**
	 * Add WooCommerce options.
	 *
	 * @param array<string, mixed> $selection_options Selection options.
	 * @return void
	 */
	private static function add_woocommerce_options( array &$selection_options ): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$product_type_options = self::get_product_type_options();
		if ( ! empty( $product_type_options ) ) {
			$selection_options['product-types'] = [
				'label' => __( 'Product Types', 'surerank' ),
				'value' => $product_type_options,
			];
		}
	}

	/**
	 * Get product type options.
	 *
	 * @return array<string, string> Product type options.
	 */
	private static function get_product_type_options(): array {
		$product_types = wc_get_product_types();
		$options       = [];

		foreach ( $product_types as $type_key => $type_label ) {
			$options[ 'product-type|' . $type_key ] = $type_label;
		}

		return $options;
	}

	/**
	 * Add taxonomy options.
	 *
	 * @param array<string, mixed>         $selection_options Selection options.
	 * @param array<string, \WP_Post_Type> $post_types Post types.
	 * @param mixed                        $consider_type Consider type.
	 * @return void
	 */
	private static function add_taxonomy_options( array &$selection_options, array $post_types, $consider_type ): void {
		$taxonomies = self::get_public_taxonomies();
		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			self::process_taxonomy_for_post_types( $selection_options, $taxonomy, $post_types, $consider_type );
		}
	}

	/**
	 * Get public taxonomies.
	 *
	 * @return array<string, \WP_Taxonomy> Public taxonomies.
	 */
	private static function get_public_taxonomies(): array {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
		return array_filter( $taxonomies, static fn( $tax ) => $tax instanceof \WP_Taxonomy );
	}

	/**
	 * Process taxonomy for all post types.
	 *
	 * @param array<string, mixed>         $selection_options Selection options.
	 * @param \WP_Taxonomy                 $taxonomy Taxonomy.
	 * @param array<string, \WP_Post_Type> $post_types Post types.
	 * @param mixed                        $consider_type Consider type.
	 * @return void
	 */
	private static function process_taxonomy_for_post_types( array &$selection_options, \WP_Taxonomy $taxonomy, array $post_types, $consider_type ): void {
		foreach ( $post_types as $post_type ) {
			$post_opt = self::get_post_target_rule_options( $post_type, $taxonomy, $consider_type );
			if ( empty( $post_opt ) ) {
				continue;
			}

			self::merge_post_option( $selection_options, $post_opt );
		}
	}

	/**
	 * Merge post option into selection options.
	 *
	 * @param array<string, mixed> $selection_options Selection options.
	 * @param array<string, mixed> $post_opt Post option.
	 * @return void
	 */
	private static function merge_post_option( array &$selection_options, array $post_opt ): void {
		$key = $post_opt['post_key'];

		if ( ! isset( $selection_options[ $key ] ) ) {
			$selection_options[ $key ] = [
				'label' => $post_opt['label'],
				'value' => $post_opt['value'],
			];
			return;
		}

		self::merge_option_values( $selection_options[ $key ]['value'], $post_opt['value'] );
	}

	/**
	 * Merge option values.
	 *
	 * @param array<string, mixed> $existing_values Existing values.
	 * @param mixed                $new_values New values.
	 * @return void
	 */
	private static function merge_option_values( array &$existing_values, $new_values ): void {
		if ( ! is_array( $new_values ) || empty( $new_values ) ) {
			return;
		}

		foreach ( $new_values as $key => $value ) {
			if ( ! in_array( $value, $existing_values, true ) ) {
				$existing_values[ $key ] = $value;
			}
		}
	}

	/**
	 * Get specific target option.
	 *
	 * @return array<string, mixed> Specific target option.
	 */
	private static function get_specific_target_option(): array {
		return [
			'label' => __( 'Specific Target', 'surerank' ),
			'value' => [
				'specifics' => __( 'Specific Pages / Posts / Taxonomies, etc.', 'surerank' ),
			],
		];
	}
}
