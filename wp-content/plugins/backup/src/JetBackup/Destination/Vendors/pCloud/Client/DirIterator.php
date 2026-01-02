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
namespace JetBackup\Destination\Vendors\pCloud\Client;

use Exception;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {

	const CHUNK_LIMIT = 1000;

	private Box $_destination;
	private string $_directory;
	private ?ListFiles $_list=null;
	private ?string $_parent_id=null;
	/** @var File[] */
	private array $_files=[];

	/**
	 * @param Box $destination
	 * @param string $directory
	 * @param string|null $parent_id
	 *
	 * @throws IOException
	 * @throws JBException
	 */
	public function __construct(Box $destination, string $directory, ?string $parent_id=null) {
		$this->_destination = $destination;
		$this->_directory = $directory;
		
		if(!$this->_destination->getClient()) throw new IOException("Unable to retrieve google drive service");
		$path = $this->_destination->getRealPath($this->_directory);
		
		if(!$parent_id) $parent_id = $this->_destination->getFileId($path == '/' ? '' : $path);
		$this->_parent_id = $parent_id;

		if(!$this->_destination->dirExists($this->_directory, $parent_id)) return;

		$this->rewind();
	}

	/**
	 * @param bool $rewind
	 *
	 * @return void
	 * @throws IOException
	 */
	private function _loadChunk(bool $rewind=false):void {
		try {

			$marker = '';

			if(!$rewind && $this->_list) {
				if(!$this->_list->getNextPageToken()) {
					$this->_list = null;
					return;
				}
				$marker = $this->_list->getNextPageToken();
			}

			$this->_list = $this->_destination->_retries(function() use ($rewind, $marker) {
				return $this->_destination->getClient()->listFolder($this->_parent_id, self::CHUNK_LIMIT, $marker);
			}, "Failed fetching list of files");
			
			$this->_files = $this->_list ? $this->_list->getFiles() : [];

		} catch(Exception $e) {
			if($e->getCode() == 404) $this->_files = [];
			else throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind():void { $this->_loadChunk(true); }

	/**
	 * @return bool
	 * @throws IOException
	 */
	public function hasNext():bool {
		if(!($this->_files && count($this->_files))) $this->_loadChunk();
		return ($this->_files && count($this->_files));
	}

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
		$file->setFileData(json_encode([ 'id' => $nextfile->getId() ]));

		return $file;
	}
}