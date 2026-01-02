<?php

namespace JetBackup\License;

use JetBackup\Factory;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class LicenseLocalKey {

	private $_localKey;
	private $_signed;
	private $_signed_status;
	private $_status;
	private $_description;

	public function __construct($localKey=null) {
		if(!$localKey) $localKey = Factory::getConfig()->getLicenseLocalKey();
		$this->_localKey = $localKey;
		$this->_parseLocalKey();
	}

	private function setSigned($signed) { $this->_signed = $signed; }
	public function getSigned() { return $this->_signed; }
	private function setSignedStatus($status) { $this->_signed_status = $status; }
	public function getSignedStatus() { return $this->_signed_status; }
	private function setStatus($status) { $this->_status = $status; }
	public function getStatus() { return $this->_status; }
	private function setDescription($description) { $this->_description = $description; }
	public function getDescription() { return $this->_description; }
	public function getLocalKey() { return $this->_localKey; }

	private function _parseLocalKey() {
		if(!$this->getLocalKey()) return;
		list($signed, $signed_status, $status, $description) = explode("|", $this->getLocalKey(), 4);
		if($signed) $this->setSigned($signed);
		if($signed_status) $this->setSignedStatus($signed_status);
		if($status) $this->setStatus($status);
		if($description) $this->setDescription($description);
	}


}