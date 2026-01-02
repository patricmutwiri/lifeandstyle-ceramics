<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\NotificationException;
use JetBackup\Factory;
use JetBackup\Notification\Email;
use JetBackup\UserInput\UserInput;
use JetBackup\Wordpress\Helper;

class SendTestEmail extends aAjax {


	/**
	 * @return string
	 * @throws AjaxException
	 */
	public function getRecipient():string { return $this->getUserInput('alternate_email', '', UserInput::STRING); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws NotificationException
	 */
	public function execute(): void {

		$recipient = $this->getRecipient();
		if (!Factory::getSettingsNotifications()->isEmailsEnabled()) throw new AjaxException("Email System is disabled");
		if (!Helper::validateEmail($recipient)) throw new AjaxException("Email from ($recipient) invalid!");

		Email::send(
			$recipient,
			'JetBackup Test Email',
			'This is a test email');

		$this->setResponseMessage('Email sent!');

	}
}