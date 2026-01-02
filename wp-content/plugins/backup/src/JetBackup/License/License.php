<?php

namespace JetBackup\License;


use Exception;
use JetBackup\Alert\Alert;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\LicenseException;
use JetBackup\Factory;
use JetBackup\Web\JetHttp;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class License {

	const LICENSE_CHECK_URL = "https://check-v3.jetlicense.com/v2/";
	const LOCALKEY_DAYS = 259200; // 3 days
	const LOCALKEY_CHECK_INTERVAL = 172800; // 2 days
	const LOCALKEY_FAIL_INTERVAL = 3540; // 59 minutes
	const NOTIFY_ERROR_INTERVAL = 21600; // 6 hours
	const LICENSE_PRODUCT_ID = "67bc74749a80571ed902c792";
	const LIST_PRODUCT_ID = [
		self::LICENSE_PRODUCT_ID, // Production
		'63cd2f8bba4b3903835787dc', // Solo
		'63cd2fd30c422d43a55ea0da', // Admin
		'63cd30220c422d43a55ea0db', // Pro
		'64e60930438e804c0a6c1603', // Pro 100
	];
	const PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxseSQBDHnTGbSKaS3A2h
CTbvyQpxMw+t/IXfLRZx+xdwFO7iFx/8nYVxgxbv/Typkq7uomJ/cqxnCrWT6Arz
5OoMgwsiLq0qMb4fXErsBrNt7v4F5AyGzLizVQXnQw06TpXdqYzud/MsfPQFTQKM
p7LDbYO0zSxj9b12Q2cj4Bq5cwvAHDmSasDcLSI7FVqrohTCEKU4/AegFSzGbma3
2mHcENyaFkT4m7fhQ0EbaFTrBw2/vW63TqJy6B71P+6q68dh5s2stwxcogN0Nx62
BPdaHxatGIXJYvxCHDHnFaPuCyZCXWHmOb1j84cuJU28DMiTDSG6g04lp/WRLuq9
EwIDAQAB
-----END PUBLIC KEY-----";

	const STATUS_ACTIVE         = "Active";
	const STATUS_INVALID        = "Invalid";

	/**
	 * @param string $error
	 *
	 * @return void
	 * @throws IOException
	 */
	private static function _addAlert(string $error):void {
		$settings = Factory::getConfig();

		if($settings->getLicenseNotifyDate()) {
			if($settings->getLicenseNotifyDate() > (time()-self::NOTIFY_ERROR_INTERVAL)) return;
			Alert::add("License check failed", "There was a failed license check. Error: $error. Please visit the following link for more information https://docs.jetbackup.com/licensing_issue_notification.html", Alert::LEVEL_CRITICAL);
		}

		$settings->setLicenseNotifyDate(time());
		$settings->save();
	}

	private static function _fetchLicense($product_id, $licenseKey, $domain) {

		$public_key = openssl_get_publickey(static::PUBLIC_KEY);

		if(!openssl_public_encrypt($licenseKey, $key_encrypted, $public_key))
			throw new LicenseException("Unable to prepare license key for validation");

		try {
			$response = JetHttp::request()
				->setMethod(JetHttp::METHOD_POST)
				->setTimeout(30)
				->setReturnTransfer()
				->setBody(http_build_query([
				   'output'	        => 'json',
				   'license_key'    => base64_encode($key_encrypted),
				   'product_id'	    => $product_id,
				   'domain'         => $domain,
				]))
				->exec(self::LICENSE_CHECK_URL);
		} catch(HttpRequestException $e) {
			throw new LicenseException( "Failed checking license (" . self::LICENSE_CHECK_URL . "). Error: " . $e->getMessage());
		}

		if($response->getHeaders()->getCode() !== 200 || !($data = $response->getBody()))
			throw new LicenseException( "Could not resolve host (" . self::LICENSE_CHECK_URL . ")");

		$result = json_decode($data, true);

		if($result === false)
			throw new LicenseException("No valid response received from the license server");

		if(!$result['success'] || !$result['data']['localkey'])
			throw new LicenseException("Invalid response from licensing server: {$result['message']}");

		$localKey = $result['data']['localkey'];

		$newLocalKeyDetails = new LicenseLocalKey($localKey);

		$signed = $newLocalKeyDetails->getSigned() ? base64_decode($newLocalKeyDetails->getSigned()) : '';
		$signed_key = sha1(intval(time() / self::LOCALKEY_DAYS), true);

		if(!openssl_verify($signed_key, $signed, $public_key, OPENSSL_ALGO_SHA256))
			throw new LicenseException( "Failed validating license. " . ($newLocalKeyDetails->getDescription() ? " The error returned from our licensing server: \"" . $newLocalKeyDetails->getDescription() . "\"" : '') . "<br />Please contact your JetApps license provider if you need to reissue your license");

		return $localKey;
	}
	
	/**
	 * @param string|null $licenseKey
	 *
	 * @return void
	 * @throws IOException
	 * @throws LicenseException
	 */
	public static function retrieveLocalKey(?string $licenseKey=null) {
		$config = Factory::getConfig();
		
		$localKeyDetails = new LicenseLocalKey();

		if(!$licenseKey) $licenseKey = $config->getLicenseKey();
		
		if (!$licenseKey) {
			$config->setLicenseLocalKey('');
			$config->setLicenseLastCheck(time());
			$config->setLicenseNextCheck(time() + self::LOCALKEY_CHECK_INTERVAL);
			$config->save();
			return;
		}
		
		try {
			
			$domain = Wordpress::getSiteDomain();
			if (!$domain) throw new LicenseException('Cannot find domain');
			$domain = preg_replace('/^www\./', '', $domain);

			$exception = null;
			
			foreach(self::LIST_PRODUCT_ID as $product_id) {

				try {
					$localKey = self::_fetchLicense($product_id, $licenseKey, $domain);
					$exception = null;
					break;
				} catch(LicenseException $e) {
					$exception = $e;
				}
			}

			if($exception) throw $exception;

			$config->setLicenseNotifyDate();
			$config->setLicenseLocalKey($localKey);
			$config->setLicenseLastCheck(time());
			$config->setLicenseNextCheck(time() + self::LOCALKEY_CHECK_INTERVAL);
			$config->save();

		} catch(LicenseException $e) {
			self::_addAlert($e->getMessage());
			$config->setLocalKeyInvalid($e->getMessage(), $localKeyDetails);
			$config->save();
			throw $e;
		}
	}

	/**
	 * @throws LicenseException
	 * @throws Exception
	 */
	public static function checkLocalKey() {

		$settings = Factory::getConfig();

		if(!$settings->getLicenseKey())
			throw new LicenseException("No license key found");

		$localKey = new LicenseLocalKey();

		if(!$localKey->getLocalKey())
			throw new LicenseException("LocalKey is empty");

		$status = $localKey->getStatus() ?: self::STATUS_INVALID;
		$description = $localKey->getDescription() ?? '';
		$public_key = openssl_get_publickey(static::PUBLIC_KEY);

		$modulo = $settings->getLicenseLastCheck() % self::LOCALKEY_DAYS;
		$signed = $localKey->getSignedStatus() ? base64_decode($localKey->getSignedStatus()) : '';
		$signed_key = sha1(intval((time() - $modulo) / self::LOCALKEY_DAYS) . self::STATUS_ACTIVE, true);

		if(!openssl_verify($signed_key, $signed, $public_key, OPENSSL_ALGO_SHA256)) {
			if($status == self::STATUS_ACTIVE || ($status == self::STATUS_INVALID && !$description)) {
				$description = "Cannot find valid license. " . ($description ? " Error: $description." : '') . " Please visit the following link for more information https://docs.jetbackup.com/licensing_issue_notification.html";
				$settings->setLocalKeyInvalid($description, $localKey);
				$settings->setLicenseNextCheck();
				$settings->save();
			}
			throw new LicenseException($description, $status);
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function getLicenseStatus():string {
		try {
			self::checkLocalKey();
		} catch(LicenseException $e) {
			return $e->getStatus();
		}
		
		return self::STATUS_ACTIVE;
	}

	/**
	 * @return bool
	 */
	public static function isValid():bool {

		try {
			self::checkLocalKey();
		} catch(Exception $e) {
			return false;
		}
		
		return true;
	}
}