<?php
/**
 * Custom Feed
 *
 * This file handles the functionality to print a custom feed on the frontend.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Class Feed
 * Handles functionality to print the custom feed in the frontend.
 *
 * @since 1.0.0
 */
class Feed {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'the_excerpt_rss', [ $this, 'custom_feed_content' ] );
		add_filter( 'the_content_feed', [ $this, 'custom_feed_content' ] );
		add_action( 'template_redirect', [ $this, 'disable_feed_indexing' ] );
		add_action( 'init', [ $this, 'additional_settings' ] );
	}

	/**
	 * Custom Feed Content
	 *
	 * @param string $content The content of the feed.
	 * @since 1.0.0
	 * @return string
	 */
	public function custom_feed_content( $content ) {
		// apply filter to add permalink to the content or not.
		if ( apply_filters( 'surerank_disable_permalink_in_feed', false ) ) {
			return $content;
		}

		$permalink = get_permalink();

		if ( ! $permalink ) {
			return $content;
		}

		// Append site name and link to the content.
		$content_after = sprintf(
			'<p>%s <a href="%s">%s</a></p>',
			esc_html__( 'Read more at', 'surerank' ),
			esc_url( $permalink ),
			esc_html( get_bloginfo( 'name' ) )
		);
		return $content . $content_after;
	}

	/**
	 * Disable Feed Indexing
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function disable_feed_indexing() {
		// apply filter to disable feed indexing or not.
		if ( apply_filters( 'surerank_disable_feed_indexing', false ) || ! is_feed() ) {
			return;
		}

		// Disable indexing of feed entries.
		header( 'X-Robots-Tag: noindex', true );
	}

	/**
	 * Additional Settings
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function additional_settings() {
		$global_meta = Settings::get();

		$meta_keys = [
			'remove_global_comments_feed'  => 'feed_links_show_comments_feed',
			'remove_post_authors_feed'     => 'feed_links_extra_show_author_feed',
			'remove_post_types_feed'       => 'feed_links_extra_show_post_feed',
			'remove_category_feed'         => 'feed_links_extra_show_category_feed',
			'remove_tag_feeds'             => 'feed_links_extra_show_tag_feed',
			'remove_custom_taxonomy_feeds' => 'feed_links_extra_show_tax_feed',
			'remove_search_results_feed'   => 'feed_links_extra_show_search_feed',
		];

		// Remove feed links based on settings.
		foreach ( $meta_keys as $meta_key => $filter ) {
			if ( ! empty( $global_meta[ $meta_key ] ) ) {
				add_filter( $filter, '__return_false' );
			}
		}

		// Disable Atom and RDF feeds.
		if ( ! empty( $global_meta['remove_atom_rdf_feeds'] ) ) {
			add_action( 'do_feed_atom', [ $this, 'disable_feed_and_redirect' ], 1 );
			add_action( 'do_feed_rdf', [ $this, 'disable_feed_and_redirect' ], 1 );
		}

		// Disable various feeds.
		$feed_actions = [
			'remove_global_comments_feed'  => 'do_feed_rss2_comments',
			'remove_post_authors_feed'     => 'do_feed_rss',
			'remove_post_types_feed'       => 'do_feed_rss2',
			'remove_category_feed'         => 'do_feed_category',
			'remove_tag_feeds'             => 'do_feed_tag',
			'remove_custom_taxonomy_feeds' => 'do_feed_custom_taxonomy',
			'remove_search_results_feed'   => 'do_feed_search',
		];

		foreach ( $feed_actions as $meta_key => $action ) {
			if ( ! empty( $global_meta[ $meta_key ] ) ) {
				add_action( $action, [ $this, 'disable_feed_and_redirect' ], 1 );
			}
		}
	}

	/**
	 * Disable Feed and Redirect
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function disable_feed_and_redirect() {
		wp_safe_redirect( esc_url( home_url() ), 301 );
		exit;
	}
}
