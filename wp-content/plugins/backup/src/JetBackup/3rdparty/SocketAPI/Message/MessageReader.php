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
namespace JetBackup\SocketAPI\Message;

use JetBackup\SocketAPI\Exception\MessageException;
use JetBackup\SocketAPI\Socket\Socket;

class MessageReader extends Message {

	const READ_CHUNK = 1024;

	/** @var Socket  */
	private $_socket;
	private $_buffer;

	public function __construct(Socket $socket) {
		parent::__construct();
		$this->_buffer = '';
		$this->_socket = $socket;
	}

	/**
	 * @param $length
	 *
	 * @return int
	 * @throws MessageException
	 */
	public function read($length=self::READ_CHUNK) {
		if(feof($this->_socket->getStreamResource()))
			throw new MessageException("Nothing to read from socket");

		if(($data = socket_read($this->_socket->getSocketResource(), $length)) === false) {
			$code = socket_last_error($this->_socket->getSocketResource());
			$message = socket_strerror($code);
			throw new MessageException("Failed reading from socket. Error: {$message} (error code {$code})");
		}

		$this->append($data);

		return strlen($data);
	}

	/**
	 * @param string $message
	 */
	public function append($message) { $this->_buffer .= $message; }

	/**
	 * @throws MessageException
	 */
	public function checkMessage() {

		// can't find delimiter - wait for more data
		if(!$this->_findDelimiter()) return;

		// must receive more than 12 bytes - wait for more data
		if(strlen($this->_buffer) < 13) return;

		$length = self::unpackInt(substr($this->_buffer, 4));

		if(!$length || $length > self::MAX_MESSAGE_LENGTH) {
			// Can't find length or length is too long - move buffer pointer 1 byte forward and go to the next delimiter
			$this->_buffer = substr($this->_buffer, 1);

			if(!$length) throw new MessageException("Invalid message length provided");
			else throw new MessageException("The maximum message length is " . self::MAX_MESSAGE_LENGTH . " bytes. You sent " . $length . " bytes");
		}

		$msglen = strlen(substr($this->_buffer, 8));

		// not all message was received - wait for more data
		if($msglen < ($length+4)) return;

		$length_end = self::unpackInt(substr($this->_buffer, $length+8));

		if(!$length_end || $length != $length_end) {
			// start length and end length is not equal - move buffer pointer 1 byte forward and go to the next delimiter
			$this->_buffer = substr($this->_buffer, 1);

			if(!$length_end) throw new MessageException("Invalid message protocol");
			else throw new MessageException("The start message length and end message length doesn't match");
		}

		// remove the first 8 bytes (delimiter and length) and add this message to the iterator
		$message = substr($this->_buffer, 8, $length);

		// move the buffer pointer to the next message
		$this->_buffer = substr($this->_buffer, $length+12);

		foreach($this->_protocol_listener as $listener) $listener->onMessageReady($message);
	}

	/**
	 * @return bool
	 */
	private function _findDelimiter() {

		// must receive more than 12 bytes - wait for more data
		if(strlen($this->_buffer) < 13) return false;

		$d = self::unpackInt($this->_buffer);
		$o = 4;

		while(self::DELIMITER != $d) {
			$s = substr($this->_buffer, $o++, 1);
			if($s === false) return false;
			$s = (int)ord($s);
			$d = (($d << 8) | $s) & self::MAX_UINT32;
		}

		$this->_buffer = substr($this->_buffer, $o-4);
		return true;
	}
}