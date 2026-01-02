<?php

namespace JetBackup\Destination\Vendors\DropBox;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Vendors\DropBox\Client\Client;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileStream;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private Client $_client;
	private string $_source;
	private string $_destination;
	private object $_data;

	public function __construct(Client $client, $source, $destination) {
		$this->_client = $client;
		$this->_source = $source;
		$this->_destination = $destination;
		$this->_data = new \stdClass();
	}
	
	/**
	 * @return object
	 */
	public function prepare():object {
		return $this->_client->startUploadSession()->Body;
	}

	public function setData(object $data):void {
		$this->_data = $data;		
	}

	public function getOffset():int {
		if(!isset($this->_data->session_id)) throw new IOException("No session id was found");
		return $this->_client->getUploadSessionOffset($this->_data->session_id);
	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int { return null; }

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 */
	public function upload(FileChunk $chunk):void {
		if(!isset($this->_data->session_id)) throw new IOException("No session id was found");
		$this->_client->appendUploadSession($chunk, $this->_data->session_id);
	}

	/**
	 * @return void
	 */
	public function finalize():void {
		if(!isset($this->_data->session_id)) throw new IOException("No session id was found");
		$this->_client->finishUploadSession($this->_data->session_id, $this->getOffset(), $this->_destination);
	}
}