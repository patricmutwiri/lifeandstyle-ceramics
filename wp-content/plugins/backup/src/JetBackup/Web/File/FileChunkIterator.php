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
namespace JetBackup\Web\File;

use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class FileChunkIterator {

	private FileStream $_file;
	private int $_chunk_size;

	/**
	 * @param FileStream $file
	 * @param int $chunk_size
	 */
	public function __construct(FileStream $file, int $chunk_size=FileChunk::CHUNK_SIZE) {
		$this->_file = $file;
		$this->_chunk_size = $chunk_size;
	}

	/**
	 * @return void
	 */
	public function rewind():void {
		$this->_file->seek(0);
	}

	/**
	 * @return bool
	 */
	public function hasNext():bool {
		return $this->_file->tell() < $this->_file->getSize();
	}

	/**
	 * @return FileChunk|null
	 * @throws IOException
	 */
	public function next():?FileChunk {
		if(!$this->hasNext()) return null;
		$chunk = new FileChunk($this->_file, $this->_chunk_size);
		if(!$chunk->getSize()) return null;
		return $chunk;
	}
}