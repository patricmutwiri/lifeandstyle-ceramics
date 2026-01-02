<?php
/**
 * Plugin Name: SureRank SEO
 * Plugin URI: https://surerank.com
 * Description: Grow traffic of your website with SureRank — a lightweight SEO toolkit plugin for WordPress users who want better rankings without the complexity.
 * Author: SureRank
 * Author URI: https://surerank.com/
 * Version: 1.6.1
 * License: GPLv2 or later
 * Text Domain: surerank
 * Requires at least: 6.7
 * Requires PHP: 7.4
 *
 * @package surerank
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Require the necessary files.
 */

define( 'SURERANK_FILE', __FILE__ );
define( 'SURERANK_BASE', plugin_basename( SURERANK_FILE ) );
define( 'SURERANK_DIR', plugin_dir_path( SURERANK_FILE ) );
define( 'SURERANK_URL', plugins_url( '/', SURERANK_FILE ) );
define( 'SURERANK_VERSION', '1.6.1' );
define( 'SURERANK_PREFIX', 'surerank' );
require_once 'constants.php';
require_once 'loader.php';
