<?php
/**
 * Abstract Analyzer class.
 *
 * Base class for performing SEO checks for WordPress entities with consistent output for UI.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Analyzer;

use DOMDocument;
use DOMXPath;
use SureRank\Inc\Functions\Get;

/**
 * Abstract Analyzer class.
 */
class Utils {

	/**
	 * Get rendered XPath.
	 *
	 * @param string $rendered_content Rendered content.
	 * @return DOMXPath|null
	 */
	public static function get_rendered_xpath( $rendered_content ) {
		if ( empty( $rendered_content ) ) {
			return null;
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );

		$encoded_content = mb_encode_numericentity(
			htmlspecialchars_decode(
				htmlentities( $rendered_content, ENT_NOQUOTES, 'UTF-8', false ),
				ENT_NOQUOTES
			),
			[ 0x80, 0x10FFFF, 0, ~0 ],
			/**
			 * Conversion map for mb_encode_numericentity:
			 * 0x80 (128) is the first non-ASCII Unicode code point.
			 * 0x10FFFF (1,114,111) is the highest valid Unicode code point.
			 * 0 is the bitmask for the first byte (no filtering).
			 * ~0 is the bitmask to include all characters in the range.
			 */
			'UTF-8'
		);

		if ( empty( $encoded_content ) ) {
			return null;
		}

		$dom->loadHTML( $encoded_content );
		libxml_clear_errors();
		return new DOMXPath( $dom );
	}

	/**
	 * Check for search engine title.
	 *
	 * @param string|null $title Title.
	 * @return array<string, mixed>
	 */
	public static function analyze_title( $title ) {
		if ( $title === null ) {
			return [
				'status'  => 'error',
				'message' => __( 'Search engine title is missing on the page.', 'surerank' ),
				'type'    => 'page',
			];
		}

		$title        = html_entity_decode( $title );
		$length       = mb_strlen( $title );
		$exists       = ! empty( $title );
		$is_optimized = $exists && $length <= Get::TITLE_LENGTH;
		// translators: %s is the search engine title length.
		$working_message = sprintf( __( 'Search engine title is present and under %s characters.', 'surerank' ), Get::TITLE_LENGTH );
		// translators: %s is the search engine title length.
		$exceeding_message = sprintf( __( 'Search engine title exceeds %s characters.', 'surerank' ), Get::TITLE_LENGTH );
		// translators: %s is the search engine title length.
		$missing_message = __( 'Search engine title is missing on the page.', 'surerank' );

		$message = $exists
			? ( $length <= Get::TITLE_LENGTH
				? $working_message
				: $exceeding_message )
			: $missing_message;

		$description = $exists && ! $is_optimized ? [
			// translators: %s is the search engine title.
			sprintf( __( 'The search engine title for the page is: "%s"', 'surerank' ), $title ),
		] : [];

		return [
			'status'  => $exists ? ( $is_optimized ? 'success' : 'warning' ) : 'error',
			'message' => $message,
			'type'    => 'page',
		];
	}

	/**
	 * Check for search engine description.
	 *
	 * @param string|null $description Description.
	 * @return array<string, mixed>
	 */
	public static function analyze_description( $description ) {

		if ( $description === null ) {
			return [
				'status'  => 'warning',
				'message' => __( 'Search engine description is missing on the page.', 'surerank' ),
				'type'    => 'page',
			];
		}

		$length       = mb_strlen( $description );
		$exists       = ! empty( $description );
		$is_optimized = $exists && $length >= Get::DESCRIPTION_MIN_LENGTH && $length <= Get::DESCRIPTION_LENGTH;
		// translators: %s is the search engine description length.
		$working_message = sprintf( __( 'Search engine description is present and under %s characters.', 'surerank' ), Get::DESCRIPTION_LENGTH );
		// translators: %s is the search engine description length.
		$exceeding_message = sprintf( __( 'Search engine description exceeds %s characters.', 'surerank' ), Get::DESCRIPTION_LENGTH );
		// translators: %s is the search engine description length.
		$missing_message = __( 'Search engine description is missing on the page.', 'surerank' );

		$message = $exists
			? ( $length <= Get::DESCRIPTION_LENGTH
				? $working_message
				: $exceeding_message )
			: $missing_message;

		/* translators: %s is the search engine description */
		$description = $exists && ! $is_optimized ? [ sprintf( __( 'The search engine description for the page is: "%s"', 'surerank' ), $description ) ] : [];

		return [
			'status'  => $exists && $length <= Get::DESCRIPTION_LENGTH ? 'success' : 'warning',
			'message' => $message,
			'type'    => 'page',
		];
	}

	/**
	 * Check for canonical URL.
	 *
	 * @param string|null $canonical Canonical URL.
	 * @param string|null $permalink Permalink URL.
	 * @return array<string, mixed>
	 */
	public static function analyze_canonical_url( $canonical, $permalink ) {
		if ( $canonical === null && $permalink === null ) {
			return [
				'status'  => 'warning',
				'message' => __( 'Canonical tag is not present on the page.', 'surerank' ),
				'type'    => 'page',
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Canonical tag is present on the page.', 'surerank' ),
			'type'    => 'page',
		];
	}

	/**
	 * Check for URL length.
	 *
	 * @param string|null $url URL.
	 * @return array<string, mixed>
	 */
	public static function check_url_length( $url ) {
		if ( $url === null ) {
			return [
				'status'  => 'warning',
				'message' => __( 'No URL provided.', 'surerank' ),
				'type'    => 'page',
			];
		}

		$length          = mb_strlen( $url );
		$exists          = ! empty( $url );
		$is_optimized    = $exists && $length <= Get::URL_LENGTH;
		$working_message = __( 'Page URL is short and SEO-friendly.', 'surerank' );

		/* translators: %s is the URL length. */
		$exceeding_message = sprintf( __( 'Page URL is longer than %s characters and may affect SEO and readability.', 'surerank' ), Get::URL_LENGTH );
		$missing_message   = __( 'No URL provided.', 'surerank' );

		$message = $exists
			? ( $is_optimized ? $working_message : $exceeding_message )
			: $missing_message;

		return [
			'status'  => $exists ? ( $is_optimized ? 'success' : 'warning' ) : 'warning',
			'message' => $message,
			'type'    => 'page',
		];
	}
	/**
	 * Get meta data.
	 *
	 * @param array<string, mixed> $meta Meta data.
	 * @return array<string, mixed>
	 */
	public static function get_meta_data( array $meta ) {
		$meta_data   = $meta['data'] ?? [];
		$global_data = $meta['global_default'] ?? [];

		if ( empty( $meta_data['page_title'] ) ) {
			$meta_data['page_title'] = $global_data['page_title'] ?? '';
			$meta_data['page_title'] = str_replace( '%title%', '%term_title%', $meta_data['page_title'] );
		}

		if ( empty( $meta_data['page_description'] ) ) {
			$meta_data['page_description'] = $global_data['page_description'] ?? '';
			$meta_data['page_description'] = str_replace( '%excerpt%', '%term_description%', $meta_data['page_description'] );
		}

		return $meta_data;
	}

	/**
	 * Get existing broken links.
	 *
	 * @param array<string, mixed> $broken_links Broken links.
	 * @param array<string>        $urls URLs.
	 * @return array<string, array<string, mixed>>
	 */
	public static function existing_broken_links( $broken_links, $urls ) {
		$description           = $broken_links['description'] ?? [];
		$existing_broken_links = [];
		foreach ( $description as $item ) {
			if ( is_array( $item ) && isset( $item['list'] ) ) {
				$existing_broken_links = $item['list'];
				break;
			}
		}

		$filtered_broken_links = [];

		if ( is_array( $existing_broken_links ) ) {
			foreach ( $existing_broken_links as $key => $existing_link ) {
				if ( is_string( $existing_link ) ) {
					if ( in_array( $existing_link, $urls, true ) ) {
						$filtered_broken_links[ $key ] = [
							'url'     => $existing_link,
							'status'  => 'error',
							'details' => __( 'The link is broken.', 'surerank' ),
							'type'    => 'page',
						];
					}
				} elseif ( is_array( $existing_link ) && isset( $existing_link['url'] ) ) {
					if ( in_array( $existing_link['url'], $urls, true ) ) {
						$filtered_broken_links[ $key ] = $existing_link;
					}
				}
			}
		}

		return $filtered_broken_links;
	}

	/**
	 * Check for open graph tags.
	 *
	 * @return array<string, mixed>
	 */
	public static function open_graph_tags() {

		if ( apply_filters( 'surerank_disable_open_graph_tags', false ) ) {
			return [
				'status'  => 'suggestion',
				'message' => __( 'Open Graph tags are not present on the page.', 'surerank' ),
				'type'    => 'page',
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Open Graph tags are present on the page.', 'surerank' ),
			'type'    => 'page',
		];
	}

	/**
	 * Analyze focus keyword in SEO title.
	 *
	 * @param string|null $title SEO title.
	 * @param string      $keyword Focus keyword.
	 * @return array<string, mixed>
	 */
	public static function analyze_keyword_in_title( $title, $keyword ) {
		if ( empty( $keyword ) ) {
			return [
				'status'  => 'suggestion',
				'message' => __( 'No focus keyword set to analyze title.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		if ( empty( $title ) ) {
			return [
				'status'  => 'warning',
				'message' => __( 'No SEO title found to analyze.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		if ( self::keyword_exists_in_text( $title, $keyword ) ) {
			return [
				'status'  => 'success',
				// translators: %s is the focus keyword.
				'message' => sprintf( __( 'Focus keyword "%s" found in SEO title.', 'surerank' ), $keyword ),
				'type'    => 'keyword',
			];
		}

		return [
			'status'  => 'warning',
			// translators: %s is the focus keyword.
			'message' => sprintf( __( 'Focus keyword "%s" not found in SEO title.', 'surerank' ), $keyword ),
			'type'    => 'keyword',
		];
	}

	/**
	 * Analyze focus keyword in meta description.
	 *
	 * @param string|null $description Meta description.
	 * @param string      $keyword Focus keyword.
	 * @return array<string, mixed>
	 */
	public static function analyze_keyword_in_description( $description, $keyword ) {
		if ( empty( $keyword ) ) {
			return [
				'status'  => 'suggestion',
				'message' => __( 'No focus keyword set to analyze meta description.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		if ( empty( $description ) ) {
			return [
				'status'  => 'warning',
				'message' => __( 'No meta description found to analyze.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		if ( self::keyword_exists_in_text( $description, $keyword ) ) {
			return [
				'status'  => 'success',
				// translators: %s is the focus keyword.
				'message' => sprintf( __( 'Focus keyword "%s" found in meta description.', 'surerank' ), $keyword ),
				'type'    => 'keyword',
			];
		}

		return [
			'status'  => 'warning',
			// translators: %s is the focus keyword.
			'message' => sprintf( __( 'Focus keyword "%s" not found in meta description.', 'surerank' ), $keyword ),
			'type'    => 'keyword',
		];
	}

	/**
	 * Analyze focus keyword in URL.
	 *
	 * @param string $url Page URL.
	 * @param string $keyword Focus keyword.
	 * @return array<string, mixed>
	 */
	public static function analyze_keyword_in_url( $url, $keyword ) {
		if ( empty( $keyword ) ) {
			return [
				'status'  => 'suggestion',
				'message' => __( 'No focus keyword set to analyze URL.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		if ( empty( $url ) ) {
			return [
				'status'  => 'warning',
				'message' => __( 'No URL found to analyze.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		// Convert keyword to URL-friendly format (lowercase, spaces to hyphens).
		$url_friendly_keyword = strtolower( str_replace( ' ', '-', $keyword ) );
		$url_lower            = strtolower( $url );

		if ( strpos( $url_lower, $url_friendly_keyword ) !== false || self::keyword_exists_in_text( $url, $keyword ) ) {
			return [
				'status'  => 'success',
				// translators: %s is the focus keyword.
				'message' => sprintf( __( 'Focus keyword "%s" found in URL.', 'surerank' ), $keyword ),
				'type'    => 'keyword',
			];
		}

		return [
			'status'  => 'warning',
			// translators: %s is the focus keyword.
			'message' => sprintf( __( 'Focus keyword "%s" not found in URL.', 'surerank' ), $keyword ),
			'type'    => 'keyword',
		];
	}

	/**
	 * Analyze focus keyword in content.
	 *
	 * @param string $content Page content.
	 * @param string $keyword Focus keyword.
	 * @return array<string, mixed>
	 */
	public static function analyze_keyword_in_content( $content, $keyword ) {
		if ( empty( $keyword ) ) {
			return [
				'status'  => 'suggestion',
				'message' => __( 'No focus keyword set to analyze content.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		if ( empty( $content ) ) {
			return [
				'status'  => 'warning',
				'message' => __( 'No content found to analyze.', 'surerank' ),
				'type'    => 'keyword',
			];
		}

		// Clean content of HTML tags for better analysis.
		$clean_content = wp_strip_all_tags( $content );
		$clean_content = preg_replace( '/\s+/', ' ', $clean_content );
		$clean_content = trim( (string) $clean_content );

		if ( self::keyword_exists_in_text( $clean_content, $keyword ) ) {
			return [
				'status'  => 'success',
				// translators: %s is the focus keyword.
				'message' => sprintf( __( 'Focus keyword "%s" found in content.', 'surerank' ), $keyword ),
				'type'    => 'keyword',
			];
		}

		return [
			'status'  => 'warning',
			// translators: %s is the focus keyword.
			'message' => sprintf( __( 'Focus keyword "%s" not found in content.', 'surerank' ), $keyword ),
			'type'    => 'keyword',
		];
	}

	/**
	 * Check if keyword exists in text (case-insensitive).
	 *
	 * @param string $text Text to search in.
	 * @param string $keyword Keyword to search for.
	 * @return bool
	 */
	private static function keyword_exists_in_text( $text, $keyword ) {
		if ( empty( $text ) || empty( $keyword ) ) {
			return false;
		}
		return stripos( $text, $keyword ) !== false;
	}
}
