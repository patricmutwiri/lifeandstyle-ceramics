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

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class AjaxException extends JBException {

	private array $data;

	/**
	 * @param string $message
	 * @param array $data
	 * @param int $code
	 * @param $previous
	 */
	public function __construct(string $message="", array $data=[], int $code = 0, $previous=null) {
		$this->data = $data;
		parent::__construct($message, $code, $previous);
	}

	public function getData():array { return $this->data; }
}
