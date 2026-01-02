<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception\RuntimeException;

/**
 * Hybridauth storage manager
 */
class Transient implements StorageInterface
{
    /**
     * Namespace
     *
     * @var string
     */
    protected $storeNamespace = 'HYBRIDAUTH::STORAGE';

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = '';

	/**
	 * We need short term data storage, so we will be using WordPress transient
	 *
	 * @var array
	 */
	protected $transient = [];
	
	/**
	 * Key used to identify the Transient
	 *
	 * @var string
	 */
	protected $key = '';

    /**
     * Initiate a new session
     *
     * @throws RuntimeException
     */
	public function __construct()
	{
		if(!empty($_COOKIE['lz_social_login'])){
			$this->key = sanitize_text_field(wp_unslash($_COOKIE['lz_social_login']));
			
			$transient = get_transient($this->key);
			if(!empty($transient)){
				$this->transient = $transient;
			}
			return;
		}

		if(headers_sent()){
			// phpcs:ignore
			throw new RuntimeException('HTTP headers already sent to browser and Hybridauth won\'t be able to start/resume LZ session. To resolve this, cookie must be set before outputing any data.');
		}

		$this->key = 'lz_' . bin2hex(random_bytes(12));

		if(!setcookie('lz_social_login', $this->key, time() + 90, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true)){
			throw new RuntimeException('LZ session failed to start.');
		}
	}

    /**
     * {@inheritdoc}
     */
	public function get($key)
	{	
		if(empty($this->transient)){
			$this->transient = get_transient($this->key);

			if(empty($this->transient) || !is_array($this->transient)){
				return null;
			}
		}

		if(isset($this->transient[$key])){
			$value = $this->transient[$key];

			if(is_array($value) && array_key_exists('lateObject', $value)){
				$value = unserialize($value['lateObject']);
			}

			return $value;
		}

		return null;
	}

    /**
     * {@inheritdoc}
     */
	public function set($key, $value)
	{
		if(is_object($value)){
			// We encapsulate as our classes may be defined after session is initialized.
			$value = ['lateObject' => serialize($value)];
		}

		$this->transient[$key] = $value;
		set_transient($this->key, $this->transient, 60);
	}

    /**
     * {@inheritdoc}
     */
	public function clear()
	{
		delete_transient($this->key);
		setcookie('lz_social_login', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}

    /**
     * {@inheritdoc}
     */
	public function delete($key)
	{
		if(isset($this->transient, $this->transient[$key])){
			unset($this->transient[$key]);
			set_transient($this->key, $this->transient, 60);
		}
	}
	
	 /**
     * {@inheritdoc}
     */
	public function deleteMatch($key)
    {
		if(isset($this->transient) && count($this->transient)){
			foreach($this->transient as $k => $v) {
				if(strstr($k, $key)){
					unset($this->transient[$k]);
				}
			}

			set_transient($this->key, $this->transient, 60);
		}
	}
}
