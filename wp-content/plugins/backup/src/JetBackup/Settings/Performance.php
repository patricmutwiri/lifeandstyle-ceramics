<?php

namespace JetBackup\Settings;

use JetBackup\BackupJob\BackupJob;
use JetBackup\Config\System;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use ReflectionException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Performance extends Settings {

	const SECTION = 'performance';

	const EXECUTION_TIME = 'PERFORMANCE_EXECUTION_TIME';

	const READ_CHUNK_SIZE = 'READ_CHUNK_SIZE';
	const SQL_CLEANUP_REVISIONS = 'SQL_CLEANUP_REVISIONS';
	const USE_DEFAULT_EXCLUDES = 'USE_DEFAULT_EXCLUDES';
	const EXCLUDE_NESTED_SITES = 'EXCLUDE_NESTED_SITES';

	const USE_DEFAULT_DB_EXCLUDES = 'USE_DEFAULT_DB_EXCLUDES';
	const GZIP_COMPRESS_ARCHIVE = 'GZIP_COMPRESS_ARCHIVE';
	const GZIP_COMPRESS_DB = 'GZIP_COMPRESS_DB';
	const DEFAULT_EXCLUDES = 'DEFAULT_EXCLUDES';
	const DEFAULT_DB_EXCLUDES = 'DEFAULT_DB_EXCLUDES';
	const EXECUTION_TIMES =  [0, 10, 20, 30, 40, 50, 60, 120, 300, 600];

	/**
	 * @throws IOException
	 * @throws ReflectionException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return int
	 */
	public function getExecutionTime():int { return (int) $this->get(self::EXECUTION_TIME, 0); }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setExecutionTime(int $value):void { $this->set(self::EXECUTION_TIME, $value); }

	/**
	 *
	 * @return int
	 */
	public function getReadChunkSize():int { return (int) $this->get(self::READ_CHUNK_SIZE, 1); } // used for GUI only

	public function getReadChunkSizeBytes():int { return $this->getReadChunkSize() * 1024 * 1024; }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setReadChunkSize(int $value):void { $this->set(self::READ_CHUNK_SIZE, $value); }

	/**
	 * @return bool
	 */
	public function isSQLCleanupRevisionsEnabled():bool { return (bool) $this->get(self::SQL_CLEANUP_REVISIONS, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setSQLCleanupRevisionsEnabled(bool $value):void { $this->set(self::SQL_CLEANUP_REVISIONS, $value); }

	/**
	 * @return bool
	 */
	public function isUseDefaultExcludes():bool { return (bool) $this->get(self::USE_DEFAULT_EXCLUDES, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setUseDefaultExcludes(bool $value):void { $this->set(self::USE_DEFAULT_EXCLUDES, $value); }

	/**
	 * @return bool
	 */
	public function isExcludeNestedSitesEnabled():bool { return (bool) $this->get(self::EXCLUDE_NESTED_SITES, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setExcludeNestedSites(bool $value):void { $this->set(self::EXCLUDE_NESTED_SITES, $value); }

	/**
	 * @return bool
	 */
	public function isUseDefaultDBExcludes():bool { return (bool) $this->get(self::USE_DEFAULT_DB_EXCLUDES, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setUseDefaultDBExcludes(bool $value):void { $this->set(self::USE_DEFAULT_DB_EXCLUDES, $value); }

	/**
	 * @return bool
	 */
	public function isGzipCompressArchive():bool { return (bool) $this->get(self::GZIP_COMPRESS_ARCHIVE, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setGzipCompressArchive(bool $value):void { $this->set(self::GZIP_COMPRESS_ARCHIVE, $value); }

	/**
	 * @return bool
	 */
	public function isGzipCompressDB():bool { return (bool) $this->get(self::GZIP_COMPRESS_DB, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setGzipCompressDB(bool $value):void { $this->set(self::GZIP_COMPRESS_DB, $value); }

	/**
	 * @return array
	 */
	public function getDisplay():array {

		return [
			self::READ_CHUNK_SIZE               => $this->getReadChunkSize(),
			self::EXECUTION_TIME                => $this->getExecutionTime(),
			self::SQL_CLEANUP_REVISIONS         => $this->isSQLCleanupRevisionsEnabled() ? 1 : 0,
			self::USE_DEFAULT_EXCLUDES          => $this->isUseDefaultExcludes() ? 1 : 0,
			self::EXCLUDE_NESTED_SITES          => $this->isExcludeNestedSitesEnabled() ? 1 : 0,
			self::USE_DEFAULT_DB_EXCLUDES       => $this->isUseDefaultDBExcludes() ? 1 : 0,
			self::GZIP_COMPRESS_ARCHIVE         => $this->isGzipCompressArchive() ? 1 : 0,
			self::GZIP_COMPRESS_DB              => $this->isGzipCompressDB() ? 1 : 0,
			self::DEFAULT_EXCLUDES              => BackupJob::getDefaultExcludes(null, null),
			self::DEFAULT_DB_EXCLUDES           => BackupJob::DEFAULT_DATABASE_EXCLUDES,
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayCLI():array {

		return [
			'Read Chunk Size'               => $this->getReadChunkSize(),
			'Max Execution Time'            => $this->getExecutionTime(),
			'SQL Cleanup Revisions'         => $this->isSQLCleanupRevisionsEnabled() ? "Yes" : "No",
			'Use Default Excludes'          => $this->isUseDefaultExcludes() ? "Yes" : "No",
			'Exclude Nested Sites'          => $this->isExcludeNestedSitesEnabled() ? "Yes" : "No",
			'Use Default Database Excludes' => $this->isUseDefaultDBExcludes() ? "Yes" : "No",
			'Compress Backup Files'         => $this->isGzipCompressArchive() ? "Yes" : "No",
			'Compress Backup Database'      => $this->isGzipCompressDB() ? "Yes" : "No",
		];
	}

	/**
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {

		if(!in_array($this->getExecutionTime(), self::EXECUTION_TIMES))
			throw new FieldsValidationException('Execution time of '. $this->getExecutionTime() . ' is not allowed');

		$serverExecutionTime = System::getServerExecutionTime();
		if ($serverExecutionTime > 0 && $this->getExecutionTime() > $serverExecutionTime)
			throw new FieldsValidationException( 'Execution time of ' . $this->getExecutionTime() . ' seconds cannot be higher than server defaults: ' . $serverExecutionTime);
	}
}