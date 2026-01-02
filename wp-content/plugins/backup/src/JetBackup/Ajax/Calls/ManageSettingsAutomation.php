<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Crontab\Crontab;
use JetBackup\Exception\AjaxException;
use JetBackup\Factory;
use JetBackup\Settings\Automation;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ManageSettingsAutomation extends aAjax {

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getHeartbeat(): bool { return $this->getUserInput(Automation::HEARTBEAT, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getWPCron(): bool { return $this->getUserInput(Automation::WP_CRON, false, UserInput::BOOL); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getHeartbeatTTL(): int { return $this->getUserInput(Automation::HEARTBEAT_TTL, 10000, UserInput::UINT); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getCrons(): bool { return $this->getUserInput(Automation::CRONS, true, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getCronStatus(): bool { return $this->getUserInput(Automation::CRON_STATUS, false, UserInput::BOOL); }


	/**
	 * @return void
	 * @throws AjaxException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsAutomation();

		$jb_cron_status_set = $this->isset(Automation::CRON_STATUS);
		$jb_cron_status_value = $jb_cron_status_set ? $settings->isCronStatusEnabled() : null;

		if($this->isset(Automation::WP_CRON)) $settings->setWPCron($this->_getWPCron());
		if($this->isset(Automation::HEARTBEAT)) $settings->setHeartbeatEnabled($this->_getHeartbeat());
		if($this->isset(Automation::HEARTBEAT_TTL)) $settings->setHeartbeatTTL($this->_getHeartbeatTTL());
		if($this->isset(Automation::CRONS)) $settings->setCronsEnabled($this->_getCrons());
		if($this->isset(Automation::CRON_STATUS)) $settings->setCronStatusEnabled($this->_getCronStatus());

		$settings->validateFields();
		$settings->save();

		if($jb_cron_status_set) {
			if ($jb_cron_status_value != $settings->isCronStatusEnabled()) {
				$crontab = new Crontab();
				if($settings->isCronStatusEnabled() == 0) $crontab->removeCrontab();
				if($settings->isCronStatusEnabled() == 1) $crontab->addCrontab();
			}
		}

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}