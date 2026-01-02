<?php

namespace JetBackup\Downloader;

use JetBackup\Exception\DownloaderException;
use JetBackup\Factory;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Downloader {
	
	const DEFAULT_CHUNK = 8192; // 8k
	
	private ?string $_filename;
	private string $_source;

	/**
	 * @param string $source
	 * @param string|null $filename
	 */
	public function __construct(string $source, ?string $filename=null) {
		$this->_source   = $source;
		$this->_filename = $filename;
	}

	/**
	 * @return \stdClass
	 */
	private static function _getRange($filesize):object {
		$output = new \stdClass();
		$output->start = 0;
		$output->end = $filesize-1;
		
		if (
			!isset($_SERVER['HTTP_RANGE']) || 
			!preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)
		) return $output;
		
		$output->start = (int) $matches[1];
		if (!empty($matches[2])) $output->end = (int) $matches[2];
		
		return $output;
	}

	/**
	 * @param int $chunk
	 *
	 * @return void
	 * @throws DownloaderException
	 */
	public function download(int $chunk=self::DEFAULT_CHUNK):void {

		if( !$this->_source || !file_exists($this->_source) || is_dir($this->_source)) throw new DownloaderException('The provided download file does not exists');
		if (!str_starts_with(trim($this->_source), trim(Factory::getLocations()->getDataDir()))) throw new DownloaderException('Invalid download source path');
		if(!($fd = fopen($this->_source, 'rb'))) throw new DownloaderException('Unable to open download file');
		if(!$this->_filename) $this->_filename = basename($this->_source);

		$filesize = filesize($this->_source);
		if ($filesize === false || $filesize < 0) throw new DownloaderException('Unable to stat download file');

		$range = self::_getRange($filesize);

		// gzip compression may corrupt binary data
		if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 'Off');
		while (ob_get_level()) @ob_end_clean();

		header("Content-Type: application/octet-stream");
		header('Content-Disposition: attachment; filename="' . $this->_filename . '"; filename*=UTF-8\'\'' . rawurlencode($this->_filename));
		header("Accept-Ranges: bytes");
		header("Content-Length: " . ($range->end - $range->start + 1));
		header("Content-Range: bytes $range->start-$range->end/".$filesize);

		if($range->start > 0) fseek($fd, $range->start);

		while (!feof($fd) && ($pos = ftell($fd)) <= $range->end) {
			echo fread($fd, min($chunk, $range->end - $pos + 1));
			flush();
		}

		fclose($fd);
		exit;
		}
	}