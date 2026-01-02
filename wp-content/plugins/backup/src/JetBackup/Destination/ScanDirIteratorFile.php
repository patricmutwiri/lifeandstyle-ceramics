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

use JetBackup\Destination\Integration\DestinationFile;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOVanishedException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ScanDirIteratorFile {

	const IFMT = 0170000;
	const IFDIR = 0040000;
	const IFCHR = 0020000;
	const IFBLK = 0060000;
	const IFREG = 0100000;
	const IFIFO = 0010000;
	const IFLNK = 0120000;
	const IFSOCK = 0140000;

	private string $_path;
	private string $_clean_path;
	private array $_stat;
	private bool $_pulled=false;

	/**
	 * @param string $path
	 *
	 * @throws IOVanishedException
	 */
	public function __construct(string $path) {
		$this->_path = $path;
		clearstatcache(true, $path);
		$stat = @lstat($path);
		if(!$stat) throw new IOVanishedException(sprintf("The file %s has vanished", $path));
		$this->_stat = $stat;
	}

	/**
	 * @return string
	 */
	public function getPath():string { return $this->_path; }

	/**
	 * @return string
	 */
	public function getFullPath():string {
		if(!isset($this->_clean_path)) $this->_clean_path = preg_replace("#/+#", "/", $this->getPath());
		return $this->_clean_path;
	}
	
	/**
	 * @return array
	 */
	public function getStat():array { return $this->_stat; }

	/**
	 * @return int
	 */
	public function getMode():int { return (int) $this->_stat['mode']; }

	/**
	 * @return int
	 */
	public function getSize():int { return (int) $this->_stat['size']; }

	/**
	 * @return int
	 */
	public function getModifyTime():int { return (int) $this->_stat['mtime']; }

	/**
	 * @return int
	 */
	public function getGroupId():int { return (int) $this->_stat['gid']; }

	/**
	 * @return int
	 */
	public function getOwnerId():int { return (int) $this->_stat['uid']; }

	/**
	 * @return string
	 */
	public function getGroup():string {
		$group = Util::getgrgid($this->getGroupId());
		return $group ? $group['name'] : '';
	}

	/**
	 * @return string
	 */
	public function getOwner():string {
		$user = Util::getpwuid($this->getOwnerId());
		return $user ? $user['name'] : '';
	}

	/**
	 * @return int
	 */
	public function getType():int {
		switch(($this->getMode() & self::IFMT)) {
			case self::IFDIR: return DestinationFile::TYPE_DIRECTORY;
			case self::IFCHR: return DestinationFile::TYPE_CHAR;
			case self::IFBLK: return DestinationFile::TYPE_BLOCK;
			case self::IFREG: return DestinationFile::TYPE_FILE;
			case self::IFIFO: return DestinationFile::TYPE_FIFO;
			case self::IFLNK: return DestinationFile::TYPE_LINK;
			case self::IFSOCK: return DestinationFile::TYPE_SOCKET;
			default: return DestinationFile::TYPE_UNKNOWN;
		}
	}

	public function setPulled():void { $this->_pulled = true; }
	public function isPulled():bool { return $this->_pulled; }

	/**
	 * @return string
	 */
	public function getLinkTarget():string { return $this->isLink() ? readlink($this->getFullPath()) : ''; }
	
	/**
	 * @return bool
	 */
	public function isDir():bool { return ($this->getMode() & self::IFMT) == self::IFDIR; }

	/**
	 * @return bool
	 */
	public function isLinkDir():bool { return $this->isLink() && is_dir(realpath($this->getFullPath())); }

	/**
	 * @return bool
	 */
	public function isFile():bool { return ($this->getMode() & self::IFMT) == self::IFREG; }

	/**
	 * @return bool
	 */
	public function isLink():bool { return ($this->getMode() & self::IFMT) == self::IFLNK; }

	/**
	 * @return bool
	 */
	public function isBlockDevice():bool { return ($this->getMode() & self::IFMT) == self::IFBLK; }

	/**
	 * @return bool
	 */
	public function isCharacterDevice():bool { return ($this->getMode() & self::IFMT) == self::IFCHR; }

	/**
	 * @return bool
	 */
	public function isFifo():bool { return ($this->getMode() & self::IFMT) == self::IFIFO; }

	/**
	 * @return bool
	 */
	public function isSocket():bool { return ($this->getMode() & self::IFMT) == self::IFSOCK; }
}
