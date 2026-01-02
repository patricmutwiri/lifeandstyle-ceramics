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
namespace JetBackup\Destination\Vendors\Box;

use Exception;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\Box\Client\Client;
use JetBackup\Destination\Vendors\Box\Client\ClientException;
use JetBackup\Destination\Vendors\Box\Client\File;
use JetBackup\Destination\Vendors\Box\Client\ListFiles;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {

	const CHUNK_LIMIT = 1000;

	private Box $_destination;
	private string $_directory;
	private ?ListFiles $_list=null;
	private ?string $_parent_id=null;
	/** @var File[] */
	private array $_files=[];
	private array $_subdirectories = []; // Queue of subdirectories
	private array $_processedDirectories = [];

	/**
	 * @param Box $destination
	 * @param string $directory
	 * @param string|null $parent_id
	 *
	 * @throws IOException
	 * @throws JBException
	 */
	public function __construct(Box $destination, string $directory, ?string $parent_id=null) {

		$this->_destination = $destination;
		$this->_directory = $directory;
		if(!$this->_destination->getClient()) throw new IOException("Unable to retrieve Box service");
		$path = $this->_destination->getRealPath($this->_directory);
		
		if(!$parent_id) $parent_id = $this->_destination->getFolderId($path == '/' ? '' : $this->_directory);
		if($parent_id === null) return;
		$this->_parent_id = $parent_id;
		$this->_destination->getLogController()->logDebug("[DestinationDirIterator] Processing directory: {$this->_directory} with Parent ID: {$this->_parent_id}");

		$this->rewind();
	}

	/**
	 * @param bool $rewind
	 *
	 * @return void
	 * @throws IOException
	 */
	private function _loadChunk(bool $rewind = false): void {
		$this->_destination->getLogController()->logDebug("[_loadChunk] Processing directory: {$this->_directory} with Parent ID: {$this->_parent_id}");
		if (in_array($this->_directory, $this->_processedDirectories, true)) {
			$this->_destination->getLogController()->logDebug("[_loadChunk] Skipping already processed directory: {$this->_directory}");
			return;
		}
		$this->_processedDirectories[] = $this->_directory;

		try {
			$marker = '';
			if (!$rewind && $this->_list) {
				$marker = $this->_list->getNextPageToken();
				if (!$marker) {
					$this->_list = null;
					return;
				}
			}

			$this->_list = $this->_destination->_retries(function () use ($marker) {
				return $this->_destination->getClient()->listFolder(
					$this->_parent_id ?? Client::ROOT_FOLDER,
					self::CHUNK_LIMIT,
					$marker
				);
			}, "Failed fetching list of files");

			$this->_files = $this->_list ? $this->_list->getFiles() : [];
			foreach ($this->_files as $file) {
				if ($file->getMimeType() === Client::MIMITYPE_DIR) {
					$subdirPath = rtrim($this->_directory, '/') . '/' . $file->getName();
					$subdirPath = preg_replace('#/+#', '/', $subdirPath); // Normalize slashes
					$this->_subdirectories[] = [
						'path' => $subdirPath,
						'id' => $file->getId(),
					];
					$this->_destination->getLogController()->logDebug("[_loadChunk] Enqueued subdirectory: $subdirPath with ID: {$file->getId()}");
				}
			}
		} catch (Exception $e) {
			if ($e->getCode() === 404) {
				$this->_files = [];
			} else {
				throw new IOException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}



	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind(): void {
		$this->_destination->getLogController()->logDebug("[rewind] Reloading current directory: {$this->_directory}");
		$this->_loadChunk(true);
	}


	/**
	 * @return bool
	 * @throws IOException
	 */
	public function hasNext(): bool {
		if (!($this->_files && count($this->_files))) {
			// If there are no more files in the current directory, move to the next subdirectory
			if (!empty($this->_subdirectories)) {
				$nextSubdir = array_shift($this->_subdirectories);
				$this->_directory = $nextSubdir['path'];
				$this->_parent_id = $nextSubdir['id'];

				// Debugging: Log subdirectory switch
				$this->_destination->getLogController()->logDebug("[hasNext] Switching to subdirectory: {$this->_directory} with ID: {$this->_parent_id}");

				// Reload chunk for the new subdirectory
				$this->rewind();

				return $this->hasNext(); // Reevaluate after switching
			}

			return false; // No more files or subdirectories
		}

		return true;
	}




	/**
	 * @return ?iDestinationFile
	 * @throws IOException
	 */
	public function getNext(): ?iDestinationFile {
		if (!$this->hasNext()) return null;

		// If `_files` is empty but there are subdirectories, `hasNext` handles traversal
		if (empty($this->_files)) {
			return null; // Safeguard against infinite loop
		}

		$nextFile = array_shift($this->_files);

		$path = rtrim($this->_directory, '/') . '/' . $nextFile->getName();
		$path = preg_replace('#/+#', '/', $path); // Normalize slashes
		$basename = basename($path);

		$file = new DestinationFile();
		$file->setType($nextFile->getMimeType() === Client::MIMITYPE_DIR
			? iDestinationFile::TYPE_DIRECTORY
			: iDestinationFile::TYPE_FILE);
		$file->setName($basename);
		$file->setPath($basename === $path ? '' : dirname($path));
		$file->setSize($nextFile->getMimeType() === Client::MIMITYPE_DIR
			? ($nextFile->getSize() ?: 4096)
			: $nextFile->getSize());
		$file->setModifyTime($nextFile->getModificationTime());
		$file->setFileData(json_encode(['id' => $nextFile->getId()]));

		// Debugging: Log the file being processed
		$this->_destination->getLogController()->logDebug("[getNext] Processing file: $basename with path: $path");

		return $file;
	}


}