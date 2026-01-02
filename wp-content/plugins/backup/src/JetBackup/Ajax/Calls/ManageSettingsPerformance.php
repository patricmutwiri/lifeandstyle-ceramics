<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\Settings\Performance;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ManageSettingsPerformance extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getReadChunkSize(): int { return $this->getUserInput(Performance::READ_CHUNK_SIZE, 0, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getExecutionTime(): int { return $this->getUserInput(Performance::EXECUTION_TIME, 0, UserInput::UINT); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getSQLCleanupRevisions(): bool { return $this->getUserInput(Performance::SQL_CLEANUP_REVISIONS, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getUseDefaultExcludes(): bool { return $this->getUserInput(Performance::USE_DEFAULT_EXCLUDES, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getExcludeNestedSites(): bool { return $this->getUserInput(Performance::EXCLUDE_NESTED_SITES, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getUseDefaultDBExcludes(): bool { return $this->getUserInput(Performance::USE_DEFAULT_DB_EXCLUDES, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getGzipCompressArchive(): bool { return $this->getUserInput(Performance::GZIP_COMPRESS_ARCHIVE, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getGzipCompressDB(): bool { return $this->getUserInput(Performance::GZIP_COMPRESS_DB, false, UserInput::BOOL); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws FieldsValidationException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsPerformance();

		if($this->isset(Performance::READ_CHUNK_SIZE)) $settings->setReadChunkSize($this->_getReadChunkSize());
		if($this->isset(Performance::EXECUTION_TIME)) $settings->setExecutionTime($this->_getExecutionTime());
		if($this->isset(Performance::SQL_CLEANUP_REVISIONS)) $settings->setSQLCleanupRevisionsEnabled($this->_getSQLCleanupRevisions());
		if($this->isset(Performance::USE_DEFAULT_EXCLUDES)) $settings->setUseDefaultExcludes($this->_getUseDefaultExcludes());
		if($this->isset(Performance::EXCLUDE_NESTED_SITES)) $settings->setExcludeNestedSites($this->_getExcludeNestedSites());
		if($this->isset(Performance::USE_DEFAULT_DB_EXCLUDES)) $settings->setUseDefaultDBExcludes($this->_getUseDefaultDBExcludes());
		if($this->isset(Performance::GZIP_COMPRESS_ARCHIVE)) $settings->setGzipCompressArchive($this->_getGzipCompressArchive());
		if($this->isset(Performance::GZIP_COMPRESS_DB)) $settings->setGzipCompressDB($this->_getGzipCompressDB());

		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}

