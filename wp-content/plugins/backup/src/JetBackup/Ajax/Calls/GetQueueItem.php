<?php

namespace JetBackup\Ajax\Calls;

defined('__JETBACKUP__') or die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Queue\QueueItem;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class GetQueueItem extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		if(!$this->getId()) throw new AjaxException("No queue item id provided");

		$item = new QueueItem($this->getId());

		if(!$item->getId()) throw new AjaxException("Invalid queue item id provided");

		$this->setResponseData($this->isCLI() ? $item->getDisplayCLI() : $item->getDisplay());
	}
}