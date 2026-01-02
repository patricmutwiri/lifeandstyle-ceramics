<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Destination\Vendors\SFTP;

use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {

	private SFTP $_destination;
	private string $_directory;
	/** @var iDestinationFile[] */
	private array $_queue=[];

	/**
	 * @param SFTP $destination
	 * @param string $directory
	 *
	 * @throws IOException
	 */
	public function __construct(SFTP $destination, string $directory) {
		if(!$destination->dirExists($directory)) return;

		$this->_destination = $destination;
		$this->_directory = $directory;

		$this->rewind();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	private function _loadQueue():void {

		$this->_destination->retries(function() {
			$this->_queue = [];

			$connection = $this->_destination->getConnection();
			if(!($list = $connection->rawlist($this->_destination->getRealPath($this->_directory)))) return;
			krsort($list, SORT_STRING);
			foreach($list as $filename => $details) {
				if($filename == '.' || $filename == '..') continue;
				$details['path'] = $this->_directory . '/' . $filename;
				$details['perms'] = $details['mode'];
				$this->_queue[] = DestinationFile::genFile($details);
			}

		}, "Failed fetching list");
	}

	/**
	 * @throws IOException
	 */
	public function rewind():void { $this->_loadQueue(); }

	/**
	 * @return bool
	 */
	public function hasNext():bool { return $this->_queue && !!sizeof($this->_queue); }

	/**
	 * @return iDestinationFile|null
	 */
	public function getNext():?iDestinationFile {
		if(!$this->hasNext()) return null;
		return array_pop($this->_queue);
	}
}