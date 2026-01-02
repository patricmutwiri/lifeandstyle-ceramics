<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\MFA\GoogleAuthenticator;
use JetBackup\UserInput\UserInput;

class ValidateMFA extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getCode():int { return $this->getUserInput('code', 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 */
	public function execute(): void {

		if(!$this->getCode()) throw new AjaxException("Please enter a valid 6-digit MFA code");
		if (!GoogleAuthenticator::verifyCode($this->getCode())) throw new AjaxException("Invalid code");
		GoogleAuthenticator::setCookie();
		$this->setResponseMessage("QR Code validated successfully");
	}
}