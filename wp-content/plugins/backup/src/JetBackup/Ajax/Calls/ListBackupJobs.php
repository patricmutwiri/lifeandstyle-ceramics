<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\ListRecord;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListBackupJobs extends ListRecord {

	/**
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
		
		$query = BackupJob::query()->where([BackupJob::HIDDEN, '=', false]);

		if($filter = $this->getFilter()) {
			$fields = [BackupJob::NAME];
			$query->search($fields, $filter);
		}

		$output = $this->isCLI() ? [] : [ 'jobs' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());

		$list = $query->getQuery()->fetch();
		
		foreach($list as $job_details) {
			$job = new BackupJob( $job_details[ JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $job->getDisplayCLI();
			else $output['jobs'][] = $job->getDisplay();
		}

		$this->setResponseData($output);
	}
}