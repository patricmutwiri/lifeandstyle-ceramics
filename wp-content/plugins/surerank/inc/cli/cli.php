<?php
/**
 * SureRank CLI
 *
 * 1. Run `wp surerank generate_cache` Generates Cache.
 *
 * @package surerank
 * @since 1.2.0
 */

namespace SureRank\Inc\Cli;

use SureRank\Inc\Traits\Get_Instance;
use WP_CLI;

/**
 * WP-Cli commands to manage SureRank CLI features.
 *
 * @since 1.2.0
 */
class Cli {

	use Get_Instance;

	/**
	 * Export thh site.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Import demo site.
	 *     $ wp surerank generate_cache --force
	 *
	 * @since 1.2.0
	 * @param array<string,string> $args Positional arguments.
	 * @param array<string,string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function generate_cache( $args = [], $assoc_args = [] ) {
		$force = isset( $assoc_args['force'] ) ? 'yes' : '';
		if ( class_exists( 'WP_CLI' ) ) {
			// Start the cache building process.
			do_action( 'surerank_start_building_cache', $force );

			WP_CLI::line( __( 'SureRank Cache generated successfully', 'surerank' ) );
		}
	}

}

/**
 * Add Command
 */
if ( defined( 'WP_CLI' ) && class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'surerank', Cli::get_instance() );
}
