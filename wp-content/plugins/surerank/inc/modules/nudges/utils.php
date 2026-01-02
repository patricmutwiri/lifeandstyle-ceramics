<?php
/**
 * Utils class.
 *
 * @package SureRank\Inc\Modules\Nudges
 * @since 1.5.0
 */

namespace SureRank\Inc\Modules\Nudges;

use SureRank\Inc\Traits\Get_Instance;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The Utils class.
 *
 * @package SureRank\Inc\Modules\Nudges
 * @since 1.5.0
 */
class Utils {



	use Get_Instance;


	/**
	 * Check if Pro version is active.
	 * 
	 * @return bool True if pro is active, false otherwise.
	 */
	public function is_pro_active() {
		return defined( 'SURERANK_PRO_VERSION' );
	}

	/**
	 * Get Pro Nudges.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_nudges() {
		$nudges = get_option( SURERANK_NUDGES, [] );

		if ( empty( $nudges ) ) {
			return [];
		}

		foreach ( $nudges as $key => $nudge ) {

			if ( isset( $nudge['display'] ) && $nudge['display'] === false ) {
				continue;
			}

			if ( isset( $nudge['next_time_to_display'] ) && time() < $nudge['next_time_to_display'] ) {
				$nudges[ $key ]['display'] = false;
			}
		}
		return $nudges;
	}

}
