<?php

namespace JetBackup\Destination\Vendors\S3;

use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\S3\Client\Exception\ClientException;
use JetBackup\Destination\Vendors\S3\Client\ObjectData;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Wordpress\Wordpress;

class DirIterator implements DestinationDirIterator {

	const CHUNK_LIMIT = 500;

	private S3 $_destination;
	private string $_directory;
	/** @var ObjectData[] */
	private array $_items;

	/**
	 * @param S3 $destination
	 * @param string $directory
	 *
	 * @throws IOException
	 */
	public function __construct(S3 $destination, string $directory) {

		if(!$destination->dirExists($directory)) return;

		$this->_destination = $destination;
		$this->_directory = preg_replace("/^\/+/", "", $destination->getRealPath($directory));
		$this->_items = [];

		$this->_load();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	private function _load():void {
		try {
			$list = null;
			while (!$list || $list->isTruncated()) {
				$token = '';
				if($list && !($token = $list->getNextContinuationToken())) return;
				$list = $this->_destination->getClient()->listObjects($this->_directory,self::CHUNK_LIMIT,$token);
				while($file = $list->getNextObject()) $this->_items[$file->getKey()] = $file;
			}

			krsort($this->_items, SORT_STRING);
		} catch(ClientException|HttpRequestException $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind():void { $this->_load(); }

	/**
	 * @return bool
	 */
	public function hasNext():bool { return $this->_items && sizeof($this->_items); }

	/**
	 * @return iDestinationFile|null
	 */
	public function getNext():?iDestinationFile {
		if(!$this->hasNext()) return null;

		$nextfile = array_pop($this->_items);

		$path = $this->_destination->removeRealPath('/' . $nextfile->getKey());
		if(Wordpress::strContains($path, '/')) $path = preg_replace("/^\/+/", "", dirname($path));
		else $path = '';

		$file = new DestinationFile();
		$file->setType($nextfile->isDir() ? iDestinationFile::TYPE_DIRECTORY : iDestinationFile::TYPE_FILE);
		$file->setName(basename($nextfile->getKey()));
		$file->setPath('/' . $path);
		$file->setSize($nextfile->isDir() ? 4096 : $nextfile->getSize());
		$file->setModifyTime($nextfile->getMtime());

		return $file;
	}
}