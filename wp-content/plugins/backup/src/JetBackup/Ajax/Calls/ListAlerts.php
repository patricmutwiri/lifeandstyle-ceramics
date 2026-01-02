<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Ajax\ListRecord;
use JetBackup\Alert\Alert;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListAlerts extends ListRecord {

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public function execute(): void {
		
		$query = Alert::query();

		if($filter = $this->getFilter()) {
			$fields = [Alert::TITLE];
			$query->search($fields, $filter);
		}

		$output = $this->isCLI() ? [] : [ 'alerts' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());

		$list = $query->getQuery()->fetch();

		foreach($list as $alert_details) {
			$alert = new Alert($alert_details[JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $alert->getDisplayCLI();
			else $output['alerts'][] = $alert->getDisplay();
		}
		
		$this->setResponseData($output);
	}
}