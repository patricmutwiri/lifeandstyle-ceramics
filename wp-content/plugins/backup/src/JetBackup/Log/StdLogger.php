<?php
/*
*
* JetBackup @ package
* Created By Shlomi Bazak
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Log;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class StdLogger implements Logger {

	private FileLogger $_logger;

	public function __construct(int $level=Logger::LOG_LEVEL_ERROR) {
		$this->_logger = new FileLogger("php://stdout", $level);
	}

	public function addEvent(string $message, int $level, int $params=Logger::PARAMS_NEW_LINE|Logger::PARAMS_ADD_DATE):void {
		$this->_logger->addEvent($message, $level, $params);
	}
}