<?php
/**
 * Utils Class.
 *
 * @package SureMails;
 * @since 1.9.0
 */

namespace SureMails\Inc\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureMails\Inc\Traits\Instance;

/**
 * Utils
 *
 * @since 1.9.0
 */
class Utils {

	use Instance;

	/**
	 * Get the SureMails admin URL.
	 *
	 * @param string $fragment Optional URL fragment (hash).
	 * @return string The complete admin URL.
	 */
	public static function get_admin_url( $fragment = '' ) {
		$base_url = admin_url( 'admin.php?page=' . SUREMAILS );

		if ( ! empty( $fragment ) ) {
			$base_url .= '#' . ltrim( $fragment, '#' );
		}

		return $base_url;
	}
}
