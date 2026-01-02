<?php

use JetBackup\JetBackup;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'Mysqldump' . JetBackup::SEP . 'autoload.php');
require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'phpseclib3' . JetBackup::SEP . 'autoload.php');
require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'ParagonIE' . JetBackup::SEP . 'autoload.php');
require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'SimpleThenticator' . JetBackup::SEP . 'autoload.php');
require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'SleekDB' . JetBackup::SEP . 'autoload.php');
require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'SocketAPI' . JetBackup::SEP . 'autoload.php');