<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\ListRecord;
use JetBackup\Destination\Destination;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListDestinations extends ListRecord {

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws DestinationException
	 */
	public function execute(): void {
		
		$query = Destination::query();

		if($filter = $this->getFilter()) {
			$fields = [Destination::TYPE, Destination::NAME, Destination::NOTES];
			$query->search($fields, $filter);
		}

		$output = $this->isCLI() ? [] : [ 'destinations' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());

		$list = $query->getQuery()->fetch();
		
		foreach($list as $destination_details) {
			$destination = new Destination($destination_details[JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $destination->getDisplayCLI();
			else $output['destinations'][] = $destination->getDisplay();
		}

		$this->setResponseData($output);
	}
}