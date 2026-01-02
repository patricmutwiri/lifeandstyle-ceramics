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

abstract class File {
	
	public function __construct(string $filename, string $mode) {

		if(strpos($mode, 'r') !== false && !file_exists($filename))
			throw new ArchiveException('no such file: '.$filename);
	}
	
	abstract public function truncate($offset): bool;
	abstract public function read(int $length);
	abstract public function write(string $data, $length=null);
	abstract public function seek(int $offset, int $whence = SEEK_SET): int;
	abstract public function tell(): int;
	abstract public function eof(): bool;
	abstract public function flush(): bool;
	abstract public function close(): bool;
	
	public function __destruct() {
		$this->close();
	}
}