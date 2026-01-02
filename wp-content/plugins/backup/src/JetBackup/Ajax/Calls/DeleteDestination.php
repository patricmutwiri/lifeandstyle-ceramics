<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Destination\Destination;
use JetBackup\Destination\Vendors\Local\Local;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Snapshot\Snapshot;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class DeleteDestination extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return int
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	private static function _getTotalLocalDestinations():int{

		return sizeof(Destination::query()
			->select([JetBackup::ID_FIELD])
			->where([ Destination::TYPE, "=", Local::TYPE ])
			->getQuery()
			->fetch());

	}

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
	
		if(!$this->getId()) throw new AjaxException("No destination id provided");
		
		$destination = new Destination($this->getId());
		if(!$destination->getId()) throw new AjaxException("Invalid destination id provided");

		if($destination->isDefault()) throw new AjaxException("Cannot delete default destination");
		if ($destination->getType() == Local::TYPE && self::_getTotalLocalDestinations() <= 1) throw new AjaxException("We have to keep at least 1 Local destination");
		if(BackupJob::getDestinationsCount($destination->getId()) > 0) throw new AjaxException("Cannot delete a destination with assigned backup jobs");
		Snapshot::deleteByDestinationID($this->getId());
		$destination->delete();

		$this->setResponseMessage("Destination Deleted successfully! Reloading...");
	}
}