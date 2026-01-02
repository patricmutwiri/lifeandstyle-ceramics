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
namespace JetBackup\Archive\Data;

class SetterGetter {

	private $_data;
	public function __construct($data=[]) {
		$this->_data = $data;
	}

	protected function set(string $key,  $value):void { $this->_data[$key] = $value; }
	protected function get(string $key,  $default='') { return $this->_data[$key] ?? $default; }
}