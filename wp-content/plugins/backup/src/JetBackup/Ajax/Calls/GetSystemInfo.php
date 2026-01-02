<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Config\System;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\JetBackupLinux\JetBackupLinux;

class GetSystemInfo extends aAjax {

	/**
	 * @return void
	 * @throws IOException
	 */
	public function execute(): void {

		$this->setResponseData([
			'system_checks'         => [
				'data_dir_secured'      => System::isDataDirSecured(),
				'post_max_size'         => ini_get('post_max_size') !== false && Util::humanReadableToBytes(ini_get('post_max_size')) < System::PHP_MIN_POST_MAX_SIZE,
				'php_compatible'        => System::isPHPVersionCompatible(),
				'cron_run'              => System::getLastCron() > 600,
				'heartbeat'             => Factory::getSettingsAutomation()->isHeartbeatEnabled(),
				'cron'                  => Factory::getSettingsAutomation()->isCronsEnabled(),
				'jb_linux_status'       => JetBackupLinux::isInstalled() && !Factory::getSettingsGeneral()->isJBIntegrationEnabled(),
				'isWindows'             => System::isWindowsOS(),
				'pdo_mysql_buffered_query_available' => defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'),
			],
			'data'             => [
				'secured_dir'                   => System::getRecommendSecurePath(),
				'data_dir'                      => Factory::getLocations()->getDataDir(),
				'post_max_size'                 => ini_get('post_max_size'),
				'recommended_post_max_size'     => Util::bytesToHumanReadable(System::PHP_MIN_POST_MAX_SIZE),
			],
			'info'      => System::getSystemInfo(),
			'alerts'    => System::getTotalAlerts(),
		]);
	}
}