<?php

namespace JetBackup\MFA;

use Exception;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;
use SimpleThenticator\SimpleAuthenticator;

if (!defined('__JETBACKUP__')) die('Direct access is not allowed');

class GoogleAuthenticator {

	const MFA_KEY = 'jetbackup_mfa_google_authenticator';
	const MFA_COOKIE_KEY = 'jetbackup_mfa_auth';
	const MFA_SETUP_COMPLETED = 'jetbackup_mfa_setup_completed';

	const MFA_MAX_ATTEMPTS = 10;
	const MFA_MAX_ATTEMPTS_KEY = 'jetbackup_mfa_max_attempts';

	private static ?SimpleAuthenticator $authenticator = null;

	private static function getAuthenticator(): SimpleAuthenticator {
		if (self::$authenticator === null) {
			self::$authenticator = new SimpleAuthenticator();
		}
		return self::$authenticator;
	}

	/**
	 * Create a secret and store it for the given user ID.
	 *
	 * @throws Exception
	 */
	public static function createSecret(): string {

		$userId = Helper::getUserId();
		$secret = self::getAuthenticator()::createSecret();
		if (!Wordpress::updateUserMeta($userId, self::MFA_KEY, $secret, '')) {
			throw new Exception('Failed to save MFA secret for user ID: ' . $userId);
		}

		return $secret;
	}

	public static function getCookieHash(): string {
		return  hash_hmac('sha512', Wordpress::getAuthSalt(), Wordpress::getAuthKey());
	}

	public static function setCookie(): void {
		$expire = time() + 86400;
		$path = defined('COOKIEPATH') ? COOKIEPATH : '/';
		$domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
		$secure = is_ssl();

		setcookie(
			self::MFA_COOKIE_KEY,
			self::getCookieHash(),
			[
				'expires' => $expire,
				'path' => $path,
				'domain' => $domain,
				'secure' => $secure,
				'httponly' => true,
				'samesite' => 'None'
			]
		);
	}

	/**
	 * Clear the MFA setup for a user.
	 */
	public static function clearCookie(): void {
		$userId = Helper::getUserId();

		$expire = time() - 3600;
		$path = defined('COOKIEPATH') ? COOKIEPATH : '/';
		$domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
		$secure = is_ssl();

		setcookie(
			self::MFA_COOKIE_KEY,
			'',
			[
				'expires' => $expire,
				'path' => $path,
				'domain' => $domain,
				'secure' => $secure,
				'httponly' => true,
				'samesite' => 'None'
			]
		);

		// Clear user metadata for MFA
		Wordpress::deleteUserMeta($userId, self::MFA_KEY);
		Wordpress::deleteUserMeta($userId, self::MFA_SETUP_COMPLETED);
	}


	public static function isSetupCompleted(): bool {
		$userId = Helper::getUserId();
		return Wordpress::getUserMeta($userId, self::MFA_SETUP_COMPLETED, true) ?? false;
	}

	/**
	 * Generate a QR Code URL for Google Authenticator.
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getQRcode(): array {

		$authenticator = self::getAuthenticator();
		$domain = Wordpress::getSiteDomain();
		$label = "JetBackup [$domain]";
		$userId = Helper::getUserId();
		$secret = Wordpress::getUserMeta($userId, self::MFA_KEY, true);
		if (!$secret || $secret == '') $secret = self::createSecret();

		$setupCompleted = self::isSetupCompleted();
		return [
			'code' => $setupCompleted ? '' : $authenticator->getQRCodeGoogleUrl($secret, $label),
			'isFirstTime' => !$setupCompleted,
		];

	}

	/**
	 * Verify the provided MFA code.
	 *
	 * @param int $code
	 *
	 * @return bool
	 */
	public static function verifyCode(int $code): bool {
		$userId = Helper::getUserId();
		$secret = Wordpress::getUserMeta($userId, self::MFA_KEY, true);
		if (!$secret) return false;

		$attempts = (int) Wordpress::getUserMeta($userId, self::MFA_MAX_ATTEMPTS_KEY, true);

		if ($attempts > 0) usleep(min(pow($attempts, 2) * 100000, 3000000)); // up to 3s
		$isValid = self::getAuthenticator()->verifyCode($secret, $code, 3, null);

		if ($isValid) {
			Wordpress::updateUserMeta($userId, self::MFA_SETUP_COMPLETED, 'true', '');
			Wordpress::deleteUserMeta($userId, self::MFA_MAX_ATTEMPTS_KEY);
			return true;
		}

		// Failed attempt
		$attempts++;
		Wordpress::updateUserMeta($userId, self::MFA_MAX_ATTEMPTS_KEY, $attempts, '');

		// If max attempts reached, logout
		if ($attempts >= self::MFA_MAX_ATTEMPTS) {
			Wordpress::wpLogout();
			Wordpress::wpRedirect(Wordpress::wpLoginURL('', true));
			exit;
		}

		return false;
	}


}