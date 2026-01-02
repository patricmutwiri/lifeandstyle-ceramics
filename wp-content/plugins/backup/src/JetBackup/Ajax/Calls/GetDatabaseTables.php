<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Snapshot\Snapshot;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

/**
 * Fetch available database tables inside a given snap item
 */
class GetDatabaseTables extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public function execute(): void {

		if(!$this->_getId()) throw new AjaxException("No snap id provided");

		$snap = new Snapshot($this->_getId());
		$items = $snap->getItems();
		$output = [];

		foreach($items as $item) {
			if(trim($item->getName()) == "") continue;
			if ($item->getBackupType() != BackupJob::TYPE_ACCOUNT) continue;
			if ($item->getBackupContains() == BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE || $item->getBackupContains() == BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL) {
				$output['db_prefix'] =  $item->getParams()['db_prefix'] ?? null;
				$output['db_tables'][] = $item->getName();
			}
		}

		$this->setResponseData($output);

	}
}