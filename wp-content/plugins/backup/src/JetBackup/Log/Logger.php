<?php

namespace JetBackup\Log;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface Logger{

	const LOG_LEVEL_ERROR 		        = 1;  // ERROR
	const LOG_LEVEL_WARNING 		    = 2;  // WARNING
	const LOG_LEVEL_NOTICE 		        = 4;  // NOTICE
	const LOG_LEVEL_MESSAGE 		    = 8;  // MESSAGE
	const LOG_LEVEL_DEBUG			    = 16; // DEBUG
	const LOG_LEVEL_ALL			        = self::LOG_LEVEL_ERROR | self::LOG_LEVEL_WARNING | self::LOG_LEVEL_NOTICE | self::LOG_LEVEL_MESSAGE | self::LOG_LEVEL_DEBUG; // ALL

	const LOG_LEVEL_NAMES = [
		self::LOG_LEVEL_ERROR 	=> "ERROR",
		self::LOG_LEVEL_WARNING => "WARNING",
		self::LOG_LEVEL_NOTICE  => "NOTICE",
		self::LOG_LEVEL_MESSAGE => "MESSAGE",
		self::LOG_LEVEL_DEBUG   => "DEBUG",
	];

	const PARAMS_NEW_LINE 	        = 1; // new line
	const PARAMS_ADD_DATE 	        = 2; // Add date ahead of the message
	const PARAMS_ADD_LEVEL 	        = 4;
	const PARAMS_ADD_IP 	        = 8;
	const PARAMS_BACK_START_LINE    = 16; // reset line

	const DATE_FORMAT = "Y-m-d H:i:s";

	/**
	 * @param string $message
	 * @param int $level
	 * @param int $params
	 *
	 * @return void
	 */
	public function addEvent(string $message, int $level, int $params):void;
}