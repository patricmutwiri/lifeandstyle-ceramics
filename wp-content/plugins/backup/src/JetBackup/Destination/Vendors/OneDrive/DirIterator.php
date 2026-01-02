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
namespace JetBackup\Destination\Vendors\OneDrive;

use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\OneDrive\Client\ClientException;
use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {

	private OneDrive $_destination;
	private string $_directory;
	private array $_files;

	/**
	 * @param OneDrive $destination
	 * @param string $directory
	 *
	 * @throws IOException
	 */
	public function __construct(OneDrive $destination, string $directory) {
		$this->_destination = $destination;
		$this->_directory = $directory;
		$this->_files = [];
		if(!$this->_destination->dirExists($this->_directory)) return;
		$this->rewind();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	private function _load():void {

		$directory = $this->_destination->getRealPath($this->_directory);
		if($directory == '/') $directory = '';

		$params = [ '$top' => 1000 ];
		$next = true;
		$token = '';

		while ($next) {
			if($token) $params['$skiptoken'] = $token;

			try {
				$response = $this->_destination->client('get', OneDrive::DRIVE_ROOT . ($directory ? ":$directory:" : '') . '/children', $params);
			} catch(ClientException $e) {
				throw new IOException($e->getMessage(), $e->getCode(), $e);
			}

			$next = $response->Body->{'@odata.nextLink'} ?? '';
			$token = ($next && preg_match("/skiptoken=(.*)$/", $next, $matches)) ? $matches[1] : '';
			$files = $response->Body->value ?? [];
			foreach ($files as $file) $this->_files[$file->name] = $file;
		}

		if ($this->_files) krsort($this->_files, SORT_STRING);
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind():void { $this->_load(); }

	/**
	 * @return bool
	 */
	public function hasNext(): bool { return $this->_files && sizeof($this->_files); }

	/**
	 * @return iDestinationFile|null
	 */
	public function getNext():?iDestinationFile {
		if(!$this->hasNext()) return null;

		$nextfile = array_pop($this->_files);
		
		$path = $this->_directory . '/' . $nextfile->name;
		$basename = basename($path);
		
		$file = new DestinationFile();
		$file->setType(isset($nextfile->folder) ? iDestinationFile::TYPE_DIRECTORY : iDestinationFile::TYPE_FILE);
		$file->setName($basename);
		$file->setPath($basename == $path ? '' : dirname($path));
		$file->setSize($nextfile->size);
		$file->setModifyTime(strtotime($nextfile->lastModifiedDateTime));
		$file->setFileData(json_encode([ 'id' => $nextfile->id ]));

		return $file;
	}
}