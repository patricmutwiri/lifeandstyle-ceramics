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
namespace JetBackup\Destination\Vendors\pCloud;

use Exception;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\pCloud\Client\Client;
use JetBackup\Destination\Vendors\pCloud\Client\File;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {

	private pCloud $_destination;
	private string $_directory;
	/** @var File[] */
	private array $_files=[];

	/**
	 * @param pCloud $destination
	 * @param string $directory
	 * @param string|null $parent_id
	 *
	 * @throws IOException
	 * @throws JBException
	 */
	public function __construct(pCloud $destination, string $directory) {
		$this->_destination = $destination;
		$this->_directory = $directory;
		
		if(!$this->_destination->getClient()) throw new IOException("Unable to retrieve google drive service");
		//if(!$this->_destination->dirExists($this->_directory)) return;

		$this->rewind();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind():void {
		try {

			$this->_files = $this->_destination->_retries(function() {
				return $this->_destination->getClient()->listFolder($this->_directory);
			}, "Failed fetching list of files");

		} catch(Exception $e) {
			if($e->getCode() == Client::CODE_DIR_NOT_FOUND) $this->_files = [];
			else throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return bool
	 * @throws IOException
	 */
	public function hasNext():bool { return ($this->_files && count($this->_files)); }

	/**
	 * @return ?iDestinationFile
	 * @throws IOException
	 */
	public function getNext():?iDestinationFile {
		if(!$this->hasNext()) return null;

		$nextfile = array_shift($this->_files);
		
		$path = $this->_directory . '/' . $nextfile->getName();
		$basename = basename($path);
		
		$file = new DestinationFile();
		$file->setType($nextfile->getMimeType() == Client::MIMITYPE_DIR ? iDestinationFile::TYPE_DIRECTORY : iDestinationFile::TYPE_FILE);
		$file->setName($basename);
		$file->setPath($basename == $path ? '' : dirname($path));
		$file->setSize($nextfile->getMimeType() == Client::MIMITYPE_DIR ? 4096 : $nextfile->getSize());
		$file->setModifyTime($nextfile->getModificationTime());

		return $file;
	}
}