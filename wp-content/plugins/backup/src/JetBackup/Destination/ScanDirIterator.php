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
namespace JetBackup\Destination;

use JetBackup\Exception\IOVanishedException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ScanDirIterator {

	private array $_queue;

	/**
	 * @param string $directory
	 *
	 * @throws IOVanishedException
	 */
	public function __construct(string $directory) {
		$this->_queue = [];
		$this->_loadDir($directory);
	}

	/**
	 * @param string $directory
	 *
	 * @return void
	 * @throws IOVanishedException
	 */
	private function _loadDir(string $directory):void {
		if(!file_exists($directory) || !is_dir($directory)) throw new IOVanishedException(sprintf("The directory %s has vanished", $directory));
		$files = opendir($directory);
		while($file = readdir($files)) {
			if($file == '.' || $file == '..') continue;
			try {
				$this->_queue[] = new ScanDirIteratorFile($directory . '/' . $file);
			} catch(IOVanishedException $e) {}
		}
	}

	/**
	 * @return string|null
	 */	
	public function queueShift():?ScanDirIteratorFile { return $this->_queue ? array_pop($this->_queue) : null; }
	
	/**
	 * @return ScanDirIteratorFile|null
	 * @throws IOVanishedException
	 */
	public function next():?ScanDirIteratorFile {
		if(!($file = $this->queueShift())) return null;
		
		if($file && $file->isDir() && !$file->isPulled()) {
			$file->setPulled();
			$this->_queue[] = $file;
			$this->_loadDir($file->getFullPath());
			return $this->next();
		}

		return $file;
	}
}
