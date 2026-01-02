<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemDownload extends aQueueItem {

	const SNAPSHOT_ID = 'snapshot_id';
	const DOWNLOAD_ID = 'download_id';

	public function setSnapshotId(int $_id):void { $this->set(self::SNAPSHOT_ID, $_id); }
	public function getSnapshotId():int { return (int) $this->get(self::SNAPSHOT_ID, 0); }

	public function setDownloadId(int $_id):void { $this->set(self::DOWNLOAD_ID, $_id); }
	public function getDownloadId():int { return (int) $this->get(self::DOWNLOAD_ID, 0); }

	public function getDisplay():array {
		return [
			self::SNAPSHOT_ID       => $this->getSnapshotId(),
			self::DOWNLOAD_ID   => $this->getDownloadId(),
		];
	}
}
