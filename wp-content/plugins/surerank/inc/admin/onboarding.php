<?php
/**
 * Onboarding
 *
 * @package SureRank\Inc\Admin
 */

namespace SureRank\Inc\Admin;

use SureRank\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Onboarding class
 */
class Onboarding {

	use Get_Instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register menu
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'surerank',
			__( 'Onboarding', 'surerank' ),
			__( 'Onboarding', 'surerank' ),
			'manage_options',
			'surerank_onboarding',
			[ $this, 'render_onboarding' ],
			99
		);
	}

	/**
	 * Render onboarding
	 *
	 * @return void
	 */
	public function render_onboarding() {
		echo '<div class="surerank-root surerank-setting-page surerank-styles"><div id="surerank-root"></div></div>';
	}
}
