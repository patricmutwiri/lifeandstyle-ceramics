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
namespace JetBackup\Archive;

use JetBackup\Archive\File\File;
use JetBackup\Archive\File\FileInfo;
use JetBackup\Archive\File\GZFile;
use JetBackup\Archive\File\RegFile;
use JetBackup\Archive\Header\Header;
use JetBackup\Archive\Header\Sparse\Sparse;
use JetBackup\Archive\Header\Sparse\SparseRegion;
use JetBackup\Archive\Scan\Scan;
use JetBackup\DirIterator\DirIteratorFile;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\VanishedException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Queue\QueueItem;

class Archive {

	const ARCHIVE_TEMP_DATA_FILE = '.tar_working_file';
	const ARCHIVE_EXT = '.tar';

	const OPT_COMPRESSED        = 1<<1;
	const OPT_IGNORE_VANISHED   = 1<<2;
	const OPT_SPARSE            = 1<<3;
	const OPT_DEBUG             = 1<<4;

	const LOG_TYPE_INFO         = 1;
	const LOG_TYPE_WARRING      = 2;
	const LOG_TYPE_ERROR        = 3;
	const LOG_TYPE_DEBUG        = 4;

	protected const DEFAULT_COMPRESSION_LEVEL = 9;

	protected const FD_TYPE_READ = 1;
	protected const FD_TYPE_WRITE = 2;

	const BLOCK_SIZE = 512;
	const NULL_CHAR = "\0";
	const TRUE_CHAR = "\x01";

	protected $_filename;
	/** @var callable */
	private $_exclude_callback;
	/** @var callable */
	private $_progress_bar_callback;
	/** @var callable */
	private $_logger;
	/** @var callable */
	private $_extract_file_callback;
	/** @var callable */
	private $_create_file_callback;
	private $_opt;
	private $_compress_level;
	protected ?File $_file = null;
	protected $_fd_type;
	private bool $_append;
	private LogController $_logController;
	/**
	 * @var mixed|string|null
	 */
	private $_workspace;

	/**
	 * @param string $archive_filename
	 * @param bool $append
	 * @param int $options
	 * @param int $compress_level
	 * @param null $workspace
	 *
	 * @throws ArchiveException
	 */
	public function __construct( string $archive_filename, bool $append = false, int $options=0, int $compress_level=self::DEFAULT_COMPRESSION_LEVEL, $workspace=null) {
		$this->_filename = $archive_filename;
		$this->_compress_level = $compress_level;
		$this->_append = $append;
		$this->_logController = new LogController();

		if (!$workspace) $workspace = Factory::getLocations()->getTempDir();
		$this->_workspace = $workspace;

		$this->_setOptions($options);

		if ($this->isCompressed()) {
			if(!function_exists('gzopen'))
				throw new ArchiveException('No gzip support available');

			if ($this->_compress_level < 1 || $this->_compress_level > 9)
				throw new ArchiveException('Compression level should be between 1 and 9');
		}
	}

	public static function isTar($path) : bool {
		return str_ends_with($path, Archive::ARCHIVE_EXT);
	}

	public static function isGzCompressed($path): bool {
		return str_ends_with($path, '.gz');
	}


	/**
	 * @param bool $append
	 *
	 * @return void
	 */
	public function setAppend(bool $append) { $this->_append = $append; }

	/**
	 * @return bool
	 */
	public function isAppend():bool { return $this->_append; }
	
	/**
	 * @param callable $logger
	 *
	 * @return void
	 */
	public function setLogController(LogController $logController): void { 
		$this->_logController = $logController; 
	}
	
	/**
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function setProgressBarCallback(callable $callback): void { 
		$this->_progress_bar_callback = $callback; 
	}

	/**
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function setExcludeCallback(callable $callback): void {
		$this->_exclude_callback = $callback;
	}

	/**
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function setExtractFileCallback(callable $callback): void {
		$this->_extract_file_callback = $callback;
	}

	/**
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function setCreateFileCallback(callable $callback): void {
		$this->_create_file_callback = $callback;
	}
	
	/**
	 * @param int $file
	 *
	 * @return void
	 */
	protected function increaseProgressBar(int $file=1):void {
		if(!$this->_progress_bar_callback) return;
		call_user_func($this->_progress_bar_callback, $file);
	}

	/**
	 * @param string $message
	 * @param string $subMessage
	 * @param int $totalSubItems
	 * @param int $currentSubItems
	 *
	 * @return void
	 */
	protected function extractFileCallback(string $message, string $subMessage, int $totalSubItems, int  $currentSubItems) {
		if(!$this->_extract_file_callback) return;
		call_user_func($this->_extract_file_callback, $message, $subMessage, $totalSubItems, $currentSubItems);
	}

	/**
	 * @param Header $header
	 *
	 * @return void
	 */
	protected function createFileCallback(Header $header) {
		if(!$this->_create_file_callback) return;
		call_user_func($this->_create_file_callback, $header);
	}

	private function _setOptions(int $options) { $this->_opt = $options; }
	private function _getOptions(): int { return $this->_opt; }
	private function _addOption(int $option) { $this->_setOptions($this->_getOptions() | $option); }
	private function _isOption(int $option): bool { return !!($this->_getOptions() & $option); }
	
	/**
	 * @return bool
	 */
	protected function isIgnoreVanished(): bool { return $this->_isOption(self::OPT_IGNORE_VANISHED); }

	/**
	 * @return bool
	 */
	protected function isCompressed(): bool { return $this->_isOption(self::OPT_COMPRESSED); }

	/**
	 * @return bool
	 */
	protected function isSparse(): bool { return $this->_isOption(self::OPT_SPARSE); }

	/**
	 * @return bool
	 */
	protected function isDebug(): bool { return $this->_isOption(self::OPT_DEBUG); }
	
	/**
	 * @param int $type
	 *
	 * @return void
	 * @throws ArchiveException
	 */
	protected function _checkFD($type): void {
		if(!$this->_file || $this->_fd_type == $type) return;
		switch($this->_fd_type) {
			case self::FD_TYPE_READ: throw new ArchiveException("file is already open reading. you can't write to this file");
			case self::FD_TYPE_WRITE: throw new ArchiveException("file is already open writing. you can't read from this file");
		}
	}

	/**
	 * @param int $type
	 *
	 * @return void
	 * @throws ArchiveException
	 */
	protected function _createFd($type): void {
		$this->_checkFD($type);
		if($this->_file) return;

		if(!$this->isAppend() && $type == self::FD_TYPE_WRITE && DirIteratorFile::safe_filesize($this->_filename) && DirIteratorFile::safe_filesize($this->_filename) > 0)
			throw new ArchiveException("The archive file isn't empty, you can't open it for writing");

		$this->_fd_type = $type;

		$file_info = pathinfo($this->_filename);

		if($this->isAppend()) {

			$this->_logController->logDebug('Tar _createFd: Append TRUE');

			if($type == self::FD_TYPE_WRITE) {
				if(!file_exists($this->_filename)) touch($this->_filename);
				$this->_file = new RegFile($this->_filename, 'rb+');
				$_file_size = DirIteratorFile::safe_filesize($this->_filename);
				$this->_logController->logDebug('Tar _createFd: FD_TYPE_WRITE -> Reset position based on size, seek -> ' . $_file_size);
				$this->_file->seek($_file_size);
			} else {
				$this->_logController->logDebug('Tar _createFd: NEW FD Opened');
				$this->_file = new RegFile($this->_filename, 'rb');
			}

		} elseif ($file_info['extension'] === 'gz') {
			$this->_logController->logDebug('Tar _createFd: [NOT Append] Extension GZ found, opening new FD');
			$this->_file = new GZFile($this->_filename, $type == self::FD_TYPE_WRITE ? 'wb' : 'rb');
		} else {
			$this->_logController->logDebug('Tar _createFd: [NOT Append | Not GZ] Opening new FD');
			$this->_file = new RegFile($this->_filename, $type == self::FD_TYPE_WRITE ? 'wb' : 'rb');
		}
	}

	private function _commitInfoFile($info) {
		$lockfile = $this->_getInfoFile();
		$swap_file = $lockfile . '.swap';
		$resolvedPath = realpath($swap_file);

		//$this->_logController->logDebug("Resolved swap file path: $resolvedPath (length: " . strlen($resolvedPath) . ")");
		//$this->_logController->logDebug("Commit Info File: Lock file path is $lockfile");
		//$this->_logController->logDebug("Commit Info File: Swap file path is $swap_file");

		// Check if directory is writable
		if (!is_writable(dirname($swap_file))) {
			throw new ArchiveException("Directory is not writable: " . dirname($swap_file));
		}

		// Check if swap file exists and is writable
		if (file_exists($swap_file) && !is_writable($swap_file)) {
			throw new ArchiveException("Swap file is not writable: " . $swap_file);
		}

		// Open the file for writing
		$fp = fopen($swap_file, 'c');
		if ($fp === false) {
			throw new ArchiveException('Could not open info swap file for writing');
		}
		//$this->_logController->logDebug("Commit Info File: Swap file opened successfully");

		// Lock the file
		if (!flock($fp, LOCK_EX)) {
			fclose($fp);
			throw new ArchiveException('Could not lock info swap file');
		}
		//$this->_logController->logDebug("Commit Info File: Swap file locked successfully");

		// Encode the data to JSON
		$json_data = json_encode($info);
		if ($json_data === false) {
			$json_error = json_last_error_msg();
			//$this->_logController->logDebug("Commit Info File: Failed to encode JSON. Error: $json_error");
			flock($fp, LOCK_UN);
			fclose($fp);
			throw new ArchiveException("Failed to encode JSON data: $json_error");
		}

		// Write the JSON data to the file
		$bytes_written = fwrite($fp, $json_data);
		if ($bytes_written === false || $bytes_written < strlen($json_data)) {
			flock($fp, LOCK_UN);
			fclose($fp);
			throw new ArchiveException("Could not write full JSON data to the swap file: $swap_file");
		}
		//$this->_logController->logDebug("Commit Info File: Data written to swap file successfully");

		// Flush and unlock the file
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		//$this->_logController->logDebug("Commit Info File: Swap file flushed, unlocked, and closed");

		// Remove the old info file
		if (file_exists($lockfile)) {
			if (!unlink($lockfile)) {
				throw new ArchiveException('Could not remove old info file');
			}
			//$this->_logController->logDebug("Commit Info File: Old info file removed successfully");
		}

		// Rename the swap file to the main file
		if (!rename($swap_file, $lockfile)) {
			throw new ArchiveException('Could not rename info swap file to main file');
		}
		//$this->_logController->logDebug("Commit Info File: Swap file renamed to main file successfully");
	}


	private function _getInfo() {
		$lockfile = $this->_getInfoFile();
		$swap_file = $lockfile . '.swap';

		$data = null;

		if (file_exists($lockfile)) {
			$fp = fopen($lockfile, 'r');
			if ($fp !== false) {
				if (flock($fp, LOCK_SH)) {
					$data = json_decode(file_get_contents($lockfile));
					flock($fp, LOCK_UN);
				}
				fclose($fp);
			}
		}

		if (!$data && file_exists($swap_file)) {
			$fp = fopen($swap_file, 'r');
			if ($fp !== false) {
				if (flock($fp, LOCK_SH)) {
					$data = json_decode(file_get_contents($swap_file));
					flock($fp, LOCK_UN);
				}
				fclose($fp);
			}
			if ($data && !rename($swap_file, $lockfile)) {
				throw new ArchiveException('Could not copy info swap file to main file');
			}
		}

		if (file_exists($swap_file)) {
			unlink($swap_file);
		}
		
		return $data;
	}


	private function _getInfoFile(): string {
		return $this->_workspace . JetBackup::SEP . self::ARCHIVE_TEMP_DATA_FILE . '_' . md5($this->_filename) . '.tmp';
	}

	/**
	 * @throws ArchiveException
	 */
	private function _closeInfoFile() {
		$lockfile = $this->_getInfoFile();
		$swap_file = $lockfile . '.swap';

		if (file_exists($lockfile) && !unlink($lockfile)) {
			throw new ArchiveException('Could not remove info file');
		}
		if (file_exists($swap_file) && !unlink($swap_file)) {
			throw new ArchiveException('Could not remove info swap file');
		}
	}

	public function appendFileChunked(DirIteratorFile $fileInstance, $as='', ?callable $callback=null, $chunkSize = 1024) {
		$file = new \JetBackup\Filesystem\File($fileInstance->getName());
		$this->_createFd(self::FD_TYPE_WRITE);

		// Load info if file exists
		$info = $this->_getInfo();
		
		// If no info file found, or it's not the same file, create new info file
		if (!$info || !isset($info->file->name) || $info->file->name != $file->path()) {
			//$this->_logController->logDebug('Creating new info file or resetting info due to file mismatch');

			if (!$info) {
				$this->_logController->logDebug('Tar appendFileChunked: No Chunk info data found (Adding new file)');
				$archive_position = $fileInstance->getArchivePosition();

				if ($archive_position !== null) {
					$this->_logController->logDebug('Tar appendFileChunked: Moving seek to pointer -> ' . $archive_position);
					$this->_file->seek($archive_position);
				}
			}

			if ($info && isset($info->file->name) && $info->file->name != $file->path()) {
				$this->_logController->logDebug('Tar appendFileChunked: Restarting existing file with valid info file');
				$this->_logController->logDebug('Tar appendFileChunked: Moving seek to pointer -> ' . $info->position->start);
				$this->_file->seek($info->position->start);
			}

			$info = new \stdClass();
			$info->file = new \stdClass();
			$info->file->name = $file->path();
			$info->file->size = $file->size();
			$info->file->mtime = $file->mtime();
			$info->file->changed = 0;
			$info->file->read = 0;
			$info->position = new \stdClass();
			$info->position->start = $this->_file->tell();
			$info->position->current = $info->position->start;
			$info->headers = false;

			$this->_commitInfoFile($info);
		}

		$this->_logController->logMessage('Tar appendFileChunked position: '. $this->_file->tell());

		if (filesize($this->_filename) > 0 && $this->_file->tell() == 0) {
			$msg = 'Error: Tar position is zero with active tar file';
			$this->_logController->logMessage($msg);
			throw new ArchiveException($msg);
		}


		// If file has changed while working on it, start from the beginning
		if ($info->file->size != $file->size() || $info->file->mtime != $file->mtime()) {
			$this->_logController->logDebug('Tar appendFileChunked: File ' . basename($file->path()) . ' changed, resetting positions');

			// If this file changes more than 3 times, throw exception
			if ($info->file->changed >= 3) throw new ArchiveException("Tar appendFileChunked: File changed too many times");

			$this->_file->seek($info->position->start);
			$info->file->size = $file->size();
			$info->file->mtime = $file->mtime();
			$info->file->changed++;
			$info->file->read = 0;
			$info->position->current = $info->position->start;
			$info->headers = false;

			$this->_commitInfoFile($info);
		}

		$this->_file->seek($info->position->current);

		if (!$info->headers) {
			$header = $this->writeHeader(FileInfo::fromPath($file->path(), $as));

			$info->position->current = $this->_file->tell();
			$info->headers = true;

			$this->_commitInfoFile($info);
			$this->createFileCallback($header);
		}

		// Not a file, nothing to do
		if (!($file->isFile() && !$file->isLink())) {
			// Reading is done, remove info file
			$this->_closeInfoFile();
			return;
		}

		$fd = fopen($file->path(), 'r');
		if (!$fd) throw new ArchiveException('Tar appendFileChunked: Could not open file for reading: ' . $file->path());

		if ($info->file->read) fseek($fd, $info->file->read);

		while (!feof($fd)) {
			// Check if we need to stop
			if ($callback && call_user_func($callback)) {
				fclose($fd);
				return;
			}

			$read = $this->writeData(fread($fd, $chunkSize));
			$info->file->read += $read;
			$info->position->current += $read;

			$this->_commitInfoFile($info);
		}

		fclose($fd);

		// Reading is done, remove info file
		$this->_closeInfoFile();
	}


	/**
	 * @param $data
	 *
	 * @return false|int
	 * @throws ArchiveException
	 */
	protected function _write($data) {
		$this->_createFd(self::FD_TYPE_WRITE);
		return $this->_file->write($data);
	}

	/**
	 * @param $length
	 *
	 * @return false|string
	 * @throws ArchiveException
	 */
	protected function _read($length=self::BLOCK_SIZE) {
		$this->_createFd(self::FD_TYPE_READ);

		// we must read in block size chunks
		if($length != self::BLOCK_SIZE) $length = ceil($length / self::BLOCK_SIZE) * self::BLOCK_SIZE;
		return $this->_file->read($length);
	}

	protected function _eof() {
		$this->_createFd(self::FD_TYPE_READ);
		return $this->_file->eof();
	}
	/**
	 * @return void
	 */
	protected function _close(): void {

		if($this->_file) {
			$this->_file->close();
			$this->_file = null;
		}

	}

	/**
	 * @param string $data
	 *
	 * @return int
	 * @throws ArchiveException
	 */
	public function writeData($data): int {
		$output = 0;
		for ($i = 0; $i < strlen($data); $i += self::BLOCK_SIZE)
			$output += $this->_write(pack("a" . self::BLOCK_SIZE, substr($data, $i, self::BLOCK_SIZE)));
		return $output;
	}

	/**
	 * @return void
	 * @throws ArchiveException
	 */
	public function save(): void {

		$this->writeData('');
		$this->writeData('');
		$this->_file->flush();
		if($this->_file->tell() < DirIteratorFile::safe_filesize($this->_filename)) {
			$this->_file->truncate($this->_file->tell());
		}
		
		$this->_close();

	}

	/**
	 * @param FileInfo $file
	 * @param Sparse|null $sparse
	 *
	 * @return Header
	 * @throws ArchiveException
	 */
	public function writeHeader(FileInfo $file, ?Sparse $sparse=null): Header {
	
		$header = new Header();
		$header->setTypeFlag($file->getFlagType());
		$header->setFilename($file->getPath());
		$header->setSize($file->getSize(), false);
		$header->setMtime($file->getMtime(), false);
		$header->setMode(0777 & $file->getMode(), false);
		$header->setUid($file->getUid(), false);
		$header->setGid($file->getGid(), false);
		$header->setUname($file->getOwner());
		$header->setGname($file->getGroup());
		$header->setLinkName($file->getLink());
		if($file->getDevMajor()) $header->setDevMajor($file->getDevMajor(), false);
		if($file->getDevMinor()) $header->setDevMinor($file->getDevMinor(), false);
		$header->setMagic("ustar");
		$header->setSparse($sparse);

		if (strlen($file->getPath()) > Header::LENGTH_FILENAME) {
			$long_header = new Header();
			$long_header->setTypeFlag(Header::GNUTYPE_LONGNAME);
			$long_header->setFilename('././@LongLink');
			$long_header->setSize(strlen($file->getPath()), false);
			$long_header->setMagic("ustar");

			$this->_write($long_header->pack());
			$this->writeData($file->getPath());
			$header->setFilename(substr($file->getPath(), 0, Header::LENGTH_FILENAME));
		}

		$header->buildPrefix();
		$this->_write($header->pack());

		if($header->getTypeFlag() == Header::GNUTYPE_SPARSE && $header->getSparse())
			$header->getSparse()->buildExtended(function($data) { $this->_write($data); });
		
		return $header;
	}
	
	/**
	 * @param string $path
	 * @param bool $is_dir
	 *
	 * @return bool
	 */	
	private function _isExcluded(string $path, bool $is_dir=false): bool {
		if(!$this->_exclude_callback) return false;
		return call_user_func($this->_exclude_callback, $path, $is_dir);
	}

	/**
	 * @param string $path
	 * @param FileInfo $info
	 *
	 * @return void
	 * @throws ArchiveException
	 */
	private function _appendFile(string $path, FileInfo $info):void {
		if($info->getFlagType() != Header::REGTYPE) {
			$header = $this->writeHeader($info);
			$this->createFileCallback($header);
			return;			
		}
		
		if($this->isSparse() && $info->getSize() >= self::BLOCK_SIZE && $info->getSparseness() < 1) {
			$info->setFlagType(Header::GNUTYPE_SPARSE);
			$sparse = new Sparse();

			$sparse->setRealSize($info->getSize(), false);

			$file = new RegFile($path, 'rb');
			
			$info->setSize(self::_buildSparse($file, $sparse));

			$header = $this->writeHeader($info, $sparse);

			// write the data
			foreach($sparse->getRegions() as $region) {
				$offset = $region->getOffset(false);
				$numbytes = $region->getNumbytes(false);

				$file->seek($offset);
				if($numbytes) {
					$chunks = $numbytes / self::BLOCK_SIZE;
					for($i = 0; $i < $chunks; $i++) {
						$read = $file->read(self::BLOCK_SIZE);
						$this->writeData($read);
					}
				}
			}

			$file->close();
		} else {
			$header = $this->writeHeader($info);

			$file = new RegFile($path, 'rb');
			while (!$file->eof()) $this->writeData($file->read(self::BLOCK_SIZE));
			$file->close();
		}

		$this->createFileCallback($header);
	}
	
	/**
	 * @param $path
	 * @param FileInfo|null $info
	 *
	 * @return void
	 * @throws ArchiveException
	 */
	public function appendFile($path, ?FileInfo $info=null): void {
		if(!file_exists($path)) throw new ArchiveException("Failed appending file '$path'. File not exists");

		$this->increaseProgressBar();

		if(!$info) $info = FileInfo::fromPath($path);
		else $info->setSize(filesize($path));

		if($info->isSocket()) {
			$this->_logController->logMessage("Socket file '$path' ignored");
			return;
		}

		$this->_appendFile($path, $info);
	}

	/**
	 * @param FileInfo $info
	 * @param string|null $data
	 *
	 * @return void
	 * @throws ArchiveException
	 */
	public function appendData(FileInfo $info, ?string $data=null): void {
		$this->increaseProgressBar();
		$info->setSize($data === null ? 0 : strlen($data));
		$header = $this->writeHeader($info);
		if($data !== null) $this->writeData($data);

		$this->createFileCallback($header);
	}

	/**
	 * @param string $source
	 * @param string|bool $replace_source true or empty string will remove the source from the path, any other string will replace the source with the string  
	 *
	 * @return void
	 * @throws ArchiveException
	 * @throws VanishedException
	 */
	public function appendDirectory($source, $replace_source=false): void {

		if(!file_exists($source) || !is_dir($source)) throw new ArchiveException("The provided directory not exists or not a directory");

		if($this->_progress_bar_callback) {
			// get total
			$total = 0;
			$queue = [[opendir($source), $source]];

			while($queue) {
				$dir = array_pop($queue);
				while(($entry = readdir($dir[0]))) {
					if($entry == '.' || $entry == '..') continue;
					
					$file = new \JetBackup\Filesystem\File($dir[1] . JetBackup::SEP . $entry);
					
					$clean_path = JetBackup::SEP . trim(substr($file->path(), strlen($source)), JetBackup::SEP);
					if($this->_isExcluded($clean_path, $file->isDir())) continue;

					if($file->isDir() && !$file->isLink()) {
						$queue[] = $dir;
						$queue[] = [opendir($file->path()), $file->path()];
						continue 2;
					}

					// count files
					$total++;
				}

				// count directories
				$total++;
			}

			$this->increaseProgressBar($total);
		}

		$queue = [new Scan($source, $source)];

		while($queue) {
			$dir = array_pop($queue);

			while($entry = $dir->next()) {

				$path = $entry->getFullPath();

				if($entry->isSocket()) {
					$this->_logController->logMessage("Socket file '$path' ignored");
					continue;
				}

				// Path is excluded, skip
				/**
				 * We changed the way we are including and excluding files
				 * Previously we had to scan the entire disk to determine if the file is included or excluded
				 * We change it to use the same algorithm as rsync, The entire path must be included in order to be backed up
				 * e.g. if we want to backup "/a/b/c/d" we must provide the following include list "/a", "/a/b", "/a/b/c", "/a/b/c/d/***"
				 */
				$clean_path = JetBackup::SEP . trim($entry->getCleanPath(), JetBackup::SEP);
				if($this->_isExcluded($clean_path, $entry->isDir())) continue;

				if($entry->isDir()) {

					try {
						$newDir = new Scan($path, $source);
					} catch(VanishedException $e) {
						// This dir has vanished, do nothing and continue backup up

						$this->_logController->logMessage($e->getMessage(), self::LOG_TYPE_DEBUG);
						if(!$this->isIgnoreVanished()) throw $e;
						continue;
					}

					$newDir->setParent($dir);

					$queue[] = $dir;
					$queue[] = $newDir;

					$path = $entry->getFullPath();
					if(is_string($replace_source) && $replace_source) $path = rtrim($replace_source, JetBackup::SEP) . JetBackup::SEP . $entry->getCleanPath();
					else if((is_string($replace_source) && !$replace_source) || $replace_source) $path = $entry->getCleanPath();

					$fileinfo = FileInfo::fromPath($entry->getFullPath(), $path);
					
					$header = $this->writeHeader($fileinfo);

					$this->createFileCallback($header);

					continue 2;
				}

				$dir->addSize($entry->getSize());

				$path = $entry->getFullPath();
				if(is_string($replace_source) && $replace_source) $path = rtrim($replace_source, JetBackup::SEP) . JetBackup::SEP . $entry->getCleanPath();
				else if((is_string($replace_source) && !$replace_source) || $replace_source) $path = $entry->getCleanPath();

				$fileinfo = FileInfo::fromPath($entry->getFullPath(), $path);
				$this->_appendFile($entry->getFullPath(), $fileinfo);
			}
		}
	}

	private static function _buildSparse(File $file, Sparse $sparse) {

		$size = 0;
		$offset = 0;
		$numbytes = 0;
		while (!$file->eof()) {
			$data = $file->read(self::BLOCK_SIZE);
			// check if all are nulls
			if(self::_isNull($data)) {
				if($numbytes) {
					$region = new SparseRegion();
					$region->setOffset($offset, false);
					$region->setNumbytes($numbytes, false);
					$sparse->addRegion($region);
					$offset += $numbytes;
				}

				$numbytes = 0;
				$offset += self::BLOCK_SIZE;
			} else {
				$data_len = strlen($data);
				$numbytes += $data_len;
				$size += $data_len;
			}
		}

		$region = new SparseRegion();
		$region->setOffset($offset, false);
		$region->setNumbytes($numbytes, false);
		$sparse->addRegion($region);

		return $size;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	private static function _isNull(string $data): bool {
		for($i = 0; $i < strlen($data); $i++) if($data[$i] != self::NULL_CHAR) return false;
		return true;
	}
	
	/**
	 * @param string $destination
	 *
	 * @return void
	 * @throws ArchiveException
	 */
	public function extract( string $destination): void {
		$this->_logController->logMessage("[tar] Extracting archive file '$destination'");
		$destination = rtrim($destination, JetBackup::SEP);
		if (!is_dir($destination)) throw new ArchiveException("Destination directory not exists '$destination'");

		// Load progress info if available
		$info = $this->_getInfo() ?? (object) [
			'currentFile' => null,
			'currentOffset' => 0,
			'totalBytesExtracted' => 0
		];

		$longname = null;
		$corrupted_header = false;

		// We found out that is some cases (e.g. when we have bad gz file) the feof will return true and the read will return false
		// In that case we are in infinite loop.
		while(!$this->_eof()) {
			if(($data = $this->_read()) === false) throw new ArchiveException("The file provided is corrupted");
			if(trim($data) === '') continue;

			try {
				$header = Header::parse($data, function() { return $this->_read(); });
				$corrupted_header = false;
			} catch (ArchiveException $e) {
				if(!$corrupted_header) $this->_logController->logError("Invalid header detected. Skipping corrupted data block.");
				$corrupted_header = true;
				continue; // Skip this header and move to the next
			}


			if($header->getTypeFlag() == Header::GNUTYPE_LONGNAME) {
				$longname = $this->_read($header->getSize(false));
				continue;
			}

			if($longname) {
				$header->setFilename($longname);
				$longname = null;
			}

			if($this->isDebug()) self::printHeader($header);

			// Resume Logic Validation: Skip files that are already processed
			$filename = trim($header->getFilename());
			$info->currentFile = $filename;

			$size = $header->getSize(false);
			$mode = $header->getMode(false);
			$mtime = $header->getMtime(false);
			$uname = trim($header->getUname()) ?: $header->getUid(false);
			$gname = trim($header->getGname()) ?: $header->getGid(false);
			$link = trim($header->getLinkName());
			$devmajor = $header->getDevMajor(false);
			$devminor = $header->getDevMinor(false);

			if($this->_isExcluded($filename)) {
				// read and drop all file data
				if ($size > 0) $this->_read($size);
				continue;
			}

            // Replace Windows backslashes with standard forward slashes
            $filename = Util::normalizePath($filename);
			$output = $destination. JetBackup::SEP .$filename;

			if (file_exists($output) && is_readable($output) && DirIteratorFile::safe_filesize($output) == $size) {
				if ($size > 0) $this->_read($size);
				continue;
			}

			$directory = $header->getTypeFlag() == Header::DIRTYPE ? $output : dirname($output);

			if(!file_exists($directory) && !@mkdir($directory, 0777, true)) {
				$this->_logController->logError("Failed creating directory '$directory'");
				continue;
			}

			$this->_logController->logDebug("Processing case: {$header->getTypeFlag()} for file: $filename");

			switch($header->getTypeFlag()) {


				case Header::GNUTYPE_SPARSE:
					try {
						$file = new RegFile($output, 'ab'); // Open in append mode for resumption
						$regions = $header->getSparse()->getRegions();

						foreach ($regions as $index => $region) {
							// Skip already processed regions
							if (isset($info->sparseRegion) && $info->sparseRegion > $index) {
								continue;
							}

							$offset = $region->getOffset(false);
							$numbytes = $region->getNumbytes(false);

							if (sizeof($regions) == 1 && !$numbytes) {
								$file->truncate($offset);
								break;
							}

							if ($numbytes > 0) {
								$file->seek($offset);
								$file->write($this->_read($numbytes), $numbytes);
								$info->sparseRegion = $index; // Save progress
							} else {
								$file->seek($offset-1);
								$file->write(self::NULL_CHAR);
							}
						}

						$file->close();
					} catch (ArchiveException $e) {
						$this->_logController->logError("Failed creating sparse file '$output'. Error: " . $e->getMessage());
						continue 2;
					}
				break;


				case Header::REGTYPE:
				case Header::AREGTYPE:
					try {

						if (file_exists($output) && (!is_readable($output) || !is_writable($output))) {
							$this->_logController->logError("Output file '$output' has bad permissions or is not accessible. Skipping.");
							$this->_read($size); // Skip the data block
							continue 2;
						}

						$file = new RegFile($output, 'ab');

						if($size) {

							// Resume from the last offset
							$file->seek($info->currentOffset);
							$remaining = $size - $info->currentOffset;

							if ($remaining <= 0) {
								$info->currentFile = null;
								$info->currentOffset = 0;
								$this->_commitInfoFile($info);
								continue 2;
							}

							for ($i = 0; $i < floor($remaining / self::BLOCK_SIZE); $i++) {
								$file->write($this->_read(), self::BLOCK_SIZE);
								$info->currentOffset += self::BLOCK_SIZE;
							}

							if (($remaining % self::BLOCK_SIZE) != 0) {
								$file->write($this->_read(), $remaining % self::BLOCK_SIZE);
								$info->currentOffset += $remaining % self::BLOCK_SIZE;
							}
						}

						$file->close();
						// File fully processed, reset current offset
						$info->currentOffset = 0;
						$info->currentFile = null;
						$info->totalBytesExtracted += $size;
						$this->_commitInfoFile($info);
					} catch(ArchiveException $e) {
						$this->_logController->logError("Failed creating file '$output'. Error: " . $e->getMessage());
						$this->_read($size); // Skip the data block
						continue 2;
					}
				break;

				case Header::SYMTYPE:
					try {
						if (!function_exists('symlink') || !@symlink($link, $output)) {
							$this->_logController->logError("Failed creating symlink '$output' to target '$link'");
						} else {
							$this->_logController->logMessage("Created symlink '$output' -> '$link'");
						}
						$info->currentFile = null;
						$info->currentOffset = 0;
						$this->_commitInfoFile($info);
					} catch (ArchiveException $e) {
						$this->_logController->logError("Exception while creating symlink '$output': " . $e->getMessage());
					}
					break; // Exit the switch and proceed with the next iteration of the loop


				case Header::CHRTYPE:
					if(!posix_mknod($output, POSIX_S_IFCHR, $devmajor, $devminor)) {
						$this->_logController->logError("Failed creating character special file '$output'");
						continue 2;
					}
				break;

				case Header::BLKTYPE:
					if(!posix_mknod($output, POSIX_S_IFBLK, $devmajor, $devminor)) {
						$this->_logController->logError("Failed creating block special file '$output'");
						continue 2;
					}
				break;

				case Header::FIFOTYPE:
					if(!posix_mkfifo($output, $mode)) {
						$this->_logController->logError("Failed creating fifo file '$output'");
						continue 2;
					}
				break;

				case Header::DIRTYPE:
					try {
						// Ensure the directory exists
						if (!file_exists($output) && !@mkdir($output, 0755, true)) {
							$this->_logController->logError("Failed creating directory '$output'");
							break; // Log the error and skip
						}

						// Set ownership, permissions, and modification time
						if(function_exists('chown')) @chown($output, $uname);
						if(function_exists('chgrp')) @chgrp($output, $gname);
						if(function_exists('touch')) @touch($output, $mtime);
						if(function_exists('chmod')) @chmod($output, $mode);

						// Log successful processing of the directory
						$this->_logController->logDebug("Processed directory: $output");

						// Reset state for the next file
						$info->currentFile = null;
						$info->currentOffset = 0;
						$this->_commitInfoFile($info);
					} catch (ArchiveException $e) {
						$this->_logController->logError("Error processing directory '$output'. Error: " . $e->getMessage());
						continue 2; // Skip to the next item
					}
					break;

			}

			if($header->getTypeFlag() != Header::SYMTYPE) {
				if(function_exists('chown')) @chown($output, $uname);
				if(function_exists('chgrp')) @chgrp($output, $gname);
				if(function_exists('touch')) @touch($output, $mtime);
				if(function_exists('chmod')) @chmod($output, $mode);
			}

			$this->extractFileCallback('Archive Extract', $header->getFilename(),  DirIteratorFile::safe_filesize($this->_filename), $info->totalBytesExtracted);
		}

		// close FD
		$this->_close();
		$this->_closeInfoFile();

	}

	/**
	 * @throws ArchiveException
	 */
	public function list() {

		$longlink = '';
		
		$rows = [];
		while(!$this->_eof()) {
			$data = $this->_read();
			if(trim($data) === '') continue;

			$header = Header::parse($data, function() { return $this->_read(); });

			if($header->getTypeFlag() == Header::GNUTYPE_LONGNAME) {
				$size = $header->getSize(false);
				if($size > 0) $longlink = $this->_read($size);
				continue;
			}
			

			switch($header->getTypeFlag()) {
				default: $permissions = '-'; break;
				//case Header::LNKTYPE: $permissions = '-'; break;
				case Header::SYMTYPE: $permissions = 'l'; break;
				case Header::CHRTYPE: $permissions = 'c'; break;
				case Header::BLKTYPE: $permissions = 'b'; break;
				case Header::DIRTYPE: $permissions = 'd'; break;
				case Header::FIFOTYPE: $permissions = 'p'; break;
				case Header::CONTTYPE: $permissions = '?'; break;
				case Header::GNUTYPE_SPARSE: continue 2;
			}

			$digits = str_split(substr($header->getMode(), 4));
			$modes = ['---', '--x', '-w-', '-wx', 'r--', 'r-x', 'rw-', 'rwx'];
			$permissions .= $modes[$digits[0]] . $modes[$digits[1]] . $modes[$digits[2]];

			$rows[] = [
				'mode'      => $permissions,
				'uname'     => $header->getUname() ?: $header->getUid(false),
				'gname'     => $header->getGname() ?: $header->getGid(false),
				'size'      => $header->getDevMajor(false) ? $header->getDevMajor(false).','.$header->getDevMinor(false) : $header->getSize(false),
				'mtime'     => date("Y-m-d H:i", $header->getMtime(false)),
				'filename'  => ($longlink ?: $header->getFilename()) . ($header->getLinkName() ? ' -> ' . $header->getLinkName() : ''),
			];

			$longlink = '';
			
			if(
				$header->getTypeFlag() == Header::REGTYPE ||
				$header->getTypeFlag() == Header::AREGTYPE ||
				$header->getTypeFlag() == Header::GNUTYPE_SPARSE
			) {
				// skip data
				$size = $header->getSize(false);
				$chunk = 1024;
				$read = 0;
				if($size > 0) {
					while($read < $size) {
						$size_left = $size - $read;
						$read_size = ($size_left < $chunk)? $size_left: $chunk;
						$this->_read($read_size);
						$read += $read_size;
					}
				}
			}
		}

		// close FD
		$this->_close();

		$spaces = ['mode'=>0,'uname'=>0,'gname'=>0,'size'=>0,'mtime'=>0,'filename'=>0];
		
		foreach($rows as $row) {
			foreach($row as $key => $value) {
				if(isset($spaces[$key]) && $spaces[$key] > strlen($value)) continue;
				$spaces[$key] = strlen($value);
			}
		}

		foreach($rows as $row) {
			echo sprintf("%s %s %s %s %s", 
				str_pad($row['mode'], $spaces['mode']),
				str_pad($row['uname']. JetBackup::SEP .$row['gname'], $spaces['uname']+$spaces['gname']+1),
				//str_pad($row['gname'], $spaces['gname']),
				str_pad($row['size'], $spaces['size'], ' ', STR_PAD_LEFT),
				str_pad($row['mtime'], $spaces['mtime']), 
				$row['filename']
			) . PHP_EOL;
		}
	}
	
	public function dumpHeaders() {

		//$corrupted_header = false;

		while(!$this->_eof()) {
			$data = $this->_read();
			if(trim($data) === '') continue;

			try {
				$header = Header::parse($data, function() { return $this->_read(); }, true);
			} catch (ArchiveException $e) {
				continue;
			}
			
			if(
				$header->getTypeFlag() == Header::REGTYPE || 
				$header->getTypeFlag() == Header::AREGTYPE || 
				$header->getTypeFlag() == Header::GNUTYPE_SPARSE || 
				$header->getTypeFlag() == Header::GNUTYPE_LONGNAME
			) {
				// skip data
				$size = $header->getSize(false);
				$chunk = 1024;
				$read = 0;
				if($size > 0) {
					while($read < $size) {
						$size_left = $size - $read;
						$read_size = ($size_left < $chunk)? $size_left: $chunk;
						$this->_read($read_size);
						$read += $read_size;
					}
				}
			}
		}

		// close FD
		$this->_close();
	}
	
	public static function printHeader(Header $header) {

		echo "FILENAME: {$header->getFilename()}" . PHP_EOL;
		echo "PERMISSIONS: {$header->getMode()} -> {$header->getMode(false)}" . PHP_EOL;
		echo "UID: {$header->getUid()} -> {$header->getUid(false)}" . PHP_EOL;
		echo "GID: {$header->getGid()} -> {$header->getGid(false)}" . PHP_EOL;
		echo "SIZE: {$header->getSize()} -> {$header->getSize(false)}" . PHP_EOL;
		echo "MTIME: {$header->getMtime()} -> {$header->getMtime(false)}" . PHP_EOL;
		echo "CHECKSUM: {$header->getChecksum()}" . PHP_EOL;
		echo "TYPEFLAG: {$header->getTypeFlag()}" . PHP_EOL;
		if(trim($header->getLinkName())) echo "LINK: {$header->getLinkName()}" . PHP_EOL;
		if(trim($header->getUname())) echo "OWNER: {$header->getUname()}" . PHP_EOL;
		if(trim($header->getGname())) echo "GROUP: {$header->getGname()}" . PHP_EOL;
		if(trim($header->getDevMajor(false))) echo "DEV MAJOR: {$header->getDevMajor()} -> {$header->getDevMajor(false)}" . PHP_EOL;
		if(trim($header->getDevMinor(false))) echo "DEV MINOR: {$header->getDevMinor()} -> {$header->getDevMinor(false)}" . PHP_EOL;
		if(trim($header->getPrefix())) {

			$sparse = $header->getSparse();

			if($sparse) {
				echo "SPARSE DETAILS" . PHP_EOL;
				if($sparse->getAtime(false)) echo "- ATIME: {$sparse->getAtime()} -> {$sparse->getAtime(false)}" . PHP_EOL;
				if($sparse->getCtime(false)) echo "- CTIME: {$sparse->getCtime()} -> {$sparse->getCtime(false)}" . PHP_EOL;
				if($sparse->getOffset(false)) echo "- OFFSET: {$sparse->getOffset()} -> {$sparse->getOffset(false)}" . PHP_EOL;
				if($sparse->getLongName(false)) echo "- LONGNAME: {$sparse->getLongName()} -> {$sparse->getLongName(false)}" . PHP_EOL;
				if($sparse->getRealSize(false)) echo "- REALSIZE: {$sparse->getRealSize()} -> {$sparse->getRealSize(false)}" . PHP_EOL;

				foreach($sparse->getRegions() as $i => $region) {
					echo "- REGION $i OFFSET: {$region->getOffset()} -> {$region->getOffset(false)} | NUMBYTES: {$region->getNumbytes()} -> {$region->getNumbytes(false)}" . PHP_EOL;
				}

			} else {
				echo "PREFIX: {$header->getPrefix()}" . PHP_EOL;
			}
		}

		echo "***********************************" . PHP_EOL;

	}

	public function getFileFD(): ?File {
		$this->_createFd(self::FD_TYPE_WRITE);
		return $this->_file;
	}
}
