<?php
/**
 * Seo Popup.
 *
 * @since 1.0.0
 * @package surerank
 */

namespace SureRank\Inc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SureRank\Inc\Traits\Enqueue;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Seo Popup frontend.
 *
 * @method void admin_enqueue_scripts()
 * @since 1.0.0
 */
class Seo_Popup {

	use Enqueue;
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __construct() {
		$this->enqueue_scripts();
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! is_admin() ) {
			return; // Exit if not admin, this will avoid js on frontend pages.
		}

		$this->build_assets_operations(
			'seo-popup',
			[
				'hook'        => 'seo-popup',
				'object_name' => 'seo_popup',
				'data'        => [
					'post_id'       => get_the_ID(),
					'site_icon_url' => get_site_icon_url( 16 ),
					'editor_type'   => 'frontend',
				],
			]
		);
	}
}
