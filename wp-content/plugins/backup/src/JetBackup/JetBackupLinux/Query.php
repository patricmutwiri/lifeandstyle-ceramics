<?php

namespace JetBackup\JetBackupLinux;

use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\SocketAPI\Exception\SocketAPIException;
use JetBackup\SocketAPI\SocketAPI;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Query extends SocketAPI {

	/**
	 * @param $function
	 *
	 * @return Query
	 * @throws JetBackupLinuxException
	 */
	public static function api($function):Query {
		if(!function_exists('socket_connect'))
			throw new JetBackupLinuxException("The function socket_connect not installed or disabled within your PHP.");
		return new Query($function);
	}

	public function execute() {

		try {
			$response = parent::execute();
		} catch(SocketAPIException $e) {
			throw new JetBackupLinuxException($e->getMessage());
		}
		
		if(!$response['success']) throw new JetBackupLinuxException($response['message']);
		return $response['data'];		
	}

}