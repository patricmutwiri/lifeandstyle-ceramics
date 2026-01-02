<?php

namespace JetBackup\Notification;

use JetBackup\Data\ArrayData;
use JetBackup\Exception\NotificationException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Notification extends ArrayData {

	const TEMPLATES_PATH = JetBackup::TEMPLATES_PATH . JetBackup::SEP . 'emails';
	
	private array $_vars=[];
	
	public static function message():Notification {
		return (new Notification());
	}
	
	public function addParam($key, $value):Notification { $this->set($key, $value); return $this; }

	/**
	 * @param $subject
	 * @param $message_name
	 *
	 * @return void
	 * @throws NotificationException
	 */
	public function send($subject, $message_name) {

		$recipient = Factory::getSettingsNotifications()->getAlternateEmail() ?: Wordpress::getBlogInfo('admin_email');
		
		$message_file = self::TEMPLATES_PATH . JetBackup::SEP . $message_name . '.tpl';
		if(!file_exists($message_file)) throw new NotificationException('Message file does not exist');

		$main_file = self::TEMPLATES_PATH . JetBackup::SEP . 'main.tpl';
		if(!file_exists($main_file)) throw new NotificationException('Main file does not exist');

		$message = $this->_parse(file_get_contents($main_file), [
			'content' => $this->_parse(file_get_contents($message_file), $this->getData()),
			'year' => date('Y')
		]);

		Email::send($recipient, $subject, $message);
	}
	
	private function _parse($content, $vars) {

		$content = preg_replace("#{else}#", "<?php else: ?>", $content);
		$content = preg_replace( "#{/if}#", "<?php endif; ?>", $content);
		$content = preg_replace( "#{/foreach}#", "<?php endforeach; ?>", $content);

		$content = preg_replace_callback("#{(if|elseif|foreach)\s+([^}]+)}#", function($matches) {
			$condition = preg_replace_callback("#\\$([a-zA-Z0-9_]+)#", function($matches) {
				return $this->_buildVar($matches[1]);
			}, $matches[2]);

			return "<?php $matches[1]($condition): ?>";

		}, $content);

		$content = preg_replace_callback("#{\\$([^}]+)}#", function($matches) {
			return "<?php echo " . $this->_buildVar($matches[1]) . "; ?>";
		}, $content);

		$this->_vars = $vars;

		ob_start();
		eval('?>' . $content);
		return ob_get_clean();
	}

	private function _buildVar($var) {
		$parts = explode(".", $var);
		$output = "\$this->_vars";
		foreach ($parts as $part) $output .= "['$part']";
		return $output;
	}
}