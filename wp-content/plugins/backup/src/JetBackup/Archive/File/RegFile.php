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

class RegFile extends File {
	
	protected $_fd;

	/**
	 * @throws ArchiveException
	 */
	public function __construct(string $filename, string $mode) {

		parent::__construct($filename, $mode);

		if (!($this->_fd = fopen($filename, $mode)))
			throw new ArchiveException('Could not open file: '.$filename);
	}
	
	public function truncate($offset): bool {
		return ftruncate($this->_fd, $offset);
	}
	
	public function read(int $length) {
		return fread($this->_fd, $length);
	}
	
	public function eof(): bool {
		return feof($this->_fd);
	}

	public function flush() : bool {
		return fflush($this->_fd);
	}

	public function write(string $data, $length=null) {

		if ($length === null) return fwrite($this->_fd, $data);

		return fwrite($this->_fd, $data, $length);

	}

	public function seek(int $offset, int $whence = SEEK_SET): int {
		return fseek($this->_fd, $offset, $whence);
	}

	public function tell(): int {
		return ftell($this->_fd);
	}

	public function close(): bool {

		if(!$this->_fd) return true;
		$result = fclose($this->_fd);
		$this->_fd = null;
		return $result;

	}
}