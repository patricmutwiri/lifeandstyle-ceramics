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
namespace JetBackup\Archive\Header\Sparse;

use JetBackup\Archive\Data\SetterGetter;
use JetBackup\Archive\Header\Header;

class SparseRegion extends SetterGetter {

	const LENGTH_OFFSET = 12;
	const LENGTH_NUMBYTES = 12;
	const REGION_LENGTH = self::LENGTH_OFFSET + self::LENGTH_NUMBYTES;

	const OFFSET_OFFSET = 0;
	const OFFSET_NUMBYTES = 12;

	const 
		OFFSET      = 'offset',
		NUMBYTES    = 'numbytes';
	
	public function setOffset($offset, $octal=true) { $this->set(self::OFFSET, $octal ? octdec(trim($offset)) : (int) $offset); }
	public function getOffset($octal=true) { return Header::_getDecOct($this->get(self::OFFSET), $octal, self::LENGTH_OFFSET); }

	public function setNumbytes($bytes, $octal=true) { $this->set(self::NUMBYTES, $octal ? octdec(trim($bytes)) : (int) $bytes); }
	public function getNumbytes($octal=true) { return Header::_getDecOct($this->get(self::NUMBYTES), $octal, self::LENGTH_NUMBYTES); }
	
	public static function fromData($data):? SparseRegion {
		if(!trim($data) || strlen($data) != self::REGION_LENGTH) return null;
		$part = new SparseRegion();
		$part->setOffset(substr($data, self::OFFSET_OFFSET, self::LENGTH_OFFSET));
		$part->setNumbytes(substr($data, self::OFFSET_NUMBYTES, self::LENGTH_NUMBYTES));
		return $part;
	}
}