<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Destination\Vendors\S3\Client;

use Exception;
use JetBackup\Destination\Vendors\S3\Client\Exception\ClientException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class ClientRetry {

	const WAIT_TIME = 333000;
	const MAX_WAIT_TIME = 60000000;
	
	private string $_func;
	private array $_args;
	private $_retry_callback;
	private $_success_callback;

	/**
	 * 
	 */
	public function __construct() {
		$this->_args = [];
	}

	/**
	 * @param string $function
	 *
	 * @return ClientRetry
	 */
	public function func(string $function):ClientRetry {
		$this->_func = $function;
		return $this;
	}

	/**
	 * @param ...$args
	 *
	 * @return ClientRetry
	 */
	public function args(...$args):ClientRetry {
		$this->_args = $args;
		return $this;
	}

	/**
	 * @param callable $callback
	 *
	 * @return ClientRetry
	 */
	public function retryCallback(callable $callback):ClientRetry {
		$this->_retry_callback = $callback;
		return $this;
	}

	/**
	 * @param callable $callback
	 *
	 * @return ClientRetry
	 */
	public function successCallback(callable $callback):ClientRetry {
		$this->_success_callback = $callback;
		return $this;
	}

	/**
	 * @param ClientManager $manager
	 *
	 * @return mixed
	 * @throws ClientException
	 */
	public function exec(ClientManager $manager) {
		$client = $manager->getClient();
		if(!$this->_func) throw new ClientException("You must provide function");
		if(!method_exists($client, $this->_func)) throw new ClientException("Invalid function ($this->_func) provide");

		$waittime = self::WAIT_TIME;
		$tries = 0;
		while(true) {
			try {
				$result = call_user_func_array([$client, $this->_func], $this->_args);

				if(($success_callback = $this->_success_callback)) $success_callback();
				return $result;
			} catch(Exception $e) {
				if(($e->getCode() < 500 && !in_array($e->getCode(), [0,1,400,403,429])) || $tries >= $manager->getRetries()) throw new ClientException($e->getMessage(), $e->getCode());

				if(($retry_callback = $this->_retry_callback)) $retry_callback();

				$log_args = [];
				foreach($this->_args as $arg) {
					if(is_array($arg)) $arg = 'Array -> ' . json_encode($arg);
					$log_args[] = $arg;
				}

				$manager->getLogController()->logDebug("Failed $this->_func(" . implode(", ", $log_args). "). Error: {$e->getMessage()} (Code: {$e->getCode()})");
				if($waittime > self::MAX_WAIT_TIME) $waittime = self::MAX_WAIT_TIME;
				usleep($waittime);
				$waittime *= 2;
				$tries++;
				$manager->getLogController()->logDebug("Retry $tries/{$manager->getRetries()} $this->_func(" . implode(", ", $log_args). ")");
			}
		}
	}
}