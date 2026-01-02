<?php

namespace JetBackup\Settings;

use JetBackup\Crontab\Crontab;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\IOException;
use JetBackup\Wordpress\Wordpress;
use ReflectionException;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Automation extends Settings {

	const SECTION = 'automation';

	const CRON_PUBLIC = 'AUTOMATION_CRON_PUBLIC';
	const CRON_COMMAND = 'AUTOMATION_CRON_COMMAND';
	const CRON_CONTENT = 'AUTOMATION_CRON_CONTENT';
	const WP_CRON = 'WP_CRON';

	const HEARTBEAT = 'HEARTBEAT';
	const HEARTBEAT_TTL = 'HEARTBEAT_TTL';
	const HEARTBEAT_TTL_VALUES = 'HEARTBEAT_TTL_VALUES';
	const CRONS = 'CRONS';
	const CRON_STATUS = 'CRON_STATUS';

	/**
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	public function isWPCronEnabled():bool { return (bool) $this->get(self::WP_CRON, true); }
	public function setWPCron(bool $value):void { $this->set(self::WP_CRON, $value); }

	/**
	 * @return bool
	 */
	public function isHeartbeatEnabled():bool { return (bool) $this->get(self::HEARTBEAT, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setHeartbeatEnabled(bool $value):void { $this->set(self::HEARTBEAT, $value); }

	/**
	 * @return int
	 */
	public function getHeartbeatTTL():int { return (int) $this->get(self::HEARTBEAT_TTL, 10000); }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setHeartbeatTTL(int $value):void { $this->set(self::HEARTBEAT_TTL, $value); }

	/**
	 * @return bool
	 */
	public function isCronsEnabled():bool { return (bool) $this->get(self::CRONS, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setCronsEnabled(bool $value):void { $this->set(self::CRONS, $value); }

	/**
	 * @return bool
	 */
	public function isCronStatusEnabled():bool { return (bool) $this->get(self::CRON_STATUS, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setCronStatusEnabled(bool $value):void { $this->set(self::CRON_STATUS, $value); }

	/**
	 * @return array
	 */
	public function getDisplay():array {

		$cron = new Crontab();

		return [
			self::CRON_PUBLIC       => Crontab::getPublicCron(),
			self::CRON_COMMAND      => Crontab::getCommand(),
			self::CRON_CONTENT      => implode(PHP_EOL, $cron->getCrontab()),
			self::WP_CRON                     => $this->isWPCronEnabled() ? 1 : 0,
			self::HEARTBEAT                     => $this->isHeartbeatEnabled() ? 1 : 0,
			self::CRONS                         => $this->isCronsEnabled() ? 1 : 0,
			self::CRON_STATUS                   => $this->isCronStatusEnabled() ? 1 : 0,
			self::HEARTBEAT_TTL                   => $this->getHeartbeatTTL(),
			self::HEARTBEAT_TTL_VALUES => [
				5 => 5000,
				10 => 10000,
				20 => 20000,
				30 => 30000,
				40 => 40000,
				50 => 50000,
				60 => 60000,
			]
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayCLI():array {

		return [
			'Wordpress Cron' => $this->isWPCronEnabled() ? "Yes" : "No",
			'Heartbeat'         => $this->isHeartbeatEnabled() ? "Yes" : "No",
			'Cron Jobs'         => $this->isCronsEnabled() ? "Yes" : "No",
			'Cron Job Status'   => $this->isCronStatusEnabled() ? "Yes" : "No",
		];
	}

	/**
	 * @return void
	 */
	public function validateFields():void {}

}