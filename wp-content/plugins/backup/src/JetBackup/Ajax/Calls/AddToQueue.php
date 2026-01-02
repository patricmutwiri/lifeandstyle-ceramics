<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__' )) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Archive\Archive;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Download\Download;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Exception\QueueException;
use JetBackup\Export\Vendor\Vendor;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemRestore;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Upload\Upload;
use JetBackup\UserInput\UserInput;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class AddToQueue extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _snapshotPath():string { return $this->getUserInput('snapshot_path', '', UserInput::STRING); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getType():int { return $this->getUserInput(QueueItem::TYPE, 0, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getPanelType():int { return $this->getUserInput(Vendor::PANEL_TYPE, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getFileName():string { return $this->getUserInput('fileName', '', UserInput::STRING); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getFileSize():int { return $this->getUserInput('fileSize', 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getFileUploadId():string { return $this->getUserInput('fileUploadId', '', UserInput::STRING); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getMixedSites():bool { return $this->getUserInput('mixedSites', false, UserInput::BOOL); }

	/**
	 * @throws AjaxException
	 */
	private function _getFileManagerList():array { return $this->getUserInput('fileManager', [], UserInput::ARRAY, UserInput::ARRAY);}

	/**
	 * @throws AjaxException
	 */
	private function _getRestoreOptions(): int { return $this->getUserInput('restoreOptions', 0, UserInput::UINT); }

	/**
	 * Returns an array of homedir paths, these can be used for either include (restore only X), or exclude (restore all but Y)
	 * @return array
	 * @throws AjaxException
	 */
	private function _getHomedirFolders():array { return $this->getUserInput('folderList', [], UserInput::ARRAY, UserInput::STRING);}

	/**
	 * Returns an array of database tables, these can be used for either include (restore only X), or exclude (restore all but Y)
	 * @return array
	 * @throws AjaxException
	 */
	private function _getDatabaseTables():array { return $this->getUserInput('selectedTables', [], UserInput::ARRAY, UserInput::STRING);}



	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws DestinationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \JetBackup\Exception\IOException
	 */
	public function execute(): void {

		try {
			switch ($this->_getType()) {
				case Queue::QUEUE_TYPE_BACKUP: $this->_queueBackup(); break;
				case Queue::QUEUE_TYPE_RESTORE: $this->_queueRestore(); break;
				case Queue::QUEUE_TYPE_DOWNLOAD: $this->_queueDownload(); break;
                case Queue::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG: $this->_queueDownloadBackupLog(); break;
				case Queue::QUEUE_TYPE_REINDEX: $this->_queueReindex(); break;
				case Queue::QUEUE_TYPE_EXPORT: $this->_queueExport(); break;
				case Queue::QUEUE_TYPE_EXTRACT: $this->_queueExtract(); break;


				// Both of those should be automatic and shouldn't be triggered by the user
				//case Queue::QUEUE_TYPE_RETENTION_CLEANUP: break;
				//case Queue::QUEUE_TYPE_SYSTEM: break;

				default: throw new AjaxException("Invalid queue type specified: %s", [$this->_getType()]);
			}
		} catch(QueueException $e) {
			throw new AjaxException("Failed adding to queue. Error: %s", [$e->getMessage()]);			
		}

	}

	/**
	 * Checks if the listed destinations are valid, could be useful for manual delete from DB, but still listed in the job
	 *
	 * @throws DBException
	 * @throws InvalidArgumentException
	 * @throws IOException
	 * @throws AjaxException
	 */
	private static function _validateDestinations(?array $destinations): void {
		if (empty($destinations)) throw new AjaxException("No valid destinations provided.");
		$validDestinations = array_filter($destinations, function ($destination_id) {return (new Destination($destination_id))->getId();});
		if (empty($validDestinations)) throw new AjaxException("No valid destination found.");
	}

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws QueueException
	 * @throws IOException
	 * @throws InvalidArgumentException|DBException
	 */
	private function _queueBackup():void {
		if (!$this->_getId()) throw new AjaxException("No backup job id was provided");
		$job = new BackupJob($this->_getId());
		self::_validateDestinations($job->getDestinations());
		if (!$job->getId()) throw new AjaxException("Invalid backup job id provided");
		$job->addToQueue(true);
		$this->setResponseMessage($job->getName(). " Added to queue!");
		$this->setResponseData($this->isCLI() ? $job->getDisplayCLI() : $job->getDisplay());
	}

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \JetBackup\Exception\IOException
	 */
	private function _queueRestore():void {

		$options = $this->_getRestoreOptions();

		/**
		 * Determine which files and database tables should be included or excluded
		 * based on the provided restore options.
		 *
		 * - `$included_files`: Restore only listed folders/files
		 * - `$excluded_files`: Exclude listed folders/files
		 * - `$exclude_db`: Exclude listed database tables
		 * - `$include_db`: Restore only listed databases tables
		 */

		$included_files = ($options & (QueueItemRestore::OPTION_RESTORE_FILES_INCLUDE)) ? $this->_getHomedirFolders() : [];
		$excluded_files = ($options & (QueueItemRestore::OPTION_RESTORE_FILES_EXCLUDE)) ? $this->_getHomedirFolders() : [];
		$exclude_db    = ($options & QueueItemRestore::OPTION_RESTORE_DATABASE_EXCLUDE) ? $this->_getDatabaseTables() : [];
		$include_db    = ($options & QueueItemRestore::OPTION_RESTORE_DATABASE_INCLUDE) ? $this->_getDatabaseTables() : [];

		if ($this->_getId()) {
			$snap = new Snapshot($this->_getId());
			if (!$snap->getId()) throw new AjaxException("Invalid snapshot id provided");
			if ($snap->getBackupType() != BackupJob::TYPE_ACCOUNT) throw new AjaxException("Can only restore account backups");
			if (($options & QueueItemRestore::OPTION_RESTORE_FILES_INCLUDE) && !Helper::isMultisite() && $snap->getEngine() != Engine::ENGINE_JB) throw new AjaxException("This restore feature is not supported");
			if( $snap->getEngine() != Engine::ENGINE_JB) self::_validateDestinations([$snap->getDestinationId()]);
			$snap->addToRestoreQueue($options, $excluded_files, $included_files, $exclude_db, $include_db, $this->_getFileManagerList());
			$this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
			$this->setResponseMessage("Added to queue!");
		} elseif($this->_snapshotPath()) {
			Snapshot::addToRestoreQueueByPath($this->_snapshotPath(), $options, $excluded_files, $include_db, $exclude_db);
			$this->setResponseMessage("Added to queue!");
		} elseif($this->_getFileName()) {

			$tmp_name = isset($_FILES['file']['tmp_name']) ? Wordpress::sanitizeTextField($_FILES['file']['tmp_name']) : null;
			if(!$tmp_name)
				throw new AjaxException("No upload file was provided");

			$upload = new Upload();

			if(!$this->_getFileUploadId()) {
				if(!$this->_getFileSize()) throw new AjaxException("No upload file size was provided");

				$upload->setFilename($this->_getFileName());
				$upload->setSize($this->_getFileSize());
				$upload->setCreated(time());
				$upload->save();
			} else {
				$upload->loadByUploadId($this->_getFileUploadId());
				if(!$upload->getId()) throw new AjaxException("Invalid upload id was provided");
			}

			try {
				$upload->writeChunk($tmp_name);
			} catch(IOException $e) {
				throw new AjaxException("Failed writing chunk to upload file. Error: %s", [$e->getMessage()]);
			}
			
			if($upload->isCompleted()) {

				if (!( Archive::isTar($upload->getFileLocation()) || Archive::isGzCompressed($upload->getFileLocation()) ) ) {
					Util::rm(dirname($upload->getFileLocation()));
					throw new AjaxException("Invalid backup file provided. Only .tar or .tar.gz files are allowed.");
				}

				Snapshot::addToRestoreQueueByPath($upload->getFileLocation());
				$this->setResponseMessage("Added to queue!");
			} else {
				$this->setResponseMessage("Waiting for next file chunk");
				$this->setResponseData([ 'upload_id' => $upload->getUniqueId() ]);
			}

		} else throw new AjaxException("No snapshot id, path of file was provided");
	}

    /**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws DestinationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 */
	private function _queueReindex():void {

		if($this->_getId() == 0) {
			if(!Factory::getSettingsGeneral()->isJBIntegrationEnabled()) throw new AjaxException("JetBackup Linux integration is disabled");
			if(!JetBackupLinux::isInstalled()) throw new AjaxException("JetBackup Linux integration is not installed");

			try {
				JetBackupLinux::checkRequirements();
				JetBackupLinux::addToQueue();
				$this->setResponseMessage("Added to reindex queue");
			} catch (JetBackupLinuxException $e) {
				throw new AjaxException($e->getMessage());
			}

		} else {
			if (!$this->_getId()) throw new AjaxException("No destination id was provided");
			$destination = new Destination($this->_getId());
			if (!$destination->getId()) throw new AjaxException("Invalid destination id provided");

			$destination->addToQueue($this->_getMixedSites());
			if(!$destination->getId()) throw new AjaxException("Invalid destination id provided");
			$this->setResponseMessage("Destination '" . $destination->getName() . "' add to reindex queue");
			$this->setResponseData($destination->getDisplay());
		}


	}

    /**
	 * Export to control panel (not supported with legacy backups)
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 */
	private function _queueExport():void {
		if (!$this->_getId()) throw new AjaxException("No snapshot id was provided");
		$snap = new Snapshot($this->_getId());
		self::_validateDestinations([$snap->getDestinationId()]);
		if (!$snap->getId()) throw new AjaxException("Invalid snapshot id provided");
		if ($snap->getEngine() == Engine::ENGINE_SGB) throw new AjaxException("This feature is not supported with legacy SGB backups");
		if ($snap->getBackupType() != BackupJob::TYPE_ACCOUNT) throw new AjaxException("Can only export account backups");
		if ($snap->getContains() != BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL) throw new AjaxException("Can only export full backups");
		$snap->addToExportQueue($this->_getPanelType());
		$this->setResponseMessage("Added to queue!");
		$this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
	}



    /**
     * @return void
     * @throws AjaxException
     * @throws DBException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws QueueException
     */
    private function _queueDownload():void {
        if (!$this->_getId()) throw new AjaxException("No snapshot id was provided");
        $snap = new Snapshot($this->_getId());
        self::_validateDestinations([$snap->getDestinationId()]);
        if (!$snap->getId()) throw new AjaxException("Invalid snapshot id provided");
        if ($snap->getBackupType() != BackupJob::TYPE_ACCOUNT) throw new AjaxException("Can only download account backups");

        $totalDownloads = sizeof(Download::query()->getQuery()->fetch());
        $allowedDownloads = Factory::getSettingsMaintenance()->getDownloadLimit();
        if ($allowedDownloads > 0 && $totalDownloads >= $allowedDownloads) throw new AjaxException("Download limit of $allowedDownloads reached. Please clear an existing download before starting a new one.");

        $snap->addToDownloadQueue();
        $this->setResponseMessage("Added to queue!");
        $this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
    }

    /**
     * @return void
     * @throws AjaxException
     * @throws DBException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws QueueException
     */
    private function _queueDownloadBackupLog():void {
        if (!$this->_getId()) throw new AjaxException("No snapshot id was provided");
        $snap = new Snapshot($this->_getId());
        self::_validateDestinations([$snap->getDestinationId()]);
        if (!$snap->getId()) throw new AjaxException("Invalid snapshot id provided");

        $snap->addToDownloadLogQueue();
        $this->setResponseMessage("Added to queue!");
        $this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
    }

    /**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 */
	private function _queueExtract():void {
		if (!$this->_getId()) throw new AjaxException("No snapshot id was provided");
		$snap = new Snapshot($this->_getId());
		self::_validateDestinations([$snap->getDestinationId()]);
		if (!$snap->getId()) throw new AjaxException("Invalid snapshot id provided");
		if ($snap->getBackupType() != BackupJob::TYPE_ACCOUNT) throw new AjaxException("Can only extract account backups");

		$snap->addToExtractQueue();
		$this->setResponseMessage("Added to queue!");
		$this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
	}
}
