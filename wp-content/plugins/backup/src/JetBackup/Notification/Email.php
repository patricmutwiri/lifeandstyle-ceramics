<?php

namespace JetBackup\Notification;

use JetBackup\Exception\NotificationException;
use JetBackup\Factory;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Email {

	private function __construct() {}

	/**
	 * @throws NotificationException
	 */
	public static function send($recipient, $subject, $message, $attachments=[], $from=null, $headers=[]) {


		if (!Factory::getSettingsNotifications()->isEmailsEnabled()) return true; // Do nothing if emails are disabled
		if (!Helper::validateEmail($recipient)) throw new NotificationException("Email recipient ($recipient) invalid!");



		if(!$from) {
			$site_url = parse_url(Wordpress::getBlogInfo('url'));
            // Use a fake domain if running on localhost (real domains may be rejected in tests)
            $from = 'wordpress@' . ($site_url['host'] === 'localhost' ? 'localhost.local' : $site_url['host']);
		}

		if(!$recipient) {
			$recipient = Wordpress::getBlogInfo('admin_email');
		}
		
		if (!Helper::validateEmail($from)) throw new NotificationException("Email from ($from) invalid!");

		$subject = Wordpress::sanitizeTextField($subject);

		$headers = array_merge($headers, [
			'MIME-Version: 1.0',
			'Content-Type: text/html; charset=UTF-8',
		]);

		foreach ($headers as $key => $value) {
			$headers[$key] = Wordpress::sanitizeTextField($value);
		}

		// Ensure $attachments is an array
		$attachments = is_array($attachments) ? $attachments : [$attachments];

		$attachments = array_filter($attachments, function($file) {
			$valid = file_exists($file) && is_readable($file);
			if (!$valid) throw new NotificationException("Attachment $file is not valid!");
		});

		try {
			return Wordpress::sendMail($recipient, $subject, $message, $headers, $attachments);
		} catch (\Exception $e) {
			throw new NotificationException($e->getMessage(), $e->getCode());
		}
	}
}