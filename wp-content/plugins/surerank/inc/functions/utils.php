<?php
/**
 * Utils.
 *
 * Utils module class for handling utils functions.
 *
 * @package SureRank\Inc\Functions;
 * @since 1.5.0
 */

namespace SureRank\Inc\Functions;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Utils class
 *
 * Main module class for utils functions.
 */
class Utils {

	use Get_Instance;

	/**
	 * Convert absolute URL to relative path.
	 *
	 * Removes the home URL and trailing slashes from the given URL.
	 *
	 * @param string $url Full URL.
	 * @return string Relative path without leading/trailing slashes.
	 * @since 1.5.0
	 */
	public static function get_relative_url( $url ) {
		$home_url = trailingslashit( home_url() );
		$relative = str_replace( $home_url, '', trailingslashit( $url ) );
		return rtrim( $relative, '/' );
	}
}
