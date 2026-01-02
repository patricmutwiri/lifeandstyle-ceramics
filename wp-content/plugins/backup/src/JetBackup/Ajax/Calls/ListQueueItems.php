<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\ListRecord;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Queue\QueueItem;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListQueueItems extends ListRecord {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getType():int { return $this->getUserInput(QueueItem::TYPE, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
		
		$query = QueueItem::query();
		
		if($this->getType()) $query->where([QueueItem::TYPE, '=', $this->getType()]);
		if($filter = $this->getFilter()) $query->search(['progress.message'], $filter);

		$output = $this->isCLI() ? [] : [ 'items' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());
		
		$list = $query->getQuery()->fetch();
		
		foreach($list as $item_details) {
			$item = new QueueItem($item_details[JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $item->getDisplayCLI();
			else $output['items'][] = $item->getDisplay();
		}
		$this->setResponseData($output);
	}
}