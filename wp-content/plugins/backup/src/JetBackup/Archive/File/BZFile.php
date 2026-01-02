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
namespace JetBackup\Archive\File;

use JetBackup\Exception\ArchiveException;

class BZFile extends File {

	private $_fd;
	
	public function __construct(string $filename, string $mode) {
		parent::__construct($filename, $mode);

		if (!($this->_fd = @bzopen($filename, $mode)))
			throw new ArchiveException('Could not open file: '.$filename);
	}

	public function truncate($offset): bool {
		throw new ArchiveException("gzip doesn't support 'truncate'");
	}

	public function eof(): bool {
		return feof($this->_fd);
	}

	public function read(int $length) {
		return bzread($this->_fd, $length);
	}
	
	public function write(string $data, int $length=null) {
		return bzwrite($this->_fd, $data, $length);
	}

	public function seek(int $offset, int $whence = SEEK_SET): int {
		throw new ArchiveException("gzip doesn't support 'seek'");
	}

	public function tell(): int {
		return ftell($this->_fd);
	}

	public function flush() : bool {
		return fflush($this->_fd);
	}

	public function close(): bool {
		if(!$this->_fd) return true;
		$result = bzclose($this->_fd);
		$this->_fd = null;
		return $result;
	}
}