<?php

namespace JetBackup\Ajax\Calls;

if ( ! defined( '__JETBACKUP__' ) ) {
	die( 'Direct access is not allowed' );
}

use JetBackup\Ajax\aAjax;
use JetBackup\Destination\Destination;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\JetBackup;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class GetDestination extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @throws DBException
	 * @throws AjaxException
	 * @throws DestinationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		if(!$this->getId()) throw new AjaxException("No destination id provided");

		$destination = new Destination($this->getId());

		if(!$destination->getId()) throw new AjaxException("Invalid destination id provided");

		$this->setResponseData($this->isCLI() ? $destination->getDisplayCLI() : $destination->getDisplay());
	}
}