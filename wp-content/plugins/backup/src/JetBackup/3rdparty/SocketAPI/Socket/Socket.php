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
namespace JetBackup\SocketAPI\Socket;

use JetBackup\SocketAPI\Exception\SocketException;
use JetBackup\SocketAPI\Message\MessageReader;
use JetBackup\SocketAPI\Message\MessageWriter;

class Socket {

	private $_socket;
	private $_stream;
	private $_file;

	private $_reader;
	private $_writer;

	/**
	 * Socket constructor.
	 * @param $socket
	 * @throws SocketException
	 */
	public function __construct($socket=null) {
		$this->_socket = $socket ?: socket_create(AF_UNIX, SOCK_STREAM, 0);
		if(!$this->getSocketResource()) throw new SocketException("Could not create socket");
	}
	
	/**
	 * @param string $file
	 */
	public function setFile($file) { $this->_file = $file; }

	/**
	 * @return string
	 */
	public function getFile() { return $this->_file; }

	public function getSocketResource() { return $this->_socket; }
	public function getStreamResource() {
		if(!$this->_stream) $this->_stream = socket_export_stream($this->getSocketResource());
		return $this->_stream;
	}


	/**
	 * @return MessageReader
	 */
	public function getReader(): MessageReader {
		if(!$this->_reader) $this->_reader = new MessageReader($this);
		return $this->_reader;
	}

	/**
	 * @return MessageWriter
	 */
	public function getWriter(): MessageWriter {
		if(!$this->_writer) $this->_writer = new MessageWriter($this);
		return $this->_writer;
	}

	/**
	 * @param bool $isServer
	 *
	 * @throws SocketException
	 */
	public function connect( bool $isServer=false) {
		if(!$this->getFile()) throw new SocketException("There is no socket file set");
		if($isServer && file_exists($this->getFile())) @unlink($this->getFile());
		if (!file_exists($this->getFile()) || !is_readable($this->getFile())) throw new SocketException("Could not connect to socket (not exist).");
		if(!@socket_connect($this->getSocketResource(), $this->getFile())) throw new SocketException("Could not connect to socket (socket error).");
	}

	/**
	 *
	 */
	public function close() {
		@socket_close($this->getSocketResource());
	}
}