<?php

namespace JetBackup\Download;

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\CLI\CLI;
use JetBackup\Data\DBObject;
use JetBackup\Data\SleekStore;
use JetBackup\Downloader\Downloader;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DownloaderException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Download extends DBObject {

	const COLLECTION = 'download';
	
	const CREATED = 'created';
	const FILENAME = 'filename';
	const SIZE = 'size';
	const LOCATION = 'location';

	/**
	 * @param int|null $_id
	 *
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public function __construct( ?int $_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById($_id);
	}

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setCreated(int $value):void { $this->set(self::CREATED, $value); }

	/**
	 * @return int
	 */
	public function getCreated():int { return $this->get(self::CREATED, 0); }

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	public function setFilename(string $value):void { $this->set(self::FILENAME, $value); }

	/**
	 * @return string
	 */
	public function getFilename():string { return $this->get(self::FILENAME); }

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setSize(int $size):void { $this->set(self::SIZE, $size); }

	/**
	 * @return int
	 */
	public function getSize():int { return $this->get(self::SIZE, 0); }

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	public function setLocation(string $value):void { $this->set(self::LOCATION, $value); }

	/**
	 * @return string
	 */
	public function getLocation():string { return $this->get(self::LOCATION); }

	/**
	 * @return SleekStore
	 */
	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	/**
	 * @return QueryBuilder
	 */
	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	/**
	 * @param int $ttl
	 *
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function deleteByTTL(int $ttl) : void {
		if (!$ttl) return;

		$items = self::query()
		               ->where([self::CREATED, '<', time() - $ttl])
		               ->getQuery()
		               ->fetch();
		if (empty($items)) return;
		foreach ($items as $item) {
			$download = new Download($item[JetBackup::ID_FIELD]);
			$file =  Factory::getLocations()->getDownloadsDir() . JetBackup::SEP . $download->getLocation();
			if (file_exists($file)) @unlink($file);
			Alert::add('System Cleanup', "Download item ". $download->getFilename() . " removed", Alert::LEVEL_INFORMATION);
			$download->delete();
		}
	}


	/**
	 * @return void
	 * @throws DownloaderException
	 */
	public function download():void {
		if (!$this->getFilename()) throw new DownloaderException('Download file not found');
		$downloader = new Downloader(Factory::getLocations()->getDownloadsDir() . JetBackup::SEP . $this->getLocation(), $this->getFilename());
		$downloader->download();
	}

	/**
	 * @param string $source
	 * @param string|null $prefix
	 *
	 * @return Download
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function create(string $source, ?string $prefix = null):Download {

		$size = filesize($source);
		$id = Util::generateUniqueId();
		$downloads_dir = Factory::getLocations()->getDownloadsDir();
		if(!is_dir($downloads_dir)) mkdir($downloads_dir, 0700);

		rename($source, $downloads_dir . JetBackup::SEP . $id);

		$download = new Download();
		$download->setCreated(time());
		$download->setFilename(($prefix ? $prefix . "_" : "") . basename($source));
		$download->setLocation($id);
		$download->setSize($size);
		$download->save();

		return $download;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getDisplay():array {
		return [
			JetBackup::ID_FIELD => $this->getId(),
			self::CREATED       => $this->getCreated(),
			self::FILENAME      => $this->getFilename(),
			self::LOCATION      => $this->getLocation(),
			self::SIZE          => Util::bytesToHumanReadable($this->getSize()),
		];
	}

	public function getDisplayCLI(): array {
		return [
			'ID'            => $this->getId(),
			'Created'       => CLI::date($this->getCreated()),
			'Filename'      => $this->getFilename(),
			'Location'      => $this->getLocation(),
			'Size'          => $this->getSize(),
		];
	}
}