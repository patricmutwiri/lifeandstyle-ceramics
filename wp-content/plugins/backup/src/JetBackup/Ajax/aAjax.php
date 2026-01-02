<?php

namespace JetBackup\Ajax;

use JetBackup\Exception\AjaxException;
use JetBackup\Exception\UserInputException;
use JetBackup\UserInput\UserInput;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class aAjax implements iAjax {

	protected UserInput $_data;
	private bool $_is_cli=false;
	private string $_response_message='';
	private array $_response_data=[];
	private array $_response_cli=[];

	public function __construct() {
		$this->_data = new UserInput();
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function setData(array $data=[]):void {
		unset($data['actionType']);
		$this->_data->setData($data);
	}

	/**
	 * @param bool $cli
	 *
	 * @return void
	 */
	public function setCLI(bool $cli):void {
		$this->_is_cli = $cli;
	}

	/**
	 * @return bool
	 */
	public function isCLI(): bool {
		return $this->_is_cli;
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function setResponseCLI(array $data):void { $this->_response_cli = $data; }

	/**
	 * @return array
	 */
	public function getResponseCLI(): array { return $this->_response_cli ?? []; }

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function setResponseData(array $data):void { $this->_response_data = $data; }

	/**
	 * @return array
	 */
	public function getResponseData(): array { return $this->_response_data ?? []; }

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function setResponseMessage(string $message):void { $this->_response_message = $message; }

	/**
	 * @return string
	 */
	public function getResponseMessage(): string { return $this->_response_message ?? ''; }

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isset(string $key):bool {
		$fields = $this->_data->getData();
		return isset($fields[$key]);
	}

	/**
	 * @param string $key
	 * @param $default
	 * @param int $type
	 * @param int $subType
	 *
	 * @return array|bool|float|int|mixed|object
	 * @throws AjaxException
	 */
	public function getUserInput(string $key, $default, int $type, int $subType = 0) {
		try {
			return $this->_data->getValidated($key, $default, $type, $subType);
		} catch(UserInputException $e) {
			throw new AjaxException($e->getMessage(), $e->getData());
		}
	}
}