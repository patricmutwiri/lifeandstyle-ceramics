<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemExtract extends aQueueItem {

	const SNAPSHOT_ID = 'snapshot_id';
	const EXTRACT_PATH = 'extract_path';
	
	public function setSnapshotId(int $_id):void { $this->set(self::SNAPSHOT_ID, $_id); }
	public function getSnapshotId():int { return (int) $this->get(self::SNAPSHOT_ID, 0); }

	public function setExtractPath(string $path):void { $this->set(self::EXTRACT_PATH, $path); }
	public function getExtractPath():string { return $this->get(self::EXTRACT_PATH); }

	public function getDisplay():array {
		return [
			self::SNAPSHOT_ID       => $this->getSnapshotId(),
			self::EXTRACT_PATH      => $this->getExtractPath(),
		];
	}
}
