<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Api;

use CloudLinux\Imunify\App\DataStore;
use CloudLinux\Imunify\App\Exception\ApiException;

/**
 * AJAX Handler class.
 */
class AjaxHandler {

	/**
	 * AJAX action name.
	 */
	const AJAX_ACTION = 'imunify_security';

	/**
	 * DataStore instance.
	 *
	 * @var DataStore
	 */
	private $dataStore;

	/**
	 * Constructor.
	 *
	 * @param DataStore $dataStore Data store instance.
	 */
	public function __construct( DataStore $dataStore ) {
		$this->dataStore = $dataStore;
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handleAjaxRequest' ) );
	}

	/**
	 * Handle AJAX request.
	 *
	 * @return void
	 */
	public function handleAjaxRequest() {
		// Check for the JSON payload.
		$json = file_get_contents( 'php://input' );
		$data = json_decode( $json, true );

		// Initialize response array.
		$response = array(
			'data'     => array(),
			'messages' => array(),
			'result'   => 'error',
		);

		// Check if method and params are set.
		if ( isset( $data['method'] ) && is_array( $data['method'] ) && isset( $data['params'] ) && is_array( $data['params'] ) ) {
			try {
				// Process the method and params.
				$method = $data['method'];
				$params = $data['params'];

				// Get the data from the data store.
				$response = $this->dataStore->loadData( $method, $params );
			} catch ( ApiException $e ) {
				$response['messages'][] = $e->getMessage();
			}
		} else {
			$response['messages'][] = 'Invalid input data.';
		}

		// Send JSON response.
		wp_send_json( $response );
	}
}
