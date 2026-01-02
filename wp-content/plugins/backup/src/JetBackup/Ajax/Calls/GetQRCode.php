<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Ajax\aAjax;
use JetBackup\MFA\GoogleAuthenticator;

class GetQRCode extends aAjax {

	/**
	 * @return void
	 * @throws Exception
	 */
	public function execute(): void {

		$this->setResponseData(GoogleAuthenticator::getQRcode());
		$this->setResponseMessage('Success');
	}

}