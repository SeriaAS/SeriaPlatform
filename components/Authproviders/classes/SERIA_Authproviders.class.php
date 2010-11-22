<?php

class SERIA_Authproviders
{
	protected static $loadProviders = array(
		'SERIA_LocalAuthprovider',
		'SERIA_ExternalAuthprovider',
	);
	protected static $providers = array();
	protected static $creators = array();
	protected static $providerOperations = array();
	protected static $autoDiscoveryEnabled = true;

	public static function disableAutomaticDiscovery()
	{
		self::$autoDiscoveryEnabled = false;
	}

	public static function addProviderClass($pclass)
	{
		self::$loadProviders[] = $pclass;
		
	}
	public static function addProvider(SERIA_IAuthprovider &$provider)
	{
		if (!$provider)
			throw new Exception('NULL/false passed as object.');
		self::$providers[] =& $provider;
	}

	public static function loadProviders($className=false, $clearAll=false)
	{
		static $loaded = false;
		static $classes = array();

		if ($clearAll) {
			$loaded = false;
			$classes = array();
			self::$providerOperations = array();
			self::$creators = array();
			self::$providers = array();
		}

		if ($className === false) {
			if ($loaded)
				return;
			else
				$loaded = true;
		} else {
			if (isset($classes[$className]))
				return;
			$classes[$className] = true;
			$ops = call_user_func(array($className, 'loadProviders'));
			if ($ops) {
				self::$providerOperations[$className] = $ops;
				if (isset($ops['new'])) {
					self::$creators[] = array(
						'id' => $className,
						'caption' => $ops['new']['caption'],
						'call' => $ops['new']['call']
					);
				}
			}
			return;
		}
		foreach (self::$loadProviders as $load) {
			if (isset($classes[$load]))
				continue;
			$classes[$load] = true;
			$ops = call_user_func(array($load, 'loadProviders'));
			if ($ops) {
				self::$providerOperations[$load] = $ops;
				if (isset($ops['new'])) {
					self::$creators[] = array(
						'id' => $load,
						'caption' => $ops['new']['caption'],
						'call' => $ops['new']['call']
					);
				}
			}
		}
	}
	public static function getProviders()
	{
		return self::$providers;
	}
	public static function getProviderUrl($provider, $continueUrl=false, $loginPage=false, $loginState=false)
	{
		if (is_a($loginPage, 'SERIA_Url'))
			$loginPage = $loginPage->__toString();
		$url = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/provider.php');
		$url->setParam('providerClass', get_class($provider));
		$url->setParam('provider', $provider->getProviderId());
		if ($continueUrl !== false)
			$url->setParam('continue', $continueUrl);
		if ($loginPage !== false)
			$url->setParam('login', $loginPage);
		if ($loginState)
			$url = $loginState->stampUrl($url);
		return $url->__toString();
	}
	public static function getAllProviderUrls($continueUrl=false, $loginPage=false, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		$loginState = new SERIA_AuthenticationState();
		if ($loginPage === false) {
			if ($loginType == SERIA_IAuthprovider::LOGIN_SYSTEM)
				$loginPage = SERIA_HTTP_ROOT.'/seria/platform/pages/login.php';
			else if ($loginType == SERIA_IAuthprovider::LOGIN_GUEST)
				$loginPage = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/guestLogin.php';
			else
				throw new SERIA_Exception('No login page specified and unknown login-type.');
		}
		if (!is_a($loginPage, 'SERIA_Url'))
			$loginPage = new SERIA_Url($loginPage);
		if (!$continueUrl)
			$continueUrl = SERIA_Url::current()->__toString();
		self::loadProviders();
		$providers = array();
		foreach (self::getProviders() as $provider) {
			if (!$provider->isEnabled($loginType) || !$provider->isAvailable())
				continue;
			$providers[$provider->getProviderId()] = array(
				'class' => get_class($provider),
				'name' => $provider->getName(),
				'url' => self::getProviderUrl($provider, $continueUrl, $loginPage, $loginState)
			);
			unset($provider);
		}
		return $providers;
	}
	public static function getCreators()
	{
		return self::$creators;
	}
	public static function &getProvider($providerId)
	{
		foreach (self::$providers as &$provider) {
			if ($provider->getProviderId() == $providerId)
				return $provider;
		}
		return null;
	}
	public static function getProviderOperations($provider)
	{
		$cName = get_class($provider);
		if (isset(self::$providerOperations[$cName]))
			return self::$providerOperations[$cName];
		else
			return false;
	}

	protected static $currentProviderInCall = null;
	protected static $currentGuestLogin = null;
	public static function callAuthenticationProvider_beforeLogin()
	{
		SERIA_Base::debug('Before login hooked.');
		if (!self::$currentProviderInCall)
			return;
		if (self::$currentGuestLogin)
			SERIA_Base::blockSystemAccess();
	}
	public static function callAuthenticationProvider_login()
	{
		SERIA_Base::debug('Login hooked.');
		if (!self::$currentProviderInCall)
			return;
		SERIA_Components::getComponent('seria_authproviders')->loggedInByProvider(self::$currentProviderInCall);
	}
	public static function callAuthenticationProvider(&$provider, $interactive=true, $reset=false, $guestLogin=false)
	{
		static $hooked = false;

		SERIA_Base::debug('Calling provider '.$provider->getName().' for '.($guestLogin ? 'guest ' : '').'login.');
		if (!$hooked) {
			$hooked = true;
			SERIA_Hooks::listen(SERIA_Base::BEFORE_LOGIN, array('SERIA_Authproviders', 'callAuthenticationProvider_beforeLogin'));
			SERIA_Hooks::listen(SERIA_Base::LOGGED_IN, array('SERIA_Authproviders', 'callAuthenticationProvider_login'));
		}
		$stack = array(
			'provider' => &self::$currentProviderInCall,
			'guest' => self::$currentGuestLogin
		);
		self::$currentProviderInCall =& $provider;
		self::$currentGuestLogin = $guestLogin;
		$res =& $provider->authenticate($interactive, $reset, $guestLogin);
		self::$currentGuestLogin = $stack['guest'];
		self::$currentProviderInCall =& $stack['provider'];
		SERIA_Base::debug('Provider returned.');
		return $res;
	}

	public static function getHandshakeReturnUrl(&$provider, $interactive, $guestLogin=false)
	{
		$continue = false;
		if (isset($_GET['continue']))
			$continue = $_GET['continue'];
		if (!$continue)
			$continue = SERIA_Url::current()->__toString(); /* Autodiscovery login is called directly on a normal page view */
		$url = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/handleLogin.php');
		return $url->__toString();
	}
	protected static function doAuthenticateSelected(&$provider)
	{
		return array(
			'result' => self::callAuthenticationProvider($provider),
			&$provider
		);
	}
	public static function authenticate($interactive=true, $guestLogin=false)
	{
		try {
			if (SERIA_AuthenticationState::available()) {
				$state = new SERIA_AuthenticationState();
				if ($state->exists('callProvider'))
					$callProvider = $state->get('callProvider');
			} else if (isset($_GET['provider']))
				$callProvider = isset($_GET['provider']);
			else
				$callProvider = false;
			if ($callProvider) {
				foreach (self::$providers as &$provider) {
					if ($provider->getProviderId() == $callProvider)
						return array(
							'result' => self::callAuthenticationProvider($provider, $interactive, /*reset=*/false, $guestLogin),
							'provider' => &$provider
						);
				}
				throw new SERIA_Exception('Unknown authprovider: '.$callProvider);
			} else {
				if (!$interactive)
					return; /* Non-interactive should go through autodiscovery to start auth */
				$avail = array();
				foreach (self::$providers as &$provider) {
					if ($provider->isEnabled($guestLogin ? SERIA_IAuthprovider::LOGIN_GUEST : SERIA_IAuthprovider::LOGIN_SYSTEM) && $provider->isAvailable())
						$avail[] =& $provider;
				}
				unset($provider);
				if (count($avail) == 1) {
					/*
					 * One option: Start authenticating right away..
					 */
					return array(
						'result' => self::callAuthenticationProvider($avail[0], $interactive, /*reset=*/false, $guestLogin),
						'provider' => &$avail[0]
					);
				} else if (count($avail) > 1) {
					/*
					 * Have to show options..
					 */
					$gui = new SERIA_Gui(_t('Please select login provider'));
					$gui->contents(SERIA_Components::getComponent('seria_authproviders')->parseTemplateToString('choose', array(
						'providers' => $avail,
						'gui' => $gui
					)));
					echo $gui->output();
					return true;
				} else
					return false; /* Good, no options available! This will probably fall back to local anyway */
			}
		} catch (SERIA_LocalAuthprovider $local) {
			return false; /* Intentional fallback to local! */
		}
		throw new SERIA_Exception('Unhandled authentication call');
	}
	protected static function doHandleLogin($interactive, $guestLogin)
	{
		SERIA_Authproviders::loadProviders();
		do {
			/*
			 * Go through the list of available login mechanisms.
			 */
			$result = SERIA_Authproviders::authenticate($interactive, $guestLogin);
		} while (is_array($result) && $result['result'] === false);
		if (is_array($result)) {
			if (SERIA_Base::user() !== false) {
				/*
				 * Logged in: redirect..
				 */
				$state = new SERIA_AuthenticationState();
				$state->terminate('continue');
			}
			if ($result['result'] && is_string($result['result'])) {
				$state = new SERIA_AuthenticationState();
				SERIA_Base::redirectTo($state->stampUrl($result['result']));
				die();
			}
			return $result['result'];
		} else
			return $result;
	}
	public static function handleLogin($interactive=true)
	{
		return self::doHandleLogin($interactive, false);
	}
	public static function handleGuestLogin($interactive=true)
	{
		return self::doHandleLogin($interactive, true);
	}
	public static function automaticDiscovery()
	{
		global $seria_options;

		SERIA_Base::debug('Automatic discovery called..');
		if (!self::$autoDiscoveryEnabled) {
			if (!SERIA_AuthenticationState::available())
				return;
			$loginState = new SERIA_AuthenticationState();
			if (!$loginState->exists('autoDiscovery'))
				return;
			else
				SERIA_Base::debug('Autodiscovery was disabled, but since we own the state-object we are going to continue anyway..');
		}
		if ((isset($seria_options['skip_authentication']) && $seria_options['skip_authentication']) ||
		    (isset($seria_options['skip_session']) && $seria_options['skip_session']))
			return;
		if (!SERIA_AuthenticationState::available()) {
			if (isset($_SESSION['disableAutoLogin']) && $_SESSION['disableAutoLogin'] >= time())
				return; /* quarantined */
			$created = true;
			SERIA_Base::debug('Creating a state-object for authentication..');
		} else
			$created = false;
		$loginState = new SERIA_AuthenticationState();
		if (!$created && !$loginState->exists('autoDiscovery')) {
			SERIA_Base::debug('Aborting autodiscovery because of other auth-process');
			return; /* Don't interfere with another login procedure */
		}
		if (SERIA_Base::user() !== false) {
			if ($loginState->exists('continue'))
				$loginState->terminate('continue');
			return;
		}
		$loginState->set('autoDiscovery', true);
		if ($loginState->exists('autoDiscoveryVector'))
			$wait = $loginState->get('autoDiscoveryVector');
		else
			$wait = false;
		if (!$loginState->exists('continue'))
			$loginState->set('continue', SERIA_Url::current()->__toString());
		$preCheck = 0;
		foreach (self::$loadProviders as $load) {
			if (call_user_func(array($load, 'automaticDiscoveryPreCheck'))) {
				SERIA_Base::debug('Loading providers for auto: '.$load);
				self::loadProviders($load);
				$preCheck++;
			}
		}
		if (!$preCheck) {
			if (!$created)
				$loginState->terminate('abort');
			$loginState->forget();
			return;
		}
		foreach (self::$providers as &$provider) {
			if (!$wait) {
				SERIA_Base::debug('Auto login (N) discovery checking for availability and login for: '.$provider->getName());
				if ($provider->isEnabled(SERIA_IAuthprovider::LOGIN_AUTO) && $provider->isAvailable() && $provider->automaticDiscovery()) {
					SERIA_Base::debug('Auto login (N) discovery, will try to log in with: '.$provider->getName());
					$loginState->push('continue', $loginState->stampUrl(SERIA_Url::current()->setParam('returned', $provider->getProviderId()))->__toString());
					$loginState->set('autoDiscoveryVector', $provider->getProviderId());
					$loginState->set('autoDiscoveryVectorTries', 1);
					$res = self::callAuthenticationProvider($provider, false, /*reset:*/true);
					if ($res && is_string($res)) {
						SERIA_Base::redirectTo($loginState->stampUrl($res));
						die();
					}
					if ($res === true && SERIA_Base::user() !== false) {
						SERIA_Components::getComponent('seria_authproviders')->loggedInByProvider($provider);
						$loginState->terminate('continue');
					}
					/*
					 * Continuing straight through means failure..
					 */
					$loginState->pop('continue');
					$loginState->set('autoDiscoveryVector', false);
				}
			}
			if ($wait == $provider->getProviderId()) {
				SERIA_Base::debug('Auto login (N) discovery returned to login for: '.$provider->getName());
				$continue = $loginState->get('continue');
				if (!is_array($continue) || count($continue) == 1) {
					SERIA_Base::debug('Need to put back a continue back to me url.');
					$loginState->push('continue', $loginState->stampUrl(SERIA_Url::current()->setParam('returned', $provider->getProviderId()))->__toString());
				}
				/*
				 * This is always not the first call (second or later call)..
				 */
				if ($loginState->get('autoDiscoveryVectorTries') <= 5) {
					$loginState->set('autoDiscoveryVectorTries', $loginState->get('autoDiscoveryVectorTries') + 1);
					$res = self::callAuthenticationProvider($provider, false, /*reset:*/false);
					if ($res && is_string($res)) {
						SERIA_Base::redirectTo($loginState->stampUrl($res));
						die();
					}
					if ($res === true && SERIA_Base::user() !== false) {
						SERIA_Components::getComponent('seria_authproviders')->loggedInByProvider($provider);
						$loginState->terminate('continue');
					}
					/*
					 * Continuing straight through means failure..
					 */
					$continue = $loginState->get('continue');
					if (is_array($continue) && count($continue) > 1)
						$loginState->pop('continue');
					$wait = false;
				} else
					SERIA_Base::debug('Autodiscovery vector retry limit reached, continuing without retrying.');
			}
		}
		SERIA_Base::debug('No auto-providers were able to log in..');
		$_SESSION['disableAutoLogin'] = time() + 60; /* quarantine for a while */
		if (!$created)
			$loginState->terminate('abort');
		$loginState->forget();
	}

	public static function userObjectHook($user)
	{
		$mappings = SERIA_Base::db()->query('SELECT authprovider_id FROM {authproviders_user_mapping} WHERE user_id = :user_id', array('user_id' => $user->get('id')))->fetchAll(PDO::FETCH_NUM);
		if (!$mappings)
			return;

		self::loadProviders();
		foreach ($mappings  as $mapping) {
			if ($mapping[0]) {
				foreach (self::$providers as &$provider) {
					if ($provider->getProviderId() == $mapping[0]) {
						$provider->userObjectHook($user);
					}
				}
				unset($provider);
			}
		}
	}
	public static function hookIntoUserObject(&$provider, &$user)
	{
		$fields = array(
			'user_id' => $user->get('id'),
			'authprovider_id' => $provider->getProviderId()
		);
		SERIA_Base::db()->insert('{authproviders_user_mapping}', array_keys($fields), $fields);
	}
}
