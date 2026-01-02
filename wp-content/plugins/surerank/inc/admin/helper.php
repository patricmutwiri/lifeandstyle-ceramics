<?php
/**
 * Helper class for bulk operations.
 *
 * Contains common functionality shared between bulk edit and bulk actions.
 *
 * @package SureRank\Inc\Admin
 */

namespace SureRank\Inc\Admin;

use SureRank\Inc\Functions\Update;

defined( 'ABSPATH' ) || exit;

/**
 * Helper class for bulk operations.
 */
class Helper {

	/**
	 * Update robot settings for a post or term.
	 *
	 * @param int    $id Post or term ID.
	 * @param string $meta_key Meta key (post_no_index or post_no_follow).
	 * @param string $meta_value Meta value (yes or no).
	 * @param bool   $is_taxonomy Whether this is a taxonomy.
	 * @return bool Success status.
	 */
	public static function update_robot_meta( int $id, string $meta_key, string $meta_value, bool $is_taxonomy = false ): bool {
		$full_meta_key = 'surerank_settings_' . $meta_key;

		if ( $is_taxonomy ) {
			Update::term_meta( $id, $full_meta_key, $meta_value );
		} else {
			Update::post_meta( $id, $full_meta_key, $meta_value );
		}

		return true;
	}

	/**
	 * Check if current screen is a taxonomy screen.
	 *
	 * @return bool Whether current screen is taxonomy.
	 */
	public static function is_taxonomy_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen && 'edit-tags' === $screen->base;
	}

	/**
	 * Display success notice.
	 *
	 * @param string $message Notice message.
	 * @return void
	 */
	public static function display_notice( string $message ): void {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Get Business details.
	 *
	 * @since 1.5.0
	 * @param string $key options name.
	 * @return array<string, mixed>|array<int, string>|string|array<string>|array<string,string>
	 */
	public static function get_saved_business_details( string $key ) {
		$details = get_option( 'zipwp_user_business_details', [] );

		if ( ! is_array( $details ) ) {
			$details = [];
		}

		if ( ! empty( $key ) ) {
			return $details[ $key ] ?? '';
		}

		return $details;
	}
}
