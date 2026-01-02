<?php
/**
 * SEOPress Importer Class
 *
 * Handles importing data from SEOPress plugin.
 *
 * @package SureRank\Inc\Importers
 * @since   1.3.0
 */

namespace SureRank\Inc\Importers\Seopress;

use Exception;
use SureRank\Inc\API\Onboarding;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Importers\BaseImporter;
use SureRank\Inc\Importers\ImporterUtils;
use SureRank\Inc\Traits\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements SEOPress → SureRank migration.
 */
class Seopress extends BaseImporter {

	use Logger;

	/**
	 * Get plugin name.
	 */
	public function get_plugin_name(): string {
		return Constants::PLUGIN_NAME;
	}

	/**
	 * Get plugin file.
	 */
	public function get_plugin_file(): string {
		return Constants::PLUGIN_FILE;
	}

	/**
	 * Check if SEOPress plugin is active.
	 */
	public function is_plugin_active(): bool {
		return defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Detect SEOPress data for post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function detect_post( int $post_id ): array {
		$meta          = get_post_meta( $post_id );
		$excluded_keys = $this->get_excluded_meta_keys();

		if ( $this->has_source_meta( $meta, $excluded_keys ) ) {
			return ImporterUtils::build_response(
				sprintf(
					// translators: %d: post ID.
					__( 'SEOPress data detected for post %d.', 'surerank' ),
					$post_id
				),
				true
			);
		}

		ImporterUtils::update_surerank_migrated( $post_id );

		return ImporterUtils::build_response(
			sprintf(
				// translators: %d: post ID.
				__( 'No SEOPress data found for post %d.', 'surerank' ),
				$post_id
			),
			false,
			[],
			true
		);
	}

	/**
	 * Detect SEOPress data for term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function detect_term( int $term_id ): array {
		$term = get_term( $term_id );

		if ( ! $term || is_wp_error( $term ) ) {
			return ImporterUtils::build_response(
				sprintf(
					// translators: %d: term ID.
					__( 'Invalid term ID %d.', 'surerank' ),
					$term_id
				),
				false,
				[],
				true
			);
		}

		$this->type = $term->taxonomy && in_array( $term->taxonomy, array_keys( $this->taxonomies ), true ) ? $term->taxonomy : '';
		$term_meta  = get_term_meta( $term_id );

		// Check if term has any SEOPress meta.
		$has_seopress_data = false;
		if ( is_array( $term_meta ) ) {
			foreach ( array_keys( $term_meta ) as $key ) {
				if ( str_starts_with( (string) $key, Constants::META_KEY_PREFIX ) ) {
					$has_seopress_data = true;
					break;
				}
			}
		}

		if ( $has_seopress_data ) {
			return ImporterUtils::build_response(
				sprintf(
					// translators: %d: term ID.
					__( 'SEOPress data detected for term %d.', 'surerank' ),
					$term_id
				),
				true
			);
		}

		ImporterUtils::update_surerank_migrated( $term_id, false );

		return ImporterUtils::build_response(
			sprintf(
				// translators: %d: term ID.
				__( 'No SEOPress data found for term %d.', 'surerank' ),
				$term_id
			),
			false,
			[],
			true
		);
	}

	/**
	 * Import meta-robots settings for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post_meta_robots( int $post_id ): array {
		try {
			$robots = $this->collect_robot_data();
			$this->remove_existing_post_robots( $post_id );
			$this->apply_robot_settings( $robots );
			return ImporterUtils::build_response(
				sprintf(
					// translators: %d: post ID.
					__( 'Meta-robots imported for post %d.', 'surerank' ),
					$post_id
				),
				true
			);
		} catch ( Exception $e ) {
			self::log(
				sprintf(
					/* translators: %d: post ID, %s: error message. */
					__( 'Error importing meta-robots for post %1$d: %2$s', 'surerank' ),
					$post_id,
					$e->getMessage()
				)
			);
			return ImporterUtils::build_response( $e->getMessage(), false );
		}
	}

	/**
	 * Import meta-robots settings for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term_meta_robots( int $term_id ): array {
		try {
			$robots = $this->collect_robot_data();
			$this->apply_robot_settings( $robots );
			$this->remove_existing_term_robots( $term_id );

			return ImporterUtils::build_response(
				sprintf(
					// translators: %d: term ID.
					__( 'Meta-robots imported for term %d.', 'surerank' ),
					$term_id
				),
				true
			);
		} catch ( Exception $e ) {
			self::log(
				sprintf(
					/* translators: %d: term ID, %s: error message. */
					__( 'Error importing meta-robots for term %1$d: %2$s', 'surerank' ),
					$term_id,
					$e->getMessage()
				)
			);
			return ImporterUtils::build_response( $e->getMessage(), false );
		}
	}

	/**
	 * Import general SEO settings for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post_general_settings( int $post_id ): array {
		$mapping = [
			'_seopress_titles_title'     => [ '', 'page_title' ],
			'_seopress_titles_desc'      => [ '', 'page_description' ],
			'_seopress_robots_canonical' => [ '', 'canonical_url' ],
		];

		$imported = $this->process_meta_mapping( $mapping );
		// translators: %d: post ID.
		$message = $imported ? __( 'General settings imported for post %d.', 'surerank' ) : __( 'No general settings to import for post %d.', 'surerank' );

		return ImporterUtils::build_response(
			sprintf(
				// translators: %d: post ID.
				$message,
				$post_id
			),
			$imported
		);
	}

	/**
	 * Import general SEO settings for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term_general_settings( int $term_id ): array {
		$mapping = [
			'_seopress_titles_title'     => [ '', 'page_title' ],
			'_seopress_titles_desc'      => [ '', 'page_description' ],
			'_seopress_robots_canonical' => [ '', 'canonical_url' ],
		];

		$imported = $this->process_meta_mapping( $mapping );
		// translators: %d: term ID.
		$message = $imported ? __( 'General settings imported for term %d.', 'surerank' ) : __( 'No general settings to import for term %d.', 'surerank' );

		return ImporterUtils::build_response(
			sprintf(
				// translators: %d: term ID.
				$message,
				$term_id
			),
			$imported
		);
	}

	/**
	 * Import social metadata for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_post_social( int $post_id ): array {
		$this->default_surerank_meta['twitter_same_as_facebook'] = ! $this->has_twitter_specific_data();

		$imported = $this->process_meta_mapping( Constants::get_social_mapping() );
		// translators: %d: post ID.
		$message = $imported ? __( 'Social metadata imported for post %d.', 'surerank' ) : __( 'No social metadata to import for post %d.', 'surerank' );

		return ImporterUtils::build_response(
			sprintf(
				// translators: %d: post ID.
				$message,
				$post_id
			),
			$imported
		);
	}

	/**
	 * Import social metadata for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array{success: bool, message: string}
	 */
	public function import_term_social( int $term_id ): array {
		$this->default_surerank_meta['twitter_same_as_facebook'] = ! $this->has_twitter_specific_data();

		$imported = $this->process_meta_mapping( Constants::get_social_mapping() );
		// translators: %d: term ID.
		$message = $imported ? __( 'Social metadata imported for term %d.', 'surerank' ) : __( 'No social metadata to import for term %d.', 'surerank' );

		return ImporterUtils::build_response(
			sprintf(
				// translators: %d: term ID.
				$message,
				$term_id
			),
			$imported
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function import_global_settings(): array {
		$titles_settings   = get_option( 'seopress_titles_option_name', [] );
		$social_settings   = get_option( 'seopress_social_option_name', [] );
		$sitemap_settings  = get_option( 'seopress_xml_sitemap_option_name', [] );
		$advanced_settings = get_option( 'seopress_advanced_option_name', [] );

		// Ensure all settings are arrays.
		$titles_settings   = is_array( $titles_settings ) ? $titles_settings : [];
		$social_settings   = is_array( $social_settings ) ? $social_settings : [];
		$sitemap_settings  = is_array( $sitemap_settings ) ? $sitemap_settings : [];
		$advanced_settings = is_array( $advanced_settings ) ? $advanced_settings : [];

		// Store individual settings arrays for proper nested processing.
		$this->source_settings = [
			'seopress_titles_option_name'      => $titles_settings,
			'seopress_social_option_name'      => $social_settings,
			'seopress_xml_sitemap_option_name' => $sitemap_settings,
			'seopress_advanced_option_name'    => $advanced_settings,
		];

		if ( empty( $this->source_settings ) ) {
			return ImporterUtils::build_response(
				__( 'No SEOPress global settings found to import.', 'surerank' ),
				false
			);
		}

		$this->surerank_settings = Settings::get();

		$this->update_robot_settings();
		$this->update_description_and_title();
		$this->update_archive_settings();
		$this->update_social_settings();
		$this->update_sitemap_settings();
		$this->update_site_details();

		try {
			ImporterUtils::update_global_settings( $this->surerank_settings );
			return ImporterUtils::build_response(
				__( 'SEOPress global settings imported successfully.', 'surerank' ),
				true
			);
		} catch ( Exception $e ) {
			self::log(
				sprintf(
					/* translators: %s: error message. */
					__( 'Error importing SEOPress global settings: %s', 'surerank' ),
					$e->getMessage()
				)
			);
			return ImporterUtils::build_response( $e->getMessage(), false );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_not_allowed_types(): array {
		return Constants::NOT_ALLOWED_TYPES;
	}

	/**
	 * Get the source meta data for a post or term.
	 *
	 * @param int    $id          The ID of the post or term.
	 * @param bool   $is_taxonomy Whether it is a taxonomy.
	 * @param string $type        The type of post or term.
	 * @return array<string, mixed>
	 */
	protected function get_source_meta_data( int $id, bool $is_taxonomy, string $type = '' ): array {
		return Constants::seopress_meta_data( $id, $is_taxonomy, $type );
	}

	/**
	 * Get the meta key prefix for the importer.
	 *
	 * @return string
	 */
	protected function get_meta_key_prefix(): string {
		return Constants::META_KEY_PREFIX;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_excluded_meta_keys(): array {
		return Constants::EXCLUDED_META_KEYS;
	}

	/**
	 * Remove existing robots from the default surerank meta.
	 *
	 * @param int  $id          The ID (post or term).
	 * @param bool $is_term     Whether this is a term (true) or post (false).
	 * @return void
	 */
	private function remove_existing_robots( int $id, bool $is_term = false ): void {
		$this->default_surerank_meta['post_no_index']   = '';
		$this->default_surerank_meta['post_no_follow']  = '';
		$this->default_surerank_meta['post_no_archive'] = '';

		$meta_keys = [
			'surerank_settings_post_no_index',
			'surerank_settings_post_no_follow',
			'surerank_settings_post_no_archive',
		];

		foreach ( $meta_keys as $key ) {
			if ( $is_term ) {
				Update::term_meta( $id, $key, '' );
			} else {
				Update::post_meta( $id, $key, '' );
			}
		}
	}

	/**
	 * Remove existing robots from the default surerank meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	private function remove_existing_term_robots( int $term_id ): void {
		$this->remove_existing_robots( $term_id, true );
	}

	/**
	 * Remove existing robots from the default surerank meta.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function remove_existing_post_robots( int $post_id ): void {
		$this->remove_existing_robots( $post_id );
	}

	/**
	 * Collect robot data.
	 *
	 * @return array<string, string>
	 */
	private function collect_robot_data(): array {
		$robots = [];

		if ( ! empty( $this->source_meta['_seopress_robots_index'] ) && 'yes' === $this->source_meta['_seopress_robots_index'] ) {
			$robots['noindex'] = 'yes';
		}

		if ( ! empty( $this->source_meta['_seopress_robots_follow'] ) && 'yes' === $this->source_meta['_seopress_robots_follow'] ) {
			$robots['nofollow'] = 'yes';
		}

		if ( ! empty( $this->source_meta['_seopress_robots_snippet'] ) && 'yes' === $this->source_meta['_seopress_robots_snippet'] ) {
			$robots['noarchive'] = 'yes';
		}

		return $robots;
	}

	/**
	 * Apply robot settings.
	 *
	 * @param array<string, string> $robots Robot data to apply.
	 */
	private function apply_robot_settings( array $robots ): void {
		$robots_mapping = Constants::get_robots_mapping();
		foreach ( $robots as $key => $value ) {
			if ( isset( $robots_mapping[ $key ] ) ) {
				$this->default_surerank_meta[ $robots_mapping[ $key ] ] = $value;
			}
		}
	}

	/**
	 * Process meta mapping.
	 *
	 * @param array<string, array<int, string>> $mapping Mapping array.
	 * @return bool
	 */
	private function process_meta_mapping( array $mapping ): bool {
		$imported = false;
		foreach ( $mapping as $old_key => $new_key ) {
			$target = $new_key[1];
			$value  = $this->source_meta[ $old_key ] ?? null;

			if ( null !== $value ) {
				$this->default_surerank_meta[ $target ] = Constants::replace_placeholders( $value, $this->source_meta['separator'] ?? '-' );
				$imported                               = true;
			}
		}
		return $imported;
	}

	/**
	 * Update robot settings.
	 *
	 * @return void
	 */
	private function update_robot_settings() {
		$titles = $this->source_settings['seopress_titles_option_name'] ?? [];

		$this->surerank_settings['no_index']   = [];
		$this->surerank_settings['no_follow']  = [];
		$this->surerank_settings['no_archive'] = [];

		if ( ! empty( $titles['seopress_titles_single_titles'] ) && is_array( $titles['seopress_titles_single_titles'] ) ) {
			foreach ( $titles['seopress_titles_single_titles'] as $type => $config ) {
				if ( ! is_array( $config ) ) {
					continue;
				}
				if ( ! empty( $config['noindex'] ) && '1' === (string) $config['noindex'] ) {
					if ( ! in_array( $type, $this->surerank_settings['no_index'], true ) ) {
						$this->surerank_settings['no_index'][] = $type;
					}
				}
				if ( ! empty( $config['nofollow'] ) && '1' === (string) $config['nofollow'] ) {
					if ( ! in_array( $type, $this->surerank_settings['no_follow'], true ) ) {
						$this->surerank_settings['no_follow'][] = $type;
					}
				}
				if ( ! empty( $config['noarchive'] ) && '1' === (string) $config['noarchive'] ) {
					if ( ! in_array( $type, $this->surerank_settings['no_archive'], true ) ) {
						$this->surerank_settings['no_archive'][] = $type;
					}
				}
			}
		}

		if ( ! empty( $titles['seopress_titles_tax_titles'] ) && is_array( $titles['seopress_titles_tax_titles'] ) ) {
			foreach ( $titles['seopress_titles_tax_titles'] as $tax => $config ) {
				if ( ! is_array( $config ) ) {
					continue;
				}
				if ( ! empty( $config['noindex'] ) && '1' === (string) $config['noindex'] ) {
					if ( ! in_array( $tax, $this->surerank_settings['no_index'], true ) ) {
						$this->surerank_settings['no_index'][] = $tax;
					}
				}
				if ( ! empty( $config['nofollow'] ) && '1' === (string) $config['nofollow'] ) {
					if ( ! in_array( $tax, $this->surerank_settings['no_follow'], true ) ) {
						$this->surerank_settings['no_follow'][] = $tax;
					}
				}
				if ( ! empty( $config['noarchive'] ) && '1' === (string) $config['noarchive'] ) {
					if ( ! in_array( $tax, $this->surerank_settings['no_archive'], true ) ) {
						$this->surerank_settings['no_archive'][] = $tax;
					}
				}
			}
		}

		foreach ( [ 'author', 'date', 'search_title' ] as $type ) {
			$key = 'seopress_titles_archives_' . $type . '_noindex';
			if ( ! empty( $titles[ $key ] ) && '1' === (string) $titles[ $key ] ) {
				if ( ! in_array( $type, $this->surerank_settings['no_index'], true ) ) {
					$type                                  = 'search_title' === $type ? 'search' : $type;
					$this->surerank_settings['no_index'][] = $type;
				}
			}
		}
	}

	/**
	 * Update titles and descriptions.
	 *
	 * @return void
	 */
	private function update_description_and_title(): void {
		$titles = $this->source_settings['seopress_titles_option_name'] ?? [];
		$sep    = $this->get_separator();

		if ( ! empty( $titles['seopress_titles_home_site_title'] ) ) {
			$this->surerank_settings['home_page_title'] = Constants::replace_placeholders( (string) $titles['seopress_titles_home_site_title'], $sep );
		}
		if ( ! empty( $titles['seopress_titles_home_site_desc'] ) ) {
			$this->surerank_settings['home_page_description'] = Constants::replace_placeholders( (string) $titles['seopress_titles_home_site_desc'], $sep );
		}

		$title_tpl = '';
		$desc_tpl  = '';

		if ( ! empty( $titles['seopress_titles_single_titles'] ) && is_array( $titles['seopress_titles_single_titles'] ) ) {
			foreach ( $titles['seopress_titles_single_titles'] as $type => $config ) {
				if ( ! is_array( $config ) ) {
					continue;
				}
				if ( empty( $title_tpl ) && ! empty( $config['title'] ) ) {
					$title_tpl = Constants::replace_placeholders( (string) $config['title'], $sep );
				}
				if ( empty( $desc_tpl ) && ! empty( $config['description'] ) ) {
					$desc_tpl = Constants::replace_placeholders( (string) $config['description'], $sep );
				}
			}
		}

		if ( ! empty( $title_tpl ) ) {
			$this->surerank_settings['page_title'] = $title_tpl;
		}
		if ( ! empty( $desc_tpl ) ) {
			$this->surerank_settings['page_description'] = $desc_tpl;
		}
	}

	/**
	 * Update archive settings.
	 */
	private function update_archive_settings(): void {
		$titles = $this->source_settings['seopress_titles_option_name'] ?? [];

		$this->surerank_settings['author_archive'] = ! array_key_exists( 'seopress_titles_archives_author_disable', $titles );

		$this->surerank_settings['date_archive'] = ! array_key_exists( 'seopress_titles_archives_date_disable', $titles );

		$this->surerank_settings['noindex_paginated_pages'] = ! array_key_exists( 'seopress_titles_paged_noindex', $titles );
	}

	/**
	 * Update social settings.
	 */
	private function update_social_settings(): void {
		$social = $this->source_settings['seopress_social_option_name'] ?? [];
		$titles = $this->source_settings['seopress_titles_option_name'] ?? [];
		$sep    = $this->get_separator();

		if ( isset( $social['seopress_social_facebook_og'] ) ) {
			$this->surerank_settings['open_graph_tags'] = ! empty( $social['seopress_social_facebook_og'] );
		}

		$this->surerank_settings['home_page_facebook_image_url'] = $social['seopress_social_facebook_img'] ?? '';
		$this->surerank_settings['home_page_twitter_image_url']  = $social['seopress_social_twitter_card_img'] ?? '';

		$this->surerank_settings['home_page_facebook_title']       = ! empty( $titles['seopress_titles_home_site_title'] ) ? Constants::replace_placeholders( (string) $titles['seopress_titles_home_site_title'], $sep ) : '';
		$this->surerank_settings['home_page_facebook_description'] = ! empty( $titles['seopress_titles_home_site_desc'] ) ? Constants::replace_placeholders( (string) $titles['seopress_titles_home_site_desc'], $sep ) : '';

		$use_og = isset( $social['seopress_social_twitter_card_og'] );
		$this->surerank_settings['home_page_twitter_title']       = $use_og
			? ( ! empty( $titles['seopress_titles_home_site_title'] ) ? Constants::replace_placeholders( (string) $titles['seopress_titles_home_site_title'], $sep ) : '' )
			: ( ! empty( $social['seopress_social_twitter_card_title'] ) ? Constants::replace_placeholders( (string) $social['seopress_social_twitter_card_title'], $sep ) : '' );
		$this->surerank_settings['home_page_twitter_description'] = $use_og
			? ( ! empty( $titles['seopress_titles_home_site_desc'] ) ? Constants::replace_placeholders( (string) $titles['seopress_titles_home_site_desc'], $sep ) : '' )
			: ( ! empty( $social['seopress_social_twitter_card_description'] ) ? Constants::replace_placeholders( (string) $social['seopress_social_twitter_card_description'], $sep ) : '' );

		$this->surerank_settings['facebook_page_url']        = $social['seopress_social_accounts_facebook'] ?? '';
		$this->surerank_settings['twitter_profile_username'] = $social['seopress_social_accounts_twitter'] ?? '';
		$this->surerank_settings['twitter_same_as_facebook'] = $use_og;

		if ( ! empty( $social['seopress_social_twitter_card_img_size'] ) && is_string( $social['seopress_social_twitter_card_img_size'] ) ) {
			$this->surerank_settings['twitter_card_type'] = strtolower( $social['seopress_social_twitter_card_img_size'] ) === 'large' ? 'summary_large_image' : 'summary';
		}

		if ( ! empty( $social['seopress_social_facebook_img'] ) && is_string( $social['seopress_social_facebook_img'] ) ) {
			$this->surerank_settings['fallback_image'] = $social['seopress_social_facebook_img'];
		}
	}

	/**
	 * Update sitemap settings.
	 */
	private function update_sitemap_settings(): void {
		$sitemap = $this->source_settings['seopress_xml_sitemap_option_name'] ?? [];
		if ( ! is_array( $sitemap ) ) {
			return;
		}

		$this->surerank_settings['enable_xml_sitemap']       = ! empty( $sitemap['seopress_xml_sitemap_general_enable'] );
		$this->surerank_settings['enable_xml_image_sitemap'] = ! empty( $sitemap['seopress_xml_sitemap_img_enable'] );
	}

	/**
	 * Update site details.
	 */
	private function update_site_details(): void {
		$social = $this->source_settings['seopress_social_option_name'] ?? [];
		$data   = [];

		if ( ! empty( $social['seopress_social_knowledge_name'] ) ) {
			$data['website_name'] = (string) $social['seopress_social_knowledge_name'];
		}

		if ( ! empty( $social['seopress_social_knowledge_type'] ) && is_string( $social['seopress_social_knowledge_type'] ) ) {
			$type                      = $social['seopress_social_knowledge_type'];
			$is_org                    = strtolower( $type ) === 'organization';
			$data['organization_type'] = $is_org ? 'Organization' : 'Person';
			$data['website_type']      = $is_org ? 'organization' : 'person';
		}

		if ( ! empty( $social['seopress_social_knowledge_img'] ) && is_string( $social['seopress_social_knowledge_img'] ) ) {
			$data['website_logo'] = $social['seopress_social_knowledge_img'];
		}

		if ( ! empty( $social['seopress_social_knowledge_phone'] ) ) {
			$data['website_owner_phone'] = (string) $social['seopress_social_knowledge_phone'];
		}

		$this->update_other_social_profiles();

		if ( ! empty( $data ) ) {
			Onboarding::update_common_onboarding_data( $data );
		}
	}

	/**
	 * Update other social profiles.
	 */
	private function update_other_social_profiles(): void {
		$social   = $this->source_settings['seopress_social_option_name'] ?? [];
		$profiles = [];
		$mapping  = [
			'seopress_social_accounts_twitter'   => 'twitter',
			'seopress_social_accounts_instagram' => 'instagram',
			'seopress_social_accounts_linkedin'  => 'linkedin',
			'seopress_social_accounts_youtube'   => 'youtube',
			'seopress_social_accounts_pinterest' => 'pinterest',
		];

		foreach ( $mapping as $source => $target ) {
			if ( ! empty( $social[ $source ] ) && is_string( $social[ $source ] ) ) {
				$profiles[ $target ] = $social[ $source ];
			}
		}

		if ( ! empty( $profiles ) ) {
			$this->surerank_settings['social_profiles'] = array_merge(
				$this->surerank_settings['social_profiles'] ?? [],
				$profiles
			);
		}

		$extra_social_profiles = $social['seopress_social_accounts_extra'] ?? [];

		$this->surerank_settings['social_profiles'] = ImporterUtils::get_mapped_social_profiles( $extra_social_profiles, $this->surerank_settings['social_profiles'] ?? [] );
	}

	/**
	 * Get the separator from titles settings.
	 *
	 * @return string
	 */
	private function get_separator(): string {
		$titles = $this->source_settings['seopress_titles_option_name'] ?? [];
		return isset( $titles['seopress_titles_sep'] ) && is_string( $titles['seopress_titles_sep'] ) ? $titles['seopress_titles_sep'] : ' - ';
	}

	/**
	 * Check if Twitter-specific social data exists
	 *
	 * @return bool True if Twitter has custom data, false if should use Facebook data
	 * @since 1.3.0
	 */
	private function has_twitter_specific_data(): bool {
		$twitter_fields = [
			'_seopress_social_twitter_title',
			'_seopress_social_twitter_desc',
			'_seopress_social_twitter_img',
			'_seopress_social_twitter_img_attachment_id',
		];

		foreach ( $twitter_fields as $field ) {
			if ( ! empty( $this->source_meta[ $field ] ) ) {
				return true;
			}
		}

		return false;
	}
}
