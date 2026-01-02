<?php

namespace JetBackup\ResumableTask;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ResumableTaskItem {
	private $_data=null;
	private $_result=null;
	private bool $_status=false;

	public function setData($data) { $this->_data = $data; }
	public function getData() { return $this->_data; }

	public function setResult($result) { $this->_result = $result; }
	public function getResult() { return $this->_result; }

	public function setCompleted(bool $status) { $this->_status = !!$status; }
	public function isCompleted():bool { return $this->_status; }
}