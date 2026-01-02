<?php

namespace JetBackup\Log;

use Exception;
use JetBackup\Entities\Util;
use JetBackup\Exception\LogException;
use JetBackup\Wordpress\Helper;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class FileLogger implements Logger{

	const DEFAULT_ADD_EVENT_PARAMS = Logger::PARAMS_NEW_LINE | Logger::PARAMS_BACK_START_LINE | Logger::PARAMS_ADD_DATE | Logger::PARAMS_ADD_IP;
	
	private $_fileptr;
	private string $_filepath;
	private int $_level;

	public function __construct(string $logfile, int $level=Logger::LOG_LEVEL_ERROR | Logger::LOG_LEVEL_WARNING | Logger::LOG_LEVEL_NOTICE | Logger::LOG_LEVEL_MESSAGE) {
		$this->_filepath = $logfile;
		$this->_level = $level;
		if(!$this->_filepath) throw new LogException("You must specify log files path.");
		$isStd = preg_match("/^php:\/\//", $this->_filepath);
		if(!$isStd && !file_exists(dirname($this->_filepath))) mkdir(dirname($this->_filepath), 0700, true);
		$this->_fileptr = fopen($this->_filepath, "a+");
		if(!$this->_fileptr) throw new LogException($this->_filepath);
	}

	public function addEvent(string $message, int $level, int $params=self::DEFAULT_ADD_EVENT_PARAMS):void {
		if(($level & $this->_level) == 0) return;
		$format = $this->getLineFormat($params, $level);
		$message = sprintf($format, $message);
		if(fwrite($this->_fileptr, $message) === false) throw new LogException("Failed writing to file");
	}

	/**
	 * @return string
	 */
	public function getFilePath():string { return $this->_filepath; }

	/**
	 * @return int
	 */
	public function getLevel():int { return $this->_level; }

	/**
	 * @throws Exception
	 */
	protected function getLineFormat(int $params, int $level):string {
		$format = "%s";
		if(($params & Logger::PARAMS_BACK_START_LINE) > 0) $format = "$format\r";
		if(($params & Logger::PARAMS_NEW_LINE) > 0) $format = "$format\n";
		if(($params & Logger::PARAMS_ADD_LEVEL) > 0) $format = "[". @Logger::LOG_LEVEL_NAMES[$level] ."] $format";
		if(($params & Logger::PARAMS_ADD_IP) > 0 && ($ip = Helper::getUserIP())) $format = "[$ip] $format";
		if(($params & Logger::PARAMS_ADD_DATE) > 0) $format = "[".Util::date(Logger::DATE_FORMAT) . "] $format";
		return $format;
	}

	public function __destruct(){
		if (!$this->_fileptr) return;
		fflush($this->_fileptr);
		fclose($this->_fileptr);
	}
}