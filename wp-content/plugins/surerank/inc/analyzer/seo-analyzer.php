<?php
/**
 * SEO Analyzer class.
 *
 * Performs SEO checks on HTML content with consistent output for UI.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Analyzer;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use SureRank\Inc\API\Analyzer;
use SureRank\Inc\Functions\Get;
use WP_Error;

/**
 * Class SeoAnalyzer
 *
 * Analyzes HTML content for SEO metrics with standardized output.
 */
class SeoAnalyzer {

	/**
	 * Instance object.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * DOMDocument instance containing parsed HTML.
	 *
	 * @var DOMDocument|null
	 */
	private $dom = null;

	/**
	 * Array of error messages encountered during analysis.
	 *
	 * @var array<string>
	 */
	private $errors = [];

	/**
	 * Base URL being analyzed.
	 *
	 * @var string
	 */
	private $base_url = '';

	/**
	 * Scraper instance for fetching content.
	 *
	 * @var Scraper
	 */
	private $scraper;

	/**
	 * Parser instance for parsing HTML.
	 *
	 * @var Parser
	 */
	private $parser;

	/**
	 * Cached HTML content.
	 *
	 * @var string|WP_Error
	 */
	private $html_content = '';

	/**
	 * Constructor.
	 *
	 * @param string $url The URL to analyze.
	 * @return void
	 */
	public function __construct( string $url ) {
		$this->scraper = Scraper::get_instance();
		$this->parser  = Parser::get_instance();
		$this->initialize( $url );
	}

	/**
	 * Initiator.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to analyze.
	 * @return self initialized object of class.
	 */
	public static function get_instance( $url ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $url );
		}
		return self::$instance;
	}

	/**
	 * Get XPath instance for DOMDocument.
	 *
	 * @return DOMXPath|array<string, mixed>
	 */
	public function get_xpath() {
		if ( $this->dom === null ) {
			return [
				'exists'  => true,
				'status'  => 'error',
				'details' => $this->errors,
				'message' => __( 'Failed to load DOM for analysis.', 'surerank' ),
			];
		}

		return new DOMXPath( $this->dom );
	}

	/**
	 * Analyze page title.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_title( DOMXPath $xpath ) {

		$helptext = [
			__( "Your homepage title is one of the most important signals you can give to search engines — and to people. It’s the main clickable headline that appears in search results. Think of it like your site/'s headline on Google.", 'surerank' ),
			__( 'This is often the first thing someone sees before they visit your site. A well-crafted title tells them what your site is about and encourages them to click. It also helps search engines understand what your homepage represents.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'Your SEO title is a key part of how search engines decide what your homepage is about. It also sets expectations for visitors. If your title is missing or unclear, it can reduce your visibility or cause users to scroll past your site.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can include:', 'surerank' ) ),
			__( 'Mention your site or brand name and a clear description of what it offers. You don’t need to be fancy — just accurate and helpful.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Example:', 'surerank' ) ),
			__( '“Bright Bakes – Easy Baking Recipes for Everyone”', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Where to add this:', 'surerank' ) ),
			sprintf(
				/* translators: %s is the URL of the surerank settings page */
				__( "If your homepage is a static page, this <a href='%s' target='_blank'>link</a> will navigate you to the relevant page.", 'surerank' ),
				$this->get_homepage_settings_url()
			),
			sprintf(
				/* translators: %s is the URL of the surerank settings page */
				__( "If your homepage shows your latest posts, you can add the title directly by editing from the <a href='%s' target='_blank'>Home Page Settings</a>.", 'surerank' ),
				$this->get_homepage_settings_url()
			),
		];

		$titles = $xpath->query( '//title' );
		if ( ! $titles instanceof DOMNodeList ) {
			return $this->build_error_response(
				__( 'Search engine title is missing on the homepage.', 'surerank' ),
				$helptext,
				__( 'Search engine title is missing on the homepage.', 'surerank' ),
				'error'
			);
		}

		$exists  = $titles->length > 0;
		$content = '';
		$length  = 0;

		if ( $exists ) {
			$title_node = $titles->item( 0 );
			if ( $title_node instanceof DOMElement ) {
				$content = trim( $title_node->textContent );
				$length  = mb_strlen( $content );
			}
		}

		if ( ! $exists ) {
			$status = 'error';
		} elseif ( $length > Get::TITLE_LENGTH ) {
			$status = 'warning';
		} else {
			$status = 'success';
		}

		return [
			'exists'      => $exists,
			'status'      => $status,
			'description' => $helptext,
			'message'     => $this->get_title_message( $exists, $length, $status ),
		];
	}

	/**
	 * Analyze meta description.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_meta_description( DOMXPath $xpath ) {

		$description = [
			__( 'Your homepage description is like a one-sentence pitch that appears below your homepage title in search results. It’s a quick summary that helps people decide whether to visit your site.', 'surerank' ),
			__( 'It’s like a one-sentence pitch — short, clear, and focused on what your site offers. This is your chance to make a strong first impression.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'A good description tells Google and your potential visitors what your homepage is about. It can directly influence how many people click through to your site. When written well, it improves visibility and makes your search result more appealing.', 'surerank' ),

			sprintf( '<b> %s </b>', __( 'To keep things clear and visible, try to keep your homepage description between 150 to 160 characters. That’s usually enough to say something meaningful, without getting cut off in search results.</b>', 'surerank' ) ),

			sprintf( '<h6> %s </h6>', __( 'What to write:', 'surerank' ) ),
			__( 'Think about how you’d describe your site to someone who’s never seen it before. What is it about? Who is it for? What can they expect when they visit?', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Example:', 'surerank' ) ),
			__( '“Discover simple, healthy meals that you can cook at home — even if you’re just getting started.”', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Where to add this:', 'surerank' ) ),
			sprintf(
				/* translators: %s is the URL of the surerank settings page */
				__( "If your homepage is a static page, this <a href='%s' target='_blank'>link</a> will navigate you to the relevant page.", 'surerank' ),
				$this->get_homepage_settings_url()
			),
			sprintf(
				/* translators: %s is the URL of the surerank settings page */
				__( "If your homepage shows your latest posts, you can add the title directly by editing from the <a href='%s' target='_blank'>Home Page Settings</a>.", 'surerank' ),
				$this->get_homepage_settings_url()
			),
		];
		$meta_desc   = $xpath->query( '//meta[@name="description"]/@content' );
		if ( ! $meta_desc instanceof DOMNodeList ) {
			return $this->build_error_response(
				__( 'Search engine description is missing on the homepage.', 'surerank' ),
				$description,
				__( 'Search engine description is missing on the homepage.', 'surerank' ),
				'warning'
			);
		}

		$exists  = $meta_desc->length > 0;
		$content = '';
		$length  = 0;

		if ( $exists ) {
			$meta_node = $meta_desc->item( 0 );
			if ( $meta_node instanceof DOMAttr ) {
				$content = trim( $meta_node->value );
				$length  = mb_strlen( $content );
			}
		}

		if ( ! $exists ) {
			$status = 'warning';
		} elseif ( $length > Get::DESCRIPTION_LENGTH ) {
			$status = 'warning';
		} else {
			$status = 'success';
		}

		return [
			'exists'      => $exists,
			'status'      => $status,
			'description' => $description,
			'message'     => $this->get_meta_description_message( $exists, $length, $status ),
		];
	}

	/**
	 * Analyze headings (H1).
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_heading_h1( DOMXPath $xpath ) {
		$h1_analysis = $this->analyze_h1( $xpath );

		$exists = $h1_analysis['exists'];
		$status = 'success';
		$title  = __( 'Homepage contains one H1 heading', 'surerank' );

		$descriptions = [
			__( 'The H1 heading is usually the main title that appears visually on your homepage. It tells both visitors and search engines what the page is all about.', 'surerank' ),
			__( 'Think of it like the front cover of a book. It should quickly explain what someone has landed on, and help guide their next step.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'Search engines look at the H1 to understand the main topic of a page. Visitors use it to quickly decide whether they’re in the right place. If it’s missing or unclear, your site may feel incomplete or hard to understand.', 'surerank' ),

			__( 'Every page should have just one H1 — especially the homepage. Using multiple H1s can confuse search engines and reduce the clarity of your site’s structure.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What makes a good H1:', 'surerank' ) ),
			__( 'It should be clear, specific, and match what your site is about. Avoid vague or generic text. A short sentence or phrase that describes what you do works best.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Example:', 'surerank' ) ),
			__( '“Helping You Learn to Code from Scratch”', 'surerank' ),
			__( '“Creative Interior Design for Modern Spaces”', 'surerank' ),
		];

		if ( ! $h1_analysis['exists'] ) {
			$status = 'warning';
			$title  = __( 'No H1 heading found on the homepage.', 'surerank' );
		} elseif ( ! $h1_analysis['is_optimized'] ) {
			$status = 'warning';
			$title  = __( 'Multiple H1 headings found on the homepage.', 'surerank' );
		} else {
			$title = __( 'Homepage contains one H1 heading.', 'surerank' );
		}

		return [
			'exists'      => $exists,
			'status'      => $status,
			'description' => $descriptions,
			'message'     => $title,
			'not_fixable' => true,
		];
	}

	/**
	 * Analyze H2 headings.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_heading_h2( DOMXPath $xpath ) {

		$descriptions = [
			__( 'H2 headings act like section titles that break your homepage into clear parts. Think of them as signposts that guide visitors through your content. Instead of reading everything line by line, people scan — and H2s help them quickly spot what matters most.', 'surerank' ),
			__( 'Search engines also pay attention to these subheadings. They use them to understand the structure of your page and what each section is about.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'Subheadings make your content easier to scan for humans and easier to understand for search engines. A homepage without any H2s can feel flat and confusing. Just a few well-placed H2s can give your content more structure and impact.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What to write:', 'surerank' ) ),
			__( 'Each H2 should introduce a new section — like your services, your process, your values, or key offerings. Keep them short and helpful.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Examples:', 'surerank' ) ),
			__( 'What We Do', 'surerank' ),
			__( 'How It Works', 'surerank' ),
			__( 'Trusted by 100+ Clients', 'surerank' ),
		];

		$h2_analysis = $this->analyze_h2( $xpath );

		$exists = $h2_analysis['exists'];
		$status = 'success';
		$title  = __( 'Homepage contains at least one H2 heading', 'surerank' );

		if ( ! $h2_analysis['exists'] ) {
			$status = 'warning';
			$title  = __( 'Homepage does not contain at least one H2 heading', 'surerank' );
		}

		return [
			'exists'      => $exists,
			'status'      => $status,
			'description' => $descriptions,
			'message'     => $title,
			'not_fixable' => true,
		];
	}

	/**
	 * Analyze images for ALT attributes.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_images( DOMXPath $xpath ) {
		$images = $xpath->query( '//img' );
		if ( ! $images instanceof DOMNodeList ) {
			return [
				'exists'      => false,
				'status'      => 'warning',
				'description' => $this->build_image_description( false, 0, 0, [] ),
				'message'     => __( 'No images found on the homepage.', 'surerank' ),
			];
		}

		$total              = $images->length;
		$missing_alt        = 0;
		$missing_alt_images = [];

		foreach ( $images as $img ) {
			if ( $img instanceof DOMElement ) {
				$src = $img->hasAttribute( 'src' )
					? trim( $img->getAttribute( 'src' ) )
					: '';
				if ( ! $img->hasAttribute( 'alt' ) || empty( trim( $img->getAttribute( 'alt' ) ) ) ) {
					$missing_alt++;
					$missing_alt_images[] = $src;
				}
			}
		}

		$exists       = $total > 0;
		$is_optimized = $missing_alt === 0;

		if ( ! $exists ) {
			return [
				'exists'      => false,
				'status'      => 'warning',
				'description' => [ __( 'The homepage does not contain any images.', 'surerank' ) ],
				'message'     => __( 'No images found on the homepage.', 'surerank' ),
			];
		}

		$title = $is_optimized ? __( 'Images on the homepage have alt text attributes.', 'surerank' ) : __( 'Images on the homepage do not have alt text attributes.', 'surerank' );

		return [
			'exists'      => $exists,
			'status'      => $is_optimized ? 'success' : 'warning',
			'description' => $this->build_image_description( $exists, $total, $missing_alt, $missing_alt_images ),
			'message'     => $title,
		];
	}

	/**
	 * Analyze internal links if any.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_links( DOMXPath $xpath ) {
		$links    = $xpath->query( '//a[@href]' );
		$helptext = [
			__( 'Your homepage is usually the first place visitors land. From here, they should be able to quickly jump to the most important parts of your site — whether that’s a service page, your blog, or a contact form.', 'surerank' ),
			__( 'Internal links help people find what they need and help search engines understand the structure of your site. They also pass link value (or “SEO juice”) from your homepage to other key pages.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'Without internal links, your homepage becomes a dead end. Visitors might not explore further, and search engines won’t know which pages matter most.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What to link to:', 'surerank' ) ),
			__( 'Include links to things like: ', 'surerank' ),
			__( 'About or Team page', 'surerank' ),
			__( 'Services or product pages', 'surerank' ),
			__( 'Blog or recent posts', 'surerank' ),
			__( 'Contact or booking page', 'surerank' ),
			__( 'You can do this with buttons, menus, or even simple text links.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Where to add them:', 'surerank' ) ),
			__( 'Edit your homepage and look for natural spots to add links — like under short introductions, in feature sections, or at the end of content blocks.', 'surerank' ),
		];

		if ( ! $links instanceof DOMNodeList ) {
			return [
				'exists'      => false,
				'status'      => 'warning',
				'description' => [
					$helptext,
				],
				'message'     => __( 'Home Page does not contain internal links to other pages on the site.', 'surerank' ),
				'not_fixable' => true,
			];
		}

		$internal       = 0;
		$internal_links = [];

		foreach ( $links as $link ) {
			if ( $link instanceof DOMElement ) {
				$href = $link->getAttribute( 'href' );
				if ( empty( $href ) || strpos( $href, '#' ) === 0 ) {
					continue;
				}
				$host = wp_parse_url( $href, PHP_URL_HOST );
				if ( ! is_string( $host ) || $host === $this->base_url ) {
					$internal++;
					$internal_links[] = $href;
				}
			}
		}

		$exists = $internal > 0;
		$title  = $exists ? __( 'Home Page contains internal links to other pages on the site.', 'surerank' ) : __( 'Home Page does not contain internal links to other pages on the site.', 'surerank' );
		return [
			'exists'      => $exists,
			'status'      => $exists ? 'success' : 'warning',
			'description' => $helptext,
			'message'     => $title,
			'not_fixable' => true,
		];
	}

	/**
	 * Analyze canonical tag.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_canonical( DOMXPath $xpath ) {
		$helptext = [
			__( 'Sometimes, your homepage can be accessed through more than one URL — for example, with or without a trailing slash, or with tracking parameters like ?ref=newsletter. A canonical tag tells search engines which version is the “main” one.', 'surerank' ),
			__( 'This helps avoid confusion and ensures that all ranking signals point to the correct version of your homepage.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'If search engines see multiple versions of the same page, they may treat them as duplicates. That can split your SEO value and reduce your visibility in search results. A canonical tag keeps everything focused and clear.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( 'Add a canonical tag to your homepage that points to your preferred URL (e.g., https://example.com/). This tells search engines, “This is the official version to index.”', 'surerank' ),
		];

		$canonical = $xpath->query( '//link[@rel="canonical"]/@href' );
		if ( ! $canonical instanceof DOMNodeList ) {
			return $this->build_error_response(
				__( 'Canonical tag is not present on the homepage.', 'surerank' ),
				$helptext,
				__( 'Canonical tag is not present on the homepage.', 'surerank' ),
				'warning'
			);
		}

		$exists = $canonical->length > 0;
		$title  = $exists ? __( 'Canonical tag is present on the homepage.', 'surerank' ) : __( 'Canonical tag is not present on the homepage.', 'surerank' );
		return [
			'exists'      => $exists,
			'status'      => $exists ? 'success' : 'warning',
			'description' => $helptext,
			'message'     => $title,
		];
	}

	/**
	 * Analyze indexing meta tag.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function analyze_indexing( DOMXPath $xpath ) {
		$robots      = $xpath->query( '//meta[@name="robots"]/@content' );
		$description = [
			__( 'For your website to appear in search results, your homepage must be allowed to be indexed. Indexing simply means giving search engines permission to include your page in their listings.', 'surerank' ),
			__( 'Sometimes indexing gets turned off by accident — during site setup, through theme settings, or by certain plugins. When that happens, your homepage becomes invisible to search engines, even if the rest of your site is working fine.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'Your homepage is the front door to your website. It’s usually the first page people find when they search for your brand or business. If indexing is blocked, search engines can’t list it — which means potential visitors may never reach your site at all.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( 'You can do this by reviewing your page\'s SEO settings, your site\'s global visibility settings, or the Advanced settings of the homepage in the SureRank plugin.', 'surerank' ),

			__( 'Once indexing is enabled, search engines will be able to include your homepage in search results — helping more people discover what you offer.', 'surerank' ),
		];

		if ( ! $robots instanceof DOMNodeList ) {
			return [
				'exists'      => false,
				'status'      => 'warning',
				'description' => $description,
				'message'     => __( 'Homepage is not indexable by search engines.', 'surerank' ),
			];
		}

		$exists = $robots->length > 0;

		$content = '';

		if ( $exists ) {
			$robots_node = $robots->item( 0 );
			if ( $robots_node instanceof DOMAttr ) {
				$content = trim( $robots_node->value );
			}
		}

		$is_indexable = $exists ? strpos( $content, 'noindex' ) === false : true;
		$title        = $is_indexable ? __( 'Homepage is indexable by search engines.', 'surerank' ) : __( 'Homepage is not indexable by search engines.', 'surerank' );

		return [
			'exists'      => $exists,
			'status'      => $is_indexable ? 'success' : 'error',
			'description' => $description,
			'message'     => $title,
		];
	}

	/**
	 * Analyze homepage reachability.
	 *
	 * @return array<string, mixed>
	 */
	public function analyze_reachability() {
		$home_url          = home_url();
		$is_reachable      = $this->base_url === wp_parse_url( $home_url, PHP_URL_HOST ) && ! is_wp_error( $this->html_content );
		$working_label     = __( 'Home Page is loading correctly.', 'surerank' );
		$not_working_label = __( 'Home Page is not loading correctly.', 'surerank' );

		$description = [
			__( 'Your homepage is the front door to your website — it’s often the first page people see, and the one search engines visit first. If your homepage doesn’t load or returns an error, both visitors and search engines may have trouble accessing your content.', 'surerank' ),
			__( 'This can happen if your homepage was moved, deleted, or misconfigured — especially if your homepage is set to display your latest posts or a custom page.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why it matters:', 'surerank' ) ),
			__( "If your homepage isn’t reachable, it can affect how your entire site is crawled, indexed, and understood by search engines. It's also the first impression for your visitors — so it needs to be available and working.", 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( "Open your homepage in a browser and make sure it loads without errors. In WordPress, go to Settings → Reading and check that the right homepage is selected. If you're using a static page, ensure that page still exists and is published.", 'surerank' ),
		];

		if ( ! $is_reachable ) {
			$response     = $this->scraper->fetch( $home_url );
			$is_reachable = ! is_wp_error( $response );
		}

		$title = $is_reachable ? $working_label : $not_working_label;
		return [
			'exists'      => true,
			'status'      => $is_reachable ? 'success' : 'error',
			'description' => $description,
			'message'     => $title,
			'not_fixable' => true,
		];
	}

	/**
	 * Analyze secure HTTPS connection.
	 *
	 * @return array<string, mixed>
	 */
	public function analyze_secure_connection(): array {
		$header_url        = $this->fetch_header( 'x-final-url' );
		$effective_url     = $header_url !== '' ? $header_url : home_url();
		$working_label     = __( 'Site is served over a secure HTTPS connection.', 'surerank' );
		$not_working_label = __( 'Site is not served over a secure HTTPS connection.', 'surerank' );

		$description = [
			__( 'A secure connection means your website uses HTTPS — the little padlock icon next to your site’s address in a browser. This shows visitors (and search engines) that your site is safe and their information is protected.', 'surerank' ),
			__( 'Having a secure site isn’t just about safety — it’s also a trust signal. Search engines are more likely to rank secure sites higher, and modern browsers may even warn visitors when a site is not using HTTPS.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why it matters:', 'surerank' ) ),
			__( 'Security is important for everyone — even if your site doesn’t collect sensitive info. Using HTTPS protects your visitors and boosts your site’s credibility with search engines and users alike.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( 'Make sure your website uses HTTPS and shows a padlock icon in the address bar. You can enable HTTPS with an SSL certificate — many hosting providers offer this for free. Once set up, your connection will be secure, and your site will appear safer to both search engines and people.', 'surerank' ),
		];

		$is_https = strpos( $effective_url, 'https://' ) === 0;
		$title    = $is_https ? $working_label : $not_working_label;
		return [
			'exists'      => true,
			'status'      => $is_https ? 'success' : 'warning',
			'description' => $description,
			'message'     => $title,
			'not_fixable' => true,
		];
	}

	/**
	 * Analyze open graph tags.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function open_graph_tags( DOMXPath $xpath ): array {
		$og_tags           = $xpath->query( "//meta[starts-with(@property, 'og:')]" );
		$working_label     = __( 'Open Graph tags are present on your homepage.', 'surerank' );
		$not_working_label = __( 'Open Graph tags are not present on your homepage.', 'surerank' );
		$helptext          = [
			__( 'When someone shares your homepage on social media or messaging apps, platforms try to pull a title, description, and image. Without Open Graph tags, they may choose random content — or display nothing at all.', 'surerank' ),
			__( 'Open Graph tags let you decide exactly what people see when your homepage is shared.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'A good preview makes your site more clickable and professional. A missing or messy preview can make it harder for people to trust or engage with your content.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( 'Add Open Graph tags that define: ', 'surerank' ),
			__( 'A short title for your homepage', 'surerank' ),
			__( 'A brief description', 'surerank' ),
			__( 'A clear image that represents your site or brand', 'surerank' ),
			__( 'This ensures your homepage always looks polished when it’s shared.', 'surerank' ),

			sprintf(
				/* translators: %s is the URL of the home page */
				__( 'Configure your Home Page Social settings. Depending on your homepage setup, <a href="%s">this link</a> will take you to the relevant page.', 'surerank' ),
				$this->get_homepage_settings_url()
			),
		];

		if ( ! $og_tags instanceof DOMNodeList ) {
			return [
				'exists'      => false,
				'status'      => 'warning',
				'description' => $helptext,
				'message'     => $not_working_label,
			];
		}

		$details        = [];
		$required_tags  = [ 'og:title', 'og:description' ];
		$found_required = [
			'og:title'       => false,
			'og:description' => false,
		];

		foreach ( $og_tags as $tag ) {
			if ( $tag instanceof DOMElement ) {
				$property = $tag->getAttribute( 'property' );
				$content  = $tag->getAttribute( 'content' );

				$details[] = $property . ':' . $content;

				if ( in_array( $property, $required_tags, true ) ) {
					$found_required[ $property ] = true;
				}
			}
		}

		$missing_required = array_keys( array_filter( $found_required, static fn( $found) => ! $found ) );
		if ( ! empty( $missing_required ) ) {
			return [
				'exists'      => ! empty( $details ),
				'status'      => 'warning',
				'description' => $helptext,
				'message'     => $not_working_label,
			];
		}

		return [
			'exists'      => true,
			'status'      => 'success',
			'description' => $helptext,
			'message'     => $working_label,
		];
	}

	/**
	 * Analyze schema meta data.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	public function schema_meta_data( DOMXPath $xpath ) {
		$schema_meta_data  = $xpath->query( "//script[@type='application/ld+json']" );
		$working_label     = __( 'Structured data (schema) is present on the home page.', 'surerank' );
		$not_working_label = __( 'Structured data (schema) is not present on the home page.', 'surerank' );
		$helptext          = [
			__( 'Think of schema as a little “cheat sheet” your homepage shares with search engines. It quietly tells them what kind of content your homepage contains — like whether it\'s a website, a blog, or something else. This extra detail helps search engines understand your page better and sometimes display richer information in search results.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why it matters:', 'surerank' ) ),
			__( 'Search engines don’t just look at text — they look for signals. Schema gives them those signals. With it, your homepage can show up more clearly and attractively in search results, with added details like a breadcrumb path or your site name.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( 'Make sure your homepage includes basic structured data like WebPage schema. It helps search engines recognize your homepage as a core part of your site.', 'surerank' ),

			__( 'You don’t have to add anything manually — SureRank handles this in the background, as long as the feature is active and enabled.', 'surerank' ),
		];

		if ( ! $schema_meta_data instanceof DOMNodeList ) {
			return [
				'exists'      => false,
				'status'      => 'suggestion',
				'description' => $helptext,
				'message'     => $not_working_label,
			];
		}

		if ( ! $schema_meta_data->length ) {
			return [
				'exists'      => false,
				'status'      => 'suggestion',
				'description' => $helptext,
				'message'     => $not_working_label,
			];
		}

		return [
			'exists'      => true,
			'status'      => 'success',
			'description' => $helptext,
			'message'     => $working_label,
		];
	}

	/**
	 * Analyze WWW canonicalization.
	 *
	 * @return array<string, mixed>
	 */
	public function analyze_www_canonicalization(): array {
		$home_url = home_url();
		$parsed   = wp_parse_url( $home_url );

		$helptext = [
			__( 'Your website can usually be visited in two ways: https://example.com and https://www.example.com.', 'surerank' ),
			__( 'Both may work, but unless one redirects to the other, search engines treat them as separate websites — even though they look the same.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'If your site runs under both versions without redirection, it creates duplicate versions of every page. That splits your SEO value and may confuse search engines about which version to prioritize.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What you can do:', 'surerank' ) ),
			__( 'Pick your preferred domain (with or without www) and set up a redirect so all visitors and search engines are sent to that version. This makes your SEO stronger and avoids unnecessary duplication.', 'surerank' ),
		];

		$working_label     = __( 'Site correctly redirects between www and non-www versions.', 'surerank' );
		$not_working_label = __( 'Site does not correctly redirect between www and non-www versions.', 'surerank' );

		if ( ! is_array( $parsed ) || ! isset( $parsed['host'], $parsed['scheme'] ) ) {
			return [
				'exists'      => false,
				'status'      => 'error',
				'description' => $helptext,
				'message'     => $not_working_label,
				'not_fixable' => true,
			];
		}

		$host    = (string) $parsed['host'];
		$scheme  = (string) $parsed['scheme'];
		$timeout = 8;

		// Skip www canonicalization check for subdomain sites (e.g., subdomain.example.com).
		// This check only applies to root domains (example.com vs www.example.com).
		$host_parts   = explode( '.', $host );
		$is_subdomain = count( $host_parts ) > 2 && ! str_starts_with( $host, 'www.' );

		if ( $is_subdomain ) {
			return [
				'exists'      => true,
				'status'      => 'success',
				'description' => $helptext,
				'message'     => $working_label,
				'not_fixable' => true,
			];
		}

		$is_www    = str_starts_with( $host, 'www.' );
		$alternate = $is_www ? preg_replace( '/^www\./', '', $host ) : "www.{$host}";
		$test_url  = "{$scheme}://{$alternate}";

		// Pull the Location header (empty string on failure).
		$response = wp_safe_remote_head(
			$test_url,
			[
				'redirection' => 5,
				'timeout'     => $timeout,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'exists'      => false,
				'status'      => 'error',
				'description' => $helptext,
				'message'     => $not_working_label,
				'not_fixable' => true,
			];
		}

		$status_code  = (int) wp_remote_retrieve_response_code( $response );
		$raw_location = wp_remote_retrieve_header( $response, 'location' );

		$location = is_array( $raw_location )
			? ( $raw_location[0] ?? '' )
			: ( ! empty( $raw_location ) ? $raw_location : '' );

		// Normalize the final URL.
		if ( str_starts_with( $location, '/' ) ) {
			$final_url = "{$scheme}://{$host}{$location}";
		} elseif ( $location !== '' ) {
			$final_url = $location;
		} else {
			$final_url = $test_url;
		}

		$final_host        = (string) ( wp_parse_url( $final_url, PHP_URL_HOST ) ?? '' );
		$redirect_happened = $location !== '';
		$redirect_ok       = $redirect_happened ? $final_host === $host : true;
		$request_ok        = $status_code >= 200 && $status_code < 300;

		$all_good = $redirect_ok && $request_ok;

		$title = $all_good ? $working_label : $not_working_label;
		return [
			'exists'      => true,
			'status'      => $all_good ? 'success' : 'warning',
			'description' => $helptext,
			'message'     => $title,
			'not_fixable' => true,
		];
	}

	/**
	 * Initialize the analyzer by fetching and parsing the URL.
	 *
	 * @param string $url The URL to analyze.
	 * @return void
	 */
	private function initialize( string $url ) {

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$this->errors[] = __( 'Invalid URL.', 'surerank' );
			return;
		}

		$parsed_url         = wp_parse_url( $url, PHP_URL_HOST );
		$this->base_url     = is_string( $parsed_url ) ? $parsed_url : '';
		$this->html_content = $this->scraper->fetch( $url );

		if ( is_wp_error( $this->html_content ) ) {
			$this->errors[] = $this->html_content->get_error_message();
			return;
		}

		$parsed_dom = $this->parser->parse( $this->html_content );
		if ( is_wp_error( $parsed_dom ) ) {
			$this->errors[] = $parsed_dom->get_error_message();
			return;
		}

		$this->dom = $parsed_dom;
	}

	/**
	 * Get title analysis message.
	 *
	 * @param bool   $exists Whether title exists.
	 * @param int    $length Title length.
	 * @param string $status Status of the analysis.
	 * @return string
	 */
	private function get_title_message( bool $exists, int $length, string $status ) {
		if ( ! $exists ) {
			return __( 'Search engine title is missing on the homepage.', 'surerank' );
		}

		if ( $status === 'warning' ) {
			/* translators: %1$d is the maximum recommended length of the title. */
			$message = __( 'Search engine title of the home page exceeds %1$d characters.', 'surerank' );
			return sprintf( $message, Get::TITLE_LENGTH );
		}

		if ( $status === 'success' ) {
			return __( 'Search engine title of the home page is present and under 60 characters.', 'surerank' );
		}

		return __( 'Search engine title is present and under 60 characters.', 'surerank' );
	}

	/**
	 * Get meta description analysis message.
	 *
	 * @param bool   $exists Whether meta description exists.
	 * @param int    $length Meta description length.
	 * @param string $status Status of the analysis.
	 * @return string
	 */
	private function get_meta_description_message( bool $exists, int $length, string $status ) {
		if ( ! $exists ) {
			return __( 'Search engine description is missing on the homepage.', 'surerank' );
		}

		if ( $status === 'warning' ) {
			/* translators: %1$d is the maximum length of the meta description. */
			$message = __( 'Search engine description of the home page exceeds %1$d characters.', 'surerank' );
			return sprintf( $message, Get::DESCRIPTION_LENGTH );
		}

		if ( $status === 'success' ) {
			return __( 'Search engine description of the home page is present and under 160 characters.', 'surerank' );
		}

		return __( 'Search engine description is missing on the homepage.', 'surerank' );
	}

	/**
	 * Analyze H1 headings.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array{
	 *     exists: bool,
	 *     is_optimized: bool,
	 *     details: array{
	 *         count: int,
	 *         contents: array<string>
	 *     }
	 * }
	 */
	private function analyze_h1( DOMXPath $xpath ): array {
		$h1s = $xpath->query( '//h1' );
		if ( ! $h1s instanceof DOMNodeList ) {
			return [
				'exists'       => false,
				'is_optimized' => false,
				'details'      => [
					'count'    => 0,
					'contents' => [],
				],
			];
		}

		$exists   = $h1s->length > 0;
		$count    = $h1s->length;
		$contents = [];

		if ( $exists ) {
			foreach ( $h1s as $h1_node ) {
				if ( $h1_node instanceof DOMElement ) {
					$contents[] = trim( $h1_node->textContent );
				}
			}
		}

		return [
			'exists'       => $exists,
			'is_optimized' => $count === 1,
			'details'      => [
				'count'    => $count,
				'contents' => $contents,
			],
		];
	}

	/**
	 * Analyze H2 headings.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return array<string, mixed>
	 */
	private function analyze_h2( DOMXPath $xpath ) {
		$h2s = $xpath->query( '//h2' );
		if ( ! $h2s instanceof DOMNodeList ) {
			return [
				'exists'       => false,
				'is_optimized' => false,
				'details'      => [
					'count'    => 0,
					'contents' => [],
				],
			];
		}

		$exists   = $h2s->length > 0;
		$count    = $h2s->length;
		$contents = [];

		if ( $exists ) {
			foreach ( $h2s as $h2_node ) {
				if ( $h2_node instanceof DOMElement ) {
					$contents[] = trim( $h2_node->textContent );
				}
			}
		}

		return [
			'exists'       => $exists,
			'is_optimized' => $count >= 1,
			'details'      => [
				'count'    => $count,
				'contents' => $contents,
			],
		];
	}

	/**
	 * Build image analysis description.
	 *
	 * @param bool          $exists Whether images exist.
	 * @param int           $total Total number of images.
	 * @param int           $missing_alt Number of images missing ALT.
	 * @param array<string> $missing_alt_images Images missing ALT attributes.
	 *  @return array<int, array<string, array<int, string>|string>|string>
	 */
	private function build_image_description( bool $exists, int $total, int $missing_alt, array $missing_alt_images ) {
		$list = [];
		if ( $missing_alt !== 0 ) {
			foreach ( $missing_alt_images as $image ) {
				if ( ! in_array( $image, $list ) ) {
					$list[] = esc_html( $image );
				}
			}
		}

		return [
			__( 'Images add personality and style to your homepage — but search engines can’t “see” them unless you describe what they are. That’s where ALT text comes in.', 'surerank' ),
			__( 'ALT (alternative) text is a short description added to each image. It helps with accessibility for users using screen readers, and it helps search engines understand what the image is about. That can help your content appear in image search results too.', 'surerank' ),
			[ 'list' => $list ],
			[ 'img' => 'true' ],
			sprintf( '<h6> %s </h6>', __( 'Why this matters:', 'surerank' ) ),
			__( 'ALT text improves accessibility, makes your site more inclusive, and helps with SEO. It’s especially important on your homepage, where your most important content and images usually live.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'What to write:', 'surerank' ) ),
			__( 'Just describe what the image is about. If it’s decorative, you can skip it — but for key images, write a short, clear summary.', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Examples:', 'surerank' ) ),
			__( 'Woman doing yoga in a sunny room', 'surerank' ),
			__( 'Handmade ceramic mug on a wooden table', 'surerank' ),

			sprintf( '<h6> %s </h6>', __( 'Where to add it:', 'surerank' ) ),
			__( 'When uploading or editing images in WordPress, you’ll see an “ALT text” field in the media settings. Fill this out for each image on your homepage.', 'surerank' ),
		];
	}

	/**
	 * Build error response for invalid queries.
	 *
	 * @param string                      $title Error title.
	 * @param array<string|array<string>> $helptext Error description (HTML).
	 * @param string                      $message Error message.
	 * @param string                      $status Error status.
	 * @return array<string, mixed>
	 */
	private function build_error_response( string $title, array $helptext, string $message, string $status = 'error' ) {
		return [
			'exists'      => false,
			'status'      => $status,
			'description' => $helptext,
			'message'     => $message,
		];
	}

	/**
	 * Get the given header from the last fetched response.
	 *
	 * @param string $header The header name to retrieve.
	 * @return string        Header value, or '' if unavailable.
	 */
	private function fetch_header( string $header ): string {
		if ( is_wp_error( $this->html_content ) || empty( $this->scraper->get_body() ) ) {
			return '';
		}

		$value = $this->scraper->get_header( $header );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Get the URL of the home page social settings page.
	 *
	 * @return string
	 */
	private function get_homepage_settings_url() {
		$page_on_front = intval( Get::option( 'page_on_front' ) );
		if ( get_edit_post_link( $page_on_front ) ) {
			return get_edit_post_link( $page_on_front );
		}
		return Analyzer::get_instance()->get_surerank_settings_url( 'homepage', 'general' );
	}
}
