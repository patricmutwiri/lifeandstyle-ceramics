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

use JetBackup\Archive\Archive;
use JetBackup\Archive\Data\SetterGetter;
use JetBackup\Archive\Header\Header;

class Sparse extends SetterGetter {

	const LENGTH_ATIME              = 12;
	const LENGTH_CTIME              = 12;
	const LENGTH_OFFSET             = 12;
	const LENGTH_LONGNAME           = 4;
	const LENGTH_PAD                = 1;
	const LENGTH_REGIONS            = (SparseRegion::REGION_LENGTH * 4);
	const LENGTH_EXTENDED           = 1;
	const LENGTH_REALSIZE           = 12;
	const LENGTH_PAD_END            = 5;
	const LENGTH_EXTENDED_REGIONS   = (SparseRegion::REGION_LENGTH * 21);
	const LENGTH_EXTENDED_PAD       = 7;

	const OFFSET_ATIME              = 0;
	const OFFSET_CTIME              = self::LENGTH_ATIME;
	const OFFSET_OFFSET             = self::LENGTH_ATIME + self::LENGTH_CTIME;
	const OFFSET_LONGNAME           = self::LENGTH_ATIME + self::LENGTH_CTIME + self::LENGTH_OFFSET;
	const OFFSET_REGIONS            = self::LENGTH_ATIME + self::LENGTH_CTIME + self::LENGTH_OFFSET + self::LENGTH_LONGNAME + 
										self::LENGTH_PAD;
	const OFFSET_EXTENDED           = self::LENGTH_ATIME + self::LENGTH_CTIME + self::LENGTH_OFFSET + self::LENGTH_LONGNAME + 
										self::LENGTH_PAD + self::LENGTH_REGIONS;
	const OFFSET_REALSIZE           = self::LENGTH_ATIME + self::LENGTH_CTIME + self::LENGTH_OFFSET + self::LENGTH_LONGNAME + 
										self::LENGTH_PAD + self::LENGTH_REGIONS + self::LENGTH_EXTENDED;
	const OFFSET_EXTENDED_REGIONS   = 0;
	const OFFSET_EXTENDED_EXTENDED  = self::LENGTH_EXTENDED_REGIONS;
	
	const 
		ATIME       = 'atime',
		CTIME       = 'ctime',
		OFFSET      = 'offset',
		LONGNAME    = 'longname',
		REALSIZE    = 'realsize',
		REGIONS     = 'regions';
	
	public function __construct($data=[]) {
		parent::__construct($data);
		if(isset($data[self::REGIONS])) foreach($data[self::REGIONS] as $part) $this->addRegion(new SparseRegion($part));
	}

	public function setAtime($time, $octal=true) { $this->set(self::ATIME, $octal ? octdec(trim($time)) : (int) $time); }
	public function getAtime($octal=true) { return Header::_getDecOct($this->get(self::ATIME), $octal, self::LENGTH_ATIME); }

	public function setCtime($time, $octal=true) { $this->set(self::CTIME, $octal ? octdec(trim($time)) : (int) $time); }
	public function getCtime($octal=true) { return Header::_getDecOct($this->get(self::CTIME), $octal, self::LENGTH_CTIME); }

	public function setOffset($offset, $octal=true) { $this->set(self::OFFSET, $octal ? octdec(trim($offset)) : (int) $offset); }
	public function getOffset($octal=true) { return Header::_getDecOct($this->get(self::OFFSET), $octal, self::LENGTH_OFFSET); }

	public function setLongName($name, $octal=true) { $this->set(self::LONGNAME, $octal ? octdec(trim($name)) : (int) $name); }
	public function getLongName($octal=true) { return Header::_getDecOct($this->get(self::LONGNAME), $octal, self::LENGTH_LONGNAME); }

	public function setRealSize($size, $octal=true) { $this->set(self::REALSIZE, $octal ? octdec(trim($size)) : (int) $size); }
	public function getRealSize($octal=true) { return Header::_getDecOct($this->get(self::REALSIZE), $octal, self::LENGTH_REALSIZE); }
	
	public function setRegions($regions) { $this->set(self::REGIONS, $regions); }

	/**
	 * @return SparseRegion[]
	 */
	public function getRegions(): array { return $this->get(self::REGIONS, []); }

	/**
	 * @param SparseRegion $region
	 *
	 * @return void
	 */
	public function addRegion(SparseRegion $region) { 
		$parts = $this->getRegions();
		$parts[] = $region;
		$this->setRegions($parts);
	}
	
	public static function fromPrefix($prefix, callable $readDataBlock):? Sparse {
		if(!trim($prefix) || strlen($prefix) != Header::LENGTH_PREFIX) return null;
		$sparse = new Sparse();
		$sparse->setAtime(substr($prefix, self::OFFSET_ATIME, self::LENGTH_ATIME));
		$sparse->setCtime(substr($prefix, self::OFFSET_CTIME, self::LENGTH_CTIME));
		$sparse->setOffset(substr($prefix, self::OFFSET_OFFSET, self::LENGTH_OFFSET));
		$sparse->setLongName(substr($prefix, self::OFFSET_LONGNAME, self::LENGTH_LONGNAME));
		$sparse->setRealSize(substr($prefix, self::OFFSET_REALSIZE, self::LENGTH_REALSIZE));

		$regions_data = substr($prefix, self::OFFSET_REGIONS, self::LENGTH_REGIONS);

		$extended = trim(substr($prefix, self::OFFSET_EXTENDED, self::LENGTH_EXTENDED));
		while($extended) {
			$extended_data = $readDataBlock();
			$regions_data .= substr($extended_data, self::OFFSET_EXTENDED_REGIONS, self::LENGTH_EXTENDED_REGIONS);
			$extended = trim(substr($extended_data, self::OFFSET_EXTENDED_EXTENDED, self::LENGTH_EXTENDED));
		}

		$objects = (strlen($regions_data) / SparseRegion::REGION_LENGTH);

		for($i = 0; $i < $objects; $i++) {
			if(!($region = SparseRegion::fromData(substr($regions_data, $i * SparseRegion::REGION_LENGTH, SparseRegion::REGION_LENGTH)))) continue;
			$sparse->addRegion($region);
		}
		
		return $sparse;
	}
	
	public function buildExtended(callable $writeExtended) {

		// write the extra sparse regions
		$regions = $this->getRegions();
		$sparse_data = '';
		while($regions) {
			$region = array_shift($regions);

			$sparse_data .= $region->getOffset();
			$sparse_data .= $region->getNumbytes();

			// 504 bytes is the max we can add to 1 block
			if(strlen($sparse_data) == Sparse::LENGTH_EXTENDED_REGIONS || !sizeof($regions)) {
				$sparse_data .= sizeof($regions) ? Archive::TRUE_CHAR : Archive::NULL_CHAR; // extended
				$sparse_data .= str_repeat(Archive::NULL_CHAR, Sparse::LENGTH_EXTENDED_PAD); // pad
				$writeExtended($sparse_data);
				$sparse_data = '';
			}
		}
	}
}