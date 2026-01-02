<?php

namespace JetBackup\DirIterator;

use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile;
use JetBackup\Entities\Util;
use JetBackup\Exception\DirIteratorException;
use JetBackup\Exception\DirIteratorFileVanishedException;
use JetBackup\Filesystem\File;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Wordpress\Wordpress;


if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class DirIterator implements DestinationDirIterator {

	const POSITION_EOL_STRING = "{eol:%s}";
	const POSITION_EOL_REGEX = "\{eol:([r|n]{1})\}";
	const POSITION_ARCHIVE_STRING = "{pos:%d}";
	const POSITION_ARCHIVE_REGEX = "\{pos:([\d]+)\}$";

	const PHP_EXIT = "<?php exit; ?>";
	private $_source;
	private $_exclude;
	private LogController $_logController;

	private $_tree_filename;
	private $_tree_filesize;
	private $_tree_fd;
	private $_tree_fd_position;
	private $_current_file;
	private $_current_file_length;
	private $_prev_file_length;
	private $_total_file;
	private $_callback;
	/**
	 * @throws DirIteratorException
	 */
	public function __construct($tree_filename) {
		if(!$tree_filename) throw new DirIteratorException("No tree file was provided");
		$this->_tree_filename = $tree_filename;
		$this->_tree_fd_position = 0;
		$this->_current_file_length = 0;
		$this->_prev_file_length = 0;
		$this->_total_file = 0;
		$this->_exclude = [];
		$this->_logController = new LogController();
	}

	public function setCallBack(callable $callback): void {
		$this->_callback = $callback;
	}

	public function setSource($source) { $this->_source = $source; }
	public function getSource() { return $this->_source; }

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
	 * Should always create new tree, tree file should not be present at this point
	 * @throws DirIteratorException
	 */
	public function getTotalFiles(): int {
		$this->_buildTree();
		return $this->_total_file;
	}

	public function setExcludes($excludes) { $this->_exclude = $excludes; }
	public function getExcludes(): array {return $this->_exclude;}

	/**
	 * @throws DirIteratorException
	 */
	public function hasNext(): bool {
		$this->_current_file = $this->_calculateNextFile();
		return !!$this->_current_file;
	}

	/**
	 * @param $archive_position
	 *
	 * @return DirIteratorFile
	 * @throws DirIteratorException
	 * @throws DirIteratorFileVanishedException
	 */
	public function next($archive_position): DirIteratorFile {
		$this->_tree_fd_position += $this->_prev_file_length;
		$this->_prev_file_length = $this->_current_file_length;
		$this->save($archive_position);
		return new DirIteratorFile($this->_current_file);

	}


	/**
	 * @throws DirIteratorException
	 */
	private function _calculateNextFile(): ?string {
		$this->getLogController()->logDebug("[DirIterator] [_calculateNextFile]");
		$this->_buildTree();
		if(!$this->_tree_fd) $this->_tree_fd = fopen($this->_tree_filename, 'a+');
		if(!$this->_tree_filesize) $this->_tree_filesize = DirIteratorFile::safe_filesize($this->_tree_filename);

		$delimiter_length = strlen(PHP_EOL);
		$this->_current_file_length = $delimiter_length + 1;
		$currentLine = '';

		while (-1 !== fseek($this->_tree_fd, -($this->_current_file_length + $this->_prev_file_length + $this->_tree_fd_position), SEEK_END)) {
			$char = fgetc($this->_tree_fd);
			$currentLine = $char . $currentLine;
			$this->_current_file_length++;

			if(substr($currentLine, 0, $delimiter_length) == PHP_EOL) {
				$currentLine = substr($currentLine, $delimiter_length);
				$this->_current_file_length -= $delimiter_length;
				break;
			}
		}

		if($currentLine) {
			$this->_current_file_length--;
			if (substr($currentLine, 0, strlen(self::PHP_EXIT)) == self::PHP_EXIT) return null;
			return $currentLine;
		}

		return null;
	}

	public function save($archive_position) {
		if(!file_exists($this->_tree_filename)) return;

		$eol_size = strlen(PHP_EOL);
		$new_size = $this->_tree_filesize - $this->_tree_fd_position;
		
		if($this->_current_file && !preg_match("/" . self::POSITION_ARCHIVE_REGEX . "/", $this->_current_file)) {
			$pos = sprintf(self::POSITION_ARCHIVE_STRING, $archive_position);
			$this->_current_file .= $pos;

			ftruncate($this->_tree_fd, $new_size-$this->_current_file_length);
			fseek($this->_tree_fd, $new_size-$this->_current_file_length);
			fwrite($this->_tree_fd, $this->_current_file);

			$this->_current_file_length += strlen($pos);
			$this->_prev_file_length = $this->_current_file_length;
			$new_size += strlen($pos);
		}

		$new_size -= $eol_size;

		ftruncate($this->_tree_fd, $new_size);
		fseek($this->_tree_fd, $new_size);
		fwrite($this->_tree_fd, PHP_EOL);
		$new_size += $eol_size;

		$this->_tree_filesize = $new_size;
		$this->_tree_fd_position = 0;
	}

	public function isBuildDone() {
		if(
			!file_exists($this->_tree_filename) ||
			filesize($this->_tree_filename) == 0
		) return false;

		$file = fopen($this->_tree_filename, "r");
		fseek($file, strlen(self::PHP_EXIT));
		$status = fread($file, 1);
		fclose($file);
		return $status == '1';
	}

	private function _countFiles():int {
		if(!file_exists($this->_tree_filename)) return 0;
		if(filesize($this->_tree_filename) == 0) return 0;
		$file = fopen($this->_tree_filename, "r");
		$total = 0;
		while (fgets($file) !== false) $total++;
		fclose($file);
		// Remove the first line from counting
		return $total-1;
	}
	
	private function _buildTree() {
		
		if($this->isBuildDone()) return;
		
		$this->getLogController()->logDebug("[DirIterator] [_buildTree]");
		$this->getLogController()->logDebug("\t[_buildTree] Tree File name: " . $this->_tree_filename);


		if(!$this->getSource()) throw new DirIteratorException("No source was provided");
		$source = new File($this->getSource());

		if(!$source->exists()) throw new DirIteratorException("The provided source not exists");
		if(!$source->isDir() || $source->isLink()) throw new DirIteratorException("The provided source isn't directory");

		$completed = $this->_countFiles();
		
		$old_umask = umask(0177);
		if(!file_exists($this->_tree_filename)) file_put_contents($this->_tree_filename, self::PHP_EXIT."0".PHP_EOL);
		umask($old_umask); // Reset to previous umask value

		$this->_tree_fd = fopen($this->_tree_filename, 'r+');
		fseek($this->_tree_fd, 0, SEEK_END);

		$queue = [dir($source->path())];

		while($queue) {

			$dir = array_pop($queue);
			$dir_path = $dir->path;

			while(($entry = $dir->read()) !== false) {
				if($entry == '.' || $entry == '..') continue;

				$file = new File($dir_path . JetBackup::SEP . $entry);
				
				if (!$file->isReadable()) {
					$this->getLogController()->logError("The file {$file->path()} is not readable, Skipping");
					if ($this->_callback) call_user_func($this->_callback, 'error', $file->path(), $this->_total_file);
					continue;
				}

				// check if this entry is excluded
				$clean_path = JetBackup::SEP . trim(substr($file->path(), strlen($this->getSource())), JetBackup::SEP);
				$clean_path = str_replace('\\', '/', $clean_path);
				foreach($this->getExcludes() as $exclude) {
					// fnmatch() would fail to match paths correctly because Windows uses backslashes
                    $exclude = Util::normalizePath($exclude);
					//$this->getLogController()->logDebug("\t[_buildTree] analyzing exclude: [$exclude] - [$clean_path] ");
					if(fnmatch($exclude, $clean_path, FNM_CASEFOLD) || ($file->isDir() && fnmatch($exclude, $clean_path . JetBackup::SEP, FNM_CASEFOLD))) {
						$this->getLogController()->logDebug("\t[_buildTree] Exclude HIT: '$exclude' for '$clean_path'");
						continue 2;
					}
				}

				// Handle wp-content: If using an alternate content dir, treat it as regular 'wp-content'
				$alternateWpContent = Wordpress::getAlternateContentDir() &&  basename(Wordpress::getAlternateContentDir()) == basename($file->path())  && $file->isLink();

				// Add regular directories (not symlinks) to the queue for further traversal.
				$realDir = $file->isDir() && !$file->isLink();

				if ($alternateWpContent || $realDir) {
					$queue[] = $dir;
					$queue[] = dir( $file->path() );
					continue 2;
				} else {
					$this->_total_file++;
					if($this->_total_file <= $completed) continue;
					$filename = self::_escapeEOL($file->path());
					fwrite($this->_tree_fd, $filename . PHP_EOL);
					if ($this->_callback) call_user_func($this->_callback, 'file', $filename, $this->_total_file);
				}
			}

			$this->_total_file++;
			if($this->_total_file <= $completed) continue;
			$filename = self::_escapeEOL($dir_path);
			fwrite($this->_tree_fd, $filename . PHP_EOL);
			if ($this->_callback) call_user_func($this->_callback, 'dir', $filename, $this->_total_file);

			$dir->close();
		}
		$this->getLogController()->logDebug("\t[_buildTree] Total files in tree: " . $this->_total_file);

		fseek($this->_tree_fd, strlen(self::PHP_EXIT));
		fwrite($this->_tree_fd, '1');
		fseek($this->_tree_fd, 0, SEEK_END);

		//copy ($this->_tree_filename, $this->_tree_filename.'_original.php');
		//chmod($this->_tree_filename.'_original.php', 0600);
	}
	
	private static function _escapeEOL($string):string {
		return trim(str_replace(["\r","\n"], [sprintf(self::POSITION_EOL_STRING, "r"), sprintf(self::POSITION_EOL_STRING, "n")], $string));
	}
	
	public function __destruct() {
		//$this->save();
		if($this->_tree_fd) fclose($this->_tree_fd);
	}

	public function done() {
		if(file_exists($this->_tree_filename)) unlink($this->_tree_filename);
	}

	/**
	 * @inheritDoc
	 */
	public function rewind():void {}

	/**
	 * @inheritDoc
	 */
	public function getNext(): ?DestinationFile { return null; }
}