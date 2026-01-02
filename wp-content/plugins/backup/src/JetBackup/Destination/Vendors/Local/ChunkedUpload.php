<?php

namespace JetBackup\Destination\Vendors\Local;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private string $_destination;

	public function __construct($destination) {
		$this->_destination = $destination;
	}
	
	/**
	 * @return object
	 */
	public function prepare():object {
		return new \stdClass();
	}

	public function setData(object $data):void {}
	
	/**
	 * @return int
	 */
	public function getOffset():int {
		return file_exists($this->_destination) ? filesize($this->_destination) : 0;
	}

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 */
	public function upload(FileChunk $chunk):void {
		$fd = fopen($this->_destination, 'a');
		while(($data = $chunk->read(1024)) !== false) fwrite($fd, $data);
		fclose($fd);
	}

	/**
	 * @return void
	 */
	public function finalize():void {}

	/**
	 * @inheritDoc
	 */
	public function getChunkSize(): ?int {
		return null;
	}
}