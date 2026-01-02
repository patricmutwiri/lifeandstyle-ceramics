<?php

namespace JetBackup\Destination;

use JetBackup\Exception\DestinationException;
use JetBackup\Exception\IOVanishedException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Queue\QueueItem;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Tree {

	const TREE_FILE = '%s' . JetBackup::SEP . '%d_tree.ini';

	private Destination $_destination;
	private QueueItem $_queue_item;
	private string $_source;
	private string $_tree_file;

	/**
	 * @param Destination $destination
	 * @param QueueItem $item
	 * @param string $source
	 */
	public function __construct(Destination $destination, QueueItem $item, string $source) {
		$this->_destination = $destination;
		$this->_queue_item = $item;
		$this->_source = $source;
		$this->_tree_file = sprintf(self::TREE_FILE, $source, $destination->getId());
	}

	/**
	 * @return void
	 * @throws DestinationException
	 * @throws IOVanishedException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	private function _build():void {

		if(file_exists($this->_tree_file)) return;

		$excludes = [
			'*' . JetBackup::SEP . '.htaccess',
			'*' . JetBackup::SEP . 'web.config',
			'*' . JetBackup::SEP . 'index.html',
			JetBackup::SEP . 'tree.php_done',
			JetBackup::SEP . '*_tree.ini',
			JetBackup::SEP . 'tmp',
			JetBackup::SEP . 'tmp' . JetBackup::SEP . '*',
			JetBackup::SEP . 'logs',
			JetBackup::SEP . 'logs' . JetBackup::SEP . '*',
			JetBackup::SEP . basename($this->_tree_file),
		];

		$fd = fopen($this->_tree_file, 'w');
		if(!$fd) throw new DestinationException("Cannot open tree file: $this->_tree_file");
		
		// We must build the tree with directories first, we must create the directory before uploading files to that directory
		$scan = new ScanDirIterator($this->_source);

		$total_size = 0;
		while($entry = $scan->next()) {
			$filename = substr($entry->getFullPath(), strlen($this->_source));
			foreach($excludes as $exclude) if(fnmatch($exclude, $filename)) continue 2;
			fwrite($fd, $filename . PHP_EOL);
			if(!$entry->isDir()) $total_size += $entry->getSize();
		}

		fclose($fd);

		$progress = $this->_queue_item->getProgress();
		$progress->setTotalSubItems($total_size);
		$progress->setCurrentSubItem(0);
		$this->_queue_item->save();

	}

	/**
	 * @param callable $callback
	 *
	 * @return void
	 * @throws DestinationException
	 * @throws IOVanishedException
	 */
	public function process(callable $callback) {

		$this->_build();
		
		$fp = fopen($this->_tree_file, 'r+');
		if (!$fp) throw new DestinationException("Cannot open tree file: $this->_tree_file");

		fseek($fp, 0, SEEK_END);
		$fileSize = ftell($fp); // Get the initial size of the file
		$position = $fileSize;
		$buffer = '';
		$_chunk_size = $this->_destination->getChunkSizeBytes() ?: Factory::getSettingsPerformance()->getReadChunkSizeBytes();

		while ($position > 0) {

			$readSize = min($position, $_chunk_size);
			$position -= $readSize; // Adjust position for the next read
			fseek($fp, $position);
			$chunk = fread($fp, $readSize) . $buffer; // Prepend previously buffered data

			while (($eolPos = strrpos($chunk, PHP_EOL)) !== false) {
				$lineStartPos = $eolPos + strlen(PHP_EOL);
				$line = substr($chunk, $lineStartPos);
				$chunk = substr($chunk, 0, $eolPos);

				if (!empty($line)/* && basename($line) != basename(self::TREE_FILE)*/) call_user_func($callback, $line);

				// Update the position for truncating the file
				$newSize = $position + $eolPos + strlen(PHP_EOL);
				ftruncate($fp, $newSize);
				fflush($fp); // Ensure changes are written
				fseek($fp, $newSize); // Set the pointer for the next operation
			}

			$buffer = $chunk; // Save any remaining data not followed by PHP_EOL

			if ($position === 0 && !empty($buffer)) {
				// If there's buffered data left when we reach the start of the file, process it
				call_user_func($callback, $buffer);
				ftruncate($fp, 0); // Truncate the file if the entire content has been processed
			}


		}

		fclose($fp);

		// At this point, if tree file is empty we know we finished with the current destination
		// tree upload, we need this if using multiple uploads to multiple destinations.

		// Do not delete tree file here
		//unlink($_tree_file);
	}
}