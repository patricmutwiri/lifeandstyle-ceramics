<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemRetentionCleanup extends aQueueItem {

	public function getDisplay():array {
		return [
		];
	}
}
