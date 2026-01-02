<?php

namespace JetBackup\Queue;

use JetBackup\Data\ArrayData;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Progress extends ArrayData {

	const MESSAGE = 'message';
	const TOTAL_ITEMS = 'total_items';
	const CURRENT_ITEM = 'current_item';
	const SUB_MESSAGE = 'sub_message';
	const TOTAL_SUB_ITEMS = 'total_sub_items';
	const CURRENT_SUB_ITEM = 'current_sub_item';
	const PERCENTAGE = 'percentage';
	const SUB_PERCENTAGE = 'sub_percentage';

	public function __construct(array $data=[]) {
		$this->setData($data);
	}

	public function setMessage(string $message):void { $this->set(self::MESSAGE, $message); }
	public function getMessage():string { return $this->get(self::MESSAGE); }

	public function setTotalItems(int $items):void { $this->set(self::TOTAL_ITEMS, $items); }
	public function getTotalItems():int { return $this->get(self::TOTAL_ITEMS, 1); }

	public function setCurrentItem(int $current):void { $this->set(self::CURRENT_ITEM, $current); }
	public function getCurrentItem():int { return $this->get(self::CURRENT_ITEM, 0); }

	public function setSubMessage(string $message):void { $this->set(self::SUB_MESSAGE, $message); }
	public function getSubMessage():string { return $this->get(self::SUB_MESSAGE); }

	public function setTotalSubItems(int $items):void { $this->set(self::TOTAL_SUB_ITEMS, $items); }
	public function getTotalSubItems():int { return $this->get(self::TOTAL_SUB_ITEMS, 0); }

	public function setCurrentSubItem(int $current):void { $this->set(self::CURRENT_SUB_ITEM, $current); }
	public function getCurrentSubItem():int { return $this->get(self::CURRENT_SUB_ITEM, 0); }

	public function increaseCurrentItem():void {  $this->setCurrentItem($this->getCurrentItem() + 1); }
	public function increaseCurrentSubItem():void {  $this->setCurrentSubItem($this->getCurrentSubItem() + 1); }
	
	public function getPercentage():int { return $this->getTotalItems() > 0 ? min(100, floor(($this->getCurrentItem() / $this->getTotalItems()) * 100)) : 0; }
	public function getSubPercentage():int { return $this->getTotalSubItems() > 0 ? min(100, floor(($this->getCurrentSubItem() / $this->getTotalSubItems()) * 100)) : 0; }
	
	public function resetSub() {
		$this->setSubMessage('');
		$this->setTotalSubItems(0);
		$this->setCurrentSubItem(0);
	}
	
	public function getDisplay() {
		return [
			self::MESSAGE               => $this->getMessage(),
			self::TOTAL_ITEMS           => $this->getTotalItems(),
			self::CURRENT_ITEM          => $this->getCurrentItem(),
			self::PERCENTAGE            => $this->getPercentage(),
			self::SUB_MESSAGE           => $this->getSubMessage(),
			self::TOTAL_SUB_ITEMS       => $this->getTotalSubItems(),
			self::CURRENT_SUB_ITEM      => $this->getCurrentSubItem(),
			self::SUB_PERCENTAGE        => $this->getSubPercentage(),
		];
	}
}
