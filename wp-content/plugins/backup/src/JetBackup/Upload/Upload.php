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
namespace JetBackup\Upload;

use JetBackup\Data\DBObject;
use JetBackup\Data\SleekStore;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use SleekDB\QueryBuilder;

defined("__JETBACKUP__") or die("Restricted Access.");

class Upload extends DBObject {

	const COLLECTION = 'upload';

	const UNIQUE_ID = 'unique_id';
	const FILENAME = 'filename';
	const CREATED = 'created';
	const SIZE = 'size';
	const UPLOADED = 'uploaded';
	
	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
	}

	public function loadByUploadId($unique_id) {
		$this->_load([[self::UNIQUE_ID, '=', $unique_id]]);
	}
	
	public function setUniqueId(string $id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setFilename(string $name) { $this->set(self::FILENAME, $name); }
	public function getFilename():string { return $this->get(self::FILENAME); }

	public function setCreated(int $created) { $this->set(self::CREATED, $created); }
	public function getCreated():int { return $this->get(self::CREATED, 0); }

	public function setSize(int $size) { $this->set(self::SIZE, $size); }
	public function getSize():int { return $this->get(self::SIZE, 0); }

	public function setUploaded(int $uploaded) { $this->set(self::UPLOADED, $uploaded); }
	public function getUploaded():int { return $this->get(self::UPLOADED, 0); }

	/**
	 * @return string
	 * @throws IOException
	 */
	public function getFileLocation():string {
		if(!$this->getUniqueId()) throw new IOException("No upload id was found");
		$directory = Factory::getLocations()->getTempDir() . JetBackup::SEP . $this->getUniqueId();
		if(!is_dir($directory)) mkdir($directory, 0700);
		Util::secureFolder($directory);
		return $directory . JetBackup::SEP . $this->getFilename();
	}

	/**
	 * @param string $data
	 *
	 * @return void
	 * @throws IOException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws \SleekDB\Exceptions\InvalidArgumentException
	 */
	public function writeChunk(string $file):void {
		if($this->isCompleted()) throw new IOException("This file is already fully uploaded");
		if(!file_exists($file)) throw new IOException("The provided chunk file does not exist");

		$length = filesize($file);
		$uploaded = $this->getUploaded();

		if($uploaded + $length > $this->getSize()) throw new IOException("File data provided is exceeded the provided file size");

		$target = $this->getFileLocation();
		
		$fd = fopen($target, 'a');
		if(!$fd) throw new IOException("Failed to open file '$target'");
		fseek($fd, $uploaded);

		$file_fd = fopen($file, 'rb');
		if(!$file_fd) throw new IOException("Failed to open chunk file '$file'");

		while(!feof($file_fd)) fwrite($fd, fread($file_fd, 1024));
		
		fclose($fd);
		fclose($file_fd);

		$this->setUploaded($uploaded + $length);
		$this->save();
	}
	
	public function isCompleted():bool {
		return $this->getUploaded() >= $this->getSize();
	}

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		parent::save();
	}
}