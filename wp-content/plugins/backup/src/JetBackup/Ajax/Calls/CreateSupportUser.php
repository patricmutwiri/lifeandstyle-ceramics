<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Wordpress\Helper;

class CreateSupportUser extends aAjax {

	/**
	 * @return void
	 * @throws AjaxException
	 */
	public function execute(): void {

		if (!Helper::isAdminUser()) throw new AjaxException("Not enough privileges to access this action");
		if(Helper::isMultisite() && !Helper::isNetworkAdminUser()) throw new AjaxException("You must be a network admin to access this action");

		try {
			$output = Helper::createSupportUser();
		} catch (\Exception $e) {
			throw new AjaxException($e->getMessage());
		}

		$this->setResponseData($output);
		$this->setResponseMessage("User created successfully!");
	}

}
