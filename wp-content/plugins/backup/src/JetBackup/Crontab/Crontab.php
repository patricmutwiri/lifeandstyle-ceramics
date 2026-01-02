<?php

namespace JetBackup\Crontab;

use JetBackup\Alert\Alert;
use JetBackup\Factory;
use JetBackup\IO\Execute;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Crontab {

	const CRON_URL = "%s/%s/%s/backup/public/cron/cron.php?token=%s";
	const CRON_FILE = JetBackup::CRON_PATH . JetBackup::SEP . 'cron.php';
	const TEMP_FILE = '.temp_cron.php';
	
	private string $_temp_cron;
	private ?array $_crontab=null;
	private ?bool $_crontab_status=null;

	public function __construct () {
		$this->_temp_cron = Factory::getLocations()->getTempDir() . '/' . self::TEMP_FILE;
	}

	public function getCrontabStatus():bool {
		if($this->_crontab_status === null) {
			$this->_crontab_status = Factory::getSettingsAutomation()->isCronStatusEnabled();
		}
		return $this->_crontab_status;
	}

	public static function getPublicCron():string {
		return sprintf(self::CRON_URL, Wordpress::getSiteURL(), Wordpress::WP_CONTENT, Wordpress::WP_PLUGINS, Factory::getConfig()->getCronToken());
	}

	public static function getCommand():string {
		$php = Factory::getSettingsGeneral()->getPHPCLILocation();
		return sprintf("* * * * * %s %s > /dev/null 2>&1 &", $php, self::CRON_FILE);
	}

	public function getCrontab():array {
		if($this->_crontab === null && !Execute::run('crontab -l', $output)) $this->_crontab = $output;
		return $this->_crontab ?? [];
	}

	private function _addCron():array {
		$output = [];
		foreach($this->getCrontab() as $line) {
			$line = trim($line);
			if(
				!$line ||
				preg_match('/no\s*crontab\s*for/i', $line) ||
				strpos($line, '#') !== false
			) continue;
			$output[] = $line;
		}
		$output[] = $this->getCommand();
		return $output;
	}

	private function _removeCron():array {
		$output = [];
		foreach($this->getCrontab() as $line) if (strpos($line, self::CRON_FILE) === false) $output[] = $line;
		return $output;
	}

	public function buildCrontab ($remove = false):string {
		$output = $remove ? $this->_removeCron() : $this->_addCron();
		return implode(PHP_EOL, $output) . PHP_EOL;
	}


	private function _createTempCron($remove = false): bool {
		$crontab = $this->buildCrontab($remove);
		$old = umask(077);
		$created = file_put_contents($this->_temp_cron, $crontab);
		umask ($old);
		return $created;
	}


	public function crontabExists():bool {
		foreach($this->getCrontab() as $line) if (strpos($line, self::CRON_FILE) !== false) return true;
		return false;
	}

	public function removeCrontab():void {

		if(
			!$this->crontabExists() || // not found in crontab / cannot read - nothing to do
			!$this->_createTempCron(true) // cannot create tempcron, cannot continue
		) return;

		if(!Execute::run('crontab ' . $this->_temp_cron)) {
			$config = Factory::getSettingsAutomation();
			$config->setCronStatusEnabled(0);
			$config->save();
			Alert::add('Crontab Removed', 'System level crontab successfully removed', Alert::LEVEL_INFORMATION);
		}
	}

	public function addCrontab():void {

		if( $this->crontabExists() || !$this->_createTempCron() ) return;

		if (!Execute::run('crontab ' . $this->_temp_cron)) {
			$config = Factory::getSettingsAutomation();
			$config->setCronStatusEnabled(1);
			$config->save();
			Alert::add('Crontab Added', 'System level crontab successfully added', Alert::LEVEL_INFORMATION);
		}
	}


	private function isLinux():bool {
		return stristr(PHP_OS, 'Linux');
	}

	private function cleanup():void {
		if (file_exists($this->_temp_cron)) @unlink($this->_temp_cron);
	}

}