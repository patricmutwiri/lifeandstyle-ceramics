<?php

namespace JetBackup\Ajax;

use Exception;
use JetBackup\Cron\Cron;
use JetBackup\Data\ArrayData;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\JBException;
use JetBackup\JetBackup;
use JetBackup\MFA\GoogleAuthenticator;
use JetBackup\Wordpress\UI;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Ajax extends ArrayData {

	const MFA_ALLOWED_FUNCTIONS = ['panelPreload', 'getQRCode', 'validateMFA'];

	private function __construct() {
		$this->setData(self::_getRequestData());
	}

	/**
	 * @return array
	 */
	private static function _getRequestData():array {
		$input = file_get_contents("php://input");
		$params = $input ? json_decode($input,true) : [];
		if(!$params) $params = [];
		if($_GET && is_array($_GET)) $params = array_merge($_GET, $params);
		if($_POST && is_array($_POST)) $params = array_merge($_POST, $params);
		if($_REQUEST && is_array($_REQUEST)) $params = array_merge($_REQUEST, $params);
		return $params;
	}
	
	/**
	 * @return string
	 */
	private static function _getNonce():string { 
		$params = self::_getRequestData();
		return $params['nonce'] ?? ''; 
	}

	/**
	 * @return void
	 * @throws AjaxException
	 */
	private static function _init():void {
		if (!function_exists('current_user_can'))   throw new AjaxException('Error %s - WordPress Core function missing', [102]);
		if (!function_exists('is_user_logged_in'))  throw new AjaxException('Error %s - WordPress Core function missing', [103]);
		if (!is_user_logged_in())                           throw new AjaxException('Error %s - You are not logged in', [104]);
		if (!current_user_can('manage_options'))   throw new AjaxException('Error %s - Insufficient user permissions', [105]);
		if (!Wordpress::verifyNonce(self::_getNonce()))     throw new AjaxException('Error %s - Session Expired (Refresh Page Needed?)', [108]);
	}

	/**
	 * @return void
	 */
	public static function main():void {
		(new Ajax())->execute();
	}

	/**
	 * @return void
	 */
	public function execute():void {

		if (Wordpress::isDebugModeEnabled()) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}

		$data = $this->getData();

		try {
			self::_init();
			if(!isset($data['actionType']) || !$data['actionType']) throw new AjaxException("No action type provided");

			$method = "\JetBackup\Ajax\Calls\\" . ucfirst($data['actionType']);
			if(!class_exists($method)) throw new AjaxException("Invalid action type provided (action: %s)", [$data['actionType']]);

			if (GoogleAuthenticator::isSetupCompleted() &&
			    !UI::validateMFA() &&
			    !in_array($data['actionType'], self::MFA_ALLOWED_FUNCTIONS)) throw new AjaxException('MFA is not validated');

			/** @var iAjax $call */
			$call = new $method();
			$call->setData($data);

			$call->execute();
			self::_output($call->getResponseMessage(), $call->getResponseData());
		} catch(AjaxException $e) {
			$msg = $e->getMessage();
			self::_exit($msg, $e->getData());
		} catch(JBException $e) {
			self::_exit($e->getMessage());
		}
	}

	/**
	 * @return void
	 */
	public static function heartbeat():void {
		
		try {
			self::_init();
			Cron::main();
		} catch( Exception $e) {
			self::_exit($e->getMessage());
		}

		self::_output('Heartbeat Done');
	}


	/**
	 * @param string $message
	 * @param array $data
	 * @param int $success
	 *
	 * @return void
	 */
	private static function _output(string $message, array $data=[], int $success=1):void {
		die(json_encode([
			'message' => $message,
			'success' => $success,
			'data' => $data,
			'system'    => [
				'version'   => JetBackup::VERSION,
				'nonce'     => Wordpress::createNonce(),
			],
		]));
	}

	/**
	 * @param string $message
	 * @param array $data
	 *
	 * @return void
	 */
	private static function _exit(string $message, array $data=[]):void {
		self::_output($message, $data, 0);
	}

	/**
	 * @param int $time
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function date(int $time):string {
		return Util::date("Y-m-d H:i:s", $time);
	}
}