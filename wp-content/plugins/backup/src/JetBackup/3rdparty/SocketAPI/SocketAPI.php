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
namespace JetBackup\SocketAPI;

use JetBackup\SocketAPI\Client\Client;
use JetBackup\SocketAPI\Exception\ClientException;
use JetBackup\SocketAPI\Exception\SocketAPIException;

class SocketAPI {

	private $_data;
	private string $_function;

	public function __construct($function) {
		$this->_function = $function;
	}
	/**
	 * @return array
	 * @throws SocketAPIException
	 */
	public function execute() {
		try {
			$message = [];
			$message['function'] = $this->_function;
			if($this->_data) $message['data'] = $this->_data;
			
			$client = new Client();
			$response = json_decode($client->send(json_encode($message)), true);
			$client->close();
			if($response === false) throw new ClientException("Invalid response");
			return $response;
		} catch(ClientException $e) {
			throw new SocketAPIException($e->getMessage());
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function arg($key, $value) {
		$this->_data[$key] = $value;
		return $this;
	}

	/**
	 * @param int $limit
	 * @param int $skip
	 *
	 * @return $this
	 */
	public function limit($limit, $skip=0) {
		$this->_data['limit'] = $limit;
		$this->_data['skip'] = $skip;
		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function sortAsc($key) {
		$this->_data['sort'][$key] = 1;
		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function sortDesc($key) {
		$this->_data['sort'][$key] = -1;
		return $this;
	}

	/**
	 * @return SocketAPI
	 * @throws SocketAPIException
	 */
	public static function api($function):SocketAPI {
		if(!function_exists('socket_connect')) 
			throw new SocketAPIException("The function socket_connect not installed or disabled within your PHP.");
		
		return new SocketAPI($function);
	}
}
