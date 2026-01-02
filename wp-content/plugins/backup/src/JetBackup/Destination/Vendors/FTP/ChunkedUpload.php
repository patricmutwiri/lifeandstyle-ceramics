<?php

namespace JetBackup\Destination\Vendors\FTP;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private FTP $_client;
	private string $_destination;

	public function __construct(FTP $client, $destination) {
		$this->_client = $client;
		$this->_destination = $destination;
	}
	
	/**
	 * @return object
	 */
	public function prepare():object { return new \stdClass(); }
	public function setData(object $data):void {}
	
	/**
	 * @return int
	 * @throws IOException
	 */
	public function getOffset():int {
		return $this->_client->_client('getFileSize', $this->_destination);
	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int { return null; }

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 * @throws IOException
	 */
	public function upload(FileChunk $chunk):void {
		$this->_client->_client('uploadChunk', $chunk, $this->_destination);
	}

	/**
	 * @return void
	 */
	public function finalize():void {}
}