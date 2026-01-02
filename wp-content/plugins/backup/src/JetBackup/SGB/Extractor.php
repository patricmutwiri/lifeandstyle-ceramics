<?php

namespace JetBackup\SGB;

use JetBackup\Exception\SGBExtractorException;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Extractor {

	private $_fd;
	private $_target;
	private $_info_file;
	private LogController $_logController;

	/**
	 * @param string $file
	 * @param string $target
	 *
	 * @throws SGBExtractorException
	 */
	public function __construct(string $file, string $target) {
		if(!($this->_fd = fopen($file, "rb")))
			throw new SGBExtractorException("Failed opening file");

		// Move FD pointer to place
		$this->_seek(-4, SEEK_END);
		$footer_offset = $this->_readHex(4);
		$this->_seek(-$footer_offset, SEEK_END);

		$this->_info_file = $file . '.info';
		$this->_target = $target;
		$this->_logController = new LogController();

	}

	/**
	 * @param LogController $logController
	 *
	 * @return void
	 */
	public function setLogController(LogController $logController) { $this->_logController = $logController; }

	/**
	 * @return LogController
	 */
	public function getLogController():LogController { return $this->_logController; }

	/**
	 * @param int $length
	 *
	 * @return string|null
	 */
	private function _read(int $length):?string {
		$read = fread($this->_fd, $length);
		return $read === false ? null : $read;
	}

	/**
	 * @param int $position
	 * @param int $whence
	 *
	 * @return int
	 */
	private function _seek(int $position, int $whence=SEEK_SET):int {
		return fseek($this->_fd, $position, $whence);
	}

	/**
	 * @param int $length
	 *
	 * @return float|int
	 */
	private function _readHex(int $length) {
		return hexdec(self::_unpackLittleEndian($this->_read($length), $length) );
	}

	/**
	 * @return array
	 */
	private function _getExtra():array {
		$version = $this->_readHex(1);
		$extra_size = $this->_readHex(4);
		$extra = $extra_size > 0 ? $this->_read($extra_size) : [];

		if(is_string($extra)) {
			$extra = json_decode($extra, true);
			if(is_string($extra['tables'])) $extra['tables'] = json_decode($extra['tables'], true);
		}

		if(is_array($extra)) $extra['versions'] = $version;
		
		return $extra;
	}

	/**
	 * @return array
	 */
	private function _fetchFilesList():array {

		$total_files = $this->_readHex(4);

		$files = [];

		for($i = 0; $i < $total_files; $i++) {
			
			// we don't need the crc, just move the pointer 4 bytes
			$this->_seek(4, SEEK_CUR);
			//

			if (($filenameLen = $this->_readHex(2)) <= 0) continue;

			$file = [];
			$file['filename'] = $this->_read($filenameLen);
			$file['offset'] = $this->_readHex(8);
			$file['chunks'] = [];
			// We don't need the compressed/uncompressed length, just move the pointer 16 bytes (8 bytes for each one)
			$this->_seek(16, SEEK_CUR);
			//
			$total_chunks = $this->_readHex(4);

			for($j = 0; $j < $total_chunks; $j++) {
				$file['chunks'][] = [
					'start' => $this->_readHex(8),
					'size'  => $this->_readHex(8),
				];
			}

			$files[] = $file;
		}
		
		return $files;
	}

	/**
	 * @param array $file
	 *
	 * @return void
	 * @throws SGBExtractorException
	 */
	private function _extractFile(array $file):void {

		$target = $this->_target . JetBackup::SEP . $file['filename'];
		
		if(!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
		
		$target_temp = $target . '.sgbpTemFile';

		$chunks = $file['chunks'];
		
		$info = $this->_getInfo();

		if($info->chunk <= 0 && file_exists($target_temp)) unlink($target_temp);
		$fd = fopen($target_temp, "ab");

		if($info->chunk > 0) $chunks = array_splice($chunks, $info->chunk);

		foreach($chunks as $chunk) {
			fseek($fd, $chunk['start']);
			
			$this->_seek($file['offset'] + 4 + $chunk['start']);
			$chunk_data = $this->_read($chunk['size']);
			
			if (
				!($file_data = gzinflate($chunk_data)) ||
				fwrite($fd, $file_data) == false
			) {
				fclose($fd);
				unlink($target_temp);
				throw new SGBExtractorException("Failed to extracting file $target");
			}

			$info->chunk++;
			$this->_updateInfo($info);
		}

		fflush($fd);
		fclose($fd);

		if(!@rename($target_temp, $target)) {
			unlink($target_temp);
			throw new SGBExtractorException("Error when trying to move $target_temp to $target");
		}
	}

	/**
	 * @return void
	 */
	public function extract(?callable $callback=null):void {
		
		// read the extra data just to move the FD pointer to place, we don't really need this data
		$this->_getExtra();

		$files = $this->_fetchFilesList();
		
		$info = $this->_getInfo();

		if($info->file > 0) $files = array_splice($files, $info->file);
		
		foreach($files as $file) {
			try {
				$this->_extractFile($file);
			} catch(SGBExtractorException $e) {
				$this->getLogController()->logError("Failed extracting file '{$file['filename']}', skipping to next file. Error: " . $e->getMessage());
			}

			$info->file++;
			$info->chunk = 0;
			$this->_updateInfo($info);
			
			if($callback) $callback();
		}
		
		@unlink($this->_info_file);
		@unlink($this->_info_file . '.tmp');
	}

	/**
	 * @param string $data
	 * @param int $size
	 *
	 * @return string
	 */
	private static function _unpackLittleEndian(string $data, int $size):string {
		return unpack('H' . ($size * 2), strrev($data))[1];
	}

	private function _getInfo() {
		
		if(file_exists($this->_info_file)) {
			$data = file_get_contents($this->_info_file);
			if($data && ($output = json_decode($data))) return $output;
		}
		
		$output = new \stdClass();
		$output->file = 0;
		$output->chunk = 0;
		return $output;
	}

	private function _updateInfo($data) {
		$tempFile = $this->_info_file . '.tmp';
		$jsonData = json_encode($data);
		if ($jsonData === false)
			throw new SGBExtractorException("Failed to encode compress information");

		if (file_put_contents($tempFile, $jsonData) === false)
			throw new SGBExtractorException("Failed to write compress information to temporary file");

		if (!rename($tempFile, $this->_info_file))
			throw new SGBExtractorException("Failed to atomically write compress information");
	}
}