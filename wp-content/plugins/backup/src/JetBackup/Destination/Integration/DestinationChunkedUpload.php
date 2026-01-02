<?php

namespace JetBackup\Destination\Integration;

use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface DestinationChunkedUpload {

	/**
	 * @return object
	 */
	public function prepare():object;

	/**
	 * @param object $data
	 *
	 * @return void
	 */
	public function setData(object $data):void;
	
	/**
	 * @return int
	 */
	public function getOffset():int;

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int;

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 */
	public function upload(FileChunk $chunk):void;

	/**
	 * @return void
	 */
	public function finalize():void;
}