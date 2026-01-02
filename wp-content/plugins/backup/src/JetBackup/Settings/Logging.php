<?php

namespace JetBackup\Settings;

use JetBackup\Exception\FieldsValidationException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Logging extends Settings {

	const SECTION = 'logging';

	const DEBUG = 'DEBUG';
	const LOG_ROTATE = 'LOG_ROTATE';

	/**
	 * @throws \JetBackup\Exception\IOException
	 * @throws \ReflectionException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return bool
	 */
	public function isDebugEnabled():bool { return (bool) $this->get(self::DEBUG, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setDebugEnabled(bool $value):void { $this->set(self::DEBUG, $value); }

	/**
	 * @return int
	 */
	public function getLogRotate():int { return (int) $this->get(self::LOG_ROTATE, 7); }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setLogRotate(int $value):void { $this->set(self::LOG_ROTATE, $value); }

	/**
	 * @return array
	 */
	public function getDisplay():array {

		return [
			self::DEBUG                         => $this->isDebugEnabled() ? 1 : 0,
			self::LOG_ROTATE                    => $this->getLogRotate(),
		];
	}

	/**
	 * @return string[]
	 */
	public function getDisplayCLI():array {

		return [
			'Debug Enabled'     => $this->isDebugEnabled() ? "Yes" : "No",
			'Log Rotate'        => $this->getLogRotate(),
		];
	}

	/**
	 * @return void
	 */
	public function validateFields():void {
		if($this->getLogRotate() < 0) throw new FieldsValidationException("Log rotation must be a positive integer");
	}
}