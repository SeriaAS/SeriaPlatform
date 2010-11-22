<?php
	class SERIA_AuthprovidersComponent extends SERIA_Component
	{
		protected $loggedInByProvider = null;
		protected $autoDiscoveryCookie = false;
		private $active = false;

		function setActive($state)
		{
			$this->active = $state;
		}

		// returns a string that uniquely identifies the application. Two applications that are incompatible can never share the unique name
		function getId() { return 'seria_authproviders'; }
		function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/components/authproviders'; }
		function getInstallationPath() { return dirname(dirname(__FILE__)); }

		// returns a string with the name of the application. This string should be translated before it is returned.
		function getName() { return _t('Seria Authproviders');}

		function __construct()
		{
		}

		public function isEnabled()
		{
			return defined('SERIA_AUTHPROVIDERS_ENABLED') && SERIA_AUTHPROVIDERS_ENABLED;
		}
		// after all applications have been loaded, the embed() is called for each application
		function embed()
		{
			SERIA_Router::instance()->addRoute('Authproviders', 'Authproviders config', array($this, 'showConfigurationPage'), 'components/authproviders/config');
			SERIA_Router::instance()->addRoute('Authproviders', 'Authproviders config check', array($this, 'showConfigCheck'), 'components/authproviders/configcheck');
			SERIA_Router::instance()->addRoute('Authproviders', 'Local usermanagement is blocked', array($this, 'showUsermanagementBlockage'), 'components/authproviders/usermanagement');
			if ($this->isEnabled()) {
				SERIA_Hooks::listen('SERIA_Base::user', array($this, 'userObjectRequested'));
				SERIA_Hooks::listen('SeriaPlatformBootComplete', array($this, 'platformBootComplete'));
				SERIA_Hooks::listen('login', array($this, 'handleLogin'));
				SERIA_Hooks::listen('guestLogin', array($this, 'handleGuestLogin'));
				SERIA_Hooks::listen('SERIA_User::__construct', array('SERIA_Authproviders', 'userObjectHook'));
			}
			SERIA_Hooks::listen('seria_maintain', array('SERIA_UserLoginXml', 'cleanupSids'));
			SERIA_Hooks::listen('seria_maintain', array('SERIA_AsyncUserMetaSync', 'updateAllUsers'));
			SERIA_Hooks::listen('loggedIn', array($this, 'successfulLogin'));
			SERIA_Hooks::listen(SERIA_Base::LOGOUT_HOOK, array($this, 'beforeLogout'));
			SERIA_Hooks::listen(SERIA_Base::AFTER_LOGOUT_HOOK, array($this, 'logout'));
			SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($this, 'guiEmbed'));
		}
		public function guiEmbed($gui)
		{
			$gui->addMenuItem('controlpanel/settings/authproviders', _t('Authentication'), _t('Seria Platform can support several authentication services through this application.'), SERIA_HTTP_ROOT.'?route=components/authproviders/config', SERIA_HTTP_ROOT.'/seria/components/Authproviders/icon.png', 100);

			/* Block local user management if we are using remote authentication */
			try {
				if (SERIA_AuthprovidersConfiguration2::usingExternalAuthentication()) {
					$blockageUrl = SERIA_HTTP_ROOT.'?route=components/authproviders/usermanagement';
					$gui->addMenuItem('controlpanel/users', _t("User management"), _t("Manage system user accounts and rights"), $blockageUrl, SERIA_HTTP_ROOT.'/seria/components/Authproviders/icon.png', 0);
					$gui->addMenuItem('controlpanel/users/list', _t("List user accounts"), _t("Display all user accounts."), $blockageUrl, SERIA_HTTP_ROOT.'/seria/components/Authproviders/icon.png', 0);
					$gui->addMenuItem('controlpanel/users/edit', _t("Create user"), _t("Create a user account."), $blockageUrl, SERIA_HTTP_ROOT.'/seria/components/Authproviders/icon.png', 0);
				}
			} catch (PDOException $e) {
				SERIA_Base::debug('Warning: Database failure in authproviders gui-embed: '.$e->getMessage());
				/* A database will just turn off the useredit blockage. Nothing critical here. */
			}
		}

		/*
		 * This is called on every page view except skip_*
		 */
		public function userObjectRequested($setUser)
		{
			static $firstCall = true;

			/* This does also provide recursion protection */
			if (!$firstCall)
				return;
			$firstCall = false;
			if (sizeof($_POST) == 0 && $setUser !== NULL) {
				if (SERIA_AuthenticationState::available()) {
					$loginState = new SERIA_AuthenticationState();
					if (!$loginState->exists('autoDiscovery'))
						$loginState = false;
				} else
					$loginState = false;
				if ($loginState || SERIA_Base::user() === false)
					SERIA_Authproviders::automaticDiscovery();
			}
		}
		public function platformBootComplete()
		{
			if (sizeof($_POST) == 0) {
				if (SERIA_Base::user() !== false) {
					if (($provider =& $this->loggedInByProvider())) {
						SERIA_Base::debug('Calling automatic logout discovery..');
						/*
						 * Discover remote logout.
						 */
						$provider->automaticDiscovery();
					} else
						SERIA_Base::debug('Automatic logout discovery skipped..');
				}
			}
		}
		public function beforeLogout($user)
		{
			if (SERIA_DEBUG) {
				SERIA_Base::debug('Authproviders: Logging user out (received hook)');
				if (class_exists('DebugLogging'))
					DebugLogging::printBacktrace();
			}
			if (($provider =& $this->loggedInByProvider())) {
				if (method_exists($provider, 'beforeLogout'))
					$provider->beforeLogout();
			}
		}
		public function logout($user)
		{
			SERIA_Base::debug('Final logout event seen');
			/*
			 * Publish domain logout..
			 */
			$this->setLoginDiscoveryCookie('logout');
			if (($provider =& $this->loggedInByProvider())) {
				SERIA_Base::debug('Authproviders: Logout event will be sent to provider '.$provider->getName());
				$provider->logout();
				$this->loggedInByProvider(null);
			}
		}
		public function successfulLogin($user)
		{
			SERIA_Base::debug('Authproviders: Logged in!');
			$this->setLoginDiscoveryCookie('auto:'.mt_rand().':'.time());
		}
		public function handleLogin()
		{
			if (sizeof($_POST) && !isset($_GET['authenticationModule'])) {
				/*
				 * This is the local login provider that is receiving credentials.
				 * Other modules that wish to receive post-data must pass the
				 * authenticationModule=$provider->getProviderId()
				 * GET-parameter.
				 */
				return false; /* Fall back to local */
			}
			if (isset($_GET['continue']))
				$continue = $_GET['continue'];
			else
				$continue = SERIA_HTTP_ROOT;
			/*
			 * Authentication state entry point (ASEP)
			 */
			$state = new SERIA_AuthenticationState();
			if (!$state->exists('continue'))
				$state->set('continue', $continue);
			$retv = SERIA_Authproviders::handleLogin();
			if ($retv && is_string($retv)) {
				SERIA_Base::redirectTo($state->stampUrl($retv));
				die();
			}
			return $retv;
		}
		public function handleGuestLogin()
		{
			if (sizeof($_POST) && !isset($_GET['authenticationModule'])) {
				/*
				 * This is the local login provider that is receiving credentials.
				 * Other modules that wish to receive post-data must pass the
				 * authenticationModule=$provider->getProviderId()
				 * GET-parameter.
				 */
				return false; /* Fall back to local */
			}
			if (isset($_GET['continue']))
				$continue = $_GET['continue'];
			else
				$continue = SERIA_HTTP_ROOT;
			/*
			 * Authentication state entry point (ASEP)
			 */
			$state = new SERIA_AuthenticationState();
			if (!$state->exists('continue'))
				$state->set('continue', $continue);
			$retv = SERIA_Authproviders::handleGuestLogin();
			if ($retv && is_string($retv)) {
				SERIA_Base::redirectTo($state->stampUrl($retv));
				die();
			}
			return $retv;
		}

		public function getTemplate($name)
		{
			$template_file = '/components/Authproviders/templates/'.$name.'.php';
			$search_order = array(
				SERIA_ROOT.'/templates/Authproviders/'.$name.'.php',
				SERIA_ROOT.$template_file,
				SERIA_ROOT.'/seria'.$template_file
			);
			foreach ($search_order as $search) {
				if (file_exists($search))
					return $search;
			}
		}
		public function parseTemplate($name, $params)
		{
			$tpl = $this->getTemplate($name);
			if ($tpl)
				return SERIA_Template::parse($tpl, $params);
			throw new SERIA_Exception('No such template: '.$name);
		}
		public function parseTemplateToString($name, $params)
		{
			$tpl = $this->getTemplate($name);
			if ($tpl)
				return SERIA_Template::parseToString($tpl, $params);
			throw new SERIA_Exception('No such template: '.$name);
		}

		public function setLoginDiscoveryCookie($value)
		{
			if (($publishDomain = SERIA_Base::getParam('authproviders_publish_domain'))) {
				$loginServerKey = 'logindiscovery'.sha1($_SERVER['SERVER_NAME']);
				if (!setCookie($loginServerKey, $value, time() + SERIA_SESSION_TTL * 100, '/', $publishDomain, false, false))
					throw new SERIA_Exception('Failed to set login discovery cookie!');
				$this->autoDiscoveryCookie = $value;
			}
		}
		public function getLoginDiscoveryCookie()
		{
			if (SERIA_Base::getParam('authproviders_publish_domain')) {
				$loginServerKey = 'logindiscovery'.sha1($_SERVER['SERVER_NAME']);
				if ($this->autoDiscoveryCookie === false && isset($_COOKIE[$loginServerKey]))
					$this->autoDiscoveryCookie = $_COOKIE[$loginServerKey];
			}
			return $this->autoDiscoveryCookie;
		}

		public function loggedInByProvider($provider=false)
		{
			if ($this->loggedInByProvider === null &&
			    isset($_SESSION['seria_authproviders_logged_in_by_provider']) &&
			    $_SESSION['seria_authproviders_logged_in_by_provider']) {
				SERIA_Authproviders::loadProviders();
				$providers =& SERIA_Authproviders::getProviders();
				foreach ($providers as &$try_provider) {
					if ($try_provider->getProviderId() == $_SESSION['seria_authproviders_logged_in_by_provider']) {
						$this->loggedInByProvider =& $try_provider;
					}
				}
				unset($try_provider);
				unset($providers);
			}
			if ($provider === false) {
				if ($this->loggedInByProvider)
					return $this->loggedInByProvider;
				return false;
			}
			$prev = $this->loggedInByProvider;
			$this->loggedInByProvider = $provider;
			if ($provider !== null)
				$_SESSION['seria_authproviders_logged_in_by_provider'] = $provider->getProviderId();
			else {
				if (isset($_SESSION['seria_authproviders_logged_in_by_provider'])) {
					$_SESSION['seria_authproviders_logged_in_by_provider'] = false;
					unset($_SESSION['seria_authproviders_logged_in_by_provider']);
				}
			}
			if ($prev)
				return $prev;
			else
				return false;
		}

		public function showConfigurationPage()
		{
			SERIA_Base::pageRequires('admin');
			$template = new SERIA_MetaTemplate();
			echo $template->parse($this->getInstallationPath().'/metaTemplates/config.php');
			die();
		}
		public function showConfigCheck()
		{
			SERIA_Base::pageRequires('admin');
			$template = new SERIA_MetaTemplate();
			echo $template->parse($this->getInstallationPath().'/metaTemplates/configCheck.php');
			die();
		}
		public function showUsermanagementBlockage()
		{
			SERIA_Base::pageRequires('admin');
			$template = new SERIA_MetaTemplate();
			echo $template->parse($this->getInstallationPath().'/metaTemplates/remoteUsermanagement.php');
			die();
		}
	}
