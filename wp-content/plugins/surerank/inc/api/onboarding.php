<?php
/**
 * Onboarding class
 *
 * Handles onboarding related REST API endpoints for the SureRank plugin.
 *
 * @package SureRank\Inc\API
 */

namespace SureRank\Inc\API;

use SureRank\Inc\Admin\Helper;
use SureRank\Inc\Admin\Update_Timestamp;
use SureRank\Inc\Functions\Requests;
use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const DEFAULT_USER_DETAILS = [
	'first_name' => '',
	'last_name'  => '',
	'email'      => '',
	'skip'       => 'no',
	'lead'       => false,
];

/**
 * Class Onboarding
 *
 * Handles onboarding related REST API endpoints.
 */
class Onboarding extends Api_Base {
	use Get_Instance;

	/**
	 * Route Onboarding
	 */
	protected const ONBOARDING = '/onboarding';

	/**
	 * Register API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_api_namespace(),
			self::ONBOARDING,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_website_details' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'first_name'           => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'required'          => false,
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'last_name'            => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'required'          => false,
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'email'                => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
						'required'          => false,
						'validate_callback' => static function( $value ) {
							return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
						},
					],
					'website_type'         => [
						'type'              => 'string',
						'required'          => false,
						'description'       => __( 'Type of the website.', 'surerank' ),
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'website_name'         => [
						'type'              => 'string',
						'required'          => false,
						'description'       => __( 'Name of the website.', 'surerank' ),
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'business_description' => [
						'type'              => 'string',
						'required'          => false,
						'description'       => __( 'Business description of the website.', 'surerank' ),
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'website_owner_name'   => [
						'type'              => 'string',
						'required'          => false,
						'description'       => __( 'Name of the website owner.', 'surerank' ),
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'website_owner_phone'  => [
						'type'              => 'string',
						'required'          => false,
						'description'       => __( 'Phone number of the website owner.', 'surerank' ),
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'organization_type'    => [
						'type'              => 'string',
						'required'          => false,
						'description'       => __( 'Type of the organization.', 'surerank' ),
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function( $value ) {
							return is_string( $value );
						},
					],
					'about_page'           => [
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',

					],
					'contact_page'         => [
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					],
					'social_profiles'      => [
						'type'        => 'object',
						'required'    => false,
						'description' => __( 'Social profiles URLs.', 'surerank' ),
						'properties'  => array_fill_keys(
							self::get_social_profile_keys(),
							[
								'type'              => 'string',
								'sanitize_callback' => 'esc_url_raw',
								'validate_callback' => static function( $value ) {
									return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
								},
							]
						),
					],
					'website_logo'         => [
						'type'     => 'string',
						'required' => false,
					],
				],
			]
		);
	}

	/**
	 * Update common onboarding data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Onboarding data.
	 * @return bool
	 */
	public static function update_common_onboarding_data( $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		$instance = self::get_instance();

		$defaults = [
			'website_type'         => '',
			'website_name'         => '',
			'business_description' => Helper::get_saved_business_details( 'business_description' ),
			'website_owner_name'   => '',
			'organization_type'    => 'Organization',
			'website_owner_phone'  => '',
			'website_logo'         => '',
			'first_name'           => '',
			'last_name'            => '',
			'email'                => '',
			'about_page'           => 0,
			'contact_page'         => 0,
			'social_profiles'      => [],
		];

		$data = wp_parse_args(
			$data,
			$defaults
		);

		$settings = Settings::get();

		$instance->set_onboarding_data( $data, $settings );
		$updated_onboarding = $instance->process_onboarding_data( $data, $settings );
		$updated_settings   = Update::option( SURERANK_SETTINGS, $settings );

		if ( $updated_onboarding && $updated_settings ) {
			$instance->set_schemas_pages( $data, $settings );
			Update_Timestamp::timestamp_option();
			return true;
		}

		return false;
	}

	/**
	 * Save Website Details
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return void
	 */
	public function save_website_details( $request ) {
		$data = $request->get_params();

		if ( empty( $data ) ) {
			Send_Json::error( [ 'message' => __( 'Invalid data provided', 'surerank' ) ] );
		}

		if ( self::update_common_onboarding_data( $data ) ) {
			Send_Json::success( [ 'message' => __( 'Settings updated successfully', 'surerank' ) ] );
		}

		Update::option( 'surerank_onboarding_completed', true );

		Send_Json::error( [ 'message' => __( 'Failed to update settings', 'surerank' ) ] );
	}

	/**
	 * Set Schemas Pages
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	public function set_schemas_pages( $data, $settings ) {
		$this->set_about_page( $data, $settings );
		$this->set_contact_page( $data, $settings );
	}

	/**
	 * Set About Page
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return bool|int
	 */
	public function set_about_page( $data, &$settings ) {
		$about_page = $data['about_page'] ?? 0;

		if ( ! $about_page ) {
			return false;
		}

		$default_schemas                               = Settings::prepare_schemas( $settings, 'page', $about_page, true );
		$schema                                        = $this->find_schema_by_type( $settings, 'WebPage' );
		$default_schemas[ $schema ]['fields']['@type'] = 'AboutPage';
		$default_schemas[ $schema ]['type']            = 'AboutPage';
		$schemas                                       = [
			'schemas' => $default_schemas,
		];

		return Update::post_meta( $about_page, 'surerank_settings_schemas', $schemas );
	}

	/**
	 * Set Contact Page
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return bool|int
	 */
	public function set_contact_page( $data, &$settings ) {
		$contact_page = $data['contact_page'] ?? 0;

		if ( ! $contact_page ) {
			return false;
		}

		$default_schemas                               = Settings::prepare_schemas( $settings, 'page', $contact_page, true );
		$schema                                        = $this->find_schema_by_type( $settings, 'WebPage' );
		$default_schemas[ $schema ]['fields']['@type'] = 'ContactPage';
		$default_schemas[ $schema ]['type']            = 'ContactPage';
		$schemas                                       = [
			'schemas' => $default_schemas,
		];

		return Update::post_meta( $contact_page, 'surerank_settings_schemas', $schemas );
	}

	/**
	 * Save Onboarding Data
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @return bool
	 */
	public function save_onboarding_data( $data ) {
		return Update::option( 'surerank_settings_onboarding', $data );
	}

	/**
	 * Set Website Details
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	public function set_website_details( $data, &$settings ) {
		$this->update_schema_field( $settings, 'Organization', 'name', $data['website_name'] );
		$this->update_schema_field( $settings, 'Organization', '@type', $data['organization_type'] );
		$this->update_schema_field( $settings, 'Organization', 'logo', $data['website_logo'] );
		$this->update_schema_field( $settings, 'Organization', 'telephone', $data['website_owner_phone'] );
		$this->update_schema_field( $settings, 'Organization', 'email', $data['email'] );
		$this->update_schema_field( $settings, 'Person', 'name', $data['website_owner_name'] );
		$this->update_schema_field( $settings, 'Person', 'image', $data['website_logo'] );
		$sanitized_name = sanitize_text_field( $data['website_name'] );
		update_option( 'blogname', $sanitized_name );
	}

	/**
	 * Update Schema Field
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $schema_type Schema type.
	 * @param string               $field Field name.
	 * @param mixed                $value Field value.
	 * @return void
	 */
	public function update_schema_field( &$settings, $schema_type, $field, $value ) {

		$schema_id = $this->find_schema_by_type( $settings, $schema_type );
		if ( is_null( $schema_id ) ) {
			return; // Bail early if schema is not found.
		}

		if ( ! isset( $settings['schemas'][ $schema_id ]['fields'] ) ) {
			$settings['schemas'][ $schema_id ]['fields'] = [];
		}

		$settings['schemas'][ $schema_id ]['fields'][ $field ] = $value;
	}

	/**
	 * Find Schema by Type
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings array.
	 * @param string               $type Schema type to find.
	 * @return string|null Schema ID if found, null otherwise.
	 */
	public function find_schema_by_type( &$settings, $type ) {
		if ( isset( $settings['schemas'] ) && is_array( $settings['schemas'] ) ) {
			foreach ( $settings['schemas'] as $schema_id => $schema ) {
				if ( isset( $schema['type'] ) && $schema['type'] === $type ) {
					return $schema_id;
				}
			}
		}
		return null;
	}

	/**
	 * Person or Organization
	 *
	 * @since 1.0.0
	 * @param string $website_type Website type.
	 * @return string
	 */
	public function person_or_organization( $website_type ) {
		return in_array( $website_type, [ 'blog', 'personal' ] ) ? 'person' : 'organization';
	}

	/**
	 * Get Social Profile Keys
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 */
	public static function get_social_profile_keys() {
		return array_column( self::social_profiles(), 'id' );
	}

	/**
	 * Set Social Profiles
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function social_profiles() {
		return apply_filters(
			'surerank_social_profiles',
			[
				[
					'label'             => 'Facebook',
					'id'                => 'facebook',
					'placeholder'       => 'https://www.facebook.com/my-page-url',
					'show_in_dashboard' => false,
				],
				[
					'label'             => 'X',
					'id'                => 'twitter',
					'placeholder'       => 'https://x.com/myaccount',
					'show_in_dashboard' => false,
				],
				[
					'label'       => 'Instagram',
					'id'          => 'instagram',
					'placeholder' => 'https://www.instagram.com/my-page-url',
				],
				[
					'label'       => 'YouTube',
					'id'          => 'youtube',
					'placeholder' => 'https://www.youtube.com/my-channel-url',
				],
				[
					'label'       => 'LinkedIn',
					'id'          => 'linkedin',
					'placeholder' => 'https://www.linkedin.com/company/my-company',
				],
				[
					'label'       => 'TikTok',
					'id'          => 'tiktok',
					'placeholder' => 'https://www.tiktok.com/@username',
				],
				[
					'label'       => 'Pinterest',
					'id'          => 'pinterest',
					'placeholder' => 'https://www.pinterest.com/my-page-url',
				],
				[
					'label'       => 'WhatsApp',
					'id'          => 'whatsapp',
					'placeholder' => 'https://wa.me/number',
				],
				[
					'label'       => 'Telegram',
					'id'          => 'telegram',
					'placeholder' => 'https://t.me/username',
				],
				[
					'label'       => 'Yelp',
					'id'          => 'yelp',
					'placeholder' => 'https://www.yelp.com/biz/business-name-location',
				],
				[
					'label'       => 'BlueSky',
					'id'          => 'bluesky',
					'placeholder' => 'https://bsky.app/profile/username',
				],
			]
		);
	}

	/**
	 * Set Social Schema
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	public function set_social_schema( $data, &$settings ) {
		$same_as_urls = array_filter(
			$data['social_profiles'] ?? [],
			static function( $url ) {
				return ! empty( $url );
			}
		);
		$this->update_schema_field( $settings, 'Organization', 'sameAs', $same_as_urls );
	}

	/**
	 * Retrieves the value of a specific key from the 'surerank_onboarding_user_details' option.
	 *
	 * @param string $key The key to retrieve from the settings.
	 * @param mixed  $default The default value to return if the key does not exist.
	 * @since 1.0.0
	 * @return mixed The value of the specified key or the default value.
	 */
	public function get_user_details( ?string $key = null, $default = null ) {
		$settings = wp_parse_args( get_option( 'surerank_onboarding_user_details', [] ), DEFAULT_USER_DETAILS );

		if ( $key === null ) {
			return $settings;
		}

		if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Sets the value of the 'surerank_onboarding_user_details' option.
	 *
	 * @param mixed $details The value to save.
	 * @since 1.0.0
	 * @return void
	 */
	public function set_user_details( $details ) {
		update_option( 'surerank_onboarding_user_details', wp_parse_args( $details, DEFAULT_USER_DETAILS ) );
	}

	/**
	 * Set Onboarding Data
	 *
	 * We need to store the facebook page url and twitter profile username in the settings array as per the new requirement.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	private function set_onboarding_data( $data, &$settings ) {
		$settings['facebook_page_url']        = $data['social_profiles']['facebook'] ?? '';
		$settings['twitter_profile_username'] = $data['social_profiles']['twitter'] ?? '';
	}

	/**
	 * Process Onboarding Data
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	private function process_onboarding_data( $data, &$settings ) {
		$this->set_website_details( $data, $settings );
		$this->set_social_schema( $data, $settings );
		$this->set_social_profiles( $data, $settings );
		$lead = [
			'email'      => $data['email'] ?? '',
			'first_name' => $data['first_name'] ?? '',
			'last_name'  => $data['last_name'] ?? '',
		];
		$this->generate_lead( $lead );
		$this->set_person_or_organization( $data, $settings );

		return $this->save_onboarding_data( $data );
	}

	/**
	 * Set Person or Organization.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	private function set_person_or_organization( &$data, &$settings ) {
		$type                           = $this->person_or_organization( $data['website_type'] );
		$data['person_or_organization'] = $type;

		/**
		 * Depending on the type, we need to set the publisher to the correct schema.
		 * We need to set the publisher for WebPage and Article.
		 */
		$schema = $type === 'organization' ? '%schemas.organization%' : '%schemas.person%';

		foreach ( [ 'WebPage', 'Article' ] as $context ) {
			$this->update_schema_field( $settings, $context, 'publisher', $schema );
		}
	}

	/**
	 * Set Social Profiles
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	private function set_social_profiles( &$data, &$settings ) {
		$settings['social_profiles'] = $data['social_profiles'];
		unset( $data['social_profiles'] );
	}

	/**
	 * Generate Lead
	 *
	 * @since 1.0.0
	 * @param array<string,string> $lead Website data.
	 * @return void
	 */
	private function generate_lead( $lead ) {
		$subscription_status = $this->get_user_details( 'lead', false );

		if ( $subscription_status ) {
			return;
		}

		if ( empty( $lead['email'] ) || empty( $lead['first_name'] ) ) {
			return;
		}

		$url = 'https://websitedemos.net/wp-json/surerank/v1/subscribe/';

		$args = [
			'body' => [
				'EMAIL'     => $lead['email'],
				'FIRSTNAME' => $lead['first_name'],
				'LASTNAME'  => $lead['last_name'] ?? '',
			],
		];

		$response = Requests::post( $url, $args );

		if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			$lead['lead'] = true;

			$this->set_user_details( $lead );
		}
	}
}
