<?php
/**
 * Enqueue
 *
 * @package surerank
 * @since 0.0.1
 */

namespace SureRank\Inc\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait Enqueue.
 *
 * @since 1.0.0
 */
trait Enqueue {

	/**
	 * Enqueue prefix
	 *
	 * @var string
	 */
	public $enqueue_prefix = SURERANK_PREFIX;

	/**
	 * Build path
	 *
	 * @var string
	 */
	public $build_path = SURERANK_DIR . 'build/';

	/**
	 * Build url
	 *
	 * @var string
	 */
	public $build_url = SURERANK_URL . 'build/';

	/**
	 * Language directory
	 *
	 * @var string
	 */
	public $language_dir = SURERANK_DIR . 'languages';

	/**
	 * Enqueue scripts
	 * This function should be called from the class constructor.
	 * It will add action to enqueue scripts.
	 * Further create a wp_enqueue_scripts() to enqueue scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts admin
	 * This function should be called from the class constructor
	 * It will add action to enqueue scripts in admin.
	 * Further create a admin_enqueue_scripts() to enqueue admin scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts_admin() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * This function does the actual enqueuing of scripts.
	 * It should be called from the wp_enqueue_scripts() or admin_enqueue_scripts() created in the class.
	 * It will register and enqueue the script.
	 * It will also localize the script if the localization array is not empty.
	 *
	 * @param string                                  $hook               Hook name user wish to choose for new js file.
	 * @param string                                  $path               Path to the script.
	 * @param array<string, mixed>|array<int, string> $dependency         Array of dependencies required for this script.
	 * @param array<string, mixed>|array<int, string> $localization_array Array of localization data, if required.
	 *                                   It should contain hook, object_name and data.
	 *                                   Example: [ 'hook' => 'example', 'object_name' => 'example', 'data' => $localized_data ].
	 * @param string                                  $version            Version of the script.
	 *                                                                    Default is the plugin version.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function script_operations( $hook, $path, $dependency, $localization_array = [], $version = SURERANK_VERSION ) {
		$this->register_script( $hook, $path, $dependency, $version );
		$this->enqueue_script( $hook );

		if ( ! empty( $localization_array ) && is_array( $localization_array ) && ! empty( $localization_array['hook'] ) && ! empty( $localization_array['object_name'] ) && ! empty( $localization_array['data'] ) ) {
			$this->localize_script( $localization_array['hook'], $localization_array['object_name'], $localization_array['data'] );
		}

		// Set the script translations if the JS file exists.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				$this->enqueue_prefix . '-' . $hook,
				$this->enqueue_prefix,
				$this->language_dir
			);
		}
	}

	/**
	 * This function does the actual enqueuing of styles.
	 * It should be called from the wp_enqueue_scripts() or admin_enqueue_scripts() created in the class.
	 * It will register and enqueue the style.
	 *
	 * @param string                                  $hook      Hook name user wish to choose for new css file.
	 * @param string                                  $path      Path to the style.
	 * @param array<string, mixed>|array<int, string> $dependency Array of dependencies required for this style.
	 * @param string                                  $version   Version of the style.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function style_operations( $hook, $path, $dependency, $version = SURERANK_VERSION ) {
		$this->register_style( $hook, $path, $dependency, $version );
		$this->enqueue_style( $hook );
	}

	/**
	 * This function should be called from the wp_enqueue_scripts() created in the class.
	 * It will enqueue the scripts and styles.
	 * It will also localize the script if the localization array is not empty.
	 *
	 * @param string                                  $handle           Handle need to be the same as the folder name of app in build folder.
	 * @param array<string, mixed>|array<int, string> $localization_data Array of localization data, if required.
	 *                                  It should contain hook, object_name and data.
	 *                                  Example: [ 'hook' => 'example', 'object_name' => 'example', 'data' => $localized_data ].
	 * @param array<string, mixed>|array<int, string> $script_dep       Array of dependencies required for this script.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function build_assets_operations( $handle, $localization_data = [], $script_dep = [] ) {
		if ( empty( $handle ) || ! is_string( $handle ) ) {
			return;
		}

		$script_asset_path = $this->build_path . $handle . '/index.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SURERANK_VERSION,
			];

		$script_dep = array_merge( $script_info['dependencies'], $script_dep );

		$this->enqueue_files_with_deps( $handle, $handle, $script_dep, $localization_data );
	}

	/**
	 * Enqueue vendor and common assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_vendor_and_common_assets() {
		// 1. First, enqueue runtime - all other chunks depend on it
		$this->enqueue_files_with_deps( 'runtime', 'runtime', [] );

		// 2. Get all vendor chunk directories
		$vendor_dirs    = glob( $this->build_path . 'vendor-*', GLOB_ONLYDIR );
		$vendor_handles = [];

		// 3. Enqueue all vendor chunks with runtime as dependency
		if ( ! empty( $vendor_dirs ) ) {
			foreach ( $vendor_dirs as $vendor_dir ) {
				$dir_name         = basename( $vendor_dir );
				$handle           = $this->enqueue_prefix . '-' . $dir_name;
				$vendor_handles[] = $handle;

				$this->enqueue_files_with_deps(
					$dir_name,
					$dir_name,
					[ $this->enqueue_prefix . '-runtime' ]
				);
			}
		}

		// 4. Enqueue common with runtime and all vendor chunks as dependencies
		$common_deps = array_merge(
			[ $this->enqueue_prefix . '-runtime' ],
			$vendor_handles
		);
		$this->enqueue_files_with_deps( 'common', 'common', $common_deps );
	}

	/**
	 * Register script.
	 * This function should be called from the register_enqueue_localize_script() created in the class.
	 * But it can also be called directly if user wants to register the script only.
	 *
	 * @param string                                  $hook      Hook name user wish to choose for new js file.
	 * @param string                                  $path      Path to the script.
	 * @param array<string, mixed>|array<int, string> $dependency Array of dependencies required for this script.
	 * @param string                                  $version   Version of the script.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_script( $hook, $path, $dependency, $version = SURERANK_VERSION ) {
		wp_register_script(
			$this->enqueue_prefix . '-' . $hook,
			$path,
			$dependency,
			$version,
			true
		);
	}

	/**
	 * Enqueue script.
	 * This function should be called from the register_enqueue_localize_script() created in the class.
	 * But it can also be called directly if user wants to enqueue the script which is already registered.
	 * This function should be called after the register_script() function.
	 * It will add prefix to the hook name and enqueue the script, should not be used for already existing scripts.
	 *
	 * @param string $hook Hook name user wish to choose for new js file.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_script( $hook ) {
		wp_enqueue_script( $this->enqueue_prefix . '-' . $hook );
	}

	/**
	 * Localize script.
	 * This function should be called from the register_enqueue_localize_script() created in the class.
	 * But it can also be called directly if user wants to localize the script which is already registered.
	 * This function should be called after the enqueue_script() function.
	 * It will add prefix to the hook name and localize the script, should not be used for already existing scripts.
	 *
	 * @param string                                  $hook        Hook name user wish to choose for new js file.
	 * @param string                                  $object_name Name of the object to be used in js file.
	 * @param array<string, mixed>|array<int, string> $data        Array of data to be localized.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function localize_script( $hook, $object_name, $data ) {
		wp_localize_script(
			$this->enqueue_prefix . '-' . $hook,
			$this->enqueue_prefix . '_' . $object_name,
			$data
		);
	}

	/**
	 * Register style.
	 * This function should be called from the register_enqueue_style() created in the class.
	 * But it can also be called directly if user wants to register the style only.
	 *
	 * @param string                                  $hook       Hook name user wish to choose for new css file.
	 * @param string                                  $path       Path to the style.
	 * @param array<string, mixed>|array<int, string> $dependency Array of dependencies required for this style.
	 * @param string                                  $version    Version of the style.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_style( $hook, $path, $dependency, $version = SURERANK_VERSION ) {
		wp_register_style(
			$this->enqueue_prefix . '-' . $hook,
			$path,
			$dependency,
			$version
		);
	}

	/**
	 * Enqueue style.
	 * This function should be called from the register_enqueue_style() created in the class.
	 * But it can also be called directly if user wants to enqueue the style which is already registered.
	 * This function should be called after the register_style() function.
	 * It will add prefix to the hook name and enqueue the style, should not be used for already existing styles.
	 *
	 * @param string $hook Hook name user wish to choose for new css file.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_style( $hook ) {
		wp_enqueue_style( $this->enqueue_prefix . '-' . $hook );
	}

	/**
	 * Creates nonces
	 * It will create nonces for provided events in form of array.
	 * It should be called when array of nonce needs to be created, to further localize the script.
	 * It should be called from the function wp_enqueue_scripts() or admin_enqueue_scripts() created in the class.
	 *
	 * @param array<string, mixed>|array<int, string> $events Array of events for which nonce needs to be created.
	 * @since 1.0.0
	 * @return array<string, mixed>|array<int, string>
	 */
	public function create_nonces( $events ) {
		$nonces = [];
		foreach ( $events as $event ) {
			$nonces[ $event . '_nonce' ] = wp_create_nonce( $this->enqueue_prefix . '_' . $event );
		}
		return $nonces;
	}

	/**
	 * Helper method to enqueue a JavaScript file with its dependencies.
	 *
	 * @param string                                  $name The file name (without extension).
	 * @param string                                  $dir The directory name.
	 * @param array<string, mixed>|array<int, string> $deps Additional dependencies.
	 * @param array<string, mixed>|array<int, string> $localization_data Localization data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function enqueue_files_with_deps( $name, $dir, $deps = [], $localization_data = [] ) {
		if ( empty( $name ) || empty( $dir ) ) {
			return;
		}

		$asset_path = $this->build_path . $dir . '/index.asset.php';

		// Special case for common and runtime which have same-named files.
		if ( $name === 'common' || $name === 'runtime' ) {
			$asset_path = $this->build_path . $dir . '/' . $name . '.asset.php';
		}

		if ( file_exists( $asset_path ) ) {
			$info     = include $asset_path;
			$all_deps = array_merge( $deps, $info['dependencies'] );
			$version  = $info['version'];

			// Determine the correct JS file path.
			if ( $name === 'common' || $name === 'runtime' ) {
				$js_file = $name . '.js';
			} else {
				$js_file = 'index.js';
			}

			$this->script_operations(
				$name,
				$this->build_url . $dir . '/' . $js_file,
				$all_deps,
				$localization_data,
				$version
			);

			// Check and enqueue corresponding CSS if it exists.
			$style_path = $this->build_path . $dir . '/style.css';
			if ( file_exists( $style_path ) ) {
				$this->style_operations(
					$name,
					$this->build_url . $dir . '/style.css',
					[],
					$version
				);
			}
		}
	}
}
