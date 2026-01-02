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
namespace JetBackup\Destination\Vendors\Box\Client;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class File {
	
	private string $_id='';
	private string $_name='';
	private int $_size=0;
	private string $_mimeType='';
	private int $_mtime=0;
	private int $_ctime=0;
	private string $_sha1Checksum='';

	public function setId(string $id):void { $this->_id = $id; }
	public function getId():string { return $this->_id; }

	public function setName(string $name):void { $this->_name = $name; }
	public function getName():string { return $this->_name; }

	public function setSize(int $size):void { $this->_size = $size; }
	public function getSize():int { return $this->_size; }

	public function setMimeType(string $type):void { $this->_mimeType = $type; }
	public function getMimeType():string { return $this->_mimeType; }

	public function setModificationTime(int $time):void { $this->_mtime = $time; }
	public function getModificationTime():int { return $this->_mtime; }

	public function setCreationTime(int $time):void { $this->_ctime = $time; }
	public function getCreationTime():int { return $this->_ctime; }

	public function setSHA1Checksum(string $checksum):void { $this->_sha1Checksum = $checksum; }
	public function getSHA1Checksum():string { return $this->_sha1Checksum; }
}