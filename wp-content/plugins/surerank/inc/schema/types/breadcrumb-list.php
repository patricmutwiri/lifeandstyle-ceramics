<?php
/**
 * Breadcrumb List
 *
 * This file handles the functionality for the Breadcrumb List schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * BreadcrumbList
 * This class handles the functionality for the Breadcrumb List schema type.
 *
 * @since 1.0.0
 */
class BreadcrumbList extends Base {
	use Get_Instance;

	/**
	 * Get Schema Data
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'       => 'BreadcrumbList',
			'type'        => 'BreadcrumbList',
			'show_on'     => [
				'rules'        => [
					'basic-global',
				],
				'specific'     => [],
				'specificText' => [],
			],
			'not_show_on' => [
				'rules'        => [
					'special-front',
				],
				'specific'     => [],
				'specificText' => [],
			],
			'fields'      => $this->parse_fields( $this->get() ),
		];
	}

	/**
	 * Get Variables
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_breadcrumblist',
			[
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'BreadcrumbList',
				],
				[
					'id'       => 'name',
					'std'      => __( 'BreadcrumbList', 'surerank' ),
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'       => '@id',
					'type'     => 'Hidden',
					'std'      => '%current.url%#%id%',
					'required' => true,
				],
				[
					'id'       => '@type',
					'std'      => 'BreadcrumbList',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'       => 'itemListElement',
					'type'     => 'Hidden',
					'std'      => '%current.breadcrumbs%',
					'required' => true,
				],
			]
		);
	}
}
