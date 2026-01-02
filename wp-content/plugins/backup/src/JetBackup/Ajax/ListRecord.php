<?php

namespace JetBackup\Ajax;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class ListRecord extends aAjax {

	/**
	 * @return int
	 */
	public function getSkip():int { return (int) $this->_data->get('skip', 0); }

	/**
	 * @return int
	 */
	public function getLimit():int { return (int) $this->_data->get('limit', 0); }

	/**
	 * @return array
	 */
	public function getSort():array { return (array) $this->_data->get('sort', []); }

	/**
	 * @return array
	 */
	public function getFind():array { return $this->_data->get('find', []); }

	/**
	 * @return string
	 */
	public function getFilter():string { return $this->_data->get('filter'); }

}