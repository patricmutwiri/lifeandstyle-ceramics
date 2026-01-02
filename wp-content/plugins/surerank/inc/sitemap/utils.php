<?php
/**
 * Sitemap Utilities
 *
 * Utility functions to manage XML headers, stylesheets, and other reusable functions for sitemaps.
 *
 * @since 0.0.1
 * @package SureRank
 */

namespace SureRank\Inc\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use DOMDocument;
use DOMElement;
use DOMProcessingInstruction;
use RuntimeException;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Sitemap Utility Functions Class
 *
 * Provides methods for generating XML for sitemap indexes and main sitemaps.
 *
 * @since 1.0.0
 */
class Utils {
	use Get_Instance;

	/**
	 * Generates the XML for the sitemap index.
	 *
	 * @param array<string, mixed>|array<int, string> $sitemap List of sitemap URLs to include in the index.
	 * @return string XML string for the sitemap index.
	 * @throws RuntimeException If XML generation fails.
	 */
	public static function sitemap_index( array $sitemap ): string {
		self::output_headers();

		$dom            = self::create_dom();
		$xsl_stylesheet = self::add_stylesheet( $dom, 'sitemap-stylesheet.xsl' );
		$dom->appendChild( $xsl_stylesheet );

		$sitemap_index = self::create_urlset( 'sitemapindex', $dom );

		// Add each sitemap URL to the <sitemapindex>.
		foreach ( $sitemap as $url ) {
			$sitemap_element   = $dom->createElement( 'sitemap' );
			$loc               = $dom->createElement( 'loc', esc_url( (string) $url['link'] ) );
			$formatted_lastmod = (string) $url['updated'];
			$lastmod           = $dom->createElement( 'lastmod', esc_xml( (string) $formatted_lastmod ) );
			$sitemap_element->appendChild( $loc );
			$sitemap_element->appendChild( $lastmod );
			$sitemap_index->appendChild( $sitemap_element );
		}

		// Return the XML as a string or throw an exception if saveXML fails.
		$xml_string = $dom->saveXML();
		if ( false === $xml_string ) {
			throw new RuntimeException( 'Failed to generate sitemap index XML.' );
		}
		return $xml_string;
	}

	/**
	 * Generates the XML for the main sitemap with individual URLs.
	 *
	 * @param array<string, mixed>|array<int, string> $sitemap Array of URL data, each containing 'link', 'updated', and optional 'images'.
	 * @return string XML string for the main sitemap.
	 * @throws RuntimeException If XML generation fails.
	 */
	public static function sitemap_main( array $sitemap ): string {
		self::output_headers();

		$dom  = self::create_dom();
		$xslt = self::add_stylesheet( $dom, 'sitemap-stylesheet.xsl' );
		$dom->appendChild( $xslt );

		// Create the root <urlset> element.
		$urlset = self::create_urlset( 'urlset', $dom );
		$urlset->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$urlset->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
		$urlset->setAttribute( 'xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' );
		$urlset->setAttribute( 'xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1' );

		foreach ( $sitemap as $url ) {
			$url_element = $dom->createElement( 'url' );

			$loc = $dom->createElement( 'loc', esc_url( (string) $url['link'] ) );
			$url_element->appendChild( $loc );

			$lastmod = $dom->createElement( 'lastmod', esc_xml( (string) $url['updated'] ) );
			$url_element->appendChild( $lastmod );

			// Add each image if available.
			if ( isset( $url['images_data'] ) ) {
				foreach ( $url['images_data'] as $image ) {
					$image_element = $dom->createElement( 'image:image' );

					$image_loc = $dom->createElement( 'image:loc', esc_url( (string) $image['link'] ) );
					$image_element->appendChild( $image_loc );

					$url_element->appendChild( $image_element );
				}
			}

			// Append the <url> element to <urlset>.
			$urlset->appendChild( $url_element );
		}

		// Return the XML as a string or throw an exception if saveXML fails.
		$xml_string = $dom->saveXML();
		if ( false === $xml_string ) {
			throw new RuntimeException( 'Failed to generate main sitemap XML.' );
		}
		return $xml_string;
	}

	/**
	 * Outputs necessary headers for XML display.
	 *
	 * @return void
	 */
	public static function output_headers() {
		$headers = [
			'Pragma'        => 'public',
			'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
			'Content-Type'  => 'application/xml; charset=UTF-8',
		];
		foreach ( $headers as $header => $value ) {
			header( "{$header}: {$value}" );
		}
	}

	/**
	 * Creates and initializes a new DOMDocument for XML.
	 *
	 * @return DOMDocument Initialized DOMDocument instance.
	 */
	public static function create_dom(): DOMDocument {
		$doc               = new DOMDocument( '1.0', 'UTF-8' );
		$doc->formatOutput = true;
		return $doc;
	}

	/**
	 * Creates and appends a root XML element for the sitemap.
	 *
	 * @param string      $element Name of the root element (e.g., 'sitemapindex' or 'urlset').
	 * @param DOMDocument $dom     The DOMDocument instance to append to.
	 * @return DOMElement The created root element.
	 */
	public static function create_urlset( string $element, DOMDocument $dom ): DOMElement {
		$urlset = $dom->createElement( $element );
		$urlset->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$dom->appendChild( $urlset );
		return $urlset;
	}

	/**
	 * Adds an XSL stylesheet to the DOM.
	 *
	 * @param DOMDocument $dom The DOMDocument instance to add the stylesheet to.
	 * @param string      $url Path to the XSL stylesheet.
	 * @return DOMProcessingInstruction The created stylesheet instruction.
	 */
	public static function add_stylesheet( DOMDocument $dom, string $url ): DOMProcessingInstruction {
		return $dom->createProcessingInstruction( 'xml-stylesheet', 'type="text/xsl" href="' . esc_url( site_url( '/' ) . $url ) . '"' );
	}

	/**
	 * Retrieves all images associated with a specific post.
	 *
	 * @param int $post_id The ID of the post.
	 * @return array<string, mixed> Array of image URLs.
	 */
	public static function get_images_from_post( int $post_id ) {
		$images        = [];
		$thumbnail_url = self::get_thumbnail_image( $post_id );

		if ( ! empty( $thumbnail_url ) ) {
			$images[] = $thumbnail_url;
		}

		$images = array_merge( $images, self::get_images_from_post_content( $post_id ) );
		$images = array_merge( $images, self::get_images_from_gallery_shortcode( $post_id ) );

		$attachment_image = self::get_attachment_image( $post_id );
		if ( ! empty( $attachment_image ) ) {
			$images[] = $attachment_image;
		}

		return $images;
	}

	/**
	 * Retrieves the post's thumbnail image.
	 *
	 * @param int $post_id Post ID.
	 * @return string Thumbnail image URL or empty string if no thumbnail.
	 */
	public static function get_thumbnail_image( int $post_id ): string {
		$thumbnail_id  = get_post_thumbnail_id( $post_id );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '';
		return $thumbnail_url ? $thumbnail_url : ''; // Ensure it returns a string.
	}

	/**
	 * Extracts images from the post content.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|array<int, string> Array of image URLs.
	 */
	public static function get_images_from_post_content( int $post_id ) {
		$images  = [];
		$content = get_post_field( 'post_content', $post_id );

		preg_match_all( '/<img[^>]+src="([^">]+)"/i', $content, $matches );
		if ( ! empty( $matches[1] ) ) {
			$images = $matches[1];
		}
		return $images;
	}

	/**
	 * Retrieves image URL if post is an image attachment.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Image URL if attachment is an image, null otherwise.
	 */
	public static function get_attachment_image( int $post_id ): ?string {
		if ( 'attachment' === get_post_type( $post_id ) && wp_attachment_is_image( $post_id ) ) {
			$attachment_url = wp_get_attachment_url( $post_id );
			return $attachment_url ? $attachment_url : null; // Ensure it returns string or null.
		}
		return null;
	}

	/**
	 * Extracts images from gallery shortcodes in the post content.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|array<int, string> Array of image URLs from the gallery shortcode.
	 */
	public static function get_images_from_gallery_shortcode( int $post_id ) {
		$images  = [];
		$content = get_post_field( 'post_content', $post_id );

		if ( preg_match_all( '/' . get_shortcode_regex( [ 'gallery' ] ) . '/s', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $shortcode ) {
				if ( 'gallery' === $shortcode[2] ) {
					$attributes = shortcode_parse_atts( $shortcode[3] );
					if ( ! empty( $attributes['ids'] ) ) {
						$ids = explode( ',', $attributes['ids'] );
						foreach ( $ids as $id ) {
							$image_url = wp_get_attachment_url( (int) $id );
							if ( $image_url ) {
								$images[] = $image_url;
							}
						}
					}
				}
			}
		}
		return $images;
	}

	/**
	 * Outputs the stylesheet for the sitemap.
	 *
	 * @param string $stylesheet The stylesheet to output.
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_stylesheet( $stylesheet ) {

		self::output_headers();

		$sitemap_title = esc_html( get_bloginfo( 'name' ) . ' Sitemap' );
		$sitemap_slug  = Xml_Sitemap::get_slug();

		$stylesheet_obj     = new Stylesheet();
		$stylesheet_content = $stylesheet_obj->generate( $sitemap_title, $sitemap_slug );

		echo apply_filters( 'surerank_sitemap_output_stylesheet', $stylesheet_content, $stylesheet ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Get noindex settings.
	 *
	 * @return array<string, mixed>|array<int, string>
	 */
	public static function get_noindex_settings() {
		$settings = Get::option( SURERANK_SETTINGS );
		return $settings['no_index'] ?? [];
	}

	/**
	 * Get meta query for indexable content based on no_index settings
	 *
	 * @param string $content_type The post type or taxonomy name.
	 * @return array<int|string, mixed> The meta query array.
	 */
	public static function get_indexable_meta_query( string $content_type ): array {
		$no_index_settings = self::get_noindex_settings();

		if ( in_array( $content_type, $no_index_settings, true ) ) {
			return [
				[
					'key'     => 'surerank_settings_post_no_index',
					'value'   => 'no',
					'compare' => '=',
				],
			];
		}

		return [
			'relation' => 'OR',
			[
				'key'     => 'surerank_settings_post_no_index',
				'value'   => 'yes',
				'compare' => '!=',
			],
			[
				'key'     => 'surerank_settings_post_no_index',
				'compare' => 'NOT EXISTS',
			],
		];
	}

}
