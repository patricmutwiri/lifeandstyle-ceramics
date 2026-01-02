<?php

namespace JetBackup\Exception;

use JetBackup\License\License;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class LicenseException extends JBException {
	private string $_status;

	/**
	 * @param string $message
	 * @param string $status
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct(string $message, string $status=License::STATUS_INVALID, int $code=0, ?\Throwable $previous=null) {
		$this->_status = $status;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return string
	 */
	public function getStatus():string { return $this->_status; }
}