<?php

namespace JetBackup\Ajax;

use JetBackup\Exception\AjaxException;
use JetBackup\Exception\JBException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface iAjax {
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function setData(array $data=[]): void;

	/**
	 * @param bool $cli
	 *
	 * @return void
	 */
	public function setCLI(bool $cli): void;

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws JBException
	 */
	public function execute():void;

	/**
	 * @return array
	 */
	public function getResponseData(): array;

	/**
	 * @return string
	 */
	public function getResponseMessage(): string;
}