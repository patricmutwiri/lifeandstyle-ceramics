<?php
/**
 * Plugin Name: Imunify Security
 * Plugin URI: https://imunify360.com/imunify-security-wp-plugin/
 * Description: Imunify Security WordPress plugin is a comprehensive tool offering malware scanning, firewall protection, and intrusion detection for WordPress websites.
 * Version: 2.0.3
 * Requires at least: 5.0.0
 * Requires PHP: 5.6
 * Author: CloudLinux
 * Author URI: https://www.cloudlinux.com
 * Text Domain: imunify-security
 * Domain Path: /languages
 * Licence: CloudLinux Commercial License
 *
 * Copyright 2010-2025 CloudLinux
 */

use CloudLinux\Imunify\App\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'IMUNIFY_SECURITY_SLUG', 'imunify-security' );
define( 'IMUNIFY_SECURITY_PATH', dirname( __FILE__ ) );
define( 'IMUNIFY_SECURITY_VERSION', '2.0.2' );
define( 'IMUNIFY_SECURITY_FILE_PATH', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$namespace = 'CloudLinux\\Imunify\\';
		if ( preg_match( '#^' . preg_quote( $namespace, '/' ) . '#', $class ) ) {
			$path  = IMUNIFY_SECURITY_PATH . DIRECTORY_SEPARATOR . 'inc';
			$name  = str_replace( $namespace, '', $class );
			$file  = preg_replace( '#\\\\#', '/', $name ) . '.php';
			$path .= '/' . $file;

			// @phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( @file_exists( $path ) ) {
				include_once $path;
			}
		}
	}
);

try {
	if ( ! class_exists( Plugin::class ) ) {
		return;
	}

	Plugin::instance()->init();
} catch ( \Exception $e ) {
	do_action( 'imunify_security_set_error', E_WARNING, 'Init plugin failed: ' . $e->getMessage(), __FILE__, __LINE__ );
}
