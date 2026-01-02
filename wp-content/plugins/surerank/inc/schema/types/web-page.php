<?php
/**
 * WebPage Schema
 *
 * This file handles the functionality for the WebPage schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * WebPage
 * This class handles the functionality for the WebPage schema type.
 *
 * @since 1.0.0
 */
class WebPage extends Base {

	use Get_Instance;

	/**
	 * Get Schema Types
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'WebPage',
			'type'    => 'WebPage',
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
	 * Get WebPage Schema
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_webpage',
			[
				[
					'id'       => '@id',
					'type'     => 'Hidden',
					'std'      => '%current.url%#%id%',
					'required' => true,
				],
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'WebPage',
				],
				[
					'id'       => '@type',
					'required' => true,
					'label'    => __( 'Type', 'surerank' ),
					'type'     => 'Select',
					'std'      => 'WebPage',
					'options'  => $this->get_schema_type_options(),
				],
				$this->add_helper_property(
					'name',
					[
						'required' => true,
						'std'      => '%current.title%',
						'tooltip'  => __( 'Enter the title of the webpage as it should appear in search results. This name is included in structured data to describe the specific page.', 'surerank' ),
					]
				),
				[
					'id'      => 'author',
					'label'   => __( 'Author', 'surerank' ),
					'tooltip' => __( 'The author of the page.', 'surerank' ),
					'std'     => '%schemas.person%',
				],
				[
					'id'      => 'inLanguage',
					'label'   => __( 'In language', 'surerank' ),
					'tooltip' => __( 'Specify the language used on the webpage using a standard IETF BCP 47 language code (e.g., en-US). This helps search engines serve content to the appropriate audience.', 'surerank' ),
					'std'     => '%site.language%',
					'show'    => true,
				],
				$this->add_helper_property(
					'url',
					[
						'required' => true,
						'std'      => '%current.url%',
					]
				),
				[
					'label' => __( 'BreadcrumbList', 'surerank' ),
					'id'    => 'breadcrumb',
					'std'   => '%schemas.breadcrumblist%',
					'show'  => true,
				],
				[
					'id'          => 'contributor',
					'label'       => __( 'Contributor', 'surerank' ),
					'std'         => '%schemas.person%',
					'tooltip'     => __( 'A secondary contributor to the page', 'surerank' ),
					'description' => __( 'Please create a Person or an Organization schema and link to this property via a dynamic variable', 'surerank' ),
				],
				[
					'id'          => 'copyrightHolder',
					'label'       => __( 'Copyright holder', 'surerank' ),
					'std'         => '%schemas.person%',
					'tooltip'     => __( 'The party holding the legal copyright to the page.', 'surerank' ),
					'description' => __( 'Please create a Person or an Organization schema and link to this property via a dynamic variable', 'surerank' ),
				],
				$this->add_helper_property(
					'datePublished',
					[
						'tooltip' => __( 'Date the page was created.', 'surerank' ),
						'std'     => '%post.created_date%',
					]
				),
				$this->add_helper_property(
					'dateModified',
					[
						'tooltip' => __( 'Date of modification.', 'surerank' ),
						'std'     => '%post.modified_date%',
					]
				),
				[
					'id'      => 'description',
					'show'    => true,
					'label'   => __( 'Description', 'surerank' ),
					'tooltip' => __( 'Write a short summary that describes the content of the webpage. This description is used in structured data to give search engines more context.', 'surerank' ),
				],
				[
					'label' => __( 'Is part of', 'surerank' ),
					'id'    => 'isPartOf',
					'std'   => '%schemas.website%',
					'show'  => true,
				],
				[
					'id'      => 'publisher',
					'label'   => __( 'Publisher', 'surerank' ),
					'std'     => '%schemas.organization%',
					'tooltip' => __( 'Provide the name of the publisher responsible for the content on the page. This information will appear in structured data to show content ownership.', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'thumbnailUrl',
					'label'   => __( 'Thumbnail URL', 'surerank' ),
					'tooltip' => __( 'A thumbnail image relevant to the page.', 'surerank' ),
					'std'     => '%post.thumbnail%',
				],
				$this->add_helper_property( 'mainEntity' ),
			]
		);
	}

	/**
	 * Get Schema Type Options
	 *
	 * @return array<string, string>
	 * @since 1.0.0
	 */
	public function get_schema_type_options() {
		return apply_filters(
			'surerank_schema_type_webpage_options',
			[
				'WebPage'        => __( 'WebPage', 'surerank' ),
				'CollectionPage' => __( 'CollectionPage', 'surerank' ),
				'AboutPage'      => __( 'AboutPage', 'surerank' ),
				'ContactPage'    => __( 'ContactPage', 'surerank' ),
			]
		);
	}
}
