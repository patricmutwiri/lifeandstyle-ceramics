<?php

namespace JetBackup\Settings;

use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Maintenance extends Settings {

	const SECTION = 'maintenance';

	const MAINTENANCE_QUEUE_HOURS_TTL = 'MAINTENANCE_QUEUE_HOURS_TTL';
	const MAINTENANCE_QUEUE_ALERTS_TTL = 'MAINTENANCE_QUEUE_ALERTS_TTL';
	const MAINTENANCE_DOWNLOAD_ITEMS_TTL = 'MAINTENANCE_DOWNLOAD_ITEMS_TTL';
	const MAINTENANCE_DOWNLOAD_LIMIT = 'MAINTENANCE_DOWNLOAD_LIMIT';

	const CONFIG_EXPORT_ROTATE = 'CONFIG_EXPORT_ROTATE';

	private BackupJob $_backup;

	/**
	 * @throws DBException
	 * @throws IOException
	 * @throws JBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
		$this->_backup = BackupJob::getDefaultConfigJob();
	}

	/**
	 * @return int
	 */
	public function getQueueItemsTTL():int { return (int) $this->get(self::MAINTENANCE_QUEUE_HOURS_TTL, 24); }
	public function getDownloadItemsTTL():int { return (int) $this->get(self::MAINTENANCE_DOWNLOAD_ITEMS_TTL, 72); }
	public function getDownloadLimit():int { return (int) $this->get(self::MAINTENANCE_DOWNLOAD_LIMIT, 5); }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setQueueItemsTTL(int $value):void { $this->set(self::MAINTENANCE_QUEUE_HOURS_TTL, $value); }
	public function setDownloadItemsTTL(int $value):void { $this->set(self::MAINTENANCE_DOWNLOAD_ITEMS_TTL, $value); }
	public function setDownloadLimit(int $value):void { $this->set(self::MAINTENANCE_DOWNLOAD_LIMIT, $value); }

	/**
	 * @return int
	 */
	public function getAlertsTTL():int { return (int) $this->get(self::MAINTENANCE_QUEUE_ALERTS_TTL, 72); }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setAlertsTTL(int $value):void { $this->set(self::MAINTENANCE_QUEUE_ALERTS_TTL, $value); }

	/**
	 * @return int
	 */
	public function getConfigExportRotate():int {
		$schedules = $this->_backup->getSchedules();
		return $schedules[0]->getRetain(); 
	}

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setConfigExportRotate(int $value):void {
		$schedules = $this->_backup->getSchedules();
		$schedules[0]->setRetain($value);
		$this->_backup->setSchedules($schedules);
	}

	/**
	 * @return array
	 */
	public function getDisplay():array {

		return [
			self::MAINTENANCE_QUEUE_HOURS_TTL   => $this->getQueueItemsTTL(),
			self::MAINTENANCE_QUEUE_ALERTS_TTL  => $this->getAlertsTTL(),
			self::MAINTENANCE_DOWNLOAD_ITEMS_TTL  => $this->getDownloadItemsTTL(),
			self::MAINTENANCE_DOWNLOAD_LIMIT  => $this->getDownloadLimit(),
			self::CONFIG_EXPORT_ROTATE          => $this->getConfigExportRotate(),
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayCLI():array {

		return [
			'Queue Items TTL'           => $this->getQueueItemsTTL(),
			'Alerts TTL'                => $this->getAlertsTTL(),
			'Download Items TTL'        => $this->getDownloadItemsTTL(),
			'Downloads Limits'        => $this->getDownloadLimit(),
			'Config Export Rotate'      => $this->getConfigExportRotate(),
		];
	}

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if($this->getQueueItemsTTL() < 0) throw new FieldsValidationException("Done Queue items TTL must be a positive integer or 0 to disable");
		if($this->getDownloadItemsTTL() < 0) throw new FieldsValidationException("Download TTL must be a positive integer or 0 to disable");
		if($this->getDownloadLimit() < 0) throw new FieldsValidationException("Download limit must be a positive integer or 0 to disable");
		if($this->getAlertsTTL() < 0) throw new FieldsValidationException("System Alerts TTL must be a positive integer or 0 to disable");
		if($this->getConfigExportRotate() < 1) throw new FieldsValidationException("Export Config Rotate Days must be grater then 0");
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws JBException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function save(): void {
		parent::save();

		$this->_backup->calculateNextRun();
		$this->_backup->save();
	}
}