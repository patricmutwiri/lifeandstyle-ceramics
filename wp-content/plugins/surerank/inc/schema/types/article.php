<?php
/**
 * Article
 *
 * This file will handle functionality for all Article.
 *
 * @package surerank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Article
 * This class will handle functionality for all Article.
 *
 * @since 1.0.0
 */
class Article extends Base {

	use Get_Instance;

	/**
	 * Get Schema Data
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'Article',
			'type'    => 'Article',
			'show_on' => [
				'rules'        => [
					'post|all',
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
	 * @return array<int,array<string,mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_article',
			[
				[
					'id'   => 'googleDocs',
					'type' => 'Hidden',
					'url'  => 'https://developers.google.com/search/docs/advanced/structured-data/article',
					'show' => true,
				],

				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'Article',
				],
				[
					'id'       => '@id',
					'type'     => 'Hidden',
					'std'      => '%current.url%#%id%',
					'required' => true,
				],
				[
					'label'    => __( 'Schema Type', 'surerank' ),
					'id'       => '@type',
					'type'     => 'Select',
					'required' => true,
					'options'  => $this->get_schema_type_options(),
					'std'      => 'Article',
				],
				$this->add_helper_property(
					'name',
					[
						'std'     => '%post.title%',
						'tooltip' => __( 'The name of the article.', 'surerank' ),
					]
				),
				$this->add_helper_property(
					'url',
					[
						'required' => true,
						'std'      => '%post.url%',
					]
				),
				[
					'label'    => __( 'Headline', 'surerank' ),
					'id'       => 'headline',
					'type'     => 'Text',
					'required' => true,
					'tooltip'  => __( 'Enter a concise and descriptive headline for the article. This headline will be included in your site’s structured data and should not exceed 110 characters to ensure proper display in search and social platforms.', 'surerank' ),
					'std'      => '%post.title%',
				],
				$this->add_helper_property(
					'description',
					[
						'tooltip' => __( 'Provide a short summary of the article’s content. This description is used in your site’s structured data to help search engines understand what the article is about.', 'surerank' ),
					]
				),
				$this->add_helper_property(
					'datePublished',
					[
						'required' => true,
						'tooltip'  => __( 'Enter the date and time the article was first published, using the ISO 8601 format.  This value is used in your site’s structured data to show when the content was originally made public.', 'surerank' ),
						'std'      => '%post.date%',
					]
				),
				$this->add_helper_property(
					'dateModified',
					[
						'tooltip' => __( 'The date and time the article was first published, in ISO 8601 format', 'surerank' ),
						'std'     => '%post.modified_date%',
					]
				),
				[
					'label'    => __( 'Comment count', 'surerank' ),
					'id'       => 'commentCount',
					'type'     => 'Text',
					'required' => true,
					'tooltip'  => __( 'Add the total number of comments the article has received. This helps search engines understand the level of engagement and is included in the structured data.', 'surerank' ),
					'std'      => '%post.comment_count%',
				],
				[
					'label'    => __( 'Word count', 'surerank' ),
					'id'       => 'wordCount',
					'type'     => 'Text',
					'required' => true,
					'tooltip'  => __(
						'Enter the total word count of the article’s main content. This information is used in structured data to provide search engines with content length details.
',
						'surerank'
					),
					'std'      => '%post.word_count%',
				],
				[
					'label'    => __( 'Keywords', 'surerank' ),
					'id'       => 'keywords',
					'type'     => 'Text',
					'required' => true,
					'tooltip'  => __( 'List relevant tags or keywords that describe the article, separated by commas. These keywords are used in structured data to help categorize and improve content discoverability.', 'surerank' ),
					'std'      => '%post.tags%',
				],
				[
					'label'    => __( 'Sections', 'surerank' ),
					'id'       => 'articleSection',
					'type'     => 'Text',
					'required' => true,
					'tooltip'  => __( 'Specify the section or category the article belongs to, such as Sports, Lifestyle, or Business. This is included in structured data to help search engines organize content by topic.', 'surerank' ),
					'std'      => '%post.categories%',
				],
				[
					'id'       => 'author',
					'label'    => __( 'Author', 'surerank' ),
					'tooltip'  => __( 'Specify the name of the person who wrote the article. This author information will be included in the structured data to attribute the content correctly.', 'surerank' ),
					'std'      => '%schemas.person%',
					'required' => true,
				],
				$this->add_helper_property( 'image', [ 'show' => true ] ),
				[
					'id'               => 'hasPart',
					'label'            => __( 'Subscription and pay-walled content', 'surerank' ),
					'type'             => 'Group',
					'tooltip'          => __( 'Indicates the content that is part of this item.', 'surerank' ),
					'cloneable'        => true,
					'cloneItemHeading' => __( 'Part', 'surerank' ),
					'fields'           => [
						[
							'id'       => '@type',
							'std'      => 'WebPageElement',
							'type'     => 'Hidden',
							'required' => true,
							'hidden'   => true,
						],
						[
							'id'       => 'isAccessibleForFree',
							'label'    => __( 'Is accessible for free?', 'surerank' ),
							'tooltip'  => __( 'Whether the dataset is accessible without payment.', 'surerank' ),
							'type'     => 'Select',
							'std'      => 'true',
							'options'  => [
								'true'  => __( 'Yes', 'surerank' ),
								'false' => __( 'No', 'surerank' ),
							],
							'required' => true,
						],
						[
							'id'       => 'cssSelector',
							'label'    => __( 'CSS selector', 'surerank' ),
							'tooltip'  => __( 'Class name around each pay-walled section of your page.', 'surerank' ),
							'required' => true,
						],
					],
				],
				[
					'id'    => 'isPartOf',
					'label' => __( 'Is part of', 'surerank' ),
					'std'   => '%schemas.webpage%',
					'show'  => true,
				],
				$this->add_helper_property( 'mainEntityOfPage' ),
				[
					'id'       => 'publisher',
					'label'    => __( 'Publisher', 'surerank' ),
					'tooltip'  => __( 'Enter the name of the individual or organization that published the article. This is included in the structured data to credit the source of the content.', 'surerank' ),
					'std'      => '%schemas.organization%',
					'required' => true,
				],
				$this->add_helper_property( 'mainEntity' ),
			]
		);
	}

	/**
	 * Get Schema Types
	 *
	 * @return array<string, string>
	 */
	public function get_schema_type_options() {
		return apply_filters(
			'surerank_default_schema_type_article_schema_types',
			[
				'Article'     => __( 'Article', 'surerank' ),
				'NewsArticle' => __( 'NewsArticle', 'surerank' ),
				'BlogPosting' => __( 'BlogPosting', 'surerank' ),
			]
		);
	}
}
