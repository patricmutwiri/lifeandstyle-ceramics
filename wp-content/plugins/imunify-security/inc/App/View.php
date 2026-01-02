<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

/**
 * View interface.
 */
abstract class View {

	/**
	 * Render a template.
	 *
	 * @param string $file Template file.
	 * @param array  $data Template data.
	 *
	 * @return void
	 */
	public function render( $file, $data = array() ) {
		$path = IMUNIFY_SECURITY_PATH . '/views/' . $file . '.php';
		if ( is_readable( $path ) ) {

			do_action( 'imunify_security_set_error_handler' );
			try {
				include $path;
			} catch ( \Exception $e ) {
				do_action(
					'imunify_security_set_error',
					E_ERROR,
					'Widget rendering failed: ' . $e->getMessage(),
					__FILE__,
					__LINE__,
					array(
						'fingerprint' => array( 'widget_rendering_failed', get_class( $e ) ),
					)
				);
			}

			do_action( 'imunify_security_restore_error_handler' );
		}
	}
}
