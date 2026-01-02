<?php

namespace JetBackup\JetBackupLinux;

use JetBackup\Data\ArrayData;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\SocketAPI\Exception\SocketAPIException;
use JetBackup\SocketAPI\SocketAPI;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class JetBackupLinuxObject extends ArrayData {

	const ID_FIELD = '_id';
	
	private string $_item;
	private array $_data=[];
	
	public function __construct($item) {
		$this->_item = $item;
	}
	
	public function set($key, $value):void {
		parent::set($key, $value);
		$this->_data[$key] = $value;
	}

	public function setData($data=[]):void {
		parent::setData($data);
		$this->_data = [];
	}

	public function setId(string $id):void { $this->set(self::ID_FIELD, $id); }
	public function getId():string { return $this->get(self::ID_FIELD); }

	/**
	 * @param string $id
	 *
	 * @return void
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	protected function load(string $id):void {
		if(!JetBackupLinux::isEnabled()) return;

		$query = SocketAPI::api( 'get' . $this->_item);
		$query->arg(self::ID_FIELD, $id);

		try {
			$response = $query->execute();
		} catch(SocketAPIException $e) {
			throw new JetBackupLinuxException($e->getMessage());
		}
		
		if(!$response['success']) throw new JetBackupLinuxException($response['message']);
		$this->setData($response['data']);
	}

	/**
	 * @return void
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	public function save():void {
		if(!JetBackupLinux::isEnabled()) return;

		$query = SocketAPI::api( 'manage' . $this->_item);
		if($this->getId()) $query->arg(self::ID_FIELD, $this->getId());
		$query->arg('action', $this->getId() ? 'modify' : 'create');

		foreach($this->_data as $key => $value) $query->arg($key, $value);

		try {
			$response = $query->execute();
		} catch(SocketAPIException $e) {
			throw new JetBackupLinuxException($e->getMessage());
		}

		if(!$response['success']) throw new JetBackupLinuxException($response['message']);
		$this->setData($response['data']);
	}

	/**
	 * @return void
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	public function delete():void {
		if(!JetBackupLinux::isEnabled() || !$this->getId()) return;

		$query = SocketAPI::api( 'delete' . $this->_item);
		$query->arg(self::ID_FIELD, $this->getId());

		try {
			$query->execute();
		} catch(SocketAPIException $e) {
			throw new JetBackupLinuxException($e->getMessage());
		}

		$this->setData();
	}
}