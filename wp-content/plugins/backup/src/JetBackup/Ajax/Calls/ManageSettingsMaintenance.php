<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\JBException;
use JetBackup\Factory;
use JetBackup\Settings\Maintenance;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ManageSettingsMaintenance extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getQueueItemsTTL(): int { return $this->getUserInput(Maintenance::MAINTENANCE_QUEUE_HOURS_TTL, 24, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getDownloadItemsTTL(): int { return $this->getUserInput(Maintenance::MAINTENANCE_DOWNLOAD_ITEMS_TTL, 72, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getDownloadLimit(): int { return $this->getUserInput(Maintenance::MAINTENANCE_DOWNLOAD_LIMIT, 5, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getAlertsTTL(): int { return $this->getUserInput(Maintenance::MAINTENANCE_QUEUE_ALERTS_TTL, 72, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getConfigExportRotate(): int { return $this->getUserInput(Maintenance::CONFIG_EXPORT_ROTATE, 2, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws FieldsValidationException
	 * @throws InvalidArgumentException
	 * @throws JBException
	 * @throws IOException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsMaintenance();

		if($this->isset(Maintenance::MAINTENANCE_QUEUE_HOURS_TTL)) $settings->setQueueItemsTTL($this->_getQueueItemsTTL());
		if($this->isset(Maintenance::MAINTENANCE_DOWNLOAD_ITEMS_TTL)) $settings->setDownloadItemsTTL($this->_getDownloadItemsTTL());
		if($this->isset(Maintenance::MAINTENANCE_DOWNLOAD_LIMIT)) $settings->setDownloadLimit($this->_getDownloadLimit());
		if($this->isset(Maintenance::MAINTENANCE_QUEUE_ALERTS_TTL)) $settings->setAlertsTTL($this->_getAlertsTTL());
		if($this->isset(Maintenance::CONFIG_EXPORT_ROTATE)) $settings->setConfigExportRotate($this->_getConfigExportRotate());

		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}