<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Settings\Integrations;
use JetBackup\Showcase\Showcase;
use JetBackup\UserInput\UserInput;
use JetBackup\Wordpress\Helper;

class manageShowcase extends aAjax
{

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getType():string { return $this->getUserInput(Showcase::TYPE, '', UserInput::STRING); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getStatus():bool { return $this->getUserInput(Showcase::STATUS, false, UserInput::BOOL); }

	/**
	 * @return void
	 * @throws AjaxException
	 */
	public function execute(): void {

		if (!Helper::isAdminUser()) throw new AjaxException("Not enough privileges to access this action");
		if (Helper::isMultisite() && !Helper::isNetworkAdminUser()) throw new AjaxException("You must be a network admin to access this action");
		if(!$this->_getType()) throw new AjaxException("Invalid showcase type");

		try {
			switch ($this->_getType()) {
				case Showcase::QUICK_START: Showcase::setQuickStart($this->_getStatus());
				// Other future showcases here
			}
		} catch (\Exception $e) {
			throw new AjaxException($e->getMessage());
		}

		$this->setResponseMessage("Saved successfully");

	}
}