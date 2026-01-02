<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Destination\Destination;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\JBException;
use JetBackup\JetBackup;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class DestinationSetExportConfig extends aAjax {

	/**
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws DestinationException
	 * @throws \JetBackup\Exception\IOException
	 * @throws JBException
	 */
	public function execute(): void {

		if(!$this->getId()) throw new AjaxException("No job id provided");

		$destination = new Destination($this->getId());
		if(!$destination->getId()) throw new AjaxException("Invalid destination id provided");

		$enabled = !$destination->isExportConfig();
		
		$backup = BackupJob::getDefaultConfigJob();

		$destinations = $backup->getDestinations();
		
		if($enabled) $destinations[] = $destination->getId();
		else {
			foreach($destinations as $i => $destination_id) {
				if($destination_id != $destination->getId()) continue;
				unset($destinations[$i]);
				break;
			}
		}

		$destinations = array_unique($destinations);
		
		$backup->setEnabled(!!$destinations);
		$backup->setDestinations($destinations);
		$backup->calculateNextRun();
		
		$destination->setExportConfig($enabled); // Opposite status
		$destination->save();
		$backup->save();

		$this->setResponseMessage("Destination export config " . ($destination->isExportConfig() ? 'enabled' : 'disabled') . " successfully! Reloading...");
	}
}