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

use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\JetBackup;

class ScanFile {

	const IFMT = 0170000;
	const IFDIR = 0040000;
	const IFCHR = 0020000;
	const IFBLK = 0060000;
	const IFREG = 0100000;
	const IFIFO = 0010000;
	const IFLNK = 0120000;
	const IFSOCK = 0140000;

	private $_filename;
	private $_path;
	private $_clean_path;
	private $_source;
	private $_stat;

	public function __construct($directory, $filename, $source) {
		$this->_path = $directory;
		$this->_filename = $filename;
		$this->_source = $source;
		clearstatcache(true, $directory . JetBackup::SEP . $filename);
		$this->_stat = @lstat($directory . JetBackup::SEP . $filename);
		if($this->_stat === false) throw new ArchiveException("Failed fetching information for file '$directory/$filename'");
	}

	public function getFilename() { return $this->_filename; }
	public function getPath() { return $this->_path; }
	public function getFullPath() {

		if (!isset($this->_clean_path)) {
			$dirSeparator = preg_quote(JetBackup::SEP, '#');
			$this->_clean_path = preg_replace("#" . $dirSeparator . "+#", JetBackup::SEP, $this->getPath() . JetBackup::SEP . $this->getFilename());
		}

		return $this->_clean_path;
	}
	public function getCleanPath() { return substr($this->getFullPath(), strlen($this->_source)); }
	public function getStat() { return $this->_stat; }
	public function getMode() { return $this->_stat['mode']; }
	public function getSize() { return $this->_stat['size']; }
	public function getModifyTime() { return $this->_stat['mtime']; }
	public function getGroupId() { return $this->_stat['gid']; }
	public function getOwnerId() { return $this->_stat['uid']; }
	public function getGroup() {
		$group = Util::getgrgid($this->getGroupId());
		return $group ? $group['name'] : '';
	}
	public function getOwner() {
		$user = Util::getpwuid($this->getOwnerId());
		return $user ? $user['name'] : '';
	}

	public function isDir() { return ($this->getMode() & self::IFMT) == self::IFDIR; }
	public function isFile() { return ($this->getMode() & self::IFMT) == self::IFREG; }
	public function isLink() { return ($this->getMode() & self::IFMT) == self::IFLNK; }
	public function isBlockDevice() { return ($this->getMode() & self::IFMT) == self::IFBLK; }
	public function isCharacterDevice() { return ($this->getMode() & self::IFMT) == self::IFCHR; }
	public function isFifo() { return ($this->getMode() & self::IFMT) == self::IFIFO; }
	public function isSocket() { return ($this->getMode() & self::IFMT) == self::IFSOCK; }

}
