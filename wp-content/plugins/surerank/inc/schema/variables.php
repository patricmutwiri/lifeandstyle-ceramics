<?php
/**
 * Variables
 *
 * This file handles functionality for all schema variables.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\Traits\Get_Instance;
use WP_Term;

/**
 * Class Variables
 *
 * Handles the functionality for all schema variables.
 *
 * @package SureRank\Inc\Schema
 * @since 1.0.0
 */
class Variables {
	use Get_Instance;

	/**
	 * Schema variables.
	 *
	 * @var mixed
	 * @since 1.0.0
	 */
	private $variables = null;

	/**
	 * Constructor
	 *
	 * Initializes the Variables class and sets up schema variables.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$taxonomies = Helper::get_instance()->get_taxonomies();
		$options    = [];
		foreach ( $taxonomies as $taxonomy ) {
			$key                            = Helper::get_instance()->normalize( $taxonomy['slug'] );
			$options[ "%post.tax.{$key}%" ] = $taxonomy['name'];
		}

		$all_variables = array_merge(
			$options,
			$this->get_post_variables(),
			$this->get_term_variables(),
			$this->get_author_variables(),
			$this->get_user_variables(),
			$this->get_site_variables(),
			$this->get_current_page_variables(),
			$this->get_schema_links(),
			$this->get_website_details_variables(),
		);

		asort( $all_variables );
		$this->variables = $all_variables;
	}

	/**
	 * Get Schema Variables
	 *
	 * @return array<int, array<string, mixed>> Schema variables.
	 */
	public function get_schema_variables() {

		return apply_filters( 'surerank_default_schema_variables', $this->variables );
	}

	/**
	 * Get Post Variables
	 *
	 * @return array<string, mixed> Post variables.
	 */
	private function get_post_variables() {
		return [
			'%post.title%'         => __( 'Post Title', 'surerank' ),
			'%post.ID%'            => __( 'Post ID', 'surerank' ),
			'%post.excerpt%'       => __( 'Post Excerpt', 'surerank' ),
			'%post.content%'       => __( 'Post Content', 'surerank' ),
			'%post.url%'           => __( 'Post URL', 'surerank' ),
			'%post.slug%'          => __( 'Post Slug', 'surerank' ),
			'%post.date%'          => __( 'Post Date', 'surerank' ),
			'%post.modified_date%' => __( 'Post Modified Date', 'surerank' ),
			'%post.thumbnail%'     => __( 'Post Thumbnail', 'surerank' ),
			'%post.comment_count%' => __( 'Post Comment Count', 'surerank' ),
			'%post.word_count%'    => __( 'Post Word Count', 'surerank' ),
			'%post.tags%'          => __( 'Post Tags', 'surerank' ),
			'%post.categories%'    => __( 'Post Categories', 'surerank' ),
		];
	}

	/**
	 * Get Term Variables
	 *
	 * @return array<string, mixed> Term variables.
	 */
	private function get_term_variables() {
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return [];
		}

		return [
			'%term.ID%'          => __( 'Term ID', 'surerank' ),
			'%term.name%'        => __( 'Term Name', 'surerank' ),
			'%term.slug%'        => __( 'Term Slug', 'surerank' ),
			'%term.taxonomy%'    => __( 'Term Taxonomy', 'surerank' ),
			'%term.description%' => __( 'Term Description', 'surerank' ),
			'%term.url%'         => __( 'Term URL', 'surerank' ),
		];
	}

	/**
	 * Get Author Variables
	 *
	 * @return array<string, mixed> Author variables.
	 */
	private function get_author_variables() {
		return [
			'%author.ID%'           => __( 'Author ID', 'surerank' ),
			'%author.first_name%'   => __( 'Author First Name', 'surerank' ),
			'%author.last_name%'    => __( 'Author Last Name', 'surerank' ),
			'%author.display_name%' => __( 'Author Display Name', 'surerank' ),
			'%author.username%'     => __( 'Author Username', 'surerank' ),
			'%author.nickname%'     => __( 'Author Nickname', 'surerank' ),
			'%author.email%'        => __( 'Author Email', 'surerank' ),
			'%author.website_url%'  => __( 'Author Website URL', 'surerank' ),
			'%author.nicename%'     => __( 'Author Nicename', 'surerank' ),
			'%author.description%'  => __( 'Author Description', 'surerank' ),
			'%author.posts_url%'    => __( 'Author Posts URL', 'surerank' ),
			'%author.avatar%'       => __( 'Author Avatar', 'surerank' ),
		];
	}

	/**
	 * Get User Variables
	 *
	 * @return array<string, mixed> User variables.
	 */
	private function get_user_variables() {
		return [
			'%user.ID%'           => __( 'User ID', 'surerank' ),
			'%user.first_name%'   => __( 'User First Name', 'surerank' ),
			'%user.last_name%'    => __( 'User Last Name', 'surerank' ),
			'%user.display_name%' => __( 'User Display Name', 'surerank' ),
			'%user.username%'     => __( 'User Username', 'surerank' ),
			'%user.nickname%'     => __( 'User Nickname', 'surerank' ),
			'%user.email%'        => __( 'User Email', 'surerank' ),
			'%user.website_url%'  => __( 'User Website URL', 'surerank' ),
			'%user.nicename%'     => __( 'User Nicename', 'surerank' ),
			'%user.description%'  => __( 'User Description', 'surerank' ),
			'%user.posts_url%'    => __( 'User Posts URL', 'surerank' ),
			'%user.avatar%'       => __( 'User Avatar', 'surerank' ),
		];
	}

	/**
	 * Get Site Variables
	 *
	 * @return array<string, mixed> Site variables.
	 */
	private function get_site_variables() {
		return [
			'%site.title%'       => __( 'Site Title', 'surerank' ),
			'%site.description%' => __( 'Site Description', 'surerank' ),
			'%site.url%'         => __( 'Site URL', 'surerank' ),
			'%site.language%'    => __( 'Site Language', 'surerank' ),
			'%site.icon%'        => __( 'Site Icon', 'surerank' ),
		];
	}

	/**
	 * Get Current Page Variables
	 *
	 * @return array<string, mixed> Current page variables.
	 */
	private function get_current_page_variables() {
		return [
			'%current.title%' => __( 'Current Page Title', 'surerank' ),
			'%current.url%'   => __( 'Current Page URL', 'surerank' ),
		];
	}

	/**
	 * Get Schema Variables
	 *
	 * @return array<string, mixed> Schema links.
	 * @since 1.0.0
	 */
	private function get_schema_links() {
		$active_schemas = Schemas::get_instance()->get_active_schemas();
		$data           = [];
		foreach ( $active_schemas as $schema ) {
			$type                        = strtolower( $schema['type'] );
			$data[ "%schemas.{$type}%" ] = sprintf(
				/* translators: %s is replaced with the schema type (e.g., "Product", "Article"). */
				__( '%s Schema', 'surerank' ),
				$schema['type']
			);
		}
		return $data;
	}

	/**
	 * Get Website Details Variables
	 *
	 * @return array<string, mixed> Website details variables.
	 * @since 1.6.0
	 */
	private function get_website_details_variables() {
		return [
			'%website_details.website_type%'         => __( 'Website Type', 'surerank' ),
			'%website_details.website_name%'         => __( 'Website Name', 'surerank' ),
			'%website_details.business_description%' => __( 'Business Description', 'surerank' ),
			'%website_details.website_owner_name%'   => __( 'Website Owner Name', 'surerank' ),
			'%website_details.organization_type%'    => __( 'Organization Type', 'surerank' ),
			'%website_details.website_owner_phone%'  => __( 'Website Owner Phone', 'surerank' ),
			'%website_details.website_logo%'         => __( 'Website Logo', 'surerank' ),
			'%website_details.about_page%'           => __( 'About Page ID', 'surerank' ),
			'%website_details.contact_page%'         => __( 'Contact Page ID', 'surerank' ),
		];
	}
}
