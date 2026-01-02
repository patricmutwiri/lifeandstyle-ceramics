<?php
/**
 * Organization Schema
 *
 * This file handles the functionality for the Organization schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Organization
 * This class handles the functionality for the Organization schema type.
 *
 * @since 1.0.0
 */
class Organization extends Base {

	use Get_Instance;

	/**
	 * Get Schema Data
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'Organization',
			'type'    => 'Organization',
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
	 * Get Organization Schema
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_organization',
			[
				[
					'id'       => '@id',
					'type'     => 'Hidden',
					'std'      => '%site.url%#%id%',
					'required' => true,
					'show'     => false,
				],
				[
					'id'   => 'schemaDocs',
					'type' => 'Hidden',
					'url'  => 'https://schema.org/Organization',
					'show' => true,
				],
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'Organization',
				],
				[
					'id'      => '@type',
					'label'   => __( 'Type', 'surerank' ),
					'tooltip' => __( 'Select the type that best describes the organization, such as Corporation, NGO, or EducationalOrganization. This information is used in structured data to define the nature of the entity.', 'surerank' ),
					'show'    => true,
					'type'    => 'SelectGroup',
					'std'     => 'Organization',
					'options' => $this->get_schema_type_options(),
				],
				$this->add_helper_property(
					'name',
					[
						'required' => true,
						'tooltip'  => __( 'Enter the full name of the organization as it should appear in structured data. This helps identify the business or entity associated with your website.', 'surerank' ),
						'std'      => '%site.title%',
					]
				),
				[
					'id'      => 'email',
					'label'   => __( 'Email', 'surerank' ),
					'tooltip' => __( "Provide a valid email address for the organization. This contact detail will be included in your site's structured data.", 'surerank' ),
					'show'    => true,
				],
				[
					'id'      => 'faxNumber',
					'label'   => __( 'Fax number', 'surerank' ),
					'tooltip' => __( 'The fax number of the organization', 'surerank' ),
				],
				$this->add_helper_property(
					'Person',
					[
						'id'               => 'founder',
						'label'            => __( 'Founder', 'surerank' ),
						'tooltip'          => __( 'A person who founded this organization.', 'surerank' ),
						'cloneable'        => true,
						'cloneItemHeading' => __( 'Employee', 'surerank' ),
					]
				),
				[
					'id'      => 'foundingDate',
					'label'   => __( 'Founding date', 'surerank' ),
					'tooltip' => __( 'The date that this organization was founded.', 'surerank' ),
					'type'    => 'Date',
				],
				[
					'label'   => __( 'Keywords', 'surerank' ),
					'id'      => 'keywords',
					'tooltip' => __( 'Keywords or tags used to describe some item. Multiple textual entries in a keywords list are typically delimited by commas.', 'surerank' ),
				],
				[
					'id'      => 'logo',
					'label'   => __( 'Logo URL', 'surerank' ),
					'show'    => true,
					'tooltip' => __( 'Enter the direct URL to the organization’s official logo image. This logo will be used in structured data to visually represent the brand.', 'surerank' ),
					'type'    => 'Image',
					'std'     => '%site.icon%',
				],
				[
					'id'        => 'sameAs',
					'label'     => __( 'Same as', 'surerank' ),
					'tooltip'   => __( 'Add a URL that clearly identifies the organization, such as a Wikipedia page, Wikidata entry, social media profile, or official site. This helps search engines verify the organization’s identity.', 'surerank' ),
					'show'      => true,
					'cloneable' => true,
					'std'       => '',
				],
				[
					'label'   => __( 'Slogan', 'surerank' ),
					'id'      => 'slogan',
					'show'    => true,
					'tooltip' => __( 'Provide the official slogan or motto associated with the organization or individual. This will be included in structured data for additional context.', 'surerank' ),
					'std'     => '%site.description%',
				],
				[
					'id'      => 'telephone',
					'label'   => __( 'Telephone', 'surerank' ),
					'tooltip' => __( 'Enter a working phone number for the organization, including the country and area code. This detail helps complete your structured data profile.', 'surerank' ),
					'show'    => true,
					'std'     => '',
				],
				$this->add_helper_property(
					'url',
					[
						'tooltip' => __( 'Provide the full URL of the specific business location. This should be a working link that represents the official page for that location.', 'surerank' ),
						'std'     => '%site.url% ',
						'show'    => true,
					]
				),
			]
		);
	}

	/**
	 * Get Schema Type Options
	 *
	 * @return array<string, mixed>
	 * @since 1.0.0
	 */
	public function get_schema_type_options() {
		$groups = [
			'general'    => [
				'label'   => __( 'General', 'surerank' ),
				'options' => [
					'Organization' => __( 'Organization', 'surerank' ),
					'Corporation'  => __( 'Corporation', 'surerank' ),
					'NGO'          => __( 'NGO', 'surerank' ),
				],
			],
			'education'  => [
				'label'   => __( 'Educational', 'surerank' ),
				'options' => [
					'EducationalOrganization' => __( 'EducationalOrganization', 'surerank' ),
					'CollegeOrUniversity'     => __( 'CollegeOrUniversity', 'surerank' ),
					'ElementarySchool'        => __( 'ElementarySchool', 'surerank' ),
					'HighSchool'              => __( 'HighSchool', 'surerank' ),
					'MiddleSchool'            => __( 'MiddleSchool', 'surerank' ),
					'Preschool'               => __( 'Preschool', 'surerank' ),
					'School'                  => __( 'School', 'surerank' ),
				],
			],
			'government' => [
				'label'   => __( 'Government', 'surerank' ),
				'options' => [
					'GovernmentOrganization' => __( 'GovernmentOrganization', 'surerank' ),
					'FundingAgency'          => __( 'FundingAgency', 'surerank' ),
				],
			],
			'medical'    => [
				'label'   => __( 'Medical', 'surerank' ),
				'options' => [
					'MedicalOrganization' => __( 'MedicalOrganization', 'surerank' ),
					'DiagnosticLab'       => __( 'DiagnosticLab', 'surerank' ),
					'VeterinaryCare'      => __( 'VeterinaryCare', 'surerank' ),
				],
			],
			'arts'       => [
				'label'   => __( 'Arts & Performance', 'surerank' ),
				'options' => [
					'PerformingGroup' => __( 'PerformingGroup', 'surerank' ),
					'DanceGroup'      => __( 'DanceGroup', 'surerank' ),
					'MusicGroup'      => __( 'MusicGroup', 'surerank' ),
					'TheaterGroup'    => __( 'TheaterGroup', 'surerank' ),
				],
			],
			'media'      => [
				'label'   => __( 'Media', 'surerank' ),
				'options' => [
					'NewsMediaOrganization' => __( 'NewsMediaOrganization', 'surerank' ),
				],
			],
			'research'   => [
				'label'   => __( 'Research', 'surerank' ),
				'options' => [
					'Project'         => __( 'Project', 'surerank' ),
					'ResearchProject' => __( 'ResearchProject', 'surerank' ),
					'Consortium'      => __( 'Consortium', 'surerank' ),
				],
			],
			'sports'     => [
				'label'   => __( 'Sports', 'surerank' ),
				'options' => [
					'SportsOrganization' => __( 'SportsOrganization', 'surerank' ),
					'SportsTeam'         => __( 'SportsTeam', 'surerank' ),
				],
			],
			'services'   => [
				'label'   => __( 'Services', 'surerank' ),
				'options' => [
					'Airline'       => __( 'Airline', 'surerank' ),
					'LibrarySystem' => __( 'LibrarySystem', 'surerank' ),
					'WorkersUnion'  => __( 'WorkersUnion', 'surerank' ),
				],
			],
		];

		return apply_filters( 'surerank_schema_type_organization_options', $groups );
	}
}
