<?php
if(!defined('__JETBACKUP_RESTORE__')) exit;

if (function_exists('opcache_get_status')) ini_set('opcache.enable', 0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: 0');

use JetBackup\Cache\CacheHandler;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Restore\Restore;
use JetBackup\Wordpress\Wordpress;

define('__JETBACKUP__', true);

define('PLUGIN_PATH', 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'backup');
define('JB_ROOT', WP_ROOT . DIRECTORY_SEPARATOR . PLUGIN_PATH);

require_once(JB_ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'JetBackup' . DIRECTORY_SEPARATOR . 'autoload.php');

$queue_id = isset($_REQUEST['id']) ? Wordpress::sanitizeTextField(Wordpress::getUnslash($_REQUEST['id'])) : '';

if(!$queue_id) die('Queue id not provided');
CacheHandler::pre();
$queue = new QueueItem();
$queue->loadByUniqueId($queue_id);
if(!$queue->getId()) die('Queue not found');
if($queue->getType() != Queue::QUEUE_TYPE_RESTORE) die('Queue type not supported');
//if($queue->getStatus() >= Queue::STATUS_DONE) die('Queue already completed');
if($queue->getStatus() < Queue::STATUS_RESTORE_WAITING_FOR_RESTORE) die('Queue not ready yet');

$action = isset($_REQUEST['action']) ? Wordpress::sanitizeTextField(Wordpress::getUnslash($_REQUEST['action'])) : null;
if ($action) ( new Restore( $queue ) )->execute($action);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Restore Progress Status Page</title>
	<link href="<?php echo PUBLIC_PATH.PLUGIN_PATH; ?>/public/libraries/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo PUBLIC_PATH.PLUGIN_PATH; ?>/public/css/restore.css" rel="stylesheet">

	<script type="text/javascript" src="<?php echo PUBLIC_PATH.PLUGIN_PATH; ?>/public/js/restore.js"></script>
	<script type="text/javascript">
		const restore = new Restore({
			queue_id: '<?php echo $queue_id; ?>',
            interval: 1000
		});
	</script>
</head>
<body>
<div class="container">
	<img src="<?php echo PUBLIC_PATH.PLUGIN_PATH; ?>/public/images/logo.png" class="logo" alt="JetBackup Logo">
	<div class="status-header">
		Restore Progress
	</div>
	<div class="status-container">

		<div class="alert alert-danger alert-dismissible fade show" role="alert" id="error-alert" style="display: none;">
			<strong>Error:</strong> <span id="error-message"></span>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="restore.closeError()"></button>
		</div>

        <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert" style="display: none;">
            <strong>Success:</strong> <span id="success-message"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="restore.closeSuccess()"></button>
        </div>

        <div id="progress-bars-container">

            <div class="status-item">
                <div class="progress-container">
                    <label>Overall Progress:</label>
                    <div class="progress">
                        <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
            </div>

            <div class="status-item" id="subprogress-container" style="display: none;">
                <div class="progress-container">
                    <label id="subprogress-title"></label>
                    <div class="progress">
                        <div id="subprogress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
            </div>

        </div>

		<div class="status-item">
			<label>Status Log:</label>
			<div class="log-container" id="log-container">
				<div class="log-entry"></div>
			</div>
		</div>
		<div class="d-flex justify-content-center gap-2">
			<button type="button" class="btn btn-danger" id="cancel-btn" onclick="restore.cancel();">Cancel</button>
			<button type="button" class="btn btn-secondary" id="force-refresh" onclick="restore.refresh();">Force Refresh</button>
			<button type="button" class="btn btn-success btn-lg" id="finalize-btn" onclick="restore.completed();" style="display: none; width: 200px;">Complete Restore</button>
		</div>

	</div>
	<div class="footer">
		&copy; <?php echo date("Y") ?> JetBackup v<?php echo \JetBackup\JetBackup::VERSION ?>. All rights reserved.
	</div>
</div>

<script type="text/javascript">restore.start();</script>

</body>
</html>