<?php
/**
 * Search_Action List
 *
 * This file handles the functionality for the Search Action schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Search_Action
 * This class handles the functionality for the Search Action schema type.
 *
 * @since 1.0.0
 */
class Search_Action extends Base {

	use Get_Instance;

	/**
	 * Get Schema Data
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'SearchAction',
			'type'    => 'SearchAction',
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
	 * Get Variables
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		$default_search_url = esc_url( home_url( '/' ) ) . '?s={search_term_string}';

		$search_url = apply_filters( 'surerank_search_action_target_url', $default_search_url );

		return apply_filters(
			'surerank_default_schema_type_search_action',
			[
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'SearchAction',
					'is_name' => true,
				],
				[
					'id'       => '@id',
					'type'     => 'Hidden',
					'std'      => '%site.url%#%id%',
					'required' => true,
				],
				[
					'id'       => 'target',
					'type'     => 'Hidden',
					'required' => true,
					'std'      => $search_url,
				],
				[
					'id'       => 'query-input',
					'type'     => 'Hidden',
					'required' => true,
					'std'      => 'required name=search_term_string',
				],
			]
		);
	}
}
