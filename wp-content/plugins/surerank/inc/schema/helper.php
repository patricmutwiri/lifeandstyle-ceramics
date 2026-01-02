<?php
/**
 * Helper
 *
 * This file will handle functionality for all Helper.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema;

use SureRank\Inc\Traits\Get_Instance;

/**
 * Helper
 * This class will handle functionality for all Helper.
 *
 * @since 1.0.0
 */
class Helper {

	use Get_Instance;

	public const UNSUPPORTED_TAXONOMIES = [
		'wp_theme',
		'wp_template_part_area',
		'link_category',
		'nav_menu',
		'post_format',
		'mb-views-category',
	];

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get Taxonomies
	 *
	 * @param array<string, mixed> $args Arguments.
	 * @return array<string, mixed>|array<int, array<string, string>>
	 * @since 1.0.0
	 */
	public function get_taxonomies( $args = [] ) {
		$unsupported = self::UNSUPPORTED_TAXONOMIES;
		$taxonomies  = get_taxonomies( $args, 'objects' );
		if ( empty( $taxonomies ) ) {
			return [];
		}
		$taxonomies = array_diff_key( $taxonomies, array_flip( $unsupported ) );
		$taxonomies = array_map(
			static function( $taxonomy ) {
				return [
					'slug' => esc_attr( $taxonomy->name ),
					'name' => esc_html( $taxonomy->label ),
				];
			},
			$taxonomies
		);

		return array_values( $taxonomies );
	}

	/**
	 * Normalize
	 *
	 * @param string $key key.
	 * @return string
	 * @since 1.0.0
	 */
	public function normalize( $key ) {
		return str_replace( '-', '_', $key );
	}

	/**
	 * Get property
	 *
	 * Retrieves a specific property based on the provided name and arguments.
	 *
	 * @param string               $name The name of the property to retrieve.
	 * @param array<string, mixed> $args Optional. Additional arguments to customize the property retrieval. Default is an empty array.
	 * @return array<string, mixed> The retrieved property.
	 * @since 1.0.0
	 */
	public function get_property( $name, $args = [] ) {
		$properties = [];

		$properties['type'] = [
			'id'       => '@type',
			'type'     => 'Hidden',
			'required' => true,
		];

		$properties['image'] = [
			'id'        => 'image',
			'label'     => __( 'Image', 'surerank' ),
			'std'       => [ '%post.thumbnail%' ],
			'type'      => 'Image',
			'cloneable' => true,
		];

		$properties['product_image'] = [
			'id'        => 'image',
			'label'     => __( 'Product Image', 'surerank' ),
			'std'       => [ '%product.image%' ],
			'type'      => 'Image',
			'cloneable' => true,
		];

		$properties['name'] = [
			'label' => __( 'Name', 'surerank' ),
			'id'    => 'name',
			'std'   => '%post.title%',
		];

		$properties['Answer'] = [
			'type'   => 'Group',
			'fields' => [
				[
					'id'       => '@type',
					'std'      => 'Answer',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'       => 'text',
					'label'    => __( 'Text', 'surerank' ),
					'type'     => 'Textarea',
					'tooltip'  => __( 'The full instruction text of this step', 'surerank' ),
					'required' => true,
				],
				[
					'id'      => 'upvoteCount',
					'label'   => __( 'Upvote count', 'surerank' ),
					'tooltip' => __( 'The total number of votes that this question has received. If the page supports upvotes and downvotes, then set the upvoteCount value to a single aggregate value that represents both upvotes and downvotes.', 'surerank' ),
				],
				[
					'id'      => 'url',
					'label'   => __( 'URL', 'surerank' ),
					'tooltip' => __( 'A URL that links directly to this answer.', 'surerank' ),
				],
			],
		];

		$properties['dateCreated'] = [
			'id'    => 'dateCreated',
			'label' => __( 'Created date', 'surerank' ),
			'type'  => 'Date',
		];

		$properties['datePublished'] = [
			'id'    => 'datePublished',
			'label' => __( 'Published date', 'surerank' ),
			'type'  => 'Date',
		];

		$properties['dateModified'] = [
			'id'    => 'dateModified',
			'label' => __( 'Modified date', 'surerank' ),
			'type'  => 'Date',
		];

		$properties['description'] = [
			'id'    => 'description',
			'label' => __( 'Description', 'surerank' ),
			'type'  => 'Textarea',
			'std'   => '%post.excerpt%',
			'show'  => true,
		];

		$properties['url'] = [
			'id'    => 'url',
			'label' => __( 'URL', 'surerank' ),
		];

		$properties['aggregateRating'] = [
			'id'           => 'aggregateRating',
			'label'        => __( 'Aggregate rating', 'surerank' ),
			'type'         => 'Group',
			'propertyType' => 'AggregateRating',
			'tooltip'      => __( 'Include an overall rating based on customer reviews. This rating is used in structured data to reflect the product’s average performance or satisfaction level.', 'surerank' ),
			'fields'       => [
				[
					'id'       => 'ratingValue',
					'label'    => __( 'Value', 'surerank' ),
					'std'      => '%product.rating%',
					'tooltip'  => __( 'Specify the actual score given to the product, such as ‘4’, ‘60%’, or ‘6/10’. This numeric value contributes to the aggregate rating in structured data.', 'surerank' ),
					'required' => true,
				],
				[
					'id'       => 'reviewCount',
					'label'    => __( 'Review count', 'surerank' ),
					'std'      => '%product.review_count%',
					'tooltip'  => __( 'Enter the total number of reviews the product has received. This helps search engines understand the product’s popularity and display review count in rich results.', 'surerank' ),
					'required' => true,
				],
			],
		];

		// https://schema.org/VideoObject.
		$properties['VideoObject'] = [
			'id'     => 'video',
			'label'  => __( 'Video', 'surerank' ),
			'type'   => 'Group',
			'fields' => [
				[
					'id'       => '@type',
					'std'      => 'VideoObject',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'label'    => __( 'Name', 'surerank' ),
					'id'       => 'name',
					'std'      => '%post.title%',
					'required' => true,
				],
				[
					'id'       => 'description',
					'label'    => __( 'Description', 'surerank' ),
					'type'     => 'Textarea',
					'std'      => '%post.content%',
					'required' => true,
				],
				[
					'id'       => 'thumbnailUrl',
					'label'    => __( 'Thumbnail URL', 'surerank' ),
					'tooltip'  => __( 'A URL pointing to the video thumbnail image file', 'surerank' ),
					'std'      => '%post.thumbnail%',
					'required' => true,
				],
				[
					'id'       => 'uploadDate',
					'label'    => __( 'Upload Date', 'surerank' ),
					'tooltip'  => __( 'The date the video was first published, in ISO 8601 format.', 'surerank' ),
					'type'     => 'Date',
					'std'      => '%post.date%',
					'required' => true,
				],
				[
					'id'      => 'contentURL',
					'label'   => __( 'Content URL', 'surerank' ),
					'tooltip' => __( 'A URL pointing to the actual video media file', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'embedURL',
					'label'   => __( 'Embed URL', 'surerank' ),
					'tooltip' => __( 'A URL pointing to a player for the specific video. Don\'t link to the page where the video lives; this must be the URL of the video player itself. Usually this is the information in the src element of an <embed> tag.', 'surerank' ),
				],
				[
					'id'      => 'duration',
					'label'   => __( 'Duration (min)', 'surerank' ),
					'tooltip' => __( 'The duration of the video in ISO 8601 format. For example, PT00H30M5S represents a duration of "thirty minutes and five seconds".', 'surerank' ),
				],
				[
					'id'      => 'expires',
					'label'   => __( 'Expires', 'surerank' ),
					'tooltip' => __( 'If applicable, the date after which the video will no longer be available, in ISO 8601 format. Don\'t supply this information if your video does not expire.', 'surerank' ),
				],
				[
					'id'               => 'hasPart',
					'label'            => __( 'Has part', 'surerank' ),
					'tooltip'          => __( 'If your video has important segments, specify them here', 'surerank' ),
					'type'             => 'Group',
					'cloneable'        => true,
					'cloneItemHeading' => __( 'Part', 'surerank' ),
					'fields'           => [
						[
							'id'       => '@type',
							'std'      => 'Clip',
							'type'     => 'Hidden',
							'required' => true,
						],
						[
							'label' => __( 'Name', 'surerank' ),
							'id'    => 'name',
							'show'  => true,
						],
						[
							'id'    => 'startOffset',
							'label' => __( 'Start offset', 'surerank' ),
							'show'  => true,
						],
						[
							'label' => __( 'URL', 'surerank' ),
							'id'    => 'url',
							'show'  => true,
						],
					],
				],
				[
					'id'     => 'interactionStatistic',
					'type'   => 'Group',
					'fields' => [
						[
							'id'       => '@type',
							'type'     => 'Hidden',
							'std'      => 'InteractionCounter',
							'required' => true,
						],
						[
							'id'       => 'interactionType',
							'type'     => 'Group',
							'required' => true,
							'fields'   => [
								[
									'id'       => '@type',
									'type'     => 'Hidden',
									'std'      => 'WatchAction',
									'required' => true,
								],
							],
						],
						[
							'id'       => 'userInteractionCount',
							'label'    => __( 'Interaction statistic', 'surerank' ),
							'tooltip'  => __( 'The number of times the video has been watched', 'surerank' ),
							'required' => true,
						],
					],
				],
			],
		];

		$properties['address'] = [
			'id'     => 'address',
			'label'  => __( 'Address', 'surerank' ),
			'type'   => 'Group',
			'fields' => [
				[
					'id'   => '@type',
					'std'  => 'PostalAddress',
					'type' => 'Hidden',
					'show' => true,
				],
				[
					'id'      => 'streetAddress',
					'label'   => __( 'Street address', 'surerank' ),
					'tooltip' => __( 'The detailed street address.', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'addressLocality',
					'label'   => __( 'Locality', 'surerank' ),
					'tooltip' => __( 'The locality in which the street address is, and which is in the region.', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'addressRegion',
					'label'   => __( 'Region', 'surerank' ),
					'tooltip' => __( 'The region in which the locality is, and which is in the country.', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'addressCountry',
					'label'   => __( 'Country', 'surerank' ),
					'tooltip' => __( 'The country. You can also provide the two-letter ISO 3166-1 alpha-2 country code.', 'surerank' ),
					'std'     => 'US',
					'show'    => true,
				],
				[
					'id'    => 'postalCode',
					'label' => __( 'Postal code', 'surerank' ),
					'show'  => true,
				],
			],
		];

		// https://schema.org/Person.
		$properties['Person'] = [
			'type'   => 'Group',
			'fields' => [
				[
					'id'       => '@type',
					'std'      => 'Person',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'       => 'name',
					'required' => true,
					'std'      => '%author.display_name%',
				],
			],
		];

		// https://schema.org/OpeningHoursSpecification.
		$properties['OpeningHoursSpecification'] = [
			'type'      => 'Group',
			'cloneable' => true,
			'fields'    => [
				[
					'id'      => 'dayOfWeek',
					'label'   => __( 'Day of week', 'surerank' ),
					'tooltip' => __( 'The day of the week for which these opening hours are valid', 'surerank' ),
					'show'    => true,
					'type'    => 'Select',
					'options' => [
						'https://schema.org/Monday'    => __( 'Monday', 'surerank' ),
						'https://schema.org/Tuesday'   => __( 'Tuesday', 'surerank' ),
						'https://schema.org/Wednesday' => __( 'Wednesday', 'surerank' ),
						'https://schema.org/Thursday'  => __( 'Thursday', 'surerank' ),
						'https://schema.org/Friday'    => __( 'Friday', 'surerank' ),
						'https://schema.org/Saturday'  => __( 'Saturday', 'surerank' ),
						'https://schema.org/Sunday'    => __( 'Sunday', 'surerank' ),
					],
				],
				[
					'id'      => 'opens',
					'label'   => __( 'Opens', 'surerank' ),
					'tooltip' => __( 'The opening hour of the place or service on the given day(s) of the week, in hh:mm:ss format', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'closes',
					'label'   => __( 'Closes', 'surerank' ),
					'tooltip' => __( 'The closing hour of the place or service on the given day(s) of the week, in hh:mm:ss format', 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'validFrom',
					'label'   => __( 'Valid from', 'surerank' ),
					'tooltip' => __( 'The date when the item becomes valid, in YYYY-MM-DD format', 'surerank' ),
				],
				[
					'id'      => 'validThrough',
					'label'   => __( 'Valid through', 'surerank' ),
					'tooltip' => __( 'The date after when the item is not valid, in YYYY-MM-DD format', 'surerank' ),
				],
			],
		];

		// https://schema.org/Review.
		$properties['Review'] = [
			'id'               => 'review',
			'type'             => 'Group',
			'label'            => __( 'Reviews', 'surerank' ),
			'cloneable'        => true,
			'cloneItemHeading' => __( 'Review', 'surerank' ),
			'tooltip'          => __( 'Reviews of the item', 'surerank' ),
			'fields'           => [
				[
					'id'       => '@type',
					'std'      => 'Review',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'     => 'author',
					'type'   => 'Group',
					'show'   => true,
					'fields' => [
						[
							'id'       => '@type',
							'std'      => 'Person',
							'type'     => 'Hidden',
							'required' => true,
						],
						[
							'id'       => 'name',
							'label'    => __( 'Author', 'surerank' ),
							'required' => true,
							'std'      => '%author.display_name%',
						],
					],
				],
				[
					'id'     => 'reviewRating',
					'type'   => 'Group',
					'label'  => __( 'Rating', 'surerank' ),
					'show'   => true,
					'fields' => [
						[
							'id'       => '@type',
							'std'      => 'Rating',
							'type'     => 'Hidden',
							'required' => true,
						],
						[
							'id'       => 'ratingValue',
							'label'    => __( 'Rating value', 'surerank' ),
							'required' => true,
							'type'     => 'DataList',
							'std'      => '5',
							'options'  => [
								1 => 1,
								2 => 2,
								3 => 3,
								4 => 4,
								5 => 5,
							],
						],
						[
							'id'      => 'bestRating',
							'label'   => __( 'Best rating', 'surerank' ),
							'type'    => 'DataList',
							'std'     => '5',
							'options' => [
								1 => 1,
								2 => 2,
								3 => 3,
								4 => 4,
								5 => 5,
							],
						],
						[
							'id'      => 'worstRating',
							'label'   => __( 'Worst rating', 'surerank' ),
							'type'    => 'DataList',
							'std'     => '1',
							'options' => [
								1 => 1,
								2 => 2,
								3 => 3,
								4 => 4,
								5 => 5,
							],
						],
					],
				],
				[
					'id'    => 'datePublished',
					'label' => __( 'Date published', 'surerank' ),
					'type'  => 'Date',
				],
			],
		];

		$properties['duration'] = [
			'fields' => [
				[
					'id'       => '@type',
					'std'      => 'QuantitativeValue',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'    => 'value',
					'label' => __( 'Value', 'surerank' ),
					'show'  => true,
				],
				[
					'id'    => 'minValue',
					'label' => __( 'Min value', 'surerank' ),
				],
				[
					'id'    => 'maxValue',
					'label' => __( 'Max value', 'surerank' ),
				],
				[
					'id'      => 'unitText',
					'label'   => __( 'Unit text', 'surerank' ),
					'type'    => 'Select',
					'std'     => 'MONTH',
					'tooltip' => __( 'A string or text indicating the unit of measurement.', 'surerank' ),
					'options' => [
						'HOUR'  => __( 'Hour', 'surerank' ),
						'DAY'   => __( 'Day', 'surerank' ),
						'WEEK'  => __( 'Week', 'surerank' ),
						'MONTH' => __( 'Month', 'surerank' ),
						'YEAR'  => __( 'Year', 'surerank' ),
					],
				],
			],
		];

		$properties['QuantitativeValue'] = [
			'fields' => [
				[
					'id'       => '@type',
					'std'      => 'QuantitativeValue',
					'type'     => 'Hidden',
					'required' => true,
				],
				[
					'id'    => 'value',
					'label' => __( 'Value', 'surerank' ),
					'show'  => true,
				],
				[
					'id'    => 'minValue',
					'label' => __( 'Min value', 'surerank' ),
				],
				[
					'id'    => 'maxValue',
					'label' => __( 'Max value', 'surerank' ),
				],
				[
					'id'      => 'unitText',
					'label'   => __( 'Unit text', 'surerank' ),
					'tooltip' => __( 'A string or text indicating the unit of measurement.', 'surerank' ),
					'show'    => true,
				],
			],
		];

		$properties['mainEntity'] = [
			'id'          => 'mainEntity',
			'label'       => __( 'Main entity', 'surerank' ),
			'tooltip'     => __( 'Indicates the primary entity described in some page or other CreativeWork.', 'surerank' ),
			'description' => __( 'Please create a schema and link to this property via a dynamic variable', 'surerank' ),
		];

		$properties['mainEntityOfPage'] = [
			'id'      => 'mainEntityOfPage',
			'label'   => __( 'Main entity of page', 'surerank' ),
			'tooltip' => __( 'Identify the main entity being described on the page, such as an article, product, or event. This helps search engines understand the primary focus of the page through structured data.', 'surerank' ),
			'std'     => '%schemas.webpage%',
			'show'    => true,
		];

		$property = $properties[ $name ] ?? [];

		return array_replace_recursive( $property, $args );
	}

}
