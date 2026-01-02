<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemExport extends aQueueItem {

	const TYPE = 'type';
	const SNAPSHOT_ID = 'snapshot_id';
	const DOWNLOAD_ID = 'download_id';

	public function setType(int $type):void { $this->set(self::TYPE, $type); }
	public function getType():int { return (int) $this->get(self::TYPE, 0); }

	public function setSnapshotId(int $_id):void { $this->set(self::SNAPSHOT_ID, $_id); }
	public function getSnapshotId():int { return (int) $this->get(self::SNAPSHOT_ID, 0); }

	public function setDownloadId(int $_id):void { $this->set(self::DOWNLOAD_ID, $_id); }
	public function getDownloadId():int { return (int) $this->get(self::DOWNLOAD_ID, 0); }

	public function getDisplay():array {
		return [
			self::TYPE          => $this->getType(),
			self::SNAPSHOT_ID   => $this->getSnapshotId(),
			self::DOWNLOAD_ID   => $this->getDownloadId(),
		];
	}
}
