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

interface Protocol {

	/**
	 * @param ProtocolListener $listener
	 *
	 * @return void
	 */
	public function addListener(ProtocolListener $listener);
}
