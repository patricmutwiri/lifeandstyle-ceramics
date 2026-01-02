<?php

namespace JetBackup\Destination\Integration;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface DestinationFile {

	const TYPE_UNKNOWN      = 0;
	const TYPE_FILE         = 1;
	const TYPE_DIRECTORY    = 2;
	const TYPE_LINK         = 3;
	const TYPE_BLOCK        = 4;
	const TYPE_FIFO         = 5;
	const TYPE_SOCKET       = 6;
	const TYPE_NETWORK      = 7;
	const TYPE_CHAR         = 8;

	const TYPE_NAMES = [
		self::TYPE_UNKNOWN      => "Unknown",
		self::TYPE_FILE         => "File",
		self::TYPE_DIRECTORY    => "Directory",
		self::TYPE_LINK         => "Link",
		self::TYPE_BLOCK        => "Block Device",
		self::TYPE_FIFO         => "Fifo",
		self::TYPE_SOCKET       => "Socket",
		self::TYPE_NETWORK      => "Network",
		self::TYPE_CHAR         => "Char",
	];

	/**
	 * @return string
	 */
	public function getPath(): string;

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return string
	 */
	public function getFullPath(): string;

	/**
	 * @return int
	 */
	public function getType(): int;

	/**
	 * @return int
	 */
	public function getSize(): int;

	/**
	 * @return int
	 */
	public function getModifyTime(): int;

	/**
	 * @return string
	 */
	public function getOwner(): string;

	/**
	 * @return string
	 */
	public function getGroup(): string;

	/**
	 * @return int
	 */
	public function getPermissions();

	/**
	 * @return string
	 */
	public function getLinkTarget();

	/**
	 * @return string
	 */
	public function getFileId();

	/**
	 * @return string
	 */
	public function getFilesDir();

	/**
	 * @return string
	 */
	public function getFileData();

	/**
	 * @return array
	 */
	public function getData();
}