<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemReindex extends aQueueItem {

	const DESTINATION_ID = 'destination_id';
	const CROSS_DOMAIN = 'cross_domain';

	public function setDestinationId(int $_id):void { $this->set(self::DESTINATION_ID, $_id); }
	public function getDestinationId():int { return (int) $this->get(self::DESTINATION_ID, 0); }

	public function setCrossDomain(bool $mixed):void { $this->set(self::CROSS_DOMAIN, $mixed); }
	public function isCrossDomain():bool { return !!$this->get(self::CROSS_DOMAIN, false); }

	public function getDisplay():array {
		return [
			self::DESTINATION_ID    => $this->getDestinationId(),
			self::CROSS_DOMAIN       => $this->isCrossDomain(),
		];
	}
}
