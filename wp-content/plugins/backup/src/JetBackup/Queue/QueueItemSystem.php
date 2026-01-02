<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemSystem extends aQueueItem {

	const TYPE = 'type';
	
	public function setType(int $type):void { $this->set(self::TYPE, $type); }
	public function getType():int { return $this->get(self::TYPE, 0); }

	public function getDisplay():array {
		return [
			self::TYPE      => $this->getType(),
		];
	}
}
