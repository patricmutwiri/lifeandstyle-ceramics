<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

/**
 * Handles injection of plugin icons into plugin update information.
 */
class PluginUpdateManager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'site_transient_update_plugins', array( $this, 'injectPluginInfo' ) );
	}

	/**
	 * Injects plugin information including icons into the update transient.
	 *
	 * @param \stdClass|null $transient The update transient.
	 * @return \stdClass|null
	 */
	public function injectPluginInfo( $transient ) {
		if ( ! $transient ) {
			return $transient;
		}

		$plugin_basename = plugin_basename( IMUNIFY_SECURITY_FILE_PATH );
		if ( isset( $transient->response[ $plugin_basename ] ) ) {
			$this->injectIcons( $transient->response[ $plugin_basename ] );
		} elseif ( isset( $transient->no_update[ $plugin_basename ] ) ) {
			// Populating the no_update information is required to support WordPress 5.5.
			$this->injectIcons( $transient->no_update[ $plugin_basename ] );
		}

		return $transient;
	}

	/**
	 * Injects icons into the plugin information.
	 *
	 * @param \stdClass $pluginInfo The plugin information.
	 */
	private function injectIcons( &$pluginInfo ) {
		$plugin_url = plugin_dir_url( IMUNIFY_SECURITY_FILE_PATH );

		// Add icon information.
		$pluginInfo->icons = array(
			'1x'  => $plugin_url . 'assets/images/icons/imunify-security.png',
			'2x'  => $plugin_url . 'assets/images/icons/imunify-security@2x.png',
			'svg' => $plugin_url . 'assets/images/icons/imunify-security.svg',
		);
	}
}
