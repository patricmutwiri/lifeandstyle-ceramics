<?php

namespace JetBackup\Destination\Integration;

use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\RegistrationException;
use JetBackup\Exception\ValidationException;
use JetBackup\Log\LogController;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface Destination {

	public function __construct(int $chunk_size, string $path, ?LogController $logController=null, ?string $name=null, int $id=0);

	/**
	 * @param string $data
	 */
	public function setSerializedData(string $data);

	/**
	 * @return string
	 */
	public function getSerializedData(): string ;

	/**
	 * @return array
	 */
	public function protectedFields():array;

	/**
	 * @throws FieldsValidationException
	 * @return void
	 */
	public function validateFields():void;

	/**
	 * @param object $data
	 */
	public function setData(object $data):void;

	/**
	 * @return array
	 */
	public function getData(): array;

	/**
	 * @throws ConnectionException
	 */
	public function connect():void;

	/**
	 * @return void
	 */
	public function disconnect():void;

	/**
	 * @throws RegistrationException
	 */
	public function register():void;

	/**
	 * @return void
	 */
	public function unregister():void;

	/**
	 * @return string
	 */
	public function getPath():string;
	
	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function dirExists(string $directory, ?string $data=null): bool;

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function fileExists(string $file, ?string $data=null): bool;

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function createDir(string $directory, bool $recursive, ?string $data=null): ?string;

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeDir(string $directory, ?string $data=null):void;

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function copyFileToLocal(string $source, string $destination, ?string $data=null):void;

	/**
	 * @param string $source
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function copyFileToLocalChunked(string $source, string $destination, ?string $data=null):DestinationChunkedDownload;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):?string;

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 */
	public function copyFileToRemoteChunked(string $source, string $destination, ?string $data=null):DestinationChunkedUpload;
	
	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return DestinationDirIterator
	 */
	public function listDir(string $directory, ?string $data=null):DestinationDirIterator;

	/**
	 * @return DestinationDiskUsage|null
	 */
	public function getDiskInfo():?DestinationDiskUsage;

	/**
	 * @param string $file
	 *
	 * @return DestinationFile|null
	 */
	public function getFileStat(string $file):?DestinationFile;
}