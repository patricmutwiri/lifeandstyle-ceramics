<?php

namespace JetBackup\Data;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * array Data is class to easy array manipulations.
 */
class ArrayData{

	/** @var array the data holded by this object */
	private array $_data=[];
	
	/**
	 * Set data for this object.
	 * Data is key=>value array.
	 * @param array $data the data to set
	 */
	public function setData($data=[]){
		$this->_data = $data;
	}

	/**
	 * Marge data for this object
	 * @param array $data the data to marge with
	 */
	public function margeData($data=[]){
		$this->_data = array_merge($this->_data, $data);
	}

	/**
	 * Set the value held under the given key.
	 * @param String $key the key for the value
	 * @param Mixed $value the value to set.
	 */
	public function set($key, $value){
		$this->_data[$key] = $value;
	}

	/**
	 * Get value held under the given key.
	 * @param String $key the key for the value.
	 * @param Mixed $default the default value if the key not found.
	 * @return Mixed the value under the specified key.
	 */
	public function get($key, $default=''){
		return $this->_data[$key] ?? $default;
	}

	/**
	 * Get the array (byval) holded by this instance.
	 * @return array the array (byval) holded by this instance.
	 */
	public function getData():array{
		return $this->_data;
	}
}