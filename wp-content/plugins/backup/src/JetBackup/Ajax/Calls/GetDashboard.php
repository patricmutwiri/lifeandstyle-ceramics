<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Alert\Alert;
use JetBackup\Config\System;
use JetBackup\Entities\Util;
use JetBackup\Queue\Queue;
use JetBackup\Snapshot\Snapshot;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class GetDashboard extends aAjax {

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \JetBackup\Exception\IOException
	 */
	public function execute(): void {

		$this->setResponseData([
			'statistics'     => [
				'alerts'             	=> Alert::getTotalAlerts(),
				'snapshots'          	=> Snapshot::getTotalSnapshots(),
				'snapshots_size'     	=> Util::bytesToHumanReadable(Snapshot::getTotalSnapshotsSize()),
				'system_alerts'      	=> System::getTotalAlerts(),
				'total_queue_pending'	=> Queue::getTotalPendingItems(),
				'total_queue_processing'=> Queue::getTotalActiveItems(),
				'total_queue_completed'	=> Queue::getTotalCompletedItems(),
				'total_queue_aborted'	=> Queue::getTotalAbortedItems(),
				'queue'              	=> Queue::getTotalActiveItems(),
			]
		]);
	}
}