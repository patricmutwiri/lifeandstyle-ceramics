<?php

namespace JetBackup\Filesystem;

use JetBackup\Exception\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class File {

	const IFMT = 0170000;
	const IFDIR = 0040000;
	const IFCHR = 0020000;
	const IFBLK = 0060000;
	const IFREG = 0100000;
	const IFIFO = 0010000;
	const IFLNK = 0120000;
	const IFSOCK = 0140000;

	private $_file;
	private $_stat;
	private $_readable;
	private $_fixed_size;
	
	public function __construct($file) {
		$this->_file = $file;
	}

	/**
	 * @throws IOException
	 */
	public function getStat($key = null, $default = null) {
		if ($this->_stat === null) {
			$this->_stat = @lstat($this->_file);
			if (!$this->_stat) throw new IOException('[getStat] Unable to retrieve file status: ' . $this->_file);
		}
		return $key ? ($this->_stat[$key] ?? $default) : $this->_stat;
	}
	
	public function path():string { return $this->_file; }
	public function dir() { return $this->isDir() ? dir($this->path()) : null; }
	public function exists():bool { return $this->_stat !== null || file_exists($this->_file); }
	public function size():int {
		if($this->_fixed_size === null) {
			if($this->isDir() || $this->isLink() || strpos($this->_file, "\0") !== false) $this->_fixed_size = true;
			else $this->_fixed_size = false;
		}
		if($this->_fixed_size) return 0;
		return (int) $this->getStat('size', 0); 
	}
	public function mtime():int { return (int) $this->getStat('mtime', 0); }
	public function uid():int { return (int) $this->getStat('uid', 0); }
	public function gid():int { return (int) $this->getStat('gid', 0); }
	public function mode():int { return (int) $this->getStat('mode', 0); }

	public function isDir():bool { return ($this->mode() & self::IFMT) == self::IFDIR; }
	public function isFile():bool { return ($this->mode() & self::IFMT) == self::IFREG; }
	public function isLink():bool { return ($this->mode() & self::IFMT) == self::IFLNK; }
	public function isBlockDevice():bool { return ($this->mode() & self::IFMT) == self::IFBLK; }
	public function isCharacterDevice():bool { return ($this->mode() & self::IFMT) == self::IFCHR; }
	public function isFifo():bool { return ($this->mode() & self::IFMT) == self::IFIFO; }
	public function isSocket():bool { return ($this->mode() & self::IFMT) == self::IFSOCK; }
	public function isReadable():bool {
		if($this->_readable === null) $this->_readable = is_readable($this->_file);
		return $this->_readable;
	}
}