<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Queue\QueueItem;
use JetBackup\UserInput\UserInput;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class GetLog extends aAjax {
	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getQueueItemId():int { return $this->getUserInput('queue_item_id', 0, UserInput::UINT); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getQueueItemContent():bool { return $this->getUserInput('content', false, UserInput::BOOL); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws FileException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
		if (!$this->_getQueueItemId()) throw new AjaxException("No queue item ID provided");

		$queue_item = new QueueItem($this->_getQueueItemId());
		if (!$queue_item->getId()) throw new AjaxException('The provided queue item id not found');

		$logFile = $queue_item->getLogFile();
		if (!file_exists($logFile) || !is_readable($logFile)) throw new AjaxException("Log file not found or not readable");

		$stream = new FileStream($logFile);

		$output = [
			'path' => $logFile,
			'size' => Util::bytesToHumanReadable($stream->getSize()),
			'type' => $stream->getMimeType(),
		];

		if($this->_getQueueItemContent()) $output['content'] = $stream->read();
		$this->setResponseData($output);
		$stream->close();

	}

}