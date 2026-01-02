<?php

namespace JetBackup\Destination\Vendors\Local;

use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\IOException;

class DirIterator implements DestinationDirIterator {

	const FILE_TYPES = [
		'file'      => iDestinationFile::TYPE_FILE,
		'dir'       => iDestinationFile::TYPE_DIRECTORY,
		'link'      => iDestinationFile::TYPE_LINK,
		'block'     => iDestinationFile::TYPE_BLOCK,
		'fifo'      => iDestinationFile::TYPE_FIFO,
		'char'      => iDestinationFile::TYPE_CHAR,
		'network'   => iDestinationFile::TYPE_NETWORK,
		'socket'    => iDestinationFile::TYPE_SOCKET,
	];

	private Local $_destination;
	private string $_directory;
	private array $_queue;
	private ?\Directory $_pointer=null;
	private ?iDestinationFile $_next=null;

	/**
	 * @param Local $destination
	 * @param string $directory
	 *
	 * @throws IOException
	 */
	public function __construct(Local $destination, string $directory) {
		if(!$destination->dirExists($directory))
			throw new IOException("Directory does not exist");

		$this->_destination = $destination;
		$this->_directory = $destination->getRealPath($directory);
		
		$this->rewind();
		$this->_findNext();
	}

	/**
	 * @return void
	 */
	public function rewind():void {
		$this->_next = null;
		$this->_queue = [];
		$this->_pointer = dir($this->_directory);
	}

	/**
	 * @return bool
	 */
	public function hasNext(): bool { return $this->_next !== null; }

	/**
	 * @return iDestinationFile|null
	 */
	public function getNext():?iDestinationFile {
		if($this->_next === null) return null;
		$next = $this->_next;
		$this->_findNext();
		return $next;
	}

	/**
	 * @return void
	 */
	private function _findNext():void {
		while(($this->_next = $this->_getNextPointer()) === null && $this->_queue) {
			$this->_pointer = array_shift($this->_queue);
		}
	}

	/**
	 * @return iDestinationFile|null
	 */
	private function _getNextPointer():?iDestinationFile {
		while($this->_pointer && ($fileName = $this->_pointer->read()) !== false) {
			if($fileName == '.' || $fileName == '..') continue;
			$path = $this->_pointer->path . '/' . $fileName;

			$type = self::FILE_TYPES[filetype($path)];
			if($type === null) $type = iDestinationFile::TYPE_UNKNOWN;

			$file = new DestinationFile();
			$file->setType($type);
			$file->setName($fileName);
			$file->setPath(substr($this->_pointer->path, strlen($this->_destination->getRealPath('/'))));
			$file->setSize($file->getType() == iDestinationFile::TYPE_DIRECTORY ? 4096 : filesize($path));
			$file->setModifyTime(filemtime($path));

			return $file;
		}
		return null;
	}
}

?>