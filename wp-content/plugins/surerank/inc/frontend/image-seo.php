<?php
/**
 * Image SEO Processor
 *
 * Handles image-specific SEO enhancement logic.
 *
 * @package surerank
 * @since 1.5.0
 */

namespace SureRank\Inc\Frontend;

use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image SEO processor
 *
 * @since 1.5.0
 */
class Image_Seo {

	use Get_Instance;

	/**
	 * Check if image enhancement is enabled
	 *
	 * @return bool
	 * @since 1.5.0
	 */
	public function is_enabled(): bool {
		return apply_filters( 'surerank_auto_set_image_title_and_alt', true );
	}

	/**
	 * Backward compatibility method for status check
	 *
	 * @return bool
	 * @since 1.5.0
	 */
	public function status(): bool {
		return $this->is_enabled();
	}

	/**
	 * Extract images that need processing
	 *
	 * @param string $content Clean content.
	 * @return array<string> Image tags that need enhancement
	 * @since 1.5.0
	 */
	public function extract_processable_images( $content ): array {
		$missing_alt_images   = $this->extract_images_missing_alt( $content );
		$missing_title_images = $this->extract_images_missing_title( $content );

		if ( empty( $missing_alt_images ) && empty( $missing_title_images ) ) {
			return [];
		}

		return array_unique( array_merge( $missing_alt_images, $missing_title_images ) );
	}

	/**
	 * Process image tags in content
	 *
	 * @param string        $content Original content.
	 * @param array<string> $image_tags Image tags to process.
	 * @param int|null      $post_id Post context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	public function process_images( $content, $image_tags, $post_id ): string {
		$context = $this->build_processing_context( $post_id );
		return $this->enhance_image_tags( $content, $image_tags, $context );
	}

	/**
	 * Extract images missing alt attributes
	 *
	 * @param string $content Content to search.
	 * @return array<string> Image tags missing alt
	 * @since 1.5.0
	 */
	private function extract_images_missing_alt( $content ): array {
		/**
		 * Finds all <img> tags that are missing proper alt attributes for accessibility compliance.
		 *
		 * Regex breakdown:
		 * <img                                    : Matches literal "<img"
		 * (?!                                     : Start negative lookahead (ensure pattern does NOT exist)
		 *   [^>]*                                 : Match any chars except ">" (stay within tag)
		 *   alt\s*=\s*                            : Match "alt" + optional whitespace + "=" + optional whitespace
		 *   ["\']                                 : Match opening quote (single or double)
		 *   [^"\'\s]                              : Match at least one non-quote, non-whitespace character
		 *   [^"\']*                               : Match remaining non-quote characters
		 *   ["\']                                 : Match closing quote
		 * )                                       : End negative lookahead
		 * [^>]*>                                  : Match remaining tag content until closing ">"
		 * i                                       : Case-insensitive flag
		 *
		 * Examples of what this WILL match (accessibility violations):
		 * - <img src="photo.jpg">                 (no alt attribute)
		 * - <img src="photo.jpg" alt="">          (empty alt)
		 * - <img src="photo.jpg" alt=" ">         (whitespace-only alt)
		 * - <IMG SRC="photo.jpg" ALT="">          (case variations)
		 *
		 * Examples of what this will NOT match (valid alt attributes):
		 * - <img src="photo.jpg" alt="A photo">   (valid alt text)
		 * - <img src="photo.jpg" alt="User avatar"> (descriptive alt text)
		 *
		 * @param string $content The HTML content to search for non-compliant img tags
		 * @param array $matches Output array that will contain all matched img tags
		 * @return int Number of matches found
		 *
		 * @see https://www.php.net/manual/en/reference.pcre.pattern.syntax.php
		 * @since 1.0.0
		 */
		preg_match_all( '/<img(?![^>]*alt\s*=\s*["\'][^"\'\s][^"\']*["\'])[^>]*>/i', $content, $matches );
		return $matches[0];
	}

	/**
	 * Extract images missing title attributes
	 *
	 * @param string $content Content to search.
	 * @return array<string> Image tags missing title
	 * @since 1.5.0
	 */
	private function extract_images_missing_title( $content ): array {
		/**
		 * Finds all <img> tags that are missing proper title attributes for accessibility compliance.
		 *
		 * Regex breakdown:
		 * <img                                    : Matches literal "<img"
		 * (?!                                     : Start negative lookahead (ensure pattern does NOT exist)
		 *   [^>]*                                 : Match any chars except ">" (stay within tag)
		 *   title\s*=\s*                            : Match "title" + optional whitespace + "=" + optional whitespace
		 *   ["\']                                 : Match opening quote (single or double)
		 *   [^"\'\s]                              : Match at least one non-quote, non-whitespace character
		 *   [^"\']*                               : Match remaining non-quote characters
		 *   ["\']                                 : Match closing quote
		 * )                                       : End negative lookahead
		 * [^>]*>                                  : Match remaining tag content until closing ">"
		 * i                                       : Case-insensitive flag
		 *
		 * Examples of what this WILL match (accessibility violations):
		 * - <img src="photo.jpg">                 (no title attribute)
		 * - <img src="photo.jpg" title="">          (empty title)
		 * - <img src="photo.jpg" title=" ">         (whitespace-only title)
		 * - <IMG SRC="photo.jpg" TITLE="">          (case variations)
		 *
		 * Examples of what this will NOT match (valid title attributes):
		 * - <img src="photo.jpg" title="A photo">   (valid title text)
		 * - <img src="photo.jpg" title="User avatar"> (descriptive title text)
		 *
		 * @param string $content The HTML content to search for non-compliant img tags
		 * @param array $matches Output array that will contain all matched img tags
		 * @return int Number of matches found
		 *
		 * @see https://www.php.net/manual/en/reference.pcre.pattern.syntax.php
		 * @since 1.0.0
		 */
		preg_match_all( '/<img(?![^>]*title\s*=\s*["\'][^"\'\s][^"\']*["\'])[^>]*>/i', $content, $matches );
		return $matches[0];
	}

	/**
	 * Build processing context object
	 *
	 * @param int|null $post_id Post ID.
	 * @return object{title: string, slug: string, site_name: string} Context data
	 * @since 1.5.0
	 */
	private function build_processing_context( $post_id ): object {
		$post = get_post( $post_id );

		return (object) [
			'title'     => $post->post_title ?? '',
			'slug'      => $post->post_name ?? '',
			'site_name' => get_bloginfo( 'name' ),
		];
	}

	/**
	 * Enhance individual image tags
	 *
	 * @param string                                                 $content Original content.
	 * @param array<string>                                          $images Image tag array.
	 * @param object{title: string, slug: string, site_name: string} $context Processing context.
	 * @return string Enhanced content
	 * @since 1.5.0
	 */
	private function enhance_image_tags( $content, $images, $context ): string {
		foreach ( $images as $original_tag ) {
			$enhanced_tag = $this->enhance_single_image( $original_tag, $context );

			if ( $enhanced_tag !== $original_tag ) {
				$content = str_replace( $original_tag, $enhanced_tag, $content );
			}
		}

		return $content;
	}

	/**
	 * Enhance single image tag
	 *
	 * @param string                                                 $tag Original image tag.
	 * @param object{title: string, slug: string, site_name: string} $context Processing context.
	 * @return string Enhanced tag
	 * @since 1.5.0
	 */
	private function enhance_single_image( $tag, $context ): string {
		$attributes = $this->parse_image_attributes( $tag );

		if ( empty( $attributes ) ) {
			return $tag;
		}

		$image_src = $this->resolve_image_source( $attributes );

		if ( empty( $image_src ) ) {
			return $tag;
		}

		$enhancements = $this->calculate_needed_enhancements( $attributes );
		$enhancements = apply_filters( 'surerank_image_seo_enhancements', $enhancements, $attributes, $image_src, $context );

		if ( empty( $enhancements ) ) {
			return $tag;
		}

		return $this->apply_enhancements( $attributes, $enhancements, $image_src, $context );
	}

	/**
	 * Parse attributes from image tag
	 *
	 * @param string $tag Image tag.
	 * @return array<string, string> Parsed attributes
	 * @since 1.5.0
	 */
	private function parse_image_attributes( $tag ): array {
		$attributes = [];

		/**
		 * Using regex to parse image attributes
		 *
		 * Regex pattern breakdown:
		 *  ([a-zA-Z_:][a-zA-Z0-9\-_.:]*)        : Check for the attribute name.
		 *   [a-zA-Z_:]                         : First char: letter, underscore, or colon
		 *   [a-zA-Z0-9\-_.]*                   : Remaining chars: alphanumeric, hyphen, dot, underscore, colon
		 * =                                    : Literal equals sign
		 * ["\']                                : Opening quote (single or double)
		 * ([^"\']*)                            : Capture group 2 - Attribute value (any chars except quotes)
		 * ["\']                                : Closing quote (single or double)
		 * i                                    : Case-insensitive flag
		 *
		 * Examples of what this WILL match (accessibility violations):
		 * - <img src="photo.jpg">                 (no alt attribute)
		 * - <img src="photo.jpg" alt="">          (empty alt)
		 * - <img src="photo.jpg" alt=" ">         (whitespace-only alt)
		 * - <IMG SRC="photo.jpg" ALT="">          (case variations)
		 *
		 * Examples of what this will NOT match (valid alt attributes):
		 * - <img src="photo.jpg" alt="A photo">   (valid alt text)
		 * - <img src="photo.jpg" alt="User avatar"> (descriptive alt text)
		 */
		if ( preg_match_all( '/([a-zA-Z_:][a-zA-Z0-9\-_.:]*)=["\']([^"\']*)["\']/', $tag, $matches, PREG_SET_ORDER ) ) {
			/**
			 * [0] => src="photo.jpg"      // Full match
			 * [1] => src                  // Attribute name
			 * [2] => photo.jpg            // Attribute value
			 */
			foreach ( $matches as $match ) {
				$attributes[ $match[1] ] = $match[2];
			}
		}

		return $attributes;
	}

	/**
	 * Resolve image source URL (supports lazy loading)
	 *
	 * @param array<string, string> $attributes Image attributes.
	 * @return string Image source
	 * @since 1.5.0
	 */
	private function resolve_image_source( $attributes ): string {
		$lazy_attrs = [ 'data-src', 'data-lazy-src', 'data-layzr' ];

		foreach ( $lazy_attrs as $attr ) {
			if ( ! empty( $attributes[ $attr ] ) ) {
				return $attributes[ $attr ];
			}
		}

		return $attributes['src'] ?? '';
	}

	/**
	 * Calculate which enhancements are needed
	 *
	 * @param array<string, string> $attributes Current attributes.
	 * @return array<string, string> Needed enhancements
	 * @since 1.5.0
	 */
	private function calculate_needed_enhancements( $attributes ): array {
		$needed = [];

		$auto_add_alt = ! empty( Settings::get( 'auto_set_image_alt' ) );

		if ( $auto_add_alt && empty( $attributes['alt'] ) ) {
			$needed['alt'] = apply_filters( 'surerank_image_seo_alt_template', '%filename%' );
		}

		if ( apply_filters( 'surerank_image_seo_enable_title', true ) && empty( $attributes['title'] ) ) {
			$needed['title'] = apply_filters( 'surerank_image_seo_title_template', '%title%' );
		}

		return $needed;
	}

	/**
	 * Apply enhancements to image attributes
	 *
	 * @param array<string, string>                                  $attributes Original attributes.
	 * @param array<string, string>                                  $enhancements Needed enhancements.
	 * @param string                                                 $src Image source.
	 * @param object{title: string, slug: string, site_name: string} $context Processing context.
	 * @return string Enhanced image tag
	 * @since 1.5.0
	 */
	private function apply_enhancements( $attributes, $enhancements, $src, $context ): string {
		$filename = $this->extract_clean_filename( $src );

		foreach ( $enhancements as $attr => $template ) {
			$attributes[ $attr ] = $this->resolve_template( $template, $context, $filename );
		}

		return $this->build_image_tag( $attributes );
	}

	/**
	 * Extract and clean filename from URL
	 *
	 * @param string $url Image URL.
	 * @return string Clean filename
	 * @since 1.5.0
	 */
	private function extract_clean_filename( $url ): string {
		if ( empty( $url ) ) {
			return '';
		}

		return $this->sanitize_filename( $this->get_basename_without_extension( $url ) );
	}

	/**
	 * Get filename without extension
	 *
	 * @param string $url URL.
	 * @return string Basename
	 * @since 1.5.0
	 */
	private function get_basename_without_extension( $url ): string {
		$filename = basename( $url );
		/**
		 * Using regex to get the basename without the extension
		 * Regex pattern breakdown:
		 *
		 * \. matches a literal dot
		 * [^.]+ matches one or more characters that are not a dot
		 * $ matches the end of the string
		 */
		$result = preg_replace( '/\.[^.]+$/', '', $filename );
		return $result !== null ? $result : $filename;
	}

	/**
	 * Sanitize filename for readability
	 *
	 * @param string $filename Raw filename.
	 * @return string Sanitized filename
	 * @since 1.5.0
	 */
	private function sanitize_filename( $filename ): string {
		/**
		 * Using regex to sanitize the filename
		 * Regex pattern breakdown:
		 *
		 * [-_] matches a hyphen or underscore
		 * + matches one or more of the preceding element
		 * $ matches the end of the string
		 */
		$cleaned      = preg_replace( '/[-_]+/', ' ', $filename );
		$safe_cleaned = $cleaned !== null ? $cleaned : $filename;
		return ucwords( trim( $safe_cleaned ) );
	}

	/**
	 * Resolve template variables
	 *
	 * @param string                                                 $template Template string.
	 * @param object{title: string, slug: string, site_name: string} $context Context data.
	 * @param string                                                 $filename Clean filename.
	 * @return string Resolved string
	 * @since 1.5.0
	 */
	private function resolve_template( $template, $context, $filename ): string {
		if ( empty( $template ) ) {
			return '';
		}

		$variables = $this->build_variable_map( $context, $filename );

		$resolved = trim( strtr( $template, $variables ) );

		return apply_filters( 'surerank_image_seo_resolved_text', $resolved, $template, $context, $filename );
	}

	/**
	 * Build variable replacement map
	 *
	 * @param object{title: string, slug: string, site_name: string} $context Context data.
	 * @param string                                                 $filename Filename.
	 * @return array<string, string> Variable mappings
	 * @since 1.5.0
	 */
	private function build_variable_map( $context, $filename ): array {
		$default_vars = [
			'%title%'     => $context->title,
			'%filename%'  => $filename,
			'%site_name%' => $context->site_name,
			'%slug%'      => $context->slug,
		];

		return apply_filters( 'surerank_image_seo_variable_map', $default_vars, $context, $filename );
	}

	/**
	 * Build complete image tag from attributes
	 *
	 * @param array<string, string> $attributes Attribute pairs.
	 * @return string Complete image tag
	 * @since 1.5.0
	 */
	private function build_image_tag( $attributes ): string {
		$attr_pairs = [];

		foreach ( $attributes as $name => $value ) {
			$attr_pairs[] = $this->format_attribute_pair( $name, $value );
		}

		return sprintf( '<img %s>', implode( ' ', $attr_pairs ) );
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
