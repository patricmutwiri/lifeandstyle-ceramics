<?php

namespace JetBackup\Exception;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Alert\Alert;
use Throwable;

class GzipException  extends Exception  {
	public function __construct( $message = null, $code = 0, Throwable $previous = null ) {
		// make sure everything is assigned properly
		parent::__construct( $message, $code, $previous );
		Alert::add('GzipCompressorException Error', $message, Alert::LEVEL_CRITICAL);
	}

}