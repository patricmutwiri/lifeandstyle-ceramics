<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\TaskException;
use JetBackup\JetBackup;
use JetBackup\Queue\QueueItem;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class AbortQueueItem extends aAjax {

	/**
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws IOException
	 * @throws TaskException
	 */
	public function execute(): void {

		if(!$this->getId()) throw new AjaxException("No queue id provided");
		$queue = new QueueItem($this->getId());
		if(!$queue->getId()) throw new AjaxException("Invalid queue id provided");
		$queue->abort();
		$this->setResponseMessage("Queue Item Aborted successfully!");

	}
}