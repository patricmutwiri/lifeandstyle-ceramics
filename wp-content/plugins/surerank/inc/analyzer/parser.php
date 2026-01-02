<?php
/**
 * Parser class.
 *
 * Parses HTML content into a DOM for SEO analysis.
 *
 * @package SureRank\Inc\Analyzer
 */

namespace SureRank\Inc\Analyzer;

use DOMDocument;
use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Parser
 *
 * Parses HTML content into a DOMDocument, optimized for webpage SEO analysis.
 */
class Parser {
	use Get_Instance;

	/**
	 * Parse HTML content.
	 *
	 * @param string $html The HTML content to parse.
	 * @return DOMDocument|WP_Error Parsed DOM or error on critical failure.
	 */
	public function parse( string $html ) {
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();

		if ( ! mb_check_encoding( $html, 'UTF-8' ) ) {
			$html = mb_convert_encoding( $html, 'UTF-8', 'auto' );
		}

		if ( ! $html ) {
			return new WP_Error(
				'parse_failed',
				__( 'Failed to parse HTML.', 'surerank' )
			);
		}

		$html = $this->pre_process_html( $html );

		if ( ! $html ) {
			return new WP_Error(
				'parse_failed',
				__( 'Failed to pre-process HTML.', 'surerank' )
			);
		}

		$html = $this->pre_process_html( $html );

		$success = $dom->loadHTML(
			'<?xml encoding="UTF-8">' . $html,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS | LIBXML_NONET
		);

		libxml_clear_errors();

		if ( ! $success || empty( $dom->childNodes ) ) {
			return new WP_Error(
				'parse_failed',
				__( 'Failed to parse HTML.', 'surerank' )
			);
		}

		return $dom;
	}

	/**
	 * Pre-process HTML to replace & with &amp; and add doctype.
	 *
	 * @param string $html The raw HTML content.
	 * @return string Pre-processed HTML.
	 */
	private function pre_process_html( $html ) {

		if ( ! $html ) {
			return '';
		}

		$html = preg_replace( '/&(?![A-Za-z0-9#]{1,7};)/', '&amp;', (string) $html );

		if ( ! preg_match( '/<!DOCTYPE html>/i', (string) $html ) ) {
			$html = '<!DOCTYPE html><html><body>' . $html . '</body></html>';
		}

		// Remove handlers and attributes that can cause issues for examples: onerror, onclick, etc.
		return (string) preg_replace( '/\s+on[a-z]+="[^"]*"/i', '', (string) $html );
	}
}
