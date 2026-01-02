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

class GZFile extends File {

	private $_fd;

	/**
	 * @throws ArchiveException
	 */
	public function __construct(string $filename, string $mode) {
		parent::__construct($filename, $mode);

		if (!($this->_fd = @gzopen($filename, $mode)))
			throw new ArchiveException('Could not open file: '.$filename);
	}

	public function truncate($offset): bool {
		throw new ArchiveException("gzip doesn't support 'truncate'");
	}

	public function eof(): bool {
		return gzeof($this->_fd);
	}

	public function read(int $length) {
		return gzread($this->_fd, $length);
	}
	
	public function write(string $data,$length=null) {
		return gzwrite($this->_fd, $data, $length);
	}

	/**
	 * @throws ArchiveException
	 */
	public function seek(int $offset, int $whence = SEEK_SET): int {
		throw new ArchiveException("gzip doesn't support 'seek'");
	}

	public function tell(): int {
		return gztell($this->_fd);
	}

	public function flush() : bool {
		return fflush($this->_fd);
	}

	public function close(): bool {
		if(!$this->_fd) return true;
		$result = gzclose($this->_fd);
		$this->_fd = null;
		return $result;
	}

	/**
	 * @throws ArchiveException
	 */
	public static function getGzipOriginalSize($filePath) {


		// Open the file in binary read mode
		$file = fopen($filePath, 'rb');
		if ($file === false) {
			throw new ArchiveException("Unable to open file: $filePath");
		}

		// Seek to the end of the file to read the original size
		fseek($file, -4, SEEK_END);
		$sizeBytes = fread($file, 4);
		fclose($file);

		// Convert the size from little-endian to integer
		return unpack('V', $sizeBytes)[1];
	}



	/**
	 * @throws ArchiveException
	 */
	public static function isGzipFile($filePath): bool {
		// Check if the file exists and is readable
		if (!file_exists($filePath) || !is_readable($filePath)) {
			throw new ArchiveException("File does not exist or is not readable: $filePath");
		}

		// Open the file in binary read mode
		$file = fopen($filePath, 'rb');
		if ($file === false) {
			throw new ArchiveException("Unable to open file: $filePath");
		}

		// Read the first three bytes of the file
		$header = fread($file, 3);
		fclose($file);

		// Check if the magic number and compression method match the gzip signature
		// Gzip files start with 1F 8B 08 (hexadecimal)
		return $header === "\x1F\x8B\x08";
	}


}