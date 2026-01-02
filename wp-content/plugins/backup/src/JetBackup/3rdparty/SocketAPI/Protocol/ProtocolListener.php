<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\SocketAPI\Protocol;

interface ProtocolListener {
	
	/**
	 * @param string $message
	 *
	 * @return mixed
	 */
	public function onMessageReady($message);
}
