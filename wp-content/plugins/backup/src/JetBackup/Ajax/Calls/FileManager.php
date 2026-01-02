<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* http://www.jetapps.com
*
**/
namespace  JetBackup\Ajax\Calls;

use JetBackup\Ajax\ListRecord;
use JetBackup\BackupJob\BackupJob;
use JetBackup\CLI\CLI;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\Snapshot\Snapshot;
use JetBackup\SocketAPI\Exception\SocketAPIException;
use JetBackup\UserInput\UserInput;
use JetBackup\Wordpress\Helper;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

defined("__JETBACKUP__") or die("Restricted Access.");


class FileManager extends ListRecord {


    /**
     * @return int
     * @throws AjaxException
     */
    public function getDestinationId():int { return $this->getUserInput('destination_id', 0, UserInput::UINT); }

    /**
	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getSnapshotId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	public function getPath():string { return $this->getUserInput('location', '', UserInput::STRING); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws SocketAPIException
	 * @throws DestinationException
	 */
	public function execute():void {
        if ($this->getDestinationId()) {
            $response = $this->getDestinationsFiles();
            $this->setResponseData($response);
            return;
        }
		$snap = new Snapshot($this->getSnapshotId());
		if(!$snap->getId()) throw new AjaxException("Invalid snapshot id provided");
		if($snap->getEngine() != Engine::ENGINE_JB) throw new AjaxException("This feature is only supported for JetBackup Linux snapshots");
		if($snap->getBackupType() != BackupJob::TYPE_ACCOUNT) throw new AjaxException("This feature is only supported for account backups");
		if ($snap->getContains() != BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR &&
		    $snap->getContains() != BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL) {
			throw new AjaxException("This feature is only supported for backups containing the home directory");
		}

		$item = null;
		$items = $snap->getItems();

		foreach($items as $item_details) {
			if($item_details->getBackupContains() != BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR) continue;
			$item = $item_details;
			break;
		}

		if(!$item) throw new AjaxException("This backup doesn't contain homedir item");
		if(!$userHomedir = Helper::getUserHomedir())throw new AjaxException("Unable to fetch linux user homedir");

		$wordpressHomedir = Factory::getWPHelper()->getWordPressHomedir();

		if(!str_starts_with($wordpressHomedir, $userHomedir))
			throw new AjaxException("The WP public dir doesn't start with the account homedir");

		$public_dir = substr($wordpressHomedir, strlen($userHomedir)+1);
		
		$path = preg_replace("#/+#", "/", $public_dir . JetBackup::SEP . $this->getPath());

		$sort = $this->getSort();
		if(isset($sort['_id'])) {
			$sort['name'] = $sort['_id'];
			unset($sort['_id']);
		}


		try {
			$response = JetBackupLinux::fileManager($item->getUniqueId(), $path, $this->getLimit(), $this->getSkip(), $sort);
		} catch(JetBackupLinuxException $e) {
			throw new AjaxException($e->getMessage());
		}

		if(!$this->isCLI()) {
			$this->setResponseData($response);
			return;
		}
		
		$output = [];

		foreach ($response['files'] as $file) {
			$output[] = [
				//'ID'        => $file['id'],
				'Name'      => $file['name'] . ($file['link'] ? ' -> ' . $file['link'] : ''),
				'Type'      => $file['icon'] == 'dir' ? 'Directory' : 'File',
				'Created'   => CLI::date(strtotime($file['created'])),
				'Size'      => Util::bytesToHumanReadable($file['size']),
			];
		}

		$this->setResponseData($output);
	}

	/**
	 * @return array
	 * @throws DestinationException
	 * @throws IOException
	 * @throws DBException
	 * @throws AjaxException
	 * @throws InvalidArgumentException
	 * @throws \Exception
	 */
    private function getDestinationsFiles(): array
    {
        $destination = new Destination($this->getDestinationId());
        if (!$destination->getId()) {
            throw new AjaxException("Invalid destination id provided");
        }

        $iterator = $destination->listDir($this->getPath());

        $limit = $this->getLimit(); // e.g., 10
        $skip  = $this->getSkip();  // e.g., 20

        $allFiles = [];

        while ($iterator->hasNext()) {
            $file = $iterator->getNext();
            $name = $file->getName();
            $type = ($file->getType() == iDestinationFile::TYPE_DIRECTORY) ? 'Directory' : 'File';
            $path = $file->getPath();
            $size = $file->getSize();
            $mtime = Util::date('Y-m-d H:i:s', $file->getModifyTime());

            $allFiles[] = [
                'name'    => $name,
                'type'    => $type,
                'created' => $mtime,
                'size'    => Util::bytesToHumanReadable($size),
                'path'    => $path,
                'icon'    => $type == 'Directory' ? 'dir' : 'file',
            ];
        }

        $totalFiles = count($allFiles);
        $pagedFiles = array_slice($allFiles, $skip, $limit);

        return [
            'total' => $totalFiles,
            'files' => $pagedFiles,
        ];
    }

}