<?php
/**
 * Link SEO Processor
 *
 * Handles link-specific SEO enhancement logic.
 *
 * @package surerank
 * @since 1.5.0
 */

namespace SureRank\Inc\Frontend;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Link SEO processor
 *
 * @since 1.5.0
 */
class Link_Seo {

	use Get_Instance;

	/**
	 * Check if link enhancement is enabled
	 *
	 * @return bool
	 * @since 1.5.0
	 */
	public function is_enabled(): bool {
		return apply_filters( 'surerank_auto_add_nofollow_external_links', false );
	}

	/**
	 * Extract links that need processing
	 *
	 * @param string $content Clean content.
	 * @return array<string> Link tags that need enhancement
	 * @since 1.5.0
	 */
	public function extract_processable_links( $content ): array {
		return $this->extract_external_links( $content );
	}

	/**
	 * Process link tags in content
	 *
	 * @param string        $content Original content.
	 * @param array<string> $link_tags Link tags to process.
	 * @param int|null      $post_id Post context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	public function process_links( $content, $link_tags, $post_id ): string {
		$context = $this->build_processing_context( $post_id );
		return $this->enhance_link_tags( $content, $link_tags, $context );
	}

	/**
	 * Extract external links that need nofollow
	 *
	 * @param string $content Content to search.
	 * @return array<string> External link tags
	 * @since 1.5.0
	 */
	private function extract_external_links( $content ): array {
		/**
		 * Extract all anchor tags with href attributes from content
		 *
		 * Regex pattern breakdown:
		 * <a                           : Matches literal "<a"
		 * [^>]*                        : Match any characters except ">" (stay within opening tag)
		 * href=                        : Match literal "href="
		 * ["\']                        : Match opening quote (single or double)
		 * ([^"\']*)                    : Capture group 1 - Match and capture href value (any chars except quotes)
		 * ["\']                        : Match closing quote (single or double)
		 * [^>]*                        : Match remaining tag attributes until closing ">"
		 * >                            : Match closing bracket of opening tag
		 * i                            : Case-insensitive flag
		 *
		 * Examples of what this WILL match:
		 * - <a href="https://example.com">Link</a>
		 * - <A HREF='http://site.org' class="external">Link</A>
		 * - <a class="btn" href="mailto:test@example.com" id="contact">Email</a>
		 * - <a target="_blank" href="https://google.com" rel="noopener">Google</a>
		 *
		 * Captured groups:
		 * [0] => Full anchor tag: <a href="https://example.com" class="link">
		 * [1] => href value: https://example.com
		 *
		 * @param string $content The HTML content to search for anchor tags
		 * @param array $matches Output array containing matched anchor tags and href values
		 * @return int Number of matches found
		 *
		 * @see https://www.php.net/manual/en/reference.pcre.pattern.syntax.php
		 * @since 1.5.0
		 */
		preg_match_all( '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i', $content, $matches, PREG_SET_ORDER );

		$external_links   = [];
		$site_domain      = $this->get_site_domain();
		$excluded_domains = $this->get_excluded_domains();

		foreach ( $matches as $match ) {
			$full_tag = $match[0];
			$url      = $match[1];

			if ( $this->is_external_link( $url, $site_domain ) && ! $this->is_excluded_domain( $url, $excluded_domains ) && ! $this->already_has_nofollow( $full_tag ) ) {
				$external_links[] = $full_tag;
			}
		}

		return array_unique( $external_links );
	}

	/**
	 * Check if URL is external link
	 *
	 * @param string $url URL to check.
	 * @param string $site_domain Current site domain.
	 * @return bool True if external
	 * @since 1.5.0
	 */
	private function is_external_link( $url, $site_domain ): bool {
		if ( empty( $url ) || $url[0] === '#' ) {
			return false;
		}

		if ( $url[0] === '/' ) {
			return false;
		}

		$url_domain = wp_parse_url( $url, PHP_URL_HOST );
		return $url_domain && $url_domain !== $site_domain;
	}

	/**
	 * Check if domain is excluded
	 *
	 * @param string        $url URL to check.
	 * @param array<string> $excluded_domains Excluded domains.
	 * @return bool True if excluded
	 * @since 1.5.0
	 */
	private function is_excluded_domain( $url, $excluded_domains ): bool {
		if ( empty( $excluded_domains ) ) {
			return false;
		}

		$url_domain = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $url_domain ) {
			return false;
		}

		foreach ( $excluded_domains as $excluded_domain ) {
			if ( $url_domain === trim( $excluded_domain ) || strpos( $url_domain, '.' . trim( $excluded_domain ) ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if link already has nofollow
	 *
	 * @param string $link_tag Link tag.
	 * @return bool True if has nofollow
	 * @since 1.5.0
	 */
	private function already_has_nofollow( $link_tag ): bool {
		/**
		 * Check if anchor tag already contains nofollow in rel attribute
		 *
		 * Regex pattern breakdown:
		 * rel=                         : Match literal "rel="
		 * ["\']                        : Match opening quote (single or double)
		 * [^"\']*                      : Match any characters except quotes (before nofollow)
		 * nofollow                     : Match literal "nofollow"
		 * [^"\']*                      : Match any characters except quotes (after nofollow)
		 * ["\']                        : Match closing quote (single or double)
		 * i                            : Case-insensitive flag
		 *
		 * Examples of what this WILL match:
		 * - rel="nofollow"
		 * - rel="nofollow external"
		 * - rel="external nofollow"
		 * - rel="noopener nofollow noreferrer"
		 * - REL='NOFOLLOW'             (case insensitive)
		 *
		 * Examples of what this will NOT match:
		 * - rel="external"             (no nofollow)
		 * - rel=""                     (empty rel)
		 * - class="nofollow"           (wrong attribute)
		 * - href="nofollow.com"        (nofollow in URL, not rel)
		 *
		 * @param string $link_tag The anchor tag to check
		 * @return int 1 if pattern matches, 0 if no match, false on error
		 *
		 * @see https://www.php.net/manual/en/reference.pcre.pattern.syntax.php
		 * @since 1.5.0
		 */
		return preg_match( '/rel=["\'][^"\']*nofollow[^"\']*["\']/i', $link_tag ) === 1;
	}

	/**
	 * Get current site domain
	 *
	 * @return string Site domain
	 * @since 1.5.0
	 */
	private function get_site_domain(): string {
		$site_domain = wp_parse_url( home_url(), PHP_URL_HOST );
		return $site_domain ? $site_domain : '';
	}

	/**
	 * Get excluded domains list
	 *
	 * @return array<string> Excluded domains
	 * @since 1.5.0
	 */
	private function get_excluded_domains(): array {
		$excluded_domains = apply_filters( 'surerank_nofollow_excluded_domains', [] );

		if ( is_string( $excluded_domains ) ) {
			$excluded_domains = array_map( 'trim', explode( ',', $excluded_domains ) );
		}

		return is_array( $excluded_domains ) ? $excluded_domains : [];
	}

	/**
	 * Build processing context object
	 *
	 * @param int|null $post_id Post ID.
	 * @return object{post_id: int|null, site_domain: string} Context data
	 * @since 1.5.0
	 */
	private function build_processing_context( $post_id ): object {
		return (object) [
			'post_id'     => $post_id,
			'site_domain' => $this->get_site_domain(),
		];
	}

	/**
	 * Enhance individual link tags
	 *
	 * @param string                                         $content Original content.
	 * @param array<string>                                  $links Link tag array.
	 * @param object{post_id: int|null, site_domain: string} $context Processing context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	private function enhance_link_tags( $content, $links, $context ): string {
		foreach ( $links as $original_tag ) {
			$enhanced_tag = $this->enhance_single_link( $original_tag, $context );

			if ( $enhanced_tag !== $original_tag ) {
				$content = str_replace( $original_tag, $enhanced_tag, $content );
			}
		}

		return $content;
	}

	/**
	 * Enhance single link tag
	 *
	 * @param string                                         $tag Original link tag.
	 * @param object{post_id: int|null, site_domain: string} $context Processing context.
	 * @return string Enhanced tag
	 * @since 1.5.0
	 */
	private function enhance_single_link( $tag, $context ): string {
		$attributes = $this->parse_link_attributes( $tag );

		if ( empty( $attributes ) ) {
			return $tag;
		}

		$enhancement_needed = $this->calculate_needed_enhancements( $attributes );
		$enhancement_needed = apply_filters( 'surerank_link_seo_enhancements', $enhancement_needed, $attributes, $context );

		if ( ! $enhancement_needed ) {
			return $tag;
		}

		return $this->apply_enhancements( $attributes );
	}

	/**
	 * Parse attributes from link tag
	 *
	 * @param string $tag Link tag.
	 * @return array<string, string> Parsed attributes
	 * @since 1.5.0
	 */
	private function parse_link_attributes( $tag ): array {
		$attributes = [];

		/**
		 * Parse all attributes from an HTML anchor tag
		 *
		 * Regex pattern breakdown:
		 * ([a-zA-Z_:][a-zA-Z0-9\-_.:]*)   : Capture group 1 - Attribute name
		 *   [a-zA-Z_:]                     : First char: letter, underscore, or colon
		 *   [a-zA-Z0-9\-_.]*               : Remaining chars: alphanumeric, hyphen, dot, underscore, colon
		 * =                                : Literal equals sign
		 * ["\']                            : Opening quote (single or double)
		 * ([^"\']*)                        : Capture group 2 - Attribute value (any chars except quotes)
		 * ["\']                            : Closing quote (single or double)
		 *
		 * Examples of what this WILL match:
		 * - href="https://example.com"
		 * - class='btn external'
		 * - target="_blank"
		 * - rel="nofollow noopener"
		 * - data-toggle="modal"
		 * - xml:lang="en"                  (namespaced attributes)
		 *
		 * Examples of what this will NOT match:
		 * - href=https://example.com       (no quotes)
		 * - disabled                       (boolean attribute without value)
		 * - 123invalid="value"             (attribute name starts with number)
		 *
		 * Captured groups per match:
		 * [0] => Full match: href="https://example.com"
		 * [1] => Attribute name: href
		 * [2] => Attribute value: https://example.com
		 *
		 * @param string $tag The anchor tag to parse
		 * @param array $matches Output array containing all matched attributes
		 * @return int Number of matches found
		 *
		 * @see https://www.php.net/manual/en/reference.pcre.pattern.syntax.php
		 * @since 1.5.0
		 */
		if ( preg_match_all( '/([a-zA-Z_:][a-zA-Z0-9\-_.:]*)=["\']([^"\']*)["\']/', $tag, $matches, PREG_SET_ORDER ) ) {
			/**
			 * Process matches to build attribute array:
			 * [0] => href="https://example.com"  // Full match
			 * [1] => href                         // Attribute name
			 * [2] => https://example.com          // Attribute value
			 */
			foreach ( $matches as $match ) {
				$attributes[ $match[1] ] = $match[2];
			}
		}

		return $attributes;
	}

	/**
	 * Calculate which enhancements are needed
	 *
	 * @param array<string, string> $attributes Current attributes.
	 * @return bool Whether enhancement is needed
	 * @since 1.5.0
	 */
	private function calculate_needed_enhancements( $attributes ): bool {
		if ( ! isset( $attributes['href'] ) ) {
			return false;
		}

		$site_domain      = $this->get_site_domain();
		$excluded_domains = $this->get_excluded_domains();

		if ( ! $this->is_external_link( $attributes['href'], $site_domain ) || $this->is_excluded_domain( $attributes['href'], $excluded_domains ) ) {
			return false;
		}

		return $this->needs_any_rel_enhancement( $attributes );
	}

	/**
	 * Check if nofollow enhancement is needed for this link
	 *
	 * @param array<string, string> $attributes Link attributes.
	 * @return bool True if enhancement is needed
	 * @since 1.5.0
	 */
	private function needs_any_rel_enhancement( $attributes ): bool {
		$current_rel_values = $this->get_current_rel_values( $attributes );

		return $this->is_enabled() && ! in_array( 'nofollow', $current_rel_values, true );
	}

	/**
	 * Get current rel attribute values as array
	 *
	 * @param array<string, string> $attributes Link attributes.
	 * @return array<string> Current rel values
	 * @since 1.5.0
	 */
	private function get_current_rel_values( $attributes ): array {
		if ( ! isset( $attributes['rel'] ) ) {
			return [];
		}

		return array_map( 'trim', explode( ' ', $attributes['rel'] ) );
	}

	/**
	 * Apply enhancements to link attributes
	 *
	 * @param array<string, string> $attributes Original attributes.
	 * @return string Enhanced link tag
	 * @since 1.5.0
	 */
	private function apply_enhancements( $attributes ): string {
		$rel_values = isset( $attributes['rel'] )
			? array_map( 'trim', explode( ' ', $attributes['rel'] ) )
			: [];

		$rel_values = $this->apply_rel_enhancements( $rel_values );

		if ( ! empty( $rel_values ) ) {
			$attributes['rel'] = implode( ' ', array_filter( $rel_values ) );
		}

		$attributes = apply_filters( 'surerank_link_seo_enhanced_attributes', $attributes );

		return $this->build_link_tag( $attributes );
	}

	/**
	 * Apply all enabled rel attribute enhancements
	 *
	 * @param array<string> $rel_values Current rel values.
	 * @return array<string> Enhanced rel values
	 * @since 1.5.0
	 */
	private function apply_rel_enhancements( $rel_values ): array {
		if ( $this->is_enabled() && ! in_array( 'nofollow', $rel_values, true ) ) {
			$rel_values[] = 'nofollow';
		}

		/**
		 * Filter to allow custom rel attribute enhancements
		 *
		 * @param array<string> $rel_values Current rel values
		 */
		return apply_filters( 'surerank_link_seo_rel_enhancements', $rel_values );
	}

	/**
	 * Build complete link tag from attributes
	 *
	 * @param array<string, string> $attributes Attribute pairs.
	 * @return string Complete link tag
	 * @since 1.5.0
	 */
	private function build_link_tag( $attributes ): string {
		$attr_pairs = [];

		foreach ( $attributes as $name => $value ) {
			$attr_pairs[] = $this->format_attribute_pair( $name, $value );
		}

		return sprintf( '<a %s>', implode( ' ', $attr_pairs ) );
	}

	/**
	 * Format single attribute pair
	 *
	 * @param string $name Attribute name.
	 * @param string $value Attribute value.
	 * @return string Formatted pair
	 * @since 1.5.0
	 */
	private function format_attribute_pair( $name, $value ): string {
		return sprintf( '%s="%s"', esc_attr( $name ), esc_attr( $value ) );
	}
}
