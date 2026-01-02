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

class FileChunk {

	const CHUNK_SIZE = 5242880; // 5MB
	const CHUNK_SIZE_PIECE = 1048576; // 1MB

	private FileStream $_file;
	private int $_chunk_start;
	private int $_chunk_size;
	private int $_readed;

	/**
	 * Chunk constructor.
	 *
	 * @param FileStream $file
	 * @param int $chunk_size
	 *
	 * @throws IOException
	 */
	public function __construct(FileStream $file, int $chunk_size=self::CHUNK_SIZE) {
		$this->_file = $file;
		$this->_chunk_start = $this->getFile()->tell();
		$this->_readed = 0;

		$size = ($this->getFile()->getSize() - $this->_chunk_start);
		if($size < 0) throw new IOException("Chunk size length is too small ($size bytes)");
		$this->_chunk_size = min($size, $chunk_size);
	}

	/**
	 * @return void
	 */
	public function rewind():void {
		$this->_readed = 0;
		$this->getFile()->seek($this->_chunk_start);
	}

	/**
	 * @return int
	 */
	public function getSize():int {
		return $this->_chunk_size;
	}

	/**
	 * @return FileStream
	 */
	public function getFile():FileStream {
		return $this->_file;
	}

	/**
	 * @param int|false $length
	 *
	 * @return false|string
	 */
	public function readPiece($length=false) {
		if(!$length || $length > self::CHUNK_SIZE_PIECE) $length = self::CHUNK_SIZE_PIECE;
		return $this->read($length);
	}

	/**
	 * @param int $length
	 *
	 * @return int
	 */
	public function length(int $length):int {
		if($this->_readed + $length > $this->_chunk_size) $length = $this->_chunk_size - $this->_readed;
		return $length;		
	}
	
	/**
	 * @param int $length
	 *
	 * @return false|string
	 */
	public function read(int $length) {
		$length = $this->length($length);
		if($length <= 0) return false;
		$this->_readed += $length;
		return $this->getFile()->read($length);
	}

	/**
	 * @return bool
	 */
	public function eof():bool {
		return $this->getFile()->eof();
	}
	
	/**
	 * @param string $algorithm
	 *
	 * @return string
	 */
	public function getHash(string $algorithm, $binary=false):string {
		$this->rewind();
		$ctx = hash_init($algorithm);
		while (($data = $this->read(self::CHUNK_SIZE_PIECE)) !== false) hash_update($ctx, $data);
		$this->rewind();
		return hash_final($ctx, $binary);
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return "Chunk -> " . json_encode([
				'file'          => $this->_file->getFile(),
				'chunk_size'    => $this->_chunk_size,
				'chunk_start'   => $this->_chunk_start,
				'read'          => $this->_readed,
			]);
	}
}