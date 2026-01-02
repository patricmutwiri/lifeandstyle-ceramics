<?php 

namespace JetBackup\Destination\Vendors\Local;

use Exception;
use JetBackup\Destination\Destination;
use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Entities\Util;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');


class Local extends DestinationWrapper {

	const TYPE = 'Local';

	/**
	 * @return string
	 */
	public function getPath():string {
		return preg_replace("#" . preg_quote(JetBackup::SEP) . "+#", JetBackup::SEP, Factory::getLocations()->getBackupsDir() . JetBackup::SEP . parent::getPath());
	}

	/**
	 * @param $path
	 *
	 * @return string
	 */
	public function getRealPath($path):string {
		return preg_replace("#" . preg_quote(JetBackup::SEP) . "+#", JetBackup::SEP, $this->getPath() . JetBackup::SEP . $path);
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 */
	public function connect():void {
		try{
			if(!file_exists($this->getPath())) {
				mkdir($this->getPath(), 0700);
				Util::secureFolder($this->getPath());
			}
			if(!is_writable($this->getPath())) throw new ConnectionException("Destination path is not writable");
		} catch (Exception $e) {
			throw new ConnectionException($e->getMessage());
		}

	}

	/**
	 * @return void
	 */
	public function disconnect():void {}

	/**
	 * @return void
	 */
	public function register():void {}

	/**
	 * @return void
	 */
	public function unregister():void {}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return bool
	 */
	public function dirExists(string $directory, ?string $data=null): bool {
		$this->getLogController()->logDebug("[dirExists] {$this->getRealPath($directory)}");
		return is_dir($this->getRealPath($directory));
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return bool
	 */
	public function fileExists(string $file, ?string $data=null): bool {
		$this->getLogController()->logDebug("[fileExists] {$this->getRealPath($file)}");
		return is_file($this->getRealPath($file));
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function createDir( string $directory, bool $recursive, ?string $data=null):?string {
		$this->getLogController()->logDebug("[createDir] {$this->getRealPath($directory)}");
		if(!$this->dirExists($directory) && !@mkdir($this->getRealPath($directory), 0700, $recursive)) throw new IOException("Failed crating directory");
		return null;
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeDir( string $directory, ?string $data=null):void {
		$this->getLogController()->logDebug("[removeDir] $directory");
		if(!$this->dirExists($directory)) return;
		Util::rm($this->getRealPath($directory));
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void {
		$this->getLogController()->logDebug("[removeFile] {$this->getRealPath($file)}");
		if($this->fileExists($file) && !@unlink($this->getRealPath($file)))
			throw new IOException("Failed deleting file");
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function copyFileToLocal(string $source, string $destination, ?string $data=null):void {
		$this->getLogController()->logDebug("[copyFileToLocal] {$this->getRealPath($source)} -> $destination");
		if(!copy($this->getRealPath($source), $destination)) 
			throw new IOException("Failed coping file to local");
	}

	/**
	 * @param string $source
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 */
	public function copyFileToLocalChunked(string $source, string $destination, ?string $data=null):DestinationChunkedDownload {
		$this->getLogController()->logDebug("[copyFileToLocalChunked] $source -> {$this->getRealPath($destination)}");
		return new ChunkedDownload($this->getRealPath($source), $destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):?string {
		$this->getLogController()->logDebug("[copyFileToRemote] $source -> {$this->getRealPath($destination)}");
		if(!copy($source, $this->getRealPath($destination)))
			throw new IOException("Failed coping file to remote");
		return null;
	}

	/**
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 */
	public function copyFileToRemoteChunked(string $source, string $destination, ?string $data=null):DestinationChunkedUpload {
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] $source -> {$this->getRealPath($destination)}");
		return new ChunkedUpload($this->getRealPath($destination));
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return DestinationDirIterator
	 * @throws IOException
	 */
	public function listDir(string $directory, ?string $data=null):DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] $directory");
		return new DirIterator($this, $directory);
	}

	/**
	 * @return iDestinationDiskUsage|null
	 */
	public function getDiskInfo(): ?iDestinationDiskUsage {

		if(!function_exists('disk_total_space') || !function_exists('disk_free_space')) return null;

		$backupdir = $this->getRealPath('/'); // getRealPath returns the content dir
		$totalDisk = disk_total_space($backupdir);
		$usedDisk = disk_free_space($backupdir);

		$output = new DestinationDiskUsage();
		$output->setUsageSpace((int) $usedDisk);
		$output->setTotalSpace((int) $totalDisk);
		$output->setFreeSpace($totalDisk - $usedDisk);

		return $output;

	}
	
	/**
	 * @param int $mode
	 *
	 * @return int
	 */
	private static function _getFileType(int $mode):int {
		switch(($mode & 0170000)) {
			case 0100000: return iDestinationFile::TYPE_FILE;
			case 0120000: return iDestinationFile::TYPE_LINK;
			case 0040000: return iDestinationFile::TYPE_DIRECTORY;
			case 0060000: return iDestinationFile::TYPE_BLOCK;
			case 0010000: return iDestinationFile::TYPE_FIFO;
		}

		return iDestinationFile::TYPE_UNKNOWN;
	}

	/**
	 * @param string $file
	 *
	 * @return iDestinationFile|null
	 */
	public function getFileStat(string $file):?iDestinationFile {
		$this->getLogController()->logDebug("[getFileStat] $file");
		if(!$this->fileExists($file) && !$this->dirExists($file)) return null;
		$stat = stat($this->getRealPath($file));

		$output = new DestinationFile();
		$output->setName(basename($file));
		$output->setPath(dirname($file));
		$output->setModifyTime($stat['mtime']);
		$output->setSize($stat['size']);
		$output->setType(self::_getFileType($stat['mode']));

		return $output;
	}
	
	public function protectedFields(): array { return []; }


	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws DBException
	 * @throws InvalidArgumentException|FieldsValidationException
	 */
	public function validateFields(): void {

		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if(!str_starts_with(parent::getPath(), JetBackup::SEP) && !Destination::getIsDefault($this->getId())) throw new FieldsValidationException("Path must start with " . JetBackup::SEP);
		if(strlen($this->getPath()) <= 1) throw new FieldsValidationException("Path must point to a directory and can't be only " . JetBackup::SEP);
	}

	public function setData( object $data ): void {
	}

	public function getData(): array {
		return $this->getOptions()->getData();
	}
}