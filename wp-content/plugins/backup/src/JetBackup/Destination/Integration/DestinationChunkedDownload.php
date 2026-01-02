<?php

namespace JetBackup\Destination\Integration;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface DestinationChunkedDownload {

	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 */
	public function download(int $start, int $end):int;
}