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
namespace JetBackup\Archive\Scan;

use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\VanishedException;

class Scan {

	private $_opendir;
	/** @var Scan */
	private $_parent;
	private $_source;
	private $_path;
	private $_size;

	/**
	 * @param $directory
	 * @param $source
	 *
	 * @throws VanishedException
	 */
	public function __construct($directory, $source) {
		if(!file_exists($directory) || !is_dir($directory) || ! ( $this->_opendir = @opendir( $directory ) ) )
			throw new VanishedException(sprintf("The directory %s has vanished", $directory));
		$this->_path = $directory;
		$this->_source = $source;
		$this->_size = 0;
	}

	/**
	 * @return ScanFile|null
	 */
	public function next() {
		if(($next = readdir($this->_opendir)) === false) return null;
		if($next == '.' || $next == '..') return $this->next();
		
		try {
			return new ScanFile($this->_path, $next, $this->_source);
		} catch(ArchiveException $ex ) {
			return null;
		}
	}

	/**
	 * @return ScanFile
	 * @throws ArchiveException
	 */
	public function getDetails() {
		return new ScanFile(dirname($this->_path), basename($this->_path), $this->_source);
	}
	
	public function addSize($size) {
		$this->_size += (int) $size;
		if($this->_parent) $this->_parent->addSize($size);
	}
	public function getSize() { return $this->_size; }
	public function getPath() { return $this->_path; }
	
	public function setParent(Scan $parent) { $this->_parent = $parent; }
	public function getParent() { return $this->_parent; }

	public function __destruct() {
		closedir($this->_opendir);
	}
}
