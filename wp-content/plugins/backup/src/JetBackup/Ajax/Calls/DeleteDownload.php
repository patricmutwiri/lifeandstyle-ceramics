<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Alert\Alert;
use JetBackup\Download\Download;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\NotificationException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class DeleteDownload extends aAjax {

	/**
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }


	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws NotificationException
	 */
	public function execute(): void {

		if(!$this->_getId()) throw new AjaxException("No download id provided");

		$download = new Download($this->_getId());
		if(!$download->getId()) throw new AjaxException("Invalid download id provided");

		$file =  Factory::getLocations()->getDownloadsDir() . JetBackup::SEP . $download->getLocation();
		if (file_exists($file)) @unlink($file);
		Alert::add('Download Deleted', "Download item ". $download->getFilename() . " removed by user", Alert::LEVEL_INFORMATION);
		$download->delete();

		$this->setResponseMessage('Deleted Successfully');

	}
}