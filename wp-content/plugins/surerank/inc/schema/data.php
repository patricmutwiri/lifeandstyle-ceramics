<?php
/**
 * Data Class
 *
 * Responsible for collecting and processing data for schemas.
 *
 * @since 1.0.0
 * @package SureRank
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\Frontend\Breadcrumbs;
use SureRank\Inc\Frontend\Description;
use SureRank\Inc\Traits\Get_Instance;
use WP_Post;
use WP_Term;
use WP_User;

/**
 * Data Class
 *
 * Responsible for collecting and processing data for schemas.
 *
 * @since 1.0.0
 */
class Data {
	use Get_Instance;
	/**
	 * Holds the collected schema data.
	 *
	 * @var array<string, mixed>
	 */
	private $data = [];

	/**
	 * Holds the queried object.
	 *
	 * @var mixed
	 */
	private $queried_object = null;

	/**
	 * Collects all data for schema rendering.
	 *
	 * @return array<string, mixed>Collected schema data.
	 */
	public function collect() {
		$this->data = [
			'post'            => $this->get_post_data(),
			'term'            => $this->get_term_data(),
			'author'          => $this->get_author_data(),
			'user'            => $this->get_user_data(),
			'site'            => $this->get_site_data(),
			'current'         => $this->get_current_data(),
			'schemas'         => $this->get_schema_links(),
			'website_details' => $this->get_website_details(),
		];

		$this->data = apply_filters( 'surerank_schema_data', $this->data );

		// Normalize post content and calculate word count.
		if ( isset( $this->data['post']['content'] ) ) {
			$post_content                     = esc_html( (string) $this->data['post']['content'] );
			$this->data['post']['content']    = $post_content;
			$this->data['post']['word_count'] = str_word_count( $post_content );
		}

		return $this->data;
	}

	/**
	 * Retrieves schema links.
	 *
	 * @return array<string, mixed>The schema links data.
	 */
	public function get_schema_links() {
		$schemas = Schemas::get_instance()->get_active_schemas();
		$data    = [];
		return $this->get_schema_links_data( $schemas );
	}

	/**
	 * Retrieves schema links data.
	 *
	 * @param array<string, mixed> $schemas The schema data.
	 * @return array<string, mixed>The schema links data.
	 */
	public function get_schema_links_data( array $schemas ) {
		$data = [];
		foreach ( $schemas as $schema ) {

			$id = $this->get_id( $schema );
			if ( $this->add_breadcrumb( $schema ) ) {
				continue;
			}

			$data[ $id ] = [
				'@id' => $this->get_id_value( $schema ),
			];
		}
		return $data;
	}

	/**
	 * Adds a breadcrumb to a schema.
	 *
	 * @param array<string, mixed> $schema The schema data.
	 * @return bool True if the breadcrumb should be added, false otherwise.
	 */
	public function add_breadcrumb( array $schema ): bool {
		$rules = $schema['not_show_on']['rules'] ?? [];

		if ( is_front_page() && in_array( 'special-front', $rules ) && 'BreadcrumbList' === $schema['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the ID for a schema.
	 *
	 * @param array<string, mixed> $schema The schema array.
	 * @return string The sanitized schema ID.
	 */
	public function get_id( array $schema ): string {
		$label = $this->get( $schema, 'fields._label', $schema['type'] );
		return $this->sanitize_id( $label );
	}

	/**
	 * Retrieves the ID value for a schema.
	 *
	 * @param array<string, mixed> $schema The schema array.
	 * @return string The ID value.
	 */
	public function get_id_value( array $schema ): string {
		$id = $this->get( $schema, 'fields.@id', '%current.url%#%id%' );
		return str_replace( '%id%', $this->get_id( $schema ), $id );
	}

	/**
	 * Retrieves a value from an array using dot notation.
	 *
	 * @param array<string, mixed> $array   The array to search.
	 * @param string               $key     The dot-notated key.
	 * @param mixed                $default The default value if the key is not found.
	 * @return mixed The value from the array or the default value.
	 */
	public function get( $array, $key, $default = null ) {
		if ( ! $key ) {
			return $array;
		}

		$keys = explode( '.', $key );
		foreach ( $keys as $key ) {
			if ( isset( $array[ $key ] ) ) {
				$array = $array[ $key ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	/**
	 * Retrieves the schema data.
	 *
	 * @param array<string, mixed> $schema The schema data.
	 * @return array<string, mixed>The schema data.
	 */
	public static function get_schema_type( $schema ) {

		$fields = $schema['fields'] ?? [];
		$label  = self::get_instance()->get_id( $schema );

		if ( isset( $fields['@id'] ) ) {
			$fields['@id'] = str_replace( '%id%', $label, $fields['@id'] );
		}

		return $fields;
	}

	/**
	 * Retrieves the queried object and caches it.
	 *
	 * @return mixed The queried object.
	 */
	private function get_queried_object() {
		if ( null === $this->queried_object ) {
			$this->queried_object = \get_queried_object();
		}
		return $this->queried_object;
	}

	/**
	 * Retrieves post data.
	 *
	 * @return array<string, mixed>The post data.
	 */
	private function get_post_data() {
		$post = $this->get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return [];
		}

		if ( ! $post->ID ) {
			return [];
		}

		$taxonomies = Helper::get_instance()->get_taxonomies();

		$taxonomies_data = [];
		foreach ( $taxonomies as $taxonomy ) {
			$taxonomies_data[ $taxonomy['slug'] ] = $this->get_terms( $post->ID, $taxonomy['slug'] );
		}

		return [
			'ID'            => (int) $post->ID,
			'title'         => sanitize_text_field( $post->post_title ),
			'excerpt'       => Description::get_instance()->post( $post->ID ),
			'content'       => sanitize_text_field( $post->post_content ),
			'url'           => get_permalink( $post ),
			'slug'          => sanitize_title( $post->post_name ),
			'date'          => get_the_date( 'c', $post ),
			'modified_date' => get_the_modified_date( 'c', $post ),
			'created_date'  => get_the_date( 'c', $post ),
			'thumbnail'     => get_the_post_thumbnail_url( $post->ID, 'full' ),
			'comment_count' => (int) $post->comment_count,
			'tags'          => sanitize_text_field( $this->get_terms( $post->ID, 'post_tag' ) ),
			'categories'    => sanitize_text_field( $this->get_terms( $post->ID, 'category' ) ),
			'custom_field'  => array_map( 'sanitize_text_field', $this->get_custom_field_data( $post->ID ) ),
			'taxonomies'    => array_map( 'sanitize_text_field', $this->get_taxonomies_for_post( $post->ID ) ),
			'tax'           => $taxonomies_data,
		];
	}

	/**
	 * Retrieves term data.
	 *
	 * @return array<string, mixed>The term data.
	 */
	private function get_term_data() {
		$term = $this->get_queried_object();

		if ( ! $term instanceof WP_Term ) {
			return [];
		}

		if ( ! $term->term_id ) {
			return [];
		}

		return [
			'ID'          => (int) $term->term_id,
			'name'        => sanitize_text_field( $term->name ),
			'slug'        => sanitize_title( $term->slug ),
			'taxonomy'    => sanitize_text_field( $term->taxonomy ),
			'description' => Description::get_instance()->taxonomy( $term->term_id ),
			'url'         => get_term_link( $term->term_id ),
		];
	}

	/**
	 * Retrieves author data for the current post.
	 *
	 * @return array<string, mixed>The author data.
	 */
	private function get_author_data() {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return [];
		}

		$author_id = (int) get_post_field( 'post_author', $post_id );
		return $this->get_user( $author_id );
	}

	/**
	 * Retrieves current user data.
	 *
	 * @return array<string, mixed>The current user data.
	 */
	private function get_user_data() {
		return $this->get_user( get_current_user_id() );
	}

	/**
	 * Retrieves user data by ID.
	 *
	 * @param int $user_id The user ID.
	 * @return array<string, mixed>The user data.
	 */
	private function get_user( int $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return [];
		}

		return [
			'ID'           => (int) $user->ID,
			'first_name'   => sanitize_text_field( $user->first_name ),
			'last_name'    => sanitize_text_field( $user->last_name ),
			'username'     => sanitize_text_field( $user->user_login ),
			'display_name' => sanitize_text_field( $user->display_name ),
			'nickname'     => sanitize_text_field( $user->nickname ),
			'email'        => sanitize_email( $user->user_email ),
			'website_url'  => esc_url( $user->user_url ),
			'nicename'     => sanitize_title( $user->user_nicename ),
			'description'  => esc_html( $user->description ),
			'posts_url'    => get_author_posts_url( $user->ID ),
			'avatar'       => get_avatar_url( $user->ID ),
		];
	}

	/**
	 * Retrieves site data.
	 *
	 * @return array<string, mixed>The site data.
	 */
	private function get_site_data() {
		return [
			'title'       => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'url'         => home_url( '/' ),
			'language'    => get_locale(),
			'icon'        => get_site_icon_url(),
		];
	}

	/**
	 * Retrieves current page data.
	 *
	 * @return array<string, mixed>The current page data.
	 */
	private function get_current_data() {
		global $wp;

		$bread       = Breadcrumbs::get_instance()->get_crumbs();
		$breadcrumbs = [];
		foreach ( $bread as $index => $crumb ) {
			$breadcrumbs[] = [
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'item'     => [
					'@id'  => $crumb['link'],
					'name' => $crumb['name'],
				],
			];
		}

		return [
			'url'         => home_url( $wp->request ),
			'breadcrumbs' => $breadcrumbs,
			'title'       => $this->get_title(),
		];
	}

	/**
	 * Retrieves the title.
	 *
	 * @return string The title.
	 */
	private function get_title(): string {
		$post = ! is_singular() ? $this->get_queried_object() : get_post();

		if ( $post instanceof WP_Post ) {
			return $post->post_title;
		}

		if ( $post instanceof WP_Term ) {

			return $post->name;
		}

		if ( $post instanceof WP_User ) {
			return $post->display_name;
		}

		return get_the_title();
	}

	/**
	 * Retrieves terms for a post by taxonomy.
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $taxonomy The taxonomy slug.
	 * @return string The terms as a comma-separated string.
	 */
	private function get_terms( int $post_id, string $taxonomy ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		return is_array( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';
	}

	/**
	 * Retrieves taxonomies for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string, mixed>The taxonomies data.
	 */
	private function get_taxonomies_for_post( int $post_id ) {
		$taxonomies = get_object_taxonomies( 'post', 'objects' );
		$data       = [];

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy->name );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$data[ $taxonomy->name ] = wp_list_pluck( $terms, 'name' );
			}
		}

		return $data;
	}

	/**
	 * Retrieves custom field data for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string, mixed>The custom field data.
	 */
	private function get_custom_field_data( int $post_id ) {
		$meta_values = get_post_meta( $post_id );

		if ( empty( $meta_values ) || ! is_array( $meta_values ) ) {
			return [];
		}

		// Filter out meta values that are not arrays or are empty.
		return array_map(
			static function ( $value ) {
				return reset( $value );
			},
			$meta_values
		);
	}

	/**
	 * Retrieves website details data from onboarding.
	 *
	 * @return array<string, mixed>The website details data.
	 */
	private function get_website_details() {
		$onboarding_data = get_option( 'surerank_settings_onboarding', [] );

		if ( empty( $onboarding_data ) || ! is_array( $onboarding_data ) ) {
			return [];
		}

		$social_profiles = [];
		if ( isset( $onboarding_data['social_profiles'] ) && is_array( $onboarding_data['social_profiles'] ) ) {
			foreach ( $onboarding_data['social_profiles'] as $key => $url ) {
				$social_profiles[ $key ] = esc_url( is_scalar( $url ) ? strval( $url ) : '' );
			}
		}

		return [
			'website_type'         => sanitize_text_field( $onboarding_data['website_type'] ?? '' ),
			'website_name'         => sanitize_text_field( $onboarding_data['website_name'] ?? '' ),
			'business_description' => sanitize_text_field( $onboarding_data['business_description'] ?? '' ),
			'website_owner_name'   => sanitize_text_field( $onboarding_data['website_owner_name'] ?? '' ),
			'organization_type'    => sanitize_text_field( $onboarding_data['organization_type'] ?? 'Organization' ),
			'website_owner_phone'  => sanitize_text_field( $onboarding_data['website_owner_phone'] ?? '' ),
			'website_logo'         => esc_url( is_scalar( $onboarding_data['website_logo'] ?? '' ) ? strval( $onboarding_data['website_logo'] ) : '' ),
			'about_page'           => absint( $onboarding_data['about_page'] ?? 0 ),
			'contact_page'         => absint( $onboarding_data['contact_page'] ?? 0 ),
			'social_profiles'      => $social_profiles,
		];
	}

	/**
	 * Sanitizes a string for use as an ID.
	 *
	 * @param string $text The input text.
	 * @return string The sanitized ID.
	 */
	private function sanitize_id( $text ) {

		if ( ! $text || ! is_string( $text ) ) {
			return '';
		}

		$id = sanitize_title( $text );
		$id = preg_replace( '/[^a-z0-9_]/', '_', (string) $id ); // Only accepts alphanumeric and underscores.
		$id = preg_replace( '/[ _]{2,}/', '_', (string) $id );   // Remove duplicated `_`.
		$id = trim( (string) $id, '_' );                        // Trim `_`.
		$id = preg_replace( '/^\d+/', '', (string) $id );       // Don't start with numbers.
		$id = trim( (string) $id, '_' );                        // Trim `_` again.

		return esc_attr( $id );
	}
}
