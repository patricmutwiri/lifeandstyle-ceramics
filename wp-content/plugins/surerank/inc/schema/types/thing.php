<?php
/**
 * Thing Schema
 *
 * This file handles the functionality for the Thing schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Thing
 * This class handles the functionality for the Thing schema type.
 *
 * @since 1.0.0
 */
class Thing extends Base {

	use Get_Instance;

	/**
	 * Get Schema Data
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'Thing',
			'type'    => 'Thing',
			'show_on' => [
				'rules'        => [],
				'specific'     => [],
				'specificText' => [],
			],
			'fields'  => $this->parse_fields( $this->get() ),
		];
	}

	/**
	 * Get Thing Schema
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_thing',
			[
				[
					'id'   => 'schemaDocs',
					'type' => 'Hidden',
					'url'  => 'https://schema.org/Thing',
					'show' => true,
				],
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'Thing',
				],
				[
					'id'       => 'name',
					'label'    => __( 'Name', 'surerank' ),
					'tooltip'  => __( 'The name of the item', 'surerank' ),
					'required' => true,
				],
				[
					'id'      => 'alternateName',
					'label'   => __( 'Alternate name', 'surerank' ),
					'tooltip' => __( 'An alias for the item', 'surerank' ),
				],
				[
					'id'      => 'description',
					'label'   => __( 'Description', 'surerank' ),
					'tooltip' => __( 'A description of the item', 'surerank' ),
				],
				[
					'id'      => 'identifier',
					'label'   => __( 'Identifier', 'surerank' ),
					'tooltip' => __( 'The identifier property represents any kind of identifier for any kind of Thing, such as ISBNs, GTIN codes, UUIDs etc. Schema.org provides dedicated properties for representing many of these, either as textual strings or as URL (URI) links.', 'surerank' ),
				],
				[
					'id'      => 'image',
					'label'   => __( 'Image', 'surerank' ),
					'tooltip' => __( 'An image of the item', 'surerank' ),
				],
				$this->add_helper_property(
					'mainEntityOfPage',
					[
						'show' => false,
					]
				),
				[
					'id'      => 'sameAs',
					'label'   => __( 'Same as', 'surerank' ),
					'tooltip' => __( 'URL of a reference Web page that unambiguously indicates the item\'s identity. E.g. the URL of the item\'s Wikipedia page, Wikidata entry, or official website.', 'surerank' ),
					'std'     => '',
				],
				[
					'id'      => 'url',
					'label'   => __( 'URL', 'surerank' ),
					'tooltip' => __( 'URL of the item', 'surerank' ),
				],
			]
		);
	}
}
