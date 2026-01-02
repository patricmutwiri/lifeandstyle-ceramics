<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

use CloudLinux\Imunify\App\Views\Widget;
use CloudLinux\Imunify\App\Views\AdminPage;
use CloudLinux\Imunify\App\Api\AjaxHandler;

/**
 * Initial class
 */
class Plugin {
	/**
	 * Self instance
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Container.
	 *
	 * @var array
	 */
	private $container = array();

	/**
	 * Private constructor
	 */
	private function __construct() {
		// Empty constructor - no instantiation here.
	}

	/**
	 * Private clone
	 */
	private function __clone() {
	}

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get service
	 *
	 * @param string $key class.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if ( array_key_exists( $key, $this->container ) ) {
			return $this->container[ $key ];
		}

		return null;
	}

	/**
	 * Get environment
	 *
	 * @return string
	 */
	public function environment() {
		$environment = 'production';
		if (
			$this->isStagingModeDefined()
		) {
			$environment = 'development';
		}

		return $environment;
	}

	/**
	 * Check IMUNIFY_SECURITY_STAGING_MODE constant.
	 *
	 * @return bool
	 */
	public function isStagingModeDefined() {
		return ( defined( 'IMUNIFY_SECURITY_STAGING_MODE' ) && IMUNIFY_SECURITY_STAGING_MODE );
	}

	/**
	 * Setup container.
	 *
	 * @return void
	 */
	private function coreSetup() {
		$this->container[ Debug::class ]         = new Debug( $this->environment() );
		$this->container[ DataStore::class ]     = new DataStore();
		$this->container[ AccessManager::class ] = new AccessManager();
		$this->container[ AjaxHandler::class ]   = new AjaxHandler( $this->container[ DataStore::class ] );

		add_action( 'init', array( $this, 'load_translations' ) );
	}

	/**
	 * Additional setup for WP Admin env.
	 *
	 * @return void
	 */
	private function adminSetup() {
		// Create widget first.
		$this->container[ Widget::class ] = new Widget(
			$this->container[ AccessManager::class ],
			$this->container[ DataStore::class ]
		);

		// Instantiate AdminPage.
		$this->container[ AdminPage::class ] = new AdminPage(
			$this->container[ AccessManager::class ],
			$this->container[ DataStore::class ]
		);

		// Create asset loader with widget dependency.
		$this->container[ AssetLoader::class ] = new AssetLoader(
			$this->container[ Widget::class ]
		);

		$this->container[ PluginUpdateManager::class ] = new PluginUpdateManager();
	}

	/**
	 * Init plugin.
	 *
	 * @return void
	 */
	public function init() {
		$this->coreSetup();
		if ( is_admin() ) {
			$this->adminSetup();
		}
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public function load_translations() {
		load_plugin_textdomain( 'imunify-security', false, dirname( plugin_basename( IMUNIFY_SECURITY_FILE_PATH ) ) . '/languages' );
	}
}
