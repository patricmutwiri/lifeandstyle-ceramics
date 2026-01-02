<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\ListRecord;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Snapshot\Snapshot;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListBackups extends ListRecord {

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public function execute(): void {

		$query = Snapshot::query()->where([Snapshot::BACKUP_TYPE, '!=', BackupJob::TYPE_CONFIG]);
		$output = $this->isCLI() ? [] : [ 'backups' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());

		$list = $query->getQuery()->fetch();

		foreach($list as $snapshot_details) {
			$snapshot = new Snapshot($snapshot_details[JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $snapshot->getDisplayCLI();
			else $output['backups'][] = $snapshot->getDisplay();
		}

		$this->setResponseData($output);

	}
}