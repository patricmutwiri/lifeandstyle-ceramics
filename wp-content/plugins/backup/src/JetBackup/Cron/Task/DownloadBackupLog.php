<?php

namespace JetBackup\Cron\Task;

use JetBackup\Archive\Archive;
use JetBackup\Archive\Gzip;
use JetBackup\Destination\Destination;
use JetBackup\Exception\DBException;
use JetBackup\Exception\GzipException;
use JetBackup\Exception\TaskException;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemDownload;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotDownload;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');
class DownloadBackupLog extends Task
{

    const LOG_FILENAME = 'download';

    private Snapshot $_snapshot;
    private QueueItemDownload $_queue_item_download;
    private string $_target;

    public function __construct() {
        parent::__construct(self::LOG_FILENAME);
    }

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws TaskException
	 */
    public function execute():void  {
        parent::execute();

        $this->_queue_item_download = $this->getQueueItem()->getItemData();
        $this->_snapshot = new Snapshot($this->_queue_item_download->getSnapshotId());

        $destination = new Destination($this->_snapshot->getDestinationId());

        if(!License::isValid() && !in_array($destination->getType(), Destination::LICENSE_EXCLUDED)) {
            $this->getLogController()->logError("You can't download backup log from {$destination->getType()} destination without a license");
            $this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
            $this->getQueueItem()->updateProgress('Download Aborted!', QueueItem::PROGRESS_LAST_STEP);
            return;
        }

        if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
            $this->getLogController()->logMessage('Starting Download Task');

            $this->getQueueItem()->getProgress()->setTotalItems(count(Queue::STATUS_DOWNLOAD_LOG_NAMES));
            $this->getQueueItem()->save();

            $this->getQueueItem()->updateProgress('Starting Download Task');
        } elseif($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
            $this->getLogController()->logMessage('Resumed Download Task');
        }


        try {
            $this->func([$this, '_download']);
            $this->func([$this, '_decompress']);
            if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE && !$this->getQueueItem()->getErrors()) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
            else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
            $this->getLogController()->logMessage('Completed!');
        } catch(\Exception $e) {
            $this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
            $this->getLogController()->logError($e->getMessage());
            $this->getLogController()->logMessage('Failed!');
        }

        $this->getQueueItem()->updateProgress(
            $this->getQueueItem()->getStatus() == Queue::STATUS_DONE
                ? 'Download Backup Log Completed!'
                : ($this->getQueueItem()->getStatus() == Queue::STATUS_PARTIALLY
                ? 'Completed with errors (see logs)'
                : 'Download Logs Failed!'),
            QueueItem::PROGRESS_LAST_STEP
        );

        $this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
    }

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \JetBackup\Exception\IOException
	 * @throws \Exception
	 */
    public function _download() {

        $queue_item = $this->getQueueItem();

        $this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
        $this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

        $queue_item->updateStatus(Queue::STATUS_DOWNLOAD_DOWNLOAD);
        $queue_item->updateProgress('Downloading backup log file');
        $this->getLogController()->logMessage('Downloading backup log file');

        $download = new SnapshotDownload($this->_snapshot, $this->getQueueItem()->getWorkspace());
        $download->setLogController($this->getLogController());
        $download->setQueueItem($this->getQueueItem());
        $download->setTask($this);
        $download->downloadLog();

        // done downloading, reset sub process bar
        $queue_item->getProgress()->resetSub();
        $queue_item->save();
    }

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws GzipException
	 */
    public function _decompress(){

        $log_file =  $this->getQueueItem()->getWorkspace(). JetBackup::SEP . Snapshot::SKELETON_LOG_DIRNAME . JetBackup::SEP .Snapshot::SKELETON_LOG_FILENAME;

        if(Archive::isGzCompressed($log_file)) {
            if(file_exists($log_file)) {
                $this->getLogController()->logMessage("\tDecompressing $log_file");
                Gzip::decompress($log_file);
            }
            $log_file = substr($log_file, 0, -3);

        }
        $this->getQueueItem()->setLogFile($log_file);
        $this->getQueueItem()->save();

    }
}