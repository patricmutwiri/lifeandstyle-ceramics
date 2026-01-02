<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

use CloudLinux\Imunify\App\Views\Widget;

/**
 * Handles loading of JavaScript and CSS assets for the widget.
 */
class AssetLoader {

	/**
	 * Asset handle for the widget styles and scripts.
	 */
	const WIDGET_HANDLE = 'imunify-security-widget';

	/**
	 * The widget instance.
	 *
	 * @var Widget
	 */
	private $widget;

	/**
	 * Constructor.
	 *
	 * @param Widget $widget The widget instance.
	 */
	public function __construct( Widget $widget ) {
		$this->widget = $widget;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Enqueues assets if the widget will be rendered.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueueAssets( $hook ) {

		// Widget assets.
		if ( 'index.php' === $hook && $this->widget->willBeRendered() ) {

			$plugin_url = plugin_dir_url( IMUNIFY_SECURITY_FILE_PATH );
			wp_enqueue_style(
				self::WIDGET_HANDLE,
				"{$plugin_url}assets/css/admin.min.css",
				array(),
				IMUNIFY_SECURITY_VERSION
			);

			wp_enqueue_script(
				self::WIDGET_HANDLE,
				"{$plugin_url}assets/js/admin.min.js",
				array( 'jquery' ),
				IMUNIFY_SECURITY_VERSION,
				true
			);

			wp_localize_script(
				self::WIDGET_HANDLE,
				'imunifyWidget',
				array(
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'snoozeNonce' => wp_create_nonce( Widget::WIDGET_SNOOZE_NONCE_NAME ),
				)
			);
		}
	}
}
