<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Ajax\ListRecord;
use JetBackup\Download\Download;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListDownloads extends ListRecord {

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws Exception
	 */
	public function execute(): void {

		$query = Download::query();

		if($filter = $this->getFilter()) {
			$fields = [Download::FILENAME];
			$query->search($fields, $filter);
		}
		$output = $this->isCLI() ? [] : [ 'alerts' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());

		$list = $query->getQuery()->fetch();

		foreach($list as $download_item) {
			$download = new Download($download_item[JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $download->getDisplayCLI();
			else $output['downloads'][] = $download->getDisplay();
		}

		$this->setResponseData($output);

	}
}