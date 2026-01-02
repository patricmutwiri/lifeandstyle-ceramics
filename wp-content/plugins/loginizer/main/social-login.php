<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

include_once dirname(__FILE__, 2) . '/lib/hybridauth/autoload.php';
include_once __DIR__ .'/social-base.php';

use Hybridauth\Exception\Exception;
use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;
use Hybridauth\Storage\Transient;

class Loginizer_Social_Login extends Loginizer_Social_Base{

	// Process all the requests from here.
	static function login_init(){
		global $loginizer;

		try {			
			self::$storage = new Transient();

			// Security check here.
			$lz_social_nonce = '';
			if(!empty($_GET['social_security'])){
				$lz_social_nonce = sanitize_text_field(wp_unslash($_GET['social_security']));
			} elseif(!empty(self::$storage) && !empty(self::$storage->get('social_security'))){
				$lz_social_nonce = sanitize_text_field(self::$storage->get('social_security'));
			}

			if(!wp_verify_nonce($lz_social_nonce, 'loginizer_social_check')){
				self::$error['security-check'] = __('Security check failed when trying to login', 'loginizer');
				self::trigger_error();
				return;
			}

			$providers = self::build_provider_arr();

			if(empty($providers)){
				self::$error['login_error'] = __('No Provider is configured, please contact the admin about this issue', 'loginizer');
				self::trigger_error();
				return;
			}

			$callback_query = [];
			$callback_query['lz_social_provider'] = lz_optget('lz_social_provider');

			$config = [
				// Location where to redirect users once they authenticate with a provider
				'callback' => wp_login_url().'?'.http_build_query($callback_query),

				// Providers specifics
				'providers' => $providers
			];

			$hybridauth = new Hybridauth($config, null, self::$storage);
	
			//Step 1: Here we will be redirected to the App Auth page
			if(!empty($_GET['lz_social_provider'])){
				if(is_array($hybridauth->getProviders()) && in_array($_GET['lz_social_provider'], $hybridauth->getProviders())) {
					// Store the provider for the callback event
					self::$storage->set('provider', lz_optget('lz_social_provider'));
					
					if(!empty($_REQUEST['test'])){
						self::$storage->set('test', true);
					}

					self::$storage->set('social_security', wp_create_nonce('loginizer_social_check'));

					if(!empty($_REQUEST['ref']) && wp_http_validate_url(sanitize_url(wp_unslash($_REQUEST['ref'])))){
						self::$storage->set('ref', rawurlencode(sanitize_url(wp_unslash($_REQUEST['ref']))));
					}
					
					if(isset($_REQUEST['interim-login'])){
						self::$storage->set('interim_login', 'lz');
					}

				} else {
					self::$error['provider_error'] = esc_html__('The app you are trying to login through is not configured', 'loginizer');
					self::trigger_error();
					return;
				}
			}

			// Step 2: After we are back from the Apps auth page.
			if($provider = self::$storage->get('provider')){
				if(!is_array($hybridauth->getProviders()) || !in_array($provider, $hybridauth->getProviders())) {
					self::$error['provider_error'] = esc_html__('The app you are trying to login through is not configured', 'loginizer');
					self::trigger_error();
					return;
				}

				$hybridauth->authenticate($provider);

				self::$storage->set('provider', null); // Cleaning
				self::$storage->delete('social_security');

				self::$provider = $provider;
				
				if(self::$storage->get('test')){
					self::$storage->delete('test');
					self::$test = true;
				}
				
				if(self::$storage->get('ref')){
					self::$ref = rawurldecode(self::$storage->get('ref'));
					self::$storage->delete('ref');
				}
				
				if(self::$storage->get('interim_login')){
					self::$interim_login = self::$storage->get('interim_login');
					self::$storage->delete('interim_login');
				}

				// Retrieve the provider record
				$adapter = $hybridauth->getAdapter($provider);
				$userProfile = $adapter->getUserProfile();
				$accessToken = $adapter->getAccessToken();
				
				// Check if the user have account which is verified
				if(empty($userProfile->emailVerified)){
					self::$error['login_failed'] = __('The social account you are using does not have a verified email.', 'loginizer');
					$adapter->disconnect();
					self::trigger_error();
					return;
				}

				$data = [
					'access_token' => $accessToken,
					'identifier' => $userProfile->identifier,
					'email' => $userProfile->email,
					'first_name' => $userProfile->firstName,
					'last_name' => $userProfile->lastName,
					'photoURL' => strtok($userProfile->photoURL, '?'),
				];

				$adapter->disconnect();

				if(empty($data['email'])){
					self::$error['login_failed'] = __('No email details were returned !', 'loginizer');
					self::trigger_error();
					return;
				}
				
				// If it is a test then, we are satisfied that the Provider is returning data
				// As this verifies the provider is working.
				if(self::$test === true){
					self::close_tab();
					return;
				}

				// Create an account if it does not exists.
				if(empty(email_exists(sanitize_email($data['email'])))){
					if(self::$test === true){
						self::$error['test_error'] = __('The email you are using for the test is not registered on this website. So register this email first.', 'loginizer');
						self::trigger_error();
						return;
					}
					
					if(defined('LOGINIZER_PREMIUM') && !empty($loginizer['social_settings']['general']['register_new'])){
						self::register_account($data);
						echo 'Register Acount';
						return;
					}

					self::$error['login_error'] = __('You can not register through Social Login', 'loginizer');
					self::trigger_error();
					return;
				}

				$user = get_user_by('email', sanitize_email($data['email']));
				if(empty($user)){
					self::$error['login_error'] = __('User with this email does not exists.', 'loginizer');
					self::trigger_error();
					return;
				}

				$authenticated = loginizer_wp_authenticate($user, $user->user_login, $user->user_pass);
				if(is_wp_error($authenticated)){
					return;
				}

				self::login_user($user);
				self::close_tab();
			}

		}catch(\Exception $e){
			@error_log('Loginizer Log(Social): '. esc_html($e->getMessage()));
			self::$error['login_error'] = __('Oops, we ran into an issue! ', 'loginizer') . $e->getMessage();
			self::trigger_error();
			//wp_safe_redirect(wp_login_url());
			return;
		}
	}

	/**
	 * Creates an array of Config which is valid for HybridAuth using the setting of the provider.	
	 *
	 * @return mixed[]
	 */
	private static function build_provider_arr(){
		$config = [];
		$providers = get_option('loginizer_provider_settings', []);
		
		if(empty($providers)){
			return $config;
		}

		foreach($providers as $key => $provider){

			if(empty($provider['enabled']) || empty($provider['client_id']) || empty($provider['client_secret'])){
				continue;
			}

			$config_index = ucfirst($key);

			$config[$config_index] = [
				'enabled' => true,
				'keys' => [
					'id' => $provider['client_id'],
					'secret' => $provider['client_secret']
				]
			];
			
			if($key = 'MicrosoftGraph' && !empty($provider['account_type']) && $provider['account_type'] != 'common'){
				$config[$config_index]['tenant'] = $provider['account_type'];
			}
		}
		
		return $config;
	}
}

Loginizer_Social_Login::login_init();