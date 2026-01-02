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

use JetBackup\SocketAPI\Protocol\ProtocolListener;
use JetBackup\SocketAPI\Socket\Socket;

class MessageWriter extends Message implements ProtocolListener {

	const WRITE_CHUNK = 1024;

	/** @var Socket  */
	private $_socket;

	public function __construct(Socket $socket) {
		parent::__construct();
		$this->_socket = $socket;
	}

	public function write($message) {
		$message = self::_create($message);

		$written = 0;
		$total = strlen($message);

		while($written < $total) {
			$write = [$this->_socket->getSocketResource()];
			$changes = socket_select($read, $write, $except, 10);
			if($changes === false) return false;
			if($changes <= 0) continue;
			$chunk = substr($message, $written, self::WRITE_CHUNK);
			$chunk_length = strlen($chunk);
			if(($written_length = @socket_write($this->_socket->getSocketResource(), $chunk, $chunk_length)) === false) return false;
			$written += $written_length;
		}

		return ($total == $written);
	}

	public function onMessageReady($message) {
		$this->write($message);
	}

	/**
	 * @param $message
	 *
	 * @return string
	 */
	protected static function _create($message) {
		$del = self::packInt(self::DELIMITER);
		$len = self::packInt(strlen($message));
		return $del . $len . $message . $len;
	}
}