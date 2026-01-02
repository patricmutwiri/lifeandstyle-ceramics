<?php

namespace JetBackup\Destination\Vendors\S3;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Vendors\S3\Client\ClientManager;
use JetBackup\Destination\Vendors\S3\Client\Exception\ClientException;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private ClientManager $_client;
	private string $_destination;
	private object $_data;
	private ?array $_parts=null;
	private int $_currentPart=0;

	public function __construct(ClientManager $client, $destination) {
		$this->_client = $client;
		$this->_destination = $destination;
		$this->_data = new \stdClass();
	}
	
	/**
	 * @return object
	 * @throws ClientException
	 */
	public function prepare():object {
		$output = new \stdClass();
		$output->upload_id = $this->_client->createUploadID($this->_destination);
		return $output;
	}

	public function setData(object $data):void {
		$this->_data = $data;		
	}

	/**
	 * @throws IOException
	 * @throws ClientException
	 */
	public function _loadParts() {
		if(!isset($this->_data->upload_id)) throw new IOException("No upload id was found");
		if($this->_parts !== null) return;
		$parts = $this->_client->listUploadParts($this->_destination, $this->_data->upload_id);
		$this->_parts = [];
		if(!isset($parts->Body->Part)) return;
		
		foreach($parts->Body->Parts as $part_details) {
			$part = new \stdClass();
			$part->number = $part_details->PartNumber;
			$part->etag = trim($part_details->ETag, '"');
			$part->Size = $part_details->Size;
			$this->_parts[] = $part;
		}
	}
	
	/**
	 * @return int
	 * @throws IOException|ClientException
	 */
	public function getOffset():int {
		$this->_loadParts();
		$offset = 0;
		foreach($this->_parts as $part) $offset += $part->size;
		return $offset;
	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int { return null; }
	
	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 * @throws IOException|ClientException
	 */
	public function upload(FileChunk $chunk):void {
		if(!isset($this->_data->upload_id)) throw new IOException("No upload id was found");
		$this->_loadParts();
		$this->_currentPart = sizeof($this->_parts);
		$this->_currentPart++;
		$result = $this->_client->putChunk($chunk, $this->_destination, $this->_data->upload_id, $this->_currentPart);

		$part = new \stdClass();
		$part->number = $this->_currentPart;
		$part->etag = trim($result->Headers->etag, '"');
		$part->size = $chunk->getSize();
		
		$this->_parts[] = $part;
	}

	/**
	 * @return void
	 * @throws IOException|ClientException
	 */
	public function finalize():void {
		if(!isset($this->_data->upload_id)) throw new IOException("No upload id was found");
		$this->_loadParts();
		$xml = new \SimpleXMLElement('<CompleteMultipartUpload/>');
		foreach($this->_parts as $part_details) {
			$part = $xml->addChild('Part');
			$part->addChild('PartNumber', $part_details->number);
			$part->addChild('ETag', $part_details->etag);
		}
		$this->_client->closeChunkedUpload($this->_destination, $this->_data->upload_id, $xml->asXML());
	}
}