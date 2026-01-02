<?php

namespace JetBackup\Data;

use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class DBObject extends ArrayData {
	
	private array $_data=[];
	private array $_find=[];
	private SleekStore $_db;

	/**
	 * @throws DBException
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function __construct($collection) {
		$this->_db = new SleekStore($collection);
	}

	public function getDB():SleekStore {
		return $this->_db;
	}
	
	public function margeData($data=[]):void {
		parent::margeData($data);
		$this->_data = array_merge($this->_data, $data);
	}

	public function setData($data=[]):void {
		parent::setData($data);
		$this->_data = [];
	}

	public function set($key, $value):void {
		parent::set($key, $value);
		$this->_data[$key] = $value;
	}

	private function _save_new():void {
		if(!($data = $this->getData())) return;
		unset($data[JetBackup::ID_FIELD]);
		$this->getDB()->clearCache();
		$ret = $this->getDB()->insert($data);
		$this->setId($ret[JetBackup::ID_FIELD]);
		$this->setFind([[JetBackup::ID_FIELD, '=', $ret[JetBackup::ID_FIELD]]]);
		$this->_data = [];
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function delete():void {
		if(!$this->getId()) return;
		$this->getDB()->clearCache();
		$this->getDB()->deleteById($this->getId());
		$this->setData();
	}

	/**
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function save():void {
		if($this->getId()) $this->setFind([[JetBackup::ID_FIELD, '=', $this->getId()]]);

		if(!$this->_find) {
			$this->_save_new();
			return;
		}

		if(!$this->_data) return;
		$this->getDB()->clearCache();
		$this->getDB()->createQueryBuilder()->where($this->_find)->getQuery()->update($this->_data);
		$this->_data = [];
	}
	
	public function setId(int $id):void { $this->set(JetBackup::ID_FIELD, $id); }
	public function getId():int { return (int) $this->get(JetBackup::ID_FIELD, 0); }

	public function setFind(array $find):void { $this->_find = $find; }

	/**
	 * @throws InvalidArgumentException
	 */
	protected function _loadById(int $id):void {
		$data = $this->getDB()->findById($id);
		if(!$data) return;
		$this->setData($data);
		//$this->_load([[JetBackup::ID_FIELD, '=', $id]]);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function load():void {
		$query = $this->_db->createQueryBuilder();
		if($this->_find) foreach($this->_find as $where) $query->where($where);
		$data = $query->getQuery()->first();
		if(!$data) return;
		$this->setData($data);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	protected function _load(array $find):void {
		$this->setFind($find);
		$this->load();
		if($this->getId()) $this->setFind([[JetBackup::ID_FIELD, '=', $this->getId()]]);
		else $this->setFind([]);
	}

}