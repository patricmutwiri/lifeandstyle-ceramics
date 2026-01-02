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
namespace JetBackup\Destination\Vendors\FTP;

use Exception;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {
	
	private FTP $_destination;
	private string $_directory;
	/** @var iDestinationFile[] */
	private array $_list=[];

	/**
	 * @param FTP $destination
	 * @param string $directory
	 *
	 * @throws IOException
	 */
	public function __construct(FTP $destination, string $directory) {
		$this->_destination = $destination;
		$this->_directory = preg_replace("/^\/+/", "/", $directory);
		$this->rewind();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind():void {
		try{
			$this->_list = [];
			$list = $this->_destination->_listDir($this->_directory);
			foreach($list as $item) $this->_list[$item->getFullPath()] = $item;
			if($this->_list) krsort($this->_list, SORT_STRING);
		} catch (Exception $e) {
			if($e->getCode() == FTPClient::CODE_TRUNCATED) throw new IOException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @return bool
	 */
	public function hasNext(): bool {
		return !!$this->_list;
	}

	/**
	 * @return iDestinationFile|null
	 */
	public function getNext():?iDestinationFile {
		if(!$this->hasNext()) return null;
		$file = array_pop($this->_list);
		$file->setPath($this->_destination->removeRealPath($file->getPath()));
		return $file;
	}
}
