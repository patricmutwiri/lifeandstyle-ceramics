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
namespace JetBackup\SocketAPI\Client;

use JetBackup\Entities\Util;
use JetBackup\SocketAPI\Exception\ClientException;
use JetBackup\SocketAPI\Exception\MessageException;
use JetBackup\SocketAPI\Exception\SocketException;
use JetBackup\SocketAPI\Exception\WellKnownException;
use JetBackup\SocketAPI\Message\Message;
use JetBackup\SocketAPI\Message\MessageReader;
use JetBackup\SocketAPI\Message\MessageWriter;
use JetBackup\SocketAPI\Protocol\ProtocolListener;
use JetBackup\SocketAPI\Socket\Socket;

class Client implements ProtocolListener {
	
	const SOCKET_FILE = '/usr/local/jetapps/tmp/jetbackup5/api.sock';

	private $_details;
	/** @var Socket */
	private $_socket;
	/** @var MessageReader */
	private $_reader;
	/** @var MessageWriter */
	private $_writer;

	private $_status;
	private $_message;
	private $_operation;

	/**
	 * @throws ClientException
	 */
	public function __construct() {

		$this->_details = Util::getpwuid(Util::geteuid());
		if(!$this->_details) throw new ClientException("Cannot get user details for socket (posix uid/gid)");
		$this->_openSocketConnection();
	}

	/**
	 * @return void
	 * @throws ClientException
	 */
	private function _openSocketConnection() {

		try {
			$this->_socket = new Socket();
			$this->_socket->setFile(self::SOCKET_FILE);
			$this->_socket->connect();

			$this->_reader = $this->_socket->getReader();
			$this->_writer = $this->_socket->getWriter();

			$this->_reader->addListener($this);
		} catch(SocketException $e) {
			throw new ClientException($e->getMessage(), 1);
		}
	}

	/**
	 * @param string $msg
	 *
	 * @return string
	 * @throws ClientException
	 */
	public function send($msg='') {

		$this->_status = null;
		$this->_message = null;
		$this->_operation = true;

		try {
			$password = WellKnown::getPassword();
		} catch(WellKnownException $e) {
			throw new ClientException($e->getMessage());
		}
		
		while(true) {
			try {
				if(!$this->_writer->write(base64_encode("{$this->_details['name']}|$password|$msg")))
					throw new MessageException("Failed to write to socket");

				while($this->_operation) {
					$length = $this->_reader->read();
					$this->_reader->checkMessage();
					if($this->_operation && $length <= 0) throw new MessageException("Connection to socket closed");
				}

				break;
			} catch(MessageException $e) {
				$this->_socket->close();
				$this->_openSocketConnection();
				throw new ClientException($e->getMessage(), 1);
			}
		}

		if($this->_status > 0) throw new ClientException($this->_message, $this->_status);
		return $this->_message;
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function onMessageReady($message) {
		$this->_status = Message::unpackInt($message);
		$this->_message = substr($message, 4);
		$this->_operation = false;
	}

	public function close() {
		$this->_socket->close();
		unset($this->_socket);
	}
}
