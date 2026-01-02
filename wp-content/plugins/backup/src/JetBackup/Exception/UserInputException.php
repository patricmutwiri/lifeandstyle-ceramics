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
namespace JetBackup\Exception;

use Throwable;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class UserInputException extends JBException {
	private array $_types;
	private array $_data;

	/**
	 * @param string $message
	 * @param array $data
	 * @param array $types
	 * @param int $code
	 * @param throwable|null $previous
	 */
	public function __construct(string $message="", array $data=[], array $types=[], int $code = 0, ?throwable $previous=null) {
		$this->_types = $types;
		$this->_data = $data;
		parent::__construct($message, $code, $previous);
	}

	public function getTypes():array { return $this->_types; }
	public function getData():array { return $this->_data; }
}
