<?php

namespace JetBackup\Destination\Vendors\JetStorage;
use JetBackup\Destination\Vendors\S3\S3;
use JetBackup\Exception\FieldsValidationException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class JetStorage extends S3 {
	const TYPE = 'JetStorage';

    /**
     * @return string
     */
    public function getQuickAccessCode():string { return $this->getOptions()->get('quick_access_code'); }

    /**
     * @param string $key
     * @return void
     */
    protected function setQuickAccessCode(string $key):void { $this->getOptions()->set('quick_access_code', trim($key)); }

    /**
     * @return void
     * @throws FieldsValidationException
     */
    public function validateFields(): void {
         parent::validateFields();
    }
}
