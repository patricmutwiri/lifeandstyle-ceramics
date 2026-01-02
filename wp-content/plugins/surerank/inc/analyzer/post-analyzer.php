<?php
/**
 * Post Analyzer class.
 *
 * Performs SEO checks for WordPress posts like page, post, cpts with consistent output for UI.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Analyzer;

use DOMElement;
use DOMNodeList;
use DOMXPath;
use SureRank\Inc\API\Admin;
use SureRank\Inc\API\Post;
use SureRank\Inc\Frontend\Image_Seo;
use SureRank\Inc\Functions\Get;
use SureRank\Inc\Functions\Helper;
use SureRank\Inc\Functions\Settings;
use SureRank\Inc\Functions\Update;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\Traits\Logger;
use WP_Post;

/**
 * Post Analyzer
 */
class PostAnalyzer {
	use Get_Instance;
	use Logger;

	/**
	 * XPath instance.
	 *
	 * @var DOMXPath|null
	 */
	private $xpath;

	/**
	 * Page title.
	 *
	 * @var string|null
	 */
	private $page_title;

	/**
	 * Page description.
	 *
	 * @var string|null
	 */
	private $page_description = '';

	/**
	 * Canonical URL.
	 *
	 * @var string|null
	 */
	private $canonical_url = '';

	/**
	 * Post ID.
	 *
	 * @var int|null
	 */
	private $post_id;

	/**
	 * Post permalink.
	 *
	 * @var string
	 */
	private $post_permalink = '';

	/**
	 * Post content.
	 *
	 * @var string
	 */
	private $post_content = '';

	/**
	 * Constructor.
	 */
	private function __construct() {
		if ( ! Settings::get( 'enable_page_level_seo' ) ) {
			return;
		}
		add_action( 'wp_after_insert_post', [ $this, 'save_post' ], 10, 2 );
		add_filter( 'surerank_run_post_seo_checks', [ $this, 'run_checks' ], 10, 2 );
	}

	/**
	 * Handle post save to run SEO checks.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object || ! $post_type_object->public ||
			in_array( $post_type, apply_filters( 'surerank_excluded_post_types_from_seo_checks', [] ), true ) ) {
			return;
		}
		$response = $this->run_checks( $post_id, $post );

		if ( isset( $response['status'] ) && 'error' === $response['status'] ) {
			self::log( $response['message'] );
		}
	}

	/**
	 * Run SEO checks for the post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return array<string, mixed>
	 */
	public function run_checks( $post_id, $post ) {
		$this->post_id = $post_id;

		if ( ! $this->post_id || ! $post instanceof WP_Post ) {
			return [
				'status' => 'error',
			];
		}

		$meta_data = Post::get_post_data_by_id( $post_id, $post->post_type, false );
		$variables = Admin::get_instance()->get_variables( $post_id, null );
		$meta_data = Utils::get_meta_data( $meta_data );

		foreach ( $meta_data as $key => $value ) {
			$meta_data[ $key ] = Helper::replacement( $key, $value, $variables );
		}

		$this->page_title       = $meta_data['page_title'] ?? '';
		$this->page_description = $meta_data['page_description'] ?? '';
		$this->canonical_url    = $meta_data['canonical_url'] ?? '';
		$this->post_permalink   = $this->get_original_permalink( $post_id, $post );
		$this->post_content     = apply_filters( 'surerank_post_analyzer_content', $post->post_content, $post );
		/**
		 * Parse blocks and render them to get the rendered content.
		 * Commented out because it's not needed for the analyzer.
		 *
		 * Kept here for reference if needed in the future.
		 *
		 * $blocks           = parse_blocks( $post_content );
		 * foreach ( $blocks as $block ) {
		 *  $rendered_content .= render_block( $block );
		 * }
		 */

		$this->xpath = Utils::get_rendered_xpath( $this->post_content );
		$result      = $this->analyze( $meta_data );

		if ( $this->update_broken_links_status( $result ) && is_array( $result ) ) {
			$result['broken_links'] = $this->update_broken_links_status( $result );
		}

		$success = Update::post_seo_checks( $post_id, $result );

		if ( ! $success ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update SEO checks', 'surerank' ),
			];
		}

		return $result;
	}

	/**
	 * Determine whether a URL should be skipped. We use an inclusive check:
	 * - Allow absolute http/https URLs
	 * - Allow relative URLs (no scheme)
	 * - Skip everything else (mailto:, tel:, sms:, geo:, javascript:, data:, etc.)
	 *
	 * @param string $href URL or href attribute value.
	 * @return bool True if the URL should be skipped.
	 */
	public static function should_skip_url( string $href ): bool {
		$href = trim( $href );
		if ( $href === '' ) {
			return true;
		}

		// Skip anchors like #section.
		if ( strpos( $href, '#' ) === 0 ) {
			return true;
		}

		// If the URL contains a scheme, parse it and allow only http/https.
		// Examples: mailto:, tel:, sms:, geo:, javascript:, data: will be skipped.
		$parts  = wp_parse_url( $href );
		$scheme = null;
		if ( is_array( $parts ) && ! empty( $parts['scheme'] ) ) {
			$scheme = strtolower( $parts['scheme'] );
			// Allow only http and https schemes.
			return ! in_array( $scheme, [ 'http', 'https' ], true );
		}

		// If parse_url returned null/false, check if it contains a colon early on
		// (which would indicate a scheme we can't parse). Otherwise treat as relative (allow).
		return strpos( $href, ':' ) !== false;
	}

	/**
	 * Analyze the post.
	 *
	 * @param array<string, mixed> $meta_data Meta data.
	 * @return array<string, mixed>
	 */
	private function analyze( array $meta_data ) {
		// Get focus keyword for keyword checks.
		$focus_keyword = $meta_data['focus_keyword'] ?? '';

		return [
			'h2_subheadings'            => $this->check_subheadings(),
			'image_alt_text'            => $this->check_image_alt_text(),
			'media_present'             => $this->check_media_present(),
			'links_present'             => $this->check_links_present(),
			'url_length'                => Utils::check_url_length( $this->post_permalink ),
			'search_engine_title'       => Utils::analyze_title( $this->page_title ),
			'search_engine_description' => Utils::analyze_description( $this->page_description ),
			'canonical_url'             => $this->canonical_url(),
			'all_links'                 => $this->get_all_links(),
			'open_graph_tags'           => Utils::open_graph_tags(),
			// Keyword checks.
			'keyword_in_title'          => Utils::analyze_keyword_in_title( $this->page_title, $focus_keyword ),
			'keyword_in_description'    => Utils::analyze_keyword_in_description( $this->page_description, $focus_keyword ),
			'keyword_in_url'            => Utils::analyze_keyword_in_url( $this->post_permalink, $focus_keyword ),
			'keyword_in_content'        => Utils::analyze_keyword_in_content( $this->post_content, $focus_keyword ),
		];
	}

	/**
	 * Check for H2 subheadings.
	 *
	 * @return array<string, mixed>
	 */
	private function check_subheadings() {
		$headings = [ 'h2', 'h3', 'h4', 'h5', 'h6' ];
		$count    = 0;

		foreach ( $headings as $tag ) {
			$elements = $this->xpath ? $this->xpath->query( "//{$tag}" ) : null;
			$count   += $elements instanceof DOMNodeList ? $elements->length : 0;
		}

		if ( $count === 0 ) {
			return [
				'status'  => 'warning',
				'message' => __( 'The page does not contain any subheadings.', 'surerank' ),
				'type'    => 'page',
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Page contains at least one subheading.', 'surerank' ),
			'type'    => 'page',
		];
	}

	/**
	 * Check for image alt text.
	 *
	 * @return array<string, mixed>
	 */
	private function check_image_alt_text(): array {
		$images = $this->get_images();

		if ( ! $images instanceof DOMNodeList || $images->length === 0 ) {
			return [];
		}

		$analysis = $this->analyze_images( $images );
		return $this->build_image_alt_response( $analysis );
	}

	/**
	 * Get all images from the content.
	 *
	 * @return \DOMNodeList<\DOMNode>|null List of image elements.
	 */
	private function get_images(): ?\DOMNodeList {
		$result = $this->xpath ? $this->xpath->query( '//img' ) : null;
		return $result === false ? null : $result;
	}

	/**
	 * Analyze images for alt text attributes.
	 *
	 * @param \DOMNodeList<\DOMNode> $images List of image elements.
	 * @return array{total: int, missing_alt: int, missing_alt_images: array<string>} Analysis results.
	 */
	private function analyze_images( $images ): array {
		$analysis = [
			'total'              => $images->length,
			'missing_alt'        => 0,
			'missing_alt_images' => [],
		];

		foreach ( $images as $img ) {
			if ( $this->is_missing_alt_text( $img ) ) {
				$analysis['missing_alt']++;
				$src = $this->get_image_src( $img );
				if ( $src ) {
					$analysis['missing_alt_images'][] = $src;
				}
			}
		}

		return $analysis;
	}

	/**
	 * Check if an image element is missing alt text.
	 *
	 * @param \DOMNode $img Image element.
	 * @return bool True if missing alt text.
	 */
	private function is_missing_alt_text( \DOMNode $img ): bool {
		if ( ! $img instanceof \DOMElement ) {
			return false;
		}

		return ! $img->hasAttribute( 'alt' ) || empty( trim( $img->getAttribute( 'alt' ) ) );
	}

	/**
	 * Get the src attribute from an image element.
	 *
	 * @param \DOMNode $img Image element.
	 * @return string Image source URL or empty string.
	 */
	private function get_image_src( \DOMNode $img ): string {
		if ( ! $img instanceof \DOMElement ) {
			return '';
		}

		return $img->hasAttribute( 'src' ) ? $img->getAttribute( 'src' ) : '';
	}

	/**
	 * Build the response array for image alt text analysis.
	 *
	 * @param array{total: int, missing_alt: int, missing_alt_images: array<string>} $analysis Analysis results.
	 * @return array<string, mixed> Response array.
	 */
	private function build_image_alt_response( array $analysis ): array {
		$exists       = $analysis['total'] > 0;
		$is_optimized = $exists && $analysis['missing_alt'] === 0;
		$status       = $this->get_alt_text_status( $exists, $is_optimized );
		$message      = $this->get_alt_text_message( $exists, $is_optimized );

		return [
			'status'      => $status,
			'description' => $this->build_image_description( $exists, $analysis['total'], $analysis['missing_alt'], $analysis['missing_alt_images'] ),
			'message'     => $message,
			'show_images' => $exists && $analysis['missing_alt'] > 0,
			'type'        => 'page',
		];
	}

	/**
	 * Get the status for alt text analysis.
	 *
	 * @param bool $exists Whether images exist.
	 * @param bool $is_optimized Whether all images have alt text.
	 * @return string Status string.
	 */
	private function get_alt_text_status( bool $exists, bool $is_optimized ): string {
		if ( ! $exists ) {
			return 'warning';
		}

		if ( Image_Seo::get_instance()->status() && ! $is_optimized ) {
			return 'suggestion';
		}

		return $is_optimized ? 'success' : 'warning';
	}

	/**
	 * Get the message for alt text analysis.
	 *
	 * @param bool $exists Whether images exist.
	 * @param bool $is_optimized Whether all images have alt text.
	 * @return string Message string.
	 */
	private function get_alt_text_message( bool $exists, bool $is_optimized ): string {
		if ( $exists && $is_optimized ) {
			return __( 'All images on this page have alt text attributes.', 'surerank' );
		}

		$base_message = __( 'One or more images are missing alt text attributes.', 'surerank' );

		if ( Image_Seo::get_instance()->status() ) {
			return $base_message . ' ' . __( 'But don\'t worry, we will add them automatically for you.', 'surerank' );
		}

		return $base_message . ' ' . __( 'You can add them manually or turn on auto-set image title and alt in the settings.', 'surerank' );
	}

	/**
	 * Build image description.
	 *
	 * @param bool          $exists Whether images exist.
	 * @param int           $total Total number of images.
	 * @param int           $missing_alt Number of images missing alt text.
	 * @param array<string> $missing_alt_images Images missing alt text.
	 * @return array<int, array<string, array<int, string>>|string>
	 */
	private function build_image_description( bool $exists, int $total, int $missing_alt, array $missing_alt_images ): array {
		if ( ! $exists ) {
			return $this->get_no_images_description();
		}

		if ( $missing_alt === 0 ) {
			return $this->get_optimized_images_description();
		}

		return $this->get_missing_alt_description( $missing_alt_images );
	}

	/**
	 * Get description for pages with no images.
	 *
	 * @return array<int, string> Description array.
	 */
	private function get_no_images_description(): array {
		return [
			__( 'The page does not contain any images.', 'surerank' ),
			__( 'Add images to improve the post/page\'s visual appeal and SEO.', 'surerank' ),
		];
	}

	/**
	 * Get description for pages with optimized images.
	 *
	 * @return array<int, string> Description array.
	 */
	private function get_optimized_images_description(): array {
		return [
			__( 'Images on the post/page have alt text attributes', 'surerank' ),
		];
	}

	/**
	 * Get description for pages with missing alt text.
	 *
	 * @param array<string> $missing_alt_images Images missing alt text.
	 * @return array<int, array<string, array<int, string>>> Description array.
	 */
	private function get_missing_alt_description( array $missing_alt_images ): array {
		if ( empty( $missing_alt_images ) ) {
			return [];
		}

		$list = [];
		foreach ( array_unique( $missing_alt_images ) as $image ) {
			$list[] = esc_html( $image );
		}

		return [
			[ 'list' => $list ],
		];
	}

	/**
	 * Check for media present.
	 *
	 * @return array<string, mixed>
	 */
	private function check_media_present() {
		$images         = $this->xpath ? $this->xpath->query( '//img' ) : new DOMNodeList();
		$videos         = $this->xpath ? $this->xpath->query( '//video' ) : new DOMNodeList();
		$featured_image = get_post_thumbnail_id( $this->post_id );

		$image_length = $images->length ?? 0;
		$video_length = $videos->length ?? 0;
		$exists       = $image_length > 0 || $video_length > 0 || $featured_image;
		$message      = $exists ? __( 'This page includes images or videos to enhance content.', 'surerank' ) : __( 'No images or videos found on this page.', 'surerank' );

		return [
			'status'  => $exists ? 'success' : 'warning',
			'message' => $message,
			'type'    => 'page',
		];
	}

	/**
	 * Check for links present.
	 *
	 * @return array<string, mixed>
	 */
	private function check_links_present() {
		$links = $this->xpath ? $this->xpath->query( '//a[@href]' ) : new DOMNodeList();

		if ( ! $links || $links->length === 0 ) {
			return [
				'status'  => 'warning',
				'message' => __( 'No links found on the page.', 'surerank' ),
				'type'    => 'page',
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Links are present on the page.', 'surerank' ),
			'type'    => 'page',
		];
	}

	/**
	 * Get canonical URL.
	 *
	 * @return array<string, mixed>
	 */
	private function canonical_url() {
		if ( $this->canonical_url === null ) {
			return [
				'status'  => 'error',
				'message' => __( 'No canonical URL provided.', 'surerank' ),
				'type'    => 'page',
			];
		}

		$permalink = get_permalink( (int) $this->post_id );
		if ( ! $permalink ) {
			return [
				'status'  => 'error',
				'message' => __( 'No permalink provided.', 'surerank' ),
				'type'    => 'page',
			];
		}

		return Utils::analyze_canonical_url( $this->canonical_url, $permalink );
	}

	/**
	 * Update broken links status.
	 *
	 * @param array<string, mixed> $result Result.
	 * @return array<string, mixed>|false
	 */
	private function update_broken_links_status( $result ) {
		$links = $this->xpath ? $this->xpath->query( '//a[@href]' ) : new DOMNodeList();

		$empty_message = [
			'status'  => 'success',
			'message' => __( 'No broken links found on the page.', 'surerank' ),
		];

		if ( ! $links || $links->length === 0 ) {
			return $empty_message;
		}

		$urls = [];
		foreach ( $links as $link ) {
			if ( $link instanceof DOMElement ) {
				if ( ! in_array( $link->getAttribute( 'href' ), $urls ) ) {
					$urls[] = $link->getAttribute( 'href' );
				}
			}
		}

		$broken_links = Get::post_meta( (int) $this->post_id, SURERANK_SEO_CHECKS, true );
		$broken_links = $broken_links['broken_links'] ?? [];

		$existing_broken_links = Utils::existing_broken_links( $broken_links, $urls );

		if ( empty( $existing_broken_links ) ) {
			return $empty_message;
		}

		return false;
	}

	/**
	 * Get all links from the rendered post content.
	 *
	 * @return array<string>
	 */
	private function get_all_links() {
		if ( ! $this->xpath ) {
			return [];
		}

		$links        = [];
		$anchor_nodes = $this->xpath->query( '//a[@href]' );

		if ( ! $anchor_nodes instanceof DOMNodeList ) {
			return [];
		}

		foreach ( $anchor_nodes as $anchor ) {
			if ( $anchor instanceof DOMElement ) {
				$href = trim( $anchor->getAttribute( 'href' ) );

				// Skip empty hrefs and duplicates.
				if ( $href === '' || in_array( $href, $links, true ) ) {
					continue;
				}

				// Use the shared helper to decide if this URL should be skipped.
				if ( ! self::should_skip_url( $href ) ) {
					$links[] = $href;
				}
			}
		}

		return $links;
	}

	/**
	 * Get the original permalink for a post, even if it's set as homepage.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return string Original permalink or empty string.
	 */
	private function get_original_permalink( $post_id, $post ) {
		$homepage_id = (int) get_option( 'page_on_front' );

		if ( $homepage_id === $post_id ) {
			return $this->generate_original_page_url( $post );
		}

		$permalink = get_permalink( $post_id );
		return $permalink !== false ? $permalink : '';
	}

	/**
	 * Generate original page URL for a post that's set as homepage.
	 *
	 * @param WP_Post $post Post object.
	 * @return string Original page URL.
	 */
	private function generate_original_page_url( $post ) {
		if ( empty( $post->post_name ) ) {
			return '';
		}

		return trailingslashit( home_url() ) . $post->post_name . '/';
	}

}
