<?php

namespace JetBackup\Log;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class LogController {

	private array $_loggers=[];

	/**
	 * @param string $message
	 * @param int $level
	 * @param int $params
	 *
	 * @return void
	 */
	private function _log(string $message, int $level, int $params=Logger::PARAMS_NEW_LINE | Logger::PARAMS_BACK_START_LINE | Logger::PARAMS_ADD_DATE | Logger::PARAMS_ADD_IP):void {
		if(!isset($this->_loggers) || !$this->_loggers) return;
		foreach($this->_loggers as $logger) $logger->addEvent($message, $level, $params);
	}

	/**
	 * @param Logger $logger
	 *
	 * @return void
	 */
	public function addLogger(Logger $logger):void { $this->_loggers[] = $logger; }

	/**
	 * @param Logger[] $loggers
	 *
	 * @return void
	 */
	public function setLoggers(array $loggers):void { $this->_loggers = $loggers; }

	/**
	 * @return Logger[]
	 */
	public function getLoggers():array { return $this->_loggers; }

	/**
	 * @param string $debug
	 *
	 * @return void
	 */
	public function logDebug(string $debug):void { $this->_log($debug, Logger::LOG_LEVEL_DEBUG, Logger::PARAMS_NEW_LINE | Logger::PARAMS_BACK_START_LINE | Logger::PARAMS_ADD_DATE | Logger::PARAMS_ADD_IP | Logger::PARAMS_ADD_LEVEL); }

	/**
	 * To count errors use logError through the 'Task' Class and not directly through this function
	 * Use cases are for errors that only reports but not exit so we will know that a task is completed with errors
	 *
	 * @param string $error
	 *
	 * @return void
	 */
	public function logError(string $error):void { $this->_log($error, Logger::LOG_LEVEL_ERROR, Logger::PARAMS_NEW_LINE | Logger::PARAMS_BACK_START_LINE | Logger::PARAMS_ADD_DATE | Logger::PARAMS_ADD_IP | Logger::PARAMS_ADD_LEVEL); }

	/**
	 * @param string $warning
	 *
	 * @return void
	 */
	public function logWarning(string $warning):void { $this->_log($warning, Logger::LOG_LEVEL_WARNING, Logger::PARAMS_NEW_LINE | Logger::PARAMS_BACK_START_LINE | Logger::PARAMS_ADD_DATE | Logger::PARAMS_ADD_IP | Logger::PARAMS_ADD_LEVEL); }

	/**
	 * @param string $notice
	 *
	 * @return void
	 */
	public function logNotice(string $notice):void { $this->_log($notice, Logger::LOG_LEVEL_NOTICE, Logger::PARAMS_NEW_LINE | Logger::PARAMS_BACK_START_LINE | Logger::PARAMS_ADD_DATE | Logger::PARAMS_ADD_IP | Logger::PARAMS_ADD_LEVEL); }

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function logMessage(string $message):void { $this->_log($message, Logger::LOG_LEVEL_MESSAGE); }

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function logClean(string $message):void { $this->_log($message, Logger::LOG_LEVEL_MESSAGE, Logger::PARAMS_NEW_LINE); }

	/**
	 *
	 */
	public function __destruct() {
		unset($this->_loggers);
	}
}