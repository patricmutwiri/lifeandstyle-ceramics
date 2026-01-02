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

use JetBackup\SocketAPI\Protocol\Protocol;
use JetBackup\SocketAPI\Protocol\ProtocolListener;

class Message implements Protocol {

	const MAX_UINT32 = 4294967295;
	const MAX_MESSAGE_LENGTH = 1099511627776; // 10MB
	const DELIMITER = 369332131;
	
	/** @var ProtocolListener[] */
	protected $_protocol_listener;

	public function __construct() {
		$this->_protocol_listener = [];
	}

	/**
	 * @param ProtocolListener $listener
	 *
	 * @return void
	 */
	public function addListener(ProtocolListener $listener) {
		$this->_protocol_listener[] = $listener;
	}

	/**
	 * @param $data
	 *
	 * @return false|string
	 */
	public static function packInt($data) { return pack("N", $data); }

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public static function unpackInt($data) { return unpack("N", $data)[1]; }
}