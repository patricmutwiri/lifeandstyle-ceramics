<?php

namespace JetBackup\Destination;

use JetBackup\Data\ArrayData;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Entities\Util;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DestinationFile extends ArrayData implements iDestinationFile {
	
	const FILE_ID     = 'file_id';
	const FILES_DIR   = 'files_dir';
	const PATH        = 'path';
	const NAME        = 'name';
	const TYPE        = 'type';
	const SIZE        = 'size';
	const MTIME       = 'mtime';
	const OWNER       = 'owner';
	const GROUP       = 'group';
	const FILE_DATA   = 'file_data';
	const PERMISSIONS = 'permissions';
	const LINK_TARGET = 'link_target';
	
	/**
	 * @return string
	 */
	public function getPath(): string { return $this->get(self::PATH); }

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	public function setPath(string $path):void { $this->set(self::PATH, $path); }

	/**
	 * @return string
	 */
	public function getName(): string { return $this->get(self::NAME); }

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public function setName(string $name):void { $this->set(self::NAME, $name); }

	/**
	 * @return string
	 */
	public function getFullPath(): string { return preg_replace("/^\/+/", "", $this->getPath() . '/' . $this->getName()); }

	/**
	 * @return int
	 */
	public function getType(): int { return (int) $this->get(self::TYPE, 0); }

	/**
	 * @param int $type
	 *
	 * @return void
	 */
	public function setType(int $type):void { $this->set(self::TYPE, $type); }

	/**
	 * @return int
	 */
	public function getSize(): int { return (int) $this->get(self::SIZE, 0); }

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setSize(int $size):void { $this->set(self::SIZE, $size); }

	/**
	 * @return int
	 */
	public function getModifyTime(): int { return (int) $this->get(self::MTIME, 0); }

	/**
	 * @param int $time
	 *
	 * @return void
	 */
	public function setModifyTime(int $time):void { $this->set(self::MTIME, $time); }

	/**
	 * @return string
	 */
	public function getOwner(): string { return $this->get(self::OWNER, 'root'); }

	/**
	 * @param string $owner
	 *
	 * @return void
	 */
	public function setOwner(string $owner):void { $this->set(self::OWNER, $owner); }

	/**
	 * @return string
	 */
	public function getGroup(): string { return $this->get(self::GROUP, 'root'); }

	/**
	 * @param string $group
	 *
	 * @return void
	 */
	public function setGroup(string $group):void { $this->set(self::GROUP, $group); }

	/**
	 * @return int
	 */
	public function getPermissions():int { return $this->get(self::PERMISSIONS, 0000); }

	/**
	 * @param int $permissions
	 *
	 * @return void
	 */
	public function setPermissions(int $permissions):void { $this->set(self::PERMISSIONS, $permissions); }

	/**
	 * @return string
	 */
	public function getLinkTarget():string { return $this->get(self::LINK_TARGET); }

	/**
	 * @param string $target
	 *
	 * @return void
	 */
	public function setLinkTarget(string $target):void { $this->set(self::LINK_TARGET, $target); }

	/**
	 * @return string
	 */
	public function getFileId():string { return $this->get(self::FILE_ID); }

	/**
	 * @param string $id
	 *
	 * @return void
	 */
	public function setFileId(string $id):void { $this->set(self::FILE_ID, $id); }

	/**
	 * @return string
	 */
	public function getFilesDir():string { return $this->get(self::FILES_DIR); }

	/**
	 * @param string $dir
	 *
	 * @return void
	 */
	public function setFilesDir(string $dir):void { $this->set(self::FILES_DIR, $dir); }

	/**
	 * @return string
	 */
	public function getFileData():string { return $this->get(self::FILE_DATA); }

	/**
	 * @param string $data
	 *
	 * @return void
	 */
	public function setFileData(string $data):void { $this->set(self::FILE_DATA, $data); }

	/**
	 * @param array $file
	 *
	 * @return DestinationFile
	 */
	public static function genFile(array $file):DestinationFile {

		$item = new DestinationFile();

		if(isset($file['path'])) {
			$item->setPath(Util::mb_dirname($file['path']));
			$item->setName(Util::mb_basename($file['path']));
		}

		if(isset($file['perms'])) {
			$type = self::TYPE_UNKNOWN;
			$item->setPermissions(/*sprintf("0%o", (0777 & $file['perms']))*/$file['perms']);
			switch($file['perms'] & 0170000) {
				case 0100000: $type = self::TYPE_FILE; break;
				case 0120000: $type = self::TYPE_LINK; break;
				case 0040000: $type = self::TYPE_DIRECTORY; break;
				case 0060000: $type = self::TYPE_BLOCK; break;
				case 0010000: $type = self::TYPE_FIFO; break;
			}
			$item->setType($type);
		}

		if(isset($file['size'])) $item->setSize($file['size']);
		if(isset($file['mtime'])) $item->setModifyTime(intval($file['mtime']));
		if(isset($file['user'])) $item->setOwner($file['user']);
		if(isset($file['group'])) $item->setGroup($file['group']);
		if(isset($file['link'])) $item->setLinkTarget($file['link']);
		if(isset($file['file_id'])) $item->setFileId($file['file_id']);
		if(isset($file['files_dir'])) $item->setFilesDir($file['files_dir']);
		if(isset($file['data'])) $item->setFileData($file['data']);

		return $item;
	}
}