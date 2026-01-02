<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Queue\Queue;

class ClearCompletedQueueItems extends aAjax {

	/**
	 * @return void
	 * @throws AjaxException
	 */
	public function execute(): void {

		try {
			Queue::clearCompleted();
		} catch (\Exception $e) {
			throw new AjaxException($e->getMessage());
		}

		$this->setResponseMessage("QueueCleared successfully!");

	}
}