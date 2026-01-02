<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Cron\Cron;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\QueueException;
use JetBackup\Wordpress\Helper;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * Helper function to run the cron from cli (WP Only)
 */
class ExecuteCron extends aAjax {

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws JBException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
		if(!$this->isCLI() || !Helper::isWPCli()) throw new AjaxException("Can only execute through wp cli");
		Cron::main();
	}
}