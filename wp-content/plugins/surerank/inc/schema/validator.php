<?php
/**
 * Validator
 *
 * This file handles the validation of schema rules for determining visibility
 * based on specified conditions.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

/**
 * Validator class
 *
 * Handles the validation of schema rules for determining visibility
 * based on specified conditions.
 */
class Validator {

	/**
	 * Validate Schema Rules
	 *
	 * Determines if the schema should be displayed based on `show_on`
	 * and `not_show_on` rules.
	 *
	 * @param array<string, mixed> $schema Schema data.
	 * @param string               $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param int                  $post_id Post ID is from api request.
	 * @param bool                 $is_taxonomy Whether the post is a taxonomy.
	 * @return bool True if schema should be displayed, false otherwise.
	 */
	public static function validate_schema_rules( $schema, $post_type = '', $post_id = 0, $is_taxonomy = false ) {

		// if schema has parent key, and it is true, we will return true, because we are using post meta data for schema now.
		if ( isset( $schema['parent'] ) && $schema['parent'] ) {
			return true;
		}

		$show_on_rules        = $schema['show_on']['rules'] ?? [];
		$show_on_specific     = $schema['show_on']['specific'] ?? [];
		$not_show_on_rules    = $schema['not_show_on']['rules'] ?? [];
		$not_show_on_specific = $schema['not_show_on']['specific'] ?? [];

		$show_on_match     = false;
		$not_show_on_match = false;

		if ( empty( $show_on_rules ) && empty( $show_on_specific ) && empty( $not_show_on_rules ) && empty( $not_show_on_specific ) ) {
			return false;
		}

		if ( ! empty( $show_on_rules ) || ! empty( $show_on_specific ) ) {
			$show_on_match = self::evaluate_rules( $show_on_rules, $post_type, $is_taxonomy, $post_id ) ||
							self::evaluate_specifics( $show_on_specific, $post_type, $post_id );
		}

		if ( ! empty( $not_show_on_rules ) || ! empty( $not_show_on_specific ) ) {
			$not_show_on_match = self::evaluate_rules( $not_show_on_rules, $post_type, $is_taxonomy, $post_id ) ||
								self::evaluate_specifics( $not_show_on_specific, $post_type, $post_id );

		}

		// - `show_on` must match (true).
		// - `not_show_on` must NOT match (false).
		return $show_on_match && ! $not_show_on_match;
	}

	/**
	 * Evaluate Rules
	 *
	 * Evaluates an array of rules to check if any match the current context.
	 *
	 * @param array<string, mixed> $rules Rules to evaluate.
	 * @param string               $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param bool                 $is_taxonomy Whether the post is a taxonomy.
	 * @param int                  $post_id Post ID is from api request.
	 * @return bool True if a rule matches, false otherwise.
	 */
	private static function evaluate_rules( $rules, $post_type, $is_taxonomy = false, $post_id = 0 ) {
		if ( empty( $rules ) ) {
			return false;
		}

		foreach ( $rules as $rule ) {
			if ( self::matches_current_context( $rule, $post_type, $is_taxonomy, $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Evaluate Specifics
	 *
	 * Evaluates specific items to determine if any match the current context.
	 *
	 * @param array<string, mixed> $specifics Specific items to evaluate.
	 * @param string               $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param int                  $post_id Post ID is from api request.
	 * @return bool True if a specific item matches, false otherwise.
	 */
	private static function evaluate_specifics( $specifics, $post_type, $post_id ) {
		if ( empty( $specifics ) ) {
			return false;
		}

		foreach ( $specifics as $specific ) {
			if ( self::matches_specific_item( $specific, $post_type, $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if Rule Matches Current Context
	 *
	 * Evaluates a single rule to determine if it matches the current WordPress context.
	 *
	 * @param string $rule Rule to check.
	 * @param string $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param bool   $is_taxonomy Whether the post is a taxonomy.
	 * @param int    $post_id Post ID is from api request.
	 * @return bool True if the rule matches, false otherwise.
	 */
	private static function matches_current_context( $rule, $post_type, $is_taxonomy = false, $post_id = 0 ) {
		$rule_parts = explode( '|', $rule );
		$rule_type  = $rule_parts[0];

		// Check basic rules first.
		$basic_result = self::check_basic_rules( $rule_type, $is_taxonomy );
		if ( $basic_result !== null ) {
			return $basic_result;
		}

		// Check special page rules.
		$special_result = self::check_special_rules( $rule_type, $is_taxonomy, $post_id );
		if ( $special_result !== null ) {
			return $special_result;
		}

		// Check post type specific rules.
		return self::check_post_type_specific_rules( $rule_type, $rule_parts, $post_type, $post_id );
	}

	/**
	 * Check basic rules.
	 *
	 * @param string $rule_type Rule type.
	 * @param bool   $is_taxonomy Is taxonomy.
	 * @return bool|null Result or null if not applicable.
	 */
	private static function check_basic_rules( string $rule_type, bool $is_taxonomy ) {
		switch ( $rule_type ) {
			case 'basic-global':
				return true;
			case 'basic-singulars':
				return is_singular() || ! $is_taxonomy;
			case 'basic-archives':
				return is_archive() || $is_taxonomy;
		}
		return null;
	}

	/**
	 * Check special page rules.
	 *
	 * @param string $rule_type Rule type.
	 * @param bool   $is_taxonomy Is taxonomy.
	 * @param int    $post_id Post ID.
	 * @return bool|null Result or null if not applicable.
	 */
	private static function check_special_rules( string $rule_type, bool $is_taxonomy, $post_id ) {
		switch ( $rule_type ) {
			case 'special-404':
				return is_404();
			case 'special-search':
				return is_search();
			case 'special-blog':
				return is_home();
			case 'special-front':
				return self::is_front_page_context( $post_id, $is_taxonomy );
			case 'special-date':
				return is_date() || $is_taxonomy;
			case 'special-author':
				return is_author() || $is_taxonomy;
		}

		return null;
	}

	/**
	 * Check if current context is front page.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $is_taxonomy Is taxonomy.
	 * @return bool True if front page context.
	 */
	private static function is_front_page_context( int $post_id, bool $is_taxonomy ) {
		$frontpage_id = get_option( 'page_on_front' );
		if ( $frontpage_id && ( (int) $post_id === (int) $frontpage_id ) ) {
			return true;
		}

		return is_front_page() || $is_taxonomy;
	}

	/**
	 * Check post type specific rules.
	 *
	 * @param string                                  $rule_type Rule type.
	 * @param array<string, mixed>|array<int, string> $rule_parts Rule parts.
	 * @param string                                  $post_type Post type.
	 * @param int                                     $post_id Post ID.
	 * @return bool Result.
	 */
	private static function check_post_type_specific_rules( string $rule_type, array $rule_parts, string $post_type, int $post_id ): bool {
		switch ( $rule_type ) {
			case 'post':
				return self::handle_post_type_rules( $rule_parts, $post_type, 'post' );
			case 'page':
				return self::handle_page_rules( $rule_parts, $post_type );
			case 'product-type':
				return self::handle_product_type_rules( $rule_parts, $post_type, $post_id );
			case 'product':
				return self::handle_product_rules( $rule_parts, $post_type );
			default:
				if ( post_type_exists( $rule_type ) ) {
					return self::handle_custom_post_type_rules( $rule_parts, $post_type );
				}
				return false;
		}
	}

	/**
	 * Handle Product Type Rules
	 *
	 * Evaluate product type-specific rules.
	 *
	 * @param array<string, mixed>|array<int, string> $rule_parts Rule parts array.
	 * @param string                                  $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param int                                     $post_id Post ID is from api request.
	 * @return bool True if matches, false otherwise.
	 */
	private static function handle_product_type_rules( $rule_parts, $post_type, $post_id ) {

		if ( ! function_exists( 'wc_get_product' ) ) {
			return false;
		}

		if ( 'product' !== $post_type && ! is_singular( 'product' ) && ! $post_id ) {
			return false;
		}

		$product_type = $rule_parts[1] ?? '';
		if ( empty( $product_type ) ) {
			return false;
		}

		$product = null;

		if ( is_singular( 'product' ) ) {
			global $post;
			$product_id = $post->ID;
		} else {
			$product_id = get_the_ID();
			$product_id = $product_id ? $product_id : $post_id;
		}

		$product = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			return false;
		}

		$product_type = $product->get_type();

		if ( $product_type === $rule_parts[1] ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle Custom Post Type Rules
	 *
	 * Evaluate custom post type-specific rules.
	 *
	 * @param array<string, mixed>|array<int, string> $rule_parts Rule parts array.
	 * @param string                                  $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @return bool True if matches, false otherwise.
	 */
	private static function handle_custom_post_type_rules( $rule_parts, $post_type ) {
		return self::handle_post_type_rules( $rule_parts, $post_type, $rule_parts[0] );
	}

	/**
	 * Handle Page Rules
	 *
	 * Evaluate page-specific rules.
	 *
	 * @param array<string, mixed>|array<int, string> $rule_parts Rule parts array.
	 * @param string                                  $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @return bool True if matches, false otherwise.
	 */
	private static function handle_page_rules( $rule_parts, $post_type ) {
		switch ( $rule_parts[1] ?? '' ) {
			case 'all':
				return is_page() || 'page' === $post_type;
			case 'front':
				return is_front_page();
			default:
				return false;
		}
	}

	/**
	 * Handle Product Rules
	 *
	 * Evaluate product-specific rules.
	 *
	 * @param array<string, mixed>|array<int, string> $rule_parts Rule parts array.
	 * @param string                                  $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @return bool True if matches, false otherwise.
	 */
	private static function handle_product_rules( $rule_parts, $post_type ) {
		return self::handle_post_type_rules( $rule_parts, $post_type, 'product' );
	}

	/**
	 * Handle Post Type Rules
	 *
	 * Evaluate post type-specific rules.
	 *
	 * @param array<string, mixed>|array<int, string> $rule_parts Rule parts array.
	 * @param string                                  $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param string                                  $default_type Default post type (e.g., 'product', 'post', 'custom_post').
	 * @return bool True if matches, false otherwise.
	 */
	private static function handle_post_type_rules( $rule_parts, $post_type, $default_type ) {
		switch ( $rule_parts[1] ?? '' ) {
			case 'all':
				if ( isset( $rule_parts[2] ) ) {
					switch ( $rule_parts[2] ) {
						case 'archive':
							return is_post_type_archive( $default_type );
						case 'taxarchive':
							$taxonomy = $rule_parts[3] ?? '';
							if ( 'category' === $taxonomy && 'post' !== $default_type ) {
								return is_category();
							}
							if ( 'post_tag' === $taxonomy && 'post' !== $default_type ) {
								return is_tag();
							}
							if ( is_tax( $taxonomy ) ) {
								return true;
							}
							return $post_type === $taxonomy;
					}
				}
				return is_singular( $default_type ) || $post_type === $default_type;
			case 'archive':
				return is_post_type_archive( $default_type );
			default:
				return false;
		}
	}

	/**
	 * Check if Specific Item Matches
	 *
	 * Determines if a specific item (e.g., post ID or product ID) matches the current context.
	 *
	 * @param string $specific Specific item (e.g., post ID).
	 * @param string $post_type Post type is the post_type we are getting from API request to show in settings.
	 * @param int    $post_id Post ID is from api request.
	 * @return bool True if the specific item matches, false otherwise.
	 */
	private static function matches_specific_item( $specific, $post_type, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$parsed_item = self::parse_specific_item( $specific );
		if ( ! $parsed_item ) {
			return false;
		}

		$type = $parsed_item['type'];
		$id   = $parsed_item['id'];

		if ( self::is_post_type( $type ) ) {
			return self::matches_post_type_item( $id, $post_id );
		}

		if ( 'tax' === $type ) {
			return self::matches_taxonomy_item( $id, $parsed_item['parts'], $post_id );
		}

		return false;
	}

	/**
	 * Parse specific item string into components.
	 *
	 * @param string $specific Specific item string.
	 * @return array{type: string, id: int, parts: array<int, string>}|false Array with type, id, and parts or false if invalid.
	 */
	private static function parse_specific_item( string $specific ) {
		$specific_parts = explode( '-', $specific );

		if ( count( $specific_parts ) < 2 ) {
			return false;
		}

		return [
			'type'  => $specific_parts[0],
			'id'    => (int) $specific_parts[1],
			'parts' => $specific_parts,
		];
	}

	/**
	 * Check if type is a valid post type.
	 *
	 * @param string $type Type to check.
	 * @return bool True if valid post type.
	 */
	private static function is_post_type( string $type ) {
		$post_type_array = [ 'post', 'page', 'product' ];
		return in_array( $type, $post_type_array, true );
	}

	/**
	 * Check if post type item matches.
	 *
	 * @param int $id Item ID.
	 * @param int $post_id Post ID from request.
	 * @return bool True if matches.
	 */
	private static function matches_post_type_item( int $id, int $post_id ) {
		global $post;

		if ( isset( $post ) ) {
			return (int) $id === $post->ID;
		}

		if ( ! empty( $post_id ) ) {
			return (int) $id === $post_id;
		}

		return false;
	}

	/**
	 * Check if taxonomy item matches.
	 *
	 * @param int                $id Term ID.
	 * @param array<int, string> $parts Specific parts array.
	 * @param int                $post_id Post ID from request.
	 * @return bool True if matches.
	 */
	private static function matches_taxonomy_item( int $id, array $parts, int $post_id ): bool {
		// Check if post has the term (single context).
		if ( isset( $parts[2] ) && 'single' === $parts[2] ) {
			return self::post_has_term( $id, $post_id );
		}

		// Check if on taxonomy archive page.
		if ( self::is_taxonomy_archive( $id ) ) {
			return true;
		}

		// Direct ID match.
		return $id === $post_id;
	}

	/**
	 * Check if post has specific term.
	 *
	 * @param int $term_id Term ID.
	 * @param int $post_id Post ID.
	 * @return bool True if post has term.
	 */
	private static function post_has_term( int $term_id, int $post_id ) {
		global $post;

		$term = get_term( $term_id );
		if ( ! $term instanceof \WP_Term ) {
			return false;
		}

		$post_check_id = is_singular() ? $post->ID : $post_id;
		if ( ! $post_check_id ) {
			return false;
		}

		return has_term( $term_id, $term->taxonomy, $post_check_id ) && (int) $term_id === $term->term_id;
	}

	/**
	 * Check if current page is taxonomy archive with specific ID.
	 *
	 * @param int $term_id Term ID to check.
	 * @return bool True if on matching taxonomy archive.
	 */
	private static function is_taxonomy_archive( int $term_id ) {
		if ( ! ( is_tax() || is_category() || is_tag() ) ) {
			return false;
		}

		$queried_object = get_queried_object();
		return $queried_object instanceof \WP_Term && $term_id === $queried_object->term_id;
	}

}
