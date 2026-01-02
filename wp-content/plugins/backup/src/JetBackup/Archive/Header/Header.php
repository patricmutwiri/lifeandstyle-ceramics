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
namespace JetBackup\Archive\Header;

use JetBackup\Archive\Archive;
use JetBackup\Archive\Data\SetterGetter;
use JetBackup\Archive\Header\Sparse\Sparse;
use JetBackup\Archive\Header\Sparse\SparseRegion;
use JetBackup\Exception\ArchiveException;

class Header extends SetterGetter {

	const REGTYPE = '0'; // regular file
	const AREGTYPE = '\0'; // regular file
	const LNKTYPE = '1'; // link
	const SYMTYPE = '2'; // reserved (symlink)
	const CHRTYPE = '3'; // character special
	const BLKTYPE = '4'; // block special
	const DIRTYPE = '5'; // directory
	const FIFOTYPE = '6'; // FIFO special
	const CONTTYPE = '7'; // reserved
	const XHDTYPE = 'x'; // Extended header referring to the next file in the archive
	const XGLTYPE = 'g'; // Global extended header

	/* This is a dir entry that contains the names of files that were in the
	   dir at the time the dump was made.  */
	const GNUTYPE_DUMPDIR = 'D';

	/* Identifies the *next* file on the tape as having a long linkname.  */
	const GNUTYPE_LONGLINK = 'K';

	/* Identifies the *next* file on the tape as having a long name.  */
	const GNUTYPE_LONGNAME = 'L';

	/* This is the continuation of a file that began on another volume.  */
	const GNUTYPE_MULTIVOL = 'M';

	/* This is for sparse files.  */
	const GNUTYPE_SPARSE = 'S';

	/* This file is a tape/volume header.  Ignore it on extraction.  */
	const GNUTYPE_VOLHDR = 'V';

	/* Solaris extended header */
	const SOLARIS_XHDTYPE = 'X';

	const LENGTH_FILENAME       = 100;
	const LENGTH_MODE           = 8;
	const LENGTH_UID            = 8;
	const LENGTH_GID            = 8;
	const LENGTH_SIZE           = 12;
	const LENGTH_MTIME          = 12;
	const LENGTH_CHECKSUM       = 8;
	const LENGTH_TYPEFLAG       = 1;
	const LENGTH_LINKNAME       = 100;
	const LENGTH_MAGIC          = 6;
	const LENGTH_VERSION        = 2;
	const LENGTH_UNAME          = 32;
	const LENGTH_GNAME          = 32;
	const LENGTH_DEVMAJOR       = 8;
	const LENGTH_DEVMINOR       = 8;
	const LENGTH_PREFIX         = 155;
	const LENGTH_PAD            = 12;

	const OFFSET_FILENAME       = 0;
	const OFFSET_MODE           = self::LENGTH_FILENAME;
	const OFFSET_UID            = self::LENGTH_FILENAME + self::LENGTH_MODE;
	const OFFSET_GID            = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID;
	const OFFSET_SIZE           = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID;
	const OFFSET_MTIME          = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID + 
									self::LENGTH_SIZE;
	const OFFSET_CHECKSUM       = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID + 
									self::LENGTH_SIZE + self::LENGTH_MTIME;
	const OFFSET_TYPEFLAG       = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID + 
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM;
	const OFFSET_LINKNAME       = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID + 
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG;
	const OFFSET_MAGIC          = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID + 
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG + 
									self::LENGTH_LINKNAME;
	const OFFSET_VERSION        = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID + 
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG + 
									self::LENGTH_LINKNAME + self::LENGTH_MAGIC;
	const OFFSET_UNAME          = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID +
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG +
									self::LENGTH_LINKNAME + self::LENGTH_MAGIC + self::LENGTH_VERSION;
	const OFFSET_GNAME          = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID +
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG +
									self::LENGTH_LINKNAME + self::LENGTH_MAGIC + self::LENGTH_VERSION + self::LENGTH_UNAME;
	const OFFSET_DEVMAJOR       = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID +
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG +
									self::LENGTH_LINKNAME + self::LENGTH_MAGIC + self::LENGTH_VERSION + self::LENGTH_UNAME + 
									self::LENGTH_GNAME;
	const OFFSET_DEVMINOR       = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID +
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG +
									self::LENGTH_LINKNAME + self::LENGTH_MAGIC + self::LENGTH_VERSION + self::LENGTH_UNAME +
									self::LENGTH_GNAME + self::LENGTH_DEVMAJOR;
	const OFFSET_PREFIX         = self::LENGTH_FILENAME + self::LENGTH_MODE + self::LENGTH_UID + self::LENGTH_GID +
									self::LENGTH_SIZE + self::LENGTH_MTIME + self::LENGTH_CHECKSUM + self::LENGTH_TYPEFLAG +
									self::LENGTH_LINKNAME + self::LENGTH_MAGIC + self::LENGTH_VERSION + self::LENGTH_UNAME +
									self::LENGTH_GNAME + self::LENGTH_DEVMAJOR + self::LENGTH_DEVMINOR;

	const 
		FILENAME    = 'filename',
		MODE        = 'mode',
		UID         = 'uid',
		GID         = 'gid',
		SIZE        = 'size',
		MTIME       = 'mtime',
		CHECKSUM    = 'checksum',
		TYPEFLAG    = 'typeflag',
		LINKNAME    = 'linkname',
		MAGIC       = 'magic',
		VERSION     = 'version',
		UNAME       = 'uname',
		GNAME       = 'gname',
		DEVMAJOR    = 'devmajor',
		DEVMINOR    = 'devminor',
		PREFIX      = 'prefix',
		SPARSE      = 'sparse';

	public function __construct($data=[]) {
		parent::__construct();
		if(isset($data[self::FILENAME])) $this->setFilename($data[self::FILENAME]);
		if(isset($data[self::MODE])) $this->setMode($data[self::MODE]);
		if(isset($data[self::UID])) $this->setUid($data[self::UID]);
		if(isset($data[self::GID])) $this->setGid($data[self::GID]);
		if(isset($data[self::SIZE])) $this->setSize($data[self::SIZE]);
		if(isset($data[self::MTIME])) $this->setMtime($data[self::MTIME]);
		if(isset($data[self::CHECKSUM])) $this->setChecksum($data[self::CHECKSUM]);
		if(isset($data[self::TYPEFLAG])) $this->setTypeFlag($data[self::TYPEFLAG]);
		if(isset($data[self::LINKNAME])) $this->setLinkName($data[self::LINKNAME]);
		if(isset($data[self::MAGIC])) $this->setMagic($data[self::MAGIC]);
		if(isset($data[self::VERSION])) $this->setVersion($data[self::VERSION]);
		if(isset($data[self::UNAME])) $this->setUname($data[self::UNAME]);
		if(isset($data[self::GNAME])) $this->setGname($data[self::GNAME]);
		if(isset($data[self::DEVMAJOR])) $this->setDevMajor($data[self::DEVMAJOR]);
		if(isset($data[self::DEVMINOR])) $this->setDevMinor($data[self::DEVMINOR]);
		if(isset($data[self::PREFIX])) $this->setPrefix($data[self::PREFIX]);
		if(isset($data[self::SPARSE])) $this->setSparse(new Sparse($data[self::SPARSE]));
	}
	
	public function setFilename($filename): void { $this->set(self::FILENAME, $filename); }
	public function getFilename(): string { return $this->get(self::FILENAME); }

	public function setMode($mode, $octal=true): void {


		$this->set(self::MODE, $octal ? octdec(trim($mode)) : (int) $mode);

	}
	public function getMode($octal=true) { return self::_getDecOct($this->get(self::MODE), $octal, 7); }

	public function setUid($uid, $octal=true): void { $this->set(self::UID, $octal ? octdec(trim($uid)) : (int) $uid); }
	public function getUid($octal=true) { return self::_getDecOct($this->get(self::UID), $octal, 7); }

	public function setGid($gid, $octal=true): void { $this->set(self::GID, $octal ? octdec(trim($gid)) : (int) $gid); }
	public function getGid($octal=true) { return self::_getDecOct($this->get(self::GID), $octal, 7); }

	public function setSize($size, $octal=true): void {

		$this->set(self::SIZE, $octal ? octdec(trim($size)) : (int) $size);

	}
	public function getSize($octal=true) { return self::_getDecOct($this->get(self::SIZE), $octal, 11); }

	public function setMtime($mtime, $octal=true): void { $this->set(self::MTIME, $octal ? octdec(trim($mtime)) : (int) $mtime); }
	public function getMtime($octal=true) { return self::_getDecOct($this->get(self::MTIME), $octal, 11); }

	public function setChecksum($checksum, $octal=true): void { $this->set(self::CHECKSUM, $octal ? octdec(trim($checksum)) : (int) $checksum); }
	public function getChecksum($octal=true) { return self::_getDecOct($this->get(self::CHECKSUM), $octal, 6); }

	public function setTypeFlag(string $flag): void { $this->set(self::TYPEFLAG, $flag); }
	public function getTypeFlag(): string { return trim($this->get(self::TYPEFLAG)); }

	public function setLinkName($name): void { $this->set(self::LINKNAME, $name); }
	public function getLinkName(): string { return trim($this->get(self::LINKNAME)); }

	public function setMagic($magic): void { $this->set(self::MAGIC, $magic); }
	public function getMagic(): string { return trim($this->get(self::MAGIC)); }

	public function setVersion($version): void { $this->set(self::VERSION, $version); }
	public function getVersion(): string { return trim($this->get(self::VERSION)); }

	public function setUname($name): void { $this->set(self::UNAME, $name); }
	public function getUname(): string { return trim($this->get(self::UNAME)); }

	public function setGname($name): void { $this->set(self::GNAME, $name); }
	public function getGname(): string { return trim($this->get(self::GNAME)); }

	public function setDevMajor($device, $octal=true): void { $this->set(self::DEVMAJOR, $octal ? octdec(trim($device)) : (int) $device); }
	public function getDevMajor($octal=true) { return self::_getDecOct($this->get(self::DEVMAJOR), $octal, 7); }

	public function setDevMinor($device, $octal=true): void { $this->set(self::DEVMINOR, $octal ? octdec(trim($device)) : (int) $device); }
	public function getDevMinor($octal=true) { return self::_getDecOct($this->get(self::DEVMINOR), $octal, 7); }

	public function setPrefix($prefix): void { $this->set(self::PREFIX, $prefix); }
	public function getPrefix(): string { return $this->get(self::PREFIX); }

	public function setSparse( ?Sparse $sparse=null): void { $this->set(self::SPARSE, $sparse); }
	public function getSparse():? Sparse { return $this->get(self::SPARSE, null); }

	/**
	 * @return void
	 */
	public function buildPrefix(): void {
		if($this->getTypeFlag() != self::GNUTYPE_SPARSE || !($sparse = $this->getSparse())) return;

		$regions = $sparse->getRegions();

		$prefix = "";
		$prefix .= $sparse->getAtime();
		$prefix .= $sparse->getCtime();
		$prefix .= $sparse->getOffset();
		$prefix .= $sparse->getLongName();
		$prefix .= Archive::NULL_CHAR; // pad

		// we have space only for 4 regions, more than that will be written in the next blocks (each 21 regions block)
		for($i = 0; $i < 4; $i++) {
			$region = array_shift($regions);

			if($region) {
				$prefix .= $region->getOffset();
				$prefix .= $region->getNumbytes();
			} else {
				$prefix .= str_repeat(Archive::NULL_CHAR, SparseRegion::REGION_LENGTH);
			}
		}

		$prefix .= sizeof($regions) ? Archive::TRUE_CHAR : Archive::NULL_CHAR; // extended
		$prefix .= $sparse->getRealSize();
		$prefix .= str_repeat(Archive::NULL_CHAR, Sparse::LENGTH_PAD_END);

		$sparse->setRegions($regions);
		$this->setPrefix($prefix);
	}

	/**
	 * @return string
	 */
	public function pack(): string {
		$pack =  pack("a" . self::LENGTH_FILENAME,      $this->getFilename()); //name
		$pack .= pack("a" . self::LENGTH_MODE,          $this->getMode()); // mode
		$pack .= pack("a" . self::LENGTH_UID,           $this->getUid()); // uid
		$pack .= pack("a" . self::LENGTH_GID,           $this->getGid()); // gid
		$pack .= pack("a" . self::LENGTH_SIZE,          $this->getSize()); // size
		$pack .= pack("A" . self::LENGTH_MTIME,         $this->getMtime()); // mtime
		$pack .= pack("a" . self::LENGTH_CHECKSUM,      ""); // checksum
		$pack .= pack("a" . self::LENGTH_TYPEFLAG,      $this->getTypeFlag()); // typeflag
		$pack .= pack("a" . self::LENGTH_LINKNAME,      $this->getLinkName()); // linkname
		$pack .= pack("a" . self::LENGTH_MAGIC,         $this->getMagic()); // magic
		$pack .= pack("a" . self::LENGTH_VERSION,       $this->getVersion()); // version
		$pack .= pack("a" . self::LENGTH_UNAME,         $this->getUname()); // uname
		$pack .= pack("a" . self::LENGTH_GNAME,         $this->getGname()); // gname
		$pack .= pack("a" . self::LENGTH_DEVMAJOR,      $this->getDevMajor()); // devmajor
		$pack .= pack("a" . self::LENGTH_DEVMINOR,      $this->getDevMinor()); // devminor
		$pack .= pack("a" . self::LENGTH_PREFIX,        $this->getPrefix()); // prefix
		$pack .= pack("a" . self::LENGTH_PAD,           ""); // pad

		$this->setChecksum(self::calculateChecksum($pack), false);
		$checksum = pack('a' . self::LENGTH_CHECKSUM, $this->getChecksum());
		return substr_replace($pack, $checksum, self::OFFSET_CHECKSUM, self::LENGTH_CHECKSUM);
	}

	/**
	 * @param mixed $data
	 * @param callable $readDataBlock
	 *
	 * @return Header
	 * @throws ArchiveException
	 */
	public static function parse($data, callable $readDataBlock, bool $debug=false): Header {

		if($data === null || $data === false)
			throw new ArchiveException("Failed reading header");

		if(strlen($data) < Archive::BLOCK_SIZE)
			throw new ArchiveException("Header length is invalid");

		$args = [
			"a" . self::LENGTH_FILENAME . self::FILENAME,
			"a" . self::LENGTH_MODE . self::MODE,
			"a" . self::LENGTH_UID . self::UID,
			"a" . self::LENGTH_GID . self::GID,
			"a" . self::LENGTH_SIZE . self::SIZE,
			"A" . self::LENGTH_MTIME . self::MTIME,
			"a" . self::LENGTH_CHECKSUM . self::CHECKSUM,
			"a" . self::LENGTH_TYPEFLAG . self::TYPEFLAG,
			"a" . self::LENGTH_LINKNAME . self::LINKNAME,
			"a" . self::LENGTH_MAGIC . self::MAGIC,
			"a" . self::LENGTH_VERSION . self::VERSION,
			"a" . self::LENGTH_UNAME . self::UNAME,
			"a" . self::LENGTH_GNAME . self::GNAME,
			"a" . self::LENGTH_DEVMAJOR . self::DEVMAJOR,
			"a" . self::LENGTH_DEVMINOR . self::DEVMINOR,
			"a" . self::LENGTH_PREFIX . self::PREFIX
		];

		if(!($header_data = unpack(implode("/", $args), $data)))
			throw new ArchiveException("Failed parsing header");

		if(isset($header_data[self::CHECKSUM]) && octdec(trim($header_data[self::CHECKSUM])) != self::calculateChecksum($data))
			throw new ArchiveException("Header does not match its checksum for account '{$header_data[self::FILENAME]}'");

		$header = new Header($header_data);

		if($header->getTypeFlag() == Header::GNUTYPE_SPARSE) 
			$header->setSparse(Sparse::fromPrefix($header->getPrefix(), $readDataBlock));

		if($debug) Archive::printHeader($header);
		
		return $header;
	}

	/**
	 * @param string $data
	 *
	 * @return int
	 */
	public static function calculateChecksum(string $data): int {
		$checksum = 256;
		for ($i = 0; $i < Archive::BLOCK_SIZE; $i++) {
			// skip checksum, not should be in the checksum calculation
			if($i >= self::OFFSET_CHECKSUM && $i < (self::OFFSET_CHECKSUM + self::LENGTH_CHECKSUM)) continue;
			$checksum += ord($data[$i]);
		}
		return $checksum;		
	}

	/**
	 * @param mixed $value
	 * @param bool $octal
	 * @param int $octal_length
	 *
	 * @return int|string
	 */
	public static function _getDecOct( $value, bool $octal, int $octal_length=0) {
		if(!$octal_length) $octal = false;
		if(!$octal) return (int) $value;
		if(!is_int($value)) return str_repeat(Archive::NULL_CHAR, $octal_length);
		return sprintf("%0{$octal_length}o", $value);
	}
}