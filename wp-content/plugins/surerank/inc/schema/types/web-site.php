<?php
/**
 * WebSite Schema
 *
 * This file handles the functionality for the WebSite schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * WebSite
 * This class handles the functionality for the WebSite schema type.
 *
 * @since 1.0.0
 */
class WebSite extends Base {

	use Get_Instance;

	/**
	 * Get Schema Types
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'WebSite',
			'type'    => 'WebSite',
			'show_on' => [
				'rules'        => [
					'basic-global',
				],
				'specific'     => [],
				'specificText' => [],
			],
			'fields'  => $this->parse_fields( $this->get() ),
		];
	}

	/**
	 * Get WebSite Schema
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_website',
			[
				[
					'id'       => '@id',
					'type'     => 'Hidden',
					'std'      => '%site.url%#%id%',
					'required' => true,
				],
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'WebSite',
				],
				$this->add_helper_property(
					'name',
					[
						'required' => true,
						'std'      => '%site.title%',
						'tooltip'  => __( 'Enter the official name of your website. This name will be used in structured data to represent your site in search results.', 'surerank' ),
					]
				),
				[
					'id'      => 'author',
					'label'   => __( 'Author', 'surerank' ),
					'tooltip' => __( 'The author of the website.', 'surerank' ),
					'std'     => '%schemas.person%',
				],
				[
					'id'          => 'copyrightHolder',
					'label'       => __( 'Copyright holder', 'surerank' ),
					'std'         => '%schemas.person%',
					'tooltip'     => __( 'The party holding the legal copyright to the page.', 'surerank' ),
					'description' => __( 'Please create a Person or an Organization schema and link to this property via a dynamic variable', 'surerank' ),
				],
				[
					'id'      => 'description',
					'show'    => true,
					'label'   => __( 'Description', 'surerank' ),
					'tooltip' => __( 'Write a brief description of what your website is about. This summary helps search engines understand the site’s purpose and content.', 'surerank' ),
					'std'     => '%site.description%',
				],
				$this->add_helper_property(
					'url',
					[
						'required' => true,
						'std'      => '%site.url%',
					]
				),
				[
					'id'    => 'potentialAction',
					'label' => __( 'Potential action', 'surerank' ),
					'std'   => '%schemas.searchaction%',
					'show'  => true,
				],
				[
					'id'      => 'publisher',
					'label'   => __( 'Publisher', 'surerank' ),
					'std'     => '%schemas.organization%',
					'tooltip' => __( 'Specify the individual or organization that owns or publishes the website. This will be reflected in the structured data for clear attribution.', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'thumbnailUrl',
					'label'   => __( 'Thumbnail URL', 'surerank' ),
					'tooltip' => __( 'A thumbnail image relevant to the website.', 'surerank' ),
				],
			]
		);
	}
}
