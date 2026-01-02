<?php

namespace JetBackup\Integrations;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface Integrations {

	public function execute() : void ;

}