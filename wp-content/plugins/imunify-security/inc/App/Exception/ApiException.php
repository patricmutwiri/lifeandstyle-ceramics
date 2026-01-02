<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Exception;

/**
 * Exception thrown when an API request fails.
 */
class ApiException extends \Exception {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	private $errorCode;

	/**
	 * Constructor.
	 *
	 * @param string $message   The error message.
	 * @param string $errorCode The error code.
	 */
	public function __construct( $message, $errorCode ) {
		parent::__construct( $message );
		$this->errorCode = $errorCode;
	}

	/**
	 * Get the error code.
	 *
	 * @return string
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}
}
